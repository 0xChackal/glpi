<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2009 by the INDEPNET Development Team.

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

/**
 *  Config class
 */
class Config extends CommonDBTM {

   // From CommonDBTM
   public $auto_message_on_action = false;

   static function getTypeName() {
      global $LANG;

      return $LANG['common'][12];
   }

   function defineTabs($ID,$withtemplate){
      global $LANG;

      $tabs[1] = $LANG['setup'][70];   // Main
      $tabs[2] = $LANG['setup'][119];  // Display
      $tabs[3] = $LANG['setup'][6];    // Prefs
      $tabs[4] = $LANG['setup'][184];  // Restrict
      $tabs[5] = $LANG['title'][24];   // Helpdesk
      $tabs[6] = $LANG['connect'][0];  // Conection
      $tabs[7] = $LANG['setup'][800];  // Slave
      $tabs[8] = $LANG['setup'][720];  // SysInfo

      return $tabs;
   }

   function showForm() {
      $this->showTabs(0);

      echo "<div id='tabcontent'></div>";
      echo "<script type='text/javascript'>loadDefaultTab();</script>";
   }

   /**
    * Prepare input datas for updating the item
    *
    *@param $input datas used to update the item
    *
    *@return the modified $input array
    *
   **/
   function prepareInputForUpdate($input) {

      if (isset($input["smtp_password"]) && empty($input["smtp_password"])) {
         unset($input["smtp_password"]);
      }
      if (isset($input["proxy_password"]) && empty($input["proxy_password"])) {
         unset($input["proxy_password"]);
      }

      if (isset($input["planning_begin"])) {
         $input["planning_begin"]=$input["planning_begin"].":00:00";
      }
      if (isset($input["planning_end"])) {
         $input["planning_end"]=$input["planning_end"].":00:00";
      }

      // Manage DB Slave process
      if (isset($input['_dbslave_status'])) {
         $already_active=DBConnection::isDBSlaveActive();
         if ($input['_dbslave_status']) {
            DBConnection::changeCronTaskStatus(true);
         	if (!$already_active) {
               DBConnection::createDBSlaveConfig();
            } else {
               DBConnection::saveDBSlaveConf($input["_dbreplicate_dbhost"],$input["_dbreplicate_dbuser"],
                               $input["_dbreplicate_dbpassword"],$input["_dbreplicate_dbdefault"]);
            }
         }
         if (!$input['_dbslave_status'] && $already_active) {
            DBConnection::deleteDBSlaveConfig();
            DBConnection::changeCronTaskStatus(false);
         }
      }

      // Matrix for Impact / Urgence / Priority
      if (isset($input['_matrix'])) {
         $tab = array();
         for ($urgency=1 ; $urgency<=5 ; $urgency++) {
            for ($impact=1 ; $impact<=5 ; $impact++) {
               $priority = $input["_matrix_${urgency}_${impact}"];
               $tab[$urgency][$impact]=$priority;
            }
         }
         $input['priority_matrix'] = exportArrayToDB($tab);

         $input['urgency_mask'] = 0;
         $input['impact_mask'] = 0;
         for ($i=1 ; $i<=5 ; $i++) {
            if ($input["_urgency_${i}"]) {
               $input['urgency_mask'] += (1<<$i);
            }
            if ($input["_impact_${i}"]) {
               $input['impact_mask'] += (1<<$i);
            }
         }
      }
      return $input;
   }

