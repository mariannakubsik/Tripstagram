<?php

    require __DIR__ . '/vendor/autoload.php';

    use Twig\Environment;
    use Twig\Loader\FilesystemLoader;

    $loader = new FilesystemLoader(__DIR__ . '/templates');
    $twig = new Environment($loader);

    session_start();

    ini_set('display_errors', 1);
    error_reporting(E_ALL);
    require __DIR__ . '/vendor/autoload.php';
    define("IN_INDEX", 1);

    include("config.inc.php");
    include("functions.inc.php"); 
    
    $date = date('Y-m-d');
    if (isset($config) && is_array($config)) {

        try {
            $dbh = new PDO('mysql:host=' . $config['db_host'] . ';dbname=' . $config['db_name'] . ';charset=utf8mb4', $config['db_user'], $config['db_password']);
            $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            print "Nie mozna polaczyc sie z baza danych: " . $e->getMessage();
            exit();
        }

    } 
    else {
        exit("Nie znaleziono konfiguracji bazy danych.");
    }
    
   if(isset($_SESSION['id'])){
    if(empty($_POST))
    {
        echo $twig->render('add.html.twig',['session' => $_SESSION, 'data' => $date]);
    }
    else {    
        try {
            if (empty($_FILES['image'])) {
                throw new Exception('Image file is missing');
            }
            
            $image = $_FILES['image'];
            if ($image['error'] !== 0) {
                if ($image['error'] === 1) 
                    throw new Exception('Max upload size exceeded');
                    
                throw new Exception('Image uploading error: INI Error');
            }
            if (!file_exists($image['tmp_name']))
                throw new Exception('Image file is missing in the server');
            $maxFileSize = 2 * 10e6; 
            if ($image['size'] > $maxFileSize)
                throw new Exception('Max size limit exceeded'); 
            $imageData = getimagesize($image['tmp_name']);
            if (!$imageData) 
                throw new Exception('Invalid image');
            $mimeType = $imageData['mime'];
            $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($mimeType, $allowedMimeTypes)) 
                throw new Exception('Only JPEG, PNG and GIFs are allowed');
            

            $fileExtention = strtolower(pathinfo($image['name'] ,PATHINFO_EXTENSION));
            $fileName = round(microtime(true)) . mt_rand() . '.' . $fileExtention; 
            $path = '/Images/' . $fileName;
            $destination = $_SERVER['DOCUMENT_ROOT'] . $path;
        
            if (!move_uploaded_file($image['tmp_name'], $destination))
                throw new Exception('Error in moving the uploaded file');
        
            $localization = $_POST['localization'];
            $description = $_POST['description'];
            $protocol = stripos($_SERVER['SERVER_PROTOCOL'],'https') === true ? 'https://' : 'http://';
            $domain = $protocol . $_SERVER['SERVER_NAME'];
            $url = $domain . $path;
            $currentDate = date("Y-m-d H:i:s");
           

            $stmt = $dbh->prepare("SELECT * FROM a30_Users WHERE id = :id");
            $stmt->execute([':id' => $_SESSION['id']]);
            $userID = $stmt->fetch(PDO::FETCH_ASSOC)['id'];

    
            $stmt = $dbh -> prepare("INSERT INTO a30_Post (id, imgSource, createdTime, localization, description, userID ) 
                VALUES (
                null, '$url', '$currentDate', '$localization', '$description', $userID) 
                ");
            $stmt = $stmt->execute();
            
            if ( true) {
                $info = "Przesłano plik!";
                echo $twig->render('add.html.twig', ['post' => $_POST, 'session' =>$_SESSION, 'get' => $_GET, 'test'=> $info, 'data' => $date]);
                
            } else 
                throw new Exception('Error in saving into the database');
            
        } catch (Exception $e) {
            $info = "Plik nie został przesłany.";
            echo $twig->render('add.html.twig', ['post' => $_POST, 'session' =>$_SESSION, 'get' => $_GET, 'test'=>$info, 'data' => $date]);
        }
    }       
   }
   else {
        header("Location:https://s105.labagh.pl/main");
        exit();
   }