/**
 * Contains the relevant methods and functions for the plugin
 *
 * @package weal-profile
 */

( function () {
	'use strict';

	let containerResults;
	let userFormClassObject;
	let tabButton1;
	let tabButton3;

	// In-memory cache for tab/subtab HTML responses.
	// Purpose: avoid repeating expensive requests within the same page session.
	// Cache is cleared automatically on full page reload.
	const wealProfileHtmlCache = new Map();

	function cacheGet( key ) {
		return wealProfileHtmlCache.has( key ) ? wealProfileHtmlCache.get( key ) : null;
	}

	function cacheSet( key, value ) {
		wealProfileHtmlCache.set( key, value );
	}

	window.addEventListener(
		'load',
		function () {
			containerResults = document.getElementById( 'container-results' );
			tabButton1       = document.getElementById( 'tab-button-1' );
			tabButton3       = document.getElementById( 'tab-button-3' );

			checkTabGetParamsLoading();

			const avatarInput = document.querySelector(
				'.weal-profile-avatar-form input[type="file"][name="weal_profile_avatar"]'
			);

			if ( ! avatarInput ) {
				return;
			}

			avatarInput.addEventListener(
				'change',
				function () {
					if ( ! this.files || this.files.length === 0 ) {
						return;
					}

					const form = this.closest( 'form' );

					if ( ! form ) {
						return;
					}

					form.submit();
				}
			);
		}
	);

	document.addEventListener(
		'click',
		function ( e ) {
			var target = e.target.closest( '[data-wp-action]' );
			if ( ! target ) {
				return;
			}
			switch ( target.dataset.wpAction ) {
				case 'switch-tab':
					e.preventDefault();
					setEmptyTemplate();
					getPage( { clickId: target.id, page: 1 } );
					TabsSwitcherHelper.switch( target.id );
					break;
				case 'switch-activity-button':
					e.preventDefault();
					switchOtherUserTab( target );
					break;
				case 'switch-my-tab':
					e.preventDefault();
					setEmptyButtonTemplate();
					switchMyAccountTab( target );
					break;
			}
		}
	);

	function setEmptyButtonTemplate() {
		const formUserButton = document.getElementById( 'my-profile-subtab-content' );
		if ( ! formUserButton ) {
			return;
		}
		formUserButton.classList.add( 'fade-out' );
	}

	document.addEventListener(
		'input',
		function ( e ) {
			var target = e.target.closest( '[data-wp-action="edit-user-data"]' );
			if ( target ) {
				editingUserData( target );
			}
		}
	);

	document.addEventListener(
		'change',
		function ( e ) {
			var target = e.target.closest( '.achievement-toggle-input' );
			if ( ! target ) {
				return;
			}

			var achievementId = target.dataset.achievementId;
			var hidden        = ! target.checked;

			var formData = new FormData();
			formData.append( 'achievement_id', achievementId );
			formData.append( 'hidden', hidden ? 'true' : 'false' );

			var xhr = new XMLHttpRequest();
			xhr.open( 'POST', wealProfilePageData.root + 'weal-profile/v1/toggle-achievement-visibility/', true );
			xhr.setRequestHeader( 'X-WP-Nonce', wealProfilePageData.nonce );
			xhr.onreadystatechange = function () {
				if ( 4 === this.readyState && 200 === this.status ) {
					var json = JSON.parse( this.response );
					if ( json.success ) {
						var item     = target.closest( '.weal-profile-achievement-item' );
						var statusEl = item ? item.querySelector( '.toggle-status-text' ) : null;
						if ( statusEl ) {
							statusEl.textContent = hidden ? 'Hidden' : 'Shown';
						}
						if ( item ) {
							item.classList.toggle( 'user-hidden', hidden );
						}
					}
				}
			};
			xhr.send( formData );
		}
	);

	class TabsSwitcherHelper {
		static tabs = {
			'tab-button-1': 'activity',
			'tab-button-3': 'info',
		};

		static getTabName( buttonId ) {
			return this.tabs[ buttonId ];
		}

		static switch ( activeTabId ) {

			tabButton1.classList.remove( 'active' );
			tabButton3.classList.remove( 'active' );

			switch ( activeTabId ) {
				case 'tab-button-1':
					tabButton1.classList.add( 'active' );
					break;
				case 'tab-button-3':
					tabButton3.classList.add( 'active' );
					break;
			}
		}
	}

	function replaceUrlParam( paramValue ) {
		var queryParams = new URLSearchParams( window.location.search );
		queryParams.set( 'tab', paramValue );
		history.replaceState( null, null, '?' + queryParams.toString() );
	}

	function getPage( clickData ) {
		var clickId = clickData.clickId;
		var page    = clickData.page || 1;

		var tabName  = TabsSwitcherHelper.getTabName( clickId );
		var cacheKey = 'main:' + tabName + ':page:' + page;
		var cached   = cacheGet( cacheKey );
		if ( cached ) {
			containerResults.innerHTML = cached;

			replaceUrlParam( tabName );

			if ( 'info' === tabName ) {
				triggerUserForm();
			}

			if ( 'activity' === tabName ) {
				initMyAccountSubtab();
			}

			delFadeOut();
			return;
		}

		var formData = new FormData();
		formData.append( 'tabName', tabName );
		formData.append( 'page', page );

		var xhr = new XMLHttpRequest();
		xhr.open( 'POST', wealProfilePageData.root + 'weal-profile/v1/switch-tab-ajax/', true );
		xhr.setRequestHeader( 'X-WP-Nonce', wealProfilePageData.nonce );
		xhr.onreadystatechange = function () {
			if ( 4 === this.readyState && 200 === this.status ) {
				var json                   = JSON.parse( this.response );
				containerResults.innerHTML = json.html;

				cacheSet( cacheKey, json.html );

				replaceUrlParam( tabName );

				if ( 'info' === tabName ) {
					triggerUserForm();
				}

				if ( 'activity' === tabName ) {
					initMyAccountSubtab();
				}
			}
			if ( 4 === this.readyState && ( 404 === this.status || 401 === this.status ) ) {
				console.log( 'An error occurred.' );
			}

			delFadeOut();
		};
		xhr.send( formData );
	}

	function delFadeOut() {

		const containerResults = document.getElementById( 'container-results' );

		if ( ! containerResults ) {
			return;
		}
		containerResults.classList.remove( 'fade-out' );
	}

	function setEmptyTemplate() {

		const containerResults = document.getElementById( 'container-results' );

		if ( ! containerResults ) {
			return;
		}
		containerResults.classList.add( 'fade-out' );
	}

	function triggerUserForm() {
		userFormClassObject = new UserDataForm();

		var form = document.getElementById( 'user-data-form' );
		if ( ! form ) {
			return;
		}
		form.addEventListener(
			'submit',
			function ( event ) {
				event.preventDefault();
				userFormSubmit( event.target );
			}
		);
	}

	function userFormSubmit( elForm ) {
		var formData = new FormData( elForm );

		var xhr = new XMLHttpRequest();
		xhr.open( 'POST', wealProfilePageData.root + 'weal-profile/v1/info-tab/', true );
		xhr.setRequestHeader( 'X-WP-Nonce', wealProfilePageData.nonce );
		xhr.onreadystatechange = function () {
			if ( 4 === this.readyState && 200 === this.status ) {
				console.log( this.response );

				successAjaxButtonEvent( 'success' );
			}
			if ( 4 === this.readyState && ( 404 === this.status || 401 === this.status ) ) {
				console.log( 'An error occurred.' );
				successAjaxButtonEvent( 'warning' );
			}
		};
		xhr.send( formData );
	}

	function successAjaxButtonEvent( statusClass ) {
		var formUserButton = document.getElementById( 'form-user-button' );
		if ( ! formUserButton ) {
			return;
		}
		formUserButton.classList.add( statusClass );
		if ( 'success' === statusClass ) {
			setTimeout(
				function () {
					toggleBtn( false );
				},
				1500
			);
		}
	}

	function editingUserData( el ) {
		if ( userFormClassObject ) {
			userFormClassObject.editingUserData( el );
		}
	}

	function toggleBtn( isFormChanged ) {
		var formUserButton = document.getElementById( 'form-user-button' );
		if ( ! formUserButton ) {
			return;
		}
		formUserButton.style.display = isFormChanged ? 'block' : 'none';
		if ( isFormChanged ) {
			formUserButton.classList.remove( 'success' );
			formUserButton.classList.remove( 'warning' );
		}
	}
	window.toggleBtn = toggleBtn;

	function switchOtherUserTab( el ) {
		var tabs = document.querySelectorAll( '.activity-button' );
		tabs.forEach(
			function ( t ) {
				t.classList.remove( 'active' );
			}
		);
		el.classList.add( 'active' );

		var tab        = el.getAttribute( 'data-tab' );
		var postsEl    = document.getElementById( 'other-user-posts' );
		var commentsEl = document.getElementById( 'other-user-comments' );
		if ( postsEl ) {
			postsEl.style.display = 'posts' === tab ? 'block' : 'none';
		}
		if ( commentsEl ) {
			commentsEl.style.display = 'comments' === tab ? 'block' : 'none';
		}

		var queryParams = new URLSearchParams( window.location.search );
		if ( 'posts' === tab ) {
			queryParams.delete( 'comments_page' );
		} else {
			queryParams.delete( 'posts_page' );
		}
		var newUrl = queryParams.toString() ? '?' + queryParams.toString() : window.location.pathname;
		history.replaceState( null, null, newUrl );
	}

	function switchMyAccountTab( el ) {
		var tabs = document.querySelectorAll( '.activity-button' );
		tabs.forEach(
			function ( t ) {
				t.classList.remove( 'active' );
			}
		);
		el.classList.add( 'active' );

		loadMyAccountSubtab( el.getAttribute( 'data-tab' ), 1 );
	}

	function loadMyAccountSubtab( tab, page ) {
		var endpoint = 'my-profile/comments/';
		if ( 'posts' === tab ) {
			endpoint = 'my-profile/posts/';
		}

		var cacheKey = 'sub:' + tab + ':page:' + ( page || 1 );
		var cached   = cacheGet( cacheKey );
		if ( cached ) {
			var container = document.getElementById( 'my-profile-subtab-content' );
			if ( container ) {
				container.innerHTML = cached;
			}
			attachMyAccountPagination( tab );
			updateMyAccountSubtabUrl( tab, page || 1 );
			delButtonSubcontent();
			return;
		}

		var formData = new FormData();
		formData.append( 'page', page || 1 );

		var xhr = new XMLHttpRequest();
		xhr.open( 'POST', wealProfilePageData.root + 'weal-profile/v1/' + endpoint, true );
		xhr.setRequestHeader( 'X-WP-Nonce', wealProfilePageData.nonce );
		xhr.onreadystatechange = function () {
			if ( 4 === this.readyState && 200 === this.status ) {
				var json      = JSON.parse( this.response );
				var container = document.getElementById( 'my-profile-subtab-content' );
				if ( container ) {
					container.innerHTML = json.html;
				}
				cacheSet( cacheKey, json.html );
				attachMyAccountPagination( tab );
				updateMyAccountSubtabUrl( tab, page );
			}
			if ( 4 === this.readyState && ( 404 === this.status || 401 === this.status ) ) {
				console.log( 'An error occurred.' );
			}

			delButtonSubcontent()

		};
		xhr.send( formData );
	}

	function delButtonSubcontent() {
		const containerResults = document.getElementById( 'my-profile-subtab-content' );
		if ( ! containerResults ) {
			return;
		}
		containerResults.classList.remove( 'fade-out' );
	}

	function initMyAccountSubtab() {
		var tabs = document.querySelectorAll( '.activity-button' );
		if ( 0 === tabs.length ) {
			return;
		}

		var params = getParameters( getNavUrl() );
		var b      = params.b || 'p';
		var tab    = 'c' === b ? 'comments' : 'posts';
		var page   = parseInt( params.my_page, 10 ) || 1;

		tabs.forEach(
			function ( t ) {
				t.classList.remove( 'active' );
				if ( t.getAttribute( 'data-tab' ) === tab ) {
					t.classList.add( 'active' );
				}
			}
		);

		loadMyAccountSubtab( tab, page );
	}

	function attachMyAccountPagination( tab ) {
		var container = document.getElementById( 'my-profile-subtab-content' );
		if ( ! container ) {
			return;
		}

		var links = container.querySelectorAll( '.weal-pagination a' );
		links.forEach(
			function ( link ) {
				link.addEventListener(
					'click',
					function ( e ) {
						e.preventDefault();
						var url  = new URL( link.href );
						var page = url.searchParams.get( 'my_page' );
						if ( page ) {
							loadMyAccountSubtab( tab, parseInt( page, 10 ) );
						}
					}
				);
			}
		);
	}

	function updateMyAccountSubtabUrl( tab, page ) {
		var queryParams = new URLSearchParams( window.location.search );
		queryParams.set( 'b', 'comments' === tab ? 'c' : 'p' );
		if ( page > 1 ) {
			queryParams.set( 'my_page', page );
		} else {
			queryParams.delete( 'my_page' );
		}
		history.replaceState( null, null, '?' + queryParams.toString() );
	}

	function getNavUrl() {
		return window.location.search.replace( '?', '' );
	}

	function getParameters( url ) {
		var params = {};
		url        = url.toLowerCase();
		url        = url.split( '&' );

		for ( var i = 0, urlLength = url.length; i < urlLength; i++ ) {
			var prop       = url[ i ].slice( 0, url[ i ].search( '=' ) );
			var value      = url[ i ].slice( url[ i ].search( '=' ) ).replace( '=', '' );
			params[ prop ] = value;
		}
		return params;
	}

	function checkTabGetParamsLoading() {

		if ( ! wealProfilePageData.is_own_profile ) {
			return;
		}

		var params = getParameters( getNavUrl() );

		if ( 'info' === ( typeof params !== 'undefined' && params !== null ? params.tab : undefined ) ) {
			getPage(
				{
					clickId: 'tab-button-3',
				}
			);
			TabsSwitcherHelper.switch( 'tab-button-3' );
		} else {
			getPage(
				{
					clickId: 'tab-button-1',
				}
			);
			TabsSwitcherHelper.switch( 'tab-button-1' );
		}

	}

	document.addEventListener(
		'click',
		function ( e ) {
			var icon = e.target.closest( '.achievement-icon' );
			if ( ! icon ) {
				return;
			}

			var description = icon.getAttribute( 'data-description' );
			if ( ! description ) {
				return;
			}

			var existing = document.querySelector( '.achievement-tooltip' );
			if ( existing ) {
				existing.remove();
			}

			var tooltip         = document.createElement( 'span' );
			tooltip.className   = 'achievement-tooltip';
			tooltip.textContent = description;

			var rect           = icon.getBoundingClientRect();
			tooltip.style.left = ( rect.left + rect.width / 2 ) + 'px';
			tooltip.style.top  = rect.top + 'px';

			document.body.appendChild( tooltip );

			var tooltipRect    = tooltip.getBoundingClientRect();
			tooltip.style.left = ( rect.left + rect.width / 2 - tooltipRect.width / 2 ) + 'px';

			setTimeout(
				function () {
					if ( tooltip.parentNode ) {
						tooltip.remove();
					}
				},
				4000
			);
		}
	);
} )();
