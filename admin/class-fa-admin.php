<?php
/**
 * Admin assets / menu registration helper.
 */

if (!defined('ABSPATH')) {
    exit;
}

class FA_Admin {

    public function __construct() {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    public function enqueue_assets($hook) {
        if (strpos($hook, FA_MENU_SLUG) === false) {
            return;
        }

        wp_enqueue_style(
            'fa-admin',
            FA_PLUGIN_URL . 'admin/css/admin.css',
            array(),
            FA_VERSION
        );

        wp_enqueue_script(
            'fa-admin-settings',
            FA_PLUGIN_URL . 'admin/js/admin-settings.js',
            array(),
            FA_VERSION,
            true
        );
    }
}
