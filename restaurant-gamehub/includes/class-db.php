<?php
namespace Restaurant\GameHub;

if (!defined('ABSPATH')) {
    exit;
}

class DB
{
    public static function table(string $key): string
    {
        global $wpdb;
        $prefix = $wpdb->prefix . 'gamehub_';
        $tables = [
            'plays' => $prefix . 'plays',
            'prizes' => $prefix . 'prizes',
            'campaigns' => $prefix . 'campaigns',
            'claims' => $prefix . 'claims',
            'leads' => $prefix . 'leads',
            'consents' => $prefix . 'consents',
            'qr_codes' => $prefix . 'qr_codes',
        ];
        return $tables[$key] ?? '';
    }

    public static function schema(): array
    {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();
        return [
            "CREATE TABLE " . self::table('plays') . " (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                site_id BIGINT UNSIGNED NOT NULL,
                user_id BIGINT UNSIGNED NULL,
                game_type VARCHAR(32) NOT NULL,
                campaign_id BIGINT UNSIGNED NULL,
                prize_id BIGINT UNSIGNED NULL,
                result VARCHAR(32) NOT NULL,
                qr_id VARCHAR(64) NULL,
                lead_id BIGINT UNSIGNED NULL,
                user_key VARCHAR(190) NULL,
                device_hash VARCHAR(128) NULL,
                ip_address VARCHAR(64) NULL,
                user_agent VARCHAR(255) NULL,
                created_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                KEY game_type (game_type),
                KEY campaign_id (campaign_id),
                KEY prize_id (prize_id),
                KEY qr_id (qr_id)
            ) {$charset};",
            "CREATE TABLE " . self::table('campaigns') . " (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                name VARCHAR(190) NOT NULL,
                start_date DATETIME NOT NULL,
                end_date DATETIME NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'inactive',
                created_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                KEY status (status)
            ) {$charset};",
            "CREATE TABLE " . self::table('prizes') . " (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                campaign_id BIGINT UNSIGNED NOT NULL,
                title VARCHAR(190) NOT NULL,
                description TEXT NULL,
                type VARCHAR(20) NOT NULL,
                image_id BIGINT UNSIGNED NULL,
                value VARCHAR(100) NULL,
                weight DECIMAL(10,4) NOT NULL DEFAULT 1,
                stock BIGINT UNSIGNED NULL,
                remaining BIGINT UNSIGNED NULL,
                active TINYINT(1) NOT NULL DEFAULT 1,
                expiry_days INT NULL,
                created_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                KEY campaign_id (campaign_id),
                KEY type (type)
            ) {$charset};",
            "CREATE TABLE " . self::table('claims') . " (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                play_id BIGINT UNSIGNED NOT NULL,
                prize_id BIGINT UNSIGNED NOT NULL,
                claim_code VARCHAR(64) NOT NULL,
                claim_token VARCHAR(128) NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'new',
                expires_at DATETIME NOT NULL,
                used_at DATETIME NULL,
                staff_user_id BIGINT UNSIGNED NULL,
                created_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY claim_code (claim_code),
                KEY status (status)
            ) {$charset};",
            "CREATE TABLE " . self::table('leads') . " (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                first_name VARCHAR(120) NOT NULL,
                last_name VARCHAR(120) NOT NULL,
                email VARCHAR(190) NULL,
                phone VARCHAR(50) NULL,
                whatsapp VARCHAR(50) NULL,
                source_game VARCHAR(32) NULL,
                campaign_id BIGINT UNSIGNED NULL,
                user_key VARCHAR(190) NULL,
                created_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                KEY campaign_id (campaign_id)
            ) {$charset};",
            "CREATE TABLE " . self::table('consents') . " (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                lead_id BIGINT UNSIGNED NOT NULL,
                consent_type VARCHAR(50) NOT NULL,
                granted TINYINT(1) NOT NULL DEFAULT 0,
                consent_text TEXT NULL,
                consent_version VARCHAR(50) NULL,
                source VARCHAR(50) NULL,
                ip_address VARCHAR(64) NULL,
                proof TEXT NULL,
                created_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                KEY lead_id (lead_id)
            ) {$charset};",
            "CREATE TABLE " . self::table('qr_codes') . " (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                label VARCHAR(190) NOT NULL,
                game_type VARCHAR(32) NULL,
                target_url TEXT NOT NULL,
                qr_id VARCHAR(64) NOT NULL,
                created_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY qr_id (qr_id)
            ) {$charset};",
        ];
    }

    public static function get_stats(): array
    {
        global $wpdb;
        $plays_table = self::table('plays');
        $claims_table = self::table('claims');
        $leads_table = self::table('leads');
        $today = gmdate('Y-m-d');
        return [
            'today' => (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$plays_table} WHERE DATE(created_at) = %s", $today)),
            'total' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$plays_table}"),
            'wins' => (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$plays_table} WHERE result = %s", 'win')),
            'consolation' => (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$plays_table} WHERE result = %s", 'consolation')),
            'leads' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$leads_table}"),
            'claimed' => (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$claims_table} WHERE status = %s", 'used')),
        ];
    }

    public static function get_prizes_by_type(string $type): array
    {
        global $wpdb;
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM " . self::table('prizes') . " WHERE type = %s ORDER BY created_at DESC",
                $type
            )
        );
    }

    public static function save_simple_prize(int $prize_id, array $data): int
    {
        global $wpdb;
        $campaign_id = Utils::get_active_campaign_id() ?: Utils::ensure_campaign();
        $payload = [
            'campaign_id' => $campaign_id,
            'title' => $data['title'],
            'type' => $data['type'],
            'weight' => $data['weight'],
            'stock' => $data['stock'],
            'remaining' => $data['stock'],
            'expiry_days' => $data['expiry_days'] ?: null,
            'active' => 1,
            'created_at' => Utils::now_mysql(),
        ];
        if ($prize_id) {
            $wpdb->update(self::table('prizes'), $payload, ['id' => $prize_id]);
            return $prize_id;
        }
        $wpdb->insert(self::table('prizes'), $payload);
        return (int) $wpdb->insert_id;
    }

    public static function toggle_prize(int $prize_id): void
    {
        global $wpdb;
        $prize = $wpdb->get_row($wpdb->prepare("SELECT active FROM " . self::table('prizes') . " WHERE id = %d", $prize_id));
        if (!$prize) {
            return;
        }
        $wpdb->update(self::table('prizes'), ['active' => $prize->active ? 0 : 1], ['id' => $prize_id]);
    }

    public static function get_claim_by_code(string $code): ?object
    {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM " . self::table('claims') . " WHERE claim_code = %s", $code));
    }

    public static function mark_claim_used(string $code, int $user_id): void
    {
        global $wpdb;
        $wpdb->update(self::table('claims'), [
            'status' => 'used',
            'used_at' => Utils::now_mysql(),
            'staff_user_id' => $user_id,
        ], ['claim_code' => $code]);
    }

    public static function get_prize_title(int $prize_id): string
    {
        global $wpdb;
        $title = $wpdb->get_var($wpdb->prepare("SELECT title FROM " . self::table('prizes') . " WHERE id = %d", $prize_id));
        return $title ?: '-';
    }

    public static function get_prize_by_id(int $prize_id): ?object
    {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM " . self::table('prizes') . " WHERE id = %d", $prize_id));
    }
}
