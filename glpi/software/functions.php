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


function titleSoftware(){

         GLOBAL  $lang,$HTMLRel;
         
         echo "<div align='center'><table border='0'><tr><td>";
         echo "<img src=\"".$HTMLRel."pics/logiciels.png\" alt='".$lang["software"][0]."' title='".$lang["software"][0]."'></td>\n";
         echo "<td><a class='icon_consol' href=\"software-add-select.php\"><strong>".$lang["software"][0]."</strong></a>\n";
                echo "</td>";
                echo "<td><a class='icon_consol'  href='".$HTMLRel."setup/setup-templates.php?type=".SOFTWARE_TYPE."'>".$lang["common"][8]."</a></td>";
                echo "</tr></table></div>";

}
function showSoftwareForm ($target,$ID,$search_software="",$withtemplate='') {
	// Show Software or blank form
	
	GLOBAL $cfg_glpi,$lang;
	
	
	$sw = new Software;

	$sw_spotted = false;

	if(empty($ID) && $withtemplate == 1) {
		if($sw->getEmpty()) $sw_spotted = true;
	} else {
		if($sw->getfromDB($ID)) $sw_spotted = true;
	}

	if($sw_spotted) {
		if(!empty($withtemplate) && $withtemplate == 2) {
			$template = "newcomp";
			$datestring = $lang["computers"][14].": ";
			$date = convDateTime(date("Y-m-d H:i:s"));
		} elseif(!empty($withtemplate) && $withtemplate == 1) { 
			$template = "newtemplate";
			$datestring = $lang["computers"][14].": ";
			$date = convDateTime(date("Y-m-d H:i:s"));
		} else {
			$datestring = $lang["common"][26]." : ";
			$date = convDateTime($sw->fields["date_mod"]);
			$template = false;
		}


	echo "<div align='center'><form method='post' action=\"$target\">";
		if(strcmp($template,"newtemplate") === 0) {
			echo "<input type=\"hidden\" name=\"is_template\" value=\"1\" />";
		}

	echo "<table class='tab_cadre_fixe'>";

		echo "<tr><th align='center' colspan='2' >";
		if(!$template) {
			echo $lang["software"][41].": ".$sw->fields["ID"];
		}elseif (strcmp($template,"newcomp") === 0) {
			echo $lang["software"][42].": ".$sw->fields["tplname"];
			echo "<input type='hidden' name='tplname' value='".$sw->fields["tplname"]."'>";
		}elseif (strcmp($template,"newtemplate") === 0) {
			echo $lang["common"][6]."&nbsp;: ";
			autocompletionTextField("tplname","glpi_software","tplname",$sw->fields["tplname"],20);
		}
		
		echo "</th><th colspan='2' align='center'>".$datestring.$date;
		if (!$template&&!empty($sw->fields['tplname']))
			echo "&nbsp;&nbsp;&nbsp;(".$lang["common"][13].": ".$sw->fields['tplname'].")";
		echo "</th></tr>";

	echo "<tr class='tab_bg_1'><td>".$lang["common"][16].":		</td>";
	echo "<td>";
	autocompletionTextField("name","glpi_software","name",$sw->fields["name"],25);
	echo "</td>";

	echo "<td>".$lang["software"][5].":		</td>";
	echo "<td>";
	autocompletionTextField("version","glpi_software","version",$sw->fields["version"],20);
	echo "</td></tr>";


	echo "<tr class='tab_bg_1'><td>".$lang["software"][3].": 	</td><td>";
		dropdownValue("glpi_dropdown_os", "platform", $sw->fields["platform"]);
	echo "</td>";
	
	echo "<td>".$lang["common"][5].": 	</td><td>";
		dropdownValue("glpi_enterprises","FK_glpi_enterprise",$sw->fields["FK_glpi_enterprise"]);
	echo "</td></tr>";
	
	echo "<tr class='tab_bg_1'><td>".$lang["common"][10].": 	</td><td>";
		dropdownUsersID("tech_num", $sw->fields["tech_num"]);
	echo "</td>";

	echo "<td>".$lang["common"][15].": 	</td><td colspan='2'>";
		dropdownValue("glpi_dropdown_locations", "location", $sw->fields["location"]);
	echo "</td></tr>";

	// UPDATE
	echo "<tr class='tab_bg_1'><td>".$lang["software"][29].":</td><td>";
	echo "<select name='is_update'><option value='Y' ".($ID&&$sw->fields['is_update']=='Y'?"selected":"").">".$lang["choice"][1]."</option><option value='N' ".(!$ID||$sw->fields['is_update']=='N'?"selected":"").">".$lang["choice"][0]."</option></select>";
	echo "&nbsp;".$lang["pager"][2]."&nbsp;";
	dropdownValue("glpi_software","update_software",$sw->fields["update_software"]);
	echo "</td>";

	if (!$template){
	echo "<td>".$lang["reservation"][24].":</td><td><b>";
	showReservationForm(SOFTWARE_TYPE,$ID);
	echo "</b></td></tr>";
	} else echo "<td colspan='2'>&nbsp;</td></tr>";
	
	

	echo "<tr class='tab_bg_1'><td valign='top'>";
	echo $lang["common"][25].":	</td>";
	echo "<td align='center' colspan='3'><textarea cols='50' rows='4' name='comments' >".$sw->fields["comments"]."</textarea>";
	echo "</td></tr>";
	
	echo "<tr>";

	if ($template) {

			if (empty($ID)||$withtemplate==2){
			echo "<td class='tab_bg_2' align='center' colspan='4'>\n";
			echo "<input type='hidden' name='ID' value=$ID>";
			echo "<input type='submit' name='add' value=\"".$lang["buttons"][8]."\" class='submit'>";
			echo "</td>\n";
			} else {
			echo "<td class='tab_bg_2' align='center' colspan='4'>\n";
			echo "<input type='hidden' name='ID' value=$ID>";
			echo "<input type='submit' name='update' value=\"".$lang["buttons"][7]."\" class='submit'>";
			echo "</td>\n";
			}


	} else {


                echo "<td class='tab_bg_2'>&nbsp;</td>";
                echo "<td class='tab_bg_2' valign='top'>";
		echo "<input type='hidden' name='ID' value=\"$ID\">\n";
		echo "<div align='center'><input type='submit' name='update' value=\"".$lang["buttons"][7]."\" class='submit'></div>";
		echo "</td>";
//		echo "</form>\n\n";
		//echo "<form action=\"$target\" method='post'>\n";
		echo "<td class='tab_bg_2' valign='top' colspan='2'>\n";
//		echo "<input type='hidden' name='ID' value=\"$ID\">\n";
		echo "<div align='center'>";
		if ($sw->fields["deleted"]=='N')
		echo "<input type='submit' name='delete' value=\"".$lang["buttons"][6]."\" class='submit'>";
		else {
		echo "<input type='submit' name='restore' value=\"".$lang["buttons"][21]."\" class='submit'>";
		
		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type='submit' name='purge' value=\"".$lang["buttons"][22]."\" class='submit'>";
		}
		echo "</div>";
		echo "</td>";
		
	}
		echo "</tr>";

		echo "</table></form></div>";

		return true;	
	}
	else {
                echo "<div align='center'><b>".$lang["software"][22]."</b></div>";

                return false;
        }

}

