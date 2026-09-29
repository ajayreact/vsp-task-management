<?php

namespace App\Modules\RecruiterOperations\Speech;

use App\Modules\RecruiterOperations\Speech\Providers\BrowserSpeechProvider;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\Log;

/**
 * Picks the configured speech provider. A missing or unknown driver falls back
 * to the browser provider, so Listen to Lesson keeps working with Indian
 * English on every lesson even when configuration is wrong.
 */
class TrainingSpeechProviderResolver
{
    public const FALLBACK_DRIVER = 'browser';

    public function __construct(protected Container $container) {}

    public function resolve(?string $driver): TrainingSpeechProvider
    {
        $providers = (array) config('recruiter-training.speech.providers', []);
        $class = $driver !== null ? ($providers[$driver] ?? null) : null;

        if (is_string($class) && is_subclass_of($class, TrainingSpeechProvider::class)) {
            /** @var TrainingSpeechProvider */
            return $this->container->make($class);
        }

        if (filled($driver) && $driver !== self::FALLBACK_DRIVER) {
            Log::warning('Unknown recruiter training speech driver; using the browser provider.', ['driver' => $driver]);
        }

        return $this->container->make(BrowserSpeechProvider::class);
    }
}
