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

namespace GlpiPlugin\Geolocation;

use CommonGLPI;
use Html;
use Migration;
use ProfileRight;
use Session;

class Profile extends CommonGLPI
{
    public static string $rightname = 'profile';

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0): string|array
    {
        switch ($item->getType()) {
            case 'Profile':
                return self::createTabEntry(__('Geolocation', 'geolocation'));
        }
        return '';
    }

    public static function getIcon(): string
    {
        return PLUGIN_GEOLOCATION_ICON;
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0): bool
    {
        switch ($item->getType()) {
            case \Profile::class:
                /** @var \Profile $item */
                $profile = new self();
                return $profile->showProfileForm($item);
        }
        return false;
    }

    /**
     * Display the Geolocation rights matrix of a profile: a self-service profile only gets the
     * dedicated helpdesk right, a central profile only the central one.
     *
     * @param \Profile $profile
     *
     * @return bool
     */
    public function showProfileForm(\Profile $profile): bool
    {
        if (!Session::haveRight(self::$rightname, READ)) {
            return false;
        }

        $canedit = Session::haveRight(self::$rightname, UPDATE);

        echo "<div class='spaced'>";
        if ($canedit) {
            echo "<form method='post' action='" . htmlescape($profile::getFormURL()) . "'>";
        }

        $matrix_options = [
            'canedit' => $canedit,
            'title'   => __('Geolocation', 'geolocation'),
        ];

        if (($profile->fields['interface'] ?? '') === 'helpdesk') {
            $rights = self::getHelpdeskRights();
        } else {
            $rights = self::getGeneralRights();
        }

        $profile->displayRightsChoiceMatrix($rights, $matrix_options);

        if ($canedit) {
            echo "<div class='text-center'>";
            echo Html::hidden('id', ['value' => $profile->getID()]);
            echo Html::submit(_sx('button', 'Save'), ['name' => 'update']);
            echo "</div>\n";
            Html::closeForm();
        }
        echo '</div>';
        return true;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function getGeneralRights(): array
    {
        return [
            [
                'rights'   => [READ => __('Read'), UPDATE => __('Update'), CREATE => __('Create'), PURGE => _x('button', 'Delete permanently')],
                'itemtype' => Geolocation::class,
                'label'    => __('Geolocation', 'geolocation'),
                'field'    => Geolocation::$rightname,
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function getHelpdeskRights(): array
    {
        return [
            [
                'rights'   => [READ => __('Read'), CREATE => __('Create'), UPDATE => __('Update'), PURGE => _x('button', 'Delete permanently')],
                'itemtype' => Geolocation::class,
                'label'    => __('Geolocation of my tickets', 'geolocation'),
                'field'    => Geolocation::HELPDESK_RIGHTNAME,
            ],
        ];
    }

    public static function install(Migration $migration): void
    {
        /** @var \DBmysql $DB */
        global $DB;

        $helpdesk_right = Geolocation::HELPDESK_RIGHTNAME;
        if (countElementsInTable(ProfileRight::getTable(), ['name' => $helpdesk_right]) > 0) {
            return;
        }
        $migration->displayMessage("Adding right $helpdesk_right");
        ProfileRight::addProfileRights([$helpdesk_right]);

        // Up to 2.0.0 self-service profiles used the central right: move it to the dedicated one.
        // Geolocation::install() runs first and has already turned DELETE into PURGE, both are handled.
        $iterator = $DB->request([
            'SELECT'     => [ProfileRight::getTable() . '.profiles_id', ProfileRight::getTable() . '.rights'],
            'FROM'       => ProfileRight::getTable(),
            'INNER JOIN' => [
                \Profile::getTable() => [
                    'ON' => [
                        \Profile::getTable()     => 'id',
                        ProfileRight::getTable() => 'profiles_id',
                    ],
                ],
            ],
            'WHERE'      => [
                ProfileRight::getTable() . '.name'   => Geolocation::$rightname,
                ProfileRight::getTable() . '.rights' => ['>', 0],
                \Profile::getTable() . '.interface'  => 'helpdesk',
            ],
        ]);
        foreach ($iterator as $row) {
            $rights = $row['rights'] & (READ | CREATE | UPDATE | PURGE);
            if ($row['rights'] & DELETE) {
                $rights |= PURGE;
            }
            ProfileRight::updateProfileRights($row['profiles_id'], [
                $helpdesk_right         => $rights,
                Geolocation::$rightname => 0,
            ]);
        }
    }

    public static function uninstall(Migration $migration): void
    {
        /** @var \DBmysql $DB */
        global $DB;

        $DB->delete(ProfileRight::getTable(), ['name' => [Geolocation::$rightname, Geolocation::HELPDESK_RIGHTNAME]]);
    }
}
