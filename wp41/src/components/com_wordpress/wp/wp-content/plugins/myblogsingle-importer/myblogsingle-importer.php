<?php
// wp_for_joomla - Automatically added globals at beginning of file
global $component_name;
global $importer_started;
global $wpdb;
global $table_prefix;

/*
Plugin Name: myBlog Single Importer
Plugin URI: http://www.corephp.com/
Description: Import posts, comments, categories and tags from the myBlog database. This single blog importer, will only import all of the data to the current blog.
Author: 'corePHP'
Author URI: http://www.corephp.com/
Version: 1.1
Stable tag: 1.1
License: GPL v2 - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*/

if ( !defined('WP_LOAD_IMPORTERS') )
	return;

// Load Importer API
require_once ABSPATH . 'wp-admin/includes/import.php';

if ( !class_exists( 'WP_Importer' ) ) {
	$class_wp_importer = ABSPATH . 'wp-admin/includes/class-wp-importer.php';
	if ( file_exists( $class_wp_importer ) )
		require_once $class_wp_importer;
}

/** Load WordPress Administration Bootstrap */
$parent_file = 'tools.php';
$submenu_file = 'import.php';
$title = 'Import myBlog Single';

/**
 * myBlog Importer
 *
 * @package WordPress
 * @subpackage Importer
 */

/**
 * How many records per GData query
 *
 * @package WordPress
 * @subpackage Blogger_Import
 * @var int
 * @since unknown
 */
define( 'MAX_RESULTS',        50 );

/**
 * How many seconds to let the script run
 *
 * @package WordPress
 * @subpackage Blogger_Import
 * @var int
 * @since unknown
 */
//define( 'MAX_EXECUTION_TIME', 60 );

/**
 * How many seconds between status bar updates
 *
 * @package WordPress
 * @subpackage Blogger_Import
 * @var int
 * @since unknown
 */
//define( 'STATUS_INTERVAL',     3 );

global $component_name;
if ( !$component_name ) {
	$component_name = 'com_wordpress';
}

/**
 * myBlog Importer
 *
 * @package WordPress for Joomla
 * @subpackage Importer
 */
