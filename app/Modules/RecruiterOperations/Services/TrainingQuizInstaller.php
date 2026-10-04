<?php

namespace App\Modules\RecruiterOperations\Services;

use App\Modules\Core\Models\User;
use App\Modules\RecruiterOperations\Enums\AssessmentType;
use App\Modules\RecruiterOperations\Enums\TrainingContentStatus;
use App\Modules\RecruiterOperations\Models\Assessment;
use App\Modules\RecruiterOperations\Models\AssessmentVersion;
use App\Modules\RecruiterOperations\Models\TrainingCourse;
use App\Modules\RecruiterOperations\Services\Assessments\AssessmentContentService;
use App\Modules\RecruiterOperations\Services\Assessments\AssessmentQuestionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Creates a course's planned training quizzes and links them to the course's
 * draft version, through the normal assessment and training services:
 *
 * - a quiz that does not exist yet (matched by title) is created with its
 *   questions and its Version 1 is published, because only a published quiz
 *   version can be linked to a course;
 * - an existing quiz is left as it is; its current published version is used;
 * - the quiz is linked to the course's draft version only. The published
 *   course version, its assignments and any recruiter attempts are never
 *   touched, and the course version itself is not published.
 */
class TrainingQuizInstaller
{
    public const CREATED = 'Created and published Version 1';

    public const EXISTS = 'Already exists';

    public const LINKED = 'Linked to draft';

    public const ALREADY_LINKED = 'Already linked';

    public const NOT_PUBLISHED = 'Not linked: quiz has no published version';

    public const NO_DRAFT = 'Not linked: course has no draft version';

    public function __construct(
        protected AssessmentContentService $assessments,
        protected AssessmentQuestionService $questions,
        protected TrainingContentService $content,
    ) {}

    /**
     * @param  array{course: string, quizzes: list<array{title: string, description: string, settings: array<string, mixed>, questions: list<array{type: string, category: string, prompt: string, explanation: string, options: list<array{text: string, is_correct: bool}>}>}>}  $plan
     * @return list<array{quiz: string, questions: int, quiz_action: string, link: string}>
     */
    public function install(array $plan, ?User $actor, bool $dryRun): array
    {
        $course = TrainingCourse::query()->where('slug', Str::slug($plan['course']))->first();
        $draft = $course?->versions()->where('status', TrainingContentStatus::Draft->value)->first();
        $report = [];

        foreach ($plan['quizzes'] as $quiz) {
            $report[] = DB::transaction(function () use ($quiz, $draft, $actor, $dryRun) {
                $assessment = $this->find($quiz['title']);
                $action = $assessment === null ? self::CREATED : self::EXISTS;

                if ($assessment === null && ! $dryRun && $actor !== null) {
                    $assessment = $this->create($quiz, $actor);
                }

                $version = $assessment?->currentVersion;
                $link = match (true) {
                    $draft === null => self::NO_DRAFT,
                    $assessment !== null && $draft->assessmentVersions()->where('ro_assessment_versions.assessment_id', $assessment->id)->exists() => self::ALREADY_LINKED,
                    $assessment !== null && $version === null => self::NOT_PUBLISHED,
                    default => self::LINKED,
                };

                if ($link === self::LINKED && $version instanceof AssessmentVersion && ! $dryRun && $actor !== null) {
                    $this->content->attachAssessment($draft, $version, $actor);
                }

                return ['quiz' => $quiz['title'], 'questions' => count($quiz['questions']), 'quiz_action' => $action, 'link' => $link];
            });
        }

        return $report;
    }

    protected function find(string $title): ?Assessment
    {
        return Assessment::query()
            ->with('currentVersion')
            ->where('type', AssessmentType::TrainingQuiz->value)
            ->where('title', $title)
            ->first();
    }

    /**
     * @param  array{title: string, description: string, settings: array<string, mixed>, questions: list<array{type: string, category: string, prompt: string, explanation: string, options: list<array{text: string, is_correct: bool}>}>}  $quiz
     */
    protected function create(array $quiz, User $actor): Assessment
    {
        $assessment = $this->assessments->createAssessment([
            'title' => $quiz['title'],
            'description' => $quiz['description'],
            'type' => AssessmentType::TrainingQuiz->value,
            ...$quiz['settings'],
        ], $actor);

        /** @var AssessmentVersion $version */
        $version = $assessment->versions()->firstOrFail();

        foreach ($quiz['questions'] as $question) {
            $this->questions->createVersionQuestion($version, [...$question, 'points' => 1], $actor);
        }

        $this->assessments->publishVersion($version, $actor);

        return $assessment->refresh()->load('currentVersion');
    }
}
