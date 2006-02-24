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
	include ($phproot."/glpi/includes.php");
	header("Content-Type: text/html; charset=UTF-8");
	header_nocache();

	checkAuthentication("post-only");

	// Make a select box with preselected values
	$db = new DB;

	if($_POST['table'] == "glpi_dropdown_netpoint") {

		$where="";
		if (strlen($_POST['searchText'])>0&&$_POST['searchText']!=$cfg_features["ajax_wildcard"])
			$where=" WHERE (t1.name LIKE '%".$_POST['searchText']."%' OR t2.completename LIKE '%".$_POST['searchText']."%')";

		$NBMAX=$cfg_layout["dropdown_max"];
		$LIMIT="LIMIT 0,$NBMAX";
		if ($_POST['searchText']==$cfg_features["ajax_wildcard"]) $LIMIT="";
			
			
		$query = "select t1.ID as ID, t1.name as netpname, t2.completename as loc from glpi_dropdown_netpoint as t1";
		$query .= " left join glpi_dropdown_locations as t2 on (t1.location = t2.ID)";
		$query.=$where;
		$query .= " order by t1.name,t2.name $LIMIT"; 
		
		$result = $db->query($query);

		echo "<select name=\"".$_POST['myname']."\">";
		
		if ($_POST['searchText']!=$cfg_features["ajax_wildcard"]&&$db->numrows($result)==$NBMAX)
		echo "<option value=\"0\">--".$lang["common"][11]."--</option>";
		else echo "<option value=\"0\">-----</option>";
		
		$output=getDropdownName($_POST['table'],$_POST['value'],1);
		if (!empty($output["name"])&&$output["name"]!="&nbsp;")
		echo "<option selected value='".$_POST['value']."'>".$output["name"]."</option>";
		
				
		if ($db->numrows($result)) {
			while ($data =$db->fetch_array($result)) {
				$output = $data['netpname'];
				$loc=$data['loc'];
				$ID = $data['ID'];
				echo "<option value=\"$ID\" title=\"$output\"";
				if ($ID==$_POST['value']) echo " selected ";
				echo ">".$output." ($loc)</option>";
			}
		}
		echo "</select>";
	}	else {

	$where="WHERE '1'='1' ";
	if (in_array($_POST['table'],$deleted_tables))
		$where.=" AND deleted='N' ";
	if (in_array($_POST['table'],$template_tables))
		$where.=" AND is_template='0' ";
		

$where .=" AND  (ID <> '".$_POST['value']."' ";

	if (strlen($_POST['searchText'])>0&&$_POST['searchText']!=$cfg_features["ajax_wildcard"])
		if (in_array($_POST['table'],$dropdowntree_tables))
		$where.=" AND completename LIKE '%".$_POST['searchText']."%' ";
		else $where.=" AND name LIKE '%".$_POST['searchText']."%' ";

$where.=")";

	$NBMAX=$cfg_layout["dropdown_max"];
	$LIMIT="LIMIT 0,$NBMAX";
	if ($_POST['searchText']==$cfg_features["ajax_wildcard"]) $LIMIT="";


	if (in_array($_POST['table'],$dropdowntree_tables))
		$query = "SELECT ID, completename as name FROM ".$_POST['table']." $where ORDER BY completename $LIMIT";
	else $query = "SELECT ID, name FROM ".$_POST['table']." $where ORDER BY name $LIMIT";
	
	$result = $db->query($query);

	echo "<select name=\"".$_POST['myname']."\" size='1'>";

	if ($_POST['searchText']!=$cfg_features["ajax_wildcard"]&&$db->numrows($result)==$NBMAX)
	echo "<option value=\"0\">--".$lang["common"][11]."--</option>";

	if ($table=="glpi_dropdown_kbcategories")
	echo "<option value=\"0\">--".$lang["knowbase"][12]."--</option>";
	else echo "<option value=\"0\">-----</option>";

	$output=getDropdownName($_POST['table'],$_POST['value'],1);
	if (!empty($output["name"])&&$output["name"]!="&nbsp;")
	echo "<option selected value='".$_POST['value']."'>".$output["name"]."</option>";
	
	if ($db->numrows($result)) {
		while ($data =$db->fetch_array($result)) {
			$display = $data['name'];
			$ID = $data['ID'];
			if (empty($display)) $display="($ID)";
				echo "<option value=\"$ID\" title=\"$display\">".substr($display,0,$_POST["limit"])."</option>";
		}
	}
	echo "</select>";
	if ($_POST["display_comments"]&&!empty($output["comments"])) {
		$rand=mt_rand();
		echo "<img src='".$HTMLRel."/pics/aide.png' onmouseout=\"setdisplay(getElementById('comments_$rand'),'none')\" onmouseover=\"setdisplay(getElementById('comments_$rand'),'block')\">";
		echo "<span class='over_link' id='comments_$rand'>".nl2br($output["comments"])."</span>";
	}

	}


?>