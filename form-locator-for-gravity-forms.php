<?php
/**
 * Form Locator for Gravity Forms
 *
 * @package       FormLocator
 * @author        Chris Eggleston
 * @license       gplv2
 * @version       2.0.0
 *
 * @wordpress-plugin
 * Plugin Name:   Form Locator for Gravity Forms
 * Plugin URI:    https://github.com/chrisegg/form-locator-for-gravity-forms
 * Description:   Lists WordPress pages and posts that contain Gravity Forms block or shortcode, including those deleted, trashed, and inactive.
 * Version:       2.0.0
 * Author:        Chris Eggleston
 * Author URI:    https://gravityranger.com
 * Text Domain:   form-locator-for-gravity-forms
 * Domain Path:   /languages
 * License:       GPLv2
 * License URI:   https://www.gnu.org/licenses/gpl-2.0.html
 * Update URI:    https://github.com/chrisegg/form-locator-for-gravity-forms
 * Requires at least: 6.7
 * Tested up to: 6.7.2
 * Requires PHP: 8.0
 *
 * You should have received a copy of the GNU General Public License
 * along with Form Locator for Gravity Forms. If not, see <https://www.gnu.org/licenses/gpl-2.0.html/>.
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('FORM_LOCATOR_VERSION', '2.0.0');
define('FORM_LOCATOR_PLUGIN_FILE', __FILE__);
define('FORM_LOCATOR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('FORM_LOCATOR_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Check if Gravity Forms is active and load the add-on
 */
add_action('gform_loaded', array('Form_Locator_Bootstrap', 'load'), 5);

class Form_Locator_Bootstrap {

    /**
     * Load the Form Locator add-on if Gravity Forms is present and up to date
     */
    public static function load() {
        
        if (!method_exists('GFForms', 'include_addon_framework')) {
            return;
        }

        // Include the Gravity Forms Add-On Framework
        GFForms::include_addon_framework();

        // Include our add-on class
        require_once plugin_dir_path(__FILE__) . 'includes/class-form-locator-addon.php';

        // Register the add-on
        GFAddOn::register('Form_Locator_AddOn');
    }
}

/**
 * Display admin notice if Gravity Forms is not active or up to date
 */
function form_locator_admin_notice() {
    $message = esc_html__('Form Locator for Gravity Forms requires Gravity Forms 2.4 or greater to be installed and active.', 'form-locator-for-gravity-forms');
    echo '<div class="notice notice-error"><p>' . $message . '</p></div>';
}

/**
 * Check if Gravity Forms is active, if not show admin notice
 */
function form_locator_check_gf_dependency() {
    if (!class_exists('GFForms')) {
        add_action('admin_notices', 'form_locator_admin_notice');
        return false;
    }
    
    if (!version_compare(GFForms::$version, '2.4', '>=')) {
        add_action('admin_notices', 'form_locator_admin_notice');
        return false;
    }
    
    return true;
}

// Check dependency on plugins_loaded
add_action('plugins_loaded', 'form_locator_check_gf_dependency');

