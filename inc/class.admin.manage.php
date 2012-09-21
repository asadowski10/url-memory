<?php
class UrlMemory_Admin_Manage extends WP_List_Table {
	/**
	 * Method to define all your cols in your table
	 *
	 * @return array() $auth_order
	 * @author Amaury Balmer, Alexandre Sadowski
	 */
	private static $auth_order = array('id', /*'status',*/ 'path', 'post_id');

	/**
	 * Constructor
	 *
	 * @return void
	 * @author Amaury Balmer, Alexandre Sadowski
	 */
	public function __construct() {
		parent::__construct(array(
			'singular' => 'redirect', // singular name of the listed records
			'plural' => 'redirects', // plural name of the listed records
			'ajax' => false	// does this table support ajax?
		));

		//Check if user wants delete item in row
		$this->checkDelete();
	}

	/**
	 * Method when no url redirect founded
	 *
	 * @return string
	 * @author Amaury Balmer, Alexandre Sadowski
	 */
	function no_items() {
		_e('No redirection was found.', 'url-memory');
	}

	/**
	 * This method display default column
	 *
	 * @return string $item[ $column_name ]
	 * @author Amaury Balmer, Alexandre Sadowski
	 */
	function column_default($item, $column_name) {
		switch( $column_name ) {
			case 'id' :
			//case 'status' :
			case 'post_id' :
			case 'path' :
				return $item[$column_name];
				break;
			default :
				return print_r($item, true);
				//Show the whole array for troubleshooting purposes
				break;
		}
	}

	/**
	 * Decide which columns to activate the sorting functionality on
	 * @return array $sortable, the array of columns that can be sorted by the user
 	 * @author Amaury Balmer, Alexandre Sadowski
	 */
	function get_sortable_columns() {
		return array(
			'id' 		=> array( 'id', false ),
			//'status' 	=> array( 'status', false ),
			'post_id' 	=> array( 'post_id', false ),
			'path' 		=> array( 'path', false ),
		);
	}
	
	/**
	 * Define the columns that are going to be used in the table
	 * 
	 * @return array $columns, the array of columns to use with the table
	 * @author Amaury Balmer, Alexandre Sadowski
	 */
	function get_columns() {
		return array(
			'cb' 		=> '<input type="checkbox" />',
			'id' 		=> __( 'Id','url-memory' ),
			//'status' 	=> __( 'Status', 'url-memory' ),
			'post_id'	=> __( 'Object', 'url-memory' ),
			'path' 		=> __( 'Path', 'url-memory' )
		);
	}
	
	/**
	 * Define the columns that are going to be used in the table
	 *
	 * @return array() $query
	 * @author Amaury Balmer, Alexandre Sadowski
	 */
	function prepareQuery() {
		global $wpdb;

		// If no sort, default to title
		$_orderby = (!empty($_GET['orderby']) && in_array($_GET['orderby'], self::$auth_order)) ? $_GET['orderby'] : 'id';
		// If no order, default to asc
		$_order = (!empty($_GET['order']) && in_array($_GET["order"], array('asc', 'desc'))) ? $_GET["order"] : 'asc';
		$order_by = " ORDER BY $_orderby $_order";
		
		// Search
		$search = $this -> getSearchQuery();

		// Make the order
		$limit = $wpdb -> prepare(' LIMIT %d,%d', ($this -> get_pagenum() == 1 ? 0 : $this -> get_pagenum()), $this -> get_items_per_page('um_per_page', UM_DEFAULT_SCREEN_PPP));

		return $wpdb -> get_results("SELECT id, status, path, post_id FROM $wpdb->url_redirect WHERE 1 = 1 AND status = 0 $search $order_by $limit", ARRAY_A);
	}

