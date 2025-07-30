# Shoplic Logger - AI Tag Control Instructions

## Overview

Shoplic Logger is a WordPress logging plugin that allows developers to add comprehensive logging throughout the codebase with tag-based control. You can control which logs are actually output by toggling tags on/off.

## How the Tag System Works

1. **Tag Format in Code**: `slt#tagname@state`
   - `slt#` = prefix for easy discovery
   - `tagname` = descriptive tag name
   - `@state` = `@on` or `@off`

2. **Tag Storage in Logs**: Clean format without prefix/state
   - Logs show: `[TAGS: payment, critical]`
   - Not: `[TAGS: slt#payment@off, slt#critical@on]`

3. **Output Control**: Only logs with at least one `@on` tag are written to file

## Basic Usage

### Adding Logs with Tags

```php
// Using do_action (recommended)
do_action('sl_log', 'Payment started', $payment_data, ['slt#payment@off', 'slt#checkout@off']);
do_action('sl_error', 'API timeout', $error_data, ['slt#api@off', 'slt#critical@off']);
do_action('sl_info', 'User registered', $user_data, ['slt#user-register@off', 'slt#auth@off']);
do_action('sl_debug', 'Query executed', $query_data, ['slt#database@off', 'slt#performance@off']);
do_action('sl_warning', 'Low memory', $system_data, ['slt#system@off', 'slt#monitoring@off']);

// Using helper functions
sl_log('Payment started', $payment_data, ['slt#payment@off', 'slt#checkout@off']);
sl_error('API timeout', $error_data, ['slt#api@off', 'slt#critical@off']);

// With context
do_action('sl_error',
    sprintf('[%s - %s] Payment failed', basename(__FILE__), __METHOD__),
    ['order_id' => 456, 'error' => 'Card declined'],
    ['slt#payment@off', 'slt#error@off', 'slt#woocommerce@off']
);
```

## AI Control Commands

### Discovering Tags in Codebase

```bash
# Find all tags
grep -r "slt#" --include="*.php" | grep -o "slt#[^'\"]*" | sort | uniq

# Find specific tag usage
grep -r "slt#payment" --include="*.php"

# Count tag occurrences
grep -r "slt#" --include="*.php" -o | cut -d: -f2 | sort | uniq -c
```

### Controlling Logs

#### Turn on specific tags
```bash
# Enable payment logs
find . -name "*.php" -type f -exec sed -i 's/slt#payment@off/slt#payment@on/g' {} +

# Enable critical logs
find . -name "*.php" -type f -exec sed -i 's/slt#critical@off/slt#critical@on/g' {} +

# Enable multiple tags
find . -name "*.php" -type f -exec sed -i -e 's/slt#payment@off/slt#payment@on/g' -e 's/slt#checkout@off/slt#checkout@on/g' {} +
```

#### Turn off specific tags
```bash
# Disable payment logs
find . -name "*.php" -type f -exec sed -i 's/slt#payment@on/slt#payment@off/g' {} +

# Turn off all logs (reset)
find . -name "*.php" -type f -exec sed -i 's/@on\]/@off]/g' {} +
```

#### Complex scenarios
```bash
# Turn off all logs except critical
find . -name "*.php" -type f -exec sed -i 's/@on\]/@off]/g' {} +
find . -name "*.php" -type f -exec sed -i 's/slt#critical@off/slt#critical@on/g' {} +

# Enable debugging for specific file
sed -i 's/slt#debug@off/slt#debug@on/g' path/to/specific-file.php
```

## Common Use Cases

### 1. User Request: "Turn off payment logs, turn on user-register logs"
```bash
find . -name "*.php" -type f -exec sed -i 's/slt#payment@on/slt#payment@off/g' {} +
find . -name "*.php" -type f -exec sed -i 's/slt#user-register@off/slt#user-register@on/g' {} +
```

### 2. User Request: "Debug the checkout process"
```bash
find . -name "*.php" -type f -exec sed -i -e 's/slt#checkout@off/slt#checkout@on/g' -e 's/slt#payment@off/slt#payment@on/g' -e 's/slt#cart@off/slt#cart@on/g' {} +
```

### 3. User Request: "Show only errors and critical logs"
```bash
find . -name "*.php" -type f -exec sed -i 's/@on\]/@off]/g' {} +
find . -name "*.php" -type f -exec sed -i -e 's/slt#error@off/slt#error@on/g' -e 's/slt#critical@off/slt#critical@on/g' {} +
```

### 4. User Request: "Enable all database performance logs"
```bash
find . -name "*.php" -type f -exec sed -i -e 's/slt#database@off/slt#database@on/g' -e 's/slt#performance@off/slt#performance@on/g' {} +
```

## Best Practices for AI

1. **Always use find with -type f**: Ensures only files are processed
2. **Use specific paths when possible**: `find ./wp-content/plugins/my-plugin -name "*.php"`
3. **Check current state before changing**: `grep -r "slt#payment@" --include="*.php" | head -10`
4. **Group related tags**: Enable/disable related functionality together
5. **Reset before major changes**: Turn all off, then enable specific ones

## Tag Naming Conventions

Suggest these tag categories to developers:
- **Feature tags**: `payment`, `checkout`, `cart`, `user-register`
- **Level tags**: `error`, `critical`, `warning`, `info`
- **Component tags**: `database`, `api`, `cache`, `session`
- **Performance tags**: `performance`, `slow-query`, `memory`
- **Integration tags**: `woocommerce`, `stripe`, `mailchimp`

## Example: Complete Logging Implementation

```php
// Add comprehensive logging to a payment function
function process_payment($order_id) {
    do_action('sl_info', 
        sprintf('[%s] Payment process started', __FUNCTION__),
        ['order_id' => $order_id],
        ['slt#payment@off', 'slt#checkout@off']
    );
    
    try {
        $result = $payment_gateway->charge($order_id);
        
        do_action('sl_info',
            sprintf('[%s] Payment successful', __FUNCTION__),
            ['order_id' => $order_id, 'transaction_id' => $result->id],
            ['slt#payment@off', 'slt#checkout@off', 'slt#success@off']
        );
        
    } catch (Exception $e) {
        do_action('sl_error',
            sprintf('[%s] Payment failed', __FUNCTION__),
            ['order_id' => $order_id, 'error' => $e->getMessage()],
            ['slt#payment@off', 'slt#error@off', 'slt#critical@off']
        );
    }
}
```

## Admin Interface

Tags appear as clickable badges in the WordPress admin:
- Click any tag to filter logs containing that tag
- Active filters are highlighted in red
- Click "필터 해제" to clear filters

## Summary

The tag system allows you to:
1. Write comprehensive logs throughout the codebase
2. Control output without removing code
3. Quickly enable/disable logs for debugging
4. Filter logs in the admin interface
5. Group related functionality with tags

Remember: Developers add logs with tags in `off` state by default. You control which logs appear by toggling tags to `on`.