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
include ($phproot . "/glpi/includes_software.php");
include ($phproot . "/glpi/includes_computers.php");
include ($phproot . "/glpi/includes_tracking.php");
include ($phproot . "/glpi/includes_financial.php");
include ($phproot . "/glpi/includes_documents.php");
include ($phproot . "/glpi/includes_users.php");
include ($phproot . "/glpi/includes_links.php");
include ($phproot . "/glpi/includes_reservation.php");

if(isset($_GET)) $tab = $_GET;
if(empty($tab) && isset($_POST)) $tab = $_POST;
if(!isset($tab["ID"])) $tab["ID"] = "";
if(!isset($tab["withtemplate"])) $tab["withtemplate"] = "";
if(!isset($tab["search_software"])) $tab["search_software"] = "";

if (isset($_POST["add"]))
{
	checkAuthentication("admin");
	unset($_POST["search_software"]);

	$newID=addSoftware($_POST);
	logEvent($newID, "software", 4, "inventory", $_SESSION["glpiname"]." ".$lang["log"][20]." ".$_POST["name"].".");
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($tab["delete"]))
{
	checkAuthentication("admin");
	if (!empty($tab["withtemplate"]))
		deleteSoftware($tab,1);
	else deleteSoftware($tab);

	logEvent($tab["ID"], "software", 4, "inventory", $_SESSION["glpiname"]." ".$lang["log"][22]);
	if(!empty($tab["withtemplate"])) 
		glpi_header($cfg_install["root"]."/setup/setup-templates.php");
	 else 
	glpi_header($cfg_install["root"]."/software/");
}
else if (isset($_POST["restore"]))
{
	checkAuthentication("admin");
	restoreSoftware($_POST);
	logEvent($tab["ID"], "software", 4, "inventory", $_SESSION["glpiname"]." ".$lang["log"][23]);
	glpi_header($cfg_install["root"]."/software/");
}
else if (isset($tab["purge"]))
{
	checkAuthentication("admin");
	deleteSoftware($tab,1);
	logEvent($tab["ID"], "software", 4, "inventory", $_SESSION["glpiname"]." ".$lang["log"][24]);
	glpi_header($cfg_install["root"]."/software/");
}
else if (isset($_POST["update"]))
{
	checkAuthentication("admin");
	unset($_POST["search_software"]);
	updateSoftware($_POST);
	logEvent($_POST["ID"], "software", 4, "inventory", $_SESSION["glpiname"]." ".$lang["log"][21]);
	glpi_header($_SERVER['HTTP_REFERER']);
} 
else
{
	checkAuthentication("normal");

	if (!isset($_SESSION['glpi_onglet'])) $_SESSION['glpi_onglet']=1;
	if (isset($_GET['onglet'])) {
		$_SESSION['glpi_onglet']=$_GET['onglet'];
		//glpi_header($_SERVER['HTTP_REFERER']);
	}
	
	commonHeader($lang["title"][12],$_SERVER["PHP_SELF"]);
	
	$ci=new CommonItem();
	if ($ci->getFromDB(SOFTWARE_TYPE,$tab["ID"]))
	
	showSoftwareOnglets($_SERVER["PHP_SELF"]."?ID=".$tab["ID"], $tab["withtemplate"],$_SESSION['glpi_onglet'] );
	if (!empty($tab["withtemplate"])) {

		if (showSoftwareForm($_SERVER["PHP_SELF"],$tab["ID"],$tab['search_software'], $tab["withtemplate"])){
		
		if (!empty($tab["ID"])){
		switch($_SESSION['glpi_onglet']){
				case 4 :
					showInfocomForm($cfg_install["root"]."/infocoms/infocoms-info-form.php",SOFTWARE_TYPE,$tab["ID"],1,$tab["withtemplate"]);
					showContractAssociated(SOFTWARE_TYPE,$tab["ID"],$tab["withtemplate"]);
					break;
				case 5 :
					showDocumentAssociated(SOFTWARE_TYPE,$tab["ID"],$tab["withtemplate"]);
					break;
			}
		}
		
		}
		
	} else {


		if (isAdmin($_SESSION["glpitype"])&&isset($_POST["delete_inter"])&&!empty($_POST["todel"])){
			$j=new Job;
			foreach ($_POST["todel"] as $key => $val){
				if ($val==1) $j->deleteInDB($key);
				}
			}

		if (showSoftwareForm($_SERVER["PHP_SELF"],$tab["ID"],$tab['search_software'])){
			switch($_SESSION['glpi_onglet']){
				case -1:
					showLicensesAdd($tab["ID"]);
					showLicenses($tab["ID"]);
					showInfocomForm($cfg_install["root"]."/infocoms/infocoms-info-form.php",SOFTWARE_TYPE,$tab["ID"]);
					showContractAssociated(SOFTWARE_TYPE,$tab["ID"]);
					showDocumentAssociated(SOFTWARE_TYPE,$tab["ID"]);
					showJobListForItem($_SESSION["glpiname"],SOFTWARE_TYPE,$tab["ID"]);
					showOldJobListForItem($_SESSION["glpiname"],SOFTWARE_TYPE,$tab["ID"]);
					showLinkOnDevice(SOFTWARE_TYPE,$tab["ID"]);
					break;
				case 2 :
					showLicensesAdd($tab["ID"]);
					showLicenses($tab["ID"],1);
					break;
				case 4 :
					showInfocomForm($cfg_install["root"]."/infocoms/infocoms-info-form.php",SOFTWARE_TYPE,$tab["ID"]);
					showContractAssociated(SOFTWARE_TYPE,$tab["ID"]);
					break;
				case 5 :
					showDocumentAssociated(SOFTWARE_TYPE,$tab["ID"]);
					break;
				case 6 :
					showJobListForItem($_SESSION["glpiname"],SOFTWARE_TYPE,$tab["ID"]);
					showOldJobListForItem($_SESSION["glpiname"],SOFTWARE_TYPE,$tab["ID"]);
					break;
				case 7 :
					showLinkOnDevice(SOFTWARE_TYPE,$tab["ID"]);
					break;	
				case 10 :
					showNotesForm($_SERVER["PHP_SELF"],SOFTWARE_TYPE,$tab["ID"]);
					break;				
				case 11 :
					printDeviceReservations($_SERVER["PHP_SELF"],SOFTWARE_TYPE,$tab["ID"]);
					break;
				case 12 :
					showHistory(SOFTWARE_TYPE,$tab["ID"]);
				break;
				default :
					showLicensesAdd($tab["ID"]);
					showLicenses($tab["ID"]);
					break;
			}
		}
	}

	commonFooter();
}

?>
