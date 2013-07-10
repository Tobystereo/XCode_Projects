<?php
/**
 * @file
 * Pugpig Article Rewrite
 */
?><?php
/*

Licence:
==============================================================================
(c) 2011, Kaldor Holdings Ltd
This module is released under the GNU General Public License.
See COPYRIGHT.txt and LICENSE.txt

 */?><?php

/************************************************************************
Change the permalink to rewrite outgoing links
*************************************************************************/

add_filter('post_link','pugpig_permalink');
add_filter('page_link','pugpig_permalink');
add_filter('category_link','pugpig_permalink');
add_filter('tag_link','pugpig_permalink');
add_filter('author_link','pugpig_permalink');
add_filter('day_link','pugpig_permalink');
add_filter('month_link','pugpig_permalink');
add_filter('year_link','pugpig_permalink');

function pugpig_permalink($permalink, $endpoint = null) {

  // Ignore this if we are outputting a feed
  if (pugpig_is_pugpig_url()) {
    $permalink = pugpig_rewrite_html_url($permalink);
  }
  
  return $permalink;
}

/************************************************************************
Show the Pugpig theme if required
*************************************************************************/
add_filter( 'template', 'pugpig_theme_switcher' );
add_filter( 'stylesheet', 'pugpig_theme_switcher' );

function pugpig_theme_switcher($theme) {

  // See if we have a setting for theme override
  $theme_switch = get_option("pugpig_opt_theme_switch");
  if (empty($theme_switch)) return $theme;

  if (pugpig_is_pugpig_url()) {
    return $theme_switch;
  }

  return $theme;
}

/************************************************************************
Register a buffer handlers so we can search/replace all the output
************************************************************************/
add_action('template_redirect','pugpig_ob_start');
function pugpig_ob_start() {

    if (pugpig_is_pugpig_url()) {
        ob_start('pugpig_rewrite_content');      
    }

/*
    if (!is_admin() && !is_preview()) {
        ob_start('pugpig_rewrite_content');
    }
*/
}

/************************************************************************
Get the root so we can make relative links
We want everything up to the third slash
************************************************************************/
function pugpig_get_root() {
  $url = get_option('siteurl');

  while(substr_count($url, '/') > 2) { // all we need is the :// from the protocol
    $array = explode('/', $url);
    array_pop($array);
    $url = implode('/', $array);
  }
  return $url;
}

/************************************************************************
Remove domain from URLs that could be root relative
************************************************************************/
function pugpig_rewrite_content($content) {
  // This is no longer required as the link to the manifest is in the ATOM feed
  
  // $id = 'post-' . get_the_ID() . ".manifest";
  // $manifest = pugpig_path_to_rel_url(PUGPIG_MANIFESTPATH . $id);
  // $pattern = '/<html/i';
  // $content = preg_replace($pattern,'<html manifest="' . $manifest . '"',$content);

  // TODO: Need to make this less aggresive - we want absolute URLs for non html cases
  //if (strpos($content, '<html') !== FALSE) {
  $absoluteprefix = pugpig_get_root();
  $content = str_replace($absoluteprefix, '', $content);
    // $content = str_replace(" site", " Puggers site", $content);
    //}
  $content = pugpig_rewrite_wpcontent_links($content);
  return $content;
}


/************************************************************************
Returns a block of markup with image URLs fixed
This will also remove a ?ver=123 from the query string
************************************************************************/
function pugpig_rewrite_wpcontent_links($markup) { 
 
   $regex = '#([\'"])('.pugpig_strip_domain(site_url()).'/wp-content/.*?)\1#i';
   if (preg_match_all($regex, $markup, $matches)) {

     foreach ($matches[2] as $src) {

        $new_uri = url_create_deep_dot_url($src);

        // Strip version number?
        $new_uri = remove_query_arg( 'ver', $new_uri );
        $markup= str_replace($src, $new_uri, $markup); 
     }
   }

   if (preg_match_all('#([\'"])(/.*?/pugpig_index.html)\1#i', $markup, $matches)) {
     foreach ($matches[2] as $src) {
        $new_uri = url_create_deep_dot_url($src);
        $markup= str_replace($src, $new_uri, $markup); 
     }
   }

   return $markup;
}
