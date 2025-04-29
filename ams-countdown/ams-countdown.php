<?php
/**
 * Plugin Name: AMS Countdown
 * Description: Countdown Gutenberg Block conectat la pluginul AMS - Replace Date and Hour Range.
 * Version: 1.0
 * Author: Raluca Manea [amoos.ro]
 */

if (!defined('ABSPATH')) {
    exit;
}

// Verificăm dacă pluginul AMS - Replace Date and Hour Range este activ
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
if (!is_plugin_active('ams-date-hour-replacer/ams-date-hour-replacer.php')) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p><strong>AMS Countdown:</strong> Pluginul <em>AMS - Replace Date and Hour Range</em> trebuie activat pentru a funcționa acest block.</p></div>';
    });
    return;
}

// Înregistrăm block-ul
function ams_register_countdown_block() {
    if (!function_exists('register_block_type')) {
        return;
    }

    register_block_type(__DIR__ . '/build/block.json');
}
add_action('init', 'ams_register_countdown_block');

// Render callback PHP
function ams_render_countdown_block() {
    $strDate = get_option('ams_date');
    $strHour = get_option('ams_hour_start');

    if (!$strDate || !$strHour) {
        return '<div class="ams-countdown">Data sau ora nu sunt setate.</div>';
    }

    $strDateTime = $strDate . ' ' . $strHour;
    return '<div class="ams-countdown" data-date="' . esc_attr($strDateTime) . '"></div>';
}