function showLicensesAdd($ID) {
	
	GLOBAL $cfg_glpi,$lang;
	
	echo "<div align='center'>&nbsp;<table class='tab_cadre_fixe' cellpadding='2'>";
	echo "<tr><td align='center' class='tab_bg_2'><strong>";
	echo "<a href=\"".$cfg_glpi["root_doc"]."/software/software-licenses.php?form=add&amp;sID=$ID\">";
	echo $lang["software"][12];
	echo "</a></strong></td></tr>";
	echo "</table></div><br>";
}

function showLicenses ($sID,$show_computers=0) {

	GLOBAL $db,$cfg_glpi, $HTMLRel, $lang;
	
	

	$query = "SELECT count(ID) AS COUNT  FROM glpi_licenses WHERE (sID = '$sID')";
	$query_update = "SELECT count(glpi_licenses.ID) AS COUNT  FROM glpi_licenses, glpi_software WHERE (glpi_software.ID = glpi_licenses.sID AND glpi_software.update_software = '$sID' and glpi_software.is_update='Y')";
	
	if ($result = $db->query($query)) {
		if ($db->result($result,0,0)!=0) { 
			$nb_licences=$db->result($result, 0, "COUNT");
			$result_update = $db->query($query_update);
			$nb_updates=$db->result($result_update, 0, "COUNT");;
			$installed = getInstalledLicence($sID);
			// As t'on utilis� trop de licences en prenant en compte les mises a jours (double install original + mise � jour)
			// Rien si free software
			$pb="";
			if (($nb_licences-$nb_updates-$installed)<0&&!isFreeSoftware($sID)&&!isGlobalSoftware($sID)) $pb="class='tab_bg_1_2'";
			
			echo "<form name='lic_form' method='get' action=\"".$cfg_glpi["root_doc"]."/software/software-licenses.php\">";

			echo "<br><div align='center'><table cellpadding='2' class='tab_cadre_fixe'>";
			echo "<tr><th colspan='5' $pb >";
			echo $nb_licences;
			echo "&nbsp;".$lang["software"][13]."&nbsp;-&nbsp;$nb_updates&nbsp;".$lang["software"][36]."&nbsp;-&nbsp;$installed&nbsp;".$lang["software"][19]."</th>";
			echo "<th colspan='1'>";
			echo " ".$lang["software"][19]." :</th></tr>";
			$i=0;
			echo "<tr><th>".$lang["common"][19]."</th><th>".$lang["software"][21]."</th><th>".$lang["software"][32]."</th><th>".$lang["software"][28]."</th><th>".$lang["software"][35]."</th>";
			echo "<th>";
			
			if ($show_computers){
				echo $lang["buttons"][14]."&nbsp;";
				echo "<select name='update_licenses' id='update_licenses_choice'>";
				echo "<option value=''>-----</option>";
				echo "<option value='update_expire'>".$lang["software"][32]."</option>";
				echo "<option value='update_buy'>".$lang["software"][35]."</option>";
				echo "</select>";
			
				echo "<script type='text/javascript' >\n";
				echo "   new Form.Element.Observer('update_licenses_choice', 1, \n";
				echo "      function(element, value) {\n";
				echo "      	new Ajax.Updater('update_licenses_view','".$cfg_glpi["root_doc"]."/ajax/updateLicenses.php',{asynchronous:true, evalScripts:true, \n";
				echo "           method:'post', parameters:'type=' + value\n";
				echo "})})\n";
				echo "</script>\n";

				echo "<span id='update_licenses_view'>\n";
				echo "&nbsp;";
				echo "</span>\n";	
			} else echo "&nbsp;";
			
			echo "</th></tr>";
				} else {

			echo "<br><div align='center'><table border='0' width='50%' cellpadding='2'>";
			echo "<tr><th>".$lang["software"][14]."</th></tr>";
			echo "</table></div>";
		}
	}

$query = "SELECT count(ID) AS COUNT , serial as SERIAL, expire as EXPIRE, oem as OEM, oem_computer as OEM_COMPUTER, buy as BUY  FROM glpi_licenses WHERE (sID = '$sID') GROUP BY serial, expire, oem, oem_computer, buy ORDER BY serial,oem, oem_computer";
//echo $query;
	if ($result = $db->query($query)) {			
	while ($data=$db->fetch_array($result)) {

		$serial=$data["SERIAL"];
		$num_tot=$data["COUNT"];
		$expire=$data["EXPIRE"];
		$oem=$data["OEM"];
		$oem_computer=$data["OEM_COMPUTER"];
		$buy=$data["BUY"];
		
		$SEARCH_LICENCE="(glpi_licenses.sID = $sID AND glpi_licenses.serial = '".$serial."'  AND glpi_licenses.oem = '$oem' AND glpi_licenses.oem_computer = '$oem_computer'  AND glpi_licenses.buy = '$buy' ";
		if ($expire=="")
		$SEARCH_LICENCE.=" AND glpi_licenses.expire IS NULL)";
		else $SEARCH_LICENCE.=" AND glpi_licenses.expire = '$expire')";
		
		$today=date("Y-m-d"); 
		$expirer=0;
		$expirecss="";
		if ($expire!=NULL&&$today>$expire) {$expirer=1; $expirecss="_2";}
		// Get installed licences


        $query_inst = "SELECT glpi_inst_software.ID AS ID, glpi_inst_software.license AS lID, glpi_computers.deleted as deleted, ";
        $query_inst .= " glpi_infocoms.ID as infocoms, glpi_licenses.comments AS COMMENT, ";
        $query_inst .= " glpi_computers.ID AS cID, glpi_computers.name AS cname FROM glpi_licenses";
                $query_inst .= " INNER JOIN glpi_inst_software ";
        $query_inst .= " ON ( glpi_inst_software.license = glpi_licenses.ID )";
        $query_inst .= " INNER JOIN glpi_computers ON (glpi_computers.deleted='N' AND glpi_computers.is_template='0' AND glpi_inst_software.cID= glpi_computers.ID) ";
        $query_inst .= " LEFT JOIN glpi_infocoms ON (glpi_infocoms.device_type='".LICENSE_TYPE."' AND glpi_infocoms.FK_device=glpi_licenses.ID) ";
        $query_inst .= " WHERE $SEARCH_LICENCE ";
        
		$result_inst = $db->query($query_inst);
		$num_inst=$db->numrows($result_inst);

		echo "<tr class='tab_bg_1' valign='top'>";
		echo "<td align='center'><strong>".$serial."</strong></td>";
		echo "<td align='center'><strong>";
		echo $num_tot;
		echo "</strong></td>";
		
		echo "<td align='center' class='tab_bg_1$expirecss'><strong>";
		if ($expire==NULL)
			echo $lang["software"][26];
		else {
			if ($expirer) echo $lang["software"][27];
			else echo $lang["software"][25]."&nbsp;".convDate($expire);
			}

		echo "</strong></td>";
		if ($serial!="free"&&$serial!="global"){
			// OEM
			if ($data["OEM"]=='Y') {
			$comp=new Computer();
			$comp->getFromDB($data["OEM_COMPUTER"]);
			}
			echo "<td align='center' class='tab_bg_1".($data["OEM"]=='Y'&&!isset($comp->fields['ID'])?"_2":"")."'>".($data["OEM"]=='Y'?$lang["choice"][1]:$lang["choice"][0]);
			if ($data["OEM"]=='Y') {
			echo "<br><strong>";
			if (isset($comp->fields['ID']))
			echo "<a href='".$cfg_glpi["root_doc"]."/computers/computers-info-form.php?ID=".$comp->fields['ID']."'>".$comp->fields['name']."</a>";
			else echo "N/A";
			echo "<strong>";
			} 
			echo "</td>";
		}
		else echo "<td>&nbsp;</td>";

		if ($serial!="free"){
			// BUY
			echo "<td align='center'>".($data["BUY"]=='Y'?$lang["choice"][1]:$lang["choice"][0]);
			echo "</td>";
		} else 
		echo "<td>&nbsp;</td>";
		
		echo "<td align='center'>";
		
		
		// Logiciels install�s :
		echo "<table width='100%'>";
	
		// Restant	

		echo "<tr><td align='center'>";
	
		if (!$show_computers){
		echo $lang["software"][19].": $num_inst&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
		}	
		
		$restant=$num_tot-$num_inst;
	 	  $query_new="SELECT glpi_licenses.ID as ID FROM glpi_licenses WHERE $SEARCH_LICENCE";		
			if ($result_new = $db->query($query_new)) {			
				$IDdup=$db->result($result_new,0,0);
			
				if ($serial!="free"&&$serial!="global") {
					echo $lang["software"][20].":";
					echo "<select name='stock_licenses_$IDdup'>";
					if (max(0,$restant-100)>0) echo "<option value='0'>0</option>";
					for ($i=max(0,$restant-100);$i<=$restant+100;$i++)
						echo "<option value='$i' ".($i==$restant?" selected ":"").">$i</option>";
					echo "</select>";
					echo "<input type='hidden' name='nb_licenses_$IDdup' value='$restant'>";
					echo "<input type='image' name='update_stock_licenses' value='$IDdup' src='".$HTMLRel."pics/actualiser.png' class='calendrier'>";
				}
				if ($serial=="free"||$serial=="global"){
					// Display infocoms
					echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<strong>";
					showDisplayInfocomLink(LICENSE_TYPE,$IDdup,1);
					echo "</strong>";
				}
			}


		if ($restant!=0||$serial=="free"||$serial=="global") {
			// Get first non installed license ID
			$query_first="SELECT glpi_licenses.ID as ID, glpi_inst_software.license as iID FROM glpi_licenses LEFT JOIN glpi_inst_software ON glpi_inst_software.license = glpi_licenses.ID WHERE $SEARCH_LICENCE";
			if ($result_first = $db->query($query_first)) {			
				if ($serial=="free"||$serial=="global")
				$ID=$db->result($result_first,0,"ID");
				else{
				$fin=0;
				while (!$fin&&$temp=$db->fetch_array($result_first))
					if ($temp["iID"]==NULL){
						$fin=1;
						$ID=$temp["ID"];
					}
				}
				if (!empty($ID)){
				echo "</td><td align='center'>";
				if ($serial=="free"||$serial=="global"){
					echo "<strong><a href=\"".$cfg_glpi["root_doc"]."/software/software-licenses.php?delete=delete&amp;ID=$ID\">";
					echo "<img src=\"".$HTMLRel."pics/delete.png\" alt='".$lang["buttons"][6]."' title='".$lang["buttons"][6]."'>";
					echo "</a></strong>";
				}
				echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<strong><a href=\"".$cfg_glpi["root_doc"]."/software/software-licenses.php?form=update&amp;lID=$ID&amp;sID=$sID\">";
				echo "<img src=\"".$HTMLRel."pics/edit.png\" alt='".$lang["buttons"][14]."' title='".$lang["buttons"][14]."'>";
				echo "</a></strong>";
				}
				
			}
		}
		// Dupliquer une licence
/* 	  $query_new="SELECT glpi_licenses.ID as ID FROM glpi_licenses WHERE $SEARCH_LICENCE";		
		if ($result_new = $db->query($query_new)) {			
			
			$IDdup=$db->result($result_new,0,0);
			if ($serial!="free"&&$serial!="global"){
				echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<strong><a href=\"".$cfg_glpi["root_doc"]."/software/software-licenses.php?duplicate=duplicate&amp;lID=$IDdup\">";
				echo "<img src=\"".$HTMLRel."pics/add.png\" alt='".$lang["buttons"][8]."' title='".$lang["buttons"][8]."'>";
				echo "</a></strong>";
			}
		}
*/

		// Add select all checkbox
		if ($show_computers){
		if ($num_inst>0&&$serial!="free"&&$serial!="global"){
			echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$lang["search"][7].":";
			$rand=mt_rand();

			echo "<input type='checkbox' onclick='toggle$rand();'>";
			echo "<script type='text/javascript' >\n";
			echo "function toggle$rand(){\n";
			while ($data_inst=$db->fetch_array($result_inst)){
				echo " var lic=document.getElementById('license_".$data_inst["lID"]."');";
				echo " if (lic.checked) \n";
				echo "      lic.checked = false;\n";
				echo " else lic.checked = true;\n";
			}
			echo "}</script>\n";
			$db->data_seek($result_inst,0);
		}
		}		
		
		echo "</td></tr>";
		
		
		// Logiciels install�s
		if ($show_computers)
		while ($data_inst=$db->fetch_array($result_inst)){
			echo "<tr class='tab_bg_1".(($data["OEM"]=='Y'&&$data["OEM_COMPUTER"]!=$data_inst["cID"])||$data_inst["deleted"]=='Y'?"_2":"")."'><td align='center'>";

			if ($serial!="free"&&$serial!="global") 
			echo "<input type='checkbox' name='license_".$data_inst["lID"]."' id='license_".$data_inst["lID"]."'>";

			echo "<strong><a href=\"".$cfg_glpi["root_doc"]."/computers/computers-info-form.php?ID=".$data_inst["cID"]."\">";
			echo $data_inst["cname"];
			echo "</a></strong></td><td align='center'>";
			
			// Comment
			if (!empty($data_inst["COMMENT"])) {
			echo "<img onmouseout=\"cleanhide('comment_".$data_inst["ID"]."')\" onmouseover=\"cleandisplay('comment_".$data_inst["ID"]."')\" src=\"".$HTMLRel."pics/aide.png\" alt=''>";
			echo "<div class='over_link' id='comment_".$data_inst["ID"]."'>".nl2br($data_inst["COMMENT"])."</div>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
			}
			// delete
			echo "<a href=\"".$cfg_glpi["root_doc"]."/software/software-licenses.php?uninstall=uninstall&amp;ID=".$data_inst["ID"]."&amp;cID=".$data_inst["cID"]."\">";
			echo "<img src=\"".$HTMLRel."pics/remove.png\" alt='".$lang["buttons"][5]."' title='".$lang["buttons"][5]."'>";
			echo "</a>";

			if ($serial!="free"&&$serial!="global"){
				echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<strong><a href=\"".$cfg_glpi["root_doc"]."/software/software-licenses.php?form=update&amp;lID=".$data_inst["lID"]."&amp;sID=$sID\">";
				echo "<img src=\"".$HTMLRel."pics/edit.png\" alt='".$lang["buttons"][14]."' title='".$lang["buttons"][14]."'>";
				echo "</a></strong>";
				// Display infocoms
				echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<strong>";
				showDisplayInfocomLink(LICENSE_TYPE,$data_inst["lID"],1);
				echo "</strong>";
				}
			
			echo "</td></tr>";
		}
			
		
		
		echo "</table></td>";
		
		echo "</tr>";
				
	}
	}	
echo "</table></div>\n\n";
echo "</form>";
}


