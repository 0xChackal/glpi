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
include ($phproot . "/glpi/common/classes.php");
include ($phproot . "/glpi/common/functions.php");
include ($phproot . "/glpi/common/functions_db.php");
include ($phproot . "/glpi/config/based_config.php");
include ($cfg_install['config_dir'] . "/config_db.php");

// ITEMS TYPE
define("GENERAL_TYPE","0");
define("COMPUTER_TYPE","1");
define("NETWORKING_TYPE","2");
define("PRINTER_TYPE","3");
define("MONITOR_TYPE","4");
define("PERIPHERAL_TYPE","5");
define("SOFTWARE_TYPE","6");
define("CONTACT_TYPE","7");
define("ENTERPRISE_TYPE","8");
define("INFOCOM_TYPE","9");
define("CONTRACT_TYPE","10");
define("CARTRIDGE_TYPE","11");
define("TYPEDOC_TYPE","12");
define("DOCUMENT_TYPE","13");
define("KNOWBASE_TYPE","14");
define("USER_TYPE","15");
define("TRACKING_TYPE","16");
define("CONSUMABLE_TYPE","17");
define("CONSUMABLE_ITEM_TYPE","18");
define("CARTRIDGE_ITEM_TYPE","19");
define("LICENSE_TYPE","20");
define("LINK_TYPE","21");
define("STATE_TYPE","22");

// DEVICE TYPE
define("MOBOARD_DEVICE","1");
define("PROCESSOR_DEVICE","2");
define("RAM_DEVICE","3");
define("HDD_DEVICE","4");
define("NETWORK_DEVICE","5");
define("DRIVE_DEVICE","6"); 
define("CONTROL_DEVICE","7");
define("GFX_DEVICE","8");
define("SND_DEVICE","9");
define("PCI_DEVICE","10");
define("CASE_DEVICE","11");
define("POWER_DEVICE","12");

//Load language
if(!function_exists('loadLang')) {
	function loadLang($language) {
		
			unset($lang);
			global $lang;
			include ("_relpos.php");
			$file = $phproot ."/glpi/dicts/".$language.".php";
			include($file);
	}
}

/* ----------------------------------------------------------------- */
/**
* Get data from old dropdowns to new devices
*
* This function assure to keep clean data and integrity, during the change from 
* computers-dropdown to computers devices. Then delete the unused old elements.
*
* @param $devtype integer the devtype number 
* @param $devname string the device table name (end of the name (glpi_device_thisparam))
* @param $dpdname string the dropdown table name (end of the name (glpi_dropdown_thisparam))
* @param $compDpdName string the name of the dropdown foreign key on glpi_computers (eg : hdtype, processor) 
* @param $specif string the name of the dropdown value entry on glpi_computer (eg : hdspace, processor_speed) optionnal argument.
* @returns nothing if everything is good, else display mysql query and error.
*/
function compDpd2Device($devtype,$devname,$dpdname,$compDpdName,$specif='') {
	global $lang;
	$query = "select * from glpi_dropdown_".$dpdname."";
	$db = new DB;
	$result = $db->query($query);
	while($lndropd = $db->fetch_array($result)) {
		$query2 = "insert into glpi_device_".$devname." (designation) values ('".addslashes($lndropd["name"])."')";
		$db->query($query2) or die("unable to transfer ".$dpdname." to ".$devname."  ".$lang["update"][90].$db->error());
		$devid = $db->insert_id();
		$query3 = "select * from glpi_computers where ".$compDpdName." = '".$lndropd["ID"]."'";
		$result3 = $db->query($query3);
		while($lncomp = $db->fetch_array($result3)) {
			$query4 = "insert into glpi_computer_device (device_type, FK_device, FK_computers) values ('$devtype','".$devid."','".$lncomp["ID"]."')";
			if(!empty($specif)) {
				$queryspecif = "SELECT ".$specif." FROM glpi_computers WHERE ID = '".$lncomp["ID"]."'";
				if($resultspecif = $db->query($queryspecif)) {
					$query4 = "insert into glpi_computer_device (specificity, device_type, FK_device, FK_computers) values ('".$db->result($resultspecif,0,$specif)."','$devtype','".$devid."','".$lncomp["ID"]."')";
				}
				
			}
			$db->query($query4) or die("unable to migrate from ".$dpdname." to ".$devname." for item computer:".$lncomp["ID"]."  ".$lang["update"][90].$db->error());
		}
	}
	mysql_free_result($result);
	//Delete unused elements (dropdown on the computer table, dropdown table and specif)
	$query = "ALTER TABLE glpi_computers drop `".$compDpdName."`";
	$db->query($query) or die("Error : ".$query." ".$db->error());
	$query = "DROP TABLE `glpi_dropdown_".$dpdname."`";
	$db->query($query) or die("Error : ".$query." ".$db->error());
	if(!empty($specif)) {
		$query = "ALTER TABLE glpi_computers drop `".$specif."`";
		$db->query($query) or die("Error : ".$query." ".$db->error());
	}
}

/*---------------------------------------------------------------------*/
/**
* Test if there is an user with superadmin rights
*
*
* @returns boolean true if its ok, elsewhere false.
*/
function superAdminExists() {
	$db = new DB;
	$query = "select type, password from glpi_users";
	$result = $db->query($query);
	$var1 = false;
	while($line = $db->fetch_array($result)) {
		if($line["type"] == "super-admin" && !empty($line["password"])) $var1 = true;
	}
	mysql_free_result($result);
	return $var1;
}

/*---------------------------------------------------------------------*/
/**
* Put the correct root_doc value on glpi_config table.
*
*
* @returns nothing if everything is right, display query and mysql error if bad.
*/
function updaterootdoc() {
	
	// hack pour IIS qui ne connait pas $_SERVER['REQUEST_URI']  grrrr
	if ( !isset($_SERVER['REQUEST_URI']) ) {
	    $_SERVER['REQUEST_URI'] = $_SERVER['PHP_SELF'];
	}
	
	$root_doc = ereg_replace("/update.php","",$_SERVER['REQUEST_URI']);
	$db = new DB;
	$query = "update glpi_config set root_doc = '".$root_doc."' where ID = '1'";
	$db->query($query) or die(" root_doc ".$lang["update"][90].$db->error());
}

/*---------------------------------------------------------------------*/
/**
* Display the form of content update (addslashes compatibility (V0.4))
*
*
* @returns nothing (displays)
*/
function showContentUpdateForm() {
	
	global $lang;
	echo "<div align='center'>";
	echo "<h3>".$lang["update"][94]."</h3>";
	echo "<p>".$lang["install"][63]."</p>";
	echo "<p>".$lang["update"][107]."</p></div>";
	echo "<p class='submit'> <a href=\"update_content.php\"><span class='button'>".$lang["install"][25]."</span></a>";
}


///// FONCTION POUR UPDATE LOCATION

function validate_new_location(){
	$db=new DB;
	$query=" DROP TABLE `glpi_dropdown_locations`";	
	$db->query($query);
	$query=" ALTER TABLE `glpi_dropdown_locations_new` RENAME `glpi_dropdown_locations`";	
	$db->query($query);
}

function display_new_locations(){
	
	$db=new DB;

	$MAX_LEVEL=10;

	$SELECT_ALL="";
	$FROM_ALL="";
	$ORDER_ALL="";
	$WHERE_ALL="";
	for ($i=1;$i<=$MAX_LEVEL;$i++){
		$SELECT_ALL.=" , location$i.name AS NAME$i , location$i.parentID AS PARENT$i ";
		$FROM_ALL.=" LEFT JOIN glpi_dropdown_locations_new AS location$i ON location".($i-1).".ID = location$i.parentID ";
		//$WHERE_ALL.=" AND location$i.level='$i' ";
		$ORDER_ALL.=" , NAME$i";

	}

	$query="select location0.name AS NAME0, location0.parentID AS PARENT0 $SELECT_ALL FROM glpi_dropdown_locations_new AS location0 $FROM_ALL  WHERE location0.parentID='0' $WHERE_ALL  ORDER BY NAME0 $ORDER_ALL";
	//echo $query;
	//echo "<hr>";
	$result=$db->query($query);
	$data_old=array();
	echo "<table><tr>";
	for ($i=0;$i<=$MAX_LEVEL;$i++){
		echo "<th>$i</th><th>&nbsp;</th>";
	}
	echo "</tr>";

	while ($data =  $db->fetch_array($result)){
	
		echo "<tr class=tab_bg_1>";
		for ($i=0;$i<=$MAX_LEVEL;$i++){
			if (!isset($data_old["NAME$i"])||($data_old["PARENT$i"]!=$data["PARENT$i"])||($data_old["NAME$i"]!=$data["NAME$i"])){
				$name=$data["NAME$i"];
				if (isset($data["NAME".($i+1)])&&!empty($data["NAME".($i+1)]))
				$arrow="--->";
			else $arrow="";
			} else {
				$name="";
				$arrow="";
			}
	
			echo "<td>".$name."</td>";
			echo "<td>$arrow</td>";
		}
	
		echo "</tr>";
	$data_old=$data;
	}
	mysql_free_result($result);
	echo "</table>";
}

function display_old_locations(){
	$db=new DB;
	$query="SELECT * from glpi_dropdown_locations order by name;";
	$result=$db->query($query);

	while ($data =  $db->fetch_array($result))
	echo "<b>".$data['name']."</b> - ";
	
	mysql_free_result($result);
}

function location_create_new($split_char,$add_first){

	$db=new DB;
	
	$query_auto_inc= "ALTER TABLE `glpi_dropdown_locations_new` CHANGE `ID` `ID` INT(11) NOT NULL";
	$result_auto_inc=$db->query($query_auto_inc);
	
	$query="SELECT MAX(ID) AS MAX from glpi_dropdown_locations;";
	//echo $query."<br>";
	$result=$db->query($query);
	$new_ID=$db->result($result,0,"MAX");
	$new_ID++;


	
	$query="SELECT * from glpi_dropdown_locations;";
	$result=$db->query($query);

	$query_clear_new="TRUNCATE TABLE `glpi_dropdown_locations_new`";
	//echo $query_clear_new."<br>";
	
	$result_clear_new=$db->query($query_clear_new); 

	if (!empty($add_first)){
		$root_ID=$new_ID;
		$new_ID++;
		$query_insert="INSERT INTO glpi_dropdown_locations_new VALUES ('$root_ID','".addslashes($add_first)."',0)";
		
		echo $query_insert."<br>";
		$result_insert=$db->query($query_insert);
		
	} else {
		$root_ID=0;
	}

	while ($data =  $db->fetch_array($result)){
		if (!empty($split_char))
			$splitter=split($split_char,$data['name']);
		else $splitter=array($data['name']);
	
		$up_ID=$root_ID;
	
		for ($i=0;$i<count($splitter)-1;$i++){
			// Entr�e existe deja ??
			$query_search="select ID from glpi_dropdown_locations_new WHERE name='".addslashes($splitter[$i])."'  AND parentID='".$up_ID."'";
//				echo $query_search."<br>";
			$result_search=$db->query($query_search);
			if ($db->numrows($result_search)==1){	// Found
				$up_ID=$db->result($result_search,0,"ID");
			} else { // Not FOUND -> INSERT
				$query_insert="INSERT INTO glpi_dropdown_locations_new VALUES ('$new_ID','".addslashes($splitter[$i])."','$up_ID')";
//					echo $query_insert."<br>";
				$result_insert=$db->query($query_insert);
				$up_ID=$new_ID++;

			}
		}

		// Ajout du dernier
		$query_insert="INSERT INTO glpi_dropdown_locations_new VALUES ('".$data["ID"]."','".addslashes($splitter[count($splitter)-1])."','$up_ID')";
//			echo $query_insert."<br>";

		$result_insert=$db->query($query_insert);

	}
	mysql_free_result($result);
	$query_auto_inc= "ALTER TABLE `glpi_dropdown_locations_new` CHANGE `ID` `ID` INT(11) NOT NULL AUTO_INCREMENT";
	$result_auto_inc=$db->query($query_auto_inc);

}

///// FIN FONCTIONS POUR UPDATE LOCATION

function showLocationUpdateForm(){
	global $lang;
	$db=new DB;
	
	if (FieldExists("glpi_dropdown_locations", "parentID")) {
		updateTreeDropdown();
		return true;
	}

	if (!isset($_POST['root'])) $_POST['root']='';
	if (!isset($_POST['car_sep'])) $_POST['car_sep']='';

	if(!TableExists("glpi_dropdown_locations_new")) {
		$query = " CREATE TABLE `glpi_dropdown_locations_new` (`ID` INT NOT NULL auto_increment,`name` VARCHAR(255) NOT NULL ,`parentID` INT NOT NULL ,PRIMARY KEY (`ID`),UNIQUE KEY (`name`,`parentID`), KEY(`parentID`)) TYPE=MyISAM;";
		$db->query($query) or die("LOCATION ".$db->error());
	}

	if (!isset($_POST["validate_location"])){
		echo "<div align='center'>";
		echo "<h4>".$lang["update"][130]."</h4>";
		echo "<p>".$lang["update"][131]."</p>";
		echo "<p>".$lang["update"][132]."<br>".$lang["update"][133]."</p>";
		echo "<form action=\"".$_SERVER["PHP_SELF"]."\" method=\"post\">";
		echo "<p>".$lang["update"][134].": <input type=\"text\" name=\"car_sep\" value=\"".$_POST['car_sep']."\"/></p>";
		echo "<p>".$lang["update"][135].": <input type=\"text\" name=\"root\" value=\"".$_POST['root']."\"/></p>";
		echo "<input type=\"submit\" class='submit' name=\"new_location\" value=\"".$lang["buttons"][2]."\" />";
		echo "<input type=\"hidden\" name=\"from_update\" value=\"from_update\" />";
		echo "</form>";
		echo "</div>";
	}



	if (isset($_POST["new_location"])){
		location_create_new($_POST['car_sep'],$_POST['root']);	
		echo "<h4>".$lang["update"][138].": </h4>";
		display_old_locations();	
		echo "<h4>".$lang["update"][137].": </h4>";
		display_new_locations();	
		echo "<p>".$lang["update"][136]."</p>";
		echo "<form action=\"".$_SERVER["PHP_SELF"]."\" method=\"post\">";
		echo "<input type=\"submit\" class='submit' name=\"validate_location\" value=\"".$lang["buttons"][2]."\" />";
		echo "<input type=\"hidden\" name=\"from_update\" value=\"from_update\" />";
		echo "</form>";
	}
	else if (isset($_POST["validate_location"])){
		validate_new_location();
		updateTreeDropdown();
		return true;
	} else {
	display_old_locations();	
	}
}

//Verifie si la table $tablename existe
/*function TableExists($tablename) {
  
   $db = new DB;
   // Get a list of tables contained within the database.
   $result = $db->list_tables($db);
   $rcount = $db->numrows($result);

   // Check each in list for a match.
   for ($i=0;$i<$rcount;$i++) {
       if ($db->table_name($result, $i)==$tablename) return true;
   }
   mysql_free_result($result);
   return false;
}

//Verifie que le champs $field existe bien dans la table $table
function FieldExists($table, $field) {
	$db = new DB;
	$result = $db->query("SELECT * FROM ". $table ."");
	$fields = $db->num_fields($result);
	$var1 = false;
	for ($i=0; $i < $fields; $i++) {
		$name  = $db->field_name($result, $i);
		if(strcmp($name,$field)==0) {
			$var1 = true;
		}
	}
	mysql_free_result($result);
	return $var1;
}
*/

//test la connection a la base de donn�e.
function test_connect() {
$db = new DB;
if($db->error == 0) return true;
else return false;
}

//Change table2 from varchar to ID+varchar and update table1.chps with depends
function changeVarcharToID($table1, $table2, $chps)
{

global $lang;

$db = new DB;

if(!FieldExists($table2, "ID")) {
	$query = " ALTER TABLE `". $table2 ."` ADD `ID` INT NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST";
	$db->query($query) or die("".$lang["update"][90].$db->error());
}
$query = "ALTER TABLE $table1 ADD `temp` INT";
$db->query($query) or die($lang["update"][90].$db->error());

$query = "select ". $table1 .".ID as row1, ". $table2 .".ID as row2 from ". $table1 .",". $table2 ." where ". $table2 .".name = ". $table1 .".". $chps." ";
$result = $db->query($query) or die($lang["update"][90].$db->error());
while($line = $db->fetch_array($result)) {
	$query = "update ". $table1 ." set temp = ". $line["row2"] ." where ID = '". $line["row1"] ."'";
	$db->query($query) or die($lang["update"][90].$db->error());
}
mysql_free_result($result);

$query = "ALTER TABLE ". $table1 ." DROP ". $chps."";
$db->query($query) or die($lang["update"][90].$db->error());
$query = "ALTER TABLE ". $table1 ." CHANGE `temp` `". $chps ."` INT";
$db->query($query) or die($lang["update"][90].$db->error());
}

//update the database to the 0.31 version
function updateDbTo031()
{

global $lang;

$db = new DB;


//amSize ramSize
 $query = "Alter table users drop can_assign_job";
 $db->query($query) or die($lang["update"][90].$db->error());
 $query = "Alter table users add can_assign_job enum('yes','no') NOT NULL default 'no'";
 $db->query($query) or die($lang["update"][90].$db->error());
 $query = "Update users set can_assign_job = 'yes' where type = 'admin'";
 $db->query($query) or die($lang["update"][90].$db->error());
 
 echo "<p class='center'>Version 0.2 & < </p>";

//Version 0.21 ajout du champ ramSize a la table printers si non existant.


if(!FieldExists("printers", "ramSize")) {
	$query = "alter table printers add ramSize varchar(6) NOT NULL default ''";
	$db->query($query) or die($lang["update"][90].$db->error());
}

 echo "<p class='center'>Version 0.21  </p>";

//Version 0.3
//Ajout de NOT NULL et des valeurs par defaut.

$query = "ALTER TABLE computers MODIFY achat_date date NOT NULL default '0000-00-00'";
$db->query($query) or die($lang["update"][90].$db->error());
$query = "ALTER TABLE computers MODIFY date_fin_garantie date NOT NULL default '0000-00-00'";


$query = "ALTER TABLE monitors MODIFY achat_date date NOT NULL default '0000-00-00'";
$db->query($query) or die($lang["update"][90].$db->error());
$query = "ALTER TABLE monitors MODIFY date_fin_garantie date NOT NULL default '0000-00-00'";

$query = "ALTER TABLE networking MODIFY achat_date date NOT NULL default '0000-00-00'";
$db->query($query) or die($lang["update"][90].$db->error());
$query = "ALTER TABLE networking MODIFY date_fin_garantie date NOT NULL default '0000-00-00'";


$query = "ALTER TABLE printers MODIFY achat_date date NOT NULL default '0000-00-00'";
$db->query($query) or die($lang["update"][90].$db->error());
$query = "ALTER TABLE printers MODIFY date_fin_garantie date NOT NULL default '0000-00-00'";

$query = "ALTER TABLE templates MODIFY achat_date date NOT NULL default '0000-00-00'";
$db->query($query) or die($lang["update"][90].$db->error());
$query = "ALTER TABLE templates MODIFY date_fin_garantie date NOT NULL default '0000-00-00'";

 echo "<p class='center'>Version 0.3  </p>";

 
}
 

//update database up to 0.31
function updatedbUpTo031()
{

global $lang;
$ret = array();

$db = new DB;
if(!TableExists("glpi_config"))
{
$query = "CREATE TABLE `glpi_config` (
  `ID` int(11) NOT NULL auto_increment,
  `num_of_events` varchar(200) NOT NULL default '',
  `jobs_at_login` varchar(200) NOT NULL default '',
  `sendexpire` varchar(200) NOT NULL default '',
  `cut` varchar(200) NOT NULL default '',
  `expire_events` varchar(200) NOT NULL default '',
  `list_limit` varchar(200) NOT NULL default '',
  `version` varchar(200) NOT NULL default '',
  `logotxt` varchar(200) NOT NULL default '',
  `root_doc` varchar(200) NOT NULL default '',
  `event_loglevel` varchar(200) NOT NULL default '',
  `mailing` varchar(200) NOT NULL default '',
  `imap_auth_server` varchar(200) NOT NULL default '',
  `imap_host` varchar(200) NOT NULL default '',
  `ldap_host` varchar(200) NOT NULL default '',
  `ldap_basedn` varchar(200) NOT NULL default '',
  `ldap_rootdn` varchar(200) NOT NULL default '',
  `ldap_pass` varchar(200) NOT NULL default '',
  `admin_email` varchar(200) NOT NULL default '',
  `mailing_signature` varchar(200) NOT NULL default '',
  `mailing_new_admin` varchar(200) NOT NULL default '',
  `mailing_followup_admin` varchar(200) NOT NULL default '',
  `mailing_finish_admin` varchar(200) NOT NULL default '',
  `mailing_new_all_admin` varchar(200) NOT NULL default '',
  `mailing_followup_all_admin` varchar(200) NOT NULL default '',
  `mailing_finish_all_admin` varchar(200) NOT NULL default '',
  `mailing_new_all_normal` varchar(200) NOT NULL default '',
  `mailing_followup_all_normal` varchar(200) NOT NULL default '',
  `mailing_finish_all_normal` varchar(200) NOT NULL default '',
  `mailing_new_attrib` varchar(200) NOT NULL default '',
  `mailing_followup_attrib` varchar(200) NOT NULL default '',
  `mailing_finish_attrib` varchar(200) NOT NULL default '',
  `mailing_new_user` varchar(200) NOT NULL default '',
  `mailing_followup_user` varchar(200) NOT NULL default '',
  `mailing_finish_user` varchar(200) NOT NULL default '',
  `ldap_field_name` varchar(200) NOT NULL default '',
  `ldap_field_email` varchar(200) NOT NULL default '',
  `ldap_field_location` varchar(200) NOT NULL default '',
  `ldap_field_realname` varchar(200) NOT NULL default '',
  `ldap_field_phone` varchar(200) NOT NULL default '',
  PRIMARY KEY  (`ID`)
) TYPE=MyISAM AUTO_INCREMENT=2 ";
$db->query($query) or die($lang["update"][90].$db->error());

$query = "INSERT INTO `glpi_config` VALUES (1, '10', '1', '1', '80', '30', '15', ' 0.31', 'GLPI powered by indepnet', '/glpi', '5', '0', '', '', '', '', '', '', 'admsys@xxxxx.fr', 'SIGNATURE', '1', '1', '1', '1', '0', '0', '0', '0', '0', '0', '0', '0','1', '1', '1', 'uid', 'mail', 'physicaldeliveryofficename', 'cn', 'telephonenumber')";
$db->query($query) or die($lang["update"][90].$db->error());

  echo "<p class='center'>Version > 0.31  </p>";
}

// Get current version
$query="SELECT version FROM glpi_config";
$result=$db->query($query) or die("get current version".$db->error());
$current_version=trim($db->result($result,0,0));

switch ($current_version){
	case "0.31": update031to04();
	case "0.4": 
	case "0.41": update04to042();
	case "0.42": update042to05();
	case "0.5": update05to051();
	case "0.51": 
	case "0.51a": update051to06();
	case "0.6": update06to065();
	case "0.65": 
	break;
	default:
	update031to04();
	update04to042();
	update042to05();
	update05to051();
	update051to06();
	break;
}

// Update version number and default langage and new version_founded ---- LEAVE AT THE END
	$query = "UPDATE `glpi_config` SET `version` = ' 0.65', default_language='".$_SESSION["dict"]."',founded_new_version='' ;";
	$db->query($query) or die("0.6 ".$lang["update"][90].$db->error());

optimize_tables();

return $ret;
}

