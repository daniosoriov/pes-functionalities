<?php
/**
 * Plugin Name: PES functionalities
 * Plugin URI: http://danioshiweb.com
 * Description: This plugin has useful functionalities for the PES personalized website.
 * Version: 1.0.0
 * Author: Daniel Osorio
 * Author URI: http://danioshiweb.com
 * License: GPL2
 */

// exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

define("VIMEO_CLIENT_ID", 'ad4c3e454c7bbb115fd01ec5e4b55cf409f2cc31');
define("VIMEO_CLIENT_SECRET", 'GScPSrBofMFMbQQaBaKRk3lHH1D2rEZIiCKIMGxFbpW62ah7TnS7YHNAi7XnAQ8AiEoxCaRzCH9+qdUQF2rON8kWaY5pL37cYtINiwKIjubEdSv3AdW2GTsBaUGbortt');
define("VIMEO_ACCESS_TOKEN", 'b405b9b453f844bdbf311a6914f7e4ae');

define("PES_HASH", 'ad4c3e454c7bbb115fdtFdau2801ec5e4b55cf409f2cc31');


/** 
 * Add query filters to the gallery page.
 */
function pes_add_query_vars_filter( $vars ) {
  $vars[] = "gallery_action";
  $vars[] = "gallery_page";
  $vars[] = "gallery_show";
  return $vars;
}
add_filter( 'query_vars', 'pes_add_query_vars_filter' );


/**
 * Returns the layout for the tags of a specific post.
 * @param $post_id the post ID.
 * @return string the layout in html.
 */
function pes_tags_layout($post_id) {
  $terms = get_the_terms($post_id, 'leader');
  $leaders_string = $topic_string = $country_string = $party_string = '';
  if ($terms && !is_wp_error($terms)) {
    //$leaders_string = '<p class="pes-tags-section">'. the_terms( $post_id, 'leader', 'Leaders: ', ', ') .'</p>';
  }
  $terms = get_the_terms($post_id, 'topic');
  if ($terms && !is_wp_error($terms)) {
//    $topic_string = '<p class="pes-tags-section">'. the_terms( $post_id, 'topic', 'Topics: ', ', ') .'</p>';
  }
  $terms = get_the_terms($post_id, 'country');
  if ($terms && !is_wp_error($terms)) {
//    $country_string = '<p class="pes-tags-section">'. the_terms( $post_id, 'country', 'Countries: ', ', ') .'</p>';
  }
  $terms = get_the_terms($post_id, 'party');
  if ($terms && !is_wp_error($terms)) {
//    $party_string = '<p class="pes-tags-section">'. the_terms( $post_id, 'party', 'Political parties: ', ', ') .'</p>';
  }
  
  return '
    <!-- Header tag fields for PES -->
      <section class="pes-section">
        <div class="pes-tags">
          <p class="pes-tags-section">'. the_terms( $post_id, 'leader', 'Leaders: ', ', ') .'</p>
          <p class="pes-tags-section">'. the_terms( $post_id, 'topic', 'Topics: ', ', ') .'</p>
          <p class="pes-tags-section">'. the_terms( $post_id, 'country', 'Countries: ', ', ') .'</p>
          <p class="pes-tags-section">'. the_terms( $post_id, 'party', 'Political parties: ', ', ') .'</p>
        </div>
      </section>
      <!-- End of header tag fields for PES -->
  ';
}

/**
 * Set up the vimeo library with our oAuth2.
 * playground: https://developer.vimeo.com/api/playground
 */
function pes_vimeo_setup() {
  require("library/vimeo.php/autoload.php");
  $lib = new \Vimeo\Vimeo(VIMEO_CLIENT_ID, VIMEO_CLIENT_SECRET);
  $lib->setToken(VIMEO_ACCESS_TOKEN);
  return $lib;
}

/**
 * Get all the information about a video from vimeo.
 * @param $video_id the id of the video on vimeo.
 */
function pes_vimeo_get_info($video_id) {
  $lib = pes_vimeo_setup();
  $response = $lib->request("/videos/{$video_id}", array(), 'GET');
  //echo '<pre>Response '.print_r($response['body']['download'],1).'</pre>';
  return $response;
}

/**
 * Get all the information relative to the download videos
 * for a specific video.
 * @param $video_id the id of the video on vimeo.
 */
