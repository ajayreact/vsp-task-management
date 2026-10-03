<?php

namespace App\Modules\RecruiterOperations\Services;

use App\Modules\RecruiterOperations\Enums\TrainingSectionKind;

/**
 * The structured lesson format. A lesson is a list of sections, each with a
 * kind, a heading and a body. The body is light plain text, never HTML:
 *
 * - each line is a paragraph;
 * - "- " starts a bullet, "1. " a numbered item;
 * - lines starting with "|" form a table ("| --- |" after the first row
 *   makes it the header);
 * - **text** is bold.
 *
 * This class converts the older single-block written content into sections,
 * turns sections back into plain text for the lesson body and for speech,
 * and fingerprints them so translations can tell when English changed.
 */
final class TrainingLessonStructure
{
    public const MAX_SECTIONS = 30;

    public const MAX_HEADING = 150;

    public const MAX_BODY = 20000;

    /**
     * A single paragraph longer than this reads as a wall of text.
     */
    public const GIANT_PARAGRAPH_WORDS = 120;

    /**
     * Lowercased headings of the older written content, and what they become.
     *
     * @var array<string, array{0: TrainingSectionKind, 1: string|null}>
     */
    private const KNOWN_HEADINGS = [
        'learning objective' => [TrainingSectionKind::Objective, 'Learning Objective'],
        'what you need to know' => [TrainingSectionKind::Content, 'What You Need to Know'],
        'key concepts' => [TrainingSectionKind::Content, 'Key Concepts'],
        'practical example' => [TrainingSectionKind::Example, 'Recruiter Example'],
        'sample response' => [TrainingSectionKind::Example, null],
        'examples' => [TrainingSectionKind::Example, null],
        'the scenario' => [TrainingSectionKind::Example, null],
        'recruiter checklist' => [TrainingSectionKind::Reference, 'Recruiter Checklist'],
        'what to confirm' => [TrainingSectionKind::Reference, null],
        'what to record' => [TrainingSectionKind::Reference, null],
        'what the recruiter should cover' => [TrainingSectionKind::Reference, null],
        'common mistakes' => [TrainingSectionKind::Mistakes, 'Common Mistakes'],
        'what recruiters must not do' => [TrainingSectionKind::Mistakes, null],
        'what recruiters should not do' => [TrainingSectionKind::Mistakes, null],
        'what to avoid in a cold call' => [TrainingSectionKind::Mistakes, null],
        'important note' => [TrainingSectionKind::Note, 'Important Note'],
        'source note' => [TrainingSectionKind::Note, 'Source Note'],
        'reflection questions' => [TrainingSectionKind::Practice, 'Reflection Questions'],
        'reflection questions for the recruiter' => [TrainingSectionKind::Practice, null],
        'reflection questions for the candidate' => [TrainingSectionKind::Practice, null],
        'points to think about' => [TrainingSectionKind::Practice, null],
        'key takeaway' => [TrainingSectionKind::Takeaway, 'Key Takeaway'],
    ];

    /**
     * @return list<array{kind: string, heading: string, body: string}>
     */
    public static function fromPlainText(?string $body): array
    {
        $lines = explode("\n", self::normalizeNewlines((string) $body));
        $sections = [];
        $current = null;
        $count = count($lines);

        foreach ($lines as $index => $raw) {
            $line = trim($raw);

            if ($line === '') {
                if ($current !== null) {
                    $current['lines'][] = '';
                }

                continue;
            }

            $previousBlank = $index === 0 || trim($lines[$index - 1]) === '';

            if ($previousBlank && self::looksLikeHeading($line) && self::hasTextAfter($lines, $index, $count)) {
                if ($current !== null) {
                    $sections[] = $current;
                }

                [$kind, $heading] = self::KNOWN_HEADINGS[mb_strtolower($line)] ?? [TrainingSectionKind::Content, null];
                $current = ['kind' => $kind, 'heading' => $heading ?? $line, 'lines' => []];

                continue;
            }

            $current ??= ['kind' => TrainingSectionKind::Content, 'heading' => TrainingSectionKind::Content->defaultHeading(), 'lines' => []];
            $current['lines'][] = $line;
        }

        if ($current !== null) {
            $sections[] = $current;
        }

        $converted = [];

        foreach ($sections as $section) {
            $body = self::sectionBody($section['kind'], $section['lines']);

            if ($body !== '') {
                $converted[] = ['kind' => $section['kind']->value, 'heading' => $section['heading'], 'body' => $body];
            }
        }

        usort($converted, fn (array $a, array $b) => ($b['kind'] === TrainingSectionKind::Objective->value) <=> ($a['kind'] === TrainingSectionKind::Objective->value));

        return $converted;
    }

