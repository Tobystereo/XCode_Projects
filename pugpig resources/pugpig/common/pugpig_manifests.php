<?php
/**
 * @file
 * Pugpig Feed Generation
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
Output the date in RFC3339 format
 * **********************************************************************/
function pugpig_date3339($timestamp=0) {

    if (!$timestamp) {
        $timestamp = time();
    }
    $date = date('Y-m-d\TH:i:s', $timestamp);

    $matches = array();
    if (preg_match('/^([\-+])(\d{2})(\d{2})$/', date('O', $timestamp), $matches)) {
        $date .= $matches[1] . $matches[2] . ':' . $matches[3];
    }
    else {
        $date .= 'Z';
    }
    return $date;
}

/************************************************************************
Output the date in RFC2822 format
 * **********************************************************************/
function pugpig_date2822($timestamp=0) {
    if (!$timestamp) {
        $timestamp = time();
    }
    $date = date('r', $timestamp);


    return $date;
}

/************************************************************************
Output the date in Kindle format
 * **********************************************************************/
function pugpig_date_kindle($timestamp=0) {
    if (!$timestamp) {
        $timestamp = time();
    }
    $date = date('Y-d-m', $timestamp);


    return $date;
}

/************************************************************************
Take the domain off a URL
 * **********************************************************************/
function pugpig_strip_domain($path) {
    $u = parse_url($path);

    if (array_key_exists("path", $u)) {
      $output = $u["path"];
    } else {
      $output = "";
    }
    if (array_key_exists("query", $u)) {
      $output .= '?' . $u["query"];
    }

    // TODO: Do we care about ?query and #fragment?
    return $output;
}


/************************************************************************
* Take a manifest and add the CDN to all the assets
*************************************************************************/
function pupig_add_cdn_to_manifest_lines($manifest_contents, $cdn) {
    $ret = '';
    $lines = preg_split('/\n/m', $manifest_contents, 0);
    foreach($lines as $line) {
      if (!in_array($line, array('', '*', 'CACHE MANIFEST', 'CACHE', 'CACHE:', 'NETWORK','NETWORK:'))) {
        preg_match('/\s*([^#]*)/', $line, $matches);
        if (count($matches) > 1) {
          // Only include CDN prefix if the URL starts with a /
          if ($matches[1] != '*' && trim($matches[1]) != '' && startsWith($matches[1], '/'))
            $line = $cdn .  $line;
        } 
      }
      $ret .= $line . "\n";
    }
    return $ret;
}

/************************************************************************
Try to make the URLs relative
 * **********************************************************************/
function pugpig_path_to_rel_url($path) {
  return pugpig_strip_domain(pugpig_path_to_abs_url($path));
}

/************************************************************************
Container for the OPDS XML
 * **********************************************************************/
