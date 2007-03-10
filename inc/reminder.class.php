<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2006 by the INDEPNET Development Team.

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
// Original Author of file: Jean-mathieu Doléans
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')){
	die("Sorry. You can't access directly to this file");
	}



class Reminder extends CommonDBTM {

	function Reminder () {
		$this->table="glpi_reminder";
		$this->type=REMINDER_TYPE;
	}

	function prepareInputForAdd($input) {
		global $LANG;

		if(empty($input["title"])) $input["title"]=$LANG["reminder"][15];

		$input["begin"] = $input["end"] = "0000-00-00 00:00:00";

		if (isset($input['plan'])){
			$input['_plan']=$input['plan'];
			unset($input['plan']);
			$input['rv']="1";
			$input["begin"] = $input['_plan']["begin_date"]." ".$input['_plan']["begin_hour"].":00";
			$input["end"] = $input['_plan']["end_date"]." ".$input['_plan']["end_hour"].":00";
		}	


		// set new date.
		$input["date"] = $_SESSION["glpi_currenttime"];

		return $input;
	}

	function pre_updateInDB($input,$updates) {
		if (count($updates)){
			$this->fields["date_mod"]=$_SESSION["glpi_currenttime"];
			$updates[]="date_mod";
		}
		return array($input,$updates);
	}

	function prepareInputForUpdate($input) {
		global $LANG;

		if(empty($input["title"])) $input["title"]=$LANG["reminder"][15];


		if (isset($input['plan'])){
			$input['_plan']=$input['plan'];
			unset($input['plan']);
			$input['rv']="1";
			$input["begin"] = $input['_plan']["begin_date"]." ".$input['_plan']["begin_hour"].":00";
			$input["end"] = $input['_plan']["end_date"]." ".$input['_plan']["end_hour"].":00";
		}	

		return $input;
	}


	function title(){

		global  $LANG,$CFG_GLPI;

		displayTitle($CFG_GLPI["root_doc"]."/pics/reminder.png",$LANG["reminder"][0],"",array("reminder.form.php"=>$LANG["buttons"][8]));
	}




