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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

// Direct access to file
if(ereg("dropdownUsers.php",$_SERVER['PHP_SELF'])){
	define('GLPI_ROOT','..');
	$AJAX_INCLUDE=1;
	include (GLPI_ROOT."/inc/includes.php");
	header("Content-Type: text/html; charset=UTF-8");
	header_nocache();
}
if (!defined('GLPI_ROOT')){
	die("Can not acces directly to this file");
	}

checkCentralAccess();
// Make a select box with all glpi users

$joinprofile=false;
if (!isset($_POST['right'])) $_POST['right']="all";
if ($_POST['right']=="interface"){
	$where=" glpi_profiles.".$_POST['right']."='central' ";
	$joinprofile=true;
} else if ($_POST['right']=="ID"){
	$where=" glpi_users.ID='".$_SESSION["glpiID"]."' ";
} else if ($_POST['right']=="all"){
	$where=" glpi_users.ID > '1' ";
} else {
	$joinprofile=true;
	$where=" glpi_profiles.".$_POST['right']."='1' ";
}

$where.=" AND glpi_users.deleted='0' ";
if (isset($_POST["entity_restrict"])&&$_POST["entity_restrict"]>=0){
	$where.= " AND glpi_users_profiles.FK_entities='".$_POST["entity_restrict"]."'";
} else {
	$where.=getEntitiesRestrictRequest("AND","glpi_users_profiles");
}


if (isset($_POST['value']))
$where.=" AND  (glpi_users.ID <> '".$_POST['value']."') ";

if (strlen($_POST['searchText'])>0&&$_POST['searchText']!=$CFG_GLPI["ajax_wildcard"]){
	$where.=" AND (glpi_users.name ".makeTextSearch($_POST['searchText'])." OR glpi_users.realname ".makeTextSearch($_POST['searchText'])." OR glpi_users.firstname ".makeTextSearch($_POST['searchText']).")";
}


$NBMAX=$CFG_GLPI["dropdown_max"];
$LIMIT="LIMIT 0,$NBMAX";
if ($_POST['searchText']==$CFG_GLPI["ajax_wildcard"]) $LIMIT="";

$query = "SELECT DISTINCT glpi_users.* FROM glpi_users ";
$query.=" LEFT JOIN glpi_users_profiles ON (glpi_users.ID = glpi_users_profiles.FK_users)";
if ($joinprofile){
  $query .=" LEFT JOIN glpi_profiles ON (glpi_profiles.ID= glpi_users_profiles.FK_profiles) ";
}
$query.= " WHERE $where ORDER BY glpi_users.realname,glpi_users.firstname, glpi_users.name $LIMIT";
//echo $query;
$result = $DB->query($query);

echo "<select id='dropdown_".$_POST["myname"].$_POST["rand"]."' name=\"".$_POST['myname']."\">";

if ($_POST['searchText']!=$CFG_GLPI["ajax_wildcard"]&&$DB->numrows($result)==$NBMAX)
echo "<option value=\"0\">--".$LANG["common"][11]."--</option>";


if ($_POST['all']==0)
echo "<option value=\"0\">[ Nobody ]</option>";
else if($_POST['all']==1) echo "<option value=\"0\">[ ".$LANG["search"][7]." ]</option>";

if (isset($_POST['value'])){
	$output=getUserName($_POST['value']);
	if (!empty($output)&&$output!="&nbsp;")
		echo "<option selected value='".$_POST['value']."'>".$output."</option>";
}		

if ($DB->numrows($result)) {
	while ($data=$DB->fetch_array($result)) {
			
		$output=formatUserName($data["ID"],$data["name"],$data["realname"],$data["firstname"]);
		
	
		echo "<option value=\"".$data["ID"]."\" title=\"$output - ".$data["name"]."\">".substr($output,0,$CFG_GLPI["dropdown_limit"])."</option>";
	}
}
echo "</select>";

if (isset($_POST["comments"])&&$_POST["comments"]){
	echo "<script type='text/javascript' >\n";
	echo "   new Form.Element.Observer('dropdown_".$_POST["myname"].$_POST["rand"]."', 1, \n";
	echo "      function(element, value) {\n";
	echo "      	new Ajax.Updater('comments_".$_POST["myname"].$_POST["rand"]."','".$CFG_GLPI["root_doc"]."/ajax/comments.php',{asynchronous:true, evalScripts:true, \n";
	echo "           method:'post', parameters:'value='+value+'&table=glpi_users'\n";
	echo "})})\n";
	echo "</script>\n";
}

// Manage updates others dropdown for helpdesk
if (isset($_POST["helpdesk_ajax"])&&$_POST["helpdesk_ajax"]){
	echo "<script type='text/javascript' >";
	echo "   new Form.Element.Observer('dropdown_author".$_POST["rand"]."', 1, ";
	echo "      function(element, value) {";
	echo "      	new Ajax.Updater('tracking_my_devices','".$CFG_GLPI["root_doc"]."/ajax/updateTrackingDeviceType.php',{asynchronous:true, evalScripts:true, ";
	echo "           method:'post', parameters:'userID=' + value+'&device_type=0'";
	echo "})})";
	echo "</script>";

	echo "<script type='text/javascript' >\n";
	echo "   new Form.Element.Observer('dropdown_author".$_POST["rand"]."', 1, \n";
	echo "      function(element, value) {\n";
	echo "      	new Ajax.Updater('uemail_result','".$CFG_GLPI["root_doc"]."/ajax/uemailUpdate.php',{asynchronous:true, evalScripts:true, \n";
	echo "           method:'post', parameters:'value='+value\n";
	echo "})})\n";
	echo "</script>\n";
}


?>