function showLicenseForm($target,$action,$sID,$lID="") {

	GLOBAL $cfg_glpi, $lang,$HTMLRel;

	$show_infocom=false;
	
	switch ($action){
	case "add" :
		$title= $lang["software"][15]." ($sID):";
		$button= $lang["buttons"][8];
		$ic=new Infocom();
		
		if ($ic->getFromDBforDevice(SOFTWARE_TYPE,$sID))
			$show_infocom=true;
		
		break;
	case "update" :
		$title = $lang["software"][34]." ($lID):";
		$button= $lang["buttons"][14];
		break;
	}
	
	// Get previous values or defaults values
	$values=array();
	// defaults values :
	$values['serial']='';
	//$values['number']='1';
	$values['expire']="0000-00-00";
	$values['oem']='N';
	$values["oem_computer"]='';
	$values["comments"]='';
//	$values['is_update']='N';
//	$values["update_software"]='';
	$values['buy']='Y';
	
	
	if (isset($_POST)&&!empty($_POST)){ // Get from post form
	foreach ($values as $key => $val)
		if (isset($_POST[$key]))
		$values[$key]=$_POST[$key];
	
	}
	else if (!empty($lID)){ // Get from DB
	$lic=new License();
	$lic->getfromDB($lID);
	$values=$lic->fields;
	} 
	
	if (empty($values['expire'])) $values['expire']="0000-00-00";
	
	
	
	echo "<div align='center'><strong>";
	echo "<a href=\"".$cfg_glpi["root_doc"]."/software/software-info-form.php?ID=$sID\">";
	echo $lang["buttons"][13]."</strong>";
	echo "</a><br>";
	
	echo "<form name='form' method='post' action=\"$target\">";
	
	echo "<table class='tab_cadre'><tr><th colspan='3'>$title</th></tr>";
	

	echo "<tr class='tab_bg_1'><td>".$lang["software"][16]."</td>";
	echo "<td>";
	autocompletionTextField("serial","glpi_licenses","serial",$values["serial"],20);
	echo "</td></tr>";
	
	if ($action!="update"){
	echo "<tr class='tab_bg_1'><td>";
	echo $lang["printers"][26].":</td><td><select name=number>";
	echo "<option value='1' selected>1</option>";
	for ($i=2;$i<=1000;$i++)
		echo "<option value='$i'>$i</option>";
	echo "</select></td></tr>";
	}

	if ($show_infocom){
		echo "<tr class='tab_bg_1'><td>".$lang["financial"][3].":</td><td>";
		showDisplayInfocomLink(SOFTWARE_TYPE,$sID);
		echo "</td></tr>"; 
	}
		
	echo "<tr class='tab_bg_1'><td>".$lang["search"][9].":</td><td>";
	showCalendarForm("form","expire",$values['expire']);
	echo "</td></tr>"; 
	
	// OEM
	echo "<tr class='tab_bg_1'><td>".$lang["software"][28]."</td><td>";
	echo "<select name='oem'><option value='Y' ".($values['oem']=='Y'?"selected":"").">".$lang["choice"][1]."</option><option value='N' ".($values['oem']=='N'?"selected":"").">".$lang["choice"][0]."</option></select>";
	dropdownValue("glpi_computers","oem_computer",$values["oem_computer"]);
	
	echo "</td></tr>";
	// BUY
	echo "<tr class='tab_bg_1'><td>".$lang["software"][35]."</td><td>";
	echo "<select name='buy'><option value='Y' ".($values['buy']=='Y'?"selected":"").">".$lang["choice"][1]."</option><option value='N' ".($values['buy']=='N'?"selected":"").">".$lang["choice"][0]."</option></select>";
	echo "</td></tr>";

	echo "<tr class='tab_bg_1'><td>".$lang["common"][25]."</td><td>";
	echo "<textarea name='comments' rows='6' cols='40'>".$values['comments']."</textarea>";
	echo "</td></tr>";
	
	echo "<tr class='tab_bg_2'>";
	echo "<td align='center' colspan='3'>";
	echo "<input type='hidden' name='sID' value=".$sID.">";
	echo "<input type='hidden' name='lID' value=".$lID.">";
	echo "<input type='hidden' name='form' value=".$action.">";
	echo "<input type='submit' name='$action' value=\"".$button."\" class='submit'>";
	echo "</td>";

	echo "</table></form></div>";
}


