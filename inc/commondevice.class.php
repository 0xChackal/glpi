<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2012 by the INDEPNET Development Team.

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
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}


// CommonDevice Class for Device*class
abstract class CommonDevice extends CommonDropdown {


   function canCreate() {
      return Session::haveRight('device', 'w');
   }


   function canView() {
      return Session::haveRight('device', 'r');
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if (($item->getType() == 'Computer')
          && Session::haveRight("computer","r")) {
         if ($_SESSION['glpishow_count_on_tabs']) {
            $nb    = 0;
            $types =  Computer_Device::getDeviceTypes();
            foreach ($types as $type) {
               $table = getTableForItemType('Computer_'.$type);
               $nb   += countElementsInTable($table, "computers_id = '".$item->getID()."'");
            }
            return self::createTabEntry(_n('Component', 'Components', 2), $nb);
         }
         return _n('Component', 'Components', 2);
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      Computer_Device::showForComputer($item, $withtemplate);
      return true;
   }


   function getAdditionalFields() {

      return array(array('name'  => 'manufacturers_id',
                         'label' => __('Manufacturer'),
                         'type'  => 'dropdownValue'));
   }


   function getSearchOptions() {

      $tab = array();
      $tab['common']           = __('Characteristics');

      $tab[1]['table']         = $this->getTable();
      $tab[1]['field']         = 'designation';
      $tab[1]['name']          = __('Name');
      $tab[1]['datatype']      = 'itemlink';
      $tab[1]['itemlink_link'] = $this->getType();
      $tab[1]['massiveaction'] = false;

      $tab[23]['table']        = 'glpi_manufacturers';
      $tab[23]['field']        = 'name';
      $tab[23]['name']         = __('Manufacturer');

      $tab[16]['table']        = $this->getTable();
      $tab[16]['field']        = 'comment';
      $tab[16]['name']         = __('Comments');
      $tab[16]['datatype']     = 'text';

      return $tab;
   }


   function title() {

      Dropdown::showItemTypeMenu(_n('Component', 'Components', 2),
                                 Dropdown::getDeviceItemTypes(), $this->getSearchURL());
   }


   function displayHeader() {
      Html::header($this->getTypeName(1), '', "config", "device", get_class($this));
   }


   /**
    * @param $with_comment (default 0)
   **/
   function getName($with_comment=0) {

      $toadd = "";
      if ($with_comment) {
         $toadd = sprintf(__('%1$s - %2$s'), $toadd, $this->getComments());
      }

      if (isset($this->fields['designation']) && !empty($this->fields['designation'])) {
         return $this->fields['designation'].$toadd;
      }
      return NOT_AVAILABLE;
   }


   /**
    * return the display data for a specific device
    *
    * @return array
   **/
   function getFormData() {
      return false;
   }


   /**
    * Return the specifities localized name for the Device
    *
    * @return string
   **/
   static function getSpecifityLabel() {
      return array();
   }


   function cleanDBonPurge() {

      $compdev = new Computer_Device();
      $compdev->cleanDBonItemDelete($this->getType(), $this->fields['id']);
   }


   /**
    * Import a device is not exists
    *
    * @param $input array of datas
    *
    * @return interger ID of existing or new Device
   **/
   function import(array $input) {
      global $DB;

      if (!isset($input['designation']) || empty($input['designation'])) {
         return 0;
      }
      $query = "SELECT `id`
                FROM `".$this->getTable()."`
                WHERE `designation` = '" . $input['designation'] . "'";

      $result = $DB->query($query);
      if ($DB->numrows($result) > 0) {
         $line = $DB->fetch_assoc($result);
         return $line['id'];
      }
      return $this->add($input);
   }

}
?>