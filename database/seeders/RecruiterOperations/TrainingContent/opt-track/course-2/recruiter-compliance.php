<?php

/*
 * Combined lesson "Recruiter Compliance". One topic per merged lesson:
 * Recruiter Compliance Boundaries, What Recruiters Should Verify, What Recruiters Should Not Promise.
 */

return [
    'title' => 'Recruiter Compliance',
    'from' => 'Recruiter Compliance Boundaries',
    'compliance' => true,
    'review' => 'Immigration content: confirm against current official sources (USCIS, SEVP, DHS Study in the States) and company policy before publishing.',
    'en' => [
        ['kind' => 'objective', 'heading' => 'Learning Objective', 'body' => <<<'TEXT'
            Know the compliance boundaries of the recruiter role: what you may verify, what you cannot determine, and what you must never promise.
            TEXT],
        ['kind' => 'flow', 'heading' => 'Lesson Map', 'body' => <<<'TEXT'
            1. Recruiter Compliance Boundaries [[Recruiter Compliance Boundaries]]
            2. What Recruiters Should Verify [[What Recruiters Should Verify]]
            3. What Recruiters Should Not Promise [[What Recruiters Should Not Promise]]
            TEXT],
        ['kind' => 'topic', 'heading' => 'Recruiter Compliance Boundaries', 'body' => <<<'TEXT'
            Your job is accuracy and process discipline. When a question touches eligibility, law or documents, stop, record the facts and escalate.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            Recruiters collect accurate information, share approved facts, and hand off to the right people. Recruiters do not make legal determinations, give immigration advice, or promise outcomes.
            TEXT],
        ['kind' => 'content', 'heading' => 'Recruiters may', 'body' => <<<'TEXT'
            - Ask the company-approved work authorization questions.
            - Record status, dates and degree information accurately.
            - Share the company's approved statements about sponsorship, STEM OPT support and E-Verify.
            - Direct candidates to their DSO, HR or an immigration attorney.
            - Escalate anything unclear.
            TEXT],
        ['kind' => 'content', 'heading' => 'Recruiters must not', 'body' => <<<'TEXT'
            - Decide whether someone is eligible for OPT, STEM OPT, H-1B or any other benefit.
            - Tell a candidate what to file, when to file, or how to answer government forms.
            - Guarantee H-1B selection, approval, STEM OPT support or green card sponsorship.
            - Suggest that someone can work before their authorization starts or after it ends.
            - Ask for documents or personal numbers outside the approved process.
            - Treat candidates differently based on citizenship, national origin, name or accent.
            - Create, alter or suggest altering any document.
            TEXT],
        ['kind' => 'content', 'heading' => 'From the company training material: the work authorization checkpoint', 'body' => <<<'TEXT'
            The Job Description Analysis training gives four rules for every requirement.
            Do not assume. Visa eligibility can vary by client, role, contract type and conversion terms.
            Capture the requirement. Record any stated restriction, such as No H-1B, No CPT or OPT, or citizen-only language.
            Confirm before submission. When the wording is unclear, verify with the Account Manager or the Business Development Manager, known as the BDM, before presenting a candidate.
            Document accurately. Never alter or misrepresent a candidate's work authorization.
            TEXT],
        ['kind' => 'content', 'heading' => 'When to verify', 'body' => <<<'TEXT'
            - When dates on a document do not match what the candidate said.
            - When a name differs between the resume and a document.
            - When a candidate's status has changed since your last conversation.
            - When an EAD end date is close to a proposed start date.
            TEXT],
        ['kind' => 'content', 'heading' => 'When to escalate to HR or compliance', 'body' => <<<'TEXT'
            - Any question about eligibility or job relevance for OPT.
            - Any STEM OPT, I-983, E-Verify or H-1B question.
            - Any document that seems unusual, altered or inconsistent.
            - Any request from a candidate or client that feels wrong or pressures you to skip steps.
            - Any mention of a SEVIS problem, status termination or legal issue.
            TEXT],
        ['kind' => 'content', 'heading' => 'How to escalate', 'body' => <<<'TEXT'
            - Write down the facts, not your opinions.
            - Include the candidate's name, the question, the documents involved and the dates.
            - Tell the candidate politely that the right team will respond, and do not guess in the meantime.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            A candidate's EAD shows a different spelling of his last name than his resume. You do not accuse him of anything. You say: I noticed a small spelling difference. Could you confirm which is correct? You then note the difference and inform HR.
            TEXT],
        ['kind' => 'topic', 'heading' => 'What Recruiters Should Verify', 'body' => <<<'TEXT'
            Check names, dates and status for consistency, and send every mismatch to HR.
            TEXT],
        ['kind' => 'reference', 'heading' => 'Recruiter Checklist', 'body' => <<<'TEXT'
            | Check | What to look for |
            | --- | --- |
            | Name | The same spelling on the resume and on documents |
            | Work authorization type | Matches what the candidate told you |
            | EAD start date | On or before the proposed start date |
            | EAD end date | Not close to the start date without HR knowing |
            | Degree and graduation | Fit the OPT timeline the candidate described |
            | Location | Current location and relocation plans are clear |
            TEXT],
        ['kind' => 'content', 'heading' => 'What Verify Means for a Recruiter', 'body' => <<<'TEXT'
            For a recruiter, verify means checking that information is complete and consistent. It never means deciding that a document is genuine, or that a person is eligible. Anything that does not match goes to HR.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            The proposed start date is June 1, but the candidate's EAD starts on June 10. You do not move the date yourself. You tell your lead and HR, and you let the candidate know that the start date will follow the EAD start date.
            TEXT],
        ['kind' => 'practice', 'heading' => 'Check Your Knowledge', 'body' => <<<'TEXT'
            1. Which two dates do you compare before agreeing a start date?
            2. A last name is spelled differently on the resume and the EAD. What do you do?
            3. Is checking that a document is genuine part of your role?
            TEXT],
        ['kind' => 'topic', 'heading' => 'What Recruiters Should Not Promise', 'body' => <<<'TEXT'
            Never promise what the government or management decides. Give honest answers and refer to HR.
            TEXT],
        ['kind' => 'reference', 'heading' => 'Never Promise, Say Instead', 'body' => <<<'TEXT'
            | Never promise | Say instead |
            | --- | --- |
            | H-1B selection or approval | No company can guarantee H-1B. HR can explain our policy. |
            | Green Card sponsorship | Sponsorship is decided by management. I will connect you with HR. |
            | STEM OPT approval or an I-983 signature | HR and compliance review every STEM OPT case. |
            | A placement, project or start date | Our goal is placement. I will keep you updated at every step. |
            | That a role counts for OPT | Please confirm with your DSO. HR reviews it too. |
            | A rate that is not approved | Let me check with my lead before I confirm anything. |
            TEXT],
        ['kind' => 'content', 'heading' => 'Why It Matters', 'body' => <<<'TEXT'
            Candidates make immigration and career decisions based on what you say. A false promise can harm the candidate and creates legal risk for the company. An honest answer, with a referral to HR, builds more trust than a promise.
            TEXT],
        ['kind' => 'note', 'heading' => 'Money and Offer Letters', 'body' => <<<'TEXT'
            Never tell a candidate that a job, an offer letter, work authorization or sponsorship can be bought. Recruiters never collect money. Every question about fees goes to HR.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            A candidate says: Just tell me you will file my H-1B next year and I will join today. You reply: I understand how important that is. I cannot promise it, because the government decides selection and our management decides sponsorship. HR can explain our policy before you decide.
            TEXT],
        ['kind' => 'takeaway', 'heading' => 'Key Takeaway', 'body' => <<<'TEXT'
            Verify only what your role allows, record facts accurately, and never promise immigration approval, employment, placement or sponsorship.
            TEXT],
    ],
];
