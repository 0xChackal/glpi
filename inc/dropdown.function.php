<?php
/*
 * @version $Id$
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

if (!defined('GLPI_ROOT')){
	die("Sorry. You can't access directly to this file");
	}

// Functions Dropdown




/**
 * Print out an HTML "<select>" for a dropdown
 *
 * 
 * 
 *
 * @param $table the dropdown table from witch we want values on the select
 * @param $myname the name of the HTML select
 * @param $display_comments display the comments near the dropdown
 * @param $entity_restrict Restrict to a defined entity
 * @return nothing (display the select box)
 **/
function dropdown($table,$myname,$display_comments=1,$entity_restrict=-1) {

	return dropdownValue($table,$myname,0,$display_comments,$entity_restrict);
}

/**
 * Print out an HTML "<select>" for a dropdown with preselected value
 *
 *
 *
 *
 *
 * @param $table the dropdown table from witch we want values on the select
 * @param $myname the name of the HTML select
 * @param $value the preselected value we want
 * @param $display_comments display the comments near the dropdown
 * @param $entity_restrict Restrict to a defined entity
 * @return nothing (display the select box)
 *
 */
function dropdownValue($table,$myname,$value=0,$display_comments=1,$entity_restrict=-1) {

	global $DB,$CFG_GLPI,$LANG;

	$rand=mt_rand();

	displaySearchTextAjaxDropdown($myname.$rand);
	$name="------";
	$comments="";
	$limit_length=$CFG_GLPI["dropdown_limit"];
	if (empty($value)) $value=0;
	if ($value>0){
		$tmpname=getDropdownName($table,$value,1);
		if ($tmpname["name"]!="&nbsp;"){
			$name=$tmpname["name"];
			$comments=$tmpname["comments"];
			$limit_length=max(strlen($name),$CFG_GLPI["dropdown_limit"]);
		}
	}

	echo "<script type='text/javascript' >\n";
	echo "   new Form.Element.Observer('search_$myname$rand', 1, \n";
	echo "      function(element, value) {\n";
	echo "      	new Ajax.Updater('results_$myname$rand','".$CFG_GLPI["root_doc"]."/ajax/dropdownValue.php',{asynchronous:true, evalScripts:true, \n";
	echo "           onComplete:function(request)\n";
	echo "            {Element.hide('search_spinner_$myname$rand');}, \n";
	echo "           onLoading:function(request)\n";
	echo "            {Element.show('search_spinner_$myname$rand');},\n";
	echo "           method:'post', parameters:'searchText='+value+'&value=$value&table=$table&myname=$myname&limit=$limit_length&comments=$display_comments&rand=$rand&entity_restrict=$entity_restrict'\n";
	echo "})})\n";
	echo "</script>\n";

	echo "<div id='search_spinner_$myname$rand' style=' position:absolute;  filter:alpha(opacity=70); -moz-opacity:0.7; opacity: 0.7; display:none;'><img src=\"".$CFG_GLPI["root_doc"]."/pics/wait.png\" title='Processing....' alt='Processing....' /></div>\n";

	$nb=0;
	if ($CFG_GLPI["use_ajax"]){
		if (!in_array($table,$CFG_GLPI["specif_entities_tables"])){
			$nb=countElementsInTable($table);
		} else {
			if ($entity_restrict>=0){
				$nb=countElementsInTableForEntity($table,$entity_restrict);
			} else {
				$nb=countElementsInTableForMyEntities($table);
			}
		}
	}

	if (!$CFG_GLPI["use_ajax"]||$nb<$CFG_GLPI["ajax_limit_count"]){
		echo "<script type='text/javascript' >\n";
		echo "Element.hide('search_spinner_$myname$rand');";
		echo "Element.hide('search_$myname$rand');";
		echo "</script>\n";
	}

	echo "<span id='results_$myname$rand'>\n";
	if (!$CFG_GLPI["use_ajax"]||$nb<$CFG_GLPI["ajax_limit_count"]){
		$_POST["myname"]=$myname;
		$_POST["table"]=$table;
		$_POST["value"]=$value;
		$_POST["rand"]=$rand;
		$_POST["comments"]=$display_comments;
		$_POST["entity_restrict"]=$entity_restrict;
		$_POST["limit"]=$limit_length;
		$_POST["searchText"]=$CFG_GLPI["ajax_wildcard"];
		include (GLPI_ROOT."/ajax/dropdownValue.php");
	} else {
		echo "<select name='$myname'><option value='$value'>$name</option></select>\n";
	}
	echo "</span>\n";	

	$comments_display="";
	$comments_display2="";
	if ($display_comments) {
		$comments_display=" onmouseout=\"cleanhide('comments_$myname$rand')\" onmouseover=\"cleandisplay('comments_$myname$rand')\" ";
		$comments_display2="<span class='over_link' id='comments_$myname$rand'>".nl2br($comments)."</span>";
	}

	$which="";

	$dropdown_right=false;

	if (ereg("glpi_dropdown_",$table)||ereg("glpi_type_",$table)){
		if (!in_array($table,$CFG_GLPI["specif_entities_tables"])){
			$dropdown_right=haveRight("dropdown","w");
		} else {
			$dropdown_right=haveRight("entity_dropdown","w");
		}

		if ($dropdown_right){

//			$search=array("/glpi_dropdown_/","/glpi_type_/");
//			$replace=array("","");
//			$which=preg_replace($search,$replace,$table);
			$which=$table;
		}
	}

	if ($display_comments){
		echo "<img alt='".$LANG["common"][25]."' src='".$CFG_GLPI["root_doc"]."/pics/aide.png' $comments_display ";
		if ($dropdown_right&&!empty($which)) echo " style='cursor:pointer;'  onClick=\"window.open('".$CFG_GLPI["root_doc"]."/front/popup.php?popup=dropdown&amp;which=$which"."&amp;rand=$myname$rand&amp;FK_entities=$entity_restrict' ,'glpipopup', 'height=400, width=1000, top=100, left=100, scrollbars=yes' )\"";
		echo ">";
		echo $comments_display2;
	}

	if ($table=="glpi_enterprises"){
		echo getEnterpriseLinks($value);	
	}

	return $rand;
}



/**
 * Make a select box without parameters value
 *
 *
* @param $table the dropdown table from witch we want values on the select
 * @param $myname the name of the HTML select
 * @param $value the preselected value we want
 * @return nothing (print out an HTML select box)
 * 
 */
function dropdownNoValue($table,$myname,$value) {
	// Make a select box without parameters value

	global $DB,$CFG_GLPI;

	$where="";
	if (in_array($table,$CFG_GLPI["deleted_tables"])){
		$where="WHERE deleted='0'";
	}
	if (in_array($table,$CFG_GLPI["template_tables"])){
		$where.="AND is_template='0'";
	}

	if (in_array($table,$CFG_GLPI["dropdowntree_tables"])){
		$query = "SELECT ID FROM $table $where ORDER BY completename";
	}
	else {
		$query = "SELECT ID FROM $table $where ORDER BY name";
	}

	$result = $DB->query($query);

	echo "<select name=\"$myname\" size='1'>";
	$i = 0;
	$number = $DB->numrows($result);
	if ($number > 0) {
		while ($i < $number) {
			$ID = $DB->result($result, $i, "ID");
			if ($ID === $value) {
			} else {
				echo "<option value=\"$ID\">".getDropdownName($table,$ID)."</option>";
			}
			$i++;
		}
	}
	echo "</select>";
}


/**
 * Make a select box with all glpi users where select key = name
 *
 * Think it's unused now.
 *
 *
 * @param $myname select name
 * @param $value default value
 * @param $right limit user who have specific right : interface -> central ; ID -> only current user ; all -> all users ; sinon specific right like show_ticket, create_ticket....
 * @param $all Nobody or All display for none selected
 * @param $display_comments display comments near the dropdown
 * @param $entity_restrict Restrict to a defined entity
 * @param $helpdesk_ajax use ajax for helpdesk auto update (mail device_type)
 * @return nothing (print out an HTML select box)
 *
 *
 */