function update031to04(){
global $lang;
$db = new DB;

//0.4 Prefixage des tables : 
echo "<p class='center'>Version 0.4 </p>";

if(!TableExists("glpi_computers")) {

	$query = "ALTER TABLE computers RENAME glpi_computers";
	$db->query($query) or die($lang["update"][90].$db->error());
	$query = "ALTER TABLE connect_wire RENAME glpi_connect_wire";
	$db->query($query) or die($lang["update"][90].$db->error());
	$query = "ALTER TABLE dropdown_gfxcard RENAME glpi_dropdown_gfxcard";
	$db->query($query) or die($lang["update"][90].$db->error());
	$query = "ALTER TABLE dropdown_hdtype RENAME glpi_dropdown_hdtype";
	$db->query($query) or die($lang["update"][90].$db->error());
	$query = "ALTER TABLE dropdown_iface RENAME glpi_dropdown_iface";
	$db->query($query) or die($lang["update"][90].$db->error());
	$query = "ALTER TABLE dropdown_locations RENAME glpi_dropdown_locations";
	$db->query($query) or die($lang["update"][90].$db->error());
	$query = "ALTER TABLE dropdown_moboard RENAME glpi_dropdown_moboard";
	$db->query($query) or die($lang["update"][90].$db->error());
	$query = "ALTER TABLE dropdown_network RENAME glpi_dropdown_network";
	$db->query($query) or die($lang["update"][90].$db->error());
	$query = "ALTER TABLE dropdown_os RENAME glpi_dropdown_os";
	$db->query($query) or die($lang["update"][90].$db->error());
	$query = "ALTER TABLE dropdown_processor RENAME glpi_dropdown_processor";
	$db->query($query) or die($lang["update"][90].$db->error());
	$query = "ALTER TABLE dropdown_ram RENAME glpi_dropdown_ram";
	$db->query($query) or die($lang["update"][90].$db->error());
	$query = "ALTER TABLE dropdown_sndcard RENAME glpi_dropdown_sndcard";
	$db->query($query) or die($lang["update"][90].$db->error());
	$query = "ALTER TABLE event_log RENAME glpi_event_log";
	$db->query($query) or die($lang["update"][90].$db->error());
	$query = "ALTER TABLE followups RENAME glpi_followups";
	$db->query($query) or die($lang["update"][90].$db->error());
	$query = "ALTER TABLE inst_software RENAME glpi_inst_software";
	$db->query($query) or die($lang["update"][90].$db->error());
	$query = "ALTER TABLE licenses RENAME glpi_licenses";
	$db->query($query) or die($lang["update"][90].$db->error());
	$query = "ALTER TABLE monitors RENAME glpi_monitors";
	$db->query($query) or die($lang["update"][90].$db->error());
	$query = "ALTER TABLE networking RENAME glpi_networking";
	$db->query($query) or die($lang["update"][90].$db->error());
	$query = "ALTER TABLE networking_ports RENAME glpi_networking_ports";
	$db->query($query) or die($lang["update"][90].$db->error());
	$query = "ALTER TABLE networking_wire RENAME glpi_networking_wire";
	$db->query($query) or die($lang["update"][90].$db->error());
	if(TableExists("prefs")&&!TableExists("glpi_prefs")) {
		$query = "ALTER TABLE prefs RENAME glpi_prefs";
		$db->query($query) or die($lang["update"][90].$db->error());
	}
	$query = "ALTER TABLE printers RENAME glpi_printers";
	$db->query($query) or die($lang["update"][90].$db->error());
	$query = "ALTER TABLE software RENAME glpi_software";
	$db->query($query) or die($lang["update"][90].$db->error());
	$query = "ALTER TABLE templates RENAME glpi_templates";
	$db->query($query) or die($lang["update"][90].$db->error());
	$query = "ALTER TABLE tracking RENAME glpi_tracking";
	$db->query($query) or die($lang["update"][90].$db->error());
	$query = "ALTER TABLE type_computers RENAME glpi_type_computers";
	$db->query($query) or die($lang["update"][90].$db->error());
	$query = "ALTER TABLE type_monitors RENAME glpi_type_monitors";
	$db->query($query) or die($lang["update"][90].$db->error());
	$query = "ALTER TABLE type_networking RENAME glpi_type_networking";
	$db->query($query) or die($lang["update"][90].$db->error());
	$query = "ALTER TABLE type_printers RENAME glpi_type_printers";
	$db->query($query) or die($lang["update"][90].$db->error());
	$query = "ALTER TABLE users RENAME glpi_users";
	$db->query($query) or die($lang["update"][90].$db->error()); 

}	

//Ajout d'un champs ID dans la table users
if(!FieldExists("glpi_users", "ID")) {
	$query = "ALTER TABLE `glpi_users` DROP PRIMARY KEY";
	$db->query($query) or die($lang["update"][90].$db->error());
	$query = "ALTER TABLE `glpi_users` ADD UNIQUE (`name`)";
	$db->query($query) or die($lang["update"][90].$db->error());
	$query = "ALTER TABLE `glpi_users` ADD INDEX (`name`)";
	$db->query($query) or die($lang["update"][90].$db->error());
	$query = " ALTER TABLE `glpi_users` ADD `ID` INT NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST";
	$db->query($query) or die($lang["update"][90].$db->error());

}
//Mise a jour des ID pour les tables dropdown et type. cl�s primaires sur les tables dropdown et type, et mise a jour des champs li�s
if(!FieldExists("glpi_dropdown_os", "ID")) {
	changeVarcharToID("glpi_computers", "glpi_dropdown_os", "os");
	changeVarcharToID("glpi_computers", "glpi_dropdown_hdtype", "hdtype");
	changeVarcharToID("glpi_computers", "glpi_dropdown_sndcard", "sndcard");
	changeVarcharToID("glpi_computers", "glpi_dropdown_moboard", "moboard");
	changeVarcharToID("glpi_computers", "glpi_dropdown_gfxcard", "gfxcard");
	changeVarcharToID("glpi_computers", "glpi_dropdown_network", "network");
	changeVarcharToID("glpi_computers", "glpi_dropdown_ram", "ramtype");
	changeVarcharToID("glpi_computers", "glpi_dropdown_locations", "location");
	changeVarcharToID("glpi_computers", "glpi_dropdown_processor", "processor");
	changeVarcharToID("glpi_monitors", "glpi_dropdown_locations", "location");
	changeVarcharToID("glpi_networking", "glpi_dropdown_locations", "location");
	changeVarcharToID("glpi_networking_ports", "glpi_dropdown_iface", "iface");
	changeVarcharToID("glpi_printers", "glpi_dropdown_locations", "location");
	changeVarcharToID("glpi_software", "glpi_dropdown_locations", "location");
	changeVarcharToID("glpi_software", "glpi_dropdown_os", "platform");
	changeVarcharToID("glpi_templates", "glpi_dropdown_os", "os");
	changeVarcharToID("glpi_templates", "glpi_dropdown_hdtype", "hdtype");
	changeVarcharToID("glpi_templates", "glpi_dropdown_sndcard", "sndcard");
	changeVarcharToID("glpi_templates", "glpi_dropdown_moboard", "moboard");
	changeVarcharToID("glpi_templates", "glpi_dropdown_gfxcard", "gfxcard");
	changeVarcharToID("glpi_templates", "glpi_dropdown_network", "network");
	changeVarcharToID("glpi_templates", "glpi_dropdown_ram", "ramtype");
	changeVarcharToID("glpi_templates", "glpi_dropdown_locations", "location");
	changeVarcharToID("glpi_templates", "glpi_dropdown_processor", "processor");
	changeVarcharToID("glpi_users", "glpi_dropdown_locations", "location");
	
	changeVarcharToID("glpi_monitors", "glpi_type_monitors", "type");
	changeVarcharToID("glpi_printers", "glpi_type_printers", "type");
	changeVarcharToID("glpi_networking", "glpi_type_networking", "type");
	changeVarcharToID("glpi_computers", "glpi_type_computers", "type");
	changeVarcharToID("glpi_templates", "glpi_type_computers", "type");
	
}

if(!TableExists("glpi_type_peripherals")) {

$query = "CREATE TABLE `glpi_type_peripherals` (
	`ID` int(11) NOT NULL auto_increment,
	`name` varchar(255),
	 PRIMARY KEY  (`ID`)
	) TYPE=MyISAM;";
$db->query($query)or die("0A ".$lang["update"][90].$db->error());
}

if(!TableExists("glpi_peripherals")) {

	$query = "CREATE TABLE `glpi_peripherals` (
	`ID` int(11) NOT NULL auto_increment,
	`name` varchar(255) NOT NULL default '',
	`date_mod` datetime NOT NULL default '0000-00-00 00:00:00',
	 `contact` varchar(255) NOT NULL default '',
	 `contact_num` varchar(255) NOT NULL default '',
	`comments` text NOT NULL,
	`serial` varchar(255) NOT NULL default '',
	 `otherserial` varchar(255) NOT NULL default '',
	 `date_fin_garantie` date default NULL,
	  `achat_date` date NOT NULL default '0000-00-00',
	 `maintenance` int(2) default '0',
	  `location` int(11) NOT NULL default '0',
	 `type` int(11) NOT NULL default '0',
	 `brand` varchar(255) NOT NULL default '',
	  PRIMARY KEY  (`ID`)
	) TYPE=MyISAM;";

$db->query($query) or die("0 ".$lang["update"][90].$db->error());
}

if(TableExists("glpi_prefs")&&!FieldExists("glpi_prefs", "ID")) {
	$query = "Alter table glpi_prefs drop primary key";
	$db->query($query) or die("1 ".$lang["update"][90].$db->error());
	$query = "Alter table glpi_prefs add ID INT(11) not null auto_increment primary key";
	$db->query($query) or die("3 ".$lang["update"][90].$db->error());
}
if(!FieldExists("glpi_config", "ID")) {

	$query = "ALTER TABLE `glpi_config` CHANGE `config_id` `ID` INT(11) NOT NULL AUTO_INCREMENT ";
	$db->query($query) or die("4 ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_computers", "location")) {
	$query = "ALTER TABLE `glpi_computers` ADD INDEX (`location`) ";
	$db->query($query) or die("5 ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_computers", "os")) {
	$query = "ALTER TABLE `glpi_computers` ADD INDEX (`os`) ";
	$db->query($query) or die("6 ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_computers", "type")) {
	$query = "ALTER TABLE `glpi_computers` ADD INDEX (`type`) ";
	$db->query($query) or die("7 ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_followups", "tracking")) {
	$query = "ALTER TABLE `glpi_followups` ADD INDEX (`tracking`) ";
	$db->query($query) or die("12 ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_networking", "location")) {
	$query = "ALTER TABLE `glpi_networking` ADD INDEX (`location`) ";
	$db->query($query) or die("13 ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_networking_ports", "on_device")) {
	$query = "ALTER TABLE `glpi_networking_ports` ADD INDEX (`on_device` , `device_type`)";
	$db->query($query) or die("14 ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_peripherals", "type")) {
	$query = "ALTER TABLE `glpi_peripherals` ADD INDEX (`type`) ";
	$db->query($query) or die("14 ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_peripherals", "location")) {
	$query = "ALTER TABLE `glpi_peripherals` ADD INDEX (`location`) ";
	$db->query($query) or die("15 ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_printers", "location")) {
	$query = "ALTER TABLE `glpi_printers` ADD INDEX (`location`) ";
	$db->query($query) or die("16 ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_tracking", "computer")) {
	$query = "ALTER TABLE `glpi_tracking` ADD INDEX (`computer`) ";
	$db->query($query) or die("17 ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_tracking", "author")) {
	$query = "ALTER TABLE `glpi_tracking` ADD INDEX (`author`) ";
	$db->query($query) or die("18 ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_tracking", "assign")) {
	$query = "ALTER TABLE `glpi_tracking` ADD INDEX (`assign`) ";
	$db->query($query) or die("19 ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_tracking", "date")) {
	$query = "ALTER TABLE `glpi_tracking` ADD INDEX (`date`) ";
	$db->query($query) or die("20 ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_tracking", "closedate")) {
	$query = "ALTER TABLE `glpi_tracking` ADD INDEX (`closedate`) ";
	$db->query($query) or die("21 ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_tracking", "status")) {
	$query = "ALTER TABLE `glpi_tracking` ADD INDEX (`status`) ";
	$db->query($query) or die("22 ".$lang["update"][90].$db->error());
}


if(!TableExists("glpi_dropdown_firmware")) {
	$query = " CREATE TABLE `glpi_dropdown_firmware` (`ID` INT NOT NULL AUTO_INCREMENT ,`name` VARCHAR(255) NOT NULL ,PRIMARY KEY (`ID`))";
	$db->query($query) or die("23 ".$lang["update"][90].$db->error());
}

if(!FieldExists("glpi_networking","firmware")) {
	$query = "ALTER TABLE `glpi_networking` ADD `firmware` INT(11);";
	$db->query($query) or die("24 ".$lang["update"][90].$db->error());
}

if(!FieldExists("glpi_tracking","realtime")) {
	$query = "ALTER TABLE `glpi_tracking` ADD `realtime` FLOAT NOT NULL;";
	$db->query($query) or die("25 ".$lang["update"][90].$db->error());
}

if(!FieldExists("glpi_printers","flags_usb")) {
	$query = "ALTER TABLE `glpi_printers` ADD `flags_usb` TINYINT DEFAULT '0' NOT NULL AFTER `flags_par`";
	$db->query($query) or die("26 ".$lang["update"][90].$db->error());
}

if(!FieldExists("glpi_licenses","expire")) {
	$query = "ALTER TABLE `glpi_licenses` ADD `expire` date default NULL";
	$db->query($query) or die("27 ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_licenses", "sID")) {
$query = "ALTER TABLE `glpi_licenses` ADD INDEX (`sID`) ";
$db->query($query) or die("32 ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_followups", "author")) {
$query = "ALTER TABLE `glpi_followups` ADD INDEX (`author`) ";
$db->query($query) or die("33 ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_monitors", "type")) {
$query = "ALTER TABLE `glpi_monitors` ADD INDEX (`type`) ";
$db->query($query) or die("34 ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_monitors", "location")) {
$query = "ALTER TABLE `glpi_monitors` ADD INDEX (`location`) ";
$db->query($query) or die("35 ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_monitors", "type")) {
$query = "ALTER TABLE `glpi_monitors` ADD INDEX (`type`) ";
$db->query($query) or die("37 ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_networking", "type")) {
$query = "ALTER TABLE `glpi_networking` ADD INDEX (`type`) ";
$db->query($query) or die("38 ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_networking", "firmware")) {
$query = "ALTER TABLE `glpi_networking` ADD INDEX (`firmware`) ";
$db->query($query) or die("39 ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_printers", "type")) {
$query = "ALTER TABLE `glpi_printers` ADD INDEX (`type`) ";
$db->query($query) or die("42 ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_software", "platform")) {
$query = "ALTER TABLE `glpi_software` ADD INDEX (`platform`) ";
$db->query($query) or die("44 ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_software", "location")) {
$query = "ALTER TABLE `glpi_software` ADD INDEX (`location`) ";
$db->query($query) or die("45 ".$lang["update"][90].$db->error());
}

if(!TableExists("glpi_dropdown_netpoint")) {
	$query = " CREATE TABLE `glpi_dropdown_netpoint` (`ID` INT NOT NULL AUTO_INCREMENT ,`location` INT NOT NULL ,`name` VARCHAR(255) NOT NULL ,PRIMARY KEY (`ID`))";
	$db->query($query) or die("46 ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_dropdown_netpoint", "location")) {
$query = "ALTER TABLE `glpi_dropdown_netpoint` ADD INDEX (`location`) ";
$db->query($query) or die("47 ".$lang["update"][90].$db->error());
}

if(!FieldExists("glpi_networking_ports","netpoint")) {
	$query = "ALTER TABLE `glpi_networking_ports` ADD `netpoint` INT default NULL";
	$db->query($query) or die("27 ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_networking_ports", "netpoint")) {
$query = "ALTER TABLE `glpi_networking_ports` ADD INDEX (`netpoint`) ";
$db->query($query) or die("47 ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_networking_wire", "end1")) {
$query = "ALTER TABLE `glpi_networking_wire` ADD INDEX (`end1`) ";
$db->query($query) or die("40 ".$lang["update"][90].$db->error());


// Clean Table
$query = "SELECT * FROM  `glpi_networking_wire` ORDER BY end1, end2 ";
$result=$db->query($query);
$curend1=-1;
$curend2=-1;
while($line = $db->fetch_array($result)) {
	if ($curend1==$line['end1']&&$curend2==$line['end2']){
		$q2="DELETE FROM `glpi_networking_wire` WHERE `ID`='".$line['ID']."' LIMIT 1";
		$db->query($q2);
		}
	else {$curend1=$line['end1'];$curend2=$line['end2'];}
	}	
mysql_free_result($result);
		
$query = "ALTER TABLE `glpi_networking_wire` ADD UNIQUE end1_1 (`end1`,`end2`) ";
$db->query($query) or die("477 ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_networking_wire", "end2")) {
$query = "ALTER TABLE `glpi_networking_wire` ADD INDEX (`end2`) ";
$db->query($query) or die("41 ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_connect_wire", "end1")) {
$query = "ALTER TABLE `glpi_connect_wire` ADD INDEX (`end1`) ";
$db->query($query) or die("40 ".$lang["update"][90].$db->error());

// Clean Table
$query = "SELECT * FROM  `glpi_connect_wire` ORDER BY type, end1, end2 ";
$result=$db->query($query);
$curend1=-1;
$curend2=-1;
$curtype=-1;
while($line = $db->fetch_array($result)) {
	if ($curend1==$line['end1']&&$curend2==$line['end2']&&$curtype==$line['type']){
		$q2="DELETE FROM `glpi_connect_wire` WHERE `ID`='".$line['ID']."' LIMIT 1";
		$db->query($q2);
		}
	else{ $curend1=$line['end1'];$curend2=$line['end2'];$curtype=$line['type'];}
	}	
mysql_free_result($result);	
$query = "ALTER TABLE `glpi_connect_wire` ADD UNIQUE end1_1 (`end1`,`end2`,`type`) ";
$db->query($query) or die("478 ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_connect_wire", "end2")) {
$query = "ALTER TABLE `glpi_connect_wire` ADD INDEX (`end2`) ";
$db->query($query) or die("40 ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_connect_wire", "type")) {
$query = "ALTER TABLE `glpi_connect_wire` ADD INDEX (`type`) ";
$db->query($query) or die("40 ".$lang["update"][90].$db->error());
}



if(!FieldExists("glpi_config","ldap_condition")) {
	$query = "ALTER TABLE `glpi_config` ADD `ldap_condition` varchar(255) NOT NULL default ''";
	$db->query($query) or die("48 ".$lang["update"][90].$db->error());
}

$query = "ALTER TABLE `glpi_users` CHANGE `type` `type` ENUM('normal', 'admin', 'post-only', 'super-admin') DEFAULT 'normal' NOT NULL";
$db->query($query) or die("49 ".$lang["update"][90].$db->error());

$ret["adminchange"] = false;
//All "admin" users have to be set as "super-admin"
if(!superAdminExists()) {
	$query = "update glpi_users set type = 'super-admin' where type = 'admin'";
	$db->query($query) or die("49 ".$lang["update"][90].$db->error());
	if($db->affected_rows() != 0) {
		$ret["adminchange"] = true;
	}
}

if(!FieldExists("glpi_users","password_md5")) {
	$query = "ALTER TABLE `glpi_users` ADD `password_md5` VARCHAR(80) NOT NULL AFTER `password` ";
	$db->query($query) or die("glpi_users.Password_md5".$lang["update"][90].$db->error());
}

if(!FieldExists("glpi_config","permit_helpdesk")) {
	$query = "ALTER TABLE `glpi_config` ADD `permit_helpdesk` varchar(200) NOT NULL";
	$db->query($query) or die("glpi_config_permit_helpdesk ".$lang["update"][90].$db->error());
}

}

// Update from 0.4 and 0.41 to 0.42
function update04to042(){
global $lang;
$db = new DB;

echo "<p class='center'>Version 0.42 </p>";

if(!TableExists("glpi_reservation_item")) {

 
	$query = "CREATE TABLE glpi_reservation_item (ID int(11) NOT NULL auto_increment,device_type tinyint(4) NOT NULL default '0', id_device int(11) NOT NULL default '0', comments text NOT NULL, PRIMARY KEY  (ID), KEY device_type (device_type));";

	$db->query($query) or die("4201 ".$lang["update"][90].$db->error());
}

if(!TableExists("glpi_reservation_resa")) {
	$query = "CREATE TABLE glpi_reservation_resa (  
	ID bigint(20) NOT NULL auto_increment,  
	id_item int(11) NOT NULL default '0',  
	begin datetime NOT NULL default '0000-00-00 00:00:00',  
	end datetime NOT NULL default '0000-00-00 00:00:00',  
	id_user int(11) NOT NULL default '0',  
	PRIMARY KEY  (`ID`),  
	KEY id_item (`id_item`),  
	KEY id_user (`id_user`),  
	KEY begin (`begin`),  
	KEY end (`end`));";

	$db->query($query) or die("4202 ".$lang["update"][90].$db->error());
}

if(!FieldExists("glpi_tracking","device_type")) {
	$query = "ALTER TABLE `glpi_tracking` ADD `device_type` INT DEFAULT '1' NOT NULL AFTER `assign` ;";
	$db->query($query) or die("4203 ".$lang["update"][90].$db->error());
}

// Ajout language par defaut
if(!FieldExists("glpi_config","default_language")) {

	$query = "ALTER TABLE `glpi_config` ADD `default_language` VARCHAR(255) DEFAULT 'french' NOT NULL ;";
	$db->query($query) or die("4204 ".$lang["update"][90].$db->error());

}

}

// Update from 0.42 to 0.5
function update042to05(){
global $lang;
$db = new DB;

 echo "<p class='center'>Version 0.5 </p>";


// Augmentation taille itemtype
	$query = "ALTER TABLE `glpi_event_log` CHANGE `itemtype` `itemtype` VARCHAR(20) NOT NULL ;";
	$db->query($query) or die("4204 ".$lang["update"][90].$db->error());

	// Correction des itemtype tronqu�s
	$query = "UPDATE `glpi_event_log` SET `itemtype` = 'reservation' WHERE `itemtype` = 'reservatio' ;";
	$db->query($query) or die("4204 ".$lang["update"][90].$db->error());


/*******************************GLPI 0.5***********************************************/
//pass all templates to computers
if(!FieldExists("glpi_computers","is_template")) {
	$query = "ALTER TABLE `glpi_computers` ADD `is_template` ENUM('0','1') DEFAULT '0' NOT NULL ";
	$db->query($query) or die("0.5 alter computers add is_template ".$lang["update"][90].$db->error());
	$query = "ALTER TABLE `glpi_computers` ADD `tplname` VARCHAR(200) DEFAULT NULL ";
	$db->query($query) or die("0.5 alter computers add tplname ".$lang["update"][90].$db->error());
	
	$query = "Select * from glpi_templates";
	$result = $db->query($query);
	
	
	while($line = $db->fetch_array($result)) {
		$line=addslashes_deep($line);
		$query2 = "INSERT INTO glpi_computers (`ID`,`name`, `osver`, `processor_speed`, `serial`, `otherserial`, `ram`, `hdspace`, `contact`, `contact_num`, `comments`, `achat_date`, `date_fin_garantie`, `maintenance`, `os`, `hdtype`, `sndcard`, `moboard`, `gfxcard`, `network`, `ramtype`, `location`, `processor`, `type`, `is_template`, `tplname`)";
		
		$query2 .= " VALUES ('','".$line["name"]."', '".$line["osver"]."', '".$line["processor_speed"]."', '".$line["serial"]."', '".$line["otherserial"]."', '".$line["ram"]."', '".$line ["hdspace"]."', '".$line["contact"]."', '".$line["contact_num"]."', '".$line["comments"]."', '".$line["achat_date"]."', '".$line["date_fin_garantie"]."', '".$line["maintenance"]."', '".$line["os"]."', '".$line["hdtype"]."', '".$line["sndcard"]."', '".$line["moboard"]."', '".$line["gfxcard"]."', '".$line["network"]."', '".$line["ramtype"]."', '".$line["location"]."', '".$line["processor"]."', '".$line["type"]."','1','".$line["templname"]."')";	
		//echo $query2;
		$db->query($query2) or die("0.5-convert template 2 computers ".$db->error());
	}
	mysql_free_result($result);
	$query = "DROP TABLE glpi_templates";
	$db->query($query) or die("0.5 drop table templates ".$db->error());
	
	$query="SELECT ID FROM glpi_computers WHERE tplname='Blank Template'";
	$result=$db->query($query);
	if ($db->numrows($result)==0){
		$query="INSERT INTO glpi_computers (is_template,tplname) VALUES ('1','Blank Template')";
		$db->query($query) or die("0.5 add blank template ".$lang["update"][90].$db->error());	
	}
	mysql_free_result($result);
}




//New internal peripherals ( devices ) config

if(!TableExists("glpi_computer_device")) {
	$query = "CREATE TABLE `glpi_computer_device` (
  `ID` int(11) NOT NULL auto_increment,
  `specificity` varchar(250) NOT NULL default '',
  `device_type` tinyint(4) NOT NULL default '0',
  `FK_device` int(11) NOT NULL default '0',
  `FK_computers` int(11) NOT NULL default '0',
  PRIMARY KEY  (`ID`),
  KEY (`device_type`),
  KEY (`device_type`,`FK_device`),
  KEY (`FK_computers`)
) TYPE=MyISAM;";
	$db->query($query) or die("0.5 CREATE TABLE `glpi_computer_device` ".$lang["update"][90].$db->error());
}

