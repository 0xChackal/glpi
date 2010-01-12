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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/// Generic class for Search Engine
class Search {

   /**
   * Display search engine for an type
   *
   * @param $itemtype item type to manage
   * @return nothing
   */
   static function show ($itemtype) {

      Search::manageGetValues($itemtype);
      Search::showForm($itemtype,$_GET);
      Search::showList($itemtype,$_GET);
   }

   /**
   * Generic Search and list function
   *
   * Build the query, make the search and list items after a search.
   *
   *@param $itemtype item type
   *@param $params parameters array may include field, contains, searchtype, sort, order, start, deleted, link, link2, contains2, field2, itemtype2
   *
   *@return Nothing (display)
   *
   **/
   static function showList ($itemtype,$params) {
      global $DB,$CFG_GLPI,$LANG,
            $PLUGIN_HOOKS;

      // Instanciate an object to access method
      $item = NULL;

      if ($itemtype!='States' && class_exists($itemtype)) {
         $item = new $itemtype();
      }

      // Default values of parameters
      $p['link']        = array();//
      $p['field']       = array();//
      $p['contains']    = array();//
      $p['searchtype']  = array();//
      $p['sort']        = '1'; //
      $p['order']       = 'ASC';//
      $p['start']       = 0;//
      $p['is_deleted']  = 0;
      $p['export_all']  = 0;
      $p['link2']       = '';//
      $p['contains2']   = '';//
      $p['field2']      = '';//
      $p['itemtype2']   = '';

      foreach ($params as $key => $val) {
            $p[$key]=$val;
      }

      if ($p['export_all']) {
         $p['start']=0;
      }

      $target= getItemTypeSearchURL($itemtype);

      $limitsearchopt=Search::getCleanedOptions($itemtype);

      if (isset($CFG_GLPI['union_search_type'][$itemtype])) {
         $itemtable=$CFG_GLPI['union_search_type'][$itemtype];
      } else {
         $itemtable=getTableForItemType($itemtype);
      }

      $LIST_LIMIT=$_SESSION['glpilist_limit'];

      // Set display type for export if define
      $output_type=HTML_OUTPUT;
      if (isset($_GET['display_type'])) {
         $output_type=$_GET['display_type'];
         // Limit to 10 element
         if ($_GET['display_type']==GLOBAL_SEARCH) {
            $LIST_LIMIT=GLOBAL_SEARCH_DISPLAY_COUNT;
         }
      }
      // hack for States
      if (isset($CFG_GLPI['union_search_type'][$itemtype])) {
         $entity_restrict = true;
      } else {
         $entity_restrict = $item->isEntityAssign();
      }

      $names = array('Computer'   => $LANG['Menu'][0],
                     'Printer'    => $LANG['Menu'][2],
                     'Monitor'    => $LANG['Menu'][3],
                     'Peripheral' => $LANG['Menu'][16],
                     'Software'   => $LANG['Menu'][4],
                     'Phone'      => $LANG['Menu'][34]);

      // Get the items to display
      $toview=Search::addDefaultToView($itemtype);

      // Add items to display depending of personal prefs
      $displaypref=DisplayPreference::getForTypeUser($itemtype,$_SESSION['glpiID']);
      if (count($displaypref)) {
         foreach ($displaypref as $val) {
            array_push($toview,$val);
         }
      }

      // Add searched items
      if (count($p['field'])>0) {
         foreach($p['field'] as $key => $val) {
            if (!in_array($val,$toview) && $val!='all' && $val!='view') {
               array_push($toview,$val);
            }
         }
      }

      // Add order item
      if (!in_array($p['sort'],$toview)) {
         array_push($toview,$p['sort']);
      }

      // Clean toview array
      $toview=array_unique($toview);
      foreach ($toview as $key => $val) {
         if (!isset($limitsearchopt[$val])) {
            unset($toview[$key]);
         }
      }
      $toview_count=count($toview);

      // Construct the request

      //// 1 - SELECT
      $SELECT = "SELECT ".Search::addDefaultSelect($itemtype);

      // Add select for all toview item
      foreach ($toview as $key => $val) {
         $SELECT.= Search::addSelect($itemtype,$val,$key,0);
      }


      //// 2 - FROM AND LEFT JOIN
      // Set reference table
      $FROM = " FROM `$itemtable`";

      // Init already linked tables array in order not to link a table several times
      $already_link_tables=array();
      // Put reference table
      array_push($already_link_tables,$itemtable);

      // Add default join
      $COMMONLEFTJOIN = Search::addDefaultJoin($itemtype,$itemtable,$already_link_tables);
      $FROM .= $COMMONLEFTJOIN;

      $searchopt=array();
      $searchopt[$itemtype]=&Search::getOptions($itemtype);
      // Add all table for toview items
      foreach ($toview as $key => $val) {
         $FROM .= Search::addLeftJoin($itemtype,$itemtable,$already_link_tables,
                              $searchopt[$itemtype][$val]["table"],
                              $searchopt[$itemtype][$val]["linkfield"]);
      }

      // Search all case :
      if (in_array("all",$p['field'])) {
         foreach ($searchopt[$itemtype] as $key => $val) {
            // Do not search on Group Name
            if (is_array($val)) {
               $FROM .= Search::addLeftJoin($itemtype,$itemtable,$already_link_tables,
                                    $searchopt[$itemtype][$key]["table"],
                                    $searchopt[$itemtype][$key]["linkfield"]);
            }
         }
      }


      //// 3 - WHERE

      // default string
      $COMMONWHERE = Search::addDefaultWhere($itemtype);
      $first=empty($COMMONWHERE);

      // Add deleted if item have it
      if ($item && $item->maybeDeleted()) {
         $LINK= " AND " ;
         if ($first) {
            $LINK=" ";
            $first=false;
         }
         $COMMONWHERE .= $LINK."`$itemtable`.`is_deleted` = '".$p['is_deleted']."' ";
      }

      // Remove template items
      if ($item && $item->maybeTemplate()) {
         $LINK= " AND " ;
         if ($first) {
            $LINK=" ";
            $first=false;
         }
         $COMMONWHERE .= $LINK."`$itemtable`.`is_template` = '0' ";
      }

      // Add Restrict to current entities
      if ($entity_restrict) {
         $LINK= " AND " ;
         if ($first) {
            $LINK=" ";
            $first=false;
         }

         if ($itemtype == 'Entity') {
            $COMMONWHERE .= getEntitiesRestrictRequest($LINK,$itemtable,'id','',true);
         } else if (isset($CFG_GLPI["union_search_type"][$itemtype])) {

            // Will be replace below in Union/Recursivity Hack
            $COMMONWHERE .= $LINK." ENTITYRESTRICT ";
         } else {
            $COMMONWHERE .= getEntitiesRestrictRequest($LINK,$itemtable,'','',$item->maybeRecursive());
         }
      }
      $WHERE="";
      $HAVING="";

      // Add search conditions
      // If there is search items
      if ($_SESSION["glpisearchcount"][$itemtype]>0 && count($p['contains'])>0) {
         for ($key=0 ; $key<$_SESSION["glpisearchcount"][$itemtype] ; $key++) {
            // if real search (strlen >0) and not all and view search
            if (isset($p['contains'][$key]) && strlen($p['contains'][$key])>0) {
               // common search
               if ($p['field'][$key]!="all" && $p['field'][$key]!="view") {
                  $LINK=" ";
                  $NOT=0;
                  $tmplink="";
                  if (is_array($p['link']) && isset($p['link'][$key])) {
                     if (strstr($p['link'][$key],"NOT")) {
                        $tmplink=" ".str_replace(" NOT","",$p['link'][$key]);
                        $NOT=1;
                     } else {
                        $tmplink=" ".$p['link'][$key];
                     }
                  } else {
                     $tmplink=" AND ";
                  }

                  if (isset($searchopt[$itemtype][$p['field'][$key]]["usehaving"])) {
                     // Manage Link if not first item
                     if (!empty($HAVING)) {
                        $LINK=$tmplink;
                     }
                     // Find key
                     $item_num=array_search($p['field'][$key],$toview);
                     $HAVING .= Search::addHaving($LINK,$NOT,$itemtype,$p['field'][$key],$p['searchtype'][$key],$p['contains'][$key],0,$item_num);
                  } else {
                     // Manage Link if not first item
                     if (!empty($WHERE)) {
                        $LINK=$tmplink;
                     }
                     $WHERE .= Search::addWhere($LINK,$NOT,$itemtype,$p['field'][$key],$p['searchtype'][$key],$p['contains'][$key]);
                  }

               // view and all search
               } else {
                  $LINK=" OR ";
                  $NOT=0;
                  $globallink=" AND ";
                  if (is_array($p['link']) && isset($p['link'][$key])) {
                     switch ($p['link'][$key]) {
                        case "AND" :
                           $LINK=" OR ";
                           $globallink=" AND ";
                           break;

                        case "AND NOT" :
                           $LINK=" AND ";
                           $NOT=1;
                           $globallink=" AND ";
                           break;

                        case "OR" :
                           $LINK=" OR ";
                           $globallink=" OR ";
                           break;

                        case "OR NOT" :
                           $LINK=" AND ";
                           $NOT=1;
                           $globallink=" OR ";
                           break;
                     }
                  } else {
                     $tmplink=" AND ";
                  }

                  // Manage Link if not first item
                  if (!empty($WHERE)) {
                     $WHERE .= $globallink;
                  }
                  $WHERE.= " ( ";
                  $first2=true;

                  $items=array();
                  if ($p['field'][$key]=="all") {
                     $items=$searchopt[$itemtype];
                  } else { // toview case : populate toview
                     foreach ($toview as $key2 => $val2) {
                        $items[$val2]=$searchopt[$itemtype][$val2];
                     }
                  }

                  foreach ($items as $key2 => $val2) {
                     if (is_array($val2)) {
                        // Add Where clause if not to be done in HAVING CLAUSE
                        if (!isset($val2["usehaving"])) {
                           $tmplink=$LINK;
                           if ($first2) {
                              $tmplink=" ";
                              $first2=false;
                           }
                           $WHERE .= Search::addWhere($tmplink,$NOT,$itemtype,$key2,$p['searchtype'][$key],$p['contains'][$key]);
                        }
                     }
                  }
                  $WHERE.=" ) ";
               }
            }
         }
      }

      //// 4 - ORDER
      $ORDER=" ORDER BY `id` ";
      foreach($toview as $key => $val) {
         if ($p['sort']==$val) {
            $ORDER= Search::addOrderBy($itemtype,$p['sort'],$p['order'],$key);
         }
      }


      //// 5 - META SEARCH
      // Preprocessing
      if ($_SESSION["glpisearchcount2"][$itemtype]>0 && is_array($p['itemtype2'])) {

         // a - SELECT
         for ($i=0 ; $i<$_SESSION["glpisearchcount2"][$itemtype] ; $i++) {
            if (isset($p['itemtype2'][$i]) && !empty($p['itemtype2'][$i]) && isset($p['contains2'][$i])
               && strlen($p['contains2'][$i])>0) {

               $SELECT .= Search::addSelect($p['itemtype2'][$i],$p['field2'][$i],$i,1,$p['itemtype2'][$i]);
            }
         }

         // b - ADD LEFT JOIN
         // Already link meta table in order not to linked a table several times
         $already_link_tables2=array();
         // Link reference tables
         for ($i=0 ; $i<$_SESSION["glpisearchcount2"][$itemtype] ; $i++) {
            if (isset($p['itemtype2'][$i]) && !empty($p['itemtype2'][$i]) && isset($p['contains2'][$i])
               && strlen($p['contains2'][$i])>0) {
               if (!in_array(getTableForItemType($p['itemtype2'][$i]),$already_link_tables2)) {
                  $FROM .= Search::addMetaLeftJoin($itemtype,$p['itemtype2'][$i],$already_link_tables2,
                                          (($p['contains2'][$i]=="NULL")||(strstr($p['link2'][$i],"NOT"))));
               }
            }
         }
         // Link items tables
         for ($i=0 ; $i<$_SESSION["glpisearchcount2"][$itemtype] ; $i++) {
            if (isset($p['itemtype2'][$i]) && !empty($p['itemtype2'][$i]) && isset($p['contains2'][$i])
               && strlen($p['contains2'][$i])>0) {
               if (!isset($searchopt[$p['itemtype2'][$i]])) {
                  $searchopt[$p['itemtype2'][$i]]=&Search::getOptions($p['itemtype2'][$i]);
               }
               if (!in_array($searchopt[$p['itemtype2'][$i]][$p['field2'][$i]]["table"]."_".$p['itemtype2'][$i],
                           $already_link_tables2)) {

                  $FROM .= Search::addLeftJoin($p['itemtype2'][$i],getTableForItemType($p['itemtype2'][$i]),$already_link_tables2,
                                       $searchopt[$p['itemtype2'][$i]][$p['field2'][$i]]["table"],
                                       $searchopt[$p['itemtype2'][$i]][$p['field2'][$i]]["linkfield"],
                                       1,$p['itemtype2'][$i]);
               }
            }
         }
      }


      //// 6 - Add item ID
      // Add ID to the select
      if (!empty($itemtable)) {
         $SELECT .= "`$itemtable`.`id` AS id ";
      }


      //// 7 - Manage GROUP BY
      $GROUPBY = "";
      // Meta Search / Search All / Count tickets
      if ($_SESSION["glpisearchcount2"][$itemtype]>0 || !empty($HAVING) || in_array('all',$p['field'])) {
         $GROUPBY = " GROUP BY `$itemtable`.`id`";
      }

      if (empty($GROUPBY)) {
         foreach ($toview as $key2 => $val2) {
            if (!empty($GROUPBY)) {
               break;
            }
            if (isset($searchopt[$itemtype][$val2]["forcegroupby"])) {
               $GROUPBY = " GROUP BY `$itemtable`.`id`";
            }
         }
      }

      // Specific search for others item linked  (META search)
      if (is_array($p['itemtype2'])) {
         for ($key=0 ; $key<$_SESSION["glpisearchcount2"][$itemtype] ; $key++) {
            if (isset($p['itemtype2'][$key]) && !empty($p['itemtype2'][$key]) && isset($p['contains2'][$key])
               && strlen($p['contains2'][$key])>0) {
               $LINK="";

               // For AND NOT statement need to take into account all the group by items
               if (strstr($p['link2'][$key],"AND NOT")
                  || isset($searchopt[$p['itemtype2'][$key]][$p['field2'][$key]]["usehaving"])) {

                  $NOT=0;
                  if (strstr($p['link2'][$key],"NOT")) {
                     $tmplink = " ".str_replace(" NOT","",$p['link2'][$key]);
                     $NOT=1;
                  } else {
                     $tmplink = " ".$p['link2'][$key];
                  }
                  if (!empty($HAVING)) {
                     $LINK=$tmplink;
                  }
                  $HAVING .= Search::addHaving($LINK,$NOT,$p['itemtype2'][$key],$p['field2'][$key],'contains',$p['contains2'][$key],1,$key);
               } else { // Meta Where Search
                  $LINK=" ";
                  $NOT=0;
                  // Manage Link if not first item
                  if (is_array($p['link2']) && isset($p['link2'][$key]) && strstr($p['link2'][$key],"NOT")) {
                     $tmplink = " ".str_replace(" NOT","",$p['link2'][$key]);
                     $NOT=1;
                  } else if (is_array($p['link2']) && isset($p['link2'][$key])) {
                     $tmplink = " ".$p['link2'][$key];
                  } else {
                     $tmplink = " AND ";
                  }
                  if (!empty($WHERE)) {
                     $LINK=$tmplink;
                  }
                  $WHERE .= Search::addWhere($LINK,$NOT,$p['itemtype2'][$key],$p['field2'][$key],'contains',$p['contains2'][$key],1);
               }
            }
         }
      }

      // If no research limit research to display item and compute number of item using simple request
      $nosearch=true;
      for ($i=0 ; $i<$_SESSION["glpisearchcount"][$itemtype] ; $i++) {
         if (isset($p['contains'][$i]) && strlen($p['contains'][$i])>0) {
            $nosearch=false;
         }
      }

      if ($_SESSION["glpisearchcount2"][$itemtype]>0) {
         $nosearch=false;
      }

      $LIMIT="";
      $numrows=0;
      //No search : count number of items using a simple count(ID) request and LIMIT search
      if ($nosearch) {
         $LIMIT= " LIMIT ".$p['start'].", ".$LIST_LIMIT;

         // Force group by for all the type -> need to count only on table ID
         if (!isset($searchopt[$itemtype][1]['forcegroupby'])) {
            $count = "count(*)";
         } else {
            $count = "count(DISTINCT `$itemtable`.`id`)";
         }
         $query_num = "SELECT $count
                     FROM `$itemtable`".
                     $COMMONLEFTJOIN;

         $first=true;

         if (!empty($COMMONWHERE)) {
            $LINK= " AND " ;
            if ($first) {
               $LINK = " WHERE ";
               $first=false;
            }
            $query_num .= $LINK.$COMMONWHERE;
         }
         // Union Search :
         if (isset($CFG_GLPI["union_search_type"][$itemtype])) {
            $tmpquery=$query_num;
            $numrows=0;

            foreach ($CFG_GLPI[$CFG_GLPI["union_search_type"][$itemtype]] as $ctype) {
               $ctable=getTableForItemType($ctype);
               $citem=new $ctype();
               if ($citem->canView()) {
                  // State case
                  if ($itemtype == 'States') {
                     $query_num=str_replace($CFG_GLPI["union_search_type"][$itemtype],
                                          $ctable,$tmpquery);
                     $query_num .= " AND $ctable.`states_id` > '0' ";
                  } else {// Ref table case
                     $reftable=getTableForItemType($itemtype);
                     $replace = "FROM `$reftable`
                                 INNER JOIN `$ctable`
                                 ON (`$reftable`.`items_id`=`$ctable`.`id`
                                    AND `$reftable`.`itemtype` = '$ctype')";

                     $query_num=str_replace("FROM `".$CFG_GLPI["union_search_type"][$itemtype]."`",
                                          $replace,$tmpquery);
                     $query_num=str_replace($CFG_GLPI["union_search_type"][$itemtype],
                                          $ctable,$query_num);
                  }
                  $query_num=str_replace("ENTITYRESTRICT",
                                       getEntitiesRestrictRequest('',$ctable,'','',$citem->maybeRecursive()),
                                       $query_num);
                  $result_num = $DB->query($query_num);
                  $numrows+= $DB->result($result_num,0,0);
               }
            }
         } else {
            $result_num = $DB->query($query_num);
            $numrows= $DB->result($result_num,0,0);
         }
      }

      // If export_all reset LIMIT condition
      if ($p['export_all']) {
         $LIMIT="";
      }

      if (!empty($WHERE) || !empty($COMMONWHERE)) {
         if (!empty($COMMONWHERE)) {
            $WHERE =' WHERE '.$COMMONWHERE.(!empty($WHERE)?' AND ( '.$WHERE.' )':'');
         } else {
            $WHERE =' WHERE '.$WHERE.' ';
         }
         $first=false;
      }

      if (!empty($HAVING)) {
         $HAVING=' HAVING '.$HAVING;
      }

      $DB->query("SET SESSION group_concat_max_len = 9999999;");

      // Create QUERY
      if (isset($CFG_GLPI["union_search_type"][$itemtype])) {
         $first=true;
         $QUERY="";
         foreach ($CFG_GLPI[$CFG_GLPI["union_search_type"][$itemtype]] as $ctype) {
            $ctable = getTableForItemType($ctype);
            $citem = new $ctype();
            if ($citem->canView()) {
               if ($first) {
                  $first=false;
               } else {
                  $QUERY.=" UNION ";
               }
               $tmpquery="";
               // State case
               if ($itemtype == 'States') {
                  $tmpquery = $SELECT.", '$ctype' AS TYPE ".
                              $FROM.
                              $WHERE;
                  $tmpquery = str_replace($CFG_GLPI["union_search_type"][$itemtype],
                                          $ctable,$tmpquery);
                  $tmpquery .= " AND `$ctable`.`states_id` > '0' ";
               } else {// Ref table case
                  $reftable=getTableForItemType($itemtype);

                  $tmpquery = $SELECT.", '$ctype' AS TYPE, `$reftable`.`id` AS refID, ".
                                    "`$ctable`.`entities_id` AS ENTITY ".
                              $FROM.
                              $WHERE;
                  $replace = "FROM `$reftable`".
                     " INNER JOIN `$ctable`".
                     " ON (`$reftable`.`items_id`=`$ctable`.`id`".
                     " AND `$reftable`.`itemtype` = '$ctype')";
                  $tmpquery = str_replace("FROM `".$CFG_GLPI["union_search_type"][$itemtype]."`",$replace,
                                          $tmpquery);
                  $tmpquery = str_replace($CFG_GLPI["union_search_type"][$itemtype],
                                          $ctable,$tmpquery);
               }
               $tmpquery = str_replace("ENTITYRESTRICT",
                                    getEntitiesRestrictRequest('',$ctable,'','',$citem->maybeRecursive()),
                                    $tmpquery);

               // SOFTWARE HACK
               if ($ctype == 'Software') {
                  $tmpquery = str_replace("glpi_softwares.serial","''",$tmpquery);
                  $tmpquery = str_replace("glpi_softwares.otherserial","''",$tmpquery);
               }
               $QUERY .= $tmpquery;
            }
         }
         if (empty($QUERY)) {
            echo displaySearchError($output_type);
            return;
         }
         $QUERY .= str_replace($CFG_GLPI["union_search_type"][$itemtype].".","",$ORDER).
                  $LIMIT;
      } else {
         $QUERY = $SELECT.
                  $FROM.
                  $WHERE.
                  $GROUPBY.
                  $HAVING.
                  $ORDER.
                  $LIMIT;
      }

      // Get it from database and DISPLAY
      if ($result = $DB->query($QUERY)) {
         // if real search or complete export : get numrows from request
         if (!$nosearch||$p['export_all']) {
            $numrows= $DB->numrows($result);
         }
         // Contruct Pager parameters
         $globallinkto = Search::getArrayUrlLink("field",$p['field']).
                        Search::getArrayUrlLink("link",$p['link']).
                        Search::getArrayUrlLink("contains",$p['contains']).
                        Search::getArrayUrlLink("field2",$p['field2']).
                        Search::getArrayUrlLink("contains2",$p['contains2']).
                        Search::getArrayUrlLink("itemtype2",$p['itemtype2']).
                        Search::getArrayUrlLink("link2",$p['link2']);

         $parameters = "sort=".$p['sort']."&amp;order=".$p['order'].$globallinkto;

         // Not more used : clean pages : try to comment it
         /*
         $tmp=explode('?',$target,2);
         if (count($tmp)>1) {
            $target = $tmp[0];
            $parameters = $tmp[1].'&amp;'.$parameters;
         }
         */
         if ($output_type==GLOBAL_SEARCH) {
            if (class_exists($itemtype)) {
               echo "<div class='center'><h2>".$item->getTypeName();
               // More items
               if ($numrows>$p['start']+GLOBAL_SEARCH_DISPLAY_COUNT) {
                  echo " <a href='$target?$parameters'>".$LANG['common'][66]."</a>";
               }
               echo "</h2></div>\n";
            } else {
               return false;
            }
         }

         // If the begin of the view is before the number of items
         if ($p['start']<$numrows) {
            // Display pager only for HTML
            if ($output_type==HTML_OUTPUT) {
               // For plugin add new parameter if available
               if ($plug=isPluginItemType($itemtype)) {
                  $function='plugin_'.$plug['plugin'].'_addParamFordynamicReport';

                  if (function_exists($function)) {
                     $out=$function($itemtype);
                     if (is_array($out) && count($out)) {
                        foreach ($out as $key => $val) {
                           if (is_array($val)) {
                              $parameters .= Search::getArrayUrlLink($key,$val);
                           } else {
                              $parameters .= "&amp;$key=$val";
                           }
                        }
                     }
                  }
               }
               printPager($p['start'],$numrows,$target,$parameters,$itemtype);
            }

            // Form to massive actions
            $isadmin=($item && $item->canUpdate());
            if (!$isadmin && in_array($itemtype,$CFG_GLPI["infocom_types"])){
               $infoc=new Infocom();
               $isadmin=($infoc->canUpdate() || $infoc->canCreate());
            }

            if ($isadmin && $output_type==HTML_OUTPUT) {
               echo "<form method='post' name='massiveaction_form' id='massiveaction_form' action=\"".
                     $CFG_GLPI["root_doc"]."/front/massiveaction.php\">";
            }

            // Compute number of columns to display
            // Add toview elements
            $nbcols=$toview_count;
            // Add meta search elements if real search (strlen>0) or only NOT search
            if ($_SESSION["glpisearchcount2"][$itemtype]>0 && is_array($p['itemtype2'])) {
               for ($i=0 ; $i<$_SESSION["glpisearchcount2"][$itemtype] ; $i++) {
                  if (isset($p['itemtype2'][$i])
                     && isset($p['contains2'][$i])
                     && strlen($p['contains2'][$i])>0
                     && !empty($p['itemtype2'][$i])
                     && (!isset($p['link2'][$i]) || !strstr($p['link2'][$i],"NOT"))) {

                     $nbcols++;
                  }
               }
            }

            if ($output_type==HTML_OUTPUT) { // HTML display - massive modif
               $nbcols++;
            }

            // Define begin and end var for loop
            // Search case
            $begin_display=$p['start'];
            $end_display=$p['start']+$LIST_LIMIT;

            // No search Case
            if ($nosearch) {
               $begin_display=0;
               $end_display=min($numrows-$p['start'],$LIST_LIMIT);
            }

            // Export All case
            if ($p['export_all']) {
               $begin_display=0;
               $end_display=$numrows;
            }

            // Display List Header
            echo displaySearchHeader($output_type,$end_display-$begin_display+1,$nbcols);

            // New Line for Header Items Line
            echo displaySearchNewLine($output_type);
            $header_num=1;

            if ($output_type==HTML_OUTPUT) { // HTML display - massive modif
               $search_config="";
               if (haveRight("search_config","w") || haveRight("search_config_global","w")) {
                  $tmp = " class='pointer' onClick=\"var w = window.open('".$CFG_GLPI["root_doc"].
                        "/front/popup.php?popup=search_config&amp;itemtype=$itemtype' ,'glpipopup', ".
                        "'height=400, width=1000, top=100, left=100, scrollbars=yes' ); w.focus();\"";

                  $search_config = "<img alt='".$LANG['setup'][252]."' title='".$LANG['setup'][252].
                                    "' src='".$CFG_GLPI["root_doc"]."/pics/options_search.png' ";
                  $search_config .= $tmp.">";
               }
               echo displaySearchHeaderItem($output_type,$search_config,$header_num,"",0,$p['order']);
            }

            // Display column Headers for toview items
            foreach ($toview as $key => $val) {
               $linkto = "$target?itemtype=$itemtype&amp;sort=".$val."&amp;order=".($p['order']=="ASC"?"DESC":"ASC").
                        "&amp;start=".$p['start'].$globallinkto;
               echo displaySearchHeaderItem($output_type,$searchopt[$itemtype][$val]["name"],
                                          $header_num,$linkto,$p['sort']==$val,$p['order']);
            }

            // Display columns Headers for meta items
            if ($_SESSION["glpisearchcount2"][$itemtype]>0 && is_array($p['itemtype2'])) {
               for ($i=0 ; $i<$_SESSION["glpisearchcount2"][$itemtype] ; $i++) {
                  if (isset($p['itemtype2'][$i]) && !empty($p['itemtype2'][$i]) && isset($p['contains2'][$i])
                     && strlen($p['contains2'][$i])>0) {

                     echo displaySearchHeaderItem($output_type,$names[$p['itemtype2'][$i]]." - ".
                                                $searchopt[$p['itemtype2'][$i]][$p['field2'][$i]]["name"],
                                                $header_num);
                  }
               }
            }

            // Add specific column Header
            if ($itemtype == 'CartridgeItem') {
               echo displaySearchHeaderItem($output_type,$LANG['cartridges'][0],$header_num);
            }
            if ($itemtype == 'ConsumableItem') {
               echo displaySearchHeaderItem($output_type,$LANG['consumables'][0],$header_num);
            }
            if ($itemtype == 'States' || $itemtype == 'ReservationItem') {
               echo displaySearchHeaderItem($output_type,$LANG['state'][6],$header_num);
            }
            if ($itemtype == 'ReservationItem' && $output_type == HTML_OUTPUT) {
               if (haveRight("reservation_central","w")) {
                  echo displaySearchHeaderItem($output_type,"&nbsp;",$header_num);
                  echo displaySearchHeaderItem($output_type,"&nbsp;",$header_num);
               }
               echo displaySearchHeaderItem($output_type,"&nbsp;",$header_num);
            }
            // End Line for column headers
            echo displaySearchEndLine($output_type);

            // if real search seek to begin of items to display (because of complete search)
            if (!$nosearch) {
               $DB->data_seek($result,$p['start']);
            }

            // Define begin and end var for loop
            // Search case
            $i=$begin_display;

            // Init list of items displayed
            if ($output_type==HTML_OUTPUT) {
               initNavigateListItems($itemtype);
            }

            // Num of the row (1=header_line)
            $row_num=1;
            // Display Loop
            while ($i < $numrows && $i<($end_display)) {
               // Column num
               $item_num=1;
               // Get data and increment loop variables
               $data=$DB->fetch_assoc($result);
               $i++;
               $row_num++;
               // New line
               echo displaySearchNewLine($output_type,($i%2));

               // Add item in item list
               addToNavigateListItems($itemtype,$data["id"]);

               if ($output_type==HTML_OUTPUT) { // HTML display - massive modif
                  $tmpcheck="";
                  if ($isadmin) {
                     if ($itemtype == 'Entity'
                        && !in_array($data["id"],$_SESSION["glpiactiveentities"])) {

                        $tmpcheck="&nbsp;";
                     } else if ($item->maybeRecursive()
                              && !in_array($data["entities_id"],$_SESSION["glpiactiveentities"])) {
                        $tmpcheck="&nbsp;";
                     } else {
                        $sel="";
                        if (isset($_GET["select"]) && $_GET["select"]=="all") {
                           $sel="checked";
                        }
                        if (isset($_SESSION['glpimassiveactionselected'][$data["id"]])) {
                           $sel="checked";
                        }
                        $tmpcheck="<input type='checkbox' name='item[".$data["id"]."]' value='1' $sel>";
                     }
                  }
                  echo displaySearchItem($output_type,$tmpcheck,$item_num,$row_num,"width='10'");
               }

               // Print first element - specific case for user
               echo displaySearchItem($output_type,Search::giveItem($itemtype,1,$data,0),$item_num,$row_num,
                                 Search::displayConfigItem($itemtype,$searchopt[$itemtype][1]["table"].".".
                                                            $searchopt[$itemtype][1]["field"]));
               // Print other toview items
               foreach ($toview as $key => $val) {
                  // Do not display first item
                  if ($key>0) {
                     echo displaySearchItem($output_type,Search::giveItem($itemtype,$val,$data,$key),$item_num,
                                          $row_num,
                              Search::displayConfigItem($itemtype,$searchopt[$itemtype][$val]["table"].".".
                                                         $searchopt[$itemtype][$val]["field"]));
                  }
               }

               // Print Meta Item
               if ($_SESSION["glpisearchcount2"][$itemtype]>0 && is_array($p['itemtype2'])) {
                  for ($j=0 ; $j<$_SESSION["glpisearchcount2"][$itemtype] ; $j++) {
                     if (isset($p['itemtype2'][$j]) && !empty($p['itemtype2'][$j]) && isset($p['contains2'][$j])
                        && strlen($p['contains2'][$j])>0) {

                        // General case
                        if (strpos($data["META_$j"],"$$$$")===false) {
                           $out=Search::giveItem ($p['itemtype2'][$j],$p['field2'][$j],$data,$j,1);
                           echo displaySearchItem($output_type,$out,$item_num,$row_num);

                        // Case of GROUP_CONCAT item : split item and multilline display
                        } else {
                           $split=explode("$$$$",$data["META_$j"]);
                           $count_display=0;
                           $out="";
                           $unit="";
                           if (isset($searchopt[$p['itemtype2'][$j]][$p['field2'][$j]]['unit'])) {
                              $unit=$searchopt[$p['itemtype2'][$j]][$p['field2'][$j]]['unit'];
                           }
                           for ($k=0 ; $k<count($split) ; $k++) {
                              if ($p['contains2'][$j]=="NULL" || strlen($p['contains2'][$j])==0
                                 ||preg_match('/'.$p['contains2'][$j].'/i',$split[$k])
                                 || isset($searchopt[$p['itemtype2'][$j]][$p['field2'][$j]]['forcegroupby'])) {

                                 if ($count_display) {
                                    $out.= "<br>";
                                 }
                                 $count_display++;

                                 // Manage Link to item
                                 $split2=explode("$$",$split[$k]);
                                 if (isset($split2[1])) {
                                    $out .= "<a href=\"".getItemTypeFormURL($p['itemtype2'][$j])."?id=".$split2[1]."\">";
                                    $out .= $split2[0].$unit;
                                    if ($_SESSION["glpiis_ids_visible"] || empty($split2[0])) {
                                       $out .= " (".$split2[1].")";
                                    }
                                    $out .= "</a>";
                                 } else {
                                    $out .= $split[$k].$unit;
                                 }
                              }
                           }
                           echo displaySearchItem($output_type,$out,$item_num,$row_num);
                        }
                     }
                  }
               }
               // Specific column display
               if ($itemtype == 'CartridgeItem') {
                  echo displaySearchItem($output_type,
                                       Cartridge::getCount($data["id"],$data["ALARM"],$output_type),
                                       $item_num,$row_num);
               }
               if ($itemtype == 'ConsumableItem') {
                  echo displaySearchItem($output_type,
                                       Consumable::getCount($data["id"],$data["ALARM"],$output_type),
                                       $item_num,$row_num);
               }
               if ($itemtype == 'States' || $itemtype == 'ReservationItem') {
                  $typename=$data["TYPE"];
                  if (class_exists($data["TYPE"])) {
                     $itemtmp = new $data["TYPE"]();
                     $typename=$itemtmp->getTypeName();
                  }
                  echo displaySearchItem($output_type,$typename,$item_num,$row_num);
               }
               if ($itemtype == 'ReservationItem' && $output_type == HTML_OUTPUT) {
                  if (haveRight("reservation_central","w")) {
                     if (!haveAccessToEntity($data["ENTITY"])) {
                        echo displaySearchItem($output_type,"&nbsp;",$item_num,$row_num);
                        echo displaySearchItem($output_type,"&nbsp;",$item_num,$row_num);
                     } else {
                        echo displaySearchItem($output_type,
                              "<a href=\"".getItemTypeFormURL($itemtype)."?id=".$data["refID"].
                              "&amp;is_active=".($data["ACTIVE"]?0:1)."&amp;update=update\" ".
                              "title='".$LANG['buttons'][42]."'><img src=\"".
                              $CFG_GLPI["root_doc"]."/pics/".($data["ACTIVE"]?"moins":"plus").
                              ".png\" alt='' title=''></a>",
                              $item_num,$row_num,"class='center'");
                        echo displaySearchItem($output_type,"<a href=\"javascript:confirmAction('".
                                       addslashes($LANG['reservation'][38])."\\n".
                                       addslashes($LANG['reservation'][39])."','".
                                       getItemTypeFormURL($itemtype)."?id=".$data["refID"].
                                       "&amp;delete=delete')\" title='".
                                       $LANG['reservation'][6]."'><img src=\"".
                                       $CFG_GLPI["root_doc"]."/pics/delete.png\" alt='' title=''></a>",
                                       $item_num,$row_num,"class='center'");
                     }
                  }
                  if ($data["ACTIVE"]) {
                     echo displaySearchItem($output_type,"<a href='reservation.php?reservationitems_id=".
                                    $data["refID"]."' title='".$LANG['reservation'][21]."'><img src=\"".
                                    $CFG_GLPI["root_doc"]."/pics/reservation-3.png\" alt='' title=''></a>",
                                    $item_num,$row_num,"class='center'");
                  } else {
                     echo displaySearchItem($output_type,"&nbsp;",$item_num,$row_num);
                  }
               }
               // End Line
               echo displaySearchEndLine($output_type);
            }

            $title="";
            // Create title
            if ($output_type==PDF_OUTPUT_LANDSCAPE || $output_type==PDF_OUTPUT_PORTRAIT) {
               if ($_SESSION["glpisearchcount"][$itemtype]>0 && count($p['contains'])>0) {
                  for ($key=0 ; $key<$_SESSION["glpisearchcount"][$itemtype] ; $key++) {
                     if (strlen($p['contains'][$key])>0) {
                        if (isset($p["link"][$key])) {
                           $title.=" ".$p["link"][$key]." ";
                        }
                        switch ($p['field'][$key]) {
                           case "all" :
                              $title .= $LANG['common'][66];
                              break;

                           case "view" :
                              $title .= $LANG['search'][11];
                              break;

                           default :
                              $title .= $searchopt[$itemtype][$p['field'][$key]]["name"];
                        }
                        $title .= " = ".$p['contains'][$key];
                     }
                  }
               }
               if ($_SESSION["glpisearchcount2"][$itemtype]>0 && count($p['contains2'])>0) {
                  for ($key=0 ; $key<$_SESSION["glpisearchcount2"][$itemtype] ; $key++) {
                     if (strlen($p['contains2'][$key])>0) {
                        if (isset($p['link2'][$key])) {
                           $title .= " ".$p['link2'][$key]." ";
                        }
                        $title .= $names[$p['itemtype2'][$key]]."/";
                        $title .= $searchopt[$p['itemtype2'][$key]][$p['field2'][$key]]["name"];
                        $title .= " = ".$p['contains2'][$key];
                     }
                  }
               }
            }

            // Display footer
            echo displaySearchFooter($output_type,$title);

            // Delete selected item
            if ($output_type==HTML_OUTPUT) {
               if ($isadmin) {
                  openArrowMassive("massiveaction_form");
                  dropdownMassiveAction($itemtype,$p['is_deleted']);
                  closeArrowMassive();

                  // End form for delete item
                  echo "</form>\n";
               } else {
                  echo "<br>";
               }
            }
            if ($output_type==HTML_OUTPUT) { // In case of HTML display
               printPager($p['start'],$numrows,$target,$parameters);
            }
         } else {
            echo displaySearchError($output_type);
         }
      } else {
         echo $DB->error();
      }
      // Clean selection
      $_SESSION['glpimassiveactionselected']=array();
   }





