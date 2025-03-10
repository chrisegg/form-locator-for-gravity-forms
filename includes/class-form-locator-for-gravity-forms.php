<?php
// Prevent direct file access
if (!defined('ABSPATH')) {
    exit;
}

<?php
// Prevent direct file access
if (!defined('ABSPATH')) {
    exit;
}

class Form_Locator_For_Gravity_Forms {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_gf_pages_menu']);
    }

    // Register the admin menu for the plugin
    public function add_gf_pages_menu() {
        add_menu_page(
            'Gravity Forms Pages', // Page title
            'GF Pages', // Menu title
            'manage_options', // Capability required to access
            'gf-pages-list', // Menu slug
            [$this, 'list_gravity_forms_pages'], // Callback function to render page
            'dashicons-list-view', // Icon displayed in menu
            100 // Position in menu
        );
    }

    // List pages containing Gravity Forms shortcodes or blocks
    public function list_gravity_forms_pages() {
        global $wpdb;

        try {
            // Perform the scan only when the menu item is clicked
            if (!isset($_GET['gf_scan']) || $_GET['gf_scan'] !== '1') {
                echo '<div class="wrap"><h1>Gravity Forms Pages</h1>';
                echo '<p><a href="' . esc_url(admin_url('admin.php?page=gf-pages-list&gf_scan=1')) . '" class="button button-primary">Scan for Gravity Forms</a></p>';
                echo '</div>';
                return;
            }

            // Retrieve all published posts
            $results = $wpdb->get_results($wpdb->prepare(
                "SELECT ID, post_title, post_type, post_content FROM {$wpdb->posts} WHERE post_status = %s", 'publish'
            ), ARRAY_A);
            if ($wpdb->last_error) {
                throw new Exception('Database error: ' . $wpdb->last_error);
            }
            $total_posts_scanned = count($results);
            $gf_pages = [];

            // Scan for Gravity Forms usage in content
            foreach ($results as $post) {
                $form_ids = $this->get_gravity_form_ids($post['post_content']);
                $block_form_ids = $this->get_gravity_block_form_ids($post['post_content']);
                $has_login_form = $this->has_gravity_login_form($post['post_content']);

                if (!empty($form_ids) || !empty($block_form_ids) || $has_login_form) {
                    $gf_pages[] = [
                        'ID' => $post['ID'],
                        'Type' => esc_html($post['post_type']), // Secure output
                        'Title' => esc_html($post['post_title']), // Secure output
                        'Form IDs' => array_map('intval', $form_ids),
                        'Block Form IDs' => array_map('intval', $block_form_ids),
                        'Has Login Form' => $has_login_form // Keep boolean logic, escape during output
                    ];
                }
            }

            include plugin_dir_path(__FILE__) . '../views/admin-page.php';
        } catch (Exception $e) {
            $this->log_error($e->getMessage());
            echo '<div class="error"><p>There was an error processing your request. Please try again later.</p></div>';
        }
    }

    // Extract form IDs from Gravity Forms shortcodes securely
    private function get_gravity_form_ids($content) {
        preg_match_all('/\\[gravityform[^\\]]*id=[\"\\']?(\\d+)[\"\\']?/i', $content, $matches);
        return !empty($matches[1]) ? array_map('intval', $matches[1]) : [];
    }

    // Extract form IDs from Gravity Forms blocks securely
    private function get_gravity_block_form_ids($content) {
        preg_match_all('/\"formId\"\\s*:\\s*\"?(\\d+)\"?/', $content, $matches);
        return !empty($matches[1]) ? array_map('intval', $matches[1]) : [];
    }

    // Check if the content has a Gravity Forms login form securely
    private function has_gravity_login_form($content) {
        return (bool) preg_match('/\\[gravityform[^\\]]*action=[\"\\']login[\"\\']/', $content);
    }

    // Check the status of a Gravity Form securely
    public function check_gravity_form_status($form_id) {
        global $wpdb;

        if (!class_exists('GFAPI')) {
            return 'unknown';
        }

        // Sanitize form ID before database query
        $form_id = intval($form_id);
        $trash_check = $wpdb->get_var($wpdb->prepare(
            "SELECT is_trash FROM {$wpdb->prefix}gf_form WHERE id = %d", $form_id
        ));

        if ($trash_check == 1) {
            return 'trash';
        }

        $form = GFAPI::get_form($form_id);
        if (!$form) {
            return 'deleted';
        }

        return rgar($form, 'is_active') ? 'active' : 'inactive';
    }

    // Display a status message based on the form's status securely
    public function display_form_status_message($form_id, $form_status) {
        $status_messages = [
            'inactive' => " <span style='color: orange;'>(Inactive)</span>",
            'trash' => " <span style='color: brown;'>(Trashed)</span>",
            'deleted' => " <span style='color: red;'>(Deleted)</span>"
        ];
        echo isset($status_messages[$form_status]) ? $status_messages[$form_status] : '';
    }

    // Log error messages to a file
    private function log_error($message) {
        error_log($message, 3, plugin_dir_path(__FILE__) . 'error.log');
    }
}
?>
