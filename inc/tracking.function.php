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


// FUNCTIONS Tracking System


function titleTracking(){
	global  $LANG,$CFG_GLPI;

	displayTitle($CFG_GLPI["root_doc"]."/pics/suivi-intervention.png",$LANG["tracking"][0],$LANG["tracking"][0]);

}

/**
 * Print "onglets" (on the top of items forms)
 *
 * Print "onglets" for a better navigation.
 *
 *@param $target filename : The php file to display then
 *
 *@return nothing (diplays)
 *
 **/
function showTrackingOnglets($target){
	global $LANG,$CFG_GLPI;

	if (preg_match("/\?ID=([0-9]+)/",$target,$ereg)){
		$ID=$ereg[1];

		$job=new Job();
		$job->getFromDB($ID);

		echo "<div id='barre_onglets'><ul id='onglet'>";

		if ($_SESSION["glpiactiveprofile"]["interface"]=="central"){
			echo "<li class='actif'><a href=\"".$CFG_GLPI["root_doc"]."/front/tracking.form.php?ID=$ID&amp;onglet=1\">".$LANG["job"][38]." $ID</a></li>";

			if (haveRight("show_ticket","1"))
				display_plugin_headings($target,TRACKING_TYPE,"","");

			echo "<li class='invisible'>&nbsp;</li>";

			// admin yes  
			if (haveRight("comment_ticket","1")||haveRight("comment_all_ticket","1")||$job->fields["assign"]==$_SESSION["glpiID"]){
				echo "<li onClick=\"showAddFollowup(); Effect.Appear('viewfollowup');\" id='addfollowup'><a href='#'>".$LANG["job"][29]."</a></li>";
			}


			// Post-only could'nt see other item  but other user yes 
			if (haveRight("show_ticket","1")){
				echo "<li class='invisible'>&nbsp;</li>";

				$next=getNextItem("glpi_tracking",$ID);
				$prev=getPreviousItem("glpi_tracking",$ID);
				$cleantarget=preg_replace("/\?ID=([0-9]+)/","",$target);
				if ($prev>0) echo "<li><a href='$cleantarget?ID=$prev'><img src=\"".$CFG_GLPI["root_doc"]."/pics/left.png\" alt='".$LANG["buttons"][12]."' title='".$LANG["buttons"][12]."'></a></li>";
				if ($next>0) echo "<li><a href='$cleantarget?ID=$next'><img src=\"".$CFG_GLPI["root_doc"]."/pics/right.png\" alt='".$LANG["buttons"][11]."' title='".$LANG["buttons"][11]."'></a></li>";
			}
		}elseif (haveRight("comment_ticket","1")){

			// Postonly could post followup in helpdesk area	
			echo "<li class='actif'><span style='float: left;display: block;color: #666;text-decoration: none;padding: 3px;'><a href=\"".$CFG_GLPI["root_doc"]."/front/helpdesk.public.php?show=user&amp;ID=$ID\">".$LANG["job"][38]." $ID</span></a></li>";

			if (!ereg("old_",$job->fields["status"])&&$job->fields["author"]==$_SESSION["glpiID"]){
				echo "<li class='invisible'>&nbsp;</li>";

				echo "<li onClick=\"showAddFollowup(); Effect.Appear('viewfollowup');\" id='addfollowup'><a href='#'>".$LANG["job"][29]."</span></a></li>";
			}
		}

	}

	echo "</ul></div>";	 

}





function commonTrackingListHeader($output_type=HTML_OUTPUT,$target="",$parameters="",$sort="",$order=""){
	global $LANG,$CFG_GLPI;

	// New Line for Header Items Line
	echo displaySearchNewLine($output_type);
	// $show_sort if 
	$header_num=1;

	$items=array(
			$LANG["joblist"][0]=>"glpi_tracking.status",
			$LANG["common"][27]=>"glpi_tracking.date",
			$LANG["joblist"][2]=>"glpi_tracking.priority",
			$LANG["common"][37]=>"author.name",
			$LANG["joblist"][4]=>"assign.name",
			$LANG["common"][1]=>"glpi_tracking.device_type,glpi_tracking.computer",
			$LANG["common"][36]=>"glpi_dropdown_tracking_category.completename",
			$LANG["joblist"][6]=>"glpi_tracking.contents",
		    );

	foreach ($items as $key => $val){
		$issort=0;
		$link="";
		if ($sort==$val) $issort=1;
		$link=$target."?".$parameters."&amp;order=".($order=="ASC"?"DESC":"ASC")."&amp;sort=$val";
		if (ereg("helpdesk",$target)){
			$link.="&amp;show=user";
		}
		echo displaySearchHeaderItem($output_type,$key,$header_num,$link,$issort,$order);
	}

	echo displaySearchHeaderItem($output_type,"",$header_num,"",0,$order);

	// End Line for column headers		
	echo displaySearchEndLine($output_type);
}

function getTrackingOrderPrefs ($ID) {
	// Returns users preference settings for job tracking
	// Currently only supports sort order


	if($_SESSION["glpitracking_order"])
	{
		return "DESC";
	} 
	else
	{
		return "ASC";
	}

}

function showCentralJobList($target,$start,$status="process") {
	// Lists all Jobs, needs $show which can have keywords 
	// (individual, unassigned) and $contains with search terms.
	// If $item is given, only jobs for a particular machine
	// are listed.

	global $DB,$CFG_GLPI, $LANG;

	if (!haveRight("show_ticket","1")) return false;

	if($status=="waiting"){ // on affiche les tickets en attente
		$query = "SELECT ID FROM glpi_tracking WHERE (assign = '".$_SESSION["glpiID"]."') AND (status ='waiting' ) ORDER BY date ".getTrackingOrderPrefs($_SESSION["glpiID"]);

		$title=$LANG["central"][11];

	}else{ // on affiche les tickets planifiés ou assignés à glpiID

		$query = "SELECT ID FROM glpi_tracking WHERE (assign = '".$_SESSION["glpiID"]."') AND (status ='plan' OR status = 'assign') ORDER BY date ".getTrackingOrderPrefs($_SESSION["glpiID"]);

		$title=$LANG["central"][9];
	}

	$lim_query = " LIMIT ".$start.",".$CFG_GLPI["list_limit"]."";	

	$result = $DB->query($query);
	$numrows = $DB->numrows($result);

	$query .= $lim_query;

	$result = $DB->query($query);
	$i = 0;
	$number = $DB->numrows($result);

	if ($number > 0) {
		echo "<table class='tab_cadrehov'>";

		echo "<tr><th colspan='5'><b><a href=\"".$CFG_GLPI["root_doc"]."/front/tracking.php?assign=".$_SESSION["glpiID"]."&amp;status=$status&amp;reset=reset_before\">".$title."</a></b></th></tr>";
		echo "<tr><th></th>";
		echo "<th>".$LANG["common"][37]."</th>";
		echo "<th>".$LANG["common"][1]."</th>";
		echo "<th colspan='2'>".$LANG["joblist"][6]."</th></tr>";
		while ($i < $number) {
			$ID = $DB->result($result, $i, "ID");
			showJobVeryShort($ID);
			$i++;
		}
		echo "</table>";
	}
	else
	{
		echo "<table class='tab_cadrehov'>";
		echo "<tr><th>".$title."</th></tr>";

		echo "</table>";
	}
}

function showCentralJobCount(){
	// show a tab with count of jobs in the central and give link	

	global $DB,$CFG_GLPI, $LANG;

	if (!haveRight("show_ticket","1")) return false;	

	$query="SELECT status, COUNT(*) AS COUNT FROM glpi_tracking GROUP BY status";



	$result = $DB->query($query);


	$status=array("new"=>0, "assign"=>0, "plan"=>0, "waiting"=>0);

	if ($DB->numrows($result)>0)
		while ($data=$DB->fetch_assoc($result)){

			$status[$data["status"]]=$data["COUNT"];
		}

	echo "<div align='center'><table class='tab_cadrehov' style='text-align:center'>";

	echo "<tr><th colspan='2'><b><a href=\"".$CFG_GLPI["root_doc"]."/front/tracking.php?status=process&amp;reset=reset_before\">".$LANG["tracking"][0]."</a></b></th></tr>";
	echo "<tr><th ><b>".$LANG["tracking"][28]."</b></th><th>".$LANG["tracking"][29]."</th></tr>";
	echo "<tr class='tab_bg_2'>";
	echo "<td><a href=\"".$CFG_GLPI["root_doc"]."/front/tracking.php?status=new&amp;reset=reset_before\">".$LANG["tracking"][30]."</a> </td>";
	echo "<td>".$status["new"]."</td></tr>";
	echo "<tr class='tab_bg_2'>";
	echo "<td><a href=\"".$CFG_GLPI["root_doc"]."/front/tracking.php?status=assign&amp;reset=reset_before\">".$LANG["tracking"][31]."</a></td>";
	echo "<td>".$status["assign"]."</td></tr>";
	echo "<tr class='tab_bg_2'>";
	echo "<td><a href=\"".$CFG_GLPI["root_doc"]."/front/tracking.php?status=plan&amp;reset=reset_before\">".$LANG["tracking"][32]."</a></td>";
	echo "<td>".$status["plan"]."</td></tr>";
	echo "<tr class='tab_bg_2'>";
	echo "<td><a href=\"".$CFG_GLPI["root_doc"]."/front/tracking.php?status=waiting&amp;reset=reset_before\">".$LANG["tracking"][33]."</a></td>";
	echo "<td>".$status["waiting"]."</td></tr>";


	echo "</table></div><br>";


}




function showOldJobListForItem($username,$item_type,$item) {
	// $item is required
	// affiche toutes les vielles intervention pour un $item donn� 


	global $DB,$CFG_GLPI, $LANG;

	if (!haveRight("show_ticket","1")) return false;
	$candelete=haveRight("delete_ticket","1");

	$where = "(status = 'old_done' OR status = 'old_notdone')";	
	$query = "SELECT ".getCommonSelectForTrackingSearch()." FROM glpi_tracking ".getCommonLeftJoinForTrackingSearch()." WHERE $where and (device_type = '$item_type' and computer = '$item') ORDER BY date ".getTrackingOrderPrefs($_SESSION["glpiID"]);


	$result = $DB->query($query);

	$i = 0;
	$number = $DB->numrows($result);

	if ($number > 0)
	{
		echo "<div align='center'>&nbsp;<table class='tab_cadre_fixe'>";
		echo "<tr><th colspan=9>".$number." ".$LANG["job"][18]."  ".$LANG["job"][17]."";
		if ($number > 1) { echo "s"; }
		echo " ".$LANG["job"][16].":&nbsp;";
		echo "<a href='".$CFG_GLPI["root_doc"]."/front/tracking.php?reset=reset_before&amp;status=all&amp;item=$item&amp;type=$item_type'>".$LANG["buttons"][23]."</a>";

		echo "</th></tr>";

		commonTrackingListHeader();

		while ($data=$DB->fetch_assoc($result))
		{
			showJobShort($data, 0);
			$i++;
		}

		echo "</table></div>";
	} 
	else
	{
		echo "<br><div align='center'>";
		echo "<table class='tab_cadre_fixe'>";
		echo "<tr><th>".$LANG["joblist"][22]."</th></tr>";
		echo "</table>";
		echo "</div><br>";
	}

}

