<?php
function table_name($model) {
    global $wpdb;
    return $wpdb->prefix . "sw_$model";
}

function sw_db_create_database() {
    global $wpdb;
	$charset_collate = $wpdb->get_charset_collate();
	$table = table_name('todo_factors');

	$sql = "CREATE TABLE $table (
		id int(9) NOT NULL AUTO_INCREMENT,
        order_id int NOT NULL,
        UNIQUE KEY id (id),
        UNIQUE (order_id)
	) $charset_collate;";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );
}

function sw_db_add_todo_factor($order_id) {
    $table = table_name('todo_factors');
    $sql = "INSERT INTO $table (order_id) VALUES ($order_id);";
    dbDelta($sql);
}

function sw_db_get_todo_factors() {
    global $wpdb;
    $table = table_name('todo_factors');
    $orders = $wpdb->get_results("SELECT order_id FROM $table");
    $orders = array_map(function ($row) {
        return $row->order_id;
    }, $orders);
    return $orders;
}
