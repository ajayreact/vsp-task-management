<?php

/*
 * "Explaining the Opportunity — Complete Services Guide": the company and the
 * full service model in one playbook lesson. Offer Letter and Payroll are the
 * core services and quote the approved prices; the other services support
 * them. Company facts are marked for management confirmation.
 */

return [
    'title' => 'Explaining the Opportunity — Complete Services Guide',
    'from' => 'Introducing VSP Group',
    'compliance' => true,
    'review' => $confirm('VSP Group established in 2018 (another company page says 2010), CEO Mr. Vikram Sagar Pasala, Forbes America\'s Most Promising Companies 2024 (another page says ranked number 57), headquarters in New Jersey and Texas (another page lists Atlanta, Georgia), the Offer Letter Service at $500 per eligible candidate, the Payroll Service at $2,260 total (payroll amount $1,934.17 and service charge $325.83), in-class training of 4 to 5 weeks with accommodation, resume building, job and interview support, C2C marketing, and direct clients all over the USA').' Legal review: earlier course content said offer letters are never sold and that a paid service must never be presented as buying an offer letter. Confirm the approved wording for the Offer Letter Service, how payments are taken, and that an offer letter only documents genuine employment.',
    'en' => $lesson(
        'Explain VSP Group and our complete service model clearly and honestly: Offer Letter and Payroll as the core services, supported by training, resume building, job and interview support and C2C marketing, using only approved facts and prices.',
        [
            ['note', 'Rules for Explaining Our Services', <<<'TEXT'
            - Explain services only after you understand the consultant's requirement, technology, work authorization and employment situation.
            - **Offer Letter Service and Payroll Service are our core services.** Quote only the approved prices: $500 per eligible candidate, and $2,260 total for payroll ($1,934.17 payroll amount plus $325.83 service charge).
            - An offer letter documents genuine employment. It does not by itself create or guarantee work authorization, and it is never described as buying a job, a work permit or sponsorship.
            - Recruiters never collect money personally. Every payment follows the company's official written process.
            - Never promise immigration approval, guaranteed employment, a guaranteed project or guaranteed placement.
            - Use only company facts management has confirmed, the same way on every call. Name a client only with management's approval.
            TEXT],
            ['flow', 'Explanation Flow', <<<'TEXT'
            1. Understand Requirement. What the consultant needs before you explain anything. [[Understand Requirement]]
            2. Introduce VSP Group. Confirmed company facts only. [[Company Introduction]]
            3. Service Overview. Core services first, then the support services. [[Service Overview]]
            4. **Core service:** Offer Letter — $500. Per eligible candidate. [[Offer Letter Service]]
            5. Training. Technical training and project preparation. [[Training]]
            6. Resume Building. Real experience, clearly presented. [[Resume Building]]
            7. Job / Interview Support. Preparation, mock interviews, coordination. [[Job & Interview Support]]
            8. C2C Marketing. Profile marketing and client coordination. [[C2C Marketing]]
            9. **Core service:** Payroll — $2,260. Payroll $1,934.17 plus service charge $325.83. [[Payroll Service]]
            10. Check Understanding? Ask what the consultant thinks. [[Check Understanding?]]
            11. Next Steps. Mail, resume and the sales manager call. [[Next Steps]]
            TEXT],
            ['script', 'Company Introduction', <<<'TEXT'
            **Say:** "See, we are a growing consulting and technology services company. Our company, VSP Group, was established in the year 2018, and our CEO is Mr. Vikram Sagar Pasala. We were named one of America's Most Promising Companies by Forbes Magazine in the year 2024."
            **Consultant may say:**
            - **They say:** "I have never heard of your company." **You say:** "That is fair. I will email you our website and my LinkedIn profile from my official address, so you can check us at your own pace."
            - **They say:** "Where are you located?" **You say:** "Our headquarters are in New Jersey and Texas." Use only the confirmed locations.
            **Record in CRM:**
            - Company introduction given.
            - Any doubts the consultant raised.
            **Never:** Add facts that are not in the confirmed introduction, or give different facts on different calls. Candidates check them online.
            TEXT],
            ['script', 'Service Overview', <<<'TEXT'
            **Understand Requirement**
            **Ask:** "Before I explain our services, may I understand what you need right now?"
            **Consultant may say:**
            - **They say:** "I need an offer letter." **You say:** "Okay. Let me explain our Offer Letter Service and what it covers."
            - **They say:** "I need payroll." **You say:** "Okay. Let me explain our Payroll Service and the exact amounts."
            - **They say:** "I need projects." **You say:** "Okay. Let me explain how our C2C marketing team works."
            - **They say:** "I need training first." **You say:** "Okay. Let me explain our training."
            **Our Services**
            **Say:** "Our core services are our Offer Letter Service and our Payroll Service. Along with these, we provide C2C marketing, technical training and other training programs, job support, interview preparation and resume building."
            **Record in CRM:**
            - The consultant's requirement.
            - The services you explained.
            **Tip:** Lead with the service that matches the requirement, then mention the others briefly.
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
            - **They say:** "Will this get my OPT or EAD approved?" **You say:** "No company can promise that. The offer letter is employment documentation. Work authorization is decided by your DSO and USCIS."
            - **They say:** "Who is eligible?" **You say:** "Our HR team checks eligibility before anything is issued. I will share your details with them."
            - **They say:** "How do I pay?" **You say:** "Payments only go through our official company process, which my sales manager explains in writing. I never collect payments myself."
            **Record in CRM:**
            - Offer Letter Service explained, with the price.
            - Interest, and any eligibility questions passed to HR.
            **Never:** Describe an offer letter as a work permit, or promise approval, a job or a project.
            TEXT],
            ['script', 'Training', <<<'TEXT'
            **What We Offer**
            **Say:** "We provide technical training, additional training programs, and project preparation."
            **Location and Duration**
            **Say:** "Our company, VSP Group, has headquarters in New Jersey and Texas. From there you will get in-class training. The training period would be around 4 to 5 weeks."
            **Accommodation**
            **Say:** "We will provide you accommodation at our headquarters. We will allot you rooms for staying."
            **Consultant may say:**
            - **They say:** "Is there any cost for accommodation?" **You say:** "Let me confirm the current terms with HR, and I will send them to you in writing."
            - **They say:** "Will I get a job after training?" **You say:** "Training prepares you for project work. I cannot promise a job, but our team supports you with resume building, interview preparation and marketing."
            **Record in CRM:**
            - Training needed, and the technology.
            - Location and accommodation questions.
            **Tip:** For OPT consultants, HR and compliance decide how training fits their work authorization.
            TEXT],
            ['script', 'Resume Building', <<<'TEXT'
            **Say:** "We help with resume preparation, resume optimization and how your profile is presented to clients."
            **Consultant may say:**
            - **They say:** "Can you add more experience to my resume?" **You say:** "No. Your resume must show your real education, training and experience. We help you present it clearly."
            - **They say:** "When will my resume be ready?" **You say:** "My sales manager will confirm the timeline on your call."
            **Record in CRM:**
            - Resume building needed.
            - Resume received, and its date.
            **Never:** Invent experience, employers, dates or skills.
            TEXT],
            ['script', 'Job & Interview Support', <<<'TEXT'
            **Say:** "We provide interview preparation, mock interviews, technical and job support, and interview coordination."
            **Consultant may say:**
            - **They say:** "Will you guarantee I clear the interview?" **You say:** "No one can guarantee that, but we prepare you well with mock interviews and support at every step."
            - **They say:** "Do you help once I am on a project?" **You say:** "Yes, job support is part of our services. My sales manager can explain how it works for your technology."
            **Record in CRM:**
            - Interview preparation or job support needed.
            **Never:** Promise interview results or offers.
            TEXT],
            ['script', 'C2C Marketing', <<<'TEXT'
            **Say:** "Through C2C marketing, our team markets your profile, submits it to relevant opportunities, coordinates with clients and accounts, and follows up on each opportunity."
            **Client Opportunities**
            **Say:** "We are having direct clients all over the USA."
            **Consultant may say:**
            - **They say:** "Which clients?" **You say:** "We work across industries such as banking and retail. My sales manager can explain the opportunities for your technology." Name a client only with approval.
            - **They say:** "How soon will I get a project?" **You say:** "I cannot promise a date or a project. Our marketing team works on every opportunity and keeps you updated at every step."
            **Record in CRM:**
            - C2C marketing explained.
            - Preferred locations, roles and industries.
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
            **Record in CRM:**
            - Payroll Service explained, with the exact amounts.
            **Never:** Round the figures or quote a different amount.
            TEXT],
            ['script', 'Next Steps', <<<'TEXT'
            **Check Understanding?**
            **Ask:** "Based on what you need, which of our services would you like to go ahead with?"
            **Consultant may say:**
            - **They say:** "I am interested." **You say:** "That is great. Here is what happens next."
            - **They say:** "I have questions." **You say:** Answer from the Candidate Questions & Objections lesson, then ask again.
            - **They say:** "Let me think about it." **You say:** "Of course. I will send you our company details today, and call you on Friday. Does that work?"
            **What Happens Next**
            **Say:** "Here is what happens next. I will email you our company details today. Please reply with your most updated resume. Then I will set up a call with my sales manager. What time suits you for that call?"
            **Record in CRM:**
            - Services the consultant wants.
            - Next step, with its date and time zone.
            TEXT],
        ],
        'Understand first, then explain: Offer Letter ($500) and Payroll ($2,260) as the core, the support services after them, confirmed facts only, no guarantees, and a clear next step.',
    ),
];
