<?php
/**
 * Plugin Name: HunterVD Client Training Dashboard
 * Description: Adds client training videos/instructions to the WordPress dashboard and protects Elementor-built pages from accidental WordPress editor edits.
 * Version: 1.0.0
 * Author: HunterVDigital
 * License: GPL-2.0-or-later
 * Text Domain: huntervd-client-training-dashboard
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if (! defined('ABSPATH')) {
    exit;
}

final class HunterVD_Client_Training_Dashboard
{
    private const OPTION_KEY = 'huntervd_ctd_settings';
    private const PAGE_SLUG = 'huntervd-client-training-dashboard';
    private const PLUGIN_SLUG = 'huntervd-client-training-dashboard';

    private const DEFAULT_SETTINGS = [
        'videos' => "https://vimeo.com/1187170255\nhttps://vimeo.com/1187170534?share=copy&fl=sv&fe=ci",
        'instruction_heading' => 'Welcome to your Admin Dash',
        'instruction_text' => 'If you need any assistance, please contact HunterVDigital Immediatly!',
        'disable_editor_links_for_elementor' => 1,
        'github_repo' => '',
        'enable_github_auto_updates' => 1,
    ];

    public function __construct()
    {
        if (! defined('HUNTERVD_CTD_PLUGIN_BASENAME')) {
            define('HUNTERVD_CTD_PLUGIN_BASENAME', plugin_basename(__FILE__));
        }
        register_activation_hook(__FILE__, [$this, 'activate']);

        add_action('admin_menu', [$this, 'register_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('wp_dashboard_setup', [$this, 'register_dashboard_widget']);

        add_filter('get_edit_post_link', [$this, 'filter_edit_post_link'], 10, 3);
        add_action('admin_bar_menu', [$this, 'remove_frontend_admin_bar_edit'], 999);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);

        add_filter('pre_set_site_transient_update_plugins', [$this, 'inject_github_update'], 50);
        add_filter('plugins_api', [$this, 'plugins_api'], 10, 3);
        add_filter('auto_update_plugin', [$this, 'enable_auto_update_for_this_plugin'], 10, 2);
    }

    public function activate(): void
    {
        if (! get_option(self::OPTION_KEY)) {
            add_option(self::OPTION_KEY, self::DEFAULT_SETTINGS);
        }
    }

    private function get_settings(): array
    {
        $stored = get_option(self::OPTION_KEY, []);

        if (! is_array($stored)) {
            $stored = [];
        }

        return wp_parse_args($stored, self::DEFAULT_SETTINGS);
    }

    public function register_settings_page(): void
    {
        add_options_page(
            __('Client Training Dashboard', 'huntervd-client-training-dashboard'),
            __('Client Training Dashboard', 'huntervd-client-training-dashboard'),
            'manage_options',
            self::PAGE_SLUG,
            [$this, 'render_settings_page']
        );
    }

    public function register_settings(): void
    {
        register_setting(self::OPTION_KEY, self::OPTION_KEY, [$this, 'sanitize_settings']);
    }

    public function sanitize_settings(array $input): array
    {
        $current = $this->get_settings();

        $current['videos'] = isset($input['videos']) ? trim((string) $input['videos']) : '';
        $current['instruction_heading'] = isset($input['instruction_heading']) ? sanitize_text_field((string) $input['instruction_heading']) : '';
        $current['instruction_text'] = isset($input['instruction_text']) ? sanitize_textarea_field((string) $input['instruction_text']) : '';
        $current['disable_editor_links_for_elementor'] = empty($input['disable_editor_links_for_elementor']) ? 0 : 1;
        $current['github_repo'] = isset($input['github_repo']) ? sanitize_text_field((string) $input['github_repo']) : '';
        $current['enable_github_auto_updates'] = empty($input['enable_github_auto_updates']) ? 0 : 1;

        return $current;
    }

    public function render_settings_page(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        $settings = $this->get_settings();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Client Training Dashboard Settings', 'huntervd-client-training-dashboard'); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields(self::OPTION_KEY); ?>

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">
                            <label for="huntervd-videos"><?php esc_html_e('Training video URLs', 'huntervd-client-training-dashboard'); ?></label>
                        </th>
                        <td>
                            <textarea id="huntervd-videos" name="<?php echo esc_attr(self::OPTION_KEY); ?>[videos]" rows="8" class="large-text code"><?php echo esc_textarea($settings['videos']); ?></textarea>
                            <p class="description"><?php esc_html_e('One URL per line. Supports YouTube and Vimeo links.', 'huntervd-client-training-dashboard'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="huntervd-instruction-heading"><?php esc_html_e('Welcome heading', 'huntervd-client-training-dashboard'); ?></label>
                        </th>
                        <td>
                            <input id="huntervd-instruction-heading" type="text" class="regular-text" name="<?php echo esc_attr(self::OPTION_KEY); ?>[instruction_heading]" value="<?php echo esc_attr($settings['instruction_heading']); ?>" />
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="huntervd-instruction-text"><?php esc_html_e('Welcome text', 'huntervd-client-training-dashboard'); ?></label>
                        </th>
                        <td>
                            <textarea id="huntervd-instruction-text" name="<?php echo esc_attr(self::OPTION_KEY); ?>[instruction_text]" rows="4" class="large-text"><?php echo esc_textarea($settings['instruction_text']); ?></textarea>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php esc_html_e('Elementor safety', 'huntervd-client-training-dashboard'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="<?php echo esc_attr(self::OPTION_KEY); ?>[disable_editor_links_for_elementor]" value="1" <?php checked((int) $settings['disable_editor_links_for_elementor'], 1); ?> />
                                <?php esc_html_e('Hide "Back to WordPress Editor" and "Edit Page" links for Elementor-built pages.', 'huntervd-client-training-dashboard'); ?>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="huntervd-github-repo"><?php esc_html_e('GitHub repository', 'huntervd-client-training-dashboard'); ?></label>
                        </th>
                        <td>
                            <input id="huntervd-github-repo" type="text" class="regular-text code" name="<?php echo esc_attr(self::OPTION_KEY); ?>[github_repo]" value="<?php echo esc_attr($settings['github_repo']); ?>" placeholder="owner/repository" />
                            <p class="description"><?php esc_html_e('Used for plugin update checks through GitHub Releases API.', 'huntervd-client-training-dashboard'); ?></p>
                            <label>
                                <input type="checkbox" name="<?php echo esc_attr(self::OPTION_KEY); ?>[enable_github_auto_updates]" value="1" <?php checked((int) $settings['enable_github_auto_updates'], 1); ?> />
                                <?php esc_html_e('Enable automatic updates from GitHub release tags.', 'huntervd-client-training-dashboard'); ?>
                            </label>
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function register_dashboard_widget(): void
    {
        wp_add_dashboard_widget(
            'huntervd_client_training_widget',
            __('Client Training Videos', 'huntervd-client-training-dashboard'),
            [$this, 'render_dashboard_widget']
        );
    }

    public function render_dashboard_widget(): void
    {
        $settings = $this->get_settings();

        echo '<div class="huntervd-training-widget">';

        if (! empty($settings['instruction_heading'])) {
            echo '<h2>' . esc_html($settings['instruction_heading']) . '</h2>';
        }

        if (! empty($settings['instruction_text'])) {
            echo '<p>' . esc_html($settings['instruction_text']) . '</p>';
        }

        $urls = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $settings['videos'])));

        if (! empty($urls)) {
            foreach ($urls as $url) {
                $embed = wp_oembed_get($url, ['width' => 560]);

                if ($embed) {
                    echo '<div style="margin:16px 0;">' . wp_kses_post($embed) . '</div>';
                } else {
                    echo '<p><a href="' . esc_url($url) . '" target="_blank" rel="noopener noreferrer">' . esc_html($url) . '</a></p>';
                }
            }
        } else {
            esc_html_e('No training videos configured yet.', 'huntervd-client-training-dashboard');
        }

        echo '</div>';
    }

    private function is_elementor_page(int $post_id): bool
    {
        if ($post_id <= 0) {
            return false;
        }

        $edit_mode = get_post_meta($post_id, '_elementor_edit_mode', true);
        $data = get_post_meta($post_id, '_elementor_data', true);

        return ! empty($edit_mode) || ! empty($data);
    }

    private function should_protect_elementor_pages(): bool
    {
        $settings = $this->get_settings();

        return (int) $settings['disable_editor_links_for_elementor'] === 1;
    }

    public function filter_edit_post_link($link, $post_id, $context)
    {
        if (! $this->should_protect_elementor_pages()) {
            return $link;
        }

        if ($this->is_elementor_page((int) $post_id) && $context !== 'elementor') {
            return '';
        }

        return $link;
    }

    public function remove_frontend_admin_bar_edit(
        WP_Admin_Bar $admin_bar
    ): void {
        if (! is_admin_bar_showing() || ! is_singular() || ! $this->should_protect_elementor_pages()) {
            return;
        }

        $post_id = get_queried_object_id();
        if ($this->is_elementor_page((int) $post_id)) {
            $admin_bar->remove_node('edit');
        }
    }

    public function enqueue_admin_assets(string $hook_suffix): void
    {
        if (! in_array($hook_suffix, ['post.php', 'post-new.php'], true) || ! $this->should_protect_elementor_pages()) {
            return;
        }

        $screen = get_current_screen();
        if (! $screen || empty($screen->post_type)) {
            return;
        }

        $post_id = isset($_GET['post']) ? (int) $_GET['post'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ($post_id <= 0 || ! $this->is_elementor_page($post_id)) {
            return;
        }

        wp_register_script('huntervd-editor-guard', false, [], '1.0.0', true);
        wp_enqueue_script('huntervd-editor-guard');

        $script = <<<JS
(function() {
    function hideButtons() {
        var selectors = [
            'a', 'button'
        ];

        selectors.forEach(function(selector) {
            document.querySelectorAll(selector).forEach(function(node) {
                var text = (node.textContent || '').trim();
                if (!text) return;

                var isBackButton = text.toLowerCase().indexOf('back to wordpress editor') !== -1;
                var isEditPage = text.toLowerCase() === 'edit page';

                if (isBackButton || isEditPage) {
                    node.style.display = 'none';
                    node.setAttribute('aria-hidden', 'true');
                }
            });
        });

        document.querySelectorAll('#wp-admin-bar-edit').forEach(function(node) {
            node.style.display = 'none';
        });
    }

    hideButtons();
    var observer = new MutationObserver(hideButtons);
    observer.observe(document.body, { childList: true, subtree: true });
})();
JS;
        wp_add_inline_script('huntervd-editor-guard', $script);
    }

    public function inject_github_update($transient)
    {
        if (! is_object($transient)) {
            return $transient;
        }

        $settings = $this->get_settings();

        if ((int) $settings['enable_github_auto_updates'] !== 1 || empty($settings['github_repo'])) {
            return $transient;
        }

        if (! isset($transient->checked[HUNTERVD_CTD_PLUGIN_BASENAME])) {
            return $transient;
        }

        $current_version = $transient->checked[HUNTERVD_CTD_PLUGIN_BASENAME];
        $repo = trim((string) $settings['github_repo']);

        if (! preg_match('/^[A-Za-z0-9_.-]+\/[A-Za-z0-9_.-]+$/', $repo)) {
            return $transient;
        }

        $response = wp_remote_get(
            'https://api.github.com/repos/' . $repo . '/releases/latest',
            [
                'timeout' => 15,
                'headers' => [
                    'Accept' => 'application/vnd.github+json',
                    'User-Agent' => 'WordPress/' . get_bloginfo('version') . '; ' . home_url('/'),
                ],
            ]
        );

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return $transient;
        }

        $body = json_decode((string) wp_remote_retrieve_body($response), true);
        if (! is_array($body) || empty($body['tag_name']) || empty($body['zipball_url'])) {
            return $transient;
        }

        $latest_version = ltrim((string) $body['tag_name'], 'v');

        if (! version_compare($latest_version, $current_version, '>')) {
            return $transient;
        }

        $package_url = add_query_arg(
            [
                'archive' => 'zip',
            ],
            sprintf('https://github.com/%s/archive/refs/tags/%s.zip', $repo, rawurlencode((string) $body['tag_name']))
        );

        $transient->response[HUNTERVD_CTD_PLUGIN_BASENAME] = (object) [
            'slug' => self::PLUGIN_SLUG,
            'plugin' => HUNTERVD_CTD_PLUGIN_BASENAME,
            'new_version' => $latest_version,
            'url' => 'https://github.com/' . $repo,
            'package' => $package_url,
        ];

        return $transient;
    }

    public function plugins_api($result, $action, $args)
    {
        if ($action !== 'plugin_information' || empty($args->slug) || $args->slug !== self::PLUGIN_SLUG) {
            return $result;
        }

        $settings = $this->get_settings();

        return (object) [
            'name' => 'HunterVD Client Training Dashboard',
            'slug' => self::PLUGIN_SLUG,
            'version' => '1.0.0',
            'author' => '<a href="https://huntervdigital.com.au/">HunterVDigital</a>',
            'homepage' => ! empty($settings['github_repo']) ? 'https://github.com/' . $settings['github_repo'] : home_url('/'),
            'sections' => [
                'description' => 'Client training videos and Elementor editor safety controls.',
            ],
        ];
    }

    public function enable_auto_update_for_this_plugin(bool $update, $item): bool
    {
        $settings = $this->get_settings();

        if (empty($item->plugin) || $item->plugin !== HUNTERVD_CTD_PLUGIN_BASENAME) {
            return $update;
        }

        return (int) $settings['enable_github_auto_updates'] === 1;
    }
}

new HunterVD_Client_Training_Dashboard();
