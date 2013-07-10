<?php
/**
 * @file
 * Pugpig Packager
 */
?><?php
/*

Licence:
==============================================================================
(c) 2011, Kaldor Holdings Ltd
This module is released under the GNU General Public License.
See COPYRIGHT.txt and LICENSE.txt

 */?><?php

include_once "pugpig_interface.php";  
include_once "pugpig_utilities.php";
include_once "pugpig_manifests.php";  
include_once "pugpig_feed_parsers.php";  
include_once "multicurl.php";  
include_once "url_to_absolute/url_to_absolute.php";  
include_once "url_to_absolute/add_relative_dots.php";  

function _pugpig_package_get_asset_urls_from_manifest($manifest_contents, $entries = array(), $base_url) {
  
  $found_manifest_start = FALSE;
  $lines = preg_split('/\n/m', $manifest_contents, 0, PREG_SPLIT_NO_EMPTY);
  foreach($lines as $line) {
    preg_match('/\s*([^#]*)/', $line, $matches);
    if (count($matches) > 1) {
      $m = trim($matches[1]);

      // Ignore all lines until we find the "CACHE MANIFEST one"
      // Can't do this as it is currently used to scan partial manifests too
      /*
      if ($m == "CACHE MANIFEST") $found_manifest_start = TRUE;
      if (!$found_manifest_start) {
        continue;
      }
      */

      if (!empty($m)
        && !in_array($m, $entries)
        && substr($m, 0, strlen('CACHE')) != 'CACHE'
        && substr($m, 0, strlen('NETWORK')) != 'NETWORK'
        && $m != '*') {
          if (!startsWith($m, "/")) {
            // We have a relative URL
            $m =   pugpig_strip_domain(url_to_absolute($base_url, $m));
          }
          if (!empty($m)) {
            $entries[] = $m;
          } 
        }
      }
  }
  return $entries;
}

function _package_url($url, $base) {
  if (substr($url, 0, 4) != 'http' && substr($url, 0, 1) != '/')
    return $base . $url;
  else
    return $url;
}

function _print_progress_bar($length) {
  for ($n=0; $n<$length; $n++) 
    print ($n % 10 == 9 ? '.' : $n % 10 + 1);
  print '<br />';
}

function _pugpig_package_url_remove_domain($url) {
  // Strip off domain
  $colon_pos = strpos($url, '://');
  if ($colon_pos > 0)
    $url = '/' . substr($url, strpos($url, '/', $colon_pos + 3) + 1);
  return $url;
}

function _pugpig_package_item_url($url, $base, $domain) {
  if (strpos($url, '://') > 0)
    return $url;
  elseif (substr($url, 0, 1) === '/')
    return $domain . $url;
  else
    return $base . $url;
}

function _package_rmdir($dir) { // Recursive directory delete
   if (is_dir($dir)) {
     $objects = scandir($dir);
     foreach ($objects as $object) {
       if ($object != "." && $object != "..") {
         if (filetype($dir."/".$object) == "dir") {
           _package_rmdir($dir."/".$object);
         } else {
           //print_r($dir."/".$object . '<br />');
           $ret = unlink($dir."/".$object);
           if (!$ret) {
             // print_r("Failed to delete file: " . $dir ."/".$object . '<br />');
           }
         }
       }
     }
     reset($objects);
     if (!rmdir($dir)) {
       // print_r("Failed to remove directory: " . $dir  . '<br />');
     };
   }
 }


/************************************************************************
Takes the name of a zip file, and an array of files in the format
src => dest
************************************************************************/
function _package_zip($zip_path, $files) {
  $zip = new ZipArchive;
  if ($zip->open($zip_path, ZipArchive::CREATE) === TRUE) {
    // _print_immediately('Creating archive: ' . $zip_path . '');
    foreach ($files as $src => $dest) {
      // _print_immediately($src . ' -> ' . $dest . '<br>');
      $zip->addFile($src, $dest);
    }
    $zip->close();
  } else {
    // _print_immediately('Failed to create ' . $zip_path . ' !<br />');
  }
}

