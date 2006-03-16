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

// ----------------------------------------------------------------------
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

include ("_relpos.php");
// CLASSES Reservation_Item and Reservation_Resa

class ReservationItem{
	var $fields	= array();
	var $updates	= array();
	var $obj = NULL;	
	function getfromDB ($ID) {
		global $db;
		// Make new database object and fill variables
		$query = "SELECT * FROM glpi_reservation_item WHERE (ID = '$ID')";
		if ($result = $db->query($query)) {
			$data = $db->fetch_array($result);
			foreach ($data as $key => $val) {
				$this->fields[$key] = $val;
			}
		if (!isset($this->fields["device_type"]))			
		return false;
			switch ($this->fields["device_type"]){
			case COMPUTER_TYPE :
				$this->obj=new Computer;
				break;
			case NETWORKING_TYPE :
				$this->obj=new Netdevice;
				break;
			case PRINTER_TYPE :
				$this->obj=new Printer;
				break;
			case MONITOR_TYPE : 
				$this->obj= new Monitor;	
				break;
			case PERIPHERAL_TYPE : 
				$this->obj= new Peripheral;	
				break;				
			case PHONE_TYPE : 
				$this->obj= new Phone;	
				break;					
			case SOFTWARE_TYPE : 
				$this->obj= new Software;	
				break;					
			}
			if ($this->obj!=NULL)
			return $this->obj->getfromDB($this->fields["id_device"]);
			else return false;
			
		} else {
			return false;
		}
	}
	function getType (){
		global $lang;
		
		switch ($this->fields["device_type"]){
			case COMPUTER_TYPE :
				return $lang["computers"][44];
				break;
			case NETWORKING_TYPE :
				return $lang["networking"][12];
				break;
			case PRINTER_TYPE :
				return $lang["printers"][4];
				break;
			case MONITOR_TYPE : 
				return $lang["monitors"][4];
				break;
			case PERIPHERAL_TYPE : 
				if (isset($this->obj->fields["type"])&&$this->obj->fields["type"]!=0)
					return getDropdownName("glpi_type_peripherals",$this->obj->fields["type"]);
				else	return $lang["peripherals"][4];

				return $lang["peripherals"][4];
				break;				
			case SOFTWARE_TYPE : 
				return $lang["software"][10];
				break;
			case PHONE_TYPE : 
				return $lang["phones"][4];
				break;
			
			}
	
	}
	function getName(){
		if (isset($this->obj->fields["name"])&&$this->obj->fields["name"]!="")
	return $this->obj->fields["name"];
	else return "N/A";
	}

	function getLocation(){
		if (isset($this->obj->fields["location"])&&$this->obj->fields["location"]!="")
	return getTreeValueCompleteName("glpi_dropdown_locations",$this->obj->fields["location"]);
	else return "N/A";
	}
	
