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

$geo = new PluginGeolocationGeolocation();
if (isset($_POST['add'])) {
    $geo->check(-1, CREATE, $_POST);

    $newID = $geo->add($_POST, false);
    Html::back();
} elseif (isset($_POST["purge"])) {
    $geo->check($_POST["id"], PURGE);
    $geo->delete($_POST, 1);
    Html::back();
} elseif (isset($_POST["update"])) {
    $geo->check($_POST["id"], UPDATE);
    if (empty($_POST['latitude']) && empty($_POST['longitude'])) {
        $geo->check($_POST["id"], PURGE);
        $geo->delete($_POST, 1);
    } else {
        $geo->update($_POST);
    }
    Html::back();
}
Html::back();
