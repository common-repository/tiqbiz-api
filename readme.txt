=== Tiqbiz API ===
Contributors: tiqbiz
Tags: api, admin
Requires at least: 4.0
Tested up to: 4.6
Stable tag: trunk
License: CC BY-SA 4.0
License URI: https://creativecommons.org/licenses/by-sa/4.0/legalcode

Integrates your WordPress site with the Tiqbiz API

== Description ==

This plugin synchronises post and EventON/CalPress events in your WordPress site to your Tiqbiz account.

For more information on Tiqbiz, please see http://tiqbiz.com/

== Installation ==

1. Install either via the WordPress.org plugin directory, or by uploading the plugin files to your server
2. After activating the plugin, you will need to go to the Tiqbiz API Settings page and provide some authentication details, which will be provided by the Tiqbiz team
3. Any new or updated posts or EventON/CalPress events will be synced across to your Tiqbiz account

== Frequently Asked Questions ==

For all informaton on this plugin and Tiqbiz, please see http://tiqbiz.com/

== Screenshots ==

1. An example of the notice shown while syncing
2. The Tiqbiz API Settings page

== Changelog ==

= 2.0.10 =
Update Markdown converter and fix whitespace issue

= 2.0.9 =
Fix for post/event duplication

= 2.0.8 =
Notification fix for events

= 2.0.7 =
Only send automatica notifications for posts - bring back the option for
events

= 2.0.6 =
Notifications are now automatically sent

= 2.0.4 =
Resolved an issue where all day events were broken in EventON

= 2.0.0 =
Updated plugin to support v6 API

= 1.0.8 =
Resolved an issue where some special characters were not displaying properly

= 1.0.7 =
Sync via plugin rather than AJAX directly

= 1.0.6 =
Better handling of post/event content

= 1.0.5 =
Fix for single day all day events

= 1.0.4 =
Support syncing future posts

= 1.0.3 =
Support for CalPress Pro

= 1.0.2 =
* Better timezone handling for event times
* PHP 5.3 compatibility
* Add PHP version to to settings page

= 1.0.1 =
Change 'Options' to 'Settings'

= 1.0 =
Initial release

== Upgrade Notice ==

= 1.0 =
Initial release

= 1.0.1 =
Minor update

= 1.0.2 =
Minor update to handle timezones better, and support older versions of PHP

= 1.0.3 =
Minor update to support CalPress Pro

= 1.0.4 =
Minor update to support syncing future posts

= 1.0.5 =
Minor update to fix an issue with single day all day events

= 1.0.6 =
Minor update to fix an issue with unusual characters in post/event content, and to add support for shortcodes

= 1.0.7 =
Update to fix an issue with posts that have lots of content not syncing due to URL length limits

= 1.0.8 =
Update to fix an issue where some special characters were not displaying properly

= 2.0.0 =
Major update to enable support for the v6 API

= 2.0.4 =
Small update to fix a conflict with EventON

= 2.0.6 =
Small update to fix a notification issue

= 2.0.7 =
Another small update to notification behavior

= 2.0.8 =
Fixed a bug causing events with no 24-hour notification to mistakenly send one on publish instead

= 2.0.9 =
Fixed a bug where duplicating a post or event would cause the synced version to be overridden

= 2.0.10 =
Fixes a bug that may cause some text pasted from Word to contain strange characters
