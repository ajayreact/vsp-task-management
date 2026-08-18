<?php

namespace App\Modules\TaskManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\TaskManagement\Models\Deliverable;
use App\Modules\TaskManagement\Models\DeliverableShareLink;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class DeliverableShareController extends Controller
{
    public function show(string $token): Response
    {
        $link = $this->linkForToken($token);

        $deliverable = $link->deliverable;
        $task = $deliverable->task;

        return Inertia::render('TaskManagement/share/show', [
            'brand' => config('app.name'),
            'client_name' => $task->project->company->name,
            'project_name' => $task->project->name,
            'task_title' => $task->title,
            'deliverable' => [
                'title' => 'Version '.$deliverable->version,
                'status' => $deliverable->status->label(),
                'submitted_at' => $deliverable->submitted_at->toDateString(),
            ],
            'files' => $this->publicProofFiles($link),
        ]);
    }

    public function file(string $token, string $mediaUuid): SymfonyResponse
    {
        $link = $this->linkForToken($token);
        $media = $this->proofMediaForLink($link, $mediaUuid);

        abort_unless(is_file($media->getPath()), 404);

        return $media->toInlineResponse(request());
    }

    protected function linkForToken(string $token): DeliverableShareLink
    {
        return DeliverableShareLink::query()
            ->where('token', $token)
            ->with(['deliverable.task.project.company'])
            ->firstOrFail();
    }

    /**
     * @return list<array{name: string, mime: string, size: int, url: string}>
     */
    protected function publicProofFiles(DeliverableShareLink $link): array
    {
        return $link->deliverable
            ->getMedia('proofs')
            ->map(fn (Media $media) => [
                'name' => $media->file_name,
                'mime' => $media->mime_type,
                'size' => $media->size,
                'url' => $link->publicFileUrl($media->uuid),
            ])
            ->values()
            ->all();
    }

    protected function proofMediaForLink(DeliverableShareLink $link, string $mediaUuid): Media
    {
        $media = Media::query()->where('uuid', $mediaUuid)->firstOrFail();
        $deliverableClass = (new Deliverable)->getMorphClass();

        abort_unless(
            $media->collection_name === 'proofs'
            && $media->model_type === $deliverableClass
            && (int) $media->model_id === (int) $link->tm_deliverable_id,
            404,
        );

        return $media;
    }
}