/************************************************************************
************************************************************************/
function _package_edition_package_timestamp($edition_tag) {

  $base = _package_final_folder();
  $contents = _package_edition_package_contents($base, $edition_tag);  
  if (isset($contents['xml_timestamp'])) return $contents['xml_timestamp'];
  return null;
}

/************************************************************************
************************************************************************/
function _package_edition_package_size($edition_tag) {

  $base = _package_final_folder();
  $contents = _package_edition_package_contents($base, $edition_tag);  
  $size = 0;
  if (isset($contents['html_size'])) $size += (int) $contents['html_size'];
  if (isset($contents['assets_size'])) $size += (int) $contents['assets_size'];
  return bytesToSize($size);
}

/************************************************************************
************************************************************************/
function _package_edition_package_contents($base, $edition_tag, $timestamp = '*') {
  $contents = array();

  // Get the XML file
  $xml_package = _package_get_most_recent_file($base . $edition_tag . '-package-' . $timestamp . '.xml');
  if ($xml_package != null) {
    $contents['xml_timestamp'] = filemtime($xml_package);  
    if ($timestamp == '*') {
      $timestamp = str_replace($base . $edition_tag . '-package-', '', $xml_package);
      $timestamp = str_replace('.xml', '', $timestamp);
    }
  } 
  
  // Get the file names of the HTML and Asset zip files
  $html_archive = $base . $edition_tag . '-html-' . $timestamp . '.zip';
  $assets_archive = $base . $edition_tag . '-assets-' . $timestamp . '.zip';

  // Check our files exist
  if (!file_exists($html_archive)) $html_archive = '';
  if (!file_exists($assets_archive)) $assets_archive = '';

  $prefix_len = strlen($base);

  // Spit out data about the files
  $contents['html_archive'] = $html_archive;
  $contents['html_timestamp'] = file_exists($contents['html_archive']) ? filemtime($contents['html_archive']) : NULL;
  $contents['html_size'] = file_exists($contents['html_archive']) ? filesize($contents['html_archive']) : 0;
  $contents['html_url'] = substr($contents['html_archive'], $prefix_len);

  $contents['assets_archive'] = $assets_archive;
  $contents['assets_timestamp'] = file_exists($contents['assets_archive']) ? filemtime($contents['assets_archive']) : NULL;
  $contents['assets_size'] = file_exists($contents['assets_archive']) ? filesize($contents['assets_archive']) : 0;
  $contents['assets_url'] = substr($contents['assets_archive'], $prefix_len);

  return $contents;
}

/************************************************************************
$content_xml_url is the location of the ATOM feed relative to the location
of the package.xml file
************************************************************************/
function _package_edition_package_list_xml($base, $edition_tag, $url_root = '', $cdn = '', $output = 'string', $timestamp = '*', $content_xml_url = "content.xml") {

  $url_root = pugpig_strip_domain($url_root);

  $contents = _package_edition_package_contents($base, $edition_tag, $timestamp);
 
  if (!file_exists($contents['html_archive']))
    return NULL;

  $total_size = 0;

  //$content_xml = "content.xml";

  $d = new DomDocument('1.0', 'UTF-8');
  $d->formatOutput = TRUE;

  $package = $d->createElement('package');
  $package->setAttribute('root', $content_xml_url);

  $comment = "Generated: " . date(DATE_RFC822);
  $package->appendChild($d->createComment($comment));

  $part = $d->createElement('part');
  $part->setAttribute('name', 'html');
  $part->setAttribute('src', $url_root . $contents['html_url']);
  $part->setAttribute('size', $contents['html_size']);
  $total_size += $contents['html_size'];

  $part->setAttribute('modified', gmdate(DATE_ATOM, $contents['html_timestamp']));
  $package->appendChild($part);

  if (is_numeric($contents['assets_size']) && $contents['assets_size'] > 0) {
    $part = $d->createElement('part');
    $part->setAttribute('name', 'assets');
    $part->setAttribute('src', $cdn . $url_root . $contents['assets_url']);
    $part->setAttribute('size', $contents['assets_size']);
    $total_size += $contents['assets_size'];
    $part->setAttribute('modified', gmdate(DATE_ATOM, $contents['assets_timestamp']));
    $package->appendChild($part);
  }

  $package->setAttribute('size', $total_size);


  $d->appendChild($package);

  if ($output == 'string')
    return $d->saveXML();
  else {
    $d->save($output);
    return $output;
  }
}

