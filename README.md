=== Weal Profile ===
Contributors: leouix, Weal
Tags: manage-user-page, user-profile, user-account, achievements, comments-rating
Donate link: https://www.donationalerts.com/r/weal_plugin
Requires at least: 6.2
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.4.2
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Creates a personal account page for logged-in users to manage profile information, track achievements, and review site activity.

== Description ==

Weal Profile adds a dedicated front-end account page for authenticated users (default: `/my-profile`), allowing them to manage selected profile fields without accessing the WordPress admin dashboard.

Development happens open-source on GitHub. Contributions, issues, and feature requests are welcome:
[GitHub Repository](https://github.com/leouix/weal-profile)

The plugin also includes an admin settings page where site administrators can configure the account page, choose which profile fields are editable by users, and manage gamification thresholds.

== Admin Features ==

Available in the WordPress admin menu under **Weal Profile**.
Administrators can:
* Enable or disable profile fields shown on the public account page
* Set a custom URL slug for the personal account page (default: `/my-profile`)
* Enable or disable the comment likes/dislikes system
* Configure thresholds and comment count targets for each achievement badge

**Available profile fields:**
* Avatar / Gravatar
* Display Name
* Website URL
* Nickname
* First Name
* Last Name
* Biography

== Front-End User Features ==

On the public account page, logged-in users can:
* Edit their personal profile information (based on admin settings)
* Upload and remove a profile avatar
* View their comment activity and reaction statistics (likes/dislikes received)

== Achievements & Badges System ==

A motivational achievements and badges system that rewards users based on their comment activity:
* **Active Commentator badge** `dashicons-awards` — awarded when a user reaches a configurable threshold of approved comments.
* **Cutie badge** `&#x1f970;` (🥰) — awarded when a user receives a configurable number of likes on comments.
* **Angry badge** `&#x1f47f;` (👿) — awarded when a user receives a configurable number of dislikes on comments.

**Key features:**
* Badges are dynamically displayed on user avatars in the comments section.
* Fully configurable targets via the admin panel.
* REST API endpoints for managing achievement settings.

== Comment Likes / Dislikes ==

Logged-in users can like or dislike comments on posts.
* Toggle likes and dislikes with a single click.
* Active state visual feedback on voted buttons.
* Vote counts update instantly via AJAX.
* Configurable toggle in admin settings to enable/disable the system.
* Comment reaction statistics shown on the user profile page (total likes/dislikes received, top voted comments).
* Dedicated database table (`weal_comment_votes`) for efficient vote storage.
* Unique constraint prevents duplicate votes per user per comment.

== Post Rating System ==

A 5-star rating system is automatically displayed on single post pages.
* Visitors can rate posts from 1 to 5 stars.
* Rating is stored in post meta (`rating_sum`, `rating_count`, `rating_average`).
* Partial star visualization (e.g., 3.5 stars shows 3 full + 1 half-filled star).
* Anti-double-vote protection via cookies.
* AJAX-based submission with instant UI update.
* Schema.org `AggregateRating` markup for SEO.

== Activity Center ==

The plugin provides a convenient centralized space where users can track their comment history and quickly return to conversations they participated in.

== Localization ==

The plugin is fully translation-ready and officially supports 4 languages:
* English (EN)
* French (FR)
* Spanish (ES)
* Russian (RU)

== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory, or install it through the WordPress Plugins screen.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Go to **WordPress Admin → Weal Profile**.
4. Configure the account page URL, adjust achievement thresholds, and choose which profile fields users can edit.
5. Visit the account page (default: `/my-profile`) while logged in.

== Database ==

The plugin creates a custom database table on activation:
* `wp_weal_comment_votes` — stores comment likes/dislikes (`comment_id`, `user_id`, `is_liked`, `created_at`).

When updating the plugin, the table is automatically created or updated via a version check — no need to deactivate and reactivate.

== Frequently Asked Questions ==

= How can I access another user's page? =
Logged-in users can access another user's profile page by clicking on their name or avatar link within the website's comments section.

= How does the rating system prevent double voting? =
Each vote sets a cookie (`weal_voted_post_{post_id}`) valid for 1 year. Both the REST API and JavaScript check for existing cookies to prevent multiple votes from the same browser.

= Can I disable the comment likes feature? =
Yes. Go to **WordPress Admin → Weal Profile** and uncheck "Enable likes and dislikes on comments".

== Changelog ==

= 1.4.2 =
* Improved user interaction and security.

= 1.4.1 =
* Added UI and security improvements.

= 1.4.0 =
* Added achievements system: Active User, Nice User, and Angry User badges based on comment activity.
* Configurable comment count targets for each achievement in admin settings.
* Badge display on user avatars in comments.
* REST API endpoints for achievements management in admin panel.

= 1.3.1 =
* Added comment reactions display on user profile page.
* Improved user interaction with the interface.

= 1.3.0 =
* Added client-side caching.
* Improved user interaction with the interface.

= 1.2.2 =
* Improved responsive layout and styling for the profile page.

= 1.2.1 =
* Added display of records on the user page.

= 1.1.0 =
* Added support for public or shared user profile pages.

= 1.0.0 =
* Initial release.