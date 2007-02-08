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

///// Manage Netdevices /////


///// Manage Ports on Devices /////

function showPorts ($device,$device_type,$withtemplate='') {

	global $DB,$CFG_GLPI, $LANG,$LINK_ID_TABLE;

	if (!haveRight("networking","r")) return false;
	$canedit=haveRight("networking","w");

	$device_real_table_name = $LINK_ID_TABLE[$device_type];

	$query = "SELECT ID FROM glpi_networking_ports WHERE (on_device = $device AND device_type = $device_type) ORDER BY name, logical_number";
	if ($result = $DB->query($query)) {
		if ($DB->numrows($result)!=0) { 
			$colspan=8;
			if (empty($withtemplate)){
				echo "<form id='networking_ports' name='networking_ports' method='post' action=\"".$CFG_GLPI["root_doc"]."/front/networking.port.php\">";
				if ($canedit)
					$colspan++;
			}

			echo "<div align='center'><table class='tab_cadre_fixe'>";
			echo "<tr>";
			echo "<th colspan='$colspan'>";
			echo $DB->numrows($result)." ";
			if ($DB->numrows($result)<2) {
				echo $LANG["networking"][37];
			} else {
				echo $LANG["networking"][13];
			}
			echo ":</th>";

			echo "</tr>";        
			echo "<tr>";
			if ($withtemplate!=2&&$canedit){
				echo "<th>&nbsp;</th>";
			}
			echo "<th>#</th><th>".$LANG["common"][16]."</th><th>".$LANG["networking"][51]."</th>";
			echo "<th>".$LANG["networking"][14]."</th><th>".$LANG["networking"][15]."</th>";
			echo "<th>".$LANG["networking"][56]."</th>";
			echo "<th>".$LANG["networking"][16]."</th><th>".$LANG["networking"][17].":</th></tr>\n";
			$i=0;
			while ($devid=$DB->fetch_row($result)) {
				$netport = new Netport;
				$netport->getfromDB(current($devid));
				echo "<tr class='tab_bg_1'>";
				if ($withtemplate!=2&&$canedit){
					echo "<td align='center' width='20'><input type='checkbox' name='del_port[".$netport->fields["ID"]."]' value='1'></td>";
				}
				echo "<td align='center'><b>";
				if ($withtemplate!=2) echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/networking.port.php?ID=".$netport->fields["ID"]."\">";
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

			if ($canedit){
				echo "<div align='center'>";
				echo "<table cellpadding='5' width='950'>";
				echo "<tr><td><img src=\"".$CFG_GLPI["root_doc"]."/pics/arrow-left.png\" alt=''></td><td><a onclick= \"if ( markAllRows('networking_ports') ) return false;\" href='".$_SERVER['PHP_SELF']."?ID=$device&amp;select=all'>".$LANG["buttons"][18]."</a></td>";

				echo "<td>/</td><td><a onclick= \"if ( unMarkAllRows('networking_ports') ) return false;\" href='".$_SERVER['PHP_SELF']."?ID=$device&amp;select=none'>".$LANG["buttons"][19]."</a>";
				echo "</td>";
				echo "<td width='80%' align='left'>";
				dropdownMassiveActionPorts();
				echo "</td>";
				echo "</table>";

				echo "</div>";
			} 
			if (empty($withtemplate)){
				echo "</form>";
			}
		}
	}
}

function showPortVLAN($ID,$withtemplate,$referer=''){
	global $DB,$CFG_GLPI,$LANG;

	$canedit=haveRight("networking","w");



	$query="SELECT * from glpi_networking_vlan WHERE FK_port='$ID'";
	$result=$DB->query($query);
	if ($DB->numrows($result)>0){
		echo "<table cellpadding='0' cellspacing='0'>";	
		while ($line=$DB->fetch_array($result)){
			echo "<tr><td>".getDropdownName("glpi_dropdown_vlan",$line["FK_vlan"]);
			echo "</td><td>";
			if ($canedit){
				echo "<a href='".$CFG_GLPI["root_doc"]."/front/networking.port.php?unassign_vlan=unassigned&amp;ID=".$line["ID"]."&amp;referer=$referer'>";
				echo "<img src=\"".$CFG_GLPI["root_doc"]."/pics/delete2.png\" alt='".$LANG["buttons"][6]."' title='".$LANG["buttons"][6]."'></a>";
			} else echo "&nbsp;";
			echo "</td></tr>";
		}
		echo "</table>";
	} else echo "&nbsp;";


}

function assignVlan($port,$vlan){
	global $DB;
	$query="INSERT INTO glpi_networking_vlan (FK_port,FK_vlan) VALUES ('$port','$vlan')";
	$DB->query($query);

	$np=new NetPort();
	if ($np->getContact($port)){
		$query="INSERT INTO glpi_networking_vlan (FK_port,FK_vlan) VALUES ('".$np->contact_id."','$vlan')";
		$DB->query($query);
	}

}

function unassignVlanbyID($ID){
	global $DB;
	$query="DELETE FROM glpi_networking_vlan WHERE ID='$ID'";
	$DB->query($query);
}

function unassignVlan($portID,$vlanID){
	global $DB;
	$query="DELETE FROM glpi_networking_vlan WHERE FK_port='$portID' AND FK_vlan='$vlanID'";
	$DB->query($query);
}

function showNetportForm($target,$ID,$ondevice,$devtype,$several) {

	global $CFG_GLPI, $LANG, $REFERER;

	if (!haveRight("networking","r")) return false;

	$netport = new Netport;
	if($ID)
	{
		$netport->getFromDB($ID);
		$netport->getDeviceData($netport->fields["on_device"],$netport->fields["device_type"]);
	}
	else
	{
		$netport->getEmpty();
	}

	// Ajout des infos d��remplies
	if (isset($_POST)&&!empty($_POST)){
		foreach ($netport->fields as $key => $val)
			if ($key!='ID'&&isset($_POST[$key]))
				$netport->fields[$key]=$_POST[$key];
	}

	displayTitle("","","",array($REFERER=>$LANG["buttons"][13]));

	echo "<br><div align='center'>";

	echo "<form method='post' action=\"$target\">";

	echo "<input type='hidden' name='referer' value='".urlencode($REFERER)."'>";
	echo "<table class='tab_cadre'><tr>";

	echo "<th colspan='4'>".$LANG["networking"][20].":</th>";
	echo "</tr>";

	if ($several!="yes"){
		echo "<tr class='tab_bg_1'><td>".$LANG["networking"][21].":</td>";
		echo "<td>";
		autocompletionTextField("logical_number","glpi_networking_ports","logical_number",$netport->fields["logical_number"],5);	
		echo "</td></tr>";
	}
	else {
		echo "<tr class='tab_bg_1'><td>".$LANG["networking"][21].":</td>";
		echo "<input type='hidden' name='several' value='yes'>";
		echo "<input type='hidden' name='logical_number' value=''>";
		echo "<td>";
		echo $LANG["networking"][47].":&nbsp;";
		dropdownInteger('from_logical_number',0,0,100);
		echo $LANG["networking"][48].":&nbsp;";
		dropdownInteger('to_logical_number',0,0,100);
		echo "</td></tr>";
	}

	echo "<tr class='tab_bg_1'><td>".$LANG["common"][16].":</td>";
	echo "<td>";
	autocompletionTextField("name","glpi_networking_ports","name",$netport->fields["name"],20);	
	echo "</td></tr>";

	echo "<tr class='tab_bg_1'><td>".$LANG["networking"][16].":</td><td>";
	dropdownValue("glpi_dropdown_iface","iface", $netport->fields["iface"]);
	echo "</td></tr>";

	echo "<tr class='tab_bg_1'><td>".$LANG["networking"][14].":</td><td>";
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
			echo "<tr class='tab_bg_1'><td>".$LANG["networking"][15].":</td><td>";
			echo "<select name='pre_mac'>";
			echo "<option value=''>------</option>";
			foreach ($macs as $key => $val){
				echo "<option value='".$val."' >$val</option>";	
			}
			echo "</select>";

			echo "</td></tr>\n";

			echo "<tr class='tab_bg_2'><td>&nbsp;</td>";
			echo "<td>".$LANG["networking"][57];
			echo "</td></tr>\n";

		}
	}

	echo "<tr class='tab_bg_1'><td>".$LANG["networking"][15].":</td><td>";
	autocompletionTextField("ifmac","glpi_networking_ports","ifmac",$netport->fields["ifmac"],25);	

	echo "</td></tr>\n";

	if ($several!="yes"){
		echo "<tr class='tab_bg_1'><td>".$LANG["networking"][51].":</td>";

		echo "<td align='center' >";
		dropdownValue("glpi_dropdown_netpoint","netpoint", $netport->fields["netpoint"]);		
		echo "</td></tr>";
	}
	if ($ID) {
		echo "<tr class='tab_bg_2'>";
		echo "<td align='center'>";
		echo "<input type='hidden' name='ID' value=".$netport->fields["ID"].">";
		echo "<input type='submit' name='update' value=\"".$LANG["buttons"][7]."\" class='submit'>";
		echo "</td>";

		echo "<td align='center'>";
		echo "<input type='submit' name='delete' value=\"".$LANG["buttons"][6]."\" class='submit'>";
		echo "</td></tr>";
	} else 
	{

		echo "<tr class='tab_bg_2'>";
		echo "<td align='center' colspan='2'>";
		echo "<input type='hidden' name='on_device' value='$ondevice'>";
		echo "<input type='hidden' name='device_type' value='$devtype'>";
		echo "<input type='submit' name='add' value=\"".$LANG["buttons"][8]."\" class='submit'>";
		echo "</td></tr>";
	}

	echo "</table></form></div>";	
	// SHOW VLAN 
	if ($ID){
		echo "<div align='center'>";
		echo "<form method='post' action=\"$target\">";
		echo "<input type='hidden' name='referer' value='$REFERER'>";
		echo "<input type='hidden' name='ID' value='$ID'>";

		echo "<table class='tab_cadre'>";
		echo "<tr><th>".$LANG["setup"][90]."</th></tr>";
		echo "<tr class='tab_bg_2'><td>";
		showPortVLAN($netport->fields["ID"],0,$REFERER);
		echo "</td></tr>";

		echo "<tr  class='tab_bg_2'><td>";
		echo $LANG["networking"][55].":&nbsp;";
		dropdown("glpi_dropdown_vlan","vlan");
		echo "&nbsp;<input type='submit' name='assign_vlan' value='".$LANG["buttons"][3]."' class='submit'>";
		echo "</td></tr>";

		echo "</table>";

		echo "</form>";




		echo "</div>";	


	}
}


