<?php
/**
Plugin Name: MS User Notification Helper
Plugin URI: https://github.com/tarosky/ms-user-notification-helper
Description: Change From email of the notification sent to newly added user.
Author: Tarosky INC.
Version: nightly
Author URI: https://tarosky.co.jp/
License: GPL3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html
Text Domain: ms-user-notification-helper
Domain Path: /languages
 */

defined( 'ABSPATH' ) or die();

if ( defined( 'MS_USER_NOTIFICATION_HELPER' ) ) {
	// If constant defined, skip.
	return;
}

// Define constant to avoid duplicated processing.
define( 'MS_USER_NOTIFICATION_HELPER', true );

/**
 * Add filter hook for WP Mail on multisite notifications.
 */
add_filter( 'wpmu_signup_blog_notification', 'ms_user_notification_register_mail_hook' );
add_filter( 'wpmu_signup_user_notification', 'ms_user_notification_register_mail_hook' );
add_filter( 'wpmu_welcome_user_notification', 'ms_user_notification_register_mail_hook' );
add_filter( 'wpmu_welcome_notification', 'ms_user_notification_register_mail_hook' );

/**
 * Register mail hook for ms functions.
 *
 * @param mixed $arg Argument.
 * @return mixed
 */
function ms_user_notification_register_mail_hook( $arg ) {
	// If intentionally defined constant, skip.
	if ( defined( 'MS_USER_NOTIFICATION_HELPER_OMIT' ) ) {
		return $arg;
	}
	// Register mail hook just before sending notification.a
	add_filter( 'wp_mail', 'ms_user_notification_mail_hook', 11 );
	return $arg;
}


/**
 * Change from email header.
 *
 * @see wp_mail()
 * @param array $attributes Mail attributes.
 * @return array
 */
function ms_user_notification_mail_hook( $attributes ) {
	$admin_email = get_site_option( 'admin_email' );
	if ( ! is_array( $attributes['headers'] ) ) {
		$headers = explode( "\n", str_replace( "\r\n", "\n", $attributes['headers'] ) );
	} else {
		$headers = $attributes['headers'];
	}
	// If from header found, replace admin email to default one..
	foreach ( $headers as $key => $header ) {
		if ( ! preg_match( '#From:[^<]+<([^>]+)>#u', $header, $matches ) ) {
			// This is not From header.
			continue;
		}
		list( $match, $mail ) = $matches;
		if ( $mail !== $admin_email ) {
			continue;
		}
		// From is admin_mail.
		$mail_from       = 'wordpress@' . wp_parse_url( network_home_url(), PHP_URL_HOST );
		$mail_from       = apply_filters( 'ms_user_notification_mail_default_from', $mail_from );
		$headers[ $key ] = str_replace( "<{$mail}>", "<{$mail_from}>", $header );
		break;
	}
	$attributes['headers'] = $headers;
	// Remove filter to avoid duplicated processing.
	remove_filter( 'wp_mail', 'ms_user_notification_mail_hook', 11 );
	return $attributes;
}
