<?php

defined('JPATH_BASE') or die();
JFormHelper::loadFieldClass('list');

class JFormFieldWPTag extends JFormFieldList
{
	protected $type = 'wptag';

	public function getOptions()
	{
	    $db = JFactory::getDBO();
	    $query = $db->getQuery(true);
	    $query->select('t.slug');
	    $query->select('t.name');
	    $query->from('#__wp_term_taxonomy AS tt');
	    $query->leftJoin('#__wp_terms AS t ON tt.term_id=t.term_id');
	    $query->where("taxonomy='post_tag'");
	    $db->setQuery($query);
	    $optionList = $db->loadAssocList();

	    foreach ( $optionList as $option ) {
    	    $options[] = JHtml::_( 'select.option', $option['slug'], $option['name'] );
	    }

        return $options;
	}
}