function showPortsAdd($ID,$devtype) {

	global $DB,$CFG_GLPI, $LANG,$LINK_ID_TABLE;

	if (!haveTypeRight($devtype,"w")) return false;

	$device_real_table_name = $LINK_ID_TABLE[$devtype];


	echo "<div align='center'><table class='tab_cadre_fixe' cellpadding='2'>";
	echo "<tr>";
	echo "<td align='center' class='tab_bg_2'  >";
	echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/networking.port.php?on_device=$ID&amp;device_type=$devtype\"><b>";
	echo $LANG["networking"][19];
	echo "</b></a></td>";
	echo "<td align='center' class='tab_bg_2' width='50%'>";
	echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/networking.port.php?on_device=$ID&amp;device_type=$devtype&amp;several=yes\"><b>";
	echo $LANG["networking"][46];
	echo "</b></a></td>";

	echo "</tr>";
	echo "</table></div><br>";
}

function showConnection($ID,$withtemplate='',$type=COMPUTER_TYPE) {

	global $CFG_GLPI, $LANG,$INFOFORM_PAGES;

	if (!haveTypeRight($type,"r")) return false;
	$canedit=haveRight("networking","w");

	$contact = new Netport;
	$netport = new Netport;

	if ($contact->getContact($ID)) {
		$netport->getfromDB($contact->contact_id);
		$netport->getDeviceData($netport->fields["on_device"],$netport->fields["device_type"]);
		echo "\n\n<table border='0' cellspacing='0' width='100%'><tr ".($netport->deleted=='Y'?"class='tab_bg_2_2'":"").">";
		echo "<td><b>";
		echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/networking.port.php?ID=".$netport->fields["ID"]."\">";
		if (rtrim($netport->fields["name"])!="")
			echo $netport->fields["name"];
		else echo $LANG["common"][0];
		echo "</a></b>";
		echo " ".$LANG["networking"][25]." <b>";

		echo "<a href=\"".$CFG_GLPI["root_doc"]."/".$INFOFORM_PAGES[$netport->fields["device_type"]]."?ID=".$netport->device_ID."\">";

		echo $netport->device_name;
		if ($CFG_GLPI["view_ID"]) echo " (".$netport->device_ID.")";
		echo "</a>";
		echo "</b></td>";
		if ($canedit){
			echo "<td align='right'><b>";
			if ($withtemplate!=2)
				echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/networking.port.php?disconnect=disconnect&amp;ID=$ID\">".$LANG["buttons"][10]."</a>";
			else "&nbsp;";
			echo "</b></td>";
		}
		echo "</tr></table>";

	} else {
		echo "<table border='0' cellspacing='0' width='100%'><tr>";
		if ($canedit){
			echo "<td align='left'>";
			if ($withtemplate!=2&&$withtemplate!=1){
				dropdownConnectPort($ID,$type,"dport");
			}
			else echo "&nbsp;";
			echo "</td>";
		}
		echo "<td><div id='not_connected_display$ID'>".$LANG["connect"][1]."</div></td>";

		echo "</tr></table>";
	}
}	


