<?php

/*
 * OPT Recruiter course 2. Existing lessons in four modules. Employment & Work
 * Authorization Basics is split: its visa categories become Other Visa &
 * Status Overview. EAD Dates moves here from the calling course. New lessons
 * are awareness level only; every lesson in this course needs compliance
 * review, and recruiters never give immigration or legal advice.
 */

$review = 'Immigration content: confirm against current official sources (USCIS, SEVP, DHS Study in the States) and company policy before publishing.';

return [
    'course' => 'Immigration & Work Authorization',
    'title' => 'Immigration & Work Authorization',
    'modules' => [
        'Visa / Status' => [
            ['title' => 'F-1', 'compliance' => true, 'review' => $review],
            [
                'title' => 'F-2',
                'compliance' => true,
                'review' => $review,
                'en' => [
                    ['kind' => 'objective', 'heading' => 'Learning Objective', 'body' => 'Recognise F-2 status, understand that it does not allow employment, and respond correctly when an F-2 dependent asks about jobs.'],
                    [
                        'kind' => 'content',
                        'heading' => 'What You Need to Know',
                        'body' => <<<'TEXT'
                        - F-2 is the dependent status for the spouse and unmarried children under 21 of an F-1 student.
                        - An F-2 dependent's status depends on the F-1 student keeping their own status.
                        - F-2 dependents are not allowed to work in the U.S. There is no F-2 work permit.
                        - An F-2 spouse may not study full time. Children may attend school from kindergarten to grade 12.
                        - To work, a person must first move to a status that allows employment. That is a decision for the person, their DSO and an immigration attorney, never for a recruiter.
                        TEXT,
                    ],
                    [
                        'kind' => 'reference',
                        'heading' => 'Quick Reference',
                        'body' => <<<'TEXT'
                        | Question | F-1 student | F-2 dependent |
                        | --- | --- | --- |
                        | Who holds it? | The student | Spouse or child of the student |
                        | Can work? | Only with CPT, OPT or STEM OPT | No |
                        | Full-time study? | Yes | Spouse no, children K-12 yes |
                        TEXT,
                    ],
                    ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => 'A caller says her husband is on F-1 and she is on F-2, and asks whether you can place her. You say: Thank you for telling me. Our process can only move forward with candidates who are authorized to work. Your DSO or an immigration attorney can explain your options. You record the call and do not submit her profile.'],
                    [
                        'kind' => 'mistakes',
                        'heading' => 'Common Mistakes',
                        'body' => <<<'TEXT'
                        - Treating F-2 like F-1 and asking about OPT dates.
                        - Suggesting a way to work, such as a change of status, yourself.
                        - Adding an F-2 dependent to a submission pipeline.
                        TEXT,
                    ],
                    ['kind' => 'takeaway', 'heading' => 'Key Takeaway', 'body' => 'F-2 does not allow work. Be kind, do not advise, and refer the person to their DSO or an attorney.'],
                ],
            ],
            ['title' => 'CPT', 'compliance' => true, 'review' => $review],
            ['title' => 'OPT', 'compliance' => true, 'review' => $review],
            ['title' => 'STEM OPT', 'compliance' => true, 'review' => $review],
            ['title' => 'H-1B', 'compliance' => true, 'review' => $review],
            ['title' => 'H-4', 'compliance' => true, 'review' => $review],
            ['title' => 'H-4 EAD', 'compliance' => true, 'review' => $review],
            ['title' => 'EAD', 'compliance' => true, 'review' => $review],
            [
                'title' => 'Green Card',
                'compliance' => true,
                'review' => $review,
                'en' => [
                    ['kind' => 'objective', 'heading' => 'Learning Objective', 'body' => 'Understand what a Green Card is, how it differs from temporary statuses, and how to handle Green Card topics on recruiter calls.'],
                    [
                        'kind' => 'content',
                        'heading' => 'What You Need to Know',
                        'body' => <<<'TEXT'
                        - A Green Card holder is a lawful permanent resident, or LPR. The card itself is Form I-551.
                        - A permanent resident can live in the U.S. and work for any employer, without an EAD.
                        - The card is usually renewed every ten years. A conditional card lasts two years. Permanent resident status does not end just because the card expires.
                        - A Green Card is not citizenship. A permanent resident may later apply to become a citizen.
                        - People get Green Cards through family, through an employer, through investment and through other routes.
                        TEXT,
                    ],
                    [
                        'kind' => 'content',
                        'heading' => 'Employment-Based Green Card, at an Awareness Level',
                        'body' => <<<'TEXT'
                        - The employer-sponsored route usually has several government steps, often over several years.
                        - Some people waiting for their Green Card hold an EAD while their application is pending.
                        - Sponsorship is a company policy decision made by management and HR, case by case.
                        TEXT,
                    ],
                    ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => 'A candidate asks whether your company sponsors Green Cards. You do not answer from memory. You say: Sponsorship decisions are made by our management under company policy. I will connect you with HR, who can explain the current policy. You record the question in your notes.'],
                    [
                        'kind' => 'mistakes',
                        'heading' => 'Common Mistakes',
                        'body' => <<<'TEXT'
                        - Promising Green Card sponsorship, or a timeline.
                        - Asking candidates whether they are a Green Card holder instead of using the approved work authorization questions.
                        - Treating an expired card as proof that someone cannot work. HR decides.
                        TEXT,
                    ],
                    ['kind' => 'takeaway', 'heading' => 'Key Takeaway', 'body' => 'A Green Card means permanent residence and open work authorization. Sponsorship questions always go to HR.'],
                ],
            ],
            [
                'title' => 'Other Visa & Status Overview',
                'from' => 'Employment & Work Authorization Basics',
                'compliance' => true,
                'review' => $review.' Split from Employment & Work Authorization Basics.',
                'en' => [
                    ['kind' => 'objective', 'heading' => 'Learning Objective', 'body' => 'Recognise the other U.S. visa and status categories you will hear from candidates and vendors, and know which ones allow work.'],
                    [
                        'kind' => 'content',
                        'heading' => 'What You Need to Know',
                        'body' => <<<'TEXT'
                        USCIS stands for U.S. Citizenship and Immigration Services. It is the government agency that decides petitions and applications such as OPT, STEM OPT, H-1B and Green Cards.
                        A visa is a document placed in the passport that allows a person to travel to the U.S. and seek entry for a specific purpose.
                        There are two basic types of U.S. visas. Immigrant visas are for people who intend to live permanently in the U.S. Nonimmigrant visas are for temporary purposes, such as tourism, business, study, exchange programs and temporary work.
                        TEXT,
                    ],
                    [
                        'kind' => 'reference',
                        'heading' => 'Categories You Will Hear About',
                        'body' => <<<'TEXT'
                        | Category | What it is | Work? |
                        | --- | --- | --- |
                        | U.S. Citizen (USC) | Born in the U.S. or naturalized. Not a visa. | Any employer |
                        | TN | Certain Canadian and Mexican professionals, under the USMCA, which replaced NAFTA in 2020. Up to three years at a time. | Sponsoring employer |
                        | B-1 / B-2 | Business and tourist visitors. | No |
                        | L-1 / L-2 | Intra-company transfer. L-1A managers up to seven years, L-1B specialised knowledge up to five years. L-2 is the dependent status. | Same company only; L-2 spouses may be authorized |
                        | E-3 | Australian citizens in specialty occupations, up to two years at a time. | Sponsoring employer |
                        | J-1 / J-2 | Exchange visitors and their dependents. Some J-1 holders must return home for two years. | Program rules; J-2 may apply for work authorization |
                        | H-2A, H-2B, H-3 | Agricultural, non-agricultural and trainee categories. Rare in IT staffing. | Program rules |
                        TEXT,
                    ],
                    ['kind' => 'content', 'heading' => 'Covered in Their Own Lessons', 'body' => 'F-1, F-2, CPT, OPT, STEM OPT, H-1B, H-4, H-4 EAD, EAD and Green Card each have their own lesson in this module.'],
                    ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => 'A vendor asks whether you have any TN candidates. You check your notes and the requirement, and you confirm with your Account Manager that the client accepts TN before you search. You do not decide eligibility yourself.'],
                    ['kind' => 'takeaway', 'heading' => 'Key Takeaway', 'body' => 'Know the names and the basic work rule of each category, and leave every eligibility decision to HR and the employer.'],
                ],
            ],
        ],
        'Documents & Systems' => [
            ['title' => 'I-20', 'compliance' => true, 'review' => $review],
            ['title' => 'I-765', 'compliance' => true, 'review' => $review],
            ['title' => 'I-983', 'compliance' => true, 'review' => $review],
            ['title' => 'I-94', 'compliance' => true, 'review' => $review],
            ['title' => 'I-9', 'compliance' => true, 'review' => $review],
            [
                'title' => 'Passport',
                'compliance' => true,
                'review' => $review,
                'en' => [
                    ['kind' => 'objective', 'heading' => 'Learning Objective', 'body' => 'Understand the role of the passport alongside the visa, I-94 and EAD, and how recruiters handle passport information safely.'],
                    [
                        'kind' => 'content',
                        'heading' => 'What You Need to Know',
                        'body' => <<<'TEXT'
                        - A passport is an identity and nationality document issued by the person's home country.
                        - The U.S. visa stamp is placed in the passport. The visa is for travel and entry; it is not the same as permission to work.
                        - On entry, the person's admission record is the Form I-94, which is linked to the passport.
                        - An OPT or STEM OPT candidate's permission to work is shown by the EAD card, not by the passport.
                        - For Form I-9, HR decides which documents are acceptable. The employee chooses which acceptable documents to show.
                        TEXT,
                    ],
                    [
                        'kind' => 'reference',
                        'heading' => 'How the Documents Fit Together',
                        'body' => <<<'TEXT'
                        | Document | What it shows |
                        | --- | --- |
                        | Passport | Identity and nationality |
                        | Visa | Permission to travel and seek entry |
                        | I-94 | Admission record and status |
                        | I-20 | Student program details, from the school |
                        | EAD | Permission to work, with start and end dates |
                        TEXT,
                    ],
                    ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => 'A candidate mentions that his visa stamp has expired, but he is in the U.S. on OPT with a valid EAD. You do not tell him whether this is a problem. You note what he said and pass it to HR with his other details.'],
                    [
                        'kind' => 'mistakes',
                        'heading' => 'Common Mistakes',
                        'body' => <<<'TEXT'
                        - Asking for a passport copy during screening.
                        - Treating an expired visa stamp as proof that a person cannot work.
                        - Telling a candidate which documents to show for Form I-9.
                        TEXT,
                    ],
                    ['kind' => 'takeaway', 'heading' => 'Key Takeaway', 'body' => 'The passport proves identity, the EAD proves permission to work, and HR decides which documents are needed and when.'],
                ],
            ],
            ['title' => 'SEVIS', 'compliance' => true, 'review' => $review],
            ['title' => 'DSO', 'compliance' => true, 'review' => $review],
            ['title' => 'E-Verify', 'compliance' => true, 'review' => $review],
        ],
        'Employment & Work Authorization' => [
            [
                'title' => 'Employment & Work Authorization Basics',
                'compliance' => true,
                'review' => $review.' The visa category overview moved to its own lesson.',
                'en' => [
                    ['kind' => 'objective', 'heading' => 'Learning Objective', 'body' => 'Bring together the core ideas of U.S. work authorization, including dates, Social Security Numbers and unemployment, so that you can screen candidates accurately and safely.'],
                    [
                        'kind' => 'content',
                        'heading' => 'What You Need to Know',
                        'body' => <<<'TEXT'
                        Employment authorization means a person is legally allowed to work in the U.S. Citizens and lawful permanent residents, also called Green Card holders, are authorized to work without time limits. Many others are authorized only in certain categories and for certain dates.
                        For OPT recruiters, the common categories are OPT, STEM OPT, H-1B, H-4 EAD and, less often, others such as Green Card holders.
                        TEXT,
                    ],
                    [
                        'kind' => 'content',
                        'heading' => 'Work Authorization Dates',
                        'body' => <<<'TEXT'
                        - The start date is the earliest day the person may work.
                        - The end date is the last day of the current authorization.
                        - Between these dates, the person may work within the rules of the category.
                        - Always write dates in the month, day, year format, and use the month name when possible.
                        - If the end date is near, note it clearly and inform HR, so that renewal, STEM OPT or another plan can be considered.
                        TEXT,
                    ],
                    [
                        'kind' => 'content',
                        'heading' => 'Social Security Number',
                        'body' => <<<'TEXT'
                        - A Social Security Number, or SSN, is a nine-digit number issued by the Social Security Administration. It is used for payroll, taxes and reporting.
                        - OPT candidates who did not have an SSN may apply for one, often around the time their EAD is approved.
                        - An SSN alone does not prove work authorization.
                        - Some employment processes, such as E-Verify, use the SSN. HR handles situations where an SSN has been applied for but not yet issued.
                        - An SSN is highly sensitive. Never ask for it in early screening. Collect it only through the approved onboarding process.
                        TEXT,
                    ],
                    ['kind' => 'content', 'heading' => 'Unemployment Awareness', 'body' => 'OPT and STEM OPT include limits on the total days a student may be unemployed. Candidates under pressure from these limits may be anxious to start quickly. Be kind and professional, but never rush a placement in a way that skips steps, and never advise on the limits. Refer them to their DSO.'],
                    ['kind' => 'content', 'heading' => 'Questions You May Ask', 'body' => 'Follow your company\'s approved screening script. Commonly approved questions are: Are you currently authorized to work in the U.S.? and Will you now or in the future require sponsorship for employment visa status? Ask everyone the same questions, regardless of name, accent or background.'],
                    ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => 'A candidate on OPT says her EAD ends in three months and she has a STEM degree. You record the EAD dates, note the STEM degree, and flag the end date in your handoff to HR. You do not tell her what to file or when.'],
                    [
                        'kind' => 'mistakes',
                        'heading' => 'Common Mistakes',
                        'body' => <<<'TEXT'
                        - Treating an SSN as proof of work authorization.
                        - Asking different questions to different candidates.
                        - Rushing a candidate who is close to an unemployment limit.
                        TEXT,
                    ],
                    ['kind' => 'takeaway', 'heading' => 'Key Takeaway', 'body' => 'Know the category, record the dates exactly, handle personal numbers carefully, and refer status decisions to HR and the DSO.'],
                ],
            ],
            [
                'title' => 'OPT Employment Basics',
                'compliance' => true,
                'review' => $review,
                'en' => [
                    ['kind' => 'objective', 'heading' => 'Learning Objective', 'body' => 'Understand the basic employment rules of post-completion OPT, so that you can screen OPT candidates accurately and know what to pass to HR.'],
                    [
                        'kind' => 'content',
                        'heading' => 'What You Need to Know',
                        'body' => <<<'TEXT'
                        - Post-completion OPT usually gives up to 12 months of work authorization for each higher degree level.
                        - Work may start only on or after the EAD start date, and must stop by the EAD end date.
                        - The work must be directly related to the student's major field of study.
                        - Work of at least 20 hours a week counts as employment for post-completion OPT.
                        - Unemployment during post-completion OPT is limited to a total of 90 days.
                        - Students report employment details and changes to their DSO, or through the SEVP Portal, within the deadlines their school gives them.
                        TEXT,
                    ],
                    [
                        'kind' => 'reference',
                        'heading' => 'What You Record on a Call',
                        'body' => <<<'TEXT'
                        | Item | Why |
                        | --- | --- |
                        | EAD start and end dates | The start date can never be before the EAD start date |
                        | Degree and major | HR and the candidate confirm the role relates to the degree |
                        | Current employment | Shows whether the candidate is employed or unemployed |
                        TEXT,
                    ],
                    ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => 'A candidate with a master\'s in data science asks whether a QA role would count for her OPT. You say: Whether a role relates to your degree is something you confirm with your DSO, and our HR team reviews it too. I will share the job description so you can check. You record her question for HR.'],
                    [
                        'kind' => 'mistakes',
                        'heading' => 'Common Mistakes',
                        'body' => <<<'TEXT'
                        - Agreeing a start date before the EAD start date.
                        - Telling a candidate that a role does or does not relate to their degree.
                        - Counting a candidate's unemployment days for them.
                        TEXT,
                    ],
                    ['kind' => 'takeaway', 'heading' => 'Key Takeaway', 'body' => 'OPT work must fit the EAD dates and relate to the degree. Record the facts and let the DSO and HR decide.'],
                ],
            ],
            [
                'title' => 'STEM OPT Employment Basics',
                'compliance' => true,
                'review' => $review,
                'en' => [
                    ['kind' => 'objective', 'heading' => 'Learning Objective', 'body' => 'Understand the extra employer and training requirements of STEM OPT, and why STEM OPT placements need HR and compliance review.'],
                    [
                        'kind' => 'content',
                        'heading' => 'What You Need to Know',
                        'body' => <<<'TEXT'
                        - STEM OPT is a 24-month extension of OPT for students with a qualifying STEM degree.
                        - The employer must be enrolled in E-Verify and must have an Employer Identification Number, or EIN.
                        - The job must be paid, for at least 20 hours a week, with pay comparable to similar U.S. workers.
                        - The student and employer complete a training plan, Form I-983, and the employer must provide that training and supervision.
                        - STEM OPT adds 60 days of allowed unemployment, for a total of 150 days across OPT and STEM OPT.
                        - The student reports to the DSO every six months, and the student and employer complete evaluations at the end of each year.
                        TEXT,
                    ],
                    ['kind' => 'content', 'heading' => 'Why STEM OPT Needs Extra Care in Staffing', 'body' => 'The STEM OPT rules expect the employer who signs the training plan to train and supervise the student directly. Placements at a client site therefore need careful review. Whether a STEM OPT candidate can be placed on a particular project is decided by HR and compliance, never by the recruiter.'],
                    ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => 'A candidate on STEM OPT asks whether your company can sign his I-983 for a client project. You say: That is decided by our HR and compliance team, who review the training plan for every STEM OPT placement. I will pass your question to them today.'],
                    [
                        'kind' => 'mistakes',
                        'heading' => 'Common Mistakes',
                        'body' => <<<'TEXT'
                        - Saying the company is enrolled in E-Verify without HR confirming it.
                        - Promising that an I-983 will be signed.
                        - Treating STEM OPT placements exactly like regular OPT placements.
                        TEXT,
                    ],
                    ['kind' => 'takeaway', 'heading' => 'Key Takeaway', 'body' => 'STEM OPT brings E-Verify, a training plan and reporting duties. Every STEM OPT placement goes through HR and compliance.'],
                ],
            ],
            [
                'title' => 'Work Authorization Verification',
                'compliance' => true,
                'review' => $review,
                'en' => [
                    ['kind' => 'objective', 'heading' => 'Learning Objective', 'body' => 'Understand who verifies work authorization, when it happens, and what the recruiter\'s part is.'],
                    [
                        'kind' => 'content',
                        'heading' => 'What You Need to Know',
                        'body' => <<<'TEXT'
                        - The employer verifies every new employee's identity and work authorization on Form I-9. HR does this, not the recruiter.
                        - Form I-9 is completed when the person starts work, within the deadlines set by the rules.
                        - Employers enrolled in E-Verify also confirm the I-9 information electronically.
                        - The employee chooses which acceptable documents to show. An employer may not demand specific documents.
                        - Before hiring, some vendors and clients ask staffing companies to confirm work authorization dates. Recruiters do this only through the approved process.
                        TEXT,
                    ],
                    [
                        'kind' => 'reference',
                        'heading' => 'Who Does What',
                        'body' => <<<'TEXT'
                        | Step | Who |
                        | --- | --- |
                        | Ask the approved screening questions and record answers | Recruiter |
                        | Check dates and names for consistency | Recruiter, then HR |
                        | Form I-9 and E-Verify | HR |
                        | Decide eligibility | Employer, through HR |
                        TEXT,
                    ],
                    ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => 'A vendor asks you to confirm that your candidate is work-authorized. You share only the work authorization type and dates recorded through the approved process, and you tell the vendor that formal verification is completed by HR at hire.'],
                    ['kind' => 'mistakes', 'heading' => 'Common Mistakes', 'body' => "- Telling a vendor a candidate is fully verified before HR has verified anything.\n- Asking a candidate for a specific document, such as only the EAD, for Form I-9.\n- Judging whether a document is genuine yourself."],
                    ['kind' => 'takeaway', 'heading' => 'Key Takeaway', 'body' => 'Recruiters record and check for consistency. HR verifies, on Form I-9 and through E-Verify where it applies.'],
                ],
            ],
            ['title' => 'EAD Dates', 'copy' => ['Calling & Communication', 'EAD Dates'], 'compliance' => true, 'review' => $review.' Moved from the calling course.'],
            [
                'title' => 'Employment Documentation',
                'compliance' => true,
                'review' => $review,
                'en' => [
                    ['kind' => 'objective', 'heading' => 'Learning Objective', 'body' => 'Know which employment documents are part of an OPT or STEM OPT placement, who owns each one, and how recruiters handle them.'],
                    [
                        'kind' => 'reference',
                        'heading' => 'Common Employment Documents',
                        'body' => <<<'TEXT'
                        | Document | Owner |
                        | --- | --- |
                        | Offer letter or employment agreement | HR |
                        | Form I-9 and its documents | HR |
                        | W-4 and state tax forms | Payroll |
                        | Form I-983 training plan, for STEM OPT | Student, employer and HR |
                        | Timesheets and project records | Consultant, account management and payroll |
                        | Employment details the student reports to the DSO | Student |
                        TEXT,
                    ],
                    [
                        'kind' => 'content',
                        'heading' => 'How Recruiters Handle Documents',
                        'body' => <<<'TEXT'
                        - Request documents only at the stage your company process says, and only the ones it lists.
                        - Use secure company channels. Never accept documents by text message or social media.
                        - Never store document copies on personal devices.
                        - Check only that a document is complete and readable. HR decides whether it is genuine and valid.
                        TEXT,
                    ],
                    ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => 'A newly placed consultant sends you her signed offer letter and a photo of her EAD on a messaging app. You thank her, ask her to upload both through the secure company link, and delete the messages as your company process requires.'],
                    ['kind' => 'takeaway', 'heading' => 'Key Takeaway', 'body' => 'Every employment document has an owner. Recruiters collect only what the process asks for, securely, and pass it on.'],
                ],
            ],
        ],
        'Recruiter Compliance' => [
            ['title' => 'Recruiter Compliance Boundaries', 'compliance' => true, 'review' => $review],
            [
                'title' => 'What Recruiters Should Verify',
                'compliance' => true,
                'review' => $review,
                'en' => [
                    ['kind' => 'objective', 'heading' => 'Learning Objective', 'body' => 'Know the consistency checks a recruiter makes before handing a candidate to HR or a vendor.'],
                    [
                        'kind' => 'reference',
                        'heading' => 'Recruiter Checklist',
                        'body' => <<<'TEXT'
                        | Check | What to look for |
                        | --- | --- |
                        | Name | The same spelling on the resume and on documents |
                        | Work authorization type | Matches what the candidate told you |
                        | EAD start date | On or before the proposed start date |
                        | EAD end date | Not close to the start date without HR knowing |
                        | Degree and graduation | Fit the OPT timeline the candidate described |
                        | Location | Current location and relocation plans are clear |
                        TEXT,
                    ],
                    ['kind' => 'content', 'heading' => 'What Verify Means for a Recruiter', 'body' => 'For a recruiter, verify means checking that information is complete and consistent. It never means deciding that a document is genuine, or that a person is eligible. Anything that does not match goes to HR.'],
                    ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => 'The proposed start date is June 1, but the candidate\'s EAD starts on June 10. You do not move the date yourself. You tell your lead and HR, and you let the candidate know that the start date will follow the EAD start date.'],
                    ['kind' => 'practice', 'heading' => 'Check Your Knowledge', 'body' => "1. Which two dates do you compare before agreeing a start date?\n2. A last name is spelled differently on the resume and the EAD. What do you do?\n3. Is checking that a document is genuine part of your role?"],
                    ['kind' => 'takeaway', 'heading' => 'Key Takeaway', 'body' => 'Check names, dates and status for consistency, and send every mismatch to HR.'],
                ],
            ],
            [
                'title' => 'What Recruiters Should Not Promise',
                'compliance' => true,
                'review' => $review,
                'en' => [
                    ['kind' => 'objective', 'heading' => 'Learning Objective', 'body' => 'Recognise the promises recruiters must never make, and use honest wording instead.'],
                    [
                        'kind' => 'reference',
                        'heading' => 'Never Promise, Say Instead',
                        'body' => <<<'TEXT'
                        | Never promise | Say instead |
                        | --- | --- |
                        | H-1B selection or approval | No company can guarantee H-1B. HR can explain our policy. |
                        | Green Card sponsorship | Sponsorship is decided by management. I will connect you with HR. |
                        | STEM OPT approval or an I-983 signature | HR and compliance review every STEM OPT case. |
                        | A placement, project or start date | Our goal is placement. I will keep you updated at every step. |
                        | That a role counts for OPT | Please confirm with your DSO. HR reviews it too. |
                        | A rate that is not approved | Let me check with my lead before I confirm anything. |
                        TEXT,
                    ],
                    ['kind' => 'content', 'heading' => 'Why It Matters', 'body' => 'Candidates make immigration and career decisions based on what you say. A false promise can harm the candidate and creates legal risk for the company. An honest answer, with a referral to HR, builds more trust than a promise.'],
                    ['kind' => 'note', 'heading' => 'Money and Offer Letters', 'body' => 'Never tell a candidate that a job, an offer letter, work authorization or sponsorship can be bought. Recruiters never collect money. Every question about fees goes to HR.'],
                    ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => 'A candidate says: Just tell me you will file my H-1B next year and I will join today. You reply: I understand how important that is. I cannot promise it, because the government decides selection and our management decides sponsorship. HR can explain our policy before you decide.'],
                    ['kind' => 'takeaway', 'heading' => 'Key Takeaway', 'body' => 'Never promise what the government or management decides. Give honest answers and refer to HR.'],
                ],
            ],
            [
                'title' => 'When to Escalate to HR, Compliance or DSO',
                'compliance' => true,
                'review' => $review,
                'en' => [
                    ['kind' => 'objective', 'heading' => 'Learning Objective', 'body' => 'Know which situations to escalate, and to whom, so that questions reach the right people quickly.'],
                    [
                        'kind' => 'reference',
                        'heading' => 'Who Handles What',
                        'body' => <<<'TEXT'
                        | Situation | Escalate to |
                        | --- | --- |
                        | Document mismatch, unusual document, status change | HR |
                        | STEM OPT, I-983, E-Verify or H-1B questions | HR and compliance |
                        | Requests for money, or pressure to skip steps | Your lead and HR |
                        | OPT eligibility, unemployment days, SEVIS or school questions | The candidate's own DSO |
                        | RFE, denial or any legal issue | HR; the candidate may also speak to an immigration attorney |
                        TEXT,
                    ],
                    ['kind' => 'content', 'heading' => 'About the DSO', 'body' => 'The DSO is the official at the candidate\'s school. The candidate contacts their own DSO. Recruiters do not contact a DSO on a candidate\'s behalf unless HR asks them to.'],
                    ['kind' => 'content', 'heading' => 'How to Escalate', 'body' => "- Write down the facts, not opinions.\n- Include the candidate's name, the question, the documents and the dates.\n- Tell the candidate who will respond, and do not guess in the meantime."],
                    ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => 'A candidate says USCIS sent her an RFE for her STEM OPT application. You do not explain what to do. You say: Thank you for telling me. Please share this with your DSO. I am informing our HR team so they know. You send HR a short note with the facts.'],
                    ['kind' => 'takeaway', 'heading' => 'Key Takeaway', 'body' => 'When in doubt, escalate. HR and compliance handle company decisions; the DSO handles school and status questions.'],
                ],
            ],
        ],
    ],
];
