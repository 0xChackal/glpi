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
 Original Author of file:
 Purpose of file:
 ----------------------------------------------------------------------


*/

include ("_relpos.php");
include ($phproot . "/glpi/includes.php");
include ($phproot . "/glpi/includes_setup.php");

checkauthentication("admin");


commonHeader("Setup",$_SERVER["PHP_SELF"]);


 // titre
        echo "<div align='center'><table border='0'><tr><td><b>";
        echo "<img src=\"".$HTMLRel."pics/configuration.png\" ></td><td><span class='icon_nav'>".$lang["Menu"][10]."</span>";
        echo "</b></td></tr></table></div>";

echo "<div align='center'><table border='0' cellpadding='5'>";
echo "<tr><th>".$lang["setup"][62]."</th></tr>";

echo "<tr class='tab_bg_1'><td  align='center'><b><a href=\"setup-dropdowns.php\">".$lang["setup"][0]."</a></b></td></tr>";

echo "<tr class='tab_bg_1'><td  align='center'><b><a href=\"setup-templates.php\">".$lang["setup"][1]."</a></b></td></tr>";

echo "<tr class='tab_bg_1'><td align='center'><b><a href=\"setup-users.php\">".$lang["setup"][2]."</a></b></td> </tr>";


echo "</table></div>";




commonFooter();
?>
