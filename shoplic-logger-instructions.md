# Instructions for using Shoplic Logger in WordPress

When you are asked to add logging to a WordPress project, you must use Shoplic Logger actions. This approach ensures your code never breaks even if the logger plugin is deactivated.

## 1. Core Logging Actions

Use these actions for different log levels:

- `do_action('sl_log', $message, $data, $disable)`: General log messages
- `do_action('sl_error', $message, $data, $disable)`: Error conditions
- `do_action('sl_info', $message, $data, $disable)`: Informational messages
- `do_action('sl_warning', $message, $data, $disable)`: Warning conditions
- `do_action('sl_debug', $message, $data, $disable)`: Debug information (only logs when `WP_DEBUG` is true)

Parameters:
- `$message` (string): The log message
- `$data` (array|null): Optional contextual data
- `$disable` (bool): Optional flag to skip logging when true

## 2. Best Practices

### A. Always Include Context Information
Use `sprintf` pattern with `basename(__FILE__)` and `__METHOD__` or `__FUNCTION__`:

```php
do_action('sl_error',
    sprintf('[%s - %s] Payment failed', basename(__FILE__), __METHOD__),
    ['order_id' => 456, 'error' => 'Card declined']
);
```

### B. Use Structured Data
Always pass contextual data as the second argument (associative array):

**Good:**
```php
do_action('sl_log', 'User registered', ['user_id' => 123, 'email' => 'user@example.com']);
```

**Bad:**
```php
do_action('sl_log', 'User registered. ID: 123, Email: user@example.com');
```

## 3. Conditional Logging

Use the third parameter `$disable` to skip logging based on conditions. The log is **skipped** when this parameter is `true`:

```php
// Skip logging in production
$is_production = defined('WP_ENV') && WP_ENV === 'production';
do_action('sl_debug', 'Debug info', $data, $is_production);

// Log only when error exists
$has_error = !empty($error);
do_action('sl_error', 'Error occurred', $error_data, !$has_error);

// Log only for slow operations
$is_fast = $execution_time < 1.0;
do_action('sl_warning', 'Slow operation', ['time' => $execution_time], $is_fast);
```

## 4. Real-World Examples

### Plugin Initialization
```php
add_action('init', function() {
    do_action('sl_log', 
        sprintf('[%s - %s] Plugin initialized', basename(__FILE__), __FUNCTION__)
    );
});
```

### WooCommerce Integration
```php
add_action('woocommerce_new_order', function($order_id) {
    $order = wc_get_order($order_id);
    
    do_action('sl_info',
        sprintf('[%s - %s] New order received', basename(__FILE__), __FUNCTION__),
        [
            'order_id' => $order_id,
            'total' => $order->get_total(),
            'customer_email' => $order->get_billing_email()
        ]
    );
});
```

### API Error Handling
```php
$response = wp_remote_get($api_url);

if (is_wp_error($response)) {
    do_action('sl_error',
        sprintf('[%s - %s] API request failed', basename(__FILE__), __METHOD__),
        [
            'url' => $api_url,
            'error_code' => $response->get_error_code(),
            'error_message' => $response->get_error_message()
        ]
    );
} else {
    // Log successful responses only in debug mode
    $is_not_debug = !defined('WP_DEBUG') || !WP_DEBUG;
    do_action('sl_debug',
        sprintf('[%s - %s] API response received', basename(__FILE__), __METHOD__),
        [
            'status_code' => wp_remote_retrieve_response_code($response),
            'body_preview' => substr(wp_remote_retrieve_body($response), 0, 100)
        ],
        $is_not_debug
    );
}
```

### Database Query Monitoring
```php
global $wpdb;
$start_time = microtime(true);

$results = $wpdb->get_results($sql);

$execution_time = microtime(true) - $start_time;
$is_fast = $execution_time < 0.1;

do_action('sl_warning',
    sprintf('[%s - %s] Slow query detected', basename(__FILE__), __METHOD__),
    [
        'query' => $sql,
        'execution_time' => $execution_time,
        'num_rows' => count($results)
    ],
    $is_fast // Skip if query is fast
);
```

### User Activity Tracking
```php
add_action('wp_login', function($user_login, $user) {
    do_action('sl_info',
        sprintf('[%s - %s] User logged in', basename(__FILE__), __FUNCTION__),
        [
            'user_id' => $user->ID,
            'user_login' => $user_login,
            'user_role' => implode(', ', $user->roles)
        ]
    );
}, 10, 2);
```

### Form Submission Handling
```php
add_action('gform_after_submission', function($entry, $form) {
    $is_spam = rgar($entry, 'status') === 'spam';
    
    do_action('sl_info',
        sprintf('[%s - %s] Form submitted', basename(__FILE__), __FUNCTION__),
        [
            'form_id' => $form['id'],
            'form_title' => $form['title'],
            'entry_id' => $entry['id'],
            'is_spam' => $is_spam
        ],
        $is_spam // Don't log spam submissions
    );
}, 10, 2);
```

## 5. Important Notes

### Log File Organization
Shoplic Logger automatically organizes logs by source:
- Plugin logs: `/wp-content/sl-logs/plugin-name/`
- Theme logs: `/wp-content/sl-logs/theme-name/`
- MU-Plugin logs: `/wp-content/sl-logs/mu-plugin-name/`

## Summary

1. **Include context**: Use `sprintf('[%s - %s] Message', basename(__FILE__), __METHOD__)`
2. **Use structured data**: Pass arrays as the second parameter
3. **Apply conditional logging**: Use the third parameter to control when logs are written
4. **Choose appropriate level**: `sl_error` for errors, `sl_info` for events, `sl_debug` for development