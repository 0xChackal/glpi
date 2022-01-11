<?php

/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2022 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

namespace Glpi\Console\Database;

use Glpi\Cache\CacheManager;
use Glpi\Console\AbstractCommand;
use Glpi\Console\Command\ForceNoPluginsOptionCommandInterface;
use Glpi\Console\Traits\TelemetryActivationTrait;
use Glpi\Toolbox\VersionParser;
use Migration;
use Session;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Update;

class UpdateCommand extends AbstractCommand implements ForceNoPluginsOptionCommandInterface
{
    use TelemetryActivationTrait;

   /**
    * Error code returned when trying to update from an unstable version.
    *
    * @var integer
    */
    const ERROR_NO_UNSTABLE_UPDATE = 1;

   /**
    * Error code returned when security key file is missing.
    *
    * @var integer
    */
    const ERROR_MISSING_SECURITY_KEY_FILE = 2;

    protected $requires_db_up_to_date = false;

    protected function configure()
    {
        parent::configure();

        $this->setName('glpi:database:update');
        $this->setAliases(['db:update']);
        $this->setDescription(__('Update database schema to new version'));

        $this->addOption(
            'allow-unstable',
            'u',
            InputOption::VALUE_NONE,
            __('Allow update to an unstable version')
        );

        $this->addOption(
            'force',
            'f',
            InputOption::VALUE_NONE,
            __('Force execution of update from v-1 version of GLPI even if schema did not changed')
        );

        $this->registerTelemetryActivationOptions($this->getDefinition());
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {

        global $GLPI_CACHE;
        $GLPI_CACHE = (new CacheManager())->getInstallerCacheInstance(); // Use dedicated "installer" cache

        parent::initialize($input, $output);

        $this->outputWarningOnMissingOptionnalRequirements();

        $this->db->disableTableCaching();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $allow_unstable = $input->getOption('allow-unstable');
        $force          = $input->getOption('force');
        $no_interaction = $input->getOption('no-interaction'); // Base symfony/console option

        $update = new Update($this->db);

       // Initialize entities
        $_SESSION['glpidefault_entity'] = 0;
        Session::initEntityProfiles(2);
        Session::changeProfile(4);

       // Display current/future state information
        $currents            = $update->getCurrents();
        $current_version     = $currents['version'];
        $current_db_version  = $currents['dbversion'];

        global $migration; // Migration scripts are using global migrations
        $migration = new Migration(GLPI_VERSION);
        $migration->setOutputHandler($output);
        $update->setMigration($migration);

        $informations = new Table($output);
        $informations->setHeaders(['', __('Current'), _n('Target', 'Targets', 1)]);
        $informations->addRow([__('Database host'), $this->db->dbhost, '']);
        $informations->addRow([__('Database name'), $this->db->dbdefault, '']);
        $informations->addRow([__('Database user'), $this->db->dbuser, '']);
        $informations->addRow([__('GLPI version'), $current_version, GLPI_VERSION]);
        $informations->addRow([__('GLPI database version'), $current_db_version, GLPI_SCHEMA_VERSION]);
        $informations->render();

        if (Update::isDbUpToDate() && !$force) {
            $output->writeln('<info>' . __('No migration needed.') . '</info>');
            return 0;
        }

        if (!VersionParser::isStableRelease(GLPI_VERSION) && !$allow_unstable) {
           // Prevent unstable update unless explicitly asked
            $output->writeln(
                sprintf(
                    '<error>' . __('%s is not a stable release. Please upgrade manually or add --allow-unstable option.') . '</error>',
                    GLPI_VERSION
                ),
                OutputInterface::VERBOSITY_QUIET
            );
            return self::ERROR_NO_UNSTABLE_UPDATE;
        }

        if ($update->isExpectedSecurityKeyFileMissing()) {
            $output->writeln(
                sprintf(
                    '<error>' . __('The key file "%s" used to encrypt/decrypt sensitive data is missing. You should retrieve it from your previous installation or encrypted data will be unreadable.') . '</error>',
                    $update->getExpectedSecurityKeyFilePath()
                ),
                OutputInterface::VERBOSITY_QUIET
            );

            if ($no_interaction) {
                return self::ERROR_MISSING_SECURITY_KEY_FILE;
            }
        }

        if (!$no_interaction) {
           // Ask for confirmation (unless --no-interaction)
           /** @var \Symfony\Component\Console\Helper\QuestionHelper $question_helper */
            $question_helper = $this->getHelper('question');
            $run = $question_helper->ask(
                $input,
                $output,
                new ConfirmationQuestion(__('Do you want to continue?') . ' [Yes/no]', true)
            );
            if (!$run) {
                $output->writeln(
                    '<comment>' . __('Update aborted.') . '</comment>',
                    OutputInterface::VERBOSITY_VERBOSE
                );
                 return 0;
            }
        }

        $update->doUpdates($current_version, $force);
        $output->writeln('<info>' . __('Migration done.') . '</info>');

        (new CacheManager())->resetAllCaches(); // Ensure cache will not use obsolete data

        $this->handTelemetryActivation($input, $output);

        return 0; // Success
    }

    public function getNoPluginsOptionValue()
    {

        return true;
    }
}