function showJobListForItem($username,$item_type,$item) {
	// $item is required
	//affiche toutes les vielles intervention pour un $item donn� 

	global $DB,$CFG_GLPI, $LANG;

	if (!haveRight("show_ticket","1")) return false;


	$where = "(status = 'new' OR status= 'assign' OR status='plan' OR status='waiting')";	
	$query = "SELECT ".getCommonSelectForTrackingSearch()." FROM glpi_tracking ".getCommonLeftJoinForTrackingSearch()." WHERE $where and (computer = '$item' and device_type= '$item_type') ORDER BY date ".getTrackingOrderPrefs($_SESSION["glpiID"]);


	$result = $DB->query($query);

	$i = 0;
	$number = $DB->numrows($result);

	if ($number > 0)
	{
		echo "<div align='center'>&nbsp;<table class='tab_cadre_fixe'>";
		echo "<tr><th colspan='9'>".$number." ".$LANG["job"][17]."";
		if ($number > 1) { echo "s"; }
		echo " ".$LANG["job"][16].":&nbsp;";
		echo "<a href='".$CFG_GLPI["root_doc"]."/front/tracking.php?reset=reset_before&amp;status=all&amp;item=$item&amp;type=$item_type'>".$LANG["buttons"][23]."</a>";
		echo "</th></tr>";

		if ($item)
		{
			echo "<tr><td align='center' class='tab_bg_2' colspan='9'>";
			echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/helpdesk.php?computer=$item&amp;device_type=$item_type\"><strong>";
			echo $LANG["joblist"][7];
			echo "</strong></a>";
			echo "</td></tr>";
		}

		commonTrackingListHeader();

		while ($data=$DB->fetch_assoc($result))
		{
			showJobShort($data, 0);
			$i++;
		}
		echo "</table></div>";
	} 
	else
	{
		echo "<br><div align='center'>";
		echo "<table class='tab_cadre_fixe'>";
		echo "<tr><th>".$LANG["joblist"][8]."</th></tr>";

		if ($item)
		{

			echo "<tr><td align='center' class='tab_bg_2' colspan='8'>";
			echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/helpdesk.php?computer=$item&amp;device_type=$item_type\"><strong>";
			echo $LANG["joblist"][7];
			echo "</strong></a>";
			echo "</td></tr>";
		}
		echo "</table>";
		echo "</div><br>";
	}
}


function showJobShort($data, $followups,$output_type=HTML_OUTPUT,$row_num=0) {
	// Prints a job in short form
	// Should be called in a <table>-segment
	// Print links or not in case of user view

	global $CFG_GLPI, $LANG;

	// Make new job object and fill it from database, if success, print it
	$job = new Job;
	$job->fields['ID'] = $data['ID'];
	$candelete=haveRight("delete_ticket","1");
	$canupdate=haveRight("update_ticket","1");
	$viewusers=haveRight("user","r");
	$align="align='center'";
	$align_desc="align='left'";
	if ($followups) { 
		$align.=" valign='top' ";
		$align_desc.=" valign='top' ";
	}
	if ($data["ID"])
	{
		$item_num=1;
		$bgcolor=$CFG_GLPI["priority_".$data["priority"]];

		echo displaySearchNewLine($output_type,$row_num%2);



		// First column
		$first_col= "ID: ".$data["ID"];
		if ($output_type==HTML_OUTPUT)
			$first_col.="<br><img src=\"".$CFG_GLPI["root_doc"]."/pics/".$data["status"].".png\" alt='".getStatusName($data["status"])."' title='".getStatusName($data["status"])."'>";
		else $first_col.=" - ".getStatusName($data["status"]);
		if (($candelete||$canupdate)&&$output_type==HTML_OUTPUT){
			$sel="";
			if (isset($_GET["select"])&&$_GET["select"]=="all") $sel="checked";
			$first_col.="&nbsp;<input type='checkbox' name='item[".$data["ID"]."]' value='1' $sel>";
		}


		echo displaySearchItem($output_type,$first_col,$item_num,$row_num,0,$align);

		// Second column
		$second_col="";	
		if (!ereg("old_",$data["status"]))
		{
			$second_col.="<small>".$LANG["joblist"][11].":";
			if ($output_type==HTML_OUTPUT) $second_col.="<br>";
			$second_col.= "&nbsp;".convDateTime($data["date"])."</small>";
		}
		else
		{
			$second_col.="<small>".$LANG["joblist"][11].":";
			if ($output_type==HTML_OUTPUT) $second_col.="<br>";
			$second_col.="&nbsp;".convDateTime($data["date"]);
			$second_col.="<br>";
			$second_col.="<i>".$LANG["joblist"][12].":";
			if ($output_type==HTML_OUTPUT) $second_col.="<br>";
			$second_col.="&nbsp;".convDateTime($data["closedate"])."</i>";
			$second_col.="<br>";
			if ($data["realtime"]>0) $second_col.=$LANG["job"][20].": ";
			if ($output_type==HTML_OUTPUT) $second_col.="<br>";
			$second_col.="&nbsp;".getRealtime($data["realtime"]);
			$second_col.="</small>";
		}

		echo displaySearchItem($output_type,$second_col,$item_num,$row_num,0,$align." width=130");

		// Third Column
		echo displaySearchItem($output_type,"<strong>".getPriorityName($data["priority"])."</strong>",$item_num,$row_num,0,"$align bgcolor='$bgcolor'");

		// Fourth Column

		if ($viewusers){
			$fourth_col="<strong>".formatUserName($data['authorID'],$data['authorname'],$data['authorrealname'],$data['authorfirstname'],1)."</strong>";
		} else {
			$fourth_col="<strong>".formatUserName($data['authorID'],$data['authorname'],$data['authorrealname'],$data['authorfirstname'],0)."</strong>";
		}

		if ($data["FK_group"])
			$fourth_col.="<br>".$data["groupname"];

		echo displaySearchItem($output_type,$fourth_col,$item_num,$row_num,0,$align);

		// Fifth column
		$fifth_col="";
		if ($data["assign"]>0){
			if ($viewusers)
				$fifth_col.=formatUserName($data['assignID'],$data['assignname'],$data['assignrealname'],$data['assignfirstname'],1);
			else
				$fifth_col.="<strong>".formatUserName($data['assignID'],$data['assignname'],$data['assignrealname'],$data['assignfirstname'],0)."</strong>";
		}

		if ($data["assign_ent"]>0){
			if (!empty($fifth_col)){
				$fifth_col.="<br>";
			}
			if ($viewusers)
				$fifth_col.=getAssignName($data["assign_ent"],ENTERPRISE_TYPE,1);
			else
				$fifth_col.="<strong>".getAssignName($data["assign_ent"],ENTERPRISE_TYPE)."</strong>";

		}
		echo displaySearchItem($output_type,$fifth_col,$item_num,$row_num,0,$align);

		$ci=new CommonItem();
		$ci->getFromDB($data["device_type"],$data["computer"]);
		// Sixth Colum
		$sixth_col="";

		$sixth_col.=$ci->getType();
		if ($data["device_type"]>0&&$data["computer"]>0){
			$sixth_col.="<br><strong>";
			if (haveTypeRight($data["device_type"],"r")){
				$sixth_col.=$ci->getLink($output_type==HTML_OUTPUT);
			} else {
				$sixth_col.=$ci->getNameID();
			}
			$sixth_col.="</strong>";
		} 

		echo displaySearchItem($output_type,$sixth_col,$item_num,$row_num,$ci->getField("deleted"),$align);

		// Seventh column
		echo displaySearchItem($output_type,"<strong>".$data["catname"]."</strong>",$item_num,$row_num,0,$align);

		// Eigth column

		$stripped_content=resume_text($data["contents"],400);
		if ($followups){$stripped_content=resume_text($data["contents"],$CFG_GLPI["cut"]);}

		$eigth_column="<strong>".$stripped_content."</strong>";
		if ($followups&&$output_type==HTML_OUTPUT)
		{
			$eigth_column.=showFollowupsShort($data["ID"]);
		}


		echo displaySearchItem($output_type,$eigth_column,$item_num,$row_num,0,$align_desc."width='300'");


		// Nineth column
		$nineth_column="";
		// Job Controls

		if ($_SESSION["glpiactiveprofile"]["interface"]=="central"){
			if (!haveRight("show_ticket","1")&&$data["author"]!=$_SESSION["glpiID"]&&$data["assign"]!=$_SESSION["glpiID"]&&(!haveRight("show_group_ticket",1)||!in_array($data["FK_group"],$_SESSION["glpigroups"]))) 
				$nineth_column.="&nbsp;";
			else 
				$nineth_column.="<a href=\"".$CFG_GLPI["root_doc"]."/front/tracking.form.php?ID=".$data["ID"]."\"><strong>".$LANG["joblist"][13]."</strong></a>&nbsp;(".$job->numberOfFollowups().")";
		}
		else
			$nineth_column.="<a href=\"".$CFG_GLPI["root_doc"]."/front/helpdesk.public.php?show=user&amp;ID=".$data["ID"]."\">".$LANG["joblist"][13]."</a>&nbsp;(".$job->numberOfFollowups(haveRight("show_full_ticket","1")).")";

		echo displaySearchItem($output_type,$nineth_column,$item_num,$row_num,0,$align." width='40'");

		// Finish Line
		echo displaySearchEndLine($output_type);
	}
	else
	{
		echo "<tr class='tab_bg_2'><td colspan='6' ><i>".$LANG["joblist"][16]."</i></td></tr>";
	}
}

