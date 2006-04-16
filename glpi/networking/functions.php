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
///// Manage Netdevices /////

function titleNetdevices() {
         // titre
         
	global  $lang,$HTMLRel;

	echo "<div align='center'><table border='0'><tr><td>";
	echo "<img src=\"".$HTMLRel."pics/networking.png\" alt='".$lang["networking"][11]."' title='".$lang["networking"][11]."'></td>";
	if (haveRight("networking","w")){
		echo "<td><a  class='icon_consol' href=\"".$HTMLRel."setup/setup-templates.php?type=".NETWORKING_TYPE."&amp;add=1\"><b>".$lang["networking"][11]."</b></a>";
		echo "</td>";
		echo "<td><a class='icon_consol' href='".$HTMLRel."setup/setup-templates.php?type=".NETWORKING_TYPE."&amp;add=0'>".$lang["common"][8]."</a></td>";
	} else echo "<td><span class='icon_sous_nav'><b>".$lang["Menu"][1]."</b></span></td>";
	echo "</tr></table></div>";
 
}



function showNetworkingForm ($target,$ID,$withtemplate='') {
	// Show device or blank form
	
	global $cfg_glpi, $lang,$HTMLRel;

	if (!haveRight("networking","r")) return false;

	$netdev = new Netdevice;

	$netdev_spotted = false;

	if(empty($ID) && $withtemplate == 1) {
		if($netdev->getEmpty()) $netdev_spotted = true;
	} else {
		if($netdev->getfromDB($ID)) $netdev_spotted = true;
	}

	if($netdev_spotted) {
		if(!empty($withtemplate) && $withtemplate == 2) {
			$template = "newcomp";
			$datestring = $lang["computers"][14].": ";
			$date = convDateTime(date("Y-m-d H:i:s"));
		} elseif(!empty($withtemplate) && $withtemplate == 1) { 
			$template = "newtemplate";
			$datestring = $lang["computers"][14].": ";
			$date = convDateTime(date("Y-m-d H:i:s"));
		} else {
			$datestring = $lang["common"][26].": ";
			$date = convDateTime($netdev->fields["date_mod"]);
			$template = false;
		}


	echo "<div align='center'><form name='form' method='post' action=\"$target\">\n";

		if(strcmp($template,"newtemplate") === 0) {
			echo "<input type=\"hidden\" name=\"is_template\" value=\"1\" />\n";
		}

	echo "<table  class='tab_cadre_fixe' cellpadding='2'>\n";

		echo "<tr><th align='center' >\n";
		if(!$template) {
			echo $lang["networking"][54].": ".$netdev->fields["ID"];
		}elseif (strcmp($template,"newcomp") === 0) {
			echo $lang["networking"][53].": ".$netdev->fields["tplname"];
			echo "<input type='hidden' name='tplname' value='".$netdev->fields["tplname"]."'>";
		}elseif (strcmp($template,"newtemplate") === 0) {
			echo $lang["common"][6].": ";
			autocompletionTextField("tplname","glpi_networking","tplname",$netdev->fields["tplname"],20);	
		}
		echo "</th><th  align='center'>".$datestring.$date;
		if (!$template&&!empty($netdev->fields['tplname']))
			echo "&nbsp;&nbsp;&nbsp;(".$lang["common"][13].": ".$netdev->fields['tplname'].")";
		echo "</th></tr>\n";

	
	echo "<tr><td class='tab_bg_1' valign='top'>\n";

	echo "<table cellpadding='1' cellspacing='0' border='0'>\n";

	echo "<tr><td>".$lang["common"][16].":	</td>\n";
	echo "<td>";
	autocompletionTextField("name","glpi_networking","name",$netdev->fields["name"],20);	
	echo "</td></tr>\n";

	echo "<tr><td>".$lang["common"][15].": 	</td><td>\n";
		dropdownValue("glpi_dropdown_locations", "location", $netdev->fields["location"]);
	echo "</td></tr>\n";
	
	echo "<tr class='tab_bg_1'><td>".$lang["common"][10].": 	</td><td colspan='2'>\n";
		dropdownUsersID("tech_num", $netdev->fields["tech_num"]);
	echo "</td></tr>\n";
		
	echo "<tr><td>".$lang["common"][21].":	</td><td>\n";
		autocompletionTextField("contact_num","glpi_networking","contact_num",$netdev->fields["contact_num"],20);	
	echo "</td></tr>\n";

	echo "<tr><td>".$lang["common"][18].":	</td><td>\n";
		autocompletionTextField("contact","glpi_networking","contact",$netdev->fields["contact"],20);	
	echo "</td></tr>\n";
	
	if (!$template){
	echo "<tr><td>".$lang["reservation"][24].":</td><td><b>";
	showReservationForm(NETWORKING_TYPE,$ID);
	echo "</b></td></tr>";
	}

		
		echo "<tr><td>".$lang["state"][0].":</td><td>\n";
		$si=new StateItem();
		$t=0;
		if ($template) $t=1;
		$si->getfromDB(NETWORKING_TYPE,$netdev->fields["ID"],$t);
		dropdownValue("glpi_dropdown_state", "state",$si->fields["state"]);
		echo "</td></tr>\n";
		
	echo "<tr><td>".$lang["setup"][88].": 	</td><td>\n";
		dropdownValue("glpi_dropdown_network", "network", $netdev->fields["network"]);
	echo "</td></tr>\n";

	echo "<tr><td>".$lang["setup"][89].": 	</td><td>\n";
		dropdownValue("glpi_dropdown_domain", "domain", $netdev->fields["domain"]);
	echo "</td></tr>\n";

	echo "</table>\n";

	echo "</td>\n";	
	echo "<td class='tab_bg_1' valign='top'>\n";

	echo "<table cellpadding='1' cellspacing='0' border='0'>\n";

	echo "<tr><td>".$lang["common"][17].": 	</td><td>\n";
		dropdownValue("glpi_type_networking", "type", $netdev->fields["type"]);
	echo "</td></tr>\n";

	echo "<tr><td>".$lang["common"][22].": 	</td><td>";
		dropdownValue("glpi_dropdown_model_networking", "model", $netdev->fields["model"]);
	echo "</td></tr>";
	
	echo "<tr class='tab_bg_1'><td>".$lang["common"][5].": 	</td><td colspan='2'>\n";
		dropdownValue("glpi_enterprises","FK_glpi_enterprise",$netdev->fields["FK_glpi_enterprise"]);
	echo "</td></tr>\n";
	
	echo "<tr><td>".$lang["networking"][49].": 	</td><td>\n";
	dropdownValue("glpi_dropdown_firmware", "firmware", $netdev->fields["firmware"]);
	echo "</td></tr>\n";
		
	echo "<tr><td>".$lang["networking"][5].":	</td><td>\n";
	autocompletionTextField("ram","glpi_networking","ram",$netdev->fields["ram"],20);	
	echo "</td></tr>\n";

	echo "<tr><td>".$lang["common"][19].":	</td><td>\n";
	autocompletionTextField("serial","glpi_networking","serial",$netdev->fields["serial"],20);	
	echo "</td></tr>\n";

	echo "<tr><td>".$lang["common"][20].":</td><td>\n";
	autocompletionTextField("otherserial","glpi_networking","otherserial",$netdev->fields["otherserial"],20);	
	echo "</td></tr>\n";
	
	echo "<tr><td>".$lang["networking"][14].":</td><td>\n";
	autocompletionTextField("ifaddr","glpi_networking","ifaddr",$netdev->fields["ifaddr"],20);	
	echo "</td></tr>\n";

	echo "<tr><td>".$lang["networking"][15].":</td><td>\n";
	autocompletionTextField("ifmac","glpi_networking","ifmac",$netdev->fields["ifmac"],20);	
	echo "</td></tr>\n";
		
	echo "</table>\n";
	
	echo "</td>\n";	
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td class='tab_bg_1' valign='top' colspan='2'>\n";

	echo "<table width='100%' cellpadding='0' cellspacing='0' border='0'><tr><td valign='top'>\n";
	echo $lang["common"][25].":	</td>\n";
	echo "<td align='center'><textarea cols='35' rows='4' name='comments' >".$netdev->fields["comments"]."</textarea>\n";
	echo "</td></tr></table>\n";

	echo "</td>";
	echo "</tr>\n";


	if (haveRight("networking","w")) {
		echo "<tr>\n";
	
		if ($template) {

			if (empty($ID)||$withtemplate==2){
			echo "<td class='tab_bg_2' align='center' colspan='2'>\n";
			echo "<input type='hidden' name='ID' value=$ID>";
			echo "<input type='submit' name='add' value=\"".$lang["buttons"][8]."\" class='submit'>";
			echo "</td>\n";
			} else {
			echo "<td class='tab_bg_2' align='center' colspan='2'>\n";
			echo "<input type='hidden' name='ID' value=$ID>";
			echo "<input type='submit' name='update' value=\"".$lang["buttons"][7]."\" class='submit'>";
			echo "</td>\n";
			}

		} else {

			echo "<td class='tab_bg_2' valign='top'>";
			echo "<input type='hidden' name='ID' value=\"$ID\">\n";
			echo "<div align='center'><input type='submit' name='update' value=\"".$lang["buttons"][7]."\" class='submit'></div>";
			echo "<td class='tab_bg_2' valign='top'>\n";

			echo "<div align='center'>\n";
			if ($netdev->fields["deleted"]=='N')
				echo "<input type='submit' name='delete' value=\"".$lang["buttons"][6]."\" class='submit'>\n";
			else {
				echo "<input type='submit' name='restore' value=\"".$lang["buttons"][21]."\" class='submit'>\n";
		
				echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type='submit' name='purge' value=\"".$lang["buttons"][22]."\" class='submit'>\n";
			}
			echo "</div>\n";
			echo "</td>\n";
		}
		echo "</tr>\n";
	}
	
	echo "</table></form></div>\n";

	return true;
		}
	else {
                echo "<div align='center'><b>".$lang["networking"][38]."</b></div>";
                return false;
        }

}

