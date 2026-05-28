/**
 * Contains the relevant methods and functions for the plugin
 *
 * @package weal-profile
 */

let containerResults;
let userFormClassObject;
let tabButton1;
let tabButton2;
let tabButton3;


window.addEventListener(
	'load',
	function () {
		containerResults = document.getElementById( 'container-results' );
		tabButton1       = document.getElementById( 'tab-button-1' );
		// tabButton2 = document.getElementById( 'tab-button-2' ).
		tabButton3 = document.getElementById( 'tab-button-3' );

		checkTabGetParamsLoading();

		const avatarInput = document.querySelector(
			'.weal-profile-avatar-form input[type="file"][name="weal_profile_avatar"]'
		);

		if ( ! avatarInput) {
			return;
		}

		avatarInput.addEventListener(
			'change',
			function () {
				if ( ! this.files || this.files.length === 0) {
					return;
				}

				const form = this.closest( 'form' );

				if ( ! form) {
					return;
				}

				form.submit();
			}
		);
	}
);

function triggerUserForm() {
	userFormClassObject = new UserDataForm();

	var form = document.getElementById( 'user-data-form' );
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
	xhr.onreadystatechange = function ( res ) {
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

function switchTab( el ) {
	getPage(
		{
			clickId: el.id,
		}
	);
	TabsSwitcherHelper.switch( el.id );
}

function getPage( clickData ) {
	var clickId = clickData.clickId;

	var formData = new FormData();
	formData.append( 'tabName', TabsSwitcherHelper.getTabName( clickId ) );

	var xhr = new XMLHttpRequest();
	xhr.open( 'POST', wealProfilePageData.root + 'weal-profile/v1/switch-tab-ajax/', true );
	xhr.setRequestHeader( 'X-WP-Nonce', wealProfilePageData.nonce );
	xhr.onreadystatechange = function ( res ) {
		if ( 4 === this.readyState && 200 === this.status ) {
			var json                   = JSON.parse( this.response );
			containerResults.innerHTML = json.html;

			const check1 = TabsSwitcherHelper.getTabName( clickId );

			replaceUrlParam( check1 );

			if ( 'info' === TabsSwitcherHelper.getTabName( clickId ) ) {
				triggerUserForm();
			}
		}
		if ( 4 === this.readyState && ( 404 === this.status || 401 === this.status ) ) {
			console.log( 'An error occurred.' );
		}
	};
	xhr.send( formData );
}

function successAjaxButtonEvent( statusClass ) {
	var formUserButton = document.getElementById( 'form-user-button' );
	formUserButton.classList.add( statusClass );
	if ( 'success' === statusClass ) {
		setTimeout(
			function () {
				toggleBtn( false ); },
			1500
		);
	}
}

function editingUserData( el ) {
	userFormClassObject.editingUserData( el );
}

function toggleBtn( isFormChanged ) {
	var formUserButton = document.getElementById( 'form-user-button' );
	if ( undefined !== formUserButton && null !== formUserButton ) {
		isFormChanged ? formUserButton.style.display = 'block' : formUserButton.style.display = 'none';
		if ( isFormChanged ) {
			formUserButton.classList.remove( 'success' );
			formUserButton.classList.remove( 'warning' );
		}
	}
}

class TabsSwitcherHelper {
	static tabs = {
		'tab-button-1': 'activity',
		'tab-button-2': 'users',
		'tab-button-3': 'info',
	};

	static getTabName( buttonId ) {
		return this.tabs[ buttonId ];
	}

	static switch ( activeTabId ) {

		tabButton1.classList.remove( 'active' );
		// tabButton2.classList.remove( 'active' ).
		tabButton3.classList.remove( 'active' );

		switch ( activeTabId ) {
			case 'tab-button-1':
				tabButton1.classList.add( 'active' );
				break;
			case 'tab-button-2':
				// tabButton2.classList.add( 'active' ).
				break;
			case 'tab-button-3':
				tabButton3.classList.add( 'active' );
				break;
		}
	}
}
function getNavUrl() {
	// Get URL.
	return window.location.search.replace( '?', '' );
}

function getParameters( url ) {
	// Params obj.
	var params = {};
	// To lowercase.
	url = url.toLowerCase();
	// To array.
	url = url.split( '&' );

	// Iterate over URL parameters array.
	var length = url.length;
	for ( var i = 0; i < length; i++ ) {
		// Create prop.
		var prop = url[ i ].slice( 0, url[ i ].search( '=' ) );
		// Create Val.
		var value = url[ i ].slice( url[ i ].search( '=' ) ).replace( '=', '' );
		// Params New Attr.
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

function replaceUrlParam( paramValue ) {
	var queryParams = new URLSearchParams( window.location.search );
	queryParams.set( 'tab', paramValue );
	history.replaceState( null, null, '?' + queryParams.toString() );
}

function switchOtherUserTab( el ) {
	var tabs = document.querySelectorAll( '.other-user-tab' );
	tabs.forEach(
		function ( t ) {
			t.classList.remove( 'active' ); }
	);
	el.classList.add( 'active' );

	var tab = el.getAttribute( 'data-tab' );
	document.getElementById( 'other-user-posts' ).style.display    =
		( 'posts' === tab ) ? 'block' : 'none';
	document.getElementById( 'other-user-comments' ).style.display =
		( 'comments' === tab ) ? 'block' : 'none';
}
