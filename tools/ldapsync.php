<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2012 by the INDEPNET Development Team.

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
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */
// @since version 0.83

// Ensure current directory when run from crontab
chdir(dirname($_SERVER["SCRIPT_FILENAME"]));

if (isset($_SERVER['argv'])) {
   for ($i=1 ; $i<$_SERVER['argc'] ; $i++) {
      $it            = explode("=",$_SERVER['argv'][$i], 2);
      $it[0]         = preg_replace('/^--/', '', $it[0]);

      $_GET[$it[0]]  = (isset($it[1]) ? $it[1] : true);
   }
}
if (isset($_GET['help'])) {
   echo "Usage : php ldapsync.php --entity=<id> | --ldap=<id> [ others options ]\n";
   echo "Options values :\n";
   echo "\t--entity      only sync user of this entity\n";
   echo "\t--server      only sync user of entities attached to this server (ID or default)\n";
   echo "\t--profile     only sync user with this profile\n";
   echo "\t--process     number of process to launch, one per entity, GNU/Linux only\n";
   echo "\t--verbose     display a lot of information\n";
   echo "\t--mailentity  send a report to the entity administrator\n";
   echo "\t--mailadmin   send a report to the glpi administrator\n";
   echo "\t--limit       max entities to sync (for debug purpose)\n";
   exit (0);
}

$nbproc = (isset($_GET['process']) ? intval($_GET['process']) : 1);
if ($nbproc < 1) {
   die(sprintf(__('%1$s (%2$s)')."\n", __('Invalid number of process'), $nbproc));

} else if (($nbproc > 1)
           && !(function_exists('pcntl_fork')
           && function_exists('posix_getpid'))) {
   die(__('Multi process need PCNTL and POSIX extension (GNU/Linux only)')."\n");
}


