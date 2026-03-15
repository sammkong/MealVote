import express from 'express';
import session from 'express-session';
import bcrypt from 'bcrypt';
import { createClient } from 'redis';
import RedisStore from 'connect-redis';
import mysql from 'mysql2/promise';

import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname  = path.dirname(__filename);

const app = express();

// JSON 본문 파싱 + 정적 이미지 제공
app.use(express.json());
app.use('/images', express.static(path.join(__dirname, '../frontend/public/images')));

// DB / 세션 관련 환경변수
const DB_HOST = process.env.DB_HOST || 'mysql';
const DB_USER = process.env.DB_USER || 'mv';
const DB_PASS = process.env.DB_PASS || 'mvpass';
const DB_NAME = process.env.DB_NAME || 'mealvote';
const SESSION_SECRET = process.env.SESSION_SECRET || 'dev_secret';
const REDIS_URL = process.env.REDIS_URL || 'redis://redis:6379';

// MySQL 커넥션 풀
const pool = mysql.createPool({
  host: DB_HOST,
  user: DB_USER,
  password: DB_PASS,
  database: DB_NAME,
  connectionLimit: 10
});

// Redis 세션 저장소 설정
const redisClient = createClient({ url: REDIS_URL });
await redisClient.connect();

app.set('trust proxy', 1);
app.use(session({
  name: 'MEALVOTE_SID',
  store: new RedisStore({ client: redisClient }),
  secret: SESSION_SECRET,
  resave: false,
  saveUninitialized: false,
  cookie: {
    httpOnly: true,
    sameSite: 'lax',
    maxAge: 1000 * 60 * 60
  }
}));

// username으로 사용자 한 명 조회
async function findUser(username) {
  const [rows] = await pool.query(
    'SELECT * FROM users WHERE username=?',
    [username]
  );
  return rows[0] || null;
}

// 회원가입: 비밀번호를 bcrypt 해시로 저장
app.post('/api/auth/register', async (req, res) => {
  try {
    const { username, password } = req.body || {};
    if (!username || !password) {
      return res.status(400).json({ error: 'REQUIRED', message: 'username, password 필수' });
    }
    if (password.length < 4) {
      return res.status(400).json({ error: 'WEAK_PASSWORD', message: '비밀번호는 4자 이상' });
    }

    const exists = await findUser(username);
    if (exists) {
      return res.status(409).json({ error: 'DUPLICATE', message: '이미 존재하는 아이디' });
    }

    const hashed = await bcrypt.hash(password, 10);
    await pool.query(
      'INSERT INTO users (username, password) VALUES (?, ?)',
      [username, hashed]
    );

    return res.status(201).json({ message: '회원가입 성공' });
  } catch (e) {
    console.error(e);
    return res.status(500).json({ error: 'SERVER_ERROR' });
  }
});

// 로그인: 세션에 간단한 사용자 정보 저장
app.post('/api/auth/login', async (req, res) => {
  const { username, password } = req.body || {};
  if (!username || !password) {
    return res.status(400).json({ error: 'BAD_REQUEST' });
  }

  const u = await findUser(username);
  if (!u) return res.status(401).end();

  const ok = await bcrypt.compare(password, u.password);
  if (!ok) return res.status(401).end();

  req.session.user = { id: u.user_id, username: u.username };
  res.json({ userId: u.user_id, username: u.username });
});

// 로그아웃: 세션 제거
app.post('/api/auth/logout', async (req, res) => {
  req.session.destroy(() => {});
  res.status(204).end();
});

// 현재 로그인한 사용자 정보 조회
app.get('/api/auth/me', async (req, res) => {
  if (!req.session.user) return res.status(401).end();
  res.json({
    userId: req.session.user.id,
    username: req.session.user.username
  });
});

// 후기 작성: 로그인 필수
app.post('/api/review', async (req, res) => {
  try {
    if (!req.session.user) return res.status(401).end();

    const { menu_id, content } = req.body || {};
    if (!menu_id || !content || !String(content).trim()) {
      return res.status(400).json({ error: 'REQUIRED' });
    }

    await pool.query(
      'INSERT INTO reviews (menu_id, user_id, content) VALUES (?, ?, ?)',
      [menu_id, req.session.user.id, String(content).trim().slice(0, 500)]
    );

    return res.status(201).json({ message: 'ok' });
  } catch (e) {
    console.error(e);
    return res.status(500).json({ error: 'SERVER_ERROR' });
  }
});

// 오늘 후기 목록: 로그인 필수 + 메뉴/작성자 정보 포함
app.get('/api/review/today', async (req, res) => {
  try {
    // 여기서 401을 보내면 프론트에서 "로그인을 해야..."로 안내
    if (!req.session.user) return res.status(401).end();

    const [rows] = await pool.query(
      `SELECT r.review_id,
              r.menu_id,
              m.title AS menu_title,
              u.username,
              r.content,
              r.created_at
       FROM reviews r
       JOIN menu m ON m.menu_id = r.menu_id
       JOIN users u ON u.user_id = r.user_id
       WHERE DATE(m.menu_date) = CURRENT_DATE()
       ORDER BY r.review_id DESC
       LIMIT 50`
    );

    res.json(rows);
  } catch (e) {
    console.error(e);
    return res.status(500).json({ error: 'SERVER_ERROR' });
  }
});

app.listen(3000, () => console.log('node-auth on :3000'));