if(!TableExists("glpi_device_gfxcard")) {
	$query = "CREATE TABLE `glpi_device_gfxcard` (
  `ID` int(11) NOT NULL auto_increment,
  `designation` varchar(120) NOT NULL default '',
  `ram` varchar(10) NOT NULL default '',
  `interface` enum('AGP','PCI','PCI-X','Other') NOT NULL default 'AGP',
  `comment` text NOT NULL,
  `FK_glpi_enterprise` int(11) NOT NULL default '0',
   `specif_default` VARCHAR(250) NOT NULL,
  PRIMARY KEY  (`ID`),
  KEY(`FK_glpi_enterprise`)
) TYPE=MyISAM;";
	$db->query($query) or die("0.5 create table `glpi_device_gfxcard` ".$lang["update"][90].$db->error());
	compDpd2Device(GFX_DEVICE,"gfxcard","gfxcard","gfxcard");
}
if(!TableExists("glpi_device_hdd")) {
	$query = "CREATE TABLE `glpi_device_hdd` (
  `ID` int(11) NOT NULL auto_increment,
  `designation` varchar(100) NOT NULL default '',
  `rpm` varchar(20) NOT NULL default '',
  `interface` enum('IDE','SATA','SCSI') NOT NULL default 'IDE',
  `cache` varchar(20) NOT NULL default '',
  `comment` text NOT NULL,
  `FK_glpi_enterprise` int(11) NOT NULL default '0',
  `specif_default` VARCHAR(250) NOT NULL,
  PRIMARY KEY  (`ID`),
  KEY(`FK_glpi_enterprise`)
) TYPE=MyISAM;";
	$db->query($query) or die("0.5 CREATE TABLE `glpi_device_hdtype` ".$lang["update"][90].$db->error());
	compDpd2Device(HDD_DEVICE,"hdd","hdtype","hdtype","hdspace");
}
if(!TableExists("glpi_device_iface")) {
	$query = "CREATE TABLE `glpi_device_iface` (
  `ID` int(11) NOT NULL auto_increment,
  `designation` varchar(120) NOT NULL default '',
  `bandwidth` varchar(20) NOT NULL default '',
  `comment` text NOT NULL,
  `FK_glpi_enterprise` int(11) NOT NULL default '0',
  `specif_default` VARCHAR(250) NOT NULL,
  PRIMARY KEY  (`ID`),
  KEY(`FK_glpi_enterprise`)
) TYPE=MyISAM;";
	$db->query($query) or die("0.5- CREATE TABLE `glpi_device_iface` ".$lang["update"][90].$db->error());
	compDpd2Device(NETWORK_DEVICE,"iface","network","network");
}
if(!TableExists("glpi_device_moboard")) {
	$query = "CREATE TABLE `glpi_device_moboard` (
  `ID` int(11) NOT NULL auto_increment,
  `designation` varchar(100) NOT NULL default '',
  `chipset` varchar(120) NOT NULL default '',
  `comment` text NOT NULL,
  `FK_glpi_enterprise` int(11) NOT NULL default '0',
  `specif_default` VARCHAR(250) NOT NULL,
  PRIMARY KEY  (`ID`),
  KEY(`FK_glpi_enterprise`)
) TYPE=MyISAM;";
	$db->query($query) or die("0.5 CREATE TABLE `glpi_device_moboard` ".$lang["update"][90].$db->error());
	compDpd2Device(MOBOARD_DEVICE,"moboard","moboard","moboard");
}
if(!TableExists("glpi_device_processor")) {
	$query = "CREATE TABLE `glpi_device_processor` (
  `ID` int(11) NOT NULL auto_increment,
  `designation` varchar(120) NOT NULL default '',
  `frequence` int(11) NOT NULL default '0',
  `comment` text NOT NULL,
  `FK_glpi_enterprise` int(11) NOT NULL default '0',
  `specif_default` VARCHAR(250) NOT NULL,
  PRIMARY KEY  (`ID`),
  KEY(`FK_glpi_enterprise`)
) TYPE=MyISAM;";
	$db->query($query) or die("0.5 CREATE TABLE `glpi_device_processor` ".$lang["update"][90].$db->error());
	compDpd2Device(PROCESSOR_DEVICE,"processor","processor","processor","processor_speed");
}
if(!TableExists("glpi_device_ram")) {
	$query = "CREATE TABLE `glpi_device_ram` (
  `ID` int(11) NOT NULL auto_increment,
  `designation` varchar(100) NOT NULL default '',
  `type` enum('EDO','DDR','SDRAM','SDRAM-2') NOT NULL default 'EDO',
  `frequence` varchar(8) NOT NULL default '',
  `comment` text NOT NULL,
  `FK_glpi_enterprise` int(11) NOT NULL default '0',
  `specif_default` VARCHAR(250) NOT NULL,
  PRIMARY KEY  (`ID`),
  KEY(`FK_glpi_enterprise`)
) TYPE=MyISAM;";
	$db->query($query) or die("0.5 CREATE TABLE `glpi_device_ram` ".$lang["update"][90].$db->error());
	compDpd2Device(RAM_DEVICE,"ram","ram","ramtype","ram");
}
if(!TableExists("glpi_device_sndcard")) {
	$query = "CREATE TABLE `glpi_device_sndcard` (
  `ID` int(11) NOT NULL auto_increment,
  `designation` varchar(120) NOT NULL default '',
  `type` varchar(100) NOT NULL default '',
  `comment` text NOT NULL,
  `FK_glpi_enterprise` int(11) NOT NULL default '0',
  `specif_default` VARCHAR(250) NOT NULL,
  PRIMARY KEY  (`ID`),
  KEY(`FK_glpi_enterprise`)
) TYPE=MyISAM;";
	$db->query($query) or die("0.5 CREATE TABLE `glpi_device_sndcard ".$lang["update"][90].$db->error());
	compDpd2Device(SND_DEVICE,"sndcard","sndcard","sndcard");
}

if(!TableExists("glpi_device_power")) {
	$query = "CREATE TABLE glpi_device_power (
	ID int(11) NOT NULL auto_increment,
	designation varchar(255) NOT NULL default '',
	power varchar(20) NOT NULL default '',
	atx enum('Y','N') NOT NULL default 'Y',
	`comment` text NOT NULL,
	FK_glpi_enterprise int(11) NOT NULL default '0',
	`specif_default` VARCHAR(250) NOT NULL,
	PRIMARY KEY (`ID`),
	KEY FK_glpi_enterprise (`FK_glpi_enterprise`)
	) TYPE=MyISAM;";
	$db->query($query) or die("Error : ".$query." ".$db->error());
}

if(!TableExists("glpi_device_case")) {
	$query = "CREATE TABLE glpi_device_case(
	ID int(11) NOT NULL AUTO_INCREMENT ,
	designation varchar(255) NOT NULL default '',
	format enum('Grand', 'Moyen', 'Micro') NOT NULL default 'Moyen',
	`comment` text NOT NULL ,
	FK_glpi_enterprise int(11) NOT NULL default '0',
	`specif_default` VARCHAR(250) NOT NULL,
	PRIMARY KEY (`ID`) ,
	KEY FK_glpi_enterprise(`FK_glpi_enterprise`)
	)TYPE = MyISAM;";
	$db->query($query) or die("Error : ".$query." ".$db->error());
}

if(!TableExists("glpi_device_drive")) {
	$query = "CREATE TABLE `glpi_device_drive` (
	`ID` INT NOT NULL AUTO_INCREMENT ,
	`designation` VARCHAR(255) NOT NULL ,
	`is_writer` ENUM('Y', 'N') DEFAULT 'Y' NOT NULL ,
	`speed` VARCHAR(30) NOT NULL ,
	`interface` ENUM('IDE', 'SATA', 'SCSI') NOT NULL ,
	`comment` TEXT NOT NULL ,
	`FK_glpi_enterprise` INT NOT NULL ,
	`specif_default` VARCHAR(250) NOT NULL,
	KEY FK_glpi_enterprise(`FK_glpi_enterprise`),
	PRIMARY KEY (`ID`)
	)TYPE=MyISAM;";
	$db->query($query) or die("Error : ".$query." ".$db->error());
}

if(!TableExists("glpi_device_pci")) {
	$query = "CREATE TABLE glpi_device_pci (
	ID int(11) NOT NULL auto_increment,
	designation varchar(255) NOT NULL default '',
	`comment` text NOT NULL,
	FK_glpi_enterprise int(11) NOT NULL default '0',
	`specif_default` VARCHAR(250) NOT NULL,
	PRIMARY KEY (ID),
	KEY FK_glpi_enterprise (FK_glpi_enterprise)
	) TYPE=MyISAM;";
	$db->query($query) or die("Error : ".$query." ".$db->error());
} 

if(!TableExists("glpi_device_control")) {
	$query = "CREATE TABLE glpi_device_control (
	ID int(11) NOT NULL auto_increment,
	designation varchar(255) NOT NULL default '',
	interface enum('IDE','SATA','SCSI','USB') NOT NULL default 'IDE',
	raid enum('Y','N') NOT NULL default 'Y',
	`comment` text NOT NULL,
	FK_glpi_enterprise int(11) NOT NULL default '0',
	`specif_default` VARCHAR(250) NOT NULL,
	PRIMARY KEY (ID),
	KEY FK_glpi_enterprise (FK_glpi_enterprise)
	) TYPE=MyISAM;";
	$db->query($query) or die("Error : ".$query." ".$db->error());
}


// END new internal devices.

if(!TableExists("glpi_enterprises")) {
	$query = "CREATE TABLE `glpi_enterprises` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(50) NOT NULL default '',
  `type` int(11) NOT NULL default '0',
  `address` text NOT NULL default '',
  `website` varchar(100) NOT NULL default '',
  `phonenumber` varchar(20) NOT NULL default '',
  `comments` text NOT NULL,
  `deleted` enum('Y','N') NOT NULL default 'N',
  PRIMARY KEY  (`ID`),
  KEY `deleted` (`deleted`),
  KEY `type` (`type`)
) TYPE=MyISAM;";
	$db->query($query) or die("0.5 CREATE TABLE `glpi_enterprise ".$lang["update"][90].$db->error());
}

/// Base connaissance
if(!TableExists("glpi_dropdown_kbcategories")) {
$query="CREATE TABLE `glpi_dropdown_kbcategories` (
  `ID` int(11) NOT NULL auto_increment,
  `parentID` int(11) NOT NULL default '0',
  `name` varchar(255) NOT NULL,
  PRIMARY KEY  (`ID`),
  KEY(`parentID`),
  UNIQUE KEY(`parentID`,`name`)
)  TYPE=MyISAM;";
	$db->query($query) or die("0.5 CREATE TABLE `glpi_dropdown_kbcategories ".$lang["update"][90].$db->error());

$query="CREATE TABLE `glpi_kbitems` (
  `ID` int(11) NOT NULL auto_increment,
  `categoryID` int(11) NOT NULL default '0',
  `question` text NOT NULL,
  `answer` text NOT NULL,
  `faq` enum('yes','no') NOT NULL default 'no',
  PRIMARY KEY  (`ID`),
  KEY(`categoryID`)
) TYPE=MyISAM;";
	$db->query($query) or die("0.5 CREATE TABLE `glpi_kbitems ".$lang["update"][90].$db->error());

}

// Comment reservation
if(!FieldExists("glpi_reservation_resa","comment")) {
	$query = "ALTER TABLE `glpi_reservation_resa` ADD `comment` VARCHAR(255) NOT NULL ;";
	$db->query($query) or die("0.5 alter reservation add comment ".$lang["update"][90].$db->error());
}	

// Tracking categorie
if(!TableExists("glpi_dropdown_tracking_category")) {

$query= "CREATE TABLE glpi_dropdown_tracking_category (
  ID int(11) NOT NULL auto_increment,
  name varchar(255) default NULL,
  PRIMARY KEY  (ID)
) TYPE=MyISAM;";
	$db->query($query) or die("0.5 CREATE TABLE `glpi_dropdown_tracking_category ".$lang["update"][90].$db->error());

}

if(!FieldExists("glpi_tracking","category")) {
	$query= "ALTER TABLE `glpi_tracking` ADD `category` INT(11) ;";
	$db->query($query) or die("0.5 alter tracking add categorie ".$lang["update"][90].$db->error());
}

// Nouvelle gestion des software et licenses
if(!FieldExists("glpi_licenses","oem")) {
$query = "ALTER TABLE `glpi_licenses` ADD `oem` ENUM('N', 'Y') DEFAULT 'N' NOT NULL , ADD `oem_computer` INT(11) NOT NULL, ADD `buy` ENUM('Y', 'N') DEFAULT 'Y' NOT NULL;";
	$db->query($query) or die("0.5 alter licenses add oem + buy ".$lang["update"][90].$db->error());

$query = "ALTER TABLE `glpi_software` ADD `is_update` ENUM('N', 'Y') DEFAULT 'N' NOT NULL , ADD `update_software` INT(11) NOT NULL DEFAULT '-1';";
	$db->query($query) or die("0.5 alter software add update ".$lang["update"][90].$db->error());
}

// Couleur pour les priorit�s
if(!FieldExists("glpi_config","priority_1")) {
$query= "ALTER TABLE `glpi_config` ADD `priority_1` VARCHAR(200) DEFAULT '#fff2f2' NOT NULL, ADD `priority_2` VARCHAR(200) DEFAULT '#ffe0e0' NOT NULL, ADD `priority_3` VARCHAR(200) DEFAULT '#ffcece' NOT NULL, ADD `priority_4` VARCHAR(200) DEFAULT '#ffbfbf' NOT NULL, ADD `priority_5` VARCHAR(200) DEFAULT '#ffadad' NOT NULL ;";
	$db->query($query) or die("0.5 alter config add priority_X ".$lang["update"][90].$db->error());

}

// Gestion des cartouches
if(!TableExists("glpi_cartridges")) {
$query= "CREATE TABLE `glpi_cartridges` (
  `ID` int(11) NOT NULL auto_increment,
  `FK_glpi_cartridges_type` int(11) NOT NULL default '0',
  `FK_glpi_printers` int(11) NOT NULL default '0',
  `date_in` date default NULL,
  `date_use` date default NULL,
  `date_out` date default NULL,
  `pages` int(11)  NOT NULL default '0',
  PRIMARY KEY  (`ID`),
  KEY(`FK_glpi_cartridges_type`),
  KEY(`FK_glpi_printers`)
) TYPE=MyISAM;";
	$db->query($query) or die("0.5 CREATE TABLE glpi_cartridges ".$lang["update"][90].$db->error());

$query= "CREATE TABLE `glpi_cartridges_type` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
  `ref` varchar(255) NOT NULL default '',
  `location` int(11) NOT NULL default '0',
  `type` tinyint(4) NOT NULL default '0',
  `FK_glpi_enterprise` int(11) NOT NULL default '0',
  `tech_num` int(11) default '0',
  `deleted` enum('Y','N') NOT NULL default 'N',
  `comments` text NOT NULL,
  PRIMARY KEY  (`ID`),
  KEY(`FK_glpi_enterprise`),
  KEY(`tech_num`),
  KEY(`deleted`)
) TYPE=MyISAM;";
	$db->query($query) or die("0.5 CREATE TABLE glpi_cartridges_type ".$lang["update"][90].$db->error());
	
$query= "CREATE TABLE `glpi_cartridges_assoc` (
  `ID` int(11) NOT NULL auto_increment,
  `FK_glpi_cartridges_type` int(11) NOT NULL default '0',
  `FK_glpi_type_printer` int(11) NOT NULL default '0',
  PRIMARY KEY  (`ID`),
  UNIQUE KEY `FK_glpi_type_printer` (`FK_glpi_type_printer`,`FK_glpi_cartridges_type`),
 KEY(`FK_glpi_cartridges_type`),
 KEY(`FK_glpi_type_printer`) 
) TYPE=MyISAM;";
	$db->query($query) or die("0.5 CREATE TABLE glpi_cartridges_assoc ".$lang["update"][90].$db->error());
}

//// DEBUT INSERTION PARTIE GESTION 
if(!TableExists("glpi_contracts")) {
$query= "CREATE TABLE `glpi_contacts` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
  `phone` varchar(30) NOT NULL default '',
  `phone2` varchar(30) NOT NULL default '',
  `fax` varchar(30) NOT NULL default '',
  `email` varchar(255) NOT NULL default '',
  `type` tinyint(4) NOT NULL default '1',
  `comments` text NOT NULL,
  `deleted` enum('Y','N') NOT NULL default 'N',
  PRIMARY KEY  (`ID`)
) TYPE=MyISAM;";
	$db->query($query) or die("0.5 CREATE TABLE glpi_contact ".$lang["update"][90].$db->error());

$query = " CREATE TABLE `glpi_dropdown_enttype` (`ID` INT NOT NULL AUTO_INCREMENT ,`name` VARCHAR(255) NOT NULL ,PRIMARY KEY (`ID`))";
$db->query($query) or die("23 ".$lang["update"][90].$db->error());

	
$query= "CREATE TABLE `glpi_contact_enterprise` (
  `ID` int(11) NOT NULL auto_increment,
  `FK_enterprise` int(11) NOT NULL default '0',
  `FK_contact` int(11) NOT NULL default '0',
  PRIMARY KEY  (`ID`),
  UNIQUE KEY `FK_enterprise` (`FK_enterprise`,`FK_contact`),
  KEY(`FK_enterprise`),
  KEY(`FK_contact`) 
) TYPE=MyISAM;";
	$db->query($query) or die("0.5 CREATE TABLE glpi_contact_enterprise ".$lang["update"][90].$db->error());

$query= "CREATE TABLE `glpi_contracts` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
  `num` varchar(255) NOT NULL default '',
  `cost` float NOT NULL default '0',
  `contract_type` int(11) NOT NULL default '0',
  `begin_date` date default NULL,
  `duration` tinyint(4) NOT NULL default '0',
  `notice` tinyint(4) NOT NULL default '0',
  `periodicity` tinyint(4) NOT NULL default '0',
  `facturation` tinyint(4) NOT NULL default '0',
  `bill_type` int(11) NOT NULL default '0',
  `comments` text NOT NULL,
  `compta_num` varchar(255) NOT NULL default '',
  `deleted` enum('Y','N') NOT NULL default 'N',
  `week_begin_hour` time NOT NULL default '00:00:00',
  `week_end_hour` time NOT NULL default '00:00:00',
  `saturday_begin_hour` time NOT NULL default '00:00:00',
  `saturday_end_hour` time NOT NULL default '00:00:00',
  `saturday` enum('Y','N') NOT NULL default 'N',
  `monday_begin_hour` time NOT NULL default '00:00:00',
  `monday_end_hour` time NOT NULL default '00:00:00',
  `monday` enum('Y','N') NOT NULL default 'N',
  PRIMARY KEY  (`ID`),
  KEY `contract_type` (`contract_type`),
  KEY `begin_date` (`begin_date`),
  KEY `bill_type` (`bill_type`)
) TYPE=MyISAM;";
	$db->query($query) or die("0.5 CREATE TABLE glpi_contract ".$lang["update"][90].$db->error());

$query= "CREATE TABLE `glpi_contract_device` (
  `ID` int(11) NOT NULL auto_increment,
  `FK_contract` int(11) NOT NULL default '0',
  `FK_device` int(11) NOT NULL default '0',
  `device_type` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`ID`),
  UNIQUE KEY `FK_contract` (`FK_contract`,`FK_device`,`device_type`),
  KEY (`FK_contract`),
  KEY (`FK_device`,`device_type`)
) TYPE=MyISAM;";
	$db->query($query) or die("0.5 CREATE TABLE glpi_contract_device ".$lang["update"][90].$db->error());

$query= "CREATE TABLE `glpi_contract_enterprise` (
  `ID` int(11) NOT NULL auto_increment,
  `FK_enterprise` int(11) NOT NULL default '0',
  `FK_contract` int(11) NOT NULL default '0',
  PRIMARY KEY  (`ID`),
  UNIQUE KEY `FK_enterprise` (`FK_enterprise`,`FK_contract`),
  KEY  (`FK_enterprise`),
  KEY (`FK_contract`)
) TYPE=MyISAM;";
	$db->query($query) or die("0.5 CREATE TABLE glpi_contrat_enterprise ".$lang["update"][90].$db->error());

$query= "CREATE TABLE `glpi_infocoms` (
  `ID` int(11) NOT NULL auto_increment,
  `FK_device` int(11) NOT NULL default '0',
  `device_type` tinyint(4) NOT NULL default '0',
  `buy_date` date NOT NULL default '0000-00-00',
  `use_date` date NOT NULL default '0000-00-00',
  `warranty_duration` tinyint(4) NOT NULL default '0',
  `warranty_info` varchar(255) NOT NULL default '',
  `FK_enterprise` int(11) default NULL,
  `num_commande` varchar(50) NOT NULL default '',
  `bon_livraison` varchar(50) NOT NULL default '',
  `num_immo` varchar(50) NOT NULL default '',
  `value` float NOT NULL default '0',
  `warranty_value` float default NULL,
  `amort_time` tinyint(4) NOT NULL default '0',
  `amort_type` varchar(20) NOT NULL default '',
  `amort_coeff` float NOT NULL default '0',
  `comments` text NOT NULL,
  PRIMARY KEY  (`ID`),
  UNIQUE KEY `FK_device` (`FK_device`,`device_type`),
  KEY `FK_enterprise` (`FK_enterprise`),
  KEY `buy_date` (`buy_date`)
) TYPE=MyISAM;";
	$db->query($query) or die("0.5 CREATE TABLE glpi_infocom ".$lang["update"][90].$db->error());

///// Move warranty infos from item to infocoms.

function date_diff($from, $to) {
	$from=strtotime($from);
	$to=strtotime($to);
	if ($from > $to) {
		$t = $to;
		$to = $from;
		$from = $t;
	}

	$year1 = date("Y", $from);
	$year2 = date("Y", $to);
	$month1 = date("n", $from);
	$month2 = date("n", $to);

	if ($month2 < $month1) {
		$month2 += 12;
		$year2 --;
	}
	$months = $month2 - $month1;
	$years = $year2 - $year1;
	return (12*$years+$months);
}

function updateMaintenanceInfos($table,$type,$ID){
	$db=new DB;
	$elements=array();
	$query="SELECT ID from $table WHERE maintenance='1'";
	$result=$db->query($query);
	while ($data=$db->fetch_array($result)){
	$query_insert="INSERT INTO glpi_contract_device (FK_contract,FK_device,device_type) VALUES ('$ID','".$data["ID"]."','$type')";	
	$result_insert=$db->query($query_insert) or die("0.5 insert for update maintenance ".$lang["update"][90].$db->error());
	}
	mysql_free_result($result);
	
	$query_drop =  "ALTER TABLE `$table` DROP `maintenance`";
	$result_drop=$db->query($query_drop) or die("0.5 drop for update maintenance ".$lang["update"][90].$db->error());

}

function updateWarrantyInfos($table,$type){
	$db=new DB;
	$elements=array();
	$query="SELECT ID,achat_date,date_fin_garantie from $table ORDER BY achat_date,date_fin_garantie";
	$result=$db->query($query) or die("0.5 select for update warranty ".$lang["update"][90].$db->error());
	while ($data=$db->fetch_array($result)){
		if (($data['achat_date']!="0000-00-00"&&!empty($data['achat_date']))||($data['date_fin_garantie']!="0000-00-00"&&!empty($data['date_fin_garantie']))){
			$IDitem=$data['ID'];
			if ($data['achat_date']=="0000-00-00"&&!empty($data['achat_date'])) $achat_date=date("Y-m-d");
			else $achat_date=$data['achat_date'];
			$duration=0;
			if ($data['date_fin_garantie']!="0000-00-00"&&!empty($data['date_fin_garantie']))
				$duration=round(date_diff($achat_date,$data['date_fin_garantie']),2);
			$query_insert="INSERT INTO glpi_infocoms (device_type,FK_device,buy_date,warranty_duration) VALUES ('$type','$IDitem','".$achat_date."','$duration')";
			$result_insert=$db->query($query_insert) or die("0.5 insert for update warranty ".$lang["update"][90].$db->error());
		}
	}
	mysql_free_result($result);
	
	$query_drop =  "ALTER TABLE `$table` DROP `achat_date`";
	$result_drop=$db->query($query_drop) or die("0.5 drop1 for update warranty ".$lang["update"][90].$db->error());
	$query_drop =  "ALTER TABLE `$table` DROP `date_fin_garantie`";
	$result_drop=$db->query($query_drop) or die("0.5 drop2 for update warranty ".$lang["update"][90].$db->error());

}
function isMaintenanceUsed(){
	$db = new DB;
	$tables=array("glpi_computers","glpi_printers","glpi_monitors","glpi_peripherals","glpi_networking");
	foreach ($tables as $key => $table){
		$query="SELECT ID from $table WHERE maintenance='1';";
		$result=$db->query($query) or die("0.5 find for update maintenance ".$lang["update"][90].$db->error());
		if ($db->numrows($result)>0) return true;
	}
	return false;

}

function dropMaintenanceField(){
	$db = new DB;
	$tables=array("glpi_computers","glpi_printers","glpi_monitors","glpi_peripherals","glpi_networking");
	foreach ($tables as $key => $table){
		$query="ALTER TABLE `$table` DROP `maintenance`";
		$result=$db->query($query) or die("0.5 alter for update maintenance ".$lang["update"][90].$db->error());
	}
}


// Update Warranty Infos
updateWarrantyInfos("glpi_computers",COMPUTER_TYPE);
updateWarrantyInfos("glpi_printers",PRINTER_TYPE);
updateWarrantyInfos("glpi_networking",NETWORKING_TYPE);
updateWarrantyInfos("glpi_monitors",MONITOR_TYPE);
updateWarrantyInfos("glpi_peripherals",PERIPHERAL_TYPE);

// Update Maintenance Infos
if (isMaintenanceUsed()){

	$query="INSERT INTO `glpi_contracts` VALUES (1, 'Maintenance', '', '0', 5, '2005-01-01', 120, 0, 0, 0, 0, '', '', 'N', '00:00:00', '00:00:00', '00:00:00', '00:00:00', 'N', '00:00:00', '00:00:00', 'N');";
	$result=$db->query($query) or die("0.5 insert_init for update maintenace ".$lang["update"][90].$db->error());

	if ($result){
		$query="SELECT ID FROM glpi_contracts;";
		$result=$db->query($query) or die("0.5 select_init for update maintenace ".$lang["update"][90].$db->error());
		if ($result){
			$data=$db->fetch_array($result);
			$IDcontract=$data["ID"];
			updateMaintenanceInfos("glpi_computers",COMPUTER_TYPE,$IDcontract);
			updateMaintenanceInfos("glpi_printers",PRINTER_TYPE,$IDcontract);
			updateMaintenanceInfos("glpi_networking",NETWORKING_TYPE,$IDcontract);
			updateMaintenanceInfos("glpi_monitors",MONITOR_TYPE,$IDcontract);
			updateMaintenanceInfos("glpi_peripherals",PERIPHERAL_TYPE,$IDcontract);
		}
	}
} else dropMaintenanceField();

}
//// FIN INSERTION PARTIE GESTION 

