<?php
class UrlMemory_Admin_Post {

	/**
	 * Constructor
	 *
	 * @return boolean
	 */
	public function __construct() {
		add_action( 'admin_notices', array(__CLASS__, 'admin_notices') );
		add_action( 'admin_init', array(__CLASS__, 'admin_init') );
		add_action( 'add_meta_boxes', array(__CLASS__, 'add_meta_boxes'), 10, 2 );
	}
	
	/**
	 * This method show warning message if the current slug post is used for redirection
	 *
	 * @return void
	 * @author Amaury Balmer, Alexandre Sadowski
	 */
	public static function admin_notices() {
		global $wpdb, $pagenow;
		
		// Only on write page
		if ( $pagenow != 'post.php' || !isset($_GET['post']) )
			return false;
		
		// Object ID is valid ?
		$post_id = (int) $_GET['post'];
		if ( $post_id == 0 ) {
			return false;
		}
		
		// Get post detail
		$post = get_post($post_id);
		if ( $post == false ) {
			return false;
		}
		
		// Slug is empty ?
		if ( empty( $post->post_name ) ){
			return false;
		}
		
		// Published content ?
		if ( $post->post_status != 'publish' ){
			return false;
		}
		// Get permalink and remove HTTP and host
		$current_path = str_replace( home_url(), '', get_permalink($post_id) );
		
		if ( isset( $current_path ) && !empty( $current_path ) ) {
			$result = $wpdb->get_var($wpdb->prepare("SELECT id FROM $wpdb->url_redirect WHERE status = %d AND path = %s AND post_id != %d", 0, $current_path, $post_id ) );
			if ( $result != false ) { ?>
				<div id="message" class="error fade">
					<p><strong>
						<?php 
						printf(
							__('Warning: this URL has already been used in the past, a redirection to the new was created. <a href="%s">Click here</a> to remove this redirection. If not, consider changing your slug!', 'url-memory')
							, wp_nonce_url(admin_url('/post.php?post='.$post_id.'&action=remove_redirect&id='.$result), 'remove-redirect'.$result )
						); ?>
					</strong></p>
				</div>
			<?php }
		}
		
		return true;
	}

	/**
	 * This method remove the redirect of warning link message in admin
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	public static function admin_init() {
		if ( isset($_GET['post']) && isset($_GET['id']) && isset($_GET['action']) && $_GET['action'] == 'remove_redirect' ) {
			check_admin_referer('remove-redirect'.$_GET['id']);
			
			um_delete_redirect( $_GET['id'] );
			
			wp_redirect( wp_get_referer() );
			exit();
		}
	}
	
	/**
	 * Adds the meta box container in edit post / page
	 * 
	 * @return boolean
	 * @author Amaury Balmer, Alexandre Sadowski
	 */
	public static function add_meta_boxes( $post_type, $post ) {
		global $wpdb;
		
		// Query for check redirect
		$result = $wpdb -> get_var($wpdb -> prepare("SELECT path FROM $wpdb->url_redirect WHERE status = %d AND post_id = %d", 0, $post->ID) );
		if ( $result == false ) {
			return false;
		}
		
		// Add metabox in admin if redirect exist
		foreach( get_post_types( array('exclude_from_search' => false) ) as $post_type ) {
			add_meta_box(
				'urlredirectdiv', 
				__( 'Active redirections', 'url-memory'), 
				array( __CLASS__, 'umMetaBoxContent' ), 
				$post_type, 
				'side', 
				'low'
			);
		}
	
		return true;
	}

	/**
	* This method is to show list of redirection of a post in metabox
	*
	* @return void
	* @author Amaury Balmer, Alexandre Sadowski
	*/
	public static function umMetaBoxContent( $post ) {
		global $wpdb;

		$results = $wpdb -> get_results($wpdb -> prepare("SELECT path FROM $wpdb->url_redirect WHERE status = %d AND post_id = %d", 0, $post->ID) );
		if ( isset( $results ) && !empty( $results ) ) : ?>
			<p><?php _e( 'The URI of this content has been changed in the past. Following is a list of active redirects to this content : ', 'url-memory' ); ?></p>
			<ul>
				<?php foreach ( $results as $row ) : ?>
					<li style="list-style-type:disc;margin-left:15px;"><?php echo home_url( $row->path ); ?></li>
				<?php endforeach; ?>
			</ul>
		<?php
		endif;
	}
}