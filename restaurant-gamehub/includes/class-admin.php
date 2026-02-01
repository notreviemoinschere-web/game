<?php
namespace Restaurant\GameHub;

if (!defined('ABSPATH')) {
    exit;
}

class Admin
{
    public static function register_menu(): void
    {
        add_menu_page(
            'GameHub',
            'GameHub',
            'gamehub_manage',
            'gamehub-settings',
            [self::class, 'render_settings'],
            'dashicons-games',
            58
        );
        add_submenu_page('gamehub-settings', 'Campaigns', 'Campaigns', 'gamehub_manage', 'gamehub-campaigns', [self::class, 'render_campaigns']);
        add_submenu_page('gamehub-settings', 'Prizes', 'Prizes', 'gamehub_manage', 'gamehub-prizes', [self::class, 'render_prizes']);
        add_submenu_page('gamehub-settings', 'Actions', 'Actions', 'gamehub_manage', 'gamehub-actions', [self::class, 'render_actions']);
        add_submenu_page('gamehub-settings', 'QR Codes', 'QR Codes', 'gamehub_manage', 'gamehub-qr', [self::class, 'render_qr']);
        add_submenu_page('gamehub-settings', 'Leads', 'Leads', 'gamehub_manage', 'gamehub-leads', [self::class, 'render_leads']);
        add_submenu_page('gamehub-settings', 'Stats', 'Stats', 'gamehub_manage', 'gamehub-stats', [self::class, 'render_stats']);
        add_submenu_page('gamehub-settings', 'Staff Validation', 'Staff Validation', 'gamehub_validate', 'gamehub-validate', [self::class, 'render_validate']);
        add_submenu_page('gamehub-settings', 'Export/Import', 'Export/Import', 'gamehub_export', 'gamehub-export-import', [self::class, 'render_export_import']);
        add_submenu_page('gamehub-settings', 'Data Privacy', 'Data Privacy', 'gamehub_manage', 'gamehub-privacy', [self::class, 'render_privacy']);
        add_submenu_page('gamehub-settings', 'Config Copy', 'Config Copy', 'gamehub_manage', 'gamehub-config-copy', [self::class, 'render_config_copy']);
        add_submenu_page('gamehub-settings', 'Audit Logs', 'Audit Logs', 'gamehub_manage', 'gamehub-audit', [self::class, 'render_audit']);
    }

