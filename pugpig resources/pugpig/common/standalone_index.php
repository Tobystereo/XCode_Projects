<?php
/**
 * @file
 * Pugpig Standalone PHP tests
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

  pugpig_interface_output_header("Pugpig - Standalone Tests");

?>

<h1>Pugpig PHP Suite (Version <?php echo pugpig_get_standalone_version() ?>)</h1>
<img src="images/pugpig-32x32.png" />

<?php

if (!file_exists('standalone_config.php')) {
	echo "<h2>Warning - standalone_config.php not found</h2>";
	echo "In order to use these pages, you will need to configure settings in the file: <code>standalone_config.php</code>";
} else {
	include_once 'standalone_config.php';
}
?>

<p>
<a href="pugpig_packager_run.php">Packager</a><br />
This can be used to create a package from any external ATOM feed. You can also use it to test any endpoint.
The config values are entered into this form.
</p>

<p>
<a href="auth_test/test_form.php">Sample Auth Endpoint with Test Form and Test Data</a><br />
Point your app at the endpoint used in this example to test all the edge cases.
</p>

<p>
<a href="standalone_pugpig_itunes_edition_credentials.php">Configurable iTunes Receipt Validator</a><br />
This will fail unless a receipt is POSTed to the URL. In order to use this, you'll need to provide
configuration in the standalone_config.php file:
<ul>
  <li>Your iTunes app store password</li>
  <li>The common iTunes prefix used by all subscription products</li>
  <li>Your Pugpig secret used to generate Pugpig credentials</li>
 </ul>
</p>

<p><a href="standalone_pugpig_google_receipt_validation.php">Configurable Google Receipt Validator</a></p>

<p>
  <a href="standalone_pugpig_amazon_receipt_validation.php">Configurable Amazon Receipt Validator</a><br />
  In order to use this, you'll need to provide configuration in the standalone_config.php file:
  <ul>
    <li>The Amazon store base URL</li>
    <li>Your Amazon store shared secret</li>
  </ul>
</p>

<p>
<a href="standalone_pugpig_subs_test_page.php">Configurable External Subscription Test Page</a><br />
This can be use to test a third party subscription integration than follows
the specification outlined on the <a href="http://dev.pugpig.com/doku.php/wiki:arch:security:example">Pugpig Wiki</a>. You will need to set configuration values the standalone_config.php file:
<ul>
  <li>Your subscription endpoint URLs</li>
  <li>The credential parameters your endpoint accepts (e.g. username and password)</li>
  <li>Any test users you wish to use</li>
  <li>Your Pugpig secret used to generate Pugpig credentials</li>
 </ul>
</p>

<p>
<a href="stubs/fake_edition_credentials.php">Fake Credential Generator</a><br />
You can POST anything you want to this if you're happy with rubbish credentials that won't be checked. 
Only use it while testing your app.
</p> 
