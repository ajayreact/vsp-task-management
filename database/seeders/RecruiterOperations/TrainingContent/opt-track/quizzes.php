<?php

/*
 * Training quizzes for the OPT & STEM OPT Recruiter Process course (course 3).
 * Each quiz is created once as a training quiz, published as its Version 1
 * (only published quiz versions can be linked) and linked to the course's
 * draft version. Question types: single choice (also used for scenarios and
 * for "which comes first" sequence questions), multiple choice and
 * true/false. Every question has an explanation.
 *
 * Options starting with "* " are correct.
 */

$choice = fn (string $category, string $prompt, array $options, string $explanation, bool $several = false): array => [
    'type' => $several ? 'multiple_choice' : 'single_choice',
    'category' => $category,
    'prompt' => $prompt,
    'explanation' => $explanation,
    'options' => array_map(fn (string $option) => [
        'text' => str_starts_with($option, '* ') ? substr($option, 2) : $option,
        'is_correct' => str_starts_with($option, '* '),
    ], $options),
];

$truth = fn (string $category, string $prompt, bool $answer, string $explanation): array => [
    'type' => 'true_false',
    'category' => $category,
    'prompt' => $prompt,
    'explanation' => $explanation,
    'options' => [['text' => 'True', 'is_correct' => $answer], ['text' => 'False', 'is_correct' => ! $answer]],
];

$settings = [
    'passing_percentage' => 70,
    'max_attempts' => 3,
    'time_limit_minutes' => null,
    'show_result' => true,
    'allow_review' => true,
    'randomize_questions' => false,
    'randomize_options' => false,
];