function pes_vimeo_get_download_videos($video_id) {
  $lib = pes_vimeo_setup();
  $response = $lib->request("/videos/{$video_id}", array(), 'GET');
  //echo '<pre>Response '.print_r($response['body']['download'],1).'</pre>';
  return $response['body']['download'];
}

function pes_vimeo_reorder_download_videos($videos) {
  $tmp = $new_videos = array();
  foreach ($videos as $key => $video) {
    $tmp[$video['size']] = $key;
  }
  ksort($tmp);
  foreach ($tmp as $key) {
    $new_videos[] = $videos[$key];
  }
  return $new_videos;
}

function pes_vimeo_format_display($video) {
  switch ($video['quality']) {
    case 'hd':
      if ($video['height'] == 720) {
        return 'HD 720p';
      }
      if ($video['height'] == 1080) {
        return 'HD 1080p';
      }
      break;
      
    case 'sd':
      if ($video['height'] == 360) {
        return 'Mobile SD';
      }
      elseif ($video['height'] == 540) {
        return 'SD 540p';
      }
      break;
      
    case 'source':
      return 'Original';
      break;
  }
}

function pes_get_gallery_pagination($total_images, $paged = 1, $number_by_page = 25) {
  $total_pages = floor($total_images / $number_by_page) + (($total_images % $number_by_page) ? 1 : 0);
  if (1 == $total_pages) return;
  
  $pages_to_show = 5;
  $larger_page_to_show = 3;
  $larger_page_multiple = 10;
  $options = array(
    'first_text' => '&laquo; First',
    'last_text' => '&raquo; Last',
    'prev_text' => '&laquo;',
    'next_text' => '&raquo;',
  );
  if (is_plugin_active('wp-pagenavi/wp-pagenavi.php')) {
    $options = wp_parse_args( $options, PageNavi_Core::$options->get() );
    $pages_to_show = absint( $options['num_pages'] );
    $larger_page_to_show = absint( $options['num_larger_page_numbers'] );
  }
  $pages_to_show_minus_1 = $pages_to_show - 1;
  $half_page_start = floor( $pages_to_show_minus_1/2 );
  $half_page_end = ceil( $pages_to_show_minus_1/2 );
  $start_page = $paged - $half_page_start;

  if ( $start_page <= 0 )
      $start_page = 1;

  $end_page = $paged + $half_page_end;

  if ( ( $end_page - $start_page ) != $pages_to_show_minus_1 )
      $end_page = $start_page + $pages_to_show_minus_1;

  if ( $end_page > $total_pages ) {
      $start_page = $total_pages - $pages_to_show_minus_1;
      $end_page = $total_pages;
  }

  if ( $start_page < 1 )
      $start_page = 1;
  
  $string = '';
  $post_url = get_post_permalink();
  if ($total_pages > 0) {
    $string .= '<div class="wp-pagenavi">';
    $string .= "<span class='pages'>Page {$paged} of {$total_pages}</span>";
    // First page
    if ( $start_page >= 2 && $pages_to_show < $total_pages ) {
      $string .= '<a class="first" href="'. $post_url .'">'. $options['first_text'] .'</a>';
    }
    // Previous page
    if ( $paged > 1 && !empty( $options['prev_text'] ) ) {
      $previous = $paged - 1;
      $string .= '<a class="previouspostslink" href="'. $post_url .'?gallery_page='. $previous .'" rel="prev">'. $options['prev_text'] .'</a>';
    }
    if ( $start_page >= 2 && $pages_to_show < $total_pages ) {
      $string .= "<span class='extend'>...</span>";
    }
    
    // The pages.
    
    // Smaller pages
    $larger_pages_array = array();
    if ( $larger_page_multiple )
      for ( $i = $larger_page_multiple; $i <= $total_pages; $i+= $larger_page_multiple )
        $larger_pages_array[] = $i;
    
    $larger_page_start = 0;
    foreach ( $larger_pages_array as $larger_page ) {
      if ( $larger_page < ($start_page - $half_page_start) && $larger_page_start < $larger_page_to_show ) {
        $string .= "<a href='{$post_url}?gallery_page={$larger_page}' class='current larger'>{$larger_page}</a>";
        $larger_page_start++;
      }
    }
    if ( $larger_page_start )
      $string .= "<span class='extend'>...</span>";
    
    // Pages
    foreach ( range( $start_page, $end_page ) as $i ) {
      if ( $i == $paged ) {
        $string .= "<span class='current smaller'>{$i}</span>";
      } else {
        $string .= "<a href='{$post_url}?gallery_page={$i}' class='current larger'>{$i}</a>";
      }
    }
    
    // Large pages
    $larger_page_end = 0;
    $larger_page_out = '';
    foreach ( $larger_pages_array as $larger_page ) {
      if ( $larger_page > ($end_page + $half_page_end) && $larger_page_end < $larger_page_to_show ) {
        $larger_page_out .= "<a href='{$post_url}?gallery_page={$larger_page}' class='current larger'>{$larger_page}</a>";
        $larger_page_end++;
      }
    }

    if ( $larger_page_out ) {
      $string .= "<span class='extend'>...</span>";
    }
    $string .= $larger_page_out;
    
    if ( $end_page < $total_pages ) {
      $string .= "<span class='extend'>...</span>";
    }

    // Next
    if ( $paged < $total_pages && !empty( $options['next_text'] ) ) {
      $next = $paged + 1;
      $string .= '<a class="nextpostslink" href="'. $post_url .'?gallery_page='. $next .'" rel="next">'. $options['next_text'] .'</a>';
    }
    // Last page
    if ( $end_page < $total_pages ) {
      $string .= '<a class="last" href="'. $post_url .'?gallery_page='. $total_pages .'">'. $options['last_text'] .'</a>';
    }
    $string .= '</div>';
  }
  return $string;
}

