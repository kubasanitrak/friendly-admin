<?php
/**
 * Plugin Name: Friendly Admin
 * Plugin URI: https://github.com/kubasanitrak/friendly-admin
 * Description: Client-friendly WordPress admin — custom dashboard page and role-based menu visibility.
 * Version: 0.1.0
 * Author: kubasanitrak
 * Author URI: https://github.com/kubasanitrak
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: friendly-admin
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

define('FA_VERSION', '0.1.0');
define('FA_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('FA_PLUGIN_URL', plugin_dir_url(__FILE__));
define('FA_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('FA_MENU_SLUG', 'friendly-admin');

/**
 * GitHub release updates (Plugin Update Checker).
 */
require_once FA_PLUGIN_DIR . 'lib/plugin-update-checker/plugin-update-checker.php';

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$fa_update_checker = PucFactory::buildUpdateChecker(
    'https://github.com/kubasanitrak/friendly-admin/',
    __FILE__,
    'friendly-admin'
);
$fa_update_checker->getVcsApi()->enableReleaseAssets('/friendly-admin\.zip($|[?&#])/i');

/**
 * Activation.
 */
function fa_activate() {
    require_once FA_PLUGIN_DIR . 'includes/class-fa-activator.php';
    FA_Activator::activate();
}
register_activation_hook(__FILE__, 'fa_activate');

require_once FA_PLUGIN_DIR . 'includes/class-fa-loader.php';

/**
 * Initialize the plugin.
 */
function fa_init() {
    $loader = new FA_Loader();
    $loader->run();
}
add_action('plugins_loaded', 'fa_init');

/**
 * Load translations.
 */
function fa_load_textdomain() {
    load_plugin_textdomain(
        'friendly-admin',
        false,
        dirname(FA_PLUGIN_BASENAME) . '/languages'
    );
}
add_action('init', 'fa_load_textdomain');
