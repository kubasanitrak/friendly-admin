<?php
/**
 * Role-based main sidebar menu visibility + URL guard.
 */

if (!defined('ABSPATH')) {
    exit;
}

class FA_Menus {

    /** @var array<string, array{name:string,slug:string}> */
    private $menus = array();

    public function __construct() {
        // Capture after other plugins register menus; also used on settings screen.
        add_action('admin_menu', array($this, 'capture_and_filter'), 99999);
        add_action('admin_init', array($this, 'guard_url_access'), 1);
    }

    /**
     * Capture menu list for settings; filter for restricted users.
     */
    public function capture_and_filter() {
        $this->compile_menus();
        $this->persist_captured_menus();
        $this->apply_visibility();
    }

    private function compile_menus() {
        global $menu;

        $output = array();
        if (!is_array($menu)) {
            $this->menus = $output;
            return;
        }

        foreach ($menu as $item) {
            if (!isset($item[0], $item[2])) {
                continue;
            }
            // Separators.
            if ($item[0] === '' || strpos((string) $item[4], 'wp-menu-separator') !== false) {
                continue;
            }
            // Always keep Friendly Admin settings reachable for unrestricted / manage_options users —
            // still list it so it can be toggled for restricted roles.
            $name = wp_strip_all_tags($item[0]);
            $name = trim(preg_replace('/\d+$/', '', $name));
            $slug = remove_query_arg('return', $item[2]);

            $output[$slug] = array(
                'name' => $name !== '' ? $name : $slug,
                'slug' => $slug,
            );
        }

        $this->menus = $output;
    }

    /**
     * Store menu catalog for settings UI (when an unrestricted user loads admin).
     */
    private function persist_captured_menus() {
        if (!FA_Settings::is_unrestricted_user() && !current_user_can('manage_options')) {
            return;
        }

        // Prefer capturing on our settings screen so list is complete.
        $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
        if ($page !== FA_MENU_SLUG && !self::is_settings_request()) {
            // Still refresh occasionally when unrestricted user hits any admin page
            // if we have no catalog yet.
            $existing = FA_Settings::get('captured_menus', array());
            if (is_array($existing) && !empty($existing)) {
                return;
            }
        }

        $list = array_values($this->menus);
        FA_Settings::update(array('captured_menus' => $list));
    }

    /**
     * @return bool
     */
    private static function is_settings_request() {
        if (!is_admin()) {
            return false;
        }
        $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
        return $page === FA_MENU_SLUG;
    }

    /**
     * @return array<string, array{name:string,slug:string}>
     */
    public function get_menus() {
        if (!empty($this->menus)) {
            return $this->menus;
        }
        $captured = FA_Settings::get('captured_menus', array());
        if (!is_array($captured)) {
            return array();
        }
        $out = array();
        foreach ($captured as $row) {
            if (!is_array($row) || empty($row['slug'])) {
                continue;
            }
            $slug = (string) $row['slug'];
            $out[$slug] = array(
                'name' => isset($row['name']) ? (string) $row['name'] : $slug,
                'slug' => $slug,
            );
        }
        return $out;
    }

    /**
     * Whether menu filtering applies to current user.
     *
     * @return bool
     */
    public function should_filter() {
        if (FA_Settings::is_unrestricted_user()) {
            return false;
        }

        $role = FA_Settings::primary_role();
        if ($role === '') {
            return false;
        }

        $visibility = FA_Settings::get('menu_visibility', array());
        if (!is_array($visibility) || !isset($visibility[$role]) || !is_array($visibility[$role])) {
            // No config for this role → do not filter (safe default).
            return false;
        }

        return true;
    }

    /**
     * Allowed main menu slugs for current user role.
     *
     * @return string[]|null null if not filtering
     */
    public function allowed_slugs() {
        if (!$this->should_filter()) {
            return null;
        }
        $role       = FA_Settings::primary_role();
        $visibility = FA_Settings::get('menu_visibility', array());
        $slugs      = isset($visibility[$role]) && is_array($visibility[$role])
            ? $visibility[$role]
            : array();
        return array_map(array('FA_Settings', 'sanitize_menu_slug'), $slugs);
    }