/**
 * Format bytes.
 */
function pes_formatBytes($bytes, $precision = 3) { 
  $units = array('B', 'KB', 'MB', 'GB', 'TB'); 

  $bytes = max($bytes, 0); 
  $pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
  $pow = min($pow, count($units) - 1); 

  // Uncomment one of the following alternatives
  // $bytes /= pow(1024, $pow);
  $bytes /= (1 << (10 * $pow)); 

  return round($bytes, $precision) .' '. $units[$pow]; 
} 


/**
 * Shortens a string $oldstring to a new string cut in
 * $wordsreturned words.
 * @param $oldstring the string to cut
 * @param $wordsreturned the amount of words
 * @return the new string cut
 **/
function shorten_string($oldstring, $wordsreturned) {
  $retval = $oldstring;
  $string = preg_replace('/(?<=\S,)(?=\S)/', ' ', $oldstring);
  $string = str_replace("\n", " ", $string);
  $array = explode(" ", $string);
  if (count($array) <= $wordsreturned) {
    $retval = $string;
  }
  else {
    array_splice($array, $wordsreturned);
    $retval = implode(" ", $array) ." [...]";
  }
  return $retval;
}


/**
 * Limit post excerpts length.
 */
function custom_excerpt_length( $length ) {
    return 20;
}
add_filter( 'excerpt_length', 'custom_excerpt_length', 999 );

// https://wpbakery.atlassian.net/wiki/display/VC/Adding+Custom+Shortcode+to+Grid+Builder
add_filter( 'vc_grid_item_shortcodes', 'pes_add_shortcode_icon_to_grid' );
function pes_add_shortcode_icon_to_grid( $shortcodes ) {
  $shortcodes['vc_icon_download'] = array(
    'post_id' => 'post_id',
    'name' => __( 'Icon download', 'pes-icon-download' ),
    'base' => 'vc_icon_download',
    'category' => __( 'Content', 'pes-icon-download' ),
    'description' => __( 'Outputs download icon', 'pes-icon-download' ),
    'post_type' => Vc_Grid_Item_Editor::postType(),
  );
  return $shortcodes;
}

add_shortcode( 'vc_icon_download', 'vc_icon_download_render' );
function vc_icon_download_render($atts) {
  //$post_id = '{{ post_data:ID }}';
  //return '<>{{ post_data:ID }}';
  // <span class=\"fa fa-cloud-download\" title=\"Download available\" style=\"color: {$a['color']}\"></span>
  
  $a = shortcode_atts(array(
    'class' => 'photo',
  ), $atts);
  $class = '';
  $label = '';
  if ($a['class'] == 'photo') {
    $class = ' photo';
    $label = 'Hi-res';
  }
  elseif ($a['class'] == 'video') {
    $class = ' video';
    $label = 'HD';
  }
  return "
    <div class=\"pes-available\">
      <span title=\"Download available in Hi-res. Go to the gallery.\">Available in <span class=\"pes-hi-res{$class}\">{$label}</span></span>
    </div>";
}


