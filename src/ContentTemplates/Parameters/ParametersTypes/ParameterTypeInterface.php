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

namespace Glpi\ContentTemplates\Parameters\ParametersTypes;

use Glpi\ContentTemplates\Parameters\TemplatesParametersInterface;

/**
 * Interface for parameters types.
 *
 * @since 10.0.0
 */
interface ParameterTypeInterface
{
   /**
    * To be defined in each subclasses, convert the parameter data into an array
    * that can be shared to the client side code as json and used for autocompletion.
    *
    * @return array
    */
    public function compute(): array;

   /**
    * Label to use for this parameter's documentation
    *
    * @return string
    */
    public function getDocumentationLabel(): string;

   /**
    * Recommended usage (twig code) to use for this parameter's documentation
    *
    * @param string|null $parent
    *
    * @return string
    */
    public function getDocumentationUsage(?string $parent = null): string;

   /**
    * Reference to others parameters for this parameter's documentation
    *
    * @return TemplatesParametersInterface|null
    */
    public function getDocumentationReferences(): ?TemplatesParametersInterface;

   /**
    * Field name for this parameter's documentation
    *
    * @return string
    */
    public function getDocumentationField(): string;
}
