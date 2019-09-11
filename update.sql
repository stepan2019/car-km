SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for email_option
-- ----------------------------
DROP TABLE IF EXISTS `email_option`;
CREATE TABLE `email_option`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `html_email` tinyint(1) NOT NULL,
  `smtp_auth` tinyint(1) NOT NULL,
  `smtp_server` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `encryption` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `port` int(11) NOT NULL,
  `username` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `password` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `bcc_email` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `admin_email` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 18 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = 'email_option' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of email_option
-- ----------------------------
INSERT INTO `email_option` VALUES (17, 0, 1, 'asd', 'TLS', 1, 'admin@email.com', '123', 'admin@email.com', 1);

SET FOREIGN_KEY_CHECKS = 1;