function pes_search_filter_exclude_fields($query_args, $sfid) {
  // If it's the main search, exclude images that are internal.
  if ($sfid == 16100) {
    $query_args = array(
      'tax_query' => array(
        'relation' => 'AND',
        array(
          'taxonomy' => 'attachment_category',
          'field'    => 'id',
          'terms'    => array( 128 ),
          'operator' => 'NOT IN',
        ),
      ),
    );
    $query_args = array();
  }
  return $query_args;
}
add_filter( 'sf_edit_query_args', 'pes_search_filter_exclude_fields', 10, 2);

/**
 * Removes the meta box for admin posts for different
 * custom taxonomy terms.
 */
function pes_remove_custom_taxonomy() {
  $tax_h = array('meeting', 'topic', 'party', 'pes-category', 'video-categories');
  $tax = array('leader', 'country');
  foreach ($tax as $slug) {
    remove_meta_box( 'tagsdiv-'. $slug, 'gallery', 'side' );
    remove_meta_box( 'tagsdiv-'. $slug, 'gallery', 'side' );
  }
  foreach ($tax_h as $slug) {
    remove_meta_box( $slug .'div', 'gallery', 'side' );
    remove_meta_box( $slug .'div', 'gallery', 'side' );
  }
}
add_action( 'admin_menu', 'pes_remove_custom_taxonomy' );

/**
 * Save gallery metadata when saved.
 * Taken from: https://codex.wordpress.org/Plugin_API/Action_Reference/save_post
 *
 * @param int $post_id The post ID.
 * @param post $post The post object.
 * @param bool $update Whether this is an existing post being updated or not.
 */
function save_gallery_meta( $post_id, $post, $update ) {
  
  // TODO: Check this page: https://www.advancedcustomfields.com/resources/acfsave_post/
  // TODO: Check also this page: https://www.advancedcustomfields.com/resources/update_field/
  // TODO: Check also this page: https://www.advancedcustomfields.com/resources/get_field/
  
  // TODO: Probably what needs to be done is:
  // Use acfsave_post to get the data saved to the post after saving it in the database,
  // then use the get_field to get all the attachments realted to the post,
  // then do a foreach and modify one by one the fields related, which are:
  // topic, meeting type and countries.
  // After this is done, do the same with the pictures but only update the parent post
  // with the value of the leader.
  
  
  
  
  if ( isset( $_REQUEST['book_author'] ) ) {
    update_post_meta( $post_id, 'book_author', sanitize_text_field( $_REQUEST['book_author'] ) );
  }

  if ( isset( $_REQUEST['publisher'] ) ) {
    update_post_meta( $post_id, 'publisher', sanitize_text_field( $_REQUEST['publisher'] ) );
  }

  // Checkboxes are present if checked, absent if not.
  if ( isset( $_REQUEST['inprint'] ) ) {
    update_post_meta( $post_id, 'inprint', TRUE );
  } else {
    update_post_meta( $post_id, 'inprint', FALSE );
  }
}
add_action( 'save_post_gallery', 'save_gallery_meta', 10, 3 );

/**
 * Creates the welcome link on the navigation bar for logged in users.
 */
function pes_navigation_name($items) {
  if (is_user_logged_in()) {
    $user = wp_get_current_user();
    $name = $user->data->display_name;
    $items .= '<li id="menu-item-16025" class="menu-item menu-item-type-post_type menu-item-object-page menu-item-16025" data-depth="0"><a href="/user/"><span class="menu-title">Hi, '. $name .'!</span></a></li>';
    $items .= '<li id="menu-item-16023" class="menu-item menu-item-type-post_type menu-item-object-page menu-item-16023" data-depth="0"><a href="/logout/"><span class="menu-title">Logout</span></a></li>';
  }
  return $items;
}
add_filter( 'wp_nav_menu_items', 'pes_navigation_name');


/**
 * Changes the output of the search and filter input values
 * before rendering it.
 * Based on: https://www.designsandcode.com/documentation/search-filter-pro/action-filter-reference/#Filter_Input_Object
 * Example: https://gist.github.com/rmorse/7b59b45a14b1ca179868
 */
