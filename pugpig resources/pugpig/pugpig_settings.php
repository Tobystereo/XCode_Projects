<?php
/**
 * @file
 * Pugpig WordPress Settings
 */
?><?php
/*

Licence:
==============================================================================
(c) 2011, Kaldor Holdings Ltd
This module is released under the GNU General Public License.
See COPYRIGHT.txt and LICENSE.txt

 */?><?php

function pugpig_is_internal_user(){


  $ip = getRequestIPAddress();

  if (isset($ip)) {
    if ($ip == "127.0.0.1") return TRUE;

    $internal_ip_range = get_option("pugpig_preview_ip_range");
    if (empty($internal_ip_range)) return FALSE;

    $ranges = explode(';', $internal_ip_range);
    foreach ($ranges as $range) {
      $range = trim($range);
      if (!empty($range) && ip_in_range($ip, $range)) {
        return TRUE;
      }
    }
  }  
  return FALSE;
}

function pugpig_get_allowed_types() {
  $arr = pugpig_get_array_from_comma_separate_string(get_option("pugpig_opt_allowed_types"));
  if (!empty($arr)) return $arr;
  
  return array("post", "page", PUGPIG_AD_BUNDLE_POST_TYPE);
}

function pugpig_get_category_order() {
  return pugpig_get_array_from_comma_separate_string(get_option("pugpig_opt_category_order"));
}  

function pugpig_get_itunes_subscription_array() {
  return pugpig_get_array_from_comma_separate_string(get_option("pugpig_opt_itunes_subscription_array"));
}


function pugpig_should_send_push() {
  $v = get_option("pugpig_opt_send_push");
  if (empty($v)) return false;
  return true;
}

function pugpig_should_show_debug() {
  $v = get_option("pugpig_opt_show_debug");
  if (empty($v)) return false;
  return true;
}


function pugpig_should_use_thumbs() {
  return false;
  //$v = get_option("pugpig_use_thumbs");
  //if (empty($v)) return false;
  //return true;
}

function pugpig_validate_theme($theme_name) {
  $theme_dir = get_theme_root();
  if (!is_dir($theme_dir . "/" . $theme_name) && $theme_name != "")
      {
      ?>
      <div class="error"><p><strong><?php _e('Error: invalid theme name', 'menu-test' ); ?></strong></p></div>
      <?php
      }
}

/************************************************************************
Settings
************************************************************************/
function pugpig_get_authentication_secret() {
    return get_option('pugpig_opt_authentication_secret', 'TOPSECRETCHANGEME');
}

function pugpig_get_num_editions(){
  $v = get_option("pugpig_opt_num_editions");
  if (empty($v) || !is_numeric($v) || $v < 1) return 10;
  if ($v > 100) return 100;
  return $v;
}

function pugpig_get_issue_prefix() {
    return get_option('pugpig_opt_issue_prefix', 'editions_');
}

function pugpig_get_taxonomy_name() {
    return get_option('pugpig_opt_taxonomy_name', 'category');
}

