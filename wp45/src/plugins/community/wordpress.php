<?php
// no direct access
defined('_JEXEC') or die('Restricted access');

require_once( JPATH_BASE . DS . 'components' . DS . 'com_community' . DS . 'libraries' . DS . 'core.php');

class plgCommunityWordpress extends CApplications
{

	var $name 		= "Wordpress Application";
	var $_name		= 'wordpress';
	var $_path		= '';
	var $_user		= '';
	var $_my		= '';

    function plgCommunityWordpress( & $subject, $config )
    {
		$this->_user	=& CFactory::getActiveProfile();
		
		parent::__construct( $subject, $config );
    }

	function onProfileDisplay()
	{
		// Load WordPress
		require_once( JPATH_ROOT .DS. 'components' .DS. 'com_wordpress' .DS. 'wordpress_loader.php' );
		wpj_loader::load();

		//Load Language file.
		JPlugin::loadLanguage( 'plg_wpmu_latestpost', JPATH_ADMINISTRATOR );
	
		// Get the document object
		$document = JFactory::getDocument();
		$my       = CFactory::getUser();
		$user     = CFactory::getRequestUser();

		// Test if Wordpress exists
		if( !file_exists( JPATH_ROOT .DS. 'components' .DS. 'com_wordpress' .DS. 'wordpress_loader.php' ) ) {
			$contents = "<div class=\"icon-nopost\">
				            <img src='".JURI::base()."components/com_community/assets/error.gif' alt='' />
				        </div>
				        <div class=\"content-nopost\">"
							.JText::_('Wordpress is not installed').
						"</div>";
		} else {
			// Lets check to see if multi-site is turned on or off. 
			// If it is not turned on - lets not run the render the plugin.
			
			if ( !is_multisite() ) { return; }

			$blog_id = $this->_getEntries();

			if($blog_id){
				$data_exist = 1;
			}else{
				$data_exist = 0;
			}

			$userId = $user->id;
			$userName = $user->getDisplayName();

			$isOwner = ($my->id == $userId ) ? true : false;

			$cache = JFactory::getCache('plgCommunityWordpress');
			$cache->setCaching($this->params->get('cache', 0));
			$callback = array('plgCommunityWordpress', '_getHTML');
			$contents = $cache->call($callback, $data_exist, $blog_id, $userId, $userName, $isOwner, $this->params);
		}

		// Unload WordPress
		wpj_loader::unload();

		return $contents;
	}

