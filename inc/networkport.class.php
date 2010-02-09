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

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')){
   die("Sorry. You can't access directly to this file");
}


/// NetworkPort class
class NetworkPort extends CommonDBChild {

   /// TODO manage access right on this object

   // From CommonDBChild
   public $itemtype='itemtype';
   public $items_id='items_id'; 
   public $dohistory = true;


   function canCreate() {
      return haveRight('networking', 'w');
   }

   function canView() {
      return haveRight('networking', 'r');
   }

   function post_updateItem($history=1) {

      // Only netpoint updates : ip and mac may be different.
      $tomatch=array("netpoints_id");
      $updates=array_intersect($this->updates,$tomatch);
      if (count($this->updates)) {
         $save_ID=$this->fields["id"];
         $n=new NetworkPort_NetworkPort;
         if ($this->fields["id"]=$n->getOppositeContact($save_ID)) {
            $this->updateInDB($this->updates);
         }
         $this->fields["id"]=$save_ID;
      }
   }

   function prepareInputForUpdate($input) {

      // Is a preselected mac adress selected ?
      if (isset($input['pre_mac']) && !empty($input['pre_mac'])) {
         $input['mac']=$input['pre_mac'];
         unset($input['pre_mac']);
      }
      return $input;
   }

   function prepareInputForAdd($input) {

      if (isset($input["logical_number"]) && strlen($input["logical_number"])==0) {
         unset($input["logical_number"]);
      }
      return $input;
   }

   function pre_deleteItem() {
      $nn= new NetworkPort_NetworkPort();
      if ($nn->getFromDBForNetworkPort($this->fields["id"])){
         $nn->delete($nn->fields);
      }
      return true;
   }


   function cleanDBonPurge() {
      global $DB;

      $query = "DELETE
                FROM `glpi_networkports_networkports`
                WHERE `networkports_id_1` = '".$this->fields['id']."'
                      OR `networkports_id_2` = '".$this->fields['id']."'";
      $result = $DB->query($query);
   }


   /**
    * Get port opposite port ID if linked item
    *
    *@param $ID networking port ID
    *
    *@return ID of the NetworkPort found, false if not found
    **/
   function getContact($ID) {

      $wire = new NetworkPort_NetworkPort;
      if ($contact_id = $wire->getOppositeContact($ID)) {
         return $contact_id;
      } else {
         return false;
      }
   }

   function defineTabs($options=array()) {
      global $LANG, $CFG_GLPI;

      $ong[1] = $LANG['title'][26];
      return $ong;
   }

   /**
   * Delete All connection of the given network port
   *
   *
   * @param $ID ID of the port
   * @return true on success
   */
   function resetConnections($ID) {

   }

