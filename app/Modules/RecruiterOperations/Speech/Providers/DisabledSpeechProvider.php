<?php

namespace App\Modules\RecruiterOperations\Speech\Providers;

use App\Modules\RecruiterOperations\Exceptions\TrainingSpeechException;
use App\Modules\RecruiterOperations\Speech\TrainingAudio;
use App\Modules\RecruiterOperations\Speech\TrainingSpeechDelivery;
use App\Modules\RecruiterOperations\Speech\TrainingSpeechProvider;
use App\Modules\RecruiterOperations\Speech\VoiceProfile;

/**
 * Listening switched off on purpose. The lesson page says so rather than
 * showing a player that cannot play.
 */
class DisabledSpeechProvider implements TrainingSpeechProvider
{
    public function name(): string
    {
        return 'disabled';
    }

    public function delivery(): TrainingSpeechDelivery
    {
        return TrainingSpeechDelivery::Unavailable;
    }

    public function supportedVoices(): array
    {
        return [];
    }

    public function synthesize(string $text, VoiceProfile $voice): TrainingAudio
    {
        throw new TrainingSpeechException('Listening to lessons is turned off.');
    }
}
