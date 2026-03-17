<?php
// Prevent direct file access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Form Locator Add-On for Gravity Forms
 * 
 * Extends the GFAddOn class to provide form location functionality
 * within the official Gravity Forms add-on framework.
 */
class Form_Locator_AddOn extends GFAddOn {

    /**
     * Add-on version
     */
    protected $_version = '2.0.0';

    /**
     * Minimum Gravity Forms version required
     */
    protected $_min_gravityforms_version = '2.4';

    /**
     * Add-on slug
     */
    protected $_slug = 'form-locator-for-gravity-forms';

    /**
     * Add-on path
     */
    protected $_path = 'form-locator-for-gravity-forms/form-locator-for-gravity-forms.php';

    /**
     * Full path to the main plugin file
     */
    protected $_full_path = __FILE__;

    /**
     * Add-on title (used for page header - keep short to avoid duplicate with our styled title)
     */
    protected $_title = 'Form Locator';

    /**
     * Add-on short title
     */
    protected $_short_title = 'Form Locator';

    /**
     * Capabilities required to access the add-on
     */
    protected $_capabilities = array('manage_options');

    /**
     * Capabilities for specific functionality
     */
    protected $_capabilities_settings_page = 'manage_options';
    protected $_capabilities_form_settings = 'manage_options';
    protected $_capabilities_uninstall = 'manage_options';

    /**
     * Enable background feed processing
     */
    protected $_enable_rg_autoupgrade = true;

