<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../_auth.php';

// 로그인 사용자 정보 조회
$user = requireUserFromNode();
$user_id = $user['_resolved_user_id'];

// 요청 데이터 파싱
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

$menu_id = intval($data['menu_id'] ?? 0);
$content = trim($data['content'] ?? '');

// 기본 입력값 검증
if ($menu_id <= 0 || $content === '') {
    http_response_code(400);
    echo json_encode(['error' => 'INVALID_INPUT']);
    exit;
}

try {
    // 후기 저장
    $stmt = $pdo->prepare("
        INSERT INTO reviews (menu_id, user_id, content)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$menu_id, $user_id, $content]);

    http_response_code(201);
    echo json_encode(['ok' => true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'SERVER_ERROR']);
}
?>