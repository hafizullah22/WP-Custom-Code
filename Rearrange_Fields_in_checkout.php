
add_filter('woocommerce_checkout_fields', function($fields) {

    // Set priorities for all fields to enforce the exact order
    $custom_order = array(
        'billing_email'      => 0,
        'billing_country'    => 1,
        'billing_first_name' => 30,
        'billing_last_name'  => 40,
        'billing_address_1'  => 35,
        'billing_address_2'  => 42,
        'billing_city'       => 70,
        'billing_state'      => 80,
        
        'billing_phone'      => 100
    );

    foreach ($custom_order as $key => $priority) {
        if (isset($fields['billing'][$key])) {
            $fields['billing'][$key]['priority'] = $priority;
            $fields['billing'][$key]['class'] = array('form-row-wide'); // full width
        }
    }

    // Optional: Improve address label
    if (isset($fields['billing']['billing_address_1'])) {
        $fields['billing']['billing_address_1']['label'] = 'Street Address';
    }

    return $fields;
});


