<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../_auth.php';

// 로그인한 사용자 정보 조회 (node-auth 연동)
$user = requireUserFromNode();
$user_id = $user['_resolved_user_id'];

// JSON 요청 파싱
$input  = json_decode(file_get_contents('php://input'), true);
$menuId = isset($input['menu_id']) ? intval($input['menu_id']) : 0;
$vote   = $input['vote'] ?? '';

// 기본 유효성 검사
if (!$menuId || !in_array($vote, ['LIKE','DISLIKE'], true)) {
  http_response_code(400);
  echo json_encode(['error' => 'BAD_REQUEST']);
  exit;
}

try {
  // 같은 유저가 같은 메뉴에 대해 오늘 날짜에 이미 투표했는지 확인
  $checkSql = "SELECT 1 FROM vote_log 
               WHERE menu_id = ? 
                 AND user_id = ? 
                 AND DATE(voted_at) = CURRENT_DATE()";
  
  $stmt = $pdo->prepare($checkSql);
  $stmt->execute([$menuId, $user_id]);
  
  if ($stmt->fetch()) {
      http_response_code(409);
      echo json_encode(['error' => 'ALREADY_VOTED_TODAY']);
      exit;
  }

  // 아직 투표하지 않았다면 새로운 투표 로그 INSERT
  $sql = "INSERT INTO vote_log (menu_id, user_id, vote, voted_at)
          VALUES (?, ?, ?, NOW())";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([$menuId, $user_id, $vote]);

  http_response_code(201);
  echo json_encode(['ok' => true]);

} catch (PDOException $e) {
  // DB 예외 발생 시 서버 에러 응답
  http_response_code(500);
  echo json_encode(['error' => 'SERVER_ERROR', 'msg' => $e->getMessage()]);
}
?>