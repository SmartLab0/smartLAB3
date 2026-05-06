-- حذف قاعدة البيانات إذا موجودة وإنشاؤها من جديد
DROP DATABASE IF EXISTS university_labs;
CREATE DATABASE university_labs CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE university_labs;

-- ثم كود الجداول اللي موجود...-- حذف الجداول إذا كانت موجودة
DROP TABLE IF EXISTS chatbot_logs;
DROP TABLE IF EXISTS reports;
DROP TABLE IF EXISTS tickets;
DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS class_schedule;
DROP TABLE IF EXISTS labs;
DROP TABLE IF EXISTS users;

-- إنشاء الجداول من جديد
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    fullname VARCHAR(100) NOT NULL,
    student_id VARCHAR(20) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(15) NOT NULL,
    college VARCHAR(50) NOT NULL,
    specialization VARCHAR(100),
    password VARCHAR(255) NOT NULL,
    avatar VARCHAR(255) DEFAULT 'default-avatar.png',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
);

CREATE TABLE labs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    lab_code VARCHAR(20) UNIQUE NOT NULL,
    lab_name VARCHAR(100) NOT NULL,
    college VARCHAR(50) NOT NULL,
    building VARCHAR(10) NOT NULL,
    floor INT NOT NULL,
    capacity INT NOT NULL,
    equipment TEXT,
    status ENUM('active', 'maintenance', 'inactive') DEFAULT 'active'
);

CREATE TABLE bookings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    lab_id INT NOT NULL,
    booking_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    status ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'confirmed',
    student_count INT DEFAULT 6,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (lab_id) REFERENCES labs(id) ON DELETE CASCADE
);

CREATE TABLE tickets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    booking_id INT,
    ticket_type ENUM('current', 'past') NOT NULL,
    ticket_code VARCHAR(50) UNIQUE NOT NULL,
    status ENUM('attended', 'absent', 'pending') DEFAULT 'pending',
    check_in_time TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE SET NULL
);

CREATE TABLE reports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    lab_id INT NOT NULL,
    issue_description TEXT NOT NULL,
    issue_type VARCHAR(50),
    image_path VARCHAR(255),
    status ENUM('reported', 'in_progress', 'resolved') DEFAULT 'reported',
    report_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_date TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (lab_id) REFERENCES labs(id) ON DELETE CASCADE
);

CREATE TABLE chatbot_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    user_message TEXT NOT NULL,
    bot_response TEXT NOT NULL,
    message_type ENUM('text', 'image') DEFAULT 'text',
    image_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE class_schedule (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    day_of_week ENUM('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    course_name VARCHAR(100) NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);