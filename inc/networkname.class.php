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
// Original Author of file: Damien Touraine
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/// Class NetworkName : represent the internet name of an element. It is compose of the name itself,
/// its domain and one or several IP addresses (IPv4 and/or IPv6). It relies on IPAddress object.
/// There is no network associated with the addresses as the addresses can be inside several
/// different kind of networks : at least one real network (ie : the one that is configured in the
/// computer with gateways) and several administrative networks (for instance, entity sub-network).
/// An address can be affected to an item, or can be "free" to be reuse by another item (for
/// instance, in case of maintenance, when you change the network card of a computer, but not its
/// network information
/// since version 0.84
class NetworkName extends FQDNLabel {

   // From CommonDBChild
   public $itemtype              = 'itemtype';
   public $items_id              = 'items_id';
   public $dohistory             = true;
   public $inheritEntityFromItem = true;


   function canCreate() {

      if (!Session::haveRight('internet', 'w')) {
         return false;
      }

      if (!empty($this->fields['itemtype']) && !empty($this->fields['items_id'])) {
         $item = new $this->fields['itemtype']();
         if ($item->getFromDB($this->fields['items_id'])) {
            return $item->canCreate();
         }
      }

      return true;
   }


   function canView() {

      if (!Session::haveRight('internet', 'r')) {
         return false;
      }

      if (!empty($this->fields['itemtype']) && !empty($this->fields['items_id'])) {
         $item = new $this->fields['itemtype']();
         if ($item->getFromDB($this->fields['items_id'])) {
            return $item->canView();
         }
      }

      return true;
   }


   static function getTypeName($nb=0) {
      return _n('Network name', 'Network names', $nb);
   }


   function defineTabs($options=array()) {

      $ong  = array();
      $this->addStandardTab('NetworkAlias', $ong, $options);
      $this->addStandardTab('Log', $ong, $options);

      return $ong;
   }


   /**
    * Print the network name form
    *
    * @param $ID        integer ID of the item
    * @param $options   array
    *     - target for the Form
    *     - withtemplate template or basic computer
    *
    *@return Nothing (display)
   **/
   function showForm($ID, $options=array()) {
      global $CFG_GLPI;

      if (!Session::haveRight("internet", "r")) {
         return false;
      }

      if ($ID > 0) {
         $this->check($ID,'r');
      } else {
         $this->check(-1, 'w', $options);
      }

      $recursiveItems = $this->recursivelyGetItems();
      if (count($recursiveItems) == 0) {
         return false;
      }

      $lastItem = $recursiveItems[count($recursiveItems) - 1];

      $this->showTabs();

      $options['entities_id'] = $lastItem->getField('entities_id');
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'><td>";
      $this->displayRecursiveItems($recursiveItems, 'Type');
      echo "&nbsp;:</td>\n<td>";

      if (!($ID>0)) {
         echo "<input type='hidden' name='items_id' value='".$this->fields["items_id"]."'>\n";
         echo "<input type='hidden' name='itemtype' value='".$this->fields["itemtype"]."'>\n";
      }
      $this->displayRecursiveItems($recursiveItems, "Link");
      echo "</td>\n";
      echo "<td>" . __('Name') . "</td><td>\n";
      Html::autocompletionTextField($this, "name");
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      $address = new IPAddress();
      echo "<td rowspan='2'>".$address->getTypeName(2);
      $address->showAddButtonForChildItem($this, '_ipaddresses');
      echo "</td>";
      echo "<td rowspan='2'>";
      $address->showFieldsForItemForm($this, '_ipaddresses', 'name');
      echo "</td>\n";

      echo "<td>".FQDN::getTypeName(1)."</td><td>";
      Dropdown::show(getItemTypeForTable(getTableNameForForeignKeyField("fqdns_id")),
                     array('value'       => $this->fields["fqdns_id"],
                           'name'        => 'fqdns_id',
                           'entity'      => $this->getEntityID(),
                           'displaywith' => array('view')));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Comments')."</td>";
      echo "<td><textarea cols='45' rows='4' name='comment' >".$this->fields["comment"];
      echo "</textarea></td>\n";
      echo "</tr>";

      $this->showFormButtons($options);
      $this->addDivForTabs();

      return true;
   }


