<?php

	Class extension_remoteimageselectorfield extends Extension{
		
		public function uninstall(){
			Symphony::Database()->query("DROP TABLE `tbl_fields_remoteimageselector`");
		}

		public function install() {
			return Symphony::Database()->query("CREATE TABLE `tbl_fields_remoteimageselector` (
			  `id` int(11) unsigned NOT NULL auto_increment,
			  `field_id` int(11) unsigned NOT NULL,
			  `url` varchar(1000) NOT NULL,
			  `destination` varchar(255) NOT NULL,
			  `validator` varchar(50),
			  PRIMARY KEY (`id`),
			  UNIQUE KEY `field_id` (`field_id`)
			) TYPE=MyISAM");
		}

	}
