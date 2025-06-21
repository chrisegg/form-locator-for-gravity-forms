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
              $form_ids = $this->get_gravity_form_ids($post['post_content']);
              $block_form_ids = $this->get_gravity_block_form_ids($post['post_content']);
              $page_builder_form_ids = $this->get_page_builder_form_ids($post['ID'], $post['post_content']);
              $has_login_form = $this->has_gravity_login_form($post['post_content']);
  
              if (!empty($form_ids) || !empty($block_form_ids) || !empty($page_builder_form_ids) || $has_login_form) {
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
        $avada_form_ids = $this->get_avada_fusion_builder_form_ids($post_id, $content);
        if (!empty($avada_form_ids)) {
            $form_ids = array_merge($form_ids, $avada_form_ids);
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
            // Handle case where get_post_meta returns an array
            if (is_array($beaver_data)) {
                $data = $beaver_data;
            } else {
                // Try to decode JSON string
                $data = json_decode($beaver_data, true);
            }
            
            if (is_array($data)) {
                foreach ($data as $node) {
                    // Handle both object and array formats
                    if (is_object($node)) {
                        if (isset($node->type) && $node->type === 'module') {
                            if (isset($node->settings->form_id)) {
                                $form_ids[] = intval($node->settings->form_id);
                            }
                            if (isset($node->settings->gravity_form_id)) {
                                $form_ids[] = intval($node->settings->gravity_form_id);
                            }
                            // Check for widget-specific form IDs
                            if (isset($node->settings->{'widget-gform_widget'}->form_id)) {
                                $form_ids[] = intval($node->settings->{'widget-gform_widget'}->form_id);
                            }
                        }
                    } elseif (is_array($node)) {
                        if (isset($node['type']) && $node['type'] === 'module') {
                            if (isset($node['settings']['form_id'])) {
                                $form_ids[] = intval($node['settings']['form_id']);
                            }
                            if (isset($node['settings']['gravity_form_id'])) {
                                $form_ids[] = intval($node['settings']['gravity_form_id']);
                            }
                            // Check for widget-specific form IDs
                            if (isset($node['settings']['widget-gform_widget']['form_id'])) {
                                $form_ids[] = intval($node['settings']['widget-gform_widget']['form_id']);
                            }
                        }
                    }
                }
            }
        }
        
        return $form_ids;
    }

    // Extract form IDs from Divi Builder data
    private function get_divi_builder_form_ids($post_id, $content) {
        $form_ids = [];
        
        // Check Divi shortcodes in content
        preg_match_all('/\[et_pb_gravityform[^\]]*form_id=[\"\']?(\d+)[\"\']?/i', $content, $matches);
        if (!empty($matches[1])) {
            $form_ids = array_merge($form_ids, array_map('intval', $matches[1]));
        }
        
        return $form_ids;
    }

    // Extract form IDs from WPBakery Page Builder data
    private function get_wpbakery_form_ids($post_id, $content) {
        $form_ids = [];
        
        // Check WPBakery shortcodes in content
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

    // Extract form IDs from Avada Fusion Builder data
    private function get_avada_fusion_builder_form_ids($post_id, $content) {
        $form_ids = [];
        
        // Check Avada Fusion Builder post meta
        $avada_data = get_post_meta($post_id, '_fusion_builder_content', true);
        if (!empty($avada_data)) {
            // Avada stores data as JSON string
            $data = json_decode($avada_data, true);
            if (is_array($data)) {
                $form_ids = $this->parse_avada_elements($data);
            }
        }
        
        // Also check for Avada shortcodes in content
        preg_match_all('/\[fusion_gravityform[^\]]*form_id=[\"\']?(\d+)[\"\']?/i', $content, $matches);
        if (!empty($matches[1])) {
            $form_ids = array_merge($form_ids, array_map('intval', $matches[1]));
        }
        
        // Check for Avada's alternative shortcode format
        preg_match_all('/\[fusion_gravity_forms[^\]]*id=[\"\']?(\d+)[\"\']?/i', $content, $matches);
        if (!empty($matches[1])) {
            $form_ids = array_merge($form_ids, array_map('intval', $matches[1]));
        }
        
        return $form_ids;
    }

    // Parse Avada Fusion Builder elements recursively
    private function parse_avada_elements($elements) {
        $form_ids = [];
        
        if (!is_array($elements)) {
            return $form_ids;
        }
        
        foreach ($elements as $element) {
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
            
            // Recursively check nested elements
            if (isset($element['children']) && is_array($element['children'])) {
                $nested_form_ids = $this->parse_avada_elements($element['children']);
                $form_ids = array_merge($form_ids, $nested_form_ids);
            }
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