    /**
     * Singleton instance
     */
    private static $_instance = null;

    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (self::$_instance == null) {
            self::$_instance = new Form_Locator_AddOn();
        }
        return self::$_instance;
    }

    /**
     * Initialize the add-on
     */
    public function init() {
        parent::init();

        // Add any additional initialization here
        add_action('admin_init', array($this, 'admin_init'));

        // Move Form Locator to bottom of Forms submenu (run as late as possible)
        add_action('admin_menu', array($this, 'move_form_locator_menu_to_bottom'), PHP_INT_MAX);
    }

    /**
     * Move Form Locator menu item to the bottom of the Forms submenu.
     * Uses admin_menu at PHP_INT_MAX so we run after Gravity Forms builds its menu.
     */
    public function move_form_locator_menu_to_bottom() {
        global $submenu;

        if (empty($submenu) || !is_array($submenu)) {
            return;
        }

        $our_slug = $this->_slug;
        $our_title = $this->get_short_title();

        foreach ($submenu as $parent => $items) {
            if (!is_array($items)) {
                continue;
            }

            $our_index = null;
            $our_item = null;

            foreach ($items as $index => $item) {
                $slug = isset($item[2]) ? $item[2] : '';
                $title = isset($item[0]) ? wp_strip_all_tags($item[0]) : '';
                $is_ours = ($slug === $our_slug || $slug === 'gf_form_locator' || $title === $our_title);
                if ($is_ours) {
                    $our_index = $index;
                    $our_item = $item;
                    break;
                }
            }

            if ($our_index !== null && $our_item !== null) {
                unset($submenu[$parent][$our_index]);
                $submenu[$parent][] = $our_item;
                return;
            }
        }
    }

    /**
     * Admin initialization
     */
    public function admin_init() {
        // Additional admin initialization if needed
    }

    /**
     * Get menu icon for the add-on
     */
    public function get_menu_icon() {
        return 'gform-icon--search';
    }

    /**
     * Check if current admin page is the Form Locator plugin page
     *
     * @return bool
     */
    public function is_form_locator_plugin_page() {
        $page = function_exists('rgget') ? rgget('page') : (isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : '');
        return in_array($page, array($this->_slug, 'gf_form_locator'), true);
    }

    /**
     * Enqueue scripts for the Form Locator plugin page
     */
    public function scripts() {
        $base_url = $this->get_base_url();
        $scripts = parent::scripts();
        $scripts[] = array(
            'handle'  => 'form-locator-chartjs',
            'src'     => $base_url . '/assets/js/chart.min.js',
            'version' => $this->_version,
            'deps'    => array(),
            'in_footer' => false,
            'enqueue' => array(
                array(
                    'admin_page' => array( 'plugin_page' ),
                    'callback'   => array( $this, 'is_form_locator_plugin_page' ),
                ),
            ),
        );
        $scripts[] = array(
            'handle'  => 'form-locator-chartjs-datalabels',
            'src'     => $base_url . '/assets/js/chartjs-plugin-datalabels.min.js',
            'version' => $this->_version,
            'deps'    => array( 'form-locator-chartjs' ),
            'in_footer' => false,
            'enqueue' => array(
                array(
                    'admin_page' => array( 'plugin_page' ),
                    'callback'   => array( $this, 'is_form_locator_plugin_page' ),
                ),
            ),
        );
        return $scripts;
    }

    /**
     * Main plugin page content
     */
    public function plugin_page() {
        global $wpdb;

        try {
            // Perform the scan and prepare data for output
            $form_pages = [];
            $total_posts_scanned = 0;

            // Get post types and statuses based on settings
            $scan_config = $this->get_scan_config();
            $post_types = $scan_config['post_types'];
            $post_statuses = $scan_config['post_statuses'];

            if (empty($post_types) || empty($post_statuses)) {
                $results = array();
            } else {
                $status_placeholders = implode(',', array_fill(0, count($post_statuses), '%s'));
                $type_placeholders = implode(',', array_fill(0, count($post_types), '%s'));
                $params = array_merge($post_statuses, $post_types);
                $results = $wpdb->get_results($wpdb->prepare(
                    "SELECT ID, post_title, post_type, post_content 
                    FROM {$wpdb->posts} 
                    WHERE post_status IN ($status_placeholders) 
                    AND post_type IN ($type_placeholders) 
                    ORDER BY post_type, post_title",
                    $params
                ), ARRAY_A);
            }

            if ($wpdb->last_error) {
                $this->log_error('Database error: ' . $wpdb->last_error);
                throw new Exception(esc_html__('There was an error processing your request. Please try again later.', 'form-locator-for-gravity-forms'));
            }

            $total_posts_scanned = count($results);

            // Scan for Gravity Forms usage in content
            foreach ($results as $post) {
                $form_ids = $this->get_gravity_form_ids($post['post_content']);
                $block_form_ids = $this->get_gravity_block_form_ids($post['post_content']);
                $page_builder_form_ids = $this->get_page_builder_form_ids($post['ID'], $post['post_content']);
                $has_login_form = $this->has_gravity_login_form($post['post_content']);

                if (!empty($form_ids) || !empty($block_form_ids) || !empty($page_builder_form_ids) || $has_login_form) {
                    $form_pages[] = [
                        'ID' => $post['ID'],
                        'Type' => esc_html($post['post_type']),
                        'Title' => esc_html($post['post_title']),
                        'Form IDs' => array_map('intval', $form_ids),
                        'Block Form IDs' => array_map('intval', $block_form_ids),
                        'Page Builder Form IDs' => array_map('intval', $page_builder_form_ids),
                        'Has Login Form' => $has_login_form,
                    ];
                }
            }

            // Get chart data with error handling
            $embedded_form_ids = $this->get_all_embedded_form_ids($form_pages);
            $monthly_stats = $this->get_embedded_form_entries_by_month($embedded_form_ids, 12);
            $form_stats = $this->get_entry_stats_by_form();
            
            // Additional stats with fallbacks
            $total_entries = $this->gf_tables_exist() ? $this->get_total_entries() : 0;
            $active_forms = $this->gf_tables_exist() ? $this->get_active_forms_count() : 0;
            $inactive_forms = $this->gf_tables_exist() ? $this->get_inactive_forms_count() : 0;
            $recent_entries = $this->gf_tables_exist() ? $this->get_recent_entries_count(30) : 0;
            
            // Check for chart data errors
            $chart_errors = array();
            if (isset($monthly_stats['error'])) {
                $chart_errors['monthly'] = $monthly_stats['error'];
            }
            if (isset($form_stats['error'])) {
                $chart_errors['forms'] = $form_stats['error'];
            }

            // Pass data to the view file
            $gf_pages = $form_pages; // For backward compatibility with view
            include FORM_LOCATOR_PLUGIN_DIR . 'views/admin-page.php';

        } catch (Exception $e) {
            $this->log_error($e->getMessage());
            echo '<div class="notice notice-error"><p>' . esc_html__('There was an error processing your request. Please try again later.', 'form-locator-for-gravity-forms') . '</p></div>';
        }
    }

    /**
     * Get scan configuration (post types and statuses) based on plugin settings.
     *
     * @return array Keys: 'post_types', 'post_statuses'
     */
    private function get_scan_config() {
        return array(
            'post_types'   => $this->get_scan_post_types(),
            'post_statuses' => $this->get_scan_post_statuses(),
        );
    }

    /**
     * Get post types to scan: post, page, and all public custom post types.
     *
     * @return array List of post type slugs.
     */
    private function get_scan_post_types() {
        $types = get_post_types(array('public' => true), 'names');
        return array_values(array_diff($types, array('attachment', 'revision')));
    }

    /**
     * Get post statuses to scan based on plugin setting.
     * When "scan_all_post_types" is enabled, includes draft, private, etc.
     *
     * @return array List of post status slugs.
     */
    private function get_scan_post_statuses() {
        $include_all = $this->get_plugin_setting('scan_all_post_types');
        if (!empty($include_all)) {
            return array('publish', 'draft', 'private', 'pending', 'future', 'trash');
        }
        return array('publish');
    }

    /**
     * Extract form IDs from Gravity Forms shortcodes securely
     */
    private function get_gravity_form_ids($content) {
        preg_match_all('/\\[gravityform[^\\]]*id=[\"\\\']?(\\d+)[\"\\\']?/i', $content, $matches);
        return !empty($matches[1]) ? array_map('intval', $matches[1]) : [];
    }

    /**
     * Extract form IDs from Gravity Forms blocks securely
     */
    private function get_gravity_block_form_ids($content) {
        preg_match_all('/"formId"\s*:\s*"?(\d+)"?/', $content, $matches);
        return !empty($matches[1]) ? array_map('intval', $matches[1]) : [];
    }

    /**
     * Extract form IDs from page builder widgets and add-ons
     */
    private function get_page_builder_form_ids($post_id, $content) {
        $form_ids = [];
        
        // Check Elementor data
        $elementor_form_ids = $this->get_elementor_form_ids($post_id);
        if (!empty($elementor_form_ids)) {
            $form_ids = array_merge($form_ids, $elementor_form_ids);
        }
        
        // Check Beaver Builder data
        $beaver_form_ids = $this->get_beaver_builder_form_ids($post_id, $content);
        if (!empty($beaver_form_ids)) {
            $form_ids = array_merge($form_ids, $beaver_form_ids);
        }
        
        // Check Avada Fusion Builder data
        $fusion_form_ids = $this->get_fusion_builder_form_ids($post_id, $content);
        if (!empty($fusion_form_ids)) {
            $form_ids = array_merge($form_ids, $fusion_form_ids);
        }
        
        // Check Divi Builder data
        $divi_form_ids = $this->get_divi_form_ids($content);
        if (!empty($divi_form_ids)) {
            $form_ids = array_merge($form_ids, $divi_form_ids);
        }
        
        // Check WPBakery data
        $wpbakery_form_ids = $this->get_wpbakery_form_ids($content);
        if (!empty($wpbakery_form_ids)) {
            $form_ids = array_merge($form_ids, $wpbakery_form_ids);
        }
        
        return array_unique($form_ids);
    }

    /**
     * Get form IDs from Elementor data
     */
    private function get_elementor_form_ids($post_id) {
        $form_ids = [];
        $elementor_data = get_post_meta($post_id, '_elementor_data', true);
        
        if (empty($elementor_data)) {
            return $form_ids;
        }
        
        $data = json_decode($elementor_data, true);
        if (!is_array($data)) {
            return $form_ids;
        }
        
        $form_ids = $this->search_elementor_widgets($data);
        return array_unique($form_ids);
    }

    /**
     * Recursively search Elementor widgets for Gravity Forms
     */
    private function search_elementor_widgets($data) {
        $form_ids = [];
        
        foreach ($data as $element) {
            if (!is_array($element)) {
                continue;
            }
            
            // Check for Gravity Forms widget
            if (isset($element['widgetType']) && $element['widgetType'] === 'gravity-forms') {
                if (isset($element['settings']['form_id'])) {
                    $form_ids[] = intval($element['settings']['form_id']);
                }
            }
            
            // Check for GravityKits widget
            if (isset($element['widgetType']) && strpos($element['widgetType'], 'gravitykits') !== false) {
                if (isset($element['settings']['form_id'])) {
                    $form_ids[] = intval($element['settings']['form_id']);
                }
            }
            
            // Check for custom Gravity Forms widgets
            if (isset($element['widgetType']) && strpos($element['widgetType'], 'gravity') !== false) {
                if (isset($element['settings']['gravity_form_id'])) {
                    $form_ids[] = intval($element['settings']['gravity_form_id']);
                }
            }
            
            // Recursively check elements and columns
            if (isset($element['elements']) && is_array($element['elements'])) {
                $form_ids = array_merge($form_ids, $this->search_elementor_widgets($element['elements']));
            }
        }
        
        return $form_ids;
    }

    /**
     * Get form IDs from Beaver Builder data
     */
    private function get_beaver_builder_form_ids($post_id, $content) {
        $form_ids = [];
        $bb_data = get_post_meta($post_id, '_fl_builder_data', true);
        
        if (empty($bb_data)) {
            return $form_ids;
        }
        
        // Handle both object and array formats
        if (is_object($bb_data)) {
            foreach ($bb_data as $node) {
                if (isset($node->type) && $node->type === 'module') {
                    if (isset($node->settings) && is_object($node->settings)) {
                        // Check for direct form ID
                        if (isset($node->settings->gravity_form_id)) {
                            $form_ids[] = intval($node->settings->gravity_form_id);
                        }
                        
                        // Check for widget-based form ID
                        if (isset($node->settings->{'widget-gform_widget'}->form_id)) {
                            $form_ids[] = intval($node->settings->{'widget-gform_widget'}->form_id);
                        }
                    }
                }
            }
        } elseif (is_array($bb_data)) {
            foreach ($bb_data as $node) {
                if (isset($node['type']) && $node['type'] === 'module') {
                    if (isset($node['settings']) && is_array($node['settings'])) {
                        // Check for direct form ID
                        if (isset($node['settings']['gravity_form_id'])) {
                            $form_ids[] = intval($node['settings']['gravity_form_id']);
                        }
                        
                        // Check for widget-based form ID
                        if (isset($node['settings']['widget-gform_widget']['form_id'])) {
                            $form_ids[] = intval($node['settings']['widget-gform_widget']['form_id']);
                        }
                    }
                }
            }
        }
        
        return array_unique($form_ids);
    }

    /**
     * Get form IDs from Divi Builder content
     */
    private function get_divi_form_ids($content) {
        // Check for Divi Gravity Forms module shortcodes
        preg_match_all('/\[et_pb_gravityform[^\]]*form_id=[\"\']?(\d+)[\"\']?/i', $content, $matches);
        return !empty($matches[1]) ? array_map('intval', $matches[1]) : [];
    }

    /**
     * Get form IDs from WPBakery content
     */
    private function get_wpbakery_form_ids($content) {
        // Check for WPBakery Gravity Forms shortcodes
        preg_match_all('/\[vc_gravityform[^\]]*id=[\"\']?(\d+)[\"\']?/i', $content, $matches);
        $form_ids = !empty($matches[1]) ? array_map('intval', $matches[1]) : [];
        
        // Check for additional patterns
        // Check for GravityKits patterns in JSON/object contexts
        preg_match_all('/gravitykits[^}]*form_id[^}]*:[\s]*["\']?(\d+)["\']?/i', $content, $matches);
        if (!empty($matches[1])) {
            $form_ids = array_merge($form_ids, array_map('intval', $matches[1]));
        }
        
        // Check for generic gravity widget patterns
        preg_match_all('/"widgetType"[\s]*:[\s]*"gravity[^"]*"[^}]*"form_id"[\s]*:[\s]*["\']?(\d+)["\']?/i', $content, $matches);
        if (!empty($matches[1])) {
            $form_ids = array_merge($form_ids, array_map('intval', $matches[1]));
        }
        
        return array_unique($form_ids);
    }

    /**
     * Get form IDs from Avada Fusion Builder
     */
    private function get_fusion_builder_form_ids($post_id, $content) {
        $form_ids = [];
        
        // Check for Fusion shortcodes in content
        preg_match_all('/\[fusion_gravityform[^\]]*form_id=[\"\']?(\d+)[\"\']?/i', $content, $matches);
        if (!empty($matches[1])) {
            $form_ids = array_merge($form_ids, array_map('intval', $matches[1]));
        }
        
        // Check for alternative Fusion shortcode format
        preg_match_all('/\[fusion_gravity_forms[^\]]*id=[\"\']?(\d+)[\"\']?/i', $content, $matches);
        if (!empty($matches[1])) {
            $form_ids = array_merge($form_ids, array_map('intval', $matches[1]));
        }
        
        // Check Fusion Builder meta data
        $fusion_data = get_post_meta($post_id, '_fusion_builder_content', true);
        if (!empty($fusion_data)) {
            $data = json_decode($fusion_data, true);
            if (is_array($data)) {
                $form_ids = array_merge($form_ids, $this->search_fusion_elements($data));
            }
        }
        
        return array_unique($form_ids);
    }

    /**
     * Recursively search Fusion Builder elements for Gravity Forms
     */
    private function search_fusion_elements($data) {
        $form_ids = [];
        
        foreach ($data as $element) {
            if (!is_array($element)) {
                continue;
            }
            
            // Check for Gravity Forms element
            if (isset($element['type']) && $element['type'] === 'fusion_gravityform') {
                if (isset($element['params']['form_id'])) {
                    $form_ids[] = intval($element['params']['form_id']);
                }
            }
            
            // Check for Gravity Forms element with different parameter name
            if (isset($element['type']) && $element['type'] === 'fusion_gravity_forms') {
                if (isset($element['params']['id'])) {
                    $form_ids[] = intval($element['params']['id']);
                }
            }
            
            // Recursively check child elements
            if (isset($element['children']) && is_array($element['children'])) {
                $form_ids = array_merge($form_ids, $this->search_fusion_elements($element['children']));
            }
        }
        
        return $form_ids;
    }

    /**
     * Check if the content has a Gravity Forms login form securely
     */
    private function has_gravity_login_form($content) {
        return (bool) preg_match('/\[gravityform[^]]*action=["\']login["\']/', $content);
    }

    /**
     * Check the status of a Gravity Form securely
     */
    public function check_gravity_form_status($form_id) {
        global $wpdb;

        if (!class_exists('GFAPI')) {
            return 'Unknown';
        }

        // Check if form is in trash
        $is_trash = $wpdb->get_var($wpdb->prepare(
            "SELECT is_trash FROM {$wpdb->prefix}gf_form WHERE id = %d", $form_id
        ));

        if ($is_trash === '1') {
            return 'Trashed';
        }

        $form = GFAPI::get_form($form_id);

        if (!$form) {
            return 'Deleted';
        }

        return isset($form['is_active']) && $form['is_active'] ? 'Active' : 'Inactive';
    }

    /**
     * Display form status message with appropriate styling
     */
    public function display_form_status_message($form_id, $status) {
        $class = '';
        switch ($status) {
            case 'Active':
                $class = 'status-active';
                break;
            case 'Inactive':
                $class = 'status-inactive';
                break;
            case 'Trashed':
                $class = 'status-trashed';
                break;
            case 'Deleted':
                $class = 'status-deleted';
                break;
            default:
                $class = 'status-unknown';
        }
        
        return '<span class="form-status ' . esc_attr($class) . '">' . esc_html($status) . '</span>';
    }

    /**
     * Get all embedded form IDs from scanned pages
     */
    private function get_all_embedded_form_ids($form_pages) {
        $embedded_form_ids = array();
        
        foreach ($form_pages as $page) {
            // Collect all form IDs from shortcodes, blocks, and page builders
            if (!empty($page['Form IDs'])) {
                $embedded_form_ids = array_merge($embedded_form_ids, $page['Form IDs']);
            }
            if (!empty($page['Block Form IDs'])) {
                $embedded_form_ids = array_merge($embedded_form_ids, $page['Block Form IDs']);
            }
            if (!empty($page['Page Builder Form IDs'])) {
                $embedded_form_ids = array_merge($embedded_form_ids, $page['Page Builder Form IDs']);
            }
        }
        
        return array_unique($embedded_form_ids);
    }

    /**
     * Get entry statistics by month for embedded forms only
     */
    private function get_embedded_form_entries_by_month($embedded_form_ids, $months = 12) {
        global $wpdb;
        
        // Check if Gravity Forms tables exist
        if (!$this->gf_tables_exist()) {
            return array(
                'labels' => array(),
                'data' => array(),
                'datasets' => array(),
                'error' => 'Gravity Forms tables not found'
            );
        }
        
        // If no embedded forms found, return empty data
        if (empty($embedded_form_ids)) {
            $labels = array();
            for ($i = $months - 1; $i >= 0; $i--) {
                $labels[] = date('M Y', strtotime("-{$i} months"));
            }
            
            return array(
                'labels' => $labels,
                'data' => array_fill(0, $months, 0),
                'datasets' => array()
            );
        }
        
        try {
            // Create placeholders for the IN clause
            $placeholders = implode(',', array_fill(0, count($embedded_form_ids), '%d'));
            
            // Prepare parameters: months first, then form IDs
            $params = array_merge(array($months), $embedded_form_ids);
            
            $results = $wpdb->get_results($wpdb->prepare("
                SELECT 
                    DATE_FORMAT(e.date_created, '%%Y-%%m') as month,
                    f.title as form_name,
                    f.id as form_id,
                    COUNT(*) as entry_count
                FROM {$wpdb->prefix}gf_entry e
                INNER JOIN {$wpdb->prefix}gf_form f ON e.form_id = f.id
                WHERE e.date_created >= DATE_SUB(NOW(), INTERVAL %d MONTH)
                AND e.status = 'active'
                AND e.form_id IN ($placeholders)
                GROUP BY DATE_FORMAT(e.date_created, '%%Y-%%m'), e.form_id, f.title
                ORDER BY month ASC, form_name ASC
            ", $params));
            
            if ($wpdb->last_error) {
                $this->log_error('Database error in get_embedded_form_entries_by_month: ' . $wpdb->last_error);
                return array(
                    'labels' => array(),
                    'data' => array(),
                    'datasets' => array(),
                    'error' => 'Database query failed'
                );
            }
            
            // Initialize month labels and data structure
            $labels = array();
            $month_keys = array();
            for ($i = $months - 1; $i >= 0; $i--) {
                $month_key = date('Y-m', strtotime("-{$i} months"));
                $month_keys[] = $month_key;
                $labels[] = date('M Y', strtotime("-{$i} months"));
            }
            
            // Group data by form and create datasets
            $form_data = array();
            $form_colors = array(
                '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6',
                '#06b6d4', '#84cc16', '#f97316', '#ec4899', '#6366f1'
            );
            
            foreach ($results as $row) {
                $form_id = $row->form_id;
                $form_name = $row->form_name;
                
                if (!isset($form_data[$form_id])) {
                    $form_data[$form_id] = array(
                        'name' => $form_name,
                        'data' => array_fill(0, $months, 0)
                    );
                }
                
                // Find the month index and set the data
                $month_index = array_search($row->month, $month_keys);
                if ($month_index !== false) {
                    $form_data[$form_id]['data'][$month_index] = intval($row->entry_count);
                }
            }
            
            // Create datasets for Chart.js
            $datasets = array();
            $color_index = 0;
            
            foreach ($form_data as $form_id => $data) {
                $color = $form_colors[$color_index % count($form_colors)];
                
                $datasets[] = array(
                    'label' => $data['name'],
                    'data' => $data['data'],
                    'borderColor' => $color,
                    'backgroundColor' => $color . '20', // Add transparency
                    'borderWidth' => 2,
                    'fill' => false,
                    'tension' => 0.4,
                    'pointBackgroundColor' => $color,
                    'pointBorderColor' => '#fff',
                    'pointBorderWidth' => 2,
                    'pointRadius' => 4,
                    'pointHoverRadius' => 6
                );
                
                $color_index++;
            }
            
            // Calculate total entries per month for backward compatibility
            $total_data = array_fill(0, $months, 0);
            foreach ($form_data as $data) {
                for ($i = 0; $i < $months; $i++) {
                    $total_data[$i] += $data['data'][$i];
                }
            }
            
            return array(
                'labels' => $labels,
                'data' => $total_data, // For backward compatibility
                'datasets' => $datasets
            );
            
        } catch (Exception $e) {
            $this->log_error('Exception in get_embedded_form_entries_by_month: ' . $e->getMessage());
            return array(
                'labels' => array(),
                'data' => array(),
                'datasets' => array(),
                'error' => 'Failed to retrieve embedded form entry statistics'
            );
        }
    }

    /**
     * Get entry statistics by month for line chart (legacy method)
     */
    private function get_entry_stats_by_month($months = 12) {
        global $wpdb;
        
        // Check if Gravity Forms tables exist
        if (!$this->gf_tables_exist()) {
            return array(
                'labels' => array(),
                'data' => array(),
                'error' => 'Gravity Forms tables not found'
            );
        }
        
        try {
            $results = $wpdb->get_results($wpdb->prepare("
                SELECT 
                    DATE_FORMAT(date_created, '%%Y-%%m') as month,
                    COUNT(*) as entry_count
                FROM {$wpdb->prefix}gf_entry 
                WHERE date_created >= DATE_SUB(NOW(), INTERVAL %d MONTH)
                AND status = 'active'
                GROUP BY DATE_FORMAT(date_created, '%%Y-%%m')
                ORDER BY month ASC
            ", $months));
            
            if ($wpdb->last_error) {
                $this->log_error('Database error in get_entry_stats_by_month: ' . $wpdb->last_error);
                return array(
                    'labels' => array(),
                    'data' => array(),
                    'error' => 'Database query failed'
                );
            }
            
            // Fill in missing months with 0 entries
            $month_data = array();
            $labels = array();
            
            for ($i = $months - 1; $i >= 0; $i--) {
                $month = date('Y-m', strtotime("-{$i} months"));
                $month_data[$month] = 0;
                $labels[] = date('M Y', strtotime("-{$i} months"));
            }
            
            // Fill in actual data
            foreach ($results as $row) {
                if (isset($month_data[$row->month])) {
                    $month_data[$row->month] = intval($row->entry_count);
                }
            }
            
            return array(
                'labels' => $labels,
                'data' => array_values($month_data)
            );
            
        } catch (Exception $e) {
            $this->log_error('Exception in get_entry_stats_by_month: ' . $e->getMessage());
            return array(
                'labels' => array(),
                'data' => array(),
                'error' => 'Failed to retrieve entry statistics'
            );
        }
    }

    /**
     * Get entry statistics by form for pie chart
     */
    private function get_entry_stats_by_form() {
        global $wpdb;
        
        // Check if Gravity Forms tables exist
        if (!$this->gf_tables_exist()) {
            return array(
                'labels' => array(),
                'data' => array(),
                'error' => 'Gravity Forms tables not found'
            );
        }
        
        try {
            $results = $wpdb->get_results("
                SELECT 
                    f.title as form_name,
                    f.id as form_id,
                    COUNT(e.id) as entry_count
                FROM {$wpdb->prefix}gf_form f
                LEFT JOIN {$wpdb->prefix}gf_entry e ON f.id = e.form_id AND e.status = 'active'
                WHERE f.is_active = 1 AND f.is_trash = 0
                GROUP BY f.id, f.title
                HAVING entry_count > 0
                ORDER BY entry_count DESC
                LIMIT 10
            ");
            
            if ($wpdb->last_error) {
                $this->log_error('Database error in get_entry_stats_by_form: ' . $wpdb->last_error);
                return array(
                    'labels' => array(),
                    'data' => array(),
                    'error' => 'Database query failed'
                );
            }
            
            $form_names = array();
            $entry_counts = array();
            
            foreach ($results as $row) {
                $form_names[] = $row->form_name;
                $entry_counts[] = intval($row->entry_count);
            }
            
            return array(
                'labels' => $form_names,
                'data' => $entry_counts
            );
            
        } catch (Exception $e) {
            $this->log_error('Exception in get_entry_stats_by_form: ' . $e->getMessage());
            return array(
                'labels' => array(),
                'data' => array(),
                'error' => 'Failed to retrieve form statistics'
            );
        }
    }

    /**
     * Get total entries count
     */
    private function get_total_entries() {
        global $wpdb;
        
        $count = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$wpdb->prefix}gf_entry 
            WHERE status = 'active'
        ");
        
        return intval($count);
    }

    /**
     * Get active forms count
     */
    private function get_active_forms_count() {
        global $wpdb;
        
        $count = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$wpdb->prefix}gf_form 
            WHERE is_active = 1 AND is_trash = 0
        ");
        
        return intval($count);
    }

    /**
     * Get inactive forms count (forms that exist but are deactivated, not trashed)
     */
    private function get_inactive_forms_count() {
        global $wpdb;
        
        $count = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$wpdb->prefix}gf_form 
            WHERE is_active = 0 AND is_trash = 0
        ");
        
        return intval($count);
    }

    /**
     * Get recent entries count
     */
    private function get_recent_entries_count($days = 30) {
        global $wpdb;
        
        $count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) 
            FROM {$wpdb->prefix}gf_entry 
            WHERE status = 'active'
            AND date_created >= DATE_SUB(NOW(), INTERVAL %d DAY)
        ", $days));
        
        return intval($count);
    }

    /**
     * Check if Gravity Forms tables exist
     */
    private function gf_tables_exist() {
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'gf_form',
            $wpdb->prefix . 'gf_entry'
        );
        
        foreach ($tables as $table) {
            $result = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
            if ($result !== $table) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Log error messages
     */
    public function log_error($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Form Locator Error: ' . $message);
        }
    }

    /**
     * Plugin settings fields (if needed in the future)
     */
    public function plugin_settings_fields() {
        return array(
            array(
                'title'  => esc_html__('Form Locator Settings', 'form-locator-for-gravity-forms'),
                'fields' => array(
                    array(
                        'name'    => 'scan_all_post_types',
                        'tooltip' => esc_html__('When enabled, the scan includes draft, private, pending, future, and trashed content in addition to published posts. By default, only published content is scanned.', 'form-locator-for-gravity-forms'),
                        'label'   => esc_html__('Include Draft and Private Content', 'form-locator-for-gravity-forms'),
                        'type'    => 'checkbox',
                        'choices' => array(
                            array(
                                'label' => esc_html__('Include draft, private, and other post statuses in scan', 'form-locator-for-gravity-forms'),
                                'name'  => 'scan_all_post_types',
                            ),
                        ),
                    ),
                ),
            ),
        );
    }

    /**
     * Minimum requirements for this add-on
     */
    public function minimum_requirements() {
        return array(
            'gravityforms' => array(
                'version' => $this->_min_gravityforms_version,
            ),
            'wordpress' => array(
                'version' => '6.7',
            ),
            'php' => array(
                'version' => '8.0',
            ),
        );
    }
}