function _package_get_date_ordered_file_matches($path_with_wildcard) {
  $list = glob($path_with_wildcard);
  $found = array();
  if ($list === FALSE) { 
    if (ini_get('open_basedir')) {
      // Don't report anything
    } else if (function_exists("pugpig_set_message")) {
      pugpig_set_message("GLOB ERROR: _package_get_date_ordered_file_matches - $path_with_wildcard", 'error');
    } else {
      print("GLOB ERROR: _package_get_date_ordered_file_matches - $path_with_wildcard");
      exit();
      // Need to report this somehow
    }
    return $found;    
  }
  foreach ($list as $file) {
    $mtime = filemtime($file);
    $found[$mtime] = $file;
  }
  ksort($found, SORT_NUMERIC);
  return $found;
}

// This is slow. Ensure we never call it more than once per request per edition
global $_package_recent_files;
$_package_recent_files = array();
function _package_get_most_recent_file($path_with_wildcard) {
  global $_package_recent_files;
  if (isset($_package_recent_files[$path_with_wildcard])) return $_package_recent_files[$path_with_wildcard];

  $matches = _package_get_date_ordered_file_matches($path_with_wildcard);
  if (count($matches) > 0) {
    $ret = array_pop($matches);
    $_package_recent_files[$path_with_wildcard] = $ret;
    return $ret;
  }
  return NULL;
}

/*
Need a URL in the form: http://domain/base/relativeurl
Handles inputs of the form:
  http://domain/base/relativeurl
  http://cdn/base/relativeurl
  /base/relativeurl
  relativeurl
*/
/*
function _pugpig_package_url($url, $domain, $base) {

  return url_to_absolute($domain . $base, $url);

  if (startsWith($url, $domain . $base)) return $url;
  if (startsWith($url, $base)) return $domain . $url;
    
  // We had a relative URL 
  return $domain . $base . $url;
}
*/

/*
If the path starts with the base, we need to strip it off
*/
function _pugpig_package_path($path, $base) {
  if (startsWith($path, $base)) return substr($path, strlen($base));
  return $path;
}


// Take a set of relative URLs and convert them into an array of absolute URLs and save paths for the packager
function _pugpig_relative_urls_to_download_array($relative_path, $relative_urls, $base_url, $base_path) {

  $entries = array();
  foreach ($relative_urls as $relative_url) {
    // Remove any domains   
    $relative_url = _pugpig_package_url_remove_domain($relative_url);

    // Get the  URL that needs to be CURLed
    $url = url_to_absolute($base_url, $relative_url);

    // Take the domain off
    $root_url = _pugpig_package_url_remove_domain($url);

    // Get the path to save the file at
    $path =  $base_path . _pugpig_package_path($root_url, '/' . $relative_path);

    // In case we've got 2 slashes next to each other in the disk path
    $path = str_replace("//", "/", $path);
    
    // Convert folders to index.html files
    if (substr($path, -1) === '/') {
      $path = $path . 'index.html';
    }

    // We need to store the files on disk without %20s and the like
    // At present there is a bug in the client that appears to need these escaped
    // It does mean URLs with spaces don't work in a web browser after unzipping
    // $entries[$url] = rawurldecode($path);
    $entries[$url] = $path;

  }

  return $entries;

}

/*
Cleans the package folder leaving only the last two files from each edition
*/

