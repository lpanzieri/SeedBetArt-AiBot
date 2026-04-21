# Logging Directory

This directory contains plugin debug logs.

## Log Files

### bsp-v2.log
- **Purpose**: General plugin activity and informational logs
- **Messages**: Plugin initialization, data fetches, shortcode renders
- **Level**: INFO and DEBUG

### bsp-v2-errors.log
- **Purpose**: Error and warning messages
- **Messages**: API failures, database errors, configuration issues
- **Level**: ERROR and WARNING

## Enabling Debug Logging

Add to `wp-config.php`:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Then set debug mode in plugin settings:
- Admin → Betting Signals → Settings
- Enable "Debug Mode"

## Log Format

```
[2024-01-15 10:30:45] [INFO] Message here
[2024-01-15 10:30:46] [ERROR] Error occurred: reason
```

## Log Rotation

Logs are manually rotated when they exceed 5MB. Old logs are archived with timestamps:
- `bsp-v2-2024-01-15.log`
- `bsp-v2-errors-2024-01-15.log`

## Viewing Logs

### Command Line
```bash
tail -f wp-content/plugins/Ver0.2/logs/bsp-v2.log
```

### WordPress Admin
- Install "Debug Bar" plugin
- Visit admin dashboard to see live logs

## Security

- Logs contain sensitive information (API responses, queries)
- Ensure logs directory is not publicly accessible
- Add `.htaccess` or `web.config` to restrict access if needed

### .htaccess (for Apache)
```
<FilesMatch "\.log$">
    Order allow,deny
    Deny from all
</FilesMatch>
```

### web.config (for IIS)
```xml
<system.webServer>
  <security>
    <requestFiltering>
      <fileExtensions>
        <add fileExtension=".log" allowed="false" />
      </fileExtensions>
    </requestFiltering>
  </security>
</system.webServer>
```

## Troubleshooting

If logs aren't being created:

1. Check directory permissions (should be 755 or writable)
2. Verify WP_DEBUG is enabled in wp-config.php
3. Check server error logs for PHP issues
4. Ensure debug mode is enabled in plugin settings
5. Verify WordPress has write permission to directory

## Performance Note

Debug logging has minimal performance impact (~1-2ms per logged message). Disable in production if concerned about performance.