function pes_search_filter_change_label($input_object, $sfid) {
  if ($sfid == 16100) {
    $assoc = array(
      '_sfm_pes_topic' => 'topic',
      '_sfm_pes_meeting_type' => 'meeting',
      '_sfm_pes_leader' => 'leader',
      '_sfm_pes_country' => 'country',
      '_sfm_pes_video_category' => 'video-categories',
    );
    //echo '<pre>Input object'.print_r($input_object,1).'</pre>';
    switch ($input_object['name']) {
      case '_sf_post_type':
        foreach ($input_object['options'] as $key => $option) {
          if ($option->label == 'Media') {
            $input_object['options'][$key]->label = 'Photos';
          }
        }
        break;
        
      case '_sfm_pes_topic':
      case '_sfm_pes_meeting_type':
      case '_sfm_pes_leader':
      case '_sfm_pes_country':
      case '_sfm_pes_video_category':
        pes_term_order_array($input_object['options'], $assoc[$input_object['name']]);
        break;
    }
  }
  return $input_object;
}
add_filter('sf_input_object_pre', 'pes_search_filter_change_label', 10, 2);


function pes_term_order_array(&$input, $taxonomy) {
  $terms = pes_term_transform_array($taxonomy);
  $vals = array();
  $first_value = new stdClass();
  foreach ($input as $key => $option) {
    if (!$option->value) {
      $first_value = $option;
      $vals[] = $option;
      continue;
    }
    $option->label = $terms[$option->value];
    if (!$option->label) {
      $tax_term = get_term_by('id', $option->value, $taxonomy);
      $option->label = $tax_term->name;
    }
    $vals[$option->label] = $option;
  }
  if ($taxonomy == 'topic' || $taxonomy == 'meeting') {
    array_shift($vals);
  }
  ksort($vals);
  if ($taxonomy == 'topic' || $taxonomy == 'meeting') {
    array_unshift($vals, $first_value);
  }
  //if ($taxonomy == 'topic') echo '<pre>vals '.print_r($vals,1).'</pre>';
  $input = $vals;
}

function pes_term_transform_array($category) {
  $new_terms = array();
  $terms = get_terms($category);
  foreach ($terms as $term) {
    $new_terms[$term->term_id] = $term->name;
  }
  return $new_terms;
}


/***
***	@form content
***/
/*
add_action('um_reset_password_form', 'pes_um_reset_password_form');
function pes_um_reset_password_form() {

    global $ultimatemember;

    $fields = $ultimatemember->builtin->get_specific_fields('password_reset_text,username_b'); ?>

    <?php $output = null;
    foreach( $fields as $key => $data ) {
        $output .= $ultimatemember->fields->edit_field( $key, $data );
    }echo $output; ?>

    <div class="um-col-alt um-col-alt-b">

        <div class="um-center"><input type="submit" value="<?php _e('Reset my password','ultimatemember'); ?>" class="um-button" /></div>

        <div class="um-clear"></div>

    </div>

    <?php

}*/


/***
***	@Predefined Fields
***/
/*
function set_predefined_fields(){

    global $ultimatemember;

    if ( !isset( $ultimatemember->query ) || ! method_exists( $ultimatemember->query, 'get_roles' ) ) {
        return;
    } else {
        //die('Method loaded!');
    }

    $um_roles = $ultimatemember->query->get_roles( false, array('admin') );

    $profile_privacy = apply_filters('um_profile_privacy_options', array( __('Everyone','ultimatemember'), __('Only me','ultimatemember') ) );

    $this->predefined_fields = array(
'password_reset_text' => array(
            'title' => __('Password Reset','ultimatemember'),
            'type' => 'block',
            'content' => '<div style="text-align:center">' . __('To reset your password, please enter your email address or username below','ultimatemember'). '</div>',
            'private_use' => true,
        ),

        'username_b' => array(
            'title' => __('Username or E-mail','ultimatemember'),
            'metakey' => 'username_b',
            'type' => 'text',
            'placeholder' => __('Enter your username or email','ultimatemember'),
            'required' => 1,
            'public' => 1,
            'editable' => 0,
            'private_use' => true,
        ),

      );

    $this->predefined_fields = apply_filters('um_predefined_fields_hook', $this->predefined_fields );

}*/


function pes_crypt($q) {
  //return base64_encode( mcrypt_encrypt( MCRYPT_RIJNDAEL_256, md5( PES_HASH ), $q, MCRYPT_MODE_CBC, md5( md5( PES_HASH ) ) ) );
  
  return md5(md5(($q . PES_HASH . $q)));
}

