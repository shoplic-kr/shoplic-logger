<?php
/**
 * 쇼플릭 로거 - <span title="비동기 자바스크립트 및 XML. 페이지를 새로고침하지 않고 서버와 데이터를 주고받는 기술">AJAX</span> 핸들러 클래스
 *
 * @package ShoplLogger
 * @subpackage Ajax
 */

// 직접 접근 방지
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class SL_Ajax_Handler
 * 쇼플릭 로거의 모든 <span title="비동기 자바스크립트 및 XML. 페이지를 새로고침하지 않고 서버와 데이터를 주고받는 기술">AJAX</span> 요청 처리
 */
class SL_Ajax_Handler {
    
    /**
     * 생성자
     */
    public function __construct() {
        // 로그 관련 AJAX 핸들러
        add_action( 'wp_ajax_sl_clear_log', array( $this, 'ajax_clear_log' ) );
        add_action( 'wp_ajax_sl_delete_file', array( $this, 'ajax_delete_file' ) );
        add_action( 'wp_ajax_sl_copy_log', array( $this, 'ajax_copy_log' ) );
        add_action( 'wp_ajax_sl_refresh_log', array( $this, 'ajax_refresh_log' ) );
        
        // 디버그 설정 AJAX 핸들러
        add_action( 'wp_ajax_sl_save_debug_settings', array( $this, 'ajax_save_debug_settings' ) );
        add_action( 'wp_ajax_sl_download_wp_config', array( $this, 'ajax_download_wp_config' ) );
        
        // debug.log AJAX 핸들러
        add_action( 'wp_ajax_sl_clear_debug_log', array( $this, 'ajax_clear_debug_log' ) );
        add_action( 'wp_ajax_sl_refresh_debug_log', array( $this, 'ajax_refresh_debug_log' ) );
    }
    
