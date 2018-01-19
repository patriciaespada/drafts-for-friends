<?php
/**
 * Drafts for Friends Main Template
 *
 * @package Drafts_For_Friends
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ds = $this->get_drafts();

?>
<div class="wrap">
	<h2>
		<?php esc_html_e( 'Drafts for Friends', 'drafts-for-friends' ); ?>
		<span class="plugin-version">
		<?php
			/* translators: %s: plugin version representation */
			printf( esc_html__( 'v%s', 'drafts-for-friends' ), esc_html( DRAFTSFORFRIENDS_VERSION, 'drafts-for-friends' ) );
		?>
	</span>
	</h2>
	<div class="notice notice-error"></div>
	<div class="notice notice-success"></div>
	<h3><?php esc_html_e( 'Currently shared drafts', 'drafts-for-friends' ); ?></h3>
	<table class="widefat">
		<thead>
		<tr>
			<th><?php esc_html_e( 'ID', 'drafts-for-friends' ); ?></th>
			<th><?php esc_html_e( 'Title', 'drafts-for-friends' ); ?></th>
			<th><?php esc_html_e( 'Link', 'drafts-for-friends' ); ?></th>
			<th><?php esc_html_e( 'Expires After', 'drafts-for-friends' ); ?></th>
			<th colspan="2" class="actions"><?php esc_html_e( 'Actions', 'drafts-for-friends' ); ?></th>
		</tr>
		</thead>
		<tbody>
<?php
$s = $this->get_shared();
if ( isset( $s ) ) {
	foreach ( $s as $share ) {
		include dirname( __FILE__ ) . '/drafts-for-friends-draft.php';
	}
}
?>
			<tr class="empty-list">
				<td colspan="5"><?php esc_html_e( 'No shared drafts!', 'drafts-for-friends' ); ?></td>
			</tr>
		</tbody>
	</table>
	<h3><?php esc_html_e( 'Share a draft with friends', 'drafts-for-friends' ); ?></h3>
	<form class="draftsforfriends-share" method="post">
		<input type='hidden' name='action' value='sharedraft' />
		<p>
			<select id="draftsforfriends-postid" name="post_id">
				<option value=""><?php esc_html_e( 'Choose a draft', 'drafts-for-friends' ); ?></option>
<?php
foreach ( $ds as $dt ) {
	if ( $dt[1] ) {
?>
				<option value="" disabled="disabled"></option>
				<option value="" disabled="disabled"><?php echo esc_html( $dt[0] ); ?></option>
<?php
foreach ( $dt[2] as $d ) {
	if ( empty( $d->post_title ) ) {
		continue;
	}
?>
				<option value="<?php echo esc_attr( $d->ID ); ?>"><?php echo esc_html( $d->post_title ); ?></option>
<?php
}
	}
}
?>
			</select>
		</p>
		<p>
			<input type="submit" class="button" name="draftsforfriends_submit" value="<?php esc_attr_e( 'Share it', 'drafts-for-friends' ); ?>" />
			<?php esc_html_e( 'for', 'drafts-for-friends' ); ?>
			<?php require dirname( __FILE__ ) . '/drafts-for-friends-measures.php'; ?>
		</p>
	</form>
</div>
