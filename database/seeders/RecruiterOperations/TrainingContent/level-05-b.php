<?php

return [
    'level' => 5,
    'course' => 'Sourcing & Resume Screening',
    'lessons' => [
        'Search Strategy' => <<<'TEXT'
        Learning objective
        Plan a structured search strategy for each requirement, so that you find the best candidates quickly and do not waste time on random searching.

        What you need to know
        A search strategy is a plan that answers three questions: who you are looking for, where you will look, and in what order. It comes directly from your requirement analysis in Level 4.

        Step one, define the ideal candidate
        Write two or three sentences describing the ideal candidate: title, primary skills, experience level, location, work authorization fit and any domain experience. This is your target profile.

        Step two, choose your sources in order
        Internal database first, because these candidates already know your company.
        Referrals and your professional network.
        LinkedIn and job boards such as Dice.
        University networks and student communities, for recent graduates.
        Professional groups.
        The best order depends on the role. For entry-level OPT roles, university and student channels may come earlier.

        Step three, prepare keywords
        Use your title keywords, primary skill keywords and secondary keywords from the requirement analysis. Plan your Boolean strings before you start.

        Step four, set targets and time limits
        Decide how many qualified candidates you need, for example three strong submissions.
        Set a time limit for each source, for example thirty minutes, before moving to the next.
        Track results, so that you know which sources work best for which roles.

        Step five, review and adjust
        After each source, ask yourself: Am I finding the right people? If not, adjust your keywords, location or sources.
        If you find no matches after trying all sources, discuss the requirement with your lead. The requirement may need clarification, or it may not be a good fit for your candidate pool.

        Practical example
        For the running Java requirement, your target profile is: a Java backend developer with three or more years of Java, Spring Boot, REST and SQL, in or willing to relocate to Charlotte, whose work authorization matches the requirement. Your plan is internal database for twenty minutes, LinkedIn for thirty minutes, Dice for thirty minutes, and university alumni networks for twenty minutes. Your target is three strong submissions by the end of the day.

        Recruiter checklist
        Write a target profile for every requirement.
        Choose sources and order them.
        Prepare keywords in advance.
        Set targets and time limits.
        Review results and adjust.

        Common mistakes
        Searching without a target profile.
        Spending all your time on one source.
        Not tracking which sources work.

        From the company training material
        The Day 5 recruiter workflow puts sourcing after three steps: read the entire requirement, extract the location, duration, rate, skills, dates and client, and prioritise must-have against preferred criteria. Only then do you source, which the training defines as searching the right channels using targeted keywords. Its six sources of recruitment are job boards, professional network, vendor network, groups, internal database and direct communication. The US MNC Staffing document adds the order for staffing requirements: your hotlist first, then a mass mail to your vendor list, then a portal posting and a C2C search, and finally select the best one or two resumes.

        Key takeaway
        A clear search strategy turns sourcing into a repeatable process that delivers results faster.
        TEXT,

        'Primary Keywords' => <<<'TEXT'
        Learning objective
        Identify the primary keywords for a requirement and use them correctly in searches.

        What you need to know
        Primary keywords are the words that must appear in a suitable candidate's profile or resume. They come from the must-have skills and the core job title. Usually there are only three to five.
        If a profile does not contain a primary keyword, the candidate is unlikely to meet the requirement.

        How to identify primary keywords
        Look for skills in the job title, the summary and the required qualifications.
        Look for skills repeated in the responsibilities.
        Ask: Would the client reject a candidate without this skill? If yes, it is primary.

        Examples
        For a Java Developer role: Java, Spring Boot, REST, and SQL.
        For a Data Analyst role: SQL, Excel, and Tableau or Power BI.
        For a QA Automation Engineer role: Selenium, Java or Python, and test automation.
        For a Data Engineer role: Python or Scala, Spark, SQL, and a cloud platform such as AWS.

        Variations and synonyms
        Candidates describe skills in different ways. Include common variations.
        Spring Boot, Springboot and Spring.
        REST, RESTful and REST API.
        JavaScript and JS.
        Machine learning and ML.
        Continuous integration and CI.
        Search for the main term and its variations together, using OR in Boolean search.

        Using primary keywords in searches
        Combine primary keywords with AND, so that every result includes all of them.
        Do not add too many primary keywords. Four strong primary keywords usually work better than ten.
        Check a sample of results to make sure the keywords are finding relevant people.

        Keywords are not enough
        A keyword on a resume does not prove experience. A candidate may list a skill they used briefly in a class. Use keywords to find candidates, then verify skills on a call.

        Practical example
        For the running Java requirement, your primary keywords are Java, Spring Boot, REST and SQL. Your search is: Java AND, in brackets, Spring Boot OR Springboot, AND, in brackets, REST OR RESTful, AND SQL. You then read the top profiles to confirm real project experience.

        Recruiter checklist
        Choose three to five primary keywords from the must-haves.
        Include common variations.
        Combine with AND.
        Verify on a call.

        Common mistakes
        Using every listed skill as a primary keyword.
        Ignoring spelling variations.
        Treating a keyword match as proof of experience.

        From the company training material
        The Job Description Analysis training's Data Architect example gives a model list of high-value keywords: AWS, Solution Architect, Enterprise Architect, API Gateway, Lambda, VPC, CloudFront, security architecture, Python and Java. It also warns: validate years of experience against the requirement instead of matching keywords alone. Keywords find candidates. They do not prove a match.

        Key takeaway
        Primary keywords define the core match. Keep the list short, include variations, and always verify.
        TEXT,

        'Secondary Keywords' => <<<'TEXT'
        Learning objective
        Use secondary keywords to rank and refine search results without excluding good candidates.

        What you need to know
        Secondary keywords come from preferred skills, supporting tools, domain experience and certifications. They are useful, but a candidate without them may still qualify.
        Use secondary keywords to rank candidates, to narrow very large result lists, and to highlight strengths in submissions.

        Examples
        For a Java Developer role: Kafka, AWS, Docker, Kubernetes, microservices, banking and Agile.
        For a Data Analyst role: Python, Snowflake, healthcare and statistics.
        For a QA Automation role: Cucumber, Jenkins, API testing and performance testing.

        How to use secondary keywords
        Start with primary keywords only. If results are manageable, review them and score candidates on secondary keywords.
        If results are too many, add one secondary keyword at a time to narrow the list.
        Use OR between secondary keywords to find candidates with at least one of them, for example, in brackets, Kafka OR AWS OR Docker.
        Record which secondary skills each shortlisted candidate has. Mention them in the submission summary.

        Domain keywords
        Domain keywords, such as banking, insurance, healthcare, retail or telecom, can be powerful differentiators. Clients often favour candidates who know their industry. But domain is rarely a must-have for entry-level OPT roles, so do not exclude candidates without it unless the requirement demands it.

        Over-filtering
        Adding too many secondary keywords with AND can shrink your results to almost nothing and hide good candidates. If you find yourself with very few results, remove secondary keywords first.

        Practical example
        Your Java search with primary keywords returns two hundred profiles in the Charlotte area. You add, AND, in brackets, Kafka OR AWS, which narrows it to sixty. You review the top twenty, shortlist five, and note that three have Kafka and two have AWS.

        Recruiter checklist
        Keep secondary keywords separate from primary keywords.
        Add them one at a time when narrowing.
        Use OR to find any of several preferred skills.
        Highlight them in submissions.

        Common mistakes
        Using secondary keywords with AND from the start.
        Rejecting candidates without bonus skills.
        Forgetting to mention bonus skills in the submission.

        From the company training material
        The Job Description Analysis training separates primary skills from secondary or preferred skills. In its Java and AWS example, Spring, Spring Boot and Spring Security are preferred and Terraform is a plus. These are secondary keywords: use them to rank candidates who already have Core Java and AWS, not to remove candidates who lack them.

        Key takeaway
        Secondary keywords help you rank and stand out. Use them to refine, not to exclude.
        TEXT,

        'Boolean Search Basics' => <<<'TEXT'
        Learning objective
        Learn the basic Boolean operators and how to build simple, effective search strings for LinkedIn, job boards and internal databases.

        What you need to know
        Boolean search uses special words, called operators, to combine keywords. Most recruiting platforms support it, although each platform has small differences. Check the help pages of your tools.

        The main operators
        AND. Every result must contain both terms. Java AND Spring finds profiles with both words.
        OR. Results may contain either term. Developer OR Engineer finds profiles with either word. Use OR for synonyms and variations.
        NOT. Excludes a term. Java NOT JavaScript removes profiles that mention JavaScript. Use NOT carefully, because it can remove good candidates.
        Quotation marks. Search for an exact phrase. Quote Spring Boot, end quote, finds that exact phrase rather than the two words anywhere.
        Brackets. Group terms. Java AND, open bracket, Spring OR Hibernate, close bracket, means Java plus at least one of the two.

        Building a search string step by step
        Start with the title group, using OR. For example, open bracket, Java Developer in quotation marks, OR Java Engineer in quotation marks, OR Backend Developer in quotation marks, close bracket.
        Add the primary skill groups, using AND between groups and OR inside groups. For example, AND, open bracket, Spring Boot in quotation marks, OR Springboot, close bracket, AND, open bracket, REST OR RESTful, close bracket, AND SQL.
        Add secondary skills only if needed.
        Add NOT terms only to remove clearly irrelevant results, such as NOT intern if you need experienced candidates and the platform allows it.

        Tips
        Write operators in capital letters. Many platforms require it.
        Test your string and read a sample of results.
        Save strings that work, for reuse.
        Keep strings readable. Very long strings are hard to fix.

        Practical example
        For the running Java requirement, your LinkedIn string is: open bracket, Java Developer in quotation marks, OR Java Engineer in quotation marks, OR Backend Engineer in quotation marks, close bracket, AND, open bracket, Spring Boot in quotation marks, OR Springboot, close bracket, AND, open bracket, REST OR RESTful, close bracket. You add the Charlotte location filter separately.

        Recruiter checklist
        Group synonyms with OR inside brackets.
        Connect groups with AND.
        Use quotation marks for exact phrases.
        Use NOT sparingly.
        Test and save strings.

        Common mistakes
        Forgetting brackets, which changes the meaning.
        Overusing NOT.
        Using lower-case operators on platforms that need capitals.

        Source note
        The company training documents do not cover Boolean search. This lesson is based on general recruiting practice and needs review and approval by the training manager. Ask a senior recruiter which search strings your team uses on each portal.

        Key takeaway
        A few Boolean operators make searches precise. Build strings in groups, test them, and refine.
        TEXT,

        'Resume Screening' => <<<'TEXT'
        Learning objective
        Learn a structured method for screening resumes quickly and fairly against a requirement.

        What you need to know
        Resume screening means reviewing a resume to decide whether a candidate is worth a call for a specific requirement. Good screening is fast but careful. A typical first screen takes two to three minutes, followed by a deeper review for promising resumes.

        A structured screening method
        Step one. Check the must-haves first: primary skills, minimum experience, location fit and work mode.
        Step two. Check relevant experience: projects using the required technologies, recent use, and responsibility level.
        Step three. Check education: degree, major and graduation date, which matter for OPT candidates.
        Step four. Check consistency: dates, titles and locations should make sense together.
        Step five. Note preferred skills and domain experience.
        Step six. Note questions to ask on the call.
        Step seven. Decide: call now, keep for later, or not suitable for this requirement.

        Reading OPT candidate resumes
        OPT candidates' resumes often include academic projects, internships, CPT roles, graduate assistant jobs and work experience from their home country. Read each entry carefully, note what type of experience it is, and consider how the client may view it.
        Look for specific details: what they built, which technologies they used, and what results they achieved.

        Fair screening
        Screen every resume against the same requirement criteria.
        Do not judge candidates by name, nationality, photo, age, gender or other personal characteristics. These are not job criteria, and discrimination is illegal.
        Do not make assumptions about work authorization from a resume. Ask the approved questions on the call.

        Red flags to note, not to judge
        Large unexplained gaps.
        Overlapping full-time jobs.
        Very long skill lists without supporting project details.
        Inconsistent dates between the resume and LinkedIn.
        These are questions to ask politely on a call, not reasons to accuse a candidate.

        Practical example
        You screen a resume for the running Java requirement. It shows a master's in computer science, two years of Java with Spring Boot and REST at a company in India, a U.S. internship using Java and Kafka, and an expected graduation date. Must-haves are mostly met, with experience slightly under three years. You note questions about Spring Boot depth and SQL, and you decide to call.

        Recruiter checklist
        Check must-haves first.
        Review experience, education and consistency.
        Note questions for the call.
        Screen everyone by the same criteria.

        Common mistakes
        Spending ten minutes on a resume that fails a must-have.
        Judging candidates on personal characteristics.
        Treating red flags as proof of dishonesty.

        From the company training material: what to check
        The Job Description Analysis training lists what to check on every resume.
        Required skills. Check primary and secondary skills against the JD.
        Experience. Verify relevant years, projects and responsibilities.
        Contact details. Confirm that the candidate's contact information is complete and usable.
        Domain experience. Look for relevant industry or domain exposure.
        LinkedIn. Review it for consistency and additional professional context.
        Customised resume. Identify job-specific tailoring and relevant keywords.
        References and checks. Follow company process for reference and background checks.
        Public professional information. Use approved, lawful sources and avoid unsupported conclusions.

        The company resume screening checklist
        The training also gives a systematic checklist for every candidate: key qualifications and skills; relevant work experience; education and certifications; achievements and accomplishments; culture-fit indicators relevant to the role; job history and tenure pattern; formatting and readability; grammar and spelling; LinkedIn profile; and referrals and recommendations.
        Its key takeaway is: screen the complete resume, not just keyword matches.

        Key takeaway
        Screen in a consistent order, starting with must-haves. Be fast, fair and curious, and save your questions for the call.
        TEXT,

        'Required Skills' => <<<'TEXT'
        Learning objective
        Verify required skills on a resume and on a call, so that you submit only candidates who truly have them.

        What you need to know
        Required skills are the must-have technical skills in a requirement. Most client rejections happen because a candidate does not have the depth expected in one or more required skills. Your job is to confirm both presence and depth.

        On the resume
        Look for each required skill in the skills section and, more importantly, in the project descriptions.
        A skill listed only in the skills section, with no project using it, is weak evidence.
        Note when and where each skill was used. Recent use matters more than use years ago.
        Note the context: professional work, internship or academic project.

        On the call
        Ask open questions about each required skill.
        Which projects did you use Spring Boot in, and what did you build?
        How did you design your REST APIs?
        Which databases did you use, and what kind of SQL did you write?
        What problems did you face, and how did you solve them?
        Listen for specific answers, real examples and confidence. Vague answers suggest limited experience.

        Rating skill depth
        Strong. Used professionally for a significant period, with clear examples.
        Moderate. Used in some projects or internships, with reasonable examples.
        Basic. Studied or used briefly, with general answers.
        Record your rating for each required skill.

        Be honest about gaps
        If a candidate has a basic level in a required skill, the submission is likely to fail. Either do not submit, or ask the vendor whether the client would consider them, describing the gap honestly.

        You are not the technical interviewer
        Recruiters do not need to test skills deeply. Your goal is to confirm that the candidate has real, relevant experience, and to avoid obvious mismatches. The client's technical interview does the deep evaluation.

        Practical example
        A candidate lists SQL on her resume. On the call, she explains that she wrote complex joins and stored procedures for a reporting module in her last job, and describes how she optimised a slow query. You rate her SQL as strong and mention the reporting work in your submission summary.

        Recruiter checklist
        Find each required skill in real projects.
        Ask open questions about each one.
        Rate depth and record it.
        Be honest about gaps.

        Common mistakes
        Accepting skills listed without project evidence.
        Asking yes-or-no questions such as Do you know Java?
        Hiding gaps from the vendor.

        From the company training material
        The Job Description Analysis training says to check primary and secondary skills against the JD, and to prioritise must-have skills and verify actual project experience. For the Data Architect example, its screening focus is to look for architecture governance together with AWS cloud architecture, security and integration experience, and to check enterprise-scale infrastructure or application design and stakeholder management. A candidate with only one of these areas is not a match, however many AWS keywords the resume contains.

        Key takeaway
        Required skills must be real and deep enough. Verify each one with specific questions and honest ratings.
        TEXT,

        'Relevant Experience' => <<<'TEXT'
        Learning objective
        Judge how relevant a candidate's experience is to a requirement, beyond simple years of experience.

        What you need to know
        Relevant experience is experience that closely matches the role's work, technologies and environment. Two candidates with the same years of experience can be very different in relevance. A candidate with two years of directly relevant backend Java work may be stronger than one with four years of unrelated support work.

        Measures of relevance
        Similar work. Did they do the same kind of tasks, such as building APIs or writing ETL pipelines?
        Same technologies. Did they use the required technologies, and how recently?
        Similar environment. Did they work in Agile teams, in large organisations, or in the same domain?
        Similar responsibility. Did they build features independently, or mainly assist others?
        Recency. Have they used these skills in the last one or two years?

        Experience types for OPT candidates
        Full-time professional experience, in the U.S. or abroad.
        Internships and CPT roles.
        Graduate assistant or research roles.
        Academic projects and capstone projects.
        Freelance or volunteer projects.
        Describe each one accurately. Never present an academic project as a job.

        How clients view different experience
        Some clients count only professional experience toward a years requirement. Others value strong internships and projects, especially for entry-level roles. When unclear, ask the vendor.

        Questions to ask
        Which of your roles is most similar to this one?
        What were your main responsibilities in that role?
        How large was your team, and what was your part?
        When did you last use this technology?

        Practical example
        Two candidates apply for the running Java requirement. Candidate A has four years of Java, but mainly in production support with little development. Candidate B has two years and six months of Java development building Spring Boot microservices. You judge Candidate B as more relevant, and you ask the vendor whether two and a half years of directly relevant development would be considered.

        Recruiter checklist
        Look beyond years to the type of work.
        Check recency and responsibility.
        Describe every type of experience honestly.
        Ask the vendor how they count internships and projects.

        Common mistakes
        Choosing candidates by years only.
        Overlooking strong internships.
        Presenting projects as professional experience.

        From the company training material
        The Job Description Analysis training says to verify relevant years, projects and responsibilities. Its Java and AWS example shows how clients weigh this. The client's scoring framework gives ten percent to years of direct web development, thirty percent to development tools and skill levels, twenty-five percent to roles and project experience, fifteen percent to SDLC knowledge and experience, meaning the software development life cycle, and twenty percent to rate. Years alone count for only a tenth of the score. The training's tip is to use a client's stated scoring criteria as an additional screening guide, not as a substitute for reading the full requirement.

        Key takeaway
        Relevance matters more than raw years. Look at what the candidate actually did, and describe it honestly.
        TEXT,

        'Project Experience' => <<<'TEXT'
        Learning objective
        Learn how to read and discuss project experience, especially for OPT and early-career candidates.

        What you need to know
        Project descriptions on a resume show what a candidate actually built. For OPT candidates, projects from internships, CPT roles, academic programs and earlier jobs are often the best evidence of skill.

        What a strong project description includes
        The project name or purpose, such as a payment processing service or a sales dashboard.
        The candidate's role and responsibilities.
        The technologies used.
        The scale or complexity, such as number of users, data volume or team size.
        Results or impact, such as reduced processing time or improved accuracy.

        Questions to ask about projects
        What was the goal of the project?
        What exactly did you build or do yourself?
        Which technologies did you use, and why?
        What was the hardest problem, and how did you solve it?
        How did you test and deploy it?
        Who did you work with?
        Was this a professional, internship or academic project?

        Listening for real experience
        Real experience sounds specific. Candidates mention particular challenges, decisions and details.
        Limited experience sounds general and repeats textbook definitions.
        If a candidate cannot explain a project on their resume, note it. The client's interviewer will ask the same questions.

        Academic projects
        Academic projects can be valuable, especially for entry-level roles. They show learning and practical application. Present them honestly as academic projects in your summary. Many clients appreciate strong capstone or thesis projects using relevant technologies.

        Using projects in submissions
        Choose one or two projects that best match the requirement's responsibilities.
        Summarise them clearly in your submission notes.
        Never add details that the candidate did not tell you.

        Practical example
        A candidate's resume mentions a capstone project: an order management system using Spring Boot, REST APIs and PostgreSQL. On the call, he explains the API design, how he handled concurrent orders, and the tests he wrote. You describe this as a strong academic project in your summary, alongside his eight-month internship using Java.

        Recruiter checklist
        Ask about goal, role, technologies, challenges and results.
        Listen for specific details.
        Label project types accurately.
        Highlight the best-matching projects.

        Common mistakes
        Skipping project questions.
        Presenting academic projects as jobs.
        Ignoring candidates who cannot explain their own projects.

        From the company training material
        In the Java and AWS example's scoring framework, roles and project experience carry twenty-five percent of the score, more than years of experience. The Job Description Analysis training repeats the point in its key takeaways: prioritise must-have skills and verify actual project experience. Ask what the candidate built, which part was theirs, and which tools they used, and compare the answers with the client's primary duties.

        Key takeaway
        Projects are the clearest evidence of skill. Explore them on every call, and describe them truthfully.
        TEXT,
    ],
];
