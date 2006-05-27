<?php
/*
 ----------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2006 by the INDEPNET Development Team.
 
 http://indepnet.net/   http://glpi-project.org
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
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

	include ("_relpos.php");
	$AJAX_INCLUDE=1;
	include ($phproot . "/inc/includes.php");
	header("Content-Type: text/html; charset=UTF-8");
	header_nocache();

	checkRight("create_ticket","1");
	
		$where="WHERE '1'='1' ";
		if (in_array($_POST['table'],$cfg_glpi["deleted_tables"]))
			$where.=" AND deleted='N' ";
		if (in_array($_POST['table'],$cfg_glpi["template_tables"]))
			$where.=" AND is_template='0' ";		
			
		if (strlen($_POST['searchText'])>0&&$_POST['searchText']!=$cfg_glpi["ajax_wildcard"]){
				$WWHERE="";
				if ($_POST['table']!="glpi_software"){
				$WWHERE=" OR contact ".makeTextSearch($_POST['searchText'])." OR serial ".makeTextSearch($_POST['searchText'])." OR otherserial ".makeTextSearch($_POST['searchText']);
				}
			$where.=" AND (name ".makeTextSearch($_POST['searchText'])." OR ID = '".$_POST['searchText']."' $WWHERE)";
		}

		
		$NBMAX=$cfg_glpi["dropdown_max"];
		$LIMIT="LIMIT 0,$NBMAX";
		if ($_POST['searchText']==$cfg_glpi["ajax_wildcard"]) $LIMIT="";
						
		$query = "SELECT * FROM ".$_POST['table']." $where ORDER BY name $LIMIT";
		$result = $db->query($query);
		
		echo "<select name=\"".$_POST['myname']."\" size='1'>";
		
		if ($_POST['searchText']!=$cfg_glpi["ajax_wildcard"]&&$db->numrows($result)==$NBMAX)
			echo "<option value=\"0\">--".$lang["common"][11]."--</option>";
	
		echo "<option value=\"0\">-----</option>";
		if ($db->numrows($result)) {
			while ($data = $db->fetch_array($result)) {
			
				$output = $data['name']." (".$data['ID'].")";
				if ($_POST['table']!="glpi_software"){
					
					$output.=" - ".$data['contact']." - ".$data['serial']." - ".$data['otherserial'];
				}
				$ID = $data['ID'];
				if (empty($output)) $output="($ID)";
				echo "<option value=\"$ID\" title=\"$output\">".substr($output,0,$cfg_glpi["dropdown_limit"])."</option>";
			}
		}
		echo "</select>";
	
		
?>	