<?php
/**
 * Drafts for Friends Measures Template
 *
 * @package DraftsForFriends
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<input name="expires" type="text" value="2" size="4"/>
<select name="measure">
	<option value="s"><?php esc_html_e( 'seconds', 'drafts-for-friends' ); ?></option>
	<option value="m"><?php esc_html_e( 'minutes', 'drafts-for-friends' ); ?></option>
	<option value="h" selected="selected"><?php esc_html_e( 'hours', 'drafts-for-friends' ); ?></option>
	<option value="d"><?php esc_html_e( 'days', 'drafts-for-friends' ); ?></option>
</select>
