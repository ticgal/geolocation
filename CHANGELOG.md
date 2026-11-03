# Changelog for Geolocation
<!-- changelog-format: 2 -->

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [3.0.0] - 2026-11-03
### Features
- Compatible with GLPI 12.
- Removing a geolocation now uses the "Delete permanently" profile right, which is granted on upgrade to profiles that had "Delete".
- Saving the plugin settings asks for your password again, like the GLPI setup.
### Bugs
- Includes all the fixes from 2.0.1.
- The settings accepted item types that GLPI does not allow to geolocate.
- Users with the delete right could not remove a geolocation.

## [2.0.1] - 2026-10-08
### Features
- Self-service profiles get their own "Geolocation of my tickets" right, which only covers their own tickets.
- Self-service profiles that had the Geolocation right get the new right on upgrade.
- Uninstalling the plugin removes all its data, including the history of geolocations.
### Bugs
- Item names in the map pop-ups could run malicious code.
- The map data was available to users without the Geolocation read right.
- The map could show items of types that are not enabled in the settings or that the user cannot view.
- Users who could only see a ticket, such as observers, could change or remove its geolocation.
- Geolocations could be added to any type of item, not only to tickets and the types enabled in the settings.
- Moving a geolocation to another item is no longer possible.
- Texts and values written into the map scripts were not always escaped.
- Invalid latitude or longitude values caused a database error.
- Adding a second geolocation to the same item caused a database error.
- The "Reload" link of the map lost the selected item type.
- The release package included an unused third-party library.

## [2.0.0] - 2026-01-19
### Features
- GLPI 11 compatibility

## [1.0.0] - 2023-12-12
### Features
- New tab for assets geolocation.
- A new map is added to choose coordinates.
