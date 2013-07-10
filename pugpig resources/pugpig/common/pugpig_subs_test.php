<?php
/**
 * @file
 * Pugpig Generic User Auth Test Functions
 */
?><?php
/*

Licence:
==============================================================================
(c) 2011, Kaldor Holdings Ltd
This module is released under the GNU General Public License.
See COPYRIGHT.txt and LICENSE.txt

 */?><?php

if(!defined('PUGPIG_CURL_TIMEOUT')) define('PUGPIG_CURL_TIMEOUT', 20);

function pugpig_subs_test_form($title, $urls, $params, $test_users) {

  if (isset($urls["base"])) {
    $urls["sign_in"] = $urls["base"] . "sign_in";
    $urls["verify_subscription"] = $urls["base"] . "verify_subscription";
    $urls["edition_credentials"] = $urls["base"] . "edition_credentials";
  }

  $vals = array();
  $params[] = "product_id";
  foreach ($params as $param) {
    if (isset($_REQUEST[$param])) $vals[$param] = $_REQUEST[$param];
  }

  if (empty($vals['product_id'])) $vals['product_id'] = "com.pugpig.test.issue12345";
  $product_id = $vals['product_id'];

  $authToken = null;
  $error = '';

  $issues = array();

echo <<< EOT
    <style>
       form {border: 1px solid grey; padding: 2px; margin: 2px;}
      .pugpig_active { color: green;}
      .pugpig_inactive { color: orange;}
      .pugpig_stale { color: gray;}
      .pugpig_unknown { color: red;}
    </style>
EOT;

  echo "<h2>Pugpig Authentication Test Console - $title</h2>\n";
  
  echo "Supplied test users:\n<ul>\n";

  foreach ($test_users as $test_user) {
    $state = strtolower($test_user['state']);
    unset($test_user['state']);
    $query_params = http_build_query(array_merge($test_user ,array('product_id' => $product_id, 'page' => 'pugpig-dovetail-test')));
    $description = implode(", ", $test_user);
    echo "<li><b class='pugpig_$state'>$description</b> - <a href='?$query_params'>Test</a></li>\n";
  }
  echo "</ul>\n";

  echo "<form method='GET'>\n";
  // Need to WordPress settings pages
  echo "<input type='hidden' name='page' value='pugpig-dovetail-test' />\n";
  echo "Enter test values:<br />\n";
  foreach ($params as $param) {
  	if (isset($vals[$param])) { $val = $vals[$param]; } else $val = '';
    echo "$param: <input id='$param' name='$param' type='text' value='$val' /> \n";
  }
  echo "<br /><input type='submit' />\n";
  echo "</form>\n";

  // We will always have product_id. Need at least one more.
  echo "<p>Using <em><a href='".$urls["sign_in"]."'>".$urls["sign_in"]."</a></em><br />\n";
  echo "Using <em><a href='".$urls["verify_subscription"]."'>".$urls["verify_subscription"]."</a></em><br />\n";
  echo "Using <em><a href='".$urls["edition_credentials"]."'>".$urls["edition_credentials"]."</a></em></p>\n";

  if (count($vals) > 1) {
  	unset($vals['product_id']);
    $sep = (strpos($urls["sign_in"], "?") ? "&" : "?");
    $sign_in_req = $urls["sign_in"] . $sep . http_build_query($vals);
   
    $http_status = pugpig_subs_http_request($sign_in_req, $sign_in_response);
    $status = "unknown";

    if ($http_status != 200) {
      echo "<b class='pugpig_unknown'>SIGN IN ERROR: Status $http_status</b>\n";
    } else {
      $token = pugpug_subs_get_single_xpath_value("/token", $sign_in_response);

      // Backup format to support the Dovetail response format
      if (empty($token)) $token = pugpug_subs_get_single_xpath_value("/result_response/authToken", $sign_in_response);
      if (!empty($token)) {

        echo "Auth Token: <b class='pugpig_active'>$token</b><br />\n";

        $query_vars = array("token" => $token); 
        $sep = (strpos($urls["verify_subscription"], "?") ? "&" : "?");
        $verify_subscription_req = $urls["verify_subscription"] . $sep . http_build_query($query_vars);
        $query_vars['product_id'] = $product_id;       
        $sep = (strpos($urls["edition_credentials"], "?") ? "&" : "?");
        $edition_creds_req = $urls["edition_credentials"] . $sep . http_build_query($query_vars);


	    $http_status = pugpig_subs_http_request($verify_subscription_req, $verify_subscription_response);
	    if ($http_status != 200) {
        echo "<b class='pugpig_unknown'>VERIFY SUBSCRIPTION ERROR: Status $http_status</b>\n";
	    } else {
	    	$message = pugpug_subs_get_single_xpath_value("/subscription/@message", $verify_subscription_response);
	    	$status = pugpug_subs_get_single_xpath_value("/subscription/@state", $verify_subscription_response);
	    	$issues_exists = pugpug_subs_get_xpath_value("/subscription/issues", $verify_subscription_response);
	    	$issues = pugpug_subs_get_xpath_value("/subscription/issues/issue", $verify_subscription_response);

          if (empty($status)) {
            echo "Status: <b class='pugpig_unknown'>Got a 200, but did not get back the expected response</b><br />\n";
          } else {
          	echo "Status: <b class='pugpig_$status'>$status</b><br />\n";
          	if (!empty($message)) echo "Message: <b class='pugpig_$status'>$message</b><br />\n";
          	if ($issues_exists == '' || $issues_exists->length == 0) {
  	        	echo "<b>You have access to all issues</b><br />\n";
          	} else if ($issues->length == 0) {
          		echo "<b>You do not have access to any issues</b><br />\n";
          	} else {
          		echo "<b>You have access to " . ($issues->length) . " issues</b><br />\n";
          		echo "<ul>\n";
          		foreach ($issues as $issue) {
          			echo "<li>" . $issue->textContent . "</li>\n";
          		}
          		echo "</ul>\n";
            }
        	}

	    }

	    $http_status = pugpig_subs_http_request($edition_creds_req, $edition_creds_response);
	    if ($http_status != 200) {
        echo "<b class='pugpig_unknown'>EDITION CREDENTIALS ERROR: Status $http_status</b>\n";
	    } else {
	    	$userid = pugpug_subs_get_single_xpath_value("/credentials/userid", $edition_creds_response);
	    	$password = pugpug_subs_get_single_xpath_value("/credentials/password", $edition_creds_response);
	    	if (!empty($userid) && !empty($password)) {
	    	 echo "Got credentials for <b class='pugpig_active'>$product_id</b>\n";
	    	} else {
	    	 $status = pugpug_subs_get_single_xpath_value("/credentials/error/@status", $edition_creds_response);
	    	 $message = pugpug_subs_get_single_xpath_value("/credentials/error/@message", $edition_creds_response);
	    	 echo "Denied credentials for <b class='pugpig_unknown'>$product_id</b> (status: <b class='unknown'>$status</b>)<br />\n";
	    	 if (!empty($message)) echo "Message: <b class='pugpig_unknown'>$message</b><br />\n";
	    	}
	    }

      } else {
        echo "Credentials not recognised - did not get a token<br />\n";
      }

    }
  
 
		echo "<h3 class='pugpig_$status'>All done</h3><br />\n";
		if (!empty($sign_in_req)) {
			echo "<a href='$sign_in_req'>Raw Sign In</a><br />\n";
		    echo "<hr />" . htmlspecialchars ($sign_in_response) . "<hr />\n";
		}
		if (!empty($verify_subscription_req)) {
			echo "<a href='$verify_subscription_req'>Verify Subscription</a><br />\n";
		    echo "<hr />" . htmlspecialchars ($verify_subscription_response) . "<hr />\n";
		}
		if (!empty($edition_creds_req)) {
			echo "<a href='$edition_creds_req'>Edition Credentials</a><br />\n";
		    echo "<hr />" . htmlspecialchars ($edition_creds_response) . "<hr />\n";
		}

	}
  
  print_r("<br /><em style='font-size:small'>Test Form Version: " . pugpig_get_standalone_version() . " </em><br />");

}

