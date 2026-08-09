<?php
/**
 * Settings page markup.
 *
 * @var array  $settings
 * @var array  $roles
 * @var array  $menus
 * @var string $tab
 * @var array  $tabs
 */

if (!defined('ABSPATH')) {
    exit;
}

$option_key = FA_Settings::OPTION_KEY;
$base_url   = admin_url('admin.php?page=' . FA_MENU_SLUG);
?>
<div class="wrap fa-settings">
    <h1><?php esc_html_e('Friendly Admin', 'friendly-admin'); ?></h1>

    <nav class="nav-tab-wrapper fa-settings__tabs">
        <?php foreach ($tabs as $slug => $label) : ?>
            <a href="<?php echo esc_url(add_query_arg('tab', $slug, $base_url)); ?>"
               class="nav-tab <?php echo $tab === $slug ? 'nav-tab-active' : ''; ?>">
                <?php echo esc_html($label); ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <form method="post" action="options.php" class="fa-settings__form">
        <?php settings_fields('fa_settings_group'); ?>
        <input type="hidden" name="<?php echo esc_attr($option_key); ?>[_active_tab]" value="<?php echo esc_attr($tab); ?>">

        <?php if ($tab === 'dashboard') : ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">
                        <label for="fa_dashboard_page_id"><?php esc_html_e('Stránka pro nástěnku', 'friendly-admin'); ?></label>
                    </th>
                    <td>
                        <?php
                        wp_dropdown_pages(array(
                            'name'              => $option_key . '[dashboard_page_id]',
                            'id'                => 'fa_dashboard_page_id',
                            'selected'          => (int) $settings['dashboard_page_id'],
                            'show_option_none'  => __('— nevybráno —', 'friendly-admin'),
                            'option_none_value' => '0',
                        ));
                        ?>
                        <p class="description">
                            <?php esc_html_e('Obsah této stránky se zobrazí místo výchozí WP nástěnky vybraným rolím. Neomezení uživatelé vidí standardní nástěnku.', 'friendly-admin'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Role s vlastní nástěnkou', 'friendly-admin'); ?></th>
                    <td>
                        <fieldset class="fa-checkboxes">
                            <?php foreach ($roles as $role_slug => $role_label) : ?>
                                <label>
                                    <input type="checkbox"
                                           name="<?php echo esc_attr($option_key); ?>[dashboard_roles][]"
                                           value="<?php echo esc_attr($role_slug); ?>"
                                        <?php checked(in_array($role_slug, (array) $settings['dashboard_roles'], true)); ?>>
                                    <?php echo esc_html($role_label); ?>
                                    <code><?php echo esc_html($role_slug); ?></code>
                                </label>
                            <?php endforeach; ?>
                        </fieldset>
                    </td>
                </tr>
            </table>
        <?php elseif ($tab === 'menus') : ?>
            <p class="description fa-settings__intro">
                <?php esc_html_e('Zaškrtnuté položky se zobrazí v levém menu. Role bez uložené konfigurace nejsou omezeny. Neomezení uživatelé (a na multisite network super admini) mají vždy všechna menu.', 'friendly-admin'); ?>
            </p>
            <?php if (empty($menus)) : ?>
                <div class="notice notice-warning inline">
                    <p><?php esc_html_e('Seznam menu ještě není načten. Obnovte tuto stránku (jako neomezený uživatel), aby se menu zachytila.', 'friendly-admin'); ?></p>
                </div>
            <?php else : ?>
                <div class="fa-menu-roles" data-fa-menu-roles>
                    <div class="fa-menu-roles__nav">
                        <?php
                        $i = 0;
                        foreach ($roles as $role_slug => $role_label) :
                            ?>
                            <button type="button"
                                    class="button fa-menu-roles__tab <?php echo $i === 0 ? 'is-active' : ''; ?>"
                                    data-fa-role-tab="<?php echo esc_attr($role_slug); ?>">
                                <?php echo esc_html($role_label); ?>
                            </button>
                            <?php
                            $i++;
                        endforeach;
                        ?>
                    </div>
                    <?php
                    $i = 0;
                    foreach ($roles as $role_slug => $role_label) :
                        $allowed    = isset($settings['menu_visibility'][$role_slug]) && is_array($settings['menu_visibility'][$role_slug])
                            ? $settings['menu_visibility'][$role_slug]
                            : null;
                        $has_config = is_array($allowed);
                        ?>
                        <div class="fa-menu-roles__panel <?php echo $i === 0 ? 'is-active' : ''; ?>"
                             data-fa-role-panel="<?php echo esc_attr($role_slug); ?>"
                             <?php echo $i === 0 ? '' : 'hidden'; ?>>
                            <div class="fa-menu-roles__actions">
                                <button type="button" class="button button-small" data-fa-select-all><?php esc_html_e('Vybrat vše', 'friendly-admin'); ?></button>
                                <button type="button" class="button button-small" data-fa-select-none><?php esc_html_e('Zrušit výběr', 'friendly-admin'); ?></button>
                                <label class="fa-menu-roles__enable">
                                    <input type="checkbox"
                                           name="<?php echo esc_attr($option_key); ?>[menu_visibility_enabled][]"
                                           value="<?php echo esc_attr($role_slug); ?>"
                                           data-fa-enable-role
                                        <?php checked($has_config); ?>>
                                    <?php esc_html_e('Omezit menu pro tuto roli', 'friendly-admin'); ?>
                                </label>
                            </div>
                            <fieldset class="fa-checkboxes fa-checkboxes--menus" data-fa-role-menus>
                                <?php foreach ($menus as $menu_row) :
                                    $slug = isset($menu_row['slug']) ? (string) $menu_row['slug'] : '';
                                    $name = isset($menu_row['name']) ? (string) $menu_row['name'] : $slug;
                                    if ($slug === '') {
                                        continue;
                                    }
                                    $checked = $has_config ? in_array($slug, $allowed, true) : true;
                                    ?>
                                    <label>
                                        <input type="checkbox"
                                               name="<?php echo esc_attr($option_key); ?>[menu_visibility][<?php echo esc_attr($role_slug); ?>][]"
                                               value="<?php echo esc_attr($slug); ?>"
                                            <?php checked($checked); ?>>
                                        <?php echo esc_html($name); ?>
                                        <code><?php echo esc_html($slug); ?></code>
                                    </label>
                                <?php endforeach; ?>
                            </fieldset>
                        </div>
                        <?php
                        $i++;
                    endforeach;
                    ?>
                </div>
            <?php endif; ?>
        <?php elseif ($tab === 'chrome') : ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><?php esc_html_e('Admin oznámení', 'friendly-admin'); ?></th>
                    <td>
                        <label>
                            <input type="hidden" name="<?php echo esc_attr($option_key); ?>[hide_admin_notices]" value="0">
                            <input type="checkbox"
                                   name="<?php echo esc_attr($option_key); ?>[hide_admin_notices]"
                                   value="1"
                                <?php checked((int) $settings['hide_admin_notices'], 1); ?>>
                            <?php esc_html_e('Skrýt admin notices pro omezené uživatele', 'friendly-admin'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Nápověda a volby obrazovky', 'friendly-admin'); ?></th>
                    <td>
                        <label>
                            <input type="hidden" name="<?php echo esc_attr($option_key); ?>[hide_help_screen_options]" value="0">
                            <input type="checkbox"
                                   name="<?php echo esc_attr($option_key); ?>[hide_help_screen_options]"
                                   value="1"
                                <?php checked((int) $settings['hide_help_screen_options'], 1); ?>>
                            <?php esc_html_e('Skrýt Help a Screen Options pro omezené uživatele', 'friendly-admin'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="fa_footer_text"><?php esc_html_e('Text v patičce adminu', 'friendly-admin'); ?></label>
                    </th>
                    <td>
                        <textarea id="fa_footer_text"
                                  name="<?php echo esc_attr($option_key); ?>[footer_text]"
                                  class="large-text"
                                  rows="3"><?php echo esc_textarea($settings['footer_text']); ?></textarea>
                        <p class="description">
                            <?php esc_html_e('Povoleno základní HTML. Prázdné = výchozí text WordPressu. Zobrazí se všem uživatelům.', 'friendly-admin'); ?>
                        </p>
                    </td>
                </tr>
            </table>
        <?php else : ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">
                        <label for="fa_unrestricted_user_ids"><?php esc_html_e('Neomezená uživatelská ID', 'friendly-admin'); ?></label>
                    </th>
                    <td>
                        <input type="text"
                               class="regular-text"
                               id="fa_unrestricted_user_ids"
                               name="<?php echo esc_attr($option_key); ?>[unrestricted_user_ids]"
                               value="<?php echo esc_attr(implode(', ', array_map('intval', (array) $settings['unrestricted_user_ids']))); ?>"
                               placeholder="1, 12">
                        <p class="description">
                            <?php esc_html_e('Čárkou oddělená ID. Tito uživatelé mají všechna menu, standardní nástěnku a bez chrome omezení. Na multisite jsou navíc vždy neomezení network super admini. Při aktivaci pluginu se sem přidá ID uživatele, který plugin aktivoval.', 'friendly-admin'); ?>
                        </p>
                    </td>
                </tr>
            </table>
        <?php endif; ?>

        <?php submit_button(__('Uložit nastavení', 'friendly-admin')); ?>
    </form>
</div>
