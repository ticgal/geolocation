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

function plugin_geolocation_install()
{
	$migration = new Migration(PLUGIN_GEOLOCATION_VERSION);

	foreach (glob(dirname(__FILE__) . '/inc/*') as $filepath) {
		if (preg_match("/inc.(.+)\.class.php/", $filepath, $matches)) {
			$classname = 'PluginGeolocation' . ucfirst($matches[1]);
			include_once($filepath);
			if (method_exists($classname, 'install')) {
				$classname::install($migration);
			}
		}
	}
	$migration->executeMigration();

	return true;
}

function plugin_geolocation_uninstall()
{
	$migration = new Migration(PLUGIN_GEOLOCATION_VERSION);

	foreach (glob(dirname(__FILE__) . '/inc/*') as $filepath) {
		if (preg_match("/inc.(.+)\.class.php/", $filepath, $matches)) {
			$classname = 'PluginGeolocation' . ucfirst($matches[1]);
			include_once($filepath);
			if (method_exists($classname, 'uninstall')) {
				$classname::uninstall($migration);
			}
		}
	}
	$migration->executeMigration();

	return true;
}

function plugin_geolocation_postitemform($params = [])
{

	if (isset($params['item']) && $params['item'] instanceof CommonDBTM) {
		if (Session::haveRight(PluginGeolocationGeolocation::$rightname, READ)) {
			switch ($params['item']::getType()) {
				case Ticket::getType():
					PluginGeolocationGeolocation::showGeolocation($params['item']);
					break;
			}
		}
	}
}

function plugin_geolocation_ticket_add(Ticket $ticket)
{
	if (isset($ticket->input['latitude']) && !empty($ticket->input['latitude']) && isset($ticket->input['longitude']) && !empty($ticket->input['longitude'])) {
		if (Session::haveRight(PluginGeolocationGeolocation::$rightname, CREATE)) {
			$input = [
				'itemtype' => $ticket::getType(),
				'items_id' => $ticket->getID(),
				'latitude' => $ticket->input['latitude'],
				'longitude' => $ticket->input['longitude'],
			];
			$geolocation = new PluginGeolocationGeolocation();
			$geolocation->add($input);
		}
	} elseif (isset($ticket->input['locations_id']) && $ticket->input['locations_id'] > 0) {
		$location = new Location();
		$location->getFromDB($ticket->input['locations_id']);
		if (!empty($location->fields['latitude']) && !empty($location->fields['longitude'])) {
			$input = [
				'itemtype' => $ticket::getType(),
				'items_id' => $ticket->getID(),
				'latitude' => $location->fields['latitude'],
				'longitude' => $location->fields['longitude'],
			];
			$geolocation = new PluginGeolocationGeolocation();
			$geolocation->add($input);
		}
	}
}

function plugin_geolocation_ticket_update(Ticket $ticket)
{
	if (isset($ticket->input['latitude']) &&  isset($ticket->input['longitude'])) {
		$geolocation = new PluginGeolocationGeolocation();
		if (!empty($ticket->input['latitude']) && !empty($ticket->input['longitude'])) {
			if ($geolocation->getFromDBByCrit(['itemtype' => $ticket::getType(), 'items_id' => $ticket->getID()])) {
				if ($geolocation::canUpdate()) {
					$input = [
						'id' => $geolocation->getID(),
						'latitude' => $ticket->input['latitude'],
						'longitude' => $ticket->input['longitude'],
					];
					$geolocation->update($input);
				}
			} else {
				if (Session::haveRight(PluginGeolocationGeolocation::$rightname, CREATE)) {
					$input = [
						'itemtype' => $ticket::getType(),
						'items_id' => $ticket->getID(),
						'latitude' => $ticket->input['latitude'],
						'longitude' => $ticket->input['longitude'],
					];
					$geolocation->add($input);
				}
			}
		} elseif (empty($ticket->input['latitude']) && empty($ticket->input['longitude'])) {
			if ($geolocation->getFromDBByCrit(['itemtype' => $ticket::getType(), 'items_id' => $ticket->getID()]) && Session::haveRight(PluginGeolocationGeolocation::$rightname, PURGE)) {
				$geolocation->delete(['id' => $geolocation->getID()], 1);
			}
		}
	}
}

function plugin_geolocation_changeProfile()
{
	if (isset($_SESSION['glpiactiveprofile']['interface']) && $_SESSION['glpiactiveprofile']['interface'] == 'helpdesk') {
		$_SESSION['glpiactiveprofile'] = array_merge($_SESSION['glpiactiveprofile'], ProfileRight::getProfileRights($_SESSION['glpiactiveprofile']['id'], [PluginGeolocationGeolocation::$rightname]));
	}
}
