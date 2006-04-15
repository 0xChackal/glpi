<?php
/*
 * @version $Id$
 ----------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2006 by the INDEPNET Development Team.
 
 http://indepnet.net/   http://glpi.indepnet.org
 ----------------------------------------------------------------------

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
 ------------------------------------------------------------------------
*/

// Based on:
// IRMA, Information Resource-Management and Administration
// Christian Bauer 
// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

include ("_relpos.php");
// FUNCTIONS Setup

function showFormTreeDown ($target,$name,$human,$ID,$value2='',$where='',$tomove='',$type='') {

	global $cfg_glpi, $lang, $HTMLRel;

	if (!haveRight("dropdown","w")) return false;

	echo "<div align='center'>&nbsp;\n";
	echo "<form method='post' action=\"$target\">";

	echo "<table class='tab_cadre_fixe'  cellpadding='1'>\n";
	echo "<tr><th colspan='3'>$human:</th></tr>";
	if (countElementsInTable("glpi_dropdown_".$name)>0){
	echo "<tr><td  align='center' valign='middle' class='tab_bg_1'>";
	echo "<input type='hidden' name='which' value='$name'>";


	$value=getTreeLeafValueName("glpi_dropdown_".$name,$ID,1);

	dropdownValue("glpi_dropdown_".$name, "ID",$ID,0);
        // on ajoute un input text pour entrer la valeur modifier
		echo "&nbsp;&nbsp<input type='image' class='calendrier' src=\"".$HTMLRel."pics/puce.gif\" alt='' title='' name='fillright' value='fillright'>&nbsp";


 	echo "<input type='text' maxlength='100' size='20' name='value' value=\"".$value["name"]."\"><br>";
	echo "<textarea rows='2' cols='50' name='comments' title='".$lang["common"][25]."' alt='".$lang["common"][25]."'>".$value["comments"]."</textarea>";

	echo "</td><td align='center' class='tab_bg_2' width='99'>";
	echo "<input type='hidden' name='tablename' value='glpi_dropdown_".$name."'>";
	//  on ajoute un bouton modifier
        echo "<input type='submit' name='update' value='".$lang["buttons"][14]."' class='submit'>";
        echo "</td><td align='center' class='tab_bg_2' width='99'>";
        //
        echo "<input type='submit' name='delete' value=\"".$lang["buttons"][6]."\" class='submit'>";
	echo "</td></tr></table></form>";

	echo "<form method='post' action=\"$target\">";

	echo "<input type='hidden' name='which' value='$name'>";
	echo "<table class='tab_cadre_fixe' cellpadding='1'>\n";
	
	echo "<tr><td align='center' class='tab_bg_1'>";

	dropdownValue("glpi_dropdown_".$name, "value_to_move",$tomove,0);
	echo "&nbsp;&nbsp;&nbsp;".$lang["setup"][75]." :&nbsp;&nbsp;&nbsp;";

	dropdownValue("glpi_dropdown_".$name, "value_where",$where,0);
	echo "</td><td align='center' colspan='2' class='tab_bg_2' width='202'>";
	echo "<input type='hidden' name='tablename' value='glpi_dropdown_".$name."' >";
	echo "<input type='submit' name='move' value=\"".$lang["buttons"][20]."\" class='submit'>";
	
	echo "</td></tr></table></form>";	
		
	}

	echo "<form action=\"$target\" method='post'>";
	echo "<input type='hidden' name='which' value='$name'>";

	echo "<table class='tab_cadre_fixe' cellpadding='1'>\n";
	echo "<tr><td  align='center'  class='tab_bg_1'>";
		echo "<input type='text' maxlength='100' size='15' name='value'>&nbsp;&nbsp;&nbsp;";


	if (countElementsInTable("glpi_dropdown_".$name)>0){
		echo "<select name='type'>";
		echo "<option value='under' ".($type=='under'?" selected ":"").">".$lang["setup"][75]."</option>";
		echo "<option value='same' ".($type=='same'?" selected ":"").">".$lang["setup"][76]."</option>";
		echo "</select>&nbsp;&nbsp;&nbsp;";
;
		dropdownValue("glpi_dropdown_".$name, "value2",$value2,0);
		}		
	else echo "<input type='hidden' name='type' value='first'>";

	echo "<br><textarea rows='2' cols='50' name='comments' title='".$lang["common"][25]."' alt='".$lang["common"][25]."'></textarea>";
	 		
	echo "</td><td align='center' colspan='2' class='tab_bg_2'  width='202'>";
	echo "<input type='hidden' name='tablename' value='glpi_dropdown_".$name."' >";
	echo "<input type='submit' name='add' value=\"".$lang["buttons"][8]."\" class='submit'>";
	echo "</td></tr>";
	
	
	
	echo "</table></form></div>";
}


function showFormDropDown ($target,$name,$human,$ID,$value2='') {

	global $db,$cfg_glpi, $lang, $HTMLRel;

	if (!haveRight("dropdown","w")) return false;

	echo "<div align='center'>&nbsp;";
	echo "<form method='post' action=\"$target\">";

	echo "<table class='tab_cadre_fixe' cellpadding='1'>";
	echo "<tr><th colspan='3'>$human:</th></tr>";
	if (countElementsInTable("glpi_dropdown_".$name)>0){
	echo "<tr><td class='tab_bg_1' align='center' valign='top'>";
	echo "<input type='hidden' name='which' value='$name'>";

	dropdownValue("glpi_dropdown_".$name, "ID",$ID,0);
        // on ajoute un input text pour entrer la valeur modifier
		echo "&nbsp;&nbsp;<input type='image' class='calendrier'  src=\"".$HTMLRel."pics/puce.gif\" alt='' title='' name='fillright' value='fillright'>&nbsp;";

//        echo "<img src=\"".$HTMLRel."pics/puce.gif\" alt='' title=''>";
	if ($name != "netpoint"){
		if (!empty($ID)){
			$value=getDropdownName("glpi_dropdown_".$name,$ID,1);
		}
		else $value=array("name"=>"","comments"=>"");
	} else {$value="";$loc="";}

	if($name == "netpoint") {
		$query = "select * from glpi_dropdown_netpoint where ID = '". $ID ."'";
		$result = $db->query($query);
		$value=$loc=$comments="";
		if($db->numrows($result) == 1) {
		$value = $db->result($result,0,"name");
		$loc = $db->result($result,0,"location");
		$comments = $db->result($result,0,"comments");
		}
		echo "<br>";
		echo $lang["common"][15].": ";		

		dropdownValue("glpi_dropdown_locations", "value2",$loc,0);
		echo $lang["networking"][52].": ";
		echo "<input type='text' maxlength='100' size='10' name='value' value=\"".$value."\"><br>";
		echo "<textarea rows='2' cols='50' name='comments' title='".$lang["common"][25]."' alt='".$lang["common"][25]."'>".$comments."</textarea>";

	} 
	else {
		
        	echo "<input type='text' maxlength='100' size='20' name='value' value=\"".$value["name"]."\"><br>";
		echo "<textarea rows='2' cols='50' name='comments' title='".$lang["common"][25]."' alt='".$lang["common"][25]."'>".$value["comments"]."</textarea>";
        }
	//
	echo "</td><td align='center' class='tab_bg_2' width='99'>";
	echo "<input type='hidden' name='tablename' value='glpi_dropdown_".$name."'>";
	//  on ajoute un bouton modifier
        echo "<input type='submit' name='update' value='".$lang["buttons"][14]."' class='submit'>";
        echo "</td><td align='center' class='tab_bg_2' width='99'>";
        //
        echo "<input type='submit' name='delete' value=\"".$lang["buttons"][6]."\" class='submit'>";
	echo "</td></tr></table></form>";
	
	}
	echo "<form action=\"$target\" method='post'>";
	echo "<input type='hidden' name='which' value='$name'>";
	echo "<table class='tab_cadre_fixe' cellpadding='1'>";
	echo "<tr><td align='center'  class='tab_bg_1'>";
	if($name == "netpoint") {
		echo $lang["common"][15].": ";		
		dropdownValue("glpi_dropdown_locations", "value2",$value2,0);
		echo $lang["networking"][52].": ";
		echo "<input type='text' maxlength='100' size='10' name='value'><br>";
		echo "<textarea rows='2' cols='50' name='comments' title='".$lang["common"][25]."' alt='".$lang["common"][25]."'></textarea>";
	}
	else {
		echo "<input type='text' maxlength='100' size='20' name='value'><br>";
		echo "<textarea rows='2' cols='50' name='comments' title='".$lang["common"][25]."' alt='".$lang["common"][25]."'></textarea>";
	}
	echo "</td><td align='center' colspan='2' class='tab_bg_2' width='202'>";
	echo "<input type='hidden' name='tablename' value='glpi_dropdown_".$name."' >";
	echo "<input type='submit' name='add' value=\"".$lang["buttons"][8]."\" class='submit'>";
	echo "</td></tr>";
	
	// Multiple Add for Netpoint
	if($name == "netpoint") {
		echo "</table></form>";

		echo "<form action=\"$target\" method='post'>";
		echo "<input type='hidden' name='which' value='$name'>";
		echo "<table class='tab_cadre_fixe' cellpadding='1'>";
		echo "<tr><td align='center'  class='tab_bg_1'>";

		echo $lang["common"][15].": ";		
		dropdownValue("glpi_dropdown_locations", "value2",$value2,0);
		echo $lang["networking"][52].": ";
		echo "<input type='text' maxlength='100' size='5' name='before'>";
		echo "<select name='from'>";
		for ($i=0;$i<400;$i++) echo "<option value='$i'>$i</option>";
		echo "</select>";
		echo "-->";
		echo "<select name='to'>";
		for ($i=0;$i<400;$i++) echo "<option value='$i'>$i</option>";
		echo "</select>";

		echo "<input type='text' maxlength='100' size='5' name='after'><br>";	
		echo "<textarea rows='2' cols='50' name='comments' title='".$lang["common"][25]."' alt='".$lang["common"][25]."'></textarea>";
		echo "</td><td align='center' colspan='2' class='tab_bg_2' width='202'>";
		echo "<input type='hidden' name='tablename' value='glpi_dropdown_".$name."' >";
		echo "<input type='submit' name='several_add' value=\"".$lang["buttons"][8]."\" class='submit'>";
		echo "</td></tr>";
	}
	
	echo "</table></form></div>";
}

function showFormTypeDown ($target,$name,$human,$ID) {

	global $cfg_glpi, $lang, $HTMLRel;
	
	if (!haveRight("dropdown","w")) return false;	

	echo "<div align='center'>&nbsp;";
	
	echo "<form action=\"$target\" method='post'>";
	
	echo "<table class='tab_cadre_fixe'>";
	echo "<tr><th colspan='3'>$human:</th></tr>";
	
	if (countElementsInTable("glpi_type_".$name)>0){
	echo "<tr><td align='center' valign='center' class='tab_bg_1'>";

	dropdownValue("glpi_type_".$name, "ID",$ID,0);
	// on ajoute un input text pour entrer la valeur modifier
		echo "&nbsp;&nbsp;<input type='image' class='calendrier' src=\"".$HTMLRel."pics/puce.gif\" alt='' title='' name='fillright' value='fillright'>&nbsp;";

	if (!empty($ID))
		$value=getDropdownName("glpi_type_".$name,$ID,1);
	else $value=array("name"=>"","comments"=>"");

	echo "<input type='text' maxlength='100' size='20' name='value'  value=\"".$value["name"]."\"><br>";
	echo "<textarea rows='2' cols='50' name='comments' title='".$lang["common"][25]."' alt='".$lang["common"][25]."'>".$value["comments"]."</textarea>";

	echo "</td><td align='center' class='tab_bg_2'>";
	echo "<input type='hidden' name='tablename' value='glpi_type_".$name."'>";
	echo "<input type='hidden' name='which' value='$name'>";
	
	//  on ajoute un bouton modifier
        echo "<input type='submit' name='update' value='".$lang["buttons"][14]."' class='submit'>";
	echo "</td><td align='center' class='tab_bg_2'>";
        echo "<input type='submit' name='delete' value=\"".$lang["buttons"][6]."\" class='submit'>";
	echo "</td></tr>";
	}
	echo "</table></form>";
	
	echo "<form action=\"$target\" method='post'>";
	echo "<table class='tab_cadre_fixe'>";
	echo "<tr><td align='center' class='tab_bg_1'>";
	echo "<input type='text' maxlength='100' size='20' name='value'><br>";
	echo "<textarea rows='2' cols='50' name='comments' title='".$lang["common"][25]."' alt='".$lang["common"][25]."'></textarea>";

	echo "</td><td align='center' colspan='2' class='tab_bg_2'>";
	echo "<input type='hidden' name='tablename' value='glpi_type_".$name."'>";
	echo "<input type='hidden' name='which' value='$name'>";
	echo "<input type='submit' name='add' value=\"".$lang["buttons"][8]."\" class='submit'>";
	echo "</td></tr>";
	echo "</table></form></div>";
}
function moveTreeUnder($table,$to_move,$where){
	global $db;
	if ($where!=$to_move){
		// Is the $where location under the to move ???
		$impossible_move=false;
		
		$current_ID=$where;
		while ($current_ID!=0&&$impossible_move==false){

		$query="select * from $table WHERE ID='$current_ID'";
		$result = $db->query($query);
		$current_ID=$db->result($result,0,"parentID");
		if ($current_ID==$to_move) $impossible_move=true;

		}
		if (!$impossible_move){
	
			// Move Location
			$query = "UPDATE $table SET parentID='$where' where ID='$to_move'";
			$result = $db->query($query);
			regenerateTreeCompleteNameUnderID($table,$to_move);
		}	
	
	}	
}

