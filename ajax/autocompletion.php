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
	$AJAX_INCLUDE=1;
	include ($phproot."/glpi/includes.php");
	header("Content-Type: text/html; charset=UTF-8");
	header_nocache();

	checkCentralAccess();

	$query="SELECT DISTINCT ".$_POST['field']." AS VAL FROM ".$_POST['table']." WHERE ".$_POST['field']." LIKE '".$_POST[$_POST['myname']]."%' AND ".$_POST['field']." <> '".$_POST[$_POST['myname']]."' ORDER BY ".$_POST['field']." LIMIT 0,20";
	if ($result=$db->query($query))
	if ($db->numrows($result)>0){
		echo "<ul class='autocomp'>";
		while ($data=$db->fetch_array($result))
		echo "<li class='autocomp'>".$data["VAL"]."</li>";
		echo "</ul>";
	}

?>