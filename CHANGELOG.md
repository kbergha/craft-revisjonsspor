# craft-revisjonsspor Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/).
## 1.0.0-alpha5 - 2020-12-08
### Changed
- Lowered logging level of plugin init from info to debug.
## 1.0.0-alpha4 - 2020-12-08
### Fixes
- Fixes PSR-4 namespace/composer 2.0 issue
## 1.0.0-alpha3 - 2020-04-21
### Added
- Adds logging of user activation.
- Configurable logging in frontend and backend.

### Changed
- Use refHandle() instead of displayName() to not get translated words for elements in log.
- Made logging of properties configurable.
- Don't log empty "saved by". Related to new user activation logging. 

## 1.0.0-alpha2 - 2020-04-20
### Added
- Adds logging of user group assignments.
- Added email and username of edited or saved user to log.
- Error handling in the log function
- Some doc blocks / code comments.

## 1.0.0-alpha - 2020-04-01
### Added
- Initial release