function showJobVeryShort($ID) {
	// Prints a job in short form
	// Should be called in a <table>-segment
	// Print links or not in case of user view

	global $CFG_GLPI, $LANG;

	// Make new job object and fill it from database, if success, print it
	$job = new Job;
	$viewusers=haveRight("user","r");
	if ($job->getfromDBwithData($ID,0))
	{
		$bgcolor=$CFG_GLPI["priority_".$job->fields["priority"]];
		
		echo "<tr class='tab_bg_2'>";
		echo "<td align='center' bgcolor='$bgcolor' >ID: ".$job->fields["ID"]."</td>";
		echo "<td align='center'>";

		if ($viewusers)
			echo "<strong>".$job->getAuthorName(1)."</strong>";
		else
			echo "<strong>".$job->getAuthorName()."</strong>";

		if ($job->fields["FK_group"])
			echo "<br>".getDropdownName("glpi_groups",$job->fields["FK_group"]);


		echo "</td>";

		if (haveTypeRight($job->fields["device_type"],"r")){
			echo "<td align='center' ";
			if ($job->hardwaredatas->getField("deleted")){
				echo "class='tab_bg_1_2'";
			}
			echo ">";
			echo $job->hardwaredatas->getType()."<br>";
			echo "<strong>";
			echo $job->hardwaredatas->getLink();
			echo "</strong>";

			echo "</td>";
		}
		else {
			echo "<td  align='center' >".$job->hardwaredatas->getType()."<br><strong>".$job->hardwaredatas->getNameID()."</strong></td>";
		}
		$stripped_content =resume_text($job->fields["contents"],100);
		echo "<td ><strong>".$stripped_content."</strong>";
		echo "</td>";

		// Job Controls
		echo "<td width='40' align='center'>";

		if ($_SESSION["glpiactiveprofile"]["interface"]=="central")
			echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/tracking.form.php?ID=".$job->fields["ID"]."\"><strong>".$LANG["joblist"][13]."</strong></a>&nbsp;(".$job->numberOfFollowups().")&nbsp;<br>";
		else
			echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/helpdesk.public.php?show=user&amp;ID=".$job->fields["ID"]."\">".$LANG["joblist"][13]."</a>&nbsp;(".$job->numberOfFollowups().")&nbsp;<br>";

		// Finish Line
		echo "</tr>";
	}
	else
	{
		echo "<tr class='tab_bg_2'><td colspan='6' ><i>".$LANG["joblist"][16]."</i></td></tr>";
	}
}

function addFormTracking ($device_type=0,$ID=0,$author,$assign,$target,$error,$searchauthor='') {
	// Prints a nice form to add jobs

	global $CFG_GLPI, $LANG,$CFG_GLPI,$REFERER,$DB;
	if (!haveRight("create_ticket","1")) return false;

	if (!empty($error)) {
		echo "<div align='center'><strong>$error</strong></div>";
	}

	displayTitle("","","",array($REFERER=>$LANG["buttons"][13]));

	echo "<br><form name='form_ticket' method='post' action='$target' enctype=\"multipart/form-data\">";
	echo "<div align='center'>";

	//	if ($device_type!=0){
	echo "<input type='hidden' name='_referer' value='$REFERER'>";
	//	}	
	echo "<table class='tab_cadre'><tr><th><a href='$target'>".$LANG["buttons"][16]."</a></th><th colspan='3'>".$LANG["job"][13].": <br>";
	if ($device_type!=0){
		$m=new CommonItem;
		$m->getfromDB($device_type,$ID);
		echo $m->getType()." - ".$m->getNameID();
	}
	echo "<input type='hidden' name='computer' value=\"$ID\">";
	echo "<input type='hidden' name='device_type' value=\"$device_type\">";

	echo "</th></tr>";

	$author_rand=0;
	if (haveRight("update_ticket","1")){
		echo "<tr class='tab_bg_2' align='center'><td>".$LANG["common"][37].":</td>";
		echo "<td align='center' colspan='3'>";
		$author_rand=dropdownAllUsers("author",$author,1,$_SESSION["glpiactive_entity"],1);

		echo "</td></tr>";
	} 
	
	if ($device_type==0&&$_SESSION["glpiactiveprofile"]["helpdesk_hardware"]!=0){
		echo "<tr class='tab_bg_2'>";
		echo "<td align='center'>".$LANG["help"][24].": </td>";
		echo "<td align='center' colspan='3'>";
		dropdownMyDevices($_SESSION["glpiID"]);
		dropdownTrackingAllDevices("device_type",$device_type,0,$_SESSION["glpiactive_entity"]);
		echo "</td></tr>";
	} 


	if (haveRight("update_ticket","1")){
		echo "<tr class='tab_bg_2'><td align='center'>".$LANG["common"][27].":</td>";
		echo "<td align='center' class='tab_bg_2'>";
		showCalendarForm("form_ticket","date",date("Y-m-d H:i"),0,1);	
		echo "</td>";

		echo "<td align='center'>".$LANG["job"][44].":</td>";
		echo "<td align='center'>";
		$request_type=1;
		if (isset($_POST["request_type"])) $request_type=$_POST["request_type"];
		dropdownRequestType("request_type",$request_type);
		echo "</td></tr>";
	}


	// Need comment right to add a followup with the realtime
	if (haveRight("comment_all_ticket","1")){
		echo "<tr  class='tab_bg_2'>";
		echo "<td align='center'>";
		echo $LANG["job"][20].":</td>";
		echo "<td align='center' colspan='3'>";
		$hour=0;
		if (isset($_POST["hour"])) $hour=$_POST["hour"];
		dropdownInteger('hour',$hour,0,100);

		echo $LANG["job"][21]."&nbsp;&nbsp;";
		$min=0;
		if (isset($_POST["minute"])) $min=$_POST["minute"];
		dropdownInteger('minute',$min,0,59);

		echo $LANG["job"][22]."&nbsp;&nbsp;";
		echo "</td></tr>";
	}


	echo "<tr class='tab_bg_2'>";

	echo "<td class='tab_bg_2' align='center'>".$LANG["joblist"][2].":</td>";
	echo "<td align='center' class='tab_bg_2'>";
	$priority=3;
	if (isset($_POST["priority"])) $priority=$_POST["priority"];
	dropdownPriority("priority",$priority);
	echo "</td>";

	echo "<td>".$LANG["common"][36].":</td>";
	echo "<td align='center'>";
	$category=0;
	if (isset($_POST["category"])) $category=$_POST["category"];
	dropdownValue("glpi_dropdown_tracking_category","category",$category);
	echo "</td></tr>";


	if (haveRight("update_ticket","1")||haveRight("assign_ticket","1")){
		echo "<tr class='tab_bg_2' align='center'><td>".$LANG["buttons"][3].":</td>";
		echo "<td colspan='3'>";
		dropdownUsers("assign",$assign,"own_ticket",0,1,$_SESSION["glpiactive_entity"]);
		echo "</td></tr>";
	} else if (haveRight("steal_ticket","1")) {
		echo "<tr class='tab_bg_2' align='center'><td>".$LANG["buttons"][3].":</td>";
		echo "<td colspan='3'>";
		dropdownUsers("assign",$assign,"ID",0,1,$_SESSION["glpiactive_entity"]);
		echo "</td></tr>";
	}




	if($CFG_GLPI["mailing"] == 1){

		$query="SELECT email from glpi_users WHERE ID='$author'";
		
		$result=$DB->query($query);
		$email="";
		if ($result&&$DB->numrows($result))
			$email=$DB->result($result,0,"email");

		echo "<tr class='tab_bg_1'>";
		echo "<td align='center'>".$LANG["help"][8].":</td>";
		echo "<td align='center'>";
		dropdownYesNo('emailupdates',1);
		echo "</td>";
		echo "<td align='center'>".$LANG["help"][11].":</td>";
		echo "<td><span id='uemail_result'>";
		echo "<input type='text' size='30' name='uemail' value='$email'>";
		echo "</span>";

		echo "</td></tr>";

	}

	echo "<tr><th colspan='4' align='center'>".$LANG["job"][11].":";
	echo "</th></tr>";

	echo "<tr class='tab_bg_1'><td colspan='4' align='center'><textarea cols='80' rows='8'  name='contents'></textarea></td></tr>";

	$max_size=return_bytes_from_ini_vars(ini_get("upload_max_filesize"));
	$max_size/=1024*1024;
	$max_size=round($max_size,1);

	echo "<tr class='tab_bg_1'><td>".$LANG["document"][2]." (".$max_size." ".$LANG["common"][45]."):	";
	echo "<img src=\"".$CFG_GLPI["root_doc"]."/pics/aide.png\" style='cursor:pointer;' alt=\"aide\"onClick=\"window.open('".$CFG_GLPI["root_doc"]."/front/typedoc.list.php','Help','scrollbars=1,resizable=1,width=1000,height=500')\">";
	echo "</td>";
	echo "<td colspan='3'><input type='file' name='filename' value=\"\" size='25'></td>";
	echo "</tr>";

	echo "<tr class='tab_bg_1'><td colspan='2' align='center'>";
	echo "<input type='submit' name='add' value=\"".$LANG["buttons"][2]."\" class='submit'>";
	echo "</td><td colspan='2' align='center'>";
	if (haveRight("comment_all_ticket","1"))
		echo "<input type='submit' name='add_close' value=\"".$LANG["buttons"][26]."\" class='submit'>";
	else echo "&nbsp;";
	echo "</td></tr>";

	if (haveRight("comment_all_ticket","1")){
		echo "<tr><th colspan='4' align='center'>".$LANG["job"][45].":</th></tr>";
		echo "<tr class='tab_bg_1'><td colspan='4' align='center'><textarea cols='80' rows='8'  name='_followup'></textarea></td></tr>";
	}

	echo "</table></div></form>";

}

function getRealtime($realtime){
	global $LANG;	
	$output="";
	$hour=floor($realtime);
	if ($hour>0) $output.=$hour." ".$LANG["job"][21]." ";
	$output.=round((($realtime-floor($realtime))*60))." ".$LANG["job"][22];
	return $output;
}

function searchSimpleFormTracking($target,$status="all",$group=-1){

global $CFG_GLPI,  $LANG;


	echo "<div align='center' >";

	echo "<form method='get' name=\"form\" action=\"".$target."\">";
	echo "<table class='tab_cadre_fixe'>";
	echo "<tr class='tab_bg_1'>";
	echo "<td colspan='1' align='center'>".$LANG["joblist"][0].":&nbsp;";
	echo "<select name='status'>";
	echo "<option value='new' ".($status=="new"?" selected ":"").">".$LANG["joblist"][9]."</option>";
	echo "<option value='assign' ".($status=="assign"?" selected ":"").">".$LANG["joblist"][18]."</option>";
	echo "<option value='plan' ".($status=="plan"?" selected ":"").">".$LANG["joblist"][19]."</option>";
	echo "<option value='waiting' ".($status=="waiting"?" selected ":"").">".$LANG["joblist"][26]."</option>";
	echo "<option value='old_done' ".($status=="old_done"?" selected ":"").">".$LANG["joblist"][10]."</option>";
	echo "<option value='old_notdone' ".($status=="old_notdone"?" selected ":"").">".$LANG["joblist"][17]."</option>";
	echo "<option value='notold' ".($status=="notold"?"selected":"").">".$LANG["joblist"][24]."</option>";	
	echo "<option value='process' ".($status=="process"?"selected":"").">".$LANG["joblist"][21]."</option>";
	echo "<option value='old' ".($status=="old"?"selected":"").">".$LANG["joblist"][25]."</option>";	
	echo "<option value='all' ".($status=="all"?"selected":"").">".$LANG["joblist"][20]."</option>";
	echo "</select></td>";

	if (haveRight("show_group_ticket",1)){
		echo "<td align='center'>";
		echo "<select name='group'>";
		echo "<option value='-1' ".($group==-1?" selected ":"").">".$LANG["search"][7]."</option>";
		echo "<option value='0' ".($group==0?" selected ":"").">".$LANG["joblist"][1]."</option>";
		echo "</select>";
		echo "</td>";
	}

	echo "<td align='center' colspan='1'><input type='submit' value=\"".$LANG["buttons"][0]."\" class='submit'></td>";
	echo "</tr>";
	echo "</table>";
	echo "<input type='hidden' name='start' value='0'>";
	// helpdesk case
	if (ereg("helpdesk.public.php",$target)){
		echo "<input type='hidden' name='show' value='user'>";
	}
	echo "</form>";
	echo "</div>";

}

