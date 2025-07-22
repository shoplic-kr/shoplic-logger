# Shoplic Logger - WordPress 디버깅 및 로깅 솔루션

Shoplic Logger는 WordPress 애플리케이션의 디버깅과 모니터링을 위한 강력한 파일 기반 로깅 솔루션입니다. 플러그인/테마별 자동 분류, 시각적 관리자 인터페이스, AI 친화적인 로그 형식을 제공합니다.

## 🚀 주요 특징

### 1. **자동 소스 감지 및 분류**
로그가 발생한 위치를 자동으로 감지하여 플러그인/테마별로 분류합니다:
- 일반 플러그인: `/wp-content/sl-logs/plugin-name/`
- MU 플러그인: `mu-` 접두사로 분류
- 테마: `theme-` 접두사로 분류
- WordPress 코어, 기타 위치별 자동 정리

### 2. **간편한 헬퍼 함수**
```php
sl_log('일반 로그 메시지');
sl_error('에러가 발생했습니다', $error_data);
sl_info('정보성 메시지', $info_data);
sl_warning('경고 메시지');
sl_debug('디버그 정보'); // WP_DEBUG가 true일 때만 작동
```

### 3. **시각적 관리자 인터페이스**
WordPress 관리자 메뉴 "쇼플릭 로거"에서 제공:
- 로그 뷰어: 날짜별 필터링, 실시간 로그 확인
- 로그 관리: 클리어, 삭제, 클립보드 복사
- 디버그 설정: wp-config.php 디버그 상수 직접 관리

### 4. **조건부 로깅**
환경에 따른 선택적 로깅 지원:
```php
// 프로덕션에서는 로깅 비활성화
$is_production = defined('WP_ENV') && WP_ENV === 'production';
sl_log('개발 환경 전용 로그', null, $is_production);

// 에러가 있을 때만 로깅
sl_error('에러 발생', $error_details, empty($error));
```

### 5. **액션 기반 사용법** (플러그인 의존성 제거)
플러그인이 비활성화되어도 에러가 발생하지 않는 안전한 방법:
```php
// 직접 호출 대신 do_action 사용
do_action('sl_log', '일반 로그 메시지');
do_action('sl_error', '에러가 발생했습니다', $error_data);
do_action('sl_info', '정보성 메시지', $info_data);
do_action('sl_warning', '경고 메시지');
do_action('sl_debug', '디버그 정보');
```

## 📥 설치 방법

1. `shoplic-logger` 폴더를 `wp-content/mu-plugins/` 디렉토리에 복사
2. `shoplic-logger.php` 파일을 `wp-content/mu-plugins/` 루트에 복사
3. 설치 완료! (MU 플러그인은 자동 활성화)

## 📖 사용법

### 두 가지 사용 방법

#### 방법 1: 직접 함수 호출 (MU 플러그인에서 안전)
```php
// 간단한 메시지
sl_log('프로세스가 시작되었습니다');

// 데이터와 함께 로깅
sl_log('사용자 등록', ['user_id' => 123, 'email' => 'user@example.com']);
```

#### 방법 2: 액션 사용 (플러그인 의존성 없음)
```php
// 플러그인이 없어도 에러가 발생하지 않음
do_action('sl_log', '프로세스가 시작되었습니다');

// 데이터와 함께 로깅
do_action('sl_log', '사용자 등록', ['user_id' => 123, 'email' => 'user@example.com']);
```

### 언제 어떤 방법을 사용할까?

- **직접 함수 호출**: MU 플러그인, 또는 Shoplic Logger가 확실히 설치된 환경
- **액션 사용**: 테마, 일반 플러그인, 또는 Shoplic Logger 설치 여부가 불확실한 환경

### 컨텍스트 정보 포함 (권장)
```php
// 직접 호출 방식
sl_log(
    sprintf('[%s - %s] 주문 처리 완료', basename(__FILE__), __METHOD__),
    ['order_id' => $order_id, 'total' => $total]
);

// 액션 방식
do_action('sl_log',
    sprintf('[%s - %s] 주문 처리 완료', basename(__FILE__), __METHOD__),
    ['order_id' => $order_id, 'total' => $total]
);
```

### 로그 레벨별 사용
```php
// 직접 호출 방식
sl_error(
    sprintf('[%s - %s] 결제 실패', basename(__FILE__), __METHOD__),
    ['order_id' => 456, 'error' => '카드 승인 거부']
);
sl_info('새 주문 생성', ['order_id' => 789]);
sl_warning('재고 부족 임박', ['product_id' => 101, 'stock' => 5]);
sl_debug('메모리 사용량', ['memory' => memory_get_usage()]);

// 액션 방식
do_action('sl_error',
    sprintf('[%s - %s] 결제 실패', basename(__FILE__), __METHOD__),
    ['order_id' => 456, 'error' => '카드 승인 거부']
);
do_action('sl_info', '새 주문 생성', ['order_id' => 789]);
do_action('sl_warning', '재고 부족 임박', ['product_id' => 101, 'stock' => 5]);
do_action('sl_debug', '메모리 사용량', ['memory' => memory_get_usage()]);
```

## 🎯 실제 사용 예제

