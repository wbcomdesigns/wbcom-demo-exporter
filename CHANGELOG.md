# Changelog

## Version 2.1.0 - Released

### Changed
- Updated existing files instead of creating v2 versions
- Cleaner codebase with direct updates to core files

## Version 2.0.0 - Released

### Added
- ✅ Zero-padded file naming for proper import ordering (tablename_0001.json format)
- ✅ Professional admin UI with card-based layout
- ✅ Export history tracking (last 10 exports)
- ✅ One-click export functionality
- ✅ Copy-to-clipboard for API endpoint
- ✅ Progress bar during export
- ✅ Success/error notifications
- ✅ Human-readable timestamps ("2 hours ago")
- ✅ Fresh export directory for each export
- ✅ Clear history option

### Changed
- 📦 Increased ZIP chunk size from 6.5MB to 50MB for better performance
- 🎨 Completely redesigned admin interface
- 🔧 Improved error handling with try-catch blocks
- 📝 Better code organization with v2 classes

### Fixed
- 🐛 Batch file indexing issue where file10 came before file2
- 🐛 Memory issues with large exports
- 🐛 Missing success feedback after export

### Security
- 🔒 All inputs properly sanitized
- 🔒 Nonce verification on all actions
- 🔒 Capability checks enforced

## Version 1.0.0
- Initial release
- Basic export functionality
- API endpoint for importer access