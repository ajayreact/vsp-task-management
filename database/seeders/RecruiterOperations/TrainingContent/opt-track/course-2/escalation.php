<?php

/*
 * Combined lesson "When to Escalate". One topic per merged lesson:
 * When to Escalate to HR, Compliance or DSO, [OPT Recruiter Process & Sourcing Strategy] Escalation Rules for Recruiters.
 */

return [
    'title' => 'When to Escalate',
    'from' => 'When to Escalate to HR, Compliance or DSO',
    'compliance' => true,
    'review' => 'Immigration content: confirm against current official sources (USCIS, SEVP, DHS Study in the States) and company policy before publishing. Company-specific process: confirm each step, owner and any wording with management, HR and compliance before publishing.',
    'en' => [
        ['kind' => 'objective', 'heading' => 'Learning Objective', 'body' => <<<'TEXT'
            Recognise the questions and situations a recruiter must not handle alone, and escalate them quickly to the right owner: HR, compliance, the DSO or legal.
            TEXT],
        ['kind' => 'flow', 'heading' => 'Lesson Map', 'body' => <<<'TEXT'
            1. When to Escalate to HR, Compliance or DSO [[When to Escalate to HR, Compliance or DSO]]
            2. Escalation Rules for Recruiters [[Escalation Rules for Recruiters]]
            TEXT],
        ['kind' => 'topic', 'heading' => 'When to Escalate to HR, Compliance or DSO', 'body' => <<<'TEXT'
            When in doubt, escalate. HR and compliance handle company decisions; the DSO handles school and status questions.
            TEXT],
        ['kind' => 'reference', 'heading' => 'Who Handles What', 'body' => <<<'TEXT'
            | Situation | Escalate to |
            | --- | --- |
            | Document mismatch, unusual document, status change | HR |
            | STEM OPT, I-983, E-Verify or H-1B questions | HR and compliance |
            | Requests for money, or pressure to skip steps | Your lead and HR |
            | OPT eligibility, unemployment days, SEVIS or school questions | The candidate's own DSO |
            | RFE, denial or any legal issue | HR; the candidate may also speak to an immigration attorney |
            TEXT],
        ['kind' => 'content', 'heading' => 'About the DSO', 'body' => <<<'TEXT'
            The DSO is the official at the candidate's school. The candidate contacts their own DSO. Recruiters do not contact a DSO on a candidate's behalf unless HR asks them to.
            TEXT],
        ['kind' => 'content', 'heading' => 'How to Escalate', 'body' => <<<'TEXT'
            - Write down the facts, not opinions.
            - Include the candidate's name, the question, the documents and the dates.
            - Tell the candidate who will respond, and do not guess in the meantime.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            A candidate says USCIS sent her an RFE for her STEM OPT application. You do not explain what to do. You say: Thank you for telling me. Please share this with your DSO. I am informing our HR team so they know. You send HR a short note with the facts.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Escalation Rules for Recruiters', 'body' => <<<'TEXT'
            When in doubt, escalate the same day and record what you passed on.
            TEXT],
        ['kind' => 'reference', 'heading' => 'When to Escalate', 'body' => <<<'TEXT'
            | Situation | Escalate to | When |
            | --- | --- | --- |
            | Offer of money, or request for a guarantee | HR and compliance | Same day |
            | Request for a false document or plan | HR and compliance | Same day |
            | EAD expired or about to expire | HR | Immediately |
            | Change of employer, project, hours, pay, supervisor or location | HR | Same day |
            | Termination or resignation | HR | Same day |
            | Status, timing or eligibility question | Candidate's DSO, via the candidate | When asked |
            | Payroll question | Finance / Payroll | Same day |
            TEXT],
        ['kind' => 'note', 'heading' => 'Your Boundary', 'body' => <<<'TEXT'
            Recruiters explain the process at an awareness level only. The candidate's DSO, HR and, where needed, an immigration attorney give the answers.
            TEXT],
        ['kind' => 'takeaway', 'heading' => 'Key Takeaway', 'body' => <<<'TEXT'
            When a question touches immigration, legal or compliance decisions, stop, record it and escalate to the right owner the same day. Never guess.
            TEXT],
    ],
];
