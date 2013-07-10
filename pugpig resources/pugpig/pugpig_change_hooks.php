<?php
/**
 * @file
 * Pugpig Change Hooks
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
Update the last modified date of a single editions
************************************************************************/
function pugpig_touch_edition($edition_id) {
    wp_update_post( array("ID" => $edition_id, "post_modified" => current_time('mysql')) );        
}

/************************************************************************
Update the last modified date of all editions
************************************************************************/
function pugpig_touch_all_editions() {
 foreach(pugpig_get_editions() as $edition) {
    pugpig_touch_edition($edition->ID);        
 }    
}
/************************************************************************
Change theme means rebuild the theme manifest and update all editions
************************************************************************/
add_action('switch_theme', 'pugpig_theme_activate');
function pugpig_theme_activate($new_theme) {
  // pugpig_save_theme_manifest();
  // pugpig_add_admin_notice("Changed theme. Updating date of all pugpig HTML5 manifests.", "updated");
  // pugpig_build_all_html5_manifests();
  // pugpig_touch_all_editions();
}

/************************************************************************
Publish status transition handler
It doesn't seem like we need this at the moment
function pugpig_transition_post_status( $new_status, $old_status, $post )
{

  if (!isset($post)) return;
  if ($post->post_type == "revision") return;
  if ($post->post_type == "attachment") return;
  if(  ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) || $post->post_status == 'auto-draft' ) return;  

  // No point if nothing has happened
  if ($new_status == $old_status) return;
  
  
  if ($old_status == 'publish') {
  } else if ($new_status == 'publish') {
  } else {
  }

}
add_action('transition_post_status', 'pugpig_transition_post_status', 100, 3);
************************************************************************/

/************************************************************************
Save Blog Post
************************************************************************/
function pugpig_save_blog_post($post) {
    // Rebuild the HTML5 manifest for this page
    // TODO: CARLOS - don't need this anymore? pugpig_build_post_manifest_file($post);
    return;
/*
    // Stop here is this was an AJAX Quick Edit
    if (defined('DOING_AJAX') && DOING_AJAX) return;

    // Get current edition value
    $old_post_edition_id = pugpig_get_post_edition_id($post);
    
    // Get new edition from the form POST. If we are deleting, we ignore it though
    $new_post_edition_id = "";    
    if ($post->post_status != "trash" && !empty($_POST["post_edition"])) {
      $new_post_edition_id = $_POST["post_edition"];
    }
        
    // No change
    if ($old_post_edition_id == $new_post_edition_id) return;

    // Update our edition ID
    pugpig_save_post_edition_id($post->ID,  $new_post_edition_id);
    
    // Update the array for the previous edition
    if (!empty($old_post_edition_id)) {
      pugpig_delete_edition_array($old_post_edition_id, $post->ID);
    }    
    
    // Update the array for the new edition
    if (!empty($new_post_edition_id)) {
      pugpig_add_edition_array($new_post_edition_id, $post->ID);
    }        
   */
}

// Update the Post edition link
/*
function pugpig_save_post_edition_id($post_id, $edition_id) {
    if (!empty($edition_id)) {
      update_post_meta($post_id, PUGPIG_POST_EDITION_LINK, $edition_id);
    } else {
      delete_post_meta($post_id, PUGPIG_POST_EDITION_LINK);
    }
}
*/

/************************************************************************
Manipulate the array of posts in an edition
************************************************************************/
function pugpig_add_edition_array($edition_id, $post_id) {
      
      // Get the existing array
      $ecr = pugpig_get_edition_array(get_post_custom($edition_id)); 
      
      // Add the new item if it doesn't already exist
      if (!in_array($post_id, $ecr)) {
        array_push($ecr, $post_id);
        pugpig_save_edition_array($edition_id, $ecr);
      }
}

