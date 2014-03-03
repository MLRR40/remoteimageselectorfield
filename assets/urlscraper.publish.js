
(function($) {

	/**
	 * This plugin add an interface for subsection management.
	 *
	 * @author: Nils Hörrmann, post@nilshoerrmann.de
	 * @source: http://github.com/nilshoerrmann/subsectionmanager
	 */
	$(document).ready(function() {

		// Language strings
		// Symphony.Language.add({
		// 	'There are no selected items': false,
		// 	'Are you sure you want to delete {$item}? It will be removed from all entries. This step cannot be undone.': false,
		// 	'There are currently no items available. Perhaps you want create one first?': false,
		// 	'New item': false,
		// 	'Search existing items': false,
		// 	'no matches': false,
		// 	'1 match': false,
		// 	'{$count} matches': false,
		// 	'Remove item': false
		// });

		$('div.field-urlscraper').each(
			function urlscraper() {
				var scraper = $(this);
				var duplicator = scraper.find('div.frame');
				var subsection_link = scraper.attr('data-subsection-new');
				var urlfield = scraper.find('input[type="text"]');

				var cache = {
					input: $('#url'),
					fetch: $('#fetch'),
					imageDiv: $('#images'),
					dataOut: null,
					proxyUrl: 'http://' + window.location.hostname + '/extensions/urlscraperfield/lib/proxy.php'
				};

				/*-------------------------------------------------------------------------
				Initialisation
				-------------------------------------------------------------------------*/
					
				duplicator.symphonyDuplicator({
					headers: 'header',
					constructable: duplicator.is('.constructable'),
					destructable: duplicator.is('.destructable'),
					collapsible: true,
					orderable: duplicator.is('.sortable'),
					maximum: (duplicator.is('.multiple') ? 1000 : 1),
					save_state: false
				});

				urlfield.on('change', null, function fetchUrl(event) {
					var item = $(this),
					
					contentDiv = duplicator.find('.content');
					cache.imageDiv = contentDiv;

					var url = urlfield.val();
					sendAjax(url);

					// Load url
					//iframe.addClass('initialise loading new').attr('src', url).load(function() {
					//	load(duplicator);
					//});
				});
				
				var load = function(item) {
					var header = item.find('> header'),
						content = item.find('> .content'),
						iframe = item.find('iframe'),
						subsection = iframe.contents(),
						body = subsection.find('body').addClass('inline subsection'),
						form = body.find('form').removeAttr('style').removeClass('columns'),
						init = true;

						iframe.removeClass('loading');

						var height = $(this).outerHeight();

						if(init == true || (!iframe.is('.loading') && content.data('height') !== height && height !== 0)) {
							resize(content, iframe, body, height);
						}
				}

				var resize = function(content, iframe, body, height) {
			
					// Set iframe height
					iframe.height(height).removeClass('loading');
					
					// Set scroll position
					//body[0].scrollTop = 0;
					//body[0].querySelector('#wrapper').scrollTop = 0;
					
					// Set content height
					content.data('height', height).animate({
						height: height
					}, 'fast');
				};

				var sendAjax = function(url) {
					var url = cache.proxyUrl + "?url=" + url;
					console.log(url);

					$.ajax({
						url: url,
						type: 'get',
						//dataType: 'json',
						success: function(data){   
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
			    	cache.imageDiv.append(
			    		"<img src='" + addHttp(value) + "'>"
			    	)
					});
				}

				var addHttp = function(url) {
					if (!/^(f|ht)tps?:\/\//i.test(url)) {
						url = "http://" + "www.openair.co.uk/" + url;
					}
					return url;
				}

			});
		
	});
	
})(jQuery.noConflict());