// $all =0 -> Nobody $all=1 -> All $all=-1-> nothing
function dropdownUsers($myname,$value,$right,$all=0,$display_comments=1,$entity_restrict=-1,$helpdesk_ajax=0) {
	// Make a select box with all glpi users

	global $DB,$CFG_GLPI,$LANG;

	$rand=mt_rand();

	displaySearchTextAjaxDropdown($myname.$rand);
	
	echo "<script type='text/javascript' >\n";
	echo "   new Form.Element.Observer('search_$myname$rand', 1, \n";
	echo "      function(element, value) {\n";
	echo "      	new Ajax.Updater('results_$myname$rand','".$CFG_GLPI["root_doc"]."/ajax/dropdownUsers.php',{asynchronous:true, evalScripts:true, \n";
	echo "           onComplete:function(request)\n";
	echo "            {Element.hide('search_spinner_$myname$rand');}, \n";
	echo "           onLoading:function(request)\n";
	echo "            {Element.show('search_spinner_$myname$rand');},\n";
	echo "           method:'post', parameters:'searchText=' + value+'&value=$value&myname=$myname&all=$all&right=$right&comments=$display_comments&rand=$rand&helpdesk_ajax=$helpdesk_ajax&entity_restrict=$entity_restrict'\n";
	echo "})})\n";
	echo "</script>\n";

	echo "<div id='search_spinner_$myname$rand' style=' position:absolute;   filter:alpha(opacity=70); -moz-opacity:0.7; opacity: 0.7; display:none;'><img src=\"".$CFG_GLPI["root_doc"]."/pics/wait.png\" title='Processing....' alt='Processing....' /></div>\n";

	$nb=0;
	if ($CFG_GLPI["use_ajax"])
		$nb=countElementsInTable("glpi_users");

	if (!$CFG_GLPI["use_ajax"]||$nb<$CFG_GLPI["ajax_limit_count"]){
		echo "<script type='text/javascript' >\n";
		echo "Element.hide('search_spinner_$myname$rand');";
		echo "Element.hide('search_$myname$rand');";
		//echo "document.getElementById('search_$myname$rand').value='".$CFG_GLPI["ajax_wildcard"]."';";
		echo "</script>\n";
	}

	$default_display="";
	$comments_display="";

	$user=getUserName($value,2);

	$default_display="<select id='dropdown_".$myname.$rand."' name='$myname'><option value='$value'>".substr($user["name"],0,$CFG_GLPI["dropdown_limit"])."</option></select>\n";
	if ($display_comments) {
		$comments_display="<a href='".$user["link"]."'>";
		$comments_display.="<img alt='".$LANG["common"][25]."' src='".$CFG_GLPI["root_doc"]."/pics/aide.png' onmouseout=\"cleanhide('comments_$myname$rand')\" onmouseover=\"cleandisplay('comments_$myname$rand')\">";
		$comments_display.="</a>";
		$comments_display.="<span class='over_link' id='comments_$myname$rand'>".$user["comments"]."</span>";
	}

	echo "<span id='results_$myname$rand'>\n";
	if (!$CFG_GLPI["use_ajax"]||$nb<$CFG_GLPI["ajax_limit_count"]){
		$_POST["myname"]=$myname;
		$_POST["all"]=$all;
		$_POST["value"]=$value;
		$_POST["right"]=$right;
		$_POST["rand"]=$rand;
		$_POST["comments"]=$display_comments;
		$_POST["searchText"]=$CFG_GLPI["ajax_wildcard"];
		$_POST["helpdesk_ajax"]=$helpdesk_ajax;
		$_POST["entity_restrict"]=$entity_restrict;	

		include (GLPI_ROOT."/ajax/dropdownUsers.php");
	} else {
		if (!empty($value)&&$value>0){
			echo $default_display;
		} else {
			if ($all)
				echo "<select name='$myname'><option value='0'>[ ".$LANG["search"][7]." ]</option></select>\n";
			else 
				echo "<select name='$myname'><option value='0'>[ Nobody ]</option></select>\n";
		}
	}

	echo "</span>\n";

	echo $comments_display;

	return $rand;
}


/**
 * Make a select box with all glpi users
 *
 *
* @param $myname select name
 * @param $value default value
 * @param $display_comments display comments near the dropdown
 * @param $entity_restrict Restrict to a defined entity
 * @param $helpdesk_ajax use ajax for helpdesk auto update (mail device_type)
 * @return nothing (print out an HTML select box)
 * 
 */
function dropdownAllUsers($myname,$value=0,$display_comments=1,$entity_restrict=-1,$helpdesk_ajax=0) {
	return dropdownUsers($myname,$value,"all",0,$display_comments,$entity_restrict,$helpdesk_ajax);
}


/**
 * Make a select box with all glpi users where select key = ID
 *
 *
 *
* @param $myname select name
 * @param $value default value
 * @param $right limit user who have specific right : interface -> central ; ID -> only current user ; all -> all users ; sinon specific right like show_ticket, create_ticket....
 * @param $entity_restrict Restrict to a defined entity
 * @param $display_comments display comments near the dropdown
 * @param $all Nobody or All display for none selected
 * @return nothing (print out an HTML select box)
 */
function dropdownUsersID($myname,$value,$right,$display_comments=1,$entity_restrict=-1) {
	// Make a select box with all glpi users

	return dropdownUsers($myname,$value,$right,0,$display_comments,$entity_restrict);
}

/**
 * Get the value of a dropdown 
 *
 *
 * Returns the value of the dropdown from $table with ID $id.
 *
* @param $table the dropdown table from witch we want values on the select
 * @param $id id of the element to get
 * @param $withcomments give array with name and comments
 * @return string the value of the dropdown or &nbsp; if not exists
 */
function getDropdownName($table,$id,$withcomments=0) {
	global $DB,$CFG_GLPI,$LANG;

	if (in_array($table,$CFG_GLPI["dropdowntree_tables"])){
		return getTreeValueCompleteName($table,$id,$withcomments);

	} else	{

		$name = "";
		$comments = "";
		if ($id){
			$query = "select * from ". $table ." where ID = '". $id ."'";
			if ($result = $DB->query($query)){
				if($DB->numrows($result) != 0) {
					$data=$DB->fetch_assoc($result);
					$name = $data["name"];
					if (isset($data["comments"])){
						$comments = $data["comments"];
					}
					switch ($table){
						case "glpi_contacts" :
							$name .= " ".$data["firstname"];
							if (!empty($data["phone"])){
								$comments.="<br><strong>".$LANG["financial"][29].":</strong> ".$data["phone"];
							}
							if (!empty($data["phone2"])){
								$comments.="<br><strong>".$LANG["financial"][29]." 2:</strong> ".$data["phone2"];
							}
							if (!empty($data["mobile"])){
								$comments.="<br><strong>".$LANG["common"][42].":</strong> ".$data["mobile"];
							}
							if (!empty($data["fax"])){
								$comments.="<br><strong>".$LANG["financial"][30].":</strong> ".$data["fax"];
							}
							if (!empty($data["email"])){
								$comments.="<br><strong>".$LANG["setup"][14].":</strong> ".$data["email"];
							}
	
							
							break;
						case "glpi_dropdown_netpoint":
							$name .= " (".getDropdownName("glpi_dropdown_locations",$data["location"]).")";
							break;
						case "glpi_software":
							$name .= "  (v. ".$data["version"].")";
							
							if ($data["platform"]!=0 && $data["helpdesk_visible"] != 0)
								$comments.="<br>".$LANG["software"][3].": ".getDropdownName("glpi_dropdown_os",$data["platform"]);
							break;
					}
	
				}
			}
		}
	}
	if (empty($name)) $name="&nbsp;";
	if ($withcomments) return array("name"=>$name,"comments"=>$comments);
	else return $name;
}

/**
 * Make a select box with all glpi users in tracking table
 *
 *
 *
 * @param $myname the name of the HTML select
 * @param $value the preselected value we want
 * @param $field field of the glpi_tracking table to lookiup for possible users
 * @param $display_comments display the comments near the dropdown
 * @return nothing (print out an HTML select box)
 */

function dropdownUsersTracking($myname,$value,$field,$display_comments=1) {
	global $CFG_GLPI,$LANG,$DB;

	$rand=mt_rand();

	displaySearchTextAjaxDropdown($myname.$rand);

	echo "<script type='text/javascript' >\n";
	echo "   new Form.Element.Observer('search_$myname$rand', 1, \n";
	echo "      function(element, value) {\n";
	echo "      	new Ajax.Updater('results_$myname$rand','".$CFG_GLPI["root_doc"]."/ajax/dropdownUsersTracking.php',{asynchronous:true, evalScripts:true, \n";
	echo "           onComplete:function(request)\n";
	echo "            {Element.hide('search_spinner_$myname$rand');}, \n";
	echo "           onLoading:function(request)\n";
	echo "            {Element.show('search_spinner_$myname$rand');},\n";
	echo "           method:'post', parameters:'searchText=' + value+'&value=$value&field=$field&myname=$myname&comments=$display_comments&rand=$rand'\n";
	echo "})})\n";
	echo "</script>\n";

	echo "<div id='search_spinner_$myname$rand' style=' position:absolute;   filter:alpha(opacity=70); -moz-opacity:0.7; opacity: 0.7; display:none;'><img src=\"".$CFG_GLPI["root_doc"]."/pics/wait.png\" title='Processing....' alt='Processing....' /></div>\n";

	$nb=0;
	if ($CFG_GLPI["use_ajax"]){
		$query="SELECT COUNT(".$field.") FROM glpi_tracking ".getEntitiesRestrictRequest("WHERE","glpi_tracking");
		$result=$DB->query($query);
		$nb=$DB->result($result,0,0);
	}

	if (!$CFG_GLPI["use_ajax"]||$nb<$CFG_GLPI["ajax_limit_count"]){
		echo "<script type='text/javascript' >\n";
		echo "Element.hide('search_spinner_$myname$rand');";
		echo "Element.hide('search_$myname$rand');";
		echo "</script>\n";
	}

	$default_display="";
	$comments_display="";
	$user=getUserName($value,2);
	$default_display="<select name='$myname'><option value='$value'>".substr($user["name"],0,$CFG_GLPI["dropdown_limit"])."</option></select>\n";
	if ($display_comments) {
		$comments_display="<a href='".$user["link"]."'>";
		$comments_display.="<img alt='".$LANG["common"][25]."' src='".$CFG_GLPI["root_doc"]."/pics/aide.png' onmouseout=\"cleanhide('comments_$myname$rand')\" onmouseover=\"cleandisplay('comments_$myname$rand')\">";
		$comments_display.="</a>";
		$comments_display.="<span class='over_link' id='comments_$myname$rand'>".$user["comments"]."</span>";
	}


	echo "<span id='results_$myname$rand'>\n";
	if (!$CFG_GLPI["use_ajax"]||$nb<$CFG_GLPI["ajax_limit_count"]){
		$_POST["myname"]=$myname;
		$_POST["value"]=$value;
		$_POST["field"]=$field;
		$_POST["rand"]=$rand;
		$_POST["comments"]=$display_comments;
		$_POST["searchText"]=$CFG_GLPI["ajax_wildcard"];
		include (GLPI_ROOT."/ajax/dropdownUsersTracking.php");

	}else {
		if (!empty($value)&&$value>0){
			echo $default_display;
		} else {
			echo "<select name='$myname'><option value='0'>[ ".$LANG["search"][7]." ]</option></select>\n";
		}
	}
	echo "</span>\n";	

	echo $comments_display;	

	return $rand;
}

