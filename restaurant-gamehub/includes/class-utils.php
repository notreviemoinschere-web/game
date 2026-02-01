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

    public static function generate_claim_code(): string
    {
        $format = self::get_option('claim_code_format', 'RESTO-XXXXXX');
        $token = strtoupper(wp_generate_password(6, false, false));
        return str_replace('XXXXXX', $token, $format);
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

    public static function pick_prize(int $campaign_id, string $type, ?string $variant = null): ?object
    {
        global $wpdb;
        $table = DB::table('prizes');
        if ($variant) {
            $prizes = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM {$table} WHERE campaign_id = %d AND type = %s AND active = 1 AND (variant = %s OR variant IS NULL)",
                    $campaign_id,
                    $type,
                    $variant
                )
            );
        } else {
            $prizes = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM {$table} WHERE campaign_id = %d AND type = %s AND active = 1",
                    $campaign_id,
                    $type
                )
            );
        }
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

    public static function send_webhook(string $event, array $payload): void
    {
        $url = self::get_option('webhook_url', '');
        if (!$url) {
            return;
        }
        $secret = self::get_option('webhook_secret', '');
        $payload['event'] = $event;
        $payload['site_id'] = get_current_blog_id();
        wp_remote_post($url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'X-GameHub-Signature' => hash_hmac('sha256', wp_json_encode($payload), $secret),
            ],
            'body' => wp_json_encode($payload),
            'timeout' => 5,
        ]);
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

    public static function log_audit(string $action, array $context = []): void
    {
        global $wpdb;
        $wpdb->insert(DB::table('audit'), [
            'admin_user_id' => get_current_user_id() ?: null,
            'action' => $action,
            'context' => wp_json_encode($context),
            'created_at' => self::now_mysql(),
        ]);
    }

    public static function purge_old_data(): void
    {
        global $wpdb;
        $months = (int) self::get_option('retention_months', 12);
        if ($months <= 0) {
            return;
        }
        $cutoff = gmdate('Y-m-d H:i:s', strtotime("-{$months} months"));
        $wpdb->query(
            $wpdb->prepare(
                'DELETE FROM ' . DB::table('consents') . ' WHERE created_at < %s',
                $cutoff
            )
        );
        $wpdb->query(
            $wpdb->prepare(
                'DELETE FROM ' . DB::table('leads') . ' WHERE created_at < %s',
                $cutoff
            )
        );
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
