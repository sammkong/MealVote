<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../db.php';

try {
    // 메뉴 목록 조회 (ID, 제목, 이미지 경로)
    $sql = "SELECT menu_id, title, image_url
            FROM menu
            ORDER BY menu_id ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($rows);

} catch (Throwable $e) {
    // 조회 중 예외 발생 시 서버 오류 반환
    http_response_code(500);
    echo json_encode([
        'error' => 'SERVER_ERROR',
        'message' => $e->getMessage()
    ]);
}
?>