   /**
   * Make a select box for  connected port
   *
   * Parameters which could be used in options array :
   *    - name : string / name of the select (default is networkports_id)
   *    - comments : boolean / is the comments displayed near the dropdown (default true)
   *    - entity : integer or array / restrict to a defined entity or array of entities
   *                   (default -1 : no restriction)
   *    - entity_sons : boolean / if entity restrict specified auto select its sons
   *                   only available if entity is a single value not an array (default false)
   *
   * @param $ID ID of the current port to connect
   * @param $options possible options
   * @return nothing (print out an HTML select box)
   */
   static function dropdownConnect($ID,$options=array()) {
      // $ID,$myname,$entity_restrict=-1
      global $LANG,$CFG_GLPI;

      $p['name']='networkports_id';
      $p['comments']=1;
      $p['entity']=-1;
      $p['entity_sons']=false;


     if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $p[$key]=$val;
         }
      }

      // Manage entity_sons
      if (!($p['entity']<0) && $p['entity_sons']) {
         if (is_array($p['entity'])) {
            echo "entity_sons options is not available with array of entity";
         } else {
            $p['entity'] = getSonsOf('glpi_entities',$p['entity']);
         }
      }

      $rand=mt_rand();
      echo "<select name='itemtype[$ID]' id='itemtype$rand'>";
      echo "<option value='0'>-----</option>";

      foreach ($CFG_GLPI["netport_types"] as $key => $itemtype) {
         if (class_exists($itemtype)) {
            $item = new $itemtype();
            echo "<option value='".$itemtype."'>".$item->getTypeName()."</option>";
         } else {
            unset($CFG_GLPI["netport_types"][$key]);
         }
      }
      echo "</select>";

      $params=array('itemtype'       => '__VALUE__',
                  'entity_restrict'  => $p['entity'],
                  'current'          => $ID,
                  'comments'         => $p['comments'],
                  'myname'           => $p['name']);

      ajaxUpdateItemOnSelectEvent("itemtype$rand","show_".$p['name']."$rand",$CFG_GLPI["root_doc"].
                                 "/ajax/dropdownConnectPortDeviceType.php",$params);

      echo "<span id='show_".$p['name']."$rand'>&nbsp;</span>\n";

      return $rand;
   }

   /**
    * Show ports for an item
    *
    * @param $itemtype integer : item type
    * @param $ID integer : item ID
    * @param $withtemplate integer : withtemplate param
    */
   static function showForItem($itemtype, $ID, $withtemplate = '') {
      global $DB, $CFG_GLPI, $LANG;


      $rand = mt_rand();
      if (!class_exists($itemtype)) {
         return false;
      }
      $item = new $itemtype();
      if (!haveRight('networking','r') || !$item->can($ID, 'r')) {
         return false;
      }
      $canedit = $item->can($ID, 'w');

      // Show Add Form
      if ($canedit && (empty($withtemplate) || $withtemplate !=2)) {
         echo "\n<div class='center'><table class='tab_cadre_fixe'>";
         echo "<tr><td class='tab_bg_2 center'>";
         echo "<a href=\"" . $CFG_GLPI["root_doc"] .
               "/front/networkport.form.php?items_id=$ID&amp;itemtype=$itemtype\"><strong>";
         echo $LANG['networking'][19];
         echo "</strong></a></td>\n";
         echo "<td class='tab_bg_2 center' width='50%'>";
         echo "<a href=\"" . $CFG_GLPI["root_doc"] .
               "/front/networkport.form.php?items_id=$ID&amp;itemtype=$itemtype&amp;several=yes\"><strong>";
         echo $LANG['networking'][46];
         echo "</strong></a></td></tr>\n";
         echo "</table></div><br>\n";
      } else {
         echo "<br>";
      }

      initNavigateListItems('NetworkPort',$item->getTypeName()." = ".$item->getName());

      $query = "SELECT `id`
               FROM `glpi_networkports`
               WHERE `items_id` = '$ID'
                     AND `itemtype` = '$itemtype'
               ORDER BY `name`, `logical_number`";
      if ($result = $DB->query($query)) {
         if ($DB->numrows($result) != 0) {
            $colspan = 9;
            if ($withtemplate != 2) {
               if ($canedit) {
                  $colspan++;
                  echo "\n<form id='networking_ports$rand' name='networking_ports$rand' method='post'
                        action=\"" . $CFG_GLPI["root_doc"] . "/front/networkport.form.php\">\n";
               }
            }
            echo "<div class='center'><table class='tab_cadre_fixe'>\n";
            echo "<tr><th colspan='$colspan'>\n";
            echo $DB->numrows($result) . " ";
            if ($DB->numrows($result) < 2) {
               echo $LANG['networking'][37];
            } else {
               echo $LANG['networking'][13];
            }
            echo "&nbsp;:</th></tr>\n";

            echo "<tr>";
            if ($withtemplate != 2 && $canedit) {
               echo "<th>&nbsp;</th>\n";
            }
            echo "<th>#</th>\n";
            echo "<th>" . $LANG['common'][16] . "</th>\n";
            echo "<th>" . $LANG['networking'][51] . "</th>\n";
            echo "<th>" . $LANG['networking'][14] . "<br>" . $LANG['networking'][15] . "</th>\n";
            echo "<th>" . $LANG['networking'][60] . "&nbsp;/&nbsp;" . $LANG['networking'][61]."<br>"
                        . $LANG['networking'][59] . "</th>\n";
            echo "<th>" . $LANG['networking'][56] . "</th>\n";
            echo "<th>" . $LANG['common'][65] . "</th>\n";
            echo "<th>" . $LANG['networking'][17] . "&nbsp;:</th>\n";
            echo "<th>" . $LANG['networking'][14] . "<br>" . $LANG['networking'][15] . "</th></tr>\n";

            $i = 0;
            $netport = new NetworkPort();
            while ($devid = $DB->fetch_row($result)) {
               $netport->getFromDB(current($devid));
               addToNavigateListItems('NetworkPort',$netport->fields["id"]);

               echo "<tr class='tab_bg_1'>\n";
               if ($withtemplate != 2 && $canedit) {
                  echo "<td class='center' width='20'>";
                  echo "<input type='checkbox' name='del_port[" . $netport->fields["id"] . "]' value='1'>";
                  echo "</td>\n";
               }
               echo "<td class='center'><strong>";
               if ($canedit && $withtemplate != 2) {
                  echo "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/networkport.form.php?id=" .
                        $netport->fields["id"] . "\">";
               }
               echo $netport->fields["logical_number"];
               if ($canedit && $withtemplate != 2) {
                  echo "</a>";
               }
               echo "</strong></td>\n";
               echo "<td>" . $netport->fields["name"] . "</td>\n";
               echo "<td>".Dropdown::getDropdownName("glpi_netpoints", $netport->fields["netpoints_id"])."</td>\n";
               echo "<td>" . $netport->fields["ip"] . "<br>" .$netport->fields["mac"] . "</td>\n";
               echo "<td>" . $netport->fields["netmask"] . "&nbsp;/&nbsp;".$netport->fields["subnet"] .
                           "<br>".$netport->fields["gateway"] . "</td>\n";
               // VLANs
               echo "<td>";
               NetworkPort_Vlan::showForNetworkPort($netport->fields["id"], $canedit, $withtemplate);
               echo "</td>\n";
               echo "<td>" . Dropdown::getDropdownName("glpi_networkinterfaces",
                                             $netport->fields["networkinterfaces_id"]) . "</td>\n";

               echo "<td width='300' class='tab_bg_2'>";
               self::showConnection($item, $netport, $withtemplate);
               echo "</td>\n";
               echo "<td class='tab_bg_2'>";
               if ($netport->getContact($netport->fields["id"])) {
                  echo $netport->fields["ip"] . "<br>";
                  echo $netport->fields["mac"];
               }
               echo "</td></tr>\n";
            }
            echo "</table></div>\n";

            if ($canedit && $withtemplate != 2) {
               openArrowMassive("networking_ports$rand", true);
               dropdownMassiveActionPorts($itemtype);
               closeArrowMassive();
            } else {
               echo "<br>";
            }
            if ($canedit && $withtemplate != 2) {
               echo "</form>";
            }
         }
      }
   }

   /**
   * Display a connection of a networking port
   *
   * @param $device1 the device of the port
   * @param $netport to be displayed
   * @param $withtemplate
   *
   */
   static function showConnection(& $device1, & $netport, $withtemplate = '') {
      global $CFG_GLPI, $LANG;

      if (!$device1->can($device1->fields["id"], 'r')) {
         return false;
      }

      $contact = new NetworkPort_NetworkPort;

      $canedit = $device1->can($device1->fields["id"], 'w');
      $ID = $netport->fields["id"];

      if ($contact_id = $contact->getOppositeContact($ID)) {
         $netport->getFromDB($contact_id);
         if (class_exists($netport->fields["itemtype"])) {
            $device2 = new $netport->fields["itemtype"]();
            if ($device2->getFromDB($netport->fields["items_id"])) {

               echo "\n<table width='100%'>\n";
               echo "<tr " . ($device2->fields["is_deleted"] ? "class='tab_bg_2_2'" : "") . ">";
               echo "<td><strong>";

               if ($device2->can($device2->fields["id"], 'r')) {

                  echo $netport->getLink();
                  echo "</a></strong>\n " . $LANG['networking'][25] . " <strong>";
                  echo $device2->getLink();
                  echo "</strong>";
                  if ($device1->fields["entities_id"] != $device2->fields["entities_id"]) {
                     echo "<br>(" .Dropdown::getDropdownName("glpi_entities", $device2->getEntityID()) .")";
                  }

                  // 'w' on dev1 + 'r' on dev2 OR 'r' on dev1 + 'w' on dev2
                  if ($canedit || $device2->can($device2->fields["id"], 'w')) {
                     echo "</td>\n<td class='right'><strong>";
                     if ($withtemplate != 2) {
                        echo "<a href=\"".$netport->getFormURL()."?disconnect=".
                              "disconnect&amp;id=".$contact->fields['id']."\">" . $LANG['buttons'][10] . "</a>";
                     } else {
                        "&nbsp;";
                     }
                     echo "</strong>";
                  }
               } else {
                  if (rtrim($netport->fields["name"]) != "") {
                     echo $netport->fields["name"];
                  } else {
                     echo $LANG['common'][0];
                  }
                  echo "</strong> " . $LANG['networking'][25] . " <strong>";
                  echo $device2->getName();
                  echo "</strong><br>(" .Dropdown::getDropdownName("glpi_entities", $device2->getEntityID()) .")";
               }
               echo "</td></tr></table>\n";
            }
         }
      } else {
         echo "\n<table width='100%'><tr>";
         if ($canedit) {
            echo "<td class='left'>";
            if ($withtemplate != 2 && $withtemplate != 1) {
                  NetworkPort::dropdownConnect($ID,
                                          array('name'         => 'dport',
                                                'entity'       => $device1->fields["entities_id"],
                                                'entity_sons'  => $device1->isRecursive()));
            } else {
               echo "&nbsp;";
            }
            echo "</td>\n";
         }
         echo "<td><div id='not_connected_display$ID'>" . $LANG['connect'][1] . "</div></td>";
         echo "</tr></table>\n";
      }
   }

}

?>