function updateDropdown($input) {
	global $db,$cfg_glpi;
	
		
	if($input["tablename"] == "glpi_dropdown_netpoint") {
		$query = "update ".$input["tablename"]." SET name = '".$input["value"]."', location = '".$input["value2"]."', comments='".$input["comments"]."' where ID = '".$input["ID"]."'";
		
	}
	else {
		$query = "update ".$input["tablename"]." SET name = '".$input["value"]."', comments='".$input["comments"]."' where ID = '".$input["ID"]."'";
	}
	
	if ($result=$db->query($query)) {
		if (in_array($input["tablename"],$cfg_glpi["dropdowntree_tables"]))
			regenerateTreeCompleteNameUnderID($input["tablename"],$input["ID"]);
		return true;
	} else {
		return false;
	}
}


function addDropdown($input) {
	global $db,$cfg_glpi;
	
	if (!empty($input["value"])){

	if($input["tablename"] == "glpi_dropdown_netpoint") {
		$query = "INSERT INTO ".$input["tablename"]." (name,location,comments) VALUES ('".$input["value"]."', '".$input["value2"]."', '".$input["comments"]."')";
	}
	else if (in_array($input["tablename"],$cfg_glpi["dropdowntree_tables"])){
		if ($input['type']=="first"){
		    $query = "INSERT INTO ".$input["tablename"]." (name,parentID,completename,comments) VALUES ('".$input["value"]."', '0','','".$input["comments"]."')";		
		} else {
			$query="SELECT * from ".$input["tablename"]." where ID='".$input["value2"]."'";
			$result=$db->query($query);
			if ($db->numrows($result)>0){
				$data=$db->fetch_array($result);
				$level_up=$data["parentID"];
				if ($input["type"]=="under") {
					$level_up=$data["ID"];
				} 
				$query = "INSERT INTO ".$input["tablename"]." (name,parentID,completename,comments) VALUES ('".$input["value"]."', '$level_up','','".$input["comments"]."')";		
			} else $query = "INSERT INTO ".$input["tablename"]." (name,parentID,completename,comments) VALUES ('".$input["value"]."', '0','','".$input["comments"]."')";				
		}
	}
	else {
		$query = "INSERT INTO ".$input["tablename"]." (name,comments) VALUES ('".$input["value"]."','".$input["comments"]."')";
	}

	if ($result=$db->query($query)) {

		if (in_array($input["tablename"],$cfg_glpi["dropdowntree_tables"]))
			regenerateTreeCompleteNameUnderID($input["tablename"],$db->insert_id());		
		return true;
	} else {
		return false;
	}
}
}

function deleteDropdown($input) {

	global $db;
	$send = array();
	$send["tablename"] = $input["tablename"];
	$send["oldID"] = $input["ID"];
	$send["newID"] = "NULL";
	replaceDropDropDown($send);
}

//replace all entries for a dropdown in each items
function replaceDropDropDown($input) {
	global $db;
	$name = getDropdownNameFromTable($input["tablename"]);
	switch($name) {
	case "cartridge_type":
		$query = "update glpi_cartridges_type set type = '". $input["newID"] ."'  where type = '".$input["oldID"]."'";
		$db->query($query);
		break;
	case "consumable_type":
		$query = "update glpi_consumables_type set type = '". $input["newID"] ."'  where type = '".$input["oldID"]."'";
		$db->query($query);
		break;
	case "contact_type":
		$query = "update glpi_contacts set type = '". $input["newID"] ."'  where type = '".$input["oldID"]."'";
		$db->query($query);
		break;
	case "contract_type":
		$query = "update glpi_contracts set contract_type = '". $input["newID"] ."'  where contract_type = '".$input["oldID"]."'";
		$db->query($query);
		break;
	case "ram_type":
		$query = "update glpi_device_ram set type = '". $input["newID"] ."'  where type = '".$input["oldID"]."'";
		$db->query($query);
		break;
	case "hdd_type":
		$query = "update glpi_device_hdd set interface = '". $input["newID"] ."'  where interface = '".$input["oldID"]."'";
		$db->query($query);
		break;	
	case "vlan":
		$query = "update glpi_networking_vlan set FK_vlan = '". $input["newID"] ."'  where FK_vlan = '".$input["oldID"]."'";
		$db->query($query);
		break;
	case "domain":
		$query = "update glpi_computers set domain = '". $input["newID"] ."'  where domain = '".$input["oldID"]."'";
		$db->query($query);
		$query = "update glpi_printers set domain = '". $input["newID"] ."'  where domain = '".$input["oldID"]."'";
		$db->query($query);
		$query = "update glpi_networking set domain = '". $input["newID"] ."'  where domain = '".$input["oldID"]."'";
		$db->query($query);
		break;
	case "network":
		$query = "update glpi_computers set network = '". $input["newID"] ."'  where network = '".$input["oldID"]."'";
		$db->query($query);
		$query = "update glpi_printers set network = '". $input["newID"] ."'  where network = '".$input["oldID"]."'";
		$db->query($query);
		$query = "update glpi_networking set network = '". $input["newID"] ."'  where network = '".$input["oldID"]."'";
		$db->query($query);
		break;

	case "enttype":
		$query = "update glpi_enterprises set type = '". $input["newID"] ."'  where type = '".$input["oldID"]."'";
		$db->query($query);
		break;
	case "firmware":
		$query = "update glpi_networking set firmware = '". $input["newID"] ."'  where firmware = '".$input["oldID"]."'";
		$db->query($query);
		break;
	case "os" :
		$query = "update glpi_computers set ". $name ." = '". $input["newID"] ."'  where ". $name ." = '".$input["oldID"]."'";
		$db->query($query);
		$query = "update glpi_software set ". $name ." = '". $input["newID"] ."'  where ". $name ." = '".$input["oldID"]."'";
		$db->query($query);
		break;
	case "os_version" :
		$query = "update glpi_computers set ". $name ." = '". $input["newID"] ."'  where ". $name ." = '".$input["oldID"]."'";
		$db->query($query);
		break;
	case "os_sp" :
		$query = "update glpi_computers set ". $name ." = '". $input["newID"] ."'  where ". $name ." = '".$input["oldID"]."'";
		$db->query($query);
		break;
	case "iface" :
		$query = "update glpi_networking_ports set ". $name ." = '". $input["newID"] ."'  where ". $name ." = '".$input["oldID"]."'";
		$db->query($query);
		break;
	case "location" :
		$query = "update glpi_computers set ". $name ." = '". $input["newID"] ."'  where ". $name ." = '".$input["oldID"]."'";
		$result = $db->query($query);
		$query = "update glpi_monitors set ". $name ." = '". $input["newID"] ."'  where ". $name ." = '".$input["oldID"]."'";
		$result = $db->query($query);
		$query = "update glpi_printers set ". $name ." = '". $input["newID"] ."'  where ". $name ." = '".$input["oldID"]."'";
		$result = $db->query($query);
		$query = "update glpi_software set ". $name ." = '". $input["newID"] ."'  where ". $name ." = '".$input["oldID"]."'";
		$result = $db->query($query);
		$query = "update glpi_networking set ". $name ." = '". $input["newID"] ."'  where ". $name ." = '".$input["oldID"]."'";
		$db->query($query);
		$query = "update glpi_peripherals set ". $name ." = '". $input["newID"] ."'  where ". $name ." = '".$input["oldID"]."'";
		$db->query($query);
		$query = "update glpi_dropdown_netpoint set ". $name ." = '". $input["newID"] ."'  where ". $name ." = '".$input["oldID"]."'";
		$db->query($query);
		$query = "update glpi_cartridges_type set ". $name ." = '". $input["newID"] ."'  where ". $name ." = '".$input["oldID"]."'";
		$db->query($query);
		$query = "update glpi_users set ". $name ." = '". $input["newID"] ."'  where ". $name ." = '".$input["oldID"]."'";
		$result = $db->query($query);

		break;
	case "monitors" :
		$query = "update glpi_monitors set type = '". $input["newID"] ."'  where type = '".$input["oldID"]."'";
		$result = $db->query($query);
		break;
	case "computers" :
		$query = "update glpi_computers set type = '". $input["newID"] ."'  where type = '".$input["oldID"]."'";
		$result = $db->query($query);
		break;
	case "model" :
		$query = "update glpi_computers set model = '". $input["newID"] ."'  where model = '".$input["oldID"]."'";
		$result = $db->query($query);
		break;
	case "model_printers" :
		$query = "update glpi_printers set model = '". $input["newID"] ."'  where model = '".$input["oldID"]."'";
		$result = $db->query($query);
		break;
	case "model_monitors" :
		$query = "update glpi_monitors set model = '". $input["newID"] ."'  where model = '".$input["oldID"]."'";
		$result = $db->query($query);
		break;
	case "model_peripherals" :
		$query = "update glpi_peripherals set model = '". $input["newID"] ."'  where model = '".$input["oldID"]."'";
		$result = $db->query($query);
		break;
	case "model_phones" :
		$query = "update glpi_phones set model = '". $input["newID"] ."'  where model = '".$input["oldID"]."'";
		$result = $db->query($query);
		break;
	case "model_networking" :
		$query = "update glpi_networking set model = '". $input["newID"] ."'  where model = '".$input["oldID"]."'";
		$result = $db->query($query);
		break;
	case "printers" :
		$query = "update glpi_printers set type = '". $input["newID"] ."'  where type = '".$input["oldID"]."'";
		$result = $db->query($query);
		$query = "update glpi_cartridges_assoc set FK_glpi_type_printer = '". $input["newID"] ."'  where FK_glpi_type_printer = '".$input["oldID"]."'";
		$result = $db->query($query);
		break;
	case "networking" :
		$query = "update glpi_networking set type = '". $input["newID"] ."'  where type = '".$input["oldID"]."'";
		$result = $db->query($query);
		break;
	case "netpoint" : 
		$query = "update glpi_networking_ports set netpoint = '". $input["newID"] ."'  where netpoint = '".$input["oldID"]."'";
		$result = $db->query($query);
		break;
	case "rubdocs" : 
		$query = "update glpi_docs set rubrique = '". $input["newID"] ."'  where rubrique = '".$input["oldID"]."'";
		$result = $db->query($query);
		break;
	case "tracking_category":
		$query = "update glpi_tracking set category = '". $input["newID"] ."'  where category = '".$input["oldID"]."'";
		$result = $db->query($query);
		break;
	case "peripherals" :
		$query = "update glpi_peripherals set type = '". $input["newID"] ."'  where type = '".$input["oldID"]."'";
		$result = $db->query($query);
		break;
	case "phones" :
		$query = "update glpi_phones set type = '". $input["newID"] ."'  where type = '".$input["oldID"]."'";
		$result = $db->query($query);
		break;
	case "state" :
		$query = "update glpi_state_item set state = '". $input["newID"] ."'  where state = '".$input["oldID"]."'";
		$result = $db->query($query);
		break;
	case "auto_update" :
		$query = "update glpi_computers set auto_update = '". $input["newID"] ."'  where auto_update = '".$input["oldID"]."'";
		$result = $db->query($query);
		break;
	case "budget" :
		$query = "update glpi_infocoms set budget = '". $input["newID"] ."'  where budget = '".$input["oldID"]."'";
		$db->query($query);
		break;
	case "phone_power" :
		$query = "update glpi_phones set power = '". $input["newID"] ."'  where power = '".$input["oldID"]."'";
		$db->query($query);
		break;
	}

	$query = "delete from ". $input["tablename"] ." where ID = '". $input["oldID"] ."'";
	$db->query($query);
}

function showDeleteConfirmForm($target,$table, $ID) {
	global $db,$lang;

	if (!haveRight("dropdown","w")) return false;
	
	if ($table=="glpi_dropdown_locations"){
		
		$query = "Select count(*) as cpt FROM $table where parentID = '".$ID."'";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  {
		echo "<div align='center'><p style='color:red'>".$lang["setup"][74]."</p></div>";
		return;
		}
	}	

	if ($table=="glpi_dropdown_kbcategories"){
		$query = "Select count(*) as cpt FROM $table where parentID = '".$ID."'";
        	$result = $db->query($query);
	        if($db->result($result,0,"cpt") > 0)  {	
			echo "<div align='center'><p style='color:red'>".$lang["setup"][74]."</p></div>";
			return;
		} else {
			$query = "Select count(*) as cpt FROM glpi_kbitems where categoryID = '".$ID."'";
	        	$result = $db->query($query);
		        if($db->result($result,0,"cpt") > 0)  {
				echo "<div align='center'><p style='color:red'>".$lang["setup"][74]."</p></div>";
				return;
			}
		}
	}
		
	echo "<div align='center'>";
	echo "<p style='color:red'>".$lang["setup"][63]."</p>";
	echo "<p>".$lang["setup"][64]."</p>";
	
	echo "<form action=\"". $target ."\" method=\"post\">";
	echo "<input type=\"hidden\" name=\"tablename\" value=\"". $table ."\"  />";
	echo "<input type=\"hidden\" name=\"ID\" value=\"". $ID ."\"  />";
	echo "<input type=\"hidden\" name=\"which\" value=\"". str_replace("glpi_type_","",str_replace("glpi_dropdown_","",$table)) ."\"  />";
	echo "<input type=\"hidden\" name=\"forcedelete\" value=\"1\" />";
	
	echo "<table class='tab_cadre'><tr><td>";
	echo "<input class='button' type=\"submit\" name=\"delete\" value=\"Confirmer\" /></td>";
	
	echo "<form action=\" ". $target ."\" method=\"post\">";
	echo "<td><input class='button' type=\"submit\" name=\"annuler\" value=\"Annuler\" /></td></tr></table>";
	echo "</form>";
	echo "<p>". $lang["setup"][65]."</p>";
	echo "<form action=\" ". $target ."\" method=\"post\">";
	echo "<input type=\"hidden\" name=\"which\" value=\"". str_replace("glpi_type_","",str_replace("glpi_dropdown_","",$table)) ."\"  />";
	echo "<table class='tab_cadre'><tr><td>";
	dropdownNoValue($table,"newID",$ID);
	echo "<input type=\"hidden\" name=\"tablename\" value=\"". $table ."\"  />";
	echo "<input type=\"hidden\" name=\"oldID\" value=\"". $ID ."\"  />";
	echo "</td><td><input class='button' type=\"submit\" name=\"replace\" value=\"Remplacer\" /></td></tr></table>";
	echo "</form>";
	
	echo "</div>";
}


