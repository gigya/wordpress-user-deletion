=== Gigya - User Deletion ===

Contributors: gigya.com
Tags: user deletion, batch, cron job
Requires at least: 4.2
Tested up to: 4.9
Stable tag: 1.1
License: GPLv2 or later

Batch-delete multiple users from WordPress

== Description ==
Gigya's user deletion plugin allows the batch deletion of multiple users in WordPress via cron job, based on a CSV file that sits in the Amazon S3 cloud storage service.

The plugin includes two modes: soft-delete, which marks users as deleted but does not physically remove them from the database, and hard-delete, which removes users entirely.

It can also be used independently of Gigya, to batch-delete regular WordPress users. This requires changing 'gigya' to 'wordpress' in the do_user_deletion_job function


== Installation ==

1.	Download the Gigya - User Deletion plugin, unpack and upload the folder to the the /wp-content/plugins/ directory on your website
2.	Go to the Plugins tab in the WordPress administration panel, find the Gigya - User Deletion plugin on the list and click Activate
3.	Proceed to the plugin settings page to configure your plugin

For question about installations or configuration, please contact your account manager or contact our support via the support page on the Gigya site.


== Changelog ==

= 1.1 =
* Support for WordPress-level job frequency configuration
* Support for non-built-in Amazon S3 servers
* Don't allow incorrect S3 details to overwrite correct ones
* Bug fixes

= 1.1.1 =
* Support for pre-deletion hook that determines whether to delete any given user
