<?php

namespace App\Modules\RecruiterOperations\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\RecruiterOperations\Exceptions\TrainingSpeechException;
use App\Modules\RecruiterOperations\Models\TrainingLesson;
use App\Modules\RecruiterOperations\Services\TrainingSpeechService;
use App\Modules\RecruiterOperations\Speech\TrainingSpeechDelivery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Lesson audio and files. Open only to training managers and recruiters
 * assigned the lesson's version (TrainingLessonPolicy), checked on the route
 * and again here. Files live on the private disk and are streamed from here.
 */
class TrainingLessonMediaController extends Controller
{
    public function __construct(protected TrainingSpeechService $speech) {}

    /**
     * The speech for one lesson in the chosen voice. Unknown voices get 422.
     */
    public function speech(Request $request, TrainingLesson $trainingLesson): JsonResponse
    {
        $this->authorize('listen', $trainingLesson);

        $voice = $this->speech->resolveVoice($this->voiceParameter($request));
        $payload = $this->speech->speechFor($trainingLesson, $voice);

        return response()->json([
            ...$payload,
            'audio_url' => $this->speech->delivery() === TrainingSpeechDelivery::Audio && $payload['has_text']
                ? route('recruiter.training.lessons.audio', [$trainingLesson, 'voice' => $voice->key])
                : null,
        ]);
    }

    /**
     * Server-generated audio, for providers that produce files.
     */
    public function audio(Request $request, TrainingLesson $trainingLesson): SymfonyResponse
    {
        $this->authorize('listen', $trainingLesson);

        $voice = $this->speech->resolveVoice($this->voiceParameter($request));

        abort_unless($this->speech->delivery() === TrainingSpeechDelivery::Audio, 404);

        try {
            $audio = $this->speech->generateLessonAudio($trainingLesson, $voice);
        } catch (TrainingSpeechException $exception) {
            return response()->json(['message' => $exception->getMessage()], 503);
        }

        return response($audio->contents, 200, [
            'Content-Type' => $audio->mimeType,
            'Content-Length' => (string) strlen($audio->contents),
            'Content-Disposition' => 'inline; filename="lesson-'.$trainingLesson->id.'-'.$voice->key.'.'.$audio->extension.'"',
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function file(Request $request, TrainingLesson $trainingLesson): SymfonyResponse
    {
        $this->authorize('viewFile', $trainingLesson);

        $media = $trainingLesson->file();
        abort_if($media === null, 404);

        $path = $media->getPath();

        if (is_file($path)) {
            return response()->file($path, [
                'Content-Type' => $media->mime_type,
                'Content-Disposition' => 'inline; filename="'.addcslashes($media->file_name, '"\\').'"',
                'Cache-Control' => 'private, max-age=0, must-revalidate',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        }

        return $media->toInlineResponse($request);
    }

    protected function voiceParameter(Request $request): ?string
    {
        $voice = $request->query('voice');

        return is_string($voice) ? $voice : null;
    }
}