/**
 * 
 * Make a select box for icons
 *
 *
 * @param $value the preselected value we want
 * @param $myname the name of the HTML select
 * @param $store_path path where icons are stored
 * @return nothing (print out an HTML select box)
 */
function dropdownIcons($myname,$value,$store_path){
	global $LANG;
	if (is_dir($store_path)){
		if ($dh = opendir($store_path)) {
			echo "<select name=\"$myname\">";
			echo "<option value=''>-----</option>";
			while (($file = readdir($dh)) !== false) {
				if (eregi(".png$",$file)){
					if ($file == $value) {
						echo "<option value=\"$file\" selected>".$file;
					} else {
						echo "<option value=\"$file\">".$file;
					}
					echo "</option>";
				}
			}
			closedir($dh);
			echo "</select>";
		} else echo "Error reading directory $store_path";
	} else echo "Error $store_path is not a directory";
}


/**
 * 
 * Make a select box for device type
 *
 *
 * @param $name name of the select box
 * @param $device_type default device type
 * @param $soft with softwares ?
 * @param $cart with cartridges ?
 * @param $cons with consumables ?
 * @return nothing (print out an HTML select box)
 */
function dropdownDeviceType($name,$device_type,$soft=1,$cart=1,$cons=1){
	global $LANG;
	echo "<select name='$name'>\n";
	echo "<option value='0'>-----</option>\n";
	echo "<option value='".COMPUTER_TYPE."' ".(($device_type==COMPUTER_TYPE)?" selected":"").">".$LANG["help"][25]."</option>\n";
	echo "<option value='".NETWORKING_TYPE."' ".(($device_type==NETWORKING_TYPE)?" selected":"").">".$LANG["help"][26]."</option>\n";
	echo "<option value='".PRINTER_TYPE."' ".(($device_type==PRINTER_TYPE)?" selected":"").">".$LANG["help"][27]."</option>\n";
	echo "<option value='".MONITOR_TYPE."' ".(($device_type==MONITOR_TYPE)?" selected":"").">".$LANG["help"][28]."</option>\n";
	echo "<option value='".PERIPHERAL_TYPE."' ".(($device_type==PERIPHERAL_TYPE)?" selected":"").">".$LANG["help"][29]."</option>\n";
	echo "<option value='".PHONE_TYPE."' ".(($device_type==PHONE_TYPE)?" selected":"").">".$LANG["help"][35]."</option>\n";

	if ($soft)
		echo "<option value='".SOFTWARE_TYPE."' ".(($device_type==SOFTWARE_TYPE)?" selected":"").">".$LANG["help"][31]."</option>\n";
	if ($cart)
		echo "<option value='".CARTRIDGE_TYPE."' ".(($device_type==CARTRIDGE_TYPE)?" selected":"").">".$LANG["Menu"][21]."</option>\n";
	if ($cons)
		echo "<option value='".CONSUMABLE_TYPE."' ".(($device_type==CONSUMABLE_TYPE)?" selected":"").">".$LANG["Menu"][32]."</option>\n";
	echo "<option value='".CONTACT_TYPE."' ".(($device_type==CONTACT_TYPE)?" selected":"").">".$LANG["Menu"][22]."</option>\n";
	echo "<option value='".ENTERPRISE_TYPE."' ".(($device_type==ENTERPRISE_TYPE)?" selected":"").">".$LANG["Menu"][23]."</option>\n";
	echo "<option value='".CONTRACT_TYPE."' ".(($device_type==CONTRACT_TYPE)?" selected":"").">".$LANG["Menu"][25]."</option>\n";
	echo "</select>\n";


}



/**
 * 
 *Make a select box for all items
 *
 *
* @param $myname select name
 * @param $value default value
 * @param $value_type default value for the device type
 * @param $entity_restrict Restrict to a defined entity
* @param $types Types used
 * @return nothing (print out an HTML select box)
 */
function dropdownAllItems($myname,$value_type=0,$value=0,$entity_restrict=-1,$types='') {
	global $LANG,$CFG_GLPI;
	if (!is_array($types)){
		$types=$CFG_GLPI["state_types"];
	}
	$rand=mt_rand();
	$ci=new CommonItem();
	$options=array();
	
	foreach ($types as $type){
		$ci->setType($type);
		$options[$type]=$ci->getType();
	}
	asort($options);
	if (count($options)){
		echo "<table border='0'><tr><td>\n";
	
		echo "<select name='type' id='item_type$rand'>\n";
			echo "<option value='0'>-----</option>\n";
		foreach ($options as $key => $val){
			echo "<option value='".$key."'>".$val."</option>\n";
		}
		echo "</select>";
	
		echo "<script type='text/javascript' >\n";
		echo "   new Form.Element.Observer('item_type$rand', 1, \n";
		echo "      function(element, value) {\n";
		echo "      	new Ajax.Updater('show_$myname$rand','".$CFG_GLPI["root_doc"]."/ajax/dropdownAllItems.php',{asynchronous:true, evalScripts:true, \n";	echo "           onComplete:function(request)\n";
		echo "            {Element.hide('search_spinner_$myname$rand');}, \n";
		echo "           onLoading:function(request)\n";
		echo "            {Element.show('search_spinner_$myname$rand');},\n";
		echo "           method:'post', parameters:'idtable='+value+'&myname=$myname&value=$value&entity_restrict=$entity_restrict'\n";
		echo "})})\n";
		echo "</script>\n";
	
		echo "<div id='search_spinner_$myname$rand' style=' position:absolute;   filter:alpha(opacity=70); -moz-opacity:0.7; opacity: 0.7; display:none;'><img src=\"".$CFG_GLPI["root_doc"]."/pics/wait.png\" title='Processing....' alt='Processing....' /></div>\n";
		echo "</td><td>\n"	;
		echo "<span id='show_$myname$rand'>&nbsp;</span>\n";
		echo "</td></tr></table>\n";
	
		if ($value>0){
			echo "<script type='text/javascript' >\n";
			echo "Element.hide('search_spinner_$myname$rand');";
			echo "document.getElementById('item_type$rand').value='".$value_type."';";
			echo "</script>\n";
		}
	}
	return $rand;
}


/**
 * Make a select box for a boolean choice (Yes/No)
 *
 *
 *
 * @param $name select name
 * @param $value preselected value.
 * @return nothing (print out an HTML select box)
 */
function dropdownYesNo($name,$value=0){
	global $LANG;
	echo "<select name='$name'>\n";
	echo "<option value='0' ".(!$value?" selected ":"").">".$LANG["choice"][0]."</option>\n";
	echo "<option value='1' ".($value?" selected ":"").">".$LANG["choice"][1]."</option>\n";
	echo "</select>\n";	
}	

function getYesNo($value){
	global $LANG;
	if ($value){
		return $LANG["choice"][1];
	} else {
		return $LANG["choice"][0];
	}
}
/**
 * Make a select box for a None Read Write choice
 *
 *
 *
 * @param $name select name
 * @param $value preselected value.
 * @param $none display none choice ? 
 * @param $read display read choice ? 
 * @param $write display write choice ? 
 * @return nothing (print out an HTML select box)
 */
function dropdownNoneReadWrite($name,$value,$none=1,$read=1,$write=1){
	global $LANG;
	echo "<select name='$name'>\n";
	if ($none)
		echo "<option value='' ".(empty($value)?" selected ":"").">".$LANG["profiles"][12]."</option>\n";
	if ($read)
		echo "<option value='r' ".($value=='r'?" selected ":"").">".$LANG["profiles"][10]."</option>\n";
	if ($write)
		echo "<option value='w' ".($value=='w'?" selected ":"").">".$LANG["profiles"][11]."</option>\n";
	echo "</select>\n";	
}	

/**
 * Make a select box for Tracking my devices
 *
 *
 * @param $userID User ID for my device section
 * @return nothing (print out an HTML select box)
 */
