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

// ----------------------------------------------------------------------
// Original Author of file: Remi Collet
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/// Class DeviceCase
class DeviceCase extends CommonDevice {

   static function getTypeName($nb=0) {
      return _n('Case', 'Cases', $nb);
   }


   function getAdditionalFields() {

      return array_merge(parent::getAdditionalFields(),
                         array(array('name'  => 'devicecasetypes_id',
                                     'label' => __('Size'),
                                     'type'  => 'dropdownValue')));
   }


   function getSearchOptions() {

      $tab              = parent::getSearchOptions();

      $tab[12]['table'] = 'glpi_devicecasetypes';
      $tab[12]['field'] = 'name';
      $tab[12]['name']  = __('Size');

      return $tab;
   }


   /**
    * return the display data for a specific device
    *
    * @return array
   **/
   function getFormData() {

      $data['label'] = $data['value'] = array();
      if ($this->fields["devicecasetypes_id"]) {
         $data['label'][] = __('Size');
         $data['value'][] = Dropdown::getDropdownName("glpi_devicecasetypes",
                                                      $this->fields["devicecasetypes_id"]);
      }
      return $data;
   }
   /**
    * @since version 0.84
    *
    * @param $itemtype
    * @param $base               HTMLTable_Base object
    * @param $super              HTMLTable_SuperHeader object (default NULL)
    * @param $father             HTMLTable_Header object (default NULL)
    * @param $options   array
   **/
   static function getHTMLTableHeader($itemtype, HTMLTable_Base $base,
                                      HTMLTable_SuperHeader $super=NULL,
                                      HTMLTable_Header $father=NULL, $options=array()) {

      $column_name = __CLASS__;

      if (isset($options['dont_display'][$column_name])) {
         return;
      }

      switch ($itemtype) {
         case 'Computer_Device' :
            Manufacturer::getHTMLTableHeader(__CLASS__, $base, $super, $father, $options);
            break;
      }
   }


   /**
    * @since version 0.84
    *
    * @see inc/CommonDevice::getHTMLTableCell()
   **/
   function getHTMLTableCell($item_type, HTMLTable_Row $row, HTMLTable_Cell $father=NULL,
                             array $options=array()) {

      switch ($item_type) {
         case 'Computer_Device' :
            Manufacturer::getHTMLTableCellsForItem($row, $this, NULL, $options);
      }
   }
}
?>