///// Manage Ports on Devices /////

function showPorts ($device,$device_type,$withtemplate='') {
	
	global $db,$cfg_glpi, $lang,$HTMLRel,$LINK_ID_TABLE;

	if (!haveRight("networking","r")) return false;
		
	$device_real_table_name = $LINK_ID_TABLE[$device_type];

	$query = "SELECT location from ".$device_real_table_name." where ID = ".$device."";
	$location = $db->result($db->query($query),0,"location");

	$query = "SELECT ID FROM glpi_networking_ports WHERE (on_device = $device AND device_type = $device_type) ORDER BY logical_number";
	if ($result = $db->query($query)) {
		if ($db->numrows($result)!=0) { 
			
			$colspan=8;
			if ($withtemplate!=2){
			}
			
			echo "<br><div align='center'><table class='tab_cadre_fixe'>";
			echo "<tr><th colspan='$colspan'>";
			echo $db->numrows($result)." ";
			if ($db->numrows($result)<2) {
				echo $lang["networking"][37];
			} else {
				echo $lang["networking"][13];
			}
			echo ":</th>";

			echo "</tr>";        
			echo "<tr><th>#</th><th>".$lang["common"][16]."</th><th>".$lang["networking"][51]."</th>";
			echo "<th>".$lang["networking"][14]."</th><th>".$lang["networking"][15]."</th>";
			echo "<th>".$lang["networking"][56]."</th>";
			echo "<th>".$lang["networking"][16]."</th><th>".$lang["networking"][17].":</th></tr>\n";
			$i=0;
			while ($devid=$db->fetch_row($result)) {
				$netport = new Netport;
				$netport->getfromDB(current($devid));
				echo "<tr class='tab_bg_1'>";
				echo "<td align='center'><b>";
				if ($withtemplate!=2) echo "<a href=\"".$cfg_glpi["root_doc"]."/networking/networking-port.php?ID=".$netport->fields["ID"]."&amp;location=".$location."\">";
				echo $netport->fields["logical_number"];
				if ($withtemplate!=2) echo "</a>";
				echo "</b></td>";
				echo "<td>".$netport->fields["name"]."</td>";
				echo "<td>".getDropdownName("glpi_dropdown_netpoint",$netport->fields["netpoint"])."</td>";
				echo "<td>".$netport->fields["ifaddr"]."</td>";
				echo "<td>".$netport->fields["ifmac"]."</td>";
				// VLANs
				echo "<td>";
					showPortVLAN($netport->fields["ID"],$withtemplate);
				echo "</td>";
				echo "<td>".getDropdownName("glpi_dropdown_iface",$netport->fields["iface"])."</td>";
				echo "<td width='300'>";
					showConnection($netport->fields["ID"],$withtemplate,$device_type);
				echo "</td>";
				echo "</tr>";
			}
			echo "</table>";
			echo "</div>\n\n";
			
		}
	}
}

