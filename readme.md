# File Logger for WordPress

A powerful and flexible logging solution for WordPress that provides an easy-to-use interface for debugging and monitoring your WordPress applications.

## Installation

This plugin is designed to be used as a must-use plugin. Simply place the `file-logger.php` file in your `wp-content/mu-plugins/` directory.

## Usage

### Basic Logging

```php
// Simple log message
fl_log('Processing started');

// Log with data
fl_log('User registered', ['user_id' => 123, 'email' => 'user@example.com']);
```

### Different Log Levels

```php
// Error logging
fl_error('Payment failed', ['order_id' => 456, 'error' => 'Insufficient funds']);

// Info logging
fl_info('Order created', ['order_id' => 789]);

// Warning logging
fl_warning('Cache expired', ['cache_key' => 'homepage_data']);

// Debug logging (only works when WP_DEBUG is true)
fl_debug('Debug information', ['memory_usage' => memory_get_usage()]);
```

### Conditional Logging

The third parameter allows you to disable logging based on conditions:

```php
// Only log in development environment
$is_production = defined('WP_ENV') && WP_ENV === 'production';
fl_log('Development log', null, $is_production); // Won't log in production

// Only log when there's an error
$has_error = !empty($error_message);
fl_error('Error occurred', $error_details, !$has_error); // Only logs when error exists

// Only log for administrators
$is_not_admin = !current_user_can('manage_options');
fl_info('Admin action', $data, $is_not_admin); // Only logs for admins
```

### Best Practice: Include Context

Always include file and method names for better debugging:

```php
fl_log(
    sprintf('[%s - %s] Payment processed', basename(__FILE__), __METHOD__),
    ['order_id' => $order_id, 'amount' => $total]
);
```

## Key Features & Benefits

### 1. **Always Available**
- As a must-use plugin, it's automatically loaded and available globally
- No need to check if the plugin is active
- Works immediately after installation

### 2. **Performance Optimized**
- Lazy initialization - only creates resources when actually used
- Efficient file writing with proper locking
- Automatic log rotation to prevent huge files

### 3. **Developer Friendly**
- Simple, intuitive API with helper functions
- No complex configuration required
- Works out of the box

### 4. **Flexible Logging**
- Multiple log levels (error, info, warning, debug)
- Conditional logging to reduce noise
- Structured data logging with automatic JSON encoding

### 5. **Production Ready**
- Respects WordPress debug settings
- Safe error handling
- Automatic directory creation with proper permissions

### 6. **Easy Debugging**
- Timestamps on all log entries
- Clean, readable format
- Separate files for different log levels

## Log File Locations

Logs are stored in: `wp-content/uploads/file-logger/`

- `debug.log` - General logs and debug messages
- `error.log` - Error messages only
- `info.log` - Informational messages
- `warning.log` - Warning messages

## Configuration

The logger respects WordPress debug constants:
- `WP_DEBUG` - When false, debug logs are not written
- `WP_DEBUG_LOG` - When false, no logs are written

## Examples

### WooCommerce Order Processing
```php
add_action('woocommerce_order_status_processing', function($order_id) {
    fl_info(
        sprintf('[%s - %s] Order processing started', basename(__FILE__), __FUNCTION__),
        ['order_id' => $order_id]
    );
});
```

### API Error Handling
```php
$response = wp_remote_get($api_url);
if (is_wp_error($response)) {
    fl_error(
        sprintf('[%s - %s] API call failed', basename(__FILE__), __METHOD__),
        [
            'url' => $api_url,
            'error' => $response->get_error_message()
        ]
    );
}
```

### Performance Monitoring
```php
$start_time = microtime(true);
// ... some operation ...
$execution_time = microtime(true) - $start_time;

fl_debug(
    sprintf('[%s - %s] Operation completed', basename(__FILE__), __METHOD__),
    ['execution_time' => $execution_time, 'memory_peak' => memory_get_peak_usage()]
);
```

## License

This plugin is open source and available under the same license as WordPress.