function getDropdownNameFromTable($table) {
	
	if(ereg("glpi_type_",$table)){
		$name = ereg_replace("glpi_type_","",$table);
	}
	else {
		if($table == "glpi_dropdown_locations") $name = "location";
		else {
			$name = ereg_replace("glpi_dropdown_","",$table);
		}
	}
	return $name;
}

function getDropdownNameFromTableForStats($table) {

	if(ereg("glpi_type_",$table)){
		$name = "type";
	}
	else {
		if($table == "glpi_dropdown_locations") $name = "location";
		else {
			$name = ereg_replace("glpi_dropdown_","",$table);
		}
	}
	return $name;
}


//check if the dropdown $ID is used into item tables
function dropdownUsed($table, $ID) {

	global $db;
	$name = getDropdownNameFromTable($table);
	
	$var1 = true;
	switch($name) {
	case "cartridge_type":
		$query = "Select count(*) as cpt FROM glpi_cartridges_type where type = ".$ID."";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		break;
	case "consumable_type":
		$query = "Select count(*) as cpt FROM glpi_consumables_type where type = ".$ID."";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		break;
	case "contact_type":
		$query = "Select count(*) as cpt FROM glpi_contacts where type = ".$ID."";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		break;
	case "contract_type":
		$query = "Select count(*) as cpt FROM glpi_contracts where contract_type = ".$ID."";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		break;
	case "ram_type":
		$query = "Select count(*) as cpt FROM glpi_device_ram where type = ".$ID."";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		break;
	case "hdd_type":
		$query = "Select count(*) as cpt FROM glpi_device_hdd where interface = ".$ID."";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		break;
	case "vlan":
		$query = "Select count(*) as cpt FROM glpi_networking_vlan where FK_vlan = ".$ID."";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		break;
	case "domain":
		$query = "Select count(*) as cpt FROM glpi_computers where domain = ".$ID."";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		$query = "Select count(*) as cpt FROM glpi_printers where domain = ".$ID."";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		$query = "Select count(*) as cpt FROM glpi_networking where domain = ".$ID."";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		break;
	case "network":
		$query = "Select count(*) as cpt FROM glpi_computers where network = ".$ID."";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		$query = "Select count(*) as cpt FROM glpi_printers where network = ".$ID."";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		$query = "Select count(*) as cpt FROM glpi_networking where nertwork = ".$ID."";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		break;
	case "enttype":
		$query = "Select count(*) as cpt FROM glpi_enterprises where type = ".$ID."";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		break;
	case "firmware":
		$query = "Select count(*) as cpt FROM glpi_networking where firmware = ".$ID."";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		break;
	case "iface" : 
		$query = "Select count(*) as cpt FROM glpi_networking_ports where ". $name ." = ".$ID."";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		break;
	case "kbcategories" :
		$query = "Select count(*) as cpt FROM glpi_dropdown_kbcategories where parentID = ".$ID."";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		$query = "Select count(*) as cpt FROM glpi_kbitems where categoryID = ".$ID."";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		break;
	case "location" :
		$query = "Select count(*) as cpt FROM glpi_dropdown_locations where parentID = ".$ID."";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
	
		$query = "Select count(*) as cpt FROM glpi_computers where ". $name ." = ".$ID."";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		$query = "Select count(*) as cpt FROM glpi_monitors where ". $name ." = ".$ID."";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		$query = "Select count(*) as cpt FROM glpi_printers where ". $name ." = ".$ID."";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		$query = "Select count(*) as cpt FROM glpi_software where ". $name ." = ".$ID."";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		$query = "Select count(*) as cpt FROM glpi_networking where ". $name ." = ".$ID."";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		$query = "Select count(*) as cpt FROM glpi_peripherals where ". $name ." = ".$ID."";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		$query = "Select count(*) as cpt FROM glpi_dropdown_netpoint where ". $name ." = ".$ID."";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		$query = "Select count(*) as cpt FROM glpi_cartridges_type where ". $name ." = ".$ID."";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		$query = "Select count(*) as cpt FROM glpi_users where ". $name ." = ".$ID."";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;

		break;
	case "netpoint" : 
		$query = "Select count(*) as cpt FROM glpi_networking_ports where netpoint = '".$ID."'";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		break;
	case "os" :
		$query = "Select count(*) as cpt FROM glpi_computers where ". $name ." = ".$ID."";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		$query = "Select count(*) as cpt FROM glpi_software where platform = ".$ID."";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;	
		break;
	case "os_version" :
		$query = "Select count(*) as cpt FROM glpi_computers where ". $name ." = ".$ID."";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		break;
	case "os_sp" :
		$query = "Select count(*) as cpt FROM glpi_computers where ". $name ." = ".$ID."";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		break;
	case "rubdocs":
		$query = "Select count(*) as cpt FROM glpi_docs where rubrique = ".$ID."";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		break;
	case "state":
		$query = "Select count(*) as cpt FROM glpi_state_item where state = ".$ID."";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		break;


	case "tracking_category":
		$query = "Select count(*) as cpt FROM glpi_tracking where category = ".$ID."";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		break;
	case "computers" :
		$query = "Select count(*) as cpt FROM glpi_computers where type = '".$ID."'";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		break;
	case "model" :
		$query = "Select count(*) as cpt FROM glpi_computers where model = '".$ID."'";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		break;
	case "model_printers" :
		$query = "Select count(*) as cpt FROM glpi_printers where model = '".$ID."'";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		break;
	case "model_networking" :
		$query = "Select count(*) as cpt FROM glpi_networking where model = '".$ID."'";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		break;
	case "model_monitors" :
		$query = "Select count(*) as cpt FROM glpi_monitors where model = '".$ID."'";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		break;
	case "model_peripherals" :
		$query = "Select count(*) as cpt FROM glpi_peripherals where model = '".$ID."'";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		break;
	case "model_phones" :
		$query = "Select count(*) as cpt FROM glpi_phones where model = '".$ID."'";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		break;
	case "monitors" :
		$query = "Select count(*) as cpt FROM glpi_monitors where type = '".$ID."'";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		break;
	case "networking" :
		$query = "Select count(*) as cpt FROM glpi_networking where type = '".$ID."'";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		break;
	case "peripherals":
		$query = "Select count(*) as cpt FROM glpi_peripherals where type = '".$ID."'";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		break;
	case "phones":
		$query = "Select count(*) as cpt FROM glpi_phones where type = '".$ID."'";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		break;
	case "printers" :
		$query = "Select count(*) as cpt FROM glpi_printers where type = '".$ID."'";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		$query = "Select count(*) as cpt FROM glpi_cartridges_assoc where FK_glpi_type_printer = '".$ID."'";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;

		break;
	case "auto_update" :
		$query = "Select count(*) as cpt FROM glpi_computers where auto_update = '".$ID."'";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		break;
	case "budget" :
		$query = "Select count(*) as cpt FROM glpi_infocoms where budget = '".$ID."'";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		break;
	case "phone_power" :
		$query = "Select count(*) as cpt FROM glpi_phones where power = '".$ID."'";
		$result = $db->query($query);
		if($db->result($result,0,"cpt") > 0)  $var1 = false;
		break;

	}
	return $var1;

}












function listTemplates($type,$target,$add=0) {

	global $db,$cfg_glpi, $lang;

	$ci=new CommonItem();
	$ci->setType($type);
	if (!$ci->haveRight("w")) return false;
		
	switch ($type){
	case COMPUTER_TYPE :
		$title=$lang["Menu"][0];
		$query = "SELECT * FROM glpi_computers where is_template = '1' ORDER by tplname";
		break;
	case NETWORKING_TYPE :
		$title=$lang["Menu"][1];
		$query = "SELECT * FROM glpi_networking where is_template = '1' ORDER by tplname";
		break;
	case MONITOR_TYPE :
		$title=$lang["Menu"][3];
		$query = "SELECT * FROM glpi_monitors where is_template = '1' ORDER by tplname";
		break;	
	case PRINTER_TYPE :
		$title=$lang["Menu"][2];
		$query = "SELECT * FROM glpi_printers where is_template = '1' ORDER by tplname";
		break;	
	case PERIPHERAL_TYPE :
		$title=$lang["Menu"][16];
		$query = "SELECT * FROM glpi_peripherals where is_template = '1' ORDER by tplname";
		break;
	case SOFTWARE_TYPE :
		$title=$lang["Menu"][4];
		$query = "SELECT * FROM glpi_software where is_template = '1' ORDER by tplname";
		break;
	case PHONE_TYPE :
		$title=$lang["Menu"][34];
		$query = "SELECT * FROM glpi_phones where is_template = '1' ORDER by tplname";
		break;

	}
	if ($result = $db->query($query)) {
		
		echo "<div align='center'><table class='tab_cadre' width='50%'>";
		if ($add)
			echo "<tr><th>".$lang["common"][7]." - $title:</th></tr>";
		else 
			echo "<tr><th colspan='2'>".$lang["common"][14]." - $title:</th></tr>";
		
		while ($data= $db->fetch_array($result)) {
			
			$templname = $data["tplname"];
			if ($templname=="Blank Template")
				$templname=$lang["common"][31];
			
			echo "<tr>";
			echo "<td align='center' class='tab_bg_1'>";
			if (!$add){
				echo "<a href=\"$target?ID=".$data["ID"]."&amp;withtemplate=1\">&nbsp;&nbsp;&nbsp;$templname&nbsp;&nbsp;&nbsp;</a></td>";

				echo "<td align='center' class='tab_bg_2'>";
				if ($data["tplname"]!="Blank Template")
				echo "<b><a href=\"$target?ID=".$data["ID"]."&amp;purge=purge&amp;withtemplate=1\">".$lang["buttons"][6]."</a></b>";
				else echo "&nbsp;";
				echo "</td>";
			} else {
				echo "<a href=\"$target?ID=".$data["ID"]."&amp;withtemplate=2\">&nbsp;&nbsp;&nbsp;$templname&nbsp;&nbsp;&nbsp;</a></td>";
			}
			
			echo "</tr>";		

		}

		if (!$add){
			echo "<tr>";
			echo "<td colspan='2' align='center' class='tab_bg_2'>";
			echo "<b><a href=\"$target?withtemplate=1\">".$lang["common"][9]."</a></b>";
			echo "</td>";
			echo "</tr>";
		}
				
		echo "</table></div>";
	}
	

}





function titleConfigGen(){

global  $lang,$HTMLRel;

                echo "<div align='center'><table border='0'><tr><td>";
                echo "<img src=\"".$HTMLRel."pics/configuration.png\" alt='' title=''></td><td><b><span class='icon_sous_nav'>".$lang["setup"][100]."</span>";
		 echo "</b></td></tr></table>&nbsp;</div>";


}

function titleConfigDisplay(){

global  $lang,$HTMLRel;

                echo "<div align='center'><table border='0'><tr><td>";
                echo "<img src=\"".$HTMLRel."pics/configuration.png\" alt='' title=''></td><td><b><span class='icon_sous_nav'>".$lang["setup"][119]."</span>";
		 echo "</b></td></tr></table>&nbsp;</div>";


}