function showPortVLAN($ID,$withtemplate,$referer=''){
global $db,$HTMLRel,$lang;

$canedit=false;
if (haveRight("networking","w")) $canedit=true;
echo "<table cellpadding='0' cellspacing='0'>";

$query="SELECT * from glpi_networking_vlan WHERE FK_port='$ID'";
$result=$db->query($query);
if ($db->numrows($result)>0)
while ($line=$db->fetch_array($result)){
	echo "<tr><td>".getDropdownName("glpi_dropdown_vlan",$line["FK_vlan"]);
	echo "</td><td>";
	if ($canedit){
		echo "<a href='".$HTMLRel."networking/networking-port.php?unassign_vlan=unassigned&amp;ID=".$line["ID"]."&amp;referer=$referer'>";
		echo "<img src=\"".$HTMLRel."/pics/delete2.png\" alt='".$lang["buttons"][6]."' title='".$lang["buttons"][6]."'></a>";
	} else echo "&nbsp;";
    echo "</td></tr>";
}
echo "</table>";

}

function assignVlan($port,$vlan){
global $db;
$query="INSERT INTO glpi_networking_vlan (FK_port,FK_vlan) VALUES ('$port','$vlan')";
$db->query($query);

$np=new NetPort();
if ($np->getContact($port)){
	$query="INSERT INTO glpi_networking_vlan (FK_port,FK_vlan) VALUES ('".$np->contact_id."','$vlan')";
	$db->query($query);
}

}