	function showForm ($target,$ID) {
		// Show Reminder or blank form

		global $CFG_GLPI,$LANG;

		$issuperadmin=haveRight("reminder_public","w");
		$author=$_SESSION['glpiID'];

		$read ="";
		$remind_edit=false;
		$remind_show =false;

		if (!$ID) {

			if($this->getEmpty()){
				$remind_edit = true;
				$this->fields["title"]=$LANG["reminder"][6];
				$onfocus="onfocus=\"this.value=''\"";
			}


		} else {
			if($this->getfromDB($ID)){
				$onfocus="";
				if($this->fields["author"]==$author) {
					$remind_edit = true;
				} elseif($this->fields["type"]=="public") { 
					$remind_show = true;
				}

			}else $remind_show = false;

		}
		if ($remind_show||$remind_edit){

			if($remind_edit) {
				echo "<form method='post' name='remind' action=\"$target\">";
				if (empty($ID)){
					echo "<input type='hidden' name='FK_entities' value='".$_SESSION["glpiactive_entity"]."'>";
				}
			}

			echo "<div align='center'><table class='tab_cadre' width='450'>";
			echo "<tr><th><a href='".$CFG_GLPI['root_doc']."/front/reminder.php'>".$LANG["common"][53]."</a></th><th><b>";
			if (!$ID) {
				echo $LANG["reminder"][6].":";
			} else {
				echo $LANG["reminder"][7]." ID $ID:";
			}		
			echo "</b></th></tr>";

			echo "<tr class='tab_bg_2'><td>".$LANG["common"][57].":		</td>";
			echo "<td>";

			if($remind_edit) { 
				echo "<input type='text' size='80' name='title' $read value=\"".$this->fields["title"]."\"  ".$onfocus.">";
			}else{ 
				echo  $this->fields["title"];
			}
			echo "</td></tr>";

			if($remind_show) { 
				echo "<tr class='tab_bg_2'><td>".$LANG["planning"][9].":		</td>";
				echo "<td>";
				echo getUserName($this->fields["author"]);
				echo "</td></tr>";
			}

			echo "<tr class='tab_bg_2'><td>".$LANG["reminder"][10].":		</td>";
			echo "<td>";

			if($remind_edit) { 
				echo "<select name='type' $read>";

				echo "<option value='private' ". (((isset($_GET["type"])&&$_GET["type"]=="private")||$this->fields["type"]=="private")?"selected='selected'":"") .">".$LANG["reminder"][4]."</option>";	

				if($issuperadmin){
					echo "<option value='public' ". ((isset($_GET["type"])&&$_GET["type"]=="public")||($this->fields["type"]=="public")?"selected='selected'":"").">".$LANG["reminder"][5]."</option>";	
				}		
				echo "</select>";
			}else{
				echo $this->fields["type"];
			}

			echo "</td></tr>";


			echo "<tr class='tab_bg_2'><td >".$LANG["reminder"][11].":		</td>";





			echo "<td align='center'>";

			if($remind_edit) { 
				echo "<script type='text/javascript' >\n";
				echo "function showPlan(){\n";
				echo "Element.hide('plan');";
				echo "var a=new Ajax.Updater('viewplan','".$CFG_GLPI["root_doc"]."/ajax/planning.php' , {asynchronous:true, evalScripts:true, method: 'get',parameters: 'form=remind".(($ID&&$this->fields["rv"])?"&begin_date=".$this->fields["begin"]."&end_date=".$this->fields["end"]."":"")."'});";
				echo "}";
				echo "</script>\n";
			}
			
			if(!$ID||$this->fields["rv"]==0){
				if($remind_edit) { 
					echo "<div id='plan'  onClick='showPlan()'>\n";
					echo "<span style='font-weight: bold;text-decoration: none; color : #659900; cursor:pointer;'>".$LANG["reminder"][12]."</span>";
				}
			}else{
				if($remind_edit) {
					echo "<div id='plan'  onClick='showPlan()'>\n";
					echo "<span style='font-weight: bold;text-decoration: none; color : #009966;cursor:pointer;'>";
				}
				echo convDateTime($this->fields["begin"])."->".convDateTime($this->fields["end"]);
				if($remind_edit){
					echo "</span>";
				}
			}	
			
			if($remind_edit) { 
				echo "</div>\n";
				echo "<div id='viewplan'>\n";
				echo "</div>\n";	
			}
			echo "</td>";


			echo "</tr>";

			echo "<tr class='tab_bg_2'><td>".$LANG["reminder"][9].":		</td><td>";
			if($remind_edit) { 
				echo "<textarea cols='80' rows='15' name='text' $read>".$this->fields["text"]."</textarea>";
			}else{
				echo nl2br($this->fields["text"]);
			}
			echo "</td></tr>";

			if (!$ID) { // add

				echo "<tr>";
				echo "<td class='tab_bg_2' valign='top' colspan='2'>";
				echo "<input type='hidden' name='author' value=\"$author\">\n";
				echo "<div align='center'><input type='submit' name='add' value=\"".$LANG["buttons"][8]."\" class='submit'></div>";
				echo "</td>";
				echo "</tr>";



			} elseif($remind_edit) { // update / delete uniquement pour l'auteur du message


				echo "<tr>";

				echo "<td class='tab_bg_2' valign='top' colspan='2'>";
				echo "<input type='hidden' name='ID' value=\"$ID\">\n";
				echo "<div align='center'><input type='submit' name='update' value=\"".$LANG["buttons"][7]."\" class='submit'>";

				echo "<input type='hidden' name='ID' value=\"$ID\">\n";
				echo "<input type='hidden' name='author' value=\"$author\">\n";

				echo "<input type='submit' name='delete' value=\"".$LANG["buttons"][6]."\" class='submit'></div>";

				echo "</td>";
				echo "</tr>";




			}

			echo "</table></div>";
			if($remind_edit){echo "</form>";}
		} else {
			echo "<div align='center'><strong>".$LANG["common"][54]."</strong></div>";

		}

		return true;

	}


}

?>