function pes_decrypt($q) {
  return rtrim( mcrypt_decrypt( MCRYPT_RIJNDAEL_256, md5( PES_HASH ), base64_decode( $q ), MCRYPT_MODE_CBC, md5( md5( PES_HASH ) ) ), "\0");
}



/**
 * Recognize download URL.
 */
function pes_download_attachment_redirect() {
  global $wp;

  if ( preg_match( '/^download-pes-photo\/(\d+)$/', $wp->request, $vars ) === 1 ) {
    $attachment_id = pes_decrypt($vars[1]);
    if ( get_post_type( $attachment_id ) === 'attachment' ) {
      if (!in_array($vars[2], array('hr', 'web'))) $vars[2] = 'web';
      pes_download_attachment($vars[1], $vars[2]);
    }
  }
}
add_action( 'send_headers', 'pes_download_attachment_redirect' );


function pes_check_photos_from_gallery($post_id, $photos) {
  foreach ($photos as $attachment_id) {
    $attachment = get_post($attachment_id);
    if ($attachment->post_parent != $post_id) return FALSE;
  }
  return TRUE;
}

/**
 * Get single attachment download url
 * 
 * @param 	int $attachment_id
 * @param 	string $size the desired size of the file.
 * @return 	mixed
 */
function pes_download_attachment_url( $attachment_id = 0, $size = 'hr' ) {
  if ( get_post_type( $attachment_id ) === 'attachment' ) {
    $crypted = pes_crypt($attachment_id);
    $query_string = 'type=attachment&key='. urlencode($crypted) .'&id='. urlencode($attachment_id) .'&size='. urlencode($size);
    return plugins_url('pes-functionalities/includes/download.php?'. htmlentities($query_string));
  } 
  else {
    return '';
  }
}

/**
 *
 */
function pes_download_gallery_url($post_id, $size = 'hr') {
  if ( get_post_type( $post_id ) === 'gallery' ) {
    $crypted = pes_crypt($post_id);
    $query_string = 'type=gallery&key='. urlencode($crypted) .'&id='. urlencode($post_id) .'&size='. urlencode($size);
    return plugins_url('pes-functionalities/includes/download.php?'. htmlentities($query_string));
  } 
  else {
    return '';
  }
}

/**
 * Process attachment download function
 * 
 * @param 	int $attachment_id
 * @return 	mixed
 */
