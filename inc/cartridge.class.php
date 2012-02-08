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

//!  Cartridge Class
/**
 * This class is used to manage the cartridges.
 * @see CartridgeItem
 * @author Julien Dombre
 **/
class Cartridge extends CommonDBTM {

   // From CommonDBTM
   protected $forward_entity_to = array('Infocom');

   var $no_form_page = false;


   static function getTypeName($nb=0) {
      return _n('Cartridge', 'Cartridges', $nb);
   }


   function canCreate() {
      return Session::haveRight('cartridge', 'w');
   }


   function canView() {
      return Session::haveRight('cartridge', 'r');
   }


   function prepareInputForAdd($input) {

      $item = new CartridgeItem();
      if ($item->getFromDB($input["cartridgeitems_id"])) {
         return array("cartridgeitems_id" => $item->fields["id"],
                      "entities_id"       => $item->getEntityID(),
                      "date_in"           => date("Y-m-d"));
      }
      return array();
   }


   function post_addItem() {

      $ic = new Infocom();
      $ic->cloneItem('CartridgeItem', $this->fields["cartridgeitems_id"], $this->fields['id'],
                     $this->getType());
   }


   function post_updateItem($history=1) {

      if (in_array('pages', $this->updates)) {
         $printer = new Printer();
         if ($printer->getFromDB($this->fields['printers_id'])
             && ($this->fields['pages'] > $printer->getField('last_pages_counter')
                 || $this->oldvalues['pages'] == $printer->getField('last_pages_counter'))) {

            $printer->update(array('id'                 => $printer->getID(),
                                   'last_pages_counter' => $this->fields['pages'] ));
         }
      }
   }


   function restore(array $input,$history=1) {
      global $DB;

      $query = "UPDATE `".$this->getTable()."`
                SET `date_out` = NULL,
                    `date_use` = NULL,
                    `printers_id` = '0'
                WHERE `id`='".$input["id"]."'";

      if ($result = $DB->query($query) && $DB->affected_rows() > 0) {
         return true;
      }
      return false;
   }


   // SPECIFIC FUNCTIONS
   /**
    * Update count pages value of a cartridge
    *
    * @param $pages  count pages value
    *
    * @return boolean : true for success
   **/
   function updatePages($pages) {

      return $this->update(array('id'    => $this->fields['id'],
                                 'pages' => $pages));
   }


   /**
    * Link a cartridge to a printer.
    *
    * Link the first unused cartridge of type $Tid to the printer $pID
    *
    * @param $tID : cartridge type identifier
    * @param $pID : printer identifier
    *
    * @return boolean : true for success
   **/
   function install($pID, $tID) {
      global $DB;

      // Get first unused cartridge
      $query = "SELECT `id`
                FROM `".$this->getTable()."`
                WHERE (`cartridgeitems_id` = '$tID'
                       AND `date_use` IS NULL)";
      $result = $DB->query($query);

      if ($DB->numrows($result)>0) {
         // Mise a jour cartouche en prenant garde aux insertion multiples
         $query = "UPDATE `".$this->getTable()."`
                   SET `date_use` = '".date("Y-m-d")."',
                       `printers_id` = '$pID'
                   WHERE (`id`='".$DB->result($result,0,0)."'
                          AND `date_use` IS NULL)";

         if ($result = $DB->query($query) && $DB->affected_rows() > 0) {
            return true;
         }

      } else {
         Session::addMessageAfterRedirect(__('No free cartridge'), false, ERROR);
      }
      return false;
   }


   /**
    * UnLink a cartridge linked to a printer
    *
    * UnLink the cartridge identified by $ID
    *
    * @param $ID : cartridge identifier
    *
    * @return boolean
   **/
   function uninstall($ID) {
      global $DB;

      $query = "UPDATE`".$this->getTable()."`
                SET `date_out` = '".date("Y-m-d")."'
                WHERE `id`='$ID'";

      if ($result = $DB->query($query) && $DB->affected_rows() > 0) {
         return true;
      }
      return false;
   }


