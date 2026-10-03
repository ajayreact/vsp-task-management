<?php

namespace App\Modules\RecruiterOperations\Services;

use App\Modules\RecruiterOperations\Enums\TrainingLanguage;
use App\Modules\RecruiterOperations\Exceptions\TrainingSpeechException;
use App\Modules\RecruiterOperations\Models\TrainingLesson;
use App\Modules\RecruiterOperations\Speech\TrainingAudio;
use App\Modules\RecruiterOperations\Speech\TrainingSpeechDelivery;
use App\Modules\RecruiterOperations\Speech\TrainingSpeechProvider;
use App\Modules\RecruiterOperations\Speech\VoiceProfile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * "Listen to Lesson" for every lesson. Decides which voices are offered
 * (Indian English first), what text is spoken, and how the speech reaches the
 * recruiter, through whichever TrainingSpeechProvider is configured. Nothing
 * outside this class knows which provider is in use.
 */
class TrainingSpeechService
{
    /**
     * Longest piece handed to the browser in one go; some browsers cut off
     * longer utterances.
     */
    public const SEGMENT_CHARACTERS = 220;

    protected TrainingLessonContentService $contents;

    public function __construct(protected TrainingSpeechProvider $provider, ?TrainingLessonContentService $contents = null)
    {
        $this->contents = $contents ?? new TrainingLessonContentService;
    }

    public function provider(): TrainingSpeechProvider
    {
        return $this->provider;
    }

    public function delivery(): TrainingSpeechDelivery
    {
        return $this->provider->delivery();
    }

    /**
     * Configured voice profiles the provider really supports for one lesson
     * language (English by default), default first. A voice belongs to the
     * language of its locale: en-IN is English, te-IN is Telugu.
     *
     * @return list<VoiceProfile>
     */
    public function voices(?TrainingLanguage $language = null): array
    {
        if ($this->provider->delivery() === TrainingSpeechDelivery::Unavailable) {
            return [];
        }

        $language ??= TrainingLanguage::default();
        $supported = $this->provider->supportedVoices();
        $default = (string) config('recruiter-training.speech.default_voice', 'indian_english');
        $voices = [];

        foreach ((array) config('recruiter-training.speech.voices', []) as $key => $profile) {
            if (! is_string($key) || ! in_array($key, $supported, true) || ! is_array($profile)) {
                continue;
            }

            $locale = (string) ($profile['locale'] ?? 'en');

            if (self::languageOfLocale($locale) !== $language) {
                continue;
            }

            $voices[] = new VoiceProfile(
                $key,
                (string) ($profile['label'] ?? $key),
                $locale,
                (string) ($profile['flag'] ?? ''),
            );
        }

        usort($voices, fn (VoiceProfile $a, VoiceProfile $b) => ($b->key === $default) <=> ($a->key === $default));

        return $voices;
    }

    public static function languageOfLocale(string $locale): ?TrainingLanguage
    {
        return TrainingLanguage::tryFrom(strtolower((string) preg_split('/[-_]/', $locale)[0]));
    }

    /**
     * @return list<array{key: string, label: string, locale: string, flag: string}>
     */
    public function voiceOptions(?TrainingLanguage $language = null): array
    {
        return array_map(fn (VoiceProfile $voice) => $voice->toArray(), $this->voices($language));
    }

    /**
     * The requested voice, or the language's default when none is asked for.
     * A voice the provider does not offer, or one for another language, is
     * refused, never silently swapped.
     *
     * @throws ValidationException
     */
    public function resolveVoice(?string $key, ?TrainingLanguage $language = null): VoiceProfile
    {
        $voices = $this->voices($language);

        if ($voices === []) {
            throw ValidationException::withMessages(['voice' => ($language ?? TrainingLanguage::default())->isCanonical()
                ? 'Listening to lessons is not available.'
                : 'Listening in '.$language?->label().' is not available.']);
        }

        if ($key === null || $key === '') {
            return $voices[0];
        }

        foreach ($voices as $voice) {
            if ($voice->key === $key) {
                return $voice;
            }
        }

        throw ValidationException::withMessages(['voice' => 'That voice is not available.']);
    }

