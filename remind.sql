-- Create the database if it doesn't exist
CREATE DATABASE IF NOT EXISTS remind;
USE remind;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    otp VARCHAR(6) DEFAULT NULL,
    otp_expiry DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create schedules table
CREATE TABLE IF NOT EXISTS schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    activity_name VARCHAR(255) NOT NULL,
    schedule_date DATE NOT NULL,
    schedule_time TIME NOT NULL,
    reminder_time INT NOT NULL DEFAULT 30, -- Minutes before to send reminder
    is_notified TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create activity_logs table
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action TEXT NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- If tables already exist but are missing columns, add them

-- Ensure users table has OTP fields
ALTER TABLE users
ADD COLUMN IF NOT EXISTS otp VARCHAR(6) NULL,
ADD COLUMN IF NOT EXISTS otp_expiry DATETIME NULL;

-- Ensure schedules table has reminder_time and is_notified columns
ALTER TABLE schedules 
ADD COLUMN IF NOT EXISTS reminder_time INT NOT NULL DEFAULT 30,
ADD COLUMN IF NOT EXISTS is_notified TINYINT(1) NOT NULL DEFAULT 0;

-- Create indexes for better query performance
CREATE INDEX IF NOT EXISTS idx_schedules_user_id ON schedules(user_id);
CREATE INDEX IF NOT EXISTS idx_schedules_date_time ON schedules(schedule_date, schedule_time);
CREATE INDEX IF NOT EXISTS idx_activity_logs_user_id ON activity_logs(user_id);
