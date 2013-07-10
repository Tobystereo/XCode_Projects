<?php
/**
 * @file
 * Pugpig WordPress Admin Screens
 */
?><?php
/*

Licence:
==============================================================================
(c) 2011, Kaldor Holdings Ltd
This module is released under the GNU General Public License.
See COPYRIGHT.txt and LICENSE.txt

 */?><?php

define( 'PUGPIG_EDITION_POST_TYPE', 'pugpig_edition');
define( 'PUGPIG_EDITION_CONTENTS_ARRAY', 'pugpig_edition_contents_array');
// define( 'PUGPIG_POST_EDITION_LINK', 'pugpig_post_edition_link');
//define( 'BASE_URL',  WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__)));
define( 'BASE_URL', plugins_url('pugpig/')); // Carlos - BASE_URL definition above doesn't return soft link directories

/************************************************************************
Get all the editions
************************************************************************/
function pugpig_get_editions($status='all', $num_posts = -1) {  
  if ($status == 'all') {
    // All posts - used in the admin screens
    $args = array( 'post_type' => PUGPIG_EDITION_POST_TYPE, 'post_status' => 'any', 'numberposts' => $num_posts);
    return get_posts($args);
  } else if ($status == 'publish') {
    // Used for live OPDS feed
    $args = array( 'post_type' => PUGPIG_EDITION_POST_TYPE, 'post_status' => 'publish', 'numberposts' => $num_posts);
    return get_posts($args);
  } else if ($status == 'preview') {
    // Used for preview OPDS feed
    $args = array( 'post_type' => PUGPIG_EDITION_POST_TYPE, 'post_status' => 'draft', 'numberposts' => $num_posts);
    $draft =  get_posts($args);
    $args = array( 'post_type' => PUGPIG_EDITION_POST_TYPE, 'post_status' => 'pending', 'numberposts' => $num_posts);
    $pending =  get_posts($args);   
    $merged =  array_merge( $draft, $pending );  
    return $merged;
  } 
  
  // ERROR
  return null;
}

/************************************************************************
Get the array of posts against an edition
************************************************************************/
function pugpig_get_edition_array($custom) {
      // Get the existing array
      $ecr = array();
      if (!empty($custom[PUGPIG_EDITION_CONTENTS_ARRAY][0])) {
         $ecr = unserialize($custom[PUGPIG_EDITION_CONTENTS_ARRAY][0]);
      }
      return $ecr;
}

  
/************************************************************************
Get the edition id in which a post sits
************************************************************************/

function pugpig_get_post_editions($post) {
  $my_editions = array();
  $editions = pugpig_get_editions(); 
  foreach ($editions as $edition) {
    $page_array = pugpig_get_edition_array(get_post_custom($edition->ID));
    if (in_array($post->ID, $page_array)) $my_editions[]= $edition;
    //print_r($page_array);
    //$option .= $edition->post_title . ' (' . $edition->post_status . ')';
    //echo $option;
  }
  return $my_editions;
  /*
    $post_edition_id = "";
    $custom = get_post_custom($post->ID);
    if (!empty($custom[PUGPIG_POST_EDITION_LINK][0])) {
      $post_edition_id = $custom[PUGPIG_POST_EDITION_LINK][0];
    }
    return $post_edition_id;
*/
}


/************************************************************************
Gets the URL to a path
************************************************************************/
function pugpig_path_to_abs_url($path) {
  $path = str_replace(DIRECTORY_SEPARATOR, "/", $path);    
  $abs_path = str_replace(DIRECTORY_SEPARATOR, "/", ABSPATH);
  return site_url() . "/" . str_replace($abs_path, "", $path);
}


/************************************************************************
Add the ability to filter posts by custom fields
************************************************************************/
add_filter( 'parse_query', 'pugpig_admin_posts_filter' );
function pugpig_admin_posts_filter( $query )
{
    global $pagenow;
    if ( is_admin() && $pagenow=='edit.php' && isset($_GET['ADMIN_FILTER_FIELD_NAME']) && $_GET['ADMIN_FILTER_FIELD_NAME'] != '') {
        $query->query_vars['meta_key'] = $_GET['ADMIN_FILTER_FIELD_NAME'];
    if (isset($_GET['ADMIN_FILTER_FIELD_VALUE']) && $_GET['ADMIN_FILTER_FIELD_VALUE'] != '')
        $query->query_vars['meta_value'] = $_GET['ADMIN_FILTER_FIELD_VALUE'];
    }
}


/************************************************************************
Helpers to generate internal admin links
************************************************************************/
/*
function pugpig_edition_filter_link($edition_id, $text) {
 return '<a href="edit.php?post_type=post&ADMIN_FILTER_FIELD_NAME='.PUGPIG_POST_EDITION_LINK.'&ADMIN_FILTER_FIELD_VALUE='.$edition_id.'">' . $text . '</a>';
}
*/
function pugpig_edition_edit_link($edition_id, $text) {
 return '<a href="post.php?post='.$edition_id.'&action=edit">' . $text . '</a>';
}

/************************************************************************
Create a new custom type for editions
************************************************************************/
add_action('init', 'pugpig_editions_register');