    /**
     * Cleans sections from a form or a content file: known kinds only, trimmed
     * text, a heading for every section, empty sections dropped.
     *
     * @return list<array{kind: string, heading: string, body: string}>
     */
    public static function normalize(mixed $sections): array
    {
        if (! is_array($sections)) {
            return [];
        }

        $clean = [];

        foreach ($sections as $section) {
            if (! is_array($section)) {
                continue;
            }

            $kind = TrainingSectionKind::tryFrom((string) ($section['kind'] ?? '')) ?? TrainingSectionKind::Content;
            $heading = trim((string) preg_replace('/\s+/u', ' ', (string) ($section['heading'] ?? '')));
            $body = self::normalizeBody((string) ($section['body'] ?? ''));

            if ($body === '') {
                continue;
            }

            $clean[] = [
                'kind' => $kind->value,
                'heading' => mb_substr($heading !== '' ? $heading : $kind->defaultHeading(), 0, self::MAX_HEADING),
                'body' => $body,
            ];
        }

        return array_slice($clean, 0, self::MAX_SECTIONS);
    }

    /**
     * Insensitive to surrounding whitespace and line endings.
     *
     * @param  list<array{kind: string, heading: string, body: string}>  $sections
     */
    public static function fingerprint(array $sections): string
    {
        return hash('sha256', (string) json_encode(self::normalize($sections), JSON_UNESCAPED_UNICODE));
    }

    /**
     * The lesson body kept alongside the English sections: headings and
     * sentences without list or bold markers. Tables stay as "|" rows.
     *
     * @param  list<array{kind: string, heading: string, body: string}>  $sections
     */
    public static function toPlainText(array $sections): string
    {
        $parts = [];

        foreach ($sections as $section) {
            $lines = [$section['heading']];

            foreach (self::blocks($section['body']) as $block) {
                array_push($lines, ...match ($block['type']) {
                    'paragraph' => [self::stripInline($block['text'])],
                    'bullets', 'numbered' => array_map(fn (string $item) => self::stripInline($item), $block['items']),
                    'table' => self::tableLines($block),
                });
            }

            $parts[] = implode("\n", $lines);
        }

        return implode("\n\n", $parts);
    }

    /**
     * What Listen to Lesson reads: each heading, then its text sentence by
     * sentence. Table rows are read as "Alabama, AL." and a header row is
     * skipped; no markers are read aloud.
     *
     * @param  list<array{kind: string, heading: string, body: string}>  $sections
     */
    public static function speakable(array $sections): string
    {
        $paragraphs = [];

        foreach ($sections as $section) {
            $paragraphs[] = self::sentence($section['heading']);

            foreach (self::blocks($section['body']) as $block) {
                if ($block['type'] === 'paragraph') {
                    $paragraphs[] = self::sentence(self::stripInline($block['text']));
                } elseif ($block['type'] === 'table') {
                    $rows = array_map(
                        fn (array $row) => self::sentence(implode(', ', array_filter(array_map(fn (string $cell) => self::stripInline($cell), $row), fn (string $cell) => $cell !== ''))),
                        $block['rows'],
                    );
                    $paragraphs[] = implode(' ', array_filter($rows, fn (string $row) => $row !== ''));
                } else {
                    foreach ($block['items'] as $item) {
                        $paragraphs[] = self::sentence(self::stripInline($item));
                    }
                }
            }
        }

        return implode("\n", array_filter($paragraphs, fn (string $paragraph) => $paragraph !== ''));
    }

