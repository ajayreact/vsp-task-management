<?php

/*
 * Redesigned Level 1 lessons. English follows the existing source-aligned
 * lesson (US Visa Types state code table, OPT Recruiter Training Material);
 * Telugu is its translation and ships as "needs review".
 */

$stateTable = <<<'TEXT'
| State | Code |
| --- | --- |
| Alabama | AL |
| Alaska | AK |
| Arizona | AZ |
| Arkansas | AR |
| California | CA |
| Colorado | CO |
| Connecticut | CT |
| Delaware | DE |
| Florida | FL |
| Georgia | GA |
| Hawaii | HI |
| Idaho | ID |
| Illinois | IL |
| Indiana | IN |
| Iowa | IA |
| Kansas | KS |
| Kentucky | KY |
| Louisiana | LA |
| Maine | ME |
| Maryland | MD |
| Massachusetts | MA |
| Michigan | MI |
| Minnesota | MN |
| Mississippi | MS |
| Missouri | MO |
| Montana | MT |
| Nebraska | NE |
| Nevada | NV |
| New Hampshire | NH |
| New Jersey | NJ |
| New Mexico | NM |
| New York | NY |
| North Carolina | NC |
| North Dakota | ND |
| Ohio | OH |
| Oklahoma | OK |
| Oregon | OR |
| Pennsylvania | PA |
| Rhode Island | RI |
| South Carolina | SC |
| South Dakota | SD |
| Tennessee | TN |
| Texas | TX |
| Utah | UT |
| Vermont | VT |
| Virginia | VA |
| Washington | WA |
| West Virginia | WV |
| Wisconsin | WI |
| Wyoming | WY |
| District of Columbia | DC |
TEXT;