    /**
     * What gets read aloud: the title, the summary and the written content in
     * the chosen language (English by default). English reads the structured
     * sections when the lesson has them and the older body otherwise; another
     * language is read only from its own translation, and has no text without
     * one (or, in a published version, without an approved one).
     */
    public function lessonText(TrainingLesson $lesson, ?TrainingLanguage $language = null): string
    {
        $language ??= TrainingLanguage::default();
        $content = $language->isCanonical() ? $lesson->contentIn($language) : $this->contents->visibleTranslation($lesson, $language);

        if (! $language->isCanonical() && $content === null) {
            return '';
        }

        $title = trim($lesson->title);

        if ($title !== '' && preg_match('/[.!?:]$/u', $title) !== 1) {
            $title .= '.';
        }

        $body = $content !== null
            ? TrainingLessonStructure::speakable(TrainingLessonStructure::normalize($content->sections))
            : $this->speakableBody((string) $lesson->body);

        $parts = array_filter([
            $title,
            $language->isCanonical() ? trim((string) $lesson->description) : '',
            trim($body),
        ], fn (string $part) => $part !== '');

        $text = implode("\n\n", $parts);
        $text = (string) preg_replace("/\r\n?/", "\n", $text);
        $max = max(1, (int) config('recruiter-training.speech.max_characters', 20000));

        return mb_strlen($text) > $max ? mb_substr($text, 0, $max) : $text;
    }

    /**
     * Tables in the body ("| Alabama | AL |" lines) are read row by row as
     * "Alabama, AL." instead of reading out the pipes. A header row followed
     * by a "| --- |" line is skipped, and a table's rows form one paragraph.
     */
    protected function speakableBody(string $body): string
    {
        $lines = explode("\n", (string) preg_replace("/\r\n?/", "\n", $body));
        $cells = fn (string $line) => array_map('trim', explode('|', trim(trim($line), '|')));
        $isTable = fn (?string $line) => $line !== null && str_starts_with(trim($line), '|');
        $isSeparator = fn (?string $line) => $isTable($line) && array_filter($cells((string) $line), fn (string $cell) => preg_match('/^:?-{3,}:?$/', $cell) !== 1) === [];

        $out = [];
        $rows = [];

        foreach ($lines as $index => $line) {
            if (! $isTable($line)) {
                if ($rows !== []) {
                    $out[] = implode(' ', $rows);
                    $rows = [];
                }

                $out[] = $line;

                continue;
            }

            if ($isSeparator($line) || $isSeparator($lines[$index + 1] ?? null)) {
                continue;
            }

            $spoken = implode(', ', array_filter($cells($line), fn (string $cell) => $cell !== ''));

            if ($spoken !== '') {
                $rows[] = preg_match('/[.!?]$/u', $spoken) === 1 ? $spoken : $spoken.'.';
            }
        }

        if ($rows !== []) {
            $out[] = implode(' ', $rows);
        }

        return implode("\n", $out);
    }

    /**
     * The text split into short spoken pieces, sentence by sentence, never
     * across paragraphs.
     *
     * @return list<string>
     */
    public function segments(string $text): array
    {
        $segments = [];

        foreach (preg_split('/\n+/u', $text) ?: [] as $paragraph) {
            $paragraph = trim((string) preg_replace('/\s+/u', ' ', $paragraph));

            if ($paragraph === '') {
                continue;
            }

            $buffer = '';

            foreach (preg_split('/(?<=[.!?])\s+/u', $paragraph) ?: [] as $sentence) {
                foreach ($this->wrap($sentence) as $piece) {
                    if ($buffer === '') {
                        $buffer = $piece;
                    } elseif (mb_strlen($buffer) + 1 + mb_strlen($piece) <= self::SEGMENT_CHARACTERS) {
                        $buffer .= ' '.$piece;
                    } else {
                        $segments[] = $buffer;
                        $buffer = $piece;
                    }
                }
            }

            if ($buffer !== '') {
                $segments[] = $buffer;
            }
        }

        return $segments;
    }

    public function estimateSeconds(string $text): int
    {
        $words = count(preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY) ?: []);

        if ($words === 0) {
            return 0;
        }

        $perMinute = max(1, (int) config('recruiter-training.speech.words_per_minute', 150));

