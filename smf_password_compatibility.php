<?php
/*
Plugin Name: SMF Password Compatibility
Plugin URI: http://wordpress.org/extend/plugins/buddypress-smf-import/
Description: This plugin adds compatibility for SHA1 encoded passwords from a SMF installation.
Version: 0.9.7
Requires at least: WP 2.9.2, BuddyPress 1.2.3
Tested up to: WP 3.0, BuddyPress 1.2.3
Author: Normen Hansen
Author URI: http://www.bitwaves.de
License: GPL
*/

add_filter('check_password', 'my_check_password', 12, 4 ); 
function my_check_password($check, $password, $hash, $user_id = '') {
	if($check) return true;
	// SMF forum compatible check
	$user_info = get_userdata($user_id);
	$check = ( $hash == sha1( strtolower( $user_info->user_login ) . $password) );
	if ( $check && $user_id ) {
		// Rehash using new hash.
		wp_set_password($password, $user_id);
		$hash = wp_hash_password($password);
		return true;
	}
	return false;
}
?>