function pugpig_editions_register() {

  $labels = array(
    'name' => _x('Editions', 'post type general name'),
    'singular_name' => _x('Edition', 'post type singular name'),  
    'add_new' => _x('Add New', 'Edition item'),
    'add_new_item' => __('Add New Pugpig Edition'),
    'edit_item' => __('Edit Edition Item'),
    'new_item' => __('New Edition Item'),
    'view_item' => __('View Edition Item'),
    'search_items' => __('Search Editions'),
    'not_found' =>  __('Nothing found'),
    'not_found_in_trash' => __('Nothing found in Trash'),
    'parent_item_colon' => ''
  );

  $args = array(
    'labels' => $labels,
    'singular_label' => $labels['singular_name'],          
    'public' => true,
    'publicly_queryable' => true,
    'show_ui' => true,
    'query_var' => true,
    'rewrite' => true,
    'capability_type' => 'post',
    'hierarchical' => false,
    'menu_position' => null,
    'supports' => array('title', 'excerpt','thumbnail') // Custom Fields for debug 'custom-fields'
    );

  register_post_type( PUGPIG_EDITION_POST_TYPE , $args );

  // Add a taxonomy if the settings say we need one
  $taxonomy_name = pugpig_get_taxonomy_name();
  if (!empty($taxonomy_name) && taxonomy_exists($taxonomy_name)) {
    register_taxonomy_for_object_type($taxonomy_name, PUGPIG_EDITION_POST_TYPE); 
  }

}

/************************************************************************
Enable post-thumbnails support for edition post type
************************************************************************/
function pugpig_add_featured_image_support()
{
    $supportedTypes = get_theme_support( 'post-thumbnails' );
    if( $supportedTypes === false ) { 
        add_theme_support( 'post-thumbnails', array( PUGPIG_EDITION_POST_TYPE ) );               

    } elseif( is_array( $supportedTypes ) ) {
        $supportedTypes[0][] = PUGPIG_EDITION_POST_TYPE;
        add_theme_support( 'post-thumbnails', $supportedTypes[0] );
    }
}
add_action( 'after_setup_theme',    'pugpig_add_featured_image_support', 11 );

/************************************************************************
Custom fields required for an edition
************************************************************************/
function pugpig_edition_info(){
  global $post;
  $custom = get_post_custom($post->ID);
  $edition_date = date("Y-m-d");
  if (isset($custom["edition_date"])) {
    $edition_date = $custom["edition_date"][0];
  }
  $edition_free = TRUE;;                        
  if (isset($custom["edition_free"])) {
    $edition_free = $custom["edition_free"][0];

  } else {
    $edition_free = FALSE;;
  }

  $edition_key = "";
  if (isset($custom["edition_key"])) {
    $edition_key = $custom["edition_key"][0];
  }

  $edition_author = "";
  if (isset($custom["edition_author"])) {
    $edition_author = $custom["edition_author"][0];
  }

  $edition_sharing_link = "";
  if (isset($custom["edition_sharing_link"])) {
    $edition_sharing_link = $custom["edition_sharing_link"][0];
  }

  ?>

  <label>Key:</label>
  <input name="edition_key" value="<?php echo $edition_key; ?>" />
  <p>The key for the edition which needs to match the iTunes ID for the issue. NEVER CHANGE THIS AFTER AN EDITION IS PUBLISHED</p>

  <label>Date:</label>
  <input name="edition_date" value="<?php echo $edition_date; ?>" />
  <p>The date of the edition or issue.  (YYYY, YYYY-MM or YYYY-MM-DD)</p>

  <label>Author:</label>
  <input name="edition_author" value="<?php echo $edition_author; ?>" />
  <p>Author of this edition. Optional.</p>


  <label>Free Edition?:</label>
  <input name="edition_free_exists" type="hidden" value="1"  />
  <input name="edition_free" type="checkbox" <?php if ($edition_free) print(" checked");  ?> />
  <p>Is this edition free?</p>

  <label>Sharing Link:</label>
  <input name="edition_sharing_link"  value="<?php echo $edition_sharing_link; ?>" />
  <p>This is an optional sharing links for ALL pages in the edition that do not specify their own</p>

  <?php

}

/************************************************************************
Edition Edit Boxes on the Edition Screen and the Post screen
************************************************************************/
add_action("admin_init", "pugpig_admin_init");
function pugpig_admin_init(){

  // Needed for sortable flatplan
  wp_register_script( 'mootoolscore', BASE_URL . "js/mootools-core-1.3.2-full-compat.js");
  wp_register_script( 'mootoolsmore', BASE_URL . "js/mootools-more-1.3.2.1.js");
  wp_enqueue_script( 'mootoolscore' );
  wp_enqueue_script( 'mootoolsmore' );

  $args=array(
    'public'   => true,
    '_builtin' => false
  ); 
  $output = 'names'; // names or objects, note names is the default
  $operator = 'and'; // 'and' or 'or'
  $post_types=get_post_types($args); 
  foreach ($post_types  as $post_type ) {
    if ($post_type != PUGPIG_EDITION_POST_TYPE) {
      add_meta_box("post_info-meta", "Edition", "pugpig_post_info", $post_type, "side", "high");
    }
  }  
  
  add_meta_box("post_info-meta", "Edition", "pugpig_post_info", "post", "side", "high");
  add_meta_box("post_info-meta", "Edition", "pugpig_post_info", "page", "side", "high");
  
  add_meta_box("pugpig-edition_info-meta", "Edition Info", "pugpig_edition_info", PUGPIG_EDITION_POST_TYPE, "normal", "default");  
  add_meta_box("pugpig-edition_flatplan-meta", "Flatplan", "pugpig_flatplan_info", PUGPIG_EDITION_POST_TYPE, "normal", "default");

  // TODO: Something like this:
  // http://shibashake.com/wordpress-theme/switch_theme-vs-theme-switching
  
  
}