function pugpig_delete_edition_array($edition_id, $post_id) {

     // Get the existing array
      $ecr = pugpig_get_edition_array(get_post_custom($edition_id)); 
      
      // Add the new item if it doesn't already exist
      if (in_array($post_id, $ecr)) {
        $ecr = array_diff($ecr, array($post_id));
        pugpig_save_edition_array($edition_id, $ecr);
      }
}

function pugpig_save_edition_array($edition_id, $ecr, $touch = TRUE) {
        // Update the edition. Needs to update Last Modified Time
        if (count($ecr) == 0) {
          delete_post_meta($edition_id, PUGPIG_EDITION_CONTENTS_ARRAY);                     
        } else {
          update_post_meta($edition_id, PUGPIG_EDITION_CONTENTS_ARRAY, $ecr);             
        }
        
        if ($touch) {
          pugpig_touch_edition($edition_id);
        }
}


/************************************************************************
Save Hook
************************************************************************/
add_action('save_post', 'pugpig_save_post', 10);
function pugpig_save_post($post_id){

  $post = get_post($post_id);
  if (!isset($post)) return;
  if ($post->post_type == "revision") return;
  if ($post->post_type == "attachment") return;
  
  if(  ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) || $post->post_status == 'auto-draft' ) return;  
  
  pugpig_add_debug_notice("Saving " . $post_id . " (" . $post->post_type . ") -> status: " . $post->post_status);

  if ($post->post_type == PUGPIG_EDITION_POST_TYPE) {
    // Save Edition
    pugpig_save_edition($post);   
    
    // We've come via the quick edit. Maybe need something extra?
    if (defined('DOING_AJAX') && DOING_AJAX) {
      pugpig_add_debug_notice("QUICK EDIT: Status " . $post->post_status, "error");
  
    }
    
  } else  {
    // Save Blog Post, Custom Post or Page
    pugpig_save_blog_post($post);  
  }

}

/************************************************************************
Delete Hook
************************************************************************/
add_action('delete_post', 'pugpig_delete_post');
function pugpig_delete_post($post_id){
  $post = get_post($post_id);  
  if ($post->post_type == "revision") return;

  pugpig_add_debug_notice("Deleting " . $post_id . " (" . $post->post_type . ") -> status: " . $post->post_status);

/*
  // Delete Blog Post
  if ($post->post_type == "post") {
    // Update edition that post contains
    $post_edition_id = pugpig_get_post_edition_id($post);
    if (isset($post_edition_id)) {
       pugpig_delete_edition_array($post_edition_id, $post->ID);
    }
  }

  // Delete Edition
  if ($post->post_type == "pugpig_edition") {
    // Unset edition for any blog post in the addition
    // Update OPDS
    // Delete the edition manifest
  }

*/
}


/************************************************************************
Save Custom Edition
************************************************************************/
function pugpig_save_edition($post) {

  if ($post->post_status == "trash" || isset($_POST["edition_contents_array"])) {

    $newarr = array();

    if ($post->post_status != "trash") {
      // Get the new array if we're not trashing
      $newarr = explode(",", $_POST["edition_contents_array"]);
      $newarr = array_diff($newarr, array(""));
    }
            
    $oldarr = pugpig_get_edition_array(get_post_custom($post->ID)); 
    $added = array_diff($newarr, $oldarr);
    $removed = array_diff($oldarr, $newarr);
    
    foreach ($added as $p) {
      //pugpig_save_post_edition_id($p, $post->ID);
    }

    foreach ($removed as $p) {
      //pugpig_save_post_edition_id($p, "");
    }
  
    if ($post->post_status != "trash") {
      // Replace with new array if we'renot trashing
      pugpig_save_edition_array($post->ID, $newarr, FALSE);
    }
    
  }

  if (isset($_POST["edition_date"])) {
     update_post_meta($post->ID, "edition_date", $_POST["edition_date"]);
   }

  if (isset($_POST["edition_free_exists"])) {
    if (empty($_POST["edition_free"])) {
      delete_post_meta($post->ID, "edition_free");    
    } else {
      update_post_meta($post->ID, "edition_free", $_POST["edition_free"]);
    }
  }
   
   if (isset($_POST["edition_key"])) {
     update_post_meta($post->ID, "edition_key", $_POST["edition_key"]);
   }

   if (isset($_POST["edition_author"])) {
     update_post_meta($post->ID, "edition_author", $_POST["edition_author"]);
   }

   if (isset($_POST["edition_sharing_link"])) {
     update_post_meta($post->ID, "edition_sharing_link", $_POST["edition_sharing_link"]);
   }
   
}

