# Changelog for Geolocation

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.1] - 2026-10-08
### Security
- Escape item names and links in the pop-ups of the search map (stored XSS)
- The map data endpoint requires the Geolocation read right, only accepts tickets and the itemtypes enabled in the settings, and checks the read right on that itemtype
- The map page only accepts tickets and the itemtypes enabled in the settings, and checks the read right on that itemtype
- Saving a ticket only changes its geolocation when the user can modify that ticket
- Geolocations can only be added to tickets and the itemtypes enabled in the settings, and cannot be moved to another item
### Fixed
- Latitude and longitude are validated, invalid values no longer cause a database error
- Adding a second geolocation to the same item no longer causes a database error
- The "Reload" link of the search map lost the itemtype

## [2.0.0] - 2026-01-19
### Added
- GLPI 11 compatibility

## [1.0.0] - 2023-12-12
### Added
- New tab for assets geolocation.
- A new map is added to choose coordinates.
