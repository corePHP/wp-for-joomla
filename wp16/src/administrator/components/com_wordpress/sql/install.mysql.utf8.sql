CREATE TABLE IF NOT EXISTS `#__wp_jauthenticate` (
`user_id` int(11) NOT NULL,
`hash` varchar(32) NOT NULL,
`timestamp` int(11) NOT NULL,
PRIMARY KEY  (`user_id`)
) CHARSET=utf8 COMMENT='Table used to store authentication actions';
