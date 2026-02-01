<?php
namespace Restaurant\GameHub;

if (!defined('ABSPATH')) {
    exit;
}

class Activator
{
    public static function activate(bool $network_wide): void
    {
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
                'primary_color' => '#ff6a3d',
                'secondary_color' => '#101828',
                'accent_color' => '#7f56d9',
                'font_family' => 'System',
                'dark_mode' => false,
                'webhook_url' => '',
                'webhook_secret' => '',
                'hmac_secret' => wp_generate_password(32, true, true),
                'consent_version' => 'v1',
                'consent_text' => 'En jouant, vous acceptez le traitement nécessaire pour délivrer votre gain.',
                'marketing_email_text' => 'J’accepte de recevoir des communications marketing par email.',
                'marketing_sms_text' => 'J’accepte de recevoir des communications marketing par WhatsApp/SMS.',
                'retention_months' => 12,
                'language' => 'fr',
                'rate_limit_window' => 10,
                'rate_limit_max' => 30,
                'play_limit_hours' => 24,
                'ab_testing' => false,
                'limit_per_qr_day' => 0,
                'limit_per_hour' => 0,
                'limit_weekdays' => [],
                'anti_double_threshold' => 4,
                'qr_service_url' => 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={data}',
            ]);
        }
    }

    public static function schedule_cron(): void
    {
        if (!wp_next_scheduled('gamehub_expire_claims')) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'hourly', 'gamehub_expire_claims');
        }
        if (!wp_next_scheduled('gamehub_purge_data')) {
            wp_schedule_event(time() + DAY_IN_SECONDS, 'daily', 'gamehub_purge_data');
        }
    }

    public static function add_roles(): void
    {
        $admin = get_role('administrator');
        if ($admin) {
            $admin->add_cap('gamehub_manage');
            $admin->add_cap('gamehub_validate');
            $admin->add_cap('gamehub_export');
        }
        add_role('gamehub_staff', 'GameHub Staff', [
            'read' => true,
            'gamehub_validate' => true,
        ]);
    }
}
