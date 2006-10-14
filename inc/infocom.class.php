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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')){
	die("Sorry. You can't access directly to this file");
	}



class InfoCom extends CommonDBTM {


	function InfoCom () {
		$this->table="glpi_infocoms";
		$this->type=INFOCOM_TYPE;
	}

	function post_getEmpty () {
		global $cfg_glpi;
		$this->fields["alert"]=$cfg_glpi["infocom_alerts"];
	}

	// Specific Functions
	function getFromDBforDevice ($device_type,$ID) {

		global $db;
		$query = "SELECT * FROM glpi_infocoms WHERE (FK_device = '$ID' AND device_type='$device_type')";

		if ($result = $db->query($query)) {
			if ($db->numrows($result)==1){	
				$data = $db->fetch_assoc($result);

				foreach ($data as $key => $val) {
					$this->fields[$key] = $val;
				}
				return true;
			} else return false;
		} else {
			return false;
		}
	}

	function prepareInputForUpdate($input) {
		if (isset($input["ID"])){

			$this->getFromDB($input["ID"]);
		} else {
			if (!$this->getFromDBforDevice($input["device_type"],$input["FK_device"])){
				$input2["FK_device"]=$input["FK_device"];
				$input2["device_type"]=$input["device_type"];
				$this->add($input2);
				$this->getFromDBforDevice($input["device_type"],$input["FK_device"]);
			}
			$input["ID"]=$this->fields["ID"];
		}
		return $input;
	}

}

?>
