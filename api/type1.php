<?php

include 'includes/db.php';
include 'includes/helpers.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: *');
header('Access-Control-Allow-Methods: *');


$method = $_SERVER['REQUEST_METHOD'];
$action = (isset($_GET['action']) && !empty($_GET['action'])) ? $_GET['action'] : '';
$payload = json_decode(file_get_contents('php://input'), true);

//  Action : GET user profile
//  HTTP : GET
//  URL: api/type1.php?action=get_profile&id=y
if($action == 'get_profile' && is_set($_GET['id']) && $method == 'GET') {
    $user = [];
    $posts = [];

    // fetch user data
    $user_stm = $pdo->prepare("SELECT * FROM `users` WHERE `id` = :id");
    $user_stm->bindParam(':id', $_GET['id']);
    $user_stm->execute();
    $user = $user_stm->fetch(PDO::FETCH_ASSOC);

    // fetch posts
    $posts_stm = $pdo->prepare("SELECT * FROM `posts` WHERE `user_id` = :user_id");
    $posts_stm->bindParam(':user_id', $_GET['id']);
    $posts_stm->execute();

    while($row = $posts_stm->fetch(PDO::FETCH_ASSOC)) {
            $image_stm = $pdo->prepare("SELECT `name` FROM `images` WHERE `post_id` = :post_id LIMIT 1");
            $image_stm->bindParam(':post_id', $row['id']);
            $image_stm->execute();
            $image = $image_stm->fetch(PDO::FETCH_ASSOC);

            $row['image'] = isset($image['name']) ? $image['name'] : '';
            $posts[] = $row;
    }
 
    echo json_encode(['user' => $user, 'posts' => $posts]);
}

// Action: Get post
// HTTP verb: GET
// URL: api/v1.php?action=get_post&id=x
if($action == 'get_post' && is_set($_GET['id']) && $method == 'GET') {
    $post = [];

    // fetch post
    $post_stm = $pdo->prepare("SELECT * FROM `posts` WHERE `id` = :id");
    $post_stm->bindParam(':id', $_GET['id']);
    $post_stm->execute();
    $post = $post_stm->fetch(PDO::FETCH_ASSOC);

    echo json_encode(['post' => $post]);
}

// Action: Get post
// HTTP verb: GET
// URL: http://localhost/social-media/api/type1.php?action=get_posts
if($action == 'get_posts'  && $method == 'GET') {
    $posts = [];

    // fetch post
    $posts_stm = $pdo->prepare("SELECT * FROM `posts` ");
    $posts_stm->execute();
    while($row = $posts_stm->fetch(PDO::FETCH_ASSOC)){
        $posts[] = $row; 
    }

    echo json_encode(['posts' => $posts]);
}

// Action: Get post images
// HTTP verb: GET
// URL: api/v1.php?action=get_post_images&id=x
if($action == 'get_post_images' && is_set($_GET['id']) && $method == 'GET') {
    $images = [];

    // fetch comments
    $images_stm = $pdo->prepare("SELECT * FROM `images` WHERE `post_id` = :id");
    $images_stm->bindParam(':id', $_GET['id']);
    $images_stm->execute();
    while($row = $images_stm->fetch(PDO::FETCH_ASSOC)) {
            $images[] = $row;
    }

    echo json_encode(['images' => $images]);
}


// Action: Get post likes
// HTTP verb: GET
// URL: api/v1.php?action=get_post_likes&id=x
if ($action == 'get_post_likes' && isset($_GET['id']) && $method == 'GET') {
    $likes = [];

    // fetch likes
    $likes_stm = $pdo->prepare("SELECT * FROM `likes` WHERE `post_id` = :id");
    $likes_stm->bindParam(':id', $_GET['id']);
    $likes_stm->execute();

    while ($row = $likes_stm->fetch(PDO::FETCH_ASSOC)) {
        $likes[] = $row;
    }

    echo json_encode(['likes' => $likes]);
}




// Action: Get post comments
// HTTP verb: GET
// URL: api/v1.php?action=get_post_comments&id=x
if($action == 'get_post_comments' && is_set($_GET['id']) && $method == 'GET') {
    $comments = [];

    // fetch comments
    $comments_stm = $pdo->prepare("SELECT * FROM `comments` WHERE `post_id` = :id");
    $comments_stm->bindParam(':id', $_GET['id']);
    $comments_stm->execute();
    while($row = $comments_stm->fetch(PDO::FETCH_ASSOC)) {
            $comments[] = $row;
    }

    echo json_encode(['comments' => $comments]);
}

