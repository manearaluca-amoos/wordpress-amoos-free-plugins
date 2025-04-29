<?php
/**
 * Plugin Name: AMS - Replace Date and Hour Range (Datepicker Version)
 * Description: Plugin pentru întrârirea shortcode-urilor [ams_date], [ams_hour_start] și [ams_hour_end] cu valori din backend.
 * Version: 1.2
 * Author: Raluca Manea [amoos.ro]
 */

// 1. Creăm pagina de Setări
// add_action('admin_menu', function() {
//   add_menu_page('Setări Curs', 'Setări Curs', 'manage_options', 'ams-course-settings', 'ams_course_settings_page');
// });

add_action('admin_menu', function() {
   // $strCapabilitate = current_user_can('editor') ? 'edit_pages' : 'manage_options';
    $strCapabilitate = 'manage_options';

    add_menu_page(
        'Setări Curs',
        'Setări Curs',
        $strCapabilitate,
        'ams-course-settings',
        'ams_course_settings_page',
        'dashicons-calendar-alt',
        3
    );
});


// 2. Afișăm pagina de setări
function ams_course_settings_page() {
    ?>
    <div class="wrap">
        <h1>Setări pentru Data și Ora Cursului</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('ams_settings_group');
            do_settings_sections('ams-course-settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// 3. Înregistrăm setările și câmpurile
add_action('admin_init', function() {
    // Salvăm valorile în baza de date
    register_setting('ams_settings_group', 'ams_date');
    register_setting('ams_settings_group', 'ams_hour_start');
    register_setting('ams_settings_group', 'ams_hour_end');
    register_setting('ams_settings_group', 'ams_price');


    add_settings_section('ams_main_section', 'Setează data și orele', null, 'ams-course-settings');

    // Câmp pentru Data cursului
    add_settings_field('ams_date', 'Data cursului', function() {
        $strDate = get_option('ams_date');
        echo '<input type="text" id="ams_date" name="ams_date" value="' . esc_attr($strDate) . '" class="regular-text">';
    }, 'ams-course-settings', 'ams_main_section');

    // Câmp pentru Ora de început
    add_settings_field('ams_hour_start', 'Ora început', function() {
        $strHourStart = get_option('ams_hour_start');
        echo '<input type="text" id="ams_hour_start" name="ams_hour_start" value="' . esc_attr($strHourStart) . '" class="regular-text">';
    }, 'ams-course-settings', 'ams_main_section');

    // Câmp pentru Ora de sfârșit
    add_settings_field('ams_hour_end', 'Ora final', function() {
        $strHourEnd = get_option('ams_hour_end');
        echo '<input type="text" id="ams_hour_end" name="ams_hour_end" value="' . esc_attr($strHourEnd) . '" class="regular-text">';
    }, 'ams-course-settings', 'ams_main_section');
    
    add_settings_field('ams_price', 'Taxa cursului (numai cifră)', function() {
        $intPrice = get_option('ams_price');
        echo '<input type="number" id="ams_price" name="ams_price" value="' . esc_attr($intPrice) . '" class="regular-text">';
    }, 'ams-course-settings', 'ams_main_section');

});

// 4. Adăugăm Datepicker și Timepicker pentru toate câmpurile
add_action('admin_enqueue_scripts', function($strHookSuffix) {
    if ($strHookSuffix != 'toplevel_page_ams-course-settings') {
        return;
    }

    wp_enqueue_script('jquery-ui-datepicker');
    wp_enqueue_script('jquery-ui-slider');
    wp_enqueue_script('jquery-timepicker', 'https://cdnjs.cloudflare.com/ajax/libs/timepicker/1.3.5/jquery.timepicker.min.js', array('jquery'), '1.3.5', true);

    wp_enqueue_style('jquery-ui-css', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');
    wp_enqueue_style('jquery-timepicker-css', 'https://cdnjs.cloudflare.com/ajax/libs/timepicker/1.3.5/jquery.timepicker.min.css');

    // Inițializăm toate cele 3 inputuri

    wp_enqueue_script('jquery-ui-datepicker-ro', 'https://cdn.jsdelivr.net/npm/jquery-ui/ui/i18n/datepicker-ro.js', array('jquery-ui-datepicker'), null, true);


wp_add_inline_script('jquery-ui-datepicker-ro', 'jQuery(function($){
    $.datepicker.setDefaults($.datepicker.regional["ro"]);
    $("#ams_date").datepicker({ dateFormat: "dd MM yy" });
    $("#ams_hour_start, #ams_hour_end").timepicker({
        timeFormat: "HH:mm",
        interval: 30,
        minTime: "00:00",
        maxTime: "23:30",
        startTime: "00:00",
        dynamic: false,
        dropdown: true,
        scrollbar: true
    });
});');

});

// 5. Înlocuim shortcode-urile
add_filter('the_content', function($strContent) {
    $strDate = get_option('ams_date', '...');
    $strHourStart = get_option('ams_hour_start', '...');
    $strHourEnd = get_option('ams_hour_end', '...');
    $intPrice = get_option('ams_price', '');


    // Calculăm durata în ore
    $intStart = strtotime($strHourStart);
    $intEnd = strtotime($strHourEnd);
    $intDurationInMinutes = ($intEnd - $intStart) / 60;
    $intHours = floor($intDurationInMinutes / 60);
    $intMinutes = $intDurationInMinutes % 60;

    if ($intDurationInMinutes <= 0 || !$intStart || !$intEnd) {
        $strInterval = '—';
    } elseif ($intMinutes === 0) {
        $strInterval = $intHours . ' ore';
    } else {
        $strInterval = $intHours . 'h ' . $intMinutes . 'min';
    }

    // Înlocuim shortcode-urile
    $strContent = str_replace('[ams_date]', esc_html($strDate), $strContent);
    $strContent = str_replace('[ams_hour_start]', esc_html($strHourStart), $strContent);
    $strContent = str_replace('[ams_hour_end]', esc_html($strHourEnd), $strContent);
    $strContent = str_replace('[ams_hour_interval]', esc_html($strInterval), $strContent);
    $strContent = str_replace('[ams_price]', esc_html($intPrice), $strContent);


    return $strContent;
});


add_filter('render_block', function($strContent) {
    return do_shortcode($strContent);
}, 10, 1);


/** Editor users to temporarily have manage_options capability (which normally only Administrators have) **/
add_action('init', function() {
    // Get the Editor role object
    $objEditorRole = get_role('editor');

    if ($objEditorRole && !$objEditorRole->has_cap('manage_options')) {
        // Add 'manage_options' capability to Editors
        $objEditorRole->add_cap('manage_options');
    }
});


