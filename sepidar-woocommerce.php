<?php
/**
 * Plugin Name:     Sepidar Woocommerce
 * Plugin URI:      https://github.com/Grackins/SepidarWoocommerce
 * Description:     Wordpress plugin to sync WooCommerce and Sepidar
 * Author:          Grace Hawkins, Alireza Rahmani
 * Author URI:      https://github.com/GarceHawkins
 * Text Domain:     sepidar-woocommerce
 * Domain Path:     /languages
 * Version:         1.1.2
 *
 * @package         Sepidar_Woocommerce
 */

require_once('settings.php');
require_once('api.php');
require_once('db.php');
require_once('jobs.php');

register_activation_hook(__FILE__, 'sw_db_create_database');
add_action('sw_cron_update_quantity', 'update_quantity_data');
add_action('sw_cron_update_price', 'update_price_data');
add_action('sw_cron_save_factors', 'sw_complete_todo_factors');
add_action('woocommerce_payment_complete', 'sw_payment_complete');
add_filter('cron_schedules', 'sw_add_cron_interval');

if (! wp_next_scheduled('sw_cron_update_price')) {
    wp_schedule_event(time(), 'update_price_interval', 'sw_cron_update_price');
}
if (! wp_next_scheduled('sw_cron_update_quantity')) {
    wp_schedule_event(time(), 'update_quantity_interval', 'sw_cron_update_quantity');
}
if (! wp_next_scheduled('sw_cron_save_factors')) {
    wp_schedule_event(time(), 'save_factors_interval', 'sw_cron_save_factors');
}

function sw_add_cron_interval( $schedules ) { 
    global $SW_QUANTITY_UPDATE_INTERVAL, $SW_PRICE_UPDATE_INTERVAL;
    global $SW_SAVE_FACTORS_INTERVAL;
    $quantity_msg = 'Every ' . $SW_QUANTITY_UPDATE_INTERVAL . ' Secs';
    $schedules['update_quantity_interval'] = array(
        'interval' => $SW_QUANTITY_UPDATE_INTERVAL,
        'display'  => esc_html__( $quantity_msg ), );
    $price_msg = 'Every ' . $SW_PRICE_UPDATE_INTERVAL . ' Secs';
    $schedules['update_price_interval'] = array(
        'interval' => $SW_PRICE_UPDATE_INTERVAL,
        'display'  => esc_html__( $price_msg ), );
    $factors_msg = 'Every ' . $SW_SAVE_FACTORS_INTERVAL . ' Secs';
    $schedules['save_factors_interval'] = array(
        'interval' => $SW_SAVE_FACTORS_INTERVAL,
        'display'  => esc_html__( $factors_msg ), );
    return $schedules;
}


function sw_payment_complete($order_id) {
    error_log('Payment complete ' . $order_id);
    sw_db_add_todo_factor($order_id);
}