function pugpig_plugin_options() {

    //must check that the user has the required capability
    if (!current_user_can('manage_options'))
    {
      wp_die( __('You do not have sufficient permissions to access this page.') );
    }

    $hidden_field_name = 'mt_submit_hidden';

    $opt_vars = array (
       "pugpig_opt_num_editions" => pugpig_get_num_editions(),
       "pugpig_preview_ip_range" => "",      
       "pugpig_opt_issue_prefix" => pugpig_get_issue_prefix(),
       "pugpig_opt_authentication_secret" => pugpig_get_authentication_secret(),
       "pugpig_opt_theme_switch" => "",      
       "pugpig_use_thumbs" => "",
       "pugpig_thumb_service_url" => "",      
       "pugpig_thumb_regen_url" => "",      
       "pugpig_opt_send_push" => "",
       "pugpig_opt_itunes_inapp_shared_secret" => "",
       "pugpig_opt_itunes_subscription_prefix" => "",      
       "pugpig_opt_itunes_subscription_array" => "",      
       "pugpig_opt_urbanairship_key" => "",      
       "pugpig_opt_urbanairship_secret" => "",   
       "pugpig_opt_urbanairship_message" => "",   
       "pugpig_opt_cdn_domain" => "",   
       "pugpig_opt_show_debug" => "",
       "pugpig_opt_taxonomy_name" => pugpig_get_taxonomy_name(),
       "pugpig_opt_allowed_types" => "",        
       "pugpig_opt_category_order" => ""        
    );
     


    foreach (array_keys($opt_vars) as $v) {
      $opt_vars[$v]  = get_option($v, $opt_vars[$v]);
    }

    $theme_dir = get_theme_root();
    
    if( isset($_POST[ $hidden_field_name ]) && $_POST[ $hidden_field_name ] == 'Y' ) {
      foreach (array_keys($opt_vars) as $v) {
        if (!empty($_POST[ $v ])) {
          $opt_vars[$v] = $_POST[ $v ];
        } else {
          $opt_vars[$v] = "";
        }
        update_option( $v, $opt_vars[$v] );
        
      }

    ?>
    <div class="updated"><p><strong><?php _e('Settings saved.', 'menu-test' ); ?></strong></p></div>
    <?php
    }  

    pugpig_validate_theme($opt_vars['pugpig_opt_theme_switch']);

    // foreach (array_keys($opt_vars) as $v) echo $v . ":" . $opt_vars[$v] . "<br />";
    
    // Now display the settings editing screen

    echo '<div class="wrap">';

    // header

    echo '<div id="icon-edit" class="icon32 icon32-posts-pugpig_edition"><br></div>';
    echo "<h2>" . __( 'Pugpig settings', 'menu-test' ) . "</h2>";

    // settings form

    ?>
<form name="form1" method="post" action="">
<input type="hidden" name="<?php echo $hidden_field_name; ?>" value="Y">

<h3>General</h3>

<table class="form-table"> 

<tr valign="top"> 
<th scope="row"><label for="pugpig_opt_num_editions">Number of Editions to show</label></th> 
<td><input name="pugpig_opt_num_editions" type="text" id="pugpig_opt_num_editions" value="<?php echo $opt_vars["pugpig_opt_num_editions"]; ?>" class="regular-text" /></td> 
</tr> 

<tr valign="top"> 
<th scope="row"><label for="pugpig_opt_issue_prefix">Issue Prefix (e.g. com.pugpig.issue.). </label></th> 
<td><input name="pugpig_opt_issue_prefix" type="text" id="pugpig_opt_issue_prefix" value="<?php echo $opt_vars["pugpig_opt_issue_prefix"]; ?>" class="regular-text" />
<p class="description"><b>This should never be changed once you have published products</b></p></td> 
</tr> 

<tr valign="top"> 
<th scope="row"><label for="pugpig_opt_authentication_secret">Shared Auth Secret</label></th> 
<td><input name="pugpig_opt_authentication_secret" type="text" id="pugpig_opt_authentication_secret" value="<?php echo pugpig_get_authentication_secret(); ?>" class="regular-text" />
<p class="description">This is the secret used to generate and decode edition credentials.</p>
</td> 
</tr> 


<tr valign="top"> 
<th scope="row"><label for="pugpig_preview_ip_range">Allow Preview From IP Range</label></th> 
<td><input name="pugpig_preview_ip_range" type="text" id="pugpig_preview_ip_range" value="<?php echo $opt_vars["pugpig_preview_ip_range"]; ?>" class="regular-text" />
<p class="description">Vistiors from these ranges will be able to see draft editions.
Network ranges can be specified as a) Wildcard format:     1.2.3.* b) CIDR format:         1.2.3/24  OR  1.2.3.4/255.255.255.0
c) Start-End IP format: 1.2.3.0-1.2.3.255<br />

You are an <b><?php echo pugpig_is_internal_user() ? "Internal" : "external" ?></b> user from <b><?php echo getRequestIPAddress() ?></b></p></td> 
</tr> 

<tr valign="top"> 
<th scope="row"><label for="pugpig_opt_theme_switch">Alternative theme for Pugpig app</label></th> 
<td><input name="pugpig_opt_theme_switch" type="text" id="pugpig_opt_theme_switch" value="<?php echo $opt_vars["pugpig_opt_theme_switch"]; ?>" class="regular-text" />
  <p class="description">Use the theme slug. If this is supplied, Pugpig pages will use this theme instead of the selected blog theme. You will need to use this if you want to use a different theme for your web site and your Pugpig app</p>
</td> 
</tr> 

</table>

<p></p>

<!--
<h3>Page Thumbnails</h3>
<p>Thumbnails can be used for the web reader, and also enhance the flatplan sorting view.</p>

<table class="form-table"> 

<tr valign="top"> 
<th scope="row"><label for="pugpig_use_thumbs">Use thumbnails</label></th> 
<td><input name="pugpig_use_thumbs" type="checkbox" id="pugpig_use_thumbs" <?php if (!empty($opt_vars["pugpig_use_thumbs"])) echo "checked"; ?>  /></td> 
</tr> 

