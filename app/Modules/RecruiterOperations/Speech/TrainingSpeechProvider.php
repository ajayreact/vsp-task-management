<?php

namespace App\Modules\RecruiterOperations\Speech;

use App\Modules\RecruiterOperations\Exceptions\TrainingSpeechException;

/**
 * A text-to-speech backend for "Listen to Lesson". The rest of Training talks
 * only to TrainingSpeechService, never to a provider directly, so swapping
 * providers (or adding uploaded professional audio later) changes nothing
 * else.
 */
interface TrainingSpeechProvider
{
    public function name(): string;

    public function delivery(): TrainingSpeechDelivery;

    /**
     * Voice profile keys this provider can really speak. The voice selector
     * offers only these.
     *
     * @return list<string>
     */
    public function supportedVoices(): array;

    /**
     * Produce audio for server-delivered providers.
     *
     * @throws TrainingSpeechException when the provider cannot produce audio
     */
    public function synthesize(string $text, VoiceProfile $voice): TrainingAudio;
}
