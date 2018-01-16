/**
 * Javascript methods to support the plugin Drafts for Firends.
 *
 * @package DraftsForFriends
 */

jQuery( document ).ready(
	function() {
		jQuery( 'form.draftsforfriends-extend' ).hide();
		jQuery( 'a.draftsforfriends-extend-button' ).show();
		jQuery( 'a.draftsforfriends-extend-cancel' ).show();
		jQuery( 'a.draftsforfriends-extend-cancel' ).css( 'display', 'inline' );

		jQuery( '.draftsforfriends-extend-button' ).click(
			function(e) {
				e.preventDefault();

				shared_key = jQuery( this ).data( 'shared-key' );

				jQuery( 'form.draftsforfriends-extend[data-shared-key="' + shared_key + '"]' ).show();
				jQuery( this ).hide();
				jQuery( 'form.draftsforfriends-extend[data-shared-key="' + shared_key + '"] input[name="expires"]' ).focus();

			}
		);

		jQuery( '.draftsforfriends-extend-cancel' ).click(
			function(e) {
				e.preventDefault();

				shared_key = jQuery( this ).data( 'shared-key' );

				jQuery( 'form.draftsforfriends-extend[data-shared-key="' + shared_key + '"]' ).hide();
				jQuery( '.draftsforfriends-extend-button[data-shared-key="' + shared_key + '"]' ).show();
			}
		);

		jQuery( '.draftsforfriends-delete-button' ).click(
			function(e) {
				e.preventDefault();

				shared_key = jQuery( this ).data( 'shared-key' );

				jQuery( 'form.draftsforfriends-delete[data-shared-key="' + shared_key + '"]' ).submit();
			}
		);

	}
);
