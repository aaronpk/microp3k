<?php
class Config {
  public static $base_url = 'http://example.com';
  public static $hostname = 'example.com';
  public static $ssl = false;

  public static $dbHost = '127.0.0.1';
  public static $dbName = 'microp3k';
  public static $dbUsername = 'microp3k';
  public static $dbPassword = 'microp3k';

  public static $jwtSecret = 'xxx';

  public static $beanstalkServer = '127.0.0.1';
  public static $beanstalkPort = 11300;

  public static $defaultAuthorizationEndpoint = 'https://indieauth.com/auth';
  public static $pushHub = 'https://switchboard.p3k.io/';
}

