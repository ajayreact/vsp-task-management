<?php

namespace Database\Seeders\RecruiterOperations;

use App\Modules\RecruiterOperations\Enums\TrainingContentStatus;
use App\Modules\RecruiterOperations\Enums\TrainingLessonContentType;
use App\Modules\RecruiterOperations\Models\TrainingCategory;
use App\Modules\RecruiterOperations\Models\TrainingCourse;
use App\Modules\RecruiterOperations\Models\TrainingCourseVersion;
use App\Modules\RecruiterOperations\Models\TrainingLesson;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The approved 10-level recruiter curriculum as an outline only: level names,
 * one course per level, and lesson titles. Every course is a DRAFT Version 1
 * with empty lesson bodies. No immigration, tax, payroll or legal content is
 * written here; training managers write and review it in the app, then
 * publish.
 *
 * Idempotent: a level or course that already exists is left untouched, so
 * re-running never overwrites edited content.
 *
 *   php artisan db:seed --class="Database\Seeders\RecruiterOperations\RecruiterTrainingCurriculumSeeder"
 */
class RecruiterTrainingCurriculumSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            foreach ($this->curriculum() as $level => $definition) {
                $category = $this->category($level, $definition['name'], $definition['summary']);
                $this->course($category, $definition['course'], $definition['note'], $definition['lessons']);
            }
        });
    }

    protected function category(int $level, string $name, string $summary): TrainingCategory
    {
        $fullName = "Level {$level} - {$name}";
        $slug = Str::slug("level-{$level}-{$name}");

        $category = TrainingCategory::query()->where('slug', $slug)->first();

        if ($category !== null) {
            return $category;
        }

        $category = new TrainingCategory;
        $category->fill([
            'name' => $fullName,
            'description' => $summary,
            'level_number' => $level,
            'sort_order' => $level,
        ]);
        $category->forceFill(['slug' => $slug, 'is_active' => true])->save();

        return $category;
    }

    /**
     * @param  list<string>  $lessons
     */
    protected function course(TrainingCategory $category, string $title, string $note, array $lessons): void
    {
        $slug = Str::slug($title);

        if (TrainingCourse::query()->where('slug', $slug)->exists()) {
            return;
        }

        $course = new TrainingCourse;
        $course->fill(['category_id' => $category->id, 'title' => $title, 'description' => null]);
        $course->forceFill(['slug' => $slug, 'status' => TrainingContentStatus::Draft])->save();

        $version = new TrainingCourseVersion;
        $version->fill(['description' => $note]);
        $version->forceFill([
            'course_id' => $course->id,
            'version_number' => 1,
            'status' => TrainingContentStatus::Draft,
        ])->save();

        $used = [];

        foreach ($lessons as $index => $lessonTitle) {
            $lessonSlug = Str::slug($lessonTitle) ?: 'lesson';
            $unique = $lessonSlug;
            $suffix = 2;

            while (in_array($unique, $used, true)) {
                $unique = $lessonSlug.'-'.$suffix++;
            }

            $used[] = $unique;

            $lesson = new TrainingLesson;
            $lesson->fill([
                'title' => $lessonTitle,
                'content_type' => TrainingLessonContentType::Text,
                'body' => null,
                'is_required' => true,
            ]);
            $lesson->forceFill([
                'course_version_id' => $version->id,
                'slug' => $unique,
                'sort_order' => $index + 1,
            ])->save();
        }
    }

    /**
     * @return array<int, array{name: string, summary: string, course: string, note: string, lessons: list<string>}>
     */
    protected function curriculum(): array
    {
        $outline = 'Draft outline. Lesson content must be written and reviewed by an authorized training manager before publishing.';
        $reviewed = $outline.' Immigration, tax and legal topics must be checked against current official sources; recruiters do not make legal or tax determinations.';

        return [
            1 => [
                'name' => 'U.S. Fundamentals',
                'summary' => 'Geography, time and business calendar basics for working with U.S. clients and candidates.',
                'course' => 'U.S. Fundamentals',
                'note' => $outline,
                'lessons' => [
                    'Introduction to the United States',
                    '50 U.S. States',
                    'State Abbreviations & Codes',
                    'U.S. Regions',
                    'Major U.S. Cities',
                    'U.S. Seasons',
                    'U.S. Date & Business-Day Concepts',
                    'U.S. Holidays & Business Hours',
                    'U.S. Time Zones',
                    'India ↔ U.S. Time Conversion',
                    'Daylight Saving Time',
                ],
            ],
            2 => [
                'name' => 'Immigration & Work Authorization',
                'summary' => 'Awareness of common U.S. visa and work authorization terms, and where a recruiter\'s role ends.',
                'course' => 'Immigration & Work Authorization',
                'note' => $reviewed,
                'lessons' => [
                    'F-1',
                    'CPT',
                    'OPT',
                    'STEM OPT',
                    'EAD',
                    'H-1B',
                    'H-4',
                    'H-4 EAD',
                    'I-20',
                    'I-765',
                    'I-983',
                    'I-94',
                    'I-9',
                    'E-Verify',
                    'SEVIS',
                    'DSO',
                    'Employment & Work Authorization Basics',
                    'Recruiter Compliance Boundaries',
                ],
            ],
            3 => [
                'name' => 'U.S. IT Staffing & Payroll Fundamentals',
                'summary' => 'How U.S. IT staffing works and the payroll terms recruiters hear every day.',
                'course' => 'U.S. IT Staffing & Payroll Fundamentals',
                'note' => $reviewed,
                'lessons' => [
                    'U.S. IT Staffing Basics',
                    'Staffing Agency vs Direct Employer',
                    'Client',
                    'Vendor',
                    'Consultant',
                    'Requirement',
                    'Submission',
                    'Interview',
                    'Placement',
                    'Bench',
                    'Contract',
                    'Contract-to-Hire / CTH',
                    'Full-Time / Permanent',
                    'W2',
                    'C2C',
                    'Rate Structures',
                    'Gross Pay',
                    'Net Pay',
                    'Federal Income Tax',
                    'Withholding',
                    'Social Security',
                    'Medicare',
                    'FICA',
                    'FUTA',
                    'SUTA/SUI',
                    'W-4',
                    'W-2',
                    'EIN',
                    'Pay Period',
                    'U.S. Staffing Terminology',
                ],
            ],
            4 => [
                'name' => 'Job Requirement Analysis',
                'summary' => 'Reading a job description and capturing location, duration, rate, skills, dates, client and responsibilities.',
                'course' => 'Job Requirement Analysis',
                'note' => $outline.' Source material: the Job Description Analysis training reference. Keep its terminology.',
                'lessons' => [
                    'Understanding Requirements',
                    'Reading Job Descriptions',
                    'Job Title',
                    'Job Summary',
                    'Company Description',
                    'Qualifications',
                    'Experience',
                    'Skills',
                    'Responsibilities',
                    'Pay / Benefits',
                    'Location',
                    'Remote / Hybrid / Onsite',
                    'Duration',
                    'Rate',
                    'Start Date',
                    'Client',
                    'Must-Have vs Preferred',
                    'Work Authorization Check',
                    'Requirement → Search Keywords',
                    'Requirement Prioritization',
                ],
            ],
            5 => [
                'name' => 'Sourcing & Resume Screening',
                'summary' => 'Where to find candidates, how to search, and how to compare a resume with a job description.',
                'course' => 'Sourcing & Resume Screening',
                'note' => $outline.' Practical examples may draw on the Job Description Analysis training reference.',
                'lessons' => [
                    'LinkedIn',
                    'Dice',
                    'Job Boards',
                    'Professional Networks',
                    'Vendor Networks',
                    'Professional Groups',
                    'Internal Database',
                    'Direct Communication',
                    'Search Strategy',
                    'Primary Keywords',
                    'Secondary Keywords',
                    'Boolean Search Basics',
                    'Resume Screening',
                    'Required Skills',
                    'Relevant Experience',
                    'Project Experience',
                    'Education',
                    'Certifications',
                    'Domain Experience',
                    'Location',
                    'LinkedIn Review',
                    'Job History',
                    'Resume vs JD Comparison',
                ],
            ],
            6 => [
                'name' => 'OPT Recruiter Process & Sourcing Strategy',
                'summary' => 'The OPT recruiter workflow from requirement to payroll handoff, and where OPT candidates are found.',
                'course' => 'OPT Recruiter Process & Sourcing Strategy',
                'note' => $outline,
                'lessons' => [
                    'OPT Recruiter Workflow',
                    'Requirement Analysis',
                    'Search Strategy',
                    'Candidate Sourcing',
                    'Initial Screening',
                    'Candidate Communication',
                    'Interest Confirmation',
                    'Documentation Handoff',
                    'Offer Process',
                    'Onboarding Handoff',
                    'Payroll Handoff',
                    'Follow-Up',
                    'LinkedIn Sourcing',
                    'University Sourcing',
                    'STEM Programs',
                    'International Student Communities',
                    'Professional Groups',
                    'Referrals',
                    'Recruitment Partnerships',
                ],
            ],
            7 => [
                'name' => 'Calling & Communication',
                'summary' => 'Introductions, cold calls, screening calls, follow-up and objection handling.',
                'course' => 'Calling & Communication',
                'note' => $outline.' Source material: the "Calling script and Practice" reference. Do not present company-specific claims as universal facts.',
                'lessons' => [
                    'Recruiter Introduction',
                    'Professional Introduction',
                    '60-Second Cold Call',
                    'Company Introduction',
                    'Opportunity Explanation',
                    'Initial Screening Call',
                    'Current Location',
                    'Relocation',
                    'IT Experience',
                    'Technical Stack',
                    'Visa Status',
                    'University',
                    'Graduation',
                    'EAD Dates',
                    'Availability',
                    'Current Project',
                    'Rate Discussion',
                    'Candidate Communication',
                    'Follow-Up',
                    'Email Communication',
                    'LinkedIn Communication',
                    'Objection Handling',
                    'Objection: "I already have an employer"',
                    'Objection: "Is your company real?"',
                    'Objection: "Why do you need my EAD/I-20?"',
                    'Objection: "Can you guarantee H-1B?"',
                    'Objection: "Your rate is too low"',
                    'Mock Calling',
                ],
            ],
            8 => [
                'name' => 'University & Candidate Outreach',
                'summary' => 'Researching universities and reaching candidates professionally.',
                'course' => 'University & Candidate Outreach',
                'note' => $outline,
                'lessons' => [
                    'University Research',
                    'STEM Programs',
                    'Career Centers',
                    'International Student Offices',
                    'University Recruitment Policies',
                    'Professional Outreach',
                    'Follow-Up',
                    'Candidate Outreach',
                    'LinkedIn Outreach',
                    'Email Outreach',
                    'Phone Outreach',
                    'Referrals',
                    'Outreach Timing',
                    'Relationship Building',
                ],
            ],
            9 => [
                'name' => 'Documentation, Onboarding & Payroll Handoff',
                'summary' => 'Which documents recruiters collect and hand off, and when to escalate internally.',
                'course' => 'Documentation, Onboarding & Payroll Handoff',
                'note' => $reviewed,
                'lessons' => [
                    'Resume',
                    'EAD',
                    'I-20',
                    'I-983',
                    'I-94',
                    'I-9',
                    'W-4',
                    'Onboarding Awareness',
                    'Offer Process',
                    'Payroll Handoff',
                    'Work-State Information',
                    'Start Date',
                    'Pay Information',
                    'Internal Escalation',
                ],
            ],
            10 => [
                'name' => 'Practical Recruiter Learning',
                'summary' => 'Practice scenarios that bring the earlier levels together.',
                'course' => 'Practical Recruiter Learning',
                'note' => $outline.' Scenario outlines only; there is no assessment in this course.',
                'lessons' => [
                    'Scenario: JD Analysis',
                    'Scenario: Resume Screening',
                    'Scenario: Calling',
                    'Scenario: Objection Handling',
                    'Scenario: Immigration Boundary Scenarios',
                    'Scenario: Recruiter Workflow Simulation',
                ],
            ],
        ];
    }
}
