<?php
/**
 * Plugin loader.
 */

if (!defined('ABSPATH')) {
    exit;
}

class FA_Loader {

    public function __construct() {
        $this->load_dependencies();
    }

    private function load_dependencies() {
        require_once FA_PLUGIN_DIR . 'includes/class-fa-activator.php';
        require_once FA_PLUGIN_DIR . 'includes/class-fa-settings.php';
        require_once FA_PLUGIN_DIR . 'includes/class-fa-dashboard.php';
        require_once FA_PLUGIN_DIR . 'includes/class-fa-menus.php';
        require_once FA_PLUGIN_DIR . 'includes/class-fa-chrome.php';

        if (is_admin()) {
            require_once FA_PLUGIN_DIR . 'admin/class-fa-admin.php';
            require_once FA_PLUGIN_DIR . 'admin/class-fa-admin-settings.php';
        }
    }

    public function run() {
        FA_Settings::ensure_defaults();

        if (!is_admin()) {
            return;
        }

        new FA_Dashboard();
        new FA_Menus();
        new FA_Chrome();
        new FA_Admin();
        new FA_Admin_Settings();
    }
}
