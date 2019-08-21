<?php
header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: PUT, GET, POST, DELETE, OPTIONS');
header("Access-Control-Allow-Headers: X-Requested-With");
header('Content-Type: text/html; charset=utf-8');
header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"'); 

require_once '../include/DbHandler.php'; 

require '../libs/Slim/Slim.php'; 

\Slim\Slim::registerAutoloader(); 
$app = new \Slim\Slim(); 

/* update usuario */
$app->post('/createUser', function() use ($app){
    try{
        $response = array();
        $dbHandler = new DbHandler();
        $db = $dbHandler->getConnection();
        
        $db->beginTransaction();
        $body = $app->request->getBody();
        $data = json_decode($body, true);

        //Actualización de usuario con datos de jugador

        $updateUsuario = 'INSERT INTO user (`name`, `uid`, `email`, `is_google`, `photo_url`, `phone`) VALUES (?, ?, ?, ?, ?, ?);';
        
        $name = $data['name'];
        $uid = $data['uid'];
        $email = $data['email'];
        $is_google = $data['is_google'];
        $photo_url = $data['photo_url'];
        $phone = $data['phone'];

        $sthUsuario = $db->prepare($updateUsuario);
        $sthUsuario->bindParam(1, $name, PDO::PARAM_STR);
        $sthUsuario->bindParam(2, $uid, PDO::PARAM_STR);
        $sthUsuario->bindParam(3, $email, PDO::PARAM_STR);

        $sthUsuario->bindParam(4, $is_google, PDO::PARAM_INT);
        $sthUsuario->bindParam(5, $photo_url, PDO::PARAM_STR);
        $sthUsuario->bindParam(6, $phone, PDO::PARAM_STR);
        $sthUsuario->execute();

        //Commit exitoso de transacción
        $db->commit();

        $response["status"] = true;
        $response["description"] = "Exitoso";
        $response["idTransaction"] = time();
        $response["parameters"] = [];
        $response["timeRequest"] = date("Y-m-d H:i:s");
        echoResponse(200, $response);
    }catch(Exception $e){
        $db->rollBack();
        $response["status"] = false;
        $response["description"] = $e->getMessage();
        $response["idTransaction"] = time();
        $response["parameters"] = $e;
        $response["timeRequest"] = date("Y-m-d H:i:s");
        echoResponse(400, $response);
    }
});

/* update usuario */
$app->put('/updateUser', function() use ($app){
    try{
        $response = array();
        $dbHandler = new DbHandler();
        $db = $dbHandler->getConnection();
        
        $db->beginTransaction();
        $body = $app->request->getBody();
        $data = json_decode($body, true);

        //Actualización de usuario con datos de jugador
        $updateUsuario = 'UPDATE user 
                            SET name = ?, 
                                uid = ?,
                                email = ?,
                                is_google = ?,
                                photo_url = ?,
                                phone = ?,
                                token = ?
                            WHERE id = ?';

        $name = $data['name'];
        $uid = $data['uid'];
        $email = $data['email'];
        $is_google = $data['is_google'];
        $photo_url = $data['photo_url'];
        $phone = $data['phone'];
        $token = $data['token'];
        $id = $data['id'];

        $sthUsuario = $db->prepare($updateUsuario);
        $sthUsuario->bindParam(1, $name, PDO::PARAM_STR);
        $sthUsuario->bindParam(2, $uid, PDO::PARAM_STR);
        $sthUsuario->bindParam(3, $email, PDO::PARAM_STR);

        $sthUsuario->bindParam(4, $is_google, PDO::PARAM_INT);
        $sthUsuario->bindParam(5, $photo_url, PDO::PARAM_STR);
        $sthUsuario->bindParam(6, $phone, PDO::PARAM_STR);
        $sthUsuario->bindParam(7, $token, PDO::PARAM_STR);

        $sthUsuario->bindParam(8, $id, PDO::PARAM_INT);
        $sthUsuario->execute();

        //Commit exitoso de transacción
        $db->commit();

        $response["status"] = true;
        $response["description"] = "Exitoso";
        $response["idTransaction"] = time();
        $response["parameters"] = [];
        $response["timeRequest"] = date("Y-m-d H:i:s");
        echoResponse(200, $response);
    }catch(Exception $e){
        $db->rollBack();
        $response["status"] = false;
        $response["description"] = $e->getMessage();
        $response["idTransaction"] = time();
        $response["parameters"] = $e;
        $response["timeRequest"] = date("Y-m-d H:i:s");
        echoResponse(400, $response);
    }
});

