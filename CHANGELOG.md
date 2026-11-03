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
- Uninstalling the plugin removes all its data.
- Saving the plugin settings asks for your password again, like the GLPI setup.
### Bugs
- Includes the security fixes from 2.0.1.
- Users who could only see a ticket, such as observers, could change or remove its geolocation.
- Geolocations could be added to any type of item, not only to tickets and the types enabled in the settings.
- Moving a geolocation to another item is no longer possible.
- Adding a second geolocation to the same item caused a database error.
- The map could show items of types that are not enabled in the settings or that the user cannot view.
- The map data was available to users without the Geolocation read right.
- Item names in the map pop-ups and the coordinate fields could run malicious code.
- The settings accepted item types that GLPI does not allow to geolocate.
- Invalid latitude or longitude values caused a database error.
- The "Reload" link of the map lost the selected item type.
- Users with the delete right could not remove a geolocation.

## [2.0.0] - 2026-01-19
### Features
- GLPI 11 compatibility

## [1.0.0] - 2023-12-12
### Features
- New tab for assets geolocation.
- A new map is added to choose coordinates.
