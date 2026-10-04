<?php

/*
 * Combined lesson "U.S. Time Zones & India Conversion". One topic per merged lesson:
 * U.S. Time Zones, India ↔ U.S. Time Conversion, Daylight Saving Time.
 */

return [
    'title' => 'U.S. Time Zones & India Conversion',
    'from' => 'U.S. Time Zones',
    'en' => [
        ['kind' => 'objective', 'heading' => 'Learning Objective', 'body' => <<<'TEXT'
            Work confidently across U.S. time zones: know the zones, convert any U.S. time to India time and back, and adjust correctly when Daylight Saving Time starts and ends.
            TEXT],
        ['kind' => 'flow', 'heading' => 'Lesson Map', 'body' => <<<'TEXT'
            1. US Time Zones [[U.S. Time Zones]]
            2. India ↔ US Time Conversion [[India ↔ U.S. Time Conversion]]
            3. Daylight Saving Time [[Daylight Saving Time]]
            TEXT],
        ['kind' => 'topic', 'heading' => 'U.S. Time Zones', 'body' => <<<'TEXT'
            Always attach a time zone to every time you write or say. When people are in different zones, state the time for each of them.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            The U.S. mainland has four main time zones. Alaska and Hawaii have their own. Each zone going west is one hour earlier than the zone before it.
            TEXT],
        ['kind' => 'content', 'heading' => 'The six time zones', 'body' => <<<'TEXT'
            - Eastern Time, written as ET. Used on the East Coast, including New York, New Jersey, Georgia, Florida, North Carolina, Virginia, Ohio and Michigan.
            - Central Time, written as CT. One hour earlier than Eastern. Used in Texas, Illinois, Minnesota, Missouri, Tennessee, Alabama and nearby states.
            - Mountain Time, written as MT. Two hours earlier than Eastern. Used in Colorado, Utah, New Mexico, Montana, Wyoming and Arizona.
            - Pacific Time, written as PT. Three hours earlier than Eastern. Used in California, Washington, Oregon and Nevada.
            - Alaska Time. Four hours earlier than Eastern.
            - Hawaii-Aleutian Time. Five or six hours earlier than Eastern, depending on the season, because Hawaii does not change its clocks.
            TEXT],
        ['kind' => 'content', 'heading' => 'Standard and daylight time', 'body' => <<<'TEXT'
            Each zone has a standard time and a daylight time. For example, Eastern Standard Time is EST and Eastern Daylight Time is EDT. In daily speech people just say Eastern Time. The next lessons explain Daylight Saving Time and conversion from India.
            TEXT],
        ['kind' => 'content', 'heading' => 'Split states', 'body' => <<<'TEXT'
            Some states use two time zones. Parts of Florida, Tennessee, Kentucky, Indiana and Texas, among others, are in different zones. If a city is near a time zone border, check it.
            TEXT],
        ['kind' => 'content', 'heading' => 'Arizona', 'body' => <<<'TEXT'
            Most of Arizona does not use Daylight Saving Time. In summer, Arizona matches Pacific Time. In winter, it matches Mountain Time.
            TEXT],
        ['kind' => 'content', 'heading' => 'From the company training material', 'body' => <<<'TEXT'
            The OPT Recruiter Training Material teaches four main time zones, Eastern Standard Time, Central Standard Time, Mountain Standard Time and Pacific Standard Time, written as EST, CST, MST and PST, plus two other zones, Alaska Standard Time and Hawaii Standard Time. It then groups the states under each zone. Learn the groups below. They follow the handout, with a few corrections where the handout placed a state in the wrong zone.
            Eastern Time. Connecticut, Delaware, Georgia, Maine, Maryland, Massachusetts, New Hampshire, New Jersey, New York, North Carolina, Ohio, Pennsylvania, Rhode Island, South Carolina, Vermont, Virginia and West Virginia, plus Washington, D.C. Most of Florida, Indiana and Michigan, and the eastern part of Kentucky and Tennessee, are also Eastern.
            Central Time. Alabama, Arkansas, Illinois, Iowa, Louisiana, Minnesota, Mississippi, Missouri, Oklahoma and Wisconsin. Most of Texas, Kansas, Nebraska, North Dakota, South Dakota and Tennessee, and the western part of Kentucky, are also Central.
            Mountain Time. Arizona, Colorado, Montana, New Mexico, Utah and Wyoming, and most of Idaho. The El Paso area of Texas is also Mountain.
            Pacific Time. California, Nevada, Oregon and Washington, and the northern part of Idaho.
            Alaska Time. Alaska.
            Hawaii-Aleutian Time. Hawaii.
            TEXT],
        ['kind' => 'content', 'heading' => 'Corrections to the original handout', 'body' => <<<'TEXT'
            Texas is in Central Time, except the El Paso area. The handout listed Texas under Eastern Time with a note about the company headquarters. A company office never changes a state's time zone.
            Hawaii is not in Mountain Time, and Alaska is not in Pacific Time. Each has its own zone.
            Indiana, Kentucky, Michigan, Florida, Tennessee and several other states are split between two zones, so check the city.
            The handout wrote MO for Montana. The correct code is MT. MO is Missouri.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            A hiring manager in Chicago wants a call at 11 AM. The candidate lives in San Jose. Chicago is Central Time and San Jose is Pacific Time, two hours earlier. The candidate must join at 9 AM Pacific Time. You write both times in the invitation: 11 AM Central, 9 AM Pacific.
            TEXT],
        ['kind' => 'reference', 'heading' => 'Recruiter Checklist', 'body' => <<<'TEXT'
            - Write every meeting time with its time zone.
            - When two people are in different zones, show both times.
            - Check split states and Arizona carefully.
            - Use a reliable world clock tool rather than mental maths when in doubt.
            TEXT],
        ['kind' => 'mistakes', 'heading' => 'Common Mistakes', 'body' => <<<'TEXT'
            - Writing a time without a time zone.
            - Assuming the candidate is in the same zone as the client.
            - Forgetting that Arizona does not change its clocks.
            TEXT],
        ['kind' => 'topic', 'heading' => 'India ↔ U.S. Time Conversion', 'body' => <<<'TEXT'
            India time is fixed, but the U.S. gap changes by one hour twice a year. Check the season, convert carefully and always confirm the date.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            India Standard Time, written as IST, is five and a half hours ahead of Coordinated Universal Time. India does not use Daylight Saving Time, so IST never changes.
            Most U.S. time zones change their clocks twice a year. This means the gap between India and the U.S. changes by one hour depending on the season.
            TEXT],
        ['kind' => 'content', 'heading' => 'Time differences from India', 'body' => <<<'TEXT'
            When the U.S. is on daylight time, roughly March to November, India is ahead of the U.S. by these amounts.
            Eastern Time, nine hours thirty minutes.
            Central Time, ten hours thirty minutes.
            Mountain Time, eleven hours thirty minutes.
            Pacific Time, twelve hours thirty minutes.

            When the U.S. is on standard time, roughly November to March, India is ahead by these amounts.
            Eastern Time, ten hours thirty minutes.
            Central Time, eleven hours thirty minutes.
            Mountain Time, twelve hours thirty minutes.
            Pacific Time, thirteen hours thirty minutes.

            Arizona stays twelve hours thirty minutes behind India all year. Hawaii stays fifteen hours thirty minutes behind India all year. Alaska is thirteen hours thirty minutes behind India in summer and fourteen hours thirty minutes behind in winter.
            TEXT],
        ['kind' => 'content', 'heading' => 'From the company training material', 'body' => <<<'TEXT'
            The OPT Recruiter Training Material gives one set of differences between India and the U.S.: Eastern nine hours thirty minutes, Central ten hours thirty minutes, Mountain eleven hours thirty minutes, Pacific twelve hours thirty minutes, Alaska thirteen hours thirty minutes and Hawaii fourteen hours thirty minutes.
            Use these figures with care. The first five are correct only while the U.S. is on daylight time, roughly March to November. In winter, add one hour to each. The Hawaii figure in the handout is wrong. Hawaii does not change its clocks, so it is fifteen hours thirty minutes behind India all year. The lists above in this lesson cover both seasons, so use them instead of the single list in the handout.
            TEXT],
        ['kind' => 'content', 'heading' => 'How to convert', 'body' => <<<'TEXT'
            To get U.S. time from India time, subtract the difference.
            To get India time from U.S. time, add the difference.
            Remember that the date often changes. A 10 AM Eastern call on Monday in summer is 7:30 PM Monday in India. A 4 PM Pacific call on Monday in summer is 4:30 AM Tuesday in India.
            TEXT],
        ['kind' => 'content', 'heading' => 'Typical shift planning', 'body' => <<<'TEXT'
            U.S. business hours, 9 AM to 5 PM Eastern, are roughly 6:30 PM to 2:30 AM in India during U.S. daylight time, and 7:30 PM to 3:30 AM during U.S. standard time.
            West Coast business hours fall even later in the Indian night.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            It is July. A vendor in Dallas asks for a call at 2 PM Central Time. Dallas is ten hours thirty minutes behind India in July. Two PM plus ten hours thirty minutes is 12:30 AM in India, on the next calendar day. You confirm the call for 2 PM Central and set your own reminder for 12:30 AM IST.
            TEXT],
        ['kind' => 'reference', 'heading' => 'Recruiter Checklist', 'body' => <<<'TEXT'
            - Check whether the U.S. is currently on daylight or standard time.
            - Always confirm the date as well as the time after converting.
            - Put the U.S. time first in messages to clients and candidates.
            - Use a world clock tool to double-check important calls.
            TEXT],
        ['kind' => 'mistakes', 'heading' => 'Common Mistakes', 'body' => <<<'TEXT'
            - Using the summer difference in winter.
            - Forgetting that the date changes after midnight in India.
            - Sending a candidate an India time instead of their local time.
            TEXT],
        ['kind' => 'topic', 'heading' => 'Daylight Saving Time', 'body' => <<<'TEXT'
            Twice a year, the gap between India and most of the U.S. changes by one hour. Know the dates and re-check every scheduled call after a change.
            TEXT],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => <<<'TEXT'
            Daylight Saving Time, often shortened to DST, means moving clocks one hour forward in spring and one hour back in autumn. The idea is to have more daylight in the evening during summer.
            Under the current U.S. rule, clocks move forward one hour on the second Sunday in March and move back one hour on the first Sunday in November. Americans remember this as spring forward, fall back. Rules can be changed by law, so check the dates each year.
            TEXT],
        ['kind' => 'content', 'heading' => 'Who does not change clocks', 'body' => <<<'TEXT'
            - India does not use Daylight Saving Time.
            - Most of Arizona does not use it.
            - Hawaii does not use it.
            - U.S. territories such as Puerto Rico do not use it.
            TEXT],
        ['kind' => 'content', 'heading' => 'What changes for you', 'body' => <<<'TEXT'
            In March, when U.S. clocks move forward, the gap between India and the U.S. becomes one hour smaller. A 10 AM Eastern call moves from 8:30 PM India time to 7:30 PM India time.
            In November, when U.S. clocks move back, the gap becomes one hour larger. The same 10 AM Eastern call moves from 7:30 PM to 8:30 PM India time.
            Your shift timing in India may need to move by one hour to match U.S. business hours.
            TEXT],
        ['kind' => 'content', 'heading' => 'Why this matters for the company handout', 'body' => <<<'TEXT'
            The OPT Recruiter Training Material lists one fixed time difference for each zone, for example nine hours thirty minutes between India and Eastern Time. That figure is the summer, daylight time difference. In winter, after U.S. clocks move back in November, the difference is ten hours thirty minutes. Whenever you use the handout's list, ask yourself which season it is.
            TEXT],
        ['kind' => 'content', 'heading' => 'Other countries', 'body' => <<<'TEXT'
            Europe and some other countries also change their clocks, but on different dates. For a few weeks each year, the gap between the U.S. and Europe is different from usual. This matters only if your client or candidate is outside the U.S.
            TEXT],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => <<<'TEXT'
            On the Monday after the second Sunday in March, a candidate in New Jersey says, I will call you at 11 AM my time. Last week that was 9:30 PM in India. This week it is 8:30 PM in India. You update your reminder so you do not miss the call.
            TEXT],
        ['kind' => 'reference', 'heading' => 'Recruiter Checklist', 'body' => <<<'TEXT'
            - Mark the March and November clock-change dates in your calendar every year.
            - Re-check all recurring meetings in the week after a clock change.
            - Remember that Arizona and Hawaii do not change.
            - Confirm times with the other person when in doubt.
            TEXT],
        ['kind' => 'mistakes', 'heading' => 'Common Mistakes', 'body' => <<<'TEXT'
            - Keeping the old time difference after a clock change.
            - Assuming India also changes its clocks.
            - Forgetting to adjust shift timings for the team.
            TEXT],
        ['kind' => 'takeaway', 'heading' => 'Key Takeaway', 'body' => <<<'TEXT'
            Always say whose time zone a time is in, convert with the current Daylight Saving offset, and double-check before you schedule a call.
            TEXT],
    ],
];