function showFormConfigGen($target){
	
	global  $db,$lang,$HTMLRel,$cfg_glpi;
	
	if (!haveRight("config","w")) return false;	
	
	$query = "select * from glpi_config where ID = 1";
	$result = $db->query($query);
	
	echo "<form name='form' action=\"$target\" method=\"post\">";
	echo "<div align='center'><table class='tab_cadre'>";
	echo "<tr><th colspan='2'>".$lang["setup"][100]."</th></tr>";
	$default_language=$db->result($result,0,"default_language");
	echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][113]." </td><td><select name=\"default_language\">";
		while (list($val)=each($cfg_glpi["languages"])){
		echo "<option value=\"".$val."\"";
			if($default_language==$val){ echo " selected";}
		echo ">".$cfg_glpi["languages"][$val][0];
		}
	
	echo "</select></td></tr>";

	echo "<tr class='tab_bg_2'><td align='center'> ".$lang["setup"][133]." </td><td>   &nbsp;".$lang["choice"][1]."  &nbsp;<input type=\"radio\" name=\"ocs_mode\" value=\"1\" "; if($db->result($result,0,"ocs_mode") == 1) echo "checked=\"checked\""; echo " /> &nbsp;".$lang["choice"][0]."  &nbsp;<input type=\"radio\" name=\"ocs_mode\" value=\"0\" "; if($db->result($result,0,"ocs_mode") == 0) echo "checked"; 
	echo " ></td></tr>";

	
	echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][102]." </td><td><select name=\"event_loglevel\">";
	$level=$db->result($result,0,"event_loglevel");
	echo "<option value=\"1\"";  if($level==1){ echo " selected";} echo ">".$lang["setup"][103]." </option>";
	echo "<option value=\"2\"";  if($level==2){ echo " selected";} echo ">".$lang["setup"][104]."</option>";
	echo "<option value=\"3\"";  if($level==3){ echo " selected";} echo ">".$lang["setup"][105]."</option>";
	echo "<option value=\"4\"";  if($level==4){ echo " selected";} echo ">".$lang["setup"][106]." </option>";
	echo "<option value=\"5\"";  if($level==5){ echo " selected";} echo ">".$lang["setup"][107]."</option>";
	echo "</select></td></tr>";
	echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][109]." </td><td><input type=\"text\" name=\"expire_events\" value=\"". $db->result($result,0,"expire_events") ."\"></td></tr>";

	echo "<tr class='tab_bg_2'><td align='center'> ".$lang["setup"][116]." </td><td>   &nbsp;".$lang["choice"][1]."  &nbsp;<input type=\"radio\" name=\"auto_assign\" value=\"1\" "; if($db->result($result,0,"auto_assign") == 1) echo "checked=\"checked\""; echo " /> &nbsp;".$lang["choice"][0]."  &nbsp;<input type=\"radio\" name=\"auto_assign\" value=\"0\" "; if($db->result($result,0,"auto_assign") == 0) echo "checked"; 
	echo " ></td></tr>";

	echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][221]."</td><td>";
	showCalendarForm("form","date_fiscale",$db->result($result,0,"date_fiscale"),0);	
	echo "</td></tr>";
	echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][219]."</td><td>&nbsp;".$lang["choice"][1]."<input type=\"radio\" name=\"permit_helpdesk\" value=\"1\""; if($db->result($result,0,"permit_helpdesk") == 1) echo "checked=\"checked\""; echo " />&nbsp;".$lang["choice"][0]."<input type=\"radio\" name=\"permit_helpdesk\" value=\"0\""; if($db->result($result,0,"permit_helpdesk") == 0) echo "checked=\"checked\""; echo" /></td></tr>";

	echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][115]."</td><td><select name='cartridges_alarm'>";
	for ($i=0;$i<=100;$i++)
		echo "<option value='$i' ".($i==$db->result($result,0,"cartridges_alarm")?" selected ":"").">$i</option>";
	echo "</select></td></tr>";

	echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][306]." </td><td><select name=\"auto_update_check\">";
	$check=$db->result($result,0,"auto_update_check");
	echo "<option value=\"0\"";  if($check==0){ echo " selected";} echo ">".$lang["setup"][307]." </option>";
	echo "<option value=\"7\"";  if($check==7){ echo " selected";} echo ">".$lang["setup"][308]."</option>";
	echo "<option value=\"30\"";  if($check==30){ echo " selected";} echo ">".$lang["setup"][309]."</option>";
	echo "</select></td></tr>";

	echo "<tr class='tab_bg_2'><td align='center'> ".$lang["setup"][124]." </td><td>   &nbsp;".$lang["choice"][1]."  &nbsp;<input type=\"radio\" name=\"auto_add_users\" value=\"1\" "; if($db->result($result,0,"auto_add_users") == 1) echo "checked=\"checked\""; echo " /> &nbsp;".$lang["choice"][0]."  &nbsp;<input type=\"radio\" name=\"auto_add_users\" value=\"0\" "; if($db->result($result,0,"auto_add_users") == 0) echo "checked"; 
	echo " ></td></tr>";

	echo "<tr class='tab_bg_2'><td align='center'> ".$lang["setup"][132]." </td><td>   &nbsp;".$lang["choice"][1]."  &nbsp;<input type=\"radio\" name=\"post_only_followup\" value=\"1\" "; if($db->result($result,0,"post_only_followup") == 1) echo "checked=\"checked\""; echo " /> &nbsp;".$lang["choice"][0]."  &nbsp;<input type=\"radio\" name=\"post_only_followup\" value=\"0\" "; if($db->result($result,0,"post_only_followup") == 0) echo "checked"; 
	echo " ></td></tr>";

	echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][138]." </td><td><select name=\"debug\">";
	$check=$db->result($result,0,"debug");
	echo "<option value=\"0\"";  if($check==0){ echo " selected";} echo ">".$lang["setup"][135]." </option>";
	echo "<option value=\"1\"";  if($check==1){ echo " selected";} echo ">".$lang["setup"][136]."</option>";
	echo "<option value=\"2\"";  if($check==2){ echo " selected";} echo ">".$lang["setup"][137]."</option>";
	echo "</select></td></tr>";


	
		echo "</table>&nbsp;</div>";	
	echo "<p class=\"submit\"><input type=\"submit\" name=\"update_confgen\" class=\"submit\" value=\"".$lang["buttons"][2]."\" ></p>";

	
	echo "</form>";
}

function showFormConfigDisplay($target){
	
	global $db, $lang,$HTMLRel,$cfg_glpi;
	
	if (!haveRight("config","w")) return false;	
	
	$query = "select * from glpi_config where ID = 1";
	$result = $db->query($query);
	
	echo "<form name='form' action=\"$target\" method=\"post\">";
	echo "<div align='center'><table class='tab_cadre'>";
	echo "<tr><th colspan='2'>".$lang["setup"][100]."</th></tr>";
	
	echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][108]."</td><td> <input type=\"text\" name=\"num_of_events\" value=\"". $db->result($result,0,"num_of_events") ."\"></td></tr>";
	echo "<tr class='tab_bg_2'><td align='center'> ".$lang["setup"][110]." </td><td>   &nbsp;".$lang["choice"][1]."  &nbsp;<input type=\"radio\" name=\"jobs_at_login\" value=\"1\" "; if($db->result($result,0,"jobs_at_login") == 1) echo "checked=\"checked\""; echo " /> &nbsp;".$lang["choice"][0]."  &nbsp;<input type=\"radio\" name=\"jobs_at_login\" value=\"0\" "; if($db->result($result,0,"jobs_at_login") == 0) echo "checked"; 
	echo " ></td></tr>";

	echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][111]."</td><td> <input type=\"text\" name=\"list_limit\" value=\"". $db->result($result,0,"list_limit") ."\"></td></tr>";
	echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][112]."</td><td><input type=\"text\" name=\"cut\" value=\"". $db->result($result,0,"cut") ."\"></td></tr>";

	
	$plan_begin=split(":",$db->result($result,0,"planning_begin"));
	$plan_end=split(":",$db->result($result,0,"planning_end"));
	echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][223]."</td><td>";
	echo "<select name='planning_begin'>";
	for ($i=0;$i<=24;$i++) echo "<option value='$i'".($plan_begin[0]==$i?" selected ":"").">$i</option>";
	echo "</select>";
	
	echo "<select name='planning_end'>";
	for ($i=0;$i<=24;$i++) echo "<option value='$i' ".($plan_end[0]==$i?" selected ":"").">$i</option>";
	echo "</select>";
	
	echo "</td></tr>";
	
	echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][114]."</td><td>";
	echo "<table><tr>";
	echo "<td bgcolor='".$db->result($result,0,"priority_1")."'>1:<input type=\"text\" name=\"priority[1]\" size='7' value=\"".$db->result($result,0,"priority_1")."\"></td>";
	echo "<td bgcolor='".$db->result($result,0,"priority_2")."'>2:<input type=\"text\" name=\"priority[2]\" size='7' value=\"".$db->result($result,0,"priority_2")."\"></td>";
	echo "<td bgcolor='".$db->result($result,0,"priority_3")."'>3:<input type=\"text\" name=\"priority[3]\" size='7' value=\"".$db->result($result,0,"priority_3")."\"></td>";
	echo "<td bgcolor='".$db->result($result,0,"priority_4")."'>4:<input type=\"text\" name=\"priority[4]\" size='7' value=\"".$db->result($result,0,"priority_4")."\"></td>";
	echo "<td bgcolor='".$db->result($result,0,"priority_5")."'>5:<input type=\"text\" name=\"priority[5]\" size='7' value=\"".$db->result($result,0,"priority_5")."\"></td>";
	echo "</tr></table>";
	echo "</td></tr>";

	echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][128]." </td><td><select name=\"dateformat\">";
	$dateformat=$db->result($result,0,"dateformat");
	echo "<option value=\"0\"";  if($dateformat==0){ echo " selected";} echo ">YYYY-MM-DD</option>";
	echo "<option value=\"1\"";  if($dateformat==1){ echo " selected";} echo ">DD-MM-YYYY</option>";
	echo "</select></td></tr>";


	echo "<tr class='tab_bg_2'><td align='center'> ".$lang["setup"][117]." </td><td>   &nbsp;".$lang["choice"][1]."  &nbsp;<input type=\"radio\" name=\"public_faq\" value=\"1\" "; if($db->result($result,0,"public_faq") == 1) echo "checked=\"checked\""; echo " /> &nbsp;".$lang["choice"][0]."  &nbsp;<input type=\"radio\" name=\"public_faq\" value=\"0\" "; if($db->result($result,0,"public_faq") == 0) echo "checked";
	echo " ></td></tr>";

	$dp_limit=$db->result($result,0,"dropdown_limit");
	echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][131]."</td><td>";
	echo "<select name='dropdown_limit'>";
	for ($i=20;$i<=100;$i++) echo "<option value='$i'".($dp_limit==$i?" selected ":"").">$i</option>";
	echo "</select>";

	echo "</td></tr>";


	echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][120]." </td><td><select name=\"use_ajax\">";
	$use_ajax=$db->result($result,0,"use_ajax");
	echo "<option value=\"1\"";  if($use_ajax==1){ echo " selected";} echo ">".$lang["choice"][1]." </option>";
	echo "<option value=\"0\"";  if($use_ajax==0){ echo " selected";} echo ">".$lang["choice"][0]."</option>";
	echo "</select></td></tr>";

	echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][127]." </td><td><select name=\"ajax_autocompletion\">";
	$ajax_autocompletion=$db->result($result,0,"ajax_autocompletion");
	echo "<option value=\"1\"";  if($ajax_autocompletion==1){ echo " selected";} echo ">".$lang["choice"][1]." </option>";
	echo "<option value=\"0\"";  if($ajax_autocompletion==0){ echo " selected";} echo ">".$lang["choice"][0]."</option>";
	echo "</select></td></tr>";

	echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][121]."</td><td><input type=\"text\" size='1' name=\"ajax_wildcard\" value=\"". $db->result($result,0,"ajax_wildcard") ."\"></td></tr>";
	echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][122]."</td><td>";
	echo "<select name='dropdown_max'>";
	$dropdown_max=$db->result($result,0,"dropdown_max");
	for ($i=0;$i<=200;$i++) echo "<option value='$i'".($dropdown_max==$i?" selected ":"").">$i</option>";
	echo "</select>";
	echo "</td></tr>";

	echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][123]."</td><td>";
	echo "<select name='ajax_limit_count'>";
	$ajax_limit_count=$db->result($result,0,"ajax_limit_count");
	for ($i=0;$i<=200;$i++) echo "<option value='$i'".($ajax_limit_count==$i?" selected ":"").">$i</option>";
	echo "</select>";
	echo "</td></tr>";
	
	echo "<tr class='tab_bg_2'><td align='center'> ".$lang["setup"][118]." </td><td>";
	echo "<textarea cols='35' rows='4' name='text_login' >";
	echo $db->result($result,0,"text_login");
	echo "</textarea>";
	echo "</td></tr>";

	echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][129]." </td><td><select name=\"view_ID\">";
	$view_ID=$db->result($result,0,"view_ID");
	echo "<option value=\"1\"";  if($view_ID==1){ echo " selected";} echo ">".$lang["choice"][1]." </option>";
	echo "<option value=\"0\"";  if($view_ID==0){ echo " selected";} echo ">".$lang["choice"][0]."</option>";
	echo "</select></td></tr>";

	echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][130]." </td><td><select name=\"nextprev_item\">";
	$nextprev_item=$db->result($result,0,"nextprev_item");
	echo "<option value=\"ID\"";  if($nextprev_item=="ID"){ echo " selected";} echo ">".$lang["common"][2]." </option>";
	echo "<option value=\"name\"";  if($nextprev_item=="name"){ echo " selected";} echo ">".$lang["common"][16]."</option>";
	echo "</select></td></tr>";
		
	echo "</table>&nbsp;</div>";	
	echo "<p class=\"submit\"><input type=\"submit\" name=\"update_confdisplay\" class=\"submit\" value=\"".$lang["buttons"][2]."\" ></p>";

	
	echo "</form>";
}




function titleExtSources(){
// Un titre pour la gestion des sources externes
		
		global  $lang,$HTMLRel;
                echo "<div align='center'><table border='0'><tr><td>";
                echo "<img src=\"".$HTMLRel."pics/authentification.png\" alt='' title=''></td><td><span class='icon_sous_nav'>".$lang["setup"][150]."</span>";
		 echo "</td></tr></table>&nbsp;</div>";

}





function showMailServerConfig($value){
global $lang;

	if (!haveRight("config","w")) return false;	

if (ereg(":",$value)){
$addr=ereg_replace("{","",preg_replace("/:.*/","",$value));
$port=preg_replace("/.*:/","",preg_replace("/\/.*/","",$value));
}
else {
	if (ereg("/",$value))
	$addr=ereg_replace("{","",preg_replace("/\/.*/","",$value));
	else $addr=ereg_replace("{","",preg_replace("/}.*/","",$value));
	$port="";
}
$mailbox=preg_replace("/.*}/","",$value);

echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][163]."</td><td><input size='30' type=\"text\" name=\"mail_server\" value=\"". $addr."\" ></td></tr>";	
echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][168]."</td><td>";
echo "<select name='server_type'>";
echo "<option value=''>&nbsp;</option>";
echo "<option value='/imap' ".(ereg("/imap",$value)?" selected ":"").">IMAP</option>";
echo "<option value='/pop' ".(ereg("/pop",$value)?" selected ":"").">POP</option>";
echo "</select>";
echo "<select name='server_ssl'>";
echo "<option value=''>&nbsp;</option>";
echo "<option value='/ssl' ".(ereg("/ssl",$value)?" selected ":"").">SSL</option>";
echo "</select>";
echo "<select name='server_cert'>";
echo "<option value=''>&nbsp;</option>";
echo "<option value='/novalidate-cert' ".(ereg("/novalidate-cert",$value)?" selected ":"").">NO-VALIDATE-CERT</option>";
echo "<option value='/validate-cert' ".(ereg("/validate-cert",$value)?" selected ":"").">VALIDATE-CERT</option>";
echo "</select>";
echo "<select name='server_tls'>";
echo "<option value=''>&nbsp;</option>";
echo "<option value='/tls' ".(ereg("/tls",$value)?" selected ":"").">TLS</option>";
echo "<option value='/notls' ".(ereg("/notls",$value)?" selected ":"").">NO-TLS</option>";
echo "</select>";

echo "</td></tr>";	

echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][169]."</td><td><input size='30' type=\"text\" name=\"server_mailbox\" value=\"". $mailbox."\" ></td></tr>";	
echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][171]."</td><td><input size='10' type=\"text\" name=\"server_port\" value=\"". $port."\" ></td></tr>";	
if (empty($value)) $value="&nbsp;";
echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][170]."</td><td><b>$value</b></td></tr>";	

}	