// Merge de l'OS et de la version
if(FieldExists("glpi_computers","osver")) {
	// R�cup�ration des couples existants
	$query="SELECT DISTINCT glpi_computers.os AS ID , glpi_computers.osver AS VERS, glpi_dropdown_os.name as NAME FROM glpi_computers 
		LEFT JOIN glpi_dropdown_os ON glpi_dropdown_os.ID=glpi_computers.os ORDER BY glpi_computers.os, glpi_computers.osver";
	$result=$db->query($query) or die("0.5 select for update OS ".$lang["update"][90].$db->error());
	$valeur=array();
	$curros=-1;
	$currvers="-------------------------";
	while ($data=$db->fetch_array($result)){
		// Nouvel OS -> update de l'element de dropdown
		if ($data["ID"]!=$curros){
			$curros=$data["ID"];
			
			if (!empty($data["VERS"])){
				$query_update="UPDATE glpi_dropdown_os SET name='".$data["NAME"]." - ".$data["VERS"]."' WHERE ID='".$data["ID"]."'";
				$db->query($query_update) or die("0.5 update for update OS ".$lang["update"][90].$db->error());
			}
		
		} else { // OS deja mis a jour -> creation d'un nouvel OS et mise a jour des elements
		$newname=$data["NAME"]." - ".$data["VERS"];
		$query_insert="INSERT INTO glpi_dropdown_os (name) VALUES ('$newname');";
		$db->query($query_insert) or die("0.5 insert for update OS ".$lang["update"][90].$db->error());
		$query_select="SELECT ID from  glpi_dropdown_os WHERE name = '$newname';";
		$res=$db->query($query_select) or die("0.5 select for update OS ".$lang["update"][90].$db->error());
		if ($db->numrows($res)==1){
			$query_update="UPDATE glpi_computers SET os='".$db->result($res,0,"ID")."' WHERE os='".$data["ID"]."' AND osver='".$data["VERS"]."'";
			$db->query($query_update) or die("0.5 update2 for update OS ".$lang["update"][90].$db->error());
		}
		
		}
	}
	mysql_free_result($result);
	$query_alter= "ALTER TABLE `glpi_computers` DROP `osver` ";
	$db->query($query_alter) or die("0.5 alter for update OS ".$lang["update"][90].$db->error());
}

// Ajout Fabriquant computer
if(!FieldExists("glpi_computers","FK_glpi_enterprise")) {

	$query = "ALTER TABLE `glpi_computers` ADD `FK_glpi_enterprise` INT(11) DEFAULT '0' NOT NULL ;";
	$db->query($query) or die("0.5 add field manufacturer ".$lang["update"][90].$db->error());
	
	
	$query="ALTER TABLE `glpi_computers` ADD INDEX (`FK_glpi_enterprise`)" ;
	$db->query($query) or die("0.5 alter field manufacturer ".$lang["update"][90].$db->error());

}

// Ajout Fabriquant printer
if(!FieldExists("glpi_printers","FK_glpi_enterprise")) {

	$query = "ALTER TABLE `glpi_printers` ADD `FK_glpi_enterprise` INT(11) DEFAULT '0' NOT NULL ;";
	$db->query($query) or die("0.5 add field manufacturer ".$lang["update"][90].$db->error());
	
	$query="ALTER TABLE `glpi_printers` ADD INDEX (`FK_glpi_enterprise`)" ;
	$db->query($query) or die("0.5 alter field manufacturer ".$lang["update"][90].$db->error());


}

// Ajout Fabriquant networking
if(!FieldExists("glpi_networking","FK_glpi_enterprise")) {

	$query = "ALTER TABLE `glpi_networking` ADD `FK_glpi_enterprise` INT(11) DEFAULT '0' NOT NULL ;";
	$db->query($query) or die("0.5 add field manufacturer ".$lang["update"][90].$db->error());
	
	$query="ALTER TABLE `glpi_networking` ADD INDEX (`FK_glpi_enterprise`)" ;
	$db->query($query) or die("0.5 alter field manufacturer ".$lang["update"][90].$db->error());


}

// Ajout Fabriquant monitor
if(!FieldExists("glpi_monitors","FK_glpi_enterprise")) {

	$query = "ALTER TABLE `glpi_monitors` ADD `FK_glpi_enterprise` INT(11) DEFAULT '0' NOT NULL ;";
	$db->query($query) or die("0.5 add field manufacturer ".$lang["update"][90].$db->error());
	
	$query="ALTER TABLE `glpi_monitors` ADD INDEX (`FK_glpi_enterprise`)" ;
	$db->query($query) or die("0.5 alter field manufacturer ".$lang["update"][90].$db->error());


}

// Ajout Fabriquant software
if(!FieldExists("glpi_software","FK_glpi_enterprise")) {

	$query = "ALTER TABLE `glpi_software` ADD `FK_glpi_enterprise` INT(11) DEFAULT '0' NOT NULL ;";
	$db->query($query) or die("0.5 add field manufacturer ".$lang["update"][90].$db->error());
	
	$query="ALTER TABLE `glpi_software` ADD INDEX (`FK_glpi_enterprise`)" ;
	$db->query($query) or die("0.5 alter field manufacturer ".$lang["update"][90].$db->error());


}

// Ajout Fabriquant peripheral
if(!FieldExists("glpi_peripherals","FK_glpi_enterprise")) {

	$query = "ALTER TABLE `glpi_peripherals` ADD `FK_glpi_enterprise` INT(11) DEFAULT '0' NOT NULL ;";
	$db->query($query) or die("0.5 add field manufacturer ".$lang["update"][90].$db->error());
	
	$query="ALTER TABLE `glpi_peripherals` ADD INDEX (`FK_glpi_enterprise`)" ;
	$db->query($query) or die("0.5 alter field manufacturer ".$lang["update"][90].$db->error());
	

}

// Ajout deleted peripheral
if(!FieldExists("glpi_peripherals","deleted")) {

	$query = "ALTER TABLE `glpi_peripherals` ADD `deleted` ENUM('Y', 'N') DEFAULT 'N' NOT NULL ;";
	$db->query($query) or die("0.5 add field deleted ".$lang["update"][90].$db->error());
	
	$query="ALTER TABLE `glpi_peripherals` ADD INDEX (`deleted`)" ;
	$db->query($query) or die("0.5 alter field deleted ".$lang["update"][90].$db->error());
	

}

// Ajout deleted software
if(!FieldExists("glpi_software","deleted")) {

	$query = "ALTER TABLE `glpi_software` ADD `deleted` ENUM('Y', 'N') DEFAULT 'N' NOT NULL ;";
	$db->query($query) or die("0.5 add field deleted ".$lang["update"][90].$db->error());

	$query="ALTER TABLE `glpi_software` ADD INDEX (`deleted`)" ;
	$db->query($query) or die("0.5 alter field deleted ".$lang["update"][90].$db->error());
	

}

// Ajout deleted monitor
if(!FieldExists("glpi_monitors","deleted")) {

	$query = "ALTER TABLE `glpi_monitors` ADD `deleted` ENUM('Y', 'N') DEFAULT 'N' NOT NULL ;";
	$db->query($query) or die("0.5 add field deleted ".$lang["update"][90].$db->error());
	
	$query="ALTER TABLE `glpi_monitors` ADD INDEX (`deleted`)" ;
	$db->query($query) or die("0.5 alter field deleted ".$lang["update"][90].$db->error());
	

}

// Ajout deleted networking
if(!FieldExists("glpi_networking","deleted")) {

	$query = "ALTER TABLE `glpi_networking` ADD `deleted` ENUM('Y', 'N') DEFAULT 'N' NOT NULL ;";
	$db->query($query) or die("0.5 add field deleted ".$lang["update"][90].$db->error());

	$query="ALTER TABLE `glpi_networking` ADD INDEX (`deleted`)" ;
	$db->query($query) or die("0.5 alter field deleted ".$lang["update"][90].$db->error());
	

}
// Ajout deleted printer
if(!FieldExists("glpi_printers","deleted")) {

	$query = "ALTER TABLE `glpi_printers` ADD `deleted` ENUM('Y', 'N') DEFAULT 'N' NOT NULL ;";
	$db->query($query) or die("0.5 add field deleted ".$lang["update"][90].$db->error());
	
	$query="ALTER TABLE `glpi_printers` ADD INDEX (`deleted`)" ;
	$db->query($query) or die("0.5 alter field deleted ".$lang["update"][90].$db->error());
	

}
// Ajout deleted computer
if(!FieldExists("glpi_computers","deleted")) {

	$query = "ALTER TABLE `glpi_computers` ADD `deleted` ENUM('Y', 'N') DEFAULT 'N' NOT NULL ;";
	$db->query($query) or die("0.5 add field deleted ".$lang["update"][90].$db->error());
	
	$query="ALTER TABLE `glpi_computers` ADD INDEX (`deleted`)" ;
	$db->query($query) or die("0.5 alter field deleted ".$lang["update"][90].$db->error());
	

}

// Ajout template peripheral
if(!FieldExists("glpi_peripherals","is_template")) {

	$query = "ALTER TABLE `glpi_peripherals` ADD `is_template` ENUM('0', '1') DEFAULT '0' NOT NULL , ADD `tplname` VARCHAR(255) ;";
	$db->query($query) or die("0.5 add field deleted ".$lang["update"][90].$db->error());

	$query="INSERT INTO glpi_peripherals (is_template,tplname) VALUES ('1','Blank Template')";
	$db->query($query) or die("0.5 add blank template ".$lang["update"][90].$db->error());	

	$query="ALTER TABLE `glpi_peripherals` ADD INDEX (`is_template`)" ;
	$db->query($query) or die("0.5 alter field is_template ".$lang["update"][90].$db->error());
	
	
}

// Ajout template software
if(!FieldExists("glpi_software","is_template")) {

	$query = "ALTER TABLE `glpi_software` ADD `is_template` ENUM('0', '1') DEFAULT '0' NOT NULL , ADD `tplname` VARCHAR(255) ;";
	$db->query($query) or die("0.5 add field deleted ".$lang["update"][90].$db->error());

	$query="INSERT INTO glpi_software (is_template,tplname) VALUES ('1','Blank Template')";
	$db->query($query) or die("0.5 add blank template ".$lang["update"][90].$db->error());	
	
	$query="ALTER TABLE `glpi_software` ADD INDEX (`is_template`)" ;
	$db->query($query) or die("0.5 alter field is_template ".$lang["update"][90].$db->error());

}

// Ajout template monitor
if(!FieldExists("glpi_monitors","is_template")) {

	$query = "ALTER TABLE `glpi_monitors` ADD `is_template` ENUM('0', '1') DEFAULT '0' NOT NULL , ADD `tplname` VARCHAR(255) ;";
	$db->query($query) or die("0.5 add field deleted ".$lang["update"][90].$db->error());

	$query="INSERT INTO glpi_monitors (is_template,tplname) VALUES ('1','Blank Template')";
	$db->query($query) or die("0.5 add blank template ".$lang["update"][90].$db->error());	
	
	$query="ALTER TABLE `glpi_monitors` ADD INDEX (`is_template`)" ;
	$db->query($query) or die("0.5 alter field is_template ".$lang["update"][90].$db->error());
	

}

if(!isIndex("glpi_computers", "is_template")) {
	$query = "ALTER TABLE `glpi_computers` ADD INDEX (`is_template`) ";
	$db->query($query) or die("5 ".$lang["update"][90].$db->error());
}

// Ajout template networking
if(!FieldExists("glpi_networking","is_template")) {

	$query = "ALTER TABLE `glpi_networking` ADD `is_template` ENUM('0', '1') DEFAULT '0' NOT NULL , ADD `tplname` VARCHAR(255) ;";
	$db->query($query) or die("0.5 add field deleted ".$lang["update"][90].$db->error());

	$query="INSERT INTO glpi_networking (is_template,tplname) VALUES ('1','Blank Template')";
	$db->query($query) or die("0.5 add blank template ".$lang["update"][90].$db->error());	

	$query="ALTER TABLE `glpi_networking` ADD INDEX (`is_template`)" ;
	$db->query($query) or die("0.5 alter field is_template ".$lang["update"][90].$db->error());
	

}
// Ajout template printer
if(!FieldExists("glpi_printers","is_template")) {

	$query = "ALTER TABLE `glpi_printers` ADD `is_template` ENUM('0', '1') DEFAULT '0' NOT NULL , ADD `tplname` VARCHAR(255) ;";
	$db->query($query) or die("0.5 add field deleted ".$lang["update"][90].$db->error());
	
		$query="INSERT INTO glpi_printers (is_template,tplname) VALUES ('1','Blank Template')";
	$db->query($query) or die("0.5 add blank template ".$lang["update"][90].$db->error());	
	
	$query="ALTER TABLE `glpi_printers` ADD INDEX (`is_template`)" ;
	$db->query($query) or die("0.5 alter field is_template ".$lang["update"][90].$db->error());
	


}
// Ajout date_mod
if(!FieldExists("glpi_printers","date_mod")) {
	$query = "ALTER TABLE `glpi_printers` ADD `date_mod` DATETIME DEFAULT NULL";
	$db->query($query) or die("Error : ".$query." ".$db->error());

	$query="ALTER TABLE `glpi_printers` ADD INDEX (`date_mod`)" ;
	$db->query($query) or die("0.5 alter field date_mod ".$lang["update"][90].$db->error());
		
}

if(!isIndex("glpi_computers", "date_mod")) {
	$query = "ALTER TABLE `glpi_computers` ADD INDEX (`date_mod`) ";
	$db->query($query) or die("5 ".$lang["update"][90].$db->error());
}

// Ajout date_mod
if(!FieldExists("glpi_monitors","date_mod")) {
	$query = "ALTER TABLE `glpi_monitors` ADD `date_mod` DATETIME DEFAULT NULL";
	$db->query($query) or die("Error : ".$query." ".$db->error());
	
	$query="ALTER TABLE `glpi_monitors` ADD INDEX (`date_mod`)" ;
	$db->query($query) or die("0.5 alter field date_mod ".$lang["update"][90].$db->error());
}

// Ajout date_mod
if(!FieldExists("glpi_software","date_mod")) {
	$query = "ALTER TABLE `glpi_software` ADD `date_mod` DATETIME DEFAULT NULL";
	$db->query($query) or die("Error : ".$query." ".$db->error());
	
	$query="ALTER TABLE `glpi_software` ADD INDEX (`date_mod`)" ;
	$db->query($query) or die("0.5 alter field date_mod ".$lang["update"][90].$db->error());
}

// Ajout date_mod
if(!FieldExists("glpi_networking","date_mod")) {
	$query = "ALTER TABLE `glpi_networking` ADD `date_mod` DATETIME DEFAULT NULL";
	$db->query($query) or die("Error : ".$query." ".$db->error());
	
	$query="ALTER TABLE `glpi_networking` ADD INDEX (`date_mod`)" ;
	$db->query($query) or die("0.5 alter field date_mod ".$lang["update"][90].$db->error());
}

// Ajout tech_num
if(!FieldExists("glpi_computers","tech_num")) {
	$query = "ALTER TABLE `glpi_computers` ADD `tech_num` int(11) NOT NULL default '0' AFTER `contact_num`";
	$db->query($query) or die("Error : ".$query." ".$db->error());
	
	$query="ALTER TABLE `glpi_computers` ADD INDEX (`tech_num`)" ;
	$db->query($query) or die("0.5 alter field tech_num ".$lang["update"][90].$db->error());
}
// Ajout tech_num
if(!FieldExists("glpi_networking","tech_num")) {
	$query = "ALTER TABLE `glpi_networking` ADD `tech_num` int(11) NOT NULL default '0' AFTER `contact_num`";
	$db->query($query) or die("Error : ".$query." ".$db->error());
	
	$query="ALTER TABLE `glpi_networking` ADD INDEX (`tech_num`)" ;
	$db->query($query) or die("0.5 alter field tech_num ".$lang["update"][90].$db->error());
}
// Ajout tech_num
if(!FieldExists("glpi_printers","tech_num")) {
	$query = "ALTER TABLE `glpi_printers` ADD `tech_num` int(11) NOT NULL default '0' AFTER `contact_num`";
	$db->query($query) or die("Error : ".$query." ".$db->error());
	
	$query="ALTER TABLE `glpi_printers` ADD INDEX (`tech_num`)" ;
	$db->query($query) or die("0.5 alter field tech_num ".$lang["update"][90].$db->error());
}

// Ajout tech_num
if(!FieldExists("glpi_monitors","tech_num")) {
	$query = "ALTER TABLE `glpi_monitors` ADD `tech_num` int(11) NOT NULL default '0' AFTER `contact_num`";
	$db->query($query) or die("Error : ".$query." ".$db->error());
	
	$query="ALTER TABLE `glpi_monitors` ADD INDEX (`tech_num`)" ;
	$db->query($query) or die("0.5 alter field tech_num ".$lang["update"][90].$db->error());
}

// Ajout tech_num
if(!FieldExists("glpi_software","tech_num")) {
	$query = "ALTER TABLE `glpi_software` ADD `tech_num` int(11) NOT NULL default '0' AFTER `location`";
	$db->query($query) or die("Error : ".$query." ".$db->error());
	
	$query="ALTER TABLE `glpi_software` ADD INDEX (`tech_num`)" ;
	$db->query($query) or die("0.5 alter field tech_num ".$lang["update"][90].$db->error());
}

// Ajout tech_num
if(!FieldExists("glpi_peripherals","tech_num")) {
	$query = "ALTER TABLE `glpi_peripherals` ADD `tech_num` int(11) NOT NULL default '0' AFTER `contact_num`";
	$db->query($query) or die("Error : ".$query." ".$db->error());
	
	$query="ALTER TABLE `glpi_peripherals` ADD INDEX (`tech_num`)" ;
	$db->query($query) or die("0.5 alter field tech_num ".$lang["update"][90].$db->error());
}

// Ajout tech_num
if(!FieldExists("glpi_software","tech_num")) {
	$query = "ALTER TABLE `glpi_software` ADD `tech_num` int(11) NOT NULL default '0'";
	$db->query($query) or die("Error : ".$query." ".$db->error());
	
	$query="ALTER TABLE `glpi_software` ADD INDEX (`tech_num`)" ;
	$db->query($query) or die("0.5 alter field tech_num ".$lang["update"][90].$db->error());
}

// Ajout tech_num
if(!TableExists("glpi_type_docs")) {
	
$query = "CREATE TABLE glpi_type_docs (
		  ID int(11) NOT NULL auto_increment,
		  name varchar(255) NOT NULL default '',
		  ext varchar(10) NOT NULL default '',
		  icon varchar(255) NOT NULL default '',
		  mime varchar(100) NOT NULL default '',
		  upload enum('Y','N') NOT NULL default 'Y',
		  date_mod datetime default NULL,
		  PRIMARY KEY  (ID),
		  UNIQUE KEY extension (ext),
		  KEY (upload)
		) TYPE=MyISAM;";
		
$db->query($query) or die("Error creating table typedoc ".$query." ".$db->error());



$query = "INSERT INTO glpi_type_docs (ID, name, ext, icon, mime, upload, date_mod) VALUES  (1, 'JPEG', 'jpg', 'jpg-dist.png', '', 'Y', '2004-12-13 19:47:21'),
 (2, 'PNG', 'png', 'png-dist.png', '', 'Y', '2004-12-13 19:47:21'),
 (3, 'GIF', 'gif', 'gif-dist.png', '', 'Y', '2004-12-13 19:47:21'),
 (4, 'BMP', 'bmp', 'bmp-dist.png', '', 'Y', '2004-12-13 19:47:21'),
 (5, 'Photoshop', 'psd', 'psd-dist.png', '', 'Y', '2004-12-13 19:47:21'),
 (6, 'TIFF', 'tif', 'tif-dist.png', '', 'Y', '2004-12-13 19:47:21'),
 (7, 'AIFF', 'aiff', 'aiff-dist.png', '', 'Y', '2004-12-13 19:47:21'),
 (8, 'Windows Media', 'asf', 'asf-dist.png', '', 'Y', '2004-12-13 19:47:21'),
 (9, 'Windows Media', 'avi', 'avi-dist.png', '', 'Y', '2004-12-13 19:47:21'),
 (44, 'C source', 'c', '', '', 'Y', '2004-12-13 19:47:22'),
 (27, 'RealAudio', 'rm', 'rm-dist.png', '', 'Y', '2004-12-13 19:47:21'),
 (16, 'Midi', 'mid', 'mid-dist.png', '', 'Y', '2004-12-13 19:47:21'),
 (17, 'QuickTime', 'mov', 'mov-dist.png', '', 'Y', '2004-12-13 19:47:21'),
 (18, 'MP3', 'mp3', 'mp3-dist.png', '', 'Y', '2004-12-13 19:47:21'),
 (19, 'MPEG', 'mpg', 'mpg-dist.png', '', 'Y', '2004-12-13 19:47:21'),
 (20, 'Ogg Vorbis', 'ogg', 'ogg-dist.png', '', 'Y', '2004-12-13 19:47:21'),
 (24, 'QuickTime', 'qt', 'qt-dist.png', '', 'Y', '2004-12-13 19:47:21'),
 (10, 'BZip', 'bz2', 'bz2-dist.png', '', 'Y', '2004-12-13 19:47:21'),
 (25, 'RealAudio', 'ra', 'ra-dist.png', '', 'Y', '2004-12-13 19:47:21'),
 (26, 'RealAudio', 'ram', 'ram-dist.png', '', 'Y', '2004-12-13 19:47:21'),
 (11, 'Word', 'doc', 'doc-dist.png', '', 'Y', '2004-12-13 19:47:21'),
 (12, 'DjVu', 'djvu', '', '', 'Y', '2004-12-13 19:47:21'),
 (42, 'MNG', 'mng', '', '', 'Y', '2004-12-13 19:47:22'),
 (13, 'PostScript', 'eps', 'ps-dist.png', '', 'Y', '2004-12-13 19:47:21'),
 (14, 'GZ', 'gz', 'gz-dist.png', '', 'Y', '2004-12-13 19:47:21'),
 (37, 'WAV', 'wav', 'wav-dist.png', '', 'Y', '2004-12-13 19:47:22'),
 (15, 'HTML', 'html', 'html-dist.png', '', 'Y', '2004-12-13 19:47:21'),
 (34, 'Flash', 'swf', '', '', 'Y', '2004-12-13 19:47:22'),
 (21, 'PDF', 'pdf', 'pdf-dist.png', '', 'Y', '2004-12-13 19:47:21'),
 (22, 'PowerPoint', 'ppt', 'ppt-dist.png', '', 'Y', '2004-12-13 19:47:21'),
 (23, 'PostScript', 'ps', 'ps-dist.png', '', 'Y', '2004-12-13 19:47:21'),
 (40, 'Windows Media', 'wmv', '', '', 'Y', '2004-12-13 19:47:22'),
 (28, 'RTF', 'rtf', 'rtf-dist.png', '', 'Y', '2004-12-13 19:47:21'),
 (29, 'StarOffice', 'sdd', 'sdd-dist.png', '', 'Y', '2004-12-13 19:47:22'),
 (30, 'StarOffice', 'sdw', 'sdw-dist.png', '', 'Y', '2004-12-13 19:47:22'),
 (31, 'Stuffit', 'sit', 'sit-dist.png', '', 'Y', '2004-12-13 19:47:22'),
 (43, 'Adobe Illustrator', 'ai', 'ai-dist.png', '', 'Y', '2004-12-13 19:47:22'),
 (32, 'OpenOffice Impress', 'sxi', 'sxi-dist.png', '', 'Y', '2004-12-13 19:47:22'),
 (33, 'OpenOffice', 'sxw', 'sxw-dist.png', '', 'Y', '2004-12-13 19:47:22'),
 (46, 'DVI', 'dvi', 'dvi-dist.png', '', 'Y', '2004-12-13 19:47:22'),
 (35, 'TGZ', 'tgz', 'tgz-dist.png', '', 'Y', '2004-12-13 19:47:22'),
 (36, 'texte', 'txt', 'txt-dist.png', '', 'Y', '2004-12-13 19:47:22'),
 (49, 'RedHat/Mandrake/SuSE', 'rpm', 'rpm-dist.png', '', 'Y', '2004-12-13 19:47:22'),
 (38, 'Excel', 'xls', 'xls-dist.png', '', 'Y', '2004-12-13 19:47:22'),
 (39, 'XML', 'xml', 'xml-dist.png', '', 'Y', '2004-12-13 19:47:22'),
 (41, 'Zip', 'zip', 'zip-dist.png', '', 'Y', '2004-12-13 19:47:22'),
 (45, 'Debian', 'deb', 'deb-dist.png', '', 'Y', '2004-12-13 19:47:22'),
 (47, 'C header', 'h', '', '', 'Y', '2004-12-13 19:47:22'),
 (48, 'Pascal', 'pas', '', '', 'Y', '2004-12-13 19:47:22'),
 (50, 'OpenOffice Calc', 'sxc', 'sxc-dist.png', '', 'Y', '2004-12-13 19:47:22'),
 (51, 'LaTeX', 'tex', 'tex-dist.png', '', 'Y', '2004-12-13 19:47:22'),
 (52, 'GIMP multi-layer', 'xcf', 'xcf-dist.png', '', 'Y', '2004-12-13 19:47:22'),
 (53, 'JPEG', 'jpeg', 'jpg-dist.png', '', 'Y', '2005-03-07 22:23:17');";






$db->query($query) or die("Error inserting elements in table typedoc ".$query." ".$db->error());
	
}

