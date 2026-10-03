<?php

namespace App\Modules\RecruiterOperations\Enums;

/**
 * Review state of one language of a lesson. Converted, newly written or
 * edited content is Needs review; a manager approves it before the version is
 * published. A translation whose English source changed afterwards is shown
 * as outdated whatever its stored state.
 */
enum TrainingContentReview: string
{
    case NeedsReview = 'needs_review';
    case InReview = 'in_review';
    case ChangesRequested = 'changes_requested';
    case Approved = 'approved';

    public function label(): string
    {
        return match ($this) {
            self::NeedsReview => 'Needs review',
            self::InReview => 'In review',
            self::ChangesRequested => 'Changes requested',
            self::Approved => 'Approved',
        };
    }

    /**
     * How the state reads for a language. A translation waiting for review
     * needs a native speaker, not just any manager.
     */
    public function labelFor(TrainingLanguage $language): string
    {
        return $this === self::NeedsReview && ! $language->isCanonical() ? 'Pending native review' : $this->label();
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $status) => ['value' => $status->value, 'label' => $status->label()], self::cases());
    }
}
