<?php

/*
 * "Complete Mock OPT Recruiter Call": role-play practice and self-review for
 * the whole call. It does not repeat the scripts; the recruiter practises
 * with the playbook lessons open.
 */

return [
    'title' => 'Complete Mock OPT Recruiter Call',
    'compliance' => true,
    'review' => $immigration.' '.$confirm('the consultant scenarios, which use the Offer Letter Service at $500 and the Payroll Service at $2,260 ($1,934.17 payroll amount and $325.83 service charge)'),
    'en' => $lesson(
        'Practise the complete call from greeting to closing in a role-play, using the playbook lessons, and score yourself against a checklist until every step is natural.',
        [
            ['content', 'How to Run the Role-Play', <<<'TEXT'
            - Work in threes: one recruiter, one consultant, one observer. Swap roles after each call.
            - The recruiter keeps "How to Speak with a Consultant" open and follows it from Greeting to Closing.
            - The consultant plays one of the scenarios below and does not tell the recruiter which one.
            - The observer scores the call with the checklist, then gives two things that went well and one to improve.
            - Each call takes 10 to 15 minutes. Record your score in your training notes.
            TEXT],
            ['reference', 'Consultant Scenarios', <<<'TEXT'
            | Scenario | The consultant | What the recruiter must do |
            | --- | --- | --- |
            | 1. Needs an offer letter | On OPT, not working yet, asks whether the offer letter will get their EAD approved | Explain the Offer Letter Service at $500, and say clearly that work authorization is decided by the DSO and USCIS |
            | 2. Asks about payroll | Working on a project, asks why there is a service charge | Quote $2,260 total, $1,934.17 payroll amount and $325.83 service charge exactly, slowly |
            | 3. Doubts the company | Asks whether the company is genuine and E-Verified | Acknowledge, send verifiable details, and confirm E-Verify with HR instead of guessing |
            | 4. Busy and unsure | Driving, then asks for a call tomorrow, then hesitates about relocating | Reschedule with the time zone, call back, respect a no on relocation |
            | 5. Wants guarantees | Asks for a guaranteed project and an H-1B promise | Refuse both guarantees honestly and offer the HR and sales manager calls |
            TEXT],
            ['practice', 'Call Scoring Checklist', <<<'TEXT'
            1. Asked permission before continuing, and respected the answer.
            2. Asked every screening question, in order, the same way as for every consultant.
            3. Understood the requirement before explaining any service.
            4. Explained Offer Letter and Payroll as the core services, with the exact approved figures.
            5. Used only confirmed company facts.
            6. Made no promise of immigration approval, employment, a project, placement or H-1B.
            7. Answered questions by finding the real concern first.
            8. Covered all five next steps: company mail, resume, referrals, start date and the sales manager call.
            9. Recorded every answer in the CRM during or right after the call.
            10. Closed with a summary, a next step and a thank you.
            TEXT],
            ['practice', 'Self-Review', "- Which step felt least natural, and why?\n- Did I talk more than the consultant?\n- Was any figure or fact I said not in the playbook?\n- What will I do differently on my next real call?"],
        ],
        'Practise the full call with the playbooks open until every step is natural, every figure is exact and no promise slips in.',
    ),
];
