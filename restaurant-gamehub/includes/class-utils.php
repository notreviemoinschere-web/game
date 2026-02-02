<?php
namespace Restaurant\GameHub;

if (!defined('ABSPATH')) {
    exit;
}

class Utils
{
    public static function get_option(string $key, $default = null)
    {
        $options = get_option('gamehub_settings', []);
        return $options[$key] ?? $default;
    }

    public static function update_option(string $key, $value): void
    {
        $options = get_option('gamehub_settings', []);
        $options[$key] = $value;
        update_option('gamehub_settings', $options);
    }

    public static function now_mysql(): string
    {
        return gmdate('Y-m-d H:i:s');
    }

    public static function generate_claim_code(bool $test_mode = false): string
    {
        $format = $test_mode ? 'TEST-XXXX' : self::get_option('claim_code_format', 'RESTO-XXXXXX');
        $token = strtoupper(wp_generate_password($test_mode ? 4 : 6, false, false));
        return str_replace('XXXXXX', $token, str_replace('XXXX', $token, $format));
    }

    public static function get_hmac_secret(): string
    {
        $secret = self::get_option('hmac_secret', '');
        if (!$secret) {
            $secret = wp_generate_password(32, true, true);
            self::update_option('hmac_secret', $secret);
        }
        return $secret;
    }

    public static function sign_claim_token(string $claim_code, int $play_id): string
    {
        return hash_hmac('sha256', $claim_code . '|' . $play_id, self::get_hmac_secret());
    }

    public static function get_claim_expiry_hours(): int
    {
        return (int) self::get_option('claim_expiry_hours', 48);
    }

    public static function get_active_campaign_id(): ?int
    {
        global $wpdb;
        $table = DB::table('campaigns');
        $now = self::now_mysql();
        $campaign = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id FROM {$table} WHERE status = %s AND start_date <= %s AND (end_date IS NULL OR end_date >= %s) ORDER BY start_date DESC LIMIT 1",
                'active',
                $now,
                $now
            )
        );
        return $campaign ? (int) $campaign->id : null;
    }

    public static function ensure_campaign(): int
    {
        $campaign_id = self::get_active_campaign_id();
        if ($campaign_id) {
            return $campaign_id;
        }
        global $wpdb;
        $wpdb->insert(DB::table('campaigns'), [
            'name' => 'Campagne principale',
            'start_date' => self::now_mysql(),
            'end_date' => null,
            'status' => 'active',
            'created_at' => self::now_mysql(),
        ]);
        return (int) $wpdb->insert_id;
    }

    public static function ensure_default_prizes(): void
    {
        global $wpdb;
        $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM " . DB::table('prizes'));
        if ($count > 0) {
            return;
        }
        $campaign_id = self::ensure_campaign();
        $defaults = [
            ['title' => 'Boisson offerte', 'type' => 'win', 'weight' => 30, 'stock' => 50],
            ['title' => '-10% sur l\'addition', 'type' => 'win', 'weight' => 20, 'stock' => 100],
            ['title' => 'Dessert offert', 'type' => 'win', 'weight' => 10, 'stock' => 30],
            ['title' => 'Rejouez demain', 'type' => 'consolation', 'weight' => 30, 'stock' => null],
            ['title' => 'Merci !', 'type' => 'consolation', 'weight' => 10, 'stock' => null],
        ];
        foreach ($defaults as $item) {
            $wpdb->insert(DB::table('prizes'), [
                'campaign_id' => $campaign_id,
                'title' => $item['title'],
                'type' => $item['type'],
                'weight' => $item['weight'],
                'stock' => $item['stock'],
                'remaining' => $item['stock'],
                'active' => 1,
                'created_at' => self::now_mysql(),
            ]);
        }
    }

    public static function ensure_game_page(): int
    {
        $page_id = (int) self::get_option('gamehub_page_id', 0);
        if ($page_id && get_post_status($page_id)) {
            return $page_id;
        }
        $page = get_page_by_path('gamehub-play');
        if ($page) {
            self::update_option('gamehub_page_id', $page->ID);
            return (int) $page->ID;
        }
        $page_id = wp_insert_post([
            'post_title' => 'Jeux & Cadeaux',
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_name' => 'gamehub-play',
            'post_content' => '[gamehub_play]'
        ]);
        self::update_option('gamehub_page_id', $page_id);
        return (int) $page_id;
    }

    public static function get_public_game_url(): string
    {
        $page_id = self::ensure_game_page();
        return get_permalink($page_id);
    }

    public static function get_qr_id(): string
    {
        $qr_id = self::get_option('qr_id', '');
        if ($qr_id) {
            return $qr_id;
        }
        $qr_id = wp_generate_uuid4();
        self::update_option('qr_id', $qr_id);
        return $qr_id;
    }

    public static function pick_prize(int $campaign_id, string $type): ?object
    {
        global $wpdb;
        $table = DB::table('prizes');
        $prizes = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE campaign_id = %d AND type = %s AND active = 1",
                $campaign_id,
                $type
            )
        );
        if (!$prizes) {
            return null;
        }
        $weighted = [];
        $total = 0;
        foreach ($prizes as $prize) {
            if (!is_null($prize->stock) && (int) $prize->remaining <= 0) {
                continue;
            }
            $weight = max(0.01, (float) $prize->weight);
            $total += $weight;
            $weighted[] = ['prize' => $prize, 'weight' => $weight];
        }
        if ($total <= 0) {
            return null;
        }
        $rand = mt_rand() / mt_getrandmax() * $total;
        $running = 0;
        foreach ($weighted as $item) {
            $running += $item['weight'];
            if ($rand <= $running) {
                return $item['prize'];
            }
        }
        return $weighted[0]['prize'] ?? null;
    }

    public static function get_ip_address(): string
    {
        $ip = '';
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($parts[0]);
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return sanitize_text_field($ip);
    }

    public static function get_user_agent(): string
    {
        return isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '';
    }

    public static function is_test_mode(): bool
    {
        return (bool) self::get_option('test_mode', false);
    }

    public static function expire_claims(): void
    {
        global $wpdb;
        $table = DB::table('claims');
        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$table} SET status = %s WHERE status = %s AND expires_at < %s",
                'expired',
                'new',
                self::now_mysql()
            )
        );
    }
}
