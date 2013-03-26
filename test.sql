-- phpMyAdmin SQL Dump
-- version 3.3.9
-- http://www.phpmyadmin.net
--
-- 主机: localhost
-- 生成日期: 2013 年 03 月 26 日 05:45
-- 服务器版本: 5.5.8
-- PHP 版本: 5.3.5

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- 数据库: `lazyartest`
--

-- --------------------------------------------------------

--
-- 表的结构 `author`
--

DROP TABLE IF EXISTS `author`;
CREATE TABLE IF NOT EXISTS `author` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=3 ;

--
-- 转存表中的数据 `author`
--

INSERT INTO `author` (`id`, `name`) VALUES
(1, 'J. K. Rowling');

-- --------------------------------------------------------

--
-- 表的结构 `book`
--

DROP TABLE IF EXISTS `book`;
CREATE TABLE IF NOT EXISTS `book` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `author` int(10) unsigned NOT NULL COMMENT '作者id',
  `hit` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '阅读次数',
  `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '创建时间',
  `visited` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '最后阅读时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=5 ;

--
-- 转存表中的数据 `book`
--

INSERT INTO `book` (`id`, `name`, `author`, `hit`, `created`, `visited`) VALUES
(1, 'Harry Potter and the Philosopher''s Stone', 1, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(2, 'Harry Potter and the Chamber of Secrets', 1, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(3, 'Harry Potter and the Prisoner of Azkaban', 1, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00');