	function getLink(){
	
		global $cfg_glpi;
		$out="";	
		switch ($this->fields["device_type"]){
			case COMPUTER_TYPE :
				$out= "<a href=\"".$cfg_glpi["root_doc"]."/computers/computers-info-form.php?ID=".$this->fields["id_device"]."\">".$this->getName();
				if ($cfg_glpi["view_ID"]) $out.= " (".$this->fields["id_device"].")";
				$out.= "</a>";
				break;
			case PHONE_TYPE : 
				$out= "<a href=\"".$cfg_glpi["root_doc"]."/phones/phones-info-form.php?ID=".$this->fields["id_device"]."\">".$this->getName();
				if ($cfg_glpi["view_ID"]) $out.= " (".$this->fields["id_device"].")";
				$out.= "</a>";
				break;				
			case NETWORKING_TYPE :
				$out= "<a href=\"".$cfg_glpi["root_doc"]."/networking/networking-info-form.php?ID=".$this->fields["id_device"]."\">".$this->getName();
				if ($cfg_glpi["view_ID"]) $out.= " (".$this->fields["id_device"].")";
				$out.= "</a>";
				break;
			case PRINTER_TYPE :
				$out= "<a href=\"".$cfg_glpi["root_doc"]."/printers/printers-info-form.php?ID=".$this->fields["id_device"]."\">".$this->getName();
				if ($cfg_glpi["view_ID"]) $out.= " (".$this->fields["id_device"].")";
				$out.= "</a>";
				break;
			case MONITOR_TYPE : 
				$out= "<a href=\"".$cfg_glpi["root_doc"]."/monitors/monitors-info-form.php?ID=".$this->fields["id_device"]."\">".$this->getName();
				if ($cfg_glpi["view_ID"]) $out.= " (".$this->fields["id_device"].")";
				$out.= "</a>";
				break;
			case PERIPHERAL_TYPE : 
				$out= "<a href=\"".$cfg_glpi["root_doc"]."/peripherals/peripherals-info-form.php?ID=".$this->fields["id_device"]."\">".$this->getName();
				if ($cfg_glpi["view_ID"]) $out.= " (".$this->fields["id_device"].")";
				$out.= "</a>";
				break;	
			case SOFTWARE_TYPE : 
				$out= "<a href=\"".$cfg_glpi["root_doc"]."/software/software-info-form.php?ID=".$this->fields["id_device"]."\">".$this->getName();
				if ($cfg_glpi["view_ID"]) $out.= " (".$this->fields["id_device"].")";
				$out.= "</a>";
				break;								
			case PHONE_TYPE : 
				$out= "<a href=\"".$cfg_glpi["root_doc"]."/phones/phones-info-form.php?ID=".$this->fields["id_device"]."\">".$this->getName();
				if ($cfg_glpi["view_ID"]) $out.= " (".$this->fields["id_device"].")";
				$out.= "</a>";
				break;	
			}
	return $out;
	
	}
	
	
	function getEmpty () {
		//make an empty database object
		global $db;
		$fields = $db->list_fields("glpi_reservation_item");
		$columns = $db->num_fields($fields);
		for ($i = 0; $i < $columns; $i++) {
			$name = $db->field_name($fields, $i);
			$this->fields[$name] = "";
		}
	}

	function updateInDB($updates)  {

		global $db;

		for ($i=0; $i < count($updates); $i++) {
			$query  = "UPDATE glpi_reservation_item SET ";
			$query .= $updates[$i];
			$query .= "='";
			$query .= $this->fields[$updates[$i]];
			$query .= "' WHERE ID='";
			$query .= $this->fields["ID"];	
			$query .= "'";
			$result=$db->query($query);
		}
		
	}
	
	function addToDB() {
		
		global $db;

		// Build query
		$query = "INSERT INTO glpi_reservation_item (";
		$i=0;
		foreach ($this->fields as $key => $val) {
			$fields[$i] = $key;
			$values[$i] = $val;
			$i++;
		}		
		for ($i=0; $i < count($fields); $i++) {
			$query .= $fields[$i];
			if ($i!=count($fields)-1) {
				$query .= ",";
			}
		}
		$query .= ") VALUES (";
		for ($i=0; $i < count($values); $i++) {
			$query .= "'".$values[$i]."'";
			if ($i!=count($values)-1) {
				$query .= ",";
			}
		}
		$query .= ")";

		$result=$db->query($query);
		return $db->insert_id();
	}

	function deleteFromDB($ID) {

		global $db;

		$query = "DELETE from glpi_reservation_item WHERE ID = '$ID'";
		if ($result = $db->query($query)) {
			$query2 = "DELETE FROM glpi_reservation_resa WHERE (id_item = '$ID')";
			$result2 = $db->query($query2);
			return true;
		} else {
			return false;
		}
	}
	
}

class ReservationResa{
	var $fields	= array();
	var $updates	= array();
	
function getfromDB ($ID) {

		// Make new database object and fill variables
		global $db;
		$query = "SELECT * FROM glpi_reservation_resa WHERE (ID = '$ID')";
		if ($result = $db->query($query)) {
			$data = $db->fetch_array($result);
			if (!empty($data))

			foreach ($data as $key => $val) {
				$this->fields[$key] = $val;
			}
			return true;

		} else {
			return false;
		}
	}

function getEmpty () {
	//make an empty database object
	global $db;
	$fields = $db->list_fields("glpi_reservation_resa");
	$columns = $db->num_fields($fields);
	for ($i = 0; $i < $columns; $i++) {
		$name = $db->field_name($fields, $i);
		$this->fields[$name] = "";
	}
}

