<?php
/**
 * @file
 * Standalone iTunes receipt validator
 * You will need to modify the configuration values to suit your environment:
 */
?><?php
/*

Licence:
==============================================================================
(c) 2011, Kaldor Holdings Ltd
This module is released under the GNU General Public License.
See COPYRIGHT.txt and LICENSE.txt

 */?><?php

	include_once "pugpig_utilities.php";
	include_once "pugpig_subs.php";

if (!file_exists('standalone_config.php')) {
	echo "<h2>Warning - standalone_config.php not found</h2>";
	echo "In order to use this page, you will need to configure settings in the file: <code>standalone_config.php</code>";
	exit();
} 
include_once 'standalone_config.php';

	if(!defined('PUGPIG_CURL_TIMEOUT')) define('PUGPIG_CURL_TIMEOUT', 20);
 
  $binaryReceipt = file_get_contents("php://input");
  pugpig_send_itunes_edition_credentials($iTunesSecret, $subscriptionPrefix, array(), $binaryReceipt, $pugpigCredsSecret, $proxy_server, $proxy_port);