<tr valign="top"> 
<th scope="row"><label for="pugpig_thumb_service_url">Thumbnail Display URL</label></th> 
<td><input name="pugpig_thumb_service_url" type="text" id="pugpig_thumb_service_url" value="<?php echo $opt_vars["pugpig_thumb_service_url"]; ?>" class="regular-text" /></td> 
</tr> 

<tr valign="top"> 
<th scope="row"><label for="pugpig_thumb_regen_url">Thumbnail Regen URL</label></th> 
<td><input name="pugpig_thumb_regen_url" type="text" id="pugpig_thumb_regen_url" value="<?php echo $opt_vars["pugpig_thumb_regen_url"]; ?>" class="regular-text" /></td> 
</tr> 
</table> 
-->


<h3>iTunes settings</h3>
<p>If you are selling single editions or subscriptions via iTunes, you need to supply the following settings. Your application should validate receipts at wp-content/plugins/pugpig/edition_credentials.php</p>

<?php
$itunes_validation_url = get_bloginfo('wpurl') . "/" . PUGPIG_ITUNES_EDITION_CREDENTIALS;
?>

<table class="form-table"> 

<tr valign="top"> 
<th scope="row"><label for="pugpig_opt_itunes_inapp_shared_secret">iTunes In App Shared Secret</label></th> 
<td><input name="pugpig_opt_itunes_inapp_shared_secret" type="text" id="pugpig_opt_itunes_inapp_shared_secret" value="<?php echo $opt_vars["pugpig_opt_itunes_inapp_shared_secret"]; ?>" class="regular-text" /><p class="description">This is needs to verify receipts with iTunes. Your Pugpig client will need to validate iTunes receipts at <a target="_blank" href="<?php echo $itunes_validation_url ?>"><?php echo $itunes_validation_url ?></a></p></td> 
</tr> 

<tr valign="top"> 
<th scope="row"><label for="pugpig_opt_itunes_subscription_prefix">iTunes Allowed Subscription Item Prefix</label></th> 
<td><input name="pugpig_opt_itunes_subscription_prefix" type="text" id="pugpig_opt_itunes_subscription_prefix" value="<?php echo $opt_vars["pugpig_opt_itunes_subscription_prefix"]; ?>" class="regular-text" /><p class="description">Any items with a product ID starting with this prefix will be treated as a subscription product</p></td> 
</tr> 

<tr valign="top"> 
<th scope="row"><label for="pugpig_opt_itunes_subscription_array">iTunes Allowed Subscription Item Array</label></th> 
<td><input name="pugpig_opt_itunes_subscription_array" type="text" id="pugpig_opt_itunes_subscription_array" value="<?php echo implode(", ", pugpig_get_itunes_subscription_array()) ?>" class="regular-text" /><p class="description">Any items in this comma separated list will be treated as a subscription product</p></td> 
</tr> 

</table>


<h3>Push Notifications</h3>
<p>We are currently using Urban Airship for background Push Notifications when new editions arrive. Future versions of this plugin may implement the push functionality itself. If a message is given, a second push containing the message is sent.</p>

<table class="form-table"> 

<tr valign="top"> 
<th scope="row"><label for="pugpig_opt_send_push">Send push notifications</label></th> 
<td><input name="pugpig_opt_send_push" type="checkbox" id="pugpig_opt_send_push" <?php if (!empty($opt_vars["pugpig_opt_send_push"])) echo "checked"; ?>  /></td> 
</tr> 

<tr valign="top"> 
<th scope="row"><label for="pugpig_opt_urbanairship_key">Urban Airship Key</label></th> 
<td><input name="pugpig_opt_urbanairship_key" type="text" id="pugpig_opt_urbanairship_key" value="<?php echo $opt_vars["pugpig_opt_urbanairship_key"]; ?>" class="regular-text" /></td> 
</tr> 

<tr valign="top"> 
<th scope="row"><label for="pugpig_opt_urbanairship_secret">Urban Airship Master Secret</label></th> 
<td><input name="pugpig_opt_urbanairship_secret" type="text" id="pugpig_opt_urbanairship_secret" value="<?php echo $opt_vars["pugpig_opt_urbanairship_secret"]; ?>" class="regular-text" /></td> 
</tr> 