    private function apply_visibility() {
        $allowed = $this->allowed_slugs();
        if ($allowed === null) {
            return;
        }

        global $menu;
        if (!is_array($menu)) {
            return;
        }

        $allowed_lookup = array_fill_keys($allowed, true);

        foreach ($menu as $i => $item) {
            if (!isset($item[2])) {
                continue;
            }
            if ($item[0] === '' || (isset($item[4]) && strpos((string) $item[4], 'wp-menu-separator') !== false)) {
                continue;
            }
            $slug = remove_query_arg('return', $item[2]);
            if (!isset($allowed_lookup[$slug])) {
                unset($menu[$i]);
            }
        }
    }

    /**
     * Block direct URL access to hidden screens.
     */
    public function guard_url_access() {
        if (!is_admin() || wp_doing_ajax() || (defined('DOING_CRON') && DOING_CRON)) {
            return;
        }

        $allowed = $this->allowed_slugs();
        if ($allowed === null) {
            return;
        }

        // Always allow profile / form / AJAX endpoints that are not top-level menu items.
        $pagenow = isset($GLOBALS['pagenow']) ? (string) $GLOBALS['pagenow'] : '';
        $always  = array(
            'profile.php',
            'admin-ajax.php',
            'admin-post.php',
            'async-upload.php',
            'media-upload.php',
            'options.php',
            'load-scripts.php',
            'load-styles.php',
        );
        if (in_array($pagenow, $always, true)) {
            return;
        }

        $current_slug = $this->current_menu_slug();
        if ($current_slug === '') {
            return;
        }

        if (in_array($current_slug, $allowed, true)) {
            return;
        }

        wp_safe_redirect($this->fallback_admin_url($allowed));
        exit;
    }

    /**
     * First safe admin URL from an allowlist of menu slugs.
     *
     * @param string[] $allowed
     * @return string
     */
    private function fallback_admin_url(array $allowed) {
        if (in_array('index.php', $allowed, true)) {
            return admin_url('index.php');
        }

        foreach ($allowed as $slug) {
            $slug = (string) $slug;
            if ($slug === '') {
                continue;
            }
            if (strpos($slug, '.php') !== false) {
                return admin_url($slug);
            }
            return admin_url('admin.php?page=' . rawurlencode($slug));
        }

        return admin_url('index.php');
    }

    /**
     * Best-effort current top-level menu slug for the request.
     *
     * @return string
     */
    private function current_menu_slug() {
        global $plugin_page, $pagenow, $typenow;

        if (!empty($plugin_page) && is_string($plugin_page)) {
            // Plugin pages are registered as parent slug or as page=slug under a parent.
            // Match against captured main menu slugs first.
            $menus = $this->get_menus();
            if (isset($menus[$plugin_page])) {
                return $plugin_page;
            }
            // Parent may be in $_GET['page'] for top-level add_menu_page.
            return $plugin_page;
        }

        if (!empty($pagenow) && $pagenow === 'edit.php' && !empty($typenow)) {
            return 'edit.php?post_type=' . $typenow;
        }

        if (!empty($pagenow) && $pagenow === 'post-new.php' && !empty($typenow)) {
            return 'edit.php?post_type=' . $typenow;
        }

        if (!empty($pagenow) && in_array($pagenow, array('post.php', 'post-new.php'), true)) {
            $post_type = 'post';
            if (!empty($_GET['post'])) {
                $post = get_post(absint($_GET['post']));
                if ($post) {
                    $post_type = $post->post_type;
                }
            } elseif (!empty($_GET['post_type'])) {
                $post_type = sanitize_key(wp_unslash($_GET['post_type']));
            }
            if ($post_type === 'page') {
                return 'edit.php?post_type=page';
            }
            if ($post_type !== 'post') {
                return 'edit.php?post_type=' . $post_type;
            }
            return 'edit.php';
        }

        if (!empty($pagenow)) {
            return (string) $pagenow;
        }

        return '';
    }
}
