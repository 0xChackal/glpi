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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------


	include ("_relpos.php");
	include ($phproot."/glpi/includes.php");
	header("Content-Type: text/html; charset=UTF-8");
	header_nocache();

	checkAuthentication("post-only");

	// Make a select box


if (isset($LINK_ID_TABLE[$_POST["idtable"]])){
	$table=$LINK_ID_TABLE[$_POST["idtable"]];
	
	// Link to user for search only > normal users
	$link="dropdownValue.php";
	if ($_POST["idtable"]==USER_TYPE)
		$link="dropdownUsers.php";
	
	$rand=mt_rand();

	displaySearchTextAjaxDropdown($_POST['myname'].$rand);

	$moreparam="";
	if(isset($_POST['value'])) $moreparam="&value=".$_POST['value'];

	echo "<script type='text/javascript' >";
	echo "   new Form.Element.Observer('search_".$_POST['myname']."$rand', 1, ";
	echo "      function(element, value) {";
	echo "      	new Ajax.Updater('results_ID$rand','".$cfg_glpi["root_doc"]."/ajax/$link',{asynchronous:true, evalScripts:true, ";
	echo "           onComplete:function(request)";
	echo "            {Element.hide('search_spinner$rand');}, ";
	echo "           onLoading:function(request)";
	echo "            {Element.show('search_spinner$rand');},";
	echo "           method:'post', parameters:'searchText=' + value+'&table=$table&myname=".$_POST["myname"]."$moreparam'";
	echo "})})";
	echo "</script>";	
	
	echo "<div id='search_spinner$rand' style=' position:absolute;  filter:alpha(opacity=70); -moz-opacity:0.7; opacity: 0.7; display:none;'><img src=\"".$HTMLRel."pics/wait.png\" title='Processing....' alt='Processing....' /></div>";	
	
	echo "<span id='results_ID$rand'>";
	echo "<select name='".$_POST["myname"]."'><option value='0'>------</option></select>";
	echo "</span>";	

$nb=0;
if ($cfg_glpi["use_ajax"])
	$nb=countElementsInTable($table);

if (!$cfg_glpi["use_ajax"]||$nb<$cfg_glpi["ajax_limit_count"]){
	echo "<script type='text/javascript' >\n";
	echo "document.getElementById('search_spinner$rand').style.visibility='hidden';";
	echo "Element.hide('search_".$_POST['myname']."$rand');";
	echo "document.getElementById('search_".$_POST['myname']."$rand').value='".$cfg_glpi["ajax_wildcard"]."';";
	echo "</script>\n";
}


if(isset($_POST['value'])&&$_POST['value']>0){
	echo "<script type='text/javascript' >\n";
	echo "document.getElementById('search_spinner$rand').style.visibility='hidden';";
	echo "document.getElementById('search_".$_POST['myname']."$rand').value='".$cfg_glpi["ajax_wildcard"]."';";
	echo "</script>\n";
}

}		
?>