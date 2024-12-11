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

use Glpi\Plugin\Hooks;

define('PLUGIN_GEOLOCATION_VERSION', '1.0.0');
define('PLUGIN_GEOLOCATION_MIN_GLPI', '10.0.0');
define('PLUGIN_GEOLOCATION_MAX_GLPI', '10.1.99');

function plugin_version_geolocation()
{
	return [
		'name' => 'Geolocation',
		'version' => PLUGIN_GEOLOCATION_VERSION,
		'author' => '<a href="https://tic.gal">TICgal</a>',
		'homepage' => 'https://tic.gal',
		'license' => 'GPLv3+',
		'requirements' => [
			'glpi' => [
				'min' => PLUGIN_GEOLOCATION_MIN_GLPI,
				'max' => PLUGIN_GEOLOCATION_MAX_GLPI,
			]
		]
	];
}

function plugin_init_geolocation()
{
	global $PLUGIN_HOOKS;

	$PLUGIN_HOOKS['csrf_compliant']['geolocation'] = true;

	$plugin = new Plugin();
	if ($plugin->isActivated('geolocation')) {

		Plugin::registerClass('PluginGeolocationConfig', ['addtabon' => 'Config']);
		$PLUGIN_HOOKS['config_page']['geolocation'] = 'front/config.form.php';

		Plugin::registerClass('PluginGeolocationProfile', ['addtabon' => 'Profile']);

		Plugin::registerClass('PluginGeolocationGeolocation', ['addtabon' => PluginGeolocationConfig::getUsedItemtypes()]);

		$PLUGIN_HOOKS[Hooks::POST_ITEM_FORM]['geolocation'] = 'plugin_geolocation_postitemform';

		$PLUGIN_HOOKS[Hooks::ITEM_ADD]['geolocation'] = [
			'Ticket' => 'plugin_geolocation_ticket_add'
		];
		$PLUGIN_HOOKS[Hooks::PRE_ITEM_UPDATE]['geolocation'] = [
			'Ticket' => 'plugin_geolocation_ticket_update'
		];

		$PLUGIN_HOOKS[Hooks::REDEFINE_MENUS]['geolocation'] = [PluginGeolocationGeolocation::class, 'geolocationRedefineMenu'];

		$PLUGIN_HOOKS[Hooks::CHANGE_PROFILE]['geolocation'] = 'plugin_geolocation_changeProfile';
	}
}
