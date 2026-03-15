# node-auth/Dockerfile
FROM node:20-alpine
WORKDIR /app

# lock 파일이 없어도 동작하도록 package*.json만 복사
COPY package*.json ./

# npm ci → npm install 로 변경 (lock 없을 때 설치 가능)
RUN npm install --omit=dev

# 앱 소스
COPY app.js ./

EXPOSE 3000
CMD ["node", "app.js"]
