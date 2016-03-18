<?php
/**
* @version		$Id: details.php 1 2009-02-15 03:35:53E rafael $
* @package		Joomla
* @copyright	Copyright (C) 2005 - 2008 Open Source Matters. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
* Joomla! is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See COPYRIGHT.php for copyright notices and details.
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

jimport('joomla.form.formfield');

class JFormFieldCategories extends JFormField
{
	/**
	 * The form field type.
	 *
	 * @var		string
	 * @since	1.6
	 */
	protected $type = 'Categories';

	/**
	 * Method to get the field input markup.
	 *
	 * @return	string	The field input markup.
	 * @since	1.6
	 */
	protected function getInput()
	{
		require_once( JPATH_ROOT . '/components/com_wordpress/wordpress_loader.php' );
		wpj_loader::load();
		require_once( JPATH_ROOT . '/modules/mod_wordpress_latest/helper.php' );

		$cats = $this->wp_dropdown_categories( array(
			'show_option_all' => 'All Categories',
			'hide_empty' => 0,
			'echo' => 0,
			'hierarchical' => 1,
			'name' => $this->name.'[]',
			'selected' => (array) $this->value
		) );

		wpj_loader::unload();

		return $cats;
	}

	function wp_dropdown_categories( $args = '' )
	{
		$defaults = array(
			'show_option_all' => '', 'show_option_none' => '',
			'orderby' => 'ID', 'order' => 'ASC',
			'show_last_update' => 0, 'show_count' => 0,
			'hide_empty' => 1, 'child_of' => 0,
			'exclude' => '', 'echo' => 1,
			'selected' => 0, 'hierarchical' => 0,
			'name' => 'cat', 'class' => 'postform',
			'depth' => 0, 'tab_index' => 0
		);

		$defaults['selected'] = ( is_category() ) ? get_query_var( 'cat' ) : 0;

		$r = wp_parse_args( $args, $defaults );
		$r['include_last_update_time'] = $r['show_last_update'];
		extract( $r );

		$tab_index_attribute = '';
		if ( (int) $tab_index > 0 )
			$tab_index_attribute = " tabindex=\"$tab_index\"";

		$categories = get_categories( );
		preg_match_all('/(\w*)/', $name, $id);
		$id = implode('_', array_filter($id[0]));

		$output = '';
		if ( ! empty( $categories ) ) {
		    $output = '<select name="'.$name.'" id="'.$id.'" class="'.$class.'" '.$tab_index_attribute.' multiple="multiple" size="7">'."\n";

			if ( $show_option_all ) {
				$show_option_all = apply_filters( 'list_cats', $show_option_all );
				$selected = ( in_array(0, $r['selected']) ) ? " selected='selected'" : '';
				$output .= "\t<option value='0'$selected>$show_option_all</option>\n";
			}

			if ( $show_option_none ) {
				$show_option_none = apply_filters( 'list_cats', $show_option_none );
				$selected = ( in_array(-1, $r['selected']) ) ? " selected='selected'" : '';
				$output .= "\t<option value='-1'$selected>$show_option_none</option>\n";
			}

			if ( $hierarchical )
				$depth = $r['depth'];  // Walk the full depth.
			else
				$depth = -1; // Flat.

			$walker = new Walker_CategoryDropdownElement;
			$output .= call_user_func_array( array( &$walker, 'walk' ),
				array( $categories, $depth, $r ) );
			$output .= "</select>\n";
		}

		return $output;
	}
}