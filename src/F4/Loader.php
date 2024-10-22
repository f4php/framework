<?php

namespace F4;

use Composer\Script\Event;

class Loader {
  
  public static function loadConfigurationFile($environment) {
    $composerConfiguration = json_decode(json: file_get_contents(filename: __DIR__."../../../../../composer.json"), associative: true, flags: JSON_THROW_ON_ERROR);
    echo $composerConfiguration["extra"]["F4"]["configs"][$_SERVER["ENVIRONMENT"]??'default'] ?? null;
  }

  public static function createConfigurationFile(Event $event, ?string $filename=null) {
    var_dump($event->getArguments());
    
  }

}