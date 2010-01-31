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
// Original Author of file: Walid Nouh
// Purpose of file:
// ----------------------------------------------------------------------
if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class SingletonRuleList {
   /// Items list
   var $list = array();
   /// Items loaded ?
   var $load = 0;

   /**
   * get a unique instance of a SingletonRuleList for a type of RuleCollection
   *
   * @param $type of the Rule listed
   * @return unique instance of an object
   */
   public static  function &getInstance($type) {
      static $instances = array();

      if (!isset($instances[$type])) {
         $instances[$type] = new SingletonRuleList();
      }
      return $instances[$type];
   }


}

class RuleCollection {
   /// Rule type
   public $sub_type;
   /// Name of the class used to rule
   var $rule_class_name="Rule";
   /// process collection stop on first matched rule
   var $stop_on_first_match=false;
   /// Right needed to use this rule collection
   var $right="config";
   /// field used to order rules
   var $orderby="ranking";
   /// Processing several rules : use result of the previous one to computer the current one
   var $use_output_rule_process_as_next_input=false;
   /// Rule collection can be replay (for dictionnary)
   var $can_replay_rules=false;
   /// List of rules of the rule collection
   var $RuleList=NULL;
   /// Menu type
   var $menu_type="rule";
   /// Menu option
   var $menu_option="";

   /**
   * Constructor
   * @param sub_type the rule type used for the collection
   **/
   function __construct($sub_type=-1) {
      if ($sub_type > 0){
         $this->sub_type = $sub_type;
      }
   }

   /**
   * Get Collection Size : retrieve the number of rules
   *
   * @return : number of rules
   **/
   function getCollectionSize() {
      return countElementsInTable("glpi_rules", "sub_type=".$this->sub_type);
   }

   /**
   * Get Collection Part : retrieve descriptions of a range of rules
   *
   * @param $start : first rule (in the result set)
   * @param $limit : max number of rules ti retrieve
   **/
   function getCollectionPart($start=0,$limit=0) {
      global $DB;

      $this->RuleList = new SingletonRuleList($this->sub_type);
      $this->RuleList->list = array();

      //Select all the rules of a different type
      $sql = "SELECT *
              FROM `glpi_rules`
              WHERE `sub_type` = '".$this->sub_type."'
              ORDER BY ".$this->orderby." ASC";
      if ($limit) {
         $sql .= " LIMIT ".intval($start).",".intval($limit);
      }
      $result = $DB->query($sql);

      if ($result) {
         while ($data=$DB->fetch_assoc($result)) {
            //For each rule, get a Rule object with all the criterias and actions
            $tempRule= $this->getRuleClass();
            $tempRule->fields = $data;
            $this->RuleList->list[] = $tempRule;
         }
      }
   }

   /**
   * Get Collection Datas : retrieve descriptions and rules
   * @param $retrieve_criteria Retrieve the criterias of the rules ?
   * @param $retrieve_action Retrieve the action of the rules ?
   **/
   function getCollectionDatas($retrieve_criteria=0,$retrieve_action=0) {
      global $DB;

      if ($this->RuleList === NULL) {
         $this->RuleList = SingletonRuleList::getInstance($this->sub_type);
      }
      $need = 1+($retrieve_criteria?2:0)+($retrieve_action?4:0);

      // check if load required
      if (($need & $this->RuleList->load) != $need) {
         //Select all the rules of a different type
         $sql = "SELECT `id`
                 FROM `glpi_rules`
                 WHERE `is_active` = '1'
                       AND `sub_type` = '".$this->sub_type."'
                 ORDER BY ".$this->orderby." ASC";
         $result = $DB->query($sql);

         if ($result) {
            $this->RuleList->list = array();
            while ($rule=$DB->fetch_array($result)) {
               //For each rule, get a Rule object with all the criterias and actions
               $tempRule= $this->getRuleClass();
               if ($tempRule->getRuleWithCriteriasAndActions($rule["id"],$retrieve_criteria,
                                                             $retrieve_action)) {
                  //Add the object to the list of rules
                  $this->RuleList->list[] = $tempRule;
               }
            }
            $this->RuleList->load = $need;
         }
      }
   }

   /**
    * Get a instance of the class to manipulate rule of this collection
    *
   **/
   function getRuleClass() {
      return new $this->rule_class_name();
   }

   /**
    * Is a confirmation needed before replay on DB ?
    * If needed need to send 'replay_confirm' in POST
    * @param $target filename : where to go when done
    * @return  true if confirmtion is needed, else false
   **/
   function warningBeforeReplayRulesOnExistingDB($target) {
      return false;
   }

