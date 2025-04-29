<?php
/**
 * Plugin Name: AMS Countdown
 * Description: Shortcode [ams_countdown] - Countdown conectat la AMS - Replace Date and Hour Range.
 * Version: 1.0
 * Author: Raluca Manea [amoos.ro]
 */

if (!defined('ABSPATH')) {
    exit; // Oprește accesul direct
}

// Verificăm dacă pluginul principal e activ
include_once(ABSPATH . 'wp-admin/includes/plugin.php');

if (!is_plugin_active('ams-date-hour-replacer/ams-date-hour-replacer.php')) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p><strong>AMS Countdown:</strong> Pluginul <em>AMS - Replace Date and Hour Range</em> trebuie activat!</p></div>';
    });
    return;
}

// Înregistrăm JS și CSS
function amsCountdownEnqueueAssets() {
    wp_enqueue_script(
        'ams-countdown-script',
        plugins_url('assets/countdown.js', __FILE__),
        array('jquery'),
        null,
        true
    );

    wp_enqueue_style(
        'ams-countdown-style',
        plugins_url('assets/countdown.css', __FILE__)
    );
}
add_action('wp_enqueue_scripts', 'amsCountdownEnqueueAssets');

// Shortcode [ams_countdown]
function amsRenderCountdownShortcode() {
    $strDate = get_option('ams_date');
    $strHour = get_option('ams_hour_start');

    if (!$strDate || !$strHour) {
        return '<div class="ams-countdown">Data sau ora nu sunt setate corect.</div>';
    }

    // Corectăm lunile din română în engleză
    $arrMonthsTranslate = array(
        'ianuarie' => 'January',
        'februarie' => 'February',
        'martie' => 'March',
        'aprilie' => 'April',
        'mai' => 'May',
        'iunie' => 'June',
        'iulie' => 'July',
        'august' => 'August',
        'septembrie' => 'September',
        'octombrie' => 'October',
        'noiembrie' => 'November',
        'decembrie' => 'December',
    );

    $strDateLower = strtolower($strDate);

    foreach ($arrMonthsTranslate as $strRo => $strEn) {
        if (strpos($strDateLower, $strRo) !== false) {
            $strDateLower = str_replace($strRo, $strEn, $strDateLower);
            break;
        }
    }

    $intTimestamp = strtotime($strDateLower . ' ' . $strHour);

    if (!$intTimestamp) {
        return '<div class="ams-countdown">Eroare la procesarea datei și orei.</div>';
    }

    // Formatează în ISO 8601 pentru data-target
    $strTargetDate = date('Y-m-d\TH:i:s', $intTimestamp);

    return '<div class="ams-countdown" data-target-date="' . esc_attr($strTargetDate) . '"></div>';
}

add_shortcode('ams_countdown', 'amsRenderCountdownShortcode');
