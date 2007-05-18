<?php


/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2007 by the INDEPNET Development Team.

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
// And Marco Gaiarin for ldap features 

if (!defined('GLPI_ROOT')) {
	die("Sorry. You can't access directly to this file");
}

class User extends CommonDBTM {

	var $fields = array ();

	function User() {
		global $CFG_GLPI;

		$this->table = "glpi_users";
		$this->type = USER_TYPE;

		$this->fields['tracking_order'] = 0;
		if (isset ($CFG_GLPI["default_language"])){
			$this->fields['language'] = $CFG_GLPI["default_language"];
		} else {
			$this->fields['language'] = "en_GB";
		}

	}
	function defineOnglets($withtemplate) {
		global $LANG;


		$ong[1] = $LANG["title"][26]; // principal

		$ong[4]=$LANG["Menu"][36];

		$ong[2] = $LANG["common"][1]; // materiel
		if (haveRight("show_ticket", "1"))
			$ong[3] = $LANG["title"][28]; // tickets
		if (haveRight("reservation_central", "r"))
			$ong[11] = $LANG["title"][35];
		if (haveRight("user", "w"))
			$ong[12] = $LANG["ldap"][12];

		return $ong;
	}
	function cleanDBonMarkDeleted($ID) {

		global $DB;

		$query = "DELETE FROM glpi_users_profiles WHERE (FK_users = '$ID')";
		$DB->query($query);

	}

	function cleanDBonPurge($ID) {

		global $DB;

		// Tracking items left?
		$query3 = "UPDATE glpi_tracking SET assign = '' WHERE (assign = '$ID')";
		$DB->query($query3);

		$query = "DELETE from glpi_users_groups WHERE FK_users = '$ID'";
		$DB->query($query);

	}

	function getFromDBbyName($name) {
		global $DB;
		$query = "SELECT * FROM glpi_users WHERE (name = '" . $name . "')";
		if ($result = $DB->query($query)) {
			if ($DB->numrows($result) != 1) {
				return false;
			}
			$this->fields = $DB->fetch_assoc($result);
			if (is_array($this->fields) && count($this->fields)) {
				return true;
			} else {
				return false;
			}
		}
		return false;
	}

	function prepareInputForAdd($input) {
		global $CFG_GLPI;
		//We add the user, we set the last modification date
		$input["date_mod"]=$_SESSION["glpi_currenttime"];
		// Preferences
		if (!isset($input["language"])){
			$input["language"]=$CFG_GLPI["default_language"];
		}
		
		// Add User, nasty hack until we get PHP4-array-functions
		if (isset ($input["password"])) {
			if (empty ($input["password"])) {
				unset ($input["password"]);
			} else {
				$input["password_md5"] = md5(unclean_cross_side_scripting_deep($input["password"]));
				$input["password"] = "";
			}
		}
		if (isset ($input["_extauth"])) {
			$input["password"] = "";
			$input["password_md5"] = "";
		}
		// change email_form to email (not to have a problem with preselected email)
		if (isset ($input["email_form"])) {
			$input["email"] = $input["email_form"];
			unset ($input["email_form"]);
		}

		return $input;
	}

	function post_addItem($newID, $input) {
		$prof = new Profile();

		$input["ID"]=$newID;

		$this->syncLdapGroups($input);
		$this->applyRightRules($input);
	}

	function pre_deleteItem($ID) {
		global $LANG;
		if ($ID == 1) {
			echo "<script language=\"JavaScript\" type=\"text/javascript\">";
			echo "alert('" . addslashes($LANG["setup"][220]) . "');";
			echo "</script>";
			glpi_header($_SERVER['HTTP_REFERER']);
			exit ();
		}

	}

