<?php
return array(
		'rootLogger' => array(
				'appenders' => array('default','stdout'),
		),
		'appenders' => array(
				'default' => array(
						'class' => 'LoggerAppenderFile',
						'layout' => array(
								 'class' => 'LoggerLayoutPattern',
								 'params' => array(
										'conversionPattern' => '%date{d.m.Y H:i:s,u} %-6level - %C->%method at line %line - %msg%n'),
						),
						'params' => array(
								'file' => '/var/log/Domodata/DomoData.log',
								'append' => true
						)
				),
				'stdout' => array(
						'class' => 'LoggerAppenderConsole',
						'layout' => array(
								'class' => 'LoggerLayoutPattern',
								'params' => array(
										'conversionPattern' => '%date{d.m.Y H:i:s,u} %-6level - %C->%method at line %line - %msg%n'),
						)
				)
		)
);
?>