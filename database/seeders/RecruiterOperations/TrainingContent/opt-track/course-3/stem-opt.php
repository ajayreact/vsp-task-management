<?php

/*
 * Combined lesson "STEM OPT — Eligibility, Transition, I-983, Application,
 * Employment & Reporting", in three parts: the former lessons "STEM OPT
 * Eligibility & OPT → STEM Transition", "Form I-983 & STEM OPT
 * Application" and "STEM OPT Employment, Evaluations & Reporting". Repeated
 * content is combined into one tab each: supervision (three tabs),
 * evaluations (the I-983 evaluations tab with the checkpoint, 12-month and
 * 24-month tabs, together with the 6- and 18-month validations), and the
 * start of STEM OPT employment (two tabs). Each topic's checklist and common
 * mistakes are collected in the closing tab.
 */

return [
    'title' => 'STEM OPT — Eligibility, Transition, I-983, Application, Employment & Reporting',
    'from' => 'STEM OPT Eligibility & OPT → STEM Transition',
    'compliance' => true,
    'review' => 'Immigration process content based on the OPT to STEM OPT process diagram: confirm every rule, date and form against current USCIS, SEVP and DHS Study in the States guidance before publishing. Immigration process content: confirm every rule, date and form against current USCIS, SEVP and DHS Study in the States guidance before publishing. Company-specific process from the diagram: confirm each step, owner and any wording with management, HR and compliance before publishing. Company-specific process: confirm each step, owner and any wording with management, HR and compliance before publishing.',
    'en' => [
        ['kind' => 'objective', 'heading' => 'Learning Objective', 'body' => <<<'TEXT'
            Understand STEM OPT from start to finish: who qualifies and how a candidate moves from OPT to STEM OPT, the Form I-983 training plan and the STEM OPT application, and what STEM OPT employment requires day to day.
            TEXT],
        ['kind' => 'flow', 'heading' => 'Lesson Map', 'body' => <<<'TEXT'
            1. Part 1 — Eligibility & OPT → STEM Transition [[Part 1 — Eligibility & OPT → STEM Transition]]
            2. Part 2 — Form I-983 & STEM OPT Application [[Part 2 — Form I-983 & STEM OPT Application]]
            3. Part 3 — STEM OPT Employment, Evaluations & Reporting [[Part 3 — STEM OPT Employment, Evaluations & Reporting]]
            4. Checklist & Common Mistakes [[Checklist & Common Mistakes]]
            TEXT],
        ['kind' => 'part', 'heading' => 'Part 1 — Eligibility & OPT → STEM Transition', 'body' => <<<'TEXT'
            Know the STEM OPT eligibility requirements on the employer and the job, how OPT and STEM OPT differ, and how a candidate moves from OPT to STEM OPT with the DSO.
            TEXT],
        ['kind' => 'flow', 'heading' => 'Part 1 Map', 'body' => <<<'TEXT'
            1. What Is STEM OPT [[What Is STEM OPT?]]
            2. The STEM Degree Requirement [[The STEM Degree Requirement]]
            3. Qualifying STEM Employer [[Qualifying STEM Employer]]
            4. The E-Verify Requirement [[The E-Verify Requirement]]
            5. A Real Employment Relationship [[A Real Employment Relationship]]
            6. The Training Requirement [[The Training Requirement]]
            7. The Hours Requirement [[The Hours Requirement]]
            8. Connecting the Job to the STEM Degree [[Connecting the Job to the STEM Degree]]
            9. When to Begin STEM OPT Planning [[When to Begin STEM OPT Planning]]
            10. Why the Candidate Must Work With the DSO [[Why the Candidate Must Work With the DSO]]
            11. OPT vs STEM OPT Comparison Table [[OPT vs STEM OPT Comparison Table]]
            12. The OPT to STEM OPT Transition [[The OPT to STEM OPT Transition]]
            13. OPT vs STEM OPT Quick Check [[OPT vs STEM OPT Quick Check]]
            TEXT],
        ['kind' => 'topic', 'heading' => 'What Is STEM OPT?', 'body' => <<<'TEXT'
            STEM OPT adds 24 months for qualifying STEM graduates, with stricter employer and training requirements.
            **Full lesson:** Immigration & Work Authorization → U.S. Visa & Immigration Statuses (STEM OPT).
            TEXT],
        ['kind' => 'topic', 'heading' => 'The STEM Degree Requirement', 'body' => <<<'TEXT'
            The degree must be on the DHS STEM list. The DSO confirms eligibility.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            - The degree must be in a field on the DHS STEM Designated Degree Program List.
            - The degree must be from an SEVP-certified, accredited U.S. school.
            - In some cases, an earlier qualifying STEM degree may be used. The DSO confirms this.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Qualifying STEM Employer', 'body' => <<<'TEXT'
            A STEM OPT employer is E-Verify enrolled, a real employer, and able to train.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            - The employer must be enrolled in E-Verify and remain in good standing.
            - The employer must have a real employer-employee relationship with the student.
            - The employer must have the resources and personnel to provide the training in the I-983.
            - The student must not replace a full- or part-time U.S. worker.
            TEXT],
        ['kind' => 'note', 'heading' => 'Your Boundary', 'body' => <<<'TEXT'
            Whether the company qualifies for a particular candidate's STEM OPT is confirmed by HR and compliance, not the recruiter.
            TEXT],
        ['kind' => 'topic', 'heading' => 'The E-Verify Requirement', 'body' => <<<'TEXT'
            E-Verify enrollment is required for STEM OPT employers. HR owns it.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            - E-Verify is a federal online system that compares Form I-9 information with government records.
            - A STEM OPT employer must be enrolled in E-Verify and in good standing.
            - The employer's E-Verify company ID goes on Form I-983 and the I-765.
            - HR manages E-Verify. Recruiters never run E-Verify checks.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            A candidate asks for the company's E-Verify number for his application. You pass the request to HR, who provide it through the approved channel.
            TEXT],
        ['kind' => 'topic', 'heading' => 'A Real Employment Relationship', 'body' => <<<'TEXT'
            STEM OPT needs a real employer who trains, supervises and pays the student.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            - STEM OPT requires a bona fide employer-employee relationship.
            - The employer that signs the I-983 is the one that trains, supervises and pays the student.
            - Arrangements where the student is only placed and not supervised by the employer need careful review by compliance.
            TEXT],
        ['kind' => 'topic', 'heading' => 'The Training Requirement', 'body' => <<<'TEXT'
            STEM OPT requires real training, planned on the I-983 and delivered in practice.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            - STEM OPT employment must include a structured training plan, recorded on Form I-983.
            - The plan sets goals, explains how the student will reach them, and how progress is measured.
            - The training must be real and actually delivered.
            TEXT],
        ['kind' => 'note', 'heading' => 'Compliance Rule', 'body' => <<<'TEXT'
            A training plan must never be written for training that will not happen.
            TEXT],
        ['kind' => 'topic', 'heading' => 'The Hours Requirement', 'body' => <<<'TEXT'
            At least 20 paid hours a week, with fair compensation.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            - STEM OPT employment must be at least 20 hours a week.
            - The work must be paid. Unpaid or volunteer roles do not qualify for STEM OPT.
            - Compensation must be commensurate with similarly situated U.S. workers.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Connecting the Job to the STEM Degree', 'body' => <<<'TEXT'
            The job and training must connect directly to the STEM degree.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            - The STEM OPT job must be directly related to the qualifying STEM degree.
            - The I-983 explains how the training relates to the degree.
            - A project unrelated to the degree is not a suitable STEM OPT assignment.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            A candidate with a data science degree is offered a role doing data pipelines and reporting. The connection to the degree is clear and is described in the training plan.
            TEXT],
        ['kind' => 'topic', 'heading' => 'When to Begin STEM OPT Planning', 'body' => <<<'TEXT'
            Flag OPT end dates months ahead so STEM OPT planning can start in time.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            - The STEM OPT I-765 can be filed up to 90 days before the current OPT EAD expires.
            - It must also generally be filed within 60 days of the DSO's STEM OPT recommendation.
            - The I-983 must be completed before the DSO can recommend, so planning starts months ahead.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            A consultant's OPT EAD ends in five months. You flag it to HR now so the I-983 can be prepared in good time.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Why the Candidate Must Work With the DSO', 'body' => <<<'TEXT'
            The candidate works with the DSO at every stage of STEM OPT.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            - Only the DSO can recommend STEM OPT in SEVIS.
            - The DSO reviews the I-983 and may ask for changes.
            - The DSO tracks reporting, validations and evaluations throughout STEM OPT.
            TEXT],
        ['kind' => 'note', 'heading' => 'Your Boundary', 'body' => <<<'TEXT'
            Recruiters explain the process at an awareness level only. The candidate's DSO, HR and, where needed, an immigration attorney give the answers.
            TEXT],
        ['kind' => 'topic', 'heading' => 'OPT vs STEM OPT Comparison Table', 'body' => <<<'TEXT'
            STEM OPT keeps the OPT basics and adds E-Verify, a training plan, supervision, validations and evaluations.
            TEXT],
        ['kind' => 'reference', 'heading' => 'OPT vs STEM OPT', 'body' => <<<'TEXT'
            | Topic | Initial OPT | STEM OPT |
            | --- | --- | --- |
            | Duration | Generally up to 12 months per degree level | 24-month extension of post-completion OPT |
            | DSO involvement | Reviews eligibility and recommends OPT in SEVIS | Reviews the I-983, recommends STEM OPT, tracks validations and evaluations |
            | USCIS application | Form I-765, approved by USCIS | Form I-765 for the extension, approved by USCIS |
            | I-20 | Updated I-20 with the OPT recommendation | Updated I-20 with the STEM OPT recommendation |
            | I-765 | Filed within 30 days of the recommendation and within the OPT window | Filed before the OPT EAD expires and within 60 days of the recommendation |
            | EAD | EAD with the OPT dates | New EAD with the 24-month extension dates |
            | Employer requirements | Genuine job related to the field of study | Genuine employer-employee relationship, resources to train, fair pay |
            | E-Verify | Not required | Employer must be enrolled and in good standing |
            | I-983 | Not required | Required training plan |
            | Training plan | No formal plan | Formal plan with goals, methods and measures |
            | Supervision | Normal job supervision | Named supervision described in the I-983 |
            | Reporting | Employer details, address, unemployment, within 10 days | Same, plus validation every 6 months and employer termination reports |
            | Evaluations | None | Self-evaluation at 12 months, final evaluation at 24 months |
            TEXT],
        ['kind' => 'topic', 'heading' => 'The OPT to STEM OPT Transition', 'body' => <<<'TEXT'
            The transition starts months ahead with an early flag from the recruiter.
            TEXT],
        ['kind' => 'flow', 'heading' => 'OPT to STEM OPT Transition Flow', 'body' => <<<'TEXT'
            1. **Recruiter:** Flags the OPT end date early. Months before the OPT EAD expires.
            2. **HR:** Confirms the employer can support STEM OPT. E-Verify, a genuine role and resources to train.
            3. **Candidate:** Confirms STEM degree eligibility with the DSO. The degree must be on the DHS STEM list.
            4. **Employer:** Prepares the I-983 with the candidate. Real training tied to the STEM degree.
            5. **DSO:** Reviews the I-983 and recommends STEM OPT. The recommendation is entered in SEVIS.
            6. **Candidate:** Files the STEM OPT I-765. Before the OPT EAD expires.
            7. **USCIS:** Decides the application. Approval brings the STEM OPT EAD.
            8. **HR:** Updates records and tracks checkpoints. The 6, 12, 18 and 24-month dates.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            A consultant's OPT EAD ends in four months. You flag it to HR today, and remind the consultant to speak with his DSO about STEM OPT.
            TEXT],
        ['kind' => 'topic', 'heading' => 'OPT vs STEM OPT Quick Check', 'body' => <<<'TEXT'
            Know the differences well enough to recognise when a question needs the DSO or HR.
            TEXT],
        ['kind' => 'practice', 'heading' => 'Quick Check', 'body' => <<<'TEXT'
            1. Which program requires the employer to be enrolled in E-Verify?
            2. Which form is the STEM OPT training plan?
            3. At which months are STEM OPT evaluations completed?
            4. Does an offer letter create work authorization?
            5. Who recommends OPT and STEM OPT in SEVIS?
            TEXT],
        ['kind' => 'reference', 'heading' => 'Answers', 'body' => <<<'TEXT'
            | Question | Answer |
            | --- | --- |
            | 1 | STEM OPT |
            | 2 | Form I-983 |
            | 3 | 12 months and 24 months |
            | 4 | No. Work authorization comes from USCIS through the EAD |
            | 5 | The DSO |
            TEXT],
        ['kind' => 'part', 'heading' => 'Part 2 — Form I-983 & STEM OPT Application', 'body' => <<<'TEXT'
            Follow the Form I-983 training plan and the STEM OPT application from the employer and candidate completing the plan to the STEM OPT EAD and continued employment.
            TEXT],
        ['kind' => 'flow', 'heading' => 'Part 2 Map', 'body' => <<<'TEXT'
            1. What Is Form I-983 [[What Is Form I-983?]]
            2. The I-983 Workflow [[The I-983 Workflow]]
            3. Employer and Candidate Complete the I-983 Together [[Employer and Candidate Complete the I-983 Together]]
            4. Student and Employer Information Sections [[Student and Employer Information Sections]]
            5. Training Objectives [[Training Objectives]]
            6. Supervision and Oversight [[Supervision and Oversight]]
            7. Hours and Compensation [[Hours and Compensation]]
            8. Learning Goals and Measuring Progress [[Learning Goals and Measuring Progress]]
            9. Employer Signature and DSO Submission [[Employer Signature and DSO Submission]]
            10. The STEM OPT Application at a Glance [[The STEM OPT Application at a Glance]]
            11. Candidate Works With the DSO [[Candidate Works With the DSO]]
            12. DSO Reviews the I-983 [[DSO Reviews the I-983]]
            13. DSO Makes the STEM OPT Recommendation in SEVIS [[DSO Makes the STEM OPT Recommendation in SEVIS]]
            14. DSO Issues the STEM OPT I-20 [[DSO Issues the STEM OPT I-20]]
            15. Candidate Files Form I-765 for STEM OPT [[Candidate Files Form I-765 for STEM OPT]]
            16. USCIS Reviews the STEM OPT Application [[USCIS Reviews the STEM OPT Application]]
            17. STEM OPT Approval, RFE or Denial [[STEM OPT Approval, RFE or Denial]]
            18. The STEM OPT EAD [[The STEM OPT EAD]]
            19. Candidate Verifies the STEM EAD Dates [[Candidate Verifies the STEM EAD Dates]]
            TEXT],
        ['kind' => 'topic', 'heading' => 'What Is Form I-983?', 'body' => <<<'TEXT'
            The I-983 is the STEM OPT training plan, prepared jointly by the student and the employer.
            **Full lesson:** Immigration & Work Authorization → Immigration Documents & Systems (I-983).
            TEXT],
        ['kind' => 'topic', 'heading' => 'The I-983 Workflow', 'body' => <<<'TEXT'
            The I-983 moves from the candidate, through HR and the employer, to the DSO. Every statement must be true.
            TEXT],
        ['kind' => 'flow', 'heading' => 'Form I-983 Workflow', 'body' => <<<'TEXT'
            1. **Candidate:** Requests STEM OPT support. The candidate tells HR they plan to apply for STEM OPT.
            2. **HR:** Confirms the employer can support STEM OPT. E-Verify enrollment, a genuine role and resources to train.
            3. **Project Manager:** Defines the real training. Goals, tasks and supervision tied to the STEM degree.
            4. **Candidate:** Completes the student sections. Student information, certification and the learning goals.
            5. **Employer:** Completes the employer sections. Employer information, certification, site and official details.
            6. **Compliance:** Reviews the completed plan. Checks the plan is accurate and the training is real.
            7. **Employer:** Signs the I-983. An authorized official signs the employer certification.
            8. **Candidate:** Submits the I-983 to the DSO. Through the school's process.
            9. **DSO:** Reviews the I-983. The DSO may ask for corrections before recommending.
            TEXT],
        ['kind' => 'note', 'heading' => 'Your Boundary', 'body' => <<<'TEXT'
            Recruiters do not fill in, edit or sign any part of the I-983. They pass requests to HR and keep the candidate informed.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Employer and Candidate Complete the I-983 Together', 'body' => <<<'TEXT'
            The I-983 is a joint document. Each side certifies its own part.
            TEXT],
        ['kind' => 'content', 'heading' => 'Shared Responsibilities', 'body' => <<<'TEXT'
            - The student and the employer each complete their own sections and both sign.
            - The training plan section is written together, so goals and methods are realistic.
            - Both are responsible for the accuracy of the plan and for updating it when things change.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Student and Employer Information Sections', 'body' => <<<'TEXT'
            Each information section is completed by its owner, with details from authorized sources.
            TEXT],
        ['kind' => 'reference', 'heading' => 'Sections 1 to 4 and 6', 'body' => <<<'TEXT'
            | Section | What it records |
            | --- | --- |
            | Student Information | Name, SEVIS number, degree, school and DSO contact |
            | Student Certification | The student confirms the plan is accurate and agrees to report changes |
            | Employer Information | Employer name, address, EIN, E-Verify number, and the employer's size |
            | Employer Certification | The employer confirms the training, supervision and compensation requirements |
            | Employer Site and Official Information | The work site and the official responsible for the training |
            TEXT],
        ['kind' => 'note', 'heading' => 'Your Boundary', 'body' => <<<'TEXT'
            Employer details such as the EIN and E-Verify number are provided by HR only.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Training Objectives', 'body' => <<<'TEXT'
            Training objectives are specific, real and tied to the STEM degree.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            - The training plan states specific goals: the skills and knowledge the student will gain.
            - Goals must relate to the STEM degree and to the actual work.
            - Generic or copied goals are a common reason a DSO asks for corrections.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            A good goal: "Build and test REST services in Java, reaching independent delivery of features by month six." A weak goal: "Learn software."
            TEXT],
        ['kind' => 'topic', 'heading' => 'Supervision and Oversight', 'body' => <<<'TEXT'
            Supervision on paper must match supervision in practice.
            STEM OPT employers must have the people and resources to train and supervise.
            Supervision must be real and regular. Report gaps to HR.
            TEXT],
        ['kind' => 'content', 'heading' => 'Employer Resources', 'body' => <<<'TEXT'
            - The employer must have enough resources and experienced personnel to deliver the training.
            - The employer confirms this on the I-983 employer certification.
            TEXT],
        ['kind' => 'content', 'heading' => 'On the I-983', 'body' => <<<'TEXT'
            - The plan names how the employer will oversee and supervise the training.
            - A named, qualified supervisor oversees the student's training, guides the student, reviews progress and signs evaluations.
            - Supervision must actually happen, and is shown in project records and evaluations.
            TEXT],
        ['kind' => 'content', 'heading' => 'Supervision in Practice', 'body' => <<<'TEXT'
            - The supervisor named in the plan works with the consultant regularly.
            - Supervision includes guidance, feedback and review of work.
            - Weak or absent supervision puts the training plan at risk.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            In a check-in, a consultant says he has not spoken to his supervisor in a month. You pass this to HR and the project manager the same day.
            TEXT],
        ['kind' => 'note', 'heading' => 'Your Boundary', 'body' => <<<'TEXT'
            HR and the project manager confirm supervision. The recruiter never names a supervisor.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Hours and Compensation', 'body' => <<<'TEXT'
            Hours and pay on the I-983 must be accurate, fair and kept up to date.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            - The plan records at least 20 hours a week of paid employment.
            - Compensation must be commensurate with similarly situated U.S. workers.
            - Changes to hours or pay may be material changes that need a modified I-983.
            TEXT],
        ['kind' => 'note', 'heading' => 'Money Questions', 'body' => <<<'TEXT'
            HR sets compensation. Recruiters never discuss amounts beyond what HR has approved.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Learning Goals and Measuring Progress', 'body' => <<<'TEXT'
            Every learning goal has a method and a measure.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            - Each goal has a method: how the student will learn, such as mentoring, project work or courses.
            - Each goal has a measure: how progress is checked, such as reviews, deliverables or assessments.
            - These measures are used later in the 12-month and 24-month evaluations.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            Goal: automation testing. Method: pairing with a senior engineer on the test framework. Measure: monthly review of test coverage and defects found.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Employer Signature and DSO Submission', 'body' => <<<'TEXT'
            An authorized official signs, the student submits, and the DSO reviews.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            - An authorized employer official signs the employer certification. Recruiters never sign.
            - The student submits the signed plan to the DSO as the school instructs.
            - The DSO reviews it before making the STEM OPT recommendation in SEVIS.
            TEXT],
        ['kind' => 'topic', 'heading' => 'The STEM OPT Application at a Glance', 'body' => <<<'TEXT'
            STEM OPT moves from the candidate and DSO, to USCIS, and back to the employer under a training plan.
            TEXT],
        ['kind' => 'flow', 'heading' => 'STEM OPT Application Flow', 'body' => <<<'TEXT'
            1. **Candidate:** Works with the DSO. The candidate requests STEM OPT through the school.
            2. **DSO:** Reviews the I-983. The signed training plan is checked for completeness and accuracy.
            3. **DSO:** Makes the STEM OPT recommendation in SEVIS. The recommendation date starts the 60-day filing window.
            4. **DSO:** Issues the STEM OPT I-20. The new I-20 shows the STEM OPT recommendation.
            5. **Candidate:** Files Form I-765 for STEM OPT. Before the current OPT EAD expires, and within 60 days of the recommendation.
            6. **USCIS:** Reviews the STEM OPT application. USCIS checks the application and evidence.
            7. **USCIS:** Approves, sends an RFE, or denies. An RFE asks for more evidence.
            8. **USCIS:** Issues the STEM OPT EAD. The new card shows the 24-month extension dates.
            9. **Candidate:** Verifies the STEM EAD dates. Name and dates are checked and shared with HR.
            10. **Employer:** STEM OPT employment begins or continues. Under the I-983 training plan.
            TEXT],
        ['kind' => 'note', 'heading' => 'Your Boundary', 'body' => <<<'TEXT'
            Recruiters explain the process at an awareness level only. The candidate's DSO, HR and, where needed, an immigration attorney give the answers.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Candidate Works With the DSO', 'body' => <<<'TEXT'
            The candidate starts STEM OPT with the DSO, supported by HR.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            - The candidate asks the DSO about STEM OPT and follows the school's request process.
            - The candidate submits the signed I-983 and any school forms.
            - Recruiters encourage early contact with the DSO but never contact the DSO themselves.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            A consultant asks what to do first for STEM OPT. You say: "Please contact your DSO about their STEM OPT process, and let HR know so they can start the I-983 with you."
            TEXT],
        ['kind' => 'topic', 'heading' => 'DSO Reviews the I-983', 'body' => <<<'TEXT'
            The DSO reviews the plan; corrections go through HR and the candidate.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            - The DSO checks the I-983 is complete, signed and consistent with the student's degree.
            - The DSO may ask for corrections, which the student and employer make together.
            - Corrections go through HR. Recruiters never edit the plan.
            TEXT],
        ['kind' => 'topic', 'heading' => 'DSO Makes the STEM OPT Recommendation in SEVIS', 'body' => <<<'TEXT'
            The SEVIS recommendation starts the filing window. It is not approval.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            - When satisfied, the DSO recommends the STEM OPT extension in SEVIS.
            - The student must generally file the I-765 within 60 days of this recommendation.
            - The recommendation is not approval. USCIS still decides.
            TEXT],
        ['kind' => 'topic', 'heading' => 'DSO Issues the STEM OPT I-20', 'body' => <<<'TEXT'
            The STEM OPT I-20 records the DSO's recommendation.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            - After recommending, the DSO issues an updated I-20 showing the STEM OPT recommendation.
            - The student includes it with the I-765 application.
            - HR may later request a copy through the secure document link.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Candidate Files Form I-765 for STEM OPT', 'body' => <<<'TEXT'
            The candidate files the STEM I-765 on time. Pending-application questions go to the DSO and HR.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            - The student files Form I-765 for the STEM OPT extension with USCIS.
            - It can generally be filed up to 90 days before the current OPT EAD expires, and within 60 days of the DSO recommendation.
            - The application includes the employer's E-Verify company ID, provided by HR.
            - The candidate, or an attorney, prepares and files it. Recruiters never do.
            TEXT],
        ['kind' => 'note', 'heading' => 'Pending Applications', 'body' => <<<'TEXT'
            Questions about working while a STEM OPT application is pending go to the DSO and HR. Recruiters do not answer them.
            TEXT],
        ['kind' => 'topic', 'heading' => 'USCIS Reviews the STEM OPT Application', 'body' => <<<'TEXT'
            USCIS decides in its own time. Track, record and wait.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            - USCIS reviews the application, the I-20 and supporting evidence.
            - Processing times vary; the candidate tracks the case with the receipt number.
            - The company cannot speed up or predict the decision.
            TEXT],
        ['kind' => 'topic', 'heading' => 'STEM OPT Approval, RFE or Denial', 'body' => <<<'TEXT'
            Approval brings the STEM EAD. An RFE or denial goes straight to HR.
            TEXT],
        ['kind' => 'reference', 'heading' => 'Possible Outcomes', 'body' => <<<'TEXT'
            | Outcome | What it means | What the recruiter does |
            | --- | --- | --- |
            | Approval | The 24-month extension is granted | Records the new EAD dates and informs HR |
            | RFE | USCIS needs more evidence | Informs HR; the candidate works with the DSO or attorney |
            | Denial | The extension is not granted | Informs HR immediately; HR decides next steps |
            TEXT],
        ['kind' => 'topic', 'heading' => 'The STEM OPT EAD', 'body' => <<<'TEXT'
            The STEM OPT EAD shows the extension dates. HR updates the records.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            - On approval, USCIS issues a new EAD for the STEM OPT extension.
            - It shows the new validity dates for the 24-month period.
            - HR updates employment records and Form I-9 as required.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Candidate Verifies the STEM EAD Dates', 'body' => <<<'TEXT'
            Record the STEM EAD dates exactly and track every checkpoint from them.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            - The candidate checks the name and dates on the new card.
            - The recruiter records the dates exactly, and HR is informed.
            - The STEM OPT end date and the 6, 12, 18 and 24-month checkpoints are tracked from here.
            TEXT],
        ['kind' => 'part', 'heading' => 'Part 3 — STEM OPT Employment, Evaluations & Reporting', 'body' => <<<'TEXT'
            Understand what STEM OPT employment requires day to day: supervision, training in practice, tasks and timesheets, the 6, 12, 18 and 24-month checkpoints, employer changes and reporting.
            TEXT],
        ['kind' => 'flow', 'heading' => 'Part 3 Map', 'body' => <<<'TEXT'
            1. STEM OPT Employment at a Glance [[STEM OPT Employment at a Glance]]
            2. Training Objectives in Practice [[Training Objectives in Practice]]
            3. Project Assignment During STEM OPT [[Project Assignment During STEM OPT]]
            4. Technical Training [[Technical Training]]
            5. Weekly Tasks [[Weekly Tasks]]
            6. Timesheets During STEM OPT [[Timesheets During STEM OPT]]
            7. Performance Tracking [[Performance Tracking]]
            8. Validations & Evaluations: 6, 12, 18 and 24 Months [[Validations & Evaluations: 6, 12, 18 and 24 Months]]
            9. Employer Changes During STEM OPT [[Employer Changes During STEM OPT]]
            10. Termination and Change Reporting [[Termination and Change Reporting]]
            11. Maintaining Documentation [[Maintaining Documentation]]
            TEXT],
        ['kind' => 'topic', 'heading' => 'STEM OPT Employment at a Glance', 'body' => <<<'TEXT'
            STEM OPT employment follows the training plan from day one.
            STEM OPT is a continuous cycle of training, tracking, validation and evaluation.
            TEXT],
        ['kind' => 'flow', 'heading' => 'STEM OPT Employment & Compliance Flow', 'body' => <<<'TEXT'
            1. **Employer:** Starts training under the I-983. Real, supervised work tied to the STEM degree.
            2. **Project Manager:** Assigns weekly tasks. Tasks follow the training objectives.
            3. **Candidate:** Submits timesheets. Hours actually worked, at least 20 a week.
            4. **Project Manager:** Tracks performance. Progress is reviewed against the learning goals.
            5. **Candidate:** Completes the 6-month validation. Confirms details with the DSO.
            6. **Candidate:** Completes the 12-month self-evaluation. Signed by the employer and submitted to the DSO.
            7. **Candidate:** Completes the 18-month validation. Confirms details with the DSO again.
            8. **Candidate:** Completes the 24-month final evaluation. Signed by the employer and submitted to the DSO.
            9. **HR:** Reports material changes and terminations. A modified I-983 or a termination report, on time.
            10. **HR:** Maintains documentation. Plans, evaluations, timesheets and records kept up to date.
            TEXT],
        ['kind' => 'content', 'heading' => 'When STEM OPT Employment Begins or Continues', 'body' => <<<'TEXT'
            - STEM OPT employment continues with the employer named on the I-983.
            - Training follows the plan; supervision and evaluations happen as described.
            - Any material change to the role is reported so the I-983 can be updated.
            TEXT],
        ['kind' => 'note', 'heading' => 'Your Boundary', 'body' => <<<'TEXT'
            Recruiters support the consultant and pass changes to HR. HR manages the training plan.
            TEXT],
        ['kind' => 'note', 'heading' => 'Your Boundary', 'body' => <<<'TEXT'
            Recruiters explain the process at an awareness level only. The candidate's DSO, HR and, where needed, an immigration attorney give the answers.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Training Objectives in Practice', 'body' => <<<'TEXT'
            The training plan is a working document, not a one-time form.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            - The training objectives on the I-983 guide the consultant's tasks and learning.
            - Work should build toward the goals described in the plan.
            - If the work drifts away from the plan, the plan may need to be modified.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Project Assignment During STEM OPT', 'body' => <<<'TEXT'
            New projects for STEM OPT consultants are checked against the training plan first.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            - Projects must match the I-983 training objectives and the STEM degree.
            - A new project with different duties may be a material change that needs a modified I-983.
            - The project manager and HR confirm before a new assignment begins.
            TEXT],
        ['kind' => 'note', 'heading' => 'Escalation', 'body' => <<<'TEXT'
            Any new project for a STEM OPT consultant goes to HR before the consultant starts.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Technical Training', 'body' => <<<'TEXT'
            Technical training follows the methods in the plan and is recorded.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            - Technical training can include mentoring, code reviews, internal courses and hands-on project work.
            - The training should match the methods described in the I-983.
            - Records of training support the evaluations.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Weekly Tasks', 'body' => <<<'TEXT'
            Weekly tasks turn the training plan into daily practice.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            - The project manager sets weekly tasks that build the skills in the plan.
            - Tasks are tracked in the project's normal tools.
            - Weekly tasks are evidence that the training is happening.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Timesheets During STEM OPT', 'body' => <<<'TEXT'
            Timesheets show real hours worked, at least 20 a week.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            - Timesheets record real hours: at least 20 a week for STEM OPT.
            - The project manager approves them, and payroll uses them.
            - Recruiters may remind consultants to submit on time, but never fill them in.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Performance Tracking', 'body' => <<<'TEXT'
            Track progress against the goals so evaluations are accurate.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            - The project manager reviews progress against the learning goals.
            - Notes from reviews support the 12-month and 24-month evaluations.
            - Concerns about performance go to the project manager and HR.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Validations & Evaluations: 6, 12, 18 and 24 Months', 'body' => <<<'TEXT'
            Validations at 6 and 18 months, evaluations at 12 and 24 months.
            Evaluations show real progress against the plan.
            TEXT],
        ['kind' => 'reference', 'heading' => 'Checkpoints', 'body' => <<<'TEXT'
            | Month | Checkpoint | Owner |
            | --- | --- | --- |
            | 6 | Validation reporting | Candidate, with the DSO |
            | 12 | Self-evaluation on the I-983 | Candidate, signed by the employer |
            | 18 | Validation reporting | Candidate, with the DSO |
            | 24 | Final evaluation on the I-983 | Candidate, signed by the employer |
            TEXT],
        ['kind' => 'content', 'heading' => 'The 6-Month Validation', 'body' => <<<'TEXT'
            - Every six months during STEM OPT, the student confirms their information with the DSO.
            - This includes name, address, employer details and employment status.
            - The first validation is due at six months.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            Five months into STEM OPT, you remind the consultant that the 6-month validation is coming and suggest he checks the DSO's instructions.
            TEXT],
        ['kind' => 'content', 'heading' => 'The 12-Month Self-Evaluation', 'body' => <<<'TEXT'
            - At 12 months, the student completes a self-evaluation in section 7 of the I-983, Evaluation of Student Progress.
            - The employer reviews and signs it.
            - The student submits it to the DSO.
            TEXT],
        ['kind' => 'content', 'heading' => 'The 18-Month Validation', 'body' => <<<'TEXT'
            - At 18 months, the student again confirms their information with the DSO.
            - The same details as the 6-month validation are checked.
            - Missed validations can cause problems with the student's record.
            TEXT],
        ['kind' => 'content', 'heading' => 'The 24-Month Final Evaluation', 'body' => <<<'TEXT'
            - At the end of the 24 months, or when the training ends early, the student completes a final evaluation.
            - The employer reviews and signs it, and the student submits it to the DSO.
            - HR plans the end of STEM OPT with the consultant well in advance.
            - The final evaluation closes the training plan.
            TEXT],
        ['kind' => 'note', 'heading' => 'Your Boundary', 'body' => <<<'TEXT'
            HR tracks these dates and coordinates the employer's signature. Recruiters remind and pass on; they never complete reports or evaluations.
            TEXT],
        ['kind' => 'note', 'heading' => 'Your Boundary', 'body' => <<<'TEXT'
            What comes after STEM OPT is decided by HR, attorneys and the candidate. Recruiters do not advise on it.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Employer Changes During STEM OPT', 'body' => <<<'TEXT'
            Changes during STEM OPT need a new or modified I-983. Pass them to HR straight away.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            - A new employer must complete a new I-983, generally submitted to the DSO within 10 days of the new employment.
            - A material change with the same employer, such as duties, hours, pay or supervisor, needs a modified I-983.
            - The candidate reports the change to the DSO.
            TEXT],
        ['kind' => 'note', 'heading' => 'Escalation', 'body' => <<<'TEXT'
            Any change of employer, role, hours, pay, supervisor or location goes to HR the same day.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Termination and Change Reporting', 'body' => <<<'TEXT'
            Terminations are reported quickly by the employer. Tell HR the same day.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            - If STEM OPT employment ends, the employer must report it to the DSO, generally within five business days.
            - The student also reports the end of employment.
            - Unemployment during OPT and STEM OPT combined is generally limited to 150 days.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            A consultant tells you he is resigning. You tell HR the same day so the termination can be reported on time.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Maintaining Documentation', 'body' => <<<'TEXT'
            Documentation proves the training happened. Keep it complete and secure.
            TEXT],
        ['kind' => 'reference', 'heading' => 'Documents HR Keeps', 'body' => <<<'TEXT'
            - Form I-983 and any modifications.
            - Signed evaluations.
            - Timesheets and project records.
            - EAD copy and Form I-9.
            TEXT],
        ['kind' => 'content', 'heading' => 'What the Recruiter Does', 'body' => <<<'TEXT'
            - Collects documents only through the secure link.
            - Records receipt in the company system.
            - Never keeps copies on personal devices.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Checklist & Common Mistakes', 'body' => <<<'TEXT'
            Every topic's checklist and common mistakes in one place. Use it as a quick review before you work with a real candidate.
            TEXT],
        ['kind' => 'reference', 'heading' => 'Recruiter Checklist', 'body' => <<<'TEXT'
            **Candidate Verifies the STEM EAD Dates**
            - New EAD dates recorded.
            - HR informed.
            - Checkpoint dates added to the follow-up calendar.
            TEXT],
        ['kind' => 'mistakes', 'heading' => 'Common Mistakes', 'body' => <<<'TEXT'
            **The STEM Degree Requirement**
            - Assuming a degree qualifies because it sounds technical.
            - Telling a candidate they qualify before the DSO confirms.
            **A Real Employment Relationship**
            - Treating STEM OPT as a paperwork exercise.
            - Promising a STEM OPT placement without HR and compliance review.
            **The Hours Requirement**
            - Offering part-time hours below 20 a week.
            - Suggesting unpaid work until a project starts.
            **Employer and Candidate Complete the I-983 Together**
            - One party filling in the other's certification.
            - Copying a generic plan that does not describe the real role.
            **Supervision and Oversight**
            - Naming a supervisor who never works with the student.
            - No regular check-ins between the student and supervisor.
            **Employer Signature and DSO Submission**
            - Sending an unsigned or incomplete plan.
            - A recruiter signing on behalf of the employer.
            **DSO Reviews the I-983**
            - Editing the I-983 to speed up a DSO review.
            - Telling a candidate the DSO's questions do not matter.
            **USCIS Reviews the STEM OPT Application**
            - Promising a decision date to a client.
            - Telling a candidate the company can contact USCIS for them.
            **Training Objectives in Practice**
            - Ignoring the plan once STEM OPT is approved.
            - Assigning unrelated work without telling HR.
            TEXT],
        ['kind' => 'takeaway', 'heading' => 'Key Takeaway', 'body' => <<<'TEXT'
            - STEM OPT needs a qualifying degree, an E-Verify employer, a real paid job of at least 20 hours a week related to the degree, and a genuine training plan. Start planning early, always with the DSO.
            - The I-983 is a real training plan completed by the employer and the candidate together, reviewed by the DSO. USCIS decides the STEM OPT application; the recruiter tracks progress and records the new EAD dates.
            - STEM OPT employment must follow the training plan, with real supervision, evaluations and validations on time, and every change reported through HR and the DSO.
            TEXT],
    ],
];
