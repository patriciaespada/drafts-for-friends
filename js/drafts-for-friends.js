/**
 * Javascript methods to support the plugin Drafts for Firends.
 *
 * @package DraftsForFriends
 */

// GLOBAL SCOPE FUNCTION DECLARATION.
/**
 * Hide notice messages divs.
 */
var hideNoticeMessages = function() {
	jQuery( '.notice.notice-success' ).hide();
	jQuery( '.notice.notice-error' ).hide();
};

/**
 * Show/Hide table empty line message.
 */
var showAndHideEmptyLine = function() {
	var table = jQuery( 'table.widefat tbody' );
	if ( table.children().length > 1 ) {
		jQuery( 'tr.empty-list' ).hide();
	} else {
		jQuery( 'tr.empty-list' ).show();
	}
};

/**
 * Hide the extend form.
 *
 * @param string sharedKey String representation of the draft key
 */
var hideExtendForm = function( sharedKey ) {
	jQuery( 'form.draftsforfriends-extend[data-shared-key="' + sharedKey + '"]' ).hide();
	jQuery( '.draftsforfriends-extend-button[data-shared-key="' + sharedKey + '"]' ).show();
};

/**
 * Standard ajax json request process. Run the callback function and print the success or error messages.
 *
 * @param string response Json response
 * @param function callback
 */
var ajaxJsonRequestSuccessProcess = function( response, callback ) {
	if ( response.success ) {
		callback( response.data );

		jQuery( '.notice.notice-success' ).html( '<p>' + response.data.message + '</p>' ).slideDown();
	} else {
		jQuery( '.notice.notice-error' ).html( '<p>' + response.data.message + '</p>' ).slideDown();
	}
};

/**
 * Display ajax request failure message.
 */
var displayRequestErrorMessage = function() {
	jQuery( '.notice.notice-error' ).html( '<p>An unexpeted error occured. Please try again.</p>' ).slideDown();
};

/**
 * Transform the form inputs into a readable array with name: val format.
 *
 * @param array formInputs Array with all form inputs elements
 */
var getFormInputsAsArray = function( formInputs ) {
	var formInputsArr = {};
	formInputs.each(
		function() {
			formInputsArr[ this.name ] = jQuery( this ).val();
		}
	);
	return formInputsArr;
};


