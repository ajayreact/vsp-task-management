<?php

/*
 * Redesigned Level 4 lessons. English follows the existing source-aligned
 * lesson (Job Description Analysis_US_IT_Recruitment_Training); Telugu is its
 * translation and ships as "needs review".
 */

return [
    'level' => 4,
    'lessons' => [
        'Reading Job Descriptions' => [
            'en' => [
                [
                    'kind' => 'objective',
                    'heading' => 'Learning Objective',
                    'body' => 'Learn how U.S. job descriptions are structured and how to read them quickly and accurately.',
                ],
                [
                    'kind' => 'content',
                    'heading' => 'What You Need to Know',
                    'body' => <<<'TEXT'
                    The Job Description Analysis training defines a job description, or JD, as a written statement of the role, its expectations and its qualifications. Your goal is to turn the JD into searchable keywords and objective screening criteria.
                    Staffing requirements often add a vendor header with location, duration, rate, engagement type, interview process and submission instructions. Read both the header and the description.
                    TEXT,
                ],
                [
                    'kind' => 'reference',
                    'heading' => 'The Eight Parts of a JD',
                    'body' => <<<'TEXT'
                    1. Job title
                    2. Job summary
                    3. Company description
                    4. Requirements or qualifications
                    5. Experience
                    6. Skills
                    7. Responsibilities
                    8. Pay or benefits
                    TEXT,
                ],
                [
                    'kind' => 'content',
                    'heading' => 'Read in Four Passes',
                    'body' => <<<'TEXT'
                    1. **First pass.** Read the whole JD quickly. What will this person actually do every day?
                    2. **Second pass.** Note the title, location and work mode, duration, start date and must-have skills.
                    3. **Third pass.** Look for hidden requirements: a domain such as banking or healthcare, a certification, a specific tool version, or a phrase like local candidates only.
                    4. **Final check.** Re-read the submission instructions and the deadline.
                    TEXT,
                ],
                [
                    'kind' => 'reference',
                    'heading' => 'Reading the Language of a JD',
                    'body' => <<<'TEXT'
                    | Words in the JD | What they usually mean |
                    | --- | --- |
                    | Required, must have, minimum | The client will usually reject candidates without it |
                    | Preferred, nice to have, plus, bonus | Helpful, but not essential |
                    | Strong, expert, hands-on | Real depth of experience, not just familiarity |
                    | Exposure to, familiarity with | A lower level of depth is acceptable |
                    | Years of experience | A guideline or a strict filter. Ask the vendor if unclear |
                    TEXT,
                ],
                [
                    'kind' => 'content',
                    'heading' => 'Spotting Problems in a JD',
                    'body' => <<<'TEXT'
                    - A long list of unrelated technologies may mean the JD was copied from older roles. Ask which skills matter most.
                    - Conflicting information, such as remote in one place and onsite in another, must be clarified before sourcing.
                    - A very low rate for a senior skill set may make the requirement hard to fill. Flag it to your lead.
                    TEXT,
                ],
                [
                    'kind' => 'example',
                    'heading' => 'Recruiter Example',
                    'body' => 'A Java Developer JD mentions Kafka streaming pipelines in the responsibilities, but lists Kafka only as preferred. You ask the vendor whether Kafka is essential. The vendor confirms it is preferred, but candidates with Kafka will be prioritised. You note this in your requirement summary.',
                ],
                [
                    'kind' => 'mistakes',
                    'heading' => 'Common Mistakes',
                    'body' => <<<'TEXT'
                    - Reading only the vendor header and not the description.
                    - Missing a hidden domain or certification requirement.
                    - Assuming years of experience are flexible without asking.
                    TEXT,
                ],
                [
                    'kind' => 'practice',
                    'heading' => 'Practice',
                    'body' => <<<'TEXT'
                    1. Name the eight parts of a JD from the training.
                    2. A JD lists AWS as nice to have. Should you reject a candidate without AWS?
                    3. The header says remote, but the description says onsite three days a week. What do you do?
                    4. What do you check in the final pass?
                    TEXT,
                ],
                [
                    'kind' => 'takeaway',
                    'heading' => 'Key Takeaway',
                    'body' => 'Read job descriptions in deliberate passes and pay attention to the language. Small words like required and preferred change everything.',
                ],
            ],
            'te' => [
                [
                    'kind' => 'objective',
                    'heading' => 'నేర్చుకునే లక్ష్యం',
                    'body' => 'U.S. Job Descriptions ఎలా ఉంటాయో, వాటిని వేగంగా మరియు ఖచ్చితంగా ఎలా చదవాలో నేర్చుకోండి.',
                ],
                [
                    'kind' => 'content',
                    'heading' => 'మీరు తెలుసుకోవాల్సినది',
                    'body' => <<<'TEXT'
                    Job Description Analysis training ప్రకారం Job Description, అంటే JD, అనేది role, దాని expectations, qualifications ను వివరించే written statement. JD ని searchable keywords గా, objective screening criteria గా మార్చడమే మీ లక్ష్యం.
                    Staffing Requirements లో తరచుగా vendor header కూడా ఉంటుంది. అందులో location, duration, rate, engagement type, interview process, submission instructions ఉంటాయి. Header ని, description ని రెండింటినీ చదవండి.
                    TEXT,
                ],
                [
                    'kind' => 'reference',
                    'heading' => 'JD లోని ఎనిమిది భాగాలు',
                    'body' => <<<'TEXT'
                    1. Job title
                    2. Job summary
                    3. Company description
                    4. Requirements లేదా qualifications
                    5. Experience
                    6. Skills
                    7. Responsibilities
                    8. Pay లేదా benefits
                    TEXT,
                ],
                [
                    'kind' => 'content',
                    'heading' => 'నాలుగు సార్లు చదవండి',
                    'body' => <<<'TEXT'
                    1. **మొదటిసారి.** JD మొత్తాన్ని త్వరగా చదవండి. ఈ వ్యక్తి ప్రతిరోజూ నిజంగా ఏ పని చేస్తారు?
                    2. **రెండవసారి.** Title, location మరియు work mode, duration, start date, must-have skills ని note చేయండి.
                    3. **మూడవసారి.** దాగి ఉన్న requirements కోసం చూడండి: banking లేదా healthcare వంటి domain, certification, ఒక specific tool version, లేదా local candidates only వంటి మాట.
                    4. **చివరి check.** Submission instructions ని, deadline ని మళ్ళీ చదవండి.
                    TEXT,
                ],
                [
                    'kind' => 'reference',
                    'heading' => 'JD లోని పదాల అర్థం',
                    'body' => <<<'TEXT'
                    | JD లోని పదాలు | సాధారణంగా అర్థం |
                    | --- | --- |
                    | Required, must have, minimum | ఇది లేని Candidates ని Client సాధారణంగా reject చేస్తారు |
                    | Preferred, nice to have, plus, bonus | ఉపయోగపడుతుంది, కానీ తప్పనిసరి కాదు |
                    | Strong, expert, hands-on | కేవలం పరిచయం కాదు, లోతైన experience కావాలి |
                    | Exposure to, familiarity with | తక్కువ లోతు ఉన్నా సరిపోతుంది |
                    | Years of experience | ఇది guideline కావచ్చు లేదా strict filter కావచ్చు. స్పష్టంగా లేకపోతే vendor ని అడగండి |
                    TEXT,
                ],
                [
                    'kind' => 'content',
                    'heading' => 'JD లోని సమస్యలను గుర్తించడం',
                    'body' => <<<'TEXT'
                    - సంబంధం లేని technologies పెద్ద list గా ఉంటే, JD పాత roles నుండి copy చేసి ఉండవచ్చు. ఏ skills ముఖ్యమో అడగండి.
                    - ఒకచోట remote, మరోచోట onsite వంటి విరుద్ధమైన సమాచారం ఉంటే, sourcing కి ముందే clarify చేసుకోవాలి.
                    - Senior skill set కి చాలా తక్కువ rate ఉంటే, ఆ Requirement fill చేయడం కష్టం కావచ్చు. మీ lead కి తెలియజేయండి.
                    TEXT,
                ],
                [
                    'kind' => 'example',
                    'heading' => 'Recruiter ఉదాహరణ',
                    'body' => 'ఒక Java Developer JD లో responsibilities లో Kafka streaming pipelines గురించి ఉంది, కానీ Kafka ని preferred గా మాత్రమే చూపించారు. Kafka తప్పనిసరా అని మీరు vendor ని అడుగుతారు. అది preferred మాత్రమే, కానీ Kafka ఉన్న Candidates కి priority ఇస్తారని vendor confirm చేస్తారు. ఈ విషయాన్ని మీరు మీ requirement summary లో note చేస్తారు.',
                ],
                [
                    'kind' => 'mistakes',
                    'heading' => 'సాధారణ తప్పులు',
                    'body' => <<<'TEXT'
                    - Vendor header మాత్రమే చదివి description ని వదిలేయడం.
                    - దాగి ఉన్న domain లేదా certification requirement ని miss చేయడం.
                    - అడగకుండానే years of experience flexible అని అనుకోవడం.
                    TEXT,
                ],
                [
                    'kind' => 'practice',
                    'heading' => 'సాధన',
                    'body' => <<<'TEXT'
                    1. Training ప్రకారం JD లోని ఎనిమిది భాగాలు ఏమిటి?
                    2. ఒక JD లో AWS ని nice to have అని రాశారు. AWS లేని Candidate ని reject చేయాలా?
                    3. Header లో remote అని ఉంది, కానీ description లో వారానికి మూడు రోజులు onsite అని ఉంది. మీరు ఏమి చేస్తారు?
                    4. చివరి check లో మీరు ఏమి చూస్తారు?
                    TEXT,
                ],
                [
                    'kind' => 'takeaway',
                    'heading' => 'ముఖ్యమైన విషయం',
                    'body' => 'Job Descriptions ని ప్రణాళికతో దశలవారీగా చదవండి, వాడిన పదాలపై దృష్టి పెట్టండి. Required, preferred వంటి చిన్న పదాలే మొత్తం అర్థాన్ని మార్చేస్తాయి.',
                ],
            ],
        ],
    ],
];
