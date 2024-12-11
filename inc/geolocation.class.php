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

use Glpi\Application\View\TemplateRenderer;
use Glpi\Toolbox\Sanitizer;

class PluginGeolocationGeolocation extends CommonDBChild
{

	public static $itemtype        = 'itemtype';
	public static $items_id        = 'items_id';
	public $dohistory              = true;
	static $rightname = "plugin_geolocation_geolocation";

	static function getTypeName($nb = 0)
	{
		return 'Geolocation';
	}

	public static function geolocationRedefineMenu($menus)
	{
		if (Session::haveRight(PluginGeolocationGeolocation::$rightname, READ)) {
			if (isset($menus['helpdesk']['content']['ticket'])) {
				$icon = "<i class='ti ti-map-2' title='" . __('Geolocation', 'geolocation') . "'></i>";
				$icon .= "<span class='d-none d-xxl-block'>" . __('Geolocation', 'geolocation') . "</span>";
				$menus['helpdesk']['content']['ticket']['links'][$icon] = self::getSearchURL(false) . "?itemtype=" . Ticket::getType();
			}
			$listitemtypes = PluginGeolocationConfig::getUsedItemtypes();
			foreach ($listitemtypes as $key => $value) {
				$itemtype = strtolower($value);
				if (isset($menus['assets']['content'][$itemtype])) {
					$icon = "<i class='ti ti-map-2' title='" . __('Geolocation', 'geolocation') . "'></i>";
					$icon .= "<span class='d-none d-xxl-block'>" . __('Geolocation', 'geolocation') . "</span>";
					$menus['assets']['content'][$itemtype]['links'][$icon] = self::getSearchURL(false) . "?itemtype=" . $value;
				}
			}
		}
		return $menus;
	}

	function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
	{
		if (in_array($item->getType(), PluginGeolocationConfig::getUsedItemtypes())) {
			return self::getTypeName();
		}
		return '';
	}

	static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
	{
		if (in_array($item->getType(), PluginGeolocationConfig::getUsedItemtypes())) {
			self::showFormItem($item);
		}
		return true;
	}

	public static function showFormItem(CommonGLPI $item)
	{

		if (!self::canView()) {
			return false;
		}

		if (!$item) {
			echo "<div class='spaced'>" . __('Requested item not found') . "</div>";
		} else {
			$dev_ID   = $item->getField('id');
			$options             = [];
			$options['colspan']  = 1;
			$geolocation = new self();

			if (!$geolocation->getFromDBByCrit(['itemtype' => $item::getType(), 'items_id' => $item->getID()])) {
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
			echo "<label class='col-form-label col-xxl-4 text-xxl-end' for='latitude'>" . __('Latitude') . "</label>";
			echo "<div class='col-xxl-8 field-container'>";
			echo "<input type='number' id='latitude' step='any' class='form-control' name='latitude' value='" . $geolocation->fields['latitude'] . "'>";
			echo "</div>";
			echo "</div>";

			echo "<div class='form-field row col-12 mb-2'>";
			echo "<label class='col-form-label col-xxl-4 text-xxl-end' for='longitude'>" . __('Longitude') . "</label>";
			echo "<div class='col-xxl-8 field-container'>";
			echo "<input type='number' id='longitude' step='any' class='form-control' name='longitude' value='" . $geolocation->fields['longitude'] . "'>";
			echo "</div>";
			echo "</div>";

			echo "</div>"; //col-6

			echo "<div class='form-field col-sm-6 col-12 mb-2'>";
			$geolocation->showMap();
			echo "</div>";

			echo "</div>"; //col-12

			$options['candel'] = false;
			$geolocation->showFormButtons($options);
		}
	}

	public static function show($itemtype)
	{
		global $CFG_GLPI;

		$params = Search::manageParams($itemtype, $_GET);
		echo "<div class='search_page row'>";
		TemplateRenderer::getInstance()->display('layout/parts/saved_searches.html.twig', [
			'itemtype' => $itemtype,
		]);
		echo "<div class='col search-container'>";

		$params['target'] = self::getSearchURL(false) . "?itemtype=" . $itemtype;
		$params['as_map'] = 1;

		Search::showGenericSearch($itemtype, $params);

		$data = Search::getDatas($itemtype, $params);
		if ($data['data']['totalcount'] > 0) {
			$target = $data['search']['target'];
			$criteria = $data['search']['criteria'];
			array_pop($criteria);
			array_pop($criteria);
			$globallinkto = Toolbox::append_params(
				[
					'criteria'     => Sanitizer::unsanitize($criteria),
					'metacriteria' => Sanitizer::unsanitize($data['search']['metacriteria'])
				],
				'&amp;'
			);
			$sort_params = Toolbox::append_params([
				'sort'   => $data['search']['sort'],
				'order'  => $data['search']['order']
			], '&amp;');
			$parameters = "as_map=0&amp;" . $sort_params . '&amp;' . $globallinkto;
			if (strpos($target, '?') == false) {
				$fulltarget = $target . "?" . $parameters;
			} else {
				$fulltarget = $target . "&" . $parameters;
			}
			$typename = class_exists($itemtype) ? $itemtype::getTypeName($data['data']['totalcount']) : $itemtype;

			echo "<div class='card border-top-0 rounded-0 search-as-map'>";
			echo "<div class='card-body px-0' id='map_container'>";
			echo "<small class='text-muted p-1'>" . __('Search results for localized items only') . "</small>";
			$js = "$(function() {
				var map = initMap($('#map_container'), 'map', 'full');
				_loadMap(map, '$itemtype');
			});
			var _loadMap = function(map_elt, itemtype) {
				L.AwesomeMarkers.Icon.prototype.options.prefix = 'far';
				var _micon = 'circle';
				var stdMarker = L.AwesomeMarkers.icon({
					icon: _micon,
					markerColor: 'blue'
				});
				var aMarker = L.AwesomeMarkers.icon({
					icon: _micon,
					markerColor: 'cadetblue'
				});
				var bMarker = L.AwesomeMarkers.icon({
					icon: _micon,
					markerColor: 'purple'
				});
				var cMarker = L.AwesomeMarkers.icon({
					icon: _micon,
					markerColor: 'darkpurple'
				});
				var dMarker = L.AwesomeMarkers.icon({
					icon: _micon,
					markerColor: 'red'
				});
				var eMarker = L.AwesomeMarkers.icon({
					icon: _micon,
					markerColor: 'darkred'
				});
				//retrieve geojson data
				map_elt.spin(true);
				$.ajax({
					dataType: 'json',
					method: 'POST',
					url: '" . Plugin::getWebDir('geolocation') . "/ajax/map.php',
					data: {
						itemtype: itemtype,
						params: " . json_encode($params) . "
					}
				}).done(function(data) {
					var _points = data.points;
					var _markers = L.markerClusterGroup({
						iconCreateFunction: function(cluster) {
							var childCount = cluster.getChildCount();
							var markers = cluster.getAllChildMarkers();
							var n = 0;
							for (var i = 0; i < markers.length; i++) {
								n += markers[i].count;
							}
							var c = ' marker-cluster-';
							if (n < 10) {
								c += 'small';
							} else if (n < 100) {
								c += 'medium';
							} else {
								c += 'large';
							}
							return new L.DivIcon({ html: '<div><span>' + n + '</span></div>', className: 'marker-cluster' + c, iconSize: new L.Point(40, 40) });
						}
					});
					$.each(_points, function(index, point) {
						var _title = '<strong>' + point.title + '</strong><br/><a href=\'' + point.url + '\'>" . __('View item', 'geolocation') . "</a>';

						var _icon = stdMarker;
						var _marker = L.marker([point.lat, point.lng], { icon: _icon, title: point.title });
						_marker.count = point.count;
						_marker.bindPopup(_title);
						_markers.addLayer(_marker);
					});
					map_elt.addLayer(_markers);
					map_elt.fitBounds(
						_markers.getBounds(), {
							padding: [50, 50],
							maxZoom: 12
						}
					);
				}).fail(function (response) {
					var _data = response.responseJSON;
					var _message = '" . __s('An error occurred loading data :(') . "';
					if (_data.message) {
						_message = _data.message;
					}
					var fail_info = L.control();
					fail_info.onAdd = function (map) {
						this._div = L.DomUtil.create('div', 'fail_info');
						this._div.innerHTML = _message + '<br/><span id=\'reload_data\'><i class=\'fa fa-sync\'></i> " . __s('Reload') . "</span>';
						return this._div;
					};
					fail_info.addTo(map_elt);
					$('#reload_data').on('click', function() {
						$('.fail_info').remove();
						_loadMap(map_elt);
					});
				}).always(function() {
					//hide spinner
					map_elt.spin(false);
				});
			}
			";
			echo Html::scriptBlock($js);
			echo "</div>"; // .card-body
			echo "</div>"; // .card
		}

		echo "</div>";
		echo "</div>";
	}

