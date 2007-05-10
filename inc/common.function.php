<?php
/*
 * @version $Id$
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
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------


if (!defined('GLPI_ROOT')){
	die("Sorry. You can't access directly to this file");
	}

//******************************************************************************************************
//******************************************************************************************************
//********************************  Fonctions diverses ************************************
//******************************************************************************************************
//******************************************************************************************************

	// Fonction spéciale de gestion des erreurs
	function userErrorHandler($errno, $errmsg, $filename, $linenum, $vars){
		// Date et heure de l'erreur
		$dt = date("Y-m-d H:i:s (T)");
		$errortype = array (
			E_ERROR              => 'Error',
			E_WARNING            => 'Warning',
			E_PARSE              => 'Parsing Error',
			E_NOTICE             => 'Notice',
			E_CORE_ERROR         => 'Core Error',
			E_CORE_WARNING       => 'Core Warning',
			E_COMPILE_ERROR      => 'Compile Error',
			E_COMPILE_WARNING    => 'Compile Warning',
			E_USER_ERROR         => 'User Error',
			E_USER_WARNING       => 'User Warning',
			E_USER_NOTICE        => 'User Notice',
			E_STRICT             => 'Runtime Notice',
			E_RECOVERABLE_ERROR  => 'Catchable Fatal Error'
			);			
		// Les niveaux qui seront enregistrés
		$user_errors = array(E_USER_ERROR, E_USER_WARNING, E_USER_NOTICE);
			
		$err = "<errorentry>\n";
		$err .= "\t<datetime>" . $dt . "</datetime>\n";
		$err .= "\t<errornum>" . $errno . "</errornum>\n";
		$err .= "\t<errortype>" . $errortype[$errno] . "</errortype>\n";
		$err .= "\t<errormsg>" . $errmsg . "</errormsg>\n";
		$err .= "\t<scriptname>" . $filename . "</scriptname>\n";
		$err .= "\t<scriptlinenum>" . $linenum . "</scriptlinenum>\n";
		
		if (in_array($errno, $user_errors)) {
			$err .= "\t<vartrace>".wddx_serialize_value($vars,"Variables")."</vartrace>\n";
		}
		$err .= "</errorentry>\n\n";
		
		// sauvegarde de l'erreur, et mail si c'est critique
		error_log($err, 3, GLPI_LOG_DIR."/php-errors.log");
		echo '<div style="position:fload-left; background-color:red; z-index:10000">PHP ERROR : ';
		echo $errmsg." in ".$filename." at line ".$linenum;
		echo '</div>';
	}


function utf8_substr($str,$start)
{
   preg_match_all("/./su", $str, $ar);

   if(func_num_args() >= 3) {
       $end = func_get_arg(2);
       return join("",array_slice($ar[0],$start,$end));
   } else {
       return join("",array_slice($ar[0],$start));
   }
}
/**
 * Clean cache cron function
 *
 **/
function cron_logs(){

	global $CFG_GLPI,$DB;

	// Expire Event Log
	if ($CFG_GLPI["expire_events"] > 0) {
		$secs = $CFG_GLPI["expire_events"] * DAY_TIMESTAMP;
		$query_exp = "DELETE FROM glpi_event_log WHERE UNIX_TIMESTAMP(date) < UNIX_TIMESTAMP()-$secs";
		$DB->query($query_exp);
	}
	
}

/**
 * Clean cache cron function
 *
 **/
function cron_cache(){
	global $CFG_GLPI;
	$max_recursion=5;
	$lifetime=DEFAULT_CACHE_LIFETIME;
	while ($max_recursion>0&&(($size=filesizeDirectory(GLPI_CACHE_DIR))>MAX_CACHE_SIZE)){
		$cache_options = array(
			'cacheDir' => GLPI_CACHE_DIR,
			'lifeTime' => $lifetime,
			'automaticSerialization' => true,
			'caching' => $CFG_GLPI["use_cache"],
			'hashedDirectoryLevel' => 2,
			'fileLocking' => CACHE_FILELOCKINGCONTROL,
			'writeControl' => CACHE_WRITECONTROL,
			'readControl' => CACHE_READCONTROL,
		);
		$cache = new Cache_Lite($cache_options);
		$cache->clean(false,"old");
		$lifetime/=2;
		$max_recursion--;
	}
	if ($max_recursion>0)
		return 1;
	else return -1;
}

