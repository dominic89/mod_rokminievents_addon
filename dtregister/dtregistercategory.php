<?php
/**
 * @version		1.5 March 31, 2011
 * @author		RocketTheme http://www.rockettheme.com
 * @copyright 	Copyright (C) 2007 - 2011 RocketTheme, LLC
 * @license		http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 only
 *
 */
defined('_JEXEC') or die();

/**
 * @package     gantry
 * @subpackage  admin.elements
 */
class JElementDtregisterCategory extends JElement
{
	var	$_name = 'DtregisterCategory';

	function fetchElement($name, $value, &$node, $control_name)
	{

        $db			=& JFactory::getDBO();
        $query = "SELECT categoryId as id, categoryName as name from #__dtregister_categories where published = 1 order by ordering";

        $db->setQuery($query);
		$categories = $db->loadObjectList();

		$options = array();
        $options[] = JHTML::_('select.option', 0, '--');
		foreach ($categories as $option)
		{
			$options[] = JHTML::_('select.option', $option->id, $option->name);
		}
        return JHTML::_('select.genericlist',  $options, ''.$control_name.'['.$name.']', 'class="inputbox"', 'value', 'text', $value, $control_name.$name );
	}
}
