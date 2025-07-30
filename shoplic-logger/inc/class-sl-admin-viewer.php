<?php
/**
 * 쇼플릭 로거 - 관리자 뷰어 클래스
 *
 * @package ShoplLogger
 * @subpackage Admin
 */

// 직접 접근 방지
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class SL_Admin_Viewer
 * 로그를 볼 수 있는 관리자 인터페이스 처리
 */
class SL_Admin_Viewer {
    
    /**
     * 생성자
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'handle_actions' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
    }
    
    /**
     * 관리자 메뉴 추가
     */
    public function add_admin_menu() {
        add_menu_page(
            '쇼플릭 로거',
            '쇼플릭 로거',
            'manage_options',
            'shoplic-logger',
            array( $this, 'render_page' ),
            'dashicons-media-text',
            80
        );
    }
    
    /**
     * 스크립트 등록
     */
    public function enqueue_scripts( $hook ) {
        if ( 'toplevel_page_shoplic-logger' !== $hook ) {
            return;
        }
        
        // JavaScript 파일 등록 및 로드
        wp_enqueue_script(
            'sl-admin-viewer',
            plugin_dir_url( dirname( __FILE__ ) ) . 'assets/js/admin-viewer.js',
            array( 'jquery' ),
            '1.0.0',
            true
        );
        
        // 로컬라이즈 데이터
        wp_localize_script( 'sl-admin-viewer', 'sl_ajax', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'sl_ajax_nonce' )
        ) );
    }
    
    /**
     * 관리자 페이지 렌더링
     */
    public function render_page() {
        // 현재 탭 가져오기
        $current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'logs';
        ?>
        <div class="wrap">
            <h1>쇼플릭 로거</h1>
            
            <nav class="nav-tab-wrapper">
                <a href="?page=shoplic-logger&tab=logs" class="nav-tab <?php echo $current_tab === 'logs' ? 'nav-tab-active' : ''; ?>">
                    <span title="애플리케이션에서 기록된 이벤트나 오류 메시지">로그</span>
                </a>
                <a href="?page=shoplic-logger&tab=manual" class="nav-tab <?php echo $current_tab === 'manual' ? 'nav-tab-active' : ''; ?>">
                    <span title="쇼플릭 로거 사용 방법 및 예제">사용법</span>
                </a>
                <a href="?page=shoplic-logger&tab=debug-settings" class="nav-tab <?php echo $current_tab === 'debug-settings' ? 'nav-tab-active' : ''; ?>">
                    <span title="개발 중 문제 해결을 위한 워드프레스 디버그 모드 설정">디버그 설정</span>
                </a>
            </nav>
            
            <div class="tab-content">
                <?php
                switch ( $current_tab ) {
                    case 'manual':
                        $this->render_manual_tab();
                        break;
                        
                    case 'debug-settings':
                        $debug_settings = new SL_Debug_Settings();
                        $debug_settings->render_page();
                        break;
                        
                    
                    case 'logs':
                    default:
                        $this->render_logs_tab();
                        break;
                }
                ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * 로그 탭 컨텐츠 렌더링
     */
    private function render_logs_tab() {
        ?>
        
        <?php
        // 사용 가능한 플러그인 가져오기
        $plugins = $this->get_logged_plugins();
        
        if ( ! empty( $plugins ) || file_exists( WP_CONTENT_DIR . '/debug.log' ) ) : ?>
            <div id="sl-logs-grid">
                <?php
                foreach ( $plugins as $plugin ) {
                    $this->display_log_card( $plugin );
                }
                
                // debug.log 카드를 마지막에 추가
                if ( file_exists( WP_CONTENT_DIR . '/debug.log' ) ) {
                    $this->display_debug_log_card();
                }
                ?>
            </div>
        <?php else : ?>
            <p>아직 로그가 없습니다.</p>
        <?php endif; ?>
        
        <style>
            #sl-logs-grid {
                display: grid !important;
                grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
                gap: 20px !important;
                margin-top: 20px !important;
            }
            .sl-log-card {
                background: #fff;
                border: 1px solid #ccd0d4;
                padding: 15px;
                border-radius: 4px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                width: 100%;
                box-sizing: border-box;
            }
            .sl-log-card h3 {
                margin-top: 0;
                margin-bottom: 10px;
                font-size: 16px;
                color: #23282d;
            }
            .sl-log-actions {
                display: flex;
                gap: 5px;
                margin-bottom: 10px;
            }
            .sl-log-actions button {
                flex: 1;
                font-size: 12px;
                padding: 4px 8px;
            }
            .sl-log-content {
                background: #f1f1f1;
                border: 1px solid #e5e5e5;
                padding: 10px;
                height: 300px;
                overflow: auto;
                font-family: monospace;
                font-size: 11px;
                white-space: pre-wrap;
                word-wrap: break-word;
            }
            .sl-log-date-selector {
                margin-bottom: 10px;
            }
            .sl-log-date-selector select {
                width: 100%;
                font-size: 13px;
            }
            .sl-loading {
                opacity: 0.5;
                pointer-events: none;
            }
            .sl-tag-filter-selector {
                margin-bottom: 10px;
            }
            .sl-tag-filter-selector select {
                width: 100%;
                font-size: 13px;
                padding: 5px 8px;
                border: 1px solid #8c8f94;
                border-radius: 4px;
                background-color: #fff;
                cursor: pointer;
            }
            .sl-tag-filter-selector select:focus {
                border-color: #007cba;
                box-shadow: 0 0 0 1px #007cba;
                outline: 2px solid transparent;
            }
            .sl-filter-info {
                background: #f0f8ff;
                border: 1px solid #007cba;
                color: #0073aa;
            }
            .sl-tag {
                transition: background-color 0.2s ease;
            }
            .sl-tag:hover {
                opacity: 0.8;
                transform: scale(1.05);
            }
        </style>
        <?php
    }
    
    /**
     * 로그 카드 표시
     */
    private function display_log_card( $plugin ) {
        $log_files = $this->get_log_files( $plugin );
        $current_date = ! empty( $log_files ) ? $log_files[0]['date'] : date( 'Y-m-d' );
        $log_file = SL_LOG_DIR . '/' . $plugin . '/log-' . $current_date . '.log';
        
        // 현재 로그 파일에서 태그 추출
        $available_tags = array();
        if ( file_exists( $log_file ) ) {
            $content = file_get_contents( $log_file );
            preg_match_all( '/\[TAGS: ([^\]]+)\]/', $content, $matches );
            if ( ! empty( $matches[1] ) ) {
                foreach ( $matches[1] as $tag_string ) {
                    $tags = explode( ', ', $tag_string );
                    foreach ( $tags as $tag ) {
                        $tag = trim( $tag );
                        if ( ! empty( $tag ) ) {
                            $available_tags[ $tag ] = true;
                        }
                    }
                }
            }
        }
        $available_tags = array_keys( $available_tags );
        sort( $available_tags );
        
        ?>
        <div class="sl-log-card" data-plugin="<?php echo esc_attr( $plugin ); ?>">
            <h3><?php echo esc_html( $plugin ); ?></h3>
            
            <div class="sl-log-date-selector">
                <select class="sl-log-date-select">
                    <?php foreach ( $log_files as $file ) : ?>
                        <option value="<?php echo esc_attr( $file['date'] ); ?>" <?php selected( $current_date, $file['date'] ); ?>>
                            <?php echo esc_html( $file['date'] ); ?> (<?php echo esc_html( $file['size'] ); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <?php if ( ! empty( $available_tags ) ) : ?>
            <div class="sl-tag-filter-selector">
                <select class="sl-tag-filter-select">
                    <option value="">모든 태그 보기</option>
                    <?php foreach ( $available_tags as $tag ) : ?>
                        <option value="<?php echo esc_attr( $tag ); ?>"><?php echo esc_html( $tag ); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            
            <div class="sl-log-actions">
                <button type="button" class="button sl-clear-log" data-plugin="<?php echo esc_attr( $plugin ); ?>" data-date="<?php echo esc_attr( $current_date ); ?>">비우기</button>
                <button type="button" class="button sl-copy-log" data-plugin="<?php echo esc_attr( $plugin ); ?>" data-date="<?php echo esc_attr( $current_date ); ?>">복사</button>
                <button type="button" class="button sl-refresh-log" data-plugin="<?php echo esc_attr( $plugin ); ?>" data-date="<?php echo esc_attr( $current_date ); ?>">새로고침</button>
                <button type="button" class="button sl-delete-file" data-plugin="<?php echo esc_attr( $plugin ); ?>" data-date="<?php echo esc_attr( $current_date ); ?>">파일삭제</button>
            </div>
            
            <div class="sl-log-content" data-original-content="">
                <?php
                if ( file_exists( $log_file ) ) {
                    $content = file_get_contents( $log_file );
                    echo $this->format_log_content( $content );
                } else {
                    echo '<p>로그가 없습니다.</p>';
                }
                ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * debug.log 카드 표시
     */
    private function display_debug_log_card() {
        $debug_log_file = WP_CONTENT_DIR . '/debug.log';
        $file_size = file_exists( $debug_log_file ) ? size_format( filesize( $debug_log_file ) ) : '0 B';
        ?>
        <div class="sl-log-card">
            <h3>WordPress Debug Log</h3>
            
            <div class="sl-log-date-selector">
                <p style="margin: 5px 0; color: #666;">파일 크기: <span class="sl-debug-log-size"><?php echo esc_html( $file_size ); ?></span></p>
            </div>
            
            <div class="sl-log-actions">
                <button type="button" class="button sl-clear-debug-log">비우기</button>
                <button type="button" class="button sl-copy-debug-log">복사</button>
                <button type="button" class="button sl-refresh-debug-log">새로고침</button>
            </div>
            
            <div class="sl-log-content sl-debug-log-content">
                <?php
                if ( file_exists( $debug_log_file ) ) {
                    // 파일 크기가 너무 크면 마지막 부분만 읽기
                    $max_size = 1024 * 1024; // 1MB
                    $file_size_bytes = filesize( $debug_log_file );
                    
                    if ( $file_size_bytes > $max_size ) {
                        // 파일의 마지막 1MB만 읽기
                        $handle = fopen( $debug_log_file, 'r' );
                        fseek( $handle, -$max_size, SEEK_END );
                        $content = fread( $handle, $max_size );
                        fclose( $handle );
                        
                        // 첫 줄이 잘릴 수 있으므로 첫 개행 문자 이후부터 표시
                        $content = substr( $content, strpos( $content, "\n" ) + 1 );
                        echo '<p style="color: #ff6b6b; margin-bottom: 10px;">⚠️ 파일이 너무 커서 마지막 1MB만 표시합니다.</p>';
                    } else {
                        $content = file_get_contents( $debug_log_file );
                    }
                    
                    echo $this->format_log_content( $content );
                } else {
                    echo '<p>debug.log 파일이 없습니다.</p>';
                }
                ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * 플러그인의 로그 파일 가져오기
     */
    private function get_log_files( $plugin ) {
        $files = array();
        $dir = SL_LOG_DIR . '/' . $plugin;
        
        if ( is_dir( $dir ) ) {
            $log_files = glob( $dir . '/log-*.log' );
            rsort( $log_files ); // 최신 순으로 정렬
            
            foreach ( $log_files as $file ) {
                if ( preg_match( '/log-(\d{4}-\d{2}-\d{2})\.log$/', $file, $matches ) ) {
                    $files[] = array(
                        'date' => $matches[1],
                        'size' => size_format( filesize( $file ) ),
                        'path' => $file
                    );
                }
            }
        }
        
        return $files;
    }
    
    /**
     * 로그 내용을 색상으로 형식화
     */
    public function format_log_content( $content ) {
        // 먼저 HTML 이스케이프
        $content = esc_html( $content );
        
        // 로그 레벨별 색상 코드
        $content = preg_replace( '/\[ERROR\]/', '<span style="color: #dc3545;">[ERROR]</span>', $content );
        $content = preg_replace( '/\[WARNING\]/', '<span style="color: #ffc107;">[WARNING]</span>', $content );
        $content = preg_replace( '/\[INFO\]/', '<span style="color: #17a2b8;">[INFO]</span>', $content );
        $content = preg_replace( '/\[DEBUG\]/', '<span style="color: #6c757d;">[DEBUG]</span>', $content );
        $content = preg_replace( '/\[LOG\]/', '<span style="color: #28a745;">[LOG]</span>', $content );
        
        // 태그를 클릭 가능한 배지로 형식화
        $content = preg_replace_callback(
            '/\[TAGS: ([^\]]+)\]/',
            function( $matches ) {
                $tags = explode( ', ', $matches[1] );
                $tag_html = '<span class="sl-tags">';
                foreach ( $tags as $tag ) {
                    $tag = trim( $tag );
                    $tag_html .= '<span class="sl-tag" data-tag="' . esc_attr( $tag ) . '" style="background-color: #007cba; color: white; padding: 2px 6px; margin: 0 2px; border-radius: 3px; cursor: pointer; font-size: 11px;">' . esc_html( $tag ) . '</span>';
                }
                $tag_html .= '</span>';
                return $tag_html;
            },
            $content
        );
        
        return $content;
    }
    
    /**
     * 로그가 있는 플러그인 목록 가져오기
     */
    private function get_logged_plugins() {
        $plugins = array();
        
        if ( is_dir( SL_LOG_DIR ) ) {
            $dirs = glob( SL_LOG_DIR . '/*', GLOB_ONLYDIR );
            foreach ( $dirs as $dir ) {
                $plugins[] = basename( $dir );
            }
        }
        
        return $plugins;
    }
    
    /**
     * 액션 URL 가져오기
     */
    private function get_action_url( $action, $plugin, $date ) {
        return wp_nonce_url(
            add_query_arg( array(
                'page' => 'shoplic-logger',
                'action' => $action,
                'plugin' => $plugin,
                'date' => $date
            ), admin_url( 'admin.php' ) ),
            'sl_' . $action
        );
    }
    
    /**
     * 액션 처리
     */
    public function handle_actions() {
        if ( ! isset( $_GET['action'] ) || ! isset( $_GET['_wpnonce'] ) ) {
            return;
        }
        
        $action = sanitize_text_field( $_GET['action'] );
        $plugin = isset( $_GET['plugin'] ) ? sanitize_text_field( $_GET['plugin'] ) : '';
        $date = isset( $_GET['date'] ) ? sanitize_text_field( $_GET['date'] ) : '';
        
        if ( ! $plugin || ! $date ) {
            return;
        }
        
        $log_file = SL_LOG_DIR . '/' . $plugin . '/log-' . $date . '.log';
        
        switch ( $action ) {
            case 'download':
                if ( wp_verify_nonce( $_GET['_wpnonce'], 'sl_download' ) && file_exists( $log_file ) ) {
                    header( 'Content-Type: text/plain' );
                    header( 'Content-Disposition: attachment; filename="' . $plugin . '-' . $date . '.log"' );
                    header( 'Content-Length: ' . filesize( $log_file ) );
                    readfile( $log_file );
                    exit;
                }
                break;
                
            case 'clear':
                if ( wp_verify_nonce( $_GET['_wpnonce'], 'sl_clear' ) && file_exists( $log_file ) ) {
                    unlink( $log_file );
                    wp_redirect( add_query_arg( array(
                        'page' => 'shoplic-logger',
                        'plugin' => $plugin,
                        'cleared' => 1
                    ), admin_url( 'admin.php' ) ) );
                    exit;
                }
                break;
                
            case 'refresh':
                if ( wp_verify_nonce( $_GET['_wpnonce'], 'sl_refresh' ) ) {
                    wp_redirect( add_query_arg( array(
                        'page' => 'shoplic-logger',
                        'plugin' => $plugin,
                        'date' => $date
                    ), admin_url( 'admin.php' ) ) );
                    exit;
                }
                break;
        }
    }
    
    /**
     * 메뉴얼 탭 컨텐츠 렌더링
     */
    private function render_manual_tab() {
        ?>
        <div class="wrap">
            <div style="max-width: 800px;">
                <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; margin: 20px 0;">
                    <h2 style="margin-top: 0;">쇼플릭 로거 사용법</h2>
                    
                    <h3>1. 기본 사용법 (헬퍼 함수)</h3>
                    <pre style="background: #f1f1f1; padding: 15px; overflow-x: auto;">
// 기본 로깅
sl_log('일반 로그 메시지');
sl_error('에러 메시지');
sl_info('정보 메시지');
sl_debug('디버그 메시지');  // WP_DEBUG가 true일 때만 기록
sl_warning('경고 메시지');

// 조건부 로깅 비활성화 (disable 파라미터 사용)
$is_production = defined('WP_ENV') && WP_ENV === 'production';
sl_log('개발 환경 로그', null, $is_production); // 프로덕션에서는 로깅 안함
sl_error('에러 발생', $error_details, !$has_error); // 에러가 있을 때만 로깅</pre>
                    
                    <h3>2. 데이터와 함께 로깅</h3>
                    <pre style="background: #f1f1f1; padding: 15px; overflow-x: auto;">
// 사용자 정보 로깅
$user_data = get_userdata($user_id);
sl_log('사용자 정보 조회', $user_data);

// 에러 상황 로깅
try {
    // 코드 실행
} catch (Exception $e) {
    sl_error('결제 처리 실패', [
        'order_id' => $order_id,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}

// 옵션값 디버깅
sl_debug('플러그인 설정 확인', get_option('my_plugin_settings'));</pre>
                    
                    <h3>3. 컨텍스트 정보 포함 (권장)</h3>
                    <pre style="background: #f1f1f1; padding: 15px; overflow-x: auto;">
// 파일명과 메서드명을 포함하여 로그 위치를 명확히 표시
sl_info(
    sprintf('[%s - %s] 주문 생성 완료', basename(__FILE__), __METHOD__),
    ['order_id' => $order_id, 'total' => $total]
);

// 클래스 메서드에서 사용
sl_error(
    sprintf('[%s - %s] 재고 부족', basename(__FILE__), __METHOD__),
    ['product_id' => $product_id, 'requested' => $quantity]
);

// 조건부 로깅과 함께 사용
$is_test_mode = get_option('payment_test_mode');
sl_info(
    sprintf('[%s - %s] 결제 처리 완료', basename(__FILE__), __METHOD__),
    ['order_id' => $order_id, 'amount' => $amount],
    !$is_test_mode // 테스트 모드가 아닐 때만 로깅
);</pre>
                    
                    <h3>4. 조건부 로깅 비활성화 ($disable 파라미터)</h3>
                    <p>모든 헬퍼 함수는 세 번째 파라미터로 <code>$disable</code>를 받습니다. 이 값이 <code>true</code>일 때 로깅이 비활성화됩니다.</p>
                    <pre style="background: #f1f1f1; padding: 15px; overflow-x: auto;">
// 함수 시그니처
sl_log($message, $data = null, $disable = false);

// 사용 예제
$is_production = WP_ENV === 'production';
sl_debug('개발용 디버그 정보', $debug_data, $is_production); // 프로덕션에서는 로깅 안함

$should_skip_log = !current_user_can('manage_options');
sl_info('관리자 활동', $admin_action, $should_skip_log); // 관리자만 로깅

$no_error = empty($error_message);
sl_error('에러 정보', $error_details, $no_error); // 에러가 있을 때만 로깅</pre>
                    
                    <h3>5. 주요 기능</h3>
                    <ul style="line-height: 1.8;">
                        <li><strong>자동 분류:</strong> 플러그인/테마별로 로그가 자동으로 분류됩니다</li>
                        <li><strong>날짜별 파일:</strong> 매일 새로운 로그 파일이 생성됩니다</li>
                        <li><strong>자동 정리:</strong> 7일 이상 된 로그는 자동으로 삭제됩니다</li>
                        <li><strong>레벨별 구분:</strong> LOG, ERROR, INFO, DEBUG, WARNING 레벨 지원</li>
                        <li><strong>관리 기능:</strong> 로그 보기, 다운로드, 복사, 삭제 기능 제공</li>
                        <li><strong>보안:</strong> 로그 디렉토리는 웹에서 직접 접근 불가</li>
                        <li><strong>조건부 로깅:</strong> $disable 파라미터로 상황에 따른 로깅 제어</li>
                    </ul>
                    
                    <h3>6. 로그 위치</h3>
                    <p><code>/wp-content/sl-logs/[플러그인명]/log-YYYY-MM-DD.log</code></p>
                    
                    <h3>7. 클래스 직접 사용 (대체 방법)</h3>
                    <pre style="background: #f1f1f1; padding: 15px; overflow-x: auto;">
// 헬퍼 함수 대신 클래스를 직접 사용할 수도 있습니다
\SL::log('일반 로그 메시지');
\SL::error('에러 메시지');
\SL::info('정보 메시지');
\SL::debug('디버그 메시지');
\SL::warning('경고 메시지');</pre>
                </div>
            </div>
        </div>
        <?php
    }
}