    /**
     * @return list<array{type: 'paragraph', text: string}|array{type: 'bullets'|'numbered', items: list<string>}|array{type: 'table', header: list<string>|null, rows: list<list<string>>}>
     */
    public static function blocks(string $body): array
    {
        $blocks = [];
        $table = [];
        $list = null;

        foreach (explode("\n", self::normalizeNewlines($body)) as $raw) {
            $line = trim($raw);

            if ($line === '') {
                self::flushTable($table, $blocks);
                self::flushList($list, $blocks);

                continue;
            }

            if (str_starts_with($line, '|')) {
                self::flushList($list, $blocks);
                $table[] = array_map('trim', explode('|', trim($line, '|')));

                continue;
            }

            self::flushTable($table, $blocks);

            $type = match (true) {
                preg_match('/^[-•]\s+(.+)$/u', $line, $match) === 1 => 'bullets',
                preg_match('/^\d{1,2}[.)]\s+(.+)$/u', $line, $match) === 1 => 'numbered',
                default => null,
            };

            if ($type !== null) {
                if ($list === null || $list['type'] !== $type) {
                    self::flushList($list, $blocks);
                    $list = ['type' => $type, 'items' => []];
                }

                $list['items'][] = trim($match[1]);

                continue;
            }

            self::flushList($list, $blocks);
            $blocks[] = ['type' => 'paragraph', 'text' => $line];
        }

        self::flushTable($table, $blocks);
        self::flushList($list, $blocks);

        return $blocks;
    }

    /**
     * @param  list<list<string>>  $table
     * @param  list<array<string, mixed>>  $blocks
     * @param-out list<list<string>>  $table
     */
    private static function flushTable(array &$table, array &$blocks): void
    {
        if ($table === []) {
            return;
        }

        $hasHeader = count($table) > 1 && self::isSeparatorRow($table[1]);
        $rows = array_values(array_filter($hasHeader ? array_slice($table, 2) : $table, fn (array $row) => ! self::isSeparatorRow($row)));
        $blocks[] = ['type' => 'table', 'header' => $hasHeader ? $table[0] : null, 'rows' => $rows];
        $table = [];
    }

    /**
     * @param  array{type: 'bullets'|'numbered', items: list<string>}|null  $list
     * @param  list<array<string, mixed>>  $blocks
     * @param-out null  $list
     */
    private static function flushList(?array &$list, array &$blocks): void
    {
        if ($list !== null) {
            $blocks[] = $list;
            $list = null;
        }
    }

    public static function stripInline(string $text): string
    {
        return trim((string) preg_replace('/\*\*(.+?)\*\*/u', '$1', $text));
    }

    /**
     * @param  list<array{kind: string, heading: string, body: string}>  $sections
     */
    public static function wordCount(array $sections): int
    {
        return count(preg_split('/\s+/u', trim(self::speakable($sections)), -1, PREG_SPLIT_NO_EMPTY) ?: []);
    }

    /**
     * Word count of the longest single paragraph.
     *
     * @param  list<array{kind: string, heading: string, body: string}>  $sections
     */
    public static function longestParagraphWords(array $sections): int
    {
        $longest = 0;

        foreach ($sections as $section) {
            foreach (self::blocks($section['body']) as $block) {
                if ($block['type'] === 'paragraph') {
                    $longest = max($longest, count(preg_split('/\s+/u', $block['text'], -1, PREG_SPLIT_NO_EMPTY) ?: []));
                }
            }
        }

        return $longest;
    }

