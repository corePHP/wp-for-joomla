<?php
// no direct access
defined('_JEXEC') or die('Restricted access');

require_once( JPATH_BASE . DS . 'components' . DS . 'com_community' . DS . 'libraries' . DS . 'core.php' );

if( !class_exists( 'plgCommunityJswordpress' ) 	)
{
	class plgCommunityJswordpress extends CApplications
	{

		var $name       = 'WordPress Blogs';
		var $_name		= 'jswordpress';

	    function plgCommunityWordpress( &$subject, $config )
	    {
			parent::__construct( $subject, $config );
	    }

		function onProfileDisplay()
		{
			// Load WordPress
			require_once( JPATH_ROOT .DS. 'components' .DS. 'com_wordpress' .DS. 'wordpress_loader.php' );
			wpj_loader::load();

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

				$my		= CFactory::getUser();
				$user	= CFactory::getRequestUser();

				$userId = $user->id;
				$userName = $user->getDisplayName();

				$isOwner = ($my->id == $userId ) ? true : false;

				$cache    =& JFactory::getCache( 'plgCommunityJswordpress' );
				$cache->setCaching($this->params->get('cache', 0));
				$contents = $cache->call( array( 'plgCommunityJswordpress', '_getHTML' ),
										$userId, 
										$userName, 
										$isOwner, 
										$this->params
										);
			}

			// Unload WordPress
			wpj_loader::unload();

			return $contents;
		}

		function _getHTML( $userId, $userName, $isOwner, $params )
		{
			global $post;

			$db	       = JFactory::getDBO();
			$user      = CFactory::getActiveProfile();
			$user_info = get_userdata($user->_userid);			

			ob_start();	

			// Load WordPress
			require_once( JPATH_ROOT .DS. 'components' .DS. 'com_wordpress' .DS. 'wordpress_loader.php' );
			wpj_loader::load();
			
			switch_to_blog( $user_info->primary_blog );
			
			query_posts( array( 'showposts' => $params->get( 'count' ),
								'nopaging' => 0,
								'post_status' => 'publish',
								'caller_get_posts' => 1,
								'author' => $user->_userid
								) );

			if ( have_posts() ) :
				while ( have_posts() ) : the_post();

					$a_user     = CFactory::getUser( $user->_userid );
					$avatar_url = $a_user->getThumbAvatar();	
					?>

					<div class="profile-blog-post">
						<div <?php post_class() ?> id="post-<?php the_ID(); ?>">
							<h3><a href="<?php the_permalink() ?>" rel="bookmark" title="<?php echo JText::_('Permalink'); ?> <?php the_title_attribute(); ?>"><?php the_title(); ?></a></h3>

							<span class="createdate"><?php twentyten_posted_on(); ?></span>	
							<?php if( $isOwner ) : ?>					
								<?php edit_post_link( __( 'Edit', 'twentyten' ), '[ <span class="edit-link">', '</span> ]' ); ?>
							<?php endif; ?>	
							<br style="clear: both; width: 100%;">		

							<div class="profile-blog-container">
								<div style="float:left; padding: 8px;">
									<a href="<?php echo JRoute::_( 'index.php?option=com_community&view=profile&userid=' .$user->_userid ); ?>">
										<img src="<?php echo $avatar_url; ?>" class="avatar" alt="avatar" />
									</a>
								</div>
								<div class="wp-content" style="padding-top: 8px;">
									<?php the_content('Read the rest of this entry &raquo;'); ?>
								</div>
							</div>
							<br style="clear:both; width: 100%;" />

							<div class="profile-blog-metadata">							
								<?php if( the_tags() ) : ?>
									Tags: <?php the_tags('<ul><li>','</li><li>','</li></ul>'); ?><br />
								<?php else : ?>
									<br />
								<?php endif; ?>

								<a class="icon-bookmark" onclick="joms.bookmarks.show('<?php the_permalink() ?>');" href="javascript:void(0);"></a> | <?php comments_popup_link( 'No Comments &#187;', '1 Comment &#187;', '% Comments &#187;' ); ?>
							</div>
							<div style="clear:both; width: 100%;"></div>
						</div>
					</div>
					<div style="clear:both; width: 100%;"></div>
				<?php endwhile; ?>
			<?php else : ?>
				<div class="icon-nopost">
		            <img src="<?php echo JURI::base(); ?>components/com_community/assets/error.gif" alt="" />
		        </div>
		        <div class="content-nopost">
		            <?php echo $userName . ' ' . JText::_('currently has no posts.');?>
				</div>
				<div style="clear:both"></div>
			
				<form method="get" id="searchform" action="<?php echo get_option('home'); ?>/" >
					<p>
						<label for="s" class="accesible">Search:</label>
						<input type="text" value="" name="s" id="s" />
						<button type="submit">Go!</button>
					</p>
				</form>			
			<?php endif; ?>
			
			<div style="float:right">
				<?php if( $isOwner ) : ?>
					<?php if( !$user_info->primary_blog ) : ?>
						<a href="<?php echo site_url( 'wp-signup.php' ); ?>">
							<?php echo JText::_('WP_CREATE_NEW_BLOG'); ?>
						</a>&nbsp;|&nbsp;
					<?php else : ?>
						<a href="<?php echo get_blogaddress_by_id( $user_info->primary_blog ) . 'wp-admin/post-new.php'; ?>" target="_blank">
							<span><?php echo JText::_("Write New Entry");?></span>
						</a>&nbsp;|&nbsp;
					<?php endif; ?>
				<?php endif; ?>
				<a href="<?php echo network_site_url(); ?>">
					<?php echo JText::_("Show All");?>
				</a>
			</div>
			<div style="clear:both; width: 100%;"></div>

			<?php		
			$content	= ob_get_contents();
			ob_end_clean();	

			// Unload WordPress
			wpj_loader::unload();

			return $content;
		}
	}
}