/************************************************************************
Validate is called after save. Ensure we can't publish incomplete editions
************************************************************************/
add_action('save_post', 'pugpig_validate_post', 20);
function pugpig_validate_post($post_id) {
  $post = get_post($post_id);

  if (!isset($post)) return;
  if ($post->post_type == "revision") return;  
  if(  ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) || $post->post_status == 'auto-draft' ) return;  
  
  // Validate an edition publish
  if ($post->post_type == PUGPIG_EDITION_POST_TYPE && ( isset( $_POST['publish'] ) || isset( $_POST['save'] ) ) && $_POST['post_status'] == 'publish' ) {
    pugpig_add_debug_notice("Validating " . $post_id . " (" . $post->post_type . ") -> status: " . $post->post_status, "error");
 
    // init completion marker (add more as needed)
    $publish_errors = array();

    // retrieve meta to be validated
    $meta_edition_date = get_post_meta( $post_id, 'edition_date', true );
    
    $t = get_the_post_thumbnail($post_id);
    if (empty($t)) {
      array_push($publish_errors, $post->post_title .  ": Edition cover image not set.");
    }
   
    $meta_edition_key = get_post_meta( $post_id, 'edition_key', true );
    if ( empty( $meta_edition_key ) ) {
        array_push($publish_errors, $post->post_title . ": Edition key must be supplied.");
    }

   if ( empty( $meta_edition_date ) ) {
        array_push($publish_errors, $post->post_title . ": Edition date must be supplied.");
    } else {
      if (!pugpig_check_date_format($meta_edition_date)) {
          array_push($publish_errors, $post->post_title . ": Edition date " . $meta_edition_date .  " is not valid.");
      }
    }
    
    $ecr = pugpig_get_edition_array(get_post_custom($post->ID)); 
    if (count($ecr) == 0) {
        array_push($publish_errors, "You cannot publish an empty edition.");
    }

    // on attempting to publish - check for completion and intervene if necessary
        //  don't allow publishing while any of these are incomplete
    if ( count($publish_errors) > 0 ) {
        array_push($publish_errors, "<b>Please fix these errors before publishing an edition. The post status for edition '".$post->post_title."' has been set to Pending Review.</b>");

        global $wpdb;
        $wpdb->update( $wpdb->posts, array( 'post_status' => 'pending' ), array( 'ID' => $post_id ) );
        foreach ($publish_errors as $e) pugpig_add_admin_notice($e, "error");

        // filter the query URL to change the published message
        add_filter( 'redirect_post_location', create_function( '$location','return add_query_arg("message", "4", $location);' ) );

        return;
    }
                                                           
    // Rebuild the edition manifests
    // pugpig_build_edition_manifests($post);

    // Increase the count of items needed for push notifications
    if ($post->post_status == "publish") {
      global $pugpig_edition_changed;
      $pugpig_edition_changed++;      
      pugpig_add_debug_notice("Published edition changed: " . $post->ID);
    }
     
    
  }  
}

/************************************************************************
Validation Helpers
************************************************************************/
function pugpig_check_date_format($str) {
  $ptn = "/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/";
  if (preg_match($ptn, $str, $matches) > 0) return true;
  $ptn = "/^[0-9]{4}-[0-9]{2}$/";
  if (preg_match($ptn, $str, $matches) > 0) return true;
  $ptn = "/^[0-9]{4}$/";
  if (preg_match($ptn, $str, $matches) > 0) return true;
  
  return false;
}