### WooCommerce 주문 처리
```php
add_action('woocommerce_new_order', function($order_id) {
    $order = wc_get_order($order_id);
    
    // MU 플러그인 내부에서는 직접 호출
    sl_info(
        sprintf('[%s - %s] 새 주문 접수', basename(__FILE__), __FUNCTION__),
        [
            'order_id' => $order_id,
            'total' => $order->get_total(),
            'customer' => $order->get_billing_email()
        ]
    );
});
```

### 일반 플러그인에서 안전한 사용
```php
// 플러그인이 비활성화되어도 에러가 발생하지 않음
add_action('init', function() {
    do_action('sl_log', 
        sprintf('[%s - %s] 플러그인 초기화', basename(__FILE__), __FUNCTION__)
    );
});

// 조건부 로깅과 함께 사용
add_filter('the_content', function($content) {
    $is_production = defined('WP_ENV') && WP_ENV === 'production';
    
    do_action('sl_debug',
        sprintf('[%s - %s] 콘텐츠 필터 실행', basename(__FILE__), __FUNCTION__),
        ['post_id' => get_the_ID()],
        $is_production // 프로덕션에서는 로깅하지 않음
    );
    
    return $content;
});
```

### API 통신 로깅
```php
$response = wp_remote_get($api_url);

if (is_wp_error($response)) {
    sl_error(
        sprintf('[%s - %s] API 호출 실패', basename(__FILE__), __METHOD__),
        [
            'url' => $api_url,
            'error' => $response->get_error_message(),
            'error_code' => $response->get_error_code()
        ]
    );
} else {
    sl_debug(
        sprintf('[%s - %s] API 응답', basename(__FILE__), __METHOD__),
        [
            'status_code' => wp_remote_retrieve_response_code($response),
            'body' => wp_remote_retrieve_body($response)
        ]
    );
}
```

### 성능 모니터링
```php
$start_time = microtime(true);

// 복잡한 작업 수행
do_complex_operation();

$execution_time = microtime(true) - $start_time;

// 실행 시간이 1초 이상일 때만 경고
$is_fast = $execution_time < 1.0;
sl_warning(
    sprintf('[%s - %s] 느린 작업 감지', basename(__FILE__), __METHOD__),
    [
        'execution_time' => $execution_time,
        'memory_peak' => memory_get_peak_usage(true) / 1024 / 1024 . ' MB'
    ],
    $is_fast // 빠르면 로깅하지 않음
);
```

## 🛠️ 관리자 인터페이스

### 로그 뷰어
- **그리드 뷰**: 플러그인/테마별로 정리된 로그 표시
- **날짜 선택**: 특정 날짜의 로그만 필터링
- **실시간 보기**: 자동 새로고침으로 최신 로그 확인
- **액션 버튼**: 클리어, 삭제, 복사, 새로고침

### 디버그 설정
wp-config.php의 디버그 상수를 UI에서 직접 관리:
- `WP_DEBUG`: WordPress 디버그 모드
- `WP_DEBUG_LOG`: 로그 파일 기록 여부
- `WP_DEBUG_DISPLAY`: 화면에 에러 표시
- `SCRIPT_DEBUG`: 스크립트 디버그
- `WP_MEMORY_LIMIT`: 메모리 제한 설정

## 📁 로그 파일 구조

```
wp-content/sl-logs/
├── plugin-name/
│   ├── log-2024-01-15.log
│   └── log-2024-01-16.log
├── mu-plugin-name/
│   └── log-2024-01-16.log
└── theme-theme-name/
    └── log-2024-01-16.log
```

- 일별 로그 파일 생성 (`log-YYYY-MM-DD.log`)
- 7일 이상 된 로그는 자동 삭제
- .htaccess로 직접 접근 차단

## ⚙️ 설정

### WordPress 디버그 상수
```php
define('WP_DEBUG', true);        // 디버그 모드 활성화
define('WP_DEBUG_LOG', true);     // 로그 파일 기록
define('WP_DEBUG_DISPLAY', false); // 화면 표시 비활성화
```

### 조건부 로깅 패턴
```php
// 개발 환경에서만 로깅
$is_dev = defined('WP_ENV') && WP_ENV === 'development';
sl_debug('개발 디버그 정보', $data, !$is_dev);

// 관리자만 로깅
$is_admin = current_user_can('manage_options');
sl_info('관리자 작업', $admin_data, !$is_admin);

// 특정 사용자만 로깅
$is_test_user = get_current_user_id() === 42;
sl_debug('테스트 사용자 활동', $activity, !$is_test_user);
```

## 🔒 보안 기능

- 모든 AJAX 요청에 nonce 검증
- 관리자 권한 (manage_options) 필수
- 로그 디렉토리 .htaccess 보호
- 사용자 입력값 완전 검증

## 🚀 성능 최적화

- 최소한의 오버헤드로 가벼운 동작
- 필요시에만 파일 로드
- WP_DEBUG false 시 디버그 로깅 자동 비활성화
- 자동 정리로 디스크 공간 관리

## 🤖 AI 친화적 로그 형식

구조화된 로그 형식으로 AI 시스템 분석에 최적화:
```
[2024-01-16 10:30:45] [INFO] [checkout.php - process_order] 주문 처리 완료
Data: {"order_id":789,"total":"50000","payment":"card"}
```

## 📝 라이선스

이 플러그인은 WordPress와 동일한 라이선스로 제공됩니다.

## 🤝 기여

버그 리포트, 기능 제안, 풀 리퀘스트는 언제나 환영합니다!