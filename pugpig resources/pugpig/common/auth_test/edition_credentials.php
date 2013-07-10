<?php
/**
 * @file
 * Pugpig Auth Test Edition Credentials
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

$token = $_REQUEST["token"];
$product_id = $_REQUEST["product_id"];

$user = strrev($token);

$state = "";
$comments = array();
$message = '';
$issues = NULL;

$secret = "THIS_IS_SECRET";

$flip_active = patcf_flip_is_active($seconds);

if ($token == "badbad") {
	$state = "stale";
	$message = "You need to log in again!";
	$comments[] = "Your token is no longer valid";
} else if (patcf_is_active($all_users, $user)) {
	$state = "active";
	if (startsWith($user, "flip")) {
		$message = "Your subscription will expire in $seconds seconds.";
	} else {
		$message = "Your subscription is active.";		
	}
} else {
	$state = "inactive";
	if (startsWith($user, "flip")) {
		$message = "Your subscription will become active in $seconds seconds.";
	} else {
		$message = "You do not have an active subscription.";		
	}
}

if (endsWith($user, "all")) {
	$issues = NULL;
	$message .= " You should have access to all issues while subscribed.";
} else if (endsWith($user, "none")) {
	$issues = array();
	$message .= " Sadly you don't have access to any issues anyway.";
} else if (endsWith($user, "some")) {
	$issues = patcf_get_some_issues($all_issues, FALSE);
	$message .= " You have access to every second issue.";	
} else if (endsWith($user, "random")) {
	$issues = patcf_get_some_issues($all_issues, TRUE);
	$message .= " You have access to an ever changing random set. Any download may fail";	
} else {
	$issues = array();
	$message .= " We don't know who you are.";
}

if ($issues === NULL || in_array($product_id, $issues)) {
	$entitled = TRUE;
} else {
	$entitled = FALSE;
}
_pugpig_subs_edition_credentials_response($product_id, $secret, $entitled, $state, $comments);

