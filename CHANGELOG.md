# Changelog

All notable changes to SeedBetArt Ai Bot are documented in this file.

## [0.2] - 2024 - Complete Architectural Rebuild + Theme System

### ✨ NEW FEATURES

#### Multi-Theme Support System
- **Implemented complete theme architecture**
  - Theme folder structure: `assets/themes/{theme-slug}/`
  - Each theme has independent admin & frontend CSS/JS
  - Theme selection stored in `bsp_v2_theme` wp_option
  - Dynamic asset loading based on active theme
  - AJAX-powered theme switching without page reload

- **Pre-Built Themes**
  - **Basic Theme**: Blue-purple gradient (#667eea, #764ba2, #f093fb)
  - **Quiet Thoughts Theme**: Purple-teal-gold (#8b5cf6, #14b8a6, #f59e0b)
  - Each theme includes matching CSS for admin and frontend

- **Dedicated Themes Admin Page**
  - New menu item: `SeedBetArt Ai Bot → 🎨 Themes`
  - Responsive card-based theme browser
  - Visual preview of each theme colors
  - One-click theme switching
  - Active theme marked with ✓ badge
  - Auto-reload on theme change

#### Enhanced API Validation System
- **Real-Time API Validation**
  - Validate button for each API key (Odds, Football, OpenAI)
  - Live HTTP testing of API endpoints
  - Actual API request made during validation

- **Enhanced Error Reporting**
  - Specific error messages show what prevented communication
  - Button turns red on failure and STAYS red
  - Detailed error display below validation button
  - Error text formatted with visual hierarchy
  - WordPress toast notifications for success/failure

- **Validation Status Persistence**
  - Validation status stored in wp_options
  - Status persists across page loads
  - Green "validated" indicator shows current state
  - Nonce-protected AJAX requests

- **Feature Gating Based on Validation**
  - Shortcodes check all APIs validated before executing
  - Widgets check validation before displaying
  - Shows user-friendly error message if not validated

#### Theme Helper Functions (New in helpers.php)
```php
bsp_v2_get_available_themes()        // Scans themes folder
bsp_v2_get_current_theme()           // Returns active theme slug
bsp_v2_set_theme($theme)             // Sets active theme
bsp_v2_get_theme_url($type)          // Returns theme asset URL
bsp_v2_get_theme_dir($type)          // Returns theme filesystem path
bsp_v2_get_admin_assets()            // Returns admin CSS/JS paths
bsp_v2_get_frontend_assets()         // Returns frontend CSS/JS paths
```

#### API Validation Helper Functions
```php
bsp_v2_validate_odds_api()           // Test Odds-API connection
bsp_v2_validate_football_api()       // Test Football-API connection
bsp_v2_validate_openai_api()         // Test OpenAI connection
bsp_v2_are_all_apis_validated()      // Check if all APIs valid
bsp_v2_get_api_validation_status()   // Get status array
bsp_v2_get_openai_models()           // Returns available models
```

#### API Usage Tracking (Existing - Enhanced)
```php
bsp_v2_track_api_call($api_type)     // Track daily calls
bsp_v2_check_api_limit($api_type)    // Check against limits
bsp_v2_get_api_usage_stats($api_type) // Get usage percentage
bsp_v2_track_openai_tokens($tokens)  // Track monthly tokens
bsp_v2_check_openai_limit()          // Check token limit
bsp_v2_get_openai_usage_stats()      // Get token percentage
```

#### OpenAI Model Selection
- Dropdown selector in Settings with 7 models:
  - GPT-5 ($0.15/1K tokens)
  - GPT-5 Turbo ($0.08/1K tokens)
  - GPT-4o ($0.015/1K tokens) - Recommended
  - GPT-4o-mini ($0.00015/1K tokens) - Default
  - GPT-4 Turbo ($0.03/1K tokens)
  - GPT-4 ($0.03/1K tokens)
  - GPT-3.5 Turbo ($0.0005/1K tokens)
- Model selection stored in `bsp_v2_openai_model`
- AI classes automatically use selected model

### 🔧 ENHANCED

#### JavaScript Improvements
- **admin-enhanced.js**
  - New `selectThemeFromCard()` method for theme card buttons
  - Event listener for `.bsp-v2-theme-select-btn`
  - Enhanced validation feedback with error messages
  - Validation button stays red on failure (better UX)
  - Console logging for debugging theme changes
  - Improved error handling in AJAX calls

- **Validation Button Behavior**
  - Success: Green button, auto-resets after 3 seconds
  - Failure: Red button, STAYS red until successful validation
  - Error message displays specific issue preventing communication
  - Better user feedback for troubleshooting

#### Admin Interface
- **Settings Page Reorganization**
  - Removed Themes section from Settings (now in dedicated menu)
  - Cleaner, focused API configuration page
  - API validation status display beside each key field
  - Model selection dropdown with pricing info

- **Menu Structure**
  - Added 🎨 Themes menu item after 🗄️ Database
  - Updated menu order for logical flow
  - Consistent emoji icons for visual identification

#### Database
- New options:
  - `bsp_v2_api_validated_odds` (boolean)
  - `bsp_v2_api_validated_football` (boolean)
  - `bsp_v2_api_validated_openai` (boolean)
  - `bsp_v2_theme` (string - theme slug)
  - `bsp_v2_openai_model` (string - model ID)

#### Shortcodes & Widgets
- All shortcodes check validation before executing:
  - `[bsp_v2_value_bets]`
  - `[bsp_v2_ltd]`
  - `[bsp_v2_under25]`
  - `[bsp_v2_ai_analysis]`
  - `[bsp_v2_ai_explain]`

- All widgets check validation before displaying:
  - BSP_V2_Widget_Value_Bets
  - BSP_V2_Widget_LTD
  - BSP_V2_Widget_Under25

### 🐛 FIXED

#### JavaScript
- ✅ Removed literal `\n` character breaking JavaScript parser
- ✅ Validate buttons now respond to clicks
- ✅ Button color feedback working (green/red states)
- ✅ Console logging properly implemented
- ✅ AJAX error handling improved

#### API Validation
- ✅ All validation functions now return proper HTTP responses
- ✅ Error messages displayed instead of silent failures
- ✅ Validation persists across page loads
- ✅ Nonce validation prevents CSRF attacks

#### Theme System
- ✅ Assets properly load from active theme
- ✅ Admin enqueue functions use theme paths
- ✅ Fallback to basic theme if theme doesn't exist
- ✅ Theme folder structure auto-scanned

### 📊 COMMITS IN THIS VERSION

Commits implementing these features:
1. **e8a645b** - feat: Implement multi-theme architecture with theme switching UI
2. **1cfa842** - improve: Enhanced API validation error display with detailed messages
3. **8aff7f3** - feat: Add 'Quiet Thoughts' theme with purple, teal, and gold scheme
4. **fadbe109** - refactor: Move Themes to separate menu item with dedicated page

### 🔍 TECHNICAL DETAILS

#### Theme Architecture
```
assets/themes/
├── basic/
│   ├── admin/
│   │   ├── css/
│   │   │   ├── admin.css (150 lines)
│   │   │   └── admin-enhanced.css (999 lines - modern styles)
│   │   └── js/
│   │       ├── admin.js (200 lines)
│   │       └── admin-enhanced.js (400+ lines - modern features)
│   └── frontend/
│       ├── css/
│       │   └── frontend.css (300+ lines)
│       └── js/
│           └── frontend-interactive.js (300+ lines)
└── quiet-thoughts/
    ├── admin/ (same structure as basic)
    └── frontend/ (same structure as basic)
```

#### API Validation Flow
1. User enters API key in Settings
2. Clicks "✓ Validate" button
3. AJAX sends `wp_ajax_bsp_v2_validate_[api]_api` action
4. Backend calls validation function with actual HTTP request
5. Response returned as JSON:
   ```json
   {
     "success": true/false,
     "data": {
       "message": "Error details"
     }
   }
   ```
6. Frontend updates button color and status display
7. Status saved in wp_options for persistence

#### Theme Switching Flow
1. User clicks "Apply Theme" on theme card
2. AJAX sends `wp_ajax_bsp_v2_change_theme` action with theme slug
3. Backend validates theme exists, updates `bsp_v2_theme` option
4. Frontend reloads page with new theme
5. Admin enqueue functions load theme-specific assets
6. All admin and frontend elements use new theme colors

### 📈 STATISTICS

**Plugin Size**: ~500KB
**Files Changed**: 20+ files across 6 commits
**New Functions**: 15+ helper functions
**CSS Lines**: 2600+ lines total (all themes)
**JavaScript Lines**: 1200+ lines (interactive features)
**Lines of Code Added**: 3000+

### 🎨 COLOR SCHEMES

**Basic Theme**
- Primary: #667eea
- Secondary: #764ba2
- Accent: #f093fb

**Quiet Thoughts Theme**
- Primary: #8b5cf6
- Secondary: #14b8a6
- Accent: #f59e0b

Both themes share consistent:
- Success: #4caf50
- Warning: #ff9800
- Danger: #f44336

### 🔐 SECURITY IMPROVEMENTS

- ✅ All AJAX requests validated with nonces
- ✅ API keys sanitized on input
- ✅ Settings restricted to manage_options capability
- ✅ Theme slugs validated against available themes
- ✅ Better error message handling without exposing sensitive info

### 📚 DOCUMENTATION UPDATES

- ✅ README.md completely rewritten
- ✅ Theme system documentation added
- ✅ API validation workflow documented
- ✅ Custom theme creation guide added
- ✅ CHANGELOG.md updated
- ✅ UI_ENHANCEMENTS.md updated

---

## [0.2 - Previous Sections] - 2024 - Complete Architectural Rebuild

### Changed
- **Complete rewrite**: Rebuilt from scratch with improved architecture
- **Namespace strategy**: Switched from nested namespaces (`\SBAH\Admin`) to simple flat prefixes (`BSP_V2_`)
- **Class naming**: All classes now use `BSP_V2_` prefix for consistency and clarity
- **Function naming**: All functions now use `bsp_v2_` prefix to prevent conflicts
- **File naming**: Updated all files to use `class-bsp-v2-*` pattern for consistency
- **Directory structure**: Reorganized for better code separation
- **Initialization order**: Fixed load sequence (helpers → config → services → classes → admin → shortcodes → cron)

### Added (Previous Release)
- **Enhanced logging system**: Comprehensive debug logging with file rotation
- **Services layer**: New `SettingsManager` for centralized settings access
- **Better error handling**: Try-catch blocks integrated throughout
- **Configuration constants**: Centralized API endpoints and cache TTLs
- **Improved templates**: Better-organized frontend templates with proper escaping
- **Admin dashboard**: Cleaner admin interface with stats cards
- **CSS styling**: Professional admin and frontend styles
- **JavaScript utilities**: Admin JS for settings management
- **Database schema**: Better-organized insights table structure
- **Interactive Charts**: Chart.js integration for data visualization
- **UI Enhancements**: Modern design system with professional color palettes

### Fixed (Previous Release)
- **Plugin activation**: Resolved all activation process issues from v0.1
- **Namespace conflicts**: Eliminated 28+ namespace contamination issues
- **Missing files**: All referenced classes now properly created
- **Loading order**: Helpers now loaded before any other components
- **Global function references**: Consistent prefix prevents WP conflicts
- **Template namespace issues**: Removed all nested namespace references in templates

---

## [0.1] - Initial Release

### Added
- Basic betting analysis engine
- Odds-API integration for market data
- API-Football integration for team/match data
- Admin dashboard with settings
- Shortcode support for displaying bets
- Widget support for sidebars
- Cron-based automated updates

---

## Version Compatibility

| Version | WordPress | PHP | Status |
|---------|-----------|-----|--------|
| 0.2     | 6.4+      | 8.2+ | ✅ Current |
| 0.1     | 6.2+      | 8.0+ | ⛔ Legacy |

## Future Roadmap

- [ ] Additional pre-built themes
- [ ] Theme customizer UI for colors/fonts
- [ ] Export theme as package
- [ ] Marketplace for community themes
- [ ] Advanced notifications system
- [ ] Email alerts for high-EV bets
- [ ] Multi-language support
- [ ] Mobile app integration
- [ ] Betting history tracking
- [ ] Advanced analytics dashboard
# Changelog

## [0.2] - 2024 - Complete Architectural Rebuild

### Changed
- **Complete rewrite**: Rebuilt from scratch with improved architecture
- **Namespace strategy**: Switched from nested namespaces (`\SBAH\Admin`) to simple flat prefixes (`BSP_V2_`)
- **Class naming**: All classes now use `BSP_V2_` prefix for consistency and clarity
- **Function naming**: All functions now use `bsp_v2_` prefix to prevent conflicts
- **File naming**: Updated all files to use `class-bsp-v2-*` pattern for consistency
- **Directory structure**: Reorganized for better code separation
- **Initialization order**: Fixed load sequence (helpers → config → services → classes → admin → shortcodes → cron)

### Added
- **Enhanced logging system**: Comprehensive debug logging with file rotation
- **Services layer**: New `SettingsManager` for centralized settings access
- **Better error handling**: Try-catch blocks integrated throughout
- **Configuration constants**: Centralized API endpoints and cache TTLs
- **Improved templates**: Better-organized frontend templates with proper escaping
- **Admin dashboard**: Cleaner admin interface with stats cards
- **CSS styling**: Professional admin and frontend styles
  - `admin-enhanced.css`: Modern gradient headers, animated stat cards, dark mode support
  - `frontend.css`: Responsive widget styling
- **JavaScript utilities**: Admin JS for settings management and API validation
  - `admin-enhanced.js`: Advanced dashboard features, auto-refresh, API testing
  - `frontend-interactive.js`: Sortable columns, live search, CSV export
- **Database schema**: Better-organized insights table structure
- **Interactive Charts**: Chart.js integration for data visualization
- **UI Enhancements**: Modern design system with professional color palettes, animations, and responsive layouts (see [UI_ENHANCEMENTS.md](UI_ENHANCEMENTS.md))

### Fixed
- **Plugin activation**: Resolved all activation process issues from v0.1
- **Namespace conflicts**: Eliminated 28+ namespace contamination issues
- **Missing files**: All referenced classes now properly created
- **Loading order**: Helpers now loaded before any other components
- **Global function references**: Consistent prefix prevents WP conflicts
- **Template namespace issues**: Removed all nested namespace references in templates

### Removed
- **Nested namespaces**: Simplified to flat prefix system
- **Complex class structures**: Removed unnecessary abstraction layers
- **Dependency on namespaced helpers**: Now using simple global functions

### Technical Details

#### Before (v0.1 - Issues)
```php
namespace SBAH;
namespace SBAH\Admin;
// Mixed namespaces in templates causing reference errors
// 28+ instances of \SBAH\ in wrong contexts
// Plugin activation silent failures
// Inconsistent naming across files
```

#### After (v0.2 - Clean)
```php
// All classes prefixed with BSP_V2_
class BSP_V2_Cache { ... }
class BSP_V2_Client { ... }

// All functions prefixed with bsp_v2_
function bsp_v2_log() { ... }
function bsp_v2_get_option() { ... }

// Clear, consistent, no namespace conflicts
```

#### Key Files Changed
- Main: `seedbet-analyzer.php` → `betting-signals-plus-v2.php`
- Helpers: Recreated with 20+ functions
- Classes: 6 core classes (`Cache`, `Client`, `Logic`, `Insights`, `OpenAI`, `Cron`)
- Admin: Modular admin components (`admin-main.php`, `admin-menu.php`, `admin-settings.php`)
- Shortcodes: Reorganized into `shortcodes-bets.php` and `shortcodes-ai.php`

#### Database
- Options prefix: `bsp_v2_*` (was `sbah_*`)
- Table name: `{prefix}bsp_v2_insights` (was `{prefix}sbah_insights`)

### Migration Guide (v0.1 → v0.2)

⚠️ **Note**: v0.2 is a complete rebuild. Following migration steps recommended:

1. **Backup database**: Export all `bsp_v2_*` options
2. **Deactivate v0.1**: Ensure cron jobs are cleared
3. **Install v0.2**: Upload to `/wp-content/plugins/`
4. **Reconfigure API keys**: Enter in new Settings page
5. **Re-enable features**: Check "Enable AI" if using OpenAI
6. **Test shortcodes**: Verify betting displays appear

### Performance Improvements
- Simplified caching logic reduces memory overhead
- Faster initialization (no namespace resolution)
- Better prepared statements in database queries
- Optimized WordPress options access

### Security Improvements
- All output properly escaped (esc_html, esc_attr, wp_kses_post)
- All database queries use prepared statements
- API keys sanitized on input
- AJAX requests validated with nonces
- Input validation on all forms

### Bug Fixes from v0.1
- ✅ Plugin activation failures (root cause: namespace conflicts)
- ✅ Missing class references (root cause: inconsistent naming)
- ✅ Logging before helpers loaded (root cause: load order)
- ✅ Template namespace contamination (root cause: nested namespaces)
- ✅ Silent admin errors (root cause: exception suppression)

### Deprecations
- All `\SBAH\*` classes no longer exist
- Old `sbah_*` functions no longer available
- Old database option names (`sbah_*`) not used

### New in v0.2
- `BSP_V2_VERSION` constant for version tracking
- Debug mode with verbose logging
- Cron job health checks
- API key validation in admin
- Settings persistence via SettingsManager
- Professional admin dashboard
- Responsive frontend templates

### Testing Recommendations
- [ ] Test plugin activation on fresh WordPress install
- [ ] Verify all API keys can be entered in settings
- [ ] Test each shortcode displays data correctly
- [ ] Check debug logs show appropriate messages
- [ ] Verify cron job runs hourly
- [ ] Test on mobile to verify responsive design
- [ ] Validate all escaping prevents XSS
- [ ] Check database queries use prepared statements

### Known Limitations
- Requires HTTPS for external API calls
- OpenAI API usage costs (check pricing)
- Odds-API free tier limited to 100 calls/month
- API-Football free tier limited to 100 calls/day

### Future Roadmap (v0.3+)
- GraphQL API for programmatic access
- Advanced analytics dashboard
- User-specific bet tracking
- Email notifications
- Discord/Slack integration
- Mobile app
- Predictive model improvements
- Multi-currency support
- Localization support

---

## [0.1] - Initial Release

### Features
- Basic value bet detection
- Lay The Draw analysis
- Under 2.5 goals prediction
- OpenAI integration for match analysis
- Admin dashboard with API key management
- WordPress shortcodes for frontend display
- Hourly cron job for data updates
- Transient-based caching
- Debug logging system

### Known Issues (Fixed in v0.2)
- ⚠️ Plugin activation sometimes fails
- ⚠️ Namespace conflicts in templates
- ⚠️ Inconsistent naming conventions
- ⚠️ Loading order issues