function dropdownMyDevices($userID=0){
	global $DB,$LANG,$CFG_GLPI,$LINK_ID_TABLE;

	if ($userID==0) $userID=$_SESSION["glpiID"];

	$rand=mt_rand();

	$already_add=array();

	if ($_SESSION["glpiactiveprofile"]["helpdesk_hardware"]&pow(2,HELPDESK_MY_HARDWARE)){
		$my_devices="";

		$ci=new CommonItem();
		$my_item="";

		if (isset($_SESSION["helpdeskSaved"]["_my_items"])) $my_item=$_SESSION["helpdeskSaved"]["_my_items"];

		// My items
		foreach ($CFG_GLPI["linkuser_type"] as $type){
			if ($_SESSION["glpiactiveprofile"]["helpdesk_hardware_type"]&pow(2,$type)){
				$query="SELECT * from ".$LINK_ID_TABLE[$type]." WHERE FK_users='".$userID."' AND deleted='0' ";
				$query.=getEntitiesRestrictRequest("AND",$LINK_ID_TABLE[$type]);
				$result=$DB->query($query);
				if ($DB->numrows($result)>0){
					$ci->setType($type);
					$type_name=$ci->getType();
					
					while ($data=$DB->fetch_array($result)){
						$my_devices.="<option value='".$type."_".$data["ID"]."' ".($my_item==$type."_".$data["ID"]?"selected":"").">$type_name - ".$data["name"].($CFG_GLPI["view_ID"]?" (".$data["ID"].")":"")."</option>";
						$already_add[$type][]=$data["ID"];
					}
				}
			}
		}
		if (!empty($my_devices)){
			$my_devices="<optgroup label=\"".$LANG["tracking"][1]."\">".$my_devices."</optgroup>";
		}


		// My group items
		if (haveRight("show_group_hardware","1")){
			$group_where="";
			$groups=array();
			$query="SELECT glpi_users_groups.FK_groups, glpi_groups.name FROM glpi_users_groups LEFT JOIN glpi_groups ON (glpi_groups.ID = glpi_users_groups.FK_groups) WHERE glpi_users_groups.FK_users='".$userID."';";
			$query.=getEntitiesRestrictRequest("AND","glpi_groups");
			$result=$DB->query($query);
			$first=true;
			if ($DB->numrows($result)>0){
				while ($data=$DB->fetch_array($result)){
					if ($first) $first=false;
					else $group_where.=" OR ";
	
					$group_where.=" FK_groups = '".$data["FK_groups"]."' ";
				}

				$tmp_device="";
				foreach ($CFG_GLPI["linkuser_type"] as $type){
					if ($_SESSION["glpiactiveprofile"]["helpdesk_hardware_type"]&pow(2,$type))
					{
						$query="SELECT * from ".$LINK_ID_TABLE[$type]." WHERE $group_where AND deleted='0'";
						$query.=getEntitiesRestrictRequest("AND",$LINK_ID_TABLE[$type]);
						$result=$DB->query($query);
						if ($DB->numrows($result)>0){
							$ci->setType($type);
							$type_name=$ci->getType();
							if (!isset($already_add[$type])) $already_add[$type]=array();
							while ($data=$DB->fetch_array($result)){
								if (!in_array($data["ID"],$already_add[$type])){
									$tmp_device.="<option value='".$type."_".$data["ID"]."' ".($my_item==$type."_".$data["ID"]?"selected":"").">$type_name - ".$data["name"].($CFG_GLPI["view_ID"]?" (".$data["ID"].")":"")."</option>";
									$already_add[$type][]=$data["ID"];
								}
							}
						}
					}
				}
				if (!empty($tmp_device)){
					$my_devices.="<optgroup label=\"".$LANG["tracking"][1]." - ".$LANG["common"][35]."\">".$tmp_device."</optgroup>";
				}
			}
		}

		// Get linked items to computers
		if (isset($already_add[COMPUTER_TYPE])&&count($already_add[COMPUTER_TYPE])){
			$search_computer=" (";
			$first=true;
			foreach ($already_add[COMPUTER_TYPE] as $ID){
				if ($first) $first=false;
				else $search_computer.= " OR ";
				$search_computer.= " XXXX='$ID' ";
			}
			$search_computer.=" )";

			$tmp_device="";
			// Direct Connection
			$types=array(PERIPHERAL_TYPE,MONITOR_TYPE,PRINTER_TYPE,PHONE_TYPE);
			foreach ($types as $type){
				if ($_SESSION["glpiactiveprofile"]["helpdesk_hardware_type"]&pow(2,$type)){
					if (!isset($already_add[$type])) $already_add[$type]=array();
					$query="SELECT DISTINCT ".$LINK_ID_TABLE[$type].".* FROM glpi_connect_wire LEFT JOIN ".$LINK_ID_TABLE[$type]." ON (glpi_connect_wire.end1=".$LINK_ID_TABLE[$type].".ID) WHERE glpi_connect_wire.type='$type' AND  ".ereg_replace("XXXX","glpi_connect_wire.end2",$search_computer)." AND ".$LINK_ID_TABLE[$type].".deleted='0' ORDER BY ".$LINK_ID_TABLE[$type].".name";
					$result=$DB->query($query);
					if ($DB->numrows($result)>0){
						$ci->setType($type);
						$type_name=$ci->getType();
							while ($data=$DB->fetch_array($result)){
							if (!in_array($data["ID"],$already_add[$type])){
								$tmp_device.="<option value='".$type."_".$data["ID"]."' ".($my_item==$type."_".$data["ID"]?"selected":"").">$type_name - ".$data["name"].($CFG_GLPI["view_ID"]?" (".$data["ID"].")":"")."</option>";
								$already_add[$type][]=$data["ID"];
							}
						}
					}
				}
			}
			if (!empty($tmp_device)){
				$my_devices.="<optgroup label=\"".$LANG["reports"][36]."\">".$tmp_device."</optgroup>";
			}
				
			// Software
			if ($_SESSION["glpiactiveprofile"]["helpdesk_hardware_type"]&pow(2,SOFTWARE_TYPE)){
				$query = "SELECT DISTINCT glpi_licenses.version as version, glpi_software.name as name, glpi_software.ID as ID FROM glpi_inst_software, glpi_software,glpi_licenses ";
				$query.= "WHERE glpi_inst_software.license = glpi_licenses.ID AND glpi_licenses.sID = glpi_software.ID AND ".ereg_replace("XXXX","glpi_inst_software.cID",$search_computer)."AND  glpi_software.helpdesk_visible=1 order by glpi_software.name";
				
				$result=$DB->query($query);
				if ($DB->numrows($result)>0){
					$tmp_device="";
					$ci->setType(SOFTWARE_TYPE);
					$type_name=$ci->getType();
					if (!isset($already_add[SOFTWARE_TYPE])) $already_add[SOFTWARE_TYPE]=array();
					while ($data=$DB->fetch_array($result)){
						if (!in_array($data["ID"],$already_add[SOFTWARE_TYPE])){
							$tmp_device.="<option value='".SOFTWARE_TYPE."_".$data["ID"]."' ".($my_item==SOFTWARE_TYPE."_".$data["ID"]?"selected":"").">$type_name - ".$data["name"]." (v. ".$data["version"].")".($CFG_GLPI["view_ID"]?" (".$data["ID"].")":"")."</option>";
							$already_add[SOFTWARE_TYPE][]=$data["ID"];
						}
					}
					if (!empty($tmp_device)){
						$my_devices.="<optgroup label=\"".ucfirst($LANG["software"][17])."\">".$tmp_device."</optgroup>";
					}
				}
			}
		}
		echo "<div id='tracking_my_devices'>";
		echo $LANG["tracking"][1].":&nbsp;<select id='my_items' name='_my_items'><option value=''>--- ".$LANG["help"][30]." ---</option>$my_devices</select></div>";
	}

}
/**
 * Make a select box for Tracking All Devices
 *
 *
 *
 * @param $myname select name
 * @param $value preselected value.
 * @param $admin is an admin access ? 
 * @param $entity_restrict Restrict to a defined entity
 * @return nothing (print out an HTML select box)
 */