function pugpig_get_opds_container($edition_ids, $internal = FALSE, $atom_mode = FALSE, $extra_comments = array()) {
  $d = new DomDocument('1.0', 'UTF-8');
  $feed = $d->createElement('feed');
  $feed->setAttribute('xmlns', 'http://www.w3.org/2005/Atom');
  $feed->setAttribute('xmlns:dcterms', 'http://purl.org/dc/terms/');
  $feed->setAttribute('xmlns:opds', 'http://opds-spec.org/2010/catalog');
  $feed->setAttribute('xmlns:app', 'http://www.w3.org/2007/app');

  $comment = ($atom_mode ? "Atom" : "Package");
  if ($internal) {
    $comment .= ' Internal Feed';
  } else {
    $comment .= ' External Feed';
  }

  $feed->appendChild($d->createComment(' ' . $comment . " - Generated: " . date(DATE_RFC822) . ' '));
  foreach ($extra_comments as $extra_comment) {
    $feed->appendChild($d->createComment(' ' . $extra_comment . ' '));
  }
  $feed->appendChild(newElement($d, 'id', pugpig_get_atom_tag('opds')));

  $link = $d->createElement('link');
  $link->setAttribute('rel', 'self');
  $link->setAttribute('type', 'application/atom+xml;profile=opds-catalog;kind=acquisition');
  $link->setAttribute('href', pugpig_self_link());
  $feed->appendChild($link);

  $author = $d->createElement('author');
  $author->appendChild($d->createElement('name', 'Pugpig'));
  $feed->appendChild($author);

  $feed->appendChild($d->createElement('title', 'All Issues'));
  $feed->appendChild($d->createElement('subtitle', $comment));

  $editions = array();
  $updated = 1; // 1 second into UNIX epoch (0 == current time?)

  foreach ($edition_ids as $key => $edition_id) {
    $editions[] = $edition_id;
    $edition_updated = pugpig_get_edition_update_date(pugpig_get_edition($edition_id, TRUE, !$atom_mode), $atom_mode);
        
    if ($edition_updated > $updated)
      $updated = $edition_updated;
  }

  $feed->appendChild(newElement($d, 'updated', pugpig_date3339($updated) ));

  foreach ($editions as $edition) {
    $edition = pugpig_get_edition($edition, TRUE, !$atom_mode);
    if (!$atom_mode && array_key_exists('zip', $edition) && $edition['zip'] == '')
      $feed->appendChild($d->createComment(' ' . ucfirst($edition['status']) . ' ' . pugpig_get_atom_tag($edition['key']) . ' does not have an up to date package '));
    else
      $feed->appendChild(pugpig_get_opds_entry($d, $edition, $internal, $atom_mode));
  }

  $d->appendChild($feed);

  return $d;
}

/************************************************************************
************************************************************************/
function pugpig_get_edition_update_date($edition, $atom_mode) {
  // TODO: Ensure we get the edition object if we've just been given a key
  // The line below doesn't work - get's the wrong atom mode

  if ($atom_mode) {
    return $edition['modified'];
  } else {
    return $edition['packaged'];
  }

}

/************************************************************************
An ODPS entry for an edition
We need the atom_mode to determine the updated date
************************************************************************/
function pugpig_get_opds_entry($d, $edition, $internal = FALSE, $atom_mode = FALSE) {
  $entry = $d->createElement('entry');

  $entry->appendChild(newElement($d, 'title', $edition['title']));
  $entry->appendChild(newElement($d, 'id', pugpig_get_atom_tag($edition['key'])));
  $entry->appendChild(newElement($d, 'updated', pugpig_date3339(pugpig_get_edition_update_date($edition, $atom_mode))));

  if (!empty($edition['author'])) {
    $author = $d->createElement('author');
    $author->appendChild(newElement($d, 'name', $edition['author']));
    $entry->appendChild($author);
  }

  if (!empty($edition['date'])) {
    $entry->appendChild(newElement($d, 'dcterms:issued', $edition['date']));
  }

  // Show draft editions
  if ($edition['status'] != "published") {
    $appcontrol = $d->createElement('app:control');
    $appcontrol->appendChild($d->createElement('app:draft', 'yes'));
    $entry->appendChild($appcontrol);
  }

  $summary = newElement($d, 'summary', $edition['summary']);
  $summary->setAttribute('type', 'text');
  $entry->appendChild($summary);

  $link_img = $d->createElement('link');
  $link_img->setAttribute('rel', 'http://opds-spec.org/image');
  $link_img->setAttribute('type', 'image/jpg');
  $link_img->setAttribute('href', $edition['thumbnail']);
  $entry->appendChild($link_img);

  $link_atom = $d->createElement('link');
  $link_atom->setAttribute('type', array_key_exists('url_type', $edition) ? $edition['url_type'] : 'application/atom+xml');
  $link_atom->setAttribute('href', $edition['url']);
  
  // $prices = pugpig_get_edition_prices($edition);
    
  if (empty($edition['price']) || $edition['price'] == 'FREE') {
    // Free edition
    $entry->appendChild($d->createComment("Free edition"));
    $link_atom->setAttribute('rel', 'http://opds-spec.org/acquisition');
  
  } elseif ($edition['status'] != "published") {
    // Act as if all are DRAFT editions free
    $entry->appendChild($d->createComment("Treating paid for draft edition as free"));
    $link_atom->setAttribute('rel', 'http://opds-spec.org/acquisition'); // TODO: open-access?
  } else {
    // Paid for edition
    $entry->appendChild($d->createComment("Paid for edition"));
    $link_atom->setAttribute('rel', 'http://opds-spec.org/acquisition/buy');
  }

  $entry->appendChild($link_atom);

  $link_atom = $d->createElement('link');
  $link_atom->setAttribute('rel', 'alternate');
  $link_atom->setAttribute('type', array_key_exists('url_type', $edition) ? $edition['url_type'] : 'application/atom+xml');
  $link_atom->setAttribute('href', $edition['url']);
  $entry->appendChild($link_atom);

 // Enclosures
  if (isset($edition['links'])) {
    foreach ($edition['links'] as $link)  {
      $link_enc = $d->createElement('link');
      $link_enc->setAttribute('rel', $link['rel']);
      $link_enc->setAttribute('title', $link['title']);
      $link_enc->setAttribute('type', $link['type']);
      $link_enc->setAttribute('href', $link['href']);
      $entry->appendChild($link_enc);
    }
  }

  return $entry;
}