function unassignVlan($ID){
global $db;
$query="DELETE FROM glpi_networking_vlan WHERE ID='$ID'";
$db->query($query);
}

function showNetportForm($target,$ID,$ondevice,$devtype,$several,$search = '', $location = '') {

	global $cfg_glpi, $lang, $REFERER;
	
	if (!haveRight("networking","r")) return false;

	$netport = new Netport;
	if($ID)
	{
		$netport->getFromDB($ID);
		$netport->getDeviceData($netport->fields["on_device"],$netport->fields["device_type"]);
	}
	else
	{
		$netport->getFromNull();
	}
	
	// Ajout des infos d��remplies
	if (isset($_POST)&&!empty($_POST)){
	foreach ($netport->fields as $key => $val)
		if ($key!='ID'&&isset($_POST[$key]))
		$netport->fields[$key]=$_POST[$key];
	}
	
	
	echo "<div align='center'>";
	echo "<p><a class='icon_consol' href='$REFERER'>".$lang["buttons"][13]."</a></p>";
	
	echo "<form method='post' action=\"$target\">";

	echo "<input type='hidden' name='referer' value='$REFERER'>";
	echo "<table class='tab_cadre'><tr>";
	
	echo "<th colspan='4'>".$lang["networking"][20].":</th>";
	echo "</tr>";

	if ($several!="yes"){
	echo "<tr class='tab_bg_1'><td>".$lang["networking"][21].":</td>";
	echo "<td>";
	autocompletionTextField("logical_number","glpi_networking_ports","logical_number",$netport->fields["logical_number"],5);	
	echo "</td></tr>";
	}
	else {
	echo "<tr class='tab_bg_1'><td>".$lang["networking"][21].":</td>";
	echo "<input type='hidden' name='several' value='yes'>";
	echo "<input type='hidden' name='logical_number' value=''>";
	echo "<td>";
	echo $lang["networking"][47].":<select name='from_logical_number'>";
	for ($i=0;$i<100;$i++)
		echo "<option value='$i'>$i</option>";
	echo "</select>";
	echo $lang["networking"][48].":<select name='to_logical_number'>";
	for ($i=0;$i<100;$i++)
		echo "<option value='$i'>$i</option>";
	echo "</select>";

	echo "</td></tr>";
	}
	
	echo "<tr class='tab_bg_1'><td>".$lang["common"][16].":</td>";
	echo "<td>";
	autocompletionTextField("name","glpi_networking_ports","name",$netport->fields["name"],20);	
	echo "</td></tr>";

	echo "<tr class='tab_bg_1'><td>".$lang["networking"][16].":</td><td>";
		dropdownValue("glpi_dropdown_iface","iface", $netport->fields["iface"]);
	echo "</td></tr>";

	echo "<tr class='tab_bg_1'><td>".$lang["networking"][14].":</td><td>";
	autocompletionTextField("ifaddr","glpi_networking_ports","ifaddr",$netport->fields["ifaddr"],20);	
	echo "</td></tr>\n";

	// Show device MAC adresses
	if ((!empty($netport->device_type)&&$netport->device_type==COMPUTER_TYPE)||($several!="yes"&&$devtype==COMPUTER_TYPE)){
		$comp=new Computer();

		if (!empty($netport->device_type))
		$comp->getFromDBwithDevices($netport->device_ID);
		else 
		$comp->getFromDBwithDevices($ondevice);

		$macs=array();
		$i=0;
		// Get MAC adresses :
		if (count($comp->devices)>0)	
			foreach ($comp->devices as $key => $val)
				if ($val['devType']==NETWORK_DEVICE&&!empty($val['specificity'])){
					$macs[$i]=$val['specificity'];
					$i++;
					}
		if (count($macs)>0){
			echo "<tr class='tab_bg_1'><td>".$lang["networking"][15].":</td><td>";
			echo "<select name='pre_mac'>";
			echo "<option value=''>------</option>";
			foreach ($macs as $key => $val){
			echo "<option value='".$val."' >$val</option>";	
			}
			echo "</select>";

			echo "</td></tr>\n";

			echo "<tr class='tab_bg_2'><td>&nbsp;</td>";
			echo "<td>".$lang["networking"][57];
			echo "</td></tr>\n";
			
		}
	}
	
	echo "<tr class='tab_bg_1'><td>".$lang["networking"][15].":</td><td>";
	autocompletionTextField("ifmac","glpi_networking_ports","ifmac",$netport->fields["ifmac"],25);	

	echo "</td></tr>\n";
	
	if ($several!="yes"){
	echo "<tr class='tab_bg_1'><td>".$lang["networking"][51].":</td>";
	
	echo "<td align='center' >";
		dropdownValue("glpi_dropdown_netpoint","netpoint", $netport->fields["netpoint"]);		
	echo "</td></tr>";
	}
	if ($ID) {
		echo "<tr class='tab_bg_2'>";
		echo "<td align='center'>";
		echo "<input type='hidden' name='ID' value=".$netport->fields["ID"].">";
		echo "<input type='submit' name='update' value=\"".$lang["buttons"][7]."\" class='submit'>";
		echo "</td>";

		echo "<td align='center'>";
		echo "<input type='submit' name='delete' value=\"".$lang["buttons"][6]."\" class='submit'>";
		echo "</td></tr>";
	} else 
	{

		echo "<tr class='tab_bg_2'>";
		echo "<td align='center' colspan='2'>";
		echo "<input type='hidden' name='on_device' value='$ondevice'>";
		echo "<input type='hidden' name='device_type' value='$devtype'>";
		echo "<input type='submit' name='add' value=\"".$lang["buttons"][8]."\" class='submit'>";
		echo "</td></tr>";
	}

	echo "</table></form></div>";	
	// SHOW VLAN 
	if ($ID){
	echo "<div align='center'>";
	echo "<form method='post' action=\"$target\">";
	echo "<input type='hidden' name='referer' value='$REFERER'>";
	echo "<input type='hidden' name='ID' value='$ID'>";

	echo "<table class='tab_cadre'><tr class='tab_bg_2'><td>";
	showPortVLAN($netport->fields["ID"],0,$REFERER);
	echo "</td></tr>";
	
	echo "<tr  class='tab_bg_2'><td>";
	echo $lang["networking"][55].":&nbsp;";
	dropdown("glpi_dropdown_vlan","vlan");
	echo "<input type='submit' name='assign_vlan' value='".$lang["buttons"][3]."' class='submit'>";
	echo "</td></tr>";
	
	echo "</table>";
	
	echo "</form>";
	


	
	echo "</div>";	

		
	}
}


