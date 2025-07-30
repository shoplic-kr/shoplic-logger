<?php
/**
 * 쇼플릭 로거 - 헬퍼 함수
 *
 * @package ShoplLogger
 * @subpackage Helpers
 */

// 직접 접근 방지
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * ====================================================================
 * 더 쉬운 사용을 위한 헬퍼 함수
 * ====================================================================
 * 이 함수들은 매번 \SL::을 입력하지 않고도 SL 로거를
 * 더 간편하게 사용할 수 있는 방법을 제공합니다.
 */

// 일반 로깅을 위한 헬퍼 함수
if ( ! function_exists( 'sl_log' ) ) {
    function sl_log( $message, $data = null, $tags = [], $disable = false ) {
        !$disable && \SL::log( $message, $data, $tags );
    }
}

// 오류 로깅을 위한 헬퍼 함수
if ( ! function_exists( 'sl_error' ) ) {
    function sl_error( $message, $data = null, $tags = [], $disable = false ) {
        !$disable && \SL::error( $message, $data, $tags );
    }
}

// 정보 로깅을 위한 헬퍼 함수
if ( ! function_exists( 'sl_info' ) ) {
    function sl_info( $message, $data = null, $tags = [], $disable = false ) {
        !$disable && \SL::info( $message, $data, $tags );
    }
}

// 디버그 로깅을 위한 헬퍼 함수
if ( ! function_exists( 'sl_debug' ) ) {
    function sl_debug( $message, $data = null, $tags = [], $disable = false ) {
        !$disable && \SL::debug( $message, $data, $tags );
    }
}

// 경고 로깅을 위한 헬퍼 함수
if ( ! function_exists( 'sl_warning' ) ) {
    function sl_warning( $message, $data = null, $tags = [], $disable = false ) {
        !$disable && \SL::warning( $message, $data, $tags );
    }
}
