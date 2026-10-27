# Arabic translations: native-speaker review

Every Arabic interface string on the site was written for this release by the development team, not by a native speaker. All of them are listed below for review. Mark each row ✅ (fine), or write the corrected Arabic beside it; corrections go into the matching JSON key in `lang/ar.json` (through the translation fragments) or the PHP files in `lang/ar/`.

## Points to look at first

These choices were made deliberately but need a native speaker's judgement:

1. **The Professor's name and title.** "Professor Mohamed Osman Khidir" is written الأستاذ الدكتور محمد عثمان خضر; "Prof. Khidir / Prof. Mokhidir" is الأستاذ الدكتور خضر. Please confirm the spelling of خضر and the title the family prefers.
2. **Section names.** About = عن الأستاذ الدكتور خضر (chapter: سيرته), Work = أعماله, Archive = الأرشيف, Community = المجتمع العلمي, Commentary Corner = ركن المقالات, Tributes = كلمات الوفاء, Videos = المرئيات, Awards = الجوائز والتكريمات.
3. **Plurals and counts.** Arabic plural forms are used through trans_choice (e.g. منشور واحد / منشوران / 3 منشورات / 11 منشورًا). In several places (chat, form counters, the activity feed) a neutral "label: number" pattern was used instead to avoid agreement problems: "الأعضاء: :count", "الأحرف: :count من :max", "عدد الكلمات: :count".
4. **Gender-neutral wording.** Messages about actions ("تم نشر :type", "إضافة :type", activity entries such as "أمينة: اعتماد كلمة وفاء من يوسف") avoid gendered verbs; please check they read naturally.
5. **Decades.** ":decades" shows as "عقد 1970"; "السبعينيات" would be more idiomatic but needs a mapping per decade.
6. **Tone of key phrases.** Hero label "تكريمًا لـ" (In honour of), "اقرأ قصته", "اترك كلمة وفاء", Scroll "مرّر", Cite "استشهاد", Explore "استكشف", "ليست لهذه العيون" (Not for these eyes), the greeting "طاب يومك", "مع خالص التحية،" in emails, "قاعة القراءة" (the reading room), "قاعة المراسلات" (the correspondence room), "أمين الأرشيف" (the archivist).
7. **Admin terms.** "العربية ناقصة" (Arabic missing), "تعليق العضوية" (Suspend), "إغلاق البلاغ" (Resolve), "الأرقام الهندية (٠–٩)" (Arabic-Indic digits), "مُرسِل البريد", "المواقع المُحيلة", "عمق التمرير", short field names النسبة (attribution), الكاتب (byline), التصدير (epigraph).
8. **Long texts.** The privacy page, the community conduct rules and privacy note, and the email bodies are full translations and deserve a careful read.
9. **Typography details.** Some strings contain an invisible left-to-right mark (U+200E) after "https://" and "mailto:" so punctuation sits correctly; year ranges such as 1950–1959 display in bidi order (they read correctly right to left). Captions join place and year with the Arabic comma.
10. **Placeholder copy.** Text marked "[نص مؤقت]" is development placeholder content, not translation; it is replaced by the client's real Arabic content, not reviewed.

**1980 interface strings** in `lang/ar.json`, plus the group files in `lang/ar/` (validation, auth, passwords, pagination, chat).

## Public site: navigation, home, About, CV, publications, awards, search, errors and the built-in page copy (277)

| ✓ | English | Arabic |
|---|---|---|
| ☐ | (opens in a new tab) | (يُفتح في علامة تبويب جديدة) |
| ☐ | ,  | ،  |
| ☐ | , account menu | ، قائمة الحساب |
| ☐ | 17th edition, bibliography | الإصدار السابع عشر، قائمة المراجع |
| ☐ | 7th edition | الإصدار السابع |
| ☐ | 9th edition | الإصدار التاسع |
| ☐ | :count more in :section | المزيد في :section (:count) |
| ☐ | :decades | عقد :decade |
| ☐ | :results in :sections | :results في :sections |
| ☐ | <p>To learn which pages are read, this site keeps its own simple visitor count. It is run by the site itself: no other company sees it, and nothing is shared or sold.</p><h2>What is counted</h2><ul><li>The page opened, and the site it was reached from (only its name, such as a search engine).</li><li>How long the page was read: the time the tab was in view while you scrolled or used the page within the last :idle_seconds seconds, and how far down you scrolled.</li><li>The country you are in, worked out from your internet address, which is then forgotten. Nothing more precise than the country is kept.</li><li>Whether you use a phone, tablet or computer, and the name of the browser.</li></ul><h2>What is not</h2><ul><li>No cookies are set and nothing is stored in your browser for this count.</li><li>No internet address, name, email or other personal detail is stored. Visits on the same day are told apart by a scrambled code that changes every day and cannot be turned back into anything about you.</li><li>If your browser asks sites not to track you (Do Not Track or Global Privacy Control), nothing is counted at all.</li></ul><h2>How long it is kept</h2><p>The record of each page view is deleted after :raw_days days. After that only daily totals remain (for example, how many people read a page on a given day), and those are deleted after :rollup_months months.</p> | <p>لمعرفة الصفحات التي تُقرأ، يحتفظ هذا الموقع بإحصاء بسيط خاص به لعدد الزوار. ويتولاه الموقع نفسه: لا تطّلع عليه أي جهة أخرى، ولا يُشارك أو يُباع منه شيء.</p><h2>ما الذي يُحصى</h2><ul><li>الصفحة التي فُتحت، والموقع الذي جئت منه (اسمه فقط، مثل محرك بحث).</li><li>مدة قراءة الصفحة: الوقت الذي كانت فيه علامة التبويب ظاهرة بينما كنت تمرّر الصفحة أو تستخدمها خلال آخر :idle_seconds ثانية، ومدى ما مرّرته منها إلى الأسفل.</li><li>البلد الذي توجد فيه، ويُستنتج من عنوان الإنترنت الخاص بك ثم يُنسى العنوان. ولا يُحفظ أي شيء أدق من البلد.</li><li>هل تستخدم هاتفًا أم جهازًا لوحيًا أم حاسوبًا، واسم المتصفح.</li></ul><h2>ما الذي لا يُحصى</h2><ul><li>لا تُضبط أي ملفات تعريف ارتباط، ولا يُخزَّن شيء في متصفحك لأجل هذا الإحصاء.</li><li>لا يُخزَّن عنوان إنترنت ولا اسم ولا بريد إلكتروني ولا أي بيانات شخصية أخرى. وتُميَّز الزيارات في اليوم الواحد برمز مشفّر يتغير كل يوم، ولا يمكن تحويله إلى أي معلومة عنك.</li><li>إذا طلب متصفحك من المواقع عدم تتبعك (Do Not Track أو Global Privacy Control)، فلا يُحصى أي شيء على الإطلاق.</li></ul><h2>مدة الاحتفاظ بالبيانات</h2><p>يُحذف سجل كل مشاهدة للصفحة بعد :raw_days يومًا. وبعد ذلك لا يبقى سوى المجاميع اليومية (مثل عدد من قرؤوا صفحة ما في يوم معيّن)، وتُحذف هذه بدورها بعد :rollup_months شهرًا.</p> |
| ☐ | A book of remembrance, open to all. | سجلّ ذكرى مفتوح للجميع. |
| ☐ | A life in chapters | حياة في فصول |
| ☐ | A private circle of colleagues and former students. | دائرة خاصة من الزملاء والطلاب السابقين. |
| ☐ | A private circle of colleagues and former students. Membership is by application. | دائرة خاصة من الزملاء والطلاب السابقين. العضوية بتقديم طلب. |
| ☐ | A tribute | كلمة وفاء |
| ☐ | About | عن الأستاذ الدكتور خضر |
| ☐ | About Prof. Mokhidir | عن الأستاذ الدكتور خضر |
| ☐ | Academics and practitioners in agriculture and the related sciences, former students of Professor Khidir, and colleagues from the institutions where he taught and served. Please tell us briefly who you are and why you would like to join. | الأكاديميون والممارسون في الزراعة والعلوم المتصلة بها، وطلاب الأستاذ الدكتور خضر السابقون، والزملاء من المؤسسات التي درّس فيها وخدمها. نرجو أن تعرّفنا بنفسك باختصار وبسبب رغبتك في الانضمام. |
| ☐ | Account | الحساب |
| ☐ | Achievement | إنجاز |
| ☐ | Achievements | الإنجازات |
| ☐ | active | مفعّلة |
| ☐ | All albums | جميع الألبومات |
| ☐ | All commentary | جميع المقالات |
| ☐ | All other information | جميع المعلومات الأخرى |
| ☐ | All recordings | جميع التسجيلات |
| ☐ | All topics | جميع الموضوعات |
| ☐ | All types | جميع الأنواع |
| ☐ | All videos | جميع المرئيات |
| ☐ | Appearance | المظهر |
| ☐ | Apply to join | قدّم طلب الانضمام |
| ☐ | Apply to join the community | قدّم طلب الانضمام إلى المجتمع |
| ☐ | Appointment | منصب |
| ☐ | Appointments | المناصب |
| ☐ | Archive | الأرشيف |
| ☐ | Award | جائزة |
| ☐ | Award and honour | جائزة وتكريم |
| ☐ | Awards | الجوائز والتكريمات |
| ☐ | Awards and honours | الجوائز والتكريمات |
| ☐ | Awards and recognitions will be listed here once they are published. | ستُدرج الجوائز والتكريمات هنا فور نشرها. |
| ☐ | Back shortly | نعود قريبًا |
| ☐ | Back to tributes | العودة إلى كلمات الوفاء |
| ☐ | Be courteous. Disagree with ideas, never with people. | التزم اللباقة. اختلف مع الأفكار، لا مع الأشخاص. |
| ☐ | Biography, portrait and the facts of a life in science. | السيرة والصورة وحقائق حياة في رحاب العلم. |
| ☐ | Biography, portrait, title and affiliation, areas of expertise and key facts. | السيرة، والصورة، واللقب والانتماء المؤسسي، ومجالات التخصص، وأبرز الحقائق. |
| ☐ | Book | كتاب |
| ☐ | Book chapter | فصل في كتاب |
| ☐ | Books, articles and papers by type and year, with citations and links. | الكتب والمقالات والأوراق العلمية حسب النوع والسنة، مع الاستشهادات والروابط. |
| ☐ | Books, articles and papers by type and year. | الكتب والمقالات والأوراق العلمية حسب النوع والسنة. |
| ☐ | Books, articles and papers, with citations and links, by year and type. | الكتب والمقالات والأوراق العلمية، مع الاستشهادات والروابط، حسب السنة والنوع. |
| ☐ | Breadcrumb | مسار التنقل |
| ☐ | Captioned photographs grouped in albums. | صور موثّقة بتعليقاتها، مجمّعة في ألبومات. |
| ☐ | Captioned photographs in albums. | صور موثّقة بتعليقاتها في ألبومات. |
| ☐ | Change something | عدّل شيئًا |
| ☐ | Chapter | الفصل |
| ☐ | Chapter :numeral: | الفصل :numeral: |
| ☐ | Chapters | الفصول |
| ☐ | Citation copied to the clipboard | تم نسخ الاستشهاد إلى الحافظة |
| ☐ | Citation format | صيغة الاستشهاد |
| ☐ | Cite | استشهاد |
| ☐ | Clear filters | مسح خيارات التصفية |
| ☐ | Close the filters | إغلاق خيارات التصفية |
| ☐ | Colleagues and students, in conversation. | زملاء وطلاب في حوار متصل. |
| ☐ | Commentary | المقالات |
| ☐ | Commentary Corner | ركن المقالات |
| ☐ | Community | المجتمع العلمي |
| ☐ | Community chat | محادثة المجتمع |
| ☐ | Community sign in | دخول أعضاء المجتمع |
| ☐ | Conduct | آداب المشاركة |
| ☐ | Conference paper | ورقة مؤتمر |
| ☐ | Contact | التواصل |
| ☐ | Continue reading | تابع القراءة |
| ☐ | Continue the story | تابع القصة |
| ☐ | Copied | تم النسخ |
| ☐ | Copy | نسخ |
| ☐ | CV & Achievements | السيرة الذاتية والإنجازات |
| ☐ | CV and Achievements | السيرة الذاتية والإنجازات |
| ☐ | Dark | داكن |
| ☐ | Dashboard | لوحة التحكم |
| ☐ | Dismiss | إخفاء |
| ☐ | Do not post advertising, fundraising or personal data about others. | لا تنشر إعلانات أو دعوات لجمع التبرعات أو بيانات شخصية عن الآخرين. |
| ☐ | Each decade of his life, drawn from the record of his education, appointments and honours. | كل عقد من حياته، مستمدًّا من سجلّ تعليمه ومناصبه وتكريماته. |
| ☐ | Earliest | الأقدم |
| ☐ | Education | التعليم |
| ☐ | Education, appointments and honours, set out on the page. | التعليم والمناصب والتكريمات، معروضة على الصفحة. |
| ☐ | Education, appointments, honours and milestones, presented on the page. | التعليم والمناصب والتكريمات والمحطات البارزة، معروضة على الصفحة. |
| ☐ | Enter for every result | اضغط Enter لعرض جميع النتائج |
| ☐ | Error | خطأ |
| ☐ | Escape to close | اضغط Escape للإغلاق |
| ☐ | Essays and reflections will be published here in due course. | ستُنشر المقالات والتأملات هنا في حينها. |
| ☐ | Essays and reflections, arranged by topic. | مقالات وتأملات، مرتبة حسب الموضوع. |
| ☐ | Expertise | مجالات التخصص |
| ☐ | Explore | استكشف |
| ☐ | Featured recording | تسجيل مميّز |
| ☐ | Featured tribute | كلمة وفاء مختارة |
| ☐ | File | ملف |
| ☐ | Fill in the form below. You will receive an email to confirm your address, and a second one once your application has been reviewed. | املأ النموذج أدناه. ستصلك رسالة بريد إلكتروني لتأكيد عنوانك، وأخرى بعد مراجعة طلبك. |
| ☐ | For approved members of the circle. | للأعضاء المقبولين في الدائرة. |
| ☐ | For LaTeX reference managers | لبرامج إدارة المراجع في LaTeX |
| ☐ | From | من |
| ☐ | From :year | منذ :year |
| ☐ | Further information will be added here as it becomes available. | ستُضاف هنا معلومات أخرى حين تتوافر. |
| ☐ | Further notes and resources that do not belong to another section. | ملاحظات ومصادر إضافية لا تنتمي إلى قسم آخر. |
| ☐ | Further notes and resources. | ملاحظات ومصادر إضافية. |
| ☐ | Go to page :page | الانتقال إلى الصفحة :page |
| ☐ | He taught us how to ask the right questions. | علّمنا كيف نطرح الأسئلة الصحيحة. |
| ☐ | His lessons continue in every student he taught. | تبقى دروسه حيّة في كل طالب علّمه. |
| ☐ | His work in numbers | أعماله بالأرقام |
| ☐ | Home | الرئيسية |
| ☐ | How this site counts its readers | كيف يحصي هذا الموقع قرّاءه |
| ☐ | How this site counts its readers: first-party, without cookies, without personal data. | كيف يحصي هذا الموقع قرّاءه: إحصاء ذاتي، بلا ملفات تعريف ارتباط، وبلا بيانات شخصية. |
| ☐ | How you knew him | كيف عرفته |
| ☐ | I agree that this tribute may be published on this site with my name, relationship and location. | أوافق على نشر كلمة الوفاء هذه على الموقع مع اسمي وصلتي به ومكاني. |
| ☐ | I have read the conduct rules and agree to follow them. | قرأت آداب المشاركة وأوافق على الالتزام بها. |
| ☐ | I understand how my details will be used and kept, as described in the privacy note. | أدرك كيف ستُستخدم بياناتي وتُحفظ، وفق ما ورد في بيان الخصوصية. |
| ☐ | In honour of | تكريمًا لـ |
| ☐ | In honour of Professor Mohamed Osman Khidir | تكريمًا للأستاذ الدكتور محمد عثمان خضر |
| ☐ | Join the community | انضم إلى المجتمع |
| ☐ | Journal article | مقالة في مجلة علمية |
| ☐ | Keep discussion to scholarship, teaching and the life and work of Professor Khidir. | اجعل النقاش مقتصرًا على العلم والتعليم وحياة الأستاذ الدكتور خضر وأعماله. |
| ☐ | Key facts | حقائق بارزة |
| ☐ | Knowledge is the light we pass on. | العلم نورٌ نحمله إلى من بعدنا. |
| ☐ | Last updated | آخر تحديث |
| ☐ | Latest | الأحدث |
| ☐ | Latest publication | أحدث منشور |
| ☐ | Leadership | القيادة |
| ☐ | Leave a tribute | اترك كلمة وفاء |
| ☐ | Lecture notes and course materials by course and topic. | مذكرات المحاضرات والمواد الدراسية حسب المقرر والموضوع. |
| ☐ | Lecture notes and course materials grouped by course, topic and type. | مذكرات المحاضرات والمواد الدراسية، مصنّفة حسب المقرر والموضوع والنوع. |
| ☐ | Lectures, interviews and events, with dates and locations. | محاضرات ومقابلات وفعاليات، مع تواريخها وأماكنها. |
| ☐ | Lectures, interviews and events. | محاضرات ومقابلات وفعاليات. |
| ☐ | Life | سيرته |
| ☐ | Light | فاتح |
| ☐ | Links for :title | روابط «:title» |
| ☐ | Members of the Scientific Community also see their teaching files here when signed in. | يرى أعضاء المجتمع العلمي هنا أيضًا المواد التعليمية المخصّصة لهم عند تسجيل الدخول. |
| ☐ | Menu | القائمة |
| ☐ | Messages are visible to approved members and administrators only. | لا يطّلع على الرسائل إلا الأعضاء المعتمدون والمديرون. |
| ☐ | Messages from students, colleagues and friends, published after review. | رسائل من الطلاب والزملاء والأصدقاء، تُنشر بعد مراجعتها. |
| ☐ | Messages from students, colleagues and friends. | رسائل من الطلاب والزملاء والأصدقاء. |
| ☐ | Messages may be reported to the administrators, who may remove them and suspend members who break these rules. | يمكن الإبلاغ عن الرسائل إلى المديرين، ولهم أن يحذفوها وأن يعلّقوا عضوية من يخالف هذه القواعد. |
| ☐ | Mohamed Osman Khidir | محمد عثمان خضر |
| ☐ | More from this event | المزيد من هذه الفعالية |
| ☐ | Newest album | أحدث ألبوم |
| ☐ | Next page | الصفحة التالية |
| ☐ | No materials match these filters. | لا توجد مواد تطابق خيارات التصفية هذه. |
| ☐ | No publications match these filters | لا توجد منشورات تطابق خيارات التصفية هذه |
| ☐ | No results | لا توجد نتائج |
| ☐ | No tributes from :relationship have been published yet. | لم تُنشر بعد أي كلمة وفاء من :relationship. |
| ☐ | No tributes have been published yet. Yours could be the first. | لم تُنشر أي كلمة وفاء بعد. قد تكون كلمتك الأولى. |
| ☐ | Not for these eyes | ليست لهذه العيون |
| ☐ | Nothing has been catalogued under these filters yet. | لم يُفهرس شيء ضمن خيارات التصفية هذه بعد. |
| ☐ | Nothing has been published in this section yet. | لم يُنشر شيء في هذا القسم بعد. |
| ☐ | Nothing has been written under this topic yet. | لم يُكتب شيء في هذا الموضوع بعد. |
| ☐ | Nothing in the archive answers to that. Try another word, or browse the chapters. | لا شيء في الأرشيف يطابق ما تبحث عنه. جرّب كلمة أخرى، أو تصفّح الفصول. |
| ☐ | Open the chat | افتح المحادثة |
| ☐ | Or turn to a chapter | أو انتقل إلى أحد الفصول |
| ☐ | Other Information | معلومات أخرى |
| ☐ | Page :current of :last | صفحة :current من :last |
| ☐ | Page not found | الصفحة غير موجودة |
| ☐ | Pages of the book | صفحات الكتاب |
| ☐ | Pagination | ترقيم الصفحات |
| ☐ | Photographs and recordings from six decades. | صور وتسجيلات من ستة عقود. |
| ☐ | Photographs will be gathered into albums here once they are published. | ستُجمع الصور في ألبومات هنا فور نشرها. |
| ☐ | Pictures | الصور |
| ☐ | present | حتى الآن |
| ☐ | Preview | معاينة |
| ☐ | Preview your tribute | معاينة كلمتك |
| ☐ | Previous page | الصفحة السابقة |
| ☐ | Primary | التنقل الرئيسي |
| ☐ | Privacy | الخصوصية |
| ☐ | Prizes and recognitions, with organisation and year. | الجوائز والتكريمات، مع الجهة والسنة. |
| ☐ | Prizes and recognitions, with the awarding organisation and year. | الجوائز والتكريمات، مع الجهة المانحة والسنة. |
| ☐ | Professor | الأستاذ الدكتور |
| ☐ | Project | مشروع |
| ☐ | Projects | المشروعات |
| ☐ | Publication | منشور |
| ☐ | Publications | المنشورات |
| ☐ | Publications will be listed here as they are catalogued. | ستُدرج المنشورات هنا مع استكمال فهرستها. |
| ☐ | Publications, essays, lectures, awards… | المنشورات، المقالات، المحاضرات، الجوائز… |
| ☐ | Read his story | اقرأ قصته |
| ☐ | Read more | اقرأ المزيد |
| ☐ | Read online | اقرأ على الإنترنت |
| ☐ | Recordings of lectures, interviews and events will appear here once they are published. | ستظهر هنا تسجيلات المحاضرات والمقابلات والفعاليات فور نشرها. |
| ☐ | Refine | تصفية |
| ☐ | Refine the catalogue | تصفية الفهرس |
| ☐ | Report | تقرير |
| ☐ | Return home | العودة إلى الرئيسية |
| ☐ | Scholarship is the one inheritance that grows each time it is shared. | العلم هو الميراث الوحيد الذي يزداد كلما تقاسمناه. |
| ☐ | Scientific Community | المجتمع العلمي |
| ☐ | Scroll | مرّر |
| ☐ | Search | البحث |
| ☐ | Search results | نتائج البحث |
| ☐ | Search the archive | ابحث في الأرشيف |
| ☐ | Search: :query | البحث: :query |
| ☐ | Searching… | جارٍ البحث… |
| ☐ | Sections | الأقسام |
| ☐ | Selected works | أعمال مختارة |
| ☐ | Send tribute | أرسل كلمة الوفاء |
| ☐ | Share a memory, a lesson or a moment. A few lines are enough. | شارك ذكرى أو درسًا أو لحظة. تكفي بضعة أسطر. |
| ☐ | Share a memory, a lesson or a moment. Every tribute is read before it is published. | شارك ذكرى أو درسًا أو لحظة. تُقرأ كل كلمة وفاء قبل نشرها. |
| ☐ | Share only material you have the right to share, and credit its authors. | لا تشارك إلا ما يحق لك مشاركته، وانسبه إلى أصحابه. |
| ☐ | Signed in as :name | تم تسجيل الدخول باسم :name |
| ☐ | Site menu | قائمة الموقع |
| ☐ | Site notice | إشعار الموقع |
| ☐ | Site sections | أقسام الموقع |
| ☐ | Skip to content | انتقل إلى المحتوى |
| ☐ | Some materials are shared with members of the Scientific Community only. | بعض المواد متاحة لأعضاء المجتمع العلمي فقط. |
| ☐ | Something went wrong | حدث خطأ ما |
| ☐ | Source | المصدر |
| ☐ | Student supervised | طالب تحت إشرافه |
| ☐ | Students supervised | طلاب تحت إشرافه |
| ☐ | Switch to dark theme | التبديل إلى المظهر الداكن |
| ☐ | Switch to light theme | التبديل إلى المظهر الفاتح |
| ☐ | Teacher, scholar and mentor. A life devoted to knowledge and to those who sought it. | معلّمٌ وعالِمٌ ومرشد. حياةٌ نذرها للعلم ولكل من سعى إليه. |
| ☐ | Teaching | التدريس |
| ☐ | Teaching Materials | المواد التعليمية |
| ☐ | Teaching materials will appear here as they are prepared for sharing. | ستظهر المواد التعليمية هنا حين تُهيّأ للمشاركة. |
| ☐ | Thank you for applying. An administrator will read your application and you will receive an email once a decision has been made. | شكرًا لتقديم طلبك. سيطّلع أحد المديرين عليه، وستصلك رسالة بريد إلكتروني فور اتخاذ القرار. |
| ☐ | Thank you for your interest in the Scientific Community. On this occasion your application has not been accepted. If you believe there has been a mistake, please write to the site administrator. | نشكر لك اهتمامك بالمجتمع العلمي. لم يُقبل طلبك هذه المرة. وإن رأيت أن في الأمر خطأً، فيُرجى مراسلة مدير الموقع. |
| ☐ | Thank you. Please confirm your email address using the link we have sent you; your application will then be reviewed. | شكرًا لك. يُرجى تأكيد عنوان بريدك الإلكتروني عبر الرابط الذي أرسلناه إليك، ثم ستتم مراجعة طلبك. |
| ☐ | Thank you. Your tribute will appear once it has been reviewed. | شكرًا لك. ستظهر كلمتك بعد مراجعتها. |
| ☐ | The archivist | أمين الأرشيف |
| ☐ | The biography is being prepared and will appear here once it is published. | يجري إعداد السيرة، وستظهر هنا فور نشرها. |
| ☐ | The book of remembrance | سجلّ الذكرى |
| ☐ | The chapters of his life will appear here as his record is catalogued. | ستظهر فصول حياته هنا مع استكمال فهرسة سجلّه. |
| ☐ | The curriculum vitae is being compiled and will appear here once it is published. | يجري إعداد السيرة الذاتية، وستظهر هنا فور نشرها. |
| ☐ | The curriculum vitae of :name is published to be read online only, at :url. | نُشرت السيرة الذاتية لـ:name للقراءة على الإنترنت فقط، على العنوان :url. |
| ☐ | The fault is ours, not yours. Please return in a little while. | الخطأ منّا لا منك. يُرجى العودة بعد قليل. |
| ☐ | The figures will appear once his publications, appointments and honours are catalogued. | ستظهر الأرقام بعد فهرسة منشوراته ومناصبه وتكريماته. |
| ☐ | The full biography, his fields of expertise and the facts of a long career. | السيرة الكاملة، ومجالات تخصصه، وأبرز محطات مسيرة طويلة. |
| ☐ | The man, his path and his honours. | الرجل، ومسيرته، وتكريماته. |
| ☐ | The members' room, for those admitted. | قاعة الأعضاء، لمن قُبلت عضويتهم. |
| ☐ | The page has expired | انتهت صلاحية الصفحة |
| ☐ | The Scientific Community is a private space for researchers, former students and colleagues of Professor Khidir to keep in touch, share work in progress and continue the conversations he began. Membership is by application and every application is read by an administrator. | المجتمع العلمي فضاء خاص للباحثين وطلاب الأستاذ الدكتور خضر السابقين وزملائه، للبقاء على تواصل، وتبادل الأعمال الجارية، ومواصلة الحوارات التي بدأها. العضوية بتقديم طلب، ويطّلع أحد المديرين على كل طلب. |
| ☐ | The site is resting for a moment of care. Please return soon. | يستريح الموقع لحظةً للعناية به. يُرجى العودة قريبًا. |
| ☐ | This album has no photographs yet. | لا يضم هذا الألبوم صورًا بعد. |
| ☐ | This is how your tribute will appear. Change anything you like, or send it as it is. | هكذا ستظهر كلمتك. عدّل ما تشاء، أو أرسلها كما هي. |
| ☐ | This page has been misplaced in the archive. | ضلّت هذه الصفحة طريقها في الأرشيف. |
| ☐ | This page is kept for those with permission to see it. | هذه الصفحة محفوظة لمن يملكون إذنًا بالاطلاع عليها. |
| ☐ | To | إلى |
| ☐ | to :section | إلى :section |
| ☐ | to download them. | لتنزيلها. |
| ☐ | Too much time passed in quiet. Please go back and try once more. | مضى وقت طويل في سكون. يُرجى الرجوع والمحاولة مرة أخرى. |
| ☐ | Tributes | كلمات الوفاء |
| ☐ | Type a word or two to search across every chapter of the site. | اكتب كلمة أو كلمتين للبحث في جميع فصول الموقع. |
| ☐ | Undated | غير مؤرَّخ |
| ☐ | Until :year | حتى :year |
| ☐ | Videos | المرئيات |
| ☐ | Visit | زيارة |
| ☐ | Welcome to the community | مرحبًا بك في المجتمع |
| ☐ | What he wrote, taught and argued. | ما كتبه ودرّسه وناقشه. |
| ☐ | Where you are writing from | من أين تكتب |
| ☐ | Who may join | من يحق له الانضمام |
| ☐ | Words from his students, colleagues and friends, and a place to add your own. | كلمات من طلابه وزملائه وأصدقائه، ومكان لتضيف كلمتك. |
| ☐ | Work | أعماله |
| ☐ | Year of teaching | سنة في التدريس |
| ☐ | Years | السنوات |
| ☐ | Years of teaching | سنوات في التدريس |
| ☐ | You are looking at a draft. Visitors cannot see it until it is published. | أنت تطّلع على مسودة، ولن يتمكن الزوار من رؤيتها حتى تُنشر. |
| ☐ | You are no longer a member | لم تعد عضوًا |
| ☐ | You are signed in but have not applied to join the community. | لقد سجّلت الدخول، لكنك لم تتقدم بطلب للانضمام إلى المجتمع. |
| ☐ | Your access to the community chat has been suspended by an administrator. If you would like to discuss this, please write to the site administrator. | علّق أحد المديرين وصولك إلى محادثة المجتمع. إن رغبت في مناقشة ذلك، فيُرجى مراسلة مدير الموقع. |
| ☐ | Your account | حسابك |
| ☐ | Your application has been approved. The chat is open to you. | تمت الموافقة على طلبك. المحادثة متاحة لك الآن. |
| ☐ | Your application is being reviewed | طلبك قيد المراجعة |
| ☐ | Your application was not accepted | لم يُقبل طلبك |
| ☐ | Your membership is suspended | عضويتك معلّقة |
| ☐ | Your membership of the Scientific Community has ended. You are welcome to apply again. | انتهت عضويتك في المجتمع العلمي. يسعدنا أن تتقدم بطلب جديد. |
| ☐ | Your name, email address, affiliation, academic field and reason for joining are used only to review your application and, if it is approved, to show your name and affiliation beside your messages to other members. They are kept while your account exists and are never published, sold or shared outside the site. Administrators can see your application; other members see only your name and affiliation. You can ask for your account to be removed at any time by writing to the site administrator. | لا تُستخدم بيانات اسمك وبريدك الإلكتروني وانتمائك المؤسسي وتخصصك الأكاديمي وسبب انضمامك إلا لمراجعة طلبك، ثم لعرض اسمك وانتمائك بجانب رسائلك إلى الأعضاء الآخرين إذا قُبل الطلب. وتُحفظ هذه البيانات ما دام حسابك قائمًا، ولا تُنشر ولا تُباع ولا تُشارك خارج الموقع أبدًا. يطّلع المديرون على طلبك، ولا يرى الأعضاء الآخرون سوى اسمك وانتمائك. ويمكنك طلب حذف حسابك في أي وقت بمراسلة مدير الموقع. |
| ☐ | Your own words, published after review. | كلماتك أنت، تُنشر بعد المراجعة. |
| ☐ | {1} :count entry\|[2,*] :count entries | {1} بند واحد\|{2} بندان\|[3,10] :count بنود\|[11,*] :count بندًا |
| ☐ | {1} :count photograph\|[2,*] :count photographs | {1} صورة واحدة\|{2} صورتان\|[3,10] :count صور\|[11,*] :count صورة |
| ☐ | {1} :count record\|[2,*] :count records | {1} سجل واحد\|{2} سجلان\|[3,10] :count سجلات\|[11,*] :count سجلًا |
| ☐ | {1} :count result\|[2,*] :count results | {1} نتيجة واحدة\|{2} نتيجتان\|[3,10] :count نتائج\|[11,*] :count نتيجة |
| ☐ | {1} :count section\|[2,*] :count sections | {1} قسم واحد\|{2} قسمين\|[3,10] :count أقسام\|[11,*] :count قسمًا |
| ☐ | {1} Showing :first–:last of :count record\|[2,*] Showing :first–:last of :count records | {1} عرض :first–:last من أصل سجل واحد\|{2} عرض :first–:last من أصل سجلين\|[3,10] عرض :first–:last من أصل :count سجلات\|[11,*] عرض :first–:last من أصل :count سجلًا |
| ☐ | © :year Professor Mohamed Osman Khidir. All rights reserved. | © :year الأستاذ الدكتور محمد عثمان خضر. جميع الحقوق محفوظة. |

