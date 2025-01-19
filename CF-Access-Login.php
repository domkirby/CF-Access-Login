<?php
/**
 * Plugin Name: CF Access Login
 * Plugin URI: https://github.com/domkirby/CF-Access-Login
 * Description: A plugin to enable Cloudflare Access login for WordPress
 * Version: 0.9.14
 * Author: Dom Kirby
 * Author URI: https://domkirby.com
 * License: MIT
 * License URI: https://opensource.org/licenses/MIT
 * Text Domain: cf-access-login
 * Domain Path: /languages
 */

 namespace CF_Access_Login;

 //If this file is called directly, abort.
    if ( ! defined( 'WPINC' ) ) {
        die;
    }

// JWT Classes from composer
require_once(__DIR__ . '/vendor/autoload.php');

//Updater
require_once(__DIR__ . '/github-updater.php');

//Settings
require_once __DIR__ . '/cfa-settings.php';

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;

define('WP_CF_ACCESS_JWT_ALGORITHM', ['RS256']);
define('WP_CF_ACCESS_RETRY', 1);
define('WP_CF_ACCESS_CACHE_KEY', 'cf_access_jwt_keys');

//$settings = new Settings();
$client_jwt = $_COOKIE['CF_Authorization'] ?? '';


function get_auth_domain() {
    return get_option('cf_access_login_team_name');
}

function get_jwt_audience() {
    return get_option('cf_access_login_app_audience_id');
}

function get_redirect_login() {
    return get_option('cf_access_login_auto_redirect');
}

function get_logout_on_wp_logout() {
    return get_option('cf_access_login_logout_on_wp_logout');
}

function get_jwks_uri() {
    $domain = get_auth_domain() . '.cloudflareaccess.com';

    return "https://$domain/cdn-cgi/access/certs";
}

function fetch_jwks() {
    $jwks_uri = get_jwks_uri();
    $response = wp_remote_get($jwks_uri);

    if (is_wp_error($response)) {
        return false;
    }

    $body = wp_remote_retrieve_body($response);
    $jwks = json_decode($body, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        return false;
    }

    wp_cache_set(WP_CF_ACCESS_CACHE_KEY, $jwks);

    return $jwks;
}

function get_jwks() {
    $jwks = wp_cache_get(WP_CF_ACCESS_CACHE_KEY);

    if ($jwks === false) {
        $jwks = fetch_jwks();
    }

    return $jwks;
}

function verify_audience($jwt) {
    $audience = get_jwt_audience();

    if ($audience === null) {
        return false;
    }

    if (!isset($jwt->aud)) {
        return false;
    }
    if(is_array($jwt->aud)) {
        return in_array($audience, $jwt->aud);
    } else {
        return $audience == $jwt->aud;
    }
}

function verify_issuer($jwt)
{
    $issuer = "https://" . get_auth_domain() . ".cloudflareaccess.com";
    if(!isset($jwt->iss)) {
        return false;
    } else {
        return $issuer == $jwt->iss;
    }
    
}

function display_loggedin_token_alert() {
    // Output the notice
    echo '<div class="notice notice-success is-dismissible">';
    echo '<p>' . esc_html__('You have been logged in with your Cloudflare Access token.', 'cf-access-login') . '</p>';
    echo '</div>';
}

function login_page_cf_failed_message($message)
{
    $warning_message = '<div class="message error" style="text-align: center;">' . 
        __('Cloudflare login failed with your token. Please login manually to troubleshoot.', 'cf-access-login') . 
        '</div>';

    return $message . $warning_message;
}