   /**
    * Print the config form for common options
    *
    *@param $target filename : where to go when done.
    *
    *@return Nothing (display)
    *
   **/
   function showFormMain($target) {
      global $DB, $LANG, $CFG_GLPI;

      if (!haveRight("config", "w")) {
         return false;
      }

      echo "<form name='form' action=\"$target\" method=\"post\">";
      echo "<div class='center' id='tabsbody'>";
      echo "<input type='hidden' name='id' value='" . $CFG_GLPI["id"] . "'>";

      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='4'>" . $LANG['setup'][70] . "</th></tr>";

      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['setup'][102] . " </td>";
      echo "<td><select name=\"event_loglevel\">";
      $level = $CFG_GLPI["event_loglevel"];
      echo "<option value=\"1\"";
      if ($level == 1) {
         echo " selected";
      }
      echo ">" . $LANG['setup'][103] . " </option>";
      echo "<option value=\"2\"";
      if ($level == 2) {
         echo " selected";
      }
      echo ">" . $LANG['setup'][104] . "</option>";
      echo "<option value=\"3\"";
      if ($level == 3) {
         echo " selected";
      }
      echo ">" . $LANG['setup'][105] . "</option>";
      echo "<option value=\"4\"";
      if ($level == 4) {
         echo " selected";
      }
      echo ">" . $LANG['setup'][106] . " </option>";
      echo "<option value=\"5\"";
      if ($level == 5) {
         echo " selected";
      }
      echo ">" . $LANG['setup'][107] . "</option>";
      echo "</select></td>";
      echo "<td class='center'>".$LANG['setup'][101]."</td><td>";
      Dropdown::showInteger('cron_limit', $CFG_GLPI["cron_limit"], 1, 30);
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td class='center'> " . $LANG['setup'][186] . " </td><td>";
      Dropdown::showGMT("time_offset", $CFG_GLPI["time_offset"]);
      echo "</td>";
      echo "<td class='center'> " . $LANG['setup'][185] . " </td><td>";
      Dropdown::showYesNo("use_log_in_files", $CFG_GLPI["use_log_in_files"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td colspan='4' class='center'>";
      echo "<strong>" . $LANG['Menu'][38] . "</strong></td></tr>";

      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['setup'][115] . "</td><td>";
      Dropdown::showInteger('default_alarm_threshold', $CFG_GLPI["default_alarm_threshold"], -1, 100);
      echo "</td>";
      echo "<td class='center'>" . $LANG['setup'][221] . "</td><td>";
      showDateFormItem("date_tax",$CFG_GLPI["date_tax"],false);
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['setup'][360] . "</td><td>";
      $tab=array(0=>$LANG['common'][59],1=>$LANG['entity'][8]);
      Dropdown::showFromArray('use_autoname_by_entity', $tab,
                        array('value' => $CFG_GLPI["use_autoname_by_entity"]));
      echo "</td>";
      echo "<td class='center'>&nbsp;</td><td>&nbsp;";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td colspan='4' class='center'>";
      echo "<strong>" . $LANG['common'][41] . "</strong></td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td class='center'>" . $LANG['setup'][246] . " (" . $LANG['common'][44] . ")</td><td>";
      Contract::dropdownAlert("default_contract_alert", $CFG_GLPI["default_contract_alert"]);
      echo "</td>";
      echo "<td class='center'>" . $LANG['setup'][247] . " (" . $LANG['common'][44] . ")</td><td>";
      echo "<select name=\"default_infocom_alert\">";
      echo "<option value=\"0\" " . ($CFG_GLPI["default_infocom_alert"] == 0 ? " selected " : "") .
             " >-----</option>";
      echo "<option value=\"" . pow(2, ALERT_END) . "\" " .
             ($CFG_GLPI["default_infocom_alert"] == pow(2, ALERT_END) ? " selected " : "") . " >" .
             $LANG['financial'][80] . " </option>";
      echo "</select>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td colspan='4' class='center'>";
      echo "<strong>" . $LANG['setup'][306] . "</strong></td></tr>";

      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['setup'][401] . " </td>";
      echo "<td><input type=\"text\" name=\"proxy_name\" value=\"" . $CFG_GLPI["proxy_name"] . "\">";
      echo "</td>";
      echo "<td class='center'>" . $LANG['setup'][402] . " </td>";
      echo "<td><input type=\"text\" name=\"proxy_port\" value=\"" . $CFG_GLPI["proxy_port"] . "\">";
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['setup'][403] . " </td>";
      echo "<td><input type=\"text\" name=\"proxy_user\" value=\"" . $CFG_GLPI["proxy_user"] . "\">";
      echo "</td>";
      echo "<td class='center'>" . $LANG['setup'][404] . " </td>";
      echo "<td><input type=\"password\" name=\"proxy_password\" value=\"\"></td></tr>";

      echo "<tr class='tab_bg_1'><td colspan='4' class='center'>";
      echo "<strong>" . $LANG['rulesengine'][77] . "</strong></td></tr>";
      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['rulesengine'][86] . " </td><td>";
      Dropdown::show('SoftwareCategory',
                     array('value'  => $CFG_GLPI["softwarecategories_id_ondelete"],
                           'name' => "softwarecategories_id_ondelete"));

      echo "</td><td class='center' colspan='2'></td></tr>";

      echo "<tr class='tab_bg_2'><td colspan='4' class='center'>";
      echo "<input type=\"submit\" name=\"update\" class=\"submit\" value=\"" .
             $LANG['buttons'][2] . "\" ></td></tr>";
      echo "</table></div>";
      echo "</form>";
   }

   /**
    * Print the config form for display
    *
    *@param $target filename : where to go when done.
    *
    *@return Nothing (display)
    *
   **/
   function showFormDisplay($target) {
      global $DB, $LANG, $CFG_GLPI;

      if (!haveRight("config", "w")) {
         return false;
      }

      echo "<form name='form' action=\"$target\" method=\"post\">";
      echo "<div class='center' id='tabsbody'>";
      echo "<input type='hidden' name='id' value='" . $CFG_GLPI["id"] . "'>";
      echo "<table class='tab_cadre_fixe'>";

      echo "<tr><th colspan='4'>" . $LANG['setup'][119] . "</th></tr>";

      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['setup'][149] . " </td><td>";
      Dropdown::showInteger("decimal_number",$CFG_GLPI["decimal_number"],1,4);
      echo "</td>";
      echo "<td class='center'>" . $LANG['setup'][148] . "</td><td>";
      echo "<select name='time_step'>";
      $steps = array (5,
                     10,
                     15,
                     20,
                     30,
                     60);
      foreach ($steps as $step) {
         echo "<option value='$step'" . ($CFG_GLPI["time_step"] == $step ? " selected " : "") . ">";
         echo "$step</option>";
      }
      echo "</select>&nbsp;" . $LANG['job'][22];
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['setup'][112] . "</td><td>";
      Dropdown::showInteger('cut', $CFG_GLPI["cut"], 50, 500,50);
      echo "</td>";
      $plan_begin = explode(":", $CFG_GLPI["planning_begin"]);
      $plan_end = explode(":", $CFG_GLPI["planning_end"]);
      echo "<td class='center'>" . $LANG['setup'][223] . "</td><td>";
      Dropdown::showInteger('planning_begin', $plan_begin[0], 0, 24);
      echo "&nbsp;->&nbsp;";
      Dropdown::showInteger('planning_end', $plan_end[0], 0, 24);
      echo " </td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td class='center'>" . $LANG['setup'][111]." <br> ".$LANG['common'][58]."</td><td>";
      Dropdown::showInteger("list_limit_max",$CFG_GLPI["list_limit_max"],5,200,5);
      echo "</td><td class='center'>".$LANG['setup'][10]."</td><td>&nbsp;";
      $values = array (REALNAME_BEFORE=>$LANG['common'][48]." ".$LANG['common'][43],
                       FIRSTNAME_BEFORE=>$LANG['common'][43]." ".$LANG['common'][48]);
      echo "<select name='names_format'>";
      foreach ($values as $key=>$val) {
         echo "<option value='$key'" . ($CFG_GLPI["names_format"] == $key ? " selected " : "") . ">";
         echo "$val</option>";
      }
      echo "</select>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td colspan='4' class='center'>";
      echo "<strong>" . $LANG['setup'][6] . "</strong></td></tr>";

      echo "<tr class='tab_bg_2'><td class='center'> " . $LANG['setup'][118] . " </td>";
      echo "<td colspan='3' class='center'>";
      echo "<textarea cols='70' rows='4' name='text_login' >";
      echo $CFG_GLPI["text_login"];
      echo "</textarea>";
      echo "</td></tr>";
      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['setup'][407] . "</td>";
      echo "<td><input size='30' type=\"text\" name=\"helpdesk_doc_url\" value=\"" .
                 $CFG_GLPI["helpdesk_doc_url"] . "\"></td>";
      echo "<td class='center'>" . $LANG['setup'][408] . "</td>";
      echo "<td><input size='30' type=\"text\" name=\"central_doc_url\" value=\"" .
                 $CFG_GLPI["central_doc_url"] . "\"></td></tr>";

      echo "<tr class='tab_bg_1'><td colspan='4' class='center'>";
      echo "<strong>" . $LANG['setup'][147] . "</strong></td></tr>";

      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['setup'][120] . " </td><td>";
      Dropdown::showYesNo("use_ajax", $CFG_GLPI["use_ajax"]);
      echo "</td>";
      echo "<td class='center'>" . $LANG['setup'][127] . " </td><td>";
      Dropdown::showYesNo("use_ajax_autocompletion", $CFG_GLPI["use_ajax_autocompletion"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['setup'][121] . "</td>";
      echo "<td><input type=\"text\" size='1' name=\"ajax_wildcard\" value=\"" .
                 $CFG_GLPI["ajax_wildcard"] . "\"></td>";
      echo "<td class='center'>" . $LANG['setup'][122] . "</td><td>";
      Dropdown::showInteger('dropdown_max', $CFG_GLPI["dropdown_max"], 0, 200);
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['setup'][123] . "</td><td>";
      Dropdown::showInteger('ajax_limit_count', $CFG_GLPI["ajax_limit_count"], 0, 200);
      echo "</td><td colspan='2'>&nbsp;</td></tr>";

      echo "<tr class='tab_bg_2'><td colspan='4' class='center'>";
      echo "<input type=\"submit\" name=\"update\" class=\"submit\" value=\"" .
             $LANG['buttons'][2] . "\" ></td></tr>";
      echo "</table></div>";
      echo "</form>";
   }

   /**
    * Print the config form for restrictions
    *
    *@param $target filename : where to go when done.
    *
    *@return Nothing (display)
    *
   **/
   function showFormRestrict($target) {
      global $DB, $LANG, $CFG_GLPI;

      if (!haveRight("config", "w")) {
         return false;
      }

      echo "<form name='form' action=\"$target\" method=\"post\">";
      echo "<div class='center' id='tabsbody'>";
      echo "<input type='hidden' name='id' value='" . $CFG_GLPI["id"] . "'>";
      echo "<table class='tab_cadre_fixe'>";

      echo "<tr><th colspan='4'>" . $LANG['setup'][270] . "</th></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td class='center'> " . $LANG['setup'][271] . " </td>";
      echo "<td>";
      $this->dropdownGlobalManagement ("monitors_management_restrict",
                                       $CFG_GLPI["monitors_management_restrict"]);
      echo "</td><td class='center'> " . $LANG['setup'][272] . " </td><td>";
      $this->dropdownGlobalManagement ("peripherals_management_restrict",
                                       $CFG_GLPI["peripherals_management_restrict"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td class='center'> " . $LANG['setup'][273] . " </td><td>";
      $this->dropdownGlobalManagement ("phones_management_restrict",
                                       $CFG_GLPI["phones_management_restrict"]);
      echo "</td><td class='center'> " . $LANG['setup'][275] . " </td><td>";
      $this->dropdownGlobalManagement("printers_management_restrict",
                                      $CFG_GLPI["printers_management_restrict"]);
      echo "</td></tr>";

      echo "<tr><th colspan='2'>" . $LANG['setup'][134]. "</th><th colspan='2'></th></tr>";

      echo "<tr class='tab_bg_2'><td class='center'> " . $LANG['setup'][133] . " </td><td>";
      Dropdown::showYesNo("use_ocs_mode", $CFG_GLPI["use_ocs_mode"]);
      echo "</td><td class='center'colspan='2'></tr>";

      echo "<tr><th colspan='4' class='center'>" . $LANG['login'][10] . "</th></tr>";

      echo "<tr class='tab_bg_2'><td class='center'> " . $LANG['setup'][124] . " </td><td>";
      Dropdown::showYesNo("is_users_auto_add", $CFG_GLPI["is_users_auto_add"]);
      echo "</td>";
      echo "<td class='center'> " . $LANG['setup'][613] . " </td><td class='center'>";
      Dropdown::showYesNo("use_noright_users_add", $CFG_GLPI["use_noright_users_add"]);
      echo " </td></tr>";

      echo "<tr><th colspan='4' class='center'>" . $LANG['Menu'][20] . "</th></tr>";

      echo "<tr class='tab_bg_2'><td class='center'> " . $LANG['setup'][117] . " </td><td>";
      Dropdown::showYesNo("use_public_faq", $CFG_GLPI["use_public_faq"]);
      echo " </td><td class='center' colspan='2'></td></tr>";

      echo "<tr class='tab_bg_2'><td colspan='4' class='center'>";
      echo "<input type=\"submit\" name=\"update\" class=\"submit\" value=\"" .
             $LANG['buttons'][2] . "\" ></td></tr>";
      echo "</table></div>";
      echo "</form>";
   }

   /**
    * Print the config form for connections
    *
    *@param $target filename : where to go when done.
    *
    *@return Nothing (display)
    *
   **/
   function showFormConnection($target) {
      global $DB, $LANG, $CFG_GLPI;

      if (!haveRight("config", "w")) {
         return false;
      }

      echo "<form name='form' action=\"$target\" method=\"post\">";
      echo "<div class='center' id='tabsbody'>";
      echo "<input type='hidden' name='id' value='" . $CFG_GLPI["id"] . "'>";
      echo "<table class='tab_cadre_fixe'>";

      echo "<tr><th colspan='4'>" . $LANG['setup'][280]. " (" . $LANG['peripherals'][32] . ")</th>";
      echo "</tr>";

      echo "<tr><th>&nbsp;</th><th>" . $LANG['setup'][281] . "</th>";
      echo "<th>" . $LANG['setup'][282] . "</th><th>&nbsp;</th></tr>";

      echo "<tr class='tab_bg_2'><td class='center'> " . $LANG['common'][18] . " </td>";
      echo "<td>" . $LANG['setup'][283] . "&nbsp;:&nbsp;";
      Dropdown::showYesNo("is_contact_autoupdate", $CFG_GLPI["is_contact_autoupdate"]);
      echo "</td><td>" . $LANG['setup'][284] . "&nbsp;:&nbsp;";
      Dropdown::showYesNo("is_contact_autoclean", $CFG_GLPI["is_contact_autoclean"]);
      echo "</td><td>&nbsp;</td></tr>";

      echo "<tr class='tab_bg_2'><td class='center'> " . $LANG['common'][34] . " </td>";
      echo "<td>" . $LANG['setup'][283] . "&nbsp;:&nbsp;";
      Dropdown::showYesNo("is_user_autoupdate", $CFG_GLPI["is_user_autoupdate"]);
      echo "</td><td>" . $LANG['setup'][284] . "&nbsp;:&nbsp;";
      Dropdown::showYesNo("is_user_autoclean", $CFG_GLPI["is_user_autoclean"]);
      echo " </td><td>&nbsp;</td></tr>";

      echo "<tr class='tab_bg_2'><td class='center'> " . $LANG['common'][35] . " </td>";
      echo "<td>" . $LANG['setup'][283] . "&nbsp;:&nbsp;";
      Dropdown::showYesNo("is_group_autoupdate", $CFG_GLPI["is_group_autoupdate"]);
      echo "</td><td>" . $LANG['setup'][284] . "&nbsp;:&nbsp;";
      Dropdown::showYesNo("is_group_autoclean", $CFG_GLPI["is_group_autoclean"]);
      echo "</td><td>&nbsp;</td></tr>";

      echo "<tr class='tab_bg_2'><td class='center'> " . $LANG['common'][15] . " </td>";
      echo "<td>" . $LANG['setup'][283] . "&nbsp;:&nbsp;";
      Dropdown::showYesNo("is_location_autoupdate", $CFG_GLPI["is_location_autoupdate"]);
      echo "</td><td>" . $LANG['setup'][284] . "&nbsp;:&nbsp;";
      Dropdown::showYesNo("is_location_autoclean", $CFG_GLPI["is_location_autoclean"]);
      echo " </td><td>&nbsp;</td></tr>";

      echo "<tr class='tab_bg_2'><td class='center'> " . $LANG['state'][0] . " </td><td>";
      State::dropdownBehaviour("state_autoupdate_mode", $LANG['setup'][197],
                             $CFG_GLPI["state_autoupdate_mode"]);
      echo "</td><td>";
      State::dropdownBehaviour("state_autoclean_mode", $LANG['setup'][196],
                             $CFG_GLPI["state_autoclean_mode"]);
      echo " </td><td>&nbsp;</td></tr>";

      echo "<tr class='tab_bg_2'><td colspan='4' class='center'>";
      echo "<input type=\"submit\" name=\"update\" class=\"submit\" value=\"" .
             $LANG['buttons'][2] . "\" ></td></tr>";
      echo "</table></div>";
      echo "</form>";
   }

   /**
    * Print the config form for slave DB
    *
    *@param $target filename : where to go when done.
    *
    *@return Nothing (display)
    *
   **/
   function showFormDBSlave($target) {
      global $DB, $LANG, $CFG_GLPI, $DBSlave;

      if (!haveRight("config", "w")) {
         return false;
      }

      echo "<form name='form' action=\"$target\" method=\"post\">";
      echo "<div class='center' id='tabsbody'>";
      echo "<input type='hidden' name='id' value='" . $CFG_GLPI["id"] . "'>";
      echo "<table class='tab_cadre_fixe'>";
      $active = DBConnection::isDBSlaveActive();

      echo "<tr class='tab_bg_2'><th colspan='4'>" . $LANG['setup'][800] . "</th></tr>";

      echo "<tr class='tab_bg_2'><td class='center'> " . $LANG['setup'][801] . " </td><td>";
      Dropdown::showYesNo("_dbslave_status", $active);
      echo " </td><td colspan='2'></td></tr>";

      if ($active){
         $DBSlave = DBConnection::getDBSlaveConf();

         echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['install'][30] . " </td>";
         echo "<td><input type=\"text\" name=\"_dbreplicate_dbhost\" size='40' value=\"" .
                    $DBSlave->dbhost . "\"></td>";
         echo "<td class='center'>" . $LANG['setup'][802] . "</td><td>";
         echo "<input type=\"text\" name=\"_dbreplicate_dbdefault\" value=\"" .
                $DBSlave->dbdefault . "\">";
         echo "</td></tr>";

         echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['install'][31] . "</td><td>";
         echo "<input type=\"text\" name=\"_dbreplicate_dbuser\" value=\"" . $DBSlave->dbuser . "\">";
         echo "<td class='center'>" . $LANG['install'][32] . "</td><td>";
         echo "<input type=\"password\" name=\"_dbreplicate_dbpassword\" value=\"" .
                $DBSlave->dbpassword . "\">";
         echo "</td></tr>";

         echo "<tr class='tab_bg_2'><th colspan='4'>" . $LANG['setup'][704] . "</th></tr>";

         echo "<tr class='tab_bg_2'><td class='center'> " . $LANG['setup'][203] . " </td>";
         echo "<td colspan='3'>";
         echo "<input type=\"text\" size='50' name=\"dbreplicate_email\" value=\"" .
                $CFG_GLPI["dbreplicate_email"] . "\">";
         echo "</td></tr>";

         echo "<tr class='tab_bg_2'>";
         if ($DBSlave->connected && !$DB->isSlave()) {
            echo "<td colspan='4' class='center'>" . $LANG['setup'][803] . "&nbsp;: ";
            echo timestampToString(DBConnection::getReplicateDelay(),1);
            echo "</td>";
         } else
            echo "<td colspan='4'></td>";
         echo "</tr>";
      }

      echo "<tr class='tab_bg_2'><td colspan='4' class='center'>";
      echo "<input type=\"submit\" name=\"update\" class=\"submit\" value=\"" .
             $LANG['buttons'][2] . "\" ></td></tr>";
      echo "</table></div>";
      echo "</form>";
   }

   /**
    * Print the config form for connections
    *
    *@param $target filename : where to go when done.
    *
    *@return Nothing (display)
    *
   **/
   function showFormHelpdesk($target) {
      global $DB, $LANG, $CFG_GLPI;

      if (!haveRight("config", "w")) {
         return false;
      }

      echo "<form name='form' action=\"$target\" method=\"post\">";
      echo "<div class='center' id='tabsbody'>";
      echo "<table class='tab_cadre_fixe'>";


      echo "<tr><th colspan='4' class='center'>" . $LANG['mailing'][9]. "</th></tr>";

      echo "<tr class='tab_bg_2'><td class='center'> " . $LANG['setup'][219] . " </td><td>";
      Dropdown::showYesNo("use_anonymous_helpdesk", $CFG_GLPI["use_anonymous_helpdesk"]);
      echo "</td><td class='center'>" . $LANG['setup'][610] . "</td><td>";
      Dropdown::showYesNo("is_ticket_title_mandatory", $CFG_GLPI["is_ticket_title_mandatory"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'><td class='center'> " . $LANG['setup'][611] . " </td><td>";
      Dropdown::showYesNo("is_ticket_content_mandatory", $CFG_GLPI["is_ticket_content_mandatory"]);
      echo "</td><td class='center'>" . $LANG['setup'][612] . "</td><td>";
      Dropdown::showYesNo("is_ticket_category_mandatory", $CFG_GLPI["is_ticket_category_mandatory"]);
      echo "</td></tr>";


      echo "<tr><th colspan='4'>" . $LANG['title'][24] . "</th></tr>";

      echo "<tr class='tab_bg_2'><td class='center'> " . $LANG['setup'][116] . " </td><td>";
      Dropdown::showYesNo("use_auto_assign_to_tech", $CFG_GLPI["use_auto_assign_to_tech"]);
      echo "</td>";
      echo "<td class='center'>" . $LANG['setup'][405] . "</td><td>";
      Dropdown::showYesNo("add_followup_on_update_ticket", $CFG_GLPI["add_followup_on_update_ticket"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['tracking'][37] . "</td><td>";
      Dropdown::showYesNo("keep_tickets_on_delete", $CFG_GLPI["keep_tickets_on_delete"]);
      echo "</td>";
      echo "<td class='center'>" . $LANG['setup'][409] . "</td><td>";
      Dropdown::show('DocumentCategory',
                     array('value'  => $CFG_GLPI["documentcategories_id_forticket"],
                           'name' => "documentcategories_id_forticket"));
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['setup'][608] . "</td><td>";
      Dropdown::showYesNo("default_software_helpdesk_visible",
                    $CFG_GLPI["default_software_helpdesk_visible"]);
      echo "</td>";
      echo "<td class='center'> " . $LANG['mailgate'][7] . " (".$LANG['common'][44].")</td><td>";
      echo "<input type=\"text\" size='8' name=\"default_mailcollector_filesize_max\" value=\"" .
             $CFG_GLPI["default_mailcollector_filesize_max"] . "\">&nbsp;".
             $LANG['mailgate'][8]." - ".getSize($CFG_GLPI["default_mailcollector_filesize_max"]);
      echo "</td></tr>";

      echo "</table><table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='7'>" . $LANG['help'][1];
      echo "<input type='hidden' name='_matrix' value='1'></th></tr>";

      echo "<tr class='tab_bg_2'><td class='b right' colspan='2'>".$LANG['joblist'][30]."&nbsp;:</td>";
      for ($impact=5, $msg=47 ; $impact>=1 ; $impact--, $msg++) {
         echo "<td class='center'>".$LANG['help'][$msg]."&nbsp;: ";
         if ($impact==3) {
            $isimpact[3] = 1;
            echo "<input type='hidden' name='_impact_3' value='1'>";
         } else {
            $isimpact[$impact] = $CFG_GLPI['impact_mask']&(1<<$impact);
            Dropdown::showYesNo("_impact_${impact}", $isimpact[$impact]);
         }
         echo "</td>";
      }
      echo "</tr>";
      echo "<tr class='tab_bg_1'><td class='b' colspan='2'>".$LANG['joblist'][29]."</td>";
      for ($impact=5, $msg=47 ; $impact>=1 ; $impact--, $msg++) {
         echo "<td>&nbsp;</td>";
      }
      echo "</tr>";
      for ($urgency=5, $msg=42 ; $urgency>=1 ; $urgency--, $msg++) {
         echo "<tr class='tab_bg_1'><td>".$LANG['help'][$msg]."&nbsp;:</td>";
         echo "<td>";
         if ($urgency==3) {
            $isurgency[3] = 1;
            echo "<input type='hidden' name='_urgency_3' value='1'>";
         } else {
            $isurgency[$urgency] = $CFG_GLPI['urgency_mask']&(1<<$urgency);
            Dropdown::showYesNo("_urgency_${urgency}", $isurgency[$urgency]);
         }
         echo "</td>";
         for ($impact=5 ; $impact>=1 ; $impact--) {
            $pri = round(($urgency+$impact)/2);
            if (isset($CFG_GLPI['priority_matrix'][$urgency][$impact])) {
               $pri = $CFG_GLPI['priority_matrix'][$urgency][$impact];
            }
            if ($isurgency[$urgency] && $isimpact[$impact]) {
               $bgcolor=$_SESSION["glpipriority_$pri"];
               echo "<td class='center' bgcolor='$bgcolor'>";
               Ticket::dropdownPriority("_matrix_${urgency}_${impact}",$pri);
               echo "</td>";
            } else {
               echo "<td><input type='hidden' name='_matrix_${urgency}_${impact}' value='$pri'></td>";
            }
         }
         echo "</tr>\n";
      }
      echo "</tr>\n";

      echo "<tr class='tab_bg_2'><td colspan='7' class='center'>";
      echo "<input type='hidden' name='id' value='" . $CFG_GLPI["id"] . "'>";
      echo "<input type='submit' name='update' class='submit' value=\"" .
             $LANG['buttons'][2] . "\" ></td></tr>";
      echo "</table></div>";
      echo "</form>";
   }

   /**
    * Print the config form for default user prefs
    *
    *@param $target filename : where to go when done.
    *@param $data array containing datas (CFG_GLPI for global config / glpi_users fields for user prefs)
    *
    *@return Nothing (display)
    *
   **/
   function showFormUserPrefs($target,$data=array()) {
      global $DB, $LANG, $CFG_GLPI;

      $oncentral=($_SESSION["glpiactiveprofile"]["interface"]=="central");
      $userpref=false;
      if (isset($data['last_login'])) {
         $userpref=true;
      }

      echo "<form name='form' action=\"$target\" method=\"post\">";
      echo "<div class='center' id='tabsbody'>";
      echo "<input type='hidden' name='id' value='" . $data["id"] . "'>";
      echo "<table class='tab_cadre_fixe'>";

      echo "<tr><th colspan='4'>" . $LANG['setup'][119] . "</th></tr>";

      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['setup'][128] . " </td>";
      echo "<td><select name=\"date_format\">";

      $date_formats=array(0 => "YYYY-MM-DD",
                          1 => "DD-MM-YYYY",
                          2 => "MM-DD-YYYY");
      foreach ($date_formats as $key => $val) {
         echo "<option value=\"$key\"";
         if ($data["date_format"] == $key) {
            echo " selected";
         }
         echo ">$val</option>";
      }
      echo "</select></td>";
      echo "<td class='center'>" . $LANG['setup'][150] . " </td>";
      echo "<td><select name=\"number_format\">";
      echo "<option value=\"0\"";
      if ($data["number_format"] == 0) {
         echo " selected";
      }
      echo ">1 234.56</option>";
      echo "<option value=\"1\"";
      if ($data["number_format"] == 1) {
         echo " selected";
      }
      echo ">1,234.56</option>";
      echo "<option value=\"2\"";
      if ($data["number_format"] == 2) {
         echo " selected";
      }
      echo ">1 234,56</option>";
      echo "</select></td></tr>";

      if ($oncentral) {
         echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['setup'][129] . " </td><td>";
         Dropdown::showYesNo("is_ids_visible", $data["is_ids_visible"]);
         echo "</td>";
         echo "<td class='center'>" . $LANG['setup'][131] . "</td><td>";
         Dropdown::showInteger('dropdown_chars_limit', $data["dropdown_chars_limit"], 20, 100);
         echo "</td></tr>";

         echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['setup'][132] . "</td><td>";
         Dropdown::showYesNo('use_flat_dropdowntree', $data["use_flat_dropdowntree"]);
         echo "</td><td class='center'>&nbsp;</td><td>&nbsp;";
         echo "</td></tr>";
      }

      echo "<tr class='tab_bg_2'>";
      echo "<td class='center'>" . $LANG['setup'][111]."</td><td>";
      // Limit using global config
      Dropdown::showInteger('list_limit',
                      ($data['list_limit']<$CFG_GLPI['list_limit_max'] ? $data['list_limit']
                                                                       : $CFG_GLPI['list_limit_max']),
                      5,$CFG_GLPI['list_limit_max'],5);
      echo "</td>";
      echo "<td class='center'>" . ($userpref?$LANG['setup'][41]:$LANG['setup'][113]) . " </td><td>";

      if (haveRight("config","w") || ! GLPI_DEMO_MODE) {
         Dropdown::showLanguages("language", $data["language"]);
      } else {
         echo "&nbsp;";
      }

      echo "</td></tr>";

      if ($oncentral) {
         echo "<tr class='tab_bg_1'><td colspan='4' class='center'>";
         echo "<strong>" . $LANG['title'][24] . "</strong></td></tr>";

         echo "<tr class='tab_bg_2'><td class='center'> " . $LANG['setup'][110] . " </td><td>";
         Dropdown::showYesNo("show_jobs_at_login", $data["show_jobs_at_login"]);
         echo " </td><td>&nbsp;</td><td>&nbsp;</td></tr>";

         echo "<tr class='tab_bg_2'><td class='center'> " . $LANG['setup'][39] . " </td><td>";
         Dropdown::showYesNo("followup_private", $data["followup_private"]);
         echo "</td>";
         echo "<td class='center'> " . $LANG['job'][44] . " </td><td>";
         Dropdown::show('RequestType',
                        array('value'  => $data["default_requesttypes_id"],
                              'name' => "default_requesttypes_id"));
         echo "</td></tr>";

         echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['setup'][114] . "</td>";
         echo "<td colspan='3'>";

         echo "<table><tr>";
         echo "<td bgcolor='" . $data["priority_1"] . "'>1&nbsp;:&nbsp;";
         echo "<input type='text' name='priority_1' size='7' value='" .
                $data["priority_1"] . "'></td>";
         echo "<td bgcolor='" . $data["priority_2"] . "'>2&nbsp;:&nbsp;";
         echo "<input type='text' name='priority_2' size='7' value='" .
                $data["priority_2"] . "'></td>";
         echo "<td bgcolor='" . $data["priority_3"] . "'>3&nbsp;:&nbsp;";
         echo "<input type='text' name='priority_3' size='7' value='" .
                $data["priority_3"] . "'></td>";
         echo "<td bgcolor='" . $data["priority_4"] . "'>4&nbsp;:&nbsp;";
         echo "<input type='text' name='priority_4' size='7' value='" .
                $data["priority_4"] . "'></td>";
         echo "<td bgcolor='" . $data["priority_5"] . "'>5&nbsp;:&nbsp;";
         echo "<input type='text' name='priority_5' size='7' value='" .
                $data["priority_5"] . "'></td>";
         echo "<td bgcolor='" . $data["priority_6"] . "'>6&nbsp;:&nbsp;";
         echo "<input type='text' name='priority_6' size='7' value='" .
                $data["priority_6"] . "'></td>";
         echo "</tr></table>";

         echo "</td></tr>";

         echo "<tr class='tab_bg_1'><td colspan='4' class='center'>";
         echo "<strong>" . $LANG['softwarecategories'][5] . "</strong></td></tr>";
         echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['softwarecategories'][4]."</td>";
         echo "<td>";
         Dropdown::showYesNo("is_categorized_soft_expanded", $data["is_categorized_soft_expanded"]);
         echo "</td><td class='center'>" . $LANG['softwarecategories'][3] . "</td><td>";
         Dropdown::showYesNo("is_not_categorized_soft_expanded", $data["is_not_categorized_soft_expanded"]);
         echo "</td></tr>";
      }

      echo "<tr class='tab_bg_2'><td colspan='4' class='center'>";
      echo "<input type=\"submit\" name=\"update\" class=\"submit\" value=\"" .
             $LANG['buttons'][2] . "\" ></td></tr>";
      echo "</table></div>";
      echo "</form>";
   }

   /**
    * Print the mailing config form
    *
    *@param $target filename : where to go when done.
    *@param $tabs integer : ID of the tab to display
    *
    *@return Nothing (display)
    *
   **/
   function showFormMailing($target,$tabs) {
      global $DB, $LANG, $CFG_GLPI;

      if (!haveRight("config", "w")) {
         return false;
      }

      echo "<form action=\"$target\" method=\"post\">";
      echo "<input type='hidden' name='id' value='" . $CFG_GLPI["id"] . "'>";

      switch ($tabs) {
         case 1 :
            echo "<div class='center'><table class='tab_cadre_fixe'>";
            echo "<tr><th colspan='2'>" . $LANG['setup'][201] . "</th></tr>";
            echo "<tr class='tab_bg_2'><td >" . $LANG['setup'][202] . "</td><td>";
            Dropdown::showYesNo("use_mailing", $CFG_GLPI["use_mailing"]);
            echo "</td></tr>";
            echo "<tr class='tab_bg_2'><td >" . $LANG['setup'][203] . "</td>";
            echo "<td><input type=\"text\" name=\"admin_email\" size='40' value=\"" .
                       $CFG_GLPI["admin_email"] . "\">";
            if (!isValidEmail($CFG_GLPI["admin_email"])) {
               echo "<span class='red'>&nbsp;".$LANG['mailing'][110]."</span>";
            }
            echo " </td></tr>";
            echo "<tr class='tab_bg_2'><td >" . $LANG['setup'][207] . "</td>";
            echo "<td><input type=\"text\" name=\"admin_reply\" size='40' value=\"" .
                       $CFG_GLPI["admin_reply"] . "\">";
            if (!isValidEmail($CFG_GLPI["admin_reply"])) {
               echo "<span class='red'>&nbsp;".$LANG['mailing'][110]."</span>";
            }
            echo " </td></tr>";
            echo "<tr class='tab_bg_2'><td >" . $LANG['setup'][204] . "</td>";
            echo "<td><textarea cols='60' rows='3' name=\"mailing_signature\" >".
                       $CFG_GLPI["mailing_signature"]."</textarea></td></tr>";
            echo "<tr class='tab_bg_2'><td >" . $LANG['setup'][226] . "</td><td>";
            Dropdown::showYesNo("show_link_in_mail", $CFG_GLPI["show_link_in_mail"]);
            echo "</td></tr>";
            echo "<tr class='tab_bg_2'><td >" . $LANG['setup'][227] . "</td>";
            echo "<td><input type=\"text\" name=\"url_base\" size='40' value=\"" .
                       $CFG_GLPI["url_base"] . "\"> </td></tr>";
            if (!function_exists('mail')) {
               echo "<tr class='tab_bg_2'><td class='center' colspan='2'>";
               echo "<span class='red'>" . $LANG['setup'][217] . " : </span>";
               echo "<span>" . $LANG['setup'][218] . "</span></td></tr>";
            }
            echo "<tr class='tab_bg_2'>";
            echo "<td>" . $LANG['setup'][245] . " " . $LANG['setup'][244] . "</td><td>";
            echo "<select name='cartridges_alert_repeat'> ";
            echo "<option value='0' " .
                   ($CFG_GLPI["cartridges_alert_repeat"] == 0 ? "selected" : "") . " >" .
                   $LANG['setup'][307] . "</option>";
            echo "<option value='" . DAY_TIMESTAMP . "' " .
                   ($CFG_GLPI["cartridges_alert_repeat"] == DAY_TIMESTAMP ? "selected" : "") . " >" .
                   $LANG['setup'][305] . "</option>";
            echo "<option value='" . WEEK_TIMESTAMP . "' " .
                   ($CFG_GLPI["cartridges_alert_repeat"] == WEEK_TIMESTAMP ? "selected" : "") . " >" .
                   $LANG['setup'][308] . "</option>";
            echo "<option value='" . MONTH_TIMESTAMP . "' " .
                   ($CFG_GLPI["cartridges_alert_repeat"] == MONTH_TIMESTAMP ? "selected" : "") . " >" .
                   $LANG['setup'][309] . "</option>";
            echo "</select>";
            echo "</td></tr>";
            echo "<tr class='tab_bg_2'>";
            echo "<td >" . $LANG['setup'][245] . " " . $LANG['setup'][243] . "</td><td>";
            echo "<select name='consumables_alert_repeat'> ";
            echo "<option value='0' " .
                   ($CFG_GLPI["consumables_alert_repeat"] == 0 ? "selected" : "") . " >" .
                   $LANG['setup'][307] . "</option>";
            echo "<option value='" . DAY_TIMESTAMP . "' " .
                   ($CFG_GLPI["consumables_alert_repeat"] == DAY_TIMESTAMP ? "selected" : "")." >".
                   $LANG['setup'][305] . "</option>";
            echo "<option value='" . WEEK_TIMESTAMP . "' " .
                   ($CFG_GLPI["consumables_alert_repeat"] == WEEK_TIMESTAMP ? "selected" : "")." >".
                   $LANG['setup'][308] . "</option>";
            echo "<option value='" . MONTH_TIMESTAMP . "' " .
                   ($CFG_GLPI["consumables_alert_repeat"] == MONTH_TIMESTAMP ? "selected" : "")." >".
                   $LANG['setup'][309] . "</option>";
            echo "</select>";
            echo "</td></tr>";
            echo "<tr class='tab_bg_2'><td >" . $LANG['setup'][264] . "</td><td>";
            Dropdown::showYesNo("use_licenses_alert", $CFG_GLPI["use_licenses_alert"]);
            echo "</td></tr>";
            echo "<tr class='tab_bg_1'><td colspan='2'>&nbsp;</td></tr>";
            echo "<tr class='tab_bg_2'><td >" . $LANG['setup'][231] . "</td><td>&nbsp; ";
            $mail_methods=array(MAIL_MAIL=>$LANG['setup'][650],
                                MAIL_SMTP=>$LANG['setup'][651],
                                MAIL_SMTPSSL=>$LANG['setup'][652],
                                MAIL_SMTPTLS=>$LANG['setup'][653]);
            Dropdown::showFromArray("smtp_mode",$mail_methods,
                                 array('value' => $CFG_GLPI["smtp_mode"]));
            echo "</td></tr>";
            echo "<tr class='tab_bg_2'><td >" . $LANG['setup'][232] . "</td>";
            echo "<td><input type=\"text\" name=\"smtp_host\" size='40' value=\"" .
                       $CFG_GLPI["smtp_host"] . "\"></td></tr>";
            echo "<tr class='tab_bg_2'><td >" . $LANG['setup'][234] . "</td>";
            echo "<td><input type=\"text\" name=\"smtp_username\" size='40' value=\"" .
                       $CFG_GLPI["smtp_username"] . "\"></td></tr>";
            echo "<tr class='tab_bg_2'><td >" . $LANG['setup'][235] . "</td>";
            echo "<td><input type=\"password\" name=\"smtp_password\" size='40' value=\"\"></td></tr>";
            echo "<tr class='tab_bg_2'><td class='center' colspan='2'>";
            echo "<input type=\"submit\" name=\"update_mailing\" class=\"submit\" value=\"" .
                   $LANG['buttons'][2] . "\" ></td></tr>";
            echo "</table></div></form>";

            echo "<form action=\"$target\" method=\"post\">";
            echo "<div class='center'><table class='tab_cadre_fixe'><tr>";
            echo "<th colspan='2'>" . $LANG['setup'][229] . "</th></tr>";
            echo "<tr class='tab_bg_2'><td class='center'>";
            echo "<input class=\"submit\" type=\"submit\" name=\"test_smtp_send\" value=\"" .
                   $LANG['buttons'][2] . "\">";
            echo " </td></tr></table></div>";
            break;

         case 2 :
            $profiles[USER_MAILING_TYPE . "_" . ADMIN_MAILING] = $LANG['setup'][237];
            $profiles[USER_MAILING_TYPE . "_" . ADMIN_ENTITY_MAILING] = $LANG['setup'][237]." ".
                                                                        $LANG['entity'][0];
            $profiles[USER_MAILING_TYPE . "_" . TECH_MAILING] = $LANG['common'][10];
            $profiles[USER_MAILING_TYPE . "_" . AUTHOR_MAILING] = $LANG['job'][4];
            $profiles[USER_MAILING_TYPE . "_" . RECIPIENT_MAILING] = $LANG['job'][3];
            $profiles[USER_MAILING_TYPE . "_" . USER_MAILING] = $LANG['common'][34] . " " .
                                                                $LANG['common'][1];
            $profiles[USER_MAILING_TYPE . "_" . ASSIGN_MAILING] = $LANG['setup'][239];
            $profiles[USER_MAILING_TYPE . "_" . ASSIGN_ENT_MAILING] = $LANG['financial'][26];
            $profiles[USER_MAILING_TYPE . "_" . ASSIGN_GROUP_MAILING] = $LANG['setup'][248];
            $profiles[USER_MAILING_TYPE . "_" .
                      SUPERVISOR_ASSIGN_GROUP_MAILING] = $LANG['common'][64]." ".$LANG['setup'][248];
            $profiles[USER_MAILING_TYPE . "_" .
                      SUPERVISOR_AUTHOR_GROUP_MAILING] = $LANG['common'][64]." ".$LANG['setup'][249];
            asort($profiles);
            $query = "SELECT `id`, `name`
                      FROM `glpi_profiles`
                      ORDER BY `name`";
            $result = $DB->query($query);
            while ($data = $DB->fetch_assoc($result)) {
               $profiles[PROFILE_MAILING_TYPE ."_" . $data["id"]] = $LANG['profiles'][22] . " " .
                                                                    $data["name"];
            }
            $query = "SELECT `id`, `name`
                      FROM `glpi_groups`
                      ORDER BY `name`";
            $result = $DB->query($query);
            while ($data = $DB->fetch_assoc($result)) {
               $profiles[GROUP_MAILING_TYPE ."_" . $data["id"]] = $LANG['common'][35] . " " .
                                                                  $data["name"];
            }
            echo "<div class='center'>";
            echo "<input type='hidden' name='update_notifications' value='1'>";
            // ADMIN
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr><th colspan='3'>" . $LANG['setup'][211] . "</th></tr>";
            echo "<tr class='tab_bg_2'>";
            showFormMailingType("new", $profiles);
            echo "</tr>";
            echo "<tr><th colspan='3'>" . $LANG['setup'][212] . "</th></tr>";
            echo "<tr class='tab_bg_1'>";
            showFormMailingType("followup", $profiles);
            echo "</tr>";
            echo "<tr class='tab_bg_2'><th colspan='3'>" . $LANG['setup'][213] . "</th></tr>";
            echo "<tr class='tab_bg_2'>";
            showFormMailingType("finish", $profiles);
            echo "</tr>";
            echo "<tr class='tab_bg_2'><th colspan='3'>" . $LANG['setup'][230] . "</th></tr>";
            echo "<tr class='tab_bg_1'>";
            $profiles[USER_MAILING_TYPE . "_" . OLD_ASSIGN_MAILING] = $LANG['setup'][236];
            ksort($profiles);
            showFormMailingType("update", $profiles);
            unset ($profiles[USER_MAILING_TYPE . "_" . OLD_ASSIGN_MAILING]);
            echo "</tr>";
            echo "<tr class='tab_bg_2'><th colspan='3'>" . $LANG['setup'][225] . "</th></tr>";
            echo "<tr class='tab_bg_2'>";
            unset ($profiles[USER_MAILING_TYPE . "_" . ASSIGN_MAILING]);
            unset ($profiles[USER_MAILING_TYPE . "_" . ASSIGN_ENT_MAILING]);
            unset ($profiles[USER_MAILING_TYPE . "_" . ASSIGN_GROUP_MAILING]);
            unset ($profiles[USER_MAILING_TYPE . "_" . SUPERVISOR_ASSIGN_GROUP_MAILING]);
            unset ($profiles[USER_MAILING_TYPE . "_" . SUPERVISOR_AUTHOR_GROUP_MAILING]);
            unset ($profiles[USER_MAILING_TYPE . "_" . RECIPIENT_MAILING]);

            showFormMailingType("resa", $profiles);
            echo "</tr></table></div>";
            break;

         case 3 :
            $profiles[USER_MAILING_TYPE . "_" . ADMIN_MAILING] = $LANG['setup'][237];
            $profiles[USER_MAILING_TYPE . "_" . ADMIN_ENTITY_MAILING] = $LANG['setup'][237]." ".
                                                                        $LANG['entity'][0];
            $query = "SELECT `id`, `name`
                      FROM `glpi_profiles`
                      ORDER BY `name`";
            $result = $DB->query($query);
            while ($data = $DB->fetch_assoc($result)) {
               $profiles[PROFILE_MAILING_TYPE ."_" . $data["id"]] = $LANG['profiles'][22] . " " .
                                                                    $data["name"];
            }
            $query = "SELECT `id`, `name`
                      FROM `glpi_groups`
                      ORDER BY `name`";
            $result = $DB->query($query);
            while ($data = $DB->fetch_assoc($result)) {
               $profiles[GROUP_MAILING_TYPE ."_" . $data["id"]] = $LANG['common'][35] . " " .
                                                                  $data["name"];
            }
            ksort($profiles);
            echo "<div class='center'>";
            echo "<input type='hidden' name='update_notifications' value='1'>";
            // ADMIN
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr><th colspan='3'>" . $LANG['setup'][243]."&nbsp;&nbsp;";
            echo "<input class=\"submit\" type=\"submit\" name=\"test_cron_consumables\" value=\"" .
                   $LANG['buttons'][50] . "\">";
            echo "</th></tr>";
            echo "<tr class='tab_bg_2'>";
            showFormMailingType("alertconsumable", $profiles);
            echo "</tr>";
            echo "<tr><th colspan='3'>" . $LANG['setup'][244]."&nbsp;&nbsp;";
            echo "<input class=\"submit\" type=\"submit\" name=\"test_cron_cartridges\" value=\"" .
                   $LANG['buttons'][50] . "\">";
            echo "</th></tr>";
            echo "<tr class='tab_bg_1'>";
            showFormMailingType("alertcartridge", $profiles);
            echo "</tr>";
            echo "<tr><th colspan='3'>" . $LANG['setup'][246]."&nbsp;&nbsp;";
            echo "<input class=\"submit\" type=\"submit\" name=\"test_cron_contracts\" value=\"" .
                   $LANG['buttons'][50] . "\">";
            echo "</th></tr>";
            echo "<tr class='tab_bg_2'>";
            showFormMailingType("alertcontract", $profiles);
            echo "</tr>";
            echo "<tr><th colspan='3'>" . $LANG['setup'][247]."&nbsp;&nbsp;";
            echo "<input class=\"submit\" type=\"submit\" name=\"test_cron_infocoms\" value=\"" .
                   $LANG['buttons'][50] . "\">";
            echo "</th></tr>";
            echo "<tr class='tab_bg_1'>";
            showFormMailingType("alertinfocom", $profiles);
            echo "</tr>";
            echo "<tr><th colspan='3'>" . $LANG['setup'][264]."&nbsp;&nbsp;";
            echo "<input class=\"submit\" type=\"submit\" name=\"test_cron_softwares\" value=\"" .
                   $LANG['buttons'][50] . "\">";
            echo "</th></tr>";
            echo "<tr class='tab_bg_1'>";
            showFormMailingType("alertlicense", $profiles);
            echo "</tr>";
            echo "</table></div>";
            break;
      }
      echo "</form>";
   }

   /*
    * Display a HTML report about systeme information / configuration
    *
    */
   function showSystemInformations () {
      global $DB,$LANG,$CFG_GLPI;

      $width=128;

      echo "<div class='center' id='tabsbody'>";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th>" . $LANG['setup'][721] . "</th></tr>";
      echo "<tr class='tab_bg_1'><td><pre>[code]\n&nbsp;\n";

      echo "GLPI ".$CFG_GLPI['version']." (".$CFG_GLPI['root_doc']." => ".
            dirname(dirname($_SERVER["SCRIPT_FILENAME"])).")\n";

      echo "\n</pre></td></tr><tr><th>" . $LANG['common'][52] . "</th></tr>\n";
      echo "<tr class='tab_bg_1'><td><pre>\n&nbsp;\n";

      echo wordwrap($LANG['setup'][5]."&nbsp;: ".php_uname()."\n", $width, "\n\t");
      $exts = get_loaded_extensions();
      sort($exts);
      echo wordwrap("PHP ".phpversion()." (".implode(', ',$exts).")\n", $width, "\n\t");
      $msg = $LANG['common'][12].": ";
      foreach (array('memory_limit',
                     'max_execution_time',
                     'safe_mode') as $key) {
         $msg.= $key.'="'.ini_get($key).'" ';
      }
      echo wordwrap($msg."\n", $width, "\n\t");

      $msg = $LANG['Menu'][4].": ";
      if (isset($_SERVER["SERVER_SOFTWARE"])) {
         $msg .= $_SERVER["SERVER_SOFTWARE"];
      }
      if (isset($_SERVER["SERVER_SIGNATURE"])) {
         $msg .= ' ('.html_clean($_SERVER["SERVER_SIGNATURE"]).')';
      }
      echo wordwrap($msg."\n", $width, "\n\t");

      if (isset($_SERVER["HTTP_USER_AGENT"])) {
         echo "\t" . $_SERVER["HTTP_USER_AGENT"] . "\n";
      }

      $version = "???";
      foreach ($DB->request('SELECT VERSION() as ver') as $data) {
         $version = $data['ver'];
      }
      echo "MySQL: $version (".$DB->dbuser."@".$DB->dbhost."/".$DB->dbdefault.")\n";

      foreach ($CFG_GLPI["systeminformations_type"] as $type) {
      	$tmp = new $type;
         $tmp->showSystemInformations($width);
      }

      echo "\n</td></tr>";
      echo "<tr class='tab_bg_1'><td>[/code]\n</td></tr>";
      echo "<tr class='tab_bg_2'><th>" . $LANG['setup'][722] . "</th></tr>\n";
      echo "</tr>\n";
      echo "</table></div>\n";
   }

   /**
    * Dropdown for global management config
    *
    * @param $name select name
    * @param $value default value
    */
   static function dropdownGlobalManagement($name,$value) {
      global $LANG;

      echo "<select name=\"".$name."\">";

      $yesUnit = $LANG['peripherals'][32];
      $yesGlobal = $LANG['peripherals'][31];

      echo "<option value='2'";
      if ($value == 2) {
         echo " selected";
      }
      echo ">".$LANG['choice'][0]."</option>";

      echo "<option value='0'";
      if ($value == 0) {
      echo " selected";
      }
      echo ">" . $LANG['choice'][1]." - ". $LANG['setup'][274]. " : ".  $yesUnit . "</option>";

      echo "<option value='1'";
      if ($value == 1) {
      echo " selected";
      }
      echo ">" . $LANG['choice'][1]." - ". $LANG['setup'][274]. " : ". $yesGlobal . " </option>";

      echo "</select>";
   }

   /**
    * Get language in GLPI associated with the value coming from LDAP
    * Value can be, for example : English, en_EN or en
    * @param $lang : the value coming from LDAP
    * @return the locale's php page in GLPI or '' is no language associated with the value
    */
   static function getLanguage($lang) {
      global $CFG_GLPI;

      // Search in order : ID or extjs dico or tinymce dico / native lang / english name / extjs dico / tinymce dico
      // ID  or extjs dico or tinymce dico 
      foreach ($CFG_GLPI["languages"] as $ID => $language) {
         if (strcasecmp($lang,$ID) == 0
            || strcasecmp($lang,$language[2]) == 0
            || strcasecmp($lang,$language[3]) == 0) {
            return $ID;
         }
      }
      // native lang
      foreach ($CFG_GLPI["languages"] as $ID => $language) {
         if (strcasecmp($lang,$language[0]) == 0) {
            return $ID;
         }
      }
      // english lang name
      foreach ($CFG_GLPI["languages"] as $ID => $language) {
         if (strcasecmp($lang,$language[4]) == 0) {
            return $ID;
         }
      }

      return "";
   }

}

?>