function dropdownTrackingAllDevices($myname,$value,$admin=0,$entity_restrict=-1){
	global $LANG,$CFG_GLPI,$DB,$LINK_ID_TABLE;

	$rand=mt_rand();

	if ($_SESSION["glpiactiveprofile"]["helpdesk_hardware"]==0){
		echo "<input type='hidden' name='$myname' value='0'>";
		echo "<input type='hidden' name='computer' value='0'>";
	} else {
		echo "<div id='tracking_all_devices'>";

		if ($_SESSION["glpiactiveprofile"]["helpdesk_hardware"]&pow(2,HELPDESK_ALL_HARDWARE)){
			if (!$admin&&$_SESSION["glpiactiveprofile"]["helpdesk_hardware"]&pow(2,HELPDESK_MY_HARDWARE)){
				echo $LANG["tracking"][2].":<br>";
			}
			echo "<select id='search_$myname$rand' name='$myname'>\n";

			echo "<option value='0' ".(($value==0)?" selected":"").">".$LANG["help"][30]."</option>\n";
			if ($_SESSION["glpiactiveprofile"]["helpdesk_hardware_type"]&pow(2,COMPUTER_TYPE))
				echo "<option value='".COMPUTER_TYPE."' ".(($value==COMPUTER_TYPE)?" selected":"").">".$LANG["help"][25]."</option>\n";
			if ($_SESSION["glpiactiveprofile"]["helpdesk_hardware_type"]&pow(2,NETWORKING_TYPE))
				echo "<option value='".NETWORKING_TYPE."' ".(($value==NETWORKING_TYPE)?" selected":"").">".$LANG["help"][26]."</option>\n";
			if ($_SESSION["glpiactiveprofile"]["helpdesk_hardware_type"]&pow(2,PRINTER_TYPE))
				echo "<option value='".PRINTER_TYPE."' ".(($value==PRINTER_TYPE)?" selected":"").">".$LANG["help"][27]."</option>\n";
			if ($_SESSION["glpiactiveprofile"]["helpdesk_hardware_type"]&pow(2,MONITOR_TYPE))
				echo "<option value='".MONITOR_TYPE."' ".(($value==MONITOR_TYPE)?" selected":"").">".$LANG["help"][28]."</option>\n";
			if ($_SESSION["glpiactiveprofile"]["helpdesk_hardware_type"]&pow(2,PERIPHERAL_TYPE))
				echo "<option value='".PERIPHERAL_TYPE."' ".(($value==PERIPHERAL_TYPE)?" selected":"").">".$LANG["help"][29]."</option>\n";
			if ($_SESSION["glpiactiveprofile"]["helpdesk_hardware_type"]&pow(2,SOFTWARE_TYPE))
				echo "<option value='".SOFTWARE_TYPE."' ".(($value==SOFTWARE_TYPE)?" selected":"").">".$LANG["help"][31]."</option>\n";
			if ($_SESSION["glpiactiveprofile"]["helpdesk_hardware_type"]&pow(2,PHONE_TYPE))
				echo "<option value='".PHONE_TYPE."' ".(($value==PHONE_TYPE)?" selected":"").">".$LANG["help"][35]."</option>\n";
			echo "</select>\n";

			echo "<script type='text/javascript' >\n";
			echo "   new Form.Element.Observer('search_$myname$rand', 1, \n";
			echo "      function(element, value) {\n";
			echo "      	new Ajax.Updater('results_$myname$rand','".$CFG_GLPI["root_doc"]."/ajax/dropdownTrackingDeviceType.php',{asynchronous:true, evalScripts:true, \n";
			echo "           onComplete:function(request)\n";
			echo "            {Element.hide('search_spinner_$myname$rand');}, \n";
			echo "           onLoading:function(request)\n";
			echo "            {Element.show('search_spinner_$myname$rand');},\n";
			echo "           method:'post', parameters:'type=' + value+'&myname=computer&entity_restrict=$entity_restrict&admin=$admin'\n";
			echo "})})\n";
			echo "</script>\n";


			echo "<div id='search_spinner_$myname$rand' style=' position:absolute;  filter:alpha(opacity=70); -moz-opacity:0.7; opacity: 0.7; display:none;'><img src=\"".$CFG_GLPI["root_doc"]."/pics/wait.png\" title='Processing....' alt='Processing....' /></div>\n";

			echo "<span id='results_$myname$rand'>\n";

			if (isset($_SESSION["helpdeskSaved"]["computer"])){
				$ci=new CommonItem();
				if ($ci->getFromDB($value,$_SESSION["helpdeskSaved"]["computer"])){
					echo "<select name='computer'>\n";
					echo "<option value='".$_SESSION["helpdeskSaved"]["computer"]."'>".$ci->getName()."</option>\n";

					echo "</select>\n";
				}
			}

			echo "</span>\n";	
		}
		echo "</div>";
	}		
	return $rand;
}

/**
 * Make a select box for connections
 *
 *
 *
 * @param $type type to connect
 * @param $fromtype from where the connection is
 * @param $myname select name
 * @param $onlyglobal display only global devices (used for templates)
 * @param $entity_restrict Restrict to a defined entity
 * @return nothing (print out an HTML select box)
 */
function dropdownConnect($type,$fromtype,$myname,$entity_restrict=-1,$onlyglobal=0) {


	global $CFG_GLPI,$LINK_ID_TABLE;

	$rand=mt_rand();

	displaySearchTextAjaxDropdown($myname.$rand);

	echo "<script type='text/javascript' >\n";
	echo "   new Form.Element.Observer('search_$myname$rand', 1, \n";
	echo "      function(element, value) {\n";
	echo "      	new Ajax.Updater('results_$myname$rand','".$CFG_GLPI["root_doc"]."/ajax/dropdownConnect.php',{asynchronous:true, evalScripts:true, \n";
	echo "           onComplete:function(request)\n";
	echo "            {Element.hide('search_spinner_$myname$rand');}, \n";
	echo "           onLoading:function(request)\n";
	echo "            {Element.show('search_spinner_$myname$rand');},\n";
	echo "           method:'post', parameters:'searchText=' + value+'&fromtype=$fromtype&idtable=$type&myname=$myname&onlyglobal=$onlyglobal&entity_restrict=$entity_restrict'\n";
	echo "})})\n";
	echo "</script>\n";

	echo "<div id='search_spinner_$myname$rand' style=' position:absolute;   filter:alpha(opacity=70); -moz-opacity:0.7; opacity: 0.7; display:none;'><img src=\"".$CFG_GLPI["root_doc"]."/pics/wait.png\" title='Processing....' alt='' /></div>\n";

	$nb=0;
	if ($CFG_GLPI["use_ajax"]){
		if ($entity_restrict>=0){
			$nb=countElementsInTableForEntity($LINK_ID_TABLE[$type],$entity_restrict);
		} else {
			$nb=countElementsInTableForMyEntities($LINK_ID_TABLE[$type]);
		}
	}

	if (!$CFG_GLPI["use_ajax"]||$nb<$CFG_GLPI["ajax_limit_count"]){
		echo "<script type='text/javascript' >\n";
		echo "Element.hide('search_spinner_$myname$rand');";
		echo "Element.hide('search_$myname$rand');";
		echo "document.getElementById('search_$myname$rand').value='".$CFG_GLPI["ajax_wildcard"]."';";
		echo "</script>\n";
	}


	echo "<span id='results_$myname$rand'>\n";
	echo "<select name='$myname'><option value='0'>------</option></select>\n";
	echo "</span>\n";	

	return $rand;
}


/**
 * Make a select box for  connected port
 *
 *
 * @param $ID ID of the current port to connect
 * @param $type type of device where to search ports
 * @param $myname select name
 * @return nothing (print out an HTML select box)
 */
function dropdownConnectPort($ID,$type,$myname,$entity_restrict=-1) {


	global $LANG,$CFG_GLPI;

	$rand=mt_rand();
	echo "<select name='type[$ID]' id='item_type$rand'>\n";
	echo "<option value='0'>-----</option>\n";
	echo "<option value='".COMPUTER_TYPE."'>".$LANG["Menu"][0]."</option>\n";
	echo "<option value='".NETWORKING_TYPE."'>".$LANG["Menu"][1]."</option>\n";
	echo "<option value='".PRINTER_TYPE."'>".$LANG["Menu"][2]."</option>\n";
	echo "<option value='".PERIPHERAL_TYPE."'>".$LANG["Menu"][16]."</option>\n";
	echo "<option value='".PHONE_TYPE."'>".$LANG["Menu"][34]."</option>\n";
	echo "</select>\n";


	echo "<script type='text/javascript' >\n";
	echo "   new Form.Element.Observer('item_type$rand', 1, \n";
	echo "      function(element, value) {\n";
	echo "      	new Ajax.Updater('show_$myname$rand','".$CFG_GLPI["root_doc"]."/ajax/dropdownConnectPortDeviceType.php',{asynchronous:true, evalScripts:true, \n";	
	echo "           onComplete:function(request)\n";
	echo "            {Element.hide('search_spinner_$myname$rand');}, \n";
	echo "           onLoading:function(request)\n";
	echo "            {Element.show('search_spinner_$myname$rand');Element.hide('not_connected_display$ID');},\n";
	echo "           method:'post', parameters:'current=$ID&type='+value+'&myname=$myname&entity_restrict=$entity_restrict'\n";
	echo "})})\n";
	echo "</script>\n";

	echo "<div id='search_spinner_$myname$rand' style=' position:absolute;   filter:alpha(opacity=70); -moz-opacity:0.7; opacity: 0.7; display:none;'><img src=\"".$CFG_GLPI["root_doc"]."/pics/wait.png\" title='Processing....' alt='Processing....' /></div>\n";
	echo "<span id='show_$myname$rand'>&nbsp;</span>\n";

	return $rand;
}

/**
 * Make a select box for link document
 *
 * @param $myname name of the select box
 * @param $entity_restrict restrict multi entity
 * @return nothing (print out an HTML select box)
 */
