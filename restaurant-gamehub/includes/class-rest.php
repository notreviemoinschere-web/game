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
                return current_user_can('gamehub_validate') || current_user_can('manage_options');
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
        $allowed = ['roulette', 'scratch', 'quiz'];
        if (!in_array($type, $allowed, true)) {
            return new WP_Error('invalid_type', 'Invalid game type', ['status' => 400]);
        }
        $qr_id = sanitize_text_field($request->get_param('qr_id'));
        $user_key = sanitize_text_field($request->get_param('user_key'));
        $device_hash = sanitize_text_field($request->get_param('device_hash'));

        if (!self::can_play($user_key, $device_hash)) {
            return new WP_Error('play_limit', 'Play limit reached', ['status' => 429]);
        }

        $campaign_id = Utils::get_active_campaign_id();
        if (!$campaign_id) {
            return new WP_Error('no_campaign', 'No active campaign', ['status' => 409]);
        }

        $result = 'lose';
        $prize = Utils::pick_prize($campaign_id, 'win');
        if ($prize) {
            $result = 'win';
        } else {
            $prize = Utils::pick_prize($campaign_id, 'consolation');
            if ($prize) {
                $result = 'consolation';
            }
        }

        $play_id = self::record_play($type, $campaign_id, $prize ? (int) $prize->id : null, $result, $qr_id, $user_key, $device_hash);
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

        return rest_ensure_response($response);
    }

    public static function handle_lead(WP_REST_Request $request)
    {
        global $wpdb;
        $first = sanitize_text_field($request->get_param('first_name'));
        $last = sanitize_text_field($request->get_param('last_name'));
        $email = sanitize_email($request->get_param('email'));
        $phone = sanitize_text_field($request->get_param('phone'));
        $campaign_id = (int) $request->get_param('campaign_id');
        $game = sanitize_text_field($request->get_param('game'));

        if (!$first || !$last || (!$email && !$phone)) {
            return new WP_Error('missing_fields', 'Required fields missing', ['status' => 400]);
        }

        $wpdb->insert(DB::table('leads'), [
            'first_name' => $first,
            'last_name' => $last,
            'email' => $email,
            'phone' => $phone,
            'whatsapp' => '',
            'source_game' => $game,
            'campaign_id' => $campaign_id ?: null,
            'user_key' => $email ? hash('sha256', strtolower(trim($email))) : '',
            'created_at' => Utils::now_mysql(),
        ]);

        return rest_ensure_response(['lead_id' => (int) $wpdb->insert_id]);
    }

    public static function handle_validate(WP_REST_Request $request)
    {
        $code = sanitize_text_field($request->get_param('claim_code'));
        $token = sanitize_text_field($request->get_param('claim_token'));
        if (!$code) {
            return new WP_Error('missing_code', 'Missing code', ['status' => 400]);
        }
        $claim = DB::get_claim_by_code($code);
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
            return new WP_Error('expired', 'Code expired', ['status' => 410]);
        }
        DB::mark_claim_used($code, get_current_user_id());
        return rest_ensure_response(['status' => 'used']);
    }

    private static function record_play(string $type, int $campaign_id, ?int $prize_id, string $result, string $qr_id, string $user_key, string $device_hash): int
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
            'ip_address' => Utils::get_ip_address(),
            'user_agent' => Utils::get_user_agent(),
            'created_at' => Utils::now_mysql(),
        ]);

        if ($prize_id && !Utils::is_test_mode()) {
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
        $prize = $wpdb->get_row($wpdb->prepare(\"SELECT expiry_days FROM \" . DB::table('prizes') . \" WHERE id = %d\", $prize_id));
        $hours = $prize && $prize->expiry_days ? ((int) $prize->expiry_days * 24) : Utils::get_claim_expiry_hours();
        $expires = gmdate('Y-m-d H:i:s', time() + $hours * HOUR_IN_SECONDS);
        $code = Utils::generate_claim_code(Utils::is_test_mode());
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

    private static function can_play(string $user_key, string $device_hash): bool
    {
        global $wpdb;
        $hours = (int) Utils::get_option('play_limit_hours', 24);
        if ($hours <= 0) {
            return true;
        }
        $since = gmdate('Y-m-d H:i:s', time() - $hours * HOUR_IN_SECONDS);
        $table = DB::table('plays');
        if ($user_key) {
            $count = (int) $wpdb->get_var(
                $wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE user_key = %s AND created_at >= %s", $user_key, $since)
            );
            if ($count > 0) {
                return false;
            }
        }
        if ($device_hash) {
            $count = (int) $wpdb->get_var(
                $wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE device_hash = %s AND created_at >= %s", $device_hash, $since)
            );
            if ($count > 0) {
                return false;
            }
        }
        return true;
    }
}
