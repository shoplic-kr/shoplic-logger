# Shoplic Logger Instructions for WordPress

When adding logging to WordPress projects, use Shoplic Logger actions.

## Core Actions

- `do_action('sl_log', $message, $data, $disable)` - General logging
- `do_action('sl_error', $message, $data, $disable)` - Errors
- `do_action('sl_info', $message, $data, $disable)` - Information
- `do_action('sl_warning', $message, $data, $disable)` - Warnings
- `do_action('sl_debug', $message, $data, $disable)` - Debug (only when WP_DEBUG is true)

## Best Practices

### Always include context
```php
do_action('sl_error',
    sprintf('[%s - %s] Payment failed', basename(__FILE__), __METHOD__),
    ['order_id' => 456, 'error' => 'Card declined']
);
```

### Use structured data
```php
// Good
do_action('sl_log', 'User registered', ['user_id' => 123, 'email' => 'user@example.com']);

// Bad
do_action('sl_log', 'User registered. ID: 123, Email: user@example.com');
```

### Conditional logging
```php
// Skip in production
$is_production = defined('WP_ENV') && WP_ENV === 'production';
do_action('sl_debug', 'Debug info', $data, $is_production);

// Log only on error
$has_error = !empty($error);
do_action('sl_error', 'Error occurred', $error_data, !$has_error);
```

## Examples

### WooCommerce order
```php
add_action('woocommerce_new_order', function($order_id) {
    $order = wc_get_order($order_id);
    
    do_action('sl_info',
        sprintf('[%s - %s] New order', basename(__FILE__), __FUNCTION__),
        [
            'order_id' => $order_id,
            'total' => $order->get_total(),
            'customer_email' => $order->get_billing_email()
        ]
    );
});
```

### API errors
```php
$response = wp_remote_get($api_url);

if (is_wp_error($response)) {
    do_action('sl_error',
        sprintf('[%s - %s] API failed', basename(__FILE__), __METHOD__),
        [
            'url' => $api_url,
            'error' => $response->get_error_message()
        ]
    );
}
```