function showPortsAdd($ID,$devtype) {
	
	global $db,$cfg_glpi, $lang,$LINK_ID_TABLE;
	
	if (!haveTypeRight($devtype,"w")) return false;

	$device_real_table_name = $LINK_ID_TABLE[$devtype];

	$query = "SELECT location from ".$device_real_table_name." where ID = ".$ID."";
	$location = $db->result($db->query($query),0,"location");

	echo "<div align='center'><table class='tab_cadre_fixe' cellpadding='2'>";
	echo "<tr>";
	echo "<td align='center' class='tab_bg_2'  >";
	echo "<a href=\"".$cfg_glpi["root_doc"]."/networking/networking-port.php?on_device=$ID&amp;device_type=$devtype&amp;location=$location\"><b>";
	echo $lang["networking"][19];
	echo "</b></a></td>";
	echo "<td align='center' class='tab_bg_2' width='50%'>";
	echo "<a href=\"".$cfg_glpi["root_doc"]."/networking/networking-port.php?on_device=$ID&amp;device_type=$devtype&amp;several=yes&amp;location=$location\"><b>";
	echo $lang["networking"][46];
	echo "</b></a></td>";

	echo "</tr>";
	echo "</table></div><br>";
}

function showConnection ($ID,$withtemplate='',$type=COMPUTER_TYPE) {

	global $cfg_glpi, $lang,$INFOFORM_PAGES;

	if (!haveTypeRight($type,"r")) return false;
	$canedit=false;
	if (haveRight("networking","w")) $canedit=true;

	$contact = new Netport;
	$netport = new Netport;
	
	if ($contact->getContact($ID)) {
		$netport->getfromDB($contact->contact_id);
		$netport->getDeviceData($netport->fields["on_device"],$netport->fields["device_type"]);
		echo "\n\n<table border='0' cellspacing='0' width='100%'><tr ".($netport->deleted=='Y'?"class='tab_bg_2_2'":"").">";
		echo "<td><b>";
		echo "<a href=\"".$cfg_glpi["root_doc"]."/networking/networking-port.php?ID=".$netport->fields["ID"]."\">";
		if (rtrim($netport->fields["name"])!="")
			echo $netport->fields["name"];
		else echo $lang["common"][0];
		echo "</a></b>";
		echo " ".$lang["networking"][25]." <b>";

		echo "<a href=\"".$cfg_glpi["root_doc"]."/".$INFOFORM_PAGES[$netport->fields["device_type"]]."?ID=".$netport->device_ID."\">";

		echo $netport->device_name;
		if ($cfg_glpi["view_ID"]) echo " (".$netport->device_ID.")";
		echo "</a>";
		echo "</b></td>";
		if ($canedit){
			echo "<td align='right'><b>";
			if ($withtemplate!=2)
				echo "<a href=\"".$cfg_glpi["root_doc"]."/networking/networking-port-disconnect.php?ID=$ID\">".$lang["buttons"][10]."</a>";
			else "&nbsp;";
			echo "</b></td>";
		}
		echo "</tr></table>";
		
	} else {
		echo "<table border='0' cellspacing='0' width='100%'><tr>";
		if ($canedit){
			echo "<td align='left'>";
			if ($withtemplate!=2&&$withtemplate!=1){
				echo "<form method='post' action=\"".$cfg_glpi["root_doc"]."/networking/networking-port-connect.php\">";
				echo "<input type='hidden' name='connect' value='connect'>";
				echo "<input type='hidden' name='sport' value='$ID'>";
				dropdownConnectPort($ID,$type,"dport");
				echo "</form>";
				}
			else echo "&nbsp;";
			echo "</td>";
		}
		echo "<td><div id='not_connected_display$ID'>".$lang["connect"][1]."</div></td>";

		echo "</tr></table>";
	}
}	


