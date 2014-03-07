
(function($) {

	/**
	 * This plugin adds a field to allow retrieving images from an external URL
	 *
	 * @authors: David Allen, James Lambie @ We Are Daddy
	 * @source: http://github.com/jimlambie/remoteimageselectorfield

	 * @credits: image preview & getDimensions function lifted from https://github.com/symphonists/image_index_preview
	 */
	$(document).ready(function() {

		var root, page, link, path, file, size, external;
		var defaultSize = 140;

		root = Symphony.Context.get('root');
		page = Symphony.Context.get('env')['page'];		

		$('div.field-remoteimageselector').each(
			function remoteimageselector() {
				var selector = $(this);
				var selectorFrame = selector.find('div.frame');
				var urlfield = selector.find('input[type="text"]');
	
				var settings = {
					input: selector.find('input[type="text"]'),
					slider: $('.bxslider'),
					proxyUrl: 'http://' + window.location.hostname + '/extensions/remoteimageselectorfield/lib/proxy.php',
					saveURL: selector.find('input[type="hidden"]')
				};

				/*-------------------------------------------------------------------------
				Initialisation
				-------------------------------------------------------------------------*/
					
				selectorFrame.symphonyDuplicator({
					headers: 'header',
					constructable: selectorFrame.is('.constructable'),
					destructable: selectorFrame.is('.destructable'),
					collapsible: true,
					orderable: selectorFrame.is('.sortable'),
					maximum: (selectorFrame.is('.multiple') ? 1000 : 1),
					save_state: false
				});

				settings.input.on('change', null, function fetchUrl(event) {
					selectorFrame.addClass('open');
					var url = $(this).val();
					sendAjax(url);
				});

				// Image Preview
				var previewImage = function() {

					link = selector.find('a.image');
					
					if (link.length == 0) return;
					
					if (link.attr('href').indexOf('/workspace/') >= 0) {
						if (page == 'index') {
							if (link.data('path') != null) {
								path = link.data('path');
								filename = link.text();
								file = path.replace(root, '').replace('/workspace/','') + '/' + filename;
								full_filepath = root + path + '/' + filename;
							}
						}
						else {
							path = link.attr('href');
							file = path.replace(root, '').replace('/workspace/','');
							full_filepath = path;
						}
						external = 0;
					}
					else {
						path = link.attr('href');
						file = path.substr(path.indexOf('://')+3);
						full_filepath = path;
						external = 1;
					}

					if (path && file.match(/\.(?:bmp|gif|jpe?g|png)$/i)) {
						// remove file name
						link.text('');
						// add image
						getDimensions(full_filepath, link, file, external);
					}

				};

				var overrideRemoveFile = function() {

					$remove_link = selector.find('label.file:has(a) span.frame em');
					$remove_link.unbind('click.admin');
					$remove_link.on('click.admin', function(event) {	
					 	// Prevent clickthrough
						event.preventDefault();
						// remove the existing image
						$(this).parent().empty();
						// fire change on it
						urlfield.trigger('change');
					});
				};
				
				var resize = function(content, height) {
					// Set content height
					content.css('height', 'auto');
					content.data('height', height).animate({
						height: height
					}, 'fast');
				};

				var sendAjax = function(url) {
					var url = settings.proxyUrl + "?url=" + url;
					console.log(url);

					$.ajax({
						url: url,
						type: 'get',
					
						success: function(data){   
							console.log(data);
							selectorFrame.find('.content').removeClass('loading');
							parseData(data);
						},
						error:function(){
						  console.log('error');
						}   
					}); 

				};

				var parseData = function(data) {
					var parse = $.parseJSON(data);
					displayImages(parse);
				}

				var displayImages = function(data) {
					
					$.each(data, function(key, value) {
				    	settings.slider.append(
				    		"<li><img class='slider_image' src='" + addHttp(value) + "'></li>"
				    	)
					});

					settings.slider.bxSlider({	
						mode: 'horizontal',
					  	minSlides: 2,
						maxSlides: 2,
						slideWidth: 300,
						useCss: false,
						adaptiveHeight:true,
						infiniteLoop: false
					});

					initSelection();

					var height = settings.slider.outerHeight();
					resize(selectorFrame.find('.content'), height);
				}

				var addHttp = function(url) {
					if (!/^(f|ht)tps?:\/\//i.test(url)) {
						url = "http://" + url;
					}
					return url;
				}

				var initSelection = function() {
					var images = $('.slider_image');

					images.on('click', function() {
						images.each(function() {
							$(this).removeClass('selected');
						});
						setSelection.call(this);
					})
				}

				var setSelection = function(element) {
					$(this).addClass('selected');
					settings.saveURL.val($(this).attr('src'));
				}

				var getDimensions = function(src, link, file, external) {
					var ratio, w, h, external = external || 0;
					img = document.createElement('img');
					img.src = src;

					img.onload = function() {
						w = this.width;
						h = this.height;

						if (h > w) {
							 ratio = w / h;
							 size = parseInt(defaultSize * ratio) + '/' + 0;
						}
						else {
							 ratio = h / w;
							 size = 0 + '/' + parseInt(defaultSize * ratio);
						}

						// add preview
						$('<img />', {
							src: root + '/image/1/' + size + '/' + external + '/' + file
						}).prependTo(link);
					}
				}

				previewImage();
				overrideRemoveFile();

			});
		
	});
	
})(jQuery.noConflict());
