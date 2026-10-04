<?php

/*
 * "Practical Immigration Screening": how to ask about status on a call,
 * record it, request documents at the right stage and escalate. Built from
 * the approved screening questions, What Recruiters Should Verify, EAD Dates
 * and When to Escalate; the company's document stage is left for management
 * to confirm.
 */

return [
    'title' => 'Practical Immigration Screening',
    'compliance' => true,
    'review' => 'Immigration content: confirm against current official sources (USCIS, SEVP, DHS Study in the States) and company policy before publishing. Company-specific process, not yet defined: management and HR must confirm at which stage an EAD copy is requested (before submission or only at HR onboarding), the approved secure channel for documents, and the CRM fields used to record status and dates.',
    'en' => [
        ['kind' => 'objective', 'heading' => 'Learning Objective', 'body' => 'Ask every candidate the approved immigration questions in the same way, record the answers exactly, request documents only at the approved stage and through the approved channel, and escalate anything you must not decide.'],
        ['kind' => 'note', 'heading' => 'Rules for Immigration Questions', 'body' => <<<'TEXT'
            - Ask the same approved questions of every candidate, in the same way.
            - Record facts exactly as given. Never interpret, advise or decide eligibility.
            - Never ask about nationality, religion, age, family or other protected characteristics.
            - Documents are collected only at the approved stage, through the approved secure channel, and never by text message, chat or personal email.
            - When in doubt, escalate. HR, compliance and the candidate's DSO give the answers.
            TEXT],
        ['kind' => 'flow', 'heading' => 'Immigration Screening Flow', 'body' => <<<'TEXT'
            1. Ask the Status. The approved visa status question. [[Asking About Status]]
            2. Record the EAD Dates. Start, end and card received. [[EAD Dates]]
            3. Check Consistency. Names and dates against the requirement. [[Checking Consistency]]
            4. Documents? Only at the approved stage and channel. [[Documents at the Right Stage]]
            5. Escalate? Anything you must not decide. [[What to Escalate]]
            TEXT],
        ['kind' => 'script', 'heading' => 'Asking About Status', 'body' => <<<'TEXT'
            **Say:** "I have a few questions about your work authorization, which I ask every candidate."
            **Ask:** "Let me know your visa status."
            **Consultant may say:**
            - **They say:** "I am on OPT." **You say:** "Okay, thank you. What are the start and end dates on your EAD?"
            - **They say:** "My OPT application is pending." **You say:** "Okay, thank you for letting me know. When did you apply?" Record it as pending.
            - **They say:** "I am on STEM OPT." **You say:** "Okay, thank you. When did your STEM OPT start?"
            - **They say:** "Why do you need this?" **You say:** "I only need your status and dates to understand which opportunities fit. HR verifies documents later, through the official process."
            - **They say:** "What should I do about my status?" **You say:** "That is a question for your DSO, or an immigration attorney. I cannot advise on immigration."
            **Record in CRM:**
            - Visa status, exactly as the candidate says it.
            - Pending applications, with the date filed.
            **Never:** Ask about nationality or other protected characteristics, or suggest a status the candidate should apply for.
            TEXT],
        ['kind' => 'script', 'heading' => 'EAD Dates', 'body' => <<<'TEXT'
            **Ask:** "Has your EAD been approved?"
            **Ask:** "What are the start and end dates on your EAD?"
            **Ask:** "Have you received the physical card yet?"
            **Consultant may say:**
            - **They say:** "June 1, 2026 to May 31, 2027." **You say:** "Thank you. June 1, 2026 to May 31, 2027." Repeat the dates back.
            - **They say:** "I don't remember the dates." **You say:** "No problem. Could you check your card and send me the dates by email?"
            - **They say:** "I am planning to apply for STEM OPT." **You say:** "Okay, thank you. Have you already applied, or when do you plan to?"
            **Record in CRM:**
            - EAD start and end dates, with the month name.
            - Card received: yes or no.
            - Remaining unemployment days, if the candidate shares them.
            - Any planned or pending STEM OPT application, with its date.
            **Tip:** A candidate with very few unemployment days left needs careful, urgent handling. Tell HR.
            TEXT],
        ['kind' => 'script', 'heading' => 'Checking Consistency', 'body' => <<<'TEXT'
            **Say:** "Thank you. Let me confirm the details I have noted." Read back the name, status and dates.
            **Check:**
            - The name is spelled the same way on the resume and in your notes.
            - The EAD start date is on or before the proposed start date.
            - The EAD end date covers the project, or HR knows that it does not.
            - The degree and graduation date fit the timeline the candidate described.
            **Consultant may say:**
            - **They say:** "My last name is spelled differently on my EAD." **You say:** "Thank you for telling me. I will note it so HR can review it."
            - **They say:** "Can I start before my EAD start date?" **You say:** "No. Work can only start on or after your EAD start date."
            **Record in CRM:**
            - Any mismatch, flagged for HR.
            **Never:** Decide that a document is genuine, or that a candidate is eligible. Verifying means checking for consistency only.
            TEXT],
        ['kind' => 'script', 'heading' => 'Documents at the Right Stage', 'body' => <<<'TEXT'
            **Say:** "On this call I only need your status and dates. If a document is needed later, it is collected through our secure company process, and HR explains why."
            **Consultant may say:**
            - **They say:** "Do you need my EAD now?" **You say:** "Not on this call. If it is needed at a later stage, you will share it through our secure company link."
            - **They say:** "I do not want to share my documents." **You say:** "I respect that. I will note it and let my lead know."
            - **They say:** "Can I send it on WhatsApp?" **You say:** "Please do not. Documents are only shared through our secure company process."
            **Record in CRM:**
            - Documents requested, the stage, the purpose and the channel used.
            - Any refusal, reported to your lead.
            **Important:** Management to confirm: the stage at which an EAD copy is requested, and the approved secure channel. Until then, collect no documents on screening calls.
            TEXT],
        ['kind' => 'script', 'heading' => 'What to Escalate', 'body' => <<<'TEXT'
            **Escalate when:**
            - A document does not match, looks unusual, or the status has changed. Escalate to HR.
            - The candidate asks about STEM OPT, the I-983, E-Verify or H-1B. Escalate to HR and compliance.
            - Anyone asks for money for an immigration outcome, or pressure to skip steps. Escalate to your lead and HR.
            - The question is about OPT eligibility, unemployment days, SEVIS or school matters. The candidate asks their own DSO.
            - There is an RFE, a denial or any legal issue. Escalate to HR; the candidate may also speak to an immigration attorney.
            **Say:** "That is a good question. I want you to get the correct answer, so I am passing it to our HR team. They will get back to you."
            **Consultant may say:**
            - **They say:** "USCIS sent me an RFE." **You say:** "Thank you for telling me. Please share it with your DSO. I am informing our HR team so they know."
            **Record in CRM:**
            - The question, the facts, the dates, and who it was escalated to.
            **Never:** Guess, or explain what the candidate should file.
            TEXT],
        ['kind' => 'reference', 'heading' => 'Immigration Record Template', 'body' => <<<'TEXT'
            | Field | What to record | Example |
            | --- | --- | --- |
            | Visa status | Exactly as given | OPT |
            | EAD dates | Start and end, with the month name | June 1, 2026 to May 31, 2027 |
            | Card received | Yes or no | Yes |
            | Unemployment days left | If shared | 60 |
            | Pending applications | Type and date filed | STEM OPT, filed March 3, 2027 |
            | Mismatches | What, and flagged to whom | Last name spelling, flagged to HR |
            | Documents | Stage, purpose, channel | None at screening |
            | Escalations | Question, owner, date | STEM OPT question, HR, March 5 |
            TEXT],
        ['kind' => 'takeaway', 'heading' => 'Key Takeaway', 'body' => 'Ask the same approved questions of everyone, record status and dates exactly, collect documents only at the approved stage and channel, and escalate whatever you must not decide.'],
    ],
];
