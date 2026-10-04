<?php

/*
 * "Initial Candidate Screening — Complete Screening Script": every screening
 * question as one playbook lesson. Each Script section is one tab; the
 * screening flow links to those tabs and to the steps inside them. Lines in
 * quotes come from the company calling script where it has them.
 */

return [
    'title' => 'Initial Candidate Screening — Complete Screening Script',
    'from' => 'Introducing the Job Opportunity',
    'compliance' => true,
    'review' => $immigration.' '.$confirm('the description of VSP Group as a U.S. IT consulting firm, and the services offered for training and placement'),
    'en' => $lesson(
        'Keep this lesson open while you screen a consultant. It gives every screening question in order, what the consultant may answer, what to ask next, and exactly what to record in the CRM, ending with a clear screening result: qualified, follow-up or not interested.',
        [
            ['note', 'Rules for Screening', <<<'TEXT'
            - Ask every question, in order, and record each answer in the CRM while you are on the call.
            - Record facts only, in the consultant's own words. Write dates with the month name, such as June 30, 2027.
            - Never advise on immigration filings, and never comment on what the consultant should do with their status. Refer those questions to HR.
            - Never promise immigration approval, guaranteed employment, a guaranteed project or guaranteed placement.
            - Replace Kiran and Priya with your own name and the consultant's name.
            TEXT],
            ['flow', 'Screening Flow', <<<'TEXT'
            1. Start Screening. Introduce the opportunity in one sentence. [[Job Opportunity]]
            2. Job Interest? Looking for an opportunity or a change? [[Job Search Status]]
            3. Visa Status. The current work authorization. [[Visa Status]]
            4. EAD Expiry. The exact EAD start and end dates. [[EAD Expiry]]
            5. Master's / Degree. Degree, field and completion month. [[Education]]
            6. Current Location. City, state and time zone. [[Location]]
            7. Relocation. Open to relocating for training and projects? [[Relocation]]
            8. Training Interest. Needs training and placement support? [[Training & Placement]]
            9. Technology. The main technology and secondary skills. [[Technology]]
            10. Experience. Years, projects and tools. [[Experience]]
            11. Availability. Notice period and start date. [[Availability / Notice Period]]
            12. Qualified / Follow-up / Not Interested? Decide the screening result. [[Screening Result]]
            13. Next Step. Agree and record what happens next. [[Next Step]]
            TEXT],
            ['script', 'Job Opportunity', <<<'TEXT'
            **Say:** "We are a U.S. IT consulting firm looking for consultants to place with our direct clients."
            **Consultant may say:**
            - **They say:** "Okay, tell me more." **You say:** "Sure. First, may I ask a few quick questions to understand your profile?" Go to Job Search Status.
            - **They say:** "Which company is this?" **You say:** "VSP Group. I will send you our company details by email after the call."
            - **They say:** "How did you get my number?" **You say:** "We found your profile for IT opportunities. If you prefer not to be contacted, I will update our records."
            **Record in CRM:**
            - Introduction given: yes.
            - Source of the profile, if you know it.
            **Tip:** Keep it to one sentence. Company details come later in the call, after you understand the consultant.
            TEXT],
            ['script', 'Job Search Status', <<<'TEXT'
            **Ask:** "Are you looking for any job opportunity or job change?"
            **Consultant may say:**
            - **They say:** "Yes, I am looking." **You say:** "Okay, that sounds good. Let me ask a few quick questions." Go to Visa & EAD.
            - **They say:** "I am already working, but open to a change." **You say:** "Okay, thank you. What would make a change worth it for you?" Record the reason, then continue.
            - **They say:** "I am already working and happy." **You say:** "That is great. Do you know any friends who are looking for an opportunity?" Record as not looking.
            - **They say:** "Not right now." **You say:** "No problem. May I contact you again in a few months?" Record the answer and a follow-up date.
            **Record in CRM:**
            - Job search status: actively looking, open to a change, not looking, or not now.
            - Reason for looking, if shared.
            - Follow-up date, if not now.
            **Never:** Push after a clear no.
            TEXT],
            ['script', 'Visa & EAD', <<<'TEXT'
            **Visa Status**
            **Ask:** "Let me know your visa status."
            **Consultant may say:**
            - **They say:** "I am on OPT." **You say:** "Okay, thank you." Ask the EAD dates next.
            - **They say:** "I am on STEM OPT." **You say:** "Okay, thank you. When did your STEM OPT start?"
            - **They say:** "My OPT application is pending." **You say:** "Okay, thank you for letting me know. When did you apply?" Record it as pending for HR.
            - **They say:** "I am on H-1B." or "I have a Green Card." **You say:** "Okay, thank you." Record the status exactly as given.
            - **They say:** "Why do you need my visa details?" **You say:** "I only need your status and dates to understand which opportunities fit. Our HR team verifies documents later, through the official process."
            **EAD Expiry**
            **Ask:** "What is the expiry date of your visa?"
            **Ask:** "And what is the start date on your EAD card?"
            **Consultant may say:**
            - **They say:** "It ends on June 30, 2027." **You say:** "Thank you. June 30, 2027." Repeat the date back to confirm it.
            - **They say:** "It expires next month." **You say:** "Okay, thank you. What is the exact date?" Note it for HR. Do not tell the consultant what to file.
            - **They say:** "I don't remember." **You say:** "No problem. Could you check your EAD card and send me the dates by email?"
            **Record in CRM:**
            - Visa status, exactly as given.
            - EAD start and end dates, with the month name.
            - Pending applications, and when they were filed.
            - Any date that is near, flagged for HR.
            **Never:** Advise on extensions, filings or what to do next with a status. Refer those questions to HR.
            TEXT],
            ['script', 'Education', <<<'TEXT'
            **Ask:** "When did you finish your master's?"
            **Ask:** "What was your degree and field of study, and which university?"
            **Consultant may say:**
            - **They say:** "I finished my master's in Computer Science in May." **You say:** "Okay, thank you. Which university was that?"
            - **They say:** "I am graduating next semester." **You say:** "Okay, thank you. Let me note the expected month."
            - **They say:** "I have a bachelor's only." **You say:** "Okay, thank you." Record it exactly.
            **Record in CRM:**
            - Degree, field of study and university.
            - Completion month and year, or the expected month.
            TEXT],
            ['script', 'Location', <<<'TEXT'
            **Ask:** "Where are you located?"
            **Consultant may say:**
            - **They say:** "I am in Chicago." **You say:** "Okay, thank you. Is that Chicago, Illinois?" Confirm the state.
            - **They say:** "I am moving next month." **You say:** "Okay. Where are you moving to, and when?"
            **Record in CRM:**
            - Current city and state.
            - Time zone, for every future call.
            - Planned move, with the place and date.
            TEXT],
            ['script', 'Relocation', <<<'TEXT'
            **Ask:** "Are you open to relocation for training and projects?"
            **Consultant may say:**
            - **They say:** "Yes, I am open to relocating." **You say:** "Okay, that sounds good."
            - **They say:** "Only to some states." **You say:** "Okay. Which states would work for you?" Record the list.
            - **They say:** "No, I need to stay where I am." **You say:** "Okay, thank you for letting me know. I will note that." Record it. Projects can still be in or near their location.
            - **They say:** "Let me think about it." **You say:** "Of course. I will note that, and we can discuss it again on our next call."
            **Record in CRM:**
            - Open to relocation: yes, some states (list them), no, or thinking.
            TEXT],
            ['script', 'Training & Placement', <<<'TEXT'
            **Ask:** "Do you need training and placement?"
            **Consultant may say:**
            - **They say:** "Yes." **You say:** "Okay, that sounds good." Go to Technology.
            - **They say:** "No, I already have experience." **You say:** "Okay, thank you. Then let me understand your experience." Go to Experience.
            - **They say:** "What kind of training?" **You say:** "We provide technical training, additional training programs and project preparation. My sales manager will explain the details for your technology."
            - **They say:** "Will you guarantee a job after training?" **You say:** "No company can honestly guarantee that. Our team supports you with resume building, interview preparation and marketing at every step."
            **Record in CRM:**
            - Needs training: yes or no.
            - Needs placement and marketing support: yes or no.
            - Any other support asked about, such as an offer letter or payroll.
            **Never:** Promise a job, a project or a placement date.
            TEXT],
            ['script', 'Technology', <<<'TEXT'
            **Ask:** "So what kind of technology are you interested in?"
            **Ask:** "Which other tools or skills do you know?"
            **Consultant may say:**
            - **They say:** "SQL development." **You say:** "Okay, thank you. Which databases have you worked with?"
            - **They say:** "Java, and some cloud." **You say:** "Okay. Which cloud platform, and which would you like to focus on?"
            - **They say:** "I am not sure." **You say:** "No problem. What did you study in your master's, and which subjects did you enjoy?" Note it for your sales manager.
            **Record in CRM:**
            - Main technology.
            - Secondary skills and tools.
            **Never:** Record a vague answer such as "IT". Ask until you have a specific technology.
            TEXT],
            ['script', 'Experience', <<<'TEXT'
            **Ask:** "How many years of experience do you have in that technology?"
            **Ask:** "Can you tell me about your most recent project?"
            **Consultant may say:**
            - **They say:** "Two years, at a bank in Texas." **You say:** "Okay, thank you. What was your role and which tools did you use?"
            - **They say:** "Only academic projects." **You say:** "Okay, that is fine. Tell me about your best academic project." Record it as academic.
            - **They say:** "An internship." **You say:** "Okay. How long was it, and what did you work on?"
            - **They say:** "I am on a project right now." **You say:** "Okay, thank you. When does the project end?"
            **Record in CRM:**
            - Years of experience: professional, internship and academic, recorded separately.
            - Most recent project: role, domain and tools.
            - Current project and its end date, if they are working now.
            TEXT],
            ['script', 'Availability / Notice Period', <<<'TEXT'
            **Ask:** "What is your notice period to join the training and placement program?"
            **Ask:** "When would you be able to start?"
            **Consultant may say:**
            - **They say:** "I can start right away." **You say:** "Okay, thank you." Record it as immediately available.
            - **They say:** "Two weeks." **You say:** "Okay, thank you. So you could start around the 20th?" Confirm the date.
            - **They say:** "I need to give notice at my current job." **You say:** "Okay. How many weeks of notice do you need to give?"
            **Rate and Other Interviews**
            **Ask:** "What rate or salary are you expecting?"
            **Ask:** "Are you interviewing anywhere else, or has any other company submitted your profile?"
            **Consultant may say:**
            - **They say:** "Around $60 an hour." **You say:** "Okay, thank you. Is that W2 or C2C?" Record it exactly. Do not confirm any rate.
            - **They say:** "Another vendor submitted me to a client last week." **You say:** "Thank you for telling me. Which client was it, so we do not submit you twice?"
            **Video Interview Readiness**
            **Ask:** "Do you have a working webcam, a stable internet connection and a quiet place for video interviews?"
            **Consultant may say:**
            - **They say:** "Yes, I have all of that." **You say:** "Great. Some clients ask for technical or coding assessments with the camera on, so that will help."
            - **They say:** "My internet is not very stable." **You say:** "Okay, thank you. Please plan a reliable place before any interview." Record it.
            **Record in CRM:**
            - Available: immediately, or the start date.
            - Notice period, in weeks.
            - Rate or salary expectation, with W2 or C2C.
            - Other interviews and submissions, with the client names.
            - Webcam, internet and a quiet place: yes or no.
            TEXT],
            ['script', 'Screening Result', <<<'TEXT'
            **Qualified / Follow-up / Not Interested?**
            **Qualified when:**
            - The consultant is looking, or open to a change.
            - The visa status and EAD dates are recorded.
            - The technology and experience are clear.
            - The availability is known.
            **Consultant may say:**
            - **They say:** "This sounds good. What is next?" **You say:** "Thank you for sharing these details. Next, I will explain our services, and set up a call with my sales manager." Result: Qualified.
            - **They say:** "Can we talk later this week?" **You say:** "Of course. Would Friday at 11 a.m. your time work?" Result: Follow-up.
            - **They say:** "I am not interested." **You say:** "No problem. Thank you for your time. If you know any friends who are looking, please share my number." Result: Not Interested.
            **Next Step**
            **Say:** "Thank you, Priya. I will send you a mail with our company details today. Please send me your most updated resume."
            **Ask:** "What is your available time to talk with my sales manager?"
            **Record in CRM:**
            - Screening result: qualified, follow-up, or not interested.
            - Next step and its date and time zone.
            - Resume requested: yes, and by when.
            **Tip:** Never leave a screening without a result and a next step in the CRM.
            TEXT],
            ['reference', 'CRM Notes', <<<'TEXT'
            | Field | What to record | Example |
            | --- | --- | --- |
            | Job search status | Looking, open to a change, not looking, not now | Actively looking |
            | Visa status | Exactly as given | OPT |
            | EAD dates | Start and end dates, with the month name | January 15, 2026 to January 14, 2027 |
            | Education | Degree, field, university, completion month | MS Computer Science, May 2025 |
            | Location | City, state and time zone | Chicago, Illinois, Central Time |
            | Relocation | Yes, some states, no, or thinking | Yes, Texas or New Jersey |
            | Training & placement | Training needed, support needed | Needs training and marketing support |
            | Technology | Main technology and secondary skills | SQL development; Power BI |
            | Experience | Professional, internship and academic years; latest project | 1 year internship, banking data project |
            | Current project | Project and end date, if working now | Banking project, ends March 31, 2026 |
            | Availability | Start date or notice period | Two weeks |
            | Rate expectation | Amount, W2 or C2C, exactly as given | $60 an hour, W2 |
            | Other submissions | Other interviews and clients already submitted to | Submitted to a retail client last week |
            | Video readiness | Webcam, stable internet, quiet place | Yes |
            | Screening result | Qualified, follow-up, or not interested | Qualified |
            | Next step | What, when, time zone | Sales manager call, Thursday 11 a.m. Eastern |
            TEXT],
        ],
        'Ask every screening question in order, record each answer in the CRM as you go, never advise on immigration or promise results, and finish every screening with a clear result and a next step.',
    ),
];
