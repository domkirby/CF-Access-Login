<?php
namespace CF_Access_Login;
class GitHub_Updater {
    private $plugin_slug;
    private $plugin_file;

    // Define constants for the GitHub repository details
    const GITHUB_OWNER = 'domkirby'; // Replace with your GitHub username or organization
    const GITHUB_REPO = 'CF-Access-Login'; // Replace with your GitHub repository name

    public function __construct($plugin_file) {
        $this->plugin_slug = plugin_basename($plugin_file);
        $this->plugin_file = $plugin_file;

        add_filter('pre_set_site_transient_update_plugins', [$this, 'check_for_update']);
        add_filter('plugins_api', [$this, 'plugins_api_handler'], 10, 3);
    }

    public function check_for_update($transient) {
        // Don't run on non-update screens
        if (empty($transient->checked)) {
            return $transient;
        }

        // Get plugin version from GitHub releases
        $latest_release = $this->get_latest_release();
        if (!$latest_release || !isset($latest_release->tag_name)) {
            return $transient;
        }

        $remote_version = $latest_release->tag_name;
        $plugin_data = get_plugin_data($this->plugin_file);
        $current_version = $plugin_data['Version'];

        if (version_compare($current_version, $remote_version, '<')) {
            $update = (object)[
                'slug'        => dirname($this->plugin_slug),
                'new_version' => $remote_version,
                'package'     => $latest_release->zipball_url,
                'url'         => $latest_release->html_url,
            ];

            $transient->response[$this->plugin_slug] = $update;
        }

        return $transient;
    }

    public function plugins_api_handler($result, $action, $args) {
        if ($action !== 'plugin_information' || $args->slug !== dirname($this->plugin_slug)) {
            return $result;
        }

        $latest_release = $this->get_latest_release();
        if (!$latest_release || !isset($latest_release->tag_name)) {
            return $result;
        }

        $result = (object)[
            'name'          => self::GITHUB_REPO,
            'slug'          => dirname($this->plugin_slug),
            'version'       => $latest_release->tag_name,
            'download_link' => $latest_release->zipball_url,
            'sections'      => [
                'description' => 'This plugin is updated via GitHub releases.',
                'changelog'   => isset($latest_release->body) ? $latest_release->body : 'No changelog available.',
            ],
        ];

        return $result;
    }

    private function get_latest_release() {
        $url = "https://api.github.com/repos/" . self::GITHUB_OWNER . "/" . self::GITHUB_REPO . "/releases/latest";

        // Use WordPress HTTP API
        $response = wp_remote_get($url, [
            'headers' => ['User-Agent' => 'WordPress/' . get_bloginfo('version')],
        ]);

        if (is_wp_error($response)) {
            return false;
        }

        $data = wp_remote_retrieve_body($response);
        return json_decode($data);
    }
}
