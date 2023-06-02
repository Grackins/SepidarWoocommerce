<?php

require_once( 'settings.php' );

$BASE_URL = 'http://' . $SEPIDAR_ADDRESS . ':' . $SEPIDAR_PORT .
            '/api/v1';

$REQUEST_HEADERS = array(
	'Authorization' => 'Basic ' . base64_encode( $SEPIDAR_USERNAME . ':' . $SEPIDAR_PASSWORD ),
	'Content-Type'  => 'application/json; charset=utf-8'
);


function get_url( $method_name ) {
	global $BASE_URL;

	return $BASE_URL . '/' . $method_name;
}

function sw_filter_is_online_market( $product ) {
	return $product['stockcode'] == 101;
}

function sw_filter_is_online_shop( $product ) {
	return $product['saleTypeNumber'] == 2;
}

function sw_filter_is_online_shop_sale( $product ) {
	return $product['saleTypeNumber'] == 8;
}

function sw_get_invoice_number( $order_id ) {
	global $SW_BASE_INVOICE_NUMBER;

	return $order_id - $SW_BASE_INVOICE_NUMBER;
}

function sw_get_sale_type( $product, $payment ) {
	if ($payment == "operator")
		return 5;
	//"melli_new" and "WC_ZPal" is others
	if ( $product->get_sale_price() == null ) {
		return 2;
	}
	if ( $product->get_sale_price() < $product->get_price() ) {
		return 8;
	}

	return 2;
}

function fetch_all_sepidar_products_quantity() {
	global $REQUEST_HEADERS;
	global $QUANTITY_FIELD_NAME, $SKU_FIELD_NAME;
	$url  = get_url( 'StockSummary' );
	$args = array(
		'headers' => $REQUEST_HEADERS,
		'timeout' => 20,
	);
	$req  = wp_remote_get( $url, $args );
	if ( is_wp_error( $req ) ) {
		error_log( 'Failed to fetch quantities' );
		error_log( print_r( $req->errors, true ) );

		return array();
	}

	$body     = wp_remote_retrieve_body( $req );
	$products = json_decode( $body, true );
	$products = array_filter( $products, "sw_filter_is_online_market" );
	$result   = array();
	foreach ( $products as $product ) {
		$result[ $product['itemcode'] ] = $product['quantity'];
	}

	return $result;
}

function fetch_all_sepidar_products_price($sale) {
	global $REQUEST_HEADERS;
	$url  = get_url( 'PriceNoteList' );
	$args = array(
		'headers' => $REQUEST_HEADERS,
		'timeout' => 20,
	);
	$req  = wp_remote_get( $url, $args );
	if ( is_wp_error( $req ) ) {
		error_log( 'Failed to fetch prices' );
		error_log( print_r( $req->errors, true ) );

		return array();
	}

	$body     = wp_remote_retrieve_body( $req );
	$products = json_decode( $body, true );
	if ($sale)
		$products = array_filter( $products, "sw_filter_is_online_shop_sale" );
	else
		$products = array_filter( $products, "sw_filter_is_online_shop" );
	$result   = array();
	foreach ( $products as $product ) {
		$result[ $product['itemCode'] ] = $product['fee'] / 10000;
	}

	return $result;
}

function sw_api_register_invoice( $order, $factor_id ) {
	global $REQUEST_HEADERS;
	error_log( "send invoice $order->id" );
	$data      = array();
	$date_paid = $order->get_date_paid();
	$payment_method = $order->get_payment_method();
	$date      = $date_paid->format( 'Y-m-d' );
	foreach ( $order->get_items() as $item_key => $item ) {
		$quantity  = $item->get_quantity();
		$product   = $item->get_product();
		$item_sku  = $product->get_sku();
		$sale_type = sw_get_sale_type( $product, $payment_method );
		$price     = $product->get_price();
		$fee       = $price * 10000;
		$item_data = array(
			"sourceid"       => 0,
			"customercode"   => "20001",
			"saletypenumber" => $sale_type,
			"discount"       => 0,
			"addition"       => 0,
			"currencyRate"   => 1,
			"stockCode"      => 101,
			"number"         => intval( $factor_id ),
			"date"           => $date,
			"itemcode"       => strval( $item_sku ),
			"quantity"       => $quantity,
			"fee"            => $fee
		);
		array_push( $data, $item_data );
	}
	$args = array(
		'headers'     => $REQUEST_HEADERS,
		'timeout'     => 20,
		'method'      => 'POST',
		'body'        => json_encode( $data ),
		'data_format' => 'body',
	);
	$url  = get_url( 'RegisterInvoice' );
	$req  = wp_remote_post( $url, $args );
	if ( is_wp_error( $req ) ) {
		error_log( 'Failed to register invoice' );
		error_log( print_r( $req->errors, true ) );

		return false;
	}
	$body   = wp_remote_retrieve_body( $req );
	$result = json_decode( $body, true );
	error_log( wp_remote_retrieve_response_code( $req ) );
	error_log( print_r( $result, true ) );
	if ( wp_remote_retrieve_response_code( $req ) != 200 ) {
		return false;
	}
	if ( strlen( $result['message'] ) == 0 ) {
		return true;
	} elseif ( strval( $result['message'] ) === "8-There is Invoice Number." ) {
		error_log( 'Failed to register invoice because of Invoice is exists: ' . $factor_id );

		return false;
	}

	return false;
}

function sw_api_register_delivery( $order, $factor_id ) {
	global $REQUEST_HEADERS;
	error_log( "send delivery $order->id" );
	$data = array(
		'invoicenumber'  => intval( $factor_id ),
		'saletypenumner' => 2,
	);
	$args = array(
		'headers'     => $REQUEST_HEADERS,
		'timeout'     => 20,
		'method'      => 'POST',
		'body'        => json_encode( $data ),
		'data_format' => 'body',
	);
	$url  = get_url( 'RegisterInventorydelivery' );
	$req  = wp_remote_post( $url, $args );
	if ( is_wp_error( $req ) ) {
		error_log( 'Failed to register delivery' );
		error_log( print_r( $req->errors, true ) );

		return false;
	}
	$body   = wp_remote_retrieve_body( $req );
	$result = json_decode( $body, true );
	error_log( print_r( $result, true ) );
	error_log( wp_remote_retrieve_response_code( $req ) );
	if ( wp_remote_retrieve_response_code( $req ) != 200 ) {
		return false;
	}
	if ( strlen( $result['message'] ) == 0 ) {
		return false;
	}

	return true;
}
