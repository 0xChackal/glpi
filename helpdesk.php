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
include ($phproot . "/glpi/includes_reservation.php");
include ($phproot . "/glpi/includes_knowbase.php");
include ($phproot . "/glpi/includes_documents.php");
include ($phproot . "/glpi/includes_computers.php");
include ($phproot . "/glpi/includes_printers.php");
include ($phproot . "/glpi/includes_networking.php");
include ($phproot . "/glpi/includes_peripherals.php");
include ($phproot . "/glpi/includes_monitors.php");
include ($phproot . "/glpi/includes_software.php");
include ($phproot . "/glpi/includes_financial.php");
include ($phproot . "/glpi/includes_phones.php");


// Redirect management
if (isset($_GET['redirect'])){
	checkHelpdeskAccess();
	list($type,$ID)=split("_",$_GET["redirect"]);
	glpi_header($cfg_glpi["root_doc"]."/helpdesk.php?show=user&ID=$ID");
}

if (isset($_GET["show"]) && strcmp($_GET["show"],"user") == 0)
{

	checkHelpdeskAccess();
	//*******************
	// Affichage interventions en cours
	//******************
	if (isset($_POST['add'])&&haveRight("comment_ticket","1")) {
		$fup=new Followup();
		$newID=$fup->add($_POST);

		logEvent($_POST["tracking"], "tracking", 4, "tracking", $_SESSION["glpiname"]." ".$lang["log"][20]." $newID.");
		glpi_header($_SERVER['HTTP_REFERER']);
//		if ($_SESSION["glpitype"]="normal")
//			glpi_header($cfg_glpi["root_doc"]."/tracking/tracking-info-form.php?ID=".$_POST["tracking"]);
//		else 
//			glpi_header($cfg_glpi["root_doc"]."/helpdesk.php?show=user&ID=".$_POST["tracking"]);
	}	
	if (!isset($_GET["start"])) $_GET["start"]=0;

	helpHeader($lang["title"][1],$_SERVER["PHP_SELF"],$_SESSION["glpiname"]);

	if (!isset($_GET["ID"])) {
		showTrackingList($_SERVER["PHP_SELF"],0,"all",$_SESSION["glpiID"]);
		//showJobList($_SERVER["PHP_SELF"],$_SESSION["glpiID"],$_GET["show"],"","","",$_GET["start"]);
	}
	else {
		 showJobDetails($_GET["ID"]);
		 showFollowupsSummary($_GET["ID"]);
	}
}
elseif (isset($_POST["clear_resa"])||isset($_POST["edit_resa"])||isset($_POST["add_resa"])||(isset($_GET["show"]) && strcmp($_GET["show"],"resa") == 0)){
	
	//*******************
	// Affichage Module r�servation 
	//******************
	checkRight("reservation_helpdesk","1");
	$rr=new ReservationResa();
	if (isset($_POST["edit_resa"])){
		list($begin_year,$begin_month,$begin_day)=split("-",$_POST["begin_date"]);
		list($end_year,$end_month,$end_day)=split("-",$_POST["end_date"]);
		$_POST["begin"]=date("Y-m-d H:i:00",mktime($_POST["begin_hour"],$_POST["begin_min"],0,$begin_month,$begin_day,$begin_year));
		$_POST["end"]=date("Y-m-d H:i:00",mktime($_POST["end_hour"],$_POST["end_min"],0,$end_month,$end_day,$end_year));
		unset($_POST["begin_date"]);unset($_POST["begin_hour"]);unset($_POST["begin_min"]);
		unset($_POST["end_date"]);unset($_POST["end_hour"]);unset($_POST["end_min"]);
		$item=$_POST["id_item"];
		unset($_POST["edit_resa"]);unset($_POST["id_item"]);
		if ($_SESSION["glpiID"]==$_POST["id_user"]) // test S�curit�
		if ($rr->update($_POST,$_SERVER["PHP_SELF"],$item))
			glpi_header($cfg_glpi["root_doc"]."/helpdesk.php?show=resa&ID=$item&mois_courant=$begin_month&annee_courante=$begin_year");
		else exit();			
	}

	helpHeader($lang["title"][1],$_SERVER["PHP_SELF"],$_SESSION["glpiname"]);
	
	if (isset($_POST["clear_resa"])){
		if ($rr->delete($_POST["ID"])){
			logEvent($_POST["ID"], "reservation", 4, "inventory", $_SESSION["glpiname"]."delete a reservation.");
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
			if ($_SESSION["glpiID"]==$_POST["id_user"]) 
			$ok=$rr->add($_POST,$_SERVER["PHP_SELF"],$ok);

		}
		// Positionnement du calendrier au mois de debut
		$_GET["mois_courant"]=$begin_month;
		$_GET["annee_courant"]=$begin_year;
		
		if ($ok){
			logEvent($_POST["id_item"], "reservation", 4, "inventory", $_SESSION["glpiname"]." add a reservation.");
			printCalendrier($_SERVER["PHP_SELF"],$_POST["id_item"]);
		}
	}
	else {
		printReservationItems($_SERVER["PHP_SELF"]);
	}
}
//*******************
// fin  Affichage Module r�servation 
//*******************


//*******************
// Affichage Module FAQ
//******************



else if (isset($_GET["show"]) && strcmp($_GET["show"],"faq") == 0){
	$name="";
	checkRight("faq","r");
	helpHeader($lang["title"][1],$_SERVER["PHP_SELF"],$_SESSION["glpiname"]);

	
	if (isset($_GET["ID"])){
	
	ShowKbItemFull($_GET["ID"],"no");
	showDocumentAssociated(KNOWBASE_TYPE,$_GET["ID"],3);
	
	} else {
	initExpandSessionVar();

	if (isset($_GET["toshow"])) {
		if ($_GET["toshow"]=="all")
			ExpandSessionVarShowAll();
		else ExpandSessionVarShow($_GET["toshow"]);
	}
	if (isset($_GET["tohide"])) {
		if ($_GET["tohide"]=="all")
			ExpandSessionVarHideAll();
		else ExpandSessionVarHide($_GET["tohide"]);
	}
	if (isset($_POST["contains"])) $contains=$_POST["contains"];
	else $contains="";

	if (isset($_POST["contains"])) searchLimitSessionVarKnowbase($_POST["contains"]);

	
	faqShowCategoriesall($_SERVER["PHP_SELF"]."?show=faq",$contains);
	}
}
//*******************
//  fin Affichage Module FAQ
//******************


else {
checkHelpdeskAccess();
helpHeader($lang["title"][1],$_SERVER["PHP_SELF"],$_SESSION["glpiname"]);

printHelpDesk($_SESSION["glpiID"],1);
}

helpFooter();

?>
