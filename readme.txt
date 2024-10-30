=== BuddyPress SMF Import ===
Contributors: normen
Donate Link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=X7SZG3SM4JYGY
Tags: buddypress, smf, forum, import
Requires at least: WP 2.9.2, BP 1.2.3
Tested up to: WP 3.0, BP 1.2.4
Stable tag: trunk

Imports boards and users from SMF 1.1x forums to BuddyPress (forums) / WordPress (users)

== Description ==

This plugin imports boards and users from SMF 1.1x forums to BuddyPress (forums) / WordPress (users)

<h3>Features:</h3>
<ul>
<li>
Preserves current WordPress users if name exists
</li>
<li>
Create groups for forums after import (optional)
</li>
<li>
Main plugin adds admin panel for all functions
</li>
<li>
Separate plugin successively converts the sha-1 encoded passwords from SMF on user login
</li>
<li>
Includes php script that can be put into old forum directory for redirection to new topic locations
</li>
</ul>
<h3>Known issues:</h3>
<ul>
<li>
Only intended for initial forum imports, it clears all current forums before importing due to the initial 0.7.3 db format requirement (users/groups etc. are kept intact)
</li>
<li>
Depending on forum size, the PHP memory and exec time requirements can be a bit high
</li>
</ul>

The plugin is based on a modified phpbb import PHP script for bbpress 0.7.3.
It uses its own version of bbpress to convert from the initial 0.7.3 bbpress db format to a buddypress compatible one.

== Installation ==

This section describes how to install the plugin and get it working.

1. Upload the `buddypress-smf-import` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Go to BuddyPress->SMF Import in your WordPress admin panel

== Frequently Asked Questions ==

= I used the "create groups" function twice and now my groups are messed up, what to do? =

Clear all tables in wp_groups, wp_groups_groupmeta and wp_groups_members (only delete contents, not tables!) and use "create groups" again.

= After updating the plugin I get strange database erros and cannot access the forum, what to do? =

The bb-config.php file of the included bbpress is replaced and so the forum configuration is reset.
Version 0.9.3 introduces a button in the admin menu of the importer that allows rewriting the bb-config.php file.
<br>
You can also move the correct bb-config.php file to any place you want and go through the "forum setup" wizard of BuddyPress again to register it.

== Screenshots ==

1. Admin Panel

== Changelog ==

= 0.9.6 =
* remove positions fix due to output error
* update redirect script to redirect to forums

= 0.9.6 =
* correct post positions on import

= 0.9.5 =
* remove spaces when creating slugs

= 0.9.4 =
* remove bbpress config file from plugin to avoid overwriting on plugin update

= 0.9.3 =
* add rewrite bbconfig file option

= 0.9.2 =
* skip tags if not exist in SMF forum

= 0.9.1 =
* fix for BP 1.2.5

= 0.9 =
* Code cleanups / annotations
* Check if tag topics/users exist
* Updated redirect script and added instructions

= 0.8.4 =
* Add tag import
* Change imported users role to 0:"none"

= 0.8.3 =
* Fix problem with editing topics (post_position)
* Move admin panel to include folder
* Uses charset of WP install db for new tables instead of utf-8
* Output info about skipped data

== Upgrade Notice ==

= 0.8.4 =
Adds tag support, posts and tags can be reimported w/o cleaning db.

= 0.8.3 =
Sets user role to "none" on import, clean db needed for new user import.
