<?php
/**
 * @file
 * Pugpig Edition OPDS feed
 */
?><?php
/*

Licence:
==============================================================================
(c) 2011, Kaldor Holdings Ltd
This module is released under the GNU General Public License.
See COPYRIGHT.txt and LICENSE.txt

 */?><?php

// Generate the OPDS feed of the editions

$status = 'publish';

$extra_comments = array();
$extra_comments[] = "Generated by: Pugpig WordPress Plugin " . PUGPIG_CURRENT_VERSION;


header('Content-Type: ' . feed_content_type('atom') . '; charset=' . get_option('blog_charset'), true);
header('Content-Disposition: inline; filename="opds.xml"');

$internal = FALSE;

// Show internal to internal users or if explicit on query string
if (pugpig_is_internal_user() || (isset($_GET["internal"]) && $_GET["internal"] == 'true')) {
  if (!pugpig_is_internal_user()) {
    header('HTTP/1.1 403 Forbidden');
    echo "Access denied to internal feed for external user from " . getRequestIPAddress();
    exit;
  }
  $status = 'all';  
  $internal = TRUE;
}

$atom_mode = FALSE;
if (isset($_GET["atom"]) && $_GET["atom"] == 'true') {
  $atom_mode = TRUE;
}

$extra_comments[] = "Status: $status. Number of editions to include: " . pugpig_get_num_editions();

$editions = pugpig_get_editions($status, pugpig_get_num_editions());


$edition_ids = array();
foreach($editions as $edition)
  $edition_ids[] = $edition->ID;

$d = pugpig_get_opds_container($edition_ids, $internal, $atom_mode, $extra_comments);  
$d->formatOutput = TRUE;
echo $d->saveXML();
