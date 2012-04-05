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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class Change_Problem extends CommonDBRelation{

   // From CommonDBRelation
   public $itemtype_1 = 'Change';
   public $items_id_1 = 'changes_id';

   public $itemtype_2 = 'Problem';
   public $items_id_2 = 'problems_id';

   var $checks_only_for_itemtype1 = true;


   static function getTypeName($nb=0) {
      return _n('Link Problem/Change','Links Problem/Change',$nb);
   }


   /**
    * Get search function for the class
    *
    * @return array of search option
   **/
   function getSearchOptions() {
      return parent::getSearchOptions();
   }


   /**
    * Show tickets for a problem
    *
    * @param $problem Problem object
   **/
   static function showForProblem(Problem $problem) {
      global $DB, $CFG_GLPI;

      $ID = $problem->getField('id');
      if (!$problem->can($ID,'r')) {
         return false;
      }

      $canedit = $problem->can($ID,'w');
      $rand    = mt_rand();
      echo "<form name='changeproblem_form$rand' id='changeproblem_form$rand' method='post'
             action='";
      echo Toolbox::getItemTypeFormURL(__CLASS__)."'>";
      $colspan = 1;


      $query = "SELECT DISTINCT `glpi_changes_problems`.`id` AS linkID,
                                `glpi_changes`.*
                FROM `glpi_changes_problems`
                LEFT JOIN `glpi_changes`
                     ON (`glpi_changes_problems`.`changes_id` = `glpi_changes`.`id`)
                WHERE `glpi_changes_problems`.`problems_id` = '$ID'
                ORDER BY `glpi_changes`.`name`";
      $result = $DB->query($query);
      $numrows = $DB->numrows($result);

/*      if ($canedit && $numrows) {
         Html::openArrowMassives("changeproblem_form$rand", true, true);
         Html::closeArrowMassives(array('delete' => __('Delete')));
      }*/

      echo "<div class='center'><table class='tab_cadre_fixehov'>";
      echo "<tr><th colspan='2'>"._n('Change - ', 'Changes - ', 2);
      echo "<a href='".Toolbox::getItemTypeFormURL('Change')."?problems_id=$ID'>";
      _e('Create a change from this problem');
      echo "</a>";
      echo "</th>";
      if ($problem->isRecursive()) {
         echo "<th>".__('Entity')."</th>";
         $colspan++;
      }
      echo "</tr>";



      $used = array();
      if ($numrows) {
         Session::initNavigateListItems('Change',
                              //TRANS : %1$s is the itemtype name,
                              //        %2$s is the name of the item (used for headings of a list)
                                        sprintf(__('%1$s = %2$s'), $problem->getTypeName(1),
                                                $problem->fields["name"]));

         while ($data = $DB->fetch_assoc($result)) {
            $used[$data['id']] = $data['id'];
            Session::addToNavigateListItems('Change', $data["id"]);
            echo "<tr class='tab_bg_1'>";
            echo "<td width='10'>";
            if ($canedit) {
               echo "<input type='checkbox' name='item[".$data["linkID"]."]' value='1'>";
            } else {
               echo "&nbsp;";
            }
            echo "</td>";
            echo "<td><a href='".Toolbox::getItemTypeFormURL('Change')."?id=".$data['id']."'>".
                      $data["name"]."</a></td>";
            if ($problem->isRecursive()) {
               echo "<td>".Dropdown::getDropdownName('glpi_entities', $data["entities_id"])."</td>";
            }
            echo "</tr>";
         }
      }

      if ($canedit) {
         echo "<tr class='tab_bg_2'><td class='right'  colspan='$colspan'>";
         echo "<input type='hidden' name='problems_id' value='$ID'>";
         Dropdown::show('Change', array('used'        => $used,
                                        'entity'      => $problem->getEntityID(),
                                        'entity_sons' => $problem->isRecursive()));
         echo "</td><td class='center'>";
         echo "<input type='submit' name='add' value=\"".__s('Add')."\" class='submit'>";
         echo "</td></tr>";
      }

      echo "</table></div>";

      if ($canedit && $numrows) {
         Html::openArrowMassives("changeproblem_form$rand", true);
         Html::closeArrowMassives(array('delete' => __('Delete')));
      }
      echo "</form>";
   }


   /**
    * Show problems for a change
    *
    * @param $change Change object
   **/
   static function showForChange(Change $change) {
      global $DB, $CFG_GLPI;

      $ID = $change->getField('id');
      if (!$change->can($ID,'r')) {
         return false;
      }

      $canedit = $change->can($ID,'w');
      $rand    = mt_rand();
      echo "<form name='changeproblem_form$rand' id='changeproblem_form$rand' method='post'
             action='";
      echo Toolbox::getItemTypeFormURL(__CLASS__)."'>";
      $colspan = 1;

      $query = "SELECT DISTINCT `glpi_changes_problems`.`id` AS linkID,
                                `glpi_problems`.*
                FROM `glpi_changes_problems`
                LEFT JOIN `glpi_problems`
                     ON (`glpi_changes_problems`.`problems_id` = `glpi_problems`.`id`)
                WHERE `glpi_changes_problems`.`changes_id` = '$ID'
                ORDER BY `glpi_problems`.`name`";
      $result = $DB->query($query);
      $numrows = $DB->numrows($result);


//       if ($canedit && $numrows) {
//          Html::openArrowMassives("changeproblem_form$rand", true, true);
//          Html::closeArrowMassives(array('delete' => __('Delete')));
//       }

      echo "<div class='center'><table class='tab_cadre_fixehov'>";
      echo "<tr><th colspan='2'>"._n('Problem', 'Problems', 2)."</th></tr>";
      echo "<tr><th colspan='2'>".__('Title')."</th>";
      echo "</tr>";


      $used = array();
      if ($numrows) {
         Session::initNavigateListItems('Problem',
         //TRANS : %1$s is the itemtype name,
         //        %2$s is the name of the item (used for headings of a list)
                                        sprintf(__('%1$s = %2$s'),
                                                $change->getTypeName(1), $change->fields["name"]));

         while ($data = $DB->fetch_assoc($result)) {
            $used[$data['id']] = $data['id'];
            Session::addToNavigateListItems('Problem', $data["id"]);
            echo "<tr class='tab_bg_1'>";
            echo "<td width='10'>";
            if ($canedit) {
               echo "<input type='checkbox' name='item[".$data["linkID"]."]' value='1'>";
            } else {
               echo "&nbsp;";
            }
            echo "</td>";
            echo "<td><a href='".Toolbox::getItemTypeFormURL('Problem')."?id=".$data['id']."'>".
                      $data["name"]."</a></td>";
            echo "</tr>";
         }
      }

      if ($canedit) {
         echo "<tr class='tab_bg_2'><td class='right'  colspan='$colspan'>";
         echo "<input type='hidden' name='changes_id' value='$ID'>";
         Dropdown::show('Problem', array('used'   => $used,
                                         'entity' => $change->getEntityID()));
         echo "</td><td class='center'>";
         echo "<input type='submit' name='add' value=\"".__s('Add')."\" class='submit'>";
         echo "</td></tr>";
      }

      echo "</table></div>";

      if ($canedit && $numrows) {
         Html::openArrowMassives("changeproblem_form$rand", true);
         Html::closeArrowMassives(array('delete' => __('Delete')));
      }
      echo "</form>";
   }


}
?>
