<?php
/**
 * Drafts for Friends Draft Template
 *
 * @package DraftsForFriends
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$p           = get_post( $share['id'] );
$url         = get_bloginfo( 'url' ) . '/?p=' . $p->ID . '&draftsforfriends=' . $share['key'];
$expire_time = $this->get_time_to_expire( $share );

?>
<tr id="<?php echo esc_attr( $share['key'] ); ?>">
	<td class="id">
		<?php echo esc_html( absint( $p->ID ) ); ?>
	</td>
	<td class="title">
		<?php echo esc_html( $p->post_title ); ?>
	</td>
	<td class="link">
		<a href="<?php echo esc_url( $url ); ?>"><?php echo esc_url( $url ); ?></a>
		<div class="row-actions">
			<a class="draftsforfriends-copy-link" href="#" title="Copy Link" data-link="<?php echo esc_url( $url ); ?>">Copy Link</a>
		</div>
	</td>
	<td class="expires-after">
		<?php echo esc_html( $expire_time ); ?>
	</td>
	<td class="actions">
		<a class="draftsforfriends-extend-button" data-shared-key="<?php echo esc_attr( $share['key'] ); ?>" href="#">
				<?php esc_html_e( 'Extend', 'draftsforfriends' ); ?>
		</a>
		<form class="draftsforfriends-extend" data-shared-key="<?php echo esc_attr( $share['key'] ); ?>" method="post">
			<?php wp_nonce_field( 'extend', 'extend-nonce' ); ?>
			<input type="hidden" name="action" value="extend" />
			<input type="hidden" name="key" value="<?php echo esc_attr( $share['key'] ); ?>" />
			<input type="submit" class="button" name="draftsforfriends_extend_submit" value="<?php esc_attr_e( 'Extend', 'draftsforfriends' ); ?>"/>
			<?php esc_html_e( 'by', 'draftsforfriends' ); ?>
			<?php require dirname( __FILE__ ) . '/drafts-for-friends-measures.php'; ?>
			<a class="draftsforfriends-extend-cancel" data-shared-key="<?php echo esc_attr( $share['key'] ); ?>" href="#">
				<?php esc_html_e( 'Cancel', 'draftsforfriends' ); ?>
			</a>
		</form>
	</td>
	<td class="actions">
		<form class="draftsforfriends-delete" data-shared-key="<?php echo esc_attr( $share['key'] ); ?>" method='post'>
			<?php wp_nonce_field( 'delete', 'delete-nonce' ); ?>
			<input type='hidden' name='action' value='delete' />
			<input type='hidden' name='key' value='<?php echo esc_attr( $share['key'] ); ?>' />
		</form>
		<a class="draftsforfriends-delete-button" data-shared-key="<?php echo esc_attr( $share['key'] ); ?>" href="#">
				<?php esc_html_e( 'Delete', 'draftsforfriends' ); ?>
		</a>
	</td>
</tr>
