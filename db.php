<?php
require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
require_once('settings.php');

class SW_TodoFactor {
    public $order_id;
    public $stage;
    public $factor_id;

    function __construct($order_id, $stage ,$factor_id) {
        $this->order_id = $order_id;
        $this->stage = $stage;
        $this->factor_id = $factor_id;
    }
}

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
        factor_id int(9) NOT NULL,
        order_id int NOT NULL,
        stage int NOT NULL,
        UNIQUE KEY id (id),
        UNIQUE (order_id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

function sw_db_add_todo_factor($order_id) {
    error_log('Adding todo factor ' . $order_id);
    $table = table_name('todo_factors');
    $factor_id = sw_db_get_last_factor_number() + 1;
    $sql = "INSERT INTO $table (order_id, factor_id, stage) VALUES ($order_id, $factor_id, 0);";
    dbDelta($sql);
}

function sw_db_get_todo_factors() {
    global $wpdb;
    $table = table_name('todo_factors');
    $orders = $wpdb->get_results("SELECT order_id, factor_id, stage FROM $table");
    $orders = array_map(function ($row) {
	    return new SW_TodoFactor($row->order_id, $row->stage, $row->factor_id);
    }, $orders);
    return $orders;
}

function sw_db_update_todo_factor($todo) {
    $table = table_name('todo_factors');
    $order_id = $todo->order_id;
    $stage = $todo->stage;
    if ($todo->stage == 2) {
	    $sql = "UPDATE $table SET stage=$stage WHERE order_id=$order_id";
	    error_log("fuck this shit!");
	    dbDelta($sql);
	    $sql = "DELETE FROM $table WHERE order_id=$order_id";
    } else
        $sql = "UPDATE $table SET stage=$stage WHERE order_id=$order_id";
    dbDelta($sql);
}

function sw_db_get_last_factor_number() {
	global $wpdb;
	global $SW_BASE_INVOICE_NUMBER;
	$table = table_name('todo_factors');
	$factor_number = $wpdb->get_results("SELECT factor_id FROM $table ORDER BY factor_id DESC LIMIT 1");
	if ($factor_number == null)
		return $SW_BASE_INVOICE_NUMBER;
	$factor_number = array_map(function ($row) {
		return $row->factor_id;
	}, $factor_number);
	return $factor_number[0];
}
