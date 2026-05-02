# Weal Profile

**Contributors:** Leouix  
**Tags:** manage-user-page, user-profile, user-account, account-page  
**Donate link:** https://weal.cloud/  
**Requires at least:** 6.2  
**Tested up to:** 6.9  
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

Available profile fields:

- Display Name
- Website URL
- Nickname
- First Name
- Last Name
- Biography

### Front-End User Features

On the public account page, logged-in users can:

- Edit their personal profile information (based on admin settings)
- Upload and remove a custom profile avatar
- View a list of all comments they have posted on the site
- Open direct links to related posts and discussions

### Comment Activity Center

The plugin provides a convenient centralized space where users can track their comment history and quickly return to conversations they participated in.

### Avatar Support

Users can upload a custom profile avatar directly from the account page. Uploaded avatars are stored in the WordPress Media Library and can be removed at any time.

## Installation

1. Upload the plugin folder to the `/wp-content/plugins/` directory, or install it through the WordPress Plugins screen.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Go to **WordPress Admin → Weal Profile**.
4. Configure the account page URL and choose which profile fields users can edit.
5. Visit the account page (default: `/my-account`) while logged in.

## Frequently Asked Questions

### How can I access another user's page?

Currently, users can only view and manage their own account information.

Support for public or shared user profile pages may be added in a future release.