    /**
     * 로그 비우기를 위한 AJAX 핸들러
     */
    public function ajax_clear_log() {
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'sl_ajax_nonce' ) ) {
            wp_send_json_error( 'Nonce verification failed' );
            return;
        }
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Permission denied' );
            return;
        }
        
        $plugin = isset( $_POST['plugin'] ) ? sanitize_text_field( $_POST['plugin'] ) : '';
        $date = isset( $_POST['date'] ) ? sanitize_text_field( $_POST['date'] ) : '';
        
        if ( empty( $plugin ) || empty( $date ) ) {
            wp_send_json_error( 'Missing parameters' );
            return;
        }
        
        $log_file = SL_LOG_DIR . '/' . $plugin . '/log-' . $date . '.log';
        
        if ( file_exists( $log_file ) ) {
            // 파일 내용을 비움
            if ( file_put_contents( $log_file, '' ) !== false ) {
                wp_send_json_success();
            } else {
                wp_send_json_error( 'Failed to clear file content' );
            }
        } else {
            wp_send_json_error( 'File not found' );
        }
    }
    
    /**
     * 로그 파일 삭제를 위한 AJAX 핸들러
     */
    public function ajax_delete_file() {
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'sl_ajax_nonce' ) ) {
            wp_send_json_error( 'Nonce verification failed' );
            return;
        }
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Permission denied' );
            return;
        }
        
        $plugin = isset( $_POST['plugin'] ) ? sanitize_text_field( $_POST['plugin'] ) : '';
        $date = isset( $_POST['date'] ) ? sanitize_text_field( $_POST['date'] ) : '';
        
        if ( empty( $plugin ) || empty( $date ) ) {
            wp_send_json_error( 'Missing parameters' );
            return;
        }
        
        $log_file = SL_LOG_DIR . '/' . $plugin . '/log-' . $date . '.log';
        
        if ( file_exists( $log_file ) ) {
            // 파일을 완전히 삭제
            if ( unlink( $log_file ) ) {
                wp_send_json_success();
            } else {
                wp_send_json_error( 'Failed to delete file' );
            }
        } else {
            wp_send_json_error( 'File not found' );
        }
    }
    
    /**
     * 로그 복사를 위한 AJAX 핸들러
     */
    public function ajax_copy_log() {
        if ( ! wp_verify_nonce( $_POST['nonce'], 'sl_ajax_nonce' ) || ! current_user_can( 'manage_options' ) ) {
            wp_die();
        }
        
        $plugin = sanitize_text_field( $_POST['plugin'] );
        $date = sanitize_text_field( $_POST['date'] );
        $log_file = SL_LOG_DIR . '/' . $plugin . '/log-' . $date . '.log';
        
        if ( file_exists( $log_file ) ) {
            $content = file_get_contents( $log_file );
            wp_send_json_success( array( 'content' => $content ) );
        } else {
            wp_send_json_error();
        }
    }
    
    /**
     * 로그 새로고침을 위한 AJAX 핸들러
     */
    public function ajax_refresh_log() {
        if ( ! wp_verify_nonce( $_POST['nonce'], 'sl_ajax_nonce' ) || ! current_user_can( 'manage_options' ) ) {
            wp_die();
        }
        
        $plugin = sanitize_text_field( $_POST['plugin'] );
        $date = sanitize_text_field( $_POST['date'] );
        $log_file = SL_LOG_DIR . '/' . $plugin . '/log-' . $date . '.log';
        
        $admin_viewer = new SL_Admin_Viewer();
        
        if ( file_exists( $log_file ) ) {
            $content = file_get_contents( $log_file );
            $formatted_content = $admin_viewer->format_log_content( $content );
            $size = size_format( filesize( $log_file ) );
            
            wp_send_json_success( array(
                'content' => $formatted_content,
                'size' => $size
            ) );
        } else {
            wp_send_json_success( array(
                'content' => '<p>로그가 없습니다.</p>',
                'size' => '0 B'
            ) );
        }
    }
    
    /**
     * 디버그 설정 저장을 위한 AJAX 핸들러
     */
    public function ajax_save_debug_settings() {
        if ( ! wp_verify_nonce( $_POST['nonce'], 'sl_ajax_nonce' ) || ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Permission denied' );
            return;
        }
        
        try {
            require_once SL_PLUGIN_DIR . '/vendor/WPConfigTransformer.php';
            
            $config_path = ABSPATH . 'wp-config.php';
            if ( ! file_exists( $config_path ) ) {
                wp_send_json_error( 'wp-config.php 파일을 찾을 수 없습니다.' );
                return;
            }
            
            if ( ! is_writable( $config_path ) ) {
                wp_send_json_error( 'wp-config.php 파일에 쓰기 권한이 없습니다.' );
                return;
            }
            
            $config_transformer = new WPConfigTransformer( $config_path );
            
            // POST에서 디버그 설정 가져오기
            $settings = array(
                'WP_DEBUG' => isset( $_POST['wp_debug'] ) && $_POST['wp_debug'] === '1',
                'WP_DEBUG_LOG' => isset( $_POST['wp_debug_log'] ) && $_POST['wp_debug_log'] === '1',
                'WP_DEBUG_DISPLAY' => isset( $_POST['wp_debug_display'] ) && $_POST['wp_debug_display'] === '1',
                'SCRIPT_DEBUG' => isset( $_POST['script_debug'] ) && $_POST['script_debug'] === '1',
                'SAVEQUERIES' => isset( $_POST['savequeries'] ) && $_POST['savequeries'] === '1',
                'WP_DISABLE_FATAL_ERROR_HANDLER' => isset( $_POST['wp_disable_fatal_error_handler'] ) && $_POST['wp_disable_fatal_error_handler'] === '1'
            );
            
            // 각 상수 업데이트
            foreach ( $settings as $constant => $value ) {
                $config_transformer->update(
                    'constant',
                    $constant,
                    $value ? 'true' : 'false',
                    array(
                        'raw' => true,
                        'normalize' => true,
                        'add' => true
                    )
                );
            }
            
            wp_send_json_success( array(
                'message' => '설정이 성공적으로 저장되었습니다.',
                'settings' => $settings
            ) );
            
        } catch ( Exception $e ) {
            wp_send_json_error( '설정 저장 중 오류가 발생했습니다: ' . $e->getMessage() );
        }
    }
    
    /**
     * <span title="워드프레스의 주요 설정 파일">wp-config.php</span> 백업 다운로드를 위한 AJAX 핸들러
     */
    public function ajax_download_wp_config() {
        if ( ! wp_verify_nonce( $_GET['nonce'], 'sl_ajax_nonce' ) || ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Permission denied' );
        }
        
        $config_path = ABSPATH . 'wp-config.php';
        
        if ( ! file_exists( $config_path ) ) {
            wp_die( 'wp-config.php file not found' );
        }
        
        // 다운로드를 위한 <span title="HTTP 요청이나 응답에 포함되는 추가 정보">헤더</span> 설정
        header( 'Content-Type: text/plain' );
        header( 'Content-Disposition: attachment; filename="wp-config-backup-' . date( 'Y-m-d-H-i-s' ) . '.php"' );
        header( 'Content-Length: ' . filesize( $config_path ) );
        header( 'Pragma: no-cache' );
        header( 'Expires: 0' );
        
        readfile( $config_path );
        exit;
    }
    
    /**
     * debug.log 비우기를 위한 AJAX 핸들러
     */
    public function ajax_clear_debug_log() {
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'sl_ajax_nonce' ) ) {
            wp_send_json_error( 'Nonce verification failed' );
            return;
        }
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Permission denied' );
            return;
        }
        
        $debug_log_file = WP_CONTENT_DIR . '/debug.log';
        
        if ( file_exists( $debug_log_file ) ) {
            // 파일 내용을 비움
            if ( file_put_contents( $debug_log_file, '' ) !== false ) {
                wp_send_json_success();
            } else {
                wp_send_json_error( 'Failed to clear debug.log' );
            }
        } else {
            // 파일이 없어도 성공으로 처리
            wp_send_json_success();
        }
    }
    
    /**
     * debug.log 새로고침을 위한 AJAX 핸들러
     */
    public function ajax_refresh_debug_log() {
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'sl_ajax_nonce' ) ) {
            wp_send_json_error( 'Nonce verification failed' );
            return;
        }
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Permission denied' );
            return;
        }
        
        $debug_log_file = WP_CONTENT_DIR . '/debug.log';
        $admin_viewer = new SL_Admin_Viewer();
        
        if ( file_exists( $debug_log_file ) ) {
            // 파일 크기 확인
            $max_size = 1024 * 1024; // 1MB
            $file_size_bytes = filesize( $debug_log_file );
            $file_size = size_format( $file_size_bytes );
            
            if ( $file_size_bytes > $max_size ) {
                // 파일의 마지막 1MB만 읽기
                $handle = fopen( $debug_log_file, 'r' );
                fseek( $handle, -$max_size, SEEK_END );
                $content = fread( $handle, $max_size );
                fclose( $handle );
                
                // 첫 줄이 잘릴 수 있으므로 첫 개행 문자 이후부터 표시
                $content = substr( $content, strpos( $content, "\n" ) + 1 );
                $formatted_content = '<p style="color: #ff6b6b; margin-bottom: 10px;">⚠️ 파일이 너무 커서 마지막 1MB만 표시합니다.</p>';
                $formatted_content .= $admin_viewer->format_log_content( $content );
            } else {
                $content = file_get_contents( $debug_log_file );
                $formatted_content = $admin_viewer->format_log_content( $content );
            }
            
            wp_send_json_success( array(
                'content' => $formatted_content,
                'size' => $file_size
            ) );
        } else {
            wp_send_json_success( array(
                'content' => '<p>debug.log 파일이 없습니다.</p>',
                'size' => '0 B'
            ) );
        }
    }
}