	/**
	 * This method count total items in table
	 *
	 * @return integer Count(id)
	 * @author Amaury Balmer, Alexandre Sadowski
	 */
	function totalItems() {
		global $wpdb;
		
		$search = $this -> getSearchQuery();
		return (int)$wpdb -> get_var("SELECT COUNT( id ) FROM $wpdb->url_redirect WHERE 1 = 1 AND status = 0 $search");
	}

	/**
	 * Prepare the table with different parameters, pagination, columns and table elements
	 *
	 * @author Amaury Balmer, Alexandre Sadowski
	 */
	function prepare_items() {
		$this -> _column_headers = array($this -> get_columns(), array(), $this -> get_sortable_columns());

		// Get total items
		$total_items = $this -> totalItems();
		$elements_per_page = $this -> get_items_per_page('um_per_page', UM_DEFAULT_SCREEN_PPP);

		// Set the pagination
		$this -> set_pagination_args(array(
			'total_items' => $total_items, //WE have to calculate the total number of items
			'per_page' => $elements_per_page, //WE have to determine how many items to show on a page
			'total_pages' => ceil($total_items / $elements_per_page)
		));

		$this -> items = $this -> prepareQuery();
	}

	/**
	 * Add checkbox input on ID column for delete action
	 *
	 * @return string
	 * @author Amaury Balmer, Alexandre Sadowski
	 */
	function column_cb($item) {
		return sprintf('<input type="checkbox" name="id[]" value="%s" />', $item['id']);
	}

	/**
	 * Add home_url in rows of path column
	 *
	 * @return string
	 * @author Amaury Balmer, Alexandre Sadowski
	 */
	function column_path($item) {
		return home_url($item['path']);
	}
	
	/**
	 * Add post title in rows of post id column
	 *
	 * @return string
	 * @author Amaury Balmer, Alexandre Sadowski
	 */
	function column_post_id($item) {
		return $item['post_id'] . ' - <a href="'.get_edit_post_link($item['post_id']).'">' . get_the_title($item['post_id']) . '</a>';
	}

	/**
	 * Get an associative array ( option_name => option_title ) with the list
	 * of bulk actions available on this table.
	 *
	 * @return array() $actions
	 * @author Amaury Balmer, Alexandre Sadowski
	 */
	function get_bulk_actions() {
		return array('delete' => __('Delete', 'url-memory') );
	}

	/**
	 * Check if user wants delete item on manage page
	 *
	 * @return string $status and $message
	 * @author Amaury Balmer, Alexandre Sadowski
	 */
	function checkDelete() {
		if (!isset($_GET['page']) || $_GET['page'] != 'url-memory' || !isset($_GET['action']) ) {
			return false;
		}
		
		$action = $this -> current_action();
		if (empty($action) || !array_key_exists($action, $this -> get_bulk_actions()) || !isset($_GET['id']) || empty($_GET['id'])) {
			add_settings_error('url-memory', 'settings_updated', __('Oups! You probably forgot to tick redirections to delete?', 'url-memory'), 'error');
			return false;
		}

		check_admin_referer('bulk-redirects');

		$_GET['id'] = array_map('absint', $_GET['id']);
		
		switch ( $action ) {
			case 'delete' :
				$result = um_delete_redirect($_GET['id']);
				if ($result === false) {
					$message_code = 0;
				} elseif ($result === 0) {
					$message_code = 1;
				} else {
					$message_code = 2;
				}
				
				wp_redirect( admin_url('tools.php?page=url-memory&message-code='.$message_code.'&message-value='.(int)$result) );
				exit();
				break;
			default :
				break;
		}
		
		return true;
	}

	/**
	 * This method is to get search query
	 *
	 * @return array() $query
	 * @author Amaury Balmer, Alexandre Sadowski
	 */
	function getSearchQuery() {
		global $wpdb;

		if (!isset($_GET['page']) || $_GET['page'] != 'url-memory' || !isset($_GET['s']) || empty($_GET['s'])) {
			return ' ';
		}

		return $wpdb -> prepare(" AND path LIKE '%%%s%%' ", $_GET['s']);
	}

}