   /**
    * Replay Collection on DB
    * @param $offset  first row to work on
    * @param $maxtime float : max system time to stop working
    * @param $items   array containg items to replay. If empty -> all
    * @param $params  additional parameters if needed
    *
    * @return -1 if all rows done, else offset for next run
   **/
   function replayRulesOnExistingDB($offset=0,$maxtime=0, $items=array(),$params=array()) {
   }

   /**
   * Get title used in list of rules
   * @return Title of the rule collection
   **/
   function getTitle() {
      global $LANG;

      return $LANG['rulesengine'][29];
   }

   /**
   * Show the list of rules
   * @param $target
   * @return nothing
   **/
   function showListRules($target) {
      global $CFG_GLPI, $LANG;

      $canedit = haveRight($this->right, "w");
      echo "<table class='tab_cadre_fixe'><tr><th>";
      //Display informations about the how the rules engine process the rules
      if ($this->stop_on_first_match) {
         //The engine stop on the first matched rule
         echo "<span class='center b'>".$LANG['rulesengine'][120]."</span><br>";
      } else {
         //The engine process all the rules
         echo "<span class='center b'>".$LANG['rulesengine'][121]."</span><br>";
      }
      if ($this->use_output_rule_process_as_next_input) {
         //The engine keep the result of a rule to be processed further
         echo "<span class='center b'>".$LANG['rulesengine'][122]."</span><br>";
      }
      echo "</th></tr></table>\n";

      $nb = $this->getCollectionSize();
      $start = (isset($_GET["start"]) ? $_GET["start"] : 0);
      if ($start >= $nb) {
         $start = 0;
      }
      $limit = $_SESSION['glpilist_limit'];
      $this->getCollectionPart($start,$limit);

      printPager($start,$nb,$_SERVER['PHP_SELF'],"");

      echo "<br><form name='ruleactions_form' id='ruleactions_form' method='post' action=\"$target\">";
      echo "\n<div class='center'>";
      echo "<table class='tab_cadre_fixehov'>";
      echo "<tr><th colspan='6'>" . $this->getTitle() ."</th></tr>\n";
      echo "<tr><td class='tab_bg_2 center' colspan='2'>".$LANG['common'][16]."</td>";
      echo "<td class='tab_bg_2 center'>".$LANG['joblist'][6]."</td>";
      echo "<td class='tab_bg_2 center'>".$LANG['common'][60]."</td>";
      echo "<td class='tab_bg_2' colspan='2'></td></tr>\n";

      initNavigateListItems('Rule',"",$this->sub_type);
      for ($i=$start,$j=0 ; isset($this->RuleList->list[$j]) ; $i++,$j++) {
         $this->RuleList->list[$j]->showMinimalForm($target,$i==0,$i==$nb-1);
         addToNavigateListItems('Rule',$this->RuleList->list[$j]->fields['id'],$this->sub_type);
      }
      echo "</table>\n";
      if ($canedit && $nb>0) {
         openArrowMassive("ruleactions_form", true);

         echo "<select name='massiveaction' id='massiveaction'>";
         echo "<option value='-1' selected>------</option>";
         echo "<option value='delete'>".$LANG['buttons'][6]."</option>";
         if ($this->orderby=="ranking") {
            echo "<option value='move_rule'>".$LANG['buttons'][20]."</option>";
         }
         echo "<option value='activate_rule'>".$LANG['buttons'][41]."</option>";
         echo "</select>\n";

         $params = array('action'   => '__VALUE__',
                         'itemtype' => 'Rule',
                         'sub_type' => $this->sub_type);

         ajaxUpdateItemOnSelectEvent("massiveaction","show_massiveaction",
                                     $CFG_GLPI["root_doc"]."/ajax/dropdownMassiveAction.php",$params);

         echo "<span id='show_massiveaction'>&nbsp;</span>\n";
         echo "</td>";
         if ($this->can_replay_rules) {
            echo "<td><input type='submit' name='replay_rule' value=\"" . $LANG['rulesengine'][76] .
                       "\" class='submit'></td>";
         }

         closeArrowMassive();
      }
      echo "</form>";
      echo "<br><span class='icon_consol'>";
      echo "<a href='#' onClick=\"var w=window.open('".$CFG_GLPI["root_doc"].
             "/front/popup.php?popup=test_all_rules&amp;sub_type=".$this->sub_type.
             "&amp' ,'glpipopup', 'height=400, width=1000, top=100, left=100, scrollbars=yes' );".
             "w.focus();\">".$LANG['rulesengine'][84]."</a></span>";

      $this->showAdditionalInformationsInForm($target);
   }

