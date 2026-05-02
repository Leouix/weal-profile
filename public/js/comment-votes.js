(function () {
	'use strict';

	const REST_URL     = wealCommentVotesData.root + 'weal-profile/v1/comment-vote';
	const NONCE        = wealCommentVotesData.nonce;
	const IS_LOGGED_IN = wealCommentVotesData.isLoggedIn;

	document.addEventListener(
		'click',
		function (e) {
			const voteBtn = e.target.closest( '.weal-vote-btn' );
			if ( ! voteBtn) {
				return;
			}

			e.preventDefault();

			if ( ! IS_LOGGED_IN) {
				return;
			}

			const commentId = voteBtn.dataset.commentId;
			const action    = voteBtn.dataset.action;

			if ( ! commentId || ! action) {
				return;
			}

			fetch(
				REST_URL,
				{
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'X-WP-Nonce': NONCE,
					},
					body: JSON.stringify(
						{
							comment_id: parseInt( commentId, 10 ),
							vote_type: action,
						}
					),
				}
			)
			.then(
				function (response) {
					return response.json();
				}
			)
			.then(
				function (data) {
					if (data.success) {
						updateVoteUI( commentId, data );
					}
				}
			)
			.catch(
				function (error) {
					console.error( 'Vote error:', error );
				}
			);
		}
	);

	function updateVoteUI(commentId, data) {
		const container = document.querySelector( '.weal-vote-container[data-comment-id="' + commentId + '"]' );
		if ( ! container) {
			return;
		}

		const likeBtn      = container.querySelector( '.weal-vote-like' );
		const dislikeBtn   = container.querySelector( '.weal-vote-dislike' );
		const likeCount    = container.querySelector( '.weal-like-count' );
		const dislikeCount = container.querySelector( '.weal-dislike-count' );

		if (likeCount) {
			likeCount.textContent = data.likes;
		}
		if (dislikeCount) {
			dislikeCount.textContent = data.dislikes;
		}

		if (likeBtn) {
			likeBtn.classList.remove( 'weal-vote-active' );
		}
		if (dislikeBtn) {
			dislikeBtn.classList.remove( 'weal-vote-active' );
		}

		if ('liked' === data.user_status && likeBtn) {
			likeBtn.classList.add( 'weal-vote-active' );
		} else if ('disliked' === data.user_status && dislikeBtn) {
			dislikeBtn.classList.add( 'weal-vote-active' );
		}
	}
})();
