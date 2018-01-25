=== Gigya - User Deletion ===

Contributors: gigya.com
Tags: user deletion, batch, cron job
Requires at least: 4.2
Tested up to: 4.9
Stable tag: 1.0
License: GPLv2 or later

Batch-delete multiple users from WordPress

== Description ==
Gigya's user deletion plugin allows the batch deletion of multiple users in WordPress via cron job, based on a CSV file that sites in the Amazon S3 cloud storage service.

The plugin includes two modes: soft-delete, which marks users as deleted but does not physically remove them from the database, and hard-delete, which removes users entirely.

It can also be used independently of Gigya, to batch-delete regular WordPress users.

Use at your own risk!


== Installation ==

1.	Install the <a href="https://wordpress.org/plugins/amazon-web-services/" title="Amazon Web Services WordPress plugin">Amazon Web Services plugin</a> from the WordPress plugin repository.
2.	After downloading the Gigya - User Deletion plugin, unpack and upload the folder to the the /wp-content/plugins/ directory on your website
3.	Go to the Plugins tab in the WordPress administration panel, find the Gigya - User Deletion plugin on the list and click Activate
4.	Proceed to the plugin settings page to configure your plugin

For question about installations or configuration, please contact your account manager or contact our support via the support page on the Gigya site.


== Changelog ==