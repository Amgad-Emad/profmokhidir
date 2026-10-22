import { readSetting, writeSetting } from './storage';

const COLLAPSED = 'admin.sidebar.collapsed';
const GROUPS = 'admin.sidebar.groups';

/*
 | The admin frame: the mobile drawer (`open`), the desktop sidebar collapsed
 | to icons (`collapsed`, remembered per browser) and the collapsible groups
 | (remembered too; the group holding the current screen always opens).
 */
export function adminShell() {
    return {
        open: false,
        collapsed: false,

        init() {
            this.collapsed = readSetting(COLLAPSED, false) === true;
        },

        toggleCollapsed() {
            this.collapsed = !this.collapsed;
            writeSetting(COLLAPSED, this.collapsed);
        },

        groupOpen(key, hasActive) {
            if (hasActive) return true;
            return readSetting(GROUPS, {})[key] !== false;
        },

        rememberGroup(key, open) {
            const groups = readSetting(GROUPS, {});
            groups[key] = open;
            writeSetting(GROUPS, groups);
        },
    };
}
