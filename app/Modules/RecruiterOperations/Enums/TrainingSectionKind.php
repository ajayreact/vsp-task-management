<?php

namespace App\Modules\RecruiterOperations\Enums;

/**
 * What a lesson section is for. The kind decides how the section is styled
 * (an example is a callout, a takeaway is highlighted); the heading is free
 * text, so "Call Flow" or "State Code Table" are ordinary Content sections.
 *
 * Flow and Scenario use the same plain-text body with a fixed shape:
 * - Flow: numbered steps, each "1. **Owner:** Step. Optional detail.",
 *   shown as an interactive flowchart;
 * - Scenario: one line per part, "**Situation:** ...", "**Correct
 *   response:** ...", "**Incorrect response:** ...", "**Why:** ...",
 *   "**Escalation:** ...".
 * - Script: one call stage. "**Say:** ..." lines, "**Consultant may say:**"
 *   followed by "- **They say:** ... **You say:** ..." items, "**Record in
 *   CRM:**" followed by items, and "**Visa / Status**" lines that mark a
 *   step inside the stage. Three or more Script sections make the lesson a
 *   calling playbook, with tabs and a clickable call flow.
 * - Topic: one subject of a combined lesson. Its body is a short key point;
 *   the sections after it, up to the next Topic, belong to it. A lesson with
 *   topics is shown with one tab per topic.
 * - Part: groups the topics after it, up to the next Part, in a long
 *   lesson. Its body is a short introduction. A lesson with parts shows the
 *   parts above the tabs, and the tabs of the current part only.
 *
 * A flow step may end with "[[Heading]]" to link to a section or step of
 * the same lesson.
 */
enum TrainingSectionKind: string
{
    case Objective = 'objective';
    case Content = 'content';
    case Example = 'example';
    case Reference = 'reference';
    case Mistakes = 'mistakes';
    case Practice = 'practice';
    case Note = 'note';
    case Takeaway = 'takeaway';
    case Flow = 'flow';
    case Scenario = 'scenario';
    case Script = 'script';
    case Topic = 'topic';
    case Part = 'part';

    public function label(): string
    {
        return match ($this) {
            self::Objective => 'Learning objective',
            self::Content => 'Content section',
            self::Example => 'Recruiter example',
            self::Reference => 'Quick reference / checklist',
            self::Mistakes => 'Common mistakes',
            self::Practice => 'Practice',
            self::Note => 'Important note',
            self::Takeaway => 'Key takeaway',
            self::Flow => 'Process flowchart',
            self::Scenario => 'Scenario (correct / incorrect response)',
            self::Script => 'Call script stage (say, responses, record in CRM)',
            self::Topic => 'Topic (starts a tab; the sections after it belong to it)',
            self::Part => 'Part (groups the topics after it in a long lesson)',
        };
    }

    public function defaultHeading(): string
    {
        return match ($this) {
            self::Objective => 'Learning Objective',
            self::Content => 'What You Need to Know',
            self::Example => 'Recruiter Example',
            self::Reference => 'Quick Reference',
            self::Mistakes => 'Common Mistakes',
            self::Practice => 'Practice',
            self::Note => 'Important Note',
            self::Takeaway => 'Key Takeaway',
            self::Flow => 'Process Flow',
            self::Scenario => 'Scenario',
            self::Script => 'Call Stage',
            self::Topic => 'Topic',
            self::Part => 'Part',
        };
    }

    /**
     * @return list<array{value: string, label: string, heading: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $kind) => [
            'value' => $kind->value,
            'label' => $kind->label(),
            'heading' => $kind->defaultHeading(),
        ], self::cases());
    }
}