function dropdownDocument($myname,$entity_restrict=-1) {


	global $DB,$LANG,$CFG_GLPI;

	$rand=mt_rand();

	$where="";
	if ($entity_restrict>=0){
		$where=" WHERE FK_entities='".$entity_restrict."'";
	} else {
		$where.=getEntitiesRestrictRequest(" WHERE ","glpi_docs");
	}


	$query="SELECT * FROM glpi_dropdown_rubdocs WHERE ID IN (SELECT DISTINCT rubrique FROM glpi_docs $where) ORDER BY name";
	$result=$DB->query($query);

	echo "<select name='_rubdoc' id='rubdoc'>\n";
	echo "<option value='0'>------</option>\n";
	while ($data=$DB->fetch_assoc($result)){
		echo "<option value='".$data['ID']."'>".$data['name']."</option>\n";
	}
	echo "</select>\n";


	echo "<script type='text/javascript' >\n";
	echo "   new Form.Element.Observer('rubdoc', 1, \n";
	echo "      function(element, value) {\n";
	echo "      	new Ajax.Updater('show_$myname$rand','".$CFG_GLPI["root_doc"]."/ajax/dropdownDocument.php',{asynchronous:true, evalScripts:true, \n";	
	echo "           onComplete:function(request)\n";
	echo "            {Element.hide('search_spinner_$myname$rand');}, \n";
	echo "           onLoading:function(request)\n";
	echo "            {Element.show('search_spinner_$myname$rand');},\n";
	echo "           method:'post', parameters:'rand=$rand&entity_restrict=$entity_restrict&rubdoc='+value+'&myname=$myname'\n";
	echo "})})\n";
	echo "</script>\n";

	echo "<div id='search_spinner_$myname$rand' style=' position:absolute;   filter:alpha(opacity=70); -moz-opacity:0.7; opacity: 0.7; display:none;'><img src=\"".$CFG_GLPI["root_doc"]."/pics/wait.png\" title='Processing....' alt='Processing....' /></div>\n";

	echo "<span id='show_$myname$rand'>";
	$_POST["entity_restrict"]=$entity_restrict;
	$_POST["rubdoc"]=0;
	$_POST["myname"]=$myname;
	$_POST["rand"]=$rand;
	include (GLPI_ROOT."/ajax/dropdownDocument.php");
	echo "</span>\n";

	return $rand;
}


/**
 * Make a select box for  software to install
 *
 *
 * @param $myname select name
 * @param $withtemplate is it a template computer ?
 * @param $massiveaction is it a massiveaction select ?
 * @return nothing (print out an HTML select box)
 */
function dropdownSoftwareToInstall($myname,$withtemplate,$entity_restrict,$massiveaction=0) {
	global $CFG_GLPI;

	$rand=mt_rand();

	displaySearchTextAjaxDropdown($myname.$rand);

	echo "<script type='text/javascript' >\n";
	echo "   new Form.Element.Observer('search_$myname$rand', 1, \n";
	echo "      function(element, value) {\n";
	echo "      	new Ajax.Updater('results_$myname$rand','".$CFG_GLPI["root_doc"]."/ajax/dropdownSelectSoftware.php',{asynchronous:true, evalScripts:true, \n";
	echo "           onComplete:function(request)\n";
	echo "            {Element.hide('search_spinner_$myname$rand');}, \n";
	echo "           onLoading:function(request)\n";
	echo "            {Element.show('search_spinner_$myname$rand');},\n";
	echo "           method:'post', parameters:'searchSoft=' + value+'&myname=$myname&withtemplate=$withtemplate&massiveaction=$massiveaction&entity_restrict=$entity_restrict'\n";
	echo "})})\n";
	echo "</script>\n";

	echo "<div id='search_spinner_$myname$rand' style=' position:absolute;   filter:alpha(opacity=70); -moz-opacity:0.7; opacity: 0.7; display:none;'><img src=\"".$CFG_GLPI["root_doc"]."/pics/wait.png\" title='Processing....' alt='' /></div>\n";


	$nb=0;

	if ($CFG_GLPI["use_ajax"]){
		$nb=countElementsInTableForEntity("glpi_software",$entity_restrict);
	}

	if (!$CFG_GLPI["use_ajax"]||$nb<$CFG_GLPI["ajax_limit_count"]){
		echo "<script type='text/javascript' >\n";
		echo "Element.hide('search_spinner_$myname$rand');";
		echo "Element.hide('search_$myname$rand');";
		echo "document.getElementById('search_$myname$rand').value='".$CFG_GLPI["ajax_wildcard"]."';";
		echo "</script>\n";
	}


	echo "<span id='results_$myname$rand'>\n";
	echo "<select name='$myname'><option value='0'>------</option></select>\n";
	echo "</span>\n";	


	return $rand;
}

/**
 * Show div with auto completion
 *
 * @param $myname text field name
 * @param $table table to search for autocompletion
 * @param $field field to serahc for autocompletion
 * @param $value value to fill text field
 * @param $size size of the text field
 * @param $option option of the textfield
 * @return nothing (print out an HTML div)
 */
function autocompletionTextField($myname,$table,$field,$value='',$size=20,$option=''){
	global $CFG_GLPI;

	if ($CFG_GLPI["use_ajax"]&&$CFG_GLPI["ajax_autocompletion"]){
		$rand=mt_rand();
		echo "<input $option id='textfield_$myname$rand' type='text' name='$myname' value=\"".ereg_replace("\"","''",$value)."\" size='$size'>\n";
		echo "<div id='textfieldupdate_$myname$rand' style='display:none;border:1px solid black;background-color:white;'></div>\n";
		echo "<script type='text/javascript' language='javascript' charset='utf-8'>";
		echo "new Ajax.Autocompleter('textfield_$myname$rand','textfieldupdate_$myname$rand','".$CFG_GLPI["root_doc"]."/ajax/autocompletion.php',{parameters:'table=$table&field=$field&myname=$myname'});";
		echo "</script>";
	}	else {
		echo "<input $option type='text' name='$myname' value=\"".ereg_replace("\"","''",$value)."\" size='$size'>\n";
	}
}


/**
 * Make a select box form  for device type 
 *
 *
 * @param $target URL to post the form
 * @param $cID computer ID
 * @param $withtemplate is it a template computer ?
 * @return nothing (print out an HTML select box)
 */
function device_selecter($target,$cID,$withtemplate='') {
	global $LANG,$CFG_GLPI;

	if (!haveRight("computer","w")) return false;

	if(!empty($withtemplate) && $withtemplate == 2) {
		//do nothing
	} else {
		echo "<table class='tab_cadre_fixe'>";
		echo "<tr  class='tab_bg_1'><td colspan='2' align='right'>";
		echo $LANG["devices"][0].":";
		echo "</td>";
		echo "<td colspan='63'>"; 
		echo "<form action=\"$target\" method=\"post\">";

		$rand=mt_rand();

		echo "<select name=\"new_device_type\" id='device$rand'>";

		echo "<option value=\"-1\">-----</option>";
		echo "<option value=\"".MOBOARD_DEVICE."\">".getDictDeviceLabel(MOBOARD_DEVICE)."</option>";
		echo "<option value=\"".HDD_DEVICE."\">".getDictDeviceLabel(HDD_DEVICE)."</option>";
		echo "<option value=\"".GFX_DEVICE."\">".getDictDeviceLabel(GFX_DEVICE)."</option>";
		echo "<option value=\"".NETWORK_DEVICE."\">".getDictDeviceLabel(NETWORK_DEVICE)."</option>";
		echo "<option value=\"".PROCESSOR_DEVICE."\">".getDictDeviceLabel(PROCESSOR_DEVICE)."</option>";
		echo "<option value=\"".SND_DEVICE."\">".getDictDeviceLabel(SND_DEVICE)."</option>";
		echo "<option value=\"".RAM_DEVICE."\">".getDictDeviceLabel(RAM_DEVICE)."</option>";
		echo "<option value=\"".DRIVE_DEVICE."\">".getDictDeviceLabel(DRIVE_DEVICE)."</option>";
		echo "<option value=\"".CONTROL_DEVICE."\">".getDictDeviceLabel(CONTROL_DEVICE)."</option>";
		echo "<option value=\"".PCI_DEVICE."\">".getDictDeviceLabel(PCI_DEVICE)."</option>";
		echo "<option value=\"".CASE_DEVICE."\">".getDictDeviceLabel(CASE_DEVICE)."</option>";
		echo "<option value=\"".POWER_DEVICE."\">".getDictDeviceLabel(POWER_DEVICE)."</option>";
		echo "</select>";

		echo "<script type='text/javascript' >\n";
		echo "   new Form.Element.Observer('device$rand', 1, \n";
		echo "      function(element, value) {\n";
		echo "      	new Ajax.Updater('showdevice$rand','".$CFG_GLPI["root_doc"]."/ajax/dropdownDevice.php',{asynchronous:true, evalScripts:true, \n";	echo "           onComplete:function(request)\n";
		echo "            {Element.hide('search_spinner_device$rand');}, \n";
		echo "           onLoading:function(request)\n";
		echo "            {Element.show('search_spinner_device$rand');},\n";
		echo "           method:'post', parameters:'idtable='+value+'&myname=new_device_id'\n";
		echo "})})\n";
		echo "</script>\n";

		echo "<div id='search_spinner_device$rand' style=' position:absolute;   filter:alpha(opacity=70); -moz-opacity:0.7; opacity: 0.7; display:none;'><img src=\"".$CFG_GLPI["root_doc"]."/pics/wait.png\" title='Processing....' alt='Processing....' /></div>\n";
		echo "<span id='showdevice$rand'>&nbsp;</span>\n";


		echo "<input type=\"hidden\" name=\"withtemplate\" value=\"".$withtemplate."\" >";
		echo "<input type=\"hidden\" name=\"connect_device\" value=\"".true."\" >";
		echo "<input type=\"hidden\" name=\"cID\" value=\"".$cID."\" >";
		echo "<input type=\"submit\" class ='submit' value=\"".$LANG["buttons"][2]."\" >";
		echo "</form>";
		echo "</td>";
		echo "</tr></table>";
	}
}