return [
    'level' => 1,
    'lessons' => [
        'State Abbreviations & Codes' => [
            'en' => [
                [
                    'kind' => 'objective',
                    'heading' => 'Learning Objective',
                    'body' => 'Learn the official two-letter postal codes for all 50 states and Washington, D.C., so you can read job descriptions, resumes and addresses without hesitation.',
                ],
                [
                    'kind' => 'content',
                    'heading' => 'What You Need to Know',
                    'body' => <<<'TEXT'
                    - U.S. locations are written as a city plus a two-letter state code in capital letters, for example Dallas, TX.
                    - The codes are set by the U.S. Postal Service. You will see them in almost every requirement, resume and address.
                    - Always read the code together with the city. Different states have cities with the same name.
                    TEXT,
                ],
                [
                    'kind' => 'reference',
                    'heading' => 'State Code Table',
                    'body' => $stateTable,
                ],
                [
                    'kind' => 'content',
                    'heading' => 'Commonly Confused Codes',
                    'body' => <<<'TEXT'
                    - AK is Alaska and AR is Arkansas. Arizona is AZ.
                    - MA is Massachusetts, MD is Maryland and ME is Maine.
                    - MI is Michigan, MN is Minnesota, MS is Mississippi, MO is Missouri and MT is Montana.
                    - IA is Iowa, ID is Idaho, IL is Illinois and IN is Indiana.
                    - NE is Nebraska and NV is Nevada.
                    - WA is Washington state. DC is Washington, D.C.
                    TEXT,
                ],
                [
                    'kind' => 'note',
                    'heading' => 'Correction to the Company Handout',
                    'body' => 'The OPT Recruiter Training Material writes MO for Montana and Co for Colorado. The correct codes are **MT** for Montana and **CO**, in capitals, for Colorado. MO is Missouri.',
                ],
                [
                    'kind' => 'content',
                    'heading' => 'Spelling Codes on Calls',
                    'body' => 'On phone calls, letters such as B, D, P and T, or M and N, are easy to mishear. Spell codes with the standard phonetic alphabet, for example N as in November, J as in Juliet, for New Jersey.',
                ],
                [
                    'kind' => 'example',
                    'heading' => 'Recruiter Example',
                    'body' => 'One requirement says Location: Columbus, OH. Another says Columbus, GA. These are two different cities: Columbus, Ohio, and Columbus, Georgia. Read the state code, not only the city name, before you start searching for candidates.',
                ],
                [
                    'kind' => 'mistakes',
                    'heading' => 'Common Mistakes',
                    'body' => <<<'TEXT'
                    - Writing MI for Mississippi, or MA for Maryland.
                    - Using lower case or unofficial three-letter versions.
                    - Ignoring the state code when two cities share a name.
                    - Copying MO for Montana from the old handout.
                    TEXT,
                ],
                [
                    'kind' => 'practice',
                    'heading' => 'Practice',
                    'body' => <<<'TEXT'
                    1. What is the code for Massachusetts, and what is the code for Maryland?
                    2. A resume says Austin, TX. Which state is that?
                    3. Which state is AK, and which state is AR?
                    4. A job description says Portland, ME. Is that the same city as Portland, OR?
                    5. How would you spell NJ to a candidate on a phone call?
                    TEXT,
                ],
                [
                    'kind' => 'takeaway',
                    'heading' => 'Key Takeaway',
                    'body' => 'Two-letter state codes appear everywhere in U.S. staffing. Read them carefully, write them correctly, and check before you guess.',
                ],
            ],
            'te' => [
                [
                    'kind' => 'objective',
                    'heading' => 'నేర్చుకునే లక్ష్యం',
                    'body' => 'అన్ని 50 U.S. states మరియు Washington, D.C. యొక్క official two-letter postal codes నేర్చుకోండి. దీనివల్ల Job Description, Resume, address లను సందేహం లేకుండా చదవగలుగుతారు.',
                ],
                [
                    'kind' => 'content',
                    'heading' => 'మీరు తెలుసుకోవాల్సినది',
                    'body' => <<<'TEXT'
                    - U.S. లో location ను city పేరు తో పాటు capital letters లో two-letter state code గా రాస్తారు. ఉదాహరణకు Dallas, TX.
                    - ఈ codes ను U.S. Postal Service నిర్ణయిస్తుంది. దాదాపు ప్రతి Requirement, Resume, address లో ఇవి కనిపిస్తాయి.
                    - City పేరు తో పాటు code ను ఎప్పుడూ కలిపి చదవండి. వేర్వేరు states లో ఒకే పేరు ఉన్న cities ఉంటాయి.
                    TEXT,
                ],
                [
                    'kind' => 'reference',
                    'heading' => 'State Code పట్టిక',
                    'body' => $stateTable,
                ],
                [
                    'kind' => 'content',
                    'heading' => 'తరచుగా తికమకపడే Codes',
                    'body' => <<<'TEXT'
                    - AK అంటే Alaska, AR అంటే Arkansas. Arizona code AZ.
                    - MA అంటే Massachusetts, MD అంటే Maryland, ME అంటే Maine.
                    - MI అంటే Michigan, MN అంటే Minnesota, MS అంటే Mississippi, MO అంటే Missouri, MT అంటే Montana.
                    - IA అంటే Iowa, ID అంటే Idaho, IL అంటే Illinois, IN అంటే Indiana.
                    - NE అంటే Nebraska, NV అంటే Nevada.
                    - WA అంటే Washington state. DC అంటే Washington, D.C.
                    TEXT,
                ],
                [
                    'kind' => 'note',
                    'heading' => 'Company Handout లో సవరణ',
                    'body' => 'OPT Recruiter Training Material లో Montana కు MO అని, Colorado కు Co అని రాసి ఉంది. సరైన codes: Montana కు **MT**, Colorado కు capital letters లో **CO**. MO అంటే Missouri.',
                ],
                [
                    'kind' => 'content',
                    'heading' => 'Calls లో Codes ను Spell చేయడం',
                    'body' => 'Phone calls లో B, D, P, T లేదా M, N వంటి letters సులభంగా తప్పుగా వినిపిస్తాయి. Standard phonetic alphabet తో spell చేయండి. ఉదాహరణకు New Jersey కోసం N as in November, J as in Juliet.',
                ],
                [
                    'kind' => 'example',
                    'heading' => 'Recruiter ఉదాహరణ',
                    'body' => 'ఒక Requirement లో Location: Columbus, OH అని ఉంది. మరొక దానిలో Columbus, GA అని ఉంది. ఇవి రెండు వేర్వేరు cities: Columbus, Ohio మరియు Columbus, Georgia. Candidates కోసం search మొదలుపెట్టే ముందు city పేరు మాత్రమే కాదు, state code కూడా చదవండి.',
                ],
                [
                    'kind' => 'mistakes',
                    'heading' => 'సాధారణ తప్పులు',
                    'body' => <<<'TEXT'
                    - Mississippi కు MI అని, Maryland కు MA అని రాయడం.
                    - Lower case లేదా official కాని three-letter codes వాడటం.
                    - రెండు cities కు ఒకే పేరు ఉన్నప్పుడు state code ను పట్టించుకోకపోవడం.
                    - పాత handout నుండి Montana కు MO అని copy చేయడం.
                    TEXT,
                ],
                [
                    'kind' => 'practice',
                    'heading' => 'సాధన',
                    'body' => <<<'TEXT'
                    1. Massachusetts code ఏమిటి? Maryland code ఏమిటి?
                    2. ఒక Resume లో Austin, TX అని ఉంది. అది ఏ state?
                    3. AK ఏ state? AR ఏ state?
                    4. ఒక Job Description లో Portland, ME అని ఉంది. ఇది Portland, OR ఒకటేనా?
                    5. Phone call లో Candidate కు NJ ను ఎలా spell చేసి చెబుతారు?
                    TEXT,
                ],
                [
                    'kind' => 'takeaway',
                    'heading' => 'ముఖ్యమైన విషయం',
                    'body' => 'Two-letter state codes U.S. staffing లో ప్రతిచోటా కనిపిస్తాయి. వాటిని జాగ్రత్తగా చదవండి, సరిగ్గా రాయండి, అంచనా వేయకుండా ముందు check చేయండి.',
                ],
            ],
        ],
    ],
];
