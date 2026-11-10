<?php

return [
    'title' => 'User Guide',
    'intro' => 'A short guide to Rumus Langit: from getting a report to understanding prices and credits.',

    'langkah_title' => 'How to get a report',
    'langkah' => [
        'Enter your birth data (date, time and place) — your weton, zodiac and Chinese sign are calculated from it.',
        'Verify your email with the 6-digit code we send, so your reports and purchases are saved to your account.',
        'In the catalog, pick the report you want. Paid reports open the payment page; Dream Interpretation is free.',
        'Answer a short Yes/No questionnaire (plus partner data for the Compatibility report), then wait a moment for your report.',
        'Read your report. It is saved to your account and can be reopened anytime via "View my latest report".',
    ],

    'paket_title' => 'Plans and prices',
    'paket' => [
        ['judul' => 'Single report — Rp :satuan', 'isi' => 'To try one report. You get :n credits for that report (see "What is a credit?" below). Reports you have created are saved forever.'],
        ['judul' => 'Monthly subscription — Rp :bulanan', 'isi' => 'Every report with no credits (fair-use limit of :batas reports per day), plus Daily Lucky Numbers & Colors and the Daily Fortune email while subscribed.'],
        ['judul' => 'Yearly subscription — Rp :tahunan', 'isi' => 'Same as monthly for 12 months, at a better price than paying monthly twelve times.'],
    ],
    'paket_saran' => 'Tip: if you want to open more than one report, a subscription is usually cheaper than buying single reports.',

    'jatah_title' => 'What is a credit?',
    'jatah_intro' => 'Each single-report purchase gives you :n credits. A credit is used when you create a report with questionnaire answers that DIFFER from before.',
    'jatah_contoh_title' => 'Example',
    'jatah_contoh' => [
        'You fill in the questionnaire with answers A → the report is created, 1 credit used (:n−1 left).',
        'You submit the same answers A again on the same day → you see the exact same report, no credit used.',
        'The next day you submit the same answers A → a fresh report is generated (the result may differ), still no credit used.',
        'You change your answers to B → a new report with different answers, 1 more credit used.',
        'Out of credits → old reports can still be opened; to create with new answers, buy again (:n new credits) or subscribe.',
    ],
    'jatah_catatan' => 'Switching language (Indonesian ↔ English) with the same answers creates the report in the new language without using a credit. Failed generations do not use a credit.',

    'hari_title' => 'Why can results differ on another day?',
    'hari_isi' => 'Within one day (Western Indonesia Time), the same answers always show the same result. After the day changes (00:00 WIB), the same answers create a new report, so the wording and recommendations may differ. Your old report is not lost.',

    'bayar_title' => 'Payment',
    'bayar' => [
        'Payment uses QRIS from an Indonesian e-wallet or mobile banking app (GoPay, OVO, DANA, ShopeePay, bank apps).',
        'After paying, the page moves on to the questionnaire by itself once the payment is confirmed (usually a few seconds).',
        'A payment receipt is emailed to you automatically. Not in your Inbox? Check your Spam or Junk folder.',
        'Paying from abroad? Save the QR image and open it via the scan/upload-QR feature of your Indonesian bank or e-wallet app.',
    ],

    'akun_title' => 'Account and login',
    'akun' => [
        'Sign in with an email code: on the Login page enter your email → a 6-digit code is sent (valid 5 minutes; check Spam if it is missing).',
        'Prefer a password? Open Settings (⚙ icon) and create one. The email code always keeps working.',
        'Your reports and purchases follow your account email, so you can open them from any device after signing in.',
    ],

    'faq_title' => 'FAQ',
    'faq' => [
        ['q' => 'I paid but access has not opened.', 'a' => 'Wait 1–2 minutes and refresh. Check the payment receipt email. If it still has not opened, contact us with the order number (RL-…) from the receipt.'],
        ['q' => 'Did I lose my report?', 'a' => 'No. Open the report in the catalog and choose "View my latest report". Reports are saved to your account forever.'],
        ['q' => 'Why is today\'s result different from yesterday\'s for the same answers?', 'a' => 'Because a fresh report is generated each day. Within one day the result is always the same.'],
        ['q' => 'How many reports can a subscriber create?', 'a' => 'No credits, with a fair-use limit of :batas reports per day to keep the service smooth for everyone. Dream Interpretation (free) is limited to :gratis per day.'],
    ],

    'disclaimer' => 'Rumus Langit is a metaphysics consultation and reflective entertainment platform — not a substitute for medical, legal, financial or other professional advice.',
];
