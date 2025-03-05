<?php
/**
 * Form Locator for Gravity Forms
 *
 * @package       GFLOCATOR
 * @author        Chris Eggleston
 * @license       gplv2
 * @version       1.0.0
 *
 * @wordpress-plugin
 * Plugin Name:   Form Locator for Gravity Forms
 * Plugin URI:    https://gravityranger.com/plugins
 * Description:   Lists WordPress pages and posts that contain Gravity Forms block or shortcode, including those deleted, trashed, and inactive.
 * Version:       1.0.0
 * Author:        Chris Eggleston
 * Author URI:    https://gravityranger.com
 * Text Domain:   form-locator-for-gravity-forms
 * Domain Path:   /languages
 * License:       GPLv2
 * License URI:   https://www.gnu.org/licenses/gpl-2.0.html
 *
 * You should have received a copy of the GNU General Public License
 * along with Form Locator for Gravity Forms. If not, see <https://www.gnu.org/licenses/gpl-2.0.html/>.
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Main Plugin File
require_once plugin_dir_path(__FILE__) . 'includes/class-form-locator-for-gravity-forms.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-form-locator-for-gravity-forms-run.php';

// Initialize the plugin
Form_Locator_For_Gravity_Forms_Run::init();
?>
