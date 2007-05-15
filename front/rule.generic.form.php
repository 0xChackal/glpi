<?php
/*
 * @version $Id: rule.ldap.form.php 4895 2007-05-06 23:53:00Z moyo $
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2007 by the INDEPNET Development Team.

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
// Original Author of file: Walid Nouh
// Purpose of file:
// ----------------------------------------------------------------------


$NEEDED_ITEMS=array("entity","rulesengine","rule.ldap","rule.ocs","rule.tracking");

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

if (isset($_GET["ID"]))
{
	$generic_rule = new Rule;
	$generic_rule->getFromDB($_GET["ID"]);
	checkRight($generic_rule->right,"r");
	
	switch ($generic_rule->fields["rule_type"])
	{
		case RULE_OCS_AFFECT_COMPUTER :
			$rule = new OcsAffectEntityRule();
		break;		
		case RULE_AFFECT_RIGHTS :
			$rule = new RightAffectRule();
		break;
		case RULE_TRACKING_AUTO_ACTION:
			$rule = new TrackingBusinessRule();
		break;
	}
	
	include (GLPI_ROOT . "/front/rule.common.form.php");
}
?>
