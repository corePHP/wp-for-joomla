<?php 

error_reporting(-1);

defined('JPATH_BASE') or die;

jimport( 'joomla.utilities.date' );

require_once JPATH_ADMINISTRATOR . '/components/com_finder/helpers/indexer/adapter.php';

/**
 * Finder adapter for Wordpress.
 *
 * @package     Joomla.Plugin
 * @subpackage  Finder.Wordpress
 * @since       2.5
 */

class PlgFinderWordpress extends FinderIndexerAdapter
{
	protected $context = 'Wordpress';
	
	protected $extension = 'com_wordpress';
	
	protected $layout = 'wordpress_blog';
	
	protected $type_title = 'Wordpress Blog';
	
	protected $table = '#__wp_posts';
		
	protected $autoloadLanguage = true;
	
	public function onFinderAfterDelete($context, $table)
	{
	    
		if ($context == 'com_wordpress.wordpress_blog')
		{
		    $id = $table->id;
		}
		elseif ($context == 'com_finder.index')
		{
		    $id = $table->link_id;
		}
		else
		{
		    return true;
		}

        return $this->remove($id);
    }

    public function onFinderAfterSave($context, $row, $isNew)
	{
		
		if ($context == 'com_wordpress.wordpress_blog')
		{
			
			if (!$isNew && $this->old_access != $row->access)
			{
				$this->itemAccessChange($row);
			}
			
			if(count($row) >0)
			{
				$this->reindex($row->id);
			}
			
			if (!$isNew && $this->old_cataccess != $row->access)
			{
				$this->categoryAccessChange($row);
			}
		}
	
		return true;
    }

    public function onFinderBeforeSave($context, $row, $isNew)
	{
		
		if ($context == 'com_wordpress.wordpress_blog')
		{
		
			if (!$isNew)
			{
				$this->checkItemAccess($row);
			}
		}
	
		return true;
	}

	public function onFinderChangeState($context, $pks, $value)
	{
	
		if ($context == 'com_wordpress.wordpress_blog')
		{
			$this->itemStateChange($pks, $value);
		}

		if ($context == 'com_plugins.plugin' && $value === 0)
		{
			$this->pluginDisable($pks);
		}
	}

	protected function index(FinderIndexerResult $item, $format = 'html')
	{
		if (JComponentHelper::isEnabled($this->extension) == false)
		{
			return;
		}
	
		$item->setLanguage();
		
		$extension = ucfirst(substr($item->extension, 4));

		$item->access = '1';

		$item->state  = '1';

		$item->url = $this->getURL($item->id, 'com_wordpress', $this->layout);

		$item->route = 'index.php?p='.$item->id.'&option=com_wordpress&';

		$item->addTaxonomy('Type', 'Wordpress Blog');

		$item->addTaxonomy('Language', $item->language);

		$this->indexer->index($item);

    }

	protected function setup()
	{
		return true;
	}

	protected function getListQuery($sql = null)
	{
		$db = JFactory::getDbo();

		$date          = & JFactory::getDate();

        $sql = $sql instanceof JDatabaseQuery ? $sql : $db->getQuery(true);

		$sql->select('a.id AS id, a.post_title As title, a.post_content AS summary, a.post_excerpt, a.post_name, a.post_status As state, a.post_type, a.post_password, a.post_date');

		$sql->from('#__wp_posts AS a');

		$sql->where( "(" .$db->quoteName('a.post_status') .   " =  " . $db->quote('publish') . ")" );

		$sql->where("(" .$db->quoteName('a.post_type') .     " = " . $db->quote('post') . " or " . $db->quoteName('a.post_type') .   " = " . $db->quote('revision').") ");
		$sql->where($db->quoteName('a.post_password') . " = " . $db->quote(' ') );
        
		return $sql;
	}
	
	protected function getStateQuery()
	{
		
		$sql = $this->db->getQuery(true);
		
		$sql->select('a.id AS id, a.post_title As title, a.post_content AS summary, a.post_excerpt, a.post_name, a.post_type, a.post_password, a.post_date');

		$sql->select($this->db->quoteName('a.post_status') . ' AS state');

		$sql->from($this->db->quoteName('#__wp_posts') . ' AS a');
	
		return $sql;

	}

	protected function blogAccessChange($row)
	{

		$sql = clone($this->getStateQuery());

		$sql->where('a.id = '.(int)$row->id);

		$this->db->setQuery($sql);

		$items = $this->db->loadObjectList();

		foreach ($items as $item)
		{
			$temp = max($item->access, $row->access);

			$this->change((int)$item->id, 'access', $temp);

			$this->reindex($item->id);
		}
	}
}