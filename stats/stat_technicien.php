<?php
/*
 
  ----------------------------------------------------------------------
GLPI - Gestionnaire libre de parc informatique
 Copyright (C) 2002 by the INDEPNET Development Team.
 Bazile Lebeau, baaz@indepnet.net - Jean-Mathieu Dol�ans, jmd@indepnet.net
 http://indepnet.net/   http://glpi.indepnet.org
 ----------------------------------------------------------------------
 Based on:
IRMA, Information Resource-Management and Administration
Christian Bauer, turin@incubus.de 

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
 ----------------------------------------------------------------------
 Original Author of file: Mustapha Saddalah et Bazile Lebeau
 Purpose of file:
 ----------------------------------------------------------------------
*/
 
include ("_relpos.php");
include ($phproot . "/glpi/includes.php");
require ("functions.php");


checkAuthentication("normal");

commonHeader("Stats",$_SERVER["PHP_SELF"]);

echo "<div align ='center'><b>".$lang["stats"][17]."</b></div><hr noshade>";
//affichage du tableu
//table display
echo "<div align ='center'><table border='0' cellpadding=5>";
echo "<tr><th>".$lang["stats"][16]."</th><th>".$lang["stats"][13]."</th><th>".$lang["stats"][14]."</th><th>".$lang["stats"][15]."</th></tr>";

//recuperation des different utilisateurs ayant eu des interventions attribu�es
//get distinct user who has intervention assigned to
$nomTech = getNbIntervTech();

//Pour chacun de ces utilisateurs on affiche
//foreach these users display
foreach($nomTech as $key)
{
	echo "<tr class='tab_bg_1'>";
	echo "<td>".$key["assign"]."</td>";
	//le nombre d'intervention
	//the number of intervention
	echo "<td>".getNbinter(1,'assign',$key["assign"])."</td>";
	//le nombre d'intervention resolues
	//the number of resolved intervention
	echo "<td>".getNbresol(1,'assign',$key["assign"])."</td>";
	//Le temps moyen de resolution
	//The average time to resolv
	echo "<td>".getResolAvg(1, 'assign',$key["assign"])."</td>";
	echo "</tr>";
}
echo "</table>";
echo "</div>";

commonFooter();
?>
