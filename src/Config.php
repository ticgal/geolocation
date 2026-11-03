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

use CommonDBTM;
use CommonGLPI;
use DBConnection;
use Glpi\Application\View\TemplateRenderer;
use Migration;
use Session;

class Config extends CommonDBTM
{
    public static string $rightname = 'config';

    public function __construct()
    {
        /** @var \DBmysql $DB */
        global $DB;
        if ($DB->tableExists($this->getTable())) {
            $this->getFromDB(1);
        }
    }

    public static function canView(string $interface = ''): bool
    {
        return Session::haveRight('config', READ);
    }

    public static function canCreate(string $interface = ''): bool
    {
        return Session::haveRight('config', UPDATE);
    }

    public static function canUpdate(string $interface = ''): bool
    {
        return Session::haveRight('config', UPDATE);
    }

    /**
     * Same as GLPI setup: saving the plugin settings requires a recent authentication.
     */
    protected static function itemTypeRequiresReauthentication(): bool
    {
        return true;
    }

    public static function getTypeName($nb = 0): string
    {
        return 'Geolocation';
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0): string|array
    {
        if ($item->getType() == 'Config') {
            return self::createTabEntry(self::getTypeName());
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0): bool
    {
        if ($item->getType() == 'Config') {
            self::showConfigForm();
        }
        return true;
    }

    /**
     * Itemtypes that can be geolocated, among the ones GLPI allows (financial information types).
     *
     * @return array<string, string> itemtype => type name
     */
    public static function getAvailableItemtypes(): array
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $values = [];
        foreach ($CFG_GLPI['infocom_types'] as $itemtype) {
            if ($item = getItemForItemtype($itemtype)) {
                $values[$itemtype] = $item->getTypeName();
            }
        }
        return $values;
    }

    /**
     * @return string[]
     */
    public static function getUsedItemtypes(): array
    {
        $config = new self();
        $assets = $config->fields['assets'] ?? [];
        if (!is_array($assets)) {
            $assets = importArrayFromDB($assets);
        }
        return array_values(array_filter($assets, 'is_string'));
    }

    public static function showConfigForm(): bool
    {
        $config = new self();

        TemplateRenderer::getInstance()->display('@geolocation/config.html.twig', [
            'item'     => $config,
            'used'     => self::getUsedItemtypes(),
            'values'   => self::getAvailableItemtypes(),
            'options'  => [
                'full_width' => true,
            ],
        ]);

        return true;
    }

    public function prepareInputForUpdate($input)
    {
        if ((!isset($input["assets"])) || (!is_array($input["assets"]))) {
            $input["assets"] = [];
        }
        $available = array_keys(self::getAvailableItemtypes());
        $input["assets"] = exportArrayToDB(array_values(array_intersect($input["assets"], $available)));

        return $input;
    }

    public static function getIcon(): string
    {
        return PLUGIN_GEOLOCATION_ICON;
    }

    public static function install(Migration $migration): void
    {
        /** @var \DBmysql $DB */
        global $DB;

        $default_charset = DBConnection::getDefaultCharset();
        $default_collation = DBConnection::getDefaultCollation();
        $default_key_sign = DBConnection::getDefaultPrimaryKeySignOption();

        $table = self::getTable();
        $config = new self();
        if (!$DB->tableExists($table)) {
            $migration->displayMessage("Installing $table");
            $query = "CREATE TABLE IF NOT EXISTS $table (
                `id` int {$default_key_sign} NOT NULL auto_increment,
                `assets` text,
                PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";
            $DB->doQuery($query);

            $config->add([
                'id' => 1,
                'assets' => exportArrayToDB([]),
            ]);
        }
    }

    public static function uninstall(Migration $migration): void
    {
        $migration->dropTable(self::getTable());
    }
}
