<?php

class PublishTask {

  public static function publish($entry_id) {

    $entry = db\get_by_id('entries', $entry_id);
    $user = db\get_by_id('users', $entry->user_id);

    DeferredTask::queue('PublishTask', 'notify_hub', [Config::$pushHub, 'http://' . $user->domain . '/']);

    $source_url = 'http://' . $user->domain . '/entry/' . $entry_id;
    DeferredTask::queue('PublishTask', 'send_webmention', [$source_url, $entry->in_reply_to]);
  }

  public static function notify_hub($hub_url, $topic_url) {
    // Notify the PuSH hub of the update
    $response = request\post($hub_url, [
      'hub.mode' => 'publish',
      'hub.topic' => $topic_url
    ]);
    print_r($response);
  }

  public static function send_webmention($source_url, $target_url) {
    // Send a webmention to the reply URL
    $client = new IndieWeb\MentionClient($source_url);
    $client->sendSupportedMentions($target_url);
  }

}