## Public site: videos, pictures, teaching, commentary, other information, tributes, community, share cards (126)

| ✓ | English | Arabic |
|---|---|---|
| ☐ | :count min read | {1} قراءة في دقيقة واحدة\|{2} قراءة في دقيقتين\|[3,10] قراءة في :count دقائق\|[11,*] قراءة في :count دقيقة |
| ☐ | A :from application cannot become :to. | لا يمكن تحويل طلب بحالة :from إلى :to. |
| ☐ | about :title | عن :title |
| ☐ | Academic field | التخصص الأكاديمي |
| ☐ | academic field | التخصص الأكاديمي |
| ☐ | Albums | الألبومات |
| ☐ | All | الكل |
| ☐ | Already a member? :link. | هل أنت عضو بالفعل؟ :link. |
| ☐ | An account with this email address already exists. Sign in to continue. | يوجد حساب بهذا البريد الإلكتروني. سجّل الدخول للمتابعة. |
| ☐ | anyone else | غيرهم |
| ☐ | Apply | تطبيق |
| ☐ | Approved | مقبول |
| ☐ | Archive (evergreen duotone) | أرشيفية (ثنائية اللون بالأخضر الداكن) |
| ☐ | As you would like it to appear beside your tribute. | كما تودّ أن يظهر بجانب كلمتك. |
| ☐ | Back | رجوع |
| ☐ | Captions | الترجمة النصية |
| ☐ | Captions for this recording (WebVTT) | الترجمة النصية لهذا التسجيل (WebVTT) |
| ☐ | Categories | الفئات |
| ☐ | Check your tribute before it is sent for review. | راجع كلمتك قبل إرسالها للمراجعة. |
| ☐ | Choose how you knew Professor Khidir. | اختر صلتك بالأستاذ الدكتور خضر. |
| ☐ | Close | إغلاق |
| ☐ | Colleague | زميل |
| ☐ | Colleagues | الزملاء |
| ☐ | Community members | أعضاء المجتمع العلمي |
| ☐ | Confirm password | تأكيد كلمة المرور |
| ☐ | Contents | المحتويات |
| ☐ | Credit: | المصدر: |
| ☐ | Decade of :decade | عقد :decade |
| ☐ | Download | تنزيل |
| ☐ | Download the file | نزّل الملف |
| ☐ | Email | البريد الإلكتروني |
| ☐ | Enter an email address in the form name@example.com, or leave it empty. | أدخل بريدًا إلكترونيًا بصيغة name@example.com، أو اترك الحقل فارغًا. |
| ☐ | Enter your academic field, for example soil science. | أدخل تخصصك الأكاديمي، مثل علوم التربة. |
| ☐ | Enter your email address. | أدخل بريدك الإلكتروني. |
| ☐ | Enter your name. | أدخل اسمك. |
| ☐ | Enter your university, institute or organisation. | أدخل جامعتك أو معهدك أو مؤسستك. |
| ☐ | Exercise | تمرين |
| ☐ | External link | رابط خارجي |
| ☐ | Family | العائلة |
| ☐ | Filter by relationship | التصفية حسب الصلة |
| ☐ | First published at :site; the original is linked at the end. | نُشرت أولًا في :site، ورابط الأصل في نهاية المقالة. |
| ☐ | For example soil science or rural development. | مثل علوم التربة أو التنمية الريفية. |
| ☐ | For members of the Scientific Community | لأعضاء المجتمع العلمي |
| ☐ | for the tribute from :name (PNG image) | لكلمة الوفاء من :name (صورة PNG) |
| ☐ | Friend | صديق |
| ☐ | Friends | الأصدقاء |
| ☐ | Further information | معلومات إضافية |
| ☐ | Further reading | قراءات أخرى |
| ☐ | Landscape | عرضية |
| ☐ | Lecture notes | مذكرات المحاضرات |
| ☐ | More albums | ألبومات أخرى |
| ☐ | More commentary | مقالات أخرى |
| ☐ | More videos | مرئيات أخرى |
| ☐ | Next | التالي |
| ☐ | Next photograph | الصورة التالية |
| ☐ | Not applied | لم يتقدّم |
| ☐ | Open | فتح |
| ☐ | Optional, and never shown publicly. Only used if we need to reach you about your tribute. | اختياري، ولا يُعرض للعموم أبدًا. لا يُستخدم إلا إذا احتجنا إلى التواصل معك بشأن كلمتك. |
| ☐ | Optional. Town or city and country, for example Khartoum, Sudan. | اختياري. المدينة والبلد، مثل: الخرطوم، السودان. |
| ☐ | Other | أخرى |
| ☐ | Other materials | مواد أخرى |
| ☐ | Others | آخرون |
| ☐ | Panorama | بانورامية |
| ☐ | Password | كلمة المرور |
| ☐ | Pending review | قيد المراجعة |
| ☐ | Photographs | الصور |
| ☐ | Photographs from the :decade | صور من :decade |
| ☐ | Photographs in :album | صور ألبوم :album |
| ☐ | Play | تشغيل |
| ☐ | Please choose one of the listed relationships. | يُرجى اختيار إحدى الصلات المدرجة. |
| ☐ | Portrait | طولية |
| ☐ | Portrait (warm monochrome) | صورة شخصية (أحادية اللون دافئة) |
| ☐ | Previous | السابق |
| ☐ | Previous photograph | الصورة السابقة |
| ☐ | Public | عام |
| ☐ | Read the original | اقرأ الأصل |
| ☐ | Read the privacy note | اقرأ بيان الخصوصية |
| ☐ | Reading | قراءات |
| ☐ | reason for joining | سبب الانضمام |
| ☐ | Rejected | مرفوض |
| ☐ | Remembering | في ذكراه |
| ☐ | Say briefly why you would like to join. | اذكر باختصار سبب رغبتك في الانضمام. |
| ☐ | Share a memory of Professor Khidir. Tributes are read before they are published. | شاركنا ذكرى عن الأستاذ الدكتور خضر. تُقرأ كلمات الوفاء قبل نشرها. |
| ☐ | Share card | بطاقة المشاركة |
| ☐ | Shorten your reason to :max characters or fewer. | اختصر السبب إلى :max حرفًا أو أقل. |
| ☐ | Shorten your tribute to :max characters or fewer. | اختصر كلمتك إلى :max حرفًا أو أقل. |
| ☐ | Sign in | تسجيل الدخول |
| ☐ | Slides | شرائح العرض |
| ☐ | Square | مربعة |
| ☐ | Step :step of :total | الخطوة :step من :total |
| ☐ | Student | طالب |
| ☐ | Student, colleague, family or friend. | طالب، أو زميل، أو من العائلة، أو صديق. |
| ☐ | Students | الطلاب |
| ☐ | Suspended | موقوف |
| ☐ | Syllabus | المنهج الدراسي |
| ☐ | Thank you | شكرًا لك |
| ☐ | This browser cannot play the video. :link to watch it. | لا يستطيع هذا المتصفح تشغيل التسجيل. :link لمشاهدته. |
| ☐ | This link has expired. Open the material again from the Teaching Materials page. | انتهت صلاحية هذا الرابط. افتح المادة مجددًا من صفحة المواد التعليمية. |
| ☐ | This recording is hosted on another site. | هذا التسجيل مستضاف على موقع آخر. |
| ☐ | Through the decades | عبر العقود |
| ☐ | Tick the box to agree that your tribute may be published. | ضع علامة في المربع للموافقة على نشر كلمتك. |
| ☐ | Tick the box to agree to the conduct rules. | ضع علامة في المربع للموافقة على قواعد السلوك. |
| ☐ | Tick the box to confirm you have read the privacy note. | ضع علامة في المربع لتأكيد اطلاعك على بيان الخصوصية. |
| ☐ | to open :title | لفتح :title |
| ☐ | Too many tributes have been submitted from this address recently. Please try again later. | أُرسلت كلمات وفاء كثيرة من هذا العنوان مؤخرًا. يُرجى المحاولة لاحقًا. |
| ☐ | Topic | الموضوع |
| ☐ | Topics | الموضوعات |
| ☐ | tribute | كلمة الوفاء |
| ☐ | Type | النوع |
| ☐ | Uploaded file | ملف مرفوع |
| ☐ | Version :version | الإصدار :version |
| ☐ | View | طريقة العرض |
| ☐ | View larger | عرض بحجم أكبر |
| ☐ | Watch on :host | شاهد على :host |
| ☐ | Watch the video | شاهد التسجيل |
| ☐ | We will send a link to confirm it. | سنرسل إليك رابطًا لتأكيده. |
| ☐ | Website | الموقع الإلكتروني |
| ☐ | Why you would like to join | سبب رغبتك في الانضمام |
| ☐ | Write | الكتابة |
| ☐ | Write your tribute. | اكتب كلمتك. |
| ☐ | Your name | اسمك |
| ☐ | Your tribute | كلمتك |
| ☐ | Your university, institute or organisation. | جامعتك أو معهدك أو مؤسستك. |
| ☐ | {1} :count album\|[2,*] :count albums | {1} ألبوم واحد\|{2} ألبومين\|[3,10] :count ألبومات\|[11,*] :count ألبومًا |
| ☐ | {1} :count item\|[2,*] :count items | {0} لا توجد عناصر\|{1} عنصر واحد\|{2} عنصران\|[3,10] :count عناصر\|[11,*] :count عنصرًا |
| ☐ | {1} :count photograph in :albums\|[2,*] :count photographs in :albums | {0} لا توجد صور في :albums\|{1} صورة واحدة في :albums\|{2} صورتان في :albums\|[3,10] :count صور في :albums\|[11,*] :count صورة في :albums |

## Scientific Community chat (154)

| ✓ | English | Arabic |
|---|---|---|
| ☐ | 1 member | عضو واحد |
| ☐ | 1 message | رسالة واحدة |
| ☐ | 1 new message | رسالة جديدة واحدة |
| ☐ | :count characters left | المتبقي: :count حرفًا |
| ☐ | :count characters over | تجاوزت الحد بـ :count حرفًا |
| ☐ | :count members | الأعضاء: :count |
| ☐ | :count messages | الرسائل: :count |
| ☐ | :count new messages | رسائل جديدة: :count |
| ☐ | :count online | المتصلون: :count |
| ☐ | :name has been suspended. | عُلّقت عضوية :name. |
| ☐ | :name is larger than :max MB. | حجم :name يتجاوز :max ميغابايت. |
| ☐ | :name is not a file type the chat accepts. | :name من نوع ملفات لا تقبله المحادثة. |
| ☐ | a link | رابط |
| ☐ | a list | قائمة |
| ☐ | A message cannot be empty. | لا يمكن أن تكون الرسالة فارغة. |
| ☐ | A message may not exceed :max characters. | لا يجوز أن تتجاوز الرسالة :max حرفًا. |
| ☐ | About this room | عن هذه القاعة |
| ☐ | Account menu | قائمة الحساب |
| ☐ | Actions for :name | إجراءات خاصة بـ :name |
| ☐ | Add a description (optional) | أضف وصفًا (اختياري) |
| ☐ | Administrator | مدير |
| ☐ | All members | جميع الأعضاء |
| ☐ | Attach at most :max files to one message. | أرفق :max ملفات على الأكثر بالرسالة الواحدة. |
| ☐ | Attach files | إرفاق ملفات |
| ☐ | Attachments | المرفقات |
| ☐ | Back to the community page | العودة إلى صفحة المجتمع |
| ☐ | bold | خط عريض |
| ☐ | Broadcasting (Reverb) | البث المباشر (Reverb) |
| ☐ | Cancel | إلغاء |
| ☐ | Cancel reply | إلغاء الرد |
| ☐ | Choose a file to attach. | اختر ملفًا لإرفاقه. |
| ☐ | Close the panel | إغلاق اللوحة |
| ☐ | Conversation | المحادثة |
| ☐ | Copy link | نسخ الرابط |
| ☐ | Copy this link | انسخ هذا الرابط |
| ☐ | Correspondence | المراسلات |
| ☐ | Could not upload. Remove it and try again. | تعذّر الرفع. أزل الملف ثم حاول مرة أخرى. |
| ☐ | Delete | حذف |
| ☐ | Delete message | حذف الرسالة |
| ☐ | Delete this message permanently? | هل تريد حذف هذه الرسالة نهائيًا؟ |
| ☐ | deleted | حُذفت |
| ☐ | Describe the image (optional) | صِف الصورة (اختياري) |
| ☐ | Do not upload material you do not have the right to share. | لا ترفع موادّ لا يحق لك مشاركتها. |
| ☐ | Document | مستند |
| ☐ | Drop files to attach them | أفلت الملفات هنا لإرفاقها |
| ☐ | Earlier letters | رسائل سابقة |
| ☐ | Edit | تعديل |
| ☐ | Edit message | تعديل الرسالة |
| ☐ | edited | معدّلة |
| ☐ | Enter to send · Shift+Enter for a new line | Enter للإرسال · Shift+Enter لسطر جديد |
| ☐ | Every correspondence begins with a first letter. | كل مراسلة تبدأ برسالة أولى. |
| ☐ | Everyone in the room will see “Message deleted” in its place, and its attached files are removed. | سيرى جميع من في القاعة «حُذفت الرسالة» في مكانها، وستُزال الملفات المرفقة بها. |
| ☐ | File uploads are switched off for now. | رفع الملفات متوقف حاليًا. |
| ☐ | Find a member | البحث عن عضو |
| ☐ | Formatting | التنسيق |
| ☐ | Formatting help | مساعدة التنسيق |
| ☐ | Former member | عضو سابق |
| ☐ | Image | صورة |
| ☐ | Image shared by :name | صورة شاركها :name |
| ☐ | Images (JPEG, PNG, WebP, HEIC) up to :image MB; PDF, Word, Excel, PowerPoint, CSV and ZIP up to :document MB; at most :max files. | الصور (JPEG وPNG وWebP وHEIC) حتى :image ميغابايت؛ وملفات PDF وWord وExcel وPowerPoint وCSV وZIP حتى :document ميغابايت؛ وبحد أقصى :max ملفات. |
| ☐ | Images in the conversation | الصور في المحادثة |
| ☐ | inline code | شيفرة ضمن السطر |
| ☐ | italic | خط مائل |
| ☐ | Jump to the latest | الانتقال إلى أحدث الرسائل |
| ☐ | Keep each image description to :max characters or fewer. | اجعل وصف كل صورة في حدود :max حرفًا أو أقل. |
| ☐ | Link copied | نُسخ الرابط |
| ☐ | Linked message | الرسالة المرتبطة |
| ☐ | Loading… | جارٍ التحميل… |
| ☐ | maths (KaTeX) | معادلات رياضية (KaTeX) |
| ☐ | Member | عضو |
| ☐ | Members | الأعضاء |
| ☐ | Message actions | إجراءات الرسالة |
| ☐ | Message removed | حُذفت الرسالة |
| ☐ | Messages | الرسائل |
| ☐ | Messages can be at most :max characters. | لا تتجاوز الرسالة :max حرفًا. |
| ☐ | Name or affiliation… | الاسم أو جهة الانتساب… |
| ☐ | New | جديد |
| ☐ | No member answers to that. | لا يوجد عضو يطابق ما كتبت. |
| ☐ | No one else has been here in the last five minutes. | لم يحضر أحد غيرك خلال الدقائق الخمس الأخيرة. |
| ☐ | Not sent | لم تُرسَل |
| ☐ | Nothing has been pinned yet. | لم يُثبَّت شيء بعد. |
| ☐ | Nothing in the conversation answers to that. | لا يوجد في المحادثة ما يطابق ما كتبت. |
| ☐ | One conversation for the approved members of the Scientific Community. | محادثة واحدة للأعضاء المعتمدين في المجتمع العلمي. |
| ☐ | One of the files could not be found. Remove it and attach it again. | تعذّر العثور على أحد الملفات. أزله ثم أرفقه مجددًا. |
| ☐ | One of the files is no longer available. Remove it and attach it again. | أحد الملفات لم يعد متاحًا. أزله ثم أرفقه مجددًا. |
| ☐ | online | متصل |
| ☐ | Online now | المتصلون الآن |
| ☐ | Only moderators may do this. | هذا الإجراء مقصور على المشرفين. |
| ☐ | Only the author may edit a message. | لا يعدّل الرسالة إلا كاتبها. |
| ☐ | Open image | فتح الصورة |
| ☐ | Original message removed | حُذفت الرسالة الأصلية |
| ☐ | Pin | تثبيت |
| ☐ | Pinned | المثبّتة |
| ☐ | pinned | مثبّتة |
| ☐ | Please say briefly why you are reporting this message. | يُرجى ذكر سبب إبلاغك عن هذه الرسالة باختصار. |
| ☐ | Polling | الاستطلاع الدوري |
| ☐ | Reason | السبب |
| ☐ | Reconnecting… | جارٍ إعادة الاتصال… |
| ☐ | Reload it | أعد تحميلها |
| ☐ | Remove :name | إزالة :name |
| ☐ | Reply | رد |
| ☐ | Replying to | ردًّا على |
| ☐ | Report this message | الإبلاغ عن هذه الرسالة |
| ☐ | Retry | إعادة المحاولة |
| ☐ | Save | حفظ |
| ☐ | Say briefly why you are reporting this message. | اذكر باختصار سبب إبلاغك عن هذه الرسالة. |
| ☐ | Search messages… | ابحث في الرسائل… |
| ☐ | Search the conversation | البحث في المحادثة |
| ☐ | Send | إرسال |
| ☐ | Send report | إرسال البلاغ |
| ☐ | Sending | جارٍ الإرسال |
| ☐ | sent | أُرسلت |
| ☐ | Shorten the message to :max characters or fewer. | اختصر الرسالة إلى :max حرفًا أو أقل. |
| ☐ | Shorten the reason to :max characters or fewer. | اختصر السبب إلى :max حرفًا أو أقل. |
| ☐ | Sign in again | سجّل الدخول مجددًا |
| ☐ | Sign out | تسجيل الخروج |
| ☐ | Suspend | تعليق العضوية |
| ☐ | Suspend :name from the community? | هل تريد تعليق عضوية :name في المجتمع؟ |
| ☐ | Tell the administrators briefly what is wrong. They will see the message, your name and your reason. | أخبر المديرين باختصار بما يستدعي الإبلاغ. سيطّلعون على الرسالة واسمك والسبب الذي ذكرته. |
| ☐ | Thank you. An administrator will look at this message. | شكرًا لك. سيطّلع أحد المديرين على هذه الرسالة. |
| ☐ | That member could not be found. | تعذّر العثور على هذا العضو. |
| ☐ | The change could not be saved. | تعذّر حفظ التعديل. |
| ☐ | The change could not be saved. Check your connection and try again. | تعذّر حفظ التعديل. تحقق من اتصالك ثم حاول مرة أخرى. |
| ☐ | The community chat is only open to approved members. | محادثة المجتمع متاحة للأعضاء المعتمدين فقط. |
| ☐ | The file could not be uploaded. Try again. | تعذّر رفع الملف. حاول مرة أخرى. |
| ☐ | The message identifier is invalid; please reload the page. | معرّف الرسالة غير صالح؛ يُرجى إعادة تحميل الصفحة. |
| ☐ | The message is missing its identifier; please reload the page. | الرسالة تفتقد معرّفها؛ يُرجى إعادة تحميل الصفحة. |
| ☐ | The message you are replying to could not be found. | تعذّر العثور على الرسالة التي ترد عليها. |
| ☐ | The message you are replying to is not in this channel. | الرسالة التي ترد عليها ليست في هذه القناة. |
| ☐ | The report could not be sent. | تعذّر إرسال البلاغ. |
| ☐ | The report could not be sent. Check your connection and try again. | تعذّر إرسال البلاغ. تحقق من اتصالك ثم حاول مرة أخرى. |
| ☐ | This cannot be undone. | لا يمكن التراجع عن هذا الإجراء. |
| ☐ | This message has been removed. | حُذفت هذه الرسالة. |
| ☐ | This message was changed elsewhere, reload it. | عُدّلت هذه الرسالة في مكان آخر، فأعد تحميلها. |
| ☐ | Today | اليوم |
| ☐ | Type a word or two to search. | اكتب كلمة أو كلمتين للبحث. |
| ☐ | Unpin | إلغاء التثبيت |
| ☐ | unpinned | أُلغي تثبيتها |
| ☐ | Uploading | جارٍ الرفع |
| ☐ | View profile | عرض الملف الشخصي |
| ☐ | Wait for the files to finish uploading. | انتظر حتى يكتمل رفع الملفات. |
| ☐ | Write a message or attach a file first. | اكتب رسالة أو أرفق ملفًا أولًا. |
| ☐ | Write to the community | اكتب إلى المجتمع |
| ☐ | Write to the community… | اكتب إلى المجتمع… |
| ☐ | Yes, suspend | نعم، علّق العضوية |
| ☐ | Yesterday | أمس |
| ☐ | You | أنت |
| ☐ | You are offline. Messages you write will be sent when the connection returns. | أنت غير متصل بالإنترنت. ستُرسَل الرسائل التي تكتبها عند عودة الاتصال. |
| ☐ | You are sending messages too quickly. Please wait :seconds seconds. | أنت ترسل الرسائل بسرعة كبيرة. يُرجى الانتظار :seconds ثانية. |
| ☐ | You cannot report your own message. | لا يمكنك الإبلاغ عن رسالتك. |
| ☐ | You cannot suspend yourself. | لا يمكنك تعليق عضويتك. |
| ☐ | You may not delete this message. | لا يحق لك حذف هذه الرسالة. |
| ☐ | You may not do that. | لا يحق لك القيام بذلك. |
| ☐ | Your session has expired. | انتهت صلاحية جلستك. |

## Sign-in screens, account, form controls and emails (209)

