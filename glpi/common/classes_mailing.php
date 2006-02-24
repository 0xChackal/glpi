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
/**
 *  Mailing class for trackings
 */
class Mailing
{
	//! mailing type (new,attrib,followup,finish)
	var $type=NULL;
	/** Job class variable - job to be mailed
	* @see Job
	*/
	var $job=NULL;
	/** User class variable - user who make changes
	* @see User
	*/
	var $user=NULL;
 
	/**
	* Constructor
	* @param $type mailing type (new,attrib,followup,finish)
	* @param $job Job to mail
	* @param $user User who made change
	* @return nothing 
	*/
	function Mailing ($type="",$job=NULL,$user=NULL)
	{
		$this->type=$type;
		$this->job=$job;
		$this->user=$user;
	}
	/**
	* Determine if email is valid
	* @param $email email to check
	* @return boolean 
	*/
	function is_valid_email($email="")
	{
		if( !eregi( "^" .
			"[a-zA-Z0-9]+([_\\.-][a-zA-Z0-9]+)*" .    //user
            "@" .
            "([a-zA-Z0-9]+([\.-][a-zA-Z0-9]+)*)+" .   //domain
            "\\.[a-zA-Z0-9]{2,}" .                    //sld, tld 
            "$", $email)
                        )
        {
        //echo "Erreur: '$email' n'est pas une adresse mail valide!<br>";
        return false;
        }
		else return true;
	}

	/**
	* Give mails to send the mail
	* 
	* Determine email to send mail using global config and Mailing type
	*
	* @return array containing email
	*/
	function get_users_to_send_mail()
	{
		GLOBAL $db,$cfg_glpi;
		
		$emails=array();
		$nb=0;
				
		if (isset($cfg_glpi["mailing_".$this->type."_admin"])&&$cfg_glpi["mailing_".$this->type."_admin"]&&$this->is_valid_email($cfg_glpi["admin_email"])&&!in_array($cfg_glpi["admin_email"],$emails))
		{
			$emails[$nb]=$cfg_glpi["admin_email"];
			$nb++;
		}

		if (isset($cfg_glpi["mailing_".$this->type."_all_admin"])&&$cfg_glpi["mailing_".$this->type."_all_admin"])
		{
			$query = "SELECT email FROM glpi_users WHERE (".searchUserbyType("admin").")";
			if ($result = $db->query($query)) 
			{
				while ($row = $db->fetch_row($result))
				{
					// Test du format du mail et de sa non existance dans la table
					if ($this->is_valid_email($row[0])&&!in_array($row[0],$emails))
					{
						$emails[$nb]=$row[0];
						$nb++;
					}
				}
			}
		}	

		if (isset($cfg_glpi["mailing_".$this->type."_all_normal"])&&$cfg_glpi["mailing_".$this->type."_all_normal"])
		{
			$query = "SELECT email FROM glpi_users WHERE (".searchUserbyType("normal").")";
			if ($result = $db->query($query)) 
			{
				while ($row = $db->fetch_row($result))
				{
					// Test du format du mail et de sa non existance dans la table
					if ($this->is_valid_email($row[0])&&!in_array($row[0],$emails))
					{
						$emails[$nb]=$row[0];
						$nb++;
					}
				}
			}
		}	

		if (isset($cfg_glpi["mailing_".$this->type."_attrib"])&&$cfg_glpi["mailing_".$this->type."_attrib"]&&$this->job->fields["assign"])
		{
			$query2 = "SELECT email FROM glpi_users WHERE (ID = '".$this->job->fields["assign"]."')";
			if ($result2 = $db->query($query2)) 
			{
				if ($db->numrows($result2)==1)
				{
					$row2 = $db->fetch_row($result2);
					if ($this->is_valid_email($row2[0])&&!in_array($row2[0],$emails))
						{
							$emails[$nb]=$row2[0];
							$nb++;
						}
				}
			}
		}

		if (isset($cfg_glpi["mailing_".$this->type."_user"])&&$cfg_glpi["mailing_".$this->type."_user"]&&$this->job->fields["emailupdates"]=="yes")
		{
			if ($this->is_valid_email($this->job->fields["uemail"])&&!in_array($this->job->fields["uemail"],$emails))
			{
				$emails[$nb]=$this->job->fields["uemail"];
				$nb++;
			}
		}
		return $emails;
	}