	public static function showGeolocation(CommonDBTM $item)
	{

		$geolocation = new self();
		if (!$geolocation->getFromDBByCrit(['itemtype' => $item::getType(), 'items_id' => $item->getID()])) {
			$geolocation->getEmpty();
		}

		echo "<div class='form-field row col-12 mb-2'>";
		echo "<label class='col-form-label col-xxl-4 text-xxl-end' for='latitude'>" . __('Latitude') . "</label>";
		echo "<div class='col-xxl-8 field-container'>";
		echo "<input type='number' id='latitude' step='any' class='form-control' name='latitude' value='" . $geolocation->fields['latitude'] . "'>";
		echo "</div>";
		echo "</div>";

		echo "<div class='form-field row col-12 mb-2'>";
		echo "<label class='col-form-label col-xxl-4 text-xxl-end' for='longitude'>" . __('Longitude') . "</label>";
		echo "<div class='col-xxl-8 field-container'>";
		echo "<input type='number' id='longitude' step='any' class='form-control' name='longitude' value='" . $geolocation->fields['longitude'] . "'>";
		echo "</div>";
		echo "</div>";

		$geolocation->showMap();
	}

	/**
	 * get openstreetmap
	 */
	public function showMap()
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

	public static function getIcon()
	{
		return "ti ti-map-2";
	}

	static function install(Migration $migration)
	{
		global $DB;

		$default_charset = DBConnection::getDefaultCharset();
		$default_collation = DBConnection::getDefaultCollation();
		$default_key_sign = DBConnection::getDefaultPrimaryKeySignOption();

		$table = self::getTable();
		if (!$DB->tableExists($table)) {
			$migration->displayMessage("Installing $table");
			$query = "CREATE TABLE IF NOT EXISTS $table (
				`id` int {$default_key_sign} NOT NULL auto_increment,
				`itemtype` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
				`items_id` int {$default_key_sign} NOT NULL DEFAULT '0',
				`latitude` decimal(9,6) NOT NULL DEFAULT '0.0000',
				`longitude` decimal(9,6) NOT NULL DEFAULT '0.0000',
				PRIMARY KEY (`id`),
				UNIQUE KEY `unicity` (`itemtype`,`items_id`),
				KEY `latitude` (`latitude`),
				KEY `longitude` (`longitude`)
				) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";
			$DB->query($query) or die($DB->error());
		}
	}
}
