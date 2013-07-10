<?php
/**
 * @file
 * Pugpig Dovetails common code
 */
?><?php
/*

Licence:
==============================================================================
(c) 2011, Kaldor Holdings Ltd
This module is released under the GNU General Public License.
See COPYRIGHT.txt and LICENSE.txt

 */?><?php
// ************************************************************************
//
// ************************************************************************
include_once("pugpig_subs_test.php");

function _dovetail_edition_credentials($url, $product_id, $username, $password, $token) {
  $response = NULL;
  $status = 'NOT_ENTITLED';
  
  if ($token != '' && $product_id != '') {
    $status = 'OK';
  
    $response = _dovetail_verify_entitlement($url, $product_id, $token);
    $check = _dovetail_check_entitlement_response($response);

    $failopen = $check['failopen'];  
    $failmessage = $check['failmessage'];
    $state = $check['state'];

    if (!$state)
      $status = 'NOT_ENTITLED';
  }
  
  $writer = new XMLWriter();
  $writer->openMemory();
  $writer->setIndent(TRUE);
  $writer->setIndentString('  ');
  $writer->startDocument('1.0', 'UTF-8');

  if ($response == NULL) {
    $writer->startElement('error');
    $writer->writeAttribute('status', $status);
    if ($token == '')     $writer->writeComment("NO TOKEN PROVIDED");
    if ($product_id == '')     $writer->writeComment("NO PRODUCT ID PROVIDED");

    $writer->endElement();  
  } 
  elseif ($status == 'OK') {
    $writer->startElement('credentials');
    $writer->writeElement('userid', $username);
    $writer->writeElement('password', $password);
    $writer->writeElement('productid', $product_id);
    
    if ($failopen) {
          $writer->writeComment("FAILING OPEN: " . $failmessage);
    }

    $writer->writeComment($response->request);
    $writer->writeComment($response->code);
    $writer->writeComment($response->status_message);
    if (isset($response->error) && !is_null($response->error) && $response->error != '' && $response->error != $response->status_message)
      $writer->writeComment($response->error);
    $writer->endElement();
  }
  else {
    $writer->startElement('error');
    $writer->writeAttribute('status', $status);
    $writer->writeElement('productid', $product_id);
    $writer->writeComment($response->request);
    $writer->writeComment($response->code);
    $writer->writeComment($response->status_message);
    if (isset($response->error) && !is_null($response->error) && $response->error != '' && $response->error != $response->status_message)
      $writer->writeComment($response->error);
    $writer->endElement();
  }

  $writer->endDocument();

  header('Content-type: text/xml');
  echo $writer->outputMemory();
  exit; // Don't do the usual Drupal caching headers etc when completing the request
}

// ************************************************************************
// 
// ************************************************************************
function _dovetail_verify_subscription($url, $product_id, $token) {

  $writer = new XMLWriter();
  $writer->openMemory();
  $writer->setIndent(true);
  $writer->setIndentString('  ');
  $writer->startDocument('1.0', 'UTF-8');

  $writer->startElement('subscription');
  if ($token == '') {
      $writer->writeAttribute('state', 'inactive');
      $writer->writeComment("NO TOKEN PROVIDED");
  }
  else {
    // Do the request
    $response = _dovetail_verify_entitlement($url, $product_id, $token);
    header('Content-type: text/xml');

    $check = _dovetail_check_entitlement_response($response);

    $failopen = $check['failopen'];  
    $failmessage = $check['failmessage'];
    $state = $check['state'];

    if (!$failopen) {
      $writer->writeAttribute('state', $state ? 'active' : 'inactive');
    } 
    else {
      $writer->writeAttribute('state', 'active');
      $writer->writeComment("FAILING OPEN: " . $failmessage);
    }
    $writer->writeComment("Request: " . $response->request);
    if (isset($response->error) && !is_null($response->error) && $response->error != '' && $response->error != $response->status_message)
      $writer->writeComment($response->error);
   $writer->writeComment("Status code: " . $response->code);
   $writer->writeComment("Status message: " . $response->status_message);

 }
  $writer->endElement();

  $writer->endDocument();
  header('Content-type: text/xml');
  header('Cache-Control: no-cache, must-revalidate, post-check=0, pre-check=0');
  echo $writer->outputMemory();;
  exit; // Don't do the usual Drupal caching headers etc when completing the request
}

// ************************************************************************
// _dovetail_sign_in
// ************************************************************************
function _dovetail_sign_in($base_url, $product_id, $clientRef, $brandRefList, $webId) {
  if (!isset($base_url) || $base_url == '' || !isset($clientRef) || $clientRef == ''  || !isset($brandRefList) || $brandRefList == '' ) {
    header('HTTP/1.1 500 Internal Server Error');
    echo 'The Dovetail settings for URL, Client Ref and Brand Ref are not set.';
    exit;
  }     

  $brandArray = explode(",", $brandRefList);
  $brandCount = 0;

  $comments = "<!-- BRANDS: $brandRefList -->\n";
  $best_response = NULL;
  foreach ($brandArray as $brandRef) {
    $sign_in_response = _dovetail_get_sign_in_response($base_url, $clientRef, $brandRef, $webId);
    $authToken = _dovetail_get_token_from_response($sign_in_response);

    if ($best_response == NULL) $best_response = $sign_in_response;

    $brandCount++;
    // If we don't get back a token and we have more brands to try, skip to the next one
    if (!$authToken) {
      $comments .= "<!-- Failed auth with $brandRef. Try next one. -->\n";
      continue;
    } else {
      $comments .= "<!-- Logged in with $brandRef. Checking response. -->\n"; 
      // Keep this as it is successful login, which might be the best we have
      $best_response = $sign_in_response;

      // If we have a log in on the last one, no point verifying. May as well use it.
      if ($brandCount == count($brandArray)) {
        $comments .= "<!-- Use without entitlement check. Nothing else afterwards. -->\n"; 
        break;
      }

      $entitlement_response = _dovetail_verify_entitlement($base_url, $product_id, $authToken);
      $check = _dovetail_check_entitlement_response($entitlement_response);

      if (!$check['state']) {
         $comments .= "<!-- Failed entitlement check for $brandRef. Try next one. -->\n";
         continue;
      } else {
        // We are valid. Won't do any better than this
         $comments .= "<!-- Have access on $brandRef. Using it. -->\n";
         break;
      }
    }
  }

  $comments .= "<!-- Using: " . $best_response->request . " -->\n";

  header('Content-type: text/xml', true, $best_response->code);

  echo $best_response->data;
  echo $comments;
  
  exit();
}

