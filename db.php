<?php
function sw_db_create_databse() {
    global $wpdb;
	$charset_collate = $wpdb->get_charset_collate();
	$table_name = $wpdb->prefix . 'sw_todo_factors';

	$sql = "CREATE TABLE $table_name (
		id int(9) NOT NULL AUTO_INCREMENT,
        order_id int NOT NULL,
        UNIQUE KEY id (id),
        UNIQUE (order_id)
	) $charset_collate;";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );
}

function sw_db_add_todo_factor($order_id) {
}

function sw_db_get_todo_factors() {
}
