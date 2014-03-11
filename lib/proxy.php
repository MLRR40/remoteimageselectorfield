<?PHP
require_once('parser.php');

// Script: Simple PHP Proxy: Get external HTML, JSON and more!
//
// *Version: 1.6, Last updated: 1/24/2009*
// 
// Project Home - http://benalman.com/projects/php-simple-proxy/
// GitHub       - http://github.com/cowboy/php-simple-proxy/
// Source       - http://github.com/cowboy/php-simple-proxy/raw/master/ba-simple-proxy.php
// 
// About: License
// 
// Copyright (c) 2010 "Cowboy" Ben Alman,
// Dual licensed under the MIT and GPL licenses.
// http://benalman.com/about/license/
// 
// About: Examples
// 
// This working example, complete with fully commented code, illustrates one way
// in which this PHP script can be used.
// 
// Simple - http://benalman.com/code/projects/php-simple-proxy/examples/simple/
// 
// About: Release History
// 
// 1.6 - (1/24/2009) Now defaults to JSON mode, which can now be changed to
//       native mode by specifying ?mode=native. Native and JSONP modes are
//       disabled by default because of possible XSS vulnerability issues, but
//       are configurable in the PHP script along with a url validation regex.
// 1.5 - (12/27/2009) Initial release
// 
// Topic: GET Parameters
// 
// Certain GET (query string) parameters may be passed into ba-simple-proxy.php
// to control its behavior, this is a list of these parameters. 
// 
//   url - The remote URL resource to fetch. Any GET parameters to be passed
//     through to the remote URL resource must be urlencoded in this parameter.
//   mode - If mode=native, the response will be sent using the same content
//     type and headers that the remote URL resource returned. If omitted, the
//     response will be JSON (or JSONP). <Native requests> and <JSONP requests>
//     are disabled by default, see <Configuration Options> for more information.
//   callback - If specified, the response JSON will be wrapped in this named
//     function call. This parameter and <JSONP requests> are disabled by
//     default, see <Configuration Options> for more information.
//   user_agent - This value will be sent to the remote URL request as the
//     `User-Agent:` HTTP request header. If omitted, the browser user agent
//     will be passed through.
//   send_cookies - If send_cookies=1, all cookies will be forwarded through to
//     the remote URL request.
//   send_session - If send_session=1 and send_cookies=1, the SID cookie will be
//     forwarded through to the remote URL request.
//   full_headers - If a JSON request and full_headers=1, the JSON response will
//     contain detailed header information.
//   full_status - If a JSON request and full_status=1, the JSON response will
//     contain detailed cURL status information, otherwise it will just contain
//     the `http_code` property.
// 
// Topic: POST Parameters
// 
// All POST parameters are automatically passed through to the remote URL
// request.
// 
// Topic: JSON requests
// 
// This request will return the contents of the specified url in JSON format.
// 
// Request:
// 
// > ba-simple-proxy.php?url=http://example.com/
// 
// Response:
// 
// > { "contents": "<html>...</html>", "headers": {...}, "status": {...} }
// 
// JSON object properties:
// 
//   contents - (String) The contents of the remote URL resource.
//   headers - (Object) A hash of HTTP headers returned by the remote URL
//     resource.
//   status - (Object) A hash of status codes returned by cURL.
// 
// Topic: JSONP requests
// 
// This request will return the contents of the specified url in JSONP format
// (but only if $enable_jsonp is enabled in the PHP script).
// 
// Request:
// 
// > ba-simple-proxy.php?url=http://example.com/&callback=foo
// 
// Response:
// 
// > foo({ "contents": "<html>...</html>", "headers": {...}, "status": {...} })
// 
// JSON object properties:
// 
//   contents - (String) The contents of the remote URL resource.
//   headers - (Object) A hash of HTTP headers returned by the remote URL
//     resource.
//   status - (Object) A hash of status codes returned by cURL.
// 
// Topic: Native requests
// 
// This request will return the contents of the specified url in the format it
// was received in, including the same content-type and other headers (but only
// if $enable_native is enabled in the PHP script).
// 
// Request:
// 
// > ba-simple-proxy.php?url=http://example.com/&mode=native
// 
// Response:
// 
// > <html>...</html>
// 
// Topic: Notes
// 
// * Assumes magic_quotes_gpc = Off in php.ini
// 
// Topic: Configuration Options
// 
// These variables can be manually edited in the PHP file if necessary.
// 
//   $enable_jsonp - Only enable <JSONP requests> if you really need to. If you
//     install this script on the same server as the page you're calling it
//     from, plain JSON will work. Defaults to false.
//   $enable_native - You can enable <Native requests>, but you should only do
//     this if you also whitelist specific URLs using $valid_url_regex, to avoid
//     possible XSS vulnerabilities. Defaults to false.
//   $valid_url_regex - This regex is matched against the url parameter to
//     ensure that it is valid. This setting only needs to be used if either
//     $enable_jsonp or $enable_native are enabled. Defaults to '/.*/' which
//     validates all URLs.
// 
// ############################################################################

