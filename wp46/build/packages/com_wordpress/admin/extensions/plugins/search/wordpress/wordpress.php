<?php
/**
 * @package		WordPress for Joomla!
 * @copyright	Copyright (C) 2010 'corePHP'. All rights reserved.
 * @license		GNU/GPL 2.0
 */

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

class plgSearchWordPress extends JPlugin
{
	/**
	 * Constructor
	 *
	 * @access      protected
	 * @param       object  $subject The object to observe
	 * @param       array   $config  An array that holds the plugin configuration
	 * @since       1.5
	 */
	public function __construct(& $subject, $config)
	{
		parent::__construct($subject, $config);
		$this->loadLanguage();
	}

	/**
	* @return array An array of search areas
	*/
	function onContentSearchAreas()
	{
		static $areas = array(
			'wordpress' => 'PLG_SEARCH_WORDPRESS'
		);
		return $areas;
	}

	/**
	* Contacts Search method
	*
	* The sql must return the following fields that are used in a common display
	* routine: href, title, section, created, text, browsernav
	* @param string Target search string
	* @param string mathcing option, exact|any|all
	* @param string ordering option, newest|oldest|popular|alpha|category
	 */
	function onContentSearch( $text, $phrase = '', $ordering = '', $areas = null )
	{
		global $mainframe;

		$db		= JFactory::getDBO();
		$user	= JFactory::getUser();

		// Load WordPress
		require_once( JPATH_ROOT .DS. 'components' .DS. 'com_wordpress' .DS. 'wordpress_loader.php' );
		wpj_loader::load();

		$searchText = $text;

		// load plugin params info

		// Lets grab our Params
		$sBlogs        = $this->params->get( 'search_blogs', 1 );
		$sPages        = $this->params->get( 'search_pages', 0 );
		$limit         = $this->params->def( 'search_limit', 50 );
	
		// Lets pull some date functions - we may or may not use these
		$nullDate      = $db->getNullDate();
		$date          = & JFactory::getDate();
		$now           = $date->toSQL();

		$text = trim( $text );
		if ($text == '') {
			return array();
		}

		// Lets create the where statements.
		$wheres = array();
		switch ( $phrase ) 
		{
			case 'exact':
				$text		= $db->Quote( '%'.$db->escape( $text, true ).'%', false );
				$wheres2 	= array();
				$wheres2[] 	= 'a.post_title LIKE '.$text;
				$wheres2[] 	= 'a.post_content LIKE '.$text;
				$wheres2[] 	= 'a.post_excerpt LIKE '.$text;
				$wheres2[] 	= 'a.post_name LIKE '.$text;
				$where 		= '(' . implode( ') OR (', $wheres2 ) . ')';
				break;

			case 'all':
			case 'any':
			default:
				$words = explode( ' ', $text );
				$wheres = array();
				foreach ( $words as $word ) 
				{
					$word		= $db->Quote( '%'.$db->escape( $word, true ).'%', false );
					$wheres2 	= array();
					$wheres2[] 	= 'a.post_title LIKE '.$word;
					$wheres2[] 	= 'a.post_content LIKE '.$word;
					$wheres2[] 	= 'a.post_excerpt LIKE '.$word;
					$wheres2[] 	= 'a.post_name LIKE '.$word;
					$wheres[] 	= implode( ' OR ', $wheres2 );
				}
				$where = '(' . implode( ($phrase == 'all' ? ') AND (' : ') OR ('), $wheres ) . ')';
				break;
		}

		$morder = '';
		switch ( $ordering ) 
		{
			case 'oldest':
				$order = 'a.post_date ASC';
				break;

			case 'alpha':
				$order = 'a.post_title ASC';
				break;

			case 'popular':
			case 'category':
			case 'newest':
				default:
				$order = 'a.post_date DESC';
				break;
		}

		$rows = array();

		// search blogs
		if ( $sBlogs && $limit > 0 )
		{
			// Returns true or false for Muli Site
			$isIt = is_multiSite();	
		
			if($isIt) { // It is Multi Site - lets build our query
				// Lets find all our blogs first
				$query = "SELECT blog_id 
							FROM #__wp_blogs
								WHERE public = '1'
									AND archived = '0'
									AND mature = '0'
									AND spam = '0'
									AND deleted = '0'
									AND blog_id != 1";
				$db->setQuery( $query );
				$blogs = $db->loadObjectList();

				// We have found all the blogs now - lets first look at blog_id = 1 and move on
				$query = " ( SELECT a.post_title AS title, 
							a.post_date AS created,
							a.post_content AS text,
							a.ID,
							1 AS blog_id,
							0 AS browsernav,
							0 AS section
								FROM #__wp_posts AS a
								WHERE ( " . $where . " )
									AND a.post_status = 'publish'
									AND a.post_type = 'post'
									AND a.post_password = ''
									AND a.post_date <= " . $db->Quote($now) . " 
									ORDER BY " . $order . " ) ";
				
				// Lets now do a union with all the other blog IDs			
				foreach( $blogs AS $blog ) {
					$query .= " UNION ( SELECT a.post_title AS title, 
								a.post_date AS created,
								a.post_content AS text,
								a.ID,
								" . $blog->blog_id . " AS blog_id,
								0 AS browsernav,
								0 AS section
									FROM #__wp_" . $blog->blog_id . "_posts AS a
									WHERE ( " . $where . " )
										AND a.post_status = 'publish'
										AND a.post_type = 'post'
										AND a.post_password = ''
										AND a.post_date <= " . $db->Quote($now) . " 
										ORDER BY " . $order . " ) ";											
				}				
				$db->setQuery( $query, 0, $limit );
				$list = $db->loadObjectList();		
				$limit -= count( $list );		
			} else {
				$query = "SELECT a.post_title AS title, 
							a.post_date AS created,
							a.post_content AS text,
							a.ID,
							0 AS browsernav,
							0 AS section
								FROM #__wp_posts AS a
								WHERE ( " . $where . " )
									AND a.post_status = 'publish'
									AND a.post_type = 'post'
									AND a.post_password = ''
									AND a.post_date <= " . $db->Quote($now) . " 
									ORDER BY " . $order;
				$db->setQuery( $query, 0, $limit );
				$list = $db->loadObjectList();
				$limit -= count( $list );
			} // end else
		
			if ( isset( $list ) ) {
				foreach ( $list as $key => $item ) {
					if($isIt) {
						$list[$key]->href = get_blog_permalink( $item->blog_id, $item->ID );
					} else {
						$list[$key]->href = get_permalink( $item->ID );
					}
					$list[$key]->title = html_entity_decode($list[$key]->title,ENT_QUOTES,'UTF-8');			
				}
			}
			$rows[] = $list;
		}

		$results = array();
		if( count( $rows ) )
		{
			foreach( $rows as $row )
			{
				$new_row = array();
				foreach( $row AS $key => $article ) {
					if( searchHelper::checkNoHTML( $article, $searchText, array( 'text', 'title', 'metadesc', 'metakey' ) ) ) {
						$new_row[] = $article;
					}
				}
				$results = array_merge( $results, ( array ) $new_row );
			}
		}

		// Unload WordPress
		wpj_loader::unload();

		return $results;
	}
}