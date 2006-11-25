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
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')){
	die("Sorry. You can't access directly to this file");
	}

// CLASSES Printers


class Printer  extends CommonDBTM {

	function Printer () {
		$this->table="glpi_printers";
		$this->type=PRINTER_TYPE;
		$this->dohistory=true;

	}	

	function defineOnglets($withtemplate){
		global $LANG,$CFG_GLPI;

		if (haveRight("cartridge","r"))	
			$ong[1]=$LANG["title"][26];
		if (haveRight("contract_infocom","r"))	
			$ong[3]=$LANG["title"][27];
		if (haveRight("networking","r")||haveRight("computer","r"))
			$ong[4]=$LANG["Menu"][26];
		if (haveRight("document","r"))
			$ong[5]=$LANG["title"][25];

		if(empty($withtemplate)){
			if (haveRight("show_ticket","1"))	
				$ong[6]=$LANG["title"][28];
			if (haveRight("link","r"))
				$ong[7]=$LANG["title"][34];
			if (haveRight("notes","r"))
				$ong[10]=$LANG["title"][37];
			if (haveRight("reservation_central","r"))
				$ong[11]=$LANG["title"][35];
				
			$ong[12]=$LANG["title"][38];

		}	
		return $ong;
	}


	function prepareInputForUpdate($input) {
		// set new date.
		$input["date_mod"] = date("Y-m-d H:i:s");

		return $input;
	}

	function post_updateItem($input,$updates,$history=1) {

		if(isset($input["state"])){
			if (isset($input["is_template"])&&$input["is_template"]==1){
				updateState(PRINTER_TYPE,$input["ID"],$input["state"],1,0);
			}else {
				updateState(PRINTER_TYPE,$input["ID"],$input["state"],0,$history);
			}
		}
	}

	function prepareInputForAdd($input) {
		// set new date.
		$input["date_mod"] = date("Y-m-d H:i:s");

		// dump status
		$input["_oldID"]=$input["ID"];
		unset($input['withtemplate']);
		unset($input['ID']);

		// Manage state
		$input["_state"]=-1;
		if (isset($input["state"])){
			$input["_state"]=$input["state"];
			unset($input["state"]);
		}

		return $input;
	}

	function postAddItem($newID,$input) {
		global $DB;
		// Add state
		if ($input["_state"]>0){
			if (isset($input["is_template"])&&$input["is_template"]==1)
				updateState(PRINTER_TYPE,$newID,$input["_state"],1,0);
			else updateState(PRINTER_TYPE,$newID,$input["_state"],0,0);
		}

		// ADD Infocoms
		$ic= new Infocom();
		if ($ic->getFromDBforDevice(PRINTER_TYPE,$input["_oldID"])){
			$ic->fields["FK_device"]=$newID;
			unset ($ic->fields["ID"]);
			if (isset($ic->fields["num_immo"])) {
				$ic->fields["num_immo"] = autoName($ic->fields["num_immo"], "num_immo", 1, INFOCOM_TYPE);
			}

			$ic->addToDB();
		}

		// ADD Ports
		$query="SELECT ID from glpi_networking_ports WHERE on_device='".$input["_oldID"]."' AND device_type='".PRINTER_TYPE."';";
		$result=$DB->query($query);
		if ($DB->numrows($result)>0){

			while ($data=$DB->fetch_array($result)){
				$np= new Netport();
				$np->getFromDB($data["ID"]);
				unset($np->fields["ID"]);
				unset($np->fields["ifaddr"]);
				unset($np->fields["ifmac"]);
				unset($np->fields["netpoint"]);
				$np->fields["on_device"]=$newID;
				$np->addToDB();
			}
		}

		// ADD Contract				
		$query="SELECT FK_contract from glpi_contract_device WHERE FK_device='".$input["_oldID"]."' AND device_type='".PRINTER_TYPE."';";
		$result=$DB->query($query);
		if ($DB->numrows($result)>0){

			while ($data=$DB->fetch_array($result))
				addDeviceContract($data["FK_contract"],PRINTER_TYPE,$newID);
		}

		// ADD Documents			
		$query="SELECT FK_doc from glpi_doc_device WHERE FK_device='".$input["_oldID"]."' AND device_type='".PRINTER_TYPE."';";
		$result=$DB->query($query);
		if ($DB->numrows($result)>0){

			while ($data=$DB->fetch_array($result))
				addDeviceDocument($data["FK_doc"],PRINTER_TYPE,$newID);
		}

	}


