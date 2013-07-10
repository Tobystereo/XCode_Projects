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

$token = $_REQUEST["token"];
$user = strrev($token);

$state = "";
$comments = array();
$message = '';
$issues = NULL;

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
}
_pugpig_subs_verify_subscription_response($state, $comments, $message, $issues);