   function getSearchOptions() {

      $tab = parent::getSearchOptions();

      $tab[12]['table']         = 'glpi_fqdns';
      $tab[12]['field']         = 'fqdn';
      $tab[12]['name']          = FQDN::getTypeName(1);

      $tab[13]['table']         = 'glpi_ipaddresses';
      $tab[13]['field']         = 'name';
      $tab[13]['name']          = IPAddress::getTypeName(1);
      $tab[13]['joinparams']    = array('jointype' => 'itemtype_item');
      $tab[13]['forcegroupby']  = true;
      $tab[13]['massiveaction'] = false;

      $tab[20]['table']        = $this->getTable();
      $tab[20]['field']        = 'itemtype';
      $tab[20]['name']         = __('Type');
      $tab[20]['datatype']     = 'itemtype';
      $tab[20]['massiveation'] = false;

      $tab[21]['table']        = $this->getTable();
      $tab[21]['field']        = 'items_id';
      $tab[21]['name']         = __('id');
      $tab[21]['datatype']     = 'integer';
      $tab[21]['massiveation'] = false;

      return $tab;
   }


   /**
    * Check input validity for CommonDBTM::add and CommonDBTM::update
    *
    * @param $input the input given to CommonDBTM::add or CommonDBTM::update
    *
    * @return $input altered array of new values;
   **/
   function prepareInput($input) {

      if (isset($input["_ipaddresses"])) {
         $addresses = IPAddress::checkInputFromItem($input["_ipaddresses"],
                                                    self::getType(), $this->getID());

         if (count($addresses["invalid"]) > 0) {
            $msg = sprintf(_n('Invalid IP address: %s', 'Invalid IP addresses: %s',
                              count($addresses["invalid"])),
                           implode (', ',$addresses["invalid"]));
            Session::addMessageAfterRedirect($msg, false, ERROR);
            unset($addresses["invalid"]);
         }

         // TODO : is it usefull to check that there is at least one IP address ?
         // if ((count($addresses["new"]) + count($addresses["previous"])) == 0) {
         //    Session::addMessageAfterRedirect(__('No IP address (v4 or v6) defined'), false, ERROR);
         //    return false;
         // }

         $this->IPs             = $addresses;
         $input["_ipaddresses"] = "";
      }

      return $input;
   }


   function prepareInputForAdd($input) {

      $input = $this->prepareInput($input);

      if (!is_array($input)) {
         return false;
      }
      return parent::prepareInputForAdd($input);
   }


   function prepareInputForUpdate($input) {

      $input = $this->prepareInput($input);

      if (!is_array($input)) {
         return false;
      }
      return parent::prepareInputForUpdate($input);
   }


   function pre_deleteItem() {

      IPAddress::cleanAddress($this->getType(), $this->GetID());
      return parent::pre_deleteItem();
   }


   /**
    * \brief Update IPAddress database
    * Update IPAddress database to remove old IPs and add new ones. Update this "IPs" cache field
    * with the current IP addresses according to the database
    * And, if the addresses are different than before, recreate the link with the networks
   **/
   function post_workOnItem() {

      if (isset($this->IPs)) {
         global $DB;

         // Update IPAddress database : return value is a list of
         $newIPaddressField      = IPAddress::updateDatabase($this->IPs, $this->getType(),
                                                             $this->getID());

         $new_ip_addresses_field = implode('\n', $newIPaddressField);

         $query = "UPDATE `".$this->getTable()."`
                   SET `ip_addresses` = '$new_ip_addresses_field'
                   WHERE `id` = '".$this->getID()."'";
         $DB->query($query);

         unset($this->IPs);

      } else {
         $new_ip_addresses_field = "";
      }
   }


   function post_addItem() {

      $this->post_workOnItem();
      parent::post_addItem();
   }


   function post_updateItem($history=1) {

      $this->post_workOnItem();
      parent::post_updateItem($history);
   }


   function cleanDBonPurge() {

      $alias = new NetworkAlias();
      $alias->cleanDBonItemDelete($this->getType(), $this->GetID());

      $ipAddress = new IPAddress();
      $ipAddress->cleanDBonItemDelete($this->getType(), $this->GetID());
   }