	/**
	* Format the mail body to send
	* @return mail body string
	*/
	function get_mail_body()
	{
		global $cfg_glpi, $lang;
		
		// Create message body from Job and type
		$body="";
		
		if ($cfg_glpi["url_in_mail"]&&!empty($cfg_glpi["url_base"])){
			$body.=$lang["mailing"][1]."\n"; $body.="URL : ".$cfg_glpi["url_base"]."/index.php?redirect=tracking_".$this->job->fields["ID"]."\n";
			
			}
		
		$body.=$this->job->textDescription();
		if ($this->type!="new") $body.=$this->job->textFollowups();
		
		
		
		return $body;
	}

	/**
	* Format the mail subject to send
	* @return mail subject string
	*/
	function get_mail_subject()
	{
		GLOBAL $lang;
		
		// Create the message subject 
		$subject=sprintf("%s%07d%s","[GLPI #",$this->job->fields["ID"],"] ");
		
		switch ($this->type){
			case "new":
			$subject.=$lang["mailing"][9];
				break;
			case "attrib":
			$subject.=$lang["mailing"][12];
				break;
			case "followup":
			$subject.=$lang["mailing"][10];
				break;
			case "update":
			$subject.=$lang["mailing"][30];
				break;
			case "finish":
			$subject.=$lang["mailing"][11]." ".convDateTime($this->job->fields["closedate"]);			
				break;
			default :
			$subject.=$lang["mailing"][13];
				break;
		}
		
		
		
		return $subject;
	}
	
	/**
	* Get reply to address 
	* @return return mail
	*/
	function get_reply_to_address ()
	{
		GLOBAL $cfg_glpi;
	$replyto="";

	switch ($this->type){
			case "new":
				if ($this->is_valid_email($this->job->fields["uemail"])) $replyto=$this->job->fields["uemail"];
				else $replyto=$cfg_glpi["admin_email"];
				break;
			case "followup":
			case "update":
				if ($this->is_valid_email($this->user->fields["email"])) $replyto=$this->user->fields["email"];
				else $replyto=$cfg_glpi["admin_email"];
				break;
			default :
				$replyto=$cfg_glpi["admin_email"];
				break;
		}
	return $replyto;		
	}
	/**
	* Send mail function
	*
	* Construct email and send it
	*
	* @return mail subject string
	*/
	function send()
	{
		GLOBAL $cfg_glpi,$phproot;
		if ($cfg_glpi["mailing"])
		{
			if (!is_null($this->job)&&!is_null($this->user)&&(strcmp($this->type,"new")||strcmp($this->type,"attrib")||strcmp($this->type,"followup")||strcmp($this->type,"finish")))
			{
				// get users to send mail
				$users=$this->get_users_to_send_mail();
				// get body + signature OK
				$body=$this->get_mail_body()."\n-- \n".$cfg_glpi["mailing_signature"];
				$body=ereg_replace("<br />","",$body);
				$body=ereg_replace("<br>","",$body);

				// get subject OK
				$subject=$this->get_mail_subject();
				// get sender :  OK
				$sender= $cfg_glpi["admin_email"];
				// get reply-to address : user->email ou job_email if not set OK
				$replyto=$this->get_reply_to_address ();
				// Send all mails
				require_once($phproot."/glpi/common/MIMEMail.php");
				for ($i=0;$i<count($users);$i++)
				{
				$mmail=new MIMEMail();
		  		$mmail->ReplyTo($replyto);
		  		$mmail->From($sender);
		  		
		  		$mmail->To($users[$i]);
		  		$mmail->Subject($subject);		  
		  		$mmail->Priority(2);
		  		$mmail->MessageStream($body);
				
				// Attach a file
		  		// $mmail->AttachFile($FILE);
		  		
				// Notification reception
				// $mmail->setHeader('Disposition-Notification-To', "\"".$users[0]['name']."\" <".$users[0]['email'].">"); 
		  
		  		$mmail->Send();
				
				}
			} else {
				echo "Type d'envoi invalide";
			}
		}
	}
}

/**
 *  Mailing class for reservations
 */
class MailingResa{
	/** ReservationResa class variable
	* @see ReservationResa
	*/
	var $resa;	
	//! type of mailing (new, update, delete)
	var $type;

	/**
	* Constructor
	* @param $type mailing type (new,attrib,followup,finish)
	* @param $resa ReservationResa to mail
	* @return nothing 
	*/
	function MailingResa ($resa,$type="new")
	{
	$this->resa=$resa;
	$this->type=$type;

	}

	/**
	* Determine if email is valid
	* @param $email email to check
	* @return boolean 
	*/
	function is_valid_email($email="")
	{
		if( !eregi( "^" .
			"[a-zA-Z0-9]+([_\\.-][a-zA-Z0-9]+)*" .    //user
            "@" .
            "([a-zA-Z0-9]+([\.-][a-zA-Z0-9]+)*)+" .   //domain
            "\\.[a-zA-Z0-9]{2,}" .                    //sld, tld 
            "$", $email)
                        )
        {
        return false;
        }
		else return true;
	}