// ************************************************************************
// 
// ************************************************************************
function _dovetail_verify_entitlement($url, $product_id, $token) {
// https://staging-dovetailrestfulservice.subscribeonline.co.uk/fulfillment-integration/verify-entitlement?authToken=E5634B04-2464-4CA1-A29C-44ABAABF8F9&productId=uk.co.company.magazine.evo.1234
/*
Success Results:

Example Entitled Results:
<result_response httpStatusCode="200" date="17/08/2011 17:10:30">
  <entitled>true</entitled>
</result_response>

Example NOT Entitled Results:
<result_response httpStatusCode="200" date="17/08/2011 17:10:30">
  <entitled>false</entitled>
</result_response>

Error Results:
<result_response errorCode="" httpStatusCode="401" date="17/08/2011 16:50:11"/>
<result_response errorCode="" httpStatusCode="500" date="17/08/2011 16:50:11"/>
 */
  if (!isset($url) || $url == '' || !isset($product_id) || $product_id == '') {
    header('HTTP/1.1 500 Internal Server Error');
    echo 'The Dovetail settings for URL and/or Product ID are not set.';
    exit;
  } 
  else {
    $url = $url . '/fulfillment-integration/verify-entitlement'
      . '?authToken=' . $token
      . '&productId=' . $product_id;
      //. '&uuid=' . $uuid;
    $options = array('timeout' => PUGPIG_CURL_TIMEOUT);
    $response = pugpig_dovetail_http_request($url, $options);
    
    return $response;
  }
}

// ************************************************************************
// 
// ************************************************************************
function _dovetail_get_sign_in_response($base_url, $clientRef, $brandRef, $webId) {

   $url = $base_url . '/fulfillment-integration/sign-in'
      . '?sourceSystem=' . 'MASS'
      . '&clientRef=' . $clientRef
      . '&brandRef=' . $brandRef
      . '&webId=' . $webId;
    $options = array('timeout' => PUGPIG_CURL_TIMEOUT);
    $response = pugpig_dovetail_http_request($url, $options);
    return $response;
}

// ************************************************************************
// 
// ************************************************************************
function _dovetail_get_token_from_response($response) {
    if ($response->code == 200) {
      $startp = strpos($response->data, "<authToken>");
      $endp = strpos($response->data, "</authToken>");
      if ($startp > 0 && $endp > 0) {
        $authToken = substr($response->data, $startp + strlen("<authToken>" ), 
          $endp - $startp - strlen("</authToken>") + 1 );   
        return $authToken;
      }
    }
    return FALSE;
}

// ************************************************************************
// Check a verify entitlement response
// ************************************************************************
function _dovetail_check_entitlement_response($response) {
    $state = TRUE;
    $failopen = FALSE;
    $failmessage = "";
    $status = "";

    if ($response->code != 200) {
      $failopen = TRUE;
      $failmessage = 'Did not recevied an HTTP 200.';
    }  elseif (strpos($response->data, '<entitled>true</entitled>') > 0) {
      $state = TRUE;
    } elseif (strpos($response->data, 'result_response') > 0) {
      $state = FALSE;
    } else {
      // Didn't even get an entitled block in the response
      $failopen = TRUE;
      $failmessage = '200 response did not contain any XML with result_response block';
    }   

    $ret = array('state' => $state, 'failopen' => $failopen, 'failmessage' => $failmessage );
    return $ret;
}

// ************************************************************************
// Test form
// ************************************************************************
function _dovetail_test_form($url, $clientRef, $brandRefList, $test_user_string = '') {
  // https://staging-dovetailrestfulservice.subscribeonline.co.uk/fulfillment-integration/sign-in?sourceSystem=MASS&clientRef=XXX&brandRef=YYY&webId=12345

  $title = "Dovetail Test Form (Client: $clientRef, Brands: $brandRefList)";

  $urls["sign_in"] = pugpig_get_current_base_url() . "/dovetail_sign_in/";
  $urls["verify_subscription"] = pugpig_get_current_base_url() . "/dovetail_verify_subscription/";
  $urls["edition_credentials"] = pugpig_get_current_base_url() . "/dovetail_edition_credentials/";
  $params = array("webId");

  $test_users =pugpig_subs_get_test_user_array($params, $test_user_string);

  pugpig_subs_test_form($title, $urls, $params, $test_users);
  
  exit; // Don't do the usual Drupal caching headers etc when completing the request
}