/*
// No longer using this. A simple PAID or FREE replaces it 
function pugpig_get_edition_prices($edition) {
  $result = array();

  if (isset($edition) && array_key_exists('price', $edition) && isset($edition['price']) && $edition['price'] != '') {
    $prices = explode(',', $edition['price']);

    foreach($prices as $price) {
      $price = trim($price);
      $currency = 'GBP';
      $amount = mb_substr($price, 1);

      switch (mb_substr($price, 0, 1)) {
        case '$': $currency = 'USD'; break;
        case '£': $currency = 'GBP'; break;
        case '€': $currency = 'EUR'; break;
        default: 
          $currency = mb_substr($price, 0, 3);
          $amount = mb_substr($price, 3); // default
      }

      if (is_numeric($amount) && $amount > 0) {
        $result[] = array(
          'currency' => $currency,
          'value' => $amount
        );
      }
    }
  }

  return $result;
}
*/

/************************************************************************
The top level container for an edition ATOM feed
 * **********************************************************************/
function pugpig_get_atom_container($edition_id, $include_hidden = FALSE) {
  $edition = pugpig_get_edition($edition_id, $include_hidden);

  $d = new DomDocument('1.0', 'UTF-8');
  $feed = $d->createElement('feed');
  $feed->setAttribute('xmlns', 'http://www.w3.org/2005/Atom');
  $feed->setAttribute('xmlns:app', 'http://www.w3.org/2007/app');

  $feed->appendChild(newElement($d, 'id', pugpig_get_atom_tag($edition['key'])));

  $comment = ' ' . ($include_hidden ? "Atom Including Hidden Files - " : "Atom Contents Feeds - ");
  $comment .= "Generated: " . date(DATE_RFC822) . ' ';

  $feed->appendChild($d->createComment($comment));


  $link = $d->createElement('link');
  $link->setAttribute('rel', 'self');
  $link->setAttribute('type', 'application/atom+xml');

  $link->setAttribute('href', pugpig_self_link());
  $feed->appendChild($link);

  $feed->appendChild(newElement($d, 'title', $edition['title']));
  $feed->appendChild(newElement($d, 'updated', pugpig_date3339(pugpig_get_edition_update_date($edition, TRUE))));

  $author = $d->createElement('author');
  $author->appendChild($d->createElement('name', 'Pugpig'));
  $feed->appendChild($author);

  foreach ($edition['page_ids'] as $page_id) {
    $page = pugpig_get_page($page_id);
    // We only ever want published pages in these feeds
    if ($page['status'] == 'published') {
      $entry = pugpig_get_atom_entry($d, $page, $edition);
      $feed->appendChild($entry);
    }
  }

  $d->appendChild($feed);

  return $d;
}

/************************************************************************
An ATOM entry for a post in an edition
 * **********************************************************************/
