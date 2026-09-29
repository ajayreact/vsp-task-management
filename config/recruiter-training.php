<?php

use App\Modules\RecruiterOperations\Speech\Providers\BrowserSpeechProvider;
use App\Modules\RecruiterOperations\Speech\Providers\DisabledSpeechProvider;

/*
|--------------------------------------------------------------------------
| Recruiter Training
|--------------------------------------------------------------------------
|
| Environment variables (all optional):
|
|   RECRUITER_TRAINING_TTS_DRIVER      Which speech provider reads lessons aloud.
|                                      "browser" (default) speaks on the
|                                      recruiter's own device with the Web
|                                      Speech API: no API key, no cost.
|                                      "disabled" turns Listen to Lesson off.
|                                      An unknown value falls back to "browser".
|   RECRUITER_TRAINING_TTS_MAX_CHARS   Longest lesson text sent for speech.
|   RECRUITER_TRAINING_MEDIA_DISK      Private disk for lesson files and any
|                                      generated audio. Must not be public.
|   RECRUITER_TRAINING_MAX_UPLOAD_KB   Largest lesson file accepted.
|
| A server-side provider (one that returns audio files) is added by writing a
| class that implements TrainingSpeechProvider, listing it under
| speech.providers and pointing RECRUITER_TRAINING_TTS_DRIVER at its key. Its
| credentials belong in .env and config/services.php, never in this file.
|
*/

return [

    'media' => [
        'disk' => env('RECRUITER_TRAINING_MEDIA_DISK', 'local'),
        'max_kilobytes' => (int) env('RECRUITER_TRAINING_MAX_UPLOAD_KB', 600 * 1024),
    ],

    'speech' => [

        'driver' => env('RECRUITER_TRAINING_TTS_DRIVER', 'browser'),

        'providers' => [
            'browser' => BrowserSpeechProvider::class,
            'disabled' => DisabledSpeechProvider::class,
        ],

        // Offered first on every lesson.
        'default_voice' => 'indian_english',

        'voices' => [
            'indian_english' => ['label' => 'Indian English', 'locale' => 'en-IN', 'flag' => '🇮🇳'],
            'us_english' => ['label' => 'US English', 'locale' => 'en-US', 'flag' => '🇺🇸'],
            'uk_english' => ['label' => 'UK English', 'locale' => 'en-GB', 'flag' => '🇬🇧'],
        ],

        'max_characters' => (int) env('RECRUITER_TRAINING_TTS_MAX_CHARS', 20000),

        // Spoken words per minute at 1x, used to estimate duration on devices
        // that do not report it.
        'words_per_minute' => 150,
    ],

];