/************************************************************************
Feeds
************************************************************************/
function pugpig_create_opds_feed() {
  define('DONOTCDN', 'PUGPIG');
  load_template( WP_PLUGIN_DIR . '/pugpig/feeds/pugpig_feed_opds.php'); 
}  
add_action('do_feed_opds', 'pugpig_create_opds_feed', 10, 1); 

function pugpig_create_edition_feed() {
  define('DONOTCDN', 'PUGPIG');
  load_template( WP_PLUGIN_DIR . '/pugpig/feeds/pugpig_feed_edition.php');
}
add_action('do_feed_edition', 'pugpig_create_edition_feed', 10, 1); 

/*
The default (package) mode:
http://wordpress.xx.com/pugpig/?feed=opds
Atom mode:
http://wordpress.xx.com/pugpig/?feed=opds&atom=true
Package+internal mode:
http://wordpress.xx.com/pugpig/?feed=opds&internal=true
Atom+internal mode:
http://wordpress.xx.com/pugpig/?feed=opds&atom=true&internal=true
*/

function pugpig_feed_opds_link($internal=false, $atom= false) {
  $odps_link = site_url() . "/feed/opds/";
  $first = TRUE;
  if ($internal) {
     $odps_link .=  ($first ? "?" : "&") . "internal=true";
     $first = FALSE;
  } 
  if ($atom) {
     $odps_link .= ($first ? "?" : "&") . "atom=true";
     $first = FALSE;
  }   
  return $odps_link;
}


// http://www.howtocreate.in/how-to-create-a-custom-rss-feed-in-wordpress/
function custom_feed_rewrite($wp_rewrite) {
$feed_rules = array(

'feed/(.+)' => 'index.php?feed=' . $wp_rewrite->preg_index(1),
'(.+).xml' => 'index.php?feed='. $wp_rewrite->preg_index(1)
);
$wp_rewrite->rules = $feed_rules + $wp_rewrite->rules;

//print_r($wp_rewrite); exit();
}
add_filter('generate_rewrite_rules', 'custom_feed_rewrite');

/************************************************************************
Create the edition selector box used on the posts and pages
************************************************************************/
function pugpig_post_info(){
  global $post;

  // Get the currect editions 
  $post_editions =  pugpig_get_post_editions($post);
  if (empty($post_editions)) {
    echo 'No editions';
  } else {
    echo "<ul>";
    foreach ($post_editions as $edition) {
      echo "<li>" . $edition->post_title . "</li>";
    }
    echo "</ul>";
  }
  /*
  print_r($post_editions);
  return;

  $pages = pugpig_get_editions(); 
  echo '<select name="post_edition">';
  echo '<option value="">&mdash; Select &mdash;</option>';

  foreach ($pages as $edition) {
    $option = '<option value="' . $edition->ID . '" ' . ($edition->ID == $post_edition ? " selected" : "") . '>';
    $option .= $edition->post_title . ' (' . $edition->post_status . ')';
    $option .= '</option>';
    echo $option;
  }
  echo '</select>';   
*/
  ?>
    
  <img src="<?php echo(BASE_URL) ?>common/images/pugpig-32x32.png" />
  
  <?php
  // echo '<p>Sharing Link:<br /><input type="text" name="post_sharing_link"></p>';

}

function pugpig_get_feed_post_title($post) {
  $title = $post->post_title;

  // Allow modules to change the title
  $title = apply_filters('pugpig_feed_post_title', $title, $post);

  return $title;
}

function pugpig_get_feed_post_level($post) {
  $level = "1";

  // Allow modules to change the level
  $level = apply_filters('pugpig_feed_post_level', $level, $post);

  return $level;
}

function pugpig_get_feed_post_summary($post) {
  $summary = $post->post_excerpt;

  // Allow modules to change the title
  $summary = apply_filters('pugpig_feed_post_summary', $summary, $post);
  return $summary;
}
 
function pugpig_get_feed_post_author($post) {
  $author = "";
  $this_author = $post->post_author;
  if (!empty($this_author)) {
    $author = get_userdata($this_author)->display_name;
  }  

  // Allow modules to change the title
  $author = apply_filters('pugpig_feed_post_author', $author, $post);

  return $author;
}

