<?php

/*
 * Redesigned Level 7 lessons. English follows the existing source-aligned
 * lesson and the Day 6 sixty-second cold call in the Calling script and
 * Practice document; Telugu is its translation and ships as "needs review".
 */

return [
    'level' => 7,
    'lessons' => [
        '60-Second Cold Call' => [
            'en' => [
                [
                    'kind' => 'objective',
                    'heading' => 'Learning Objective',
                    'body' => 'Deliver a clear, natural 60-second cold call that introduces you and the opportunity, and ends with a question and a next step.',
                ],
                [
                    'kind' => 'content',
                    'heading' => 'What You Need to Know',
                    'body' => <<<'TEXT'
                    - A cold call is a call to a candidate who is not expecting you.
                    - Most candidates decide within a minute whether to continue.
                    - A good cold call is short, specific and honest. It is an invitation to a conversation, not a speech.
                    TEXT,
                ],
                [
                    'kind' => 'content',
                    'heading' => 'Call Preparation',
                    'body' => <<<'TEXT'
                    - Prepare a one-line role summary: the role, the client type, the location and the work mode.
                    - Pick one detail from the candidate's profile that matches the role.
                    - Decide the next step you will offer: a screening call now, or a calendar invite for later.
                    TEXT,
                ],
                [
                    'kind' => 'reference',
                    'heading' => 'Call Flow',
                    'body' => <<<'TEXT'
                    | Time | What you do |
                    | --- | --- |
                    | 0 to 10 seconds | Greeting, your name and company, and permission |
                    | 10 to 30 seconds | The role, the location and one interesting detail |
                    | 30 to 45 seconds | One detail from their profile that matches |
                    | 45 to 60 seconds | A question that invites them to talk |
                    TEXT,
                ],
                [
                    'kind' => 'example',
                    'heading' => 'Example Conversation from the Company Calling Script',
                    'body' => <<<'TEXT'
                    **Recruiter:** Hi Priya, my name is Anita from our company. I was reviewing your profile on LinkedIn and was really impressed by your background in data engineering. I know your time is valuable, so I will keep this brief. Do you have sixty seconds to talk about an active project opportunity?
                    **Candidate:** Sure. What is it about?
                    **Recruiter:** Great. We specialise in placing F-1 international graduates with clients across the U.S. Right now we are building a developer pipeline for an upcoming project that fits your skills in Python and Spark. Are you currently open to new opportunities?
                    **Candidate:** Yes, I am on OPT right now and looking.
                    **Recruiter:** Perfect. I would like to walk you through our placement process and check your timelines. Do you have five minutes now, or should I send a calendar invite for later today?
                    TEXT,
                ],
                [
                    'kind' => 'content',
                    'heading' => 'After the 60 Seconds',
                    'body' => <<<'TEXT'
                    - Interested: move to the initial screening, or schedule it.
                    - Not interested now: ask whether you can stay in touch, and what roles they prefer.
                    - Already placed: congratulate them and ask whether they know anyone who might be interested.
                    TEXT,
                ],
                [
                    'kind' => 'note',
                    'heading' => 'What Not to Say',
                    'body' => <<<'TEXT'
                    The original script says the company is completely E-Verified, has a legal team that handles STEM OPT extensions and H-1B sponsorships without any hiccups, and works with tier-1 clients. Do not say these lines as written.
                    No employer can promise that STEM OPT or H-1B filings will succeed. If a candidate asks about E-Verify or sponsorship, share only the facts HR has approved. Verify with HR or authorized personnel.
                    TEXT,
                ],
                [
                    'kind' => 'mistakes',
                    'heading' => 'Common Mistakes',
                    'body' => <<<'TEXT'
                    - Talking for three minutes without a pause.
                    - Using the same generic pitch for everyone.
                    - Putting pressure on the candidate, such as saying they must decide today.
                    - Asking for documents or personal details on the first call.
                    - Promising interviews, offers or sponsorship.
                    - Forgetting to agree on the next step.
                    TEXT,
                ],
                [
                    'kind' => 'practice',
                    'heading' => 'Practice',
                    'body' => <<<'TEXT'
                    1. What are the four parts of the 60-second call?
                    2. Write a one-line role summary for a hybrid Java Developer role in Charlotte, NC.
                    3. The candidate says they are busy right now. What do you do?
                    4. The candidate asks whether you will sponsor their H-1B. How do you answer on a cold call?
                    TEXT,
                ],
                [
                    'kind' => 'takeaway',
                    'heading' => 'Key Takeaway',
                    'body' => 'A great cold call is short, specific and honest, and it always ends with a question and a next step.',
                ],
            ],
            'te' => [
                [
                    'kind' => 'objective',
                    'heading' => 'నేర్చుకునే లక్ష్యం',
                    'body' => 'మిమ్మల్ని, opportunity ని పరిచయం చేసి, ఒక ప్రశ్నతో మరియు next step తో ముగిసే స్పష్టమైన, సహజమైన 60-second cold call చేయడం నేర్చుకోండి.',
                ],
                [
                    'kind' => 'content',
                    'heading' => 'మీరు తెలుసుకోవాల్సినది',
                    'body' => <<<'TEXT'
                    - Cold call అంటే మీ call ని ఊహించని Candidate కి చేసే call.
                    - మాట్లాడటం కొనసాగించాలా వద్దా అని చాలా మంది Candidates ఒక్క నిమిషంలోనే నిర్ణయించుకుంటారు.
                    - మంచి cold call చిన్నగా, స్పష్టంగా, నిజాయితీగా ఉంటుంది. ఇది ప్రసంగం కాదు, సంభాషణకు ఆహ్వానం.
                    TEXT,
                ],
                [
                    'kind' => 'content',
                    'heading' => 'Call కి ముందు సిద్ధం కావడం',
                    'body' => <<<'TEXT'
                    - ఒక line role summary సిద్ధం చేసుకోండి: role, client type, location, work mode.
                    - Role కి match అయ్యే ఒక detail ని Candidate profile నుండి ఎంచుకోండి.
                    - మీరు ఇవ్వబోయే next step ని నిర్ణయించుకోండి: ఇప్పుడే screening call, లేదా తర్వాత కోసం calendar invite.
                    TEXT,
                ],
                [
                    'kind' => 'reference',
                    'heading' => 'Call సాగే క్రమం',
                    'body' => <<<'TEXT'
                    | సమయం | మీరు చేసేది |
                    | --- | --- |
                    | 0 నుండి 10 seconds | Greeting, మీ పేరు మరియు company, permission అడగడం |
                    | 10 నుండి 30 seconds | Role, location, ఒక ఆసక్తికరమైన detail |
                    | 30 నుండి 45 seconds | వారి profile లో match అయ్యే ఒక detail |
                    | 45 నుండి 60 seconds | వారిని మాట్లాడేలా చేసే ఒక ప్రశ్న |
                    TEXT,
                ],
                [
                    'kind' => 'example',
                    'heading' => 'Company Calling Script నుండి ఉదాహరణ సంభాషణ',
                    'body' => <<<'TEXT'
                    **Recruiter:** Hi Priya, నా పేరు Anita, మా company నుండి call చేస్తున్నాను. LinkedIn లో మీ profile చూశాను, data engineering లో మీ background నాకు చాలా నచ్చింది. మీ సమయం విలువైనదని నాకు తెలుసు, కాబట్టి క్లుప్తంగా చెబుతాను. ఒక active project opportunity గురించి మాట్లాడటానికి అరవై seconds సమయం ఉందా?
                    **Candidate:** Sure. దేని గురించి?
                    **Recruiter:** Great. మేము F-1 international graduates ని U.S. అంతటా ఉన్న clients దగ్గర place చేస్తాము. ప్రస్తుతం రాబోయే ఒక project కోసం developer pipeline తయారు చేస్తున్నాము, అది Python మరియు Spark లో మీ skills కి సరిపోతుంది. ప్రస్తుతం మీరు కొత్త opportunities కి open గా ఉన్నారా?
                    **Candidate:** అవును, ఇప్పుడు నేను OPT లో ఉన్నాను, job కోసం చూస్తున్నాను.
                    **Recruiter:** Perfect. మా placement process గురించి వివరించి, మీ timelines check చేయాలనుకుంటున్నాను. ఇప్పుడు ఐదు నిమిషాలు సమయం ఉందా, లేదా ఈరోజు తర్వాత కోసం calendar invite పంపమంటారా?
                    TEXT,
                ],
                [
                    'kind' => 'content',
                    'heading' => '60 Seconds తర్వాత',
                    'body' => <<<'TEXT'
                    - ఆసక్తి ఉంటే: initial screening కి వెళ్ళండి, లేదా దాన్ని schedule చేయండి.
                    - ఇప్పుడు ఆసక్తి లేకపోతే: contact లో ఉండవచ్చా అని, వారికి ఎలాంటి roles ఇష్టమో అడగండి.
                    - ఇప్పటికే placed అయి ఉంటే: అభినందించి, ఆసక్తి ఉన్న ఎవరైనా తెలుసా అని అడగండి.
                    TEXT,
                ],
                [
                    'kind' => 'note',
                    'heading' => 'చెప్పకూడని మాటలు',
                    'body' => <<<'TEXT'
                    Original script లో company పూర్తిగా E-Verified అని, STEM OPT extensions మరియు H-1B sponsorships ని ఎలాంటి ఇబ్బంది లేకుండా చూసే legal team ఉందని, tier-1 clients తో పని చేస్తామని ఉంది. ఈ మాటలను ఉన్నది ఉన్నట్టుగా చెప్పవద్దు.
                    STEM OPT లేదా H-1B filings తప్పకుండా success అవుతాయని ఏ employer కూడా హామీ ఇవ్వలేరు. Candidate E-Verify లేదా sponsorship గురించి అడిగితే, HR approve చేసిన facts మాత్రమే చెప్పండి. HR లేదా authorized personnel తో verify చేయండి.
                    TEXT,
                ],
                [
                    'kind' => 'mistakes',
                    'heading' => 'సాధారణ తప్పులు',
                    'body' => <<<'TEXT'
                    - ఆగకుండా మూడు నిమిషాలు మాట్లాడటం.
                    - అందరికీ ఒకే generic pitch వాడటం.
                    - ఈరోజే నిర్ణయించుకోవాలి అని చెప్పడం వంటి ఒత్తిడి పెట్టడం.
                    - మొదటి call లోనే documents లేదా personal details అడగడం.
                    - Interviews, offers లేదా sponsorship గురించి హామీ ఇవ్వడం.
                    - Next step ఏమిటో ఒప్పందం చేసుకోవడం మర్చిపోవడం.
                    TEXT,
                ],
                [
                    'kind' => 'practice',
                    'heading' => 'సాధన',
                    'body' => <<<'TEXT'
                    1. 60-second call లోని నాలుగు భాగాలు ఏమిటి?
                    2. Charlotte, NC లో hybrid Java Developer role కోసం ఒక line role summary రాయండి.
                    3. ఇప్పుడు busy గా ఉన్నానని Candidate చెబితే మీరు ఏమి చేస్తారు?
                    4. మీరు వారి H-1B sponsor చేస్తారా అని Candidate అడిగితే, cold call లో ఎలా జవాబు ఇస్తారు?
                    TEXT,
                ],
                [
                    'kind' => 'takeaway',
                    'heading' => 'ముఖ్యమైన విషయం',
                    'body' => 'మంచి cold call చిన్నగా, స్పష్టంగా, నిజాయితీగా ఉంటుంది. అది ఎప్పుడూ ఒక ప్రశ్నతో మరియు ఒక next step తో ముగుస్తుంది.',
                ],
            ],
        ],
    ],
];
