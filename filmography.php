<?php
/**
 * Plugin Name: Filmography
 * Plugin URI: http://hughguiney.com/2014/blah
 * Description: List your films.
 * Version: 0.1
 * Author: Hugh Guiney
 * Author URI: http://hughguiney.com/
 * License: Public Domain
 */

function film_post_type() {  
  register_post_type(
    'film',
    array(
      'labels' => array(
        'name' => _x('Films', 'post type general name'),
        'singular_name' => _x('Film', 'post type singular name'),
        'add_new' => _x('Add New', 'Film'),
        'add_new_item' => __('Add New Film'),
        'edit_item' => __('Edit Film'),
        'new_item' => __('New Film'),
        'all_items' => __('All Films'),
        'view_item' => __('View Film'),
        'search_items' => __('Search Films'),
        'not_found' =>  __('No Films found'),
        'not_found_in_trash' => __('No Films found in Trash'), 
        'parent_item_colon' => '',
        'menu_name' => __('Films')
      ),
      'public' => true,
      'menu_position' => 5,
      'rewrite' => array('slug' => 'films', 'with_front' => false),
      'supports' => array('title', 'thumbnail'),
      'has_archive' => true //'films'
    )
  );
}

function set_film_icon() {
  if ( is_plugin_active( 'post-type-icons/post-type-icons.php' ) ) {
    pti_set_post_type_icon( 'film', 'film' );
    //add_filter( 'pti_plugin_show_admin_menu', '__return_false' );
  }
}

function release_status_taxonomy() {
  register_taxonomy(
    'release_status',
    'film',
    array(
      'hierarchical' => true,
      'label' => 'Release Status',
      'query_var' => true,
      'rewrite' => array('slug' => 'release-status')
    )
  );
}

// function films_activate() {
//   // register taxonomies/post types here
//   flush_rewrite_rules();
// }

// function films_deactivate() {
//   flush_rewrite_rules();
// }

function films_archive_only_released( $query ) {
  global $film_metadata;
  $film_metadata->the_meta();

  if ( is_admin() || !$query->is_main_query() ) {
    return;
  }

  if ( is_post_type_archive( 'film' ) ) {
  //if ( $query->is_tax( 'release_status' ) ) {
    $query->set(
      'tax_query',
      array(
        'relation' => 'OR',
        array(
          'taxonomy' => 'release_status',
          'field' => 'slug',
          'terms' => array( 'upcoming', 'unreleased' ),
          'operator' => 'NOT IN'
        )
      )
    ); // set
    
    // http://www.farinspace.com/forums/topic/sorting-query-based-on-meta-box-value/#post-1918
    $query->set(  
      'meta_key', $film_metadata->get_the_name('release_date')
    );

    $query->set( 'order', 'DESC' );
  
    $query->set( 'orderby', 'meta_value' );
    
    $query->set( 'posts_per_page', -1 );
  }

  //var_dump( $query );

  return $query;
}

function films_post_class( $classes ) {
  if ( get_post_type() === 'film' ) {
    $classes = array_diff( $classes, array( 'hentry' ) );

    if ( !in_array( 'entry', $classes ) ) {
      $classes[] = 'entry';
    }
  }

  return $classes;
}

/**
* Modify <title> if on an archive page.
*
* @author Philip Downer <philip@manifestbozeman.com>
* @link http://manifestbozeman.com
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
* @version v1.0
*
* @param string $orig_title Original page title
* @return string New page title
*/
function archive_titles($orig_title, $sep) {
  global $post;
  $post_type = $post->post_type;
  
  $types = array(
    array( //Create an array for each post type you wish to control.
      'post_type' => 'film', //Your custom post type name
      //'title' => 'Films ' . $sep . ' ' . get_bloginfo( 'name' ) //The title tag you'd like displayed
      'remove' => ' Archive'
    ),
  );
 
  if ( is_archive() ) { //FIRST CHECK IF IT'S AN ARCHIVE
    
    //CHECK IF THE POST TYPE IS IN THE ARRAY
    foreach ( $types as $k => $v) {
      if ( in_array($post_type, $types[$k])) {
      return str_replace($types[$k]['remove'], '', $orig_title); //$types[$k]['title'];
      }
    }
    
  } else { //NOT AN ARCHIVE, RETURN THE ORIGINAL TITLE
    return $orig_title;
  }
}


// include the class in your theme or plugin
include_once WP_CONTENT_DIR . '/wpalchemy/MetaBox.php';
 
function film_metabox_styles()
{
  wp_enqueue_style( 'wpalchemy-metabox', plugin_dir_url( __FILE__ ) . '/style/film_meta.css' );
}

//echo plugin_dir_path( __FILE__ ) . 'style/film_fieldname_meta.css';

/*
Title
Release Date
Content URL
Thumbnail URL
Embed URL
Genre
Runtime
Director
Summary
Festivals
*/

$film_fests = new WPAlchemy_MetaBox(array(
  'id' => '_film_festivals',
  'title' => 'Festivals',
  'types' => array('film'),
  'context' => 'normal',
  'priority' => 'high',
  'template' => get_stylesheet_directory() . '/metaboxes/film-festivals.php'
));

$film_metadata = new WPAlchemy_MetaBox(array(
  'id' => '_film_metadata',
  'title' => 'Metadata',
  'types' => array('film'), // added only for pages and to custom post type "events"
  'context' => 'normal', // same as above, defaults to "normal"
  'priority' => 'high', // same as above, defaults to "high"
  'template' => get_stylesheet_directory() . '/metaboxes/film-metadata.php',
  'mode' => WPALCHEMY_MODE_EXTRACT,
  'prefix' => '_filmography_'
));

add_action( 'init', 'film_post_type' );
add_action( 'init', 'release_status_taxonomy' );
add_action( 'admin_enqueue_scripts', 'film_metabox_styles' );
add_action( 'admin_init', 'set_film_icon' );
add_action( 'pre_get_posts', 'films_archive_only_released' );
//add_filter( 'wp_title', 'archive_titles', 100, 2 );
//add_filter( 'post_class', 'films_post_class' );

// register_activation_hook( __FILE__, 'films_activate' );
// register_deactivation_hook( __FILE__, 'films_deactivate' );