// Action: Add comment (post)
// HTTP verb: POST
// URL: api/v1.php
if(is_set($payload) && ($payload['action'] == 'add_comment') && $method == 'POST') {
    $add_comment_stm = $pdo->prepare("INSERT INTO `comments` (`user_id`, `post_id`, `comment`) VALUES (:user_id, :post_id, :comment)");
    
    $add_comment_stm->bindParam(':user_id', $payload['user_id']);
    $add_comment_stm->bindParam(':post_id', $payload['post_id']);
    $add_comment_stm->bindParam(':comment', $payload['comment']);

    if($add_comment_stm->execute()) {
        $response = ['status' => 1, 'message' => 'Komenti u shtua me sukses.'];
    } else {
        $response = ['status' => 0, 'message' => 'Komenti nuk u shtua - dicka shkoi keq!'];
    }

    echo json_encode($response);
}

// Action: Add post 
// HTTP verb: POST
// URL: api/v1.php
if(is_set($payload) && ($payload['action'] == 'add_post') && $method == 'POST') {
    $add_post_stm = $pdo->prepare("INSERT INTO `posts` (`user_id`, `description`) VALUES (:user_id, :description)");
    
    $add_post_stm->bindParam(':user_id', $payload['user_id']);
    $add_post_stm->bindParam(':description', $payload['description']);

    if($add_post_stm->execute()) {
        $post_id = $pdo->lastInsertId();

        if(count($_POST['images'])) {
            for($i = 0; $i < count($_POST['images']); $i++) {
                $filename = time() ."_" .$_FILES['images'][$i]['name'];

                if(move_uploaded_file($_FILES['images'][$i]['tmp_name'], "../uploads/".$filename)) {
                    // insert ne images table
                    $add_image_stm = $pdo->prepare("INSERT INTO `images` (`post_id`, `name`) VALUES (:post_id, :name)");
                    $add_image_stm->bindParam(':post_id', $post_id);
                    $add_image_stm->bindParam(':name', $filename);
                    $add_image_stm->execute();
                }
            }
        }

        $response = ['status' => 1, 'message' => 'Publikimi u shtua me sukses.'];
    } else {
        $response = ['status' => 0, 'message' => 'Publikimi nuk u shtua - dicka shkoi keq!'];
    }

    echo json_encode($response);
}

// Action: post like
// HTTP verb: POST
// URL: api/v1.php


if ($action == 'like_post' && isset($_GET['user_id']) && isset($_GET['post_id']) && $method == 'GET') {
    $like_post_stm = $pdo->prepare("INSERT INTO `liks` (`user_id`, `post_id`) VALUES (:user_id, :post_id )");

    $like_post_stm->bindParam(':user_id', $_GET['user_id']);
    $like_post_stm->bindParam(':post_id', $_GET['post_id']);

    if ($like_post_stm->execute()) {
        $response = ['status' => 1];
    } else {
        $response = ['status' => 0];
    }

    echo json_encode($response);
}



// Action : register
// HTTP : POST
// URL : api/type1.php


// if(is_set($payload) && ($payload['action'] == 'register') && $method == 'POST') {
//     $register_stm = $pdo->prepare("INSERT INTO `users` (`name`, `surname`, `email`, `password`) VALUES (:name, :surname, :email, :password, :token)");
    
//     $hash = password_hash($payload['password'], PASSWORD_BCRYPT);
//     $token = str_shuffle($hash);

//     $register_stm->bindParam(':name', $payload['name']);
//     $register_stm->bindParam(':surname', $payload['surname']);
//     $register_stm->bindParam(':email', $payload['email']);
//     $register_stm->bindParam(':password', $hash);
//     $register_stm->bindParam(':token', $token);

//     if($register_stm->execute()) {
//         $response = ['status' => 1, 'message' => 'Perdoruesi u regjistrua me sukses.'];
//     } else {
//         $response = ['status' => 0, 'message' => 'Perdoruesi nuk u regjistrua - dicka shkoi keq!'];
//     }

//     echo json_encode($response);
// }

