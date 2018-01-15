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

				jQuery( 'form[data-shared-key="' + shared_key + '"]' ).show();
				jQuery( this ).hide();
				jQuery( 'form[data-shared-key="' + shared_key + '"] input[name="expires"]' ).focus();

			}
		);

		jQuery( '.draftsforfriends-extend-cancel' ).click(
			function(e) {
				e.preventDefault();

				shared_key = jQuery( this ).data( 'shared-key' );

				jQuery( 'form[data-shared-key="' + shared_key + '"]' ).hide();
				jQuery( '.draftsforfriends-extend-button[data-shared-key="' + shared_key + '"]' ).show();
			}
		);

	}
);
