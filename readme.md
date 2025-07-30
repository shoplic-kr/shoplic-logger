# Shoplic Logger - 태그 기반 WordPress 로깅 솔루션

Shoplic Logger는 태그 기반 제어 시스템을 통해 선택적 로깅이 가능한 WordPress 디버깅 솔루션입니다. 기본적으로 모든 로그는 `@off` 상태로 작성되며, 필요한 태그만 `@on`으로 변경하여 원하는 로그를 활성화할 수 있습니다.

## 🚀 주요 특징

### 1. **태그 기반 선택적 로깅**
모든 로그는 태그와 함께 작성되며, `@on` 상태의 태그가 있는 로그만 파일에 기록됩니다:
```php
// 태그가 모두 @off 상태이므로 로그가 기록되지 않음
do_action('sl_log', '결제 프로세스 시작', $data, ['slt#payment@off', 'slt#checkout@off']);

// slt#critical이 @on 상태이므로 로그가 기록됨
do_action('sl_error', '치명적 오류', $error, ['slt#api@off', 'slt#critical@on']);
```

### 2. **간편한 태그 제어**
`find`와 `sed` 명령어로 태그를 쉽게 on/off 할 수 있습니다:
```bash
# payment 관련 로그 활성화
find . -name "*.php" -type f -exec sed -i 's/slt#payment@off/slt#payment@on/g' {} +

# 모든 로그 비활성화 (초기화)
find . -name "*.php" -type f -exec sed -i 's/@on\]/@off]/g' {} +
```

### 3. **자동 소스 감지 및 분류**
로그가 발생한 위치를 자동으로 감지하여 플러그인/테마별로 분류합니다:
- 일반 플러그인: `/wp-content/sl-logs/plugin-name/`
- MU 플러그인: `mu-` 접두사로 분류
- 테마: `theme-` 접두사로 분류
- WordPress 코어, 기타 위치별 자동 정리

### 4. **시각적 관리자 인터페이스**
WordPress 관리자 메뉴 "쇼플릭 로거"에서 제공:
- 로그 뷰어: 날짜별 필터링, 태그별 필터링
- 로그 관리: 클리어, 삭제, 클립보드 복사
- 디버그 설정: wp-config.php 디버그 상수 직접 관리

### 5. **액션 기반 사용법** (플러그인 의존성 제거)
플러그인이 비활성화되어도 에러가 발생하지 않는 안전한 방법:
```php
// 태그와 함께 사용
do_action('sl_log', '사용자 등록', $user_data, ['slt#user-register@off', 'slt#auth@off']);
do_action('sl_error', 'API 타임아웃', $error, ['slt#api@off', 'slt#critical@off']);
do_action('sl_info', '주문 완료', $order_data, ['slt#order@off', 'slt#sales@off']);
do_action('sl_warning', '재고 부족', $stock_data, ['slt#inventory@off', 'slt#warning@off']);
do_action('sl_debug', '쿼리 실행', $query_data, ['slt#database@off', 'slt#performance@off']);
```

## 📥 설치 방법

1. `shoplic-logger` 폴더를 `wp-content/mu-plugins/` 디렉토리에 복사
2. `shoplic-logger.php` 파일을 `wp-content/mu-plugins/` 루트에 복사
3. 설치 완료! (MU 플러그인은 자동 활성화)

## 📖 사용법

### 태그와 함께 로깅하기

모든 로그는 태그와 함께 작성해야 합니다. 태그는 `slt#태그명@상태` 형식을 사용합니다:

```php
// 기본 사용법 - 태그는 @off 상태로 시작
do_action('sl_log', 
    sprintf('[%s - %s] 결제 시작', basename(__FILE__), __METHOD__),
    $payment_data, 
    ['slt#payment@off', 'slt#checkout@off']
);

// 여러 태그 사용
do_action('sl_error', 
    sprintf('[%s - %s] API 호출 실패', basename(__FILE__), __METHOD__),
    ['url' => $api_url, 'error' => $error_message], 
    ['slt#api@off', 'slt#error@off', 'slt#critical@off']
);
```

### 태그 on/off 제어

#### 특정 태그 활성화
```bash
# payment 태그만 활성화
find . -name "*.php" -type f -exec sed -i 's/slt#payment@off/slt#payment@on/g' {} +

# error와 critical 태그 동시 활성화
find . -name "*.php" -type f -exec sed -i -e 's/slt#error@off/slt#error@on/g' -e 's/slt#critical@off/slt#critical@on/g' {} +
```