| ✓ | English | Arabic |
|---|---|---|
| ☐ | :count / :max characters | الأحرف: :count من :max |
| ☐ | :count options | عدد الخيارات: :count |
| ☐ | :count word | عدد الكلمات: :count |
| ☐ | :count words | عدد الكلمات: :count |
| ☐ | :name (opens in a new tab) | :name (يُفتح في علامة تبويب جديدة) |
| ☐ | :name has applied to join the Scientific Community on :site. | تقدّم :name بطلب للانضمام إلى المجتمع العلمي على :site. |
| ☐ | :tag is back in the field for editing. | أُعيد «:tag» إلى الحقل لتحريره. |
| ☐ | :tag removed. | أُزيل: :tag. |
| ☐ | :tags added. | أُضيف: :tags. |
| ☐ | A 1px quiet rule on the field surface. | خط هادئ بسمك 1px على سطح الحقل. |
| ☐ | A community application is waiting for review | طلب انضمام إلى المجتمع العلمي بانتظار المراجعة |
| ☐ | A full address starting with https://, or mailto: for an email. | عنوان كامل يبدأ بـ https://‎، أو mailto:‎ لعنوان بريد إلكتروني. |
| ☐ | A new tribute has been submitted on :site and is waiting for review. | أُرسلت كلمة وفاء جديدة على :site وهي بانتظار المراجعة. |
| ☐ | A new verification link has been sent to your email address. | أُرسل رابط تأكيد جديد إلى بريدك الإلكتروني. |
| ☐ | A tribute is waiting for review | كلمة وفاء بانتظار المراجعة |
| ☐ | About your community application | بشأن طلب انضمامك إلى المجتمع العلمي |
| ☐ | Add a link | إضافة رابط |
| ☐ | All rights reserved. | جميع الحقوق محفوظة. |
| ☐ | Alt text | النص البديل |
| ☐ | Apply link | تطبيق الرابط |
| ☐ | Authentication code | رمز التحقق |
| ☐ | Back to sign in | العودة إلى تسجيل الدخول |
| ☐ | Back to the website | العودة إلى الموقع |
| ☐ | Before continuing, please confirm your email address using the link we sent you. Did not receive it? We can send another. | قبل المتابعة، يُرجى تأكيد بريدك الإلكتروني عبر الرابط الذي أرسلناه إليك. ألم يصلك؟ يمكننا إرسال رابط آخر. |
| ☐ | Bold | غامق |
| ☐ | Bronze rule (brass on dark) and a message with a warning icon that names the fix. | خط برونزي (نحاسي على الخلفية الداكنة) ورسالة مع أيقونة تحذير تبيّن طريقة التصحيح. |
| ☐ | Bulleted list | قائمة نقطية |
| ☐ | Cancel setup | إلغاء الإعداد |
| ☐ | Caps Lock is on. | مفتاح الأحرف الكبيرة (Caps Lock) مفعّل. |
| ☐ | Change password | تغيير كلمة المرور |
| ☐ | Changing your email address will ask you to verify it again. | عند تغيير بريدك الإلكتروني سيُطلب منك تأكيده من جديد. |
| ☐ | Checking… | جارٍ التحقق… |
| ☐ | Choose :label. | اختر :label. |
| ☐ | choose a file | اختر ملفًا |
| ☐ | Choose a new password | اختر كلمة مرور جديدة |
| ☐ | Choose a password with the link below to activate your account. For security the link can only be used once and expires after :minutes minutes; if it has expired, ask an administrator to resend the invitation. | اختر كلمة مرور عبر الرابط أدناه لتفعيل حسابك. حرصًا على الأمان، لا يمكن استخدام الرابط إلا مرة واحدة، وتنتهي صلاحيته بعد :minutes دقيقة؛ فإذا انتهت صلاحيته فاطلب من أحد المديرين إعادة إرسال الدعوة. |
| ☐ | Choose a password you use nowhere else. | اختر كلمة مرور لا تستخدمها في أي مكان آخر. |
| ☐ | choose images | اختر الصور |
| ☐ | Choose one | اختر واحدًا |
| ☐ | Code from your app | الرمز من تطبيقك |
| ☐ | Confirm | تأكيد |
| ☐ | Confirm and turn on | تأكيد وتفعيل |
| ☐ | Confirm new password | تأكيد كلمة المرور الجديدة |
| ☐ | Confirm your password | تأكيد كلمة المرور |
| ☐ | Confirming… | جارٍ التأكيد… |
| ☐ | Continue | متابعة |
| ☐ | Correct the fields below, then save again. | صحّح الحقول أدناه، ثم احفظ مرة أخرى. |
| ☐ | Current password | كلمة المرور الحالية |
| ☐ | Default | الافتراضية |
| ☐ | Describe what the image shows for people who cannot see it. | صِف ما تُظهره الصورة لمن لا يستطيع رؤيتها. |
| ☐ | Digit :number of :length | الرقم :number من :length |
| ☐ | Disabled | معطّل |
| ☐ | Dotted rule, muted ink, not-allowed cursor; skipped by Tab. | خط منقّط وحبر باهت ومؤشر المنع؛ ويتخطاه مفتاح Tab. |
| ☐ | Drag a file here, or | اسحب ملفًا إلى هنا، أو |
| ☐ | Drag images here, or | اسحب الصور إلى هنا، أو |
| ☐ | Each code signs you in once if you lose your authenticator. Keep them somewhere safe. | يتيح لك كل رمز تسجيل الدخول مرة واحدة إذا فقدت تطبيق المصادقة. احفظها في مكان آمن. |
| ☐ | Email me a reset link | أرسل إليّ رابط إعادة التعيين |
| ☐ | Enable two-factor authentication | تفعيل التحقق بخطوتين |
| ☐ | Enter :label. | أدخل :label. |
| ☐ | Enter a date as day, month and year. | أدخل التاريخ باليوم والشهر والسنة. |
| ☐ | Enter a full web address starting with https:// or mailto:. | أدخل عنوان ويب كاملًا يبدأ بـ https://‎ أو mailto:‎. |
| ☐ | Enter a full web address starting with https://. | أدخل عنوان ويب كاملًا يبدأ بـ https://‎. |
| ☐ | Enter a number between :min and :max. | أدخل رقمًا بين :min و:max. |
| ☐ | Enter a whole number. | أدخل عددًا صحيحًا. |
| ☐ | Enter a year between :min and :max. | أدخل سنة بين :min و:max. |
| ☐ | Enter an email address in the form name@example.com. | أدخل عنوان بريد إلكتروني بالصيغة name@example.com. |
| ☐ | Enter one of the recovery codes you stored when you set up two-factor authentication. | أدخل أحد رموز الاسترداد التي حفظتها عند إعداد التحقق بخطوتين. |
| ☐ | Enter the :length-digit code from your app. | أدخل الرمز المكوّن من :length أرقام من تطبيقك. |
| ☐ | Enter the six-digit code from your authenticator app. | أدخل الرمز المكوّن من ستة أرقام من تطبيق المصادقة. |
| ☐ | Enter your email address and we will send you a link to choose a new password. | أدخل بريدك الإلكتروني وسنرسل إليك رابطًا لاختيار كلمة مرور جديدة. |
| ☐ | Fair | مقبولة |
| ☐ | Filled | معبّأ |
| ☐ | Finish setup | أكمل الإعداد |
| ☐ | Focus | التركيز |
| ☐ | Forgotten your password? | هل نسيت كلمة المرور؟ |
| ☐ | From: :name | من: :name |
| ☐ | From: :name (:details) | من: :name (:details) |
| ☐ | Generate new codes | إنشاء رموز جديدة |
| ☐ | Good | جيدة |
| ☐ | Heading | عنوان |
| ☐ | Hello :name, | مرحبًا :name، |
| ☐ | Hello! | مرحبًا! |
| ☐ | Hello, | مرحبًا، |
| ☐ | Hover | التمرير |
| ☐ | If you did not create an account, no further action is required. | إذا لم تنشئ حسابًا، فلا يلزمك أي إجراء. |
| ☐ | If you did not request a password reset, no further action is required. | إذا لم تطلب إعادة تعيين كلمة المرور، فلا يلزمك أي إجراء. |
| ☐ | If you were not expecting this invitation you can ignore this email; nothing happens until the link is used. | إذا لم تكن تتوقع هذه الدعوة فيمكنك تجاهل هذه الرسالة؛ فلن يحدث شيء ما لم يُستخدم الرابط. |
| ☐ | If you're having trouble clicking the ":actionText" button, copy and paste the URL below<br>into your web browser: | إذا واجهت صعوبة في النقر على زر «:actionText»، فانسخ الرابط أدناه والصقه<br>في متصفح الويب: |
| ☐ | Invalid | غير صالح |
| ☐ | It will not appear on the site until it has been approved. | لن تظهر على الموقع قبل الموافقة عليها. |
| ☐ | Italic | مائل |
| ☐ | Keep me signed in | إبقائي مسجّلًا الدخول |
| ☐ | Language | اللغة |
| ☐ | Length matters more than symbols. A passphrase of twelve characters or more is strong. | الطول أهم من الرموز. عبارة المرور المكوّنة من اثني عشر حرفًا أو أكثر قوية. |
| ☐ | Link | رابط |
| ☐ | Loading | جارٍ التحميل |
| ☐ | Members of the Scientific Community and the editorial staff sign in here. | يسجّل أعضاء المجتمع العلمي وفريق التحرير دخولهم من هنا. |
| ☐ | Mouse, touch or keyboard alike: the field's own line turns from 1px muted to 2px strong ink over 200ms, with a faint tint behind the field. No outline, ring or box. | بالفأرة أو اللمس أو لوحة المفاتيح على السواء: يتحول خط الحقل من 1px باهت إلى 2px بحبر قوي خلال 200ms، مع ظل لوني خفيف خلف الحقل. بلا إطار أو حلقة أو مربع. |
| ☐ | Muted ink and a hairline that draws slowly along the foot of the field. | حبر باهت وخط رفيع يُرسم ببطء على طول أسفل الحقل. |
| ☐ | Name | الاسم |
| ☐ | Name and email | الاسم والبريد الإلكتروني |
| ☐ | New password | كلمة المرور الجديدة |
| ☐ | New recovery codes have been generated. The old ones no longer work. | أُنشئت رموز استرداد جديدة، ولم تعد الرموز القديمة صالحة. |
| ☐ | No affiliation or field was given. | لم تُذكر جهة انتماء أو تخصص. |
| ☐ | No matches. Try fewer letters. | لا توجد نتائج مطابقة. جرّب أحرفًا أقل. |
| ☐ | Note from the reviewer: :note | ملاحظة من المراجِع: :note |
| ☐ | Numbered list | قائمة مرقّمة |
| ☐ | Off | متوقف |
| ☐ | On | مفعّل |
| ☐ | On the sunken surface behind a quiet dotted rule, so it does not invite typing; still focusable and copyable. | على السطح الغائر خلف خط منقّط هادئ، فلا يدعو إلى الكتابة؛ ويظل قابلًا للتركيز والنسخ. |
| ☐ | On this occasion your application has not been accepted. | نأسف لعدم قبول طلبك هذه المرة. |
| ☐ | Only once an invalid field is corrected: a tick and the success tone. | فقط بعد تصحيح حقل غير صالح: علامة صح ولون النجاح. |
| ☐ | Open the community | الدخول إلى المجتمع العلمي |
| ☐ | Open the dashboard | فتح لوحة التحكم |
| ☐ | Optional | اختياري |
| ☐ | Please click the button below to verify your email address. | يُرجى النقر على الزر أدناه لتأكيد بريدك الإلكتروني. |
| ☐ | Press Enter or a comma after each one. | اضغط Enter أو فاصلة بعد كل إدخال. |
| ☐ | QR code for your authenticator app | رمز QR لتطبيق المصادقة |
| ☐ | Quote | اقتباس |
| ☐ | Read-only | للقراءة فقط |
| ☐ | Recommended for administrator accounts | يُوصى به لحسابات المديرين |
| ☐ | Recovery code | رمز الاسترداد |
| ☐ | Recovery codes | رموز الاسترداد |
| ☐ | Redo | إعادة |
| ☐ | Regards, | مع خالص التحية، |
| ☐ | Remove | إزالة |
| ☐ | Remove :tag | إزالة :tag |
| ☐ | Remove link | إزالة الرابط |
| ☐ | Replace | استبدال |
| ☐ | Required | مطلوب |
| ☐ | Resend verification email | إعادة إرسال رسالة التأكيد |
| ☐ | Reset Password | إعادة تعيين كلمة المرور |
| ☐ | Reset your password | إعادة تعيين كلمة المرور |
| ☐ | Review applications | مراجعة الطلبات |
| ☐ | Save details | حفظ البيانات |
| ☐ | Save password | حفظ كلمة المرور |
| ☐ | Saving… | جارٍ الحفظ… |
| ☐ | Scan the code with your authenticator app, or enter this key by hand: | امسح الرمز بتطبيق المصادقة، أو أدخل هذا المفتاح يدويًا: |
| ☐ | Scan the code with your authenticator app, then enter a code to finish. | امسح الرمز بتطبيق المصادقة، ثم أدخل رمزًا لإكمال الإعداد. |
| ☐ | Sending your application… | جارٍ إرسال طلبك… |
| ☐ | Sending… | جارٍ الإرسال… |
| ☐ | Set your password | تعيين كلمة المرور |
| ☐ | Settings | الإعدادات |
| ☐ | Shorten this to :max characters or fewer. | اختصر هذا إلى :max حرفًا أو أقل. |
| ☐ | Show password | إظهار كلمة المرور |
| ☐ | Signed in as :email. | تم تسجيل الدخول باسم :email. |
| ☐ | Signing in… | جارٍ تسجيل الدخول… |
| ☐ | Start writing… | ابدأ الكتابة… |
| ☐ | Strong | قوية |
| ☐ | Subheading | عنوان فرعي |
| ☐ | Switch between the light and the dark theme. The site starts from your device setting; once you switch, the choice is kept on this account and on this device. | بدّل بين المظهر الفاتح والمظهر الداكن. يبدأ الموقع بإعداد جهازك، وبعد التبديل يُحفظ اختيارك في هذا الحساب وعلى هذا الجهاز. |
| ☐ | Tell us a little about yourself. An administrator reviews every application. | حدّثنا قليلًا عن نفسك. يراجع أحد المديرين كل طلب. |
| ☐ | Thank you for applying to join the Scientific Community on :site. | نشكرك على تقدّمك بطلب الانضمام إلى المجتمع العلمي على :site. |
| ☐ | The :attribute must be no longer than :max characters (it has :count). | يجب ألا يزيد :attribute على :max حرفًا (عدد أحرفه الآن :count). |
| ☐ | The language of the dashboard, the chat, the sign-in screens and the emails we send you. | لغة لوحة التحكم والمحادثة وشاشات تسجيل الدخول والرسائل التي نرسلها إليك بالبريد الإلكتروني. |
| ☐ | The provided password does not match your current password. | كلمة المرور المدخلة لا تطابق كلمة مرورك الحالية. |
| ☐ | The provided password was incorrect. | كلمة المرور المدخلة غير صحيحة. |
| ☐ | The provided two factor authentication code was invalid. | رمز التحقق المدخل غير صالح. |
| ☐ | The provided two factor recovery code was invalid. | رمز الاسترداد المدخل غير صالح. |
| ☐ | The reading room | قاعة القراءة |
| ☐ | The rule darkens to the strong rule. | يغمق الخط إلى الخط القوي. |
| ☐ | The rule stays strong so an answered field reads as settled. | يبقى الخط قويًا ليبدو الحقل المُجاب عنه مستقرًا. |
| ☐ | The upload did not finish. Check your connection and choose the file again. | لم يكتمل الرفع. تحقّق من اتصالك واختر الملف مرة أخرى. |
| ☐ | There is a problem with this form | في هذا النموذج مشكلة |
| ☐ | They cannot use the chat until an administrator approves them. | لا يمكنه استخدام المحادثة حتى يوافق عليه أحد المديرين. |
| ☐ | This account has been deactivated. Contact an administrator. | تم تعطيل هذا الحساب. يُرجى التواصل مع أحد المديرين. |
| ☐ | This is a secure area. Please confirm your password before continuing. | هذه منطقة محمية. يُرجى تأكيد كلمة المرور قبل المتابعة. |
| ☐ | This password reset link will expire in :count minutes. | تنتهي صلاحية رابط إعادة التعيين هذا بعد :count دقيقة. |
| ☐ | Tick this box to continue. | ضع علامة في هذا المربع للمتابعة. |
| ☐ | Too short | قصيرة جدًا |
| ☐ | Turn off two-factor authentication | إيقاف التحقق بخطوتين |
| ☐ | Two-factor authentication | التحقق بخطوتين |
| ☐ | Two-factor authentication has been turned off. | تم إيقاف التحقق بخطوتين. |
| ☐ | Two-factor authentication is on. Store your recovery codes somewhere safe. | التحقق بخطوتين مفعّل. احفظ رموز الاسترداد في مكان آمن. |
| ☐ | Undo | تراجع |
| ☐ | Update your name and email, change your password, choose the appearance and language, and manage two-factor authentication. | حدّث اسمك وبريدك الإلكتروني، وغيّر كلمة المرور، واختر المظهر واللغة، وأدِر التحقق بخطوتين. |
| ☐ | Uploaded. Save to keep it. | اكتمل الرفع. احفظ للاحتفاظ به. |
| ☐ | Uploading… :percent% | جارٍ الرفع… :percent% |
| ☐ | Use a long passphrase you do not use anywhere else. | استخدم عبارة مرور طويلة لا تستخدمها في أي مكان آخر. |
| ☐ | Use a recovery code instead | استخدم رمز استرداد بدلًا من ذلك |
| ☐ | Use an authenticator code instead | استخدم رمزًا من تطبيق المصادقة بدلًا من ذلك |
| ☐ | Use at least :min characters. | استخدم :min أحرف على الأقل. |
| ☐ | Valid (after a correction) | صالح (بعد التصحيح) |
| ☐ | Verify Email Address | تأكيد البريد الإلكتروني |
| ☐ | Verify your email | تأكيد بريدك الإلكتروني |
| ☐ | Verify your email address | تأكيد بريدك الإلكتروني |
| ☐ | Visit the community page | زيارة صفحة المجتمع العلمي |
| ☐ | Web address | عنوان الويب |
| ☐ | When enabled, signing in also asks for a six-digit code from an authenticator app such as Google Authenticator, 1Password or Authy. | عند تفعيله، يطلب تسجيل الدخول أيضًا رمزًا من ستة أرقام من تطبيق مصادقة مثل Google Authenticator أو 1Password أو Authy. |
| ☐ | Whoops! | عذرًا! |
| ☐ | You are receiving this email because we received a password reset request for your account. | تصلك هذه الرسالة لأننا تلقّينا طلبًا لإعادة تعيين كلمة المرور الخاصة بحسابك. |
| ☐ | You are welcome to apply again. | يسعدنا أن تتقدّم بطلب جديد. |
| ☐ | You can now sign in and take part in the members' chat. | يمكنك الآن تسجيل الدخول والمشاركة في محادثة الأعضاء. |
| ☐ | You have been invited to :site | دعوة للانضمام إلى :site |
| ☐ | You have been invited to join the dashboard of :site as :role. | دُعيت للانضمام إلى لوحة تحكم :site بصفة :role. |
| ☐ | You have been invited to join the dashboard of :site as a member of staff. | دُعيت للانضمام إلى لوحة تحكم :site بصفتك أحد أعضاء فريق العمل. |
| ☐ | You have been invited to join the dashboard of :site as an administrator. | دُعيت للانضمام إلى لوحة تحكم :site بصفة مدير. |
| ☐ | You have been invited to join the dashboard of :site as an editor. | دُعيت للانضمام إلى لوحة تحكم :site بصفة محرر. |
| ☐ | You have signed out. | تم تسجيل خروجك. |
| ☐ | Your application to join the Scientific Community on :site has been approved. | تمت الموافقة على طلب انضمامك إلى المجتمع العلمي على :site. |
| ☐ | Your application to join the Scientific Community on :site has been received and will be reviewed by an administrator. | تم استلام طلب انضمامك إلى المجتمع العلمي على :site، وسيراجعه أحد المديرين. |
| ☐ | Your community application has been approved | تمت الموافقة على طلب انضمامك إلى المجتمع العلمي |
| ☐ | Your community application has been received | تم استلام طلب انضمامك إلى المجتمع العلمي |
| ☐ | Your community membership has been suspended | عُلّقت عضويتك في المجتمع العلمي |
| ☐ | Your community membership has ended | انتهت عضويتك في المجتمع العلمي |
| ☐ | Your details have been saved. | حُفظت بياناتك. |
| ☐ | Your membership of the Scientific Community on :site has been suspended by an administrator, and the chat is closed to you for now. | علّق أحد المديرين عضويتك في المجتمع العلمي على :site، والمحادثة مغلقة أمامك في الوقت الحالي. |
| ☐ | Your membership of the Scientific Community on :site has ended. | انتهت عضويتك في المجتمع العلمي على :site. |
| ☐ | Your password has been changed. | تم تغيير كلمة المرور. |

## Admin: content editing, lists, images (455)

