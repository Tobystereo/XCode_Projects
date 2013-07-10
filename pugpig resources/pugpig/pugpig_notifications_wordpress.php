<?php
/**
 * @file
 * Pugpig Notifications for WordPress
 */
?><?php
/*

Licence:
==============================================================================
(c) 2011, Kaldor Holdings Ltd
This module is released under the GNU General Public License.
See COPYRIGHT.txt and LICENSE.txt

 */?><?php

/************************************************************************
Send push notification
************************************************************************/
$push_notification_sent = FALSE;
function pugpig_send_push_notification($message) {
  global $push_notification_count;
  if ($push_notification_count) {
      return;
  }
  $push_notification_count = TRUE;

  $ret = "<div>Sending \"$message\"...</div>";

  if (!pugpig_should_send_push()) {
    pugpig_add_debug_notice("Push notifications not enabled");
    return;
  }
    
  // Currently only Urban Airship
  $key = get_option("pugpig_opt_urbanairship_key");
  $secret = get_option("pugpig_opt_urbanairship_secret");
  
  if (!empty($key) && !empty($secret)) {
    $ret = $ret . '<div>' . pugpig_push_to_urban_airship ($key, $secret, 1, $message) . '</div>';
    //pugpig_add_admin_notice($ret, "updated");
  } else {
    $ret = $ret . '<div>' . "Could not send push notification. Urban Airship key/secret not set in settings area." . '</div>';
    //pugpig_add_admin_notice($ret, "error");
  }
  
  return $ret;
}

function pugpig_push_notification_form() {
  if (isset($_POST['pugpig_push_message_type']) && $_POST['pugpig_push_message_type'] != '') {
    if ($_POST['pugpig_push_message_type'] == 'custom') {
      echo pugpig_send_push_notification($_POST['pugpig_push_message_custom']);
    } elseif ($_POST['pugpig_push_message_type'] == 'edition') {
      echo pugpig_send_push_notification($_POST['pugpig_push_message_edition']);
    } elseif ($_POST['pugpig_push_message_type'] == 'default') {
      echo pugpig_send_push_notification($_POST['pugpig_push_message_default']);
    }
    echo "<div><a href='..''>Return</a></div>";
  } else {
    $last_edition_summary = '';
    $editions = pugpig_get_editions('publish', 1);
    if (count($editions) > 0) {
      $last_edition_summary = $editions[0]->post_excerpt;
    }
 
$hidden_field_name = 'mt_submit_hidden';
     
?>

<div id="icon-edit" class="icon32 icon32-posts-pugpig_edition"><br /></div><h2>Pugpig Push Notification</h2>

Using this form will send a push notifications to all the devices registered with your Pugpig Application. Use with care.

<form name="form1" method="post" action="">
<input type="hidden" name="<?php echo $hidden_field_name; ?>" value="Y">

<table class="form-table"> 

<tr valign="top"> 
<th scope="row"><label for="pugpig_push_message_type">The last edition summary</label></th> 
<td><input type="radio" name="pugpig_push_message_type" value="edition" /><input type="hidden" name="pugpig_push_message_edition" value="<?php echo $last_edition_summary; ?>" /></td> 
<td><?php echo $last_edition_summary; ?></td>
</tr> 

<tr valign="top"> 
<th scope="row"><label for="pugpig_push_message_type">The default notification message</label></th> 
<td><input type="radio" name="pugpig_push_message_type" value="default" /><input type="hidden" name="pugpig_push_message_default" value="<?php echo get_option("pugpig_opt_urbanairship_message"); ?>" /></td> 
<td><?php echo get_option("pugpig_opt_urbanairship_message"); ?></td>
</tr> 

<tr valign="top"> 
<th scope="row"><label for="pugpig_push_message_type">Your own message</label></th> 
<td><input type="radio" name="pugpig_push_message_type" value="custom" /></td>
<td><input name="pugpig_push_message_custom" type="text" style="width:400px" /></td> 
</tr> 

<tr valign="top"> 
<th scope="row"></th> 
<td></td> 
<td><input type="submit" value="Send!" /></td> 
</tr> 

</table>
</form>
<?php
  }
  exit();
}