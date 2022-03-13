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
        header("Location:https://s105.labagh.pl/main");
        exit();
    }
    else {
          $userFeedback = "Invalid data.";

    if (empty($_POST)) {
        echo $twig->render('register.html.twig', [ 'klucz' => $config['recaptcha_public'], 'data' => $date]);
    }
    else {
        
        if(isset($_POST['login']) && isset($_POST['email']) && isset($_POST['new_password_1']) && isset($_POST['new_password_2']))
        {
            
            if (isset($_POST['g-recaptcha-response'])) {
                $gRecaptchaResponse = $_POST['g-recaptcha-response'];
            }
		    $recaptcha = new \ReCaptcha\ReCaptcha($config['recaptcha_private']);
		    $resp = $recaptcha->verify($gRecaptchaResponse);

                $username = $_POST['login'];
                $new_email= $_POST['email'];
                $new_password_1= ($_POST['new_password_1']);
                $new_password_2 = ($_POST['new_password_2']);

           if($resp->isSuccess() && preg_match('/^[a-zA-Z0-9\-\_\.]+\@[a-zA-Z0-9\-\_\.]+\.[a-zA-Z]{2,5}$/D', $new_email) && preg_match('/^(?=.*[A-Za-z0-9]$)[A-Za-z][A-Za-z\d.-]{0,100}$/D', $username) && $_POST['new_password_1'] === $_POST['new_password_2'])
           {
                $hash_password_1 = password_hash($new_password_1, PASSWORD_DEFAULT);  
                try {
                    $stmt = $dbh->prepare ("INSERT INTO a30_Users (
                    id, login, password, email) 
                    VALUES (
                        null, :login, :password, :email) 
                    ");

                $stmt->execute([':login' => $username, ':password' => $hash_password_1,':email' => $new_email]);

                $userFeedback = "Dziękujemy za rejestrację.";
                } 
                catch (PDOException $e) {
                        $userFeedback = "Nieprawidłowe dane.";
                }
            }
            else {
                $userFeedback = "Nieprawidłowe dane.";
            }

            echo $twig->render('register.html.twig', [ 'test' => $userFeedback, 'klucz' => $config['recaptcha_public'], 'data' => $date]);
        }
        else {
            echo $twig->render('register.html.twig', [ 'test' => $userFeedback, 'klucz' => $config['recaptcha_public'], 'data' => $date]);
        }
    }
    }
