<?php

use App\Constants\AdminNavigation;
use App\Constants\AdminUx;
use App\Constants\Analytics;
use App\Constants\ContentTypes;
use App\Constants\ErrorPages;
use App\Constants\Pages;
use App\Constants\Permissions;
use App\Constants\RateLimits;
use App\Constants\Roles;
use App\Constants\Search;
use App\Constants\Sections;
use App\Constants\Seo;
use App\Constants\ThemeSettings;
use App\Constants\TributeCards;
use App\Http\Controllers\AboutController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin\AdminSearchController;
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PageErrorPreviewController;
use App\Http\Controllers\Admin\TwoFactorReminderController;
use App\Http\Controllers\AnalyticsCollectController;
use App\Http\Controllers\AwardController;
use App\Http\Controllers\ChatAttachmentController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ChatUploadController;
use App\Http\Controllers\CommentaryController;
use App\Http\Controllers\CommunityController;
use App\Http\Controllers\CvController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OtherItemController;
use App\Http\Controllers\PictureController;
use App\Http\Controllers\PrivacyController;
use App\Http\Controllers\PublicationController;
use App\Http\Controllers\RobotsController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SectionPlaceholderController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\StyleguideController;
use App\Http\Controllers\TeachingMaterialController;
use App\Http\Controllers\ThemeController;
use App\Http\Controllers\TributeController;
use App\Http\Controllers\VideoController;
use App\Http\Middleware\EnsureCommunityMember;
use App\Http\Middleware\EnsureLocalEnvironment;
use App\Livewire\Admin\Albums\Form as AlbumForm;
use App\Livewire\Admin\Albums\Index as AlbumIndex;
use App\Livewire\Admin\Awards\Form as AwardForm;
use App\Livewire\Admin\Awards\Index as AwardIndex;
use App\Livewire\Admin\Commentaries\Form as CommentaryForm;
use App\Livewire\Admin\Commentaries\Index as CommentaryIndex;
use App\Livewire\Admin\Community\Applications as CommunityApplications;
use App\Livewire\Admin\Community\Reports as ChatReports;
use App\Livewire\Admin\CvEntries\Form as CvEntryForm;
use App\Livewire\Admin\CvEntries\Index as CvEntryIndex;
use App\Livewire\Admin\HomeQuotes\Form as HomeQuoteForm;
use App\Livewire\Admin\HomeQuotes\Index as HomeQuoteIndex;
use App\Livewire\Admin\Media\Library as MediaLibrary;
use App\Livewire\Admin\OtherItems\Form as OtherItemForm;
use App\Livewire\Admin\OtherItems\Index as OtherItemIndex;
use App\Livewire\Admin\Pages\Edit as PageEdit;
use App\Livewire\Admin\Pages\Index as PageIndex;
use App\Livewire\Admin\Profile\EditProfile;
use App\Livewire\Admin\Publications\Form as PublicationForm;
use App\Livewire\Admin\Publications\Index as PublicationIndex;
use App\Livewire\Admin\Settings\SiteSettings;
use App\Livewire\Admin\TeachingMaterials\Form as TeachingMaterialForm;
use App\Livewire\Admin\TeachingMaterials\Index as TeachingMaterialIndex;
use App\Livewire\Admin\Tributes\Moderate as TributeModeration;
use App\Livewire\Admin\Users\Index as UserIndex;
use App\Livewire\Admin\Videos\Form as VideoForm;
use App\Livewire\Admin\Videos\Index as VideoIndex;
use App\Livewire\Community\Chat\Room as ChatRoom;
use App\Services\ChatAttachmentService;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

// Crawlers: the sitemap of published content and the robots rules (no static files, so both follow APP_URL).
Route::get(Seo::SITEMAP_PATH, SitemapController::class)->name(Seo::SITEMAP_ROUTE);
Route::get(Seo::ROBOTS_PATH, RobotsController::class)->name(Seo::ROBOTS_ROUTE);

// Site-wide search: the plain page, and JSON for the header dialog (Accept: application/json).
Route::get(Search::PATH, SearchController::class)->name(Search::ROUTE);

// Sections with their own controller. Each keeps the route name from Sections::definitions();
// detail routes nest under the same name (e.g. commentary.show).
$sections = Sections::definitions();

Route::get($sections[Sections::ABOUT]['path'], AboutController::class)->name($sections[Sections::ABOUT]['route']);
Route::get($sections[Sections::CV]['path'], CvController::class)->name($sections[Sections::CV]['route']);
Route::get($sections[Sections::PUBLICATIONS]['path'], PublicationController::class)->name($sections[Sections::PUBLICATIONS]['route']);
Route::get($sections[Sections::AWARDS]['path'], AwardController::class)->name($sections[Sections::AWARDS]['route']);

