<?php

/*
 * Combined lesson "Requirement to Placement Lifecycle". One topic per merged lesson:
 * Requirement, Submission, Interview, Placement, Bench.
 */

return [
    'title' => 'Requirement to Placement Lifecycle',
    'from' => 'Requirement',
    'compliance' => true,
    'review' => 'Includes topics that were awaiting compliance review: Requirement, Submission, Interview, Placement, Bench.',
    'en' => [
        ['kind' => 'objective', 'heading' => 'Learning Objective', 'body' => <<<'TEXT'
            Follow a requirement from the moment it arrives to placement and beyond: responding to the requirement, submitting candidates correctly, preparing them for interviews, managing the placement, and supporting consultants on the bench.
            TEXT],
        ['kind' => 'flow', 'heading' => 'Lesson Map', 'body' => <<<'TEXT'
            1. Requirement [[Requirement]]
            2. Submission [[Submission]]
            3. Interview [[Interview]]
            4. Placement [[Placement]]
            5. Bench [[Bench]]
            6. Checklist & Common Mistakes [[Checklist & Common Mistakes]]
            TEXT],
        ['kind' => 'topic', 'heading' => 'Requirement', 'body' => <<<'TEXT'
            A requirement is the starting point of every placement. Read it fully, clarify doubts, and match candidates only to what it really asks for.
            How to analyse and prioritise a requirement, and how to check its work authorization rules, is taught in **Job Requirement Analysis → Reading & Analysing a Requirement**.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            A requirement, often called a req or a job requirement, is a request from a client or vendor to fill a position. It is usually shared as a job description with extra staffing details.
            TEXT],
        ['kind' => 'content', 'heading' => 'Information a good requirement contains', 'body' => <<<'TEXT'
            - Job title and level.
            - Client or industry.
            - Location and work mode, meaning onsite, hybrid or remote.
            - Duration, for example twelve months with possible extension.
            - Engagement type, such as contract, contract-to-hire or full-time.
            - Rate or salary range, if shared.
            - Start date.
            - Required skills and preferred skills.
            - Years of experience.
            - Responsibilities.
            - Work authorization requirements or restrictions.
            - Interview process and submission deadline.
            TEXT],
        ['kind' => 'content', 'heading' => 'From the company training material', 'body' => <<<'TEXT'
            The US MNC Staffing document says that understanding the requirement is the first and most important step in recruiting. It teaches you to study the requirement two or three times and note what the client needs: the platform or technology, the domain, the project location, the duration, any must-have or preferred conditions, the type of project, such as full-time, contract or long term, and the visa statuses the client accepts, such as Green Card, OPT, EAD, H-1B or U.S. citizen.
            The Job Description Analysis training lists the mandatory details to capture before sourcing: location, duration, rate, skills, dates, the job description, the client and the roles and responsibilities. The Job Requirement Analysis course teaches each of these.
            TEXT],
        ['kind' => 'content', 'heading' => 'Responding to a requirement', 'body' => <<<'TEXT'
            - Read the full requirement carefully.
            - Note the must-have skills, location, work mode and authorization rules.
            - Ask the vendor or your lead about anything unclear.
            - Search your database and sources for matching candidates.
            - Screen candidates before submitting.
            - Submit within the deadline, following the vendor's format.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            A requirement reads: Java developer, Charlotte, NC, hybrid, twelve months, Spring Boot and microservices required, Kafka preferred, start in two weeks. You note Java, Spring Boot and microservices as must-haves, Kafka as a bonus, and confirm hybrid attendance with candidates before submitting.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Submission', 'body' => <<<'TEXT'
            Every submission represents your company. Make it accurate, consented and complete.
            The honesty rules for submissions are taught here in full; later courses point back to this tab.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            A submission means formally presenting a candidate to a vendor or client for a specific requirement. It usually includes the candidate's resume and a short summary, sometimes called a skill matrix or candidate summary.
            TEXT],
        ['kind' => 'content', 'heading' => 'Before submitting', 'body' => <<<'TEXT'
            - The candidate has been screened.
            - The candidate is interested in this specific role.
            - The candidate has agreed to be submitted, usually in writing as a right to represent.
            - You have confirmed the candidate has not already been submitted to the same client for the same role.
            - You have confirmed location, work mode, availability and the rate or salary expectation.
            - The work authorization information matches the requirement, as recorded through approved questions.
            TEXT],
        ['kind' => 'content', 'heading' => 'What a good submission includes', 'body' => <<<'TEXT'
            - An updated resume, unchanged except for formatting allowed by your company.
            - A clear summary of how the candidate matches the must-have skills.
            - Current location and willingness to relocate if required.
            - Availability to start and to interview.
            - Any other details the vendor requires.
            TEXT],
        ['kind' => 'content', 'heading' => 'From the company training material: the submission package', 'body' => <<<'TEXT'
            The US MNC Staffing document describes exactly what to collect before a submission.
            If the consultant will work on your company's W2, discuss the rate with them, then send the requirement, the submission template and the right to represent, which the document calls the R2R.
            If the consultant has an employer, call the employer, discuss and negotiate the rate, send a rate confirmation, and send the non-disclosure agreement, called the NDA, or the non-compete agreement, called the NCA, as your process requires.
            Collect the completed submission template, the updated resume, the signed right to represent, the rate confirmation and, where there is an employer, the employer details and signed NDA or NCA.
            Attach everything to one email with the requirement and send it to your BDM or resource manager, who submits to the client or vendor.
            The document notes that rate negotiation should keep a minimum margin for the company. Company-specific process: margin rules are set by management. Never discuss margins with a consultant or an employer.
            TEXT],
        ['kind' => 'content', 'heading' => 'Honesty in submissions', 'body' => <<<'TEXT'
            - Never add skills, projects or experience that the candidate does not have.
            - Never change dates, titles or education on a resume.
            - Never misrepresent work authorization.
            - Inaccurate submissions damage your company's reputation, can lead to terminated relationships, and can harm the candidate.
            TEXT],
        ['kind' => 'content', 'heading' => 'After submitting', 'body' => <<<'TEXT'
            - Record the submission details, including date, client, vendor and role.
            - Inform the candidate that they were submitted.
            - Follow up with the vendor on feedback.
            - Prepare the candidate for any interview.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            You have a strong data analyst candidate for a requirement in Chicago. Before submitting, you confirm she is willing to work hybrid in Chicago, has not applied to this client before, and agrees in writing to be represented. You then submit her resume and a summary highlighting SQL, Python and Tableau, which are the must-have skills.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Interview', 'body' => <<<'TEXT'
            Interviews decide placements. Clear scheduling, honest preparation and quick follow-up give your candidate the best fair chance.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            After a submission, the client may choose to interview the candidate. IT interviews often have several rounds.
            TEXT],
        ['kind' => 'content', 'heading' => 'Common interview types', 'body' => <<<'TEXT'
            - A phone screen is a short call to check basic fit.
            - A video interview uses tools such as Microsoft Teams, Zoom or Webex, usually with the camera on.
            - A technical interview tests skills through questions, coding or problem solving.
            - A managerial or behavioural interview checks communication, teamwork and fit.
            - An in-person interview may be required for onsite or hybrid roles.
            TEXT],
        ['kind' => 'content', 'heading' => 'Scheduling', 'body' => <<<'TEXT'
            - Confirm the interview date and time with the candidate in their own time zone and in the client's time zone.
            - Share the meeting link and the interviewer names if allowed.
            - Confirm again the day before and a few hours before.
            TEXT],
        ['kind' => 'content', 'heading' => 'Preparing the candidate', 'body' => <<<'TEXT'
            - Review the job description and must-have skills with the candidate.
            - Remind them to test their internet, camera and microphone.
            - Advise a quiet, well-lit, professional setting.
            - Encourage them to prepare examples from their projects.
            - Remind them to join a few minutes early.
            - Remind them to answer honestly and to say I am not sure when they do not know something.
            TEXT],
        ['kind' => 'content', 'heading' => 'Interview integrity', 'body' => <<<'TEXT'
            Candidates must attend their own interviews and answer on their own. Any form of proxy interviewing, where someone else attends or answers for the candidate, is dishonest and can end relationships with clients. Report any suspicion through your company's process.
            Many clients now require cameras on for this reason.
            TEXT],
        ['kind' => 'content', 'heading' => 'After the interview', 'body' => <<<'TEXT'
            - Call the candidate to hear how it went.
            - Share feedback from the vendor or client when you receive it.
            - Keep the candidate informed if a decision is delayed.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            A client schedules a technical video interview for 1 PM Eastern on Tuesday. The candidate is in Austin, Texas, which is Central Time. You confirm 12 PM Central with the candidate, send the link, remind him to keep his camera on, and call him after the interview for feedback.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Placement', 'body' => <<<'TEXT'
            A placement is complete only when the consultant starts correctly. Hand off accurately, watch for risks, and stay involved until day one.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            A placement happens when a client selects a candidate and the candidate begins the assignment or job. For a recruiter, the placement is the goal, but it is only successful if the consultant starts on time with correct documentation and payroll.
            TEXT],
        ['kind' => 'content', 'heading' => 'Steps after selection', 'body' => <<<'TEXT'
            - The client or vendor confirms selection.
            - Rate, start date, location and work mode are confirmed.
            - An offer or confirmation is shared with the candidate according to your company process.
            - The candidate accepts.
            - Onboarding begins, including documentation, background checks if required, and employment verification steps handled by HR.
            - Payroll setup is completed.
            - The consultant starts and is supported in the first days.
            TEXT],
        ['kind' => 'content', 'heading' => 'Recruiter responsibilities at this stage', 'body' => <<<'TEXT'
            Communicate the selection quickly and accurately.
            Confirm the candidate's acceptance.
            Hand off complete information to HR and payroll: legal name, contact details, client, vendor, work location and state, start date, rate or salary, work authorization dates and any special notes.
            Stay in touch until the consultant has started.
            TEXT],
        ['kind' => 'content', 'heading' => 'Risks that can stop a placement', 'body' => <<<'TEXT'
            - Work authorization dates that do not cover the start date.
            - Missing documents.
            - Candidate backing out because of a competing offer.
            - Background check delays.
            - Unclear work location, which affects payroll and taxes.
            TEXT],
        ['kind' => 'content', 'heading' => 'Early warning', 'body' => <<<'TEXT'
            If you learn of any risk, inform your lead immediately. Early escalation often saves a placement.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            A candidate is selected for a role starting in ten days. Her EAD starts in eight days. You inform HR immediately, confirm all details, and keep in contact with her daily until her first day.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Bench', 'body' => <<<'TEXT'
            The bench is the time between projects. Accurate handoffs and honest communication help consultants return to work quickly.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            In U.S. IT staffing, a consultant is on the bench when they are available and between projects. A company with consultants on the bench actively markets them to vendors and clients to find their next project.
            Bench sales is the function of marketing these consultants. In your company it is a separate team from OPT recruiting.
            TEXT],
        ['kind' => 'content', 'heading' => 'Why bench matters', 'body' => <<<'TEXT'
            Consultants on the bench want to return to work quickly.
            OPT and STEM OPT consultants also have limits on unemployment, which can make bench time stressful. Never advise on these limits. Refer the consultant to their DSO and HR.
            Bench policies, including whether and how anyone is paid during bench time, are company-specific and may involve legal requirements. HR and management decide these. Recruiters do not discuss or promise bench pay.
            TEXT],
        ['kind' => 'content', 'heading' => 'How OPT recruiting and bench sales connect', 'body' => <<<'TEXT'
            OPT recruiters find and prepare candidates. In some companies, candidates who join are then marketed to clients by the bench sales team.
            TEXT],
        ['kind' => 'content', 'heading' => 'From the company training material', 'body' => <<<'TEXT'
            The US MNC Staffing document defines sales or marketing as marketing the bench consultants, and describes W2 consultants as the company's own bench. The OPT Recruiter role description adds the OPT recruiter's part: ask eligible candidates whether they want their professional profile marketed for suitable IT opportunities, explain the bench and marketing process, add interested candidates to the bench pipeline after the required onboarding, keep their resumes and information updated, and coordinate with the Bench Sales team. The recruiter also tracks profile submissions, interviews, client responses, requirements and placement progress for each bench candidate.
            Good handoffs between the two teams matter: accurate resumes, skills, availability, location preferences and work authorization dates.
            TEXT],
        ['kind' => 'content', 'heading' => 'Professional conduct', 'body' => <<<'TEXT'
            - Never misrepresent a bench consultant's experience or skills to make them easier to place.
            - Never submit the same consultant to the same client through different routes.
            - Keep the consultant informed about submissions and interviews.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            A consultant's project ends next month. Your team lead asks you to prepare a clear summary of his skills, latest project, location preference and EAD end date for the bench sales team. You confirm each detail with him before handing it over.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Checklist & Common Mistakes', 'body' => <<<'TEXT'
            Every topic's checklist and common mistakes in one place. Use it as a quick review before you work on a real requirement.
            TEXT],
        ['kind' => 'reference', 'heading' => 'Recruiter Checklist', 'body' => <<<'TEXT'
            **Requirement**
            - Capture every key detail of the requirement in your notes.
            - Clarify unclear points before sourcing.
            - Respect work authorization restrictions.
            - Track the submission deadline.
            **Submission**
            - Screen, confirm interest and get consent before every submission.
            - Check for duplicates.
            - Keep the resume truthful.
            - Record and follow up.
            **Interview**
            - Confirm times in both time zones.
            - Prepare the candidate on the role and technology.
            - Send reminders.
            - Debrief after every interview.
            **Placement**
            - Confirm all offer details in writing.
            - Complete the handoff to HR and payroll on time.
            - Check work authorization dates against the start date.
            - Stay in contact until day one.
            **Bench**
            - Understand your company's bench process and who owns it.
            - Hand off complete and accurate profiles.
            - Refer bench pay and status questions to HR.
            TEXT],
        ['kind' => 'mistakes', 'heading' => 'Common Mistakes', 'body' => <<<'TEXT'
            **Requirement**
            - Skimming the requirement and missing restrictions.
            - Submitting candidates who do not match the must-have skills.
            - Missing the deadline.
            **Submission**
            - Submitting without consent.
            - Editing a resume to add missing skills.
            - Not informing the candidate that they were submitted.
            **Interview**
            - Wrong time zone in the invitation.
            - No preparation call.
            - Not following up after the interview.
            **Placement**
            - Celebrating too early and forgetting the handoff.
            - Leaving the work state blank.
            - Not checking the EAD start date against the job start date.
            **Bench**
            - Promising a consultant how long they will be on the bench.
            - Discussing bench pay without HR guidance.
            - Passing outdated resumes to the bench sales team.
            TEXT],
        ['kind' => 'note', 'heading' => 'Sources & Review', 'body' => <<<'TEXT'
            **Interview:** The company documents mention interviews in several places: the US MNC Staffing document says to follow up for feedback after submission, the Day 7 questionnaire asks whether the candidate is comfortable with a live webcam coding assessment, and the Java and AWS requirement in the Day 5 training includes a video interview. They do not describe interview preparation in detail, so the rest of this topic needs review and approval by the training manager.
            **Placement:** The company documents describe placement as the goal of the process: the OPT calling script says that after training the company prepares the resume and places the candidate with direct clients, and the business plan lists interviews and placement opportunities after bench enrollment and profile marketing. No placement is guaranteed. The rest of this topic is based on general staffing practice and needs review and approval by the training manager.
            TEXT],
        ['kind' => 'takeaway', 'heading' => 'Key Takeaway', 'body' => <<<'TEXT'
            Every placement follows the same path: read the requirement fully, submit only screened and consenting candidates with truthful resumes, prepare them for interviews, and stay in touch through placement and the bench.
            TEXT],
    ],
];
