<?php
/*
 * @version $Id: typedoc.form.php 8624 2009-08-04 12:45:43Z moyo $
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2009 by the INDEPNET Development Team.

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
// Original Author of file: Remi Collet
// Purpose of file:
// ----------------------------------------------------------------------


if (!($dropdown instanceof CommonDropdown)) {
   displayErrorAndDie('');
}

if (!isset($_GET["id"])) {
   $_GET["id"] = "";
}
checkTypeRight($dropdown->type, 'r');


if (isset($_POST["add"])) {
   $dropdown->check(-1,'w',$_POST);

   if ($newID=$dropdown->add($_POST)) {
      refreshMainWindow();
      logEvent($newID, "dropdown", 4, "setup",$_SESSION["glpiname"]." added ".$_POST["name"].".");
   }
   glpi_header($_SERVER['HTTP_REFERER']);

} else if (isset($_POST["delete"])) {
   $dropdown->check($_POST["id"],'w');
   $dropdown->delete($_POST,1);
   refreshMainWindow();

   logEvent($_POST["id"], "dropdown", 4, "setup", $_SESSION["glpiname"]." ".$LANG['log'][22]);
   glpi_header($dropdown->getSearchURL());

} else if (isset($_POST["update"])) {
   $dropdown->check($_POST["id"],'w');
   $dropdown->update($_POST);
   refreshMainWindow();

   logEvent($_POST["id"], "dropdown", 4, "setup", $_SESSION["glpiname"]." ".$LANG['log'][21]);
   glpi_header($_SERVER['HTTP_REFERER']);

} else if (isset($_POST["execute"])) {
   if (method_exists($dropdown, $_POST["_method"])) {
      call_user_func(array(&$dropdown,$_POST["_method"]),$_POST);
      glpi_header($_SERVER['HTTP_REFERER']);
   } else {
      displayErrorAndDie($LANG['common'][24]);
   }

} else if (isset($_GET['popup'])) {
   popHeader($dropdown->getTypeName(),$_SERVER['PHP_SELF']);
   if (isset($_GET["rand"])) {
      $_SESSION["glpipopup"]["rand"]=$_GET["rand"];
   }
   $dropdown->showForm($_SERVER['PHP_SELF'],$_GET["id"]);
   echo "<div class='center'><br><a href='javascript:window.close()'>".$LANG['buttons'][13]."</a>";
   echo "</div>";
   popFooter();

} else {
   commonHeader($dropdown->getTypeName(),$_SERVER['PHP_SELF'],"config","dropdowns",
                str_replace('glpi_','',$dropdown->table));
   $dropdown->showForm($_SERVER['PHP_SELF'],$_GET["id"]);
   commonFooter();
}

?>