// DOM READY SCOPE EVENTS.
jQuery( document ).ready(
	function() {

		hideNoticeMessages();
		showAndHideEmptyLine();

		/**
		 * Expands the extend form.
		 */
		jQuery( document ).on(
			'click', '.draftsforfriends-extend-button',
			function( e ) {
				e.preventDefault();

				var sharedKey = jQuery( this ).data( 'shared-key' );

				jQuery( 'form.draftsforfriends-extend[data-shared-key="' + sharedKey + '"]' ).show();
				jQuery( this ).hide();
				jQuery( 'form.draftsforfriends-extend[data-shared-key="' + sharedKey + '"] input[name="expires"]' ).focus();
			}
		);

		/**
		 * Cancel the extend operation.
		 */
		jQuery( document ).on(
			'click', '.draftsforfriends-extend-cancel',
			function( e ) {
				e.preventDefault();

				var sharedKey = jQuery( this ).data( 'shared-key' );

				hideExtendForm( sharedKey );
			}
		);

		/**
		 * Extend the expiration date of a draft post (AJAX call to the server).
		 */
		jQuery( document ).on(
			'submit', 'form.draftsforfriends-extend',
			function( e ) {
				e.preventDefault();

				hideNoticeMessages();

				var sharedKey = jQuery( this ).data( 'shared-key' );

				hideExtendForm( sharedKey );

				// Build the request.
				var request = getFormInputsAsArray( jQuery( 'form.draftsforfriends-extend[data-shared-key="' + sharedKey + '"] :input' ) );

				jQuery.ajax(
					{
						url: wp_ajax_extend.ajaxurl,
						data: {
							action: request['action'],
							key: request['key'],
							expires: request['expires'],
							measure: request['measure'],
							security: wp_ajax_extend.ajax_nonce
						},
						type: 'POST',
						dataType: 'json',
						success: function( response ) {
							ajaxJsonRequestSuccessProcess(
								response, function( data ) {
									// Reset form.
									jQuery( 'form.draftsforfriends-extend[data-shared-key="' + sharedKey + '"]' ).trigger( "reset" );

									// Updates the expiration time.
									jQuery( 'tr#' + sharedKey + ' td.expires-after' ).html( data.expires );
								}
							);
						},
						error: function ( errorThrown ) {
							displayRequestErrorMessage();
						}
					}
				);
			}
		);

		/**
		 * Delete a draft post (AJAX call to the server)
		 */
		jQuery( document ).on(
			'click', '.draftsforfriends-delete-button',
			function( e ) {
				e.preventDefault();

				hideNoticeMessages();

				var sharedKey = jQuery( this ).data( 'shared-key' );

				// Build the request.
				var request = getFormInputsAsArray( jQuery( 'form.draftsforfriends-delete[data-shared-key="' + sharedKey + '"] :input' ) );

				jQuery.ajax(
					{
						url: wp_ajax_delete.ajaxurl,
						data: {
							action: request['action'],
							key: request['key'],
							security: wp_ajax_delete.ajax_nonce
						},
						type: 'POST',
						dataType: 'json',
						success: function( response ) {
							ajaxJsonRequestSuccessProcess(
								response, function( data ) {
									// Reset form.
									jQuery( 'form.draftsforfriends-delete[data-shared-key="' + sharedKey + '"]' ).trigger( "reset" );

									// Remove the deleted draft.
									jQuery( 'tr#' + sharedKey ).slideUp();
									jQuery( 'tr#' + sharedKey ).remove();

									// Show no drafts shared message if table is empty.
									showAndHideEmptyLine();
								}
							);
						},
						error: function ( errorThrown ) {
							displayRequestErrorMessage();
						}
					}
				);
			}
		);

		/**
		 * Share a new draft post (AJAX call to the server)
		 */
		jQuery( 'form.draftsforfriends-share' ).submit(
			function( e ) {
				e.preventDefault();

				hideNoticeMessages();

				// Build the request.
				var request = getFormInputsAsArray( jQuery( 'form.draftsforfriends-share :input' ) );

				jQuery.ajax(
					{
						url: wp_ajax_sharedraft.ajaxurl,
						data: {
							action: request['action'],
							post_id: request['post_id'],
							expires: request['expires'],
							measure: request['measure'],
							security: wp_ajax_sharedraft.ajax_nonce
						},
						type: 'POST',
						success: function( response ) {
							// Reset form.
							jQuery( 'form.draftsforfriends-share' ).trigger( "reset" );

							if ( response.data ) {
								if ( ! response.success ) {
									jQuery( '.notice.notice-error' ).html( '<p>' + response.data.message + '</p>' ).slideDown();
								}
							} else {
								jQuery( 'tr.empty-list' ).hide();
								jQuery( 'table.widefat' ).append( response );
								jQuery( '.notice.notice-success' ).html( '<p>' + sharedraft_success_message.message + '</p>' ).slideDown();
							}
						},
						error: function ( errorThrown ) {
							displayRequestErrorMessage();
						}
					}
				);
			}
		);

		/**
		 * Copy the link of the draft post.
		 */
		jQuery( document ).on(
			'click', '.draftsforfriends-copy-link',
			function( e ) {
				e.preventDefault();

				var link = jQuery( this ).data( 'link' );

				var temp = jQuery( '<input>' );
				jQuery( 'body' ).append( temp );
				temp.val( link ).select();
				document.execCommand( 'copy' );
				temp.remove();
			}
		);
	}
);
