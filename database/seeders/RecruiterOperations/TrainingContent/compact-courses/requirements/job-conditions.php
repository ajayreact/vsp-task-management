<?php

/*
 * Combined lesson "Location, Work Mode, Duration, Rate & Start Date". One topic per merged lesson:
 * Location, Remote / Hybrid / Onsite, Duration, Rate, Pay / Benefits, Start Date.
 */

return [
    'title' => 'Location, Work Mode, Duration, Rate & Start Date',
    'from' => 'Location',
    'en' => [
        ['kind' => 'objective', 'heading' => 'Learning Objective', 'body' => <<<'TEXT'
            Analyse the job conditions in a requirement (location, work mode, duration, rate, pay and benefits, and start date) and use them to judge whether the requirement is workable for your candidates.
            TEXT],
        ['kind' => 'flow', 'heading' => 'Lesson Map', 'body' => <<<'TEXT'
            1. Location [[Location]]
            2. Remote / Hybrid / Onsite [[Remote / Hybrid / Onsite]]
            3. Duration [[Duration]]
            4. Rate [[Rate]]
            5. Pay / Benefits [[Pay / Benefits]]
            6. Start Date [[Start Date]]
            7. Checklist & Common Mistakes [[Checklist & Common Mistakes]]
            TEXT],
        ['kind' => 'topic', 'heading' => 'Location', 'body' => <<<'TEXT'
            Location decides who can realistically do the role. Analyse it carefully and be honest about relocation.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            The location tells you where the work will be done. It is usually written as city and state, for example Charlotte, NC. It may also name a specific office, campus or ZIP code.
            Location affects which candidates are suitable, whether relocation is needed, which time zone applies, and which state's payroll rules apply.
            TEXT],
        ['kind' => 'content', 'heading' => 'Location phrases you will see', 'body' => <<<'TEXT'
            - Local only means the client wants candidates who already live within commuting distance.
            - Locals preferred means local candidates have an advantage, but others may be considered.
            - Open to relocation means the client will consider candidates who move before the start date.
            - Relocation assistance means the employer may help with moving costs. Confirm the details before mentioning this.
            - Multiple locations means the role could be based in any of several listed cities.
            TEXT],
        ['kind' => 'content', 'heading' => 'From the company training material', 'body' => <<<'TEXT'
            The Job Description Analysis training lists location as the first mandatory detail to capture before sourcing: the city and state, whether the role is onsite, remote or hybrid, and any local requirement. Its two examples show why. The Data Architect role is in McLean, Virginia, day-one onsite. The Java and AWS role in Draper, Utah, is ninety percent remote, but Utah local with a driving licence, so a remote candidate in Texas would not qualify.
            TEXT],
        ['kind' => 'content', 'heading' => 'How to analyse location', 'body' => <<<'TEXT'
            - Confirm the city and state.
            - Identify the time zone.
            - Check the nearest major city, if the location is a suburb.
            - Check the local or relocation rules.
            - Combine this with the work mode, covered in the next tab.
            TEXT],
        ['kind' => 'content', 'heading' => 'Discussing location with candidates', 'body' => <<<'TEXT'
            Confirming a candidate's current location, relocation and commute is taught in **Sourcing & Resume Screening → Profile Verification & Resume vs JD** (Location). The rule that matters at this stage: never tell a vendor a candidate is local when they are not.
            TEXT],
        ['kind' => 'content', 'heading' => 'Location and handoffs', 'body' => <<<'TEXT'
            The work location is essential information for HR and payroll. Record the exact work city and state, not only the client's headquarters.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            The running example is in Charlotte, NC, hybrid. A candidate lives in Raleigh, North Carolina. You know Raleigh is about two and a half to three hours' drive from Charlotte, so a daily commute is not realistic for three office days a week. You ask whether he would relocate to the Charlotte area before the start date. He agrees, so you record that he is relocating, with a planned move date.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Remote / Hybrid / Onsite', 'body' => <<<'TEXT'
            Work mode decides daily life for the consultant. Confirm the details exactly and record where the work will happen.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            - Onsite means the consultant works at the client location every working day.
            - Hybrid means the consultant works some days onsite and some days remotely, for example three days in the office and two from home.
            - Remote means the consultant works from home or another approved location, without regular office attendance.
            TEXT],
        ['kind' => 'content', 'heading' => 'Details to confirm for each mode', 'body' => <<<'TEXT'
            Onsite. The exact address area, office hours, and whether there is any flexibility.
            Hybrid. The number of onsite days, whether those days are fixed, and whether this might change.
            Remote. Whether the consultant must live in a specific state or time zone, whether occasional travel to the office is needed, and the required working hours.
            TEXT],
        ['kind' => 'content', 'heading' => 'From the company training material', 'body' => <<<'TEXT'
            The training's examples show how precise a work mode can be. The Data Architect role is day-one onsite, with three days onsite and two remote. Day-one onsite means the consultant must be in the office from the very first day, so relocation must be complete before the start. The Java and AWS role is ninety percent remote, with possible in-person meetings, and the consultant must be based in Utah.
            TEXT],
        ['kind' => 'content', 'heading' => 'Remote does not mean location-free', 'body' => <<<'TEXT'
            Many remote roles restrict where the consultant may live, for example remote within the U.S. only, or remote, but must be in Eastern or Central Time.
            Some remote roles require travel to the office for the first week or for quarterly meetings.
            The state where a remote consultant works affects payroll and taxes. Always record it.
            TEXT],
        ['kind' => 'content', 'heading' => 'Work mode changes', 'body' => <<<'TEXT'
            Clients sometimes change work modes, for example from remote to hybrid. Ask the vendor how likely changes are, and make sure candidates understand that the client sets the policy.
            TEXT],
        ['kind' => 'content', 'heading' => 'Discussing work mode with candidates', 'body' => <<<'TEXT'
            - Describe the mode exactly as the client defines it.
            - Ask whether the candidate is comfortable with it.
            - For hybrid roles, confirm they can commute or will relocate.
            - For remote roles, confirm their home state and time zone.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            The running example is hybrid, three days onsite in Charlotte. A candidate says she prefers remote work but would accept hybrid if the commute is reasonable. You confirm she lives in Charlotte, record her acceptance of three onsite days, and note her preference in case a remote role comes up later.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Duration', 'body' => <<<'TEXT'
            Duration is an estimate. Explain it honestly and compare it with the candidate's authorization dates.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            Duration is the expected length of a contract, such as three months, six months, twelve months, or long term. It is usually an estimate based on the project, not a promise.
            Duration may be followed by phrases such as with possible extension, likely to extend, or contract-to-hire.
            TEXT],
        ['kind' => 'content', 'heading' => 'From the company training material', 'body' => <<<'TEXT'
            The Job Description Analysis training defines duration as the contract length and possible extension. The US MNC Staffing document also tells recruiters to note the duration of the project and its type, such as full-time, contract or long term, when first studying a requirement.
            TEXT],
        ['kind' => 'content', 'heading' => 'Reading duration phrases', 'body' => <<<'TEXT'
            - Six months plus, or six months with extension, means the client expects at least six months and may extend.
            - Long term usually means more than a year, but this is not guaranteed.
            - Short term, under three months, may suit few OPT candidates, because short roles can create gaps in employment.
            - Contract-to-hire after six months means possible conversion to permanent after that time.
            TEXT],
        ['kind' => 'content', 'heading' => 'Why duration matters for OPT candidates', 'body' => <<<'TEXT'
            OPT and STEM OPT candidates have authorization end dates and unemployment limits. A short contract may end with a gap before the next role. Candidates will want to understand the expected duration. Answer honestly, and refer status questions to their DSO and your HR.
            Compare the duration with the candidate's EAD end date. If a twelve-month contract extends beyond the EAD end date, note this for HR. HR decides how to proceed, for example if STEM OPT applies.
            TEXT],
        ['kind' => 'content', 'heading' => 'Explaining duration', 'body' => <<<'TEXT'
            Use the client's words and add honest context: This is a twelve-month contract. The vendor says extensions are common on this project, but they depend on the client's needs and budget.
            TEXT],
        ['kind' => 'content', 'heading' => 'Early endings', 'body' => <<<'TEXT'
            Contracts may end early. Projects get cancelled, budgets change and clients reorganise. Do not hide this possibility.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            The running example is twelve months with possible extension. A candidate's OPT EAD ends in seven months, and she has a STEM degree. You note that the contract extends beyond her current EAD end date, and you flag this for HR in your handoff. You do not tell her what to file or promise anything about STEM OPT.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Rate', 'body' => <<<'TEXT'
            The rate shows whether a requirement is workable. Use approved pay rates only, and address gaps in expectations early.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            - Most contract requirements include a rate, usually hourly. Read it carefully to understand what it represents.
            - It may be the bill rate offered to your company by the vendor.
            - It may be marked W2, C2C or 1099, which changes how it is used.
            - It may be all-inclusive, meaning no extra payments for expenses.
            - It may be a maximum, often written as up to a certain amount.
            TEXT],
        ['kind' => 'content', 'heading' => 'From the company training material', 'body' => <<<'TEXT'
            The Job Description Analysis training lists rate as a mandatory detail: the pay or rate details and the conditions attached to them. Its Java and AWS example uses a client scoring framework in which rate counts for twenty percent of the candidate's score. So rate is not only a business detail. It directly affects whether a candidate is selected.
            TEXT],
        ['kind' => 'content', 'heading' => 'Using the rate in your analysis', 'body' => <<<'TEXT'
            - Compare the rate with the skills and experience required. A senior role with a very low rate may be hard to fill.
            - Ask your lead what pay rate your company can offer consultants for this requirement.
            - Compare the approved pay rate with typical candidate expectations for the skill set and location.
            - Note any requirement where the rate makes a match unlikely, and discuss it with your lead before spending a lot of time.
            TEXT],
        ['kind' => 'content', 'heading' => 'Location and rate', 'body' => <<<'TEXT'
            Rates and salaries vary across the U.S. Roles in high-cost areas, such as the San Francisco Bay Area or New York City, often pay more than similar roles elsewhere. Candidates may expect more when relocating to expensive cities.
            TEXT],
        ['kind' => 'content', 'heading' => 'Discussing rate with candidates', 'body' => <<<'TEXT'
            Follow the rate rules in **U.S. IT Staffing & Payroll Fundamentals → Employment Types, W2/C2C & Rates** (Rate Structures): ask for expectations first, share only the approved pay rate, and never share the bill rate unless your company allows it.
            - Make clear whether the rate is hourly W2 or another arrangement.
            - If a candidate's expectation is above the approved range, say so honestly. Do not promise that you can negotiate more unless your lead agrees.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            The running example's vendor header gives a bill rate. Your lead confirms the W2 pay rate range for consultants. A candidate expects a rate above the range. You say: The approved range for this role is below your expectation. Would you like to be considered at the top of this range, or should I look for roles closer to your expectation? You record his decision.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Pay / Benefits', 'body' => <<<'TEXT'
            Know what the numbers in a requirement mean. Share approved pay information early, accurately and honestly.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            U.S. requirements may show pay as an hourly rate, an annual salary range, or not at all. Some U.S. states and cities have pay transparency laws that require salary ranges in job postings, so you will see ranges more often than in the past.
            In staffing requirements, the rate shown by a vendor is usually the bill rate in the chain, not the consultant's pay rate. U.S. IT Staffing & Payroll Fundamentals → Employment Types, W2/C2C & Rates explains bill rate and pay rate.
            TEXT],
        ['kind' => 'content', 'heading' => 'From the company training material', 'body' => <<<'TEXT'
            The Job Description Analysis training says to capture the rate or salary and any stated benefits or conditions. Conditions can be easy to miss. The Java and AWS example says equipment is provided by the agency, and it includes a video interview and a drug test. These are part of the pay and working conditions a candidate needs to know.
            TEXT],
        ['kind' => 'content', 'heading' => 'Common pay formats', 'body' => <<<'TEXT'
            - An hourly rate, for example per hour on W2.
            - A salary range, for example a minimum and maximum annual salary.
            - An all-inclusive rate, which includes any expenses.
            - A rate described by engagement type, such as W2 rate or C2C rate.
            TEXT],
        ['kind' => 'content', 'heading' => 'Benefits', 'body' => <<<'TEXT'
            Benefits may include health, dental and vision insurance, paid time off, holidays, retirement plans, and training support. For contract roles, benefits depend on the employer, which is often the staffing company. For direct hire roles, the client offers benefits.
            Never describe benefits that you have not confirmed with HR.
            TEXT],
        ['kind' => 'content', 'heading' => 'Discussing pay with candidates', 'body' => <<<'TEXT'
            - Ask for the candidate's expected pay early in the conversation, and whether they are thinking hourly or annual, W2 or other.
            - Share only the pay rate or range approved by your team.
            - Be clear that rates are gross amounts before taxes.
            - If expectations are far apart, say so politely and early, rather than wasting everyone's time.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            The running example's vendor header shows a bill rate. Your lead tells you the approved W2 pay range for consultants. When you speak to a candidate, you ask for her expected W2 hourly rate. Her expectation is within the approved range, so you note it and continue screening. You do not mention the bill rate.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Start Date', 'body' => <<<'TEXT'
            The start date is a target. Check every factor that affects it, and never promise a start before the candidate is authorized.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            The start date is when the client wants the consultant to begin. It may be exact, such as June 3, or general, such as immediate, within two weeks, or ASAP.
            Clients often move start dates, and onboarding steps such as background checks can take time. Treat the start date as a target, and plan for the steps needed to reach it.
            TEXT],
        ['kind' => 'content', 'heading' => 'From the company training material', 'body' => <<<'TEXT'
            The Job Description Analysis training calls this detail dates: the expected start date and end date. Capture both. The end date, together with the duration, tells you whether the assignment will run past the candidate's EAD end date.
            TEXT],
        ['kind' => 'content', 'heading' => 'What can affect a candidate\'s start date', 'body' => <<<'TEXT'
            - Work authorization. The candidate's EAD must be valid on the start date. A candidate whose EAD starts after the client's date cannot begin earlier.
            - Notice period. A candidate who is currently working may need to give notice to their current employer, often two weeks in the U.S.
            - Relocation. Moving to a new city takes time.
            - Onboarding. Background checks, drug tests if required, documentation and equipment setup take time.
            - Personal plans. Travel, graduation ceremonies or family commitments.
            TEXT],
        ['kind' => 'content', 'heading' => 'Questions to ask candidates', 'body' => <<<'TEXT'
            - When is the earliest date you could start?
            - Do you need to give notice to a current employer?
            - If relocation is needed, when could you move?
            - Is your work authorization valid from that date? Use your company's approved wording.
            TEXT],
        ['kind' => 'content', 'heading' => 'Using start dates in prioritisation', 'body' => <<<'TEXT'
            Urgent start dates mean the client may move quickly. Candidates available immediately are valuable for these requirements.
            A candidate who cannot meet the start date may still be worth discussing with the vendor if they are a strong match. Be honest about their availability.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            The running example needs someone within two weeks. A strong candidate's EAD starts in three weeks. You tell the vendor honestly: Strong match, available to start in three weeks, on the EAD start date. The vendor checks with the client, who agrees to wait. You record the confirmed start date.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Checklist & Common Mistakes', 'body' => <<<'TEXT'
            Every topic's checklist and common mistakes in one place. Use it as a quick review before you work on a real requirement.
            TEXT],
        ['kind' => 'reference', 'heading' => 'Recruiter Checklist', 'body' => <<<'TEXT'
            **Location**
            - Confirm city, state, time zone and local rules.
            - Ask candidates for their current city and relocation willingness.
            - Record the exact work location for handoffs.
            **Remote / Hybrid / Onsite**
            - Confirm the exact work mode and number of onsite days.
            - For remote roles, confirm location restrictions and travel.
            - Record the consultant's working state.
            **Duration**
            - Record the duration and any extension wording.
            - Compare duration with work authorization end dates.
            - Explain duration honestly.
            **Rate**
            - Identify what kind of rate the requirement shows.
            - Get the approved pay rate from your lead.
            - Compare rate with skills, experience and location.
            - Be honest when expectations do not match.
            **Pay / Benefits**
            - Identify whether the requirement shows bill rate, pay rate or salary.
            - Use only approved pay figures with candidates.
            - Confirm benefits with HR before discussing them.
            **Start Date**
            - Record the client's start date and how flexible it is.
            - Check the candidate's earliest realistic start date.
            - Compare it with the EAD start date.
            - Be honest with the vendor about availability.
            TEXT],
        ['kind' => 'mistakes', 'heading' => 'Common Mistakes', 'body' => <<<'TEXT'
            **Location**
            - Assuming a candidate in the same state is local.
            - Describing a relocating candidate as local.
            - Recording the client's headquarters instead of the actual work site.
            **Remote / Hybrid / Onsite**
            - Telling a candidate a hybrid role is mostly remote.
            - Assuming remote roles have no state restrictions.
            - Not recording the working state for remote consultants.
            **Duration**
            - Presenting an estimated duration as a guarantee.
            - Ignoring a gap between the contract length and the EAD end date.
            - Promising extensions.
            **Rate**
            - Quoting the vendor's rate as the candidate's pay.
            - Agreeing to a candidate's rate without approval.
            - Ignoring an unworkable rate until after submission.
            **Pay / Benefits**
            - Quoting the bill rate to a candidate as their pay.
            - Promising benefits without confirmation.
            - Avoiding the pay discussion until the end.
            **Start Date**
            - Promising a start date before the EAD is valid.
            - Forgetting notice periods or relocation time.
            - Not telling the vendor about availability limits.
            TEXT],
        ['kind' => 'takeaway', 'heading' => 'Key Takeaway', 'body' => <<<'TEXT'
            Check location, work mode, duration, rate and start date before you source, and raise any condition that makes a match unlikely with your lead early.
            TEXT],
    ],
];
