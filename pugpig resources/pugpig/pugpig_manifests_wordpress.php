<?php
/**
 * @file
 * Pugpig Manifest Mappings for WordPress
 */
?><?php
/*

Licence:
==============================================================================
(c) 2011, Kaldor Holdings Ltd
This module is released under the GNU General Public License.
See COPYRIGHT.txt and LICENSE.txt

 */?><?php
 
include 'common/pugpig_manifests.php';
include 'common/pugpig_packager.php';

function generate_edition_atom_feed($edition_id, $include_hidden = FALSE) {

    // Check it exists
    $edition = get_post($edition_id);
    if (empty($edition)) {
        header('HTTP/1.1 404 Not Found');
        exit;  
    }


    if ($edition->post_status != 'publish') {
      if (FALSE && !pugpig_is_internal_user()) {
        header('HTTP/1.1 403 Forbidden');
        exit;
      }  
    }

    header('Content-Type: ' . feed_content_type('atom') . '; charset=' . get_option('blog_charset'), true);
    header('Content-Disposition: inline');
    $d = pugpig_get_atom_container($edition_id, $include_hidden);  
    $d->formatOutput = TRUE;

    echo $d->saveXML();
}

function _package_final_folder() {
  return PUGPIG_MANIFESTPATH . 'packages/';
}

function get_edition_package($edition_id) {
  $edition = get_post($edition_id);      
  $edition_key = pugpig_get_full_edition_key($edition);
  //print_r("*** $edition_key ***");
  $package_search_expression = PUGPIG_MANIFESTPATH . 'packages/' . $edition_key . '-package-*.xml';  
  return _package_get_most_recent_file($package_search_expression);

}

function get_edition_package_url($edition_id) {
  $packaged_timestamp = get_edition_package_timestamp($edition_id);
  if (empty($packaged_timestamp)) return "";
  $edition = get_post($edition_id);      
  return pugpig_strip_domain(pugpig_get_package_manifest_url($edition));

/*
  $wp_ud_arr = wp_upload_dir();
  $package_xml =  get_edition_package($edition_id);
  if (strlen($package_xml) > 0) {
    $url = $wp_ud_arr['baseurl'] . '/pugpig-api/packages' . substr($package_xml, strrpos($package_xml, '/'));
    $url = pugpig_strip_domain($url);
  } else {
    $url = '';
  }
  
  return $url;
*/
}

function get_edition_package_timestamp($edition_id) {
  $package_xml =  get_edition_package($edition_id);
  
  return filemtime($package_xml);
  
}

// Returns the latest XML package descriptor
function package_edition_package_list($edition) {
  $edition_tag = pugpig_get_full_edition_key($edition);
  $wp_ud_arr = wp_upload_dir();

  if ($edition->post_status == 'publish') {
    $cdn = get_option('pugpig_opt_cdn_domain');
  } else {
    $cdn = '';
  }

  $xml = _package_edition_package_list_xml(
    PUGPIG_MANIFESTPATH . 'packages/', 
    $edition_tag, 
    pugpig_strip_domain($wp_ud_arr['baseurl']) . '/pugpig-api/packages/', 
    $cdn,
    'string',
    '*',
    PUGPIG_ATOM_FILE_NAME
    );

  if (is_null($xml)) {
    header('HTTP/1.0 404 Not Found');
    echo "This page does not exist. Maybe edition $edition_tag has no packages.";
    exit;
  }

  header('Content-Type: application/pugpigpkg+xml; charset=utf-8');
  header('Content-Disposition: inline');

  print $xml;
  return NULL;
}


