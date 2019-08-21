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

/*obtener usuario para loguarse */
$app->get('/getSuites', function() use ($app){
    $sql = "";
    $sqlTotal = "";
    try{
        $response = array();
        $dbHandler = new DbHandler();
        $db = $dbHandler->getConnection();

        $page = $app->request()->params('page');
        $paginated = $app->request()->params('paginated');

        $suite_type = $app->request()->params('suite_type');
        $property_type = $app->request()->params('property_type');

        $higher_price = $app->request()->params('higher_price');
        $lower_price = $app->request()->params('lower_price');

        $bedrooms = $app->request()->params('bedrooms');
        $bathrooms = $app->request()->params('bathrooms');

        $lower_size = $app->request()->params('lower_size');
        $higher_size = $app->request()->params('higher_size');

        $state = $app->request()->params('state');
        $city = $app->request()->params('city');
        $country = $app->request()->params('country');

        $lower_garage = $app->request()->params('lower_garage');
        $higher_garage = $app->request()->params('higher_garage');

        $latitude = $app->request()->params('latitude');
        $longitude = $app->request()->params('longitude');

        $kms = $app->request()->params('kms');

        //$resultado = $db->getRadios();
        $sqlTotal = "SELECT COUNT(*) as todos ";
        $sql3 = 'SELECT 
                s.price,
                s.bedrooms,
                s.bathrooms,
                s.size,
                s.description,
                s.date_at,
                s.is_premium,
                s.premium_at,
                s.title,
                s.garages,
                IF(length(GROUP_CONCAT(p.path)) < 2,NULL,GROUP_CONCAT(p.path))  as photos';

        if($latitude != null && $longitude != null){
            $sql3 = $sql3.', (6371 * ACOS( 
                                SIN(RADIANS(a.latitude)) * SIN(RADIANS('.$latitude.')) 
                                + COS(RADIANS(a.longitude - '.$longitude.')) * COS(RADIANS(a.latitude)) 
                                * COS(RADIANS('.$latitude.'))
                                )
                   ) AS distance_mts';
        }
        $sqlConcat = ' FROM suite s
                LEFT JOIN photos p
                ON (p.id_suite = s.id)
                INNER JOIN address a
                ON (a.id = s.id_address)
                INNER JOIN settlement se
                ON (se.id = a.id_settlement)
                INNER JOIN city c
                ON (c.id = se.id_city)
                INNER JOIN state st
                ON (st.id = c.id_state)
                INNER JOIN catalog c3
                ON (c3.id = st.id_country)
                INNER JOIN catalog c1
                ON (c1.id = s.id_suite_type)
                INNER JOIN catalog c2
                ON (c2.id = s.id_property_type) WHERE s.id_status = 11';


        if($suite_type != null){
            $sqlConcat = $sqlConcat.' AND s.id_suite_type = '.$suite_type;
        }
        if($property_type != null){
            $sqlConcat = $sqlConcat.' AND s.id_property_type = '.$property_type;
        }
        if($bedrooms != null){
            $sqlConcat = $sqlConcat.' AND s.bedrooms = '.$bedrooms;
        }
        if($bathrooms != null){
            $sqlConcat = $sqlConcat.' AND s.bathrooms = '.$bathrooms;
        }
        if($higher_price != null && $lower_price != null){
            $sqlConcat = $sqlConcat.' AND s.price BETWEEN '.$lower_price.' AND '.$higher_price;
        }
        if($lower_size != null && $higher_size != null){
            $sqlConcat = $sqlConcat.' AND s.size BETWEEN '.$lower_size.' AND '.$higher_size;
        }
        if($lower_garage != null && $higher_garage != null){
            $sqlConcat = $sqlConcat.' AND s.garages BETWEEN '.$lower_garage.' AND '.$higher_garage;
        }
        
        $paginado = 10;
        if(!is_null($paginated)){
            $paginado = $paginated;
        }
        
        
        if($page === null){
            $response["status"] = false;
            $response["description"] = "El valor de página es requerido";
            $response["idTransaction"] = time();
            $response["parameters"] = [];
            $response["timeRequest"] = date("Y-m-d H:i:s");

            echoResponse(200, $response);
        }else{

            $sqlOrder = " ORDER BY s.is_premium DESC, s.title ASC";

            $sqlTotal = $sqlTotal . $sqlConcat;

            $sql = $sql3.$sqlConcat." GROUP BY s.id";

            if($latitude !== null && $longitude !== null){
                $clausuleWhere = ' HAVING distance_mts < '.$kms;

                //$sqlTotal = $sqlTotal . $clausuleWhere;
                $sqlTotal = $sql.$clausuleWhere.$sqlOrder;

                $sql = $sql.$clausuleWhere.$sqlOrder." LIMIT ".(($page-1)*$paginado).",".$paginado;

                
            }else{
                $sqlTotal = $sql.$sqlOrder;
                $sql = $sql.$sqlOrder." LIMIT ".(($page-1)*$paginado).",".$paginado;
                
            }

            $sthTotal = $db->prepare($sqlTotal);
            $sthTotal->execute();
            $rows2 = $sthTotal->fetchAll(PDO::FETCH_ASSOC);

            $sth = $db->prepare($sql);
            //$sqlFinal = $sth->debugDumpParams();
            $sth->execute();
            $rows = $sth->fetchAll(PDO::FETCH_ASSOC);

            for ($i=0; $i < sizeof($rows); $i++) { 
                $arrayPhotos = explode(",",$rows[$i]['photos']);
                if(sizeof($arrayPhotos)>0){
                    if(strlen($arrayPhotos[0])<1){
                        $rows[$i]['photos'] = [];
                    }else{
                        $rows[$i]['photos'] = $arrayPhotos;
                    }
                    
                }else{
                    $rows[$i]['photos'] = [];
                }
                if($latitude !== null && $longitude !== null){
                    $rows[$i]['distance_mts'] = number_format($rows[$i]['distance_mts'] * 1000, 2, '.', ' ');
                }
            }

            if(!empty($rows)){
                    
                    $format = array();



                    $operacion = sizeof($rows2) / $paginado; 
                    
                    $operacion = floor($operacion);

                    $verificarMult = $operacion * $paginado;

                    if($verificarMult < sizeof($rows2)){
                        $operacion = $operacion+1;
                    }

                    $format["total_pages"] = $operacion;
                    $format["registers"] = $rows;
                    $format["querie"] = $sql;

                    $response["status"] = true;
                    $response["description"] = "Exitoso";
                    $response["idTransaction"] = time();
                    $response["parameters"] = $format;
                    $response["timeRequest"] = date("Y-m-d H:i:s");

                    echoResponse(200, $response);
                
            }else{
                $response["status"] = false;
                $response["description"] = "No se encontraron suites conforme a tu búsqueda";
                $response["idTransaction"] = time();
                $response["parameters"] = [];
                $response["timeRequest"] = date("Y-m-d H:i:s");

                echoResponse(400, $response);
            }
        }

        
    }catch(Exception $e){
        $response["status"] = false;
        $response["description"] = $e->getMessage();
        $response["idTransaction"] = time();
        $response["parameters"] = $e;
        $response["timeRequest"] = date("Y-m-d H:i:s");

        echoResponse(400, $response);
    }
});

