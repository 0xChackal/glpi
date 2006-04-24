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

$NEEDED_ITEMS=array("stat","tracking","user","setup","device");
include ($phproot . "/inc/includes.php");

commonHeader($lang["title"][11],$_SERVER["PHP_SELF"]);

checkRight("statistic","1");

echo "<div align='center'><p><b><span class='icon_sous_nav'>".$lang["stats"][19]."</span></b></p>";

if (isset($_GET["date1"])) $_POST["date1"] = $_GET["date1"];
if (isset($_GET["date2"])) $_POST["date2"] = $_GET["date2"];

if(empty($_POST["date1"])&&empty($_POST["date2"])) {
$year=date("Y")-1;
$_POST["date1"]=date("Y-m-d",mktime(1,0,0,date("m"),date("d"),$year));

$_POST["date2"]=date("Y-m-d");
}

if ($_POST["date1"]!=""&&$_POST["date2"]!=""&&strcmp($_POST["date2"],$_POST["date1"])<0){
$tmp=$_POST["date1"];
$_POST["date1"]=$_POST["date2"];
$_POST["date2"]=$tmp;
}

if(!isset($_GET["start"])) $_GET["start"] = 0;

if (isset($_GET["dropdown"])) $_POST["dropdown"] = $_GET["dropdown"];
if(empty($_POST["dropdown"])) $_POST["dropdown"] = "glpi_type_computers";

echo "<form method=\"post\" name=\"form\" action=\"stat_lieux.php\">";

echo "<table class='tab_cadre'><tr class='tab_bg_2'><td rowspan='2'>";
echo "<select name=\"dropdown\">";

echo "<option value=\"glpi_type_computers\" ".($_POST["dropdown"]=="glpi_type_computers"?"selected":"").">".$lang["common"][17]."</option>";
echo "<option value=\"glpi_dropdown_model\" ".($_POST["dropdown"]=="glpi_dropdown_model"?"selected":"").">".$lang["common"][22]."</option>";

echo "<option value=\"glpi_dropdown_os\" ".($_POST["dropdown"]=="glpi_dropdown_os"?"selected":"").">".$lang["computers"][9]."</option>";
echo "<option value=\"glpi_dropdown_locations\" ".($_POST["dropdown"]=="glpi_dropdown_locations"?"selected":"").">".$lang["common"][15]."</option>";


for ($i=MOBOARD_DEVICE;$i<=POWER_DEVICE;$i++)
	echo "<option value=\"$i\" ".($_POST["dropdown"]==$i?"selected":"").">".getDeviceTypeLabel($i)."</option>";
echo "</select></td>";


echo "<td align='right'>";
echo $lang["search"][8]." :</td><td>";
showCalendarForm("form","date1",$_POST["date1"]);
echo "</td><td rowspan='2' align='center'><input type=\"submit\" class='button' name=\"submit\" Value=\"". $lang["buttons"][7] ."\" /></td></tr>";
echo "<tr class='tab_bg_2'><td align='right'>".$lang["search"][9]." :</td><td>";
showCalendarForm("form","date2",$_POST["date2"]);
echo "</td></tr>";
echo "</table></form></div>";

echo "<div align ='center'>";

