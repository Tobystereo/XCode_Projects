<?php
/**
 * @file
 * Pugpig Feed Parsers
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
Parse and OPDS feed
************************************************************************/
function _pugpig_package_parse_opds($opds_xml) {

  $opds_ret = array();
  $editions = array();
  $feed_title = '';
  $feed_subtitle = '';
  
  if ($opds_xml != '') {
    $atom = new XMLReader();
    
    $atom->XML($opds_xml);
    while ($atom->read()) {
      if ($atom->localName == 'entry' && $atom->nodeType == XMLReader::ELEMENT) {
      
        $edition_cover = "";
        $edition_id = "";
        $edition_title = "";
        $edition_summary = "";
        $edition_type = "";
        $edition_url = "";
        $edition_free = TRUE;
        
        while ($atom->read() && $atom->localName != 'entry') {

          // ID of an entry
          if ($atom->localName == 'id' && $atom->nodeType == XMLReader::ELEMENT) {
             $atom->read();
             $edition_id = $atom->value;
          }    
          
          // ID of an entry
          if ($atom->localName == 'title' && $atom->nodeType == XMLReader::ELEMENT) {
             $atom->read();
             $edition_title = $atom->value;
          } 
          
          // ID of an entry
          if ($atom->localName == 'summary' && $atom->nodeType == XMLReader::ELEMENT) {
             $atom->read();
             $edition_summary = $atom->value;
          }  
          // Links in an entry
          if ($atom->localName == 'link'  && $atom->nodeType == XMLReader::ELEMENT) {
               $lrel = $atom->getAttribute('rel');
               $ltype = $atom->getAttribute('type');
               $lurl = $atom->getAttribute('href');

               if ($lrel == 'http://opds-spec.org/image') {
                 $edition_cover = $lurl;
               }
               
               if ($lrel == 'http://opds-spec.org/acquisition' || $lrel == 'http://opds-spec.org/acquisition/buy') {
                 if ($ltype == 'application/pugpigpkg+xml') {
                  $edition_type = 'package';
                 } elseif ($ltype == 'application/atom+xml') {
                  $edition_type = 'atom';
                 } else {
                  $edition_type = 'Unknown';
                 }
                 $edition_url = $lurl;
               }    
               
               if ($lrel == 'http://opds-spec.org/acquisition/buy') {
                $edition_free = FALSE;
               }

          }
        
        }
        //print_r("Processed edition ".$edition_id." - " . $edition_title. " " . $edition_summary . "  <br />");
        $editions[$edition_id]['cover'] = $edition_cover;
        $editions[$edition_id]['title'] = $edition_title;
        $editions[$edition_id]['summary'] = $edition_summary;
        $editions[$edition_id]['url'] = $edition_url;
        $editions[$edition_id]['type'] = $edition_type;
        $editions[$edition_id]['free'] = $edition_free;
        
        
      } else {
      
        if ($atom->localName == 'title' && $atom->nodeType == XMLReader::ELEMENT) {
                  $atom->read();
                  $feed_title = $atom->value;
        } 
        if ($atom->localName == 'subtitle' && $atom->nodeType == XMLReader::ELEMENT) {
                  $atom->read();
                  $feed_subtitle = $atom->value;
        }       }
    }
    $atom->close();  
  }
  
  // print_r($editions);
  $opds_ret['title'] = $feed_title;
  if ($feed_subtitle != '') $opds_ret['title'] .= ' - ' . $feed_subtitle;
  $opds_ret['editions'] = $editions;
  return $opds_ret;

}

/************************************************************************
Parse the ATOM XML to extract the edition tag, the manifests, the HTML pages
************************************************************************/
function _pugpig_package_parse_atom($atom_xml) {
  $atom_ret =  array();
  $manifest_urls =  array();
  $html_urls =  array();
  $edition_tag = '';

  if ($atom_xml != '') {
    $atom = new XMLReader();
    $atom->XML($atom_xml);
    while ($atom->read()) {
      if ($atom->localName == 'id' && $edition_tag == '') {
        $edition_tag = $atom->readString();
      } elseif ($atom->localName == 'link') {
        $url = $atom->getAttribute('href');
        $rel = $atom->getAttribute('rel');
        $type = $atom->getAttribute('type');
        switch ($rel) {
          case 'related': if ($type == "text/cache-manifest") $manifest_urls[] = $url; break;
          case 'alternate': if ($type == "text/html") $html_urls[] = $url; break;
        }
      }
    }
    $atom->close();
  }
  
  $atom_ret['edition_tag'] = $edition_tag;
  $atom_ret['manifest_urls'] = $manifest_urls;
  $atom_ret['html_urls'] = $html_urls;

  return $atom_ret;
}

/************************************************************************
Validation of XML
************************************************************************/
function check_xml_is_valid($xml_input) {

  // Check that they are valid
  libxml_use_internal_errors(true);
  $ret = simplexml_load_string($xml_input);
  if ($ret == FALSE) {
    $errors = libxml_get_errors();
    $xml = explode("\n", $xml_input);

    $msg = "";
    foreach ($errors as $error) {
        $msg .= htmlspecialchars(display_xml_error($error, $xml)) . "<br />";
    }

    libxml_clear_errors();
    return ($msg);
  }
  
  return '';
}

/************************************************************************
************************************************************************/
function display_xml_error($error, $xml)
{
    $return  = $xml[$error->line - 1] . "\n";

    switch ($error->level) {
        case LIBXML_ERR_WARNING:
            $return .= "Warning $error->code: ";
            break;
         case LIBXML_ERR_ERROR:
            $return .= "Error $error->code: ";
            break;
        case LIBXML_ERR_FATAL:
            $return .= "Fatal Error $error->code: ";
            break;
    }

    $return .= trim($error->message) .
               "\n  Line: $error->line" .
               "\n  Column: $error->column";

    if ($error->file) {
        $return .= "\n  File: $error->file";
    }

    return "$return\n\n";
}

