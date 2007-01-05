<?php
/*
 *  @version $Id$
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

$NEEDED_ITEMS=array("user","tracking","reservation","document","knowbase","computer","printer","networking","peripheral","monitor","software","infocom","phone","enterprise");

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");


// Redirect management
if (isset($_GET['redirect'])){
	checkHelpdeskAccess();
	list($type,$ID)=split("_",$_GET["redirect"]);
	glpi_header($CFG_GLPI["root_doc"]."/front/helpdesk.public.php?show=user&ID=$ID");
}

//*******************
	// Affichage Module reservation 
	//******************
	checkRight("reservation_helpdesk","1");
	$rr=new ReservationResa();
	if (isset($_POST["edit_resa"])){
		list($begin_year,$begin_month,$begin_day)=split("-",$_POST["begin_date"]);
		$id_item=key($_POST["items"]);
		if ($_SESSION["glpiID"]==$_POST["id_user"]) 
			if ($rr->update($_POST,$_SERVER['PHP_SELF'],$id_item))
				glpi_header($CFG_GLPI["root_doc"]."/front/helpdesk.resa.php?show=resa&ID=".$id_item."&mois_courant=$begin_month&annee_courante=$begin_year");
			else exit();
	}

	helpHeader($LANG["title"][1],$_SERVER['PHP_SELF'],$_SESSION["glpiname"]);

	if (isset($_POST["clear_resa"])){
		$id_item=key($_POST["items"]);
		if ($rr->delete($_POST)){ // delete() need an array !
			logEvent($_POST["ID"], "reservation", 4, "inventory", $_SESSION["glpiname"]." delete a reservation.");
		}
		list($begin_year,$begin_month,$begin_day)=split("-",$_POST["begin_date"]);
		$_GET["mois_courant"]=$begin_month;
		$_GET["annee_courant"]=$begin_year;
		printCalendrier($_SERVER['PHP_SELF'],$id_item);

	}

	if (isset($_GET["ID"])){
		printCalendrier($_SERVER['PHP_SELF'],$_GET["ID"]);
	}
	else if (isset($_GET["add_item"])){
		if (!isset($_GET["date"])) $_GET["date"]=date("Y-m-d");
		showAddReservationForm($_SERVER['PHP_SELF'],$_GET["add_item"],$_GET["date"]);
	}
	else if (isset($_GET["edit"])){
		showAddReservationForm($_SERVER['PHP_SELF'],$_GET["edit_item"],"",$_GET["edit"]);
	}
	else if (isset($_POST["add_resa"])){
		$all_ok=true;
		$id_item=0;
		foreach ($_POST['items'] as $id_item){
			$_POST['id_item']=$id_item;
			$ok=true;
			$times=$_POST["periodicity_times"];
			list($begin_year,$begin_month,$begin_day)=split("-",$_POST["begin_date"]);
			list($end_year,$end_month,$end_day)=split("-",$_POST["end_date"]);
			$to_add=1;
			if ($_POST["periodicity"]=="week") $to_add=7;
			for ($i=1;$i<=$times&&$ok;$i++){
				$_POST["begin_date"]=date("Y-m-d",mktime(0,0,0,$begin_month,$begin_day+($i-1)*$to_add,$begin_year));
				$_POST["end_date"]=date("Y-m-d",mktime(0,0,0,$end_month,$end_day+($i-1)*$to_add,$end_year));
				if ($_SESSION["glpiID"]==$_POST["id_user"]) {
					unset($rr->fields["ID"]);
					$ok=$rr->add($_POST,$_SERVER['PHP_SELF'],$ok);
				}
	
			}
			// Positionnement du calendrier au mois de debut
			$_GET["mois_courant"]=$begin_month;
			$_GET["annee_courant"]=$begin_year;
	
			if ($ok){
				logEvent($_POST["id_item"], "reservation", 4, "inventory", $_SESSION["glpiname"]." add a reservation.");
			} else $all_ok=false;
		}

		if ($all_ok){
			// Several reservations
			if (count($_POST['items'])>1){
				glpi_header($CFG_GLPI["root_doc"] . "/front/helpdesk.resa.php?ID=");
			} else { // Only one reservation
				glpi_header($CFG_GLPI["root_doc"] . "/front/helpdesk.resa.php?ID=$id_item");
			}
		}
	}
	else {
		printReservationItems($_SERVER['PHP_SELF']);
	}

//*******************
// fin  Affichage Module reservation 
//*******************
helpFooter();


?>