   /**
   * Print generic search form
   *
   *@param $itemtype type to display the form
   *@param $params parameters array may include field, contains, sort, is_deleted, link, link2, contains2, field2, type2
   *
   *@return nothing (displays)
   *
   **/
   static function showForm($itemtype,$params) {
      global $LANG,$CFG_GLPI;

      // Default values of parameters
      $p['link']        = array();//
      $p['field']       = array();
      $p['contains']    = array();
      $p['searchtype']  = '';
      $p['sort']        = '';
      $p['is_deleted']  = 0;
      $p['link2']       = '';//
      $p['contains2']   = '';
      $p['field2']      = '';
      $p['itemtype2']   = '';

      foreach ($params as $key => $val) {
         $p[$key]=$val;
      }

      $options=Search::getCleanedOptions($itemtype);
      $target = getItemTypeSearchURL($itemtype);

      // Instanciate an object to access method
      $item = NULL;
      if ($itemtype!='States' && class_exists($itemtype)) {
         $item = new $itemtype();
      }


      // Meta search names
      $names = array('Computer'   => $LANG['Menu'][0],
                     'Printer'    => $LANG['Menu'][2],
                     'Monitor'    => $LANG['Menu'][3],
                     'Peripheral' => $LANG['Menu'][16],
                     'Software'   => $LANG['Menu'][4],
                     'Phone'      => $LANG['Menu'][34]);

      echo "<form method='get' action=\"$target\">";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr class='tab_bg_1'>";
      echo "<td>";
      echo "<table>";

      // Display normal search parameters
      for ($i=0 ; $i<$_SESSION["glpisearchcount"][$itemtype] ; $i++) {
         echo "<tr><td class='right'>";
         // First line display add / delete images for normal and meta search items
         if ($i==0) {
            echo "<a href='".$CFG_GLPI["root_doc"]."/front/computer.php?add_search_count=".
                  "1&amp;itemtype=$itemtype'>";
            echo "<img src=\"".$CFG_GLPI["root_doc"]."/pics/plus.png\" alt='+' title='".
                  $LANG['search'][17]."'></a>&nbsp;&nbsp;&nbsp;&nbsp;";
            if ($_SESSION["glpisearchcount"][$itemtype]>1) {
               echo "<a href='".$CFG_GLPI["root_doc"]."/front/computer.php?delete_search_count=".
                     "1&amp;itemtype=$itemtype'>";
               echo "<img src=\"".$CFG_GLPI["root_doc"]."/pics/moins.png\" alt='-' title='".
                     $LANG['search'][18]."'></a>&nbsp;&nbsp;&nbsp;&nbsp;";
            }
            if (isset($names[$itemtype])) {
               echo "<a href='".$CFG_GLPI["root_doc"]."/front/computer.php?add_search_count2=".
                     "1&amp;itemtype=$itemtype'>";
               echo "<img src=\"".$CFG_GLPI["root_doc"]."/pics/meta_plus.png\" alt='+' title='".
                     $LANG['search'][19]."'></a>&nbsp;&nbsp;&nbsp;&nbsp;";
               if ($_SESSION["glpisearchcount2"][$itemtype]>0) {
                  echo "<a href='".$CFG_GLPI["root_doc"]."/front/computer.php?delete_search_count2=".
                        "1&amp;itemtype=$itemtype'>";
                  echo "<img src=\"".$CFG_GLPI["root_doc"]."/pics/meta_moins.png\" alt='-' title='".
                        $LANG['search'][20]."'></a>&nbsp;&nbsp;&nbsp;&nbsp;";
               }
            }
         }
         // Display link item
         if ($i>0) {
            echo "<select name='link[$i]'>";
            echo "<option value='AND' ";
            if (is_array($p["link"]) && isset($p["link"][$i]) && $p["link"][$i] == "AND") {
               echo "selected";
            }
            echo ">AND</option>\n";

            echo "<option value='OR' ";
            if (is_array($p["link"]) && isset($p["link"][$i]) && $p["link"][$i] == "OR") {
               echo "selected";
            }
            echo ">OR</option>\n";

            echo "<option value='AND NOT' ";
            if (is_array($p["link"]) && isset($p["link"][$i]) && $p["link"][$i] == "AND NOT") {
               echo "selected";
            }
            echo ">AND NOT</option>\n";

            echo "<option value='OR NOT' ";
            if (is_array($p["link"]) && isset($p["link"][$i]) && $p["link"][$i] == "OR NOT") {
               echo "selected";
            }
            echo ">OR NOT</option>";
            echo "</select>&nbsp;";
         }


         // display select box to define serach item
         echo "<select id='Search$itemtype$i' name=\"field[$i]\" size='1'>";
         echo "<option value='view' ";
         if (is_array($p['field']) && isset($p['field'][$i]) && $p['field'][$i] == "view") {
            echo "selected";
         }
         echo ">".$LANG['search'][11]."</option>\n";

         reset($options);
         $first_group=true;
         $selected='view';
         foreach ($options as $key => $val) {
            // print groups
            if (!is_array($val)) {
               if (!$first_group) {
                  echo "</optgroup>\n";
               } else {
                  $first_group=false;
               }
               echo "<optgroup label='$val'>";
            } else {
               echo "<option value='$key'";
               if (is_array($p['field']) && isset($p['field'][$i]) && $key == $p['field'][$i]) {
                  echo "selected";
                  $selected=$key;
               }
               echo ">". utf8_substr($val["name"],0,28) ."</option>\n";
            }
         }
         if (!$first_group) {
            echo "</optgroup>\n";
         }
         echo "<option value='all' ";
         if (is_array($p['field']) && isset($p['field'][$i]) && $p['field'][$i] == "all") {
            echo "selected";
         }
         echo ">".$LANG['common'][66]."</option>";
         echo "</select>&nbsp;\n";

         echo "<span id='SearchSpan$itemtype$i'>\n";

         $_POST['itemtype']=$itemtype;
         $_POST['num']=$i;
         $_POST['field']=$selected;
         $_POST['searchtype']=(is_array($p['searchtype']) && isset($p['searchtype'][$i])?$p['searchtype'][$i]:"" );
         $_POST['value']=(is_array($p['contains']) && isset($p['contains'][$i])?stripslashes($p['contains'][$i]):"" );
         include (GLPI_ROOT."/ajax/searchoption.php");
         echo "</span>\n";

      $params = array('field'       => '__VALUE__',
                      'itemtype'    => $itemtype,
                      'num'         => $i,
                      'value'       => $_POST["value"],
                      'searchtype'  => $_POST["searchtype"]);
      ajaxUpdateItemOnSelectEvent("Search$itemtype$i","SearchSpan$itemtype$i",
                                  $CFG_GLPI["root_doc"]."/ajax/searchoption.php",$params,false);

         echo "</td></tr>\n";
      }

      // Display meta search items
      $linked=array();
      if ($_SESSION["glpisearchcount2"][$itemtype]>0) {
         // Define meta search items to linked
         switch ($itemtype) {
            case 'Computer' :
               $linked = array('Printer', 'Monitor', 'Peripheral', 'Software', 'Phone');
               break;

            case 'Printer' :
            case 'Monitor' :
            case 'Peripheral' :
            case 'Software' :
            case 'Phone' :
               $linked = array('Computer');
               break;
         }
      }

      if (is_array($linked) && count($linked)>0) {
         for ($i=0 ; $i<$_SESSION["glpisearchcount2"][$itemtype] ; $i++) {
            echo "<tr><td class='left'>";
            $rand=mt_rand();

            // Display link item (not for the first item)
            echo "<select name='link2[$i]'>";
            echo "<option value='AND' ";
            if (is_array($p['link2']) && isset($p['link2'][$i]) && $p['link2'][$i] == "AND") {
               echo "selected";
            }
            echo ">AND</option>\n";

            echo "<option value='OR' ";
            if (is_array($p['link2']) && isset($p['link2'][$i]) && $p['link2'][$i] == "OR") {
               echo "selected";
            }
            echo ">OR</option>\n";

            echo "<option value='AND NOT' ";
            if (is_array($p['link2']) && isset($p['link2'][$i]) && $p['link2'][$i] == "AND NOT") {
               echo "selected";
            }
            echo ">AND NOT</option>\n";

            echo "<option value='OR NOT' ";
            if (is_array($p['link2'] )&& isset($p['link2'][$i]) && $p['link2'][$i] == "OR NOT") {
               echo "selected";
            }
            echo ">OR NOT</option>\n";
            echo "</select>";

            // Display select of the linked item type available
            echo "<select name='itemtype2[$i]' id='itemtype2_".$itemtype."_".$i."_$rand'>";
            echo "<option value=''>------</option>";
            foreach ($linked as $key) {
               echo "<option value='$key'>".utf8_substr($names[$key],0,20)."</option>\n";
            }
            echo "</select>";

            // Ajax script for display search meat item
            echo "<span id='show_".$itemtype."_".$i."_$rand'>&nbsp;</span>\n";

            $params=array('itemtype'=>'__VALUE__',
                        'num'=>$i,
                        'field'=>(is_array($p['field2']) && isset($p['field2'][$i])?$p['field2'][$i]:""),
                        'val'=>(is_array($p['contains2']) && isset($p['contains2'][$i])?$p['contains2'][$i]:""));

            ajaxUpdateItemOnSelectEvent("itemtype2_".$itemtype."_".$i."_$rand","show_".$itemtype."_".
                     $i."_$rand",$CFG_GLPI["root_doc"]."/ajax/updateMetaSearch.php",$params,false);

            if (is_array($p['itemtype2']) && isset($p['itemtype2'][$i]) && !empty($p['itemtype2'][$i])) {
               $params['itemtype']=$p['itemtype2'][$i];
               ajaxUpdateItem("show_".$itemtype."_".$i."_$rand",
                              $CFG_GLPI["root_doc"]."/ajax/updateMetaSearch.php",$params,false);
               echo "<script type='text/javascript' >";
               echo "window.document.getElementById('itemtype2_".$itemtype."_".$i."_$rand').value='".
                                                   $p['itemtype2'][$i]."';";
               echo "</script>\n";
            }
            echo "</td></tr>\n";
         }
      }
      echo "</table>\n";
      echo "</td>\n";

      echo "<td width='150px'>";
      echo "<table width='100%'><tr>";
      // Display sort selection
/*      echo "<td colspan='2'>".$LANG['search'][4];
      echo "&nbsp;<select name='sort' size='1'>";
      reset($options);
      $first_group=true;
      foreach ($options as $key => $val) {
         if (!is_array($val)) {
            if (!$first_group) {
               echo "</optgroup>\n";
            } else {
               $first_group=false;
            }
            echo "<optgroup label='$val'>";
         } else {
            echo "<option value='$key'";
            if ($key == $p['sort']) {
               echo " selected";
            }
            echo ">".utf8_substr($val["name"],0,20)."</option>\n";
         }
      }
      if (!$first_group) {
         echo "</optgroup>\n";
      }
      echo "</select> ";
      echo "</td>\n";
*/
      // Display deleted selection
      echo "<td class='center'>";
      $itemtable=getTableForItemType($itemtype);
      if ($item && $item->maybeDeleted()) {
         echo "<img src=\"".$CFG_GLPI["root_doc"]."/pics/showdeleted.png\" alt='".$LANG['common'][3].
               "' title='".$LANG['common'][3]."'>";
         Dropdown::showYesNo("is_deleted",$p['is_deleted']);
         echo '&nbsp;&nbsp;';
      }
      echo "<a href='".$CFG_GLPI["root_doc"]."/front/computer.php?reset_search=".
            "reset_search&amp;itemtype=$itemtype' >";
      echo "&nbsp;&nbsp;<img title=\"".$LANG['buttons'][16]."\" alt=\"".$LANG['buttons'][16]."\" src='".
            $CFG_GLPI["root_doc"]."/pics/reset.png' class='calendrier'></a>";
      Bookmark::showSaveButton(BOOKMARK_SEARCH,$itemtype);
      echo "</td>\n";
      echo "</tr><tr>";

      // Display submit button
      echo "<td width='80' class='center'>";
      echo "<input type='submit' value=\"".$LANG['buttons'][0]."\" class='submit' >";
      echo "</td></tr>";
      echo "</table>\n";

      echo "</td></tr>";
      echo "</table>\n";

      // For dropdown
      echo "<input type='hidden' name='itemtype' value='$itemtype'>";

      // Reset to start when submit new search
      echo "<input type='hidden' name='start' value='0'>";
      echo "</form>";
   }


