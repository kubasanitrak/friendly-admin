<?php
/**
 * Custom dashboard content from a selected WP page.
 */

if (!defined('ABSPATH')) {
    exit;
}

class FA_Dashboard {

    public function __construct() {
        add_action('wp_dashboard_setup', array($this, 'setup'), 999);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_dashboard_assets'));
        add_filter('admin_body_class', array($this, 'body_class'));
    }

    /**
     * Whether custom dashboard applies to the current user.
     *
     * @return bool
     */
    public function should_replace() {
        if (FA_Settings::is_unrestricted_user()) {
            return false;
        }

        $page_id = (int) FA_Settings::get('dashboard_page_id', 0);
        if ($page_id <= 0) {
            return false;
        }

        $post = get_post($page_id);
        if (!$post || $post->post_type !== 'page' || $post->post_status !== 'publish') {
            return false;
        }

        $roles = FA_Settings::get('dashboard_roles', array());
        if (!is_array($roles) || empty($roles)) {
            return false;
        }

        $role = FA_Settings::primary_role();
        return $role !== '' && in_array($role, $roles, true);
    }

    public function setup() {
        if (!$this->should_replace()) {
            return;
        }

        $this->remove_default_widgets();
        remove_action('welcome_panel', 'wp_welcome_panel');

        wp_add_dashboard_widget(
            'fa_custom_dashboard',
            $this->widget_title(),
            array($this, 'render_widget')
        );
    }

    /**
     * @return string
     */
    private function widget_title() {
        $page_id = (int) FA_Settings::get('dashboard_page_id', 0);
        $title   = $page_id ? get_the_title($page_id) : '';
        if ($title === '') {
            $title = __('Nástěnka', 'friendly-admin');
        }
        return $title;
    }

    private function remove_default_widgets() {
        global $wp_meta_boxes;

        if (!isset($wp_meta_boxes['dashboard']) || !is_array($wp_meta_boxes['dashboard'])) {
            return;
        }

        foreach ($wp_meta_boxes['dashboard'] as $context => $priorities) {
            if (!is_array($priorities)) {
                continue;
            }
            foreach ($priorities as $priority => $widgets) {
                if (!is_array($widgets)) {
                    continue;
                }
                foreach (array_keys($widgets) as $widget_id) {
                    if ($widget_id === 'fa_custom_dashboard') {
                        continue;
                    }
                    remove_meta_box($widget_id, 'dashboard', $context);
                }
            }
        }
    }

    public function render_widget() {
        $page_id = (int) FA_Settings::get('dashboard_page_id', 0);
        $post    = get_post($page_id);
        if (!$post) {
            echo '<p>' . esc_html__('Vybraná stránka nebyla nalezena.', 'friendly-admin') . '</p>';
            return;
        }

        $content = $post->post_content;
        if (function_exists('do_blocks')) {
            $content = do_blocks($content);
        }
        $content = apply_filters('the_content', $content);

        echo '<div class="fa-dashboard">';
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- filtered via the_content / do_blocks
        echo $content;
        echo '</div>';
    }

    public function enqueue_dashboard_assets($hook) {
        if ($hook !== 'index.php') {
            return;
        }
        if (!$this->should_replace()) {
            return;
        }

        wp_enqueue_style(
            'fa-admin',
            FA_PLUGIN_URL . 'admin/css/admin.css',
            array(),
            FA_VERSION
        );
    }

    /**
     * @param string $classes
     * @return string
     */
    public function body_class($classes) {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if ($screen && $screen->id === 'dashboard' && $this->should_replace()) {
            $classes .= ' fa-custom-dashboard';
        }
        return $classes;
    }
}
