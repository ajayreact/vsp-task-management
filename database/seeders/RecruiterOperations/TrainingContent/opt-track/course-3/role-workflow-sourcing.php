<?php

/*
 * Combined lesson "OPT Recruiter Role, Workflow & Candidate Sourcing": the
 * former lessons "OPT Recruiter Role & Complete Workflow" and "Candidate
 * Sourcing & Lead Generation", their topics kept as tabs with the wording
 * unchanged. Each topic's checklist and common mistakes are collected in the
 * closing tab.
 */

return [
    'title' => 'OPT Recruiter Role, Workflow & Candidate Sourcing',
    'from' => 'OPT Recruiter Role & Complete Workflow',
    'compliance' => true,
    'review' => 'Immigration process content: confirm every rule, date and form against current USCIS, SEVP and DHS Study in the States guidance before publishing. Company-specific process: confirm each step, owner and any wording with management, HR and compliance before publishing. Company-specific process from the diagram: confirm each step, owner and any wording with management, HR and compliance before publishing. Company-specific process, not yet defined: management must confirm the target candidate profile, the sourcing channels and job boards the company uses and pays for, daily and weekly sourcing targets, the CRM fields and lead statuses, duplicate-lead rules, and the referral amount and terms before publishing. Legal and compliance: confirm the privacy, consent and do-not-contact rules, and university outreach rules.',
    'en' => [
        ['kind' => 'objective', 'heading' => 'Learning Objective', 'body' => <<<'TEXT'
            Understand the OPT recruiter's role end to end: what you own, what you hand off, who owns each step, and how the work continues after placement.
            Find and qualify OPT and STEM OPT candidates through the approved channels, record every lead correctly in the CRM, and hand qualified leads into the calling process.
            TEXT],
        ['kind' => 'note', 'heading' => 'Where the Calling Lessons Are', 'body' => <<<'TEXT'
            Calling skills are taught once, in the **Calling & Communication** course. Use those lessons for every call:
            - How to Speak with a Consultant
            - Initial Candidate Screening — Complete Screening Script
            - Explaining the Opportunity — Complete Services Guide
            - Candidate Questions & Objections — Complete Response Guide, and Follow-Up & Next Steps — Complete Follow-Up Playbook
            - Complete Mock OPT Recruiter Call
            This course covers the process behind the call.
            TEXT],
        ['kind' => 'flow', 'heading' => 'Lesson Map', 'body' => <<<'TEXT'
            1. Quick Recap: F-1, OPT, DSO and USCIS [[Quick Recap: F-1, OPT, DSO and USCIS]]
            2. The Recruiter's Role in the OPT Process [[The Recruiter's Role in the OPT Process]]
            3. What the Recruiter Can and Cannot Do [[What the Recruiter Can and Cannot Do]]
            4. The Recruiter Internal Workflow [[The Recruiter Internal Workflow]]
            5. Who Owns Each Step [[Who Owns Each Step]]
            6. Handing Off to HR and Compliance [[Handing Off to HR and Compliance]]
            7. Ongoing Follow-Up After Placement [[Ongoing Follow-Up After Placement]]
            8. Sourcing Workflow [[Sourcing Workflow]]
            9. Who We Are Looking For [[Who We Are Looking For]]
            10. Sourcing Channels [[Sourcing Channels]]
            11. Qualifying a Lead [[Qualifying a Lead Before You Call]]
            12. Recording Leads in the CRM [[Recording Leads in the CRM]]
            13. Targets and Reporting [[Daily Targets & Reporting]]
            14. Compliance in Sourcing [[Compliance in Sourcing]]
            15. Checklist & Common Mistakes [[Checklist & Common Mistakes]]
            TEXT],
        ['kind' => 'topic', 'heading' => 'Quick Recap: F-1, OPT, DSO and USCIS', 'body' => <<<'TEXT'
            - **What Is F-1 Status?** F-1 is a student status. Work is the exception, and only with proper authorization.
            - **What Is OPT?** OPT is temporary, field-related work for F-1 students, approved by USCIS after a DSO recommendation.
            - **What Is Post-Completion OPT?** Post-completion OPT follows graduation. Record the dates, and let the DSO advise on timing and unemployment.
            - **Who Is the DSO?** The DSO is the school official who manages the student's SEVIS record. Send status questions there.
            - **What Does the DSO Do?** The DSO recommends and records. USCIS approves. The employer and recruiter never replace the DSO.
            - **What Does USCIS Do?** Only USCIS approves employment authorization and issues the EAD.
            **Full lesson:** Immigration & Work Authorization → U.S. Visa & Immigration Statuses, and Immigration & Work Authorization → Immigration Documents & Systems (DSO, I-765).
            TEXT],
        ['kind' => 'topic', 'heading' => 'The Recruiter\'s Role in the OPT Process', 'body' => <<<'TEXT'
            Recruiters connect candidates with genuine opportunities and accurate information. Immigration decisions belong to the DSO, USCIS and HR.
            TEXT],
        ['kind' => 'content', 'heading' => 'What the Recruiter Does', 'body' => <<<'TEXT'
            - Finds and screens candidates for genuine roles that match their skills and field of study.
            - Records accurate information: status, EAD dates, degree and availability.
            - Passes documents to HR and compliance through approved, secure channels.
            - Keeps candidates informed, and passes changes to HR quickly.
            TEXT],
        ['kind' => 'mistakes', 'heading' => 'What the Recruiter Never Does', 'body' => <<<'TEXT'
            - Gives immigration advice or predicts USCIS decisions.
            - Guarantees OPT, STEM OPT, an EAD or a project.
            - Collects or requests money from candidates.
            - Decides whether a document is acceptable. HR does that.
            TEXT],
        ['kind' => 'note', 'heading' => 'Your Boundary', 'body' => <<<'TEXT'
            Recruiters explain the process at an awareness level only. The candidate's DSO, HR and, where needed, an immigration attorney give the answers.
            TEXT],
        ['kind' => 'topic', 'heading' => 'What the Recruiter Can and Cannot Do', 'body' => <<<'TEXT'
            Recruiters match, record, hand off and inform. Advice, decisions and money are never part of the role.
            TEXT],
        ['kind' => 'reference', 'heading' => 'Can and Cannot', 'body' => <<<'TEXT'
            | The recruiter can | The recruiter cannot |
            | --- | --- |
            | Present genuine roles that match the candidate's field | Guarantee OPT, STEM OPT or an EAD |
            | Record status, dates and availability | Give immigration or legal advice |
            | Collect documents through the secure channel | Decide whether a document is acceptable |
            | Pass questions to HR, the DSO or an attorney | Collect, request or accept money |
            | Keep the candidate informed | Create or change job duties to fit a status |
            TEXT],
        ['kind' => 'topic', 'heading' => 'The Recruiter Internal Workflow', 'body' => <<<'TEXT'
            Each step has one owner. The recruiter sources, screens, hands off and follows up.
            TEXT],
        ['kind' => 'flow', 'heading' => 'Recruiter Internal Workflow', 'body' => <<<'TEXT'
            1. **Recruiter:** Candidate sourcing. Find candidates whose skills and field of study match genuine roles.
            2. **Recruiter:** First contact and screening. Use the approved script; record status, EAD dates, degree and availability.
            3. **Candidate:** Shares documents securely. Through the secure document link only.
            4. **HR:** Reviews documents. Checks completeness and fit with the role.
            5. **Compliance:** Reviews immigration-related risk. Confirms the role, documents and any STEM OPT needs.
            6. **Project Manager:** Technical and project review. Confirms skills, project, duties and supervision.
            7. **HR:** Offer letter and onboarding. Issues the offer, completes Form I-9 and onboarding.
            8. **DSO:** Receives the candidate's employment report. The candidate reports the employer as the school instructs.
            9. **USCIS:** Decides any pending or STEM OPT application. Only USCIS grants employment authorization.
            10. **Finance / Payroll:** Runs payroll. Based on approved timesheets.
            11. **Recruiter:** Ongoing follow-up. Regular check-ins; dates and changes passed to HR.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Who Owns Each Step', 'body' => <<<'TEXT'
            Know the owner, pass the question, and follow up until it is answered.
            TEXT],
        ['kind' => 'reference', 'heading' => 'Owners', 'body' => <<<'TEXT'
            | Owner | Responsible for |
            | --- | --- |
            | Recruiter | Sourcing, screening, accurate records, secure document requests, follow-up |
            | HR | Document review, offer letter, Form I-9, onboarding, I-983 coordination |
            | Compliance | Immigration-related risk review and approval of process wording |
            | Candidate | Applications, DSO contact, reporting, honest information |
            | DSO | Eligibility, SEVIS recommendations, I-983 review, reporting records |
            | USCIS | Deciding the I-765 and issuing the EAD |
            | Project Manager | Project, duties, supervision, timesheet approval |
            | Finance / Payroll | Payroll and payment questions |
            TEXT],
        ['kind' => 'topic', 'heading' => 'Handing Off to HR and Compliance', 'body' => <<<'TEXT'
            A clean handoff saves everyone time and protects the candidate.
            TEXT],
        ['kind' => 'content', 'heading' => 'A Good Handoff', 'body' => <<<'TEXT'
            - Complete, accurate notes in the company system.
            - Documents uploaded through the secure link, with receipt recorded.
            - Open questions listed clearly: anything the candidate asked that you could not answer.
            - The candidate told what happens next and who will contact them.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            You hand over a candidate with notes: "EAD 01 Jul 2026 to 30 Jun 2027, CS master's, available immediately, asked about STEM OPT timing (told to check with DSO and HR)."
            TEXT],
        ['kind' => 'topic', 'heading' => 'Ongoing Follow-Up After Placement', 'body' => <<<'TEXT'
            Follow-up continues for the whole engagement. Dates and changes go to HR early.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Do', 'body' => <<<'TEXT'
            - Check in after the first day, the first week, and regularly after that.
            - Track the EAD end date, project end dates and STEM OPT checkpoints, and alert HR early.
            - Pass every change to HR the same day: project, location, hours, supervisor or employer.
            - Follow up on timesheet and payroll questions with the right team.
            TEXT],
        ['kind' => 'reference', 'heading' => 'Follow-Up Calendar', 'body' => <<<'TEXT'
            | When | Action |
            | --- | --- |
            | Day 1 and week 1 | Check-in call |
            | Monthly | Check-in and timesheet reminder |
            | 5 months before EAD end | Flag to HR |
            | STEM OPT months 6, 12, 18, 24 | Remind about validation or evaluation |
            TEXT],
        ['kind' => 'topic', 'heading' => 'Sourcing Workflow', 'body' => <<<'TEXT'
            Every lead follows the same path: find, qualify, record, call, follow up.
            TEXT],
        ['kind' => 'note', 'heading' => 'Rules for Sourcing', 'body' => <<<'TEXT'
            - Use only the channels and accounts the company approves.
            - Never assume a candidate's status from a profile. Status is confirmed only on the call, with the approved questions.
            - Respect every do-not-contact request immediately, and record it.
            - Never post or send misleading job details, and never promise a job, placement or immigration outcome in any message.
            - Details marked "Management to define" are not yet company process. Do not invent them; ask your lead.
            TEXT],
        ['kind' => 'flow', 'heading' => 'Sourcing Flow', 'body' => <<<'TEXT'
            1. **Recruiter:** Know the Target Profile. Who the company is looking for right now. [[Who We Are Looking For]]
            2. **Recruiter:** Search the Channels. Only the approved channels. [[Sourcing Channels]]
            3. **Recruiter:** Qualify the Lead. Check the profile before you call. [[Qualifying a Lead Before You Call]]
            4. **Recruiter:** Record in the CRM. Every lead, with its source. [[Recording Leads in the CRM]]
            5. **Recruiter:** First Call. Use How to Speak with a Consultant in the Calling & Communication course.
            6. **Recruiter:** Follow Up. Use the Follow-Up & Next Steps playbook.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Who We Are Looking For', 'body' => <<<'TEXT'
            Know the profile before you search, so every lead you record is worth a call.
            TEXT],
        ['kind' => 'content', 'heading' => 'Typical Profile', 'body' => <<<'TEXT'
            - F-1 students and recent graduates who are on OPT or STEM OPT, or whose OPT is pending.
            - Candidates in IT and related technologies who need one of our services: offer letter, payroll, training, resume building, job and interview support, or C2C marketing.
            - Candidates open to the training and project locations the company offers.
            TEXT],
        ['kind' => 'note', 'heading' => 'Management to Define', 'body' => <<<'TEXT'
            - The current target profile: degrees, technologies and graduation window.
            - Any locations or technologies to prioritise this month.
            - Profiles the company does not work with.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Sourcing Channels', 'body' => <<<'TEXT'
            Use the approved channels, and record which channel every lead came from.
            TEXT],
        ['kind' => 'reference', 'heading' => 'Channels', 'body' => <<<'TEXT'
            | Channel | How it is used | Learn the technique in |
            | --- | --- | --- |
            | LinkedIn | Search profiles, then connect or message with approved wording | Sourcing & Resume Screening → LinkedIn; University & Candidate Outreach → LinkedIn Outreach |
            | Job boards | Search resumes and respond to applicants on the boards the company uses | Sourcing & Resume Screening → Dice, Job Boards |
            | University networks | Career centers, international student offices and STEM programs, following each university's rules | University & Candidate Outreach → University Research, Career Centers, University Recruitment Policies |
            | Referrals | Ask every candidate for friends who are looking | University & Candidate Outreach → Referrals; Follow-Up & Next Steps → Referral |
            | Internal database | Re-contact past candidates whose situation may have changed | Sourcing & Resume Screening → Internal Database |
            TEXT],
        ['kind' => 'note', 'heading' => 'Management to Define', 'body' => <<<'TEXT'
            - Which job boards and paid accounts the company uses, and who may use them.
            - Approved message templates for LinkedIn, email and job boards.
            - The referral amount and terms. The calling script gives $750, pending confirmation.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Qualifying a Lead Before You Call', 'body' => <<<'TEXT'
            A two-minute profile check saves a wasted call and avoids wrong assumptions.
            TEXT],
        ['kind' => 'reference', 'heading' => 'Profile Check', 'body' => <<<'TEXT'
            - Education: degree, field and graduation date, as listed.
            - Technology: the main skills and tools.
            - Location: the current city and state, as listed.
            - Experience: internships, projects and roles.
            - Contact details: a working phone number or email.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Recording Leads in the CRM', 'body' => <<<'TEXT'
            If it is not in the CRM, it did not happen. Record every lead before you call.
            TEXT],
        ['kind' => 'reference', 'heading' => 'What to Record', 'body' => <<<'TEXT'
            - Name, phone and email, exactly as listed.
            - Source: the channel, and the referrer's name for referrals.
            - Education, technology and location from the profile check.
            - Status of the lead: new, contacted, interested, follow-up, not interested or do not contact.
            - The date of every contact attempt.
            TEXT],
        ['kind' => 'note', 'heading' => 'Management to Define', 'body' => <<<'TEXT'
            - The exact CRM fields and lead statuses to use.
            - How to check for duplicate leads, and who owns a lead another recruiter already contacted.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Daily Targets & Reporting', 'body' => <<<'TEXT'
            Targets are set by management. Track your own numbers every day so your lead can support you.
            TEXT],
        ['kind' => 'content', 'heading' => 'What to Track', 'body' => <<<'TEXT'
            - New leads recorded.
            - Calls made and conversations held.
            - Interested candidates and sales manager calls booked.
            - Resumes received and referrals collected.
            TEXT],
        ['kind' => 'note', 'heading' => 'Management to Define', 'body' => <<<'TEXT'
            - Daily and weekly targets for each number above.
            - How and when results are reported, and to whom.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Compliance in Sourcing', 'body' => <<<'TEXT'
            Source honestly, respect privacy, and follow every platform's and university's rules.
            TEXT],
        ['kind' => 'content', 'heading' => 'Rules', 'body' => <<<'TEXT'
            - Follow the terms of every platform you use. Do not copy or export data in ways the platform does not allow.
            - Follow each university's recruitment policy. Contact career centers and student offices only through their official process.
            - Store candidate information only in the CRM and approved systems.
            - Respect opt-outs at once, and record them so no one contacts the person again.
            - Never describe a service as a guaranteed job, placement or immigration result.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Checklist & Common Mistakes', 'body' => <<<'TEXT'
            Every topic's checklist and common mistakes in one place. Use it as a quick review before you work with a real candidate.
            TEXT],
        ['kind' => 'mistakes', 'heading' => 'Common Mistakes', 'body' => <<<'TEXT'
            **Who Owns Each Step**
            - Taking on another owner's decision to save time.
            - Passing a question to the wrong team and not following up.
            **Qualifying a Lead Before You Call**
            - Assuming someone is on OPT because of their graduation date or university.
            - Skipping the profile check and calling with no context.
            - Recording a lead without its source.
            TEXT],
        ['kind' => 'takeaway', 'heading' => 'Key Takeaway', 'body' => <<<'TEXT'
            - Own the recruiter steps fully, hand off immigration and employment decisions to their owners on time, and keep following up after placement.
            - Source only through approved channels, check every profile before you call, record every lead with its source, and never assume status or promise results.
            TEXT],
    ],
];
