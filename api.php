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

function sw_get_invoice_number($order_id) {
    global $SW_BASE_INVOICE_NUMBER;
    return $order_id - $SW_BASE_INVOICE_NUMBER;
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

function sw_api_register_invoice($order) {
    global $REQUEST_HEADERS;
    $data = array();
    $date_modified = $order->get_date_modified();
    $date = $date_modified->format('Y-m-d');
    foreach ($order->get_items() as $item_key => $item ){
        $itemcode = $item->get_product_id();
        $quantity = $item->get_quantity();
        $product = $item->get_product();
        $price = $product->get_price();
        $fee = $price * 10000;
        $item_data = array(
            "sourceid"=> 0,
            "customercode"=> "20001",
            "saletypenumber"=> 2,
            "discount"=> 0,
            "addition"=> 0,
            "currencyRate"=> 1,
            "stockCode"=> 101,
            "number" => sw_get_invoice_number($order->id),
            "date" => $date,
            "itemcode" => strval($itemcode),
            "quantity" => $quantity,
            "fee" => $fee
        );
        array_push($data, $item_data);
    }
    $args = array(
        'headers' => $REQUEST_HEADERS,
        'timeout' => 20,
        'method'  => 'POST',
        'body'    => json_encode($data),
    );
    $url = get_url('RegisterInvoice');
    $req = wp_remote_post($url, $args);
    if (is_wp_error($req)) {
        error_log('Failed to register invoice');
        error_log(print_r($req->errors, true));
        return false;
    }
    $body = wp_remote_retrieve_body($req);
    $result = json_decode($body, true);
    if (strlen($result['message']) == 0)
        return true;
    return false;
}

function sw_api_register_delivery($order) {
    global $REQUEST_HEADERS;
    $data = array(
        'invoicenumber' => sw_get_invoice_number($order->id),
        'saletypenumner' => 2,
    );
    $args = array(
        'headers' => $REQUEST_HEADERS,
        'timeout' => 20,
        'method'  => 'POST',
        'body'    => json_encode($data),
    );
    $url = get_url('RegisterInventorydelivery');
    $req = wp_remote_post($url, $args);
    if (is_wp_error($req)) {
        error_log('Failed to register delivery');
        error_log(print_r($req->errors, true));
        return false;
    }
    $body = wp_remote_retrieve_body($req);
    $result = json_decode($body, true);
    if (strlen($result['message']) == 0)
        return false;
    return true;
}
