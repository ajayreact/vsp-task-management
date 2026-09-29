<?php

namespace App\Modules\RecruiterOperations\Enums;

/**
 * The kinds of work a recruiter task can describe. Fixed on purpose; there is
 * no admin-editable list.
 */
enum RecruiterWorkType: string
{
    case CandidateSourcing = 'candidate_sourcing';
    case CandidateOutreach = 'candidate_outreach';
    case FollowUp = 'follow_up';
    case LinkedinSourcing = 'linkedin_sourcing';
    case UniversityResearch = 'university_research';
    case Training = 'training';
    case InternalMeeting = 'internal_meeting';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::CandidateSourcing => 'Candidate sourcing',
            self::CandidateOutreach => 'Candidate outreach',
            self::FollowUp => 'Follow-up',
            self::LinkedinSourcing => 'LinkedIn sourcing',
            self::UniversityResearch => 'University research',
            self::Training => 'Training',
            self::InternalMeeting => 'Internal meeting',
            self::Other => 'Other',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $type) => ['value' => $type->value, 'label' => $type->label()],
            self::cases(),
        );
    }
}