/**
 * Garbage collector for expired file session
 *
 **/
function cron_session()
	{
		
		// max time to keep the file session
		$maxlifetime = session_cache_expire();
				
		foreach (glob(GLPI_SESSION_DIR."/sess_*") as $filename) {
			if (filemtime($filename) + $maxlifetime < time()) {
				// Delete session file if not delete before
				@unlink($filename);
			}
		}
			return true;
	}




/**
 * Get the filesize of a complete directory (from php.net)
 *
 * @param $path directory or file to get size
 * @return size of the $path
 **/
function filesizeDirectory($path)
   {
       if(!is_dir($path)) return filesize($path);
   if ($handle = opendir($path)) {
       $size = 0;
       while (false !== ($file = readdir($handle))) {
           if($file!='.' && $file!='..'){
                   $size += filesize($path.'/'.$file);
               $size += filesizeDirectory($path.'/'.$file);
           }
       }
       closedir($handle);
       return $size;
   }
}


/**
 * Clean cache function
 *
 * @param $group group to clean (if not set clean all the cache)
 * @return nothing
 **/
function cleanCache($group=""){

	include_once (GLPI_ROOT."/lib/cache_lite/Lite.php");

	$cache_options = array(
		'cacheDir' => GLPI_CACHE_DIR,
		'lifeTime' => 0,
		'hashedDirectoryLevel' => 2,
		'fileLocking' => CACHE_FILELOCKINGCONTROL,
		'writeControl' => CACHE_WRITECONTROL,
		'readControl' => CACHE_READCONTROL,
	);
	$CACHE = new Cache_Lite($cache_options);
	if (empty($group)){
		$CACHE->clean();
	} else {
		$CACHE->clean($group,"ingroup");
	}

}

/**
 * Clean cache function for relations using a specific table
 *
 * @param $table table used. Need to clean all cache using this table
 * @return nothing
 **/
function cleanRelationCache($table){
	global $LINK_ID_TABLE;
	$RELATION=getDbRelations();
	if (isset($RELATION[$table])){
		foreach ($RELATION[$table] as $tablename => $field){
			if ($key=array_search($tablename,$LINK_ID_TABLE)){
				cleanCache("GLPI_$key");
			}
		}
	}
}

/**
 * Clean all dict cache
 *
 * @param $group cache group
 * @param $item  remove cache for item
 * @return nothing
 **/
function cleanAllItemCache($item,$group){
	global $CFG_GLPI;

	foreach ($CFG_GLPI["languages"] as $key => $val){
		// clean main sheet
		$CFG_GLPI["cache"]->remove($item."_".$key,$group);
	}
}


/**
 * Get the SEARCH_OPTION array using cache
 *
 * @return the SEARCH_OPTION array
 **/
function getSearchOptions(){
	global $LANG,$CFG_GLPI;
	$options = array(
		'cacheDir' => GLPI_CACHE_DIR,
		'lifeTime' => DEFAULT_CACHE_LIFETIME,
		'automaticSerialization' => true,
		'caching' => $CFG_GLPI["use_cache"],
		'hashedDirectoryLevel' => 2,
		'masterFile' => GLPI_ROOT . "/inc/search.constant.php",
		'fileLocking' => CACHE_FILELOCKINGCONTROL,
		'writeControl' => CACHE_WRITECONTROL,
		'readControl' => CACHE_READCONTROL,
	);
	$cache = new Cache_Lite_File($options);

	// Set a id for this cache : $file
	if (!($SEARCH_OPTION = $cache->get("OPTIONS","GLPI_SEARCH_".$_SESSION["glpilanguage"]))) {
		// Cache miss !
		// Put in $SEARCH_OPTION datas to put in cache
		include (GLPI_ROOT . "/inc/search.constant.php");
		$cache->save($SEARCH_OPTION,"OPTIONS","GLPI_SEARCH_".$_SESSION["glpilanguage"]);
	}

	return $SEARCH_OPTION;
}

/**
 * Get the $RELATION array using cache. It's defined all relations between tables in the DB.
 *
 * @return the $RELATION array
 **/