/*obtener usuario para loguarse */
$app->get('/getSuiteTypes', function() use ($app){
    try{
        $response = array();
        $dbHandler = new DbHandler();
        $db = $dbHandler->getConnection();
        
        $sql = 'SELECT id,name,label FROM catalog where id_catalog_type = 1';
        
        $sth = $db->prepare($sql);
        //$sqlFinal = $sth->debugDumpParams();
        $sth->execute();
        $rows = $sth->fetchAll(PDO::FETCH_ASSOC);

        $response["status"] = "A";
        $response["description"] = "Exitoso";
        $response["idTransaction"] = time();
        $response["parameters"] = $rows;
        $response["timeRequest"] = date("Y-m-d H:i:s");
        echoResponse(200, $response);

    }catch(Exception $e){
        $response["status"] = "I";
        $response["description"] = $e->getMessage();
        $response["idTransaction"] = time();
        $response["parameters"] = $e;
        $response["timeRequest"] = date("Y-m-d H:i:s");

        echoResponse(400, $response);
    }
});

$app->get('/getPropertyTypes', function() use ($app){
    try{
        $response = array();
        $dbHandler = new DbHandler();
        $db = $dbHandler->getConnection();
        
        $sql = 'SELECT id,name,label FROM catalog where id_catalog_type = 3';
        
        $sth = $db->prepare($sql);
        //$sqlFinal = $sth->debugDumpParams();
        $sth->execute();
        $rows = $sth->fetchAll(PDO::FETCH_ASSOC);

        $response["status"] = "A";
        $response["description"] = "Exitoso";
        $response["idTransaction"] = time();
        $response["parameters"] = $rows;
        $response["timeRequest"] = date("Y-m-d H:i:s");
        echoResponse(200, $response);

    }catch(Exception $e){
        $response["status"] = "I";
        $response["description"] = $e->getMessage();
        $response["idTransaction"] = time();
        $response["parameters"] = $e;
        $response["timeRequest"] = date("Y-m-d H:i:s");

        echoResponse(400, $response);
    }
});

