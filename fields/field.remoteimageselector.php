<?php

	if (!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');

	require_once(TOOLKIT . '/fields/field.upload.php');

	require_once(CORE . '/class.cacheable.php');
	require_once(EXTENSIONS . '/remoteimageselectorfield/extension.driver.php');

	Class Fieldremoteimageselector extends FieldUpload {

		private $_filter_origin = array();

		public function __construct(){
			parent::__construct();
			$this->_name = 'Remote Image Selector';
		}

	/*-------------------------------------------------------------------------
		Definition:
	-------------------------------------------------------------------------*/

		public function mustBeUnique(){
			return true;
		}

		public function canFilter(){
			return false;
		}

	/*-------------------------------------------------------------------------
		Setup:
	-------------------------------------------------------------------------*/

		public function createTable(){
			return Symphony::Database()->query(
				"CREATE TABLE IF NOT EXISTS `tbl_entries_data_" . $this->get('id') . "` (
				  `id` int(11) unsigned NOT NULL auto_increment,
				  `entry_id` int(11) unsigned NOT NULL,
				  `url` varchar(1000) default NULL,
				  `fileurl` varchar(1000) default NULL,
				  `file` varchar(255) default NULL,
				  `size` int(11) unsigned NULL,
				  `mimetype` varchar(100) default NULL,
				  `meta` varchar(255) default NULL,
				  PRIMARY KEY  (`id`),
				  UNIQUE KEY `entry_id` (`entry_id`),
				  KEY `file` (`file`),
				  KEY `mimetype` (`mimetype`)
				) TYPE=MyISAM;"
			);
		}


	/*-------------------------------------------------------------------------
		Settings:
	-------------------------------------------------------------------------*/

		public function displaySettingsPanel(&$wrapper, $errors=NULL){
			parent::displaySettingsPanel($wrapper, $errors);
		}

		public function commit(){
			if(!parent::commit()) return false;

			$id = $this->get('id');

			if($id === false) return false;

			$fields = array();

			$fields['field_id'] = $id;
			$fields['destination'] = $this->get('destination');

			return FieldManager::saveSettings($id, $fields);
		}

	/*-------------------------------------------------------------------------
		Publish:
	-------------------------------------------------------------------------*/

		public function displayPublishPanel(&$wrapper, $data=NULL, $flagWithError=NULL, $fieldnamePrefix=NULL, $fieldnamePostfix=NULL, $entry_id=NULL){
			if (class_exists('Administration') && Administration::instance()->Page) {
				Administration::instance()->Page->addStylesheetToHead(URL . '/extensions/remoteimageselectorfield/assets/jquery.bxslider.css');
				Administration::instance()->Page->addScriptToHead(URL . '/extensions/remoteimageselectorfield/assets/jquery.bxslider.min.js', 78);
				Administration::instance()->Page->addStylesheetToHead(URL . '/extensions/remoteimageselectorfield/assets/remoteimageselector.publish.css', 'screen', 80);
				Administration::instance()->Page->addScriptToHead(URL . '/extensions/remoteimageselectorfield/assets/remoteimageselector.publish.js', 82);
			}

			$fieldlabel = new XMLElement('p', $this->get('label'));
			$fieldlabel->setAttribute('class', 'label');
			if($this->get('required') != 'yes') $fieldlabel->appendChild(new XMLElement('i', __('Optional')));

			$wrapper->appendChild($fieldlabel);

			$label = Widget::Label('');
			$label->setAttribute('class', 'file');

			if (isset($data['url'])) {
				$label->appendChild(Widget::Input('fields'.$fieldnamePrefix.'['.$this->get('element_name').'][url]'.$fieldnamePostfix, $data['url']));
			}
			else {
				$label->appendChild(Widget::Input('fields'.$fieldnamePrefix.'['.$this->get('element_name').'][url]'.$fieldnamePostfix, ''));
			}

			$span = new XMLElement('span', NULL, array('class' => 'frame'));

			if (isset($data['file'])) {
				$filename = $this->get('destination') . '/' . basename($data['file']);
				$file = $this->getFilePath($data['file']);
				if (file_exists($file) === false || !is_readable($file)) {
					$flagWithError = __('The file uploaded is no longer available. Please check that it exists, and is readable.');
				}

				$span->appendChild(new XMLElement('span', Widget::Anchor(preg_replace("![^a-z0-9]+!i", "$0&#8203;", $filename), URL . $filename, null, "image", null)));
				$label->appendChild($span);
			}
			else {
				$filename = null;
			}

			$wrapper->appendChild($label);			

			$url_field = Widget::Input('fields'.$fieldnamePrefix.'['.$this->get('element_name').'][fileurl]'.$fieldnamePostfix, '', 'hidden');
			$wrapper->appendChild($url_field);

			$divframe = new XMLElement('div', NULL, array('class' => 'dark frame editable searchable'));
			$divcontent = new XMLElement('div', NULL, array('class' => 'content'));

			$slider = new XMLElement('div', NULL, array('class' => 'bxslider'));
			$divcontent->appendChild($slider);
			$divframe->appendChild($divcontent);			
			$wrapper->appendChild($divframe);
		}

		public function processRawFieldData($data, &$status, $simulate=false, $entry_id=NULL){
			$status = self::__OK__;

			// No file given, save empty data:
			if ($data === null) {
				return array(
					'url' 			=> null,
					'fileurl' 	=> null,
					'file' 			=> null,
					'mimetype'	=> null,
					'size' 			=> null,
					'meta' 			=> null
				);
			}

			// Where to upload the new file?
			$abs_path = DOCROOT . '/' . trim($this->get('destination'), '/');
			$rel_path = str_replace('/workspace', '', $this->get('destination'));

			if (is_array($data)) {

				if ($data['fileurl'] && strlen($data['fileurl']) > 0) {

					try {

						$temp_filename = basename($data['fileurl']);
						$temp_file = $abs_path . '/' . $temp_filename;

						// If a file already exists, then rename the file being uploaded by
						// adding `_1` to the filename. If `_1` already exists, the logic
						// will keep adding 1 until a filename is available (#672)
						if (file_exists($temp_file)) {
							$extension = General::getExtension($temp_file);
							$new_file = substr($temp_file, 0, -1 - strlen($extension));
							$renamed_file = $new_file;
							$count = 1;

							do {
								$renamed_file = $new_file . '_' . $count . '.' . $extension;
								$count++;
							} while (file_exists($renamed_file));

							// Extract the name filename from `$renamed_file`.
							$data['name'] = str_replace($abs_path . '/', '', $renamed_file);
						}

						// var_dump($this->get('destination'));
						// var_dump($temp_file);exit;
						file_put_contents($temp_file, file_get_contents($data['fileurl']));
					}
					catch (Exception $e) {
    				echo 'Caught exception: ',  $e->getMessage(), "\n";exit;
					}

				}
				else {
					return array(
						'url' 			=> $data['url'],
						'fileurl' 	=> null,
						'file' 			=> null,
						'mimetype'	=> null,
						'size' 			=> null,
						'meta' 			=> null
					);
				}

			}


			// Its not an array, so just retain the current data and return:
			if (is_array($data) === false) {

				$file = $this->getFilePath(basename($data));

				$result = array(
					'url' =>		null,
					'fileurl' =>	null,
					'file' =>		$data,
					'mimetype' =>	null,
					'size' =>		null,
					'meta' =>		null
				);

				// Grab the existing entry data to preserve the MIME type and size information
				if (isset($entry_id)) {
					$row = Symphony::Database()->fetchRow(0, sprintf(
						"SELECT `url`, `fileurl`, `file`, `mimetype`, `size`, `meta` FROM `tbl_entries_data_%d` WHERE `entry_id` = %d",
						$this->get('id'),
						$entry_id
					));

					if (empty($row) === false) {
						$result = $row;
					}
				}

				// Found the file, add any missing meta information:
				if (file_exists($file) && is_readable($file)) {
					if (empty($result['mimetype'])) {
						$result['mimetype'] = General::getMimeType($file);
					}

					if (empty($result['size'])) {
						$result['size'] = filesize($file);
					}

					if (empty($result['meta'])) {
						$result['meta'] = serialize(self::getMetaInfo($file, $result['mimetype']));
					}
				}

				// The file was not found, or is unreadable:
				else {
					$message = __('The file uploaded is no longer available. Please check that it exists, and is readable.');
					$status = self::__INVALID_FIELDS__;
				}

				return $result;
			}

			if ($simulate && is_null($entry_id)) return $data;

			// Check to see if the entry already has a file associated with it:
			if (is_null($entry_id) === false) {
				$row = Symphony::Database()->fetchRow(0, sprintf(
					"SELECT * FROM `tbl_entries_data_%s` WHERE `entry_id` = %d LIMIT 1",
					$this->get('id'),
					$entry_id
				));

				var_dump($row);

				$existing_file = isset($row['file'])
					? $this->getFilePath($row['file'])
					: null;

				// File was removed:
				if (
					$data['error'] == UPLOAD_ERR_NO_FILE
					&& !is_null($existing_file)
					&& is_file($existing_file)
				) {
					General::deleteFile($existing_file);
				}
			}

			// Do not continue on upload error:
			if ($data['error'] == UPLOAD_ERR_NO_FILE || $data['error'] != UPLOAD_ERR_OK) {
				return false;
			}

			// Sanitize the filename
			$data['name'] = $temp_filename;

			$file = $this->getFilePath($data['name']);

			// if ($uploaded === false) {
			// 	$message = __(
			// 		'There was an error while trying to upload the file %1$s to the target directory %2$s.',
			// 		array(
			// 			'<code>' . $data['name'] . '</code>',
			// 			'<code>workspace/' . ltrim($rel_path, '/') . '</code>'
			// 		)
			// 	);
			// 	$status = self::__ERROR_CUSTOM__;

			// 	return false;
			// }

			// File has been replaced:
			if (
				isset($existing_file)
				&& $existing_file !== $file
				&& is_file($existing_file)
			) {
				General::deleteFile($existing_file);
			}

			// Get the mimetype, don't trust the browser. RE: #1609
			$data['type'] = General::getMimeType($file);

			return array(
				'url' => $data['url'],
				'fileurl' => $data['fileurl'],
				'file' =>		basename($file),
				'size' =>		$data['size'],
				'mimetype' =>	$data['type'],
				'meta' =>		serialize(self::getMetaInfo($file, $data['type']))
			);
		}

	/*-------------------------------------------------------------------------
		Output:
	-------------------------------------------------------------------------*/

		public function appendFormattedElement(&$wrapper, $data, $encode = false, $mode = null, $entry_id = null) {

			if(!is_array($data) || !isset($data['file']) || is_null($data['file'])){
				return;
			}

			$file = $this->getFilePath($data['file']);

			$element = new XMLElement($this->get('element_name'));
			$element->appendChild(new XMLElement('url', urlencode($data['url'])));

			$item = new XMLElement('image');
			$item->setAttributeArray(array(
				'size' =>	(
								file_exists($file)
								&& is_readable($file)
									? General::formatFilesize(filesize($file))
									: 'unknown'
							),
			 	'path' =>	General::sanitize(
								str_replace(WORKSPACE, NULL, dirname($file))
			 				),
				'type' =>	$data['mimetype']
			));

			$item->appendChild(new XMLElement('filename', General::sanitize(basename($file))));
			$item->appendChild(new XMLElement('fileurl', urlencode($data['fileurl'])));

			$m = unserialize($data['meta']);

			if(is_array($m) && !empty($m)){
				$item->appendChild(new XMLElement('meta', NULL, $m));
			}

			$element->appendChild($item);
			$wrapper->appendChild($element);
		}

	}
