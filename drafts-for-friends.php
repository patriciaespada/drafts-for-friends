<?php
/**
 * Implementation of the plugin Drafts for Firends.
 *
 * @package DraftsForFriends
 */

/**
 * Plugin Name: Drafts for Friends
 * Plugin URI: http://automattic.com/
 * Description: Now you don't need to add friends as users to the blog in order to let them preview your drafts
 * Author: Patrícia Espada
 * Text Domain: draftsforfriends
 * Domain Path: /languages
 * Version: 1.0
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

		add_action( 'wp_ajax_delete', array( $this, 'process_delete' ) );
		add_action( 'wp_ajax_extend', array( $this, 'process_extend' ) );
		add_action( 'wp_ajax_sharedraft', array( $this, 'process_share_draft' ) );

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
		wp_enqueue_script( 'draftsforfriends', plugins_url( 'js/drafts-for-friends.js', __FILE__ ), array( 'jquery' ) );
		wp_enqueue_style( 'draftsforfriends', plugins_url( 'css/drafts-for-friends.css', __FILE__ ) );

		wp_localize_script(
			'draftsforfriends', 'wp_ajax_delete', array(
				'ajaxurl'    => admin_url( 'admin-ajax.php', $protocol ),
				'ajax_nonce' => wp_create_nonce( 'delete' ),
			)
		);
		wp_localize_script(
			'draftsforfriends', 'wp_ajax_extend', array(
				'ajaxurl'    => admin_url( 'admin-ajax.php', $protocol ),
				'ajax_nonce' => wp_create_nonce( 'extend' ),
			)
		);
		wp_localize_script(
			'draftsforfriends', 'wp_ajax_sharedraft', array(
				'ajaxurl'    => admin_url( 'admin-ajax.php', $protocol ),
				'ajax_nonce' => wp_create_nonce( 'sharedraft' ),
			)
		);
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
	 * @return boolean False if value was not updated and true if value was updated
	 */
	public function save_admin_options() {
		global $current_user;
		if ( $current_user->id > 0 ) {
			$this->admin_options[ $current_user->id ] = $this->user_options;
		}
		return update_option( 'shared', $this->admin_options );
	}

	/**
	 * Add the admin page.
	 *
	 * @return void
	 */
	public function add_admin_pages() {
		add_submenu_page(
			'edit.php', __( 'Drafts for Friends', 'draftsforfriends' ), __( 'Drafts for Friends', 'draftsforfriends' ),
			1, 'draftsforfriends', array( $this, 'output_existing_menu_sub_admin_page' )
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
		if ( isset( $params['expires'] ) ) {
			$exp = absint( $params['expires'] );
		}
		$mults = array(
			's' => 1,
			'm' => 60,
			'h' => 3600,
			'd' => 24 * 3600,
		);
		if ( isset( $params['measure'] ) && $mults[ $params['measure'] ] ) {
			$multiply = $mults[ $params['measure'] ];
		}
		return absint( $exp * $multiply );
	}

	/**
	 * Process share a new draft.
	 *
	 * @return void The response is either an error message, or the html with the representation of the new draft.
	 */
	public function process_share_draft() {
		$params = filter_input_array( INPUT_POST );

		if ( empty( $params['security'] ) || ! wp_verify_nonce( $params['security'], 'sharedraft' ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html( 'Could not verify the origin and intent of the request: nonce verification failed.', 'draftsforfriends' ),
				)
			);
		}

		global $current_user;
		if ( isset( $params['post_id'] ) ) {
			$p = get_post( $params['post_id'] );
			if ( empty( $p ) ) {
				wp_send_json_error(
					array(
						'message' => esc_html( 'Could not find any post with the specified id.', 'draftsforfriends' ),
					)
				);
			}
			if ( 'publish' === get_post_status( $p ) ) {
				wp_send_json_error(
					array(
						'message' => esc_html( 'The post is already published.', 'draftsforfriends' ),
					)
				);
			}

			$share                          = array(
				'id'      => $p->ID,
				'expires' => time() + $this->calc( $params ),
				'key'     => 'baba_' . wp_generate_password( 12, false ),
			);
			$this->user_options['shared'][] = $share;

			$result = $this->save_admin_options();
			if ( $result ) {
				include_once dirname( __FILE__ ) . '/parts/drafts-for-friends-draft.php';
			} else {
				wp_send_json_error(
					array(
						'message' => esc_html( 'An error occurred while creating the post draft. Please try again.', 'draftsforfriends' ),
					)
				);
			}
		} else {
			wp_send_json_error(
				array(
					'message' => esc_html( 'No post id was found in the request.', 'draftsforfriends' ),
				)
			);
		}
	}

	/**
	 * Delete the shared post.
	 *
	 * @return void The response is a json with an error message or with an successfull message
	 */
	public function process_delete() {
		$params = filter_input_array( INPUT_POST );

		if ( empty( $params['security'] ) || ! wp_verify_nonce( $params['security'], 'delete' ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html( 'Could not verify the origin and intent of the request: nonce verification failed.', 'draftsforfriends' ),
				)
			);
		}

		if ( isset( $params['key'] ) ) {
			$shared = array();
			foreach ( $this->user_options['shared'] as $share ) {
				if ( $share['key'] === $params['key'] ) {
					continue;
				}
				$shared[] = $share;
			}
			$this->user_options['shared'] = $shared;

			$result = $this->save_admin_options();
			if ( $result ) {
				wp_send_json_success(
					array(
						'message' => esc_html( 'The post draft was successfully deleted.', 'draftsforfriends' ),
					)
				);
			} else {
				wp_send_json_error(
					array(
						'message' => esc_html( 'An error occurred while deleting the post draft. Please try again.', 'draftsforfriends' ),
					)
				);
			}
		} else {
			wp_send_json_error(
				array(
					'message' => esc_html( 'No draft post key was found in the request.', 'draftsforfriends' ),
				)
			);
		}
	}

	/**
	 * Extend the shared post to have a new expiration date.
	 * If the expiration date is in the past then we apply the new expiration date taking into account the
	 * current date. If it's not in the past, then we simply add the amount of expiration to the existing one.
	 *
	 * @return void  The response is a json with an error message or with an successfull message and the new expiration time
	 */
	public function process_extend() {
		$params = filter_input_array( INPUT_POST );

		if ( empty( $params['security'] ) || ! wp_verify_nonce( $params['security'], 'extend' ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html( 'Could not verify the origin and intent of the request: nonce verification failed.', 'draftsforfriends' ),
				)
			);
		}

		if ( isset( $params['key'] ) ) {
			$shared       = array();
			$result_share = array();
			foreach ( $this->user_options['shared'] as $share ) {
				if ( $share['key'] === $params['key'] ) {
					if ( $share['expires'] < time() ) {
						$share['expires'] = time() + $this->calc( $params );
					} else {
						$share['expires'] += $this->calc( $params );
					}
					$result_share = $share;
				}
				$shared[] = $share;
			}
			$this->user_options['shared'] = $shared;

			$result = $this->save_admin_options();
			if ( $result ) {
				wp_send_json_success(
					array(
						'expires' => esc_html( $this->get_time_to_expire( $result_share ) ),
						'message' => esc_html( 'The post draft was successfully extended.', 'draftsforfriends' ),
					)
				);
			} else {
				wp_send_json_error(
					array(
						'message' => esc_html( 'An error occurred while extending the post draft. Please try again', 'draftsforfriends' ),
					)
				);
			}
		} else {
			wp_send_json_error(
				array(
					'message' => esc_html( 'No draft post key was found in the request.', 'draftsforfriends' ),
				)
			);
		}
	}

	/**
	 * Get posts that can added as drafts (drafts, scheduled and pending review posts).
	 *
	 * @return array Array with an array for each type of posts that can be added as drafts
	 */
	public function get_drafts() {
		global $current_user;

		// Get the future user posts, ordered by the post_modified DESC.
		$args  = array(
			'post_author' => absint( $current_user->id ),
			'post_status' => array( 'draft', 'future', 'pending' ),
			'post_type'   => 'post',
			'orderby'     => 'post_modified',
			'order'       => 'DESC',
		);
		$posts = new WP_Query( $args );

		// Arrange posts to order by status and present the ID and title per post.
		$my_drafts    = array();
		$my_scheduled = array();
		$pending      = array();
		foreach ( $posts->posts as $post ) {
			$post_array = (object) array(
				'ID'         => $post->ID,
				'post_title' => $post->post_title,
			);
			if ( 'draft' === $post->post_status ) {
				$my_drafts[] = $post_array;
			} elseif ( 'future' === $post->post_status ) {
				$my_scheduled[] = $post_array;
			} elseif ( 'pending' === $post->post_status ) {
				$pending[] = $post_array;
			}
		}

		// Build the select dropdown for choosing the draft.
		$ds = array(
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
		$now = current_time( 'timestamp' );
		if ( $share['expires'] < $now ) {
			return __( 'Expired', 'draftsforfriends' );
		} else {
			$diff    = $share['expires'] - $now;
			$days    = floor( $diff / ( 60 * 60 * 24 ) );
			$hours   = floor( ( $diff - $days * ( 60 * 60 * 24 ) ) / ( 60 * 60 ) );
			$minutes = floor( ( $diff - ( $days * ( 60 * 60 * 24 ) + $hours * ( 60 * 60 ) ) ) / 60 );

			/* translators: %d: days representation */
			$days_str = sprintf( _n( '%d day', '%d days', $days, 'draftsforfriends' ), $days );
			/* translators: %d: hours representation */
			$hours_str = sprintf( _n( '%d hour', '%d hours', $hours, 'draftsforfriends' ), $hours );
			/* translators: %d: minutes representation */
			$minutes_str = sprintf( _n( '%d minute', '%d minutes', $minutes, 'draftsforfriends' ), $minutes );

			if ( $days > 0 ) {
				/*
				 * translators:
				 * %1$d: days string (e.g.: 1 day or 20 days)
				 * %2$d: hours string (e.g.: 1 hour or 20 hours)
				 * %3$d: minutes string (e.g.: 1 minute or 20 minutes)
				 */
				return sprintf( __( '%1$s, %2$s, %3$s', 'draftsforfriends' ), $days_str, $hours_str, $minutes_str );
			} elseif ( $hours > 0 ) {
				/*
				 * translators:
				 * %1$d: hours string (e.g.: 1 hour or 20 hours)
				 * %2$d: minutes string (e.g.: 1 minute or 20 minutes)
				 */
				return sprintf( __( '%1$s, %2$s', 'draftsforfriends' ), $hours_str, $minutes_str );
			} elseif ( $minutes > 0 ) {
				return $minutes_str;
			} else {
				return __( '1 minute', 'draftsforfriends' );
			}
		}
	}

	/**
	 * Output the admin page.
	 *
	 * @return void
	 */
	public function output_existing_menu_sub_admin_page() {

		include_once dirname( __FILE__ ) . '/parts/drafts-for-friends-page.php';

	}

	/**
	 * Check if a friend can view the post.
	 *
	 * @param int $pid The post id.
	 * @return boolean True if the url matches, false otherwise
	 */
	public function can_view( $pid ) {
		$key = filter_input( INPUT_GET, 'draftsforfriends', FILTER_SANITIZE_STRING );
		if ( empty( $key ) ) {
			return false;
		}

		foreach ( $this->admin_options as $option ) {
			$shares = $option['shared'];
			foreach ( $shares as $share ) {
				if ( $key === $share['key'] && $pid === $share['id'] ) {
					return $share['expires'] >= time();
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
		if ( 1 !== count( $pp ) ) {
			return $pp;
		}
		$p      = $pp[0];
		$status = get_post_status( $p );
		if ( 'publish' !== $status && $this->can_view( $p->ID ) ) {
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
}

new draftsforfriends();
