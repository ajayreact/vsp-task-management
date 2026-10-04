<?php

/*
 * "How to Speak with a Consultant": the whole call as one playbook lesson.
 * Each Script section is one stage of the call (a tab on the page); the two
 * flows link to those stages and to the steps inside them. Offer Letter and
 * Payroll are the core services: their stages quote the approved prices and
 * are shown prominently. Other lines in quotes come from the company calling
 * script.
 */

return [
    'title' => 'How to Speak with a Consultant',
    'from' => 'Purpose of the Recruiter Call',
    'compliance' => true,
    'review' => $confirm('the Offer Letter Service at $500 per eligible candidate, the Payroll Service at $2,260 total (payroll amount $1,934.17 and service charge $325.83), the descriptions of C2C marketing, training, job support, interview preparation and resume building, VSP Group established in 2018, CEO Mr. Vikram Sagar Pasala, Forbes America\'s Most Promising Companies 2024, H-1B and Green Card sponsorship, the $750 referral amount, and the voicemail call-back number 9515708888 extension 999').' '.$immigration.' Legal review: confirm the offer letter and payroll wording is accurate and approved, and that an offer letter is described only as documentation of genuine employment.',
    'en' => $lesson(
        'Use this playbook during a real call with a consultant. It covers every stage from "Greetings of the day" to "Thank you, have a nice day": screening, understanding what the consultant needs, explaining our services with Offer Letter and Payroll as the core services, and agreeing the next step. Each stage shows what to say, what the consultant may answer, what to say next, and what to record in the CRM.',
        [
            ['note', 'Rules for Every Call', <<<'TEXT'
            - **Offer Letter Service and Payroll Service are our core services.** C2C marketing, training, job support, interview preparation and resume building support them.
            - Explain services only after you understand the consultant's requirement, technology, work authorization and employment situation.
            - Quote only the approved prices: Offer Letter Service $500 per eligible candidate; Payroll Service $2,260 total, made up of the payroll amount of $1,934.17 and a service charge of $325.83.
            - Never promise immigration approval, guaranteed employment, a guaranteed project or guaranteed placement.
            - An offer letter is employment documentation. It does not by itself create or guarantee work authorization. The DSO and USCIS decide immigration matters.
            - The company facts and prices in this playbook must be confirmed by management before use. Replace Kiran, Ajay and Priya with your own name and the consultant's name.
            TEXT],
            ['flow', 'Call Flow', <<<'TEXT'
            1. Start Call. Profile, time zone and CRM open. [[Prepare]]
            2. Greeting. Greet and confirm the name. [[Greeting]]
            3. Good Time? If busy, book a call-back time. [[Good Time?]]
            4. Screening. Looking for a job or a change? [[Screening]]
            5. Visa / Status. Work authorization and expiry date. [[Visa / Status]]
            6. Education. When the master's was finished. [[Education]]
            7. Location. City and state. [[Location]]
            8. Employment Situation. Working, between jobs, or starting out. [[Employment Situation]]
            9. Technology. The technology they work in. [[Technology]]
            10. Understand Requirement. What the consultant needs now. [[Understand Requirement]]
            11. Explain Services. Our service model. [[Your Service Model]]
            12. **Core service:** Offer Letter — $500. Per eligible candidate. [[Offer Letter Service]]
            13. **Core service:** Payroll — $2,260. Payroll $1,934.17 plus service charge $325.83. [[Payroll Service]]
            14. Interested? Answer questions and objections first. [[Interested?]]
            15. Resume. Company mail, resume and referrals. [[Resume & Referral]]
            16. Sales Manager. Book the call with your sales manager. [[Sales Manager Call]]
            17. Follow-up. Start date and the next follow-up. [[Follow-up]]
            18. Close. Summarise and thank the consultant. [[Closing]]
            TEXT],
            ['flow', 'Service Flow', <<<'TEXT'
            1. Screening. Work authorization, employment situation, technology. [[Screening]]
            2. Understand Requirement. Ask what the consultant needs before you explain anything. [[Understand Requirement]]
            3. Explain Services. Give the overview of our service model. [[Your Service Model]]
            4. **Core service:** Offer Letter — $500. Employment documentation and onboarding support for eligible candidates. [[Offer Letter Service]]
            5. Training. Technical training, other programs, project preparation. [[Training]]
            6. Resume Building. Preparation, optimization, profile presentation. [[Resume Building]]
            7. Job / Interview Support. Interview preparation, mock interviews, job support. [[Job / Interview Support]]
            8. C2C Marketing. Profile marketing, submissions, client coordination. [[C2C Marketing]]
            9. **Core service:** Payroll — $2,260. Payroll $1,934.17 plus service charge $325.83. [[Payroll Service]]
            10. Next Step. Resume, sales manager call and follow-up. [[Resume & Referral]]
            TEXT],
            ['script', 'Prepare', <<<'TEXT'
            **Before you dial:**
            - Check the consultant's time zone. Call during their business hours.
            - Read the profile: the name and how to say it, the degree, skills and location.
            - Open the consultant's record in the CRM, ready to type notes during the call.
            - Keep this playbook open on the Greeting tab, and the approved prices in front of you.
            - Use a quiet place and a headset. Smile before you dial.
            **If nobody answers:** Leave the voicemail from the Voicemail tab, and record the attempt.
            **Record in CRM:**
            - Call attempt: date, time and the consultant's time zone.
            **Tip:** Two minutes of preparation makes the whole call smoother. Never search for the script or the prices while the consultant waits.
            TEXT],
            ['script', 'Greeting', <<<'TEXT'
            **Opening**
            **Say:** "Greetings of the day! Hi, this is Kiran calling from VSP Group."
            **Say:** "Am I speaking to Priya?"
            **Consultant may say:**
            - **They say:** "Yes, this is Priya." **You say:** "How are you doing, Priya?" Listen to the answer, then ask if it is a good time.
            - **They say:** "Who is calling?" **You say:** "This is Kiran from VSP Group, a U.S. IT consulting firm. I am calling about a job opportunity. Am I speaking to Priya?"
            - **They say:** "Wrong number." **You say:** "Sorry for the trouble. Thank you, have a nice day." End the call. Never discuss the opportunity with someone else.
            **Good Time?**
            **Say:** "This call is regarding a job opportunity to you. Is this the right time to speak with you?"
            **Consultant may say:**
            - **They say:** "Yes, go ahead." **You say:** Continue with the Screening tab.
            - **They say:** "I am busy right now." or "I am driving." **You say:** "Sorry to disturb you. May I know what would be the right time to reach you?"
            - **They say:** "Tomorrow at 10 a.m." **You say:** "Is that 10 a.m. Central Time? I will call you then. Thank you, have a nice day."
            - **They say:** "I am not interested." **You say:** "No problem. Thank you for your time. Have a nice day." Do not push.
            **Record in CRM:**
            - Name confirmed, or wrong number.
            - Right time to talk: yes, or call back.
            - Call-back date, time and time zone, with a reminder set.
            **Tip:** Pause after the right-time question and let the consultant answer. If you agree a call-back, call at exactly that time.
            TEXT],
            ['script', 'Screening', <<<'TEXT'
            **Job Search**
            **Say:** "We are a U.S. IT consulting firm working with consultants and clients across the U.S."
            **Say:** "Are you looking for any job opportunity or job change?"
            **Consultant may say:**
            - **They say:** "Yes, I am looking." **You say:** "Okay, that sounds good. Let me ask a few quick questions so I understand your situation."
            - **They say:** "I am already working." **You say:** "That is great. May I still ask a few questions? Some of our services also support working consultants."
            - **They say:** "Not right now." **You say:** "No problem. May I contact you again later?" Record the answer.
            **Visa / Status**
            **Say:** "Let me know your visa status."
            **Say:** "What is the expiry date of your visa?"
            **Consultant may say:**
            - **They say:** "I am on OPT until August next year." **You say:** "Okay, thank you." Record the status and the exact EAD end date.
            - **They say:** "I am on STEM OPT." **You say:** "Okay. What is the end date on your EAD card?"
            - **They say:** "My application is still pending." **You say:** "Okay, thank you for letting me know." Record it as pending for HR.
            - **They say:** "Why do you need my visa details?" **You say:** "I only need your status and dates to understand which services fit you. Our HR team verifies documents later, through the official process."
            **Tip:** Record facts only. Never advise the consultant on immigration filings or what to do with their status. Refer those questions to HR.
            **Education**
            **Say:** "When did you finish your master's?"
            **Consultant may say:**
            - **They say:** "I finished my master's in May." **You say:** "Okay, thank you." Note the degree and university if they mention them.
            - **They say:** "I am graduating next semester." **You say:** "Okay, thank you. Let me note that." Record the expected month.
            **Location**
            **Say:** "Where are you located?"
            **Consultant may say:**
            - **They say:** "I am in Chicago." **You say:** "Okay, thank you." Record the city and state. It also tells you the time zone.
            **Employment Situation**
            **Say:** "Are you working at the moment, or are you looking for your first position?"
            **Consultant may say:**
            - **They say:** "I am not working yet." **You say:** "Okay, thank you. How long have you been looking?"
            - **They say:** "I am working, but my project is ending." **You say:** "Okay. When does it end?" Record the date.
            - **They say:** "I am working on a project now." **You say:** "Okay, thank you. Who handles your payroll at the moment?" Record the answer.
            **Technology**
            **Say:** "So what kind of technology are you working in, or interested in?"
            **Consultant may say:**
            - **They say:** "SQL development." **You say:** "Okay, thank you. How many years of experience do you have with it?"
            - **They say:** "I am not sure." **You say:** "No problem. What did you study in your master's, and which subjects did you enjoy?" Note it for your sales manager.
            **Record in CRM:**
            - Looking for a job: yes, already working, or not now.
            - Visa status and the exact EAD end date, with the month name.
            - Master's completion month and year.
            - Current city and state.
            - Employment situation, and the project end date if any.
            - Technology and years of experience.
            TEXT],
            ['script', 'Our Services', <<<'TEXT'
            **Understand Requirement**
            **Ask:** "Before I explain our services, may I understand what you need right now?"
            **Consultant may say:**
            - **They say:** "I need an offer letter." **You say:** "Okay. Let me explain our Offer Letter Service and what it covers." Go to the Offer Letter Service tab.
            - **They say:** "I need payroll." **You say:** "Okay. Let me explain our Payroll Service and the exact amounts." Go to the Payroll Service tab.
            - **They say:** "I need projects." **You say:** "Okay. Our C2C marketing team markets consultant profiles and submits them to relevant opportunities. Let me explain how it works."
            - **They say:** "I need training first." **You say:** "Okay. We provide technical training and other training programs. Let me explain."
            - **They say:** "I am not sure what I need." **You say:** "No problem. Let me give you an overview of our services, and we can see what fits."
            **Tip:** Explain services only after you understand the consultant's requirement, technology, work authorization and employment situation.
            **About VSP Group**
            **Say:** "See, we are a growing consulting and technology services company. Our company, VSP Group, was established in the year 2018, and our CEO is Mr. Vikram Sagar Pasala. We were named one of America's Most Promising Companies by Forbes Magazine in the year 2024."
            **Service Overview**
            **Say:** "Our core services are our Offer Letter Service and our Payroll Service. Along with these, we provide C2C marketing, technical training and other training programs, job support, interview preparation and resume building."
            **Record in CRM:**
            - The consultant's requirement: offer letter, payroll, projects, training, or not sure.
            - The services you explained.
            TEXT],
            ['reference', 'Your Service Model', <<<'TEXT'
            | Service | What it includes | Price |
            | --- | --- | --- |
            | **1. Offer Letter Service (core)** | Employment and offer documentation for eligible candidates; onboarding support; related employment documentation | **$500** per eligible candidate |
            | **2. Payroll Service (core)** | Payroll amount of $1,934.17 plus a service charge of $325.83 | **$2,260** total |
            | 3. C2C Marketing | Marketing consultant profiles; submitting profiles to relevant opportunities; client and account coordination; following up on opportunities | Confirm with your sales manager |
            | 4. Training | Technical training; additional training programs; project preparation | Confirm with your sales manager |
            | 5. Job Support & Interview Preparation | Interview preparation; mock interviews; technical and job support; interview coordination | Confirm with your sales manager |
            | 6. Resume Building | Resume preparation; resume optimization; profile presentation | Confirm with your sales manager |
            TEXT],
            ['script', 'Offer Letter Service', <<<'TEXT'
            **Price:** $500 per eligible candidate
            **What it includes:**
            - Employment and offer documentation for eligible candidates.
            - Onboarding support.
            - Related employment documentation.
            **Say:** "Our Offer Letter Service is $500 per eligible candidate. It covers the employment and offer documentation, onboarding support, and the related employment documentation."
            **Consultant may say:**
            - **They say:** "Will this offer letter get my OPT or EAD approved?" **You say:** "No company can promise that. The offer letter is employment documentation. Work authorization is decided by your DSO and USCIS."
            - **They say:** "Who is eligible?" **You say:** "Our HR team checks eligibility before anything is issued. I will share your details with them."
            - **They say:** "What happens after I pay?" **You say:** "My sales manager will explain the exact process and timelines on your call, and you will get the details in writing."
            **Record in CRM:**
            - Offer Letter Service explained: yes.
            - Consultant's interest: interested, thinking, or not interested.
            - Eligibility questions passed to HR.
            **Never:** Promise immigration approval, a guaranteed job or a guaranteed project. Never describe an offer letter as a work permit.
            TEXT],
            ['script', 'Training & Support', <<<'TEXT'
            **Training**
            **Say:** "We provide technical training, additional training programs, and project preparation to get you ready for real project work."
            **Consultant may say:**
            - **They say:** "How long is the training?" **You say:** "It depends on the technology and the program. My sales manager will share the exact schedule."
            - **They say:** "Is the training online or in person?" **You say:** "Let me confirm the current options for your technology, and I will include them in my mail."
            **Resume Building**
            **Say:** "We help with resume preparation, resume optimization and how your profile is presented to clients."
            **Job / Interview Support**
            **Say:** "We provide interview preparation, mock interviews, technical and job support, and interview coordination."
            **Consultant may say:**
            - **They say:** "Will you guarantee I clear the interview?" **You say:** "No one can guarantee that, but we prepare you well with mock interviews and support at every step."
            **Record in CRM:**
            - Training needed: yes or no, and the technology.
            - Resume building needed: yes or no.
            - Job or interview support needed: yes or no.
            **Tip:** Present these as support for the core services. Never promise a result from training or interview preparation.
            TEXT],
            ['script', 'C2C Marketing', <<<'TEXT'
            **Say:** "Through C2C marketing, our team markets your profile, submits it to relevant opportunities, coordinates with clients and accounts, and follows up on each opportunity."
            **Consultant may say:**
            - **They say:** "How soon will I get a project?" **You say:** "I cannot promise a date or a project. Our marketing team works on every opportunity and keeps you updated at every step."
            - **They say:** "Which clients do you work with?" **You say:** "We work with clients all over the USA. My sales manager can explain the opportunities for your technology."
            **Record in CRM:**
            - C2C marketing explained: yes.
            - Preferred locations and roles.
            **Never:** Promise a project, a client, a rate or a start date.
            TEXT],
            ['script', 'Payroll Service', <<<'TEXT'
            **Price:** $2,260 total
            **Breakdown:**
            - Payroll amount: $1,934.17
            - Service charge: $325.83
            - Total payroll service: $2,260.00
            **Calculation:** $2,260 − $1,934.17 = $325.83
            **Say:** "For our payroll service, the total payroll service amount is $2,260. The applicable payroll amount is $1,934.17, and the remaining $325.83 is the service charge."
            **Consultant may say:**
            - **They say:** "Why is there a service charge?" **You say:** "The service charge of $325.83 covers our payroll processing. The applicable payroll amount is $1,934.17, and together they make the total of $2,260."
            - **They say:** "Can you explain the amounts again?" **You say:** "Of course. The total is $2,260. Of that, $1,934.17 is the payroll amount and $325.83 is the service charge." Say each number slowly.
            - **They say:** "Does payroll guarantee a project or my visa?" **You say:** "No. Payroll does not guarantee a project, employment or any immigration approval."
            **Interested?**
            **Ask:** "Based on what you need, which of our services would you like to go ahead with?"
            **Consultant may say:**
            - **They say:** "This sounds good. I am interested." **You say:** "That is great." Go to Resume & Referral.
            - **They say:** "I have some questions first." **You say:** Answer from the Questions / Objections tab, then ask again.
            - **They say:** "Let me think about it." **You say:** "Of course. I will send you our company details today, and I can call you on Friday. Does that work?"
            - **They say:** "I am not interested." **You say:** "No problem. Thank you for your time." Go to Closing.
            **Record in CRM:**
            - Payroll Service explained, with the exact amounts.
            - Services the consultant is interested in.
            - Interest: interested, has questions, thinking, or not interested.
            **Never:** Round the figures or quote a different amount. Use exactly $2,260, $1,934.17 and $325.83.
            TEXT],
            ['script', 'Questions / Objections', <<<'TEXT'
            **Consultant may say:**
            - **They say:** "Why should I choose VSP Group?" **You say:** "We offer a complete service model: our Offer Letter and Payroll services, plus C2C marketing, training, job support, interview preparation and resume building. We have a large marketing team, direct clients all over the USA, and an internal team of HR, legal, finance and account managers."
            - **They say:** "How do I know your company is genuine?" **You say:** "That is a very fair question. VSP Group is a consulting and technology services company, and our CEO is Mr. Vikram Sagar Pasala. I will send you an email right now from my official company address, with our website and my LinkedIn profile, so you can check us at your own pace."
            - **They say:** "Is the offer letter enough for my work authorization?" **You say:** "No. An offer letter is employment documentation. It does not by itself give or guarantee work authorization. Your DSO and USCIS decide that."
            - **They say:** "Do you guarantee placement?" **You say:** "No company can honestly guarantee placement. What we guarantee is that our team works on your profile and keeps you updated at every step."
            - **They say:** "Will you file my H-1B?" **You say:** "I understand H-1B is important for your plans. Our company offers H-1B and Green Card sponsorship under its policy. I want to be honest: no company can guarantee H-1B, because the selection is done by the government. Our HR team can explain the policy in detail. Shall I arrange that?"
            - **They say:** "Why do you need my EAD?" **You say:** "On this call I only need your status and dates. Our HR team verifies work authorization through the official onboarding process. Please do not send documents by text or chat."
            - **They say:** "Can I speak with your manager?" **You say:** "Of course. That is our next step anyway. What is your available time to talk with my sales manager? I will send you a confirmation."
            **If you do not know the answer:** Say: "That is a good question. Let me confirm with my sales manager and get back to you." Never guess.
            **Record in CRM:**
            - Each question or objection, and how you answered it.
            - Anything you promised to confirm, with a follow-up date.
            **Never:** Promise immigration approval, guaranteed employment, guaranteed placement, H-1B, a Green Card, a project, a date or a rate.
            TEXT],
            ['script', 'Resume & Referral', <<<'TEXT'
            **Company Mail**
            **Say:** "I will send you a mail with our company details today."
            **Resume**
            **Say:** "Please send me your most updated resume."
            **Consultant may say:**
            - **They say:** "I will send it tonight." **You say:** "Thank you. My official email address will be in the mail I send you."
            - **They say:** "My resume is old." **You say:** "No problem. Please send your most recent one. Our resume building service can help improve it."
            **Referral**
            **Say:** "Please refer your friends also. For each referral you will get a referral amount of $750. The more friends you refer, the more benefits you will get from our company."
            **Consultant may say:**
            - **They say:** "How is the referral paid?" **You say:** "Let me confirm the current terms with my team, and I will include them in my mail."
            **Record in CRM:**
            - Company mail sent: date and time.
            - Resume promised by (date), or received.
            - Referral names and phone numbers, if shared.
            **Tip:** Ask for the resume by email only, never through personal messaging apps.
            TEXT],
            ['script', 'Follow-up', <<<'TEXT'
            **Start Date**
            **Say:** "When would you like to get started?"
            **Consultant may say:**
            - **They say:** "As soon as possible." **You say:** "Okay, thank you. I will let my sales manager know."
            - **They say:** "I am working now, so I need to give notice." **You say:** "Okay. How many weeks of notice do you need to give your current employer?"
            **Sales Manager Call**
            **Say:** "What is your available time to talk with my sales manager?"
            **Consultant may say:**
            - **They say:** "Thursday at 11 a.m. Eastern." **You say:** "Perfect. I will send you a confirmation with the time and my manager's name."
            - **They say:** "I am not sure yet." **You say:** "No problem. Can I call you tomorrow to fix a time?"
            **Follow-up Call**
            **Say:** "Hi Priya, this is Kiran from VSP Group, following up on our call. Did you get a chance to send your resume?"
            **Record in CRM:**
            - Preferred start date, or notice period.
            - Sales manager call: date, time and time zone.
            - Next follow-up date, with a reminder set.
            - Call notes, the requirement and the services discussed, shared with your sales manager before their call.
            **Tip:** Always say whose time zone a time is in. Never promise what the sales manager will offer.
            TEXT],
            ['script', 'Closing', <<<'TEXT'
            **Summarise**
            **Say:** "So, I will send you a mail with our company details today. Please send me your most updated resume, and I will set up your call with my sales manager on Thursday at 11 a.m. Eastern."
            **Close**
            **Say:** "Thank you! Have a nice day. Bye."
            **Record in CRM:**
            - Call outcome: interested, call back, not interested, or wrong number.
            - Services discussed, and the next step with its date.
            - A short call summary, typed before your next call.
            **Tip:** Never end abruptly once you have the information. Close with a summary, a next step and a thank you.
            TEXT],
            ['script', 'Voicemail', <<<'TEXT'
            **Say:** "Hi, this is Ajay calling from VSP Group. This call is regarding a job opportunity for you. I would appreciate it if you call me back at 9515708888, extension 999. Thank you. Bye, have a nice day."
            **Before you leave it:**
            - Use your own name, and the call-back number and extension assigned to you.
            - Say the number slowly, and repeat it once.
            - Keep it under 30 seconds.
            **Record in CRM:**
            - Voicemail left: date and time.
            - Next call attempt date.
            **Never:** Leave visa, pricing or personal details in a voicemail.
            TEXT],
            ['example', 'Complete Call Script', <<<'TEXT'
            **Recruiter:** Greetings of the day! Hi, this is Kiran calling from VSP Group.
            **Recruiter:** Am I speaking to Priya?
            **Consultant:** Yes, this is Priya.
            **Recruiter:** How are you doing, Priya?
            **Consultant:** I am good, thank you.
            **Recruiter:** This call is regarding a job opportunity to you. Is this the right time to speak with you?
            **Consultant:** Yes, go ahead.
            **Recruiter:** We are a U.S. IT consulting firm working with consultants and clients across the U.S. Are you looking for any job opportunity or job change?
            **Consultant:** Yes.
            **Recruiter:** Let me know your visa status.
            **Consultant:** I am on OPT.
            **Recruiter:** What is the expiry date of your visa?
            **Consultant:** August next year.
            **Recruiter:** When did you finish your master's?
            **Consultant:** In May.
            **Recruiter:** Where are you located?
            **Consultant:** I am in Chicago.
            **Recruiter:** Are you working at the moment, or are you looking for your first position?
            **Consultant:** I am not working yet.
            **Recruiter:** So what kind of technology are you working in, or interested in?
            **Consultant:** SQL development.
            **Recruiter:** Before I explain our services, may I understand what you need right now?
            **Consultant:** I need an offer letter, and help getting projects.
            **Recruiter:** See, we are a growing consulting and technology services company. Our company, VSP Group, was established in the year 2018, and our CEO is Mr. Vikram Sagar Pasala. We were named one of America's Most Promising Companies by Forbes Magazine in the year 2024.
            **Recruiter:** Our core services are our Offer Letter Service and our Payroll Service. Along with these, we provide C2C marketing, technical training and other training programs, job support, interview preparation and resume building.
            **Recruiter:** Our Offer Letter Service is $500 per eligible candidate. It covers the employment and offer documentation, onboarding support, and the related employment documentation.
            **Consultant:** Will the offer letter get my work authorization approved?
            **Recruiter:** No company can promise that. The offer letter is employment documentation. Work authorization is decided by your DSO and USCIS.
            **Recruiter:** We also provide technical training and project preparation, resume building, and job support with interview preparation and mock interviews.
            **Recruiter:** Through C2C marketing, our team markets your profile, submits it to relevant opportunities, coordinates with clients and accounts, and follows up on each opportunity. I cannot promise a date or a project, but we keep you updated at every step.
            **Recruiter:** For our payroll service, the total payroll service amount is $2,260. The applicable payroll amount is $1,934.17, and the remaining $325.83 is the service charge.
            **Recruiter:** Based on what you need, which of our services would you like to go ahead with?
            **Consultant:** The offer letter and C2C marketing sound good. I am interested.
            **Recruiter:** That is great. I will send you a mail with our company details today. Please send me your most updated resume.
            **Recruiter:** Please refer your friends also. For each referral you will get a referral amount of $750. The more friends you refer, the more benefits you will get from our company.
            **Recruiter:** When would you like to get started?
            **Consultant:** As soon as possible.
            **Recruiter:** What is your available time to talk with my sales manager?
            **Consultant:** Thursday at 11 a.m. Eastern.
            **Recruiter:** Perfect. Thank you! Have a nice day. Bye.
            **Voicemail, if the consultant does not answer:**
            **Recruiter:** Hi, this is Ajay calling from VSP Group. This call is regarding a job opportunity for you. I would appreciate it if you call me back at 9515708888, extension 999. Thank you. Bye, have a nice day.
            TEXT],
        ],
        'Understand the consultant first, then explain our services with Offer Letter ($500) and Payroll ($2,260: $1,934.17 payroll plus $325.83 service charge) as the core. Quote only approved figures, never promise approval, employment or placement, record everything in the CRM, and end with a clear next step.',
    ),
];