function pugpig_get_feed_post_categories($post) {
  $categories = array();
  $page_categories = wp_get_post_categories( $post->ID );    

  $categories = apply_filters('pugpig_feed_post_categories', $categories, $post);

  // If no module has helped out, use the slug
  if (count($categories) == 0) {
    foreach($page_categories as $c)  {
      $cat = get_category( $c );
      if ($cat->slug != 'uncategorized') {
        $categories[] = $cat->slug;
      }
    }
  }

  return $categories;
}

function pugpig_get_feed_post_custom_categories($post) {
  $categories = array();
  
  // Allow modules to change the title
  $categories = apply_filters('pugpig_feed_post_custom_categories', $categories, $post);
  return $categories;
}

/* Get the Top Level category */
function pugpig_get_category_parent( $id, $visited = array() ) {
         
   $parent = &get_category( $id );
   if ( is_wp_error( $parent ) )
           return $parent;

  $name = $parent->slug;
   
   if ( $parent->parent && ( $parent->parent != $parent->term_id ) && !in_array( $parent->parent, $visited ) ) {
           $visited[] = $parent->parent;
           return pugpig_get_category_parent( $parent->parent, $visited );
   }

   return $parent;
}

function pugpig_get_first_category($post) {
  $cats = get_the_category($post->ID);
  if (!empty($cats)) {
    return $cats[0];
  }
  return "";
}

function pugpig_get_first_category_slug($post) {
  $cats = get_the_category($post->ID);
  if (!empty($cats)) {
    return $cats[0]->slug;
  }
  return "";
}

function pugpig_get_flatplan_style($post) {
  $style = "font-weight: bold;";

  // Allow modules to change the title
  $style = apply_filters('pugpig_flatplan_style', $style, $post);

  return $style;
}

function pugpig_get_flatplan_description($post) {
  $desc = "[<b style='".pugpig_get_flatplan_style($post) . "'>$post->post_type</b>] ";

  $desc .= pugpig_get_feed_post_title($post);

  $first_category = pugpig_get_first_category($post);
  if (!empty($first_category)) {

    $category_desc = $first_category->slug;
 
    // Add the top level category
    $parent_category = pugpig_get_category_parent($first_category->term_id);

    if (isset($parent_category) && $parent_category->slug != $first_category->slug) {
       $category_desc =  $parent_category->slug . " -> " . $category_desc;
    }
    $desc .= " (<span style='color:blue'>". $category_desc . "</span>)";

    $desc .=  "<span style='color:green'> " . pugpig_get_feed_post_author($post) . "</span>";


    $summary = strip_tags(pugpig_get_feed_post_summary($post));

    $desc .=  "<span style='color:orange'> " . $summary . "</span>";
 

  }


  $desc = apply_filters('pugpig_flatplan_description', $desc, $post);

  return $desc;
}


// This is used to order a query set by categories
function pugpig_category_orderby( $a, $b ) {

      $category_order_arr = pugpig_get_category_order();

      $apos = array_search( pugpig_get_first_category_slug($a), $category_order_arr );
      if ($apos === FALSE) $apos = 10000;
      $bpos   = array_search( pugpig_get_first_category_slug($b), $category_order_arr );
      if ($bpos === FALSE) $bpos = 10000;

      return ( $apos < $bpos ) ? -1 : 1;
  }


/************************************************************************
************************************************************************/
function pugpig_get_post_terms($post) {
  // Get posts in the categories for the right hand side
  $taxonomy_name = pugpig_get_taxonomy_name();
  if (empty($taxonomy_name)) return array();
  $term_objects = wp_get_post_terms( $post->ID, $taxonomy_name);
  $terms = array();
  foreach ($term_objects as $term_object) {
    $terms[] = $term_object->slug;
  }
  return $terms;
}

/************************************************************************
Flatplan editing interface
Need the ability to drag recent post/pages that are not currently into
an edition into the current edition
************************************************************************/
function pugpig_flatplan_info() {
  global $post;


  ?>
  
  <style>
  
  
  img.thumb { border: 1px solid green; margin: 2px; width: 71px; height: 102px; }
    
#pugpig_included LI  {
  cursor: move;
  padding: 3px;
}

#pugpig_included UL {
  border: 1px solid #000;
  float: left;
  min-height: 200px;
  margin: 5px;
  min-width: 200px;
}

