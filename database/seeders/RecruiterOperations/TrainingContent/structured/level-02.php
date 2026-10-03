<?php

/*
 * Redesigned Level 2 lessons. English follows the existing source-aligned
 * lesson (OPT Recruiter Training Material, US Visa Types); Telugu is its
 * translation and ships as "needs review". Awareness only, never legal advice.
 */

return [
    'level' => 2,
    'lessons' => [
        'OPT' => [
            'en' => [
                [
                    'kind' => 'objective',
                    'heading' => 'Learning Objective',
                    'body' => 'Understand Optional Practical Training, the work authorization behind most OPT recruiting, and the key dates a recruiter must track.',
                ],
                [
                    'kind' => 'content',
                    'heading' => 'What You Need to Know',
                    'body' => <<<'TEXT'
                    - OPT is temporary employment authorization for F-1 students.
                    - The work must be directly related to the student's major field of study.
                    - Under current rules, a student can generally receive up to 12 months of OPT per higher degree level.
                    - Pre-completion OPT is used before the program ends. Post-completion OPT is used after it ends. Most of your candidates use post-completion OPT.
                    TEXT,
                ],
                [
                    'kind' => 'content',
                    'heading' => 'How a Student Gets OPT',
                    'body' => <<<'TEXT'
                    1. The DSO recommends OPT in SEVIS and issues an updated I-20.
                    2. The student files Form I-765 with USCIS.
                    3. If approved, the student receives an EAD card.
                    4. The student may start work only on or after the EAD start date, and only with the approved card.
                    TEXT,
                ],
                [
                    'kind' => 'reference',
                    'heading' => 'Key Dates to Track',
                    'body' => <<<'TEXT'
                    | Date | Where it comes from |
                    | --- | --- |
                    | Program end date | I-20 |
                    | EAD start date | EAD card |
                    | EAD end date | EAD card |
                    | Employment start date | The new employer |
                    TEXT,
                ],
                [
                    'kind' => 'content',
                    'heading' => 'Reading the Company Handout Correctly',
                    'body' => <<<'TEXT'
                    The OPT Recruiter Training Material says OPT is valid for 12 months and can be extended by up to 24 months, for a total of 36 months, and that MBA students have OPT for 12 months only.
                    - The 24-month extension is STEM OPT. Only graduates of qualifying STEM degree programs can apply for it.
                    - So 36 months is the maximum for a STEM graduate, not the normal case.
                    - Most MBA programs are not STEM programs, but some are STEM designated. Do not assume either way.
                    TEXT,
                ],
                [
                    'kind' => 'content',
                    'heading' => 'Unemployment and Reporting',
                    'body' => <<<'TEXT'
                    - During post-completion OPT, students are generally allowed a limited number of unemployment days, commonly cited as 90 days.
                    - A candidate close to that limit may be under pressure. Be aware of it, but never advise on it.
                    - Reporting employment details to the school or the SEVP portal is the candidate's responsibility, guided by the DSO.
                    TEXT,
                ],
                [
                    'kind' => 'example',
                    'heading' => 'Recruiter Example',
                    'body' => 'A candidate shares that her EAD is valid from June 1 to May 31 next year. She graduated in December. You record both dates, note that she can start work from June 1, and tell HR that her EAD ends next May, so they can plan for STEM OPT if she is eligible.',
                ],
                [
                    'kind' => 'mistakes',
                    'heading' => 'Common Mistakes',
                    'body' => <<<'TEXT'
                    - Assuming an approved application means the candidate can start immediately.
                    - Calculating unemployment days for the candidate.
                    - Ignoring the EAD end date during placement.
                    - Deciding yourself whether a job relates to the degree, instead of escalating as company policy requires.
                    TEXT,
                ],
                [
                    'kind' => 'practice',
                    'heading' => 'Check Your Knowledge',
                    'body' => <<<'TEXT'
                    1. Which document gives you the EAD start and end dates?
                    2. A candidate's EAD starts on July 15, and the client wants a July 1 start. What do you tell the client?
                    3. Is 36 months of OPT normal for every candidate? Why or why not?
                    4. A candidate asks you how many unemployment days she has left. What do you do?
                    TEXT,
                ],
                [
                    'kind' => 'takeaway',
                    'heading' => 'Key Takeaway',
                    'body' => 'OPT is the foundation of your work. Track the dates carefully, respect the rules, and refer status questions to the DSO and HR.',
                ],
            ],
            'te' => [
                [
                    'kind' => 'objective',
                    'heading' => 'నేర్చుకునే లక్ష్యం',
                    'body' => 'ఎక్కువ OPT recruiting కి ఆధారమైన work authorization అయిన Optional Practical Training ను, మరియు Recruiter తప్పకుండా track చేయాల్సిన ముఖ్యమైన dates ను అర్థం చేసుకోండి.',
                ],
                [
                    'kind' => 'content',
                    'heading' => 'మీరు తెలుసుకోవాల్సినది',
                    'body' => <<<'TEXT'
                    - OPT అంటే Optional Practical Training. ఇది F-1 students కి ఇచ్చే temporary employment authorization.
                    - చేసే పని student యొక్క major field of study కి నేరుగా related గా ఉండాలి.
                    - ప్రస్తుత rules ప్రకారం, సాధారణంగా ఒక్కో higher degree level కి గరిష్టంగా 12 months OPT వస్తుంది.
                    - Program పూర్తి కాకముందు వాడేది Pre-completion OPT. Program పూర్తయిన తర్వాత వాడేది Post-completion OPT. మీ Candidates లో ఎక్కువ మంది Post-completion OPT లో ఉంటారు.
                    TEXT,
                ],
                [
                    'kind' => 'content',
                    'heading' => 'Student కి OPT ఎలా వస్తుంది',
                    'body' => <<<'TEXT'
                    1. DSO, SEVIS లో OPT ని recommend చేసి updated I-20 ఇస్తారు.
                    2. Student, USCIS కి Form I-765 file చేస్తారు.
                    3. Approve అయితే, student కి EAD card వస్తుంది.
                    4. EAD start date రోజు లేదా ఆ తర్వాత మాత్రమే, approved card చేతిలో ఉన్నప్పుడే student పని మొదలుపెట్టవచ్చు.
                    TEXT,
                ],
                [
                    'kind' => 'reference',
                    'heading' => 'Track చేయాల్సిన ముఖ్యమైన Dates',
                    'body' => <<<'TEXT'
                    | Date | ఎక్కడ నుండి వస్తుంది |
                    | --- | --- |
                    | Program end date | I-20 |
                    | EAD start date | EAD card |
                    | EAD end date | EAD card |
                    | Employment start date | కొత్త employer |
                    TEXT,
                ],
                [
                    'kind' => 'content',
                    'heading' => 'Company Handout ని సరిగ్గా అర్థం చేసుకోవడం',
                    'body' => <<<'TEXT'
                    OPT Recruiter Training Material ప్రకారం OPT validity 12 months, దాన్ని గరిష్టంగా 24 months extend చేయవచ్చు, మొత్తం 36 months. MBA students కి 12 months మాత్రమే OPT ఉంటుంది అని కూడా ఉంది.
                    - ఆ 24-month extension అంటే STEM OPT. Qualifying STEM degree programs పూర్తి చేసిన వారు మాత్రమే దానికి apply చేయగలరు.
                    - కాబట్టి 36 months అనేది STEM graduate కి గరిష్ట పరిమితి, అందరికీ వర్తించే సాధారణ case కాదు.
                    - చాలా MBA programs STEM కాదు, కానీ కొన్ని STEM designated గా ఉంటాయి. ఏ వైపూ assume చేయవద్దు.
                    TEXT,
                ],
                [
                    'kind' => 'content',
                    'heading' => 'Unemployment మరియు Reporting',
                    'body' => <<<'TEXT'
                    - Post-completion OPT సమయంలో students కి సాధారణంగా పరిమిత unemployment days మాత్రమే అనుమతి ఉంటుంది. ఇది సాధారణంగా 90 days అని చెబుతారు.
                    - ఆ limit దగ్గరలో ఉన్న Candidate ఒత్తిడిలో ఉండవచ్చు. ఈ విషయం మీకు తెలిసి ఉండాలి, కానీ దాని గురించి ఎప్పుడూ advice ఇవ్వవద్దు.
                    - Employment details ని school కి లేదా SEVP portal కి report చేయడం Candidate బాధ్యత. దీనికి DSO guide చేస్తారు.
                    TEXT,
                ],
                [
                    'kind' => 'example',
                    'heading' => 'Recruiter ఉదాహరణ',
                    'body' => 'ఒక Candidate తన EAD June 1 నుండి వచ్చే సంవత్సరం May 31 వరకు valid అని చెప్పారు. ఆమె December లో graduate అయ్యారు. మీరు రెండు dates ని record చేస్తారు, June 1 నుండి పని మొదలుపెట్టవచ్చని note చేస్తారు, ఆమె EAD వచ్చే May లో ముగుస్తుందని HR కి తెలియజేస్తారు. దీనివల్ల ఆమె eligible అయితే STEM OPT కోసం HR ముందుగానే plan చేయగలరు.',
                ],
                [
                    'kind' => 'mistakes',
                    'heading' => 'సాధారణ తప్పులు',
                    'body' => <<<'TEXT'
                    - Application approve అయిన వెంటనే Candidate పని మొదలుపెట్టవచ్చని అనుకోవడం.
                    - Candidate కోసం unemployment days ని మీరే లెక్కించడం.
                    - Placement సమయంలో EAD end date ని పట్టించుకోకపోవడం.
                    - Job, degree కి related అవునా కాదా అని company policy ప్రకారం escalate చేయకుండా మీరే నిర్ణయించడం.
                    TEXT,
                ],
                [
                    'kind' => 'practice',
                    'heading' => 'మీ అవగాహనను పరీక్షించుకోండి',
                    'body' => <<<'TEXT'
                    1. EAD start date మరియు end date ఏ document నుండి తెలుస్తాయి?
                    2. ఒక Candidate EAD July 15 న మొదలవుతుంది, కానీ Client July 1 start కావాలంటున్నారు. Client కి ఏమి చెబుతారు?
                    3. 36 months OPT ప్రతి Candidate కి సాధారణమేనా? ఎందుకు?
                    4. తనకు ఇంకా ఎన్ని unemployment days మిగిలి ఉన్నాయని ఒక Candidate అడిగితే మీరు ఏమి చేస్తారు?
                    TEXT,
                ],
                [
                    'kind' => 'takeaway',
                    'heading' => 'ముఖ్యమైన విషయం',
                    'body' => 'OPT మీ పనికి పునాది. Dates ని జాగ్రత్తగా track చేయండి, rules ని గౌరవించండి, status కి సంబంధించిన ప్రశ్నలను DSO మరియు HR కి refer చేయండి.',
                ],
            ],
        ],
    ],
];