	/**
	* Give mails to send the mail
	* 
	* Determine email to send mail using global config and Mailing type
	*
	* @return array containing email
	*/
	function get_users_to_send_mail()
	{
		GLOBAL $db,$cfg_glpi;
		
		$emails=array();
		$nb=0;
		
		if ($cfg_glpi["mailing_resa_admin"]&&$this->is_valid_email($cfg_glpi["admin_email"])&&!in_array($cfg_glpi["admin_email"],$emails))
		{
			$emails[$nb]=$cfg_glpi["admin_email"];
			$nb++;
		}

		if ($cfg_glpi["mailing_resa_all_admin"])
		{
			$query = "SELECT email FROM glpi_users WHERE (".searchUserbyType("admin").")";
			if ($result = $db->query($query)) 
			{
				while ($row = $db->fetch_row($result))
				{
					// Test du format du mail et de sa non existance dans la table
					if ($this->is_valid_email($row[0])&&!in_array($row[0],$emails))
					{
						$emails[$nb]=$row[0];
						$nb++;
					}
				}
			}
		}	

		if ($cfg_glpi["mailing_resa_user"])
		{
			$user = new User;
			if ($user->getFromDBbyID($this->resa->fields["id_user"]))
			if ($this->is_valid_email($user->fields["email"])&&!in_array($user->fields["email"],$emails))
			{
				$emails[$nb]=$user->fields["email"];
				$nb++;
			}
		}
		return $emails;
	}

	/**
	* Format the mail body to send
	* @return mail body string
	*/
	function get_mail_body()
	{
		// Create message body from Job and type
		$body="";
		
		$body.=$this->resa->textDescription();
		
		return $body;
	}
	/**
	* Format the mail subject to send
	* @return mail subject string
	*/
	function get_mail_subject()
	{
		GLOBAL $lang;
		
		// Create the message subject 
		if ($this->type=="new")
		$subject="[GLPI] ".$lang["mailing"][19];
		else if ($this->type=="update") $subject="[GLPI] ".$lang["mailing"][23];
		else if ($this->type=="delete") $subject="[GLPI] ".$lang["mailing"][29];
		
		return $subject;
	}
	
	/**
	* Get reply to address 
	* @return return mail
	*/
	function get_reply_to_address ()
	{
		GLOBAL $cfg_glpi;
	$replyto="";

	$user = new User;
	if ($user->getFromDB($this->resa->fields["id_user"])){
		if ($this->is_valid_email($user->fields["email"])) $replyto=$user->fields["email"];		
		else $replyto=$cfg_glpi["admin_email"];
	}
	else $replyto=$cfg_glpi["admin_email"];		
		
	return $replyto;		
	}
	/**
	* Send mail function
	*
	* Construct email and send it
	*
	* @return mail subject string
	*/
	function send()
	{
		GLOBAL $cfg_glpi,$phproot;
		if ($cfg_glpi["mailing"]&&$this->is_valid_email($cfg_glpi["admin_email"]))
		{
				// get users to send mail
				$users=$this->get_users_to_send_mail();
				// get body + signature OK
				$body=$this->get_mail_body()."\n-- \n".$cfg_glpi["mailing_signature"];
				$body=ereg_replace("<br />","",$body);
				$body=ereg_replace("<br>","",$body);

				// get subject OK
				$subject=$this->get_mail_subject();
				// get sender :  OK
				$sender= $cfg_glpi["admin_email"];
				// get reply-to address : user->email ou job_email if not set OK
				$replyto=$this->get_reply_to_address ();

				// Send all mails
				require_once($phproot."/glpi/common/MIMEMail.php");
				for ($i=0;$i<count($users);$i++)
				{
				$mmail=new MIMEMail();
		  		$mmail->ReplyTo($replyto);
		  		$mmail->From($sender);
		  		
		  		$mmail->To($users[$i]);
		  		$mmail->Subject($subject);		  
		  		$mmail->Priority(2);
		  		$mmail->MessageStream($body);
				
				// Attach a file
		  		// $mmail->AttachFile($FILE);
		  		
				// Notification reception
				// $mmail->setHeader('Disposition-Notification-To', "\"".$users[0]['name']."\" <".$users[0]['email'].">"); 
		  
		  		$mmail->Send();
				}
		}
	}
	
}


?>
