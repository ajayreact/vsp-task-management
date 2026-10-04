<?php

/*
 * Combined lesson "Reading & Analysing a Requirement". One topic per merged lesson:
 * Understanding Requirements, Reading Job Descriptions, Job Title, Job Summary, Company Description, Client, Work Authorization Check, Requirement Prioritization, Requirement → Search Keywords.
 */

return [
    'title' => 'Reading & Analysing a Requirement',
    'from' => 'Understanding Requirements',
    'compliance' => true,
    'review' => 'Includes topics that were awaiting compliance review: Work Authorization Check.',
    'en' => [
        ['kind' => 'objective', 'heading' => 'Learning Objective', 'body' => <<<'TEXT'
            Read any requirement methodically: understand the role, the job title, summary, company and client, check the work authorization rules, and decide which requirements to work on first.
            TEXT],
        ['kind' => 'flow', 'heading' => 'Lesson Map', 'body' => <<<'TEXT'
            1. Understanding Requirements [[Understanding Requirements]]
            2. Reading Job Descriptions [[Reading Job Descriptions]]
            3. Job Title [[Job Title]]
            4. Job Summary [[Job Summary]]
            5. Company Description [[Company Description]]
            6. Client [[Client]]
            7. Work Authorization Check [[Work Authorization Check]]
            8. Requirement Prioritization [[Requirement Prioritization]]
            9. Requirement → Search Keywords [[Requirement → Search Keywords]]
            10. Checklist & Common Mistakes [[Checklist & Common Mistakes]]
            TEXT],
        ['kind' => 'topic', 'heading' => 'Understanding Requirements', 'body' => <<<'TEXT'
            Analysis comes before action. A clear understanding of the requirement makes every other recruiting step faster and more accurate.
            TEXT],
        ['kind' => 'flow', 'heading' => 'The Analysis Method', 'body' => <<<'TEXT'
            1. Read Once. Understand the overall role without taking notes. [[Reading Job Descriptions]]
            2. Break It Into Parts. Title, summary, company, client and conditions. [[Job Title]]
            3. Must-Have vs Preferred. Separate what decides the shortlist from what is a bonus.
            4. Write a Summary. The real job in one sentence, in your own words.
            5. Keywords and Sources. Turn the summary into a search plan. [[Requirement → Search Keywords]]
            6. Prioritise. Decide where the requirement fits in your work. [[Requirement Prioritization]]
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            Every placement starts with a requirement. If you misunderstand the requirement, every later step, including sourcing, screening and submission, will go in the wrong direction. Strong recruiters spend a few focused minutes analysing a requirement before they search for a single candidate.
            A requirement usually arrives as a job description, called a JD, plus staffing details from the vendor or client, such as rate, duration and submission deadline.
            TEXT],
        ['kind' => 'content', 'heading' => 'The analysis method used in this level', 'body' => <<<'TEXT'
            Read the whole requirement once without taking notes, to understand the overall role.
            Read it again and break it into parts: job title, summary, company, qualifications, experience, skills, responsibilities, pay, location, work mode, duration, rate, start date, client and work authorization rules.
            Separate must-have requirements from preferred ones.
            Write a short requirement summary in your own words.
            Turn the summary into search keywords and a sourcing plan.
            Decide the priority of the requirement against your other work.
            The following lessons cover each of these parts in turn.
            TEXT],
        ['kind' => 'content', 'heading' => 'From the company training material', 'body' => <<<'TEXT'
            This course follows the Day 5 Job Description Analysis training. By the end of it, you should be able to understand requirements and break them into actionable sourcing criteria, analyse job descriptions for must-have skills, experience, responsibilities and job conditions, screen resumes against requirement-specific keywords and experience, and source accurately.
            The training describes the recruitment process in four steps. First, get the requirement from the Account Manager or the BDM. Second, identify sources, such as Dice, job posts, vendors, groups, the internal database and your network. Third, identify qualified people by matching skills, experience, location, rate and work authorization. Fourth, communicate: discuss the opportunity and confirm the candidate's interest and details.
            It then gives the Day 5 recruiter workflow in six words. Read the entire requirement. Extract the location, duration, rate, skills, dates and client. Prioritize must-have against preferred criteria. Source through the right channels with targeted keywords. Screen skills, experience, location and other conditions. Communicate to confirm interest and fit before submission.
            Its key message is: the quality of sourcing starts with the quality of requirement analysis.
            TEXT],
        ['kind' => 'content', 'heading' => 'The two requirement examples from the training', 'body' => <<<'TEXT'
            Example one is a Data Architect in McLean, Virginia, day-one onsite, three days onsite and two remote, on contract. It asks for five or more years of architecture governance, technology selection, architecture review boards and cloud migration, five or more years of AWS solutions, enterprise cloud and application security, API gateways and integration patterns, and an enterprise or solution architect background with Python or Java. The AWS stack includes EC2, ECS Fargate, Lambda, PostgreSQL, MongoDB, SageMaker, Athena, Glue, VPC and CloudFront.
            Example two is a Java and AWS Full Stack Developer in Draper, Utah, ninety percent remote, on a twelve-month contract, Utah local with a driving licence. It asks for twelve or more years of experience, Core Java and JEE, with Spring, Spring Boot and Spring Security preferred, HTML5, CSS and JavaScript frameworks, REST and SOAP services, AWS API Gateway, Elastic Beanstalk and CloudFormation, with Terraform as a plus, and CI and CD, GitHub and SQL. It also mentions a video interview, a ten-panel drug test, and visa restrictions that must be verified.
            You will see these two examples again in later tabs and lessons of this course.
            TEXT],
        ['kind' => 'content', 'heading' => 'The running example used in this level', 'body' => <<<'TEXT'
            - Title: Java Developer.
            - **Client:** a large banking client.
            - Location: Charlotte, NC, hybrid, three days onsite each week.
            - Duration: twelve months, with possible extension.
            - Engagement: W2 contract.
            - Start date: within two weeks.
            - Required: three or more years of Java, Spring Boot, REST APIs and SQL.
            - Preferred: Kafka, AWS and banking domain experience.
            - Responsibilities: develop and maintain microservices, write unit tests, and work in an Agile team.
            TEXT],
        ['kind' => 'content', 'heading' => 'Questions a good analysis answers', 'body' => <<<'TEXT'
            - What is the real job, in one sentence?
            - Which three or four skills will decide whether a candidate is shortlisted?
            - Where must the person be, and how often?
            - When must they start, and for how long?
            - Are there any work authorization restrictions?
            - What is unclear and needs to be asked?
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            Using the running example, a strong recruiter summarises: A mid-level Java backend developer for a bank in Charlotte, three days a week in the office, starting in two weeks for one year. Must have Java, Spring Boot, REST and SQL. Kafka and AWS are a plus.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Reading Job Descriptions', 'body' => <<<'TEXT'
            Read job descriptions in deliberate passes and pay attention to the language. Small words like required and preferred change everything.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            The Job Description Analysis training defines a job description, or JD, as a written statement of the role, its expectations and its qualifications. Your goal is to turn the JD into searchable keywords and objective screening criteria.
            Staffing requirements often add a vendor header with location, duration, rate, engagement type, interview process and submission instructions. Read both the header and the description.
            TEXT],
        ['kind' => 'reference', 'heading' => 'The Eight Parts of a JD', 'body' => <<<'TEXT'
            1. Job title
            2. Job summary
            3. Company description
            4. Requirements or qualifications
            5. Experience
            6. Skills
            7. Responsibilities
            8. Pay or benefits
            TEXT],
        ['kind' => 'content', 'heading' => 'Read in Four Passes', 'body' => <<<'TEXT'
            1. **First pass.** Read the whole JD quickly. What will this person actually do every day?
            2. **Second pass.** Note the title, location and work mode, duration, start date and must-have skills.
            3. **Third pass.** Look for hidden requirements: a domain such as banking or healthcare, a certification, a specific tool version, or a phrase like local candidates only.
            4. **Final check.** Re-read the submission instructions and the deadline.
            TEXT],
        ['kind' => 'reference', 'heading' => 'Reading the Language of a JD', 'body' => <<<'TEXT'
            | Words in the JD | What they usually mean |
            | --- | --- |
            | Required, must have, minimum | The client will usually reject candidates without it |
            | Preferred, nice to have, plus, bonus | Helpful, but not essential |
            | Strong, expert, hands-on | Real depth of experience, not just familiarity |
            | Exposure to, familiarity with | A lower level of depth is acceptable |
            | Years of experience | A guideline or a strict filter. Ask the vendor if unclear |
            TEXT],
        ['kind' => 'content', 'heading' => 'Spotting Problems in a JD', 'body' => <<<'TEXT'
            - A long list of unrelated technologies may mean the JD was copied from older roles. Ask which skills matter most.
            - Conflicting information, such as remote in one place and onsite in another, must be clarified before sourcing.
            - A very low rate for a senior skill set may make the requirement hard to fill. Flag it to your lead.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            A Java Developer JD mentions Kafka streaming pipelines in the responsibilities, but lists Kafka only as preferred. You ask the vendor whether Kafka is essential. The vendor confirms it is preferred, but candidates with Kafka will be prioritised. You note this in your requirement summary.
            TEXT],
        ['kind' => 'practice', 'heading' => 'Practice', 'body' => <<<'TEXT'
            1. Name the eight parts of a JD from the training.
            2. A JD lists AWS as nice to have. Should you reject a candidate without AWS?
            3. The header says remote, but the description says onsite three days a week. What do you do?
            4. What do you check in the final pass?
            TEXT],
        ['kind' => 'topic', 'heading' => 'Job Title', 'body' => <<<'TEXT'
            A title is a starting point. Use it to build a list of similar titles, and judge candidates by what they actually did.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            The job title is the first clue to the role, but titles vary widely between companies. A Software Engineer at one company may do the same work as a Java Developer at another. A Data Analyst at one client may need advanced engineering skills, while at another the role is mainly reporting.
            TEXT],
        ['kind' => 'content', 'heading' => 'From the company training material', 'body' => <<<'TEXT'
            The Job Description Analysis training gives one rule for the title: titles vary by company, so do not judge a role by its title alone. Its key takeaways repeat this: do not rely on the job title alone, read the summary, skills and responsibilities. For example, the training's Data Architect requirement also expects an enterprise or solution architect background, so profiles titled Solution Architect or Enterprise Architect are relevant even though the title says Data Architect.
            TEXT],
        ['kind' => 'content', 'heading' => 'Parts of a title', 'body' => <<<'TEXT'
            - Level, such as junior, associate, mid-level, senior, lead, principal or architect.
            - Function, such as developer, engineer, analyst, tester or administrator.
            - Technology or domain, such as Java, Python, Salesforce, data, cloud or QA automation.
            TEXT],
        ['kind' => 'content', 'heading' => 'What level words usually suggest', 'body' => <<<'TEXT'
            - Junior or associate usually means zero to two years of experience.
            - Mid-level or no level word often means about two to five years.
            - Senior often means five or more years, with independent ownership.
            - Lead or architect suggests leadership or design responsibility.
            - These are only guides. Always compare with the experience section.
            TEXT],
        ['kind' => 'content', 'heading' => 'Similar titles to search for', 'body' => <<<'TEXT'
            - A Java Developer requirement may match profiles titled Java Engineer, Software Engineer, Backend Developer, Full Stack Developer with Java, or Application Developer.
            - A Data Analyst requirement may match Business Intelligence Analyst, Reporting Analyst or Data Specialist profiles, if the skills match.
            - A QA Automation Engineer requirement may match SDET, which stands for Software Development Engineer in Test, or Test Automation Engineer.
            TEXT],
        ['kind' => 'content', 'heading' => 'Titles and OPT candidates', 'body' => <<<'TEXT'
            OPT candidates are often early in their careers. Their titles may include intern, graduate assistant, research assistant or project roles from university. Look at what they actually did, not only their titles.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            For the running Java Developer example, you search for Java Developer, Java Software Engineer, Backend Engineer with Java and Spring Boot Developer. You also review profiles titled Software Engineer whose project descriptions show Java and Spring Boot.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Job Summary', 'body' => <<<'TEXT'
            The job summary tells you why the role exists. Turn it into a clear one-line summary that you can explain in seconds.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            The job summary, sometimes called the position overview or about the role, is a short paragraph at the start of the JD. It explains why the role exists and what the person will mainly do.
            Summaries are often written in marketing language, so look past phrases like exciting opportunity and dynamic team to find the facts.
            TEXT],
        ['kind' => 'content', 'heading' => 'From the company training material', 'body' => <<<'TEXT'
            The Job Description Analysis training says the summary is where you look for the role's focus, the business context and useful keywords.
            TEXT],
        ['kind' => 'content', 'heading' => 'What to look for', 'body' => <<<'TEXT'
            - The main purpose of the role, such as building new features, maintaining existing systems, migrating to the cloud or supporting reporting.
            - The team or project, such as a payments platform or a data warehouse.
            - The working style, such as Agile, cross-functional, or customer-facing.
            - Signals of seniority, such as own, lead, design or mentor.
            - Signals of urgency, such as immediate need or quick start.
            TEXT],
        ['kind' => 'content', 'heading' => 'Writing your own one-line summary', 'body' => <<<'TEXT'
            A strong one-line summary includes the level, the role, the key technology, the domain, the location and work mode, and the duration. It should be easy to read aloud on a call.
            Template: A level role, working with key skills, for a domain client, in location and work mode, for duration.
            TEXT],
        ['kind' => 'content', 'heading' => 'Why your summary matters', 'body' => <<<'TEXT'
            - You will use it in your notes, when explaining the role to candidates, and when sharing it with teammates.
            - A clear summary helps a candidate decide quickly whether they are interested.
            - If you cannot write the summary, you do not yet understand the role. Ask questions.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            The running example's summary says: Join our digital banking team to build scalable microservices that power customer payments, in a collaborative Agile environment. Your one-line summary becomes: A mid-level Java backend developer, working with Spring Boot microservices and REST APIs, for a banking client's payments platform, in Charlotte, North Carolina, hybrid three days a week, for a twelve-month contract.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Company Description', 'body' => <<<'TEXT'
            The company description reveals the domain and environment. Use it to find and highlight candidates who will fit naturally.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            Many JDs include a paragraph about the company, sometimes called about us. In staffing requirements, this may describe the end client, the vendor, or may be removed to keep the client confidential.
            TEXT],
        ['kind' => 'content', 'heading' => 'From the company training material', 'body' => <<<'TEXT'
            The Job Description Analysis training says the company description helps you understand the organisation and the working environment. The requirement analysis slide adds that client name or client information should be captured where permitted.
            TEXT],
        ['kind' => 'content', 'heading' => 'What the company description can tell you', 'body' => <<<'TEXT'
            - The industry or domain, such as banking, insurance, healthcare, retail, telecom, automotive or government.
            - The size of the organisation, which affects processes and culture.
            - The company's products or services, which help candidates relate their experience.
            - Values and culture, which can help you prepare candidates for behavioural interviews.
            - Locations, which can help confirm where the team sits.
            TEXT],
        ['kind' => 'content', 'heading' => 'Domain matters', 'body' => <<<'TEXT'
            Domain knowledge can be a strong advantage. A candidate who built payment applications may stand out for a banking client. Healthcare roles may value knowledge of healthcare data standards. Insurance roles may value claims or policy systems experience.
            When the domain is listed as preferred, candidates with domain experience should be highlighted in your summary.
            TEXT],
        ['kind' => 'content', 'heading' => 'Confidential clients', 'body' => <<<'TEXT'
            When the client is not named, the description may say a Fortune 500 financial client or a leading healthcare provider. Do not guess the client's name to candidates. Follow your company's rules for when and how the client name is shared.
            TEXT],
        ['kind' => 'content', 'heading' => 'Researching the company', 'body' => <<<'TEXT'
            If the client is named, spend a few minutes on its official website to understand its business. This helps you speak about the role confidently and prepare candidates.
            Never share internal or confidential client information you may have learned elsewhere.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            For the running example, the description says the client is one of the largest banks in the U.S., with a focus on digital transformation. You note banking domain and digital transformation. When screening, you ask candidates about any banking, payments or financial services projects, and you highlight those in your submission.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Client', 'body' => <<<'TEXT'
            Knowing the client and the vendor chain helps you prepare candidates and avoid costly mistakes.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            In requirement analysis, client means the end client, the organisation where the consultant will work. The requirement may name the client, describe it, or hide it.
            Who the client and the vendors are, and the confidentiality rules for client information, are taught in **U.S. IT Staffing & Payroll Fundamentals → U.S. IT Staffing Model & Key Players**. Here you identify them in a specific requirement.
            TEXT],
        ['kind' => 'content', 'heading' => 'From the company training material', 'body' => <<<'TEXT'
            The Job Description Analysis training says to capture the client name or client information, where permitted. The US MNC Staffing document explains that requirements arrive from the client or from a preferred vendor, also called the Tier 1 layer, through the BDM, and that client names and project details are protected by the NDA.
            TEXT],
        ['kind' => 'content', 'heading' => 'What to identify about the client', 'body' => <<<'TEXT'
            - The client name, if shared, and the industry.
            - The vendor chain: who sent the requirement, and who that vendor works for.
            - The client's interview style, if known from previous requirements.
            - Any client rules, such as no duplicate submissions, maximum submissions per vendor, required forms, or background checks.
            - The client's sponsorship policy or work authorization restrictions, if stated.
            TEXT],
        ['kind' => 'content', 'heading' => 'Why the client matters', 'body' => <<<'TEXT'
            - Different clients have different expectations. Some are known for detailed technical interviews. Some require long background checks. Some prefer local candidates strongly.
            - Knowing the client helps you prepare candidates and set realistic expectations.
            - If a candidate has worked at the same client before, or has already applied there, the submission may be affected. Always ask.
            TEXT],
        ['kind' => 'content', 'heading' => 'Information sharing', 'body' => <<<'TEXT'
            Share the client name with candidates only when your process allows, following the confidentiality rules in U.S. IT Staffing Model & Key Players → Client.
            Never contact the end client directly about a requirement that came through a vendor, unless your company has authorised this. Going around a vendor can damage relationships and breach agreements.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            The running example came from an implementation partner working for a large bank. You note: end client, the bank; vendor, the implementation partner. Your team's notes show this bank usually conducts two technical rounds on video with cameras on. You prepare candidates for this, and you ask each one whether they have applied to this bank in the last six months.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Work Authorization Check', 'body' => <<<'TEXT'
            Work authorization rules are set by the client. Check every candidate consistently, record accurately, and never misrepresent.
            Visa statuses, EAD dates and escalation are taught in **Immigration & Work Authorization**. This topic covers only the requirement checkpoint.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            - Many requirements include work authorization rules. You will see phrases such as these.
            - No sponsorship, or must be authorized to work without sponsorship.
            - Citizens and green card holders only, sometimes written as USC and GC only.
            - No OPT or STEM OPT, or no H-1B.
            - OPT and STEM OPT accepted.
            - Some government or defence roles require U.S. citizenship and security clearance.
            TEXT],
        ['kind' => 'content', 'heading' => 'From the company training material: the recruiter checkpoint', 'body' => <<<'TEXT'
            The Job Description Analysis training has a dedicated slide called Work Authorization, Recruiter Checkpoint. Its rule is: always read the actual requirement and confirm client-specific eligibility.
            Do not assume. Visa eligibility can vary by client, role, contract type and conversion terms.
            Capture the requirement. Record any stated restrictions, such as No H-1B, No CPT or OPT, or citizen-only language.
            Confirm before submission. When wording is unclear, verify with the Account Manager or BDM before presenting the candidate.
            Document accurately. Do not alter or misrepresent a candidate's work authorization.
            The training's Java and AWS example includes the note that stated visa restrictions must be verified. The US MNC Staffing document also tells recruiters to note which visa statuses the client is looking for, such as Green Card, OPT, EAD, H-1B or U.S. citizen.
            TEXT],
        ['kind' => 'content', 'heading' => 'Why requirements include these rules', 'body' => <<<'TEXT'
            Some clients do not sponsor visas or prefer not to employ candidates who may need sponsorship later. Some government contracts legally require citizenship. Whatever the reason, the rule is set by the client. Recruiters must follow it.
            TEXT],
        ['kind' => 'content', 'heading' => 'How to check', 'body' => <<<'TEXT'
            Read the work authorization rule before sourcing.
            Ask every candidate the same approved questions, such as: Are you currently authorized to work in the U.S.? and Will you now or in the future require sponsorship for employment visa status?
            Record the candidate's answer and their current authorization type and dates, through the approved process.
            Compare the answer with the requirement's rule.
            If the rule is unclear, ask the vendor, for example: Does the client accept candidates on STEM OPT?
            TEXT],
        ['kind' => 'content', 'heading' => 'What not to do', 'body' => <<<'TEXT'
            - Do not submit a candidate whose authorization does not meet the requirement, even if they are otherwise excellent.
            - Do not describe a candidate's authorization inaccurately to fit a requirement.
            - Do not ask about national origin, citizenship country, religion or other protected characteristics.
            - Do not treat candidates differently because of their name, accent or background.
            - Do not interpret documents yourself. HR handles verification.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            A requirement says: No OPT, must not require sponsorship. Your best Java candidate is on STEM OPT. You do not submit her for this requirement. You note the restriction, keep her for other requirements, and look for candidates who meet this one.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Requirement Prioritization', 'body' => <<<'TEXT'
            Prioritise by your real chance of success. Focus your best time on clear, workable requirements.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            Recruiters often receive more requirements than they can work on at once. Spending equal time on every requirement is not effective. Prioritise requirements that you have the best chance of filling, with clear information and responsive partners.
            TEXT],
        ['kind' => 'content', 'heading' => 'From the company training material', 'body' => <<<'TEXT'
            The US MNC Staffing document explains that the BDM assigns each requirement to a recruiter based on technology or skill set, so your priorities usually start with what the BDM has assigned to you. The Job Description Analysis training adds a final piece of advice: ask senior recruiters for role-specific screening practices, and observe how they analyse requirements.
            TEXT],
        ['kind' => 'content', 'heading' => 'Factors that increase priority', 'body' => <<<'TEXT'
            - Clear and complete requirement details.
            - Skills that match candidates you have or can find quickly.
            - A workable rate.
            - Work authorization rules that fit your candidate pool, such as OPT accepted.
            - An urgent but realistic start date.
            - A responsive vendor with a good history of feedback.
            - Multiple openings for the same role.
            - Instructions from your team lead about key clients.
            TEXT],
        ['kind' => 'content', 'heading' => 'Factors that decrease priority', 'body' => <<<'TEXT'
            - Vague requirements with long, unrelated skill lists.
            - A rate that does not match the experience required.
            - Restrictions that exclude most of your candidates.
            - Vendors who rarely respond or give feedback.
            - Requirements that many other companies are already submitting to, with strict submission limits.
            TEXT],
        ['kind' => 'content', 'heading' => 'A simple priority method', 'body' => <<<'TEXT'
            - High priority. Clear requirement, good fit with your pool, workable rate, responsive vendor. Work on it immediately.
            - Medium priority. Some gaps or uncertainty. Clarify with the vendor, then work on it.
            - Low priority. Poor fit or unclear. Do a quick search, and spend more time only if you find a strong match.
            - Always follow your team lead's instructions where they set priorities.
            TEXT],
        ['kind' => 'content', 'heading' => 'Time management', 'body' => <<<'TEXT'
            - Set aside focused time for your high-priority requirements during the U.S. morning, when vendors and candidates are most responsive.
            - Track your submissions and follow-ups for each requirement.
            - Review priorities at least once a day, because new information changes them.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            You have three requirements: the running Java example, which is clear, OPT-friendly and urgent; a senior architect role with a low rate; and a vague data role from a vendor who has not responded to your last three submissions. You work on the Java requirement first, ask your lead about the architect rate, and do a quick search for the data role before clarifying it with the vendor.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Requirement → Search Keywords', 'body' => <<<'TEXT'
            Your analysis becomes your search. Clear keyword lists and a source plan lead to faster, better shortlists.
            **Full lesson:** Sourcing & Resume Screening → Search Strategy, Keywords & Boolean (Requirement → Search Keywords).
            TEXT],
        ['kind' => 'topic', 'heading' => 'Checklist & Common Mistakes', 'body' => <<<'TEXT'
            Every topic's checklist and common mistakes in one place. Use it as a quick review before you work on a real requirement.
            TEXT],
        ['kind' => 'reference', 'heading' => 'Recruiter Checklist', 'body' => <<<'TEXT'
            **Understanding Requirements**
            - Read every requirement twice before sourcing.
            - Break it into parts and write a short summary.
            - Note anything unclear and ask before you search.
            **Job Title**
            - Identify the level, function and technology in the title.
            - Build a list of similar titles for searching.
            - Check the experience section to confirm the level.
            **Job Summary**
            - Identify the purpose, team, working style and seniority in the summary.
            - Write a one-line summary for every requirement.
            - Use the summary when you first speak to candidates.
            **Company Description**
            - Note the domain and size of the client.
            - Use domain experience as a differentiator.
            - Follow confidentiality rules for client names.
            **Client**
            - Identify the end client and the vendor chain.
            - Note client rules and interview patterns.
            - Ask candidates about previous applications to the client.
            - Respect the vendor relationship.
            **Work Authorization Check**
            - Read work authorization rules before sourcing.
            - Use the same approved questions with every candidate.
            - Record authorization type and dates accurately.
            - Ask the vendor when rules are unclear.
            **Requirement Prioritization**
            - Score each requirement on clarity, fit, rate, restrictions, urgency and vendor quality.
            - Work on high-priority requirements first.
            - Review priorities daily with your lead.
            TEXT],
        ['kind' => 'mistakes', 'heading' => 'Common Mistakes', 'body' => <<<'TEXT'
            **Understanding Requirements**
            - Searching immediately after reading only the job title.
            - Treating every listed skill as equally important.
            - Ignoring location and start date until after submission.
            **Reading Job Descriptions**
            - Reading only the vendor header and not the description.
            - Missing a hidden domain or certification requirement.
            - Assuming years of experience are flexible without asking.
            **Job Title**
            - Searching only for the exact title.
            - Rejecting candidates because their title is different, even when their work matches.
            - Assuming a senior title means a fixed number of years.
            **Job Summary**
            - Copying the marketing summary into your notes.
            - Leaving out the location or duration from the summary.
            **Company Description**
            - Ignoring the domain because it is not in the skills list.
            - Guessing or revealing a confidential client's name.
            **Client**
            - Confusing the vendor with the end client.
            - Contacting the end client directly.
            - Missing a duplicate submission.
            **Work Authorization Check**
            - Submitting candidates who do not meet the stated rule.
            - Asking non-approved or discriminatory questions.
            - Changing a candidate's stated status in a submission.
            **Requirement Prioritization**
            - Working on requirements in the order they arrive.
            - Spending hours on requirements with no realistic chance.
            TEXT],
        ['kind' => 'takeaway', 'heading' => 'Key Takeaway', 'body' => <<<'TEXT'
            Read every requirement twice, break it into parts, identify the client and the work authorization rules, and work first on the requirements you have a real chance of filling.
            TEXT],
    ],
];
