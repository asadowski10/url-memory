<?php
/*
 Plugin Name: URL Memory
 Plugin URI: http://wordpress.org/extend/plugins/url-memory/
 Description: Save history URLs for a post and manages 301 redirects for SEO
 Author: Amaury Balmer, Alexandre Sadowski
 Author URI: http://www.beapi.fr
 Version: 1.1
 Text Domain: url-memory
 Domain Path: /languages/
 Network: false

 ----

 Copyright 2012 Amaury Balmer (amaury@beapi.fr), Alexandre Sadowski (asadowski@beapi.fr)

 This program is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

// Setup table name to store url's
global $wpdb;
$wpdb -> tables[] = 'url_redirect';
$wpdb -> url_redirect = $wpdb -> prefix . 'url_redirect';

// Folder name
define('UM_VERSION', '1.0');
define('UM_OPTION', 'url_redirect');

define('UM_URL', plugins_url('', __FILE__));
define('UM_DIR', dirname(__FILE__));

// Define var Post Per Page default to display rows in settings table
define('UM_DEFAULT_SCREEN_PPP', 20);

// Define var Post Per Page default to Populate Query
define('UM_DEFAULT_POPULATE_PPP', 150);

// Library
require (UM_DIR . '/inc/functions.inc.php');
// require (UM_DIR . '/inc/functions.tpl.php');

// Call client class and functions
require (UM_DIR . '/inc/class.base.php');
require (UM_DIR . '/inc/class.client.php');

if (is_admin()) {// Call admin class
	require (UM_DIR . '/inc/class.admin.php');
	require (UM_DIR . '/inc/class.admin.post.php');

	//Our class extends the WP_List_Table class, so we need to make sure that it's there
	if (!class_exists('WP_List_Table')) {
		require_once (ABSPATH . '/wp-admin/includes/class-wp-list-table.php');
	}
	
	// Call Admin Manage class
	require (UM_DIR . '/inc/class.admin.manage.php');
}

// Activate/Desactive Url Memory
register_activation_hook(__FILE__, array('UrlRedirect_Base', 'activate'));
register_deactivation_hook(__FILE__, array('UrlRedirect_Base', 'deactivate'));

add_action('plugins_loaded', 'init_url_memory');
function init_url_memory() {
	// Load translations
	load_plugin_textdomain('url-memory', false, basename(rtrim(dirname(__FILE__), '/')) . '/languages');

	// Client
	new UrlMemory_Client();

	// Admin
	if (is_admin()) {
		new UrlMemory_Admin();
		new UrlMemory_Admin_Post();
	}

}
