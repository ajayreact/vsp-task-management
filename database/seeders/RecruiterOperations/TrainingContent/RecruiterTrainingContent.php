<?php

namespace Database\Seeders\RecruiterOperations\TrainingContent;

/**
 * Written content for the 10-level OPT recruiter curriculum, keyed by level,
 * course title and lesson title exactly as RecruiterTrainingCurriculumSeeder
 * creates them. Each level-*.php file in this folder returns
 * ['level' => int, 'course' => string, 'lessons' => [title => body]]; a level
 * may be split across several files.
 *
 * Bodies are plain text (the lesson page shows them as-is and the audio player
 * reads them aloud), so they avoid bullet symbols and arrows: every line is a
 * heading or a complete sentence.
 */
class RecruiterTrainingContent
{
    /**
     * @return list<array{level: int, course: string, lessons: array<string, string>}>
     */
    public static function all(): array
    {
        $levels = [];

        foreach (glob(__DIR__.'/level-*.php') ?: [] as $file) {
            /** @var array{level: int, course: string, lessons: array<string, string>} $part */
            $part = require $file;
            $levels[$part['level']] ??= ['level' => $part['level'], 'course' => $part['course'], 'lessons' => []];
            $levels[$part['level']]['lessons'] += $part['lessons'];
        }

        ksort($levels);

        return array_values($levels);
    }

    /**
     * Redesigned structured lessons, by level, lesson title and language code
     * ('en' is the source, 'te' its Telugu translation). Each structured/*.php
     * file returns ['level' => int, 'lessons' => [title => ['en' => sections,
     * 'te' => sections]]], sections being lists of kind, heading and body.
     *
     * @return array<int, array<string, array<string, list<array{kind: string, heading: string, body: string}>>>>
     */
    public static function redesigned(): array
    {
        $levels = [];

        foreach (glob(__DIR__.'/structured/level-*.php') ?: [] as $file) {
            /** @var array{level: int, lessons: array<string, array<string, list<array{kind: string, heading: string, body: string}>>>} $part */
            $part = require $file;
            $levels[$part['level']] = ($levels[$part['level']] ?? []) + $part['lessons'];
        }

        ksort($levels);

        return $levels;
    }

    /**
     * The four-course OPT Recruiter track, in course order. Each
     * opt-track/course-*.php file returns the existing course it reorganises
     * ('course'), the title it is meant to carry ('title'), and its modules:
     * module name => planned lessons, each with a 'title' and optionally the
     * lesson it replaces ('from'), a lesson to copy from another course
     * ('copy' => [course, lesson]), new English sections ('en'), whether it
     * needs compliance review ('compliance') and the items reviewers must
     * confirm ('review').
     *
     * @return list<array{course: string, title: string, modules: array<string, list<array<string, mixed>>>}>
     */
    public static function optTrack(): array
    {
        $files = glob(__DIR__.'/opt-track/course-*.php') ?: [];
        sort($files);

        /** @var list<array{course: string, title: string, modules: array<string, list<array<string, mixed>>>}> */
        return array_map(fn (string $file) => require $file, $files);
    }

    /**
     * Combined-lesson plans for the general recruiter courses outside the OPT
     * track (Levels 4 to 6), in level order. Each compact-courses/level-*.php
     * file has the same shape as an opt-track course plan.
     *
     * @return list<array{course: string, title: string, modules: array<string, list<array<string, mixed>>>}>
     */
    public static function compactCourses(): array
    {
        $files = glob(__DIR__.'/compact-courses/level-*.php') ?: [];
        sort($files);

        /** @var list<array{course: string, title: string, modules: array<string, list<array<string, mixed>>>}> */
        return array_map(fn (string $file) => require $file, $files);
    }

    /**
     * Training quizzes for an OPT track course: the course they link to
     * ('course') and each quiz's title, description, version settings and
     * questions, in the shape AssessmentQuestionService accepts.
     *
     * @return array{course: string, quizzes: list<array{title: string, description: string, settings: array<string, mixed>, questions: list<array{type: string, category: string, prompt: string, explanation: string, options: list<array{text: string, is_correct: bool}>}>}>}
     */
    public static function optQuizzes(): array
    {
        /** @var array{course: string, quizzes: list<array{title: string, description: string, settings: array<string, mixed>, questions: list<array{type: string, category: string, prompt: string, explanation: string, options: list<array{text: string, is_correct: bool}>}>}>} */
        return require __DIR__.'/opt-track/quizzes.php';
    }

    /**
     * Fingerprints of the bodies earlier releases shipped, which
     * recruiter:training-content --refresh is allowed to replace.
     *
     * @return array<int, array<string, list<string>>>
     */
    public static function previous(): array
    {
        /** @var array<int, array<string, list<string>>> */
        return require __DIR__.'/previous-content.php';
    }
}