function constructIMAPAuthServer($input){

$out="";
if (isset($input['mail_server'])&&!empty($input['mail_server'])) $out.="{".$input['mail_server'];
else return $out;
if (isset($input['server_port'])&&!empty($input['server_port'])) $out.=":".$input['server_port'];
if (isset($input['server_type'])) $out.=$input['server_type'];
if (isset($input['server_ssl'])) $out.=$input['server_ssl'];
if (isset($input['server_cert'])) $out.=$input['server_cert'];
if (isset($input['server_tls'])) $out.=$input['server_tls'];

$out.="}";
if (isset($input['server_mailbox'])) $out.=$input['server_mailbox'];

return $out;
	
}

function showFormExtSources($target) {

	global  $db,$lang,$HTMLRel;

	if (!haveRight("config","w")) return false;	
	
	$query = "select * from glpi_config where ID = 1";
	$result = $db->query($query);
	
	echo "<form action=\"$target\" method=\"post\">";
	
	if(function_exists('imap_open')) {

		echo "<div align='center'>";
		echo "<p >".$lang["setup"][160]."</p>";
//		echo "<p>".$lang["setup"][161]."</p>";
		echo "<table class='tab_cadre_fixe'>";
		echo "<tr><th colspan='2'>".$lang["setup"][162]."</th></tr>";
		echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][164]."</td><td><input size='30' type=\"text\" name=\"imap_host\" value=\"". $db->result($result,0,"imap_host") ."\" ></td></tr>";

		showMailServerConfig($db->result($result,0,"imap_auth_server"));
		echo "</table>&nbsp;</div>";
	}
	else {
		echo "<input type=\"hidden\" name=\"IMAP_Test\" value=\"1\" >";
		
		echo "<div align='center'>&nbsp;<table class='tab_cadre_fixe'>";
		echo "<tr><th colspan='2'>".$lang["setup"][162]."</th></tr>";
		echo "<tr class='tab_bg_2'><td align='center'><p class='red'>".$lang["setup"][165]."</p><p>".$lang["setup"][166]."</p></td></tr></table></div>";
	}
	if(extension_loaded('ldap'))
	{
		echo "<div align='center'><p > ".$lang["setup"][151]."</p>";

		echo "<table class='tab_cadre_fixe'>";
		echo "<tr><th colspan='4'>".$lang["setup"][152]."</th></tr>";

		echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][153]."</td><td><input type=\"text\" name=\"ldap_host\" value=\"". $db->result($result,0,"ldap_host") ."\"></td>";
		echo "<td align='center'>".$lang["setup"][172]."</td><td><input type=\"text\" name=\"ldap_port\" value=\"". $db->result($result,0,"ldap_port") ."\"></td></tr>";

		echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][154]."</td><td><input type=\"text\" name=\"ldap_basedn\" value=\"". $db->result($result,0,"ldap_basedn") ."\" ></td>";
		echo "<td align='center'>".$lang["setup"][155]."</td><td><input type=\"text\" name=\"ldap_rootdn\" value=\"". $db->result($result,0,"ldap_rootdn") ."\" ></td></tr>";

		echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][156]."</td><td><input type=\"password\" name=\"ldap_pass\" value=\"". $db->result($result,0,"ldap_pass") ."\" ></td>";
		echo "<td align='center'>".$lang["setup"][159]."</td><td><input type=\"text\" name=\"ldap_condition\" value=\"". $db->result($result,0,"ldap_condition") ."\" ></td></tr>";

		echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][228]."</td><td><input type=\"text\" name=\"ldap_login\" value=\"". $db->result($result,0,"ldap_login") ."\" ></td>";
		echo "<td align='center'>".$lang["setup"][180]."</td><td>";
		if (function_exists("ldap_start_tls")){
			$ldap_use_tls=$db->result($result,0,"ldap_use_tls");
			echo "<select name='ldap_use_tls'>\n";
			echo "<option value='0' ".(!$ldap_use_tls?" selected ":"").">".$lang["choice"][0]."</option>\n";
			echo "<option value='1' ".($ldap_use_tls?" selected ":"").">".$lang["choice"][1]."</option>\n";
			echo "</select>\n";	
		} else {
			echo "<input type='hidden' name='ldap_use_tls' value='0'>";
			echo $lang["setup"][181];
			
		}
		echo "</td></tr>";
		
		echo "<tr class='tab_bg_1'><td align='center' colspan='4'>".$lang["setup"][167]."</td></tr>";

		echo "<tr class='tab_bg_2'><td align='center'>name</td><td><input type=\"text\" name=\"ldap_field_name\" value=\"". $db->result($result,0,"ldap_field_name") ."\" ></td>";
		echo "<td align='center'>email</td><td><input type=\"text\" name=\"ldap_field_email\" value=\"". $db->result($result,0,"ldap_field_email") ."\" ></td></tr>";

		echo "<tr class='tab_bg_2'><td align='center'>location</td><td><input type=\"text\" name=\"ldap_field_location\" value=\"". $db->result($result,0,"ldap_field_location") ."\" ></td>";
		echo "<td align='center'>phone</td><td><input type=\"text\" name=\"ldap_field_phone\" value=\"". $db->result($result,0,"ldap_field_phone") ."\" ></td></tr>";

		echo "<tr class='tab_bg_2'><td align='center'>realname</td><td><input type=\"text\" name=\"ldap_field_realname\" value=\"". $db->result($result,0,"ldap_field_realname") ."\" ></td>";
		echo "<td align='center'>&nbsp;</td><td>&nbsp;</td></tr>";
		
		echo "</table>&nbsp;</div>";
	}
	else {
		echo "<input type=\"hidden\" name=\"LDAP_Test\" value=\"1\" >";
		echo "<div align='center'><table class='tab_cadre_fixe'>";
		echo "<tr><th colspan='2'>".$lang["setup"][152]."</th></tr>";
		echo "<tr class='tab_bg_2'><td align='center'><p class='red'>".$lang["setup"][157]."</p><p>".$lang["setup"][158]."</p></td></th></table></div>";
	}

	if(function_exists('curl_init')&&(version_compare(PHP_VERSION,'5','>=')||(function_exists("domxml_open_mem")&&function_exists("utf8_decode"))))
	{
		echo "<div align='center'><p > ".$lang["setup"][173]."</p>";

		echo "<table class='tab_cadre'>";
		echo "<tr><th colspan='2'>".$lang["setup"][177]."</th></tr>";
		echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][174]."</td><td><input type=\"text\" name=\"cas_host\" value=\"". $db->result($result,0,"cas_host") ."\"></td></tr>";
		echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][175]."</td><td><input type=\"text\" name=\"cas_port\" value=\"". $db->result($result,0,"cas_port") ."\"></td></tr>";
		echo "<tr class='tab_bg_2'><td align='center'>".$lang["setup"][176]."</td><td><input type=\"text\" name=\"cas_uri\" value=\"". $db->result($result,0,"cas_uri") ."\" ></td></tr>";
		
		echo "</table>&nbsp;</div>";
	}
	else {
		echo "<input type=\"hidden\" name=\"CAS_Test\" value=\"1\" >";
		echo "<div align='center'><table class='tab_cadre_fixe'>";
		echo "<tr><th colspan='2'>".$lang["setup"][177]."</th></tr>";
		echo "<tr class='tab_bg_2'><td align='center'><p class='red'>".$lang["setup"][178]."</p><p>".$lang["setup"][179]."</p></td></th></table></div>";
	}
	
	echo "<p class=\"submit\"><input type=\"submit\" name=\"update_ext\" class=\"submit\" value=\"".$lang["buttons"][2]."\" ></p>";
	echo "</form>";
}


function titleMailing(){
// Un titre pour la gestion du suivi par mail
		
		global  $lang,$HTMLRel;
                echo "<div align='center'><table border='0'><tr><td>";
                echo "<img src=\"".$HTMLRel."pics/mail.png\" alt='' title=''></td><td><span class='icon_sous_nav'>".$lang["setup"][200]."</span>";
		 echo "</td></tr></table></div>";
}