	function prepareInputForUpdate($input) {
		global  $LANG;

		if (isset ($input["ID"]) && $input["ID"] == 1) {
			echo "<script language=\"JavaScript\" type=\"text/javascript\">";
			echo "alert('" . addslashes($LANG["setup"][220]) . "');";
			echo "</script>";
			glpi_header($_SERVER['HTTP_REFERER']);
			exit ();
		}
		if (isset ($input["password"])) {
			if (empty ($input["password"])) {
				unset ($input["password"]);
			} else {
				$input["password_md5"] = md5(unclean_cross_side_scripting_deep($input["password"]));
				$input["password"] = "";
			}
		}

		// change email_form to email (not to have a problem with preselected email)
		if (isset ($input["email_form"])) {
			$input["email"] = $input["email_form"];
			unset ($input["email_form"]);
		}

		// Update User in the database
		if (!isset ($input["ID"]) && isset ($input["name"])) {
			if ($this->getFromDBbyName($input["name"]))
				$input["ID"] = $this->fields["ID"];
		}

		if (isset ($_SESSION["glpiID"]) && isset ($input["language"]) && $_SESSION["glpiID"] == $input['ID']) {
			$_SESSION["glpilanguage"] = $input["language"];
		}
		if (isset ($_SESSION["glpiID"]) && isset ($input["tracking_order"]) && $_SESSION["glpiID"] == $input['ID']) {
			$_SESSION["glpitracking_order"] = $input["tracking_order"];
		}

		// Security system execpt for login update
		if (isset ($_SESSION["glpiID"]) && !haveRight("user", "w") && !ereg("login.php", $_SERVER['PHP_SELF'])) {
			if ($_SESSION["glpiID"] == $input['ID']) {
				$ret = $input;
				// extauth ldap case
				if ($_SESSION["glpiextauth"] && $input["auth_method"] != AUTH_LDAP) {
					foreach ($input['ldap_fields'] as $key => $val){
						if (!empty ($val)){
							unset ($ret[$key]);
						}
					}
				}
				// extauth imap case
				if (isset($input["auth_method"])&&$input["auth_method"] == AUTH_MAIL){
					unset ($ret["email"]);
					}

				unset ($ret["active"]);
				unset ($ret["comments"]);
				return $ret;
			} else {
				return array ();
			}
		}

		$this->syncLdapGroups($input);

		$this->applyRightRules($input);

		return $input;
	}

	

	function post_updateItem($input, $updates, $history) {
		// Clean header cache for the user
		if (in_array("language", $updates) && isset ($input["ID"])) {
			cleanCache("GLPI_HEADER_".$input["ID"]);
		}
	}

