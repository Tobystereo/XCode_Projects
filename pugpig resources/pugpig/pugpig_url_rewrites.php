<?php
/**
 * @file
 * Pugpig rewrite rules for WordPress
 */
?><?php
/*

Licence:
==============================================================================
(c) 2011, Kaldor Holdings Ltd
This module is released under the GNU General Public License.
See COPYRIGHT.txt and LICENSE.txt

 */?><?php

// We add this to the end of the HTML files so we can save them
// We use query string name if we have draft posts or no peramalinks
define( 'PUGPIG_HTML_FILE_NAME', 'pugpig_index.html');
define( 'PUGPIG_HTML_MANIFEST_NAME', 'pugpig.manifest'); // WP won't allow dots: index.manifest (Boo!)
define( 'PUGPIG_EDITION_PACKAGE_FILE_NAME', 'pugpig_package_list.manifest');
define( 'PUGPIG_ATOM_FILE_NAME', 'pugpig_atom_contents.manifest');

/************************************************************************
Check if this is a Pugpig HTML page
*************************************************************************/
function pugpig_is_pugpig_endpoint($endpoint) {

  if (strpos($_SERVER["REQUEST_URI"], $endpoint)) return TRUE;
  if (isset($_REQUEST[str_replace(".","_", $endpoint)])) return TRUE;
  return FALSE;
}

function pugpig_rewrite_endpoint_url($url, $endpoint) {

  if (endsWith($url, "/")) {
    $url .= $endpoint;
  } else if (strpos($url, "?")) {
    // No permalinks so packager won't work
    $url .= "&" . str_replace(".","_", $endpoint) . "=true";
  } else {
    $url .= "/" . $endpoint;
  }  
  return $url;
}

function pugpig_is_pugpig_url() {
  return pugpig_is_pugpig_endpoint(PUGPIG_HTML_FILE_NAME);
}

function pugpig_is_pugpig_manifest() {
  return pugpig_is_pugpig_endpoint(PUGPIG_HTML_MANIFEST_NAME);
}

function pugpig_is_pugpig_package_xml() {
  return pugpig_is_pugpig_endpoint(PUGPIG_EDITION_PACKAGE_FILE_NAME);
}

function pugpig_is_pugpig_edition_atom_xml() {
  return pugpig_is_pugpig_endpoint(PUGPIG_ATOM_FILE_NAME);
}

function pugpig_rewrite_html_url($url) {
 return pugpig_rewrite_endpoint_url($url, PUGPIG_HTML_FILE_NAME);
}

/*
function pugpig_rewrite_atom_xml($url) {
 return pugpig_rewrite_endpoint_url($url, PUGPIG_ATOM_FILE_NAME);
}
*/
function pugpig_get_html_url($post) {
  // Ad bundles should return the path to the actual bundle HTML file - not the slug URL which then results in a redirect
  // (and a different relative URL to the ad bundle contents)
  if (isset($post) && $post->post_type == PUGPIG_AD_BUNDLE_POST_TYPE) {
    return pugpig_ad_bundle_url($post);
  } else {
    $atom_url = pugpig_permalink(get_permalink($post));
    return pugpig_rewrite_html_url($atom_url);
  }
}

function pugpig_get_canonical_url($post) {
  $url = '';
  // Ad bundles should return the path to the actual bundle HTML file - not the slug URL which then results in a redirect
  // (and a different relative URL to the ad bundle contents)
  if (isset($post) && $post->post_type == PUGPIG_AD_BUNDLE_POST_TYPE) {
    $url = pugpig_ad_bundle_url($post);
  } else {
    $url = pugpig_permalink(get_permalink($post));
  }

  if (substr($url, 0, 4) !== 'http') {
    $root_url = parse_url(get_option('siteurl'));
    return $root_url['scheme'] . '://' . $root_url['host'] . (isset($root_url['port']) ? ':' . $root_url['port'] : '') . $url;
  } else {
    return $url;
  }
}

function pugpig_get_manifest_url($post) {
  $manifest_url = pugpig_permalink(get_permalink($post));
  return pugpig_rewrite_endpoint_url($manifest_url, PUGPIG_HTML_MANIFEST_NAME);
}

