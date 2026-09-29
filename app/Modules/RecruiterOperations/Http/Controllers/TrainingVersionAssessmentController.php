<?php

namespace App\Modules\RecruiterOperations\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\RecruiterOperations\Models\AssessmentVersion;
use App\Modules\RecruiterOperations\Models\TrainingCourseVersion;
use App\Modules\RecruiterOperations\Services\TrainingContentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Links quizzes to a draft training course version
 * (recruiter.training.manage).
 */
class TrainingVersionAssessmentController extends Controller
{
    public function __construct(protected TrainingContentService $content) {}

    public function store(Request $request, TrainingCourseVersion $trainingVersion): RedirectResponse
    {
        $validated = $request->validate([
            'assessment_version_id' => ['required', 'integer', 'exists:ro_assessment_versions,id'],
        ], [], ['assessment_version_id' => 'quiz']);

        $quiz = AssessmentVersion::query()->findOrFail((int) $validated['assessment_version_id']);
        $this->content->attachAssessment($trainingVersion, $quiz, $request->user());

        return back()->with('success', 'Quiz linked to this course version.');
    }

    public function destroy(Request $request, TrainingCourseVersion $trainingVersion, AssessmentVersion $assessmentVersion): RedirectResponse
    {
        $this->content->detachAssessment($trainingVersion, $assessmentVersion, $request->user());

        return back()->with('success', 'Quiz removed from this course version.');
    }
}
