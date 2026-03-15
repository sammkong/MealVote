-- MealVote 초기 스키마 및 샘플 데이터 생성
USE mealvote;

-- 1. 사용자 테이블
CREATE TABLE IF NOT EXISTS users (
  user_id       BIGINT PRIMARY KEY AUTO_INCREMENT,
  username      VARCHAR(100) NOT NULL UNIQUE,
  password      VARCHAR(255) NOT NULL,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. 메뉴 테이블
CREATE TABLE IF NOT EXISTS menu (
  menu_id       BIGINT PRIMARY KEY AUTO_INCREMENT,
  menu_date     DATE NOT NULL,
  title         VARCHAR(200) NOT NULL,
  image_url     VARCHAR(500) NULL,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  -- 같은 날짜에 같은 메뉴 이름 등록 방지
  UNIQUE KEY uk_menu_date_title (menu_date, title)
);

-- 3. 투표 로그 테이블 
CREATE TABLE IF NOT EXISTS vote_log (
  vote_id       BIGINT PRIMARY KEY AUTO_INCREMENT,
  menu_id       BIGINT NOT NULL,
  user_id       BIGINT NOT NULL,
  vote          ENUM('LIKE','DISLIKE') NOT NULL,
  voted_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  -- [설명] 이 투표가 어떤 메뉴에 대한 건지 연결합니다.
  -- [CASCADE] 만약 '제육덮밥' 메뉴가 삭제되면, 그 메뉴에 달린 투표들도 같이 '자동 삭제'됩니다. (찌꺼기 방지)
  FOREIGN KEY (menu_id) REFERENCES menu(menu_id) ON DELETE CASCADE,
  -- [설명] 이 투표를 누가 했는지 연결합니다.
  -- [CASCADE] 만약 'mj' 유저가 탈퇴해서 삭제되면, 그 사람이 했던 투표들도 같이 '자동 삭제'됩니다.
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- 4. 후기 테이블
CREATE TABLE IF NOT EXISTS reviews (
  review_id     BIGINT PRIMARY KEY AUTO_INCREMENT,
  menu_id       BIGINT NOT NULL,
  user_id       BIGINT NOT NULL,
  content       VARCHAR(500) NOT NULL,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (menu_id) REFERENCES menu(menu_id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  INDEX idx_reviews_menu (menu_id),
  INDEX idx_reviews_user (user_id)
);

-- 5. 테스트 데이터: 유저 1명 (username: mj / password: 2323)
INSERT IGNORE INTO users (user_id, username, password)
VALUES (1, 'mj', '2323');

-- 6. 메뉴 데이터: 오늘 메뉴 3개
INSERT INTO menu (menu_date, title, image_url)
VALUES
  (CURRENT_DATE(), '김치찌개', NULL),
  (CURRENT_DATE(), '제육덮밥', NULL),
  (CURRENT_DATE(), '비빔밥', NULL)
ON DUPLICATE KEY UPDATE title = VALUES(title);