function getDbRelations(){
	global $CFG_GLPI;
	$options = array(
		'cacheDir' => GLPI_CACHE_DIR,
		'lifeTime' => DEFAULT_CACHE_LIFETIME,
		'automaticSerialization' => true,
		'caching' => $CFG_GLPI["use_cache"],
		'hashedDirectoryLevel' => 2,
		'masterFile' => GLPI_ROOT . "/inc/relation.constant.php",
		'fileLocking' => CACHE_FILELOCKINGCONTROL,
		'writeControl' => CACHE_WRITECONTROL,
		'readControl' => CACHE_READCONTROL,
	);
	$cache = new Cache_Lite_File($options);

	// Set a id for this cache : $file
	if (!($RELATION = $cache->get("OPTIONS","GLPI_RELATION"))) {
		// Cache miss !
		// Put in $SEARCH_OPTION datas to put in cache
		include (GLPI_ROOT . "/inc/relation.constant.php");
		$cache->save($RELATION,"OPTIONS","GLPI_RELATION");
	}

	return $RELATION;
}

/**
 * Check Write Access to a directory
 *
 * @param $dir directory to check
 * @return 2 : creation error 1 : delete error 0: OK
 **/
function testWriteAccessToDirectory($dir){
	$fp = fopen($dir . "/test_glpi.txt",'w');

	if (empty($fp)) {
		return 2;
	}
	else {
		$fw = fwrite($fp,"This file was created for testing reasons. ");
		fclose($fp);
		$delete = unlink($dir . "/test_glpi.txt");
		if (!$delete) {
			return 1;
		}
	}
	return 0;
}

function checkWriteAccessToDirs(){
		global $LANG;
		$dir_to_check=array(
			GLPI_DUMP_DIR => $LANG["install"][16],
			GLPI_DOC_DIR => $LANG["install"][21],
			GLPI_CONFIG_DIR => $LANG["install"][23],
			GLPI_SESSION_DIR => $LANG["install"][50],
			GLPI_CRON_DIR => $LANG["install"][52],
			GLPI_CACHE_DIR => $LANG["install"][99],
		);
		$error=0;	
		foreach ($dir_to_check as $dir => $message){
			echo "<tr class='tab_bg_1'><td><strong>".$message."</strong></td>";
			$tmperror=testWriteAccessToDirectory($dir);
	
			switch($tmperror){
				// Error on creation
				case 2 :
					echo "<td><p class='red'>".$LANG["install"][17]."</p> ".$LANG["install"][97]."'".$dir."'. ".$LANG["install"][98]."</td></tr>";
					$error=2;
					break;
				case 1 :
					echo "<td><p class='red'>".$LANG["install"][19]."</p> ".$LANG["install"][97]."'".$dir."'. ".$LANG["install"][98]."</td></tr>";
					$error=1;
					break;
				default :
					echo "<td>".$LANG["install"][20]."</td></tr>";
					break;
			}
		}
	return $error;
}

/**
 * Give name of the device
 *
 *
 *
 * @param $ID ID of the device type
 * @return string name of the device type in the current lang
 *
 */
function getDeviceTypeName($ID){
	global $LANG;
	switch ($ID){
		case COMPUTER_TYPE : return $LANG["help"][25];break;
		case NETWORKING_TYPE : return $LANG["help"][26];break;
		case PRINTER_TYPE : return $LANG["help"][27];break;
		case MONITOR_TYPE : return $LANG["help"][28];break;
		case PERIPHERAL_TYPE : return $LANG["help"][29];break;
		case SOFTWARE_TYPE : return $LANG["help"][31];break;
		case CARTRIDGE_TYPE : return $LANG["Menu"][21];break;
		case CONTACT_TYPE : return $LANG["Menu"][22];break;
		case ENTERPRISE_TYPE : return $LANG["Menu"][23];break;
		case CONTRACT_TYPE : return $LANG["Menu"][25];break;
		case CONSUMABLE_TYPE : return $LANG["Menu"][32];break;
	}

}

/**
 * Strip slash  for variable & array
 *
 *
 *
 * @param $value item to stripslashes (array or string)
 * @return stripslashes item
 *
 */
function stripslashes_deep($value) {
	$value = is_array($value) ?
		array_map('stripslashes_deep', $value) :
			(is_null($value) ? NULL : stripslashes($value));

	return $value;
}

/**
 *  Add slash for variable & array 
 *
 *
 *
 * @param $value value to add slashes (array or string)
 * @return addslashes value
 *
 */
