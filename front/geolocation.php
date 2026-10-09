<?php

/*
 -------------------------------------------------------------------------
 Geolocation plugin for GLPI
 Copyright (C) 2022 - 2026 by the TICGAL Team.
 https://www.tic.gal
 -------------------------------------------------------------------------
 LICENSE
 This file is part of the Geolocation plugin.
 Geolocation plugin is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 3 of the License, or
 (at your option) any later version.
 Geolocation plugin is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.
 You should have received a copy of the GNU General Public License
 along with Geolocation. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 @package   Geolocation
 @author    the TICGAL team
 @copyright Copyright (C) 2022 - 2026 TICGAL team
 @license   AGPL License 3.0 or (at your option) any later version
            http://www.gnu.org/licenses/agpl-3.0-standalone.html
 @link      https://www.tic.gal
 @since     2022
 ----------------------------------------------------------------------
*/

use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\BadRequestHttpException;

Session::checkLoginUser();

if (!Session::haveRight(PluginGeolocationGeolocation::$rightname, READ)) {
    throw new AccessDeniedHttpException();
}

$itemtype = $_GET['itemtype'] ?? null;
if (!is_string($itemtype) || !in_array($itemtype, PluginGeolocationGeolocation::getAllowedItemtypes(), true)) {
    throw new BadRequestHttpException();
}
if (!$itemtype::canView()) {
    throw new AccessDeniedHttpException();
}

$menu = 'assets';
if (is_a($itemtype, CommonITILObject::class, true)) {
    $menu = 'helpdesk';
}
Html::header($itemtype::getTypeName(Session::getPluralNumber()), '', $menu, $itemtype);

PluginGeolocationGeolocation::show($itemtype);

Html::footer();
