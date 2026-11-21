<?php

/*
 * The stakeholders' archive: every photograph, certificate, video and document
 * supplied by the family, described in English and Arabic and filed in the
 * section where it belongs. Read by Database\Seeders\StakeholderArchiveSeeder;
 * file names are relative to this folder.
 *
 * Facts come from the items themselves (certificate wording, dates and
 * signatures, handwritten labels on the frames). A photo whose year or place is
 * not shown on the item is left without one, never guessed: the admin lists it
 * as incomplete until an editor fills it in.
 */

use App\Constants\CvSection;
use App\Constants\MediaProperties as P;
use App\Constants\MediaTreatment;
use App\Constants\PublicationType;
use App\Constants\TeachingAccess;
use App\Constants\TeachingMaterialType;
use App\Constants\TermVocabulary;
use App\Constants\VideoProvider;

/**
 * One album photo: file, English and Arabic caption, alt text and place, year.
 */
$photo = static fn (string $file, array $caption, array $alt, ?array $place, ?int $year, array $extra = []): array => [
    'file' => 'photos/'.$file,
    'properties' => array_filter([
        P::CAPTION => $caption[0],
        P::ALT_TEXT => $alt[0],
        P::PLACE => $place[0] ?? null,
        P::YEAR => $year,
        P::TRANSLATIONS => ['ar' => array_filter([
            P::CAPTION => $caption[1],
            P::ALT_TEXT => $alt[1],
            P::PLACE => $place[1] ?? null,
        ])],
    ], fn ($value): bool => $value !== null) + $extra,
];

$omdurman = ['Omdurman', 'أم درمان'];
$khartoum = ['Khartoum', 'الخرطوم'];
$cairo = ['Cairo', 'القاهرة'];
$davis = ['Davis, California', 'ديفيس، كاليفورنيا'];
$cambridge = ['Cambridgeshire, England', 'كامبريدجشير، إنجلترا'];
$portrait = [P::TREATMENT => MediaTreatment::Portrait->value];
$ceremonyCredit = [P::CREDIT => 'Reflection'];
// Photographs of his honours are what the home timeline shows beside each decade.
$award = [P::ON_TIMELINE => true];

$ahfadPortrait = $photo(
    'ahfad-2019-portrait.jpg',
    ['Professor Khidir in academic dress at the Ahfad University for Women graduation ceremony', 'البروفيسور محمد عثمان خضر بالزي الأكاديمي في حفل تخريج جامعة الأحفاد للبنات'],
    ['Professor Mohamed Osman Khidir, an elderly man in a white academic gown with gold trim, a dark sash and a striped tie, standing before a decorated stage.', 'البروفيسور محمد عثمان خضر، رجل مسن يرتدي رداءً أكاديمياً أبيض بحواشٍ ذهبية ووشاحاً داكناً وربطة عنق مخططة، يقف أمام منصة مزينة.'],
    // Focus near the top, so crops to a landscape frame keep his face.
    $omdurman, 2019, $portrait + $ceremonyCredit + [P::IS_COVER => true, P::FOCUS_X => 50, P::FOCUS_Y => 18],
);