    public static function render_settings(): void
    {
        wp_enqueue_style('gamehub-admin');
        $settings = get_option('gamehub_settings', []);
        ?>
        <div class="wrap">
            <h1>GameHub Settings</h1>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('gamehub_save_settings'); ?>
                <input type="hidden" name="action" value="gamehub_save_settings">
                <table class="form-table">
                    <tr>
                        <th scope="row">Claim code format</th>
                        <td><input type="text" name="claim_code_format" value="<?php echo esc_attr($settings['claim_code_format'] ?? 'RESTO-XXXXXX'); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row">Claim expiry (hours)</th>
                        <td><input type="number" name="claim_expiry_hours" value="<?php echo esc_attr($settings['claim_expiry_hours'] ?? 48); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row">Primary color</th>
                        <td><input type="text" name="primary_color" value="<?php echo esc_attr($settings['primary_color'] ?? '#ff6a3d'); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row">Secondary color</th>
                        <td><input type="text" name="secondary_color" value="<?php echo esc_attr($settings['secondary_color'] ?? '#101828'); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row">Accent color</th>
                        <td><input type="text" name="accent_color" value="<?php echo esc_attr($settings['accent_color'] ?? '#7f56d9'); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row">Font family</th>
                        <td><input type="text" name="font_family" value="<?php echo esc_attr($settings['font_family'] ?? 'System'); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row">Dark mode</th>
                        <td><input type="checkbox" name="dark_mode" value="1" <?php checked(!empty($settings['dark_mode'])); ?>></td>
                    </tr>
                    <tr>
                        <th scope="row">Language</th>
                        <td>
                            <select name="language">
                                <option value="fr" <?php selected($settings['language'] ?? 'fr', 'fr'); ?>>FR</option>
                                <option value="en" <?php selected($settings['language'] ?? 'fr', 'en'); ?>>EN</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Webhook URL</th>
                        <td><input type="url" name="webhook_url" value="<?php echo esc_attr($settings['webhook_url'] ?? ''); ?>" class="large-text"></td>
                    </tr>
                    <tr>
                        <th scope="row">Webhook Secret</th>
                        <td><input type="text" name="webhook_secret" value="<?php echo esc_attr($settings['webhook_secret'] ?? ''); ?>" class="large-text"></td>
                    </tr>
                    <tr>
                        <th scope="row">QR service URL</th>
                        <td><input type="text" name="qr_service_url" value="<?php echo esc_attr($settings['qr_service_url'] ?? ''); ?>" class="large-text"></td>
                    </tr>
                    <tr>
                        <th scope="row">Consent version</th>
                        <td><input type="text" name="consent_version" value="<?php echo esc_attr($settings['consent_version'] ?? 'v1'); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row">Consent text (necessary)</th>
                        <td><textarea name="consent_text" class="large-text" rows="2"><?php echo esc_textarea($settings['consent_text'] ?? ''); ?></textarea></td>
                    </tr>
                    <tr>
                        <th scope="row">Marketing email text</th>
                        <td><textarea name="marketing_email_text" class="large-text" rows="2"><?php echo esc_textarea($settings['marketing_email_text'] ?? ''); ?></textarea></td>
                    </tr>
                    <tr>
                        <th scope="row">Marketing WhatsApp/SMS text</th>
                        <td><textarea name="marketing_sms_text" class="large-text" rows="2"><?php echo esc_textarea($settings['marketing_sms_text'] ?? ''); ?></textarea></td>
                    </tr>
                    <tr>
                        <th scope="row">Data retention (months)</th>
                        <td><input type="number" name="retention_months" value="<?php echo esc_attr($settings['retention_months'] ?? 12); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row">Rate limit window (minutes)</th>
                        <td><input type="number" name="rate_limit_window" value="<?php echo esc_attr($settings['rate_limit_window'] ?? 10); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row">Rate limit max</th>
                        <td><input type="number" name="rate_limit_max" value="<?php echo esc_attr($settings['rate_limit_max'] ?? 30); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row">Play limit (hours)</th>
                        <td><input type="number" name="play_limit_hours" value="<?php echo esc_attr($settings['play_limit_hours'] ?? 24); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row">Limit per QR/day</th>
                        <td><input type="number" name="limit_per_qr_day" value="<?php echo esc_attr($settings['limit_per_qr_day'] ?? 0); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row">Limit per hour</th>
                        <td><input type="number" name="limit_per_hour" value="<?php echo esc_attr($settings['limit_per_hour'] ?? 0); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row">Allowed weekdays (1=Mon..7=Sun)</th>
                        <td><input type="text" name="limit_weekdays" value="<?php echo esc_attr(implode(',', $settings['limit_weekdays'] ?? [])); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row">Anti-double threshold (distinct users/device)</th>
                        <td><input type="number" name="anti_double_threshold" value="<?php echo esc_attr($settings['anti_double_threshold'] ?? 4); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row">A/B testing</th>
                        <td><input type="checkbox" name="ab_testing" value="1" <?php checked(!empty($settings['ab_testing'])); ?>></td>
                    </tr>
                </table>
                <?php submit_button('Save settings'); ?>
            </form>
        </div>
        <?php
    }