function addslashes_deep($value) {
	$value = is_array($value) ?
		array_map('addslashes_deep', $value) :
			(is_null($value) ? NULL : addslashes($value));
	return $value;
}

/**
 * Prevent from XSS
 * Clean code 
 *
 *
 * @param $value item to prevent (array or string)
 * @return clean item
 $
 * @see unclean_cross_side_scripting_deep*
 */
function clean_cross_side_scripting_deep($value) {
	$in=array("<",">");
	$out=array("&lt;","&gt;");
	$value = is_array($value) ?
		array_map('clean_cross_side_scripting_deep', $value) :
			(is_null($value) ? NULL : str_replace($in,$out,$value));
	return $value;
}
/**
 *  
 *  Invert fonction from clean_cross_side_scripting_deep
 *
 *
 * @param $value item to unclean from clean_cross_side_scripting_deep
 * @return unclean item
 * @see clean_cross_side_scripting_deep
 *
 */
function unclean_cross_side_scripting_deep($value) {
	$in=array("<",">");
	$out=array("&lt;","&gt;");
	$value = is_array($value) ?
		array_map('clean_cross_side_scripting_deep', $value) :
			(is_null($value) ? NULL : str_replace($out,$in,$value));
	return $value;
}
/**
 *  Utf8 decode for variable & array
 *
 *
 *
 * @param $value item to utf8_decode (array or string)
 * @return decoded item
 *
 */
function utf8_decode_deep($value) {
	$value = is_array($value) ?
		array_map('utf8_decode_deep', $value) :
			(is_null($value) ? NULL : utf8_decode($value));
	return $value;

}

/**
 *  Resume test for followup
 *
 * @param $string
 * @param $length
 * @return cut string
 *
 */
function resume_text($string,$length=255){
	
	if (strlen($string)>$length){
		$string=utf8_substr($string,0,$length)."&nbsp;(...)";
	}

	return $string;
}

/**
 *  Tag suppression
 *
 * @param $string
 * @param rempl
 * @return clean string
 *
 */
//  Inspired From SPIP
// Suppression basique et brutale de tous les <...>
function removeTags($string, $rempl = "") {
	$string = preg_replace(",<[^>]*>,U", $rempl, $string);
	// ne pas oublier un < final non ferme
	// mais qui peut aussi etre un simple signe plus petit que
	$string = str_replace('<', ' ', $string);
	return $string;
}

/**
 *  convert html text to 
 *
 * @param $string
 * @return clean string
 *
 */
//  Inspired from SPIP
function textBrut($string) {

	$string = preg_replace("/\s+/u", " ", $string);
	$string = preg_replace("/<(p|br)( [^>]*)?".">/i", "\n\n", $string);
	$string = preg_replace("/^\n+/", "", $string);
	$string = preg_replace("/\n+$/", "", $string);
	$string = preg_replace("/\n +/", "\n", $string);
	$string = removeTags($string);
	$string = preg_replace("/(&nbsp;| )+/", " ", $string);
	// nettoyer l'apostrophe curly qui pose probleme a certains rss-readers, lecteurs de mail...
	$string = str_replace("&#8217;","'",$string);
	return $string;
}




/**
 * Convert a date YY-MM-DD to DD-MM-YY for display in a html table
 *
 *
 *
 * @param $time
 * @return $time or $date
 *
 */
function convDateTime($time) { 
	global $CFG_GLPI;
	if (is_null($time)) return $time;
	if ($CFG_GLPI["dateformat"]!=0) {
		$date = substr($time,8,2)."-";        // jour 
		$date = $date.substr($time,5,2)."-";  // mois 
		$date = $date.substr($time,0,4). " "; // année 
		$date = $date.substr($time,11,5);     // heures et minutes 
		return $date; 
	}else {
		return $time;
	}
}

/**
 * Convert a date YY-MM-DD to DD-MM-YY for calendar
 *
 *
 *
 * @param $time
 * @return $time or $date
 *
 */
function convDate($time) { 
	global $CFG_GLPI;
	if (is_null($time)) return $time;
	if ($CFG_GLPI["dateformat"]!=0) {
		$date = substr($time,8,2)."-";        // jour 
		$date = $date.substr($time,5,2)."-";  // mois 
		$date = $date.substr($time,0,4); // année 
		//$date = $date.substr($time,11,5);     // heures et minutes 
		return $date; 
	}else {
		return $time;
	}
}
/**
 *  Send a file to the navigator
 *
 * @param $file
 * @param $filename
 * @return nothing
 */