return [
    // About: the name, title and affiliation the documents give, and the portrait.
    'profile' => [
        'name' => ['en' => 'Mohamed Osman Khidir', 'ar' => 'محمد عثمان خضر'],
        'title' => ['en' => 'Professor Emeritus of Agronomy', 'ar' => 'بروفيسور فخري في علم المحاصيل'],
        'affiliation' => ['en' => 'Faculty of Agriculture, University of Khartoum', 'ar' => 'كلية الزراعة، جامعة الخرطوم'],
    ],
    'portrait' => $ahfadPortrait,

    // Pictures: albums in display order.
    'albums' => [
        [
            'title' => ['en' => 'Honorary Doctorate, Ahfad University for Women', 'ar' => 'الدكتوراه الفخرية من جامعة الأحفاد للبنات'],
            'description' => [
                'en' => 'On 19 October 2019, at its annual graduation ceremony in Omdurman, Ahfad University for Women conferred on Professor Khidir the degree of Doctor of Science, honoris causa, in recognition of his contribution to higher education and scientific research in Sudan and the Arab world.',
                'ar' => 'في 19 أكتوبر 2019، وخلال حفل التخريج السنوي في أم درمان، منحت جامعة الأحفاد للبنات البروفيسور محمد عثمان خضر درجة الدكتوراه الفخرية في العلوم، تقديراً لإسهامه في مسيرة التعليم العالي والبحث العلمي في السودان والوطن العربي.',
            ],
            'photos' => [
                $ahfadPortrait,
                $photo(
                    'ahfad-2019-conferral.jpg',
                    ['Receiving the honorary doctorate from the President of Ahfad University for Women', 'تسلّم شهادة الدكتوراه الفخرية من رئيس جامعة الأحفاد للبنات'],
                    ['Professor Khidir, in a white gown and leaning on a walking frame, receives an open certificate folder from the university president in a dark gown with silver bars; graduates in white sit behind them.', 'البروفيسور خضر، بردائه الأبيض ومستنداً إلى مشاية، يتسلّم ملف الشهادة من رئيس الجامعة بردائه الداكن ذي الأشرطة الفضية، وخلفهما خريجون بالزي الأبيض.'],
                    $omdurman, 2019, $portrait + $ceremonyCredit + $award,
                ),
                $photo(
                    'ahfad-2019-handshake.jpg',
                    ['A handshake with the university president after the conferral', 'مصافحة رئيس الجامعة عقب منح الدرجة'],
                    ['Professor Khidir and the university president, both in academic gowns, shake hands on the ceremony stage.', 'البروفيسور خضر ورئيس الجامعة بالزي الأكاديمي يتصافحان على منصة الحفل.'],
                    $omdurman, 2019, $portrait + $ceremonyCredit,
                ),
                $photo(
                    'ahfad-2019-scroll.jpg',
                    ['Receiving the degree scroll during the ceremony', 'تسلّم وثيقة الدرجة خلال الحفل'],
                    ['Professor Khidir takes a scroll tied with a gold tassel from a woman in a white gown and veil.', 'البروفيسور خضر يتسلّم وثيقة ملفوفة بشرابة ذهبية من سيدة ترتدي رداءً أبيض وطرحة.'],
                    $omdurman, 2019, $portrait + $ceremonyCredit,
                ),
                $photo(
                    'ahfad-2019-standing.jpg',
                    ['Standing for the conferral among guests and graduates', 'واقفاً لمنح الدرجة بين الضيوف والخريجين'],
                    ['Professor Khidir stands in his white gown, hands on a walking frame, with guests and graduates seated around him.', 'البروفيسور خضر واقفاً بردائه الأبيض ويداه على المشاية، ومن حوله الضيوف والخريجون جلوس.'],
                    $omdurman, 2019, $portrait,
                ),
                $photo(
                    'ahfad-2019-certificate.jpg',
                    ['Certificate of the honorary degree of Doctor of Science, by decision of the Scientific Council of 16 October 2019', 'شهادة الدكتوراه الفخرية في العلوم، بقرار المجلس العلمي للجامعة في جلسته بتاريخ 16 أكتوبر 2019'],
                    ['An Arabic certificate on the letterhead of Ahfad University for Women bearing the name Mohamed Osman Khidir Mahjoub and the degree, signed by the president Gasim Badri and sealed in gold.', 'شهادة باللغة العربية على ترويسة جامعة الأحفاد للبنات تحمل اسم محمد عثمان خضر محجوب والدرجة الممنوحة، بتوقيع رئيس الجامعة قاسم بدري وختم ذهبي.'],
                    $omdurman, 2019,
                ),
                $photo(
                    'ahfad-2019-letter.jpg',
                    ['Letter of thanks from Professor Gasim Badri, President of Ahfad University for Women, 4 November 2019', 'خطاب شكر وتقدير من البروفيسور قاسم بدري، رئيس جامعة الأحفاد للبنات، بتاريخ 4 نوفمبر 2019'],
                    ['A typed Arabic letter on Ahfad University for Women letterhead, dated 4 November 2019, thanking Professor Khidir for honouring the graduation ceremony of 19 October 2019 with his presence.', 'خطاب مطبوع باللغة العربية على ترويسة جامعة الأحفاد للبنات بتاريخ 4 نوفمبر 2019، يشكر البروفيسور خضر على تشريفه حفل التخريج في 19 أكتوبر 2019.'],
                    $omdurman, 2019,
                ),
            ],
        ],
        [
            'title' => ['en' => 'Honours and distinctions', 'ar' => 'الأوسمة والتكريمات'],
            'description' => [
                'en' => 'Medals, shields and certificates of honour presented to Professor Khidir by universities, academies and the State.',
                'ar' => 'ميداليات ودروع وشهادات تكريم قدّمتها للبروفيسور خضر جامعات وأكاديميات والدولة.',
            ],
            'photos' => [
                $photo(
                    'honour-2002-order-of-science.jpg',
                    ['Warrant of the Gold Order of Science, Letters and Arts, from the Presidency of the Republic of the Sudan', 'براءة وسام العلم والآداب والفنون الذهبي من رئاسة جمهورية السودان'],
                    ['An Arabic calligraphic warrant headed Republic of the Sudan, Presidency of the Republic, conferring the Gold Order of Science, Letters and Arts, with a red seal.', 'براءة بالخط العربي تحمل عنوان جمهورية السودان، رئاسة الجمهورية، بمنح وسام العلم والآداب والفنون الذهبي، وعليها ختم أحمر.'],
                    $khartoum, 2002, [P::IS_COVER => true] + $award,
                ),
                $photo(
                    'honour-2002-zubair-prize.jpg',
                    ['Certificate of the Al-Zubair Prize for Innovation and Scientific Excellence, awarded for his research on breeding oil crops', 'براءة جائزة الشهيد الزبير محمد صالح للإبداع والتميز العلمي، تقديراً لأبحاثه في تربية المحاصيل الزيتية'],
                    ['A framed Arabic certificate with blue and gold arabesque corners and a gold seal, headed Al-Zubair Prize for Innovation and Scientific Excellence.', 'براءة عربية مؤطرة بزخارف عربية زرقاء وذهبية في أركانها وختم ذهبي، بعنوان جائزة الزبير للإبداع والتميز العلمي.'],
                    $khartoum, 2002,
                ),
                $photo(
                    'honour-2014-uofk-conference-shield.jpg',
                    ['Shield of honour from the University of Khartoum at the Fifth Annual Conference on Postgraduate Studies and Scientific Research in Agricultural and Veterinary Sciences, 24–27 February 2014', 'درع تكريم من جامعة الخرطوم في المؤتمر السنوي الخامس للدراسات العليا والبحث العلمي في مجال العلوم الزراعية والبيطرية، 24–27 فبراير 2014'],
                    ['A wooden shield in a presentation box lined with yellow satin, its silver plate bearing the University of Khartoum crest and the words "In honour of Professor Mohamed Osman Khidir".', 'درع خشبي في علبة مبطنة بالساتان الأصفر، على لوحته الفضية شعار جامعة الخرطوم وعبارة "تكريم البروفيسور محمد عثمان خضر".'],
                    $khartoum, 2014, $award,
                ),
                $photo(
                    'honour-1999-ibc-scholars.jpg',
                    ['Certificate of inclusion in 2000 Outstanding Scholars of the 20th Century, for his contribution to genetics and plant breeding, May 1999', 'شهادة إدراجه ضمن ألفي عالم متميز في القرن العشرين، تقديراً لإسهامه في الوراثة وتربية النبات، مايو 1999'],
                    ['A framed certificate of the International Biographical Centre, Cambridge, naming Mohamed-Osman Khidir among the 2000 Outstanding Scholars of the 20th Century in the field of genetics and plant breeding.', 'شهادة مؤطرة من المركز الدولي للسير الذاتية في كامبريدج تدرج اسم محمد عثمان خضر ضمن ألفي عالم متميز في القرن العشرين في مجال الوراثة وتربية النبات.'],
                    $cambridge, 1999, $award,
                ),
                $photo(
                    'honour-1986-yarmouk-shield.jpg',
                    ['Shield of Yarmouk University on its tenth anniversary, 1976–1986', 'درع جامعة اليرموك في ذكراها العاشرة، 1976–1986'],
                    ['A white shield on maroon velvet bearing the Yarmouk University emblem: a torch numbered 10 rising from an open book, framed by an olive branch.', 'درع أبيض على قاعدة من المخمل العنابي يحمل شعار جامعة اليرموك: شعلة عليها الرقم 10 تعلو كتاباً مفتوحاً ويحيط بها غصن زيتون.'],
                    ['Irbid, Jordan', 'إربد، الأردن'], 1986, $award,
                ),
                $photo(
                    'honour-1984-whos-who.jpg',
                    ['Certificate of inclusion in the International Who\'s Who of Contemporary Achievement, 12 April 1984', 'شهادة إدراجه في موسوعة من هو من للإنجاز المعاصر الدولية، 12 أبريل 1984'],
                    ['A certificate in a gilt frame with a purple ornamental border, attesting the inclusion of Mohamed Osman Khidir in the International Who\'s Who of Contemporary Achievement for outstanding contributions to society.', 'شهادة في إطار مذهّب بحاشية زخرفية أرجوانية، تثبت إدراج اسم محمد عثمان خضر في موسوعة من هو من للإنجاز المعاصر الدولية لإسهاماته المتميزة في المجتمع.'],
                    ['St Ives, Cambridgeshire, England', 'سانت آيفز، كامبريدجشير، إنجلترا'], 1984, $award,
                ),
                // Undated: the year and place are not shown on these items (see the seeder's report).
                $photo(
                    'honour-aas-fellow.jpg',
                    ['Certificate of Fellowship of the African Academy of Sciences, for outstanding contributions to the advancement of science in Africa', 'شهادة زمالة الأكاديمية الأفريقية للعلوم، تقديراً لإسهاماته المتميزة في تقدّم العلوم في أفريقيا'],
                    ['A framed certificate of the African Academy of Sciences conferring on Mohamed-Osman Khidir the status of Fellow, signed by the President and the Scientific Secretary.', 'شهادة مؤطرة من الأكاديمية الأفريقية للعلوم تمنح محمد عثمان خضر صفة الزميل، بتوقيع الرئيس والأمين العلمي.'],
                    null, null,
                ),
                $photo(
                    'honour-uofk-shield-2.jpg',
                    ['Shield of the University of Khartoum presented in his honour', 'درع جامعة الخرطوم المقدَّم تكريماً له'],
                    ['A silver plaque in a blue velvet case with gilt corners, bearing the words Republic of the Sudan and University of Khartoum 1902 around the enamelled university crest.', 'لوحة فضية في علبة من المخمل الأزرق بأركان مذهّبة، تحمل عبارتي جمهورية السودان وجامعة الخرطوم 1902 حول شعار الجامعة المطلي بالمينا.'],
                    $khartoum, null,
                ),
                $photo(
                    'honour-uofk-shield-1.jpg',
                    ['A second University of Khartoum shield, in an engraved silver arch', 'درع آخر لجامعة الخرطوم داخل قوس فضي محفور'],
                    ['A silver plaque on maroon velvet with an engraved geometric arch around the enamelled University of Khartoum crest and the words Republic of the Sudan.', 'لوحة فضية على مخمل عنابي بقوس هندسي محفور يحيط بشعار جامعة الخرطوم المطلي بالمينا وعبارة جمهورية السودان.'],
                    $khartoum, null,
                ),
                $photo(
                    'honour-squ-medal.jpg',
                    ['Medal of Sultan Qaboos University', 'ميدالية جامعة السلطان قابوس'],
                    ['A shield-shaped silver medal on blue velvet, embossed with the Sultan Qaboos University emblem: an open book, crossed swords and khanjar, an atom and an ear of wheat.', 'ميدالية فضية على شكل درع فوق مخمل أزرق، منقوش عليها شعار جامعة السلطان قابوس: كتاب مفتوح وسيفان متقاطعان وخنجر وذرة وسنبلة قمح.'],
                    ['Muscat, Oman', 'مسقط، سلطنة عُمان'], null,
                ),
                $photo(
                    'honour-squ-case.jpg',
                    ['The presentation case of the Sultan Qaboos University medal', 'علبة ميدالية جامعة السلطان قابوس'],
                    ['A blue leather case stamped in silver with the name Sultan Qaboos University in Arabic and English.', 'علبة جلدية زرقاء مطبوع عليها بالفضي اسم جامعة السلطان قابوس بالعربية والإنجليزية.'],
                    ['Muscat, Oman', 'مسقط، سلطنة عُمان'], null,
                ),
                $photo(
                    'honour-senas-medal-front.jpg',
                    ['Medal of the Sudanese Encyclopedia of Agricultural Sciences, inscribed with his name', 'ميدالية الموسوعة السودانية للعلوم الزراعية منقوش عليها اسمه'],
                    ['A round medal on a ribbon in the colours of the Sudanese flag, its face inscribed "Professor Mohamed Osman Khidir" above a red and green leaf emblem.', 'ميدالية مستديرة بشريط بألوان العلم السوداني، على وجهها عبارة "بروفيسور/ محمد عثمان خضر" فوق شعار ورقتين باللونين الأحمر والأخضر.'],
                    $khartoum, null,
                ),
                $photo(
                    'honour-senas-medal-reverse.jpg',
                    ['Reverse of the medal, with the SENAS emblem', 'ظهر الميدالية وعليه شعار الموسوعة (SENAS)'],
                    ['The silver reverse of the medal, inscribed Sudanese Encyclopedia of Agricultural Science in Arabic and English around the SENAS logo.', 'الوجه الفضي الخلفي للميدالية وعليه عبارة الموسوعة السودانية للعلوم الزراعية بالعربية والإنجليزية حول شعار SENAS.'],
                    $khartoum, null,
                ),
            ],
        ],
        [
            'title' => ['en' => 'Degrees and certificates', 'ar' => 'الشهادات والدرجات العلمية'],
            'description' => [
                'en' => 'From his elementary certificate in 1950 to his doctorate in genetics from the University of California, Davis, in 1966: the certificates that trace his education and scientific career.',
                'ar' => 'من شهادة الدراسة الابتدائية عام 1950 إلى الدكتوراه في الوراثة من جامعة كاليفورنيا في ديفيس عام 1966: شهادات ترسم مسيرته التعليمية والعلمية.',
            ],
            'photos' => [
                $photo(
                    'cert-1966-phd-davis.jpg',
                    ['Doctor of Philosophy in Genetics, University of California, conferred at Davis on 26 January 1966', 'دكتوراه الفلسفة في الوراثة من جامعة كاليفورنيا، مُنحت في ديفيس بتاريخ 26 يناير 1966'],
                    ['A framed diploma of the Regents of the University of California conferring on Mohamed Osman Khidir the degree of Doctor of Philosophy for original research in genetics, with a gold seal.', 'شهادة مؤطرة من مجلس أوصياء جامعة كاليفورنيا تمنح محمد عثمان خضر درجة دكتوراه الفلسفة لبحثه الأصيل في الوراثة، وعليها ختم ذهبي.'],
                    $davis, 1966, [P::IS_COVER => true],
                ),
                $photo(
                    'cert-1963-sigma-xi.jpg',
                    ['Election as an associate member of the Society of the Sigma Xi, California at Davis Chapter, 9 May 1963', 'انتخابه عضواً مشاركاً في جمعية سيغما كاي العلمية، فرع ديفيس بكاليفورنيا، 9 مايو 1963'],
                    ['A framed certificate in Gothic lettering of the Society of the Sigma Xi, devoted to the promotion of research in science, with a gold seal and blue ribbon.', 'شهادة مؤطرة بالخط القوطي من جمعية سيغما كاي المعنية بتعزيز البحث العلمي، وعليها ختم ذهبي وشريط أزرق.'],
                    $davis, 1963,
                ),
                $photo(
                    'cert-1962-magric-khartoum.jpg',
                    ['Master of Agriculture, University of Khartoum, 13 August 1962', 'ماجستير الزراعة من جامعة الخرطوم، 13 أغسطس 1962'],
                    ['A framed bilingual diploma of the University of Khartoum, English on the left and Arabic on the right, awarding the degree of Master of Agriculture to Mohammed Osman Khider.', 'شهادة مؤطرة ثنائية اللغة من جامعة الخرطوم، بالإنجليزية يساراً والعربية يميناً، تمنح محمد عثمان خضر درجة ماجستير الزراعة.'],
                    $khartoum, 1962,
                ),
                $photo(
                    'cert-1959-bsc-cairo.jpg',
                    ['Bachelor of Agricultural Science, Cairo University, with the grade Very Good and second-class honours, 1959', 'بكالوريوس العلوم الزراعية من جامعة القاهرة بتقدير جيد جداً مع مرتبة الشرف الثانية، 1959'],
                    ['A framed handwritten Arabic certificate of Cairo University, dated 1959, recording the award of the Bachelor of Agricultural Science.', 'شهادة عربية مكتوبة بخط اليد في إطار مذهّب من جامعة القاهرة، مؤرخة عام 1959، تثبت منحه بكالوريوس العلوم الزراعية.'],
                    $cairo, 1959,
                ),
                $photo(
                    'cert-secondary.jpg',
                    ['General Secondary Certificate, science section, from the Egyptian Ministry of Education; he passed in June 1955, ranked 776th of 10,445 successful candidates', 'شهادة الدراسة الثانوية (القسم الخاص) شعبة العلوم من وزارة التربية والتعليم المصرية؛ نجح فيها في يونيو 1955 وكان ترتيبه 776 من بين 10,445 ناجحاً'],
                    ['A worn secondary-school certificate with a blue-green floral border and the Egyptian emblem, written in Arabic.', 'شهادة ثانوية قديمة بحاشية نباتية زرقاء مخضرة وشعار مصر، مكتوبة بالعربية.'],
                    $cairo, 1955,
                ),
                $photo(
                    'cert-1950-elementary.jpg',
                    ['Elementary Studies Certificate, issued by the Egyptian Ministry of Education for its schools in the Sudan; he passed in May 1950, ranked 47th of 470 successful candidates', 'شهادة الدراسة الابتدائية من وزارة المعارف العمومية المصرية (منطقة السودان)؛ نجح فيها في مايو 1950 وكان ترتيبه 47 من بين 470 ناجحاً'],
                    ['A worn certificate of the Kingdom of Egypt, Ministry of Public Education, Sudan district, with an ornate black border and an embossed seal.', 'شهادة قديمة من المملكة المصرية، وزارة المعارف العمومية، منطقة السودان، بحاشية سوداء مزخرفة وختم بارز.'],
                    ['Sudan', 'السودان'], 1950,
                ),
                $photo(
                    'cert-1972-fao-iaea-study-tour.jpg',
                    ['Certificate of the FAO/IAEA study tour on the use of isotopes and radiation in genetics and plant breeding, USSR, 4 July to 4 August 1972', 'شهادة الجولة الدراسية لمنظمة الأغذية والزراعة والوكالة الدولية للطاقة الذرية حول استخدام النظائر والإشعاع في الوراثة وتربية النبات، الاتحاد السوفيتي، 4 يوليو إلى 4 أغسطس 1972'],
                    ['A framed certificate bearing the FAO and IAEA emblems, attesting that Mohamed-Osman Khidir took part in the study tour in the USSR.', 'شهادة مؤطرة تحمل شعاري منظمة الأغذية والزراعة والوكالة الدولية للطاقة الذرية، تثبت مشاركة محمد عثمان خضر في الجولة الدراسية في الاتحاد السوفيتي.'],
                    ['USSR', 'الاتحاد السوفيتي'], 1972,
                ),
                $photo(
                    'cert-2016-food-security-conference.jpg',
                    ['Certificate as session chairperson at the 2nd International Conference on Agriculture, Food Security and Biotechnology, 17–18 October 2016', 'شهادة رئاسة جلسة في المؤتمر الدولي الثاني للزراعة والأمن الغذائي والتقانة الحيوية، 17–18 أكتوبر 2016'],
                    ['A certificate of participation bearing the logos of ISESCO, COMSATS, IRCC, the University of Khartoum and the Sudanese Ministry of Higher Education, with an official stamp.', 'شهادة مشاركة تحمل شعارات الإيسيسكو وكومساتس ومركز البحوث والاستشارات الصناعية وجامعة الخرطوم ووزارة التعليم العالي، وعليها ختم رسمي.'],
                    $khartoum, 2016,
                ),
            ],
        ],
        [
            'title' => ['en' => 'Meetings and conferences', 'ar' => 'اجتماعات ومؤتمرات'],
            'description' => [
                'en' => 'Professor Khidir among delegates and colleagues at regional and international gatherings on science, technology and research.',
                'ar' => 'البروفيسور خضر بين الوفود والزملاء في ملتقيات إقليمية ودولية حول العلم والتقانة والبحث العلمي.',
            ],
            'photos' => [
                $photo(
                    'vienna-1979-un-conference.jpg',
                    ['At the United Nations Conference on Science and Technology for Development', 'في مؤتمر الأمم المتحدة للعلم والتكنولوجيا من أجل التنمية'],
                    ['Delegates wearing interpretation headphones sit at a long conference table in a crowded hall; the gilt frame is labelled by hand "U.N. Conf. on Sci. & Tech., Vienna, 1979".', 'مندوبون يضعون سماعات الترجمة يجلسون إلى طاولة مؤتمر طويلة في قاعة مكتظة؛ وعلى الإطار المذهّب تعليق بخط اليد: مؤتمر الأمم المتحدة للعلم والتكنولوجيا، فيينا 1979.'],
                    ['Vienna, Austria', 'فيينا، النمسا'], 1979, [P::IS_COVER => true],
                ),
                $photo(
                    'doha-arab-research-councils.jpg',
                    ['Meeting of the Federation of Arab Scientific Research Councils', 'اجتماع اتحاد مجالس البحث العلمي العربية'],
                    ['A formal group photograph of about forty delegates, in suits and Gulf dress, standing on the steps of a hall, with a handwritten Arabic caption naming the meeting in Doha, Qatar.', 'صورة جماعية رسمية لنحو أربعين مندوباً ببدلات وأزياء خليجية على درج إحدى القاعات، وتحتها تعليق عربي بخط اليد يذكر الاجتماع في الدوحة بقطر.'],
                    ['Doha, Qatar', 'الدوحة، قطر'], null,
                ),
                $photo(
                    'khartoum-october-reception.jpg',
                    ['With Lieutenant Colonel Mohamed Nabil Fouad, the Egyptian military attaché in Khartoum, and his wife, at the celebration of the 6 October anniversary', 'مع المقدم أ.ح محمد نبيل فؤاد، الملحق العسكري المصري بالخرطوم، وحرمه، في الاحتفال بذكرى السادس من أكتوبر'],
                    ['A framed black-and-white photograph of a reception: an officer in a peaked cap and a woman greet a smiling woman in a Sudanese toub and a man in a suit; a handwritten Arabic caption runs below.', 'صورة بالأبيض والأسود في إطار مذهّب لحفل استقبال: ضابط بقبعة عسكرية وسيدة يصافحان سيدة مبتسمة بالثوب السوداني ورجلاً ببدلة، وتحتها تعليق عربي بخط اليد.'],
                    $khartoum, null,
                ),
            ],
        ],
    ],

    // Awards: the roll of honour, oldest first, undated last.
    'awards' => [
        [
            'name' => ['en' => 'International Who\'s Who of Contemporary Achievement', 'ar' => 'موسوعة من هو من للإنجاز المعاصر الدولية'],
            'organisation' => ['en' => 'Biographical Publications, Cambridgeshire, England', 'ar' => 'منشورات السير الذاتية، كامبريدجشير، إنجلترا'],
            'year' => 1984,
            'description' => ['en' => 'Included for outstanding contributions to society.', 'ar' => 'أُدرج اسمه تقديراً لإسهاماته المتميزة في خدمة المجتمع.'],
        ],
        [
            'name' => ['en' => 'Tenth Anniversary Shield', 'ar' => 'درع الذكرى العاشرة'],
            'organisation' => ['en' => 'Yarmouk University, Jordan', 'ar' => 'جامعة اليرموك، الأردن'],
            'year' => 1986,
        ],
        [
            'name' => ['en' => '2000 Outstanding Scholars of the 20th Century', 'ar' => 'ألفا عالم متميز في القرن العشرين'],
            'organisation' => ['en' => 'International Biographical Centre, Cambridge', 'ar' => 'المركز الدولي للسير الذاتية، كامبريدج'],
            'year' => 1999,
            'description' => ['en' => 'In honour of an outstanding contribution to genetics and plant breeding.', 'ar' => 'تكريماً لإسهامه المتميز في مجال الوراثة وتربية النبات.'],
        ],
        [
            'name' => ['en' => 'Al-Zubair Prize for Innovation and Scientific Excellence', 'ar' => 'جائزة الشهيد الزبير محمد صالح للإبداع والتميز العلمي'],
            'year' => 2002,
            'description' => ['en' => 'Awarded in the field of agriculture, at the level of established scientists, for his research on breeding oil crops.', 'ar' => 'مُنحت في مجال الزراعة على مستوى العلماء، تقديراً لأبحاثه في تربية المحاصيل الزيتية.'],
        ],
        [
            'name' => ['en' => 'Gold Order of Science, Letters and Arts', 'ar' => 'وسام العلم والآداب والفنون الذهبي'],
            'organisation' => ['en' => 'Presidency of the Republic of the Sudan', 'ar' => 'رئاسة الجمهورية، جمهورية السودان'],
            'year' => 2002,
            'description' => ['en' => 'Conferred in appreciation of his distinguished service to the nation in education, science, letters and the arts.', 'ar' => 'مُنح تقديراً لما قدّمه من أعمال جليلة للوطن في ميادين التربية والتعليم والعلوم والآداب والفنون.'],
        ],
        [
            'name' => ['en' => 'Honour of the Fifth Annual Conference on Postgraduate Studies and Scientific Research', 'ar' => 'تكريم المؤتمر السنوي الخامس للدراسات العليا والبحث العلمي'],
            'organisation' => ['en' => 'University of Khartoum', 'ar' => 'جامعة الخرطوم'],
            'year' => 2014,
            'description' => ['en' => 'Conference on agricultural and veterinary sciences, Khartoum, 24–27 February 2014.', 'ar' => 'في مجال العلوم الزراعية والبيطرية، الخرطوم، 24–27 فبراير 2014.'],
        ],
        [
            'name' => ['en' => 'Honorary Doctor of Science', 'ar' => 'الدكتوراه الفخرية في العلوم'],
            'organisation' => ['en' => 'Ahfad University for Women', 'ar' => 'جامعة الأحفاد للبنات'],
            'year' => 2019,
            'description' => ['en' => 'In recognition of his contribution to higher education and scientific research in Sudan and the Arab world; conferred at the university\'s graduation ceremony in Omdurman on 19 October 2019.', 'ar' => 'تقديراً لجهوده في تنمية مسيرة التعليم العالي والبحث العلمي في السودان والوطن العربي؛ مُنحت في حفل تخريج الجامعة بأم درمان في 19 أكتوبر 2019.'],
        ],
        [
            'name' => ['en' => 'Fellow of the African Academy of Sciences', 'ar' => 'زميل الأكاديمية الأفريقية للعلوم'],
            'organisation' => ['en' => 'African Academy of Sciences', 'ar' => 'الأكاديمية الأفريقية للعلوم'],
            'year' => null,
            'description' => ['en' => 'For outstanding contributions to the advancement of science in Africa.', 'ar' => 'تقديراً لإسهاماته المتميزة في تقدّم العلوم في أفريقيا.'],
        ],
        [
            'name' => ['en' => 'Shield of Honour', 'ar' => 'درع التكريم'],
            'organisation' => ['en' => 'University of Khartoum', 'ar' => 'جامعة الخرطوم'],
            'year' => null,
        ],
        [
            'name' => ['en' => 'University Medal', 'ar' => 'ميدالية الجامعة'],
            'organisation' => ['en' => 'Sultan Qaboos University, Oman', 'ar' => 'جامعة السلطان قابوس، سلطنة عُمان'],
            'year' => null,
        ],
        [
            'name' => ['en' => 'Medal of the Sudanese Encyclopedia of Agricultural Sciences', 'ar' => 'ميدالية الموسوعة السودانية للعلوم الزراعية'],
            'organisation' => ['en' => 'Sudanese Encyclopedia of Agricultural Sciences (SENAS)', 'ar' => 'الموسوعة السودانية للعلوم الزراعية (SENAS)'],
            'year' => null,
        ],
    ],

    // CV and Achievements: only what the certificates establish.
    'cv' => [
        [CvSection::Education, ['en' => 'Elementary Studies Certificate', 'ar' => 'شهادة الدراسة الابتدائية'], ['en' => 'Egyptian Ministry of Education, Sudan district', 'ar' => 'وزارة المعارف العمومية المصرية، منطقة السودان'], ['en' => 'Ranked 47th of 470 successful candidates, May 1950.', 'ar' => 'كان ترتيبه 47 من بين 470 ناجحاً، مايو 1950.'], null, 1950],
        [CvSection::Education, ['en' => 'General Secondary Certificate, science section', 'ar' => 'شهادة الدراسة الثانوية (القسم الخاص)، شعبة العلوم'], ['en' => 'Egyptian Ministry of Education, Cairo', 'ar' => 'وزارة التربية والتعليم المصرية، القاهرة'], ['en' => 'Ranked 776th of 10,445 successful candidates, June 1955.', 'ar' => 'كان ترتيبه 776 من بين 10,445 ناجحاً، يونيو 1955.'], null, 1955],
        [CvSection::Education, ['en' => 'B.Sc. in Agricultural Sciences', 'ar' => 'بكالوريوس العلوم الزراعية'], ['en' => 'Cairo University', 'ar' => 'جامعة القاهرة'], ['en' => 'Very Good, with second-class honours.', 'ar' => 'بتقدير جيد جداً مع مرتبة الشرف الثانية.'], null, 1959],
        [CvSection::Education, ['en' => 'Master of Agriculture', 'ar' => 'ماجستير الزراعة'], ['en' => 'University of Khartoum', 'ar' => 'جامعة الخرطوم'], ['en' => 'Conferred on 13 August 1962.', 'ar' => 'مُنحت في 13 أغسطس 1962.'], null, 1962],
        [CvSection::Education, ['en' => 'Ph.D. in Genetics', 'ar' => 'دكتوراه الفلسفة في الوراثة'], ['en' => 'University of California, Davis', 'ar' => 'جامعة كاليفورنيا، ديفيس'], ['en' => 'Conferred on 26 January 1966 for original research in genetics.', 'ar' => 'مُنحت في 26 يناير 1966 لبحثه الأصيل في علم الوراثة.'], null, 1966],
        [CvSection::Education, ['en' => 'Doctor of Science, honoris causa', 'ar' => 'الدكتوراه الفخرية في العلوم'], ['en' => 'Ahfad University for Women', 'ar' => 'جامعة الأحفاد للبنات'], null, null, 2019],
        [CvSection::Appointment, ['en' => 'Professor Emeritus, Department of Agronomy', 'ar' => 'بروفيسور فخري، قسم المحاصيل الحقلية'], ['en' => 'Faculty of Agriculture, University of Khartoum, Shambat', 'ar' => 'كلية الزراعة، جامعة الخرطوم، شمبات'], null, null, null],
        [CvSection::Achievement, ['en' => 'Associate member, The Society of the Sigma Xi', 'ar' => 'عضو مشارك في جمعية سيغما كاي العلمية'], ['en' => 'California at Davis Chapter', 'ar' => 'فرع ديفيس بكاليفورنيا'], ['en' => 'Elected on 9 May 1963.', 'ar' => 'انتُخب في 9 مايو 1963.'], 1963, 1963],
        [CvSection::Achievement, ['en' => 'Study Tour on the Use of Isotopes and Radiation in Genetics and Plant Breeding', 'ar' => 'الجولة الدراسية حول استخدام النظائر والإشعاع في الوراثة وتربية النبات'], ['en' => 'FAO and the International Atomic Energy Agency, USSR', 'ar' => 'منظمة الأغذية والزراعة والوكالة الدولية للطاقة الذرية، الاتحاد السوفيتي'], ['en' => 'Participant, 4 July to 4 August 1972.', 'ar' => 'مشارك، من 4 يوليو إلى 4 أغسطس 1972.'], 1972, 1972],
        [CvSection::Achievement, ['en' => 'United Nations Conference on Science and Technology for Development', 'ar' => 'مؤتمر الأمم المتحدة للعلم والتكنولوجيا من أجل التنمية'], ['en' => 'United Nations, Vienna', 'ar' => 'الأمم المتحدة، فيينا'], ['en' => 'Participant.', 'ar' => 'مشارك.'], 1979, 1979],
        [CvSection::Achievement, ['en' => '2nd International Conference on Agriculture, Food Security and Biotechnology', 'ar' => 'المؤتمر الدولي الثاني للزراعة والأمن الغذائي والتقانة الحيوية'], ['en' => 'ISESCO, COMSATS, IRCC, University of Khartoum and the Ministry of Higher Education and Scientific Research, Khartoum', 'ar' => 'الإيسيسكو وكومساتس ومركز البحوث والاستشارات الصناعية وجامعة الخرطوم ووزارة التعليم العالي والبحث العلمي، الخرطوم'], ['en' => 'Session chairperson, 17–18 October 2016.', 'ar' => 'رئيس جلسة، 17–18 أكتوبر 2016.'], 2016, 2016],
        [CvSection::Project, ['en' => 'Training course on research methodology and scientific writing in English', 'ar' => 'دورة تدريبية في منهجية البحث والكتابة العلمية باللغة الإنجليزية'], ['en' => 'Faculty of Agriculture, University of Khartoum, with the Ministry of Higher Education and Scientific Research', 'ar' => 'كلية الزراعة، جامعة الخرطوم، بالتعاون مع وزارة التعليم العالي والبحث العلمي'], ['en' => 'September 2016.', 'ar' => 'سبتمبر 2016.'], 2016, 2016],
    ],

    // Home: lines from his own lectures and papers, between the chapters of his life. Each is quoted
    // verbatim in the language it was written in; the other language is a translation.
    'quotes' => [
        [
            'quote' => ['en' => 'Research is a scientific inquiry to discover or verify facts and test hypotheses.', 'ar' => 'البحث تقصٍّ علمي لاكتشاف الحقائق أو التحقق منها واختبار الفرضيات.'],
            'attribution' => ['en' => 'Training course on research methodology and scientific writing', 'ar' => 'الدورة التدريبية في منهجية البحث والكتابة العلمية'],
        ],
        [
            'quote' => ['en' => 'Plant and animal genetic resources are the biological foundation of food security.', 'ar' => 'الموارد الوراثية النباتية والحيوانية تمثل الأساس البيولوجي للأمن الغذائي.'],
            'attribution' => ['en' => 'The importance of biodiversity and the threats it faces', 'ar' => 'أهمية ومهددات التنوع الحيوي'],
        ],
        [
            'quote' => ['en' => 'It is necessary to measure the response of varieties to drought stress by direct field testing for yield.', 'ar' => 'من الضروري قياس استجابة الأصناف للإجهاد المائي بالاختبار للإنتاجية تحت ظروف الحقل.'],
            'attribution' => ['en' => 'Breeding field crops for abiotic stresses in the dry lands', 'ar' => 'تربية المحاصيل الحقلية لتحمّل الإجهاد اللاأحيائي في الأراضي الجافة'],
        ],
        [
            'quote' => ['en' => 'Doctoral theses were distinguished by their rigour, in content and in language, and most were published in international, regional and local journals.', 'ar' => 'تميزت رسائل الدكتوراه بالرصانة، محتوىً ولغةً، مما أدى إلى نشر معظمها في دوريات عالمية وإقليمية ومحلية.'],
            'attribution' => ['en' => 'Workshop on the quality of doctoral theses, University of Khartoum', 'ar' => 'ورشة عمل ضمان جودة رسائل الدكتوراه، جامعة الخرطوم'],
        ],
    ],

    // Publications: the papers supplied as text. Year and venue are not stated in the files.
    'publications' => [
        [
            'type' => PublicationType::Other,
            'title' => ['en' => 'Breeding Field Crops for Abiotic Stresses in the Dry Lands', 'ar' => 'تربية المحاصيل الحقلية لتحمّل الإجهاد اللاأحيائي في الأراضي الجافة'],
            'abstract' => [
                'en' => 'Most of the main crops of the Sudan (sorghum, pearl millet, groundnut and sesame) are produced predominantly under rainfed conditions in arid and semi-arid areas, characterised by erratic and unpredictable rainfall. Their yield is greatly reduced by water-deficit stress, sometimes accompanied by other abiotic stresses such as salinity and heat. This paper describes the classical breeding approaches for developing drought-tolerant varieties, notes that effective selection for heat tolerance has lagged behind that used for other quantitative characters, and briefly discusses heterosis, mutagenesis and genetic engineering. It concludes that the response of varieties to drought must be measured by direct field testing for yield, and that breeding field crops for drought tolerance in the Sudan is very limited, and practically absent for salinity and heat tolerance.',
                'ar' => 'تُنتج معظم المحاصيل الحقلية في السودان (الذرة الرفيعة والدخن والفول السوداني والسمسم) مطرياً في المناطق الجافة وشبه الجافة، حيث تعاني من الإجهاد المائي الذي كثيراً ما تصاحبه ملوحة التربة وارتفاع درجة الحرارة. تصف الورقة طرق التربية التقليدية لإنتاج أصناف تتحمل الجفاف، وتشير إلى أن الانتخاب الفعّال لتحمل الحرارة متخلف عن ذلك المستخدم لتحسين الصفات الكمية الأخرى، وتتطرق إلى الاستخدام المحتمل لقوة الهجين والتربية الطفورية والهندسة الوراثية. وتخلص إلى ضرورة قياس استجابة الأصناف للإجهاد المائي بالاختبار الحقلي للإنتاجية، وإلى أن تربية المحاصيل الحقلية في السودان لتحمل الجفاف محدودة، ومعدومة عملياً فيما يتعلق بتحمل الملوحة والحرارة.',
            ],
            'file' => 'documents/paper-breeding-field-crops-abiotic-stress.pdf',
        ],
        [
            'type' => PublicationType::ConferencePaper,
            'title' => ['en' => 'The Importance of Biodiversity and the Threats It Faces', 'ar' => 'أهمية ومهددات التنوع الحيوي'],
            'abstract' => [
                'en' => 'Biodiversity is the variation among ecosystems and living organisms, within and between species, arising from genetic and environmental variation and their interaction. It allows species to survive, and plant and animal genetic resources are the biological basis of food security, which led to the Convention on Biological Diversity and the founding of IPGRI. The paper reviews the threats to biodiversity, among them drought and desertification, horizontal expansion of agriculture, fires, tree felling, hunting, overgrazing, wars and weak legislation, and recommends regular surveys of genetic erosion, gene banks, protected areas, captive breeding of endangered animals, alternative energy sources, protective legislation, university teaching, public awareness and cooperation among Arab countries and with international organisations.',
                'ar' => 'التنوع الحيوي هو التباين بين النظم البيئية وبين الكائنات الحية داخل النوع الواحد أو بين الأنواع، ويعود إلى التباين الوراثي والبيئي والتفاعل بينهما. وتكمن أهميته في أنه يمكّن الأنواع من الاستمرار، وتمثّل الموارد الوراثية النباتية والحيوانية الأساس البيولوجي للأمن الغذائي، مما أدى إلى عقد اتفاقية التنوع الحيوي وإنشاء المعهد الدولي للموارد الوراثية. تستعرض الورقة مهددات التنوع الحيوي، ومنها الجفاف والتصحر والتوسع الأفقي في الزراعة والحرائق وقطع الأشجار والصيد والرعي الجائر والحروب وضعف التشريعات، وتوصي بإجراء مسوحات دورية للتآكل الوراثي، وحفظ البذور في بنوك الجينات، وإنشاء المحميات، وتوفير مصادر بديلة للطاقة، وسنّ التشريعات، وإدخال التنوع الحيوي في المقررات الجامعية، وتوعية الجمهور، والتعاون بين الدول العربية ومع المنظمات الدولية.',
            ],
            'file' => 'documents/paper-biodiversity-importance-and-threats.pdf',
        ],
        [
            'type' => PublicationType::Other,
            'title' => ['en' => 'Genetically Modified Crops: Cotton as an Example', 'ar' => 'المحاصيل المحوّرة وراثياً: القطن مثالاً'],
            'abstract' => [
                'en' => 'A genetically modified crop is one into which DNA has been transferred from another organism across species boundaries, using recombinant DNA technology. Taking cotton as the example, the paper traces the historical background of crossing between species, explains how genetically modified crops are produced, and discusses their benefits, risks and regulation.',
                'ar' => 'المحصول المحوّر وراثياً هو الذي نُقل إليه الدنا (DNA) من كائن آخر عبر الحدود بين الأنواع، أي عبر العزل التكاثري، باستخدام تقانة الدنا المطعّم، أو ما يُعرف مجازاً بالهندسة الوراثية. تتخذ الورقة من القطن مثالاً، فتعرض الخلفية التاريخية للتهجين بين الأنواع، وتشرح كيفية إنتاج المحاصيل المحوّرة وراثياً، وتناقش فوائدها ومخاطرها وتنظيمها.',
            ],
            'file' => 'documents/paper-gm-crops-cotton.pdf',
        ],
        [
            'type' => PublicationType::Other,
            'title' => ['en' => 'The Current Status of Oilseed Crop Production in the Sudan and Its Potential to Close the Gap in the Arab Countries', 'ar' => 'الوضع الراهن لإنتاج المحاصيل الزيتية في السودان وإمكانات مساهمتها في سد الفجوة في الدول العربية'],
            'abstract' => [
                'en' => 'Rapid rises in food prices and the growing use of crops for biofuel have deepened the import burden of the Arab countries, whose deficit in vegetable oils is about 60 per cent. As the leading Arab producer of annual oil crops, and the only Arab country with room for both horizontal and vertical expansion, the Sudan is looked to to close this gap. The paper reviews the production of sesame, groundnut, sunflower and cottonseed in the Sudan, the state of its oil-milling industry, which works at about a quarter of its capacity, and the prospects for increasing production.',
                'ar' => 'أدّت الزيادات المتسارعة في أسعار المواد الغذائية والتوجه إلى استخدام المحاصيل في إنتاج الوقود الحيوي إلى تفاقم أعباء الاستيراد على الدول العربية، التي تبلغ فجوتها في الزيوت النباتية نحو 60%. ولما كان السودان أهم الدول العربية المنتجة للمحاصيل الزيتية الحولية، والدولة العربية الوحيدة التي تزخر بإمكانات التوسع الأفقي والرأسي، فإن الأنظار تتجه إليه لسد هذه الفجوة. تستعرض الورقة إنتاج السمسم والفول السوداني وزهرة الشمس وبذرة القطن في السودان، وحال صناعة استخلاص الزيوت التي تعمل بنحو ربع طاقتها التصميمية، وآفاق زيادة الإنتاج.',
            ],
            'file' => 'documents/paper-oilseeds-sudan-current-status.pdf',
        ],
    ],

    // Teaching Materials: grouped by course. Public downloads.
    'teaching' => [
        'courses' => [
            'Evolution of Crop Plants and Genetic Resources' => ['en' => 'A course (2+1 credit hours) on the origin and evolution of crop plants under domestication, and on the collection, conservation and use of plant genetic resources.', 'ar' => 'مقرر (2+1 ساعة معتمدة) في نشأة النباتات المحصولية وتطورها تحت الاستئناس، وفي جمع الموارد الوراثية النباتية وصونها واستخدامها.'],
            'Oil Crops' => ['en' => 'The oil crops of the Sudan and the world, their importance, production and breeding.', 'ar' => 'المحاصيل الزيتية في السودان والعالم: أهميتها وإنتاجها وتربيتها.'],
            'Research Methodology and Scientific Writing' => ['en' => 'A training course for researchers and postgraduate students on research methods and on writing scientific papers, reviews and conference papers in English.', 'ar' => 'دورة تدريبية للباحثين وطلاب الدراسات العليا في مناهج البحث وكتابة الأوراق العلمية والمراجعات وأوراق المؤتمرات باللغة الإنجليزية.'],
            'Plant Breeding and Genetic Resources: Lectures' => ['en' => 'Public lectures and seminar presentations on crop breeding, biodiversity and genetically modified crops.', 'ar' => 'محاضرات عامة وعروض في تربية المحاصيل والتنوع الحيوي والمحاصيل المحوّرة وراثياً.'],
            'Postgraduate Studies' => ['en' => 'Workshops for supervisors and examiners of postgraduate research at the University of Khartoum.', 'ar' => 'ورش عمل لمشرفي ومُمتحني بحوث الدراسات العليا في جامعة الخرطوم.'],
        ],
        'materials' => [
            ['Evolution of Crop Plants and Genetic Resources', 'Lectures', TeachingMaterialType::Slides, ['en' => 'Evolution of crop plants and genetic resources: lecture slides', 'ar' => 'تطور المحاصيل والموارد الوراثية: شرائح المحاضرات'], ['en' => 'The full set of lecture slides for the course, in Arabic (128 pages).', 'ar' => 'الشرائح الكاملة لمحاضرات المقرر باللغة العربية (128 صفحة).'], 'evolution-crop-plants-slides.pdf', null],
            ['Evolution of Crop Plants and Genetic Resources', 'Course text', TeachingMaterialType::Notes, ['en' => 'Evolution of crop plants and genetic resources: course text', 'ar' => 'تطور المحاصيل والموارد الوراثية: نص المقرر'], ['en' => 'The written course text, in Arabic (29 pages).', 'ar' => 'النص المكتوب للمقرر باللغة العربية (29 صفحة).'], 'evolution-crop-plants-notes.pdf', null],
            ['Oil Crops', 'Lectures', TeachingMaterialType::Slides, ['en' => 'Oil crops: lecture slides', 'ar' => 'المحاصيل الزيتية: شرائح المحاضرات'], ['en' => 'What an oil crop is, why oil crops matter, and the main oil crops grown in the Sudan, in Arabic (106 pages).', 'ar' => 'تعريف المحصول الزيتي وأهمية المحاصيل الزيتية وأهمها في السودان، باللغة العربية (106 صفحات).'], 'oil-crops-slides.pdf', null],
            ['Oil Crops', 'Plant breeding', TeachingMaterialType::Notes, ['en' => 'Breeding oil crops: course text', 'ar' => 'تربية النبات (المحاصيل الزيتية): نص المقرر'], ['en' => 'The aims and methods of plant breeding applied to oil crops, in Arabic (26 pages).', 'ar' => 'أهداف تربية النبات وطرقها مطبّقةً على المحاصيل الزيتية، باللغة العربية (26 صفحة).'], 'oil-crops-breeding-notes.pdf', null],
            ['Research Methodology and Scientific Writing', 'Course outline', TeachingMaterialType::Syllabus, ['en' => 'Training programme in scientific writing in English: outline', 'ar' => 'البرنامج التدريبي في الكتابة العلمية باللغة الإنجليزية: التصور'], ['en' => 'The proposal and outline of the training programme, prepared with the Ministry of Higher Education and Scientific Research, in Arabic.', 'ar' => 'تصور البرنامج التدريبي ومحاوره، بالتعاون مع وزارة التعليم العالي والبحث العلمي، باللغة العربية.'], 'scientific-writing-course-outline-2016.pdf', '2016-09-01'],
            ['Research Methodology and Scientific Writing', 'Research methodology', TeachingMaterialType::Slides, ['en' => 'Research methodology and scientific writing: slides', 'ar' => 'منهجية البحث والكتابة العلمية: الشرائح'], ['en' => 'Types of research, the scientific method, experimental design and the structure of a research paper (74 pages).', 'ar' => 'أنواع البحث والمنهج العلمي وتصميم التجارب وبنية الورقة العلمية (74 صفحة).'], 'research-methodology-scientific-writing-slides.pdf', null],
            ['Research Methodology and Scientific Writing', 'Scientific writing', TeachingMaterialType::Slides, ['en' => 'Training course on scientific writing: slides', 'ar' => 'الدورة التدريبية في الكتابة العلمية: الشرائح'], ['en' => 'The final course slides on writing each part of a scientific paper (80 pages).', 'ar' => 'الشرائح النهائية للدورة في كتابة أجزاء الورقة العلمية (80 صفحة).'], 'scientific-writing-course-slides.pdf', null],
            ['Research Methodology and Scientific Writing', 'Scientific writing', TeachingMaterialType::Notes, ['en' => 'Scientific writing tips', 'ar' => 'إرشادات في الكتابة العلمية'], ['en' => 'Notes on writing review papers and conference papers, and on scientific style and conventions (3 pages).', 'ar' => 'ملاحظات في كتابة أوراق المراجعة وأوراق المؤتمرات، وفي الأسلوب والأعراف العلمية (3 صفحات).'], 'scientific-writing-tips.pdf', null],
            ['Postgraduate Studies', 'Quality of doctoral theses', TeachingMaterialType::Slides, ['en' => 'Workshop on the quality of doctoral theses: research proposal, supervision and examination', 'ar' => 'ورشة عمل ضمان جودة رسائل الدكتوراه: مشروع البحث والإشراف والتقويم'], ['en' => 'Presented at the Graduate College, University of Khartoum, in Arabic (26 pages).', 'ar' => 'قُدّمت في كلية الدراسات العليا بجامعة الخرطوم، باللغة العربية (26 صفحة).'], 'phd-thesis-quality-workshop-slides.pdf', null],
            ['Plant Breeding and Genetic Resources: Lectures', 'Abiotic stress', TeachingMaterialType::Slides, ['en' => 'Breeding field crops for abiotic stresses in the dry lands: slides', 'ar' => 'تربية المحاصيل الحقلية لتحمّل الإجهاد اللاأحيائي في الأراضي الجافة: الشرائح'], ['en' => 'The presentation of the paper of the same name (30 pages).', 'ar' => 'العرض التقديمي للورقة التي تحمل العنوان نفسه (30 صفحة).'], 'breeding-field-crops-abiotic-stress-slides.pdf', null],
            ['Plant Breeding and Genetic Resources: Lectures', 'Biodiversity', TeachingMaterialType::Slides, ['en' => 'Biodiversity: slides', 'ar' => 'التنوع الحيوي: الشرائح'], ['en' => 'A lecture on biodiversity, its importance and the threats it faces, in Arabic (46 pages).', 'ar' => 'محاضرة في التنوع الحيوي وأهميته ومهدداته، باللغة العربية (46 صفحة).'], 'biodiversity-slides.pdf', null],
            ['Plant Breeding and Genetic Resources: Lectures', 'Genetically modified crops', TeachingMaterialType::Slides, ['en' => 'Genetically modified crops, cotton as an example: slides', 'ar' => 'المحاصيل المحوّرة وراثياً، القطن مثالاً: الشرائح'], ['en' => 'The presentation of the paper of the same name, in Arabic (32 pages).', 'ar' => 'العرض التقديمي للورقة التي تحمل العنوان نفسه، باللغة العربية (32 صفحة).'], 'gm-crops-cotton-slides.pdf', null],
        ],
        'access' => TeachingAccess::Public,
    ],

    // Arabic names of the teaching courses and topics above (the stored term stays English).
    'terms' => [
        TermVocabulary::TeachingCourse->value => [
            'Evolution of Crop Plants and Genetic Resources' => 'تطور المحاصيل والموارد الوراثية',
            'Oil Crops' => 'المحاصيل الزيتية',
            'Research Methodology and Scientific Writing' => 'منهجية البحث والكتابة العلمية',
            'Plant Breeding and Genetic Resources: Lectures' => 'تربية النبات والموارد الوراثية: محاضرات',
            'Postgraduate Studies' => 'الدراسات العليا',
        ],
        TermVocabulary::TeachingTopic->value => [
            'Lectures' => 'المحاضرات',
            'Course text' => 'نص المقرر',
            'Plant breeding' => 'تربية النبات',
            'Course outline' => 'وصف المقرر',
            'Research methodology' => 'منهجية البحث',
            'Scientific writing' => 'الكتابة العلمية',
            'Quality of doctoral theses' => 'جودة رسائل الدكتوراه',
            'Abiotic stress' => 'الإجهاد اللاأحيائي',
            'Biodiversity' => 'التنوع الحيوي',
            'Genetically modified crops' => 'المحاصيل المحوّرة وراثياً',
        ],
    ],

    // Videos: the Ahfad ceremony recordings (uploaded) and the SENAS opening (YouTube).
    'videos' => [
        [
            'title' => ['en' => 'Conferral of the honorary doctorate, Ahfad University for Women: part one', 'ar' => 'منح الدكتوراه الفخرية، جامعة الأحفاد للبنات: الجزء الأول'],
            'date' => '2019-10-19',
            'event' => ['en' => 'Ahfad University for Women graduation ceremony', 'ar' => 'حفل تخريج جامعة الأحفاد للبنات'],
            'location' => $omdurman,
            'description' => ['en' => 'Professor Khidir receives the honorary degree of Doctor of Science at the university\'s annual graduation ceremony.', 'ar' => 'البروفيسور خضر يتسلّم درجة الدكتوراه الفخرية في العلوم في حفل التخريج السنوي للجامعة.'],
            'provider' => VideoProvider::File,
            'file' => 'videos/ahfad-2019-ceremony-part-1.mp4',
            'thumbnail' => 'ahfad-2019-conferral.jpg',
            'is_featured' => true,
        ],
        [
            'title' => ['en' => 'Conferral of the honorary doctorate, Ahfad University for Women: part two', 'ar' => 'منح الدكتوراه الفخرية، جامعة الأحفاد للبنات: الجزء الثاني'],
            'date' => '2019-10-19',
            'event' => ['en' => 'Ahfad University for Women graduation ceremony', 'ar' => 'حفل تخريج جامعة الأحفاد للبنات'],
            'location' => $omdurman,
            'description' => ['en' => 'A second recording from the ceremony.', 'ar' => 'تسجيل ثانٍ من الحفل.'],
            'provider' => VideoProvider::File,
            'file' => 'videos/ahfad-2019-ceremony-part-2.mp4',
            'thumbnail' => 'ahfad-2019-handshake.jpg',
        ],
        [
            'title' => ['en' => 'Opening ceremony of the Sudanese Encyclopedia of Agricultural Sciences', 'ar' => 'حفل افتتاح الموسوعة السودانية للعلوم الزراعية'],
            'date' => null,
            'event' => ['en' => 'Launch of the Sudanese Encyclopedia of Agricultural Sciences (SENAS)', 'ar' => 'تدشين الموسوعة السودانية للعلوم الزراعية'],
            'location' => null,
            'description' => ['en' => 'The opening ceremony of the encyclopedia, published by SENAS Sudan.', 'ar' => 'حفل افتتاح الموسوعة، من نشر قناة الموسوعة السودانية.'],
            'provider' => VideoProvider::YouTube,
            'url' => 'https://www.youtube.com/watch?v=zBOeq_ozKvQ',
            'thumbnail' => 'honour-senas-medal-front.jpg',
        ],
    ],
];