function displaySearchTextAjaxDropdown($id,$size=4){
	global $CFG_GLPI;
	echo "<input type='text' ondblclick=\"document.getElementById('search_$id').value='".$CFG_GLPI["ajax_wildcard"]."';\" id='search_$id' name='____data_$id' size='$size'>\n";

}

function dropdownMassiveAction($device_type,$deleted=0){
	global $LANG,$CFG_GLPI;

	

	echo "<select name=\"massiveaction\" id='massiveaction'>";

	echo "<option value=\"-1\" selected>-----</option>";
	if ($device_type!=MAILGATE_TYPE){
		echo "<option value=\"update\">".$LANG["buttons"][14]."</option>";
	}

	if ($deleted){
		echo "<option value=\"purge\">".$LANG["buttons"][22]."</option>";
		echo "<option value=\"restore\">".$LANG["buttons"][21]."</option>";
	} else {
		echo "<option value=\"delete\">".$LANG["buttons"][6]."</option>";
		if ($device_type==PHONE_TYPE || $device_type==PRINTER_TYPE
			|| $device_type==PERIPHERAL_TYPE || $device_type==MONITOR_TYPE){
			echo "<option value=\"connect\">".$LANG["buttons"][9]."</option>";
			echo "<option value=\"disconnect\">".$LANG["buttons"][10]."</option>";
		}
		if ($device_type!=DOCUMENT_TYPE&&$device_type!=MAILGATE_TYPE){
			echo "<option value=\"add_document\">".$LANG["document"][16]."</option>";
		}
		if (in_array($device_type,$CFG_GLPI["state_types"])){
			echo "<option value=\"add_contract\">".$LANG["financial"][36]."</option>";
		}
		switch ($device_type){
			case COMPUTER_TYPE :
				echo "<option value=\"install\">".$LANG["buttons"][4]."</option>";
				break;
			case ENTERPRISE_TYPE :
				echo "<option value=\"add_contact\">".$LANG["financial"][24]."</option>";
				break;
			case CONTACT_TYPE :
				echo "<option value=\"add_enterprise\">".$LANG["financial"][25]."</option>";
				break;
			case USER_TYPE :
				echo "<option value=\"add_group\">".$LANG["setup"][604]."</option>";
				break;
		}

	}
	echo "</select>";

	echo "<script type='text/javascript' >\n";
	echo "   new Form.Element.Observer('massiveaction', 1, \n";
	echo "      function(element, value) {\n";
	echo "      	new Ajax.Updater('show_massiveaction','".$CFG_GLPI["root_doc"]."/ajax/dropdownMassiveAction.php',{asynchronous:true, evalScripts:true, \n";	echo "           onComplete:function(request)\n";
	echo "            {Element.hide('search_spinner_massiveaction');}, \n";
	echo "           onLoading:function(request)\n";
	echo "            {Element.show('search_spinner_massiveaction');},\n";
	echo "           method:'post', parameters:'deleted=$deleted&action='+value+'&type=$device_type'\n";
	echo "})})\n";
	echo "</script>\n";

	echo "<div id='search_spinner_massiveaction' style=' position:absolute;   filter:alpha(opacity=70); -moz-opacity:0.7; opacity: 0.7; display:none;'><img src=\"".$CFG_GLPI["root_doc"]."/pics/wait.png\" title='Processing....' alt='Processing....' /></div>\n";
	echo "<span id='show_massiveaction'>&nbsp;</span>\n";
}

function dropdownMassiveActionPorts(){
	global $LANG,$CFG_GLPI;

	echo "<select name=\"massiveaction\" id='massiveaction'>";

	echo "<option value=\"-1\" selected>-----</option>";
	echo "<option value=\"delete\">".$LANG["buttons"][6]."</option>";
	echo "<option value=\"assign_vlan\">".$LANG["networking"][55]."</option>";
	echo "<option value=\"unassign_vlan\">".$LANG["networking"][58]."</option>";
	echo "</select>";

	echo "<script type='text/javascript' >\n";
	echo "   new Form.Element.Observer('massiveaction', 1, \n";
	echo "      function(element, value) {\n";
	echo "      	new Ajax.Updater('show_massiveaction','".$CFG_GLPI["root_doc"]."/ajax/dropdownMassiveActionPorts.php',{asynchronous:true, evalScripts:true, \n";	echo "           onComplete:function(request)\n";
	echo "            {Element.hide('search_spinner_massiveaction');}, \n";
	echo "           onLoading:function(request)\n";
	echo "            {Element.show('search_spinner_massiveaction');},\n";
	echo "           method:'post', parameters:'action='+value\n";
	echo "})})\n";
	echo "</script>\n";

	echo "<div id='search_spinner_massiveaction' style=' position:absolute;   filter:alpha(opacity=70); -moz-opacity:0.7; opacity: 0.7; display:none;'><img src=\"".$CFG_GLPI["root_doc"]."/pics/wait.png\" title='Processing....' alt='Processing....' /></div>\n";
	echo "<span id='show_massiveaction'>&nbsp;</span>\n";
}

function globalManagementDropdown($target,$withtemplate,$ID,$value){
	global $LANG,$CFG_GLPI;	

	if ($value&&empty($withtemplate)) {
		echo $LANG["peripherals"][31];

		echo "&nbsp;<a title=\"".$LANG["common"][39]."\" href=\"javascript:confirmAction('".addslashes($LANG["common"][40])."\\n".addslashes($LANG["common"][39])."','$target?unglobalize=unglobalize&amp;ID=$ID')\">".$LANG["common"][38]."</a>&nbsp;";	

		echo "<img alt=\"".$LANG["common"][39]."\" title=\"".$LANG["common"][39]."\" src=\"".$CFG_GLPI["root_doc"]."/pics/aide.png\">";
	} else {
		echo "<select name='is_global'>";
		echo "<option value='0' ".(!$value?" selected":"").">".$LANG["peripherals"][32]."</option>";
		echo "<option value='1' ".($value?" selected":"").">".$LANG["peripherals"][31]."</option>";
		echo "</select>";
	}
}

function dropdownContractAlerting($myname,$value){
	global $LANG;
	echo "<select name='$myname'>";
	echo "<option value='0' ".($value==0?"selected":"")." >-------</option>";
	echo "<option value='".pow(2,ALERT_END)."' ".($value==pow(2,ALERT_END)?"selected":"")." >".$LANG["buttons"][32]."</option>";
	echo "<option value='".pow(2,ALERT_NOTICE)."' ".($value==pow(2,ALERT_NOTICE)?"selected":"")." >".$LANG["financial"][10]."</option>";
	echo "<option value='".(pow(2,ALERT_END)+pow(2,ALERT_NOTICE))."' ".($value==(pow(2,ALERT_END)+pow(2,ALERT_NOTICE))?"selected":"")." >".$LANG["buttons"][32]." + ".$LANG["financial"][10]."</option>";
	echo "</select>";

}


/**
 * Print a select with hours
 *
 * Print a select named $name with hours options and selected value $value
 *
 *@param $name string : HTML select name
 *@param $value integer : HTML select selected value
 *
 *@return Nothing (display)
 *
 **/
function dropdownHours($name,$value,$limit_planning=0){
	global $CFG_GLPI;

	$begin=0;
	$end=24;
	$step=$CFG_GLPI["time_step"];
	// Check if the $step is Ok for the $value field
	$split=split(":",$value);
	// Valid value XX:YY ou XX:YY:ZZ
	if (count($split)==2||count($split)==3){
		$min=$split[1];
		// Problem
		if (($min%$step)!=0){
			// set minimum step
			$step=5;
		}
	}

	if ($limit_planning){
		$plan_begin=split(":",$CFG_GLPI["planning_begin"]);
		$plan_end=split(":",$CFG_GLPI["planning_end"]);
		$begin=(int) $plan_begin[0];
		$end=(int) $plan_end[0];
	}
	echo "<select name=\"$name\">";
	for ($i=$begin;$i<$end;$i++){
		if ($i<10)
			$tmp="0".$i;
		else $tmp=$i;

		for ($j=0;$j<60;$j+=$step){
			if ($j<10) $val=$tmp.":0$j";
			else $val=$tmp.":$j";

			echo "<option value='$val' ".($value==$val.":00"||$value==$val?" selected ":"").">$val</option>";
		}
	}
	// Last item
	$val=$end.":00";
	echo "<option value='$val' ".($value==$val.":00"||$value==$val?" selected ":"").">$val</option>";
	echo "</select>";	
}	

function dropdownLicenseOfSoftware($myname,$sID) {
	global $DB,$LANG;

	$query="SELECT * from glpi_licenses WHERE sID='$sID' GROUP BY serial, expire, oem, oem_computer, buy ORDER BY serial,oem, oem_computer";
	$result=$DB->query($query);
	if ($DB->numrows($result)){
		echo "<select name='$myname'>";
		while ($data=$DB->fetch_array($result)){
			echo "<option value='".$data["ID"]."'>".$data["serial"];
			if ($data["expire"]!=NULL) echo " - ".$LANG["software"][25]." ".$data["expire"];
			else echo " - ".$LANG["software"][26];
			if ($data["buy"]) echo " - ".$LANG["software"][35];
			else echo " - ".$LANG["software"][37];
			if ($data["oem"]) echo " - ".$LANG["software"][28];
			echo "</option>";
		}
		echo "</select>";
	}

}


