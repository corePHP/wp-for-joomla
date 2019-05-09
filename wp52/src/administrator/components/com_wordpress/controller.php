<?php
defined('_JEXEC') or die;

class WordpressController extends JControllerLegacy
{
	public function display($cachable = false, $urlparams = false)
	{

		$mainframe = JFactory::getApplication();
		$user = JFactory::getUser();
		$db = JFactory::getDBO();

		$now = strtotime('now');
		$secret = ($mainframe->getCfg('secret') ? $mainframe->getCfg('secret') : md5($mainframe->getCfg('sitename')));
		$hash = md5($user->username . $secret);

		$query = "REPLACE INTO #__wp_jauthenticate ( `user_id`, `hash`, `timestamp` ) VALUES ( {$user->id}, '{$hash}', {$now} )";
		$db->setQuery($query);
		$db->query();

		// Find path to WordPress folder
		$db = JFactory::getDBO();
		$query = "SELECT option_value FROM #__wp_options WHERE option_name = 'wpj_multisite_path'";
		$db->setQuery($query);
		try
		{
			$wp_path = $db->loadResult();
		}
		catch (EXCEPTION $e)
		{

		}

		// Check to see if we are in multisite or not
		if (!$wp_path)
		{
			$path = 'components/com_wordpress/wp/wp-admin/';
		}
		else
		{
			$path = "{$wp_path}/wp-admin/";
		}
		$mainframe->redirect(JURI::root() . "{$path}?h={$hash}");

	}
}