function addLicense($input) {
	$lic = new License;
	
	// dump status
	unset($input["lID"]);
	unset($input["form"]);
	unset($input["add"]);
	unset($input["withtemplate"]);
	
	if (empty($input['expire'])||$input['expire']=="0000-00-00") unset($input['expire']);
	if ($input['oem']=='N') $input['oem_computer']=-1;

	// fill array for update
	foreach ($input as $key => $val) {
		if ($key[0]!='_'&&(empty($lic->fields[$key]) || $sw->fields[$key] != $input[$key])) {
			$lic->fields[$key] = $input[$key];
		}
	}
	$newID=$lic->addToDB();
	
	if ($input['oem']=='Y')
		installSoftware($input['oem_computer'],$newID);

	// Add infocoms if exists for the licence
	$ic=new Infocom();
	if ($ic->getFromDBforDevice(SOFTWARE_TYPE,$lic->fields["sID"])){
		unset($ic->fields["ID"]);
		$ic->fields["FK_device"]=$newID;
		$ic->fields["device_type"]=LICENSE_TYPE;
		$ic->addToDB();
	}
	
	return $newID;
}

function updateLicense($input) {
	// Update License in the database

	$lic = new License;
	$lic->getFromDB($input["lID"]);

	if (empty($input['expire'])) unset($input['expire']);
	if (!isset($input['expire'])||$input['expire']=="0000-00-00") $input['expire']="NULL";
	if (isset($input['oem'])&&$input['oem']=='N') $input['oem_computer']=-1;

	
	// Fill the update-array with changes
	$x=0;
	foreach ($input as $key => $val) {
		if (array_key_exists($key,$lic->fields) && $lic->fields[$key] != $input[$key]) {
			$lic->fields[$key] = $input[$key];
			$updates[$x] = $key;
			$x++;
		}
	}

	if(!empty($updates)) {
	
		$lic->updateInDB($updates);
	}
}

