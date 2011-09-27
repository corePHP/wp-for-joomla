<?php
/**
* @author Steven Pignataro
* @email support@corephp.com
* @version $Id: com_wordpress.php
* @package Xmap
* @license GNU/GPL
* @description Xmap plugin for WordPress for Joomla! component
*/

defined( '_JEXEC' ) or die( 'Restricted access.' );

class xmap_com_wordpress 
{
	
    function getWordpress( &$xmap, &$parent, &$params )
    {
        $db 	= &JFactory::getDBO();
        $my 	= &JFactory::getUser();
		$date	= &JFactory::getDate();
		$now	= $date->toMySQL();

		// Load WordPress
		require_once( JPATH_ROOT .DS. 'components' .DS. 'com_wordpress' .DS. 'wordpress_loader.php' );
		wpj_loader::load();
			
		// Returns true or false for Multi Site
		$isIt = is_multiSite();
		
		if( $isIt ) 
		{ // WordPress is setup as Multi-Site - lets output the multi-site layout

			// Build the list of blogs in the database and get specific key information
			$query = "SELECT blog_id
						FROM #__wp_blogs
							WHERE public = '1'
								AND archived = '0'
								AND mature = '0'
								AND spam = '0'
								AND deleted = '0'
					";
			$db->setQuery( $query );
			$blogs = $db->loadObjectList();

			$xmap->changeLevel(1);
			
			foreach ( $blogs as $blog ) {
				switch_to_blog( $blog->blog_id ); // Switch to that blog

		        $node 				= new stdclass;
                $node->id 			= $parent->id;
                $node->uid 			= $parent->uid.'msb'.$blog->blog_id;
				$node->name			= get_bloginfo( 'name' );
				$node->link 		= get_bloginfo( 'wpurl' );
                $node->priority 	= $params['blogpost_priority'];
                $node->changefreq 	= $params['blog_changefreq'];
                $node->expandible 	= true;
                $node->modified 	= time();                
                
				// If the $node is not empty - lets get those post and print them out
				if( $xmap->printNode( $node ) !== FALSE ) {
					// If this is set to 0 lets no include it.
					if( $params['include_blogpost'] == 0 ) { continue; }
					
					// Lets set the blog numbers for the query now
					if( $blog->blog_id == 1 ) {
						$blog_number = '_';
					} else {
						$blog_number = '_' . $blog->blog_id . '_';
					}
					
					// We need to query for post
					$query = "SELECT a.post_title AS title, 
								a.post_date AS created,
								a.post_content AS text,
								a.ID
									FROM #__wp" . $blog_number . "posts AS a
									WHERE a.post_status = 'publish'
										AND a.post_type = 'post'
										AND a.post_password = ''
										ORDER BY a.post_date DESC";
					
					$db->setQuery( $query );
					$posts = $db->loadObjectList();					
					
					foreach ( $posts as $post ) {
						$xmap->changeLevel(1);
						
						$node 				= new stdclass;
		                $node->id 			= $parent->id;
		                $node->uid 			= $parent->uid.'msbp'.$post->ID;
						$node->name         = html_entity_decode(get_the_title( $post->ID ),null,'UTF-8');
						$node->link 		= get_permalink($post->ID);
		                $node->priority 	= $params['blogpost_priority'];
		                $node->changefreq 	= $params['blog_changefreq'];
		                $node->expandible 	= false;
		                $node->modified 	= time();                
		                $xmap->printNode($node);
						$xmap->changeLevel(-1);
					}
				}			
				restore_current_blog(); // reset to go to next blog
			}
			
			$xmap->changeLevel(-1);
			
		} else { // WordPress is setup as Single Blog
			/* determine if we should print out any post links or just the root post */
			if ($params['include_blogpost'] ) 
			{			
				$xmap->changeLevel(1);

				$count_posts = wp_count_posts();
				$published_posts = $count_posts->publish;

				$blogpost = get_posts( array( 'numberposts' => $published_posts ) );	

			 	foreach($blogpost as $post) {			
	                $node 				= new stdclass;
	                $node->id 			= $parent->id;
	                $node->uid 			= $parent->uid.'bp'.$post->ID;
					$node->name         = html_entity_decode(get_the_title( $post->ID ),null,'UTF-8');
					$node->link 		= get_permalink($post->ID);
	                $node->priority 	= $params['blogpost_priority'];
	                $node->changefreq 	= $params['blog_changefreq'];
	                $node->expandible 	= false;
	                $node->modified 	= time();                
	                $xmap->printNode($node);
				}			
				$xmap->changeLevel(-1);
			}
		} // End WordPress Multi Site Check

		// Unload WordPress
		wpj_loader::unload();
    }

    // Build the tree
    function getTree( &$xmap, &$parent, &$params   )
    {
        
        $include_blogpost = JArrayHelper::getValue($params,'include_blogpost',1);
        $include_blogpost = ( $include_blogpost == 1
        || ( $include_bloggers == 2 && $xmap->view == 'xml')
        || ( $include_bloggers == 3 && $xmap->view == 'html')
        ||   $xmap->view == 'navigator');
        $params['include_blogpost'] = $include_blogpost;

		 //----- Set tpriority and changefreq params
        $priority = JArrayHelper::getValue($params,'blogpost_priority',$parent->priority);
        $changefreq = JArrayHelper::getValue($params,'blogpost_priority',$parent->changefreq);
        if ($priority  == '-1')
            $priority = $parent->priority;
        if ($changefreq  == '-1')
            $changefreq = $parent->changefreq;

        $params['blogpost_priority'] = $priority;
        $params['blog_changefreq'] = $changefreq;

        xmap_com_wordpress::getWordpress($xmap, $parent, $params);
    }
}