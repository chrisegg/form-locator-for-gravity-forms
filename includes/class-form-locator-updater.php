<?php
/**
 * Form Locator for Gravity Forms - GitHub Update Checker
 *
 * Provides automatic update notifications from GitHub releases. Hooks into
 * WordPress plugin update transients and plugins_api for seamless integration.
 *
 * @package   Form_Locator_For_Gravity_Forms
 * @since     2.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Form_Locator_Updater
 */
class Form_Locator_Updater {

    /**
     * Transient key for cached release data
     */
    const CACHE_KEY = 'form_locator_release_data';

    /**
     * Cache duration in seconds (12 hours)
     */
    const CACHE_DURATION = 43200;

    /**
     * Initialize the update checker
     */
    public static function init() {
        if (!is_admin()) {
            return;
        }

        add_filter('pre_set_site_transient_update_plugins', array(__CLASS__, 'check_for_update'));
        add_filter('plugins_api', array(__CLASS__, 'plugin_info'), 5, 3);
        add_action(
            'in_plugin_update_message-' . plugin_basename(FORM_LOCATOR_PLUGIN_FILE),
            array(__CLASS__, 'update_message')
        );
        add_action('load-update-core.php', array(__CLASS__, 'clear_release_cache'));
        add_filter('upgrader_pre_install', array(__CLASS__, 'prevent_deactivation_during_update'), 5, 2);
        add_action('upgrader_process_complete', array(__CLASS__, 'remove_cron_filter_after_update'), 10, 2);
    }

    /**
     * Clear cached release data when Updates page loads.
     * Ensures admins see new releases immediately when they check for updates.
     */
    public static function clear_release_cache() {
        delete_transient(self::CACHE_KEY);
    }

    /**
     * Prevent plugin deactivation during updates by making wp_doing_cron() return true.
     * WordPress skips deactivation when wp_doing_cron() is true (same as background updates).
     *
     * @param bool|WP_Error $response   The response object
     * @param array         $hook_extra Extra arguments passed to the hook
     * @return bool|WP_Error The unmodified response
     */
    public static function prevent_deactivation_during_update($response, $hook_extra) {
        if (isset($hook_extra['type']) && $hook_extra['type'] === 'plugin') {
            add_filter('wp_doing_cron', '__return_true');
        }
        return $response;
    }

    /**
     * Remove the wp_doing_cron filter after plugin update completes.
     *
     * @param WP_Upgrader $upgrader The upgrader instance
     * @param array       $options  Array of update data
     */
    public static function remove_cron_filter_after_update($upgrader, $options) {
        if (isset($options['type']) && $options['type'] === 'plugin') {
            remove_filter('wp_doing_cron', '__return_true');
        }
    }

    /**
     * Check for plugin updates
     *
     * @param object $transient The update plugins transient
     * @return object Modified transient
     */
    public static function check_for_update($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }

        $current_version = FORM_LOCATOR_VERSION;
        $release_data    = self::get_release_data();

        if (!$release_data || empty($release_data['version'])) {
            return $transient;
        }

        $latest_version = $release_data['version'];
        if (version_compare($latest_version, $current_version, '>')) {
            $plugin_slug = plugin_basename(FORM_LOCATOR_PLUGIN_FILE);
            $tag_name    = $release_data['tag_name'] ?? ('v' . $latest_version);
            $package_url = 'https://github.com/' . FORM_LOCATOR_GITHUB_REPO . '/archive/refs/tags/' . $tag_name . '.zip';

            $readme         = self::get_readme_sections();
            $desc_content   = !empty($readme['description']) ? $readme['description'] : esc_html__('Comprehensive Gravity Forms detection across WordPress pages, posts, and page builders.', 'form-locator-for-gravity-forms');
            $changelog_raw  = $release_data['changelog'] ?? '';
            if (empty(trim($changelog_raw))) {
                $changelog_raw = self::get_changelog_from_readme();
            }

            $transient->response[$plugin_slug] = (object) array(
                'slug'          => 'form-locator-for-gravity-forms',
                'plugin'        => $plugin_slug,
                'new_version'   => $latest_version,
                'url'           => 'https://github.com/' . FORM_LOCATOR_GITHUB_REPO,
                'package'       => $package_url,
                'requires'      => '6.7',
                'requires_php'  => '8.0.0',
                'tested'        => '7.0.0',
                'last_updated'  => $release_data['date'] ?? '',
                'sections'     => array(
                    'description' => nl2br(esc_html($desc_content)),
                    'changelog'   => $changelog_raw ? nl2br(esc_html($changelog_raw)) : '',
                ),
            );
        }

