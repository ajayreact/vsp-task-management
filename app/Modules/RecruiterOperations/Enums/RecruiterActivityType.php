<?php

namespace App\Modules\RecruiterOperations\Enums;

/**
 * What kind of work a recruiter logged. The values are stored, so keep them
 * stable; relabel freely.
 */
enum RecruiterActivityType: string
{
    case CandidateSourcing = 'candidate_sourcing';
    case CandidateOutreach = 'candidate_outreach';
    case FollowUp = 'follow_up';
    case LinkedinSourcing = 'linkedin_sourcing';
    case UniversityResearch = 'university_research';
    case EmailOutreach = 'email_outreach';
    case PhoneCalls = 'phone_calls';
    case InternalMeeting = 'internal_meeting';
    case Training = 'training';
    case Research = 'research';
    case Administrative = 'administrative';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::CandidateSourcing => 'Candidate sourcing',
            self::CandidateOutreach => 'Candidate outreach',
            self::FollowUp => 'Follow-up',
            self::LinkedinSourcing => 'LinkedIn sourcing',
            self::UniversityResearch => 'University research',
            self::EmailOutreach => 'Email outreach',
            self::PhoneCalls => 'Phone calls',
            self::InternalMeeting => 'Internal meeting',
            self::Training => 'Training',
            self::Research => 'Research',
            self::Administrative => 'Administrative',
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
