<?php
/**
 * @file
 * Standalone Amazon receipt validator
 * You will need to modify the configuration values to suit your environment:
 */
?><?php
/*

Licence:
==============================================================================
(c) 2013, Kaldor Holdings Ltd
This module is released under the GNU General Public License.
See COPYRIGHT.txt and LICENSE.txt

 */?><?php

include_once "pugpig_utilities.php";
include_once "pugpig_google.php";

// http://phpseclib.sourceforge.net/
// Add the path to where you installed seclib to your php.ini include_path setting
@include_once('Crypt/RSA.php');

if (!file_exists('standalone_config.php')) {
	echo "<h2>Warning - standalone_config.php not found</h2>";
	echo "In order to use this page, you will need to configure settings in the file: <code>standalone_config.php</code>";
	exit();
} 
include_once 'standalone_config.php';

if(!defined('PUGPIG_CURL_TIMEOUT')) {
  define('PUGPIG_CURL_TIMEOUT', 20);
}

$check = pugpig_google_check_setup();
if ($check['status']!='OK') {
  echo '<h2>' . $check['message'] . '</h2>';
  exit();
}

$signed_data   = '{"orders":[{"productId":"sku","purchaseToken":"purchaseToken"}]}';
$sku           = 'sku';
$base_url = ''; // not currently used
$pugpig_secret = 'somethingIsSecret';

$rsa = new Crypt_RSA();

// create a public and private key just for testing
extract($rsa->createKey());

$rsa->loadKey($privatekey);

$rsa->setSignatureMode(CRYPT_RSA_SIGNATURE_PKCS1);
$signature = base64_encode($rsa->sign($signed_data));

pugpig_send_google_edition_credentials($publickey, $signature, $signed_data, $sku, $base_url, $pugpig_secret);
