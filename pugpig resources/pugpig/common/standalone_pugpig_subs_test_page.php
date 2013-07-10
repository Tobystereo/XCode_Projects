<?php
/**
 * @file
 * Pugpig Subscription Test Page
 */
?><?php
/*

Licence:
==============================================================================
(c) 2011, Kaldor Holdings Ltd
This module is released under the GNU General Public License.
See COPYRIGHT.txt and LICENSE.txt

 */?><?php

include_once 'pugpig_utilities.php';
include_once 'pugpig_interface.php';
include_once 'pugpig_subs_test.php';

if (!file_exists('standalone_config.php')) {
	echo "<h2>Warning - standalone_config.php not found</h2>";
	echo "In order to use this page, you will need to configure settings in the file: <code>standalone_config.php</code>";
	exit();
} 
include_once 'standalone_config.php';

pugpig_interface_output_header("Pugpig - Subscription Test Page");
pugpig_subs_test_form($title, $urls, $params, $test_users);
