<?php
// Default timezone
date_default_timezone_set('UTC');

ORM::configure('mysql:host=' . Config::$dbHost . ';dbname=' . Config::$dbName);
ORM::configure('username', Config::$dbUsername);
ORM::configure('password', Config::$dbPassword);

function render($page, $theme, $data) {
  global $app;
  return $app->render('themes/'.$theme.'/layout.php', array_merge($data, array(
    'page' => 'themes/'.$theme.'/'.$page,
    'page_name' => $page
  )));
};

function partial($template, $theme, $data=[], $debug=false) {
  global $app;

  if($debug) {
    $tpl = new Savant3(\Slim\Extras\Views\Savant::$savantOptions);
    echo '<pre>' . $tpl->fetch('themes/'.$theme.'/'.$template.'.php') . '</pre>';
    return '';
  }

  ob_start();
  $tpl = new Savant3(\Slim\Extras\Views\Savant::$savantOptions);
  foreach($data as $k=>$v) {
    $tpl->{$k} = $v;
  }
  $tpl->display('themes/'.$theme.'/'.$template.'.php');
  return ob_get_clean();
}

function api_response(&$app, $data, $status=200) {
  $app->response()->status($status);
  $headers = \getallheaders();
  if(k($headers, 'Accept') == 'application/json') {
    $app->response()['Content-Type'] = 'application/json';
    if(is_string($data)) {
      $data = ['description' => $data];
    }
    $app->response()->body(json_encode($data));
  } else {
    $app->response()['Content-Type'] = 'application/x-www-form-encoded';
    if(is_string($data)) {
      $app->response()->body($data);
    } else {
      $app->response()->body(http_build_query($data));
    }
  }
}

function k($a, $k, $default=null) {
  if(is_array($k)) {
    $result = true;
    foreach($k as $key) {
      $result = $result && array_key_exists($key, $a);
    }
    return $result;
  } else {
    if(is_array($a) && array_key_exists($k, $a) && $a[$k])
      return $a[$k];
    elseif(is_object($a) && property_exists($a, $k) && $a->$k)
      return $a->$k;
    else
      return $default;
  }
}

function bs()
{
  static $pheanstalk;
  if(!isset($pheanstalk)) {
    $pheanstalk = new Pheanstalk\Pheanstalk(Config::$beanstalkServer, Config::$beanstalkPort);
  }
  return $pheanstalk;
}

function friendly_url($url) {
  return preg_replace(['/https?:\/\//','/\/$/'],'',$url);
}

function friendly_date($date_string, $tz_offset) {
  $date = new DateTime($date_string);
  if($tz_offset > 0)
    $date->add(new DateInterval('PT'.$tz_offset.'S'));
  elseif($tz_offset < 0)
    $date->sub(new DateInterval('PT'.abs($tz_offset).'S'));
  $tz = ($tz_offset < 0 ? '-' : '+') . sprintf('%02d:%02d', abs($tz_offset/60/60), ($tz_offset/60)%60);
  return $date->format('F j, Y g:ia') . ' ' . $tz;
}

function utc_date($date_string) {
  $date = new DateTime($date_string);
  return $date->format('Y-m-d\TH:i:s\Z');
}

function build_url($parsed_url) { 
  $scheme   = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : ''; 
  $host     = isset($parsed_url['host']) ? $parsed_url['host'] : ''; 
  $port     = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : ''; 
  $user     = isset($parsed_url['user']) ? $parsed_url['user'] : ''; 
  $pass     = isset($parsed_url['pass']) ? ':' . $parsed_url['pass']  : ''; 
  $pass     = ($user || $pass) ? "$pass@" : ''; 
  $path     = isset($parsed_url['path']) ? $parsed_url['path'] : ''; 
  $query    = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : ''; 
  $fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : ''; 
  return "$scheme$user$pass$host$port$path$query$fragment"; 
} 

// Input: Any URL or string like "aaronparecki.com"
// Output: Normlized URL (default to http if no scheme, force "/" path)
//         or return false if not a valid URL
function normalize_url($url) {
  $me = parse_url($url);

  if(array_key_exists('path', $me) && $me['path'] == '')
    return false;

  // parse_url returns just "path" for naked domains
  if(count($me) == 1 && array_key_exists('path', $me)) {
    $me['host'] = $me['path'];
    unset($me['path']);
  }

  if(!array_key_exists('scheme', $me))
    $me['scheme'] = 'http';

  if(!array_key_exists('path', $me))
    $me['path'] = '/';

  // Invalid scheme
  if(!in_array($me['scheme'], array('http','https')))
    return false;

  return build_url($me);
}

if(!function_exists('getallheaders')) {
  function getallheaders()
  {
    $headers = '';
    foreach($_SERVER as $name=>$value) { 
      if(substr($name, 0, 5) == 'HTTP_') { 
        $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value; 
      } 
    } 
    return $headers; 
  } 
}