   /**
    * \brief dettach an address from an item
    *
    * The address can be unaffected, and remain "free"
    *
    * @param $items_id  the id of the item
    * @param $itemtype  the type of the item
    *
   **/
   static function unaffectAddressesOfItem($items_id, $itemtype) {
      global $DB;

      $query = "SELECT `id`
                FROM `glpi_networknames`
                WHERE `items_id` = '".$items_id."'
                AND `itemtype` = '".$itemtype."'";

      foreach ($DB->request($query) as $networkNameID) {
         self::unaffectAddressByID($networkNameID['id']);
      }
   }


   /**
    * \brief dettach an address from an item
    *
    * The address can be unaffected, and remain "free"
    *
    * @param $networkNameID the id of the NetworkName
   **/
   static function unaffectAddressByID($networkNameID) {
      self::affectAddress($networkNameID, 0, '');
   }


   /**
    * @param $networkNameID
    * @param $items_id
    * @param $itemtype
   **/
   static function affectAddress($networkNameID, $items_id, $itemtype) {

      $networkName = new self();
      $networkName->update(array('id'       => $networkNameID,
                                 'items_id' => $items_id,
                                 'itemtype' => $itemtype));
   }


   /**
    * Get the full name (internet name) of a NetworkName
    *
    * @param $ID ID of the NetworkName
    *
    * @return its internet name, or empty string if invalid NetworkName
   **/
   static function getInternetNameFromID($ID) {

      $networkName = new self();

      if ($networkName->can($ID, 'r')) {
         return FQDNLabel::getInternetNameFromLabelAndDomainID($this->fields["name"],
                                                               $this->fields["fqdns_id"]);
      }
      return "";
   }


   /**
    * @param $networkPortID
   **/
   static function showFormForNetworkPort($networkPortID) {
      global $DB;

      $name         = new self();
      $number_names = 0;

      if ($networkPortID > 0) {
         $query = "SELECT `id`
                   FROM `".$name->getTable()."`
                   WHERE `itemtype` = 'NetworkPort'
                   AND `items_id` = '$networkPortID'";
         $result = $DB->query($query);

         if ($DB->numrows($result) > 1) {
            echo "<tr class='tab_bg_1'><th colspan='4'>" .
                   __("Several network names available! Go to the tab 'Network Name' to manage them.") .
                 "</th></tr>\n";
            return;
         }

        switch ($DB->numrows($result)) {
            case 1 :
               $nameID = $DB->fetch_assoc($result);
               $name->getFromDB($nameID['id']);
               break;

            case 0 :
               $name->getEmpty();
               break;
         }

      } else {
         $name->getEmpty();
      }

      echo "<tr class='tab_bg_1'><th colspan='4'>" . $name->getTypeName(1);
      if ($name->getID() > 0) {
         echo "<input type='hidden' name='NetworkName_id' value='".$name->getID()."'>\n";
      }
      echo "</th></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . self::getTypeName(1) . "</td><td>\n";
      Html::autocompletionTextField($name, "name", array('name' => 'NetworkName_name'));
      echo "</td>\n";

      $address = new IPAddress();
      echo "<td rowspan='2'>".$address->getTypeName(2);
      $address->showAddButtonForChildItem($name, 'NetworkName__ipaddresses');
      echo "</td>";
      echo "<td rowspan='2'>";
      $address->showFieldsForItemForm($name, 'NetworkName__ipaddresses', 'name');
      echo "</tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".FQDN::getTypeName(1)."</td><td>";
      Dropdown::show(getItemTypeForTable(getTableNameForForeignKeyField("fqdns_id")),
                     array('value'       => $name->fields["fqdns_id"],
                           'name'        => 'NetworkName_fqdns_id',
                           'entity'      => $name->getEntityID(),
                           'displaywith' => array('view')));
      echo "</td></tr>\n";
      echo "</tr>\n";
   }


