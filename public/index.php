<?php
    // main boilerplate
    define("__ROOT__", dirname(dirname(__FILE__)));
    
    // psr-4 autoloader
    require_once(__ROOT__."/vendor/autoload.php"); 

    // load project config (config.json)
    $config_str_contents = file_get_contents(__ROOT__."/config.json");
    $config_array = json_decode($config_str_contents, true);

    // setup db connection
    
    // converts array of associative arrays to a single associative array
    foreach ($config_array["dboptions"] as $item) {
        $config_db_options[array_keys($item)[0]] = $item[array_keys($item)[0]];
    }
    
    try {
        $pdo = new PDO($config_array["dsn"], $config_array["dbuser"], 
                       $config_array["dbpass"], $config_db_options);
    } catch (\PDOException $e) {
        throw new \PDOException($e->getMessage(), (int)$e->getCode());
    }


