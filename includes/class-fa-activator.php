<?php
/**
 * Plugin activation.
 */

if (!defined('ABSPATH')) {
    exit;
}

class FA_Activator {

    public static function activate() {
        require_once FA_PLUGIN_DIR . 'includes/class-fa-settings.php';
        FA_Settings::ensure_defaults();

        // Bootstrap: activating user becomes unrestricted so they can configure the plugin.
        $user_id = get_current_user_id();
        if ($user_id > 0) {
            $settings = FA_Settings::all();
            $ids      = isset($settings['unrestricted_user_ids']) && is_array($settings['unrestricted_user_ids'])
                ? array_map('intval', $settings['unrestricted_user_ids'])
                : array();
            if (!in_array($user_id, $ids, true)) {
                $ids[] = $user_id;
                FA_Settings::update(array('unrestricted_user_ids' => array_values(array_unique($ids))));
            }
        }
    }
}
