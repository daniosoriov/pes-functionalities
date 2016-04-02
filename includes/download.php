<?php
// load wp core
$path = explode( 'wp-content', __FILE__ );
include_once( reset( $path ) . 'wp-load.php' );

$crypted = isset( $_GET['key'] ) ? urldecode($_GET['key']) : 0;
$attachment_id = isset( $_GET['id'] ) ? urldecode((int) $_GET['id']) : 0;
$size = isset( $_GET['size'] ) ? urldecode($_GET['size']) : 'web';

//echo 'crypted: '. $crypted .'<br />';
//echo 'attachment_id: '. $attachment_id .'<br />';
//echo 'size: '. $size .'<br />';

if (pes_crypt($attachment_id) == $crypted) {
  if ( get_post_type( $attachment_id ) === 'attachment' ) {
    if (!in_array($size, array('hr', 'web'))) $size = 'web';
    pes_download_attachment($attachment_id, $size);
  }
}