function searchFormTracking($extended=0,$target,$start="",$status="new",$author=0,$group=0,$assign=0,$assign_ent=0,$category=0,$priority=0,$request_type=0,$item=0,$type=0,$showfollowups="",$field2="",$contains2="",$field="",$contains="",$date1="",$date2="",$computers_search="",$enddate1="",$enddate2="") {
	// Print Search Form

	global $CFG_GLPI,  $LANG;

	if (!haveRight("show_ticket","1")) {
		if ($author==0&&$assign==0)
			if (!haveRight("own_ticket","1"))
				$author=$_SESSION["glpiID"];
			else $assign=$_SESSION["glpiID"];
	}

	if ($extended==1){
		$option["comp.ID"]				= $LANG["common"][2];
		$option["comp.name"]				= $LANG["common"][16];
		$option["glpi_dropdown_locations.name"]		= $LANG["common"][15];
		$option["glpi_type_computers.name"]		= $LANG["common"][17];
		$option["glpi_dropdown_model.name"]		= $LANG["common"][22];
		$option["glpi_dropdown_os.name"]		= $LANG["computers"][9];
		$option["processor.designation"]		= $LANG["computers"][21];
		$option["comp.serial"]				= $LANG["common"][19];
		$option["comp.otherserial"]			= $LANG["common"][20];
		$option["ram.designation"]			= $LANG["computers"][23];
		$option["iface.designation"]			= $LANG["setup"][9];
		$option["sndcard.designation"]			= $LANG["devices"][7];
		$option["gfxcard.designation"]			= $LANG["devices"][2];
		$option["moboard.designation"]			= $LANG["devices"][5];
		$option["hdd.designation"]			= $LANG["computers"][36];
		$option["comp.comments"]			= $LANG["common"][25];
		$option["comp.contact"]				= $LANG["common"][18];
		$option["comp.contact_num"]		        = $LANG["common"][21];
		$option["comp.date_mod"]			= $LANG["common"][26];
		$option["glpi_networking_ports.ifaddr"] 	= $LANG["networking"][14];
		$option["glpi_networking_ports.ifmac"] 		= $LANG["networking"][15];
		$option["glpi_dropdown_netpoint.name"]		= $LANG["networking"][51];
		$option["glpi_enterprises.name"]		= $LANG["common"][5];
		$option["resptech.name"]			=$LANG["common"][10];
	}
	echo "<form method='get' name=\"form\" action=\"".$target."\">";


	echo "<div align='center' >";

	echo "<table class='tab_cadre_fixe'>";


	echo "<tr><th colspan='6' style='vertical-align:middle' ><div style='position: relative'><span><strong>".$LANG["search"][0]."</strong></span>";
	if ($extended)
		echo "<span style='  position:absolute; right:0; margin-right:5px; font-size:10px;'><a href='$target?extended=0'><img src=\"".$CFG_GLPI["root_doc"]."/pics/deplier_up.png\" alt=''>".$LANG["buttons"][36]."</a></span>";
	else echo "<span  style='  position:absolute; right:0; margin-right:5px; font-size:10px;'><a href='$target?extended=1'><img src=\"".$CFG_GLPI["root_doc"]."/pics/deplier_down.png\" alt=''>".$LANG["buttons"][35]."</a></span>";
	echo "</div></th></tr>";



	echo "<tr class='tab_bg_1'>";
	echo "<td colspan='1' align='center'>".$LANG["joblist"][0].":<br>";
	echo "<select name='status'>";
	echo "<option value='new' ".($status=="new"?" selected ":"").">".$LANG["joblist"][9]."</option>";
	echo "<option value='assign' ".($status=="assign"?" selected ":"").">".$LANG["joblist"][18]."</option>";
	echo "<option value='plan' ".($status=="plan"?" selected ":"").">".$LANG["joblist"][19]."</option>";
	echo "<option value='waiting' ".($status=="waiting"?" selected ":"").">".$LANG["joblist"][26]."</option>";
	echo "<option value='old_done' ".($status=="old_done"?" selected ":"").">".$LANG["joblist"][10]."</option>";
	echo "<option value='old_notdone' ".($status=="old_notdone"?" selected ":"").">".$LANG["joblist"][17]."</option>";
	echo "<option value='notold' ".($status=="notold"?"selected":"").">".$LANG["joblist"][24]."</option>";	
	echo "<option value='process' ".($status=="process"?"selected":"").">".$LANG["joblist"][21]."</option>";
	echo "<option value='old' ".($status=="old"?"selected":"").">".$LANG["joblist"][25]."</option>";	
	echo "<option value='all' ".($status=="all"?"selected":"").">".$LANG["joblist"][20]."</option>";
	echo "</select></td>";
	echo "<td  colspan='1' align='center'>".$LANG["common"][37].":<br>";
	dropdownUsersTracking("author",$author,"author");
	echo "</td>";

	echo "<td  colspan='1' align='center'>".$LANG["common"][35].":<br>";
	dropdownValue("glpi_groups","group",$group);
	echo "</td>";

	echo "<td colspan='1' align='center'>".$LANG["joblist"][2].":<br>";
	dropdownPriority("priority",$priority,1);
	echo "</td>";

	echo "<td colspan='2' align='center'>".$LANG["common"][36].":<br>";
	dropdownValue("glpi_dropdown_tracking_category","category",$category);
	echo "</td>";

	echo "</tr>";
	echo "<tr class='tab_bg_1'>";

	echo "<td align='center' colspan='2'>";
	echo "<table border='0'><tr><td>".$LANG["common"][1].":</td><td>";
	dropdownAllItems("item",$type,$item);
	echo "</td></tr></table>";
	echo "</td>";
	echo "<td colspan='3' align='center'>".$LANG["job"][5].":<br>";

	echo $LANG["job"][27].":&nbsp;";
	dropdownUsers("assign",$assign,"own_ticket",1);
	echo "<br>";
	echo $LANG["job"][28].":&nbsp;";
	dropdownValue("glpi_enterprises","assign_ent",$assign_ent);

	echo "</td>";
	echo "<td align='center'>".$LANG["job"][44].":<br>";
	dropdownRequestType("request_type",$request_type);
	echo "</td>";
	echo "</tr>";

	if ($extended){
		echo "<tr class='tab_bg_1'>";
		echo "<td align='center' colspan='6'>";
		$selected="";
		if ($computers_search) $selected="checked";
		echo "<input type='checkbox' name='only_computers' value='1' $selected>".$LANG["reports"][24].":&nbsp;";

		echo "<input type='text' size='15' name=\"contains\" value=\"". stripslashes($contains) ."\" >";
		echo "&nbsp;";
		echo $LANG["search"][10]."&nbsp;";

		echo "<select name='field' size='1'>";
		echo "<option value='all' ";
		if($field == "all") echo "selected";
		echo ">".$LANG["search"][7]."</option>";
		reset($option);
		foreach ($option as $key => $val) {
			echo "<option value=\"".$key."\""; 
			if($key == $field) echo "selected";
			echo ">". $val ."</option>\n";
		}
		echo "</select>&nbsp;";

		echo "</td></tr>";
	}
	if($extended)	{
		echo "<tr class='tab_bg_1'><td colspan='2' align='right'>".$LANG["reports"][60].":</td><td align='center' colspan='2'>".$LANG["search"][8].":&nbsp;";
		showCalendarForm("form","date1",$date1);
		echo "</td><td align='center' colspan='2'>";
		echo $LANG["search"][9].":&nbsp;";
		showCalendarForm("form","date2",$date2);
		echo "</td></tr>";

		echo "<tr class='tab_bg_1'><td colspan='2' align='right'>".$LANG["reports"][61].":</td><td align='center' colspan='2'>".$LANG["search"][8].":&nbsp;";
		showCalendarForm("form","enddate1",$enddate1);
		echo "</td><td align='center' colspan='2'>";
		echo $LANG["search"][9].":&nbsp;";
		showCalendarForm("form","enddate2",$enddate2);
		echo "</td></tr>";
	}
	echo "<tr  class='tab_bg_1'>";

	echo "<td align='center' colspan='2'>";
	$elts=array("both"=>$LANG["joblist"][6]." / ".$LANG["job"][7],"contents"=>$LANG["joblist"][6],"followup" => $LANG["job"][7],"ID"=>"ID");
	echo "<select name='field2'>";
	foreach ($elts as $key => $val){
		$selected="";
		if ($field2==$key) $selected="selected";
		echo "<option value=\"$key\" $selected>$val</option>";
	}
	echo "</select>";



	echo "&nbsp;".$LANG["search"][2]."&nbsp;";
	echo "<input type='text' size='15' name=\"contains2\" value=\"".stripslashes($contains2)."\">";
	echo "</td>";

	echo "<td align='center' colspan='2'>".$LANG["reports"][59].":<select name='showfollowups'>";
	echo "<option value='1' ".($showfollowups=="1"?"selected":"").">".$LANG["choice"][1]."</option>";
	echo "<option value='0' ".($showfollowups=="0"?"selected":"").">".$LANG["choice"][0]."</option>";	
	echo "</select></td>";


	echo "<td align='center' colspan='1'><input type='submit' value=\"".$LANG["buttons"][0]."\" class='submit'></td>";
	echo "<td align='center'  colspan='1'><input type='submit' name='reset' value=\"".$LANG["buttons"][16]."\" class='submit'></td>";

	echo "</tr>";

	echo "</table></div>";
	echo "<input type='hidden' name='start' value='0'>";
	echo "</form>";


}


function getCommonSelectForTrackingSearch(){
return " DISTINCT glpi_tracking.*,
		glpi_tracking.author as authorID, author.name AS authorname, author.realname AS authorrealname, author.firstname AS authorfirstname,	
		glpi_tracking.assign as assignID, assign.name AS assignname, assign.realname AS assignrealname, assign.firstname AS assignfirstname,
		glpi_dropdown_tracking_category.completename AS catname,
		glpi_groups.name as groupname ";
}