   /**
    * Get HTMLTable columns headers for a given item type
    *
    * @param $itemtype           The type of the item
    * @param $table              HTMLTable object: the table to update
    * @param $fathers_name       The name of the father element (default '')
    * @param $options      array of possible options:
    *       - 'dont_display' : array of the columns that must not be display
    *       - 'column_links' : array of links for a given column
    *
   **/
   static function getHTMLTableHeaderForItem($itemtype, HTMLTable &$table, $fathers_name="",
                                             $options=array()) {

      $column_name = __CLASS__;

      if (isset($options['dont_display'][$column_name])) {
         return;
      }

      $header = self::getTypeName();
      if (isset($options['column_links'][$column_name])) {
         $header = "<a href='".$options['column_links'][$column_name]."'>$header</a>";
      }
      $table->addHeader($header, $column_name, $fathers_name, __CLASS__);

      NetworkAlias::getHTMLTableHeaderForItem(__CLASS__, $table, $column_name, $options);
      IPAddress::getHTMLTableHeaderForItem(__CLASS__, $table, $column_name, $options);
   }


   /**
    * Get HTMLTable row for a given item
    *
    * @param $item            CommonDBTM object
    * @param $table           HTMLTable object: tThe table to update
    * @param $canedit         display the edition elements (ie : add, remove, ...)
    * @param $close_row       set to true if we must close the row at the end of the current element
    * @param $options   array of possible options:
    *       - 'dont_display' : array of the elements that must not be display
    *       - 'SQL_options'  : SQL options to add after WHERE request
    *
   **/
   static function getHTMLTableForItem(CommonDBTM $item, HTMLTable &$table, $canedit, $close_row,
                                       $options=array()) {
      global $DB, $CFG_GLPI;

      $column_name = __CLASS__;
      if (isset($options['dont_display'][$column_name])) {
         return;
      }

      switch ($item->getType()) {
      case 'IPNetwork':
      case 'FQDN' :
         $where_criterion = $item->getCriterionForMatchingNetworkNames();
         break;

      case 'NetworkEquipment' :
      case 'NetworkPort' :
         $where_criterion = "`itemtype` = '".$item->getType()."'
                             AND `items_id` = '".$item->getID()."'";
         break;
      }


      $query = "SELECT `id`
                FROM `glpi_networknames`
                WHERE $where_criterion";

      if (isset($options['SQL_options'])) {
         $query .= " ".$options['SQL_options'];
      }
      $result = $DB->query($query);

      $address = new self();

      if ($DB->numrows($result) > 0) {
         while ($line = $DB->fetch_assoc($result)) {

            if ($address->getFromDB($line["id"])) {

               $content      = "<a href='" . $address->getLinkURL(). "'>";
               $internetName = $address->getInternetName();
               if (empty($internetName)) {
                  $content .= "(".$line["id"].")";
               } else {
                  $content .= $internetName;
               }
               $content .= "</a>";

               if ($canedit) {
                  $content .= "&nbsp;- <a href='" . $address->getFormURL() .
                              "?remove_address=unaffect&id=" . $address->getID() . "'>&nbsp;".
                              "<img src=\"" . $CFG_GLPI["root_doc"] .
                              "/pics/sub_dropdown.png\" alt=\"" . __s('Dissociate') .
                              "\" title=\"" . __s('Dissociate') . "\"></a>";
                  $content .= "&nbsp;- <a href='" . $address->getFormURL() .
                              "?remove_address=purge&id=" . $address->getID() . "'>&nbsp;".
                              "<img src=\"" . $CFG_GLPI["root_doc"] .
                              "/pics/delete.png\" alt=\"" . __s('Purge') . "\" title=\"" .
                              __s('Purge') . "\"></a>";
               }

               $table->addElement($content, $column_name, $address->getID(), $item->getID());

               NetworkAlias::getHTMLTableForItem($address, $table, $canedit, false, $options);

               IPAddress::getHTMLTableForItem($address, $table, $canedit, false, $options);

               if ($close_row) {
                  $table->closeRow();
               }
            }
         }
      }
   }


