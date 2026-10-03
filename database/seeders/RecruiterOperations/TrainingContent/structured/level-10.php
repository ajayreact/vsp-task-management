<?php

/*
 * Redesigned Level 10 lessons. English follows the existing source-aligned
 * scenario and the OPT calling script in the Calling script and Practice
 * document; Telugu is its translation and ships as "needs review".
 */

return [
    'level' => 10,
    'lessons' => [
        'Scenario: Calling' => [
            'en' => [
                [
                    'kind' => 'objective',
                    'heading' => 'Learning Objective',
                    'body' => 'Practise a complete screening call, from introduction to professional closing, using the call structure from Level 7.',
                ],
                [
                    'kind' => 'content',
                    'heading' => 'How to Use This Scenario',
                    'body' => <<<'TEXT'
                    1. Work in pairs. One person plays the recruiter and the other plays the candidate.
                    2. The candidate uses the candidate card below.
                    3. Run the call for 15 to 20 minutes.
                    4. Review the call with the reflection questions, then swap roles.
                    TEXT,
                ],
                [
                    'kind' => 'reference',
                    'heading' => 'The Requirement',
                    'body' => <<<'TEXT'
                    | Detail | Requirement |
                    | --- | --- |
                    | Role | Java Developer |
                    | Client | Banking client |
                    | Location | Charlotte, North Carolina, hybrid three days a week |
                    | Type | 12-month W2 contract |
                    | Start | In two weeks |
                    | Required | Java with three or more years, Spring Boot, REST services, SQL |
                    | Preferred | Kafka, AWS, banking experience |
                    | Work authorization | OPT and STEM OPT candidates accepted |
                    TEXT,
                ],
                [
                    'kind' => 'reference',
                    'heading' => 'Candidate Card',
                    'body' => <<<'TEXT'
                    Only the person playing the candidate reads this card.
                    | Detail | Candidate |
                    | --- | --- |
                    | Name | Rahul |
                    | Location | Columbus, Ohio, staying with a friend |
                    | Education | MS in Computer Science, program ended in December |
                    | Experience | Two years as a Java developer in India, and a six-month Java internship in the U.S. |
                    | Skills | Java, Spring Boot, REST, MySQL. Studied Kafka in a course, not used at work |
                    | Work authorization | OPT, EAD valid from February 1 this year to January 31 next year |
                    | Availability | Can start in two weeks. Family event abroad in four months, for ten days |
                    | Rate | Expects slightly more than the approved range |
                    | Concerns | Worried about relocating without a car. Shares a noisy apartment |
                    TEXT,
                ],
                [
                    'kind' => 'reference',
                    'heading' => 'What the Recruiter Should Cover',
                    'body' => <<<'TEXT'
                    - Introduction, permission and the approved company introduction.
                    - The opportunity, current location and relocation.
                    - Education, graduation, IT experience and technical stack, including Kafka depth.
                    - Visa status and EAD dates, using approved questions only.
                    - Availability and planned absences.
                    - Rate, within the approved range.
                    - Webcam and interview environment.
                    - Interest confirmation, next steps and a professional closing.
                    TEXT,
                ],
                [
                    'kind' => 'example',
                    'heading' => 'Closing from the Company Calling Script',
                    'body' => 'Send the company details by email, ask for an updated resume, ask for referrals, ask the notice period, and ask when the candidate is available to talk with your sales manager. End with: Thank you, have a nice day.',
                ],
                [
                    'kind' => 'note',
                    'heading' => 'Company-Specific Process',
                    'body' => 'Describe the training and placement program, accommodation, offer letter, referral bonus and sponsorship only in the wording HR has approved. Verify with HR or authorized personnel.',
                ],
                [
                    'kind' => 'practice',
                    'heading' => 'Reflection Questions',
                    'body' => <<<'TEXT'
                    1. Did you ask for permission at the start?
                    2. Did you find out about the planned trip? How?
                    3. How did you handle the rate expectation without making a promise?
                    4. How did you discuss relocation realistically?
                    5. Did you summarise and agree on clear next steps?
                    TEXT,
                ],
                [
                    'kind' => 'takeaway',
                    'heading' => 'Key Takeaway',
                    'body' => 'Complete calls are a skill. Each practice call makes the next real call clearer and more confident.',
                ],
            ],
            'te' => [
                [
                    'kind' => 'objective',
                    'heading' => 'నేర్చుకునే లక్ష్యం',
                    'body' => 'Level 7 లో నేర్చుకున్న call structure ని ఉపయోగించి, introduction నుండి professional closing వరకు పూర్తి screening call ని practice చేయండి.',
                ],
                [
                    'kind' => 'content',
                    'heading' => 'ఈ Scenario ని ఎలా ఉపయోగించాలి',
                    'body' => <<<'TEXT'
                    1. ఇద్దరు కలిసి చేయండి. ఒకరు Recruiter గా, మరొకరు Candidate గా ఉంటారు.
                    2. Candidate కింద ఉన్న candidate card ని ఉపయోగిస్తారు.
                    3. Call ని 15 నుండి 20 నిమిషాలు నడపండి.
                    4. Reflection questions తో call ని review చేసి, తర్వాత పాత్రలు మార్చుకోండి.
                    TEXT,
                ],
                [
                    'kind' => 'reference',
                    'heading' => 'Requirement',
                    'body' => <<<'TEXT'
                    | వివరం | Requirement |
                    | --- | --- |
                    | Role | Java Developer |
                    | Client | Banking client |
                    | Location | Charlotte, North Carolina, వారానికి మూడు రోజులు hybrid |
                    | Type | 12-month W2 contract |
                    | Start | రెండు వారాల్లో |
                    | Required | మూడు లేదా అంతకంటే ఎక్కువ years Java, Spring Boot, REST services, SQL |
                    | Preferred | Kafka, AWS, banking experience |
                    | Work authorization | OPT మరియు STEM OPT Candidates ని accept చేస్తారు |
                    TEXT,
                ],
                [
                    'kind' => 'reference',
                    'heading' => 'Candidate Card',
                    'body' => <<<'TEXT'
                    Candidate పాత్ర చేసే వారు మాత్రమే ఈ card చదవాలి.
                    | వివరం | Candidate |
                    | --- | --- |
                    | పేరు | Rahul |
                    | Location | Columbus, Ohio, ఒక friend దగ్గర ఉంటున్నారు |
                    | Education | MS in Computer Science, program December లో ముగిసింది |
                    | Experience | India లో రెండు సంవత్సరాలు Java developer గా, U.S. లో ఆరు నెలల Java internship |
                    | Skills | Java, Spring Boot, REST, MySQL. Kafka ని ఒక course లో నేర్చుకున్నారు, పనిలో వాడలేదు |
                    | Work authorization | OPT, EAD ఈ సంవత్సరం February 1 నుండి వచ్చే సంవత్సరం January 31 వరకు valid |
                    | Availability | రెండు వారాల్లో join కాగలరు. నాలుగు నెలల తర్వాత పది రోజులు విదేశంలో family event ఉంది |
                    | Rate | Approved range కంటే కొంచెం ఎక్కువ ఆశిస్తున్నారు |
                    | ఆందోళనలు | Car లేకుండా relocate అవ్వడం గురించి ఆందోళన. శబ్దం ఉండే apartment లో share చేసుకుంటున్నారు |
                    TEXT,
                ],
                [
                    'kind' => 'reference',
                    'heading' => 'Recruiter Cover చేయాల్సిన విషయాలు',
                    'body' => <<<'TEXT'
                    - Introduction, permission, approved company introduction.
                    - Opportunity, ప్రస్తుత location, relocation.
                    - Education, graduation, IT experience, technical stack, Kafka లో ఎంత లోతు ఉందో కూడా.
                    - Visa status మరియు EAD dates, approved questions మాత్రమే ఉపయోగించి.
                    - Availability మరియు ముందే plan చేసుకున్న సెలవులు.
                    - Rate, approved range లోపలే.
                    - Webcam మరియు interview environment.
                    - ఆసక్తిని confirm చేయడం, next steps, professional closing.
                    TEXT,
                ],
                [
                    'kind' => 'example',
                    'heading' => 'Company Calling Script ప్రకారం Closing',
                    'body' => 'Company details ని email ద్వారా పంపండి, updated Resume అడగండి, referrals అడగండి, notice period అడగండి, మీ sales manager తో మాట్లాడటానికి Candidate ఎప్పుడు available గా ఉంటారో అడగండి. చివరగా: Thank you, have a nice day అని ముగించండి.',
                ],
                [
                    'kind' => 'note',
                    'heading' => 'Company కి ప్రత్యేకమైన Process',
                    'body' => 'Training మరియు placement program, accommodation, offer letter, referral bonus, sponsorship గురించి HR approve చేసిన మాటల్లో మాత్రమే చెప్పండి. HR లేదా authorized personnel తో verify చేయండి.',
                ],
                [
                    'kind' => 'practice',
                    'heading' => 'ఆలోచించాల్సిన ప్రశ్నలు',
                    'body' => <<<'TEXT'
                    1. మొదట్లో permission అడిగారా?
                    2. Plan చేసుకున్న trip గురించి తెలుసుకున్నారా? ఎలా?
                    3. హామీ ఇవ్వకుండా rate expectation ని ఎలా handle చేశారు?
                    4. Relocation గురించి వాస్తవికంగా ఎలా మాట్లాడారు?
                    5. Summary చెప్పి, స్పష్టమైన next steps పై ఒప్పందం చేసుకున్నారా?
                    TEXT,
                ],
                [
                    'kind' => 'takeaway',
                    'heading' => 'ముఖ్యమైన విషయం',
                    'body' => 'పూర్తి calls చేయడం ఒక skill. ప్రతి practice call తర్వాతి నిజమైన call ని మరింత స్పష్టంగా, నమ్మకంగా చేస్తుంది.',
                ],
            ],
        ],
    ],
];