function try_login() {
    global $client_jwt;
    if($client_jwt == '') {
        return;
    }
    if(!get_auth_domain() || !get_jwt_audience()) {
        return;
    }
    $jwks = get_jwks();

    if ($jwks === false) {
        return;
    }

    

    $recognized = false;
    $user = null;
    $user_id = 0;
    $retries = 0;

    if($client_jwt != '') {
        $jwt = $client_jwt;
        JWT::$leeway = 60;
        

        while (!$recognized && $retries < WP_CF_ACCESS_RETRY) {
            try {
                $jwt = JWT::decode($jwt, JWK::parseKeySet($jwks));
                
                if (!verify_audience($jwt)) {
                    return;
                }
                if(!verify_issuer($jwt)) {
                    $args = array(
                        'response' => 500,
                        'link_url' => '/cdn-cgi/access/logout',
                        'link_text' => __('Log out and try again', 'cf-access-login'),
                        'exit' => true
                    );
                    wp_die( __('There is an issue with the JWT issued by Cloudflare, because "issuer" (iss) field is incorrect.'), __('Malformed JWT'), $args );
                }

                $email = $jwt->email;
                $user = get_user_by('email', $email);
                $user_id = $user->ID;
                $recognized = true;
            } catch(\UnexpectedValueException $e) {
                $jwks = fetch_jwks();
                $retries++;
            } catch (Exception $e) {
                return;
            }
        }

        if ($recognized) {
            $current_user = wp_get_current_user();
            if($current_user->ID  == $user_id) {
                return;
            }
            if($user_id > 0) {
                wp_set_current_user($user_id);
                wp_set_auth_cookie($user_id);
                add_action( 'init', function() use($user) {
                    do_action('wp_login', $user->name, $user);
                    $returnUrl = $_GET['redirect_to'] ?? admin_url();
                    if(str_contains($returnUrl, '?')) {
                        $cf_login_query = "&cf_access_token_loggedin=1";
                    } else {
                        $cf_login_query = "?cf_access_token_loggedin=1";
                    }
                    $returnUrl = $_SERVER['REQUEST_URI'];
                    wp_safe_redirect($returnUrl . $cf_login_query, 302, 'CFA-Login');
                    exit;
                });
                
                wp_safe_redirect(admin_url());
            }
        } elseif($user_id = 0) {
            wp_logout();
            wp_set_current_user(0);
        } elseif(get_redirect_login()) {
            $args = array(
                'response' => 500,
                'link_url' => '/cdn-cgi/access/logout',
                'link_text' => __('Log out and try again', 'cf-access-login'),
                'exit' => true
            );
            error_log('CFA JWT Login Failed due to not found user.');
            $error = __('The user in the JWT could not be found in WordPress, or there was a problem with the token.', 'cf-access-login');
            wp_die($error, '', $args);
        }
    }
}

function show_localhost_warning_login_page($message)
{
    $custom_message = '<p class="notice notice-info message">' . 
        __('Cloudflare auto redirection enabled, but you are on localhost. Assuming dev and cancelling the redirect.', 'cf-access-login') . 
        '</p>';

    return $message . $custom_message;
}

function login_redirect()
{
    if(!get_auth_domain() || !get_jwt_audience()) {
        return;
    }

    if(get_redirect_login()) {
        $hostname = $_SERVER['HTTP_HOST'];
        if($hostname == "localhost") {
            add_filter( 'login_message', __NAMESPACE__ . '\\show_localhost_warning_login_page' );
            return;
        }
        if(!is_user_logged_in()) {
            wp_safe_redirect( admin_url(), 302, 'CFA-Login' );
            exit;
        }
    }
}

function logout_redirect()
{
    if(!get_auth_domain() || !get_jwt_audience()) {
        return;
    }
    if(get_logout_on_wp_logout()) {
        $hostname = $_SERVER['HTTP_HOST'];
        if($hostname == "localhost") {
            add_filter( 'login_message', __NAMESPACE__ . '\\show_localhost_warning_login_page' );
            return;
        }
        
        if(wp_safe_redirect( '/cdn-cgi/access/logout', 302, 'CFA-Login' )) {
            exit;
        }
    }
}

function admin_page_misconfig_warning() {
    echo '<div class="notice notice-warning">';
    echo '<p>' . __('<strong>WARNING:</strong> Cloudflare Access Login is enabled but is missing settings!', 'cf-access-login');
    echo '&nbsp;<a href="' . admin_url( 'options-general.php?page=cf-access-login' ) . '">' . __('Go to Options', 'cf-access-login') . '</a></p>';
    echo '</div>';
}

if(!get_auth_domain() || !get_jwt_audience()) {
    add_action('admin_notices', __NAMESPACE__ . '\\admin_page_misconfig_warning');
}

add_action('plugins_loaded', __NAMESPACE__ . '\\try_login');
add_action('login_form_login', __NAMESPACE__ . '\\login_redirect');
add_action('wp_logout', __NAMESPACE__ . '\\logout_redirect', 10);


if(isset($_GET['cf_access_token_loggedin'])) {
    add_action('admin_notices', __NAMESPACE__ . '\\display_loggedin_token_alert');
}

function cf_access_login_textdomain() {
    load_plugin_textdomain('cf-access-login', false, dirname(plugin_basename(__FILE__)) . '/languages/');
}
add_action('plugins_loaded', __NAMESPACE__ . '\\cf_access_login_textdomain');


//Updater not yet used.
//new GitHub_Updater(__FILE__);