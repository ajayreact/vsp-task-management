<?php

/*
 * Combined lesson "Employment & Work Authorization". One topic per merged lesson:
 * Employment & Work Authorization Basics, OPT Employment Basics, STEM OPT Employment Basics, Work Authorization Verification, EAD Dates, Employment Documentation.
 */

return [
    'title' => 'Employment & Work Authorization',
    'from' => 'Employment & Work Authorization Basics',
    'compliance' => true,
    'review' => 'Immigration content: confirm against current official sources (USCIS, SEVP, DHS Study in the States) and company policy before publishing. The visa category overview moved to its own lesson. Immigration content: confirm against current official sources (USCIS, SEVP, DHS Study in the States) and company policy before publishing. Immigration content: confirm against current official sources (USCIS, SEVP, DHS Study in the States) and company policy before publishing. Moved from the calling course.',
    'en' => [
        ['kind' => 'objective', 'heading' => 'Learning Objective', 'body' => <<<'TEXT'
            Understand how employment authorization works in practice: the basics, OPT and STEM OPT employment, how work authorization is verified, how to read EAD dates, and the employment documentation involved.
            TEXT],
        ['kind' => 'flow', 'heading' => 'Lesson Map', 'body' => <<<'TEXT'
            1. Employment & Work Authorization Basics [[Employment & Work Authorization Basics]]
            2. OPT Employment Basics [[OPT Employment Basics]]
            3. STEM OPT Employment Basics [[STEM OPT Employment Basics]]
            4. Work Authorization Verification [[Work Authorization Verification]]
            5. EAD Dates [[EAD Dates]]
            6. Employment Documentation [[Employment Documentation]]
            TEXT],
        ['kind' => 'topic', 'heading' => 'Employment & Work Authorization Basics', 'body' => <<<'TEXT'
            Know the category, record the dates exactly, handle personal numbers carefully, and refer status decisions to HR and the DSO.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            Employment authorization means a person is legally allowed to work in the U.S. Citizens and lawful permanent residents, also called Green Card holders, are authorized to work without time limits. Many others are authorized only in certain categories and for certain dates.
            For OPT recruiters, the common categories are OPT, STEM OPT, H-1B, H-4 EAD and, less often, others such as Green Card holders.
            TEXT],
        ['kind' => 'content', 'heading' => 'Work Authorization Dates', 'body' => <<<'TEXT'
            - The start date is the earliest day the person may work.
            - The end date is the last day of the current authorization.
            - Between these dates, the person may work within the rules of the category.
            - Always write dates in the month, day, year format, and use the month name when possible.
            - If the end date is near, note it clearly and inform HR, so that renewal, STEM OPT or another plan can be considered.
            TEXT],
        ['kind' => 'content', 'heading' => 'Social Security Number', 'body' => <<<'TEXT'
            - A Social Security Number, or SSN, is a nine-digit number issued by the Social Security Administration. It is used for payroll, taxes and reporting.
            - OPT candidates who did not have an SSN may apply for one, often around the time their EAD is approved.
            - An SSN alone does not prove work authorization.
            - Some employment processes, such as E-Verify, use the SSN. HR handles situations where an SSN has been applied for but not yet issued.
            - An SSN is highly sensitive. Never ask for it in early screening. Collect it only through the approved onboarding process.
            TEXT],
        ['kind' => 'content', 'heading' => 'Unemployment Awareness', 'body' => <<<'TEXT'
            OPT and STEM OPT include limits on the total days a student may be unemployed. Candidates under pressure from these limits may be anxious to start quickly. Be kind and professional, but never rush a placement in a way that skips steps, and never advise on the limits. Refer them to their DSO.
            TEXT],
        ['kind' => 'content', 'heading' => 'Questions You May Ask', 'body' => <<<'TEXT'
            Follow your company's approved screening script. Commonly approved questions are: Are you currently authorized to work in the U.S.? and Will you now or in the future require sponsorship for employment visa status? Ask everyone the same questions, regardless of name, accent or background.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            A candidate on OPT says her EAD ends in three months and she has a STEM degree. You record the EAD dates, note the STEM degree, and flag the end date in your handoff to HR. You do not tell her what to file or when.
            TEXT],
        ['kind' => 'mistakes', 'heading' => 'Common Mistakes', 'body' => <<<'TEXT'
            - Treating an SSN as proof of work authorization.
            - Asking different questions to different candidates.
            - Rushing a candidate who is close to an unemployment limit.
            TEXT],
        ['kind' => 'topic', 'heading' => 'OPT Employment Basics', 'body' => <<<'TEXT'
            OPT work must fit the EAD dates and relate to the degree. Record the facts and let the DSO and HR decide.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            - Post-completion OPT usually gives up to 12 months of work authorization for each higher degree level.
            - Work may start only on or after the EAD start date, and must stop by the EAD end date.
            - The work must be directly related to the student's major field of study.
            - Work of at least 20 hours a week counts as employment for post-completion OPT.
            - Unemployment during post-completion OPT is limited to a total of 90 days.
            - Students report employment details and changes to their DSO, or through the SEVP Portal, within the deadlines their school gives them.
            TEXT],
        ['kind' => 'reference', 'heading' => 'What You Record on a Call', 'body' => <<<'TEXT'
            | Item | Why |
            | --- | --- |
            | EAD start and end dates | The start date can never be before the EAD start date |
            | Degree and major | HR and the candidate confirm the role relates to the degree |
            | Current employment | Shows whether the candidate is employed or unemployed |
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            A candidate with a master's in data science asks whether a QA role would count for her OPT. You say: Whether a role relates to your degree is something you confirm with your DSO, and our HR team reviews it too. I will share the job description so you can check. You record her question for HR.
            TEXT],
        ['kind' => 'mistakes', 'heading' => 'Common Mistakes', 'body' => <<<'TEXT'
            - Agreeing a start date before the EAD start date.
            - Telling a candidate that a role does or does not relate to their degree.
            - Counting a candidate's unemployment days for them.
            TEXT],
        ['kind' => 'topic', 'heading' => 'STEM OPT Employment Basics', 'body' => <<<'TEXT'
            STEM OPT brings E-Verify, a training plan and reporting duties. Every STEM OPT placement goes through HR and compliance.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            - STEM OPT is a 24-month extension of OPT for students with a qualifying STEM degree.
            - The employer must be enrolled in E-Verify and must have an Employer Identification Number, or EIN.
            - The job must be paid, for at least 20 hours a week, with pay comparable to similar U.S. workers.
            - The student and employer complete a training plan, Form I-983, and the employer must provide that training and supervision.
            - STEM OPT adds 60 days of allowed unemployment, for a total of 150 days across OPT and STEM OPT.
            - The student reports to the DSO every six months, and the student and employer complete evaluations at the end of each year.
            TEXT],
        ['kind' => 'content', 'heading' => 'Why STEM OPT Needs Extra Care in Staffing', 'body' => <<<'TEXT'
            The STEM OPT rules expect the employer who signs the training plan to train and supervise the student directly. Placements at a client site therefore need careful review. Whether a STEM OPT candidate can be placed on a particular project is decided by HR and compliance, never by the recruiter.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            A candidate on STEM OPT asks whether your company can sign his I-983 for a client project. You say: That is decided by our HR and compliance team, who review the training plan for every STEM OPT placement. I will pass your question to them today.
            TEXT],
        ['kind' => 'mistakes', 'heading' => 'Common Mistakes', 'body' => <<<'TEXT'
            - Saying the company is enrolled in E-Verify without HR confirming it.
            - Promising that an I-983 will be signed.
            - Treating STEM OPT placements exactly like regular OPT placements.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Work Authorization Verification', 'body' => <<<'TEXT'
            Recruiters record and check for consistency. HR verifies, on Form I-9 and through E-Verify where it applies.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            - The employer verifies every new employee's identity and work authorization on Form I-9. HR does this, not the recruiter.
            - Form I-9 is completed when the person starts work, within the deadlines set by the rules.
            - Employers enrolled in E-Verify also confirm the I-9 information electronically.
            - The employee chooses which acceptable documents to show. An employer may not demand specific documents.
            - Before hiring, some vendors and clients ask staffing companies to confirm work authorization dates. Recruiters do this only through the approved process.
            TEXT],
        ['kind' => 'reference', 'heading' => 'Who Does What', 'body' => <<<'TEXT'
            | Step | Who |
            | --- | --- |
            | Ask the approved screening questions and record answers | Recruiter |
            | Check dates and names for consistency | Recruiter, then HR |
            | Form I-9 and E-Verify | HR |
            | Decide eligibility | Employer, through HR |
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            A vendor asks you to confirm that your candidate is work-authorized. You share only the work authorization type and dates recorded through the approved process, and you tell the vendor that formal verification is completed by HR at hire.
            TEXT],
        ['kind' => 'mistakes', 'heading' => 'Common Mistakes', 'body' => <<<'TEXT'
            - Telling a vendor a candidate is fully verified before HR has verified anything.
            - Asking a candidate for a specific document, such as only the EAD, for Form I-9.
            - Judging whether a document is genuine yourself.
            TEXT],
        ['kind' => 'topic', 'heading' => 'EAD Dates', 'body' => <<<'TEXT'
            EAD dates define when a candidate can work. Record them exactly, compare them carefully, and involve HR whenever there is doubt.
            TEXT],
        ['kind' => 'note', 'heading' => 'Important Note', 'body' => <<<'TEXT'
            This lesson covers recruiter awareness only, not legal advice. HR verifies employment authorization documents. Follow company policy and confirm with HR or compliance.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            The Employment Authorization Document, called the EAD, shows the period during which a person may work in the United States under certain categories, including OPT and STEM OPT. The start date and end date on the card are very important. A candidate generally cannot start work before the start date, and work authorization planning must consider the end date.
            TEXT],
        ['kind' => 'content', 'heading' => 'How to ask', 'body' => <<<'TEXT'
            - Has your EAD been approved?
            - What are the start and end dates on your EAD?
            - Have you received the physical card yet?
            - Are you planning to apply for a STEM OPT extension, or have you already applied?
            TEXT],
        ['kind' => 'content', 'heading' => 'Recording EAD dates', 'body' => <<<'TEXT'
            - Write dates with the month name, for example June 1, 2026 to May 31, 2027.
            - Record the category if the candidate shares it, as stated on the card.
            - Record whether the card has been received.
            - Record any pending extension application and its date.
            TEXT],
        ['kind' => 'content', 'heading' => 'Using EAD dates in recruiting', 'body' => <<<'TEXT'
            Compare the start date with the requirement's start date.
            Compare the end date with the project duration. If the project lasts beyond the end date, flag it for HR, especially when an extension is planned.
            Inform your lead when a placed consultant's EAD end date is approaching, according to company process.
            TEXT],
        ['kind' => 'content', 'heading' => 'Pending EAD applications', 'body' => <<<'TEXT'
            If a candidate is waiting for approval, record the application date and expected timing as the candidate describes it. Do not promise start dates that depend on an approval. Keep the candidate in your pipeline and follow up.
            TEXT],
        ['kind' => 'content', 'heading' => 'Documents and privacy', 'body' => <<<'TEXT'
            Company-specific process: some companies ask for a copy of the EAD at a certain stage, for example before submission, to confirm the dates. Others collect it only during HR onboarding. Follow your company's process exactly and store documents only in approved systems. Verify with HR or authorized personnel.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            A candidate's EAD runs from June 1, 2026 to May 31, 2027. The requirement is a twelve-month contract starting June 15, 2026. You note that the project would extend beyond the EAD end date, and that the candidate plans to apply for STEM OPT. You flag this for HR before submission, as your process requires.
            TEXT],
        ['kind' => 'reference', 'heading' => 'Recruiter Checklist', 'body' => <<<'TEXT'
            - Record EAD dates exactly, with month names.
            - Compare dates with the start date and duration.
            - Flag date concerns to HR.
            - Follow the approved document process.
            TEXT],
        ['kind' => 'mistakes', 'heading' => 'Common Mistakes', 'body' => <<<'TEXT'
            - Recording dates in an unclear number format.
            - Ignoring the EAD end date.
            - Promising a start date before approval.
            TEXT],
        ['kind' => 'content', 'heading' => 'From the company calling script', 'body' => <<<'TEXT'
            The Day 7 questionnaire records the EAD card start date and end date as full dates, and the remaining unemployment days out of ninety or one hundred and fifty. Record all three. A candidate with a valid EAD but very few unemployment days left needs urgent and careful handling, and HR should be told.
            Company-specific process: EAD copies are collected only through the approved secure process. Verify with HR or authorized personnel.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Employment Documentation', 'body' => <<<'TEXT'
            Every employment document has an owner. Recruiters collect only what the process asks for, securely, and pass it on.
            TEXT],
        ['kind' => 'reference', 'heading' => 'Common Employment Documents', 'body' => <<<'TEXT'
            | Document | Owner |
            | --- | --- |
            | Offer letter or employment agreement | HR |
            | Form I-9 and its documents | HR |
            | W-4 and state tax forms | Payroll |
            | Form I-983 training plan, for STEM OPT | Student, employer and HR |
            | Timesheets and project records | Consultant, account management and payroll |
            | Employment details the student reports to the DSO | Student |
            TEXT],
        ['kind' => 'content', 'heading' => 'How Recruiters Handle Documents', 'body' => <<<'TEXT'
            - Request documents only at the stage your company process says, and only the ones it lists.
            - Use secure company channels. Never accept documents by text message or social media.
            - Never store document copies on personal devices.
            - Check only that a document is complete and readable. HR decides whether it is genuine and valid.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            A newly placed consultant sends you her signed offer letter and a photo of her EAD on a messaging app. You thank her, ask her to upload both through the secure company link, and delete the messages as your company process requires.
            TEXT],
        ['kind' => 'takeaway', 'heading' => 'Key Takeaway', 'body' => <<<'TEXT'
            Work authorization is verified by HR through the official process. Recruiters record status and EAD dates accurately and never treat an offer letter or any document as authorization.
            TEXT],
    ],
];
