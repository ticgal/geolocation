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

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

class PluginGeolocationProfile extends Profile
{
    public static $rightname = "profile";

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
       switch ($item->getType()) {
            case 'Profile':
                return self::createTabEntry('Geolocation');
        }
        return ''; 
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        switch ($item->getType()) {
            case Profile::class:
                /** @var Profile $item */
                $profile = new self();
                return $profile->showProfileForm($item);
        }
        return false;
    }

    /**
     * Display the profile form for AccessTransparency rights
     * @param  Profile $profile
     * @return bool
     */
    public function showProfileForm(Profile $profile)
    {
        if (!Session::haveRight("profile", READ)) {
            return false;
        }

        $canedit = Session::haveRight("profile", UPDATE);

        echo "<div class='spaced'>";
        if ($canedit) {
            echo "<form method='post' action='" . htmlspecialchars($profile::getFormURL()) . "'>";
        }

        $rights = self::getGeneralRights();

        $matrix_options = [
            'canedit' => $canedit,
            'title'   => __('Geolocation', 'geolocation'),
        ];

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

    public static function getGeneralRights()
    {
        $rights = [];
        $rights[] =  [
            'rights' => [READ   => __('Read'), UPDATE => __('Update'), CREATE => __('Create'), DELETE => __('Delete')],
            'itemtype' => 'PluginGeolocationGeolocation',
            'label'    => __('Geolocation', 'geolocation'),
            'field'    => 'plugin_geolocation_geolocation',
        ];
        return $rights;
    }

    public static function uninstall()
    {
        /** @var \DBmysql $DB */
        global $DB;

        $table = ProfileRight::getTable();
        $query = "DELETE FROM $table WHERE `name` LIKE '%plugin_geolocation%'";
        $DB->doQuery($query);
    }
}
