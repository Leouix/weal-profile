/**
 * Weal Profile Achievements Admin JavaScript.
 *
 * @package Weal_Profile
 */

(function () {
	var mediaFrame = null;
	var currentIconWrapper = null;

	function init() {
		var container = document.querySelector( '.achievement-container' );
		if ( ! container ) {
			return;
		}

		// Event delegation for form submits (covers dynamically added forms).
		container.addEventListener(
			'submit',
			function ( event ) {
				var form = event.target.closest( '.achievement-form' );
				if ( form ) {
					event.preventDefault();
					saveForm( form );
				}
			}
		);

		// Event delegation for icon picker buttons (covers dynamically added items).
		container.addEventListener(
			'click',
			function ( event ) {
				var uploadBtn = event.target.closest( '.upload-achievement-icon-button' );
				if ( uploadBtn ) {
					openMediaLibrary( uploadBtn.closest( '.achievement-wrapper' ) );
					return;
				}
				var removeBtn = event.target.closest( '.remove-achievement-icon-button' );
				if ( removeBtn ) {
					removeIcon( removeBtn.closest( '.achievement-wrapper' ) );
				}
			}
		);
	}

	function openMediaLibrary( wrapper ) {
		currentIconWrapper = wrapper;

		if ( mediaFrame ) {
			mediaFrame.open();
			return;
		}

		mediaFrame = wp.media( {
			title: wealProfileAchievementsData.chooseIconTitle || 'Choose Achievement Icon',
			library: { type: 'image' },
			button: {
				text: wealProfileAchievementsData.selectText || 'Select'
			},
			multiple: false
		} );

		mediaFrame.on( 'select', function () {
			var attachment = mediaFrame.state().get( 'selection' ).first().toJSON();
			setIcon( currentIconWrapper, attachment.id, attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url );
		} );

		mediaFrame.open();
	}

	function setIcon( wrapper, attachmentId, imageUrl ) {
		var iconInput = wrapper.querySelector( '.achievement-icon-input' );
		var removeFlag = wrapper.querySelector( '.achievement-remove-icon-flag' );
		var preview = wrapper.querySelector( '.achievement-icon-preview' );
		var previewLabel = wrapper.querySelector( '.custom-icon-label' );

		if ( iconInput ) {
			iconInput.value = attachmentId;
		}
		if ( removeFlag ) {
			removeFlag.value = '0';
		}
		if ( preview ) {
			preview.innerHTML = '<img src="' + imageUrl + '" alt="" class="achievement-custom-icon" style="max-width:20px;max-height:20px;border-radius:50%;">';
			preview.style.display = 'flex';
		}
		if ( previewLabel ) {
			previewLabel.style.display = 'inline';
		}
	}

	function removeIcon( wrapper ) {
		var iconInput = wrapper.querySelector( '.achievement-icon-input' );
		var removeFlag = wrapper.querySelector( '.achievement-remove-icon-flag' );
		var preview = wrapper.querySelector( '.achievement-icon-preview' );
		var previewLabel = wrapper.querySelector( '.custom-icon-label' );

		var defaultIcon = preview ? preview.dataset.defaultIcon : '';
		var isAlreadyDefault = ( iconInput && iconInput.value === defaultIcon ) || ( removeFlag && removeFlag.value === '1' );

		if ( iconInput ) {
			iconInput.value = '';
		}
		if ( removeFlag ) {
			removeFlag.value = '1';
		}
		if ( preview && ! isAlreadyDefault ) {
			preview.innerHTML = preview.dataset.defaultIconHtml || '';
			preview.style.display = 'flex';
		}
		if ( previewLabel && ! isAlreadyDefault ) {
			previewLabel.style.display = 'inline';
		}
	}

	function saveForm( elForm ) {
		var formData      = new FormData( elForm );
		var buttonArea    = elForm.querySelector( '.button-area' );
		var successNotice = buttonArea.querySelector( '.achievement-success-notice' );
		var errorNotice   = buttonArea.querySelector( '.achievement-error-notice' );

		var labelInput = elForm.querySelector( 'input[name$="[label]"]' );
		var labelError = elForm.querySelector( '.label-error-notice' );
		if ( labelInput && '' === labelInput.value.trim() ) {
			if ( labelError ) {
				labelError.textContent = wealProfileAchievementsData.labelRequired || 'Label cannot be empty.';
				labelError.style.display = 'inline';
			}
			return;
		}
		if ( labelError ) {
			labelError.style.display = 'none';
		}

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

				var preview = elForm.querySelector( '.achievement-icon-preview' );
				var previewLabel = elForm.querySelector( '.custom-icon-label' );
				if ( preview ) {
					preview.innerHTML = '';
					preview.style.display = 'none';
				}
				if ( previewLabel ) {
					previewLabel.style.display = 'none';
				}

				if ( response.icon_html ) {
					var iconEl = elForm.querySelector( 'h3 .admin-achievement-icon' );
					if ( iconEl ) {
						iconEl.outerHTML = response.icon_html;
					}
				}

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