function pugpig_get_atom_entry($d, $page, $edition) {

  $entry = $d->createElement('entry');

  $entry->appendChild(newElement($d, 'title', strip_tags($page['title'])));
  $entry->appendChild(newElement($d, 'id', pugpig_get_atom_tag('page-' . $page['id'])));
  $entry->appendChild(newElement($d, 'updated', pugpig_date3339( $page['modified'] )));
  $entry->appendChild(newElement($d, 'published', pugpig_date3339( $page['date'] )));

  // Author
  if (isset($page['author']) && !empty($page['author'])) {
    $author = $d->createElement('author');
    $author->appendChild($d->createElement('name', $page['author']));
    $entry->appendChild($author);
  }

  // Show draft editions
  if ($page['status'] != "published") {
    $appcontrol = $d->createElement('app:control');
    $appcontrol->appendChild($d->createElement('app:draft', 'yes'));
    $entry->appendChild($appcontrol);
  }

  $summary = newElement($d, 'summary', $page['summary']);
  $summary->setAttribute('type', 'text');
  $entry->appendChild($summary);

  // Categories should be first for the default client ToC to work normally
  foreach ($page['categories'] as $cat)  {
    $category = $d->createElement('category');
    $category->setAttribute('scheme', 'http://schema.pugpig.com/section');
    $category->setAttribute('term', $cat);
    $entry->appendChild($category);
  }

  // Page Type 
  $category = $d->createElement('category');
  $category->setAttribute('scheme', 'http://schema.pugpig.com/pagetype');
  $category->setAttribute('term', $page['type']);
  $entry->appendChild($category);

  // Level
  $category = $d->createElement('category');
  $category->setAttribute('scheme', 'http://schema.pugpig.com/level');
  $category->setAttribute('term', $page['level']);
  $entry->appendChild($category);

  if (isset($page['custom_categories'])) {
      foreach ($page['custom_categories'] as $scheme=>$val)  {
        $category = $d->createElement('category');
        $category->setAttribute('scheme', "http://schema.pugpig.com/$scheme");
        $category->setAttribute('term', $val);
        $entry->appendChild($category);      
      }
  }

  $link_man = $d->createElement('link');
  $link_man->setAttribute('rel', 'related');
  $link_man->setAttribute('type', 'text/cache-manifest');
  $link_man->setAttribute('href', $page['manifest']);
  $entry->appendChild($link_man);

  $link_man = $d->createElement('link');
  $link_man->setAttribute('rel', 'alternate');
  $link_man->setAttribute('type', 'text/html');
  $link_man->setAttribute('href', $page['url']);
  $entry->appendChild($link_man);
  
  // Take the sharing link from the edition if it isn't on the page
  $sharing_link = '';
  if (isset($page['sharing_link'])) $sharing_link = $page['sharing_link'];
  if ($sharing_link == '' && isset($edition['sharing_link'])) $sharing_link = $edition['sharing_link'];
  
  if (!empty($sharing_link)) {
    $link_man = $d->createElement('link');
    $link_man->setAttribute('rel', 'bookmark');
    $link_man->setAttribute('type', 'text/html');
    $link_man->setAttribute('href', $sharing_link);
    $entry->appendChild($link_man);
  }

  // Enclosures
  if (isset($page['links'])) {
    foreach ($page['links'] as $link)  {
      $link_enc = $d->createElement('link');
      $link_enc->setAttribute('rel', $link['rel']);
      $link_enc->setAttribute('title', $link['title']);
      $link_enc->setAttribute('type', $link['type']);
      $link_enc->setAttribute('href', $link['href']);
      $entry->appendChild($link_enc);
    }
  }

  return $entry;
}

function pugpig_self_link() {
  $serverrequri = pugpig_request_uri();

  if (!isset($serverrequri)) {
    $serverrequri = $_SERVER['PHP_SELF'];
  }

  return pugpig_get_current_base_url() . $serverrequri;
}

/************************************************************************
 * **********************************************************************/
