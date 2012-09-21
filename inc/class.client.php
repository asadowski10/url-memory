<?php
class UrlMemory_Client {

	/**
	 * Constructor, register hooks
	 *
	 * @return void
	 * @author Amaury Balmer, Alexandre Sadowski
	 */
	public function __construct() {
		add_action('init', array(__CLASS__, 'init'), 1);
		add_action('delete_post', array(__CLASS__, 'delete_post'), 10, 1);
		add_action('save_post', array(__CLASS__, 'save_post'), 10, 2);
	}

	/**
	 * Hook call to redirect posts to right url
	 *
	 * @param integer $object_id
	 * @return void
	 * @author Amaury Balmer, Alexandre Sadowski
	 */
	public static function init() {
		global $wpdb;

		if (!isset($_SERVER['REQUEST_URI']) || empty($_SERVER['REQUEST_URI']))
			return false;

		$result_id = $wpdb -> get_var($wpdb -> prepare("SELECT post_id FROM $wpdb->url_redirect WHERE status = %d AND path = %s", 0, $_SERVER['REQUEST_URI']));
		if ($result_id != false && (int)$result_id > 0) {
			$result = get_post($result_id);
			if ($result -> post_status != 'publish') {
				return false;
			}

			// Test is valid redirect exist ?
			$counter = $wpdb -> get_var($wpdb -> prepare("SELECT COUNT(post_id) FROM $wpdb->url_redirect WHERE status = %d AND post_id = %d", 1, $result_id));
			if ( $counter == 1 ) {
				wp_redirect(get_permalink($result_id), 301);
				exit();
			}
		}
		
		return false;
	}

	/**
	 * Hook call for delete url redirection of deleted post
	 *
	 * @param integer $object_id
	 * @return void
	 * @author Amaury Balmer, Alexandre Sadowski
	 */
	public static function delete_post($object_id = 0) {
		um_delete_redirect_rows(array($object_id));
	}

	/**
	 * Hook call for add url redirection of new post
	 * @param integer $object_id
	 * @param string $object
	 * @return void
	 * @author Amaury Balmer, Alexandre Sadowski
	 */
	public static function save_post($object_id = 0, $object = null) {
		global $wpdb, $post;

		// Object ID is valid ?
		$object_id = (int)$object_id;
		if ($object_id == 0) {
			return false;
		}

		// Be sure to have POST data
		if (is_null($object)) {
			$object = get_post($object_id);
		}

		// Slug is empty ?
		if (empty($object -> post_name)) {
			return false;
		}

		// Published content ?
		if ($object -> post_status != 'publish') {
			return false;
		}

		// Get permalink and remove HTTP and host
		$path = str_replace(home_url(), '', get_permalink($object_id));

		// Loop for insert on DB or change status and path !
		if (isset($path) && !empty($path)) {
			$result = $wpdb -> get_var($wpdb -> prepare("SELECT id FROM $wpdb->url_redirect WHERE status = %d AND path = %s", 1, $path));
			if ($result == false) {
				// New URL or previous used URL ?
				$result_id = $wpdb -> get_var($wpdb -> prepare("SELECT id FROM $wpdb->url_redirect WHERE status = %d AND path = %s", 0, $path));
				if ($result_id == false) {
					// Add new content URL
					$row_changed = (int) $wpdb -> insert($wpdb -> url_redirect, array('status' => 1, 'post_id' => $object_id, 'path' => $path), array('%d', '%d', '%s'));
					if ( $row_changed > 0 && $wpdb->insert_id > 0 ) {
						$save_insert_id = $wpdb->insert_id;
						
						// Deactive active URL for manage redirect !
						$wpdb -> update($wpdb -> url_redirect, array('status' => 0), array('post_id' => $object_id));
						
						// Restore status on active URL
						$wpdb -> update($wpdb -> url_redirect, array('status' => 1), array('id' => $save_insert_id));
					}
				} else {
					// Deactive active URL for manage redirect !
					$wpdb -> update($wpdb -> url_redirect, array('status' => 0), array('post_id' => $object_id));
					
					// Restore status on active URL
					$wpdb -> update($wpdb -> url_redirect, array('status' => 1), array('id' => $result_id));
				}

			}
		}
		
		// Loop for change status and path of posts childs !
		if (is_post_type_hierarchical($object -> post_type) != false) {
			$child_query = new WP_Query( array('post_parent' => $object_id, 'post_type' => $object -> post_type, 'post_status' => 'publish', 'nopaging' => true));
			if ($child_query -> have_posts()) {
				while ($child_query -> have_posts()) {
					$child_query -> the_post();
					self::save_post(get_the_ID(), $post);
				}
			}
			wp_reset_postdata();
		}
		
		return true;
	}

}