/*
    span.thumb { border: 1px solid yellow; margin: 2px; width: 71px; height: 102px; }


 ul.pugpig_thumbs {
  list-style: none;
  margin: 5px auto 0;
  }

.pugpig_thumbs li {
  cursor: move;
 // padding: 3px;
  float: left;
  } 

.pugpig_thumbs li a:hover img {
  opacity:0.3;  filter:alpha(opacity=30);
  }

.pugpig_thumbs li img {
  background-color: white;
  padding: 7px; margin: 0;
  border: 1px dotted #58595b;
 // width: 129px;
 // height: 145px;
  }

 
#pugpig_thumbs li a { display: block;}
*/

  </style>

  <script>
    // http://mootools.net/docs/more/Drag/Sortables
    window.addEvent('domready', FlatPlan);

    function FlatPlan(){
      var eca_hidden = document.getElementById("edition_contents_array");
      var mySortables = new Sortables('#pugpig_included UL', {
        clone: true,
        revert: true,
        opacity: 0.5,
        onComplete: function() { 
           eca_hidden.value = mySortables.serialize(0);
        }
      });
      eca_hidden.value = mySortables.serialize(0);
    }

    function AddAll() {
      var uls = jQuery('#pugpig_included ul');
      var $left = jQuery(uls[0]);
      // $left.children('li').remove();
      $left.append(jQuery('#pugpig_same_terms_posts li'));
      FlatPlan();
      return;
    }

    function RemoveAll() {
      var uls = jQuery('#pugpig_included ul');
      var $right = jQuery(uls[1]);
      // $left.children('li').remove();
      $right.append(jQuery('#edition_included li'));
      FlatPlan();
      return;
    }
        
  </script>

  <input type="hidden" id="edition_contents_array" name="edition_contents_array" />

  <?php
  
  // Show all posts
  
  $ecr = pugpig_get_edition_array(get_post_custom($post->ID)); 
    
  echo "<div id='pugpig_included'>";

  echo "<p>Drag posts between the boxes below to add, remove, or rearrange them:</p>";

  echo "<div style='float:left;'>";

  echo "<p style='text-align:center;' id = 'pugpig_same_terms_posts'>Posts in this edition:<br /></p>";

  echo "<ul id='edition_included'>";  
  foreach ($ecr as $post_id) {
    $p = get_post($post_id);
    //$thumb = "/wordpress/kaldor/raster.php?url=" . get_permalink($edition_id);
    //echo "<li id='x_$edition->ID'><img class='thumb' title='$edition->post_title' src='$thumb'  /></li>";
    echo "<li id='$p->ID'>" . pugpig_get_flatplan_description($p);
    if($p->post_status == 'trash' || $p->post_status == 'draft') {
      echo "\n<span style='color:orange'>(". $p->post_status .")</span>";
    }
    echo "</li>";
  }

  echo "</ul>";

  echo '<div name="Remove_all" href="" class="button-primary" value="Add All" onclick="RemoveAll();">Remove all posts from edition</div>';

  echo "</div>";

  echo "<div style='float:left;'>";

  echo "<p style='text-align:center;' id = 'pugpig_same_terms_posts'>Posts sharing your terms:<br /></p>";

  echo '<ul id="pugpig_same_terms_posts">';  

/*
// Get posts
$args = array(
  'post_type' => 'any',
  'numberposts'     => 100,
  'orderby'         => 'post_date',
  'order'           => 'DESC',
  'post_status'     => 'any',
 );


// Get all posts for the right hand side
 $myposts = get_posts( $args );
 foreach( $myposts as $p ) {
    $in_editions = pugpig_get_post_editions($p);
    if ( ($p->post_type != 'attachment' && $p->post_type != PUGPIG_EDITION_POST_TYPE)) {
      echo "<li id='$p->ID'>" . pugpig_get_flatplan_description($p) . "</li>";
    } 
 }
*/

  // Get posts in the categories for the right hand side
  $terms = pugpig_get_post_terms($post);

  if (count($terms) > 0) {

    // Search everything except $p->post_type = PUGPIG_EDITION_POST_TYPE and attachment
    $post_types = array_diff(pugpig_get_allowed_types(), array(PUGPIG_EDITION_POST_TYPE)); // Remove edition type from search
    $taxonomy_name = pugpig_get_taxonomy_name();
    $args = array(
        'post_type' => $post_types,
        'post_status' => 'any',
        'tax_query' => array(
          array(
              'taxonomy'  => $taxonomy_name,
              'field'     => 'slug',
              'terms'     => $terms ,
              ),
         ),
        'nopaging'  => TRUE, 
        'orderby'   => 'date',
        'order'     => 'DESC',
    );


    $wp_query = new WP_Query($args);

    $category_order_arr = pugpig_get_category_order();

    if (!empty($category_order_arr)) {
      usort( $wp_query->posts, "pugpig_category_orderby" );
    }
    while ( $wp_query->have_posts() ) : $wp_query->the_post(); global $post;
      if (!in_array($post->ID, $ecr)){
        echo "<li id='$post->ID'>" . pugpig_get_flatplan_description($post) . "</li>";}
    endwhile;

  }
  echo "</ul>";

  if (count($terms) > 0) {
    echo '<div name="Add_all" href="" class="button-primary" onclick="AddAll();" >Add these posts to edition</div>';
  } else {
    echo "This edition has no terms yet.";
  }

  echo "</div>";

  echo "</div>";
  
  /*
  // Get posts in the categories for the right hand side
  $terms = pugpig_get_post_terms($post);

  // Search everything except $p->post_type = PUGPIG_EDITION_POST_TYPE and attachment
  $post_types = array_diff(pugpig_get_allowed_types(), array(PUGPIG_EDITION_POST_TYPE)); // Remove edition type from search
  $taxonomy_name = get_option("pugpig_opt_taxonomy_name");
  $args = array(
      'post_type' => $post_types,
      'post_status' => 'any',
      'tax_query' => array(
        array(
            'taxonomy'  => $taxonomy_name,
            'field'     => 'slug',
            'terms'     => $terms ,
            ),
       ),
      'nopaging'  => TRUE, 
      'orderby'   => 'date',
      'order'     => 'DESC',
  ); 

  $wp_query = new WP_Query($args); */

  
  // TODO: Count returns 0 often
  //echo "$taxonomy_name:<b>" . implode(",", $terms) ."</b> - Found " . $wp_query->found_posts. " item(s)";

  

  /*echo '<ul id="pugpig_same_terms_posts">';

  // Sort the items by category if required
  $category_order_arr = pugpig_get_category_order();

  if (!empty($category_order_arr)) {
    usort( $wp_query->posts, "pugpig_category_orderby" );
  }

  // The Loop
  /*while ( $wp_query->have_posts() ) : $wp_query->the_post(); global $post;
    if (!in_array($post->ID, $ecr)){
      echo "<li id='$post->ID'>" . pugpig_get_flatplan_description($post) . "</li>";}
  endwhile;
  print_r($ecr); 

  echo '</ul>';*/

  echo "<p class='reorder'><br />Posts will automatically be ordered by taxonomy.  If you have rearranged them and want to recover the original order, remove all posts from the edition, update, then add all posts in again.</p>";

  echo "<p class='save_warning'><strong>You must hit Update to save your changes!</strong></p>";

  echo "<div style='clear: both;'></div>";
}


