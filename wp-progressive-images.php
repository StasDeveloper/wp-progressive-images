<?php
/**
 * Plugin Name: Wordpress Progressive Images
 * Plugin URI: https://github.com/girin/wp-progressive-images
 * Description: Plugin for Progressive Image Loading with a blur effect to reduce the page load time
 * Version: 0.0.1
 * Author: Andrew Hirin
 * Author URI: https://github.com/girin
 * Text Domain: wp-progressive-images
 * License: GPL2+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.txt
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/* Define our plugin constants */
define( 'WPPI_URL', plugin_dir_url( __FILE__ ) );
define( 'WPPI_PATH', plugin_dir_path( __FILE__ ) );
define( 'WPPI_VERSION', '0.0.9' );

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style(
		'wppi-css',
		WPPI_URL . 'css/main.css',
		'',
		WPPI_VERSION,
		''
	);
	wp_enqueue_script(
		'wppi-js',
		WPPI_URL . 'js/main.js',
		'',
		WPPI_VERSION,
		true
	);
} );

/**
 * Filter for replace IMG tags with necessary structure in the post content
 *
 * @param string $content   current post content
 *
 * @return string Modified post content
 */
function wppi_filter_images( $content ) {
	/* Apply only in the frontend */
	if ( is_admin() ) {
		return $content;
	}

	/* Pattern to find IMG tags in the content */
	$pattern = '/<(img[\w\W]+?src=")([\w\W]+?)\.(png|jpg|jpeg|gif)("[\w\W]+?[\/]?)>/';

	/* Replacing IMG tags with new structures */
	$content = preg_replace_callback( $pattern, 'wppi_replace_callback', $content );

	return $content;
}

add_filter( 'the_content', 'wppi_filter_images', 99 );

/**
 * Replace Callback function for generate necessary structure to replace each IMG tag
 *
 * @param array $parts   $matches from preg_replace_callback()
 *
 * @return string Content to replace IMG tag
 */
function wppi_replace_callback( $parts ) {
	/* Generate the full image URL */
	if ( strpos( $parts[2], 'http' ) === false ) {
		$image = home_url( $parts[2] . '.' . $parts[3] );
	} else {
		$image = $parts[2] . '.' . $parts[3];
	}

	/* Get image size */
	$image_size = getimagesize( $image );

	/* Get parts of the image file */
	$image_path_parts = pathinfo( $image );

	/* Generate URL and PATH for a small copy of the image */
	$small_file_path = WPPI_PATH . 'thumbs/' . $image_path_parts['filename'] . '_thumb.' . $image_path_parts['extension'];
	$small_file_url  = WPPI_URL . 'thumbs/' . $image_path_parts['filename'] . '_thumb.' . $image_path_parts['extension'];

	/* Create a new small copy of the image if it does not exist */
	if ( ! file_exists( $small_file_path ) ) {
		wppi_image_resize( $image, $small_file_path, $image_path_parts['extension'], $image_size );
	}

	/* Add custom class to IMG tag */
	if ( strpos( $parts[0], 'class="' ) !== false ) {
		$parts[1] = preg_replace( '/class="([\w\W]+?)"/', 'class="$1 progressiveMedia-image"', $parts[1] );
		$parts[4] = preg_replace( '/class="([\w\W]+?)"/', 'class="$1 progressiveMedia-image"', $parts[4] );
	} else {
		$parts[4] .= ' class="progressiveMedia-image"';
	}

	/* Generate content to replace IMG tag */
	$replace = '<canvas class="progressiveMedia-canvas"></canvas><'
	           . $parts[1] . $small_file_url . $parts[4]
	           . ' data-src="' . $parts[2] . '.' . $parts[3] . '"'
	           . ' data-width="' . $image_size[0] . '"'
	           . ' data-height="' . $image_size[1] . '">';

	return $replace;
}

/**
 * Function for creating a new small copy of the image
 *
 * @param string $file            original image file url
 * @param string $new_file_path   full path to a new small copy
 * @param string $extension       image file extension
 * @param array  $image_size      image file size
 */
function wppi_image_resize( $file, $new_file_path, $extension, $image_size ) {
	switch ( $extension ) {
		case 'jpg':
		case 'jpeg':
			$source = imagecreatefromjpeg( $file );
			break;
		case 'gif':
			$source = imagecreatefromgif( $file );
			break;
		case 'png':
			$source = imagecreatefrompng( $file );
			break;
		default:
			$source = false;
			break;
	}

	list( $width, $height ) = $image_size;
	$ratio     = 30 / $width;
	$newwidth  = 30;
	$newheight = $height * $ratio;

	$destination = imagecreatetruecolor( $newwidth, $newheight );
	if ( 'png' == $extension || ( 'gif' == $extension ) ) {
		imagealphablending( $destination, false );
		imagesavealpha( $destination, true );
		$transparent = imagecolorallocatealpha( $destination, 255, 255, 255, 127 );
		imagefilledrectangle( $destination, 0, 0, $width, $height, $transparent );
	}
	imagecopyresampled( $destination, $source, 0, 0, 0, 0, $newwidth, $newheight, $width, $height );

	switch ( $extension ) {
		case 'jpg':
		case 'jpeg':
			imagejpeg( $destination, $new_file_path, 20 );
			break;
		case 'gif':
			imagegif( $destination, $new_file_path );
			break;
		case 'png':
			imagealphablending( $destination, false );
			imagesavealpha( $destination, true );
			imagepng( $destination, $new_file_path );
			break;
		default:
			break;
	}
}
