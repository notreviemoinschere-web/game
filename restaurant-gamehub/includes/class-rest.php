<?php
namespace Restaurant\GameHub;

use WP_Error;
use WP_REST_Request;

if (!defined('ABSPATH')) {
    exit;
}

class Rest
{
    public static function register_routes(): void
    {
        register_rest_route('gamehub/v1', '/play', [
            'methods' => 'POST',
            'callback' => [self::class, 'handle_play'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('gamehub/v1', '/lead', [
            'methods' => 'POST',
            'callback' => [self::class, 'handle_lead'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('gamehub/v1', '/validate', [
            'methods' => 'POST',
            'callback' => [self::class, 'handle_validate'],
            'permission_callback' => function () {
                return current_user_can('gamehub_validate') || current_user_can('gamehub_manage');
            },
        ]);
    }

    public static function add_no_cache_headers($response, $handler, $request)
    {
        if (strpos($request->get_route(), '/gamehub/v1/') === false) {
            return $response;
        }
        if (method_exists($response, 'header')) {
            $response->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
            $response->header('Pragma', 'no-cache');
            $response->header('Expires', '0');
        }
        return $response;
    }

    public static function handle_play(WP_REST_Request $request)
    {
        $type = sanitize_text_field($request->get_param('type'));
        $qr_id = sanitize_text_field($request->get_param('qr_id'));
        $user_key = sanitize_text_field($request->get_param('user_key'));
        $device_hash = sanitize_text_field($request->get_param('device_hash'));
        $ip = Utils::get_ip_address();
        $user_agent = Utils::get_user_agent();
        if (!$type) {
            return new WP_Error('missing_type', 'Missing game type', ['status' => 400]);
        }
        if (self::is_rate_limited($ip)) {
            return new WP_Error('rate_limited', 'Too many requests', ['status' => 429]);
        }

        if (!self::can_play($user_key, $device_hash, $qr_id)) {
            return new WP_Error('play_limit', 'Play limit reached', ['status' => 429]);
        }

        $campaign_id = Utils::get_active_campaign_id();
        if (!$campaign_id) {
            return new WP_Error('no_campaign', 'No active campaign', ['status' => 409]);
        }

        $result = 'lose';
        $prize = null;
        $variant = self::get_ab_variant($device_hash);
        $win_prize = Utils::pick_prize($campaign_id, 'win', $variant);
        if ($win_prize) {
            $result = 'win';
            $prize = $win_prize;
        } else {
            $consolation = Utils::pick_prize($campaign_id, 'consolation', $variant);
            if ($consolation) {
                $result = 'consolation';
                $prize = $consolation;
            }
        }

        $play_id = self::record_play($type, $campaign_id, $prize ? (int) $prize->id : null, $result, $qr_id, $user_key, $device_hash, $ip, $user_agent);
        $claim = null;
        if ($prize) {
            $claim = self::create_claim($play_id, (int) $prize->id);
        }

        $response = [
            'result' => $result,
            'prize_id' => $prize ? (int) $prize->id : null,
            'label' => $prize ? $prize->title : 'Merci pour votre participation',
            'claim_code' => $claim['claim_code'] ?? null,
            'claim_token' => $claim['claim_token'] ?? null,
            'expires_at' => $claim['expires_at'] ?? null,
        ];

        Utils::send_webhook('play', [
            'play_id' => $play_id,
            'result' => $result,
            'prize_id' => $response['prize_id'],
        ]);

        return rest_ensure_response($response);
    }

    public static function handle_lead(WP_REST_Request $request)
    {
        global $wpdb;
        $first = sanitize_text_field($request->get_param('first_name'));
        $last = sanitize_text_field($request->get_param('last_name'));
        $email = sanitize_email($request->get_param('email'));
        $phone = sanitize_text_field($request->get_param('phone'));
        $whatsapp = sanitize_text_field($request->get_param('whatsapp'));
        $campaign_id = (int) $request->get_param('campaign_id');
        $game = sanitize_text_field($request->get_param('game'));
        $consents = $request->get_param('consents');
        $ip = Utils::get_ip_address();
        $user_key = self::build_user_key($email, $phone, $whatsapp);

        if (!$first || !$last || (!$email && !$phone && !$whatsapp)) {
            return new WP_Error('missing_fields', 'Required fields missing', ['status' => 400]);
        }

        $wpdb->insert(DB::table('leads'), [
            'first_name' => $first,
            'last_name' => $last,
            'email' => $email,
            'phone' => $phone,
            'whatsapp' => $whatsapp,
            'source_game' => $game,
            'campaign_id' => $campaign_id ?: null,
            'user_key' => $user_key,
            'created_at' => Utils::now_mysql(),
        ]);

        $lead_id = (int) $wpdb->insert_id;
        self::store_consent($lead_id, 'necessary', true, Utils::get_option('consent_text', ''), Utils::get_option('consent_version', 'v1'), 'gamehub', $ip);
        if (is_array($consents)) {
            foreach ($consents as $type => $value) {
                $text = '';
                if ($type === 'newsletter') {
                    $text = Utils::get_option('marketing_email_text', '');
                }
                if ($type === 'whatsapp') {
                    $text = Utils::get_option('marketing_sms_text', '');
                }
                self::store_consent($lead_id, sanitize_text_field($type), (bool) $value, $text, Utils::get_option('consent_version', 'v1'), 'gamehub', $ip);
            }
        }

        Utils::send_webhook('lead', [
            'lead_id' => $lead_id,
            'first_name' => $first,
            'last_name' => $last,
            'email' => $email,
            'phone' => $phone,
            'whatsapp' => $whatsapp,
        ]);

        return rest_ensure_response(['lead_id' => $lead_id]);
    }

    public static function handle_validate(WP_REST_Request $request)
    {
        global $wpdb;
        $code = sanitize_text_field($request->get_param('claim_code'));
        $token = sanitize_text_field($request->get_param('claim_token'));
        if (!$code) {
            return new WP_Error('missing_code', 'Missing code', ['status' => 400]);
        }

        $table = DB::table('claims');
        $claim = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE claim_code = %s", $code));
        if (!$claim) {
            return new WP_Error('invalid_code', 'Invalid code', ['status' => 404]);
        }
        if ($token && !hash_equals($claim->claim_token, $token)) {
            return new WP_Error('invalid_token', 'Invalid token', ['status' => 403]);
        }
        if ($claim->status === 'used') {
            return new WP_Error('already_used', 'Code already used', ['status' => 409]);
        }
        if (strtotime($claim->expires_at) < time()) {
            $wpdb->update($table, ['status' => 'expired'], ['id' => $claim->id]);
            return new WP_Error('expired', 'Code expired', ['status' => 410]);
        }

        $wpdb->update($table, [
            'status' => 'used',
            'used_at' => Utils::now_mysql(),
            'staff_user_id' => get_current_user_id(),
        ], ['id' => $claim->id]);

        return rest_ensure_response(['status' => 'used']);
    }

    private static function record_play(
        string $type,
        int $campaign_id,
        ?int $prize_id,
        string $result,
        string $qr_id,
        ?string $user_key,
        ?string $device_hash,
        ?string $ip,
        ?string $user_agent
    ): int
    {
        global $wpdb;
        $wpdb->insert(DB::table('plays'), [
            'site_id' => get_current_blog_id(),
            'user_id' => get_current_user_id() ?: null,
            'game_type' => $type,
            'campaign_id' => $campaign_id,
            'prize_id' => $prize_id,
            'result' => $result,
            'qr_id' => $qr_id ?: null,
            'user_key' => $user_key ?: null,
            'device_hash' => $device_hash ?: null,
            'ip_address' => $ip ?: null,
            'user_agent' => $user_agent ?: null,
            'created_at' => Utils::now_mysql(),
        ]);

        if ($prize_id) {
            $wpdb->query(
                $wpdb->prepare(
                    "UPDATE " . DB::table('prizes') . " SET remaining = IF(remaining IS NULL, remaining, GREATEST(remaining - 1, 0)) WHERE id = %d",
                    $prize_id
                )
            );
        }

        return (int) $wpdb->insert_id;
    }

    private static function create_claim(int $play_id, int $prize_id): array
    {
        global $wpdb;
        $expires = gmdate('Y-m-d H:i:s', time() + Utils::get_claim_expiry_hours() * HOUR_IN_SECONDS);
        $code = Utils::generate_claim_code();
        $token = Utils::sign_claim_token($code, $play_id);

        $wpdb->insert(DB::table('claims'), [
            'play_id' => $play_id,
            'prize_id' => $prize_id,
            'claim_code' => $code,
            'claim_token' => $token,
            'status' => 'new',
            'expires_at' => $expires,
            'created_at' => Utils::now_mysql(),
        ]);

        return [
            'claim_code' => $code,
            'claim_token' => $token,
            'expires_at' => $expires,
        ];
    }

    private static function store_consent(int $lead_id, string $type, bool $granted, string $text, string $version, string $source, string $ip): void
    {
        global $wpdb;
        $wpdb->insert(DB::table('consents'), [
            'lead_id' => $lead_id,
            'consent_type' => $type,
            'granted' => (int) $granted,
            'consent_text' => $text,
            'consent_version' => $version,
            'source' => $source,
            'ip_address' => $ip,
            'proof' => wp_json_encode(['ip' => $ip]),
            'created_at' => Utils::now_mysql(),
        ]);
    }

    private static function is_rate_limited(string $ip): bool
    {
        if (!$ip) {
            return false;
        }
        $window = max(1, (int) Utils::get_option('rate_limit_window', 10));
        $max = max(5, (int) Utils::get_option('rate_limit_max', 30));
        $key = 'gamehub_rl_' . md5($ip);
        $count = (int) get_transient($key);
        if ($count >= $max) {
            return true;
        }
        set_transient($key, $count + 1, $window * MINUTE_IN_SECONDS);
        return false;
    }

    private static function can_play(?string $user_key, ?string $device_hash, ?string $qr_id): bool
    {
        global $wpdb;
        $hours = max(1, (int) Utils::get_option('play_limit_hours', 24));
        $since = gmdate('Y-m-d H:i:s', time() - $hours * HOUR_IN_SECONDS);
        $table = DB::table('plays');

        $identifier = $user_key ?: $device_hash;
        if ($identifier) {
            $count = (int) $wpdb->get_var(
                $wpdb->prepare(
                    \"SELECT COUNT(*) FROM {$table} WHERE (user_key = %s OR device_hash = %s) AND created_at >= %s\",
                    $user_key ?: '',
                    $device_hash ?: '',
                    $since
                )
            );
            if ($count > 0) {
                return false;
            }
        }

        $limit_qr = (int) Utils::get_option('limit_per_qr_day', 0);
        if ($qr_id && $limit_qr > 0) {
            $day_since = gmdate('Y-m-d H:i:s', strtotime('-1 day'));
            $count = (int) $wpdb->get_var(
                $wpdb->prepare(
                    \"SELECT COUNT(*) FROM {$table} WHERE qr_id = %s AND created_at >= %s\",
                    $qr_id,
                    $day_since
                )
            );
            if ($count >= $limit_qr) {
                return false;
            }
        }

        $limit_hour = (int) Utils::get_option('limit_per_hour', 0);
        if ($limit_hour > 0) {
            $hour_since = gmdate('Y-m-d H:i:s', strtotime('-1 hour'));
            $count = (int) $wpdb->get_var(
                $wpdb->prepare(
                    \"SELECT COUNT(*) FROM {$table} WHERE created_at >= %s\",
                    $hour_since
                )
            );
            if ($count >= $limit_hour) {
                return false;
            }
        }

        $limit_weekdays = Utils::get_option('limit_weekdays', []);
        if (is_array($limit_weekdays) && !empty($limit_weekdays)) {
            $weekday = (int) gmdate('N');
            if (in_array($weekday, array_map('intval', $limit_weekdays), true) === false) {
                return false;
            }
        }

        if ($device_hash && $user_key) {
            $threshold = max(2, (int) Utils::get_option('anti_double_threshold', 4));
            $distinct = (int) $wpdb->get_var(
                $wpdb->prepare(
                    \"SELECT COUNT(DISTINCT user_key) FROM {$table} WHERE device_hash = %s AND created_at >= %s\",
                    $device_hash,
                    gmdate('Y-m-d H:i:s', strtotime('-7 days'))
                )
            );
            if ($distinct >= $threshold) {
                return false;
            }
        }

        return true;
    }

    private static function get_ab_variant(?string $device_hash): ?string
    {
        if (!Utils::get_option('ab_testing', false)) {
            return null;
        }
        $hash = $device_hash ?: uniqid('gh', true);
        return (crc32($hash) % 2 === 0) ? 'A' : 'B';
    }

    private static function build_user_key(string $email, string $phone, string $whatsapp): string
    {
        $raw = $email ?: ($whatsapp ?: $phone);
        return $raw ? hash('sha256', strtolower(trim($raw))) : '';
    }
}
