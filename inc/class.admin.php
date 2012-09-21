<?php
class UrlMemory_Admin {
	private $ListTable = null;
	/** 
	 * Constructor
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	public function __construct() {
		add_action( 'admin_menu', array( &$this, 'admin_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'admin_init') );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue_scripts' ) );
		add_action( 'wp_ajax_'.'urlmPopulate', array( __CLASS__, 'a_populate' ) );
		add_action( 'load-tools_page_'.'url-memory', array( __CLASS__, 'addOptionScreen' ) );
		add_filter( 'set-screen-option', array( __CLASS__, 'setOptions' ), 1, 3 );
		add_action( 'admin_head', array( __CLASS__, 'admin_head' ) );
		add_action( 'load-tools_page_url-memory', array( &$this, 'init_table' ) );
	}
	
	/**
	 * Add options settings menu and manage redirect tools
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	public function admin_menu() {
		add_options_page( __('URL Memory Settings','url-memory'), __('URL Memory', 'url-memory'), 'manage_options', 'url-memory', array( __CLASS__, 'pageOptions' ) );
		add_management_page( __('Manage redirections','url-memory'), __('Manage redirections','url-memory'), 'manage_options', 'url-memory', array(&$this, 'pageManage' ) );
	}
	
	/**
	 * Instanciate custom WP List table after current_screen defined
	 */
	function init_table() {
		$this->ListTable = new UrlMemory_Admin_Manage();
	}
	