/************************************************************************
Show admin notices from the session
************************************************************************/
function pugpig_add_admin_notice($message, $severity = "updated"){
  if (empty($_SESSION['pugpig_admin_notices'])) $_SESSION['pugpig_admin_notices'] = ""; 
  $_SESSION['pugpig_admin_notices'] .= '<div class="' . $severity . '"><p>' .  $message . '</p></div>'; 
}

function pugpig_add_debug_notice($message) {
  if (!pugpig_should_show_debug()) return;
  pugpig_add_admin_notice("<b>PUGPIG DEBUG</b> " . $message, "error");
}


function pugpig_admin_notices(){
  if(!empty($_SESSION['pugpig_admin_notices'])) print  $_SESSION['pugpig_admin_notices'];
  unset ($_SESSION['pugpig_admin_notices']);
}
add_action( 'admin_notices', 'pugpig_admin_notices' );


/************************************************************************
Very last thing - regenerate ODPS 
************************************************************************/
// Global for tracking the changes that happen in the lifecycle that need a push notification
$pugpig_edition_changed = 0;

function pugpig_shutdown(){
 global $pugpig_edition_changed;
 
 if ($pugpig_edition_changed > 0) {
    pugpig_add_admin_notice($pugpig_edition_changed . " published edition(s) have been updated", "updated"); 
 }

}
add_action( 'shutdown', 'pugpig_shutdown' );




/************************************************************************
Custom Columns for Blog Posts and pages
************************************************************************/

add_filter('manage_pages_columns', 'pugpig_edition_columns');
add_action('manage_pages_custom_column',  'pugpig_edition_show_columns');
add_filter('manage_posts_columns', 'pugpig_edition_columns');
function pugpig_edition_columns($columns) {
    $columns['edition'] = 'Pugpig';
    return $columns;
}

add_action('manage_posts_custom_column',  'pugpig_edition_show_columns');

function pugpig_edition_name_map($n) {
  return $n->post_title; 
}

function pugpig_term_link_map($c) {

$taxonomy_name = pugpig_get_taxonomy_name();

 $ret = sprintf( '<a href="%s">%s</a>',
            esc_url( add_query_arg( array( /* 'post_type' => $post->post_type, */ $taxonomy_name => $c->slug ), 'edit.php' ) ),
            esc_html( sanitize_term_field( 'name', $c->name, $c->term_id, $taxonomy_name, 'display' ) ));
 return $ret;
}

function pugpig_edition_show_columns($name) {
    global $post;
    switch ($name) {
        case 'edition':

          //echo "<a href='" . pugpig_path_to_abs_url(PUGPIG_MANIFESTPATH . "post-" . $post->ID) .".manifest'>View manifest</a><br />";  

          // If the permalink structure ends in slash we add "pugpig.manifest"
          // If the permalink structure ends without a slash we add "/pugpig.manifest"
          // If we have a query string, add it

          $manifest_url = pugpig_get_manifest_url($post);
          $html_url = pugpig_get_html_url($post);
          
          echo "<a href='$manifest_url'>View manifest</a><br />";      
          echo "<a href='$html_url'>View HTML</a><br />";      

          // Get the currect editions 
          $post_editions =  pugpig_get_post_editions($post);
          //print_r($post_editions);
          echo implode(", ", array_map('pugpig_edition_name_map', $post_editions));

         
           /*
            $edition_id = get_post_meta($post->ID, PUGPIG_POST_EDITION_LINK, true);
            XXXXX
            if (!empty($edition_id)) {    
              $edition = get_post($edition_id);
              echo pugpig_edition_filter_link($edition_id,  $edition->post_title) . "<br />";
              echo pugpig_edition_edit_link($edition_id, "Edit edition") . "<br />"; 
            }
            */
            
            
            if (pugpig_should_use_thumbs()) {
              $thumb = "/wordpress/kaldor/raster.php?url=" . get_permalink($post->ID);
              echo "<img width='60' height='80' src='$thumb'  />";  
            }
            break;
            
    }
}

