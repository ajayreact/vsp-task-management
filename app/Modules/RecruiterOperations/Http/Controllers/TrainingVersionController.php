<?php

namespace App\Modules\RecruiterOperations\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\RecruiterOperations\Http\Requests\TrainingVersionRequest;
use App\Modules\RecruiterOperations\Models\TrainingCourseVersion;
use App\Modules\RecruiterOperations\Services\TrainingContentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Draft edits and the publish/archive/discard actions on a course version.
 * Each authorizes on the route, so a published version refuses edits with 403
 * before any validation.
 */
class TrainingVersionController extends Controller
{
    public function __construct(protected TrainingContentService $content) {}

    public function update(TrainingVersionRequest $request, TrainingCourseVersion $trainingVersion): RedirectResponse
    {
        $this->content->updateVersion($trainingVersion, $request->validated(), $request->user());

        return $this->backToCourse($trainingVersion, 'Draft details saved.');
    }

    public function publish(Request $request, TrainingCourseVersion $trainingVersion): RedirectResponse
    {
        $this->content->publishVersion($trainingVersion, $request->user());

        return $this->backToCourse($trainingVersion, "{$trainingVersion->label()} published. New assignments will receive this version.");
    }

    public function archive(Request $request, TrainingCourseVersion $trainingVersion): RedirectResponse
    {
        $this->content->archiveVersion($trainingVersion, $request->user());

        return $this->backToCourse($trainingVersion, "{$trainingVersion->label()} archived. Recruiters already on it can still finish it.");
    }

    public function destroy(Request $request, TrainingCourseVersion $trainingVersion): RedirectResponse
    {
        $courseId = $trainingVersion->course_id;
        $label = $trainingVersion->label();
        $this->content->discardDraft($trainingVersion, $request->user());

        return to_route('recruiter.training.manage.courses.show', $courseId)->with('success', "Draft {$label} discarded.");
    }

    protected function backToCourse(TrainingCourseVersion $version, string $message): RedirectResponse
    {
        return to_route('recruiter.training.manage.courses.show', [$version->course_id, 'version' => $version->id])
            ->with('success', $message);
    }
}