   /**
   * Show the list of rules
   * @param $target
   * @return nothing
   **/
   function showAdditionalInformationsInForm($target) {
   }

   /**
   * Modify rule's ranking and automatically reorder all rules
   * @param $ID the rule ID whose ranking must be modified
   * @param $action up or down
   **/
   function changeRuleOrder($ID,$action) {
      global $DB;

      $sql = "SELECT `ranking`
              FROM `glpi_rules`
              WHERE `id` ='$ID'";

      if ($result = $DB->query($sql)) {
         if ($DB->numrows($result)==1) {
            $current_rank=$DB->result($result,0,0);
            // Search rules to switch
            $sql2 = "SELECT `id`, `ranking`
                     FROM `glpi_rules`
                     WHERE `sub_type` ='".$this->sub_type."'";
            switch ($action) {
               case "up" :
                  $sql2 .= " AND `ranking` < '$current_rank'
                           ORDER BY `ranking` DESC
                           LIMIT 1";
                  break;

               case "down" :
                  $sql2 .= " AND `ranking` > '$current_rank'
                           ORDER BY `ranking` ASC
                           LIMIT 1";
                  break;

               default :
                  return false;
            }
            if ($result2 = $DB->query($sql2)) {
               if ($DB->numrows($result2)==1) {
                  list($other_ID,$new_rank)=$DB->fetch_array($result2);

                  $rule = $this->getRuleClass();
                  return ($rule->update(array('id'      => $ID,
                                              'ranking' => $new_rank))
                          && $rule->update(array('id'      => $other_ID,
                                                 'ranking' => $current_rank)));
               }
            }
         }
         return false;
      }
   }

   /**
    * Update Rule Order when deleting a rule
    *
    * @param $ranking rank of the deleted rule
    *
    * @return true if all ok
   **/
   function deleteRuleOrder($ranking) {
      global $DB;

      $rules = array();
      $sql = "UPDATE
              `glpi_rules`
              SET `ranking` = `ranking`-1
              WHERE `sub_type` ='".$this->sub_type."'
                    AND `ranking` > '$ranking' ";
      return $DB->query($sql);
   }

   /**
    * Move a rule in an ordered collection
    *
    * @param $ID of the rule to move
    * @param $ref_ID of the rule position  (0 means all, so before all or after all)
    * @param $type of move : after or before
    *
    * @return true if all ok
    *
   **/
   function moveRule($ID,$ref_ID,$type='after') {
      global $DB;

      $ruleDescription = new Rule;

      // Get actual ranking of Rule to move
      $ruleDescription->getFromDB($ID);
      $old_rank=$ruleDescription->fields["ranking"];

      // Compute new ranking
      if ($ref_ID) { // Move after/before an existing rule
         $ruleDescription->getFromDB($ref_ID);
         $rank=$ruleDescription->fields["ranking"];
      } else if ($type == "after") {
         // Move after all
         $query = "SELECT MAX(`ranking`) AS maxi
                   FROM `glpi_rules`
                   WHERE `sub_type` ='".$this->sub_type."' ";
         $result = $DB->query($query);
         $ligne = $DB->fetch_array($result);
         $rank = $ligne['maxi'];
      } else {
         // Move before all
         $rank=1;
      }

      $rule = $this->getRuleClass();

      // Move others rules in the collection
      if ($old_rank < $rank) {
         if ($type=="before"){
            $rank--;
         }
         // Move back all rules between old and new rank
         $query = "SELECT `id`, `ranking`
                   FROM `glpi_rules`
                   WHERE `sub_type` ='".$this->sub_type."'
                         AND `ranking` > '$old_rank'
                         AND `ranking` <= '$rank'";
         foreach ($DB->request($query) as $data) {
            $data['ranking']--;
            $result = $rule->update($data);
         }
      } else if ($old_rank > $rank) {
         if ($type=="after") {
            $rank++;
         }
         // Move forward all rule  between old and new rank
         $query = "SELECT `id`, `ranking`
                   FROM `glpi_rules`
                   WHERE `sub_type` ='".$this->sub_type."'
                         AND `ranking` >= '$rank'
                         AND `ranking` < '$old_rank'";
         foreach ($DB->request($query) as $data) {
            $data['ranking']++;
            $result = $rule->update($data);
         }
      } else { // $old_rank == $rank : nothing to do
         $result = false;
      }

      // Move the rule
      if ($result && $old_rank != $rank) {
         $result = $rule->update(array('id'      => $ID,
                                       'ranking' => $rank));
      }
      return ($result ? true : false);
   }

