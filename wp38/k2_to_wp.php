<?php

$db = JFactory::getDbo();
$query = "
    SELECT *
    FROM `#__k2_items`
    LEFT JOIN `#__k2_categories`
";
$start = 0;
$limit = 50;

do {
    $db->setQuery($query,$start,$limit);
    $rowList = $db->loadAssocList();
    $rowCount = count($rowList);

    foreach ( $rowList as $post ) {

    	switch ($post['published']) {
    		case '1':
    		default:
    		    $post_status = 'publish';
    	}

        $k2Categories = array();

        /*
         * Categories
         */
        $wpTerms = array (
            'term_id' => 0,
            'name' => $k2Categories['name'],
            'slug' => $k2Categories['alias'],
            'term_group' => 0
        );

        $wpTermTaxonomy = array (
            'term_taxonomy_id' => 0,
            'term_id' => $wpTerms['term_id'],
            'taxonomy' => 'category',
            'description' => '',
            'parent', // term_taxonomy_id parent id
            'count' => 0 // posts in cat init as 0. @TODO recalc after insert
        );

        /*
         * This matches up posts and cats
        */
        $wpTermRelationships = array (
            'object_id',
            'term_taxonomy_id' => $wpTermTaxonomy['term_taxonomy_id'],
            'term_order'
        );

        // Save old to move them to new id
        $oldCatId[$k2Categories] = $wpTerms['term_id'];

        $k2Items = array();

        /*
         * post
        */
        $wpPosts = array(
            'ID' => 0,
            'post_author' => $k2Items['created_by'],
            'post_date' => $k2Items['created'],
            'post_date_gmt' => '0000-00-00 00:00:00',
            'post_content' => !emtpty($k2Items['fulltext']) ? $k2Items['fulltext'] : $k2Items['introtext'],
            'post_title' => $k2Items['title'],
            'post_excerpt' => $k2Items['introtext'],
            'post_status' => $post_status,
            'comment_status' => 'open',
            'ping_status' => 'open',
            'post_password' => '',
            'post_name' => $k2Items['alias'],
            'to_ping' => '',
            'pinged' => '',
            'post_modified' => $k2Items['modified'],
            'post_modified_gmt' => '0000-00-00 00:00:00',
            'post_content_filtered' => '',
            'post_parent', // nested parent
            'guid', // full URL of post
            'menu_order' => 0,
            'post_type' => 'post',
            'post_mime_type' => '',
            'comment_count' // count #__k2_comments
        );

        $k2Comments = array();

        $wpComments = array (
        	'comment_ID' => 0,
            'comment_post_ID' => $wpPost['id'],
            'comment_author' => $k2Comments['userName'],
            'comment_author_email' => $k2Comments['commentEmail'],
            'comment_author_url' => '',
            'comment_author_IP' => '',
            'comment_date' => $k2Comments['commentDate'],
            'comment_date_gmt' => '0000-00-00 00:00:00',
            'comment_content' => $k2Comments['commentText'],
            'comment_karma' => 0,
            'comment_approved' => $k2Comments['published'],
            'comment_agent' => '', // Browser
            'comment_type' => '',
            'comment_parent' => 0,
            'user_id' => $k2Comments['userID']
        );

    }

    $start += $limit;
}
while ( $rowCount >= $limit ) ;