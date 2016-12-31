CREATE DATABASE IF NOT EXISTS gigya_usage CHARACTER SET utf8;

CREATE USER IF NOT EXISTS 'gigya_usage'@'localhost' IDENTIFIED BY 'q1a2z3w4s5x6';

CREATE TABLE IF NOT EXISTS `usage` (
  site_id VARCHAR(10) NOT NULL,
  month TINYINT UNSIGNED NOT NULL,
  year SMALLINT UNSIGNED NOT NULL,
  count INT UNSIGNED ZEROFILL NOT NULL,
  last_login VARCHAR(22) NOT NULL,
  last_create VARCHAR(22) NOT NULL,
  last_cached_date TIMESTAMP NOT NULL DEFAULT NOW(),
  primary key (site_id, month, year)
);

GRANT ALL PRIVILEGES ON gigya_usage . * TO 'gigya_usage'@'localhost';