function getCommonLeftJoinForTrackingSearch(){
	return " LEFT JOIN glpi_users as author ON ( glpi_tracking.author = author.ID) "
	." LEFT JOIN glpi_users as assign ON ( glpi_tracking.assign = assign.ID) "
	." LEFT JOIN glpi_groups ON ( glpi_tracking.FK_group = glpi_groups.ID) "
	." LEFT JOIN glpi_dropdown_tracking_category ON ( glpi_tracking.category = glpi_dropdown_tracking_category.ID) ";
}


function showTrackingList($target,$start="",$sort="",$order="",$status="new",$author=0,$group=0,$assign=0,$assign_ent=0,$category=0,$priority=0,$request_type=0,$item=0,$type=0,$showfollowups="",$field2="",$contains2="",$field="",$contains="",$date1="",$date2="",$computers_search="",$enddate1="",$enddate2="") {
	// Lists all Jobs, needs $show which can have keywords 
	// (individual, unassigned) and $contains with search terms.
	// If $item is given, only jobs for a particular machine
	// are listed.
	// group = 0 : not use
	// group = -1 : groups of the author if session variable OK
	// group > 0 : specific group

	global $DB,$CFG_GLPI, $LANG;

	$candelete=haveRight("delete_ticket","1");
	$canupdate=haveRight("update_ticket","1");
	if (!haveRight("show_ticket","1")) {
		if ($author==0&&$assign==0)
			if (!haveRight("own_ticket","1"))
				$author=$_SESSION["glpiID"];
			else $assign=$_SESSION["glpiID"];
	}

	// Reduce computer list
	if ($computers_search){
		$SEARCH=makeTextSearch($contains);
		// Build query
		if($field == "all") {
			$wherecomp = " (";
			$query = "SHOW COLUMNS FROM glpi_computers";
			$result = $DB->query($query);
			$i = 0;

			while($line = $DB->fetch_array($result)) {
				if($i != 0) {
					$wherecomp .= " OR ";
				}
				if(IsDropdown($line["Field"])) {
					$wherecomp .= " glpi_dropdown_". $line["Field"] .".name $SEARCH" ;
				}
				elseif($line["Field"] == "location") {
					$wherecomp .= " glpi_dropdown_locations.name $SEARCH";
				}
				else {
					$wherecomp .= "comp.".$line["Field"] . $SEARCH;
				}
				$i++;
			}
			foreach($CFG_GLPI["devices_tables"] as $key => $val) {
				if ($val!="drive"&&$val!="control"&&$val!="pci"&&$val!="case"&&$val!="power")
					$wherecomp .= " OR ".$val.".designation ".makeTextSearch($contains,0);
			}
			$wherecomp .= " OR glpi_networking_ports.ifaddr $SEARCH";
			$wherecomp .= " OR glpi_networking_ports.ifmac $SEARCH";
			$wherecomp .= " OR glpi_dropdown_netpoint.name $SEARCH";
			$wherecomp .= " OR glpi_enterprises.name $SEARCH";
			$wherecomp .= " OR resptech.name $SEARCH";

			$wherecomp .= ")";
		}
		else {
			if(IsDevice($field)) {
				$wherecomp = "(glpi_device_".$field." $SEARCH )";
			}
			else {
				$wherecomp = "($field $SEARCH)";
			}
		}
	}
	if (!$start) {
		$start = 0;
	}
	$query = "SELECT ".getCommonSelectForTrackingSearch()." FROM glpi_tracking ".getCommonLeftJoinForTrackingSearch();

	if ($computers_search){
		$query.= " LEFT JOIN glpi_computers as comp on ( comp.ID=glpi_tracking.computer AND glpi_tracking.device_type='".COMPUTER_TYPE."' )";
		$query.= " LEFT JOIN glpi_computer_device as gcdev ON (comp.ID = gcdev.FK_computers) ";
		$query.= "LEFT JOIN glpi_device_moboard as moboard ON (moboard.ID = gcdev.FK_device AND gcdev.device_type = '".MOBOARD_DEVICE."') ";
		$query.= "LEFT JOIN glpi_device_processor as processor ON (processor.ID = gcdev.FK_device AND gcdev.device_type = '".PROCESSOR_DEVICE."') ";
		$query.= "LEFT JOIN glpi_device_gfxcard as gfxcard ON (gfxcard.ID = gcdev.FK_DEVICE AND gcdev.device_type = '".GFX_DEVICE."') ";
		$query.= "LEFT JOIN glpi_device_hdd as hdd ON (hdd.ID = gcdev.FK_DEVICE AND gcdev.device_type = '".HDD_DEVICE."') ";
		$query.= "LEFT JOIN glpi_device_iface as iface ON (iface.ID = gcdev.FK_DEVICE AND gcdev.device_type = '".NETWORK_DEVICE."') ";
		$query.= "LEFT JOIN glpi_device_ram as ram ON (ram.ID = gcdev.FK_DEVICE AND gcdev.device_type = '".RAM_DEVICE."') ";
		$query.= "LEFT JOIN glpi_device_sndcard as sndcard ON (sndcard.ID = gcdev.FK_DEVICE AND gcdev.device_type = '".SND_DEVICE."') ";
		$query.= "LEFT JOIN glpi_networking_ports on (comp.ID = glpi_networking_ports.on_device AND  glpi_networking_ports.device_type='1')";
		$query.= "LEFT JOIN glpi_dropdown_netpoint on (glpi_dropdown_netpoint.ID = glpi_networking_ports.netpoint)";
		$query.= "LEFT JOIN glpi_dropdown_os on (glpi_dropdown_os.ID = comp.os)";
		$query.= "LEFT JOIN glpi_dropdown_locations on (glpi_dropdown_locations.ID = comp.location)";
		$query.= "LEFT JOIN glpi_dropdown_model on (glpi_dropdown_model.ID = comp.model)";
		$query.= "LEFT JOIN glpi_type_computers on (glpi_type_computers.ID = comp.type)";
		$query.= " LEFT JOIN glpi_enterprises ON (glpi_enterprises.ID = comp.FK_glpi_enterprise ) ";
		$query.= " LEFT JOIN glpi_users as resptech ON (resptech.ID = comp.tech_num ) ";
	}

	if ($contains2!=""&&$field2!="contents"&&$field2!="ID") {
		$query.= " LEFT JOIN glpi_followups ON ( glpi_followups.tracking = glpi_tracking.ID)";
	}


	$where=" WHERE ";


	switch ($status){
		case "new": $where.=" glpi_tracking.status = 'new'"; break;
		case "notold": $where.=" (glpi_tracking.status = 'new' OR glpi_tracking.status = 'plan' OR glpi_tracking.status = 'assign' OR glpi_tracking.status = 'waiting')"; break;
		case "old": $where.=" ( glpi_tracking.status = 'old_done' OR glpi_tracking.status = 'old_notdone')"; break;
		case "process": $where.=" ( glpi_tracking.status = 'plan' OR glpi_tracking.status = 'assign' )"; break;
		case "waiting": $where.=" ( glpi_tracking.status = 'waiting' )"; break;
		case "old_done": $where.=" ( glpi_tracking.status = 'old_done' )"; break;
		case "old_notdone": $where.=" ( glpi_tracking.status = 'old_notdone' )"; break;
		case "assign": $where.=" ( glpi_tracking.status = 'assign' )"; break;
		case "plan": $where.=" ( glpi_tracking.status = 'plan' )"; break;
		default : $where.=" '1' = '1'";break;
	}


	if ($computers_search)
		$where.=" AND glpi_tracking.device_type= '1'";
	if ($category > 0){
		$where.=" AND ".getRealQueryForTreeItem("glpi_dropdown_tracking_category",$category,"glpi_tracking.category");
	}

	if ($computers_search) $where .= " AND $wherecomp";
	if (!empty($date1)&&$date1!="0000-00-00") $where.=" AND glpi_tracking.date >= '$date1'";
	if (!empty($date2)&&$date2!="0000-00-00") $where.=" AND glpi_tracking.date <= adddate( '". $date2 ."' , INTERVAL 1 DAY ) ";
	if (!empty($enddate1)&&$enddate1!="0000-00-00") $where.=" AND glpi_tracking.closedate >= '$enddate1'";
	if (!empty($enddate2)&&$enddate2!="0000-00-00") $where.=" AND glpi_tracking.closedate <= adddate( '". $enddate2 ."' , INTERVAL 1 DAY ) ";


	if ($type!=0)
		$where.=" AND glpi_tracking.device_type='$type'";	

	if ($item!=0&&$type!=0)
		$where.=" AND glpi_tracking.computer = '$item'";	

	if ($assign_ent!=0) $where.=" AND glpi_tracking.assign_ent = '$assign_ent'";
	if ($assign!=0) $where.=" AND glpi_tracking.assign = '$assign'";



	if ($request_type!=0) $where.=" AND glpi_tracking.request_type = '$request_type'";

	if ($priority>0) $where.=" AND glpi_tracking.priority = '$priority'";
	if ($priority<0) $where.=" AND glpi_tracking.priority >= '".abs($priority)."'";

	$search_author=false;
	if ($group>0) $where.=" AND glpi_tracking.FK_group = '$group'";
	else if ($group==-1&&$author!=0&&haveRight("show_group_ticket",1)){
		// Get Author group's
		if (count($_SESSION["glpigroups"])){
			$where.=" AND ( ";
			$i=0;
			foreach ($_SESSION["glpigroups"] as $gp){
				if ($i>0) $where.=" OR ";
				$where.=" glpi_tracking.FK_group = '$gp' ";
				$i++;
			}

			if ($author!=0) {
				if ($i>0) $where.=" OR ";
				$where.=" glpi_tracking.author = '$author'";
				$search_author=true;
			}

			
			$where.=")";
		}
	}

	if ($author!=0&&!$search_author) {
		$where.=" AND glpi_tracking.author = '$author'";
	}


	if ($contains2!=""){
		$SEARCH2=makeTextSearch($contains2);
		switch ($field2){
			case "both" :
				$where.= " AND (glpi_followups.contents $SEARCH2 OR glpi_tracking.contents $SEARCH2)";
			break;
			case "followup" :
				$where.= " AND (glpi_followups.contents $SEARCH2)";
			break;
			case "contents" :
				$where.= " AND (glpi_tracking.contents $SEARCH2)";
			break;
			case "ID" :
				$where= " WHERE (glpi_tracking.ID = '".$contains2."')";
			break;

		}
	}
	$where.=getEntitiesRestrictRequest("AND","glpi_tracking");


	if ($sort=="")
		$sort="glpi_tracking.date";
	if ($order=="")
		$order=getTrackingOrderPrefs($_SESSION["glpiID"]);

	$query.=$where." ORDER BY $sort $order";
	//echo $query;
	// Get it from database	
	if ($result = $DB->query($query)) {

		$numrows= $DB->numrows($result);

		if ($start<$numrows) {

			// Set display type for export if define
			$output_type=HTML_OUTPUT;
			if (isset($_GET["display_type"]))
				$output_type=$_GET["display_type"];


			// Pager
			$parameters2="field=$field&amp;contains=$contains&amp;date1=$date1&amp;date2=$date2&amp;only_computers=$computers_search&amp;field2=$field2&amp;contains2=$contains2&amp;assign=$assign&amp;assign_ent=$assign_ent&amp;author=$author&amp;group=$group&amp;start=$start&amp;status=$status&amp;category=$category&amp;priority=$priority&amp;type=$type&amp;showfollowups=$showfollowups&amp;enddate1=$enddate1&amp;enddate2=$enddate2&amp;item=$item&amp;request_type=$request_type";
			$parameters=$parameters2."&amp;sort=$sort&amp;order=$order";
			if (ereg("user.form.php",$_SERVER['PHP_SELF'])) $parameters.="&amp;ID=$author";
			// Manage helpdesk
			if (ereg("helpdesk",$target)) 
				$parameters.="&amp;show=user";
			if ($output_type==HTML_OUTPUT){
				if (!ereg("helpdesk",$target)) 
					printPager($start,$numrows,$target,$parameters,TRACKING_TYPE);
				else printPager($start,$numrows,$target,$parameters);
			}

			$nbcols=9;

			// Form to delete old item
			if (($candelete||$canupdate)&&$output_type==HTML_OUTPUT){
				echo "<form method='post' name='massiveaction_form' id='massiveaction_form' action=\"".$CFG_GLPI["root_doc"]."/front/massiveaction.php\">";

			}

			$i=$start;
			if (isset($_GET['export_all']))
				$i=0;

			$end_display=$start+$CFG_GLPI["list_limit"];
			if (isset($_GET['export_all']))
				$end_display=$numrows;
			// Display List Header
			echo displaySearchHeader($output_type,$end_display-$start+1,$nbcols,1);

			commonTrackingListHeader($output_type,$target,$parameters2,$sort,$order);

			while ($i < $numrows && $i<$end_display&&$data=$DB->fetch_array($result)){
//				$ID = $DB->result($result, $i, "ID");
				showJobShort($data, $showfollowups,$output_type,$i-$start+1);
				$i++;
			}
			$title="";
			// Title for PDF export
			if ($output_type==PDF_OUTPUT){
				$title.=$LANG["joblist"][0]." = ";
				switch($status){
					case "new": $title.=$LANG["joblist"][9];break;
					case "assign": $title.=$LANG["joblist"][18];break;
					case "plan": $title.=$LANG["joblist"][19];break;
					case "waiting": $title.=$LANG["joblist"][26];break;
					case "old_done": $title.=$LANG["joblist"][10];break;
					case "old_notdone": $title.=$LANG["joblist"][17];break;
					case "notold": $title.=$LANG["joblist"][24];break;
					case "process": $title.=$LANG["joblist"][21];break;
					case "old": $title.=$LANG["joblist"][25];break;
					case "all": $title.=$LANG["joblist"][20];break;
				}
				if ($author!=0) $title.=" - ".$LANG["common"][37]." = ".getUserName($author);
				if ($group>0) $title.=" - ".$LANG["common"][35]." = ".getDropdownName("glpi_groups",$group);
				if ($assign!=0) $title.=" - ".$LANG["job"][27]." = ".getUserName($assign);
				if ($request_type!=0) $title.=" - ".$LANG["job"][44]." = ".getRequestTypeName($request_type);
				if ($category!=0) $title.=" - ".$LANG["common"][36]." = ".getDropdownName("glpi_dropdown_tracking_category",category);
				if ($assign_ent!=0) $title.=" - ".$LANG["job"][27]." = ".getDropdownName("glpi_enterprises",$assign_ent);
				if ($priority!=0) $title.=" - ".$LANG["joblist"][2]." = ".getPriorityName($priority);
				if ($type!=0&&$item!=0){
					$ci=new CommonItem();
					$ci->getFromDB($type,$item);
					$title.=" - ".$LANG["common"][1]." = ".$ci->getType()." / ".$ci->getNameID();

				}
			}
			// Display footer
			echo displaySearchFooter($output_type,$title);

			// Delete selected item
			if (($candelete||$canupdate)&&$output_type==HTML_OUTPUT){
				echo "<div align='center'>";
				echo "<table cellpadding='5' width='900'>";
				echo "<tr><td><img src=\"".$CFG_GLPI["root_doc"]."/pics/arrow-left.png\" alt=''></td><td><a onclick= \"if ( markAllRows('massiveaction_form') ) return false;\" href='".$_SERVER['PHP_SELF']."?$parameters&amp;select=all&amp;start=$start'>".$LANG["buttons"][18]."</a></td>";

				echo "<td>/</td><td><a onclick=\"if ( unMarkAllRows('massiveaction_form') ) return false;\" href='".$_SERVER['PHP_SELF']."?$parameters&amp;select=none&amp;start=$start'>".$LANG["buttons"][19]."</a>";
				echo "</td><td width='80%'>";
				dropdownMassiveAction(TRACKING_TYPE);
				echo "</td><td>&nbsp;</td></table></div>";
				// End form for delete item
				echo "</form>";
			}


			// Pager
			if ($output_type==HTML_OUTPUT) // In case of HTML display
				printPager($start,$numrows,$target,$parameters);

		} else {
			echo "<div align='center'><strong>".$LANG["joblist"][8]."</strong></div>";

		}
	}
}

