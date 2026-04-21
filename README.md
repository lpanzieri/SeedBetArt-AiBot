# SeedBetArt Ai Bot

A WordPress plugin for analyzing football matches and providing data-driven betting recommendations powered by AI, with advanced theme customization and multi-theme support.

## Version

**0.2** - Completely rebuilt from v0.1 with improved architecture, theme system, and enhanced API validation

## Features

### Core Betting Analysis
- **Value Bets**: Identifies matches with positive expected value (EV) based on odds analysis
- **Lay The Draw (LTD)**: Suggests matches where laying the draw is statistically advantageous
- **Under 2.5 Goals**: Predicts low-scoring matches with odds analysis
- **AI Analysis**: Uses OpenAI to provide detailed match analysis and betting rationales
- **Automated Updates**: Hourly cron jobs fetch latest odds and match data

### Dashboard & Interface
- **Modern Admin Dashboard**: Real-time statistics, interactive charts, and professional design
- **Dashboard Widgets**: Quick stats cards, data visualizations, and API usage monitoring
- **Activity Logging**: Comprehensive debug logs and system monitoring
- **Search Parameters**: Configurable betting algorithm parameters with 12+ sliders

### Theme System ✨ NEW
- **Multi-Theme Support**: Switch between different theme designs instantly
- **Pre-Built Themes**:
  - **Basic**: Blue-purple gradient with clean, professional styling
  - **Quiet Thoughts**: Purple, teal, and gold for sophisticated appearance
- **Theme-Specific Styling**: Each theme has independent CSS/JS files
- **Dedicated Themes Menu**: Easy theme management from admin panel
- **Real-Time Application**: Theme changes apply immediately without page reload

### API Management ✨ ENHANCED
- **API Validation System**: Real-time validation of API keys with detailed error reporting
- **Validation Status Display**: Color-coded indicators (green=valid, red=failed)
- **Error Diagnostics**: Specific error messages showing what prevented API communication
- **API Usage Monitoring**: Track daily/monthly API call usage and token consumption
- **Configurable Limits**: Set usage limits to control costs and prevent overage
- **Nonce Security**: AJAX requests protected with WordPress security nonces

### Advanced Features
- **Responsive Design**: Works seamlessly on desktop, tablet, and mobile devices
- **Dark Mode Support**: Automatic system preference detection with manual toggle
- **Interactive Charts**: Chart.js integration for data visualization
- **Database Management**: View and manage plugin data with database tools
- **Settings Management**: Centralized configuration for all plugin options

## Requirements

- WordPress 6.4 or later
- PHP 8.2 or later
- MySQL 5.7+ or MariaDB 10.2+
- SSL/TLS enabled (for API requests)

## Required API Keys

### 1. Odds-API.io
- **Purpose**: Fetch current odds and match data
- **Free Tier**: 100 requests/month
- **Sign up**: https://www.odds-api.io/
- **Recommendation**: Paid plan for production use

### 2. API-Football.com
- **Purpose**: Get detailed match statistics and team information
- **Free Tier**: 100 requests/day
- **Sign up**: https://www.api-football.com/
- **Note**: Used for enhanced data enrichment

### 3. OpenAI API
- **Purpose**: Generate AI-powered betting analysis
- **Availability**: All subscription tiers
- **Sign up**: https://platform.openai.com/
- **Pricing**: Usage-based (check OpenAI documentation)
- **Available Models**:
  - `gpt-5.4` - Highest quality reasoning
  - `gpt-5.4-mini` - Recommended balance of quality and cost
  - `gpt-5.4-nano` - Lowest cost and fastest responses

## Installation

1. Download the plugin folder to `/wp-content/plugins/`
2. Go to WordPress Admin → Plugins
3. Activate "SeedBetArt Ai Bot"
4. Go to "SeedBetArt Ai Bot" menu in the admin sidebar
5. Configure and validate your API keys in **Settings**
6. Select your preferred theme in **🎨 Themes**
7. View recommendations on the Dashboard

## Configuration

### Step 1: API Key Configuration
Navigate to **SeedBetArt Ai Bot → Settings** and enter:

1. **Odds-API Key**: Your API key from odds-api.io
2. **Football-API Key**: Your API key from api-football.com
3. **OpenAI API Key**: (Optional) For AI-powered analysis

### Step 2: API Validation ✨ NEW
For each API key:
1. Enter the key in the corresponding field
2. Click the **✓ Validate** button next to each field
3. Wait for validation feedback:
   - **Green button + checkmark**: API key is valid ✓
   - **Red button + error message**: Shows specific error preventing communication ✗
