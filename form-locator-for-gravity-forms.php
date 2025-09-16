<?php
/**
 * Form Locator for Gravity Forms
 *
 * @package       GFLOCATOR
 * @author        Chris Eggleston
 * @license       gplv2
 * @version       1.1.0
 *
 * @wordpress-plugin
 * Plugin Name:   Form Locator for Gravity Forms
 * Plugin URI:    https://github.com/chrisegg/form-locator-for-gravity-forms
 * Description:   Lists WordPress pages and posts that contain Gravity Forms block or shortcode, including those deleted, trashed, and inactive.
 * Version:       1.1.0
 * Author:        Chris Eggleston
 * Author URI:    https://gravityranger.com
 * Text Domain:   form-locator-for-gravity-forms
 * Domain Path:   /languages
 * License:       GPLv2
 * License URI:   https://www.gnu.org/licenses/gpl-2.0.html
 * Update URI:    https://github.com/chrisegg/form-locator-for-gravity-forms
 *
 * You should have received a copy of the GNU General Public License
 * along with Form Locator for Gravity Forms. If not, see <https://www.gnu.org/licenses/gpl-2.0.html/>.
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('GFLOCATOR_VERSION', '1.1.0');
define('GFLOCATOR_PLUGIN_FILE', __FILE__);
define('GFLOCATOR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('GFLOCATOR_PLUGIN_URL', plugin_dir_url(__FILE__));

// GitHub repository information
define('GFLOCATOR_GITHUB_REPO', 'chrisegg/form-locator-for-gravity-forms');
define('GFLOCATOR_GITHUB_BRANCH', 'main');

// Update checker debug mode (set to true for testing)
define('GFLOCATOR_UPDATE_DEBUG', true);

// Main Plugin File
require_once plugin_dir_path(__FILE__) . 'includes/class-form-locator-for-gravity-forms.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-form-locator-for-gravity-forms-run.php';

// Initialize the plugin
Form_Locator_For_Gravity_Forms_Run::init();

// Initialize update checker
add_action('init', 'gflocator_init_update_checker');

/**
 * Initialize the update checker
 */
function gflocator_init_update_checker() {
    // Only check for updates in admin
    if (!is_admin()) {
        return;
    }
    
    // Add update checker hooks
    add_filter('pre_set_site_transient_update_plugins', 'gflocator_check_for_update');
    add_filter('plugins_api', 'gflocator_plugin_info', 10, 3);
    add_action('in_plugin_update_message-' . plugin_basename(__FILE__), 'gflocator_update_message');
    
    // Add admin action to manually check for updates (for debugging)
    if (GFLOCATOR_UPDATE_DEBUG && isset($_GET['gflocator_force_update_check'])) {
        add_action('admin_init', 'gflocator_force_update_check');
    }
}

/**
 * Force update check for debugging (only when debug mode is enabled)
 */
function gflocator_force_update_check() {
    if (!GFLOCATOR_UPDATE_DEBUG || !current_user_can('update_plugins')) {
        return;
    }
    
    // Clear all update-related transients
    delete_transient('gflocator_latest_version');
    delete_transient('gflocator_latest_version_error');
    delete_transient('gflocator_latest_date');
    delete_transient('gflocator_changelog');
    
    // Force WordPress to check for updates
    delete_site_transient('update_plugins');
    
    error_log('Form Locator: Forced update check triggered - all caches cleared');
    
    // Redirect back to plugins page
    wp_redirect(admin_url('plugins.php?gflocator_debug=cleared'));
    exit;
}

/**
 * Check for plugin updates
 */
function gflocator_check_for_update($transient) {
    if (empty($transient->checked)) {
        if (GFLOCATOR_UPDATE_DEBUG) {
            error_log('Form Locator Update Check: transient->checked is empty');
        }
        return $transient;
    }
    
    // Skip if we recently had an API error
    if (get_transient('gflocator_latest_version_error')) {
        if (GFLOCATOR_UPDATE_DEBUG) {
            error_log('Form Locator Update Check: Skipping due to recent API error');
        }
        return $transient;
    }
    
    // Get current version
    $current_version = GFLOCATOR_VERSION;
    
    // Get latest version from GitHub
    $latest_version = gflocator_get_latest_version();
    
    if (GFLOCATOR_UPDATE_DEBUG) {
        error_log('Form Locator Update Check: Current=' . $current_version . ', Latest=' . ($latest_version ?: 'false'));
    }
    
    if ($latest_version && version_compare($latest_version, $current_version, '>')) {
        $plugin_slug = plugin_basename(__FILE__);
        
        if (GFLOCATOR_UPDATE_DEBUG) {
            error_log('Form Locator Update Check: Update available! Adding to transient for ' . $plugin_slug);
        }
        
        $transient->response[$plugin_slug] = (object) array(
            'slug' => 'form-locator-for-gravity-forms',
            'new_version' => $latest_version,
            'url' => 'https://github.com/' . GFLOCATOR_GITHUB_REPO,
            'package' => 'https://github.com/' . GFLOCATOR_GITHUB_REPO . '/archive/refs/tags/v' . $latest_version . '.zip',
            'requires' => '6.7',
            'requires_php' => '8.0.0',
            'tested' => '6.7.2',
            'last_updated' => gflocator_get_latest_release_date(),
            'sections' => array(
                'description' => 'Comprehensive Gravity Forms detection across WordPress pages, posts, and page builders.',
                'changelog' => gflocator_get_changelog()
            ),
            'upgrade_notice' => 'This update includes important security and feature improvements.'
        );
    } else {
        if (GFLOCATOR_UPDATE_DEBUG) {
            error_log('Form Locator Update Check: No update needed or API failed');
        }
    }
    
    return $transient;
}

/**
 * Get plugin information for update screen
 */
