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

use GlpiPlugin\Geolocation\Config;
use GlpiPlugin\Geolocation\Geolocation;
use GlpiPlugin\Geolocation\Profile;

/**
 * Plugin classes with install()/uninstall() methods, in installation order
 *
 * @return class-string[]
 */
function plugin_geolocation_classes(): array
{
    return [Config::class, Geolocation::class, Profile::class];
}

function plugin_geolocation_install(): bool
{
    $migration = new Migration(PLUGIN_GEOLOCATION_VERSION);

    foreach (plugin_geolocation_classes() as $classname) {
        if (method_exists($classname, 'install')) {
            $classname::install($migration);
        }
    }
    $migration->executeMigration();

    return true;
}

function plugin_geolocation_uninstall(): bool
{
    $migration = new Migration(PLUGIN_GEOLOCATION_VERSION);

    foreach (plugin_geolocation_classes() as $classname) {
        if (method_exists($classname, 'uninstall')) {
            $classname::uninstall($migration);
        }
    }
    $migration->executeMigration();

    return true;
}

function plugin_geolocation_postitemform($params = [])
{
    if (isset($params['item']) && $params['item'] instanceof CommonDBTM) {
        if ($params['item'] instanceof Ticket && Session::haveRight(Geolocation::$rightname, READ)) {
            Geolocation::showGeolocation($params['item']);
        }
    }
}

function plugin_geolocation_ticket_add(Ticket $ticket)
{
    if (isset($ticket->input['latitude']) && !empty($ticket->input['latitude']) && isset($ticket->input['longitude']) && !empty($ticket->input['longitude'])) {
        $input = [
            'itemtype' => $ticket::getType(),
            'items_id' => $ticket->getID(),
            'latitude' => $ticket->input['latitude'],
            'longitude' => $ticket->input['longitude'],
        ];
        $geolocation = new Geolocation();
        if ($geolocation->can(-1, CREATE, $input)) {
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
            $geolocation = new Geolocation();
            $geolocation->add($input);
        }
    }
}

/**
 * Rights are checked per item, like in front/geolocation.form.php: the core ticket form only
 * requires READ on the ticket, and this hook runs before the ticket input is validated.
 */
function plugin_geolocation_ticket_update(Ticket $ticket)
{
    if (isset($ticket->input['latitude']) &&  isset($ticket->input['longitude'])) {
        $geolocation = new Geolocation();
        if (!empty($ticket->input['latitude']) && !empty($ticket->input['longitude'])) {
            if ($geolocation->getFromDBByCrit(['itemtype' => $ticket::getType(), 'items_id' => $ticket->getID()])) {
                if ($geolocation->can($geolocation->getID(), UPDATE)) {
                    $input = [
                        'id' => $geolocation->getID(),
                        'latitude' => $ticket->input['latitude'],
                        'longitude' => $ticket->input['longitude'],
                    ];
                    $geolocation->update($input);
                }
            } else {
                $input = [
                    'itemtype' => $ticket::getType(),
                    'items_id' => $ticket->getID(),
                    'latitude' => $ticket->input['latitude'],
                    'longitude' => $ticket->input['longitude'],
                ];
                if ($geolocation->can(-1, CREATE, $input)) {
                    $geolocation->add($input);
                }
            }
        } elseif (empty($ticket->input['latitude']) && empty($ticket->input['longitude'])) {
            if (
                $geolocation->getFromDBByCrit(['itemtype' => $ticket::getType(), 'items_id' => $ticket->getID()])
                && $geolocation->can($geolocation->getID(), PURGE)
            ) {
                $geolocation->delete(['id' => $geolocation->getID()], true);
            }
        }
    }
}

function plugin_geolocation_changeProfile()
{
    if (isset($_SESSION['glpiactiveprofile']['interface']) && $_SESSION['glpiactiveprofile']['interface'] == 'helpdesk') {
        $_SESSION['glpiactiveprofile'] = array_merge($_SESSION['glpiactiveprofile'], ProfileRight::getProfileRights($_SESSION['glpiactiveprofile']['id'], [Geolocation::$rightname]));
    }
}
