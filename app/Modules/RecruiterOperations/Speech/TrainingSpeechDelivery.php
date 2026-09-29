<?php

namespace App\Modules\RecruiterOperations\Speech;

/**
 * How a provider's speech reaches the recruiter.
 */
enum TrainingSpeechDelivery: string
{
    /** Spoken by the recruiter's browser from lesson text. */
    case Device = 'device';

    /** An audio file produced on the server and streamed to the player. */
    case Audio = 'audio';

    /** Listening is switched off. */
    case Unavailable = 'unavailable';
}