function showFormMailing($target) {
	
	global $db,$lang;

	if (!haveRight("config","w")) return false;	

		$query = "select * from glpi_config where ID = 1";
		$result = $db->query($query);
		echo "<form action=\"$target\" method=\"post\">";
		
		
		echo "<div align='center'><table class='tab_cadre_fixe'><tr><th colspan='2'>".$lang["setup"][201]."</th></tr>";
		
		//	if (function_exists('mail')) {
		echo "<tr class='tab_bg_2'><td >".$lang["setup"][202]."</td><td align='center'>&nbsp; ".$lang["choice"][1]."  &nbsp;<input type=\"radio\" name=\"mailing\" value=\"1\" "; if($db->result($result,0,"mailing") == 1) echo "checked"; echo " > &nbsp;".$lang["choice"][0]."  &nbsp;<input type=\"radio\" name=\"mailing\" value=\"0\" "; if($db->result($result,0,"mailing") == 0) echo "checked"; echo " ></td></tr>";
		
		echo "<tr class='tab_bg_2'><td >".$lang["setup"][203]."</td><td> <input type=\"text\" name=\"admin_email\" size='40' value=\"".$db->result($result,0,"admin_email")."\"> </td></tr>";
		
		echo "<tr class='tab_bg_2'><td >".$lang["setup"][204]."</td><td><input type=\"text\" name=\"mailing_signature\" size='40' value=\"".$db->result($result,0,"mailing_signature")."\" ></td></tr>";
		
		echo "<tr class='tab_bg_2'><td >".$lang["setup"][226]."</td><td align='center'>&nbsp; ".$lang["choice"][1]."  &nbsp;<input type=\"radio\" name=\"url_in_mail\" value=\"1\" "; if($db->result($result,0,"url_in_mail") == 1) echo "checked"; echo " > &nbsp;".$lang["choice"][0]."  &nbsp;<input type=\"radio\" name=\"url_in_mail\" value=\"0\" "; if($db->result($result,0,"url_in_mail") == 0) echo "checked"; echo " ></td></tr>";
		
		echo "<tr class='tab_bg_2'><td >".$lang["setup"][227]."</td><td> <input type=\"text\" name=\"url_base\" size='40' value=\"".$db->result($result,0,"url_base")."\"> </td></tr>";

		if (!function_exists('mail')) {
		echo "<tr class='tab_bg_2'><td align='center' colspan='2'><span class='red'>".$lang["setup"][217]." : </span><span>".$lang["setup"][218]."</span></td></tr>";
		}

		echo "<tr class='tab_bg_2'><td >".$lang["setup"][231]."</td><td align='center'>&nbsp; ";
		
		if (!function_exists('mail')) { // if mail php disabled we forced SMTP usage 
			echo $lang["choice"][1]."  &nbsp;<input type=\"radio\" name=\"smtp_mode\" value=\"1\" checked >";
		}else{
			echo $lang["choice"][1]."  &nbsp;<input type=\"radio\" name=\"smtp_mode\" value=\"1\" "; if($db->result($result,0,"smtp_mode") == 1) echo "checked"; echo " >";
		
			echo "&nbsp;".$lang["choice"][0]."  &nbsp;<input type=\"radio\" name=\"smtp_mode\" value=\"0\" "; if($db->result($result,0,"smtp_mode") == 0) echo "checked"; echo " ></td></tr>";
		}
			
		echo "<tr class='tab_bg_2'><td >".$lang["setup"][232]."</td><td> <input type=\"text\" name=\"smtp_host\" size='40' value=\"".$db->result($result,0,"smtp_host")."\"> </td></tr>";

		echo "<tr class='tab_bg_2'><td >".$lang["setup"][233]."</td><td> <input type=\"text\" name=\"smtp_port\" size='40' value=\"".$db->result($result,0,"smtp_port")."\"> </td></tr>";

		echo "<tr class='tab_bg_2'><td >".$lang["setup"][234]."</td><td> <input type=\"text\" name=\"smtp_username\" size='40' value=\"".$db->result($result,0,"smtp_username")."\"> </td></tr>";

		echo "<tr class='tab_bg_2'><td >".$lang["setup"][235]."</td><td> <input type=\"text\" name=\"smtp_password\" size='40' value=\"".$db->result($result,0,"smtp_password")."\"> </td></tr>";

		echo "</table>";
		
		echo "<p><b>".$lang["setup"][205]."</b></p>";
		
		// ADMIN
		echo "<table class='tab_cadre_fixe'><tr><th colspan='6'>".$lang["setup"][206]."</th></tr>";
		echo "<tr class='tab_bg_2'><td>".$lang["setup"][211]."</td><td> ".$lang["choice"][1]." <input type=\"radio\" name=\"mailing_new_admin\" value=\"1\" "; if($db->result($result,0,"mailing_new_admin") == 1) echo "checked"; echo " ></td><td> ".$lang["choice"][0]." <input type=\"radio\" name=\"mailing_new_admin\" value=\"0\" "; if($db->result($result,0,"mailing_new_admin") == 0) echo "checked"; echo " ></td>";
		
		echo "<td >".$lang["setup"][230]."</td><td> ".$lang["choice"][1]." <input type=\"radio\" name=\"mailing_update_admin\" value=\"1\" "; if($db->result($result,0,"mailing_update_admin") == 1) echo "checked"; echo "></td><td> ".$lang["choice"][0]." <input type=\"radio\" name=\"mailing_update_admin\" value=\"0\" "; if($db->result($result,0,"mailing_update_admin") == 0) echo "checked"; echo " ></td></tr>";
		
		echo "<tr class='tab_bg_2'><td >".$lang["setup"][212]."</td><td> ".$lang["choice"][1]." <input type=\"radio\" name=\"mailing_followup_admin\" value=\"1\" "; if($db->result($result,0,"mailing_followup_admin") == 1) echo "checked"; echo "></td><td> ".$lang["choice"][0]." <input type=\"radio\" name=\"mailing_followup_admin\" value=\"0\" "; if($db->result($result,0,"mailing_followup_admin") == 0) echo "checked"; echo " ></td>";
		
		echo "<td>".$lang["setup"][213]."</td><td> ".$lang["choice"][1]." <input type=\"radio\" name=\"mailing_finish_admin\" value=\"1\" "; if($db->result($result,0,"mailing_finish_admin") == 1) echo "checked"; echo " ></td><td> ".$lang["choice"][0]." <input type=\"radio\" name=\"mailing_finish_admin\" value=\"0\" "; if($db->result($result,0,"mailing_finish_admin") == 0) echo "checked"; echo " ></td></tr>";
		
		// ALL ADMIN
		echo "<tr class='tab_bg_2'><th colspan='6'>".$lang["setup"][207]."</th></tr>";
		
		echo "<tr class='tab_bg_2'><td>".$lang["setup"][211]."</td><td> ".$lang["choice"][1]." <input type=\"radio\" name=\"mailing_new_all_admin\" value=\"1\" "; if($db->result($result,0,"mailing_new_all_admin") == 1) echo "checked"; echo  " ></td><td> ".$lang["choice"][0]." <input type=\"radio\" name=\"mailing_new_all_admin\" value=\"0\" "; if($db->result($result,0,"mailing_new_all_admin") == 0) echo "checked"; echo  " ></td>";
		
		echo "<td>".$lang["setup"][230]."</td><td> ".$lang["choice"][1]." <input type=\"radio\" name=\"mailing_update_all_admin\" value=\"1\"  "; if($db->result($result,0,"mailing_update_all_admin") == 1) echo "checked"; echo  " ></td><td> ".$lang["choice"][0]." <input type=\"radio\" name=\"mailing_update_all_admin\" value=\"0\"  "; if($db->result($result,0,"mailing_update_all_admin") == 0) echo "checked"; echo  " ></td></tr>";

		echo "<tr class='tab_bg_2'><td>".$lang["setup"][212]."</td><td> ".$lang["choice"][1]." <input type=\"radio\" name=\"mailing_followup_all_admin\" value=\"1\"  "; if($db->result($result,0,"mailing_followup_all_admin") == 1) echo "checked"; echo  " ></td><td> ".$lang["choice"][0]." <input type=\"radio\" name=\"mailing_followup_all_admin\" value=\"0\"  "; if($db->result($result,0,"mailing_followup_all_admin") == 0) echo "checked"; echo  " ></td>";
		
		echo "<td>".$lang["setup"][213]."</td><td> ".$lang["choice"][1]." <input type=\"radio\" name=\"mailing_finish_all_admin\" value=\"1\"  "; if($db->result($result,0,"mailing_finish_all_admin") == 1) echo "checked"; echo  " ></td><td> ".$lang["choice"][0]." <input type=\"radio\" name=\"mailing_finish_all_admin\" value=\"0\"  "; if($db->result($result,0,"mailing_finish_all_admin") == 0) echo "checked"; echo  " ></td></tr>";
		
		// ALL NORMAL
		echo "<tr><th colspan='6'>".$lang["setup"][208]."</th></tr>";
		echo "<tr class='tab_bg_2'><td>".$lang["setup"][211]."</td><td> ".$lang["choice"][1]." <input type=\"radio\" name=\"mailing_new_all_normal\" value=\"1\"  "; if($db->result($result,0,"mailing_new_all_normal") == 1) echo "checked"; echo  " ></td><td> ".$lang["choice"][0]." <input type=\"radio\" name=\"mailing_new_all_normal\" value=\"0\"  "; if($db->result($result,0,"mailing_new_all_normal") == 0) echo "checked"; echo  " ></td>";

		echo "<td>".$lang["setup"][230]."</td><td> ".$lang["choice"][1]." <input type=\"radio\" name=\"mailing_update_all_normal\" value=\"1\" "; if($db->result($result,0,"mailing_update_all_normal") == 1) echo "checked"; echo  " ></td><td> ".$lang["choice"][0]." <input type=\"radio\" name=\"mailing_update_all_normal\" value=\"0\" "; if($db->result($result,0,"mailing_update_all_normal") == 0) echo "checked"; echo  " ></td></tr>";

		
		echo "<tr class='tab_bg_2'><td>".$lang["setup"][212]."</td><td> ".$lang["choice"][1]." <input type=\"radio\" name=\"mailing_followup_all_normal\" value=\"1\" "; if($db->result($result,0,"mailing_followup_all_normal") == 1) echo "checked"; echo  " ></td><td> ".$lang["choice"][0]." <input type=\"radio\" name=\"mailing_followup_all_normal\" value=\"0\" "; if($db->result($result,0,"mailing_followup_all_normal") == 0) echo "checked"; echo  " ></td>";
		
		echo "<td>".$lang["setup"][213]."</td><td> ".$lang["choice"][1]." <input type=\"radio\" name=\"mailing_finish_all_normal\" value=\"1\" "; if($db->result($result,0,"mailing_finish_all_normal") == 1) echo "checked"; echo  " ></td><td> ".$lang["choice"][0]." <input type=\"radio\" name=\"mailing_finish_all_normal\" value=\"0\" "; if($db->result($result,0,"mailing_finish_all_normal") == 0) echo "checked"; echo  " ></td></tr>";
		
		// ASSIGN
		echo "<tr><th colspan='6'>".$lang["setup"][209]."</th></tr>";
		echo "<tr class='tab_bg_2'><td>".$lang["setup"][211]."</td><td> ".$lang["choice"][1]." <input type=\"radio\" name=\"mailing_new_attrib\" value=\"1\" "; if($db->result($result,0,"mailing_new_attrib") == 1) echo "checked"; echo  " ></td><td> ".$lang["choice"][0]." <input type=\"radio\" name=\"mailing_new_attrib\" value=\"0\" "; if($db->result($result,0,"mailing_new_attrib") == 0) echo "checked"; echo  " ></td>";
		
		echo "<td>".$lang["setup"][230]."</td><td> ".$lang["choice"][1]." <input type=\"radio\" name=\"mailing_update_attrib\" value=\"1\" "; if($db->result($result,0,"mailing_update_attrib") == 1) echo "checked"; echo  " ></td><td> ".$lang["choice"][0]." <input type=\"radio\" name=\"mailing_update_attrib\" value=\"0\" "; if($db->result($result,0,"mailing_update_attrib") == 0) echo "checked"; echo  " ></td></tr>";


		echo "<tr class='tab_bg_2'><td>".$lang["setup"][212]."</td><td> ".$lang["choice"][1]." <input type=\"radio\" name=\"mailing_followup_attrib\" value=\"1\" "; if($db->result($result,0,"mailing_followup_attrib") == 1) echo "checked"; echo  " ></td><td> ".$lang["choice"][0]." <input type=\"radio\" name=\"mailing_followup_attrib\" value=\"0\" "; if($db->result($result,0,"mailing_followup_attrib") == 0) echo "checked"; echo  " ></td>";

		echo "<td>".$lang["setup"][213]."</td><td> ".$lang["choice"][1]." <input type=\"radio\" name=\"mailing_finish_attrib\" value=\"1\" "; if($db->result($result,0,"mailing_finish_attrib") == 1) echo "checked"; echo  " ></td><td> ".$lang["choice"][0]." <input type=\"radio\" name=\"mailing_finish_attrib\" value=\"0\" "; if($db->result($result,0,"mailing_finish_attrib") == 0) echo "checked"; echo  " ></td></tr>";

		echo "<tr class='tab_bg_2'><td>".$lang["setup"][229]."</td><td> ".$lang["choice"][1]." <input type=\"radio\" name=\"mailing_attrib_attrib\" value=\"1\" "; if($db->result($result,0,"mailing_attrib_attrib") == 1) echo "checked"; echo  " ></td><td> ".$lang["choice"][0]." <input type=\"radio\" name=\"mailing_attrib_attrib\" value=\"0\"  "; if($db->result($result,0,"mailing_attrib_attrib") == 0) echo "checked"; echo  " ></td>";
		echo "<td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>";

		// USER
		echo "<tr><th colspan='6'>".$lang["setup"][210]."</th></tr>";
		
		echo "<tr class='tab_bg_2'><td>".$lang["setup"][214]."</td><td> ".$lang["choice"][1]." <input type=\"radio\" name=\"mailing_new_user\" value=\"1\" "; if($db->result($result,0,"mailing_new_user") == 1) echo "checked"; echo  " ></td><td> ".$lang["choice"][0]." <input type=\"radio\" name=\"mailing_new_user\" value=\"0\" "; if($db->result($result,0,"mailing_new_user") == 0) echo "checked"; echo  " ></td>";

		echo "<td>".$lang["setup"][230]."</td><td> ".$lang["choice"][1]." <input type=\"radio\" name=\"mailing_update_user\" value=\"1\" "; if($db->result($result,0,"mailing_update_user") == 1) echo "checked"; echo  " ></td><td> ".$lang["choice"][0]." <input type=\"radio\" name=\"mailing_update_user\" value=\"0\" "; if($db->result($result,0,"mailing_update_user") == 0) echo "checked"; echo  " ></td></tr>";
		
		echo "<tr class='tab_bg_2'><td>".$lang["setup"][215]."</td><td> ".$lang["choice"][1]." <input type=\"radio\" name=\"mailing_followup_user\" value=\"1\" "; if($db->result($result,0,"mailing_followup_user") == 1) echo "checked"; echo  " ></td><td> ".$lang["choice"][0]." <input type=\"radio\" name=\"mailing_followup_user\" value=\"0\" "; if($db->result($result,0,"mailing_followup_user") == 0) echo "checked"; echo  " ></td>";
		
		echo "<td>".$lang["setup"][216]."</td><td> ".$lang["choice"][1]." <input type=\"radio\" name=\"mailing_finish_user\" value=\"1\"  "; if($db->result($result,0,"mailing_finish_user") == 1) echo "checked"; echo  " ></td><td> ".$lang["choice"][0]." <input type=\"radio\" name=\"mailing_finish_user\" value=\"0\" "; if($db->result($result,0,"mailing_finish_user") == 0) echo "checked"; echo  " ></td></tr>";
		echo "</table>";

		echo "<p><b>".$lang["setup"][224]."</b></p>";
		
		echo "<table class='tab_cadre_fixe'><tr><th colspan='2'>".$lang["setup"][225]."<th></tr>";

		echo "<tr class='tab_bg_2'><td>".$lang["setup"][206]."</td><td> ".$lang["choice"][1]." <input type=\"radio\" name=\"mailing_resa_admin\" value=\"1\" "; if($db->result($result,0,"mailing_resa_admin") == 1) echo "checked"; echo  " ></td><td> ".$lang["choice"][0]." <input type=\"radio\" name=\"mailing_resa_admin\" value=\"0\" "; if($db->result($result,0,"mailing_resa_admin") == 0) echo "checked"; echo  " ></td></tr>";
		echo "<tr class='tab_bg_2'><td>".$lang["setup"][207]."</td><td> ".$lang["choice"][1]." <input type=\"radio\" name=\"mailing_resa_all_admin\" value=\"1\" "; if($db->result($result,0,"mailing_resa_all_admin") == 1) echo "checked"; echo  " ></td><td> ".$lang["choice"][0]." <input type=\"radio\" name=\"mailing_resa_all_admin\" value=\"0\" "; if($db->result($result,0,"mailing_resa_all_admin") == 0) echo "checked"; echo  " ></td></tr>";
		echo "<tr class='tab_bg_2'><td>".$lang["setup"][210]."</td><td> ".$lang["choice"][1]." <input type=\"radio\" name=\"mailing_resa_user\" value=\"1\" "; if($db->result($result,0,"mailing_resa_user") == 1) echo "checked"; echo  " ></td><td> ".$lang["choice"][0]." <input type=\"radio\" name=\"mailing_resa_user\" value=\"0\" "; if($db->result($result,0,"mailing_resa_user") == 0) echo "checked"; echo  " ></td></tr>";

		echo "</table>";


		echo "</div>";
		echo "<p class=\"submit\"><input type=\"submit\" name=\"update_mailing\" class=\"submit\" value=\"".$lang["buttons"][2]."\" ></p>";
		//}
		//else {
		
			
		//echo "<tr class='tab_bg_2'><td align='center'><p class='red'>".$lang["setup"][217]."</p><p>".$lang["setup"][218]."</p></td></tr></table></div>";
		
		//}
		
		echo "</form>";

}

function updateConfigGen($event_loglevel,$expire_events, $permit_helpdesk,$default_language,$date_fiscale,$cartridges_alarm,$auto_assign,$auto_update_check,$auto_add_users,$post_only_followup,$ocs_mode,$debug) {
	
	global $db;	
		$query = "update glpi_config set ";
		$query.= "event_loglevel = '". $event_loglevel ."', default_language = '". $default_language ."',";
		$query.= "expire_events = '". $expire_events ."', permit_helpdesk='". $permit_helpdesk ."',";
		$query.= " date_fiscale = '". $date_fiscale ."', cartridges_alarm='".$cartridges_alarm."', ";
		$query.= " auto_assign = '". $auto_assign ."', auto_update_check = '".$auto_update_check."', ";
		$query.= " auto_add_users = '".$auto_add_users."', post_only_followup = '".$post_only_followup."', ";
		$query.= " ocs_mode = '".$ocs_mode."', ";
		$query.= " debug = '".$debug."' where ID = '1' ";
		
		$db->query($query);
	
}