function showFollowupsShort($ID) {
	// Print Followups for a job

	global $DB,$CFG_GLPI, $LANG;

	// Get Number of Followups

	$query="SELECT * FROM glpi_followups WHERE tracking='$ID' ORDER BY date DESC";
	$result=$DB->query($query);

	$out="";
	if ($DB->numrows($result)>0) {
		$out.="<div align='center'><table class='tab_cadre' width='100%' cellpadding='2'>\n";
		$out.="<tr><th>".$LANG["common"][27]."</th><th>".$LANG["common"][37]."</th><th>".$LANG["joblist"][6]."</th></tr>\n";

		while ($data=$DB->fetch_array($result)) {

			$out.="<tr class='tab_bg_3'>";
			$out.="<td align='center'>".convDateTime($data["date"])."</td>";
			$out.="<td align='center'>".getUserName($data["author"],1)."</td>";
			$out.="<td width='70%'><strong>".resume_text($data["contents"],$CFG_GLPI["cut"])."</strong></td>";
			$out.="</tr>";
		}		

		$out.="</table></div>";

	}
	return $out;
}




function getAssignName($ID,$type,$link=0){
	global $CFG_GLPI;

	if ($type==USER_TYPE){
		if ($ID==0) return "[Nobody]";
		return getUserName($ID,$link);

	} else if ($type==ENTERPRISE_TYPE){
		$ent=new Enterprise();
		if ($ent->getFromDB($ID)){
			$before="";
			$after="";
			if ($link){
				$before="<a href=\"".$CFG_GLPI["root_doc"]."/front/enterprise.form.php?ID=".$ID."\">";
				$after="</a>";
			}

			return $before.$ent->fields["name"].$after;
		} else return "";
	}

}