        return max(1, (int) ceil($words / $perMinute * 60));
    }

    /**
     * Everything the player needs for one lesson in one voice. For device
     * delivery that is the text itself; for audio delivery the player then
     * fetches the file.
     *
     * @return array{delivery: string, provider: string, voice: array{key: string, label: string, locale: string, flag: string}, has_text: bool, segments: list<string>, estimated_seconds: int}
     */
    public function speechFor(TrainingLesson $lesson, VoiceProfile $voice, ?TrainingLanguage $language = null): array
    {
        $text = $this->lessonText($lesson, $language);
        $delivery = $this->provider->delivery();

        return [
            'delivery' => $delivery->value,
            'provider' => $this->provider->name(),
            'voice' => $voice->toArray(),
            'has_text' => $text !== '',
            'segments' => $delivery === TrainingSpeechDelivery::Device ? $this->segments($text) : [],
            'estimated_seconds' => $this->estimateSeconds($text),
        ];
    }

    /**
     * Audio for a lesson from a server-side provider. Kept on the private
     * training disk and reused until the text, voice or provider changes, so a
     * paid provider is called once per lesson and voice.
     *
     * @throws TrainingSpeechException
     */
    public function generateLessonAudio(TrainingLesson $lesson, VoiceProfile $voice, ?TrainingLanguage $language = null): TrainingAudio
    {
        if ($this->provider->delivery() !== TrainingSpeechDelivery::Audio) {
            throw new TrainingSpeechException('This lesson is not delivered as an audio file.');
        }

        $text = $this->lessonText($lesson, $language);

        if ($text === '') {
            throw new TrainingSpeechException('This lesson has no text to read aloud.');
        }

        $disk = Storage::disk((string) config('recruiter-training.media.disk', 'local'));
        $directory = "recruiter-training/audio/{$lesson->id}";
        $prefix = $voice->key.'-'.sha1(implode('|', [$this->provider->name(), $voice->locale, $text]));

        foreach ($disk->files($directory) as $path) {
            if (str_starts_with(basename($path), $prefix.'.')) {
                return new TrainingAudio(
                    (string) $disk->get($path),
                    $disk->mimeType($path) ?: 'audio/mpeg',
                    pathinfo($path, PATHINFO_EXTENSION),
                );
            }
        }

        try {
            $audio = $this->provider->synthesize($text, $voice);
        } catch (TrainingSpeechException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            report($exception);
            Log::warning('Recruiter training speech provider failed.', [
                'provider' => $this->provider->name(),
                'lesson_id' => $lesson->id,
                'voice' => $voice->key,
            ]);

            throw new TrainingSpeechException('The audio service is not available right now. Please try again later.');
        }

        if ($audio->contents === '') {
            throw new TrainingSpeechException('The audio service returned no audio. Please try again later.');
        }

        $extension = preg_replace('/[^a-z0-9]/', '', strtolower($audio->extension)) ?: 'mp3';

        foreach ($disk->files($directory) as $stale) {
            if (str_starts_with(basename($stale), $voice->key.'-')) {
                $disk->delete($stale);
            }
        }

        $disk->put("{$directory}/{$prefix}.{$extension}", $audio->contents);

        return $audio;
    }

    /**
     * @return list<string>
     */
    protected function wrap(string $sentence): array
    {
        $sentence = trim($sentence);

        if (mb_strlen($sentence) <= self::SEGMENT_CHARACTERS) {
            return $sentence === '' ? [] : [$sentence];
        }

        $pieces = [];
        $buffer = '';

        foreach (preg_split('/\s+/u', $sentence, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $word) {
            while (mb_strlen($word) > self::SEGMENT_CHARACTERS) {
                if ($buffer !== '') {
                    $pieces[] = $buffer;
                    $buffer = '';
                }

                $pieces[] = mb_substr($word, 0, self::SEGMENT_CHARACTERS);
                $word = mb_substr($word, self::SEGMENT_CHARACTERS);
            }

            if ($buffer === '') {
                $buffer = $word;
            } elseif (mb_strlen($buffer) + 1 + mb_strlen($word) <= self::SEGMENT_CHARACTERS) {
                $buffer .= ' '.$word;
            } else {
                $pieces[] = $buffer;
                $buffer = $word;
            }
        }

        if ($buffer !== '') {
            $pieces[] = $buffer;
        }

        return $pieces;
    }
}