   /**
    * Print the cartridge count HTML array for the cartridge item $tID
    *
    * @param $tID              integer: cartridge item identifier.
     *@param $alarm_threshold  integer: threshold alarm value.
    * @param $nohtml           integer: Return value without HTML tags (default 0)
    *
    * @return string to display
   **/
   static function getCount($tID, $alarm_threshold, $nohtml=0) {
      global $DB;

      /// TODO to be more useful permit to have several columns and display number in it
      // Get total
      $total = self::getTotalNumber($tID);
      $out = "";
      if ($total!=0) {
         $unused = self::getUnusedNumber($tID);
         $used   = self::getUsedNumber($tID);
         $old    = self::getOldNumber($tID);
         $highlight = "";
         if ($unused<=$alarm_threshold) {
            $highlight = "tab_bg_1_2";
         }

         if (!$nohtml) {
            $out .= "<table  class='tab_format $highlight' width='100%'><tr><td>";
            $out .= __('Total')."</td><td>$total";
            $out .= "</td><td class='b'>";
            $out .= _nx('cartridge','New','New',$unused);
            $out .= "</td><td class='b'>$unused</td><tr>";
            $out .= "<tr><td>";
            $out .= _nx('cartridge','Used','Used',$used);
            $out .= "</td><td>$used</span></td><td>";
            $out .= _nx('cartridge','Worn','Worn',$old);
            $out .= "</td><td>$old</span></td></tr></table>";

         } else {
            //TRANS : for display cartridges count : %1$d is the total number,
            //        %2$d the new one, %3$d the used one, %4$d worn one
            $out .= sprintf(__('Total: %1$d (%2$d new, %3$d used, %4$d worn)'),
                            $total ,$unused, $used, $old);
         }

      } else {
         if (!$nohtml) {
            $out .= "<div class='tab_bg_1_2'><i>".__('No cartridge')."</i></div>";
         } else {
            $out .= __('No cartridge');
         }
      }
      return $out;
   }


   /**
    * count how many cartbridge for the cartridge item $tID
    *
    * @param $tID integer: cartridge item identifier.
    *
    * @return integer : number of cartridge counted.
   **/
   static function getTotalNumber($tID) {
      global $DB;

      $query = "SELECT id
                FROM `glpi_cartridges`
                WHERE (`cartridgeitems_id` = '$tID')";
      $result = $DB->query($query);
      return $DB->numrows($result);
   }


   /**
    * count how many cartridge used for the cartridge item $tID
    *
    * @param $tID integer: cartridge item identifier.
    *
    * @return integer : number of cartridge used counted.
   **/
   static function getUsedNumber($tID) {
      global $DB;

      $query = "SELECT id
                FROM `glpi_cartridges`
                WHERE (`cartridgeitems_id` = '$tID'
                       AND `date_use` IS NOT NULL
                       AND `date_out` IS NULL)";
      $result = $DB->query($query);
      return $DB->numrows($result);
   }


   /**
    * count how many old cartbridge for the cartridge item $tID
    *
    * @param $tID integer: cartridge item identifier.
    *
    * @return integer : number of old cartridge counted.
   **/
   static function getOldNumber($tID) {
      global $DB;

      $query = "SELECT id
                FROM `glpi_cartridges`
                WHERE (`cartridgeitems_id` = '$tID'
                       AND `date_out` IS NOT NULL)";
      $result = $DB->query($query);
      return $DB->numrows($result);
   }


   /**
    * count how many cartbridge unused for the cartridge item $tID
    *
    * @param $tID integer: cartridge item identifier.
    *
    * @return integer : number of cartridge unused counted.
   **/
   static function getUnusedNumber($tID) {
      global $DB;

      $query = "SELECT id
                FROM `glpi_cartridges`
                WHERE (`cartridgeitems_id` = '$tID'
                       AND `date_use` IS NULL)";
      $result = $DB->query($query);
      return $DB->numrows($result);
   }


   /**
    * Get the dict value for the status of a cartridge
    *
    * @param $date_use  date : date of use
    * @param $date_out  date : date of delete
    *
    * @return string : dict value for the cartridge status.
   **/
   static function getStatus($date_use, $date_out) {

      if (is_null($date_use) || empty($date_use)) {
         return _x('cartridge', 'New');
      }
      if (is_null($date_out) || empty($date_out)) {
         return _x('cartridge', 'Used');
      }
      return _x('cartridge', 'Worn');
   }