if(is_set($payload) && ($payload['action'] == 'register') && $method == 'POST') {
    $register_stm = $pdo->prepare("INSERT INTO `users` (`name`, `surname`, `email`, `password`, `token`) VALUES (:name, :surname, :email, :password, :token)");
    
    $hash = password_hash($payload['password'], PASSWORD_BCRYPT);
    $token = str_shuffle($hash);

    $register_stm->bindParam(':name', $payload['name']);
    $register_stm->bindParam(':surname', $payload['surname']);
    $register_stm->bindParam(':email', $payload['email']);
    $register_stm->bindParam(':password', $hash);
    $register_stm->bindParam(':token', $token);

    if($register_stm->execute()) {
        $response = ['status' => 1, 'message' => 'Perdoruesi u regjistrua me sukses.'];
    } else {
        $response = ['status' => 0, 'message' => 'Perdoruesi nuk u regjistrua - dicka shkoi keq!'];
    }

    echo json_encode($response);
}



// Action : Login 
// HTTP : POST
// URL : api/type1.php

// if(is_set($payload) && ($payload['action'] == 'login') && $method == 'POST') {
//     $user = [];

//     // fetch user data
//     $login_stm = $pdo->prepare("SELECT * FROM `users` WHERE `email` = :email");
//     $login_stm->bindParam(':email', $payload['email']);
//     $login_stm->execute();
//     $user = $login_stm->fetch(PDO::FETCH_ASSOC);

//     if($user == false) {
//         echo json_encode(['status' => 0, 'message' => 'Nuk korospodon asnje perdorues per email adresen e dhene!']);
//         die();
//     }

//     if (password_verify($payload['password'], $user['password'])) {
//         // Passwords match
//         echo json_encode([
//             'status' => 1,
//             'id' => $user['id'],
//             'fullname' => $user['name'] . ' ' . $user['surname'],
//             'email' => $user['email'],
//             'token' => $user['token']
//         ]);
//     } else {
//         // Passwords do not match
//         echo json_encode(['status' => 0, 'message' => 'Keni shenuar te dhena te pasakta!']);
//     }
    
// }

if(is_set($payload) && ($payload['action'] == 'login') && $method == 'POST') {
    $user = [];

    // fetch user data
    $login_stm = $pdo->prepare("SELECT * FROM `users` WHERE `email` = :email");
    $login_stm->bindParam(':email', $payload['email']);
    $login_stm->execute();
    $user = $login_stm->fetch(PDO::FETCH_ASSOC);

    if($user == false) {
        echo json_encode(['status' => 0, 'message' => 'Nuk korospodon asnje perdorues per email adresen e dhene!']);
        die();
    }

    if(password_verify($payload['password'], $user['password'])) {
        echo json_encode(['status' => 1, 'id' => $user['id'], 'fullname' => $user['name'] .' ' .$user['surname'], 'email' => $user['email'], 'token' => $user['token']]);
    } else {
        echo json_encode(['status' => 0, 'message' => 'Keni shenuar te dhena te pasakta!']);
    }
}



// Action: User is logged in 
// HTTP verb: GET
// URL: api/v1.php
if($action == 'is_logged_in' && is_set($_GET['token']) && $method == 'GET') {
    $is_loggedin_stm = $pdo->prepare("SELECT * FROM `users` WHERE `token` = :email");
    $is_loggedin_stm->bindParam(':email', $_GET['token']);
    $is_loggedin_stm->execute();
    $user = $is_loggedin_stm->fetch(PDO::FETCH_ASSOC);

    if(is_array($user)) {
        echo json_encode(['status' => 1]);
    } else {
        echo json_encode(['status' => 0, 'message' => 'Sesioni ka skaduar - kycu serish!']);
    }
}


// Action: Get post comments
// HTTP verb: GET
// URL: api/v1.php?action=get_post_bookmaker&post_id=X&user_id=X
if($action == 'get_post_bookmaker' && is_set($_GET['post_id']) && is_set($_GET['user_id']) && $method == 'GET') {
    $bookmaker_posts = [];

    // fetch comments
    $bookmaker_posts_stm = $pdo->prepare("SELECT * FROM `bookmaker`
    INNER JOIN `posts` ON `posts`.`id` = `bookmaker`.`post_id`
    WHERE `bookmaker`.`post_id` = :post_id AND `bookmaker`.`user_id` = :user_id;
    ");
    $bookmaker_posts_stm->bindParam(':post_id', $_GET['post_id']);
    $bookmaker_posts_stm->bindParam(':user_id', $_GET['user_id']);
    $bookmaker_posts_stm->execute();
    while($row = $bookmaker_posts_stm->fetch(PDO::FETCH_ASSOC)) {
            $bookmaker_posts[] = $row;
    }

    echo json_encode(['bookmaker_posts' => $bookmaker_posts]);
}
