<?php

/*
 * Combined lesson "U.S. Visa & Immigration Statuses". One topic per merged lesson:
 * F-1, F-2, CPT, OPT, STEM OPT, H-1B, H-4, H-4 EAD, EAD, Green Card, Other Visa & Status Overview.
 */

return [
    'title' => 'U.S. Visa & Immigration Statuses',
    'from' => 'F-1',
    'compliance' => true,
    'review' => 'Immigration content: confirm against current official sources (USCIS, SEVP, DHS Study in the States) and company policy before publishing.',
    'en' => [
        ['kind' => 'objective', 'heading' => 'Learning Objective', 'body' => <<<'TEXT'
            Recognise every visa and immigration status you will meet as a recruiter, from F-1, CPT, OPT and STEM OPT to H-1B, H-4, EAD and the Green Card, and know which ones allow work.
            TEXT],
        ['kind' => 'flow', 'heading' => 'Lesson Map', 'body' => <<<'TEXT'
            1. F-1 [[F-1]]
            2. F-2 [[F-2]]
            3. CPT [[CPT]]
            4. OPT [[OPT]]
            5. STEM OPT [[STEM OPT]]
            6. H-1B [[H-1B]]
            7. H-4 [[H-4]]
            8. H-4 EAD [[H-4 EAD]]
            9. EAD [[EAD]]
            10. Green Card [[Green Card]]
            11. Other Visa & Status Overview [[Other Visa & Status Overview]]
            TEXT],
        ['kind' => 'topic', 'heading' => 'F-1', 'body' => <<<'TEXT'
            F-1 is the student status behind most OPT candidates. Understand the timeline, ask clear questions, and refer status questions to the DSO.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            F-1 is a U.S. nonimmigrant status for international students studying full time at a school approved by the Student and Exchange Visitor Program, known as SEVP. Most OPT candidates you speak with are, or recently were, F-1 students.
            An F-1 student receives a Form I-20 from their school. The I-20 shows the school, the program, and the program start and end dates.
            Each school has Designated School Officials, called DSOs, who manage the student's record in a government database called SEVIS.
            F-1 students must maintain their status. This generally includes staying enrolled full time and following the rules about work.
            TEXT],
        ['kind' => 'content', 'heading' => 'Work while on F-1', 'body' => <<<'TEXT'
            F-1 status allows only limited work. Main types you will hear about are on-campus employment, Curricular Practical Training, known as CPT, and Optional Practical Training, known as OPT.
            Off-campus work without the right authorization can cause serious problems for the student. This is why recruiters never encourage anyone to start work before their authorization is confirmed.
            TEXT],
        ['kind' => 'content', 'heading' => 'Key Concepts', 'body' => <<<'TEXT'
            A visa is the stamp in the passport used to enter the U.S. Status is the category a person holds while inside the U.S. A visa can expire while the person still holds valid status.
            Duration of status, often written as D/S on the I-94 record, means the student may stay as long as they maintain their F-1 status and program.
            After finishing the program, a student generally has a short grace period to leave, change status, transfer, or begin approved OPT. Verify the current rules.
            TEXT],
        ['kind' => 'content', 'heading' => 'From the company training material', 'body' => <<<'TEXT'
            The OPT Recruiter Training Material describes the F-1 visa as the student visa given to students from all over the world who want to pursue a bachelor's or master's degree in the U.S., and notes that F-1 students are generally not eligible to work in the U.S. without separate authorization. The US Visa Types presentation adds that F-1 allows full-time study at an academic institution such as a university, private school or language institute.
            Correction: the handout says the validity of the F-1 visa is five years. That is not a fixed rule. The visa stamp's validity varies by country and case, and the student's permission to stay depends on maintaining status for the length of the program, shown as duration of status. Do not quote a fixed number of years.
            TEXT],
        ['kind' => 'content', 'heading' => 'F-2, the dependent of F-1', 'body' => <<<'TEXT'
            Both company documents describe F-2 as the dependent visa of an F-1 student, for the spouse and children. F-2 dependents are not permitted to work in the U.S. If a candidate tells you they are on F-2, record it accurately and follow company policy. Do not suggest any way around the rule.
            TEXT],
        ['kind' => 'content', 'heading' => 'Why this matters for an OPT recruiter', 'body' => <<<'TEXT'
            Your candidates are often F-1 students preparing for OPT, or graduates already on OPT or STEM OPT. Knowing how F-1 works helps you ask the right questions and understand the timeline: study, graduation, OPT application, EAD card, then work.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            A candidate says: I am an F-1 student and I graduate in May. My OPT application is pending. You note that she does not yet have an EAD card, so she cannot start work yet. You record her expected graduation date and ask her to share an update when her EAD is approved. You do not promise any start date.
            TEXT],
        ['kind' => 'reference', 'heading' => 'Recruiter Checklist', 'body' => <<<'TEXT'
            - Ask whether the candidate is currently studying or has graduated.
            - Note the program end date.
            - Ask whether OPT has been applied for, approved or is already active.
            - Never advise a candidate on maintaining status. Direct them to their DSO.
            TEXT],
        ['kind' => 'mistakes', 'heading' => 'Common Mistakes', 'body' => <<<'TEXT'
            - Treating visa and status as the same thing.
            - Assuming a student can work full time off campus without authorization.
            - Giving immigration advice instead of referring to the DSO or an immigration attorney.
            TEXT],
        ['kind' => 'topic', 'heading' => 'F-2', 'body' => <<<'TEXT'
            F-2 does not allow work. Be kind, do not advise, and refer the person to their DSO or an attorney.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            - F-2 is the dependent status for the spouse and unmarried children under 21 of an F-1 student.
            - An F-2 dependent's status depends on the F-1 student keeping their own status.
            - F-2 dependents are not allowed to work in the U.S. There is no F-2 work permit.
            - An F-2 spouse may not study full time. Children may attend school from kindergarten to grade 12.
            - To work, a person must first move to a status that allows employment. That is a decision for the person, their DSO and an immigration attorney, never for a recruiter.
            TEXT],
        ['kind' => 'reference', 'heading' => 'Quick Reference', 'body' => <<<'TEXT'
            | Question | F-1 student | F-2 dependent |
            | --- | --- | --- |
            | Who holds it? | The student | Spouse or child of the student |
            | Can work? | Only with CPT, OPT or STEM OPT | No |
            | Full-time study? | Yes | Spouse no, children K-12 yes |
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            A caller says her husband is on F-1 and she is on F-2, and asks whether you can place her. You say: Thank you for telling me. Our process can only move forward with candidates who are authorized to work. Your DSO or an immigration attorney can explain your options. You record the call and do not submit her profile.
            TEXT],
        ['kind' => 'mistakes', 'heading' => 'Common Mistakes', 'body' => <<<'TEXT'
            - Treating F-2 like F-1 and asking about OPT dates.
            - Suggesting a way to work, such as a change of status, yourself.
            - Adding an F-2 dependent to a submission pipeline.
            TEXT],
        ['kind' => 'topic', 'heading' => 'CPT', 'body' => <<<'TEXT'
            CPT is school-authorized, curriculum-based and employer-specific. Record the facts accurately and leave eligibility questions to the DSO.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            Curricular Practical Training, known as CPT, is a type of work authorization for F-1 students. It allows work experience that is an integral part of the student's study program, such as a required internship, practicum or cooperative education course.
            CPT is authorized by the school's Designated School Official, not by U.S. Citizenship and Immigration Services. The authorization appears on the student's Form I-20.
            CPT is usually tied to a specific employer, a specific location and specific dates. The student generally cannot simply move to a different employer without a new CPT authorization.
            CPT does not require an EAD card.
            TEXT],
        ['kind' => 'content', 'heading' => 'CPT versus OPT', 'body' => <<<'TEXT'
            - CPT happens during the study program and must be part of the curriculum.
            - OPT is usually used after the program ends, although pre-completion OPT also exists.
            - CPT is authorized by the school. OPT requires approval from U.S. Citizenship and Immigration Services and an EAD card.
            - CPT is employer-specific. Post-completion OPT is not tied to one employer, but the work must relate to the field of study.
            TEXT],
        ['kind' => 'content', 'heading' => 'An important rule to be aware of', 'body' => <<<'TEXT'
            Under long-standing rules, a student who uses twelve months or more of full-time CPT is generally no longer eligible for OPT at that degree level. Part-time CPT does not have this effect. Candidates should confirm their own situation with their DSO.
            TEXT],
        ['kind' => 'content', 'heading' => 'From the company training material', 'body' => <<<'TEXT'
            The OPT Recruiter Training Material describes CPT as a temporary authorization for F-1 students. It also says that CPT students can work in the university area only, and that CPT validity is nine months. Treat those two statements with care. CPT can be authorized for work with an off-campus employer when the work is part of the curriculum, and there is no single nine-month validity. The authorized dates are whatever the school's DSO records on the student's I-20. Always read the actual dates and employer on the I-20, and confirm with HR.
            The calling script's screening questionnaire includes F-1 CPT as one of the visa options a recruiter records. Record it exactly as the candidate states it.
            TEXT],
        ['kind' => 'content', 'heading' => 'Day 1 CPT', 'body' => <<<'TEXT'
            Some schools offer CPT from the start of a program. This is sometimes called Day 1 CPT. These programs can come with additional questions and scrutiny. Recruiters should not judge or advise on these programs. Record the facts and follow company policy about CPT candidates.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            A candidate says: I am on CPT with another company until August. You note that his CPT is tied to that employer. Working for a new employer would generally need a new CPT authorization from his school. You record his CPT end date and his expected OPT plans, and you follow your company's process for CPT candidates.
            TEXT],
        ['kind' => 'reference', 'heading' => 'Recruiter Checklist', 'body' => <<<'TEXT'
            - Ask whether the candidate is on CPT, OPT or another status.
            - For CPT, note the employer listed and the authorized dates.
            - Follow your company's policy on whether CPT candidates can be considered.
            - Refer questions about CPT eligibility to the candidate's DSO.
            TEXT],
        ['kind' => 'mistakes', 'heading' => 'Common Mistakes', 'body' => <<<'TEXT'
            - Treating CPT and OPT as the same.
            - Assuming CPT allows work for any employer.
            - Promising that a candidate's school will approve CPT.
            TEXT],
        ['kind' => 'topic', 'heading' => 'OPT', 'body' => <<<'TEXT'
            OPT is the foundation of your work. Track the dates carefully, respect the rules, and refer status questions to the DSO and HR.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            - OPT is temporary employment authorization for F-1 students.
            - The work must be directly related to the student's major field of study.
            - Under current rules, a student can generally receive up to 12 months of OPT per higher degree level.
            - Pre-completion OPT is used before the program ends. Post-completion OPT is used after it ends. Most of your candidates use post-completion OPT.
            TEXT],
        ['kind' => 'content', 'heading' => 'How a Student Gets OPT', 'body' => <<<'TEXT'
            1. The DSO recommends OPT in SEVIS and issues an updated I-20.
            2. The student files Form I-765 with USCIS.
            3. If approved, the student receives an EAD card.
            4. The student may start work only on or after the EAD start date, and only with the approved card.
            TEXT],
        ['kind' => 'reference', 'heading' => 'Key Dates to Track', 'body' => <<<'TEXT'
            | Date | Where it comes from |
            | --- | --- |
            | Program end date | I-20 |
            | EAD start date | EAD card |
            | EAD end date | EAD card |
            | Employment start date | The new employer |
            TEXT],
        ['kind' => 'content', 'heading' => 'Reading the Company Handout Correctly', 'body' => <<<'TEXT'
            The OPT Recruiter Training Material says OPT is valid for 12 months and can be extended by up to 24 months, for a total of 36 months, and that MBA students have OPT for 12 months only.
            - The 24-month extension is STEM OPT. Only graduates of qualifying STEM degree programs can apply for it.
            - So 36 months is the maximum for a STEM graduate, not the normal case.
            - Most MBA programs are not STEM programs, but some are STEM designated. Do not assume either way.
            TEXT],
        ['kind' => 'content', 'heading' => 'Unemployment and Reporting', 'body' => <<<'TEXT'
            - During post-completion OPT, students are generally allowed a limited number of unemployment days, commonly cited as 90 days.
            - A candidate close to that limit may be under pressure. Be aware of it, but never advise on it.
            - Reporting employment details to the school or the SEVP portal is the candidate's responsibility, guided by the DSO.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            A candidate shares that her EAD is valid from June 1 to May 31 next year. She graduated in December. You record both dates, note that she can start work from June 1, and tell HR that her EAD ends next May, so they can plan for STEM OPT if she is eligible.
            TEXT],
        ['kind' => 'mistakes', 'heading' => 'Common Mistakes', 'body' => <<<'TEXT'
            - Assuming an approved application means the candidate can start immediately.
            - Calculating unemployment days for the candidate.
            - Ignoring the EAD end date during placement.
            - Deciding yourself whether a job relates to the degree, instead of escalating as company policy requires.
            TEXT],
        ['kind' => 'practice', 'heading' => 'Check Your Knowledge', 'body' => <<<'TEXT'
            1. Which document gives you the EAD start and end dates?
            2. A candidate's EAD starts on July 15, and the client wants a July 1 start. What do you tell the client?
            3. Is 36 months of OPT normal for every candidate? Why or why not?
            4. A candidate asks you how many unemployment days she has left. What do you do?
            TEXT],
        ['kind' => 'topic', 'heading' => 'STEM OPT', 'body' => <<<'TEXT'
            STEM OPT can extend a candidate's work authorization, but it brings employer obligations. Track dates, hand off to HR, and never promise outcomes.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            STEM stands for Science, Technology, Engineering and Mathematics. Under current rules, F-1 students with a degree in a field on the Department of Homeland Security STEM Designated Degree Program List may apply for a twenty-four month extension of their OPT. This is called STEM OPT.
            The student applies by filing Form I-765 again, before their current OPT EAD expires.
            If the application is filed on time, current rules generally allow work to continue for a limited period while the application is pending. Verify the current rule and dates with HR.
            TEXT],
        ['kind' => 'content', 'heading' => 'Employer requirements', 'body' => <<<'TEXT'
            - STEM OPT has requirements that ordinary OPT does not.
            - The employer must be enrolled in E-Verify and in good standing.
            - The employer and the student must complete Form I-983, the Training Plan for STEM OPT Students.
            - The role must be a paid position related to the student's STEM degree, and the employer must have a genuine employer-employee relationship with the student.
            - The training plan describes learning goals, supervision and evaluation.
            - Changes to employment, such as a new employer, may require a new I-983 and reporting to the DSO.
            TEXT],
        ['kind' => 'content', 'heading' => 'Reporting and evaluations', 'body' => <<<'TEXT'
            Students on STEM OPT have regular reporting duties, including validation reports and self-evaluations on the I-983. Employers must report certain changes, such as the student leaving the job. These are handled by the student, the DSO, the employer and HR. Recruiters should understand that they exist.
            TEXT],
        ['kind' => 'content', 'heading' => 'Unemployment awareness', 'body' => <<<'TEXT'
            STEM OPT adds a limited number of extra unemployment days to the OPT limit. The total is commonly cited as one hundred fifty days across OPT and STEM OPT. Verify with official sources. Never calculate or advise on this for a candidate.
            TEXT],
        ['kind' => 'content', 'heading' => 'From the company training material', 'body' => <<<'TEXT'
            The calling script's screening questionnaire asks recruiters to record whether the candidate is on F-1 initial OPT or F-1 STEM OPT, the EAD start and end dates, the remaining unemployment days out of ninety or one hundred fifty, and whether the candidate's current employer is E-Verified. Record what the candidate tells you. Do not calculate unemployment days yourself.
            The calling script also contains the line that the company is completely E-Verified and has a legal team that handles STEM OPT extensions and H-1B sponsorship without any hiccups. Company-specific process: do not use this statement unless HR or authorized personnel confirm it is accurate today. Never describe any immigration process as having no risk.
            TEXT],
        ['kind' => 'content', 'heading' => 'Why this matters for recruiters', 'body' => <<<'TEXT'
            Candidates often ask whether your company supports STEM OPT. Answer only with information your company has approved. Do not promise STEM OPT support, an I-983, or approval.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            A candidate says his OPT EAD expires in four months and his degree is in data science. He asks whether your company will sign his I-983. You respond: Our HR team handles STEM OPT documentation. I will share your details with them, and they will confirm the process. You then hand off his EAD end date and degree details to HR.
            TEXT],
        ['kind' => 'reference', 'heading' => 'Recruiter Checklist', 'body' => <<<'TEXT'
            - Note the candidate's degree, major and EAD end date.
            - Ask whether they intend to apply for STEM OPT.
            - Hand off STEM OPT questions to HR or compliance.
            - Do not promise I-983 signatures or approvals.
            TEXT],
        ['kind' => 'mistakes', 'heading' => 'Common Mistakes', 'body' => <<<'TEXT'
            - Assuming every IT degree is on the STEM list.
            - Promising that an employer is eligible without checking.
            - Missing the EAD end date, which can leave a consultant unable to work.
            TEXT],
        ['kind' => 'topic', 'heading' => 'H-1B', 'body' => <<<'TEXT'
            H-1B is employer-sponsored and limited. Share only approved information, never guarantee outcomes, and refer details to HR.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            H-1B is a temporary U.S. work classification for specialty occupations, which are roles that generally require at least a bachelor's degree or equivalent in a specific field. Many IT roles fall into this category.
            H-1B is employer-sponsored. The employer files a petition with U.S. Citizenship and Immigration Services. The worker cannot apply alone.
            There is an annual limit, called the cap, on new H-1B workers for most employers. Because demand is high, there is a registration and selection process, commonly called the lottery. Selection is not guaranteed.
            Under the current process, registration generally happens in spring, and approved cap petitions usually take effect from October 1. Verify the current calendar each year.
            TEXT],
        ['kind' => 'content', 'heading' => 'H-1B and OPT candidates', 'body' => <<<'TEXT'
            Many OPT candidates hope to move to H-1B status in the future. This is why they often ask whether your company sponsors H-1B.
            There is a provision often called cap-gap, which may extend F-1 status and work authorization for some students between the end of OPT and the start of an H-1B. Whether it applies depends on the details. HR and immigration counsel decide this.
            TEXT],
        ['kind' => 'content', 'heading' => 'From the company training material', 'body' => <<<'TEXT'
            The OPT Recruiter Training Material describes H-1B as an employer-sponsored, nonimmigrant visa that allows foreign workers to work temporarily in the U.S. in a specific occupation, valid for three years and extendable by three years, for six years in total.
            The US Visa Types presentation gives more detail that recruiters should understand.
            H-1B is for a specialty occupation, which requires specialized knowledge and at least a bachelor's degree or its equivalent.
            The employer must offer a job and file a petition, Form I-129, with U.S. immigration. The approved petition allows the person to work for that employer.
            A person may work for more than one employer, but each employer must have its own approved petition.
            The employer may place the worker at another company's work site, such as a client site, under the rules that apply.
            H-1B can be transferred to a new employer. The new employer must file a new petition.
            The spouse and unmarried children under twenty-one may hold H-4 status for the same period.
            An H-1B holder may seek permanent residence, the Green Card.
            One statement in the presentation needs care. It says an H-1B worker can be inactive without affecting status as long as the employer relationship exists. Current rules include wage and benching obligations for employers, so never interpret a consultant's H-1B status yourself. Refer every such question to HR or immigration counsel.
            TEXT],
        ['kind' => 'content', 'heading' => 'What recruiters may and may not say', 'body' => <<<'TEXT'
            - You may share your company's approved, factual position on H-1B sponsorship, if HR has given you one.
            - You must not guarantee that anyone will be selected or approved.
            - You must not promise sponsorship unless your company has formally authorized that statement for that candidate.
            - You must not give opinions on a candidate's chances.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            A candidate asks: If I join, will you file my H-1B next year? You answer: H-1B sponsorship decisions are made by our management and HR team, and selection in the H-1B process is never guaranteed. I can share your interest with them, and they will discuss it with you. You then record the question in your notes.
            TEXT],
        ['kind' => 'reference', 'heading' => 'Recruiter Checklist', 'body' => <<<'TEXT'
            - Know your company's approved statement on H-1B sponsorship.
            - Never use the word guarantee about H-1B.
            - Refer specific H-1B questions to HR or immigration counsel.
            - Record candidate questions accurately for the handoff.
            TEXT],
        ['kind' => 'mistakes', 'heading' => 'Common Mistakes', 'body' => <<<'TEXT'
            - Promising H-1B sponsorship to close a candidate.
            - Saying selection is likely or easy.
            - Giving dates or fees from memory without checking current information.
            TEXT],
        ['kind' => 'topic', 'heading' => 'H-4', 'body' => <<<'TEXT'
            H-4 is a dependent status that does not allow work by itself. Confirm whether an H-4 EAD exists, and keep the conversation professional.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            H-4 is a dependent status for the spouse and unmarried children under twenty-one of an H-1B worker. The H-4 holder's status depends on the H-1B worker's status.
            H-4 status by itself does not allow employment in the U.S.
            Certain H-4 spouses may apply for an H-4 EAD, which does allow work. The next lesson explains this.
            H-4 holders can study in the U.S.
            TEXT],
        ['kind' => 'content', 'heading' => 'From the company training material', 'body' => <<<'TEXT'
            The OPT Recruiter Training Material describes H-4 as the dependent visa of H-1B and states that H-4 holders are not eligible to work in the U.S. The US Visa Types presentation lists H-1B and H-4 together as the temporary worker and the dependent who cannot work, and explains that the spouse and unmarried children under twenty-one of an H-1B professional may stay under H-4 for the same period as the H-1B. The one important exception, the H-4 EAD for certain spouses, is explained in the next lesson.
            TEXT],
        ['kind' => 'content', 'heading' => 'How H-4 connects to OPT recruiting', 'body' => <<<'TEXT'
            Some candidates you meet were F-1 students who later married an H-1B worker. Some may have changed from F-1 to H-4. Others may hold H-4 and also have an H-4 EAD.
            A candidate's status can change over time, so always ask about current status rather than assuming from the resume or past conversations.
            TEXT],
        ['kind' => 'content', 'heading' => 'Questions recruiters can ask', 'body' => <<<'TEXT'
            - Ask clearly and respectfully: What is your current work authorization, and does it have an end date?
            - If a candidate says H-4, ask: Do you currently hold an EAD that allows you to work?
            - Follow your company's approved questions. Do not ask about the spouse's personal details beyond what HR requires.
            TEXT],
        ['kind' => 'content', 'heading' => 'Sensitivity', 'body' => <<<'TEXT'
            Questions about family and marital status can be sensitive. Keep the focus on work authorization, which is relevant to the job, and avoid personal questions that are not required.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            A candidate says: I was on OPT, but now I am on H-4 through my husband. You ask whether she holds an H-4 EAD. She says her application is pending. You note that she does not currently have work authorization, record the pending status, and agree to follow up when she receives a decision. You do not suggest any start date.
            TEXT],
        ['kind' => 'reference', 'heading' => 'Recruiter Checklist', 'body' => <<<'TEXT'
            - Ask about current work authorization, not past status.
            - For H-4, confirm whether an H-4 EAD is held and note its dates.
            - Avoid unnecessary personal questions.
            - Escalate unclear situations to HR.
            TEXT],
        ['kind' => 'mistakes', 'heading' => 'Common Mistakes', 'body' => <<<'TEXT'
            - Assuming every H-4 holder can work.
            - Assuming a former OPT candidate is still on OPT.
            - Asking intrusive questions about the spouse.
            TEXT],
        ['kind' => 'topic', 'heading' => 'H-4 EAD', 'body' => <<<'TEXT'
            An H-4 EAD can allow broad work authorization, but it has an end date and renewal rules. Record accurately and let HR confirm details.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            An H-4 EAD is an Employment Authorization Document issued to certain H-4 spouses of H-1B workers. Under current rules, eligibility is generally linked to the H-1B worker having reached certain steps toward permanent residence, such as an approved immigrant petition. HR and immigration counsel decide eligibility. Recruiters do not.
            An H-4 EAD generally allows the holder to work for any employer, in any role, while the card is valid and the H-4 status is maintained.
            An H-4 EAD is not tied to a field of study like OPT.
            TEXT],
        ['kind' => 'content', 'heading' => 'What recruiters record', 'body' => <<<'TEXT'
            - The current status, H-4.
            - That an H-4 EAD is held.
            - The EAD start and end dates.
            - Whether an extension or renewal is pending.
            TEXT],
        ['kind' => 'content', 'heading' => 'Renewals and gaps', 'body' => <<<'TEXT'
            H-4 EADs must be renewed. Processing times vary. A renewal pending past the card end date can create gaps in authorization unless an automatic extension applies. Whether an automatic extension applies depends on the current rules and the details. HR must confirm.
            TEXT],
        ['kind' => 'content', 'heading' => 'H-4 EAD compared with OPT EAD', 'body' => <<<'TEXT'
            - Both are EAD cards, but they come from different categories with different rules.
            - OPT requires work related to the field of study. H-4 EAD generally does not.
            - STEM OPT requires an E-Verify employer and an I-983. H-4 EAD does not.
            - The category code on the card tells HR which type it is.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            A candidate shares an EAD with a category code that HR identifies as H-4 EAD. The end date is in two months. You note this in your handoff and highlight the end date, so HR can confirm whether a renewal has been filed before the consultant is placed.
            TEXT],
        ['kind' => 'reference', 'heading' => 'Recruiter Checklist', 'body' => <<<'TEXT'
            - Record the EAD type as confirmed by HR.
            - Note the start and end dates exactly.
            - Ask whether a renewal has been filed when the end date is near.
            - Let HR decide on any automatic extension.
            TEXT],
        ['kind' => 'mistakes', 'heading' => 'Common Mistakes', 'body' => <<<'TEXT'
            - Treating an H-4 EAD and an OPT EAD as the same.
            - Assuming a renewal is approved because it was filed.
            - Skipping the end date check during placement.
            TEXT],
        ['kind' => 'note', 'heading' => 'Source Note', 'body' => <<<'TEXT'
            The OPT Recruiter Training Material says that H-4 dependants are not eligible to work. That is true for most H-4 holders, but since 2015 certain H-4 spouses of H-1B workers can apply for an EAD, which this lesson explains. The company documents do not cover the H-4 EAD itself, so this lesson is based on published government guidance and needs review and approval by HR and the training manager.
            TEXT],
        ['kind' => 'topic', 'heading' => 'EAD', 'body' => <<<'TEXT'
            The EAD is the key work authorization document for OPT candidates. Read it carefully, record the dates exactly, and handle it securely.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            An Employment Authorization Document, known as an EAD, is a card issued by U.S. Citizenship and Immigration Services. It is also called Form I-766. It proves that a person is authorized to work in the U.S. for a specific period.
            OPT and STEM OPT candidates receive an EAD. Some other categories, such as certain H-4 spouses, also receive EADs.
            TEXT],
        ['kind' => 'content', 'heading' => 'Information on the card', 'body' => <<<'TEXT'
            - The person's name and photograph.
            - A category code that shows the type of authorization. For example, post-completion OPT and STEM OPT have their own codes. HR will interpret these codes.
            - A card valid from date, which is the start date of authorization.
            - A card expires date, which is the end date of authorization.
            - A USCIS number and card number.
            TEXT],
        ['kind' => 'content', 'heading' => 'What recruiters focus on', 'body' => <<<'TEXT'
            - The name should match the resume and other records.
            - The start date tells you the earliest date the candidate can begin work.
            - The end date tells you how long the current authorization lasts and when STEM OPT or another plan may be needed.
            - The category tells HR what type of authorization it is.
            TEXT],
        ['kind' => 'content', 'heading' => 'Handling EAD information', 'body' => <<<'TEXT'
            - EAD copies contain personal information. Follow your company's rules on how to request, store and share them.
            - Collect only what your company process requires, and only through approved channels.
            - Never post or forward EAD images in unofficial chat groups.
            - Whether and when an EAD copy is requested is a company-specific process. Some requests are only appropriate at particular stages. Confirm with HR or compliance.
            TEXT],
        ['kind' => 'content', 'heading' => 'From the company training material', 'body' => <<<'TEXT'
            The OPT Recruiter Training Material describes the EAD as the Employment Authorization Document, a card given to a person so they can work in the U.S. It notes that L-2 dependents can work if they have an EAD card. The US Visa Types presentation adds an important principle: U.S. employers must check that all employees, regardless of citizenship or national origin, are allowed to work in the U.S. A person who is not a U.S. citizen or lawful permanent resident may need an EAD to prove that eligibility.
            For recruiters, this means two things. Every candidate is treated the same way in the verification process, and the EAD dates must be recorded exactly. The calling script also asks for the EAD card start date and end date during every screening call.
            TEXT],
        ['kind' => 'content', 'heading' => 'Pending EAD', 'body' => <<<'TEXT'
            A candidate may have filed for OPT but not yet received the card. A receipt notice shows that the application was filed, but for initial OPT it generally does not allow work by itself. Record the status as pending and follow up.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            A candidate sends an EAD copy that shows a start date three weeks from now. The client wants someone to start next week. You do not suggest an early start. You inform the vendor or client of the real availability date and note the EAD dates in your handoff.
            TEXT],
        ['kind' => 'reference', 'heading' => 'Recruiter Checklist', 'body' => <<<'TEXT'
            - Record EAD start and end dates exactly as shown.
            - Confirm the name matches other records.
            - Store and share EAD copies only through approved systems.
            - Escalate any unusual or unclear document to HR.
            TEXT],
        ['kind' => 'mistakes', 'heading' => 'Common Mistakes', 'body' => <<<'TEXT'
            - Reading dates in the wrong format.
            - Assuming a receipt notice allows work.
            - Sharing personal documents through personal messaging apps.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Green Card', 'body' => <<<'TEXT'
            A Green Card means permanent residence and open work authorization. Sponsorship questions always go to HR.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            - A Green Card holder is a lawful permanent resident, or LPR. The card itself is Form I-551.
            - A permanent resident can live in the U.S. and work for any employer, without an EAD.
            - The card is usually renewed every ten years. A conditional card lasts two years. Permanent resident status does not end just because the card expires.
            - A Green Card is not citizenship. A permanent resident may later apply to become a citizen.
            - People get Green Cards through family, through an employer, through investment and through other routes.
            TEXT],
        ['kind' => 'content', 'heading' => 'Employment-Based Green Card, at an Awareness Level', 'body' => <<<'TEXT'
            - The employer-sponsored route usually has several government steps, often over several years.
            - Some people waiting for their Green Card hold an EAD while their application is pending.
            - Sponsorship is a company policy decision made by management and HR, case by case.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            A candidate asks whether your company sponsors Green Cards. You do not answer from memory. You say: Sponsorship decisions are made by our management under company policy. I will connect you with HR, who can explain the current policy. You record the question in your notes.
            TEXT],
        ['kind' => 'mistakes', 'heading' => 'Common Mistakes', 'body' => <<<'TEXT'
            - Promising Green Card sponsorship, or a timeline.
            - Asking candidates whether they are a Green Card holder instead of using the approved work authorization questions.
            - Treating an expired card as proof that someone cannot work. HR decides.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Other Visa & Status Overview', 'body' => <<<'TEXT'
            Know the names and the basic work rule of each category, and leave every eligibility decision to HR and the employer.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            USCIS stands for U.S. Citizenship and Immigration Services. It is the government agency that decides petitions and applications such as OPT, STEM OPT, H-1B and Green Cards.
            A visa is a document placed in the passport that allows a person to travel to the U.S. and seek entry for a specific purpose.
            There are two basic types of U.S. visas. Immigrant visas are for people who intend to live permanently in the U.S. Nonimmigrant visas are for temporary purposes, such as tourism, business, study, exchange programs and temporary work.
            TEXT],
        ['kind' => 'reference', 'heading' => 'Categories You Will Hear About', 'body' => <<<'TEXT'
            | Category | What it is | Work? |
            | --- | --- | --- |
            | U.S. Citizen (USC) | Born in the U.S. or naturalized. Not a visa. | Any employer |
            | TN | Certain Canadian and Mexican professionals, under the USMCA, which replaced NAFTA in 2020. Up to three years at a time. | Sponsoring employer |
            | B-1 / B-2 | Business and tourist visitors. | No |
            | L-1 / L-2 | Intra-company transfer. L-1A managers up to seven years, L-1B specialised knowledge up to five years. L-2 is the dependent status. | Same company only; L-2 spouses may be authorized |
            | E-3 | Australian citizens in specialty occupations, up to two years at a time. | Sponsoring employer |
            | J-1 / J-2 | Exchange visitors and their dependents. Some J-1 holders must return home for two years. | Program rules; J-2 may apply for work authorization |
            | H-2A, H-2B, H-3 | Agricultural, non-agricultural and trainee categories. Rare in IT staffing. | Program rules |
            TEXT],
        ['kind' => 'content', 'heading' => 'Covered in Their Own Lessons', 'body' => <<<'TEXT'
            F-1, F-2, CPT, OPT, STEM OPT, H-1B, H-4, H-4 EAD, EAD and Green Card each have their own lesson in this module.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            A vendor asks whether you have any TN candidates. You check your notes and the requirement, and you confirm with your Account Manager that the client accepts TN before you search. You do not decide eligibility yourself.
            TEXT],
        ['kind' => 'takeaway', 'heading' => 'Key Takeaway', 'body' => <<<'TEXT'
            Know which statuses allow work and under what conditions, record the status exactly as the candidate gives it, and never advise on immigration. Refer those questions to HR or the DSO.
            TEXT],
    ],
];
