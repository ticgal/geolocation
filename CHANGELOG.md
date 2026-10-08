# Changelog for Geolocation

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [3.0.0-beta.1] - 2026-10-08
### Added
- GLPI 12 compatibility
### Security
- Saving a ticket only changes its geolocation when the user can modify that ticket. Users who could only see it, such as observers, could change or remove it
- Geolocations can only be added to tickets and the itemtypes enabled in the settings, and cannot be moved to another item
- The map page and its data endpoint only accept tickets and the itemtypes enabled in the settings, and check the read right on that itemtype
- The map data endpoint requires the Geolocation read right
- Escape item names and links in the map pop-ups and the coordinate fields
- Saving the plugin settings requires re-authentication, like GLPI setup
- The settings only accept itemtypes that GLPI allows to geolocate
### Changed
- Classes moved to src/ with the GlpiPlugin\Geolocation namespace. The PluginGeolocation* class names remain as deprecated aliases for other plugins. The history saved with the old class name is updated on upgrade
- The profile offers the "Delete permanently" right instead of "Delete", which is what removing a geolocation requires. Profiles that had "Delete" get it on upgrade
- Uninstalling the plugin drops its tables
- Settings form and search map rendered with Twig templates
- Tabler icons instead of Font Awesome
- PHPStan configuration works both locally and in CI
- Unique Composer autoloader suffix and removed the unused php-qrcode dependency
### Fixed
- Latitude and longitude are validated, invalid values no longer cause a database error
- Adding a second geolocation to the same item, or moving one onto an item that already has one, no longer causes a database error
- The "Reload" link of the search map lost the itemtype
- Users with the delete right could not remove a geolocation

## [2.0.0] - 2026-01-19
### Added
- GLPI 11 compatibility

## [1.0.0] - 2023-12-12
### Added
- New tab for assets geolocation.
- A new map is added to choose coordinates.
