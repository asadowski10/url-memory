<?php
/**
 * This method allow to delete redirect post
 *
 * @param array $ids
 * @return integer row quantity deleted on table
 * @author Amaury Balmer, Alexandre Sadowski
 */
function um_delete_redirect($ids = array()) {
	global $wpdb;

	// ids are not an array?
	if (!is_array($ids)) {
		$ids = (array)$ids;
	}

	// Check in array if all ids are integer ?
	$ids = array_map('intval', $ids);

	// Empty array ? Go out !
	if (empty($ids))
		return 0;

	return $wpdb -> query("DELETE FROM $wpdb->url_redirect WHERE id IN ( " . implode(', ', $ids) . ")");
}

/**
 * This method allow to delete rows of deleted post
 * @param array $object_ids
 * @return integer row quantity deleted on table
 * @author Amaury Balmer, Alexandre Sadowski
 */
function um_delete_redirect_rows($object_ids = array()) {
	global $wpdb;

	if (!is_array($object_ids)) {
		$object_ids = (array)$object_ids;
	}
	$object_ids = array_map('intval', $object_ids);

	// Empty array ? Go out !
	if (empty($object_ids)) {
		return 0;
	}

	return $wpdb -> query("DELETE FROM $wpdb->url_redirect WHERE post_id IN ( " . implode(', ', $object_ids) . ")");
}