function deleteLicense($ID) {
	// Delete License
	
	$lic = new License;
	$lic->deleteFromDB($ID);
	
} 

function updateNumberLicenses($likeID,$number,$new_number){
	global $db;

	$lic=new License();

// Delete unused licenses
if ($number>$new_number){
	if ($lic->getFromDB($likeID)){
		$SEARCH_LICENCE="(glpi_licenses.sID = ".$lic->fields["sID"]." AND glpi_licenses.serial = '".$lic->fields["serial"]."'  AND glpi_licenses.oem = '".$lic->fields["oem"]."' AND glpi_licenses.oem_computer = '".$lic->fields["oem_computer"]."'  AND glpi_licenses.buy = '".$lic->fields["buy"]."' ";
		if ($lic->fields["expire"]=="")
		$SEARCH_LICENCE.=" AND glpi_licenses.expire IS NULL)";
		else $SEARCH_LICENCE.=" AND glpi_licenses.expire = '".$lic->fields["expire"]."')";
		
		

		for ($i=0;$i<$number-$new_number;$i++){
			$query_first="SELECT glpi_licenses.ID as ID, glpi_inst_software.license as iID FROM glpi_licenses LEFT JOIN glpi_inst_software ON glpi_inst_software.license = glpi_licenses.ID WHERE $SEARCH_LICENCE";
			if ($result_first = $db->query($query_first)) {			
				if ($lic->fields["serial"]=="free"||$lic->fields["serial"]=="global")
				$ID=$db->result($result_first,0,"ID");
				else{
				$fin=0;
				while (!$fin&&$temp=$db->fetch_array($result_first))
					if ($temp["iID"]==NULL){
						$fin=1;
						$ID=$temp["ID"];
					}
				}
				if (!empty($ID)){
					deleteLicense($ID);
				}
			}
			
		}
	}
// Create new licenses
} else if ($number<$new_number){ 
	$lic->getFromDB($likeID);
	unset($lic->fields["ID"]);

	if (is_null($lic->fields["expire"]))
		unset($lic->fields["expire"]);

	for ($i=0;$i<$new_number-$number;$i++)
		$lic->addToDB();
	

}
	
}


