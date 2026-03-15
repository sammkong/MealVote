<?php
// 오늘 작성된 후기 목록 조회 (로그인한 사용자만 접근 가능)
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__.'/../../../db.php';
require_once __DIR__.'/../../_auth.php';

// 로그인 사용자 확인
$user = requireUserFromNode();
$user_id = $user['_resolved_user_id'];

// 오늘 날짜 기준의 후기 + 작성자 + 메뉴 정보 조회
$sql = "
  SELECT
    r.review_id,
    r.content,
    r.created_at,
    u.username,
    m.title AS menu_title
  FROM reviews r
  JOIN users u ON r.user_id = u.user_id
  JOIN menu  m ON r.menu_id = m.menu_id
  WHERE DATE(r.created_at) = CURRENT_DATE()
  ORDER BY r.created_at DESC
";

$stmt = $pdo->query($sql);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($rows);
?>