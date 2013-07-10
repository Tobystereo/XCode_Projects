<?php
/**
 * @file
 * Pugpig Subscriptions XML helpers
 */
?><?php
/*

Licence:
==============================================================================
(c) 2011, Kaldor Holdings Ltd
This module is released under the GNU General Public License.
See COPYRIGHT.txt and LICENSE.txt

 */?><?php

function _pugpig_subs_start_xml_writer() {
  header('Content-type: text/xml');
  header('Cache-Control: no-cache, must-revalidate, post-check=0, pre-check=0');
  $writer = new XMLWriter();
  $writer->openMemory();
  $writer->setIndent(TRUE);
  $writer->setIndentString('  ');
  $writer->startDocument('1.0', 'UTF-8');  
  return $writer;
}

function _pugpig_subs_end_xml_writer($writer) {
  $writer->endDocument();
  echo $writer->outputMemory();
  exit; // Don't do the usual Drupal caching headers etc when completing the request
}

// ************************************************************************
// Generate response for a sign in
// $token - The token returned, or empty on failure
// $comments - any extra comments for the feed, mainly for debug
// ************************************************************************
/*
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<token>BSDF</token>

<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<error status="Creds do not match" />

*/
function _pugpig_subs_sign_in_response($token, $comments = array(), $fail_status='notrecognised', $fail_message="Invalid credentials") {
  $writer = _pugpig_subs_start_xml_writer();

  if (!empty($token)) {
    $writer->startElement('token');
    $writer->text($token);
    $writer->endElement();
    foreach ($comments as $comment) $writer->writeComment(" " . $comment . " ");
   
  } else {
    $writer->startElement('error');
    $writer->writeAttribute('status', $fail_status);
    $writer->writeAttribute('message', $fail_message);
    $writer->endElement();
    foreach ($comments as $comment) $writer->writeComment(" " . $comment . " ");
 }

  _pugpig_subs_end_xml_writer($writer);
}


// ************************************************************************
// Generate response for a verify subscription
// $state - The token used to for access
// * unknown - user isn't recognized
// * active - user is an active subscriber
// * inactive - user's subscription has lapsed
// * suspended - user's subscription has been temporarily suspended
// * error - the token is illegal
// $comments - any extra comments for the feed, mainly for debug
// $issues - NULL means they have access to all issues
// ************************************************************************
/*
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<subscription state="active"/>
*/
function _pugpig_subs_verify_subscription_response($state, $comments = array(), $message = '', $issues = NULL) {
  $writer = _pugpig_subs_start_xml_writer();

  $writer->startElement('subscription');
  $writer->writeAttribute('state', $state);
  if (!empty($message)) {
    $writer->writeAttribute('message', $message);
  }
  foreach ($comments as $comment) $writer->writeComment($comment);

  if (!isset($issues)) {
    $writer->writeComment("User has access to all issues.");
  } else {
    $writer->startElement('issues');
    foreach ($issues as $issue) {
      $writer->startElement('issue');
      $writer->text($issue);
      $writer->endElement();
    }
    $writer->endElement();
  }

  $writer->endElement();
  _pugpig_subs_end_xml_writer($writer);
}

// ************************************************************************
// Generate edition credentials in the standard response Pugpig format
// $product_id - ID of the product (normally the edition ID matching the OPDS feed)
// $secret - The secret used to generate the credentials
// $entitled - True if entitled, false otherwise
// $comments - any extra comments for the feed, mainly for debug
// $extras - any extra valurs for a positive response
// ************************************************************************
/*
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<credentials>
  <userid>USER-ID</userid>
  <password>PASSWORD</password>
</credentials>

<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<error status="NOT_ENTITLED"/>
*/
function _pugpig_subs_edition_credentials_response($product_id, $secret, $entitled = FALSE, $state, $comments = array(), $extras = array(), $error_message = '') {
    
  $writer = _pugpig_subs_start_xml_writer();


  if ($entitled) {

    $username = sha1(uniqid(mt_rand())); // TODO: do we need a more secure random number?
    $password = pugpig_generate_password($product_id, $username, $secret);

    $writer->startElement('credentials');
    $writer->writeElement('userid', $username);
    $writer->writeElement('password', $password);
    $writer->startElement('subscription');
    $writer->writeAttribute('state', $state);
    $writer->endElement();   
    $writer->writeElement('productid', $product_id);
    foreach ($comments as $comment) $writer->writeComment($comment);  
    foreach ($extras as $name => $value) $writer->writeElement($name, $value);  
   
    $writer->endElement();
  }
  else {

    $writer->startElement('credentials');

    $writer->startElement('error');
    $writer->writeAttribute('status', "notentitled");
    if (!empty($error_message)) $writer->writeAttribute('message', $error_message);
    $writer->endElement();   

    $writer->startElement('subscription');
    $writer->writeAttribute('state', $state);
    $writer->endElement();   
    
    foreach ($comments as $comment) $writer->writeComment($comment);  
    foreach ($extras as $name => $value) $writer->writeElement($name, $value);  
    $writer->endElement();

    $writer->endElement();   

  }

  $writer->endDocument();

  _pugpig_subs_end_xml_writer($writer);

}