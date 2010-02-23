<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2010 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 --------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')){
   die("Sorry. You can't access directly to this file");
}

// Class NotificationTarget
class NotificationTargetReservation extends NotificationTarget {

   function getSpecificAddresses($data,$options=array()) {

   //Look for all targets whose type is Notification::ITEM_USER
   switch ($data['type']) {

      case Notification::USER_TYPE:
         switch ($data['items_id']) {
           //Send to the author of the ticket
            case Notification::AUTHOR:
               $this->getItemAuthorAddress();
            break;
      }

      //Send to all the users of a profile
      case Notification::PROFILE_TYPE:
         $this->getUsersAddressesByProfile($data['items_id']);
      break;
      }
   }


   /**
    * Get item associated with the object on which the event was raised
    * @return the object associated with the itemtype
    */
   function getObjectItem() {
      $ri = new ReservationItem;
      if ($ri->getFromDB($this->obj->getField('reservationitems_id')))
      {
         $itemtype = $ri->getField('itemtype');
         $item = new  $itemtype ();
         $item->getFromDB($ri->getField('items_id'));
         $this->target_object = $item;
      }
   }

   function getEvents() {
      global $LANG;
      return array ('new' => $LANG['mailing'][19],
                    'update'=> $LANG['mailing'][23],
                    'delete'=>$LANG['mailing'][29]);
   }

   function getAdditionalTargets() {
      global $LANG;

      $this->addTarget(Notification::ITEM_TECH_IN_CHARGE,$LANG['common'][10]);
      $this->addTarget(Notification::ITEM_USER,$LANG['common'][34]);
      $this->addTarget(Notification::AUTHOR,$LANG['job'][4]);
   }

   function getDatasForTemplate($event,$tpldata = array(), $options=array()) {
      global $DB, $LANG, $CFG_GLPI;

      //----------- Ticket infos -------------- //

      $events = $this->getAllEvents();
      $tpldata['##reservation.action##'] = $events[$event];
      $tpldata['##reservation.user##'] = Dropdown::getDropdownName('glpi_users',
                                                                $this->obj->getField('users_id'));
      $tpldata['##reservation.begin##'] = convDateTime($this->obj->getField('begin'));
      $tpldata['##reservation.end##'] = convDateTime($this->obj->getField('end'));

      $reservationitem = new ReservationItem;
      $reservationitem->getFromDB($this->obj->getField('reservationitems_id'));
      $itemtype = $reservationitem->getField('itemtype');
      if (class_exists($itemtype)) {
         $item = new $itemtype();
         $item->getFromDB($reservationitem->getField('items_id'));
         $tpldata['##reservation.itemtype##'] = $item->getTypeName();
         $tpldata['##reservation.item.name##'] = $item->getField('name');
         $tpldata['##reservation.comment##'] = $item->getField('comment');
         $tpldata['##reservation.item.entity##'] = Dropdown::getDropdownName('glpi_entities',
                                                                    $item->getField('entities_id'));
         if ($item->isField('users_id_tech')) {
             $tpldata['##reservation.item.tech##'] = Dropdown::getDropdownName('glpi_users',
                                                                  $item->getField('users_id_tech'));
         }
      }

      $fields = array('##lang.ticket.item.name##'=> $LANG['financial'][104],
                      '##lang.reservation.user##'=>$LANG['common'][37],
                      '##lang.reservation.begin##'=>$LANG['search'][8],
                      '##lang.reservation.end##'=>$LANG['search'][9],
                      '##lang.reservation.comment##'=>$LANG['common'][25],
                      '##lang.reservation.item.entity##'=>$LANG['entity'][0],
                      '##lang.reservation.item.name##'=>$LANG['financial'][104],
                      '##lang.reservation.item.tech##'=> $LANG['common'][10]
                      );
      foreach ($fields as $name => $label) {
         $tpldata[$name] = $label;
      }
      return $tpldata;
   }
}
?>