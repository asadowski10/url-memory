<?php
class UrlRedirect_Base {
	/**
	 * Try to create the table during the installation
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	public static function activate() {
		global $wpdb;

		if (!empty($wpdb -> charset))
			$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
		if (!empty($wpdb -> collate))
			$charset_collate .= " COLLATE $wpdb->collate";

		// Add one library admin function for next function
		require_once (ABSPATH . 'wp-admin/includes/upgrade.php');

		// Try to create the meta table
		return maybe_create_table($wpdb -> url_redirect, "CREATE TABLE $wpdb->url_redirect (
				id int(20) NOT NULL AUTO_INCREMENT,
				`status` tinyint(1) NOT NULL,
				post_id bigint(20) NOT NULL,
				path text NOT NULL,
				PRIMARY KEY (id),
				UNIQUE KEY unique_pp (post_id,path(199)),
				KEY idx_sp (`status`,path(199))
			) $charset_collate;");
	}

	/**
	 * Empty function for callback uninstall
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	public static function deactivate() {
	}

}
