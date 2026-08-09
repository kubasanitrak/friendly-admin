<?php
/**
 * Admin chrome cleanup for restricted users.
 */

if (!defined('ABSPATH')) {
    exit;
}

class FA_Chrome {

    public function __construct() {
        add_action('admin_init', array($this, 'init'), 20);
        add_filter('admin_footer_text', array($this, 'footer_text'), 99);
    }

    /**
     * Whether chrome restrictions apply.
     *
     * @return bool
     */
    private function should_restrict() {
        return !FA_Settings::is_unrestricted_user();
    }

    public function init() {
        if (!$this->should_restrict()) {
            return;
        }

        if ((int) FA_Settings::get('hide_admin_notices', 1) === 1) {
            add_action('admin_head', array($this, 'strip_admin_notices'), 1);
            add_action('admin_enqueue_scripts', array($this, 'enqueue_notice_css'), 100);
        }

        if ((int) FA_Settings::get('hide_help_screen_options', 1) === 1) {
            add_action('admin_head', array($this, 'hide_help_and_screen_options'));
            add_filter('screen_options_show_screen', '__return_false');
        }
    }

    /**
     * Remove notice callbacks from common hooks (best-effort).
     */
    public function strip_admin_notices() {
        $hooks = array(
            'admin_notices',
            'all_admin_notices',
            'network_admin_notices',
            'user_admin_notices',
        );
        foreach ($hooks as $hook) {
            remove_all_actions($hook);
        }
    }

    public function enqueue_notice_css() {
        $css = '.update-nag,.notice,.is-dismissible,#wpfooter .update-nag{display:none!important}';
        wp_register_style('fa-chrome-notices', false, array(), FA_VERSION);
        wp_enqueue_style('fa-chrome-notices');
        wp_add_inline_style('fa-chrome-notices', $css);
    }

    public function hide_help_and_screen_options() {
        $screen = get_current_screen();
        if ($screen) {
            $screen->remove_help_tabs();
        }
        echo '<style id="fa-hide-help-screen">#screen-meta-links,#screen-meta,#contextual-help-link-wrap{display:none!important}</style>';
    }

    /**
     * @param string $text
     * @return string
     */
    public function footer_text($text) {
        $custom = FA_Settings::get('footer_text', '');
        if (!is_string($custom) || trim($custom) === '') {
            return $text;
        }
        // Show custom footer to everyone when set (including unrestricted).
        return wp_kses_post($custom);
    }
}
