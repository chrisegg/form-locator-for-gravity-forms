=== Form Locator for Gravity Forms ===
Author URI: https://gravityranger.com
Plugin URI: https://github.com/chrisegg/form-locator-for-gravity-forms
Donate link: https://gravityranger.com/donate
Contributors: chrisegg
Tags: Gravity Forms, Add-on, Utility, Page Builder, Elementor, Beaver Builder, Avada, Divi, WPBakery
Requires at least: 6.7
Tested up to: 7.0.0
Requires PHP: 8.0.0
Stable tag: 2.0.1
License: GPLv2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Gravity Forms add-on for comprehensive form detection across WordPress pages, posts, and page builders. Find forms embedded via shortcodes, blocks, widgets, and page builder modules.

== Description ==

Form Locator for Gravity Forms is a Gravity Forms add-on that scans your entire WordPress site to find where Gravity Forms are embedded. Built using the official Gravity Forms Add-On Framework, it provides seamless integration with your Gravity Forms installation and detects forms across multiple embedding methods and page builders.

**Key Features:**

* **Add-On Framework**: Built using the Gravity Forms Add-On Framework for seamless integration
* **Multi-Method Detection**: Finds forms embedded via shortcodes, Gutenberg blocks, and page builder widgets
* **Page Builder Support**: Comprehensive support for Elementor, Beaver Builder, Avada Fusion Builder, Divi, and WPBakery
* **Form Status Tracking**: Shows active, inactive, trashed, and deleted form status
* **Native GF Interface**: Uses Gravity Forms native styling and admin interface patterns
* **Enhanced Security**: Built with Gravity Forms security patterns and capability management
* **Comprehensive Scanning**: Searches all published posts, pages, and custom post types

**Supported Embedding Methods:**

* **Shortcodes**: `[gravityform id="1"]` and variations
* **Gutenberg Blocks**: Gravity Forms blocks with form ID
* **Login Forms**: Gravity Forms login form shortcodes
* **Page Builder Widgets**: Native widgets in supported page builders

**Supported Page Builders:**

* **Elementor**: Gravity Forms widget, GravityKits widgets, custom Gravity Forms widgets
* **Beaver Builder**: Gravity Forms widget modules
* **Avada Fusion Builder**: Built-in Gravity Forms elements and shortcodes
* **Divi Builder**: Gravity Forms modules and shortcodes
* **WPBakery Page Builder**: Gravity Forms modules and shortcodes

**What It Detects:**

* Form IDs from all embedding methods
* Form status (active, inactive, trashed, deleted)
* Login form usage
* Page builder specific implementations
* Custom add-on integrations

**Security Features:**

* Output escaping for XSS protection (esc_html, esc_attr, esc_url)
* Capability checks for admin access (manage_options)
* Secure database queries with prepared statements
* Input sanitization and validation
* The Form Locator page is read-only; no form submissions require nonce verification

== Installation ==

**Prerequisites:**
- WordPress 6.7 or higher
- PHP 8.0 or higher  
- Gravity Forms 2.7 or higher (required)

**Method 1: Traditional Installation**
1. Upload the plugin files to the `/wp-content/plugins/form-locator-for-gravity-forms` directory, or install the plugin through the WordPress admin screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress

**Method 2: Composer Installation**
1. Add the plugin to your project: `composer require chrisegg/form-locator-for-gravity-forms`
2. Activate the plugin through the 'Plugins' screen in WordPress

**After Installation:**
3. Navigate to **Forms → Form Locator** in the admin menu
4. View your comprehensive form usage report

**Note:** This add-on integrates seamlessly with your existing Gravity Forms installation and appears as a native menu item under the Forms menu.

== Frequently Asked Questions ==

= Does it only work with Gravity Forms? =

Yes, this is a Gravity Forms add-on that requires Gravity Forms 2.4 or higher to be installed and activated. It will not function without Gravity Forms.

= What page builders are supported? =

The plugin supports Elementor, Beaver Builder, Avada Fusion Builder, Divi Builder, and WPBakery Page Builder. It detects forms embedded through their native widgets and modules.

= Does it detect forms in custom post types? =

Yes, the plugin scans all published posts regardless of post type, including custom post types.

= Is it secure? =

Yes, this add-on is built using the official Gravity Forms Add-On Framework which includes built-in security features. It also follows WordPress security best practices including proper data sanitization, output escaping, capability checks, and secure database queries.

