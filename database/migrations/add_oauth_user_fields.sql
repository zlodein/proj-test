-- Добавление полей для OAuth пользователей
-- Добавляем поля last_name и user_img в таблицу users

ALTER TABLE users 
ADD COLUMN IF NOT EXISTS last_name VARCHAR(100) DEFAULT NULL COMMENT 'Фамилия пользователя' AFTER name,
ADD COLUMN IF NOT EXISTS user_img VARCHAR(500) DEFAULT NULL COMMENT 'URL аватарки пользователя';

-- Комментарии к существующим полям для ясности
COMMENT ON COLUMN users.name IS 'Имя пользователя';
COMMENT ON COLUMN users.last_name IS 'Фамилия пользователя';
COMMENT ON COLUMN users.user_img IS 'URL аватарки пользователя (из OAuth или загруженная)';
