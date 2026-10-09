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

header("Content-Type: application/json; charset=UTF-8");
Html::header_nocache();

Session::checkLoginUser();

/** @var \DBmysql $DB */
global $DB;

$itemtype = $_POST['itemtype'] ?? null;
$params   = $_POST['params'] ?? null;

if (!is_string($itemtype) || !is_array($params)) {
    http_response_code(400);
    echo json_encode([
        'success'   => false,
        'message'   => __('Required argument missing!'),
    ]);
    return;
}

if (
    !in_array($itemtype, PluginGeolocationGeolocation::getAllowedItemtypes(), true)
    || !Session::haveRight(PluginGeolocationGeolocation::$rightname, READ)
    || !$itemtype::canView()
) {
    http_response_code(403);
    echo json_encode([
        'success'   => false,
        'message'   => __('You don\'t have permission to perform this action.'),
    ]);
    return;
}

$data = Search::prepareDatasForSearch($itemtype, $params);
Search::constructSQL($data);
Search::constructData($data);

$titles = [];
foreach ($data['data']['rows'] as $row) {
    $titles[$row['raw']['id']] = $row['raw']["ITEM_" . $itemtype . "_1"] ?? '';
}

$points = [];
if (count($titles) > 0) {
    $iterator = $DB->request([
        'FROM' => PluginGeolocationGeolocation::getTable(),
        'WHERE' => [
            'itemtype' => $itemtype,
            'items_id' => array_keys($titles),
        ],
    ]);
    foreach ($iterator as $geolocation) {
        $points[$geolocation['id']] = [
            'lat' => $geolocation['latitude'],
            'lng' => $geolocation['longitude'],
            'title' => $titles[$geolocation['items_id']],
            'url' => $itemtype::getFormURLWithID($geolocation['items_id']),
            'count' => 1,
        ];
    }
}
echo json_encode(['points' => $points]);