function _pugpig_clean_package_folder($path_to_package_folder) {

  $groups = array();
  $file_names = glob($path_to_package_folder."/*.{xml,zip}", GLOB_BRACE);

  foreach ($file_names as $index => $name) {

    $path_parts = pathinfo($name);
    $filename =  $path_parts['filename'];
    $extension = $path_parts['extension'] ;

    if ($index == 0) {
      $path = $path_parts['dirname'];
    }

    $parts = explode("-", $filename);
    $number = array_pop($parts);
    
    $key = implode("-", $parts)  . "-******" . $extension;

    $groups[$key][] = $number; 
    
  }

  $deleted = array();

  foreach ($groups as $key => $group) {
    rsort($group, SORT_REGULAR);

    $i = 0;

    while ($i < count($group)) {
      if ($i > 1) {
        $fn = str_replace("******", $group[$i].".", $key);
        $filename = $path . "/" . $fn;
        //check it is not locked
        $fp = @fopen($filename, "r+");
        if ($fp != FALSE) {
          fclose($fp);
          unlink($filename);
          $deleted[] = $fn;
        }
      }

      $i++;
    }
  }

  return $deleted;
}

/*
Takes an array of the form $url => $path and turn it into $srcpath => $targetpath
If the URL is not relative to the URL where the packages will sit (package_url_base), the path in
the zip must be absolute and start with a /
Otherwise, it is relative to the location of the zip file
*/
function _pugpig_package_zip_paths($entries, $base_path, $package_url_base, $relative_path, $debug) {

    $zip_paths = array();
    foreach ($entries as $url => $path) {
     // print_r("PREP: $url -> $path<br />");

     $zip_location = substr($path, strlen($base_path));

     // If this URL does not start with the base URL, it is absolute. Stick a the relative path in front
     if (!startsWith($url, $package_url_base)) $zip_location = $relative_path . $zip_location;

     // If this URL does not start with a / but the package_url_base does, it needs a / in front.
     if (startsWith($package_url_base, "/") && !startsWith($zip_location, "/")) 
      $zip_location = "/" . $zip_location; 

     // print_r("INTO: $path -> $zip_paths[$path]<br />");
      $zip_paths[$path] = $zip_location;
    }

    if ($debug) {
      _print_immediately('<h3>DEBUG: These will go into the zip</h3>'); 
      var_dump($zip_paths);
    }
    return $zip_paths;
}

/*
Create a zip file and copy it from the temp dir to the real dir
*/

function _pugpig_package_create_zip($partname, $filename, $tmp_path, $real_path, $zip_paths, $zip_base_url) {


  if (!startsWith($zip_base_url, '/')) {
    $zip_base_url = '/' . $zip_base_url;
  }

  _print_immediately('<h3>Creating ZIP for ' . $partname . "</h3>");

  if (count($zip_paths) > 0) {
    $archive_tmp = $tmp_path  . $filename;
    $archive_real = $real_path . $filename;

    _print_immediately('<em>Zipping ' . count($zip_paths) . ' items into ' . $archive_tmp . '</em><br />');
    _package_zip($archive_tmp, $zip_paths);
    if (file_exists($archive_tmp)) {
      _print_immediately('<em>Copying to ' . $archive_real . '</em><br />');
      copy($archive_tmp, $archive_real ); // move from /tmp to default/files
      _print_immediately("<a target='_blank' href='" . $zip_base_url . $filename . "'>View ZIP file</a><br />");
      return true;
    } else {
       _print_immediately('<em>Error creating package file '.$archive_tmp.'</em><br />');
       return false;
    }
  } else {
    _print_immediately('<em>No assets to be zipped.</em><br />');
    return false;
  }
}

function _pugpig_package_show_failures($failures) {
  if (count($failures) > 0) {
    print_r("<h4>Error Summary</h4>");  
    foreach ($failures as $failure => $reason) {
      print_r("<p class='fail'><a href='". $failure."' target='_blank'>". htmlentities($failure)."</a><br/><span class='fail'>" . htmlentities($reason) . "</span></p>");
    }
    _print_immediately('<b>Aborting</b><br /><a href="javascript:location.reload(true);">Refresh this page to reload and try again. (It will resume from where it last succeeded.)</a><br />');
    exit();    
  }
}

