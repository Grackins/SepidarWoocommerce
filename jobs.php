<?php
require_once('api.php');
require_once('db.php');

function update_quantity_data() {
    error_log("Updating quantity data");
    $sep_quantities_list = fetch_all_sepidar_products_quantity();
    foreach ($sep_quantities_list as $sku => $quantity) {
        $product_id = wc_get_product_id_by_sku($sku);
        if ($product_id == 0)
            continue;
        error_log('Found quantity product: ' . $sku);
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
        error_log('Found price product: ' . $sku);
        if ($price > 1) {
	        $product = wc_get_product( $product_id );
	        $product->set_regular_price( $price );
//	        $product->set_sale_price( $price );
	        $product->save();
        }
    }
}

function sw_complete_todo_factors() {
	global $SW_SEND_FACTOR;
    error_log('Completing todo factors');
    if (!$SW_SEND_FACTOR) {
	    error_log( 'Send factor is off' );
	    return;
    }
    $todos = sw_db_get_todo_factors();
    foreach ($todos as $todo) {
        error_log("Completing todo $todo->order_id($todo->stage) with $todo->factor_id number");
        $order = wc_get_order($todo->order_id);
        switch ($todo->stage) {
        case 0:
            if (!sw_api_register_invoice($order, $todo->factor_id))
                break;
            $todo->stage++;
        case 1:
            if (!sw_api_register_delivery($order, $todo->factor_id))
                break;
            $todo->stage++;
        }
        error_log("Completed todo $todo->order_id($todo->stage)");
        sw_db_update_todo_factor($todo);
    }
}
