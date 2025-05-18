-- База данных для системы Alumni Office Средиземноморского колледжа
-- Для PostgreSQL не используем CREATE DATABASE в самом скрипте
-- CREATE DATABASE IF NOT EXISTS mediterranean_alumni;
-- USE mediterranean_alumni;

-- Таблица факультетов колледжа
CREATE TABLE IF NOT EXISTS faculties (
    faculty_id SERIAL PRIMARY KEY,
    faculty_name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Таблица ролей пользователей
CREATE TABLE IF NOT EXISTS roles (
    role_id SERIAL PRIMARY KEY,
    role_name VARCHAR(50) NOT NULL,
    description TEXT
);

-- Таблица пользователей
CREATE TABLE IF NOT EXISTS users (
    user_id SERIAL PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    student_id VARCHAR(20) UNIQUE,
    graduation_year INT,
    faculty_id INT,
    role_id INT DEFAULT 3,
    profile_picture VARCHAR(255),
    bio TEXT,
    social_links JSONB, -- PostgreSQL использует JSONB вместо JSON
    status VARCHAR(10) DEFAULT 'pending', -- PostgreSQL не имеет ENUM по умолчанию, используем VARCHAR
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (faculty_id) REFERENCES faculties(faculty_id),
    FOREIGN KEY (role_id) REFERENCES roles(role_id)
);

-- В PostgreSQL нет ON UPDATE CURRENT_TIMESTAMP, нужно использовать триггер
CREATE OR REPLACE FUNCTION update_timestamp_column()
RETURNS TRIGGER AS $$
BEGIN
   NEW.updated_at = CURRENT_TIMESTAMP;
   RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_users_timestamp BEFORE UPDATE
ON users FOR EACH ROW EXECUTE PROCEDURE update_timestamp_column();

-- Таблица новостей и событий
CREATE TABLE IF NOT EXISTS events (
    event_id SERIAL PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    event_date DATE,
    location VARCHAR(200),
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(user_id)
);

CREATE TRIGGER update_events_timestamp BEFORE UPDATE
ON events FOR EACH ROW EXECUTE PROCEDURE update_timestamp_column();

-- Таблица достижений выпускников
CREATE TABLE IF NOT EXISTS achievements (
    achievement_id SERIAL PRIMARY KEY,
    user_id INT,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    achievement_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- Заполнение базовых данных
INSERT INTO roles (role_name, description) VALUES 
('admin', 'Administrators with full access'),
('alumni', 'Registered alumni members'),
('pending', 'Alumni in registration process');

INSERT INTO faculties (faculty_name, description) VALUES 
('Business Administration', 'Faculty of Business Administration and Economics'),
('Computer Science', 'Faculty of Computer Science and Information Technology'),
('Humanities', 'Faculty of Humanities and Social Sciences'),
('Engineering', 'Faculty of Engineering and Applied Sciences'),
('Health Sciences', 'Faculty of Health Sciences and Medicine');

-- Создание администратора по умолчанию (пароль: admin123)
INSERT INTO users (full_name, email, password_hash, role_id, status) VALUES 
('Admin User', 'admin@mediterranean.edu', '$2y$10$xLRMUA7WqH5E1V5h.Y.P3uKxjj9IMxSVrGsZ4KFBDSwjbj5Ziq4b6', 1, 'active'); 