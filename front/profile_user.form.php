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

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

Session::checkCentralAccess();

$profile = new Profile();
$right   = new Profile_User();
$user    = new User();

if (isset($_POST["add"])) {

   $right->check(-1,'w',$_POST);
   if ($right->add($_POST)) {
      Event::log($_POST["users_id"], "users", 4, "setup",
                 //TRANS: %s is the user login
                 sprintf(__('%s adds a user to an entity'), $_SESSION["glpiname"]));
   }
   Html::back();

} else if (isset($_POST["delete"])) {

   if (isset($_POST["item"]) && count($_POST["item"])) {
      foreach ($_POST["item"] as $key => $val) {
         if ($val == 1) {
            if ($right->can($key,'w')) {
               $right->delete(array('id' => $key));
            }
         }
      }
      if (isset($_POST["entities_id"])) {
         // From entity tab
         Event::log($_POST["entities_id"], "entity", 4, "setup",
                     //TRANS: %s is the user login
                     sprintf(__('%s deletes a user from an entity'), $_SESSION["glpiname"]));
      } else if (isset($_POST["users_id"])) {
         Event::log($_POST["users_id"], "users", 4, "setup",
                     //TRANS: %s is the user login
                     sprintf(__('%s deletes an entity from a user'), $_SESSION["glpiname"]));
      }
   }
   Html::back();

} else if (isset($_POST["moveentity"])) {
   if (isset($_POST['entities_id']) && ($_POST['entities_id'] >= 0)) {
      foreach ($_POST["item"] as $key => $val) {
         if ($val == 1) {
            if ($right->can($key,'w')) {
               $right->update(array('id'          => $key,
                                    'entities_id' => $_POST['entities_id']));
            }
         }
      }
   }
   Html::back();
}


Html::displayErrorAndDie("lost");
?>