<?php

/*
 * OPT Recruiter course 4, from the company calling script "How to speak with
 * consultant?". The script is split into short lessons. Company-specific
 * claims stay in their own section of each lesson, marked for management
 * confirmation, and the lessons carrying them need compliance review before
 * publishing. Values are kept exactly as the script gives them.
 */

$confirm = fn (string $items): string => 'Company-specific claims from the calling script; management must confirm the current values before publishing: '.$items.'.';
$immigration = 'Immigration content: confirm against current official sources and the company\'s approved wording before publishing.';

$lesson = fn (string $objective, array $sections, string $takeaway): array => [
    ['kind' => 'objective', 'heading' => 'Learning Objective', 'body' => $objective],
    ...array_map(fn (array $section) => ['kind' => $section[0], 'heading' => $section[1], 'body' => $section[2]], $sections),
    ['kind' => 'takeaway', 'heading' => 'Key Takeaway', 'body' => $takeaway],
];

$companyNote = fn (string $body): array => ['note', 'Company-Specific Information', $body.' These are company-specific details from the calling script, not general OPT facts. Use them only after management has confirmed the current values.'];

return [
    'course' => 'Calling & Communication',
    'title' => 'OPT Recruiter Calling & Communication',
    'modules' => [
        'Calling Fundamentals' => [
            [
                'title' => 'Purpose of the Recruiter Call',
                'en' => $lesson(
                    'Understand what a recruiter call is for, so that every call has a clear goal.',
                    [
                        ['content', 'What You Need to Know', <<<'TEXT'
                        - The calling script opens with: This call is regarding a job opportunity to you.
                        - The goal of the first call is to start a conversation, not to finish the whole process.
                        - A good call does three things: it introduces you and the company, it learns about the candidate, and it agrees a next step.
                        TEXT],
                        ['reference', 'The Call in Four Stages', <<<'TEXT'
                        | Stage | Script section |
                        | --- | --- |
                        | Open | Greeting, confirm the candidate, ask if it is the right time |
                        | Screen | Visa status, expiry, graduation, location, relocation, training interest, technology |
                        | Explain | Company, training, placement |
                        | Close | Company mail, resume, referrals, notice period, sales manager call |
                        TEXT],
                        ['practice', 'Check Your Knowledge', "1. What are the three things a good first call does?\n2. Name the four stages of the call."],
                    ],
                    'Every call has a purpose: introduce, learn and agree the next step.',
                ),
            ],
            [
                'title' => 'Preparing Before the Call',
                'en' => $lesson(
                    'Prepare yourself, your notes and your environment before you dial.',
                    [
                        ['reference', 'Before You Dial', <<<'TEXT'
                        - Check the candidate's time zone. Call during their business hours.
                        - Read the candidate's profile: name, degree, skills and location.
                        - Keep the calling script and the screening questions in front of you.
                        - Have the company system open, ready for notes.
                        - Use a quiet place and a good headset.
                        TEXT],
                        ['example', 'Recruiter Example', 'It is 7:30 p.m. in Hyderabad. Your candidate is in Dallas, Texas, where it is 9:00 a.m. Central Time. You check her profile, open the screening form and call.'],
                        ['mistakes', 'Common Mistakes', "- Calling at midnight in the candidate's time zone.\n- Mispronouncing the candidate's name because you did not read it first.\n- Searching for the script while the candidate waits."],
                    ],
                    'Two minutes of preparation makes the whole call smoother.',
                ),
            ],
            [
                'title' => 'Professional Greeting',
                'en' => $lesson(
                    'Open every call with the company\'s standard greeting, clearly and warmly.',
                    [
                        ['example', 'Script Line', <<<'TEXT'
                        **Recruiter:** Greetings of the day! Hi, this is Kiran calling from VSP Group.
                        TEXT],
                        ['content', 'What You Need to Know', <<<'TEXT'
                        - Use your own name in place of Kiran.
                        - Say your name and the company name slowly and clearly.
                        - Smile while you speak. Candidates can hear it.
                        TEXT],
                        ['mistakes', 'Common Mistakes', "- Rushing the company name.\n- Starting to pitch before greeting."],
                        ['practice', 'Practice', 'Say the greeting aloud three times with your own name, at a calm pace.'],
                    ],
                    'A clear, friendly greeting earns you the next few seconds of the call.',
                ),
            ],
            [
                'title' => 'Confirming the Candidate',
                'en' => $lesson(
                    'Confirm you are speaking to the right person before sharing anything about the opportunity.',
                    [
                        ['example', 'Script Line', <<<'TEXT'
                        **Recruiter:** Am I speaking to Priya?
                        **Candidate:** Yes, this is Priya.
                        **Recruiter:** How are you doing, Priya?
                        TEXT],
                        ['content', 'What You Need to Know', <<<'TEXT'
                        - Always confirm the name first. Numbers change hands and profiles can be old.
                        - If it is the wrong person, apologise, thank them and end the call. Update your records.
                        - Ask how they are, and listen to the answer.
                        TEXT],
                        ['mistakes', 'Common Mistakes', "- Explaining the job to whoever answers.\n- Skipping the how are you, which makes the call feel cold."],
                    ],
                    'Confirm the person first. Then build a little warmth before the business.',
                ),
            ],
            [
                'title' => 'Asking if It Is a Good Time',
                'en' => $lesson(
                    'Ask permission to continue, and respect the answer.',
                    [
                        ['example', 'Script Line', <<<'TEXT'
                        **Recruiter:** This call is regarding a job opportunity to you. Is this the right time to speak with you?
                        TEXT],
                        ['content', 'What You Need to Know', <<<'TEXT'
                        - The right-time question is the permission step. Never skip it.
                        - Pause after asking, and let the candidate answer.
                        - If the answer is yes, continue with the opportunity. If no, follow the next lesson.
                        TEXT],
                        ['practice', 'Practice', 'Role-play the opening with a colleague: greeting, confirm the candidate, how are you, and the right-time question.'],
                    ],
                    'Asking permission shows respect, and a candidate who said yes listens better.',
                ),
            ],
            [
                'title' => 'Handling "Call Me Later"',
                'en' => $lesson(
                    'Reschedule politely when it is not a good time, and call back exactly when agreed.',
                    [
                        ['example', 'Script Line', <<<'TEXT'
                        **Candidate:** I am busy right now.
                        **Recruiter:** Sorry to disturb you. May I know what would be the right time to reach you?
                        TEXT],
                        ['reference', 'What You Do', <<<'TEXT'
                        - Note the time the candidate gives, and the time zone.
                        - Repeat it back: So I will call you tomorrow at 11 a.m. Eastern Time.
                        - Set a reminder in the company system.
                        - Call back at exactly that time.
                        TEXT],
                        ['mistakes', 'Common Mistakes', "- Continuing to pitch after the candidate says no.\n- Forgetting the time zone.\n- Calling back late, or not at all."],
                    ],
                    'A rescheduled call that happens on time builds trust before the real conversation starts.',
                ),
            ],
            [
                'title' => 'Professional Call Closing',
                'en' => $lesson(
                    'End every call politely, with the next step clear.',
                    [
                        ['example', 'Script Line', <<<'TEXT'
                        **Recruiter:** Thank you! Have a nice day. Bye.
                        TEXT],
                        ['content', 'Before You Say Goodbye', <<<'TEXT'
                        - Summarise what you agreed, such as the resume, the company mail or the call with the sales manager.
                        - Confirm the best time and way to reach the candidate.
                        - Thank them for their time, then use the script's closing line.
                        TEXT],
                        ['mistakes', 'Common Mistakes', "- Ending abruptly once you have the information you need.\n- Hanging up without agreeing a next step."],
                    ],
                    'Close with a summary, a next step and a thank you.',
                ),
            ],
        ],
        'Initial Candidate Screening' => [
            [
                'title' => 'Introducing the Job Opportunity',
                'compliance' => true,
                'review' => $confirm('the description of the company as a U.S. IT consulting firm placing consultants with direct clients'),
                'en' => $lesson(
                    'Introduce the company and the opportunity in one or two sentences after the candidate agrees to talk.',
                    [
                        ['example', 'Script Line', <<<'TEXT'
                        **Recruiter:** We are a U.S. IT consulting firm looking for consultants to place with our direct clients.
                        TEXT],
                        ['content', 'What You Need to Know', <<<'TEXT'
                        - Keep it short. Details about the company come later in the call.
                        - Follow it straight away with the job-search question in the next lesson.
                        TEXT],
                        $companyNote('Working with direct clients.'),
                    ],
                    'One clear sentence about who you are, then a question to the candidate.',
                ),
            ],
            [
                'title' => 'Understanding Job-Search Status',
                'en' => $lesson(
                    'Find out whether the candidate is looking for an opportunity or a job change.',
                    [
                        ['example', 'Script Line', <<<'TEXT'
                        **Recruiter:** Are you looking for any job opportunity or job change?
                        TEXT],
                        ['reference', 'What Each Answer Means', <<<'TEXT'
                        | Answer | Next step |
                        | --- | --- |
                        | Yes | Continue with the screening questions, starting with visa status |
                        | Not now | Thank them, ask if you may contact them later, and record the answer |
                        | Already working | Congratulate them, and ask whether they know anyone looking |
                        TEXT],
                        ['mistakes', 'Common Mistakes', "- Pushing the candidate after a clear no.\n- Not recording the answer for future calls."],
                    ],
                    'The job-search question decides whether the screening starts now or later.',
                ),
            ],
            ['title' => 'Visa Status', 'compliance' => true, 'review' => $immigration],
            [
                'title' => 'Visa / EAD Expiry',
                'compliance' => true,
                'review' => $immigration,
                'en' => $lesson(
                    'Ask for and record the expiry date of the candidate\'s visa status or EAD.',
                    [
                        ['example', 'Script Line', <<<'TEXT'
                        **Recruiter:** What is the expiry date of your visa?
                        TEXT],
                        ['content', 'What You Need to Know', <<<'TEXT'
                        - For OPT and STEM OPT candidates, the date that matters for work is the EAD end date.
                        - Ask for the start date too, if the candidate has a new EAD.
                        - Record dates with the month name, such as June 30, 2027.
                        - If the end date is near, note it for HR. Do not tell the candidate what to do.
                        TEXT],
                        ['mistakes', 'Common Mistakes', "- Recording only the year.\n- Commenting on what the candidate should file next."],
                    ],
                    'Record the exact EAD dates. HR decides what an approaching end date means.',
                ),
            ],
            ['title' => 'Graduation / Master\'s Completion', 'from' => 'Graduation'],
            ['title' => 'Current Location'],
            ['title' => 'Relocation'],
            [
                'title' => 'Training & Placement Interest',
                'compliance' => true,
                'review' => $confirm('the training and placement program and the relocation it requires'),
                'en' => $lesson(
                    'Ask whether the candidate is open to relocating for training and projects, and whether they need training and placement.',
                    [
                        ['example', 'Script Line', <<<'TEXT'
                        **Recruiter:** Are you open to relocation for training and projects?
                        **Recruiter:** Do you need training and placement?
                        **Candidate:** Yes.
                        **Recruiter:** Okay, that sounds good.
                        TEXT],
                        ['content', 'What You Need to Know', <<<'TEXT'
                        - These two questions decide whether to explain the training and placement program.
                        - If the candidate says yes, continue to the company introduction and the technology question.
                        - If no, record it. The candidate may suit other roles later.
                        TEXT],
                        $companyNote('The training and placement program.'),
                    ],
                    'Ask both questions plainly, and let the answers decide what you explain next.',
                ),
            ],
            [
                'title' => 'Technology Interest',
                'en' => $lesson(
                    'Find out which technology the candidate wants to work in.',
                    [
                        ['example', 'Script Line', <<<'TEXT'
                        **Recruiter:** So what kind of technology are you interested in?
                        **Candidate:** SQL development.
                        **Recruiter:** Okay, we will provide you a training and placement program with our clients.
                        TEXT],
                        ['content', 'What You Need to Know', <<<'TEXT'
                        - Listen for the technology, and ask one follow-up about their experience in it.
                        - Record the main technology and any secondary skills.
                        - Match your next sentences to what the candidate said.
                        TEXT],
                        ['mistakes', 'Common Mistakes', "- Suggesting a technology before asking.\n- Recording a vague answer, such as IT."],
                    ],
                    'Ask, listen and record the technology the candidate actually wants.',
                ),
            ],
            ['title' => 'Availability / Notice Period', 'from' => 'Availability'],
        ],
        'Explaining the Opportunity' => [
            [
                'title' => 'Introducing VSP Group',
                'compliance' => true,
                'review' => $confirm('established in 2018 (another company page says 2010), CEO Mr. Vikram Sagar Pasala, and named one of America\'s Most Promising Companies by Forbes in 2024 (another page says ranked number 57)'),
                'en' => $lesson(
                    'Introduce VSP Group using the script, with company facts kept to the confirmed version.',
                    [
                        ['example', 'Script Line', <<<'TEXT'
                        **Recruiter:** See, we are a growing consulting and technology services company. Our company, VSP Group, was established in the year 2018, and our CEO is Mr. Vikram Sagar Pasala. We were named one of America's Most Promising Companies by Forbes Magazine in the year 2024.
                        TEXT],
                        $companyNote('Founding year 2018, the CEO\'s name and the Forbes recognition in 2024. Other company material gives a different founding year and describes the Forbes recognition differently.'),
                        ['mistakes', 'Common Mistakes', "- Adding facts that are not in the confirmed introduction.\n- Giving different facts on different calls."],
                    ],
                    'Introduce the company with confirmed facts only. Candidates check them online.',
                ),
            ],
            [
                'title' => 'Explaining the Training Program',
                'compliance' => true,
                'review' => $confirm('in-class training at the New Jersey and Texas headquarters, and a training period of 4 to 5 weeks'),
                'en' => $lesson(
                    'Explain the training program clearly, as a process.',
                    [
                        ['example', 'Script Line', <<<'TEXT'
                        **Recruiter:** Our company, VSP Group, has headquarters in New Jersey and Texas. From there you will get in-class training. The training period would be around 4 to 5 weeks.
                        TEXT],
                        $companyNote('In-class training at the headquarters, and a training period of 4 to 5 weeks.'),
                        ['content', 'How to Explain It', "- Describe the training as what the company offers, not as a guarantee of a job.\n- For OPT candidates, HR and compliance decide how training fits their work authorization."],
                    ],
                    'Explain the training honestly and simply, using confirmed details only.',
                ),
            ],
            [
                'title' => 'Explaining Placement Support',
                'compliance' => true,
                'review' => $confirm('placement with direct clients after training'),
                'en' => $lesson(
                    'Explain what happens after training without promising a placement.',
                    [
                        ['example', 'Script Line', <<<'TEXT'
                        **Recruiter:** Soon after training, we will prepare your resume, and we will place you with our direct clients.
                        TEXT],
                        ['content', 'How to Explain It', <<<'TEXT'
                        - Placement is the goal of the program. No placement or start date is guaranteed.
                        - Say: After training, our marketing team works to place you with our clients.
                        TEXT],
                        $companyNote('Placement with direct clients.'),
                    ],
                    'Placement is the goal, never a promise.',
                ),
            ],
            [
                'title' => 'Explaining Location',
                'compliance' => true,
                'review' => $confirm('headquarters in New Jersey and Texas (another company page lists Atlanta, Georgia, with branches in Edison, New Jersey, and Dallas, Texas)'),
                'en' => $lesson(
                    'Tell the candidate where the training takes place.',
                    [
                        ['example', 'Script Line', <<<'TEXT'
                        **Recruiter:** Our company, VSP Group, has headquarters in New Jersey and Texas. From there you will get in-class training.
                        TEXT],
                        $companyNote('Headquarters in New Jersey and Texas. Other company material lists a different headquarters.'),
                        ['practice', 'Practice', 'Explain the training location in one sentence, then ask: Would you be comfortable relocating to New Jersey or Texas for the training?'],
                    ],
                    'Give the confirmed training location clearly, then check the candidate\'s comfort with it.',
                ),
            ],
            [
                'title' => 'Explaining Relocation',
                'compliance' => true,
                'review' => $confirm('an offer letter if the candidate relocates to the head office in Texas or New Jersey'),
                'en' => $lesson(
                    'Explain why the program asks candidates to relocate, and what the script says about the offer letter.',
                    [
                        ['example', 'Script Line', <<<'TEXT'
                        **Recruiter:** We will give you an offer letter if you relocate to our head office in Texas or New Jersey.
                        TEXT],
                        $companyNote('An offer letter on relocating to the head office.'),
                        ['note', 'Offer Letters Are Never Sold', 'An offer letter is issued by HR for a genuine position. It is never a reward for relocating that a candidate pays for, and recruiters never collect money. Refer every cost question to HR.'],
                    ],
                    'Explain relocation and the offer letter exactly as confirmed, and send cost questions to HR.',
                ),
            ],
            [
                'title' => 'Explaining Accommodation',
                'compliance' => true,
                'review' => $confirm('accommodation and allotted rooms at the headquarters'),
                'en' => $lesson(
                    'Explain the accommodation offered during training.',
                    [
                        ['example', 'Script Line', <<<'TEXT'
                        **Recruiter:** We will provide you accommodation at our headquarters. We will allot you rooms for staying.
                        TEXT],
                        $companyNote('Accommodation and rooms at the headquarters.'),
                        ['content', 'What to Confirm Before You Explain', "- Whether accommodation is still offered.\n- For how long.\n- Whether there is any cost to the candidate. Refer cost questions to HR."],
                    ],
                    'Describe accommodation only as confirmed, and refer every cost question to HR.',
                ),
            ],
            [
                'title' => 'Explaining Resume Preparation',
                'compliance' => true,
                'review' => $confirm('resume preparation by the company after training'),
                'en' => $lesson(
                    'Explain the company\'s resume preparation step honestly.',
                    [
                        ['example', 'Script Line', <<<'TEXT'
                        **Recruiter:** Soon after training, we will prepare your resume.
                        TEXT],
                        ['content', 'What You Need to Know', <<<'TEXT'
                        - The resume presents the candidate's real education, training and experience.
                        - Resume preparation improves how the experience is presented. It never invents experience, employers, dates or skills.
                        TEXT],
                        $companyNote('Resume preparation after training.'),
                    ],
                    'A prepared resume is a clearer resume, never an invented one.',
                ),
            ],
            [
                'title' => 'Explaining Client Opportunities',
                'compliance' => true,
                'review' => $confirm('direct clients all over the USA, and any client names'),
                'en' => $lesson(
                    'Explain the kind of client opportunities the company works with, without naming clients unless approved.',
                    [
                        ['example', 'Script Line', <<<'TEXT'
                        **Recruiter:** We are having direct clients all over the USA.
                        TEXT],
                        ['content', 'What You Need to Know', <<<'TEXT'
                        - Describe the industries and technologies, such as banking, retail, SQL and Java.
                        - Name a client only if management has approved it. Some clients do not allow their names to be used, and relationships change.
                        TEXT],
                        $companyNote('Direct clients across the USA.'),
                    ],
                    'Describe client opportunities honestly, and name clients only with approval.',
                ),
            ],
            [
                'title' => 'Explaining Next Steps',
                'en' => $lesson(
                    'Tell an interested candidate exactly what happens after the call.',
                    [
                        ['reference', 'Next Steps from the Script', <<<'TEXT'
                        1. We send you an email with our company details.
                        2. You send us your most updated resume.
                        3. We confirm your notice period for the training and placement program.
                        4. We schedule a call with my sales manager.
                        TEXT],
                        ['example', 'Recruiter Example', '**Recruiter:** Here is what happens next. I will email you our company details today. Please reply with your most updated resume. Then I will set up a call with my sales manager. What time suits you for that call?'],
                    ],
                    'An interested candidate should leave the call knowing every next step.',
                ),
            ],
        ],
        'Candidate Questions & Objections' => [
            [
                'title' => 'Question: "Why VSP Group?"',
                'compliance' => true,
                'review' => $confirm('in-class training, a large marketing team with dedicated account managers, direct clients all over the USA, internal staff of more than 100 people including HR, legal, finance, marketing, account managers and client managers, and H-1B and Green Card sponsorship'),
                'en' => $lesson(
                    'Answer "Why VSP Group?" with the points in the calling script, kept to confirmed facts.',
                    [
                        ['example', 'Script Line', <<<'TEXT'
                        **Candidate:** Why should I choose VSP Group?
                        **Recruiter:** VSP Group offers in-class training. We have a large marketing team with dedicated account managers. We have direct clients all over the USA. Our internal staff of more than 100 people includes HR, legal, finance, marketing, account managers and client managers. And we offer H-1B and Green Card sponsorship.
                        TEXT],
                        $companyNote('In-class training, the marketing team, direct clients, internal staff of more than 100 people, and H-1B and Green Card sponsorship.'),
                        ['note', 'Sponsorship', 'Sponsorship is decided case by case under company policy and government rules. No H-1B selection is ever guaranteed. Use only the wording HR has approved.'],
                    ],
                    'Give the confirmed reasons, honestly, and never turn sponsorship into a promise.',
                ),
            ],
            ['title' => 'Question: "Is This Company Legitimate?"', 'from' => 'Objection: "Is your company real?"', 'compliance' => true, 'review' => $confirm('any company facts, websites, addresses and E-Verify details shared to verify the company')],
            [
                'title' => 'Question: "What Is the Training Process?"',
                'compliance' => true,
                'review' => $confirm('in-class training at the headquarters, 4 to 5 weeks, accommodation, and resume preparation and placement afterwards'),
                'en' => $lesson(
                    'Explain the training process in order when a candidate asks.',
                    [
                        ['reference', 'The Process from the Script', <<<'TEXT'
                        1. Relocate to the headquarters in New Jersey or Texas.
                        2. Attend in-class training for about 4 to 5 weeks.
                        3. Stay in the accommodation provided at the headquarters.
                        4. After training, the company prepares your resume.
                        5. The marketing team works to place you with clients.
                        TEXT],
                        $companyNote('Each step and its details.'),
                        ['mistakes', 'Common Mistakes', "- Leaving out relocation until the end.\n- Saying placement is guaranteed after training."],
                    ],
                    'Walk through the training process in order, with confirmed details and no guarantees.',
                ),
            ],
            [
                'title' => 'Question: "Do I Need to Relocate?"',
                'compliance' => true,
                'review' => $confirm('relocation to the headquarters for training and for projects'),
                'en' => $lesson(
                    'Answer relocation questions honestly and early.',
                    [
                        ['example', 'Recruiter Example', <<<'TEXT'
                        **Candidate:** Do I need to relocate?
                        **Recruiter:** For the training and placement program, yes. The in-class training is at our headquarters in New Jersey or Texas. Projects can also be in other locations. Are you open to that?
                        TEXT],
                        $companyNote('Relocation for training and for projects.'),
                        ['content', 'If the Candidate Cannot Relocate', "- Thank them and record it.\n- Do not pressure them.\n- Note their preferred locations for future roles."],
                    ],
                    'Be clear about relocation from the start, and respect a no.',
                ),
            ],
            [
                'title' => 'Question: "What Happens After Training?"',
                'compliance' => true,
                'review' => $confirm('resume preparation and placement with direct clients after training'),
                'en' => $lesson(
                    'Explain what follows training without promising a placement or a date.',
                    [
                        ['example', 'Recruiter Example', <<<'TEXT'
                        **Candidate:** What happens after the training?
                        **Recruiter:** Soon after training, we prepare your resume and our marketing team works to place you with our clients. I cannot promise a date, but I will keep you updated at every step.
                        TEXT],
                        $companyNote('Resume preparation and placement with direct clients.'),
                    ],
                    'After training comes marketing and placement work, explained honestly, without dates or guarantees.',
                ),
            ],
            ['title' => 'Question: "What About H-1B?"', 'from' => 'Objection: "Can you guarantee H-1B?"', 'compliance' => true, 'review' => $immigration.' '.$confirm('the company\'s H-1B sponsorship position')],
            ['title' => 'Question: "What About My EAD?"', 'from' => 'Objection: "Why do you need my EAD/I-20?"', 'compliance' => true, 'review' => $immigration],
            [
                'title' => 'Question: "What Is the Opportunity?"',
                'compliance' => true,
                'review' => $confirm('the training and placement program with direct clients'),
                'en' => $lesson(
                    'Summarise the opportunity in a few clear sentences.',
                    [
                        ['example', 'Recruiter Example', <<<'TEXT'
                        **Candidate:** So what exactly is the opportunity?
                        **Recruiter:** It is a training and placement program. You get in-class training in your technology at our headquarters. After training, we prepare your resume and work to place you with our clients across the U.S.
                        TEXT],
                        $companyNote('The training and placement program.'),
                        ['mistakes', 'Common Mistakes', "- Describing it as a guaranteed job.\n- Giving a long speech instead of a short summary and a question."],
                    ],
                    'Summarise the program in three sentences, then ask what the candidate thinks.',
                ),
            ],
            [
                'title' => 'Question: "Can I Speak with a Manager?"',
                'en' => $lesson(
                    'Handle a request to speak with a manager positively, and arrange it.',
                    [
                        ['content', 'What You Need to Know', <<<'TEXT'
                        - The script already ends with a call between the candidate and your sales manager.
                        - A request for a manager is a good sign: the candidate is serious.
                        - Never take it personally.
                        TEXT],
                        ['example', 'Recruiter Example', '**Candidate:** Can I speak with your manager? **Recruiter:** Of course. That is our next step anyway. What is your available time to talk with my sales manager? I will send you a confirmation.'],
                    ],
                    'Welcome the request and schedule the sales manager call.',
                ),
            ],
        ],
        'Follow-Up & Next Steps' => [
            [
                'title' => 'Requesting the Updated Resume',
                'en' => $lesson(
                    'Ask for the candidate\'s most updated resume in a way that gets a reply.',
                    [
                        ['example', 'Script Line', <<<'TEXT'
                        **Recruiter:** Please send me your most updated resume.
                        TEXT],
                        ['content', 'What You Need to Know', <<<'TEXT'
                        - Ask for it during the call, and again in your email.
                        - Give the candidate your official email address.
                        - Agree when they will send it, such as by this evening.
                        TEXT],
                        ['mistakes', 'Common Mistakes', "- Accepting an old resume without asking for the latest one.\n- Asking for the resume through personal messaging apps."],
                    ],
                    'Ask clearly, give your official email and agree a time.',
                ),
            ],
            [
                'title' => 'Sending Company Information',
                'compliance' => true,
                'review' => $confirm('the company details included in the email to candidates'),
                'en' => $lesson(
                    'Send interested candidates the company details by email after the call.',
                    [
                        ['content', 'What You Need to Know', <<<'TEXT'
                        - The script says: We need to send him a mail regarding our company details.
                        - Use the approved email template from your official company address.
                        - Include your name, title and phone number in the signature.
                        - Send it the same day, while the call is fresh.
                        TEXT],
                        $companyNote('The company details in the email.'),
                    ],
                    'Send the approved company email the same day, from your official address.',
                ),
            ],
            [
                'title' => 'Referral Conversation',
                'compliance' => true,
                'review' => $confirm('a referral amount of $750'),
                'en' => $lesson(
                    'Ask interested candidates to refer friends, using the confirmed referral terms.',
                    [
                        ['example', 'Script Line', <<<'TEXT'
                        **Recruiter:** Please refer your friends also. For each referral you will get a referral amount of $750. The more friends you refer, the more benefits you will get from our company.
                        TEXT],
                        $companyNote('The referral amount of $750 and how it is paid.'),
                        ['content', 'What to Confirm Before You Explain', "- The current amount.\n- When and how it is paid.\n- Any conditions, such as the friend joining or being placed."],
                    ],
                    'Ask for referrals warmly, and quote only the confirmed referral terms.',
                ),
            ],
            [
                'title' => 'Confirming Notice Period',
                'en' => $lesson(
                    'Confirm when the candidate can join the training and placement program.',
                    [
                        ['example', 'Script Line', <<<'TEXT'
                        **Recruiter:** What is your notice period to join the training and placement program?
                        TEXT],
                        ['content', 'What You Need to Know', <<<'TEXT'
                        - Ask near the end of the call, after the candidate shows interest.
                        - Record the answer as a date or a number of weeks.
                        - If the candidate is working, ask whether they must give notice to their current employer.
                        TEXT],
                    ],
                    'A clear notice period lets the team plan the candidate\'s start.',
                ),
            ],
            [
                'title' => 'Scheduling Sales Manager Discussion',
                'en' => $lesson(
                    'Schedule the call between the candidate and your sales manager.',
                    [
                        ['example', 'Script Line', <<<'TEXT'
                        **Recruiter:** What is your available time to talk with my sales manager?
                        TEXT],
                        ['reference', 'What You Do', <<<'TEXT'
                        - Agree a date and time, and confirm the time zone.
                        - Send a confirmation with the time and your manager's name.
                        - Share your call notes with your sales manager before the call.
                        TEXT],
                        ['mistakes', 'Common Mistakes', <<<'TEXT'
                        - Booking a time without saying whose time zone it is.
                        - Promising what the sales manager will offer on the call.
                        - Forgetting to remind the candidate shortly before the call.
                        TEXT],
                    ],
                    'Book the sales manager call with a confirmed time and good notes.',
                ),
            ],
            ['title' => 'Follow-Up Call', 'from' => 'Follow-Up'],
            [
                'title' => 'Voicemail',
                'compliance' => true,
                'review' => $confirm('the call-back number 9515708888 and extension 999'),
                'en' => $lesson(
                    'Leave a short, clear voicemail when the candidate does not answer.',
                    [
                        ['example', 'Script Line', <<<'TEXT'
                        **Recruiter:** Hi, this is Ajay calling from VSP Group. This call is regarding a job opportunity for you. I would appreciate it if you call me back at 9515708888, extension 999. Thank you. Bye, have a nice day.
                        TEXT],
                        ['content', 'What You Need to Know', <<<'TEXT'
                        - Use your own name, and the call-back number and extension assigned to you.
                        - Say the number slowly, and repeat it once.
                        - Keep it under 30 seconds. Do not leave personal or visa details in a voicemail.
                        TEXT],
                        $companyNote('The call-back number 9515708888 and extension 999 in the script.'),
                    ],
                    'Short, clear and with a call-back number said slowly. Nothing personal in the message.',
                ),
            ],
        ],
        'Practical Calling' => [
            ['title' => '60-Second Cold Call'],
            ['title' => 'Initial Candidate Screening Call', 'from' => 'Initial Screening Call', 'compliance' => true, 'review' => $immigration],
            [
                'title' => 'Candidate Says "Call Me Later"',
                'en' => $lesson(
                    'Practise rescheduling a call politely and following through.',
                    [
                        ['example', 'Practice Call', <<<'TEXT'
                        **Recruiter:** Greetings of the day! Hi, this is Kiran calling from VSP Group. Am I speaking to Rahul?
                        **Candidate:** Yes.
                        **Recruiter:** How are you doing, Rahul? This call is regarding a job opportunity to you. Is this the right time to speak with you?
                        **Candidate:** Not really, I am driving.
                        **Recruiter:** Sorry to disturb you. May I know what would be the right time to reach you?
                        **Candidate:** Tomorrow at 10 a.m.
                        **Recruiter:** Is that 10 a.m. Central Time? I will call you then. Thank you, have a nice day.
                        TEXT],
                        ['practice', 'Practice', "1. Role-play the call with a colleague.\n2. Set the reminder in the company system.\n3. Make the call-back at the agreed time."],
                    ],
                    'Reschedule in under a minute, confirm the time zone and call back on time.',
                ),
            ],
            [
                'title' => 'Candidate Is Interested',
                'compliance' => true,
                'review' => $confirm('the company details email and the referral amount of $750'),
                'en' => $lesson(
                    'Practise closing a call with an interested candidate.',
                    [
                        ['example', 'Practice Call', <<<'TEXT'
                        **Candidate:** This sounds good. I am interested.
                        **Recruiter:** That is great. I will send you a mail with our company details today. Please send me your most updated resume.
                        **Recruiter:** Also, please refer your friends. You will get a referral amount for each referral.
                        **Recruiter:** What is your notice period to join the training and placement program?
                        **Candidate:** Two weeks.
                        **Recruiter:** And what is your available time to talk with my sales manager?
                        **Candidate:** Thursday at 11 a.m. Eastern.
                        **Recruiter:** Perfect. Thank you! Have a nice day.
                        TEXT],
                        $companyNote('The referral amount, which the script gives as $750.'),
                        ['practice', 'Practice', 'Practise the close until you cover all five steps: company mail, resume, referrals, notice period and the sales manager call.'],
                    ],
                    'An interested candidate needs five next steps covered before you say goodbye.',
                ),
            ],
            [
                'title' => 'Candidate Needs Training',
                'compliance' => true,
                'review' => $confirm('in-class training at the headquarters, 4 to 5 weeks, accommodation and placement afterwards'),
                'en' => $lesson(
                    'Practise explaining the training and placement program to a candidate who needs training.',
                    [
                        ['example', 'Practice Call', <<<'TEXT'
                        **Recruiter:** Do you need training and placement?
                        **Candidate:** Yes, I need training in SQL.
                        **Recruiter:** Okay, that sounds good. We will provide you training and placement with our clients. Our headquarters are in New Jersey and Texas, where you get in-class training for about 4 to 5 weeks. We provide accommodation at the headquarters. Soon after training, we prepare your resume and work to place you with our clients.
                        TEXT],
                        $companyNote('Training location, duration, accommodation and placement.'),
                        ['practice', 'Practice', 'Explain the program in under one minute, then ask: Do you have any questions about the training?'],
                    ],
                    'Explain the program in a minute, honestly, and invite questions.',
                ),
            ],
            [
                'title' => 'Candidate Questions Relocation',
                'compliance' => true,
                'review' => $confirm('relocation to the headquarters and the offer letter on relocation'),
                'en' => $lesson(
                    'Practise answering a candidate who is unsure about relocating.',
                    [
                        ['example', 'Practice Call', <<<'TEXT'
                        **Candidate:** I live in California. Do I really need to move?
                        **Recruiter:** For our training and placement program, the in-class training is at our headquarters in New Jersey or Texas, so yes. We provide accommodation during the training. Would you be open to that?
                        **Candidate:** Let me think about it.
                        **Recruiter:** Of course. I will send you our company details today, and I can call you on Friday. Does that work?
                        TEXT],
                        $companyNote('Relocation and accommodation details.'),
                    ],
                    'Answer honestly, give the candidate time, and agree a follow-up.',
                ),
            ],
            [
                'title' => 'Candidate Questions H-1B',
                'compliance' => true,
                'review' => $immigration.' '.$confirm('H-1B and Green Card sponsorship'),
                'en' => $lesson(
                    'Practise answering H-1B questions honestly, with no guarantees.',
                    [
                        ['example', 'Practice Call', <<<'TEXT'
                        **Candidate:** Will you file my H-1B?
                        **Recruiter:** I understand H-1B is important for your plans. Our company offers H-1B and Green Card sponsorship under its policy. I want to be honest: no company can guarantee H-1B, because the selection is done by the government. Our HR team can explain the policy in detail. Shall I arrange that?
                        TEXT],
                        $companyNote('H-1B and Green Card sponsorship, in the exact wording HR approves.'),
                        ['mistakes', 'Common Mistakes', "- Saying your H-1B will definitely be filed or approved.\n- Quoting sponsorship numbers or costs."],
                    ],
                    'Share the approved policy, say clearly that no one can guarantee H-1B, and offer HR.',
                ),
            ],
            [
                'title' => 'Candidate Questions the Company',
                'compliance' => true,
                'review' => $confirm('the founding year, CEO, Forbes recognition, headquarters and staff count'),
                'en' => $lesson(
                    'Practise answering a candidate who doubts the company, calmly and with checkable facts.',
                    [
                        ['example', 'Practice Call', <<<'TEXT'
                        **Candidate:** How do I know your company is genuine?
                        **Recruiter:** That is a very fair question. VSP Group is a consulting and technology services company, and our CEO is Mr. Vikram Sagar Pasala. I will send you an email right now from my official company address, with our website and my LinkedIn profile, so you can check us at your own pace.
                        TEXT],
                        $companyNote('Company facts such as the founding year, CEO, Forbes recognition, headquarters and staff count.'),
                        ['practice', 'Practice', 'Role-play with a colleague who keeps asking for proof. Stay calm, offer checkable facts and never ask for money or documents to prove anything.'],
                    ],
                    'Welcome doubts, give checkable facts and let the candidate take their time.',
                ),
            ],
            [
                'title' => 'Complete Mock OPT Recruiter Call',
                'compliance' => true,
                'review' => $immigration.' '.$confirm('every company fact in the script, including the founding year, CEO, Forbes recognition, headquarters, training, accommodation, sponsorship and referral amount'),
                'en' => $lesson(
                    'Practise the whole OPT recruiter call from greeting to closing.',
                    [
                        ['reference', 'Call Checklist', <<<'TEXT'
                        | Stage | Script step |
                        | --- | --- |
                        | Open | Greeting, confirm the candidate, how are you, right time |
                        | Introduce | U.S. IT consulting firm placing consultants with direct clients |
                        | Screen | Job search, visa status, expiry date, master's completion, location, relocation, training interest |
                        | Explain | Company introduction, technology interest, training, accommodation, resume and placement |
                        | Answer | Why VSP Group, and any questions |
                        | Close | Company mail, resume, referrals, notice period, sales manager call, thank you |
                        TEXT],
                        ['example', 'Mock Call', <<<'TEXT'
                        **Recruiter:** Greetings of the day! Hi, this is Kiran calling from VSP Group. Am I speaking to Priya? How are you doing, Priya? This call is regarding a job opportunity to you. Is this the right time to speak with you?
                        **Candidate:** Yes, go ahead.
                        **Recruiter:** We are a U.S. IT consulting firm looking for consultants to place with our direct clients. Are you looking for any job opportunity or job change?
                        **Candidate:** Yes.
                        **Recruiter:** Let me know your visa status. What is the expiry date? When did you finish your master's? Where are you located? Are you open to relocation for training and projects? Do you need training and placement?
                        **Candidate:** I am on OPT until August next year. I finished my master's in May. I am in Chicago, and yes, I am open to relocating and I need training.
                        **Recruiter:** Okay, that sounds good. What kind of technology are you interested in?
                        **Candidate:** SQL development.
                        **Recruiter:** We will provide you training and placement with our clients. You get in-class training at our headquarters for about 4 to 5 weeks, with accommodation, and then we prepare your resume and work to place you with our clients.
                        **Recruiter:** I will send you a mail with our company details. Please send your most updated resume, and refer your friends too. What is your notice period, and what is your available time to talk with my sales manager?
                        **Candidate:** I can join in two weeks. Thursday at 11 a.m. works.
                        **Recruiter:** Thank you! Have a nice day. Bye.
                        TEXT],
                        ['practice', 'Self-Review', "- Did I ask permission before continuing?\n- Did I ask every screening question the same way I ask every candidate?\n- Did I use only confirmed company facts?\n- Did I avoid any promise about placement or H-1B?\n- Did I cover all five closing steps?"],
                    ],
                    'Practise the full call until every step feels natural and every company fact is the confirmed one.',
                ),
            ],
        ],
    ],
];