return [
    'course' => 'OPT Recruiter Process & Sourcing Strategy',
    'quizzes' => [
        [
            'title' => 'OPT Basics & Initial OPT Quiz',
            'description' => 'F-1 status, the DSO, SEVIS, the I-20, Form I-765, the EAD and the initial OPT process.',
            'settings' => $settings + ['instructions' => 'Answer every question. You need 70% to pass. Each answer shows an explanation when you review your attempt.'],
            'questions' => [
                $choice('DSO', 'Who recommends OPT in the student\'s SEVIS record?', ['* The Designated School Official (DSO)', 'USCIS', 'The employer', 'The recruiter'], 'The DSO at the student\'s school recommends OPT in SEVIS. USCIS then decides the application.'),
                $choice('SEVIS', 'What is SEVIS?', ['* The government system schools use to keep F-1 student records', 'The USCIS case status website', 'The employer\'s payroll system', 'A type of work permit'], 'SEVIS is the Student and Exchange Visitor Information System. DSOs record OPT and STEM OPT recommendations in it.'),
                $choice('I-20', 'What does the updated I-20 show after the DSO recommends OPT?', ['* The OPT recommendation', 'The USCIS approval', 'The employer\'s E-Verify number', 'The candidate\'s salary'], 'The DSO issues an updated I-20 showing the recommendation. It is not USCIS approval.'),
                $choice('I-765', 'Which form does the candidate file with USCIS to apply for OPT employment authorization?', ['* Form I-765', 'Form I-983', 'Form I-9', 'Form I-20'], 'Form I-765 is the Application for Employment Authorization. The I-983 is the STEM OPT training plan and the I-9 is completed by the employer.'),
                $choice('I-765', 'Generally, within how many days of the DSO\'s SEVIS recommendation must the initial OPT I-765 be filed?', ['* 30 days', '10 days', '90 days', '180 days'], 'The initial OPT I-765 must generally be filed within 30 days of the DSO recommendation, and within the OPT filing window.'),
                $choice('EAD', 'What allows an OPT candidate to begin work?', ['* An approved EAD whose start date has arrived', 'A signed offer letter', 'The DSO\'s recommendation in SEVIS', 'A USCIS receipt notice'], 'Work begins only with an approved EAD, on or after its start date. A recommendation, receipt or offer letter is not work authorization.'),
                $truth('EAD', 'A candidate may start OPT work a few days before the EAD start date if the client is in a hurry.', false, 'Work may begin only on or after the EAD start date.'),
                $truth('OPT', 'OPT employment must be related to the candidate\'s major field of study.', true, 'OPT is temporary employment directly related to the student\'s field of study.'),
                $choice('OPT', 'Generally, how long is initial post-completion OPT for each higher education level?', ['* Up to 12 months', 'Up to 24 months', 'Up to 36 months', 'Unlimited'], 'Initial OPT is generally up to 12 months per degree level. STEM OPT can add a 24-month extension.'),
                $choice('USCIS', 'USCIS sends the candidate a Request for Evidence (RFE). What does the recruiter do?', ['* Record it, inform HR and let the candidate work with the DSO or attorney', 'Help the candidate write the response', 'Tell the candidate the case will be approved anyway', 'Contact USCIS on the candidate\'s behalf'], 'An RFE is handled by the candidate with the DSO or an attorney. Recruiters never advise on responses.'),
                $choice('Process order', 'In the initial OPT process, which step comes first?', ['* The DSO recommends OPT in SEVIS', 'The candidate files Form I-765', 'USCIS issues the EAD', 'The candidate begins employment'], 'The order is: DSO recommendation, updated I-20, I-765 filing, USCIS decision, EAD, then employment.'),
                $choice('Process order', 'Which step comes immediately after USCIS approves the OPT application?', ['* USCIS issues the EAD', 'The DSO reviews eligibility', 'The candidate files Form I-765', 'The DSO issues the updated I-20'], 'After approval, USCIS issues the EAD. The candidate then checks the dates before starting work.'),
                $choice('DSO', 'Which of these are DSO responsibilities? Select all that apply.', ['* Reviewing OPT eligibility', '* Recommending OPT in SEVIS', '* Issuing the updated I-20', 'Approving the I-765', 'Setting the candidate\'s salary'], 'The DSO reviews eligibility, recommends in SEVIS and issues the I-20. USCIS approves the I-765 and the employer sets pay.', true),
                $choice('Recruiter responsibilities', 'A candidate sends a photo of her EAD on chat. What should you do?', ['* Ask her to upload it through the secure document link', 'Save it to your phone for HR', 'Forward it to the client', 'Decide whether it looks genuine'], 'Documents are collected only through the secure link, and HR, not the recruiter, reviews them.'),
                $truth('Recruiter responsibilities', 'A recruiter may tell a candidate how long USCIS will take to decide the I-765.', false, 'Processing times vary and cannot be predicted. The candidate tracks the case with the receipt number.'),
            ],
        ],
        [
            'title' => 'STEM OPT & Form I-983 Quiz',
            'description' => 'STEM OPT eligibility, E-Verify, Form I-983, the STEM OPT application, reporting and evaluations.',
            'settings' => $settings + ['instructions' => 'Answer every question. You need 70% to pass. Each answer shows an explanation when you review your attempt.'],
            'questions' => [
                $choice('STEM OPT', 'How long is the STEM OPT extension?', ['* 24 months', '12 months', '17 months', '36 months'], 'The STEM OPT extension is 24 months, in addition to initial post-completion OPT.'),
                $choice('E-Verify', 'Which employer requirement applies to STEM OPT but not to initial OPT?', ['* Enrollment in E-Verify', 'Paying through payroll', 'Issuing an offer letter', 'Having an office in the U.S.'], 'A STEM OPT employer must be enrolled in E-Verify and remain in good standing.'),
                $choice('I-983', 'What is Form I-983?', ['* The Training Plan for STEM OPT Students', 'The Application for Employment Authorization', 'The Employment Eligibility Verification form', 'The Certificate of Eligibility for F-1 status'], 'Form I-983 is the STEM OPT training plan. The I-765 is the application, the I-9 is employment verification and the I-20 is the school document.'),
                $choice('I-983', 'Who completes Form I-983?', ['* The student and the employer together', 'The recruiter', 'The DSO', 'USCIS'], 'The student and the employer each complete and sign their sections. The DSO reviews the finished plan.'),
                $choice('STEM OPT', 'What is the minimum weekly hours requirement for STEM OPT employment?', ['* At least 20 paid hours a week', 'At least 10 hours a week', 'At least 40 hours a week', 'There is no minimum'], 'STEM OPT employment must be at least 20 paid hours a week.'),
                $choice('Evaluations', 'When are the I-983 evaluations completed?', ['* At 12 months and at 24 months (or when the training ends)', 'Every month', 'Only at the start', 'At 6 and 18 months'], 'The self-evaluation is due at 12 months and the final evaluation at 24 months. Validation reporting happens every 6 months.'),
                $choice('Reporting', 'What happens at 6 and 18 months of STEM OPT?', ['* The student validates their information with the DSO', 'The employer files a new I-765', 'USCIS reissues the EAD', 'The I-983 is cancelled'], 'Every six months the student confirms their details with the DSO, such as address and employer information.'),
                $choice('Reporting', 'A STEM OPT consultant\'s employment ends. Generally, how quickly must the employer report it to the DSO?', ['* Within 5 business days', 'Within 30 days', 'Within 90 days', 'There is no deadline'], 'The employer generally reports the end of STEM OPT employment to the DSO within 5 business days.'),
                $choice('I-765', 'When can the STEM OPT I-765 generally be filed?', ['* Up to 90 days before the OPT EAD expires, within 60 days of the DSO recommendation', 'Any time after the OPT EAD expires', 'Only after the 24-month period', 'Before the DSO recommendation'], 'The STEM OPT I-765 is filed before the current OPT EAD expires, up to 90 days early, and within 60 days of the DSO recommendation.'),
                $truth('I-983', 'An employer can complete an I-983 for training that will not actually happen, as long as the form is signed.', false, 'The I-983 must describe real training that will actually happen. A false plan is a false statement to the government.'),
                $truth('STEM OPT', 'A STEM OPT candidate\'s degree must be on the DHS STEM Designated Degree Program List.', true, 'Eligibility requires a degree in a field on the DHS STEM list. The DSO confirms eligibility.'),
                $choice('I-983', 'Which of these are part of Form I-983? Select all that apply.', ['* Training Plan', '* Employer Certification', '* Evaluation of Student Progress', 'Candidate\'s bank details', 'Client billing rate'], 'The I-983 includes student and employer information, certifications, the training plan, site details and evaluations.', true),
                $choice('Process order', 'In the I-983 workflow, which happens last?', ['* The DSO reviews the I-983', 'The project manager defines the training', 'The candidate completes the student sections', 'The employer signs the I-983'], 'The plan is defined, completed by both sides, signed by the employer, then submitted to the DSO for review.'),
                $choice('Employer changes', 'A STEM OPT consultant moves to a new employer. What is generally required?', ['* A new I-983 with the new employer, submitted to the DSO within 10 days', 'Nothing, the old I-983 still applies', 'A new I-20 from USCIS', 'A new offer letter only'], 'A new STEM OPT employer needs a new I-983, generally within 10 days of starting.'),
                $choice('Employer changes', 'A consultant\'s supervisor, duties and hours change significantly. What is needed?', ['* A modified I-983, coordinated by HR', 'Nothing, as long as the client is the same', 'A new I-765', 'A new E-Verify enrollment'], 'A material change with the same employer requires a modified I-983. Recruiters pass the change to HR the same day.'),
            ],
        ],
        [
            'title' => 'Recruiter Responsibilities & Compliance Scenarios Quiz',
            'description' => 'Employer, recruiter and project manager responsibilities, offer letter vs authorization, escalation and scenarios.',
            'settings' => $settings + ['instructions' => 'Answer every question. You need 70% to pass. Scenario questions describe real conversations; choose the correct recruiter response.'],
            'questions' => [
                $truth('Offer letter vs authorization', 'An offer letter creates immigration work authorization.', false, 'An offer letter itself does NOT create immigration work authorization. For OPT, authorization comes from USCIS through the EAD.'),
                $choice('Scenario', 'A candidate asks: "If I pay money, can you guarantee my OPT?" What is the correct response?', ['* "No one can guarantee OPT. Only USCIS approves it, and we never take payment from candidates. I\'ll pass your question to HR."', '"Yes, if you pay the processing fee we can make sure it is approved."', '"Pay half now and half after approval."', '"I can ask USCIS to prioritise your case."'], 'Only USCIS decides OPT. Linking money to an outcome is misleading. Recruiters never collect money and must escalate to HR and compliance.'),
                $choice('Scenario', 'A candidate asks: "Does your offer letter give me work authorization?" What is the correct response?', ['* "No. The offer letter confirms the job. Your EAD from USCIS authorizes you to work, from its start date."', '"Yes, once you sign it you can start work."', '"Yes, if you also sign the employment agreement."', '"It does for the first 90 days."'], 'An offer letter is an employment document only. Work authorization comes from USCIS.'),
                $choice('Scenario', 'A candidate asks: "Can you create an I-983 even if there is no actual training?" What should you do?', ['* Say no, explain the I-983 must describe real training, and report the request to HR and compliance', 'Ask HR to prepare a standard plan', 'Fill in a generic training plan yourself', 'Tell the candidate to write their own plan'], 'The I-983 must describe real training. Requests for a paper-only plan are escalated to HR and compliance the same day.'),
                $choice('Scenario', 'During a check-in, you notice a consultant\'s EAD end date has passed. What do you do?', ['* Inform HR immediately and suggest the consultant contacts the DSO', 'Tell the consultant to keep working while it is sorted out', 'Wait until the next monthly check-in', 'Ask the client to extend the project'], 'An expired EAD goes to HR immediately. HR decides whether work must stop; the recruiter never says work can continue.'),
                $choice('Scenario', 'Account management wants to place a STEM OPT consultant on a project unrelated to the STEM degree. What do you do?', ['* Ask HR and compliance to review before the consultant is presented', 'Place the consultant and update the paperwork later', 'Change the consultant\'s training plan yourself', 'Tell the consultant not to mention it to the DSO'], 'STEM OPT work must relate to the degree and match the I-983. HR and compliance review first.'),
                $choice('Recruiter responsibilities', 'Who decides whether a candidate\'s documents are acceptable for Form I-9?', ['* HR', 'The recruiter', 'The project manager', 'The client'], 'HR completes and reviews Form I-9. Recruiters never judge documents.'),
                $choice('Project manager responsibilities', 'Which is a project manager responsibility?', ['* Assigning real work, supervising and approving timesheets', 'Recommending OPT in SEVIS', 'Issuing the EAD', 'Collecting candidate fees'], 'The project manager owns the work, supervision and timesheet approval.'),
                $choice('Employer responsibilities', 'Which are STEM OPT employer responsibilities? Select all that apply.', ['* Being enrolled in E-Verify', '* Providing the training in the I-983', '* Reporting the end of employment to the DSO', 'Approving the I-765', 'Recommending STEM OPT in SEVIS'], 'The employer enrolls in E-Verify, trains under the plan and reports terminations. USCIS approves and the DSO recommends.', true),
                $choice('Recruiter responsibilities', 'Which of these may a recruiter do? Select all that apply.', ['* Record EAD dates exactly as printed', '* Pass status questions to the candidate\'s DSO', '* Remind consultants about timesheets', 'Sign the I-983 for the employer', 'Accept a payment from a candidate'], 'Recruiters record, hand off and remind. They never sign immigration forms or take money.', true),
                $choice('Escalation', 'A consultant says he is resigning next week. When do you tell HR?', ['* The same day', 'After his last day', 'At the next monthly check-in', 'Only if he asks you to'], 'Terminations are reported quickly by the employer, so HR needs to know the same day.'),
                $truth('Escalation', 'Questions about working while a STEM OPT application is pending should be answered by the recruiter.', false, 'These questions go to the candidate\'s DSO and HR. Recruiters do not answer them.'),
                $choice('Process order', 'In the recruiter internal workflow, which step comes first?', ['* Candidate sourcing', 'HR document review', 'Offer letter and onboarding', 'Ongoing follow-up'], 'The workflow runs from sourcing and screening, through HR, compliance and project review, to onboarding, payroll and follow-up.'),
                $choice('Reporting', 'Generally, within how many days must an OPT student report changes such as a new employer or address?', ['* 10 days', '30 days', '90 days', '1 year'], 'OPT students generally report changes to the DSO or in the SEVP Portal within 10 days.'),
                $truth('Recruiter responsibilities', 'Recruiters may collect candidate documents through personal email if the secure link is slow.', false, 'Documents are collected only through the secure document link, never personal email or chat.'),
            ],
        ],
    ],
];
