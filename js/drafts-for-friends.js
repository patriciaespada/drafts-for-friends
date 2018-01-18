/**
 * Javascript methods to support the plugin Drafts for Firends.
 *
 * @package DraftsForFriends
 */

jQuery( document ).ready(
	function() {

		hideNoticeMessages();
		showAndHideEmptyLine();

		/**
		 * Expands the extend form.
		 */
		jQuery( '.draftsforfriends-extend-button' ).click(
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
		jQuery( '.draftsforfriends-extend-cancel' ).click(
			function( e ) {
				e.preventDefault();

				var sharedKey = jQuery( this ).data( 'shared-key' );

				hideExtendForm( sharedKey );
			}
		);

		/**
		 * Extend the expiration date of a draft post (AJAX call to the server).
		 */
		jQuery( 'form.draftsforfriends-extend' ).submit(
			function( e ) {
				e.preventDefault();

				hideNoticeMessages();

				var sharedKey = jQuery( this ).data( 'shared-key' );

				hideExtendForm( sharedKey );

				// Build the request.
				var request = jQuery( 'form.draftsforfriends-extend[data-shared-key="' + sharedKey + '"]' ).serialize();

				jQuery.ajax(
					{
						url: ajaxurl,
						data: request,
						type: 'POST',
						dataType: 'json',
						success: function( response ) {
							ajaxRequestSuccessProcess(
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
		jQuery( '.draftsforfriends-delete-button' ).click(
			function( e ) {
				e.preventDefault();

				hideNoticeMessages();

				var sharedKey = jQuery( this ).data( 'shared-key' );

				// Build the request.
				var request = jQuery( 'form.draftsforfriends-delete[data-shared-key="' + sharedKey + '"]' ).serialize();

				jQuery.ajax(
					{
						url: ajaxurl,
						data: request,
						type: 'POST',
						dataType: 'json',
						success: function( response ) {
							console.log( response );
							ajaxRequestSuccessProcess(
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
				var request = jQuery( 'form.draftsforfriends-share' ).serialize();

				jQuery.ajax(
					{
						url: ajaxurl,
						data: request,
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
								jQuery( '.notice.notice-success' ).html( '<p>A draft for the post was successfully created.</p>' ).slideDown();
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
		jQuery( '.draftsforfriends-copy-link' ).click(
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

		function hideNoticeMessages() {
			jQuery( '.notice.notice-success' ).hide();
			jQuery( '.notice.notice-error' ).hide();
		};

		function showAndHideEmptyLine() {
			var table = jQuery( 'table.widefat tbody' );
			if ( table.children().length > 1 ) {
				jQuery( 'tr.empty-list' ).hide();
			} else {
				jQuery( 'tr.empty-list' ).show();
			}
		};

		function hideExtendForm( sharedKey ) {
			jQuery( 'form.draftsforfriends-extend[data-shared-key="' + sharedKey + '"]' ).hide();
			jQuery( '.draftsforfriends-extend-button[data-shared-key="' + sharedKey + '"]' ).show();
		}

		function ajaxRequestSuccessProcess( response, callback ) {
			if ( response.success ) {
				callback( response.data );

				jQuery( '.notice.notice-success' ).html( '<p>' + response.data.message + '</p>' ).slideDown();
			} else {
				jQuery( '.notice.notice-error' ).html( '<p>' + response.data.message + '</p>' ).slideDown();
			}
		}

		function displayRequestErrorMessage() {
			jQuery( '.notice.notice-error' ).html( '<p>An unexpeted error occured. Please try again.</p>' ).slideDown();
		}
	}
);