function installSoftware($cID,$lID,$sID='') {

	global $db;
	
	
	if (!empty($lID)&&$lID>0){
		$query = "INSERT INTO glpi_inst_software VALUES (NULL,$cID,$lID)";
		if ($result = $db->query($query)) {
			return $db->insert_id();
		} else {
			return false;
		}
	} else if ($lID<0&&!empty($sID)){ // Auto Add a license
	$lic=new License();
	$lic->fields['buy']='N';
	$lic->fields['sID']=$sID;
	$lic->fields['serial']='Automatic Add';
	$lID=$lic->addToDB();
		
		$query = "INSERT INTO glpi_inst_software VALUES (NULL,$cID,$lID)";
		if ($result = $db->query($query)) {
			return $db->insert_id();
		} else {
			return false;
		}
	
	}
}

function uninstallSoftware($ID) {

	global $db;
	$query = "DELETE FROM glpi_inst_software WHERE(ID = '$ID')";
//	echo $query;
	if ($result = $db->query($query)) {
		return true;
	} else {
		return false;
	}
}

function showSoftwareInstalled($instID,$withtemplate='') {

	GLOBAL $db,$cfg_glpi, $lang;
        
	$query = "SELECT glpi_inst_software.license as license, glpi_inst_software.ID as ID FROM glpi_inst_software, glpi_software,glpi_licenses ";
	$query.= "WHERE glpi_inst_software.license = glpi_licenses.ID AND glpi_licenses.sID = glpi_software.ID AND (glpi_inst_software.cID = '$instID') order by glpi_software.name";
	
	$result = $db->query($query);
	$number = $db->numrows($result);
	$i = 0;
		
	echo "<br><br><div align='center'><table class='tab_cadre_fixe'>";
	echo "<tr><th colspan='5'>".$lang["software"][17].":</th></tr>";
			echo "<tr><th>".$lang["common"][16]."</th><th>".$lang["software"][32]."</th><th>".$lang["software"][28]."</th><th>".$lang["software"][35]."</th><th>&nbsp;</th></tr>";
	
	while ($i < $number) {
		$lID = $db->result($result, $i, "license");
		$ID = $db->result($result, $i, "ID");
		$query2 = "SELECT * FROM glpi_licenses WHERE (ID = '$lID')";
		$result2 = $db->query($query2);
		$data=$db->fetch_array($result2);
		$today=date("Y-m-d"); 
		$expirer=0;
		$expirecss="";
		if ($data['expire']!=NULL&&$today>$data['expire']) {$expirer=1; $expirecss="_2";}

		$sw = new Software;
		$sw->getFromDB($data['sID']);
		
		if ($sw->fields['deleted']=="Y") {$expirer=1; $expirecss="_2";}

		echo "<tr class='tab_bg_1$expirecss'>";
	
		echo "<td align='center'><strong><a href=\"".$cfg_glpi["root_doc"]."/software/software-info-form.php?ID=".$data['sID']."\">";
		echo $sw->fields["name"]." (v. ".$sw->fields["version"].")</a>";
		echo "</strong>";
		echo " - ".$data['serial']."</td>";
		echo "<td align='center'><strong>";
		if ($data['expire']==NULL)
		echo $lang["software"][26];
		else {
			if ($expirer) echo $lang["software"][27];
			else echo $lang["software"][25]."&nbsp;".$data['expire'];
		}

						echo "</strong></td>";
		if ($data['serial']!="free"&&$data['serial']!="global"){
			// OEM
			if ($data["oem"]=='Y') {
			$comp=new Computer();
			$comp->getFromDB($data["oem_computer"]);
			}
			echo "<td align='center' class='tab_bg_1".($expirer||($data["oem"]=='Y'&&$comp->fields['ID']!=$instID)?"_2":"")."'>".($data["oem"]=='Y'?$lang["choice"][1]:$lang["choice"][0]);
			if ($data["oem"]=='Y') {
			echo "<br><strong>";
			if (isset($comp->fields['ID']))
			echo "<a href='".$cfg_glpi["root_doc"]."/computers/computers-info-form.php?ID=".$comp->fields['ID']."'>".$comp->fields['name']."</a>";
			else echo "N/A";
			echo "<strong>";
			} 
			echo "</td>";
		
			// BUY
			echo "<td align='center'>".($data["buy"]=='Y'?$lang["choice"][1]:$lang["choice"][0]);
			echo "</td>";								
		}
		else echo "<td>&nbsp;</td><td>&nbsp;</td>";
		echo "<td align='center' class='tab_bg_2'>";
		if(!empty($withtemplate) && $withtemplate == 2) {
			//do nothing
			echo "&nbsp;";
		} else {
			echo "<a href=\"".$cfg_glpi["root_doc"]."/software/software-licenses.php?uninstall=uninstall&amp;ID=$ID&amp;cID=$instID\">";
			echo "<strong>".$lang["buttons"][5]."</strong></a>";
		}
		echo "</td></tr>";

		$i++;		
	}
	$q="SELECT * FROM glpi_software WHERE deleted='N' AND is_template='0'";
	$result = $db->query($q);
	$nb = $db->numrows($result);
	
	if((!empty($withtemplate) && $withtemplate == 2) || $nb==0) {
		echo "</table></div>";
	} else {
		echo "<tr class='tab_bg_1'><td align='center' colspan='5'>";
		echo "<form method='post' action=\"".$cfg_glpi["root_doc"]."/software/software-licenses.php\">";

		echo "<div class='software-instal'>";
		echo "<input type='hidden' name='cID' value='$instID'>";
		//echo "<input type='hidden' name='withtemplate' value='".$withtemplate."'>";
			dropdownSoftwareToInstall("licenseID",$withtemplate);
		echo "<input type='submit' name='install' value=\"".$lang["buttons"][4]."\" class='submit'>";
		echo "</div>";
        	echo "</form>";
		echo "</td></tr>";
		echo "</table></div>";
	}
	
		

}

