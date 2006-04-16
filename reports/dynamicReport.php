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
include ($phproot . "/glpi/includes_consumables.php");
include ($phproot . "/glpi/includes_cartridges.php");
include ($phproot . "/glpi/includes_state.php");
include ($phproot . "/glpi/includes_users.php");
include ($phproot . "/glpi/includes_computers.php");
include ($phproot . "/glpi/includes_printers.php");
include ($phproot . "/glpi/includes_monitors.php");
include ($phproot . "/glpi/includes_peripherals.php");
include ($phproot . "/glpi/includes_networking.php");
include ($phproot . "/glpi/includes_tracking.php");
include ($phproot . "/glpi/includes_financial.php");
include ($phproot . "/glpi/includes_phones.php");

checkCentralAccess("normal");
include ($phproot . "/glpi/includes_search.php");
//print_r($_GET);
if (isset($_GET["item_type"])&&isset($_GET["display_type"])){


	if ($_GET["display_type"]<0) {
		$_GET["display_type"]=-$_GET["display_type"];
		$_GET["export_all"]=1;
	}

	// PDF case
	if ($_GET["display_type"]==2)
		include ($phproot . "/glpi/includes_ezpdf.php");

	switch ($_GET["item_type"]){
	case STATE_TYPE :
		showStateItemList($_SERVER["PHP_SELF"],$_SESSION["glpiname"],$_GET["field"],$_GET["phrasetype"],$_GET["contains"],$_GET["sort"],$_GET["order"],$_GET["start"],$_GET["state"]);
		break;
	case TRACKING_TYPE :
		showTrackingList($_SERVER["PHP_SELF"],$_GET["start"],$_GET["status"],$_GET["author"],$_GET["assign"],$_GET["assign_ent"],$_GET["category"],$_GET["priority"],$_GET["item"],$_GET["type"],$_GET["showfollowups"],$_GET["field2"],$_GET["contains2"],$_GET["field"],$_GET["contains"],$_GET["date1"],$_GET["date2"],$_GET["only_computers"],$_GET["enddate1"],$_GET["enddate2"]);		
		break;

	default :
		manageGetValuesInSearch($_GET["item_type"]);

		showList($_GET["item_type"],$_SERVER["PHP_SELF"],$_GET["field"],$_GET["contains"],$_GET["sort"],$_GET["order"],$_GET["start"],$_GET["deleted"],$_GET["link"],$_GET["distinct"],$_GET["link2"],$_GET["contains2"],$_GET["field2"],$_GET["type2"]);
		break;
	}
}
?>