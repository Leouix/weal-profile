# Weal Profile

**Contributors:** leouix, Weal  
**Tags:** manage-user-page, user-profile, user-account, account-page  
**Donate link:** https://weal.cloud/  
**Requires at least:** 6.2  
**Tested up to:** 7.0
**Requires PHP:** 7.4  
**Stable tag:** 1.0.0  
**License:** GPL-2.0-or-later  
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html  

A WordPress plugin that creates a personal account page where logged-in users can manage their profile information and review their site activity.

## Description

Weal Profile adds a dedicated front-end account page for authenticated users (default: `/my-account`), allowing them to manage selected profile fields without accessing the WordPress admin dashboard.

The plugin also includes an admin settings page where site administrators can configure the account page and choose which profile fields are editable by users.

### Admin Features

Available in the WordPress admin menu under **Weal Profile**.

Administrators can:

- Enable or disable profile fields shown on the public account page
- Set a custom URL slug for the personal account page (default: `/my-account`)
- Enable or disable the comment likes/dislikes system

Available profile fields:

- Avatar\Gravatar
- Display Name
- Website URL
- Nickname
- First Name
- Last Name
- Biography

### Front-End User Features

On the public account page, logged-in users can:

* Edit their personal profile information (based on admin settings)
* Upload and remove a profile avatar
* View their comment activity and reaction statistics (likes/dislikes received)

### Post Rating System

A 5-star rating system is automatically displayed on single post pages. Features:

- Visitors can rate posts from 1 to 5 stars
- Rating is stored in post meta (`rating_sum`, `rating_count`, `rating_average`)
- Partial star visualisation (e.g. 3.5 stars shows 3 full + 1 half-filled star)
- Anti-double-vote protection via cookies
- AJAX-based submission with instant UI update
- Schema.org `AggregateRating` markup for SEO

### Comment Likes / Dislikes

Logged-in users can like or dislike comments on posts. Features:

- Toggle likes and dislikes with a single click
- Active state visual feedback on voted buttons
- Vote counts update instantly via AJAX
- Configurable toggle in admin settings to enable/disable the system
- Comment reaction statistics shown on the user profile page (total likes/dislikes received, top voted comments)
- Dedicated database table (`weal_comment_votes`) for efficient vote storage
- Unique constraint prevents duplicate votes per user per comment

### Activity Center

The plugin provides a convenient centralized space where users can track their comment history and quickly return to conversations they participated in.

## Installation

1. Upload the plugin folder to the `/wp-content/plugins/` directory, or install it through the WordPress Plugins screen.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Go to **WordPress Admin → Weal Profile**.
4. Configure the account page URL and choose which profile fields users can edit.
5. Visit the account page (default: `/my-account`) while logged in.

## Database

The plugin creates a custom database table on activation:

- `wp_weal_comment_votes` — stores comment likes/dislikes (comment_id, user_id, is_liked, created_at)

When updating the plugin, the table is automatically created or updated via a version check — no need to deactivate and reactivate.

## Frequently Asked Questions

### How can I access another user's page?

Currently, users can only view and manage their own account information.

Support for public or shared user profile pages may be added in a future release.

### How does the rating system prevent double voting?

Each vote sets a cookie (`weal_voted_post_{post_id}`) valid for 1 year. Both the REST API and JavaScript check for existing cookies to prevent multiple votes from the same browser.

### Can I disable the comment likes feature?

Yes. Go to **WordPress Admin → Weal Profile** and uncheck "Enable likes and dislikes on comments".
