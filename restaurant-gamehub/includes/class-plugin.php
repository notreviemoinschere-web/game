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
        add_action('admin_init', [Utils::class, 'maybe_block_admin_access']);
        add_action('rest_api_init', [Rest::class, 'register_routes']);
        add_action('admin_menu', [Admin::class, 'register_menu']);
        add_action('admin_enqueue_scripts', [Admin::class, 'enqueue_assets']);

        add_action('admin_post_gamehub_activate_now', [Admin::class, 'handle_activate_now']);
        add_action('admin_post_gamehub_save_settings', [Admin::class, 'handle_save_settings']);
        add_action('admin_post_gamehub_save_prize_simple', [Admin::class, 'handle_save_prize_simple']);
        add_action('admin_post_gamehub_prize_toggle', [Admin::class, 'handle_prize_toggle']);
        add_action('admin_post_gamehub_validate_check', [Admin::class, 'handle_validate_check']);
        add_action('admin_post_gamehub_validate_use', [Admin::class, 'handle_validate_use']);

        add_action('admin_post_gamehub_create_company', [Admin::class, 'handle_create_company']);
        add_action('admin_post_gamehub_delete_company', [Admin::class, 'handle_delete_company']);
        add_action('admin_post_gamehub_assign_plan', [Admin::class, 'handle_assign_plan']);
        add_action('admin_post_gamehub_company_status', [Admin::class, 'handle_company_status']);
        add_action('admin_post_gamehub_save_plan', [Admin::class, 'handle_save_plan']);
        add_action('admin_post_gamehub_upgrade_plan', [Admin::class, 'handle_upgrade_plan']);

        add_action('gamehub_expire_claims', [Utils::class, 'expire_claims']);
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
        wp_register_script('gamehub-admin', $url . 'admin/js/admin.js', [], $admin_js_version, true);
    }
}
