<?php

namespace App\Modules\TaskManagement\Services;

use App\Modules\Core\Models\User;
use App\Modules\TaskManagement\Enums\ContentCalendarPlatform;
use App\Modules\TaskManagement\Enums\ContentCalendarStatus;
use App\Modules\TaskManagement\Enums\ContentCalendarTopic;
use App\Modules\TaskManagement\Enums\ContentCalendarType;
use App\Modules\TaskManagement\Models\Company;
use App\Modules\TaskManagement\Models\ContentCalendarItem;
use App\Modules\TaskManagement\Support\ContentCalendarPlatformDefaults;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContentCalendarImporter
{
    /**
     * @return list<string>
     */
    public function headers(): array
    {
        return [
            'Post #',
            'Date',
            'Format',
            'Topic',
            'Platforms',
            'Description',
            'Caption',
            'Hashtags',
            'Notes',
        ];
    }

    public function downloadTemplate(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Content Calendar');

        foreach ($this->headers() as $index => $header) {
            $sheet->setCellValue([$index + 1, 1], $header);
        }

        $sheet->fromArray([
            [
                1,
                now()->startOfMonth()->toDateString(),
                'Poster',
                'Educational',
                'Facebook, Instagram, LinkedIn',
                'Industry Awareness',
                'Sample caption',
                '#industry #tips',
                'Internal note',
            ],
            [
                2,
                now()->startOfMonth()->addDays(2)->toDateString(),
                'Reel',
                'Promotional',
                'Facebook, Instagram, YouTube',
                'Service promo reel',
                '',
                '#promo',
                '',
            ],
        ], null, 'A2');

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer): void {
            $writer->save('php://output');
        }, 'content-calendar-template.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @return array{rows: list<array<string, mixed>>, summary: array{total: int, valid: int, invalid: int, duplicates: int, importable: int}}
     */
    public function preview(Company $company, UploadedFile $file): array
    {
        $parsed = $this->parseFile($file);
        $validated = [];
        $valid = 0;
        $invalid = 0;
        $duplicates = 0;

        foreach ($parsed as $index => $row) {
            $result = $this->validateRow($company, $row, $index + 2);
            $validated[] = $result;

            if (! $result['valid']) {
                $invalid++;
            } elseif ($result['is_duplicate']) {
                $duplicates++;
                $valid++;
            } else {
                $valid++;
            }
        }

        return [
            'rows' => $validated,
            'summary' => [
                'total' => count($validated),
                'valid' => $valid,
                'invalid' => $invalid,
                'duplicates' => $duplicates,
                'importable' => max(0, $valid - $duplicates),
            ],
        ];
    }

    /**
     * @return array{imported: int, skipped_duplicates: int, skipped_invalid: int}
     */
    public function import(Company $company, UploadedFile $file, User $actor, ContentCalendarStatusWorkflow $workflow): array
    {
        $preview = $this->preview($company, $file);
        $imported = 0;
        $skippedDuplicates = 0;
        $skippedInvalid = 0;

        DB::transaction(function () use ($preview, $company, $actor, $workflow, &$imported, &$skippedDuplicates, &$skippedInvalid): void {
            foreach ($preview['rows'] as $row) {
                if (! $row['valid']) {
                    $skippedInvalid++;

                    continue;
                }

                if ($row['is_duplicate']) {
                    $skippedDuplicates++;

                    continue;
                }

                $item = ContentCalendarItem::query()->create([
                    'tm_company_id' => $company->id,
                    'scheduled_date' => $row['scheduled_date'],
                    'post_number' => $row['post_number'],
                    'topic' => $row['topic'],
                    'content_type' => $row['content_type'],
                    'description' => $row['description'],
                    'caption' => $row['caption'],
                    'hashtags' => $row['hashtags'],
                    'internal_notes' => $row['internal_notes'],
                    'status' => ContentCalendarStatus::Draft,
                    'created_by_user_id' => $actor->id,
                    'updated_by_user_id' => $actor->id,
                ]);

                $item->syncPlatforms($row['platforms']);
                $workflow->recordInitial($item, $actor, 'Imported from Excel');
                $imported++;
            }
        });

        return [
            'imported' => $imported,
            'skipped_duplicates' => $skippedDuplicates,
            'skipped_invalid' => $skippedInvalid,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function parseFile(UploadedFile $file): array
    {
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = [];

        foreach ($sheet->toArray(null, true, true, false) as $index => $raw) {
            if ($index === 0) {
                continue;
            }

            if ($this->rowIsEmpty($raw)) {
                continue;
            }

            $rows[] = [
                'post_number' => trim((string) ($raw[0] ?? '')),
                'date' => trim((string) ($raw[1] ?? '')),
                'format' => trim((string) ($raw[2] ?? '')),
                'topic' => trim((string) ($raw[3] ?? '')),
                'platforms' => trim((string) ($raw[4] ?? '')),
                'description' => trim((string) ($raw[5] ?? '')),
                'caption' => trim((string) ($raw[6] ?? '')),
                'hashtags' => trim((string) ($raw[7] ?? '')),
                'notes' => trim((string) ($raw[8] ?? '')),
            ];
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    protected function validateRow(Company $company, array $row, int $excelRow): array
    {
        $errors = [];

        $date = $this->parseDate((string) $row['date']);
        if ($date === null) {
            $errors[] = 'Invalid date';
        }

        $postNumber = $row['post_number'] === '' ? null : (int) $row['post_number'];
        if ($row['post_number'] !== '' && ($postNumber < 1 || $postNumber > 999)) {
            $errors[] = 'Post # must be between 1 and 999';
        }

        $topic = $this->resolveEnum(ContentCalendarTopic::class, (string) $row['topic']);
        if ($topic === null) {
            $errors[] = 'Unknown topic';
        }

        $format = $this->resolveEnum(ContentCalendarType::class, (string) $row['format']);
        if ($format === null) {
            $errors[] = 'Unknown format';
        }

        $platformParse = $this->parsePlatforms((string) $row['platforms'], $format);
        if ($platformParse['errors'] !== []) {
            $errors = [...$errors, ...$platformParse['errors']];
        }
        if ($platformParse['platforms'] === [] && $platformParse['errors'] === []) {
            $errors[] = 'At least one platform is required';
        }

        $isDuplicate = false;
        if ($errors === [] && $date !== null && $postNumber !== null) {
            $isDuplicate = ContentCalendarItem::query()
                ->where('tm_company_id', $company->id)
                ->whereDate('scheduled_date', $date)
                ->where('post_number', $postNumber)
                ->exists();
        }

        return [
            'excel_row' => $excelRow,
            'valid' => $errors === [],
            'errors' => $errors,
            'is_duplicate' => $isDuplicate,
            'scheduled_date' => $date,
            'post_number' => $postNumber,
            'topic' => $topic?->value,
            'topic_label' => $topic?->label(),
            'content_type' => $format?->value,
            'content_type_label' => $format?->label(),
            'platforms' => $platformParse['platforms'],
            'platform_labels' => $platformParse['labels'],
            'description' => $row['description'] !== '' ? $row['description'] : null,
            'caption' => $row['caption'] !== '' ? $row['caption'] : null,
            'hashtags' => $row['hashtags'] !== '' ? $row['hashtags'] : null,
            'internal_notes' => $row['notes'] !== '' ? $row['notes'] : null,
        ];
    }

    /**
     * @return array{platforms: list<string>, labels: list<string>, errors: list<string>}
     */
    protected function parsePlatforms(string $input, ?ContentCalendarType $format): array
    {
        $errors = [];
        $platforms = [];
        $labels = [];

        if (trim($input) === '') {
            if ($format !== null) {
                $defaults = ContentCalendarPlatformDefaults::for($format);
                foreach ($defaults as $platform) {
                    $platforms[] = $platform->value;
                    $labels[] = $platform->label();
                }
            }

            return compact('platforms', 'labels', 'errors');
        }

        $parts = preg_split('/[,|;]+/', $input) ?: [];

        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }

            $resolved = $this->resolveEnum(ContentCalendarPlatform::class, $part);
            if ($resolved === null) {
                $errors[] = "Invalid platform: {$part}";

                continue;
            }

            if (! in_array($resolved->value, $platforms, true)) {
                $platforms[] = $resolved->value;
                $labels[] = $resolved->label();
            }
        }

        return compact('platforms', 'labels', 'errors');
    }

    /**
     * @param  class-string  $enumClass
     */
    protected function resolveEnum(string $enumClass, string $input): mixed
    {
        if ($input === '') {
            return null;
        }

        $normalized = Str::of($input)->lower()->replace(['/', ' '], ['_', '_'])->snake()->value();

        foreach ($enumClass::cases() as $case) {
            if ($case->value === $normalized || Str::lower($case->label()) === Str::lower($input)) {
                return $case;
            }
        }

        $aliases = [
            ContentCalendarTopic::class => [
                'holiday' => ContentCalendarTopic::FestivalHoliday,
                'festival' => ContentCalendarTopic::FestivalHoliday,
                'festival_holiday' => ContentCalendarTopic::FestivalHoliday,
                'product' => ContentCalendarTopic::ProductService,
                'service' => ContentCalendarTopic::ProductService,
                'type' => null,
            ],
            ContentCalendarPlatform::class => [
                'twitter' => ContentCalendarPlatform::X,
                'x_twitter' => ContentCalendarPlatform::X,
                'x' => ContentCalendarPlatform::X,
                'fb' => ContentCalendarPlatform::Facebook,
                'ig' => ContentCalendarPlatform::Instagram,
                'li' => ContentCalendarPlatform::LinkedIn,
                'yt' => ContentCalendarPlatform::YouTube,
            ],
            ContentCalendarType::class => [
                'type' => null,
            ],
        ];

        return $aliases[$enumClass][$normalized] ?? null;
    }

    protected function parseDate(string $value): ?string
    {
        if ($value === '') {
            return null;
        }

        try {
            if (is_numeric($value)) {
                return Carbon::instance(Date::excelToDateTimeObject((float) $value))->toDateString();
            }

            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param  list<mixed>  $raw
     */
    protected function rowIsEmpty(array $raw): bool
    {
        foreach ($raw as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }
}
