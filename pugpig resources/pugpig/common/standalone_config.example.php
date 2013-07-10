<?php
/**
 * @file
 * Pugpig Standalone Configuration
 * $iTunesSecret - Get this value from your iTunes Connect account. It is used to verify receipts with iTunes.
 * $subscriptionPrefix -All iTunes Connect products starting with this prefix will be treated as subscription products. For example com.pugpig.subscription.
 * $pugpigCredsSecret -  This is the secret used to generate and decode edition credentials. It needs to be used in the env config settings for the Varnish config if this is being used.
 */

?><?php
/*

Licence:
==============================================================================
(c) 2011, Kaldor Holdings Ltd
This module is released under the GNU General Public License.
See COPYRIGHT.txt and LICENSE.txt

 */?><?php
  
  /***** SETTINGS FOR ITUNES RECEIPT VALIDATION *****/
  $iTunesSecret = 'YOUR_ITUNES_SECRET_HERE';

  /***** SETTINGS FOR AMAZON STORE VALIDATION *****/
  $settings_amazon = array(
    'base_url' => 'https://appstore-sdk.amazon.com',
    'secret'   => '2:oPYK5fU9aIzIdQkXor93UI3mfqoexts1vPLEDtkkx2sz0imC70p1hp_Za3OVlOm3:RsR0W-BcE_HwCsq0VcSPCQ==');

  /***** SETTINGS EDITION CREDENTIAL VALIDATION *****/
  $subscriptionPrefix = 'com.mycompany.subscription';
  $pugpigCredsSecret = 'MY_TOP_SECRET_PUGPIG_CREDS';

  /***** SETTINGS FOR DEBUG (OR RESTRICTED OUTBOUND ACCESS) *****/  
  $proxy_server = '';
  $proxy_port = '';


  /***** SETTINGS FOR AUTH TEST FORM *****/
  $title = "My Subscription Test Form";
  $urls["base"] = pugpig_get_current_base_url() . "/mysubssystem/";

  $params = array("username", "password");

  $test_users = array(
  	array("state" => "ACTIVE", "username" => "gooduser", "password" => "password"),
  	array("state" => "INACTIVE", "username" => "lapseduser", "password" => "password"),
  	array("state" => "UNKNOWN", "username" => "rubbish", "password" => "rubbish")
  );