   /**
    * \brief Show names for an item from its form
    * Beware that the rendering can be different if readden from direct item form (ie : add new
    * NetworkName, remove, ...) or if readden from item of the item (for instance from the computer
    * form through NetworkPort::ShowForItem).
    *
    * @param $item                     CommonGLPI object
    * @param $withtemplate   integer   withtemplate param (default 0)
   **/
   static function showForItem(CommonGLPI $item, $withtemplate=0) {
      global $DB, $CFG_GLPI;

      $table_options = array();

      if (($item->getType() == 'IPNetwork') || ($item->getType() == 'FQDN')) {
         if (isset($_REQUEST["start"])) {
            $start = $_REQUEST["start"];
         } else {
            $start = 0;
         }

         if (!empty($_REQUEST["order"])) {
            $order = $_REQUEST["order"];
         } else {
            $order = "name";
         }

         $table_options['dont_display'] = array('IPNetwork'    => true);
         $table_options['SQL_options']  = "ORDER BY `$order`
                                           LIMIT ".$_SESSION['glpilist_limit']."
                                           OFFSET $start";
         $table_options['column_links'] =
               array('NetworkName' => 'javascript:reloadTab("order=name");',
                     'IPAddress'   => 'javascript:reloadTab("order=ip_addresses");');

         $canedit = false;

      } else {
         $canedit = true;
      }

      $address  = new self();
      $table    = new HTMLTable();
      $table->addGlobalName(self::getTypeName(2));

      self::getHTMLTableHeaderForItem($item->getType(), $table, "", $table_options);

      // Reorder the columns for better display
      $table->setColumnOrder(array('NetworkName', 'IPAddress', 'NetworkAlias'));

      self::getHTMLTableForItem($item, $table, $canedit, true, $table_options);

      if ($table->getNumberOfRows() > 0) {

         if (($item->getType() == 'IPNetwork') || ($item->getType() == 'FQDN')) {
            $number = countElementsInTable($address->getTable(),
                                           $item->getCriterionForMatchingNetworkNames());
            Html::printAjaxPager(self::getTypeName(2), $start, $number);
         }
         Session::initNavigateListItems(__CLASS__,
                                 //TRANS : %1$s is the itemtype name,
                                 //        %2$s is the name of the item (used for headings of a list)
                                        sprintf(__('%1$s = %2$s'),
                                                $item->getTypeName(1), $item->getName()));
         $table->display();
      } else {
         echo "<table class='tab_cadre_fixe'><tr><th>".__('No network name found')."</th></tr>";
         echo "</table>";
      }

      if (($item->getType() == 'NetworkEquipment') || ($item->getType() == 'NetworkPort')) {

         $items_id = $item->getID();
         $itemtype = $item->getType();

         echo "<div class='center'>\n";
         echo "<table class='tab_cadre'>\n";

         echo "<tr><th>".__('Add a network name')."</th></tr>";

         echo "<tr><td class='center'>";
         echo "<a href=\"" . $address->getFormURL()."?items_id=$items_id&itemtype=$itemtype\">";
         echo __('New one')."</a>";
         echo "</td></tr>\n";

         echo "<tr><td class='center'>";
         echo "<form method='post' action='".$address->getFormURL()."'>\n";
         echo "<input type='hidden' name='items_id' value='$items_id'>\n";
         echo "<input type='hidden' name='itemtype' value='$itemtype'>\n";

         _e('Not associated one:');
         Dropdown::show(__CLASS__, array('name'      => 'addressID',
                                         'condition' => '`items_id`=0'));
         echo "&nbsp;<input type='submit' name='assign_address' value='" . __s('Associate') .
                      "' class='submit'>";
         echo "</form>\n";
         echo "</td></tr>\n";

         echo "</table>\n";
         echo "</div>\n";
      }

   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      switch ($item->getType()) {
         case 'NetworkEquipment' :
         case 'NetworkPort' :
         case 'IPNetwork' :
         case 'FQDN' :
             self::showForItem($item, $withtemplate);
            break;
      }
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if ($item->getID() && $item->can($item->getField('id'),'r')) {
         if ($_SESSION['glpishow_count_on_tabs']) {
            $numberElements = 0;

            switch ($item->getType()) {
               case 'IPNetwork':
               case 'FQDN' :
                  $numberElements = countElementsInTable($this->getTable(),
                                                         $item->getCriterionForMatchingNetworkNames());
                  break;

               case 'NetworkEquipment' :
               case 'NetworkPort' :

                  $numberElements = countElementsInTable($this->getTable(),
                                                        "itemtype = '".$item->getType()."'
                                                           AND items_id='".$item->getID()."'");
                  break;

            }
            return self::createTabEntry(self::getTypeName(2), $numberElements);
         }
         return self::getTypeName(2);
      }
      return '';
   }
}
?>
