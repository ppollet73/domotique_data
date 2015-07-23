<?php
require '../vendor/autoload.php';
//header('Content-Type: application/json');
$swagger = \Swagger\scan('../api.php');

echo $swagger;

?>
