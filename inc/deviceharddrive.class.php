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

/// Class DeviceHardDrive
class DeviceHardDrive extends CommonDevice {

   static function getTypeName($nb=0) {
      return _n('Hard drive', 'Hard drives', $nb);
   }


   static function getSpecifityLabel() {
      return array('specificity' => sprintf(__('%1$s (%2$s)'), __('Capacity'), __('Mio')));
   }


   function getAdditionalFields() {

      return array_merge(parent::getAdditionalFields(),
                         array(array('name'  => 'specif_default',
                                     'label' => __('Capacity by default'),
                                     'type'  => 'text',
                                     'unit'  => __('Mio')),
                               array('name'  => 'rpm',
                                     'label' => __('Rpm'),
                                     'type'  => 'text'),
                               array('name'  => 'cache',
                                     'label' => __('Cache'),
                                     'type'  => 'text',
                                     'unit'  => __('Mio')),
                               array('name'  => 'interfacetypes_id',
                                     'label' => __('Interface'),
                                     'type'  => 'dropdownValue')));
   }


   function getSearchOptions() {

      $tab                 = parent::getSearchOptions();

      $tab[11]['table']    = $this->getTable();
      $tab[11]['field']    = 'specif_default';
      $tab[11]['name']     = __('Capacity by default');
      $tab[11]['datatype'] = 'text';

      $tab[12]['table']    = $this->getTable();
      $tab[12]['field']    = 'rpm';
      $tab[12]['name']     = __('Rpm');
      $tab[12]['datatype'] = 'text';

      $tab[13]['table']    = $this->getTable();
      $tab[13]['field']    = 'cache';
      $tab[13]['name']     = __('Cache');
      $tab[13]['datatype'] = 'text';

      $tab[14]['table']    = 'glpi_interfacetypes';
      $tab[14]['field']    = 'name';
      $tab[14]['name']     = __('Interface');

      return $tab;
   }


   /**
    * return the display data for a specific device
    *
    * @return array
   **/
   function getFormData() {

      $data['label'] = $data['value'] = array();

      if (!empty($this->fields["rpm"])) {
         $data['label'][] = __('Rpm');
         $data['value'][] = $this->fields["rpm"];
      }

      if ($this->fields["interfacetypes_id"]) {
         $data['label'][] = __('Interface');
         $data['value'][] = Dropdown::getDropdownName("glpi_interfacetypes",
                                                      $this->fields["interfacetypes_id"]);
      }

      if (!empty($this->fields["cache"])) {
         $data['label'][] = __('Cache');
         $data['value'][] = $this->fields["cache"];
      }
      // Specificity
      $data['label'][] = __('Capacity');
      $data['size']    = 10;

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
                                      HTMLTable_Header $father=NULL, array $options=array()) {

      $column_name = __CLASS__;

      if (isset($options['dont_display'][$column_name])) {
         return;
      }

      switch ($itemtype) {
         case 'Computer_Device' :
            Manufacturer::getHTMLTableHeader(__CLASS__, $base, $super, $father, $options);
            $base->addHeader('rpm', __('Rpm'), $super, $father);
            $base->addHeader('cache', __('Cache'), $super, $father);
            InterfaceType::getHTMLTableHeader(__CLASS__, $base, $super, $father, $options);
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
            if ($this->fields["rpm"]) {
               $row->addCell($row->getHeaderByName('specificities', 'rpm'), $this->fields["rpm"]);
            }

            if ($this->fields["cache"]) {
               $row->addCell($row->getHeaderByName('specificities', 'cache'),
                             $this->fields["cache"]);
            }

            InterfaceType::getHTMLTableCellsForItem($row, $this, NULL, $options);

            break;
      }

   }


}
?>