function showJobDetails ($target,$ID){
	global $DB,$CFG_GLPI,$LANG;
	$job=new Job();

	$canupdate=haveRight("update_ticket","1");


	if ($job->getfromDB($ID)) {

		if (!haveRight("show_ticket","1")
			&&$job->fields["author"]!=$_SESSION["glpiID"]
			&&$job->fields["assign"]!=$_SESSION["glpiID"]
			&&!($_SESSION["glpiactiveprofile"]["show_group_ticket"]&&in_array($job->fields["FK_group"],$_SESSION["glpigroups"])) ){
			return false;
		}

		$canupdate_descr=$canupdate||($job->numberOfFollowups()==0&&$job->fields["author"]==$_SESSION["glpiID"]);
		$author=new User();
		$author->getFromDB($job->fields["author"]);
		$assign=new User();
		$assign->getFromDB($job->fields["assign"]);
		$item=new CommonItem();
		$item->getFromDB($job->fields["device_type"],$job->fields["computer"]);

		showTrackingOnglets($_SERVER['PHP_SELF']."?ID=".$ID);

		echo "<div align='center'>";
		echo "<form method='post' action='$target'  enctype=\"multipart/form-data\">\n";
		echo "<table class='tab_cadre_fixe' cellpadding='5'>";
		// Premi�e ligne
		echo "<tr ><th colspan='2' style='font-size:10px'>";
		echo $LANG["joblist"][11].": <strong>".convDateTime($job->fields["date"])."</strong>";"</th>";
		echo "<th style='font-size:10px'>".$LANG["joblist"][12].":\n";
		if (!ereg("old_",$job->fields["status"]))
		{
			echo "<i>".$LANG["job"][1]."</i>\n";
		}
		else
		{
			echo "<strong>".convDateTime($job->fields["closedate"])."</strong>\n";
		}
		echo "</th></tr>";
		echo "<tr class='tab_bg_2'>";
		// Premier Colonne
		echo "<td valign='top' width='27%'>";
		echo "<table cellpadding='3'>";
		echo "<tr class='tab_bg_2'><td align='right'>";
		echo $LANG["joblist"][0].":</td><td>";
		if ($canupdate)
			dropdownStatus("status",$job->fields["status"]);
		else echo getStatusName($job->fields["status"]);
		echo "</td></tr>";

		echo "<tr><td align='right'>";
		echo $LANG["common"][37].":</td><td>";
		if ($canupdate)
			dropdownAllUsers("author",$job->fields["author"],1,$job->fields["FK_entities"]);
		else echo $author->getName();
		echo "</td></tr>";

		echo "<tr><td align='right'>";
		echo $LANG["common"][35].":</td><td>";
		if ($canupdate)
			dropdownValue("glpi_groups","FK_group",$job->fields["FK_group"],1,$job->fields["FK_entities"]);
		else echo getDropdownName("glpi_groups",$job->fields["FK_group"]);
		echo "</td></tr>";

		echo "<tr><td align='right'>";
		echo $LANG["joblist"][2].":</td><td>";
		if ($canupdate)
			dropdownPriority("priority",$job->fields["priority"]);
		else echo getPriorityName($job->fields["priority"]);
		echo "</td></tr>";

		echo "<tr><td>";
		echo $LANG["common"][36].":</td><td>";
		if ($canupdate)
			dropdownValue("glpi_dropdown_tracking_category","category",$job->fields["category"]);
		else echo getDropdownName("glpi_dropdown_tracking_category",$job->fields["category"]);
		echo "</td></tr>";

		echo "</table></td>";

		// Deuxi�e colonne
		echo "<td valign='top' width='33%'>";

		echo "<table border='0'>";

		echo "<tr><td align='right'>";
		echo $LANG["job"][44].":</td><td>";
		if ($canupdate)
			dropdownRequestType("request_type",$job->fields["request_type"]);
		else echo getRequestTypeName($job->fields["request_type"]);
		echo "</td></tr>";

		echo "<tr><td align='right'>";
		echo $LANG["common"][1].":</td><td>";
		if ($canupdate){
			echo $item->getType()." - ".$item->getLink(1)."<br>";
			dropdownTrackingAllDevices("device_type",0,1,$job->fields["FK_entities"]);
		}
		else echo $item->getType()." ".$item->getNameID();

		echo "</td></tr>";


		echo "<tr><td align='right'>";
		echo $LANG["job"][5].":</td><td>&nbsp;</td></tr>";

		if ($canupdate||haveRight("assign_ticket","1")){
			echo "<tr><td align='right'>";
			echo $LANG["job"][27].":</td><td>";
			dropdownUsers("assign",$job->fields["assign"],"own_ticket",0,1,$job->fields["FK_entities"]);
			echo "</td></tr>";
		} else if (haveRight("steal_ticket","1")) {
			echo "<tr><td align='right'>";
			echo $LANG["job"][27].":</td><td>";
			dropdownUsers("assign",$job->fields["assign"],"ID",0,1,$job->fields["FK_entities"]);
			echo "</td></tr>";
		}else {
			echo "<tr><td align='right'>";
			echo $LANG["job"][27].":</td><td>";
			echo getUserName($job->fields["assign"]);
			echo "</td></tr>";
		}
		if ($canupdate||haveRight("assign_ticket","1")){
			echo "<tr><td align='right'>";
			echo $LANG["job"][28].":</td><td>";
			dropdownValue("glpi_enterprises","assign_ent",$job->fields["assign_ent"],1,$job->fields["FK_entities"]);
			echo "</td></tr>";
		} else {
			echo "<tr><td align='right'>";
			echo $LANG["job"][28].":</td><td>";
			echo getDropdownName("glpi_enterprises",$job->fields["assign_ent"]);
			echo "</td></tr>";

		}
		echo "</table>";









		echo "</td>";

		// Troisi�e Colonne
		echo "<td valign='top' width='20%'>";

		if(haveRight("contract_infocom","r")){  // admin = oui on affiche les couts liés à l'interventions
			echo "<table border='0'>";
			if ($job->fields["realtime"]>0){
				echo "<tr><td align='right'>";
				echo $LANG["job"][20].":</td><td>";
				echo "<strong>".getRealtime($job->fields["realtime"])."</strong>";
				echo "</td></tr>";
			}
			echo "<tr><td align='right'>";
			// cout
			echo $LANG["job"][40].": ";
			echo "</td><td><input type='text' maxlength='100' size='15' name='cost_time' value=\"".number_format($job->fields["cost_time"],$CFG_GLPI["decimal_number"],'.','')."\"></td></tr>";

			echo "<tr><td align='right'>";

			echo $LANG["job"][41].": ";
			echo "</td><td><input type='text' maxlength='100' size='15' name='cost_fixed' value=\"".number_format($job->fields["cost_fixed"],$CFG_GLPI["decimal_number"],'.','')."\">";

			echo "</td></tr>\n";

			echo "<tr><td align='right'>";

			echo $LANG["job"][42].": ";
			echo "</td><td><input type='text' maxlength='100' size='15' name='cost_material' value=\"".number_format($job->fields["cost_material"],$CFG_GLPI["decimal_number"],'.','')."\">";

			echo "</td></tr>\n";

			echo "<tr><td align='right'>";

			echo $LANG["job"][43].": ";
			echo "</td><td><strong>";
			echo trackingTotalCost($job->fields["realtime"],$job->fields["cost_time"],$job->fields["cost_fixed"],$job->fields["cost_material"]);
			echo "</strong></td></tr>\n</table>";
		}

		echo "</td></tr>";


		// Deuxi�e Ligne
		// Colonnes 1 et 2
		echo "<tr class='tab_bg_1'><td colspan='2'>";
		echo "<table width='99%' >";
		echo "<tr  class='tab_bg_2'><td width='15%'>".$LANG["joblist"][6]."<br><br></td>";
		echo "<td  width='85%' align='left'>";

		if ($canupdate_descr){ // Admin =oui on autorise la modification de la description
			$rand=mt_rand();
			echo "<script type='text/javascript' >\n";
			echo "function showDesc$rand(){\n";
			echo "Element.hide('desc$rand');";
			echo "var a=new Ajax.Updater('viewdesc$rand','".$CFG_GLPI["root_doc"]."/ajax/textarea.php' , {asynchronous:true, evalScripts:true, method: 'post',parameters: 'rows=6&cols=60&name=contents&data=".rawurlencode($job->fields["contents"])."'});";
			echo "}";
			echo "</script>\n";
			echo "<div id='desc$rand' class='div_tracking' onClick='showDesc$rand()'>\n";
			if (!empty($job->fields["contents"]))
				echo nl2br($job->fields["contents"]);
			else echo $LANG["job"][33];

			echo "</div>\n";	

			echo "<div id='viewdesc$rand'>\n";
			echo "</div>\n";	
		} else echo nl2br($job->fields["contents"]);

		echo "</td>";
		echo "</tr>";
		echo "</table>";
		echo "</td>";
		// Colonne 3

		echo "<td valign='top'>";

		// Mailing ? Y or no ?

		if ($CFG_GLPI["mailing"]==1){
			echo "<table><tr><td align='right'>";
			echo $LANG["job"][19].":</td><td>";
			if ($canupdate){
				dropdownYesNo('emailupdates',$job->fields["emailupdates"]);
			} else {
				if ($job->fields["emailupdates"]) echo $LANG["choice"][1];
				else $LANG["choice"][0];
			}
			echo "</td></tr>";

			echo "<tr><td align='right'>";
			echo $LANG["joblist"][27].":";
			echo "</td><td>";
			if ($canupdate){
				autocompletionTextField("uemail","glpi_tracking","uemail",$job->fields["uemail"],15);

				if (!empty($job->fields["uemail"]))
					echo "<a href='mailto:".$job->fields["uemail"]."'><img src='".$CFG_GLPI["root_doc"]."/pics/edit.png' alt='Mail'></a>";
			} else if (!empty($job->fields["uemail"]))
				echo "<a href='mailto:".$job->fields["uemail"]."'>".$job->fields["uemail"]."</a>";
			else echo "&nbsp;";
			echo "</td></tr></table>";


		}




		// File associated ?
		$query2 = "SELECT * FROM glpi_doc_device WHERE glpi_doc_device.FK_device = '".$job->fields["ID"]."' AND glpi_doc_device.device_type = '".TRACKING_TYPE."' ";
		$result2 = $DB->query($query2);
		$numfiles=$DB->numrows($result2);
		echo "<table width='100%'><tr><th colspan='2'>".$LANG["tracking"][25]."</th></tr>";			

		if ($numfiles>0){
			$doc=new Document;
			while ($data=$DB->fetch_array($result2)){
				$doc->getFromDB($data["FK_doc"]);

				echo "<tr><td>";
				echo getDocumentLink($doc->fields["filename"],"&tracking=$ID");
				if (haveRight("document","w"))
					echo "<a href='".$CFG_GLPI["root_doc"]."/front/document.form.php?deleteitem=delete&amp;ID=".$data["ID"]."'><img src='".$CFG_GLPI["root_doc"]."/pics/delete.png' alt='".$LANG["buttons"][6]."'></a>";
				echo "</td></tr>";
			}
		}
		if ($canupdate||haveRight("comment_all_ticket","1")||haveRight("comment_ticket","1")){
			echo "<tr><td colspan='2'>";
			echo "<input type='file' name='filename' size='20'>";
			if ($canupdate&&haveRight("document","r")){
				echo "<br>";
				dropdown("glpi_docs","document");
			}
			echo "</td></tr>";
		}
		echo "</table>";

		echo "</td></tr>";
		// Troisi�e Ligne
		if ($canupdate||$canupdate_descr||haveRight("comment_all_ticket","1")||haveRight("comment_ticket","1")||haveRight("assign_ticket","1")||haveRight("steal_ticket","1")){
			echo "<tr class='tab_bg_1'><td colspan='3' align='center'>";
			echo "<input type='submit' class='submit' name='update' value='".$LANG["buttons"][14]."'></td></tr>";
		}

		echo "</table>";
		echo "<input type='hidden' name='ID' value='$ID'>";
		echo "</form>";
		echo "</div>";

		echo "<script type='text/javascript' >\n";
		echo "function showPlan(){\n";
		echo "Element.hide('plan');";
		echo "var a=new Ajax.Updater('viewplan','".$CFG_GLPI["root_doc"]."/ajax/planning.php' , {asynchronous:true, evalScripts:true, method: 'get',parameters: 'form=followups&author=".$job->fields["assign"]."'});";
		echo "};";
		echo "function showAddFollowup(){\n";
		echo "Element.hide('viewfollowup');";
		echo "var a=new Ajax.Updater('viewfollowup','".$CFG_GLPI["root_doc"]."/ajax/addfollowup.php' , {asynchronous:true, evalScripts:true, method: 'get',parameters: 'tID=$ID'});";
		echo "};";
		echo "</script>";

		echo "<div id='viewfollowup'>\n";
		echo "</div>\n";	


		return true;
	}

	return false;
}