function sendFile($file,$filename){
	global $DB;

	// Test securite : document in DOC_DIR

	
	$tmpfile=ereg_replace(GLPI_DOC_DIR,"",$file);
	if (ereg("\.\.",$tmpfile)){
		echo "Security attack !!!";
		logEvent($file, "sendFile", 1, "security", $_SESSION["glpiname"]." try to get a non standard file.");
		return;
	}

	if (!file_exists($file)){
		echo "Error file $file does not exist";
		return;
	} else {
		$splitter=split("/",$file);
		$filedb=$splitter[count($splitter)-2]."/".$splitter[count($splitter)-1];
		$query="SELECT mime from glpi_docs WHERE filename LIKE '$filedb'";
		$result=$DB->query($query);
		$mime="application/octetstream";
		if ($result&&$DB->numrows($result)==1){
			$mime=$DB->result($result,0,0);

		} else {
			// fichiers DUMP SQL et XML
			if ($splitter[count($splitter)-2]=="dump"){
				$splitter2=split("\.",$file);
				switch ($splitter2[count($splitter2)-1]) {
					case "sql" : 
						$mime="text/x-sql";
					break;
					case "xml" :
						$mime="text/xml";
					break;
				}
			} else {
				// Cas particulier
				switch ($splitter[count($splitter)-2]) {
					case "SQL" : 
						$mime="text/x-sql";
					break;
					case "XML" :
						$mime="text/xml";
					break;
				}
			}

		}

		// Now send the file with header() magic
		header("Expires: Mon, 26 Nov 1962 00:00:00 GMT");
		header('Pragma: private'); /// IE BUG + SSL
		//header('Pragma: no-cache'); 
		header('Cache-control: private, must-revalidate'); /// IE BUG + SSL
		header("Content-disposition: filename=\"$filename\"");
		header("Content-type: ".$mime);





		$f=fopen($file,"r");

		if (!$f){
			echo "Error opening file $file";
		} else {
			// Pour que les \x00 ne devienne pas \0
			$mc=get_magic_quotes_runtime();
			if ($mc) @set_magic_quotes_runtime(0); 

			echo fread($f, filesize($file));

			if ($mc) @set_magic_quotes_runtime($mc); 
		}

	}
}

/**
 * Convert a value in byte, kbyte, megabyte etc...
 *
 *
 *
 * @param $val
 * @return $val
 *
 */
function return_bytes_from_ini_vars($val) {
	$val = trim($val);
	$last = strtolower($val{strlen($val)-1});
	switch($last) {
		// Le modifieur 'G' est disponible depuis PHP 5.1.0
		case 'g':
			$val *= 1024;
		case 'm':
			$val *= 1024;
		case 'k':
			$val *= 1024;
	}

	return $val;
}

/**
 * Header redirection hack
 *
 *
 *
 * @param $dest Redirection destination 
 * @return nothing
 *
 */
function glpi_header($dest){
	echo "<script language=javascript>window.location=\"".$dest."\"</script>";
	exit();
}

/**
 * Call cron
 *
 *
 *
 * 
 * @return nothing
 *
 */
function callCron(){
	if (ereg("front",$_SERVER['PHP_SELF'])){
		echo "<div style=\"background-image: url('cron.php');\"></div>";
	} else {
		echo "<div style=\"background-image: url('front/cron.php');\"></div>";
	}
}




/**
 * Get hour from sql
 *
 *
 *
 * @param $time
 * @return  array
 *
 */
function get_hour_from_sql($time){
	$t=explode(" ",$time);
	$p=explode(":",$t[1]);
	return $p[0].":".$p[1];
}

/**
 *  Optimize sql table
 *
 *
 *
 * @return nothing
 *
 */
function optimize_tables (){

	global $DB;
	$result=$DB->list_tables();
	while ($line = $DB->fetch_array($result))
	{
		if (ereg("glpi_",$line[0])){
			$table = $line[0];
			$query = "OPTIMIZE TABLE ".$table." ;";
			$DB->query($query);
		}
	}
	$DB->free_result($result);
}



