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
 * Changes the output of the search and filter input values
 * before rendering it.
 * Based on: https://www.designsandcode.com/documentation/search-filter-pro/action-filter-reference/#Filter_Input_Object
 * Example: https://gist.github.com/rmorse/7b59b45a14b1ca179868
 */
function pes_search_filter_change_label($input_object, $sfid) {
  //echo '<pre>LARGE attachment'.print_r($input_object,1).'</pre>';
  if ($sfid == 16100 && $input_object['name'] == '_sf_post_type') {
    foreach ($input_object['options'] as $key => $option) {
      if ($option->label == 'Media') {
        $input_object['options'][$key]->label = 'Photos';
      }
    }
  }
  return $input_object;
}
add_filter('sf_input_object_pre', 'pes_search_filter_change_label', 10, 2);




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



/**
 * Process attachment download function
 * 
 * @param 	int $attachment_id
 * @return 	mixed
 */
function pes_download_attachment( $attachment_id = 0 ) {
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

			// file exists?
			if ( ! file_exists( $filepath ) || ! is_readable( $filepath ) )
				return false;

			// if filename contains folders
			if ( ( $position = strrpos( $attachment, '/', 0 ) ) !== false )
				$filename = substr( $attachment, $position + 1 );
			else
				$filename = $attachment;
          
          
          
//          $attachment_location = $_SERVER["DOCUMENT_ROOT"] . "/file.zip";
//          if (file_exists($filepath)) {
//
//              header($_SERVER["SERVER_PROTOCOL"] . " 200 OK");
//              header("Cache-Control: public"); // needed for i.e.
//              header("Content-Type: application/zip");
//              header("Content-Transfer-Encoding: Binary");
//              header("Content-Length:".filesize($filepath));
//              header("Content-Disposition: attachment; filename=" . rawurldecode( $filename ) );
//              readfile($filepath);
//              die();        
//          } else {
//              die("Error: File not found.");
//          } 
          
          
          
          
          
          // required for IE
          if(ini_get('zlib.output_compression')) 
              ini_set('zlib.output_compression', 'Off');	
          
          // get the file mime type using the file extension
          switch(strtolower(substr(strrchr($filepath,'.'),1)))
          {
              case 'pdf': $mime = 'application/pdf'; break;
              case 'zip': $mime = 'application/zip'; break;
              case 'jpeg':
              case 'jpg': $mime = 'image/jpg'; break;
              default: exit();
          }
          header('Pragma: public'); 	// required
          header('Expires: 0');		// no cache
          header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
          header('Last-Modified: '.gmdate ('D, d M Y H:i:s', filemtime ($filepath)).' GMT');
          header('Cache-Control: private',false);
          header('Content-Type: '.$mime);
          header('Content-Disposition: attachment; filename="'.basename($filepath).'"');
          header('Content-Transfer-Encoding: binary');
          header('Content-Length: '.filesize($filepath));	// provide file size
          header('Connection: close');
          readfile($filepath);		// push it out
          exit();
          
          
          
          
          
          
          
          
          
          

//			 turn off compression
//			if ( ini_get( 'zlib.output_compression' ) )
//				ini_set( 'zlib.output_compression', 0 );
//          
//			 set needed headers
//			header( 'Content-Type: application/download' );
//			header( 'Content-Disposition: attachment; filename=' . rawurldecode( $filename ) );
//			header( 'Content-Transfer-Encoding: binary' );
//			header( 'Accept-Ranges: bytes' );
//			header( 'Cache-control: private' );
//			header( 'Pragma: private' );
//			header( 'Expires: Mon, 26 Jul 1997 05:00:00 GMT' );
//			header( 'Content-Length: ' . filesize( $filepath ) );
//
//			// increase counter of downloads
//			update_post_meta( $attachment_id, '_da_downloads', (int) get_post_meta( $attachment_id, '_da_downloads', true ) + 1 );
//
//			// start printing file
//			if ( $filepath = fopen( $filepath, 'rb' ) ) {
//				while ( ! feof( $filepath ) && ( ! connection_aborted()) ) {
//					echo fread( $filepath, 1048576 );
//					flush();
//				}
//
//				fclose( $filepath );
//			} else
//				return false;

//			exit;
		// redirect to file
		} else {
			// increase counter of downloads
			update_post_meta( $attachment_id, '_da_downloads', (int) get_post_meta( $attachment_id, '_da_downloads', true ) + 1 );

			// force file url
			header( 'Location: ' . apply_filters( 'da_download_attachment_filepath', $uploads['url'] . '/' . $attachment, $attachment_id ) );
			exit;
		}
	} else
		return false;
}



// ONLY FOR REGISTERED USERS: https://www.dfactory.eu/support/topic/download-allowed-for-registered-users/

class Download_Pes {

	/**
	 * Class constructor.
	 */
	public function __construct() {




		// settings
		/*$this->options = array( 'general' => get_option( 'download_pes_general', $this->defaults['general'] ) );

		// actions
		add_action( 'plugins_loaded', array( &$this, 'load_textdomain' ) );
		add_action( 'after_setup_theme', array( &$this, 'pass_variables' ), 9 );
		add_action( 'admin_enqueue_scripts', array( &$this, 'admin_scripts_styles' ) );
		add_action( 'wp_enqueue_scripts', array( &$this, 'frontend_scripts_styles' ) );
		add_action( 'send_headers', array( &$this, 'download_redirect' ) );

		// filters
		add_filter( 'the_content', array( &$this, 'add_content' ) );
		add_filter( 'plugin_row_meta', array( &$this, 'plugin_extend_links' ), 10, 2 );
		add_filter( 'plugin_action_links', array( &$this, 'plugin_settings_link' ), 10, 2 );*/
	}
}