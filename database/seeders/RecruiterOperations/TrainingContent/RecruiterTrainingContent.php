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
}
