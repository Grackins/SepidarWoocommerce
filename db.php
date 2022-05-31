<?php
require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

class SW_TodoFactor {
    public $order_id;
    public $stage;
    public $factor_number;

    function __construct($order_id, $stage ,$factor_number) {
        $this->order_id = $order_id;
        $this->stage = $stage;
        $this->factor_number = $factor_number;
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
    $factor_number = sw_db_get_last_factor_number();
    $sql = "INSERT INTO $table (order_id, factor_number, stage) VALUES ($order_id, $factor_number, 0);";
    dbDelta($sql);
}

function sw_db_get_todo_factors() {
    global $wpdb;
    $table = table_name('todo_factors');
    $orders = $wpdb->get_results("SELECT order_id, factor_number, stage FROM $table");
    $orders = array_map(function ($row) {
	    return new SW_TodoFactor($row->order_id, $row->factor_number, $row->stage);
    }, $orders);
    return $orders;
}

function sw_db_update_todo_factor($todo) {
    $table = table_name('todo_factors');
    $order_id = $todo->order_id;
    $stage = $todo->stage;
    if ($todo->stage == 2)
        $sql = "DELETE FROM $table WHERE order_id=$order_id";
    else
        $sql = "UPDATE $table SET stage=$stage WHERE order_id=$order_id";
    dbDelta($sql);
}

function sw_db_get_last_factor_number() {
	global $wpdb;
	$table = table_name('todo_factors');
	$factor_number = $wpdb->get_results("SELECT factor_id FROM $table ORDER BY factor_id DESC LIMIT 1");
	if ($factor_number == null)
		return 1;
	return $factor_number;
}