Route::get($sections[Sections::VIDEOS]['path'], [VideoController::class, 'index'])->name($sections[Sections::VIDEOS]['route']);
Route::get($sections[Sections::VIDEOS]['path'].'/{slug}', [VideoController::class, 'show'])->name($sections[Sections::VIDEOS]['route'].'.show');

Route::get($sections[Sections::PICTURES]['path'], [PictureController::class, 'index'])->name($sections[Sections::PICTURES]['route']);
Route::get($sections[Sections::PICTURES]['path'].'/{album}', [PictureController::class, 'show'])->name($sections[Sections::PICTURES]['route'].'.show');

Route::get($sections[Sections::TEACHING]['path'], [TeachingMaterialController::class, 'index'])->name($sections[Sections::TEACHING]['route']);
Route::get($sections[Sections::TEACHING]['path'].'/{material}/download', [TeachingMaterialController::class, 'download'])
    ->whereNumber('material')
    ->name($sections[Sections::TEACHING]['route'].'.download');

Route::get($sections[Sections::COMMENTARY]['path'], [CommentaryController::class, 'index'])->name($sections[Sections::COMMENTARY]['route']);
Route::get($sections[Sections::COMMENTARY]['path'].'/{slug}', [CommentaryController::class, 'show'])->name($sections[Sections::COMMENTARY]['route'].'.show');

Route::get($sections[Sections::OTHER]['path'], [OtherItemController::class, 'index'])->name($sections[Sections::OTHER]['route']);
Route::get($sections[Sections::OTHER]['path'].'/{slug}', [OtherItemController::class, 'show'])->name($sections[Sections::OTHER]['route'].'.show');

Route::get($sections[Sections::TRIBUTES]['path'], [TributeController::class, 'index'])->name($sections[Sections::TRIBUTES]['route']);
Route::get($sections[Sections::TRIBUTES]['path'].'/new', [TributeController::class, 'create'])->name($sections[Sections::TRIBUTES]['route'].'.create');
Route::post($sections[Sections::TRIBUTES]['path'].'/new', [TributeController::class, 'edit'])->name($sections[Sections::TRIBUTES]['route'].'.edit');
Route::post($sections[Sections::TRIBUTES]['path'].'/preview', [TributeController::class, 'preview'])->name($sections[Sections::TRIBUTES]['route'].'.preview');
Route::get($sections[Sections::TRIBUTES]['path'].'/preview', [TributeController::class, 'previewAgain'])->name($sections[Sections::TRIBUTES]['route'].'.preview.show');
Route::post($sections[Sections::TRIBUTES]['path'], [TributeController::class, 'store'])->name($sections[Sections::TRIBUTES]['route'].'.store');
Route::get($sections[Sections::TRIBUTES]['path'].'/thank-you', [TributeController::class, 'thanks'])->name($sections[Sections::TRIBUTES]['route'].'.thanks');
Route::get($sections[Sections::TRIBUTES]['path'].'/{tribute}/card', [TributeController::class, 'card'])
    ->whereNumber('tribute')
    ->name(TributeCards::ROUTE);

// Scientific Community: public page with the application form, then the members-only
// chat (Livewire). The chat routes check membership on every request, including
// Livewire updates (EnsureCommunityMember is also a persistent Livewire middleware).
Route::get($sections[Sections::COMMUNITY]['path'], [CommunityController::class, 'index'])->name($sections[Sections::COMMUNITY]['route']);
Route::post($sections[Sections::COMMUNITY]['path'].'/apply', [CommunityController::class, 'store'])
    ->middleware('throttle:'.RateLimits::COMMUNITY_SIGNUP)
    ->name($sections[Sections::COMMUNITY]['route'].'.apply');

// One room: /community/chat. Attachments are staged in a temporary area first and
// served only through signed links that check membership again.
Route::middleware(['auth', 'verified', EnsureCommunityMember::ALIAS])
    ->prefix($sections[Sections::COMMUNITY]['path'].'/chat')
    ->name($sections[Sections::COMMUNITY]['route'].'.chat')
    ->group(function () {
        Route::get('/', ChatRoom::class);
        Route::post('/uploads', [ChatUploadController::class, 'store'])
            ->middleware('throttle:'.RateLimits::CHAT_UPLOAD)
            ->name('.uploads.store');
        Route::delete('/uploads/{upload}', [ChatUploadController::class, 'destroy'])
            ->whereUuid('upload')
            ->name('.uploads.destroy');
        Route::get('/attachments/{media}/{variant}', ChatAttachmentController::class)
            ->middleware('signed')
            ->whereNumber('media')
            ->whereIn('variant', [ChatAttachmentService::VARIANT_ORIGINAL, ChatAttachmentService::VARIANT_THUMB, ChatAttachmentService::VARIANT_MEDIUM])
            ->name('.attachments.show');
    });

