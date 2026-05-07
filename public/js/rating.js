/* global wealRating */
'use strict';

document.addEventListener(
	'DOMContentLoaded',
	function () {
		if ( ! window.wealRating || ! window.wealRating.apiUrl ) {
			return;
		}

		var containers = document.querySelectorAll( '.post-rating' );

		containers.forEach(
			function ( container ) {
				var postId     = container.getAttribute( 'data-post-id' );
				var stars      = container.querySelectorAll( '.rating-stars .star-wrapper' );
				var cookieName = 'weal_voted_post_' + postId;

				if ( document.cookie.indexOf( cookieName + '=1' ) !== -1 ) {
						lockStars( container, true );
						return;
				}

				stars.forEach(
					function ( star ) {
						star.addEventListener(
							'mouseenter',
							function () {
								var rate = parseInt( star.getAttribute( 'data-rate' ), 10 );
								highlightStars( stars, rate );
							}
						);

						star.addEventListener(
							'mouseleave',
							function () {
								resetStars( stars );
							}
						);

						star.addEventListener(
							'click',
							function () {
								var rate = parseInt( star.getAttribute( 'data-rate' ), 10 );
								sendRating( postId, rate, container );
							}
						);
					}
				);
			}
		);

		function highlightStars( stars, rate ) {
			stars.forEach(
				function ( star ) {
					var currentRate = parseInt( star.getAttribute( 'data-rate' ), 10 );

					if ( currentRate <= rate ) {
							star.style.setProperty( '--fill', '100%' );
					} else {
						star.style.setProperty( '--fill', '0%' );
					}
				}
			);
		}

		function resetStars( stars ) {
			stars.forEach(
				function ( star ) {
					var initialFill = star.getAttribute( 'data-initial-fill' ) || '0%';
					star.style.setProperty( '--fill', initialFill );
				}
			);
		}

		function sendRating( postId, rating, container ) {
			lockStars( container, false );

			fetch(
				wealRating.apiUrl,
				{
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'X-WP-Nonce': wealRating.nonce,
					},
					body: JSON.stringify(
						{
							post_id: postId,
							rating: rating,
						}
					),
				}
			)
				.then(
					function ( response ) {
						return response.json();
					}
				)
				.then(
					function ( data ) {
						if ( data.success ) {
								updateUI( container, data.data.average, data.data.count );
								document.cookie =
							'weal_voted_post_' +
							postId +
							'=1; path=/; max-age=' +
							60 * 60 * 24 * 365;
						} else {
							alert( data.data.message || 'Error' );
							lockStars( container, true );
						}
					}
				)
				.catch(
					function () {
						alert( 'Request failed.' );
						lockStars( container, true );
					}
				);
		}

		function updateUI( container, average, count ) {
			var avgEl = container.querySelector( '.average-value' );
			var cntEl = container.querySelector( '.count-value' );
			var stars = container.querySelectorAll( '.rating-stars .star-wrapper' );

			if ( avgEl ) {
				avgEl.textContent = average;
			}

			if ( cntEl ) {
				cntEl.textContent = count;
			}

			stars.forEach(
				function ( star, index ) {
					var i    = index + 1;
					var fill = '0%';

					if ( average >= i ) {
							fill = '100%';
					} else if ( average < ( i - 1 ) ) {
						fill = '0%';
					} else {
						fill = ( ( average - ( i - 1 ) ) * 100 ) + '%';
					}

					star.setAttribute( 'data-initial-fill', fill );
					star.style.setProperty( '--fill', fill );
				}
			);

			var metaValue = container.querySelector(
				'meta[itemprop="ratingValue"]'
			);
			var metaCount = container.querySelector(
				'meta[itemprop="ratingCount"]'
			);

			if ( metaValue ) {
				metaValue.setAttribute( 'content', average );
			}

			if ( metaCount ) {
				metaCount.setAttribute( 'content', count );
			}

			lockStars( container, true );
		}

		function lockStars( container, voted ) {
			var stars = container.querySelectorAll(
				'.rating-stars .star-wrapper'
			);

			stars.forEach(
				function ( star ) {
					star.style.cursor  = voted ? 'default' : 'pointer';
					star.style.opacity = voted ? '0.6' : '1';

					if ( voted ) {
							star.classList.add( 'voted' );
					}
				}
			);
		}
	}
);