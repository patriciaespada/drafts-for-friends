<?php

/**
Plugin Name: Drafts for Friends
Plugin URI: http://automattic.com/
Description: Now you don't need to add friends as users to the blog in order to let them preview your drafts
Author: Neville Longbottom
Version: 2.2
Author URI:
 */

class DraftsForFriends {

	/**
	 * Drafts For Friends constructor.
	 */
	public function __construct() {
		add_action( 'init', array( &$this, 'init' ) );
	}

	/**
	 * Initialize the plugin.
	 *
	 * @return void
	 */
	public function init() {
		global $current_user;
		add_action( 'admin_menu', array( $this, 'add_admin_pages' ) );
		add_filter( 'the_posts', array( $this, 'the_posts_intercept' ) );
		add_filter( 'posts_results', array( $this, 'posts_results_intercept' ) );

		$this->admin_options = $this->get_admin_options();

		$this->user_options = ( $current_user->id > 0 && isset( $this->admin_options[ $current_user->id ] ) ) ? $this->admin_options[ $current_user->id ] : array();

		$this->save_admin_options();

		$this->admin_page_init();
	}

	/**
	 * Add admin scripts and styles.
	 *
	 * @return void
	 */
	public function admin_page_init() {
		wp_enqueue_script( 'jquery' );
		add_action( 'admin_head', array( $this, 'print_admin_css' ) );
		add_action( 'admin_head', array( $this, 'print_admin_js' ) );
	}

	/**
	 * Get the admin options for all shared objects.
	 *
	 * @return array Array with the shared objects
	 */
	public function get_admin_options() {
		$saved_options = get_option( 'shared' );
		return is_array( $saved_options ) ? $saved_options : array();
	}

	/**
	 * Save the shared objects.
	 *
	 * @return void
	 */
	public function save_admin_options() {
		global $current_user;
		if ( $current_user->id > 0 ) {
			$this->admin_options[ $current_user->id ] = $this->user_options;
		}
		update_option( 'shared', $this->admin_options );
	}

	/**
	 * Add the admin page.
	 *
	 * @return void
	 */
	public function add_admin_pages() {
		add_submenu_page(
			'edit.php', __( 'Drafts for Friends', 'draftsforfriends' ), __( 'Drafts for Friends', 'draftsforfriends' ),
			1, __FILE__, array( $this, 'output_existing_menu_sub_admin_page' )
		);
	}

	/**
	 * Calculate the expiration date for a new draft.
	 *
	 * @param array $params Array of shared draft options.
	 * @return string String representation of the expiration date
	 */
	public function calc( $params ) {
		$exp      = 60;
		$multiply = 60;
		if ( isset( $params['expires'] ) && ( $e = intval( $params['expires'] ) ) ) {
			$exp = $e;
		}
		$mults = array(
			's' => 1,
			'm' => 60,
			'h' => 3600,
			'd' => 24 * 3600,
		);
		if ( $params['measure'] && $mults[ $params['measure'] ] ) {
			$multiply = $mults[ $params['measure'] ];
		}
		return $exp * $multiply;
	}

	/**
	 * Process post options to set as new draft.
	 *
	 * @param array $params Array of shared draft options.
	 * @return string A string saying why the post wasn't shared or a new line in the table will be displayed
	 */
	public function process_post_options( $params ) {
		global $current_user;
		if ( $params['post_id'] ) {
			$p = get_post( $params['post_id'] );
			if ( ! $p ) {
				return __( 'There is no such post!', 'draftsforfriends' );
			}
			if ( 'publish' == get_post_status( $p ) ) {
				return __( 'The post is published!', 'draftsforfriends' );
			}
			$this->user_options['shared'][] = array(
				'id'      => $p->ID,
				'expires' => time() + $this->calc( $params ),
				'key'     => 'baba_' . wp_generate_password( 8 ),
			);
			$this->save_admin_options();
		}
	}

