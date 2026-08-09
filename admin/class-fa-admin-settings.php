<?php
/**
 * Friendly Admin settings screen.
 */

if (!defined('ABSPATH')) {
    exit;
}

class FA_Admin_Settings {

    public function __construct() {
        add_action('admin_menu', array($this, 'register_menu'), 9);
        add_action('admin_init', array($this, 'register_settings'));
    }

    public function register_menu() {
        add_menu_page(
            __('Friendly Admin', 'friendly-admin'),
            __('Friendly Admin', 'friendly-admin'),
            'manage_options',
            FA_MENU_SLUG,
            array($this, 'render_page'),
            'dashicons-admin-generic',
            81
        );
    }

    public function register_settings() {
        register_setting(
            'fa_settings_group',
            FA_Settings::OPTION_KEY,
            array(
                'type'              => 'array',
                'sanitize_callback' => array('FA_Settings', 'sanitize'),
                'default'           => FA_Settings::defaults(),
            )
        );
    }

    public function render_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Only unrestricted users should configure (super admin + allowlist).
        // manage_options admins who are restricted still shouldn't lock themselves out
        // of this screen if they somehow open it — but URL guard may redirect.
        if (!FA_Settings::is_unrestricted_user()) {
            echo '<div class="wrap"><h1>' . esc_html__('Friendly Admin', 'friendly-admin') . '</h1>';
            echo '<div class="notice notice-error"><p>' .
                esc_html__('Toto nastavení mohou měnit pouze neomezení uživatelé (super admin nebo ID ze seznamu).', 'friendly-admin') .
                '</p></div></div>';
            return;
        }

        $settings = FA_Settings::all();
        $roles    = FA_Settings::editable_roles();
        $menus    = $this->get_menu_catalog();
        $tab      = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : 'dashboard';
        $tabs     = array(
            'dashboard' => __('Nástěnka', 'friendly-admin'),
            'menus'     => __('Menu', 'friendly-admin'),
            'chrome'    => __('Rozhraní', 'friendly-admin'),
            'access'    => __('Přístup', 'friendly-admin'),
        );
        if (!isset($tabs[$tab])) {
            $tab = 'dashboard';
        }

        include FA_PLUGIN_DIR . 'admin/partials/settings-page.php';
    }

    /**
     * @return array<int, array{name:string,slug:string}>
     */
    private function get_menu_catalog() {
        $captured = FA_Settings::get('captured_menus', array());
        if (is_array($captured) && !empty($captured)) {
            return array_values($captured);
        }
        return array();
    }
}