function pugpig_get_package_manifest_url($edition, $strip_domain=TRUE) {
  //$edition_url = pugpig_permalink(get_permalink($edition));
  $ret = site_url() . "/editionfeed/".$edition->ID . "/" . PUGPIG_EDITION_PACKAGE_FILE_NAME;
  if ($strip_domain) $ret = pugpig_strip_domain($ret);
  return $ret;
    // return pugpig_rewrite_endpoint_url($edition_url, PUGPIG_EDITION_PACKAGE_FILE_NAME);
}

function pugpig_get_edition_atom_url($edition, $strip_domain=TRUE) {
  //$edition_url = pugpig_permalink(get_permalink($edition));
  $ret =  site_url() . "/editionfeed/" . $edition->ID . "/" . PUGPIG_ATOM_FILE_NAME;
  if ($strip_domain) $ret = pugpig_strip_domain($ret);
  return $ret;
  //return pugpig_rewrite_endpoint_url($edition_url, PUGPIG_ATOM_FILE_NAME);
}

function print_filters_for( $hook = '' ) {
    global $wp_filter;
    if( empty( $hook ) || !isset( $wp_filter[$hook] ) )
        return;

    print '<pre>';
    print_r( $wp_filter[$hook] );
    print '</pre>';
}

/************************************************************************
Get the attachment URL without the domain. This is needed for CDN plugins 
that might rewrite the URL to include the CDN domain. We want this to run very late.

We must NOT do this with the primary domain as if we do later filters will stick
the domain back WITH AN EXTRA /wordpress/ if not installed at root
************************************************************************/
function pugpig_wp_get_attachment_url($url) {

  $absoluteprefix = pugpig_get_root();
  if (startsWith($url, $absoluteprefix)) return $url;

  if (!empty($url)) $url = pugpig_strip_domain($url);
  return $url;
}

// Incoming URLs...
add_action('init', 'pugpig_add_endpoints');
function pugpig_add_endpoints() {

  $endpoint = str_replace(".","_", PUGPIG_ATOM_FILE_NAME);
  add_rewrite_tag('%'.$endpoint.'%','');
  add_rewrite_rule(  
      'editionfeed/([^/]+)/'.PUGPIG_ATOM_FILE_NAME.'$',  
      'index.php?post_type=pugpig_edition&p=$matches[1]&'. $endpoint.'=true',  
      "top");  

  $endpoint = str_replace(".","_", PUGPIG_EDITION_PACKAGE_FILE_NAME);
  add_rewrite_tag('%'.$endpoint.'%','');
  add_rewrite_rule(  
      'editionfeed/([^/]+)/'.PUGPIG_EDITION_PACKAGE_FILE_NAME.'$',  
      'index.php?post_type=pugpig_edition&p=$matches[1]&'. $endpoint.'=true',  
      "top");  

//global $wp_rewrite;
//$wp_rewrite->flush_rules();



  // Stop WordPress redirecting our lovely URLs and putting a / on the end
  if (pugpig_is_pugpig_url() || pugpig_is_pugpig_manifest() 
      || pugpig_is_pugpig_package_xml() || pugpig_is_pugpig_edition_atom_xml()) {

   // Turn off the CDN rewriting for Pugpig URLS. This is needed for W3 Total Cache
    define('DONOTCDN', 'PUGPIG');
    
    // Don't redirect - we don't want the slash on the end
    remove_filter('template_redirect', 'redirect_canonical');
    
    // Ensure we don't get URLs to different domains for attachments
    add_filter('wp_get_attachment_url', 'pugpig_wp_get_attachment_url', 2);
    add_filter('stylesheet_directory_uri', 'pugpig_wp_get_attachment_url', 2);
    add_filter('template_directory_uri', 'pugpig_wp_get_attachment_url', 2);
  }


  // We need these so that WordPress strips the bits off and still matches the post
  add_rewrite_endpoint(PUGPIG_HTML_FILE_NAME, EP_ALL); // Adds pugpig.html as default document to permalinks
  add_rewrite_endpoint(PUGPIG_HTML_MANIFEST_NAME, EP_ALL); // Adds manifest to permalinks
  add_rewrite_endpoint(PUGPIG_EDITION_PACKAGE_FILE_NAME, EP_ALL); // Adds package files
  add_rewrite_endpoint(PUGPIG_ATOM_FILE_NAME, EP_ALL); // Adds ATOM XML files
}

