<?php
/**
 * Drafts for Friends Uninstall (https://developer.wordpress.org/plugins/the-basics/uninstall-methods/)
 *
 * @package DraftsForFriends
 */

// If uninstall is not called from WordPress, die.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die();
}

$option_name = 'shared';
delete_option( $option_name );
delete_site_option( $option_name );
