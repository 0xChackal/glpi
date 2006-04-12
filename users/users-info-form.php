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
include ($phproot . "/glpi/includes_profiles.php");


$user=new User();
if (empty($_GET["name"])&&isset($_GET["ID"])){
	
	$user->getFromDB($_GET["ID"]);
	glpi_header($cfg_glpi["root_doc"]."/users/users-info-form.php?name=".$user->fields['name']);
}

if(empty($_GET["name"])) $_GET["name"] = "";

if (isset($_POST["add"])) {
	checkAuthentication("admin");
	// Pas de nom pas d'ajout	
	if (!empty($_POST["name"])){
		$newID=$user->add($_POST);
		logEvent($newID, "users", 4, "setup", $_SESSION["glpiname"]." ".$lang["log"][20]." ".$_POST["name"].".");
	}
	glpi_header($_SERVER['HTTP_REFERER']);
} else if (isset($_POST["delete"])) {
	checkAuthentication("admin");
	$user->delete($_POST);
	logEvent(0,"users", 4, "setup", $_SESSION["glpiname"]."  ".$lang["log"][22]." ".$_POST["ID"].".");
	glpi_header($cfg_glpi["root_doc"]."/users/");
} else if (isset($_POST["update"])) {
	checkAuthentication("admin");
	commonHeader($lang["title"][13],$_SERVER["PHP_SELF"]);
	$user->update($_POST);
	logEvent(0,"users", 5, "setup", $_SESSION["glpiname"]."  ".$lang["log"][21]."  ".$_POST["name"].".");
	showUserform($_SERVER["PHP_SELF"],$_POST["name"]);
} else {
	
	if (!isset($_GET["ext_auth"])){
		checkAuthentication("admin");
		commonHeader($lang["title"][13],$_SERVER["PHP_SELF"]);
		showUserform($_SERVER["PHP_SELF"],$_GET["name"]);
	} else {
		if (isset($_GET['add_ext_auth'])){
			if (isset($_GET['login'])&&!empty($_GET['login'])){
				$user=new User();
				$user->fields["name"]=$_GET['login'];
				$user->fields["type"]=$_GET['type'];
				$user->addToDB(1);
			}
			glpi_header($_SERVER['HTTP_REFERER']);
		}
		checkAuthentication("admin");
		commonHeader($lang["title"][13],$_SERVER["PHP_SELF"]);
		showAddExtAuthUserForm($_SERVER["PHP_SELF"]);
	}
}
	


commonFooter();

?>