function seems_utf8($Str) {
	for ($i=0; $i<strlen($Str); $i++) {
		if (ord($Str[$i]) < 0x80) continue; # 0bbbbbbb
			elseif ((ord($Str[$i]) & 0xE0) == 0xC0) $n=1; # 110bbbbb
				elseif ((ord($Str[$i]) & 0xF0) == 0xE0) $n=2; # 1110bbbb
				elseif ((ord($Str[$i]) & 0xF8) == 0xF0) $n=3; # 11110bbb
				elseif ((ord($Str[$i]) & 0xFC) == 0xF8) $n=4; # 111110bb
				elseif ((ord($Str[$i]) & 0xFE) == 0xFC) $n=5; # 1111110b
		else return false; # Does not match any model
			for ($j=0; $j<$n; $j++) { # n bytes matching 10bbbbbb follow ?
				if ((++$i == strlen($Str)) || ((ord($Str[$i]) & 0xC0) != 0x80))
					return false;
			}
	}
	return true;
}



//*************************************************************************************************************
// De jolies fonctions pour am�iorer l'affichage du texte de la FAQ/knowledgbase
// obsol�e since 0.68 but DONT DELETE  THIS SECTION !!
// USED IN THE UPDATE SCRIPT
//************************************************************************************************************

/**
 *Met en "ordre" une chaine avant affichage
 * Remplace tr� AVANTAGEUSEMENT nl2br 
 * 
 * @param $pee
 * @param $br
 * 
 * @return $string
 */
function autop($pee, $br=1) {

	// Thanks  to Matthew Mullenweg

	$pee = preg_replace("/(\r\n|\n|\r)/", "\n", $pee); // cross-platform newlines
	$pee = preg_replace("/\n\n+/", "\n\n", $pee); // take care of duplicates
	$pee = preg_replace('/\n?(.+?)(\n\n|\z)/s', "<p>$1</p>\n", $pee); // make paragraphs, including one at the end
	if ($br) $pee = preg_replace('|(?<!</p>)\s*\n|', "<br>\n", $pee); // optionally make line breaks
	return $pee;
}


/**
 * Rend une url cliquable htp/https/ftp meme avec une variable Get
 *
 * @param $chaine
 * 
 * 
 * 
 * @return $string
 */
function clicurl($chaine){

	$text=preg_replace("`((?:https?|ftp)://\S+)(\s|\z)`", '<a href="$1">$1</a>$2', $chaine); 

		return $text;
}

/**
 * Split the message into tokens ($inside contains all text inside $start and $end, and $outside contains all text outside)
 *
 * @param $text
 * @param $start
 * @param $end
 * 
 * @return array 
 */
function split_text($text, $start, $end)
{

	// Adapt�de PunBB 
	//Copyright (C)  Rickard Andersson (rickard@punbb.org)

	$tokens = explode($start, $text);

	$outside[] = $tokens[0];

	$num_tokens = count($tokens);
	for ($i = 1; $i < $num_tokens; ++$i)
	{
		$temp = explode($end, $tokens[$i]);
		$inside[] = $temp[0];
		$outside[] = $temp[1];
	}



	return array($inside, $outside);
}


/**
 * Replace bbcode in text by html tag
 *
 * @param $string
 * 
 * 
 * 
 * @return $string 
 */