	/**
	 * Load JavaScript in admin
	 *
	 * @return void
	 * @author Amaury Balmer, Alexandre Sadowski
	 */
	public static function admin_enqueue_scripts( $hook ) {
		if( $hook == 'settings_page_url-memory' ) {
			
			// Enqueue main script
			wp_enqueue_script ( 'admin-populate', UM_URL.'/ressources/js/admin-populate.js', array( 'jquery', 'jquery-ui-progressbar' ), UM_VERSION, true );
			wp_localize_script( 'admin-populate', 'umL10n ', array(
				'confirm' => __( 'Are you sure you want to flush the table?', 'url-memory' ),
				'ppp' => (int) UM_DEFAULT_POPULATE_PPP,
				'total_objects' => self::_getElements( 0, true),
				'end_processus_message' => __( 'End of process', 'url-memory' ),
				'start_processus_message' => __( 'Start of process', 'url-memory' ),
				'ppp_valid_message' => __( 'You need PPP valid to make working this script', 'url-memory' ),
				'processing_message' => __( 'Processing of ', 'url-memory' )
			) );
			
			// Enqueue jquery ui css for progress bar
			wp_enqueue_style( 'jquery-ui', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.7/themes/smoothness/jquery-ui.css' );
			
		}
	}
	
	/**
	 * Add Options Screen in Manage page
	 *
	 * @return void
	 * @author Amaury Balmer, Alexandre Sadowski
	 */
	public static function addOptionScreen() {
		$option = 'per_page';
			$args = array(
				'label' => __( 'URL redirects', 'url-memory' ),
				'default' => UM_DEFAULT_SCREEN_PPP,
				'option' => 'um_per_page'
			);
		add_screen_option( $option, $args );
	}

	/**
	 * This method return the value of options
	 *
	 * @return integer $value or string $value
	 * @author Amaury Balmer, Alexandre Sadowski
	 */
	public static function setOptions($status, $option, $value) {
		// Get Post Per Page value in Option Screen
		if ( 'um_per_page' == $option ){
			return (int)$value;
		}
		
		return $value;
	}
	
	/**
	 * Settings page
	 *
	 * @return void
	 * @author Amaury Balmer, Alexandre Sadowski
	 */
	public static function pageOptions() {
		settings_errors('url-memory');
		?>
		<div class="wrap">
			<div class="icon32" id="icon-options-general"><br></div>
			<h2><?php echo get_admin_page_title(); ?></h2>
			
			<h3><?php _e('Flush Table', 'url-memory')?></h3>
			<form method="post">
				<?php _e('You can delete all redirections by emptying the database', 'url-memory') ?>
				
				<p class="submit">
					<?php wp_nonce_field( 'save-flush' ); ?>
					<input type="submit" name="submit-flush" id="submit" class="button-primary" value="<?php _e('Flush Table', 'url-memory'); ?>">
				</p>
			</form>
			
			<h3><?php _e('Import all your datas','url-memory')?></h3>
			<form method="post" id="populateUrl">
				<?php _e('As you want, you can import all your existing content.','url-memory') ?>
				<p class="submit">
					<?php wp_nonce_field( 'save-populate' ); ?>
					<input type="submit" name="submit-populate" id="submit-populate" class="button-primary" value="<?php _e('Import to Database', 'url-memory'); ?>">
				</p>
				<div class="ulPg"></div>
				<ul class='message'style="display:none;height: 250px;overflow: scroll;"></ul>
			</form>
		</div>
		<?php 
	}

	/**
	 * This method allow to flush the Table
	 *
	 * @return integer number of rows deleted !
	 * @author Amaury Balmer, Alexandre Sadowski
	 */
	public static function admin_init() {
		global $wpdb;
		
		if ( isset($_POST['submit-flush']) ) {
			
			check_admin_referer( 'save-flush' );
			
			// Manage message
			$count = $wpdb -> get_var($wpdb -> prepare("SELECT COUNT(id) FROM $wpdb->url_redirect"));
			if ( $count == false ) {
				add_settings_error('url-memory', 'settings_updated', __('Nothing deleted... Because table is empty !','url-memory'), 'error');
			} else {
				add_settings_error('url-memory', 'settings_updated', sprintf(__( '%d rows deleted.', 'url-memory'), $count ), 'updated');
			}
			
			// Flush the table
			$wpdb -> query("TRUNCATE TABLE $wpdb->url_redirect");
			
		}
		return false;
	}
	
	/**
	 * This method populate the DB when you activate the plugin
	 * 
	 * @author Amaury Balmer, Alexandre Sadowski
	 */
	static private function populate( $offset = 0 ) {
		global $post;

		$full_query = self::_getElements($offset);
		if ($full_query -> have_posts()) {
			while ($full_query -> have_posts()) {
				$full_query -> the_post();
				UrlMemory_Client::save_post( get_the_ID(), $post );
			}
			
			return true;
		}
		
		return false;
	}
	
	/**
	 * This method is the Query for populate DB and count the number of posts in DB
	 *
	 * @return integer total number of posts found OR the WP_Query
	 * @author Amaury Balmer, Alexandre Sadowski
	 */
	private static function _getElements( $offset = 0, $count = false ){
		$offset = isset( $offset ) ? (int)$offset : 0 ;
		
		$full_query = new WP_Query( array('post_type' => 'any', 'post_status' => 'publish', 'posts_per_page' => UM_DEFAULT_POPULATE_PPP, 'offset' => $offset ) );
		if ($count === true){
			return $full_query ->found_posts;
		}
		return $full_query;
	}
	
	/**
	 * Ajax Method for execute static populate function
	 * 
	 * @author Amaury Balmer, Alexandre Sadowski
	 */
	public static function a_populate() {
		$offset = isset( $_GET['offset'] ) ? (int)$_GET['offset'] : 0 ;
		$nonce = isset( $_GET['nonce'] ) ? $_GET['nonce'] : '' ;
		
		if( wp_verify_nonce( $nonce, 'save-populate' ) === false ) {
			die(0);
		}
		
		echo self::populate( $offset );
		die();
	}
	
	/**
	 * Display table with redirect in manage page 
	 *
	 * @return void
	 * @author Amaury Balmer, Alexandre Sadowski
	 */
	public function pageManage() {
		if ( isset($_GET['message-code']) ) {
			$_GET['message-code'] = (int) $_GET['message-code'];
			if ( $_GET['message-code'] == 0 ) {
				add_settings_error('url-memory', 'settings_updated', __('Internal error', 'url-memory'), 'error');
			} elseif (  $_GET['message-code'] == 1 ) {
				add_settings_error('url-memory', 'settings_updated', __('No results', 'url-memory'), 'updated');
			} elseif ( $_GET['message-code'] == 2 ) {
				$result = isset($_GET['message-value']) ? $_GET['message-value'] : 0;
				add_settings_error('url-memory', 'settings_updated', sprintf(__('%d deleted elements', 'url-memory'), $result), 'updated');
			}
		}
	
		settings_errors('url-memory');
		
		screen_icon();
		?>
		<div class="wrap"> 
			<h2><?php _e( 'Manage redirections', 'url-memory' ); ?></h2>
			<?php $this->ListTable->prepare_items(); ?>
		
			<form method="get" action="">
				<input type="hidden" name="page" value="url-memory" />
				
				<?php
				$this->ListTable->search_box( 'search', 'search_id' );
				$this->ListTable->display();
				?>
			</form>
		</div>
		<?php
	}
	/**
	 *  This method manage css for table
	 *
	 * @return string <style css>
	 * @author Amaury Balmer, Alexandre Sadowski
	 */
	public static function admin_head() {
		$page = ( isset($_GET['page'] ) ) ? esc_attr( $_GET['page'] ) : false;
		if( 'url-memory' != $page )
			return false;
		 
		echo '<style type="text/css">';
			echo '.wp-list-table .column-id { width: 10%; }';
			echo '.wp-list-table .column-status { width: 5%; }';
			echo '.wp-list-table .column-post_id { width: 30%; }';
			echo '.wp-list-table .column-path { width: 55%; }';
		echo '</style>';
		return true;
	}
}