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

use CommonDBChild;
use CommonDBTM;
use CommonGLPI;
use DBConnection;
use Glpi\Application\View\TemplateRenderer;
use Glpi\DBAL\QueryExpression;
use Html;
use Migration;
use ProfileRight;
use Search;
use Session;
use Ticket;

class Geolocation extends CommonDBChild
{
    public static string $itemtype = 'itemtype';
    public static string $items_id = 'items_id';
    public bool $dohistory         = true;
    public static string $rightname = 'plugin_geolocation_geolocation';

    public static function getTypeName($nb = 0): string
    {
        return 'Geolocation';
    }

    /**
     * Itemtypes whose geolocations can be displayed: tickets and the itemtypes enabled in the plugin settings.
     *
     * @return string[]
     */
    public static function getAllowedItemtypes(): array
    {
        return array_values(array_unique(array_merge([Ticket::class], Config::getUsedItemtypes())));
    }

    public static function geolocationRedefineMenu($menus)
    {
        if (Session::haveRight(self::$rightname, READ)) {
            $icon = "<i class='" . htmlescape(self::getIcon()) . "' title='" . __s('Geolocation', 'geolocation') . "'></i>";
            $icon .= "<span class='d-none d-xxl-block'>" . __s('Geolocation', 'geolocation') . "</span>";
            if (isset($menus['helpdesk']['content']['ticket'])) {
                $menus['helpdesk']['content']['ticket']['links'][$icon] = self::getSearchURL(false) . "?itemtype=" . Ticket::getType();
            }
            foreach (Config::getUsedItemtypes() as $value) {
                $itemtype = strtolower($value);
                if (isset($menus['assets']['content'][$itemtype])) {
                    $menus['assets']['content'][$itemtype]['links'][$icon] = self::getSearchURL(false) . "?itemtype=" . urlencode($value);
                }
            }
        }
        return $menus;
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0): string|array
    {
        if (in_array($item->getType(), Config::getUsedItemtypes(), true)) {
            return self::createTabEntry(self::getTypeName());
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0): bool
    {
        if (in_array($item->getType(), Config::getUsedItemtypes(), true)) {
            self::showFormItem($item);
        }
        return true;
    }

    public function prepareInputForAdd($input)
    {
        if (!$this->checkCoordinates($input)) {
            return false;
        }
        if (!in_array($input['itemtype'] ?? null, self::getAllowedItemtypes(), true)) {
            Session::addMessageAfterRedirect(
                __s('Geolocation is not enabled for this type of item', 'geolocation'),
                false,
                ERROR,
            );
            return false;
        }
        // Only one geolocation per item (unicity key)
        if (
            isset($input['items_id'])
            && countElementsInTable(self::getTable(), ['itemtype' => $input['itemtype'], 'items_id' => $input['items_id']]) > 0
        ) {
            Session::addMessageAfterRedirect(
                __s('This item already has a geolocation', 'geolocation'),
                false,
                ERROR,
            );
            return false;
        }
        return parent::prepareInputForAdd($input);
    }

    public function prepareInputForUpdate($input)
    {
        if (!$this->checkCoordinates($input)) {
            return false;
        }
        // A geolocation belongs to its item: it cannot be moved to another one
        foreach ([static::$itemtype, static::$items_id] as $field) {
            if (array_key_exists($field, $input) && $input[$field] != ($this->fields[$field] ?? null)) {
                Session::addMessageAfterRedirect(
                    __s('A geolocation cannot be moved to another item', 'geolocation'),
                    false,
                    ERROR,
                );
                return false;
            }
        }
        return parent::prepareInputForUpdate($input);
    }

    /**
     * Latitude and longitude, when present, must be numbers within their range.
     */
    private function checkCoordinates(array $input): bool
    {
        $limits = ['latitude' => 90, 'longitude' => 180];
        foreach ($limits as $field => $limit) {
            if (!array_key_exists($field, $input)) {
                continue;
            }
            if (!is_numeric($input[$field]) || abs((float) $input[$field]) > $limit) {
                Session::addMessageAfterRedirect(
                    __s('Invalid latitude or longitude', 'geolocation'),
                    false,
                    ERROR,
                );
                return false;
            }
        }
        return true;
    }

    public static function showFormItem(?CommonGLPI $item): bool
    {
        if (!self::canView()) {
            return false;
        }

        if (!$item) {
            echo "<div class='spaced'>" . __s('Requested item not found') . "</div>";
            return false;
        }

        $dev_ID = $item->fields['id'] ?? 0;
        $options = [];
        $options['colspan']  = 1;
        $geolocation = new self();

        if (!$geolocation->getFromDBByCrit(['itemtype' => $item::getType(), 'items_id' => $dev_ID])) {
            $geolocation->getEmpty();
            $geolocation->fields["items_id"] = $dev_ID;
            $geolocation->fields["itemtype"] = $item::getType();
        }

        $geolocation->showFormHeader($options);

        echo Html::hidden('itemtype', ['value' => $item->getType()]);
        echo Html::hidden('items_id', ['value' => $dev_ID]);
        echo "<div class='form-field row col-12 mb-2'>";
        echo "<div class='form-field col-sm-6 col-12 mb-2'>";
        echo "<div class='form-field row col-12 mb-2'>";
        echo "<label class='col-form-label col-xxl-4 text-xxl-end' for='latitude'>" . __s('Latitude') . "</label>";
        echo "<div class='col-xxl-8 field-container'>";
        echo "<input type='number' id='latitude' step='any' min='-90' max='90' class='form-control' name='latitude' value='" . htmlescape((string) $geolocation->fields['latitude']) . "'>";
        echo "</div>";
        echo "</div>";
        echo "<div class='form-field row col-12 mb-2'>";
        echo "<label class='col-form-label col-xxl-4 text-xxl-end' for='longitude'>" . __s('Longitude') . "</label>";
        echo "<div class='col-xxl-8 field-container'>";
        echo "<input type='number' id='longitude' step='any' min='-180' max='180' class='form-control' name='longitude' value='" . htmlescape((string) $geolocation->fields['longitude']) . "'>";
        echo "</div>";
        echo "</div>";

        echo "</div>"; //col-6
        echo "<div class='form-field col-sm-6 col-12 mb-2'>";
        $geolocation->showMap();
        echo "</div>";

        echo "</div>"; //col-12

        $options['candel'] = false;
        $geolocation->showFormButtons($options);

        return true;
    }

    /**
     * Search page of an itemtype displayed as a map of its geolocated items
     *
     * @param class-string<CommonDBTM> $itemtype Must be one of {@see self::getAllowedItemtypes()}
     */
    public static function show(string $itemtype): void
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $params = Search::manageParams($itemtype, $_GET);
        echo "<div class='search_page row'>";
        TemplateRenderer::getInstance()->display('layout/parts/saved_searches.html.twig', [
            'itemtype' => $itemtype,
        ]);
        echo "<div class='col search-container'>";

        $params['target'] = self::getSearchURL(false) . "?itemtype=" . urlencode($itemtype);
        $params['as_map'] = 1;
        Search::showGenericSearch($itemtype, $params);

        $data = Search::getDatas($itemtype, $params);
        if ($data['data']['totalcount'] > 0) {
            TemplateRenderer::getInstance()->display('@geolocation/map.html.twig', [
                'itemtype' => $itemtype,
                'ajax_url' => $CFG_GLPI['root_doc'] . '/plugins/geolocation/ajax/map.php',
                'params'   => $params,
            ]);
        }

        echo "</div>";
        echo "</div>";
    }