function showFollowupsSummary($tID){
	global $DB,$LANG,$CFG_GLPI;


	if (!haveRight("observe_ticket","1")&&!haveRight("show_full_ticket","1")) return false;

	// Display existing Followups
	$showprivate=haveRight("show_full_ticket","1");

	$RESTRICT="";
	if (!$showprivate)  $RESTRICT=" AND ( private='0' OR author ='".$_SESSION["glpiID"]."' ) ";

	$query = "SELECT * FROM glpi_followups WHERE (tracking = $tID) $RESTRICT ORDER BY date DESC";
	$result=$DB->query($query);



	$rand=mt_rand();


	echo "<div align='center'>";
	echo "<h3>".$LANG["job"][37]."</h3>";

	if ($DB->numrows($result)==0){
		echo "<table class='tab_cadre_fixe'><tr class='tab_bg_2'><th>";
		echo "<strong>".$LANG["job"][12]."</strong>";
		echo "</th></tr></table>";
	}
	else {	

		echo "<table class='tab_cadrehov_pointer'>";
		echo "<tr><th>&nbsp;</th><th>".$LANG["common"][27]."</th><th>".$LANG["joblist"][6]."</th><th>".$LANG["job"][31]."</th><th>".$LANG["job"][35]."</th><th>".$LANG["common"][37]."</th>";
		if ($showprivate)
			echo "<th>".$LANG["job"][30]."</th>";
		echo "</tr>";
		while ($data=$DB->fetch_array($result)){

			echo "<tr class='tab_bg_2' onClick=\"viewEditFollowup".$data["ID"]."$rand();\" id='viewfollowup".$data["ID"]."$rand'>";
			echo "<td>".$data["ID"]."</td>";

			echo "<td>";

			echo "<script type='text/javascript' >\n";
			echo "function viewEditFollowup".$data["ID"]."$rand(){\n";
			//			echo "Element.hide('viewfollowup');";
			echo "var a=new Ajax.Updater('viewfollowup','".$CFG_GLPI["root_doc"]."/ajax/viewfollowup.php' , {asynchronous:true, evalScripts:true, method: 'get',parameters: 'ID=".$data["ID"]."'});";
			echo "};";

			echo "</script>\n";


			echo convDateTime($data["date"])."</td>";
			echo "<td align='left'>".nl2br($data["contents"])."</td>";

			$hour=floor($data["realtime"]);
			$minute=round(($data["realtime"]-$hour)*60,0);
			echo "<td>";
			if ($hour) echo "$hour ".$LANG["job"][21]."<br>";
			if ($minute||!$hour)
				echo "$minute ".$LANG["job"][22]."</td>";

			echo "<td>";
			$query2="SELECT * from glpi_tracking_planning WHERE id_followup='".$data['ID']."'";
			$result2=$DB->query($query2);
			if ($DB->numrows($result2)==0)
				echo $LANG["job"][32];	
			else {
				$data2=$DB->fetch_array($result2);
				echo convDateTime($data2["begin"])."<br>".convDateTime($data2["end"])."<br>".getUserName($data2["id_assign"]);
			}
			echo "</td>";

			echo "<td>".getUserName($data["author"])."</td>";
			if ($showprivate){
				echo "<td>";
				if ($data["private"])
					echo $LANG["choice"][1];
				else echo $LANG["choice"][0];
				echo "</td>";
			}

			echo "</tr>";
		}
		echo "</table>";
	}	
	echo "</div>";
}

// Formulaire d'ajout de followup
function showAddFollowupForm($tID){
	global $DB,$LANG,$CFG_GLPI;

	$job=new Job;
	$job->getFromDB($tID);

	if (!haveRight("comment_ticket","1")&&!haveRight("comment_all_ticket","1")&&$job->fields["assign"]!=$_SESSION["glpiID"]) return false;


	$commentall=(haveRight("comment_all_ticket","1")||$job->fields["assign"]==$_SESSION["glpiID"]);

	if ($_SESSION["glpiactiveprofile"]["interface"]=="central"){
		$target=$CFG_GLPI["root_doc"]."/front/tracking.form.php";
	} else {
		$target=$CFG_GLPI["root_doc"]."/front/helpdesk.public.php?show=user";
	}
	// Display Add Table
	echo "<div align='center'>";
	echo "<form name='followups' method='post' action=\"$target\">\n";
	echo "<table class='tab_cadre_fixe'>";
	echo "<tr><th colspan='2'>";
	echo $LANG["job"][29];
	echo "</th></tr>";

	if ($commentall){
		$width_left=$width_right="50%";
		$cols=50;
	} else {
		$width_left="80%";
		$width_right="20%";
		$cols=80;
	}

	echo "<tr class='tab_bg_2'><td width='$width_left'>";
	echo "<table width='100%'>";
	echo "<tr><td>".$LANG["joblist"][6]."</td>";
	echo "<td><textarea name='contents' rows=8 cols=$cols></textarea>";
	echo "</td></tr>";
	echo "</table>";
	echo "</td>";

	echo "<td width='$width_right' valign='top'>";
	echo "<table width='100%'>";

	if ($commentall){
		echo "<tr>";
		echo "<td>".$LANG["job"][30].":</td>";
		echo "<td>";
		echo "<select name='private'>";
		echo "<option value='0'>".$LANG["choice"][0]."</option>";
		echo "<option value='1'>".$LANG["choice"][1]."</option>";
		echo "</select>";
		echo "</td>";
		echo "</tr>";

		echo "<tr><td>".$LANG["job"][31].":</td><td>";
		dropdownInteger('hour',0,0,100);
		echo $LANG["job"][21]."&nbsp;&nbsp;";
		dropdownInteger('minute',0,0,59);
		echo $LANG["job"][22];
		echo "</tr>";

		if (haveRight("show_planning","1")){
			echo "<tr>";
			echo "<td>".$LANG["job"][35]."</td>";

			echo "<td>";
			echo "<div id='plan'  onClick='showPlan()'>\n";
			echo "<span class='showplan'>".$LANG["job"][34]."</span>";
			echo "</div>\n";	

			echo "<div id='viewplan'>\n";
			echo "</div>\n";	


			echo "</td>";

			echo "</tr>";
		}
	}
	echo "<tr class='tab_bg_2'>";
	echo "<td align='center'>";
	echo "<input type='submit' name='add' value='".$LANG["buttons"][8]."' class='submit'>";
	echo "</td>";
	if ($commentall){
		echo "<td align='center'>";
		echo "<input type='submit' name='add_close' value='".$LANG["buttons"][26]."' class='submit'>";
		echo "</td>";
	}
	echo "</tr>";


	echo "</table>";
	echo "</td></tr>";
	echo "</table>";
	echo "<input type='hidden' name='tracking' value='$tID'>";
	echo "</form></div>";

}


// Formulaire d'ajout de followup
function showUpdateFollowupForm($ID){
	global $DB,$LANG,$CFG_GLPI;

	if (!haveRight("comment_ticket","1")&&!haveRight("comment_all_ticket","1")) return false;

	$commentall=haveRight("comment_all_ticket","1");

	// Display existing Followups

	$query = "SELECT * FROM glpi_followups WHERE (ID = '$ID')";
	$result=$DB->query($query);


	if ($DB->numrows($result)==1){
		echo "<div align='center'>";
		$data=$DB->fetch_array($result);
		echo "<table class='tab_cadre_fixe'>";
		echo "<tr><th>";
		echo $LANG["job"][39];
		echo "</th></tr>";
		echo "<tr class='tab_bg_2'><td>";
		echo "<form method='post' action=\"".$CFG_GLPI["root_doc"]."/front/tracking.form.php\">\n";

		echo "<table width='100%'>";
		echo "<tr class='tab_bg_2'><td width='50%'>";
		echo "<table width='100%' bgcolor='#FFFFFF'>";
		echo "<tr class='tab_bg_1'><td align='center' width='10%'>".$LANG["joblist"][6]."<br><br>".$LANG["common"][27].":<br>".convDateTime($data["date"])."</td>";
		echo "<td width='90%'>";

		if ($commentall){
			echo "<textarea name='contents' cols='50' rows='6'>".$data["contents"]."</textarea>";
		} else echo nl2br($data["contents"]);


		echo "</td></tr>";
		echo "</table>";
		echo "</td>";

		echo "<td width='50%' valign='top'>";
		echo "<table width='100%'>";


		if ($commentall){
			echo "<tr>";
			echo "<td>".$LANG["job"][30].":</td>";
			echo "<td>";
			echo "<select name='private'>";
			echo "<option value='0' ".(!$data["private"]?" selected":"").">".$LANG["choice"][0]."</option>";
			echo "<option value='1' ".($data["private"]?" selected":"").">".$LANG["choice"][1]."</option>";
			echo "</select>";
			echo "</td>";
			echo "</tr>";
		} 



		echo "<tr><td>".$LANG["job"][31].":</td><td>";
		$hour=floor($data["realtime"]);
		$minute=round(($data["realtime"]-$hour)*60,0);

		if ($commentall){

			dropdownInteger('hour',$hour,0,100);
			echo $LANG["job"][21]."&nbsp;&nbsp;";
			dropdownInteger('minute',$minute,0,59);
			echo $LANG["job"][22];
		} else {
			echo $hour." ".$LANG["job"][21]." ".$minute." ".$LANG["job"][22];

		}

		echo "</tr>";

		echo "<tr>";
		echo "<td>".$LANG["job"][35]."</td>";
		echo "<td>";
		$query2="SELECT * from glpi_tracking_planning WHERE id_followup='".$data['ID']."'";
		$result2=$DB->query($query2);
		if ($DB->numrows($result2)==0)
			if ($commentall)
				echo "<a href='".$CFG_GLPI["root_doc"]."/front/planning.form.php?edit=edit&amp;fup=".$data["ID"]."&amp;ID=-1'>".$LANG["buttons"][8]."</a>";
			else echo $LANG["job"][32];	
		else {
			$data2=$DB->fetch_array($result2);
			echo convDateTime($data2["begin"])."<br>".convDateTime($data2["end"])."<br>".getUserName($data2["id_assign"]);
			if ($commentall)
				echo "<a href='".$CFG_GLPI["root_doc"]."/front/planning.form.php?edit=edit&amp;fup=".$data["ID"]."&amp;ID=".$data2["ID"]."'><img src='".$CFG_GLPI["root_doc"]."/pics/edit.png'></a>";

		}

		echo "</td>";
		echo "</tr>";

		if ($commentall){
			echo "<tr class='tab_bg_2'>";
			echo "<td align='center' colspan='2'>";
			echo "<table width='100%'><tr><td align='center'>";
			echo "<input type='submit' name='update_followup' value='".$LANG["buttons"][14]."' class='submit'>";
			echo "</td><td align='center'>";
			echo "<input type='submit' name='delete_followup' value='".$LANG["buttons"][6]."' class='submit'>";
			echo "</td></tr></table>";
			echo "</td>";
			echo "</tr>";
		}


		echo "</table>";
		echo "</td></tr>";

		echo "</table>";
		if ($commentall){
			echo "<input type='hidden' name='ID' value='".$data["ID"]."'>";
			echo "<input type='hidden' name='tracking' value='".$data["tracking"]."'>";
			echo "</form>";
		}
		echo "</td></tr>";
		echo "</table>";
		echo "</div>";
	}
}

// fonction calcul de cout total d'un ticket
function trackingTotalCost($realtime,$cost_time,$cost_fixed,$cost_material){
	global $CFG_GLPI;
	return number_format(($realtime*$cost_time)+$cost_fixed+$cost_material,$CFG_GLPI["decimal_number"],'.','');
}

/**
 * Calculate Ticket TCO for a device
 *
 * 
 *
 *@param $item_type device type
 *@param $item ID of the device
 *
 *@return float
 *
 **/
function computeTicketTco($item_type,$item){
	global $DB;
	$totalcost=0;

	$query="SELECT * 
		FROM glpi_tracking 
		WHERE (device_type = '$item_type' 
				AND computer = '$item') 
			AND (cost_time>0 
				OR cost_fixed>0
				OR cost_material>0)";
	$result = $DB->query($query);

	$i = 0;
	if ($DB->numrows($result)){
		while ($data=$DB->fetch_array($result)){
			$totalcost+=trackingTotalCost($data["realtime"],$data["cost_time"],$data["cost_fixed"],$data["cost_material"]); 
		}
	}
	return $totalcost;
}
?>