if(!TableExists("glpi_docs")) {
	
$query = "CREATE TABLE glpi_docs (
  ID int(11) NOT NULL auto_increment,
  name varchar(255) NOT NULL default '',
  filename varchar(255) NOT NULL default '',
  rubrique int(11) NOT NULL default '0',
  mime varchar(30) NOT NULL default '',
  date_mod datetime NOT NULL default '0000-00-00 00:00:00',
  comment text NOT NULL,
  deleted enum('Y','N') NOT NULL default 'N',
  PRIMARY KEY  (ID),
  KEY rubrique (rubrique),
  KEY deleted (deleted),
  KEY date_mod (date_mod)
) TYPE=MyISAM;";

$db->query($query) or die("Error creating table docs ".$query." ".$db->error());
}

if(!TableExists("glpi_doc_device")) {
	
$query = "CREATE TABLE glpi_doc_device (
  ID int(11) NOT NULL auto_increment,
  FK_doc int(11) NOT NULL default '0',
  FK_device int(11) NOT NULL default '0',
  device_type tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (ID),
  UNIQUE KEY FK_doc (FK_doc,FK_device,device_type),
  KEY FK_doc_2 (FK_doc),
  KEY FK_device (FK_device,device_type)
) TYPE=MyISAM;";

$db->query($query) or die("Error creating table docs ".$query." ".$db->error());
}

if(!TableExists("glpi_dropdown_rubdocs")) {
	
$query = "CREATE TABLE glpi_dropdown_rubdocs (
  ID int(11) NOT NULL auto_increment,
  name varchar(255) default NULL,
  PRIMARY KEY  (ID)
) TYPE=MyISAM;";
$db->query($query) or die("Error creating table docs ".$query." ".$db->error());
}

if(!isIndex("glpi_contacts", "deleted")) {
$query = "ALTER TABLE `glpi_contacts` ADD INDEX `deleted` (`deleted`) ";
$db->query($query) or die("0.5 alter field deleted".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_contacts", "type")) {
$query = "ALTER TABLE `glpi_contacts` ADD INDEX `type` (`type`) ";
$db->query($query) or die("0.5 alter field type ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_event_log", "itemtype")) {
$query = "ALTER TABLE `glpi_event_log` ADD INDEX (`itemtype`) ";
$db->query($query) or die("0.5 alter field itemtype ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_followups", "date")) {
$query = "ALTER TABLE `glpi_followups` ADD INDEX (`date`) ";
$db->query($query) or die("0.5 alter field date ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_tracking", "category")) {
$query = "ALTER TABLE `glpi_tracking` ADD INDEX (`category`) ";
$db->query($query) or die("0.5 alter field category ".$lang["update"][90].$db->error());
}

if(!FieldExists("glpi_config","date_fiscale")) {
	$query = "ALTER TABLE `glpi_config` ADD `date_fiscale` date NOT NULL default '2005-12-31'";
	$db->query($query) or die("0.5 add field date_fiscale ".$lang["update"][90].$db->error());
}

if(!FieldExists("glpi_networking","ifmac")) {
	$query = "ALTER TABLE `glpi_networking` ADD `ifmac` char(30) NOT NULL default ''";
	$db->query($query) or die("0.5 add field ifmac ".$lang["update"][90].$db->error());
}

if(!FieldExists("glpi_networking","ifaddr")) {
	$query = "ALTER TABLE `glpi_networking` ADD `ifaddr` char(30) NOT NULL default ''";
	$db->query($query) or die("0.5 add field ifaddr ".$lang["update"][90].$db->error());
}

if(!TableExists("glpi_repair_item")) {

	$query = "CREATE TABLE glpi_repair_item (
	ID int(11) NOT NULL auto_increment,
	device_type tinyint(4) NOT NULL default '0', 
	id_device int(11) NOT NULL default '0', 
	PRIMARY KEY  (ID), 
	KEY device_type (device_type), 
	KEY device_type_2 (device_type,id_device)
	)TYPE=MyISAM;";

	$db->query($query) or die("0.5 create glpirepair_item table ".$lang["update"][90].$db->error());
}

if(TableExists("glpi_prefs")&&!FieldExists("glpi_prefs","username")) {
	
	if(isIndex("glpi_prefs", "user")) {
		$query = " ALTER TABLE `glpi_prefs` DROP INDEX `user`;";
		$db->query($query) or die("0.5 drop key user ".$lang["update"][90].$db->error());
	}

	$query = " ALTER TABLE `glpi_prefs` CHANGE `user` `username` VARCHAR(80) NOT NULL;";
	$db->query($query) or die("0.5 change user to username ".$lang["update"][90].$db->error());
	$query = "ALTER TABLE `glpi_prefs` ADD UNIQUE (`username`) ";
	$db->query($query) or die("0.5 alter field username ".$lang["update"][90].$db->error());
}

//Mise a jour 0.5 verification des prefs pour chaque user.
if (TableExists("glpi_prefs")){
	$query = "select ID, name from glpi_users";
	$query2 = "select ID, username from glpi_prefs";
	$result = $db->query($query);
	$result2 = $db->query($query2);
	if($db->numrows($result) != $db->numrows($result2)) { 
		$users = array();
		$i = 0;
		while ($line = $db->fetch_array($result2)) {
			$prefs[$i] = $line["username"];
			$i++;
		}
		while($line = $db->fetch_array($result)) {
			if(!in_array($line["name"],$prefs)) {
				$query_insert =  "INSERT INTO `glpi_prefs` (`username` , `tracking_order` , `language`) VALUES ('".$line["name"]."', 'no', 'french')";
				$db->query($query_insert) or die("glpi maj prefs ".$lang["update"][90].$db->error()); 
			}
		}
	}
	mysql_free_result($result);
	mysql_free_result($result2);
}


}

// Update from 0.5 to 0.51
function update05to051(){
global $lang;
$db = new DB;
	 echo "<p class='center'>Version 0.51 </p>";

/*******************************GLPI 0.51***********************************************/

if(!FieldExists("glpi_infocoms","facture")) {
	$query = "ALTER TABLE `glpi_infocoms` ADD `facture` char(255) NOT NULL default ''";
	$db->query($query) or die("0.51 add field facture ".$lang["update"][90].$db->error());
}

if(!FieldExists("glpi_enterprises","fax")) {
	$query = "ALTER TABLE `glpi_enterprises` ADD `fax` char(255) NOT NULL default ''";
	$db->query($query) or die("0.51 add field fax ".$lang["update"][90].$db->error());
}

if(!FieldExists("glpi_docs","link")) {
	$query = "ALTER TABLE `glpi_docs` ADD `link` char(255) NOT NULL default ''";
	$db->query($query) or die("0.51 add field fax ".$lang["update"][90].$db->error());
}

if(!TableExists("glpi_dropdown_contact_type")) {

$query = "CREATE TABLE glpi_dropdown_contact_type (
  ID int(11) NOT NULL auto_increment,
  name varchar(255) NOT NULL default '',
  PRIMARY KEY  (ID)
) TYPE=MyISAM;";

$db->query($query) or die("0.51 add table dropdown_contact_type ".$lang["update"][90].$db->error());

$query="INSERT INTO glpi_dropdown_contact_type (name) VALUES ('".$lang["financial"][43]."');";
$db->query($query) or die("0.51 add entries to dropdown_contact_type ".$lang["update"][90].$db->error());
$query="INSERT INTO glpi_dropdown_contact_type (name) VALUES ('".$lang["financial"][42]."');";
$db->query($query) or die("0.51 add entries to dropdown_contact_type ".$lang["update"][90].$db->error());
}

if(!FieldExists("glpi_config","cartridges_alarm")) {
	$query = "ALTER TABLE `glpi_config` ADD `cartridges_alarm` int(11) NOT NULL default '10'";
	$db->query($query) or die("0.51 add field cartridges_alarm ".$lang["update"][90].$db->error());
}

if(!TableExists("glpi_state_item")) {

	$query = "ALTER TABLE `glpi_repair_item` RENAME `glpi_state_item`;";
	$db->query($query) or die("0.51 alter glpi_state_item table name ".$lang["update"][90].$db->error());

	$query = "ALTER TABLE `glpi_state_item` ADD `state` INT DEFAULT '1';";
	$db->query($query) or die("0.51 add state field ".$lang["update"][90].$db->error());
}

if(!TableExists("glpi_dropdown_state")) {
	$query = "CREATE TABLE glpi_dropdown_state (
  ID int(11) NOT NULL auto_increment,
  name varchar(255) default NULL,
  PRIMARY KEY  (ID)
) TYPE=MyISAM;";
	$db->query($query) or die("0.51 add state field ".$lang["update"][90].$db->error());

}

}

// Update from 0.51x to 0.6
function update051to06(){
global $lang;
$db = new DB;
	 echo "<p class='center'>Version 0.6 </p>";

/*******************************GLPI 0.6***********************************************/
$query= "ALTER TABLE `glpi_tracking` CHANGE `category` `category` INT(11) DEFAULT '0' NOT NULL";
$db->query($query) or die("0.6 alter category tracking ".$lang["update"][90].$db->error());	

// state pour les template 
if(!FieldExists("glpi_state_item","is_template")) {
$query= "ALTER TABLE `glpi_state_item` ADD `is_template` ENUM('0', '1') DEFAULT '0' NOT NULL ;";
$db->query($query) or die("0.6 add is_template in state_item ".$lang["update"][90].$db->error());	
}


if(!TableExists("glpi_dropdown_cartridge_type")) {

$query = "CREATE TABLE glpi_dropdown_cartridge_type (
  ID int(11) NOT NULL auto_increment,
  name varchar(255) NOT NULL default '',
  PRIMARY KEY  (ID)
) TYPE=MyISAM;";

$db->query($query) or die("0.6 add table dropdown_cartridge_type ".$lang["update"][90].$db->error());

$query="INSERT INTO glpi_dropdown_cartridge_type (name) VALUES ('".$lang["cartridges"][11]."');";
$db->query($query) or die("0.6 add entries to dropdown_cartridge_type ".$lang["update"][90].$db->error());
$query="INSERT INTO glpi_dropdown_cartridge_type (name) VALUES ('".$lang["cartridges"][10]."');";
$db->query($query) or die("0.6 add entries to dropdown_cartridge_type ".$lang["update"][90].$db->error());
$query="INSERT INTO glpi_dropdown_cartridge_type (name) VALUES ('".$lang["cartridges"][37]."');";
$db->query($query) or die("0.6 add entries to dropdown_cartridge_type ".$lang["update"][90].$db->error());
}

// specific alarm pour les cartouches
if(!FieldExists("glpi_cartridges_type","alarm")) {
$query= "ALTER TABLE `glpi_cartridges_type` ADD `alarm` TINYINT DEFAULT '10' NOT NULL ;";
$db->query($query) or die("0.6 add alarm in cartridges_type ".$lang["update"][90].$db->error());	
}

// email for enterprises
if(!FieldExists("glpi_enterprises","email")) {
$query= "ALTER TABLE `glpi_enterprises` ADD `email` VARCHAR(255) NOT NULL;";
$db->query($query) or die("0.6 add email in enterprises ".$lang["update"][90].$db->error());	
}

// ldap_port for config
if(!FieldExists("glpi_config","ldap_port")) {
$query= "ALTER TABLE `glpi_config` ADD `ldap_port` VARCHAR(10) DEFAULT '389' NOT NULL AFTER `ID` ;";
$db->query($query) or die("0.6 add ldap_port in config ".$lang["update"][90].$db->error());	
}

// CAS configuration
if(!FieldExists("glpi_config","cas_host")) {
$query= "ALTER TABLE `glpi_config` ADD `cas_host` VARCHAR(255) NOT NULL ,
ADD `cas_port` VARCHAR(255) NOT NULL ,
ADD `cas_uri` VARCHAR(255) NOT NULL ;";
$db->query($query) or die("0.6 add cas config in config ".$lang["update"][90].$db->error());	
}

// Limit Item for contracts and correct template bug 
if(!FieldExists("glpi_contracts","device_countmax")) {
$query= "ALTER TABLE `glpi_contracts` ADD `device_countmax` INT DEFAULT '0' NOT NULL ;";
$db->query($query) or die("0.6 add device_countmax in contracts ".$lang["update"][90].$db->error());	
}

if(!FieldExists("glpi_contract_device","is_template")) {
$query= "ALTER TABLE `glpi_contract_device` ADD `is_template` ENUM('0', '1') DEFAULT '0' NOT NULL ;";
$db->query($query) or die("0.6 add is_template in contract_device ".$lang["update"][90].$db->error());
//$query= " ALTER TABLE `glpi_contract_device` ADD INDEX (`is_template `) ";
//$db->query($query) or die("0.6 alter is_template in contract_device ".$lang["update"][90].$db->error());	
// TODO SET TO 1 the template item
}

if(!FieldExists("glpi_doc_device","is_template")) {
$query= "ALTER TABLE `glpi_doc_device` ADD `is_template` ENUM('0', '1') DEFAULT '0' NOT NULL ;";
$db->query($query) or die("0.6 add is_template in doc_device ".$lang["update"][90].$db->error());	
$query= "ALTER TABLE `glpi_doc_device` ADD INDEX (`is_template`) ;";
$db->query($query) or die("0.6 alter is_template in doc_device ".$lang["update"][90].$db->error());	
// TODO SET TO 1 the template item
}

// Contract Type to dropdown
if(!TableExists("glpi_dropdown_contract_type")) {

$query = "CREATE TABLE glpi_dropdown_contract_type (
  ID int(11) NOT NULL auto_increment,
  name varchar(255) NOT NULL default '',
  PRIMARY KEY  (ID)
) TYPE=MyISAM;";

$db->query($query) or die("0.6 add table dropdown_contract_type ".$lang["update"][90].$db->error());

$query="INSERT INTO glpi_dropdown_contract_type (name) VALUES ('".$lang["financial"][50]."');";
$db->query($query) or die("0.6 add entries to dropdown_contract_type ".$lang["update"][90].$db->error());
$query="INSERT INTO glpi_dropdown_contract_type (name) VALUES ('".$lang["financial"][51]."');";
$db->query($query) or die("0.6 add entries to dropdown_contract_type ".$lang["update"][90].$db->error());
$query="INSERT INTO glpi_dropdown_contract_type (name) VALUES ('".$lang["financial"][52]."');";
$db->query($query) or die("0.6 add entries to dropdown_contract_type ".$lang["update"][90].$db->error());
$query="INSERT INTO glpi_dropdown_contract_type (name) VALUES ('".$lang["financial"][53]."');";
$db->query($query) or die("0.6 add entries to dropdown_contract_type ".$lang["update"][90].$db->error());
$query="INSERT INTO glpi_dropdown_contract_type (name) VALUES ('".$lang["financial"][54]."');";
$db->query($query) or die("0.6 add entries to dropdown_contract_type ".$lang["update"][90].$db->error());
$query="INSERT INTO glpi_dropdown_contract_type (name) VALUES ('".$lang["financial"][55]."');";
$db->query($query) or die("0.6 add entries to dropdown_contract_type ".$lang["update"][90].$db->error());
$query="INSERT INTO glpi_dropdown_contract_type (name) VALUES ('".$lang["financial"][56]."');";
$db->query($query) or die("0.6 add entries to dropdown_contract_type ".$lang["update"][90].$db->error());

}

//// Update author and assign from tracking / followups
if(!FieldExists("glpi_tracking","assign_type")) {

	// Create assin_type field
	$query= "ALTER TABLE `glpi_tracking` ADD `assign_type` TINYINT DEFAULT '0' NOT NULL AFTER `assign` ;";
	$db->query($query) or die("0.6 add assign_type in tracking ".$lang["update"][90].$db->error());	

	$users=array();
	// Load All users
	$query="SELECT ID, name FROM glpi_users";
	$result=$db->query($query);
	while($line = $db->fetch_array($result)) {
		$users[$line["name"]]=$line["ID"];
	}
	mysql_free_result($result);
	// Load tracking authors tables
	$authors=array();
	$query="SELECT ID, author FROM glpi_tracking";
	$result=$db->query($query);
	while($line = $db->fetch_array($result)) {
		$authors[$line["ID"]]=$line["author"];
	}
	mysql_free_result($result);
	// Update authors tracking
	$query= "ALTER TABLE `glpi_tracking` CHANGE `author` `author` INT(11) DEFAULT '0' NOT NULL";
	$db->query($query) or die("0.6 alter author in tracking ".$lang["update"][90].$db->error());	

	if (count($authors)>0)
	foreach ($authors as $ID => $val){
		if (isset($users[$val])){
			$query="UPDATE glpi_tracking SET author='".$users[$val]."' WHERE ID='$ID'";
			$db->query($query);
		}
	}
	unset($authors);
	
	$assign=array();
	// Load tracking assign tables
	$query="SELECT ID, assign FROM glpi_tracking";
	$result=$db->query($query);
	while($line = $db->fetch_array($result)) {
		$assign[$line["ID"]]=$line["assign"];
	}
	mysql_free_result($result);

	// Update assign tracking
	$query= "ALTER TABLE `glpi_tracking` CHANGE `assign` `assign` INT(11) DEFAULT '0' NOT NULL";
	$db->query($query) or die("0.6 alter assign in tracking ".$lang["update"][90].$db->error());	

	if (count($assign)>0)
	foreach ($assign as $ID => $val){
		if (isset($users[$val])){
			$query="UPDATE glpi_tracking SET assign='".$users[$val]."', assign_type='".USER_TYPE."' WHERE ID='$ID'";
			$db->query($query);
		}
	}
	unset($assign);

	$authors=array();
	// Load followup authors tables
	$query="SELECT ID, author FROM glpi_followups";
	$result=$db->query($query);
	while($line = $db->fetch_array($result)) {
		$authors[$line["ID"]]=$line["author"];
	}
	mysql_free_result($result);
	
	// Update authors tracking
	$query= "ALTER TABLE `glpi_followups` CHANGE `author` `author` INT(11) DEFAULT '0' NOT NULL";
	$db->query($query) or die("0.6 alter author in followups ".$lang["update"][90].$db->error());	

	if (count($authors)>0)
	foreach ($authors as $ID => $val){
		if (isset($users[$val])){
			$query="UPDATE glpi_followups SET author='".$users[$val]."' WHERE ID='$ID'";
			$db->query($query);
		}
	}
	unset($authors);

	// Update Enterprise Tracking
	$query="SELECT computer, ID FROM glpi_tracking WHERE device_type='".ENTERPRISE_TYPE."'";
	$result=$db->query($query);

	if ($db->numrows($result)>0)
	while($line = $db->fetch_array($result)) {
		$query="UPDATE glpi_tracking SET assign='".$line["computer"]."', assign_type='".ENTERPRISE_TYPE."', device_type='0', computer='0' WHERE ID='".$line["ID"]."'";
		$db->query($query);
	}
	mysql_free_result($result);
}

// Add planning feature 

if(!TableExists("glpi_tracking_planning")) {

$query = "CREATE TABLE `glpi_tracking_planning` (
  `ID` bigint(20) NOT NULL auto_increment,
  `id_tracking` int(11) NOT NULL default '0',
  `id_assign` int(11) NOT NULL default '0',
  `begin` datetime NOT NULL default '0000-00-00 00:00:00',
  `end` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`ID`),
  KEY `id_tracking` (`id_tracking`),
  KEY `begin` (`begin`),
  KEY `end` (`end`)
) TYPE=MyISAM ;";

$db->query($query) or die("0.6 add table glpi_tracking_planning ".$lang["update"][90].$db->error());
}

if(!FieldExists("glpi_config","planning_begin")) {
$query="ALTER TABLE `glpi_config` ADD `planning_begin` TIME DEFAULT '08:00:00' NOT NULL";

$db->query($query) or die("0.6 add planning begin in config".$lang["update"][90].$db->error());
}
if(!FieldExists("glpi_config","planning_end")) {
$query="ALTER TABLE `glpi_config` ADD `planning_end` TIME DEFAULT '20:00:00' NOT NULL";

$db->query($query) or die("0.6 add planning end in config".$lang["update"][90].$db->error());
}

// Merge glpi_users and glpi_prefs
if(!FieldExists("glpi_users","language")) {
	// Create fields
	$query="ALTER TABLE `glpi_users` ADD `tracking_order` ENUM('yes', 'no') DEFAULT 'no' NOT NULL ;";
	$db->query($query) or die("0.6 add tracking_order in users".$lang["update"][90].$db->error());
	$query="ALTER TABLE `glpi_users` ADD `language` VARCHAR(255) NOT NULL DEFAULT 'english';";
	$db->query($query) or die("0.6 add language in users".$lang["update"][90].$db->error());
	
	// Move data
	$query="SELECT * from glpi_prefs";
	$result=$db->query($query);
	if ($db->numrows($result)>0)
	while ($data=$db->fetch_array($result)){
	$query2="UPDATE glpi_users SET language='".$data['language']."', tracking_order='".$data['tracking_order']."' WHERE name='".$data['username']."';";	
	$db->query($query2) or die("0.6 move pref to users".$lang["update"][90].$db->error());	
	}
	mysql_free_result($result);
	// Drop glpi_prefs
	$query="DROP TABLE `glpi_prefs`;";
	$db->query($query) or die("0.6 drop glpi_prefs".$lang["update"][90].$db->error());
	
	
}

// Create glpi_dropdown_ram_type
if(!TableExists("glpi_dropdown_ram_type")) {
	$query = "CREATE TABLE `glpi_dropdown_ram_type` (
  	`ID` int(11) NOT NULL auto_increment,
  	`name` varchar(255) NOT NULL default '',
  	PRIMARY KEY  (`ID`)
	) TYPE=MyISAM;";

	$db->query($query) or die("0.6 add table glpi_dropdown_ram_type ".$lang["update"][90].$db->error());
	$query="ALTER TABLE `glpi_device_ram` ADD `new_type` INT(11) DEFAULT '0' NOT NULL ;";
	$db->query($query) or die("0.6 create new type field for glpi_device_ram ".$lang["update"][90].$db->error());
	$query="INSERT INTO `glpi_dropdown_ram_type` (`name`) VALUES ('EDO');";
	$db->query($query) or die("0.6 insert value in glpi_dropdown_ram ".$lang["update"][90].$db->error());
	$query="INSERT INTO `glpi_dropdown_ram_type` (`name`) VALUES ('DDR');";
	$db->query($query) or die("0.6 insert value in glpi_dropdown_ram ".$lang["update"][90].$db->error());
	$query="INSERT INTO `glpi_dropdown_ram_type` (`name`) VALUES ('SDRAM');";
	$db->query($query) or die("0.6 insert value in glpi_dropdown_ram ".$lang["update"][90].$db->error());
	$query="INSERT INTO `glpi_dropdown_ram_type` (`name`) VALUES ('SDRAM-2');";
	$db->query($query) or die("0.6 insert value in glpi_dropdown_ram ".$lang["update"][90].$db->error());

	// Get values
	$query="SELECT * from glpi_dropdown_ram_type";
	$result=$db->query($query);
	$val=array();
	while ($data=$db->fetch_array($result)){
		$val[$data['name']]=$data['ID'];
	}	
	mysql_free_result($result);
	
	// Update glpi_device_ram
	$query="SELECT * from glpi_device_ram";
	$result=$db->query($query);
	if ($db->numrows($result)>0)
	while ($data=$db->fetch_array($result)){
		$query2="UPDATE glpi_device_ram SET new_type='".$val[$data['type']]."' WHERE ID ='".$data['ID']."';";
		$db->query($query2);
	}
	mysql_free_result($result);
	// ALTER glpi_device_ram
	$query="ALTER TABLE `glpi_device_ram` DROP `type`;";
	$db->query($query) or die("0.6 drop type in glpi_dropdown_ram ".$lang["update"][90].$db->error());
	$query="ALTER TABLE `glpi_device_ram` CHANGE `new_type` `type` INT(11) DEFAULT '0' NOT NULL ";
	$db->query($query) or die("0.6 rename new_type in glpi_dropdown_ram ".$lang["update"][90].$db->error());
	
	
}

// Create external links
if(!TableExists("glpi_links")) {
	$query = "CREATE TABLE `glpi_links` (
  	`ID` int(11) NOT NULL auto_increment,
  	`name` varchar(255) NOT NULL default '',
  	PRIMARY KEY  (`ID`)
	) TYPE=MyISAM;";

	$db->query($query) or die("0.6 add table glpi_links ".$lang["update"][90].$db->error());
}

if(!TableExists("glpi_links_device")) {

	$query = "CREATE TABLE `glpi_links_device` (
  	`ID` int(11) NOT NULL auto_increment,
  	`FK_links` int(11) NOT NULL default '0',
	`device_type` int(11) NOT NULL default '0',
  	PRIMARY KEY  (`ID`),
	KEY `device_type` (`device_type`),
	KEY `FK_links` (`FK_links`),
	UNIQUE `device_type_2` (`device_type`,`FK_links`)
	) TYPE=MyISAM;";

	$db->query($query) or die("0.6 add table glpi_links_device ".$lang["update"][90].$db->error());
}

// Initial count page for printer
if(!FieldExists("glpi_printers","initial_pages")) {
	$query="ALTER TABLE `glpi_printers` ADD `initial_pages` VARCHAR(30) DEFAULT '0' NOT NULL ;";
	$db->query($query) or die("0.6 add initial_pages in printers".$lang["update"][90].$db->error());
}

// Auto assign intervention
if(!FieldExists("glpi_config","auto_assign")) {
	$query="ALTER TABLE `glpi_config` ADD `auto_assign` ENUM('0', '1') DEFAULT '0' NOT NULL ;";
	$db->query($query) or die("0.6 add auto_assign in config".$lang["update"][90].$db->error());
}

