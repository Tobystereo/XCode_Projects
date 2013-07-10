<?php
/**
 * @file
 * Pugpig Auth Test Common Functions
 */
?><?php
/*

Licence:
==============================================================================
(c) 2011, Kaldor Holdings Ltd
This module is released under the GNU General Public License.
See COPYRIGHT.txt and LICENSE.txt

 */?><?php

include_once "../pugpig_subs.php";
include_once "../pugpig_subs_xml.php";
include_once "../pugpig_subs_test.php";
include_once "../pugpig_utilities.php";

$all_users = array(
"activeall", "activenone", "activesome", "activerandom",
"lapsedall", "lapsednone", "lapsedsome", "lapsedrandom",
"flipall", "flipnone", "flipesome", "fliprandom",
);


function patcf_get_issue_list($prefix, $start, $end) {
	$issues = array();
	if (!is_numeric($start) || !is_numeric($end) || $end < $start) return $issues;
	for ($i = $start; $i<=$end; $i++) {
		$issues[] = $prefix . $i;
	}
	return $issues;
}

function patcf_get_some_issues($issues, $random = TRUE) {
	$my_issues = array();
	$keep = TRUE;
	foreach ($issues as $issue) {
		if (!$random && $keep) $my_issues[] = $issue;
		if ($random && rand(0,1)) $my_issues[] = $issue;
		$keep = !$keep;
	}
	return $my_issues;
}

function patcf_is_odd($n){
	return (boolean) ($n % 2);
}

function patcf_flip_is_active(&$seconds_left) {
	$flip_seconds = 120;
	$date_array = getdate(); 

	$seconds_past_hour = $date_array['minutes'] * 60 + $date_array['seconds'];
	$pos = floor($seconds_past_hour / $flip_seconds);
	$seconds_left = $flip_seconds - ($seconds_past_hour % $flip_seconds);
	//echo $seconds_past_hour . " *** " . ($pos) . " ** " . patcf_is_odd($pos) . " **<br />";
	return patcf_is_odd($pos);
}



function patcf_is_active($all_users, $user) {
	if (!in_array($user, $all_users)) return FALSE;
	if (startsWith($user, "active")) return TRUE;
	if (startsWith($user, "flip")) return  patcf_flip_is_active($seconds);
	return FALSE;
}

function patcf_get_test_users($all_users) {
	$test_users = array();
	$test_users[] = array("state" => "UNKNOWN", "username" => "rubbish");
	$test_users[] = array("state" => "STALE", "username" => "badtoken");
	foreach ($all_users as $user) {
		$active = patcf_is_active($all_users, $user);
		$test_users[] = array("state" => $active ? "ACTIVE" : "INACTIVE", "username" => $user);
	}
	return $test_users;
}

if (isset($_REQUEST["issue_prefix"])) $issue_prefix = $_REQUEST["issue_prefix"];
if (empty($issue_prefix)) $issue_prefix = "com.pugpig.test.issue";
if (isset($_REQUEST["issue_start"])) $issue_start = $_REQUEST["issue_start"];
if (empty($issue_start)) $issue_start = "100";
if (isset($_REQUEST["issue_end"])) $issue_end = $_REQUEST["issue_end"];
if (empty($issue_end)) $issue_end = "115";

$all_issues = patcf_get_issue_list($issue_prefix, $issue_start, $issue_end);
