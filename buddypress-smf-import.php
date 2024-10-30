<?php
/*
Plugin Name: BuddyPress SMF Forum import
Plugin URI: http://wordpress.org/extend/plugins/buddypress-smf-import/
Donate Link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=X7SZG3SM4JYGY
Description: Lets you import a SMF 1.1 forum posts and users into BuddyPress.
Version: 0.9.7
Requires at least: WP 2.9.2, BuddyPress 1.2.3
Tested up to: WP 3.0, BuddyPress 1.2.4.1
License: GPL
Author: Normen Hansen
Author URI: http://www.bitwaves.de/
Site Wide Only: true
*/

require ( dirname( __FILE__ ) . '/include/buddypress-smf-functions.php' );

function bpsmf_core_add_admin_menu() {
    global $bp;
    
    add_submenu_page( 'bp-general-settings', 'SMF Import', 'SMF Import', 'manage_options', 'bpsmf_admin_settings', 'bpsmf_admin_settings' );
}
add_action('admin_menu', 'bpsmf_core_add_admin_menu',25);

function bpsmf_admin_settings() {
    include( 'include/buddypress-smf-admin.php');
}

?>