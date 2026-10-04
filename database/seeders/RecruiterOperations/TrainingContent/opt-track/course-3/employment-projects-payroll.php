<?php

/*
 * Combined lesson "OPT Employment, Offer Letter, Projects, Timesheets &
 * Payroll": the former lessons "Offer Letter, Employment & Onboarding" and
 * "Projects, Timesheets & Payroll Process", their topics kept as tabs with
 * the wording unchanged. Each topic's checklist and common mistakes are
 * collected in the closing tab.
 */

return [
    'title' => 'OPT Employment, Offer Letter, Projects, Timesheets & Payroll',
    'from' => 'Offer Letter, Employment & Onboarding',
    'compliance' => true,
    'review' => 'Company-specific process: confirm each step, owner and any wording with management, HR and compliance before publishing. Immigration process content based on the OPT to STEM OPT process diagram: confirm every rule, date and form against current USCIS, SEVP and DHS Study in the States guidance before publishing. Company-specific process from the diagram: confirm each step, owner and any wording with management, HR and compliance before publishing.',
    'en' => [
        ['kind' => 'objective', 'heading' => 'Learning Objective', 'body' => <<<'TEXT'
            Understand what a genuine OPT employment opportunity involves, how the offer letter and employment agreement differ from immigration authorization, and how onboarding, duties, location and compensation are set.
            Understand how a consultant is assigned to a project and managed day to day: project manager responsibilities, timesheets, payroll, employment records and reporting changes.
            TEXT],
        ['kind' => 'flow', 'heading' => 'Lesson Map', 'body' => <<<'TEXT'
            1. A Genuine Employment Opportunity [[A Genuine Employment Opportunity]]
            2. Offer Letter vs Immigration Authorization [[Offer Letter vs Immigration Authorization]]
            3. The Employment Agreement [[The Employment Agreement]]
            4. Employer Onboarding [[Employer Onboarding]]
            5. Employment Eligibility Verification [[Employment Eligibility Verification]]
            6. Job Duties [[Job Duties]]
            7. Work Location [[Work Location]]
            8. Compensation [[Compensation]]
            9. Project Assignment [[Project Assignment]]
            10. Project Manager Responsibilities [[Project Manager Responsibilities]]
            11. Timesheets [[Timesheets]]
            12. Payroll [[Payroll]]
            13. Employment Records [[Employment Records]]
            14. Reporting Changes Where Applicable [[Reporting Changes Where Applicable]]
            15. Checklist & Common Mistakes [[Checklist & Common Mistakes]]
            TEXT],
        ['kind' => 'topic', 'heading' => 'A Genuine Employment Opportunity', 'body' => <<<'TEXT'
            Only real jobs with real work. Anything else puts the candidate and the company at risk.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            - OPT employment must be a genuine job, related to the candidate's field of study, with real duties.
            - Generally, the candidate works at least 20 hours a week for post-completion OPT employment to count.
            - A role that exists only on paper, or only to avoid unemployment days, is not acceptable.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Offer Letter vs Immigration Authorization', 'body' => <<<'TEXT'
            An offer letter confirms a job. Only USCIS grants work authorization.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            - **An offer letter itself does NOT create immigration work authorization.**
            - Work authorization for OPT comes from USCIS, through the approved EAD.
            - An offer letter describes the job: title, duties, location, pay and start date. It does not change the candidate's status.
            - The candidate may still need to report the employer to the DSO, and HR still completes Form I-9.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            A candidate asks, "Once I sign the offer letter, can I start working?" You say: "The offer letter confirms the job. Your EAD is what authorizes you to work, from its start date. HR will confirm your start date once onboarding is complete."
            TEXT],
        ['kind' => 'topic', 'heading' => 'The Employment Agreement', 'body' => <<<'TEXT'
            HR owns the offer letter and agreement. Recruiters explain the next step and pass questions on.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            - HR prepares and issues the offer letter and employment agreement. Recruiters do not write or change them.
            - The terms reflect the real role: duties, location, hours and compensation.
            - The candidate reads and signs the documents. Questions about terms go to HR.
            TEXT],
        ['kind' => 'note', 'heading' => 'Money Questions', 'body' => <<<'TEXT'
            Recruiters never collect money, never present an offer letter as something a candidate can buy, and refer every fee question to HR.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Employer Onboarding', 'body' => <<<'TEXT'
            Onboarding is a handoff chain. Each owner completes their step before the next begins.
            TEXT],
        ['kind' => 'flow', 'heading' => 'OPT Employment Flow', 'body' => <<<'TEXT'
            1. **Recruiter:** Confirms a genuine role. The role matches the candidate's skills and field of study.
            2. **Candidate:** Shares the EAD and documents. Through the secure document link only.
            3. **HR:** Reviews documents. HR and compliance confirm the documents and fit with the role.
            4. **Project Manager:** Confirms the project and duties. Duties must relate to the field of study.
            5. **HR:** Issues the offer letter. The offer letter confirms the job; it is not work authorization.
            6. **Candidate:** Signs and starts onboarding. Only on or after the EAD start date.
            7. **HR:** Completes Form I-9. Within the required time after the first day of work.
            8. **Candidate:** Reports employment to the DSO. Through the SEVP Portal or as the school instructs.
            9. **Finance / Payroll:** Sets up payroll. Pay runs through the company's payroll process.
            10. **Recruiter:** Follows up after the start. First day, first week, then regularly.
            TEXT],
        ['kind' => 'note', 'heading' => 'Your Boundary', 'body' => <<<'TEXT'
            HR leads onboarding. The recruiter keeps the candidate informed and passes changes to HR.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Employment Eligibility Verification', 'body' => <<<'TEXT'
            Form I-9 is HR's responsibility. Recruiters stay out of document choices and decisions.
            **Full lesson:** Immigration & Work Authorization → Employment & Work Authorization (Work Authorization Verification), and Immigration & Work Authorization → Immigration Documents & Systems (I-9, E-Verify).
            TEXT],
        ['kind' => 'topic', 'heading' => 'Job Duties', 'body' => <<<'TEXT'
            Real duties, related to the degree, recorded accurately.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            - OPT employment must be directly related to the candidate's major field of study.
            - The duties should be real and described accurately in the offer and project records.
            - The candidate may be asked to explain to the DSO how the job relates to the degree.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            A candidate with a computer science degree is matched to a software testing role. The duties list testing, automation and defect analysis, which you record accurately.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Work Location', 'body' => <<<'TEXT'
            Accurate work locations protect the candidate's records. Pass every change to HR.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            - The work location in company records must be where the candidate actually works.
            - The candidate may need to report the employer address to the DSO or in the SEVP Portal.
            - A change of work location is passed to HR straight away.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Compensation', 'body' => <<<'TEXT'
            Share only approved compensation. Money never flows from the candidate through the recruiter.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            - HR and management set compensation. Recruiters share only approved figures.
            - Compensation is paid through company payroll, never in cash or through personal accounts.
            - Recruiters never ask candidates to pay, deposit or fund anything.
            TEXT],
        ['kind' => 'note', 'heading' => 'Money Questions', 'body' => <<<'TEXT'
            Any question about fees, deductions or who funds payroll goes to HR and the Accountant.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Project Assignment', 'body' => <<<'TEXT'
            Projects are assigned after review, and only when they fit the candidate's real skills and field.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            - The technical team checks the candidate's skills against the project needs.
            - The project manager confirms the project, duties and supervision.
            - The project must relate to the candidate's field of study.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Project Manager Responsibilities', 'body' => <<<'TEXT'
            The project manager owns the work and the supervision. The recruiter keeps them informed.
            TEXT],
        ['kind' => 'content', 'heading' => 'The Project Manager', 'body' => <<<'TEXT'
            - Assigns real, field-related work and sets weekly tasks.
            - Supervises the consultant and reviews progress.
            - Approves timesheets and keeps project records accurate.
            - Tells HR about changes in role, project, hours or location.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            A consultant tells you his project is ending next month. You inform the project manager and HR the same day so the next steps can be planned.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Timesheets', 'body' => <<<'TEXT'
            Timesheets show real hours, approved by the project manager.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            - Timesheets record the hours actually worked and are approved by the project manager.
            - Accurate timesheets support payroll and show real employment.
            - Recruiters may remind consultants to submit timesheets on time, but never fill them in.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Payroll', 'body' => <<<'TEXT'
            Payroll belongs to Finance / Payroll. Pass every payroll question on.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            - Payroll runs through the company's Finance / Payroll team, based on approved timesheets.
            - Tax forms, deductions and payment dates are payroll and HR topics.
            - Recruiters pass payroll questions to the Accountant or HR and follow up until answered.
            TEXT],
        ['kind' => 'note', 'heading' => 'Money Questions', 'body' => <<<'TEXT'
            Recruiters do not collect, request or handle money from candidates.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Employment Records', 'body' => <<<'TEXT'
            Records are kept by HR, collected securely and never stored on personal devices.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            - HR keeps employment records: offer letter, I-9, EAD copy, I-20 and project records.
            - Documents are collected only through the secure document link, never on chat or personal email.
            - Recruiters record what was received and when, and never store copies on personal devices.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Reporting Changes Where Applicable', 'body' => <<<'TEXT'
            Report changes quickly: to HR internally, and by the candidate to the DSO.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            - During OPT, the student generally reports employer details, address changes and periods of unemployment to the DSO or in the SEVP Portal.
            - Changes are generally reported within 10 days.
            - The company passes changes such as a new project, location or end of employment to HR, who advise the candidate.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            A consultant moves to a new client site. You tell HR the same day and remind the consultant to check with the DSO about reporting the change.
            TEXT],
        ['kind' => 'note', 'heading' => 'Your Boundary', 'body' => <<<'TEXT'
            Recruiters explain the process at an awareness level only. The candidate's DSO, HR and, where needed, an immigration attorney give the answers.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Checklist & Common Mistakes', 'body' => <<<'TEXT'
            Every topic's checklist and common mistakes in one place. Use it as a quick review before you work with a real candidate.
            TEXT],
        ['kind' => 'reference', 'heading' => 'Recruiter Checklist', 'body' => <<<'TEXT'
            **Employment Records**
            - Documents uploaded through the secure link.
            - Receipt recorded in the company system.
            - Chat copies deleted as the company process requires.
            TEXT],
        ['kind' => 'mistakes', 'heading' => 'Common Mistakes', 'body' => <<<'TEXT'
            **A Genuine Employment Opportunity**
            - Offering a placeholder role "until a project comes".
            - Describing duties the candidate will not actually perform.
            **Offer Letter vs Immigration Authorization**
            - Saying an offer letter "gives" or "secures" status.
            - Issuing an offer letter as a document a candidate can use for immigration purposes without a real job behind it.
            **Work Location**
            - Leaving an old client address in the records.
            - Not telling HR when a consultant moves to a new site.
            **Project Assignment**
            - Adding skills to a resume the candidate does not have.
            - Promising a project before the review is complete.
            **Timesheets**
            - Submitting hours on a consultant's behalf.
            - Ignoring repeated missing timesheets.
            TEXT],
        ['kind' => 'takeaway', 'heading' => 'Key Takeaway', 'body' => <<<'TEXT'
            - An offer letter documents a genuine job. It never grants work authorization. Duties, location and compensation must be real and recorded accurately.
            - Accurate project assignment, timesheets, payroll and records protect the consultant and the company. Report changes to HR promptly.
            TEXT],
    ],
];
