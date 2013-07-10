<?php
/**
 * @file
 * Pugpig Auth Test Sign In
 */
?><?php
/*

Licence:
==============================================================================
(c) 2011, Kaldor Holdings Ltd
This module is released under the GNU General Public License.
See COPYRIGHT.txt and LICENSE.txt

 */?><?php

include_once "auth_test_inc.php";

// Take the first param that isn't one of the special set
$params = $_REQUEST;
unset($params["product_id"]);
unset($params["issue_prefix"]);
unset($params["issue_start"]);
unset($params["issue_end"]);
$cred = array_shift($params);


$comments = array();
if ($cred == "badtoken") {
	$comments[] = "This token is no good. Simulate expired or cancelled";
	$token = "badbad";
} else if (in_array($cred, $all_users)) {
	$comments[] = "User is in the predefined list";
	$token = strrev($cred);
} else {
	$comments[] = "User not in the predefined list";
	$token = null;
}
_pugpig_subs_sign_in_response($token, $comments);