   /**
   * Generic Function to add GROUP BY to a request
   *
   *@param $LINK link to use
   *@param $NOT is is a negative search ?
   *@param $itemtype item type
   *@param $ID ID of the item to search
   *@param $searchtype search type ('contains' or 'equals')
   *@param $val value search
   *@param $meta is it a meta item ?
   *@param $num item number
   *
   *@return select string
   *
   **/
   static function addHaving($LINK,$NOT,$itemtype,$ID,$searchtype,$val,$meta,$num) {

      $searchopt = &Search::getOptions($itemtype);
      $table=$searchopt[$ID]["table"];
      $field=$searchopt[$ID]["field"];

      $NAME="ITEM_";
      if ($meta) {
         $NAME="META_";
      }

      // Plugin can override core definition for its type
      if ($plug=isPluginItemType($itemtype)) {
         $function='plugin_'.$plug['plugin'].'_addHaving';
         if (function_exists($function)) {
            $out=$function($LINK,$NOT,$itemtype,$ID,$val,$num);
            if (!empty($out)) {
               return $out;
            }
         }
      }

      switch ($table.".".$field) {
         default :
         break;
      }

      //// Default cases
      // Link with plugin tables
      if (preg_match("/^glpi_plugin_([a-z0-9]+)/", $table, $matches)) {
         if (count($matches)==2) {
            $plug=$matches[1];
            $function='plugin_'.$plug.'_addHaving';
            if (function_exists($function)) {
               $out=$function($LINK,$NOT,$itemtype,$ID,$val,$num);
               if (!empty($out)) {
                  return $out;
               }
            }
         }
      }

      // Preformat items
      if (isset($searchopt[$ID]["datatype"])) {
         switch ($searchopt[$ID]["datatype"]) {
            case "number" :
            case "decimal" :
               $search=array("/\&lt;/","/\&gt;/");
               $replace=array("<",">");
               $val=preg_replace($search,$replace,$val);
               if (preg_match("/([<>])([=]*)[[:space:]]*([0-9]+)/",$val,$regs)) {
                  if ($NOT) {
                     if ($regs[1]=='<') {
                        $regs[1]='>';
                     } else {
                        $regs[1]='<';
                     }
                  }
                  $regs[1].=$regs[2];
                  return " $LINK (`$NAME$num` ".$regs[1]." ".$regs[3]." ) ";
               } else {
                  if (is_numeric($val)) {
                     if (isset($searchopt[$ID]["width"])) {
                        if (!$NOT) {
                           return " $LINK (`$NAME$num` < ".
                                    (intval($val) + $searchopt[$ID]["width"])."
                                    AND `$NAME$num` > ".
                                    (intval($val) - $searchopt[$ID]["width"]).") ";
                        } else {
                           return " $LINK (`$NAME$num` > ".
                                    (intval($val) + $searchopt[$ID]["width"])."
                                    OR `$NAME$num` < ".
                                    (intval($val) - $searchopt[$ID]["width"])." ) ";
                        }
                     } else { // Exact search
                        if (!$NOT) {
                           return " $LINK (`$NAME$num` = ".(intval($val)).") ";
                        }
                        return " $LINK (`$NAME$num` <> ".(intval($val)).") ";
                     }
                  }
               }
               break;
         }
      }

      $ADD="";
      if (($NOT && $val!="NULL")
         || $val=='^$') {

         $ADD = " OR `$NAME$num` IS NULL";
      }

      return " $LINK (`$NAME$num`".makeTextSearch($val,$NOT)."
                     $ADD ) ";

   }


   /**
   * Generic Function to add ORDER BY to a request
   *
   *@param $itemtype ID of the device type
   *@param $ID field to add
   *@param $order order define
   *@param $key item number
   *
   *@return select string
   *
   **/
   static function addOrderBy($itemtype,$ID,$order,$key=0) {
      global $CFG_GLPI,$PLUGIN_HOOKS;

      // Security test for order
      if ($order!="ASC") {
         $order="DESC";
      }
      $searchopt = &Search::getOptions($itemtype);

      $table=$searchopt[$ID]["table"];
      $field=$searchopt[$ID]["field"];
      $linkfield=$searchopt[$ID]["linkfield"];

      if (isset($CFG_GLPI["union_search_type"][$itemtype])) {
         return " ORDER BY ITEM_$key $order ";
      }

      // Plugin can override core definition for its type
      if ($plug=isPluginItemType($itemtype)) {
         $function='plugin_'.$plug['plugin'].'_addOrderBy';
         if (function_exists($function)) {
            $out=$function($itemtype,$ID,$order,$key);
            if (!empty($out)) {
               return $out;
            }
         }
      }

      switch($table.".".$field) {
         case "glpi_auth_tables.name" :
            return " ORDER BY `glpi_users`.`authtype`, `glpi_authldaps`.`name`,
                              `glpi_authmails`.`name` $order ";
            break;

         case "glpi_contracts.expire" :
            return " ORDER BY ADDDATE(`glpi_contracts`.`begin_date`,
                                    INTERVAL `glpi_contracts`.`duration` MONTH) $order ";
            break;

         case "glpi_contracts.expire_notice" :
            return " ORDER BY ADDDATE(`glpi_contracts`.`begin_date`,
                                    INTERVAL (`glpi_contracts`.`duration`-`glpi_contracts`.`notice`)
                                    MONTH) $order ";
            break;

         case "glpi_users.name" :
            if (!empty($searchopt[$ID]["linkfield"])) {
               $linkfield="_".$searchopt[$ID]["linkfield"];
               return " ORDER BY ".$table.$linkfield.".realname $order, ".
                                 $table.$linkfield.".firstname $order, ".
                                 $table.$linkfield.".name $order";
            }
            break;

         case "glpi_networkports.ip" :
            return " ORDER BY INET_ATON($table.$field) $order ";
            break;
      }

      //// Default cases

      // Link with plugin tables
      if (preg_match("/^glpi_plugin_([a-z0-9]+)/", $table, $matches)) {
         if (count($matches)==2) {
            $plug=$matches[1];
            $function='plugin_'.$plug.'_addOrderBy';
            if (function_exists($function)) {
               $out=$function($itemtype,$ID,$order,$key);
               if (!empty($out)) {
                  return $out;
               }
            }
         }
      }

      // Preformat items
      if (isset($searchopt[$ID]["datatype"])) {
         switch ($searchopt[$ID]["datatype"]) {
            case "date_delay" :
               return " ORDER BY ADDDATE($table.".$searchopt[$ID]["datafields"][1].",
                                       INTERVAL $table.".$searchopt[$ID]["datafields"][2]."
                                       MONTH) $order ";
               break;
         }
      }

      //return " ORDER BY $table.$field $order ";
      return " ORDER BY ITEM_$key $order ";

   }


   /**
   * Generic Function to add default columns to view
   *
   *@param $itemtype device type
   *
   *@return select string
   *
   **/
   static function addDefaultToView ($itemtype) {
      global $CFG_GLPI;

      $toview=array();
      $item = NULL;
      if ($itemtype!='States' && class_exists($itemtype)) {
         $item = new $itemtype();
      }
      // Add first element (name)
      array_push($toview,1);

      // Add entity view :
      if (isMultiEntitiesMode()
         && (isset($CFG_GLPI["union_search_type"][$itemtype])
            || ($item && $item->maybeRecursive())
            || count($_SESSION["glpiactiveentities"])>1)) {

         array_push($toview,80);
      }
      return $toview;
   }


   /**
   * Generic Function to add default select to a request
   *
   *@param $itemtype device type
   *
   *@return select string
   *
   **/
   static function addDefaultSelect ($itemtype) {
      global $CFG_GLPI;

      $itemtable=getTableForItemType($itemtype);
      $item = NULL;
      $mayberecursive=false;
      if ($itemtype!='States' && class_exists($itemtype)) {
         $item = new $itemtype();
         $mayberecursive = $item->maybeRecursive();
      }

      switch ($itemtype) {
         case 'ReservationItem' :
            $ret = "`glpi_reservationitems`.`is_active` AS ACTIVE, ";
            break;

         case 'CartridgeItem' :
            $ret = "`glpi_cartridgeitems`.`alarm_threshold` AS ALARM, ";
            break;

         case 'ConsumableItem' :
            $ret = "`glpi_consumableitems`.`alarm_threshold` AS ALARM, ";
            break;

         default :
            $ret = "";
      }
      if ($itemtable=='glpi_entities') {
         $ret .= "`$itemtable`.`id` as entities_id, '1' as is_recursive, ";
      } else if ($mayberecursive) {
         $ret .= "`$itemtable`.`entities_id`, `$itemtable`.`is_recursive`, ";
      }
      return $ret;
   }


   /**
   * Generic Function to add select to a request
   *
   *@param $ID ID of the item to add
   *@param $num item num in the request
   *@param $itemtype item type
   *@param $meta is it a meta item ?
   *@param $meta_type meta type table ID
   *
   *@return select string
   *
   **/
   static function addSelect ($itemtype,$ID,$num,$meta=0,$meta_type=0) {
      global $PLUGIN_HOOKS,$CFG_GLPI;

      $searchopt=&Search::getOptions($itemtype);
      $table=$searchopt[$ID]["table"];
      $field=$searchopt[$ID]["field"];
      $addtable="";
      $NAME="ITEM";
      if ($meta) {
         $NAME="META";
         if (getTableForItemType($meta_type)!=$table) {
            $addtable="_".$meta_type;
         }
      }

      // Plugin can override core definition for its type
      if ($plug=isPluginItemType($itemtype)) {
         $function='plugin_'.$plug['plugin'].'_addSelect';
         if (function_exists($function)) {
            $out=$function($itemtype,$ID,$num);
            if (!empty($out)) {
               return $out;
            }
         }
      }

      switch ($table.".".$field) {
         case "glpi_contacts.completename" :
            // Contact for display in the enterprise item
            if ($CFG_GLPI["names_format"]==FIRSTNAME_BEFORE) {
               $name1='firstname';
               $name2='name';
            } else {
               $name1='name';
               $name2='firstname';
            }
            return " GROUP_CONCAT(DISTINCT CONCAT(`$table$addtable`.`$name1`, ' ',
                                                `$table$addtable`.`$name2`, '$$',
                                                `$table$addtable`.`id`)
                                 SEPARATOR '$$$$') AS ".$NAME."_$num, ";
            break;

         case "glpi_users.name" :
            if ($itemtype != 'User') {
               $linkfield="";
               if (!empty($searchopt[$ID]["linkfield"])) {
                  $linkfield="_".$searchopt[$ID]["linkfield"];
               }
               return "`$table$linkfield$addtable`.`$field` AS ".$NAME."_$num,
                     `$table$linkfield$addtable`.`realname` AS ".$NAME."_".$num."_2,
                     `$table$linkfield$addtable`.`id` AS ".$NAME."_".$num."_3,
                     `$table$linkfield$addtable`.`firstname` AS ".$NAME."_".$num."_4, ";
            }
            break;

         case "glpi_contracts.expire_notice" : // ajout jmd
            return "`$table$addtable`.`begin_date` AS ".$NAME."_$num,
                  `$table$addtable`.`duration` AS ".$NAME."_".$num."_2,
                  `$table$addtable`.`notice` AS ".$NAME."_".$num."_3, ";
            break;

         case "glpi_contracts.expire" : // ajout jmd
            return "`$table$addtable`.`begin_date` AS ".$NAME."_$num,
                  `$table$addtable`.`duration` AS ".$NAME."_".$num."_2, ";
            break;

         case "glpi_softwarelicenses.number" :
            return " FLOOR(SUM(`$table$addtable`.`$field`)
                           * COUNT(DISTINCT `$table$addtable`.`id`)
                           / COUNT(`$table$addtable`.`id`)) AS ".$NAME."_".$num.",
                     MIN(`$table$addtable`.`$field`) AS ".$NAME."_".$num."_2, ";
            break;

         case "glpi_computers_softwareversions.count" :
            return " COUNT(DISTINCT `glpi_computers_softwareversions$addtable`.`id`)
                        AS ".$NAME."_".$num.", ";
            break;

         case "glpi_computers_deviceharddrives.specificity" :
            if ($itemtype != 'DeviceHardDrive') {
               return " SUM(`glpi_computers_deviceharddrives`.`specificity`)
                        / COUNT(`glpi_computers_deviceharddrives`.`id`)
                        * COUNT(DISTINCT `glpi_computers_deviceharddrives`.`id`) AS ".$NAME."_".$num.", ";
            }
            break;

         case "glpi_computers_devicememories.specificity" :
            if ($itemtype != 'DeviceMemory') {
               return " SUM(`glpi_computers_devicememories`.`specificity`)
                        / COUNT(`glpi_computers_devicememories`.`id`)
                        * COUNT(DISTINCT `glpi_computers_devicememories`.`id`) AS ".$NAME."_".$num.", ";
            }
            break;

         case "glpi_computers_deviceprocessors.specificity" :
            if ($itemtype != 'DeviceProcessor') {
               return " SUM(`glpi_computers_deviceprocessors`.`specificity`)
                        / COUNT(`glpi_computers_deviceprocessors`.`id`) AS ".$NAME."_".$num.", ";
            }
            break;

         case "glpi_tickets.count" :
            return " COUNT(DISTINCT `glpi_tickets$addtable`.`id`) AS ".$NAME."_".$num.", ";
            break;

         case "glpi_networkports.mac" :
            $port = " GROUP_CONCAT(DISTINCT `$table$addtable`.`$field` SEPARATOR '$$$$')
                        AS ".$NAME."_$num, ";
            if ($itemtype == 'Computer') {
               $port .= "GROUP_CONCAT(DISTINCT `glpi_computers_devicenetworkcards`.`specificity`
                                    SEPARATOR '$$$$') AS ".$NAME."_".$num."_2, ";
            }
            return $port;
            break;

         case "glpi_profiles.name" :
            if ($itemtype == 'User') {
               return " GROUP_CONCAT(`$table$addtable`.`$field` SEPARATOR '$$$$') AS ".$NAME."_$num,
                        GROUP_CONCAT(`glpi_entities`.`completename` SEPARATOR '$$$$')
                           AS ".$NAME."_".$num."_2,
                        GROUP_CONCAT(`glpi_profiles_users`.`is_recursive` SEPARATOR '$$$$')
                           AS ".$NAME."_".$num."_3,";
            }
            break;

         case "glpi_entities.completename" :
            if ($itemtype == 'User') {
               return " GROUP_CONCAT(`$table$addtable`.`completename` SEPARATOR '$$$$')
                           AS ".$NAME."_$num,
                        GROUP_CONCAT(`glpi_profiles`.`name` SEPARATOR '$$$$') AS ".$NAME."_".$num."_2,
                        GROUP_CONCAT(`glpi_profiles_users`.`is_recursive` SEPARATOR '$$$$')
                           AS ".$NAME."_".$num."_3,";
            } else {
               return "`$table$addtable`.`completename` AS ".$NAME."_$num,
                     `$table$addtable`.`id` AS ".$NAME."_".$num."_2, ";
            }
            break;

         case "glpi_auth_tables.name":
            return "`glpi_users`.`authtype` AS ".$NAME."_".$num.",
                  `glpi_users`.`auths_id` AS ".$NAME."_".$num."_2,
                  `glpi_authldaps$addtable`.`$field` AS ".$NAME."_".$num."_3,
                  `glpi_authmails$addtable`.`$field` AS ".$NAME."_".$num."_4, ";
            break;

         case "glpi_softwarelicenses.name" :
         case "glpi_softwareversions.name" :
            if ($meta) {
               return " GROUP_CONCAT(DISTINCT CONCAT(`glpi_softwares`.`name`, ' - ',
                                                   `$table$addtable`.`$field`)
                                    SEPARATOR '$$$$') AS ".$NAME."_".$num.", ";
            }
            break;

         case "glpi_softwarelicenses.serial" :
         case "glpi_softwarelicenses.otherserial" :
         case "glpi_softwarelicenses.expire" :
         case "glpi_softwarelicenses.comment" :
         case "glpi_softwareversions.comment" :
            if ($meta) {
               return " GROUP_CONCAT(DISTINCT CONCAT(`glpi_softwares`.`name`, ' - ',
                                                   `$table$addtable`.`$field`)
                                    SEPARATOR '$$$$') AS ".$NAME."_".$num.", ";
            } else {
               return " GROUP_CONCAT(DISTINCT CONCAT(`$table$addtable`.`name`, ' - ',
                                                   `$table$addtable`.`$field`)
                                    SEPARATOR '$$$$') AS ".$NAME."_".$num.", ";
            }
            break;

         case "glpi_states.name" :
            if ($meta && $meta_type == 'Software') {
               return " GROUP_CONCAT(DISTINCT CONCAT(`glpi_softwares`.`name`, ' - ',
                                                   `glpi_softwareversions$addtable`.`name`, ' - ',
                                                   `$table$addtable`.`$field`)
                                    SEPARATOR '$$$$') AS ".$NAME."_".$num.", ";
            } else if ($itemtype == 'Software') {
               return " GROUP_CONCAT(DISTINCT CONCAT(`glpi_softwareversions`.`name`, ' - ',
                                                   `$table$addtable`.`$field`)
                                    SEPARATOR '$$$$') AS ".$NAME."_".$num.", ";
            }
            break;

         case 'glpi_crontasks.description' :
            return " `glpi_crontasks`.`name` AS ".$NAME."_".$num.", ";
            break;
      }

      //// Default cases
      // Link with plugin tables
      if (preg_match("/^glpi_plugin_([a-z0-9]+)/", $table, $matches)) {
         if (count($matches)==2) {
            $plug=$matches[1];
            $function='plugin_'.$plug.'_addSelect';
            if (function_exists($function)) {
               $out=$function($itemtype,$ID,$num);
               if (!empty($out)) {
                  return $out;
               }
            }
         }
      }

      $tocompute="`$table$addtable`.`$field`";

      if (isset($searchopt[$ID]["computation"])) {
         $tocompute = $searchopt[$ID]["computation"];
         $tocompute = str_replace("TABLE",$table.$addtable,$tocompute);
      }

      // Preformat items
      if (isset($searchopt[$ID]["datatype"])) {
         switch ($searchopt[$ID]["datatype"]) {
            case "date_delay" :
               if ($meta
                  || (isset($searchopt[$ID]["forcegroupby"]) && $searchopt[$ID]["forcegroupby"])
                  ){
   /*               return " GROUP_CONCAT
                              (DISTINCT ADDDATE
                                 (`$table$addtable`.".$searchopt[$ID]["datafields"][1].",
                                 INTERVAL
                                 `$table$addtable`.".$searchopt[$ID]["datafields"][2]."
                                 MONTH)
                              SEPARATOR '$$$$') AS ".$NAME."_$num, ";
   */
                  return " GROUP_CONCAT(DISTINCT
                              CONCAT(`$table$addtable`.".$searchopt[$ID]["datafields"][1].",
                                       ',',
                                       `$table$addtable`.".$searchopt[$ID]["datafields"][2].")
                              SEPARATOR '$$$$') AS ".$NAME."_$num, ";
               } else {
   /*               return "`$table$addtable`.`".$searchopt[$ID]["datafields"][1]."`
                              AS ".$NAME."_$num,
                        `$table$addtable`.`".$searchopt[$ID]["datafields"][2]."`
                              AS ".$NAME."_".$num."_2, ";
   */
                  return "CONCAT(`$table$addtable`.`".$searchopt[$ID]["datafields"][1]."`,
                                 ',',
                                 `$table$addtable`.`".$searchopt[$ID]["datafields"][2]."`)
                                 AS ".$NAME."_$num, ";
               }
               break;

            case "itemlink" :
               if ($meta
                  || (isset($searchopt[$ID]["forcegroupby"])
                     && $searchopt[$ID]["forcegroupby"])
                  || (empty($searchopt[$ID]["linkfield"])
                     && isset($searchopt[$ID]["itemlink_type"])
                     && $searchopt[$ID]["itemlink_type"] != $itemtype)) {
                  return " GROUP_CONCAT(DISTINCT CONCAT(`$table$addtable`.`$field`, '$$' ,
                                                      `$table$addtable`.`id`)
                                       SEPARATOR '$$$$') AS ".$NAME."_$num, ";
               } else {
                  return "$tocompute AS ".$NAME."_$num,
                        `$table$addtable`.`id` AS ".$NAME."_".$num."_2, ";
               }
            break;
         }
      }

      // Default case
      if ($meta
         || (isset($searchopt[$ID]["forcegroupby"])
            && $searchopt[$ID]["forcegroupby"])) {
         return " GROUP_CONCAT(DISTINCT $tocompute SEPARATOR '$$$$') AS ".$NAME."_$num, ";
      } else {
         return "$tocompute AS ".$NAME."_$num, ";
      }
   }


   /**
   * Generic Function to add default where to a request
   *
   *@param $itemtype device type
   *
   *@return select string
   *
   **/
   static function addDefaultWhere ($itemtype) {

      switch ($itemtype) {
         // No link
         case 'User' :
            // View all entities
            if (isViewAllEntities()) {
               return "";
            } else {
               return getEntitiesRestrictRequest("","glpi_profiles_users");
            }
            break;

         default :
            return "";
      }
   }


   /**
   * Generic Function to add where to a request
   *
   *@param $link link string
   *@param $nott is it a negative serach ?
   *@param $itemtype item type
   *@param $ID ID of the item to search
   *@param $searchtype searchtype used (equals or contains)
   *@param $val item num in the request
   *@param $meta is a meta search (meta=2 in search.class.php)
   *
   *@return select string
   *
   **/
   static function addWhere($link,$nott,$itemtype,$ID,$searchtype,$val,$meta=0) {
      global $LANG,$PLUGIN_HOOKS,$CFG_GLPI;

      $searchopt=&Search::getOptions($itemtype);
      $table = $searchopt[$ID]["table"];
      $field = $searchopt[$ID]["field"];

      $inittable = $table;
      if ($meta && getTableForItemType($itemtype)!=$table) {
         $table .= "_".$itemtype;
      }

      // Hack to allow search by ID on every sub-table
      if (preg_match('/^\$\$\$\$([0-9]+)$/',$val,$regs)) {
         return $link." (`$table`.`id` ".($nott?"<>":"=").$regs[1].") ";
      }

      if ($searchtype=='contains') {
         $SEARCH=makeTextSearch($val,$nott);
      } else { // Equals
         if ($nott) {
            $SEARCH=" <> '$val'";
         } else {
            $SEARCH=" = '$val'";
         }
      }

      // Plugin can override core definition for its type
      if ($plug=isPluginItemType($itemtype)) {
         $function='plugin_'.$plug['plugin'].'_addWhere';
         if (function_exists($function)) {
            $out = $function($link,$nott,$itemtype,$ID,$val);
            if (!empty($out)) {
               return $out;
            }
         }
      }

      switch ($inittable.".".$field) {
         case "glpi_users.name" :
            $linkfield="";
            if (!empty($searchopt[$ID]["linkfield"])) {
               $linkfield = "_".$searchopt[$ID]["linkfield"];

               if ($meta && getTableForItemType($itemtype)!=$inittable) {
                  $table = $inittable;
                  $linkfield .= "_".$itemtype;
               }
            }
            if ($itemtype == 'User') { // glpi_users case / not link table
               if ($searchtype=='equals') {
                  return " $link `$table$linkfield`.`id`".$SEARCH;
               } else {
                  return makeTextCriteria("`$table$linkfield`.`$field`",$val,$nott,$link);
               }
            } else {
               if ($CFG_GLPI["names_format"]==FIRSTNAME_BEFORE) {
                  $name1='firstname';
                  $name2='realname';
               } else {
                  $name1='realname';
                  $name2='firstname';
               }
               if ($searchtype=='equals') {
                  return " $link `$table$linkfield`.`id`".$SEARCH;
               } else {
                  return $link." (`$table$linkfield`.`$name1` $SEARCH
                                 OR `$table$linkfield`.`$name2` $SEARCH
                                 OR CONCAT(`$table$linkfield`.`$name1`,' ',
                                          `$table$linkfield`.`$name2`) $SEARCH".
                                 makeTextCriteria("`$table$linkfield`.`$field`",$val,$nott,'OR').") ";
               }
            }
            break;

         case "glpi_networkports.mac" :
            if ($itemtype == 'Computer') {
               return "$link (".makeTextCriteria("`glpi_computers_devicenetworkcards`.`specificity`",$val,$nott,'').
                              makeTextCriteria("`$table`.`$field`",$val,$nott,'OR').")";
            }
            return makeTextCriteria("`$table`.`$field`",$val,$nott,$link);

         case "glpi_contracts.expire" :
            $search=array("/\&lt;/","/\&gt;/");
            $replace=array("<",">");
            $val=preg_replace($search,$replace,$val);
            if (preg_match("/([<>=])(.*)/",$val,$regs)) {
               return $link." DATEDIFF(ADDDATE(`$table`.`begin_date`,
                                             INTERVAL `$table`.`duration` MONTH),
                                       CURDATE() )".$regs[1].$regs[2]." ";
            } else {
               return $link." ADDDATE(`$table`.`begin_date`,
                                    INTERVAL `$table`.`duration` MONTH) $SEARCH ";
            }
            break;

         // ajout jmd
         case "glpi_contracts.expire_notice" :
            $search=array("/\&lt;/","/\&gt;/");
            $replace=array("<",">");
            $val=preg_replace($search,$replace,$val);
            if (preg_match("/([<>])(.*)/",$val,$regs)){
               return $link." `$table`.`notice`<>'0'
                        AND DATEDIFF(ADDDATE(`$table`.`begin_date`,
                                             INTERVAL (`$table`.`duration` - `$table`.`notice`) MONTH),
                                    CURDATE() )".$regs[1].$regs[2]." ";
            } else {
               return $link." ADDDATE(`$table`.`begin_date`,
                                    INTERVAL (`$table`.`duration` - `$table`.`notice`) MONTH) $SEARCH ";
            }
            break;

         case "glpi_infocoms.sink_time" :
         case "glpi_infocoms.warranty_duration" :
            $ADD = "";
            if ($nott && $val!="NULL") {
               $ADD = " OR `$table`.`$field` IS NULL";
            }
            if (is_numeric($val)) {
               if ($nott) {
                  return $link." (`$table`.`$field` <> ".intval($val)." ".
                                 $ADD." ) ";
               } else {
                  return $link." (`$table`.`$field` = ".intval($val)."  ".
                                 $ADD." ) ";
               }
            }
            break;

         case "glpi_infocoms.sink_type" :
            $ADD = "";
            if ($nott && $val!="NULL") {
               $ADD = " OR `$table`.`$field` IS NULL";
            }
            if (preg_match("/$val/i",Infocom::getAmortTypeName(1))) {
               $val=1;
            } else if (preg_match("/$val/i",Infocom::getAmortTypeName(2))) {
               $val=2;
            }
            if (is_int($val) && $val>0) {
               if ($nott) {
                  return $link." (`$table`.`$field` <> '$val' ".
                                 $ADD." ) ";
               } else {
                  return $link." (`$table`.`$field` = '$val' ".
                                 $ADD." ) ";
               }
            }
            break;

         case "glpi_contacts.completename" :
            if ($searchtype=='equals') {
               return " $link `$table`.`id`".$SEARCH;
            } else {
               if ($CFG_GLPI["names_format"]==FIRSTNAME_BEFORE) {
                  $name1='firstname';
                  $name2='name';
               } else {
                  $name1='name';
                  $name2='firstname';
               }

               return $link." (`$table`.`$name1` $SEARCH
                              OR `$table`.`$name2` $SEARCH
                              OR CONCAT(`$table`.`$name1`,' ',`$table`.`$name2`) $SEARCH) ";
            }
            break;

         case "glpi_auth_tables.name" :
            return $link." (`glpi_authmails`.`name` $SEARCH
                           OR `glpi_authldaps`.`name` $SEARCH ) ";
            break;

         case "glpi_contracts.renewal" :
            $valid=Contract::getContractRenewalIDByName($val);
            if ($valid>0){
               return $link." `$table`.`$field`"."="."'$valid'";
            } else {
               return "";
            }
            break;

         case "glpi_networkports.ip" :
            $search=array("/\&lt;/","/\&gt;/");
            $replace=array("<",">");
            $val=preg_replace($search,$replace,$val);
            if (preg_match("/([<>])([=]*)[[:space:]]*([0-9\.]+)/",$val,$regs)) {
               if ($nott) {
                  if ($regs[1]=='<') {
                     $regs[1]='>';
                  } else {
                     $regs[1]='<';
                  }
               }
               $regs[1].=$regs[2];
               return $link." (INET_ATON(`$table`.`$field`) ".$regs[1]." ".ip2long($regs[3]).") ";
            }
            return makeTextCriteria("`$table`.`$field`",$val,$nott,$link);
            break;

      }

      //// Default cases

      // Link with plugin tables
      if (preg_match("/^glpi_plugin_([a-z0-9]+)/", $inittable, $matches)) {
         if (count($matches)==2) {
            $plug=$matches[1];
            $function='plugin_'.$plug.'_addWhere';
            if (function_exists($function)) {
               $out=$function($link,$nott,$itemtype,$ID,$val);
               if (!empty($out)) {
                  return $out;
               }
            }
         }
      }

      $tocompute="`$table`.`$field`";
      if (isset($searchopt[$ID]["computation"])) {
         $tocompute=$searchopt[$ID]["computation"];
      }

      // Preformat items
      if (isset($searchopt[$ID]["datatype"])) {
         switch ($searchopt[$ID]["datatype"]) {
            case "date" :
            case "datetime" :
            case "date_delay" :
               $date_computation=$tocompute;
               $interval_search=" MONTH ";

               if ($searchopt[$ID]["datatype"]=="date_delay") {
                  $date_computation="ADDDATE(`$table`.".$searchopt[$ID]["datafields"][1].",
                                             INTERVAL
                                             `$table`.".$searchopt[$ID]["datafields"][2]."
                                             MONTH)";
               }
               $search=array("/\&lt;/","/\&gt;/");
               $replace=array("<",">");
               $val=preg_replace($search,$replace,$val);
               if (preg_match("/([<>=])(.*)/",$val,$regs)) {
                  if (is_numeric($regs[2])) {
                     return $link." NOW() ".$regs[1]." ADDDATE($date_computation,
                                                               INTERVAL ".$regs[2]." $interval_search) ";
                  } else {
                     // Reformat date if needed
                     $regs[2]=preg_replace('@(\d{1,2})(-|/)(\d{1,2})(-|/)(\d{4})@','\5-\3-\1',$regs[2]);
                     if (preg_match('/[0-9]{2,4}-[0-9]{1,2}-[0-9]{1,2}/',$regs[2])) {
                        return $link." $date_computation ".$regs[1]." '".$regs[2]."'";
                     } else {
                        return "";
                     }
                  }
               } else { // standard search
                  // Date format modification if needed
                  $val=preg_replace('@(\d{1,2})(-|/)(\d{1,2})(-|/)(\d{4})@','\5-\3-\1',$val);
                  return makeTextCriteria($date_computation,$val,$nott,$link);
               }
               break;

            case "bool" :
               if (!is_numeric($val)) {
                  if (strcasecmp($val,$LANG['choice'][0])==0) {
                     $val=0;
                  } else if (strcasecmp($val,$LANG['choice'][1])==0) {
                     $val=1;
                  }
               }
               // No break here : use number comparaison case

            case "number" :
            case "decimal" :

               $search=array("/\&lt;/",
                           "/\&gt;/");
               $replace=array("<",
                              ">");
               $val=preg_replace($search,$replace,$val);
               if (preg_match("/([<>])([=]*)[[:space:]]*([0-9]+)/",$val,$regs)) {
                  if ($nott) {
                     if ($regs[1]=='<') {
                        $regs[1]='>';
                     } else {
                        $regs[1]='<';
                     }
                  }
                  $regs[1].=$regs[2];
                  return $link." ($tocompute ".$regs[1]." ".$regs[3].") ";
               } else if (is_numeric($val)) {
                  if (isset($searchopt[$ID]["width"])) {
                     $ADD = "";
                     if ($nott && $val!="NULL") {
                        $ADD = " OR $tocompute IS NULL";
                     }
                     if ($nott) {
                        return $link." ($tocompute < ".(intval($val)
                                                         - $searchopt[$ID]["width"])."
                                       OR $tocompute > ".(intval($val)
                                                            + $searchopt[$ID]["width"])."
                                       $ADD) ";
                     } else {
                        return $link." (($tocompute >= ".(intval($val)
                                                            - $searchopt[$ID]["width"])."
                                       AND $tocompute <= ".(intval($val)
                                                               + $searchopt[$ID]["width"]).")
                                       $ADD) ";
                     }
                  } else {
                     if (!$nott) {
                        return " $link ($tocompute = ".(intval($val)).") ";
                     } else {
                        return " $link ($tocompute <> ".(intval($val)).") ";
                     }
                  }
               }
               break;
         }
      }

      // Default case
      if ($searchtype=='equals') {
         return " $link `$table`.`id`".$SEARCH;
      } else {
         return makeTextCriteria($tocompute,$val,$nott,$link);
      }

   }


   /**
   * Generic Function to add Default left join to a request
   *
   *@param $itemtype reference ID
   *@param $ref_table reference table
   *@param $already_link_tables array of tables already joined
   *
   *@return Left join string
   *
   **/
   static function addDefaultJoin ($itemtype,$ref_table,&$already_link_tables) {

      switch ($itemtype) {
         // No link
         case 'User' :
            return Search::addLeftJoin($itemtype,$ref_table,$already_link_tables,"glpi_profiles_users","");

         default :
            return "";
      }
   }


   /**
   * Generic Function to add left join to a request
   *
   *@param $itemtype item type
   *@param $ref_table reference table
   *@param $already_link_tables array of tables already joined
   *@param $new_table new table to join
   *@param $meta is it a meta item ?
   *@param $meta_type meta type table
   *@param $linkfield linkfield for LeftJoin
   *
   *@return Left join string
   *
   **/
   static function addLeftJoin ($itemtype,$ref_table,&$already_link_tables,$new_table,$linkfield,
                        $meta=0,$meta_type=0){
      global $PLUGIN_HOOKS,$LANG;

      // Rename table for meta left join
      $AS = "";
      $nt = $new_table;

      // Multiple link possibilies case
      if ($new_table=="glpi_users") {
         $nt .= "_".$linkfield;
         $AS .= " AS ".$nt;
      }

      $addmetanum = "";
      $rt = $ref_table;
      if ($meta) {
         $addmetanum = "_".$meta_type;
         $AS = " AS $nt$addmetanum";
         $nt = $nt.$addmetanum;
      }


      // Auto link
      if ($ref_table==$new_table) {
         return "";
      }

      if (in_array($nt.".".$linkfield,$already_link_tables)) {
         return "";
      } else {
         array_push($already_link_tables, $nt.".".$linkfield);
      }

      // Plugin can override core definition for its type
      if ($plug=isPluginItemType($itemtype)) {
         $function='plugin_'.$plug['plugin'].'_addLeftJoin';
         if (function_exists($function)) {
            $out = $function($itemtype,$ref_table,$new_table,$linkfield,$already_link_tables);
            if (!empty($out)) {
               return $out;
            }
         }
      }

      switch ($new_table) {
         // No link
         case "glpi_auth_tables" :
            return " LEFT JOIN `glpi_authldaps` ON (`glpi_users`.`authtype` = ".AUTH_LDAP."
                                                   AND `glpi_users`.`auths_id` = `glpi_authldaps`.`id`)
                     LEFT JOIN `glpi_authmails` ON (`glpi_users`.`authtype` = ".AUTH_MAIL."
                                                   AND `glpi_users`.`auths_id` = `glpi_authmails`.`id`)";

         case "glpi_reservationitems" :
            return "";

         case "glpi_computerdisks" :
            if ($meta) {
               return " INNER JOIN `$new_table` $AS ON (`$rt`.`id` = `$nt`.`computers_id`) ";
            }
            return " LEFT JOIN `$new_table` $AS ON (`$rt`.`id` = `$nt`.`computers_id`) ";

         case "glpi_filesystems" :
            $out = Search::addLeftJoin($itemtype,$rt,$already_link_tables,"glpi_computerdisks",$linkfield);
            return $out."
                  LEFT JOIN `$new_table` $AS ON (`glpi_computerdisks`.`filesystems_id` = `$nt`.`id`) ";

         case "glpi_entitydatas" :
            return " LEFT JOIN `$new_table` $AS ON (`$rt`.`id` = `$nt`.`entities_id`) ";

         case "glpi_ocslinks" :
         case "glpi_registrykeys":
            return " LEFT JOIN `$new_table` $AS ON (`$rt`.`id` = `$nt`.`computers_id`) ";

         case "glpi_operatingsystems" :
            return " LEFT JOIN `$new_table` $AS ON (`$rt`.`operatingsystems_id` = `$nt`.`id`) ";

         case "glpi_networkports" :
            $out="";
            // Add networking device for computers
            if ($itemtype == 'Computer') {
               $out = Search::addLeftJoin($itemtype,$rt,$already_link_tables,"glpi_computers_devicenetworkcards",
                                 $linkfield,$meta,$meta_type);
            }
            return $out."
                  LEFT JOIN `$new_table` $AS ON (`$rt`.`id` = `$nt`.`items_id`
                                                AND `$nt`.`itemtype` = '$itemtype') ";

         case "glpi_netpoints" :
            // Link to glpi_networkports before
            $out = Search::addLeftJoin($itemtype,$rt,$already_link_tables,"glpi_networkports",$linkfield);
            return $out."
                  LEFT JOIN `$new_table` $AS ON (`glpi_networkports`.`netpoints_id` = `$nt`.`id`) ";

         case "glpi_tickets" :
            if (!empty($linkfield)) {
               return " LEFT JOIN `$new_table` $AS ON (`$rt`.`$linkfield` = `$nt`.`id`) ";
            }
            // nobreak;
         case "glpi_contracts_items" :
            return " LEFT JOIN `$new_table` $AS ON (`$rt`.`id` = `$nt`.`items_id`
                                                   AND `$nt`.`itemtype` = '$itemtype') ";

         case "glpi_users" :
            return " LEFT JOIN `$new_table` $AS ON (`$rt`.`$linkfield` = `$nt`.`id`) ";

         case "glpi_suppliers" :
            if ($itemtype == 'Contact') {
               $out = Search::addLeftJoin($itemtype,$rt,$already_link_tables,"glpi_contacts_suppliers",
                                 "contacts_id");
               return $out."
                     LEFT JOIN `$new_table` $AS
                           ON (`glpi_contacts_suppliers`.`suppliers_id` = `$nt`.`id` ".
                              getEntitiesRestrictRequest("AND","glpi_suppliers",'','',true).") ";
            }
            return " LEFT JOIN `$new_table` $AS ON (`$rt`.`suppliers_id` = `$nt`.`id`) ";

         case "glpi_contacts" :
            $out = Search::addLeftJoin($itemtype,$rt,$already_link_tables,"glpi_contacts_suppliers",
                              "suppliers_id");
            return $out."
                  LEFT JOIN `$new_table` $AS
                        ON (`glpi_contacts_suppliers`.`contacts_id` = `$nt`.`id` ".
                           getEntitiesRestrictRequest("AND","glpi_contacts",'','',true)." ) ";

         case "glpi_contacts_suppliers" :
            return " LEFT JOIN `$new_table` $AS ON (`$rt`.`id` = `$nt`.`$linkfield`) ";

         case "glpi_manufacturers" :
            return " LEFT JOIN `$new_table` $AS ON (`$rt`.`manufacturers_id` = `$nt`.`id`) ";

         case "glpi_suppliers_infocoms" :
            $out = Search::addLeftJoin($itemtype,$rt,$already_link_tables,"glpi_infocoms",$linkfield);
            return $out."
                  LEFT JOIN `glpi_suppliers` AS glpi_suppliers_infocoms
                        ON (`glpi_infocoms`.`suppliers_id` = `$nt`.`id`) ";

         case "glpi_budgets" :
            $out = Search::addLeftJoin($itemtype,$rt,$already_link_tables,"glpi_infocoms",$linkfield);
            return $out."
                  LEFT JOIN `$new_table` $AS ON (`glpi_infocoms`.`budgets_id` = `$nt`.`id`) ";

         case "glpi_cartridges" :
            return " LEFT JOIN `$new_table` $AS
                           ON (`$rt`.`id` = `$nt`.`cartridgeitems_id` ) ";

         case "glpi_consumables" :
            return " LEFT JOIN `$new_table` $AS
                           ON (`$rt`.`id` = `$nt`.`consumableitems_id` ) ";

         case "glpi_infocoms" :
            if ($itemtype == 'Software') {
               // Return the infocom linked to the license, not the template linked to the software
               return Search::addLeftJoin($itemtype,$rt,$already_link_tables,"glpi_softwarelicenses",
                                 $linkfield) ."
                     LEFT JOIN `$new_table` $AS
                           ON (`glpi_softwarelicenses`.`id` = `$nt`.`items_id`
                              AND `$nt`.`itemtype` = 'SoftwareLicense') ";
            }
            if ($itemtype == 'CartridgeItem') {
               // Return the infocom linked to the Cartridge, not the template linked to the Type
               return Search::addLeftJoin($itemtype,$rt,$already_link_tables,"glpi_cartridges",
                                 $linkfield) ."
                     LEFT JOIN `$new_table` $AS
                           ON (`glpi_cartridges`.`id` = `$nt`.`items_id`
                              AND `$nt`.`itemtype` = 'Cartridge') ";
            }
            if ($itemtype == 'ConsumableItem') {
               // Return the infocom linked to the Comsumable, not the template linked to the Type
               return Search::addLeftJoin($itemtype,$rt,$already_link_tables,"glpi_consumables",
                                 $linkfield) ."
                     LEFT JOIN `$new_table` $AS
                           ON (`glpi_cartridges`.`id` = `$nt`.`items_id`
                              AND `$nt`.`itemtype` = 'Consumable') ";
            }
            return " LEFT JOIN `$new_table` $AS ON (`$rt`.`id` = `$nt`.`items_id`
                                                   AND `$nt`.`itemtype` = '$itemtype') ";

         case "glpi_states" :
            if ($itemtype == 'Software') {
               // Return the state of the version of the software
               return Search::addLeftJoin($itemtype,$rt,$already_link_tables,"glpi_softwareversions",
                                 $linkfield,$meta,$meta_type) ."
                     LEFT JOIN `$new_table` $AS ON (`glpi_softwareversions`.`states_id` = `$nt`.`id`)";
            }
            return " LEFT JOIN `$new_table` $AS ON (`$rt`.`states_id` = `$nt`.`id`) ";

         case "glpi_profiles_users" :
         case "glpi_groups_users" :
            return " LEFT JOIN `$new_table` $AS ON (`$rt`.`id` = `$nt`.`users_id`) ";

         case "glpi_profiles" :
            // Link to glpi_profiles_users before
            $out = Search::addLeftJoin($itemtype,$rt,$already_link_tables,"glpi_profiles_users",$linkfield);
            if ($itemtype == 'User') {
               $out .= Search::addLeftJoin($itemtype,"glpi_profiles_users",$already_link_tables,
                                 "glpi_complete_entities","entities_id");
            }
            return $out."
                  LEFT JOIN `$new_table` $AS ON (`glpi_profiles_users`.`profiles_id` = `$nt`.`id`) ";

         case "glpi_entities" :
            if ($itemtype == 'User') {
               $out = Search::addLeftJoin($itemtype,"glpi_profiles_users",$already_link_tables,
                                 "glpi_profiles","");
               $out.= Search::addLeftJoin($itemtype,"glpi_profiles_users",$already_link_tables,
                                 "glpi_complete_entities","entities_id");
               return $out;
            }
            return " LEFT JOIN `$new_table` $AS ON (`$rt`.`$linkfield` = `$nt`.`id`) ";

         case "glpi_complete_entities" :
            array_push($already_link_tables,"glpi_entities.".
                     $linkfield);
            if (empty($AS)) {
               $AS = "AS glpi_entities";
            }
            return " LEFT JOIN (SELECT `id`, `name`, `entities_id`, `completename`, `comment`, `level`
                              FROM `glpi_entities`
                              UNION
                              SELECT 0 AS id, '".addslashes($LANG['entity'][2])."' AS name,
                                       -1 AS entities_id,
                                       '".addslashes($LANG['entity'][2])."' AS completename,
                                       '' AS comment, -1 AS level) $AS
                           ON (`$rt`.`$linkfield` = `glpi_entities`.`id`) ";

         case "glpi_groups":
            if (empty($linkfield)) {
               // Link to glpi_users_group before
               $out = Search::addLeftJoin($itemtype,$rt,$already_link_tables,"glpi_groups_users",$linkfield,
                                 $meta,$meta_type);
               return $out."
                     LEFT JOIN `$new_table` $AS
                           ON (`glpi_groups_users$addmetanum`.`groups_id` = `$nt`.`id`) ";
            }
            return " LEFT JOIN `$new_table` $AS ON (`$rt`.`$linkfield` = `$nt`.`id`) ";

         case "glpi_contracts" :
            $out = Search::addLeftJoin($itemtype,$rt,$already_link_tables,"glpi_contracts_items",$linkfield,
                              $meta,$meta_type);
            return $out."
                  LEFT JOIN `$new_table` $AS
                        ON (`glpi_contracts_items$addmetanum`.`contracts_id` = `$nt`.`id`) ";

         case "glpi_softwarelicensetypes" :
            return Search::addLeftJoin($itemtype,$rt,$already_link_tables,"glpi_softwarelicenses",$linkfield,
                              $meta,$meta_type) ."
                  LEFT JOIN `$new_table` $AS ON (`glpi_softwarelicenses`.`softwarelicensetypes_id` = `$nt`.`id`)";

         case "glpi_softwarelicenses" :
            if (!$meta) {
               return " LEFT JOIN `$new_table` $AS
                              ON (`$rt`.`id` = `$nt`.`softwares_id` ".
                                 getEntitiesRestrictRequest("AND",$nt,'','',true).") ";
            }
            return "";

         case "glpi_softwareversions" :
            if (!$meta) {
               return " LEFT JOIN `$new_table` $AS ON (`$rt`.`id` = `$nt`.`softwares_id`) ";
            }
            return "";

         case "glpi_computers_softwareversions" :
            $out = Search::addLeftJoin($itemtype,$rt,$already_link_tables,"glpi_softwareversions",$linkfield,
                              $meta,$meta_type);
            return $out."
                  LEFT JOIN `$new_table` $AS
                        ON (`glpi_softwareversions$addmetanum`.`id` = `$nt`.`softwareversions_id`) ";

         case "glpi_computers_devicecases" :
         case "glpi_computers_devicecontrols" :
         case "glpi_computers_devicedrives" :
         case "glpi_computers_devicegraphiccards" :
         case "glpi_computers_deviceharddrives" :
         case "glpi_computers_devicememories" :
         case "glpi_computers_devicemotherboards" :
         case "glpi_computers_devicenetworkcards" :
         case "glpi_computers_devicepcis" :
         case "glpi_computers_devicepowersupplies" :
         case "glpi_computers_deviceprocessors" :
         case "glpi_computers_devicesoundcards" :
            return " LEFT JOIN `$new_table`
                           ON (`$rt`.`id` = `$new_table`.`computers_id`) ";
         case "glpi_devicecases" :
         case "glpi_devicecontrols" :
         case "glpi_devicedrives" :
         case "glpi_devicegraphiccards" :
         case "glpi_deviceharddrives" :
         case "glpi_devicememories" :
         case "glpi_devicemotherboards" :
         case "glpi_devicenetworkcards" :
         case "glpi_devicepcis" :
         case "glpi_devicepowersupplies" :
         case "glpi_deviceprocessors" :
         case "glpi_devicesoundcards" :
            $linktable=str_replace('glpi_','glpi_computers_',$new_table);
            $out = Search::addLeftJoin($itemtype,$rt,$already_link_tables,$linktable,
                              $linkfield,$meta,$meta_type);
            return $out."
                  LEFT JOIN `$new_table` $AS ON (`$linktable`.`".getForeignKeyFieldForTable($new_table)."` = `$nt`.`id`) ";

         case 'glpi_plugins':
            return " LEFT JOIN `$new_table` $AS ON (`$rt`.`$linkfield` = `$nt`.`directory`) ";

         default :
            // Link with plugin tables : need to know left join structure
            if (preg_match("/^glpi_plugin_([a-z0-9]+)/", $new_table, $matches)) {
               if (count($matches)==2) {
                  $function = 'plugin_'.$matches[1].'_addLeftJoin';
                  if (function_exists($function)) {
                     $out=$function($itemtype,$ref_table,$new_table,$linkfield,$already_link_tables);
                     if (!empty($out)) {
                        return $out;
                     }
                  }
               }
            }
            if (!empty($linkfield)) {
               return " LEFT JOIN `$new_table` $AS ON (`$rt`.`$linkfield` = `$nt`.`id`) ";
            }
            return "";
      }
   }

   /**
   * Generic Function to add left join for meta items
   *
   *@param $from_type reference item type ID
   *@param $to_type item type to add
   *@param $already_link_tables2 array of tables already joined
   *@param $nullornott Used LEFT JOIN (null generation) or INNER JOIN for strict join
   *
   *@return Meta Left join string
   *
   **/
   static function addMetaLeftJoin($from_type,$to_type,&$already_link_tables2,$nullornott) {

      $LINK=" INNER JOIN ";
      if ($nullornott) {
         $LINK=" LEFT JOIN ";
      }

      switch ($from_type) {
         case 'Computer' :
            switch ($to_type) {
               case 'Printer' :
                  array_push($already_link_tables2,getTableForItemType($to_type));
                  return " $LINK `glpi_computers_items` AS conn_print_$to_type
                              ON (`conn_print_$to_type`.`computers_id` = `glpi_computers`.`id`
                                 AND `conn_print_$to_type`.`itemtype` = '$to_type')
                           $LINK `glpi_printers`
                              ON (`conn_print_$to_type`.`items_id` = `glpi_printers`.`id`) ";

               case 'Monitor' :
                  array_push($already_link_tables2,getTableForItemType($to_type));
                  return " $LINK `glpi_computers_items` AS conn_mon_$to_type
                              ON (`conn_mon_$to_type`.`computers_id` = `glpi_computers`.`id`
                                 AND `conn_mon_$to_type`.`itemtype` = '$to_type')
                           $LINK `glpi_monitors`
                              ON (`conn_mon_$to_type`.`items_id` = `glpi_monitors`.`id`) ";

               case 'Peripheral' :
                  array_push($already_link_tables2,getTableForItemType($to_type));
                  return " $LINK `glpi_computers_items` AS conn_periph_$to_type
                              ON (`conn_periph_$to_type`.`computers_id` = `glpi_computers`.`id`
                                 AND `conn_periph_$to_type`.`itemtype` = '$to_type')
                           $LINK `glpi_peripherals`
                              ON (`conn_periph_$to_type`.`items_id` = `glpi_peripherals`.`id`) ";

               case 'Phone' :
                  array_push($already_link_tables2,getTableForItemType($to_type));
                  return " $LINK `glpi_computers_items` AS conn_phones_$to_type
                              ON (`conn_phones_$to_type`.`computers_id` = `glpi_computers`.`id`
                                 AND `conn_phones_$to_type`.`itemtype` = '$to_type')
                           $LINK `glpi_phones`
                              ON (`conn_phones_$to_type`.`items_id` = `glpi_phones`.`id`) ";

               case 'Software' :
                  /// TODO: link licenses via installed software OR by affected/computers_id ???
                  array_push($already_link_tables2,getTableForItemType($to_type));
                  return " $LINK `glpi_computers_softwareversions` AS inst_$to_type
                              ON (`inst_$to_type`.`computers_id` = `glpi_computers`.`id`)
                           $LINK `glpi_softwareversions` AS glpi_softwareversions_$to_type
                              ON (`inst_$to_type`.`softwareversions_id`
                                 = `glpi_softwareversions_$to_type`.`id`)
                           $LINK `glpi_softwares`
                              ON (`glpi_softwareversions_$to_type`.`softwares_id`
                                 = `glpi_softwares`.`id`)
                           LEFT JOIN `glpi_softwarelicenses` AS glpi_softwarelicenses_$to_type
                              ON (`glpi_softwares`.`id` = `glpi_softwarelicenses_$to_type`.`softwares_id`" .
                                 getEntitiesRestrictRequest(' AND',"glpi_softwarelicenses_$to_type",
                                                            '','',true).") ";
            }
            break;

         case 'Monitor' :
            switch ($to_type) {
               case 'Computer' :
                  array_push($already_link_tables2,getTableForItemType($to_type));
                  return " $LINK `glpi_computers_items` AS conn_mon_$to_type
                              ON (`conn_mon_$to_type`.`items_id` = `glpi_monitors`.`id`
                                 AND `conn_mon_$to_type`.`itemtype` = '$from_type')
                           $LINK `glpi_computers`
                              ON (`conn_mon_$to_type`.`computers_id` = `glpi_computers`.`id`) ";
            }
            break;

         case 'Printer' :
            switch ($to_type) {
               case 'Computer' :
                  array_push($already_link_tables2,getTableForItemType($to_type));
                  return " $LINK `glpi_computers_items` AS conn_mon_$to_type
                              ON (`conn_mon_$to_type`.`items_id` = `glpi_printers`.`id`
                                 AND `conn_mon_$to_type`.`itemtype` = '$from_type')
                           $LINK `glpi_computers`
                              ON (`conn_mon_$to_type`.`computers_id` = `glpi_computers`.`id` ".
                                 getEntitiesRestrictRequest("AND",'glpi_computers').") ";
            }
            break;

         case 'Peripheral' :
            switch ($to_type) {
               case 'Computer' :
                  array_push($already_link_tables2,getTableForItemType($to_type));
                  return " $LINK `glpi_computers_items` AS conn_mon_$to_type
                              ON (`conn_mon_$to_type`.`items_id` = `glpi_peripherals`.`id`
                                 AND `conn_mon_$to_type`.`itemtype` = '$from_type')
                           $LINK `glpi_computers`
                              ON (`conn_mon_$to_type`.`computers_id` = `glpi_computers`.`id`) ";
            }
            break;

         case 'Phone' :
            switch ($to_type) {
               case 'Computer' :
                  array_push($already_link_tables2,getTableForItemType($to_type));
                  return " $LINK `glpi_computers_items` AS conn_mon_$to_type
                              ON (`conn_mon_$to_type`.`items_id` = `glpi_phones`.`id`
                                 AND `conn_mon_$to_type`.`itemtype` = '$from_type')
                           $LINK `glpi_computers`
                              ON (`conn_mon_$to_type`.`computers_id` = `glpi_computers.id`) ";
            }
            break;

         case 'Software' :
            switch ($to_type) {
               case 'Computer' :
                  array_push($already_link_tables2,getTableForItemType($to_type));
                  return " $LINK `glpi_softwareversions` AS glpi_softwareversions_$to_type
                              ON (`glpi_softwareversions_$to_type`.`softwares_id` = `glpi_softwares`.`id`)
                           $LINK `glpi_computers_softwareversions` AS inst_$to_type
                              ON (`inst_$to_type`.`softwareversions_id`
                                 = `glpi_softwareversions_$to_type`.`id`)
                           $LINK `glpi_computers`
                              ON (`inst_$to_type`.`computers_id` = `glpi_computers`.`id` ".
                                 getEntitiesRestrictRequest("AND",'glpi_computers').") ";
            }
            break;
      }
   }


   /**
   * Generic Function to display Items
   *
   *@param $field field which have a specific display type
   *@param $itemtype item type
   *
   *@return string to print
   *
   **/
   static function displayConfigItem ($itemtype,$field) {

      switch ($field) {
         case "glpi_ocslinks.last_update" :
         case "glpi_ocslinks.last_ocs_update" :
         case "glpi_computers.date_mod" :
         case "glpi_printers.date_mod" :
         case "glpi_networkequipments.date_mod" :
         case "glpi_peripherals.date_mod" :
         case "glpi_phones.date_mod" :
         case "glpi_softwares.date_mod" :
         case "glpi_monitors.date_mod" :
         case "glpi_documents.date_mod" :
         case "glpi_ocsservers.date_mod" :
         case "glpi_users.last_login" :
         case "glpi_users.date_mod" :
            return " class='center'";
            break;

         default:
            return "";
            break;
      }
   }


   /**
   * Generic Function to display Items
   *
   *@param $itemtype item type
   *@param $ID ID of the SEARCH_OPTION item
   *@param $data array containing data results
   *@param $num item num in the request
   *@param $meta is a meta item ?
   *
   *@return string to print
   *
   **/
   static function giveItem ($itemtype,$ID,$data,$num,$meta=0) {
      global $CFG_GLPI,$LANG,$PLUGIN_HOOKS;

      $searchopt=&Search::getOptions($itemtype);
      if (isset($CFG_GLPI["union_search_type"][$itemtype])
         && $CFG_GLPI["union_search_type"][$itemtype]==$searchopt[$ID]["table"]) {
         return Search::giveItem ($data["TYPE"],$ID,$data,$num);
      }

      // Plugin can override core definition for its type
      if ($plug=isPluginItemType($itemtype)) {
         $function='plugin_'.$plug['plugin'].'_giveItem';
         if (function_exists($function)) {
            $out=$function($itemtype,$ID,$data,$num);
            if (!empty($out)) {
               return $out;
            }
         }
      }

      $NAME="ITEM_";
      if ($meta) {
         $NAME="META_";
      }
      $table=$searchopt[$ID]["table"];
      $field=$searchopt[$ID]["field"];
      $linkfield=$searchopt[$ID]["linkfield"];

      switch ($table.'.'.$field) {
         case "glpi_users.name" :
            // USER search case
            if (!empty($linkfield)) {
               return formatUserName($data[$NAME.$num."_3"],$data[$NAME.$num],$data[$NAME.$num."_2"],
                                    $data[$NAME.$num."_4"],1);
            }
            break;

         case "glpi_profiles.name" :
            if ($itemtype == 'User') {
               $out="";
               $split=explode("$$$$",$data[$NAME.$num]);
               $split2=explode("$$$$",$data[$NAME.$num."_2"]);
               $split3=explode("$$$$",$data[$NAME.$num."_3"]);
               $count_display=0;
               $added=array();
               for ($k=0 ; $k<count($split) ; $k++) {
                  if (strlen(trim($split[$k]))>0) {
                     $text=$split[$k]." - ".$split2[$k];
                     if ($split3[$k]) {
                        $text .= " (R)";
                     }
                     if (!in_array($text,$added)) {
                        if ($count_display) {
                           $out.= "<br>";
                        }
                        $count_display++;
                        $out .= $text;
                        $added[]=$text;
                     }
                  }
               }
               return $out;
            }
            break;

         case "glpi_entities.completename" :
            if ($itemtype == 'User') {
               $out="";
               $split=explode("$$$$",$data[$NAME.$num]);
               $split2=explode("$$$$",$data[$NAME.$num."_2"]);
               $split3=explode("$$$$",$data[$NAME.$num."_3"]);
               $added=array();
               $count_display=0;
               for ($k=0 ; $k<count($split) ; $k++) {
                  if (strlen(trim($split[$k]))>0) {
                     $text=$split[$k]." - ".$split2[$k];
                     if ($split3[$k]) {
                        $text .= " (R)";
                     }
                     if (!in_array($text,$added)) {
                        if ($count_display) {
                           $out.= "<br>";
                        }
                        $count_display++;
                        $out .= $text;
                        $added[]=$text;
                     }
                  }
               }
               return $out;
            } else if ($data[$NAME.$num."_2"]==0) {  // Set name for Root entity
               $data[$NAME.$num]=$LANG['entity'][2];
            }
            break;

         case "glpi_documenttypes.icon" :
            if (!empty($data[$NAME.$num])) {
               return "<img class='middle' alt='' src='".$CFG_GLPI["typedoc_icon_dir"]."/".
                        $data[$NAME.$num]."'>";
            }
            return "&nbsp;";

         case "glpi_documents.filename" :
            $doc = new Document();
            if ($doc->getFromDB($data['id'])) {
               return $doc->getDownloadLink();
            }
            return NOT_AVAILABLE;

         case "glpi_deviceharddrives.specificity" :
         case "glpi_devicememories.specificity" :
         case "glpi_deviceprocessors.specificity" :
            return $data[$NAME.$num];

         case "glpi_networkports.mac" :
            $out = "";
            if ($itemtype == 'Computer') {
               $displayed=array();
               if (!empty($data[$NAME.$num."_2"])) {
                  $split=explode("$$$$",$data[$NAME.$num."_2"]);
                  $count_display=0;
                  for ($k=0 ; $k<count($split) ; $k++) {
                     $lowstr=utf8_strtolower($split[$k]);
                     if (strlen(trim($split[$k]))>0 && !in_array($lowstr,$displayed)) {
                        if ($count_display) {
                           $out .= "<br>";
                        }
                        $count_display++;
                        $out .= $split[$k];
                        $displayed[]=$lowstr;
                     }
                  }
                  if (!empty($data[$NAME.$num])) {
                     $out .= "<br>";
                  }
               }
               if (!empty($data[$NAME.$num])) {
                  $split=explode("$$$$",$data[$NAME.$num]);
                  $count_display=0;
                  for ($k=0 ; $k<count($split) ; $k++){
                     $lowstr=utf8_strtolower($split[$k]);
                     if (strlen(trim($split[$k]))>0 && !in_array($lowstr,$displayed)) {
                        if ($count_display) {
                           $out .= "<br>";
                        }
                        $count_display++;
                        $out.= $split[$k];
                        $displayed[]=$lowstr;
                     }
                  }
               }
               return $out;
            }
            break;

         case "glpi_contracts.duration" :
         case "glpi_contracts.notice" :
         case "glpi_contracts.periodicity" :
         case "glpi_contracts.billing" :
            if (!empty($data[$NAME.$num])) {
               $split=explode('$$$$', $data[$NAME.$num]);
               $output = "";
               foreach ($split as $duration) {
                  $output .= (empty($output)?'':'<br>') . $duration . " " . $LANG['financial'][57];
               }
               return $output;
            }
            return "&nbsp;";

         case "glpi_contracts.renewal" :
            return Contract::getContractRenewalName($data[$NAME.$num]);

         case "glpi_contracts.expire_notice" : // ajout jmd
            if ($data[$NAME.$num]!='' && !empty($data[$NAME.$num])) {
               return getExpir($data[$NAME.$num],$data[$NAME.$num."_2"],$data[$NAME.$num."_3"]);
            }
            return "&nbsp;";

         case "glpi_contracts.expire" : // ajout jmd
            if ($data[$NAME.$num]!='' && !empty($data[$NAME.$num])){
               return getExpir($data[$NAME.$num],$data[$NAME.$num."_2"]);
            }
            return "&nbsp;";

         case "glpi_infocoms.sink_time" :
            if (!empty($data[$NAME.$num])) {
               $split=explode("$$$$",$data[$NAME.$num]);
               $out='';
               foreach($split as $val) {
                  $out .= (empty($out)?'':'<br>');
                  if ($val>0) {
                     $out .= $val." ".$LANG['financial'][9];
                  }
               }
               return $out;
            }
            return "&nbsp;";

         case "glpi_infocoms.warranty_duration" :
            if (!empty($data[$NAME.$num])) {
               $split=explode("$$$$",$data[$NAME.$num]);
               $out='';
               foreach($split as $val) {
                  $out .= (empty($out)?'':'<br>');
                  if ($val>0) {
                     $out .= $val." ".$LANG['financial'][57];
                  }
                  if ($val<0) {
                     $out .= $LANG['financial'][2];
                  }
               }
               return $out;
            }
            return "&nbsp;";

         case "glpi_infocoms.sink_type" :
            $split=explode("$$$$",$data[$NAME.$num]);
            $out='';
            foreach($split as $val) {
               $out .= (empty($out)?'':'<br>').Infocom::getAmortTypeName($val);
            }
            return $out;

         case "glpi_infocoms.alert" :
            if ($data[$NAME.$num]==pow(2,ALERT_END)) {
               return $LANG['financial'][80];
            }
            return "";

         case "glpi_contracts.alert" :
            switch ($data[$NAME.$num]) {
               case pow(2,ALERT_END);
                  return $LANG['buttons'][32];

               case pow(2,ALERT_NOTICE);
                  return $LANG['financial'][10];

               case pow(2,ALERT_END) + pow(2,ALERT_NOTICE);
                  return $LANG['buttons'][32]." + ".$LANG['financial'][10];
            }
            return "";

         case "glpi_tickets.count" :
            if ($data[$NAME.$num]>0 && haveRight("show_all_ticket","1")) {
               $out= "<a href=\"".$CFG_GLPI["root_doc"]."/front/ticket.php?reset=".
                     "reset_before&status=all&itemtype=$itemtype&items_id=".$data['id']."\">";
               $out .= $data[$NAME.$num];
               $out .= "</a>";
            } else {
               $out= $data[$NAME.$num];
            }
            return $out;

         case "glpi_softwarelicenses.number" :
            if ($data[$NAME.$num."_2"]==-1) {
               return $LANG['software'][4];
            }
            if (empty($data[$NAME.$num])) {
               return 0;
            }
            return $data[$NAME.$num];

         case "glpi_auth_tables.name" :
            return Auth::getMethodName($data[$NAME.$num], $data[$NAME.$num."_2"], 1,
                                    $data[$NAME.$num."_3"].$data[$NAME.$num."_4"]);

         case "glpi_reservationitems.comment" :
            if (empty($data[$NAME.$num])) {
               return "<a href='".$CFG_GLPI["root_doc"]."/front/reservationitem.form.php?id=".
                        $data["refID"]."' title='".$LANG['reservation'][22]."'>".$LANG['common'][49].
                     "</a>";
            }
            return "<a href='".$CFG_GLPI["root_doc"]."/front/reservationitem.form.php?id=".
                     $data['refID']."' title='".$LANG['reservation'][22]."'>".
                     resume_text($data[$NAME.$num])."</a>";

         case 'glpi_crontasks.description' :
            $tmp = new CronTask();
            return $tmp->getDescription($data['id']);

         case 'glpi_crontasks.state':
            return CronTask::getStateName($data[$NAME.$num]);

         case 'glpi_crontasks.mode':
            return CronTask::getModeName($data[$NAME.$num]);
         case 'glpi_crontasks.itemtype':
            if ($plug=isPluginItemType($data[$NAME.$num])) {
               return $plug['plugin'];
            }
            return '';
      }


      //// Default case

      // Link with plugin tables : need to know left join structure
      if (preg_match("/^glpi_plugin_([a-z0-9]+)/", $table.'.'.$field, $matches)) {
         if (count($matches)==2) {
            $plug=$matches[1];
            $function='plugin_'.$plug.'_giveItem';
            if (function_exists($function)) {
               $out=$function($itemtype,$ID,$data,$num);
               if (!empty($out)) {
                  return $out;
               }
            }
         }
      }
      $unit='';
      if (isset($searchopt[$ID]['unit'])) {
         $unit=$searchopt[$ID]['unit'];
      }

      // Preformat items
      if (isset($searchopt[$ID]["datatype"])) {
         switch ($searchopt[$ID]["datatype"]) {
            case "itemlink" :
               if (!empty($data[$NAME.$num."_2"])) {
                  if (isset($searchopt[$ID]["itemlink_type"])) {
                     $link=getItemTypeFormURL($searchopt[$ID]["itemlink_type"]);
                  } else {
                     $link=getItemTypeFormURL($itemtype);
                  }
                  $out  = "<a href=\"".$link;
                  $out .= (strstr($link,'?') ?'&amp;' :  '?');
                  $out .= 'id='.$data[$NAME.$num."_2"]."\">";
                  $out .= $data[$NAME.$num].$unit;
                  if ($_SESSION["glpiis_ids_visible"] || empty($data[$NAME.$num])) {
                     $out .= " (".$data[$NAME.$num."_2"].")";
                  }
                  $out .= "</a>";
                  return $out;
               } else if (isset($searchopt[$ID]["itemlink_type"])) {
                  $out="";
                  $split=explode("$$$$",$data[$NAME.$num]);
                  $count_display=0;
                  for ($k=0 ; $k<count($split) ; $k++) {
                     if (strlen(trim($split[$k]))>0) {
                        $split2=explode("$$",$split[$k]);
                        if (isset($split2[1]) && $split2[1]>0) {
                           if ($count_display) {
                              $out .= "<br>";
                           }
                           $count_display++;
                           $page=getItemTypeFormURL($searchopt[$ID]["itemlink_type"]);
                           $page .= (strpos($page,'?') ? '&id' : '?id');
                           $out .= "<a href='$page=".$split2[1]."'>";
                           $out .= $split2[0].$unit;
                           if ($_SESSION["glpiis_ids_visible"] || empty($split2[0])) {
                              $out .= " (".$split2[1].")";
                           }
                           $out .= "</a>";
                        }
                     }
                  }
                  return $out;
               }
               break;

            case "text" :
               return str_replace('$$$$','<br>',nl2br($data[$NAME.$num]));

            case "date" :
               $split=explode("$$$$",$data[$NAME.$num]);
               $out='';
               foreach($split as $val) {
                  $out .= (empty($out)?'':'<br>').convDate($val);
               }
               return $out;

            case "datetime" :
               return convDateTime($data[$NAME.$num]);

            case "timestamp" :
               return timestampToString($data[$NAME.$num]);

            case "realtime" :
               return getRealtime($data[$NAME.$num]);

            case "date_delay" :
               $split = explode('$$$$',$data[$NAME.$num]);
               $out='';
               foreach($split as $val) {
                  if (strpos($val,',')) {
                     list($dat,$dur)=explode(',',$val);
                     if (!empty($dat)) {
                        $out .= (empty($out)?'':'<br>').getWarrantyExpir($dat,$dur);
                     }
                  }
               }
   /*          if ($data[$NAME.$num]!='' && !empty($data[$NAME.$num])) {
                  return getWarrantyExpir($data[$NAME.$num],$data[$NAME.$num."_2"]);
               }
   */
               return (empty($out) ? "&nbsp;" : $out);

            case "email" :
               $email=trim($data[$NAME.$num]);
               if (!empty($email)) {
                  return "<a href='mailto:$email'>$email</a>";
               }
               return "&nbsp;";

            case "weblink" :
               $orig_link=trim($data[$NAME.$num]);
               if (!empty($orig_link)) {
                  // strip begin of link
                  $link=preg_replace('/https?:\/\/(www.)?/','',$orig_link);
                  $link=preg_replace('/\/$/','',$link);
                  if (utf8_strlen($link)>30) {
                     $link=utf8_substr($link,0,30)."...";
                  }
                  return "<a href=\"$orig_link\" target='_blank'>$link</a>";
               }
               return "&nbsp;";

            case "number" :
               if (isset($searchopt[$ID]['forcegroupby'])
                  && $searchopt[$ID]['forcegroupby']) {
                  $out="";
                  $split=explode("$$$$",$data[$NAME.$num]);
                  $count_display=0;
                  for ($k=0 ; $k<count($split) ; $k++) {
                     if (strlen(trim($split[$k]))>0) {
                        if ($count_display) {
                           $out.= "<br>";
                        }
                        $count_display++;
                        $out .= str_replace(' ','&nbsp;',formatNumber($split[$k],false,0)).$unit;
                     }
                  }
                  return $out;
               }
               return str_replace(' ','&nbsp;',formatNumber($data[$NAME.$num],false,0)).$unit;

            case "decimal" :
               if (isset($searchopt[$ID]['forcegroupby'])
                  && $searchopt[$ID]['forcegroupby']) {
                  $out="";
                  $split=explode("$$$$",$data[$NAME.$num]);
                  $count_display=0;
                  for ($k=0 ; $k<count($split) ; $k++) {
                     if (strlen(trim($split[$k]))>0) {
                        if ($count_display) {
                           $out.= "<br>";
                        }
                        $count_display++;
                        $out .= str_replace(' ','&nbsp;',formatNumber($split[$k])).$unit;
                     }
                  }
                  return $out;
               }
               return str_replace(' ','&nbsp;',formatNumber($data[$NAME.$num])).$unit;

            case "bool" :
               return Dropdown::getYesNo($data[$NAME.$num]).$unit;
         }
      }

      // Manage items with need group by / group_concat
      if (isset($searchopt[$ID]['forcegroupby'])
         && $searchopt[$ID]['forcegroupby']) {
         $out="";
         $split=explode("$$$$",$data[$NAME.$num]);
         $count_display=0;
         for ($k=0 ; $k<count($split) ; $k++) {
            if (strlen(trim($split[$k]))>0) {
               if ($count_display) {
                  $out.= "<br>";
               }
               $count_display++;
               $out .= $split[$k].$unit;
            }
         }
         return $out;
      }

      return $data[$NAME.$num].$unit;
   }


   /**
   * Completion of the URL $_GET values with the $_SESSION values or define default values
   *
   * @param $itemtype item type to manage
   * @param $usesession Use datas save in session
   * @param $save Save params to session
   * @return nothing
   */
   static function manageGetValues($itemtype,$usesession=true,$save=true) {
      global $_GET,$DB;

      $tab=array();

      $default_values["start"]=0;
      $default_values["order"]="ASC";
      $default_values["is_deleted"]=0;
      $default_values["distinct"]="N";
      $default_values["link"]=array();
      $default_values["field"]=array(0=>"view");
      $default_values["contains"]=array(0=>"");
      $default_values["searchtype"]=array(0=>"");
      $default_values["link2"]=array();
      $default_values["field2"]=array(0=>"view");
      $default_values["contains2"]=array(0=>"");
      $default_values["itemtype2"]="";
      $default_values["sort"]=1;

      // First view of the page : try to load a bookmark
      if ($usesession && !isset($_SESSION['glpisearch'][$itemtype])) {
         $query = "SELECT `bookmarks_id`
                  FROM `glpi_bookmarks_users`
                  WHERE `users_id`='".$_SESSION['glpiID']."'
                        AND `itemtype` = '$itemtype'";
         if ($result=$DB->query($query)) {
            if ($DB->numrows($result)>0) {
               $IDtoload=$DB->result($result,0,0);
               // Set session variable
               $_SESSION['glpisearch'][$itemtype]=array();
               // Load bookmark on main window
               $bookmark=new Bookmark();
               $bookmark->load($IDtoload,false);
            }
         }
      }
      if ($usesession
         && (isset($_GET["reset_before"]) || (isset($_GET["reset"]) && $_GET["reset"]="reset_before"))) {

         if (isset($_SESSION['glpisearch'][$itemtype])) {
            unset($_SESSION['glpisearch'][$itemtype]);
         }
         if (isset($_SESSION['glpisearchcount'][$itemtype])) {
            unset($_SESSION['glpisearchcount'][$itemtype]);
         }
         if (isset($_SESSION['glpisearchcount2'][$itemtype])) {
            unset($_SESSION['glpisearchcount2'][$itemtype]);
         }
         // Bookmark use
         if (isset($_GET["glpisearchcount"])) {
            $_SESSION["glpisearchcount"][$itemtype]=$_GET["glpisearchcount"];
         }
         // Bookmark use
         if (isset($_GET["glpisearchcount2"])) {
            $_SESSION["glpisearchcount2"][$itemtype]=$_GET["glpisearchcount2"];
         }
      }

      if (is_array($_GET) && $save) {
         foreach ($_GET as $key => $val) {
            $_SESSION['glpisearch'][$itemtype][$key]=$val;
         }
      }

      foreach ($default_values as $key => $val) {
         if (!isset($_GET[$key])) {
            if ($usesession && isset($_SESSION['glpisearch'][$itemtype][$key])) {
               $_GET[$key]=$_SESSION['glpisearch'][$itemtype][$key];
            } else {
               $_GET[$key] = $val;
               $_SESSION['glpisearch'][$itemtype][$key] = $val;
            }
         }
      }

      if (!isset($_SESSION["glpisearchcount"][$itemtype])) {
         if (isset($_GET["glpisearchcount"])) {
            $_SESSION["glpisearchcount"][$itemtype]=$_GET["glpisearchcount"];
         } else {
            $_SESSION["glpisearchcount"][$itemtype]=1;
         }
      }
      if (!isset($_SESSION["glpisearchcount2"][$itemtype])) {
         // Set in URL for bookmark
         if (isset($_GET["glpisearchcount2"])) {
            $_SESSION["glpisearchcount2"][$itemtype]=$_GET["glpisearchcount2"];
         } else {
            $_SESSION["glpisearchcount2"][$itemtype]=0;
         }
      }
   }


   /**
   * Clean search options depending of user active profile
   *
   * @param $itemtype item type to manage
   * @param $action action which is used to manupulate searchoption (r/w)
   * @return clean $SEARCH_OPTION array
   */
   static function getCleanedOptions($itemtype,$action='r') {
      global $CFG_GLPI;

      $options=&Search::getOptions($itemtype);
      $todel=array();
      if (!haveRight('infocom',$action) && in_array($itemtype,$CFG_GLPI["infocom_types"])) {
         $todel=array_merge($todel,array('financial',
                                       25,26,27,28,37,38,50,51,52,53,54,55,56,57,58,59,120,122));
      }

      if (!haveRight('contract',$action) && in_array($itemtype,$CFG_GLPI["infocom_types"])) {
         $todel=array_merge($todel,array('financial',
                                       29,30,130,131,132,133,134,135,136,137,138));
      }

      if ($itemtype == 'Computer') {
         if (!haveRight('networking',$action)) {
            $todel=array_merge($todel,array('network',
                                          20,21,22,83,84,85));
         }
         if (!$CFG_GLPI['use_ocs_mode'] || !haveRight('view_ocsng',$action)) {
            $todel=array_merge($todel,array('ocsng',
                                          100,101,102,103));
         }
      }
      if (!haveRight('notes',$action)) {
         $todel[]=90;
      }

      if (count($todel)) {
         foreach ($todel as $ID) {
            if (isset($options[$ID])) {
               unset($options[$ID]);
            }
         }
      }

      return $options;
   }


   /**
   * Get the SEARCH_OPTION array
   *
   * @param $itemtype
   *
   * @return the reference to  array of search options for the given item type
   **/
   static function &getOptions($itemtype) {
      global $LANG, $CFG_GLPI;

      static $search = array();

      if (!isset($search[$itemtype])) {

         // standard type first
         if ($itemtype=='States') {
            $search[$itemtype]['common'] = $LANG['common'][32];

            $search['States'][1]['table']     = 'state_types';
            $search['States'][1]['field']     = 'name';
            $search['States'][1]['linkfield'] = 'name';
            $search['States'][1]['name']      = $LANG['common'][16];
            $search['States'][1]['datatype']  = 'itemlink';

            $search['States'][2]['table']     = 'state_types';
            $search['States'][2]['field']     = 'id';
            $search['States'][2]['linkfield'] = 'id';
            $search['States'][2]['name']      = $LANG['common'][2];

            $search['States'][31]['table']     = 'glpi_states';
            $search['States'][31]['field']     = 'name';
            $search['States'][31]['linkfield'] = 'states_id';
            $search['States'][31]['name']      = $LANG['state'][0];

            $search['States'][3]['table']     = 'glpi_locations';
            $search['States'][3]['field']     = 'completename';
            $search['States'][3]['linkfield'] = 'locations_id';
            $search['States'][3]['name']      = $LANG['common'][15];

            $search['States'][5]['table']     = 'state_types';
            $search['States'][5]['field']     = 'serial';
            $search['States'][5]['linkfield'] = 'serial';
            $search['States'][5]['name']      = $LANG['common'][19];

            $search['States'][6]['table']     = 'state_types';
            $search['States'][6]['field']     = 'otherserial';
            $search['States'][6]['linkfield'] = 'otherserial';
            $search['States'][6]['name']      = $LANG['common'][20];

            $search['States'][16]['table']     = 'state_types';
            $search['States'][16]['field']     = 'comment';
            $search['States'][16]['linkfield'] = 'comment';
            $search['States'][16]['name']      = $LANG['common'][25];
            $search['States'][16]['datatype']  = 'text';

            $search['States'][70]['table']     = 'glpi_users';
            $search['States'][70]['field']     = 'name';
            $search['States'][70]['linkfield'] = 'users_id';
            $search['States'][70]['name']      = $LANG['common'][34];

            $search['States'][71]['table']     = 'glpi_groups';
            $search['States'][71]['field']     = 'name';
            $search['States'][71]['linkfield'] = 'groups_id';
            $search['States'][71]['name']      = $LANG['common'][35];

            $search['States'][19]['table']     = 'state_types';
            $search['States'][19]['field']     = 'date_mod';
            $search['States'][19]['linkfield'] = '';
            $search['States'][19]['name']      = $LANG['common'][26];
            $search['States'][19]['datatype']  = 'datetime';

            $search['States'][23]['table']     = 'glpi_manufacturers';
            $search['States'][23]['field']     = 'name';
            $search['States'][23]['linkfield'] = 'manufacturers_id';
            $search['States'][23]['name']      = $LANG['common'][5];

            $search['States'][24]['table']     = 'glpi_users';
            $search['States'][24]['field']     = 'name';
            $search['States'][24]['linkfield'] = 'users_id_tech';
            $search['States'][24]['name']      = $LANG['common'][10];

            $search['States'][80]['table']     = 'glpi_entities';
            $search['States'][80]['field']     = 'completename';
            $search['States'][80]['linkfield'] = 'entities_id';
            $search['States'][80]['name']      = $LANG['entity'][0];
         } else if (class_exists($itemtype)) {
            $item = new $itemtype();
            $search[$itemtype] = $item->getSearchOptions();
         }

         if (in_array($itemtype, $CFG_GLPI["contract_types"])) {
            $search[$itemtype]['contract'] = $LANG['Menu'][25];

            $search[$itemtype][29]['table']         = 'glpi_contracts';
            $search[$itemtype][29]['field']         = 'name';
            $search[$itemtype][29]['linkfield']     = '';
            $search[$itemtype][29]['name']          = $LANG['common'][16]." ".$LANG['financial'][1];
            $search[$itemtype][29]['forcegroupby']  = true;
            $search[$itemtype][29]['datatype']      = 'itemlink';
            $search[$itemtype][29]['itemlink_type'] = 'Contract';

            $search[$itemtype][30]['table']        = 'glpi_contracts';
            $search[$itemtype][30]['field']        = 'num';
            $search[$itemtype][30]['linkfield']    = '';
            $search[$itemtype][30]['name']         = $LANG['financial'][4]." ".$LANG['financial'][1];
            $search[$itemtype][30]['forcegroupby'] = true;

            $search[$itemtype][130]['table']        = 'glpi_contracts';
            $search[$itemtype][130]['field']        = 'duration';
            $search[$itemtype][130]['linkfield']    = '';
            $search[$itemtype][130]['name']         = $LANG['financial'][8]." ".$LANG['financial'][1];
            $search[$itemtype][130]['forcegroupby'] = true;

            $search[$itemtype][131]['table']        = 'glpi_contracts';
            $search[$itemtype][131]['field']        = 'periodicity';
            $search[$itemtype][131]['linkfield']    = '';
            $search[$itemtype][131]['name']         = $LANG['financial'][69];
            $search[$itemtype][131]['forcegroupby'] = true;

            $search[$itemtype][132]['table']        = 'glpi_contracts';
            $search[$itemtype][132]['field']        = 'begin_date';
            $search[$itemtype][132]['linkfield']    = '';
            $search[$itemtype][132]['name']         = $LANG['search'][8]." ".$LANG['financial'][1];
            $search[$itemtype][132]['forcegroupby'] = true;
            $search[$itemtype][132]['datatype']     = 'date';

            $search[$itemtype][133]['table']        = 'glpi_contracts';
            $search[$itemtype][133]['field']        = 'accounting_number';
            $search[$itemtype][133]['linkfield']    = '';
            $search[$itemtype][133]['name']         = $LANG['financial'][13]." ".$LANG['financial'][1];
            $search[$itemtype][133]['forcegroupby'] = true;

            $search[$itemtype][134]['table']         = 'glpi_contracts';
            $search[$itemtype][134]['field']         = 'end_date';
            $search[$itemtype][134]['linkfield']     = '';
            $search[$itemtype][134]['name']          = $LANG['search'][9]." ".$LANG['financial'][1];
            $search[$itemtype][134]['forcegroupby']  = true;
            $search[$itemtype][134]['datatype']      = 'date_delay';
            $search[$itemtype][134]['datafields'][1] = 'begin_date';
            $search[$itemtype][134]['datafields'][2] = 'duration';

            $search[$itemtype][135]['table']        = 'glpi_contracts';
            $search[$itemtype][135]['field']        = 'notice';
            $search[$itemtype][135]['linkfield']    = '';
            $search[$itemtype][135]['name']         = $LANG['financial'][10]." ".$LANG['financial'][1];
            $search[$itemtype][135]['forcegroupby'] = true;

            $search[$itemtype][136]['table']        = 'glpi_contracts';
            $search[$itemtype][136]['field']        = 'cost';
            $search[$itemtype][136]['linkfield']    = '';
            $search[$itemtype][136]['name']         = $LANG['financial'][5]." ".$LANG['financial'][1];
            $search[$itemtype][136]['forcegroupby'] = true;
            $search[$itemtype][136]['datatype']     = 'decimal';

            $search[$itemtype][137]['table']        = 'glpi_contracts';
            $search[$itemtype][137]['field']        = 'billing';
            $search[$itemtype][137]['linkfield']    = '';
            $search[$itemtype][137]['name']       = $LANG['financial'][11]." ".$LANG['financial'][1];
            $search[$itemtype][137]['forcegroupby'] = true;

            $search[$itemtype][138]['table']        = 'glpi_contracts';
            $search[$itemtype][138]['field']        = 'renewal';
            $search[$itemtype][138]['linkfield']    = '';
            $search[$itemtype][138]['name']      = $LANG['financial'][107]." ".$LANG['financial'][1];
            $search[$itemtype][138]['forcegroupby'] = true;
         }

         if (in_array($itemtype, $CFG_GLPI["infocom_types"])) {
            $search[$itemtype]['financial'] = $LANG['financial'][3];

            $search[$itemtype][25]['table']        = 'glpi_infocoms';
            $search[$itemtype][25]['field']        = 'immo_number';
            $search[$itemtype][25]['linkfield']    = '';
            $search[$itemtype][25]['name']         = $LANG['financial'][20];
            $search[$itemtype][25]['forcegroupby'] = true;

            $search[$itemtype][26]['table']        = 'glpi_infocoms';
            $search[$itemtype][26]['field']        = 'order_number';
            $search[$itemtype][26]['linkfield']    = '';
            $search[$itemtype][26]['name']         = $LANG['financial'][18];
            $search[$itemtype][26]['forcegroupby'] = true;

            $search[$itemtype][27]['table']        = 'glpi_infocoms';
            $search[$itemtype][27]['field']        = 'delivery_number';
            $search[$itemtype][27]['linkfield']    = '';
            $search[$itemtype][27]['name']         = $LANG['financial'][19];
            $search[$itemtype][27]['forcegroupby'] = true;

            $search[$itemtype][28]['table']        = 'glpi_infocoms';
            $search[$itemtype][28]['field']        = 'bill';
            $search[$itemtype][28]['linkfield']    = '';
            $search[$itemtype][28]['name']         = $LANG['financial'][82];
            $search[$itemtype][28]['forcegroupby'] = true;

            $search[$itemtype][37]['table']        = 'glpi_infocoms';
            $search[$itemtype][37]['field']        = 'buy_date';
            $search[$itemtype][37]['linkfield']    = '';
            $search[$itemtype][37]['name']         = $LANG['financial'][14];
            $search[$itemtype][37]['datatype']     = 'date';
            $search[$itemtype][37]['forcegroupby'] = true;

            $search[$itemtype][38]['table']        = 'glpi_infocoms';
            $search[$itemtype][38]['field']        = 'use_date';
            $search[$itemtype][38]['linkfield']    = '';
            $search[$itemtype][38]['name']         = $LANG['financial'][76];
            $search[$itemtype][38]['datatype']     = 'date';
            $search[$itemtype][38]['forcegroupby'] = true;

            $search[$itemtype][50]['table']        = 'glpi_budgets';
            $search[$itemtype][50]['field']        = 'name';
            $search[$itemtype][50]['linkfield']    = '';
            $search[$itemtype][50]['name']         = $LANG['financial'][87];
            $search[$itemtype][50]['forcegroupby'] = true;

            $search[$itemtype][51]['table']        = 'glpi_infocoms';
            $search[$itemtype][51]['field']        = 'warranty_duration';
            $search[$itemtype][51]['linkfield']    = '';
            $search[$itemtype][51]['name']         = $LANG['financial'][15];
            $search[$itemtype][51]['forcegroupby'] = true;

            $search[$itemtype][52]['table']        = 'glpi_infocoms';
            $search[$itemtype][52]['field']        = 'warranty_info';
            $search[$itemtype][52]['linkfield']    = '';
            $search[$itemtype][52]['name']         = $LANG['financial'][16];
            $search[$itemtype][52]['forcegroupby'] = true;

            $search[$itemtype][120]['table']         = 'glpi_infocoms';
            $search[$itemtype][120]['field']         = 'end_warranty';
            $search[$itemtype][120]['linkfield']     = '';
            $search[$itemtype][120]['name']          = $LANG['financial'][80];
            $search[$itemtype][120]['datatype']      = 'date';
            $search[$itemtype][120]['datatype']      = 'date_delay';
            $search[$itemtype][120]['datafields'][1] = 'buy_date';
            $search[$itemtype][120]['datafields'][2] = 'warranty_duration';
            $search[$itemtype][120]['forcegroupby']  = true;

            $search[$itemtype][53]['table']        = 'glpi_suppliers_infocoms';
            $search[$itemtype][53]['field']        = 'name';
            $search[$itemtype][53]['linkfield']    = '';
            $search[$itemtype][53]['name']         = $LANG['financial'][26];
            $search[$itemtype][53]['forcegroupby'] = true;

            $search[$itemtype][54]['table']        = 'glpi_infocoms';
            $search[$itemtype][54]['field']        = 'value';
            $search[$itemtype][54]['linkfield']    = '';
            $search[$itemtype][54]['name']         = $LANG['financial'][21];
            $search[$itemtype][54]['datatype']     = 'decimal';
            $search[$itemtype][54]['width']        = 100;
            $search[$itemtype][54]['forcegroupby'] = true;

            $search[$itemtype][55]['table']        = 'glpi_infocoms';
            $search[$itemtype][55]['field']        = 'warranty_value';
            $search[$itemtype][55]['linkfield']    = '';
            $search[$itemtype][55]['name']         = $LANG['financial'][78];
            $search[$itemtype][55]['datatype']     = 'decimal';
            $search[$itemtype][55]['width']        = 100;
            $search[$itemtype][55]['forcegroupby'] = true;

            $search[$itemtype][56]['table']        = 'glpi_infocoms';
            $search[$itemtype][56]['field']        = 'sink_time';
            $search[$itemtype][56]['linkfield']    = '';
            $search[$itemtype][56]['name']         = $LANG['financial'][23];
            $search[$itemtype][56]['forcegroupby'] = true;

            $search[$itemtype][57]['table']        = 'glpi_infocoms';
            $search[$itemtype][57]['field']        = 'sink_type';
            $search[$itemtype][57]['linkfield']    = '';
            $search[$itemtype][57]['name']         = $LANG['financial'][22];
            $search[$itemtype][57]['forcegroupby'] = true;

            $search[$itemtype][58]['table']        = 'glpi_infocoms';
            $search[$itemtype][58]['field']        = 'sink_coeff';
            $search[$itemtype][58]['linkfield']    = '';
            $search[$itemtype][58]['name']         = $LANG['financial'][77];
            $search[$itemtype][58]['forcegroupby'] = true;

            $search[$itemtype][59]['table']        = 'glpi_infocoms';
            $search[$itemtype][59]['field']        = 'alert';
            $search[$itemtype][59]['linkfield']    = '';
            $search[$itemtype][59]['name']         = $LANG['common'][41];
            $search[$itemtype][59]['forcegroupby'] = true;

            $search[$itemtype][122]['table']       = 'glpi_infocoms';
            $search[$itemtype][122]['field']       = 'comment';
            $search[$itemtype][122]['linkfield']   = '';
            $search[$itemtype][122]['name']        = $LANG['common'][25]." - ".$LANG['financial'][3];
            $search[$itemtype][122]['datatype']    = 'text';
            $search[$itemtype][122]['forcegroupby'] = true;
         }

         // Search options added by plugins
         $plugsearch=getPluginSearchOptions($itemtype);
         if (count($plugsearch)) {
            $search[$itemtype] += array('plugins' => $LANG['common'][29]);
            $search[$itemtype] += $plugsearch;
         }
      }
      return $search[$itemtype];
   }

   /**
   * Convert an array to be add in url
   *
   * @param $name name of array
   * @param $array array to be added
   * @return string to add
   *
   */
   static function getArrayUrlLink($name,$array) {

      $out="";
      if (is_array($array) && count($array)>0) {
         foreach($array as $key => $val) {
            $out .= "&amp;".$name."[$key]=".urlencode(stripslashes($val));
         }
      }
      return $out;
   }



   /**
   * Is the search item related to infocoms
   *
   * @param $itemtype item type
   * @param $searchID ID of the element in $SEARCHOPTION
   * @return boolean
   *
   */
   static function isInfocomOption($itemtype,$searchID) {
      global $CFG_GLPI;

      return (($searchID>=25 && $searchID<=28) || ($searchID>=37 && $searchID<=38)
            ||($searchID>=50 && $searchID<=59) || ($searchID>=120 && $searchID<=122))
            && in_array($itemtype,$CFG_GLPI["infocom_types"]);
   }

   static function getActionsFor($itemtype,$field_num) {
      global $LANG;
      $searchopt=&Search::getOptions($itemtype);
      $actions = array('contains'=>$LANG['search'][2],
                       'searchopt' => array());

      if (isset($searchopt[$field_num])) {
         $action['searchopt']=$searchopt[$field_num];
         switch ($searchopt[$field_num]['field']) {
            case 'id' :
               return array('equals'=>$LANG['rulesengine'][0],
                        'searchopt'=>$searchopt[$field_num]);
            case 'name' :
            case 'completename' :
               return array('contains'=>$LANG['search'][2],
                           'equals'=>$LANG['rulesengine'][0],
                           'searchopt'=>$searchopt[$field_num]);
         }
     }

      return $actions;

   }

}
?>
