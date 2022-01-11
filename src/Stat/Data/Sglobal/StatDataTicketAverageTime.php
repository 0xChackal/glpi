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

// Using sglobal instead of global as it is a PHP keyword.
// This is fixed in php 8 so to be changed back when we no longer support php 7.

namespace Glpi\Stat\Data\Sglobal;

use Glpi\Stat\StatDataAlwaysDisplay;
use Session;

class StatDataTicketAverageTime extends StatDataAlwaysDisplay
{
    public function __construct(array $params)
    {
        parent::__construct($params);

        $avgsolved     = $this->getDataByType($params, "inter_avgsolvedtime");
        $avgclosed     = $this->getDataByType($params, "inter_avgclosedtime");
        $avgactiontime = $this->getDataByType($params, "inter_avgactiontime");

       // Convert to hours
        foreach ($avgsolved as &$val) {
            $val = round($val / HOUR_TIMESTAMP, 2);
        }
        unset($val);
        foreach ($avgclosed as &$val) {
            $val = round($val / HOUR_TIMESTAMP, 2);
        }
        unset($val);
        foreach ($avgactiontime as &$val) {
            $val = round($val / HOUR_TIMESTAMP, 2);
        }
        unset($val);

        $this->labels = array_keys($avgsolved);
        $this->series = [
         [
            'name' => __('Closure'),
            'data' => $avgsolved
         ], [
            'name' => __('Resolution'),
            'data' => $avgclosed
         ], [
            'name' => __('Real duration'),
            'data' => $avgactiontime
         ]
        ];
    }

    public function getTitle(): string
    {
        return __('Average time') . " - " .  _n('Hour', 'Hours', Session::getPluralNumber());
    }
}
