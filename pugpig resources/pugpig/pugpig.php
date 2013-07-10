<?php
/*

Things to do
==============================================================================

- Test with GZIP
- Manifest does not include wordpress includes yet. Should they be offline?
- Consider SSL, especially with the absolute to relative rewriting
- Consider a different theme for Pugpig? A setting:
http://www.nathanrice.net/blog/serve-ie6-visitors-the-default-wordpress-theme/
- Alternatively, add an extra CSS

Info for WordPress:
==============================================================================

Plugin Name: Pugpig for WordPress - Core
Plugin URI: http://pugpig.com/WordPress
Description: Allow your WordPress blog to produce beautiful Pugpig publications
Version: 1.1
Author: Kaldor Limited
Author URI: http://kaldorgroup.com/

Licence:
==============================================================================
(c) 2011, Kaldor Holdings Ltd
This module is released under the GNU General Public License.
See COPYRIGHT.txt and LICENSE.txt

*/?><?php

/************************************************************************
Version Check
*************************************************************************/

global $wp_version;
if( version_compare( $wp_version, "3.1", "<" ) ){
    exit( 'This plugin requires php5 and WordPress 3.1 or newer. <a href="http://codex.wordpress.org/Upgrading_WordPress">Please update!</a>' );
}

/************************************************************************
Includes
*************************************************************************/
require_once('pugpig_filesystem.php');
require_once('pugpig_admin.php');
require_once('pugpig_ad_bundles.php');
require_once('pugpig_change_hooks.php');
require_once('pugpig_settings.php');
require_once('pugpig_notifications_wordpress.php');
require_once('pugpig_manifests_wordpress.php');
require_once('pugpig_html_manifest.php');
require_once('pugpig_url_rewrites.php');
require_once('pugpig_article_rewrite.php');
require_once('common/pugpig_notifications.php');
require_once('common/ip_in_range.php');
require_once('common/pugpig_dovetail.php');
require_once('common/pugpig_amazon.php');
require_once('common/pugpig_google.php');
require_once('common/url_to_absolute/add_relative_dots.php');



/************************************************************************
Messy Boilerplate
*************************************************************************/

// some definition we will use
define( 'PUGPIG_CURRENT_VERSION', '1.1 (standalone ' . pugpig_get_standalone_version() . ')' );


//define('WP_DEBUG', true);
//define('WP_DEBUG_DISPLAY', true);
// error_reporting(E_ALL | E_NOTICE);
//ini_set('display_errors', '1');

//phpinfo();

// Directories to store logs and manifests

$wp_ud_arr = wp_upload_dir();

define( 'PUGPIG_MANIFESTURL', pugpig_strip_domain($wp_ud_arr['baseurl'] .'/pugpig-api/'));
define( 'PUGPIG_MANIFESTPATH', str_replace('\\', '/', $wp_ud_arr['basedir']) .'/pugpig-api/' );
// define( 'PUGPIG_THEME_MANIFEST', PUGPIG_MANIFESTPATH . 'wordpress-theme.manifest');


/************************************************************************
Using the session for admin messages
*************************************************************************/
if (!session_id())
  session_start();

if(!defined('PUGPIG_CURL_TIMEOUT')) define('PUGPIG_CURL_TIMEOUT', 20);


/************************************************************************
Admin Interface Elements
*************************************************************************/
register_activation_hook(__FILE__, 'pugpig_activate');
register_deactivation_hook(__FILE__, 'pugpig_deactivate');
register_uninstall_hook(__FILE__, 'pugpig_uninstall');

// activating the default values
function pugpig_activate() {
  pugpig_create_writable_directory(PUGPIG_MANIFESTPATH);
  // pugpig_build_all_html5_manifests();
}

// deactivating
function pugpig_deactivate() {
  update_option('plugin_error',  "");
  // pugpig_delete_directory(PUGPIG_MANIFESTPATH);
}

// uninstalling
function pugpig_uninstall() {
  pugpig_deactivate();
}

/************************************************************************
Debug on activate
Keeps the last activation error in a setting and echo it back
*************************************************************************/
add_action('activated_plugin','save_error');
function save_error(){
    update_option('plugin_error',  ob_get_contents());
}
echo get_option('plugin_error');

if (isset($wp_ud_arr['error']) && !empty($wp_ud_arr['error'])) {
	pugpig_add_admin_notice("Error creating Pugpig area: " . $wp_ud_arr['error'], "error");
}


