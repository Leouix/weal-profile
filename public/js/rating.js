/* global wealRating */
'use strict';

document.addEventListener( 'DOMContentLoaded', function () {
	if ( ! window.wealRating || ! window.wealRating.apiUrl ) {
		return;
	}

	var containers = document.querySelectorAll( '.post-rating' );

	containers.forEach( function ( container ) {
		var postId = container.getAttribute( 'data-post-id' );
		var stars  = container.querySelectorAll( '.rating-stars .dashicons' );
		var cookieName = 'weal_voted_post_' + postId;

		// Check if user already voted via cookie.
		if ( document.cookie.indexOf( cookieName + '=1' ) !== -1 ) {
			lockStars( container, true );
			return;
		}

		// Hover effect.
		stars.forEach( function ( star ) {
			star.addEventListener( 'mouseenter', function () {
				var rate = parseInt( star.getAttribute( 'data-rate' ), 10 );
				highlightStars( stars, rate );
			} );

			star.addEventListener( 'mouseleave', function () {
				resetStars( stars );
			} );

			// Click handler.
			star.addEventListener( 'click', function () {
				var rate = parseInt( star.getAttribute( 'data-rate' ), 10 );
				sendRating( postId, rate, container );
			} );
		} );
	} );

	/**
	 * Highlights stars up to a specific index.
	 *
	 * @param {NodeList} stars - The star elements.
	 * @param {number} rate - The rating value.
	 */
	function highlightStars( stars, rate ) {
		stars.forEach( function ( s ) {
			var r = parseInt( s.getAttribute( 'data-rate' ), 10 );
			if ( r <= rate ) {
				s.classList.remove( 'dashicons-star-empty' );
				s.classList.add( 'dashicons-star-filled' );
			} else {
				s.classList.remove( 'dashicons-star-filled' );
				s.classList.add( 'dashicons-star-empty' );
			}
		} );
	}

	/**
	 * Resets stars to empty state.
	 *
	 * @param {NodeList} stars - The star elements.
	 */
	function resetStars( stars ) {
		stars.forEach( function ( s ) {
			s.classList.remove( 'dashicons-star-filled' );
			s.classList.add( 'dashicons-star-empty' );
		} );
	}

	/**
	 * Sends rating via REST API.
	 *
	 * @param {string} postId - The post ID.
	 * @param {number} rating - The rating value.
	 * @param {HTMLElement} container - The rating container.
	 */
	function sendRating( postId, rating, container ) {
		var stars = container.querySelectorAll( '.rating-stars .dashicons' );
		lockStars( container, false );

		fetch( wealRating.apiUrl, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': wealRating.nonce,
			},
			body: JSON.stringify( {
				post_id: postId,
				rating: rating,
			} ),
		} )
			.then( function ( response ) {
				return response.json();
			} )
			.then( function ( data ) {
				if ( data.success ) {
					updateUI( container, data.data.average, data.data.count );
					// Set cookie for 1 year.
					document.cookie = 'weal_voted_post_' + postId + '=1; path=/; max-age=' + 60 * 60 * 24 * 365;
				} else {
					alert( data.data.message || 'Error' );
					lockStars( container, true );
				}
			} )
			.catch( function () {
				alert( 'Request failed.' );
				lockStars( container, true );
			} );
	}

	/**
	 * Updates the UI with new rating data.
	 *
	 * @param {HTMLElement} container - The rating container.
	 * @param {number} average - New average.
	 * @param {number} count - New count.
	 */
	function updateUI( container, average, count ) {
		var avgEl = container.querySelector( '.average-value' );
		var cntEl = container.querySelector( '.count-value' );

		if ( avgEl ) {
			avgEl.textContent = average;
		}
		if ( cntEl ) {
			cntEl.textContent = count;
		}

		// Update schema meta tags.
		var metaValue = container.querySelector( 'meta[itemprop="ratingValue"]' );
		var metaCount = container.querySelector( 'meta[itemprop="ratingCount"]' );
		if ( metaValue ) metaValue.setAttribute( 'content', average );
		if ( metaCount ) metaCount.setAttribute( 'content', count );

		lockStars( container, true );
	}

	/**
	 * Locks the stars visually.
	 *
	 * @param {HTMLElement} container - The rating container.
	 * @param {boolean} voted - Whether the user has voted.
	 */
	function lockStars( container, voted ) {
		var stars = container.querySelectorAll( '.rating-stars .dashicons' );
		stars.forEach( function ( s ) {
			s.style.cursor = voted ? 'default' : 'pointer';
			s.style.opacity = voted ? '0.6' : '1';
			if ( voted ) {
				s.classList.add( 'voted' );
			}
		} );
	}
} );
