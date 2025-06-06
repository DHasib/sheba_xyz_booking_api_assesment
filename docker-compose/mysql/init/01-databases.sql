# create databases
CREATE DATABASE IF NOT EXISTS `booking_db`;

# create local_developer user and grant rights
CREATE USER 'local_developer'@'mysqlDB' IDENTIFIED BY 'password';
GRANT ALL PRIVILEGES ON *.* TO 'local_developer'@'%';
