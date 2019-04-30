# Redirect Changelog

## 1.0.23 - 2019-04-30
### Changed
- Change the url size (source and destination url) from maximal 255 characters to maximal 1000 characters

### Fixed
- :void is not a return type in PHP < 7.1

## 1.0.22 - 2019-04-26
### Changed
- Cleanup and improvements in the sourcecode

### Fixed
- Class 'verbb\feedme\services\Elements' not found when updating the plugin
- Use new craft\feedme namespace

## 1.0.21 - 2019-04-17
### Added
- Give other plugins a chance to trigger event: beforeCatchall
- Improve and add missing German translations

### Fixed
- Source url to work with numbers only eg rederect /12 to /number=12
- Source url to work with # numbers, note: the # part is ignored on server side
- Small improvements in namespacig and remove undefined variable

## 1.0.20 - 2019-02-05
### Added
- Add Feed Me support

### Fixed
- Verify static template before redirecting
- Fix typo in translations

## 1.0.19 - 2018-10-05
### Added
- Added german translation file

## 1.0.18 - 2018-08-17
### Fixed
- Fixed navigation to work properly in Craft 3.0.20.


## 1.0.17 - 2018-07-04
### Fixed
- Fixed icon not shown in newer Craft CMS 3 release
- Fixed an index not found error if you enable Catch-all in the settings on some systems

## 1.0.16 - 2018-04-18
### Fixed
- Fixed migration scripts to create all tables on first install
- Small text changes

## 1.0.15 - 2018-02-21
### Fixed
- Fixed a bug causing the settings routes and section not available in with Craft CMS 3.0.0-RC11

## 1.0.14 - 2018-01-28
### Added
- Ignore not existing static files like fonts, images or video files from the catch all functionality

### Fixed
- Fixed the error "Cannot use craft\base\Object because 'Object' is a special class name" in some environments
- Fixed a not working back link in the plugin

## 1.0.13 - 2018-01-10
### Added
- Added settings screen to enable / disable all the redirects with one click
- Added a catch all setting to catch all the other url's (404) and define a twig template to enable you to create a good stylish 404 page with the correct http code
- Register the catched (not existing) url's in the database and show the last 100 in an interface. The plugin let you create new redirect rules directly from this overview by simply clicking on it.

### Changed
- The required minimal Craft version and checked the compatibility

## 1.0.12 - 2018-01-03

### Added
- Inactive redirects filter (show the redirects not visited for 60 days)

### Changed
- The required minimal Craft version and checked the compatibility
- New screenshot
- Added a link to the URL rules in the edit screen

## 1.0.11 - 2017-12-12

### Changed
- Changed hardcoded tablenames to accept table prefix settings
- New icon

## 1.0.10 - 2017-12-11

### Fixed
- The Add new button dissapeared in Craft RC1 due to changes in the craft template. We fixed this! NOTE: RC1 is required now.

# Redirect Changelog
## 1.0.9 - 2017-12-07

### Fixed
- Fixed a bug resulted in a query exception when using the plugin with Postgres and visiting a redirect url.

## 1.0.8 - 2017-11-06

### Fixed
- validateCustomFields was removed from the last Craft version. We changed the settings controller for that.

## 1.0.7 - 2017-10-22

### Fixed
- The branch was not merged correctly last build, we fixed it.

## 1.0.6 - 2017-10-19

### Fixed
- The introduced fix in version 1.0.5 created an error in some other database environments.

## 1.0.5 - 2017-10-11

### Fixed
- Fixed a bug resulted in a query exception when using the plugin with Postgres.

## 1.0.4 - 2017-10-04

### Fixed
- Fixed a bug that resets the hitAt and hitCount in the migration process.
- Fixed the form validation process and error message.

### Changed
- Added a simple url beautifier/formatter when saving the redirect.
- Cleanup some code.

### Added
- Added a main selection to filter on All redirects, Permanent redirects or Temporarily redirects.

## 1.0.3 - 2017-10-03
- Multi site support.
- Searchable and sortable list.
- Small fixes.

## 1.0.2 - 2017-07-07
- Fix for non default value in hitCount column needed for some database engines.

## 1.0.1 - 2017-06-02
- Added hit count and last hit date functionality.

## 1.0.0 - 2017-06-01
- Initial release.