	/**
	 * Delete the shared post.
	 *
	 * @param array $params Array of shared draft options.
	 * @return void Remove the shared post from the list
	 */
	public function process_delete( $params ) {
		// TODO: delete operation not working for expired shared posts.
		$shared = array();
		foreach ( $this->user_options['shared'] as $share ) {
			if ( $share['key'] == $params['key'] ) {
				continue;
			}
			$shared[] = $share;
		}
		$this->user_options['shared'] = $shared;
		$this->save_admin_options();
	}

	/**
	 * Extend the shared post to have a new expiration date.
	 *
	 * @param array $params Array of shared draft options.
	 * @return void Update the expiration date on the correspondent line in the list for the shared post
	 */
	public function process_extend( $params ) {
		// TODO: doesn't seems to be working.
		$shared = array();
		foreach ( $this->user_options['shared'] as $share ) {
			if ( $share['key'] == $params['key'] ) {
				$share['expires'] += $this->calc( $params );
			}
			$shared[] = $share;
		}
		$this->user_options['shared'] = $shared;
		$this->save_admin_options();
	}

	/**
	 * Get posts that can added as drafts (drafts, scheduled and pending review posts).
	 *
	 * @return array Array with an array for each type of posts that can be added as drafts
	 */
	public function get_drafts() {
		global $current_user;
		$my_drafts    = get_users_drafts( $current_user->id );
		$my_scheduled = $this->get_users_future( $current_user->id );
		$pending      = get_others_pending( $current_user->id );
		$ds           = array(
			array(
				__( 'Your Drafts:', 'draftsforfriends' ),
				count( $my_drafts ),
				$my_drafts,
			),
			array(
				__( 'Your Scheduled Posts:', 'draftsforfriends' ),
				count( $my_scheduled ),
				$my_scheduled,
			),
			array(
				__( 'Pending Review:', 'draftsforfriends' ),
				count( $pending ),
				$pending,
			),
		);
		return $ds;
	}

	/**
	 * Get users posts marked as drafts.
	 *
	 * @param int $user_id User id.
	 * @return array Array with all users post drafts
	 */
	public function get_users_future( $user_id ) {
		// TODO: change the query.
		global $wpdb;
		return $wpdb->get_results( "SELECT ID, post_title FROM $wpdb->posts WHERE post_type = 'post' AND post_status = 'future' AND post_author = $user_id ORDER BY post_modified DESC" );
	}

	/**
	 * Get the user shared posts.
	 *
	 * @return array Array with the user shared posts
	 */
	public function get_shared() {
		return $this->user_options['shared'];
	}

	/**
	 * Calculate the amount of time for the shared post to expire.
	 *
	 * @param array $share Object that represents the shared post.
	 * @return string String representing the time for the shared post to expire
	 */
	private function get_time_to_expire( $share ) {
		if ( $share['expires'] < time() ) {
			return __( 'Expired', 'drafts-for-friends' );
		} else {
			return __( 'Expires in ', 'drafts-for-friends' ) . human_time_diff( $share['expires'], current_time( 'timestamp' ) );
		}
	}