function pugpig_abs_link($pugpig_path, $cdn = "") {
  $base = base_path();
  // If it starts with /<base>/ then we shouldn't be adding the base
  if (substr($pugpig_path, 0, strlen($base)) == $base)
    $base = '';
  else
    $pugpig_path = trim($pugpig_path, "/"); // base has suffix "/" so remove prefix from path if it exists

  $cdn = trim($cdn, "/");
  $serverrequri = pugpig_request_uri();

  if (!isset($serverrequri)) {
    $serverrequri = $_SERVER['PHP_SELF'];
  }

  $s = empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : "";
  $protocol = substr(
    strtolower($_SERVER["SERVER_PROTOCOL"]),
    0,
    strpos($_SERVER["SERVER_PROTOCOL"], "/")
  ) . $s;
  $port = ($_SERVER["SERVER_PORT"] == "80") ? "" : (":" . $_SERVER["SERVER_PORT"]);

  if ($cdn != "") {
   return  $cdn . $base . $pugpig_path;
  }

  return ($protocol . "://" . $_SERVER['SERVER_NAME'] . $port . $base . $pugpig_path);
}

/************************************************************************
The top level feed for the Kindle RSS
 * **********************************************************************/
function pugpig_get_rss_root($edition_id) {
  $edition = pugpig_get_edition($edition_id, FALSE);
  
 
  $d = new DomDocument('1.0', 'UTF-8');
  $feed = $d->createElement('rss');
  $feed->setAttribute('version', '2.0');

  $channel = $d->createElement('channel');
  $feed->appendChild($channel);

  $channel->appendChild(newElement($d, 'title', $edition['title']));
  $channel->appendChild(newElement($d, 'link', pugpig_self_link()));
  $channel->appendChild(newElement($d, 'pubDate', pugpig_date_kindle(pugpig_get_edition_update_date($edition, TRUE))));
  
  $item = null;
  
  foreach (pugpig_get_kindle_page_array($edition) as $page) {
    if ($page['level'] == 1) {
      $item = $d->createElement('item');
      $abs_path = pugpig_abs_link('editions/' .pugpig_get_atom_tag($edition['key']) . '/data/' . $page['id'] . '/kindle.rss');
      $item->appendChild(newElement($d, 'link', $abs_path));
      // $channel->appendChild($item);
    }
    // If we have a Level 1 node, attach it once we know we have a child
    // Having a section without any children breaks everything 
    if ($page['level'] > 1 && $item != null) {
      // print_r($page['title']);
      $channel->appendChild($item);
      $item = null;
    }
  }

  $d->appendChild($feed);
  return $d;
}

/************************************************************************
Section feed for the kindle RSS
* **********************************************************************/
function pugpig_get_rss_section($edition_id, $nid) {

  // print_r('pugpig_get_rss_section(' . $edition_id . ',' . $nid . ')'); 
    
  $edition = pugpig_get_edition($edition_id, FALSE);
  $section = pugpig_get_page($nid);

  $d = new DomDocument('1.0', 'UTF-8');
  $feed = $d->createElement('rss');
  $feed->setAttribute('version', '2.0');

  $channel = $d->createElement('channel');
  $feed->appendChild($channel);


  $channel->appendChild(newElement($d, 'title', $section['title']));  
  $in_section = false;
  
  foreach (pugpig_get_kindle_page_array($edition) as $page) {
       
    if ($page['id'] == $nid && $page['level'] == 1 && !$in_section) {
      $in_section = true;

    } else {
            
      if ($page['level'] == 1) {
        // Bail when we hit the section higher level page
        $in_section = false;
      } else if ($in_section) {
        $item = $d->createElement('item');
        $abs_path = pugpig_abs_link('editions/' .pugpig_get_atom_tag($edition['key']) . '/data/' . $page['id'] . '/kindle.html');  
        $item->appendChild(newElement($d, 'link', $abs_path));
        $channel->appendChild($item);      
      }
    
    }
 
 }

  $d->appendChild($feed);
  return $d;
}


/************************************************************************
Gets all files that match a pattern
 * **********************************************************************/