    public static function showGeolocation(CommonDBTM $item): void
    {
        $geolocation = new self();
        if (!$geolocation->getFromDBByCrit(['itemtype' => $item::getType(), 'items_id' => $item->getID()])) {
            $geolocation->getEmpty();
        }

        echo "<div class='form-field row align-items-center col-12 glpi-full-width mb-2'>";
        echo "<label class='col-form-label col-xxl-4 text-xxl-end' for='latitude'>" . __s('Latitude') . "</label>";
        echo "<div class='col-xxl-8 field-container'>";
        echo "<input type='number' id='latitude' step='any' min='-90' max='90' class='form-control' name='latitude' value='" . htmlescape((string) $geolocation->fields['latitude']) . "'>";
        echo "</div>";
        echo "</div>";

        echo "<div class='form-field row align-items-center col-12 glpi-full-width mb-2'>";
        echo "<label class='col-form-label col-xxl-4 text-xxl-end' for='longitude'>" . __s('Longitude') . "</label>";
        echo "<div class='col-xxl-8 field-container'>";
        echo "<input type='number' id='longitude' step='any' min='-180' max='180' class='form-control' name='longitude' value='" . htmlescape((string) $geolocation->fields['longitude']) . "'>";
        echo "</div>";
        echo "</div>";

        $geolocation->showMap();
    }

