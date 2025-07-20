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
        
        // 로컬라이즈된 데이터와 함께 인라인 스크립트 추가
        wp_add_inline_script( 'jquery', 'var sl_ajax = ' . json_encode( array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'sl_ajax_nonce' )
        ) ) . ';' );
        
        // 시스템 정보 복사 기능을 위한 스크립트 추가
        $current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'logs';
        if ( 'system-info' === $current_tab ) {
            wp_add_inline_script( 'jquery', "
                jQuery(document).ready(function($) {
                    // 시스템 정보를 클립보드에 복사
                    $('#sl-copy-sysinfo').on('click', function(e) {
                        e.preventDefault();
                        var copyText = document.getElementById('sl-sysinfo-text');
                        copyText.select();
                        copyText.setSelectionRange(0, 99999); // 모바일 기기를 위해
                        
                        try {
                            document.execCommand('copy');
                            $('.sl-copy-message').show().delay(2000).fadeOut();
                        } catch (err) {
                            alert('복사에 실패했습니다. 수동으로 선택하여 복사해주세요.');
                        }
                    });
                });
            " );
        }
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
                <a href="?page=shoplic-logger&tab=system-info" class="nav-tab <?php echo $current_tab === 'system-info' ? 'nav-tab-active' : ''; ?>">
                    <span title="워드프레스와 서버 환경에 대한 자세한 정보">시스템 정보</span>
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
                        
                    case 'system-info':
                        SL_SysInfo_Reporter::display();
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
        </style>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // 로그 비우기
            $(document).on('click', '.sl-clear-log', function() {
                var button = $(this);
                var card = button.closest('.sl-log-card');
                var plugin = button.data('plugin');
                var date = button.data('date');
                
                card.addClass('sl-loading');
                
                $.ajax({
                    url: sl_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'sl_clear_log',
                        plugin: plugin,
                        date: date,
                        nonce: sl_ajax.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            // 파일 내용이 비워졌으므로 로그를 새로고침
                            card.find('.sl-log-content').html('<p>로그가 없습니다.</p>');
                            card.find('.sl-log-size').text('0 B');
                        }
                        card.removeClass('sl-loading');
                    },
                    error: function(xhr, status, error) {
                        card.removeClass('sl-loading');
                        alert('비우기에 실패했습니다.');
                    }
                });
            });
            
            // 로그 파일 삭제
            $(document).on('click', '.sl-delete-file', function() {
                var button = $(this);
                var card = button.closest('.sl-log-card');
                var plugin = button.data('plugin');
                var date = button.data('date');
                
                card.addClass('sl-loading');
                
                $.ajax({
                    url: sl_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'sl_delete_file',
                        plugin: plugin,
                        date: date,
                        nonce: sl_ajax.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            // 파일이 삭제되었으므로 날짜 선택기에서 해당 날짜 제거
                            var option = card.find('.sl-log-date-select option[value="' + date + '"]');
                            option.remove();
                            
                            // 다른 날짜가 있으면 첫 번째 날짜로 자동 전환
                            var newDate = card.find('.sl-log-date-select option:first').val();
                            if (newDate) {
                                card.find('.sl-log-date-select').val(newDate).trigger('change');
                            } else {
                                // 모든 로그가 삭제되면 카드 제거
                                card.fadeOut(function() {
                                    card.remove();
                                });
                            }
                        } else {
                            card.removeClass('sl-loading');
                            alert('파일 삭제에 실패했습니다.');
                        }
                    },
                    error: function(xhr, status, error) {
                        card.removeClass('sl-loading');
                        alert('파일 삭제에 실패했습니다.');
                    }
                });
            });
            
            // 로그 복사
            $(document).on('click', '.sl-copy-log', function() {
                var button = $(this);
                var content = button.closest('.sl-log-card').find('.sl-log-content').text();
                
                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(content).then(function() {
                        var originalText = button.text();
                        button.text('✓ 복사됨');
                        setTimeout(function() {
                            button.text(originalText);
                        }, 2000);
                    });
                } else {
                    // <span title="주 기능이 실패했을 때 사용하는 대체 방법">폴백</span>
                    var textArea = $('<textarea>').val(content).css({
                        position: 'fixed',
                        left: '-999999px'
                    }).appendTo('body');
                    textArea[0].select();
                    document.execCommand('copy');
                    textArea.remove();
                    
                    var originalText = button.text();
                    button.text('✓ 복사됨');
                    setTimeout(function() {
                        button.text(originalText);
                    }, 2000);
                }
            });
            
            // 로그 새로고침
            $(document).on('click', '.sl-refresh-log', function() {
                var button = $(this);
                var card = button.closest('.sl-log-card');
                var plugin = button.data('plugin');
                var date = card.find('.sl-log-date-select').val();
                
                card.addClass('sl-loading');
                
                $.post(sl_ajax.ajax_url, {
                    action: 'sl_refresh_log',
                    plugin: plugin,
                    date: date,
                    nonce: sl_ajax.nonce
                }, function(response) {
                    if (response.success) {
                        card.find('.sl-log-content').html(response.data.content);
                        card.find('.sl-log-size').text(response.data.size);
                    }
                    card.removeClass('sl-loading');
                });
            });
            
            // 날짜 변경
            $(document).on('change', '.sl-log-date-select', function() {
                var select = $(this);
                var card = select.closest('.sl-log-card');
                var plugin = card.find('.sl-refresh-log').data('plugin');
                var date = select.val();
                
                card.addClass('sl-loading');
                
                $.post(sl_ajax.ajax_url, {
                    action: 'sl_refresh_log',
                    plugin: plugin,
                    date: date,
                    nonce: sl_ajax.nonce
                }, function(response) {
                    if (response.success) {
                        card.find('.sl-log-content').html(response.data.content);
                        card.find('.sl-log-size').text(response.data.size);
                        
                        // data-date 속성 업데이트
                        card.find('.sl-clear-log, .sl-copy-log, .sl-refresh-log, .sl-delete-file').attr('data-date', date);
                    }
                    card.removeClass('sl-loading');
                });
            });
            
            // debug.log 비우기
            $(document).on('click', '.sl-clear-debug-log', function() {
                var button = $(this);
                var card = button.closest('.sl-log-card');
                
                card.addClass('sl-loading');
                
                $.ajax({
                    url: sl_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'sl_clear_debug_log',
                        nonce: sl_ajax.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            card.find('.sl-debug-log-content').html('<p>debug.log 파일이 없습니다.</p>');
                            card.find('.sl-debug-log-size').text('0 B');
                        } else {
                            alert(response.data || '비우기에 실패했습니다.');
                        }
                        card.removeClass('sl-loading');
                    },
                    error: function(xhr, status, error) {
                        card.removeClass('sl-loading');
                        alert('비우기에 실패했습니다.');
                    }
                });
            });
            
            // debug.log 복사
            $(document).on('click', '.sl-copy-debug-log', function() {
                var button = $(this);
                var content = button.closest('.sl-log-card').find('.sl-debug-log-content').text();
                
                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(content).then(function() {
                        var originalText = button.text();
                        button.text('✓ 복사됨');
                        setTimeout(function() {
                            button.text(originalText);
                        }, 2000);
                    });
                } else {
                    // 폴백
                    var textArea = $('<textarea>').val(content).css({
                        position: 'fixed',
                        left: '-999999px'
                    }).appendTo('body');
                    textArea[0].select();
                    document.execCommand('copy');
                    textArea.remove();
                    
                    var originalText = button.text();
                    button.text('✓ 복사됨');
                    setTimeout(function() {
                        button.text(originalText);
                    }, 2000);
                }
            });
            
            // debug.log 새로고침
            $(document).on('click', '.sl-refresh-debug-log', function() {
                var button = $(this);
                var card = button.closest('.sl-log-card');
                
                card.addClass('sl-loading');
                
                $.post(sl_ajax.ajax_url, {
                    action: 'sl_refresh_debug_log',
                    nonce: sl_ajax.nonce
                }, function(response) {
                    if (response.success) {
                        card.find('.sl-debug-log-content').html(response.data.content);
                        card.find('.sl-debug-log-size').text(response.data.size);
                    }
                    card.removeClass('sl-loading');
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * 로그 카드 표시
     */
    private function display_log_card( $plugin ) {
        $log_files = $this->get_log_files( $plugin );
        $current_date = ! empty( $log_files ) ? $log_files[0]['date'] : date( 'Y-m-d' );
        $log_file = SL_LOG_DIR . '/' . $plugin . '/log-' . $current_date . '.log';
        
        ?>
        <div class="sl-log-card">
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
            
            <div class="sl-log-actions">
                <button type="button" class="button sl-clear-log" data-plugin="<?php echo esc_attr( $plugin ); ?>" data-date="<?php echo esc_attr( $current_date ); ?>">비우기</button>
                <button type="button" class="button sl-copy-log" data-plugin="<?php echo esc_attr( $plugin ); ?>" data-date="<?php echo esc_attr( $current_date ); ?>">복사</button>
                <button type="button" class="button sl-refresh-log" data-plugin="<?php echo esc_attr( $plugin ); ?>" data-date="<?php echo esc_attr( $current_date ); ?>">새로고침</button>
                <button type="button" class="button sl-delete-file" data-plugin="<?php echo esc_attr( $plugin ); ?>" data-date="<?php echo esc_attr( $current_date ); ?>">파일삭제</button>
            </div>
            
            <div class="sl-log-content">
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