    /**
     * @param  list<array{kind: string, heading: string, body: string}>  $sections
     */
    public static function has(array $sections, TrainingSectionKind $kind): bool
    {
        foreach ($sections as $section) {
            if ($section['kind'] === $kind->value) {
                return true;
            }
        }

        return false;
    }

    /**
     * Body lines of a converted section. Checklists, mistakes and questions
     * become lists; a content section made only of short sentences becomes a
     * bullet list; "Recruiter:" and "Candidate:" lines get a bold speaker.
     *
     * @param  list<string>  $lines
     */
    private static function sectionBody(TrainingSectionKind $kind, array $lines): string
    {
        $text = array_values(array_filter($lines, fn (string $line) => $line !== '' && ! str_starts_with($line, '|')));
        $hasTable = count($text) !== count(array_filter($lines, fn (string $line) => $line !== ''));
        $short = $text !== [] && array_filter($text, fn (string $line) => self::words($line) > 25) === [];

        $marker = match (true) {
            $hasTable => null,
            $kind === TrainingSectionKind::Practice => 'numbered',
            in_array($kind, [TrainingSectionKind::Reference, TrainingSectionKind::Mistakes], true) => 'bullets',
            $kind === TrainingSectionKind::Content && count($text) >= 3 && $short => 'bullets',
            default => null,
        };

        $out = [];
        $number = 1;

        foreach ($lines as $line) {
            if ($line === '' || str_starts_with($line, '|')) {
                $out[] = $line;

                continue;
            }

            $line = (string) preg_replace('/^(Recruiter|Candidate|Vendor|Client|HR):\s+/u', '**$1:** ', $line);

            $out[] = match ($marker) {
                'bullets' => '- '.$line,
                'numbered' => ($number++).'. '.$line,
                default => $line,
            };
        }

        return self::normalizeBody(implode("\n", $out));
    }

    private static function looksLikeHeading(string $line): bool
    {
        return ! str_starts_with($line, '|')
            && preg_match('/[.!?:;,)"\']$/u', $line) !== 1
            && preg_match('/^[-•]|^\d{1,2}[.)]\s/u', $line) !== 1
            && mb_strlen($line) <= 90
            && self::words($line) <= 14;
    }

    /**
     * @param  list<string>  $lines
     */
    private static function hasTextAfter(array $lines, int $index, int $count): bool
    {
        for ($next = $index + 1; $next < $count; $next++) {
            if (trim($lines[$next]) !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array{header: list<string>|null, rows: list<list<string>>}  $table
     * @return list<string>
     */
    private static function tableLines(array $table): array
    {
        $row = fn (array $cells) => '| '.implode(' | ', array_map(fn (string $cell) => self::stripInline($cell), $cells)).' |';
        $lines = [];

        if ($table['header'] !== null) {
            $lines[] = $row($table['header']);
            $lines[] = '| '.implode(' | ', array_fill(0, count($table['header']), '---')).' |';
        }

        foreach ($table['rows'] as $cells) {
            $lines[] = $row($cells);
        }

        return $lines;
    }

    /**
     * @param  list<string>  $cells
     */
    private static function isSeparatorRow(array $cells): bool
    {
        return $cells !== [] && array_filter($cells, fn (string $cell) => preg_match('/^:?-{3,}:?$/', $cell) !== 1) === [];
    }

    private static function sentence(string $text): string
    {
        $text = trim($text);

        return $text === '' || preg_match('/[.!?:।]$/u', $text) === 1 ? $text : $text.'.';
    }

    private static function words(string $text): int
    {
        return count(preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY) ?: []);
    }

    private static function normalizeBody(string $body): string
    {
        $lines = array_map('rtrim', explode("\n", self::normalizeNewlines($body)));
        $text = (string) preg_replace("/\n{3,}/", "\n\n", implode("\n", $lines));

        return mb_substr(trim($text), 0, self::MAX_BODY);
    }

    private static function normalizeNewlines(string $text): string
    {
        return (string) preg_replace("/\r\n?/", "\n", $text);
    }
}
