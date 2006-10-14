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



if (!defined('GLPI_ROOT')){
	die("Sorry. You can't access directly to this file");
	}


function showCentralReminder($type="private"){
	// show reminder that are not planned 

	global $DB,$CFG_GLPI, $LANG;

	$author=$_SESSION['glpiID'];	
	$today=date("Y-m-d H:i:s");

	if($type=="public"){ // show public reminder
		$query="SELECT * FROM glpi_reminder WHERE type='public' AND (end>='$today' or rv='0')";
		$titre="<a href=\"".$CFG_GLPI["root_doc"]."/front/reminder.php\">".$LANG["reminder"][1]."</a>";
	}else{ // show private reminder
		$query="SELECT * FROM glpi_reminder WHERE author='$author' AND type='private' AND (end>='$today' or rv='0') ";
		$titre="<a href=\"".$CFG_GLPI["root_doc"]."/front/reminder.php\">".$LANG["reminder"][0]."</a>";
	}


	$result = $DB->query($query);



	echo "<div align='center'><br><table class='tab_cadrehov'>";

	echo "<tr><th><div style='position: relative'><span><strong>"."$titre"."</strong></span>";
	if ($type!="public"||haveRight("reminder_public","w"))
		echo "<span style='  position:absolute; right:0; margin-right:5px; font-size:10px;'><a href=\"".$CFG_GLPI["root_doc"]."/front/reminder.form.php?type=$type\"><img src=\"".$CFG_GLPI["root_doc"]."/pics/plus.png\" alt='+' title='".$LANG["buttons"][8]."'></a></span>";
	echo "</div>";
	echo "</th></tr>";
	if($DB->numrows($result)>0){
		while ($data =$DB->fetch_array($result)){ 

			echo "<tr class='tab_bg_2'><td><div style='position: relative'><span><a style='margin-left:8px' href=\"".$CFG_GLPI["root_doc"]."/front/reminder.form.php?ID=".$data["ID"]."\">".$data["title"]."</a></span>";

			if($data["rv"]=="1"){

				$tab=split(" ",$data["begin"]);
				$date_url=$tab[0];

				echo "<span style='  position:absolute; right:0; margin-right:5px; font-size:10px;'><a href=\"".$CFG_GLPI["root_doc"]."/front/planning.php?date=".$date_url."&amp;type=day\"><img src=\"".$CFG_GLPI["root_doc"]."/pics/rdv.png\" alt='".$LANG["planning"][3]."' title='".convDateTime($data["begin"])."=>".convDateTime($data["end"])."'></a></span>";



			}

			echo "</div></td></tr>";


		}
	}


	echo "</table></div>";
}


function showListReminder($type="private"){
	// show reminder that are not planned 

	global $DB,$CFG_GLPI, $LANG;

	$author=$_SESSION['glpiID'];	

	if($type=="public"){ // show public reminder
		$query="SELECT * FROM glpi_reminder WHERE type='public'";
		$titre=$LANG["reminder"][1];
	}else{ // show private reminder
		$query="SELECT * FROM glpi_reminder WHERE author='$author' AND type='private' ";
		$titre=$LANG["reminder"][0];
	}


	$result = $DB->query($query);

	$tabremind=array();

	$remind=new Reminder();

	$i=0;
	if ($DB->numrows($result)>0)
		while ($data=$DB->fetch_array($result)){
			$remind->getFromDB($data["ID"]);

			if($data["rv"]==1){ //Un rdv on va trier sur la date begin
				$sort=$data["begin"];
			}else{ // non programmé on va trier sur la date de modif...
				$sort=$data["date"];
			}


			$tabremind[$sort."$$".$i]["id_reminder"]=$remind->fields["ID"];
			$tabremind[$sort."$$".$i]["begin"]=($data["rv"]==1?"".$data["begin"]."":"".$data["date"]."");
			$tabremind[$sort."$$".$i]["end"]=($data["rv"]==1?"".$data["end"]."":"");
			$tabremind[$sort."$$".$i]["title"]=resume_text($remind->fields["title"],$CFG_GLPI["cut"]);
			$tabremind[$sort."$$".$i]["text"]=resume_text($remind->fields["text"],$CFG_GLPI["cut"]);
			$i++;
		}



	ksort($tabremind);


	echo "<div align='center' ><br><table class='tab_cadrehov' style='width:600px'>";
	echo "<tr><th>"."$titre"."</th><th colspan='2'>".$LANG["common"][27]."</th></tr>";

	if (count($tabremind)>0){
		foreach ($tabremind as $key => $val){

			echo "<tr class='tab_bg_2'><td width='70%'><a href=\"".$CFG_GLPI["root_doc"]."/front/reminder.form.php?ID=".$val["id_reminder"]."\">".$val["title"]."</a></td>";

			if($val["end"]!=""){	
				echo "<td style='text-align:center;'>";

				$tab=split(" ",$val["begin"]);
				$date_url=$tab[0];
				echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/planning.php?date=".$date_url."&amp;type=day\"><img src=\"".$CFG_GLPI["root_doc"]."/pics/rdv.png\" alt='".$LANG["planning"][3]."' title='".$LANG["planning"][3]."'></a>";
				echo "</td>";
				echo "<td style='text-align:center;' ><strong>".convDateTime($val["begin"]);
				echo "<br>".convDateTime($val["end"])."</strong>";

			}else{
				echo "<td>&nbsp;";
				echo "</td>";
				echo "<td  style='text-align:center;'><span style='color:#aaaaaa;'>".convDateTime($val["begin"])."</span>";

			}
			echo "</td></tr>";
		}

	}

	echo "</table></div>";
}







?>