        return $transient;
    }

    /**
     * Provide plugin information for the "View details" modal
     *
     * @param false|object|array $result The result object or array
     * @param string             $action The type of information being requested
     * @param object|null        $args   Plugin API arguments
     * @return false|object Plugin information or false
     */
    public static function plugin_info($result, $action, $args) {
        if ($action !== 'plugin_information') {
            return $result;
        }

        $slug = is_object($args) ? ($args->slug ?? '') : ($args['slug'] ?? '');
        $valid_slugs = array('form-locator-for-gravity-forms', 'form-locator-for-gravity-forms/form-locator-for-gravity-forms');
        if (!in_array($slug, $valid_slugs, true) && strpos($slug, 'form-locator-for-gravity-forms') === false) {
            return $result;
        }

        $release_data = self::get_release_data();
        if (!is_array($release_data)) {
            $release_data = array();
        }

        $version   = $release_data['version'] ?? FORM_LOCATOR_VERSION;
        $date      = $release_data['date'] ?? '';
        $tag_name  = $release_data['tag_name'] ?? ('v' . $version);
        $changelog_raw = $release_data['changelog'] ?? '';

        if (empty(trim($changelog_raw))) {
            $changelog_raw = self::get_changelog_from_readme();
        }
        $changelog = self::format_changelog_for_display($changelog_raw, $version);

        $readme = self::get_readme_sections();
        $desc_text = !empty($readme['description'])
            ? $readme['description']
            : esc_html__('Comprehensive Gravity Forms detection across WordPress pages, posts, and page builders. Find forms embedded via shortcodes, blocks, widgets, and page builder modules.', 'form-locator-for-gravity-forms');
        $description = nl2br(esc_html($desc_text));

        $install_text = !empty($readme['installation'])
            ? $readme['installation']
            : esc_html__('Upload the plugin files to the /wp-content/plugins/form-locator-for-gravity-forms directory, or install the plugin through the WordPress admin screen directly. Activate the plugin through the Plugins screen in WordPress.', 'form-locator-for-gravity-forms');
        $installation = nl2br(esc_html($install_text));

        $screenshots = '<p>' . sprintf(
            esc_html__('Screenshots available at %s', 'form-locator-for-gravity-forms'),
            '<a href="' . esc_url('https://github.com/' . FORM_LOCATOR_GITHUB_REPO) . '">GitHub</a>'
        ) . '</p>';

        $sections = array(
            'description'   => $description ?: '<p>' . esc_html__('Form Locator for Gravity Forms - comprehensive form detection across WordPress.', 'form-locator-for-gravity-forms') . '</p>',
            'installation'   => $installation ?: '<p>' . esc_html__('Upload and activate via the Plugins screen.', 'form-locator-for-gravity-forms') . '</p>',
            'changelog'      => $changelog,
            'screenshots'    => $screenshots,
        );

        return (object) array(
            'name'            => 'Form Locator for Gravity Forms',
            'slug'            => 'form-locator-for-gravity-forms',
            'version'         => $version,
            'author'          => 'Chris Eggleston',
            'author_profile'  => 'https://gravityranger.com',
            'last_updated'    => $date,
            'homepage'       => 'https://github.com/' . FORM_LOCATOR_GITHUB_REPO,
            'sections'       => $sections,
            'download_link'  => 'https://github.com/' . FORM_LOCATOR_GITHUB_REPO . '/archive/refs/tags/' . $tag_name . '.zip',
        );
    }

    /**
     * Format changelog for plugin info modal - matches Gravity Forms Stripe structure (h2 + ul/li).
     *
     * @param string $changelog_raw Raw changelog text
     * @param string $version      Plugin version
     * @return string HTML safe for wp_kses
     */
    private static function format_changelog_for_display($changelog_raw, $version) {
        if (empty(trim($changelog_raw))) {
            return '<p>' . esc_html__('Changelog not available.', 'form-locator-for-gravity-forms') . '</p>';
        }
        $lines = explode("\n", trim($changelog_raw));
        $items = array();
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $content = preg_replace('/^[=*\-]+\s*/', '', $line);
            $content = preg_replace('/\s*[=*\-]+$/', '', $content);
            $items[] = '<li>' . esc_html($content) . '</li>';
        }
        if (empty($items)) {
            return nl2br(esc_html($changelog_raw));
        }
        return '<h2>Form Locator for Gravity Forms v' . esc_html($version) . ' Changelog</h2><ul>' . implode('', $items) . '</ul>';
    }

    /**
     * Convert markdown to simple HTML using only wp_kses-allowed tags.
     *
     * @param string $text Raw markdown or plain text
     * @return string HTML safe for plugin info sections
     */
    private static function simple_markdown_to_html($text) {
        if (empty(trim($text))) {
            return '';
        }
        $html = esc_html($text);
        $html = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $html);
        $html = preg_replace('/\*(.+?)\*/s', '<em>$1</em>', $html);
        $html = preg_replace('/`([^`]+)`/', '<code>$1</code>', $html);
        $html = preg_replace('/^## (.+)$/m', '<h2>$1</h2>', $html);
        $html = preg_replace('/^### (.+)$/m', '<h3>$1</h3>', $html);
        $html = preg_replace('/^= ([^=]+) =$/m', '<h3>$1</h3>', $html);
        $html = preg_replace('/^[-*] (.+)$/m', '<li>$1</li>', $html);
        $html = preg_replace('/(<li>.*?<\/li>\s*)+/s', '<ul>$0</ul>', $html);
        return nl2br($html);
    }

    /**
     * Get readme sections (Description, Installation) from readme.txt
     *
     * @return array{description?: string, installation?: string}
     */
    private static function get_readme_sections() {
        $readme_path = FORM_LOCATOR_PLUGIN_DIR . 'readme.txt';
        if (!is_readable($readme_path)) {
            return array();
        }
        $content = file_get_contents($readme_path);
        if ($content === false) {
            return array();
        }
        $sections = array();
        $current  = null;
        $buffer   = array();
        $lines    = explode("\n", $content);
        foreach ($lines as $line) {
            if (preg_match('/^== ([^=]+) ==$/', $line, $m)) {
                if ($current === 'description') {
                    $sections['description'] = trim(implode("\n", $buffer));
                } elseif ($current === 'installation') {
                    $sections['installation'] = trim(implode("\n", $buffer));
                }
                $next = strtolower(trim($m[1]));
                $current = ($next === 'description' || $next === 'installation') ? $next : null;
                $buffer = array();
            } elseif ($current !== null) {
                $buffer[] = $line;
            }
        }
        if ($current === 'description') {
            $sections['description'] = trim(implode("\n", $buffer));
        } elseif ($current === 'installation') {
            $sections['installation'] = trim(implode("\n", $buffer));
        }
        return $sections;
    }

    /**
     * Get changelog section from readme.txt as fallback when GitHub API fails.
     *
     * @return string Changelog content or empty string
     */
    private static function get_changelog_from_readme() {
        $readme_path = FORM_LOCATOR_PLUGIN_DIR . 'readme.txt';
        if (!is_readable($readme_path)) {
            return '';
        }
        $content = file_get_contents($readme_path);
        if ($content === false) {
            return '';
        }
        $current  = null;
        $buffer   = array();
        $lines    = explode("\n", $content);
        foreach ($lines as $line) {
            if (preg_match('/^== ([^=]+) ==$/', $line, $m)) {
                if ($current === 'changelog') {
                    return trim(implode("\n", $buffer));
                }
                $next = strtolower(trim($m[1]));
                $current = ($next === 'changelog') ? $next : null;
                $buffer = array();
            } elseif ($current === 'changelog') {
                $buffer[] = $line;
            }
        }
        return $current === 'changelog' ? trim(implode("\n", $buffer)) : '';
    }

    /**
     * Display custom update message below the plugin on the Plugins screen
     */
    public static function update_message() {
        echo '<br><strong>' . esc_html__('Note:', 'form-locator-for-gravity-forms') . '</strong> ';
        echo esc_html__('This update is from GitHub. Please backup your site before updating.', 'form-locator-for-gravity-forms');
    }

    /**
     * Fetch release data from GitHub API (cached)
     *
     * @return array{version?: string, date?: string, changelog?: string}|false
     */
    private static function get_release_data() {
        $cached = get_transient(self::CACHE_KEY);
        if ($cached !== false && is_array($cached)) {
            return $cached;
        }

        $api_url  = 'https://api.github.com/repos/' . FORM_LOCATOR_GITHUB_REPO . '/releases/latest';
        $response = wp_remote_get(
            $api_url,
            array(
                'timeout' => 15,
                'headers' => array(
                    'User-Agent' => 'WordPress/' . get_bloginfo('version') . '; ' . get_bloginfo('url'),
                ),
            )
        );

        if (is_wp_error($response)) {
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (empty($data) || !isset($data['tag_name'])) {
            return false;
        }

        $version = ltrim($data['tag_name'], 'v');
        $date    = '';
        if (!empty($data['published_at'])) {
            $date = gmdate('Y-m-d', strtotime($data['published_at']));
        }
        $changelog = $data['body'] ?? '';
        $tag_name  = $data['tag_name'] ?? '';

        $release_data = array(
            'version'   => $version,
            'tag_name'  => $tag_name,
            'date'      => $date,
            'changelog' => $changelog,
        );

        set_transient(self::CACHE_KEY, $release_data, self::CACHE_DURATION);

        return $release_data;
    }
}
