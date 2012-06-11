<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2012 by the INDEPNET Development Team.

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
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

Session::checkCentralAccess();
Session::checkRight("reservation_central", "w");

$ri = new ReservationItem();

if (isset($_REQUEST["add"])) {
   if ($newID = $ri->add($_REQUEST)) {
      Event::log($newID, "reservationitem", 4, "inventory",
                 sprintf(__('%1$s adds the item %2$s (%3$d)'), $_SESSION["glpiname"],
                         $_REQUEST["itemtype"], $_REQUEST["items_id"]));
   }
   Html::back();

} else if (isset($_REQUEST["delete"])) {
   $ri->delete($_REQUEST);

   Event::log($_REQUEST['id'], "reservationitem", 4, "inventory",
              //TRANS: %s is the user login
              sprintf(__('%s deletes an item'), $_SESSION["glpiname"]));
   Html::back();

} else if (isset($_REQUEST["purge"])) {
   $ri->delete($_REQUEST, 1);

   Event::log($_REQUEST['id'], "reservationitem", 4, "inventory",
              //TRANS: %s is the user login
              sprintf(__('%s purges an item'), $_SESSION["glpiname"]));
   Html::back();

} else if (isset($_REQUEST["restore"])) {
   $ri->restore($_REQUEST);

   Event::log($_REQUEST['id'], "reservationitem", 4, "inventory",
              //TRANS: %s is the user login
              sprintf(__('%s retores an item'), $_SESSION["glpiname"]));
   Html::back();

} else if (isset($_REQUEST["update"])) {
   // from reservation form
   if (isset($_POST["id"])) {
      $_REQUEST = $_POST;
   } // else from object from
   $ri->update($_REQUEST);
   Event::log($_REQUEST['id'], "reservationitem", 4, "inventory",
              //TRANS: %s is the user login
              sprintf(__('%s updates an item'), $_SESSION["glpiname"]));
   Html::back();

} else {
   Html::header(Reservation::getTypeName(2), $_SERVER['PHP_SELF'], "utils", "reservation");
   $ri->showForm($_GET["id"]);
}

Html::footer();
?>