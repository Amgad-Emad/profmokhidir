<?php

namespace Tests\Unit\Constants;

use App\Constants\AdminNavigation;
use App\Constants\Permissions;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AdminNavigationTest extends TestCase
{
    #[Test]
    public function groups_are_in_sidebar_order(): void
    {
        $this->assertSame(['content', 'media', 'community', 'tributes', 'settings'], array_keys(AdminNavigation::groups()));
        $this->assertSame(['Content', 'Media', 'Community', 'Tributes', 'Settings'], array_values(AdminNavigation::groups()));
    }

    #[Test]
    public function every_item_belongs_to_a_group_and_names_a_valid_permission_and_an_admin_route(): void
    {
        foreach (AdminNavigation::items() as $key => $item) {
            $this->assertTrue(AdminNavigation::isValidGroup($item['group']), "{$key} group");
            $this->assertTrue(Permissions::isValid($item['permission']), "{$key} permission");
            $this->assertTrue(
                str_starts_with($item['route'], AdminNavigation::ROUTE_PREFIX) || $item['route'] === AdminNavigation::ACCOUNT_ROUTE,
                "{$key} route",
            );
            $this->assertNotSame('', $item['label']);
        }

        $this->assertTrue(AdminNavigation::isValid(AdminNavigation::TRIBUTES));
        $this->assertFalse(AdminNavigation::isValid('nope'));
    }

    #[Test]
    public function admin_only_areas_need_admin_permissions(): void
    {
        $this->assertSame(Permissions::MANAGE_COMMUNITY, AdminNavigation::get(AdminNavigation::APPLICATIONS)['permission']);
        $this->assertSame(Permissions::MANAGE_COMMUNITY, AdminNavigation::get(AdminNavigation::MEMBERS)['permission']);
        $this->assertSame(Permissions::MODERATE_CHAT, AdminNavigation::get(AdminNavigation::CHAT_REPORTS)['permission']);
        $this->assertSame(Permissions::MANAGE_USERS, AdminNavigation::get(AdminNavigation::USERS)['permission']);
        $this->assertSame(Permissions::MANAGE_SETTINGS, AdminNavigation::get(AdminNavigation::SITE_SETTINGS)['permission']);
        $this->assertSame(Permissions::MODERATE_TRIBUTES, AdminNavigation::get(AdminNavigation::TRIBUTES)['permission']);
        $this->assertSame(Permissions::MANAGE_CONTENT, AdminNavigation::get(AdminNavigation::PUBLICATIONS)['permission']);
    }

    #[Test]
    public function placeholders_exclude_pages_outside_the_dashboard(): void
    {
        $placeholders = AdminNavigation::placeholders();

        $this->assertArrayNotHasKey(AdminNavigation::ACCOUNT, $placeholders);
        $this->assertArrayNotHasKey(AdminNavigation::TRIBUTES, $placeholders);
        $this->assertArrayNotHasKey(AdminNavigation::PUBLICATIONS, $placeholders);
        $this->assertSame([], $placeholders, 'Every dashboard section has its own screens now.');
        $this->assertTrue(AdminNavigation::isBuilt(AdminNavigation::MEDIA_LIBRARY));
        $this->assertTrue(AdminNavigation::isBuilt(AdminNavigation::USERS));
        $this->assertTrue(AdminNavigation::isBuilt(AdminNavigation::SITE_SETTINGS));
        $this->assertSame('admin.users.index', AdminNavigation::routeFor(AdminNavigation::USERS));
        $this->assertSame('users', AdminNavigation::get(AdminNavigation::USERS)['path']);
        $this->assertSame('admin.settings.site', AdminNavigation::routeFor(AdminNavigation::SITE_SETTINGS));
        $this->assertSame('admin.tributes.index', AdminNavigation::routeFor(AdminNavigation::TRIBUTES));
        $this->assertSame('admin.media.library', AdminNavigation::routeFor(AdminNavigation::MEDIA_LIBRARY));
        $this->assertSame(AdminNavigation::ACCOUNT_ROUTE, AdminNavigation::routeFor(AdminNavigation::ACCOUNT));
    }

    #[Test]
    public function items_in_returns_only_that_group(): void
    {
        $items = AdminNavigation::itemsIn(AdminNavigation::GROUP_MEDIA);

        $this->assertSame([AdminNavigation::VIDEOS, AdminNavigation::ALBUMS, AdminNavigation::MEDIA_LIBRARY], array_keys($items));
    }

    #[Test]
    public function unknown_items_throw(): void
    {
        $this->expectException(InvalidArgumentException::class);
        AdminNavigation::get('missing');
    }
}