   /**
   * Process all the rules collection
   * @param input the input data used to check criterias
   * @param output the initial ouput array used to be manipulate by actions
   * @param params parameters for all internal functions
   * @return the output array updated by actions
   **/
   function processAllRules($input=array(),$output=array(),$params=array()) {

      // Get Collection datas
      $this->getCollectionDatas(1,1);
      $input=$this->prepareInputDataForProcess($input,$params);
      if (count($this->RuleList->list)) {
         foreach ($this->RuleList->list as $rule) {
            //If the rule is active, process it
            if ($rule->fields["is_active"]) {
               $output["_rule_process"]=false;
               $rule->process($input,$output,$params);
               if ($output["_rule_process"] && $this->stop_on_first_match) {
                  unset($output["_rule_process"]);
                  $output["_ruleid"]=$rule->fields["id"];
                  return $output;
               }
            }
            if ($this->use_output_rule_process_as_next_input) {
               $input=$output;
            }
         }
      }
      return $output;
   }

   /**
    * Show form displaying results for rule collection preview
    * @param $target where to go
    * @param $values data array
    **/
   function showRulesEnginePreviewCriteriasForm($target,$values) {
      global $DB, $LANG,$RULES_CRITERIAS,$RULES_ACTIONS;

      $input = $this->prepareInputDataForTestProcess();
      if (count($input)) {
         echo "<form name='testrule_form' id='testrulesengine_form' method='post' action=\"$target\">";
         echo "\n<div class='center'>";
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr><th colspan='2'>" . $LANG['rulesengine'][6] . "</th></tr>\n";

         //Brower all criterias
         foreach ($input as $criteria) {
            echo "<tr class='tab_bg_1'>";
            if (isset($RULES_CRITERIAS[$this->sub_type][$criteria])) {
               $criteria_constants = $RULES_CRITERIAS[$this->sub_type][$criteria];
               echo "<td>".$criteria_constants["name"]."&nbsp;:</td>";
            } else {
               echo "<td>".$criteria."&nbsp;:</td>";
            }
            echo "<td>";
            $rule = getRuleClass($this->sub_type);
            $rule->displayCriteriaSelectPattern($criteria,$criteria,PATTERN_IS,
                                                isset($values[$criteria])?$values[$criteria]:'');
            echo "</td></tr>\n";
         }
         $rule->showSpecificCriteriasForPreview($_POST);

         echo "<tr><td class='tab_bg_2 center' colspan='2'>";
         echo "<input type='submit' name='test_all_rules' value=\"" . $LANG['buttons'][50] .
                "\" class='submit'>";
         echo "<input type='hidden' name='sub_type' value=\"" . $this->sub_type . "\">";
         echo "</td></tr>\n";
         echo "</table></div>";
         echo "</form>\n";
      } else {
         echo '<br><div class="center b">'.$LANG['rulesengine'][97].'</div>';
      }
      return $input;
   }

   /**
   * Test all the rules collection
   * @param input the input data used to check criterias
   * @param output the initial ouput array used to be manipulate by actions
   * @param params parameters for all internal functions
   * @return the output array updated by actions
   **/
   function testAllRules($input=array(),$output=array(),$params=array()) {

      // Get Collection datas
      $this->getCollectionDatas(1,1);

      if (count($this->RuleList->list)) {
         foreach ($this->RuleList->list as $rule) {
            //If the rule is active, process it
            if ($rule->fields["is_active"]) {
               $output["_rule_process"]=false;
               $output["result"][$rule->fields["id"]]["id"]=$rule->fields["id"];
               $rule->process($input,$output,$params);
               if ($output["_rule_process"]&&$this->stop_on_first_match) {
                  unset($output["_rule_process"]);
                  $output["result"][$rule->fields["id"]]["result"]=1;
                  $output["_ruleid"]=$rule->fields["id"];
                  return $output;
               } else if ($output["_rule_process"]) {
                  $output["result"][$rule->fields["id"]]["result"]=1;
               } else {
                  $output["result"][$rule->fields["id"]]["result"]=0;
               }
            } else {
               //Rule is inactive
               $output["result"][$rule->fields["id"]]["result"]=2;
            }
            if ($this->use_output_rule_process_as_next_input) {
               $input=$output;
            }
         }
      }
      return $output;
   }

