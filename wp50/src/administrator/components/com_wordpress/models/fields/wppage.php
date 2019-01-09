<?php

defined('JPATH_BASE') or die();
JFormHelper::loadFieldClass('list');

class JFormFieldwppage extends JFormFieldList
{
	protected $type = 'wppage';

	public function getOptions()
	{
	    $db = JFactory::getDBO();
	    $query = $db->getQuery(true);
	    $query->select('t.ID AS page_id');
	    $query->select('t.post_title');
	    $query->from('#__wp_posts AS t');
	    $query->where("post_type='page'");
	    $db->setQuery($query);
	    $optionList = $db->loadAssocList();

	    foreach ( $optionList as $option ) {
    	    $options[] = JHtml::_( 'select.option', $option['page_id'], $option['post_title'] );
	    }

        return $options;
	}
}