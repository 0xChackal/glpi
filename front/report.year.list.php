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




$NEEDED_ITEMS=array("contract","infocom");

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");


checkRight("reports","r");

commonHeader($LANG["title"][16],$_SERVER['PHP_SELF'],"utils","report");

$items=array(COMPUTER_TYPE,PRINTER_TYPE,MONITOR_TYPE,NETWORKING_TYPE,PERIPHERAL_TYPE,SOFTWARE_TYPE,PHONE_TYPE);

# Titre
echo "<div align='center'><big><strong>".$LANG["reports"][57]."</strong></big><br><br>";

# Request All
if(isset($_POST["item_type"][0])&&$_POST["item_type"][0] == '0'){
	$_POST["item_type"]=$items;
}

if (isset($_POST["item_type"])&&is_array($_POST["item_type"])){
	$query=array();
	foreach ($_POST["item_type"] as $key => $val)
	if (in_array($val,$items)){
		$query[$val] = "SELECT  ".$LINK_ID_TABLE[$val].".name AS itemname, ".$LINK_ID_TABLE[$val].".deleted AS itemdeleted, ";
		$query[$val].= " glpi_dropdown_locations.completename AS location, glpi_dropdown_contract_type.name AS type, glpi_infocoms.buy_date, glpi_infocoms.warranty_duration, glpi_contracts.begin_date, glpi_contracts.duration ";
		$query[$val].= " FROM ".$LINK_ID_TABLE[$val]." ";
		$query[$val].= " LEFT JOIN glpi_contract_device ON glpi_contract_device.device_type='$val' AND ".$LINK_ID_TABLE[$val].".ID =  glpi_contract_device.FK_device ";
		$query[$val].= " LEFT JOIN glpi_contracts ON glpi_contract_device.FK_contract=glpi_contracts.ID AND glpi_contract_device.FK_contract IS NOT NULL ";
		$query[$val].= " LEFT JOIN glpi_infocoms ON glpi_infocoms.device_type='$val' AND ".$LINK_ID_TABLE[$val].".ID =  glpi_infocoms.FK_device ";
		$query[$val].= " LEFT JOIN glpi_dropdown_contract_type ON (glpi_contracts.contract_type = glpi_dropdown_contract_type.ID) ";
		$query[$val].= " LEFT JOIN glpi_dropdown_locations ON (".$LINK_ID_TABLE[$val].".location = glpi_dropdown_locations.ID) ";

		$query[$val].=" WHERE ".$LINK_ID_TABLE[$val].".is_template ='0' ";
		$query[$val].=getEntitiesRestrictRequest("AND",$LINK_ID_TABLE[$val]);

		if(isset($_POST["annee"][0])&&$_POST["annee"][0] != 'toutes')
		{
			$query[$val].=" AND ( ";
			$first=true;
			foreach ($_POST["annee"] as $key2 => $val2){
				if (!$first) $query[$val].=" OR ";
				else $first=false;

				$query[$val].= " YEAR(glpi_infocoms.buy_date) = '".$val2."'";
				$query[$val].= " OR YEAR(glpi_contracts.begin_date) = '".$val2."'";
			}
			$query[$val].=")";
		}
		$query[$val].= " order by ".$LINK_ID_TABLE[$val].".name asc";
	}
}

$ci=new CommonItem();

foreach ($query as $key => $val){
	$result = $DB->query($val);
	if ($result&&$DB->numrows($result)){
		$ci->setType($key);
		echo " <div align='center'><strong>".$ci->getType()."</strong>";
		echo "<table class='tab_cadre_report'>";
		echo "<tr> ";
		echo "<th>".$LANG["common"][16]."</th>";
		echo "<th>".$LANG["common"][28]."</th>";
		echo "<th>".$LANG["common"][15]."</th>";
		echo "<th>".$LANG["financial"][14]."</th>";
		echo "<th>".$LANG["financial"][80]."</th>";
		echo "<th>".$LANG["financial"][6]."</th>";
		echo "<th>".$LANG["search"][8]."</th>";
		echo "<th>".$LANG["search"][9]."</th>";
		echo "</tr>";
		while( $data = $DB->fetch_array($result)){
			echo "<tr>";	
			if($data['itemname']) {
				echo "<td> ".$data['itemname']." </td>"; 
			} else { 
				echo "<td> N/A </td>";
			}
			if($data['itemdeleted']) {
				echo "<td> ".$data['itemdeleted']." </td>"; 
			} else { 
				echo "<td> N/A </td>";
			}
	
			if($data['location']) {
				echo "<td> ".$data['location']." </td>"; 
			} else { 
				echo "<td> N/A </td>";
			}
	
			if($data['buy_date']) {
				echo "<td> ".convDate($data['buy_date'])." </td>"; 
				if($data["warranty_duration"]) {
					echo "<td> ".getWarrantyExpir($data["buy_date"],$data["warranty_duration"])." </td>"; 
				} else {
					echo "<td> N/A </td>";
				}
			} else {
				echo "<td> N/A </td><td> N/A </td>";
			}
			if($data['type']) {
				echo "<td> ".$data['type']." </td>"; 
			} else { 
				echo "<td> N/A </td>";
			}
	
			if($data['begin_date']) {
				echo "<td> ".convDate($data['begin_date'])." </td>"; 
				if($data["duration"]) {
					echo "<td> ".getWarrantyExpir($data["begin_date"],$data["duration"])." </td>"; 
				} else {
					echo "<td> N/A </td>";
				}
			} else {
				echo "<td> N/A </td><td> N/A </td>";
			}
	
			echo "</tr>\n";
		}	
		echo "</table></div><br><hr><br>";
	}
}

echo "</div>";
commonFooter();
?>
