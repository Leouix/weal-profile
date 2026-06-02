/**
 * Contains the relevant methods and functions for the plugin
 *
 * @package weal-profile
 */

let formUserButton
let successNotice
let errorNotice
let lockUrlIcon
let urlInput
let dashiconsUnlock
let dashiconsLock
window.addEventListener(
	'load',
	function () {

		formUserButton  = document.getElementById( 'save-create-button' );
		successNotice   = document.getElementById( 'success-notice' );
		errorNotice     = document.getElementById( 'error-notice' );
		lockUrlIcon     = document.getElementById( 'lock-url' );
		urlInput        = document.getElementById( 'adu-form-input' );
		dashiconsUnlock = document.getElementById( 'dashicons-unlock' );
		dashiconsLock   = document.getElementById( 'dashicons-lock' );

		lockUrlIcon.addEventListener(
			'click',
			function () {
				urlInput.disabled = ! urlInput.disabled;

				if (urlInput.disabled) {
					dashiconsLock.classList.remove( 'hidden' );
					dashiconsLock.classList.add( 'visible' );

					dashiconsUnlock.classList.remove( 'visible' );
					dashiconsUnlock.classList.add( 'hidden' );
				} else {
					dashiconsLock.classList.remove( 'visible' );
					dashiconsLock.classList.add( 'hidden' );

					dashiconsUnlock.classList.remove( 'hidden' );
					dashiconsUnlock.classList.add( 'visible' );
				}
			}
		);

		function saveMyAccountSettingsForm(elForm) {
			var formData = new FormData( elForm );

			var xhr = new XMLHttpRequest();
			xhr.open( 'POST', wealProfileAdminData.root + 'weal-profile/v1/admin-save-page-settings/', true );
			xhr.setRequestHeader( 'X-WP-Nonce', wealProfileAdminData.nonce );
			xhr.onreadystatechange = function () {
				if (4 !== this.readyState) {
					return;
				}

				try {
					var response = JSON.parse( this.responseText );
				} catch ( e ) {
					return;
				}

				if ( response.success ) {
					successAjaxButtonEvent( 'success' );
					lockLinkField();
				} else if ( response.message ) {
					showError( response.message );
				}
			};
			xhr.send( formData );
		}

		function lockLinkField() {
			urlInput.disabled = true;
			dashiconsLock.classList.remove( 'hidden' );
			dashiconsLock.classList.add( 'visible' );
			dashiconsUnlock.classList.remove( 'visible' );
			dashiconsUnlock.classList.add( 'hidden' );
		}

		function successAjaxButtonEvent(statusClass) {
			errorNotice.style.display = 'none';
			formUserButton.classList.add( statusClass );
			successNotice.style.display = 'block';
			if ('success' === statusClass) {
				setTimeout(
					function () {
						formUserButton.classList.remove( statusClass );
						successNotice.style.display = 'none';
					},
					1000
				);
			}
		}

		function showError( message ) {
			successNotice.style.display = 'none';
			formUserButton.classList.remove( 'success' );
			errorNotice.textContent = message;
			errorNotice.style.display = 'block';
		}

		var form = document.getElementById( 'admin-user-account-form' );
		form.addEventListener(
			'submit',
			function (event) {
				event.preventDefault();
				saveMyAccountSettingsForm( event.target );
			}
		);
	}
);
