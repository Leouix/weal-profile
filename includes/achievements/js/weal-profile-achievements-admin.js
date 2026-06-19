/**
 * Weal Profile Achievements Admin JavaScript.
 *
 * @package Weal_Profile
 */

(function () {
	var mediaFrame = null;
	var currentIconWrapper = null;

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

		var duplicateButtons = document.querySelectorAll( '.achievement-duplicate' );
		duplicateButtons.forEach(
			function ( button ) {
				button.addEventListener(
					'click',
					function () {
						duplicateAchievement( button );
					}
				);
			}
		);

		var deleteButtons = document.querySelectorAll( '.achievement-delete' );
		deleteButtons.forEach(
			function ( button ) {
				button.addEventListener(
					'click',
					function () {
						deleteAchievement( button );
					}
				);
			}
		);

		initIconPickers();
	}

	function initIconPickers() {
		var wrappers = document.querySelectorAll( '.achievement-wrapper' );
		wrappers.forEach( function ( wrapper ) {
			bindIconPickerEvents( wrapper );
		} );
	}

	function bindIconPickerEvents( wrapper ) {
		var uploadButton = wrapper.querySelector( '.upload-achievement-icon-button' );
		var removeButton = wrapper.querySelector( '.remove-achievement-icon-button' );

		if ( uploadButton ) {
			uploadButton.addEventListener( 'click', function () {
				openMediaLibrary( wrapper );
			} );
		}

		if ( removeButton ) {
			removeButton.addEventListener( 'click', function () {
				removeIcon( wrapper );
			} );
		}
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

		if ( iconInput ) {
			iconInput.value = attachmentId;
		}
		if ( removeFlag ) {
			removeFlag.value = '0';
		}
		if ( preview ) {
			preview.innerHTML = '<img src="' + imageUrl + '" alt="" class="achievement-custom-icon" style="max-width:50px;max-height:50px;border-radius:4px;">';
		}
	}

	function removeIcon( wrapper ) {
		var iconInput = wrapper.querySelector( '.achievement-icon-input' );
		var removeFlag = wrapper.querySelector( '.achievement-remove-icon-flag' );
		var preview = wrapper.querySelector( '.achievement-icon-preview' );

		if ( iconInput ) {
			iconInput.value = '';
		}
		if ( removeFlag ) {
			removeFlag.value = '1';
		}
		if ( preview ) {
			preview.innerHTML = '';
		}
	}

	function getAchievementId( el ) {
		var wrapper = el.closest( '.achievement-wrapper' );
		if ( ! wrapper ) {
			return '';
		}
		var input = wrapper.querySelector( 'input[name="achievement_id"]' );
		return input ? input.value : '';
	}

	function getNonce( el ) {
		var wrapper = el.closest( '.achievement-wrapper' );
		if ( ! wrapper ) {
			return '';
		}
		var input = wrapper.querySelector( 'input[name="weal_profile_achievements_nonce"]' );
		return input ? input.value : '';
	}

	function duplicateAchievement( button ) {
		var achievementId = getAchievementId( button );
		if ( ! achievementId ) {
			return;
		}

		var formData = new FormData();
		formData.append( 'achievement_id', achievementId );
		formData.append( 'weal_profile_achievements_nonce', getNonce( button ) );

		var xhr = new XMLHttpRequest();
		xhr.open( 'POST', wealProfileAchievementsData.root + 'weal-profile/v1/duplicate-achievement/', true );
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

			if ( response.success && response.html ) {
				var container = document.querySelector( '.achievement-container' );
				if ( container ) {
					container.insertAdjacentHTML( 'beforeend', response.html );
					bindFormEvents( container.lastElementChild );
				}
			}
		};
		xhr.send( formData );
	}

	function deleteAchievement( button ) {
		if ( ! confirm( wealProfileAchievementsData.confirmDelete ) ) {
			return;
		}

		var achievementId = getAchievementId( button );
		if ( ! achievementId ) {
			return;
		}

		var formData = new FormData();
		formData.append( 'achievement_id', achievementId );
		formData.append( 'weal_profile_achievements_nonce', getNonce( button ) );

		var xhr = new XMLHttpRequest();
		xhr.open( 'POST', wealProfileAchievementsData.root + 'weal-profile/v1/delete-achievement/', true );
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
				var wrapper = button.closest( '.achievement-wrapper' );
				if ( wrapper ) {
					wrapper.remove();
				}
			}
		};
		xhr.send( formData );
	}

	function bindFormEvents( wrapper ) {
		var form = wrapper.querySelector( '.achievement-form' );
		if ( form ) {
			form.addEventListener(
				'submit',
				function ( event ) {
					event.preventDefault();
					saveForm( event.target );
				}
			);
		}

		var duplicateButton = wrapper.querySelector( '.achievement-duplicate' );
		if ( duplicateButton ) {
			duplicateButton.addEventListener(
				'click',
				function () {
					duplicateAchievement( duplicateButton );
				}
			);
		}

		var deleteButton = wrapper.querySelector( '.achievement-delete' );
		if ( deleteButton ) {
			deleteButton.addEventListener(
				'click',
				function () {
					deleteAchievement( deleteButton );
				}
			);
		}

		bindIconPickerEvents( wrapper );
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