// Create glpi_dropdown_network
if(!TableExists("glpi_dropdown_network")) {
	$query = "CREATE TABLE `glpi_dropdown_network` (
  	`ID` int(11) NOT NULL auto_increment,
  	`name` varchar(255) NOT NULL default '',
  	PRIMARY KEY  (`ID`)
	) TYPE=MyISAM;";
	$db->query($query) or die("0.6 add table glpi_dropdown_network ".$lang["update"][90].$db->error());
}
if(!FieldExists("glpi_computers","network")) {
	$query="ALTER TABLE `glpi_computers` ADD `network` INT(11) DEFAULT '0' NOT NULL AFTER `location` ;";
	$db->query($query) or die("0.6 a network in computers".$lang["update"][90].$db->error());
}
if(!FieldExists("glpi_printers","network")) {
	$query="ALTER TABLE `glpi_printers` ADD `network` INT(11) DEFAULT '0' NOT NULL AFTER `location` ;";
	$db->query($query) or die("0.6 add network in printers".$lang["update"][90].$db->error());
}
if(!FieldExists("glpi_networking","network")) {	
	$query="ALTER TABLE `glpi_networking` ADD `network` INT(11) DEFAULT '0' NOT NULL AFTER `location` ;";
	$db->query($query) or die("0.6 a network in networking".$lang["update"][90].$db->error());
}

// Create glpi_dropdown_domain
if(!TableExists("glpi_dropdown_domain")) {
	$query = "CREATE TABLE `glpi_dropdown_domain` (
  	`ID` int(11) NOT NULL auto_increment,
  	`name` varchar(255) NOT NULL default '',
  	PRIMARY KEY  (`ID`)
	) TYPE=MyISAM;";
	$db->query($query) or die("0.6 add table glpi_dropdown_domain ".$lang["update"][90].$db->error());
}
if(!FieldExists("glpi_computers","domain")) {
	$query="ALTER TABLE `glpi_computers` ADD `domain` INT(11) DEFAULT '0' NOT NULL AFTER `location` ;";
	$db->query($query) or die("0.6 a domain in computers".$lang["update"][90].$db->error());
}
if(!FieldExists("glpi_printers","domain")) {
	$query="ALTER TABLE `glpi_printers` ADD `domain` INT(11) DEFAULT '0' NOT NULL AFTER `location` ;";
	$db->query($query) or die("0.6 a domain in printers".$lang["update"][90].$db->error());
}
if(!FieldExists("glpi_networking","domain")) {
	$query="ALTER TABLE `glpi_networking` ADD `domain` INT(11) DEFAULT '0' NOT NULL AFTER `location` ;";
	$db->query($query) or die("0.6 a domain in networking".$lang["update"][90].$db->error());
}

// Create glpi_dropdown_vlan
if(!TableExists("glpi_dropdown_vlan")) {
	$query = "CREATE TABLE `glpi_dropdown_vlan` (
  	`ID` int(11) NOT NULL auto_increment,
  	`name` varchar(255) NOT NULL default '',
  	PRIMARY KEY  (`ID`)
	) TYPE=MyISAM;";
	$db->query($query) or die("0.6 add table glpi_dropdown_vlan ".$lang["update"][90].$db->error());
}

if(!TableExists("glpi_networking_vlan")) {
	$query = "CREATE TABLE `glpi_networking_vlan` (
  	`ID` int(11) NOT NULL auto_increment,
  	`FK_port` int(11) NOT NULL default '0',
	  `FK_vlan` int(11) NOT NULL default '0',
  	PRIMARY KEY  (`ID`),
	  KEY `FK_port` (`FK_port`),
	  KEY `FK_vlan` (`FK_vlan`),
	  UNIQUE `FK_port_2` (`FK_port`,`FK_vlan`)
	  ) TYPE=MyISAM;";
	$db->query($query) or die("0.6 add table glpi_networking_vlan ".$lang["update"][90].$db->error());
}

// Global Peripherals
if(!FieldExists("glpi_peripherals","is_global")) {
	$query="ALTER TABLE `glpi_peripherals` ADD `is_global` ENUM('0', '1') DEFAULT '0' NOT NULL AFTER `FK_glpi_enterprise` ;";
	$db->query($query) or die("0.6 add is_global in peripherals".$lang["update"][90].$db->error());
}

// Global Monitors
if(!FieldExists("glpi_monitors","is_global")) {
	$query="ALTER TABLE `glpi_monitors` ADD `is_global` ENUM('0', '1') DEFAULT '0' NOT NULL AFTER `FK_glpi_enterprise` ;";
	$db->query($query) or die("0.6 add is_global in peripherals".$lang["update"][90].$db->error());
}

// Mailing Resa
if(!FieldExists("glpi_config","mailing_resa_admin")) {
	$query="ALTER TABLE `glpi_config` ADD `mailing_resa_admin` VARCHAR(200) NOT NULL DEFAULT '1' AFTER `admin_email` ;";
	$db->query($query) or die("0.6 add mailing_resa_admin in config".$lang["update"][90].$db->error());
}
if(!FieldExists("glpi_config","mailing_resa_user")) {
	$query="ALTER TABLE `glpi_config` ADD `mailing_resa_user` VARCHAR(200) NOT NULL DEFAULT '1' AFTER `admin_email` ;";
	$db->query($query) or die("0.6 add mailing_resa_user in config".$lang["update"][90].$db->error());
}
if(!FieldExists("glpi_config","mailing_resa_all_admin")) {
	$query="ALTER TABLE `glpi_config` ADD `mailing_resa_all_admin` VARCHAR(200) NOT NULL DEFAULT '0' AFTER `admin_email` ;";
	$db->query($query) or die("0.6 add mailing_resa_all_admin in config".$lang["update"][90].$db->error());
}

// Mod�le ordinateurs
if(!TableExists("glpi_dropdown_model")) {
	// model=type pour faciliter la gestion en post mise � jour : ya plus qu'a deleter les elements non voulu
	// cela conviendra a tout le monde en fonction de l'utilisation du champ type

	$query="ALTER TABLE `glpi_type_computers` RENAME `glpi_dropdown_model` ;";
	$db->query($query) or die("0.6 rename table glpi_type_computers ".$lang["update"][90].$db->error());
	
	$query = "CREATE TABLE `glpi_type_computers` (
  	`ID` int(11) NOT NULL auto_increment,
  	`name` varchar(255) NOT NULL default '',
  	PRIMARY KEY  (`ID`)
	) TYPE=MyISAM;";

	$db->query($query) or die("0.6 add table glpi_type_computers ".$lang["update"][90].$db->error());

	// copie model dans type
	$query="SELECT * FROM glpi_dropdown_model";
	$result=$db->query($query);	
	if ($db->numrows($result)>0)
	while ($data=$db->fetch_array($result)){
		$query="INSERT INTO `glpi_type_computers` (`ID`,`name`) VALUES ('".$data['ID']."','".addslashes($data['name'])."');";
		$db->query($query) or die("0.6 insert value in glpi_type_computers ".$lang["update"][90].$db->error());		
	}
	mysql_free_result($result);

	$query="INSERT INTO `glpi_type_computers` (`name`) VALUES ('".$lang["computers"][28]."');";
	$db->query($query) or die("0.6 insert value in glpi_type_computers ".$lang["update"][90].$db->error());
	$serverid=$db->insert_id();

	// Type -> mod�le
	$query="ALTER TABLE `glpi_computers` CHANGE `type` `model` INT(11) DEFAULT NULL ";
	$db->query($query) or die("0.6 add model in computers".$lang["update"][90].$db->error());
	
	$query="ALTER TABLE `glpi_computers` ADD `type` INT(11) DEFAULT NULL AFTER `model` ;";
	$db->query($query) or die("0.6 add model in computers".$lang["update"][90].$db->error());
	
	// Update server values and drop flags_server
	$query="UPDATE glpi_computers SET type='$serverid' where flags_server='1'";
	$db->query($query) or die("0.6 update type of computers".$lang["update"][90].$db->error());
	
	$query="ALTER TABLE `glpi_computers` DROP `flags_server`;";
	$db->query($query) or die("0.6 drop type in glpi_dropdown_ram ".$lang["update"][90].$db->error());

}

if(!TableExists("glpi_consumables_type")) {

$query = "CREATE TABLE `glpi_consumables_type` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
  `ref` varchar(255) NOT NULL default '',
  `location` int(11) NOT NULL default '0',
  `type` tinyint(4) NOT NULL default '0',
  `FK_glpi_enterprise` int(11) NOT NULL default '0',
  `tech_num` int(11) default '0',
  `deleted` enum('Y','N') NOT NULL default 'N',
  `comments` text NOT NULL,
  `alarm` tinyint(4) NOT NULL default '10',
  PRIMARY KEY  (`ID`),
  KEY `FK_glpi_enterprise` (`FK_glpi_enterprise`),
  KEY `tech_num` (`tech_num`),
  KEY `deleted` (`deleted`)
) TYPE=MyISAM;";

	$db->query($query) or die("0.6 add table glpi_consumables_type ".$lang["update"][90].$db->error());

$query = "CREATE TABLE `glpi_consumables` (
  `ID` int(11) NOT NULL auto_increment,
  `FK_glpi_consumables_type` int(11) default NULL,
  `date_in` date default NULL,
  `date_out` date default NULL,
  PRIMARY KEY  (`ID`),
  KEY `FK_glpi_cartridges_type` (`FK_glpi_consumables_type`),
  KEY `date_in` (`date_in`),
  KEY `date_out` (`date_out`)
) TYPE=MyISAM;";

	$db->query($query) or die("0.6 add table glpi_consumables ".$lang["update"][90].$db->error());

	$query = "CREATE TABLE `glpi_dropdown_consumable_type` (
  	`ID` int(11) NOT NULL auto_increment,
  	`name` varchar(255) NOT NULL default '',
  	PRIMARY KEY  (`ID`)
	) TYPE=MyISAM;";

	$db->query($query) or die("0.6 add table glpi_dropdown_consumable_type ".$lang["update"][90].$db->error());
		
}

// HDD connect type
if(!TableExists("glpi_dropdown_hdd_type")) {
	$query = "CREATE TABLE `glpi_dropdown_hdd_type` (
  	`ID` int(11) NOT NULL auto_increment,
  	`name` varchar(255) NOT NULL default '',
  	PRIMARY KEY  (`ID`)
	) TYPE=MyISAM;";

	$db->query($query) or die("0.6 add table glpi_dropdown_hdd_type ".$lang["update"][90].$db->error());
	
	$query="INSERT INTO `glpi_dropdown_hdd_type` (`name`) VALUES ('IDE');";
	$db->query($query) or die("0.6 insert value in glpi_dropdown_hdd_type ".$lang["update"][90].$db->error());
	$query="INSERT INTO `glpi_dropdown_hdd_type` (`name`) VALUES ('SATA');";
	$db->query($query) or die("0.6 insert value in glpi_dropdown_hdd_type ".$lang["update"][90].$db->error());
	$query="INSERT INTO `glpi_dropdown_hdd_type` (`name`) VALUES ('SCSI');";
	$db->query($query) or die("0.6 insert value in glpi_dropdown_hdd_type ".$lang["update"][90].$db->error());

	// Insertion des enum dans l'ordre - le alter garde donc les bonne valeurs
	$query="ALTER TABLE `glpi_device_hdd` CHANGE `interface` `interface` INT(11) DEFAULT '0' NOT NULL";
	$db->query($query) or die("0.6 alter interface of  glpi_device_hdd".$lang["update"][90].$db->error());
}

}

// Update from 0.6 to 0.65
function update06to065(){
global $lang;
$db = new DB;
 echo "<p class='center'>Version 0.65 </p>";

if(!isIndex("glpi_networking_ports", "on_device_2")) {
	$query = "ALTER TABLE `glpi_networking_ports` ADD INDEX (`on_device`) ";
	$db->query($query) or die("0.65 ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_networking_ports", "device_type")) {
	$query = "ALTER TABLE `glpi_networking_ports` ADD INDEX (`device_type`) ";
	$db->query($query) or die("0.65 ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_computer_device", "FK_device")) {
	$query = "ALTER TABLE `glpi_computer_device` ADD INDEX (`FK_device`) ";
	$db->query($query) or die("0.65 ".$lang["update"][90].$db->error());
}
	
// Field for public FAQ
if(!FieldExists("glpi_config","public_faq")) {
	$query="ALTER TABLE `glpi_config` ADD `public_faq` ENUM( '0', '1' ) NOT NULL AFTER `auto_assign` ;";
	$db->query($query) or die("0.65 add public_faq in config".$lang["update"][90].$db->error());
}

// Optimize amort_type field
if(FieldExists("glpi_infocoms","amort_type")) {
	$query2="UPDATE `glpi_infocoms` SET `amort_type`='0' WHERE `amort_type` = '';";
	$db->query($query2) or die("0.65 update amort_type='' in tracking".$lang["update"][90].$db->error());

	$query="ALTER TABLE `glpi_infocoms` CHANGE `amort_type` `amort_type` tinyint(4) NOT NULL DEFAULT '0'";
	$db->query($query) or die("0.65 alter amort_type in infocoms".$lang["update"][90].$db->error());
}
if(!TableExists("glpi_display")) {
	$query="CREATE TABLE glpi_display (
  ID int(11) NOT NULL auto_increment,
  type smallint(6) NOT NULL default '0',
  num smallint(6) NOT NULL default '0',
  rank smallint(6) NOT NULL default '0',
  PRIMARY KEY  (ID),
  UNIQUE KEY `type_2` (`type`,`num`),
   KEY type (type),
  KEY rank (rank),
  KEY num (num)
) TYPE=MyISAM;";
	$db->query($query) or die("0.65 add glpi_display table".$lang["update"][90].$db->error());

// TEMPORARY : ADD ITEMS TO DISPLAY TABLE : TO DEL OR TO 

$query="INSERT INTO `glpi_display` VALUES (32, 1, 4, 4),
(34, 1, 6, 6),
(33, 1, 5, 5),
(31, 1, 8, 3),
(30, 1, 23, 2),
(86, 12, 3, 1),
(49, 4, 31, 1),
(50, 4, 23, 2),
(51, 4, 3, 3),
(52, 4, 4, 4),
(44, 3, 31, 1),
(38, 2, 31, 1),
(39, 2, 23, 2),
(45, 3, 23, 2),
(46, 3, 3, 3),
(63, 6, 4, 3),
(62, 6, 5, 2),
(61, 6, 23, 1),
(83, 11, 4, 2),
(82, 11, 3, 1),
(57, 5, 3, 3),
(56, 5, 23, 2),
(55, 5, 31, 1),
(29, 1, 31, 1),
(35, 1, 3, 7),
(36, 1, 19, 8),
(37, 1, 17, 9),
(40, 2, 3, 3),
(41, 2, 4, 4),
(42, 2, 11, 6),
(43, 2, 9, 7),
(47, 3, 4, 4),
(48, 3, 9, 6),
(53, 4, 9, 6),
(54, 4, 7, 7),
(58, 5, 4, 4),
(59, 5, 9, 6),
(60, 5, 7, 7),
(64, 7, 3, 1),
(65, 7, 4, 2),
(66, 7, 5, 3),
(67, 7, 6, 4),
(68, 7, 9, 5),
(69, 8, 9, 1),
(70, 8, 3, 2),
(71, 8, 4, 3),
(72, 8, 5, 4),
(73, 8, 10, 5),
(74, 8, 6, 6),
(75, 10, 4, 1),
(76, 10, 3, 2),
(77, 10, 5, 3),
(78, 10, 6, 4),
(79, 10, 7, 5),
(80, 10, 11, 6),
(84, 11, 5, 3),
(85, 11, 6, 4),
(88, 12, 6, 2),
(89, 12, 4, 3),
(90, 12, 5, 4),
(91, 13, 3, 1),
(92, 13, 4, 2),
(93, 13, 7, 3),
(94, 13, 5, 4),
(95, 13, 6, 5),
(96, 15, 3, 1),
(97, 15, 4, 2),
(98, 15, 5, 3),
(99, 15, 6, 4),
(100, 15, 7, 5),
(101, 17, 3, 1),
(102, 17, 4, 2),
(103, 17, 5, 3),
(104, 17, 6, 4),
(105, 2, 40, 5),
(106, 3, 40, 5),
(107, 4, 40, 5),
(108, 5, 40, 5),
(109, 15, 8, 6),
(110, 23, 31, 1),
(111, 23, 23, 2),
(112, 23, 3, 3),
(113, 23, 4, 4),
(114, 23, 40, 5),
(115, 23, 9, 6),
(116, 23, 7, 7);";

$db->query($query);
}


if(!FieldExists("glpi_config","ldap_login")) {
	$query="ALTER TABLE `glpi_config` ADD `ldap_login` VARCHAR( 200 ) NOT NULL DEFAULT 'uid' AFTER `ldap_condition`;";
	$db->query($query) or die("0.65 add url in config".$lang["update"][90].$db->error());
}

if(!FieldExists("glpi_config","url_base")) {
	$query="ALTER TABLE `glpi_config` ADD `url_base` VARCHAR( 255 ) NOT NULL ;";
	$db->query($query) or die("0.65 add url in config".$lang["update"][90].$db->error());

	$query="ALTER TABLE `glpi_config` ADD `url_in_mail` ENUM( '0', '1' ) NOT NULL ;";
	$db->query($query) or die("0.65 add url_in_mail in config".$lang["update"][90].$db->error());

	$query="UPDATE glpi_config SET url_base='".ereg_replace("/install.php","",$_SERVER['HTTP_REFERER'])."' WHERE ID='1'";
	$db->query($query) or die(" url ".$lang["update"][90].$db->error());
}

if(!FieldExists("glpi_config","text_login")) {
	$query="ALTER TABLE `glpi_config` ADD `text_login` TEXT NOT NULL ;";
	$db->query($query) or die("0.65 add text_login in config".$lang["update"][90].$db->error());
}


if(!FieldExists("glpi_config","auto_update_check")) {
	$query="ALTER TABLE `glpi_config` ADD `auto_update_check` SMALLINT DEFAULT '0' NOT NULL ,
			ADD `last_update_check` DATE DEFAULT '".date("Y-m-d")."' NOT NULL, ADD `founded_new_version` VARCHAR( 10 ) NOT NULL ;";
	$db->query($query) or die("0.65 add auto_login_check in config".$lang["update"][90].$db->error());
}

//// Tracking 
if(FieldExists("glpi_tracking","status")) {
	$already_done=false;
	if ($result = $db->query("show fields from glpi_tracking"))
	while ($data=$db->fetch_array($result)){
	if ($data["Field"]=="status"&&ereg("done",$data["Type"]))
		$already_done=true;
	}
	
	if (!$already_done)	{
		$query="ALTER TABLE `glpi_tracking` CHANGE `status` `status` ENUM( 'new', 'old', 'old_done', 'assign', 'plan', 'old_notdone', 'waiting' ) DEFAULT 'new' NOT NULL ;";
		$db->query($query) or die("0.65 alter status in tracking".$lang["update"][90].$db->error());

		$query2=" UPDATE `glpi_tracking` SET status='old_done' WHERE status <> 'new';";
		$db->query($query2) or die("0.65 update status=old in tracking".$lang["update"][90].$db->error());	

		$query3=" UPDATE `glpi_tracking` SET status='assign' WHERE status='new' AND assign <> '0';";
		$db->query($query3) or die("0.65 update status=assign in tracking".$lang["update"][90].$db->error());	

		$query4="ALTER TABLE `glpi_tracking` CHANGE `status` `status` ENUM( 'new', 'old_done', 'assign', 'plan', 'old_notdone', 'waiting' ) DEFAULT 'new' NOT NULL ;";
		$db->query($query4) or die("0.65 alter status in tracking".$lang["update"][90].$db->error());
	}
}

if(FieldExists("glpi_tracking_planning","id_assign")) {
	$query="ALTER TABLE `glpi_tracking_planning` ADD INDEX ( `id_assign` ) ;";
	$db->query($query) or die("0.65 add index for id_assign in tracking_planning".$lang["update"][90].$db->error());
}
if(FieldExists("glpi_tracking","emailupdates")) {
	$query2=" UPDATE `glpi_tracking` SET `emailupdates`='no' WHERE `emailupdates`='';";
	$db->query($query2) or die("0.65 update emailupdate='' in tracking".$lang["update"][90].$db->error());
	$query="ALTER TABLE `glpi_tracking` CHANGE `emailupdates` `emailupdates` ENUM( 'yes', 'no' ) DEFAULT 'no' NOT NULL;";
	$db->query($query) or die("0.65 alter emailupdates in tracking".$lang["update"][90].$db->error());
}

if(!FieldExists("glpi_followups","private")) {
	$query="ALTER TABLE `glpi_followups` ADD `private` INT( 1 ) DEFAULT '0' NOT NULL;";
	$db->query($query) or die("0.65 add private in followups".$lang["update"][90].$db->error());
}

if(!FieldExists("glpi_followups","realtime")) {
	$query="ALTER TABLE `glpi_followups` ADD `realtime` FLOAT DEFAULT '0' NOT NULL ;";
	$db->query($query) or die("0.65 add realtime in followups".$lang["update"][90].$db->error());
}
if(!FieldExists("glpi_config","mailing_attrib_attrib")) {
	$query="ALTER TABLE `glpi_config` ADD `mailing_attrib_attrib` tinyint(4) DEFAULT '1' AFTER `mailing_finish_user` ;";
	$db->query($query) or die("0.65 add mailing_attrib_attrib in config".$lang["update"][90].$db->error());
}


 
if(!FieldExists("glpi_tracking_planning","id_followup")) {
	$query="ALTER TABLE `glpi_tracking_planning` ADD `id_followup` INT DEFAULT '0' NOT NULL AFTER `id_tracking` ;";
	$db->query($query) or die("0.65 add id_followup in tracking_planning".$lang["update"][90].$db->error());
	$query=" ALTER TABLE `glpi_tracking_planning` ADD INDEX ( `id_followup` );";
	$db->query($query) or die("0.65 add index for id_followup in tracking_planning".$lang["update"][90].$db->error());

	//// Move Planned item to followup
	// Get super-admin ID
	$suid=0;
	$query0="SELECT ID from glpi_users WHERE type='super-admin'";
	$result0=$db->query($query0);
	if ($db->numrows($result0)>0){
		$suid=$db->result($result0,0,0);
	}
	mysql_free_result($result0);
	$query="SELECT * FROM glpi_tracking_planning order by id_tracking";
	$result = $db->query($query);
	$used_followups=array();
	if ($db->numrows($result)>0)
	while ($data=$db->fetch_array($result)){
		$found=-1;
		// Is a followup existing ?
		$query2="SELECT * FROM glpi_followups WHERE tracking='".$data["id_tracking"]."'";
		$result2=$db->query($query2);
		if ($db->numrows($result2)>0)
		while ($found<0&&$data2=$db->fetch_array($result2))
		if (!in_array($data2['ID'],$used_followups)){
				$found=$data2['ID'];
		}
		mysql_free_result($result2);
		// Followup not founded
		if ($found<0){
			$query3="INSERT INTO glpi_followups (tracking,date,author,contents) VALUES ('".$data["id_tracking"]."','".date("Y-m-d")."','$suid','Automatic Added followup for compatibility problem in update')";
			$db->query($query3);
			$found=$db->insert_id();
		} 
		array_push($used_followups,$found);
		$query4="UPDATE glpi_tracking_planning SET id_followup='$found' WHERE ID ='".$data['ID']."';";
		$db->query($query4);
	}
	unset($used_followups);
	mysql_free_result($result);
	$query=" ALTER TABLE `glpi_tracking_planning` DROP `id_tracking` ;";
	$db->query($query) or die("0.65 add index for id_followup in tracking_planning".$lang["update"][90].$db->error());
}

if(!FieldExists("glpi_config","use_ajax")) {
	$query="ALTER TABLE `glpi_config` ADD `dropdown_max` INT DEFAULT '100' NOT NULL ,
	ADD `ajax_wildcard` CHAR( 1 ) DEFAULT '*' NOT NULL ,
	ADD `use_ajax` SMALLINT DEFAULT '0' NOT NULL ,
	ADD `ajax_limit_count` INT DEFAULT '50' NOT NULL ; ";
	$db->query($query) or die("0.65 add ajax fields in config".$lang["update"][90].$db->error());
}

if(!FieldExists("glpi_config","ajax_autocompletion")) {
	$query="ALTER TABLE `glpi_config` ADD `ajax_autocompletion` SMALLINT DEFAULT '1' NOT NULL ;";
	$db->query($query) or die("0.65 add ajax_autocompletion field in config".$lang["update"][90].$db->error());
}

if(!FieldExists("glpi_config","auto_add_users")) {
	$query="ALTER TABLE `glpi_config` ADD `auto_add_users` SMALLINT DEFAULT '1' NOT NULL ;";
	$db->query($query) or die("0.65 add auto_add_users field in config".$lang["update"][90].$db->error());
}

if(!FieldExists("glpi_config","dateformat")) {
	$query="ALTER TABLE `glpi_config` ADD `dateformat` SMALLINT DEFAULT '0' NOT NULL ;";
	$db->query($query) or die("0.65 add dateformat field in config".$lang["update"][90].$db->error());
}


if(FieldExists("glpi_software","version")) {
	$query=" ALTER TABLE `glpi_software` CHANGE `version` `version` VARCHAR( 200 ) NOT NULL;";
	$db->query($query) or die("0.65 alter version field in software".$lang["update"][90].$db->error());
}

