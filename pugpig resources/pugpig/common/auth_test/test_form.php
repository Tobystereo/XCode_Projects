<?php
/**
 * @file
 * Pugpig Auth Test Form
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

//$active = patcf_flip_is_active($seconds_left);
//print "SECONDS: $seconds_left<br />";

$title = "Generic Test Stub";
$v["issue_prefix"] = "com.puggers.issue";
$v["issue_start"] = 10;
$v["issue_end"] = 20;
$base = pugpig_get_current_base_url() .  $_SERVER["SCRIPT_NAME"] . "?" . http_build_query($v);

$urls["sign_in"] = str_replace("test_form", "sign_in", $base);
$urls["verify_subscription"] = str_replace("test_form", "verify_subscription", $base);
$urls["edition_credentials"] = str_replace("test_form", "edition_credentials", $base);
$params = array("username");
$test_users = patcf_get_test_users($all_users);

pugpig_subs_test_form($title, $urls, $params, $test_users);
