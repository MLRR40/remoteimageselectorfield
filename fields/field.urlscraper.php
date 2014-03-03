<?php

	if (!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');

	require_once(TOOLKIT . '/fields/field.upload.php');

	require_once(CORE . '/class.cacheable.php');
	require_once(EXTENSIONS . '/urlscraperfield/extension.driver.php');

	Class FieldUrlScraper extends FieldUpload {

		private $_filter_origin = array();

		public function __construct(){
			parent::__construct();
			$this->_name = 'Url Scraper';
		}

	/*-------------------------------------------------------------------------
		Definition:
	-------------------------------------------------------------------------*/

		public function mustBeUnique(){
			return true;
		}

		public function canFilter(){
			return true;
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
		Utilities:
	-------------------------------------------------------------------------*/

		private function __geocodeAddress($address, $can_return_default=true) {
			$coordinates = null;

			$cache_id = md5('maplocationfield_' . $address);
			$cache = new Cacheable(Symphony::Database());
			$cachedData = $cache->check($cache_id);

			// no data has been cached
			if(!$cachedData) {

				include_once(TOOLKIT . '/class.gateway.php');

				$ch = new Gateway;
				$ch->init();
				$ch->setopt('URL', 'http://maps.googleapis.com/maps/api/geocode/json?address='.urlencode($address).'&sensor=false');
				$response = json_decode($ch->exec());

				$coordinates = $response->results[0]->geometry->location;

				if ($coordinates && is_object($coordinates)) {
					$cache->write($cache_id, $coordinates->lat . ', ' . $coordinates->lng, $this->_geocode_cache_expire); // cache lifetime in minutes
				}

			}
			// fill data from the cache
			else {
				$coordinates = $cachedData['data'];
			}

			// coordinates is an array, split and return
			if ($coordinates && is_object($coordinates)) {
				return $coordinates->lat . ', ' . $coordinates->lng;
			}
			// return comma delimeted string
			elseif ($coordinates) {
				return $coordinates;
			}
			// return default coordinates
			elseif ($return_default) {
				return $this->_default_coordinates;
			}
		}

	/*-------------------------------------------------------------------------
		Settings:
	-------------------------------------------------------------------------*/
//private $supported_upload_fields = array('upload', 'uniqueupload', 'signedfileupload', 'image_upload');

		public function displaySettingsPanel(&$wrapper, $errors=NULL){
			parent::displaySettingsPanel($wrapper, $errors);

			// Get current section id
			//$section_id = Symphony::Engine()->Page->_context[1];

			// $div = new XMLElement('div', null, array('class' => 'two columns'));

			//  url
			// $label = Widget::Label(__('URL'));
			// $label->appendChild(Widget::Input('fields['.$this->get('sortorder').'][url]', $this->get('url')));
			// if(isset($errors['url'])) {
			// 	$wrapper->appendChild(Widget::wrapFormElementWithError($label, $errors['url']));
			// } else {
			// 	$wrapper->appendChild($label);
			// };

	// related field
	// $label = Widget::Label(__('Related upload field'), NULL);
	// $fields = FieldManager::fetch(NULL, $section_id, 'ASC', 'sortorder', NULL, NULL, sprintf("AND (type IN ('%s'))", implode("', '", $this->supported_upload_fields)));
	// $options = array(
	// 	array('', false, __('None Selected'), ''),
	// );
	// $attributes = array(
	// 	array()
	// );
	// if (is_array($fields) && !empty($fields)) {
	// 	foreach ($fields as $field) {
	// 		$options[] = array($field->get('id'), ($field->get('id') == $this->get('related_field_id')), $field->get('label'));
	// 	}
	// };
	// $label->appendChild(Widget::Select('fields['.$this->get('sortorder').'][related_field_id]', $options));
	// if(isset($errors['related_field_id'])) {
	// 	$wrapper->appendChild(Widget::wrapFormElementWithError($label, $errors['related_field_id']));
	// } else {
	// 	$wrapper->appendChild($label);
	// };

			//$wrapper->appendChild($div);

			//$label = Widget::Label('Default Marker Location');
			//$label->appendChild(Widget::Input('fields['.$this->get('sortorder').'][default_location]', $this->get('default_location')));
			//$wrapper->appendChild($label);

			//$label = Widget::Label('Default Zoom Level');
			//$label->appendChild(Widget::Input('fields['.$this->get('sortorder').'][default_zoom]', $this->get('default_zoom')));
			//$wrapper->appendChild($label);

			//$this->appendShowColumnCheckbox($wrapper);
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

		// public function commit(){
		// 	if(!parent::commit()) return false;

		// 	$id = $this->get('id');

		// 	if($id === false) return false;

		// 	$fields = array();

		// 	$fields['field_id'] = $id;
			//$fields['default_location'] = $this->get('default_location');
			//$fields['default_zoom'] = $this->get('default_zoom');

			//if(!$fields['default_location']) $fields['default_location'] = $this->_default_location;
			//$fields['default_location_coords'] = self::__geocodeAddress($fields['default_location']);

			//if(!$fields['default_zoom']) $fields['default_zoom'] = $this->_default_zoom;

			//Symphony::Database()->query("DELETE FROM `tbl_fields_".$this->handle()."` WHERE `field_id` = '$id' LIMIT 1");

			//return Symphony::Database()->insert($fields, 'tbl_fields_' . $this->handle());
		//}

	/*-------------------------------------------------------------------------
		Publish:
	-------------------------------------------------------------------------*/

		public function displayPublishPanel(&$wrapper, $data=NULL, $flagWithError=NULL, $fieldnamePrefix=NULL, $fieldnamePostfix=NULL, $entry_id=NULL){
			if (class_exists('Administration') && Administration::instance()->Page) {
				Administration::instance()->Page->addStylesheetToHead(URL . '/extensions/urlscraperfield/assets/urlscraper.publish.css', 'screen', 78);
				Administration::instance()->Page->addScriptToHead(URL . '/extensions/urlscraperfield/assets/urlscraper.publish.js', 80);
			}
			
			// input values
			//$coordinates = array($data['latitude'], $data['longitude']);
			//$centre = (string)$data['centre'];
			//$zoom = (string)$data['zoom'];

			// get defaults for new entries
			//if (reset($coordinates) === null) $coordinates = explode(',', $this->get('default_location_coords'));
			//if (empty($centre)) $centre = $this->get('default_location_coords');
			//if (empty($zoom)) $zoom = $this->get('default_zoom');

//			$label = Widget::Label('Marker Latitude/Longitude');
//			$label->setAttribute('class', 'coordinates');
//			$label->appendChild(Widget::Input('fields'.$fieldnamePrefix.'['.$this->get('element_name').'][coordinates]'.$fieldnamePostfix, join(', ', $coordinates)));
//			$wrapper->appendChild($label);

//			$label = Widget::Label('Centre Latitude/Longitude');
//			$label->setAttribute('class', 'centre');
//			$label->appendChild(Widget::Input('fields'.$fieldnamePrefix.'['.$this->get('element_name').'][centre]'.$fieldnamePostfix, $centre));
//			$wrapper->appendChild($label);

			$label = Widget::Label('URL');
			$label->setAttribute('class', 'product-url');
			$label->appendChild(Widget::Input('fields'.$fieldnamePrefix.'['.$this->get('element_name').'][url]'.$fieldnamePostfix, ''));
			$wrapper->appendChild($label);

			$divframe = new XMLElement('div', NULL, array('class' => 'dark frame editable searchable'));
			$divcontent = new XMLElement('div', NULL, array('class' => 'content'));
			$iframe = new XMLElement('iframe');
			
			$divcontent->appendChild($iframe);
			$divframe->appendChild($divcontent);
			
			$wrapper->appendChild($divframe);
							// <div class="dark frame editable searchable">
							// 	<ol>
							// 	<li data-value="6485" class="instance">
							// 		<input type="hidden" value="6485" />
							// 		<header>British Models - 2014's New Faces</header>
							// 		<div class="content">
										
							// 			<iframe></iframe>

							// 		</div>
							//  	</li>
							// 	</ol>
							// </div>			
		}

		public function processRawFieldData($data, &$status, $simulate=false, $entry_id=NULL){
			$status = self::__OK__;

			if (is_array($data)) {
				$coordinates = split(',', $data['coordinates']);
				return array(
					'latitude' => trim($coordinates[0]),
					'longitude' => trim($coordinates[1]),
					'centre' => $data['centre'],
					'zoom' => $data['zoom'],
				);
			}
			else {
				// if data is an address, geocode it to lat/lon first
				if (!preg_match('/^(-?[.0-9]+),\s?(-?[.0-9]+)$/', $data)) {
					$data = self::__geocodeAddress($data);
				}

				$coordinates = split(',', $data);
				return array(
					'latitude' => trim($coordinates[0]),
					'longitude' => trim($coordinates[1]),
					'centre' => $data,
					'zoom' => $this->get('default_zoom')
				);
			}
		}

	/*-------------------------------------------------------------------------
		Output:
	-------------------------------------------------------------------------*/

		public function appendFormattedElement(&$wrapper, $data, $encode = false, $mode = null, $entry_id = null) {
			$field = new XMLElement($this->get('element_name'), null, array(
				'latitude' => $data['latitude'],
				'longitude' => $data['longitude'],
			));

			$map = new XMLElement('map', null, array(
				'zoom' => $data['zoom'],
				'centre' => $data['centre']
			));
			$field->appendChild($map);

			if (count($this->_filter_origin['latitude']) > 0) {
				$distance = new XMLElement('distance');
				$distance->setAttribute('from', $this->_filter_origin['latitude'] . ',' . $this->_filter_origin['longitude']);
				$distance->setAttribute('distance', extension_maplocationfield::geoDistance($this->_filter_origin['latitude'], $this->_filter_origin['longitude'], $data['latitude'], $data['longitude'], $this->_filter_origin['unit']));
				$distance->setAttribute('unit', ($this->_filter_origin['unit'] == 'k') ? 'km' : 'miles');
				$field->appendChild($distance);
			}

			$wrapper->appendChild($field);
		}

		public function prepareTableValue($data, XMLElement $link = null, $entry_id = null) {
			if (empty($data)) return;

			$zoom = (int)$data['zoom'] - 2;
			if ($zoom < 1) $zoom = 1;

			return sprintf(
				"<img src='http://maps.google.com/maps/api/staticmap?center=%s&zoom=%d&size=160x90&sensor=false&markers=color:red|size:small|%s' alt=''/>",
				$data['centre'],
				$zoom,
				implode(',', array($data['latitude'], $data['longitude']))
			);
		}

	/*-------------------------------------------------------------------------
		Filtering:
	-------------------------------------------------------------------------*/

		public function buildDSRetrievalSQL($data, &$joins, &$where, $andOperation=false){
			// Symphony by default splits filters by commas. We want commas, so
			// concatenate filters back together again putting commas back in
			$data = join(',', $data);

			/*
			within 20 km of 10.545, -103.1
			within 2km of 1 West Street, Burleigh Heads
			within 500 miles of England
			*/

			// is a "within" radius filter
			if(preg_match('/^within/i', $data)){
				$field_id = $this->get('id');

				// parse out individual filter parts
				preg_match('/^within ([0-9]+)\s?(km|mile|miles) of (.+)$/', $data, $filters);

				$radius = trim($filters[1]);
				$unit = strtolower(trim($filters[2]));
				$origin = trim($filters[3]);

				$lat = null;
				$lng = null;

				// is a lat/long pair
				if (preg_match('/^(-?[.0-9]+),\s?(-?[.0-9]+)$/', $origin, $latlng)) {
					$lat = $latlng[1];
					$lng = $latlng[2];
				}
				// otherwise the origin needs geocoding
				else {
					$geocode = $this->__geocodeAddress($origin);
					if ($geocode) $geocode = explode(',', $geocode);
					$lat = trim($geocode[0]);
					$lng = trim($geocode[1]);
				}

				// if we don't have a decent set of coordinates, we can't query
				if (is_null($lat) || is_null($lng)) return;

				$this->_filter_origin['latitude'] = $lat;
				$this->_filter_origin['longitude'] = $lng;
				$this->_filter_origin['unit'] = $unit[0];

				// build the bounds within the query should look
				$radius = extension_maplocationfield::geoRadius($lat, $lng, $radius, ($unit[0] == 'k'));

				$where .= sprintf(
					" AND `t%d`.`latitude` BETWEEN %s AND %s AND `t%d`.`longitude` BETWEEN %s AND %s",
					$field_id, $radius['latMIN'], $radius['latMAX'],
					$field_id, $radius['lonMIN'], $radius['lonMAX']
				);

				$joins .= " LEFT JOIN `tbl_entries_data_$field_id` AS `t$field_id` ON (`e`.`id` = `t$field_id`.entry_id) ";

			}

			return true;
		}

	}
