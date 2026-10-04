<?php

/*
 * Combined lesson "Search Strategy, Keywords & Boolean". One topic per merged lesson:
 * Search Strategy, [Job Requirement Analysis] Requirement → Search Keywords, Primary Keywords, Secondary Keywords, Boolean Search Basics.
 */

return [
    'title' => 'Search Strategy, Keywords & Boolean',
    'from' => 'Search Strategy',
    'en' => [
        ['kind' => 'objective', 'heading' => 'Learning Objective', 'body' => <<<'TEXT'
            Plan every search before you start: define the ideal candidate, choose your sources in order, turn the requirement into primary and secondary keywords, and combine them with Boolean search.
            TEXT],
        ['kind' => 'flow', 'heading' => 'Lesson Map', 'body' => <<<'TEXT'
            1. Search Strategy [[Search Strategy]]
            2. Requirement → Search Keywords [[Requirement → Search Keywords]]
            3. Primary Keywords [[Primary Keywords]]
            4. Secondary Keywords [[Secondary Keywords]]
            5. Boolean Search Basics [[Boolean Search Basics]]
            6. Checklist & Common Mistakes [[Checklist & Common Mistakes]]
            TEXT],
        ['kind' => 'topic', 'heading' => 'Search Strategy', 'body' => <<<'TEXT'
            A clear search strategy turns sourcing into a repeatable process that delivers results faster.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            A search strategy is a plan that answers three questions: who you are looking for, where you will look, and in what order. It comes directly from your requirement analysis in the Job Requirement Analysis course.
            TEXT],
        ['kind' => 'content', 'heading' => 'Step one, define the ideal candidate', 'body' => <<<'TEXT'
            Write two or three sentences describing the ideal candidate: title, primary skills, experience level, location, work authorization fit and any domain experience. This is your target profile.
            TEXT],
        ['kind' => 'flow', 'heading' => 'Step two, choose your sources in order', 'body' => <<<'TEXT'
            1. Internal Database. These candidates already know your company.
            2. Referrals and Network. Your professional network and referrals.
            3. LinkedIn and Job Boards. Including Dice.
            4. University Networks. Student communities for recent graduates. For entry-level OPT roles, these may come earlier.
            5. Professional Groups. Approved groups and technology communities.
            TEXT],
        ['kind' => 'content', 'heading' => 'Step three, prepare keywords', 'body' => <<<'TEXT'
            Use your title keywords, primary skill keywords and secondary keywords from the requirement analysis. Plan your Boolean strings before you start.
            TEXT],
        ['kind' => 'content', 'heading' => 'Step four, set targets and time limits', 'body' => <<<'TEXT'
            - Decide how many qualified candidates you need, for example three strong submissions.
            - Set a time limit for each source, for example thirty minutes, before moving to the next.
            - Track results, so that you know which sources work best for which roles.
            TEXT],
        ['kind' => 'content', 'heading' => 'Step five, review and adjust', 'body' => <<<'TEXT'
            After each source, ask yourself: Am I finding the right people? If not, adjust your keywords, location or sources.
            If you find no matches after trying all sources, discuss the requirement with your lead. The requirement may need clarification, or it may not be a good fit for your candidate pool.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            For the running Java requirement, your target profile is: a Java backend developer with three or more years of Java, Spring Boot, REST and SQL, in or willing to relocate to Charlotte, whose work authorization matches the requirement. Your plan is internal database for twenty minutes, LinkedIn for thirty minutes, Dice for thirty minutes, and university alumni networks for twenty minutes. Your target is three strong submissions by the end of the day.
            TEXT],
        ['kind' => 'content', 'heading' => 'From the company training material', 'body' => <<<'TEXT'
            The Day 5 recruiter workflow puts sourcing after three steps: read the entire requirement, extract the location, duration, rate, skills, dates and client, and prioritise must-have against preferred criteria. Only then do you source, which the training defines as searching the right channels using targeted keywords. Its six sources of recruitment are job boards, professional network, vendor network, groups, internal database and direct communication. The US MNC Staffing document adds the order for staffing requirements: your hotlist first, then a mass mail to your vendor list, then a portal posting and a C2C search, and finally select the best one or two resumes.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Requirement → Search Keywords', 'body' => <<<'TEXT'
            Your analysis becomes your search. Clear keyword lists and a source plan lead to faster, better shortlists.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            After analysing a requirement, your next step is to find matching candidates. Good searching starts with good keywords. Keywords come directly from your analysis: titles, primary skills, secondary skills, domain, location and other filters.
            TEXT],
        ['kind' => 'content', 'heading' => 'From the company training material', 'body' => <<<'TEXT'
            The Job Description Analysis training states the goal directly: convert the JD into searchable keywords and objective screening criteria. For its Data Architect example, it lists the high-value keywords as AWS, Solution Architect, Enterprise Architect, API Gateway, Lambda, VPC, CloudFront, security architecture, Python and Java. Notice that the list mixes titles, platforms, services and languages. That is exactly the pattern to follow in the steps below.
            TEXT],
        ['kind' => 'content', 'heading' => 'Step one, list title keywords', 'body' => <<<'TEXT'
            Write the main title and similar titles. For example, Java Developer, Java Engineer, Software Engineer Java, Backend Developer and Spring Boot Developer.
            TEXT],
        ['kind' => 'content', 'heading' => 'Step two, list primary skill keywords', 'body' => <<<'TEXT'
            These are the must-have skills. For example, Java, Spring Boot, REST API and SQL. Include common variations, such as Spring and Springboot, REST and RESTful, and Microservices and Micro services.
            TEXT],
        ['kind' => 'content', 'heading' => 'Step three, list secondary and bonus keywords', 'body' => <<<'TEXT'
            For example, Kafka, AWS and banking. Use these to narrow or rank results, not to exclude everyone.
            TEXT],
        ['kind' => 'content', 'heading' => 'Step four, add filters', 'body' => <<<'TEXT'
            - Location, such as Charlotte or North Carolina, or a radius from the city.
            - Experience level, such as years of experience filters on job boards.
            - Work authorization, where the platform allows it and your company process approves it.
            - Recent activity, such as profiles updated in the last thirty days.
            TEXT],
        ['kind' => 'content', 'heading' => 'Step five, plan your sources', 'body' => <<<'TEXT'
            Decide where to search first: your internal database, LinkedIn, job boards such as Dice, university networks for recent graduates, and referrals. Sourcing & Resume Screening → Sourcing Channels covers each source.
            TEXT],
        ['kind' => 'content', 'heading' => 'Step six, build search strings', 'body' => <<<'TEXT'
            Combine keywords with AND, OR and NOT, called Boolean search, which the Boolean Search Basics tab covers in detail. For example: Java AND Spring Boot AND, in brackets, REST OR RESTful, AND SQL.
            TEXT],
        ['kind' => 'content', 'heading' => 'Review and adjust', 'body' => <<<'TEXT'
            - If you get too many results, add a secondary skill or narrow the location.
            - If you get too few results, use more title variations or widen the location.
            - Read a few profiles from the results to check that your keywords are finding the right people.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            For the running example, your plan is: search the internal database first for Java, Spring Boot and SQL profiles in North Carolina; then search LinkedIn for Java Developer or Backend Engineer with Spring Boot in the Charlotte area; then search Dice for recently updated profiles; and finally ask colleagues for referrals.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Primary Keywords', 'body' => <<<'TEXT'
            Primary keywords define the core match. Keep the list short, include variations, and always verify.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            Primary keywords are the words that must appear in a suitable candidate's profile or resume. They come from the must-have skills and the core job title. Usually there are only three to five.
            If a profile does not contain a primary keyword, the candidate is unlikely to meet the requirement.
            TEXT],
        ['kind' => 'content', 'heading' => 'How to identify primary keywords', 'body' => <<<'TEXT'
            - Look for skills in the job title, the summary and the required qualifications.
            - Look for skills repeated in the responsibilities.
            - Ask: Would the client reject a candidate without this skill? If yes, it is primary.
            TEXT],
        ['kind' => 'example', 'heading' => 'Examples', 'body' => <<<'TEXT'
            For a Java Developer role: Java, Spring Boot, REST, and SQL.
            For a Data Analyst role: SQL, Excel, and Tableau or Power BI.
            For a QA Automation Engineer role: Selenium, Java or Python, and test automation.
            For a Data Engineer role: Python or Scala, Spark, SQL, and a cloud platform such as AWS.
            TEXT],
        ['kind' => 'content', 'heading' => 'Variations and synonyms', 'body' => <<<'TEXT'
            - Candidates describe skills in different ways. Include common variations.
            - Spring Boot, Springboot and Spring.
            - REST, RESTful and REST API.
            - JavaScript and JS.
            - Machine learning and ML.
            - Continuous integration and CI.
            - Search for the main term and its variations together, using OR in Boolean search.
            TEXT],
        ['kind' => 'content', 'heading' => 'Using primary keywords in searches', 'body' => <<<'TEXT'
            - Combine primary keywords with AND, so that every result includes all of them.
            - Do not add too many primary keywords. Four strong primary keywords usually work better than ten.
            - Check a sample of results to make sure the keywords are finding relevant people.
            TEXT],
        ['kind' => 'content', 'heading' => 'Keywords are not enough', 'body' => <<<'TEXT'
            A keyword on a resume does not prove experience. A candidate may list a skill they used briefly in a class. Use keywords to find candidates, then verify skills on a call.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            For the running Java requirement, your primary keywords are Java, Spring Boot, REST and SQL. Your search is: Java AND, in brackets, Spring Boot OR Springboot, AND, in brackets, REST OR RESTful, AND SQL. You then read the top profiles to confirm real project experience.
            TEXT],
        ['kind' => 'content', 'heading' => 'From the company training material', 'body' => <<<'TEXT'
            The Job Description Analysis training's Data Architect example gives a model list of high-value keywords: AWS, Solution Architect, Enterprise Architect, API Gateway, Lambda, VPC, CloudFront, security architecture, Python and Java. It also warns: validate years of experience against the requirement instead of matching keywords alone. Keywords find candidates. They do not prove a match.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Secondary Keywords', 'body' => <<<'TEXT'
            Secondary keywords help you rank and stand out. Use them to refine, not to exclude.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            Secondary keywords come from preferred skills, supporting tools, domain experience and certifications. They are useful, but a candidate without them may still qualify.
            Use secondary keywords to rank candidates, to narrow very large result lists, and to highlight strengths in submissions.
            TEXT],
        ['kind' => 'example', 'heading' => 'Examples', 'body' => <<<'TEXT'
            For a Java Developer role: Kafka, AWS, Docker, Kubernetes, microservices, banking and Agile.
            For a Data Analyst role: Python, Snowflake, healthcare and statistics.
            For a QA Automation role: Cucumber, Jenkins, API testing and performance testing.
            TEXT],
        ['kind' => 'content', 'heading' => 'How to use secondary keywords', 'body' => <<<'TEXT'
            - Start with primary keywords only. If results are manageable, review them and score candidates on secondary keywords.
            - If results are too many, add one secondary keyword at a time to narrow the list.
            - Use OR between secondary keywords to find candidates with at least one of them, for example, in brackets, Kafka OR AWS OR Docker.
            - Record which secondary skills each shortlisted candidate has. Mention them in the submission summary.
            TEXT],
        ['kind' => 'content', 'heading' => 'Domain keywords', 'body' => <<<'TEXT'
            Domain keywords, such as banking, insurance, healthcare, retail or telecom, can be powerful differentiators. Clients often favour candidates who know their industry. But domain is rarely a must-have for entry-level OPT roles, so do not exclude candidates without it unless the requirement demands it.
            TEXT],
        ['kind' => 'content', 'heading' => 'Over-filtering', 'body' => <<<'TEXT'
            Adding too many secondary keywords with AND can shrink your results to almost nothing and hide good candidates. If you find yourself with very few results, remove secondary keywords first.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            Your Java search with primary keywords returns two hundred profiles in the Charlotte area. You add, AND, in brackets, Kafka OR AWS, which narrows it to sixty. You review the top twenty, shortlist five, and note that three have Kafka and two have AWS.
            TEXT],
        ['kind' => 'content', 'heading' => 'From the company training material', 'body' => <<<'TEXT'
            The Job Description Analysis training separates primary skills from secondary or preferred skills. In its Java and AWS example, Spring, Spring Boot and Spring Security are preferred and Terraform is a plus. These are secondary keywords: use them to rank candidates who already have Core Java and AWS, not to remove candidates who lack them.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Boolean Search Basics', 'body' => <<<'TEXT'
            A few Boolean operators make searches precise. Build strings in groups, test them, and refine.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            Boolean search uses special words, called operators, to combine keywords. Most recruiting platforms support it, although each platform has small differences. Check the help pages of your tools.
            TEXT],
        ['kind' => 'content', 'heading' => 'The main operators', 'body' => <<<'TEXT'
            - AND. Every result must contain both terms. Java AND Spring finds profiles with both words.
            - OR. Results may contain either term. Developer OR Engineer finds profiles with either word. Use OR for synonyms and variations.
            - NOT. Excludes a term. Java NOT JavaScript removes profiles that mention JavaScript. Use NOT carefully, because it can remove good candidates.
            - Quotation marks. Search for an exact phrase. Quote Spring Boot, end quote, finds that exact phrase rather than the two words anywhere.
            - Brackets. Group terms. Java AND, open bracket, Spring OR Hibernate, close bracket, means Java plus at least one of the two.
            TEXT],
        ['kind' => 'content', 'heading' => 'Building a search string step by step', 'body' => <<<'TEXT'
            Start with the title group, using OR. For example, open bracket, Java Developer in quotation marks, OR Java Engineer in quotation marks, OR Backend Developer in quotation marks, close bracket.
            Add the primary skill groups, using AND between groups and OR inside groups. For example, AND, open bracket, Spring Boot in quotation marks, OR Springboot, close bracket, AND, open bracket, REST OR RESTful, close bracket, AND SQL.
            Add secondary skills only if needed.
            Add NOT terms only to remove clearly irrelevant results, such as NOT intern if you need experienced candidates and the platform allows it.
            TEXT],
        ['kind' => 'content', 'heading' => 'Tips', 'body' => <<<'TEXT'
            - Write operators in capital letters. Many platforms require it.
            - Test your string and read a sample of results.
            - Save strings that work, for reuse.
            - Keep strings readable. Very long strings are hard to fix.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            For the running Java requirement, your LinkedIn string is: open bracket, Java Developer in quotation marks, OR Java Engineer in quotation marks, OR Backend Engineer in quotation marks, close bracket, AND, open bracket, Spring Boot in quotation marks, OR Springboot, close bracket, AND, open bracket, REST OR RESTful, close bracket. You add the Charlotte location filter separately.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Checklist & Common Mistakes', 'body' => <<<'TEXT'
            Every topic's checklist and common mistakes in one place. Use it as a quick review before you work on a real requirement.
            TEXT],
        ['kind' => 'reference', 'heading' => 'Recruiter Checklist', 'body' => <<<'TEXT'
            **Search Strategy**
            - Write a target profile for every requirement.
            - Choose sources and order them.
            - Prepare keywords in advance.
            - Set targets and time limits.
            - Review results and adjust.
            **Requirement → Search Keywords**
            - Build title, primary and secondary keyword lists from your analysis.
            - Include common spelling variations.
            - Plan the order of sources.
            - Adjust searches based on results.
            **Primary Keywords**
            - Choose three to five primary keywords from the must-haves.
            - Include common variations.
            - Combine with AND.
            - Verify on a call.
            **Secondary Keywords**
            - Keep secondary keywords separate from primary keywords.
            - Add them one at a time when narrowing.
            - Use OR to find any of several preferred skills.
            - Highlight them in submissions.
            **Boolean Search Basics**
            - Group synonyms with OR inside brackets.
            - Connect groups with AND.
            - Use quotation marks for exact phrases.
            - Use NOT sparingly.
            - Test and save strings.
            TEXT],
        ['kind' => 'mistakes', 'heading' => 'Common Mistakes', 'body' => <<<'TEXT'
            **Search Strategy**
            - Searching without a target profile.
            - Spending all your time on one source.
            - Not tracking which sources work.
            **Requirement → Search Keywords**
            - Searching with too many keywords, which removes good candidates.
            - Forgetting spelling variations.
            - Using bonus skills as must-have filters.
            **Primary Keywords**
            - Using every listed skill as a primary keyword.
            - Ignoring spelling variations.
            - Treating a keyword match as proof of experience.
            **Secondary Keywords**
            - Using secondary keywords with AND from the start.
            - Rejecting candidates without bonus skills.
            - Forgetting to mention bonus skills in the submission.
            **Boolean Search Basics**
            - Forgetting brackets, which changes the meaning.
            - Overusing NOT.
            - Using lower-case operators on platforms that need capitals.
            TEXT],
        ['kind' => 'note', 'heading' => 'Sources & Review', 'body' => <<<'TEXT'
            The company training documents do not cover Boolean search. This topic is based on general recruiting practice and needs review and approval by the training manager. Ask a senior recruiter which search strings your team uses on each portal.
            TEXT],
        ['kind' => 'takeaway', 'heading' => 'Key Takeaway', 'body' => <<<'TEXT'
            Plan before you search: build keywords from the requirement, start with the primary skills, add secondary terms carefully, and refine your Boolean strings as you go.
            TEXT],
    ],
];