if(is_dropdown_stat($_POST["dropdown"])) {
	//recuperation des differents lieux d'interventions
	//Get the distincts intervention location
	$type = getNbIntervDropdown($_POST["dropdown"]);
	


	if (is_array($type))
	{

		$numrows=count($type);
		printPager($_GET['start'],$numrows,$_SERVER['PHP_SELF'],"dropdown=".$_POST["dropdown"]."&amp;date1=".$_POST["date1"]."&amp;date2=".$_POST["date2"]);

		sort($type);

 //affichage du tableau
		 echo "<table class='tab_cadre_fixe' cellpadding='5' >";
		 $champ=str_replace("locations","location",str_replace("glpi_","",str_replace("dropdown_","",str_replace("_computers","",$_POST["dropdown"]))));
		 echo "<tr><th>&nbsp;</th><th>&nbsp;</th><th>".$lang["stats"][22]."</th><th>".$lang["stats"][14]."</th><th>".$lang["stats"][15]."</th><th>".$lang["stats"][25]."</th><th>".$lang["stats"][27]."</th><th>".$lang["stats"][30]."</th></tr>";

		//Pour chaque lieu on affiche
		//for each location displays
		for ($i=$_GET['start'];$i< $numrows && $i<($_GET['start']+$cfg_glpi["list_limit"]);$i++){
			$query="SELECT count(*) FROM glpi_computers WHERE $champ='".$type[$i]["ID"]."'";

			if ($result=$db->query($query))
				$count=$db->result($result,0,0);
			else $count=0; 

			echo "<tr class='tab_bg_1'>";
			echo "<td>".getDropdownName($_POST["dropdown"],$type[$i]["ID"]) ."&nbsp;($count)</td><td><a href='stat.graph.php?ID=".$type[$i]["ID"]."&amp;champ=".$champ."&amp;type=comp_champ'><img src=\"".$HTMLRel."pics/stats_item.png\" alt='' title=''></a></td>";
			//le nombre d'intervention
			//the number of intervention
			echo "<td>".getNbinter(4,"glpi_computers.".getDropdownNameFromTableForStats($_POST["dropdown"]),$type[$i]["ID"],$_POST["date1"],$_POST["date2"] )."</td>";
			//le nombre d'intervention resolues
			//the number of resolved intervention
			echo "<td>".getNbresol(4,"glpi_computers.".getDropdownNameFromTableForStats($_POST["dropdown"]),$type[$i]["ID"],$_POST["date1"],$_POST["date2"])."</td>";
			//Le temps moyen de resolution
			//The average time to resolv
			echo "<td>".getResolAvg(4,"glpi_computers.".getDropdownNameFromTableForStats($_POST["dropdown"]),$type[$i]["ID"],$_POST["date1"],$_POST["date2"])."</td>";
			//Le temps moyen de l'intervention r�lle
			//The average realtime to resolv
			echo "<td>".getRealAvg(4,"glpi_computers.".getDropdownNameFromTableForStats($_POST["dropdown"]),$type[$i]["ID"],$_POST["date1"],$_POST["date2"])."</td>";
			//Le temps total de l'intervention r�lle
			//The total realtime to resolv
			echo "<td>".getRealTotal(4,"glpi_computers.".getDropdownNameFromTableForStats($_POST["dropdown"]),$type[$i]["ID"],$_POST["date1"],$_POST["date2"])."</td>";
			//
			//
			echo "<td>".getFirstActionAvg(4,"glpi_computers.".getDropdownNameFromTableForStats($_POST["dropdown"]),$type[$i]["ID"],$_POST["date1"],$_POST["date2"])."</td>";

			echo "</tr>";
		}
	echo "</table>";
	} else {
		echo $lang["stats"][23];
	}
} else {

//---------------------- DEVICE ------------------------------------------------------
	$device_table = getDeviceTable($_POST["dropdown"]);
	
	//print_r($_POST["dropdown"]);
	$device_type = $_POST["dropdown"];
	//select devices IDs (table row)
	$query = "select ID, designation from ".$device_table." order by designation";
	$result = $db->query($query);
		
	if($db->numrows($result) >=1) {
		$i = 0;
		while($line = $db->fetch_assoc($result)) {
		$tab[$i]['ID'] = $line['ID'];
		$tab[$i]['designation'] = $line['designation'];
		$i++;
		}
	}
	if (is_array($tab)){
	sort($tab);
	$numrows=count($tab);
	printPager($_GET['start'],$numrows,$_SERVER['PHP_SELF'],"dropdown=".$_POST["dropdown"]."&amp;date1=".$_POST["date1"]."&amp;date2=".$_POST["date2"]);

	echo "<table class='tab_cadre_fixe' cellpadding='5' >";
	echo "<tr><th>&nbsp;</th><th>&nbsp;</th><th>".$lang["stats"][22]."</th><th>".$lang["stats"][14]."</th><th>".$lang["stats"][15]."</th><th>".$lang["stats"][25]."</th><th>".$lang["stats"][27]."</th><th>".$lang["stats"][30]."</th></tr>";
	
	for ($i=$_GET['start'];$i< $numrows && $i<($_GET['start']+$cfg_glpi["list_limit"]);$i++) {
		
		//select computers IDs that are using this device;
		$query2 = "SELECT distinct(glpi_computers.ID) as compid FROM glpi_computers INNER JOIN glpi_computer_device ON ( glpi_computers.ID = glpi_computer_device.FK_computers AND glpi_computer_device.device_type = '".$device_type."' AND glpi_computer_device.FK_device = '".$tab[$i]["ID"]."') WHERE glpi_computers.is_template <> '1'";
		
		$result2 = $db->query($query2);
		$designation = $tab[$i]["designation"];
		$resolvavg = 0;
		$realavg = 0;
		$realtotal = 0;
		$realfirst = 0;
		$nbinterv=0;
		$nbintervresolv=0;
		$compsearch=" ('0'='1'";
		while($line2 = $db->fetch_array($result2)) {
			$compsearch.=" OR computer='".$line2["compid"]."'";
		}
		$compsearch.=")";
		
			//select ID of tracking using this computer id
			//nbintervresolv
			$query3 = "select ID from glpi_tracking where device_type = '".COMPUTER_TYPE."' and $compsearch";
			if(!empty($_POST["date1"])) $query3.= " and glpi_tracking.date >= '". $_POST["date1"] ."' ";
			if(!empty($_POST["date2"])) $query3.= " and glpi_tracking.date <= adddate( '". $_POST["date2"] ."' , INTERVAL 1 DAY ) ";
			
			$result3 = $db->query($query3);
			$nbinterv=$db->numrows($result3);
			
			//nbinterv
			$query4 = $query3." AND ( status = 'old_done' OR status = 'old_not_done' )";
			if(!empty($_POST["date1"])) $query4.= " and glpi_tracking.date >= '". $_POST["date1"] ."' ";
			if(!empty($_POST["date2"])) $query4.= " and glpi_tracking.date <= adddate( '". $_POST["date2"] ."' , INTERVAL 1 DAY ) ";
			$result4 = $db->query($query4);
			$nbintervresolv=$db->numrows($result4);
			
			//resolvavg
			$query5 = "SELECT AVG(UNIX_TIMESTAMP(glpi_tracking.closedate)-UNIX_TIMESTAMP(glpi_tracking.date)) as total from glpi_tracking where device_type = '".COMPUTER_TYPE."' and $compsearch AND ( status = 'old_done' OR status = 'old_not_done' ) AND glpi_tracking.closedate != '0000-00-00'";
			if(!empty($_POST["date1"])) $query5.= " and glpi_tracking.date >= '". $_POST["date1"] ."' ";
			if(!empty($_POST["date2"])) $query5.= " and glpi_tracking.date <= adddate( '". $_POST["date2"] ."' , INTERVAL 1 DAY ) ";
			$result5 = $db->query($query5);
			$resolvavg=$db->result($result5,0,"total");;
			
			//realavg
			$query6 = "SELECT AVG(glpi_tracking.realtime) as total from glpi_tracking where device_type = '".COMPUTER_TYPE."' and $compsearch AND ( glpi_tracking.status = 'old_done' OR glpi_tracking.status = 'old_not_done' ) and glpi_tracking.closedate != '0000-00-00' AND glpi_tracking.realtime > 0";
			if(!empty($_POST["date1"])) $query6.= " and glpi_tracking.date >= '".$_POST["date1"]."' ";
			if(!empty($_POST["date2"])) $query6.= " and glpi_tracking.date <= adddate( '". $_POST["date2"] ."' , INTERVAL 1 DAY ) ";
			$result6 = $db->query($query6);
			$realavg=$db->result($result6,0,"total");
			
			//realtotal
			$query7 = "select SUM(glpi_tracking.realtime) as total from glpi_tracking where device_type = '".COMPUTER_TYPE."' and $compsearch AND ( glpi_tracking.status = 'old_done' OR glpi_tracking.status = 'old_not_done' ) and glpi_tracking.closedate != '0000-00-00' AND glpi_tracking.realtime > 0";
			if(!empty($_POST["date1"])) $query7.= " and glpi_tracking.date >= '". $_POST["date1"] ."' ";
			if(!empty($_POST["date2"])) $query7.= " and glpi_tracking.date <= adddate( '". $_POST["date2"] ."' , INTERVAL 1 DAY ) ";
			$result7 = $db->query($query7);
			$realtotal=$db->result($result7,0,"total");;
			
			//realfirst 
			$query8 = "select glpi_tracking.ID,  MIN(UNIX_TIMESTAMP(glpi_followups.date)-UNIX_TIMESTAMP(glpi_tracking.date)) as first from glpi_tracking LEFT JOIN glpi_followups ON (glpi_followups.tracking = glpi_tracking.ID) where device_type = '".COMPUTER_TYPE."' and $compsearch AND ( glpi_tracking.status = 'old_done' OR glpi_tracking.status = 'old_not_done' ) and glpi_tracking.closedate != '0000-00-00' AND glpi_tracking.realtime > 0 ";
			if(!empty($_POST["date1"])) $query8.= " and glpi_tracking.date >= '". $_POST["date1"] ."' ";
			if(!empty($_POST["date2"])) $query8.= " and glpi_tracking.date <= adddate( '". $_POST["date2"] ."' , INTERVAL 1 DAY ) ";
			$query8 .= "group by glpi_tracking.id";
			$result8 = $db->query($query8);
			$realfirst=0;
			while($line8 = $db->fetch_array($result8)) {
				$realfirst += $line8["first"];
			}
		
		//print row
		echo "<tr class='tab_bg_1'>";
		//first column name of the device
		echo "<td>".$tab[$i]["designation"]."</td>";
		echo "<td><a href='stat.graph.php?ID=".$tab[$i]["ID"]."&amp;device=".$device_type."&amp;type=device'><img src=\"".$HTMLRel."pics/stats_item.png\" alt='' title=''></a>";
		//second column count nb interv
		echo "<td>".$nbinterv."</td>";
		//third column nb resolved interventions
		echo "<td>".$nbintervresolv."</td>";
		//forth column
		echo "<td>".toTimeStr(floor($resolvavg))."</td>";
		//5th column
		echo "<td>".toTimeStr(floor($realavg))."</td>";
		//6th column
		echo "<td>".toTimeStr(floor($realtotal))."</td>";
		//7th collumn
		if($realfirst < $realtotal && $realfirst != 0) { 
			echo "<td>".toTimeStr(floor($realfirst))."</td>";
		} else {
			echo "<td>".toTimeStr(floor($realtotal))."</td>";
		}
		$nbintervresolv = array();
		$nbinterv = array();
		$resolvavg = 0;
		$realavg = 0;
		$realtotal = 0;
		$realfirst = 0;
		echo "</tr>";
		
		
	}
	echo "</table>";
	} else {
		echo $lang["stats"][23];
	}

}


echo "</div>"; 


commonFooter();
?>
