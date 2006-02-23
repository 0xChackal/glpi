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
include ($phproot . "/glpi/includes.php");
include ($phproot . "/glpi/includes_users.php");
include ($phproot . "/glpi/includes_tracking.php");
include ($phproot . "/glpi/includes_computers.php");
include ($phproot . "/glpi/includes_printers.php");
include ($phproot . "/glpi/includes_monitors.php");
include ($phproot . "/glpi/includes_peripherals.php");
include ($phproot . "/glpi/includes_networking.php");
include ($phproot . "/glpi/includes_software.php");
include ($phproot . "/glpi/includes_enterprises.php");
include ($phproot . "/glpi/includes_phones.php");

checkAuthentication("normal");

commonHeader($lang["title"][10],$_SERVER["PHP_SELF"]);

 titleTracking();

 
if(isset($_GET)) $tab = $_GET;
//if(empty($tab) && isset($_POST)) $tab = $_POST;


if (isset($tab['reset'])&&$tab['reset']=="reset_before") {
	unset($_SESSION['tracking']);
	unset($tab['reset']);
}

if (!isset($tab['reset'])){
	if (is_array($tab))
	foreach ($tab as $key => $val)
		if ($key[0]!='_')
			$_SESSION['tracking'][$key]=$val;
}
if (isset($tab['reset'])) unset($_SESSION['tracking']);

if (isset($_SESSION['tracking'])&&is_array($_SESSION['tracking']))
foreach ($_SESSION['tracking'] as $key => $val)
if (!isset($tab[$key])) $tab[$key]=$val;

if (!isset($tab["start"])||isset($tab['reset'])) $tab["start"]=0;
if (!isset($tab["priority"])||isset($tab['reset'])) $tab["priority"]=0;
if (!isset($tab["field2"])||isset($tab['reset'])) $tab["field2"]="both";
if (!isset($tab["contains2"])||isset($tab['reset'])) $tab["contains2"]="";
if (!isset($tab["author"])||isset($tab['reset'])) $tab["author"]=0;
if (!isset($tab["assign"])||isset($tab['reset'])) $tab["assign"]=0;
if (!isset($tab["assign_ent"])||isset($tab['reset'])) $tab["assign_ent"]=0;
if (!isset($tab["category"])||isset($tab['reset'])) $tab["category"]="";
if (!isset($tab["status"])||isset($tab['reset'])) $tab["status"]="notold";
if (!isset($tab["showfollowups"])||isset($tab['reset'])) $tab["showfollowups"]=0;
if (!isset($tab["item"])||isset($tab['reset'])) $tab["item"]=0;
if (!isset($tab["type"])||isset($tab['reset'])) $tab["type"]=0;

if (!isset($tab["extended"])) $tab["extended"]=0;

if (!isset($tab["contains"])||isset($tab['reset'])) $tab["contains"]="";
if (!isset($tab["contains3"])||isset($tab['reset'])) $tab["contains3"]="";
if (!isset($tab["date1"])||isset($tab['reset'])) $tab["date1"]="0000-00-00";
if (!isset($tab["enddate1"])||isset($tab['reset'])) $tab["enddate1"]="0000-00-00";
if (!isset($tab["date2"])||isset($tab['reset'])) $tab["date2"]="0000-00-00";
if (!isset($tab["enddate2"])||isset($tab['reset'])) $tab["enddate2"]="0000-00-00";
if (!isset($tab["field"])||isset($tab['reset'])) $tab["field"]="";
if (!isset($tab["only_computers"])||isset($tab['reset'])) $tab["only_computers"] = "";


if ($tab["date1"]!="0000-00-00"&&$tab["date2"]!="0000-00-00"&&strcmp($tab["date2"],$tab["date1"])<0){
	$tmp=$tab["date1"];
	$tab["date1"]=$tab["date2"];
	$tab["date2"]=$tmp;
}

if ($tab["enddate1"]!="0000-00-00"&&$tab["enddate2"]!="0000-00-00"&&strcmp($tab["enddate2"],$tab["enddate1"])<0){
	$tmp=$tab["enddate1"];
	$tab["enddate1"]=$tab["enddate2"];
	$tab["enddate2"]=$tmp;
}

if (isAdmin($_SESSION["glpitype"])&&isset($_POST["delete"])&&!empty($_POST["todel"])){
	$j=new Job;
	foreach ($_POST["todel"] as $key => $val){
		if ($val==1) $j->deleteInDB($key);
		}
	}

if (!$tab["extended"])
	searchFormTracking($tab["extended"],$_SERVER["PHP_SELF"],$tab["start"],$tab["status"],$tab["author"],$tab["assign"],$tab["assign_ent"],$tab["category"],$tab["priority"],$tab["item"],$tab["type"],$tab["showfollowups"],$tab["field2"],$tab["contains2"]);
else 
	searchFormTracking($tab["extended"],$_SERVER["PHP_SELF"],$tab["start"],$tab["status"],$tab["author"],$tab["assign"],$tab["assign_ent"],$tab["category"],$tab["priority"],$tab["item"],$tab["type"],$tab["showfollowups"],$tab["field2"],$tab["contains2"],$tab["field"],$tab["contains"],$tab["date1"],$tab["date2"],$tab["only_computers"],$tab["enddate1"],$tab["enddate2"]);

if (!$tab["extended"])
	showTrackingList($_SERVER["PHP_SELF"],$tab["start"],$tab["status"],$tab["author"],$tab["assign"],$tab["assign_ent"],$tab["category"],$tab["priority"],$tab["item"],$tab["type"],$tab["showfollowups"],$tab["field2"],$tab["contains2"]);
else 
	showTrackingList($_SERVER["PHP_SELF"],$tab["start"],$tab["status"],$tab["author"],$tab["assign"],$tab["assign_ent"],$tab["category"],$tab["priority"],$tab["item"],$tab["type"],$tab["showfollowups"],$tab["field2"],$tab["contains2"],$tab["field"],$tab["contains"],$tab["date1"],$tab["date2"],$tab["only_computers"],$tab["enddate1"],$tab["enddate2"]);

//showJobList($_SERVER["PHP_SELF"],$_SESSION["glpiID"],$_SESSION["tracking_show"],$_SESSION["tracking_contains"],"","",$_SESSION["tracking_start"],$_SESSION["tracking_device"],$_SESSION["tracking_category"],$_SESSION["tracking_containsID"],$_SESSION["tracking_desc"]);

commonFooter();
?>
