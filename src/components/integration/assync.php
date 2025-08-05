<?php
include_once __DIR__."/../../../vendor/autoload.php";
$dotenv = \Dotenv\Dotenv::createUnsafeImmutable(__DIR__.'/../../../');
$dotenv->load();

$scriptId = $argv[1];
$method = $argv[2];
$params = unserialize(base64_decode($argv[3]));


try {
    $path = __DIR__ . "/../../scripts/" . $scriptId . "/Main.php";

    if (!file_exists($path)) {
        echo json_encode(["error"=>"Arquivo não encontrado: " . $path]);
        exit;
    }
    
    include_once($path);
    
    $className = "Main";
    if (!class_exists($className)) {
        echo json_encode(["error"=>"Classe '$className' não encontrada no arquivo."]);
        exit;
    }
    
    $instance = new $className();
    
    if (!method_exists($instance, $method)) {
        echo json_encode(["error"=>"Método '$method' não encontrado na classe '$className'."]);
        exit;
    }

    $returns = $instance->$method($params);
    
    echo json_encode(["error"=>null, "returns"=>$returns]);

} catch (\Exception $e) {
    echo json_encode(["error"=>$e->getMessage()]);
}

?>
