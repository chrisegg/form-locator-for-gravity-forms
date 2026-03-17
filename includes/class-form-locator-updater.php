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

require_once FORM_LOCATOR_PLUGIN_DIR . 'includes/parsedown/Parsedown.php';

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
        add_filter('plugins_api', array(__CLASS__, 'plugin_info'), 10, 3);
        add_action(
            'in_plugin_update_message-' . plugin_basename(FORM_LOCATOR_PLUGIN_FILE),
            array(__CLASS__, 'update_message')
        );
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
                    'description' => self::format_plugin_section($desc_content),
                    'changelog'   => $changelog_raw ? self::format_plugin_section($changelog_raw) : '',
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

        if (!isset($args->slug) || $args->slug !== 'form-locator-for-gravity-forms') {
            return $result;
        }

        $release_data   = self::get_release_data();
        $version        = $release_data['version'] ?? FORM_LOCATOR_VERSION;
        $date           = $release_data['date'] ?? '';
        $changelog_raw  = $release_data['changelog'] ?? '';
        $changelog      = $changelog_raw ? self::format_plugin_section($changelog_raw) : esc_html__('Changelog not available.', 'form-locator-for-gravity-forms');

        $readme         = self::get_readme_sections();
        $description    = !empty($readme['description']) ? self::format_plugin_section($readme['description']) : self::format_plugin_section(esc_html__('Comprehensive Gravity Forms detection across WordPress pages, posts, and page builders. Find forms embedded via shortcodes, blocks, widgets, and page builder modules.', 'form-locator-for-gravity-forms'));
        $installation   = !empty($readme['installation']) ? self::format_plugin_section($readme['installation']) : self::format_plugin_section(esc_html__('Upload the plugin files to the /wp-content/plugins/form-locator-for-gravity-forms directory, or install the plugin through the WordPress admin screen directly. Activate the plugin through the Plugins screen in WordPress.', 'form-locator-for-gravity-forms'));

        $plugin_info = (object) array(
            'name'            => 'Form Locator for Gravity Forms',
            'slug'            => 'form-locator-for-gravity-forms',
            'version'         => $version,
            'author'          => 'Chris Eggleston',
            'author_profile'  => 'https://gravityranger.com',
            'last_updated'    => $date,
            'homepage'       => 'https://github.com/' . FORM_LOCATOR_GITHUB_REPO,
            'sections'       => array(
                'description'   => $description,
                'installation'   => $installation,
                'changelog'      => $changelog,
                'screenshots'    => 'https://github.com/' . FORM_LOCATOR_GITHUB_REPO,
            ),
            'download_link'  => 'https://github.com/' . FORM_LOCATOR_GITHUB_REPO . '/archive/refs/tags/' . ($release_data['tag_name'] ?? 'v' . $version) . '.zip',
        );

        return $plugin_info;
    }

    /**
     * Convert markdown to HTML for plugin info sections
     *
     * @param string $markdown Raw markdown text
     * @return string HTML output safe for wp_kses_post
     */
    private static function format_plugin_section($markdown) {
        if (empty(trim($markdown))) {
            return '';
        }
        $parsedown = new Parsedown();
        $parsedown->setSafeMode(true);
        return $parsedown->text($markdown);
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