| ✓ | English | Arabic |
|---|---|---|
| ☐ | (has errors) | (فيه أخطاء) |
| ☐ | (in the trash) | (في سلة المحذوفات) |
| ☐ | (in trash) | (في سلة المحذوفات) |
| ☐ | (opens the public page in a new tab) | (تفتح الصفحة العامة في علامة تبويب جديدة) |
| ☐ | :count moved back to draft. | أُعيد :count إلى المسودات. |
| ☐ | :count moved to the trash. | نُقل :count إلى سلة المحذوفات. |
| ☐ | :count published. Not published yet, because images still need details: :titles. | نُشر :count. ولم يُنشر بعدُ ما تحتاج صوره إلى تفاصيل: :titles. |
| ☐ | :count restored. | استُعيد :count. |
| ☐ | :field (Arabic) | :field (بالعربية) |
| ☐ | :file is used by :owner as the portrait. | :file مستخدم في :owner صورةً شخصية. |
| ☐ | :file is used by :owner as the poster image. | :file مستخدم في :owner صورةَ غلاف. |
| ☐ | :file is used by :owner. | :file مستخدم في :owner. |
| ☐ | :formats. Every image needs alt text, a caption, a place and a year before it can be published. | :formats. تحتاج كل صورة إلى نص بديل وتعليق ومكان وسنة قبل نشرها. |
| ☐ | :items in the trash | :items في سلة المحذوفات |
| ☐ | :size MB | :size ميغابايت |
| ☐ | :type created. | تم إنشاء :type. |
| ☐ | :type deleted permanently. | تم حذف :type نهائيًا. |
| ☐ | :type moved back to draft. | أُعيد :type إلى المسودات. |
| ☐ | :type moved back to draft. Visitors no longer see it. | أُعيد :type إلى المسودات، ولم يعد ظاهرًا للزوار. |
| ☐ | :type moved to the trash. | نُقل :type إلى سلة المحذوفات. |
| ☐ | :type published. | تم نشر :type. |
| ☐ | :type published. It is on the public site now. | تم نشر :type، وهو الآن على الموقع العام. |
| ☐ | :type restored. | تمت استعادة :type. |
| ☐ | :type saved. | تم حفظ :type. |
| ☐ | a caption | تعليقًا |
| ☐ | A finer grouping inside the course. | تصنيف أدق داخل المقرر. |
| ☐ | A group of photographs on the Pictures page. Every photo needs alt text and a caption with place and year before the album can be published. | مجموعة من الصور في صفحة الصور. تحتاج كل صورة إلى نص بديل وتعليق يذكر المكان والسنة قبل نشر الألبوم. |
| ☐ | A lecture, interview or ceremony. Link to YouTube or Vimeo, or upload the file. | محاضرة أو مقابلة أو حفل. أضف رابطًا إلى YouTube أو Vimeo، أو ارفع الملف. |
| ☐ | A page that confirms the award, if there is one online. | صفحة تؤكد الجائزة، إن وُجدت على الإنترنت. |
| ☐ | A page where the work can be read. | صفحة يمكن قراءة العمل فيها. |
| ☐ | A paper, book, chapter or report. Fill in what you know; the public page builds the citation from these fields. | بحث أو كتاب أو فصل أو تقرير. املأ ما تعرفه، وتُنشئ الصفحة العامة الاستشهاد من هذه الحقول. |
| ☐ | A PDF readers can open from the publications page. | ملف PDF يفتحه القرّاء من صفحة المنشورات. |
| ☐ | a place | مكانًا |
| ☐ | A sentence or two about the role, if it needs explaining. | جملة أو جملتان عن الدور، إن احتاج إلى توضيح. |
| ☐ | A sentence or two shown above the photos. | جملة أو جملتان تظهران فوق الصور. |
| ☐ | A sentence or two under the course heading. Only one material per course needs it; the first filled in is used. | جملة أو جملتان تحت عنوان المقرر. تكفي مادة واحدة لكل مقرر، ويُستخدم أول وصف مُدخل. |
| ☐ | A short label and its value, such as Born and 1940. They are listed beside the biography. | تسمية قصيرة وقيمتها، مثل: الميلاد و1940. تُعرض بجانب السيرة. |
| ☐ | A WebVTT captions file, so people who cannot hear can follow. | ملف ترجمة نصية بصيغة WebVTT ليتابع من لا يستطيعون السمع. |
| ☐ | a year | سنة |
| ☐ | Abstract | الملخص |
| ☐ | abstract | الملخص |
| ☐ | Access | الوصول |
| ☐ | access | الوصول |
| ☐ | Actions for :item | إجراءات «:item» |
| ☐ | Add a caption before publishing. | أضف تعليقًا قبل النشر. |
| ☐ | Add a key fact | أضف معلومة رئيسية |
| ☐ | Add images | أضف صورًا |
| ☐ | Add the first :type | أضف أول :type |
| ☐ | Add the place before publishing. | أضف المكان قبل النشر. |
| ☐ | Add the year before publishing. | أضف السنة قبل النشر. |
| ☐ | affiliation | الجهة |
| ☐ | Album photos | صور الألبومات |
| ☐ | Albums group photographs for the Pictures page. Each photo has a caption with place and year. | تجمع الألبومات الصور لصفحة الصور، ولكل صورة تعليق يذكر المكان والسنة. |
| ☐ | All images | كل الصور |
| ☐ | An address the family is happy to publish. | عنوان توافق الأسرة على نشره. |
| ☐ | An essay or note for the Commentary Corner. Visitors read it on its own page; there are no comments. | مقالة أو ملاحظة لركن المقالات. يقرؤها الزوار في صفحتها الخاصة، ولا توجد تعليقات. |
| ☐ | An honour, prize or fellowship. It appears on the Awards page, newest first. | تكريم أو جائزة أو زمالة. تظهر في صفحة الجوائز والتكريمات، الأحدث أولًا. |
| ☐ | An optional short quotation set under the title. | اقتباس قصير اختياري يوضع تحت العنوان. |
| ☐ | Anything | أي استخدام |
| ☐ | Arabic | العربية |
| ☐ | Arabic missing | العربية ناقصة |
| ☐ | As printed, without a full stop at the end. | كما طُبع، بلا نقطة في آخره. |
| ☐ | As the awarding body writes it, for example Order of Merit in Science. | كما تكتبه الجهة المانحة، مثل: وسام الاستحقاق في العلوم. |
| ☐ | As they should appear in the citation, separated by commas, for example Khidir, M. O., Hassan, A. | كما ينبغي أن تظهر في الاستشهاد، مفصولة بفواصل، مثل: Khidir, M. O., Hassan, A. |
| ☐ | Attribution | النسبة |
| ☐ | attribution | النسبة |
| ☐ | Authors | المؤلفون |
| ☐ | authors | المؤلفون |
| ☐ | Autosaved | حُفظ تلقائيًا |
| ☐ | Awarding organisation | الجهة المانحة |
| ☐ | Awards are the honours and prizes he received, with the organisation and the year. | الجوائز هي التكريمات والجوائز التي نالها، مع الجهة المانحة والسنة. |
| ☐ | Biography | السيرة |
| ☐ | Body | النص |
| ☐ | body | النص |
| ☐ | Byline | الكاتب |
| ☐ | byline | الكاتب |
| ☐ | Caption | التعليق |
| ☐ | Captions link | رابط الترجمة النصية |
| ☐ | captions link | رابط الترجمة النصية |
| ☐ | captions url | رابط الترجمة النصية |
| ☐ | Catalogue | الفهرسة |
| ☐ | Category | الفئة |
| ☐ | category | الفئة |
| ☐ | Choose a file to upload. | اختر ملفًا لرفعه. |
| ☐ | Choose an image file: JPEG, PNG or WebP. | اختر ملف صورة: JPEG أو PNG أو WebP. |
| ☐ | Choose an image to upload. | اختر صورة لرفعها. |
| ☐ | Choose either draft or published. | اختر: مسودة أو منشور. |
| ☐ | Choose one of the CV sections. | اختر أحد أقسام السيرة الذاتية. |
| ☐ | Choose one of the listed decades. | اختر أحد العقود المدرجة. |
| ☐ | Choose one of the material types. | اختر أحد أنواع المواد. |
| ☐ | Choose one of the publication types. | اختر أحد أنواع المنشورات. |
| ☐ | Choose the album first, then drop up to 20 photos at once. They upload one batch at a time; the line shows how far it has got. | اختر الألبوم أولًا، ثم أفلت ما يصل إلى 20 صورة دفعة واحدة. تُرفع دفعةً دفعة، ويبيّن الشريط مقدار التقدم. |
| ☐ | Choose the album the images belong to. | اختر الألبوم الذي تنتمي إليه الصور. |
| ☐ | Choose the portrait or archive treatment. | اختر معالجة الصورة الشخصية أو معالجة الأرشيف. |
| ☐ | Choose where the video is hosted. | اختر مكان استضافة الفيديو. |
| ☐ | Choose who may download this material. | اختر من يحق له تنزيل هذه المادة. |
| ☐ | citation | الاستشهاد |
| ☐ | Citation as written | الاستشهاد كما كُتب |
| ☐ | Citation details | تفاصيل الاستشهاد |
| ☐ | City or hall. | المدينة أو القاعة. |
| ☐ | Clear search and filters | امسح البحث والمرشحات |
| ☐ | Clear selection | امسح التحديد |
| ☐ | Clear the search, filters and sorting to change the display order. | امسح البحث والمرشحات والترتيب لتغيير ترتيب العرض. |
| ☐ | Commentaries are essays and notes for the Commentary Corner, each on its own page. | المقالات نصوص وملاحظات لركن المقالات، لكلٍّ منها صفحتها الخاصة. |
| ☐ | contact email public | البريد الإلكتروني العام للتواصل |
| ☐ | Contact note | ملاحظة التواصل |
| ☐ | contact note | ملاحظة التواصل |
| ☐ | Course | المقرر |
| ☐ | course | المقرر |
| ☐ | Course description | وصف المقرر |
| ☐ | course description | وصف المقرر |
| ☐ | Cover | الغلاف |
| ☐ | Credit | حقوق الصورة |
| ☐ | CV entries are the lines of his CV: degrees, posts, memberships. Each one appears on the CV page under its section. | بنود السيرة الذاتية هي أسطرها: الدرجات العلمية والمناصب والعضويات. يظهر كل بند في صفحة السيرة الذاتية تحت قسمه. |
| ☐ | Date | التاريخ |
| ☐ | date | التاريخ |
| ☐ | Dates | التواريخ |
| ☐ | Decade | العقد |
| ☐ | decade | العقد |
| ☐ | Delete image | احذف الصورة |
| ☐ | Delete permanently | احذف نهائيًا |
| ☐ | Delete this :type permanently? | حذف :type نهائيًا؟ |
| ☐ | Delete this image permanently? | حذف هذه الصورة نهائيًا؟ |
| ☐ | Deleted | حُذف |
| ☐ | Description | الوصف |
| ☐ | description | الوصف |
| ☐ | Destination link | رابط الوجهة |
| ☐ | destination link | رابط الوجهة |
| ☐ | destination url | رابط الوجهة |
| ☐ | Details of :image | تفاصيل «:image» |
| ☐ | DOI | DOI |
| ☐ | doi | DOI |
| ☐ | Draft | مسودة |
| ☐ | Drag handle | مقبض السحب |
| ☐ | Drag to reorder | اسحب لإعادة الترتيب |
| ☐ | Each field of work as a short phrase, in the order they should appear. Press Enter after each one. | كل مجال عمل بعبارة قصيرة، بالترتيب الذي ينبغي أن يظهر به. اضغط Enter بعد كل واحد. |
| ☐ | Each save keeps a copy. Load one to bring it back into the form; it is kept only when you save. | كل حفظ يحتفظ بنسخة. حمّل نسخة لإعادتها إلى النموذج، ولا تُعتمد إلا عند الحفظ. |
| ☐ | Earlier version loaded into the form. Save to keep it, or leave without saving to discard it. | حُمّلت نسخة سابقة في النموذج. احفظ للإبقاء عليها، أو غادر دون حفظ لتجاهلها. |
| ☐ | Edit :type | تحرير :type |
| ☐ | Edit album | تحرير الألبوم |
| ☐ | Edit award | تحرير الجائزة |
| ☐ | Edit commentary | تحرير المقالة |
| ☐ | Edit CV entry | تحرير بند في السيرة الذاتية |
| ☐ | Edit home quote | تحرير اقتباس الصفحة الرئيسية |
| ☐ | Edit item | تحرير العنصر |
| ☐ | Edit publication | تحرير المنشور |
| ☐ | Edit teaching material | تحرير المادة التعليمية |
| ☐ | Edit video | تحرير الفيديو |
| ☐ | End year | سنة الانتهاء |
| ☐ | end year | سنة الانتهاء |
| ☐ | Enter an end year no earlier than the start year. | أدخل سنة انتهاء لا تسبق سنة البداية. |
| ☐ | Enter the video link, or choose "Uploaded file" and upload it. | أدخل رابط الفيديو، أو اختر «ملف مرفوع» وارفعه. |
| ☐ | Epigraph | التصدير |
| ☐ | epigraph | التصدير |
| ☐ | epigraph attribution | مصدر التصدير |
| ☐ | Epigraph source | مصدر التصدير |
| ☐ | Event | المناسبة |
| ☐ | event | المناسبة |
| ☐ | Every photograph on the site. Each needs alt text (a sentence describing it for people who cannot see it), a caption, a place and a year before its page can be published. | كل صور الموقع. تحتاج كل صورة إلى نص بديل (جملة تصفها لمن لا يستطيعون رؤيتها) وتعليق ومكان وسنة قبل نشر صفحتها. |
| ☐ | Everything on the About page: who he was, his biography, his fields of work, his portrait and how visitors can get in touch. | كل ما في صفحة «عن الأستاذ الدكتور خضر»: من هو، وسيرته، ومجالات عمله، وصورته، وكيف يتواصل الزوار. |
| ☐ | expertise | مجالات الخبرة |
| ☐ | Expertise & key facts | الخبرة والمعلومات الرئيسية |
| ☐ | expertise entry | مجال الخبرة |
| ☐ | external link | الرابط الخارجي |
| ☐ | external url | الرابط الخارجي |
| ☐ | Fact :number label | تسمية المعلومة :number |
| ☐ | Fact :number value | قيمة المعلومة :number |
| ☐ | featured | مميّز |
| ☐ | Featured on the wall | مميّزة على الجدار |
| ☐ | file | الملف |
| ☐ | File & access | الملف والوصول |
| ☐ | File name, alt text, caption or place | اسم الملف أو النص البديل أو التعليق أو المكان |
| ☐ | Files can be at most :size. | لا يتجاوز حجم الملف :size. |
| ☐ | Filing | الأرشفة |
| ☐ | Fix these and save again: | صحّح ما يلي ثم احفظ مرة أخرى: |
| ☐ | For example 2nd edition. | مثل: الطبعة الثانية. |
| ☐ | For example Enquiries are answered by the family. | مثل: تتولى الأسرة الرد على الاستفسارات. |
| ☐ | For example Field trials in Gezira, 1972. | مثل: تجارب حقلية في الجزيرة، 1972. |
| ☐ | For example Inaugural lecture, University of Khartoum. | مثل: المحاضرة الافتتاحية، جامعة الخرطوم. |
| ☐ | For example interview, article or event. | مثل: مقابلة أو مقال أو فعالية. |
| ☐ | For example Journal of Agricultural Science. | مثل: مجلة العلوم الزراعية. |
| ☐ | For example Lecture 3: Soil water. | مثل: المحاضرة 3: ماء التربة. |
| ☐ | For example Professor of Agronomy. | مثل: أستاذ علم المحاصيل. |
| ☐ | For example the lecture, essay or tribute it comes from. | مثل: المحاضرة أو المقالة أو كلمة الوفاء التي أُخذ منها. |
| ☐ | Four digits. | أربعة أرقام. |
| ☐ | from :date | من :date |
| ☐ | From the date | من التاريخ |
| ☐ | From the year | من السنة |
| ☐ | Full bio | السيرة الكاملة |
| ☐ | full bio | السيرة الكاملة |
| ☐ | Full biography | السيرة الكاملة |
| ☐ | full biography | السيرة الكاملة |
| ☐ | Full text | النص الكامل |
| ☐ | Full text file | ملف النص الكامل |
| ☐ | Hangs one size larger in the album. | تُعلَّق بحجم أكبر درجةً في الألبوم. |
| ☐ | His full name, as it should appear on the site. | اسمه الكامل كما ينبغي أن يظهر على الموقع. |
| ☐ | History | السجل |
| ☐ | Home quotes are short lines in his words, shown between the chapters of the home page. | اقتباسات الصفحة الرئيسية أسطر قصيرة بكلماته، تظهر بين فصول الصفحة الرئيسية. |
| ☐ | How he is named at the top of the About page and in search results. | كيف يُسمّى في أعلى صفحة التعريف وفي نتائج البحث. |
| ☐ | Identity | الهوية |
| ☐ | If the piece was first published elsewhere. | إن نُشر النص أولًا في مكان آخر. |
| ☐ | image | الصورة |
| ☐ | Image deleted. | حُذفت الصورة. |
| ☐ | Image details | تفاصيل الصورة |
| ☐ | Image details saved. | حُفظت تفاصيل الصورة. |
| ☐ | Images | الصور |
| ☐ | Images can be added once the item has a title and has been saved once. | يمكن إضافة الصور بعد أن يكون للعنصر عنوان ويُحفظ مرة واحدة. |
| ☐ | Images can be at most :size. | لا يتجاوز حجم الصورة :size. |
| ☐ | In the archive | في الأرشيف |
| ☐ | Into album | إلى الألبوم |
| ☐ | is featured | مميّز |
| ☐ | item type | النوع |
| ☐ | Items with the same category are shown together. | تُعرض العناصر ذات الفئة الواحدة معًا. |
| ☐ | Journal, venue or publisher | المجلة أو جهة النشر أو الناشر |
| ☐ | JPEG, PNG or WebP up to :size | JPEG أو PNG أو WebP بحجم أقصاه :size |
| ☐ | Keep the quotation short: :max characters at most, so it reads as an interlude. | اجعل الاقتباس قصيرًا: :max حرفًا على الأكثر، ليُقرأ فاصلًا. |
| ☐ | Kept for the record. The public page writes APA, MLA, Chicago and BibTeX from the fields above. | يُحفظ للتوثيق. تكتب الصفحة العامة صيغ APA وMLA وChicago وBibTeX من الحقول أعلاه. |
| ☐ | key fact :number | المعلومة الرئيسية :number |
| ☐ | key fact label | تسمية المعلومة الرئيسية |
| ☐ | key fact value | قيمة المعلومة الرئيسية |
| ☐ | key facts | المعلومات الرئيسية |
| ☐ | key facts label | تسمية المعلومة الرئيسية |
| ☐ | key facts value | قيمة المعلومة الرئيسية |
| ☐ | Language of :group | لغة «:group» |
| ☐ | last updated | آخر تحديث |
| ☐ | last updated at | آخر تحديث |
| ☐ | latest | الأحدث |
| ☐ | Leave empty while it is ongoing. | اتركه فارغًا ما دام مستمرًا. |
| ☐ | Leave without saving | غادر دون حفظ |
| ☐ | Leave without saving? | المغادرة دون حفظ؟ |
| ☐ | Lecture notes, a syllabus or a problem set. The Teaching page groups materials by course. | مذكرات محاضرات أو خطة مقرر أو مجموعة تمارين. تجمع صفحة المواد التعليمية المواد حسب المقرر. |
| ☐ | link | الرابط |
| ☐ | Load this version | حمّل هذه النسخة |
| ☐ | location | المكان |
| ☐ | material date | التاريخ |
| ☐ | Materials with the same course are shown together. | تُعرض مواد المقرر الواحد معًا. |
| ☐ | media url | رابط الفيديو |
| ☐ | Members-only files are kept private and opened through a link that expires. | تُحفظ الملفات المخصصة للأعضاء بصورة خاصة وتُفتح عبر رابط ينتهي أجله. |
| ☐ | Missing alt text | بلا نص بديل |
| ☐ | Missing details | تفاصيل ناقصة |
| ☐ | Move down | انقل إلى الأسفل |
| ☐ | Move photo down | انقل الصورة إلى الأسفل |
| ☐ | Move photo up | انقل الصورة إلى الأعلى |
| ☐ | Move to trash | انقل إلى سلة المحذوفات |
| ☐ | Move up | انقل إلى الأعلى |
| ☐ | MP4 or WebM up to :size | MP4 أو WebM بحجم أقصاه :size |
| ☐ | name | الاسم |
| ☐ | Name of the award | اسم الجائزة |
| ☐ | Needs alt text | يحتاج إلى نص بديل |
| ☐ | Needs details | تحتاج إلى تفاصيل |
| ☐ | New album | ألبوم جديد |
| ☐ | New award | جائزة جديدة |
| ☐ | New CV entry | بند جديد في السيرة الذاتية |
| ☐ | New home quote | اقتباس جديد للصفحة الرئيسية |
| ☐ | New item | عنصر جديد |
| ☐ | New teaching material | مادة تعليمية جديدة |
| ☐ | New video | فيديو جديد |
| ☐ | No :items yet | لا توجد :items بعد |
| ☐ | No earlier versions yet. | لا توجد نسخ سابقة بعد. |
| ☐ | No images yet. | لا توجد صور بعد. |
| ☐ | No photographs yet | لا توجد صور بعد |
| ☐ | No records match this search and these filters. | لا توجد سجلات تطابق هذا البحث وهذه المرشحات. |
| ☐ | Not attached to any content | غير مرتبطة بأي محتوى |
| ☐ | Not saved yet: :reason | لم يُحفظ بعد: :reason |
| ☐ | Not saved: some fields need attention. They are listed at the top of the form. | لم يُحفظ: بعض الحقول تحتاج إلى مراجعة، وهي مدرجة في أعلى النموذج. |
| ☐ | Not used on the site | غير مستخدمة في الموقع |
| ☐ | Not yet in Arabic: :fields | لم يُترجم إلى العربية بعد: :fields |
| ☐ | Nothing has been added here yet. | لم يُضف شيء هنا بعد. |
| ☐ | Nothing matches | لا نتائج مطابقة |
| ☐ | number of students supervised | عدد الطلاب الذين أشرف عليهم |
| ☐ | On this page | في هذه الصفحة |
| ☐ | One line of the CV: a degree, a post, a membership. The CV page groups entries by section. | سطر واحد من السيرة الذاتية: درجة علمية أو منصب أو عضوية. تجمع صفحة السيرة الذاتية البنود حسب القسم. |
| ☐ | One or two sentences for the list. | جملة أو جملتان للقائمة. |
| ☐ | One or two sentences on what it recognised. Leave empty if the name says enough. | جملة أو جملتان عمّا كرّمته. اتركه فارغًا إن كان الاسم كافيًا. |
| ☐ | One or two sentences, :max characters at most. Leave out the quotation marks; they are added on the page. | جملة أو جملتان، :max حرفًا على الأكثر. لا تضع علامات التنصيص، فهي تُضاف في الصفحة. |
| ☐ | Only for long-running entries. | للبنود الممتدة فقط. |
| ☐ | Only if the family or university has confirmed the number. It appears in the figures on the home page; leave it blank to leave it out. | فقط إن أكدت الأسرة أو الجامعة العدد. يظهر ضمن الأرقام في الصفحة الرئيسية، فاتركه فارغًا لإخفائه. |
| ☐ | Only when there is no file to upload. | فقط عند عدم وجود ملف لرفعه. |
| ☐ | Open it | افتحه |
| ☐ | Opens the Videos page. Keep it to one or two. | يفتتح صفحة المرئيات. اكتفِ بتسجيل أو اثنين. |
| ☐ | Optional. Anything left empty here shows the English text on Arabic pages. | اختياري. ما يُترك فارغًا هنا يظهر نصه الإنجليزي في الصفحات العربية. |
| ☐ | Organisation | الجهة |
| ☐ | organisation | الجهة |
| ☐ | Other information holds anything that fits no other section: interviews, dedications, events. | تضم «معلومات أخرى» كل ما لا يندرج في قسم آخر: مقابلات وإهداءات وفعاليات. |
| ☐ | PDF, Word, PowerPoint or ZIP up to :size | PDF أو Word أو PowerPoint أو ZIP بحجم أقصاه :size |
| ☐ | Permissions note | ملاحظة الأذونات |
| ☐ | permissions note | ملاحظة الأذونات |
| ☐ | Photographs uploaded here are filed in albums for the Pictures page. Portraits and video posters appear here too. | تُحفظ الصور المرفوعة هنا في ألبومات لصفحة الصور، وتظهر هنا أيضًا الصور الشخصية وصور غلاف المرئيات. |
| ☐ | Photos | الصور |
| ☐ | Photos can be added once the album has a title and has been saved once. | يمكن إضافة الصور بعد أن يكون للألبوم عنوان ويُحفظ مرة واحدة. |
| ☐ | Place | المكان |
| ☐ | Portraits go warm monochrome; archive photos take the evergreen duotone. | تُعالج الصور الشخصية بأحادية اللون الدافئة، وصور الأرشيف بالتدرج الثنائي الأخضر. |
| ☐ | Poster image | صورة الغلاف |
| ☐ | Press / to jump here. | اضغط / للانتقال إلى هنا. |
| ☐ | Profile saved. | حُفظ الملف التعريفي. |
| ☐ | provider | المصدر |
| ☐ | public contact email | البريد الإلكتروني العام للتواصل |
| ☐ | Publications are his papers, books and chapters. Each can carry a PDF of the full text, and readers can filter them by type and year. | المنشورات هي أبحاثه وكتبه وفصوله. يمكن أن يُرفق بكلٍّ منها ملف PDF للنص الكامل، ويستطيع القرّاء تصفيتها حسب النوع والسنة. |
| ☐ | Publish | نشر |
| ☐ | Published | منشور |
| ☐ | Published quotes appear in order on the home page, one in each evergreen band between the chapters of his life. Use his own words or a line from an approved tribute, never a paraphrase. | تظهر الاقتباسات المنشورة بالترتيب في الصفحة الرئيسية، واحدًا في كل شريط أخضر بين فصول حياته. استخدم كلماته هو أو سطرًا من كلمة وفاء معتمدة، ولا تُعِد الصياغة أبدًا. |
| ☐ | Published: press Update to put changes live | منشور: اضغط «تحديث» لنشر التغييرات |
| ☐ | quote | الاقتباس |
| ☐ | Readers can browse the corner by these topics. Press Enter after each one. | يتصفح القرّاء الركن حسب هذه الموضوعات. اضغط Enter بعد كل واحد. |
| ☐ | Ready | جاهزة |
| ☐ | Records | السجلات |
| ☐ | Remove image | أزل الصورة |
| ☐ | Remove this image permanently? | إزالة هذه الصورة نهائيًا؟ |
| ☐ | Replace the image | استبدل الصورة |
| ☐ | Reset sorting | أعد الترتيب الافتراضي |
| ☐ | Restore | استعادة |
| ☐ | Restored an earlier version | استُعيدت نسخة سابقة |
| ☐ | Role description | وصف الدور |
| ☐ | role description | وصف الدور |
| ☐ | Sample text | نص تجريبي |
| ☐ | Save archive details | احفظ تفاصيل الأرشيف |
| ☐ | Save draft | احفظ المسودة |
| ☐ | Save first, then add images. | احفظ أولًا، ثم أضف الصور. |
| ☐ | Saved | حُفظ |
| ☐ | Search :items | البحث في :items |
| ☐ | Search images | البحث في الصور |
| ☐ | section | القسم |
| ☐ | Sections of this form | أقسام هذا النموذج |
| ☐ | Select :item | تحديد «:item» |
| ☐ | Select every :type on this page | تحديد كل :type في هذه الصفحة |
| ☐ | Selected rows | الصفوف المحددة |
| ☐ | selected works | الأعمال المختارة |
| ☐ | Selected works open the publications page. Keep the selection to a handful. | تفتتح الأعمال المختارة صفحة المنشورات. اكتفِ بعدد قليل منها. |
| ☐ | Short bio | السيرة المختصرة |
| ☐ | short bio | السيرة المختصرة |
| ☐ | Short biography | السيرة المختصرة |
| ☐ | short biography | السيرة المختصرة |
| ☐ | Show in Selected works | اعرضه ضمن الأعمال المختارة |
| ☐ | Shown at the foot of the About page. Leave both empty to use the site's contact details from Site settings. | يظهر في أسفل صفحة التعريف. اترك الحقلين فارغين لاستخدام بيانات التواصل من إعدادات الموقع. |
| ☐ | Shown on the item's own page. Not needed when the item links elsewhere. | يظهر في صفحة العنصر الخاصة. لا حاجة إليه إن كان العنصر يحيل إلى مكان آخر. |
| ☐ | slug | عنوان الصفحة |
| ☐ | Something that belongs on the record but fits no other section: an interview, a dedication, an event. Items are grouped by category. | ما يستحق التوثيق ولا يندرج في قسم آخر: مقابلة أو إهداء أو فعالية. تُجمع العناصر حسب الفئة. |
| ☐ | source | المصدر |
| ☐ | Source link | رابط المصدر |
| ☐ | source link | رابط المصدر |
| ☐ | source url | رابط المصدر |
| ☐ | Start year | سنة البداية |
| ☐ | start year | سنة البداية |
| ☐ | Status | الحالة |
| ☐ | status | الحالة |
| ☐ | Stay and keep editing | ابقَ وتابع التحرير |
| ☐ | Still holds sample text | ما زال يحوي نصًا تجريبيًا |
| ☐ | students supervised | الطلاب الذين أشرف عليهم |
| ☐ | Summary | الملخص |
| ☐ | summary | الملخص |
| ☐ | tags | الوسوم |
| ☐ | Teaching materials are lecture notes, syllabi and problem sets, grouped by course on the Teaching page. | المواد التعليمية مذكرات محاضرات وخطط مقررات ومجموعات تمارين، مجمّعة حسب المقرر في صفحة المواد التعليمية. |
| ☐ | Text & link | النص والرابط |
| ☐ | That album no longer exists. | لم يعد هذا الألبوم موجودًا. |
| ☐ | The album | الألبوم |
| ☐ | The award | الجائزة |
| ☐ | The degree, position or achievement, for example PhD in Agronomy. | الدرجة أو المنصب أو الإنجاز، مثل: دكتوراه في علم المحاصيل. |
| ☐ | The entry | البند |
| ☐ | The file is deleted from the site. | يُحذف الملف من الموقع. |
| ☐ | The file is removed from that page at once. | يُزال الملف من تلك الصفحة فورًا. |
| ☐ | The heading readers see first. | العنوان الذي يراه القرّاء أولًا. |
| ☐ | The heading readers see. | العنوان الذي يراه القرّاء. |
| ☐ | The identifier that starts with 10. — it becomes a permanent link. | المعرّف الذي يبدأ بـ 10. ويصبح رابطًا دائمًا. |
| ☐ | The item | العنصر |
| ☐ | The last part of the album's address. Leave it as it is unless the link must change. | الجزء الأخير من عنوان الألبوم. اتركه كما هو ما لم يلزم تغيير الرابط. |
| ☐ | The last part of the page's address. Leave it as it is unless the link must change. | الجزء الأخير من عنوان الصفحة. اتركه كما هو ما لم يلزم تغيير الرابط. |
| ☐ | The last part of the work's address. Leave it as it is unless the link must change. | الجزء الأخير من عنوان العمل. اتركه كما هو ما لم يلزم تغيير الرابط. |
| ☐ | The life story on the About page. Use the toolbar for headings, bold, italic, links, lists and quotes. | قصة حياته في صفحة التعريف. استخدم شريط الأدوات للعناوين والخط العريض والمائل والروابط والقوائم والاقتباسات. |
| ☐ | The material | المادة |
| ☐ | The occasion. | المناسبة. |
| ☐ | The photograph that opens the About page and the sign-in screens. It needs alt text, a caption, a place and a year before the profile can be published. | الصورة التي تفتتح صفحة التعريف وشاشات تسجيل الدخول. تحتاج إلى نص بديل وتعليق ومكان وسنة قبل نشر الملف التعريفي. |
| ☐ | The portrait can be added once the profile has a name and has been saved once. | يمكن إضافة الصورة الشخصية بعد إدخال الاسم وحفظ الملف التعريفي مرة واحدة. |
| ☐ | The poster can be added once the video has a title and has been saved once. | يمكن إضافة صورة الغلاف بعد أن يكون للفيديو عنوان ويُحفظ مرة واحدة. |
| ☐ | The quote | الاقتباس |
| ☐ | The recording | التسجيل |
| ☐ | The subject this work is filed under, used by the topic filter. Reuse an existing topic where one fits. | الموضوع الذي يُصنَّف تحته العمل، ويستخدمه مرشح الموضوعات. أعد استخدام موضوع قائم إن كان مناسبًا. |
| ☐ | The summary from the paper, if there is one. | ملخص البحث، إن وُجد. |
| ☐ | The text | النص |
| ☐ | The trash is empty. Anything moved to the trash waits here until it is restored. | سلة المحذوفات فارغة. ما يُنقل إليها يبقى هنا حتى تُستعاد. |
| ☐ | The university or institution he is known for. | الجامعة أو المؤسسة التي عُرف بها. |
| ☐ | The university, ministry or society. | الجامعة أو الوزارة أو الجمعية. |
| ☐ | The work | العمل |
| ☐ | The YouTube, Vimeo or other page address. Not needed for an uploaded file. | عنوان الصفحة على YouTube أو Vimeo أو غيرهما. لا حاجة إليه للملف المرفوع. |
| ☐ | This image is on a published page, so every detail is required. | هذه الصورة في صفحة منشورة، لذا كل التفاصيل مطلوبة. |
| ☐ | Through the decades files the photo under its year unless you choose another decade. | يضع قسم «عبر العقود» الصورة تحت سنتها ما لم تختر عقدًا آخر. |
| ☐ | Title | العنوان |
| ☐ | title | العنوان |
| ☐ | Title & summary | العنوان والملخص |
| ☐ | Title or words in :items | عنوان أو كلمات في :items |
| ☐ | topic | الموضوع |
| ☐ | Topic tags | وسوم الموضوعات |
| ☐ | Topics & links | الموضوعات والروابط |
| ☐ | Trash | سلة المحذوفات |
| ☐ | Treatment | المعالجة |
| ☐ | Two or three sentences. They open the home page and appear when the site is shared. | جملتان أو ثلاث تفتتح الصفحة الرئيسية وتظهر عند مشاركة الموقع. |
| ☐ | Two sentences shown in the list and in search results. | جملتان تظهران في القائمة وفي نتائج البحث. |
| ☐ | type | النوع |
| ☐ | Type to find an album | اكتب للبحث عن ألبوم |
| ☐ | Unpublish | إلغاء النشر |
| ☐ | Update | تحديث |
| ☐ | Updated | آخر تعديل |
| ☐ | Upload an image | ارفع صورة |
| ☐ | Uploaded video file | ملف الفيديو المرفوع |
| ☐ | url | الرابط |
| ☐ | Use a JPEG, PNG or WebP image. | استخدم صورة بصيغة JPEG أو PNG أو WebP. |
| ☐ | Use a PDF, Word, PowerPoint or ZIP file. | استخدم ملف PDF أو Word أو PowerPoint أو ZIP. |
| ☐ | Use an MP4 or WebM video. | استخدم فيديو بصيغة MP4 أو WebM. |
| ☐ | Use as cover | اجعلها الغلاف |
| ☐ | Use lowercase letters, numbers and hyphens only. | استخدم الأحرف اللاتينية الصغيرة والأرقام والشرطات فقط. |
| ☐ | Use the toolbar for headings (they build the table of contents), bold, italic, links, lists and quotes. For a sidenote, type [^1] in the text and start a paragraph with [^1]: and the note. | استخدم شريط الأدوات للعناوين (وهي تبني جدول المحتويات) والخط العريض والمائل والروابط والقوائم والاقتباسات. لإضافة حاشية، اكتب [^1] في النص وابدأ فقرة بـ [^1]: ثم الحاشية. |
| ☐ | Used as | مستخدمة بوصفها |
| ☐ | Used by :type: :label (in the trash). | يستخدمها :type: :label (في سلة المحذوفات). |
| ☐ | Used by :type: :label. | يستخدمها :type: :label. |
| ☐ | Used by the Type filter on the public page. | يستخدمه مرشح النوع في الصفحة العامة. |
| ☐ | venue or publisher | جهة النشر أو الناشر |
| ☐ | venue publisher | جهة النشر أو الناشر |
| ☐ | Version | الإصدار |
| ☐ | version | الإصدار |
| ☐ | Version history | سجل النسخ |
| ☐ | video file | ملف الفيديو |
| ☐ | Video link | رابط الفيديو |
| ☐ | video link | رابط الفيديو |
| ☐ | Video posters | صور غلاف المرئيات |
| ☐ | Videos are recordings of lectures, interviews and ceremonies, linked from YouTube or Vimeo or uploaded. | المرئيات تسجيلات لمحاضرات ومقابلات وحفلات، مرتبطة من YouTube أو Vimeo أو مرفوعة. |
| ☐ | Videos can be at most :size. | لا يتجاوز حجم الفيديو :size. |
| ☐ | View on site | اعرض على الموقع |
| ☐ | What a student will find in it. | ما سيجده الطالب فيها. |
| ☐ | What kind of material it is. | نوع المادة. |
| ☐ | What the recording is about, in two or three sentences. | موضوع التسجيل في جملتين أو ثلاث. |
| ☐ | When it happened. | متى حدث. |
| ☐ | When it was recorded. | متى سُجّل. |
| ☐ | When it was written or published. | متى كُتب أو نُشر. |
| ☐ | When set, the item links out to this address instead of opening its own page. | عند تحديده، يحيل العنصر إلى هذا العنوان بدل فتح صفحته الخاصة. |
| ☐ | When this version was written. | متى كُتب هذا الإصدار. |
| ☐ | Where it plays | مكان التشغيل |
| ☐ | Where on the CV page this belongs. | موضعه في صفحة السيرة الذاتية. |
| ☐ | Where the recording is filed when videos are grouped by decade. Leave empty to use its date. | العقد الذي يُصنَّف تحته التسجيل عند تجميع المرئيات حسب العقود. اتركه فارغًا لاستخدام تاريخه. |
| ☐ | Where the video is kept. | مكان حفظ الفيديو. |
| ☐ | Where the work appeared and how to find it. Leave out anything you do not have. | أين ظهر العمل وكيف يُعثر عليه. تجاهل ما ليس لديك. |
| ☐ | Who gave it. | من منحها. |
| ☐ | Who may download | من يحق له التنزيل |
| ☐ | Who said it. | قائله. |
| ☐ | Who said or wrote it, if not the Professor. | من قاله أو كتبه، إن لم يكن الأستاذ الدكتور. |
| ☐ | Who wrote it, if not the Professor. | كاتبه، إن لم يكن الأستاذ الدكتور. |
| ☐ | Year | السنة |
| ☐ | Year of publication. | سنة النشر. |
| ☐ | Yes, continue | نعم، تابع |
| ☐ | Your latest changes on this page have not been saved yet. If you leave now they will be lost. | لم تُحفظ آخر تغييراتك في هذه الصفحة بعد، وستضيع إن غادرت الآن. |
| ☐ | {1} :count :singular published.\|[0,*] :count :plural published. | {0} لم يُنشر شيء.\|{1} نُشر عنصر واحد (:singular).\|{2} نُشر عنصران (:plural).\|[3,10] نُشر :count عناصر (:plural).\|[11,*] نُشر :count عنصرًا (:plural). |
| ☐ | {1} :count image still lacks :details. Add the details in the media library before publishing.\|[2,*] :count images still lack :details. Add the details in the media library before publishing. | {1} ما زالت صورة واحدة تفتقر إلى :details. أضف التفاصيل في مكتبة الوسائط قبل النشر.\|{2} ما زالت صورتان تفتقران إلى :details. أضف التفاصيل في مكتبة الوسائط قبل النشر.\|[3,10] ما زالت :count صور تفتقر إلى :details. أضف التفاصيل في مكتبة الوسائط قبل النشر.\|[11,*] ما زالت :count صورة تفتقر إلى :details. أضف التفاصيل في مكتبة الوسائط قبل النشر. |
| ☐ | {1} :count item\|[0,*] :count items | {0} لا عناصر\|{1} عنصر واحد\|{2} عنصران\|[3,10] :count عناصر\|[11,*] :count عنصرًا |
| ☐ | {1} Image uploaded to :album. Open it to add alt text and a caption.\|[2,*] :count images uploaded to :album. Open each to add alt text and a caption. | {1} رُفعت الصورة إلى «:album». افتحها لإضافة النص البديل والتعليق.\|{2} رُفعت صورتان إلى «:album». افتح كل واحدة لإضافة النص البديل والتعليق.\|[3,10] رُفعت :count صور إلى «:album». افتح كل واحدة لإضافة النص البديل والتعليق.\|[11,*] رُفعت :count صورة إلى «:album». افتح كل واحدة لإضافة النص البديل والتعليق. |
| ☐ | {1} Image uploaded. Add its details below.\|[2,*] :count images uploaded. Add their details below. | {1} رُفعت الصورة. أضف تفاصيلها أدناه.\|{2} رُفعت صورتان. أضف تفاصيلهما أدناه.\|[3,10] رُفعت :count صور. أضف تفاصيلها أدناه.\|[11,*] رُفعت :count صورة. أضف تفاصيلها أدناه. |
| ☐ | {1} One field on this page still holds sample text from the site set-up. It is marked “:flag”.\|[2,*] :count fields on this page still hold sample text from the site set-up. They are marked “:flag”. | {1} ما زال حقل واحد في هذه الصفحة يحوي نصًا تجريبيًا من إعداد الموقع، وعليه علامة «:flag».\|{2} ما زال حقلان في هذه الصفحة يحويان نصًا تجريبيًا من إعداد الموقع، وعليهما علامة «:flag».\|[3,10] ما زالت :count حقول في هذه الصفحة تحوي نصًا تجريبيًا من إعداد الموقع، وعليها علامة «:flag».\|[11,*] ما زال :count حقلًا في هذه الصفحة يحوي نصًا تجريبيًا من إعداد الموقع، وعليها علامة «:flag». |
| ☐ | “:title” will be removed from the trash for good, together with its files and images. | سيُحذف «:title» من سلة المحذوفات نهائيًا، مع ملفاته وصوره. |

