=== GitHook ===
Contributors: nerdaryan
Donate link:
Tags: github, webhook, sync, deploy
Requires at least: 4.5
Requires PHP: 5.6
Tested up to: 4.9
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A very light weight plugin to sync themes and plugins directly from GitHub.

== Description ==

GitHook aims to automatically sync themes and plugins from the GitHub repository and keeping it very light weight as possible.

GitHook listen for a webhook request from GitHub. When GitHook receive a request it checks whether it is coming from a defined repository and then it pull zip ball from API and replace theme or plugin.

GitHook does not run WordPress update function instead it simply replace files. So, don't expect for plugin/theme activation hook.

All steps to get this plugin running is described in installation section.

== Installation ==

This section describes how to install the plugin and get it working.

1. Upload the plugin files to the `/wp-content/plugins/githook` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Navigate to wp-admin->Settings->GitHook
1. If your repository is private then you have to get a personal access token from GitHub first. To get access token simply navigate to [GitHub account](https://github.com/settings/tokens "generate access token") and then click on "generate new token". For scope checkbox only check "repo" checkbox and then generate checkbox.
1. One you have the access token copy and paste it to "GitHub access token" field in GitHook settings.
1. In "WebHook Secret" add a secure alpha numeric string which will will be secret key for webhook. You can generate a password using a online tool [Secure password generator](http://www.sha1-online.com/secure-password-generator.php "Secure password generator")
1. Now add repository and save.
1. NOT Done yet :D. After saving settings copy webhook url which is shown at the top of GitHook seetings. Navigate to GitHub repository -> Settings tab -> Webhooks and click "Add webhook". In "Payload URL" paste the url which you have copied from plugins settings. In "Content type" field select "application/json". In "Secret" field copy the "WebHook Secret" from plugin setting. And create webhook.

All done. For every repository you add must have the webhook which we have added in last step. For any question please send a request to support@anspress.io

== Frequently asked questions ==

= Can I sync from private repository =

Yes, you can but you have to add access token.

== Screenshots ==

1. GitHook settings page.

== Changelog ==

= 1.0.0 =

* Initial release.

== Upgrade Notice ==