/* update usuario */
$app->post('/createSuite', function() use ($app){
    $target_dir = $_SERVER["DOCUMENT_ROOT"]."/api_sweet_home/suites/";
        $response = array();
        $dbHandler = new DbHandler();
        $db = $dbHandler->getConnection();
        $request = $app->request();
        $db->beginTransaction();


        $value=0;
    try{
        
        /*Obteniendo archivos de fotos*/
        $files = $_FILES;

        $files2 = [];
        foreach ($files as $input => $infoArr) {
            $filesByInput = [];
            foreach ($infoArr as $key => $valueArr) {
                if (is_array($valueArr)) { // file input "multiple"
                    foreach($valueArr as $i=>$value) {
                        $filesByInput[$i][$key] = $value;
                    }
                }
                else { // -> string, normal file input
                    $filesByInput[] = $infoArr;
                    break;
                }
            }
            $files2 = array_merge($files2,$filesByInput);
        }
        /* termina lectura de archivos*/

        /*Creación de domicilio*/
        $sql = 'INSERT INTO `address` (`id_settlement`, `internal_number`, `external_number`, `zip_code`, `street`, `latitude`, `longitude`) VALUES (?, ?, ?, ?, ?, ?, ?)';

        $id_settlement = $request->params('id_settlement');
        $internal_number = $request->params('internal_number');
        $external_number = $request->params('external_number');
        $zip_code = $request->params('zip_code');
        $street = $request->params('street');
        $latitude = $request->params('latitude');
        $longitude = $request->params('longitude');

        $sth = $db->prepare($sql);
    
        $sth->bindParam(1, $id_settlement, PDO::PARAM_INT);
        $sth->bindParam(2, $internal_number, PDO::PARAM_STR);
        $sth->bindParam(3, $external_number, PDO::PARAM_STR);
        $sth->bindParam(4, $zip_code, PDO::PARAM_STR);
        $sth->bindParam(5, $street, PDO::PARAM_STR);
        $sth->bindParam(6, $latitude, PDO::PARAM_STR);
        $sth->bindParam(7, $longitude, PDO::PARAM_STR);

        $sth->execute();
        //$db->commit(); 
        $idDireccion = $db->lastInsertId();
        
        $value = $sth->fetch(PDO::FETCH_ASSOC); 
        //$id = $result['id'];//Se obtiene id del nuevo domicilio
        //$idDireccion = $id;

        //$value = $idDireccion;

        /*Creación de suite*/
        $sqlSuite = "INSERT INTO `suite` (`id_suite_type`, `id_property_type`, `id_status`, `id_address`, `price`, `bedrooms`, `bathrooms`, `size`, `description`, `title`, `garages`) 
            VALUES (?, ?, 11, ?, ?, ?, ?, ?, ?, ?, ?);
            ";

        $id_suite_type = $request->params('id_suite_type');
        $id_property_type = $request->params('id_property_type');
        $price = $request->params('price');
        $bedrooms = $request->params('bedrooms');
        $bathrooms = $request->params('bathrooms');
        $size = $request->params('size');
        $description = $request->params('description');
        $title = $request->params('title');
        $garages = $request->params('garages');

        $sthSuite = $db->prepare($sqlSuite);

        $sthSuite->bindParam(1, $id_suite_type, PDO::PARAM_INT);
        $sthSuite->bindParam(2, $id_property_type, PDO::PARAM_INT);

        $sthSuite->bindParam(3, $idDireccion, PDO::PARAM_INT);

        $sthSuite->bindParam(4, $price, PDO::PARAM_STR);
        $sthSuite->bindParam(5, $bedrooms, PDO::PARAM_INT);
        $sthSuite->bindParam(6, $bathrooms, PDO::PARAM_INT);
        $sthSuite->bindParam(7, $size, PDO::PARAM_STR);
        $sthSuite->bindParam(8, $description, PDO::PARAM_STR);
        $sthSuite->bindParam(9, $title, PDO::PARAM_STR);
        $sthSuite->bindParam(10, $garages, PDO::PARAM_INT);


$response["status"] = true;
        $response["description"] = "Exitoso";
        $response["idTransaction"] = time();
        $response["parameters"] = $idDireccion;
        $response["timeRequest"] = date("Y-m-d H:i:s");
        echoResponse(200, $idDireccion);

        $sthSuite->execute();
        $idSuite = $db->lastInsertId();
        /**/
    
$db->commit(); 
        
        $response["status"] = true;
        $response["description"] = "Exitoso";
        $response["idTransaction"] = time();
        $response["parameters"] = $idSuite;
        $response["timeRequest"] = date("Y-m-d H:i:s");
        echoResponse(200, $response);
    }catch(PDOException $e){
        $db->rollBack();
        $response["status"] = false;
        $response["description"] = $e->getMessage();
        $response["idTransaction"] = time();
        $response["parameters"] = $value;
        $response["timeRequest"] = date("Y-m-d H:i:s");
        echoResponse(400, $response);
    }
});

