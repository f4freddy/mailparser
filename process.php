<?php
//ini_set('display_errors', 1);
require_once __DIR__ . '/mailparse.php';


$emailParser = new PlancakeEmailParser($_REQUEST['value']);
$emailDeliveredToHeader = $emailParser->getAllHeader();
echo json_encode($emailDeliveredToHeader);
?>