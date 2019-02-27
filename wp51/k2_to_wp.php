<?php

$db = JFactory::getDbo();
$query = "
    SELECT *
    FROM `#__k2_items` AS i
    LEFT JOIN `#__k2_categories` AS c ON i.catid=c.id
";
$start = 0;
$limit = 50;

do {
    $db->setQuery($query, $start, $limit);
    $k2ItemList = $db->loadAssocList();
    $rowCount = count($rowList);
    $start += $limit;

    foreach ( $k2ItemList as $k2Item ) {

        $queryComment = "SELECT * FROM `#__k2_comments` WHERE itemID='{$k2Item['id']}'";
        $db->setQuery($queryComment);
        $commentList = $db->loadAssocList();
        $commentCount = (int)count($commentList);

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
            'parent' => 0, // term_taxonomy_id parent id
            'count' => 0 // posts in cat init as 0. @TODO recalc after insert
        );

        /*
         * This matches up posts and cats
        */
        $wpTermRelationships = array (
            'object_id' => 0,
            'term_taxonomy_id' => $wpTermTaxonomy['term_taxonomy_id'],
            'term_order' => 0
        );

        // Save old to move them to new id
        $oldCatId[$k2Categories] = $wpTerms['term_id'];

        /*
         * post
        */
        $wpPosts = array(
            'ID' => 0,
            'post_author' => $k2Item['created_by'],
            'post_date' => $k2Item['created'],
            'post_date_gmt' => '0000-00-00 00:00:00',
            'post_content' => !emtpty($k2Item['fulltext']) ? $k2Item['fulltext'] : $k2Item['introtext'],
            'post_title' => $k2Item['title'],
            'post_excerpt' => $k2Item['introtext'],
            'post_status' => $post_status,
            'comment_status' => 'open',
            'ping_status' => 'open',
            'post_password' => '',
            'post_name' => $k2Item['alias'],
            'to_ping' => '',
            'pinged' => '',
            'post_modified' => $k2Item['modified'],
            'post_modified_gmt' => '0000-00-00 00:00:00',
            'post_content_filtered' => '',
            'post_parent' => 0, // nested parent
            'guid' => '', // full URL of post
            'menu_order' => 0,
            'post_type' => 'post',
            'post_mime_type' => '',
            'comment_count' => $commentCount
        );



        foreach ($commentList as $comment) {
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

    }

} while ( $rowCount >= $limit ) ;