## Admin: dashboard, navigation, analytics, users, settings, moderation (477)

| ✓ | English | Arabic |
|---|---|---|
| ☐ | (keyboard shortcut Control or Command K) | (اختصار لوحة المفاتيح: Control أو Command مع K) |
| ☐ | (you) | (أنت) |
| ☐ | 1 result | نتيجة واحدة |
| ☐ | :actor :action | :actor: :action |
| ☐ | :actor added :thing | :actor: إضافة :thing |
| ☐ | :actor applied to join the Scientific Community | :actor: التقدّم بطلب للانضمام إلى المجتمع العلمي |
| ☐ | :actor approved a community application | :actor: قبول طلب انضمام إلى المجتمع العلمي |
| ☐ | :actor approved a tribute | :actor: اعتماد كلمة وفاء |
| ☐ | :actor approved a tribute from :name | :actor: اعتماد كلمة وفاء من :name |
| ☐ | :actor declined a community application | :actor: رفض طلب انضمام إلى المجتمع العلمي |
| ☐ | :actor declined a tribute | :actor: رفض كلمة وفاء |
| ☐ | :actor declined a tribute from :name | :actor: رفض كلمة وفاء من :name |
| ☐ | :actor declined the community application from :person | :actor: رفض طلب الانضمام المقدَّم من :person |
| ☐ | :actor deleted :file | :actor: حذف :file |
| ☐ | :actor deleted a file | :actor: حذف ملف |
| ☐ | :actor deleted a tribute | :actor: حذف كلمة وفاء |
| ☐ | :actor deleted a tribute from :name | :actor: حذف كلمة وفاء من :name |
| ☐ | :actor edited a tribute | :actor: تعديل كلمة وفاء |
| ☐ | :actor edited a tribute from :name | :actor: تعديل كلمة وفاء من :name |
| ☐ | :actor featured a tribute | :actor: إبراز كلمة وفاء |
| ☐ | :actor featured a tribute from :name | :actor: إبراز كلمة وفاء من :name |
| ☐ | :actor moved :thing back to draft | :actor: إعادة :thing إلى المسودات |
| ☐ | :actor moved :thing to the trash | :actor: نقل :thing إلى سلة المحذوفات |
| ☐ | :actor published :thing | :actor: نشر :thing |
| ☐ | :actor restored :thing from the trash | :actor: استعادة :thing من سلة المحذوفات |
| ☐ | :actor stopped featuring a tribute | :actor: إلغاء إبراز كلمة وفاء |
| ☐ | :actor stopped featuring a tribute from :name | :actor: إلغاء إبراز كلمة وفاء من :name |
| ☐ | :actor updated the details of :file | :actor: تحديث بيانات :file |
| ☐ | :actor updated the details of an image | :actor: تحديث بيانات صورة |
| ☐ | :actor uploaded :file | :actor: رفع :file |
| ☐ | :actor uploaded a file | :actor: رفع ملف |
| ☐ | :actor welcomed :person to the Scientific Community | :actor: الترحيب بـ:person في المجتمع العلمي |
| ☐ | :count published. | المنشور: :count. |
| ☐ | :count results | عدد النتائج: :count |
| ☐ | :count selected | المحدَّد: :count |
| ☐ | :done of :total ready | اكتمل :done من :total |
| ☐ | :name has been approved and emailed. | قُبل طلب :name وأُرسل إليه بريد إلكتروني. |
| ☐ | :name has been invited as :role. They have been emailed a link to set their password. | دُعي :name بدور «:role»، وأُرسل إليه رابط لتعيين كلمة المرور. |
| ☐ | :name has been reinstated. | أُعيدت عضوية :name. |
| ☐ | :name has been removed from the community. | أُنهيت عضوية :name في المجتمع العلمي. |
| ☐ | :name has been sent a new invitation link. | أُرسل إلى :name رابط دعوة جديد. |
| ☐ | :name is now :role. | أصبح دور :name: :role. |
| ☐ | :name is now without dashboard access. | لم يعد لدى :name وصول إلى لوحة التحكم. |
| ☐ | :name's account has been deactivated. They can no longer sign in. | عُطّل حساب :name، ولم يعد بإمكانه تسجيل الدخول. |
| ☐ | :name's account has been reactivated. | أُعيد تفعيل حساب :name. |
| ☐ | :name's application has been rejected and they have been emailed. | رُفض طلب :name وأُرسل إليه بريد إلكتروني. |
| ☐ | :period, compared with :comparison. | :period، مقارنةً بـ:comparison. |
| ☐ | :salutation, :name | :salutation يا :name |
| ☐ | :section has something published | في «:section» محتوى منشور |
| ☐ | :type · last changed :ago | :type · آخر تعديل :ago |
| ☐ | a :type | :type |
| ☐ | a former member | عضو سابق |
| ☐ | A new set-password link will be emailed. Any earlier link stops working. | سيُرسل بالبريد رابط جديد لتعيين كلمة المرور، ويتوقف عمل أي رابط سابق. |
| ☐ | A paper, book or chapter, with its PDF | بحث أو كتاب أو فصل، مع ملف PDF |
| ☐ | A post, degree or appointment | منصب أو درجة علمية أو تعيين |
| ☐ | A visitor | زائر |
| ☐ | About & profile | السيرة والملف الشخصي |
| ☐ | Actions | الإجراءات |
| ☐ | Active | نشط |
| ☐ | Active administrators: :count. The last one can never be demoted or deactivated. | المديرون النشطون: :count. ولا يمكن أبدًا تخفيض دور آخر مدير نشط أو تعطيل حسابه. |
| ☐ | Add alt text | إضافة النص البديل |
| ☐ | Add contact details | إضافة بيانات التواصل |
| ☐ | Add CV entry | إضافة بند إلى السيرة الذاتية |
| ☐ | Add the first :item | إضافة أول عنصر: :item |
| ☐ | Add the portrait | إضافة الصورة الشخصية |
| ☐ | Admin | الإدارة |
| ☐ | Administrators manage users, settings and the community and must use two-factor authentication. Editors manage content and tributes. | يدير المديرون المستخدمين والإعدادات والمجتمع العلمي، ويلزمهم استخدام التحقق بخطوتين. ويدير المحررون المحتوى وكلمات الوفاء. |
| ☐ | Affiliation | الجهة |
| ☐ | Album | ألبوم |
| ☐ | All roles | جميع الأدوار |
| ☐ | All sections | جميع الأقسام |
| ☐ | Alt text describes a photo for people who cannot see it. Each image needs a sentence before its page can be published. | النص البديل يصف الصورة لمن لا يستطيع رؤيتها. وتحتاج كل صورة إلى جملة وصفية قبل نشر صفحتها. |
| ☐ | an :type | :type |
| ☐ | An account with this email already exists. Change its role from the list instead. | يوجد حساب بهذا البريد الإلكتروني بالفعل. غيّر دوره من القائمة بدلًا من ذلك. |
| ☐ | An administrator can switch it on in Site settings once the client has approved it. | يمكن لأحد المديرين تفعيلها من إعدادات الموقع بعد موافقة العميل. |
| ☐ | An essay or note for the Commentary Corner | مقالة أو خاطرة لركن المقالات |
| ☐ | Analytics | الإحصاءات |
| ☐ | Analytics is switched off, so nothing new is being recorded. Figures already collected stay readable here. | الإحصاءات معطّلة، فلا يُسجَّل شيء جديد. وتبقى الأرقام التي جُمعت من قبل متاحة هنا. |
| ☐ | Any | الكل |
| ☐ | Any community membership is kept. A new administrator must set up two-factor authentication before the dashboard opens. | تبقى أي عضوية في المجتمع العلمي كما هي. ويجب على المدير الجديد إعداد التحقق بخطوتين قبل أن تُفتح له لوحة التحكم. |
| ☐ | Applicant | متقدّم |
| ☐ | Applications | طلبات الانضمام |
| ☐ | Applications and members | طلبات الانضمام والأعضاء |
| ☐ | Applications are approved or rejected here; members can be suspended, reinstated or removed. Every decision is emailed to the person and recorded in the audit log. | تُقبل طلبات الانضمام أو تُرفض هنا، ويمكن تعليق عضوية الأعضاء أو إعادتها أو إنهاؤها. ويُبلَّغ صاحب الشأن بكل قرار بالبريد الإلكتروني، ويُسجَّل في سجل التدقيق. |
| ☐ | Applied | تاريخ التقديم |
| ☐ | applied to join the community | التقدّم بطلب للانضمام إلى المجتمع العلمي |
| ☐ | Applies to counts and numbers in running text on Arabic pages. Years, citations, DOIs and data always stay in Western digits. | يسري على الأعداد والأرقام الواردة في سياق النص في الصفحات العربية. أما السنوات والإحالات المرجعية ومعرّفات DOI والبيانات فتبقى دائمًا بالأرقام الغربية. |
| ☐ | Approve | قبول |
| ☐ | Approve selected | اعتماد المحدَّد |
| ☐ | Approve this application? | قبول هذا الطلب؟ |
| ☐ | approved a community application | قبول طلب انضمام إلى المجتمع العلمي |
| ☐ | approved a tribute | اعتماد كلمة وفاء |
| ☐ | Approved members may attach up to :files images or documents to a message. Files are private to members and administrators. Leave a limit blank for the default (:image MB for images, :document MB for documents). | يمكن للأعضاء المقبولين إرفاق ما يصل إلى :files من الصور أو المستندات بالرسالة الواحدة. والملفات خاصة بالأعضاء والمديرين. اترك الحد فارغًا لاستخدام القيمة الافتراضية (:image ميغابايت للصور، و:document ميغابايت للمستندات). |
| ☐ | Arabic pages | الصفحات العربية |
| ☐ | Arabic-Indic (٠–٩) | الأرقام الهندية (٠–٩) |
| ☐ | assigned a role | إسناد دور |
| ☐ | Audience | الجمهور |
| ☐ | Author suspended and report resolved. | عُلّقت عضوية الكاتب وأُغلق البلاغ. |
| ☐ | Back to the dashboard | العودة إلى لوحة التحكم |
| ☐ | Before launch | قبل الإطلاق |
| ☐ | Browsers | المتصفحات |
| ☐ | by :name | بواسطة :name |
| ☐ | Came back for another visit the same day. | عادوا لزيارة أخرى في اليوم نفسه. |
| ☐ | Campaign sources | مصادر الحملات |
| ☐ | Change role | تغيير الدور |
| ☐ | Change the role of :name | تغيير دور :name |
| ☐ | changed roles | تغيير الأدوار |
| ☐ | Chat reports | بلاغات المحادثة |
| ☐ | Chat reports open | بلاغات محادثة مفتوحة |
| ☐ | Choose administrator or editor. | اختر مديرًا أو محررًا. |
| ☐ | Choose administrator, editor or no dashboard access. | اختر مديرًا أو محررًا أو بلا وصول إلى لوحة التحكم. |
| ☐ | Clear | لا شيء معلّق |
| ☐ | Clear the search and filters | مسح البحث وعوامل التصفية |
| ☐ | Close a menu, drawer or dialog | إغلاق قائمة أو درج أو نافذة حوار |
| ☐ | Close menu | إغلاق القائمة |
| ☐ | Collapse to icons | طيّ القائمة إلى أيقونات |
| ☐ | Commentaries | المقالات |
| ☐ | Community applications pending | طلبات انضمام بانتظار المراجعة |
| ☐ | Community chat uploads | مرفقات محادثة المجتمع العلمي |
| ☐ | Community member | عضو في المجتمع العلمي |
| ☐ | Community status | حالة العضوية |
| ☐ | Contact details are set | بيانات التواصل مضافة |
| ☐ | content | محتوى |
| ☐ | Content | المحتوى |
| ☐ | Countries | البلدان |
| ☐ | Countries are told from the address, which is then discarded; no cookie is set and nothing identifies a person. | يُستدل على البلد من عنوان الاتصال الذي يُحذف بعد ذلك؛ ولا يُحفظ أي ملف تعريف ارتباط ولا شيء يحدّد هوية شخص. |
| ☐ | Create | إنشاء |
| ☐ | created content | إنشاء محتوى |
| ☐ | Custom range | فترة مخصصة |
| ☐ | CV entries | بنود السيرة الذاتية |
| ☐ | CV entry | بند في السيرة الذاتية |
| ☐ | Dashboard menu | قائمة لوحة التحكم |
| ☐ | Dashboard role | الدور في لوحة التحكم |
| ☐ | Day | اليوم |
| ☐ | Deactivate | تعطيل |
| ☐ | Deactivate this account? | تعطيل هذا الحساب؟ |
| ☐ | Deactivated | معطَّل |
| ☐ | deactivated an account | تعطيل حساب |
| ☐ | Decline | رفض |
| ☐ | Decline selected | رفض المحدَّد |
| ☐ | Decline undone. | أُلغي الرفض. |
| ☐ | Declines undone for :count tributes. | أُلغي رفض كلمات الوفاء (العدد: :count). |
| ☐ | Default: | القيمة الافتراضية: |
| ☐ | deleted a chat message | حذف رسالة في المحادثة |
| ☐ | deleted a file | حذف ملف |
| ☐ | deleted a tribute | حذف كلمة وفاء |
| ☐ | deleted a user | حذف مستخدم |
| ☐ | deleted content | حذف محتوى |
| ☐ | deleted content permanently | حذف محتوى نهائيًا |
| ☐ | Desktop | حاسوب مكتبي |
| ☐ | Devices | الأجهزة |
| ☐ | Digits on Arabic pages | الأرقام في الصفحات العربية |
| ☐ | Digits, spaces and a leading + only. | أرقام ومسافات وعلامة + في البداية فقط. |
| ☐ | Direct or unknown | مباشر أو غير معروف |
| ☐ | Done | مكتمل |
| ☐ | Down :percent% on :period | انخفاض بنسبة :percent% مقارنةً بـ:period |
| ☐ | Drafts waiting more than :days days | مسودات تنتظر منذ أكثر من :days يومًا |
| ☐ | Each visitor is counted once per day. | يُحتسب كل زائر مرة واحدة في اليوم. |
| ☐ | edited a tribute | تعديل كلمة وفاء |
| ☐ | Editor | محرر |
| ☐ | Email unverified | البريد الإلكتروني غير مؤكَّد |
| ☐ | Engaged time | وقت القراءة |
| ☐ | Engaged time per visit | وقت القراءة لكل زيارة |
| ☐ | Engagement | التفاعل |
| ☐ | Enter the name to show on the tribute. | أدخل الاسم الذي سيظهر مع كلمة الوفاء. |
| ☐ | Every item has its Arabic text. | لكل عنصر نصه العربي. |
| ☐ | Every page opened. | كل صفحة فُتحت. |
| ☐ | Every section holds real copy. | تتضمن جميع الأقسام نصوصًا حقيقية. |
| ☐ | Feature on the page | إبرازها في الصفحة |
| ☐ | Featured | مُبرَزة |
| ☐ | featured a tribute | إبراز كلمة وفاء |
| ☐ | Fewer | أقل |
| ☐ | Filter | تصفية |
| ☐ | First day | اليوم الأول |
| ☐ | first day | اليوم الأول |
| ☐ | Fix typos | تصحيح الأخطاء الإملائية |
| ☐ | Go to | انتقال إلى |
| ☐ | Good afternoon | طاب يومك |
| ☐ | Good evening | مساء الخير |
| ☐ | Good morning | صباح الخير |
| ☐ | Headline figures | الأرقام الرئيسية |
| ☐ | Home of the dashboard | الصفحة الرئيسية للوحة التحكم |
| ☐ | Home quote | اقتباس للصفحة الرئيسية |
| ☐ | Home quotes | اقتباسات الصفحة الرئيسية |
| ☐ | Images without alt text | صور بلا نص بديل |
| ☐ | in :channel | في :channel |
| ☐ | Included in the email and kept in the audit log. | تُضمَّن في البريد الإلكتروني وتُحفظ في سجل التدقيق. |
| ☐ | Into an album, then add captions | إلى ألبوم، ثم أضف التعليقات |
| ☐ | Invitation pending | الدعوة معلّقة |
| ☐ | Invite a staff member | دعوة عضو في الفريق |
| ☐ | invited a staff member | دعوة عضو في الفريق |
| ☐ | just now | الآن |
| ☐ | Keyboard shortcuts | اختصارات لوحة المفاتيح |
| ☐ | Language of the invitation | لغة الدعوة |
| ☐ | Largest document (MB) | أقصى حجم للمستند (ميغابايت) |
| ☐ | Largest image (MB) | أقصى حجم للصورة (ميغابايت) |
| ☐ | Last 12 months | آخر 12 شهرًا |
| ☐ | Last 30 days | آخر 30 يومًا |
| ☐ | Last 30 days, :period, against :comparison. | آخر 30 يومًا، :period، مقارنةً بـ:comparison. |
| ☐ | Last 7 days | آخر 7 أيام |
| ☐ | Last day | اليوم الأخير |
| ☐ | last day | اليوم الأخير |
| ☐ | Line chart of unique visitors, :period, with :comparison as a dashed line. The table below gives the figures. | رسم بياني خطي للزوار الفريدين، :period، ويظهر :comparison بخط متقطع. ويعرض الجدول أدناه الأرقام. |
| ☐ | Location | المكان |
| ☐ | Mail sender | مُرسِل البريد |
| ☐ | Mail sender address | عنوان مُرسِل البريد |
| ☐ | Mail sender name | اسم مُرسِل البريد |
| ☐ | Media | الوسائط |
| ☐ | Media library | مكتبة الوسائط |
| ☐ | Members have reported chat messages. Look at each and keep or remove the message. | أبلغ الأعضاء عن رسائل في المحادثة. راجع كل بلاغ، ثم أبقِ الرسالة أو أزلها. |
| ☐ | Members may attach files | يمكن للأعضاء إرفاق ملفات |
| ☐ | Members will see ":placeholder" in its place, its attached files are deleted, and every report about it will be resolved. | سيرى الأعضاء «:placeholder» مكانها، وستُحذف ملفاتها المرفقة، وستُغلق كل البلاغات المتعلقة بها. |
| ☐ | Message by :name | رسالة من :name |
| ☐ | Message removed and report resolved. | أُزيلت الرسالة وأُغلق البلاغ. |
| ☐ | Messages members have reported. Resolve a report, remove the message, or suspend its author; each action is recorded in the audit log. | رسائل أبلغ عنها الأعضاء. يمكنك إغلاق البلاغ، أو إزالة الرسالة، أو تعليق عضوية كاتبها؛ ويُسجَّل كل إجراء في سجل التدقيق. |
| ☐ | Mobile | هاتف محمول |
| ☐ | Month | الشهر |
| ☐ | More visitors | زوار أكثر |
| ☐ | Most engaging pages | أكثر الصفحات جذبًا للقراءة |
| ☐ | move | تنقّل |
| ☐ | Name or email | الاسم أو البريد الإلكتروني |
| ☐ | Needs Arabic translation | يحتاج إلى ترجمة عربية |
| ☐ | Needs attention | يحتاج إلى متابعة |
| ☐ | Needs your attention | يحتاج إلى متابعتك |
| ☐ | New :type | إضافة :type |
| ☐ | New commentary | مقالة جديدة |
| ☐ | New publication | منشور جديد |
| ☐ | New tributes and community applications are announced to this address. When it is blank, every administrator and editor is emailed instead. | تُرسل إلى هذا العنوان إشعارات كلمات الوفاء الجديدة وطلبات الانضمام إلى المجتمع العلمي. وإذا كان فارغًا، يُراسَل جميع المديرين والمحررين. |
| ☐ | No activity has been recorded yet. | لم يُسجَّل أي نشاط بعد. |
| ☐ | No applications are waiting for review. | لا توجد طلبات بانتظار المراجعة. |
| ☐ | No applications have been rejected. | لم يُرفض أي طلب. |
| ☐ | No countries were recorded in this period. | لم يُسجَّل أي بلد في هذه الفترة. |
| ☐ | No dashboard access | بلا وصول إلى لوحة التحكم |
| ☐ | No email given | لم يُذكر بريد إلكتروني |
| ☐ | No figures for :period | لا توجد أرقام لـ:period |
| ☐ | No members are suspended. | لا يوجد أعضاء معلّقة عضويتهم. |
| ☐ | No pages in this section were read in this period. | لم تُقرأ أي صفحة من هذا القسم في هذه الفترة. |
| ☐ | No pages were read in this period. | لم تُقرأ أي صفحة في هذه الفترة. |
| ☐ | No reason given. | لم يُذكر سبب. |
| ☐ | No reports are waiting. | لا توجد بلاغات بانتظار المراجعة. |
| ☐ | No reports have been resolved yet. | لم يُغلق أي بلاغ بعد. |
| ☐ | No sample text is left | لم يبقَ نص تجريبي |
| ☐ | No tributes have been approved yet. | لم تُعتمد أي كلمة وفاء بعد. |
| ☐ | No tributes have been rejected. | لم تُرفض أي كلمة وفاء. |
| ☐ | No visits have been recorded in the last 30 days yet. | لم تُسجَّل أي زيارات في آخر 30 يومًا بعد. |
| ☐ | No visits were recorded in this period. | لم تُسجَّل أي زيارات في هذه الفترة. |
| ☐ | Nobody matches your search. | لا يوجد أحد يطابق بحثك. |
| ☐ | None | لا يوجد |
| ☐ | Not given | غير مذكور |
| ☐ | Not now | ليس الآن |
| ☐ | Not saved yet | لم يُحفظ بعد |
| ☐ | Note | ملاحظة |
| ☐ | Note to the applicant (optional) | ملاحظة إلى المتقدّم (اختيارية) |
| ☐ | Note to the member (optional) | ملاحظة إلى العضو (اختيارية) |
| ☐ | Nothing is published here yet, so visitors see an empty page. | لم يُنشر شيء هنا بعد، لذا يرى الزوار صفحة فارغة. |
| ☐ | Nothing is waiting for review. | لا شيء بانتظار المراجعة. |
| ☐ | Nothing is waiting for review. Everything is up to date. | لا شيء بانتظار المراجعة. كل شيء محدَّث. |
| ☐ | Nothing matches “:term”. Try another word. | لا توجد نتائج مطابقة لـ«:term». جرّب كلمة أخرى. |
| ☐ | Nothing was recorded in this period. | لم يُسجَّل شيء في هذه الفترة. |
| ☐ | Notification address | عنوان الإشعارات |
| ☐ | Notifications | الإشعارات |
| ☐ | of :list | لـ:list |
| ☐ | of :name | لـ:name |
| ☐ | of pages | للصفحات |
| ☐ | of visitors per day | للزوار في كل يوم |
| ☐ | Off by default. Turning it off stops recording at once; figures already collected stay on the report. | معطّل افتراضيًا. وتعطيله يوقف التسجيل فورًا، وتبقى الأرقام التي جُمعت في التقرير. |
| ☐ | On the site now | على الموقع الآن |
| ☐ | Only an approved tribute can be featured. | لا يمكن إبراز إلا كلمة وفاء معتمدة. |
| ☐ | open | فتح |
| ☐ | Open menu | فتح القائمة |
| ☐ | Open reports | فتح البلاغات |
| ☐ | Open the command palette: go to any screen, start something new, find content by title | فتح لوحة الأوامر: الانتقال إلى أي شاشة، أو بدء عمل جديد، أو البحث عن محتوى بعنوانه |
| ☐ | Open the full report | فتح التقرير الكامل |
| ☐ | Open the oldest | فتح الأقدم |
| ☐ | Open the profile | فتح الملف الشخصي |
| ☐ | Opens the home page | يفتح الصفحة الرئيسية |
| ☐ | Other information | معلومات أخرى |
| ☐ | Other item | عنصر آخر |
| ☐ | Other pages | صفحات أخرى |
| ☐ | Page | صفحة |
| ☐ | Page view | مشاهدة صفحة |
| ☐ | Page views | مشاهدات الصفحات |
| ☐ | Page views are kept for :days days, then only the daily totals remain, for :months months. | تُحفظ مشاهدات الصفحات مدة :days يومًا، ثم لا يبقى إلا المجاميع اليومية مدة :months شهرًا. |
| ☐ | Pages | الصفحات |
| ☐ | Pages ranked by total engaged time | الصفحات مرتبة بحسب إجمالي وقت القراءة |
| ☐ | People have asked to join the Scientific Community. Approve them to open the members' chat. | طلب أشخاص الانضمام إلى المجتمع العلمي. اقبل طلباتهم ليُفتح لهم باب محادثة الأعضاء. |
| ☐ | Per view | لكل مشاهدة |
| ☐ | Period | الفترة |
| ☐ | Pictures & albums | الصور والألبومات |
| ☐ | pinned a chat message | تثبيت رسالة في المحادثة |
| ☐ | Portrait is set | الصورة الشخصية مضافة |
| ☐ | Profile | الملف الشخصي |
| ☐ | Protect your account with two-factor authentication | احمِ حسابك بالتحقق بخطوتين |
| ☐ | Public contact details | بيانات التواصل العامة |
| ☐ | Public contact details, where staff notifications go and how emails are signed. Leave a field blank to use the default. | بيانات التواصل العامة، والعنوان الذي تصل إليه إشعارات الفريق، وطريقة توقيع الرسائل. اترك الحقل فارغًا لاستخدام القيمة الافتراضية. |
| ☐ | Public contact email | البريد الإلكتروني العام للتواصل |
| ☐ | Public contact phone | هاتف التواصل العام |
| ☐ | Public postal address | العنوان البريدي العام |
| ☐ | Publish, or update what is already published | نشر، أو تحديث ما نُشر من قبل |
| ☐ | published | منشور |
| ☐ | published content | نشر محتوى |
| ☐ | Ranked by the time visitors spent reading: the tab in view and the visitor scrolling or interacting within the last :seconds seconds. | مرتبة بحسب الوقت الذي قضاه الزوار في القراءة: أي حين تكون علامة التبويب ظاهرة ويمرّر الزائر الصفحة أو يتفاعل معها خلال آخر :seconds ثانية. |
| ☐ | Reactivate | إعادة التفعيل |
| ☐ | Reactivate this account? | إعادة تفعيل هذا الحساب؟ |
| ☐ | reactivated an account | إعادة تفعيل حساب |
| ☐ | Reason for joining | سبب الانضمام |
| ☐ | Recent activity | النشاط الأخير |
| ☐ | Recommended for administrator accounts: signing in also asks for a six-digit code from an authenticator app, so a stolen password alone cannot open the dashboard. | يُوصى به لحسابات المديرين: يطلب تسجيل الدخول أيضًا رمزًا من ستة أرقام من تطبيق المصادقة، فلا تكفي كلمة مرور مسروقة وحدها لفتح لوحة التحكم. |
| ☐ | Record visitor analytics | تسجيل إحصاءات الزوار |
| ☐ | Referrers | المواقع المُحيلة |
| ☐ | Reinstate | إعادة العضوية |
| ☐ | Reinstate this member? | إعادة عضوية هذا العضو؟ |
| ☐ | reinstated a community member | إعادة عضوية عضو في المجتمع العلمي |
| ☐ | Reject | رفض |
| ☐ | Reject this application? | رفض هذا الطلب؟ |
| ☐ | rejected a community application | رفض طلب انضمام إلى المجتمع العلمي |
| ☐ | rejected a tribute | رفض كلمة وفاء |
| ☐ | Remove from featured | إلغاء الإبراز |
| ☐ | Remove message | إزالة الرسالة |
| ☐ | Remove this member? | إنهاء عضوية هذا العضو؟ |
| ☐ | Remove this message permanently? | إزالة هذه الرسالة نهائيًا؟ |
| ☐ | removed a community member | إنهاء عضوية عضو في المجتمع العلمي |
| ☐ | removed a role | سحب دور |
| ☐ | removed a tribute from the featured place | إلغاء إبراز كلمة وفاء |
| ☐ | Replace before launch | يُستبدل قبل الإطلاق |
| ☐ | Replace the sample text in :section | استبدال النص التجريبي في :section |
| ☐ | Report resolved. | أُغلق البلاغ. |
| ☐ | Report state | حالة البلاغ |
| ☐ | reported a chat message | الإبلاغ عن رسالة في المحادثة |
| ☐ | Reported by :name | أبلغ عنها: :name |
| ☐ | Resend invite | إعادة إرسال الدعوة |
| ☐ | Resend the invitation? | إعادة إرسال الدعوة؟ |
| ☐ | resent a staff invitation | إعادة إرسال دعوة إلى عضو في الفريق |
| ☐ | Resolve | إغلاق البلاغ |
| ☐ | Resolved | مُغلق |
| ☐ | resolved a chat report | إغلاق بلاغ في المحادثة |
| ☐ | restored content from the trash | استعادة محتوى من سلة المحذوفات |
| ☐ | Results | النتائج |
| ☐ | returned a page to its original wording | إعادة صفحة إلى نصها الأصلي |
| ☐ | returned a tribute to the review queue | إعادة كلمة وفاء إلى قائمة المراجعة |
| ☐ | Returning visitors | الزوار العائدون |
| ☐ | Review tributes | مراجعة كلمات الوفاء |
| ☐ | Reviewed | تاريخ المراجعة |
| ☐ | Role | الدور |
| ☐ | Roles | الأدوار |
| ☐ | Save (a draft stays a draft) | حفظ (تبقى المسودة مسودة) |
| ☐ | Save changes | حفظ التغييرات |
| ☐ | Save role | حفظ الدور |
| ☐ | Save settings | حفظ الإعدادات |
| ☐ | Saved :time | حُفظ :time |
| ☐ | Screen, action or title | شاشة أو إجراء أو عنوان |
| ☐ | Scroll depth | عمق التمرير |
| ☐ | Search or jump to | ابحث أو انتقل إلى |
| ☐ | Search or jump to… | ابحث أو انتقل إلى… |
| ☐ | Search this list, or open the command palette | البحث في هذه القائمة، أو فتح لوحة الأوامر |
| ☐ | Search users | البحث في المستخدمين |
| ☐ | Section | القسم |
| ☐ | Select all on this page | تحديد الكل في هذه الصفحة |
| ☐ | Select tribute from :name | تحديد كلمة الوفاء من :name |
| ☐ | Send the invitation | إرسال الدعوة |
| ☐ | Send the link | إرسال الرابط |
| ☐ | Set up two-factor | إعداد التحقق بخطوتين |
| ☐ | Shorten the note to :max characters or fewer. | اختصر الملاحظة إلى :max حرفًا أو أقل. |
| ☐ | Shorten the tribute to :max characters or fewer. | اختصر كلمة الوفاء إلى :max حرفًا أو أقل. |
| ☐ | Show | عرض |
| ☐ | Show the figures as a table | عرض الأرقام في جدول |
| ☐ | Show them | عرضها |
| ☐ | Show these shortcuts | عرض هذه الاختصارات |
| ☐ | Shown in the Contact block of the About page when the profile has no contact email of its own. | تظهر في قسم التواصل بصفحة «عن الأستاذ الدكتور خضر» حين لا يتضمن الملف الشخصي بريدًا خاصًا به. |
| ☐ | signed in | تسجيل الدخول |
| ☐ | Site settings | إعدادات الموقع |
| ☐ | Site settings saved. | حُفظت إعدادات الموقع. |
| ☐ | Staff accounts and community users. Invite administrators and editors by email; they choose their own password from the link and administrators set up two-factor authentication on first sign-in. | حسابات الفريق ومستخدمو المجتمع العلمي. ادعُ المديرين والمحررين بالبريد الإلكتروني؛ فيختارون كلمات المرور بأنفسهم من الرابط، ويُعِدّ المديرون التحقق بخطوتين عند أول تسجيل دخول. |
| ☐ | Start something | ابدأ عملًا جديدًا |
| ☐ | Suspend author | تعليق عضوية الكاتب |
| ☐ | Suspend the author? | تعليق عضوية الكاتب؟ |
| ☐ | Suspend this member? | تعليق عضوية هذا العضو؟ |
| ☐ | suspended a community member | تعليق عضوية عضو في المجتمع العلمي |
| ☐ | Switch it on in :settings once the client has approved it. | فعّلها من :settings بعد موافقة العميل. |
| ☐ | Tablet | جهاز لوحي |
| ☐ | Teaching material | مادة تعليمية |
| ☐ | Teaching materials | المواد التعليمية |
| ☐ | the :type :label | :type :label |
| ☐ | The About page has no portrait yet. | لا تتضمن صفحة السيرة صورة شخصية بعد. |
| ☐ | The About page is published | صفحة السيرة منشورة |
| ☐ | The About page opens with his portrait. | تُفتتح صفحة السيرة بصورته الشخصية. |
| ☐ | The biography is public. | السيرة الذاتية متاحة للعموم. |
| ☐ | the day before | اليوم السابق |
| ☐ | The email and the dashboard start in this language; they can change it later. | يصل البريد الإلكتروني وتُفتح لوحة التحكم بهذه اللغة، ويمكن تغييرها لاحقًا. |
| ☐ | The name and address every email from the site is sent from. The address should belong to the site's own domain so that it is not rejected as spam. | الاسم والعنوان اللذان تُرسل منهما كل رسائل الموقع. ويُستحسن أن يكون العنوان من نطاق الموقع نفسه كي لا يُرفض بوصفه رسالة مزعجة. |
| ☐ | the previous 12 months | الأشهر الاثني عشر السابقة |
| ☐ | the previous :count days | الأيام الـ:count السابقة |
| ☐ | The profile is still a draft, so the About page is empty. | ما زال الملف الشخصي مسودة، لذا فصفحة السيرة فارغة. |
| ☐ | The profile still holds sample text from the set-up. | ما زال الملف الشخصي يتضمن نصًا تجريبيًا من مرحلة الإعداد. |
| ☐ | The site's own count of its readers, shown under Audience on the dashboard. Switch it on only once the client has approved visitor analytics and any notice it needs. While it is on, the footer links to a Privacy page that explains it to visitors. | إحصاء الموقع الخاص لقرّائه، ويظهر تحت «الجمهور» في لوحة التحكم. لا تفعّله إلا بعد موافقة العميل على إحصاءات الزوار وعلى أي إشعار تتطلبه. وما دام مفعّلًا، يتضمن تذييل الموقع رابطًا إلى صفحة الخصوصية التي تشرحه للزوار. |
| ☐ | Their chat access ends immediately and they will be emailed. You can reinstate them later. | سينتهي وصوله إلى المحادثة فورًا وسيُرسل إليه بريد إلكتروني. ويمكنك إعادة عضويته لاحقًا. |
| ☐ | Their chat access ends immediately, they will be emailed, and this report is resolved. | سينتهي وصوله إلى المحادثة فورًا، وسيُرسل إليه بريد إلكتروني، ويُغلق هذا البلاغ. |
| ☐ | Their chat access returns immediately and they will be emailed. | سيعود وصوله إلى المحادثة فورًا وسيُرسل إليه بريد إلكتروني. |
| ☐ | Their membership ends and they will be emailed. Their account and messages remain; they may apply again. | ستنتهي عضويته وسيُرسل إليه بريد إلكتروني. ويبقى حسابه ورسائله، ويمكنه التقدّم مرة أخرى. |
| ☐ | There are no accounts yet. Invite the first staff member. | لا توجد حسابات بعد. ادعُ أول عضو في الفريق. |
| ☐ | There are no members yet. | لا يوجد أعضاء بعد. |
| ☐ | There is no public contact email yet. | لا يوجد بريد إلكتروني عام للتواصل بعد. |
| ☐ | These were started but never published. Finish and publish them, or move them to the trash. | بُدئ العمل عليها ولم تُنشر قط. أكملها وانشرها، أو انقلها إلى سلة المحذوفات. |
| ☐ | They become a member and can use the chat straight away. They will be emailed. | سيصبح عضوًا ويستطيع استخدام المحادثة فورًا، وسيُرسل إليه بريد إلكتروني. |
| ☐ | They can sign in again with their existing password and role. | سيتمكن من تسجيل الدخول مجددًا بكلمة المرور والدور الحاليين. |
| ☐ | They receive an email with a link to choose their password. No password is shown here or sent by email. | سيصله بريد إلكتروني فيه رابط لاختيار كلمة المرور. ولا تُعرض أي كلمة مرور هنا ولا تُرسل بالبريد. |
| ☐ | They will be emailed. You can still approve them later from the Rejected tab. | سيُرسل إليه بريد إلكتروني. ويظل بإمكانك قبوله لاحقًا من تبويب «المرفوضة». |
| ☐ | They will be signed out and cannot sign in again until the account is reactivated. Nothing they created is removed. | سيُسجَّل خروجه ولن يستطيع تسجيل الدخول مجددًا حتى يُعاد تفعيل الحساب. ولن يُحذف شيء مما أنشأه. |
| ☐ | This field still holds sample text from the site set-up. Write the real text before the site launches. | ما زال هذا الحقل يتضمن نصًا تجريبيًا من مرحلة إعداد الموقع. اكتب النص الحقيقي قبل إطلاق الموقع. |
| ☐ | This is the last active administrator. Make someone else an administrator first. | هذا آخر مدير نشط. اجعل شخصًا آخر مديرًا أولًا. |
| ☐ | This period | هذه الفترة |
| ☐ | This person has already set up their account, so there is no invitation to resend. | أعدّ هذا الشخص حسابه بالفعل، فلا توجد دعوة لإعادة إرسالها. |
| ☐ | Time spent reading, on average. | متوسط الوقت المستغرق في القراءة. |
| ☐ | to :name | إلى :name |
| ☐ | To do | لم يكتمل بعد |
| ☐ | Translate :section | ترجمة :section |
| ☐ | Tribute | كلمة وفاء |
| ☐ | Tribute featured. It now opens the book of remembrance. | أُبرزت كلمة الوفاء، وهي الآن فاتحة كتاب الذكرى. |
| ☐ | tribute from :name | كلمة الوفاء من :name |
| ☐ | Tribute from :name approved. It is on the Tributes page now. | اعتُمدت كلمة الوفاء من :name، وهي الآن في صفحة كلمات الوفاء. |
| ☐ | Tribute from :name declined. It will not appear on the site. | رُفضت كلمة الوفاء من :name، ولن تظهر على الموقع. |
| ☐ | Tribute no longer featured. | أُلغي إبراز كلمة الوفاء. |
| ☐ | Tribute status | حالة كلمة الوفاء |
| ☐ | Tribute updated. The original wording is kept in the audit log. | حُدّثت كلمة الوفاء، ويُحفظ نصها الأصلي في سجل التدقيق. |
| ☐ | Tributes appear on the site only after they are approved. One approved tribute can be featured to open the book of remembrance. Email addresses are shown here to moderators and never published. | لا تظهر كلمات الوفاء على الموقع إلا بعد اعتمادها. ويمكن إبراز كلمة وفاء معتمدة واحدة لتكون فاتحة كتاب الذكرى. وتظهر عناوين البريد الإلكتروني هنا للمشرفين فقط ولا تُنشر أبدًا. |
| ☐ | Tributes pending review | كلمات وفاء بانتظار المراجعة |
| ☐ | Type a screen, an action or a title | اكتب اسم شاشة أو إجراء أو عنوانًا |
| ☐ | Unchanged on :period | دون تغيير مقارنةً بـ:period |
| ☐ | Unique visitors | الزوار الفريدون |
| ☐ | Unique visitors per day | الزوار الفريدون في كل يوم |
| ☐ | Unique visitors per month | الزوار الفريدون في كل شهر |
| ☐ | Unknown | غير معروف |
| ☐ | unpinned a chat message | إلغاء تثبيت رسالة في المحادثة |
| ☐ | unpublished content | إلغاء نشر محتوى |
| ☐ | Unsaved changes | تغييرات غير محفوظة |
| ☐ | Up :percent% on :period | ارتفاع بنسبة :percent% مقارنةً بـ:period |
| ☐ | updated image details | تحديث بيانات صورة |
| ☐ | updated the site settings | تحديث إعدادات الموقع |
| ☐ | updated the wording of a page | تحديث نص صفحة |
| ☐ | Upload photos | رفع صور |
| ☐ | uploaded a file | رفع ملف |
| ☐ | Use digits, spaces, brackets, dots, dashes and a leading + only. | استخدم الأرقام والمسافات والأقواس والنقاط والشرطات وعلامة + في البداية فقط. |
| ☐ | Users | المستخدمون |
| ☐ | Users & roles | المستخدمون والأدوار |
| ☐ | Video | مرئي |
| ☐ | View site | عرض الموقع |
| ☐ | View the public site | عرض الموقع العام |
| ☐ | Views | المشاهدات |
| ☐ | Visitor analytics | إحصاءات الزوار |
| ☐ | Visitor analytics is switched off, so nothing is being recorded. It is first-party and cookieless, and is switched on in :settings once the client has approved it. | إحصاءات الزوار معطّلة، فلا يُسجَّل أي شيء. وهي إحصاءات خاصة بالموقع ولا تستخدم ملفات تعريف الارتباط، وتُفعَّل من :settings بعد موافقة العميل. |
| ☐ | Visitor analytics is switched off, so nothing is being recorded. It is first-party and cookieless, and is switched on in Site settings by an administrator once the client has approved it. | إحصاءات الزوار معطّلة، فلا يُسجَّل أي شيء. وهي إحصاءات خاصة بالموقع ولا تستخدم ملفات تعريف الارتباط، ويفعّلها أحد المديرين من إعدادات الموقع بعد موافقة العميل. |
| ☐ | Visitors | الزوار |
| ☐ | Visitors have an address to write to. | لدى الزوار عنوان يراسلونه. |
| ☐ | Visitors have left tributes. Read each one, then approve it for the public page or decline it. | ترك الزوار كلمات وفاء. اقرأ كل واحدة منها، ثم اعتمدها للصفحة العامة أو ارفضها. |
| ☐ | Visitors over time | الزوار عبر الزمن |
| ☐ | Visitors over time, as a table | الزوار عبر الزمن، في جدول |
| ☐ | waiting | بانتظار المراجعة |
| ☐ | Western (0–9) | الأرقام الغربية (0–9) |
| ☐ | What is not: no cookies, no IP addresses, no user agents, nothing that names a person. Visitors are told apart within one day by a hash whose salt is replaced every day. Signed-in staff, bots, and browsers that send Do Not Track or Global Privacy Control are never counted. Page views are deleted after :days days and daily totals after :months months. | ما لا يُسجَّل: لا ملفات تعريف ارتباط، ولا عناوين IP، ولا بيانات وكيل المستخدم، ولا أي شيء يحدد هوية شخص. ويُميَّز الزوار خلال اليوم الواحد ببصمة مشفّرة يتغير ملحها كل يوم. ولا يُحتسب أبدًا أعضاء الفريق المسجَّل دخولهم، ولا الروبوتات، ولا المتصفحات التي ترسل إشارة Do Not Track أو Global Privacy Control. وتُحذف مشاهدات الصفحات بعد :days يومًا، والمجاميع اليومية بعد :months شهرًا. |
| ☐ | What is recorded: the page, how long it was read (tab in view and the visitor active within :seconds seconds), how far it was scrolled, the referring site's name, the country, the device type and browser family, and a campaign source when a link carries one. | ما يُسجَّل: الصفحة، ومدة قراءتها (وعلامة التبويب ظاهرة والزائر نشط خلال :seconds ثانية)، ومدى التمرير فيها، واسم الموقع المُحيل، والبلد، ونوع الجهاز وفئة المتصفح، ومصدر الحملة إن تضمّنه الرابط. |
| ☐ | What still needs doing before the site is announced. Each line updates as the work is done. | ما بقي إنجازه قبل الإعلان عن الموقع. يتحدّث كل سطر تلقائيًا مع إنجاز العمل. |
| ☐ | When off, the paperclip is hidden and new uploads are refused; files already sent stay available. | عند التعطيل يُخفى زر الإرفاق وتُرفض المرفقات الجديدة، وتبقى الملفات المرسلة من قبل متاحة. |
| ☐ | Where visitors are | أماكن الزوار |
| ☐ | Who reads the site and what holds their attention. First-party and cookieless: no personal data is kept, and visitors are counted once per day. | من يقرأ الموقع وما الذي يشدّ انتباههم. إحصاءات خاصة بالموقع دون ملفات تعريف الارتباط: لا تُحفظ أي بيانات شخصية، ويُحتسب كل زائر مرة واحدة في اليوم. |
| ☐ | Write the tribute text. | اكتب نص كلمة الوفاء. |
| ☐ | yesterday | الأمس |
| ☐ | You cannot change your own role. Ask another administrator. | لا يمكنك تغيير دورك بنفسك. اطلب ذلك من مدير آخر. |
| ☐ | You cannot deactivate your own account. Ask another administrator. | لا يمكنك تعطيل حسابك بنفسك. اطلب ذلك من مدير آخر. |
| ☐ | {0} No countries\|{1} :count country\|[2,*] :count countries | {0} لا توجد بلدان\|{1} بلد واحد\|{2} بلدان\|[3,10] :count بلدان\|[11,*] :count بلدًا |
| ☐ | {0} World map of visitors by country, no countries shaded. The list of countries gives the figures.\|{1} World map of visitors by country, :count country shaded. The list of countries gives the figures.\|[2,*] World map of visitors by country, :count countries shaded. The list of countries gives the figures. | {0} خريطة العالم للزوار حسب البلد، ولا يوجد بلد مظلَّل. وتعرض قائمة البلدان الأرقام.\|{1} خريطة العالم للزوار حسب البلد، وفيها بلد واحد مظلَّل. وتعرض قائمة البلدان الأرقام.\|{2} خريطة العالم للزوار حسب البلد، وفيها بلدان مظلَّلان. وتعرض قائمة البلدان الأرقام.\|[3,10] خريطة العالم للزوار حسب البلد، وفيها :count بلدان مظلَّلة. وتعرض قائمة البلدان الأرقام.\|[11,*] خريطة العالم للزوار حسب البلد، وفيها :count بلدًا مظلَّلًا. وتعرض قائمة البلدان الأرقام. |
| ☐ | {1} :count :noun still holds sample text from the set-up.\|[2,*] :count :noun still hold sample text from the set-up. | {1} ما زال عنصر واحد في «:section» يتضمن نصًا تجريبيًا من مرحلة الإعداد.\|{2} ما زال عنصران في «:section» يتضمنان نصًا تجريبيًا من مرحلة الإعداد.\|[3,10] ما زالت :count عناصر في «:section» تتضمن نصًا تجريبيًا من مرحلة الإعداد.\|[11,*] ما زال :count عنصرًا في «:section» تتضمن نصًا تجريبيًا من مرحلة الإعداد. |
| ☐ | {1} :count already done\|[2,*] :count already done | {1} بند واحد مكتمل\|{2} بندان مكتملان\|[3,10] :count بنود مكتملة\|[11,*] :count بندًا مكتملًا |
| ☐ | {1} :count item is shown in English on the Arabic pages until it is translated.\|[2,*] :count items are shown in English on the Arabic pages until they are translated. | {1} عنصر واحد يظهر بالإنجليزية في الصفحات العربية إلى أن يُترجم.\|{2} عنصران يظهران بالإنجليزية في الصفحات العربية إلى أن يُترجما.\|[3,10] :count عناصر تظهر بالإنجليزية في الصفحات العربية إلى أن تُترجم.\|[11,*] :count عنصرًا تظهر بالإنجليزية في الصفحات العربية إلى أن تُترجم. |
| ☐ | {1} :count tribute approved.\|[2,*] :count tributes approved. | {1} اعتُمدت كلمة وفاء واحدة.\|{2} اعتُمدت كلمتا وفاء.\|[3,10] اعتُمدت :count كلمات وفاء.\|[11,*] اعتُمدت :count كلمة وفاء. |
| ☐ | {1} :count tribute declined.\|[2,*] :count tributes declined. | {1} رُفضت كلمة وفاء واحدة.\|{2} رُفضت كلمتا وفاء.\|[3,10] رُفضت :count كلمات وفاء.\|[11,*] رُفضت :count كلمة وفاء. |
| ☐ | {1} :count view\|[2,*] :count views | {1} مشاهدة واحدة\|{2} مشاهدتان\|[3,10] :count مشاهدات\|[11,*] :count مشاهدة |
| ☐ | {1} :count visitor\|[2,*] :count visitors | {1} زائر واحد\|{2} زائران\|[3,10] :count زوار\|[11,*] :count زائرًا |

