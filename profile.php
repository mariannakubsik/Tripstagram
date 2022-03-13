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

    } else {
        exit("Nie znaleziono konfiguracji bazy danych.");
    }


    $url_name_profile = explode("/", $_SERVER['REQUEST_URI']);

    if(isset($_SESSION['id'])) {   


        $stmt = $dbh->prepare("SELECT * FROM a30_Users WHERE login = :login");
        $stmt->execute([':login' =>$url_name_profile[2]]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (empty($user)) {
            header("Location:https://s105.labagh.pl/main");
            exit();
        }
        else {
            $user_id = $user['id'];

            $stmt = $dbh->prepare("SELECT * FROM a30_Users WHERE id = :id");
            $stmt->execute([':id' => $user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt = $dbh->prepare("SELECT COUNT(*) FROM a30_Post WHERE userID = :userID");
            $stmt->execute([':userID' => $user_id]);
            $how_many_photos = intval($stmt->fetchColumn());

            $my_posts = [];
            $stmt = $dbh->prepare("SELECT * FROM a30_Post WHERE userID = :userID ORDER BY createdTime DESC");
            $stmt->execute([':userID' => $user_id]);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $stmt_1 = $dbh->prepare("SELECT COUNT(*) FROM a30_User_Post_Like WHERE PostID = :PostID");
                $stmt_1->execute([':PostID' => $row['id']]);
                $likes = intval($stmt_1->fetchColumn());
                $row['likes'] = $likes;
                $my_posts[] = $row;
            }

            $stmt = $dbh->prepare("SELECT * FROM a30_Post WHERE userid = :userid ORDER BY createdTime DESC LIMIT 1");
            $stmt->execute([':userid' => $user_id]);
            $last_post = $stmt->fetch(PDO::FETCH_ASSOC);

            echo $twig->render('profile.html.twig', ['post' => $_POST, 'user' =>$user, 'get' => $_GET, 'session' => $_SESSION, 'my_posts' => $my_posts, 'how_many_photos' => $how_many_photos, 'last_post' => $last_post, 'data' => $date]);
        }
    }
    else {
        header("Location:https://s105.labagh.pl/main");
        exit();
    }