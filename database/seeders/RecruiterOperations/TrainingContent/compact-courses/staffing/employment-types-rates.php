<?php

/*
 * Combined lesson "Employment Types, W2/C2C & Rates". One topic per merged lesson:
 * Contract, Contract-to-Hire / CTH, Full-Time / Permanent, W2, C2C, Rate Structures.
 */

return [
    'title' => 'Employment Types, W2/C2C & Rates',
    'from' => 'Contract',
    'compliance' => true,
    'review' => 'Includes topics that were awaiting compliance review: Contract, Contract-to-Hire / CTH, Full-Time / Permanent, W2, C2C, Rate Structures.',
    'en' => [
        ['kind' => 'objective', 'heading' => 'Learning Objective', 'body' => <<<'TEXT'
            Tell the engagement types apart (contract, contract-to-hire and full-time), understand W2 and C2C arrangements, and explain rate structures to candidates professionally.
            TEXT],
        ['kind' => 'flow', 'heading' => 'Lesson Map', 'body' => <<<'TEXT'
            1. Contract [[Contract]]
            2. Contract-to-Hire / CTH [[Contract-to-Hire / CTH]]
            3. Full-Time / Permanent [[Full-Time / Permanent]]
            4. W2 [[W2]]
            5. C2C [[C2C]]
            6. Rate Structures [[Rate Structures]]
            7. Checklist & Common Mistakes [[Checklist & Common Mistakes]]
            TEXT],
        ['kind' => 'topic', 'heading' => 'Contract', 'body' => <<<'TEXT'
            Contract roles are time-bound. Be clear and honest about duration and extensions so candidates make informed decisions.
            TEXT],
        ['kind' => 'reference', 'heading' => 'Engagement Types at a Glance', 'body' => <<<'TEXT'
            | | Contract | Contract-to-Hire | Full-Time / Permanent |
            | --- | --- | --- | --- |
            | Employer | Usually the staffing company or another employer in the chain | The staffing company or another employer during the contract; the client if converted | The hiring company, directly |
            | Length | A defined or estimated period, such as six or twelve months | A contract period, such as three, six or twelve months, then a possible permanent offer | No fixed end date |
            | What is not guaranteed | Extensions | Conversion | Benefits and policies are set by the employer |
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            A contract role is work for a defined or estimated period, such as six months, twelve months or the length of a project. Contract roles are the most common engagement type in U.S. IT staffing.
            The consultant is usually employed by the staffing company or another employer in the chain, not by the end client.
            Contracts are often extended when the project continues, but extensions are never guaranteed.
            TEXT],
        ['kind' => 'content', 'heading' => 'Key contract details', 'body' => <<<'TEXT'
            - Duration, for example twelve months with possible extension.
            - Start date.
            - Rate, usually hourly.
            - Location and work mode.
            - Hours per week, usually forty.
            - Overtime policy, if any.
            - Interview process.
            TEXT],
        ['kind' => 'content', 'heading' => 'From the company training material', 'body' => <<<'TEXT'
            The Job Description Analysis training defines a contract as a fixed-term position that may include an extension, and reminds recruiters that work authorization requirements depend on the client and the engagement.
            The OPT Recruiter Training Material describes two kinds of contract.
            Contract, Independent, also called 1099. The candidate works as a contractor for a company for the contract period. In January, the company sends the candidate Form 1099 showing the amount earned in the previous year. The candidate then files a tax return with the Internal Revenue Service, called the IRS, and pays both the employee and the employer share of taxes.
            Contract, Corp to Corp. A contract between two companies, the client and a vendor. The vendor's consultant works with the client, and the client pays the vendor, sending the vendor a Form 1099.
            The US MNC Staffing document adds that 1099 arrangements apply only to Green Card holders, U.S. citizens and TN visa holders. They are not used for OPT or STEM OPT candidates, who work as W2 employees.
            TEXT],
        ['kind' => 'content', 'heading' => 'Why OPT candidates take contract roles', 'body' => <<<'TEXT'
            Contract roles can offer quicker starts and practical U.S. experience related to the degree, which OPT candidates value. Whether a specific contract arrangement suits a candidate's OPT or STEM OPT situation is a question for HR and the candidate's DSO.
            TEXT],
        ['kind' => 'content', 'heading' => 'How to explain a contract role', 'body' => <<<'TEXT'
            This is a twelve-month contract with our client in Atlanta. The client has indicated it may be extended, but extensions depend on the project and are not guaranteed. You would be on our payroll as your employer.
            Use language like this only if every part is true for the role.
            TEXT],
        ['kind' => 'content', 'heading' => 'Contract endings', 'body' => <<<'TEXT'
            Contracts may end early if a project is cancelled or budgets change. Prepare candidates honestly for this possibility and explain what support your company offers, using approved information.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            A requirement says six months, with possible extension. A candidate asks: Is this a long-term job? You answer honestly: It is a six-month contract. The client mentions a possible extension, but that is not guaranteed. You record that the candidate understands the duration.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Contract-to-Hire / CTH', 'body' => <<<'TEXT'
            Contract-to-hire offers a possible path to permanent work, but conversion is never guaranteed. Set honest expectations from the start.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            Contract-to-hire, also written as CTH or C2H, means the consultant starts on a contract, and the client may offer a permanent position after a period such as three, six or twelve months.
            During the contract period, the consultant is usually employed by the staffing company or another employer in the chain. If the client converts the consultant, the consultant becomes the client's direct employee.
            Conversion is not guaranteed. It depends on performance, budget and the client's needs.
            TEXT],
        ['kind' => 'content', 'heading' => 'Important considerations', 'body' => <<<'TEXT'
            Clients deciding on conversion may consider the candidate's work authorization and whether the client will sponsor. Some clients do not sponsor visas, which may affect conversion for some candidates. Do not speculate. Share only information confirmed by the client or vendor.
            Conversion may involve a conversion fee or agreement between the client and your company. These are business terms handled by management.
            After conversion, salary and benefits are set by the client.
            TEXT],
        ['kind' => 'content', 'heading' => 'From the company training material', 'body' => <<<'TEXT'
            The Job Description Analysis training defines contract-to-hire as a role that starts as a contract and may convert to permanent employment after the agreed period.
            The OPT Recruiter Training Material describes two versions.
            Contract to Hire, Independent, or 1099. The candidate first works as a contractor and is later hired as a full-time, permanent employee. In January, the company sends a Form 1099 for the contract period and a Form W-2 for the wages earned after the hire, and the candidate files a tax return using both.
            Contract to Hire, Corp to Corp. The vendor's consultant works with the client on contract for a period and is later hired as the client's permanent employee. The client sends the vendor a Form 1099 for the contract period.
            For OPT candidates, the contract period is normally on W2 with the staffing company, not 1099.
            TEXT],
        ['kind' => 'content', 'heading' => 'How to explain CTH to candidates', 'body' => <<<'TEXT'
            This role starts as a six-month contract on our payroll. The client may consider converting you to a full-time employee after that, based on performance and their needs. Conversion is not guaranteed.
            Use this only when it reflects the actual requirement.
            TEXT],
        ['kind' => 'content', 'heading' => 'Questions candidates ask', 'body' => <<<'TEXT'
            - Will they definitely convert me? Answer: Conversion depends on the client, so I cannot guarantee it.
            - Will they sponsor my visa after conversion? Answer: I will check whether the client has shared their sponsorship policy. Do not guess.
            - What salary will I get after conversion? Answer: That is decided by the client at the time of conversion.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            A requirement is labelled CTH, six months, in Phoenix. A candidate on STEM OPT is excited about conversion. You explain that conversion is the client's decision, and you ask the vendor whether the client has a sponsorship policy for conversions. You share only the answer you receive.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Full-Time / Permanent', 'body' => <<<'TEXT'
            Full-time roles are permanent employment with a named employer. Know who the employer is and confirm salary, benefits and policies before discussing them.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            A full-time or permanent role, sometimes called a direct hire or FTE role, means the candidate is hired directly by the company as its own employee, with no fixed end date.
            Pay is usually an annual salary rather than an hourly rate.
            Employees typically receive benefits such as health insurance, paid time off and retirement plans. The details are set by the employer.
            TEXT],
        ['kind' => 'content', 'heading' => 'Full-time with the client', 'body' => <<<'TEXT'
            In a direct hire placement, the staffing company helps the client find the person, but the client becomes the employer. The staffing company usually earns a placement fee. The candidate goes on the client's payroll.
            TEXT],
        ['kind' => 'content', 'heading' => 'Full-time with your company', 'body' => <<<'TEXT'
            Some staffing companies hire consultants as full-time salaried employees of the staffing company and place them on client projects. In this case, your company is the employer and runs payroll. Always know which model applies to the role you are discussing.
            TEXT],
        ['kind' => 'content', 'heading' => 'From the company training material', 'body' => <<<'TEXT'
            The Job Description Analysis training defines full-time or permanent as direct-hire employment with the client or employer. The OPT Recruiter Training Material calls this W2 salary with benefits: a full-time, permanent job where the employee is paid a salary and may receive benefits such as a joining bonus, vacation, holidays, workers' compensation, relocation expenses, leave encashment, an individual retirement account called an IRA, health, vision, dental and life insurance, a 401k retirement plan, education benefits and other retirement plans.
            The handout also says the company usually pays the candidate when there is no job or between projects. Company-specific process: pay between projects and every benefit listed depend on company policy and the individual offer. Verify with HR or authorized personnel before mentioning any benefit to a candidate.
            TEXT],
        ['kind' => 'content', 'heading' => 'Key details to confirm', 'body' => <<<'TEXT'
            - Employer name.
            - Salary range or offer amount.
            - Benefits overview, from approved information.
            - Location and work mode.
            - Start date.
            - Any sponsorship policy the employer has shared.
            TEXT],
        ['kind' => 'content', 'heading' => 'Why this matters for OPT candidates', 'body' => <<<'TEXT'
            OPT candidates must report their employer, and STEM OPT has specific employer requirements. A full-time role with a STEM OPT-eligible employer may be attractive, but eligibility is decided by HR and the employer, not the recruiter.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            A client requests a full-time direct hire data engineer in Seattle with a salary range. A candidate asks whether the client will sponsor H-1B. You check with the vendor, who says the client does not sponsor. You share this honestly, and the candidate decides whether to proceed.
            TEXT],
        ['kind' => 'topic', 'heading' => 'W2', 'body' => <<<'TEXT'
            W2 means the consultant is an employee on payroll with taxes withheld. It is the usual arrangement for OPT and STEM OPT candidates.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            In staffing conversations, W2 means the consultant is an employee of the staffing company or another employer, and is paid through that employer's payroll. The term comes from Form W-2, the annual wage statement that employers give employees. The form is covered in U.S. Payroll, Taxes & Payroll Forms (W-2).
            With W2 employment, the employer withholds taxes from pay, pays employer payroll taxes, and handles payroll reporting.
            TEXT],
        ['kind' => 'content', 'heading' => 'W2 hourly and W2 salary', 'body' => <<<'TEXT'
            - W2 hourly means the consultant is paid for each hour worked at an hourly rate.
            - W2 salary means the consultant receives a fixed annual salary paid in regular instalments.
            - Which applies depends on the employer and the role.
            TEXT],
        ['kind' => 'content', 'heading' => 'From the company training material', 'body' => <<<'TEXT'
            The OPT Recruiter Training Material explains W2 this way. On W2, the candidate works as an employee of a company. The employer takes care of the employee and of employee taxes. In January, the employer sends the employee a Form W-2 showing the previous year's wages and deductions, and the employee files a tax return with the IRS.
            The handout lists who can work on W2: U.S. citizens, Green Card holders, EAD holders, TN holders, OPT, CPT, H-1B and L-1, among others.
            It also describes three W2 types.
            W2 salary with benefits. A full-time, permanent job with a salary and benefits.
            W2 hourly with benefits. A full-time but temporary job, paid by the hour, with benefits. When the contract ends, the employee needs another assignment.
            W2 hourly with no benefits. A full-time, temporary job, paid by the hour, without benefits.
            The US MNC Staffing document adds that W2 applies to the company's own bench consultants, and that for W2 consultants the company bears insurance claims, taxes, overheads and some benefits.
            TEXT],
        ['kind' => 'content', 'heading' => 'Why W2 matters for OPT candidates', 'body' => <<<'TEXT'
            OPT and STEM OPT candidates usually work as employees. STEM OPT specifically requires a genuine employer-employee relationship with an E-Verify employer that has signed the I-983 training plan.
            For these reasons, most OPT recruiting involves W2 employment. Your company's policy decides which arrangements are allowed for which candidates.
            TEXT],
        ['kind' => 'content', 'heading' => 'What candidates may ask', 'body' => <<<'TEXT'
            - Will taxes be deducted? Yes, the employer withholds taxes based on the candidate's information and current rules. Payroll explains the details.
            - Will I get benefits? Benefits depend on the employer's policy. Share only approved information.
            - What is the difference between W2 and C2C? The C2C tab explains it.
            TEXT],
        ['kind' => 'content', 'heading' => 'How a W2 rate is discussed', 'body' => <<<'TEXT'
            A W2 hourly rate is the rate paid to the consultant before taxes. It is lower than the bill rate the client pays, because the employer covers payroll taxes, insurance and other costs from the difference.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            A candidate asks: Is this W2 or C2C? You confirm with your lead that the role is W2 hourly with your company as employer. You explain: You will be on our payroll as our employee. Taxes are withheld from your pay, and our payroll team will guide you through the setup.
            TEXT],
        ['kind' => 'topic', 'heading' => 'C2C', 'body' => <<<'TEXT'
            C2C is a business-to-business arrangement. For OPT and STEM OPT candidates it raises compliance questions, so always follow company policy and escalate.
            TEXT],
        ['kind' => 'reference', 'heading' => 'W2 vs C2C at a Glance', 'body' => <<<'TEXT'
            | | W2 | C2C |
            | --- | --- | --- |
            | Relationship | The consultant is an employee on the employer's payroll | Your company contracts with another company that employs, or is owned by, the consultant |
            | Pay and taxes | The employer withholds taxes, pays employer payroll taxes and handles payroll reporting | The other company invoices your company and handles the consultant's pay and taxes |
            | Rate | Lower than C2C for similar work, because the employer covers payroll taxes and costs | Usually higher, because the other company covers payroll taxes, insurance and other costs |
            | OPT and STEM OPT | The usual arrangement | Raises compliance questions: follow company policy and escalate |
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            C2C stands for Corp-to-Corp. In a C2C arrangement, your company contracts with another company, rather than paying the consultant directly as an employee. The consultant is usually employed by, or owns, that other company.
            The other company invoices your company. It handles the consultant's pay and taxes.
            C2C rates are usually higher than W2 rates, because the other company covers payroll taxes, insurance and other costs itself.
            TEXT],
        ['kind' => 'content', 'heading' => 'C2C and work authorization', 'body' => <<<'TEXT'
            Many C2C consultants are H-1B workers employed by another consulting company, or are citizens or green card holders with their own companies.
            For OPT candidates, the situation is different. STEM OPT requires a genuine employer-employee relationship with an E-Verify employer that signs the I-983. Arrangements in which an OPT or STEM OPT candidate works through their own company, or through a chain that does not meet these requirements, can create serious compliance risks.
            Recruiters never decide whether an arrangement is allowed. Follow company policy and escalate.
            TEXT],
        ['kind' => 'content', 'heading' => '1099', 'body' => <<<'TEXT'
            You may also hear 1099, which refers to an independent contractor paid without tax withholding, named after the tax form used. Like C2C, this is not a typical arrangement for OPT candidates. Follow company policy.
            TEXT],
        ['kind' => 'content', 'heading' => 'From the company training material', 'body' => <<<'TEXT'
            The US MNC Staffing document lists three payment types in recruiting. W2 is for the company's own bench consultants. C2C, Corp to Corp, is for other vendors' bench consultants. 1099 is only for Green Card holders, U.S. citizens and TN holders.
            The OPT Recruiter Training Material explains Corp to Corp as a contract between one company, the client, and another company, the vendor. The vendor's consultant works with the client, and the client sends the vendor a Form 1099. In daily work, this means C2C is how you work with another employer's consultant: you call that employer, agree the rate, and sign the paperwork with the employer, not the consultant.
            The company business plan also lists C2C marketing as a service line, meaning marketing profiles to client partners under C2C engagement models. Company-specific process: which consultants may be marketed under C2C is decided by management and compliance.
            TEXT],
        ['kind' => 'content', 'heading' => 'What to do when C2C comes up', 'body' => <<<'TEXT'
            - If a requirement is C2C only, check with your lead whether your company can participate and under which arrangement.
            - If an OPT candidate asks for C2C, explain politely that your company's process for OPT candidates is the approved arrangement, and refer questions to HR.
            - Never suggest that a candidate set up a company to work C2C.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            A vendor sends a requirement that says C2C only. You ask your lead how your company handles C2C requirements and which consultants are eligible. You submit only candidates and arrangements that your company has approved.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Rate Structures', 'body' => <<<'TEXT'
            Rates have layers. Know the approved pay rate, protect business information, and explain differences professionally.
            TEXT],
        ['kind' => 'flow', 'heading' => 'From Bill Rate to Pay Rate', 'body' => <<<'TEXT'
            1. Bill Rate. What the client or vendor pays for the consultant's time, usually per hour.
            2. Vendor Layers. Each layer in a vendor chain keeps part of the bill rate.
            3. Margin. The difference between bill and pay rate, covering employer taxes, insurance, overheads and profit.
            4. Pay Rate. What the consultant receives, before taxes for W2 employees.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            - Bill rate is what the client or vendor pays for the consultant's time, usually per hour.
            - Pay rate is what the consultant receives, before taxes for W2 employees.
            - Margin or spread is the difference between bill rate and pay rate. It covers employer taxes, insurance, overheads and the company's profit.
            - Each layer in a vendor chain keeps part of the bill rate, so the rate reaching the consultant depends on the chain.
            TEXT],
        ['kind' => 'content', 'heading' => 'Common rate types', 'body' => <<<'TEXT'
            - W2 hourly rate, paid to an employee for each hour worked.
            - W2 salary, a fixed annual amount.
            - C2C hourly rate, paid to another company.
            - All-inclusive rate, which means the rate includes expenses, with nothing extra for travel or other costs.
            - Overtime rate, if overtime is allowed and paid.
            TEXT],
        ['kind' => 'content', 'heading' => 'Hourly and annual equivalents', 'body' => <<<'TEXT'
            A full-time year is often estimated at about two thousand eighty working hours, which is forty hours a week for fifty-two weeks. An hourly rate of fifty dollars is roughly one hundred four thousand dollars a year before taxes, if every hour is paid. Real pay depends on holidays, leave and actual hours, so use this only as a rough comparison.
            TEXT],
        ['kind' => 'content', 'heading' => 'Rate split', 'body' => <<<'TEXT'
            Some companies describe pay as a percentage split of the bill rate, for example a stated percentage to the consultant. This is a company-specific practice. Explain it only using your company's approved terms.
            TEXT],
        ['kind' => 'content', 'heading' => 'From the company training material', 'body' => <<<'TEXT'
            The US MNC Staffing document teaches recruiters to discuss the rate with W2 consultants directly, and with the employer for C2C consultants, to negotiate the rate, and to send a written rate confirmation before submission. It mentions keeping a minimum margin of five dollars for the company. Company-specific process: margin rules are set by management, and you should never mention margins to consultants or employers. The calling script's screening questionnaire also records the candidate's expected hourly rate, W2 or C2C.
            TEXT],
        ['kind' => 'content', 'heading' => 'Discussing rates with candidates', 'body' => <<<'TEXT'
            - Ask for the candidate's expected rate or salary and whether it is W2.
            - Share the pay rate approved by your team.
            - Never share bill rates or margins with candidates unless your company allows it.
            - Explain the reasons for differences calmly. Calling & Communication → Candidate Questions & Objections covers rate objections.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            A candidate says: The vendor posted this role at seventy dollars an hour, but you are offering forty-five on W2. You explain: The posted rate is the bill rate in the chain, which covers several companies, employer taxes and costs. The forty-five dollar W2 rate is your pay before taxes. You stay calm and factual.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Checklist & Common Mistakes', 'body' => <<<'TEXT'
            Every topic's checklist and common mistakes in one place. Use it as a quick review before you work on a real requirement.
            TEXT],
        ['kind' => 'reference', 'heading' => 'Recruiter Checklist', 'body' => <<<'TEXT'
            **Contract**
            - Confirm duration, extension wording and start date.
            - Explain the contract nature honestly.
            - Record that the candidate understood the terms.
            **Contract-to-Hire / CTH**
            - Confirm the contract period and conversion wording.
            - Never guarantee conversion.
            - Ask the vendor about client policies when candidates ask.
            - Record exactly what the candidate was told.
            **Full-Time / Permanent**
            - Confirm whether the employer is the client or your company.
            - Express salary as annual unless told otherwise.
            - Share sponsorship information only when confirmed.
            **W2**
            - Know whether the role is W2 hourly or W2 salary.
            - Know who the W2 employer is.
            - Refer tax questions to payroll.
            **C2C**
            - Know your company's policy on C2C and 1099.
            - Never place an OPT candidate on an arrangement without HR approval.
            - Escalate C2C questions.
            **Rate Structures**
            - Know the pay rate approved for the role.
            - Protect bill rates and margins.
            - Use rough annual conversions only for comparison.
            TEXT],
        ['kind' => 'mistakes', 'heading' => 'Common Mistakes', 'body' => <<<'TEXT'
            **Contract**
            - Describing a contract as permanent.
            - Promising an extension.
            - Forgetting to confirm hours and overtime policy.
            **Contract-to-Hire / CTH**
            - Selling CTH as a guaranteed full-time job.
            - Guessing about sponsorship after conversion.
            - Discussing conversion fees with candidates.
            **Full-Time / Permanent**
            - Confusing a direct hire role with a contract role.
            - Converting an hourly rate to salary incorrectly in front of a candidate.
            - Promising benefits details that you have not confirmed.
            **W2**
            - Calling a role W2 without confirming.
            - Giving tax advice.
            - Comparing W2 and C2C rates without explaining the cost difference.
            **C2C**
            - Treating C2C and W2 rates as directly comparable.
            - Suggesting C2C to OPT candidates.
            - Assuming a requirement's C2C label decides the arrangement for your consultant.
            **Rate Structures**
            - Sharing bill rates with candidates.
            - Promising a rate before it is approved.
            - Comparing C2C and W2 rates as if they are equal.
            TEXT],
        ['kind' => 'takeaway', 'heading' => 'Key Takeaway', 'body' => <<<'TEXT'
            Know which engagement type and arrangement a role uses, speak about the approved pay rate only, protect bill rates and margins, and explain rate differences calmly.
            TEXT],
    ],
];
