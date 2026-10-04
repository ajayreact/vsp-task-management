<?php

/*
 * Combined lesson "U.S. Payroll, Taxes & Payroll Forms". One topic per merged lesson:
 * Gross Pay, Net Pay, Pay Period, Federal Income Tax, Withholding, W-4, Social Security, Medicare, FICA, FUTA, SUTA/SUI, W-2, EIN.
 */

return [
    'title' => 'U.S. Payroll, Taxes & Payroll Forms',
    'from' => 'Gross Pay',
    'compliance' => true,
    'review' => 'Includes topics that were awaiting compliance review: Gross Pay, Net Pay, Pay Period, Federal Income Tax, Withholding, W-4, Social Security, Medicare, FICA, FUTA, SUTA/SUI, W-2, EIN.',
    'en' => [
        ['kind' => 'objective', 'heading' => 'Learning Objective', 'body' => <<<'TEXT'
            Understand how a U.S. paycheck is built: gross and net pay, pay periods, the federal and state taxes withheld or paid by the employer, the W-4 and W-2 forms and the EIN, so you can answer basic questions and refer the rest to payroll.
            TEXT],
        ['kind' => 'flow', 'heading' => 'Lesson Map', 'body' => <<<'TEXT'
            1. Gross Pay [[Gross Pay]]
            2. Net Pay [[Net Pay]]
            3. Pay Period [[Pay Period]]
            4. Federal Income Tax [[Federal Income Tax]]
            5. Withholding [[Withholding]]
            6. W-4 [[W-4]]
            7. Social Security [[Social Security]]
            8. Medicare [[Medicare]]
            9. FICA [[FICA]]
            10. FUTA [[FUTA]]
            11. SUTA/SUI [[SUTA/SUI]]
            12. W-2 [[W-2]]
            13. EIN [[EIN]]
            14. Checklist & Common Mistakes [[Checklist & Common Mistakes]]
            TEXT],
        ['kind' => 'topic', 'heading' => 'Gross Pay', 'body' => <<<'TEXT'
            Gross pay is earnings before deductions. Be clear that it is not the amount the consultant takes home.
            TEXT],
        ['kind' => 'flow', 'heading' => 'From Gross Pay to Net Pay', 'body' => <<<'TEXT'
            1. Gross Pay. Earnings before any taxes or deductions. [[Gross Pay]]
            2. Federal Income Tax. Withheld based on the employee's W-4. [[Withholding]]
            3. Social Security and Medicare. Together called FICA, where they apply. [[FICA]]
            4. State and Local Taxes. In states and cities that have them.
            5. Benefits and Retirement. Only if the employee enrolls or chooses them.
            6. Net Pay. The amount the employee actually receives. [[Net Pay]]
            7. Employer Taxes. FUTA, and in most states SUTA, are paid by the employer and not deducted. [[FUTA]]
            TEXT],
        ['kind' => 'reference', 'heading' => 'Who Pays Each Tax', 'body' => <<<'TEXT'
            | Tax | Paid by | Deducted from the employee's pay? |
            | --- | --- | --- |
            | Federal income tax | The employee, withheld by the employer based on the W-4 | Yes |
            | Social Security | The employee and the employer, in equal shares | The employee's share |
            | Medicare | The employee and the employer | The employee's share |
            | FUTA | The employer only | No |
            | SUTA / SUI | The employer in most states; a few states also require employee contributions | Only in those states |
            | State and local income tax | The employee, where the state or city has one | Yes, where it applies |
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            - Gross pay is the total amount an employee earns in a pay period before any taxes or deductions are taken out.
            - For an hourly employee, gross pay equals hours worked multiplied by the hourly rate, plus any overtime or other earnings.
            - For a salaried employee, gross pay equals the annual salary divided by the number of pay periods in the year.
            TEXT],
        ['kind' => 'example', 'heading' => 'Examples', 'body' => <<<'TEXT'
            A consultant paid fifty dollars an hour who works eighty hours in a two-week pay period has a gross pay of four thousand dollars for that period.
            An employee with an annual salary of ninety-six thousand dollars, paid twice a month, has a gross pay of four thousand dollars per pay period, because there are twenty-four pay periods in the year.
            TEXT],
        ['kind' => 'content', 'heading' => 'What can be included in gross pay', 'body' => <<<'TEXT'
            - Regular pay for hours worked or salary.
            - Overtime pay, if eligible.
            - Holiday or paid leave pay, if the employer provides it.
            - Bonuses or other earnings, if any.
            TEXT],
        ['kind' => 'content', 'heading' => 'Timesheets and hourly gross pay', 'body' => <<<'TEXT'
            For hourly consultants, gross pay depends on approved timesheets. If a timesheet is late or incorrect, pay can be delayed or wrong. Remind consultants to submit timesheets on time according to the client's and your payroll team's process.
            TEXT],
        ['kind' => 'content', 'heading' => 'Why recruiters should understand gross pay', 'body' => <<<'TEXT'
            Candidates often compare offers using gross numbers. When you discuss an hourly rate or salary, you are discussing gross pay. Make sure candidates understand that their take-home pay, called net pay, will be lower after taxes and deductions.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            A candidate asks how much she will receive every two weeks at a forty-dollar hourly W2 rate. You explain that if she works eighty hours in a two-week period, her gross pay would be three thousand two hundred dollars before taxes and deductions, and that payroll can explain her net pay.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Net Pay', 'body' => <<<'TEXT'
            Net pay is what remains after taxes and deductions. Payroll calculates it, and recruiters set honest expectations.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            Net pay, often called take-home pay, is the amount an employee actually receives after all taxes and deductions are subtracted from gross pay.
            Net pay equals gross pay minus taxes minus other deductions.
            TEXT],
        ['kind' => 'content', 'heading' => 'Common deductions', 'body' => <<<'TEXT'
            - Federal income tax withholding.
            - Social Security and Medicare taxes, together called FICA, where they apply.
            - State income tax, in states that have one.
            - Local taxes in some cities or counties.
            - Benefit deductions, such as the employee's share of health insurance, if enrolled.
            - Retirement contributions, if the employee chooses them.
            TEXT],
        ['kind' => 'content', 'heading' => 'Why net pay varies', 'body' => <<<'TEXT'
            Net pay depends on the employee's W-4 choices, the work state, the local area, benefit elections, and the employee's tax situation. For some F-1 students who are nonresident aliens for tax purposes, certain taxes may not apply. Payroll decides this based on the rules. Recruiters never estimate it.
            Two people with the same gross pay can have different net pay.
            TEXT],
        ['kind' => 'content', 'heading' => 'Pay stubs', 'body' => <<<'TEXT'
            Each pay period, employees receive a pay stub, also called a pay statement, that lists gross pay, each deduction and net pay. Encourage consultants to review their stubs and to contact payroll with questions.
            TEXT],
        ['kind' => 'content', 'heading' => 'Recruiter role', 'body' => <<<'TEXT'
            - Be clear that rates and salaries are gross amounts.
            - Do not promise a specific net amount.
            - Refer net pay questions to payroll, with the consultant's work state and start date in your handoff, because these affect withholding.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            A consultant says: My offer was forty dollars an hour, but my pay is much less. You explain that the offer was a gross rate, and taxes and deductions are subtracted before payment. You connect him with payroll to review his pay stub in detail.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Pay Period', 'body' => <<<'TEXT'
            Pay periods define when consultants are paid. Use the payroll calendar to set accurate expectations from the first day.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            A pay period is the span of time for which an employee is paid, for example two weeks. The pay date is the day the payment is made, which is usually a few days after the pay period ends, so that timesheets can be approved and payroll processed.
            TEXT],
        ['kind' => 'content', 'heading' => 'Common pay frequencies', 'body' => <<<'TEXT'
            - Weekly, fifty-two pay periods a year.
            - Biweekly, every two weeks, twenty-six pay periods a year. This is very common.
            - Semi-monthly, twice a month, often on the fifteenth and the last day, twenty-four pay periods a year.
            - Monthly, twelve pay periods a year. This is less common for hourly workers.
            - States have rules about how often employees must be paid. Payroll sets the schedule.
            TEXT],
        ['kind' => 'content', 'heading' => 'First pay check', 'body' => <<<'TEXT'
            A new consultant's first pay may arrive later than they expect. If someone starts in the middle of a pay period, their first payment comes on the pay date for that period, or sometimes the next one, depending on cut-off dates and timesheet approval.
            TEXT],
        ['kind' => 'content', 'heading' => 'Timesheets', 'body' => <<<'TEXT'
            For hourly consultants, the client or vendor must approve timesheets. Late approval can delay pay. Remind consultants of the timesheet deadlines that payroll gives you.
            TEXT],
        ['kind' => 'content', 'heading' => 'Direct deposit', 'body' => <<<'TEXT'
            Most U.S. employers pay through direct deposit into a bank account. New consultants usually need a U.S. bank account. Payroll collects the bank details securely during onboarding.
            TEXT],
        ['kind' => 'content', 'heading' => 'Recruiter role', 'body' => <<<'TEXT'
            - Know your company's pay frequency and pay dates.
            - Explain the first pay date accurately, using the payroll calendar.
            - Remind consultants about timesheets and bank details.
            - Refer pay problems to payroll quickly.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            A consultant starts on a Wednesday, and your company pays biweekly. You check the payroll calendar and tell him the exact date of his first pay, and that it depends on his timesheet being approved by the cut-off date.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Federal Income Tax', 'body' => <<<'TEXT'
            Federal income tax is withheld from pay based on the W-4 and IRS rules. Know the concept and leave every tax question to payroll.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            Federal income tax is a tax on income collected by the U.S. federal government through the Internal Revenue Service, known as the IRS.
            The U.S. uses a progressive system. Higher portions of income are taxed at higher rates. Rates and brackets can change from year to year.
            Employers do not decide how much federal income tax a person owes for the year. Employers withhold an estimated amount from each paycheck, based on the employee's Form W-4 and IRS rules.
            After the year ends, individuals file a tax return to calculate the actual tax owed. They may receive a refund or owe more.
            TEXT],
        ['kind' => 'content', 'heading' => 'Residency for tax purposes', 'body' => <<<'TEXT'
            For tax purposes, the U.S. classifies people as residents or nonresidents. This tax residency is different from immigration status. Many F-1 students are treated as nonresident aliens for tax purposes for a period of years, which affects how their taxes are calculated and which forms they file. Some may be covered by tax treaties between the U.S. and their home country.
            These are complex rules. Payroll and tax professionals handle them. Recruiters should simply know that differences exist and avoid comparisons between consultants.
            TEXT],
        ['kind' => 'content', 'heading' => 'State and federal', 'body' => <<<'TEXT'
            Federal income tax applies across the country. Most states also have their own income tax, but some states do not. A few cities and counties have local income taxes. This is why the work state matters.
            TEXT],
        ['kind' => 'content', 'heading' => 'Recruiter role', 'body' => <<<'TEXT'
            - Know that federal income tax is withheld from W2 pay.
            - Never estimate a candidate's tax.
            - Never advise on filing, refunds or treaties.
            - Provide accurate work location and start date details to payroll.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            A consultant asks: Should I claim exemption on my W-4? You do not answer. You respond: Payroll and a qualified tax professional can guide you on that. I will connect you with payroll.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Withholding', 'body' => <<<'TEXT'
            Withholding is how taxes are collected from each paycheck. Your accurate handoff makes it correct from day one.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            Withholding means the employer takes certain taxes out of an employee's pay and sends them to the government on the employee's behalf.
            Withholding happens every pay period for W2 employees. It does not normally happen for C2C or 1099 arrangements, where the other company or person handles their own taxes.
            TEXT],
        ['kind' => 'content', 'heading' => 'Common withholdings', 'body' => <<<'TEXT'
            - Federal income tax, based on the employee's Form W-4.
            - Social Security and Medicare taxes, together called FICA, where they apply.
            - State income tax, in states with an income tax, often based on a state withholding form.
            - Local taxes, in certain cities or counties.
            - Some states also require employee contributions for programmes such as disability or unemployment insurance.
            TEXT],
        ['kind' => 'content', 'heading' => 'What affects withholding', 'body' => <<<'TEXT'
            - The employee's W-4 and any state forms.
            - The work state and, sometimes, the home state if they are different.
            - The pay amount and pay frequency.
            - Special rules for certain workers, such as nonresident aliens for tax purposes, which payroll applies.
            TEXT],
        ['kind' => 'content', 'heading' => 'Why accuracy matters', 'body' => <<<'TEXT'
            - If the work state is wrong, the wrong state's tax may be withheld. Correcting this later can be complicated for the consultant and the company.
            - If the start date or rate is wrong, pay and withholding will be wrong.
            - If the employee's name or Social Security Number is wrong, tax reporting will be wrong.
            TEXT],
        ['kind' => 'content', 'heading' => 'Recruiter role', 'body' => <<<'TEXT'
            - Provide payroll with accurate work state, work city, start date, rate and employee details.
            - Tell consultants that payroll will guide them through tax forms.
            - Never suggest withholding choices.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            A consultant lives in New Jersey but will work onsite in New York. You record both the home address and the work location in your handoff, and you note this clearly, because payroll needs both to set up withholding correctly.
            TEXT],
        ['kind' => 'topic', 'heading' => 'W-4', 'body' => <<<'TEXT'
            The W-4 tells the employer how much federal income tax to withhold. The employee completes it, payroll guides it, and recruiters stay out of it.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            Form W-4 is the Employee's Withholding Certificate. A new W2 employee completes it so that the employer knows how much federal income tax to withhold from pay.
            The current W-4 asks about filing status, multiple jobs, dependents and other adjustments. Older versions used allowances, which may still come up in conversation.
            Employees can submit a new W-4 at any time if their situation changes.
            If an employee does not submit a W-4, the employer withholds as if the employee were single with no adjustments, under current IRS rules.
            TEXT],
        ['kind' => 'content', 'heading' => 'Nonresident aliens', 'body' => <<<'TEXT'
            Employees who are nonresident aliens for tax purposes, which includes many F-1 students on OPT, must follow special IRS instructions when completing the W-4. Payroll provides guidance. This is a common source of confusion, so it is especially important that recruiters do not advise.
            TEXT],
        ['kind' => 'content', 'heading' => 'State forms', 'body' => <<<'TEXT'
            Many states have their own withholding forms in addition to the federal W-4. Payroll provides the correct ones based on the work state.
            TEXT],
        ['kind' => 'content', 'heading' => 'Recruiter role', 'body' => <<<'TEXT'
            - Tell new hires that payroll will provide the W-4 and any state forms during onboarding.
            - Remind them to complete forms on time.
            - Refer every question about how to fill in the form to payroll or a qualified tax professional.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            A new consultant asks: Which filing status should I choose on my W-4? You respond: I cannot advise on tax forms. Payroll can explain the form, and a qualified tax professional can advise on your choices. I will connect you with payroll.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Social Security', 'body' => <<<'TEXT'
            Social Security tax is a shared payroll tax with special rules for some students. Understand the concept and leave the decision to payroll.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            Social Security is a U.S. federal programme that provides retirement, disability and survivor benefits. It is funded mainly by a payroll tax.
            For W2 employees, the employee and the employer each pay Social Security tax on wages. For many years the rate has been six point two percent each, up to an annual wage limit called the wage base, which usually changes each year. Payroll applies the current rate and limit.
            Self-employed people pay both shares themselves through self-employment tax.
            TEXT],
        ['kind' => 'content', 'heading' => 'Social Security Number', 'body' => <<<'TEXT'
            The Social Security Number, or SSN, issued by the Social Security Administration, is used to track earnings and taxes. Employees need an SSN for payroll reporting. The Immigration & Work Authorization and Documentation, Onboarding & Payroll Handoff courses cover how to handle SSNs safely.
            TEXT],
        ['kind' => 'content', 'heading' => 'Special situations to be aware of', 'body' => <<<'TEXT'
            Under long-standing rules, many F-1 students, including those on OPT and STEM OPT, who are nonresident aliens for tax purposes may be exempt from Social Security and Medicare taxes on qualifying employment. Once a person becomes a resident for tax purposes, these taxes generally apply. H-1B workers are generally subject to these taxes.
            Payroll determines whether the exemption applies to each consultant, using their documents and residency status. Recruiters never decide or promise this.
            If a consultant thinks these taxes were withheld incorrectly, refer them to payroll.
            TEXT],
        ['kind' => 'content', 'heading' => 'Recruiter role', 'body' => <<<'TEXT'
            - Understand the term when consultants mention it.
            - Do not tell candidates that they will or will not pay Social Security tax.
            - Hand off accurate personal and start details to payroll.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            A consultant on OPT says a friend on OPT does not pay Social Security tax, but he does. You do not compare cases. You say: Payroll reviews each person's situation under the rules. I will connect you with payroll so they can explain your pay stub.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Medicare', 'body' => <<<'TEXT'
            Medicare tax is a shared payroll tax without a wage limit. It is not health insurance for the worker today.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            Medicare is the U.S. federal health insurance programme mainly for people aged sixty-five and older and for some people with disabilities. Like Social Security, it is funded through payroll tax.
            For W2 employees, the employee and the employer each pay Medicare tax on wages. For many years the rate has been one point four five percent each.
            Unlike Social Security, Medicare tax has no wage limit. It applies to all covered wages.
            Employees with wages above a set threshold pay an Additional Medicare Tax on the amount above that threshold. Employers withhold it once wages pass the threshold. The employer does not match this additional tax.
            TEXT],
        ['kind' => 'content', 'heading' => 'Special situations', 'body' => <<<'TEXT'
            The same rules that may exempt some F-1 students on OPT and STEM OPT from Social Security tax may also exempt them from Medicare tax while they are nonresident aliens for tax purposes. Payroll determines this.
            TEXT],
        ['kind' => 'content', 'heading' => 'Medicare tax compared with health insurance', 'body' => <<<'TEXT'
            Medicare tax is not the same as employer health insurance. Paying Medicare tax does not give a young worker health coverage today. Health insurance through an employer is a separate benefit with its own enrolment and deductions. Candidates sometimes confuse the two. Explain the difference simply and refer details to HR.
            TEXT],
        ['kind' => 'content', 'heading' => 'Recruiter role', 'body' => <<<'TEXT'
            - Understand the term and the difference from health insurance.
            - Do not promise exemptions.
            - Refer pay stub questions to payroll and benefits questions to HR.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            A consultant asks: I pay Medicare tax, so am I covered for doctor visits? You explain: Medicare tax funds a federal programme mainly for older people. Health insurance through your employer is a separate benefit. HR can explain the plans available to you.
            TEXT],
        ['kind' => 'topic', 'heading' => 'FICA', 'body' => <<<'TEXT'
            FICA is the combined Social Security and Medicare payroll tax, shared by employee and employer.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            - FICA stands for the Federal Insurance Contributions Act. FICA tax is the combined name for Social Security tax and Medicare tax.
            - On a pay stub, these may appear separately, often labelled OASDI or Social Security, and Medicare, or together as FICA.
            - Both the employee and the employer pay FICA. The employee's share is withheld from pay. The employer pays its share on top of wages.
            TEXT],
        ['kind' => 'content', 'heading' => 'Putting the numbers together', 'body' => <<<'TEXT'
            Using the long-standing rates, the employee pays six point two percent for Social Security, up to the wage base, and one point four five percent for Medicare, for a combined seven point six five percent. The employer pays the same again. Additional Medicare Tax may apply to high earners. Payroll applies the current figures.
            TEXT],
        ['kind' => 'content', 'heading' => 'Why FICA matters in staffing', 'body' => <<<'TEXT'
            The employer's FICA share is one of the costs covered by the margin between bill rate and pay rate. This is part of why a W2 pay rate is lower than a C2C rate for similar work. When candidates ask why W2 rates are lower, FICA is one of the reasons.
            TEXT],
        ['kind' => 'content', 'heading' => 'FICA and OPT', 'body' => <<<'TEXT'
            Certain F-1 students who are nonresident aliens for tax purposes may be exempt from FICA on qualifying employment, as covered in the Social Security and Medicare tabs. Payroll decides each case. If FICA was withheld when it should not have been, or not withheld when it should have been, payroll corrects it according to the rules.
            TEXT],
        ['kind' => 'content', 'heading' => 'Recruiter role', 'body' => <<<'TEXT'
            - Know that FICA means Social Security plus Medicare.
            - Use FICA as one explanation for W2 and C2C rate differences.
            - Leave all calculations and exemptions to payroll.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            A candidate asks why the C2C rate is ten dollars higher than the W2 rate. You explain that on W2, the employer pays its own share of FICA and other employment costs, while on C2C the other company covers these itself.
            TEXT],
        ['kind' => 'topic', 'heading' => 'FUTA', 'body' => <<<'TEXT'
            FUTA is a federal unemployment tax paid only by employers. It is part of staffing costs, not an employee deduction.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            FUTA stands for the Federal Unemployment Tax Act. FUTA tax funds the federal part of the U.S. unemployment insurance system, which supports state unemployment programmes.
            FUTA is paid only by the employer. It is not withheld from the employee's pay, so it does not appear as a deduction on the employee's pay stub.
            Under long-standing rules, FUTA applies to a limited amount of each employee's wages per year. Employers that pay their state unemployment taxes on time usually receive a large credit, which significantly reduces the federal rate. Payroll handles the current rates and credits.
            TEXT],
        ['kind' => 'content', 'heading' => 'FUTA and the states', 'body' => <<<'TEXT'
            FUTA works together with state unemployment tax, called SUTA or SUI, covered in the next tab. The state programmes pay unemployment benefits to eligible workers who lose their jobs. The federal tax supports administration and loans to state funds.
            TEXT],
        ['kind' => 'content', 'heading' => 'Why recruiters should know this', 'body' => <<<'TEXT'
            FUTA is another employer cost included in the margin between bill rate and pay rate.
            Some consultants ask whether the unemployment taxes on their pay stub are FUTA. They are not, because FUTA is never deducted from employees.
            TEXT],
        ['kind' => 'content', 'heading' => 'Unemployment benefits and OPT', 'body' => <<<'TEXT'
            Eligibility for unemployment benefits depends on state law and many factors, and can involve immigration considerations. Recruiters must never advise anyone on whether to apply for unemployment benefits. Also remember that unemployment benefits are different from the OPT unemployment day limits taught in Immigration & Work Authorization.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            A consultant asks: Why is federal unemployment tax not on my pay stub? You explain: FUTA is paid entirely by the employer, so it is not deducted from your pay.
            TEXT],
        ['kind' => 'topic', 'heading' => 'SUTA/SUI', 'body' => <<<'TEXT'
            State unemployment taxes depend on where the work is done. Accurate and up-to-date work location information is essential.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            SUTA stands for State Unemployment Tax Act. SUI stands for State Unemployment Insurance. Both terms describe the state-level unemployment tax that funds each state's unemployment benefit programme.
            In most states, SUTA is paid only by the employer. A few states also require employee contributions, which then appear as deductions on the pay stub.
            Each state sets its own taxable wage limit and rates. New employers usually receive a standard new employer rate. Established employers receive a rate based on their history, often called the experience rating.
            TEXT],
        ['kind' => 'content', 'heading' => 'Why the work state matters', 'body' => <<<'TEXT'
            SUTA is generally paid to the state where the employee works, following rules that look at where the work is performed and other factors. For remote workers, and for people who work in more than one state, the rules can be more complex.
            An incorrect work state can lead to taxes being paid to the wrong state, which payroll must later correct. This is why the work state in your handoff must always be accurate.
            TEXT],
        ['kind' => 'content', 'heading' => 'Other state programmes', 'body' => <<<'TEXT'
            Some states have additional payroll programmes, such as state disability insurance or paid family leave, which can involve employee deductions. Payroll manages these.
            TEXT],
        ['kind' => 'content', 'heading' => 'Recruiter role', 'body' => <<<'TEXT'
            - Record the exact work city and state for every placement.
            - For remote roles, record where the consultant will actually be working from.
            - Inform payroll immediately if a consultant moves to another state.
            - Never advise on state taxes.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            A remote consultant tells you she is moving from Texas to California next month. You inform payroll and HR right away, with the planned move date, because this change can affect state payroll taxes and other obligations.
            TEXT],
        ['kind' => 'topic', 'heading' => 'W-2', 'body' => <<<'TEXT'
            Form W-2 summarises a year's wages and taxes. W2 employment means being on payroll. Keep addresses accurate and send questions to payroll.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            Form W-2 is the Wage and Tax Statement. Employers send it to each employee, and to the government, after the end of each calendar year. Under current rules, employers must furnish it by the end of January for the previous year.
            The W-2 shows the year's total wages and the taxes withheld, including federal income tax, Social Security and Medicare taxes, and state and local taxes where they apply.
            Employees use the W-2 to file their tax return.
            TEXT],
        ['kind' => 'content', 'heading' => 'The form and the staffing term', 'body' => <<<'TEXT'
            Form W-2, with a hyphen, is the tax document.
            W2, often written without a hyphen in staffing, describes an employment arrangement in which the consultant is an employee on payroll. The name comes from the fact that such employees receive a Form W-2.
            Make sure you know which one a person means.
            TEXT],
        ['kind' => 'content', 'heading' => 'Common questions from consultants', 'body' => <<<'TEXT'
            - When will I receive my W-2? Payroll provides the date and the method, which may be electronic.
            - My W-2 is wrong. Refer to payroll. Corrections use a separate form, the W-2c.
            - I worked for two employers. Each employer issues its own W-2.
            - I moved states. The W-2 may show wages for more than one state. Payroll explains.
            TEXT],
        ['kind' => 'content', 'heading' => 'Other forms', 'body' => <<<'TEXT'
            Independent contractors receive Form 1099 instead of a W-2.
            Some nonresident employees may also receive other tax forms, depending on their situation. Payroll handles this.
            TEXT],
        ['kind' => 'content', 'heading' => 'From the company training material', 'body' => <<<'TEXT'
            The OPT Recruiter Training Material explains it the same way: the employer sends the W-2 form to the employee in January, for the previous year's wages and deductions, and the employee submits a tax return to the Internal Revenue Service. For a contract-to-hire candidate, the company sends both a Form 1099 for the contract period and a Form W-2 for the wages earned after the hire.
            TEXT],
        ['kind' => 'content', 'heading' => 'Recruiter role', 'body' => <<<'TEXT'
            - Understand the difference between the W-2 form and W2 employment.
            - Keep consultant addresses up to date with payroll, because W-2 forms are sent to the address on file.
            - Refer all W-2 questions to payroll.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            In February, a former consultant says he did not receive his W-2. You do not guess. You confirm his current address and pass his request to payroll.
            TEXT],
        ['kind' => 'topic', 'heading' => 'EIN', 'body' => <<<'TEXT'
            The EIN identifies a business for tax purposes. Recognise it, and route all requests through HR.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            An Employer Identification Number, or EIN, is a nine-digit number that the Internal Revenue Service assigns to a business for tax purposes. It is sometimes called a Federal Tax Identification Number. It is usually written with two digits, a hyphen and seven digits.
            Think of the EIN as the business's equivalent of a Social Security Number.
            TEXT],
        ['kind' => 'content', 'heading' => 'Where the EIN appears', 'body' => <<<'TEXT'
            - On employees' W-2 forms, identifying the employer.
            - On payroll tax filings.
            - On contracts and invoices between companies, such as in C2C arrangements.
            - On some immigration forms, such as the STEM OPT training plan, Form I-983, which asks for the employer's EIN.
            - In vendor onboarding, where companies exchange tax details.
            TEXT],
        ['kind' => 'content', 'heading' => 'EIN and E-Verify', 'body' => <<<'TEXT'
            Do not confuse the EIN with the E-Verify company identification number. They are different numbers. STEM OPT candidates may need the employer's EIN for their I-983 and the E-Verify number for their STEM OPT application. HR provides both through the approved process.
            TEXT],
        ['kind' => 'content', 'heading' => 'Handling the EIN', 'body' => <<<'TEXT'
            An EIN is less sensitive than a personal Social Security Number, but it is still business information. Share it only through approved channels and only when there is a legitimate need, such as a training plan prepared by HR.
            Be cautious of unknown parties asking for your company's EIN. Fraudsters sometimes misuse business identifiers.
            TEXT],
        ['kind' => 'content', 'heading' => 'Recruiter role', 'body' => <<<'TEXT'
            - Know what an EIN is when it comes up.
            - Refer requests for the company's EIN to HR.
            - Never provide another company's EIN or tax details without authorisation.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            A STEM OPT candidate asks for your company's EIN to complete her paperwork. You respond: HR will provide the employer details needed for your STEM OPT paperwork. I will let them know you need them.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Checklist & Common Mistakes', 'body' => <<<'TEXT'
            Every topic's checklist and common mistakes in one place. Use it as a quick review before you work on a real requirement.
            TEXT],
        ['kind' => 'reference', 'heading' => 'Recruiter Checklist', 'body' => <<<'TEXT'
            **Gross Pay**
            - Speak about rates and salaries as gross amounts.
            - Remind hourly consultants that timesheets drive pay.
            - Refer net pay questions to payroll.
            **Net Pay**
            - Distinguish gross from net in every pay conversation.
            - Avoid estimating taxes.
            - Route pay stub questions to payroll.
            **Federal Income Tax**
            - Refer all tax questions to payroll.
            - Remember that tax residency is not immigration status.
            - Ensure the work state is correct in your handoff.
            **Withholding**
            - Record both home and work locations when they differ.
            - Double-check start date and rate.
            - Leave tax setup to payroll.
            TEXT],
        ['kind' => 'mistakes', 'heading' => 'Common Mistakes', 'body' => <<<'TEXT'
            **Gross Pay**
            - Presenting gross pay as take-home pay.
            - Calculating with the wrong number of pay periods.
            **Net Pay**
            - Promising take-home amounts.
            - Guessing which taxes apply to an OPT consultant.
            **Pay Period**
            - Promising a first pay date without checking.
            - Forgetting to mention timesheet deadlines.
            **Federal Income Tax**
            - Telling a consultant they will get a refund.
            - Advising on W-4 choices.
            **Withholding**
            - Recording only the client's headquarters as the work location.
            - Telling a consultant how to fill in withholding forms.
            **W-4**
            - Telling a consultant what to put on the W-4.
            - Sharing a filled-in W-4 from another consultant as an example.
            **Social Security**
            - Promising an exemption to make an offer look better.
            - Quoting rates from memory.
            **Medicare**
            - Confusing Medicare tax with health insurance.
            - Telling consultants their rates without checking with payroll.
            **FICA**
            - Thinking FICA is a third tax in addition to Social Security and Medicare.
            - Promising FICA exemption.
            **FUTA**
            - Telling a consultant FUTA is deducted from their pay.
            - Mixing up unemployment benefits with OPT unemployment days.
            **SUTA/SUI**
            - Using the client's headquarters as the work state for a remote consultant.
            - Not reporting a consultant's move.
            **W-2**
            - Confusing the form with the employment type.
            - Promising a delivery date for W-2 forms.
            **EIN**
            - Confusing the EIN with the E-Verify number.
            - Sharing the EIN casually with unknown contacts.
            TEXT],
        ['kind' => 'note', 'heading' => 'Sources & Review', 'body' => <<<'TEXT'
            The company training documents explain W2, 1099 and Corp-to-Corp arrangements and the Form W-2, but they do not cover gross pay, net pay, pay periods, federal income tax beyond the annual tax return, withholding, Form W-4, Social Security tax, Medicare tax, FICA, FUTA, state unemployment tax or the Employer Identification Number. These topics are based on published IRS and government guidance and need review and approval by payroll and the training manager.
            TEXT],
        ['kind' => 'takeaway', 'heading' => 'Key Takeaway', 'body' => <<<'TEXT'
            Speak about rates as gross pay, know what is withheld from pay and what the employer pays, and refer every calculation, exemption and tax question to payroll.
            TEXT],
    ],
];
