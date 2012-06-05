<?php
/**
 * @version   1.5 March 31, 2011
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2011 RocketTheme, LLC
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 only
 * @modified for DTRegister: Dominic Richter
 */

defined('_JEXEC') or die('Restricted access');


class RokMiniEventsSourceDTRegister extends RokMiniEvents_SourceBase
{


    function getEvents(&$params)
    {
        // Reuse existing language file from JomSocial
        $language = JFactory::getLanguage();
        $language->load('com_dtregister', JPATH_ROOT);

        $query_start_date = null;
        $query_end_date = null;
		$showPastEvent = $params->get( 'show_past_event', 0 );
		
		
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

        $catid = trim($params->get('dtregister_category', 0));

		
        $categories = '';
        if ($catid != 0)
        {
            $categories = ' AND category = ' . $catid;
        }

		
		// vergangene events
		if($showPastEvent != 1){
			$sqlshowPastEvent =  " and a.dtend >=  now()  "; // and (a.startdate <= now() || a.startdate is null ) AND
		  }else{
			$sqlshowPastEvent = ""; 
		}
 
		
		$query = 'SELECT a.*,'
	            . ' a.slabId as slug'
				. ' FROM #__dtregister_group_event AS a'
				. ' WHERE a.publish=1 '
				. $sqlshowPastEvent
                . $categories
				. ' ORDER BY a.dtstart ASC, a.dtstarttime ASC';

        $db->setQuery($query);
        $rows = $db->loadObjectList();


        $total_count = 1;
        $total_max = $params->get('dtregister_total',10);
        $events = array();
        foreach ($rows as $row)
        {
            if ($params->get('dtregister_links') != 'link_no')
            {

                $link = array(
                    'internal' => ($params->get('dtregister_links') == 'link_internal') ? true : false,
                    'link' => JRoute::_(self::getRoute($row->slug))
                );
            } else
            {
                $link = false;
            }

            $offset = 0;
            if ($params->get('dtregister_dates_format', 'utc') == 'joomla'){
                $conf =& JFactory::getConfig();
                $timezone = $conf->getValue('config.offset') ;
                $offset = $timezone * 3600 * -1;
            }
            $startdate = strtotime($row->dtstart . ' ' . $row->dtstarttime)+$offset;
            $enddate = $row->dtend ? strtotime($row->dtend . ' ' . $row->dtendtime)+$offset : strtotime($row->dtstarttime . ' ' . $row->dtendtime)+$offset;

            $event = new RokMiniEvents_Event($startdate, $enddate, $row->title, $row->event_describe, $link);
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
        $query = 'select count(*) from #__components as a where a.option = ' . $db->Quote('com_dtregister');
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
    function getRoute($id, $task = 'register')
    {
        //Not needed currently but kept because of a possible hierarchic link structure in future
        $needles = array(
            $view => (int)$id
        );

        //Create the link
		$link = 'index.php?option=com_dtregister&controller=event&task=' . $task . '&eventId=' . $id;
		
		
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
        $component =& JComponentHelper::getComponent('com_dtregister');

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
