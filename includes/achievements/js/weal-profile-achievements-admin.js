/**
 * Admin JS for the Achievements settings tab.
 *
 * @package weal-profile
 */

(function () {
	var form
	var submitButton
	var successNotice
	var errorNotice

	function init() {
		form = document.getElementById( 'achievements-settings-form' );
		if ( ! form ) {
			return;
		}

		submitButton = document.getElementById( 'save-achievements-button' );
		successNotice = document.getElementById( 'achievements-success-notice' );
		errorNotice   = document.getElementById( 'achievements-error-notice' );

		form.addEventListener(
			'submit',
			function ( event ) {
				event.preventDefault();
				saveForm( event.target );
			}
		);
	}

	function saveForm( elForm ) {
		var formData = new FormData( elForm );

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
				successNotice.style.display = 'block';
				errorNotice.style.display   = 'none';
				setTimeout(
					function () {
						successNotice.style.display = 'none';
					},
					1000
				);
			} else if ( response.message ) {
				errorNotice.textContent   = response.message;
				errorNotice.style.display = 'block';
				successNotice.style.display = 'none';
			}
		};
		xhr.send( formData );
	}

	document.addEventListener( 'DOMContentLoaded', init );
})();