	function cleanDBonPurge($ID) {
		global $DB,$CFG_GLPI;


		$job =new Job();
		$query = "SELECT * FROM glpi_tracking WHERE (computer = '$ID'  AND device_type='".PRINTER_TYPE."')";
		$result = $DB->query($query);

		if ($DB->numrows($result))
			while ($data=$DB->fetch_array($result)) {
				if ($CFG_GLPI["keep_tracking_on_delete"]==1){
					$query = "UPDATE glpi_tracking SET computer = '0', device_type='0' WHERE ID='".$data["ID"]."';";
					$DB->query($query);
				} else $job->delete(array("ID"=>$data["ID"]));
			}


		$query = "SELECT ID FROM glpi_networking_ports WHERE (on_device = '$ID' AND device_type = '".PRINTER_TYPE."')";
		$result = $DB->query($query);
		while ($data = $DB->fetch_array($result)){
			$q = "DELETE FROM glpi_networking_wire WHERE (end1 = '".$data["ID"]."' OR end2 = '".$data["ID"]."')";
			$result2 = $DB->query($q);					
		}

		$query2 = "DELETE FROM glpi_networking_ports WHERE (on_device = $ID AND device_type = '".PRINTER_TYPE."')";
		$result2 = $DB->query($query2);

		$query2 = "DELETE from glpi_connect_wire WHERE (end1 = '$ID' AND type = '".PRINTER_TYPE."')";
		$result2 = $DB->query($query2);


		$query="select * from glpi_reservation_item where (device_type='".PRINTER_TYPE."' and id_device='$ID')";
		if ($result = $DB->query($query)) {
			if ($DB->numrows($result)>0){
				$rr=new ReservationItem();
				$rr->delete(array("ID"=>$DB->result($result,0,"ID")));
			}
		}

		$query = "DELETE FROM glpi_infocoms WHERE (FK_device = '$ID' AND device_type='".PRINTER_TYPE."')";
		$result = $DB->query($query);

		$query = "DELETE FROM glpi_state_item WHERE (id_device = '$ID' AND device_type='".PRINTER_TYPE."')";
		$result = $DB->query($query);

		$query = "DELETE FROM glpi_contract_device WHERE (FK_device = '$ID' AND device_type='".PRINTER_TYPE."')";
		$result = $DB->query($query);

		$query = "UPDATE glpi_cartridges  SET FK_glpi_printers = NULL WHERE (FK_glpi_printers='$ID')";
		$result = $DB->query($query);

	}


	function title(){
		global  $LANG,$CFG_GLPI;

		$buttons=array();
		$title=$LANG["Menu"][2];
		if (haveRight("printer","w")){
			$buttons["setup.templates.php?type=".PRINTER_TYPE."&amp;add=1"]=$LANG["printers"][0];
			$buttons["setup.templates.php?type=".PRINTER_TYPE."&amp;add=0"]=$LANG["common"][8];
			$title="";
		}
		displayTitle($CFG_GLPI["root_doc"]."/pics/printer.png",$LANG["Menu"][2],$title,$buttons);
	}

