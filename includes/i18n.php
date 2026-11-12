<?php

require_once __DIR__ . '/bible-books.php';

/**
 * UI translations (same pattern as bible-quiz-app's quiz-i18n.php: a table per language, with English as the
 * fallback for any key a language doesn't define). Languages: en, ta (தமிழ்), hi (हिंदी), kn (ಕನ್ನಡ).
 *
 * The UI language is the user's most recent language choice: signed in → settings.planLang (set by the menu's
 * language picker and by saving a plan); signed out → the bt_lang cookie. Use btT('key', ['name' => ...]) —
 * {name}-style placeholders are replaced. Keep keys the same in every language; add new ones to English first.
 */

function btLabelsByLanguage(): array
{
    static $all = null;
    if ($all !== null) {
        return $all;
    }
    $all = [];
    $all['en'] = [
        'site.brand' => 'Bible Tracker',
        'site.tagline' => 'Read the Bible. Track Your Progress. Grow in God\'s Word.',
        'nav.home' => 'Home', 'nav.menu' => 'Menu', 'nav.signOut' => 'Sign out', 'nav.installApp' => 'Install App',
        'nav.myAchievements' => 'My achievements', 'nav.theme' => 'Dark / light theme', 'nav.language' => 'Language',
        'site.verse' => 'No Copyright, “Freely you have received; Freely give” — Matt 10:8',

        'home.lead' => 'Read the Bible regularly. Pick a ready-made reading plan, or sign in to create and track your own.',
        'home.downloadTitle' => 'Download plans',
        'home.downloadDesc' => 'Create a printable A4 reading plan (PDF) with your own title. No sign-in needed.',
        'home.createTitle' => 'Create & track your own plans',
        'home.createDesc' => 'Sign in with Google to build custom plans, tick off each chapter and see your progress.',
        'home.signIn' => 'Sign in with Google', 'home.signInUnavailable' => 'Sign in unavailable',
        'home.signInUnavailableNote' => 'The shared sign-in service is not installed here.',
        'home.myPlans' => 'My Plans', 'home.welcome' => 'Welcome, {name}.', 'home.newPlan' => 'New plan',
        'home.planDeleted' => 'Plan deleted.', 'home.planSaved' => 'Plan saved.',
        'home.noPlans' => 'You have no reading plans yet.', 'home.createFirst' => 'Create your first plan',
        'home.downloadReady' => 'Create a printable plan',

        'plan.read' => 'Read', 'plan.track' => 'Track', 'plan.edit' => 'Edit', 'plan.delete' => 'Delete',
        'plan.deleteConfirm' => 'Delete this plan and its progress?', 'plan.completed' => 'Completed 🎉',
        'plan.progress' => '{done} / {total} chapters · {pct}%',

        'lb.title' => 'Leaderboard', 'lb.subtitle' => 'chapters read', 'lb.you' => 'YOU', 'lb.showAll' => 'Show all',
        'lb.top10' => 'Top 10', 'lb.rank' => 'Rank', 'lb.name' => 'Name', 'lb.chaptersRead' => 'Chapters read',
        'lb.empty' => 'No readers on the leaderboard yet. Mark chapters as read to be the first!',
        'ach.title' => 'Achievements',
        'ach.intro' => 'Every chapter you mark as read counts toward your lifetime total and lights up stars — from Silver to Diamond.',
        'ach.banner' => '{name} has earned {tier} ★{n} — keep reading!',
        'ach.booksCompleted' => 'Books completed', 'ach.rank' => 'Leaderboard rank', 'ach.editName' => 'Edit name',
        'ach.toGo' => '{n} to go', 'ach.completed' => '✓ Completed', 'ach.modalHelp' => 'This name is shown on the public leaderboard. Leave it empty to use your Google name.',
        'ach.displayName' => 'Display name', 'ach.saveError' => 'Could not save. Please try again.',
        'tier.silver' => 'Silver', 'tier.bronze' => 'Bronze', 'tier.gold' => 'Gold', 'tier.platinum' => 'Platinum', 'tier.diamond' => 'Diamond',
        'common.save' => 'Save', 'common.cancel' => 'Cancel',

        'form.newPlan' => 'New plan', 'form.editPlan' => 'Edit plan', 'form.planName' => 'Plan name', 'form.optional' => '(optional)',
        'form.planNamePh' => 'e.g. Bible in a year', 'form.language' => 'Language', 'form.languageHelp' => 'Book names in your plan and schedule.',
        'form.scope' => 'Scope', 'form.tickBooks' => 'Tick the books to include.', 'form.addRanges' => 'Add one or more chapter ranges.',
        'form.to' => 'to', 'form.addRange' => 'Add range', 'form.pickSession' => 'Pick the book(s) to read in each session.',
        'form.morningBooks' => 'Morning book(s)', 'form.eveningBooks' => 'Evening book(s)', 'form.howToRead' => 'How to read?',
        'form.perDay' => 'Chapters per day', 'form.numDays' => 'Number of days', 'form.create' => 'Create plan', 'form.saveChanges' => 'Save changes',
        'form.resetNote' => "Changing the scope or reading schedule resets this plan's progress.",
        'scope.whole' => 'Whole Bible', 'scope.ot' => 'Old Testament', 'scope.nt' => 'New Testament', 'scope.books' => 'Selected Books',
        'scope.chapters' => 'Selected Chapters', 'scope.two-books' => 'Morning one book, Evening different book',
        'mode.continuous' => 'Continuous', 'mode.perday' => 'Read X Chapters per Day', 'mode.days' => 'Complete in X No of Days',
        'testament.ot' => 'Old Testament', 'testament.nt' => 'New Testament',
        'err.selectBook' => 'Select at least one book.', 'err.addRange' => 'Add at least one chapter range.',
        'err.twoBooks' => 'Choose at least one book for morning and one for evening.', 'err.session' => 'Session expired. Please try again.',
        'err.empty' => 'This selection produces an empty plan.',
        'err.dailyLimit' => 'You cannot mark more than {n} chapters as read in a day (except Psalms). Please read them first — and come back tomorrow for more.',

        'sum.perDay1' => '1 chapter per day', 'sum.perDayN' => '{n} chapters per day', 'sum.inDays' => 'in {n} days',
        'sum.continuous' => 'continuous', 'sum.me' => 'morning & evening', 'sum.morning' => 'Morning', 'sum.evening' => 'Evening',

        'tracker.plans' => 'Plans', 'tracker.selectAll' => 'Select all', 'tracker.scheduleDays' => 'Daily schedule ({n} days)',
        'tracker.scheduleReadings' => 'Daily schedule ({n} readings)', 'tracker.day' => 'Day',

        'reader.translation' => 'Bible translation', 'reader.smaller' => 'Smaller text', 'reader.larger' => 'Larger text',
        'reader.reset' => 'Reset text size', 'reader.markRead' => 'Mark as read', 'reader.read' => 'Read',
        'reader.quiz' => 'Attend Quiz', 'reader.hideQuiz' => 'Hide Quiz', 'reader.next' => 'Next: {ref}',
        'reader.planComplete' => 'Plan complete 🎉 View progress', 'reader.loading' => 'Loading…',
        'reader.loadError' => 'check your connection and try again.', 'reader.saveError' => 'Could not save progress. Please try again.',

        'dl.title' => 'Download plans',
        'dl.desc' => 'Free, ready-made reading plans as a spreadsheet (CSV) you can print or open in Excel / Google Sheets.',
        'dl.days' => '{n} days', 'dl.download' => 'Download', 'dl.cta' => 'Want a custom plan with progress tracking?',
        'dl.csvDay' => 'Day', 'dl.csvReading' => 'Reading', 'dl.csvDone' => 'Done',
        'pdf.optionalSection' => 'Optional',
        'dl.customTitle' => 'Create your own printable plan', 'dl.customDesc' => 'Pick a language, scope and reading pace, add a title, and create an A4 PDF with two columns and a tick box for every reading.', 'dl.readyMadeTitle' => 'Ready-made plans', 'pdf.header' => 'Header', 'pdf.headerPh' => 'e.g. One Year Reading Plan, Church Name, etc.', 'pdf.subheader' => 'Sub header', 'pdf.subheaderPh' => 'e.g. Read a little every day and grow in the Word of God', 'pdf.create' => 'Create PDF', 'pdf.creating' => 'Creating PDF…', 'pdf.ready' => 'Your PDF is ready', 'pdf.download' => 'Download PDF', 'pdf.error' => 'Could not create the PDF. Please try again.', 'pdf.tooMany' => 'This plan has too many readings for one PDF. Choose more chapters per day or fewer days.', 'pdf.page' => 'Page {n}',
        'preset.whole-365' => 'Whole Bible in 1 year', 'preset.whole-365-me' => 'Whole Bible in 1 year (Morning & Evening)',
        'preset.whole-180' => 'Whole Bible in 6 months', 'preset.ot-365' => 'Old Testament in 1 year',
        'preset.nt-90' => 'New Testament in 90 days', 'preset.nt-30' => 'New Testament in 30 days',
        'preset.gospels-30' => 'The Four Gospels in 30 days', 'preset.psalms-proverbs' => 'Psalms & Proverbs - 1 chapter a day',

        'ft.visitors' => 'Visitors', 'ft.downloads' => 'Downloads', 'ft.signedIn' => 'Signed-in Users', 'ft.plansCreated' => 'Plans Created',
        'ft.booksRead' => 'Books Read', 'ft.chaptersRead' => 'Chapters Read', 'ft.quizzes' => 'Quizzes Attempted',
        'ft.topLanguage' => 'Top Language', 'ft.top3Books' => 'Top 3 Books', 'ft.topScope' => 'Top Scope', 'ft.topReading' => 'Top Reading Option',
        'install.ios' => 'To install: tap the Share button, then "Add to Home Screen".',
    ];

    $all['ta'] = [
        'site.brand' => 'Bible Tracker',
        'site.tagline' => 'Read the Bible. Track Your Progress. Grow in God\'s Word.',
        'nav.home' => 'Home', 'nav.menu' => 'பட்டி', 'nav.signOut' => 'Sign Out', 'nav.installApp' => 'Install App',
        'nav.myAchievements' => 'என் சாதனைகள்', 'nav.theme' => 'இருள் / ஒளி தோற்றம்', 'nav.language' => 'மொழி',
        'site.verse' => 'பதிப்புரிமை இல்லை, “இலவசமாய்ப் பெற்றீர்கள், இலவசமாய்க் கொடுங்கள்” — மத் 10:8',

        'home.lead' => 'வேதாகமத்தை தவறாமல் வாசியுங்கள். தயாரான வாசிப்புத் திட்டத்தைத் தேர்ந்தெடுங்கள் அல்லது உங்கள் சொந்த திட்டத்தை உருவாக்கி கண்காணிக்க உள்நுழையுங்கள்.',
        'home.downloadTitle' => 'திட்டங்களைப் பதிவிறக்கு',
        'home.downloadDesc' => 'உங்கள் சொந்த தலைப்புடன் அச்சிடக்கூடிய A4 வாசிப்புத் திட்டத்தை (PDF) உருவாக்குங்கள். உள்நுழைவு தேவையில்லை.',
        'home.createTitle' => 'உங்கள் சொந்த திட்டங்களை உருவாக்கி கண்காணியுங்கள்',
        'home.createDesc' => 'Google மூலம் உள்நுழைந்து சொந்த திட்டங்களை உருவாக்கி, ஒவ்வொரு அதிகாரத்தையும் குறித்து, முன்னேற்றத்தைப் பாருங்கள்.',
        'home.signIn' => 'Google மூலம் உள்நுழை', 'home.signInUnavailable' => 'உள்நுழைவு கிடைக்கவில்லை',
        'home.signInUnavailableNote' => 'பகிர்ந்த உள்நுழைவு சேவை இங்கு நிறுவப்படவில்லை.',
        'home.myPlans' => 'என் திட்டங்கள்', 'home.welcome' => 'வரவேற்கிறோம், {name}.', 'home.newPlan' => 'புதிய திட்டம்',
        'home.planDeleted' => 'திட்டம் நீக்கப்பட்டது.', 'home.planSaved' => 'திட்டம் சேமிக்கப்பட்டது.',
        'home.noPlans' => 'உங்களுக்கு இன்னும் வாசிப்புத் திட்டங்கள் இல்லை.', 'home.createFirst' => 'உங்கள் முதல் திட்டத்தை உருவாக்குங்கள்',
        'home.downloadReady' => 'அச்சிடக்கூடிய திட்டத்தை உருவாக்கு',

        'plan.read' => 'வாசி', 'plan.track' => 'வாசிப்புப் பதிவு', 'plan.edit' => 'திருத்து', 'plan.delete' => 'நீக்கு',
        'plan.deleteConfirm' => 'இந்தத் திட்டத்தையும் அதன் முன்னேற்றத்தையும் நீக்கவா?', 'plan.completed' => 'முடிந்தது 🎉',
        'plan.progress' => '{done} / {total} அதிகாரங்கள் · {pct}%',

        'lb.title' => 'தரவரிசைப் பட்டியல்', 'lb.subtitle' => 'வாசித்த அதிகாரங்கள்', 'lb.you' => 'You', 'lb.showAll' => 'Show All',
        'lb.top10' => 'முதல் 10', 'lb.rank' => 'தரம்', 'lb.name' => 'பெயர்', 'lb.chaptersRead' => 'வாசித்த அதிகாரங்கள்',
        'lb.empty' => 'தரவரிசைப் பட்டியலில் இன்னும் யாரும் இல்லை. அதிகாரங்களை வாசித்ததாகக் குறித்து முதலாவதாக இருங்கள்!',
        'ach.title' => 'சாதனைகள்',
        'ach.intro' => 'நீங்கள் வாசித்ததாகக் குறிக்கும் ஒவ்வொரு அதிகாரமும் உங்கள் மொத்தத்தில் சேர்ந்து நட்சத்திரங்களை ஒளிரச் செய்யும் — வெள்ளி முதல் வைரம் வரை.',
        'ach.banner' => '{name} {tier} ★{n} பெற்றுள்ளார் — தொடர்ந்து வாசியுங்கள்!',
        'ach.booksCompleted' => 'முடித்த புத்தகங்கள்', 'ach.rank' => 'தரவரிசை இடம்', 'ach.editName' => 'பெயரைத் திருத்து',
        'ach.toGo' => 'இன்னும் {n}', 'ach.completed' => '✓ முடிந்தது', 'ach.modalHelp' => 'இந்தப் பெயர் பொது தரவரிசைப் பட்டியலில் காட்டப்படும். காலியாக விட்டால் உங்கள் Google பெயர் பயன்படுத்தப்படும்.',
        'ach.displayName' => 'காட்டப்படும் பெயர்', 'ach.saveError' => 'சேமிக்க முடியவில்லை. மீண்டும் முயற்சிக்கவும்.',
        'tier.silver' => 'வெள்ளி', 'tier.bronze' => 'வெண்கலம்', 'tier.gold' => 'தங்கம்', 'tier.platinum' => 'பிளாட்டினம்', 'tier.diamond' => 'வைரம்',
        'common.save' => 'சேமி', 'common.cancel' => 'ரத்து செய்',

        'form.newPlan' => 'புதிய திட்டம்', 'form.editPlan' => 'திட்டத்தைத் திருத்து', 'form.planName' => 'திட்டத்தின் பெயர்', 'form.optional' => '(விருப்பம்)',
        'form.planNamePh' => 'எ.கா. ஓராண்டில் வேதாகமம்', 'form.language' => 'மொழி', 'form.languageHelp' => 'உங்கள் திட்டத்திலும் அட்டவணையிலும் புத்தகங்களின் பெயர்கள்.',
        'form.scope' => 'வரம்பு', 'form.tickBooks' => 'சேர்க்க வேண்டிய புத்தகங்களைத் தேர்ந்தெடுங்கள்.', 'form.addRanges' => 'ஒன்று அல்லது பல அதிகார வரம்புகளைச் சேர்க்கவும்.',
        'form.to' => 'முதல்', 'form.addRange' => 'வரம்பைச் சேர்', 'form.pickSession' => 'ஒவ்வொரு வேளையிலும் வாசிக்க வேண்டிய புத்தகங்களைத் தேர்ந்தெடுங்கள்.',
        'form.morningBooks' => 'காலை புத்தகம்(கள்)', 'form.eveningBooks' => 'மாலை புத்தகம்(கள்)', 'form.howToRead' => 'எப்படி வாசிப்பது?',
        'form.perDay' => 'ஒரு நாளுக்கு அதிகாரங்கள்', 'form.numDays' => 'நாட்களின் எண்ணிக்கை', 'form.create' => 'திட்டத்தை உருவாக்கு', 'form.saveChanges' => 'மாற்றங்களைச் சேமி',
        'form.resetNote' => 'வரம்பு அல்லது வாசிப்பு அட்டவணையை மாற்றினால் இந்தத் திட்டத்தின் முன்னேற்றம் மீட்டமைக்கப்படும்.',
        'scope.whole' => 'முழு வேதாகமம்', 'scope.ot' => 'பழைய ஏற்பாடு', 'scope.nt' => 'புதிய ஏற்பாடு', 'scope.books' => 'தேர்ந்தெடுத்த புத்தகங்கள்',
        'scope.chapters' => 'தேர்ந்தெடுத்த அதிகாரங்கள்', 'scope.two-books' => 'காலையில் ஒரு புத்தகம், மாலையில் வேறு புத்தகம்',
        'mode.continuous' => 'தொடர்ச்சியாக', 'mode.perday' => 'ஒரு நாளுக்கு X அதிகாரங்கள் வாசிக்க', 'mode.days' => 'X நாட்களில் முடிக்க',
        'testament.ot' => 'பழைய ஏற்பாடு', 'testament.nt' => 'புதிய ஏற்பாடு',
        'err.selectBook' => 'குறைந்தது ஒரு புத்தகத்தைத் தேர்ந்தெடுக்கவும்.', 'err.addRange' => 'குறைந்தது ஒரு அதிகார வரம்பைச் சேர்க்கவும்.',
        'err.twoBooks' => 'காலைக்கு ஒன்றும் மாலைக்கு ஒன்றுமாக குறைந்தது ஒவ்வொரு புத்தகத்தைத் தேர்ந்தெடுக்கவும்.', 'err.session' => 'அமர்வு காலாவதியானது. மீண்டும் முயற்சிக்கவும்.',
        'err.empty' => 'இந்தத் தேர்வு வெற்றுத் திட்டத்தை உருவாக்குகிறது.',
        'err.dailyLimit' => 'ஒரு நாளில் {n} அதிகாரங்களுக்கு மேல் வாசித்ததாகக் குறிக்க முடியாது (சங்கீதம் தவிர). முதலில் வாசியுங்கள் — மேலும் குறிக்க நாளை வாருங்கள்.',

        'sum.perDay1' => 'ஒரு நாளுக்கு 1 அதிகாரம்', 'sum.perDayN' => 'ஒரு நாளுக்கு {n} அதிகாரங்கள்', 'sum.inDays' => '{n} நாட்களில்',
        'sum.continuous' => 'தொடர்ச்சியாக', 'sum.me' => 'காலை & மாலை', 'sum.morning' => 'காலை', 'sum.evening' => 'மாலை',

        'tracker.plans' => 'திட்டங்கள்', 'tracker.selectAll' => 'அனைத்தையும் தேர்ந்தெடு', 'tracker.scheduleDays' => 'தினசரி அட்டவணை ({n} நாட்கள்)',
        'tracker.scheduleReadings' => 'தினசரி அட்டவணை ({n} வாசிப்புகள்)', 'tracker.day' => 'நாள்',

        'reader.translation' => 'வேதாகம மொழிபெயர்ப்பு', 'reader.smaller' => 'சிறிய எழுத்து', 'reader.larger' => 'பெரிய எழுத்து',
        'reader.reset' => 'எழுத்தளவை மீட்டமை', 'reader.markRead' => 'வாசித்ததாகக் குறி', 'reader.read' => 'வாசித்தது',
        'reader.quiz' => 'வினாடி வினா எழுது', 'reader.hideQuiz' => 'வினாடி வினாவை மறை', 'reader.next' => 'அடுத்தது: {ref}',
        'reader.planComplete' => 'திட்டம் முடிந்தது 🎉 முன்னேற்றத்தைப் பார்', 'reader.loading' => 'ஏற்றுகிறது…',
        'reader.loadError' => 'இணைப்பைச் சரிபார்த்து மீண்டும் முயற்சிக்கவும்.', 'reader.saveError' => 'முன்னேற்றத்தைச் சேமிக்க முடியவில்லை. மீண்டும் முயற்சிக்கவும்.',

        'dl.title' => 'திட்டங்களைப் பதிவிறக்கு',
        'dl.desc' => 'இலவச, தயாரான வாசிப்புத் திட்டங்கள் — அச்சிடலாம் அல்லது Excel / Google Sheets-இல் திறக்கலாம் (CSV).',
        'dl.days' => '{n} நாட்கள்', 'dl.download' => 'பதிவிறக்கு', 'dl.cta' => 'முன்னேற்றக் கண்காணிப்புடன் சொந்தத் திட்டம் வேண்டுமா?',
        'dl.csvDay' => 'நாள்', 'dl.csvReading' => 'வாசிப்பு', 'dl.csvDone' => 'முடிந்தது',
        'pdf.optionalSection' => 'விருப்பத்தேர்வு',
        'dl.customTitle' => 'உங்கள் சொந்த அச்சிடக்கூடிய திட்டத்தை உருவாக்குங்கள்', 'dl.customDesc' => 'மொழி, வரம்பு, வாசிக்கும் முறையைத் தேர்ந்தெடுத்து, தலைப்பைச் சேர்த்து, ஒவ்வொரு வாசிப்புக்கும் குறிப்புப் பெட்டியுடன் இரண்டு பத்திகள் கொண்ட A4 PDF உருவாக்குங்கள்.', 'dl.readyMadeTitle' => 'தயாரான திட்டங்கள்', 'pdf.header' => 'தலைப்பு', 'pdf.headerPh' => 'எ.கா. ஓராண்டு வாசிப்புத் திட்டம், சபையின் பெயர், முதலியன', 'pdf.subheader' => 'துணைத் தலைப்பு', 'pdf.subheaderPh' => 'எ.கா. தினமும் சிறிது வாசித்து தேவனுடைய வார்த்தையில் வளருங்கள்', 'pdf.create' => 'PDF உருவாக்கு', 'pdf.creating' => 'PDF உருவாக்கப்படுகிறது…', 'pdf.ready' => 'உங்கள் PDF தயார்', 'pdf.download' => 'PDF பதிவிறக்கு', 'pdf.error' => 'PDF உருவாக்க முடியவில்லை. மீண்டும் முயற்சிக்கவும்.', 'pdf.tooMany' => 'ஒரு PDF-க்கு இந்தத் திட்டத்தில் வாசிப்புகள் மிக அதிகம். நாளுக்கு அதிக அதிகாரங்களைத் தேர்ந்தெடுக்கவும் அல்லது நாட்களைக் குறைக்கவும்.', 'pdf.page' => 'பக்கம் {n}',
        'preset.whole-365' => '1 ஆண்டில் முழு வேதாகமம்', 'preset.whole-365-me' => '1 ஆண்டில் முழு வேதாகமம் (காலை & மாலை)',
        'preset.whole-180' => '6 மாதங்களில் முழு வேதாகமம்', 'preset.ot-365' => '1 ஆண்டில் பழைய ஏற்பாடு',
        'preset.nt-90' => '90 நாட்களில் புதிய ஏற்பாடு', 'preset.nt-30' => '30 நாட்களில் புதிய ஏற்பாடு',
        'preset.gospels-30' => '30 நாட்களில் நான்கு சுவிசேஷங்கள்', 'preset.psalms-proverbs' => 'சங்கீதம் & நீதிமொழிகள் - ஒரு நாளுக்கு 1 அதிகாரம்',

        'ft.visitors' => 'Visitors', 'ft.downloads' => 'Downloads', 'ft.signedIn' => 'Signed-in Users', 'ft.plansCreated' => 'Plans Created',
        'ft.booksRead' => 'Books Read', 'ft.chaptersRead' => 'Chapters Read', 'ft.quizzes' => 'Quizzes Taken',
        'ft.topLanguage' => 'Top Language', 'ft.top3Books' => 'Top 3 Books', 'ft.topScope' => 'Top Scope', 'ft.topReading' => 'Top Reading Method',
        'install.ios' => 'Install: Tap the Share button, then "Add to Home Screen".',
    ];

    $all['hi'] = [
        'site.brand' => 'Bible Tracker',
        'site.tagline' => 'Read the Bible. Track Your Progress. Grow in God\'s Word.',
        'nav.home' => 'होम', 'nav.menu' => 'मेनू', 'nav.signOut' => 'साइन आउट', 'nav.installApp' => 'ऐप इंस्टॉल करें',
        'nav.myAchievements' => 'मेरी उपलब्धियाँ', 'nav.theme' => 'डार्क / लाइट थीम', 'nav.language' => 'भाषा',
        'site.verse' => 'कोई कॉपीराइट नहीं, “तुम ने सेंत-मेंत पाया है, सेंत-मेंत दो” — मत्ती 10:8',

        'home.lead' => 'नियमित रूप से बाइबल पढ़ें। तैयार पठन योजना चुनें, या अपनी योजना बनाने और ट्रैक करने के लिए साइन इन करें।',
        'home.downloadTitle' => 'योजनाएँ डाउनलोड करें',
        'home.downloadDesc' => 'अपने शीर्षक के साथ प्रिंट करने योग्य A4 पठन योजना (PDF) बनाएँ। साइन इन की आवश्यकता नहीं।',
        'home.createTitle' => 'अपनी योजनाएँ बनाएँ और ट्रैक करें',
        'home.createDesc' => 'Google से साइन इन करके अपनी योजनाएँ बनाएँ, हर अध्याय पर निशान लगाएँ और अपनी प्रगति देखें।',
        'home.signIn' => 'Google से साइन इन करें', 'home.signInUnavailable' => 'साइन इन उपलब्ध नहीं',
        'home.signInUnavailableNote' => 'साझा साइन-इन सेवा यहाँ इंस्टॉल नहीं है।',
        'home.myPlans' => 'मेरी योजनाएँ', 'home.welcome' => 'स्वागत है, {name}।', 'home.newPlan' => 'नई योजना',
        'home.planDeleted' => 'योजना हटा दी गई।', 'home.planSaved' => 'योजना सहेज ली गई।',
        'home.noPlans' => 'अभी आपकी कोई पठन योजना नहीं है।', 'home.createFirst' => 'अपनी पहली योजना बनाएँ',
        'home.downloadReady' => 'प्रिंट करने योग्य योजना बनाएँ',

        'plan.read' => 'पढ़ें', 'plan.track' => 'ट्रैक करें', 'plan.edit' => 'संपादित करें', 'plan.delete' => 'हटाएँ',
        'plan.deleteConfirm' => 'क्या यह योजना और इसकी प्रगति हटा दें?', 'plan.completed' => 'पूर्ण 🎉',
        'plan.progress' => '{done} / {total} अध्याय · {pct}%',

        'lb.title' => 'लीडरबोर्ड', 'lb.subtitle' => 'पढ़े गए अध्याय', 'lb.you' => 'आप', 'lb.showAll' => 'सभी दिखाएँ',
        'lb.top10' => 'शीर्ष 10', 'lb.rank' => 'रैंक', 'lb.name' => 'नाम', 'lb.chaptersRead' => 'पढ़े गए अध्याय',
        'lb.empty' => 'लीडरबोर्ड पर अभी कोई पाठक नहीं है। अध्यायों को पढ़ा हुआ चिह्नित करके पहले बनें!',
        'ach.title' => 'उपलब्धियाँ',
        'ach.intro' => 'आपका हर पढ़ा हुआ अध्याय आपके कुल में जुड़ता है और सितारे रोशन करता है — सिल्वर से डायमंड तक।',
        'ach.banner' => '{name} ने {tier} ★{n} अर्जित किया है — पढ़ते रहें!',
        'ach.booksCompleted' => 'पूरी की गई पुस्तकें', 'ach.rank' => 'लीडरबोर्ड रैंक', 'ach.editName' => 'नाम संपादित करें',
        'ach.toGo' => '{n} बाकी', 'ach.completed' => '✓ पूर्ण', 'ach.modalHelp' => 'यह नाम सार्वजनिक लीडरबोर्ड पर दिखाया जाता है। अपना Google नाम उपयोग करने के लिए खाली छोड़ें।',
        'ach.displayName' => 'प्रदर्शित नाम', 'ach.saveError' => 'सहेज नहीं सके। कृपया फिर से प्रयास करें।',
        'tier.silver' => 'सिल्वर', 'tier.bronze' => 'ब्रॉन्ज़', 'tier.gold' => 'गोल्ड', 'tier.platinum' => 'प्लैटिनम', 'tier.diamond' => 'डायमंड',
        'common.save' => 'सहेजें', 'common.cancel' => 'रद्द करें',

        'form.newPlan' => 'नई योजना', 'form.editPlan' => 'योजना संपादित करें', 'form.planName' => 'योजना का नाम', 'form.optional' => '(वैकल्पिक)',
        'form.planNamePh' => 'जैसे: एक वर्ष में बाइबल', 'form.language' => 'भाषा', 'form.languageHelp' => 'आपकी योजना और समय-सारणी में पुस्तकों के नाम।',
        'form.scope' => 'दायरा', 'form.tickBooks' => 'शामिल करने वाली पुस्तकों पर निशान लगाएँ।', 'form.addRanges' => 'एक या अधिक अध्याय सीमाएँ जोड़ें।',
        'form.to' => 'से', 'form.addRange' => 'सीमा जोड़ें', 'form.pickSession' => 'हर सत्र में पढ़ने के लिए पुस्तकें चुनें।',
        'form.morningBooks' => 'सुबह की पुस्तक(एँ)', 'form.eveningBooks' => 'शाम की पुस्तक(एँ)', 'form.howToRead' => 'कैसे पढ़ें?',
        'form.perDay' => 'प्रति दिन अध्याय', 'form.numDays' => 'दिनों की संख्या', 'form.create' => 'योजना बनाएँ', 'form.saveChanges' => 'बदलाव सहेजें',
        'form.resetNote' => 'दायरा या पठन समय-सारणी बदलने पर इस योजना की प्रगति रीसेट हो जाएगी।',
        'scope.whole' => 'पूरी बाइबल', 'scope.ot' => 'पुराना नियम', 'scope.nt' => 'नया नियम', 'scope.books' => 'चुनी हुई पुस्तकें',
        'scope.chapters' => 'चुने हुए अध्याय', 'scope.two-books' => 'सुबह एक पुस्तक, शाम को दूसरी पुस्तक',
        'mode.continuous' => 'लगातार', 'mode.perday' => 'प्रति दिन X अध्याय पढ़ें', 'mode.days' => 'X दिनों में पूरा करें',
        'testament.ot' => 'पुराना नियम', 'testament.nt' => 'नया नियम',
        'err.selectBook' => 'कम से कम एक पुस्तक चुनें।', 'err.addRange' => 'कम से कम एक अध्याय सीमा जोड़ें।',
        'err.twoBooks' => 'सुबह के लिए कम से कम एक और शाम के लिए कम से कम एक पुस्तक चुनें।', 'err.session' => 'सत्र समाप्त हो गया। कृपया फिर से प्रयास करें।',
        'err.empty' => 'इस चयन से खाली योजना बनती है।',
        'err.dailyLimit' => 'आप एक दिन में {n} से अधिक अध्याय पढ़े हुए चिह्नित नहीं कर सकते (भजन संहिता को छोड़कर)। पहले उन्हें पढ़ें — और अधिक के लिए कल आएँ।',

        'sum.perDay1' => 'प्रति दिन 1 अध्याय', 'sum.perDayN' => 'प्रति दिन {n} अध्याय', 'sum.inDays' => '{n} दिनों में',
        'sum.continuous' => 'लगातार', 'sum.me' => 'सुबह और शाम', 'sum.morning' => 'सुबह', 'sum.evening' => 'शाम',

        'tracker.plans' => 'योजनाएँ', 'tracker.selectAll' => 'सभी चुनें', 'tracker.scheduleDays' => 'दैनिक समय-सारणी ({n} दिन)',
        'tracker.scheduleReadings' => 'दैनिक समय-सारणी ({n} पठन)', 'tracker.day' => 'दिन',

        'reader.translation' => 'बाइबल अनुवाद', 'reader.smaller' => 'छोटा टेक्स्ट', 'reader.larger' => 'बड़ा टेक्स्ट',
        'reader.reset' => 'टेक्स्ट आकार रीसेट करें', 'reader.markRead' => 'पढ़ा हुआ चिह्नित करें', 'reader.read' => 'पढ़ा',
        'reader.quiz' => 'क्विज़ दें', 'reader.hideQuiz' => 'क्विज़ छिपाएँ', 'reader.next' => 'अगला: {ref}',
        'reader.planComplete' => 'योजना पूर्ण 🎉 प्रगति देखें', 'reader.loading' => 'लोड हो रहा है…',
        'reader.loadError' => 'अपना कनेक्शन जाँचें और फिर प्रयास करें।', 'reader.saveError' => 'प्रगति सहेज नहीं सके। कृपया फिर से प्रयास करें।',

        'dl.title' => 'योजनाएँ डाउनलोड करें',
        'dl.desc' => 'निःशुल्क, तैयार पठन योजनाएँ स्प्रेडशीट (CSV) के रूप में — प्रिंट करें या Excel / Google Sheets में खोलें।',
        'dl.days' => '{n} दिन', 'dl.download' => 'डाउनलोड', 'dl.cta' => 'प्रगति ट्रैकिंग के साथ अपनी योजना चाहिए?',
        'dl.csvDay' => 'दिन', 'dl.csvReading' => 'पठन', 'dl.csvDone' => 'पूर्ण',
        'pdf.optionalSection' => 'वैकल्पिक',
        'dl.customTitle' => 'अपनी खुद की प्रिंट करने योग्य योजना बनाएँ', 'dl.customDesc' => 'भाषा, दायरा और पढ़ने की गति चुनें, शीर्षक जोड़ें, और हर पठन के लिए टिक बॉक्स वाला दो कॉलम का A4 PDF बनाएँ।', 'dl.readyMadeTitle' => 'तैयार योजनाएँ', 'pdf.header' => 'शीर्षक', 'pdf.headerPh' => 'जैसे: एक वर्ष की पठन योजना, चर्च का नाम, आदि', 'pdf.subheader' => 'उप-शीर्षक', 'pdf.subheaderPh' => 'जैसे: हर दिन थोड़ा पढ़ें और परमेश्वर के वचन में बढ़ें', 'pdf.create' => 'PDF बनाएँ', 'pdf.creating' => 'PDF बनाई जा रही है…', 'pdf.ready' => 'आपकी PDF तैयार है', 'pdf.download' => 'PDF डाउनलोड करें', 'pdf.error' => 'PDF नहीं बन सकी। कृपया फिर से प्रयास करें।', 'pdf.tooMany' => 'एक PDF के लिए इस योजना में बहुत अधिक पठन हैं। प्रति दिन अधिक अध्याय चुनें या दिन कम करें।', 'pdf.page' => 'पृष्ठ {n}',
        'preset.whole-365' => '1 वर्ष में पूरी बाइबल', 'preset.whole-365-me' => '1 वर्ष में पूरी बाइबल (सुबह और शाम)',
        'preset.whole-180' => '6 महीने में पूरी बाइबल', 'preset.ot-365' => '1 वर्ष में पुराना नियम',
        'preset.nt-90' => '90 दिनों में नया नियम', 'preset.nt-30' => '30 दिनों में नया नियम',
        'preset.gospels-30' => '30 दिनों में चारों सुसमाचार', 'preset.psalms-proverbs' => 'भजन संहिता और नीतिवचन - प्रतिदिन 1 अध्याय',

        'ft.visitors' => 'Visitors', 'ft.downloads' => 'Downloads', 'ft.signedIn' => 'Signed-in Users', 'ft.plansCreated' => 'Plans Created',
        'ft.booksRead' => 'Books Read', 'ft.chaptersRead' => 'Chapters Read', 'ft.quizzes' => 'Quizzes Taken',
        'ft.topLanguage' => 'Top Language', 'ft.top3Books' => 'Top 3 Books', 'ft.topScope' => 'Top Scope', 'ft.topReading' => 'Top Reading Method',
        'install.ios' => 'इंस्टॉल करने के लिए: शेयर बटन दबाएँ, फिर "होम स्क्रीन पर जोड़ें" चुनें।',
    ];

    $all['kn'] = [
        'site.brand' => 'Bible Tracker',
        'site.tagline' => 'Read the Bible. Track Your Progress. Grow in God\'s Word.',
        'nav.home' => 'ಮುಖಪುಟ', 'nav.menu' => 'ಮೆನು', 'nav.signOut' => 'ಸೈನ್ ಔಟ್', 'nav.installApp' => 'ಆ್ಯಪ್ ಇನ್‌ಸ್ಟಾಲ್ ಮಾಡಿ',
        'nav.myAchievements' => 'ನನ್ನ ಸಾಧನೆಗಳು', 'nav.theme' => 'ಡಾರ್ಕ್ / ಲೈಟ್ ಥೀಮ್', 'nav.language' => 'ಭಾಷೆ',
        'site.verse' => 'ಯಾವುದೇ ಹಕ್ಕುಸ್ವಾಮ್ಯವಿಲ್ಲ, “ನೀವು ಉಚಿತವಾಗಿ ಪಡೆದಿದ್ದೀರಿ, ಉಚಿತವಾಗಿ ಕೊಡಿರಿ” — ಮತ್ತಾಯ 10:8',

        'home.lead' => 'ಬೈಬಲ್ ಅನ್ನು ನಿಯಮಿತವಾಗಿ ಓದಿರಿ. ಸಿದ್ಧ ಓದುವ ಯೋಜನೆಯನ್ನು ಆರಿಸಿ, ಅಥವಾ ನಿಮ್ಮದೇ ಯೋಜನೆಯನ್ನು ರಚಿಸಿ ಟ್ರ್ಯಾಕ್ ಮಾಡಲು ಸೈನ್ ಇನ್ ಮಾಡಿ.',
        'home.downloadTitle' => 'ಯೋಜನೆಗಳನ್ನು ಡೌನ್‌ಲೋಡ್ ಮಾಡಿ',
        'home.downloadDesc' => 'ನಿಮ್ಮ ಶೀರ್ಷಿಕೆಯೊಂದಿಗೆ ಮುದ್ರಿಸಬಹುದಾದ A4 ಓದುವ ಯೋಜನೆಯನ್ನು (PDF) ರಚಿಸಿ. ಸೈನ್ ಇನ್ ಅಗತ್ಯವಿಲ್ಲ.',
        'home.createTitle' => 'ನಿಮ್ಮದೇ ಯೋಜನೆಗಳನ್ನು ರಚಿಸಿ ಮತ್ತು ಟ್ರ್ಯಾಕ್ ಮಾಡಿ',
        'home.createDesc' => 'Google ಮೂಲಕ ಸೈನ್ ಇನ್ ಮಾಡಿ ನಿಮ್ಮ ಯೋಜನೆಗಳನ್ನು ರಚಿಸಿ, ಪ್ರತಿ ಅಧ್ಯಾಯವನ್ನು ಗುರುತಿಸಿ ಮತ್ತು ಪ್ರಗತಿಯನ್ನು ನೋಡಿ.',
        'home.signIn' => 'Google ಮೂಲಕ ಸೈನ್ ಇನ್ ಮಾಡಿ', 'home.signInUnavailable' => 'ಸೈನ್ ಇನ್ ಲಭ್ಯವಿಲ್ಲ',
        'home.signInUnavailableNote' => 'ಹಂಚಿಕೆಯ ಸೈನ್-ಇನ್ ಸೇವೆಯನ್ನು ಇಲ್ಲಿ ಇನ್‌ಸ್ಟಾಲ್ ಮಾಡಿಲ್ಲ.',
        'home.myPlans' => 'ನನ್ನ ಯೋಜನೆಗಳು', 'home.welcome' => 'ಸ್ವಾಗತ, {name}.', 'home.newPlan' => 'ಹೊಸ ಯೋಜನೆ',
        'home.planDeleted' => 'ಯೋಜನೆಯನ್ನು ಅಳಿಸಲಾಗಿದೆ.', 'home.planSaved' => 'ಯೋಜನೆಯನ್ನು ಉಳಿಸಲಾಗಿದೆ.',
        'home.noPlans' => 'ನಿಮಗೆ ಇನ್ನೂ ಓದುವ ಯೋಜನೆಗಳಿಲ್ಲ.', 'home.createFirst' => 'ನಿಮ್ಮ ಮೊದಲ ಯೋಜನೆಯನ್ನು ರಚಿಸಿ',
        'home.downloadReady' => 'ಮುದ್ರಿಸಬಹುದಾದ ಯೋಜನೆ ರಚಿಸಿ',

        'plan.read' => 'ಓದಿ', 'plan.track' => 'ಟ್ರ್ಯಾಕ್', 'plan.edit' => 'ಸಂಪಾದಿಸಿ', 'plan.delete' => 'ಅಳಿಸಿ',
        'plan.deleteConfirm' => 'ಈ ಯೋಜನೆ ಮತ್ತು ಅದರ ಪ್ರಗತಿಯನ್ನು ಅಳಿಸಬೇಕೆ?', 'plan.completed' => 'ಪೂರ್ಣಗೊಂಡಿದೆ 🎉',
        'plan.progress' => '{done} / {total} ಅಧ್ಯಾಯಗಳು · {pct}%',

        'lb.title' => 'ಲೀಡರ್‌ಬೋರ್ಡ್', 'lb.subtitle' => 'ಓದಿದ ಅಧ್ಯಾಯಗಳು', 'lb.you' => 'ನೀವು', 'lb.showAll' => 'ಎಲ್ಲವನ್ನೂ ತೋರಿಸಿ',
        'lb.top10' => 'ಅಗ್ರ 10', 'lb.rank' => 'ಶ್ರೇಣಿ', 'lb.name' => 'ಹೆಸರು', 'lb.chaptersRead' => 'ಓದಿದ ಅಧ್ಯಾಯಗಳು',
        'lb.empty' => 'ಲೀಡರ್‌ಬೋರ್ಡ್‌ನಲ್ಲಿ ಇನ್ನೂ ಯಾರೂ ಇಲ್ಲ. ಅಧ್ಯಾಯಗಳನ್ನು ಓದಿದ್ದೆಂದು ಗುರುತಿಸಿ ಮೊದಲಿಗರಾಗಿ!',
        'ach.title' => 'ಸಾಧನೆಗಳು',
        'ach.intro' => 'ನೀವು ಓದಿದ್ದೆಂದು ಗುರುತಿಸುವ ಪ್ರತಿ ಅಧ್ಯಾಯವೂ ನಿಮ್ಮ ಒಟ್ಟು ಮೊತ್ತಕ್ಕೆ ಸೇರಿ ನಕ್ಷತ್ರಗಳನ್ನು ಬೆಳಗಿಸುತ್ತದೆ — ಬೆಳ್ಳಿಯಿಂದ ವಜ್ರದವರೆಗೆ.',
        'ach.banner' => '{name} ಅವರು {tier} ★{n} ಗಳಿಸಿದ್ದಾರೆ — ಓದುತ್ತಲೇ ಇರಿ!',
        'ach.booksCompleted' => 'ಪೂರ್ಣಗೊಳಿಸಿದ ಪುಸ್ತಕಗಳು', 'ach.rank' => 'ಲೀಡರ್‌ಬೋರ್ಡ್ ಶ್ರೇಣಿ', 'ach.editName' => 'ಹೆಸರು ಸಂಪಾದಿಸಿ',
        'ach.toGo' => 'ಇನ್ನೂ {n}', 'ach.completed' => '✓ ಪೂರ್ಣಗೊಂಡಿದೆ', 'ach.modalHelp' => 'ಈ ಹೆಸರು ಸಾರ್ವಜನಿಕ ಲೀಡರ್‌ಬೋರ್ಡ್‌ನಲ್ಲಿ ತೋರಿಸಲ್ಪಡುತ್ತದೆ. ನಿಮ್ಮ Google ಹೆಸರನ್ನು ಬಳಸಲು ಖಾಲಿ ಬಿಡಿ.',
        'ach.displayName' => 'ಪ್ರದರ್ಶನ ಹೆಸರು', 'ach.saveError' => 'ಉಳಿಸಲು ಸಾಧ್ಯವಾಗಲಿಲ್ಲ. ದಯವಿಟ್ಟು ಮತ್ತೆ ಪ್ರಯತ್ನಿಸಿ.',
        'tier.silver' => 'ಬೆಳ್ಳಿ', 'tier.bronze' => 'ಕಂಚು', 'tier.gold' => 'ಚಿನ್ನ', 'tier.platinum' => 'ಪ್ಲಾಟಿನಂ', 'tier.diamond' => 'ವಜ್ರ',
        'common.save' => 'ಉಳಿಸಿ', 'common.cancel' => 'ರದ್ದುಮಾಡಿ',

        'form.newPlan' => 'ಹೊಸ ಯೋಜನೆ', 'form.editPlan' => 'ಯೋಜನೆ ಸಂಪಾದಿಸಿ', 'form.planName' => 'ಯೋಜನೆಯ ಹೆಸರು', 'form.optional' => '(ಐಚ್ಛಿಕ)',
        'form.planNamePh' => 'ಉದಾ. ಒಂದು ವರ್ಷದಲ್ಲಿ ಬೈಬಲ್', 'form.language' => 'ಭಾಷೆ', 'form.languageHelp' => 'ನಿಮ್ಮ ಯೋಜನೆ ಮತ್ತು ವೇಳಾಪಟ್ಟಿಯಲ್ಲಿ ಪುಸ್ತಕಗಳ ಹೆಸರುಗಳು.',
        'form.scope' => 'ವ್ಯಾಪ್ತಿ', 'form.tickBooks' => 'ಸೇರಿಸಬೇಕಾದ ಪುಸ್ತಕಗಳನ್ನು ಗುರುತಿಸಿ.', 'form.addRanges' => 'ಒಂದು ಅಥವಾ ಹೆಚ್ಚು ಅಧ್ಯಾಯ ವ್ಯಾಪ್ತಿಗಳನ್ನು ಸೇರಿಸಿ.',
        'form.to' => 'ರಿಂದ', 'form.addRange' => 'ವ್ಯಾಪ್ತಿ ಸೇರಿಸಿ', 'form.pickSession' => 'ಪ್ರತಿ ಅವಧಿಯಲ್ಲಿ ಓದಬೇಕಾದ ಪುಸ್ತಕ(ಗಳನ್ನು) ಆರಿಸಿ.',
        'form.morningBooks' => 'ಬೆಳಗಿನ ಪುಸ್ತಕ(ಗಳು)', 'form.eveningBooks' => 'ಸಂಜೆಯ ಪುಸ್ತಕ(ಗಳು)', 'form.howToRead' => 'ಹೇಗೆ ಓದುವುದು?',
        'form.perDay' => 'ದಿನಕ್ಕೆ ಅಧ್ಯಾಯಗಳು', 'form.numDays' => 'ದಿನಗಳ ಸಂಖ್ಯೆ', 'form.create' => 'ಯೋಜನೆ ರಚಿಸಿ', 'form.saveChanges' => 'ಬದಲಾವಣೆಗಳನ್ನು ಉಳಿಸಿ',
        'form.resetNote' => 'ವ್ಯಾಪ್ತಿ ಅಥವಾ ಓದುವ ವೇಳಾಪಟ್ಟಿಯನ್ನು ಬದಲಾಯಿಸಿದರೆ ಈ ಯೋಜನೆಯ ಪ್ರಗತಿ ಮರುಹೊಂದಿಸಲ್ಪಡುತ್ತದೆ.',
        'scope.whole' => 'ಸಂಪೂರ್ಣ ಬೈಬಲ್', 'scope.ot' => 'ಹಳೆಯ ಒಡಂಬಡಿಕೆ', 'scope.nt' => 'ಹೊಸ ಒಡಂಬಡಿಕೆ', 'scope.books' => 'ಆಯ್ದ ಪುಸ್ತಕಗಳು',
        'scope.chapters' => 'ಆಯ್ದ ಅಧ್ಯಾಯಗಳು', 'scope.two-books' => 'ಬೆಳಿಗ್ಗೆ ಒಂದು ಪುಸ್ತಕ, ಸಂಜೆ ಬೇರೆ ಪುಸ್ತಕ',
        'mode.continuous' => 'ನಿರಂತರ', 'mode.perday' => 'ದಿನಕ್ಕೆ X ಅಧ್ಯಾಯಗಳನ್ನು ಓದಿ', 'mode.days' => 'X ದಿನಗಳಲ್ಲಿ ಪೂರ್ಣಗೊಳಿಸಿ',
        'testament.ot' => 'ಹಳೆಯ ಒಡಂಬಡಿಕೆ', 'testament.nt' => 'ಹೊಸ ಒಡಂಬಡಿಕೆ',
        'err.selectBook' => 'ಕನಿಷ್ಠ ಒಂದು ಪುಸ್ತಕವನ್ನು ಆರಿಸಿ.', 'err.addRange' => 'ಕನಿಷ್ಠ ಒಂದು ಅಧ್ಯಾಯ ವ್ಯಾಪ್ತಿಯನ್ನು ಸೇರಿಸಿ.',
        'err.twoBooks' => 'ಬೆಳಗಿಗೆ ಕನಿಷ್ಠ ಒಂದು ಮತ್ತು ಸಂಜೆಗೆ ಕನಿಷ್ಠ ಒಂದು ಪುಸ್ತಕವನ್ನು ಆರಿಸಿ.', 'err.session' => 'ಸೆಷನ್ ಅವಧಿ ಮುಗಿದಿದೆ. ದಯವಿಟ್ಟು ಮತ್ತೆ ಪ್ರಯತ್ನಿಸಿ.',
        'err.empty' => 'ಈ ಆಯ್ಕೆಯಿಂದ ಖಾಲಿ ಯೋಜನೆ ಆಗುತ್ತದೆ.',
        'err.dailyLimit' => 'ಒಂದು ದಿನದಲ್ಲಿ {n} ಕ್ಕಿಂತ ಹೆಚ್ಚು ಅಧ್ಯಾಯಗಳನ್ನು ಓದಿದ್ದೆಂದು ಗುರುತಿಸಲು ಸಾಧ್ಯವಿಲ್ಲ (ಕೀರ್ತನೆಗಳನ್ನು ಹೊರತುಪಡಿಸಿ). ಮೊದಲು ಅವುಗಳನ್ನು ಓದಿ — ಇನ್ನಷ್ಟಕ್ಕೆ ನಾಳೆ ಬನ್ನಿ.',

        'sum.perDay1' => 'ದಿನಕ್ಕೆ 1 ಅಧ್ಯಾಯ', 'sum.perDayN' => 'ದಿನಕ್ಕೆ {n} ಅಧ್ಯಾಯಗಳು', 'sum.inDays' => '{n} ದಿನಗಳಲ್ಲಿ',
        'sum.continuous' => 'ನಿರಂತರ', 'sum.me' => 'ಬೆಳಿಗ್ಗೆ ಮತ್ತು ಸಂಜೆ', 'sum.morning' => 'ಬೆಳಿಗ್ಗೆ', 'sum.evening' => 'ಸಂಜೆ',

        'tracker.plans' => 'ಯೋಜನೆಗಳು', 'tracker.selectAll' => 'ಎಲ್ಲವನ್ನೂ ಆರಿಸಿ', 'tracker.scheduleDays' => 'ದೈನಂದಿನ ವೇಳಾಪಟ್ಟಿ ({n} ದಿನಗಳು)',
        'tracker.scheduleReadings' => 'ದೈನಂದಿನ ವೇಳಾಪಟ್ಟಿ ({n} ಓದುವಿಕೆಗಳು)', 'tracker.day' => 'ದಿನ',

        'reader.translation' => 'ಬೈಬಲ್ ಅನುವಾದ', 'reader.smaller' => 'ಚಿಕ್ಕ ಅಕ್ಷರ', 'reader.larger' => 'ದೊಡ್ಡ ಅಕ್ಷರ',
        'reader.reset' => 'ಅಕ್ಷರ ಗಾತ್ರ ಮರುಹೊಂದಿಸಿ', 'reader.markRead' => 'ಓದಿದ್ದೆಂದು ಗುರುತಿಸಿ', 'reader.read' => 'ಓದಿದೆ',
        'reader.quiz' => 'ರಸಪ್ರಶ್ನೆ ಬರೆಯಿರಿ', 'reader.hideQuiz' => 'ರಸಪ್ರಶ್ನೆ ಮರೆಮಾಡಿ', 'reader.next' => 'ಮುಂದಿನದು: {ref}',
        'reader.planComplete' => 'ಯೋಜನೆ ಪೂರ್ಣಗೊಂಡಿದೆ 🎉 ಪ್ರಗತಿ ನೋಡಿ', 'reader.loading' => 'ಲೋಡ್ ಆಗುತ್ತಿದೆ…',
        'reader.loadError' => 'ನಿಮ್ಮ ಸಂಪರ್ಕವನ್ನು ಪರಿಶೀಲಿಸಿ ಮತ್ತೆ ಪ್ರಯತ್ನಿಸಿ.', 'reader.saveError' => 'ಪ್ರಗತಿಯನ್ನು ಉಳಿಸಲು ಸಾಧ್ಯವಾಗಲಿಲ್ಲ. ದಯವಿಟ್ಟು ಮತ್ತೆ ಪ್ರಯತ್ನಿಸಿ.',

        'dl.title' => 'ಯೋಜನೆಗಳನ್ನು ಡೌನ್‌ಲೋಡ್ ಮಾಡಿ',
        'dl.desc' => 'ಉಚಿತ, ಸಿದ್ಧ ಓದುವ ಯೋಜನೆಗಳು ಸ್ಪ್ರೆಡ್‌ಶೀಟ್ (CSV) ಆಗಿ — ಮುದ್ರಿಸಿ ಅಥವಾ Excel / Google Sheets ನಲ್ಲಿ ತೆರೆಯಿರಿ.',
        'dl.days' => '{n} ದಿನಗಳು', 'dl.download' => 'ಡೌನ್‌ಲೋಡ್', 'dl.cta' => 'ಪ್ರಗತಿ ಟ್ರ್ಯಾಕಿಂಗ್‌ನೊಂದಿಗೆ ನಿಮ್ಮದೇ ಯೋಜನೆ ಬೇಕೇ?',
        'dl.csvDay' => 'ದಿನ', 'dl.csvReading' => 'ಓದುವಿಕೆ', 'dl.csvDone' => 'ಪೂರ್ಣ',
        'pdf.optionalSection' => 'ಐಚ್ಛಿಕ',
        'dl.customTitle' => 'ನಿಮ್ಮದೇ ಮುದ್ರಿಸಬಹುದಾದ ಯೋಜನೆಯನ್ನು ರಚಿಸಿ', 'dl.customDesc' => 'ಭಾಷೆ, ವ್ಯಾಪ್ತಿ ಮತ್ತು ಓದುವ ವೇಗವನ್ನು ಆರಿಸಿ, ಶೀರ್ಷಿಕೆ ಸೇರಿಸಿ, ಪ್ರತಿ ಓದುವಿಕೆಗೆ ಟಿಕ್ ಬಾಕ್ಸ್ ಇರುವ ಎರಡು ಕಾಲಂಗಳ A4 PDF ರಚಿಸಿ.', 'dl.readyMadeTitle' => 'ಸಿದ್ಧ ಯೋಜನೆಗಳು', 'pdf.header' => 'ಶೀರ್ಷಿಕೆ', 'pdf.headerPh' => 'ಉದಾ. ಒಂದು ವರ್ಷದ ಓದುವ ಯೋಜನೆ, ಚರ್ಚ್ ಹೆಸರು, ಇತ್ಯಾದಿ', 'pdf.subheader' => 'ಉಪ ಶೀರ್ಷಿಕೆ', 'pdf.subheaderPh' => 'ಉದಾ. ಪ್ರತಿದಿನ ಸ್ವಲ್ಪ ಓದಿ ದೇವರ ವಾಕ್ಯದಲ್ಲಿ ಬೆಳೆಯಿರಿ', 'pdf.create' => 'PDF ರಚಿಸಿ', 'pdf.creating' => 'PDF ರಚಿಸಲಾಗುತ್ತಿದೆ…', 'pdf.ready' => 'ನಿಮ್ಮ PDF ಸಿದ್ಧವಾಗಿದೆ', 'pdf.download' => 'PDF ಡೌನ್‌ಲೋಡ್ ಮಾಡಿ', 'pdf.error' => 'PDF ರಚಿಸಲು ಸಾಧ್ಯವಾಗಲಿಲ್ಲ. ದಯವಿಟ್ಟು ಮತ್ತೆ ಪ್ರಯತ್ನಿಸಿ.', 'pdf.tooMany' => 'ಒಂದು PDF ಗೆ ಈ ಯೋಜನೆಯಲ್ಲಿ ತುಂಬಾ ಓದುವಿಕೆಗಳಿವೆ. ದಿನಕ್ಕೆ ಹೆಚ್ಚು ಅಧ್ಯಾಯಗಳನ್ನು ಆರಿಸಿ ಅಥವಾ ದಿನಗಳನ್ನು ಕಡಿಮೆ ಮಾಡಿ.', 'pdf.page' => 'ಪುಟ {n}',
        'preset.whole-365' => '1 ವರ್ಷದಲ್ಲಿ ಸಂಪೂರ್ಣ ಬೈಬಲ್', 'preset.whole-365-me' => '1 ವರ್ಷದಲ್ಲಿ ಸಂಪೂರ್ಣ ಬೈಬಲ್ (ಬೆಳಿಗ್ಗೆ ಮತ್ತು ಸಂಜೆ)',
        'preset.whole-180' => '6 ತಿಂಗಳಲ್ಲಿ ಸಂಪೂರ್ಣ ಬೈಬಲ್', 'preset.ot-365' => '1 ವರ್ಷದಲ್ಲಿ ಹಳೆಯ ಒಡಂಬಡಿಕೆ',
        'preset.nt-90' => '90 ದಿನಗಳಲ್ಲಿ ಹೊಸ ಒಡಂಬಡಿಕೆ', 'preset.nt-30' => '30 ದಿನಗಳಲ್ಲಿ ಹೊಸ ಒಡಂಬಡಿಕೆ',
        'preset.gospels-30' => '30 ದಿನಗಳಲ್ಲಿ ನಾಲ್ಕು ಸುವಾರ್ತೆಗಳು', 'preset.psalms-proverbs' => 'ಕೀರ್ತನೆಗಳು ಮತ್ತು ಜ್ಞಾನೋಕ್ತಿಗಳು - ದಿನಕ್ಕೆ 1 ಅಧ್ಯಾಯ',

        'ft.visitors' => 'Visitors', 'ft.downloads' => 'Downloads', 'ft.signedIn' => 'Signed-in Users', 'ft.plansCreated' => 'Plans Created',
        'ft.booksRead' => 'Books Read', 'ft.chaptersRead' => 'Chapters Read', 'ft.quizzes' => 'Quizzes Taken',
        'ft.topLanguage' => 'Top Language', 'ft.top3Books' => 'Top 3 Books', 'ft.topScope' => 'Top Scope', 'ft.topReading' => 'Top Reading Method',
        'install.ios' => 'ಇನ್‌ಸ್ಟಾಲ್ ಮಾಡಲು: ಶೇರ್ ಬಟನ್ ಒತ್ತಿ, ನಂತರ "ಹೋಮ್ ಸ್ಕ್ರೀನ್‌ಗೆ ಸೇರಿಸಿ" ಆಯ್ಕೆಮಾಡಿ.',
    ];
    return $all;
}

