<?php

/*
 * Combined lesson "Resume Screening: Skills, Experience & Education". One topic per merged lesson:
 * Resume Screening, Required Skills, Relevant Experience, Project Experience, Education, Certifications, Domain Experience.
 */

return [
    'title' => 'Resume Screening: Skills, Experience & Education',
    'from' => 'Resume Screening',
    'en' => [
        ['kind' => 'objective', 'heading' => 'Learning Objective', 'body' => <<<'TEXT'
            Screen resumes in a structured, fair way: check required skills and their depth, relevant and project experience, education, certifications and domain experience, and decide who to call.
            TEXT],
        ['kind' => 'flow', 'heading' => 'Lesson Map', 'body' => <<<'TEXT'
            1. Resume Screening [[Resume Screening]]
            2. Required Skills [[Required Skills]]
            3. Relevant Experience [[Relevant Experience]]
            4. Project Experience [[Project Experience]]
            5. Education [[Education]]
            6. Certifications [[Certifications]]
            7. Domain Experience [[Domain Experience]]
            8. Checklist & Common Mistakes [[Checklist & Common Mistakes]]
            TEXT],
        ['kind' => 'topic', 'heading' => 'Resume Screening', 'body' => <<<'TEXT'
            Screen in a consistent order, starting with must-haves. Be fast, fair and curious, and save your questions for the call.
            What the job description asks for is analysed in **Job Requirement Analysis → Candidate Fit: Qualifications, Skills & Experience**. Here you check the resume against it.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            Resume screening means reviewing a resume to decide whether a candidate is worth a call for a specific requirement. Good screening is fast but careful. A typical first screen takes two to three minutes, followed by a deeper review for promising resumes.
            TEXT],
        ['kind' => 'flow', 'heading' => 'A structured screening method', 'body' => <<<'TEXT'
            1. Must-Haves First. Primary skills, minimum experience, location fit and work mode. [[Required Skills]]
            2. Relevant Experience. Projects using the required technologies, recent use and responsibility level. [[Relevant Experience]]
            3. Education. Degree, major and graduation date, which matter for OPT candidates. [[Education]]
            4. Consistency. Dates, titles and locations should make sense together.
            5. Preferred and Domain. Note preferred skills and domain experience. [[Domain Experience]]
            6. Questions. Note the questions to ask on the call.
            7. Decide. Call now, keep for later, or not suitable for this requirement.
            TEXT],
        ['kind' => 'content', 'heading' => 'Reading OPT candidate resumes', 'body' => <<<'TEXT'
            OPT candidates' resumes often include academic projects, internships, CPT roles, graduate assistant jobs and work experience from their home country. Read each entry carefully, note what type of experience it is, and consider how the client may view it.
            Look for specific details: what they built, which technologies they used, and what results they achieved.
            TEXT],
        ['kind' => 'content', 'heading' => 'Fair screening', 'body' => <<<'TEXT'
            - Screen every resume against the same requirement criteria.
            - Do not judge candidates by name, nationality, photo, age, gender or other personal characteristics. These are not job criteria, and discrimination is illegal.
            - Do not make assumptions about work authorization from a resume. Ask the approved questions on the call.
            TEXT],
        ['kind' => 'content', 'heading' => 'Red flags to note, not to judge', 'body' => <<<'TEXT'
            - Large unexplained gaps.
            - Overlapping full-time jobs.
            - Very long skill lists without supporting project details.
            - Inconsistent dates between the resume and LinkedIn.
            - These are questions to ask politely on a call, not reasons to accuse a candidate.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            You screen a resume for the running Java requirement. It shows a master's in computer science, two years of Java with Spring Boot and REST at a company in India, a U.S. internship using Java and Kafka, and an expected graduation date. Must-haves are mostly met, with experience slightly under three years. You note questions about Spring Boot depth and SQL, and you decide to call.
            TEXT],
        ['kind' => 'content', 'heading' => 'From the company training material: what to check', 'body' => <<<'TEXT'
            - The Job Description Analysis training lists what to check on every resume.
            - Required skills. Check primary and secondary skills against the JD.
            - Experience. Verify relevant years, projects and responsibilities.
            - Contact details. Confirm that the candidate's contact information is complete and usable.
            - Domain experience. Look for relevant industry or domain exposure.
            - LinkedIn. Review it for consistency and additional professional context.
            - Customised resume. Identify job-specific tailoring and relevant keywords.
            - References and checks. Follow company process for reference and background checks.
            - Public professional information. Use approved, lawful sources and avoid unsupported conclusions.
            TEXT],
        ['kind' => 'content', 'heading' => 'The company resume screening checklist', 'body' => <<<'TEXT'
            The training also gives a systematic checklist for every candidate: key qualifications and skills; relevant work experience; education and certifications; achievements and accomplishments; culture-fit indicators relevant to the role; job history and tenure pattern; formatting and readability; grammar and spelling; LinkedIn profile; and referrals and recommendations.
            Its key takeaway is: screen the complete resume, not just keyword matches.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Required Skills', 'body' => <<<'TEXT'
            Required skills must be real and deep enough. Verify each one with specific questions and honest ratings.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            Required skills are the must-have technical skills in a requirement. Most client rejections happen because a candidate does not have the depth expected in one or more required skills. Your job is to confirm both presence and depth.
            TEXT],
        ['kind' => 'content', 'heading' => 'On the resume', 'body' => <<<'TEXT'
            - Look for each required skill in the skills section and, more importantly, in the project descriptions.
            - A skill listed only in the skills section, with no project using it, is weak evidence.
            - Note when and where each skill was used. Recent use matters more than use years ago.
            - Note the context: professional work, internship or academic project.
            TEXT],
        ['kind' => 'content', 'heading' => 'On the call', 'body' => <<<'TEXT'
            - Ask open questions about each required skill.
            - Which projects did you use Spring Boot in, and what did you build?
            - How did you design your REST APIs?
            - Which databases did you use, and what kind of SQL did you write?
            - What problems did you face, and how did you solve them?
            - Listen for specific answers, real examples and confidence. Vague answers suggest limited experience.
            TEXT],
        ['kind' => 'content', 'heading' => 'Rating skill depth', 'body' => <<<'TEXT'
            - Strong. Used professionally for a significant period, with clear examples.
            - Moderate. Used in some projects or internships, with reasonable examples.
            - Basic. Studied or used briefly, with general answers.
            - Record your rating for each required skill.
            TEXT],
        ['kind' => 'content', 'heading' => 'Be honest about gaps', 'body' => <<<'TEXT'
            If a candidate has a basic level in a required skill, the submission is likely to fail. Either do not submit, or ask the vendor whether the client would consider them, describing the gap honestly.
            TEXT],
        ['kind' => 'content', 'heading' => 'You are not the technical interviewer', 'body' => <<<'TEXT'
            Recruiters do not need to test skills deeply. Your goal is to confirm that the candidate has real, relevant experience, and to avoid obvious mismatches. The client's technical interview does the deep evaluation.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            A candidate lists SQL on her resume. On the call, she explains that she wrote complex joins and stored procedures for a reporting module in her last job, and describes how she optimised a slow query. You rate her SQL as strong and mention the reporting work in your submission summary.
            TEXT],
        ['kind' => 'content', 'heading' => 'From the company training material', 'body' => <<<'TEXT'
            The Job Description Analysis training says to check primary and secondary skills against the JD, and to prioritise must-have skills and verify actual project experience. For the Data Architect example, its screening focus is to look for architecture governance together with AWS cloud architecture, security and integration experience, and to check enterprise-scale infrastructure or application design and stakeholder management. A candidate with only one of these areas is not a match, however many AWS keywords the resume contains.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Relevant Experience', 'body' => <<<'TEXT'
            Relevance matters more than raw years. Look at what the candidate actually did, and describe it honestly.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            Relevant experience is experience that closely matches the role's work, technologies and environment. Two candidates with the same years of experience can be very different in relevance. A candidate with two years of directly relevant backend Java work may be stronger than one with four years of unrelated support work.
            TEXT],
        ['kind' => 'content', 'heading' => 'Measures of relevance', 'body' => <<<'TEXT'
            - Similar work. Did they do the same kind of tasks, such as building APIs or writing ETL pipelines?
            - Same technologies. Did they use the required technologies, and how recently?
            - Similar environment. Did they work in Agile teams, in large organisations, or in the same domain?
            - Similar responsibility. Did they build features independently, or mainly assist others?
            - Recency. Have they used these skills in the last one or two years?
            TEXT],
        ['kind' => 'content', 'heading' => 'Experience types for OPT candidates', 'body' => <<<'TEXT'
            - Full-time professional experience, in the U.S. or abroad.
            - Internships and CPT roles.
            - Graduate assistant or research roles.
            - Academic projects and capstone projects.
            - Freelance or volunteer projects.
            - Describe each one accurately. Never present an academic project as a job.
            TEXT],
        ['kind' => 'content', 'heading' => 'How clients view different experience', 'body' => <<<'TEXT'
            Some clients count only professional experience toward a years requirement. Others value strong internships and projects, especially for entry-level roles. When unclear, ask the vendor.
            TEXT],
        ['kind' => 'content', 'heading' => 'Questions to ask', 'body' => <<<'TEXT'
            - Which of your roles is most similar to this one?
            - What were your main responsibilities in that role?
            - How large was your team, and what was your part?
            - When did you last use this technology?
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            Two candidates apply for the running Java requirement. Candidate A has four years of Java, but mainly in production support with little development. Candidate B has two years and six months of Java development building Spring Boot microservices. You judge Candidate B as more relevant, and you ask the vendor whether two and a half years of directly relevant development would be considered.
            TEXT],
        ['kind' => 'content', 'heading' => 'From the company training material', 'body' => <<<'TEXT'
            The Job Description Analysis training says to verify relevant years, projects and responsibilities. Its Java and AWS example shows how clients weigh this. The client's scoring framework gives ten percent to years of direct web development, thirty percent to development tools and skill levels, twenty-five percent to roles and project experience, fifteen percent to SDLC knowledge and experience, meaning the software development life cycle, and twenty percent to rate. Years alone count for only a tenth of the score. The training's tip is to use a client's stated scoring criteria as an additional screening guide, not as a substitute for reading the full requirement.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Project Experience', 'body' => <<<'TEXT'
            Projects are the clearest evidence of skill. Explore them on every call, and describe them truthfully.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            Project descriptions on a resume show what a candidate actually built. For OPT candidates, projects from internships, CPT roles, academic programs and earlier jobs are often the best evidence of skill.
            TEXT],
        ['kind' => 'content', 'heading' => 'What a strong project description includes', 'body' => <<<'TEXT'
            - The project name or purpose, such as a payment processing service or a sales dashboard.
            - The candidate's role and responsibilities.
            - The technologies used.
            - The scale or complexity, such as number of users, data volume or team size.
            - Results or impact, such as reduced processing time or improved accuracy.
            TEXT],
        ['kind' => 'content', 'heading' => 'Questions to ask about projects', 'body' => <<<'TEXT'
            - What was the goal of the project?
            - What exactly did you build or do yourself?
            - Which technologies did you use, and why?
            - What was the hardest problem, and how did you solve it?
            - How did you test and deploy it?
            - Who did you work with?
            - Was this a professional, internship or academic project?
            TEXT],
        ['kind' => 'content', 'heading' => 'Listening for real experience', 'body' => <<<'TEXT'
            - Real experience sounds specific. Candidates mention particular challenges, decisions and details.
            - Limited experience sounds general and repeats textbook definitions.
            - If a candidate cannot explain a project on their resume, note it. The client's interviewer will ask the same questions.
            TEXT],
        ['kind' => 'content', 'heading' => 'Academic projects', 'body' => <<<'TEXT'
            Academic projects can be valuable, especially for entry-level roles. They show learning and practical application. Present them honestly as academic projects in your summary. Many clients appreciate strong capstone or thesis projects using relevant technologies.
            TEXT],
        ['kind' => 'content', 'heading' => 'Using projects in submissions', 'body' => <<<'TEXT'
            - Choose one or two projects that best match the requirement's responsibilities.
            - Summarise them clearly in your submission notes.
            - Never add details that the candidate did not tell you.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            A candidate's resume mentions a capstone project: an order management system using Spring Boot, REST APIs and PostgreSQL. On the call, he explains the API design, how he handled concurrent orders, and the tests he wrote. You describe this as a strong academic project in your summary, alongside his eight-month internship using Java.
            TEXT],
        ['kind' => 'content', 'heading' => 'From the company training material', 'body' => <<<'TEXT'
            In the Java and AWS example's scoring framework, roles and project experience carry twenty-five percent of the score, more than years of experience. The Job Description Analysis training repeats the point in its key takeaways: prioritise must-have skills and verify actual project experience. Ask what the candidate built, which part was theirs, and which tools they used, and compare the answers with the client's primary duties.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Education', 'body' => <<<'TEXT'
            Education details matter for job fit and work authorization. Record them exactly and leave eligibility decisions to HR and the DSO.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            The education section shows degrees, majors, universities and dates. For U.S. IT roles, many requirements ask for a bachelor's degree in computer science, engineering, information technology or a related field.
            For OPT candidates, education matters in additional ways. OPT work must relate to the degree, and STEM OPT depends on the degree being in a designated STEM field. These decisions belong to HR and the candidate's DSO, but recruiters must record education accurately.
            TEXT],
        ['kind' => 'reference', 'heading' => 'What to record', 'body' => <<<'TEXT'
            - Degree level, such as bachelor's or master's.
            - Major, such as computer science, data analytics or information systems.
            - University name and location.
            - Start and end dates, or expected graduation date.
            - Any relevant coursework or academic projects.
            TEXT],
        ['kind' => 'content', 'heading' => 'Reading U.S. degree names', 'body' => <<<'TEXT'
            - Master of Science, written as MS or M.S., is a common graduate degree for international IT students.
            - Bachelor of Technology, written as B.Tech, and Bachelor of Engineering, written as B.E., are common Indian undergraduate degrees.
            - Master of Business Administration, or MBA, may include an IT or analytics concentration.
            - Some programs have long names. Record the exact major as written.
            TEXT],
        ['kind' => 'content', 'heading' => 'Graduation date and OPT timing', 'body' => <<<'TEXT'
            The program end date affects when a candidate can apply for and begin post-completion OPT. Ask for the program end date and whether OPT has been applied for or approved. Record these facts. Do not advise on timing.
            TEXT],
        ['kind' => 'content', 'heading' => 'Verifying education', 'body' => <<<'TEXT'
            Education claims are usually verified by the employer during onboarding or background checks. Recruiters should note details accurately and flag inconsistencies, such as dates that do not match LinkedIn.
            Never change degree names, majors or dates on a resume.
            TEXT],
        ['kind' => 'content', 'heading' => 'Fair screening', 'body' => <<<'TEXT'
            Do not prefer or reject candidates because of the reputation of their university, unless the requirement specifically states a criterion. Focus on the degree requirements and skills.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            A resume shows a Master of Science in Information Systems from a university in Texas, with an expected graduation in December, and a Bachelor of Technology in Electronics from India. You record both degrees, the majors and the dates. When the candidate asks whether a data engineering role fits her OPT, you refer the question to HR and her DSO.
            TEXT],
        ['kind' => 'content', 'heading' => 'From the company training material', 'body' => <<<'TEXT'
            The Job Description Analysis checklist includes education and certifications for every candidate. The OPT calling script asks: When did you complete your masters? The calling questionnaire records the university and the graduation month and year, in the format month and four-digit year. Record these exactly as the candidate gives them, and check them against the resume.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Certifications', 'body' => <<<'TEXT'
            Certifications can strengthen a profile. Record them precisely, confirm they are current, and pair them with real experience.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            Certifications are credentials issued by technology companies or professional bodies. They show that a candidate has passed an exam or met certain standards.
            Some requirements list certifications as required. More often they are preferred.
            TEXT],
        ['kind' => 'content', 'heading' => 'Common IT certifications', 'body' => <<<'TEXT'
            - Cloud certifications, such as AWS Certified Solutions Architect, AWS Certified Developer, Microsoft Azure certifications and Google Cloud certifications.
            - Salesforce certifications, such as Salesforce Administrator or Platform Developer.
            - Project and agile certifications, such as Project Management Professional, called PMP, and Certified ScrumMaster.
            - Security certifications, such as CompTIA Security Plus and Certified Information Systems Security Professional, called CISSP.
            - Data and analytics certifications from various vendors.
            TEXT],
        ['kind' => 'content', 'heading' => 'Verifying certifications', 'body' => <<<'TEXT'
            - Ask for the exact certification name and the date it was earned.
            - Ask whether it is still active, because many certifications expire.
            - Many certifications can be verified online through the issuer's verification system, using a credential identifier the candidate provides.
            - Follow your company's process for verification.
            TEXT],
        ['kind' => 'content', 'heading' => 'Certifications and skills', 'body' => <<<'TEXT'
            A certification shows knowledge, but not necessarily hands-on experience. A candidate with an AWS certification but no project use of AWS may struggle in a role that needs hands-on AWS work. Ask about practical use as well.
            TEXT],
        ['kind' => 'content', 'heading' => 'Honesty', 'body' => <<<'TEXT'
            Never add a certification a candidate does not hold.
            Never describe a certification in progress as completed. You can write, for example, AWS Developer certification in progress, expected in March, if the candidate confirms it.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            A requirement lists AWS certification as preferred. A candidate's resume says AWS Certified. On the call, you ask which certification. She says AWS Certified Cloud Practitioner, earned last year. You record the exact name and date, and you note that she used AWS Lambda and S3 in an internship project.
            TEXT],
        ['kind' => 'content', 'heading' => 'From the company training material', 'body' => <<<'TEXT'
            The Job Description Analysis resume checklist lists education and certifications together, and achievements and accomplishments as a separate item. A certification is an achievement only if it is genuine and current, so record the exact name and date.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Domain Experience', 'body' => <<<'TEXT'
            Domain experience can be a powerful differentiator. Find it, describe it accurately and use it to strengthen strong submissions.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            Domain experience means experience in a specific industry or business area, such as banking, insurance, healthcare, retail, telecom, manufacturing, logistics or government. Clients value domain experience because the consultant understands the business terms, processes and data.
            TEXT],
        ['kind' => 'content', 'heading' => 'Common domains and examples of relevant knowledge', 'body' => <<<'TEXT'
            - Banking and financial services: payments, loans, trading, risk and regulatory reporting.
            - Insurance: policies, claims, underwriting and billing.
            - Healthcare: patient records, claims, pharmacy and healthcare data standards.
            - Retail and e-commerce: orders, inventory, pricing and customer data.
            - Telecom: billing, network operations and customer management.
            - Logistics: shipping, tracking and warehouse management.
            TEXT],
        ['kind' => 'content', 'heading' => 'Finding domain experience', 'body' => <<<'TEXT'
            - Look for company names and industries in the candidate's job history.
            - Look for domain terms in project descriptions.
            - Ask on the call: Which industries have your projects been in? What business processes did your work support?
            - Academic projects may also show domain knowledge, for example a healthcare analytics capstone.
            TEXT],
        ['kind' => 'content', 'heading' => 'When domain matters', 'body' => <<<'TEXT'
            - Some requirements list domain experience as required, often for senior or business-facing roles.
            - For entry-level OPT roles, domain is usually preferred rather than required.
            - Domain experience can help a candidate stand out among similar profiles.
            TEXT],
        ['kind' => 'content', 'heading' => 'Presenting domain experience', 'body' => <<<'TEXT'
            Mention it clearly in your submission summary: two years of Java development on a payments platform for a financial services company.
            Be accurate. Working for a company that serves banks is not the same as working on banking systems. Describe exactly what the candidate did.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            For the running Java requirement with a banking client, one candidate built loan processing APIs during an internship at a credit union. You note this as relevant banking domain experience and highlight it in your summary, along with his Spring Boot skills.
            TEXT],
        ['kind' => 'content', 'heading' => 'From the company training material', 'body' => <<<'TEXT'
            The Job Description Analysis training lists domain experience as a resume check: look for relevant industry or domain exposure. Its reading method also asks you to capture relevant domain or project experience in the experience section of the JD.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Checklist & Common Mistakes', 'body' => <<<'TEXT'
            Every topic's checklist and common mistakes in one place. Use it as a quick review before you work on a real requirement.
            TEXT],
        ['kind' => 'reference', 'heading' => 'Recruiter Checklist', 'body' => <<<'TEXT'
            **Resume Screening**
            - Check must-haves first.
            - Review experience, education and consistency.
            - Note questions for the call.
            - Screen everyone by the same criteria.
            **Required Skills**
            - Find each required skill in real projects.
            - Ask open questions about each one.
            - Rate depth and record it.
            - Be honest about gaps.
            **Relevant Experience**
            - Look beyond years to the type of work.
            - Check recency and responsibility.
            - Describe every type of experience honestly.
            - Ask the vendor how they count internships and projects.
            **Project Experience**
            - Ask about goal, role, technologies, challenges and results.
            - Listen for specific details.
            - Label project types accurately.
            - Highlight the best-matching projects.
            **Education**
            - Record degree level, major, university and dates for every candidate.
            - Ask about the program end date and OPT status.
            - Flag inconsistencies.
            - Never edit education details.
            **Certifications**
            - Record exact certification names and dates.
            - Ask whether certifications are active.
            - Ask about practical use.
            - Verify according to company process.
            **Domain Experience**
            - Identify the domain of every requirement.
            - Ask candidates about industries and business processes.
            - Highlight genuine domain experience.
            - Describe it accurately.
            TEXT],
        ['kind' => 'mistakes', 'heading' => 'Common Mistakes', 'body' => <<<'TEXT'
            **Resume Screening**
            - Spending ten minutes on a resume that fails a must-have.
            - Judging candidates on personal characteristics.
            - Treating red flags as proof of dishonesty.
            **Required Skills**
            - Accepting skills listed without project evidence.
            - Asking yes-or-no questions such as Do you know Java?
            - Hiding gaps from the vendor.
            **Relevant Experience**
            - Choosing candidates by years only.
            - Overlooking strong internships.
            - Presenting projects as professional experience.
            **Project Experience**
            - Skipping project questions.
            - Presenting academic projects as jobs.
            - Ignoring candidates who cannot explain their own projects.
            **Education**
            - Recording only the highest degree and missing the major.
            - Advising on whether a job fits the degree.
            - Making judgements based on university reputation.
            **Certifications**
            - Writing a general term like AWS Certified without the specific certification.
            - Treating a certification as proof of experience.
            - Listing certifications that are in progress as completed.
            **Domain Experience**
            - Ignoring domain when the requirement mentions it.
            - Overstating domain experience.
            - Rejecting entry-level candidates for lack of domain experience when it is only preferred.
            TEXT],
        ['kind' => 'takeaway', 'heading' => 'Key Takeaway', 'body' => <<<'TEXT'
            Screen must-haves first, judge relevance and depth rather than keywords, record education accurately, and screen every candidate fairly.
            TEXT],
    ],
];
