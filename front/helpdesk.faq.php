<?php
/*
 * @version $Id$
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
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------


$NEEDED_ITEMS = array ('document', 'knowbase', 'user');

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

// Redirect management
if (isset($_GET["redirect"])) {
   manageRedirect($_GET["redirect"]);
}

//*******************
// Affichage Module FAQ
//******************

$name = "";
checkFaqAccess();
if (isset($_SESSION["glpiID"])) {
   helpHeader($LANG['Menu'][20],$_SERVER['PHP_SELF'],$_SESSION["glpiname"]);
} else {
   $_SESSION["glpilanguage"] = $CFG_GLPI['language'];
   // Anonymous FAQ
   simpleHeader($LANG['Menu'][20],array($LANG['Menu'][20] => $_SERVER['PHP_SELF']));
}

if (!isset($_GET["start"])) {
   $_GET["start"] = 0;
}
if (!isset($_GET["contains"])) {
   $_GET["contains"] = "";
}
if (!isset($_GET["knowbaseitemscategories_id"])) {
   $_GET["knowbaseitemscategories_id"] = 0;
}

if (isset($_GET["id"])) {
   $kb = new kbItem;
   if ($kb->getFromDB($_GET["id"]) && ShowKbItemFull($_GET["id"],0)) {
      Document::showAssociated($kb);
   }

} else {
   searchFormKnowbase($_SERVER['PHP_SELF'],$_GET["contains"],$_GET["knowbaseitemscategories_id"],1);
   showKbCategoriesFirstLevel($_SERVER['PHP_SELF'],$_GET["knowbaseitemscategories_id"] ,1);
   showKbItemList($_SERVER['PHP_SELF'],$_GET["contains"],$_GET["start"],
                  $_GET["knowbaseitemscategories_id"],1);
   if (!$_GET["knowbaseitemscategories_id"] && strlen($_GET["contains"]) == 0) {
      showKbViewGlobal($_SERVER['PHP_SELF'],1) ;
   }
}

helpFooter();

?>