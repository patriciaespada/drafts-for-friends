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
	<option value="s"><?php esc_html_e( 'seconds', 'draftsforfriends' ); ?></option>
	<option value="m"><?php esc_html_e( 'minutes', 'draftsforfriends' ); ?></option>
	<option value="h" selected="selected"><?php esc_html_e( 'hours', 'draftsforfriends' ); ?></option>
	<option value="d"><?php esc_html_e( 'days', 'draftsforfriends' ); ?></option>
</select>
