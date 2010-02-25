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
// Original Author of file: Walid Nouh
// Purpose of file:
// ----------------------------------------------------------------------
if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class RuleAction extends CommonDBTM {

   // From CommonDBTM
   public $table = 'glpi_ruleactions';

   /**
    * Get all actions for a given rule
    * @param $ID the rule_description ID
    * @return an array of RuleAction objects
   **/
   function getRuleActions($ID) {
      global $DB;

      $sql = "SELECT *
              FROM `glpi_ruleactions`
              WHERE `rules_id` = '$ID'";
      $result = $DB->query($sql);

      $rules_actions = array ();
      while ($rule = $DB->fetch_assoc($result)) {
         $tmp = new RuleAction;
         $tmp->fields = $rule;
         $rules_actions[] = $tmp;
      }
      return $rules_actions;
   }

   /**
    * Add an action
    * @param $action action type
    * @param $ruleid rule ID
    * @param $field field name
    * @param $value value
   **/
   function addActionByAttributes($action,$ruleid,$field,$value) {

      $ruleAction = new RuleAction;
      $input["action_type"]=$action;
      $input["field"]=$field;
      $input["value"]=$value;
      $input["rules_id"]=$ruleid;
      $ruleAction->add($input);
   }

   /**
   * Display a dropdown with all the possible actions
   **/
   static function dropdownActions($sub_type,$name,$value='') {
      global $LANG,$CFG_GLPI;

      $rule = new $sub_type();
      $actions_options = $rule->getActions();

      $actions=array("assign");
      if (isset($actions_options[$value]['force_actions'])) {
         $actions=$actions_options[$value]['force_actions'];
      }

      $elements=array();
      foreach ($actions as $action) {
         switch ($action) {
            case "assign" :
               $elements["assign"] = $LANG['rulesengine'][22];
               break;

            case "regex_result" :
               $elements["regex_result"] = $LANG['rulesengine'][45];
               break;

            case "append_regex_result" :
               $elements["append_regex_result"] = $LANG['rulesengine'][79];
               break;

            case "affectbyip" :
               $elements["affectbyip"] = $LANG['rulesengine'][46];
               break;

            case "affectbyfqdn" :
               $elements["affectbyfqdn"] = $LANG['rulesengine'][47];
               break;

            case "affectbymac" :
               $elements["affectbymac"] = $LANG['rulesengine'][49];
               break;

            case 'compute';
               $elements['compute'] = $LANG['rulesengine'][38];
               break;
         }
      }
      return Dropdown::showFromArray($name,$elements,array('value' => $value));
   }

   static function getActionByID($ID) {
      global $LANG;

      switch ($ID) {
         case "assign" :
            return $LANG['rulesengine'][22];

         case "regex_result" :
            return $LANG['rulesengine'][45];

         case "append_regex_result" :
            return $LANG['rulesengine'][79];

         case "affectbyip" :
            return $LANG['rulesengine'][46];

         case "affectbyfqdn" :
            return $LANG['rulesengine'][47];

         case "affectbymac" :
            return $LANG['rulesengine'][49];

         case 'compute' :
            return $LANG['rulesengine'][38];
      }
   }

   static function getRegexResultById($action,$regex_results) {
      $results = array();

      if (count($regex_results)>0) {
         if (preg_match_all("/#([0-9])/",$action,$results)>0) {
            foreach($results[1] as $result) {
               $action=str_replace("#$result",
                                 (isset($regex_results[$result])?$regex_results[$result]:''),$action);
            }
         }
      }
      return $action;
   }

   static function getAlreadyUsedForRuleID($rules_id,$sub_type) {
      global $DB;

      $rule = new $sub_type();
      $actions_options = $rule->getActions();

      $actions = array();
      $res = $DB->query("SELECT field FROM glpi_ruleactions WHERE rules_id='".$rules_id."'");
      while ($action = $DB->fetch_array($res)) {
         if (isset($actions_options[$action["field"]])) {
            $actions[$action["field"]] = $action["field"];
         }
      }
      return $actions;
   }

}

?>
