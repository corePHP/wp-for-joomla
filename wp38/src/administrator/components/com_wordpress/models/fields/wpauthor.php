<?php

defined('JPATH_BASE') or die();
JFormHelper::loadFieldClass('list');

class JFormFieldWPAuthor extends JFormFieldList
{
	protected $type = 'wpauthor';

	public function getOptions()
	{
	    $db = JFactory::getDBO();
	    $query = $db->getQuery(true);
	    $query->select('u.user_login');
	    $query->select('u.user_nicename');
	    $query->from('#__wp_posts as p');
	    $query->leftJoin('#__wp_users AS u ON p.post_author=u.ID');
	    $query->group("u.ID");
	    $db->setQuery($query);
	    $optionList = $db->loadAssocList();

	    foreach ( $optionList as $option ) {
    	    $options[] = JHtml::_( 'select.option', $option['user_login'], $option['user_nickname'] );
	    }

        return $options;
	}
}