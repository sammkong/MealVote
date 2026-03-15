<?php
function requireUserFromNode() {
    // 브라우저에서 전달된 Cookie 헤더(세션 정보 포함) 가져오기
    $cookie = $_SERVER['HTTP_COOKIE'] ?? '';

    // node-auth의 /api/auth/me 호출을 위한 HTTP 컨텍스트 설정
    $opts = [
        'http' => [
            'method'  => 'GET',
            'header'  => $cookie ? "Cookie: $cookie\r\n" : "",
            'ignore_errors' => true, // 401, 500이어도 응답 본문은 읽도록 설정
            'timeout' => 3,
        ],
    ];
    $ctx = stream_context_create($opts);

    // node-auth 서버에 현재 로그인 사용자 정보 요청
    $body = @file_get_contents('http://node-auth:3000/api/auth/me', false, $ctx);

    // HTTP 상태 코드 파싱
    $statusLine = $http_response_header[0] ?? 'HTTP/1.1 500';
    if (!preg_match('{HTTP/\S+\s+(\d+)}', $statusLine, $m)) {
        $status = 500;
    } else {
        $status = (int)$m[1];
    }

    // 인증 실패 또는 통신 오류 시 401 반환 후 종료
    if ($status !== 200 || $body === false) {
        http_response_code(401);
        echo json_encode(['error' => 'LOGIN_REQUIRED']);
        exit;
    }

    // JSON 응답 파싱
    $data = json_decode($body, true);
    if (!is_array($data)) {
        http_response_code(500);
        echo json_encode(['error' => 'INVALID_AUTH_RESPONSE']);
        exit;
    }

    // 다양한 필드 이름(userId, user_id, id)에 대응하여 사용자 ID 추출
    $uid = 0;
    if (isset($data['userId'])) {
        $uid = (int)$data['userId'];
    } elseif (isset($data['user_id'])) {
        $uid = (int)$data['user_id'];
    } elseif (isset($data['id'])) {
        $uid = (int)$data['id'];
    }

    // 유효하지 않은 사용자 ID일 경우 에러 처리
    if ($uid <= 0) {
        http_response_code(500);
        echo json_encode(['error' => 'INVALID_AUTH_USER']);
        exit;
    }

    // 이후 로직에서 편하게 쓰기 위해 정규화된 사용자 ID 저장
    $data['_resolved_user_id'] = $uid;
    return $data;
}
?>