function _pugpig_package_download_batch($heading, $entries, $debug) {
  
  print_r("<h3>Downloading " .$heading . " - ". count($entries) . " files</h3>");  
  if ($debug) var_dump($entries);

  // Check the URLs we're about to download are real URLs
  $format_failures = array();
  foreach (array_keys($entries) as $entry) {
    if (!filter_var($entry, FILTER_VALIDATE_URL)) {
      $format_failures[$entry] = "Invalid URL format: " . $entry;
      unset($entries[$entry]);
    } else {
      $path = $entries[$entry];
      if (strpos($path, "?") || strpos($path, "&")) {
         $format_failures[$entry] = "Invalid local disk path: " . $path;
         unset($entries[$entry]);
      }
    }
  }

  _pugpig_package_show_failures($format_failures);

  // Both of these should become a settings, as well as timeout value
  $concurrent = 5;  
  $warning_file_size = 150 * 1024; // 150 Kb
  $mc = new MultiCurl($entries, $concurrent, $warning_file_size);

  $mc->process();  
  $failures = $mc->getFailures();
  
  if (count($failures) > 0) {
    _pugpig_package_show_failures($failures);
    _print_immediately('<b>Aborting</b><br /><a href="javascript:location.reload(true);">Refresh this page to reload and try again. (It will resume from where it last succeeded.)</a><br />');
    exit();
  } else {
    print_r("<em>Done</em><br />");
  }
  return $entries;
}

function _pugpig_package_test_endpoints($endpoints, $timestamp, $tmp_root) {
  pugpig_interface_output_header("Pugpig - Endpoint Checker");

  print_r("<h1>Checking Pugpig End Points</h1>");
 

  $tmp_root = str_replace(DIRECTORY_SEPARATOR, '/', $tmp_root);
  $tmp_path = $tmp_root . 'package-' . $timestamp . '/';

  $entries = array();
  $c = 0;
  foreach ($endpoints as $endpoint) if ($endpoint != '') {
    $save_path = $tmp_path . 'opds/' . hash('md5', $endpoint). '.xml';
    $entries[$endpoint] = $save_path; 
  }

  $debug = FALSE;  
  $entries = _pugpig_package_download_batch("OPDS Feeds", $entries, $debug);
  
  $format_failures = array();
  foreach (array_keys($entries) as $entry) {
    // print_r($entry . " ---> " . $entries[$entry] . "<br />");
    
    // Read the ATOM from the file
    $fhandle = fopen($entries[$entry], 'r');
    $opds_atom = fread($fhandle, filesize($entries[$entry]));
    fclose($fhandle); 
    
    $msg = check_xml_is_valid($opds_atom);
    if ($msg != '') {
      $format_failures[$entry] = "OPDS XML Invalid: " . $msg;
      $opds_atom = '';
    }
    
    $opds_ret = _pugpig_package_parse_opds($opds_atom);
    
    $edition_roots = array();
    $package_roots = array();

    print_r("<h2>" . $entry .  "(".$opds_ret['title'].")</h2>");
    foreach ($opds_ret['editions'] as $edition) {
      $cover = url_to_absolute($entry, $edition['cover']);
      
      print_r("<img class='cover ".($edition['free']?"free":"paid")."' height='60' title='" . $edition['title'] . ': ' . $edition['summary'] . "' src='".$cover."' />");
      $edition_root = url_to_absolute($entry, $edition['url']);

      $save_path = $tmp_path . $edition['type'] . '/' . hash('md5', $edition_root). '.xml';
      $edition_roots[$edition_root] = $save_path;
      if ($edition['type'] == 'package') {
        $package_roots[] = $edition_root;
      }
    }
    $edition_roots = _pugpig_package_download_batch("Edition Roots", $edition_roots, $debug);
    
    $format_failures = array();
    foreach ($package_roots as $package_root) {
      $save_path = $edition_roots[$package_root]; 
      $fhandle = fopen($save_path , 'r');
      $package_xml_body = fread($fhandle, filesize($save_path));
      fclose($fhandle); 
      
      $msg = check_xml_is_valid($package_xml_body);
      if ($msg != '') {
        $format_failures[$package_root] = "Package XML Invalid: " . $msg;
        $opds_atom = '';
      }
     
    }
    
    // Show package format errros
    _pugpig_package_show_failures($format_failures);

    

    
  }
  
  _pugpig_package_show_failures($format_failures);

}