4. Error messages will display the exact issue (e.g., "Invalid API key", "Rate limit exceeded", "Connection timeout")
5. Button stays red until you successfully validate - helps prevent missing configurations

### Step 3: Select OpenAI Model
Choose the model for AI analysis:
- **gpt-5.4-mini** (Recommended): Best balance of cost and capability
- **gpt-5.4**: Highest quality reasoning output
- **gpt-5.4-nano**: Most economical option
- Other models available for specific use cases

### Step 4: Set Usage Limits (Optional)
Configure API usage limits to manage costs:
- **OpenAI Token Limit**: Maximum tokens per month
- **Odds-API Call Limit**: Maximum calls per day
- **Football-API Call Limit**: Maximum calls per day

### Step 5: Choose Your Theme ✨ NEW
Navigate to **SeedBetArt Ai Bot → 🎨 Themes** and:
1. Browse available theme cards in responsive grid
2. See current theme marked with ✓ badge
3. Click **Apply Theme** on your desired theme
4. Page auto-refreshes with new theme applied
5. All admin and frontend elements instantly use new theme colors

### Cache Settings
- **Odds Cache**: 1 hour (refreshes hourly via cron)
- **Events Cache**: 30 minutes
- **Insights Cache**: 2 hours

## Usage

### Display Betting Recommendations via Shortcodes

Use WordPress shortcodes to display recommendations on pages/posts:

```
[bsp_v2_value_bets]      - Show value betting opportunities
[bsp_v2_ltd]             - Show Lay The Draw suggestions
[bsp_v2_under25]         - Show Under 2.5 recommendations
```

**Features**:
- Automatically validates all APIs before displaying
- Shows error message if any API is not validated
- Responsive design works on all devices
- Theme-aware styling matches selected theme

### Display Betting Recommendations via Widgets

Three dedicated widgets are available for placement in sidebars and widget areas:

- **💰 Value Bets Widget** - Display value betting opportunities
  - Customize number of bets displayed
  - Choose display style: List, Cards, or Compact
  - Toggle confidence score and EV display
  
- **🎯 Lay The Draw Widget** - Show Lay The Draw recommendations
  - Adjustable number of recommendations
  - Optional draw probability display
  
- **⚽ Under 2.5 Goals Widget** - Display Under 2.5 Goals suggestions
  - Configurable item count
  - Show/hide expected goals (xG) and confidence scores

**Interactive Widget Features**:
- **Sortable columns**: Click headers to sort data
- **Live search**: Real-time filtering of results
- **Export to CSV**: Download data for analysis
- **Auto-refresh**: Configurable update intervals
- **Responsive design**: Adapts to all screen sizes
- **Theme-aware**: Inherits colors from active theme

**To use widgets:**
1. Go to **WordPress Admin → Appearance → Widgets**
2. Drag any of the three BSP V2 widgets into your desired widget area
3. Configure the widget options (title, number of items, display style, etc.)
4. Save changes

### Display AI Analysis

```
[bsp_v2_ai_analysis sport="football" league="Premier League"]
[bsp_v2_ai_explain type="value" odds="2.5"]
```

**Features**:
- AI-powered match analysis using configured OpenAI model
- Requires OpenAI API validation
- Detailed explanations of betting logic
- Customizable by league and match type
- Theme-aware styling

## Admin Menu Structure

```
SeedBetArt Ai Bot (Main Menu)
├── 📊 Dashboard - Real-time statistics and charts
├── ⚙️ Settings - API configuration and limits
├── 📋 Activity Log - Debug logs and monitoring
├── 🎯 Search Params - Betting algorithm parameters
├── 🗄️ Database - Database management tools
└── 🎨 Themes - Theme selection and management ✨ NEW
```

## Theme System ✨ NEW

### Available Themes

#### Basic Theme
- **Primary Color**: #667eea (Indigo)
- **Secondary Color**: #764ba2 (Purple)
- **Accent Color**: #f093fb (Pink)
- **Style**: Clean, professional gradient design
- **Best For**: Standard business appearance

#### Quiet Thoughts Theme
- **Primary Color**: #8b5cf6 (Purple)
- **Secondary Color**: #14b8a6 (Teal)
- **Accent Color**: #f59e0b (Gold)
- **Style**: Sophisticated with warm tones
- **Best For**: Premium, elegant appearance

### How Theme System Works

1. **Theme Structure**: Each theme has independent CSS and JavaScript files
   ```
   assets/themes/{theme-name}/
   ├── admin/
   │   ├── css/ (admin-enhanced.css, admin.css)
   │   └── js/ (admin-enhanced.js, admin.js)
   └── frontend/
       ├── css/ (frontend.css)
       └── js/ (frontend-interactive.js)
   ```

