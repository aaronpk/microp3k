<?php

class DeferredTask {

  public static $tubeName = 'microp3k-worker';
  
  public static function run() {
    global $pcntl_continue;

    echo "PID " . posix_getpid() . " watching tube: " . self::$tubeName . "\n";
    bs()->watch(self::$tubeName)->ignore('default');

    if(isset($pcntl_continue)) {

      while($pcntl_continue)
      {
        if(($job=bs()->reserve(2)) == FALSE)
          continue;

        self::process($job);
      } // while true

      echo "\nBye from pid " . posix_getpid() . "!\n";

    } else {
      if(($job=bs()->reserve())) {
        self::process($job);
      }  
    }
  }

  public static function run_once() {
    echo "PID " . posix_getpid() . " watching tube: " . self::$tubeName . "\n";
    bs()->watch(self::$tubeName)->ignore('default');

    if(($job=bs()->reserve())) {
      self::process($job);
    }  

    echo "\nBye from pid " . posix_getpid() . "!\n";
  }

  public static function queue($class, $method, $args=array(), $delay=0) {
    if(!is_array($args))
      $args = array($args);

    bs()->putInTube(self::$tubeName,
      json_encode(array('class'=>$class, 'method'=>$method, 'args'=>$args)),
      1024,    // priority
      $delay,  // delay
      300);    // time to run
  }
  
  private static function process(&$jobData) {
    $data = json_decode($jobData->getData());

    if(!is_object($data) || !property_exists($data, 'class')) {
      echo "Found bad job:\n";
      print_r($data);
      echo "\n";
      bs()->delete($jobData);
      return;
    }

    echo "===============================================\n";
    echo "# Beginning job: " . $data->class . '::' . $data->method . "\n";

    call_user_func_array(array($data->class, $data->method), $data->args);
    
    echo "\n# Job Complete\n-----------------------------------------------\n\n";
    bs()->delete($jobData);
  }

}