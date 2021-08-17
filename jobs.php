<?php
function update_quantity_data() {
    error_log("Updating quantity data");
    $sep_quantities_list = fetch_all_sepidar_products_quantity();
    foreach ($sep_quantities_list as $sku => $quantity) {
        $product_id = wc_get_product_id_by_sku($sku);
        if ($product_id == 0)
            continue;
        error_log('Found product: ' . $sku);
        $product = wc_get_product($product_id);
        $product->set_stock_quantity($quantity);
        $product->save();
    }
}

function update_price_data() {
    error_log("Updating price data");
    $sep_prices_list = fetch_all_sepidar_products_price();
    foreach ($sep_prices_list as $sku => $price) {
        $product_id = wc_get_product_id_by_sku($sku);
        if ($product_id == 0)
            continue;
        error_log('Found product: ' . $sku);
        $product = wc_get_product($product_id);
        $product->set_regular_price($price);
        $product->save();
    }
}
