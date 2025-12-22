// -----------------------------------------------------
// 1. HIDE DEFAULT DROPDOWN AND SINGLE VARIATION PRICE
// -----------------------------------------------------
add_action('wp_head', function () {
    if (is_product()) {
        echo '<style>
            /* Hide default variation selector */
            form.variations_form table.variations { display:none !important; }
            
            /* Hide default single variation price + message */
            .woocommerce-variation.single_variation { display:none !important; }
            
            /* Hide default add to cart button */
            .single_variation_wrap { display:none !important; }
        </style>';
    }
});

// -----------------------------------------------------
// 2. SHOW CUSTOM VARIATION TABLE
// -----------------------------------------------------
add_action('woocommerce_after_add_to_cart_form', 'hf_show_variation_table_single_button');

function hf_show_variation_table_single_button() {
    global $product;

    if (!$product || !$product->is_type('variable')) return;

    $variations = $product->get_available_variations();
    if (empty($variations)) return;

//     echo '<h3 style="margin-top:20px;">Available Options</h3>';

    echo '<table style="width:100%; border-collapse:collapse; margin-top:10px; border:1px solid #ccc;text-align:center;">';
    echo '<thead>
            <tr style="background:#F26A2E;">
                <th style="padding:8px; border:1px solid #ccc; color:#fff"><b>Variation</b></th>
                <th style="padding:8px; border:1px solid #ccc; color:#fff""><b>Price</b></th>
                <th style="padding:8px; border:1px solid #ccc;color:#fff""><b>Qty</b></th>
            </tr>
          </thead><tbody>';

		   foreach ($variations as $var) {
			$var_id = $var['variation_id'];
			$var_obj = wc_get_product($var_id);

			if (!$var_obj) continue;

			// Get only attribute values (e.g., “Black”, “White”)
			$attributes = $var_obj->get_attributes();
			$variation_name = implode(', ', $attributes);

			$price = $var_obj->get_price_html();
			$stock = $var_obj->is_in_stock() ? ($var_obj->get_stock_quantity() ?: 'In Stock') : 'Out of Stock';

			echo '<tr style="background:#f2f2f2;">';
			echo '<td style="padding:8px; border:1px solid #ccc;">' . esc_html($variation_name) . '</td>';
			echo '<td style="padding:8px; border:1px solid #ccc;">' . wp_kses_post($price) . '</td>';

        if ($var_obj->is_in_stock()) {
            echo '<td style="padding:8px; border:1px solid #ccc;">
                    <input type="number" class="hf-qty" data-variation="' . esc_attr($var_id) . '" 
                        value="0" min="0" max="' . esc_attr($var_obj->get_stock_quantity()) . '" 
                        style="width:90px;">
                  </td>';
        } else {
            echo '<td style="padding:8px; border:1px solid #ccc; color:red;">Unavailable</td>';
        }

        echo '</tr>';
    }

    echo '</tbody></table>';

    // Single Add-to-Cart button
 echo '
<div style="margin-top:25px; text-align:right;">
    <button id="hf-add-all-to-cart" 
            data-product="' . esc_attr($product->get_id()) . '"
            style="
                padding:14px 28px;
                background:#F26A2E;
                color:#ffffff;
                font-size:16px;
                font-weight:600;
                border:none;
                border-radius:8px;
                cursor:pointer;
                transition:0.3s ease;
                box-shadow:0 4px 10px rgba(0,0,0,0.12);
            "
            onmouseover="this.style.background=\'#d95b25\'"
            onmouseout="this.style.background=\'#F26A2E\'"
    >
        Add to Quote
    </button>
</div>';


	
}

// -----------------------------------------------------
add_action('wp_footer', function() {
    if (!is_product()) return;

    $ajax_nonce = wp_create_nonce('hf_add_to_cart_nonce');
    ?>
<script type="text/javascript">
jQuery(function($){
    $('#hf-add-all-to-cart').on('click', function(e){
        e.preventDefault();

        var product_id = $(this).data('product');
        var variations = [];

        $('.hf-qty').each(function(){
            var qty = parseInt($(this).val());
            if(qty > 0){
                variations.push({
                    variation_id: $(this).data('variation'),
                    quantity: qty
                });
            }
        });

        if(variations.length === 0){
            alert("Please enter quantity for at least one variation.");
            return;
        }

        $.ajax({
            url: "<?php echo admin_url('admin-ajax.php'); ?>",
            type: "POST",
            dataType: "json",
            data: {
                action: "hf_add_multiple_variations_to_cart",
                product_id: product_id,
                variations: variations,
                security: "<?php echo $ajax_nonce; ?>"
            },
            success: function(response){
                if(response.success){
                    // Redirect to cart page immediately
                    window.location.href = "<?php echo wc_get_cart_url(); ?>";
                } else {
                    alert(response.data?.message || "Error adding to cart");
                }
            },
            error: function(xhr, status, error){
                console.log(xhr.responseText);
                alert("AJAX request failed. Try again.");
            }
        });
    });
});
</script>
<?php
});
add_action('wp_ajax_hf_add_multiple_variations_to_cart', 'hf_ajax_add_multiple_variations_to_cart');
add_action('wp_ajax_nopriv_hf_add_multiple_variations_to_cart', 'hf_ajax_add_multiple_variations_to_cart');

function hf_ajax_add_multiple_variations_to_cart() {

    // Verify nonce
    if ( !isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'hf_add_to_cart_nonce') ) {
        wp_send_json_error(['message' => 'Security check failed']);
    }

    $product_id = intval($_POST['product_id']);
    $variations = $_POST['variations'] ?? [];

    if(empty($variations)){
        wp_send_json_error(['message' => 'No variations selected']);
    }

    foreach($variations as $var){
        $variation_id = intval($var['variation_id']);
        $quantity     = intval($var['quantity']);

        $variation_obj = wc_get_product($variation_id);

        if(!$variation_obj || !$variation_obj->is_in_stock()) continue;

        $stock = $variation_obj->get_stock_quantity();
        if($quantity > $stock){
            $quantity = $stock;
        }

        WC()->cart->add_to_cart($product_id, $quantity, $variation_id);
    }

    wp_send_json_success(['message' => 'Added to cart']);
}