// Get all files in a directory and all of its children
if ( ! function_exists('glob_recursive'))
{
    // Does not support flag GLOB_BRACE
    function glob_recursive($pattern, $flags = 0)
    {
        $files = glob($pattern, $flags);
 
         // Ignore FALSE if open_basedir is set
        if ($files === FALSE && ini_get('open_basedir')) $files = array();

        if ( $dirs = glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) ) {

          // Ignore FALSE if open_basedir is set
          if ($dirs === FALSE && ini_get('open_basedir')) $dirs = array();

          foreach ($dirs as $dir) { 
            $files = array_merge($files, glob_recursive($dir.'/'.basename($pattern), $flags));  
          }
        }
        
        return $files;
    }
}

/************************************************************************
Gets all the files in a directory
 * **********************************************************************/
function _pugpig_directory_get_files($directory) {
  if (substr($directory, -1) != '/')
    $directory = $directory . '/';

  $match = $directory . "*.*";
      
  $f = glob_recursive($match, GLOB_ERR);
  // print_r(implode($f, "<br />")); exit();
  return $f;
}

/************************************************************************
Generate a fragment of a manifest file for all static assets in the current
theme
 * **********************************************************************/
function pugpig_theme_manifest_string($theme_path, $theme_dir, $theme_name = '', $exclude_paths = array()) {

  // Normalise the slashes
  $theme_dir = str_replace(DIRECTORY_SEPARATOR, '/', $theme_dir);

  if (substr($theme_dir, -1) != '/')
    $theme_dir = $theme_dir . '/';

  $cache = array();
  if ($theme_name != '') {
    array_push($cache ,"# Theme assets: " . $theme_name . "\n");
  }
    
  if ($theme_path != '' && substr($theme_path, -1) != '/')
    $theme_path = $theme_path . '/';

  // Get all the static assets for the theme
  $separator = (substr($theme_dir, -1) == '/') ? '' : '/';
  // array_push($cache, "# From Path: " . $theme_path . "\n");
  // array_push($cache, "# From Dir: " . $theme_dir . "\n");
  

  $c = 0;
  
  $files = _pugpig_directory_get_files($theme_dir);
  array_push($cache, "# Total Directory File Count: " . count($files) . "\n");

  foreach ($files as $file) {
    if (!is_dir($file) && !strpos($file, '/.svn/')) {
      if ($file != "./manifest.php" && substr($file, 0, 1) != ".") {
        //$stamp = '?t=' . $file->getMTime();
       //$clean_path = str_replace($file, rawurlencode($file), $file) ;
       $clean_path = str_replace(DIRECTORY_SEPARATOR, '/', $file);

       $clean_url = str_replace($theme_dir, $separator, $clean_path);
       $clean_url = str_replace(DIRECTORY_SEPARATOR, '/', $clean_url);

       if (!in_array($clean_url, $exclude_paths) && isAllowedExtension($file)) {         
           $parts = explode("/", $theme_path . $clean_url);
           $parts = array_map("rawurlencode", $parts);
           $clean = implode("/", $parts);
           array_push($cache, $clean . "\n");
           $c++;
        } else {
           // array_push($cache, "# Skipped: " . $theme_path . $clean_url . "\n");
        }
      }  
    }

  }
  
 
  array_push($cache, "# Got " . $c . " assets"  . "\n");


  $output = "";
  foreach ($cache as $file) {
    $output .= $file;
  }
    
  return $output;
}

/*
 * http://api.drupal.org/api/drupal/includes--bootstrap.inc/function/request_uri/7
 */
function pugpig_request_uri() {

  if (isset($_SERVER['REQUEST_URI'])) {
    $uri = $_SERVER['REQUEST_URI'];
  }
  else {
    if (isset($_SERVER['argv'])) {
      $uri = $_SERVER['SCRIPT_NAME'] . '?' . $_SERVER['argv'][0];
    }
    elseif (isset($_SERVER['QUERY_STRING'])) {
      $uri = $_SERVER['SCRIPT_NAME'] . '?' . $_SERVER['QUERY_STRING'];
    }
    else {
      $uri = $_SERVER['SCRIPT_NAME'];
    }
  }
  // Prevent multiple slashes to avoid cross site requests via the Form API.
  $uri = '/' . ltrim($uri, '/');

  return $uri;
}

function newElement($d, $name, $value) {
  $element = $d->createElement($name);
  $element->appendChild($d->createTextNode($value));
  return $element;
}
