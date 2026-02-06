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
            'lp_campaigns' => $prefix . 'lp_campaigns',
            'lp_prizes' => $prefix . 'lp_prizes',
            'lp_claims' => $prefix . 'lp_claims',
            'lp_qr_codes' => $prefix . 'lp_qr_codes',
            'lp_consent_log' => $prefix . 'lp_consent_log',
        ];
        return $tables[$key] ?? '';
    }

    public static function saas_table(string $key): string
    {
        global $wpdb;
        $prefix = $wpdb->base_prefix . 'gamehub_saas_';
        $tables = [
            'companies' => $prefix . 'companies',
            'plans' => $prefix . 'plans',
            'subscriptions' => $prefix . 'subscriptions',
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

            "CREATE TABLE " . self::table('lp_campaigns') . " (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                business_id BIGINT UNSIGNED NOT NULL,
                name VARCHAR(190) NOT NULL,
                starts_at DATETIME NULL,
                ends_at DATETIME NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'active',
                created_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                KEY business_id (business_id),
                KEY status (status)
            ) {$charset};",
            "CREATE TABLE " . self::table('lp_prizes') . " (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                campaign_id BIGINT UNSIGNED NOT NULL,
                type VARCHAR(20) NOT NULL,
                title VARCHAR(190) NOT NULL,
                description TEXT NULL,
                image_url TEXT NULL,
                weight DECIMAL(10,4) NOT NULL DEFAULT 1,
                stock_total BIGINT UNSIGNED NULL,
                stock_used BIGINT UNSIGNED NOT NULL DEFAULT 0,
                claim_expiry_hours INT NOT NULL DEFAULT 48,
                active TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                KEY campaign_id (campaign_id),
                KEY type (type)
            ) {$charset};",
            "CREATE TABLE " . self::table('lp_claims') . " (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                play_id BIGINT UNSIGNED NOT NULL,
                prize_id BIGINT UNSIGNED NOT NULL,
                claim_code VARCHAR(64) NOT NULL,
                expires_at DATETIME NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'issued',
                validated_by BIGINT UNSIGNED NULL,
                validated_at DATETIME NULL,
                created_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY claim_code (claim_code),
                KEY status (status)
            ) {$charset};",
            "CREATE TABLE " . self::table('lp_qr_codes') . " (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                business_id BIGINT UNSIGNED NOT NULL,
                game_id BIGINT UNSIGNED NOT NULL,
                label VARCHAR(190) NOT NULL,
                qr_token VARCHAR(128) NOT NULL,
                target_url TEXT NOT NULL,
                created_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY qr_token (qr_token)
            ) {$charset};",
            "CREATE TABLE " . self::table('lp_consent_log') . " (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                lead_id BIGINT UNSIGNED NOT NULL,
                consent_type VARCHAR(50) NOT NULL,
                consent_text_version TEXT NULL,
                ip VARCHAR(64) NULL,
                ua VARCHAR(255) NULL,
                created_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                KEY lead_id (lead_id),
                KEY consent_type (consent_type)
            ) {$charset};",
        ];
    }

    public static function saas_schema(): array
    {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();
        return [
            "CREATE TABLE " . self::saas_table('companies') . " (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                site_id BIGINT UNSIGNED NOT NULL,
                owner_user_id BIGINT UNSIGNED NOT NULL,
                name VARCHAR(190) NOT NULL,
                slug VARCHAR(190) NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'active',
                created_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY site_id (site_id),
                KEY owner_user_id (owner_user_id),
                KEY status (status)
            ) {$charset};",
            "CREATE TABLE " . self::saas_table('plans') . " (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                code VARCHAR(32) NOT NULL,
                name VARCHAR(190) NOT NULL,
                price_monthly DECIMAL(10,2) NOT NULL DEFAULT 0,
                limits_json LONGTEXT NULL,
                active TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY code (code),
                KEY active (active)
            ) {$charset};",
            "CREATE TABLE " . self::saas_table('subscriptions') . " (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                company_id BIGINT UNSIGNED NOT NULL,
                plan_id BIGINT UNSIGNED NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'active',
                started_at DATETIME NOT NULL,
                ended_at DATETIME NULL,
                created_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                KEY company_id (company_id),
                KEY plan_id (plan_id),
                KEY status (status)
            ) {$charset};",
        ];
    }

    public static function ensure_default_plans(): void
    {
        global $wpdb;
        $table = self::saas_table('plans');
        $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
        if ($count > 0) {
            return;
        }
        $plans = [
            ['code' => 'free', 'name' => 'Gratuit', 'price' => 0, 'limits' => ['plays_per_month' => 300, 'games' => 1]],
            ['code' => 'pro', 'name' => 'Pro', 'price' => 49, 'limits' => ['plays_per_month' => 5000, 'games' => 3]],
            ['code' => 'elite', 'name' => 'Elite', 'price' => 149, 'limits' => ['plays_per_month' => -1, 'games' => -1]],
        ];
        foreach ($plans as $plan) {
            $wpdb->insert($table, [
                'code' => $plan['code'],
                'name' => $plan['name'],
                'price_monthly' => $plan['price'],
                'limits_json' => wp_json_encode($plan['limits']),
                'active' => 1,
                'created_at' => Utils::now_mysql(),
            ]);
        }
    }

    public static function create_company(string $name, string $email, int $plan_id = 0): int
    {
        global $wpdb;
        $slug = sanitize_title($name ?: 'entreprise-' . wp_generate_password(6, false, false));
        $password = wp_generate_password(18, true, true);
        $user_id = email_exists($email);
        if (!$user_id) {
            $user_id = wp_create_user($email, $password, $email);
            if (is_wp_error($user_id)) {
                return 0;
            }
        }

        $site_id = get_current_blog_id();
        if (is_multisite()) {
            $domain = parse_url(network_site_url(), PHP_URL_HOST) ?: parse_url(home_url(), PHP_URL_HOST);
            $path = '/' . trim($slug, '/') . '/';
            $new_site_id = wpmu_create_blog($domain, $path, $name, $user_id, [], get_current_network_id());
            if (!is_wp_error($new_site_id)) {
                $site_id = (int) $new_site_id;
            }
        }

        $companies_table = self::saas_table('companies');
        $wpdb->insert($companies_table, [
            'site_id' => $site_id,
            'owner_user_id' => (int) $user_id,
            'name' => sanitize_text_field($name),
            'slug' => $slug,
            'status' => 'active',
            'created_at' => Utils::now_mysql(),
        ]);
        $company_id = (int) $wpdb->insert_id;

        if ($company_id > 0) {
            if (is_multisite() && $site_id) {
                add_user_to_blog($site_id, (int) $user_id, 'loyaltyplay_business');
                switch_to_blog($site_id);
                Activator::create_tables();
                Activator::ensure_front_pages();
                Utils::ensure_game_page();
                Utils::ensure_campaign();
                Utils::ensure_default_prizes();
                restore_current_blog();
            } else {
                $user = get_user_by('id', (int) $user_id);
                if ($user) {
                    $user->set_role('loyaltyplay_business');
                }
                Utils::ensure_game_page();
                Utils::ensure_campaign();
                Utils::ensure_default_prizes();
            }

            if (!$plan_id) {
                $plan_id = (int) $wpdb->get_var($wpdb->prepare("SELECT id FROM " . self::saas_table('plans') . " WHERE code = %s LIMIT 1", 'free'));
            }
            if ($plan_id) {
                self::assign_plan($company_id, $plan_id);
            }
        }

        return $company_id;
    }

    public static function assign_plan(int $company_id, int $plan_id): void
    {
        global $wpdb;
        $subscriptions_table = self::saas_table('subscriptions');
        $wpdb->query($wpdb->prepare("UPDATE {$subscriptions_table} SET status = %s, ended_at = %s WHERE company_id = %d AND status = %s", 'cancelled', Utils::now_mysql(), $company_id, 'active'));
        $wpdb->insert($subscriptions_table, [
            'company_id' => $company_id,
            'plan_id' => $plan_id,
            'status' => 'active',
            'started_at' => Utils::now_mysql(),
            'ended_at' => null,
            'created_at' => Utils::now_mysql(),
        ]);
    }

    public static function delete_company(int $company_id): void
    {
        global $wpdb;
        $company = self::get_company($company_id);
        if (!$company) {
            return;
        }
        if (is_multisite() && !empty($company->site_id)) {
            wpmu_delete_blog((int) $company->site_id, true);
        }
        $wpdb->delete(self::saas_table('subscriptions'), ['company_id' => $company_id]);
        $wpdb->delete(self::saas_table('companies'), ['id' => $company_id]);
    }

    public static function get_company(int $company_id): ?object
    {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM " . self::saas_table('companies') . " WHERE id = %d", $company_id));
    }

    public static function get_company_by_site(int $site_id): ?object
    {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM " . self::saas_table('companies') . " WHERE site_id = %d", $site_id));
    }

    public static function get_company_subscription(int $company_id): ?object
    {
        global $wpdb;
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT s.*, p.name AS plan_name, p.code AS plan_code, p.price_monthly, p.limits_json
                 FROM " . self::saas_table('subscriptions') . " s
                 INNER JOIN " . self::saas_table('plans') . " p ON p.id = s.plan_id
                 WHERE s.company_id = %d AND s.status = %s
                 ORDER BY s.id DESC LIMIT 1",
                $company_id,
                'active'
            )
        );
    }

    public static function get_all_companies(): array
    {
        global $wpdb;
        return $wpdb->get_results(
            "SELECT c.*, p.name AS plan_name, p.code AS plan_code
             FROM " . self::saas_table('companies') . " c
             LEFT JOIN " . self::saas_table('subscriptions') . " s ON s.company_id = c.id AND s.status = 'active'
             LEFT JOIN " . self::saas_table('plans') . " p ON p.id = s.plan_id
             ORDER BY c.id DESC"
        );
    }

    public static function get_all_plans(): array
    {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM " . self::saas_table('plans') . " WHERE active = 1 ORDER BY price_monthly ASC");
    }



    public static function get_company_by_user(int $user_id): ?object
    {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM " . self::saas_table('companies') . " WHERE owner_user_id = %d ORDER BY id DESC LIMIT 1", $user_id));
    }

    public static function is_site_suspended(int $site_id): bool
    {
        global $wpdb;
        $status = $wpdb->get_var($wpdb->prepare("SELECT status FROM " . self::saas_table('companies') . " WHERE site_id = %d LIMIT 1", $site_id));
        return $status === 'suspended' || $status === 'unpaid';
    }

    public static function update_company_status(int $company_id, string $status): void
    {
        global $wpdb;
        $allowed = ['active', 'suspended', 'unpaid', 'trial'];
        if (!in_array($status, $allowed, true)) {
            return;
        }
        $wpdb->update(self::saas_table('companies'), ['status' => $status], ['id' => $company_id]);
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

    public static function get_network_stats(): array
    {
        global $wpdb;
        $companies = (int) $wpdb->get_var("SELECT COUNT(*) FROM " . self::saas_table('companies'));
        $subscriptions = (int) $wpdb->get_var("SELECT COUNT(*) FROM " . self::saas_table('subscriptions') . " WHERE status = 'active'");
        $plays = 0;
        if (is_multisite()) {
            $sites = get_sites(['number' => 0, 'fields' => 'ids']);
            foreach ($sites as $site_id) {
                $table = $wpdb->get_blog_prefix((int) $site_id) . 'gamehub_plays';
                $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
                if ($exists === $table) {
                    $plays += (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
                }
            }
        } else {
            $plays = (int) $wpdb->get_var("SELECT COUNT(*) FROM " . self::table('plays'));
        }

        return [
            'companies' => $companies,
            'active_subscriptions' => $subscriptions,
            'plays_total' => $plays,
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
