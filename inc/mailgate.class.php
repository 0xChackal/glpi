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


// CLASSES peripherals
require_once(GLPI_ROOT . "/lib/mail/receivemail.class.php");

class Mailgate  extends CommonDBTM {


	function Mailgate () {
		$this->table="glpi_mailgate";
		$this->type=MAILGATE_TYPE;
	}
	function prepareInputForUpdate($input) {

		if (isset ($input['mail_server']) && !empty ($input['mail_server']))
			$input["host"] = constructMailServerConfig($input);
		return $input;
	}

	function prepareInputForAdd($input) {

		if (isset ($input['mail_server']) && !empty ($input['mail_server']))
			$input["host"] = constructMailServerConfig($input);
		return $input;
	}

	function showForm ($target,$ID) {

		global $CFG_GLPI, $LANG;

		if (!haveRight("config","r")) return false;

		$spotted = false;

		if(empty($ID)) {
			if($this->getEmpty()) $spotted = true;
		} else {
			if($this->getfromDB($ID)) $spotted = true;
		}

		if ($spotted){

			echo "<div align='center'><form method='post' name=form action=\"$target\">";

			echo "<table class='tab_cadre' cellpadding='2'>";

			echo "<tr><th align='center' colspan='2'>";
			if (empty($ID))
				echo $LANG["mailgate"][1];
			else 
				echo $LANG["mailgate"][0].": ".$this->fields["ID"];

			echo "</th></tr>";

			echo "<tr class='tab_bg_2'><td>".$LANG["common"][16].":	</td><td>";
			autocompletionTextField("name","glpi_mailgate","name",$this->fields["name"],20);
			echo "</td></tr>";

			echo "<tr class='tab_bg_2'><td>".$LANG["entity"][0].":	</td><td>";
			dropdownValue("glpi_entities", "FK_entities",$this->fields["FK_entities"]);
			echo "</td></tr>";

			showMailServerConfig($this->fields["host"]);

			echo "<tr class='tab_bg_2'><td>".$LANG["login"][6].":	</td><td>";
			autocompletionTextField("login","glpi_mailgate","login",$this->fields["login"],20);
			echo "</td></tr>";

			echo "<tr class='tab_bg_2'><td>".$LANG["login"][7].":	</td><td>";
			autocompletionTextField("password","glpi_mailgate","password",$this->fields["password"],20);
			echo "</td></tr>";


			if (haveRight("config","w")) {

				echo "<tr class='tab_bg_1'>";
				if(empty($ID)){

					echo "<td valign='top' colspan='3'>";
					echo "<div align='center'><input type='submit' name='add' value=\"".$LANG["buttons"][8]."\" class='submit'></div>";
					echo "</td>";
					echo "</tr>";
				} else {

					echo "<td valign='top' align='center'>";
					echo "<input type='hidden' name='ID' value=\"$ID\">\n";
					echo "<input type='submit' name='update' value=\"".$LANG["buttons"][7]."\" class='submit'>";
					echo "</td>";
					echo "<td valign='top'>\n";
					echo "<div align='center'>";
					echo "<input type='submit' name='delete' value=\"".$LANG["buttons"][6]."\" class='submit'>";
					echo "</div>";
					echo "</td>";
					echo "</tr>";
					echo "<tr class='tab_bg_1'><td colspan='2' align='center'><input type='submit' name='get_mails' value=\"".$LANG["mailgate"][2]."\" class='submit'>";
					echo "</td></tr>";
				}


			}

			echo "</table></form></div>";

			return true;	
		}
		else {
			echo "<div align='center'><strong>".$LANG["common"][54]."</strong></div>";
			return false;
		}

	}
	
}


class MailCollect  extends receiveMail {
	var $entity;
	/**
	* Constructor
	*/
	function collect($server,$username,$password,$entity,$display=0){
			global $LANG;
			$this->entity=$entity;
			$this->set($username,$password,$server);
			//example receiveMail('abc@example.com','XXX','abc@example.com','mail.example.com','pop3','110');
			
			//Connect to the Mail Box
			$this->connect();
			if ($this->marubox){
				// Get Total Number of Unread Email in mail box
				$tot=$this->getTotalMails(); //Total Mails in Inbox Return integer value

//				if (isset ($tot))
//				{
						
					for($i=1;$i<=$tot;$i++)
					{
						$tkt= $this->buildTicket($i);
						$this->deleteMails($i); // Delete Mail from Mail box
						$track=new job;
						$track->add($tkt);
					}
							
//				}
				if ($display){
					$_SESSION["MESSAGE_AFTER_REDIRECT"].=$LANG["mailgate"][3].": $tot<br>";
				}
				
				$this->close_mailbox();   //Close Mail Box
			}
			else
			{
				if ($display){
					$_SESSION["MESSAGE_AFTER_REDIRECT"].= $LANG["log"][41]."<br>";
				}

				return 0;
			}
	} // end function MailCollect
	
	
	
	/* *** Primary Functions ***** *
	* Functions called directly from the Script Flow portion of the script. */
	
	
	/* function buildTicket - Builds,and returns, the major structure of the ticket to be entered . */
	function buildTicket($i)
	{
		global $DB;
	
		$head=$this->getHeaders($i);  // Get Header Info Return Array Of Headers **Key Are (subject,to,toOth,toNameOth,from,fromName)
		
/*		echo "<br>----------------------------------------- Header  -------------------------------------------------<BR>";
		echo "Subjects :: ".$head['subject']."<br>";
		echo "From :: ".$head['from']."<br>";
		echo "<br>----------------------------------------- BODY -------------------------------------------------<BR>";
		echo $this->getBody($i);  // Get Body Of Mail number Return String Get Mail id in interger
		/*$str=$obj->GetAttech($i,"./"); // Get attached File from Mail Return name of file in comma separated string  args. (mailid, Path to store file)  !! Not use for the moment !!
		$ar=explode(",",$str);
		foreach($ar as $key=>$value)
			echo ($value=="")?"":"Atteched File :: ".$value."<br>";
		*/
		
	
	
		$tkt= array ();
		
		//  Who is the user ?
		$query="SELECT ID from glpi_users WHERE email='".$head['from']."'";
		$result=$DB->query($query);
		$glpiID="";
			if ($result&&$DB->numrows($result))
				$glpiID=$DB->result($result,0,"ID");
		$tkt['author']=$glpiID;
		// Mail followup
		$tkt['uemail']=$head['from'];
		$tkt['emailupdates']=1;
		// Which entity ?
		$tkt['FK_entities']=$this->entity;
	
		//$tkt['Subject']= $head['subject'];   // not use for the moment
		$tkt['name']=$this->textCleaner($head['subject']);
		$tkt['priority']= "3";
		$tkt['device_type']="0";
		$tkt['request_type']="2";
			if (!seems_utf8($this->getBody($i))){
			$tkt['contents']= textBrut(utf8_encode($this->getBody($i)));	
			}else{
			$tkt['contents']= textBrut($this->getBody($i));
			}
		
		$tkt=addslashes_deep($tkt);
		
		return $tkt;
	}


	/* function textCleaner - Strip out unwanted/unprintable characters from the subject. */
	function textCleaner($text)
	{
		$text= str_replace("'", "", $text);
		$text= str_replace("=20", "\n", $text);
		return $text;
	}







}

?>