///// Wire the Ports /////


function makeConnector($sport,$dport) {

	global $DB,$CFG_GLPI, $LANG;

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
		echo "<div align='center'><b>".$LANG["connect"][19]."</b></div>";
	}
	else if (!empty($ips)&&empty($ipd)){
		$pd->fields["ifaddr"]=$ips;		
		$pd->updateInDB($updates);
		echo "<div align='center'><b>".$LANG["connect"][19]."</b></div>";
	}
	else if ($ips!=$ipd){
		echo "<div align='center'><b>".$LANG["connect"][20]."</b></div>";
	}
	// Update unknown MAC
	$updates[0]="ifmac";
	if (empty($macs)&&!empty($macd)){
		$ps->fields["ifmac"]=$macd;
		$ps->updateInDB($updates);
		echo "<div align='center'><b>".$LANG["connect"][21]."</b></div>";
	}
	else if (!empty($macs)&&empty($macd)){
		$pd->fields["ifmac"]=$macs;		
		$pd->updateInDB($updates);
		echo "<div align='center'><b>".$LANG["connect"][21]."</b></div>";
	}
	else if ($macs!=$macd){
		echo "<div align='center'><b>".$LANG["connect"][22]."</b></div>";
	}
	// Update unknown netpoint
	$updates[0]="netpoint";
	if (empty($nps)&&!empty($npd)){
		$ps->fields["netpoint"]=$npd;
		$ps->updateInDB($updates);
		echo "<div align='center'><b>".$LANG["connect"][17]."</b></div>";
	}
	else if (!empty($nps)&&empty($npd)){
		$pd->fields["netpoint"]=$nps;		
		$pd->updateInDB($updates);
		echo "<div align='center'><b>".$LANG["connect"][17]."</b></div>";
	}
	else if ($nps!=$npd){
		echo "<div align='center'><b>".$LANG["connect"][18]."</b></div>";
	}

	$query = "INSERT INTO glpi_networking_wire VALUES (NULL,$sport,$dport)";
	if ($result = $DB->query($query)) {
		$source=new CommonItem;
		$source->getFromDB($ps->fields['device_type'],$ps->fields['on_device']);
		$dest=new CommonItem;
		$dest->getFromDB($pd->fields['device_type'],$pd->fields['on_device']);
		echo "<br><div align='center'><b>".$LANG["networking"][44]." ".$source->getName()." - ".$ps->fields['logical_number']."  (".$ps->fields['ifaddr']." - ".$ps->fields['ifmac'].") ".$LANG["networking"][45]." ".$dest->getName()." - ".$pd->fields['logical_number']." (".$pd->fields['ifaddr']." - ".$pd->fields['ifmac'].") </b></div>";
		return true;
	} else {
		return false;
	}

}

function removeConnector($ID) {

	global $DB,$CFG_GLPI;

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
			$DB->query($query);
			// Unset netpoint from common device
			$query = "UPDATE glpi_networking_ports SET netpoint=NULL WHERE ID='$npdev'";	
			$DB->query($query);

		}

		$query = "DELETE FROM glpi_networking_wire WHERE (end1 = '$ID' OR end2 = '$ID')";
		if ($result=$DB->query($query)) {
			return true;
		} else {
			return false;
		}
	} else return false;
}


?>