   /**
    * Print out the cartridges of a defined type
    *
    * @param $cartitem  object of CartridgeItem class
    * @param $show_old  boolean : show old cartridges or not (default 0)
    *
    * @return Nothing (displays)
   **/
   static function showForCartridgeItem(CartridgeItem $cartitem, $show_old=0) {
      global $DB, $CFG_GLPI;

      $tID = $cartitem->getField('id');
      if (!$cartitem->can($tID,'r')) {
         return false;
      }
      $canedit = $cartitem->can($tID,'r');

      $query = "SELECT COUNT(*) AS count
                FROM `glpi_cartridges`
                WHERE (`cartridgeitems_id` = '$tID')";

      if ($result = $DB->query($query)) {
         $total  = $DB->result($result, 0, "count");
//          $unused = self::getUnusedNumber($tID);
//          $used   = self::getUsedNumber($tID);
//          $old    = self::getOldNumber($tID);

         echo "<div class='spaced'><table class='tab_cadre_fixe'>";
         if (!$show_old) {
            echo "<tr><th colspan='7'>".self::getCount($tID,-1)."</th>";
            echo "<th colspan='2'>&nbsp;</th></tr>";
         } else { // Old
            echo "<tr><th colspan='8'>".__('Worn cartridges')."</th>";
            echo "<th colspan='2'>&nbsp;</th></tr>";
         }
         $i = 0;
         echo "<tr><th>".__('ID')."</th><th>".__('State')."</th>";
         echo "<th>".__('Add date')."</th><th>".__('Use date')."</th>";
         echo "<th>".__('Used on')."</th><th>".__('End date')."</th>";

         if ($show_old) {
            echo "<th>".__('Printer counter')."</th>";
         }

         echo "<th>".__('Financial and administrative information')."</th>";
         echo "<th colspan='2'>&nbsp;</th>";

         echo "</tr>";
      }

      if (!$show_old) { // NEW
         $where = " AND `glpi_cartridges`.`date_out` IS NULL";
      } else { //OLD
         $where = " AND `glpi_cartridges`.`date_out` IS NOT NULL";
      }

      $stock_time       = 0;
      $use_time         = 0;
      $pages_printed    = 0;
      $nb_pages_printed = 0;
      $ORDER = " `glpi_cartridges`.`date_use` ASC,
                `glpi_cartridges`.`date_out` DESC,
                `glpi_cartridges`.`date_in`";

      if (!$show_old) {
         $ORDER = " `glpi_cartridges`.`date_out` ASC,
                   `glpi_cartridges`.`date_use` ASC,
                   `glpi_cartridges`.`date_in`";
      }
      $query = "SELECT `glpi_cartridges`.*,
                       `glpi_printers`.`id` AS printID,
                       `glpi_printers`.`name` AS printname,
                       `glpi_printers`.`init_pages_counter`
                FROM `glpi_cartridges`
                LEFT JOIN `glpi_printers`
                     ON (`glpi_cartridges`.`printers_id` = `glpi_printers`.`id`)
                WHERE `glpi_cartridges`.`cartridgeitems_id` = '$tID'
                      $where
                ORDER BY $ORDER";

      $pages = array();

      if ($result=$DB->query($query)) {
         $number = $DB->numrows($result);
         while ($data=$DB->fetch_assoc($result)) {
            $date_in  = Html::convDate($data["date_in"]);
            $date_use = Html::convDate($data["date_use"]);
            $date_out = Html::convDate($data["date_out"]);
            $printer  = $data["printers_id"];
            $page     = $data["pages"];

            echo "<tr class='tab_bg_1'><td class='center'>".$data["id"]."</td>";
            echo "<td class='center'>".self::getStatus($data["date_use"], $data["date_out"]);
            echo "</td><td class='center'>".$date_in."</td>";
            echo "<td class='center'>".$date_use."</td>";
            echo "<td class='center'>";
            if (!is_null($date_use)) {
               if ($data["printID"]>0) {
                  echo "<a href='".$CFG_GLPI["root_doc"]."/front/printer.form.php?id=".
                        $data["printID"]."'><span class='b'>".$data["printname"];
                  if ($_SESSION['glpiis_ids_visible']) {
                     echo " (".$data["printID"].")";
                  }
                  echo "</span></a>";
               } else {
                  echo NOT_AVAILABLE;
               }
               $tmp_dbeg = explode("-",$data["date_in"]);
               $tmp_dend = explode("-",$data["date_use"]);
               $stock_time_tmp = mktime(0, 0, 0, $tmp_dend[1], $tmp_dend[2], $tmp_dend[0])
                                 - mktime(0, 0, 0, $tmp_dbeg[1], $tmp_dbeg[2], $tmp_dbeg[0]);
               $stock_time += $stock_time_tmp;
            }
            echo "</td><td class='center'>";
            echo $date_out;
            if ($show_old) {
               $tmp_dbeg = explode("-",$data["date_use"]);
               $tmp_dend = explode("-",$data["date_out"]);
               $use_time_tmp = mktime(0, 0, 0, $tmp_dend[1], $tmp_dend[2], $tmp_dend[0])
                               - mktime(0, 0, 0, $tmp_dbeg[1], $tmp_dbeg[2], $tmp_dbeg[0]);
               $use_time += $use_time_tmp;
            }

            echo "</td>";
            if ($show_old) {
               // Get initial counter page
               if (!isset($pages[$printer])) {
                  $pages[$printer] = $data['init_pages_counter'];
               }
               echo "<td class='center'>";
               if ($pages[$printer]<$data['pages']) {
                  $pages_printed += $data['pages']-$pages[$printer];
                  $nb_pages_printed++;
                  $pp = $data['pages']-$pages[$printer];
                  echo sprintf(_n('%d printed page','%d printed pages',$pp), $pp);
                  $pages[$printer] = $data['pages'];
               }
               echo "</td>";
            }
            echo "<td class='center'>";
            Infocom::showDisplayLink('Cartridge',$data["id"],1);
            echo "</td>";
            echo "<td class='center'>";
            if (!is_null($date_use) && $canedit) {
               echo "<a href='".$CFG_GLPI["root_doc"]."/front/cartridge.form.php?restore=restore&amp;id=".
                     $data["id"]."&amp;tID=$tID'>".__('Back to stock')."</a>";
            } else {
                echo "&nbsp;";
            }
            echo "</td>";
            echo "<td class='center'>";
            if ($canedit) {
               echo "<a href='".$CFG_GLPI["root_doc"]."/front/cartridge.form.php?delete=delete&amp;id=".
                     $data["id"]."&amp;tID=$tID'>".__s('Delete')."</a>";
            } else {
                echo "&nbsp;";
            }
            echo "</td></tr>";
         }
         if ($show_old&&$number>0) {
            if ($nb_pages_printed==0) {
                $nb_pages_printed = 1;
            }
            echo "<tr class='tab_bg_2'><td colspan='3'>&nbsp;</td>";
            echo "<td class='center'>".__('Average time in stock')."<br>";
            echo round($stock_time/$number/60/60/24/30.5,1)." ".__('month')."</td>";
            echo "<td>&nbsp;</td>";
            echo "<td class='center'>".__('Average time in use')."<br>";
            echo round($use_time/$number/60/60/24/30.5,1)." ".__('month')."</td>";
            echo "<td class='center'>".__('Average number of printed pages')."<br>";
            echo round($pages_printed/$nb_pages_printed)."</td>";
            echo "<td colspan='3'>&nbsp;</td></tr>";
         }
      }
      echo "</table></div>\n\n";
   }


