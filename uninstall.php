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

delete_option( 'draftsforfriends_shared_posts' );
delete_site_option( 'draftsforfriends_shared_posts' );
delete_option( 'draftsforfriends_version' );