// The old channel addresses (/community/chat/general and so on) move permanently to the one room.
Route::get($sections[Sections::COMMUNITY]['path'].'/chat/{channel}', ChatController::class)
    ->where('channel', '[a-z0-9-]+')
    ->name($sections[Sections::COMMUNITY]['route'].'.chat.legacy');

$built = [Sections::ABOUT, Sections::CV, Sections::PUBLICATIONS, Sections::AWARDS, Sections::VIDEOS, Sections::PICTURES, Sections::TEACHING, Sections::COMMENTARY, Sections::COMMUNITY, Sections::OTHER, Sections::TRIBUTES];

// One placeholder page per remaining public section, named after the section, until its
// own controller exists.
foreach (array_diff_key($sections, array_flip($built)) as $key => $section) {
    Route::get($section['path'], SectionPlaceholderController::class)
        ->defaults('section', $key)
        ->name($section['route']);
}

// First-party analytics (off until approved): the beacon endpoint, and the privacy page
// that explains it (served only while analytics is on).
Route::post(Analytics::COLLECT_PATH, AnalyticsCollectController::class)
    ->middleware('throttle:'.RateLimits::ANALYTICS_COLLECT)
    ->name(Analytics::COLLECT_ROUTE);
Route::get(Analytics::PRIVACY_PATH, PrivacyController::class)->name(Analytics::PRIVACY_ROUTE);

// Design-system review page. Served only in the local environment (404 elsewhere).
Route::get('/styleguide', StyleguideController::class)
    ->middleware(EnsureLocalEnvironment::class)
    ->name('styleguide');

// The colour theme (System, Light, Dark): kept on the account when signed in, in a cookie otherwise.
Route::patch(ThemeSettings::PATH, [ThemeController::class, 'update'])->name(ThemeSettings::ROUTE);

// Signed-in users (any role) manage their own name, email, password and two-factor here.
Route::get('/account', [AccountController::class, 'show'])
    ->middleware(['auth', 'verified'])
    ->name(AdminNavigation::ACCOUNT_ROUTE);

