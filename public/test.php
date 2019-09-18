<?php
// main boilerplate
define("__ROOT__", dirname(dirname(__FILE__)));

// psr-4 autoloader
require_once(__ROOT__."/vendor/autoload.php"); 

// load project config (config.json)
$config_str_contents = file_get_contents(__ROOT__."/config.json");
$config_array = json_decode($config_str_contents, true);

// setup db connection

try {
    $pdo = new PDO($config_array["dsn"], $config_array["dbuser"], 
                   $config_array["dbpass"], array(
                   PDO::ATTR_EMULATE_PREPARES=>false,
                   PDO::MYSQL_ATTR_DIRECT_QUERY=>false,
                   PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION
    ));
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

$testSpec = new src\entity\client();
$testSpec->generateClientByID($pdo, 24);
var_dump($testSpec);
?>