function pes_download_attachment( $attachment_id = 0, $size = 'hr' ) {
	if ( get_post_type( $attachment_id ) === 'attachment' ) {
		// get options
		$options = get_option( 'download_attachments_general' );

		if ( ! isset( $options['download_method'] ) )
			$options['download_method'] = 'force';

		// get wp upload directory data
		$uploads = wp_upload_dir();

		// get file name
		$attachment = get_post_meta( $attachment_id, '_wp_attached_file', true );

		// force download
		if ( $options['download_method'] === 'force' ) {
			// get file path
			$filepath = apply_filters( 'da_download_attachment_filepath', $uploads['basedir'] . '/' . $attachment, $attachment_id );
          
          // If downloading the WEB size photo.
          if ($size == 'web') {
            $metadata = wp_get_attachment_metadata($attachment_id);
            $filepath_tmp = $uploads['basedir'] . '/' . $attachment;
            $filebase = dirname($filepath_tmp);
            $filepath = $filebase .'/'. $metadata['sizes']['large']['file'];
          }

			// file exists?
			if ( ! file_exists( $filepath ) || ! is_readable( $filepath ) )
				return false;

			// if filename contains folders
			if ( ( $position = strrpos( $attachment, '/', 0 ) ) !== false )
				$filename = substr( $attachment, $position + 1 );
			else
				$filename = $attachment;

			// disable compression
			if ( ini_get( 'zlib.output_compression' ) )
				@ini_set( 'zlib.output_compression', 0 );
			
			if ( function_exists( 'apache_setenv' ) ) {
				@apache_setenv( 'no-gzip', 1 );
			}
			
			// disable max execution time limit
			if ( ! in_array( 'set_time_limit', explode( ',',  ini_get( 'disable_functions' ) ) ) && ! ini_get( 'safe_mode' ) ) {
				@set_time_limit(0);
			}
			
			// disable magic quotes runtime
			if ( function_exists( 'get_magic_quotes_runtime' ) && get_magic_quotes_runtime() && version_compare( phpversion(), '5.4', '<' ) ) {
				set_magic_quotes_runtime(0);
			}

			// set needed headers
			nocache_headers();
			header( 'Robots: none' );
			header( 'Content-Type: application/download' );
			header( 'Content-Description: File Transfer' );
			header( 'Content-Disposition: attachment; filename=' . rawurldecode( $filename ) );
			header( 'Content-Transfer-Encoding: binary' );
			header( 'Accept-Ranges: bytes' );
			header( 'Expires: 0' );
			header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
			header( 'Pragma: public' );
			header( 'Content-Length: ' . filesize( $filepath ) );

			// increase downloads count
			update_post_meta( $attachment_id, '_da_downloads', (int) get_post_meta( $attachment_id, '_da_downloads', true ) + 1 );
          
          // increase download count for PES.
          $post_meta_pes = '_pes_downloads_'. $size;
          update_post_meta( $attachment_id, $post_meta_pes, (int) get_post_meta( $attachment_id, $post_meta_pes, true ) + 1 );
			
			// action hook
			do_action( 'da_process_file_download', $attachment_id );

			// start printing file
			if ( $filepath = fopen( $filepath, 'rb' ) ) {
				while ( ! feof( $filepath ) && ( ! connection_aborted()) ) {
					echo fread( $filepath, 1048576 );
					flush();
				}

				fclose( $filepath );
			} else
				return false;

			exit;
		// redirect to file
		} else {
			// increase downloads count
			update_post_meta( $attachment_id, '_da_downloads', (int) get_post_meta( $attachment_id, '_da_downloads', true ) + 1 );
			
			// action hook
			do_action( 'da_process_file_download', $attachment_id );

			// force file url
			header( 'Location: ' . apply_filters( 'da_download_attachment_filepath', $uploads['baseurl'] . '/' . $attachment, $attachment_id ) );
			exit;
		}
	} else
		return false;
}


/**
 * Process attachment download function
 * 
 * @param 	int $attachment_id
 * @return 	mixed
 */
function pes_download_gallery($post_id, $photos, $size = 'hr') {
  if ( get_post_type( $post_id ) === 'gallery' ) {
    $files = array();
    // get wp upload directory data
    $uploads = wp_upload_dir();
    foreach ($photos as $attachment_id) {
      // get file name
      $attachment = get_post_meta( $attachment_id, '_wp_attached_file', true );
      
      // get file path
      $filepath = apply_filters( 'da_download_attachment_filepath', $uploads['basedir'] . '/' . $attachment, $attachment_id );
      
      // If downloading the WEB size photo.
      if ($size == 'web') {
        $metadata = wp_get_attachment_metadata($attachment_id);
        $filepath_tmp = $uploads['basedir'] . '/' . $attachment;
        $filebase = dirname($filepath_tmp);
        $filepath = $filebase .'/'. $metadata['sizes']['large']['file'];
      }
      
      // file exists?
      if ( ! file_exists( $filepath ) || ! is_readable( $filepath ) )
        return false;
      
      // if filename contains folders
      if ( ( $position = strrpos( $attachment, '/', 0 ) ) !== false )
          $filename = substr( $attachment, $position + 1 );
      else
          $filename = $attachment;
      
      $files[] = array('filepath' => $filepath, 'filename' => $filename);
    }
    
    $zipname = 'Photos'. strtoupper($size) . time() .'.zip';
    
    $zip = new ZipArchive;
    if ($zip->open($zipname, ZipArchive::CREATE) === TRUE) {
      foreach ($files as $filedata) {
        $zip->addFile($filedata['filepath'], $filedata['filename']);
      }
      $zip->close();
    } else {
      echo 'Failed to download zip file. Please contact site administrator.';
    }
    
    header( 'Robots: none' );
    header( 'Content-Type: application/zip' );
    header( 'Content-Description: File Transfer' );
    header( 'Content-Disposition: attachment; filename=' . $zipname );
    header( 'Content-Transfer-Encoding: binary' );
    header( 'Accept-Ranges: bytes' );
    header( 'Expires: 0' );
    header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
    header( 'Pragma: public' );
    header( 'Content-Length: ' . filesize( $zipname ) );
    readfile($zipname);
    
    exit;
  }
  else return FALSE;
}