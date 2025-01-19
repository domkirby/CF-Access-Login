# Login to WordPress with Cloudflare Access Token

This WordPress plugin allows you to login to WordPress using a Cloudflare Access JSON Web Token (JWT). It works for websites that are protected by Cloudflare Access to require additional authentication to the WordPress admin area.

## Use
If you do not protect your WordPress site with Cloudflare Access, this plugin will do you no good!

This plugin works by searching for a user matching the ``email`` claim in the JWT with a matching user's ``email`` in WordPress. If no such user exists, the login will fail as it does not create users for you.

**WARNING:** I recommend that you maintain an account for which you know the password so that you can access your site in the event of a Cloudflare, Plugin, or other failure.

## Configuration
This plugin is simple to configure. Once activated, a new options page will be created under "Settings" in the WordPress admin menu.

Configure the following options:

- Cloudflare Team Name. This is the name of your team as presented in the domain used for logging in. For example, if your team domain is ``contoso.cloudflareaccess.com``, then your team name would be ``contoso`` (without the ``.cloudflareaccess.com`` part)

- App Audience ID. This is the AUD tag displayed in your Cloudflare app and is a string such as ``dc52e74361d1df510a0ef83eb6e3dfd42e78420820...``

- Auto Redirect Setting. This setting will automatically redirect ``wp-login.php`` to ``/wp-admin`` to trigger your Access application rule.

- Logout from Cloudflare on WordPress Logout. **Recommended to be ENABLED**. This setting will automatically log you out of CF Access when you sign out out of WordPress. If you leave it off, your token will simply log you right back into WordPress. This may be desired behavior for some, so the option is there.

All other authentication options are handled by Cloudflare.