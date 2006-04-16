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
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

include ("_relpos.php");
include ($phproot . "/glpi/includes.php");
include ($phproot . "/glpi/includes_users.php");
include ($phproot . "/glpi/includes_computers.php");
include ($phproot . "/glpi/includes_printers.php");
include ($phproot . "/glpi/includes_monitors.php");
include ($phproot . "/glpi/includes_peripherals.php");
include ($phproot . "/glpi/includes_networking.php");
include ($phproot . "/glpi/includes_software.php");
include ($phproot . "/glpi/includes_reservation.php");
include ($phproot . "/glpi/includes_phones.php");

if(isset($_GET)) $tab = $_GET;
if(empty($tab) && isset($_POST)) $tab = $_POST;
if(!isset($tab["ID"])) $tab["ID"] = "";

$ri=new ReservationItem();
$rr=new ReservationResa();

if (isset($_POST["clear_resa"])||isset($_POST["add_resa"])||isset($_POST["edit_resa"])||(isset($_GET["show"]) && strcmp($_GET["show"],"resa") == 0)){
	checkRight("reservation_helpdesk","1");

	if (isset($_POST["edit_resa"])){
		list($begin_year,$begin_month,$begin_day)=split("-",$_POST["begin_date"]);
		list($end_year,$end_month,$end_day)=split("-",$_POST["end_date"]);
		$_POST["begin"]=date("Y-m-d H:i:00",mktime($_POST["begin_hour"],$_POST["begin_min"],0,$begin_month,$begin_day,$begin_year));
		$_POST["end"]=date("Y-m-d H:i:00",mktime($_POST["end_hour"],$_POST["end_min"],0,$end_month,$end_day,$end_year));
		
		unset($_POST["begin_date"]);unset($_POST["begin_hour"]);unset($_POST["begin_min"]);
		unset($_POST["end_date"]);unset($_POST["end_hour"]);unset($_POST["end_min"]);
		$item=$_POST["id_item"];
		unset($_POST["edit_resa"]);unset($_POST["id_item"]);
		if (haveRight("reservaration_central","w")||$_SESSION["glpiID"]==$_POST["id_user"]) 
		if ($rr->update($_POST,$_SERVER["PHP_SELF"],$item))
			glpi_header($cfg_glpi["root_doc"]."/reservation/index.php?show=resa&ID=$item&mois_courant=$begin_month&annee_courante=$begin_year");
	}


	commonHeader($lang["title"][35],$_SERVER["PHP_SELF"]);

	if (isset($_POST["clear_resa"])){
		if ($rr->delete($_POST["ID"])){
			logEvent($_POST["ID"], "reservation", 4, "inventory", $_SESSION["glpiname"]." ".$lang["log"][22]);
		}
		list($begin_year,$begin_month,$begin_day)=split("-",$_POST["begin_date"]);
		$_GET["mois_courant"]=$begin_month;
		$_GET["annee_courant"]=$begin_year;
		printCalendrier($_SERVER["PHP_SELF"],$_POST["id_item"]);
	}



	if (isset($_GET["ID"])){
		printCalendrier($_SERVER["PHP_SELF"],$_GET["ID"]);
	}
	else if (isset($_GET["add"])){
		showAddReservationForm($_SERVER["PHP_SELF"],$_GET["add"],$_GET["date"]);
	}
	else if (isset($_GET["edit"])){
		showAddReservationForm($_SERVER["PHP_SELF"],$_GET["item"],"",$_GET["edit"]);
	}
	else if (isset($_POST["add_resa"])){
		$ok=true;
		$times=$_POST["periodicity_times"];
		list($begin_year,$begin_month,$begin_day)=split("-",$_POST["begin_date"]);
		list($end_year,$end_month,$end_day)=split("-",$_POST["end_date"]);
		$to_add=1;
		if ($_POST["periodicity"]=="week") $to_add=7;
		for ($i=1;$i<=$times&&$ok;$i++){
			$_POST["begin_date"]=date("Y-m-d",mktime(0,0,0,$begin_month,$begin_day+($i-1)*$to_add,$begin_year));
			$_POST["end_date"]=date("Y-m-d",mktime(0,0,0,$end_month,$end_day+($i-1)*$to_add,$end_year));
			if (haveRight("reservation_central","w")||$_SESSION["glpiID"]==$_POST["id_user"]) 
			$ok=$rr->add($_POST,$_SERVER["PHP_SELF"],$ok);

		}
		// Positionnement du calendrier au mois de debut
		$_GET["mois_courant"]=$begin_month;
		$_GET["annee_courant"]=$begin_year;
		
		if ($ok){
			logEvent($_POST["id_item"], "reservation", 4, "inventory", $_SESSION["glpiname"]." ".$lang["log"][20]);
			printCalendrier($_SERVER["PHP_SELF"],$_POST["id_item"]);
		}
	}
	else {
		printReservationItems($_SERVER["PHP_SELF"]);
	}
}
else {
	checkRight("reservation_central","r");
	if ($_SESSION["glpitype"]=="normal"){
		commonHeader($lang["title"][9],$_SERVER["PHP_SELF"]);
		printReservationItems($_SERVER["PHP_SELF"]);
	}
	// On est pas normal -> admin ou super-admin
	else {
	if (isset($_GET["add"]))
	{
		checkRight("reservation_central","w");
		$ri->add($_GET);
		logEvent(0, "reservation", 4, "inventory", $_SESSION["glpiname"]." ".$lang["log"][20]." ".$_GET["device_type"]."-".$_GET["id_device"].".");
		glpi_header($_SERVER['HTTP_REFERER']);
	} 
	else if (isset($_GET["delete"]))
	{
		checkRight("reservation_central","w");
		$ri->delete($_GET);
		logEvent(0, "reservation", 4, "inventory", $_SESSION["glpiname"]." ".$lang["log"][22]);
		glpi_header($_SERVER['HTTP_REFERER']);
	}


	if (isset($_POST["updatecomment"]))
	{
		checkRight("reservation_central","w");
		$ri->update($_POST);
		logEvent(0, "reservation", 4, "inventory", $_SESSION["glpiname"]." ".$lang["log"][21]);
	} 

	if(!isset($_GET["start"])) $_GET["start"] = 0;
	if (!isset($_GET["order"])) $_GET["order"] = "ASC";
	if (!isset($_GET["field"])) $_GET["field"] = "glpi_reservation_item.ID";
	if (!isset($_GET["phrasetype"])) $_GET["phrasetype"] = "contains";
	if (!isset($_GET["contains"])) $_GET["contains"] = "";
	if (!isset($_GET["sort"])) $_GET["sort"] = "glpi_reservation_item.ID";


	checkRight("reservation_central","r");

	commonHeader($lang["title"][35],$_SERVER["PHP_SELF"]);
	if (isset($_GET["comment"])){
		if (showReservationCommentForm($_SERVER["PHP_SELF"],$_GET["comment"])){
			}
		else {
			
			titleReservation();
			searchFormReservationItem($_GET["field"],$_GET["phrasetype"],$_GET["contains"],$_GET["sort"]);
			showReservationItemList($_SERVER["PHP_SELF"],$_SESSION["glpiname"],$_GET["field"],$_GET["phrasetype"],$_GET["contains"],$_GET["sort"],$_GET["order"],$_GET["start"]);
		}
		
	}else {
	
	titleReservation();
	searchFormReservationItem($_GET["field"],$_GET["phrasetype"],$_GET["contains"],$_GET["sort"]);
	showReservationItemList($_SERVER["PHP_SELF"],$_SESSION["glpiname"],$_GET["field"],$_GET["phrasetype"],$_GET["contains"],$_GET["sort"],$_GET["order"],$_GET["start"]);
	}
}
}
commonFooter();


?>