	// SPECIFIC FUNCTIONS
	function applyRightRules($input){
		global $DB;
		if (isset($input["auth_method"])&&($input["auth_method"] == AUTH_LDAP || $input["auth_method"]== AUTH_MAIL))
		if (isset ($input["ID"]) &&$input["ID"]>0&& isset ($input["_ldap_rules"]) && count($input["_ldap_rules"])) {

			//TODO : do not erase all the dynamic rights, but compare it with the ones in DB
			//and add/update/delete only if it's necessary !
			if (isset($input["_ldap_rules"]["rules_entities_rights"]))
				$entities_rules = $input["_ldap_rules"]["rules_entities_rights"];
			else
				$entities_rules = array();
	
			if (isset($input["_ldap_rules"]["rules_entities"]))
				$entities = $input["_ldap_rules"]["rules_entities"];
			else 
				$entities = array();
				
			if (isset($input["_ldap_rules"]["rules_rights"]))
				$rights = $input["_ldap_rules"]["rules_rights"];
			else
				$rights = array();
			
			//purge dynamic rights
			$this->purgeDynamicProfiles();
			
			//For each affectation -> write it in DB		
			foreach($entities_rules as $entity)
			{
				$affectation["FK_entities"] = $entity[0];
				$affectation["FK_profiles"] = $entity[1];
				$affectation["recursive"] = $entity[2];
				$affectation["FK_users"] = $input["ID"];
				$affectation["dynamic"] = 1;
				addUserProfileEntity($affectation);
			}
	
			foreach($entities as $entity)
			{
					foreach($rights as $right)
					{
						$affectation["FK_entities"] = $entity[0];
						$affectation["FK_profiles"] = $right;
						$affectation["FK_users"] = $input["ID"];
						$affectation["recursive"] = $entity[1];
						$affectation["dynamic"] = 1;
						addUserProfileEntity($affectation);
					}
			}
			
			//Unset all the temporary tables
			unset($input["_ldap_rules"]);
		}

	}
	function syncLdapGroups($input){
		global $DB;
		if (isset($input["auth_method"])&&$input["auth_method"]==AUTH_LDAP)
		if (isset ($input["ID"]) && $input["ID"]>0 && isset ($input["_groups"]) && count($input["_groups"])) {
			$auth_method = $this->getAuthMethodsByID($input["auth_method"], $input["id_auth"]);
			if (count($auth_method)){
				$WHERE = "";
				switch ($auth_method["ldap_search_for_groups"]) {
					case 0 : // user search
						$WHERE = "AND (glpi_groups.ldap_field <> '' AND glpi_groups.ldap_field IS NOT NULL AND glpi_groups.ldap_value<>'' AND glpi_groups.ldap_value IS NOT NULL )";
						break;
					case 1 : // group search
						$WHERE = "AND (ldap_group_dn<>'' AND ldap_group_dn IS NOT NULL )";
						break;
					case 2 : // user+ group search
						$WHERE = "AND ((glpi_groups.ldap_field <> '' AND glpi_groups.ldap_field IS NOT NULL AND glpi_groups.ldap_value<>'' AND glpi_groups.ldap_value IS NOT NULL) 
																			OR (ldap_group_dn<>'' AND ldap_group_dn IS NOT NULL) )";
						break;
				}
	
				// Delete not available groups like to LDAP
				$query = "SELECT glpi_users_groups.ID, glpi_users_groups.FK_groups 
							FROM glpi_users_groups 
							LEFT JOIN glpi_groups ON (glpi_groups.ID = glpi_users_groups.FK_groups) 
							WHERE glpi_users_groups.FK_users='" . $input["ID"] . "' $WHERE";
	
				$result = $DB->query($query);
				if ($DB->numrows($result) > 0) {
					while ($data = $DB->fetch_array($result))
						if (!in_array($data["FK_groups"], $input["_groups"])) {
							deleteUserGroup($data["ID"]);
						}
				}
	
				foreach ($input["_groups"] as $group) {
					addUserGroup($input["ID"], $group);
				}
				unset ($input["_groups"]);
			}
		}
	}

	function getName() {
		if (strlen($this->fields["realname"]) > 0)
			return $this->fields["realname"] . " " . $this->fields["firstname"];
		else
			return $this->fields["name"];

	}

	/**
	 * Function that try to load from LDAP the user information...
	 *
	 * @param $host LDAP host to connect
	 * @param $port LDAP port
	 * @param $use_tls use a tls connection
	 * @param $userdn Basedn of the user
	 * @param $rdn Root dn 
	 * @param $rpass Root Password
	 * @param $fields Fields to get
	 * @param $login User Login
	 * @param $password User Password
	 * @param $condition Condition used to restrict login
	 *
	 * @return String : basedn of the user / false if not founded
	 */
	function getFromLDAP($ldap_method, $userdn, $login, $password = "") {
		global $DB;

		// we prevent some delay...
		if (empty ($ldap_method["ldap_host"])) {
			return false;
		}

		$ds = connect_ldap($ldap_method["ldap_host"], $ldap_method["ldap_port"], $ldap_method["ldap_rootdn"], $ldap_method["ldap_pass"], $ldap_method["ldap_use_tls"]);
		// Test with login and password of the user
		if (!$ds)
			$ds = connect_ldap($ldap_method["ldap_host"], $ldap_method["ldap_port"], $login, $password, $ldap_method["ldap_use_tls"]);
		if ($ds) {
			//Set all the search fields
			$this->fields['password'] = "";
			$this->fields['password_md5'] = "";
			$fields['name'] = $ldap_method["ldap_login"];
			$fields['email'] = $ldap_method["ldap_field_email"];
			$fields['realname'] = $ldap_method["ldap_field_realname"];
			$fields['firstname'] = $ldap_method["ldap_field_firstname"];
			$fields['phone'] = $ldap_method["ldap_field_phone"];
			$fields['phone2'] = $ldap_method["ldap_field_phone2"];
			$fields['mobile'] = $ldap_method["ldap_field_mobile"];
			$fields['comments'] = $ldap_method["ldap_field_comments"];

			$fields = array_filter($fields);
			$f = array_values($fields);
							
			$sr = @ ldap_read($ds, $userdn, "objectClass=*", $f);
			$v = ldap_get_entries($ds, $sr);
		
			if (!is_array($v) || count($v) == 0 || empty ($v[0][$fields['name']][0]))
				return false;

			foreach ($fields as $k => $e) {
					if (!empty($v[0][$e][0]))
					$this->fields[$k] = addslashes($v[0][$e][0]);
					else
					$this->fields[$k] = "";
			}

			// Get group fields
			$query_user = "SELECT ID,ldap_field, ldap_value FROM glpi_groups WHERE ldap_field<>'' AND ldap_field IS NOT NULL AND ldap_value<>'' AND ldap_value IS NOT NULL";
			$query_group = "SELECT ID,ldap_group_dn FROM glpi_groups WHERE ldap_group_dn<>'' AND ldap_group_dn IS NOT NULL";

			$group_fields = array ();
			$groups = array ();
			$v = array ();
			//The groupes are retrived by looking into an ldap user object
			if ($ldap_method["ldap_search_for_groups"] == 0 || $ldap_method["ldap_search_for_groups"] == 2) {

				$result = $DB->query($query_user);

				if ($DB->numrows($result) > 0) {
					while ($data = $DB->fetch_assoc($result)) {
						$group_fields[] = strtolower($data["ldap_field"]);
						$groups[strtolower($data["ldap_field"])][$data["ID"]] = $data["ldap_value"];
					}
					$group_fields = array_unique($group_fields);
					// If the groups must be retrieve from the ldap user object
					$sr = @ ldap_read($ds, $userdn, "objectClass=*", $group_fields);
					$v = ldap_get_entries($ds, $sr);
				}
			}
			//The groupes are retrived by looking into an ldap group object
			if ($ldap_method["ldap_search_for_groups"] == 1 || $ldap_method["ldap_search_for_groups"] == 2) {

				$result = $DB->query($query_group);

				if ($DB->numrows($result) > 0) {
					while ($data = $DB->fetch_assoc($result)) {
						$groups[$ldap_method["ldap_field_group_member"]][$data["ID"]] = $data["ldap_group_dn"];
					}
					if ($ldap_method["use_dn"])
						$user_tmp = $userdn;
					else
						$user_tmp = $ldap_method["ldap_login"]."=".$login;
						
					$v2 = $this->ldap_get_user_groups($ds, $ldap_method["ldap_basedn"], $user_tmp, $ldap_method["ldap_group_condition"], $ldap_method["ldap_field_group_member"]);
					
					$v = array_merge($v, $v2);
				}

			}

			if (is_array($v) && count($v) > 0) {
				foreach ($v as $attribute => $valattribute) {
					if (is_array($valattribute))
						foreach ($valattribute as $key => $val) {
							if (is_array($val))
								for ($i = 0; $i < count($val); $i++) {
									if (isset ($val[$i]))
										if ($group_found = array_search($val[$i], $groups[$key])) {
											$this->fields["_groups"][] = $group_found;
										}
								}
						}
				}
			}

		//Instanciate the affectation's rule
		$rule = new RightRuleCollection();
			
		//Process affectation rules :
		//we don't care about the function's return because all the datas are stored in session temporary
		if (isset($this->fields["_groups"]))
			$groups = $this->fields["_groups"];
		else
			$groups = array();	

		$this->fields=$rule->processAllRules($groups,$this->fields,array("type"=>"LDAP","ldap_server"=>$ldap_method["ID"],"connection"=>$ds,"userdn"=>$userdn));
		
		//Hook to retrieve more informations for ldap
		$this->fields = doHookFunction("retrieve_more_data_from_ldap", $this->fields);
		}
		return false;

	} // getFromLDAP()

	//Get all the group a user belongs to
	function ldap_get_user_groups($ds, $ldap_base_dn, $user_dn, $group_condition, $group_field_member) {

		$groups = array ();
		$listgroups = array ();

		//Only retrive cn and member attributes from groups
		$attrs = array (
			"dn"
		);

		$filter = "(& $group_condition ($group_field_member=$user_dn))";
	
		//Perform the search
		$sr = ldap_search($ds, $ldap_base_dn, $filter, $attrs);

		//Get the result of the search as an array
		$info = ldap_get_entries($ds, $sr);
		//Browse all the groups
		for ($i = 0; $i < count($info); $i++) {
			//Get the cn of the group and add it to the list of groups
			if (isset ($info[$i]["dn"]) && $info[$i]["dn"] != '')
				$listgroups[$i] = $info[$i]["dn"];
		}

		//Create an array with the list of groups of the user
		$groups[0][$group_field_member] = $listgroups;
		//Return the groups of the user
		return $groups;
	}

	// Function that try to load from IMAP the user information... this is
	// a fake one, as you can see...
	function getFromIMAP($mail_method, $name) {
		// we prevent some delay..
		if (empty ($mail_method["imap_host"])) {
			return false;
		}

		// some defaults...
		$this->fields['password'] = "";
		$this->fields['password_md5'] = "";
		if (ereg("@", $name))
			$this->fields['email'] = $name;
		else
			$this->fields['email'] = $name . "@" . $mail_method["imap_host"];

		$this->fields['name'] = $name;

		//Instanciate the affectation's rule
		$rule = new RightRuleCollection();
			
		//Process affectation rules :
		//we don't care about the function's return because all the datas are stored in session temporary
		if (isset($this->fields["_groups"]))
			$groups = $this->fields["_groups"];
		else
			$groups = array();	
		$this->fields=$rule->processAllRules($groups,$this->fields,array("type"=>"MAIL","mail_server"=>$mail_method["ID"],"email"=>$this->fields["email"]));
		
		return true;

	} // getFromIMAP()  	    

	function blankPassword() {
		global $DB;
		if (!empty ($this->fields["name"])) {

			$query = "UPDATE glpi_users SET password='' , password_md5='' WHERE name='" . $this->fields["name"] . "'";
			$DB->query($query);
		}
	}

	function title() {
		global $LANG, $CFG_GLPI;

		$buttons = array ();
		$title = $LANG["Menu"][14];
		if (haveRight("user", "w")) {
			$buttons["user.form.php?new=1"] = $LANG["setup"][2];
			$title = "";
		}
		if (useAuthLdap()) {
			$buttons["user.form.php?new=1&amp;ext_auth=1"] = $LANG["setup"][125];
			$buttons["ldap.php"] = $LANG["setup"][3];
			
		} else if (useAuthExt()) {
			$buttons["user.form.php?new=1&amp;ext_auth=1"] = $LANG["setup"][125];
		}

		displayTitle($CFG_GLPI["root_doc"] . "/pics/users.png", $LANG["Menu"][14], $title, $buttons);
	}

	function showForm($target, $ID, $withtemplate = '') {

		// Affiche un formulaire User
		global $CFG_GLPI, $LANG;

		if ($ID != $_SESSION["glpiID"] && !haveRight("user", "r"))
			return false;

		$canedit = haveRight("user", "w");
		$canread = haveRight("user", "r");

		// Helpdesk case
		if ($ID == 1) {
			echo "<div align='center'>";
			echo $LANG["setup"][220];
			echo "</div>";
			return false;
		}
		$spotted = false;

		if (empty ($ID)) {
			$spotted = $this->getEmpty();
		} else {
			$spotted = $this->getfromDB($ID);
		}
		if ($spotted) {
			$this->showOnglets($ID, $withtemplate, $_SESSION['glpi_onglet']);
			echo "<div align='center'>";
			echo "<form method='post' name=\"user_manager\" action=\"$target\"><table class='tab_cadre_fixe'>";
			if (empty ($ID)) {
				echo "<input type='hidden' name='FK_entities' value='" . $_SESSION["glpiactive_entity"] . "'>";
				echo "<input type='hidden' name='auth_method' value='1'>";
			}

			echo "<tr><th colspan='4'>" . $LANG["setup"][57] . " : " . $this->fields["name"] . "&nbsp;";
			echo "<a href='" . $CFG_GLPI["root_doc"] . "/front/user.vcard.php?ID=$ID'>" . $LANG["common"][46] . "</a>";
			echo "</th></tr>";
			echo "<tr class='tab_bg_1'>";
			echo "<td align='center'>" . $LANG["setup"][18] . "</td>";
			// si on est dans le cas d'un ajout , cet input ne doit plus �re hiden
			if ($this->fields["name"] == "") {
				echo "<td><input  name='name' value=\"" . $this->fields["name"] . "\">";
				echo "</td>";
				// si on est dans le cas d'un modif on affiche la modif du login si ce n'est pas une auth externe
			} else {
				if (!empty ($this->fields["password_md5"])||$this->fields["auth_method"]==AUTH_DB_GLPI) {
					echo "<td>";
					autocompletionTextField("name", "glpi_users", "name", $this->fields["name"], 20);
				} else {
					echo "<td align='center'><strong>" . $this->fields["name"] . "</strong>";
					echo "<input type='hidden' name='name' value=\"" . $this->fields["name"] . "\">";
				}

				echo "<input type='hidden' name='ID' value=\"" . $this->fields["ID"] . "\">";

				echo "</td>";
			}

			//do some rights verification
			if (haveRight("user", "w")) {
				if ($this->fields["auth_method"]==AUTH_DB_GLPI||!empty ($this->fields["password"]) || !empty ($this->fields["password_md5"]) || $this->fields["name"] == "") {
					echo "<td align='center'>" . $LANG["setup"][19] . ":</td><td><input type='password' name='password' value='' size='20' /></td></tr>";
				} else
					echo "<td colspan='2'>&nbsp;</td></tr>";
			} else
				echo "<td colspan='2'>&nbsp;</td></tr>";

			if (!($CFG_GLPI["cache"]->start($ID . "_" . $_SESSION["glpilanguage"], "GLPI_" . $this->type))) {
				echo "<tr class='tab_bg_1'><td align='center'>" . $LANG["common"][48] . ":</td><td>";
				autocompletionTextField("realname", "glpi_users", "realname", $this->fields["realname"], 20);
				echo "</td>";
				echo "<td align='center'>" . $LANG["common"][43] . ":</td><td>";
				autocompletionTextField("firstname", "glpi_users", "firstname", $this->fields["firstname"], 20);
				echo "</td></tr>";

				echo "<tr class='tab_bg_1'><td align='center'>" . $LANG["common"][42] . ":</td><td>";
				autocompletionTextField("mobile", "glpi_users", "mobile", $this->fields["mobile"], 20);
				echo "</td>";
				echo "<td align='center'>" . $LANG["setup"][14] . ":</td><td>";
				autocompletionTextField("email_form", "glpi_users", "email", $this->fields["email"], 30);
				echo "</td></tr>";

				echo "<tr class='tab_bg_1'><td align='center'>" . $LANG["financial"][29] . ":</td><td>";
				autocompletionTextField("phone", "glpi_users", "phone", $this->fields["phone"], 20);
				echo "</td>";
				echo "<td align='center'>" . $LANG["financial"][29] . " 2:</td><td>";
				autocompletionTextField("phone2", "glpi_users", "phone2", $this->fields["phone2"], 20);
				echo "</td></tr>";

				echo "<tr class='tab_bg_1' align='center'><td>" . $LANG["common"][25] . ":</td><td colspan='3'><textarea  cols='70' rows='3' name='comments' >" . $this->fields["comments"] . "</textarea></td>";
				echo "</tr>";

				//Authentications informations : auth method used and server used
				//don't display is creation of a new user'
				if (!empty ($ID)) {
					echo "<tr class='tab_bg_1' align='center'><td>" . $LANG["login"][10] . ":</td><td align='center'>";
					switch ($this->fields["auth_method"]) {
						case AUTH_LDAP :
							echo $LANG["login"][2];
							$url = $CFG_GLPI["root_doc"] . "/front/setup.auth.php?next=extauth_ldap&ID=";
							break;
						case AUTH_MAIL :
							echo $LANG["login"][3];
							$url = $CFG_GLPI["root_doc"] . "/front/setup.auth.php?next=extauth_mail&ID=";
							break;
						case AUTH_CAS :
							echo $LANG["login"][4];
							break;
						case AUTH_DB_GLPI :
							echo $LANG["login"][18];
							break;
						case NOT_YET_AUTHENTIFIED :
							echo $LANG["login"][9];
							break;
					}

					if (($this->fields["auth_method"] == AUTH_LDAP || $this->fields["auth_method"] == AUTH_MAIL)) {
						if ($method = $this->getAuthMethodsByID()) {
							//If user have right, display a link to the auth server
							if (haveRight("config", "w"))
								echo "&nbsp " . $LANG["common"][52] . " <a href=\"" . $url . $method["ID"] . "\">" . $method["name"] . "</a>";
							else
								echo "&nbsp " . $LANG["common"][52] . " " . $method["name"];
						}
					}
					echo "</td><td>" . $LANG["login"][0] . ":</td><td>";

					if ($this->fields["last_login"] != "0000-00-00 00:00:00")
						echo convDateTime($this->fields["last_login"]);

					echo "</td>";

					echo "</tr>";
					echo "<tr class='tab_bg_1' align='center'><td>" . $LANG["login"][24] . ":</td><td align='center'>";
					if ($this->fields["date_mod"] != "0000-00-00 00:00:00")
						echo convDateTime($this->fields["date_mod"]);
					echo "</td><td align='center' colspan='2'></td>";
					echo "</tr>";

				}

				$CFG_GLPI["cache"]->end();
			}

			if (haveRight("user", "w"))
				if ($this->fields["name"] == "") {
					echo "<tr>";
					echo "<td class='tab_bg_2' valign='top' colspan='4' align='center'>";
					echo "<input type='submit' name='add' value=\"" . $LANG["buttons"][8] . "\" class='submit'>";
					echo "</td>";
					echo "</tr>";
				} else {
					echo "<tr>";
					echo "<td class='tab_bg_2' valign='top' align='center' colspan='2'>";
					echo "<input type='submit' name='update' value=\"" . $LANG["buttons"][7] . "\" class='submit' >";
					echo "</td>";
					echo "<td class='tab_bg_2' valign='top' align='center' colspan='2'>\n";
					if (!$this->fields["deleted"]){
						echo "<input type='submit' name='delete' onclick=\"return confirm('" . $LANG["common"][50] . "')\" value=\"".$LANG["buttons"][6]."\" class='submit'>";
					 }else {
						echo "<input type='submit' name='restore' value=\"".$LANG["buttons"][21]."\" class='submit'>";

						echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type='submit' name='purge' value=\"".$LANG["buttons"][22]."\" class='submit'>";
					}

					echo "</td>";
					echo "</tr>";
				}

			echo "</table></form></div>";
			return true;
		}
		return false;
	}

	function showMyForm($target, $ID, $withtemplate = '') {

		// Affiche un formulaire User
		global $CFG_GLPI, $LANG;

		if ($ID != $_SESSION["glpiID"])
			return false;

		if ($this->getfromDB($ID)) {
			//$this->showOnglets($ID, $withtemplate,$_SESSION['glpi_onglet']);
			$auth_method = $this->getAuthMethodsByID();

			$extauth = empty ($this->fields["password"]) && empty ($this->fields["password_md5"]);
			$imapauth = !empty ($auth_method["imap_host"]);

			echo "<div align='center'>";
			echo "<form method='post' name=\"user_manager\" action=\"$target\"><table class='tab_cadre'>";
			echo "<tr><th colspan='2'>" . $LANG["setup"][57] . " : " . $this->fields["name"] . "</th></tr>";

			echo "<tr class='tab_bg_1'>";
			echo "<td align='center'>" . $LANG["setup"][18] . "</td>";
			echo "<td align='center'><strong>" . $this->fields["name"] . "</strong>";
			echo "<input type='hidden' name='name' value=\"" . $this->fields["name"] . "\">";
			echo "<input type='hidden' name='ID' value=\"" . $this->fields["ID"] . "\">";
			echo "</td></tr>";

			//do some rights verification
			if (!$extauth && haveRight("password_update", "1")) {
				echo "<tr class='tab_bg_1'><td align='center'>" . $LANG["setup"][19] . "</td><td><input type='password' name='password' value='' size='30' /></td></tr>";
			}

			if ($CFG_GLPI["debug"] != DEMO_MODE || haveRight("config", 1)) {
				echo "<tr class='tab_bg_1'><td align='center'>" . $LANG["setup"][41] . "</td><td>";
				dropdownLanguages("language", $_SESSION["glpilanguage"]);
				echo "</td></tr>";
			}

			echo "<tr class='tab_bg_1'><td align='center'>" . $LANG["common"][48] . "</td><td>";
			if (!$extauth || $imapauth || (isset ($auth_method['ldap_fields']) && empty ($auth_method['ldap_fields']["realname"]))) {
				autocompletionTextField("realname", "glpi_users", "realname", $this->fields["realname"], 30);
			} else
				echo $this->fields["realname"];
			echo "</td></tr>";

			echo "<tr class='tab_bg_1'><td align='center'>" . $LANG["common"][43] . "</td><td>";
			if (!$extauth || $imapauth || (isset ($auth_method['ldap_fields']) && empty ($auth_method['ldap_fields']["firstname"]))) {
				autocompletionTextField("firstname", "glpi_users", "firstname", $this->fields["firstname"], 30);
			} else
				echo $this->fields["firstname"];
			echo "</td></tr>";

			echo "<tr class='tab_bg_1'><td align='center'>" . $LANG["setup"][14] . "</td><td>";
			if (!$extauth || (isset ($auth_method['ldap_fields']) && empty ($auth_method['ldap_fields']["email"]))) {
				autocompletionTextField("email_form", "glpi_users", "email", $this->fields["email"], 30);
			} else
				echo $this->fields["email"];
			echo "</td></tr>";

			echo "<tr class='tab_bg_1'><td align='center'>" . $LANG["financial"][29] . "</td><td>";
			if (!$extauth || $imapauth || (isset ($auth_method['ldap_fields']) && empty ($auth_method['ldap_fields']["phone"]))) {
				autocompletionTextField("phone", "glpi_users", "phone", $this->fields["phone"], 30);
			} else
				echo $this->fields["phone"];
			echo "</td></tr>";

			echo "<tr class='tab_bg_1'><td align='center'>" . $LANG["financial"][29] . " 2</td><td>";
			if (!$extauth || $imapauth || (isset ($auth_method['ldap_fields']) && empty ($auth_method['ldap_fields']["phone2"]))) {
				autocompletionTextField("phone2", "glpi_users", "phone2", $this->fields["phone2"], 30);
			} else
				echo $this->fields["phone2"];
			echo "</td></tr>";

			echo "<tr class='tab_bg_1'><td align='center'>" . $LANG["common"][42] . "</td><td>";
			if (!$extauth || $imapauth || (isset ($auth_method['ldap_fields']) && empty ($auth_method['ldap_fields']["mobile"]))) {
				autocompletionTextField("mobile", "glpi_users", "mobile", $this->fields["mobile"], 30);
			} else
				echo $this->fields["mobile"];
			echo "</td></tr>";

			if (haveRight("show_ticket","w"))
			{
				echo "<tr class='tab_bg_1'><td align='center'>" . $LANG["setup"][40] . "</td><td>";
				dropdownYesNo('tracking_order',$_SESSION["glpitracking_order"]);
				echo "</td></tr>";
			}
			
			echo "<tr>";
			echo "<td class='tab_bg_2' valign='top' align='center' colspan='2'>";
			echo "<input type='submit' name='update' value=\"" . $LANG["buttons"][7] . "\" class='submit' >";
			echo "</td>";
			echo "</tr>";

			echo "</table></form></div>";
			return true;
		}
		return false;
	}

	//Get all the authentication method parameters for the current user
	function getAuthMethodsByID() {
		return getAuthMethodsByID($this->fields["auth_method"], $this->fields["id_auth"]);
	}

	function pre_updateInDB($input,$updates) {
		if (count($updates)){
			$this->fields["date_mod"]=$_SESSION["glpi_currenttime"];
			$updates[]="date_mod";
		}
		return array($input,$updates);
	}

	function purgeDynamicProfiles()
	{
		global $DB;
		$sql = "DELETE FROM glpi_users_profiles WHERE FK_users=".$this->fields["ID"]." AND dynamic=1";
		$DB->query($sql);
	}
}

/* Get all the authentication methods parameters for a specific auth_method and id_auth
	* and return it as an array 
	*/
function getAuthMethodsByID($auth_method, $id_auth) {
	global $DB;

	$auth_methods = array ();
	$sql = "";

	switch ($auth_method) {
		case AUTH_LDAP :
			//Get all the ldap directories
			$sql = "SELECT * FROM glpi_auth_ldap WHERE ID=" . $id_auth;
			break;
		case AUTH_MAIL :
			//Get all the pop/imap servers
			$sql = "SELECT * FROM glpi_auth_mail WHERE ID=" . $id_auth;
			break;
	}

	if ($sql != "") {
		$result = $DB->query($sql);
		if ($DB->numrows($result) > 0) {
			$auth_methods = $DB->fetch_array($result);
		}
	}
	//Return all the authentication methods in an array
	return $auth_methods;
}
?>