## Admin › Pages editor, durations and other shared strings (278)

| ✓ | English | Arabic |
|---|---|---|
| ☐ | :decade | :decade |
| ☐ | :field (:item) | :field (:item) |
| ☐ | :field destination | وجهة :field |
| ☐ | :field label | تسمية :field |
| ☐ | :field web address | عنوان ويب :field |
| ☐ | :hoursh :minutesm | :hours س :minutes د |
| ☐ | :item: :field | :item: :field |
| ☐ | :minutesm :secondss | :minutes د :seconds ث |
| ☐ | :secondss | :seconds ث |
| ☐ | :types, up to :size. | :types، بحجم أقصاه :size. |
| ☐ | A short line in his voice under the tagline. Leave empty to show none. | سطر قصير بلسانه تحت العبارة التعريفية. اتركه فارغًا لإخفائه. |
| ☐ | Add :item | إضافة :item |
| ☐ | Album page: line while it has no photographs | صفحة الألبوم: السطر المعروض حين لا يضم صورًا |
| ☐ | Album page: link back | صفحة الألبوم: رابط العودة |
| ☐ | Also its name in breadcrumbs, search results and the cards that lead here. | وهو أيضًا اسمها في مسار التنقل ونتائج البحث والبطاقات المؤدية إليها. |
| ☐ | Also on the thank-you page. | وأيضًا في صفحة الشكر. |
| ☐ | Also repeated at the foot of the book. | ويتكرر أيضًا في ذيل السجل. |
| ☐ | Also shown in the chat. | يُعرض أيضًا في المحادثة. |
| ☐ | Also the name of the footer link. | وهو أيضًا اسم الرابط في التذييل. |
| ☐ | alt text | النص البديل |
| ☐ | Applying | تقديم الطلب |
| ☐ | Approved members: line about the chat | الأعضاء المقبولون: سطر عن المحادثة |
| ☐ | Approved: heading | مقبول: العنوان |
| ☐ | Approved: message | مقبول: الرسالة |
| ☐ | Arabic :field | :field (بالعربية) |
| ☐ | Arabic alt text | النص البديل بالعربية |
| ☐ | Arabic caption | التعليق بالعربية |
| ☐ | Arabic label: :block | التسمية بالعربية: :block |
| ☐ | Arabic place | المكان بالعربية |
| ☐ | Arabic search description | وصف البحث بالعربية |
| ☐ | Arabic search title | عنوان البحث بالعربية |
| ☐ | Arabic: :block | بالعربية: :block |
| ☐ | Around the listing | حول القائمة |
| ☐ | Button home | زر الرئيسية |
| ☐ | Button: Back to tributes | زر: العودة إلى كلمات الوفاء |
| ☐ | Button: Change something | زر: تعديل شيء ما |
| ☐ | Button: Home | زر: الرئيسية |
| ☐ | Button: Leave a tribute | زر: اترك كلمة وفاء |
| ☐ | Button: Open the chat | زر: افتح المحادثة |
| ☐ | Button: Preview your tribute | زر: معاينة كلمتك |
| ☐ | Button: Read his story | زر: اقرأ قصته |
| ☐ | Button: Refine the catalogue (small screens) | زر: تصفية الفهرس (الشاشات الصغيرة) |
| ☐ | Button: Send tribute | زر: إرسال كلمة الوفاء |
| ☐ | Button: Your account | زر: حسابك |
| ☐ | caption | التعليق |
| ☐ | Card image | صورة البطاقة |
| ☐ | Card line: About | سطر البطاقة: عن الأستاذ |
| ☐ | Card line: Publications | سطر البطاقة: المنشورات |
| ☐ | Card line: Tributes | سطر البطاقة: كلمات الوفاء |
| ☐ | Changes published. They are on the public site now. | نُشرت التغييرات، وهي الآن على الموقع العام. |
| ☐ | Chapter introduction | مقدمة الفصول |
| ☐ | Chapter names and order, menu lines, footer links and the lines every page shares. | أسماء الفصول وترتيبها، وأسطر القوائم، وروابط التذييل، والأسطر المشتركة بين جميع الصفحات. |
| ☐ | Chapter title | عنوان الفصول |
| ☐ | Checkbox: agree to the rules | مربع اختيار: الموافقة على القواعد |
| ☐ | Checkbox: consent to publish | مربع اختيار: الموافقة على النشر |
| ☐ | Checkbox: understand the privacy note | مربع اختيار: فهم ملاحظة الخصوصية |
| ☐ | Closing line under the seal | السطر الختامي تحت الختم |
| ☐ | Conduct rules | آداب المشاركة |
| ☐ | Copyright line | سطر حقوق النشر |
| ☐ | Count line when the filters match nothing | سطر العدد حين لا تطابق عوامل التصفية شيئًا |
| ☐ | Crop focus | مركز القص |
| ☐ | Default share image | صورة المشاركة الافتراضية |
| ☐ | Describe the image for people who cannot see it. | صِف الصورة لمن لا يستطيع رؤيتها. |
| ☐ | Describe the image in Arabic for people who cannot see it. Left empty, the English alt text is read. | صِف الصورة بالعربية لمن لا يستطيع رؤيتها. إذا تُرك فارغًا، يُقرأ النص البديل الإنجليزي. |
| ☐ | Discard the draft | تجاهل المسودة |
| ☐ | Domain in the footer | النطاق في التذييل |
| ☐ | Draft saved. Visitors see the original wording until the page is published. | حُفظت المسودة. يرى الزوار النص الأصلي إلى أن تُنشر الصفحة. |
| ☐ | Draft saved. Visitors still see the published wording until you publish the changes. | حُفظت المسودة. يرى الزوار النص المنشور إلى أن تنشر التغييرات. |
| ☐ | Each link goes to a page chosen from the list, so none can break. | كل رابط يؤدي إلى صفحة مختارة من القائمة، فلا يمكن أن يتعطل أي منها. |
| ☐ | Each section's name in the menus and above its page heading, and its line in the chapter panels. Their order sets the chapter numerals, so it is fixed. | اسم كل قسم في القوائم وفوق عنوان صفحته، وسطره في لوحات الفصول. ترتيبها يحدد أرقام الفصول، لذا فهو ثابت. |
| ☐ | Edit page: :page | تحرير الصفحة: :page |
| ☐ | Enter a full web address beginning with https://. | أدخل عنوان ويب كاملًا يبدأ بـ ‎https://‎. |
| ☐ | Error 403: no permission | الخطأ 403: لا صلاحية |
| ☐ | Error 404: page not found | الخطأ 404: الصفحة غير موجودة |
| ☐ | Error 419: form expired | الخطأ 419: انتهت صلاحية النموذج |
| ☐ | Error 500: something broke | الخطأ 500: حدث عطل |
| ☐ | Error 503: site down for maintenance | الخطأ 503: الموقع متوقف للصيانة |
| ☐ | Error :code | الخطأ :code |
| ☐ | Error page shown | صفحة الخطأ المعروضة |
| ☐ | Error pages | صفحات الأخطاء |
| ☐ | Essay page: button back | صفحة المقالة: زر العودة |
| ☐ | Every photograph needs a caption. | كل صورة تحتاج إلى تعليق. |
| ☐ | Every photograph needs the place it was taken. | كل صورة تحتاج إلى المكان الذي التُقطت فيه. |
| ☐ | Every photograph needs the year it was taken. | كل صورة تحتاج إلى السنة التي التُقطت فيها. |
| ☐ | Field | الحقل |
| ☐ | Field labels and hints | تسميات الحقول وإرشاداتها |
| ☐ | Filter: All topics | تصفية: جميع الموضوعات |
| ☐ | Footer | التذييل |
| ☐ | Footer closing line | السطر الختامي في التذييل |
| ☐ | Footer closing line on the home page | السطر الختامي في تذييل الصفحة الرئيسية |
| ☐ | Footer links | روابط التذييل |
| ☐ | for :block | لـ :block |
| ☐ | For example "Error 404". | مثل: «الخطأ 404». |
| ☐ | Form heading | عنوان النموذج |
| ☐ | Form introduction | مقدمة النموذج |
| ☐ | From the left (%) | من اليسار (%) |
| ☐ | From the top (%) | من الأعلى (%) |
| ☐ | Goes to | يؤدي إلى |
| ☐ | Header | الترويسة |
| ☐ | Header & footer | الترويسة والتذييل |
| ☐ | Header button: Leave a tribute | زر الترويسة: اترك كلمة وفاء |
| ☐ | Heading: Contact | عنوان: التواصل |
| ☐ | Heading: Expertise | عنوان: مجالات الخبرة |
| ☐ | Heading: Key facts | عنوان: حقائق رئيسية |
| ☐ | Headings and lists are allowed. The numbers stay in step with the analytics settings through the placeholders. | يُسمح بالعناوين والقوائم. وتبقى الأرقام متوافقة مع إعدادات الإحصاءات عبر العناصر النائبة. |
| ☐ | Hero | الواجهة الرئيسية |
| ☐ | Hero epigraph | استهلال الواجهة |
| ☐ | Hero heading, first line | عنوان الواجهة، السطر الأول |
| ☐ | Hero heading, second line | عنوان الواجهة، السطر الثاني |
| ☐ | Hero image | صورة الواجهة |
| ☐ | Hero label | تسمية الواجهة |
| ☐ | Hero tagline | العبارة التعريفية في الواجهة |
| ☐ | Hint | الإرشاد |
| ☐ | his name | اسمه |
| ☐ | how many days page views are kept | عدد الأيام التي تُحفظ فيها مشاهدات الصفحات |
| ☐ | how many idle seconds end a reading | عدد ثواني الخمول التي تنهي القراءة |
| ☐ | how many months daily totals are kept | عدد الأشهر التي تُحفظ فيها المجاميع اليومية |
| ☐ | How the site counts its readers (shown while visitor analytics is on). | كيف يحصي الموقع قرّاءه (تُعرض ما دامت إحصاءات الزوار مفعّلة). |
| ☐ | Introduction | المقدمة |
| ☐ | Introduction to the form | مقدمة النموذج |
| ☐ | Item | العنصر |
| ☐ | Item page: button back | صفحة العنصر: زر العودة |
| ☐ | Its name in breadcrumbs, menus, search results and the running head. | اسمها في مسار التنقل والقوائم ونتائج البحث والترويسة. |
| ☐ | Its name in breadcrumbs, search results and the chat. | اسمها في مسار التنقل ونتائج البحث والمحادثة. |
| ☐ | Label | التسمية |
| ☐ | Label above the book | التسمية فوق السجل |
| ☐ | Label above the chapters | التسمية فوق الفصول |
| ☐ | Label above the featured tribute | التسمية فوق كلمة الوفاء المختارة |
| ☐ | Label above the footer links | التسمية فوق روابط التذييل |
| ☐ | Label above the list of sections | التسمية فوق قائمة الأقسام |
| ☐ | Label before the code | التسمية قبل الرمز |
| ☐ | Label: All recordings | تسمية: جميع التسجيلات |
| ☐ | Label: Featured recording | تسمية: تسجيل مميز |
| ☐ | Label: Last updated | تسمية: آخر تحديث |
| ☐ | Label: Selected works | تسمية: أعمال مختارة |
| ☐ | Labels above the featured items | التسميات فوق العناصر المختارة |
| ☐ | Language of :block | لغة :block |
| ☐ | Last saved | آخر حفظ |
| ☐ | Leave empty to show none. | اتركه فارغًا لإخفائه. |
| ☐ | Leave the title empty to show his name alone, and the description empty to use the hero tagline. | اترك العنوان فارغًا ليظهر اسمه وحده، والوصف فارغًا لاستخدام العبارة التعريفية في الواجهة. |
| ☐ | Leave the title empty to use the page heading, and the description empty to use the intro. | اترك العنوان فارغًا لاستخدام عنوان الصفحة، والوصف فارغًا لاستخدام المقدمة. |
| ☐ | Leave the title empty to use the page heading, and the description empty to use the short biography. | اترك العنوان فارغًا لاستخدام عنوان الصفحة، والوصف فارغًا لاستخدام النبذة القصيرة. |
| ☐ | Leave the title empty to use the page name, the description empty to use the introduction, and the image empty to use the tributes share card. | اترك العنوان فارغًا لاستخدام اسم الصفحة، والوصف فارغًا لاستخدام المقدمة، والصورة فارغة لاستخدام بطاقة مشاركة كلمات الوفاء. |
| ☐ | Left empty, Arabic pages use the Arabic introduction. | إذا تُرك فارغًا، تستخدم الصفحات العربية المقدمة العربية. |
| ☐ | Left empty, Arabic pages use the Arabic page heading. | إذا تُرك فارغًا، تستخدم الصفحات العربية عنوان الصفحة العربي. |
| ☐ | Left empty, the original wording is kept. | إذا تُرك فارغًا، يبقى النص الأصلي. |
| ☐ | Line | السطر |
| ☐ | Line above the header | السطر فوق الترويسة |
| ☐ | Line in its panel | السطر في لوحته |
| ☐ | Line in the menu | السطر في القائمة |
| ☐ | Line when a relationship has none | السطر المعروض حين لا توجد كلمات لصلة ما |
| ☐ | Line when a topic has nothing | السطر المعروض حين يخلو موضوع من المحتوى |
| ☐ | Line when the filters match nothing | السطر المعروض حين لا تطابق عوامل التصفية شيئًا |
| ☐ | Line while no tribute is published | السطر المعروض حين لا توجد كلمة وفاء منشورة |
| ☐ | Line while nothing is catalogued | السطر المعروض ما دام الفهرس فارغًا |
| ☐ | Line while nothing is published | السطر المعروض ما دام لا يوجد شيء منشور |
| ☐ | Line while the biography is not published | السطر المعروض ما دامت السيرة غير منشورة |
| ☐ | Line while the CV is empty | السطر المعروض ما دامت السيرة الذاتية فارغة |
| ☐ | Line while there are no albums | السطر المعروض حين لا توجد ألبومات |
| ☐ | Line while there are no awards | السطر المعروض حين لا توجد جوائز |
| ☐ | Line while there are no chapters | السطر المعروض حين لا توجد فصول |
| ☐ | Line while there are no figures | السطر المعروض حين لا توجد أرقام |
| ☐ | Line while there are no materials | السطر المعروض حين لا توجد مواد |
| ☐ | Line while there are no recordings | السطر المعروض حين لا توجد تسجيلات |
| ☐ | Link: Read more | رابط: اقرأ المزيد |
| ☐ | Link: Share card | رابط: بطاقة المشاركة |
| ☐ | Link: Visit | رابط: زيارة |
| ☐ | Members-only note | ملاحظة «للأعضاء فقط» |
| ☐ | Members-only note: link to the community | ملاحظة «للأعضاء فقط»: رابط المجتمع العلمي |
| ☐ | Members-only note: words after the link | ملاحظة «للأعضاء فقط»: الكلمات بعد الرابط |
| ☐ | Membership ended: heading | انتهت العضوية: العنوان |
| ☐ | Membership ended: message | انتهت العضوية: الرسالة |
| ☐ | Menu extras | إضافات القائمة |
| ☐ | Menu links | روابط القائمة |
| ☐ | Message after applying | الرسالة بعد تقديم الطلب |
| ☐ | Messages about an application | الرسائل المتعلقة بطلب الانضمام |
| ☐ | Name in the header | الاسم في الترويسة |
| ☐ | Not accepted: heading | غير مقبول: العنوان |
| ☐ | Not accepted: message | غير مقبول: الرسالة |
| ☐ | Notice printed instead of the CV | الملاحظة المطبوعة بدلًا من السيرة الذاتية |
| ☐ | Numbered in this order on the page and in the chat. Applicants agree to them when they apply. | تُرقَّم بهذا الترتيب في الصفحة وفي المحادثة، ويوافق عليها المتقدمون عند تقديم طلباتهم. |
| ☐ | One or two sentences under the heading. Also used in menus, cards and as the search description. | جملة أو جملتان تحت العنوان، وتُستخدم أيضًا في القوائم والبطاقات ووصفًا للبحث. |
| ☐ | One or two sentences under the link in search results and when the page is shared. | جملة أو جملتان تحت الرابط في نتائج البحث وعند مشاركة الصفحة. |
| ☐ | One sentence under his name. It is also the description search engines show. | جملة واحدة تحت اسمه، وهي أيضًا الوصف الذي تعرضه محركات البحث. |
| ☐ | Only for a page outside this site, beginning with https://. It replaces the page chosen above. | لصفحة خارج هذا الموقع فقط، تبدأ بـ ‎https://‎. ويحل محل الصفحة المختارة أعلاه. |
| ☐ | Only seen on paper: the CV itself is never printed. | لا تظهر إلا على الورق: السيرة الذاتية نفسها لا تُطبع أبدًا. |
| ☐ | Opening | الافتتاح |
| ☐ | Optional. Left empty, Arabic pages show the wording in the placeholder. | اختياري. إذا تُرك فارغًا، تعرض الصفحات العربية النص الظاهر في الحقل. |
| ☐ | Optional. Shown as a narrow duotone strip on the cards that lead here from the home page and About. | اختيارية. تُعرض شريطًا ضيقًا ثنائي اللون على البطاقات المؤدية إلى هنا من الصفحة الرئيسية وصفحة «عن الأستاذ». |
| ☐ | Optional. Up to :max characters. Left empty, Arabic pages show the original wording in Arabic. | اختياري. حتى :max حرفًا. إذا تُرك فارغًا، تعرض الصفحات العربية النص الأصلي بالعربية. |
| ☐ | Or a web address | أو عنوان ويب |
| ☐ | original wording | النص الأصلي |
| ☐ | page | الصفحة |
| ☐ | Page heading | عنوان الصفحة |
| ☐ | Page moved back to draft. Visitors see the original wording. | أُعيدت الصفحة إلى المسودة، ويرى الزوار النص الأصلي. |
| ☐ | Page name | اسم الصفحة |
| ☐ | Page published. Visitors see these words now. | نُشرت الصفحة، ويرى الزوار هذا النص الآن. |
| ☐ | Phone | الهاتف |
| ☐ | place | المكان |
| ☐ | Placeholders: :list. | العناصر النائبة: :list. |
| ☐ | Preview introduction | مقدمة المعاينة |
| ☐ | Preview of :page with the draft | معاينة :page مع المسودة |
| ☐ | Preview of the page | معاينة الصفحة |
| ☐ | Preview step | خطوة المعاينة |
| ☐ | Preview width | عرض المعاينة |
| ☐ | Preview with your draft | معاينة مع مسودتك |
| ☐ | Privacy note | ملاحظة الخصوصية |
| ☐ | Publish changes | نشر التغييرات |
| ☐ | Recording page: link back | صفحة التسجيل: رابط العودة |
| ☐ | Recording page: More from this event | صفحة التسجيل: المزيد من هذه المناسبة |
| ☐ | Reload | إعادة التحميل |
| ☐ | Rule | قاعدة |
| ☐ | Scroll cue | إشارة التمرير |
| ☐ | Search & sharing | البحث والمشاركة |
| ☐ | Search description | وصف البحث |
| ☐ | search description | وصف البحث |
| ☐ | Search field label | تسمية حقل البحث |
| ☐ | Search title | عنوان البحث |
| ☐ | search title | عنوان البحث |
| ☐ | Section names and menu lines | أسماء الأقسام وأسطر القوائم |
| ☐ | Set in the quote style. | يُعرض بأسلوب الاقتباس. |
| ☐ | Set large across the hero: keep it to his name. | يُعرض بخط كبير في الواجهة: اقتصر فيه على اسمه. |
| ☐ | Share image | صورة المشاركة |
| ☐ | Shared by every error page | مشترك بين جميع صفحات الأخطاء |
| ☐ | Shown as the link in search results and on the browser tab, before the site name. | يظهر رابطًا في نتائج البحث وعلى علامة تبويب المتصفح، قبل اسم الموقع. |
| ☐ | Shown beside his name. Leave empty to use the portrait from About & profile. | تُعرض بجوار اسمه. اتركها فارغة لاستخدام الصورة الشخصية من صفحة «عن الأستاذ والملف الشخصي». |
| ☐ | Shown to visitors who are not members. | تُعرض للزوار من غير الأعضاء. |
| ☐ | Shown when the page is shared on social media; 1200 × 630 pixels works best. | تُعرض عند مشاركة الصفحة على وسائل التواصل؛ والأفضل أن تكون 1200 × 630 بكسل. |
| ☐ | Side-by-side preview | معاينة جنبًا إلى جنب |
| ☐ | Signed in, not applied: heading | مسجّل الدخول دون طلب: العنوان |
| ☐ | Signed in, not applied: message | مسجّل الدخول دون طلب: الرسالة |
| ☐ | Some changes are saved as a draft and not yet on the site. Publish changes to put them live. | بعض التغييرات محفوظة مسودةً ولم تظهر بعد على الموقع. انشر التغييرات لتظهر للزوار. |
| ☐ | Suspended: heading | موقوف: العنوان |
| ☐ | Suspended: message | موقوف: الرسالة |
| ☐ | Text | النص |
| ☐ | Thank-you message | رسالة الشكر |
| ☐ | Thank-you page | صفحة الشكر |
| ☐ | the address of the CV page | عنوان صفحة السيرة الذاتية |
| ☐ | The book of remembrance, the tribute form and the thank-you page. | سجلّ الذكرى، ونموذج كلمة الوفاء، وصفحة الشكر. |
| ☐ | the current year | السنة الحالية |
| ☐ | The destination is the one chosen in the English tab. | الوجهة هي المختارة في تبويب الإنجليزية. |
| ☐ | The five words across the header, in this order. Keep each to one short word: the header has room for little more. | الكلمات الخمس في الترويسة بهذا الترتيب. اجعل كلًّا منها كلمة قصيرة واحدة، فالترويسة لا تتسع لأكثر من ذلك. |
| ☐ | The fixed words and images of each public page: headings, introductions, buttons, empty-page lines, the community rules, the error pages, the header and footer, and how each page appears in search and when shared. Records such as publications and tributes have their own screens. | النصوص والصور الثابتة لكل صفحة عامة: العناوين، والمقدمات، والأزرار، وأسطر الصفحات الفارغة، وآداب المجتمع العلمي، وصفحات الأخطاء، والترويسة والتذييل، وطريقة ظهور كل صفحة في البحث وعند مشاركتها. أما السجلات مثل المنشورات وكلمات الوفاء فلها شاشاتها الخاصة. |
| ☐ | The heading and intro of the roll of honour. | عنوان سجل التكريم ومقدمته. |
| ☐ | The heading and line of each error page. | عنوان كل صفحة خطأ وسطرها. |
| ☐ | The heading, epigraph and small headings around the biography. | العنوان والاستهلال والعناوين الفرعية حول السيرة. |
| ☐ | The heading, intro and album labels. | العنوان والمقدمة وتسميات الألبومات. |
| ☐ | The heading, intro and catalogue labels. | العنوان والمقدمة وتسميات الفهرس. |
| ☐ | The heading, intro and labels of the CV ledger. | عنوان سجل السيرة الذاتية ومقدمته وتسمياته. |
| ☐ | The heading, intro and link labels. | العنوان والمقدمة وتسميات الروابط. |
| ☐ | The heading, intro and recording labels. | العنوان والمقدمة وتسميات التسجيلات. |
| ☐ | The heading, intro and the members-only note. | العنوان والمقدمة وملاحظة «للأعضاء فقط». |
| ☐ | The heading, intro and topic labels. | العنوان والمقدمة وتسميات الموضوعات. |
| ☐ | The hero, the chapter titles, the story cards and the closing line. | الواجهة الرئيسية وعناوين الفصول وبطاقات القصة والسطر الختامي. |
| ☐ | The home page already ends with the closing line above its seal. | تنتهي الصفحة الرئيسية أصلًا بالسطر الختامي فوق ختمها. |
| ☐ | The image every page shares on social media unless its own is set. Leave empty to use the branded card. | الصورة التي تشاركها كل صفحة على وسائل التواصل ما لم تُحدَّد لها صورة خاصة. اتركها فارغة لاستخدام البطاقة ذات الهوية البصرية. |
| ☐ | The items, their order and destinations follow the English tab. Fill in the Arabic words; an empty field shows the placeholder. | العناصر وترتيبها ووجهاتها تتبع تبويب الإنجليزية. املأ الكلمات العربية؛ والحقل الفارغ يعرض النص الظاهر فيه. |
| ☐ | The one word at the foot of the hero. | الكلمة الوحيدة أسفل الواجهة. |
| ☐ | The preview shows what is saved. Changes appear here a moment after each autosave. To read or use the page itself, open Preview in the save bar: it opens the same page in a new tab. | تعرض المعاينة ما حُفظ، وتظهر التغييرات هنا بعد لحظات من كل حفظ تلقائي. لقراءة الصفحة نفسها أو استخدامها، افتح «معاينة» في شريط الحفظ: ستُفتح الصفحة ذاتها في علامة تبويب جديدة. |
| ☐ | The privacy text | نص الخصوصية |
| ☐ | the relationship, for example students | الصلة، مثل: الطلاب |
| ☐ | The small label above his name. | التسمية الصغيرة فوق اسمه. |
| ☐ | The tribute form | نموذج كلمة الوفاء |
| ☐ | The year is the English tab’s. | السنة هي المحددة في تبويب الإنجليزية. |
| ☐ | This page is a draft: visitors see its original wording. Publish to show these words. | هذه الصفحة مسودة: يرى الزوار نصها الأصلي. انشرها لتظهر هذه النصوص. |
| ☐ | Under review: heading | قيد المراجعة: العنوان |
| ☐ | Under review: message | قيد المراجعة: الرسالة |
| ☐ | Under the heading. Also used in menus, cards and as the search description. | تحت العنوان، ويُستخدم أيضًا في القوائم والبطاقات ووصفًا للبحث. |
| ☐ | Unpublished changes | تغييرات غير منشورة |
| ☐ | Unpublished changes discarded. The form shows the published wording. | أُلغيت التغييرات غير المنشورة، ويعرض النموذج النص المنشور. |
| ☐ | Up to :max characters. | حتى :max حرفًا. |
| ☐ | Up to :max characters; bold, italic and links only. | حتى :max حرفًا؛ الخط العريض والمائل والروابط فقط. |
| ☐ | Use the original wording | استخدم النص الأصلي |
| ☐ | Used in menus, cards and as the search description. | يُستخدم في القوائم والبطاقات ووصفًا للبحث. |
| ☐ | What the community is, who may join, the conduct rules, the privacy note and every application message. | تعريف المجتمع العلمي، ومن يحق له الانضمام، وآداب المشاركة، وملاحظة الخصوصية، وجميع رسائل طلبات الانضمام. |
| ☐ | Where the frame centres when it crops the image. Click the point that must stay in view, or set it in percent. | النقطة التي يتمركز حولها الإطار عند قص الصورة. انقر النقطة التي يجب أن تبقى ظاهرة، أو حدّدها بالنسبة المئوية. |
| ☐ | year | السنة |
| ☐ | You change the words and images here; the layout, typography and colours stay as designed. | تعدّل هنا النصوص والصور؛ أما التخطيط والخطوط والألوان فتبقى كما صُممت. |

