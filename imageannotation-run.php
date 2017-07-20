<?php require_once( "config.php" );

$action = isset($_REQUEST['action']) ? trim($_REQUEST['action']) : '';

if($action == "get") {
	getResults();	
}
else if($action == "save") {
	getSave();	
}
else if($action == "delete") {
	getDelete();	
}

function getSave() {
	//save image note
	$imgID = isset($_REQUEST['imgid']) ? trim($_REQUEST['imgid']) : '';	
	$postID = isset($_REQUEST['postid']) ? trim($_REQUEST['postid']) : 0;	
	
	//get data from jQuery
	$data = array(
		$_GET["top"],
		$_GET["left"],
		$_GET["width"],
		$_GET["height"],
		html2txt($_GET["text"]),
		$_GET["id"],
		$_GET["author"],
		$_GET["email"],
	);	
	
	global $wpdb;
	
	if($data[5] != "new") {
		//if image note is not new will delete the old image note
		
		//find the old image note
		$result = $wpdb->get_results("SELECT * FROM guan_imagenote WHERE note_img_ID='".$imgID."' and note_text_ID='".$data[5]."'");
		foreach ($result as $commentresult) {
			$comment_id = (int)$commentresult->note_comment_ID; //comment ID
			$comment_author = $commentresult->note_author; //comment Author
			$comment_email = $commentresult->note_email; //comment Email
		};
		
		//delete image note
		$wpdb->query(" DELETE FROM guan_imagenote WHERE note_img_ID='".$imgID."' and note_text_ID='".$data[5]."'");
				
		//update comment with latest image note
		$wpdb->query("UPDATE $wpdb->comments SET comment_content = '".$data[4]."' WHERE comment_ID = ".$comment_id);
						
	} else {
		//if image note is new
		
		$comment_post_ID = $postID;		
		$comment_author       = ( isset($_GET['author']) )  ? trim(strip_tags($_GET['author'])) : null;
		$comment_author_email = ( isset($_GET['email']) )   ? trim($_GET['email']) : null;
		$comment_author_url   = ( isset($_GET['url']) )     ? trim($_GET['url']) : null;
		$comment_content      = $data[4];
		
		//If the user is logged in, get author name and author email
		$user = wp_get_current_user();
		if ( $user->ID ) {
			if ( empty( $user->display_name ) )
				$user->display_name=$user->user_login;
			$comment_author       = $wpdb->escape($user->display_name);
			$comment_author_email = $wpdb->escape($user->user_email);
			$comment_author_url   = $wpdb->escape($user->user_url);
			if ( current_user_can('unfiltered_html') ) {
				if ( wp_create_nonce('unfiltered-html-comment_' . $comment_post_ID) != $_POST['_wp_unfiltered_html_comment'] ) {
					kses_remove_filters();
					kses_init_filters();
				}
			}
		}
		
		//insert image note into comment
		$user_ID = $user->ID;
		$comment_type = '';
		$comment_parent = isset($_POST['comment_parent']) ? absint($_POST['comment_parent']) : 0;
		$commentdata = compact('comment_post_ID', 'comment_author', 'comment_author_email', 'comment_author_url', 'comment_content', 'comment_type', 'comment_parent', 'user_ID');
		$comment_id = wp_new_comment( $commentdata );
	}
	
	//insert new image note
	$wpdb->query("INSERT INTO `guan_imagenote`
										(
											`note_img_ID`,
											`note_comment_ID`,
											`note_author`,
											`note_email`,
											`note_top`,
											`note_left`,
											`note_width`,
											`note_height`,
											`note_text`,
											`note_text_id`,
											`note_editable`,
											`note_date`
										)
										VALUES (
										'".addslashes($imgID)."',
										'".addslashes($comment_id)."',
										'".addslashes($comment_author)."',
										'".addslashes($comment_author_email)."',
										".addslashes($data[0]).",
										".addslashes($data[1]).",
										".addslashes($data[2]).",
										".addslashes($data[3]).",
										'".addslashes($data[4])."',
										'".addslashes("id_".md5($data[4]))."',
										1,
										now()
										)");

	
	//output JSON array
	echo '{ "annotation_id": "id_'.md5($data[4]).'" }';
}

function getDelete() {
	//delete image note
	$qsType = isset($_REQUEST['imgid']) ? trim($_REQUEST['imgid']) : '';
	$data = array(
		$_GET["id"],
	);

	global $wpdb;
	
	//find the comment ID frm guan_imagenote
	$result = $wpdb->get_results("SELECT * FROM guan_imagenote WHERE note_img_ID='".$qsType."' and note_text_ID='".$data[0]."'");
		
	foreach ($result as $commentresult) {
		$comment_id = (int)$commentresult->note_comment_ID; //comment ID
	};
	
	//delete note
	$wpdb->query("DELETE FROM guan_imagenote WHERE note_img_ID='".$qsType."' and note_text_ID='".$data[0]."'");
	//delete comment
	$wpdb->query("DELETE FROM $wpdb->comments WHERE comment_ID = ".$comment_id);
}

function getResults() {
	//create table at first
	createTable();
	
	//get image note
	$qsType = isset($_REQUEST['imgid']) ? trim($_REQUEST['imgid']) : '';
	
	global $wpdb;
	
	$result = $wpdb->get_results("SELECT * FROM guan_imagenote WHERE note_img_ID = '".$qsType."' ");
		
	//output JSON array
	echo "[";
	foreach ($result as $topten) {		
		$commentApprove = $wpdb->get_var($wpdb->prepare("SELECT comment_approved FROM $wpdb->comments WHERE comment_ID = %d", (int)$topten->note_comment_ID));
		
		//the image note will auto delete if comment is deleted from admin, 
		if($commentApprove == "") {
		$commentApprove = $wpdb->get_var("SELECT comment_approved FROM $wpdb->comments WHERE comment_ID = ".(int)$topten->note_comment_ID);
		}
				
		/**
		 *Since Guan Image Notes Version 1.0
		 *Add gravatar in the notes.
		 */
		if($commentApprove == 1) {
        
			 echo "{ \"top\": " . (int)$topten->note_top . ", \"left\": " . (int)$topten->note_left . ", \"width\": " . (int)$topten->note_width . ", \"height\": " . (int)$topten->note_height . ", \"text\": \"" . $topten->note_text . "<br /><span class='notes-gravatar'>" . get_avatar($topten->note_email, 38) . "</span><br /><span class='image-annotate-author'><strong>" . $topten->note_author . "</strong></span>\", \"id\": \"" . $topten->note_text_ID . "\", \"editable\": true},";
		
		}		
	};
	echo "]";
}

//create table function
function createTable () {
   global $wpdb;
   $table_name = $wpdb->prefix . "guan_imagenote";
   if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
      
      $sql = "CREATE TABLE IF NOT EXISTS `guan_imagenote` (
	  `note_ID` int(11) NOT NULL AUTO_INCREMENT,
	  `note_img_ID` varchar(15) NOT NULL,
	  `note_comment_ID` int(11) NOT NULL,
	  `note_author` varchar(100) NOT NULL,
	  `note_email` varchar(100) NOT NULL,
	  `note_top` int(11) NOT NULL,
	  `note_left` int(11) NOT NULL,
	  `note_width` int(11) NOT NULL,
	  `note_height` int(11) NOT NULL,
	  `note_text` text NOT NULL,
	  `note_text_ID` varchar(100) NOT NULL,
	  `note_editable` tinyint(1) NOT NULL,
	  `note_date` datetime NOT NULL,
	  PRIMARY KEY (`note_ID`)
	) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=21 ;";

      require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
      dbDelta($sql);
   }
}

function html2txt($text) {
	$search = array ('@<script[^>]*?>.*?</script>@si',
			 '@<[\/\!]*?[^<>]*?>@si',
			 '@([\r\n])[\s]+@',
			 '@&(quot|#34);@i',
			 '@&(lt|#60);@i',
			 '@&(gt|#62);@i',
			 '@&(nbsp|#160);@i',
			 '@&#(\d+);@e');		

	$replace = array ('',
			 '',
			 '\1',
			 '"',
			 '<',
			 '>',
			 ' ',
			 'chr(\1)');

	return trim(preg_replace($search, $replace, $text));
}

?>