	/**
	 * Output the admin page.
	 *
	 * @return void
	 */
	public function output_existing_menu_sub_admin_page() {
		if ( $_POST['draftsforfriends_submit'] ) {
			$t = $this->process_post_options( $_POST );
		} elseif ( $_POST['action'] == 'extend' ) {
			$t = $this->process_extend( $_POST );
		} elseif ( $_GET['action'] == 'delete' ) {
			$t = $this->process_delete( $_GET );
		}
		$ds = $this->get_drafts();
?>
	<div class="wrap">
		<h2><?php _e( 'Drafts for Friends', 'draftsforfriends' ); ?></h2>
<?php if ( $t ) : ?>
		<div id="message" class="updated fade"><?php echo $t; ?></div>
<?php endif; ?>
		<h3><?php _e( 'Currently shared drafts', 'draftsforfriends' ); ?></h3>
		<table class="widefat">
			<thead>
			<tr>
				<th><?php _e( 'ID', 'draftsforfriends' ); ?></th>
				<th><?php _e( 'Title', 'draftsforfriends' ); ?></th>
				<th><?php _e( 'Link', 'draftsforfriends' ); ?></th>
				<th><?php _e( 'Expires After', 'draftsforfriends' ); ?></th>
				<th colspan="2" class="actions"><?php _e( 'Actions', 'draftsforfriends' ); ?></th>
			</tr>
			</thead>
			<tbody>
<?php
		$s = $this->get_shared();
foreach ( $s as $share ) :
	$p           = get_post( $share['id'] );
	$url         = get_bloginfo( 'url' ) . '/?p=' . $p->ID . '&draftsforfriends=' . $share['key'];
	$expire_time = $this->get_time_to_expire( $share );
?>
	<tr>
		<td><?php echo $p->ID; ?></td>
		<td><?php echo $p->post_title; ?></td>
		<!-- TODO: make the draft link selecatble -->
		<td><a href="<?php echo $url; ?>"><?php echo esc_html( $url ); ?></a></td>
		<td><?php echo $expire_time; ?></td>
		<td class="actions">
			<a class="draftsforfriends-extend edit" id="draftsforfriends-extend-link-<?php echo $share['key']; ?>"
				href="javascript:draftsforfriends.toggle_extend('<?php echo $share['key']; ?>');">
					<?php _e( 'Extend', 'draftsforfriends' ); ?>
			</a>
			<form class="draftsforfriends-extend" id="draftsforfriends-extend-form-<?php echo $share['key']; ?>"
				action="" method="post">    
				<input type="hidden" name="action" value="extend" />
				<input type="hidden" name="key" value="<?php echo $share['key']; ?>" />
				<input type="submit" class="button" name="draftsforfriends_extend_submit"
					value="<?php _e( 'Extend', 'draftsforfriends' ); ?>"/>
<?php _e( 'by', 'draftsforfriends' ); ?>
<?php echo $this->tmpl_measure_select(); ?>				
				<a class="draftsforfriends-extend-cancel"
					href="javascript:draftsforfriends.cancel_extend('<?php echo $share['key']; ?>');">
					<?php _e( 'Cancel', 'draftsforfriends' ); ?>
				</a>
			</form>
		</td>
		<td class="actions">
			<a class="delete" href="edit.php?page=<?php echo plugin_basename( __FILE__ ); ?>&amp;action=delete&amp;key=<?php echo $share['key']; ?>"><?php _e( 'Delete', 'draftsforfriends' ); ?></a>
		</td>
	</tr>
<?php
		endforeach;
if ( empty( $s ) ) :
?>
	<tr>
		<td colspan="5"><?php _e( 'No shared drafts!', 'draftsforfriends' ); ?></td>
	</tr>
<?php
		endif;
?>
			</tbody>
		</table>
		<h3><?php _e( 'Drafts for Friends', 'draftsforfriends' ); ?></h3>
		<form id="draftsforfriends-share" action=""            method="post">
		<p>
			<select id="draftsforfriends-postid" 	name="post_id">
			<option value=""><?php _e( 'Choose a draft', 'draftsforfriends' ); ?></option>
<?php
foreach ( $ds as $dt ) :
	if ( $dt[1] ) :
?>
	<option value="" disabled="disabled"></option>
	<option value="" disabled="disabled"><?php echo $dt[0]; ?></option>
<?php
foreach ( $dt[2] as $d ) :
	if ( empty( $d->post_title ) ) {
		continue;
	}
?>
<option value="<?php echo $d->ID; ?>"><?php echo wp_specialchars( $d->post_title ); ?></option>
<?php
		endforeach;
	endif;
		endforeach;
?>
			</select>
		</p>
		<p>
			<input type="submit" class="button" name="draftsforfriends_submit"
				value="<?php _e( 'Share it', 'draftsforfriends' ); ?>" />
			<?php _e( 'for', 'draftsforfriends' ); ?>
			<?php echo $this->tmpl_measure_select(); ?>.
		</p>
		</form>
		</div>
<?php
	}

