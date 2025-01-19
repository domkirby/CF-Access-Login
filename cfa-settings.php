<?php

namespace CF_Access_Login;

class Settings {
    
    public function __construct() {
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    public function add_settings_page() {
        add_options_page(
            __('CF Access Login Settings', 'cf-access-login'),
            __('CF Access Login', 'cf-access-login'),
            'manage_options',
            'cf-access-login',
            [$this, 'create_settings_page']
        );
    }

    public function create_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php _e('CF Access Login Settings', 'cf-access-login'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('cf_access_login_settings_group');
                do_settings_sections('cf-access-login');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function register_settings() {
        if (!current_user_can('manage_options')) {
            return;
        }

        register_setting('cf_access_login_settings_group', 'cf_access_login_team_name');
        register_setting('cf_access_login_settings_group', 'cf_access_login_app_audience_id');
        register_setting('cf_access_login_settings_group', 'cf_access_login_auto_redirect', [
            'sanitize_callback' => [$this, 'sanitize_checkbox']
        ]);
        register_setting('cf_access_login_settings_group', 'cf_access_login_logout_on_wp_logout', [
            'sanitize_callback' => [$this, 'sanitize_checkbox']
        ]);

        add_settings_section(
            'cf_access_login_settings_section',
            __('CF Access Login Settings', 'cf-access-login'),
            null,
            'cf-access-login'
        );

        add_settings_field(
            'cf_access_login_team_name',
            __('Cloudflare Team Name', 'cf-access-login'),
            [$this, 'team_name_callback'],
            'cf-access-login',
            'cf_access_login_settings_section'
        );

        add_settings_field(
            'cf_access_login_app_audience_id',
            __('App Audience ID', 'cf-access-login'),
            [$this, 'app_audience_id_callback'],
            'cf-access-login',
            'cf_access_login_settings_section'
        );

        add_settings_field(
            'cf_access_login_auto_redirect',
            __('Auto Redirect Setting (Automatically redirects wp-login to wp-admin to trigger your Access rule)', 'cf-access-login'),
            [$this, 'auto_redirect_callback'],
            'cf-access-login',
            'cf_access_login_settings_section'
        );

        add_settings_field(
            'cf_access_login_logout_on_wp_logout',
            __('Logout from Cloudflare on WordPress Logout (unchecking this will prevent logging out)', 'cf-access-login'),
            [$this, 'logout_on_wp_logout_callback'],
            'cf-access-login',
            'cf_access_login_settings_section'
        );
    }

    public function sanitize_checkbox($input) {
        return $input === 'on' ? true : false;
    }

    public function team_name_callback() {
        $value = get_option('cf_access_login_team_name', '');
        echo '<input type="text" id="cf_access_login_team_name" name="cf_access_login_team_name" value="' . esc_attr($value) . '" />';
    }

    public function app_audience_id_callback() {
        $value = get_option('cf_access_login_app_audience_id', '');
        echo '<input type="text" id="cf_access_login_app_audience_id" name="cf_access_login_app_audience_id" value="' . esc_attr($value) . '" />';
    }

    public function auto_redirect_callback() {
        $value = get_option('cf_access_login_auto_redirect', false);
        echo '<input type="checkbox" id="cf_access_login_auto_redirect" name="cf_access_login_auto_redirect" ' . checked($value, true, false) . ' />';
    }

    public function logout_on_wp_logout_callback() {
        $value = get_option('cf_access_login_logout_on_wp_logout', true);
        echo '<input type="checkbox" id="cf_access_login_logout_on_wp_logout" name="cf_access_login_logout_on_wp_logout" ' . checked($value, true, false) . ' />';
    }
}

new Settings();