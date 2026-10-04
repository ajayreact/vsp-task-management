<?php

/*
 * Combined lesson "Candidate Fit: Qualifications, Skills & Experience". One topic per merged lesson:
 * Qualifications, Must-Have vs Preferred, Skills, Experience, Responsibilities.
 */

return [
    'title' => 'Candidate Fit: Qualifications, Skills & Experience',
    'from' => 'Qualifications',
    'en' => [
        ['kind' => 'objective', 'heading' => 'Learning Objective', 'body' => <<<'TEXT'
            Work out exactly what the client needs from a candidate: the qualifications, what is must-have versus preferred, the skills and their priority, the years and type of experience, and the responsibilities of the role.
            TEXT],
        ['kind' => 'flow', 'heading' => 'Lesson Map', 'body' => <<<'TEXT'
            1. Qualifications [[Qualifications]]
            2. Must-Have vs Preferred [[Must-Have vs Preferred]]
            3. Skills [[Skills]]
            4. Experience [[Experience]]
            5. Responsibilities [[Responsibilities]]
            6. Checklist & Common Mistakes [[Checklist & Common Mistakes]]
            TEXT],
        ['kind' => 'topic', 'heading' => 'Qualifications', 'body' => <<<'TEXT'
            Qualifications define the minimum bar. Compare honestly, item by item, and ask when a candidate is close.
            Checking a resume against these requirements is taught in **Sourcing & Resume Screening → Resume Screening: Skills, Experience & Education**.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            The qualifications section lists what the client expects a candidate to have. It is often split into required or minimum qualifications and preferred or desired qualifications.
            Qualifications may include education, years of experience, technical skills, certifications, domain knowledge and soft skills such as communication.
            TEXT],
        ['kind' => 'content', 'heading' => 'From the company training material', 'body' => <<<'TEXT'
            The Job Description Analysis training says the requirements or qualifications section is where you identify education, experience, achievements and other qualifications. Its resume screening checklist then asks you to check key qualifications and skills, education and certifications, and achievements for every candidate.
            TEXT],
        ['kind' => 'content', 'heading' => 'Education', 'body' => <<<'TEXT'
            Many IT roles ask for a bachelor's degree in computer science, engineering, information technology or a related field. Some accept equivalent experience.
            For OPT candidates, education is especially important, because OPT work must relate to the degree. Note the candidate's degree and major for every requirement. Remember that decisions about whether a job relates to a degree are made by HR and the candidate's DSO, not by recruiters.
            Some clients prefer a master's degree for data science or analytics roles.
            TEXT],
        ['kind' => 'content', 'heading' => 'Certifications', 'body' => <<<'TEXT'
            Some roles require or prefer certifications, such as cloud certifications from AWS or Microsoft Azure, Salesforce certifications, project management certifications, or Scrum certifications.
            When a certification is required, confirm the candidate has it and ask for the name and date. Never claim a certification the candidate does not hold.
            TEXT],
        ['kind' => 'content', 'heading' => 'Soft skills', 'body' => <<<'TEXT'
            Phrases like excellent communication skills, client-facing and team player matter. Assess them on your screening call by listening to how the candidate explains their projects.
            TEXT],
        ['kind' => 'content', 'heading' => 'How to compare a candidate with qualifications', 'body' => <<<'TEXT'
            - Make a simple list of required qualifications. Mark each one as met, partly met or not met for the candidate.
            - Do the same for preferred qualifications.
            - A candidate who misses a required qualification is usually not a fit, unless the vendor confirms flexibility.
            - A candidate who meets all required and several preferred qualifications is a strong submission.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            The running example requires a bachelor's degree in computer science or related field and three years of Java. A candidate has a master's in computer science, two and a half years of Java including internships, and Kafka experience. You mark Java experience as partly met and ask the vendor whether the client would consider her. You do not change her experience on the resume.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Must-Have vs Preferred', 'body' => <<<'TEXT'
            Must-haves decide who qualifies. Preferred items decide who stands out. Keep the two lists separate.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            Most job descriptions mix essential requirements with optional ones. Clients reject candidates who miss must-have requirements, but often accept candidates who miss some preferred ones. Recruiters who confuse the two either reject good candidates or submit unsuitable ones.
            TEXT],
        ['kind' => 'content', 'heading' => 'From the company training material', 'body' => <<<'TEXT'
            In the Day 5 workflow, this is the Prioritize step: separate must-have from preferred criteria. The key takeaways add: prioritise must-have skills and verify actual project experience. In the training's Java and AWS example, Core Java and JEE are must-haves, while Spring, Spring Boot and Spring Security are marked preferred, and Terraform is a plus.
            TEXT],
        ['kind' => 'content', 'heading' => 'Signals of must-have requirements', 'body' => <<<'TEXT'
            - Words such as required, must have, minimum, mandatory and essential.
            - Skills in the job title, summary and responsibilities, as well as the skills list.
            - Location rules such as local only.
            - Work authorization restrictions.
            - Required certifications or clearances.
            TEXT],
        ['kind' => 'content', 'heading' => 'Signals of preferred requirements', 'body' => <<<'TEXT'
            - Words such as preferred, nice to have, plus, bonus, desired and familiarity.
            - Skills mentioned only once, at the end of a list.
            - Domain experience listed as an advantage.
            TEXT],
        ['kind' => 'content', 'heading' => 'Building a requirement scorecard', 'body' => <<<'TEXT'
            - Write the must-haves as a short list, usually three to six items.
            - Write the preferred items as a second list.
            - For each candidate, check every must-have first. If any is missing, stop and consider whether to ask the vendor.
            - Then count the preferred items the candidate has, to rank strong candidates.
            TEXT],
        ['kind' => 'content', 'heading' => 'When the JD is unclear', 'body' => <<<'TEXT'
            Some JDs list everything as required. Ask the vendor: Which three skills are the most important for this client? Vendors appreciate recruiters who ask focused questions.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            For the running example, your must-haves are three or more years of Java, Spring Boot, REST APIs, SQL, and the ability to work hybrid in Charlotte. Your preferred items are Kafka, AWS and banking domain experience. Candidate one meets every must-have and has Kafka. Candidate two meets every must-have and has AWS and banking experience. Candidate three has strong AWS and Kafka, but only basic Spring Boot. You submit candidates one and two, and you do not submit candidate three, because a must-have is missing.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Skills', 'body' => <<<'TEXT'
            Group and rank the skills. Primary skills decide the match, so verify them carefully on every call.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            The skills section is the heart of most IT requirements. It lists programming languages, frameworks, databases, tools, platforms and methods. Some JDs list a few skills clearly. Others list twenty or more, mixing core skills with minor ones.
            TEXT],
        ['kind' => 'content', 'heading' => 'From the company training material', 'body' => <<<'TEXT'
            The Job Description Analysis training says to separate required skills from preferred or nice-to-have skills, and lists primary and secondary or preferred skills as mandatory details to capture before sourcing. Its Data Architect example groups skills exactly this way: architecture, cloud, security, integration, development and the AWS stack.
            TEXT],
        ['kind' => 'content', 'heading' => 'Grouping skills', 'body' => <<<'TEXT'
            - Group the skills into categories to understand the role.
            - Languages, such as Java, Python, C sharp or JavaScript.
            - Frameworks, such as Spring Boot, React, Angular or .NET.
            - Databases, such as Oracle, SQL Server, PostgreSQL or MongoDB.
            - Cloud and DevOps, such as AWS, Azure, Docker, Kubernetes or Jenkins.
            - Data tools, such as Spark, Kafka, Snowflake, Tableau or Power BI.
            - Testing tools, such as Selenium or Cypress.
            - Methods, such as Agile, Scrum or test-driven development.
            TEXT],
        ['kind' => 'content', 'heading' => 'Ranking skills', 'body' => <<<'TEXT'
            - Primary skills are the core technologies the person uses every day. They often appear in the title, the summary and the responsibilities.
            - Secondary skills support the primary skills.
            - Bonus skills are nice to have.
            - A useful rule: a skill mentioned in the title, the summary and the requirements is almost certainly primary.
            TEXT],
        ['kind' => 'content', 'heading' => 'Skill depth', 'body' => <<<'TEXT'
            - Hands-on means the candidate has used it directly, not just watched others use it.
            - Strong or expert means deep experience.
            - Exposure means basic familiarity.
            - On a call, ask candidates how they used each primary skill, what they built, and which problems they solved.
            TEXT],
        ['kind' => 'content', 'heading' => 'Equivalent skills', 'body' => <<<'TEXT'
            Some skills are related, but not the same. AWS and Azure are both cloud platforms, but a client asking for AWS may not accept Azure. React and Angular are both front-end frameworks, but they are different. Ask the vendor before treating skills as equivalent.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            For the running example, you group the skills: language Java; framework Spring Boot; design REST APIs and microservices; database SQL; preferred Kafka and AWS; method Agile. You rank Java, Spring Boot, REST and SQL as primary, and Kafka and AWS as bonus.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Experience', 'body' => <<<'TEXT'
            Experience requirements need careful, honest calculation. Describe every type of experience accurately and let the client decide.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            Experience requirements are usually stated in years, for example three or more years of Java development. They may refer to total IT experience, experience with a specific technology, or experience in a domain. Read carefully to see which one is meant.
            TEXT],
        ['kind' => 'content', 'heading' => 'From the company training material', 'body' => <<<'TEXT'
            The Job Description Analysis training says to capture the required years and the relevant domain or project experience. For the Data Architect example, it tells recruiters to validate years of experience against the requirement instead of matching keywords alone. The second example, the Java and AWS developer, requests twelve or more years and scores years of direct web application development separately, so the type of experience matters as much as the total.
            TEXT],
        ['kind' => 'content', 'heading' => 'Types of experience', 'body' => <<<'TEXT'
            - Total professional experience across all jobs.
            - Technology-specific experience, such as years using Python.
            - Domain experience, such as years in healthcare IT.
            - U.S. experience, meaning work performed in the U.S. Some clients prefer it, especially for client-facing roles.
            - Leadership experience, such as leading a team or mentoring.
            TEXT],
        ['kind' => 'content', 'heading' => 'Experience and OPT candidates', 'body' => <<<'TEXT'
            OPT candidates often have a mix of experience from their home country, internships, CPT roles, graduate assistant positions and academic projects. Some clients count internships and projects, others do not. When the requirement is strict, ask the vendor how they treat these.
            Never inflate a candidate's experience. Do not add years, change dates, or present academic projects as professional jobs. This is dishonest and can end client relationships.
            TEXT],
        ['kind' => 'content', 'heading' => 'How to calculate experience', 'body' => <<<'TEXT'
            - Count from the start and end month of each relevant role.
            - Do not double count overlapping roles.
            - Separate full-time work from part-time, internship or academic work, and describe each honestly.
            TEXT],
        ['kind' => 'content', 'heading' => 'Questions to ask on a call', 'body' => <<<'TEXT'
            - How many years have you worked with this technology in a professional role?
            - What did you build with it, and what was your responsibility?
            - Which version and tools did you use?
            - Was this full time, part time, an internship or an academic project?
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            The running example asks for three or more years of Java. A candidate's resume shows two years of Java as a full-time developer in India and eight months of Java in a U.S. internship. You record two years and eight months of professional Java experience, describe each role honestly, and ask the vendor whether this is acceptable.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Responsibilities', 'body' => <<<'TEXT'
            Responsibilities show the real work. Match candidates on what they have actually done, not just on the tools they list.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            The responsibilities section, sometimes called duties or what you will do, lists the tasks the person will perform. It often reveals more about the real role than the skills list does.
            TEXT],
        ['kind' => 'content', 'heading' => 'From the company training material', 'body' => <<<'TEXT'
            The Job Description Analysis training says the responsibilities show the actual day-to-day work, what the consultant will actually do. Its Java and AWS developer example lists the primary duties: architect, design, configure, implement, diagnose, repair, upgrade and optimise applications; anticipate the impact of new software on existing systems; take part in systems design based on user needs; monitor processes and recommend improvements; adapt agency-specific systems and integrations; identify problems and recommend solutions; and support other developers with troubleshooting. Reading a list like this tells you the role needs a senior, hands-on engineer who also supports others, not only a coder.
            TEXT],
        ['kind' => 'content', 'heading' => 'What responsibilities reveal', 'body' => <<<'TEXT'
            - The type of work, such as building new features, maintaining legacy systems, fixing production issues, migrating systems, writing reports or testing.
            - The level of ownership, through words like design, lead, own, mentor, support or assist.
            - Who the person works with, such as business users, product owners, other developers or clients.
            - The working environment, such as Agile sprints, on-call support or production deployments.
            TEXT],
        ['kind' => 'content', 'heading' => 'Matching responsibilities to candidates', 'body' => <<<'TEXT'
            - Ask candidates to describe their recent projects in terms of what they did each day.
            - Listen for the same kinds of tasks as the requirement.
            - A candidate who has built and deployed microservices matches a responsibility to develop and deploy microservices better than one who has only studied them.
            - Responsibilities can also show you which of the candidate's projects to highlight in the submission summary.
            TEXT],
        ['kind' => 'content', 'heading' => 'Responsibilities and OPT job relevance', 'body' => <<<'TEXT'
            The responsibilities of a role help HR and the candidate's DSO understand how the job relates to the candidate's degree. Describe the role accurately. Do not edit the responsibilities to make them sound more related to a degree. HR and the DSO make that judgement.
            TEXT],
        ['kind' => 'content', 'heading' => 'Red flags in responsibilities', 'body' => <<<'TEXT'
            - Responsibilities that do not match the title, such as a developer title with mostly support or sales tasks.
            - Responsibilities that suggest much more seniority than the rate or experience level.
            - Raise these with your lead or the vendor.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            The running example lists: design and develop microservices, write unit and integration tests, participate in code reviews, and work with product owners in two-week sprints. On your call, you ask the candidate to describe a microservice she built, how she tested it, and how her team ran sprints. Her answers match closely, so you highlight that project in your summary.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Checklist & Common Mistakes', 'body' => <<<'TEXT'
            Every topic's checklist and common mistakes in one place. Use it as a quick review before you work on a real requirement.
            TEXT],
        ['kind' => 'reference', 'heading' => 'Recruiter Checklist', 'body' => <<<'TEXT'
            **Qualifications**
            - Separate required from preferred qualifications.
            - Mark each qualification as met, partly met or not met.
            - Ask the vendor before submitting candidates who partly meet a required item.
            **Must-Have vs Preferred**
            - Separate must-haves from preferred items for every requirement.
            - Check must-haves first for every candidate.
            - Use preferred items to rank candidates.
            - Ask the vendor when everything appears required.
            **Skills**
            - Group the skills by category.
            - Rank them as primary, secondary or bonus.
            - Ask candidates about depth for every primary skill.
            - Check with the vendor before accepting equivalent skills.
            **Experience**
            - Identify which type of experience the JD means.
            - Calculate experience from dates, honestly.
            - Ask the vendor when the candidate is close to the requirement.
            **Responsibilities**
            - Read responsibilities to understand the daily work.
            - Ask candidates to describe matching tasks from real projects.
            - Never change responsibilities to influence job relevance decisions.
            TEXT],
        ['kind' => 'mistakes', 'heading' => 'Common Mistakes', 'body' => <<<'TEXT'
            **Qualifications**
            - Ignoring education requirements.
            - Overstating certifications or experience.
            - Treating preferred qualifications as essential and rejecting good candidates.
            **Must-Have vs Preferred**
            - Rejecting a strong candidate for missing a preferred skill.
            - Submitting a candidate who misses a must-have because they have many bonus skills.
            **Skills**
            - Treating every listed skill as primary.
            - Accepting a similar technology as a match without asking.
            - Judging depth from keywords on a resume alone.
            **Experience**
            - Counting academic projects as professional years.
            - Missing overlapping dates.
            - Submitting candidates far below the required experience.
            **Responsibilities**
            - Focusing only on skills keywords and ignoring responsibilities.
            - Missing a mismatch between title and duties.
            TEXT],
        ['kind' => 'takeaway', 'heading' => 'Key Takeaway', 'body' => <<<'TEXT'
            Separate must-haves from preferences, understand the depth and type of experience the role needs, and match candidates only to what the role really asks for.
            TEXT],
    ],
];