if ( class_exists( 'WP_Importer' ) ) {
class MYBLOG_SINGLE_Import extends WP_Importer {

	// Shows the welcome screen and the magic auth link.
	function greet() {
		global $component_name;

		$next_url = 'index.php?option='.$component_name.'&amp;import=myblog&amp;start=true';
		$title = 'Import myBlog';
		$welcome = 'We are glad you decided to switch your blogging extension to WordPress for Joomla! You won\'t regret it!';
		$prereqs = 'To use this import you must have the data that you want to migrate already in your database, or in other words you must already have myBlog installed. If you are migrating from another site, install this on the other site, do the migration and then do a WordPress export and then do an import on this site.';
		$stepone = 'Just click start and we will take care of everything, just be patient.';
		$startbn = 'Start Importing';
		$password = 'If the post requires special access, what password do you want to give those type of posts.';

		echo "
		<div class='wrap'>
		".screen_icon()."
		<h2>$title</h2>
		<p>$welcome</p><p>$prereqs</p><p>$stepone</p>
			<form action='$next_url' method='get'>
				<input type='hidden' name='option' value='$component_name' />
				<input type='hidden' name='import' value='myblog' />
				<input type='hidden' name='start' value='true' />
				$password<br />
				<input type='text' name='special' value='somepassword' />
				<p class='submit' style='text-align:left;'>
					<input type='submit' class='button' value='$startbn' />
				</p>
			</form>
		</div>\n";
	}

	function uh_oh($title, $message, $info) {
		echo "<div class='wrap'>";
		screen_icon();
		echo "<h2>$title</h2><p>$message</p><pre>$info</pre></div>";
	}

	// Handy function for stopping the script after a number of seconds.
	function have_time() {
		global $importer_started;
		if ( time() - $importer_started > MAX_EXECUTION_TIME )
			die('continue');
		return true;
	}

	function import_blog() {
		global $wpdb;

		$start = 0;

		require_once( JPATH_ADMINISTRATOR.DS.'components'.DS.'com_myblog'.DS.'config.myblog.php' );
		$myBlog = new MYBLOG_Config();
		$sections = $myBlog->get('managedSections');

		$rows = $this->getPosts( $start, $sections );
		$pimported = $cimported = 0;
		while( !empty($rows) ){
			foreach($rows as $row){

				//ensure user exists before insert
				if(!$this->validateUser($row->created_by)) $this->create_new_joomla_user($row->created_by);

				//set all of the variables
				$post_author	= $row->created_by;
				$post_date		= $row->created;
				$text = '';
				if($row->introtext) $text .= $row->introtext;
				if($row->fulltext && $text) $text .= '<!--more-->'.$row->fulltext; else  $text .= $row->fulltext;
				$post_content	= $text;
				$post_title		= $row->title;
				$post_name		= $row->alias;
				$post_status	= ($row->access == 1?'private':'publish');
				if(2 == (int) $row->access) $post_password = JRequest::getVar('special', 'somepass');
				if(strtotime($row->publish_up) > strtotime('now')) $post_status = 'future';
				if($row->state == 0) $post_status = 'draft';
				$post_type		= 'post';

				//prepare
				$postdata = compact('post_author', 'post_date', 'post_content', 'post_title', 'post_name', 'post_status', 'ping_status', 'post_type', 'post_password', 'post_category');
				//$postdata['import_id'] = $row->id; //used if you want the same ids on WP as the imported ones

				//insert
				$comment_post_ID = $post_id = wp_insert_post($postdata);
				if ( is_wp_error( $post_id ) ) continue;

				//Add category
				if(!$post_category = category_exists($row->catname))
					$post_category = wp_create_category($row->catname); //create
				else
					$post_category = $post_category['term_id']; //set
				wp_set_post_categories($post_id, array($post_category));

				//Add the tags
				$tags = $this->myBlogTags($row->id);
				if (count($tags) > 0) {
					$post_tags = array();
					foreach ($tags as $tag) {
						$tag = $tag->name;
						if ( '' == $tag )
							continue;
						$slug = sanitize_term_field('slug', $tag, 0, 'post_tag', 'db');
						$tag_obj = get_term_by('slug', $slug, 'post_tag');
						$tag_id = 0;
						if ( ! empty($tag_obj) )
							$tag_id = $tag_obj->term_id;
						if ( $tag_id == 0 ) {
							$tag = $wpdb->escape($tag);
							$tag_id = wp_insert_term($tag, 'post_tag');
							if ( is_wp_error($tag_id) )
								continue;
							$tag_id = $tag_id['term_id'];
						}
						$post_tags[] = intval($tag_id);
					}
					wp_set_post_tags($post_id, $post_tags);
				}

				//Add comments - Make sure jomcomment is installed
				if (file_exists(JPATH_ROOT.'/components/com_jomcomment/jomcomment.php')){
					$comments 	= $this->getComments($row->id);
					if ( count($comments) > 0) { 
						foreach ($comments as $comment) {
							$comment_author       = $comment->name;
							$comment_author_email = $comment->email;
							$comment_author_IP    = $comment->ip;
							$comment_author_url   = $comment->website;
							$comment_date         = $comment->date;
							$comment_content      = $comment->comment;
							$comment_approved     = $comment->published;
							$user_id			  = $comment->user_id;
							//$comment_type         = '';
							//$comment_parent       = $comment;

							//prepare
							$commentdata = compact('comment_post_ID', 'comment_author', 'comment_author_url', 'comment_author_email', 'comment_author_IP', 'comment_date', 'comment_content', 'comment_approved', 'user_id');
							//insert
							wp_insert_comment($commentdata);
							//clean
							unset($comment_author, $comment_author_email, $comment_author_IP, $comment_author_url, $comment_date, $comment_content, $comment_approved, $user_id, $commentdata);
							$cimported++;
						}
					}
				}

				//Add meta for reference
				$this->process_post_meta($post_id, 'import_id', $row->id);

				$row->introtext = '';$row->fulltext = '';

				//Clean
				unset($post_author, $post_date, $post_content, $post_title, $post_name, $post_status, $ping_status, $post_type, $post_password, $post_id, $comment_post_ID, $postdata, $post_category, $post_tags);
				$pimported++;
			}
			$start = $start + MAX_RESULTS;
			$rows = $this->getPosts( $start, $sections );
		}
		return array($pimported, $cimported);
	}

	/**
	 * Get a list of posts
	 * @return Object
	 * @param $start String Limit
	 * @param $sections Sections to select the content from
	 */
	function getPosts( $start, $sections ){
		$query = "SELECT a.*, c.title AS catname
					FROM #__content AS a
						LEFT JOIN #__categories AS c ON c.id = a.catid
							WHERE a.state=0 OR a.state=1 AND
								a.sectionid IN ($sections) 
									ORDER BY a.created ASC,a.id ASC 
										LIMIT $start,".MAX_RESULTS;
		$this->db->setQuery($query);
		$rows = $this->db->loadObjectList();
		return $rows;
	}

	/**
	 *	Get list of tags for a given piece content
	 */ 
	function myBlogTags($contentid) {
		$query = "SELECT b.id,b.name, b.slug FROM #__myblog_content_categories as a,#__myblog_categories as b WHERE b.id=a.category AND a.contentid='$contentid' ORDER BY b.name DESC";
		$this->db->setQuery($query);
		$rows = $this->db->loadObjectList();

		return $rows;
	}

	/**
	 * Return array of comments	for the given contentid and option
	 * @return Object
	 * @param $cid Int Content id
	 * @param $option String
	 * @param $count Int
	 * @param $page Int
	 */
	function getComments($cid) {

		$orderBy = ' ORDER BY date ASC, id ASC ';

	    $strSQL = "SELECT a.*, b.created_by FROM #__jomcomment AS a, #__content AS b"
	            . "\n WHERE a.`contentid`='$cid'"
	            . "\n AND(a.`option`='com_content' OR a.`option`='com_myblog')"
	            . "\n AND b.`id`=a.`contentid` "
	            . $orderBy;
		$this->db->setQuery($strSQL);
		$result = $this->db->loadObjectList();
		$refArray = array();
		for($i =0; $i < count($result); $i++){
			$refArray[$result[$i]->id] = $i;
		}

		for($i =0; $i < count($result); $i++){
			$row =& $result[$i];

 			if($row->parentid != 0){
 				// search for parent array index
 				$parentIndex = $refArray[$row->parentid];

 				// unset the rows from the parent rows since this is a child
 				if(!isset($result[$parentIndex]))
 					$result[$parentIndex]->child = array();
	 				
 				$result[$parentIndex]->child[] = $row;
 			}
		}

		$finalResult = array();
		for($i =0; $i < count($result); $i++){
			if($result[$i]->parentid != 0){
				//unset($result[$i]);
			} else{
				$finalResult[] = $result[$i]; 
			}
		}

		return $finalResult;
	}

	/**
	 * Add meta data
	 */
	function process_post_meta($post_id, $key, $value) {
		// the filter can return false to skip a particular metadata key
		$_key = apply_filters('import_post_meta_key', $key);
		if ( $_key ) {
			add_post_meta( $post_id, $_key, $value );
			do_action('import_post_meta', $post_id, $_key, $value);
		}
	}

	/**
	 * Ensures a user exists in WP database
	 * if it doesn't it creates it
	 * @return 
	 * @param $id Object
	 */
	function validateUser( $id ){
		static $ids = array();
		if(array_key_exists($id, $ids)) return $ids[$id];
		$query = "SELECT user_pass
					FROM {$table_prefix}users
						WHERE ID = ".intval($id);
		$this->db->setQuery($query);
		$user = $this->db->loadResult();
		if(!$user){
			$ids[$id] = false;
			return false;
		}
		$ids[$id] = true;
		return true;
	}


	/**
	 * Created new WP user through the data in joomla's db
	 * @return bool If user was created true
	 * @param userid to create
	 */
	function create_new_joomla_user($user_id) {
		global $table_prefix;
	
		$juser		=&	JUser::getInstance($user_id);
		$db			=& JFactory::getDBO();
		$userlevel	= true;

		list($firstname, $lastname) = explode(' ', $juser->name, 2);
		if($juser->gid >= 24){
			$wp_capabilities = 'a:1:{s:13:"administrator";b:1;}';
			$user_level = 10;
		}else{
			$default_role		= get_option('default_role');
			$wp_capabilities	= serialize(array($default_role => true));
			switch($default_role){
				case 'administrator':
					$user_level = 10;
					break;
				case 'editor':
					$user_level = 7;
					break;
				case 'author':
					$user_level = 2;
					break;
				case 'contributor':
					$user_level = 1;
					break;
				case 'subscriber':
					$user_level = 0;
					$userlevel = false;
					break;
			}
		}
		
		$query = "INSERT INTO ".$table_prefix."users 
					(ID, user_login, user_pass, user_nicename, user_email, user_url, user_registered, user_activation_key, user_status, display_name)
						VALUES
							('".$juser->id."', '".$juser->username."', '".$juser->password."', '".$juser->username."', '".$juser->email."', 'http://', '".$juser->registerDate."', '', '0', '".$juser->username."')";
		$db->setQuery($query);
		$db->query();
		if($db->getErrorNum())
			return false;
		
		$query = "INSERT INTO ".$table_prefix."usermeta 
					( user_id, meta_key, meta_value )
						VALUES
							( '".$juser->id."', 'first_name', '".$firstname."'),
							( '".$juser->id."', 'last_name', '".$lastname."'),
							( '".$juser->id."', 'nickname', '".$juser->username."'),
							( '".$juser->id."', 'rich_editing', 'true'),
							( '".$juser->id."', 'comment_shortcuts', 'false'),
							( '".$juser->id."', 'admin_color', 'fresh'),
							( '".$juser->id."', '".$table_prefix."capabilities', '".$wp_capabilities."')";
							if($juser->gid >= 24 || $userlevel)
								$query .= ", ( '".$juser->id."', '".$table_prefix."user_level', '".$user_level."')";
		$db->setQuery($query);
		$db->query();
		if($db->getErrorNum())
			return false;
		
		return true;
	}

	// Congratulate the user for SWITCHING! w00t w00t s...
	function congrats($result) {
		global $component_name;

		echo '<h1>'.'Congratulations!'.'</h1><p>'.'Now that you have imported from myBlog into WordPress, what are you going to do?'.'</p><ul><li>'.'That was hard work! Take a break.'.'</li>';
		if ( $result[0] > 0 )
			echo '<li>'.sprintf('Go to <a href="%s" target="%s">Authors &amp; Users</a>, where you can modify the new user(s) or delete them. If you want to make all of the imported posts yours, you will be given that option when you delete the new authors.', 'index.php?option='.$component_name.'&task=users.php', '_parent').'</li>';
		echo '<li><strong>'.sprintf('You just imported %s posts and %s comments!', $result[0], $result[1]).'<br /></strong></li>';
		echo '</ul>';
	}

	// Figures out what to do, then does it.
	function start() {
		global $component_name;

		if ( isset( $_REQUEST['start'] ) ) {
			$result = $this->import_blog();
			if ( is_wp_error( $result ) )
				echo $result->get_error_message();
			$this->congrats($result);
		}else
			$this->greet();
	}

	function MYBLOG_SINGLE_Import() {
		global $importer_started;
		$importer_started = time();
		$this->db		=& JFactory::getDBO();
	}
}

$myblogsingle_importer = new MYBLOG_SINGLE_Import();

register_importer('myblog', 'myBlog Single', 'Import posts, comments, categories and tags from the myBlog database. This single blog importer, will only import all of the data to the current blog.', array(&$myblogsingle_importer, 'start'));

} // class_exists( 'WP_Importer' )

?>