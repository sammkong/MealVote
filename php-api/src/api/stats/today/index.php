<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__.'/../../../db.php';

// 오늘 날짜 기준으로 메뉴별 LIKE / DISLIKE 집계 조회
$sql = "SELECT m.menu_id, m.title,
        SUM(CASE WHEN v.vote='LIKE' THEN 1 ELSE 0 END) AS `like`,
        SUM(CASE WHEN v.vote='DISLIKE' THEN 1 ELSE 0 END) AS `dislike`
        FROM menu m
        LEFT JOIN vote_log v ON m.menu_id = v.menu_id
                          AND DATE(v.voted_at) = CURRENT_DATE()
        GROUP BY m.menu_id, m.title
        ORDER BY m.menu_id ASC";

$stmt = $pdo->query($sql);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 각 메뉴의 좋아요 비율 계산 (전체가 0일 경우 0으로 나누는 오류 방지)
foreach ($rows as &$r) {
  $like  = (int)$r['like'];
  $dis   = (int)$r['dislike'];
  $total = max(1, $like + $dis);   // 최소 1로 설정하여 0 나누기 방지
  $r['rate'] = $like / $total;
}

echo json_encode($rows);
?>