function countInstallations($sID,$nohtml=0) {
	
	GLOBAL $db,$cfg_glpi, $lang;
	
	
	// Get total
	$total = getLicenceNumber($sID);
	$out="";
	if ($total!=0) {

		if (isFreeSoftware($sID)) {
			// Get installed
			$installed = getInstalledLicence($sID);
			if (!$nohtml)
				$out.= "<i>".$lang["software"][39]."</i>&nbsp;&nbsp;".$lang["software"][19].": <strong>$installed</strong>";
			else $out.= $lang["software"][39]."  ".$lang["software"][19].": $installed";
		} else if (isGlobalSoftware($sID)){
			$installed = getInstalledLicence($sID);
			if (!$nohtml)
				$out.= "<i>".$lang["software"][38]."</i>&nbsp;&nbsp;".$lang["software"][19].": <strong>$installed</strong>";
			else $out.= $lang["software"][38]."  ".$lang["software"][19].": $installed";
			
		}
		else {
	
			// Get installed
			$i=0;
			$installed = getInstalledLicence($sID);
		
			// Get remaining
			$remaining = $total - $installed;

			// Output
			if (!$nohtml){
				$out.= "<table cellpadding='2' cellspacing='0'><tr>";
				$out.= "<td width='35%'>".$lang["software"][19].": <strong>$installed</strong></td>";
			} else $out.= "  ".$lang["software"][19].": $installed";
			
			$color="red";
			
			if ($remaining == 0) {
				$color="green";
			} else {
				$color="blue";
			}			
			
			if (!$nohtml){
				$remaining = "<span class='$color'>$remaining";
				$remaining .= "</span>";
				$out.= "<td width='20%'>".$lang["software"][20].": <strong>$remaining</strong></td>";
				$out.= "<td width='20%'>".$lang["software"][21].": <strong>".$total."</strong></td>";
			} else {
				$out.= "  ".$lang["software"][20].": $remaining";
				$out.= "  ".$lang["software"][21].": ".$total;
			}

			$tobuy=getLicenceToBuy($sID);
			if ($tobuy>0){
				if (!$nohtml)
					$out.= "<td width='25%'>".$lang["software"][37].": <strong><span class='red'>".$tobuy."</span></strong></td>";
				else $out.= "  ".$lang["software"][37].": ".$tobuy;
			} else {
				if (!$nohtml)
					$out.= "<td width='20%'>&nbsp;</td>";
			}
			if (!$nohtml)
				$out.= "</tr></table>";
		} 
	} else {
		if (!$nohtml)
			$out.= "<div align='center'><i>".$lang["software"][40]."</i></div>";
		else $out.= $lang["software"][40];
	}
return $out;
}	

