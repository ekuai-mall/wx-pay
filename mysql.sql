SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `ekm_order`;
CREATE TABLE `ekm_order`
(
    `id`          int(11) NOT NULL AUTO_INCREMENT,
    `name`        text    NOT NULL,
    `time_start`  int(11) NOT NULL,
    `time_finish` int(11) DEFAULT NULL,
    `status`      text    NOT NULL,
    `product`     int(11) NOT NULL,
    `user`        int(11) NOT NULL,
    `order`       text    NOT NULL,
    `price`       int(11) NOT NULL,
    `url`         text    NOT NULL,
    `remark`      text    NOT NULL,
    `extra`       text            ,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;
