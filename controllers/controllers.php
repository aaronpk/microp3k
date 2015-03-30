<?php

$app->get('/', function() use($app){
  $params = $app->request()->params();

  $user = db\find('users', ['domain' => $_SERVER['HTTP_HOST']]);

  if(!$user) {
    $html = render('index', 'default', [
      'title' => 'microp3k'
    ]);
  } else {

    $per_page = 10;

    $entries = ORM::for_table('entries')->where('user_id', $user->id);
    if(array_key_exists('before', $params)) {
      $entries->where_lte('published', $params['before']);
    }
    $entries = $entries->limit($per_page)->order_by_desc('published')->find_many();

    $newer = false;
    $older = false;

    if($entries) {
      $older = ORM::for_table('entries')->where('user_id', $user->id)
        ->where_lt('published', $entries[count($entries)-1]->published)->order_by_desc('published')->find_one();

      $newer = ORM::for_table('entries')->where('user_id', $user->id)
        ->where_gte('published', $entries[0]->published)->order_by_asc('published')->offset($per_page)->find_one();
    }

    $html = render('index', $user->theme, [
      'title' => $user->domain,
      'entries' => $entries,
      'user' => $user,
      'older' => ($older ? utc_date($older->published) : false),
      'newer' => ($newer ? utc_date($newer->published) : false)
    ]);
  }
  $app->response()->body($html);
});

$app->get('/entry/:entry_id', function($entry_id) use($app) {
  $params = $app->request()->params();

  $user = db\find('users', ['domain' => $_SERVER['HTTP_HOST']]);

  if(!$user) {
    $app->redirect('/');
  } else {
    $entry = db\find('entries', ['user_id' => $user->id, 'id' => $entry_id]);

    $html = render('single_entry', $user->theme, [
      'title' => $user->domain,
      'entry' => $entry,
      'user' => $user
    ]);
    $app->response()->body($html);
  }
});

