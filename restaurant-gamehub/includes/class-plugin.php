<?php
namespace Restaurant\GameHub;

if (!defined('ABSPATH')) {
    exit;
}

class Plugin
{
    private static ?Plugin $instance = null;

    public static function instance(): Plugin
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        register_activation_hook(dirname(__DIR__) . '/restaurant-gamehub.php', [Activator::class, 'activate']);
        add_action('init', [$this, 'register_assets']);
        add_action('init', [Activator::class, 'add_roles']);
        add_action('init', [Shortcodes::class, 'register']);
        add_action('rest_api_init', [Rest::class, 'register_routes']);
        add_action('admin_menu', [Admin::class, 'register_menu']);
        add_action('admin_post_gamehub_save_settings', [Admin::class, 'handle_save_settings']);
        add_action('admin_post_gamehub_save_campaign', [Admin::class, 'handle_save_campaign']);
        add_action('admin_post_gamehub_save_prize', [Admin::class, 'handle_save_prize']);
        add_action('admin_post_gamehub_save_qr', [Admin::class, 'handle_save_qr']);
        add_action('admin_post_gamehub_export_leads', [Admin::class, 'handle_export_leads']);
        add_action('admin_post_gamehub_import_leads', [Admin::class, 'handle_import_leads']);
        add_action('admin_post_gamehub_export_stats', [Admin::class, 'handle_export_stats']);
        add_action('admin_post_gamehub_validate_code', [Admin::class, 'handle_validate_code']);
        add_action('admin_post_gamehub_privacy_export', [Admin::class, 'handle_privacy_export']);
        add_action('admin_post_gamehub_privacy_delete', [Admin::class, 'handle_privacy_delete']);
        add_action('admin_post_gamehub_config_copy', [Admin::class, 'handle_config_copy']);
        add_action('gamehub_expire_claims', [Utils::class, 'expire_claims']);
        add_action('gamehub_purge_data', [Utils::class, 'purge_old_data']);
        add_filter('rest_post_dispatch', [Rest::class, 'add_no_cache_headers'], 10, 3);
    }

    public function register_assets(): void
    {
        $url = plugin_dir_url(dirname(__DIR__) . '/restaurant-gamehub.php');
        $css_version = filemtime(dirname(__DIR__) . '/assets/css/gamehub.css');
        $js_version = filemtime(dirname(__DIR__) . '/assets/js/gamehub.js');
        $admin_css_version = filemtime(dirname(__DIR__) . '/admin/css/admin.css');
        $admin_js_version = filemtime(dirname(__DIR__) . '/admin/js/admin.js');

        wp_register_style('gamehub-frontend', $url . 'assets/css/gamehub.css', [], $css_version);
        wp_register_script('gamehub-frontend', $url . 'assets/js/gamehub.js', ['wp-api'], $js_version, true);

        wp_register_style('gamehub-admin', $url . 'admin/css/admin.css', [], $admin_css_version);
        wp_register_script('gamehub-admin', $url . 'admin/js/admin.js', ['jquery'], $admin_js_version, true);
    }
}
