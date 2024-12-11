<?php
/*
 -------------------------------------------------------------------------
 Geolocation plugin for GLPI
 Copyright (C) 2022 by the TICgal Team.
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
 @author    the TICgal team
 @copyright Copyright (c) 2022 TICgal team
 @license   AGPL License 3.0 or (at your option) any later version
				http://www.gnu.org/licenses/agpl-3.0-standalone.html
 @link      https://www.tic.gal
 @since     2022
 ----------------------------------------------------------------------
*/

include("../../../inc/includes.php");
header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkLoginUser();

$result = [];
if (!isset($_POST['itemtype']) || !isset($_POST['params'])) {
	http_response_code(500);
	$result = [
		'success'   => false,
		'message'   => __('Required argument missing!')
	];
} else {
	$itemtype = $_POST['itemtype'];
	$params   = $_POST['params'];

	$data = Search::prepareDatasForSearch($itemtype, $params);
	Search::constructSQL($data);
	Search::constructData($data);

	$rows = $data['data']['rows'];
	$items_id = [];
	$titles = [];
	foreach ($rows as $row) {
		$items_id[] = $row['raw']['id'];
		$titles[$row['raw']['id']] = $row['raw']["ITEM_".$itemtype."_1"];
	}
	$points = [];
	if (count($items_id) > 0) {
		$query = [
			'FROM' => PluginGeolocationGeolocation::getTable(),
			'WHERE' => [
				'itemtype' => $itemtype,
				'items_id' => $items_id
			]
		];
		$iterator = $DB->request($query);
		foreach ($iterator as $result) {
			$points[$result['id']] = [
				'lat' => $result['latitude'],
				'lng' => $result['longitude'],
				'title' => $titles[$result['items_id']],
				'url' => $itemtype::getFormURLWithID($result['items_id']),
				'count' => 1
			];
		}
	}
	$result['points'] = $points;
}
echo json_encode($result);