   /**
    * Print out a link to add directly a new cartridge from a cartridge item.
    *
    * @param $cartitem object of CartridgeItem class
    *
    * @return Nothing (displays)
   **/
   static function showAddForm(CartridgeItem $cartitem) {
      global $CFG_GLPI;

      $ID = $cartitem->getField('id');
      if (!$cartitem->can($ID, 'w')) {
         return false;
      }
      if ($ID > 0) {
         echo "<div class='firstbloc'>";
         echo "<form method='post' action=\"".$CFG_GLPI["root_doc"]."/front/cartridge.form.php\">";
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr><td class='center tab_bg_2'>";
         echo "<input type='hidden' name='cartridgeitems_id' value='$ID'>\n";
         Dropdown::showInteger('to_add',1,1,100);
         echo " <input type='submit' name='add_several' value=\"".__s('Add cartridges')."\"
                class='submit'>";
         echo "</td></tr>";
         echo "</table></form></div>";
      }
   }


   /**
    * Show installed cartridges
    *
    * @param $printer   object Printer
    * @param $old       boolean : old cartridges or not ? (default 0)
    *
    * @return nothing (display)
   **/
   static function showInstalled(Printer $printer, $old=0) {
      global $DB, $CFG_GLPI;

      $instID = $printer->getField('id');
      if (!Session::haveRight("cartridge","r")) {
         return false;
      }
      $canedit = Session::haveRight("cartridge", "w");

      $query = "SELECT `glpi_cartridgeitems`.`id` AS tID,
                       `glpi_cartridgeitems`.`is_deleted`,
                       `glpi_cartridgeitems`.`ref` AS ref,
                       `glpi_cartridgeitems`.`name` AS type,
                       `glpi_cartridges`.`id`,
                       `glpi_cartridges`.`pages` AS pages,
                       `glpi_cartridges`.`date_use` AS date_use,
                       `glpi_cartridges`.`date_out` AS date_out,
                       `glpi_cartridges`.`date_in` AS date_in
                FROM `glpi_cartridges`,
                     `glpi_cartridgeitems`
                WHERE (`glpi_cartridges`.`date_out` IS ".($old?"NOT":"")." NULL
                       AND `glpi_cartridges`.`printers_id` = '$instID'
                       AND `glpi_cartridges`.`cartridgeitems_id` = `glpi_cartridgeitems`.`id`)
                ORDER BY `glpi_cartridges`.`date_out` ASC,
                         `glpi_cartridges`.`date_use` DESC,
                         `glpi_cartridges`.`date_in`";

      $result = $DB->query($query);
      $number = $DB->numrows($result);
      $i = 0;

      $pages = $printer->fields['init_pages_counter'];

      echo "<div class='spaced'><table class='tab_cadre_fixe'>";
      if ($old==0) {
         echo "<tr><th colspan='5'>".__('Used cartridges')."</th></tr>";
      } else {
         echo "<tr><th colspan='7'>".__('Worn cartridges')."</th></tr>";
      }
      echo "<tr><th>".__('ID')."</th><th>"._n('Cartridge model','Cartridge models',1)."</th>";
      echo "<th>".__('Add date')."</th>";
      echo "<th>".__('Use date')."</th>";
      if ($old!=0) {
         echo "<th>".__('End date')."</th><th>".__('Printer counter')."</th>";
      }

      echo "<th>&nbsp;</th></tr>";
      $stock_time       = 0;
      $use_time         = 0;
      $pages_printed    = 0;
      $nb_pages_printed = 0;

      while ($data=$DB->fetch_assoc($result)) {
         $date_in  = Html::convDate($data["date_in"]);
         $date_use = Html::convDate($data["date_use"]);
         $date_out = Html::convDate($data["date_out"]);
         echo "<tr class='tab_bg_1".($data["is_deleted"]?"_2":"")."'>";
         echo "<td class='center'>".$data["id"]."</td>";
         echo "<td class='center b'>";
         echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/cartridgeitem.form.php?id=".$data["tID"]."\">".
                $data["type"]." - ".$data["ref"]."</a></td>";
         echo "<td class='center'>".$date_in."</td>";
         echo "<td class='center'>".$date_use;

         $tmp_dbeg = explode("-", $data["date_in"]);
         $tmp_dend = explode("-", $data["date_use"]);

         $stock_time_tmp = mktime(0, 0, 0, $tmp_dend[1], $tmp_dend[2], $tmp_dend[0])
                           - mktime(0, 0, 0, $tmp_dbeg[1], $tmp_dbeg[2], $tmp_dbeg[0]);
         $stock_time += $stock_time_tmp;

         if ($old!=0) {
            echo "</td>";
            echo "<td class='center'>".$date_out;
         }
         if ($old!=0) {
            $tmp_dbeg = explode("-", $data["date_use"]);
            $tmp_dend = explode("-", $data["date_out"]);

            $use_time_tmp = mktime(0, 0, 0, $tmp_dend[1], $tmp_dend[2], $tmp_dend[0])
                            - mktime(0, 0, 0, $tmp_dbeg[1], $tmp_dbeg[2], $tmp_dbeg[0]);
            $use_time+=$use_time_tmp;
         }
         echo "</td><td class='center'>";
         if ($old!=0) {
            if ($canedit) {
               echo "<form method='post' action=\"".$CFG_GLPI["root_doc"].
                    "/front/cartridge.form.php\">";
               echo "<input type='hidden' name='id' value='".$data['id']."'>";
               echo "<input type='text' name='pages' value=\"".$data['pages']."\" size='10'>";
               echo "<input type='image' name='update_pages' value='update_pages'
                      src='".$CFG_GLPI["root_doc"]."/pics/actualiser.png' class='calendrier'>";
               echo "</form>";
            } else {
               echo "<input type='text' name='pages' value=\"".$data['pages']."\" size='10'>";
            }

            if ($pages<$data['pages']) {
               $pages_printed += $data['pages']-$pages;
               $nb_pages_printed++;
               $pp=$data['pages']-$pages;
               echo sprintf(_n('%d printed page','%d printed pages',$pp), $pp);
               $pages = $data['pages'];
            }
            echo "</td><td class='center'>";
         }
         if ($canedit) {
            echo "<span class='small_space'>";
            if (is_null($date_out)) {
               echo "<a href='".$CFG_GLPI["root_doc"].
                      "/front/cartridge.form.php?uninstall=uninstall&amp;id=".$data["id"].
                      "&amp;tID=".$data["tID"]."'>".__('End of life')."</a>";
            } else {
               echo "<a href='".$CFG_GLPI["root_doc"].
                      "/front/cartridge.form.php?delete=delete&amp;id=".$data["id"].
                      "&amp;tID=".$data["tID"]."'>".__('Delete')."</a>";
            }
            echo "</span></td></tr>";
         }
      }
      if ($old==0 && $canedit) {
         echo "<tr class='tab_bg_1'><td>&nbsp;</td><td class='center' colspan='3'>";
         echo "<form method='post' action=\"".$CFG_GLPI["root_doc"]."/front/cartridge.form.php\">";
         echo "<input type='hidden' name='printers_id' value='$instID'>";

         if (CartridgeItem::dropdownForPrinter($printer)) {
            echo "&nbsp;<input type='submit' name='install' value=\"".__s('Install')."\"
                         class='submit'>";
         }
         echo "</form></td><td class='tab_bg_2 center'>&nbsp;";
         echo "</td></tr>";

      } else { // Print average
         if ($number>0) {
            if ($nb_pages_printed==0) {
               $nb_pages_printed = 1;
            }
            echo "<tr class='tab_bg_2'><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>";
            echo "<td class='center'>".__('Average Time in stock')."<br>";
            $time_stock = round($stock_time/$number/60/60/24/30.5,1);
            echo sprintf(_n('%d month', '%d months', $time_stock), $time_stock)."</td>";
            echo "<td class='center'>".__('Average Time in use')."<br>";
            $time_use = round($use_time/$number/60/60/24/30.5,1);
            echo sprintf(_n('%d month', '%d months', $time_use), $time_use)."</td>";
            echo "<td class='center'>".__('Average number of printed pages')."<br>";
            echo round($pages_printed/$nb_pages_printed)."</td>";
            echo "<td>&nbsp;</td></tr>";
         }
      }
      echo "</table></div>";
   }