add_action('template_redirect', 'pugpig_catch_request');
function pugpig_catch_request() {

  // HTML manifest
  if (pugpig_is_pugpig_manifest()) {

    if (!is_singular()) {
      header('HTTP/1.1 403 Forbidden');
      echo "Not a valid pugpig request";
      exit();
    }

    header("Content-Type: text/cache-manifest");
    $post = get_queried_object();
    echo pugpig_build_post_manifest_contents($post);
    exit();
  } 

  // Package XML file
  if (pugpig_is_pugpig_package_xml()) {

   if (!is_singular()) {
      header('HTTP/1.1 403 Forbidden');
      echo "Not a valid package request";
      print_r($vars);
      exit();
    }  
    $post = get_queried_object();
    if ($post->post_type != PUGPIG_EDITION_POST_TYPE) {
      header('HTTP/1.1 403 Forbidden');
      echo "Not a valid package XML request - object is not an edition";
      exit();
    }     

    package_edition_package_list($post);
    exit();
  }

  if (pugpig_is_pugpig_edition_atom_xml()) {

   if (!is_singular()) {
      header('HTTP/1.1 403 Forbidden');
      echo "Not a singluar valid atom feed request";
      exit();
    }  
    $post = get_queried_object();
    if ($post->post_type != PUGPIG_EDITION_POST_TYPE) {
      header('HTTP/1.1 403 Forbidden');
      echo "Not a valid atom XML request - object is not an edition";
      exit();
    }      

    generate_edition_atom_feed($post->ID);
    exit();
  }
  
}

/* ==============================================================================
iTunes endpoint. Should move into a new module
============================================================================== */
include 'common/pugpig_subs.php';

define( 'PUGPIG_ITUNES_EDITION_CREDENTIALS', 'itunes_edition_credentials');

// Incoming URLs...
add_action('init', 'pugpig_itunes_add_endpoints');
function pugpig_itunes_add_endpoints() {
  add_rewrite_endpoint(PUGPIG_ITUNES_EDITION_CREDENTIALS, EP_ROOT); // iTunes editions credentials - need to post binary receipt
}

add_filter('request', 'pupgpig_itunes_manifest_filter_request');
function pupgpig_itunes_manifest_filter_request($vars) { 
    if (isset( $vars[PUGPIG_ITUNES_EDITION_CREDENTIALS])) {
      $vars[PUGPIG_ITUNES_EDITION_CREDENTIALS] = true;
    } 
    return $vars;
}

function pugpig_is_itunes_edition_credentials() {
  if (get_query_var(PUGPIG_ITUNES_EDITION_CREDENTIALS)) return TRUE;
  return FALSE;
}

function pugpig_itunes_edition_credentials() {
  $protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
  header($protocol . " 200 OK");

  $appStorePassword = get_option("pugpig_opt_itunes_inapp_shared_secret");
  $subscriptionPrefix = get_option("pugpig_opt_itunes_subscription_prefix");     
  $subscriptionArray = pugpig_get_itunes_subscription_array();

  $secret = pugpig_get_authentication_secret();

  $binaryReceipt = file_get_contents("php://input");

  echo pugpig_send_itunes_edition_credentials($appStorePassword, $subscriptionPrefix, $subscriptionArray, $binaryReceipt, $secret);
}

add_action('template_redirect', 'pugpig_itunes_catch_request');
function pugpig_itunes_catch_request() {
  if (pugpig_is_itunes_edition_credentials()) {
    pugpig_itunes_edition_credentials();
  } 
}

/************************************************************************
All pugpig posts to public
************************************************************************/

function pugpig_see_the_future( $query_obj = '' ) {
    global $wp_post_statuses;

    //echo 'FUNCTION RUNNING';
    // Make future posts and manifests visible to app
    if (pugpig_is_pugpig_url() || pugpig_is_pugpig_manifest() 
      || pugpig_is_pugpig_package_xml() || pugpig_is_pugpig_edition_atom_xml()) {
        $wp_post_statuses[ 'future' ]->public = true;
      }

    if (pugpig_is_pugpig_package_xml() || pugpig_is_pugpig_edition_atom_xml()) {
        // Need to see drafts for preview
        $wp_post_statuses[ 'draft' ]->public = true;
        $wp_post_statuses[ 'pending' ]->public = true;
    }

}

if ( ! is_admin( ) ) {
        add_action( 'pre_get_posts', 'pugpig_see_the_future' );
}