// Admin dashboard: staff roles only (Spatie role middleware) with a verified email.
// Two-factor authentication is recommended, not required (a dashboard card suggests it).
// Permissions are checked per page (Spatie permission middleware) and per action (Policies).
Route::middleware(['auth', 'verified', 'role:'.implode('|', Roles::staff())])
    ->prefix('admin')
    ->name(AdminNavigation::ROUTE_PREFIX)
    ->group(function () {
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::post(AdminUx::TWO_FACTOR_REMINDER_PATH, TwoFactorReminderController::class)
            ->name(substr(AdminUx::TWO_FACTOR_REMINDER_ROUTE, strlen(AdminNavigation::ROUTE_PREFIX)));

        // The command palette's content search (JSON).
        Route::get(AdminUx::SEARCH_PATH, AdminSearchController::class)
            ->middleware(['permission:'.Permissions::MANAGE_CONTENT, 'throttle:60,1'])
            ->name(substr(AdminUx::SEARCH_ROUTE, strlen(AdminNavigation::ROUTE_PREFIX)));

        // Content screens (Livewire): one index with create and edit forms per type,
        // the single profile form and the media library. Each route takes the
        // permission of its navigation item; actions are authorised by policy.
        Route::middleware('permission:'.Permissions::MANAGE_CONTENT)->group(function () {
            Route::get(AdminNavigation::get(AdminNavigation::PROFILE)['path'], EditProfile::class)
                ->name(substr(ContentTypes::indexRoute(ContentTypes::PROFILE), strlen(AdminNavigation::ROUTE_PREFIX)));

            $screens = [
                ContentTypes::CV_ENTRY => [CvEntryIndex::class, CvEntryForm::class],
                ContentTypes::PUBLICATION => [PublicationIndex::class, PublicationForm::class],
                ContentTypes::AWARD => [AwardIndex::class, AwardForm::class],
                ContentTypes::COMMENTARY => [CommentaryIndex::class, CommentaryForm::class],
                ContentTypes::TEACHING_MATERIAL => [TeachingMaterialIndex::class, TeachingMaterialForm::class],
                ContentTypes::OTHER_ITEM => [OtherItemIndex::class, OtherItemForm::class],
                ContentTypes::HOME_QUOTE => [HomeQuoteIndex::class, HomeQuoteForm::class],
                ContentTypes::VIDEO => [VideoIndex::class, VideoForm::class],
                ContentTypes::ALBUM => [AlbumIndex::class, AlbumForm::class],
            ];

            foreach ($screens as $type => [$index, $form]) {
                $path = AdminNavigation::get(ContentTypes::get($type)['nav'])['path'];
                $name = substr(ContentTypes::indexRoute($type), strlen(AdminNavigation::ROUTE_PREFIX));
                $parameter = ContentTypes::routeParameter($type);

                Route::get($path, $index)->name($name);
                Route::get($path.'/create', $form)->name($name.'.create');
                Route::get($path.'/{'.$parameter.':id}/edit', $form)->whereNumber($parameter)->name($name.'.edit');
            }

            // Page copy: the list of pages, one edit screen each, and the error pages drawn with their drafts.
            $pages = AdminNavigation::get(AdminNavigation::PAGES)['path'];
            $pagesName = substr(AdminNavigation::routeFor(AdminNavigation::PAGES), strlen(AdminNavigation::ROUTE_PREFIX));

            Route::get($pages, PageIndex::class)->name($pagesName);
            Route::get($pages.'/{'.Pages::ROUTE_PARAMETER.'}/edit', PageEdit::class)->whereIn(Pages::ROUTE_PARAMETER, Pages::all())->name($pagesName.'.edit');
            Route::get($pages.'/{'.Pages::ROUTE_PARAMETER.'}/preview/{code}', PageErrorPreviewController::class)
                ->whereIn(Pages::ROUTE_PARAMETER, [Pages::ERRORS])
                ->whereIn('code', array_map('strval', ErrorPages::codes()))
                ->name($pagesName.'.error-preview');

            Route::get(AdminNavigation::get(AdminNavigation::MEDIA_LIBRARY)['path'], MediaLibrary::class)
                ->name(substr(AdminNavigation::routeFor(AdminNavigation::MEDIA_LIBRARY), strlen(AdminNavigation::ROUTE_PREFIX)));
        });

        Route::get(AdminNavigation::get(AdminNavigation::TRIBUTES)['path'], TributeModeration::class)
            ->middleware('permission:'.Permissions::MODERATE_TRIBUTES)
            ->name(substr(AdminNavigation::routeFor(AdminNavigation::TRIBUTES), strlen(AdminNavigation::ROUTE_PREFIX)));

        // Community: the approval queue and member list share one screen; chat reports have their own.
        Route::middleware('permission:'.Permissions::MANAGE_COMMUNITY)->group(function () {
            Route::get(AdminNavigation::get(AdminNavigation::APPLICATIONS)['path'], CommunityApplications::class)
                ->name(substr(AdminNavigation::routeFor(AdminNavigation::APPLICATIONS), strlen(AdminNavigation::ROUTE_PREFIX)));
            Route::get(AdminNavigation::get(AdminNavigation::MEMBERS)['path'], CommunityApplications::class)
                ->name(substr(AdminNavigation::routeFor(AdminNavigation::MEMBERS), strlen(AdminNavigation::ROUTE_PREFIX)));
        });

        Route::get(AdminNavigation::get(AdminNavigation::CHAT_REPORTS)['path'], ChatReports::class)
            ->middleware('permission:'.Permissions::MODERATE_CHAT)
            ->name(substr(AdminNavigation::routeFor(AdminNavigation::CHAT_REPORTS), strlen(AdminNavigation::ROUTE_PREFIX)));

        // Audience: the analytics report and its CSV exports (admins and editors).
        Route::middleware('permission:'.Permissions::VIEW_ANALYTICS)->group(function () {
            $name = substr(Analytics::ADMIN_ROUTE, strlen(AdminNavigation::ROUTE_PREFIX));

            Route::get(Analytics::ADMIN_PATH, [AnalyticsController::class, 'index'])->name($name);
            Route::get(Analytics::ADMIN_PATH.'/export/{export}', [AnalyticsController::class, 'export'])
                ->whereIn('export', Analytics::exports())
                ->name(substr(Analytics::EXPORT_ROUTE, strlen(AdminNavigation::ROUTE_PREFIX)));
        });

        // Settings: staff and community accounts, and the site settings. Both admin only.
        Route::get(AdminNavigation::get(AdminNavigation::USERS)['path'], UserIndex::class)
            ->middleware('permission:'.Permissions::MANAGE_USERS)
            ->name(substr(AdminNavigation::routeFor(AdminNavigation::USERS), strlen(AdminNavigation::ROUTE_PREFIX)));
        Route::get(AdminNavigation::get(AdminNavigation::SITE_SETTINGS)['path'], SiteSettings::class)
            ->middleware('permission:'.Permissions::MANAGE_SETTINGS)
            ->name(substr(AdminNavigation::routeFor(AdminNavigation::SITE_SETTINGS), strlen(AdminNavigation::ROUTE_PREFIX)));
    });
