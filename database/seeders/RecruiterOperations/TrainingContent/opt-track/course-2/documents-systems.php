<?php

/*
 * Combined lesson "Immigration Documents & Systems". One topic per merged lesson:
 * I-20, I-765, I-983, I-94, I-9, Passport, SEVIS, DSO, E-Verify.
 */

return [
    'title' => 'Immigration Documents & Systems',
    'from' => 'I-20',
    'compliance' => true,
    'review' => 'Immigration content: confirm against current official sources (USCIS, SEVP, DHS Study in the States) and company policy before publishing.',
    'en' => [
        ['kind' => 'objective', 'heading' => 'Learning Objective', 'body' => <<<'TEXT'
            Understand the immigration documents and systems behind work authorization: the I-20, I-765, I-983, I-94, I-9, passport, SEVIS, the DSO's role and E-Verify, and what a recruiter may and may not do with each.
            TEXT],
        ['kind' => 'flow', 'heading' => 'Lesson Map', 'body' => <<<'TEXT'
            1. I-20 [[I-20]]
            2. I-765 [[I-765]]
            3. I-983 [[I-983]]
            4. I-94 [[I-94]]
            5. I-9 [[I-9]]
            6. Passport [[Passport]]
            7. SEVIS [[SEVIS]]
            8. DSO [[DSO]]
            9. E-Verify [[E-Verify]]
            TEXT],
        ['kind' => 'topic', 'heading' => 'I-20', 'body' => <<<'TEXT'
            The I-20 is the student's program record. It explains degree, dates and authorizations. Handle it carefully and only as your process requires.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            Form I-20 is the Certificate of Eligibility for Nonimmigrant Student Status. It is issued by a school certified by the Student and Exchange Visitor Program. The student's Designated School Official signs and updates it.
            The I-20 is the main record of the student's program in SEVIS.
            TEXT],
        ['kind' => 'content', 'heading' => 'Information on the I-20', 'body' => <<<'TEXT'
            - The student's name and SEVIS identification number, which starts with the letter N.
            - The school name and address.
            - The program of study, the degree level and the major.
            - The program start and end dates.
            - Employment authorizations, such as CPT details or an OPT recommendation, on a separate page.
            - Travel signatures from the DSO.
            TEXT],
        ['kind' => 'content', 'heading' => 'Why the I-20 matters to recruiters', 'body' => <<<'TEXT'
            - It confirms the degree and major, which matter for OPT job relevance and STEM OPT eligibility.
            - It shows the program end date, which affects OPT timing.
            - It shows OPT recommendations and CPT authorizations.
            TEXT],
        ['kind' => 'content', 'heading' => 'Requesting the I-20', 'body' => <<<'TEXT'
            The I-20 contains personal information. Whether a copy is requested, at what stage and by whom is a company-specific process. In many companies, HR collects it during onboarding rather than recruiters during first calls.
            Request documents only when your company process requires it, and explain why.
            Store copies only in approved systems.
            TEXT],
        ['kind' => 'content', 'heading' => 'If a candidate refuses', 'body' => <<<'TEXT'
            Candidates may hesitate to share an I-20, especially early in the process. This is reasonable. Respect it, explain the approved reason and stage, and offer to proceed with the information they are comfortable sharing until the document is genuinely needed. Level 7 covers this conversation in detail.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            During screening, a candidate says her I-20 shows a program end date of December 15 and an OPT recommendation. You record these facts. Your company process says HR collects the I-20 after an offer, so you do not request a copy yet.
            TEXT],
        ['kind' => 'reference', 'heading' => 'Recruiter Checklist', 'body' => <<<'TEXT'
            - Note the degree, major and program end date.
            - Follow the company process for requesting copies.
            - Never share the SEVIS number or document copies outside approved channels.
            TEXT],
        ['kind' => 'mistakes', 'heading' => 'Common Mistakes', 'body' => <<<'TEXT'
            - Requesting documents too early without a reason.
            - Ignoring the major when judging OPT job relevance. Remember to escalate rather than decide.
            - Saving document copies on a personal device.
            TEXT],
        ['kind' => 'note', 'heading' => 'Source Note', 'body' => <<<'TEXT'
            The company objection cheat sheet mentions the I-20 only when a candidate asks why it is needed, and suggests letting the candidate watermark copies For Verification Only. The company documents do not explain the form itself. This lesson is based on published government guidance and needs review and approval by HR and the training manager.
            TEXT],
        ['kind' => 'topic', 'heading' => 'I-765', 'body' => <<<'TEXT'
            Form I-765 is how candidates request work authorization. Until an EAD is approved and its start date arrives, initial OPT candidates cannot start work.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            Form I-765 is the Application for Employment Authorization. It is filed with U.S. Citizenship and Immigration Services. OPT, STEM OPT and H-4 EAD applicants all use this form, each under a different category.
            If approved, the applicant receives an EAD card.
            TEXT],
        ['kind' => 'content', 'heading' => 'Filing windows for OPT, at awareness level', 'body' => <<<'TEXT'
            For post-completion OPT, under current rules the student can generally file up to ninety days before the program end date and up to sixty days after it, and within the time frame after the DSO's recommendation. Verify the current rules.
            For STEM OPT, the student generally must file before the current OPT EAD expires.
            TEXT],
        ['kind' => 'content', 'heading' => 'Receipt notice', 'body' => <<<'TEXT'
            After filing, the applicant receives a receipt notice, also called Form I-797C. It includes a receipt number, which starts with three letters showing the service center, followed by numbers.
            The applicant can check case status online using the receipt number.
            For an initial OPT application, a receipt notice generally does not allow work. The applicant must wait for the approved EAD card and its start date.
            For some renewals and STEM OPT extensions, current rules may allow continued work while the application is pending. HR must confirm.
            TEXT],
        ['kind' => 'content', 'heading' => 'Processing time', 'body' => <<<'TEXT'
            Processing times vary and can be several months. Some applicants pay for premium processing where it is offered. Never promise a candidate an approval date.
            TEXT],
        ['kind' => 'content', 'heading' => 'Why this matters for recruiters', 'body' => <<<'TEXT'
            A candidate with a pending I-765 is a future candidate. Record the receipt date and expected timeline so you can follow up at the right time.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            A candidate says: I filed my OPT I-765 six weeks ago. You note the filing date and status as pending. You do not submit her for a role that starts next week. You set a reminder to follow up, and you ask her to inform you once the card arrives.
            TEXT],
        ['kind' => 'reference', 'heading' => 'Recruiter Checklist', 'body' => <<<'TEXT'
            - Ask whether an I-765 has been filed and when.
            - Record the status as pending, approved or denied.
            - Do not treat a pending initial OPT application as work authorization.
            - Escalate questions about continued work during a pending extension to HR.
            TEXT],
        ['kind' => 'mistakes', 'heading' => 'Common Mistakes', 'body' => <<<'TEXT'
            - Assuming a receipt notice means approval.
            - Promising a candidate their EAD will arrive by a certain date.
            - Forgetting to follow up when the EAD is expected.
            TEXT],
        ['kind' => 'note', 'heading' => 'Source Note', 'body' => <<<'TEXT'
            The company training documents do not cover Form I-765. This lesson is based on published government guidance and needs review and approval by HR and the training manager.
            TEXT],
        ['kind' => 'topic', 'heading' => 'I-983', 'body' => <<<'TEXT'
            The I-983 is a formal STEM OPT training plan between the student, the employer and the school. Recruiters support it with accurate information and leave the rest to HR.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            Form I-983 is the Training Plan for STEM OPT Students. It is required for STEM OPT. The student and the employer complete it together, and the student submits it to the DSO.
            The I-983 describes the student's role, the employer's details, the supervisor, the learning objectives, how the training relates to the STEM degree, and how progress will be evaluated.
            The employer makes formal commitments on the form, including commitments about supervision, compensation and the training environment.
            TEXT],
        ['kind' => 'content', 'heading' => 'Ongoing obligations', 'body' => <<<'TEXT'
            - The student completes self-evaluations on the I-983 at set intervals, which the supervisor reviews and signs.
            - If key details change, such as the job, the supervisor or the employer, the plan may need to be updated or a new plan submitted.
            - If the student leaves, the employer has reporting duties to the DSO.
            TEXT],
        ['kind' => 'content', 'heading' => 'Who does what', 'body' => <<<'TEXT'
            - The student provides personal details and completes the self-evaluations.
            - The employer, through authorized HR or management, completes the employer sections and signs.
            - The DSO reviews and records the plan.
            - The recruiter does not fill in, sign or promise an I-983.
            TEXT],
        ['kind' => 'content', 'heading' => 'Recruiter role', 'body' => <<<'TEXT'
            - Know that STEM OPT candidates need an I-983 with each qualifying employer.
            - Recognise when a candidate's question is about the I-983 and hand it to HR.
            - Pass accurate role, location and start date information to HR, because these feed into the training plan.
            - Never promise that the company will sign an I-983.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            A STEM OPT candidate says: My current employer has my I-983. If I join you, what happens? You respond: Changing employers on STEM OPT usually involves a new training plan with the new employer. Our HR team handles this process and will guide you. You then flag this in your handoff to HR.
            TEXT],
        ['kind' => 'reference', 'heading' => 'Recruiter Checklist', 'body' => <<<'TEXT'
            - Identify STEM OPT candidates and note their EAD end date.
            - Hand all I-983 questions to HR.
            - Provide accurate job details in your handoff.
            - Never sign, complete or promise an I-983.
            TEXT],
        ['kind' => 'mistakes', 'heading' => 'Common Mistakes', 'body' => <<<'TEXT'
            - Telling a candidate the I-983 is just a formality.
            - Promising a quick turnaround on the I-983.
            - Giving incorrect job titles or locations that later conflict with the training plan.
            TEXT],
        ['kind' => 'note', 'heading' => 'Source Note', 'body' => <<<'TEXT'
            The company training documents do not cover Form I-983. This lesson is based on published government guidance and needs review and approval by HR and the training manager.
            TEXT],
        ['kind' => 'topic', 'heading' => 'I-94', 'body' => <<<'TEXT'
            The I-94 records a person's admission and class of admission. Recruiters should understand it, but HR interprets it.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            Form I-94 is the Arrival and Departure Record. It is created by U.S. Customs and Border Protection when a non-citizen is admitted to the U.S. Today it is mostly electronic, and travellers can download their latest I-94 from the official Customs and Border Protection website.
            The I-94 is different from the visa stamp. The visa is for entry. The I-94 records the admission.
            TEXT],
        ['kind' => 'content', 'heading' => 'Information on the I-94', 'body' => <<<'TEXT'
            - The admission record number.
            - The most recent date of entry.
            - The class of admission, for example F-1 or H-1B.
            - The admit until date. For F-1 students this usually shows D/S, meaning duration of status.
            TEXT],
        ['kind' => 'content', 'heading' => 'Why it matters', 'body' => <<<'TEXT'
            - The I-94 confirms the person's current class of admission.
            - A change of status inside the U.S., such as from F-1 to H-1B, is recorded on an approval notice that may include a new I-94.
            - HR may use the I-94 as part of onboarding or document review. Some employment verification processes may involve it.
            TEXT],
        ['kind' => 'content', 'heading' => 'Recruiter role', 'body' => <<<'TEXT'
            Know what the I-94 is, so you understand HR's requests.
            Do not interpret I-94 details or decide status from them. If you notice an inconsistency, such as a class of admission that does not match what the candidate told you, report it to HR rather than questioning the candidate as if they are wrong.
            Follow the company process for whether and when the I-94 is collected.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            HR asks you to remind a new consultant to provide their latest I-94. The consultant asks why. You explain: HR uses it to confirm your current admission record as part of onboarding. You can download it from the official Customs and Border Protection website. You then share the official website guidance that HR has approved.
            TEXT],
        ['kind' => 'reference', 'heading' => 'Recruiter Checklist', 'body' => <<<'TEXT'
            - Understand the difference between visa, status and I-94.
            - Direct candidates to the official website for their I-94, never to unofficial sites.
            - Escalate inconsistencies to HR.
            TEXT],
        ['kind' => 'mistakes', 'heading' => 'Common Mistakes', 'body' => <<<'TEXT'
            - Confusing the I-94 with the visa stamp.
            - Making assumptions about status from an old I-94.
            - Sending candidates to unofficial websites that charge fees.
            TEXT],
        ['kind' => 'note', 'heading' => 'Source Note', 'body' => <<<'TEXT'
            The company training documents do not cover Form I-94. This lesson is based on published government guidance and needs review and approval by HR and the training manager.
            TEXT],
        ['kind' => 'topic', 'heading' => 'I-9', 'body' => <<<'TEXT'
            Form I-9 is a legal employer process at the time of hire. Recruiters support it with accurate information and leave verification to HR.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            Every U.S. employer must complete Form I-9 for every new employee hired to work in the U.S. This applies to citizens and non-citizens alike.
            The form verifies the employee's identity and authorization to work.
            Under current rules, the employee completes Section 1 no later than the first day of employment. The employer completes Section 2, after examining the employee's documents, within three business days of the first day of work. Verify current rules with HR.
            TEXT],
        ['kind' => 'content', 'heading' => 'Acceptable documents', 'body' => <<<'TEXT'
            The form has Lists of Acceptable Documents. The employee may present one document from List A, which shows both identity and work authorization, or one document from List B for identity plus one from List C for work authorization.
            An unexpired EAD card is an example of a List A document.
            The employee chooses which acceptable documents to present. The employer must not demand specific documents.
            TEXT],
        ['kind' => 'content', 'heading' => 'Anti-discrimination awareness', 'body' => <<<'TEXT'
            U.S. law protects workers from discrimination in the hiring and verification process, including discrimination based on citizenship status or national origin. Practices such as asking for more or different documents than required, or rejecting valid documents, can create serious legal risk.
            This is one reason why I-9 verification is handled by trained HR staff, not by recruiters.
            TEXT],
        ['kind' => 'content', 'heading' => 'Reverification', 'body' => <<<'TEXT'
            When a work authorization document such as an EAD expires, the employer may need to reverify the employee's authorization. HR tracks this.
            TEXT],
        ['kind' => 'content', 'heading' => 'Recruiter role', 'body' => <<<'TEXT'
            - Know that the I-9 exists and that it happens at hire, not during screening.
            - Never tell a candidate which document they must bring for the I-9. Refer them to HR and the official list.
            - Never complete any part of the I-9 for anyone.
            - Pass accurate start date and work location details to HR, because I-9 timing depends on them.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            A new hire asks: Should I bring my EAD or my passport for the I-9? You respond: HR will share the official list of acceptable documents, and you can choose from that list. I will connect you with HR. You do not choose for the candidate.
            TEXT],
        ['kind' => 'reference', 'heading' => 'Recruiter Checklist', 'body' => <<<'TEXT'
            - Refer all I-9 questions to HR.
            - Never specify documents.
            - Provide an accurate start date to HR.
            TEXT],
        ['kind' => 'mistakes', 'heading' => 'Common Mistakes', 'body' => <<<'TEXT'
            - Telling a candidate they must provide a specific document.
            - Treating I-9 as a recruiter task.
            - Using I-9 language during early screening.
            TEXT],
        ['kind' => 'note', 'heading' => 'Source Note', 'body' => <<<'TEXT'
            The US Visa Types material notes that employers must check the employment eligibility of all employees, regardless of citizenship. The company documents do not describe Form I-9 itself. This lesson is based on published government guidance and needs review and approval by HR and the training manager.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Passport', 'body' => <<<'TEXT'
            The passport proves identity, the EAD proves permission to work, and HR decides which documents are needed and when.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            - A passport is an identity and nationality document issued by the person's home country.
            - The U.S. visa stamp is placed in the passport. The visa is for travel and entry; it is not the same as permission to work.
            - On entry, the person's admission record is the Form I-94, which is linked to the passport.
            - An OPT or STEM OPT candidate's permission to work is shown by the EAD card, not by the passport.
            - For Form I-9, HR decides which documents are acceptable. The employee chooses which acceptable documents to show.
            TEXT],
        ['kind' => 'reference', 'heading' => 'How the Documents Fit Together', 'body' => <<<'TEXT'
            | Document | What it shows |
            | --- | --- |
            | Passport | Identity and nationality |
            | Visa | Permission to travel and seek entry |
            | I-94 | Admission record and status |
            | I-20 | Student program details, from the school |
            | EAD | Permission to work, with start and end dates |
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            A candidate mentions that his visa stamp has expired, but he is in the U.S. on OPT with a valid EAD. You do not tell him whether this is a problem. You note what he said and pass it to HR with his other details.
            TEXT],
        ['kind' => 'mistakes', 'heading' => 'Common Mistakes', 'body' => <<<'TEXT'
            - Asking for a passport copy during screening.
            - Treating an expired visa stamp as proof that a person cannot work.
            - Telling a candidate which documents to show for Form I-9.
            TEXT],
        ['kind' => 'topic', 'heading' => 'SEVIS', 'body' => <<<'TEXT'
            SEVIS is the student record system behind F-1 and OPT. Recruiters support accuracy and refer all SEVIS matters to the DSO.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            SEVIS stands for the Student and Exchange Visitor Information System. It is a government database managed by the Student and Exchange Visitor Program, which is part of U.S. Immigration and Customs Enforcement.
            SEVIS holds records for F-1 and other students and exchange visitors. Each student has a SEVIS identification number that starts with the letter N. It appears on the I-20.
            The school's Designated School Officials update SEVIS. They record enrollment, program dates, OPT recommendations, CPT authorizations and transfers.
            TEXT],
        ['kind' => 'content', 'heading' => 'SEVIS and OPT', 'body' => <<<'TEXT'
            The DSO recommends OPT in SEVIS before the student files Form I-765.
            While on OPT, the student must report employment information. Students commonly use the SEVP Portal, an online tool, to report employer details. The DSO can also update the record.
            Unemployment days during OPT are calculated using the reported employment information. This is one reason accurate employer details matter.
            For STEM OPT, the I-983 and evaluations are reviewed by the DSO.
            TEXT],
        ['kind' => 'content', 'heading' => 'SEVIS record status', 'body' => <<<'TEXT'
            A student's SEVIS record can be active, completed, terminated or transferred, among other states. These are handled by the school. A terminated record can have serious consequences for the student. Recruiters must never guess or comment on a candidate's SEVIS status. If a candidate mentions a SEVIS problem, refer them to their DSO and follow company policy.
            TEXT],
        ['kind' => 'content', 'heading' => 'Recruiter role', 'body' => <<<'TEXT'
            - Understand that SEVIS is the record behind the I-20 and OPT.
            - Provide accurate employer name, address and role details to HR so that the candidate can report employment correctly.
            - Remind candidates, using approved wording, that reporting is their responsibility with guidance from their DSO.
            - Never request a candidate's SEVIS login.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            A newly placed consultant asks: Which employer name and address should I report in the SEVP Portal? You do not guess. You ask HR for the correct legal employer name and address and share it with the consultant through the approved channel.
            TEXT],
        ['kind' => 'reference', 'heading' => 'Recruiter Checklist', 'body' => <<<'TEXT'
            - Know that the SEVIS number starts with N and appears on the I-20.
            - Send candidates to their DSO for SEVIS questions.
            - Pass accurate employer details from HR.
            - Never handle SEVIS logins or reports for a candidate.
            TEXT],
        ['kind' => 'mistakes', 'heading' => 'Common Mistakes', 'body' => <<<'TEXT'
            - Giving a client name instead of the legal employer name for reporting.
            - Commenting on a candidate's SEVIS status.
            TEXT],
        ['kind' => 'note', 'heading' => 'Source Note', 'body' => <<<'TEXT'
            The company objection cheat sheet mentions candidates' worry about their SEVIS record when changing employers. The company documents do not explain SEVIS. This lesson is based on published government guidance and needs review and approval by HR and the training manager.
            TEXT],
        ['kind' => 'topic', 'heading' => 'DSO', 'body' => <<<'TEXT'
            The DSO is the student's official guide on F-1, OPT and STEM OPT matters. Refer status questions to them, every time.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            A Designated School Official, or DSO, is a school employee authorized to manage F-1 student records in SEVIS. Larger schools have several DSOs, often working in the International Student Office or International Student and Scholar Services. The Principal Designated School Official is called the PDSO.
            TEXT],
        ['kind' => 'content', 'heading' => 'What a DSO does', 'body' => <<<'TEXT'
            - Issues and updates the I-20.
            - Recommends OPT and STEM OPT in SEVIS.
            - Authorizes CPT.
            - Reviews I-983 training plans for STEM OPT.
            - Records employment information and helps students with reporting.
            - Advises students on maintaining status.
            - Processes transfers to other schools.
            TEXT],
        ['kind' => 'content', 'heading' => 'Why recruiters should know about the DSO', 'body' => <<<'TEXT'
            Candidates often ask recruiters questions only their DSO should answer, such as Will this job count for my OPT? or How many unemployment days do I have left?
            Directing these questions to the DSO protects the candidate and your company. It is the professional answer.
            TEXT],
        ['kind' => 'content', 'heading' => 'Contacting a DSO', 'body' => <<<'TEXT'
            - Recruiters do not usually contact a student's DSO directly about the student's status. The student does that.
            - University outreach, covered in Level 8, is different. That involves career centers and approved channels, not individual student records.
            - Never pretend to represent a student when contacting a school.
            TEXT],
        ['kind' => 'content', 'heading' => 'Useful wording', 'body' => <<<'TEXT'
            - That is a great question for your DSO, because they manage your SEVIS record and can give you an accurate answer.
            - I can share the job details with you so that you can discuss them with your DSO.
            - Our HR team will provide any employer information your DSO needs.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            A candidate asks whether a business analyst role is related enough to his computer science degree for OPT. You respond: Your DSO is the right person to guide you on that. I can send you the job description and duties so that you can discuss it with them. You also note the question for your HR team.
            TEXT],
        ['kind' => 'reference', 'heading' => 'Recruiter Checklist', 'body' => <<<'TEXT'
            - Direct status questions to the DSO.
            - Provide accurate job descriptions that the candidate can share with the DSO.
            - Never contact a DSO about a student's record on the student's behalf.
            TEXT],
        ['kind' => 'mistakes', 'heading' => 'Common Mistakes', 'body' => <<<'TEXT'
            - Answering status questions yourself to keep the conversation moving.
            - Telling a candidate their DSO will definitely approve something.
            TEXT],
        ['kind' => 'note', 'heading' => 'Source Note', 'body' => <<<'TEXT'
            The company training documents do not cover the role of the designated school official. This lesson is based on published government guidance and needs review and approval by HR and the training manager.
            TEXT],
        ['kind' => 'topic', 'heading' => 'E-Verify', 'body' => <<<'TEXT'
            E-Verify confirms work authorization after hire and is essential for STEM OPT. Share only approved information and never use it to pre-screen.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            E-Verify is an online system run by the Department of Homeland Security together with the Social Security Administration. Enrolled employers use it to confirm that information from a new employee's Form I-9 matches government records.
            Many employers use E-Verify voluntarily. Some are required to, such as certain federal contractors and employers in states that require it.
            For STEM OPT, the employer must be enrolled in E-Verify and in good standing. This is why STEM OPT candidates often ask, Is your company E-Verified?
            TEXT],
        ['kind' => 'content', 'heading' => 'How E-Verify works, at a high level', 'body' => <<<'TEXT'
            - The employer completes the Form I-9 first.
            - The employer creates an E-Verify case using I-9 information, within the required time after the employee starts work.
            - The result may confirm employment authorization, or show a mismatch that the employee has the right to contest.
            - While a mismatch is being resolved, the employer must not take adverse action against the employee because of it.
            TEXT],
        ['kind' => 'content', 'heading' => 'Important rules to be aware of', 'body' => <<<'TEXT'
            - E-Verify must not be used to pre-screen job applicants. Cases are created only after a person has accepted a job offer and completed the I-9.
            - E-Verify must be used consistently for all new hires at an enrolled location, regardless of citizenship or national origin.
            - Employers display notices informing employees of E-Verify participation.
            TEXT],
        ['kind' => 'content', 'heading' => 'Recruiter role', 'body' => <<<'TEXT'
            - Know whether your company is enrolled in E-Verify, using only HR-approved information.
            - When a candidate asks, share the approved answer. Some companies share an E-Verify company identification number for STEM OPT purposes, but only through HR.
            - Never offer to run anyone through E-Verify before hire.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            A STEM OPT candidate asks: Are you an E-Verify employer? Can you share your E-Verify number? You reply: Yes, our company participates in E-Verify. Our HR team provides the E-Verify details needed for your STEM OPT paperwork at the right stage. Your answer must reflect your company's actual status as confirmed by HR.
            TEXT],
        ['kind' => 'reference', 'heading' => 'Recruiter Checklist', 'body' => <<<'TEXT'
            - Know your company's E-Verify status from HR.
            - Never use or promise E-Verify for pre-screening.
            - Pass STEM OPT E-Verify questions to HR.
            TEXT],
        ['kind' => 'mistakes', 'heading' => 'Common Mistakes', 'body' => <<<'TEXT'
            - Claiming E-Verify enrollment without confirming it.
            - Suggesting E-Verify can check a candidate before an offer.
            - Sharing company identifiers without HR approval.
            TEXT],
        ['kind' => 'note', 'heading' => 'Source Note', 'body' => <<<'TEXT'
            The company documents say that HR completes the required E-Verify process after the offer letter, that the OPT recruiter follows up with candidates on pending verification, and that the screening questionnaire asks whether a candidate's current employer is E-Verified. They do not explain how E-Verify works. That part of this lesson is based on published government guidance and needs review and approval by HR.
            TEXT],
        ['kind' => 'takeaway', 'heading' => 'Key Takeaway', 'body' => <<<'TEXT'
            Know what each document and system is for and who owns it. Recruiters never complete, interpret or advise on immigration forms; HR, the DSO and USCIS do.
            TEXT],
    ],
];
