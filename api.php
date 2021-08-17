<?php

require_once('settings.php');

$BASE_URL = 'http://' . $SEPIDAR_ADDRESS . ':' . $SEPIDAR_PORT .
            '/api/v1';

$REQUEST_HEADERS = array(
    'Authorization' => 'Basic ' . base64_encode($SEPIDAR_USERNAME . ':' . $SEPIDAR_PASSWORD)
);


function get_url($method_name) {
    global $BASE_URL;
    return $BASE_URL . '/' . $method_name;
}

function sw_filter_is_online_market($product) {
    return $product['stockcode'] == 1;
}

function sw_filter_is_online_shop($product) {
    return $product['saleTypeNumber'] == 2;
}

function fetch_all_sepidar_products_quantity() {
    global $REQUEST_HEADERS;
    global $QUANTITY_FIELD_NAME, $SKU_FIELD_NAME;
    $url = get_url('StockSummary');
    $args = array(
        'headers' => $REQUEST_HEADERS,
        'timeout' => 20,
    );
    $req = wp_remote_get($url, $args);
    if (is_wp_error($req)) {
        error_log('Failed to fetch quantities');
        error_log(print_r($req->errors, true));
        return array();
    }

    $body = wp_remote_retrieve_body($req);
    $products = json_decode($body, true);
    $products = array_filter($products, "sw_filter_is_online_market");
    $result = array();
    foreach ($products as $product)
        $result[$product['itemcode']] = $product['quantity'];
    return $result;
}

function fetch_all_sepidar_products_price() {
    global $REQUEST_HEADERS;
    $url = get_url('PriceNoteList');
    $args = array(
        'headers' => $REQUEST_HEADERS,
        'timeout' => 20,
    );
    $req = wp_remote_get($url, $args);
    if (is_wp_error($req)) {
        error_log('Failed to fetch prices');
        error_log(print_r($req->errors, true));
        return array();
    }

    $body = wp_remote_retrieve_body($req);
    $products = json_decode($body, true);
    $products = array_filter($products, "sw_filter_is_online_shop");
    $result = array();
    foreach($products as $product)
        $result[$product['itemCode']] = $product['fee'] / 10;
    return $result;
}