// $return_manifest_asset_urls = TRUE is used by Cloudfront purge code
// TODO: Why do we need to pass in the edition tag?
function _pugpig_package_edition_package($final_package_url, $content_xml_url, $relative_path,
  $debug=FALSE, $edition_tag = '', $return_manifest_asset_urls = FALSE, 
  $timestamp = '', $tmp_root, $save_root,  $cdn = '', $package_url_base = '', 
  $test_mode = FALSE, $image_test_mode = FALSE
) {

  $output = '';

  $html_zip_paths = array();
  $asset_zip_paths = array();
  
  $saved = array();
  
  $save_root = str_replace(DIRECTORY_SEPARATOR, '/', $save_root);
  $tmp_root = str_replace(DIRECTORY_SEPARATOR, '/', $tmp_root);

  $domain = '/';
  $colon_pos = strpos($content_xml_url, '://');
  if ($colon_pos > 0)
    $domain = substr($content_xml_url, 0, strpos($content_xml_url, '/', $colon_pos + 3));

  // $relative_path = _pugpig_package_url_remove_domain(substr($content_xml_url, 0, strrpos($content_xml_url, '/')) . '/');
  // WORDPRESS TEST
  //if (endsWith($content_xml_url, "pugpig_atom_contents.manifest")) $relative_path = '/';

  if (!$test_mode && !file_exists($save_root)) {
    mkdir($save_root, 0777, TRUE);
  }

  $tmp_path = $tmp_root . 'package-' . $timestamp . '/';
  
  pugpig_interface_output_header("Pugpig - Edition Packager");

  if ($test_mode) {
    print_r("<h1>Performing Pugpig Package Test Run</h1>");
  } else if ($image_test_mode) {    
    print_r("<h1>Performing Pugpig Package Image Preview</h1>");
  } else {
    print_r("<h1>Creating Pugpig Package</h1>");
  }

  print_r("<button style='cursor: pointer;' onclick=\"toggle_visibility('info');\">Info</button> ");
  print_r("<button style='cursor: pointer;' onclick=\"toggle_visibility('key');\">Key</button> ");
  print_r("<br />Packager version " . pugpig_get_standalone_version() . " <br />");

  print_r("<span id='key' style='display:none;'>");
  print_r("<span class='pass'>* - downloaded</span><br />");    
  print_r("<span class='skip'>* - skipped as already downloaded</span><br />");    
  print_r("<span class='warning'>* - downloaded, but large file warning</span><br />");    
  print_r("<span class='bigwarning'>* - downloaded, but VERY large file warning</span><br />");    
  print_r("<span class='slowwarning'>* - downloaded, but a little bit slowly</span><br />");    
  print_r("<span class='veryslowwarning'>* - downloaded, but too slowly for comfort</span><br />");    
  print_r("<span class='fail'>* - failed to fetch or save resource</span><br />");  
  print_r("</span>");
  
  print_r("<span id='info' style='display:none;'>");
  print_r("<em>Final Package URL: <a href='$final_package_url'>" . $final_package_url . '</a></em><br />');
  print_r("<em>Packaging ATOM URL: <a href='$content_xml_url'>" . $content_xml_url . '</a></em><br />');
  print_r("<em>Domain is: " . $domain . '</em><br />');
  print_r("<em>Relative path is: " . $relative_path . '</em><br />');
  print_r("<em>Package URL base is: " . $package_url_base . '</em><br />');
  print_r("<em>Save root is: " . $save_root . '</em><br />');
  print_r("<em>Temp path is: " . $tmp_path . '</em><br />');
  print_r("<em>CDN is: " . $cdn . '</em><br />');
  print_r("<em>Debug Mode is: " . ($debug ? "ON" : "OFF") . '</em><br />');
  print_r("<em>Test Mode is: " . ($test_mode ? "ON" : "OFF") . '</em><br />');
  print_r("<em>Image Mode is: " . ($image_test_mode ? "ON" : "OFF") . '</em><br />');
  print_r("<em>cURL timeout is: " . PUGPIG_CURL_TIMEOUT . ' seconds </em><br />');
  print_r("</span>");

  print_r("<h1>Retrieving files</h1>");

  _print_immediately('Package ' . $timestamp  . ' started at ' . date(PUGPIG_DATE_FORMAT, $timestamp) . '<br />');

  // Array used to store errors in the responses
  $format_failures = array();

  // Get the ATOM feeds - the real and and the one that might contain hidden extras
  $entries = array();

  $content_xml_hidden_save_path = $tmp_path . 'content-hidden.xml';

  $content_xml_hidden_path = $content_xml_url . (strpos($content_xml_url, '?') > 0 ? '&' : '?') . 'include_hidden=yes';

  $entries = _pugpig_relative_urls_to_download_array($relative_path, array($content_xml_url), $domain, $tmp_path);
  $entries[$content_xml_hidden_path]  = $content_xml_hidden_save_path;

  $entries = _pugpig_package_download_batch("Public and Hidden ATOM Feeds", $entries, $debug);

  $content_xml_save_path = $entries[$content_xml_url];
  if (file_exists($content_xml_save_path)) {
    // Read the ATOM from the hidden file
    $fhandle = fopen($content_xml_save_path, 'r');
    $atom_excluding_hidden = fread($fhandle, filesize($content_xml_save_path));
    fclose($fhandle);

    $msg = check_xml_is_valid($atom_excluding_hidden);
    if ($msg != '') {
      $format_failures[$content_xml_url] = "XML Invalid: " . $msg;
      $atom_excluding_hidden = '';
    }
  }
   
  $atom_ret = null; 
  if (file_exists($content_xml_hidden_save_path)) {
    // Read the ATOM from the hidden file
    $fhandle = fopen($content_xml_hidden_save_path, 'r');
    $atom_including_hidden = fread($fhandle, filesize($content_xml_hidden_save_path));
    fclose($fhandle);

    $msg = check_xml_is_valid($atom_including_hidden);
    if ($msg != '') {
      $format_failures[$content_xml_hidden_path] = "XML Invalid: " . $msg;
      $atom_including_hidden = '';
    } else {
      $atom_ret = _pugpig_package_parse_atom($atom_including_hidden);
    }

    unset($entries[$content_xml_hidden_path]);
    
    // We only want the real atom in the zip
    $html_zip_paths = array_merge($html_zip_paths, _pugpig_package_zip_paths($entries, $tmp_path, $package_url_base, $relative_path, $debug)); 
  }

  // Check that the XML is valid, and show the errors if not.
  _pugpig_package_show_failures($format_failures);

  if (!$atom_ret) return;

  // Update the edition tag if we have something from the feed
  if ($debug) _print_immediately('Edition tag was <b>' . $edition_tag . '<br />');
  if (!strlen($edition_tag)) $edition_tag = $atom_ret['edition_tag'];  
  _print_immediately('Edition tag is <b>' . $edition_tag . '<br />');
  
  // Process the manifests - these are relative to the ATOM content XML
  $entries = _pugpig_relative_urls_to_download_array($relative_path, $atom_ret['manifest_urls'], $content_xml_url, $tmp_path);
  $entries = _pugpig_package_download_batch("Manifests", $entries, $debug);
  $asset_zip_paths = array_merge($asset_zip_paths, _pugpig_package_zip_paths($entries, $tmp_path, $package_url_base, $relative_path, $debug)); // Keep for the asset zip
  // Getting the list of static files from the manifests
  $manifest_entries = array();
  $format_failures = array();
  foreach ($entries as $url => $sfile) {
      $fhandle = fopen($sfile, 'r');
      $fcontents = trim(fread($fhandle, filesize($sfile)));
      fclose($fhandle);
      if (!startsWith($fcontents, "CACHE MANIFEST")) {
        // This is dodgy. Delete the saved file in case it is better next time.
        $format_failures[$url] = "Manifest format not correct - CACHE MANIFEST not at start of response.";
        unlink($sfile);
      } else {
      //print_r("Read: " . $sfile . " - " . filesize($sfile) . " bytes<br />");
        $manifest_entries = _pugpig_package_get_asset_urls_from_manifest($fcontents, $manifest_entries, $url);
      }
  }
  _pugpig_package_show_failures($format_failures);

  
  $manifest_entries = array_unique($manifest_entries);

  // Stop now and return the list of manifest items if required   
  if ($return_manifest_asset_urls) {
  _print_immediately('<em>Returning ' . count($manifest_entries) . ' assets</em><br />');
   return $manifest_entries;  
  }
  
  // Process the static files
  $entries = _pugpig_relative_urls_to_download_array($relative_path, $manifest_entries, $domain, $tmp_path);

  if ($image_test_mode) {
    _pugpig_package_show_images_in_package($entries);
  } else {

    $entries = _pugpig_package_download_batch("Static Files", $entries, $debug);
    $asset_zip_paths = array_merge($asset_zip_paths, _pugpig_package_zip_paths($entries, $tmp_path, $package_url_base, $relative_path, $debug)); // Keep for the asset zip
   
    // Process the HTML files
    $entries = _pugpig_relative_urls_to_download_array($relative_path, $atom_ret['html_urls'], $content_xml_url, $tmp_path);
    $entries = _pugpig_package_download_batch("HTML Pages", $entries, $debug);
    $html_zip_paths = array_merge($html_zip_paths, _pugpig_package_zip_paths($entries, $tmp_path, $package_url_base, $relative_path, $debug)); // Keep for the html zip

    if (!$test_mode) {
      print_r("<h2>Packaging files</h2>");

      // Figure put where the packages will live
      $zip_base_url = $relative_path;
      if (!empty($package_url_base)) $zip_base_url = $package_url_base;    
      _pugpig_package_create_zip("public assets", $edition_tag . '-assets-' . $timestamp . '.zip', $tmp_path, $save_root, $asset_zip_paths, $zip_base_url);
      _pugpig_package_create_zip("secure html", $edition_tag . '-html-' . $timestamp . '.zip',  $tmp_path,   $save_root, $html_zip_paths,  $zip_base_url);

      // Create package - TODO: Check on why we save this
      print_r("<h3>Creating Package XML</h3>");

      $package_name = $edition_tag . '-package-' . $timestamp . '.xml';  
      _print_immediately('<em>Saving package xml to ' . $save_root . $package_name . '</em><br />');

      $package_xml = _package_edition_package_list_xml($save_root, $edition_tag, $package_url_base, $cdn, $save_root . $package_name, $timestamp);

      _print_immediately("<a target='_blank' href='" . $final_package_url . "'>View XML file</a><br />");

      if (is_null($package_xml)) {
        _print_immediately('Error in saving package file.<br /><br /><b>Aborting!</b><br /><a href="javascript:location.reload(true);">Refresh this page to reload and try again. (It will resume from where it last succeeded.)</a><br />');
        exit;
      }

      $deleted_files = _pugpig_clean_package_folder($save_root);

      if (count($deleted_files)) {

        print_r("<h3>Deleting old packagage files</h3>");
        _print_immediately("<b>Deleted " . count($deleted_files) . " old files</b><br />");

        foreach ($deleted_files as $f) {
          _print_immediately("Deleted $f<br />");
        }

      }

    }

   }

  // Delete the temp area
  if (!$debug) {
    _package_rmdir($tmp_path);
  } else {
    _print_immediately("<p><b>Debug mode - not deleting temp files</b></p>");    
  }
  
  _fill_buffer(16000);

  if (!$test_mode && !$image_test_mode) {
    print_r("<h2>Packaging Complete</h2>");
  } else {
    print_r("<h2>Test Run Complete</h2>");
  }

  return $edition_tag . '-package-' . $timestamp . '.xml';
}


function _pugpig_package_show_images_in_package($entries) {

  _print_immediately('<div class="portfolio"><ul id="grid">');
  foreach (array_keys($entries) as $entry) {
    $extension = "";
    $path_parts = pathinfo($entry);
    if (isset($path_parts['extension'])) {
      $extension = $path_parts['extension'];
    } 
    $char = pugpig_get_download_char($extension, 'EXT');

    if ($char == 'i') {
      _print_immediately("<li><a href='$entry'><img src='$entry'></a></li>\n");
    }
  }
  _print_immediately('</ul></div><p style="clear: both;">End of images</p>');


}

