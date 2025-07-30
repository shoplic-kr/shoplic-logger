<?php
/**
 * 쇼플릭 로거 테스트 파일
 */

// 직접 접근 방지
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// 다양한 태그 조합으로 테스트 로그 생성
add_action( 'init', function() {
    // 요청 파라미터로 테스트 로그 생성
    if ( isset( $_GET['test_shoplic_logger'] ) && current_user_can( 'manage_options' ) ) {
        
        // 태그 조합 1: system, startup
        do_action( 'sl_info', 
            sprintf( '[%s - %s] 시스템 시작 로그', basename(__FILE__), __FUNCTION__ ),
            array( 'memory' => memory_get_usage(), 'time' => current_time( 'mysql' ) ),
            array( 'slt#system@on', 'slt#startup@on' )
        );
        
        // 태그 조합 2: api, error
        do_action( 'sl_error',
            sprintf( '[%s - %s] API 연결 오류', basename(__FILE__), __FUNCTION__ ),
            array( 'url' => 'https://api.example.com', 'error' => 'Connection timeout' ),
            array( 'slt#api@on', 'slt#error@on' )
        );
        
        // 태그 조합 3: payment, checkout, order
        do_action( 'sl_log',
            sprintf( '[%s - %s] 결제 프로세스 시작', basename(__FILE__), __FUNCTION__ ),
            array( 'order_id' => rand(1000, 9999), 'amount' => '50,000원' ),
            array( 'slt#payment@on', 'slt#checkout@on', 'slt#order@on' )
        );
        
        // 태그 조합 4: user-register, auth
        do_action( 'sl_info',
            sprintf( '[%s - %s] 새 사용자 등록', basename(__FILE__), __FUNCTION__ ),
            array( 'user_email' => 'test@example.com', 'user_role' => 'subscriber' ),
            array( 'slt#user-register@on', 'slt#auth@on' )
        );
        
        // 태그 조합 5: woocommerce, sales
        do_action( 'sl_log',
            sprintf( '[%s - %s] 상품 판매 완료', basename(__FILE__), __FUNCTION__ ),
            array( 'product' => 'Test Product', 'quantity' => 2, 'revenue' => '100,000원' ),
            array( 'slt#woocommerce@on', 'slt#sales@on' )
        );
        
        // 태그 조합 6: security, failed-login
        do_action( 'sl_warning',
            sprintf( '[%s - %s] 로그인 실패 감지', basename(__FILE__), __FUNCTION__ ),
            array( 'username' => 'admin', 'ip' => $_SERVER['REMOTE_ADDR'], 'attempts' => 3 ),
            array( 'slt#security@on', 'slt#failed-login@on' )
        );
        
        // 태그 조합 7: performance
        do_action( 'sl_debug',
            sprintf( '[%s - %s] 성능 모니터링', basename(__FILE__), __FUNCTION__ ),
            array( 'page_load' => '2.3s', 'queries' => 45, 'memory_peak' => '32MB' ),
            array( 'slt#performance@on' )
        );
        
        // 태그 조합 8: database, critical
        do_action( 'sl_error',
            sprintf( '[%s - %s] 데이터베이스 연결 오류', basename(__FILE__), __FUNCTION__ ),
            array( 'error_code' => 'HY000', 'message' => 'Connection refused' ),
            array( 'slt#database@on', 'slt#critical@on' )
        );
        
        // 태그 조합 9: cart, checkout
        do_action( 'sl_info',
            sprintf( '[%s - %s] 장바구니 업데이트', basename(__FILE__), __FUNCTION__ ),
            array( 'cart_total' => '150,000원', 'items' => 5 ),
            array( 'slt#cart@on', 'slt#checkout@on' )
        );
        
        // 태그 조합 10: debug 전용
        do_action( 'sl_debug',
            sprintf( '[%s - %s] 디버그 정보', basename(__FILE__), __FUNCTION__ ),
            array( 'current_filter' => current_filter(), 'priority' => has_filter( current_filter() ) ),
            array( 'slt#debug@on' )
        );
        
        wp_die( '테스트 로그가 생성되었습니다. <a href="' . admin_url( 'admin.php?page=shoplic-logger' ) . '">로그 확인하기</a>' );
    }
} );

// 관리자 알림 표시
add_action( 'admin_notices', function() {
    if ( current_user_can( 'manage_options' ) ) {
        ?>
        <div class="notice notice-info">
            <p>쇼플릭 로거 테스트: <a href="<?php echo add_query_arg( 'test_shoplic_logger', '1' ); ?>" class="button button-secondary">테스트 로그 생성</a></p>
        </div>
        <?php
    }
} );