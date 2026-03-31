<?php
/**
 * ACF Default Field Values
 * Loaded via functions.php: require_once get_stylesheet_directory() . '/includes/acf-defaults.php';
 */

add_filter( 'acf/load_field/name=business_hours', function( $field ) {
    if ( ! empty( $field['value'] ) ) return $field;

    $field['value'] = array(
        array( 'field_69b03bcd51be5' => 'Monday',    'field_69b03be151be6' => '8:00 am', 'field_69b03bff51be7' => '5:00 pm', 'field_69b03cb66401b' => 0 ),
        array( 'field_69b03bcd51be5' => 'Tuesday',   'field_69b03be151be6' => '8:00 am', 'field_69b03bff51be7' => '5:00 pm', 'field_69b03cb66401b' => 0 ),
        array( 'field_69b03bcd51be5' => 'Wednesday', 'field_69b03be151be6' => '8:00 am', 'field_69b03bff51be7' => '5:00 pm', 'field_69b03cb66401b' => 0 ),
        array( 'field_69b03bcd51be5' => 'Thursday',  'field_69b03be151be6' => '8:00 am', 'field_69b03bff51be7' => '5:00 pm', 'field_69b03cb66401b' => 0 ),
        array( 'field_69b03bcd51be5' => 'Friday',    'field_69b03be151be6' => '8:00 am', 'field_69b03bff51be7' => '5:00 pm', 'field_69b03cb66401b' => 0 ),
        array( 'field_69b03bcd51be5' => 'Saturday',  'field_69b03be151be6' => '8:00 am', 'field_69b03bff51be7' => '5:00 pm', 'field_69b03cb66401b' => 0 ),
        array( 'field_69b03bcd51be5' => 'Sunday',    'field_69b03be151be6' => '',         'field_69b03bff51be7' => '',         'field_69b03cb66401b' => 1 ),
    );

    return $field;
} );