function updateConfigDisplay($num_of_events,$jobs_at_login,$list_limit,$cut,$priority,$planning_begin,$planning_end,$public_faq,$text_login,$use_ajax,$ajax_wildcard,$ajax_limit_count,$dropdown_max,$ajax_autocompletion,$dateformat,$view_id,$nextprev_item,$dp_limit) {
	
	global $db;
	
		$query = "update glpi_config SET ";
		$query.= " num_of_events = '". $num_of_events ."',";
		$query.= " jobs_at_login = '". $jobs_at_login ."' , list_limit = '". $list_limit ."' , cut = '". $cut ."', ";
		$query.= " priority_1 = '". $priority[1] ."', priority_2 = '". $priority[2] ."', priority_3 = '". $priority[3] ."', priority_4 = '". $priority[4] ."', priority_5 = '". $priority[5] ."', ";
		$query.= " planning_begin = '". $planning_begin .":00:00', planning_end='".$planning_end.":00:00', ";
		$query.= " public_faq = '". $public_faq ."', text_login = '". $text_login ."', ";
		$query.= " use_ajax = '". $use_ajax ."', ajax_wildcard = '". $ajax_wildcard ."', ";
		$query.= " ajax_limit_count = '". $ajax_limit_count ."', dropdown_max = '". $dropdown_max ."', ";		
		$query.= " ajax_autocompletion = '". $ajax_autocompletion ."',dateformat='".$dateformat."', ";				
		$query.= " view_ID = '". $view_id ."',nextprev_item='".$nextprev_item."', dropdown_limit='".$dp_limit."' ";				
		$query.= " where ID = '1' ";
		$db->query($query);
	
}

function updateLDAP($ldap_host,$ldap_basedn,$ldap_rootdn,$ldap_pass,$ldap_condition,$ldap_login,$field_name,$field_email,$field_location,$field_phone,$field_realname,$ldap_port,$ldap_use_tls) {
	
	global $db;
	//TODO : test the remote LDAP connection
		$query = "update glpi_config set ldap_host = '". $ldap_host ."', ";
		$query.= "ldap_basedn = '". $ldap_basedn ."', ldap_rootdn = '". $ldap_rootdn ."', ";
		$query .= "ldap_pass = '". $ldap_pass ."', ldap_condition = '". $ldap_condition ."', ldap_login = '". $ldap_login ."',";
		$query .= "ldap_field_name = '". $field_name ."', ldap_field_email = '". $field_email ."', ";
		$query .= "ldap_field_location = '". $field_location ."', ldap_field_phone = '". $field_phone ."', ";
		$query .= "ldap_field_realname = '". $field_realname ."', ldap_port = '". $ldap_port ."', ldap_use_tls = '". $ldap_use_tls ."' ";
		$query.= " where ID = '1' ";
		$db->query($query);
}
function updateIMAP($imap_auth_server,$imap_host) {
	global $db;
	//TODO : test the remote IMAP connection
		$query = "update glpi_config set imap_auth_server = '". $imap_auth_server ."', ";
		$query.= "imap_host = '". $imap_host ."' where ID = '1'";
		$db->query($query);
}

function updateCAS($cas_host,$cas_port,$cas_uri) {
	global $db;
	//TODO : test the remote IMAP connection
		$query = "update glpi_config set cas_host = '". $cas_host ."', ";
		$query.= "cas_uri = '". $cas_uri."',";
		$query.= "cas_port = '". $cas_port ."' where ID = '1'";
		$db->query($query);
}

function updateMailing($mailing,$admin_email, $mailing_signature,$mailing_new_admin,$mailing_followup_admin,$mailing_finish_admin,$mailing_new_all_admin,$mailing_followup_all_admin,$mailing_finish_all_admin,$mailing_new_all_normal,$mailing_followup_all_normal,$mailing_finish_all_normal,$mailing_followup_attrib,$mailing_finish_attrib,$mailing_new_user,$mailing_followup_user,$mailing_finish_user,$mailing_new_attrib,$mailing_resa_admin,$mailing_resa_all_admin,$mailing_resa_user,$url,$url_in_mail,$mailing_attrib_attrib,$mailing_update_admin,$mailing_update_all_admin,$mailing_update_all_normal,$mailing_update_attrib,$mailing_update_user,$smtp_mode,$smtp_host,$smtp_port,$smtp_username,$smtp_password) {

	global $db;
	$query = "update glpi_config set mailing = '". $mailing ."', ";
	$query .= "admin_email = '". $admin_email ."', ";
	$query .= "mailing_signature = '". $mailing_signature ."', ";
	$query .= "mailing_new_admin = '". $mailing_new_admin ."', ";
	$query .= "mailing_update_admin = '". $mailing_update_admin ."', ";
	$query .= "mailing_followup_admin = '". $mailing_followup_admin ."', ";
	$query .= "mailing_finish_admin = '". $mailing_finish_admin ."', ";
	$query .= "mailing_new_all_admin = '". $mailing_new_all_admin ."', ";
	$query .= "mailing_update_all_admin = '". $mailing_update_all_admin ."', ";
	$query .= "mailing_followup_all_admin = '". $mailing_followup_all_admin ."', ";
	$query .= "mailing_finish_all_admin = '". $mailing_finish_all_admin ."', ";
	$query .= "mailing_new_all_normal = '". $mailing_new_all_normal ."', ";
	$query .= "mailing_update_all_normal = '". $mailing_update_all_normal ."', ";
	$query .= "mailing_followup_all_normal = '". $mailing_followup_all_normal ."', ";
	$query .= "mailing_finish_all_normal = '". $mailing_finish_all_normal ."', ";
	$query .= "mailing_new_attrib = '". $mailing_new_attrib ."', ";
	$query .= "mailing_update_attrib = '". $mailing_update_attrib ."', ";
	$query .= "mailing_followup_attrib = '". $mailing_followup_attrib ."', ";
	$query .= "mailing_finish_attrib = '". $mailing_finish_attrib ."', ";
	$query .= "mailing_new_user = '". $mailing_new_user ."', ";
	$query .= "mailing_update_user = '". $mailing_update_user ."', ";
	$query .= "mailing_followup_user = '". $mailing_followup_user ."', ";
	$query .= "mailing_finish_user = '". $mailing_finish_user ."', ";
	$query .= "mailing_attrib_attrib = '". $mailing_attrib_attrib ."', ";
	$query .= "mailing_resa_admin = '". $mailing_resa_admin ."', ";
	$query .= "mailing_resa_all_admin = '". $mailing_resa_all_admin ."', ";
	$query .= "mailing_resa_user = '". $mailing_resa_user ."', ";
	$query .= "url_base = '". $url ."', ";
	$query .= "url_in_mail = '". $url_in_mail ."', ";
	$query .= "smtp_mode = '". $smtp_mode ."', ";
	$query .= "smtp_host = '". $smtp_host ."', ";
	$query .= "smtp_port = '". $smtp_port ."', ";
	$query .= "smtp_username = '". $smtp_username  ."', ";
	$query .= "smtp_password = '". $smtp_password ."' ";
	$query .= "where ID = 1";
	
	if($db->query($query)) return true;
	else return false;
}

function checkNewVersionAvailable($auto=1){
	global $db,$lang,$cfg_glpi;

	if (!haveRight("update","1")) return false;	

	$do_check=1;
	
	if ($auto&&$cfg_glpi["auto_update_check"]==0) return;
	else {
		$last_check=split("-",$cfg_glpi["last_update_check"]);
		$dateDiff = mktime() 
				  - mktime(0,0,0,$last_check[1],$last_check[2],$last_check[0]);		
		$dayDiff=$dateDiff/60/60/24;
		if ($dayDiff<$cfg_glpi["auto_update_check"]){
			$do_check=0;
		}
				
	}
	 if ($do_check)
	 if (ini_get('allow_url_fopen')){
	 	echo "<br>";
	 	if ($auto) echo "<div align='center'><strong>".$lang["setup"][310]."</strong></div>";
		 $fp = fopen("http://glpi.indepnet.org/latest_version", 'r');
	     $latest_version = trim(@fread($fp, 16));
		 fclose($fp);
		 if ($latest_version == '')
			echo "<div align='center'>".$lang["setup"][304]."</div>";
		 else {			
	        $cur_version = str_replace(array('.', ' '), '', strtolower($cfg_glpi["version"]));
            $cur_version = (strlen($cur_version) == 2) ? intval($cur_version) * 10 : intval($cur_version);

            $lat_version = str_replace('.', '', strtolower($latest_version));
            $lat_version = (strlen($lat_version) == 2) ? intval($lat_version) * 10 : intval($lat_version);

			if ($cur_version < $lat_version){
				echo "<div align='center'>".$lang["setup"][301]." ".$latest_version."</div>";
				echo "<div align='center'>".$lang["setup"][302]."</div>";
				
				// Auto store new relase
				if ($auto){
					$query="UPDATE glpi_config SET founded_new_version='".$latest_version."' WHERE ID='1'";
					$db->query($query);
					}
				}
            else
				echo "<div align='center'>".$lang["setup"][303]."</div>";
				
				// Update last check
				if ($auto){
					$query="UPDATE glpi_config SET last_update_check='".date("Y-m-d")."' WHERE ID='1'";
					$db->query($query);
					}
			}
 	} else 
 	echo "<div align='center'>".$lang["setup"][305]."</div>";           
}

/**
* Update the DB configuration of the OCS Mode
*
* Update this DB config from the form, do the query and go back to the form.
*
*@param $input array : The _POST values from the config form
*@param $id int : template or basic computers
*
*@return nothing (displays or error)
*
**/
function ocsUpdateDBConfig($input, $id) {
	
	global $db,$phproot;
	if(!empty($input["ocs_db_user"]) && !empty($input["ocs_db_host"])) {

		if(empty($input["ocs_db_passwd"])) $input["ocs_db_passwd"] = "";

		$query = "update glpi_ocs_config set ocs_db_user = '".$input["ocs_db_user"]."', ocs_db_host = '".$input["ocs_db_host"]."', ocs_db_passwd = '".$input["ocs_db_passwd"]."', ocs_db_name = '".$input["ocs_db_name"]."' where ID = '".$id."'";

		$db->query($query);
	} else {
		print_r($input);
		echo $lang["ocsng"][17];
	}
	
}

/**
* Update the configuration of the OCS Mode
*
* Update this config from the form, do the query and go back to the form.
*
*@param $input array : The _POST values from the config form
*@param $id int : template or basic computers
*
*@return nothing (displays or error)
*
**/
function ocsUpdateConfig($input, $id) {
	
	global $db,$phproot;

	$checksum=0;

	if ($input["import_ip"]) $checksum|= pow(2,NETWORKS_FL);
	if ($input["import_device_ports"]) $checksum|= pow(2,PORTS_FL);
	if ($input["import_device_modems"]) $checksum|= pow(2,MODEMS_FL);
	if ($input["import_device_drives"]) $checksum|= pow(2,STORAGES_FL);
	if ($input["import_device_sound"]) $checksum|= pow(2,SOUNDS_FL);
	if ($input["import_device_gfxcard"]) $checksum|= pow(2,VIDEOS_FL);
	if ($input["import_device_iface"]) $checksum|= pow(2,NETWORKS_FL);
	if ($input["import_device_hdd"]) $checksum|= pow(2,STORAGES_FL);
	if ($input["import_device_memory"]) $checksum|= pow(2,MEMORIES_FL);
	if (	$input["import_device_processor"]
		||$input["import_general_contact"]
		||$input["import_general_comments"]
		||$input["import_general_domain"]
		||$input["import_general_os"]) $checksum|= pow(2,HARDWARE_FL);
	if (	$input["import_general_enterprise"]
		||$input["import_general_type"]
		||$input["import_general_model"]
		||$input["import_general_serial"]) $checksum|= pow(2,BIOS_FL);
	if ($input["import_printer"]) $checksum|= pow(2,PRINTERS_FL);
	if ($input["import_software"]) $checksum|= pow(2,SOFTWARES_FL);
	if ($input["import_monitor"]) $checksum|= pow(2,MONITORS_FL);
	if ($input["import_periph"]) $checksum|= pow(2,INPUTS_FL);
		
	$query = "update glpi_ocs_config set tag_limit = '".$input["tag_limit"]."', default_state = '".$input["default_state"]."', import_periph = '".$input["import_periph"]."'  ,import_monitor = '".$input["import_monitor"]."',import_software =  '".$input["import_software"]."', import_printer = '".$input["import_printer"]."',`import_general_os` = '".$input["import_general_os"]."',`import_general_serial` = '".$input["import_general_serial"]."',`import_general_model` = '".$input["import_general_model"]."',`import_general_enterprise` = '".$input["import_general_enterprise"]."',`import_general_type` = '".$input["import_general_type"]."',`import_general_domain` = '".$input["import_general_domain"]."',`import_general_contact` = '".$input["import_general_contact"]."',`import_general_comments` = '".$input["import_general_comments"]."',`import_device_processor` = '".$input["import_device_processor"]."',`import_device_memory` = '".$input["import_device_memory"]."',`import_device_hdd` = '".$input["import_device_hdd"]."',`import_device_iface` = '".$input["import_device_iface"]."',`import_device_gfxcard` = '".$input["import_device_gfxcard"]."',`import_device_sound` = '".$input["import_device_sound"]."',`import_device_drives` = '".$input["import_device_drives"]."',`import_device_modems` = '".$input["import_device_modems"]."',`import_device_ports` = '".$input["import_device_ports"]."',`import_ip` = '".$input["import_ip"]."',`checksum` = '".$checksum."'  where ID = '".$id."'";

	$db->query($query);
}



