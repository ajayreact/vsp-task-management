<?php

namespace App\Modules\RecruiterOperations\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\RecruiterOperations\Enums\TrainingLanguage;
use App\Modules\RecruiterOperations\Exceptions\TrainingSpeechException;
use App\Modules\RecruiterOperations\Models\TrainingLesson;
use App\Modules\RecruiterOperations\Services\TrainingSpeechService;
use App\Modules\RecruiterOperations\Speech\TrainingSpeechDelivery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
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
     * The speech for one lesson in the chosen language and voice (English and
     * its default voice when not given). Unknown languages or voices, or a
     * voice for another language, get 422.
     */
    public function speech(Request $request, TrainingLesson $trainingLesson): JsonResponse
    {
        $this->authorize('listen', $trainingLesson);

        $language = $this->languageParameter($request);
        $voice = $this->speech->resolveVoice($this->voiceParameter($request), $language);
        $payload = $this->speech->speechFor($trainingLesson, $voice, $language);

        return response()->json([
            ...$payload,
            'language' => $language->value,
            'audio_url' => $this->speech->delivery() === TrainingSpeechDelivery::Audio && $payload['has_text']
                ? route('recruiter.training.lessons.audio', array_filter([
                    $trainingLesson,
                    'voice' => $voice->key,
                    'language' => $language->isCanonical() ? null : $language->value,
                ]))
                : null,
        ]);
    }

    /**
     * Server-generated audio, for providers that produce files.
     */
    public function audio(Request $request, TrainingLesson $trainingLesson): SymfonyResponse
    {
        $this->authorize('listen', $trainingLesson);

        $language = $this->languageParameter($request);
        $voice = $this->speech->resolveVoice($this->voiceParameter($request), $language);

        abort_unless($this->speech->delivery() === TrainingSpeechDelivery::Audio, 404);

        try {
            $audio = $this->speech->generateLessonAudio($trainingLesson, $voice, $language);
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

    protected function languageParameter(Request $request): TrainingLanguage
    {
        $value = $request->query('language');

        if ($value === null || $value === '') {
            return TrainingLanguage::default();
        }

        $language = is_string($value) ? TrainingLanguage::tryFrom($value) : null;

        if ($language === null) {
            throw ValidationException::withMessages(['language' => 'That language is not available.']);
        }

        return $language;
    }

    protected function voiceParameter(Request $request): ?string
    {
        $voice = $request->query('voice');

        return is_string($voice) ? $voice : null;
    }
}