function rembo($string){

	// Adapt�de PunBB 
	//Copyright (C)  Rickard Andersson (rickard@punbb.org)

	// If the message contains a code tag we have to split it up (text within [code][/code] shouldn't be touched)
	if (strpos($string, '[code]') !== false && strpos($string, '[/code]') !== false)
	{
		list($inside, $outside) = split_text($string, '[code]', '[/code]');
		$outside = array_map('trim', $outside);
		$string = implode('<">', $outside);
	}




	$pattern = array('#\[b\](.*?)\[/b\]#s',
			'#\[i\](.*?)\[/i\]#s',
			'#\[u\](.*?)\[/u\]#s',
			'#\[s\](.*?)\[/s\]#s',
			'#\[c\](.*?)\[/c\]#s',
			'#\[g\](.*?)\[/g\]#s',
			//'#\[url\](.*?)\[/url\]#e',
			//'#\[url=(.*?)\](.*?)\[/url\]#e',
			'#\[email\](.*?)\[/email\]#',
			'#\[email=(.*?)\](.*?)\[/email\]#',
			'#\[color=([a-zA-Z]*|\#?[0-9a-fA-F]{6})](.*?)\[/color\]#s');


	$replace = array('<strong>$1</strong>',
			'<em>$1</em>',
			'<span class="souligne">$1</span>',
			'<span class="barre">$1</span>',
			'<div align="center">$1</div>',
			'<big>$1</big>',
			// 'truncate_url(\'$1\')',
			//'truncate_url(\'$1\', \'$2\')',
		'<a href="mailto:$1">$1</a>',
		'<a href="mailto:$1">$2</a>',
		'<span style="color: $1">$2</span>');

	// This thing takes a while! :)
			$string = preg_replace($pattern, $replace, $string);



			$string=clicurl($string);

			$string=autop($string);


			// If we split up the message before we have to concatenate it together again (code tags)
			if (isset($inside))
			{
				$outside = explode('<">', $string);
				$string = '';

				$num_tokens = count($outside);

				for ($i = 0; $i < $num_tokens; ++$i)
				{
					$string .= $outside[$i];
					if (isset($inside[$i]))
						$string .= '<br><br><table  class="code" align="center" cellspacing="4" cellpadding="6"><tr><td class="punquote"><strong>Code:</strong><br><br><pre>'.trim($inside[$i]).'</pre></td></tr></table><br>';
				}
			}






			return $string;
}

// Create SQL search condition
function makeTextSearch($val,$not=0){
	$NOT="";
	if ($not) $NOT= " NOT ";
	// Unclean to permit < and > search
	$val=unclean_cross_side_scripting_deep($val);
	if ($val=="NULL"||$val=="null") $SEARCH=" IS $NOT NULL ";
	else {
		$begin=0;
		$end=0;
		if (($length=strlen($val))>0){
			if (($val[0]=='^'))
				$begin=1;
			if ($val[$length-1]=='$')
				$end=1;
		}
		if ($begin||$end) 
			$val=substr($val,$begin,$length-$end-$begin);

		$SEARCH=" $NOT LIKE '".(!$begin?"%":"").$val.(!$end?"%":"")."' ";

	}

	return $SEARCH;
}

function checkNewVersionAvailable($auto=1){
	global $DB,$LANG,$CFG_GLPI;

	if (!haveRight("check_update","r")) return false;	

	if (!$auto) echo "<br>";
	$latest_version = '';

	// Connection directe
	if (empty($CFG_GLPI["proxy_name"])){
		if ($fp=@fsockopen("glpi-project.org", 80, $errno, $errstr, 1)){

			$request  = "GET /latest_version HTTP/1.1\r\n";
			$request .= "Host: glpi-project.org\r\n";
			$request .= 'User-Agent: GLPICheckUpdate/'.trim($CFG_GLPI["version"])."\r\n";
			$request .= "Connection: Close\r\n\r\n";

			fwrite($fp, $request);
			while (!feof($fp)) {
				$ret=fgets($fp, 128);
				if (!empty($ret))
					$latest_version=$ret;
			}
			fclose($fp);
		}
	} else { // Connection using proxy
		$proxy_cont = ''; //laissez vide

		$proxy_fp = fsockopen($CFG_GLPI["proxy_name"], $CFG_GLPI["proxy_port"], $errno, $errstr, 1);
		if (!$proxy_fp)    {
			if (!$auto) echo "<div align='center'>".$LANG["setup"][311]." ($errstr)</div>";
		} 

		fputs($proxy_fp, "GET http://glpi-project.org/latest_version HTTP/1.0\r\nHost: ".$CFG_GLPI["proxy_name"]."\r\n");
		if (!empty($CFG_GLPI["proxy_user"]))
			fputs($proxy_fp, "Proxy-Authorization: Basic " . base64_encode ($CFG_GLPI["proxy_user"].":".$CFG_GLPI["proxy_password"]) . "\r\n");    // added
		fputs($proxy_fp,"\r\n");
		while(!feof($proxy_fp)) {
			$ret = fread($proxy_fp,128);
			if (!empty($ret))
				$latest_version=$ret;
		}
		fclose($proxy_fp);
	}

	if (strlen(trim($latest_version)) == 0){
		if (!$auto) echo "<div align='center'>".$LANG["setup"][304]." ($errstr)</div>";
	} else {			
		$splitted=split("\.",trim($CFG_GLPI["version"]));

		if ($splitted[0]<10) $splitted[0].="0";
		if ($splitted[1]<10) $splitted[1].="0";
		$cur_version = $splitted[0]*10000+$splitted[1]*100;
		if (isset($splitted[2])) {
			if ($splitted[2]<10) $splitted[2].="0";
			$cur_version+=$splitted[2];
		}

		$splitted=split("\.",trim($latest_version));

		if ($splitted[0]<10) $splitted[0].="0";
		if ($splitted[1]<10) $splitted[1].="0";

		$lat_version = $splitted[0]*10000+$splitted[1]*100;
		if (isset($splitted[2])) {
			if ($splitted[2]<10) $splitted[2].="0";
			$lat_version+=$splitted[2];
		}

		if ($cur_version < $lat_version){
			if (!$auto) {
				echo "<div align='center'>".$LANG["setup"][301]." ".$latest_version."</div>";
				echo "<div align='center'>".$LANG["setup"][302]."</div>";
			}

			$config_object=new Config();
			$input["ID"]=1;
			$input["founded_new_version"]=$latest_version;
			$config_object->update($input);
		}  else echo "<div align='center'>".$LANG["setup"][303]."</div>";
	} 
	return 1;
}

