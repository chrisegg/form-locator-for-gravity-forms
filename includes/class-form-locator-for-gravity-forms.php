
<?php
class Form_Locator_For_Gravity_Forms {

    public function __construct() {
        add_action('admin_menu', [$this, 'add_gf_pages_menu']);
    }

    public function add_gf_pages_menu() {
        add_menu_page(
            'Gravity Forms Pages',
            'GF Pages',
            'manage_options',
            'gf-pages-list',
            [$this, 'list_gravity_forms_pages'],
            'dashicons-list-view',
            100
        );
    }

    public function list_gravity_forms_pages() {
        global $wpdb;

        $results = $wpdb->get_results("SELECT ID, post_title, post_type, post_content FROM {$wpdb->posts} WHERE post_status = 'publish'", ARRAY_A);
        $total_posts_scanned = count($results);
        $gf_pages = [];

        foreach ($results as $post) {
            $form_ids = $this->get_gravity_form_ids($post['post_content']);
            $block_form_ids = $this->get_gravity_block_form_ids($post['post_content']);
            $has_login_form = $this->has_gravity_login_form($post['post_content']);

            if (!empty($form_ids) || !empty($block_form_ids) || $has_login_form) {
                $gf_pages[] = [
                    'ID' => $post['ID'],
                    'Type' => $post['post_type'],
                    'Title' => $post['post_title'],
                    'Form IDs' => $form_ids,
                    'Block Form IDs' => $block_form_ids,
                    'Has Login Form' => $has_login_form
                ];
            }
        }

        include plugin_dir_path(__FILE__) . '../views/admin-page.php';
    }

    private function get_gravity_form_ids($content) {
        preg_match_all('/\[gravityform[^]]*id=["\']?(\d+)["\']?/i', $content, $matches);
        return !empty($matches[1]) ? array_map('intval', $matches[1]) : [];
    }

    private function get_gravity_block_form_ids($content) {
        preg_match_all('/"formId"\s*:\s*"?(\d+)"?/', $content, $matches);
        return !empty($matches[1]) ? array_map('intval', $matches[1]) : [];
    }

    private function has_gravity_login_form($content) {
        return preg_match('/\[gravityform[^]]*action=["\']?login["\']?/i', $content);
    }

    public function check_gravity_form_status($form_id) {
        global $wpdb;

        if (!class_exists('GFAPI')) {
            return 'unknown';
        }

        $trash_check = $wpdb->get_var($wpdb->prepare("SELECT is_trash FROM {$wpdb->prefix}gf_form WHERE id = %d", $form_id));
        if ($trash_check == 1) {
            return 'trash';
        }

        $form = GFAPI::get_form($form_id);
        if (!$form) {
            return 'deleted';
        }

        return rgar($form, 'is_active') ? 'active' : 'inactive';
    }

    public function display_form_status_message($form_id, $form_status) {
        $status_messages = [
            'inactive' => " <span style='color: orange;'>(Inactive)</span>",
            'trash' => " <span style='color: brown;'>(Trashed)</span>",
            'deleted' => " <span style='color: red;'>(Deleted)</span>"
        ];
        echo $status_messages[$form_status] ?? '';
    }
}
?>
