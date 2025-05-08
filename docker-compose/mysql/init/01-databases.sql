# create databases
CREATE DATABASE IF NOT EXISTS `booking_db`;

# create local_developer user and grant rights
CREATE USER 'local_developer'@'mysql_db' IDENTIFIED BY 'ZFklP80&,xk[';
GRANT ALL PRIVILEGES ON *.* TO 'local_developer'@'%';
