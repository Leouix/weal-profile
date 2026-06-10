/**
 * Weal Profile Achievements Admin JavaScript.
 *
 * @package Weal_Profile
 */

(function () {
	function init() {
		var forms = document.querySelectorAll( '.achievement-form' );
		if ( ! forms.length ) {
			return;
		}

		forms.forEach(
			function ( form ) {
				form.addEventListener(
					'submit',
					function ( event ) {
						event.preventDefault();
						saveForm( event.target );
					}
				);
			}
		);
	}

	function saveForm( elForm ) {
		var formData      = new FormData( elForm );
		var buttonArea    = elForm.querySelector( '.button-area' );
		var successNotice = buttonArea.querySelector( '.achievement-success-notice' );
		var errorNotice   = buttonArea.querySelector( '.achievement-error-notice' );

		var xhr = new XMLHttpRequest();
		xhr.open( 'POST', wealProfileAchievementsData.root + 'weal-profile/v1/admin-save-achievements-settings/', true );
		xhr.setRequestHeader( 'X-WP-Nonce', wealProfileAchievementsData.nonce );
		xhr.onreadystatechange = function () {
			if ( 4 !== this.readyState ) {
				return;
			}

			try {
				var response = JSON.parse( this.responseText );
			} catch ( e ) {
				return;
			}

			if ( response.success ) {
				successNotice.style.display = 'inline';
				errorNotice.style.display   = 'none';
				setTimeout(
					function () {
						successNotice.style.display = 'none';
					},
					1000
				);
			} else if ( response.message ) {
				errorNotice.textContent     = response.message;
				errorNotice.style.display   = 'inline';
				successNotice.style.display = 'none';
			}
		};
		xhr.send( formData );
	}

	document.addEventListener( 'DOMContentLoaded', init );
})();
