=== CF Access Login ===
Contributors: domkirby
Donate link: https://domkirby.com
Tags: cloudflare, jwt, access, login, authentication
Requires at least: 6.2
Tested up to: 6.3
Requires PHP: 7.4
Stable tag: 0.9.11
License: MIT
License URI: https://opensource.org/licenses/MIT

CF Access Login enables WordPress sites protected by Cloudflare Access to authenticate users with Cloudflare Access JSON Web Tokens (JWTs).

== Description ==

CF Access Login is a plugin designed for WordPress websites secured by Cloudflare Access. It allows users to log in to WordPress using their Cloudflare Access JSON Web Token (JWT). The plugin matches the "email" claim in the JWT with a WordPress user email to authenticate the login.

Key Features:

Authenticate WordPress logins via Cloudflare Access JWTs.

Prevents login if no matching WordPress user email exists.

Configuration options for Cloudflare Team Name, App Audience ID, and auto-redirect settings.

Option to log out of Cloudflare Access on WordPress logout for enhanced security.

Important: This plugin is only useful for sites protected by Cloudflare Access. Ensure you maintain an account with a known password to access your site in case of failure.

== Installation ==

Upload the plugin files to the /wp-content/plugins/cf-access-login directory, or install the plugin through the WordPress plugins screen directly.

Activate the plugin through the 'Plugins' screen in WordPress.

Go to "Settings" > "CF Access Login" to configure the plugin options:

Cloudflare Team Name: Enter your team name (e.g., contoso if your domain is contoso.cloudflareaccess.com).

App Audience ID: Enter the AUD tag displayed in your Cloudflare app.

Auto Redirect Setting: Enable to redirect wp-login.php to /wp-admin.

Logout from Cloudflare on WordPress Logout: Recommended to be enabled for secure logouts.

== Frequently Asked Questions ==

= What happens if no matching user is found in WordPress? =
The login will fail. The plugin does not create new users automatically.

= Can I still log in with a WordPress username and password? =
Yes, as long as you maintain at least one account with a known password.

= Do I need to configure anything in Cloudflare? =
Yes, your site must be protected by Cloudflare Access. Refer to the Cloudflare documentation for guidance on setting up Access.

== Changelog ==

= 0.9.0 =
A test release, not ready for use!

= 0.14 =

Initial release.

Added authentication via Cloudflare Access JWT.

Included options for auto-redirect and secure logout.

== Upgrade Notice ==

= 0.14 =
First release of CF Access Login. Ensure your site is protected by Cloudflare Access before activating the plugin.

== Screenshots ==

Settings Page: Configure Cloudflare Team Name, App Audience ID, and other options.

Login Page Redirect: Automatically redirects to Cloudflare Access if not authenticated.