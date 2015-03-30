<?php

function validate_access_token(&$app) {
  $params = $app->request()->params();

  // Look up the token provided
  $headers = \getallheaders();
  $tokenString = false;
  if(k($headers, 'Authorization') && preg_match('/Bearer (.+)/', $headers['Authorization'], $match)) {
    $tokenString = $match[1];
  } elseif(k($params, 'access_token')) {
    $tokenString = k($params, 'access_token');
  }

  if(!$tokenString) {
    return api_response($app, ['error' => 'Missing access token'], 401);
  }

  $token = db\find('tokens', ['token' => $tokenString]);
  if(!$token) {
    return api_response($app, ['error' => 'Invalid access token'], 403);
  }

  if($token->date_expires && strtotime($token->date_expires) < time()) {
    return api_response($app, ['error' => 'Access token expired'], 401);
  }

  return db\get_by_id('users', $token->user_id);
}

$app->post('/micropub', function() use($app){
  $params = $app->request()->params();

  $user = validate_access_token($app);

  // Verify all required parameters exist
  
  if(k($params, 'h') != 'entry') {
    return api_response($app, ['error' => 'This endpoint can only create h-entry posts. Please make sure you have provided a parameter h=entry'], 400);
  }

  if(k($params, 'content') == '') {
    return api_response($app, ['error' => 'No content was provided'], 400);
  }

  $entry = db\create('entries');
  $entry->user_id = $user->id;
  $entry->published = date('Y-m-d H:i:s');

  if(k($params, 'in-reply-to'))
    $entry->in_reply_to = $params['in-reply-to'];

  $entry->name = k($params, 'name');
  $entry->content = k($params, 'content');

  $entry->save();

  $location = 'http://' . $user->domain . '/entry/' . $entry->id;

  $app->response()['Location'] = $location;
  api_response($app, 'The post was created successfully! You can see it here:'."\n".$location, 201);
});