/************************************************************************
Custom Columns for Editions
************************************************************************/
add_action("manage_posts_custom_column",  "pugpig_edition_custom_columns");
add_filter("manage_edit-pugpig_edition_columns", "pugpig_edition_edit_columns");

function pugpig_edition_edit_columns($columns){
  $columns = array(
    "cb" => "<input type=\"checkbox\" />",
    "title" => "Edition Title",
    "edition_actions" => "Actions",    
    "edition_date" => "Edition Date",
    "edition_free" => "Free? / Author",
    "description" => "Key / Summary",
    "image" => "Cover Image",
    "date" => "Last Modified"
  );

  $taxonomy_name = pugpig_get_taxonomy_name();
  if (!empty($taxonomy_name) && taxonomy_exists($taxonomy_name)) {
    $taxonomy = get_taxonomy($taxonomy_name);
    $columns[$taxonomy_name] = $taxonomy->labels->name;
  }
 
  $columns["manifests"] = "Links";

  return $columns;
}

function pugpig_edition_custom_columns($column){
  global $post;
  $wp_ud_arr = wp_upload_dir();
  $custom = get_post_custom();

  $edition_key = "";
  if (isset($custom["edition_key"])) {
    $edition_key = $custom["edition_key"][0];
  }

  $taxonomy_name = pugpig_get_taxonomy_name();
  $package_url = get_edition_package_url($post->ID);
  
  switch ($column) {
    case "description":
      echo $edition_key . "<br />";
      the_excerpt();
      break;
    case "edition_date":
      if (isset($custom["edition_date"])) {
        echo $custom["edition_date"][0] . "<br />";
      }

      if ($package_url != '') {
          echo 'Packaged:<br /><b>' . _ago(strtotime(pugpig_date3339(get_edition_package_timestamp($post->ID)))) . " ago</b>";
          echo '<br />Size:<br /><b>' . _package_edition_package_size(pugpig_get_full_edition_key($post)) . '</b>';
      }
       break;
    case "edition_free":
      if (isset($custom["edition_free"])) {
        echo "FREE";
      } else {
        echo "<b>PAID</b>";
      }
      if (isset($custom["edition_author"])) {
        echo '<br>' . $custom["edition_author"][0];
      }
      break;
    case "edition_actions":
      $package_vars["action"] = "generatepackagefiles";
      $package_vars["p"] = pugpig_get_package_manifest_url($post, FALSE);
      $package_vars["c"] = pugpig_get_edition_atom_url($post, FALSE);
      $package_vars["pbp"] = "/";
      $package_vars["tf"] = PUGPIG_MANIFESTPATH . 'temp/packages/';
      $package_vars["pf"] = PUGPIG_MANIFESTPATH . 'packages/';
      $package_vars["cdn"] = get_option('pugpig_opt_cdn_domain');
      $package_vars["urlbase"] = pugpig_strip_domain($wp_ud_arr['baseurl']) . '/pugpig-api/packages/';

      echo '<a target="_blank" href="' . BASE_URL . 
        'common/pugpig_packager_run.php?' . http_build_query ($package_vars) . '">Package</a><br />';

      $package_vars["image_test_mode"] = "true";
      echo '<a target="_blank" href="' . BASE_URL . 
        'common/pugpig_packager_run.php?' . http_build_query ($package_vars) . '">Images</a><br />';

        /*
        action=generatepackagefiles' 
      . '&p=' . urlencode(pugpig_get_package_manifest_url($post, FALSE)) 
      . '&c=' . urlencode(pugpig_get_edition_atom_url($post, FALSE)) 
      . '&pbp=' . urlencode("/")       
      . '&tf=' . urlencode(PUGPIG_MANIFESTPATH . 'temp/packages/') 
      . '&pf=' . urlencode(PUGPIG_MANIFESTPATH . 'packages/') 
      . '&cdn=' . urlencode(get_option('pugpig_opt_cdn_domain'))
      . '&urlbase=' . urlencode(pugpig_strip_domain($wp_ud_arr['baseurl']) . '/pugpig-api/packages/') 
      */
      echo "<a target='_blank' href='" . BASE_URL . "reader/reader.html?atom=" . urlencode(pugpig_get_edition_atom_url($post, FALSE)) ."'>Web Preview</a><br />";

      $ecr = pugpig_get_edition_array($custom);
      $count = count($ecr);
      echo " (" . $count . " items)";


      break;
    case $taxonomy_name:
      // TODO: Get the tags
      $terms = wp_get_post_terms( $post->ID, $taxonomy_name);
      echo implode(", ", array_map('pugpig_term_link_map', $terms));
      break;   
    case "image":
      set_post_thumbnail_size( 90, 120 );
      echo get_the_post_thumbnail($post->ID);
      break;
    case "manifests":
      echo "<a href='" . pugpig_get_edition_atom_url($post)."'>ATOM feed</a><br />";

      if ($package_url != '') {
        echo "<a href='" . $package_url."'>Latest Package</a><br />";
      }

      break;

  }
}

