# mealvote/php-api/Dockerfile
FROM php:8.2-apache

# 필요한 확장 설치: PDO MySQL + cURL
RUN apt-get update \
 && apt-get install -y libcurl4-openssl-dev \
 && docker-php-ext-install pdo pdo_mysql curl \
 && rm -rf /var/lib/apt/lists/*

# 소스 복사
COPY src/ /var/www/html/

EXPOSE 80