	function _getHTML($data_exist, $blog_id, $userId, $userName, $isOwner, $params)
	{
		ob_start();	

		// Load WordPress
		require_once( JPATH_ROOT .DS. 'components' .DS. 'com_wordpress' .DS. 'wordpress_loader.php' );
		wpj_loader::load();

		global $post;
		
		$user      = CFactory::getActiveProfile();

		if($data_exist){
			
			$count = 0;
			JPluginHelper::importPlugin('content');
			$dispatcher	= JDispatcher::getInstance();

			$db		= JFactory::getDBO();
			$name	= get_blog_option( $blog_id, 'blogname' );
			echo '<h1>Blog: '.$name.'</h1>';

			$r = new WP_Query(array('showposts' => $params->get('count'), 'nopaging' => 0, 'post_status' => 'publish', 'caller_get_posts' => 1));

			if ($r->have_posts()) :

				while ($r->have_posts()) : $r->the_post();

			$query = "
					SELECT js.thumb AS thumbnail
						FROM `#__community_users` AS js
								WHERE js.userid = {$post->post_author}
					";

				$db->setQuery($query);
				$img = $db->loadResult();
				
				$a_user     = CFactory::getUser( $user->_userid );
				$avatar_url = $a_user->getThumbAvatar();
				
				?>

				<div class="profile-blog-post">
					<div <?php post_class() ?> id="post-<?php the_ID(); ?>">
						<h2><a href="<?php the_permalink() ?>" rel="bookmark" title="<?php echo JText::_('PLG_WORDPRESS PERMALINK'); ?> <?php the_title_attribute(); ?>"><?php the_title(); ?></a></h2>
						<span class="createdate"><?php twentyten_posted_on(); ?></span>	
						<?php if( $isOwner ): ?>					
							<?php edit_post_link( __( 'Edit', 'twentyten' ), '[ <span class="edit-link">', '</span> ]' ); ?>
						<?php endif; ?>	
						<br styl="clear: both; width: 100%;">		
				
						<div class="profile-blog-container">
							<div style="float:left; padding: 8px;">
								<a href="<?php echo JRoute::_('index.php?option=com_community&view=profile&userid='.$post->post_author); ?>"><img src="<?php echo $avatar_url; ?>" class="avatar" alt="avatar" /></a>
							</div>
							<div class="wp-content" style="padding-top: 8px;">
								<?php the_content('Read the rest of this entry &raquo;'); ?>
							</div>
						</div>
						<br style="clear:both; width: 100%;" />
						
						<div class="profile-blog-metadata">							
							<?php if(the_tags()) : ?>
								Tags: <?php the_tags('<ul><li>','</li><li>','</li></ul>'); ?><br />
							<?php else : ?>
								<br />
							<?php endif; ?>
							
							 <a class="icon-bookmark" onclick="joms.bookmarks.show('<?php the_permalink() ?>');" href="javascript:void(0);"><!--<span>Share this</span>--></a> |  <?php comments_popup_link('No Comments &#187;', '1 Comment &#187;', '% Comments &#187;'); ?>
						</div>
						<div style="clear:both; width: 100%;"></div>
					</div>
				</div>
			<?php endwhile; ?>
		<?php else : ?>
			<h3 class="center">Not Found</h3>
			<p class="center">Sorry, we could not find your request.</p>
			<form method="get" id="searchform" action="<?php echo get_option('home'); ?>/" >
				<p>
					<label for="s" class="accesible">Search:</label>
					<input type="text" value="" name="s" id="s" />
					<button type="submit">Go!</button>
				</p>
			</form>
		<?php endif; ?>
		<div style="float:right">
		<?php 
		if($isOwner)
		{
		?>
			<a href="<?php echo get_blogaddress_by_id( $blog_id ) . 'wp-admin/post-new.php'; ?>" target="_blank"><span><?php echo JText::_("Write New Entry");?></span></a>&nbsp;&nbsp;|&nbsp; 
		<?php
		}
		?>
			<a href="<?php echo network_site_url(); ?>">
				<?php echo JText::_("Show All");?>
			</a>
		</div>
		<div style="clear:both; width: 100%;"></div>
		<?php
		}else{
		?>
		<div class="icon-nopost">
            <img src="<?php echo JURI::base(); ?>components/com_community/assets/error.gif" alt="" />
        </div>
        <div class="content-nopost">
            <?php echo $userName . ' ' . JText::_('currently has no posts.');?>
		</div>
		<div style="clear:both"></div>
		<?php 
		if($isOwner)
		{
		?>
			<div style="text-align: right;">
				<a href="<?php echo get_blogaddress_by_id( $blog_id ) . 'wp-admin/post-new.php'; ?>" target="_blank">
					<?php echo JText::_("Write New Entry");?>
				</a>
			</div>
			<br style="clear:both; width: 100%;" />
		<?php
		}
		?>
		</div>
		<br style="clear:both; width: 100%;" />
		
		<?php
		}

		$content	= ob_get_contents();
		ob_end_clean();	

		// Unload WordPress
		wpj_loader::unload();

		return $content;
	}

	function &_getEntries()
	{		
		$db			= JFactory::getDBO();
		$userId 	= $this->_user->id;

		$order_by 	= $this->params->get('order_by', 'post_date_gmt');
		$order 		= $this->params->get('order', 'DESC');
		$limit 		= $this->params->get('count', 5);

		$query	= 'SELECT meta_value FROM ' . $db->nameQuote('#__wp_usermeta') . ' '
				. ' WHERE user_id=' . $db->Quote($this->_user->id) . ' '
				. " AND meta_key = 'primary_blog' "
				;

		$db->setQuery( $query );
		$blogid	= $db->loadResult();

		// $blogid = JRequest::setVar('blog_id', $blogid);

		return $blogid;
	}
}