## Language switcher and fallback note (3)

| ✓ | English | Arabic |
|---|---|---|
| ☐ | Professor Mohamed Osman Khidir | الأستاذ الدكتور محمد عثمان خضر |
| ☐ | Read this page in | اقرأ هذه الصفحة باللغة |
| ☐ | This content is available in English only. | هذا المحتوى متاح بالإنجليزية فقط |

## Lightbox (1)

| ✓ | English | Arabic |
|---|---|---|
| ☐ | :current of :total | :current من :total |

## lang/ar/auth.php (3)

| ✓ | Key | English | Arabic |
|---|---|---|---|
| ☐ | `failed` | These credentials do not match our records. | بيانات الدخول هذه لا تطابق سجلاتنا. |
| ☐ | `password` | The provided password is incorrect. | كلمة المرور المدخلة غير صحيحة. |
| ☐ | `throttle` | Too many login attempts. Please try again in :seconds seconds. | محاولات تسجيل دخول كثيرة. يُرجى المحاولة مرة أخرى بعد :seconds ثانية. |

## lang/ar/chat.php (2)

| ✓ | Key | English | Arabic |
|---|---|---|---|
| ☐ | `tab_about` | About | نبذة |
| ☐ | `action_report` | Report | إبلاغ |

## lang/ar/pagination.php (2)

| ✓ | Key | English | Arabic |
|---|---|---|---|
| ☐ | `previous` | &laquo; Previous | &raquo; السابق |
| ☐ | `next` | Next &raquo; | التالي &laquo; |

## lang/ar/passwords.php (5)

| ✓ | Key | English | Arabic |
|---|---|---|---|
| ☐ | `reset` | Your password has been reset. | أُعيد تعيين كلمة المرور الخاصة بك. |
| ☐ | `sent` | We have emailed your password reset link. | أرسلنا إليك رابط إعادة تعيين كلمة المرور عبر البريد الإلكتروني. |
| ☐ | `throttled` | Please wait before retrying. | يُرجى الانتظار قبل المحاولة مرة أخرى. |
| ☐ | `token` | This password reset token is invalid. | رمز إعادة تعيين كلمة المرور هذا غير صالح. |
| ☐ | `user` | We can't find a user with that email address. | لم نعثر على مستخدم بهذا البريد الإلكتروني. |

## lang/ar/validation.php (306)