function pugpig_subs_get_test_user_array($params, $test_user_string) {
  $test_users = array();

  $lines = preg_split('/\n/m', $test_user_string, 0);
  foreach($lines as $line) if (!empty($line)) {
    $x = explode(",", $line);
    $y = array();
    $state = trim(array_shift($x));
    if (in_array($state, array("ACTIVE", "INACTIVE", "UNKNOWN"))) {
      if (count($x) == count($params)) {
        $y["state"] = $state;
        for ($i = 0; $i < count($params); $i++) {
          $y[$params[$i]] = trim(array_shift($x));
        }
        $test_users[] = $y;
      }
    }
  }

  return $test_users;
}

function pugpug_subs_get_single_xpath_value($expr, $body) {
	$ret = pugpug_subs_get_xpath_value($expr, $body);
	if ($ret == '' || count($ret) == 0) return '';
	$item = $ret->item(0);
	if (!isset($item)) return '';
	$value = $item->textContent;

	return $value;
}

function pugpug_subs_get_xpath_value($expr, $body) {
  if (empty($body)) return '';
  $xmldoc = new DOMDocument();
  $xmldoc->loadXML($body, LIBXML_NOCDATA | LIBXML_NOWARNING | LIBXML_NOERROR );
  $xpathvar = new DOMXPath($xmldoc);
  return $xpathvar->query($expr);
}

function pugpig_subs_http_request($url, &$response) {

  $ret = array();

  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
  curl_setopt($ch, CURLOPT_TIMEOUT, PUGPIG_CURL_TIMEOUT);

  // Use a proxy if required
  // Be aware of Drupal patch needed for proxy settings
  // drupal-7881-406-add-proxy-support-for-http-request.patch
  
  $response = curl_exec($ch);

  $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

  curl_close($ch);

  return $http_status;
}