    public static function render_campaigns(): void
    {
        global $wpdb;
        $templates = self::campaign_templates();
        $campaigns = $wpdb->get_results('SELECT * FROM ' . DB::table('campaigns') . ' ORDER BY created_at DESC');
        ?>
        <div class="wrap">
            <h1>Campaigns</h1>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="gamehub-form">
                <?php wp_nonce_field('gamehub_save_campaign'); ?>
                <input type="hidden" name="action" value="gamehub_save_campaign">
                <input type="text" name="name" placeholder="Nom campagne" required>
                <input type="datetime-local" name="start_date" required>
                <input type="datetime-local" name="end_date">
                <select name="template">
                    <option value="">Template</option>
                    <?php foreach ($templates as $key => $template) : ?>
                        <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($template['label']); ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="status">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
                <?php submit_button('Add campaign', 'primary', 'submit', false); ?>
            </form>
            <table class="widefat striped">
                <thead><tr><th>Name</th><th>Start</th><th>End</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($campaigns as $campaign) : ?>
                    <tr>
                        <td><?php echo esc_html($campaign->name); ?></td>
                        <td><?php echo esc_html($campaign->start_date); ?></td>
                        <td><?php echo esc_html($campaign->end_date ?: '-'); ?></td>
                        <td><?php echo esc_html($campaign->status); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public static function render_prizes(): void
    {
        global $wpdb;
        $campaigns = $wpdb->get_results('SELECT id,name FROM ' . DB::table('campaigns'));
        $prizes = $wpdb->get_results('SELECT * FROM ' . DB::table('prizes') . ' ORDER BY created_at DESC');
        ?>
        <div class="wrap">
            <h1>Prizes</h1>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="gamehub-form">
                <?php wp_nonce_field('gamehub_save_prize'); ?>
                <input type="hidden" name="action" value="gamehub_save_prize">
                <select name="campaign_id" required>
                    <option value="">Campaign</option>
                    <?php foreach ($campaigns as $campaign) : ?>
                        <option value="<?php echo esc_attr($campaign->id); ?>"><?php echo esc_html($campaign->name); ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="title" placeholder="Titre" required>
                <input type="text" name="description" placeholder="Description">
                <select name="type">
                    <option value="win">Win</option>
                    <option value="consolation">Consolation</option>
                </select>
                <select name="variant">
                    <option value="">Variant (all)</option>
                    <option value="A">Variant A</option>
                    <option value="B">Variant B</option>
                </select>
                <input type="number" step="0.01" name="weight" placeholder="Poids" value="1">
                <input type="number" name="stock" placeholder="Stock">
                <?php submit_button('Add prize', 'primary', 'submit', false); ?>
            </form>
            <table class="widefat striped">
                <thead><tr><th>Title</th><th>Type</th><th>Variant</th><th>Weight</th><th>Stock</th><th>Remaining</th></tr></thead>
                <tbody>
                <?php foreach ($prizes as $prize) : ?>
                    <tr>
                        <td><?php echo esc_html($prize->title); ?></td>
                        <td><?php echo esc_html($prize->type); ?></td>
                        <td><?php echo esc_html($prize->variant ?: '-'); ?></td>
                        <td><?php echo esc_html($prize->weight); ?></td>
                        <td><?php echo esc_html($prize->stock ?: '-'); ?></td>
                        <td><?php echo esc_html(is_null($prize->remaining) ? '-' : $prize->remaining); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public static function render_actions(): void
    {
        $settings = get_option('gamehub_settings', []);
        $actions = $settings['actions'] ?? [
            'instagram' => '',
            'tiktok' => '',
            'youtube' => '',
            'newsletter' => false,
            'whatsapp' => false,
            'feedback' => false,
            'referral' => '',
            'google_review' => '',
        ];
        ?>
        <div class="wrap">
            <h1>Actions post-jeu</h1>
            <p>Les actions marketing sont optionnelles et ne doivent jamais conditionner un gain. Le bouton Google Review est sans incitation.</p>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('gamehub_save_settings'); ?>
                <input type="hidden" name="action" value="gamehub_save_settings">
                <table class="form-table">
                    <tr><th>Instagram</th><td><input type="url" name="actions[instagram]" value="<?php echo esc_attr($actions['instagram']); ?>" class="large-text"></td></tr>
                    <tr><th>TikTok</th><td><input type="url" name="actions[tiktok]" value="<?php echo esc_attr($actions['tiktok']); ?>" class="large-text"></td></tr>
                    <tr><th>YouTube</th><td><input type="url" name="actions[youtube]" value="<?php echo esc_attr($actions['youtube']); ?>" class="large-text"></td></tr>
                    <tr><th>Newsletter opt-in</th><td><input type="checkbox" name="actions[newsletter]" value="1" <?php checked(!empty($actions['newsletter'])); ?>></td></tr>
                    <tr><th>WhatsApp opt-in</th><td><input type="checkbox" name="actions[whatsapp]" value="1" <?php checked(!empty($actions['whatsapp'])); ?>></td></tr>
                    <tr><th>Feedback</th><td><input type="checkbox" name="actions[feedback]" value="1" <?php checked(!empty($actions['feedback'])); ?>></td></tr>
                    <tr><th>Referral link</th><td><input type="text" name="actions[referral]" value="<?php echo esc_attr($actions['referral']); ?>" class="large-text"></td></tr>
                    <tr><th>Google review URL (no incentive)</th><td><input type="url" name="actions[google_review]" value="<?php echo esc_attr($actions['google_review']); ?>" class="large-text"></td></tr>
                </table>
                <?php submit_button('Save actions'); ?>
            </form>
        </div>
        <?php
    }

    public static function render_qr(): void
    {
        global $wpdb;
        $codes = $wpdb->get_results('SELECT * FROM ' . DB::table('qr_codes') . ' ORDER BY created_at DESC');
        ?>
        <div class="wrap">
            <h1>QR Codes</h1>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="gamehub-form">
                <?php wp_nonce_field('gamehub_save_qr'); ?>
                <input type="hidden" name="action" value="gamehub_save_qr">
                <input type="text" name="label" placeholder="Table 1" required>
                <select name="game_type">
                    <option value="">Hub (multi-jeux)</option>
                    <option value="roulette">Roulette</option>
                    <option value="scratch">Scratch</option>
                    <option value="quiz">Quiz</option>
                    <option value="pickbox">Pick-a-box</option>
                    <option value="memory">Memory</option>
                    <option value="stoptimer">Stop timer</option>
                </select>
                <input type="url" name="target_url" placeholder="Target URL" required>
                <?php submit_button('Add QR', 'primary', 'submit', false); ?>
            </form>
            <table class="widefat striped">
                <thead><tr><th>Label</th><th>QR ID</th><th>Target</th></tr></thead>
                <tbody>
                <?php foreach ($codes as $code) : ?>
                    <tr>
                        <td><?php echo esc_html($code->label); ?></td>
                        <td><?php echo esc_html($code->qr_id); ?></td>
                        <td><?php echo esc_url($code->target_url); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <p>Pour générer un QR visuel, utilisez l'URL avec un générateur QR externe ou ajoutez un plugin QR côté site.</p>
        </div>
        <?php
    }

    public static function render_leads(): void
    {
        global $wpdb;
        $leads = $wpdb->get_results('SELECT * FROM ' . DB::table('leads') . ' ORDER BY created_at DESC LIMIT 200');
        ?>
        <div class="wrap">
            <h1>Leads</h1>
            <table class="widefat striped">
                <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>WhatsApp</th><th>Game</th><th>Created</th></tr></thead>
                <tbody>
                <?php foreach ($leads as $lead) : ?>
                    <tr>
                        <td><?php echo esc_html($lead->first_name . ' ' . $lead->last_name); ?></td>
                        <td><?php echo esc_html($lead->email ?: '-'); ?></td>
                        <td><?php echo esc_html($lead->phone ?: '-'); ?></td>
                        <td><?php echo esc_html($lead->whatsapp ?: '-'); ?></td>
                        <td><?php echo esc_html($lead->source_game ?: '-'); ?></td>
                        <td><?php echo esc_html($lead->created_at); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public static function render_stats(): void
    {
        global $wpdb;
        $plays_table = DB::table('plays');
        $claims_table = DB::table('claims');
        $stats = [
            'plays' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$plays_table}"),
            'wins' => (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$plays_table} WHERE result = %s", 'win')),
            'consolation' => (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$plays_table} WHERE result = %s", 'consolation')),
            'used' => (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$claims_table} WHERE status = %s", 'used')),
        ];
        ?>
        <div class="wrap">
            <h1>Stats</h1>
            <ul>
                <li>Plays: <?php echo esc_html($stats['plays']); ?></li>
                <li>Wins: <?php echo esc_html($stats['wins']); ?></li>
                <li>Consolations: <?php echo esc_html($stats['consolation']); ?></li>
                <li>Used claims: <?php echo esc_html($stats['used']); ?></li>
            </ul>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('gamehub_export_stats'); ?>
                <input type="hidden" name="action" value="gamehub_export_stats">
                <?php submit_button('Export stats CSV'); ?>
            </form>
        </div>
        <?php
    }

    public static function render_validate(): void
    {
        wp_enqueue_script('gamehub-admin');
        global $wpdb;
        $status = sanitize_text_field($_GET['status'] ?? '');
        $claims = $wpdb->get_results('SELECT * FROM ' . DB::table('claims') . ' ORDER BY created_at DESC LIMIT 20');
        ?>
        <div class="wrap">
            <h1>Staff Validation</h1>
            <?php if ($status) : ?>
                <div class="notice notice-info"><p><?php echo esc_html('Statut: ' . $status); ?></p></div>
            <?php endif; ?>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('gamehub_validate_code'); ?>
                <input type="hidden" name="action" value="gamehub_validate_code">
                <input type="text" name="claim_code" placeholder="RESTO-XXXXXX" required>
                <?php submit_button('Validate code'); ?>
            </form>
            <h2>Dernières validations</h2>
            <table class="widefat striped">
                <thead><tr><th>Code</th><th>Status</th><th>Expires</th><th>Used at</th></tr></thead>
                <tbody>
                <?php foreach ($claims as $claim) : ?>
                    <tr>
                        <td><?php echo esc_html($claim->claim_code); ?></td>
                        <td><?php echo esc_html($claim->status); ?></td>
                        <td><?php echo esc_html($claim->expires_at); ?></td>
                        <td><?php echo esc_html($claim->used_at ?: '-'); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public static function render_export_import(): void
    {
        ?>
        <div class="wrap">
            <h1>Export / Import Leads</h1>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('gamehub_export_leads'); ?>
                <input type="hidden" name="action" value="gamehub_export_leads">
                <?php submit_button('Export Leads CSV'); ?>
            </form>
            <hr>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
                <?php wp_nonce_field('gamehub_import_leads'); ?>
                <input type="hidden" name="action" value="gamehub_import_leads">
                <input type="file" name="csv" accept="text/csv" required>
                <?php submit_button('Import Leads'); ?>
            </form>
        </div>
        <?php
    }

    public static function render_privacy(): void
    {
        ?>
        <div class="wrap">
            <h1>Mes données (RGPD)</h1>
            <p>Exporter ou supprimer un lead par email ou téléphone.</p>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('gamehub_privacy_export'); ?>
                <input type="hidden" name="action" value="gamehub_privacy_export">
                <input type="text" name="identifier" placeholder="Email ou téléphone" required>
                <?php submit_button('Exporter', 'secondary', 'submit', false); ?>
            </form>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('gamehub_privacy_delete'); ?>
                <input type="hidden" name="action" value="gamehub_privacy_delete">
                <input type="text" name="identifier" placeholder="Email ou téléphone" required>
                <?php submit_button('Supprimer', 'delete', 'submit', false); ?>
            </form>
        </div>
        <?php
    }

    public static function render_config_copy(): void
    {
        if (!is_multisite()) {
            echo '<div class="wrap"><h1>Config Copy</h1><p>Disponible uniquement en Multisite.</p></div>';
            return;
        }
        $sites = get_sites(['number' => 0]);
        ?>
        <div class="wrap">
            <h1>Duplicateur de configuration</h1>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('gamehub_config_copy'); ?>
                <input type="hidden" name="action" value="gamehub_config_copy">
                <label>Copier depuis le site ID:</label>
                <select name="source_site" required>
                    <?php foreach ($sites as $site) : ?>
                        <option value="<?php echo esc_attr($site->blog_id); ?>"><?php echo esc_html($site->blog_id . ' - ' . $site->domain); ?></option>
                    <?php endforeach; ?>
                </select>
                <?php submit_button('Copier', 'primary', 'submit', false); ?>
            </form>
        </div>
        <?php
    }

    public static function render_audit(): void
    {
        global $wpdb;
        $logs = $wpdb->get_results('SELECT * FROM ' . DB::table('audit') . ' ORDER BY created_at DESC LIMIT 50');
        ?>
        <div class="wrap">
            <h1>Audit logs</h1>
            <table class="widefat striped">
                <thead><tr><th>Action</th><th>User</th><th>Context</th><th>Date</th></tr></thead>
                <tbody>
                <?php foreach ($logs as $log) : ?>
                    <tr>
                        <td><?php echo esc_html($log->action); ?></td>
                        <td><?php echo esc_html($log->admin_user_id ?: '-'); ?></td>
                        <td><?php echo esc_html($log->context); ?></td>
                        <td><?php echo esc_html($log->created_at); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public static function handle_save_settings(): void
    {
        check_admin_referer('gamehub_save_settings');
        Utils::update_option('claim_code_format', sanitize_text_field($_POST['claim_code_format'] ?? 'RESTO-XXXXXX'));
        Utils::update_option('claim_expiry_hours', (int) ($_POST['claim_expiry_hours'] ?? 48));
        Utils::update_option('primary_color', sanitize_text_field($_POST['primary_color'] ?? '#ff6a3d'));
        Utils::update_option('secondary_color', sanitize_text_field($_POST['secondary_color'] ?? '#101828'));
        Utils::update_option('accent_color', sanitize_text_field($_POST['accent_color'] ?? '#7f56d9'));
        Utils::update_option('font_family', sanitize_text_field($_POST['font_family'] ?? 'System'));
        Utils::update_option('dark_mode', !empty($_POST['dark_mode']));
        Utils::update_option('language', sanitize_text_field($_POST['language'] ?? 'fr'));
        Utils::update_option('webhook_url', esc_url_raw($_POST['webhook_url'] ?? ''));
        Utils::update_option('webhook_secret', sanitize_text_field($_POST['webhook_secret'] ?? ''));
        Utils::update_option('qr_service_url', esc_url_raw($_POST['qr_service_url'] ?? ''));
        Utils::update_option('consent_version', sanitize_text_field($_POST['consent_version'] ?? 'v1'));
        Utils::update_option('consent_text', wp_kses_post($_POST['consent_text'] ?? ''));
        Utils::update_option('marketing_email_text', wp_kses_post($_POST['marketing_email_text'] ?? ''));
        Utils::update_option('marketing_sms_text', wp_kses_post($_POST['marketing_sms_text'] ?? ''));
        Utils::update_option('retention_months', (int) ($_POST['retention_months'] ?? 12));
        Utils::update_option('rate_limit_window', (int) ($_POST['rate_limit_window'] ?? 10));
        Utils::update_option('rate_limit_max', (int) ($_POST['rate_limit_max'] ?? 30));
        Utils::update_option('play_limit_hours', (int) ($_POST['play_limit_hours'] ?? 24));
        Utils::update_option('limit_per_qr_day', (int) ($_POST['limit_per_qr_day'] ?? 0));
        Utils::update_option('limit_per_hour', (int) ($_POST['limit_per_hour'] ?? 0));
        $weekdays = array_filter(array_map('intval', explode(',', sanitize_text_field($_POST['limit_weekdays'] ?? ''))));
        Utils::update_option('limit_weekdays', $weekdays);
        Utils::update_option('anti_double_threshold', (int) ($_POST['anti_double_threshold'] ?? 4));
        Utils::update_option('ab_testing', !empty($_POST['ab_testing']));
        if (isset($_POST['actions']) && is_array($_POST['actions'])) {
            $actions = array_map('sanitize_text_field', $_POST['actions']);
            Utils::update_option('actions', $actions);
        }
        Utils::log_audit('settings_updated', ['site_id' => get_current_blog_id()]);
        wp_safe_redirect(admin_url('admin.php?page=gamehub-settings'));
        exit;
    }

    public static function handle_save_campaign(): void
    {
        check_admin_referer('gamehub_save_campaign');
        global $wpdb;
        $templates = self::campaign_templates();
        $template_key = sanitize_text_field($_POST['template'] ?? '');
        $name = sanitize_text_field($_POST['name'] ?? '');
        if ($template_key && isset($templates[$template_key])) {
            $name = $templates[$template_key]['label'] . ' - ' . $name;
        }
        $wpdb->insert(DB::table('campaigns'), [
            'name' => $name,
            'start_date' => sanitize_text_field($_POST['start_date'] ?? ''),
            'end_date' => sanitize_text_field($_POST['end_date'] ?? '') ?: null,
            'status' => sanitize_text_field($_POST['status'] ?? 'inactive'),
            'created_at' => Utils::now_mysql(),
        ]);
        Utils::log_audit('campaign_created', ['name' => $name]);
        wp_safe_redirect(admin_url('admin.php?page=gamehub-campaigns'));
        exit;
    }

    public static function handle_save_prize(): void
    {
        check_admin_referer('gamehub_save_prize');
        global $wpdb;
        $stock = isset($_POST['stock']) && $_POST['stock'] !== '' ? (int) $_POST['stock'] : null;
        $wpdb->insert(DB::table('prizes'), [
            'campaign_id' => (int) $_POST['campaign_id'],
            'title' => sanitize_text_field($_POST['title'] ?? ''),
            'description' => sanitize_text_field($_POST['description'] ?? ''),
            'type' => sanitize_text_field($_POST['type'] ?? 'win'),
            'variant' => sanitize_text_field($_POST['variant'] ?? '') ?: null,
            'weight' => (float) ($_POST['weight'] ?? 1),
            'stock' => $stock,
            'remaining' => $stock,
            'created_at' => Utils::now_mysql(),
        ]);
        Utils::log_audit('prize_created', ['title' => sanitize_text_field($_POST['title'] ?? '')]);
        wp_safe_redirect(admin_url('admin.php?page=gamehub-prizes'));
        exit;
    }

    public static function handle_save_qr(): void
    {
        check_admin_referer('gamehub_save_qr');
        global $wpdb;
        $qr_id = sanitize_text_field($_POST['qr_id'] ?? wp_generate_uuid4());
        $wpdb->insert(DB::table('qr_codes'), [
            'label' => sanitize_text_field($_POST['label'] ?? ''),
            'game_type' => sanitize_text_field($_POST['game_type'] ?? ''),
            'target_url' => esc_url_raw($_POST['target_url'] ?? ''),
            'qr_id' => $qr_id,
            'created_at' => Utils::now_mysql(),
        ]);
        Utils::log_audit('qr_created', ['label' => sanitize_text_field($_POST['label'] ?? '')]);
        wp_safe_redirect(admin_url('admin.php?page=gamehub-qr'));
        exit;
    }

    public static function handle_export_leads(): void
    {
        check_admin_referer('gamehub_export_leads');
        if (!current_user_can('gamehub_export')) {
            wp_die('Unauthorized');
        }
        global $wpdb;
        $leads = $wpdb->get_results('SELECT * FROM ' . DB::table('leads'), ARRAY_A);
        $filename = 'gamehub-leads-' . gmdate('Ymd') . '.csv';
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename=' . $filename);
        $output = fopen('php://output', 'w');
        fputcsv($output, array_keys($leads[0] ?? ['first_name' => '', 'last_name' => '', 'email' => '', 'phone' => '', 'whatsapp' => '', 'source_game' => '', 'campaign_id' => '', 'created_at' => '']));
        foreach ($leads as $lead) {
            fputcsv($output, $lead);
        }
        fclose($output);
        exit;
    }

    public static function handle_export_stats(): void
    {
        check_admin_referer('gamehub_export_stats');
        if (!current_user_can('gamehub_export')) {
            wp_die('Unauthorized');
        }
        global $wpdb;
        $plays_table = DB::table('plays');
        $claims_table = DB::table('claims');
        $rows = [
            ['metric' => 'plays_total', 'value' => (int) $wpdb->get_var(\"SELECT COUNT(*) FROM {$plays_table}\")],
            ['metric' => 'wins', 'value' => (int) $wpdb->get_var($wpdb->prepare(\"SELECT COUNT(*) FROM {$plays_table} WHERE result = %s\", 'win'))],
            ['metric' => 'consolation', 'value' => (int) $wpdb->get_var($wpdb->prepare(\"SELECT COUNT(*) FROM {$plays_table} WHERE result = %s\", 'consolation'))],
            ['metric' => 'claims_used', 'value' => (int) $wpdb->get_var($wpdb->prepare(\"SELECT COUNT(*) FROM {$claims_table} WHERE status = %s\", 'used'))],
        ];
        $filename = 'gamehub-stats-' . gmdate('Ymd') . '.csv';
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename=' . $filename);
        $output = fopen('php://output', 'w');
        fputcsv($output, ['metric', 'value']);
        foreach ($rows as $row) {
            fputcsv($output, $row);
        }
        fclose($output);
        exit;
    }

    public static function handle_import_leads(): void
    {
        check_admin_referer('gamehub_import_leads');
        if (!current_user_can('gamehub_export')) {
            wp_die('Unauthorized');
        }
        if (empty($_FILES['csv']['tmp_name'])) {
            wp_safe_redirect(admin_url('admin.php?page=gamehub-export-import'));
            exit;
        }
        $file = fopen($_FILES['csv']['tmp_name'], 'r');
        if (!$file) {
            wp_safe_redirect(admin_url('admin.php?page=gamehub-export-import'));
            exit;
        }
        $headers = fgetcsv($file);
        global $wpdb;
        while (($row = fgetcsv($file)) !== false) {
            $data = array_combine($headers, $row);
            if (!$data) {
                continue;
            }
            $wpdb->insert(DB::table('leads'), [
                'first_name' => sanitize_text_field($data['first_name'] ?? ''),
                'last_name' => sanitize_text_field($data['last_name'] ?? ''),
                'email' => sanitize_email($data['email'] ?? ''),
                'phone' => sanitize_text_field($data['phone'] ?? ''),
                'whatsapp' => sanitize_text_field($data['whatsapp'] ?? ''),
                'source_game' => sanitize_text_field($data['source_game'] ?? ''),
                'campaign_id' => isset($data['campaign_id']) ? (int) $data['campaign_id'] : null,
                'user_key' => hash('sha256', strtolower(trim($data['email'] ?? ($data['whatsapp'] ?? ($data['phone'] ?? ''))))),
                'created_at' => sanitize_text_field($data['created_at'] ?? Utils::now_mysql()),
            ]);
        }
        fclose($file);
        wp_safe_redirect(admin_url('admin.php?page=gamehub-export-import'));
        exit;
    }

    public static function handle_validate_code(): void
    {
        check_admin_referer('gamehub_validate_code');
        if (!current_user_can('gamehub_validate') && !current_user_can('gamehub_manage')) {
            wp_die('Unauthorized');
        }
        global $wpdb;
        $code = sanitize_text_field($_POST['claim_code'] ?? '');
        if (!$code) {
            wp_safe_redirect(admin_url('admin.php?page=gamehub-validate'));
            exit;
        }
        $table = DB::table('claims');
        $claim = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE claim_code = %s", $code));
        if (!$claim) {
            wp_safe_redirect(admin_url('admin.php?page=gamehub-validate&status=invalid'));
            exit;
        }
        if ($claim->status === 'used') {
            wp_safe_redirect(admin_url('admin.php?page=gamehub-validate&status=used'));
            exit;
        }
        if (strtotime($claim->expires_at) < time()) {
            $wpdb->update($table, ['status' => 'expired'], ['id' => $claim->id]);
            wp_safe_redirect(admin_url('admin.php?page=gamehub-validate&status=expired'));
            exit;
        }
        $wpdb->update($table, [
            'status' => 'used',
            'used_at' => Utils::now_mysql(),
            'staff_user_id' => get_current_user_id(),
        ], ['id' => $claim->id]);
        Utils::log_audit('claim_validated', ['claim_code' => $code]);
        wp_safe_redirect(admin_url('admin.php?page=gamehub-validate&status=used'));
        exit;
    }

    public static function handle_privacy_export(): void
    {
        check_admin_referer('gamehub_privacy_export');
        if (!current_user_can('gamehub_manage')) {
            wp_die('Unauthorized');
        }
        $identifier = sanitize_text_field($_POST['identifier'] ?? '');
        if (!$identifier) {
            wp_safe_redirect(admin_url('admin.php?page=gamehub-privacy'));
            exit;
        }
        global $wpdb;
        $hash = hash('sha256', strtolower(trim($identifier)));
        $leads = $wpdb->get_results(
            $wpdb->prepare(
                'SELECT * FROM ' . DB::table('leads') . ' WHERE email = %s OR phone = %s OR whatsapp = %s OR user_key = %s',
                $identifier,
                $identifier,
                $identifier,
                $hash
            ),
            ARRAY_A
        );
        $filename = 'gamehub-privacy-' . gmdate('Ymd') . '.csv';
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename=' . $filename);
        $output = fopen('php://output', 'w');
        fputcsv($output, array_keys($leads[0] ?? ['first_name' => '', 'last_name' => '', 'email' => '', 'phone' => '', 'whatsapp' => '', 'source_game' => '', 'campaign_id' => '', 'created_at' => '']));
        foreach ($leads as $lead) {
            fputcsv($output, $lead);
        }
        fclose($output);
        Utils::log_audit('privacy_export', ['identifier' => $identifier]);
        exit;
    }

    public static function handle_privacy_delete(): void
    {
        check_admin_referer('gamehub_privacy_delete');
        if (!current_user_can('gamehub_manage')) {
            wp_die('Unauthorized');
        }
        $identifier = sanitize_text_field($_POST['identifier'] ?? '');
        if (!$identifier) {
            wp_safe_redirect(admin_url('admin.php?page=gamehub-privacy'));
            exit;
        }
        global $wpdb;
        $hash = hash('sha256', strtolower(trim($identifier)));
        $leads = $wpdb->get_results(
            $wpdb->prepare(
                'SELECT id FROM ' . DB::table('leads') . ' WHERE email = %s OR phone = %s OR whatsapp = %s OR user_key = %s',
                $identifier,
                $identifier,
                $identifier,
                $hash
            )
        );
        foreach ($leads as $lead) {
            $wpdb->delete(DB::table('consents'), ['lead_id' => (int) $lead->id]);
            $wpdb->delete(DB::table('leads'), ['id' => (int) $lead->id]);
        }
        Utils::log_audit('privacy_delete', ['identifier' => $identifier]);
        wp_safe_redirect(admin_url('admin.php?page=gamehub-privacy&status=deleted'));
        exit;
    }

    public static function handle_config_copy(): void
    {
        check_admin_referer('gamehub_config_copy');
        if (!current_user_can('gamehub_manage')) {
            wp_die('Unauthorized');
        }
        if (!is_multisite()) {
            wp_safe_redirect(admin_url('admin.php?page=gamehub-config-copy'));
            exit;
        }
        $source_site = (int) ($_POST['source_site'] ?? 0);
        if (!$source_site) {
            wp_safe_redirect(admin_url('admin.php?page=gamehub-config-copy'));
            exit;
        }
        $current = get_current_blog_id();
        switch_to_blog($source_site);
        $settings = get_option('gamehub_settings', []);
        restore_current_blog();
        update_option('gamehub_settings', $settings);
        Utils::log_audit('config_copied', ['source' => $source_site, 'target' => $current]);
        wp_safe_redirect(admin_url('admin.php?page=gamehub-config-copy&status=done'));
        exit;
    }

    private static function campaign_templates(): array
    {
        return [
            'fast_food' => ['label' => 'Fast-food'],
            'bar' => ['label' => 'Bar'],
            'club' => ['label' => 'Club'],
        ];
    }
}