/************************************************************************
Menus
************************************************************************/
add_action('admin_menu', 'pugpig_plugin_menu');
function pugpig_plugin_menu() {

 $capability = 'manage_options';

 add_submenu_page( 'tools.php', 'Pugpig Push Notification', 'Pugpig Push Notification', 'manage_options', 'pugpig-push-notification', 'pugpig_push_notification_form' ); 

 add_options_page( 'Pugpig Settings', 'Pugpig', $capability, 'pugpig-settings',  'pugpig_plugin_options', 'bob.gif');
}


/************************************************************************
Icons
************************************************************************/
add_action( 'admin_head', 'pugpig_edition_icons' );

function pugpig_edition_icons() {

    ?>
    <style type="text/css" media="screen">
        #menu-posts-pugpig_edition .wp-menu-image {
            background: url(<?php echo(BASE_URL) ?>images/pugpig_edition-icon.png) no-repeat 6px -16px !important;
        }
        #menu-posts-pugpig_edition:hover .wp-menu-image, #menu-posts-pugpig_edition.wp-has-current-submenu .wp-menu-image {
            background-position:6px 8px !important;
        }
        #icon-edit.icon32-posts-pugpig_edition {background: url(<?php echo(BASE_URL) ?>common/images/pugpig-32x32.png) no-repeat;}

        div.button-primary {
          margin:0 auto;
          width:170px;
          border-color: #298cba;
          font-weight: bold;
          color: #fff;
          background: #21759B url(<?php echo(site_url()) ?>/wp-admin/images/button-grad.png) repeat-x scroll left top;
          text-shadow: rgba(0,0,0,0.3) 0 -1px 0;
          text-align:center;
          clear:both;
        }
        
        div.button-primary:active {
          background: #21759b url(<?php echo(site_url()) ?>/wp-admin/images/button-grad-active.png) repeat-x scroll left top;
          color: #eaf2fa;
        }

        div.button-primary:hover {
          border-color: #13455b;
          color: #eaf2fa;
        }

        p.save_warning {
          clear: both;
          color: #B40404;
        }

        p.reorder {
          clear: both;
        }
    </style>
<?php }

/************************************************************************
Dashboard Widget
************************************************************************/
function pugpig_dashboard_widget_function() {

    ?>
    
    <img style="float:right; padding: 5px;" src="<?php echo(BASE_URL) ?>common/images/pugpig-32x32.png" />
    
    <h4>Summary</h4>
    <?php
     $ecr = pugpig_get_editions();
     
     echo '<p>Total editions: ' . count($ecr) . '<br />';
     echo "Published: [<a target='_blank' href='" . BASE_URL . "reader/reader.html?opds=" . urlencode(pugpig_feed_opds_link(false, true)) ."'>Preview</a>]";
     echo " [<a target='_blank' href='".pugpig_feed_opds_link(false)."'>OPDS Package Feed</a>]";
     echo " [<a target='_blank' href='".pugpig_feed_opds_link(false, true)."'>OPDS Atom Feed</a>]<br />";
     
     echo "Draft: [<a target='_blank' href='" . BASE_URL . "reader/reader.html?opds=" . urlencode(pugpig_feed_opds_link(true, true)) ."'>Preview</a>]";
     echo " [<a target='_blank' href='".pugpig_feed_opds_link(true)."'>OPDS Package Feed</a>]";
     echo " [<a target='_blank' href='".pugpig_feed_opds_link(true, true)."'>OPDS Atom Feed</a>]<br />";

     '</p>';
     if (count($ecr) > 0) {
       echo '<h4>Recent editions: ' . '</h4><p><ul>';
       foreach ($ecr as $post_id) {
          $post = get_post($post_id);      
          echo '<li>' .  $post->post_title . ' (' . $post->post_status . ")</li>";
       } 
       echo '</ul></p>';
     }
          
     echo '[<a href="options-general.php?page=pugpig-settings">Settings</a>]<br />';

     $entrypoints = array(
      urlencode(pugpig_feed_opds_link(false, false) . "\r\n"),
      urlencode(pugpig_feed_opds_link(false, true) . "\r\n"),
      urlencode(pugpig_feed_opds_link(true, false) . "\r\n"),
      urlencode(pugpig_feed_opds_link(true, true) . "\r\n")
     );
     
     echo "[<a target='_blank' href='" . BASE_URL . "common/pugpig_packager_run.php?entrypoints=" . join($entrypoints) ."'>Test Entry Points</a>]<br />";

     
     
}

function pugpig_add_dashboard_widgets() {
  wp_add_dashboard_widget('pugpig_dashboard_widget', 'Pugpig for WordPress Version ' .PUGPIG_CURRENT_VERSION, 'pugpig_dashboard_widget_function');
}
add_action('wp_dashboard_setup', 'pugpig_add_dashboard_widgets' );

