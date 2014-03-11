### XML Output

	<product-url>
  		<url>http://www.openair.co.uk/</url>
	  	<image size="4 KB" path="/uploads/images" type="image/jpeg">
      		<filename>keen_original_hybrid.jpg</filename>
      		<fileurl>http://www.openair.co.uk//clientuploads/openAir/uploads/radEditor/images/keen_original_hybrid.jpg</fileurl>
      		<meta creation="2014-03-11T14:38:22+00:00" width="110" height="110" />
  		</image>
	</product-url>
	
### Template
	<img src="{product-url/image/@path}/{product-url/image/filename}" />