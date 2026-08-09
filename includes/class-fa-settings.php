<?php
/**
 * Plugin settings (single option array).
 */

if (!defined('ABSPATH')) {
    exit;
}

class FA_Settings {

    const OPTION_KEY = 'fa_settings';

    /**
     * Default settings.
     *
     * @return array
     */
    public static function defaults() {
        return array(
            'dashboard_page_id'       => 0,
            'dashboard_roles'         => array(),
            'menu_visibility'         => array(),
            'unrestricted_user_ids'   => array(),
            'hide_admin_notices'      => 1,
            'hide_help_screen_options'=> 1,
            'footer_text'             => '',
            'captured_menus'          => array(),
        );
    }

    /**
     * Ensure option exists with defaults merged.
     */
    public static function ensure_defaults() {
        $current = get_option(self::OPTION_KEY, false);
        if ($current === false) {
            add_option(self::OPTION_KEY, self::defaults(), '', false);
            return;
        }
        if (!is_array($current)) {
            update_option(self::OPTION_KEY, self::defaults(), false);
            return;
        }
        $merged = array_merge(self::defaults(), $current);
        if ($merged !== $current) {
            update_option(self::OPTION_KEY, $merged, false);
        }
    }

    /**
     * All settings.
     *
     * @return array
     */
    public static function all() {
        $stored = get_option(self::OPTION_KEY, array());
        if (!is_array($stored)) {
            $stored = array();
        }
        return array_merge(self::defaults(), $stored);
    }

    /**
     * Get one setting.
     *
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    public static function get($key, $default = null) {
        $all = self::all();
        if (!array_key_exists($key, $all)) {
            return $default;
        }
        return $all[$key];
    }

    /**
     * Update settings (partial merge).
     *
     * @param array $partial
     */
    public static function update(array $partial) {
        $all = self::all();
        update_option(self::OPTION_KEY, array_merge($all, $partial), false);
    }

    /**
     * Whether the current user is unrestricted (full menus, stock dashboard, no chrome stripping).
     *
     * Multisite: network super admins are always unrestricted.
     * Single site: only IDs in unrestricted_user_ids (is_super_admin() is true for all
     * Administrators there, so it must not be used as the sole gate).
     *
     * @param int|null $user_id
     * @return bool
     */
    public static function is_unrestricted_user($user_id = null) {
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }
        $user_id = (int) $user_id;
        if ($user_id <= 0) {
            return false;
        }

        if (is_multisite() && is_super_admin($user_id)) {
            return true;
        }

        $ids = self::get('unrestricted_user_ids', array());
        if (!is_array($ids)) {
            $ids = array();
        }
        $ids = array_map('intval', $ids);

        return in_array($user_id, $ids, true);
    }

    /**
     * Primary role slug for the current user (first role).
     *
     * @param WP_User|null $user
     * @return string
     */
    public static function primary_role($user = null) {
        if (!$user instanceof WP_User) {
            $user = wp_get_current_user();
        }
        if (!$user || empty($user->roles) || !is_array($user->roles)) {
            return '';
        }
        return (string) reset($user->roles);
    }

    /**
     * Editable WP roles (excludes nothing by default; administrator can be restricted).
     *
     * @return array<string, string> role => label
     */
    public static function editable_roles() {
        if (!function_exists('get_editable_roles')) {
            require_once ABSPATH . 'wp-admin/includes/user.php';
        }
        $roles = get_editable_roles();
        $out   = array();
        foreach ($roles as $slug => $data) {
            $out[$slug] = translate_user_role($data['name']);
        }
        return $out;
    }

    /**
     * Sanitize full settings payload from Settings API.
     * Only updates fields belonging to the active settings tab.
     *
     * @param mixed $input
     * @return array
     */
    public static function sanitize($input) {
        $prev = self::all();

        if (!is_array($input)) {
            return $prev;
        }

        $out = $prev;
        $tab = isset($input['_active_tab']) ? sanitize_key($input['_active_tab']) : '';

        if ($tab === 'dashboard') {
            $out['dashboard_page_id'] = isset($input['dashboard_page_id'])
                ? absint($input['dashboard_page_id'])
                : 0;

            $out['dashboard_roles'] = array();
            if (!empty($input['dashboard_roles']) && is_array($input['dashboard_roles'])) {
                $valid_roles = array_keys(self::editable_roles());
                foreach ($input['dashboard_roles'] as $role) {
                    $role = sanitize_key($role);
                    if (in_array($role, $valid_roles, true)) {
                        $out['dashboard_roles'][] = $role;
                    }
                }
                $out['dashboard_roles'] = array_values(array_unique($out['dashboard_roles']));
            }
        }

        if ($tab === 'menus') {
            $valid_roles = array_keys(self::editable_roles());
            $enabled     = array();
            if (!empty($input['menu_visibility_enabled']) && is_array($input['menu_visibility_enabled'])) {
                foreach ($input['menu_visibility_enabled'] as $role) {
                    $role = sanitize_key($role);
                    if (in_array($role, $valid_roles, true)) {
                        $enabled[$role] = true;
                    }
                }
            }

            $visibility = array();
            $posted     = isset($input['menu_visibility']) && is_array($input['menu_visibility'])
                ? $input['menu_visibility']
                : array();

            foreach ($valid_roles as $role) {
                if (!isset($enabled[$role])) {
                    continue;
                }
                $slugs = isset($posted[$role]) && is_array($posted[$role]) ? $posted[$role] : array();
                $clean = array();
                foreach ($slugs as $slug) {
                    $slug = self::sanitize_menu_slug($slug);
                    if ($slug !== '') {
                        $clean[] = $slug;
                    }
                }
                $visibility[$role] = array_values(array_unique($clean));
            }
            $out['menu_visibility'] = $visibility;
        }

        if ($tab === 'chrome') {
            $out['hide_admin_notices']        = !empty($input['hide_admin_notices']) ? 1 : 0;
            $out['hide_help_screen_options']  = !empty($input['hide_help_screen_options']) ? 1 : 0;
            $out['footer_text']               = isset($input['footer_text'])
                ? wp_kses_post($input['footer_text'])
                : '';
        }

        if ($tab === 'access') {
            $out['unrestricted_user_ids'] = array();
            if (!empty($input['unrestricted_user_ids'])) {
                $raw = $input['unrestricted_user_ids'];
                if (is_array($raw)) {
                    $parts = $raw;
                } else {
                    $parts = preg_split('/[\s,;]+/', (string) $raw);
                }
                foreach ($parts as $id) {
                    $id = absint($id);
                    if ($id > 0) {
                        $out['unrestricted_user_ids'][] = $id;
                    }
                }
                $out['unrestricted_user_ids'] = array_values(array_unique($out['unrestricted_user_ids']));
            }
        }

        // Always preserve captured menu catalog.
        $out['captured_menus'] = isset($prev['captured_menus']) && is_array($prev['captured_menus'])
            ? $prev['captured_menus']
            : array();

        return $out;
    }

    /**
     * Sanitize a menu slug (may contain query strings / php files).
     *
     * @param string $slug
     * @return string
     */
    public static function sanitize_menu_slug($slug) {
        $slug = wp_unslash((string) $slug);
        $slug = remove_query_arg('return', $slug);
        // Allow typical WP admin slugs: index.php, edit.php?post_type=page, etc.
        $slug = preg_replace('/[^\w.\-?=&#%]/', '', $slug);
        return is_string($slug) ? $slug : '';
    }
}