| ✓ | Key | English | Arabic |
|---|---|---|---|
| ☐ | `accepted` | Tick this box to continue. | ضع علامة في هذا المربع للمتابعة. |
| ☐ | `accepted_if` | The :attribute field must be accepted when :other is :value. | يجب قبول :attribute عندما يكون :other هو :value. |
| ☐ | `active_url` | Enter a web address that exists, starting with https://. | أدخل عنوان ويب موجودًا يبدأ بـ https://‎. |
| ☐ | `after` | The :attribute field must be a date after :date. | يجب أن يكون :attribute تاريخًا بعد :date. |
| ☐ | `after_or_equal` | Enter a date on or after :date. | أدخل تاريخًا في :date أو بعده. |
| ☐ | `alpha` | The :attribute field must only contain letters. | يجب أن يحتوي :attribute على أحرف فقط. |
| ☐ | `alpha_dash` | The :attribute field must only contain letters, numbers, dashes, and underscores. | يجب أن يحتوي :attribute على أحرف وأرقام وشرطات وشرطات سفلية فقط. |
| ☐ | `alpha_num` | The :attribute field must only contain letters and numbers. | يجب أن يحتوي :attribute على أحرف وأرقام فقط. |
| ☐ | `any_of` | The :attribute field is invalid. | قيمة :attribute غير صالحة. |
| ☐ | `array` | The :attribute field must be an array. | يجب أن يكون :attribute قائمة. |
| ☐ | `array_keys` | The :attribute field must only contain the following keys: :values. | يجب ألا يحتوي :attribute إلا على المفاتيح التالية: :values. |
| ☐ | `ascii` | The :attribute field must only contain single-byte alphanumeric characters and symbols. | يجب ألا يحتوي :attribute إلا على أحرف ورموز لاتينية أحادية البايت. |
| ☐ | `base64` | The :attribute field must be a valid Base64 string. | يجب أن يكون :attribute نصًا صالحًا بترميز Base64. |
| ☐ | `before` | The :attribute field must be a date before :date. | يجب أن يكون :attribute تاريخًا قبل :date. |
| ☐ | `before_or_equal` | The :attribute field must be a date before or equal to :date. | يجب أن يكون :attribute تاريخًا في :date أو قبله. |
| ☐ | `between.array` | Choose between :min and :max items for :attribute. | اختر ما بين :min و:max عناصر لـ :attribute. |
| ☐ | `between.file` | Choose a file between :min and :max kilobytes. | اختر ملفًا يتراوح حجمه بين :min و:max كيلوبايت. |
| ☐ | `between.numeric` | Enter a number between :min and :max. | أدخل رقمًا بين :min و:max. |
| ☐ | `between.string` | Use between :min and :max characters for :attribute. | استخدم ما بين :min و:max حرفًا في :attribute. |
| ☐ | `boolean` | Choose yes or no for :attribute. | اختر نعم أو لا في :attribute. |
| ☐ | `can` | The :attribute field contains an unauthorized value. | يحتوي :attribute على قيمة غير مصرّح بها. |
| ☐ | `confirmed` | Enter the same :attribute in both fields. | أدخل :attribute نفسها في الحقلين. |
| ☐ | `contains` | The :attribute field is missing a required value. | ينقص :attribute قيمةٌ مطلوبة. |
| ☐ | `current_password` | Enter your current password as you last set it. | أدخل كلمة المرور الحالية كما عيّنتها آخر مرة. |
| ☐ | `date` | Enter :attribute as a date, for example 14 March 1984. | أدخل :attribute بصيغة تاريخ، مثل 14 مارس 1984. |
| ☐ | `date_equals` | The :attribute field must be a date equal to :date. | يجب أن يكون :attribute تاريخًا مساويًا لـ :date. |
| ☐ | `date_format` | The :attribute field must match the format :format. | يجب أن يطابق :attribute الصيغة :format. |
| ☐ | `decimal` | The :attribute field must have :decimal decimal places. | يجب أن يحتوي :attribute على :decimal منازل عشرية. |
| ☐ | `declined` | The :attribute field must be declined. | يجب رفض :attribute. |
| ☐ | `declined_if` | The :attribute field must be declined when :other is :value. | يجب رفض :attribute عندما يكون :other هو :value. |
| ☐ | `different` | The :attribute field and :other must be different. | يجب أن يختلف :attribute عن :other. |
| ☐ | `digits` | Enter :attribute as :digits digits. | أدخل :attribute في :digits أرقام. |
| ☐ | `digits_between` | The :attribute field must be between :min and :max digits. | يجب أن يتكون :attribute من :min إلى :max أرقام. |
| ☐ | `dimensions` | The :attribute field has invalid image dimensions. | أبعاد الصورة في :attribute غير صالحة. |
| ☐ | `distinct` | The :attribute field has a duplicate value. | يحتوي :attribute على قيمة مكررة. |
| ☐ | `doesnt_contain` | The :attribute field must not contain any of the following: :values. | يجب ألا يحتوي :attribute على أي مما يلي: :values. |
| ☐ | `doesnt_end_with` | The :attribute field must not end with one of the following: :values. | يجب ألا ينتهي :attribute بأي مما يلي: :values. |
| ☐ | `doesnt_start_with` | The :attribute field must not start with one of the following: :values. | يجب ألا يبدأ :attribute بأي مما يلي: :values. |
| ☐ | `email` | Enter an email address in the form name@example.com. | أدخل عنوان بريد إلكتروني بالصيغة name@example.com. |
| ☐ | `encoding` | The :attribute field must be encoded in :encoding. | يجب أن يكون :attribute بترميز :encoding. |
| ☐ | `ends_with` | The :attribute field must end with one of the following: :values. | يجب أن ينتهي :attribute بأحد ما يلي: :values. |
| ☐ | `enum` | Choose one of the options for :attribute. | اختر أحد الخيارات المتاحة لـ :attribute. |
| ☐ | `exists` | Choose one of the options for :attribute. | اختر أحد الخيارات المتاحة لـ :attribute. |
| ☐ | `extensions` | The :attribute field must have one of the following extensions: :values. | يجب أن يكون امتداد :attribute أحد ما يلي: :values. |
| ☐ | `file` | The :attribute field must be a file. | يجب أن يكون :attribute ملفًا. |
| ☐ | `filled` | The :attribute field must have a value. | يجب أن يحتوي :attribute على قيمة. |
| ☐ | `gt.array` | The :attribute field must have more than :value items. | يجب أن يحتوي :attribute على أكثر من :value عناصر. |
| ☐ | `gt.file` | The :attribute field must be greater than :value kilobytes. | يجب أن يزيد حجم :attribute على :value كيلوبايت. |
| ☐ | `gt.numeric` | The :attribute field must be greater than :value. | يجب أن يكون :attribute أكبر من :value. |
| ☐ | `gt.string` | The :attribute field must be greater than :value characters. | يجب أن يزيد :attribute على :value حرفًا. |
| ☐ | `gte.array` | The :attribute field must have :value items or more. | يجب أن يحتوي :attribute على :value عناصر أو أكثر. |
| ☐ | `gte.file` | The :attribute field must be greater than or equal to :value kilobytes. | يجب أن يكون حجم :attribute :value كيلوبايت أو أكثر. |
| ☐ | `gte.numeric` | Enter a number no smaller than :value. | أدخل رقمًا لا يقل عن :value. |
| ☐ | `gte.string` | Use at least :value characters for :attribute. | استخدم :value حرفًا على الأقل في :attribute. |
| ☐ | `hex_color` | The :attribute field must be a valid hexadecimal color. | يجب أن يكون :attribute لونًا سداسيًّا عشريًّا صالحًا. |
| ☐ | `image` | Choose an image file (JPEG, PNG or WebP) for :attribute. | اختر ملف صورة (JPEG أو PNG أو WebP) لـ :attribute. |
| ☐ | `in` | Choose one of the options for :attribute. | اختر أحد الخيارات المتاحة لـ :attribute. |
| ☐ | `in_array` | The :attribute field must exist in :other. | يجب أن يكون :attribute موجودًا في :other. |
| ☐ | `in_array_keys` | The :attribute field must contain at least one of the following keys: :values. | يجب أن يحتوي :attribute على مفتاح واحد على الأقل مما يلي: :values. |
| ☐ | `integer` | Enter :attribute as a whole number. | أدخل :attribute عددًا صحيحًا. |
| ☐ | `ip` | The :attribute field must be a valid IP address. | يجب أن يكون :attribute عنوان IP صالحًا. |
| ☐ | `ipv4` | The :attribute field must be a valid IPv4 address. | يجب أن يكون :attribute عنوان IPv4 صالحًا. |
| ☐ | `ipv6` | The :attribute field must be a valid IPv6 address. | يجب أن يكون :attribute عنوان IPv6 صالحًا. |
| ☐ | `json` | The :attribute field must be a valid JSON string. | يجب أن يكون :attribute نص JSON صالحًا. |
| ☐ | `list` | The :attribute field must be a list. | يجب أن يكون :attribute قائمة. |
| ☐ | `lowercase` | The :attribute field must be lowercase. | يجب أن يكون :attribute بأحرف صغيرة. |
| ☐ | `lt.array` | The :attribute field must have less than :value items. | يجب أن يحتوي :attribute على أقل من :value عناصر. |
| ☐ | `lt.file` | The :attribute field must be less than :value kilobytes. | يجب أن يقل حجم :attribute عن :value كيلوبايت. |
| ☐ | `lt.numeric` | The :attribute field must be less than :value. | يجب أن يكون :attribute أقل من :value. |
| ☐ | `lt.string` | The :attribute field must be less than :value characters. | يجب أن يقل :attribute عن :value حرفًا. |
| ☐ | `lte.array` | The :attribute field must not have more than :value items. | يجب ألا يحتوي :attribute على أكثر من :value عناصر. |
| ☐ | `lte.file` | The :attribute field must be less than or equal to :value kilobytes. | يجب ألا يزيد حجم :attribute على :value كيلوبايت. |
| ☐ | `lte.numeric` | The :attribute field must be less than or equal to :value. | يجب ألا يزيد :attribute على :value. |
| ☐ | `lte.string` | The :attribute field must be less than or equal to :value characters. | يجب ألا يزيد :attribute على :value حرفًا. |
| ☐ | `mac_address` | The :attribute field must be a valid MAC address. | يجب أن يكون :attribute عنوان MAC صالحًا. |
| ☐ | `max.array` | Choose no more than :max items for :attribute. | اختر ما لا يزيد على :max عناصر لـ :attribute. |
| ☐ | `max.file` | Choose a file smaller than :max kilobytes. | اختر ملفًا أصغر من :max كيلوبايت. |
| ☐ | `max.numeric` | Enter a number no larger than :max. | أدخل رقمًا لا يزيد على :max. |
| ☐ | `max.string` | Shorten :attribute to :max characters or fewer. | اختصر :attribute إلى :max حرفًا أو أقل. |
| ☐ | `max_digits` | The :attribute field must not have more than :max digits. | يجب ألا يزيد :attribute على :max أرقام. |
| ☐ | `mimes` | Choose a file of one of these types: :values. | اختر ملفًا من أحد هذه الأنواع: :values. |
| ☐ | `mimetypes` | Choose a file of one of these types: :values. | اختر ملفًا من أحد هذه الأنواع: :values. |
| ☐ | `min.array` | Choose at least :min items for :attribute. | اختر :min عناصر على الأقل لـ :attribute. |
| ☐ | `min.file` | Choose a file of at least :min kilobytes. | اختر ملفًا لا يقل حجمه عن :min كيلوبايت. |
| ☐ | `min.numeric` | Enter a number of at least :min. | أدخل رقمًا لا يقل عن :min. |
| ☐ | `min.string` | Use at least :min characters for :attribute. | استخدم :min أحرف على الأقل في :attribute. |
| ☐ | `min_digits` | The :attribute field must have at least :min digits. | يجب ألا يقل :attribute عن :min أرقام. |
| ☐ | `missing` | The :attribute field must be missing. | يجب ألا يكون :attribute موجودًا. |
| ☐ | `missing_if` | The :attribute field must be missing when :other is :value. | يجب ألا يكون :attribute موجودًا عندما يكون :other هو :value. |
| ☐ | `missing_unless` | The :attribute field must be missing unless :other is :value. | يجب ألا يكون :attribute موجودًا ما لم يكن :other هو :value. |
| ☐ | `missing_with` | The :attribute field must be missing when :values is present. | يجب ألا يكون :attribute موجودًا عند وجود :values. |
| ☐ | `missing_with_all` | The :attribute field must be missing when :values are present. | يجب ألا يكون :attribute موجودًا عند وجود :values. |
| ☐ | `multiple_of` | The :attribute field must be a multiple of :value. | يجب أن يكون :attribute من مضاعفات :value. |
| ☐ | `not_in` | The selected :attribute is invalid. | القيمة المختارة في :attribute غير صالحة. |
| ☐ | `not_regex` | The :attribute field format is invalid. | صيغة :attribute غير صالحة. |
| ☐ | `numeric` | Enter :attribute as a number. | أدخل :attribute رقمًا. |
| ☐ | `password.letters` | The :attribute field must contain at least one letter. | يجب أن تحتوي :attribute على حرف واحد على الأقل. |
| ☐ | `password.mixed` | The :attribute field must contain at least one uppercase and one lowercase letter. | يجب أن تحتوي :attribute على حرف كبير وحرف صغير على الأقل. |
| ☐ | `password.numbers` | The :attribute field must contain at least one number. | يجب أن تحتوي :attribute على رقم واحد على الأقل. |
| ☐ | `password.symbols` | The :attribute field must contain at least one symbol. | يجب أن تحتوي :attribute على رمز واحد على الأقل. |
| ☐ | `password.uncompromised` | The given :attribute has appeared in a data leak. Please choose a different :attribute. | ظهرت :attribute المدخلة في تسريب للبيانات. يُرجى اختيار :attribute مختلفة. |
| ☐ | `present` | The :attribute field must be present. | يجب أن يكون :attribute موجودًا. |
| ☐ | `present_if` | The :attribute field must be present when :other is :value. | يجب أن يكون :attribute موجودًا عندما يكون :other هو :value. |
| ☐ | `present_unless` | The :attribute field must be present unless :other is :value. | يجب أن يكون :attribute موجودًا ما لم يكن :other هو :value. |
| ☐ | `present_with` | The :attribute field must be present when :values is present. | يجب أن يكون :attribute موجودًا عند وجود :values. |
| ☐ | `present_with_all` | The :attribute field must be present when :values are present. | يجب أن يكون :attribute موجودًا عند وجود :values. |
| ☐ | `prohibited` | The :attribute field is prohibited. | لا يُسمح بـ :attribute. |
| ☐ | `prohibited_if` | The :attribute field is prohibited when :other is :value. | لا يُسمح بـ :attribute عندما يكون :other هو :value. |
| ☐ | `prohibited_if_accepted` | The :attribute field is prohibited when :other is accepted. | لا يُسمح بـ :attribute عند قبول :other. |
| ☐ | `prohibited_if_declined` | The :attribute field is prohibited when :other is declined. | لا يُسمح بـ :attribute عند رفض :other. |
| ☐ | `prohibited_unless` | The :attribute field is prohibited unless :other is in :values. | لا يُسمح بـ :attribute ما لم يكن :other ضمن :values. |
| ☐ | `prohibits` | The :attribute field prohibits :other from being present. | وجود :attribute يمنع وجود :other. |
| ☐ | `regex` | Enter :attribute in the format shown in the hint. | أدخل :attribute بالصيغة المبيّنة في التلميح. |
| ☐ | `required` | Enter :attribute. | أدخل :attribute. |
| ☐ | `required_array_keys` | The :attribute field must contain entries for: :values. | يجب أن يحتوي :attribute على مدخلات لـ: :values. |
| ☐ | `required_if` | The :attribute field is required when :other is :value. | :attribute مطلوب عندما يكون :other هو :value. |
| ☐ | `required_if_accepted` | The :attribute field is required when :other is accepted. | :attribute مطلوب عند قبول :other. |
| ☐ | `required_if_declined` | The :attribute field is required when :other is declined. | :attribute مطلوب عند رفض :other. |
| ☐ | `required_unless` | The :attribute field is required unless :other is in :values. | :attribute مطلوب ما لم يكن :other ضمن :values. |
| ☐ | `required_with` | The :attribute field is required when :values is present. | :attribute مطلوب عند وجود :values. |
| ☐ | `required_with_all` | The :attribute field is required when :values are present. | :attribute مطلوب عند وجود :values. |
| ☐ | `required_without` | The :attribute field is required when :values is not present. | :attribute مطلوب عند عدم وجود :values. |
| ☐ | `required_without_all` | The :attribute field is required when none of :values are present. | :attribute مطلوب عند عدم وجود أي من :values. |
| ☐ | `same` | The :attribute field must match :other. | يجب أن يطابق :attribute :other. |
| ☐ | `size.array` | The :attribute field must contain :size items. | يجب أن يحتوي :attribute على :size عناصر. |
| ☐ | `size.file` | The :attribute field must be :size kilobytes. | يجب أن يكون حجم :attribute :size كيلوبايت. |
| ☐ | `size.numeric` | The :attribute field must be :size. | يجب أن يكون :attribute :size. |
| ☐ | `size.string` | The :attribute field must be :size characters. | يجب أن يتكون :attribute من :size حرفًا. |
| ☐ | `starts_with` | The :attribute field must start with one of the following: :values. | يجب أن يبدأ :attribute بأحد ما يلي: :values. |
| ☐ | `string` | Enter :attribute as text. | أدخل :attribute نصًا. |
| ☐ | `timezone` | The :attribute field must be a valid timezone. | يجب أن يكون :attribute منطقة زمنية صالحة. |
| ☐ | `unique` | That :attribute is already in use. Choose another. | :attribute هذا مستخدم من قبل. اختر غيره. |
| ☐ | `uploaded` | The :attribute did not finish uploading. Choose the file again. | لم يكتمل رفع :attribute. اختر الملف مرة أخرى. |
| ☐ | `uppercase` | The :attribute field must be uppercase. | يجب أن يكون :attribute بأحرف كبيرة. |
| ☐ | `url` | Enter a full web address starting with https://. | أدخل عنوان ويب كاملًا يبدأ بـ https://‎. |
| ☐ | `ulid` | The :attribute field must be a valid ULID. | يجب أن يكون :attribute معرّف ULID صالحًا. |
| ☐ | `uuid` | The :attribute field must be a valid UUID. | يجب أن يكون :attribute معرّف UUID صالحًا. |
| ☐ | `custom.*year.integer` | Enter a year between 1900 and 2027. | أدخل سنة بين 1900 و2027. |
| ☐ | `custom.*year.between` | Enter a year between 1900 and 2027. | أدخل سنة بين 1900 و2027. |
| ☐ | `custom.*year.gte` | Enter an end year no earlier than the start year. | أدخل سنة نهاية لا تسبق سنة البداية. |
| ☐ | `attributes.name` |  | الاسم |
| ☐ | `attributes.email` |  | البريد الإلكتروني |
| ☐ | `attributes.password` |  | كلمة المرور |
| ☐ | `attributes.password_confirmation` |  | تأكيد كلمة المرور |
| ☐ | `attributes.current_password` |  | كلمة المرور الحالية |
| ☐ | `attributes.code` |  | رمز التحقق |
| ☐ | `attributes.recovery_code` |  | رمز الاسترداد |
| ☐ | `attributes.remember` |  | تذكّرني |
| ☐ | `attributes.token` |  | رمز إعادة التعيين |
| ☐ | `attributes.locale` |  | اللغة |
| ☐ | `attributes.theme` |  | المظهر |
| ☐ | `attributes.title` |  | العنوان |
| ☐ | `attributes.subtitle` |  | العنوان الفرعي |
| ☐ | `attributes.summary` |  | الملخص |
| ☐ | `attributes.body` |  | النص |
| ☐ | `attributes.content` |  | المحتوى |
| ☐ | `attributes.description` |  | الوصف |
| ☐ | `attributes.abstract` |  | الملخص |
| ☐ | `attributes.epigraph` |  | التصدير |
| ☐ | `attributes.quote` |  | الاقتباس |
| ☐ | `attributes.attribution` |  | القائل |
| ☐ | `attributes.message` |  | الرسالة |
| ☐ | `attributes.note` |  | الملاحظة |
| ☐ | `attributes.reason` |  | السبب |
| ☐ | `attributes.year` |  | السنة |
| ☐ | `attributes.start_year` |  | سنة البداية |
| ☐ | `attributes.end_year` |  | سنة النهاية |
| ☐ | `attributes.date` |  | التاريخ |
| ☐ | `attributes.published_at` |  | تاريخ النشر |
| ☐ | `attributes.status` |  | الحالة |
| ☐ | `attributes.type` |  | النوع |
| ☐ | `attributes.category` |  | التصنيف |
| ☐ | `attributes.tags` |  | الوسوم |
| ☐ | `attributes.topic` |  | الموضوع |
| ☐ | `attributes.course` |  | المقرر |
| ☐ | `attributes.version` |  | الإصدار |
| ☐ | `attributes.position` |  | الترتيب |
| ☐ | `attributes.sort_order` |  | ترتيب العرض |
| ☐ | `attributes.authors` |  | المؤلفون |
| ☐ | `attributes.venue` |  | جهة النشر |
| ☐ | `attributes.citation` |  | الاستشهاد |
| ☐ | `attributes.doi` |  | DOI |
| ☐ | `attributes.url` |  | الرابط |
| ☐ | `attributes.link` |  | الرابط |
| ☐ | `attributes.links` |  | الروابط |
| ☐ | `attributes.source_url` |  | رابط المصدر |
| ☐ | `attributes.video_url` |  | رابط الفيديو |
| ☐ | `attributes.embed` |  | رمز التضمين |
| ☐ | `attributes.organisation` |  | الجهة |
| ☐ | `attributes.organization` |  | الجهة |
| ☐ | `attributes.affiliation` |  | جهة الانتماء |
| ☐ | `attributes.academic_field` |  | التخصص العلمي |
| ☐ | `attributes.role` |  | الدور |
| ☐ | `attributes.role_description` |  | وصف الدور |
| ☐ | `attributes.event` |  | المناسبة |
| ☐ | `attributes.location` |  | المكان |
| ☐ | `attributes.place` |  | المكان |
| ☐ | `attributes.relationship` |  | الصلة |
| ☐ | `attributes.file` |  | الملف |
| ☐ | `attributes.files` |  | الملفات |
| ☐ | `attributes.image` |  | الصورة |
| ☐ | `attributes.images` |  | الصور |
| ☐ | `attributes.photo` |  | الصورة |
| ☐ | `attributes.photos` |  | الصور |
| ☐ | `attributes.portrait` |  | الصورة الشخصية |
| ☐ | `attributes.cover` |  | صورة الغلاف |
| ☐ | `attributes.attachment` |  | المرفق |
| ☐ | `attributes.attachments` |  | المرفقات |
| ☐ | `attributes.alt_text` |  | النص البديل |
| ☐ | `attributes.caption` |  | التعليق |
| ☐ | `attributes.credit` |  | حقوق الصورة |
| ☐ | `attributes.album` |  | الألبوم |
| ☐ | `attributes.album_id` |  | الألبوم |
| ☐ | `attributes.expertise` |  | مجالات الخبرة |
| ☐ | `attributes.key_facts` |  | أبرز المعلومات |
| ☐ | `attributes.short_bio` |  | النبذة المختصرة |
| ☐ | `attributes.full_bio` |  | السيرة الكاملة |
| ☐ | `attributes.contact_note` |  | ملاحظة التواصل |
| ☐ | `attributes.phone` |  | رقم الهاتف |
| ☐ | `attributes.website` |  | الموقع الإلكتروني |
| ☐ | `attributes.agree` |  | الموافقة |
| ☐ | `attributes.terms` |  | الشروط |
| ☐ | `attributes.consent` |  | الموافقة |
| ☐ | `attributes.form.name` |  | الاسم |
| ☐ | `attributes.form.email` |  | البريد الإلكتروني |
| ☐ | `attributes.form.password` |  | كلمة المرور |
| ☐ | `attributes.form.password_confirmation` |  | تأكيد كلمة المرور |
| ☐ | `attributes.form.current_password` |  | كلمة المرور الحالية |
| ☐ | `attributes.form.code` |  | رمز التحقق |
| ☐ | `attributes.form.recovery_code` |  | رمز الاسترداد |
| ☐ | `attributes.form.remember` |  | تذكّرني |
| ☐ | `attributes.form.token` |  | رمز إعادة التعيين |
| ☐ | `attributes.form.locale` |  | اللغة |
| ☐ | `attributes.form.theme` |  | المظهر |
| ☐ | `attributes.form.title` |  | العنوان |
| ☐ | `attributes.form.subtitle` |  | العنوان الفرعي |
| ☐ | `attributes.form.summary` |  | الملخص |
| ☐ | `attributes.form.body` |  | النص |
| ☐ | `attributes.form.content` |  | المحتوى |
| ☐ | `attributes.form.description` |  | الوصف |
| ☐ | `attributes.form.abstract` |  | الملخص |
| ☐ | `attributes.form.epigraph` |  | التصدير |
| ☐ | `attributes.form.quote` |  | الاقتباس |
| ☐ | `attributes.form.attribution` |  | القائل |
| ☐ | `attributes.form.message` |  | الرسالة |
| ☐ | `attributes.form.note` |  | الملاحظة |
| ☐ | `attributes.form.reason` |  | السبب |
| ☐ | `attributes.form.year` |  | السنة |
| ☐ | `attributes.form.start_year` |  | سنة البداية |
| ☐ | `attributes.form.end_year` |  | سنة النهاية |
| ☐ | `attributes.form.date` |  | التاريخ |
| ☐ | `attributes.form.published_at` |  | تاريخ النشر |
| ☐ | `attributes.form.status` |  | الحالة |
| ☐ | `attributes.form.type` |  | النوع |
| ☐ | `attributes.form.category` |  | التصنيف |
| ☐ | `attributes.form.tags` |  | الوسوم |
| ☐ | `attributes.form.topic` |  | الموضوع |
| ☐ | `attributes.form.course` |  | المقرر |
| ☐ | `attributes.form.version` |  | الإصدار |
| ☐ | `attributes.form.position` |  | الترتيب |
| ☐ | `attributes.form.sort_order` |  | ترتيب العرض |
| ☐ | `attributes.form.authors` |  | المؤلفون |
| ☐ | `attributes.form.venue` |  | جهة النشر |
| ☐ | `attributes.form.citation` |  | الاستشهاد |
| ☐ | `attributes.form.doi` |  | DOI |
| ☐ | `attributes.form.url` |  | الرابط |
| ☐ | `attributes.form.link` |  | الرابط |
| ☐ | `attributes.form.links` |  | الروابط |
| ☐ | `attributes.form.source_url` |  | رابط المصدر |
| ☐ | `attributes.form.video_url` |  | رابط الفيديو |
| ☐ | `attributes.form.embed` |  | رمز التضمين |
| ☐ | `attributes.form.organisation` |  | الجهة |
| ☐ | `attributes.form.organization` |  | الجهة |
| ☐ | `attributes.form.affiliation` |  | جهة الانتماء |
| ☐ | `attributes.form.academic_field` |  | التخصص العلمي |
| ☐ | `attributes.form.role` |  | الدور |
| ☐ | `attributes.form.role_description` |  | وصف الدور |
| ☐ | `attributes.form.event` |  | المناسبة |
| ☐ | `attributes.form.location` |  | المكان |
| ☐ | `attributes.form.place` |  | المكان |
| ☐ | `attributes.form.relationship` |  | الصلة |
| ☐ | `attributes.form.file` |  | الملف |
| ☐ | `attributes.form.files` |  | الملفات |
| ☐ | `attributes.form.image` |  | الصورة |
| ☐ | `attributes.form.images` |  | الصور |
| ☐ | `attributes.form.photo` |  | الصورة |
| ☐ | `attributes.form.photos` |  | الصور |
| ☐ | `attributes.form.portrait` |  | الصورة الشخصية |
| ☐ | `attributes.form.cover` |  | صورة الغلاف |
| ☐ | `attributes.form.attachment` |  | المرفق |
| ☐ | `attributes.form.attachments` |  | المرفقات |
| ☐ | `attributes.form.alt_text` |  | النص البديل |
| ☐ | `attributes.form.caption` |  | التعليق |
| ☐ | `attributes.form.credit` |  | حقوق الصورة |
| ☐ | `attributes.form.album` |  | الألبوم |
| ☐ | `attributes.form.album_id` |  | الألبوم |
| ☐ | `attributes.form.expertise` |  | مجالات الخبرة |
| ☐ | `attributes.form.key_facts` |  | أبرز المعلومات |
| ☐ | `attributes.form.short_bio` |  | النبذة المختصرة |
| ☐ | `attributes.form.full_bio` |  | السيرة الكاملة |
| ☐ | `attributes.form.contact_note` |  | ملاحظة التواصل |
| ☐ | `attributes.form.phone` |  | رقم الهاتف |
| ☐ | `attributes.form.website` |  | الموقع الإلكتروني |
| ☐ | `attributes.form.agree` |  | الموافقة |
| ☐ | `attributes.form.terms` |  | الشروط |
| ☐ | `attributes.form.consent` |  | الموافقة |

