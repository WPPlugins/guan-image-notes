<?php 
/*
Plugin Name: Guan Image Notes
Plugin URI: http://pangeran.org/guan-image-notes/
Description: Allows you and your visitors to add comment as textual annotations to images by select a region of the image and then attach a textual description to it. The notes or annotations is intergrated with WordPress comment system.
Author: Pangeran Wiguan
Version: 1.1
Author URI: http://wiguan88.com
*/

/*

Guan Image Notes | Add notes tagging to your images in WordPress powered blogs.
Copyright (C) 2010  Pangeran Wiguan

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

*/

//header function
function load_image_annotation_js() {
	$plugindir = get_settings('home').'/wp-content/plugins/'.dirname(plugin_basename(__FILE__));
	echo "<script src='http://ajax.googleapis.com/ajax/libs/jquery/1.3/jquery.min.js' type='text/javascript'></script>\n";
	echo "<script type='text/javascript' src='". $plugindir ."/js/jquery.annotate.js'></script>\n";
	echo "<script type='text/javascript' src='". $plugindir ."/js/jquery-ui-1.7.1.js'></script>\n";
	echo "<link rel='stylesheet' href='$plugindir/css/annotation.css' type='text/css' />\n";
	
	function ae_detect_ie()
	{
		if (isset($_SERVER['HTTP_USER_AGENT']) && 
		(strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false))
			return true;
		else
			return false;
	}
	
	/**
	 *Since Guan Image Notes Version 1.0
	 *Add notification for user to go to single page if other page than single page for example homepage.
	  */  
	if (is_single()) : { ?>
		
    <script language="javascript">
	$(document).ready(function(){
			$("img").each(function() {
				var idname = $(this).attr("id")
				if(idname.substring(0,4) == "img-") {
					source = $(this).attr('src');
					var addablecon = $(this).attr("addable")
					addablecon = addablecon == undefined ? "true" : addablecon;
					
					$(this).wrap($('<div id=' + idname.substring(4,idname.length) + ' ></div>'));
					
					$('#' + idname).mouseover(function() {
						$(this).annotateImage({
							getPostID: <?php global $wp_query; $thePostID = $wp_query->post->ID; echo $thePostID; ?>,
							getImgID: idname,
							getUrl: "<?php echo $plugindir; ?>/imageannotation-run.php",
							saveUrl: "<?php echo $plugindir; ?>/imageannotation-run.php",
							deleteUrl: "<?php echo $plugindir; ?>/imageannotation-run.php",
							editable: <?php get_currentuserinfo(); global $user_level; if ($user_level > 0) { ?>true<?php } else { ?>false<?php } ?>,
							addable: <?php get_currentuserinfo(); global $user_level; if ($user_level > 0) { ?>true<?php } else { ?> addablecon == "true" ? true : false <?php } ?>
						});
					});
				}
			});
			
			$('div').each(function() {
				var divid = $(this).attr("id");
				if(divid.substring(0,8) == "comment-") {
					var getimgsrc = imageSource(divid.substring(8,divid.length));
					if(getimgsrc != "") {
						$(this).remove("noted");
						$(this).html('<div class="image-note-thumbnail"><a href="#' + divid.substring(8,divid.length) + '"><img src="' + getimgsrc + '" /></a></div>');
					}
				}
			});
	});
	
	function imageSource(id) {
		var idreturn = "";
		$('img').each(function() {
			var imgid = $(this).attr("id");
			if(imgid == "img-" + id) {
				idreturn = $(this).attr("src");
			}
		});
		
		return idreturn;
	}
	
	</script>
    
	<?php }
	
	else : ?>
    
    <script language="javascript">
	$(document).ready(function(){
			$("img").each(function() {
				var idname = $(this).attr("id")
				if(idname.substring(0,4) == "img-") {
					source = $(this).attr('src');
					var addablecon = $(this).attr("addable")
					addablecon = addablecon == undefined ? "true" : addablecon;
					
					$(this).wrap($('<div id=' + idname.substring(4,idname.length) + ' ></div>'));
					
					$('#' + idname).mouseover(function() {
						$(this).annotateImage({
							getPostID: <?php global $wp_query; $thePostID = $wp_query->post->ID; echo $thePostID; ?>,
							getImgID: idname,
							getUrl: "<?php echo $plugindir; ?>/imageannotation-run.php",
							saveUrl: "<?php echo $plugindir; ?>/imageannotation-run.php",
							deleteUrl: "<?php echo $plugindir; ?>/imageannotation-run.php",
							editable: <?php get_currentuserinfo(); global $user_level; if ($user_level > 0) { ?>false<?php } else { ?>false<?php } ?>,
							addable2: <?php get_currentuserinfo(); global $user_level; if ($user_level > 0) { ?>true<?php } else { ?> addablecon == "true" ? true : false <?php } ?>
						});
					});
				}
			});
			
			$('div').each(function() {
				var divid = $(this).attr("id");
				if(divid.substring(0,8) == "comment-") {
					var getimgsrc = imageSource(divid.substring(8,divid.length));
					if(getimgsrc != "") {
						$(this).remove("noted");
						$(this).html('<div class="image-note-thumbnail"><a href="#' + divid.substring(8,divid.length) + '"><img src="' + getimgsrc + '" /></a></div>');
					}
				}
			});
	});
	
	function imageSource(id) {
		var idreturn = "";
		$('img').each(function() {
			var imgid = $(this).attr("id");
			if(imgid == "img-" + id) {
				idreturn = $(this).attr("src");
			}
		});
		
		return idreturn;
	}
	
	</script>
    
    <?php endif;
    
    
}

//comment function
function getImgID() {
	global $comment;
	$commentID = $comment->comment_ID;
	
	global $wpdb;
	$imgIDNow = $wpdb->get_var("SELECT note_img_ID FROM guan_imagenote WHERE note_comment_id = ".(int)$commentID);
	
	if($imgIDNow != "") {
		$str = substr($imgIDNow, 4, strlen($imgIDNow));
		echo "<div id=\"comment-".$str."\"><a href='#".$str."'>noted on #".$imgIDNow."</a></div>";
	} else {
		echo "&nbsp;";	
	}
}

/**
 *Since Guan Image Notes Version 1.0
 *This function add id="img-012345678" into the image attribute while inserting the image into post using WordPress editor.
 *The number is unique and changed dynamically according to image id attachment.
 */
function guan_id_inserter($html, $id , $alt, $title) {
	$html = str_replace('<img','<img id="img-' . $id . '" ',$html);
	return $html;
}
add_filter('get_image_tag','guan_id_inserter', 10, 4);

/**
 *Since Guan Image Notes Version 1.0
 *This function add the commented image thumbnail in the comment area.
 */
function guan_getImgID_inserter($comment_ID = 0){
	getImgID();
	$guan_comment_content = get_comment_text();
	return $guan_comment_content;
}
add_filter('comment_text', 'guan_getImgID_inserter', 10, 4);

add_action('wp_head', 'load_image_annotation_js');
add_filter('Comments', 'getImgID');
?>