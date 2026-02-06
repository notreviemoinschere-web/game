<?php
namespace Restaurant\GameHub;

if (!defined('ABSPATH')) {
    exit;
}

class Activator
{
    public static function activate(bool $network_wide): void
    {
        self::create_saas_tables();
        DB::ensure_default_plans();
        if (is_multisite() && $network_wide) {
            $sites = get_sites(['number' => 0]);
            foreach ($sites as $site) {
                switch_to_blog((int) $site->blog_id);
                self::create_tables();
                self::schedule_cron();
                self::add_roles();
                restore_current_blog();
            }
            return;
        }
        self::create_tables();
        self::schedule_cron();
        self::add_roles();
    }

    public static function create_saas_tables(): void
    {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        foreach (DB::saas_schema() as $sql) {
            dbDelta($sql);
        }
    }

    public static function create_tables(): void
    {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        foreach (DB::schema() as $sql) {
            dbDelta($sql);
        }
        if (!get_option('gamehub_settings')) {
            update_option('gamehub_settings', [
                'claim_code_format' => 'RESTO-XXXXXX',
                'claim_expiry_hours' => 48,
                'play_limit_hours' => 24,
                'active_game' => 'roulette',
                'notification_email' => '',
                'test_mode' => false,
                'hmac_secret' => wp_generate_password(32, true, true),
            ]);
        }
    }

    public static function schedule_cron(): void
    {
        if (!wp_next_scheduled('gamehub_expire_claims')) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'hourly', 'gamehub_expire_claims');
        }
    }

    public static function add_roles(): void
    {
        $admin = get_role('administrator');
        if ($admin) {
            $admin->add_cap('gamehub_manage');
            $admin->add_cap('gamehub_validate');
        }
        $manager = get_role('shop_manager');
        if ($manager) {
            $manager->add_cap('gamehub_validate');
        }
    }
}