#### 모든 태그 비활성화 (초기화)
```bash
find . -name "*.php" -type f -exec sed -i 's/@on\]/@off]/g' {} +
```

#### 태그 검색 및 확인
```bash
# 모든 태그 목록 보기
grep -r "slt#" --include="*.php" | grep -o "slt#[^'\"]*" | sort | uniq

# 특정 태그가 사용된 위치 찾기
grep -r "slt#payment" --include="*.php"

# 현재 @on 상태인 태그 확인
grep -r "@on\]" --include="*.php"
```

### 로그 레벨별 사용
```php
// 정보성 로그
do_action('sl_info',
    sprintf('[%s - %s] 새 주문', basename(__FILE__), __FUNCTION__),
    ['order_id' => 789, 'total' => $order->get_total()],
    ['slt#woocommerce@off', 'slt#order@off', 'slt#sales@off']
);

// 에러 로그
do_action('sl_error',
    sprintf('[%s - %s] 결제 실패', basename(__FILE__), __METHOD__),
    ['order_id' => 456, 'error' => '카드 승인 거부'],
    ['slt#payment@off', 'slt#error@off', 'slt#critical@off']
);

// 경고 로그
do_action('sl_warning',
    sprintf('[%s - %s] 재고 부족', basename(__FILE__), __METHOD__),
    ['product_id' => 101, 'stock' => 5],
    ['slt#inventory@off', 'slt#warning@off']
);

// 디버그 로그 (WP_DEBUG가 true일 때만)
do_action('sl_debug',
    sprintf('[%s - %s] 메모리 사용량', basename(__FILE__), __METHOD__),
    ['memory' => memory_get_usage()],
    ['slt#performance@off', 'slt#debug@off']
);
```

## 🎯 실제 사용 예제

### WooCommerce 주문 처리
```php
add_action('woocommerce_new_order', function($order_id) {
    $order = wc_get_order($order_id);
    
    do_action('sl_info',
        sprintf('[%s - %s] 새 주문 접수', basename(__FILE__), __FUNCTION__),
        [
            'order_id' => $order_id,
            'total' => $order->get_total(),
            'customer' => $order->get_billing_email()
        ],
        ['slt#woocommerce@off', 'slt#order@off', 'slt#sales@off']
    );
});
```

### API 통신 로깅
```php
$response = wp_remote_get($api_url);

if (is_wp_error($response)) {
    do_action('sl_error',
        sprintf('[%s - %s] API 호출 실패', basename(__FILE__), __METHOD__),
        [
            'url' => $api_url,
            'error' => $response->get_error_message(),
            'error_code' => $response->get_error_code()
        ],
        ['slt#api@off', 'slt#error@off', 'slt#critical@off']
    );
} else {
    do_action('sl_debug',
        sprintf('[%s - %s] API 응답', basename(__FILE__), __METHOD__),
        [
            'status_code' => wp_remote_retrieve_response_code($response),
            'body' => wp_remote_retrieve_body($response)
        ],
        ['slt#api@off', 'slt#debug@off', 'slt#http@off']
    );
}
```

### 성능 모니터링
```php
$start_time = microtime(true);

// 복잡한 작업 수행
do_complex_operation();

$execution_time = microtime(true) - $start_time;

// 실행 시간이 1초 이상일 때 경고
if ($execution_time >= 1.0) {
    do_action('sl_warning',
        sprintf('[%s - %s] 느린 작업 감지', basename(__FILE__), __METHOD__),
        [
            'execution_time' => $execution_time,
            'memory_peak' => memory_get_peak_usage(true) / 1024 / 1024 . ' MB'
        ],
        ['slt#performance@off', 'slt#slow-request@off', 'slt#monitoring@off']
    );
}
```

### 사용자 인증 추적
```php
add_action('wp_login_failed', function($username) {
    do_action('sl_warning',
        sprintf('[%s - %s] 로그인 실패', basename(__FILE__), __FUNCTION__),
        [
            'username' => $username,
            'ip' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT']
        ],
        ['slt#security@off', 'slt#auth@off', 'slt#failed-login@off']
    );
});
```

## 🏷️ 일반적인 태그 시나리오

### 결제 프로세스 디버깅
```bash
# 결제 관련 모든 로그 활성화
find . -name "*.php" -type f -exec sed -i -e 's/slt#checkout@off/slt#checkout@on/g' -e 's/slt#payment@off/slt#payment@on/g' -e 's/slt#cart@off/slt#cart@on/g' {} +
```