   /**
    * Get notification parameters by entity
    *
    * @param entity the entity (default 0)
    */
   static function getNotificationParameters($entity=0) {
      global $DB, $CFG_GLPI;

      //Look for parameters for this entity
      $query = "SELECT `cartridges_alert_repeat`
                FROM `glpi_entities`
                WHERE `id` = '$entity'";
      $iterator = $DB->request($query);

      if (!$iterator->numrows()) {
         //No specific parameters defined, taking global configuration params
         return $CFG_GLPI['cartridges_alert_repeat'];

      } else {
         $datas = $iterator->next();
         //This entity uses global parameters -> return global config
         if ($datas['cartridges_alert_repeat'] == -1) {
            return $CFG_GLPI['cartridges_alert_repeat'];
         }
         // ELSE Special configuration for this entity
         return $datas['cartridges_alert_repeat'];
      }
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if (!$withtemplate && Session::haveRight("cartridge","r"))
         switch ($item->getType()) {
            case 'Printer' :
               if ($_SESSION['glpishow_count_on_tabs']) {
                  return self::createTabEntry(self::getTypeName(2), self::countForPrinter($item));
               }
               return self::getTypeName(2);

            case 'CartridgeItem' :
               if ($_SESSION['glpishow_count_on_tabs']) {
                  return self::createTabEntry(self::getTypeName(2),
                                              self::countForCartridgeItem($item));
               }
               return self::getTypeName(2);
      }
      return '';
   }


   static function countForCartridgeItem(CartridgeItem $item) {

      $restrict = "`glpi_cartridges`.`cartridgeitems_id` = '".$item->getField('id') ."'";

      return countElementsInTable(array('glpi_cartridges'), $restrict);
   }


   /**
    * @param $item Printer object
   **/
   static function countForPrinter(Printer $item) {

      $restrict = "`glpi_cartridges`.`printers_id` = '".$item->getField('id') ."'";

      return countElementsInTable(array('glpi_cartridges'), $restrict);
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      switch ($item->getType()) {
         case 'Printer' :
            self::showInstalled($item);
            self::showInstalled($item, 1);
            return true;

         case 'CartridgeItem' :
            self::showAddForm($item);
            self::showForCartridgeItem($item);
            self::showForCartridgeItem($item, 1);
            return true;
      }
   }
}
?>