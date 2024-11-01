<?php
/*
Plugin Name: WP Baduk 
Plugin URI:http://senseis.xmp.net/?WPBaduk
Description: Embed Sensei's Library style diagrams in your blog. 
Version: 0.1
Author: Josh Guffin 
Author URI:http://senseis.xmp.net/?WPBaduk
 */

/*  Copyright 2009  Josh Guffin (email : josh.guffin@gmail.com)

	 This program is free software; you can redistribute it and/or modify
	 it under the terms of the GNU General Public License as published by
	 the Free Software Foundation; either version 2 of the License, or
	 (at your option) any later version.

	 This program is distributed in the hope that it will be useful,
	 but WITHOUT ANY WARRANTY; without even the implied warranty of
	 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	 GNU General Public License for more details.

	 You should have received a copy of the GNU General Public License
	 along with this program; if not, write to the Free Software
	 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

include 'draw.php';
//include 'wp-baduk-config.php';


function baduk_parse($content) {
  if ( !function_exists('add_shortcode') ) return; // learn to upgrade....
  add_shortcode( 'baduk' , 'shortcode_baduk');
  $content = do_shortcode($content);
  remove_shortcode('baduk');
  return $content;
}

function shortcode_baduk($atts,$content) {
  if ( NULL === $content ) return '';

  $input     = trim($content);
  $output    = "";

  // Pre-2.6 compatibility
  if ( !defined('WP_CONTENT_URL') )
	 define( 'WP_CONTENT_URL', get_option('siteurl') . '/wp-content');
  if ( !defined('WP_CONTENT_DIR') )
	 define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
  // Guess the location
  $plugin_path = WP_CONTENT_DIR.'/plugins/'.plugin_basename(dirname(__FILE__));
  $plugin_url = WP_CONTENT_URL.'/plugins/'.plugin_basename(dirname(__FILE__));

  $pngfile = "$plugin_path/diagrams/".md5($input).".png";
  $sgffile = "$plugin_path/diagrams/".md5($input).".sgf";
  $txtfile = "$plugin_path/diagrams/".md5($input).".txt";
  $pngurl  = "$plugin_url/diagrams/".md5($input).".png";
  $sgfurl  = "$plugin_url/diagrams/".md5($input).".sgf";
  $txturl  = "$plugin_url/diagrams/".md5($input).".txt";

  if (file_exists($pngfile)) 
  {
	 // so we can clear old images/sgfs from the cache later
	 touch($pngfile);
	 touch($sgffile);  
  } 
  else
  {
	 $diagram =& new GoDiagram($input);
	 if ($diagram->diagram)	// check that parsing was ok
	 {
		//create the SGF/PNG
		$sgfdata = $diagram->createSGF();
		$png =& $diagram->createPNG();

		//save them to disk
		$sgfhandle = fopen($sgffile,'w');
		fwrite($sgfhandle,$sgfdata);
		fclose($sgfhandle);

		$txthandle = fopen($txtfile,'w');
		fwrite($txthandle,$input);
		fclose($txthandle);

		imagepng($png,$pngfile);
	 }
  }

  if (!file_exists($pngfile))
  {
	 $output = "<img style=\"margin-left:auto; margin-right:auto;\" src=\"$plugin_url/error.gif\">";
  }
  else
  {
	 $imagesize = getimagesize($pngfile);
	 $imagesize[0] += 10;
	 $imagesize[1] += 10;

	 ob_start();
	 $imagedivcss = get_option( 'wp_baduk_image_div_css' );
print <<< BADOOKING
	 <style type="text/css">
		#wpbadukimage
		{
		  background-color: white;
		  margin:5px auto;
		  border:1px solid silver;
		  width:$imagesize[0]px;
		  height:$imagesize[1]px;
		  display:table;
		}
		#wpbadukimage img
		{
		  padding-top:5px; 
		  margin-left:5px; 
		  margin-right:5px; 
		  margin-top:5px;
		}
		#wpbadukimagep a
		{
		  margin-left:5px;
		  margin-right:5px;
		}
		#wpbadukimagep a:hover
		{
		  text-decoration:none;
		}
		#wpbadukimagep
		{
		  margin-top:-10px;
		  margin-bottom:-3px;
		  text-align:center;
		  font-size:xx-small;
		}
	 </style>
	 <div id="wpbadukimage"> <img src="$pngurl"><br> <p id="wpbadukimagep"> <a href="$pngurl">image</a> <a href="$sgfurl">sgf</a> <a href="$txturl">text</a> </p></div>
BADOOKING;
	 $output = ob_get_clean();
  }
  return $output;

}






// add hooks to parse content for [baduk].  Must have priority 1, otherwise 
// different filters can mess up the diagrams.
add_filter('the_content', 'baduk_parse',1);
add_filter('comment_text','baduk_parse', 1);

//add_action('admin_menu', 'wp_baduk_menu');

function wp_baduk_menu() {
  add_submenu_page('plugins.php', 'WP Baduk Options', 'WP Baduk Configuration', 8, __FILE__, 'wp_baduk_options');
}


?>