### 보안 이벤트 모니터링
```bash
# 보안 및 인증 관련 로그 활성화
find . -name "*.php" -type f -exec sed -i -e 's/slt#security@off/slt#security@on/g' -e 's/slt#auth@off/slt#auth@on/g' -e 's/slt#failed-login@off/slt#failed-login@on/g' {} +
```

### API 및 외부 통신 추적
```bash
# API 호출 관련 로그 활성화
find . -name "*.php" -type f -exec sed -i -e 's/slt#api@off/slt#api@on/g' -e 's/slt#http@off/slt#http@on/g' -e 's/slt#external@off/slt#external@on/g' {} +
```

### 성능 문제 진단
```bash
# 성능 관련 로그 활성화
find . -name "*.php" -type f -exec sed -i -e 's/slt#performance@off/slt#performance@on/g' -e 's/slt#slow-request@off/slt#slow-request@on/g' -e 's/slt#database@off/slt#database@on/g' {} +
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

### 사용 가능한 태그 목록

현재 코드베이스에서 일반적으로 사용되는 태그:
- `navigation` - 페이지 네비게이션 추적
- `tracking` - 일반 추적 이벤트
- `system` - 시스템 레벨 이벤트
- `startup` - 초기화 이벤트
- `woocommerce` - WooCommerce 통합
- `order` - 주문 관련 이벤트
- `sales` - 판매 추적
- `security` - 보안 이벤트
- `auth` - 인증 이벤트
- `failed-login` - 실패한 로그인 시도
- `api` - API 호출 및 응답
- `error` - 에러 이벤트
- `critical` - 치명적 에러
- `performance` - 성능 모니터링
- `payment` - 결제 처리
- `checkout` - 체크아웃 프로세스
- `cart` - 장바구니 이벤트
- `user-register` - 사용자 등록
- `database` - 데이터베이스 작업
- `debug` - 디버그 정보

## 💡 태그 관리 모범 사례

### 1. 항상 초기화부터 시작
```bash
# 작업 전 모든 태그를 off로 초기화
find . -name "*.php" -type f -exec sed -i 's/@on\]/@off]/g' {} +
```

### 2. 현재 상태 확인
```bash
# 특정 태그의 현재 상태 확인
grep -r "slt#payment@" --include="*.php" | head -10

# 현재 활성화된 모든 태그 보기
grep -r "@on\]" --include="*.php"
```

### 3. 특정 디렉토리만 대상으로
```bash
# 특정 플러그인만 대상으로 태그 활성화
find ./wp-content/plugins/my-plugin -name "*.php" -type f -exec sed -i 's/slt#payment@off/slt#payment@on/g' {} +
```

### 4. 관련 태그 그룹핑
디버깅 목적에 따라 관련 태그를 함께 활성화:
- **결제 디버깅**: `payment`, `checkout`, `cart`
- **보안 모니터링**: `security`, `auth`, `failed-login`
- **성능 분석**: `performance`, `slow-request`, `database`
- **API 추적**: `api`, `http`, `external`

## 🔒 보안 기능

- 모든 AJAX 요청에 nonce 검증
- 관리자 권한 (manage_options) 필수
- 로그 디렉토리 .htaccess 보호
- 사용자 입력값 완전 검증

## 🚀 성능 최적화

- 태그 기반 선택적 로깅으로 불필요한 로그 방지
- 최소한의 오버헤드로 가벼운 동작
- 필요시에만 파일 로드
- WP_DEBUG false 시 디버그 로깅 자동 비활성화
- 자동 정리로 디스크 공간 관리

## 📝 로그 형식

### 로그 파일에 저장되는 형식
```
[2024-01-16 10:30:45] [INFO] checkout.php:123 - 주문 처리 완료 [TAGS: payment, checkout]
    Data: Array
    (
        [order_id] => 789
        [total] => 50000
        [payment] => card
    )
```

### 태그 형식 규칙
- **코드에서**: `['slt#tagname@off']` 또는 `['slt#tagname@on']`
- **로그 파일에서**: `[TAGS: tagname]` (접두사와 상태 제거)
- **출력 규칙**: 최소 하나의 `@on` 태그가 있는 로그만 파일에 기록

## 📝 라이선스

이 플러그인은 WordPress와 동일한 라이선스로 제공됩니다.

## 🤝 기여

버그 리포트, 기능 제안, 풀 리퀘스트는 언제나 환영합니다!