	/**
	 * Check if a friend can view the post.
	 *
	 * @param int $pid The post id.
	 * @return boolean True if the url matches, false otherwise
	 */
	public function can_view( $pid ) {
		foreach ( $this->admin_options as $option ) {
			$shares = $option['shared'];
			foreach ( $shares as $share ) {
				if ( $share['key'] == $_GET['draftsforfriends'] && $pid ) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * If the post isn't published, and the friend can view, show it.
	 *
	 * @param array $pp Array of posts to be presented.
	 * @return array Array with the same posts that was passed as a parameter
	 */
	public function posts_results_intercept( $pp ) {
		if ( 1 != count( $pp ) ) {
			return $pp;
		}
		$p      = $pp[0];
		$status = get_post_status( $p );
		if ( 'publish' != $status && $this->can_view( $p->ID ) ) {
			$this->shared_post = $p;
		}
		return $pp;
	}

	/**
	 * Check if the post is a shared post, and return an array with that post.
	 *
	 * @param object $pp Object with information of the post.
	 * @return array The post presented as an array
	 */
	public function the_posts_intercept( $pp ) {
		if ( empty( $pp ) && ! is_null( $this->shared_post ) ) {
			return array( $this->shared_post );
		} else {
			$this->shared_post = null;
			return $pp;
		}
	}

	/**
	 * Template for the measure select.
	 *
	 * @return string String that represents the select dropdown
	 */
	public function tmpl_measure_select() {
		// TODO: put this output better.
		$secs  = __( 'seconds', 'draftsforfriends' );
		$mins  = __( 'minutes', 'draftsforfriends' );
		$hours = __( 'hours', 'draftsforfriends' );
		$days  = __( 'days', 'draftsforfriends' );
		return <<<SELECT
			<input name="expires" type="text" value="2" size="4"/>
			<select name="measure">
				<option value="s">$secs</option>
				<option value="m">$mins</option>
				<option value="h" selected="selected">$hours</option>
				<option value="d">$days</option>
			</select>
SELECT;
	}

	/**
	 * Print the block for the CSS.
	 *
	 * @return void
	 */
	public function print_admin_css() {
		// TODO: put the CSS as a seperated file.
?>
	<style type="text/css">
		a.draftsforfriends-extend, a.draftsforfriends-extend-cancel { display: none; }
		form.draftsforfriends-extend { white-space: nowrap; }
		form.draftsforfriends-extend, form.draftsforfriends-extend input, form.draftsforfriends-extend select { font-size: 11px; }
		th.actions, td.actions { text-align: center; }
	</style>
<?php
	}

	/**
	 * Print the javascript block.
	 *
	 * @return void
	 */
	public function print_admin_js() {
		// TODO: put the JS as seperated file.
?>
	<script type="text/javascript">
	jQuery(function() {
		jQuery('form.draftsforfriends-extend').hide();
		jQuery('a.draftsforfriends-extend').show();
		jQuery('a.draftsforfriends-extend-cancel').show();
		jQuery('a.draftsforfriends-extend-cancel').css('display', 'inline');
	});
	window.draftsforfriends = {
		toggle_extend: function(key) {
			jQuery('#draftsforfriends-extend-form-'+key).show();
			jQuery('#draftsforfriends-extend-link-'+key).hide();
			jQuery('#draftsforfriends-extend-form-'+key+' input[name="expires"]').focus();
		},
		cancel_extend: function(key) {
			jQuery('#draftsforfriends-extend-form-'+key).hide();
			jQuery('#draftsforfriends-extend-link-'+key).show();
		}
	};
	</script>
<?php
	}
}

new draftsforfriends();
