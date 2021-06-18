<?php
defined('_JEXEC') or die();

class MenusTableMenu extends JTableMenu
{

    public function check ()
    {
        // Set correct component id to ensure proper 404 messages with separator
        // items
        if ($this->type == "separator") {
            $this->component_id = 0;
        }

        // If the alias field is empty, set it to the title.
        $this->alias = trim($this->alias);

        if ((empty($this->alias)) &&
                 ($this->type != 'alias' && $this->type != 'url')) {
            $this->alias = $this->title;
        }

        // Make the alias URL safe.
        $this->alias = JApplication::stringURLSafe($this->alias);

        if (trim(str_replace('-', '', $this->alias)) == '') {
            $this->alias = JFactory::getDate()->format('Y-m-d-H-i-s');
        }

        // Cast the home property to an int for checking.
        $this->home = (int) $this->home;

        // Verify that a first level menu item alias is not 'component'.
        if ($this->parent_id == 1 && $this->alias == 'component') {
            $this->setError(
                    JText::_('JLIB_DATABASE_ERROR_MENU_ROOT_ALIAS_COMPONENT'));

            return false;
        }

        // Verify that a first level menu item alias is not the name of a
        // folder.
        jimport('joomla.filesystem.folder');

        /* Must have this for multisite */
        /* if ($this->parent_id == 1 &&
                 in_array($this->alias, JFolder::folders(JPATH_ROOT))) {
            $this->setError(
                    JText::sprintf('JLIB_DATABASE_ERROR_MENU_ROOT_ALIAS_FOLDER',
                            $this->alias, $this->alias));

            return false;
        } */

        // Verify that the home item a component.
        if ($this->home && $this->type != 'component') {
            $this->setError(
                    JText::_('JLIB_DATABASE_ERROR_MENU_HOME_NOT_COMPONENT'));

            return false;
        }

        return true;
    }

    public function delete ($pk = null, $children = false)
    {
        return parent::delete($pk, $children);
    }
}