/** All labels for a language, English filling any gaps. @return array<string,string> */
function btLabels(string $lang): array
{
    $all = btLabelsByLanguage();
    return ($all[btLang($lang)] ?? []) + $all['en'];
}

/** The current UI language: signed in → their saved choice (settings.planLang); signed out → bt_lang cookie; else English. */
function btUiLang(): string
{
    static $lang = null;
    if ($lang !== null) {
        return $lang;
    }
    require_once __DIR__ . '/plans.php';
    $saved = null;
    if (function_exists('btGoogleUser') && ($u = btGoogleUser())) {
        $saved = btReadUserData($u)['settings']['planLang'] ?? null;
    }
    return $lang = btLang($saved ?? ($_COOKIE['bt_lang'] ?? 'en'));
}

/** Translates $key into the UI language (English fallback; the key itself if unknown), replacing {placeholders}. */
function btT(string $key, array $vars = [], ?string $lang = null): string
{
    static $cache = [];
    $lang = $lang !== null ? btLang($lang) : btUiLang();
    $text = ($cache[$lang] ??= btLabels($lang))[$key] ?? $key;
    foreach ($vars as $k => $v) {
        $text = str_replace('{' . $k . '}', (string) $v, $text);
    }
    return $text;
}

/** The subset of labels the browser scripts need (alerts, live-updating text). */
function btJsLabels(): array
{
    $keys = ['reader.saveError', 'reader.loadError', 'reader.loading', 'reader.markRead', 'reader.read', 'reader.quiz', 'reader.hideQuiz',
        'ach.saveError', 'install.ios', 'plan.progress', 'pdf.creating', 'pdf.ready', 'pdf.download', 'pdf.error', 'pdf.tooMany'];
    $labels = btLabels(btUiLang());
    return array_intersect_key($labels, array_flip($keys));
}

/** Labels for the live language switch on the plan form (all languages, only the keys that form uses). @return array<string,array<string,string>> */
function btFormLabelsAllLanguages(): array
{
    $out = [];
    foreach (array_keys(BT_LANGUAGES) as $code) {
        $out[$code] = array_filter(
            btLabels($code),
            static fn (string $k): bool => preg_match('/^(form|scope|mode|testament|common|pdf)\./', $k) === 1 || in_array($k, ['home.newPlan', 'dl.customTitle', 'dl.customDesc', 'tracker.selectAll'], true),
            ARRAY_FILTER_USE_KEY
        );
    }
    return $out;
}
