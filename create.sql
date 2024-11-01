-- Create email_recipients table
CREATE TABLE email_recipients (
  id INT NOT NULL AUTO_INCREMENT,
  email VARCHAR(255) NOT NULL UNIQUE,
  created_at TIMESTAMP NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (id)
);

-- Create thresholds table first (to avoid foreign key constraint issues)
CREATE TABLE thresholds (
  id INT NOT NULL AUTO_INCREMENT,
  device_id VARCHAR(10) UNIQUE,
  gas_threshold FLOAT,
  smoke_threshold FLOAT,
  co_threshold FLOAT,
  lpg_threshold FLOAT,
  created_at TIMESTAMP NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (id)
);

-- Create gas_readings table with a foreign key reference to thresholds
CREATE TABLE gas_readings (
  id INT NOT NULL AUTO_INCREMENT,
  device_id VARCHAR(10),
  gas_level FLOAT NOT NULL,
  gas_type VARCHAR(20),
  smoke_status TINYINT(1),
  co_status TINYINT(1),
  lpg_status TINYINT(1),
  status TINYINT(1) DEFAULT 0,
  alert_status TINYINT(1) DEFAULT 0,
  threshold_id INT,
  timestamp TIMESTAMP NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (id),
  FOREIGN KEY (threshold_id) REFERENCES thresholds(id) ON DELETE SET NULL ON UPDATE CASCADE
);

-- Create users table
CREATE TABLE users (
  id INT NOT NULL AUTO_INCREMENT,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin', 'user', 'super_user', 'super_admin') NOT NULL,
  PRIMARY KEY (id)
);

-- Create gas_alert_responses table with foreign key references to gas_readings and users
CREATE TABLE gas_alert_responses (
  id INT NOT NULL AUTO_INCREMENT,
  reading_id INT NOT NULL,
  user_id INT,  -- Allow NULL to support ON DELETE SET NULL
  response_type ENUM('acknowledged', 'false_alarm') NOT NULL,
  response_time TIMESTAMP NOT NULL DEFAULT current_timestamp(),
  comments VARCHAR(255),
  PRIMARY KEY (id),
  FOREIGN KEY (reading_id) REFERENCES gas_readings(id) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE
);
