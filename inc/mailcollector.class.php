<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2009 by the INDEPNET Development Team.

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

/// MailCollector class
// Merge with collect GLPI system after big modification in it
// modif and debug by  INDEPNET Development Team.
/* Original class ReceiveMail 1.0 by Mitul Koradia Created: 01-03-2006
 * Description: Reciving mail With Attechment
 * Email: mitulkoradia@gmail.com
 */
class MailCollector  extends CommonDBTM {

   // Specific one
   /// working charset of the mail
   var $charset="";
   /// IMAP / POP connection
   var $marubox='';
   /// ID of the current message
   var $mid = -1;
   /// structure used to store the mail structure
   var $structure = false;
   /// structure used to store files attached to a mail
   var $files;
   /// Message to add to body to build ticket
   var $addtobody;
   /// Number of fetched emails
   var $fetch_emails=0;
   /// Maximim number of emails to fetch
   var $maxfetch_emails=0;
   /// Max size for attached files
   var $filesize_max=0;


   static function getTypeName() {
      global $LANG;

      return $LANG['Menu'][39];
   }

   function canCreate() {
      return haveRight('config', 'w');
   }

   function canView() {
      return haveRight('config', 'r');
   }

   function post_getEmpty () {
      global $CFG_GLPI;

      $this->fields['filesize_max']=$CFG_GLPI['default_mailcollector_filesize_max'];
   $this->fields['is_active']=1;
   }

   function prepareInputForUpdate($input) {

      if (isset($input['password']) && empty($input['password'])) {
         unset($input['password']);
      }
      if (isset ($input['mail_server']) && !empty ($input['mail_server'])) {
         $input["host"] = constructMailServerConfig($input);
      }
      return $input;
   }

   function prepareInputForAdd($input) {

      if (isset ($input['mail_server']) && !empty ($input['mail_server'])) {
         $input["host"] = constructMailServerConfig($input);
      }
      return $input;
   }