$app->delete('/deleteUser', function() use ($app){
    try{
        $response = array();
        $dbHandler = new DbHandler();
        $db = $dbHandler->getConnection();
        
        $db->beginTransaction();
        $body = $app->request->getBody();
        $data = json_decode($body, true);

        //Actualización de usuario con datos de jugador
        $updateUsuario = 'DELETE from user WHERE id = ?';

        $id = $data['id'];

        $sthUsuario = $db->prepare($updateUsuario);

        $sthUsuario->bindParam(1, $id, PDO::PARAM_INT);
        $sthUsuario->execute();

        //Commit exitoso de transacción
        $db->commit();

        $response["status"] = true;
        $response["description"] = "Exitoso";
        $response["idTransaction"] = time();
        $response["parameters"] = [];
        $response["timeRequest"] = date("Y-m-d H:i:s");
        echoResponse(200, $response);
    }catch(Exception $e){
        $db->rollBack();
        $response["status"] = false;
        $response["description"] = $e->getMessage();
        $response["idTransaction"] = time();
        $response["parameters"] = $e;
        $response["timeRequest"] = date("Y-m-d H:i:s");
        echoResponse(400, $response);
    }
});

/* corremos la aplicación */
$app->run();

/*********************** USEFULL FUNCTIONS **************************************/

/**
 * Verificando los parametros requeridos en el metodo o endpoint
 */
function verifyRequiredParams($required_fields) {
    $error = false;
    $error_fields = "";
    $request_params = array();
    $request_params = $_REQUEST;
    // Handling PUT request params
    if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
        $app = \Slim\Slim::getInstance();
        parse_str($app->request()->getBody(), $request_params);
    }
    foreach ($required_fields as $field) {
        if (!isset($request_params[$field]) || strlen(trim($request_params[$field])) <= 0) {
            $error = true;
            $error_fields .= $field . ', ';
        }
    }
 
    if ($error) {
        // Required field(s) are missing or empty
        // echo error json and stop the app
        $response = array();
        $app = \Slim\Slim::getInstance();


        $response["status"] = "I";
        $response["description"] = 'Campo(s) Requerido(s) ' . substr($error_fields, 0, -2) . '';
        $response["idTransaction"] = time();
        $response["parameters"] = [];
        $response["timeRequest"] = date("Y-m-d H:i:s");

        echoResponse(400, $response);
        
        $app->stop();
    }
}
 
/**
 * Validando parametro email si necesario; un Extra ;)
 */
function validateEmail($email) {
    $app = \Slim\Slim::getInstance();
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response["error"] = true;
        $response["message"] = 'Email address is not valid';
        echoResponse(400, $response);
        
        $app->stop();
    }
}
 
/**
 * Mostrando la respuesta en formato json al cliente o navegador
 * @param String $status_code Http response code
 * @param Int $response Json response
 */
function echoResponse($status_code, $response) {
    $app = \Slim\Slim::getInstance();
    // Http response code
    $app->status($status_code);
 
    // setting response content type to json
    $app->contentType('application/json');
 
    echo json_encode($response);
}

/**
 * Agregando un leyer intermedio e autenticación para uno o todos los metodos, usar segun necesidad
 * Revisa si la consulta contiene un Header "Authorization" para validar
 */
function authenticate(\Slim\Route $route) {
    // Getting request headers
    $headers = apache_request_headers();
    $response = array();
    $app = \Slim\Slim::getInstance();
 
    // Verifying Authorization Header
    if (isset($headers['Authorization'])) {
        //$db = new DbHandler(); //utilizar para manejar autenticacion contra base de datos
 
        // get the api key
        $token = $headers['Authorization'];
        
        // validating api key
        if (!($token == API_KEY)) { //API_KEY declarada en Config.php
            
            // api key is not present in users table
            $response["error"] = true;
            $response["message"] = "Acceso denegado. Token inválido";
            echoResponse(401, $response);
            
            $app->stop(); //Detenemos la ejecución del programa al no validar
            
        } else {
            //procede utilizar el recurso o metodo del llamado
        }
    } else {
        // api key is missing in header
        $response["error"] = true;
        $response["message"] = "Falta token de autorización";
        echoResponse(400, $response);
        
        $app->stop();
    }
}

/*
 *Función para encriptar contraseñas
 */
function dec_enc($action, $string) {
    $output = false;
 
    $encrypt_method = "AES-256-CBC";
    $secret_key = 'This is my secret key';
    $secret_iv = 'This is my secret iv';
 
    // hash
    $key = hash('sha256', $secret_key);
    
    // iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
    $iv = substr(hash('sha256', $secret_iv), 0, 16);
 
    if( $action == 'encrypt' ) {
        $output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
        $output = base64_encode($output);
    }
    else if( $action == 'decrypt' ){
        $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
    }
 
    return $output;
}