   /**
   * Prepare input datas for the rules collection
   * @param $input the input data used to check criterias
   * @param $params parameters
   * @return the updated input datas
   **/
   function prepareInputDataForProcess($input,$params) {
      return $input;
   }

   /**
   * Prepare input datas for the rules collection
   * @return the updated input datas
   **/
   function prepareInputDataForTestProcess() {
      global $DB;

      $input = array();
      $res = $DB->query("SELECT DISTINCT `glpi_rulecriterias`.`criteria`
                         FROM `glpi_rulecriterias`, `glpi_rules`
                         WHERE `glpi_rules`.`is_active` = '1'
                               AND `glpi_rulecriterias`.`rules_id`=`glpi_rules`.`id`
                               AND `glpi_rules`.`sub_type`='".$this->sub_type."'");
      while ($data = $DB->fetch_array($res)) {
         $input[]=$data["criteria"];
      }
      return $input;
   }

   /**
    * Show form displaying results for rule engine preview
    * @param $target where to go
    * @param $input data array
    **/
   function showRulesEnginePreviewResultsForm($target,$input) {
      global $LANG,$RULES_ACTIONS;

      $output = array();

      if ($this->use_output_rule_process_as_next_input){
         $output=$input;
      }

      $output = $this->testAllRules($input,$output,$input);
      $rule = getRuleClass($this->sub_type);

      echo "<div class='center'>";

      if (isset($output["result"])) {
         echo "<table class='tab_cadrehov'>";
         echo "<tr><th colspan='2'>" . $LANG['rulesengine'][82] . "</th></tr>\n";
         foreach ($output["result"] as $ID=>$rule_result) {
            echo "<tr class='tab_bg_1'>";
            $rule->getFromDB($ID);
            echo "<td>".$rule->fields["name"]."</td>";
            echo "<td class='b'>";
            switch ($rule_result["result"]) {
               case 0 :
                  echo $LANG['choice'][0];
                  break;

               case 1 :
                  echo $LANG['choice'][1];
                  break;

               case 2 :
                  echo $LANG['rulesengine'][107];
                  break;
            }
            echo "</td></tr>\n";
         }
         echo "</table>";
      }
      $output = $this->cleanTestOutputCriterias($output);
      unset($output["result"]);
      $global_result =(count($output)?1:0);

      echo "<br><table class='tab_cadrehov'>";
      $this->showTestResults($rule,$output,$global_result);
      echo "</table></div>";
}

   /**
    * Unset criterias from the rule's ouput results (begins by _)
    * @param $output clean output array to clean
    * @return cleaned array
    **/
   function cleanTestOutputCriterias($output) {

      //If output array contains keys begining with _ : drop it
      foreach($output as $criteria => $value) {
         if ($criteria[0]=='_') {
            unset($output[$criteria]);
         }
      }
      return $output;
   }

   /**
    * Show test results for a rule
    * @param $rule rule object
    * @param $output Output data array
    * @param $global_result boolean : global result
    * @return cleaned array
    **/
   function showTestResults($rule,$output,$global_result) {
      global $LANG,$RULES_ACTIONS;

      echo "<table class='tab_cadrehov'>";
      echo "<tr><th colspan='2'>" . $LANG['rulesengine'][81] . "</th></tr>\n";
      echo "<tr class='tab_bg_1'>";
      echo "<td colspan='2' class='center'>".$LANG['rulesengine'][41]."&nbsp;:&nbsp;<strong> ".
             Dropdown::getYesNo($global_result)."</strong></td>";

      $output = $this->preProcessPreviewResults($output);

      foreach ($output as $criteria => $value) {
         if (isset($RULES_ACTIONS[$this->sub_type][$criteria])) {
            echo "<tr class='tab_bg_2'>";
            echo "<td>".$RULES_ACTIONS[$this->sub_type][$criteria]["name"]."</td>";
            echo "<td>".$rule->getActionValue($criteria,$value)."</td>";
            echo "</tr>\n";
         }
      }
      echo "</tr></table>\n";
   }

   function preProcessPreviewResults($output) {
      return $this->cleanTestOutputCriterias($output);
   }

   /**
    * Print a title if needed which will be displayed above list of rules
    *
    *@return nothing (display)
    **/
   function title() {
   }

}

?>