2. **Theme Selection**: Stored in `bsp_v2_theme` WordPress option
3. **Asset Loading**: Admin enqueue functions dynamically load theme assets
4. **Theme Switching**: AJAX handler allows instant switching without plugin reload
5. **All Pages Themed**: Admin, frontend widgets, and shortcodes all respect active theme

### Creating Custom Themes

To create a custom theme:

1. Create folder: `assets/themes/your-theme-name/`
2. Copy files from Basic or Quiet Thoughts theme as template
3. Update CSS color variables (primary, secondary, accent, success, warning, danger)
4. Customize CSS for unique styling
5. Upload to plugin folder
6. New theme appears automatically in Themes menu (🎨 Themes page)

### Theme Color Customization

Each theme CSS uses root variables for easy customization:
```css
:root {
    --primary-color: #667eea;      /* Main brand color */
    --secondary-color: #764ba2;    /* Secondary accent */
    --accent-color: #f093fb;       /* Highlight color */
    --success-color: #4caf50;      /* Success state */
    --warning-color: #ff9800;      /* Warning state */
    --danger-color: #f44336;       /* Error state */
    --light-gray: #f5f7fa;         /* Background */
    --border-color: #e0e6ed;       /* Borders */
    --text-dark: #2c3e50;          /* Primary text */
    --text-light: #7f8c8d;         /* Secondary text */
}
```

## Directory Structure

```
SeedBetArt Ai Bot/
├── betting-signals-plus-v2.php              Main plugin file
├── includes/
│   ├── helpers.php                          Global utility functions (700+ lines)
│   ├── config-constants.php                 Configuration constants
│   ├── class-bsp-v2-cache.php              Caching system
│   ├── class-bsp-v2-client.php             External API client
│   ├── class-bsp-v2-logic.php              Betting analysis logic
│   ├── class-bsp-v2-insights.php           AI insights management
│   ├── class-bsp-v2-openai.php             OpenAI integration
│   ├── class-bsp-v2-cron.php               Scheduled tasks
│   ├── admin-main.php                      Admin dashboard & menu
│   ├── admin-menu.php                      Admin menu configuration
│   ├── admin-settings.php                  Settings page
│   ├── shortcodes-bets.php                 Betting shortcodes
│   ├── shortcodes-ai.php                   AI shortcodes
│   └── services-settings-manager.php       Settings service
├── templates/                               Frontend templates
│   ├── template-value-bets.php
│   ├── template-ltd.php
│   ├── template-under25.php
│   ├── template-ai-value.php
│   ├── template-ai-ltd.php
│   └── template-ai-under25.php
├── assets/
│   ├── themes/
│   │   ├── basic/
│   │   │   ├── admin/
│   │   │   │   ├── css/ (admin-enhanced.css, admin.css)
│   │   │   │   └── js/ (admin-enhanced.js, admin.js)
│   │   │   └── frontend/
│   │   │       ├── css/ (frontend.css)
│   │   │       └── js/ (frontend-interactive.js)
│   │   └── quiet-thoughts/
│   │       ├── admin/
│   │       │   ├── css/ (admin-enhanced.css, admin.css)
│   │       │   └── js/ (admin-enhanced.js, admin.js)
│   │       └── frontend/
│   │           ├── css/ (frontend.css)
│   │           └── js/ (frontend-interactive.js)
│   ├── css/ (backward compat - synced with themes)
│   └── js/ (backward compat - synced with themes)
├── logs/                                   Plugin log files
├── README.md                               This file
├── CHANGELOG.md                            Version history
├── UI_ENHANCEMENTS.md                      UI/UX documentation
└── uninstall.php                           Plugin cleanup script
```

## API Validation ✨ ENHANCED

### Validation Flow

1. **Enter API Key**: User pastes key into Settings form
2. **Click Validate**: Sends AJAX request to backend with nonce
3. **Backend Testing**: Validation function makes actual API request
4. **Response Handling**:
   - **Success**: Button turns green, displays "✓ Validated"
   - **Failure**: Button turns red, stays red, displays "✗ Failed" with specific error
5. **Error Messages**: Detailed error explains what prevented communication
6. **WordPress Notice**: Toast notification shows success/failure

### Common Validation Errors

| Error | Solution |
|-------|----------|
| "Invalid API key" | Check key copied correctly, no extra spaces |
| "Rate limit exceeded" | Upgrade plan or wait for limit reset |
| "Connection timeout" | Verify HTTPS enabled, check firewall rules |
| "Unauthorized" | Key may be revoked or expired |
| "Service unavailable" | Try again later, API may be down |
| "Network error" | Check internet connection and firewall |

## Debugging