///// Wire the Ports /////


function makeConnector($sport,$dport) {

	global $db,$cfg_glpi, $lang;
	
	// Get netpoint for $sport and $dport
	$ps=new Netport;
	$ps->getFromDB($sport);
	$nps="";
	$ips="";
	$macs="";
	if (isset($ps->fields["netpoint"])&&$ps->fields["netpoint"]!=0)
		$nps=$ps->fields["netpoint"];
	if (isset($ps->fields["ifaddr"]))
		$ips=$ps->fields["ifaddr"];
	if (isset($ps->fields["ifmac"]))
		$macs=$ps->fields["ifmac"];
		
		
	$pd=new Netport;
	$pd->getFromDB($dport);
	$npd="";
	$ipd="";
	$macd="";
	if (isset($pd->fields["netpoint"])&&$pd->fields["netpoint"]!=0)
		$npd=$pd->fields["netpoint"];
	if (isset($pd->fields["ifaddr"]))
		$ipd=$pd->fields["ifaddr"];
	if (isset($pd->fields["ifmac"]))
		$macd=$pd->fields["ifmac"];

	// Update unknown IP
	$updates[0]="ifaddr";
	if (empty($ips)&&!empty($ipd)){
		$ps->fields["ifaddr"]=$ipd;
		$ps->updateInDB($updates);
		echo "<div align='center'><b>".$lang["connect"][19]."</b></div>";
		}
	else if (!empty($ips)&&empty($ipd)){
		$pd->fields["ifaddr"]=$ips;		
		$pd->updateInDB($updates);
		echo "<div align='center'><b>".$lang["connect"][19]."</b></div>";
		}
	else if ($ips!=$ipd){
		echo "<div align='center'><b>".$lang["connect"][20]."</b></div>";
		}
	// Update unknown MAC
	$updates[0]="ifmac";
	if (empty($macs)&&!empty($macd)){
		$ps->fields["ifmac"]=$macd;
		$ps->updateInDB($updates);
		echo "<div align='center'><b>".$lang["connect"][21]."</b></div>";
		}
	else if (!empty($macs)&&empty($macd)){
		$pd->fields["ifmac"]=$macs;		
		$pd->updateInDB($updates);
		echo "<div align='center'><b>".$lang["connect"][21]."</b></div>";
		}
	else if ($macs!=$macd){
		echo "<div align='center'><b>".$lang["connect"][22]."</b></div>";
		}
	// Update unknown netpoint
	$updates[0]="netpoint";
	if (empty($nps)&&!empty($npd)){
		$ps->fields["netpoint"]=$npd;
		$ps->updateInDB($updates);
		echo "<div align='center'><b>".$lang["connect"][17]."</b></div>";
		}
	else if (!empty($nps)&&empty($npd)){
		$pd->fields["netpoint"]=$nps;		
		$pd->updateInDB($updates);
		echo "<div align='center'><b>".$lang["connect"][17]."</b></div>";
		}
	else if ($nps!=$npd){
		echo "<div align='center'><b>".$lang["connect"][18]."</b></div>";
		}
	
	$query = "INSERT INTO glpi_networking_wire VALUES (NULL,$sport,$dport)";
	if ($result = $db->query($query)) {
		$source=new CommonItem;
		$source->getFromDB($ps->fields['device_type'],$ps->fields['on_device']);
		$dest=new CommonItem;
		$dest->getFromDB($pd->fields['device_type'],$pd->fields['on_device']);
		echo "<br><div align='center'><b>".$lang["networking"][44]." ".$source->getName()." - ".$ps->fields['logical_number']."  (".$ps->fields['ifaddr']." - ".$ps->fields['ifmac'].") ".$lang["networking"][45]." ".$dest->getName()." - ".$pd->fields['logical_number']." (".$pd->fields['ifaddr']." - ".$pd->fields['ifmac'].") </b></div>";
		return true;
	} else {
		return false;
	}

}