/**
 * @param $pid
 * @param $data
 * @param $server
 * @param $prof
 * @param $verb
 * @param $mail
**/
function syncEntity ($pid, $data, $server, $prof, $verb, $mail) {
   global $DB, $LANG, $CFG_GLPI;

   // Re-establish DB connexion - mandatory in each forked process
   if (!DBConnection::switchToMaster()) {
      printf(__('%1$s: %2$s')."\n", $pid, __('lost DB connection'));
      return 0;
   }
   // Server from entity (if not given from option)
   if ($data['authldaps_id'] > 0) {
      $server = $data['authldaps_id'];
   }

   $entity = new Entity();
   if ($entity->getFromDB($id=$data['entities_id'])) {
      $tps = microtime(true);
      if ($verb) {
         //TRANS: %1$s is pid, %2$s is Synchonizing entity, %3$s is entity name
         echo sprintf(__('%1$s: %2$s %3$s'), $pid, __('Synchonizing entity'),
                       $entity->getField('completename'))."<br>";
         echo "($id, ".sprintf(__('%1$s = %2$s')."\n", __('mail'), $mail).")";
      }

      $sql = "SELECT DISTINCT glpi_users.*
              FROM glpi_users
              INNER JOIN glpi_profiles_users
                  ON (glpi_profiles_users.users_id = glpi_users.id
                      AND glpi_profiles_users.entities_id = $id";
      if ($prof > 0) {
         $sql .= "    AND glpi_profiles_users.profiles_id = $prof";
      }
      $sql .= ")
               WHERE glpi_users.authtype = ".Auth::LDAP;
      if ($server > 0) {
         $sql .= " AND glpi_users.auths_id = $server";
      }

      $users   = array();
      $results = array(AuthLDAP::USER_IMPORTED     => 0,
                       AuthLDAP::USER_SYNCHRONIZED => 0,
                       AuthLDAP::USER_DELETED_LDAP => 0);

      $req = $DB->request($sql);
      $i   = 0;
      $nb  = $req->numrows();

      foreach ($req as $row) {
         $i++;

         $result = AuthLdap::ldapImportUserByServerId(array('method' => AuthLDAP::IDENTIFIER_LOGIN,
                                                            'value'  => $row['name']),
                                                      AuthLDAP::ACTION_SYNCHRONIZE,
                                                      $row['auths_id']);
         if ($result) {
            $results[$result['action']] += 1;
            $users[$row['id']] = $row['name'];

            if ($result['action'] == AuthLDAP::USER_SYNCHRONIZED) {
               if ($verb) {
                  //TRANS: %1$s is pid, %2$s is username, %3$d is actual row, %4$d is total row
                  printf(__('%1$s: User %2$s synchronized (%3$d/%4$d)')."\n",
                         $pid, $row['name'], __('Synchronized'), $i, $nb);
               }
            } else if ($verb) {
               printf(__('%1$s: User %2$s %3$s')."\n", $pid, $row['name'], __('deleted'));
            }
         } else if ($verb) {
            printf(__('%1$s: User %2$s %3$s')."\n", $pid, $row['name'], __('problem with LDAP'));
         }
      }
      $tps = microtime(true)-$tps;
      printf("  %d: Entity: '%s' Synchronized: %d, Deleted from LDAP: %d, Time: %.2f\"\n",
             $pid, $entity->getField('completename'), $results[AuthLDAP::USER_SYNCHRONIZED],
            $results[AuthLDAP::USER_DELETED_LDAP], $tps);

      if ($mail) {
         $report = '';
         $user   = new User();
         foreach ($users as $id => $name) {
            if ($user->getFromDB($id)) {
               $logs = Log::getHistoryData($user, 0, $_SESSION['glpilist_limit'],
                                           "`date_mod`='".$_SESSION['glpi_currenttime']."'");
               if (count($logs)) {
                  $report .= "\n".printf(__('%1$s (%2$s)')."\n", $name, $user->getName());
                  foreach ($logs as $log) {
                     $report .= "\t";
                     if ($log['field']) {
                        $report .= sprintf(__('%1$s: %2$s')."\n", $log['field'],
                                           Html::clean($log['change']));
                     } else {
                        $report .= Html::clean($log['change'])."\n";
                     }
                  }
               }
            } else {
               $report .= "\n".sprintf(__('%1$s %2$s'), $name."\n\t", __('deleted'))."\n";
            }
         }
         if ($report) {
            $report  = __('Synchronization of already imported users')."\n".
                       sprintf(__('%1$s: %2$s'), __('Entity'), $entity->getField('completename'))."\n".
                       sprintf(__('%1$s: %2$s'), __('Date'),
                               Html::convDateTime($_SESSION['glpi_currenttime'])) . "\n" .
                       $report;
            $entdata = new Entity();
            $mmail   = new NotificationMail();
            $mmail->AddCustomHeader("Auto-Submitted: auto-generated");
            $mmail->From      = $CFG_GLPI["admin_email"];
            $mmail->FromName  = "GLPI";
            $mmail->Subject   = sprintf(__('%1$s %2$s'), "[GLPI]", __('LDAP directory link'));
            $mmail->Body = $report."\n--\n".$CFG_GLPI["mailing_signature"];

            if (($mail & 1)
                && $entdata->getFromDB($entity->getField('id'))
                && $entdata->fields['admin_email']) {
               $mmail->AddAddress($entdata->fields['admin_email']);
            } else {
               if (($mail & 1) && $verb) {
                  printf(__('%1$s: %2$s')."\n", $pid, __('No address found for email entity'));
               }
               $mail = ($mail & 2);
            }
            if (($mail & 2)
                && $CFG_GLPI['admin_email']) {
               $mmail->AddAddress($CFG_GLPI['admin_email']);
            } else {
               if (($mail & 2) && $verb) {
                  printf(__('%1$s: %2$s')."\n", $pid, __('No address found for email admin'));
               }
               $mail = ($mail & 1);
            }
            if ($mail) {
               if ($mmail->Send() && $verb) {
                   printf(__('%1$s: %2$s')."\n", $pid, __('Report send by email'));
               }
            } else {
               printf(__('%1$s: Cannot send report to %2$s (email address invalid)')."\n",
                         $pid, $entity->getField('completename'));
            }
         }
      }
      return ($results[AuthLDAP::USER_DELETED_LDAP] + $results[AuthLDAP::USER_SYNCHRONIZED]);
   }
   return 0;
}

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

ini_set('display_errors',1);
restore_error_handler();

if (isset($_GET['verbose'])) {
   $verb = $_GET['verbose'];
} else {
   $verb = false;
}
$server = 0;
if (isset($_GET['entity'])) {
   $crit = array('entities_id' => $_GET['entity']);

} else if (isset($_GET['server'])) {
   if (is_numeric($_GET['server'])) {
      $server = $_GET['server'];
      $crit   = array('authldaps_id' => $server);
   } else {
      $server = AuthLdap::getDefault();
      $crit   = array('authldaps_id' => array(0, $server));
      if ($verb) {
         printf(__('Use default LDAP server %d')."\n", $server);
      }
   }
} else {
   die(__('Entity or server option is mandatory')."\n");
}

if (isset($_GET['limit'])) {
   $crit['LIMIT'] = intval($_GET['limit']);
}

if (isset($_GET['profile'])) {
   $prof = intval($_GET['profile']);
} else {
   $prof = 0;
}

$mail = 0;
if (isset($_GET['mailentity'])) {
   $mail |= 1;
}
if (isset($_GET['mailadmin'])) {
   $mail |= 2;
}

$tps  = microtime(true);
$nb   = 0;
$pids = array();

$rows = array();
foreach ($DB->request('glpi_entitydatas', $crit) as $row) {
   $rows[] = $row;
}
if ($verb) {
   printf(_n('%d entity to synchronize', '%d entities to synchronize', count($rows))."\n",
          count($rows));
}

// DB connection could not be shared with forked process
$DB->close();

foreach ($rows as $row) {
   if ($nbproc == 1) {
      $nb += syncEntity(0, $row, $server, $prof, $verb, $mail);
      continue;
   }
   while (count($pids) >= $nbproc) {
      $pid = pcntl_wait($status);
      if ($pid < 0) {
         die (__('Could not wait')."\n");
      } else {
         $nb++;
         unset($pids[$pid]);
         if ($verb) {
            printf(__('%1$s: %2$s')."\n", $pid, __('ended'));
         }
      }
   }
   $pid = pcntl_fork();
   if ($pid < 0) {
      die(__('Could not fork')."\n");
   } else if ($pid) {
      $pids[$pid] = $pid;
      if ($verb) {
         $mes = sprintf(__('%1$s: %2$s'), $pid, __('started'));
         $mes = sprintf(__('%1$s %2$s'), $mes, "-");
         printf(__('%1$s %2$s')."\n", $mes,
                sprintf(__('%1$s %2$s'), count($pids), __('running')));
      }
   } else  {
      syncEntity(posix_getpid(), $row, $server, $prof, $verb, $mail);
      exit(0);
   }
}

while (count($pids) > 0) {
   $pid = pcntl_wait($status);
   if ($pid < 0) {
      die(__('Could not wait')."\n");
   } else {
      $nb++;
      unset($pids[$pid]);
      if ($verb) {
         $mes = sprintf(__('%1$s: %2$s'), $pid, __('ended'));
         $mes = sprintf(__('%1$s %2$s'), $mes, "-");
         printf(__('%1$s %2$s')."\n", $mes,
                sprintf(_n('waiting for %d running process', 'waiting for %d running processes',
                           count($pids)), count($pids)));
      }
   }
}

$tps = microtime(true)-$tps;
if ($nbproc==1) {
   printf(_n('%1$d user synchronized in %2$s', '%1$d users synchronized in %2$s', $nb)."\n", $nb,
          Html::clean(Html::timestampToString(round($tps,0),true)));
} else {
   printf(_n('%1$d entity synchronized in %2$s', '%1$d entities synchronized in %2$s', $nb)."\n",
          $nb, Html::clean(Html::timestampToString(round($tps,0),true)));
}