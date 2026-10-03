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

        // Offered first on every English lesson.
        'default_voice' => 'indian_english',

        // A voice reads the lesson language of its locale: en-* voices read
        // English, te-* voices read the Telugu translation.
        'voices' => [
            'indian_english' => ['label' => 'Indian English', 'locale' => 'en-IN', 'flag' => '🇮🇳'],
            'us_english' => ['label' => 'US English', 'locale' => 'en-US', 'flag' => '🇺🇸'],
            'uk_english' => ['label' => 'UK English', 'locale' => 'en-GB', 'flag' => '🇬🇧'],
            'telugu' => ['label' => 'Telugu', 'locale' => 'te-IN', 'flag' => '🇮🇳'],
        ],

        'max_characters' => (int) env('RECRUITER_TRAINING_TTS_MAX_CHARS', 20000),

        // Spoken words per minute at 1x, used to estimate duration on devices
        // that do not report it.
        'words_per_minute' => 150,
    ],

    // Which draft lessons recruiter:training-review-flags marks as needing a
    // compliance review: every lesson of these levels, plus any lesson whose
    // English mentions one of the phrases. A manager can change the flag on
    // any lesson afterwards; nothing is ever approved automatically.
    'compliance_review' => [
        'levels' => [2, 3, 9],
        'phrases' => ['legal advice'],
    ],

    // Development-only logins made by LocalTrainingTestAccountsSeeder, which
    // refuses to run outside the local and testing environments. There is no
    // default password: without LOCAL_TEST_PASSWORD a random one is printed.
    'local_test_accounts' => [
        'lead_email' => env('LOCAL_TEST_LEAD_EMAIL', 'training.lead@vsp.test'),
        'recruiter_email' => env('LOCAL_TEST_RECRUITER_EMAIL', 'training.recruiter@vsp.test'),
        'password' => env('LOCAL_TEST_PASSWORD'),
    ],

];