= Does it work with Gravity Forms add-ons? =

Yes, the plugin includes generic detection for add-ons like GravityKits and other custom implementations.

= What if a form is deleted or trashed? =

The plugin will still detect the form reference and show its status as "deleted" or "trashed" so you can identify orphaned form references.

= Does it affect site performance? =

The plugin only scans when you visit the Form Locator page. It doesn't run any background processes or affect frontend performance.

== Screenshots ==

1. Main Form Locator interface with statistics
2. Detailed results table showing form locations and status
3. Page builder detection results

== Changelog ==

= 2.0.1: March 17, 2026 =
* **FIX**: Auto-update from GitHub releases
  * Fixed "Download failed. Not Found" error when updating
  * Package URL now uses actual tag name from GitHub API (supports tags with or without "v" prefix)
* **IMPROVED**: Plugin info modal display
  * Description, Installation, and Changelog tabs now render formatted HTML instead of raw markdown
  * Expanded Description tab with full readme content (features, page builders, security)
  * Installation tab now shows prerequisites and both Traditional/Composer methods

= 2.0.0: March 16, 2026 =
* **MAJOR**: Complete refactor to official Gravity Forms Add-On Framework
  * Now extends GFAddOn class for seamless integration
  * Native Gravity Forms admin interface styling
  * Built-in security and capability management
  * Proper add-on initialization and dependency checking
* **NEW**: Enhanced admin interface
  * Native Gravity Forms styling and components
  * Improved form status indicators with color coding
  * Professional statistics cards
  * Responsive grid layout
* **NEW**: Framework benefits
  * Automatic minimum requirements checking
  * Built-in settings framework integration
  * Enhanced security patterns
  * Better internationalization support
* **RESTORED**: Custom GitHub update system
  * Automatic update notifications from GitHub releases
  * One-click updates from WordPress admin
  * Cached API calls (12-hour transient)
  * Integrated changelog display from GitHub release notes
* **IMPROVED**: Code organization
  * Eliminated unnecessary GF prefixes
  * Cleaner class structure and naming
  * Better separation of concerns
* **BREAKING**: Requires Gravity Forms 2.5+
  * This version requires a minimum of Gravity Forms 2.5
  * Older versions of Gravity Forms are no longer supported

= 1.1.0: June 20, 2025 =
* **NEW**: Added comprehensive page builder support
  * Elementor widget detection (Gravity Forms, GravityKits, custom widgets)
  * Beaver Builder module detection with widget-specific form ID extraction
  * Avada Fusion Builder element and shortcode detection
  * Divi Builder module and shortcode detection
  * WPBakery Page Builder module detection
* **NEW**: Improved admin interface
  * Modern gradient header design
  * Professional statistics display
  * Color-coded form status indicators
  * Responsive table layout
* **NEW**: Security enhancements
  * Proper output escaping (esc_html, esc_url)
  * Input sanitization and validation
  * Secure database queries
* **NEW**: Automatic update notifications
  * Custom update checker for GitHub releases
  * Automatic version comparison and notifications
  * Integrated changelog display from GitHub releases
  * One-click updates from WordPress admin
  * Cached API calls for optimal performance
* **NEW**: Composer support
  * Added composer.json for dependency management
  * Plugin can now be installed via Composer
  * Proper package metadata and licensing
* **IMPROVED**: Menu integration
  * Moved to Forms submenu for better organization
  * Proper menu positioning and URL structure
* **IMPROVED**: Error handling
  * Graceful handling of malformed data
  * Better exception handling
  * Comprehensive logging system

= 1.0.0: March 4, 2025 =
* Initial release
* Basic shortcode detection
* Simple admin interface
* Form status checking

== Upgrade Notice ==

= 2.0.1 =
Fixes the "Download failed. Not Found" error when updating from GitHub. Plugin info modal now displays formatted Description, Installation, and Changelog tabs.

= 2.0.0 =
MAJOR UPDATE: Complete refactor to official Gravity Forms Add-On Framework. This version requires Gravity Forms 2.4+ and provides native GF integration, enhanced security, improved admin interface, and automatic updates from GitHub releases.

= 1.1.0 =
This major update adds comprehensive page builder support, enhanced security, and a modern interface. The plugin now detects forms across all major page builders and provides a much more detailed analysis of your Gravity Forms usage.