function gflocator_plugin_info($result, $action, $args) {
    if ($action !== 'plugin_information') {
        return $result;
    }
    
    if (!isset($args->slug) || $args->slug !== 'form-locator-for-gravity-forms') {
        return $result;
    }
    
    $plugin_info = (object) array(
        'name' => 'Form Locator for Gravity Forms',
        'slug' => 'form-locator-for-gravity-forms',
        'version' => gflocator_get_latest_version(),
        'author' => 'Chris Eggleston',
        'author_profile' => 'https://gravityranger.com',
        'last_updated' => gflocator_get_latest_release_date(),
        'homepage' => 'https://github.com/' . GFLOCATOR_GITHUB_REPO,
        'sections' => array(
            'description' => 'Comprehensive Gravity Forms detection across WordPress pages, posts, and page builders. Find forms embedded via shortcodes, blocks, widgets, and page builder modules.',
            'installation' => 'Upload the plugin files to the /wp-content/plugins/form-locator-for-gravity-forms directory, or install the plugin through the WordPress admin screen directly. Activate the plugin through the Plugins screen in WordPress.',
            'changelog' => gflocator_get_changelog(),
            'screenshots' => 'Screenshots available at https://github.com/' . GFLOCATOR_GITHUB_REPO
        ),
        'download_link' => 'https://github.com/' . GFLOCATOR_GITHUB_REPO . '/archive/refs/tags/v' . gflocator_get_latest_version() . '.zip'
    );
    
    return $plugin_info;
}

/**
 * Display custom update message
 */
function gflocator_update_message() {
    echo '<br><strong>Note:</strong> This update is from GitHub. Please backup your site before updating.';
}

/**
 * Make a GitHub API request with error handling and retry logic
 */
function gflocator_github_api_request($endpoint) {
    $api_url = 'https://api.github.com/repos/' . GFLOCATOR_GITHUB_REPO . '/' . $endpoint;
    
    $response = wp_remote_get($api_url, array(
        'timeout' => 15,
        'headers' => array(
            'User-Agent' => 'WordPress/' . get_bloginfo('version') . '; ' . get_bloginfo('url'),
            'Accept' => 'application/vnd.github.v3+json'
        )
    ));
    
    if (is_wp_error($response)) {
        if (GFLOCATOR_UPDATE_DEBUG) {
            error_log('Form Locator GitHub API Error: ' . $response->get_error_message());
        }
        return new WP_Error('api_error', $response->get_error_message());
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    if ($response_code !== 200) {
        if (GFLOCATOR_UPDATE_DEBUG) {
            error_log('Form Locator GitHub API HTTP Error: ' . $response_code);
        }
        return new WP_Error('http_error', 'HTTP ' . $response_code);
    }
    
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        if (GFLOCATOR_UPDATE_DEBUG) {
            error_log('Form Locator GitHub API: JSON decode error');
        }
        return new WP_Error('json_error', 'Invalid JSON response');
    }
    
    return $data;
}

/**
 * Get latest version from GitHub
 */
function gflocator_get_latest_version() {
    $cache_key = 'gflocator_latest_version';
    $cached_version = get_transient($cache_key);
    
    if ($cached_version !== false) {
        return $cached_version;
    }
    
    // Skip if we recently had an API error
    if (get_transient('gflocator_latest_version_error')) {
        return false;
    }
    
    $data = gflocator_github_api_request('releases/latest');
    
    if (is_wp_error($data)) {
        // Cache error for 30 minutes to avoid repeated failed requests
        set_transient('gflocator_latest_version_error', true, 30 * MINUTE_IN_SECONDS);
        return false;
    }
    
    if (empty($data) || !isset($data['tag_name'])) {
        if (GFLOCATOR_UPDATE_DEBUG) {
            error_log('Form Locator GitHub API: Invalid response format');
        }
        set_transient('gflocator_latest_version_error', true, 30 * MINUTE_IN_SECONDS);
        return false;
    }
    
    // Remove 'v' prefix if present
    $version = ltrim($data['tag_name'], 'v');
    
    // Validate version format
    if (!preg_match('/^\d+\.\d+\.\d+.*$/', $version)) {
        if (GFLOCATOR_UPDATE_DEBUG) {
            error_log('Form Locator GitHub API: Invalid version format: ' . $version);
        }
        return false;
    }
    
    // Cache for 12 hours
    set_transient($cache_key, $version, 12 * HOUR_IN_SECONDS);
    
    return $version;
}

/**
 * Get latest release date
 */
function gflocator_get_latest_release_date() {
    $cache_key = 'gflocator_latest_date';
    $cached_date = get_transient($cache_key);
    
    if ($cached_date !== false) {
        return $cached_date;
    }
    
    $data = gflocator_github_api_request('releases/latest');
    
    if (is_wp_error($data) || empty($data) || !isset($data['published_at'])) {
        return false;
    }
    
    $date = date('Y-m-d', strtotime($data['published_at']));
    
    // Cache for 12 hours
    set_transient($cache_key, $date, 12 * HOUR_IN_SECONDS);
    
    return $date;
}

/**
 * Get changelog from GitHub
 */
function gflocator_get_changelog() {
    $cache_key = 'gflocator_changelog';
    $cached_changelog = get_transient($cache_key);
    
    if ($cached_changelog !== false) {
        return $cached_changelog;
    }
    
    $data = gflocator_github_api_request('releases/latest');
    
    if (is_wp_error($data) || empty($data) || !isset($data['body'])) {
        return 'Changelog not available.';
    }
    
    $changelog = wp_kses_post($data['body']); // Sanitize HTML content
    
    // Cache for 12 hours
    set_transient($cache_key, $changelog, 12 * HOUR_IN_SECONDS);
    
    return $changelog;
}
?>
