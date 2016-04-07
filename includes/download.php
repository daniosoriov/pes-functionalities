<?php
// load wp core
$path = explode( 'wp-content', __FILE__ );
include_once( reset( $path ) . 'wp-load.php' );

$type = isset( $_GET['type'] ) ? urldecode($_GET['type']) : '';
$crypted = isset( $_GET['key'] ) ? urldecode($_GET['key']) : 0;
$post_id = isset( $_GET['id'] ) ? urldecode((int) $_GET['id']) : 0;
$size = isset( $_GET['size'] ) ? urldecode($_GET['size']) : 'web';

/*echo 'type: '. $type .'<br />';
echo 'crypted: '. $crypted .'<br />';
echo 'post_id: '. $post_id .'<br />';
echo 'size: '. $size .'<br />';
echo '<pre>GET '.print_r($_GET,1).'</pre>';
echo '<pre>POST '.print_r($_POST,1).'</pre>';*/

if (pes_crypt($post_id) == $crypted && get_post_type($post_id) === $type) {
  if (!in_array($size, array('hr', 'web'))) $size = 'web';
  switch ($type) {
    case 'attachment':
      pes_download_attachment($post_id, $size);
      break;

    case 'gallery':
      $photos = (isset($_POST['photos'])) ? $_POST['photos'] : '';
      if ($photos) {
        $photos = explode(',', $photos);
        if (pes_check_photos_from_gallery($post_id, $photos)) {
          pes_download_gallery($post_id, $photos, $size);
        }
      }
      break;
  }
}