	function showForm ($target,$ID,$withtemplate='') {

		global $CFG_GLPI, $LANG;
		if (!haveRight("printer","r")) return false;



		$printer_spotted = false;
		$unknown_device=false;
		if((empty($ID) && $withtemplate == 1)||$ID==-1) {
			if($this->getEmpty()) $printer_spotted = true;
			$unknown_device=true;
		} else {
			if($this->getfromDB($ID)&&haveAccessToEntity($this->fields["FK_entities"])) $printer_spotted = true;
		}

		if($printer_spotted) {

			$this->showOnglets($ID, $withtemplate,$_SESSION['glpi_onglet']);

			if(!empty($withtemplate) && $withtemplate == 2) {
				$template = "newcomp";
				$datestring = $LANG["computers"][14].": ";
				$date = convDateTime(date("Y-m-d H:i:s"));
			} elseif(!empty($withtemplate) && $withtemplate == 1) { 
				$template = "newtemplate";
				$datestring = $LANG["computers"][14].": ";
				$date = convDateTime(date("Y-m-d H:i:s"));
			} else {
				$datestring = $LANG["common"][26].": ";
				$date = convDateTime($this->fields["date_mod"]);
				$template = false;
			}


			echo "<div align='center' ><form method='post' name='form' action=\"$target\">\n";
			if(strcmp($template,"newtemplate") === 0) {
				echo "<input type=\"hidden\" name=\"is_template\" value=\"1\" />\n";
			}
			if ($unknown_device){
				echo "<input type='hidden' name='FK_entities' value='".$_SESSION["glpiactive_entity"]."'>";
			}
			echo "<table class='tab_cadre_fixe' cellpadding='2'>\n";

			echo "<tr><th align='center' >\n";
			if(!$template) {
				echo $LANG["printers"][29].": ".$this->fields["ID"];
			}elseif (strcmp($template,"newcomp") === 0) {
				echo $LANG["printers"][28].": ".$this->fields["tplname"];
				echo "<input type='hidden' name='tplname' value='".$this->fields["tplname"]."'>";
			}elseif (strcmp($template,"newtemplate") === 0) {
				echo $LANG["common"][6]."&nbsp;: ";
				autocompletionTextField("tplname","glpi_printers","tplname",$this->fields["tplname"],20);		
			}

			echo "</th><th  align='center'>".$datestring.$date;
			if (!$template&&!empty($this->fields['tplname']))
				echo "&nbsp;&nbsp;&nbsp;(".$LANG["common"][13].": ".$this->fields['tplname'].")";
			echo "</th></tr>\n";

			if (!($CFG_GLPI["cache"]->start($ID."_".$_SESSION["glpilanguage"],"GLPI_".$this->type))) {
				echo "<tr><td class='tab_bg_1' valign='top'>\n";
	
				// table identification
				echo "<table cellpadding='1' cellspacing='0' border='0'>\n";
				echo "<tr><td>".$LANG["common"][16]."*:	</td>\n";
				echo "<td>";
				$objectName = autoName($this->fields["name"], "name", ($template === "newcomp"), PRINTER_TYPE);
				autocompletionTextField("name","glpi_printers","name",$objectName,20);
	
				//		autocompletionTextField("name","glpi_printers","name",$this->fields["name"],20);		
				echo "</td></tr>\n";
	
				echo "<tr><td>".$LANG["common"][15].": 	</td><td>\n";
				dropdownValue("glpi_dropdown_locations", "location", $this->fields["location"],1,$this->fields["FK_entities"]);
				echo "</td></tr>\n";
	
				echo "<tr class='tab_bg_1'><td>".$LANG["common"][5].": 	</td><td colspan='2'>\n";
				dropdownValue("glpi_enterprises","FK_glpi_enterprise",$this->fields["FK_glpi_enterprise"]);
				echo "</td></tr>\n";
	
				echo "<tr class='tab_bg_1'><td>".$LANG["common"][10].": 	</td><td colspan='2'>\n";
				dropdownUsersID("tech_num", $this->fields["tech_num"],"interface",1,$this->fields["FK_entities"]);
				echo "</td></tr>\n";
	
				echo "<tr><td>".$LANG["common"][21].":	</td><td>\n";
				autocompletionTextField("contact_num","glpi_printers","contact_num",$this->fields["contact_num"],20);			
				echo "</td></tr>\n";
	
				echo "<tr><td>".$LANG["printers"][8].":	</td><td>\n";
				autocompletionTextField("contact","glpi_printers","contact",$this->fields["contact"],20);			
				echo "</td></tr>\n";
	
				echo "<tr><td>".$LANG["common"][34].": 	</td><td>";
				dropdownAllUsers("FK_users", $this->fields["FK_users"],1,$this->fields["FK_entities"]);
				echo "</td></tr>";
	
				echo "<tr><td>".$LANG["common"][35].": 	</td><td>";
				dropdownValue("glpi_groups", "FK_groups", $this->fields["FK_groups"],1,$this->fields["FK_entities"]);
				echo "</td></tr>";
	
	
				
	
				echo "<tr><td>".$LANG["setup"][88].": 	</td><td>\n";
				dropdownValue("glpi_dropdown_network", "network", $this->fields["network"]);
				echo "</td></tr>\n";
	
				echo "<tr><td>".$LANG["setup"][89].": 	</td><td>\n";
				dropdownValue("glpi_dropdown_domain", "domain", $this->fields["domain"]);
				echo "</td></tr>\n";
	
	
				echo "</table>"; // fin table indentification
	
				echo "</td>\n";	
				echo "<td class='tab_bg_1' valign='top'>\n";
	
				// table type,serial..
				echo "<table cellpadding='1' cellspacing='0' border='0'>\n";
	
				echo "<tr><td>".$LANG["state"][0].":</td><td>\n";
				$si=new StateItem();
				$t=0;
				if ($template) $t=1;
				$si->getfromDB(PRINTER_TYPE,$this->fields["ID"],$t);
				dropdownValue("glpi_dropdown_state", "state",$si->fields["state"]);
				echo "</td></tr>\n";
	
				echo "<tr><td>".$LANG["common"][17].": 	</td><td>\n";
				dropdownValue("glpi_type_printers", "type", $this->fields["type"]);
				echo "</td></tr>\n";
	
				echo "<tr><td>".$LANG["common"][22].": 	</td><td>";
				dropdownValue("glpi_dropdown_model_printers", "model", $this->fields["model"]);
				echo "</td></tr>";
	
				echo "<tr><td>".$LANG["common"][19].":	</td><td>\n";
				autocompletionTextField("serial","glpi_printers","serial",$this->fields["serial"],20);	echo "</td></tr>\n";
	
				echo "<tr><td>".$LANG["common"][20]."*:</td><td>\n";
				$objectName = autoName($this->fields["otherserial"], "otherserial", ($template === "newcomp"), PRINTER_TYPE);
				autocompletionTextField("otherserial","glpi_printers","otherserial",$objectName,20);
	
				//autocompletionTextField("otherserial","glpi_printers","otherserial",$this->fields["otherserial"],20);
				echo "</td></tr>\n";
	
				echo "<tr><td>".$LANG["printers"][18].": </td><td>\n";
	
				// serial interface?
				echo "<table border='0' cellpadding='2' cellspacing='0'><tr>\n";
				echo "<td>".$LANG["printers"][14]."</td>\n";
				echo "<td>";
				dropdownYesNoInt("flags_serial",$this->fields["flags_serial"]);
				echo "</td>";
				echo "</tr></table>\n";
	
				// parallel interface?
				echo "<table border='0' cellpadding='2' cellspacing='0'><tr>\n";
				echo "<td>".$LANG["printers"][15]."</td>\n";
				echo "<td>";
				dropdownYesNoInt("flags_par",$this->fields["flags_par"]);
				echo "</td>";
	
				echo "</tr></table>\n";
	
				// USB ?
				echo "<table border='0' cellpadding='2' cellspacing='0'><tr>\n";
				echo "<td>".$LANG["printers"][27]."</td>\n";
				echo "<td>";
				dropdownYesNoInt("flags_usb",$this->fields["flags_usb"]);
				echo "</td>";
	
				echo "</tr></table>\n";
	
				// Ram ?
				echo "<tr><td>".$LANG["printers"][23].":</td><td>\n";
				autocompletionTextField("ramSize","glpi_printers","ramSize",$this->fields["ramSize"],20);
				echo "</td></tr>\n";
				// Initial count pages ?
				echo "<tr><td>".$LANG["printers"][30].":</td><td>\n";
				autocompletionTextField("initial_pages","glpi_printers","initial_pages",$this->fields["initial_pages"],20);		
				echo "</td></tr>\n";
	
	
				echo "<tr><td>".$LANG["printers"][35].":</td><td>";
				globalManagementDropdown($target,$withtemplate,$this->fields["ID"],$this->fields["is_global"]);
				echo "</td></tr>";
	
				echo "</table>\n";
				echo "</td>\n";	
				echo "</tr>\n";
	
				echo "<tr>\n";
				echo "<td class='tab_bg_1' valign='top' colspan='2'>\n";
	
				// table commentaires
				echo "<table width='100%' cellpadding='0' cellspacing='0' border='0'><tr><td valign='top'>\n";
				echo $LANG["common"][25].":	</td>\n";
				echo "<td align='center'><textarea cols='35' rows='4' name='comments' >".$this->fields["comments"]."</textarea>\n";
				echo "</td></tr></table>\n";
	
				echo "</td>\n";
				echo "</tr>\n";
				$CFG_GLPI["cache"]->end();
			}



			if (haveRight("printer","w")){
				echo "<tr>\n";

				if ($template) {

					if (empty($ID)||$withtemplate==2){
						echo "<td class='tab_bg_2' align='center' colspan='2'>\n";
						echo "<input type='hidden' name='ID' value=$ID>";
						echo "<input type='submit' name='add' value=\"".$LANG["buttons"][8]."\" class='submit'>";
						echo "</td>\n";
					} else {
						echo "<td class='tab_bg_2' align='center' colspan='2'>\n";
						echo "<input type='hidden' name='ID' value=$ID>";
						echo "<input type='submit' name='update' value=\"".$LANG["buttons"][7]."\" class='submit'>";
						echo "</td>\n";
					}

				} else {

					echo "<td class='tab_bg_2' valign='top' align='center'>";
					echo "<input type='hidden' name='ID' value=\"$ID\">\n";
					echo "<input type='submit' name='update' value=\"".$LANG["buttons"][7]."\" class='submit'>";
					echo "</td>\n\n";
					echo "<td class='tab_bg_2' valign='top' align='center'>\n";
					echo "<div align='center'>";
					if ($this->fields["deleted"]=='N')
						echo "<input type='submit' name='delete' value=\"".$LANG["buttons"][6]."\" class='submit'>";
					else {
						echo "<input type='submit' name='restore' value=\"".$LANG["buttons"][21]."\" class='submit'>";

						echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type='submit' name='purge' value=\"".$LANG["buttons"][22]."\" class='submit'>";
					}
					echo "</div>";
					echo "</td>";

				}
				echo "</tr>";
			}
			echo "</table></form></div>";

			return true;	
		}
		else {
			echo "<div align='center'><b>".$LANG["printers"][17]."</b></div>";
			return false;
		}

	}




}

?>
