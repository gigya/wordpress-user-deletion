<?php

class GigyaUserDeletionInstall {
	public function init() {
		global $wpdb;

		$table_name      = $wpdb->prefix . GIGYA_USER_DELETION;
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
				  id mediumint(9) NOT NULL AUTO_INCREMENT,
				  filename varchar(128) NOT NULL UNIQUE,
				  time_processed int(11) NOT NULL,
				  PRIMARY KEY  (id)
				) {$charset_collate};";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );

		add_option( 'gigya_user_deletion_db_version', '1.0' );
	}
}