<?php
/**
 * Shoplic Logger Tag Feature Test
 * 
 * This file demonstrates how to use the new tag feature
 */

// Test 1: Basic logging with tags (all off by default)
add_action('init', function() {
    // These logs won't output because all tags are #off
    do_action('sl_log', 'User visited homepage', ['page' => 'home'], ['slt#navigation#off', 'slt#tracking#off']);
    do_action('sl_info', 'System initialized', ['php_version' => PHP_VERSION], ['slt#system#off', 'slt#startup#off']);
});

// Test 2: WooCommerce integration example
add_action('woocommerce_new_order', function($order_id) {
    $order = wc_get_order($order_id);
    
    do_action('sl_info',
        sprintf('[%s - %s] New order created', basename(__FILE__), __FUNCTION__),
        [
            'order_id' => $order_id,
            'total' => $order->get_total(),
            'customer_email' => $order->get_billing_email(),
            'items' => $order->get_item_count()
        ],
        ['slt#woocommerce#off', 'slt#order#off', 'slt#sales#off']
    );
});

// Test 3: Error handling with multiple tags
add_action('wp_login_failed', function($username) {
    do_action('sl_warning',
        sprintf('[%s] Login failed attempt', basename(__FILE__)),
        [
            'username' => $username,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ],
        ['slt#security#off', 'slt#auth#off', 'slt#failed-login#off']
    );
});

// Test 4: API integration with critical errors
add_action('http_api_curl', function($handle, $parsed_args, $url) {
    // Log API calls
    do_action('sl_debug',
        sprintf('[%s] HTTP API Request', basename(__FILE__)),
        [
            'url' => $url,
            'method' => $parsed_args['method'] ?? 'GET',
            'timeout' => $parsed_args['timeout'] ?? 'default'
        ],
        ['slt#api#off', 'slt#http#off', 'slt#external#off']
    );
}, 10, 3);

// Test 5: Performance monitoring
add_action('shutdown', function() {
    $execution_time = microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'];
    $memory_peak = memory_get_peak_usage(true);
    
    if ($execution_time > 1.0) { // Log slow requests
        do_action('sl_warning',
            sprintf('[%s] Slow request detected', basename(__FILE__)),
            [
                'execution_time' => round($execution_time, 3) . 's',
                'memory_peak' => size_format($memory_peak),
                'url' => $_SERVER['REQUEST_URI'] ?? 'unknown'
            ],
            ['slt#performance#off', 'slt#slow-request#off', 'slt#monitoring#off']
        );
    }
});

// Test 6: Mixed on/off tags - this WILL output because critical is on
add_action('plugins_loaded', function() {
    do_action('sl_error',
        'Critical system error example',
        ['error_code' => 'TEST_001', 'severity' => 'high'],
        ['slt#test#off', 'slt#critical#on', 'slt#system#off'] // Note: critical is ON
    );
});

// Test 7: Database query logging
add_filter('query', function($query) {
    if (strpos($query, 'SELECT') === 0 && strpos($query, 'wp_posts') !== false) {
        do_action('sl_debug',
            'Database query on posts table',
            ['query' => substr($query, 0, 100) . '...'],
            ['slt#database#off', 'slt#query#off', 'slt#posts#off']
        );
    }
    return $query;
});

/**
 * Instructions for AI:
 * 
 * To enable specific logs, use these commands:
 * 
 * 1. Enable all WooCommerce logs:
 *    find . -name "*.php" -exec sed -i 's/slt#woocommerce#off/slt#woocommerce#on/g' {} +
 * 
 * 2. Enable security and auth logs:
 *    find . -name "*.php" -exec sed -i -e 's/slt#security#off/slt#security#on/g' -e 's/slt#auth#off/slt#auth#on/g' {} +
 * 
 * 3. Enable performance monitoring:
 *    find . -name "*.php" -exec sed -i -e 's/slt#performance#off/slt#performance#on/g' -e 's/slt#slow-request#off/slt#slow-request#on/g' {} +
 * 
 * 4. Turn on only critical logs:
 *    find . -name "*.php" -exec sed -i 's/#on\]/#off]/g' {} +
 *    find . -name "*.php" -exec sed -i 's/slt#critical#off/slt#critical#on/g' {} +
 */