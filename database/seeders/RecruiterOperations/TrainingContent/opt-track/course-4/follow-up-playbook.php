<?php

/*
 * "Follow-Up & Next Steps — Complete Follow-Up Playbook": everything after
 * the consultant says yes, and every follow-up after the call, in one
 * playbook lesson.
 */

return [
    'title' => 'Follow-Up & Next Steps — Complete Follow-Up Playbook',
    'from' => 'Requesting the Updated Resume',
    'compliance' => true,
    'review' => $confirm('the company details included in the email to candidates, the $750 referral amount and its terms, and the voicemail call-back number 9515708888 extension 999'),
    'en' => $lesson(
        'Close every interested call with all the next steps agreed, and follow up on time by email, phone and voicemail until the consultant responds or clearly says no.',
        [
            ['note', 'Rules for Follow-Up', <<<'TEXT'
            - An interested consultant leaves the call with five next steps covered: company mail, resume, referrals, start date and the sales manager call.
            - Confirm everything in writing from your official company email, the same day.
            - Always say whose time zone a time is in, and call back exactly when agreed.
            - Use only approved company channels. Text messages only if the consultant agreed to them.
            - Never promise what the sales manager will offer, or any result.
            TEXT],
            ['flow', 'Follow-Up Flow', <<<'TEXT'
            1. Interested. The consultant wants to go ahead. [[The Five-Step Close]]
            2. Company Mail. Approved template, sent the same day. [[Company Mail]]
            3. Resume. The most updated resume, by email. [[Resume Request]]
            4. Referral. Ask for friends who are looking. [[Referral]]
            5. Start Date. Notice period or start date. [[Start Date]]
            6. Sales Manager Call. Book it, confirm it, share notes. [[Sales Manager Call]]
            7. Follow-up Call. Updates on schedule, even with no news. [[Follow-up Call]]
            8. Responded? If not, follow the no-response steps. [[No Response]]
            9. Voicemail. Short, clear, with the call-back number. [[Voicemail]]
            TEXT],
            ['script', 'The Five-Step Close', <<<'TEXT'
            **Consultant may say:**
            - **They say:** "This sounds good. I am interested." **You say:** "That is great. I will send you a mail with our company details today. Please send me your most updated resume." Then cover the other steps in order.
            **Cover all five:**
            - Company mail.
            - Resume.
            - Referrals.
            - Start date or notice period.
            - Sales manager call.
            **Record in CRM:**
            - Interested: yes, with the services discussed.
            - Each of the five steps, ticked as agreed.
            TEXT],
            ['script', 'Company Mail', <<<'TEXT'
            **Say:** "I will send you a mail with our company details today."
            **Send:**
            - The approved company email template, from your official company address.
            - Your name, title and phone number in the signature.
            - A short summary of what you agreed on the call.
            **Consultant may say:**
            - **They say:** "I did not get your mail." **You say:** "Let me check the address with you and resend it now. Please also check your spam folder."
            **Record in CRM:**
            - Company mail sent: date and time.
            **Tip:** Send it within an hour of the call, while the conversation is fresh.
            TEXT],
            ['script', 'Resume Request', <<<'TEXT'
            **Say:** "Please send me your most updated resume."
            **Consultant may say:**
            - **They say:** "I will send it tonight." **You say:** "Thank you. My official email address is in the mail I am sending you."
            - **They say:** "My resume is old." **You say:** "No problem. Please send your most recent one. Our resume building service can help improve it."
            - **They say:** "Can I send it on WhatsApp?" **You say:** "Please send it by email to my official address, so it is stored securely."
            **Record in CRM:**
            - Resume promised by (date), or received.
            **Never:** Accept an old resume without asking for the latest, or collect resumes through personal messaging apps.
            TEXT],
            ['script', 'Referral', <<<'TEXT'
            **Say:** "Please refer your friends also. For each referral you will get a referral amount of $750. The more friends you refer, the more benefits you will get from our company."
            **Consultant may say:**
            - **They say:** "How is the referral paid?" **You say:** "Let me confirm the current terms with my team, and I will include them in my mail."
            - **They say:** "My friend is looking." **You say:** "Great. May I have their name and number, and may I mention that you referred them?"
            **Record in CRM:**
            - Referral names and numbers, with the consultant's permission.
            **Tip:** Quote only the confirmed amount, payment timing and conditions.
            TEXT],
            ['script', 'Start Date', <<<'TEXT'
            **Ask:** "What is your notice period to join the training and placement program?"
            **Ask:** "When would you like to get started?"
            **Consultant may say:**
            - **They say:** "Two weeks." **You say:** "Okay, thank you. So you could start around the 20th?" Confirm the date.
            - **They say:** "I need to give notice at my current job." **You say:** "Okay. How many weeks of notice do you need to give?"
            **Record in CRM:**
            - Start date, or notice period in weeks.
            TEXT],
            ['script', 'Sales Manager Call', <<<'TEXT'
            **Ask:** "What is your available time to talk with my sales manager?"
            **Consultant may say:**
            - **They say:** "Thursday at 11 a.m. Eastern." **You say:** "Perfect. I will send you a confirmation with the time and my manager's name."
            - **They say:** "I am not sure yet." **You say:** "No problem. Can I call you tomorrow to fix a time?"
            **After the call:**
            - Send the confirmation with the date, time, time zone and your manager's name.
            - Share your call notes with your sales manager before their call.
            - Remind the consultant shortly before the call.
            **Record in CRM:**
            - Sales manager call: date, time and time zone.
            - Reminder set.
            **Never:** Promise what the sales manager will offer.
            TEXT],
            ['script', 'Follow-up Call', <<<'TEXT'
            **Say:** "Hi Priya, this is Kiran from VSP Group, following up on our call. Did you get a chance to send your resume?"
            **Follow-up schedule:**
            - After submission: an update within one or two business days.
            - After an interview: call the same day for feedback, then share client feedback when you receive it.
            - When there is no news: send a short update anyway.
            - After the start: check in on the first day and after the first week.
            **Consultant may say:**
            - **They say:** "Any update?" **You say:** "Not yet. I am following up with the client, and I will update you by Thursday afternoon."
            - **They say:** "The interview went well, but one question was hard." **You say:** "Thank you for telling me. I will note your feedback and ask the vendor for client feedback."
            **Record in CRM:**
            - Every follow-up: date, channel and outcome.
            - Next update promised, with its date.
            **Tip:** Close every follow-up with a next step: "I will update you by Thursday afternoon."
            TEXT],
            ['script', 'No Response', <<<'TEXT'
            **Do:**
            - Follow up two or three times, a few days apart, by phone and email.
            - Leave the voicemail when the consultant does not answer.
            **Then send:** "I have not heard back, so I will assume you are not looking right now. Please contact me anytime if that changes."
            **Consultant may say:**
            - **They say:** "Sorry, I was busy. I am still interested." **You say:** "No problem at all. Shall we continue from where we stopped? Did you get a chance to send your resume?"
            - **They say:** "I found another job." **You say:** "Congratulations! Thank you for letting me know. If any of your friends are looking, please share my number."
            **Record in CRM:**
            - Each attempt: date, time and channel.
            - Final message sent, and the status set to not responding.
            **Never:** Follow up many times a day, or use pressure.
            TEXT],
            ['script', 'Voicemail', <<<'TEXT'
            **Say:** "Hi, this is Ajay calling from VSP Group. This call is regarding a job opportunity for you. I would appreciate it if you call me back at 9515708888, extension 999. Thank you. Bye, have a nice day."
            **Before you leave it:**
            - Use your own name, and the call-back number and extension assigned to you.
            - Say the number slowly, and repeat it once.
            - Keep it under 30 seconds.
            - Send a short email afterwards, so the consultant has your number in writing.
            **When they call back:**
            - **They say:** "I got a voicemail from this number." **You say:** "Thank you for calling back. This is Ajay from VSP Group. I called about a job opportunity for you. Is this a good time to talk for a few minutes?"
            - **They say:** "What was the voicemail about?" **You say:** "It was about a job opportunity that may match your profile. I left only my number in the message, so I can share the details with you now."
            **Record in CRM:**
            - Voicemail left: date and time.
            - Next call attempt date.
            **Never:** Leave visa, pricing or personal details in a voicemail.
            TEXT],
        ],
        'Cover all five next steps before you say goodbye, confirm them in writing the same day, follow up on schedule, and record every attempt in the CRM.',
    ),
];
