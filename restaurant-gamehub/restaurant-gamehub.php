<?php
/**
 * Plugin Name: Restaurant GameHub
 * Description: Multisite-ready restaurant game hub with QR-driven games, prizes, leads, and stats.
 * Version: 1.0.0
 * Author: GameHub
 * Network: true
 * Requires at least: 6.4
 * Requires PHP: 8.1
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/includes/class-utils.php';
require_once __DIR__ . '/includes/class-db.php';
require_once __DIR__ . '/includes/class-activator.php';
require_once __DIR__ . '/includes/class-rest.php';
require_once __DIR__ . '/includes/class-shortcodes.php';
require_once __DIR__ . '/includes/class-admin.php';
require_once __DIR__ . '/includes/class-plugin.php';

Restaurant\GameHub\Plugin::instance();