function cron_check_update(){
	checkNewVersionAvailable(1);
}

function getWarrantyExpir($from,$addwarranty){
	if ($from==NULL || $from=='0000-00-00')
		return "";
	else return convDate(date("Y-m-d", strtotime("$from+$addwarranty month ")));

}

function getExpir($begin,$duration,$notice="0"){
	if ($begin==NULL || $begin=='0000-00-00'){
		return "";
	} else {
		$diff=strtotime("$begin+$duration month -$notice month")-time();
		$diff_days=floor($diff/60/60/24);
		if($diff_days>0){
			return $diff_days." ".$LANG["stats"][31];
		}else{
			return "<span class='red'>".$diff_days." ".$LANG["stats"][31]."</span>";
		}
	}

}

function manageRedirect($where){
	global $CFG_GLPI,$PLUGIN_HOOKS;
	if (!empty($where)){
		$data=split("_",$where);
		if (count($data)>=2&&isset($_SESSION["glpiactiveprofile"]["interface"])&&!empty($_SESSION["glpiactiveprofile"]["interface"])){
			switch ($_SESSION["glpiactiveprofile"]["interface"]){
				case "helpdesk" :
					switch ($data[0]){
						case "plugin":
							if (isset($data[2])&&$data[2]>0&&isset($PLUGIN_HOOKS['redirect_page'][$data[1]])&&!empty($PLUGIN_HOOKS['redirect_page'][$data[1]])){
								glpi_header($CFG_GLPI["root_doc"]."/plugins/".$data[1]."/".$PLUGIN_HOOKS['redirect_page'][$data[1]]."?ID=".$data[2]);
							} else {
								glpi_header($CFG_GLPI["root_doc"]."/front/helpdesk.public.php");
							} 
						break;
						case "tracking":
							glpi_header($CFG_GLPI["root_doc"]."/front/helpdesk.public.php?show=user&ID=".$data[1]);
						break;
						default:
							glpi_header($CFG_GLPI["root_doc"]."/front/helpdesk.public.php");
						break;


					}
				break;
				case "central" :
					switch ($data[0]){
						case "plugin":
							if (isset($data[2])&&$data[2]>0&&isset($PLUGIN_HOOKS['redirect_page'][$data[1]])&&!empty($PLUGIN_HOOKS['redirect_page'][$data[1]])){
								glpi_header($CFG_GLPI["root_doc"]."/plugins/".$data[1]."/".$PLUGIN_HOOKS['redirect_page'][$data[1]]."?ID=".$data[2]);
							} else {
								glpi_header($CFG_GLPI["root_doc"]."/front/central.php");
							} 
						break;
						default : 
							if (!empty($data[0])&&$data[1]>0){
								glpi_header($CFG_GLPI["root_doc"]."/front/".$data[0].".form.php?ID=".$data[1]);
							} else {
								glpi_header($CFG_GLPI["root_doc"]."/front/central.php");
							}
						break;
					}
				break;
			}
		}
	}
}

function cleanInputText($string){
	return preg_replace('/\"/','&quot;',$string);
} 

?>
