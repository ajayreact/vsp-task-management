<?php

namespace App\Modules\RecruiterOperations\Speech\Providers;

use App\Modules\RecruiterOperations\Exceptions\TrainingSpeechException;
use App\Modules\RecruiterOperations\Speech\TrainingAudio;
use App\Modules\RecruiterOperations\Speech\TrainingSpeechDelivery;
use App\Modules\RecruiterOperations\Speech\TrainingSpeechProvider;
use App\Modules\RecruiterOperations\Speech\VoiceProfile;

/**
 * The default: the recruiter's browser reads the lesson text with the Web
 * Speech API, asking for the profile's locale (en-IN for Indian English).
 * Needs no key and costs nothing. Which voices exist depends on the device,
 * so the player tells the recruiter when no voice for the locale is installed
 * instead of pretending one is.
 */
class BrowserSpeechProvider implements TrainingSpeechProvider
{
    public function name(): string
    {
        return 'browser';
    }

    public function delivery(): TrainingSpeechDelivery
    {
        return TrainingSpeechDelivery::Device;
    }

    public function supportedVoices(): array
    {
        return array_keys((array) config('recruiter-training.speech.voices', []));
    }

    public function synthesize(string $text, VoiceProfile $voice): TrainingAudio
    {
        throw new TrainingSpeechException('This lesson is read aloud by your browser, not as an audio file.');
    }
}
