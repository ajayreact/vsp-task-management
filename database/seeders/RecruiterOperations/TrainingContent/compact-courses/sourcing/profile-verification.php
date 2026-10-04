<?php

/*
 * Combined lesson "Profile Verification & Resume vs JD". One topic per merged lesson:
 * Location, LinkedIn Review, Job History, Resume vs JD Comparison.
 */

return [
    'title' => 'Profile Verification & Resume vs JD',
    'from' => 'Resume vs JD Comparison',
    'en' => [
        ['kind' => 'objective', 'heading' => 'Learning Objective', 'body' => <<<'TEXT'
            Verify a profile before you call or submit: confirm location fit, review the LinkedIn profile and job history for consistency, and compare the resume with the JD to reach a clear submit decision.
            TEXT],
        ['kind' => 'flow', 'heading' => 'Lesson Map', 'body' => <<<'TEXT'
            1. Location [[Location]]
            2. LinkedIn Review [[LinkedIn Review]]
            3. Job History [[Job History]]
            4. Resume vs JD Comparison [[Resume vs JD Comparison]]
            5. Checklist & Common Mistakes [[Checklist & Common Mistakes]]
            TEXT],
        ['kind' => 'topic', 'heading' => 'Location', 'body' => <<<'TEXT'
            Location must be confirmed, not assumed. Accurate location details prevent rejected submissions and payroll problems.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            Location fit is one of the most common reasons submissions fail. A candidate may appear local on a resume but have moved, or may list a city where they studied rather than where they live now.
            TEXT],
        ['kind' => 'reference', 'heading' => 'What to confirm', 'body' => <<<'TEXT'
            - Current city and state.
            - How long they have lived there, if relevant.
            - Willingness to relocate, and how soon.
            - Commute expectations for hybrid and onsite roles.
            - For remote roles, the state they will work from, and their time zone.
            TEXT],
        ['kind' => 'content', 'heading' => 'Where location information comes from', 'body' => <<<'TEXT'
            - The resume header, which may be outdated.
            - LinkedIn location, which may also be outdated.
            - The candidate's own confirmation on a call, which is the most reliable.
            - Always confirm directly.
            TEXT],
        ['kind' => 'content', 'heading' => 'Assessing fit', 'body' => <<<'TEXT'
            Local means within a reasonable commute of the work location. Reasonable depends on the area. In large metropolitan areas, an hour's commute may be common. Ask the candidate whether the commute works for them.
            Relocation candidates should confirm that they are willing and able to move before the start date.
            For local-only requirements, submit only candidates who already live locally, unless the vendor confirms otherwise.
            TEXT],
        ['kind' => 'content', 'heading' => 'Honesty in submissions', 'body' => <<<'TEXT'
            Write the candidate's actual current location in the submission, and add relocation details if relevant, for example currently in Dallas, Texas, willing to relocate to Charlotte, North Carolina, before the start date.
            Never describe a relocating candidate as local.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            A resume shows an address in Charlotte. On the call, the candidate says he moved to New Jersey two months ago for an internship that ended, and he can move back to Charlotte within two weeks. You record his current location as New Jersey and note that he is willing to relocate to Charlotte within two weeks.
            TEXT],
        ['kind' => 'content', 'heading' => 'From the company training material', 'body' => <<<'TEXT'
            The Day 5 workflow includes location in the Screen step: validate skills, experience, location and other conditions. The OPT calling script asks two questions on every call: Where are you located? and Are you willing to relocate? The training's Java and AWS example shows why: it requires Utah local candidates with a driving licence, so a resume that only says United States is not enough.
            TEXT],
        ['kind' => 'topic', 'heading' => 'LinkedIn Review', 'body' => <<<'TEXT'
            LinkedIn review supports accurate submissions. Check consistency, ask respectfully, and resolve differences before submitting.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            Many clients and vendors look at a candidate's LinkedIn profile before or during the interview process. Reviewing it yourself helps you spot inconsistencies early, understand the candidate better and prepare them.
            TEXT],
        ['kind' => 'content', 'heading' => 'What to review', 'body' => <<<'TEXT'
            - Name, so that it matches the resume.
            - Headline and summary.
            - Job titles, companies and dates.
            - Education, degrees and dates.
            - Skills and endorsements.
            - Recommendations, if any.
            - Activity, such as posts about projects or learning.
            - Profile completeness and professionalism.
            TEXT],
        ['kind' => 'content', 'heading' => 'Checking consistency', 'body' => <<<'TEXT'
            - Compare job titles, companies and dates with the resume.
            - Compare education details.
            - Note any significant differences, such as a job on the resume that does not appear on LinkedIn, or different dates.
            - Small differences are common, for example if a profile has not been updated. Large differences need a polite question.
            TEXT],
        ['kind' => 'content', 'heading' => 'Asking about differences', 'body' => <<<'TEXT'
            Ask in a neutral, respectful way: I noticed your LinkedIn shows your role ended in March, but your resume shows it ending in June. Could you help me understand which is correct?
            Record the answer. Ask the candidate to update the resume or profile if something is outdated.
            If differences remain unclear or suggest misrepresentation, escalate to your lead. Do not submit until resolved.
            TEXT],
        ['kind' => 'content', 'heading' => 'Advising candidates on their profile', 'body' => <<<'TEXT'
            You may suggest, politely, that a candidate keeps their LinkedIn profile up to date and consistent with their resume, because clients may look at it.
            Do not ask candidates to change facts. Only suggest updates that make the profile accurate.
            TEXT],
        ['kind' => 'content', 'heading' => 'Privacy', 'body' => <<<'TEXT'
            Review only what the candidate has made visible professionally. Do not look for unrelated personal information.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            A candidate's resume lists two years at a software company. Her LinkedIn shows one year at the same company and a separate year at a university as a graduate assistant. You ask politely, and she explains that she combined the two roles on her resume by mistake. You ask her to correct the resume so that it lists both roles separately before you submit.
            TEXT],
        ['kind' => 'content', 'heading' => 'From the company training material', 'body' => <<<'TEXT'
            The Job Description Analysis training says to review LinkedIn for consistency and additional professional context, and to use public professional information only from approved, lawful sources, avoiding unsupported conclusions. A difference between the resume and the profile is a question to ask the candidate, not proof of anything.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Job History', 'body' => <<<'TEXT'
            Job history tells a candidate's story. Read it carefully, ask fair questions, and keep every detail accurate.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            Job history is the list of a candidate's roles, with employers, titles and dates. It shows how their career has developed, how long they stay in roles, and how recent their relevant experience is.
            TEXT],
        ['kind' => 'content', 'heading' => 'What to look at', 'body' => <<<'TEXT'
            - Order and dates. Most U.S. resumes list the most recent role first.
            - Duration of each role.
            - Progression, such as moving from junior to more responsible roles.
            - Type of employment: full time, contract, internship, CPT, part time or academic.
            - Location of each role.
            - Gaps between roles.
            - Overlapping roles.
            TEXT],
        ['kind' => 'content', 'heading' => 'Gaps', 'body' => <<<'TEXT'
            Gaps are common, especially for international students who moved to the U.S. to study. A gap may be explained by study, job searching, family reasons or visa processing. Ask neutrally: What were you doing between these two roles? Accept reasonable explanations. Do not judge candidates for gaps.
            Do not ask about protected personal matters, such as health, pregnancy or family plans.
            TEXT],
        ['kind' => 'content', 'heading' => 'Overlaps', 'body' => <<<'TEXT'
            Overlapping roles can be normal, for example a part-time graduate assistant role during a full-time study program. But overlapping full-time jobs need a polite question, because they can raise concerns with clients.
            TEXT],
        ['kind' => 'content', 'heading' => 'Short roles', 'body' => <<<'TEXT'
            Many short roles may be explained by contract work. Ask whether roles were contracts and how long each was planned to last.
            TEXT],
        ['kind' => 'content', 'heading' => 'U.S. and non-U.S. experience', 'body' => <<<'TEXT'
            Note which roles were in the U.S. and which were abroad. Some clients value U.S. experience for client-facing roles. Describe each accurately.
            TEXT],
        ['kind' => 'content', 'heading' => 'Honesty', 'body' => <<<'TEXT'
            - Never change dates or merge roles to hide gaps.
            - Never remove roles to make a resume look different, without the candidate's agreement and accuracy.
            - If a candidate asks you to change dates, decline politely and explain that you must submit accurate information.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            A resume shows a developer role in India until July 2023, a master's program from August 2023 to May 2025, and an internship from May to August 2024. You note that the internship overlaps with the master's program, which is normal for a summer internship, possibly on CPT. You ask the candidate to confirm the internship type and record it accurately.
            TEXT],
        ['kind' => 'content', 'heading' => 'From the company training material', 'body' => <<<'TEXT'
            The Job Description Analysis checklist includes job history and tenure pattern, and formatting, readability, grammar and spelling. Tenure pattern means how long the candidate stayed in each role. Short contract roles are normal in IT staffing, so ask about them rather than assuming a problem.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Resume vs JD Comparison', 'body' => <<<'TEXT'
            A structured comparison leads to clear decisions and honest, persuasive submissions. Accuracy protects the candidate, the client and your company.
            The honesty rules for every submission are in **U.S. IT Staffing & Payroll Fundamentals → Requirement to Placement Lifecycle** (Submission).
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            The resume versus JD comparison is the final screening step before a call or a submission. It uses your requirement analysis from the Job Requirement Analysis course and the screening skills from this course. The result is a clear decision and a short, accurate summary.
            TEXT],
        ['kind' => 'content', 'heading' => 'A comparison template', 'body' => <<<'TEXT'
            - For each item, write met, partly met or not met, with a short note.
            - Title and level.
            - Primary skills, one line each.
            - Years of relevant experience.
            - Education.
            - Location and work mode fit.
            - Start date fit.
            - Work authorization fit, based on approved screening.
            - Preferred skills.
            - Domain experience.
            - Consistency check, including LinkedIn and job history.
            - Open questions.
            TEXT],
        ['kind' => 'flow', 'heading' => 'Making the decision', 'body' => <<<'TEXT'
            1. Every Must-Have Met? Submit when the candidate has also confirmed interest, location, availability and rate.
            2. One Must-Have Partly Met? Ask the vendor first, describing the gap honestly.
            3. A Must-Have Not Met? Do not submit.
            4. Work Authorization Does Not Match? Do not submit.
            5. Unresolved Inconsistencies? Do not submit until they are resolved.
            TEXT],
        ['kind' => 'content', 'heading' => 'Writing the submission summary', 'body' => <<<'TEXT'
            - Start with a one-line overview: a mid-level Java developer with two years and eight months of Spring Boot microservices experience.
            - List how the candidate meets each primary skill, with brief evidence.
            - Mention relevant preferred skills and domain experience.
            - Include location, relocation, availability and other required details.
            - Keep it factual. Do not exaggerate.
            TEXT],
        ['kind' => 'content', 'heading' => 'Avoiding inaccurate submissions', 'body' => <<<'TEXT'
            - Re-read the resume and your notes before sending.
            - Make sure the resume is the latest version confirmed by the candidate.
            - Check that the names, dates and details in your summary match the resume.
            - Make sure the candidate has given consent for this specific submission.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            For the running Java requirement, your comparison shows: Java, strong; Spring Boot, strong; REST, strong; SQL, moderate; experience, two years and ten months, partly met; education, master's in computer science, met; location, willing to relocate to Charlotte in two weeks; start date, met; work authorization, matches the requirement; Kafka, yes; banking domain, no. You ask the vendor whether two years and ten months is acceptable. The vendor agrees, so you submit with an honest summary.
            TEXT],
        ['kind' => 'content', 'heading' => 'From the company training material', 'body' => <<<'TEXT'
            The Job Description Analysis training's learning objective for this skill is to compare candidate profiles against requirement-specific keywords and experience. Its workflow ends with screen, validating skills, experience, location and other conditions, and communicate, confirming interest and requirement fit before submission. Where a client provides a scoring framework, such as the Java and AWS example's split of ten, thirty, twenty-five, fifteen and twenty percent, use it as an additional guide. The US MNC Staffing document then says to select the best one or two resumes for each requirement, rather than sending many weak ones.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Checklist & Common Mistakes', 'body' => <<<'TEXT'
            Every topic's checklist and common mistakes in one place. Use it as a quick review before you work on a real requirement.
            TEXT],
        ['kind' => 'reference', 'heading' => 'Recruiter Checklist', 'body' => <<<'TEXT'
            **Location**
            - Confirm current city and state on every call.
            - Ask about relocation and commute.
            - Write the true location in submissions.
            - Record the work location for handoffs.
            **LinkedIn Review**
            - Review LinkedIn for every shortlisted candidate.
            - Compare jobs, dates and education.
            - Ask about differences respectfully.
            - Escalate unresolved inconsistencies.
            **Job History**
            - Read job history in order with dates.
            - Ask neutral questions about gaps and overlaps.
            - Record employment types accurately.
            - Never alter dates.
            **Resume vs JD Comparison**
            - Compare every item using the template.
            - Decide clearly based on must-haves.
            - Write an honest, specific summary.
            - Check consent and the latest resume before sending.
            TEXT],
        ['kind' => 'mistakes', 'heading' => 'Common Mistakes', 'body' => <<<'TEXT'
            **Location**
            - Trusting the resume address.
            - Calling relocating candidates local.
            - Not confirming the state for remote roles.
            **LinkedIn Review**
            - Skipping the LinkedIn review.
            - Accusing candidates instead of asking.
            - Submitting candidates with major unexplained differences.
            **Job History**
            - Judging candidates harshly for gaps.
            - Ignoring overlapping full-time roles.
            - Changing dates to improve a resume.
            **Resume vs JD Comparison**
            - Submitting based on a general impression.
            - Hiding a gap in the summary.
            - Sending an old resume.
            TEXT],
        ['kind' => 'takeaway', 'heading' => 'Key Takeaway', 'body' => <<<'TEXT'
            Verify before you submit: confirm location honestly, check LinkedIn and job history for consistency, and submit only when every must-have is met.
            TEXT],
    ],
];
