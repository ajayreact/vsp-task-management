<?php

namespace App\Modules\RecruiterOperations\Enums;

/**
 * What an assessment is for. The engine is shared; only training quizzes are
 * offered today. The other cases are reserved so future recruiter and
 * practical assessments need no schema change. They are never offered in the
 * UI or accepted from a form.
 */
enum AssessmentType: string
{
    case TrainingQuiz = 'training_quiz';
    case RecruiterAssessment = 'recruiter_assessment';
    case PracticalAssessment = 'practical_assessment';

    public function label(): string
    {
        return match ($this) {
            self::TrainingQuiz => 'Training quiz',
            self::RecruiterAssessment => 'Recruiter assessment',
            self::PracticalAssessment => 'Practical assessment',
        };
    }

    public function isAvailable(): bool
    {
        return $this === self::TrainingQuiz;
    }

    /**
     * @return list<self>
     */
    public static function available(): array
    {
        return array_values(array_filter(self::cases(), fn (self $type) => $type->isAvailable()));
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $type) => ['value' => $type->value, 'label' => $type->label()], self::available());
    }
}
