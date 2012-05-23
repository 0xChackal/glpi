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
// Original Author of file: Olivier Andreotti
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class RegistryKey extends CommonDBTM {


   static function getTypeName($nb=0) {
      // No plural
      return __('Registry');
   }

// TODO OCS
/*
   function canCreate() {
      // Only create on ocsng sync
      return Session::haveRight('sync_ocsng', 'w');
   }


   function canView() {
      return Session::haveRight('ocsng', 'r');
   }
*/

   function cleanDBonPurge() {
      global $DB;

      $query = "DELETE
                FROM `".$this->getTable()."`
                WHERE `computers_id` = '".$this->fields['id']."'";
      $result = $DB->query($query);
   }


   /** Display registry values for a computer
    *
    * @param $comp Computer object
   **/
   static function showForComputer(Computer $comp) {
      global $DB;

      if (!Session::haveRight("computer","r")) {
         return false;
      }

      //REGISTRY HIVE
      $REGISTRY_HIVE = array("HKEY_CLASSES_ROOT", "HKEY_CURRENT_CONFIG", "HKEY_CURRENT_USER",
                             "HKEY_DYN_DATA", "HKEY_LOCAL_MACHINE", "HKEY_USERS");

      $query = "SELECT *
                FROM `glpi_registrykeys`
                WHERE `computers_id` = '".$comp->getID()."'";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)!=0) {
            echo "<div class='center'><table class='tab_cadre_fixe'>";
            echo "<tr><th colspan='4'>".sprintf(_n('%d registry key found',
                                                   '%d registry keys found'),
                                                $DB->numrows($result))."</th></tr>\n";

            echo "<tr>";
            // TODO OCS
            /*<th>".__('OCSNG name')."</th>";*/
            echo "<th>".__('Hive')."</th>";
            echo "<th>".__('Path')."</th>";
            echo "<th>".__('Key/value')."</th></tr>\n";
            while ($data=$DB->fetch_assoc($result)) {
               echo "<tr class='tab_bg_1'>";
               // TODO OCS
            /* echo "<td>".$data["ocs_name"]."</td>";*/
               if (isset($REGISTRY_HIVE[$data["hive"]])) {
                  echo "<td>".$REGISTRY_HIVE[$data["hive"]]."</td>";
               } else {
                  echo "<td>(".$data["hive"].")</td>";
               }
               echo "<td>".$data["path"]."</td>";
               echo "<td>".$data["value"]."</td>";
               echo "</tr>";
            }
            echo "</table></div>\n\n";
         } else {
            echo "<div class='center'><table class='tab_cadre_fixe'>";
            echo "<tr class='tab_bg_2'><th>".__('Registry')."</th></tr>";
            echo "<tr class='tab_bg_2'><td class='center b'>".__('No key found in registry').
                 "</td></tr>";
            echo "</table></div>";
         }
      }
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {
      global $CFG_GLPI;

      if (!$withtemplate && $CFG_GLPI["use_ocs_mode"]) {
         switch ($item->getType()) {
            case 'Computer' :
               if ($_SESSION['glpishow_count_on_tabs']) {
                  return self::createTabEntry(self::getTypeName(2),
                                              countElementsInTable($this->getTable(),
                                                                   "computers_id
                                                                     = '".$item->getID()."'"));
               }
               return self::getTypeName(2);
         }
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      if ($item->getType()=='Computer') {
         self::showForComputer($item);
      }
      return true;
   }

}
?>