function removeConnector($ID) {

	global $db,$cfg_glpi;
	
	// Update to blank networking item
	$nw=new Netwire;
	if ($ID2=$nw->getOppositeContact($ID)){
	
	$np1=new Netport;
	$np2=new Netport;
	$np1->getFromDB($ID);
	$np2->getFromDB($ID2);
	$npnet=-1;
	$npdev=-1;
	if ($np1->fields["device_type"]!=NETWORKING_TYPE&&$np2->fields["device_type"]==NETWORKING_TYPE){
		$npnet=$ID2;
		$npdev=$ID;
		}
	if ($np2->fields["device_type"]!=NETWORKING_TYPE&&$np1->fields["device_type"]==NETWORKING_TYPE){
		$npnet=$ID;
		$npdev=$ID2;
		}
	if ($npnet!=-1&&$npdev!=-1){
		// Unset MAC and IP fron networking device
		$query = "UPDATE glpi_networking_ports SET ifaddr='', ifmac='' WHERE ID='$npnet'";	
		$db->query($query);
		// Unset netpoint from common device
		$query = "UPDATE glpi_networking_ports SET netpoint=NULL WHERE ID='$npdev'";	
		$db->query($query);

	}
	
	$query = "DELETE FROM glpi_networking_wire WHERE (end1 = '$ID' OR end2 = '$ID')";
	if ($result=$db->query($query)) {
		return true;
	} else {
		return false;
	}
	} else return false;
}


?>