   /**
    * Print the mailgate form
    *
    *@param $target filename : where to go when done.
    *@param $ID Integer : Id of the item to print
    *
    *@return boolean item found
    **/
   function showForm ($target,$ID) {
      global $CFG_GLPI, $LANG;

      if (!haveRight("config","r")) {
         return false;
      }
      if ($ID > 0) {
         $this->check($ID,'r');
      } else {
         // Create item
         $this->check(-1,'w');
      }

      echo "<div class='center'><form method='post' name=form action=\"$target\">";
      echo "<table class='tab_cadre'>";
      echo "<tr><th class='center'>";
      if (empty($ID)) {
         echo $LANG['mailgate'][1];
      } else {
         echo $LANG['mailgate'][0]."&nbsp;: ".$this->fields["id"];
      }
      echo "</th><th>";
      echo ($ID>0?$LANG['common'][26].": ".convDateTime($this->fields["date_mod"]):'&nbsp;');
      echo "</th></tr>";

      if (!function_exists('mb_list_encodings') || !function_exists('mb_convert_encoding')) {
         echo "<tr class='tab_bg_1'><td colspan='2'>";
         echo $LANG['mailgate'][4];
         echo "</td></tr>";
      }
      echo "<tr class='tab_bg_1'><td>".$LANG['common'][16]."&nbsp;:</td><td>";
      autocompletionTextField($this, "name");
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>".$LANG['entity'][0]."&nbsp;:</td><td>";
      Dropdown::show('Entity',
                     array('value'  => $this->fields["entities_id"],
                           'entity' => $_SESSION['glpiactiveentities']));
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>".$LANG['common'][60]."&nbsp;:</td><td>";
      Dropdown::showYesNo("is_active", $this->fields["is_active"]);
      echo "</td></tr>";

      showMailServerConfig($this->fields["host"]);

      echo "<tr class='tab_bg_1'><td>".$LANG['login'][6]."&nbsp;:</td><td>";
      autocompletionTextField($this, "login");
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>".$LANG['login'][7]."&nbsp;:</td><td>";
      echo "<input type='password' name='password' value='' size='20'>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td width='200px'> " . $LANG['mailgate'][7] . "&nbsp;:</td><td>";
      echo "<input type='text' size='15' name='filesize_max' value=\"" .
             $this->fields["filesize_max"] . "\">&nbsp;".$LANG['mailgate'][8]." - ".
             getSize($this->fields["filesize_max"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>".$LANG['common'][25]."&nbsp;:</td><td>";
      echo "<textarea cols='45' rows='5' name='comment' >".$this->fields["comment"]."</textarea>";
      echo "</td></tr>";

      if (haveRight("config","w")) {
         echo "<tr class='tab_bg_2'>";
         if (empty($ID)) {
            echo "<td class='top' colspan='2'>";
            echo "<div class='center'><input type='submit' name='add' value=\"".
                                       $LANG['buttons'][8]."\" class='submit'></div>";
            echo "</td></tr>";
         } else {
            echo "<td class='top center'>";
            echo "<input type='hidden' name='id' value=\"$ID\">";
            echo "<input type='submit' name='update' value=\"".$LANG['buttons'][7]."\" class='submit'>";
            echo "</td>";
            echo "<td class='top'><div class='center'>";
            echo "<input type='submit' name='delete' value=\"".$LANG['buttons'][6]."\" class='submit'>";
            echo "</div></td>";
            echo "</tr>";
            echo "<tr class='tab_bg_2'><td colspan='2' class='center'>";
            echo "<input type='submit' name='get_mails' value=\"".
                   $LANG['mailgate'][2]."\" class='submit'>";
            echo "</td></tr>";
         }
      }
      echo "</table></form></div>";
      return true;
   }

   function getSearchOptions() {
      global $LANG;

      $tab = array();
      $tab['common'] = $LANG['common'][16];

      $tab[1]['table']         = 'glpi_mailcollectors';
      $tab[1]['field']         = 'name';
      $tab[1]['linkfield']     = 'name';
      $tab[1]['name']          = $LANG['common'][16];
      $tab[1]['datatype']      = 'itemlink';
      $tab[1]['itemlink_type'] = 'MailCollector';

      $tab[80]['table']     = 'glpi_entities';
      $tab[80]['field']     = 'completename';
      $tab[80]['linkfield'] = 'entities_id';
      $tab[80]['name']      = $LANG['entity'][0];

      $tab[2]['table']     = 'glpi_mailcollectors';
      $tab[2]['field']     = 'is_active';
      $tab[2]['linkfield'] = 'is_active';
      $tab[2]['name']      = $LANG['common'][60];
      $tab[2]['datatype']  = 'bool';

      $tab[19]['table']     = 'glpi_mailcollectors';
      $tab[19]['field']     = 'date_mod';
      $tab[19]['linkfield'] = '';
      $tab[19]['name']      = $LANG['common'][26];
      $tab[19]['datatype']  = 'datetime';

      $tab[16]['table']     = 'glpi_mailcollectors';
      $tab[16]['field']     = 'comment';
      $tab[16]['linkfield'] = 'comment';
      $tab[16]['name']      = $LANG['common'][25];
      $tab[16]['datatype']  = 'text';


      return $tab;
   }


   /**
   * Constructor
   * @param $mailgateID ID of the mailgate
   * @param $display display messages in MessageAfterRedirect or just return error
   * @return if $display = false return messages result string
   */
   function collect($mailgateID,$display=0) {
      global $LANG;

      if ($this->getFromDB($mailgateID)) {
         $this->mid = -1;
         $this->fetch_emails = 0;
         //Connect to the Mail Box
         $this->connect();

         if ($this->marubox) {
            // Get Total Number of Unread Email in mail box
            $tot=$this->getTotalMails(); //Total Mails in Inbox Return integer value
            $error=0;
            for($i=1 ; $i<=$tot && $this->fetch_emails<=$this->maxfetch_emails ; $i++) {
               $tkt= $this->buildTicket($i);
               $tkt['_mailgate']=$mailgateID;
               $this->deleteMails($i); // Delete Mail from Mail box
               $result=imap_fetchheader($this->marubox,$i);
               // Is a mail responding of an already existgin ticket ?
               if (isset($tkt['tickets_id']) ) {
                  // Deletion of message with sucess
                  if (false === is_array($result)) {
                     $fup=new TicketFollowup();
                     $fup->add($tkt);
                  } else {
                     $error++;
                  }
               } else { // New ticket
                  // Deletion of message with sucess
                  if (false === is_array($result)) {
                     $track=new Ticket();
                     $track->add($tkt);
                  } else {
                     $error++;
                  }
               }
               $this->fetch_emails++;
            }
            imap_expunge($this->marubox);
            $this->close_mailbox();   //Close Mail Box

            if ($display) {
               if ($error==0) {
                  addMessageAfterRedirect($LANG['mailgate'][3]."&nbsp;: ".$this->fetch_emails);
               } else {
                  addMessageAfterRedirect($LANG['mailgate'][3]."&nbsp;: ".$this->fetch_emails.
                                          " ($error ".$LANG['common'][63].")",false,ERROR);
               }
            } else {
               return "Number of messages: available=$tot, collected=".$this->fetch_emails.
                       ($error>0?" ($error error(s))":"");
            }
         } else {
            if ($display) {
               addMessageAfterRedirect($LANG['log'][41],false,ERROR);
            } else {
               return "Could not connect to mailgate server";
            }
         }
      } else {
         if ($display) {
            addMessageAfterRedirect($LANG['common'][54]."&nbsp;: mailgate ".$mailgateID,false,ERROR);
         } else {
            return 'Could find mailgate '.$mailgateID;
         }
      }
   }

   /** function buildTicket - Builds,and returns, the major structure of the ticket to be entered .
   * @param $i mail ID
   * @return ticket fields array
   */
   function buildTicket($i) {
      global $DB,$LANG,$CFG_GLPI;

      $head=$this->getHeaders($i); // Get Header Info Return Array Of Headers
                                   // **Key Are (subject,to,toOth,toNameOth,from,fromName)
      $tkt= array ();

      // max size = 0 : no import attachments
      if ($this->fields['filesize_max']>0) {
         if (is_writable(GLPI_DOC_DIR."/_tmp/")) {
            $_FILES=$this->getAttached($i,GLPI_DOC_DIR."/_tmp/",$this->fields['filesize_max']);
         } else {
            logInFile('mailgate',GLPI_DOC_DIR."/_tmp/ is not writable");
         }
      }
      //  Who is the user ?
      $tkt['users_id']=0;
      $query="SELECT `id`
              FROM `glpi_users`
              WHERE `email`='".$head['from']."'";
      $result=$DB->query($query);
      if ($result && $DB->numrows($result)) {
         $tkt['users_id']=$DB->result($result,0,"id");
      }
      // AUto_import
      $tkt['_auto_import']=1;
      // For followup : do not check users_id = login user
      $tkt['_do_not_check_users_id']=1;
      $body=$this->getBody($i);
      // Do it before using charset variable
      $head['subject']=$this->decodeMimeString($head['subject']);

      if (!empty($this->charset)) {
         $body=encodeInUtf8($body,$this->charset);
      }
      if (!seems_utf8($body)) {
         $tkt['content']= encodeInUtf8($body);
      } else {
         $tkt['content']= $body;
      }
      // Add message from getAttached
      if ($this->addtobody) {
         $tkt['content'] .= $this->addtobody;
      }
      // Detect if it is a mail reply
      $glpi_message_match="/GLPI-([0-9]+)\.[0-9]+\.[0-9]+@\w*/";
      // See In-Reply-To field
      if (isset($head['in_reply_to'])) {
         if (preg_match($glpi_message_match,$head['in_reply_to'],$match)) {
            $tkt['tickets_id'] = (int)$match[1];
         }
      }
      // See in References
      if (!isset($tkt['tickets_id']) && isset($head['references'])) {
         if (preg_match($glpi_message_match,$head['references'],$match)) {
            $tkt['tickets_id'] = (int)$match[1];
         }
      }
      // See in title
      if (!isset($tkt['tickets_id']) && preg_match('/\[GLPI #(\d+)\]/',$head['subject'],$match)) {
         $tkt['tickets_id']=(int)$match[1];
      }
      // Found ticket link
      if ( isset($tkt['tickets_id']) ) {
         // it's a reply to a previous ticket
         $job=new Ticket();
         // Check if ticket  exists and users_id exists in GLPI
         /// TODO check if users_id have right to add a followup to the ticket
         if ($job->getFromDB($tkt['tickets_id'])
             && ($tkt['users_id'] > 0 || !strcasecmp($job->fields['user_email'],$head['from']))) {

            $content=explode("\n",$tkt['content']);
            $tkt['content']="";
            $first_comment=true;
            $to_keep=array();
            foreach($content as $ID => $val) {
               if (isset($val[0])&&$val[0]=='>') {
                  // Delete line at the top of the first comment
                  if ($first_comment) {
                     $first_comment=false;
                     if (isset($to_keep[$ID-1])) {
                        unset($to_keep[$ID-1]);
                     }
                  }
               } else {
                  // Detect a signature if already keep lines
                  $to_keep[$ID]=$ID;
               }
            }
            foreach($to_keep as $ID ) {
               $tkt['content'].=$content[$ID]."\n";
            }
         } else {
            unset($tkt['tickets_id']);
         }
      }
      if (! isset($tkt['tickets_id'])) {
         // Mail followup
         $tkt['user_email']=$head['from'];
         $tkt['use_email_notification']=1;
         // Which entity ?
         $tkt['entities_id']=$this->fields['entities_id'];;
         //$tkt['Subject']= $head['subject'];   // not use for the moment
         $tkt['name']=$this->textCleaner($head['subject']);
         // Medium
         $tkt['urgency']= "3";
         // No hardware associated
         $tkt['itemtype']="0";
         // Mail request type
      } else {
         // Reopen if needed
         $tkt['add_reopen']=1;
      }
      $tkt['requesttypes_id']=RequestType::getDefault('mail');
      $tkt['content']=clean_cross_side_scripting_deep(html_clean($tkt['content']));

      $tkt=addslashes_deep($tkt);
      return $tkt;
   }

   /** function textCleaner - Strip out unwanted/unprintable characters from the subject.
   * @param $text text to clean
   * @return clean text
   */
   function textCleaner($text) {
      $text= str_replace("=20", "\n", $text);
      return $text;
   }

   ///return supported encodings in lowercase.
   function mb_list_lowerencodings() {

      $r=mb_list_encodings();
      for ($n=sizeOf($r); $n--; ) {
         $r[$n]=utf8_strtolower($r[$n]);
      }
      return $r;
   }

   /**  Receive a string with a mail header and returns it
   // decoded to a specified charset.
   // If the charset specified into a piece of text from header
   // isn't supported by "mb", the "fallbackCharset" will be
   // used to try to decode it.
   * @param $mimeStr mime header string
   * @param $inputCharset input charset
   * @param $targetCharset target charset
   * @param $fallbackCharset charset used if input charset not supported by mb
   * @return decoded string
   */
   function decodeMimeString($mimeStr, $inputCharset='utf-8', $targetCharset='utf-8',
                             $fallbackCharset='iso-8859-1') {

      if (function_exists('mb_list_encodings') && function_exists('mb_convert_encoding')) {
         $encodings=$this->mb_list_lowerencodings();
         $inputCharset=utf8_strtolower($inputCharset);
         $targetCharset=utf8_strtolower($targetCharset);
         $fallbackCharset=utf8_strtolower($fallbackCharset);
         $decodedStr='';
         $mimeStrs=imap_mime_header_decode($mimeStr);
         for ($n=sizeOf($mimeStrs), $i=0; $i<$n; $i++) {
            $mimeStr=$mimeStrs[$i];
            $mimeStr->charset=utf8_strtolower($mimeStr->charset);
            if (($mimeStr == 'default' && $inputCharset == $targetCharset)
                || $mimeStr->charset == $targetCharset) {

               $decodedStr.=$mimeStr->text;
            } else {
               if (in_array($mimeStr->charset, $encodings)) {
                  $this->charset=$mimeStr->charset;
               }
               $decodedStr.=mb_convert_encoding($mimeStr->text, $targetCharset,
                  (in_array($mimeStr->charset, $encodings) ? $mimeStr->charset : $fallbackCharset));
            }
         }
         return $decodedStr;
      } else {
         return $mimeStr;
      }
   }

    ///Connect To the Mail Box
   function connect() {
      $this->marubox=@imap_open($this->fields['host'],$this->fields['login'],$this->fields['password'], 1);
   }

   /**
    * get the message structure if not already retrieved
    *
    * @param $mid : Message ID.
    *
    */
    function getStructure ($mid) {

      if ($mid != $this->mid || !$this->structure) {
         $this->structure = imap_fetchstructure($this->marubox,$mid);
         if ($this->structure) {
            $this->mid = $mid;
         }
      }
   }

   /**
   *This function is use full to Get Header info from particular mail
   *
   * @param $mid               = Mail Id of a Mailbox
   *
   * @return Return Associative array with following keys
   * subject   => Subject of Mail
   * to        => To Address of that mail
   * toOth     => Other To address of mail
   * toNameOth => To Name of Mail
   * from      => From address of mail
   * fromName  => Form Name of Mail
   */
   function getHeaders($mid) { // Get Header info

      $mail_header=imap_header($this->marubox,$mid);
      $sender=$mail_header->from[0];
      if (utf8_strtolower($sender->mailbox)!='mailer-daemon'
          && utf8_strtolower($sender->mailbox)!='postmaster') {

         $mail_details=array('from'=>utf8_strtolower($sender->mailbox).'@'.$sender->host,
                             'subject'=>$mail_header->subject);
         if (isset($mail_header->references)) {
            $mail_details['references'] = $mail_header->references;
         }
         if (isset($mail_header->in_reply_to)) {
            $mail_details['in_reply_to'] = $mail_header->in_reply_to;
         }
      }
      return $mail_details;
   }

   /**Get Mime type Internal Private Use
   * @param $structure mail structure
   * @return mime type
   */
   function get_mime_type(&$structure) {

      $primary_mime_type = array("TEXT",
                                 "MULTIPART",
                                 "MESSAGE",
                                 "APPLICATION",
                                 "AUDIO",
                                 "IMAGE",
                                 "VIDEO",
                                 "OTHER");
      if ($structure->subtype) {
         return $primary_mime_type[(int) $structure->type] . '/' . $structure->subtype;
      }
      return "TEXT/PLAIN";
   }

   /**Get Part Of Message Internal Private Use
   * @param $stream An IMAP stream returned by imap_open
   * @param $msg_number The message number
   * @param $mime_type mime type of the mail
   * @param $structure struture of the mail
   * @param $part_number The part number.
   * @return data of false if error
   */
   function get_part($stream, $msg_number, $mime_type, $structure = false, $part_number = false) {

      if ($structure) {
         if ($mime_type == $this->get_mime_type($structure)) {
            if (!$part_number) {
               $part_number = "1";
            }
            $text = imap_fetchbody($stream, $msg_number, $part_number);
            if ($structure->encoding == 3) {
               $text =  imap_base64($text);
            } else if($structure->encoding == 4) {
               $text =  imap_qprint($text);
            }
            //else { return $text; }
            if ($structure->subtype && $structure->subtype=="HTML") {
               $text = str_replace("\r","",$text);
               $text = str_replace("\n","",$text);
            }

            if (count($structure->parameters)>0) {
               foreach ($structure->parameters as $param) {
                  if ((strtoupper($param->attribute)=='CHARSET')
                      && function_exists('mb_convert_encoding')
                      && strtoupper($param->value) != 'UTF-8') {
                     $text = mb_convert_encoding($text, 'utf-8',$param->value);
                  }
               }
            }
            return $text;
         }
         if ($structure->type == 1) { /* multipart */
            $prefix="";
            reset($structure->parts);
            while (list($index, $sub_structure) = each($structure->parts)) {
               if ($part_number) {
                  $prefix = $part_number . '.';
               }
               $data = $this->get_part($stream, $msg_number, $mime_type, $sub_structure, $prefix .
                                       ($index + 1));
               if ($data) {
                  return $data;
               }
            }
         }
      }
      return false;
   }

   /**
    * used to get total unread mail from That mailbox
    *
    * Return :
    * Int Total Mail
    */
   function getTotalMails() {//Get Total Number off Unread Email In Mailbox

      $headers=imap_headers($this->marubox);
      return count($headers);
   }

   /**
   *GetAttech($mid,$path) / Prefer use getAttached
   *Save attached file from mail to given path of a particular location
   *
   * @param $mid mail id
   * @param $path path where to save
   *
   * @return  String of filename with coma separated
   *like a.gif,pio.jpg etc
   */
   function GetAttech($mid,$path) {

      $struckture = imap_fetchstructure($this->marubox,$mid);
      $ar="";
      if (isset($struckture->parts) && count($struckture->parts)>0) {
         foreach ($struckture->parts as $key => $value) {
            $enc=$struckture->parts[$key]->encoding;
            if ($struckture->parts[$key]->ifdparameters) {
               $name=$struckture->parts[$key]->dparameters[0]->value;
               $message = imap_fetchbody($this->marubox,$mid,$key+1);
               if ($enc == 0) {
                  $message = imap_8bit($message);
               }
               if ($enc == 1) {
                  $message = imap_8bit ($message);
               }
               if ($enc == 2) {
                  $message = imap_binary ($message);
               }
               if ($enc == 3) {
                  $message = imap_base64 ($message);
               }
               if ($enc == 4) {
                  $message = quoted_printable_decode($message);
               }
               if ($enc == 5) {
                  $message = $message;
               }
               $fp=fopen($path.$name,"w");
               fwrite($fp,$message);
               fclose($fp);
               $ar=$ar.$name.",";
            }
         }
      }
      $ar=substr($ar,0,(strlen($ar)-1));
      return $ar;
   }

   /**
    * Private function : Recursivly get attached documents
    *
    * @param $mid : message id
    * @param $path : temporary path
    * @param $maxsize : of document to be retrieved
    * @param $structure : of the message or part
    * @param $part : part for recursive
    *
    * Result is stored in $this->files
    *
    */
   function getRecursiveAttached ($mid, $path, $maxsize, $structure, $part="") {
      global $LANG;

      if ($structure->type == 1) { // multipart
         reset($structure->parts);
         while(list($index, $sub) = each($structure->parts)) {
            $this->getRecursiveAttached($mid, $path, $maxsize, $sub,
                                        ($part ? $part.".".($index+1) : ($index+1)));
         }
      } else {
         $filename='';

         if ($structure->ifdparameters) {
            // get filename of attachment if present
            // if there are any dparameters present in this part
            if (count($structure->dparameters)>0) {
               foreach ($structure->dparameters as $dparam) {
                  if ((utf8_strtoupper($dparam->attribute)=='NAME')
                      || (utf8_strtoupper($dparam->attribute)=='FILENAME')) {

                     $filename=$dparam->value;
                  }
               }
            }
         }

         //if no filename found
         if (empty($filename) && $structure->ifparameters) {
            // if there are any parameters present in this part
            if (count($structure->parameters)>0) {
               foreach ($structure->parameters as $param) {
                  if ((utf8_strtoupper($param->attribute)=='NAME')
                      || (utf8_strtoupper($param->attribute)=='FILENAME')) {

                     $filename=$param->value;
                  }
               }
            }
         }

         if (empty($filename) && $structure->type==5 && $structure->subtype) {
            // Embeded image come without filename - generate trivial one
            $filename = "image_$part.".$structure->subtype;
         }

         // if no filename found, ignore this part
         if (empty($filename)) {
            return false;
         }
         $filename=$this->decodeMimeString($filename);

         if ($structure->bytes > $maxsize) {
            $this->addtobody .= "<br>".$LANG['mailgate'][6]." (" .
                                getSize($structure->bytes) . "): ".$filename;
            return false;
         }
         if (!Document::isValidDoc($filename)) {
            $this->addtobody .= "<br>".$LANG['mailgate'][5]." (" .
                                $this->get_mime_type($structure) . "): ".$filename;
            return false;
         }
         if ($message=imap_fetchbody($this->marubox, $mid, $part)) {
            switch ($structure->encoding) {
               case 1 :
                  $message = imap_8bit($message);
                  break;

               case 2 :
                  $message = imap_binary($message);
                  break;

               case 3 :
                  $message = imap_base64($message);
                  break;

               case 4 :
                  $message = quoted_printable_decode($message);
                  break;
            }
            if (file_put_contents($path.$filename, $message)) {
               $this->files['multiple'] = true;
               $j = count($this->files)-1;
               $this->files[$j]['filename']['size'] = $structure->bytes;
               $this->files[$j]['filename']['name'] = $filename;
               $this->files[$j]['filename']['tmp_name'] = $path.$filename;
               $this->files[$j]['filename']['type'] = $this->get_mime_type($structure);
            }
         } // fetchbody
      } // Single part
   }

   /**
    * Public function : get attached documents in a mail
    *
    * @param $mid : message id
    * @param $path : temporary path
    * @param $maxsize : of document to be retrieved
    *
    * @return array like $_FILES
    *
    */
   function getAttached ($mid, $path, $maxsize) {

      $this->getStructure($mid);
      $this->files = array();
      $this->addtobody="";
      $this->getRecursiveAttached($mid, $path, $maxsize, $this->structure);
      return ($this->files);
   }

   /**
    * Get The actual mail content from this mail
    *
    * @param $mid : mail Id
    */
   function getBody($mid) {// Get Message Body

      $this->getStructure($mid);
      $body = $this->get_part($this->marubox, $mid, "TEXT/HTML", $this->structure);
      if ($body == "") {
         $body = $this->get_part($this->marubox, $mid, "TEXT/PLAIN", $this->structure);
      }
      if ($body == "") {
         return "";
      }
      return $body;
   }

   /**
    * Delete mail from that mail box
    *
    * @param $mid : mail Id
    */
   function deleteMails($mid) {
      imap_delete($this->marubox,$mid);
   }

   /**
    * Close The Mail Box
    *
    */
   function close_mailbox() {
      imap_close($this->marubox,CL_EXPUNGE);
   }

   static function cronInfo($name) {
      global $LANG;

      return array('description' => $LANG['crontask'][9],
                   'parameter'   => $LANG['crontask'][39]);
   }

   /**
    * Cron action on mailgate : retrieve mail and create tickets
    * @return -1 : done but not finish 1 : done with success
    **/
   static function cronMailgate($task) {
      global $DB;

      $query = "SELECT *
                FROM `glpi_mailcollectors`
                WHERE `is_active` = '1'";
      if ($result=$DB->query($query)) {
         $max = $task->fields['param'];
         if ($DB->numrows($result)>0) {
            $mc=new MailCollector();

            while ($max>0 && $data=$DB->fetch_assoc($result)) {
               $mc->maxfetch_emails = $max;

               $task->log("Collect mails from ".$data["host"]." for  ".
                           Dropdown::getDropdownName("glpi_entities",$data["entities_id"])."\n");
               $message=$mc->collect($data["id"]);

               $task->log("$message\n");
               $task->addVolume($mc->fetch_emails);

               $max -= $mc->fetch_emails;
            }
         }
         if ($max == $task->fields['param']) {
            return 0; // Nothin to do
         } else if ($max > 0) {
            return 1; // done
         }
         return -1; // still messages to retrieve
      }
      return 0;
   }

   function showSystemInformations($width) {
      global $LANG,$CFG_GLPI,$DB;

            echo "<tr class='tab_bg_2'><th>" . $LANG['setup'][704] .
         " / ". $LANG['mailgate'][0] ."</th></tr>\n";
      echo "<tr class='tab_bg_1'><td><pre>\n&nbsp;\n";

      $msg = $LANG['setup'][231].": ";
      switch($CFG_GLPI['smtp_mode']) {
         case MAIL_MAIL :
            $msg .= $LANG['setup'][650];
            break;

         case MAIL_SMTP :
            $msg .= $LANG['setup'][651];
            break;

         case MAIL_SMTPSSL :
            $msg .= $LANG['setup'][652];
            break;

         case MAIL_SMTPTLS :
            $msg .= $LANG['setup'][653];
            break;
      }
      if ($CFG_GLPI['smtp_mode'] != MAIL_MAIL) {
         $msg .= " (".(empty($CFG_GLPI['smtp_username'])?'':$CFG_GLPI['smtp_username']."@").
                    $CFG_GLPI['smtp_host'].")";
      }
      echo wordwrap($msg."\n", $width, "\n\t\t");

      echo $LANG['mailgate'][0]."\n";
      foreach ($DB->request('glpi_mailcollectors') as $mc) {
         $msg = "\t".$LANG['common'][16].':"'.$mc['name'].'"  ';
         $msg .= " ".$LANG['common'][52].':'.$mc['host'];
         $msg .= " ".$LANG['login'][6].':"'.$mc['login'].'"';
         $msg .= " ".$LANG['login'][7].':'.(empty($mc['password'])?$LANG['choice'][0]:$LANG['choice'][1]);
         $msg .= " ".$LANG['common'][60].':'.($mc['is_active']?$LANG['choice'][1]:$LANG['choice'][0]);
         echo wordwrap($msg."\n", $width, "\n\t\t");
      }
   }
}

?>