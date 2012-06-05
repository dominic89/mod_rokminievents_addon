<?php
/**
 * @version   1.5 March 31, 2011
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2011 RocketTheme, LLC
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 only
 * @modified for Seminar: Dominic Richter
 */

defined('_JEXEC') or die('Restricted access');

class RokMiniEventsSourceSeminar extends RokMiniEvents_SourceBase
{
    function getEvents(&$params)
    {
        // Reuse existing language file from JomSocial
        $language = JFactory::getLanguage();
        $language->load('com_seminar', JPATH_ROOT);


        $query_start_date = null;
        $query_end_date = null;

        if ($params->get('time_range') == 'time_span' || $params->get('rangespan') != 'all_events')
        {
            $query_start_date = $params->get('startmin');
            $startMax = $params->get('startmax', false);
            if ($startMax !== false)
            {
                $query_end_date = $startMax;
            }
        }


        $mainframe =& JFactory::getApplication();

        $db =& JFactory::getDBO();
        $user =& JFactory::getUser();
        $user_gid = (int)$user->get('aid');

        $catid = trim($params->get('seminar_category', 0));

        $categories = '';
        if ($catid != 0)
        {
            $categories = ' AND catid = ' . $catid;
        }

        $dates_start='';
        if (!empty($query_start_date)){
            $dates_start = ' AND a.begin >= ' . $db->Quote($query_start_date);
        }
        $dates_end ='';
        if (!empty($query_end_date)){
            $dates_end = ' AND a.end <= ' . $db->Quote($query_end_date);
        }
/* SQL Jetzt = NOW( )
		aus seminar modul		
  		switch ($params->get('seminar_past',0)) {
		case 0:
			$where[] = "$showend > '$neudatum'";
			break;
		case 1:
			$where[] = "$showend <= '$neudatum'";
			break;
		}
*/
		
		$query = 'SELECT a.*,'
	            . ' a.id as slug'
				. ' FROM #__seminar AS a'
				. ' WHERE a.published =1'
                . $categories
                . $dates_start
                . $dates_end
				. ' ORDER BY a.BEGIN ASC';

        $db->setQuery($query);
        $rows = $db->loadObjectList();


        $total_count = 1;
        $total_max = $params->get('seminar_total',10);
        $events = array();
        foreach ($rows as $row)
        {
            if ($params->get('seminar_links') != 'link_no')
            {

                $link = array(
                    'internal' => ($params->get('seminar_links') == 'link_internal') ? true : false,
                    'link' => JRoute::_(self::getRoute($row->slug))
                );
            } else
            {
                $link = false;
            }

            $offset = 0;
            if ($params->get('seminar_dates_format', 'utc') == 'joomla'){
                $conf =& JFactory::getConfig();
                $timezone = $conf->getValue('config.offset') ;
                $offset = $timezone * 3600 * -1;
            }
            $startdate = strtotime($row->begin . ' ' . $row->times)+$offset;
            $enddate = $row->end ? strtotime($row->end . ' ' . $row->endtimes)+$offset : strtotime($row->begin . ' ' . $row->endtimes)+$offset;

            $event = new RokMiniEvents_Event($startdate, $enddate, $row->title, $row->shortdesc, $link);
            $events[] = $event;
            $total_count++;
            if ($total_count > $total_max) break;
        }

        //$events = array();
        return $events;
    }

    /**
     * Checks to see if the source is available to be used
     * @return bool
     */
    function available()
    {
        $db =& JFactory::getDBO();
        $query = 'select count(*) from #__components as a where a.option = ' . $db->Quote('com_seminar');
        $db->setQuery($query);
        $count = (int)$db->loadResult();
        if ($count > 0)
            return true;

        return false;
    }

    /**
     * Determines an Seminar Link
     *
     * @param int The id of an Seminar item
     * @param string The view
     * @since 0.9
     *
     * @return string determined Link
     */
    function getRoute($id, $task = '3')
    {
        //Not needed currently but kept because of a possible hierarchic link structure in future
        $needles = array(
            $view => (int)$id
        );

        //Create the link
        $link = 'index.php?option=com_seminar&task=' . $task . '&cid=' . $id;
		
        if ($item = self::_findItem($needles))
        {
            $link .= '&Itemid=' . $item->id;
        }
        ;

        return $link;
    }

    /**
     * Determines the Itemid
     *
     * searches if a menuitem for this item exists
     * if not the first match will be returned
     *
     * @param array The id and view
     * @since 0.9
     *
     * @return int Itemid
     */
    function _findItem($needles)
    {
        $component =& JComponentHelper::getComponent('com_seminar');

        $menus = & JSite::getMenu();
        $items = $menus->getItems('componentid', $component->id);
        $user = & JFactory::getUser();
        $access = (int)$user->get('aid');

        //Not needed currently but kept because of a possible hierarchic link structure in future
        foreach ($needles as $needle => $id)
        {
            if (!empty($items))
            {
                foreach ($items as $item)
                {

                    if ((@$item->query['view'] == $needle) && (@$item->query['id'] == $id) && ($item->published == 1) && ($item->access <= $access))
                    {
                        return $item;
                    }
                }


                //no menuitem exists -> return first possible match
                foreach ($items as $item)
                {
                    if ($item->published == 1 && $item->access <= $access)
                    {
                        return $item;
                    }
                }
            }

        }
        return false;
    }
}
