<?php
// Prevent direct file access
if (!defined('ABSPATH')) {
    exit;
}

class Form_Locator_For_Gravity_Forms {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_gf_pages_menu'], 999);
    }

    // Register the admin menu for the plugin
    public function add_gf_pages_menu() {
        add_submenu_page(
            'gf_edit_forms', // Parent slug (Gravity Forms menu)
            'Form Locator', // Page title
            'Form Locator', // Menu title
            'manage_options', // Capability required to access
            'gf_form_locator', // Menu slug
            [$this, 'list_gravity_forms_pages'] // Callback function to render page
        );
    }

  // List pages containing Gravity Forms shortcodes or blocks
  public function list_gravity_forms_pages() {
      global $wpdb;
  
      try {
          // Perform the scan and prepare data for output
          $gf_pages = [];
          $total_posts_scanned = 0;
  
          // Retrieve all published posts
          $results = $wpdb->get_results($wpdb->prepare(
              "SELECT ID, post_title, post_type, post_content FROM {$wpdb->posts} WHERE post_status = %s", 'publish'
          ), ARRAY_A);
  
          if ($wpdb->last_error) {
              throw new Exception('Database error: ' . $wpdb->last_error);
          }
  
          $total_posts_scanned = count($results);
  
          // Scan for Gravity Forms usage in content
          foreach ($results as $post) {
              error_log('Scanning post ID: ' . $post['ID'] . ' - Title: ' . $post['post_title']);
              
              $form_ids = $this->get_gravity_form_ids($post['post_content']);
              $block_form_ids = $this->get_gravity_block_form_ids($post['post_content']);
              $page_builder_form_ids = $this->get_page_builder_form_ids($post['ID'], $post['post_content']);
              $has_login_form = $this->has_gravity_login_form($post['post_content']);
  
              if (!empty($form_ids) || !empty($block_form_ids) || !empty($page_builder_form_ids) || $has_login_form) {
                  error_log('Found forms in post ID: ' . $post['ID'] . ' - Shortcodes: ' . implode(',', $form_ids) . ' - Blocks: ' . implode(',', $block_form_ids) . ' - Page Builder: ' . implode(',', $page_builder_form_ids));
                  
                  $gf_pages[] = [
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
  
          // Pass data to the view file
          include plugin_dir_path(__FILE__) . '../views/admin-page.php';
  
      } catch (Exception $e) {
          $this->log_error($e->getMessage());
          echo '<div class="error"><p>There was an error processing your request. Please try again later.</p></div>';
      }
  }

    // Extract form IDs from Gravity Forms shortcodes securely
    private function get_gravity_form_ids($content) {
        preg_match_all('/\\[gravityform[^\\]]*id=[\"\\\']?(\\d+)[\"\\\']?/i', $content, $matches);
        return !empty($matches[1]) ? array_map('intval', $matches[1]) : [];
    }

    // Extract form IDs from Gravity Forms blocks securely
    private function get_gravity_block_form_ids($content) {
        preg_match_all('/"formId"\s*:\s*"?(\d+)"?/', $content, $matches);
        return !empty($matches[1]) ? array_map('intval', $matches[1]) : [];
    }

    // Extract form IDs from page builder widgets and add-ons
    private function get_page_builder_form_ids($post_id, $content) {
        $form_ids = [];
        
        error_log('Starting page builder detection for post ID: ' . $post_id);
        
        // Check Elementor data
        $elementor_form_ids = $this->get_elementor_form_ids($post_id);
        if (!empty($elementor_form_ids)) {
            $form_ids = array_merge($form_ids, $elementor_form_ids);
            error_log('Found Elementor forms: ' . implode(', ', $elementor_form_ids));
        }
        
        // Check Beaver Builder data
        error_log('Checking Beaver Builder for post ID: ' . $post_id);
        $beaver_form_ids = $this->get_beaver_builder_form_ids($post_id, $content);
        if (!empty($beaver_form_ids)) {
            $form_ids = array_merge($form_ids, $beaver_form_ids);
            error_log('Found Beaver Builder forms: ' . implode(', ', $beaver_form_ids));
        } else {
            error_log('No Beaver Builder forms found for post ID: ' . $post_id);
        }
        
        // Check Divi Builder data
        $divi_form_ids = $this->get_divi_builder_form_ids($post_id, $content);
        if (!empty($divi_form_ids)) {
            $form_ids = array_merge($form_ids, $divi_form_ids);
        }
        
        // Check WPBakery Page Builder data
        $wpbakery_form_ids = $this->get_wpbakery_form_ids($post_id, $content);
        if (!empty($wpbakery_form_ids)) {
            $form_ids = array_merge($form_ids, $wpbakery_form_ids);
        }
        
        // Check for very specific add-on patterns that are unlikely to be shortcodes
        $addon_form_ids = $this->get_addon_form_ids($content);
        if (!empty($addon_form_ids)) {
            $form_ids = array_merge($form_ids, $addon_form_ids);
        }
        
        error_log('Total page builder forms found: ' . count($form_ids));
        return array_unique($form_ids);
    }

    // Extract form IDs from Elementor data
    private function get_elementor_form_ids($post_id) {
        global $wpdb;
        $form_ids = [];
        
        // Check Elementor post meta
        $elementor_data = get_post_meta($post_id, '_elementor_data', true);
        if (!empty($elementor_data)) {
            // Handle case where get_post_meta returns an array
            if (is_array($elementor_data)) {
                $data = $elementor_data;
            } else {
                // Try to decode JSON string
                $data = json_decode($elementor_data, true);
            }
            
            if (is_array($data)) {
                $form_ids = $this->parse_elementor_widgets($data);
            }
        }
        
        return $form_ids;
    }

    // Parse Elementor widgets recursively
    private function parse_elementor_widgets($widgets) {
        $form_ids = [];
        
        if (!is_array($widgets)) {
            return $form_ids;
        }
        
        foreach ($widgets as $widget) {
            // Check for Gravity Forms widget
            if (isset($widget['widgetType']) && $widget['widgetType'] === 'gravity-forms') {
                if (isset($widget['settings']['form_id'])) {
                    $form_ids[] = intval($widget['settings']['form_id']);
                }
            }
            
            // Check for GravityKits widget
            if (isset($widget['widgetType']) && strpos($widget['widgetType'], 'gravitykits') !== false) {
                if (isset($widget['settings']['form_id'])) {
                    $form_ids[] = intval($widget['settings']['form_id']);
                }
            }
            
            // Check for custom Gravity Forms widgets
            if (isset($widget['widgetType']) && strpos($widget['widgetType'], 'gravity') !== false) {
                if (isset($widget['settings']['form_id'])) {
                    $form_ids[] = intval($widget['settings']['form_id']);
                }
                if (isset($widget['settings']['gravity_form_id'])) {
                    $form_ids[] = intval($widget['settings']['gravity_form_id']);
                }
            }
            
            // Recursively check nested elements
            if (isset($widget['elements']) && is_array($widget['elements'])) {
                $nested_form_ids = $this->parse_elementor_widgets($widget['elements']);
                $form_ids = array_merge($form_ids, $nested_form_ids);
            }
        }
        
        return $form_ids;
    }

    // Extract form IDs from Beaver Builder data
    private function get_beaver_builder_form_ids($post_id, $content = '') {
        global $wpdb;
        $form_ids = [];
        
        // Check Beaver Builder post meta
        $beaver_data = get_post_meta($post_id, '_fl_builder_data', true);
        if (!empty($beaver_data)) {
            error_log('Found Beaver Builder data for post ID: ' . $post_id);
            
            // Handle case where get_post_meta returns an array
            if (is_array($beaver_data)) {
                $data = $beaver_data;
                error_log('Beaver Builder data is already an array with ' . count($data) . ' items');
            } else {
                // Try to decode JSON string
                $data = json_decode($beaver_data, true);
                error_log('Beaver Builder data is JSON string, decoded to array with ' . (is_array($data) ? count($data) : 'invalid') . ' items');
            }
            
            if (is_array($data)) {
                foreach ($data as $index => $node) {
                    error_log('Processing Beaver Builder node ' . $index);
                    
                    // Handle both object and array formats
                    if (is_object($node)) {
                        error_log('Node ' . $index . ' is object, type: ' . (isset($node->type) ? $node->type : 'unknown'));
                        if (isset($node->type) && $node->type === 'module') {
                            error_log('Node ' . $index . ' is a module');
                            if (isset($node->settings->form_id)) {
                                error_log('Found form_id in node ' . $index . ': ' . $node->settings->form_id);
                                $form_ids[] = intval($node->settings->form_id);
                            }
                            if (isset($node->settings->gravity_form_id)) {
                                error_log('Found gravity_form_id in node ' . $index . ': ' . $node->settings->gravity_form_id);
                                $form_ids[] = intval($node->settings->gravity_form_id);
                            }
                            // Check for widget-specific form IDs
                            if (isset($node->settings->{'widget-gform_widget'}->form_id)) {
                                error_log('Found form_id in widget-gform_widget: ' . $node->settings->{'widget-gform_widget'}->form_id);
                                $form_ids[] = intval($node->settings->{'widget-gform_widget'}->form_id);
                            }
                            // Let's also check for any settings that might contain form IDs
                            if (isset($node->settings)) {
                                error_log('Node ' . $index . ' settings: ' . json_encode($node->settings));
                            }
                        }
                    } elseif (is_array($node)) {
                        error_log('Node ' . $index . ' is array, type: ' . (isset($node['type']) ? $node['type'] : 'unknown'));
                        if (isset($node['type']) && $node['type'] === 'module') {
                            error_log('Node ' . $index . ' is a module');
                            if (isset($node['settings']['form_id'])) {
                                error_log('Found form_id in node ' . $index . ': ' . $node['settings']['form_id']);
                                $form_ids[] = intval($node['settings']['form_id']);
                            }
                            if (isset($node['settings']['gravity_form_id'])) {
                                error_log('Found gravity_form_id in node ' . $index . ': ' . $node['settings']['gravity_form_id']);
                                $form_ids[] = intval($node['settings']['gravity_form_id']);
                            }
                            // Check for widget-specific form IDs
                            if (isset($node['settings']['widget-gform_widget']['form_id'])) {
                                error_log('Found form_id in widget-gform_widget: ' . $node['settings']['widget-gform_widget']['form_id']);
                                $form_ids[] = intval($node['settings']['widget-gform_widget']['form_id']);
                            }
                            // Let's also check for any settings that might contain form IDs
                            if (isset($node['settings'])) {
                                error_log('Node ' . $index . ' settings: ' . json_encode($node['settings']));
                            }
                        }
                    }
                }
            } else {
                error_log('Beaver Builder data could not be parsed as array');
            }
        } else {
            error_log('No Beaver Builder data found for post ID: ' . $post_id);
        }
        
        // If no forms found in structured data, check the rendered content
        if (empty($form_ids)) {
            error_log('No forms found in Beaver Builder structured data, checking content sources');
            
            // Try multiple content sources
            $content_sources = [
                $content, // Original content passed in
                get_post_field('post_content', $post_id), // Raw post content
                get_post_field('post_content_filtered', $post_id), // Filtered content
                $this->get_rendered_content($post_id), // Rendered content with filters applied
            ];
            
            foreach ($content_sources as $index => $content_source) {
                if (!empty($content_source)) {
                    error_log('Checking content source ' . $index . ' for post ID ' . $post_id . ' (length: ' . strlen($content_source) . ')');
                    // Let's also log a snippet of the content to see what we're working with
                    if ($index === 0) {
                        error_log('Content snippet: ' . substr($content_source, 0, 500));
                    }
                    $beaver_form_ids = $this->get_beaver_builder_from_content($content_source);
                    if (!empty($beaver_form_ids)) {
                        error_log('Found forms in content source ' . $index . ': ' . implode(', ', $beaver_form_ids));
                        $form_ids = array_merge($form_ids, $beaver_form_ids);
                        break; // Found forms, no need to check other sources
                    }
                } else {
                    error_log('Content source ' . $index . ' is empty for post ID ' . $post_id);
                }
            }
        } else {
            error_log('Found forms in Beaver Builder structured data: ' . implode(', ', $form_ids));
        }
        
        return $form_ids;
    }

    // Extract form IDs from Beaver Builder rendered content
    private function get_beaver_builder_from_content($content) {
        $form_ids = [];
        
        // Test if error logging is working
        error_log('TEST: Beaver Builder content check - Content length: ' . strlen($content));
        
        // Look for gform_widget class which indicates Gravity Forms widget
        if (strpos($content, 'gform_widget') !== false) {
            error_log('Found gform_widget in content for post');
            
            // Extract form IDs from the rendered HTML
            preg_match_all('/id="gform_(\d+)"/', $content, $matches);
            if (!empty($matches[1])) {
                error_log('Found form IDs from id attribute: ' . implode(', ', $matches[1]));
                $form_ids = array_merge($form_ids, array_map('intval', $matches[1]));
            }
            
            // Also check for data-formid attribute
            preg_match_all('/data-formid="(\d+)"/', $content, $matches);
            if (!empty($matches[1])) {
                error_log('Found form IDs from data-formid: ' . implode(', ', $matches[1]));
                $form_ids = array_merge($form_ids, array_map('intval', $matches[1]));
            }
            
            // Check for form_id in hidden inputs
            preg_match_all('/name="gform_submit" value="(\d+)"/', $content, $matches);
            if (!empty($matches[1])) {
                error_log('Found form IDs from gform_submit: ' . implode(', ', $matches[1]));
                $form_ids = array_merge($form_ids, array_map('intval', $matches[1]));
            }
        } else {
            error_log('No gform_widget found in content');
            
            // Let's also check for other Beaver Builder indicators
            if (strpos($content, 'fl-builder') !== false) {
                error_log('Found fl-builder indicator in content');
            }
            if (strpos($content, '<!-- wp:fl-builder') !== false) {
                error_log('Found Beaver Builder block in content');
            }
            
            // Look for widget titles that indicate Gravity Forms
            if (strpos($content, 'fl-builder-settings-title-text-wrap') !== false) {
                error_log('Found Beaver Builder widget title in content');
                
                // Look for "Form" in the widget title
                if (preg_match('/<span[^>]*class="[^"]*fl-builder-settings-title-text-wrap[^"]*"[^>]*>Form<\/span>/', $content)) {
                    error_log('Found "Form" widget title in Beaver Builder');
                    
                    // Now look for form IDs in the widget settings
                    preg_match_all('/"form_id"[\s]*:[\s]*["\']?(\d+)["\']?/', $content, $matches);
                    if (!empty($matches[1])) {
                        error_log('Found form IDs from widget settings: ' . implode(', ', $matches[1]));
                        $form_ids = array_merge($form_ids, array_map('intval', $matches[1]));
                    }
                    
                    // Also look for other form ID patterns in the widget data
                    preg_match_all('/"id"[\s]*:[\s]*["\']?(\d+)["\']?/', $content, $matches);
                    if (!empty($matches[1])) {
                        error_log('Found potential form IDs from widget data: ' . implode(', ', $matches[1]));
                        // We'll need to filter these to only include valid form IDs
                        foreach ($matches[1] as $potential_id) {
                            $form_id = intval($potential_id);
                            if ($form_id > 0 && $form_id < 10000) { // Reasonable form ID range
                                $form_ids[] = $form_id;
                            }
                        }
                    }
                }
            }
            
            // Look for Gravity Forms specific patterns in Beaver Builder data
            if (strpos($content, 'gravity') !== false || strpos($content, 'gform') !== false) {
                error_log('Found gravity/gform indicators in content');
                
                // Look for form IDs in various patterns
                preg_match_all('/"form_id"[\s]*:[\s]*["\']?(\d+)["\']?/', $content, $matches);
                if (!empty($matches[1])) {
                    error_log('Found form IDs from gravity patterns: ' . implode(', ', $matches[1]));
                    $form_ids = array_merge($form_ids, array_map('intval', $matches[1]));
                }
            }
        }
        
        return array_unique($form_ids);
    }

    // Get rendered content by applying WordPress filters
    private function get_rendered_content($post_id) {
        $post = get_post($post_id);
        if (!$post) {
            return '';
        }
        
        // Apply content filters to get the rendered HTML
        $rendered_content = apply_filters('the_content', $post->post_content);
        
        // Also try with Beaver Builder specific filters
        if (class_exists('FLBuilderModel')) {
            $rendered_content = apply_filters('fl_builder_render_content', $rendered_content, $post);
        }
        
        return $rendered_content;
    }

    // Extract form IDs from Divi Builder data
    private function get_divi_builder_form_ids($post_id, $content) {
        $form_ids = [];
        
        // Check Divi shortcodes in content
        preg_match_all('/\[et_pb_gravityform[^\]]*form_id=[\"\']?(\d+)[\"\']?/i', $content, $matches);
        if (!empty($matches[1])) {
            $form_ids = array_merge($form_ids, array_map('intval', $matches[1]));
        }
        
        // Check Divi post meta
        $divi_data = get_post_meta($post_id, '_et_pb_use_builder', true);
        if ($divi_data === 'on') {
            // Divi stores data in shortcodes, which we already checked above
        }
        
        return $form_ids;
    }

    // Extract form IDs from WPBakery Page Builder data
    private function get_wpbakery_form_ids($post_id, $content) {
        $form_ids = [];
        
        // Check WPBakery shortcodes in content - but only if they're not standard shortcodes
        // Look for WPBakery specific patterns
        preg_match_all('/\[vc_gravityform[^\]]*id=[\"\']?(\d+)[\"\']?/i', $content, $matches);
        if (!empty($matches[1])) {
            $form_ids = array_merge($form_ids, array_map('intval', $matches[1]));
        }
        
        return $form_ids;
    }

    // Extract form IDs from various add-ons and custom implementations
    private function get_addon_form_ids($content) {
        $form_ids = [];
        
        // Check for GravityKits patterns in JSON/object contexts
        preg_match_all('/gravitykits[^}]*form_id[^}]*:[\s]*["\']?(\d+)["\']?/i', $content, $matches);
        if (!empty($matches[1])) {
            $form_ids = array_merge($form_ids, array_map('intval', $matches[1]));
        }
        
        // Check for very specific widget patterns that indicate page builder usage
        preg_match_all('/"widgetType"[\s]*:[\s]*"gravity[^"]*"[^}]*"form_id"[\s]*:[\s]*["\']?(\d+)["\']?/i', $content, $matches);
        if (!empty($matches[1])) {
            $form_ids = array_merge($form_ids, array_map('intval', $matches[1]));
        }
        
        // Check for module patterns that indicate page builder usage
        preg_match_all('/"type"[\s]*:[\s]*"module"[^}]*"form_id"[\s]*:[\s]*["\']?(\d+)["\']?/i', $content, $matches);
        if (!empty($matches[1])) {
            $form_ids = array_merge($form_ids, array_map('intval', $matches[1]));
        }
        
        return $form_ids;
    }

    // Check if the content has a Gravity Forms login form securely
    private function has_gravity_login_form($content) {
        return (bool) preg_match('/\[gravityform[^]]*action=["\']login["\']/', $content);
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