	function updateInDB($updates)  {

		global $db;

		for ($i=0; $i < count($updates); $i++) {
			$query  = "UPDATE glpi_reservation_resa SET ";
			$query .= $updates[$i];
			$query .= "='";
			$query .= $this->fields[$updates[$i]];
			$query .= "' WHERE ID='";
			$query .= $this->fields["ID"];	
			$query .= "'";
			$result=$db->query($query);
		}
		
	}
	
	function addToDB() {
		
		global $db;

		// Build query
		$query = "INSERT INTO glpi_reservation_resa (";
		$i=0;
		foreach ($this->fields as $key => $val) {
			$fields[$i] = $key;
			$values[$i] = $val;
			$i++;
		}		
		for ($i=0; $i < count($fields); $i++) {
			$query .= $fields[$i];
			if ($i!=count($fields)-1) {
				$query .= ",";
			}
		}
		$query .= ") VALUES (";
		for ($i=0; $i < count($values); $i++) {
			$query .= "'".$values[$i]."'";
			if ($i!=count($values)-1) {
				$query .= ",";
			}
		}
		$query .= ")";

		$result=$db->query($query);
		return $db->insert_id();
	}

	function deleteFromDB($ID) {

		global $db;

		$query = "DELETE from glpi_reservation_resa WHERE ID = '$ID'";
		if ($result = $db->query($query)) {
			return true;
		} else {
			return false;
		}
	}
	
	function is_reserved(){
		global $db;
		if (!isset($this->fields["id_item"])||empty($this->fields["id_item"]))
		return true;
		
		// When modify a reservation do not itself take into account 
		$ID_where="";
		if(isset($this->fields["ID"]))
		$ID_where=" (ID <> '".$this->fields["ID"]."') AND ";
		
		$query = "SELECT * FROM glpi_reservation_resa".
		" WHERE $ID_where (id_item = '".$this->fields["id_item"]."') AND ( ('".$this->fields["begin"]."' < begin AND '".$this->fields["end"]."' > begin) OR ('".$this->fields["begin"]."' < end AND '".$this->fields["end"]."' >= end) OR ('".$this->fields["begin"]."' >= begin AND '".$this->fields["end"]."' < end))";
//		echo $query."<br>";
		if ($result=$db->query($query)){
			return ($db->numrows($result)>0);
		}
		return true;
		}
	function test_valid_date(){
		return (strtotime($this->fields["begin"])<strtotime($this->fields["end"]));
		}

	function displayError($type,$ID,$target){
		global $HTMLRel,$lang;
		
		echo "<br><div align='center'>";
		switch ($type){
			case "date":
			 echo $lang["reservation"][19];
			break;
			case "is_res":
			 echo $lang["reservation"][18];
			break;
			default :
				echo "Erreur Inconnue";
			break;
		}
		echo "<br><a href='".$target."?show=resa&amp;ID=$ID'>".$lang["reservation"][20]."</a>";
		echo "</div>";
		}
	function textDescription(){
		global $lang;
		
		$ci=new ReservationItem();
		$ci->getFromDB($this->fields["id_item"]);		
		
		$u=new User();
		$u->getFromDbbyID($this->fields["id_user"]);
		$content="";
		
		$content.=$lang["mailing"][1]."\n";	
		$content.=$lang["mailing"][2]." ".$u->getName()."\n";
		$content.=$lang["mailing"][7]." ".$ci->getName()."\n";
		$content.=$lang["mailing"][20]." ".convDateTime($this->fields["begin"])."\n";
		$content.=$lang["mailing"][21]." ".convDateTime($this->fields["end"])."\n";
		$content.=$lang["mailing"][22]." ".$this->fields["comment"]."\n";
		$content.=$lang["mailing"][1]."\n";	
		return $content;
		
	}

}


?>