$app->put('/disableSuite', function() use ($app){
    try{
        $response = array();
        $dbHandler = new DbHandler();
        $db = $dbHandler->getConnection();
        
        $db->beginTransaction();
        $body = $app->request->getBody();
        $data = json_decode($body, true);

        //Actualización de usuario con datos de jugador
        $updateUsuario = 'UPDATE suite SET id_status = 12 WHERE (id = ?)';

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

$app->put('/enableSuite', function() use ($app){
    try{
        $response = array();
        $dbHandler = new DbHandler();
        $db = $dbHandler->getConnection();
        
        $db->beginTransaction();
        $body = $app->request->getBody();
        $data = json_decode($body, true);

        //Actualización de usuario con datos de jugador
        $updateUsuario = 'UPDATE suite SET id_status = 11 WHERE (id = ?)';

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

$app->put('/makePremiumById', function() use ($app){
    try{
        $response = array();
        $dbHandler = new DbHandler();
        $db = $dbHandler->getConnection();
        
        $db->beginTransaction();
        $body = $app->request->getBody();
        $data = json_decode($body, true);

        //Actualización de usuario con datos de jugador
        $updateUsuario = 'UPDATE suite SET is_premium = 1, premium_at = now() WHERE (id = ?)';

        $id = $data['id'];

        $sthUsuario = $db->prepare($updateUsuario);

        $sthUsuario->bindParam(1, $id, PDO::PARAM_INT);
        
        $sthUsuario->execute();

        //Commit exitoso de transacción
        $db->commit();

        $response["status"] = true;
        $response["description"] = "Exitoso";
        $response["idTransaction"] = time();
        $response["parameters"] = $id;
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

$app->put('/removePremiumById', function() use ($app){
    try{
        $response = array();
        $dbHandler = new DbHandler();
        $db = $dbHandler->getConnection();
        
        $db->beginTransaction();
        $body = $app->request->getBody();
        $data = json_decode($body, true);

        //Actualización de usuario con datos de jugador
        $updateUsuario = 'UPDATE suite SET is_premium = 0, premium_at = now() WHERE (id = ?)';

        $id = $data['id'];

        $sthUsuario = $db->prepare($updateUsuario);

        $sthUsuario->bindParam(1, $id, PDO::PARAM_INT);
        
        $sthUsuario->execute();

        //Commit exitoso de transacción
        $db->commit();

        $response["status"] = true;
        $response["description"] = "Exitoso";
        $response["idTransaction"] = time();
        $response["parameters"] = $id;
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
$app->post('/formData', function() use ($app){
    $target_dir = $_SERVER["DOCUMENT_ROOT"]."/api_sweet_home/suites/";

        $response = array();
        $dbHandler = new DbHandler();
        $db = $dbHandler->getConnection();

        $request = $app->request();
        $db->beginTransaction();
        $uno = $request->params('dos');
        
        $files = $_FILES;
        $res = array();
        $res["paramDos"] = $uno;
        $res["filesComplete"] = $files;
        $res["files"] = $files['uno'];


    $files2 = [];
    foreach ($files as $input => $infoArr) {
        $filesByInput = [];
        foreach ($infoArr as $key => $valueArr) {
            if (is_array($valueArr)) { // file input "multiple"
                foreach($valueArr as $i=>$value) {
                    $filesByInput[$i][$key] = $value;
                }
            }
            else { // -> string, normal file input
                $filesByInput[] = $infoArr;
                break;
            }
        }
        $files2 = array_merge($files2,$filesByInput);
    }
$res["files2"] = $files2;

        $file = $_FILES['uno']['name'];
        $path = pathinfo($file);

        $filename = $path['filename'];
        $ext = $path['extension'];
        $temp_name = $_FILES['uno']['tmp_name'];
        $path_filename_ext = $target_dir.$filename.".".$ext;
    try{
        

        if (file_exists($path_filename_ext)) {
         $res["mensaje"] = "Sorry, file already exists.";
         }else{
         move_uploaded_file($temp_name,$path_filename_ext);
         $res["mensaje"] =  "Congratulations! File Uploaded Successfully.";
         }

        $response["status"] = true;
        $response["description"] = "Exitoso";
        $response["idTransaction"] = time();
        $response["parameters"] = $res;
        $response["timeRequest"] = date("Y-m-d H:i:s");
        echoResponse(200, $response);
    }catch(Exception $e){
        $db->rollBack();
        $response["status"] = false;
        $response["description"] = $e->getMessage();
        $response["idTransaction"] = time();
        $response["parameters"] = $res;
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