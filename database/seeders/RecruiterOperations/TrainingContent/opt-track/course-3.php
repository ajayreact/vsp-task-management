<?php

/*
 * OPT Recruiter course 3, built on the OPT to Employment to STEM OPT process
 * diagram: seven process stages and the internal company process. Lessons are
 * short and awareness level. Every lesson needs compliance review: the
 * immigration steps must be checked against current official sources, and the
 * internal process against the company's approved process.
 */

$review = 'Immigration process content based on the OPT to STEM OPT process diagram: confirm every rule, date and form against current USCIS, SEVP and DHS Study in the States guidance before publishing.';
$company = 'Company-specific process from the diagram: confirm each step, owner and any wording with management, HR and compliance before publishing.';

$lesson = fn (string $objective, array $sections, string $takeaway): array => [
    ['kind' => 'objective', 'heading' => 'Learning Objective', 'body' => $objective],
    ...array_map(fn (array $section) => ['kind' => $section[0], 'heading' => $section[1], 'body' => $section[2]], $sections),
    ['kind' => 'takeaway', 'heading' => 'Key Takeaway', 'body' => $takeaway],
];

$boundary = 'Recruiters explain the process at an awareness level only. The candidate\'s DSO, HR and, where needed, an immigration attorney give the answers.';

return [
    'course' => 'OPT Recruiter Process & Sourcing Strategy',
    'title' => 'OPT to STEM OPT: Complete Recruiter Process',
    'modules' => [
        'Before OPT' => [
            [
                'title' => 'Completing the Degree & Contacting the DSO',
                'compliance' => true,
                'review' => $review,
                'en' => $lesson(
                    'Understand the first step of the OPT journey: an F-1 student finishing their degree and asking their DSO about OPT.',
                    [
                        ['content', 'What You Need to Know', <<<'TEXT'
                        - Post-completion OPT starts with an F-1 student who is completing, or has completed, their degree program.
                        - The student contacts the Designated School Official, or DSO, at their school to request OPT.
                        - The student can apply from 90 days before the program end date until 60 days after it.
                        - Many candidates you call are at this stage: graduating soon, with no EAD yet.
                        TEXT],
                        ['reference', 'What to Note on a Call', <<<'TEXT'
                        | Ask | Record |
                        | --- | --- |
                        | When do you complete your degree? | Month and year |
                        | Have you applied for OPT yet? | Not yet, applied, or approved |
                        TEXT],
                        ['example', 'Recruiter Example', 'A candidate graduates in May and has not applied for OPT. You record the graduation month and say: Please speak with your DSO about the OPT application. Once you have your EAD dates, we can plan the next steps together.'],
                        ['note', 'Your Boundary', $boundary],
                    ],
                    'OPT begins with the student and the DSO. Record where the candidate is in the journey, and never advise on timing.',
                ),
            ],
            [
                'title' => 'DSO Eligibility Review & Updated I-20',
                'compliance' => true,
                'review' => $review,
                'en' => $lesson(
                    'Understand what the DSO does before an OPT application and why the updated I-20 matters.',
                    [
                        ['content', 'What You Need to Know', <<<'TEXT'
                        - The DSO reviews whether the student is eligible for OPT under the school's records.
                        - If eligible, the DSO recommends OPT in SEVIS, the government's student database.
                        - The student receives an updated Form I-20 showing the OPT recommendation.
                        - USCIS must receive the student's OPT application within 30 days of the DSO's recommendation.
                        TEXT],
                        ['example', 'Recruiter Example', 'A candidate says his DSO has issued his updated I-20 with the OPT recommendation. You note that his application is about to be filed, and that he does not have an EAD yet.'],
                        ['mistakes', 'Common Mistakes', "- Telling a candidate whether they are eligible for OPT.\n- Asking for the I-20 on a first call."],
                    ],
                    'The DSO recommends OPT and updates the I-20. The candidate then files with USCIS.',
                ),
            ],
        ],
        'OPT Application' => [
            [
                'title' => 'Form I-765 & the USCIS Application',
                'compliance' => true,
                'review' => $review,
                'en' => $lesson(
                    'Understand how an OPT application is filed with USCIS, so that you can recognise an "applied, pending" candidate.',
                    [
                        ['content', 'What You Need to Know', <<<'TEXT'
                        - The student applies for OPT on Form I-765, the Application for Employment Authorization.
                        - The student files it with USCIS, online or by mail, with the updated I-20 and other evidence.
                        - USCIS sends a receipt notice with a receipt number, which the student uses to track the case.
                        - Filing fees and processing times change. Candidates check them on the USCIS website.
                        TEXT],
                        ['content', 'Applied Is Not Approved', 'A candidate whose application is pending cannot start work. Work begins only after the EAD is approved and its start date arrives.'],
                        ['example', 'Recruiter Example', 'A candidate says: I applied for OPT last month. You note that the application is pending and ask: Would you like me to keep in touch, so that we can plan once your EAD is approved?'],
                    ],
                    'Form I-765 is the OPT application. Pending means the candidate cannot work yet.',
                ),
            ],
            [
                'title' => 'USCIS Review: Approval, RFE or Denial',
                'compliance' => true,
                'review' => $review,
                'en' => $lesson(
                    'Recognise the possible outcomes of an OPT application and respond appropriately when a candidate shares one.',
                    [
                        ['reference', 'Possible Outcomes', <<<'TEXT'
                        | Outcome | What it means | What you do |
                        | --- | --- | --- |
                        | Approved | USCIS issues the EAD card | Record the EAD dates |
                        | RFE | USCIS asks for more evidence by a deadline | Refer to the DSO; inform HR if you are working with the candidate |
                        | Denied | The application was refused | Refer to the DSO or an attorney; do not advise |
                        TEXT],
                        ['example', 'Recruiter Example', 'A candidate tells you she received a Request for Evidence. You say: I am sorry to hear that. Your DSO is the right person to guide you on the response. I will keep your profile ready for when it is resolved.'],
                        ['note', 'Your Boundary', $boundary],
                    ],
                    'Approval brings the EAD. An RFE or denial goes to the DSO or an attorney, never to the recruiter.',
                ),
            ],
            [
                'title' => 'The EAD Card',
                'compliance' => true,
                'review' => $review,
                'en' => $lesson(
                    'Read the key details on an EAD card correctly.',
                    [
                        ['content', 'What You Need to Know', <<<'TEXT'
                        - The Employment Authorization Document, or EAD, is Form I-766.
                        - It shows the person's name, photo, category and the dates the card is valid.
                        - The category for post-completion OPT is usually printed as C03B, and for STEM OPT as C03C.
                        - The card's start and end dates are the work authorization dates.
                        TEXT],
                        ['reference', 'What You Record', "- Name exactly as on the card.\n- Category.\n- Card valid from date.\n- Card expires date."],
                        ['mistakes', 'Common Mistakes', "- Mixing up the card's start date with the application date.\n- Recording dates in a format that can be misread. Use the month name."],
                    ],
                    'The EAD shows who may work, in which category and between which dates. Record those details exactly.',
                ),
            ],
        ],
        'OPT Employment' => [
            [
                'title' => 'EAD Start & End Dates',
                'compliance' => true,
                'review' => $review,
                'en' => $lesson(
                    'Use the EAD start and end dates to plan a realistic start date with HR.',
                    [
                        ['content', 'What You Need to Know', <<<'TEXT'
                        - Employment can begin only on or after the EAD start date.
                        - Employment under that EAD must end by the EAD end date.
                        - If the end date is near, HR needs to know early, so that STEM OPT or another plan can be considered.
                        TEXT],
                        ['example', 'Recruiter Example', 'A client wants a start date of May 20, but the candidate\'s EAD starts on June 3. You tell your lead and HR the same day, and the start date is planned for June 3 or later.'],
                        ['practice', 'Check Your Knowledge', "1. The EAD starts on July 1. Can the candidate start on June 28?\n2. The EAD ends in six weeks. Who do you inform?"],
                    ],
                    'The EAD dates set the limits of every start date. Check them before anything is agreed.',
                ),
            ],
            [
                'title' => 'Candidate Documents & Employer Verification',
                'compliance' => true,
                'review' => $review,
                'en' => $lesson(
                    'Understand which documents are collected when an OPT candidate starts work, and who verifies them.',
                    [
                        ['content', 'What You Need to Know', <<<'TEXT'
                        - The company process lists which documents are collected and at which stage.
                        - Recruiters collect only those documents, through secure company channels.
                        - The employer verifies identity and work authorization on Form I-9, and through E-Verify where the employer is enrolled.
                        - Recruiters check that documents are complete and readable. HR decides whether they are genuine and valid.
                        TEXT],
                        ['example', 'Recruiter Example', 'A candidate uploads a blurry photo of his EAD. You ask him politely to upload a clear copy through the secure link, and you tell HR when it arrives.'],
                        ['mistakes', 'Common Mistakes', "- Collecting documents by text message.\n- Telling a client that a candidate is verified before HR has verified anything."],
                    ],
                    'Collect only what the process asks for, securely, and let HR verify.',
                ),
            ],
            [
                'title' => 'Form I-9',
                'compliance' => true,
                'review' => $review,
                'en' => $lesson(
                    'Understand Form I-9 at an awareness level, and the recruiter\'s limited part in it.',
                    [
                        ['content', 'What You Need to Know', <<<'TEXT'
                        - Every new employee in the U.S. completes Form I-9, Employment Eligibility Verification.
                        - The employee completes Section 1 no later than the first day of work.
                        - The employer reviews the employee's documents and completes Section 2 within three business days of the start.
                        - An EAD is one of the documents that can establish both identity and work authorization.
                        - The employee chooses which acceptable documents to show. The employer may not demand specific ones.
                        TEXT],
                        ['example', 'Recruiter Example', 'A consultant asks you which documents to bring for his I-9. You say: Our HR team will send you the list of acceptable documents, and you can choose from it. I will ask them to contact you today.'],
                    ],
                    'Form I-9 belongs to HR. Make sure the consultant knows HR will guide them, and never choose documents for them.',
                ),
            ],
            [
                'title' => 'Offer Letter & Starting Employment',
                'compliance' => true,
                'review' => $review.' '.$company,
                'en' => $lesson(
                    'Understand how an OPT candidate moves from offer to their first day of work.',
                    [
                        ['content', 'What You Need to Know', <<<'TEXT'
                        - HR issues the offer letter or employment agreement for a genuine position, with real duties, pay and a work location.
                        - The start date is on or after the EAD start date.
                        - After starting, the student reports their employer details to the school, through the DSO or the SEVP Portal, within the deadline the school gives.
                        TEXT],
                        ['note', 'Offer Letters Are Never Sold', 'An offer letter is never something a candidate can buy. Recruiters never collect money, and every question about cost goes to HR.'],
                        ['example', 'Recruiter Example', 'Your candidate accepts the offer. You confirm the start date against her EAD start date, tell HR, and remind her to report her new employer to her school as her DSO instructs.'],
                    ],
                    'A genuine offer, a start date inside the EAD dates, and employer reporting by the student start OPT employment correctly.',
                ),
            ],
        ],
        'OPT Work Period' => [
            [
                'title' => 'Employment Related to the Field of Study',
                'compliance' => true,
                'review' => $review,
                'en' => $lesson(
                    'Understand why OPT work must relate to the candidate\'s degree, and how recruiters handle that question.',
                    [
                        ['content', 'What You Need to Know', <<<'TEXT'
                        - OPT employment must be directly related to the student's major field of study.
                        - The student should be able to explain how the job uses what they studied.
                        - The DSO may ask the student about this, and HR reviews it for placements.
                        TEXT],
                        ['example', 'Recruiter Example', 'A candidate with a master\'s in computer science is offered a Java developer role. She asks you to confirm that it counts for OPT. You share the job description and say: Your DSO can confirm this with you, and our HR team reviews it as well.'],
                        ['mistakes', 'Common Mistakes', "- Telling a candidate that any IT job counts.\n- Changing a job title to make it look related."],
                    ],
                    'Job relevance is checked by the candidate, the DSO and HR. Share accurate job details; never decide it yourself.',
                ),
            ],
            [
                'title' => 'Timesheets, Project Records & Supervision',
                'compliance' => true,
                'review' => $review.' '.$company,
                'en' => $lesson(
                    'Understand why accurate timesheets, project records and supervision matter during OPT employment.',
                    [
                        ['content', 'What You Need to Know', <<<'TEXT'
                        - Timesheets record the real hours worked. Payroll pays from them.
                        - Project records show what the consultant actually does.
                        - The consultant should have a supervisor and receive training and guidance at work.
                        - Records must always be true. Hours, duties and dates are never invented or changed.
                        TEXT],
                        ['example', 'Recruiter Example', 'A consultant asks whether he can submit last week\'s timesheet with extra hours to reach a target. You say no, explain that timesheets must show the real hours, and inform your lead.'],
                    ],
                    'Real hours, real work and real supervision. Never ask anyone to record anything that did not happen.',
                ),
            ],
            [
                'title' => 'Employment Reporting & Changes',
                'compliance' => true,
                'review' => $review,
                'en' => $lesson(
                    'Know which changes during OPT must be reported, and who reports them.',
                    [
                        ['content', 'What You Need to Know', <<<'TEXT'
                        - During OPT, the student reports employment details and changes, such as a new employer, an address change or a job ending.
                        - Students usually report within 10 days, through the SEVP Portal or their DSO.
                        - Time without qualifying employment counts toward the 90-day unemployment limit.
                        - Inside the company, recruiters tell HR about any change they hear about, the same day.
                        TEXT],
                        ['example', 'Recruiter Example', 'A placed consultant tells you his project ends next Friday. You inform HR and account management immediately, and remind him to report the change as his DSO instructs.'],
                    ],
                    'Changes are reported by the student to the school, and by you to HR, quickly.',
                ),
            ],
            [
                'title' => 'Preparing for STEM OPT',
                'compliance' => true,
                'review' => $review,
                'en' => $lesson(
                    'Recognise when an OPT candidate should start preparing for STEM OPT, and what you pass to HR.',
                    [
                        ['content', 'What You Need to Know', <<<'TEXT'
                        - The process diagram says to check STEM OPT eligibility 90 to 120 days before the OPT end date.
                        - The student's degree must be on the DHS STEM Designated Degree Program List.
                        - The employer must be enrolled in E-Verify, and a training plan, Form I-983, is needed.
                        - The student can file up to 90 days before the current OPT EAD expires, and must file before it expires.
                        TEXT],
                        ['example', 'Recruiter Example', 'A consultant\'s OPT ends in four months and he has a STEM degree. You flag the end date to HR with his degree details, so that HR and the consultant can plan. You do not tell him what to file.'],
                        ['practice', 'Check Your Knowledge', "1. How early does the diagram say to check STEM OPT eligibility?\n2. Who decides whether the company can support a STEM OPT extension?"],
                    ],
                    'Flag OPT end dates early. STEM OPT planning needs time, HR and the DSO.',
                ),
            ],
        ],
        'STEM OPT Extension' => [
            [
                'title' => 'STEM OPT Eligibility',
                'compliance' => true,
                'review' => $review,
                'en' => $lesson(
                    'Understand the basic conditions for a STEM OPT extension at an awareness level.',
                    [
                        ['reference', 'Basic Conditions', <<<'TEXT'
                        | Condition | Detail |
                        | --- | --- |
                        | Degree | On the DHS STEM Designated Degree Program List |
                        | Current status | On post-completion OPT |
                        | Employer | Enrolled in E-Verify, with an EIN |
                        | Job | Paid, at least 20 hours a week, related to the STEM degree |
                        | Length | 24 months |
                        TEXT],
                        ['content', 'Your Role', 'Record the degree, major and EAD end date, and pass them to HR. Whether a candidate qualifies is decided by the DSO, HR and USCIS.'],
                        ['note', 'Your Boundary', $boundary],
                    ],
                    'STEM OPT depends on the degree, the employer and the job. Record the facts; others decide eligibility.',
                ),
            ],
            [
                'title' => 'E-Verify for STEM OPT',
                'compliance' => true,
                'review' => $review.' '.$company,
                'en' => $lesson(
                    'Understand why E-Verify matters for STEM OPT and how to answer E-Verify questions.',
                    [
                        ['content', 'What You Need to Know', <<<'TEXT'
                        - E-Verify is the government's online system that confirms an employee's work authorization from Form I-9 information.
                        - A STEM OPT employer must be enrolled in E-Verify and stay in good standing.
                        - The STEM OPT application includes the employer's E-Verify details.
                        TEXT],
                        ['example', 'Recruiter Example', 'A candidate asks: Is your company E-Verified? You answer only with the statement HR has approved. If you do not have one, you say: I will confirm that with HR and email you today.'],
                        ['mistakes', 'Common Mistakes', "- Answering E-Verify questions from memory.\n- Sharing E-Verify identifiers HR has not approved for sharing."],
                    ],
                    'E-Verify enrollment is required for STEM OPT. Use only HR\'s approved answer.',
                ),
            ],
            [
                'title' => 'Form I-983 Training Plan',
                'compliance' => true,
                'review' => $review,
                'en' => $lesson(
                    'Understand what Form I-983 is and who completes it.',
                    [
                        ['content', 'What You Need to Know', <<<'TEXT'
                        - Form I-983 is the Training Plan for STEM OPT Students.
                        - The student and the employer complete it together, before the DSO recommends STEM OPT.
                        - It describes the learning goals, the training, the supervision and how progress is measured.
                        - The employer commits to provide the training described, and the student keeps a copy for the school.
                        TEXT],
                        ['example', 'Recruiter Example', 'A candidate asks you to fill in his I-983. You explain that HR and his supervisor complete the employer sections with him, and you connect him with HR.'],
                    ],
                    'The I-983 is a real training commitment between the student and employer. HR leads it.',
                ),
            ],
            [
                'title' => 'Employer & Candidate Responsibilities',
                'compliance' => true,
                'review' => $review,
                'en' => $lesson(
                    'Know the main responsibilities of the employer and the candidate during a STEM OPT extension.',
                    [
                        ['reference', 'Who Is Responsible for What', <<<'TEXT'
                        | Employer | Candidate |
                        | --- | --- |
                        | Provide the training and supervision in the I-983 | Follow the training plan |
                        | Pay comparable to similar U.S. workers | Report changes to the DSO within 10 days |
                        | Report to the DSO if the student leaves, usually within five business days | Confirm their details with the DSO every six months |
                        | Report material changes to the training plan | Complete evaluations with the employer |
                        TEXT],
                        ['content', 'Your Role', 'Tell HR quickly about anything that changes a consultant\'s job, supervisor, hours or pay, so that the right reports can be made.'],
                    ],
                    'STEM OPT duties are shared. Your part is to pass on changes to HR immediately.',
                ),
            ],
            [
                'title' => 'DSO Review, SEVIS Update & Filing',
                'compliance' => true,
                'review' => $review,
                'en' => $lesson(
                    'Understand the final steps before a STEM OPT application is filed.',
                    [
                        ['content', 'What You Need to Know', <<<'TEXT'
                        - The student gives the completed I-983 to the DSO.
                        - The DSO reviews it and, if appropriate, recommends STEM OPT in SEVIS.
                        - The student receives an updated I-20 showing the STEM OPT recommendation.
                        - The student must file within 60 days of the DSO's recommendation, and before the current OPT EAD expires.
                        TEXT],
                        ['example', 'Recruiter Example', 'A consultant tells you his DSO has updated his I-20 for STEM OPT. You note it and inform HR, who may need to track the filing with him.'],
                    ],
                    'DSO review, the SEVIS update and the new I-20 come before the STEM OPT filing.',
                ),
            ],
        ],
        'STEM OPT Application' => [
            [
                'title' => 'Form I-765 for STEM OPT & USCIS Processing',
                'compliance' => true,
                'review' => $review,
                'en' => $lesson(
                    'Understand how the STEM OPT application is filed and what a pending application means for work.',
                    [
                        ['content', 'What You Need to Know', <<<'TEXT'
                        - The student files Form I-765 again, this time for the STEM OPT extension.
                        - The application includes the updated I-20 and the employer's E-Verify details.
                        - USCIS sends a receipt notice. Processing times vary.
                        - If the application was filed on time, the rules may allow the student to keep working for a limited period after the OPT EAD expires while it is pending. HR confirms each case.
                        TEXT],
                        ['example', 'Recruiter Example', 'A consultant\'s OPT EAD expires next week and his STEM OPT application is pending. You do not tell him whether he can keep working. You make sure HR knows, and HR confirms with him.'],
                    ],
                    'STEM OPT is filed on Form I-765. Whether work continues while it is pending is HR\'s decision.',
                ),
            ],
            [
                'title' => 'STEM OPT Approval, RFE or Denial',
                'compliance' => true,
                'review' => $review,
                'en' => $lesson(
                    'Respond appropriately to each STEM OPT application outcome.',
                    [
                        ['reference', 'Outcomes', <<<'TEXT'
                        | Outcome | What you do |
                        | --- | --- |
                        | Approved | Ask the consultant to share the new EAD with HR through the secure process |
                        | RFE | Refer to the DSO; inform HR |
                        | Denied | Inform HR immediately; refer to the DSO or an attorney |
                        TEXT],
                        ['note', 'Your Boundary', $boundary],
                    ],
                    'Every outcome goes to HR the same day. RFEs and denials are handled by the DSO, HR and attorneys.',
                ),
            ],
            [
                'title' => 'The STEM OPT EAD',
                'compliance' => true,
                'review' => $review,
                'en' => $lesson(
                    'Recognise the STEM OPT EAD and what happens when it arrives.',
                    [
                        ['content', 'What You Need to Know', <<<'TEXT'
                        - The STEM OPT EAD usually shows category C03C and dates covering the 24-month extension.
                        - HR updates the employment records, including Form I-9, with the new card.
                        - Record the new start and end dates exactly.
                        TEXT],
                        ['example', 'Recruiter Example', 'A consultant sends you a photo of his new STEM OPT EAD. You thank him, ask him to upload it through the secure link for HR, and update the dates in your records.'],
                    ],
                    'A new EAD means new dates. Get it to HR securely and update your records.',
                ),
            ],
        ],
        'STEM OPT Employment' => [
            [
                'title' => 'The 24-Month STEM OPT Period',
                'compliance' => true,
                'review' => $review,
                'en' => $lesson(
                    'Understand the shape of the 24-month STEM OPT period.',
                    [
                        ['content', 'What You Need to Know', <<<'TEXT'
                        - STEM OPT lasts 24 months from the STEM OPT EAD start date.
                        - The job must stay paid, at least 20 hours a week, and related to the STEM degree.
                        - Allowed unemployment is 150 days in total across OPT and STEM OPT.
                        - The process diagram marks checkpoints at 6, 12, 18 and 24 months.
                        TEXT],
                        ['example', 'Recruiter Example', 'You keep a reminder for each placed STEM OPT consultant at 6, 12, 18 and 24 months, so that HR is never surprised by a checkpoint.'],
                    ],
                    'Twenty-four months, with checkpoints every six months. Track the dates for HR.',
                ),
            ],
            [
                'title' => 'Training Plan, Reporting & Evaluations',
                'compliance' => true,
                'review' => $review,
                'en' => $lesson(
                    'Understand the reporting and evaluation steps during STEM OPT.',
                    [
                        ['reference', 'Checkpoints', <<<'TEXT'
                        | When | What happens |
                        | --- | --- |
                        | Every 6 months | The student confirms their details with the DSO |
                        | 12 months | The student and employer complete a self-evaluation on the I-983 |
                        | 24 months | The final evaluation is completed |
                        | Any material change | The training plan is updated and the DSO informed |
                        TEXT],
                        ['content', 'Your Role', 'Recruiters do not complete these forms. You remind HR of upcoming dates and pass on any change you hear about.'],
                    ],
                    'Validations every six months and evaluations each year keep STEM OPT on track. HR leads; you remind.',
                ),
            ],
            [
                'title' => 'Employment Changes During STEM OPT',
                'compliance' => true,
                'review' => $review,
                'en' => $lesson(
                    'Know what happens when a STEM OPT consultant\'s employment changes.',
                    [
                        ['content', 'What You Need to Know', <<<'TEXT'
                        - A new STEM OPT employer must also be enrolled in E-Verify and needs a new I-983.
                        - The student reports a change of employer to the DSO within 10 days.
                        - The employer reports to the DSO when the student leaves, usually within five business days.
                        - Changes to duties, supervisor, hours or pay may require an updated training plan.
                        TEXT],
                        ['example', 'Recruiter Example', 'A STEM OPT consultant\'s project is ending and a new client is interested. You tell HR before anything is agreed, because the change may need a new or updated training plan.'],
                    ],
                    'Any change during STEM OPT goes to HR before it happens, not after.',
                ),
            ],
            [
                'title' => 'End of STEM OPT',
                'compliance' => true,
                'review' => $review,
                'en' => $lesson(
                    'Understand what to watch for as STEM OPT comes to an end.',
                    [
                        ['content', 'What You Need to Know', <<<'TEXT'
                        - Work authorization ends on the STEM OPT EAD end date.
                        - After it ends, an F-1 student generally has a 60-day grace period to leave, change status or start a new program. Work is not allowed in that period.
                        - Some candidates move to H-1B or another status. Those plans are handled by HR, the company's attorneys and the candidate.
                        TEXT],
                        ['example', 'Recruiter Example', 'A consultant\'s STEM OPT ends in five months. You flag the date to HR and account management now, so that there is time to plan with the consultant and the client.'],
                        ['mistakes', 'Common Mistakes', "- Noticing the end date only a few weeks before.\n- Suggesting a consultant can keep working during the grace period."],
                    ],
                    'Flag STEM OPT end dates months ahead. What comes next is decided by HR, attorneys and the candidate.',
                ),
            ],
        ],
        'Internal Company Process' => [
            [
                'title' => 'Candidate Contact',
                'compliance' => true,
                'review' => $company,
                'en' => $lesson(
                    'Understand the first step of the internal company process: making contact and confirming interest.',
                    [
                        ['reference', 'The Internal Process', <<<'TEXT'
                        | Step | Owner |
                        | --- | --- |
                        | 1. Candidate Contact | Recruiter |
                        | 2. Document Collection | Recruiter, then HR |
                        | 3. HR / Compliance Review | HR and compliance |
                        | 4. Technical / Project Review | Technical team and account management |
                        | 5. Offer Letter | HR |
                        | 6. Onboarding | HR, with the recruiter |
                        | 7. Employment Support | Recruiter, HR and account management |
                        TEXT],
                        ['content', 'What You Do at This Step', "- Call or message the candidate using the approved calling script.\n- Confirm interest, work authorization status, dates and location.\n- Record everything in the company system the same day."],
                        ['example', 'Recruiter Example', 'You call a candidate, complete the screening questions, and she confirms she is interested. You record her answers and agree the next step: sharing documents through the secure link.'],
                    ],
                    'Candidate contact opens the process. Clear notes here make every later step easier.',
                ),
            ],
            ['title' => 'Document Collection', 'from' => 'Documentation Handoff', 'compliance' => true, 'review' => $company],
            [
                'title' => 'HR / Compliance Review',
                'compliance' => true,
                'review' => $company,
                'en' => $lesson(
                    'Understand what happens when HR and compliance review a candidate, and why recruiters wait for their decision.',
                    [
                        ['content', 'What You Need to Know', <<<'TEXT'
                        - HR and compliance review the candidate's documents for completeness, validity and fit with the role.
                        - Candidates who do not meet the documentation requirements do not move forward.
                        - The review decides whether the process may continue. The recruiter does not decide this.
                        TEXT],
                        ['note', 'Compliance Review Required', 'The company source material describes offer letters as a paid service and payroll amounts collected before payroll runs. These arrangements need written review by HR and qualified immigration counsel before any recruiter describes them. Recruiters never collect money, never present an offer letter as something a candidate can buy, and refer every fee question to HR.'],
                        ['example', 'Recruiter Example', 'A candidate asks when she will get her offer letter. You say: Our HR team is reviewing your documents now. I will update you as soon as they finish.'],
                    ],
                    'HR and compliance decide whether the process continues. Wait for their decision and keep the candidate informed.',
                ),
            ],
            [
                'title' => 'Technical / Project Review',
                'compliance' => true,
                'review' => $company,
                'en' => $lesson(
                    'Understand the technical and project review step and how to prepare candidates for it.',
                    [
                        ['content', 'What You Need to Know', <<<'TEXT'
                        - The technical team checks the candidate's skills against the technology and project needs.
                        - This may include a technical discussion, an assessment or a review of past projects.
                        - Account management confirms which projects or clients the profile can be presented to.
                        TEXT],
                        ['content', 'What You Do', "- Tell the candidate what to expect and how long it takes.\n- Make sure the resume reflects the candidate's real experience.\n- Pass the outcome to the candidate promptly."],
                        ['mistakes', 'Common Mistakes', "- Adding skills to a resume the candidate does not have.\n- Promising a project before the review is complete."],
                    ],
                    'The technical review matches real skills to real projects. Keep resumes honest and expectations clear.',
                ),
            ],
            ['title' => 'Offer Letter', 'from' => 'Offer Process', 'compliance' => true, 'review' => $company],
            ['title' => 'Onboarding', 'from' => 'Onboarding Handoff', 'compliance' => true, 'review' => $company],
            [
                'title' => 'Employment Support',
                'compliance' => true,
                'review' => $company,
                'en' => $lesson(
                    'Understand how recruiters support consultants after they start work.',
                    [
                        ['content', 'What You Need to Know', <<<'TEXT'
                        - Stay in regular contact: after the first day, after the first week, and regularly after that.
                        - Follow up on timesheets and payroll questions with the Accountant or HR.
                        - Track the EAD end date, project end dates and STEM OPT checkpoints, and alert HR early.
                        - Pass every concern to the right team: HR, payroll or account management.
                        TEXT],
                        ['note', 'Money Questions', 'Recruiters do not collect, request or handle money from candidates. Any question about who funds payroll goes to HR, the Accountant and the OPT Head.'],
                        ['example', 'Recruiter Example', 'In a monthly check-in, a consultant says his manager has changed. You thank him and tell HR the same day, because a STEM OPT training plan may need updating.'],
                    ],
                    'Support continues after the start. Stay in touch, track dates and pass changes to HR quickly.',
                ),
            ],
        ],
    ],
];