    /**
    * get openstreetmap
    */
    public function showMap(): void
    {
        $rand = mt_rand();

        echo "<div id='setlocation_container_{$rand}'></div>";
        $js = "
        $(function(){
        var map_elt, _marker;
        var _setLocation = function(lat, lng) {
            if (_marker) {
               map_elt.removeLayer(_marker);
            }
            _marker = L.marker([lat, lng]).addTo(map_elt);
            map_elt.fitBounds(
               L.latLngBounds([_marker.getLatLng()]), {
                  padding: [50, 50],
                  maxZoom: 20
               }
            );
        };

        var _autoSearch = function() {
            var _tosearch = '';
            var _address = $('*[name=address]').val();
            var _town = $('*[name=town]').val();
            var _country = $('*[name=country]').val();
            if (_address != '') {
               _tosearch += _address;
            }
            if (_town != '') {
               if (_address != '') {
                  _tosearch += ' ';
               }
               _tosearch += _town;
            }
            if (_country != '') {
               if (_address != '' || _town != '') {
                  _tosearch += ' ';
               }
               _tosearch += _country;
            }

            $('.leaflet-control-geocoder-form > input[type=text]').val(_tosearch);
        }
        var finalizeMap = function() {
            var geocoder = L.Control.geocoder({
                defaultMarkGeocode: false,
                errorMessage: '" . __s('No result found') . "',
                placeholder: '" . __s('Search') . "'
            });
            geocoder.on('markgeocode', function(e) {
                this._map.fitBounds(e.geocode.bbox);
            });
            map_elt.addControl(geocoder);
            _autoSearch();

            function onMapClick(e) {
               var popup = L.popup();
               popup
                  .setLatLng(e.latlng)
                  .setContent('SELECTPOPUP')
                  .openOn(map_elt);
            }

            map_elt.on('click', onMapClick);

            map_elt.on('popupopen', function(e){
               var _popup = e.popup;
               var _container = $(_popup._container);

               var _clat = _popup._latlng.lat.toString();
               var _clng = _popup._latlng.lng.toString();

               _popup.setContent('<p><a href=\'#\'>" . __s('Set location here') . "</a></p>');

               $(_container).find('a').on('click', function(e){
                  e.preventDefault();
                  _popup.remove();
                  $('*[name=latitude]').val(_clat);
                  $('*[name=longitude]').val(_clng).trigger('change');
               });
            });

            var _curlat = $('*[name=latitude]').val();
            var _curlng = $('*[name=longitude]').val();

            if (_curlat && _curlng) {
               _setLocation(_curlat, _curlng);
            }

            $('*[name=latitude],*[name=longitude]').on('change', function(){
               var _curlat = $('*[name=latitude]').val();
               var _curlng = $('*[name=longitude]').val();

               if (_curlat && _curlng) {
                  _setLocation(_curlat, _curlng);
               }
            });
        }

        // Geolocation may be disabled in the browser (e.g. geo.enabled = false in firefox)
        if (!navigator.geolocation) {
            map_elt = initMap($('#setlocation_container_{$rand}'), 'setlocation_{$rand}', '200px');
            finalizeMap();
            return;
        }

        navigator.geolocation.getCurrentPosition(function(pos) {
            // Try to determine an appropriate zoom level based on accuracy
            var acc = pos.coords.accuracy;
            if (acc > 3000) {
                // Very low accuracy. Most likely a device without GPS or a cellular connection
                var zoom = 10;
            } else if (acc > 1000) {
                // Low accuracy
                var zoom = 15;
            } else if (acc > 500) {
                // Medium accuracy
                var zoom = 17;
            } else {
                // High accuracy
                var zoom = 20;
            }
            map_elt = initMap($('#setlocation_container_{$rand}'), 'setlocation_{$rand}', '200px', {
                position: [pos.coords.latitude, pos.coords.longitude],
                zoom: zoom
            });
            finalizeMap();
        }, function() {
            map_elt = initMap($('#setlocation_container_{$rand}'), 'setlocation_{$rand}', '200px');
            finalizeMap();
        }, {enableHighAccuracy: true});
        });";
        echo Html::scriptBlock($js);
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
        if (!$DB->tableExists($table)) {
            $migration->displayMessage("Installing $table");
            $query = "CREATE TABLE IF NOT EXISTS $table (
                `id` int {$default_key_sign} NOT NULL auto_increment,
                `itemtype` varchar(100) COLLATE {$default_collation} NOT NULL,
                `items_id` int {$default_key_sign} NOT NULL DEFAULT '0',
                `latitude` decimal(9,6) NOT NULL DEFAULT '0.0000',
                `longitude` decimal(9,6) NOT NULL DEFAULT '0.0000',
                PRIMARY KEY (`id`),
                UNIQUE KEY `unicity` (`itemtype`,`items_id`),
                KEY `latitude` (`latitude`),
                KEY `longitude` (`longitude`)
                ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";
            $DB->doQuery($query);
        }

        // Before 3.0.0 the class was PluginGeolocationGeolocation: update the itemtype stored in logs and other GLPI tables
        $migration->renameItemtype('PluginGeolocationGeolocation', self::class, false);

        // Before 3.0.0 the profile offered DELETE, but removing a geolocation requires PURGE
        $DB->update(
            ProfileRight::getTable(),
            ['rights' => new QueryExpression('(' . $DB->quoteName('rights') . ' | ' . PURGE . ') & ~' . DELETE)],
            [
                'name' => self::$rightname,
                new QueryExpression($DB->quoteName('rights') . ' & ' . DELETE),
            ],
        );
    }

    public static function uninstall(Migration $migration): void
    {
        $migration->dropTable(self::getTable());
    }
}
