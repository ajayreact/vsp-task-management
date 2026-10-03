<?php

namespace Database\Seeders\RecruiterOperations\TrainingContent;

/**
 * Written content for the 10-level OPT recruiter curriculum, keyed by level,
 * course title and lesson title exactly as RecruiterTrainingCurriculumSeeder
 * creates them. Each level-*.php file in this folder returns
 * ['level' => int, 'course' => string, 'lessons' => [title => body]]; a level
 * may be split across several files.
 *
 * Bodies are plain text (the lesson page shows them as-is and the audio player
 * reads them aloud), so they avoid bullet symbols and arrows: every line is a
 * heading or a complete sentence.
 */
class RecruiterTrainingContent
{
    /**
     * @return list<array{level: int, course: string, lessons: array<string, string>}>
     */
    public static function all(): array
    {
        $levels = [];

        foreach (glob(__DIR__.'/level-*.php') ?: [] as $file) {
            /** @var array{level: int, course: string, lessons: array<string, string>} $part */
            $part = require $file;
            $levels[$part['level']] ??= ['level' => $part['level'], 'course' => $part['course'], 'lessons' => []];
            $levels[$part['level']]['lessons'] += $part['lessons'];
        }

        ksort($levels);

        return array_values($levels);
    }

    /**
     * Redesigned structured lessons, by level, lesson title and language code
     * ('en' is the source, 'te' its Telugu translation). Each structured/*.php
     * file returns ['level' => int, 'lessons' => [title => ['en' => sections,
     * 'te' => sections]]], sections being lists of kind, heading and body.
     *
     * @return array<int, array<string, array<string, list<array{kind: string, heading: string, body: string}>>>>
     */
    public static function redesigned(): array
    {
        $levels = [];

        foreach (glob(__DIR__.'/structured/level-*.php') ?: [] as $file) {
            /** @var array{level: int, lessons: array<string, array<string, list<array{kind: string, heading: string, body: string}>>>} $part */
            $part = require $file;
            $levels[$part['level']] = ($levels[$part['level']] ?? []) + $part['lessons'];
        }

        ksort($levels);

        return $levels;
    }

    /**
     * Fingerprints of the bodies earlier releases shipped, which
     * recruiter:training-content --refresh is allowed to replace.
     *
     * @return array<int, array<string, list<string>>>
     */
    public static function previous(): array
    {
        /** @var array<int, array<string, list<string>>> */
        return require __DIR__.'/previous-content.php';
    }
}