// Change these configuration options if needed, see above descriptions for info.
$enable_jsonp    = false;
$enable_native   = false;
$valid_url_regex = '/.*/';

// ############################################################################

$url = $_GET['url'];

if ( !$url ) {

  // Passed url not specified.
  $contents = 'ERROR: url not specified';
  $status = array( 'http_code' => 'ERROR' );
  echo $status; exit;

} else if ( !preg_match( $valid_url_regex, $url ) ) {

  // Passed url doesn't match $valid_url_regex.
  $contents = 'ERROR: invalid url';
  $status = array( 'http_code' => 'ERROR' );


} else {
  $ch = curl_init( $url );

  if ( strtolower($_SERVER['REQUEST_METHOD']) == 'post' ) {
  curl_setopt( $ch, CURLOPT_POST, true );
  curl_setopt( $ch, CURLOPT_POSTFIELDS, $_POST );
  }

  curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
  curl_setopt( $ch, CURLOPT_HEADER, true );
  curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );


  list( $header, $contents ) = preg_split( '/([\r\n][\r\n])\\1/', curl_exec( $ch ), 2 );

  $status = curl_getinfo( $ch );

  curl_close( $ch );

}

  // Split header text into an array.
  $header_text = preg_split( '/[\r\n]+/', $header );

  $html = str_get_html($contents);

  $parsed_url = parse_url($url);
  $baseurl = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : ''; 
  $baseurl = $baseurl . isset($parsed_url['host']) ? $parsed_url['host'] : '';  

  $image = array();
  $open_graph = array();

  /*********************************
  /*  Image Grabbing
  /*  We grab the og:image first because it's the first choice from 
  /*  the scraped website, then we grab all the images and merge the arrays
  /*********************************/
  if( $html->find('meta[name=og:image]') ){
    
    foreach($html->find('meta[name=og:image]') as $element) {
    
      $open_graph["0"] = $element->attr['content'];

    }
  }

  $index = 1;

  foreach($html->find('img') as $element) {
    
    $image[$index] = $element->src;
    $index++;

  }

  // now lets remove all non-links
  foreach($image as $key => $value) {
    if (!filter_var($value, FILTER_VALIDATE_URL, FILTER_FLAG_HOST_REQUIRED)) {
      $image[$key] = $baseurl.'/'.$value;
    }
    elseif(!filter_var($value, FILTER_VALIDATE_URL)) {
      unset($image[$key]);
    }
  }

  // custom filters to allow loading of jpegs first as they are probably more relevant
  function find_jpg($url) {

    $extensions = array("jpg", "jpeg");

      if(!is_array($extensions)) $extensions = array($extensions);
      foreach($extensions as $extension) {
          if( ($pos = strpos($url, $extension))!== false ){ 
            return $url;
          }
      }
      return false;
  }

  function find_else($url) {

    $extensions = array("jpg", "jpeg");

      if(!is_array($extensions)) $extensions = array($extensions);
      foreach($extensions as $extension) {
          if( ($pos = strpos($url, $extension))!== false ){ 
            return false;
          }
      }

      return $url;
    }

  // filtering arrays!
  $jpg_array = array_filter($image, "find_jpg");
  $non_jpg_array = array_filter($image, "find_else");

  //merge arrays to show og:image first, followed by all other jpgs, then all other image types (possibly icons and stuff)
  $results = array_merge($open_graph, $jpg_array, $non_jpg_array);

  echo json_encode($results, JSON_FORCE_OBJECT);

  ?>