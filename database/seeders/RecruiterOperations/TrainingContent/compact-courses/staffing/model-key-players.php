<?php

/*
 * Combined lesson "U.S. IT Staffing Model & Key Players". One topic per merged lesson:
 * U.S. IT Staffing Basics, Staffing Agency vs Direct Employer, Client, Vendor, Consultant, U.S. Staffing Terminology.
 */

return [
    'title' => 'U.S. IT Staffing Model & Key Players',
    'from' => 'U.S. IT Staffing Basics',
    'compliance' => true,
    'review' => 'Includes topics that were awaiting compliance review: U.S. IT Staffing Basics, Staffing Agency vs Direct Employer, Client, Vendor, Consultant, Staffing Glossary.',
    'en' => [
        ['kind' => 'objective', 'heading' => 'Learning Objective', 'body' => <<<'TEXT'
            Understand how U.S. IT staffing works and who is involved: the staffing company and the direct employer, the client, the vendor chain and the consultant, and the everyday staffing vocabulary used between them.
            TEXT],
        ['kind' => 'flow', 'heading' => 'Lesson Map', 'body' => <<<'TEXT'
            1. US IT Staffing Basics [[U.S. IT Staffing Basics]]
            2. Staffing Agency vs Direct Employer [[Staffing Agency vs Direct Employer]]
            3. Client [[Client]]
            4. Vendor [[Vendor]]
            5. Consultant [[Consultant]]
            6. Staffing Glossary [[Staffing Glossary]]
            7. Checklist & Common Mistakes [[Checklist & Common Mistakes]]
            TEXT],
        ['kind' => 'topic', 'heading' => 'U.S. IT Staffing Basics', 'body' => <<<'TEXT'
            U.S. IT staffing connects client needs with qualified professionals through a clear lifecycle. Your accuracy at each step makes the placement succeed.
            TEXT],
        ['kind' => 'flow', 'heading' => 'The Staffing Chain', 'body' => <<<'TEXT'
            1. End Client. The company where the work is done and that pays for it. [[Client]]
            2. Vendor. A prime vendor or implementation partner between the client and your company. [[Vendor]]
            3. Staffing Company. Finds, screens and supplies qualified professionals. [[Staffing Agency vs Direct Employer]]
            4. Consultant. The professional who does the work. [[Consultant]]
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            U.S. companies often need IT professionals quickly, for a project or for a fixed period, or they want to try someone before hiring permanently. Staffing companies fill these needs by finding, screening and supplying qualified professionals.
            IT staffing covers roles such as software developers, Java and .NET developers, data engineers, data analysts, business analysts, quality assurance testers, cloud and DevOps engineers, and project managers.
            TEXT],
        ['kind' => 'content', 'heading' => 'The main parties', 'body' => <<<'TEXT'
            - The end client is the company where the work is actually done, for example a bank or a retailer.
            - A vendor or implementation partner may sit between the end client and your company.
            - Your company is the staffing company. It may be the employer of the consultant.
            - The consultant is the professional who does the work.
            TEXT],
        ['kind' => 'content', 'heading' => 'The staffing lifecycle', 'body' => <<<'TEXT'
            - A requirement is received from a client or vendor.
            - The recruiter analyses the requirement.
            - The recruiter sources and screens candidates.
            - A qualified candidate is submitted.
            - The client interviews the candidate.
            - The client selects the candidate and an offer or confirmation follows.
            - Onboarding, documentation and payroll setup happen.
            - The consultant starts the project, and the company supports them until the assignment ends.
            TEXT],
        ['kind' => 'content', 'heading' => 'From the company training material: the three parts of US staffing', 'body' => <<<'TEXT'
            The US MNC Staffing document divides staffing into three parts.
            Recruiting, defined as sourcing consultants to the client's requirements, or providing resources to meet the client's needs. This is your main role.
            Sales or marketing, defined as marketing the company's bench consultants to vendors and clients.
            Business development, handled by Business Development Managers, called BDMs, senior recruiters and leadership. Business development collects requirements from clients or from preferred vendors and assigns them to the right recruiter or team, based on technology or skill set.
            TEXT],
        ['kind' => 'content', 'heading' => 'The recruiting process in the company material', 'body' => <<<'TEXT'
            The same document teaches seven steps. Receive the requirement from the BDM. Understand the requirement. Fetch resumes. Call the consultant. Call the consultant's employer, if they have one. Complete the submission. Follow up on the submission feedback.
            The Job Description Analysis training summarises the same flow in four steps: get the requirement from the Account Manager or BDM, identify sources, identify qualified people, and communicate with them. The Job Requirement Analysis and Sourcing & Resume Screening courses teach each step in detail.
            TEXT],
        ['kind' => 'content', 'heading' => 'Types of engagement', 'body' => <<<'TEXT'
            - Contract means work for a fixed or estimated period.
            - Contract-to-hire means a contract that may convert to a permanent role.
            - Full-time or permanent means the client hires the person directly.
            - Each is covered in detail in the Employment Types, W2/C2C & Rates lesson.
            TEXT],
        ['kind' => 'content', 'heading' => 'Where OPT recruiters fit', 'body' => <<<'TEXT'
            OPT recruiters focus on candidates who are on, or about to be on, OPT or STEM OPT. They help these candidates find U.S. IT roles that match their degree and skills, and they hand off accurate information to HR and payroll.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            A vendor sends a requirement for a junior Python developer in Dallas for twelve months. You identify an OPT candidate with a computer science master's degree and Python project experience. You screen her, confirm details, submit her through your team's process, and track the interview and outcome.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Staffing Agency vs Direct Employer', 'body' => <<<'TEXT'
            Know who the employer is, explain it clearly, and let HR handle any immigration questions about the arrangement.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            A direct employer hires people to work for its own business. For example, a bank hires a software engineer onto its own payroll.
            A staffing agency, also called a staffing company or consulting company, recruits professionals and places them at client companies. In many U.S. IT arrangements, the staffing company is the legal employer. It runs payroll, while the consultant works on the client's project.
            TEXT],
        ['kind' => 'content', 'heading' => 'How the two models compare', 'body' => <<<'TEXT'
            - With a direct employer, the employee is on the client's payroll and follows the client's HR policies.
            - With a staffing agency, the consultant is often on the staffing company's payroll and follows its HR policies, while working on the client's project.
            - Interviews are usually conducted by the client in both models.
            - Benefits and pay are set by whoever is the employer.
            TEXT],
        ['kind' => 'content', 'heading' => 'Why it matters for OPT candidates', 'body' => <<<'TEXT'
            OPT candidates must report their employer. Accurate information about who the employer is matters.
            For STEM OPT, the employer must meet specific requirements, including E-Verify and the I-983 training plan, and must have a genuine employer-employee relationship with the student. Whether a particular arrangement fits is decided by HR and compliance, not by recruiters.
            Candidates often ask: Who will be my employer? Answer accurately using approved company information.
            TEXT],
        ['kind' => 'content', 'heading' => 'From the company training material', 'body' => <<<'TEXT'
            The US MNC Staffing document defines the employer simply as the consultant's company, the company whose payroll the consultant is on. When you call a consultant who already works for another company, that company is the employer, and the document teaches you to contact the employer as well before submitting, because the employer must agree to the rate and sign the paperwork.
            The document also lists terms every recruiter must know before starting: consultant, employer, vendor, preferred vendor or Tier 1 layer, client, blue chip companies, implementation partner, types of visas, payment terms, NDA, NCA, right to represent and MSA. A blue chip company is a large, well-established and financially strong company. An implementation partner is a consulting firm delivering a project for the end client. The other terms are explained in the Vendor, Submission and U.S. Staffing Terminology lessons.
            TEXT],
        ['kind' => 'content', 'heading' => 'Explaining it to candidates', 'body' => <<<'TEXT'
            A simple explanation: Our company is your employer. We run your payroll and provide HR support. You will work on a project for our client, and the client will interview you. Use this only if it is true for your company and the role.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            A candidate asks: Will I be working for the bank or for you? You confirm with your team that your company is the employer of record for this role. You explain that the bank is the end client where she will work on the project, and your company handles her employment, payroll and HR.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Client', 'body' => <<<'TEXT'
            The client is the reason the requirement exists. Protect their information, respect their rules, and send only accurate, qualified profiles.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            The client is the company that needs the work done and pays for it. In staffing, people usually mean the end client, the organisation where the consultant actually works, such as a bank, an insurance company, a retailer or a technology firm.
            Sometimes your company works directly with the end client. Often there are one or more vendors in between.
            TEXT],
        ['kind' => 'content', 'heading' => 'Client information in a requirement', 'body' => <<<'TEXT'
            The client name may be shown, hidden, or described generally, for example a leading healthcare client.
            Clients set the skills, experience, location, work mode, duration and interview process.
            Some clients have strict rules about how candidates are submitted, how many submissions are allowed, and whether a candidate can be submitted by more than one company.
            TEXT],
        ['kind' => 'content', 'heading' => 'Confidentiality', 'body' => <<<'TEXT'
            - Client names and requirement details are often confidential. Share them with candidates only as your company process allows.
            - Never post client requirements publicly without approval.
            - Never share one client's information with another client or vendor.
            TEXT],
        ['kind' => 'content', 'heading' => 'Duplicate submissions', 'body' => <<<'TEXT'
            If a candidate has already been submitted to the same client for the same role by another company, a second submission can create conflict and may disqualify the candidate. Always ask the candidate whether they have already applied or been submitted to that client.
            TEXT],
        ['kind' => 'content', 'heading' => 'From the company training material', 'body' => <<<'TEXT'
            The OPT Recruiter Training Material and the calling section list direct clients, naming Accenture, Capgemini, Avanade, iGate, T-Mobile USA, Tesoro, HCL, Infosys, Apple and Pepsi. Company-specific process: client relationships change, and some of these names may be implementation partners or past clients rather than current direct clients. Verify with HR or authorized personnel before you name any client to a candidate, and never say a candidate will work for a client before a real requirement and selection exist.
            TEXT],
        ['kind' => 'content', 'heading' => 'Client expectations', 'body' => <<<'TEXT'
            Clients expect accurate profiles, honest information, quick responses, and candidates who attend interviews on time.
            A wrong submission damages the relationship for the whole team.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            A requirement says: Client, a top retail company in Minneapolis. A candidate asks for the client's name. Your process allows sharing the name only after the candidate agrees to be submitted. You explain this politely, confirm his interest, and then share the client name according to your process.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Vendor', 'body' => <<<'TEXT'
            Vendors are partners in the chain to the client. Fast, accurate and rule-following submissions build trust and bring more requirements.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            A vendor is a company in the supply chain between the end client and your company. Vendors receive requirements from the client, or from another vendor, and share them with staffing partners.
            You will hear several related terms.
            A prime vendor or managed service provider has a direct contract with the end client.
            An implementation partner is a consulting firm delivering a project for the end client, which may need consultants.
            A sub-vendor works under another vendor.
            A vendor management system is an online platform some clients use to manage requirements and submissions.
            TEXT],
        ['kind' => 'content', 'heading' => 'From the company training material', 'body' => <<<'TEXT'
            The US MNC Staffing document defines a vendor as the company or person that sends requirements to us, or that submits consultant details to us. So the word works in both directions: a vendor can be above you, sending requirements, or beside you, offering their own bench consultants.
            A preferred vendor, also called the Tier 1 layer, is a vendor with a direct, approved relationship with the client. Requirements that come from a preferred vendor are closer to the client and are usually more reliable.
            The document also teaches that recruiters send requirements to their vendor list, a practice called mass mailing, so that other vendors can offer matching consultants on a Corp to Corp basis. Agreements with vendors are usually covered by a Master Services Agreement, called an MSA.
            TEXT],
        ['kind' => 'content', 'heading' => 'The vendor chain', 'body' => <<<'TEXT'
            A common chain is end client, then prime vendor or implementation partner, then your company, then the consultant. Each layer has its own agreements and rates. The longer the chain, the lower the rate that reaches the consultant, and the more coordination is needed.
            TEXT],
        ['kind' => 'content', 'heading' => 'Working with vendors', 'body' => <<<'TEXT'
            - Respond quickly and professionally to vendor requirements.
            - Read every requirement carefully, including submission rules.
            - Send complete, accurate profiles in the format the vendor requests.
            - Keep vendors updated on candidate availability and interview schedules.
            - Follow up politely on feedback.
            TEXT],
        ['kind' => 'content', 'heading' => 'Rules to respect', 'body' => <<<'TEXT'
            Many vendors require a right to represent, a written confirmation from the candidate that your company may submit them for a specific role. This prevents duplicate submissions.
            Vendors may ask for specific details, such as work authorization type and location. Share only what your company process allows.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            A vendor emails a requirement for a QA automation engineer and asks for submissions with a right to represent within four hours. You shortlist two candidates, confirm their interest and availability, obtain written right to represent through your approved process, and submit both profiles with accurate summaries before the deadline.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Consultant', 'body' => <<<'TEXT'
            Consultants are the people your work serves. Honest communication and reliable follow-up keep them successful and loyal.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            In U.S. IT staffing, a consultant is a professional placed at a client to work on a project. The word is used for contractors at every level, from junior developers to senior architects.
            For OPT recruiters, consultants are usually recent graduates or early-career professionals on OPT or STEM OPT.
            TEXT],
        ['kind' => 'content', 'heading' => 'From the company training material', 'body' => <<<'TEXT'
            The US MNC Staffing document defines the consultant as the resource for the client's requirement, in other words, the person you are sourcing for the client's need. It also explains that W2 consultants are treated as the company's own bench consultants, and that for these consultants the company bears insurance claims, taxes, overheads and some benefits.
            TEXT],
        ['kind' => 'content', 'heading' => 'Consultant, candidate and employee', 'body' => <<<'TEXT'
            - A candidate is someone being considered for a role.
            - A consultant is someone placed on a project.
            - An employee is someone on a company's payroll. A consultant may be your company's employee while working at a client.
            TEXT],
        ['kind' => 'content', 'heading' => 'What consultants expect', 'body' => <<<'TEXT'
            - Clear information about the role, the client, the location, the rate or salary, and the start date.
            - Honest answers, even when the answer is I will check and get back to you.
            - Timely updates on interviews and decisions.
            - Support with onboarding, documentation and payroll questions, from the right teams.
            - Respectful treatment and privacy.
            TEXT],
        ['kind' => 'content', 'heading' => 'Recruiter responsibilities to consultants', 'body' => <<<'TEXT'
            - Set honest expectations from the first call.
            - Keep consultants informed at each step.
            - Hand off accurately to HR and payroll.
            - Respond to messages within a reasonable time.
            - Escalate problems early instead of hiding them.
            TEXT],
        ['kind' => 'content', 'heading' => 'Consultant concerns', 'body' => <<<'TEXT'
            Consultants may worry about their work authorization dates, payroll timing, project continuation and future plans such as STEM OPT or H-1B. Listen carefully, record the concern, and hand it to the right team. Never promise outcomes you do not control.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            A consultant who started last week messages you: I have not received any information about my first pay date. You do not guess. You check with payroll, then reply with the confirmed pay schedule and the contact for future payroll questions.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Staffing Glossary', 'body' => <<<'TEXT'
            Fluent staffing vocabulary makes you faster and more credible. Keep learning terms and explain them simply to candidates.
            TEXT],
        ['kind' => 'content', 'heading' => 'Key terms', 'body' => <<<'TEXT'
            Terms with their own tab or lesson (client, vendor, consultant, requirement, submission, interview, placement, bench, W2, C2C, 1099, contract-to-hire and the rate terms) are not repeated here.
            - JD. Job description.
            - End client. The company where the work is done.
            - Prime vendor. A vendor with a direct contract with the end client.
            - Implementation partner. A consulting firm delivering a project for the end client.
            - MSP. Managed service provider, which manages staffing suppliers for a client.
            - VMS. Vendor management system, an online platform for requirements and submissions.
            - Right to represent, or RTR. Written permission from a candidate to submit them for a specific role.
            - Skill matrix. A table summarising how a candidate matches the required skills.
            - Hotlist. A list of available consultants shared with vendors, usually by bench sales teams.
            - Start date. The first day of work.
            - Extension. A continuation of a contract beyond its original end.
            - FTE. Full-time employee.
            - Onsite, hybrid, remote. Where the work happens.
            - Local only. The client wants candidates already living near the work location.
            - Relocation. Moving to the work location.
            - Backfill. Replacing someone who left a role.
            - Interview slots. Times offered for interviews.
            - Feedback. The client's response after an interview.
            - Offboarding. The process when an assignment ends.
            TEXT],
        ['kind' => 'content', 'heading' => 'Terms from the company training material', 'body' => <<<'TEXT'
            - The US MNC Staffing document lists terms every recruiter must know before starting work. In addition to those above, learn these.
            - Preferred vendor, or Tier 1 layer. A vendor with a direct, approved relationship with the client.
            - Blue chip company. A large, well-established, financially strong client.
            - BDM. Business Development Manager, who collects requirements and assigns them to recruiters.
            - Resource manager. The person who receives your submission package and forwards it.
            - NDA. Non-disclosure agreement, which protects confidential client and project information.
            - NCA. Non-compete agreement, which stops a consultant or employer from bypassing your company to work directly with the client.
            - MSA. Master Services Agreement, the main contract between two companies, under which individual assignments are placed.
            - R2R. The document's term for the right to represent, also written RTR.
            - Rate confirmation. A written confirmation of the agreed rate, sent before submission.
            - Submission template. The standard form with candidate details that goes with every submission.
            - Mass mailing. Sending a requirement to your whole vendor list so that vendors can offer matching consultants.
            TEXT],
        ['kind' => 'content', 'heading' => 'Technologies named in the company training material', 'body' => <<<'TEXT'
            The OPT Recruiter Training Material lists the technology groups the company recruits and trains for. Recognise these group names in requirements and resumes.
            Java, including J2EE, Struts, Hibernate, JavaScript and HTML.
            Microsoft technologies, including .NET, C sharp, ASP.NET, Visual Basic and SharePoint.
            Data warehousing and ETL, including Informatica, data analysis, data modelling and data architecture.
            Databases, including SQL, Oracle, Teradata and Sybase.
            Reporting tools, including Business Intelligence tools, Crystal Reports and Oracle BI.
            ERP, meaning enterprise resource planning, including SAP, PeopleSoft and Oracle Applications.
            Testing, including QA analysts, white box and black box testing, Selenium, manual and automation testing.
            System administration and networking, including Cisco, Linux, Unix, storage area networks and Solaris.
            Web, including PHP and web development. Mobile, including Android and iOS. Middleware, including webMethods, IBM MQ and TIBCO.
            Some of these names are older technologies. Today's requirements also commonly ask for cloud platforms such as AWS and Azure, Python, data engineering, and modern JavaScript frameworks.
            TEXT],
        ['kind' => 'content', 'heading' => 'Using terms carefully', 'body' => <<<'TEXT'
            Terms can mean slightly different things to different companies. When a vendor uses a term you are unsure about, ask politely. When you talk to candidates, explain terms in simple language, especially for new graduates.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            A vendor writes: Need local only, W2, twelve months CTH, send RTR and skill matrix by EOD Central. You understand: candidates must already live near the location, employment must be W2, the contract may convert to permanent, and you must send the candidate's written permission and a skills table before the end of the business day in Central Time.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Checklist & Common Mistakes', 'body' => <<<'TEXT'
            Every topic's checklist and common mistakes in one place. Use it as a quick review before you work on a real requirement.
            TEXT],
        ['kind' => 'reference', 'heading' => 'Recruiter Checklist', 'body' => <<<'TEXT'
            **U.S. IT Staffing Basics**
            - Know who the end client, vendor and employer are for every requirement.
            - Follow the lifecycle step by step without skipping screening or documentation.
            - Keep clear records at every step.
            **Staffing Agency vs Direct Employer**
            - Know who the legal employer is for each role.
            - Explain the model honestly and simply.
            - Hand off employer-related immigration questions to HR.
            **Client**
            - Know the end client and the vendor chain for every requirement.
            - Follow confidentiality rules for client information.
            - Ask candidates about any previous applications to the same client.
            - Treat every submission as representing your company.
            **Vendor**
            - Identify where the vendor sits in the chain.
            - Follow the vendor's submission format and deadlines.
            - Obtain right to represent before submitting.
            - Keep a clear record of what was sent and when.
            **Consultant**
            - Treat consultants as long-term professional relationships.
            - Keep commitments small and keep them.
            - Route questions to the right team and follow up.
            **Staffing Glossary**
            - Learn these terms until they are automatic.
            - Ask when a term is unclear.
            - Translate jargon for candidates.
            TEXT],
        ['kind' => 'mistakes', 'heading' => 'Common Mistakes', 'body' => <<<'TEXT'
            **U.S. IT Staffing Basics**
            - Confusing the vendor with the end client.
            - Submitting candidates before proper screening.
            - Forgetting that onboarding and payroll are part of a successful placement.
            **Staffing Agency vs Direct Employer**
            - Telling a candidate they will be employed by the client when they will not.
            - Assuming every staffing arrangement suits STEM OPT.
            - Giving different explanations to different candidates for the same role.
            **Client**
            - Sharing client names or rates freely.
            - Submitting a candidate who was already submitted to the same client.
            - Overselling a candidate who does not match the requirement.
            **Vendor**
            - Submitting without a right to represent.
            - Sending incomplete profiles.
            - Ignoring vendor instructions about formatting or deadlines.
            **Consultant**
            - Ignoring consultants once they are placed.
            - Promising project extensions or salary increases.
            - Answering payroll or immigration questions yourself.
            **Staffing Glossary**
            - Using jargon with candidates who do not understand it.
            - Guessing the meaning of an unfamiliar term.
            TEXT],
        ['kind' => 'takeaway', 'heading' => 'Key Takeaway', 'body' => <<<'TEXT'
            Know every party in the staffing chain and what each one needs, protect client information, and use staffing terms accurately, explaining them simply to candidates.
            TEXT],
    ],
];