### Enable Debug Mode

1. Go to **Settings → Enable Debug Mode** checkbox
2. Or add to `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

### View Logs

Navigate to **SeedBetArt Ai Bot → Activity Log** to see:
- API request logs
- Validation attempts and results
- Error messages
- Theme switching events
- Cache operations

Or check files in `/logs/`:
- `bsp-v2.log` - General plugin logs
- `bsp-v2-errors.log` - Error logs only

### Common Issues

**API Validation Fails with Specific Error**
- Read error message carefully - it shows the exact issue
- If "Network error", verify API endpoint is accessible
- If "Invalid key", double-check key string
- If "Rate limit", check API dashboard for quota status

**No Bets Displaying**
- Check all APIs are validated (Settings page shows status)
- Verify API quotas not exceeded
- Check Activity Log for specific errors
- Run manual database check (Database menu)

**API Limits Hit**
- Check usage stats in Settings page
- Increase time-based limits or upgrade API plan
- Review cron job frequency

**Theme Not Applying**
- Clear browser cache (Ctrl+F5 or Cmd+Shift+R)
- Verify theme folder exists in `/assets/themes/`
- Check browser console for JavaScript errors
- View Activity Log for errors

**Slow Performance**
- Increase cache TTLs in `includes/config-constants.php`
- Reduce shortcode refresh frequency
- Consider using a database query cache plugin
- Check API response times

## Naming Conventions

- **Prefix**: `bsp_v2_` for functions, `BSP_V2_` for classes
- **Database**: All options prefixed with `bsp_v2_`
- **Database Table**: `{prefix}bsp_v2_insights` for AI insights
- **Shortcodes**: `[bsp_v2_*]` format
- **Theme Folders**: `assets/themes/{theme-slug}/`
- **AJAX Actions**: `wp_ajax_bsp_v2_*`

## Database Schema

### wp_options Table
Plugin settings and cache:
```
bsp_v2_api_key_odds
bsp_v2_api_key_football
bsp_v2_api_key_openai
bsp_v2_openai_model
bsp_v2_api_validated_odds          (boolean)
bsp_v2_api_validated_football      (boolean)
bsp_v2_api_validated_openai        (boolean)
bsp_v2_theme                       (current theme slug)
bsp_v2_debug_enabled
bsp_v2_auto_refresh
bsp_v2_cache_odds_{sport}          (transient)
```

### wp_bsp_v2_insights Table
```
id (INT PRIMARY KEY)
user_id (INT)
match_id (VARCHAR)
insight_type (VARCHAR)
ai_analysis (LONGTEXT)
confidence (DECIMAL)
created_at (DATETIME)
updated_at (DATETIME)
```

### Daily Tracking (wp_options - expires daily)
```
bsp_v2_odds_api_calls_YYYY-MM-DD
bsp_v2_football_api_calls_YYYY-MM-DD
```

### Monthly Tracking (wp_options)
```
bsp_v2_openai_tokens_YYYY-MM
```

## Security

- All database queries use prepared statements
- All output properly escaped (esc_html, esc_attr, wp_kses_post)
- API keys stored in WordPress options with sanitization
- AJAX requests validated with WordPress nonces
- Input validation on all forms
- Settings restricted to manage_options capability
- HTTPS required for API requests

## Performance

- Transient-based caching (1-2 hour TTLs)
- Hourly cron jobs prevent API overwhelming
- Database queries optimized with indexes
- Theme assets loaded from current theme only
- Lazy-loading of charts and interactive elements
- Asset minification support
- Conditional asset loading (only on BSP pages)

## Support & Documentation

- **Logs**: Check `/logs/` directory or Activity Log page
- **Debug**: Enable WP_DEBUG for detailed information
- **Theme Docs**: See [Theme System](#theme-system-new) section
- **API Validation**: See [API Validation](#api-validation-new-enhanced) section
- **API Docs**: 
  - Odds-API: https://odds-api.io/
  - API-Football: https://www.api-football.com/
  - OpenAI: https://platform.openai.com/docs/

## Version History

### 0.2 (Current)
- ✨ Multi-theme support with 2 pre-built themes (Basic, Quiet Thoughts)
- ✨ Theme management admin interface with dedicated menu
- ✨ Enhanced API validation with detailed error messages
- ✨ Validation errors display with specific diagnostic information
- ✨ Real-time validation feedback (green=success, red=fail)
- Improved error handling and logging
- Better template organization
- Modern admin interface with charts
- Database management tools
- Activity logging system
- 14+ major features implemented

### 0.1
- Initial release
- Basic betting analysis
- Single theme

## License

GPL-2.0-or-later

## Author

SeedBetArt Development Team
