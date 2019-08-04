<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if($_SERVER['REQUEST_METHOD']!=='POST') die;
header('Content-Type: application/json');

$success = false;
$errorCode = 500;
$errorMsg = '';

$db = @new mysqli("localhost", "root", "", "guc_2019_client_side");
if($db->connect_errno) {
    $errorMsg = 'Failed to connect to database.';
} else {
    $requestBody = file_get_contents('php://input');
    $requestObj = json_decode($requestBody);
    $keys = ['time', 'lines', 'score'];
    $passArgCheck = is_object($requestObj);
    foreach($keys as $k) {
        if(!$passArgCheck || !property_exists($requestObj, $k) || !is_int($requestObj->{$k})) {
            $passArgCheck = false;
            break;
        }
    }

    if($passArgCheck) {
        $sql = 'INSERT INTO `results` (`id`, `time`, `lines`, `score`) VALUES (NULL, ?, ?, ?)';
        $newId = null;
        $results = [];
        
        if ($stmt = $db->prepare($sql)) {
            $stmt->bind_param("iii", $requestObj->time, $requestObj->lines, $requestObj->score);
            $stmt->execute();
            $newId = $db->insert_id;
        } else {
            $errorMsg = 'Failed to prepare statement.';
        }
        
        if(!is_null($newId)) {
            $ret = [
                'results'=> &$results,
                'current_id' => $newId,
            ];
            
            if ($result=$db->query('SELECT * FROM `results`')) {
                $responseKeys = $keys;
                $responseKeys[] = 'id';
                while ($row=$result->fetch_object()) {
                    foreach($responseKeys as $k)
                        $row->{$k}*=1;
                    $results[]=$row;
                }
                $result->free_result();
            }
        }
        echo json_encode($ret);
        $success = true;
    } else {
        $errorCode = 400;
        $errorMsg = 'Format Error';
    }
}


if(!$success) {
    http_response_code($errorCode);
    die(json_encode(['error'=>$errorMsg]));
}

