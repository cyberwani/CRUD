<?php
$response;
switch($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $response = array(
            array(
                'id' => '7',
                'text' => 'foo',
                'fruit' => array('apple', 'orange'),
                'letter' => 'b',
                'awesome' => '4'
            ),
            array(
                'id' => '8',
                'fruit' => array('apple'),
                'text' => 'default'
            )
        );
        break;
    case 'PUT':
        $response = true;//json_decode(file_get_contents('php://input'));
        break;
    case 'POST':
        $response = substr(md5(rand()), 0, 7);
        break;
    case 'DELETE':
        $response = true;//json_decode(file_get_contents('php://input'));
        break;
    default:
        throw new Exception("Invalid Request Method");
}
echo json_encode($response);
?>