function ocsFormDBConfig($target, $id) {


	global  $db,$dbocs,$lang;

	if (!haveRight("config","1")) return false;	

	$query = "select * from glpi_ocs_config where ID = '".$id."'";
	$result = $db->query($query);
	$data=$db->fetch_array($result);

	echo "<form name='formdbconfig' action=\"$target\" method=\"post\">";
	echo "<input type='hidden' name='update_ocs_dbconfig' value='1'>";
	echo "<div align='center'><table class='tab_cadre'>";
	echo "<tr><th colspan='2'>".$lang["ocsconfig"][0]."</th></tr>";
	echo "<tr class='tab_bg_2'><td align='center'>".$lang["ocsconfig"][2]." </td><td> <input type=\"text\" name=\"ocs_db_host\" value=\"".$data["ocs_db_host"]."\"></td></tr>";
	echo "<tr class='tab_bg_2'><td align='center'>".$lang["ocsconfig"][4]." </td><td> <input type=\"text\" name=\"ocs_db_name\" value=\"".$data["ocs_db_name"]."\"></td></tr>";
	echo "<tr class='tab_bg_2'><td align='center'>".$lang["ocsconfig"][1]." </td><td> <input type=\"text\" name=\"ocs_db_user\" value=\"".$data["ocs_db_user"]."\"></td></tr>";
	echo "<tr class='tab_bg_2'><td align='center'>".$lang["ocsconfig"][3]." </td><td> <input type=\"password\" name=\"ocs_db_passwd\" value=\"".$data["ocs_db_passwd"]."\"></td></tr>";
	echo "</table></div>";
	echo "<p class=\"submit\"><input type=\"submit\" name=\"update_conf_ocs\" class=\"submit\" value=\"".$lang["buttons"][2]."\" ></p>";
	echo "</form>";

	echo "<div align='center'>";
	if (!$dbocs->error){
		echo $lang["ocsng"][18]."<br>";
		if ($dbocs->query("SELECT CHECKSUM FROM hardware")) {
			echo $lang["ocsng"][19]."</div>";
			ocsFormConfig($target, $id);
		} else echo $lang["ocsng"][20]."</div>";
	} else echo $lang["ocsng"][21]."</div>";
	
}

function ocsFormConfig($target, $id) {


	global  $db,$lang;

	if (!haveRight("config","1")) return false;	

	$query = "select * from glpi_ocs_config where ID = '".$id."'";
	$result = $db->query($query);
	$data=$db->fetch_array($result);

	echo "<form name='formconfig' action=\"$target\" method=\"post\">";
	echo "<input type='hidden' name='update_ocs_config' value='1'>";
	echo "<div align='center'><table class='tab_cadre'>";
	echo "<tr><th colspan='2'>".$lang["ocsconfig"][5]."</th></tr>";

	echo "<tr class='tab_bg_2'><td align='center'>".$lang["ocsconfig"][17]." </td><td> <input type=\"text\" size='30' name=\"tag_limit\" value=\"".$data["tag_limit"]."\"></td></tr>";

	echo "<tr class='tab_bg_2'><td align='center'>".$lang["ocsconfig"][16]." </td><td>";
	dropdownValue("glpi_dropdown_state","default_state",$data["default_state"]);
	echo "</td></tr>";

	$periph=$data["import_periph"];
	$monitor=$data["import_monitor"];
	$printer=$data["import_printer"];
	$software=$data["import_software"];
	echo "<tr class='tab_bg_2'><td align='center'>".$lang["ocsconfig"][8]." </td><td>";
	echo "<select name='import_periph'>";
	echo "<option value='0' ".($periph==0?" selected ":"").">".$lang["ocsconfig"][11]."</option>";
	echo "<option value='1' ".($periph==1?" selected ":"").">".$lang["ocsconfig"][10]."</option>";
	echo "<option value='2' ".($periph==2?" selected ":"").">".$lang["ocsconfig"][12]."</option>";
	echo "</select>";
	echo "</td></tr>";
	echo "<tr class='tab_bg_2'><td align='center'>".$lang["ocsconfig"][7]." </td><td>";
	echo "<select name='import_monitor'>";
	echo "<option value='0' ".($monitor==0?" selected ":"").">".$lang["ocsconfig"][11]."</option>";
	echo "<option value='1' ".($monitor==1?" selected ":"").">".$lang["ocsconfig"][10]."</option>";
	echo "<option value='2' ".($monitor==2?" selected ":"").">".$lang["ocsconfig"][12]."</option>";
	echo "</select>";
	echo "</td></tr>";
	echo "<tr class='tab_bg_2'><td align='center'>".$lang["ocsconfig"][9]." </td><td>";

	echo "<select name='import_printer'>";
	echo "<option value='0' ".($printer==0?" selected ":"").">".$lang["ocsconfig"][11]."</option>";
	echo "<option value='1' ".($printer==1?" selected ":"").">".$lang["ocsconfig"][10]."</option>";
	echo "<option value='2' ".($printer==2?" selected ":"").">".$lang["ocsconfig"][12]."</option>";
	echo "</select>";
	echo "</td></tr>";
	echo "<tr class='tab_bg_2'><td align='center'>".$lang["ocsconfig"][6]." </td><td>";
	echo "<select name='import_software'>";
	echo "<option value='0' ".($software==0?" selected ":"").">".$lang["ocsconfig"][11]."</option>";
	echo "<option value='1' ".($software==1?" selected ":"").">".$lang["ocsconfig"][12]."</option>";
	echo "</select>";

	echo "</td></tr>";
	echo "</table></div>";

	echo "<div align='center'>".$lang["ocsconfig"][15]."</div>";
	echo "<div align='center'>".$lang["ocsconfig"][14]."</div>";
	echo "<div align='center'>".$lang["ocsconfig"][13]."</div>";

	echo "<br />";

	echo "<div align='center'><table class='tab_cadre'>";
	echo "<tr><th>".$lang["ocsconfig"][27]."</th><th>".$lang["ocsconfig"][28]."</th></tr>";
	$os=$data["import_general_os"];
	$serial=$data["import_general_serial"];
	$model=$data["import_general_model"];
	$enterprise=$data["import_general_enterprise"];
	$type=$data["import_general_type"];
	$domain=$data["import_general_domain"];
	$contact=$data["import_general_contact"];
	$comments=$data["import_general_comments"];
	echo "<tr><td class='tab_bg_2' valign='top'><table width='100%' cellpadding='1' cellspacing='0' border='0'>";
	echo "<tr class='tab_bg_2'><td align='center'>".$lang["ocsconfig"][19]." </td><td>";
	echo "<select name='import_general_os'>";
	echo "<option value='0' ".($os==0?" selected ":"").">".$lang["choice"][0]."</option>";
	echo "<option value='1' ".($os==1?" selected ":"").">".$lang["choice"][1]."</option>";
	echo "</select>";
	echo "</td></tr>";
	echo "<tr class='tab_bg_2'><td align='center'>".$lang["common"][19]." </td><td>";
	echo "<select name='import_general_serial'>";
	echo "<option value='0' ".($serial==0?" selected ":"").">".$lang["choice"][0]."</option>";
	echo "<option value='1' ".($serial==1?" selected ":"").">".$lang["choice"][1]."</option>";
	echo "</select>";
	echo "</td></tr>";
	echo "<tr class='tab_bg_2'><td align='center'>".$lang["common"][22]." </td><td>";
	echo "<select name='import_general_model'>";
	echo "<option value='0' ".($model==0?" selected ":"").">".$lang["choice"][0]."</option>";
	echo "<option value='1' ".($model==1?" selected ":"").">".$lang["choice"][1]."</option>";
	echo "</select>";
	echo "</td></tr>";
	echo "<tr class='tab_bg_2'><td align='center'>".$lang["common"][5]." </td><td>";
	echo "<select name='import_general_enterprise'>";
	echo "<option value='0' ".($enterprise==0?" selected ":"").">".$lang["choice"][0]."</option>";
	echo "<option value='1' ".($enterprise==1?" selected ":"").">".$lang["choice"][1]."</option>";
	echo "</select>";
	echo "</td></tr>";
	echo "<tr class='tab_bg_2'><td align='center'>".$lang["common"][17]." </td><td>";
	echo "<select name='import_general_type'>";
	echo "<option value='0' ".($type==0?" selected ":"").">".$lang["choice"][0]."</option>";
	echo "<option value='1' ".($type==1?" selected ":"").">".$lang["choice"][1]."</option>";
	echo "</select>";
	echo "</td></tr>";
	echo "<tr class='tab_bg_2'><td align='center'>".$lang["ocsconfig"][24]." </td><td>";
	echo "<select name='import_general_domain'>";
	echo "<option value='0' ".($domain==0?" selected ":"").">".$lang["choice"][0]."</option>";
	echo "<option value='1' ".($domain==1?" selected ":"").">".$lang["choice"][1]."</option>";
	echo "</select>";
	echo "</td></tr>";
	echo "<tr class='tab_bg_2'><td align='center'>".$lang["common"][18]." </td><td>";
	echo "<select name='import_general_contact'>";
	echo "<option value='0' ".($contact==0?" selected ":"").">".$lang["choice"][0]."</option>";
	echo "<option value='1' ".($contact==1?" selected ":"").">".$lang["choice"][1]."</option>";
	echo "</select>";
	echo "</td></tr>";
	echo "<tr class='tab_bg_2'><td align='center'>".$lang["common"][25]." </td><td>";
	echo "<select name='import_general_comments'>";
	echo "<option value='0' ".($comments==0?" selected ":"").">".$lang["choice"][0]."</option>";
	echo "<option value='1' ".($comments==1?" selected ":"").">".$lang["choice"][1]."</option>";
	echo "</select>";
	echo "</td></tr>";

	echo "<tr class='tab_bg_2'><td colspan='2'>&nbsp;";
	echo "</td></tr>";
	$ip=$data["import_ip"];
	echo "<tr class='tab_bg_2'><td align='center'>".$lang["ocsconfig"][38]." </td><td>";
	echo "<select name='import_ip'>";
	echo "<option value='0' ".($ip==0?" selected ":"").">".$lang["choice"][0]."</option>";
	echo "<option value='1' ".($ip==1?" selected ":"").">".$lang["choice"][1]."</option>";
	echo "</select>";
	echo "</td></tr>";

	echo "</table></td>";
	echo "<td class='tab_bg_2' valign='top'><table width='100%' cellpadding='1' cellspacing='0' border='0'>";
	$processor=$data["import_device_processor"];
	$memory=$data["import_device_memory"];
	$hdd=$data["import_device_hdd"];
	$iface=$data["import_device_iface"];
	$gfxcard=$data["import_device_gfxcard"];
	$sound=$data["import_device_sound"];
	$drives=$data["import_device_drives"];
	$modems=$data["import_device_modems"];
	$ports=$data["import_device_ports"];

	echo "<tr class='tab_bg_2'><td align='center'>".$lang["ocsconfig"][29]." </td><td>";
	echo "<select name='import_device_processor'>";
	echo "<option value='0' ".($processor==0?" selected ":"").">".$lang["choice"][0]."</option>";
	echo "<option value='1' ".($processor==1?" selected ":"").">".$lang["choice"][1]."</option>";
	echo "</select>";
	echo "</td></tr>";

	echo "<tr class='tab_bg_2'><td align='center'>".$lang["ocsconfig"][30]." </td><td>";
	echo "<select name='import_device_memory'>";
	echo "<option value='0' ".($memory==0?" selected ":"").">".$lang["choice"][0]."</option>";
	echo "<option value='1' ".($memory==1?" selected ":"").">".$lang["choice"][1]."</option>";
	echo "</select>";
	echo "</td></tr>";

	echo "<tr class='tab_bg_2'><td align='center'>".$lang["ocsconfig"][31]." </td><td>";
	echo "<select name='import_device_hdd'>";
	echo "<option value='0' ".($hdd==0?" selected ":"").">".$lang["choice"][0]."</option>";
	echo "<option value='1' ".($hdd==1?" selected ":"").">".$lang["choice"][1]."</option>";
	echo "</select>";
	echo "</td></tr>";

	echo "<tr class='tab_bg_2'><td align='center'>".$lang["ocsconfig"][32]." </td><td>";
	echo "<select name='import_device_iface'>";
	echo "<option value='0' ".($iface==0?" selected ":"").">".$lang["choice"][0]."</option>";
	echo "<option value='1' ".($iface==1?" selected ":"").">".$lang["choice"][1]."</option>";
	echo "</select>";
	echo "</td></tr>";

	echo "<tr class='tab_bg_2'><td align='center'>".$lang["ocsconfig"][33]." </td><td>";
	echo "<select name='import_device_gfxcard'>";
	echo "<option value='0' ".($gfxcard==0?" selected ":"").">".$lang["choice"][0]."</option>";
	echo "<option value='1' ".($gfxcard==1?" selected ":"").">".$lang["choice"][1]."</option>";
	echo "</select>";
	echo "</td></tr>";

	echo "<tr class='tab_bg_2'><td align='center'>".$lang["ocsconfig"][34]." </td><td>";
	echo "<select name='import_device_sound'>";
	echo "<option value='0' ".($sound==0?" selected ":"").">".$lang["choice"][0]."</option>";
	echo "<option value='1' ".($sound==1?" selected ":"").">".$lang["choice"][1]."</option>";
	echo "</select>";
	echo "</td></tr>";

	echo "<tr class='tab_bg_2'><td align='center'>".$lang["ocsconfig"][35]." </td><td>";
	echo "<select name='import_device_drives'>";
	echo "<option value='0' ".($drives==0?" selected ":"").">".$lang["choice"][0]."</option>";
	echo "<option value='1' ".($drives==1?" selected ":"").">".$lang["choice"][1]."</option>";
	echo "</select>";
	echo "</td></tr>";

	echo "<tr class='tab_bg_2'><td align='center'>".$lang["ocsconfig"][36]." </td><td>";
	echo "<select name='import_device_modems'>";
	echo "<option value='0' ".($modems==0?" selected ":"").">".$lang["choice"][0]."</option>";
	echo "<option value='1' ".($modems==1?" selected ":"").">".$lang["choice"][1]."</option>";
	echo "</select>";
	echo "</td></tr>";

	echo "<tr class='tab_bg_2'><td align='center'>".$lang["ocsconfig"][37]." </td><td>";
	echo "<select name='import_device_ports'>";
	echo "<option value='0' ".($ports==0?" selected ":"").">".$lang["choice"][0]."</option>";
	echo "<option value='1' ".($ports==1?" selected ":"").">".$lang["choice"][1]."</option>";
	echo "</select>";
	echo "</td></tr>";

	echo "</table></td></tr>";
	echo "</table></div>";
	echo "<p class=\"submit\"><input type=\"submit\" name=\"update_conf_ocs\" class=\"submit\" value=\"".$lang["buttons"][2]."\" ></p>";
	echo "</form>";
	
}

?>
