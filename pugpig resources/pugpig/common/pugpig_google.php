<?php
/**
 * @file
 * Pugpig Subscriptions
 */
?><?php
/*

Licence:
==============================================================================
(c) 2011, Kaldor Holdings Ltd
This module is released under the GNU General Public License.
See COPYRIGHT.txt and LICENSE.txt

 */?><?php

include_once 'pugpig_subs.php';
include_once 'pugpig_subs_xml.php';

// http://phpseclib.sourceforge.net/
// Add the path to where you installed seclib to your php.ini include_path setting
@include_once('Crypt/RSA.php');

function _google_get_product_id($data, $sku) {
  $json = json_decode($data);

  if (!empty($json) && property_exists($json, 'orders')) {
    foreach ($json->orders as $order) {
      if (property_exists($order, 'productId') && $order->productId==$sku && property_exists($order, 'purchaseToken')) {
        return $order->purchaseToken;
      } 
    } 
  }
  return null;
}


function _google_verify_token($public_key, $signature, $signed_data, $sku, $base_url) {
  $comments = array();
  $error    = '';
  $status   = 'unknown';

  if (!class_exists('Crypt_RSA')) {
    $comments[] = 'PHPSecLib is not in the PHP path.';
  }

  $purchaseToken = _google_get_product_id($signed_data, $sku);
  if (empty($purchaseToken)) {
    $status = 'invalid';
    $error  = 'The SKU is not present in the data.';
  } else {
    $status = 'unverified'; // unverified until verified
    
    $comments[] = 'The SKU is present in the data.';
    $comments[] = 'The purchase token is ' . $purchaseToken;

    // verify the data signature
    if (!class_exists('Crypt_RSA')) {
      $error = 'PHPSecLib is not in the PHP path.';
    } else {
      $rsa = new Crypt_RSA();
      $rsa->setSignatureMode(CRYPT_RSA_SIGNATURE_PKCS1);
      $rsa->loadKey("-----BEGIN PUBLIC KEY-----\n" . $public_key . "\n-----END PUBLIC KEY-----");
      if ($rsa->verify($signed_data, base64_decode($signature))) {
        $comments[] = 'verified ok';
        $status     = 'OK';
      } else {
        $comments[] = 'verification failed';
      }
    }
  }

  return array(
    'status'   => $status,
    'comments' => $comments,
    'error'    => $error
    );
}

function pugpig_send_google_edition_credentials($public_key, $signature, $signed_data, $sku, $base_url, $pugpig_secret) {
  $result = _google_verify_token($public_key, $signature, $signed_data, $sku, $base_url);
  _pugpig_subs_edition_credentials_response($sku, $pugpig_secret, $result['status']=='OK', $result['status'], $result['comments'], array(), $result['error']);
}

function pugpig_google_check_setup() {
  $status  = 'OK';
  $message = '';
  
  if (!class_exists('Crypt_RSA')) {
    $message = 'PHPSecLib is not in the PHP include path.  Pugpig Google cannot be used until it is.';
    $status  = 'error';
  }

  return array(
    'status'  => $status, 
    'message' => $message
    );
}

/* TODO : USe OAuth2 API etc to check the subscription details
  // http://stackoverflow.com/questions/11115381/unable-to-get-the-subscription-information-from-google-play-android-developer-ap
  // https://code.google.com/p/google-api-php-client/wiki/OAuth2
  // https://code.google.com/p/google-api-php-client/source/browse/trunk/examples/plus/index.php
  // Subscriptions
  if ($result === 'OK' && isset($purchaseToken) and $purchaseToken !== '') {
    // GET https://www.googleapis.com/androidpublisher/v1/applications/<var class="apiparam">packageName</var>/subscriptions/<var class="apiparam">subscriptionId</var>/purchases/<var class="apiparam">token</var>
    $url = $base_url . 'subscriptions/' . $sku . '/purchases/' . $purchaseToken;

    $options = array('timeout' => PUGPIG_CURL_TIMEOUT);
    $response = drupal_http_request($url, $options);
    
    // https://developers.google.com/android-publisher/v1/purchases#resource
    // {
    //   "kind": "androidpublisher#subscriptionPurchase",
    //   "initiationTimestampMsec": long,
    //   "validUntilTimestampMsec": long,
    //   "autoRenewing": boolean
    // }
    

    if ($response->code == 200) {
      $json = json_decode($response->data);
      $expiration_date = $json->validUntilTimestampMsec;
      $now = (microtime(true) * 1000);
      if ($expiration_date > $now)
        $result = 'OK';
      else
        $result = 'just_expired';
    } else {
      $result = 'expired';
    }
  }
*/