function getInstalledLicence($sID){
	global $db;
	$query = "SELECT count(*) FROM glpi_licenses INNER JOIN glpi_inst_software ON (glpi_licenses.sID = '$sID' AND glpi_licenses.ID = glpi_inst_software.license ) 
						INNER JOIN glpi_computers ON ( glpi_inst_software.cID=glpi_computers.ID AND glpi_computers.deleted='N' AND glpi_computers.is_template='0' )";
	
	$result = $db->query($query);
	
	if ($db->numrows($result)!=0){
		return $db->result($result,0,0);
	} else return 0;
	
}

function getLicenceToBuy($sID){
	global $db;
	$query = "SELECT ID FROM glpi_licenses WHERE (sID = '$sID' AND buy ='N')";
	$result = $db->query($query);
	return $db->numrows($result);
}

function getLicenceNumber($sID){
	global $db;
	$query = "SELECT ID,serial FROM glpi_licenses WHERE (sID = '$sID')";
	$result = $db->query($query);
	return $db->numrows($result);
}

function isGlobalSoftware($sID){
	global $db;
	$query = "SELECT ID,serial FROM glpi_licenses WHERE (sID = '$sID' and serial='global')";
	$result = $db->query($query);
	
	return ($db->numrows($result)>0);
}

function isFreeSoftware($sID){
	global $db;
	$query = "SELECT ID,serial FROM glpi_licenses WHERE (sID = '$sID'  and serial='free')";
	$result = $db->query($query);
	return ($db->numrows($result)>0);
}

function getInstallionsForLicense($ID){
	global $db;
	$query = "SELECT count(*) FROM glpi_inst_software INNER JOIN glpi_computers ON ( glpi_inst_software.cID=glpi_computers.ID ) WHERE glpi_inst_software.license ='$ID' AND glpi_computers.deleted='N' AND glpi_computers.is_template='0' ";
	
	$result = $db->query($query);
	
	if ($db->numrows($result)!=0){
		return $db->result($result,0,0);
	} else return 0;
	
}


?>
