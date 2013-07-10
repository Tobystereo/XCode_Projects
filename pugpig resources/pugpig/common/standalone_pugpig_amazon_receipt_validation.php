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
include_once "pugpig_amazon.php";

if (!file_exists('standalone_config.php')) {
	echo "<h2>Warning - standalone_config.php not found</h2>";
	echo "In order to use this page, you will need to configure settings in the file: <code>standalone_config.php</code>";
	exit();
} 
include_once 'standalone_config.php';

if(!defined('PUGPIG_CURL_TIMEOUT')) {
  define('PUGPIG_CURL_TIMEOUT', 20);
}

if (empty($settings_amazon['base_url']) || empty($settings_amazon['secret']) ) {
		echo "<h2>Warning - base_url and secret need to be set</h2>";
		exit();
}

// settings
$base_url = $settings_amazon['base_url'];
$amazon_secret = $settings_amazon['secret'];

// credentials to check
$user_id = 'l3HL7XppEMhrOGDnur9-ulvqomrSg6qyODKmah76lJU=';
$product_sku = 'com.amazon.android.comics.OCT110363';
$subs_sku = null;
$token = '2:FlrXSsmgOBKXoBbf6BtIrBtmbZLNr92laKjtTMTlz9tQyYUXl-vuEsdl1Hr8g0xxsQIa8JP3uIqNfmatmSRnOamsrYWGlpKFTrKb0IWXPlYlXhY4EH0ufJYuWzoOicNXCm6BBH9seKczkQ_I-QObpjCuHnlZk4pXgl3g_VggJZGpWBtuvYAqOVXYfcMjf268BaMjVX7plTQ_MPvzLrRNGQ==:qsy5n5MMZM4u-LlDrqGp5Q==';
$pugpig_secret = $pugpigCredsSecret;

pugpig_send_amazon_edition_credentials($user_id, $product_sku, $subs_sku, $token, $base_url, $amazon_secret, $pugpig_secret, $proxy_server, $proxy_port);