function dropdownInteger($myname,$value,$min=0,$max=100,$step=1){

	echo "<select name='$myname'>\n";
	for ($i=$min;$i<=$max;$i+=$step){
		echo "<option value='$i' ".($i==$value?" selected ":"").">$i</option>";
	}
	echo "</select>";

}

function dropdownLanguages($myname,$value){
	global $CFG_GLPI;
	echo "<select name='$myname'>";

	while (list($cle)=each($CFG_GLPI["languages"])){
		echo "<option value=\"".$cle."\"";
		if ($value==$cle) { echo " selected"; }
		echo ">".$CFG_GLPI["languages"][$cle][0]." ($cle)";
	}
	echo "</select>";
}

function dropdownActiveEntities($myname){
	global $DB,$CFG_GLPI;

	$rand=mt_rand();
	$query = "SELECT * FROM glpi_entities ".getEntitiesRestrictRequest("WHERE","glpi_entities","ID")." ORDER BY completename";
	$result = $DB->query($query);

	$link="central.php";
	if ($_SESSION["glpiactiveprofile"]["interface"]!="central"){
		$link="helpdesk.public.php";
	}
	
	echo "<form method='POST' action=\"".$CFG_GLPI['root_doc']."/front/$link\">";
	echo "<select onChange='submit()' id='active_entity' name=\"".$myname."\" size='1'>";
	
	/*	$outputval=getDropdownName("glpi_entities",$_SESSION['glpiactive_entity']);
		if (!empty($outputval)&&$outputval!="&nbsp;"){
			echo "<option class='tree' selected value='".$_SESSION['active_entity']."'>".$outputval."</option>";
		}
	*/
	// Manage Root entity
	if (in_array(0,$_SESSION['glpiactiveentities'])){
		echo "<option ".($_SESSION['glpiactive_entity']==0?" selected ":"")."value=\"0\" class='tree' >ROOT</option>";
	}

	if ($DB->numrows($result)) {
		while ($data =$DB->fetch_array($result)) {

			$ID = $data['ID'];
			$level = $data['level'];

			if (empty($data['name'])) $output="($ID)";
			else $output=$data['name'];

			$class=" class='tree' ";
			$raquo="&raquo;";
			if ($level==1){
				$class=" class='treeroot' ";
				$raquo="";
			}
			$style=$class;
			echo "<option ".($ID==$_SESSION['glpiactive_entity']?" selected ":"")."value=\"$ID\" $style title=\"".$data['completename']."\">".str_repeat("&nbsp;&nbsp;&nbsp;", $level).$raquo.substr($output,0,50)."</option>";
		}
	}
	echo "</select></form>";
}


function dropdownStatus($name,$value=0){
	global $LANG;

	echo "<select name='$name'>";
	echo "<option value='new' ".($value=="new"?" selected ":"").">".$LANG["joblist"][9]."</option>";
	echo "<option value='assign' ".($value=="assign"?" selected ":"").">".$LANG["joblist"][18]."</option>";
	echo "<option value='plan' ".($value=="plan"?" selected ":"").">".$LANG["joblist"][19]."</option>";
	echo "<option value='waiting' ".($value=="waiting"?" selected ":"").">".$LANG["joblist"][26]."</option>";
	echo "<option value='old_done' ".($value=="old_done"?" selected ":"").">".$LANG["joblist"][10]."</option>";
	echo "<option value='old_notdone' ".($value=="old_notdone"?" selected ":"").">".$LANG["joblist"][17]."</option>";
	echo "</select>";	
}

function getStatusName($value){
	global $LANG;

	switch ($value){
		case "new" :
			return $LANG["joblist"][9];
		break;
		case "assign" :
			return $LANG["joblist"][18];
		break;
		case "plan" :
			return $LANG["joblist"][19];
		break;
		case "waiting" :
			return $LANG["joblist"][26];
		break;
		case "old_done" :
			return $LANG["joblist"][10];
		break;
		case "old_notdone" :
			return $LANG["joblist"][17];
		break;
	}	
}

function dropdownPriority($name,$value=0,$complete=0){
	global $LANG;

	echo "<select name='$name'>";
	if ($complete){
		echo "<option value='0' ".($value==1?" selected ":"").">".$LANG["search"][7]."</option>";
		echo "<option value='-5' ".($value==-5?" selected ":"").">".$LANG["search"][16]." ".$LANG["help"][3]."</option>";
		echo "<option value='-4' ".($value==-4?" selected ":"").">".$LANG["search"][16]." ".$LANG["help"][4]."</option>";
		echo "<option value='-3' ".($value==-3?" selected ":"").">".$LANG["search"][16]." ".$LANG["help"][5]."</option>";
		echo "<option value='-2' ".($value==-2?" selected ":"").">".$LANG["search"][16]." ".$LANG["help"][6]."</option>";
		echo "<option value='-1' ".($value==-1?" selected ":"").">".$LANG["search"][16]." ".$LANG["help"][7]."</option>";
	}
	echo "<option value='5' ".($value==5?" selected ":"").">".$LANG["help"][3]."</option>";
	echo "<option value='4' ".($value==4?" selected ":"").">".$LANG["help"][4]."</option>";
	echo "<option value='3' ".($value==3?" selected ":"").">".$LANG["help"][5]."</option>";
	echo "<option value='2' ".($value==2?" selected ":"").">".$LANG["help"][6]."</option>";
	echo "<option value='1' ".($value==1?" selected ":"").">".$LANG["help"][7]."</option>";

	echo "</select>";	
}


function getPriorityName($value){
	global $LANG;

	switch ($value){
		case 5 :
			return $LANG["help"][3];
			break;
		case 4 :
			return $LANG["help"][4];
			break;
		case 3 :
			return $LANG["help"][5];
			break;
		case 2 :
			return $LANG["help"][6];
			break;
		case 1 :
			return $LANG["help"][7];
			break;
	}	
}

function getRequestTypeName($value){
	global $LANG;

	switch ($value){
		case 1 :
			return $LANG["Menu"][31];
			break;
		case 2 :
			return $LANG["setup"][14];
			break;
		case 3 :
			return $LANG["title"][41];
			break;
		case 4 :
			return $LANG["tracking"][34];
			break;
		case 5 :
			return $LANG["tracking"][35];
			break;
		case 6 :
			return $LANG["tracking"][36];
			break;
		default : return "";
	}	
}

function dropdownRequestType($name,$value=0){
	global $LANG;

	echo "<select name='$name'>";
	echo "<option value='0' ".($value==0?" selected ":"").">-----</option>";
	echo "<option value='1' ".($value==1?" selected ":"").">".$LANG["Menu"][31]."</option>"; // Helpdesk
	echo "<option value='2' ".($value==2?" selected ":"").">".$LANG["setup"][14]."</option>"; // mail
	echo "<option value='3' ".($value==3?" selected ":"").">".$LANG["title"][41]."</option>"; // phone
	echo "<option value='4' ".($value==4?" selected ":"").">".$LANG["tracking"][34]."</option>"; // direct
	echo "<option value='5' ".($value==5?" selected ":"").">".$LANG["tracking"][35]."</option>"; // writing
	echo "<option value='6' ".($value==6?" selected ":"").">".$LANG["tracking"][36]."</option>"; // other

	echo "</select>";	
}

function dropdownAmortType($name,$value=0){
	global $LANG;

	echo "<select name='$name'>";
	echo "<option value='0' ".($value==0?" selected ":"").">-------------</option>";
	echo "<option value='2' ".($value==2?" selected ":"").">".$LANG["financial"][47]."</option>";
	echo "<option value='1' ".($value==1?" selected ":"").">".$LANG["financial"][48]."</option>";
	echo "</select>";	
}
function getAmortTypeName($value){
	global $LANG;

	switch ($value){
		case 2 :
			return $LANG["financial"][47];
			break;
		case 1 :
			return $LANG["financial"][48];
			break;
		case 0 :
			return "";
			break;

	}
}	

function getPlanningState($value)
{
	global $LANG;
	
	switch ($value){
		case 0:
			return $LANG["planning"][16];
			break;
		case 1:
			return $LANG["planning"][17];
			break;
		case 2:
			return $LANG["planning"][18];
			break;
	}
	
}


function dropdownPlanningState($name,$value='')
{
	global $LANG;
	
	echo "<select name='$name' id='$name'>";

	echo "<option value='0'".($value==0?" selected ":"").">".$LANG["planning"][16]."</option>";
	echo "<option value='1'".($value==1?" selected ":"").">".$LANG["planning"][17]."</option>";
	echo "<option value='2'".($value==2?" selected ":"").">".$LANG["planning"][18]."</option>";

	echo "</select>";	
	
}
	
function dropdownArrayValues($name,$elements,$value='')
{
	echo "<select name='$name' id='$name'>";

	foreach($elements as $key => $val){
		echo "<option value='".$key."'".($value==$key?" selected ":"").">".$val."</option>";
	}

	echo "</select>";	
	
}

?>