if(!FieldExists("glpi_config","nextprev_item")) {
	$query="ALTER TABLE `glpi_config` ADD `nextprev_item` VARCHAR( 200 ) DEFAULT 'name' NOT NULL ;";
	$db->query($query) or die("0.65 add nextprev_item field in config".$lang["update"][90].$db->error());
}

if(!FieldExists("glpi_config","view_ID")) {
	$query="ALTER TABLE `glpi_config` ADD `view_ID` SMALLINT DEFAULT '0' NOT NULL ;";
	$db->query($query) or die("0.65 add nextprev_item field in config".$lang["update"][90].$db->error());
}

if(FieldExists("glpi_infocoms","comments")) {
	$query=" ALTER TABLE `glpi_infocoms` CHANGE `comments` `comments` TEXT";
	$db->query($query) or die("0.65 alter comments in glpi_infocoms".$lang["update"][90].$db->error());
}

$new_model=array("monitors","networking","peripherals","printers");

foreach ($new_model as $model)
if(!TableExists("glpi_dropdown_model_$model")) {
	// model=type pour faciliter la gestion en post mise � jour : ya plus qu'a deleter les elements non voulu
	// cela conviendra a tout le monde en fonction de l'utilisation du champ type

	$query = "CREATE TABLE `glpi_dropdown_model_$model` (
  	`ID` int(11) NOT NULL auto_increment,
  	`name` varchar(255) NOT NULL default '',
  	PRIMARY KEY  (`ID`)
	) TYPE=MyISAM;";

	$db->query($query) or die("0.6 add table glpi_dropdown_model_$model ".$lang["update"][90].$db->error());

	// copie type dans model
	$query="SELECT * FROM glpi_type_$model";
	$result=$db->query($query);	
	if ($db->numrows($result)>0)
	while ($data=$db->fetch_array($result)){
		$query="INSERT INTO `glpi_dropdown_model_$model` (`ID`,`name`) VALUES ('".$data['ID']."','".addslashes($data['name'])."');";
		$db->query($query) or die("0.6 insert value in glpi_dropdown_model_$model ".$lang["update"][90].$db->error());		
	}
	mysql_free_result($result);

	$query="ALTER TABLE `glpi_$model` ADD `model` INT(11) DEFAULT NULL AFTER `type` ;";
	$db->query($query) or die("0.6 add model in $model".$lang["update"][90].$db->error());

	$query="UPDATE `glpi_$model` SET `model` = `type` ";
	$db->query($query) or die("0.6 add model in $model".$lang["update"][90].$db->error());
	 
}

// Update pour les cartouches compatibles : type -> model
if(FieldExists("glpi_cartridges_assoc","FK_glpi_type_printer")) {
	$query=" ALTER TABLE `glpi_cartridges_assoc` CHANGE `FK_glpi_type_printer` `FK_glpi_dropdown_model_printers` INT( 11 ) DEFAULT '0' NOT NULL ";
	$db->query($query) or die("0.65 alter FK_glpi_type_printer field in cartridges_assoc ".$lang["update"][90].$db->error());
}

if(!FieldExists("glpi_links","data")) {
	$query=" ALTER TABLE `glpi_links` ADD `data` TEXT NOT NULL ;";
	$db->query($query) or die("0.65 create data in links ".$lang["update"][90].$db->error());
}

if(!TableExists("glpi_dropdown_auto_update")) {
	$query = "CREATE TABLE `glpi_dropdown_auto_update` (
  	`ID` int(11) NOT NULL auto_increment,
  	`name` varchar(255) NOT NULL default '',
  	PRIMARY KEY  (`ID`)
	) TYPE=MyISAM;";

	$db->query($query) or die("0.65 add table glpi_dropdown_auto_update ".$lang["update"][90].$db->error());
}

if(!FieldExists("glpi_computers","auto_update")) {
	$query="ALTER TABLE `glpi_computers` ADD `auto_update` INT DEFAULT '0' NOT NULL AFTER `os` ;";
	$db->query($query) or die("0.65 alter computers add auto_update ".$lang["update"][90].$db->error());	
}

// Update specificity of computer_device
$query="SELECT glpi_computer_device.ID as ID,glpi_device_processor.specif_default as SPECIF FROM glpi_computer_device LEFT JOIN glpi_device_processor ON (glpi_computer_device.FK_device=glpi_device_processor.ID AND glpi_computer_device.device_type='".PROCESSOR_DEVICE."') WHERE glpi_computer_device.specificity =''";
$result=$db->query($query);
if ($db->numrows($result)>0)
while ($data=$db->fetch_assoc($result)){
	$query2="UPDATE glpi_computer_device SET specificity='".$data["SPECIF"]."' WHERE ID = '".$data["ID"]."'";
	$db->query($query2);
}

$query="SELECT glpi_computer_device.ID as ID,glpi_device_processor.specif_default as SPECIF FROM glpi_computer_device LEFT JOIN glpi_device_processor ON (glpi_computer_device.FK_device=glpi_device_processor.ID AND glpi_computer_device.device_type='".PROCESSOR_DEVICE."') WHERE glpi_computer_device.specificity =''";
$result=$db->query($query);
if ($db->numrows($result)>0)
while ($data=$db->fetch_assoc($result)){
	$query2="UPDATE glpi_computer_device SET specificity='".$data["SPECIF"]."' WHERE ID = '".$data["ID"]."'";
	$db->query($query2);
}

$query="SELECT glpi_computer_device.ID as ID,glpi_device_ram.specif_default as SPECIF FROM glpi_computer_device LEFT JOIN glpi_device_ram ON (glpi_computer_device.FK_device=glpi_device_ram.ID AND glpi_computer_device.device_type='".RAM_DEVICE."') WHERE glpi_computer_device.specificity =''";
$result=$db->query($query);
if ($db->numrows($result)>0)
while ($data=$db->fetch_assoc($result)){
	$query2="UPDATE glpi_computer_device SET specificity='".$data["SPECIF"]."' WHERE ID = '".$data["ID"]."'";
	$db->query($query2);
}

$query="SELECT glpi_computer_device.ID as ID,glpi_device_hdd.specif_default as SPECIF FROM glpi_computer_device LEFT JOIN glpi_device_hdd ON (glpi_computer_device.FK_device=glpi_device_hdd.ID AND glpi_computer_device.device_type='".HDD_DEVICE."') WHERE glpi_computer_device.specificity =''";
$result=$db->query($query);
if ($db->numrows($result)>0)
while ($data=$db->fetch_assoc($result)){
	$query2="UPDATE glpi_computer_device SET specificity='".$data["SPECIF"]."' WHERE ID = '".$data["ID"]."'";
	$db->query($query2);
}

$query="SELECT glpi_computer_device.ID as ID,glpi_device_iface.specif_default as SPECIF FROM glpi_computer_device LEFT JOIN glpi_device_iface ON (glpi_computer_device.FK_device=glpi_device_iface.ID AND glpi_computer_device.device_type='".NETWORK_DEVICE."') WHERE glpi_computer_device.specificity =''";
$result=$db->query($query);
if ($db->numrows($result)>0)
while ($data=$db->fetch_assoc($result)){
	$query2="UPDATE glpi_computer_device SET specificity='".$data["SPECIF"]."' WHERE ID = '".$data["ID"]."'";
	$db->query($query2);
}

// add field notes in tables
$new_notes=array("computers","software","monitors","networking","peripherals","printers","cartridges_type","consumables_type","contacts","enterprises","contracts","docs");

foreach ($new_notes as $notes)
if(!FieldExists("glpi_$notes","notes")) {	
	$query="ALTER TABLE `glpi_$notes` ADD   `notes` LONGTEXT NULL ;";
	$db->query($query) or die("0.65 add notes field in table".$lang["update"][90].$db->error());

}

if(!FieldExists("glpi_users","active")) {	
	$query="ALTER TABLE `glpi_users` ADD `active` INT( 2 ) DEFAULT '1' NOT NULL ";
	$db->query($query) or die("0.65 add active in users ".$lang["update"][90].$db->error());
}


if(TableExists("glpi_type_docs")){
	$query="SELECT * from glpi_type_docs WHERE ext='odt' OR ext='ods' OR ext='odp' OR ext='otp' OR ext='ott' OR ext='ots' OR ext='odf' OR ext='odg' OR ext='otg' OR ext='odb' OR ext='oth' OR ext='odm' OR ext='odc' OR ext='odi'";
	$result=$db->query($query);
	if ($db->numrows($result)==0){
		$query2="INSERT INTO `glpi_type_docs` ( `ID` , `name` , `ext` , `icon` , `mime` , `upload` , `date_mod` ) VALUES (NULL, 'Oasis Open Office Writer', 'odt', 'odt-dist.png', NULL, 'Y', '2006-01-21 17:41:13'),
	(NULL, 'Oasis Open Office Calc', 'ods', 'ods-dist.png', NULL, 'Y', '2006-01-21 17:41:31'),
	(NULL, 'Oasis Open Office Impress', 'odp', 'odp-dist.png', NULL, 'Y', '2006-01-21 17:42:54'),
	(NULL, 'Oasis Open Office Impress Template', 'otp', 'odp-dist.png', NULL, 'Y', '2006-01-21 17:43:58'),
	(NULL, 'Oasis Open Office Writer Template', 'ott', 'odt-dist.png', NULL, 'Y', '2006-01-21 17:44:41'),
	(NULL, 'Oasis Open Office Calc Template', 'ots', 'ods-dist.png', NULL, 'Y', '2006-01-21 17:45:30'),
	(NULL, 'Oasis Open Office Math', 'odf', 'odf-dist.png', NULL, 'Y', '2006-01-21 17:48:05'),
	(NULL, 'Oasis Open Office Draw', 'odg', 'odg-dist.png', NULL, 'Y', '2006-01-21 17:48:31'),
	(NULL, 'Oasis Open Office Draw Template', 'otg', 'odg-dist.png', NULL, 'Y', '2006-01-21 17:49:46'),
	(NULL, 'Oasis Open Office Base', 'odb', 'odb-dist.png', NULL, 'Y', '2006-01-21 18:03:34'),
	(NULL, 'Oasis Open Office HTML', 'oth', 'oth-dist.png', NULL, 'Y', '2006-01-21 18:05:27'),
	(NULL, 'Oasis Open Office Writer Master', 'odm', 'odm-dist.png', NULL, 'Y', '2006-01-21 18:06:34'),
	(NULL, 'Oasis Open Office Chart', 'odc', NULL, NULL, 'Y', '2006-01-21 18:07:48'),
	(NULL, 'Oasis Open Office Image', 'odi', NULL, NULL, 'Y', '2006-01-21 18:08:18');";
		$db->query($query2) or die("0.65 add new type docs ".$lang["update"][90].$db->error());
	}
}



///// BEGIN  MySQL Compatibility
if(FieldExists("glpi_infocoms","warranty_value")) {	
	$query2=" UPDATE `glpi_infocoms` SET `warranty_value`='0' WHERE `warranty_value` IS NULL;";
	$db->query($query2) or die("0.65 update warranty_value='' in tracking".$lang["update"][90].$db->error());

	$query="ALTER TABLE `glpi_infocoms` CHANGE `warranty_info` `warranty_info` VARCHAR( 255 ) NULL DEFAULT NULL,
		CHANGE `warranty_value` `warranty_value` FLOAT NOT NULL DEFAULT '0',
		CHANGE `num_commande` `num_commande` VARCHAR( 200 ) NULL DEFAULT NULL,
		CHANGE `bon_livraison` `bon_livraison` VARCHAR( 200 ) NULL DEFAULT NULL,
		CHANGE `facture` `facture` VARCHAR( 200 ) NULL DEFAULT NULL,
		CHANGE `num_immo` `num_immo` VARCHAR( 200 ) NULL DEFAULT NULL;";
	$db->query($query) or die("0.65 alter various fields in infocoms ".$lang["update"][90].$db->error());
}

if(FieldExists("glpi_reservation_item","comments")) {	
	$query="ALTER TABLE `glpi_reservation_item` CHANGE `comments` `comments` TEXT NULL ";
	$db->query($query) or die("0.65 alter comments in glpi_reservation_item ".$lang["update"][90].$db->error());
}


if(FieldExists("glpi_cartridges_type","comments")) {	
	$query="ALTER TABLE `glpi_cartridges_type` CHANGE `name` `name` VARCHAR( 255 ) NULL DEFAULT NULL,
		CHANGE `ref` `ref` VARCHAR( 255 ) NULL DEFAULT NULL ,
		CHANGE `comments` `comments` TEXT NULL DEFAULT NULL ";
	$db->query($query) or die("0.65 alter various fields in cartridges_type ".$lang["update"][90].$db->error());
}

if(FieldExists("glpi_computer_device","specificity")) {	
	$query="ALTER TABLE `glpi_computer_device` CHANGE `specificity` `specificity` VARCHAR( 250 ) NULL ";
	$db->query($query) or die("0.65 alter specificity in glpi_computer_device ".$lang["update"][90].$db->error());
}

$inv_table=array("computers","monitors","networking","peripherals","printers");

foreach ($inv_table as $table)
if(FieldExists("glpi_$table","comments")) {	
	$query="ALTER TABLE `glpi_$table` CHANGE `name` `name` VARCHAR( 200 ) NULL ,
		CHANGE `serial` `serial` VARCHAR( 200 ) NULL ,
		CHANGE `otherserial` `otherserial` VARCHAR( 200 ) NULL ,
		CHANGE `contact` `contact` VARCHAR( 200 ) NULL ,
		CHANGE `contact_num` `contact_num` VARCHAR( 200 ) NULL ,
		CHANGE `location` `location` INT( 11 ) NOT NULL DEFAULT '0',
		CHANGE `comments` `comments` TEXT NULL ";
	$db->query($query) or die("0.65 alter various fields in $table ".$lang["update"][90].$db->error());
}

if(FieldExists("glpi_computers","os")) {	
	$db->query($query) or die("0.65 alter various fields in $table ".$lang["update"][90].$db->error());
	$query="ALTER TABLE `glpi_computers` CHANGE `os` `os` INT( 11 ) NOT NULL DEFAULT '0',
		CHANGE `model` `model` INT( 11 ) NOT NULL DEFAULT '0',
		CHANGE `type` `type` INT( 11 ) NOT NULL DEFAULT '0'";
	$db->query($query) or die("0.65 alter various fields in computers ".$lang["update"][90].$db->error());

}
if(FieldExists("glpi_networking","ram")) {	
	$query="ALTER TABLE `glpi_networking` CHANGE `ram` `ram` VARCHAR( 200 ) NULL ,
		CHANGE `ifmac` `ifmac` VARCHAR( 200 ) NULL ,
		CHANGE `ifaddr` `ifaddr` VARCHAR( 200 ) NULL";
	$db->query($query) or die("0.65 alter 2 various fields in networking ".$lang["update"][90].$db->error());
}

if(FieldExists("glpi_peripherals","brand")) {	
	$query="ALTER TABLE `glpi_peripherals` CHANGE `brand` `brand` VARCHAR( 200 ) NULL ";
	$db->query($query) or die("0.65 alter 2 various fields in peripherals ".$lang["update"][90].$db->error());
}

if(FieldExists("glpi_printers","ramSize")) {	
	$query="ALTER TABLE `glpi_printers` CHANGE `ramSize` `ramSize` VARCHAR( 200 ) NULL ";
	$db->query($query) or die("0.65 alter 2 various fields in printers ".$lang["update"][90].$db->error());
}
if(FieldExists("glpi_consumables_type","comments")) {	
	$query="ALTER TABLE `glpi_consumables_type` CHANGE `name` `name` VARCHAR( 255 ) NULL ,
		CHANGE `ref` `ref` VARCHAR( 255 ) NULL ,
		CHANGE `comments` `comments` TEXT NULL  ";
	$db->query($query) or die("0.65 alter various fields in consumables_type ".$lang["update"][90].$db->error());
}

if(FieldExists("glpi_contacts","comments")) {	
	$query="ALTER TABLE `glpi_contacts` CHANGE `name` `name` VARCHAR( 255 ) NULL ,
		CHANGE `phone` `phone` VARCHAR( 200 ) NULL ,
		CHANGE `phone2` `phone2` VARCHAR( 200 ) NULL ,
		CHANGE `fax` `fax` VARCHAR( 200 ) NULL ,
		CHANGE `email` `email` VARCHAR( 255 ) NULL ,
		CHANGE `comments` `comments` TEXT NULL  ";
	$db->query($query) or die("0.65 alter various fields in contacts ".$lang["update"][90].$db->error());
}


if(FieldExists("glpi_contracts","comments")) {	
	$query="ALTER TABLE `glpi_contracts` CHANGE `name` `name` VARCHAR( 255 ) NULL ,
		CHANGE `num` `num` VARCHAR( 255 ) NULL ,
		CHANGE `comments` `comments` TEXT NULL ,
		CHANGE `compta_num` `compta_num` VARCHAR( 255 ) NULL ";
	$db->query($query) or die("0.65 alter various fields in contracts ".$lang["update"][90].$db->error());
}

$device=array("case","control","drive","gfxcard","hdd","iface","moboard","pci","power","processor","ram","sndcard");

foreach ($device as $dev)
if(FieldExists("glpi_device_$dev","comment")) {	
	$query="ALTER TABLE `glpi_device_$dev` CHANGE `designation` `designation` VARCHAR( 255 ) NULL ,
		CHANGE `comment` `comment` TEXT NULL ,
		CHANGE `specif_default` `specif_default` VARCHAR( 250 ) NULL,
		ADD INDEX ( `designation` ); ";
	$db->query($query) or die("0.65 alter various fields in device_$dev ".$lang["update"][90].$db->error());

}

if(FieldExists("glpi_docs","comment")) {	
	$query="ALTER TABLE `glpi_docs` CHANGE `name` `name` VARCHAR( 255 ) NULL ,
		CHANGE `filename` `filename` VARCHAR( 255 ) NULL ,
		CHANGE `mime` `mime` VARCHAR( 30 ) NULL ,
		CHANGE `comment` `comment` TEXT NULL ,
		CHANGE `link` `link` VARCHAR( 255 ) NULL  ";
	$db->query($query) or die("0.65 alter various fields in docs ".$lang["update"][90].$db->error());
}

if(FieldExists("glpi_enterprises","comments")) {	
	$query="ALTER TABLE `glpi_enterprises` CHANGE `name` `name` VARCHAR( 200 ) NULL ,
		CHANGE `address` `address` TEXT NULL ,
		CHANGE `website` `website` VARCHAR( 200 ) NULL ,
		CHANGE `phonenumber` `phonenumber` VARCHAR( 200 ) NULL ,
		CHANGE `comments` `comments` TEXT NULL ,
		CHANGE `fax` `fax` VARCHAR( 255 ) NULL ,
		CHANGE `email` `email` VARCHAR( 255 ) NULL  ";
	$db->query($query) or die("0.65 alter various fields in enterprises ".$lang["update"][90].$db->error());
}

if(FieldExists("glpi_event_log","message")) {	
	$query="ALTER TABLE `glpi_event_log` CHANGE `itemtype` `itemtype` VARCHAR( 200 ) NULL ,
		CHANGE `service` `service` VARCHAR( 200 ) NULL ,
		CHANGE `message` `message` TEXT NULL   ";
	$db->query($query) or die("0.65 alter various fields in event_log ".$lang["update"][90].$db->error());
}

if(FieldExists("glpi_kbitems","question")) {	
	$query="ALTER TABLE `glpi_kbitems` CHANGE `question` `question` TEXT NULL ,
		CHANGE `answer` `answer` TEXT NULL ";
	$db->query($query) or die("0.65 alter various fields in kbitems ".$lang["update"][90].$db->error());
}

if(FieldExists("glpi_licenses","serial")) {	
	$query="ALTER TABLE `glpi_licenses` CHANGE `serial` `serial` VARCHAR( 255 ) NULL";
	$db->query($query) or die("0.65 alter serial in licenses ".$lang["update"][90].$db->error());
}

if(FieldExists("glpi_links","data")) {	
	$query="ALTER TABLE `glpi_links` CHANGE `name` `name` VARCHAR( 255 ) NULL ,
		CHANGE `data` `data` TEXT NULL";
	$db->query($query) or die("0.65 alter various fields in links ".$lang["update"][90].$db->error());
}
 

if(FieldExists("glpi_networking_ports","ifmac")) {	
	$query="ALTER TABLE `glpi_networking_ports` CHANGE `name` `name` CHAR( 200 ) NULL ,
		CHANGE `ifaddr` `ifaddr` CHAR( 200 ) NULL ,
		CHANGE `ifmac` `ifmac` CHAR( 200 ) NULL";
	$db->query($query) or die("0.65 alter various fields in networking_ports ".$lang["update"][90].$db->error());
}
 
if(FieldExists("glpi_reservation_resa","comment")) {	
	$query="ALTER TABLE `glpi_reservation_resa` CHANGE `comment` `comment` TEXT NULL";
	$db->query($query) or die("0.65 alter comment in reservation_resa ".$lang["update"][90].$db->error());
} 

if(FieldExists("glpi_software","version")) {	
	$query="ALTER TABLE `glpi_software` CHANGE `name` `name` VARCHAR( 200 ) NULL ,
		CHANGE `version` `version` VARCHAR( 200 ) NULL ";
	$db->query($query) or die("0.65 alter various fields in software ".$lang["update"][90].$db->error());
} 

if(FieldExists("glpi_type_docs","name")) {	
	$query="ALTER TABLE `glpi_type_docs` CHANGE `name` `name` VARCHAR( 255 ) NULL ,
		CHANGE `ext` `ext` VARCHAR( 10 ) NULL ,
		CHANGE `icon` `icon` VARCHAR( 255 ) NULL ,
		CHANGE `mime` `mime` VARCHAR( 100 ) NULL ";
	$db->query($query) or die("0.65 alter various fields in type_docs ".$lang["update"][90].$db->error());
} 

if(FieldExists("glpi_users","language")) {	
	$query="ALTER TABLE `glpi_users` CHANGE `name` `name` VARCHAR( 80 ) NULL ,
		CHANGE `password` `password` VARCHAR( 80 ) NULL ,
		CHANGE `password_md5` `password_md5` VARCHAR( 80 ) NULL ,
		CHANGE `email` `email` VARCHAR( 200 ) NULL ,
		CHANGE `realname` `realname` VARCHAR( 255 ) NULL ,
		CHANGE `language` `language` VARCHAR( 255 ) NULL  ";
	$db->query($query) or die("0.65 alter various fields in users ".$lang["update"][90].$db->error());
} 

if(FieldExists("glpi_config","cut")) {	
	$query="ALTER TABLE `glpi_config` CHANGE `num_of_events` `num_of_events` VARCHAR( 200 ) NULL ,
		CHANGE `jobs_at_login` `jobs_at_login` VARCHAR( 200 ) NULL ,
		CHANGE `sendexpire` `sendexpire` VARCHAR( 200 ) NULL ,
		CHANGE `cut` `cut` VARCHAR( 200 ) NULL ,
		CHANGE `expire_events` `expire_events` VARCHAR( 200 ) NULL ,
		CHANGE `list_limit` `list_limit` VARCHAR( 200 ) NULL ,
		CHANGE `version` `version` VARCHAR( 200 ) NULL ,
		CHANGE `logotxt` `logotxt` VARCHAR( 200 ) NULL ,
		CHANGE `root_doc` `root_doc` VARCHAR( 200 ) NULL ,
		CHANGE `event_loglevel` `event_loglevel` VARCHAR( 200 ) NULL ,
		CHANGE `mailing` `mailing` VARCHAR( 200 ) NULL ,
		CHANGE `imap_auth_server` `imap_auth_server` VARCHAR( 200 ) NULL ,
		CHANGE `imap_host` `imap_host` VARCHAR( 200 ) NULL ,
		CHANGE `ldap_host` `ldap_host` VARCHAR( 200 ) NULL ,
		CHANGE `ldap_basedn` `ldap_basedn` VARCHAR( 200 ) NULL ,
		CHANGE `ldap_rootdn` `ldap_rootdn` VARCHAR( 200 ) NULL ,
		CHANGE `ldap_pass` `ldap_pass` VARCHAR( 200 ) NULL ,
		CHANGE `admin_email` `admin_email` VARCHAR( 200 ) NULL ,
		CHANGE `mailing_signature` `mailing_signature` VARCHAR( 200 ) DEFAULT '--' ,
		CHANGE `mailing_new_admin` `mailing_new_admin` tinyint(4) DEFAULT '1' ,
		CHANGE `mailing_followup_admin` `mailing_followup_admin` tinyint(4) DEFAULT '1' ,
		CHANGE `mailing_finish_admin` `mailing_finish_admin` tinyint(4) DEFAULT '1' ,
		CHANGE `mailing_new_all_admin` `mailing_new_all_admin` tinyint(4) DEFAULT '0' ,
		CHANGE `mailing_followup_all_admin` `mailing_followup_all_admin` tinyint(4) DEFAULT '0' ,
		CHANGE `mailing_finish_all_admin` `mailing_finish_all_admin` tinyint(4) DEFAULT '0' ,
		CHANGE `mailing_new_all_normal` `mailing_new_all_normal` tinyint(4) DEFAULT '0' ,
		CHANGE `mailing_followup_all_normal` `mailing_followup_all_normal` tinyint(4) DEFAULT '0' ,
		CHANGE `mailing_finish_all_normal` `mailing_finish_all_normal` tinyint(4) DEFAULT '0' ,
		CHANGE `mailing_new_attrib` `mailing_new_attrib` tinyint(4) DEFAULT '1' ,
		CHANGE `mailing_followup_attrib` `mailing_followup_attrib` tinyint(4) DEFAULT '1' ,
		CHANGE `mailing_finish_attrib` `mailing_finish_attrib` tinyint(4) DEFAULT '1' ,
		CHANGE `mailing_new_user` `mailing_new_user` tinyint(4) DEFAULT '1' ,
		CHANGE `mailing_followup_user` `mailing_followup_user` tinyint(4) DEFAULT '1' ,
		CHANGE `mailing_finish_user` `mailing_finish_user` tinyint(4) DEFAULT '1' ,
		CHANGE `ldap_field_name` `ldap_field_name` VARCHAR( 200 ) NULL ,
		CHANGE `ldap_field_email` `ldap_field_email` VARCHAR( 200 ) NULL ,
		CHANGE `ldap_field_location` `ldap_field_location` VARCHAR( 200 ) NULL ,
		CHANGE `ldap_field_realname` `ldap_field_realname` VARCHAR( 200 ) NULL ,
		CHANGE `ldap_field_phone` `ldap_field_phone` VARCHAR( 200 ) NULL ,
		CHANGE `ldap_condition` `ldap_condition` VARCHAR( 255 ) NULL ,
		CHANGE `permit_helpdesk` `permit_helpdesk` VARCHAR( 200 ) NULL ,
		CHANGE `cas_host` `cas_host` VARCHAR( 255 ) NULL ,
		CHANGE `cas_port` `cas_port` VARCHAR( 255 ) NULL ,
		CHANGE `cas_uri` `cas_uri` VARCHAR( 255 ) NULL ,
		CHANGE `url_base` `url_base` VARCHAR( 255 ) NULL ,
		CHANGE `text_login` `text_login` TEXT NULL ,
		CHANGE `founded_new_version` `founded_new_version` VARCHAR( 10 ) NULL ";
	$db->query($query) or die("0.65 alter various fields in config ".$lang["update"][90].$db->error());
} 
///// END  MySQL Compatibility


