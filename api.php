<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once 'vendor/autoload.php';
//require_once 'src/InstagramScraper.php';

use InstagramScraper\Instagram;

Unirest\Request::verifyPeer(false);

$supported_actions = 'Supported actions: "get_account", "get_followers".';

if (empty($_GET['action'])) {
    $error = 'Missing parameter "action". ' . $supported_actions;
    http_response_code(400);
    echo json_encode($error, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    return;
}

switch (strtolower($_GET['action'])) {
    // ?action=get_account&username=USERNAME
    case 'get_account':
        // Validate input
        if (empty($_GET['username'])) {
            http_response_code(400);
            echo json_encode('Missing query parameter "username".', JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            return;
        }

        // Execute
        try {
            $account = Instagram::getAccount($_GET['username']);
            echo json_encode($account, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            return;
        } catch (InstagramException $e) {
            http_response_code(500);
            echo json_encode($e, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            return;
        }
        break;

    // basic auth with username and pass
    // ?action=get_followers&username=USERNAME&count=COUNT&page_size=PAGESIZE
    case 'get_followers':
        // Validate input
        if (empty($_SERVER['PHP_AUTH_USER'])) {
            $error = 'Missing basic auth username.';
        }       
        elseif (empty($_SERVER['PHP_AUTH_PW'])) {
            $error = 'Missing basic auth password.';
        }
        elseif (empty($_GET['username'])) {
            $error = 'Missing query parameter "username".';
        }
        elseif (empty($_GET['count'])) {
            $error = 'Missing query parameter "count".';
        }
        elseif (empty($_GET['page_size'])) {
            $error = 'Missing query parameter "page_size".';
        }

        if (!empty($error)) {
            http_response_code(400);
            echo json_encode($error, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            return;
        }

        // Execute
        try {
            $followers = [];
            $instagram = Instagram::withCredentials($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);
            usleep(1100000); // Delay to mimic browser
            $instagram->login();
            usleep(1500000);
            $account = Instagram::getAccount($_GET['username']);
            usleep(1200000);
            $followers = $instagram->getFollowers($account->getId(), $_GET['count'], $_GET['page_size'], true);
            echo json_encode($followers, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            return;
        } catch (InstagramException $e) {
            http_response_code(500);
            echo json_encode($e, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            return;
        }
        break;

    default:
        $error = 'Invalid "action" parameter. ' . $supported_actions;
        http_response_code(400);
        echo json_encode($error, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        return;
}