<tr valign="top"> 
<th scope="row"><label for="pugpig_opt_urbanairship_message">Urban Airship Message</label></th> 
<td><input name="pugpig_opt_urbanairship_message" type="text" id="pugpig_opt_urbanairship_message" value="<?php echo $opt_vars["pugpig_opt_urbanairship_message"]; ?>" class="regular-text" /></td> 
</tr> 

</table>

<h3>Content Delivery Network</h3>
<p>The module supports using a CDN to serve static assets and packages</p>
<table class="form-table"> 

<tr valign="top"> 
<th scope="row"><label for="pugpig_opt_cdn_domain">CDN Domain</label></th> 
<td><input name="pugpig_opt_cdn_domain" type="text" id="pugpig_opt_cdn_domain" value="<?php echo $opt_vars["pugpig_opt_cdn_domain"]; ?>" class="regular-text" />
  <p class="description">Use this if you wish to serve static assets from a content delivery network. No trailing slash, e.g. http://my.cdn.com</a>
</td> 
</tr> 
</table>

<h3>Debug and Testing</h3>
<p>Various settings for debugging and testing. By default, the plugin will only rewrite post markup for requests from a Pugpig client.</p>

<table class="form-table"> 


<tr valign="top"> 
<th scope="row"><label for="pugpig_opt_show_debug">Output debug admin messages</label></th> 
<td><input name="pugpig_opt_show_debug" type="checkbox" id="pugpig_opt_show_debug" <?php if (!empty($opt_vars["pugpig_opt_show_debug"])) echo "checked"; ?>  /></td> 
</tr> 

</table>

<h3>Assign New Posts To Editions</h3>
<p>At present, new posts are not assigned to an edition. We may introduce features to set defaults.</p>

<table class="form-table">
<tr valign="top"> 
<th scope="row"><label for="pugpig_opt_taxonomy_name">Taxonomy to allow on editions</label></th> 
<td><input name="pugpig_opt_taxonomy_name" type="text" id="pugpig_opt_taxonomy_name" value="<?php echo pugpig_get_taxonomy_name() ?>" class="regular-text" />
  <p class="description">The taxonomy slug to use for placing items into an edition. 
    For categories, use <b>category</b>, for tags use <b>post_tag</b>. 
  Otherwise enter the name of your custom taxonomy.<br />
  <?php
    $taxonomy_name = pugpig_get_taxonomy_name();
    if (!empty($taxonomy_name) && taxonomy_exists($taxonomy_name)) {
      $taxonomy = get_taxonomy($taxonomy_name);
      echo "<span style='color:green;'>Taxonomy <b>" . $taxonomy->labels->name . "</b> exists</span>";
    } else {
      echo "<span style='color:red;'>Taxonomy does not exist</span>";
    }
  ?>
    </p>
</td> 
</tr> 
<tr valign="top"> 
<th scope="row"><label for="pugpig_opt_allowed_types">Allowed Post Types (Comma separated)</label></th> 
<td><input name="pugpig_opt_allowed_types" type="text" id="pugpig_opt_allowed_types" value="<?php echo implode(", ", pugpig_get_allowed_types()) ?>" class="regular-text" /></td> 
</tr> <tr valign="top"> 
<th scope="row"><label for="pugpig_opt_category_order">Auto Curator Category Order (Comma separated)</label></th> 
<td><input name="pugpig_opt_category_order" type="text" id="pugpig_opt_category_order" value="<?php echo implode(", ", pugpig_get_category_order()) ?>" class="regular-text" /></td> 
</tr> 
</table>


<p class="submit">
<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
</p>

</form>
</div>

<img style="float:right;"width="100" src="<?php echo(BASE_URL) ?>common/images/pugpig-large.png" />

<p>Thank you for using the Pugpig Connector for WordPress.</p>
<p>See the <a href="http://dev.pugpig.com/doku.php/server:existing:wordpress:releasenotes">Pugpig for WordPress Change Log</a></p>
<p>Go to <a href='http://pugpig.com/resources'>The Pugpig Resources Page</a> to get:<ul><li>- the latest version of this module</li><li>- other software in the Pugpig suite</li><li>- tutorials and product documentation</li></ul></p>
<p>If you have any feedback, suggestions or product bug reports, drop us a line at <a href='mailto:info@pugpig.com?subject=I have been using your WordPress module and ...'>info@pugpig.com</a></p>

<p style='font-size: smaller;'>&copy; (c) 2011, Kaldor Holdings Ltd. All rights reserved.</p>
<p style='font-size: smaller;'>This module is released under the GNU General Public License.</p>
<p style='font-size: smaller;'>See COPYRIGHT.txt and LICENSE.txt</p>

<?php

}