if(!FieldExists("glpi_config","dropdown_limit")) {	
	$query="ALTER TABLE `glpi_config` ADD `dropdown_limit` INT( 11 ) DEFAULT '50' NOT NULL ";
	$db->query($query) or die("0.65 add dropdown_limit in config ".$lang["update"][90].$db->error());
}


if(FieldExists("glpi_consumables_type","type")) {	
	$query="ALTER TABLE `glpi_consumables_type` CHANGE `type` `type` INT( 11 ) NOT NULL DEFAULT '0',
			CHANGE `alarm` `alarm` INT( 11 ) NOT NULL DEFAULT '10'";
	$db->query($query) or die("0.65 alter type and alarm in consumables_type ".$lang["update"][90].$db->error());
}


if(!FieldExists("glpi_config","post_only_followup")) {	
	$query="ALTER TABLE `glpi_config` ADD `post_only_followup` tinyint( 4 ) DEFAULT '1' NOT NULL ";
	$db->query($query) or die("0.65 add dropdown_limit in config ".$lang["update"][90].$db->error());
}

if(!FieldExists("glpi_monitors","flags_dvi")) {	
	$query="ALTER TABLE `glpi_monitors` ADD `flags_dvi` tinyint( 4 ) DEFAULT '0' NOT NULL AFTER `flags_bnc`";
	$db->query($query) or die("0.65 add dropdown_limit in config ".$lang["update"][90].$db->error());
}

if(!TableExists("glpi_history")) {
	$query="CREATE TABLE `glpi_history` (
  `ID` int(11) NOT NULL auto_increment,
  `FK_glpi_device` int(11) NOT NULL default '0',
  `device_type` tinyint(4) NOT NULL default '0',
  `device_internal_type` int(11) default '0',
  `device_internal_action` tinyint(4) default '0',
  `user_name` varchar(200) default NULL,
  `date_mod` datetime default NULL,
  `id_search_option` int(11) NOT NULL default '0',
  `old_value` varchar(255) default NULL,
  `new_value` varchar(255) default NULL,
  PRIMARY KEY  (`ID`),
  KEY `FK_glpi_device` (`FK_glpi_device`)
) TYPE=MyISAM;";

	$db->query($query) or die("0.65 add glpi_history table".$lang["update"][90].$db->error());
}

if(FieldExists("glpi_tracking","assign_type")) {	

	$query="ALTER TABLE `glpi_tracking` ADD `assign_ent` INT NOT NULL DEFAULT '0' AFTER `assign` ";
	$db->query($query) or die("0.65 add assign_ent in tracking ".$lang["update"][90].$db->error());

	$query="UPDATE `glpi_tracking` SET assign_ent=assign WHERE assign_type='".ENTERPRISE_TYPE."'";
	$db->query($query) or die("0.65 update assign_ent in tracking ".$lang["update"][90].$db->error());

	$query="UPDATE `glpi_tracking` SET assign=0 WHERE assign_type='".ENTERPRISE_TYPE."'";
	$db->query($query) or die("0.65 update assign_ent in tracking ".$lang["update"][90].$db->error());

	$query="ALTER TABLE `glpi_tracking` DROP `assign_type`";
	$db->query($query) or die("0.65 drop assign_type in tracking ".$lang["update"][90].$db->error());



}

if(!FieldExists("glpi_config","mailing_update_admin")) {
	$query="ALTER TABLE `glpi_config` ADD `mailing_update_admin` tinyint(4) DEFAULT '1' AFTER `mailing_new_admin` ;";
	$db->query($query) or die("0.65 add mailing_update_admin in config".$lang["update"][90].$db->error());
}
if(!FieldExists("glpi_config","mailing_update_all_admin")) {
	$query="ALTER TABLE `glpi_config` ADD `mailing_update_all_admin` tinyint(4) DEFAULT '0' AFTER `mailing_new_all_admin` ;";
	$db->query($query) or die("0.65 add mailing_update_all_admin in config".$lang["update"][90].$db->error());
}
if(!FieldExists("glpi_config","mailing_update_all_normal")) {
	$query="ALTER TABLE `glpi_config` ADD `mailing_update_all_normal` tinyint(4) DEFAULT '0' AFTER `mailing_new_all_normal` ;";
	$db->query($query) or die("0.65 add mailing_update_all_normal in config".$lang["update"][90].$db->error());
}
if(!FieldExists("glpi_config","mailing_update_attrib")) {
	$query="ALTER TABLE `glpi_config` ADD `mailing_update_attrib` tinyint(4) DEFAULT '1' AFTER `mailing_new_attrib` ;";
	$db->query($query) or die("0.65 add mailing_update_attrib in config".$lang["update"][90].$db->error());
}
if(!FieldExists("glpi_config","mailing_update_user")) {
	$query="ALTER TABLE `glpi_config` ADD `mailing_update_user` tinyint(4) DEFAULT '1' AFTER `mailing_new_user` ;";
	$db->query($query) or die("0.65 add mailing_update_user in config".$lang["update"][90].$db->error());
}

if(!FieldExists("glpi_config","ldap_use_tls")) {
	$query="ALTER TABLE `glpi_config` ADD `ldap_use_tls` VARCHAR( 200 ) NOT NULL DEFAULT '0' AFTER `ldap_login` ";
	$db->query($query) or die("0.65 add ldap_use_tls in config".$lang["update"][90].$db->error());
}

if(FieldExists("glpi_config","cut")) { // juste pour affichage identique sur toutes les versions.
$query="UPDATE `glpi_config` SET `cut` = '255' WHERE `ID` =1";
$db->query($query) or die("0.65 update Cut in config".$lang["update"][90].$db->error());
}

if(!FieldExists("glpi_licenses","comments")) {
	$query="ALTER TABLE `glpi_licenses` ADD `comments` TEXT NULL ";
	$db->query($query) or die("0.65 add comments in licenses".$lang["update"][90].$db->error());
}

///////////// MODE OCS

// Delete plugin table
if(TableExists("glpi_ocs_link")&&!FieldExists("glpi_ocs_link","import_device")) {
	$query = "DROP TABLE `glpi_ocs_link`";
	$db->query($query) or die("0.65 MODE OCS drop plugin ocs_link ".$lang["update"][90].$db->error());
}

if(TableExists("glpi_ocs_config")&&!FieldExists("glpi_ocs_config","checksum")) {
	$query = "DROP TABLE `glpi_ocs_config`";
	$db->query($query) or die("0.65 MODE OCS drop plugin ocs_config ".$lang["update"][90].$db->error());
}

if(!TableExists("glpi_ocs_link")) {
	$query = "CREATE TABLE `glpi_ocs_link` (
  `ID` int(11) NOT NULL auto_increment,
  `glpi_id` int(11) NOT NULL default '0',
  `ocs_id` varchar(255) NOT NULL default '',
  `auto_update` int(2) NOT NULL default '1',
  `last_update` datetime NOT NULL default '0000-00-00 00:00:00',
  `computer_update` LONGTEXT NULL,
  `import_device` LONGTEXT NULL,
  `import_software` LONGTEXT NULL,
  `import_monitor` LONGTEXT NULL,
  `import_peripheral` LONGTEXT NULL,
  `import_printers` LONGTEXT NULL,
  PRIMARY KEY  (`ID`),
  UNIQUE KEY `ocs_id_2` (`ocs_id`),
  KEY `ocs_id` (`ocs_id`),
  KEY `glpi_id` (`glpi_id`),
  KEY `auto_update` (`auto_update`),
  KEY `last_update` (`last_update`)
) TYPE=MyISAM";
	$db->query($query) or die("0.65 MODE OCS creation ocs_link ".$lang["update"][90].$db->error());
}

if(!TableExists("glpi_ocs_config")) {
	$query = "CREATE TABLE `glpi_ocs_config` (
  `ID` int(11) NOT NULL auto_increment,
  `ocs_db_user` varchar(255) NOT NULL default '',
  `ocs_db_passwd` varchar(255) NOT NULL default '',
  `ocs_db_host` varchar(255) NOT NULL default '',
  `ocs_db_name` varchar(255) NOT NULL default '',
  `checksum` int(11) NOT NULL default '0',
  `import_periph` int(2) NOT NULL default '0',
  `import_monitor` int(2) NOT NULL default '0',
  `import_software` int(2) NOT NULL default '0',
  `import_printer` int(2) NOT NULL default '0',
  `import_general_os` int(2) NOT NULL default '0',
  `import_general_serial` int(2) NOT NULL default '0',
  `import_general_model` int(2) NOT NULL default '0',
  `import_general_enterprise` int(2) NOT NULL default '0',
  `import_general_type` int(2) NOT NULL default '0',
  `import_general_domain` int(2) NOT NULL default '0',
  `import_general_contact` int(2) NOT NULL default '0',
  `import_general_comments` int(2) NOT NULL default '0',
  `import_device_processor` int(2) NOT NULL default '0',
  `import_device_memory` int(2) NOT NULL default '0',
  `import_device_hdd` int(2) NOT NULL default '0',
  `import_device_iface` int(2) NOT NULL default '0',
  `import_device_gfxcard` int(2) NOT NULL default '0',
  `import_device_sound` int(2) NOT NULL default '0',
  `import_device_drives` int(2) NOT NULL default '0',
  `import_device_ports` int(2) NOT NULL default '0',
  `import_device_modems` int(2) NOT NULL default '0',
  `import_ip` int(2) NOT NULL default '0',

  PRIMARY KEY  (`ID`)
) ENGINE=MyISAM";

	$db->query($query) or die("0.65 MODE OCS creation ocs_config ".$lang["update"][90].$db->error());
	$query = "INSERT INTO `glpi_ocs_config` VALUES (1, 'ocs', 'ocs', 'localhost', 'ocsweb', 0, 2, 2, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);";
	$db->query($query) or die("0.65 MODE OCS add default config ".$lang["update"][90].$db->error());

}

if(!FieldExists("glpi_computers","ocs_import")) {
	$query = "ALTER TABLE `glpi_computers` ADD `ocs_import` TINYINT NOT NULL DEFAULT '0'";
	$db->query($query) or die("0.65 MODE OCS add default config ".$lang["update"][90].$db->error());
}

if(!FieldExists("glpi_config","ocs_mode")) {
	$query = "ALTER TABLE `glpi_config` ADD `ocs_mode` TINYINT NOT NULL DEFAULT '0' ";
	$db->query($query) or die("0.65 MODE OCS add ocs_mode in config ".$lang["update"][90].$db->error());
}
///////////// FIN MODE OCS


if(!TableExists("glpi_dropdown_budget")) {
	$query = "CREATE TABLE `glpi_dropdown_budget` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY  (`ID`)
) ENGINE=MyISAM;";
	$db->query($query) or die("0.65 add dropdown_budget ".$lang["update"][90].$db->error());
}

if(!FieldExists("glpi_infocoms","budget")) {
	$query = "ALTER TABLE `glpi_infocoms` ADD `budget` INT NULL DEFAULT '0';";
	$db->query($query) or die("0.65 add budget in infocoms ".$lang["update"][90].$db->error());
}
 
if(!FieldExists("glpi_tracking","cost_time")) {
	$query = "ALTER TABLE `glpi_tracking` ADD `cost_time` FLOAT NOT NULL DEFAULT '0',
		ADD `cost_fixed` FLOAT NOT NULL DEFAULT '0',
		ADD `cost_material` FLOAT NOT NULL DEFAULT '0'";
	$db->query($query) or die("0.65 add cost fields in tracking ".$lang["update"][90].$db->error());
}

// Global Printers
if(!FieldExists("glpi_printers","is_global")) {
	$query="ALTER TABLE `glpi_printers` ADD `is_global` ENUM('0', '1') DEFAULT '0' NOT NULL AFTER `FK_glpi_enterprise` ;";
	$db->query($query) or die("0.6 add is_global in printers ".$lang["update"][90].$db->error());
}

if(!FieldExists("glpi_config","debug")) {
	$query="ALTER TABLE `glpi_config` ADD `debug` int(2) NOT NULL default '0' ";
	$db->query($query) or die("0.65 add debug in config ".$lang["update"][90].$db->error());
}

if(!TableExists("glpi_dropdown_os_version")) {
	$query = "CREATE TABLE `glpi_dropdown_os_version` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`ID`)
) ENGINE=MyISAM;";
	$db->query($query) or die("0.65 add dropdown_os_version ".$lang["update"][90].$db->error());
}

if(!TableExists("glpi_dropdown_os_sp")) {
	$query = "CREATE TABLE `glpi_dropdown_os_sp` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`ID`)
) ENGINE=MyISAM;";
	$db->query($query) or die("0.65 add dropdown_os_sp ".$lang["update"][90].$db->error());
}

if(!FieldExists("glpi_computers","os_version")) {
	$query="ALTER TABLE `glpi_computers` ADD `os_version` INT NOT NULL DEFAULT '0' AFTER `os` ,
		ADD `os_sp` INT NOT NULL DEFAULT '0' AFTER `os_version` ";
	$db->query($query) or die("0.65 add os_version os_sp in computers ".$lang["update"][90].$db->error());
}

// ADD INDEX
$tbl=array("cartridges_type","computers","consumables_type","contacts","contracts","docs","enterprises","monitors","networking","peripherals","printers","software","users");

foreach ($tbl as $t)
if(!isIndex("glpi_$t","name")) {	
	$query="ALTER TABLE `glpi_$t` ADD INDEX ( `name` ) ";
	$db->query($query) or die("0.65 add index in name field $t ".$lang["update"][90].$db->error());
}

$result=$db->list_tables();
while ($line = $db->fetch_array($result))
if (ereg("glpi_dropdown",$line[0])||ereg("glpi_type",$line[0]))
if(!isIndex($line[0],"name")) {	
	$query="ALTER TABLE `".$line[0]."` ADD INDEX ( `name` ) ";
	$db->query($query) or die("0.65 add index in name field ".$line[0]." ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_reservation_item","device_type_2")) {	
	$query="ALTER TABLE `glpi_reservation_item` ADD INDEX  `device_type_2` ( `device_type`,`id_device` ) ";
	$db->query($query) or die("0.65 add index in reservation_item ".$line[0]." ".$lang["update"][90].$db->error());
}


if(!TableExists("glpi_dropdown_model_phones")) {

$query = "CREATE TABLE `glpi_dropdown_model_phones` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`ID`)
) ENGINE=MyISAM;";
	$db->query($query) or die("0.65 add dropdown_model_phones ".$lang["update"][90].$db->error());
}

if(!TableExists("glpi_type_phones")) {

$query = "CREATE TABLE `glpi_type_phones` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`ID`)
) ENGINE=MyISAM;";
	$db->query($query) or die("0.65 add type_phones ".$lang["update"][90].$db->error());
}


if(!TableExists("glpi_dropdown_phone_power")) {

$query = "CREATE TABLE `glpi_dropdown_phone_power` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`ID`)
) ENGINE=MyISAM;";
	$db->query($query) or die("0.65 add dropdown_phone_power ".$lang["update"][90].$db->error());
}

if(!TableExists("glpi_phones")) {

$query = "CREATE TABLE `glpi_phones` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) default NULL,
  `date_mod` datetime NOT NULL default '0000-00-00 00:00:00',
  `contact` varchar(255) default NULL,
  `contact_num` varchar(255) default NULL,
  `tech_num` int(11) NOT NULL default '0',
  `comments` text,
  `serial` varchar(255) default NULL,
  `otherserial` varchar(255) default NULL,
  `firmware` varchar(255) default NULL,
  `location` int(11) NOT NULL default '0',
  `type` int(11) NOT NULL default '0',
  `model` int(11) default NULL,
  `brand` varchar(255) default NULL,
  `power` tinyint(4) NOT NULL default '0',
  `number_line` varchar(255) NOT NULL default '',
  `flags_casque` tinyint(4) NOT NULL default '0',
  `flags_hp` tinyint(4) NOT NULL default '0',
  `FK_glpi_enterprise` int(11) NOT NULL default '0',
  `is_global` enum('0','1') NOT NULL default '0',
  `deleted` enum('Y','N') NOT NULL default 'N',
  `is_template` enum('0','1') NOT NULL default '0',
  `tplname` varchar(255) default NULL,
  `notes` longtext,
  PRIMARY KEY  (`ID`),
  KEY `type` (`type`),
  KEY `name` (`name`),
  KEY `location` (`location`),
  KEY `FK_glpi_enterprise` (`FK_glpi_enterprise`),
  KEY `deleted` (`deleted`),
  KEY `is_template` (`is_template`),
  KEY `tech_num` (`tech_num`)
) ENGINE=MyISAM;";
	$db->query($query) or die("0.65 add phones ".$lang["update"][90].$db->error());

$query="INSERT INTO `glpi_phones` VALUES (1, NULL, '0000-00-00 00:00:00', NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, 0, '', 0, 0, 0, '0', 'N', '1', 'Blank Template', NULL);";
$db->query($query) or die("0.65 blank template in phones ".$lang["update"][90].$db->error());
}



}


function updateTreeDropdown(){

$db=new DB();
// Update Tree dropdown
if(!FieldExists("glpi_dropdown_locations","completename")) {
$query= "ALTER TABLE `glpi_dropdown_locations` ADD `completename` TEXT NOT NULL ;";
$db->query($query) or die("0.6 add completename in dropdown_locations ".$lang["update"][90].$db->error());	
regenerateTreeCompleteName("glpi_dropdown_locations");
}
if(!FieldExists("glpi_dropdown_kbcategories","completename")) {
$query= "ALTER TABLE `glpi_dropdown_kbcategories` ADD `completename` TEXT NOT NULL ;";
$db->query($query) or die("0.6 add completename in dropdown_kbcategories ".$lang["update"][90].$db->error());	
regenerateTreeCompleteName("glpi_dropdown_kbcategories");
}
}

function showFormSu() {
	include ("_relpos.php");
	global $lang;
	echo "<div align='center'>";
	echo "<h3>".$lang["update"][97]."</h3>";
	echo "<p>".$lang["update"][98]."</p>";
	echo "<p>".$lang["update"][99]."</p>";
	echo "<form action=\"".$_SERVER["PHP_SELF"]."\" method=\"post\">";
	echo "<p>".$lang["update"][100]." <input type=\"text\" name=\"login_su\" /></p>";
	echo "<p>".$lang["update"][101]." <input type=\"password\" name=\"pass_su1\" /></p>";
	echo "<p>".$lang["update"][102]." <input type=\"password\" name=\"pass_su2\" /></p>";
	echo "<input type=\"submit\" class='submit' name=\"ajout_su\" value=\"".$lang["install"][25] ."\" />";
	echo "</form>";
	echo "</div>";
}

//Debut du script
	
	if(!isset($_SESSION)) session_start();
	if(empty($_SESSION["dict"])) $_SESSION["dict"] = "french";
	loadLang($_SESSION["dict"]);
	include ("_relpos.php");
	
	// Send UTF8 Headers
	header("Content-Type: text/html; charset=UTF-8");

	echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">";
        echo "<html>";
        echo "<head>";
        echo " <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">";
        echo "<meta http-equiv=\"Content-Script-Type\" content=\"text/javascript\"> ";
        echo "<meta http-equiv=\"Content-Style-Type\" content=\"text/css\"> ";
        echo "<meta http-equiv=\"Content-Language\" content=\"fr\"> ";
        echo "<meta name=\"generator\" content=\"\">";
        echo "<meta name=\"DC.Language\" content=\"fr\" scheme=\"RFC1766\">";
        echo "<title>Setup GLPI</title>";
       // CSS
	echo "<link rel='stylesheet'  href='style_install.css' type='text/css' media='screen' >";
       
         echo "</head>";
        echo "<body>";
	echo "<div id='principal'>";
	echo "<div id='bloc'>";
	echo "<div class='haut'></div>";
	 echo "<h2>GLPI SETUP</h2>";
	echo "<br/><h3>Update</h3>";

// step 1    avec bouton de confirmation

if(empty($_POST["continuer"]) && empty($_POST["ajout_su"]) && empty($_POST["from_update"])) {
	$db = new DB;
	if(empty($from_install)&&!isset($_POST["from_update"])) {
		echo "<div align='center'>";
		echo "<h3><span class='red'>".$lang["update"][105]."</span>";
		echo "<p class='submit'> <a href=\"index.php\"><span class='button'>".$lang["update"][106]."</span></a></p>";
		echo "</div>";
	}
	else {
		echo "<div align='center'>";
		echo "<h3><span class='red'>".$lang["update"][91]."</span>".$lang["update"][92]. $db->dbdefault ."</h3>";
	
		echo "<form action=\"update.php\" method=\"post\">";
		echo "<input type=\"submit\" class='submit' name=\"continuer\" value=\"".$lang["install"][25] ."\" />";
		echo "</form></div>";
	}
}
// Step 2  
elseif(empty($_POST["ajout_su"])) {
	if(test_connect()) {
		echo "<h3>".$lang["update"][93]."</h3>";
		if (!isset($_POST["update_location"]))
			if(!TableExists("glpi_config")) {
				updateDbTo031();
				$tab = updateDbUpTo031();
				updaterootdoc();
			} else {
				$tab = updateDbUpTo031();
				updaterootdoc();
			}
		
		if(!superAdminExists()) {
			showFormSu();
		}
		else {
			echo "<div align='center'>";
			if(!empty($tab) && $tab["adminchange"]) {
				echo "<div align='center'> <h2>". $lang["update"][96] ."<h2></div>";
			}
			if (showLocationUpdateForm()){
				// Get current version
				$db=new DB();
				$query="SELECT version FROM glpi_config";
				$result=$db->query($query) or die("get current version".$db->error());
				$current_version=trim($db->result($result,0,0));

				switch ($current_version){
					case "0.31": 
					case "0.4": 
					case "0.41": 
					case "0.42": 
					case "0.5": 
					case "0.51": 
					case "0.51a": 
						showContentUpdateForm();
						break;
					default:
					echo "<a href=\"index.php\"><span class='button'>".$lang["install"][64]."</span></a>";
						break;
				}
				}
		echo "</div>";
		}
	}
	else {
		echo "<h3> ";
		echo $lang["update"][95] ."</h3>";
        }
	echo "<div class='bas'></div></div></div></body></html>";
}
elseif(!empty($_POST["ajout_su"])) {
	if(!empty($_POST["pass_su1"]) && !empty($_POST["login_su"]) && $_POST["pass_su1"] == $_POST["pass_su2"]) {

		include ($phproot . "/glpi/users/classes.php");

		$user = new User;
		$user->fields["name"]=$_POST["login_su"];
		$user->fields["password"]=$_POST["pass_su1"];
		$user->fields["type"]="super-admin";
		$user->fields["language"]=$_SESSION["dict"];
		$user->addToDB();

		echo "<div align='center'>";
		echo "<h3>".$lang["update"][104]."</h3>";
		echo "</div>";
		if (showLocationUpdateForm()){
				// Get current version
				$db=new DB();
				$query="SELECT version FROM glpi_config";
				$result=$db->query($query) or die("get current version".$db->error());
				$current_version=trim($db->result($result,0,0));

				switch ($current_version){
					case "0.31": 
					case "0.4": 
					case "0.41": 
					case "0.42": 
					case "0.5": 
					case "0.51": 
					case "0.51a": 
						showContentUpdateForm();
						break;
					default:
					echo "<div align='center'><a href=\"index.php\"><span class='button'>".$lang["install"][64]."</span></a></div>";
						break;
				}
			
		}
	}
	else {
		echo "<div align='center' color='red'>";
		echo "<h3>".$lang["update"][103]."</h3>";
		echo "</div>";
		showFormSu();
	}
}



?>
