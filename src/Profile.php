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
     * Display the Geolocation rights matrix of a profile
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

        $profile->displayRightsChoiceMatrix(self::getGeneralRights(), $matrix_options);

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

    public static function uninstall(Migration $migration): void
    {
        /** @var \DBmysql $DB */
        global $DB;

        $DB->delete(ProfileRight::getTable(), ['name' => Geolocation::$rightname]);
    }
}
