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


$NEEDED_ITEMS = array('computer', 'enterprise', 'entity', 'group', 'ldap', 'monitor',
   'networking', 'peripheral', 'phone', 'printer', 'profile', 'reservation', 'rulesengine',
   'rule.right', 'setup', 'software', 'tracking', 'user');

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

if(empty($_GET["id"])) $_GET["id"] = "";

if (!isset($_GET["start"])) {
   $_GET["start"]=0;
}

if (!isset($_GET["sort"])) {
   $_GET["sort"]="";
}
if (!isset($_GET["order"])) {
   $_GET["order"]="";
}


$user = new User();
$groupuser = new GroupUser();

if (empty($_GET["id"])&&isset($_GET["name"])) {

   $user->getFromDBbyName($_GET["name"]);
   glpi_header($CFG_GLPI["root_doc"]."/front/user.form.php?id=".$user->fields['id']);
}

if(empty($_GET["name"])) {
   $_GET["name"] = "";
}

if (isset($_POST["add"])) {
   $user->check(-1,'w',$_POST);

   // Pas de nom pas d'ajout
   if (!empty($_POST["name"]) && $newID=$user->add($_POST)) {
      logEvent($newID, "users", 4, "setup", $_SESSION["glpiname"]." ".$LANG['log'][20]." ".$_POST["name"].".");
   }
   glpi_header($_SERVER['HTTP_REFERER']);

} else if (isset($_POST["delete"])) {
   $user->check($_POST['id'],'w',$_POST);
   $user->delete($_POST);
   logEvent(0,"users", 4, "setup", $_SESSION["glpiname"]."  ".$LANG['log'][22]." ".$_POST["id"].".");
   glpi_header($CFG_GLPI["root_doc"]."/front/user.php");

} else if (isset($_POST["restore"])) {
   $user->check($_POST['id'],'w',$_POST);
   $user->restore($_POST);
   logEvent($_POST["id"],"users", 4, "setup", $_SESSION["glpiname"]." ".$LANG['log'][23]);
   glpi_header($CFG_GLPI["root_doc"]."/front/user.php");

} else if (isset($_POST["purge"])) {
   $user->check($_POST['id'],'w',$_POST);
   $user->delete($_POST,1);
   logEvent($_POST["id"], "users", 4, "setup", $_SESSION["glpiname"]." ".$LANG['log'][24]);
   glpi_header($CFG_GLPI["root_doc"]."/front/user.php");

} else if (isset ($_POST["force_ldap_resynch"])) {
   checkSeveralRightsAnd(array("user"=>"w", "user_authtype"=>"w"));

   $user->getFromDB($_POST["id"]);
   ldapImportUserByServerId($user->fields["name"],true,$user->fields["auths_id"],true);
   glpi_header($_SERVER['HTTP_REFERER']);

} else if (isset($_POST["update"])) {
   $user->check($_POST['id'],'w',$_POST);
   $user->update($_POST);
   logEvent(0,"users", 5, "setup", $_SESSION["glpiname"]."  ".$LANG['log'][21]."  ".$_POST["name"].".");
   glpi_header($_SERVER['HTTP_REFERER']);

} else if (isset($_POST["addgroup"])) {
   $groupuser->check(-1,'w',$_POST);
   $groupuser->add($_POST);
   logEvent($_POST["users_id"], "users", 4, "setup", $_SESSION["glpiname"]." ".$LANG['log'][48]);
   glpi_header($_SERVER['HTTP_REFERER']);

} else if (isset($_POST["deletegroup"])) {
   checkRight("user","w");
   if (count($_POST["item"]))
      foreach ($_POST["item"] as $key => $val)
         if ($groupuser->can($key,'w')) {
            deleteUserGroup($key);
         }
   logEvent($_POST["users_id"], "users", 4, "setup", $_SESSION["glpiname"]." ".$LANG['log'][49]);
   glpi_header($_SERVER['HTTP_REFERER']);

} else if (isset($_POST["addright"])) {
   checkRight("user","w");

   $prof=new Profile();
   if ($prof->currentUserHaveMoreRightThan(array($_POST['profiles_id']=>$_POST['profiles_id']))) {
      addUserProfileEntity($_POST);
      logEvent($_POST["users_id"], "users", 4, "setup", $_SESSION["glpiname"]." ".$LANG['log'][61]);
   }

   glpi_header($_SERVER['HTTP_REFERER']);

} else if (isset($_POST["deleteright"])) {
   checkRight("user","w");

   if (isset($_POST["item"])&&count($_POST["item"])) {
      foreach ($_POST["item"] as $key => $val) {
         deleteUserProfileEntity($key);
      }
      logEvent($_POST["users_id"], "users", 4, "setup", $_SESSION["glpiname"]." ".$LANG['log'][62]);
   }
   glpi_header($_SERVER['HTTP_REFERER']);

} else if (isset($_POST["switch_auth_internal"])) {
   checkSeveralRightsAnd(array("user"=>"w", "user_authtype"=>"w"));

   $user = new User;
   $input["id"]=$_POST["id"];
   $input["authtype"]=AUTH_DB_GLPI;
   $input["auths_id"]=0;
   $user->update($input);
   glpi_header($_SERVER['HTTP_REFERER']);

} elseif (isset($_POST["switch_auth_ldap"])) {
   checkSeveralRightsAnd(array("user"=>"w", "user_authtype"=>"w"));

   $user = new User;
   $input["id"]=$_POST["id"];
   $input["authtype"]=AUTH_LDAP;
   $input["auths_id"]=$_POST["auths_id"];
   $user->update($input);
   glpi_header($_SERVER['HTTP_REFERER']);

} elseif (isset($_POST["switch_auth_mail"])) {
   checkSeveralRightsAnd(array("user"=>"w", "user_authtype"=>"w"));

   $user = new User;
   $input["id"]=$_POST["id"];
   $input["authtype"]=AUTH_MAIL;
   $input["auths_id"]=$_POST["auths_id"];
   $user->update($input);
   glpi_header($_SERVER['HTTP_REFERER']);

} else {

   if (!isset($_GET["ext_auth"])) {
      checkRight("user","r");

      commonHeader($LANG['title'][13],$_SERVER['PHP_SELF'],"admin","user");

      $user->showForm($_SERVER['PHP_SELF'],$_GET["id"]);

      commonFooter();
   } else {
      checkRight("user","w");

      if (isset($_GET['add_ext_auth_ldap'])) {
         if (isset($_GET['login']) && !empty($_GET['login'])) {
            import_user_from_ldap_servers($_GET['login']);
         }
         glpi_header($_SERVER['HTTP_REFERER']);
      }
      if (isset($_GET['add_ext_auth_simple'])) {
         if (isset($_GET['login']) && !empty($_GET['login'])) {
            $newID=$user->add(array('name'=>$_GET['login'],'_extauth'=>1,'add'=>1));
            logEvent($newID, "users", 4, "setup", $_SESSION["glpiname"]." ".$LANG['log'][20]." ".$_GET['login'].".");
         }
         glpi_header($_SERVER['HTTP_REFERER']);
      }

      commonHeader($LANG['title'][13],$_SERVER['PHP_SELF'],"admin","user");
      showAddExtAuthUserForm($_SERVER['PHP_SELF']);
      commonFooter();
   }
}

?>
