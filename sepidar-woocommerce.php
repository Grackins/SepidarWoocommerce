<?php
/**
 * Plugin Name:     Sepidar Woocommerce
 * Plugin URI:      https://github.com/Grackins/SepidarWoocommerce
 * Description:     Wordpress plugin to sync WooCommerce and Sepidar
 * Author:          Grace Hawkins, Alireza Rahmani
 * Author URI:      https://github.com/GarceHawkins
 * Text Domain:     sepidar-woocommerce
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Sepidar_Woocommerce
 */

require_once('settings.php');
require_once('api.php');
require_once('db.php');

error_log("Running the shit\n");

register_activation_hook(__FILE__, 'sw_db_create_database');
add_action('sw_cron_update_quantity', 'update_quantity_data');
add_action('sw_cron_update_price', 'update_price_data');
add_action('woocommerce_payment_complete', 'sw_payment_complete');
add_filter('cron_schedules', 'sw_add_cron_interval');

if (! wp_next_scheduled('sw_cron_update_price')) {
    wp_schedule_event(time(), 'update_price_interval', 'sw_cron_update_price');
}
if (! wp_next_scheduled('sw_cron_update_quantity')) {
    wp_schedule_event(time(), 'update_quantity_interval', 'sw_cron_update_quantity');
}

function sw_add_cron_interval( $schedules ) { 
    global $SW_QUANTITY_UPDATE_INTERVAL, $SW_PRICE_UPDATE_INTERVAL;
    $quantity_msg = 'Every ' . $SW_QUANTITY_UPDATE_INTERVAL . ' Secs';
    $price_msg = 'Every ' . $SW_PRICE_UPDATE_INTERVAL . ' Secs';
    $schedules['update_quantity_interval'] = array(
        'interval' => $SW_QUANTITY_UPDATE_INTERVAL,
        'display'  => esc_html__( $quantity_msg ), );
    $schedules['update_price_interval'] = array(
        'interval' => $SW_PRICE_UPDATE_INTERVAL,
        'display'  => esc_html__( $price_msg ), );
    return $schedules;
}

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

function sw_payment_complete($order_id) {
    sw_db_add_todo_factor($order_ir);
}