function pugpig_get_edition($id, $include_hidden = TRUE, $use_package = TRUE) {

  $edition = get_post($id);

  $attachment_id = get_post_meta( $edition->ID, '_thumbnail_id', true);
  $thumbnail = BASE_URL . "common/images/nocover.jpg";
  if (!empty($attachment_id)) {
    $thumbnail =  wp_get_attachment_url($attachment_id);
  }
  $thumbnail = pugpig_strip_domain($thumbnail);

  // Use the CDN in the feed for the cover if the edition is published
  if ($edition->post_status == 'publish') {
    $cdn = get_option('pugpig_opt_cdn_domain');
    if (!empty($cdn)) $thumbnail = $cdn . $thumbnail;
  }
  
  $url = pugpig_strip_domain(pugpig_get_edition_atom_url($edition));
  $url_type = 'application/atom+xml';
  

 $packaged_timestamp = '';
  if ($use_package) {
    $url_type = 'application/pugpigpkg+xml';

    // TODO: Check if something exists.
    // If yes, use pugpig_get_package_manifest_url
    $packaged_timestamp = get_edition_package_timestamp($edition->ID);

    $url = get_edition_package_url($edition->ID);
    if ($url != '') {
      $packaged_timestamp = get_edition_package_timestamp($edition->ID);
      $url = pugpig_strip_domain($url);
    }
  }

  $custom = get_post_custom($edition->ID);

  $price = "FREE";
  if (!isset($custom["edition_free"])) {
    $price =  "PAID";
  }
  


  $item = array(
    'id' => $edition->ID,
    'title' => $edition->post_title,
    'key' => pugpig_get_full_edition_key($edition), // Need edition value for KEY
    'summary' => $edition->post_excerpt,
    'page_ids' => pugpig_get_edition_array(get_post_custom($edition->ID)), 
    'author' => get_post_meta($edition->ID, 'edition_author', true),
    'price' => $price,
    'date' => get_post_meta( $edition->ID, 'edition_date', true ),
    'status' => ($edition->post_status == 'publish' ? 'published' : $edition->post_status),
    'modified' => strtotime($edition->post_modified),
    'thumbnail' => $thumbnail,
    'url' => $url,
    'url_type' => $url_type,
    'sharing_link' => get_post_meta( $edition->ID, 'edition_sharing_link', true ),
    'zip' => $url,
    'packaged' => $packaged_timestamp
  );

  return $item;
}

function pugpig_get_full_edition_key($edition) {
  $edition_prefix = pugpig_get_issue_prefix();
  return $edition_prefix . get_post_meta($edition->ID, 'edition_key', true);
}

// Returns an array of enclosures to include in the entry
function pugpig_get_links($post) {
  // TODO: Make this a filter
  $links = array();
  $links = apply_filters('pugpig_add_link_items', $links, $post);

  // Fix any links that might not be relative
  if (isset($links)) foreach ($links as &$link) {
    $link['href'] = url_create_deep_dot_url($link['href']);
  }

  return $links;
}

function pugpig_get_page($id) {
  $post = get_post($id);

  // Get the link for sharing
  // TODO: Allow post specific values in future
  // Get canonical URL for sharing (e.g. Twitter, Facebook)
  $sharing_link = apply_filters( 'pugpig_page_sharing_link', pugpig_get_canonical_url($post), $post );
  //$sharing_link = 'http://pugpig.com/1';

  $status = $post->post_status;

  // We want everything except draft posts in an edition
  if ($status != 'draft' && $status != 'trash') $status = 'published'; // We expect the word 'published'

  $page = array(
    'id' => $post->ID,
    'title' => pugpig_get_feed_post_title($post),
    'summary' =>  pugpig_get_feed_post_summary($post),
    'status' => $status,
    'author' => pugpig_get_feed_post_author($post),
    'modified' => strtotime ($post->post_modified),
    'date' => strtotime ($post->post_date),
    'type' => $post->post_type,
    'categories' => pugpig_get_feed_post_categories($post),
    'custom_categories' => pugpig_get_feed_post_custom_categories($post),
    'links' => pugpig_get_links($post),
    'url' => url_create_deep_dot_url(pugpig_strip_domain(pugpig_get_html_url($post))),
    'manifest' => url_create_deep_dot_url(pugpig_strip_domain(pugpig_get_manifest_url($post))),
    'level' => pugpig_get_feed_post_level($post),
    'sharing_link' => $sharing_link
  );

  return $page;
}

function pugpig_get_atom_tag($key = '') {
  return $key;
}
