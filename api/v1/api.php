<?php
//error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
session_start();
include_once $_SERVER['DOCUMENT_ROOT'] . "/db.php";
include_once $_SERVER['DOCUMENT_ROOT'] . "/classes/User.php";
include_once $_SERVER['DOCUMENT_ROOT'] . "/classes/Permissions.php";
include_once $_SERVER['DOCUMENT_ROOT'] . "/classes/Helpers.php";
include_once $_SERVER['DOCUMENT_ROOT'] . "/classes/Config.php";
include_once $_SERVER['DOCUMENT_ROOT'] . "/classes/DiffViewer.php";
include_once $_SERVER['DOCUMENT_ROOT'] . "/classes/Parsedown.php";
include_once $_SERVER['DOCUMENT_ROOT'] . "/classes/Interviews.php";
require_once $_SERVER['DOCUMENT_ROOT'] . "/vendor/autoload.php";
$url = $_GET['url'];
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($url == "loginUser") {
        $email = $_POST['email'];
        $password = $_POST['password'];
        $sql = "SELECT * FROM users WHERE email = :email";
        $query = $pdo->prepare($sql);
        $query->bindValue(':email', $email, PDO::PARAM_STR);
        $query->execute();
        $selected_user = $query->fetch();
        $arr = ['token' => '', 'uid' => ''];
        if (password_verify($password, $selected_user->password)) {
            $userid = $selected_user->id;
            $cstrong = true;
            $token = bin2hex(openssl_random_pseudo_bytes(64, $cstrong));
            $stoken = sha1($token);
            $sql2 = "INSERT INTO login_tokens (`token`, `user_id`) VALUES (:stoken , :userid )";
            $query2 = $pdo->prepare($sql2);
            $query2->bindValue(':stoken', $stoken, PDO::PARAM_STR);
            $query2->bindValue(':userid', $userid, PDO::PARAM_STR);
            $query2->execute();
            setcookie("LOGINTOKEN", $token, time() + 60 * 60 * 24 * 365, "/");
            $arr['token'] .= $token;
            $arr['uid'] .= $userid;
            $json = json_encode($arr);
            Helpers::addAuditLog("LOGGED_IN::{$_SERVER['REMOTE_ADDR']} Logged Into Account ID:{$userid} Username:{$selected_user->username}");
            echo $json;
        } else {
            Helpers::addAuditLog("AUTHENTICATION_FAILED::{$_SERVER['REMOTE_ADDR']} Triggered An Unauthenticated Response In `Login`");
            $arr['token'] .= "Failed";
            $json = json_encode($arr);
            echo $json;
        }
    } else if ($url == "signupUser") {
        $password = $_POST['password'];
        $cpassword = $_POST['cpassword'];
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];
        $username = $first_name . $last_name;
        $email = $_POST['email'];
        if (!empty($username) && !empty($password) && !empty($cpassword) && !empty($first_name) && !empty($last_name) && !empty($email)) {
            if ($password == $cpassword) {
                $password = password_hash($password, PASSWORD_DEFAULT);
                $username = preg_replace('/[^A-Za-z0-9\-]/', '', $username);
                $first_name = preg_replace('/[^A-Za-z0-9\-]/', '', $first_name);
                $last_name = preg_replace('/[^A-Za-z0-9\-]/', '', $last_name);
                $uniqid = bin2hex(openssl_random_pseudo_bytes(256));
                $sql = "SELECT * FROM users WHERE email = :email";
                $query = $pdo->prepare($sql);
                $query->bindValue(':email', $email, PDO::PARAM_STR);
                $query->execute();
                $result = $query->fetch();
                if ($result->email == "") {
                    $sql2 = "SELECT username FROM users WHERE username = :username";
                    $query2 = $pdo->prepare($sql2);
                    $query2->bindValue(':username', $username, PDO::PARAM_STR);
                    $query2->execute();
                    $result2 = $query2->fetch();
                    if ($result->username == "") {
                        $sql3 = "INSERT INTO users (`username`, `first_name`, `last_name`, `email`, `password`) VALUES (:username , :firstname , :lastname , :email , :password)";
                        $query3 = $pdo->prepare($sql3);
                        $query3->bindValue(':username', $username, PDO::PARAM_STR);
                        $query3->bindValue(':firstname', $first_name, PDO::PARAM_STR);
                        $query3->bindValue(':lastname', $last_name, PDO::PARAM_STR);
                        $query3->bindValue(':email', $email, PDO::PARAM_STR);
                        $query3->bindValue(':password', $password, PDO::PARAM_STR);
                        $query3->execute();
                        $latestID = $pdo->lastInsertId();
                        Helpers::addAuditLog("ACCOUNT_CREATED::{$_SERVER['REMOTE_ADDR']} Created Account {$username} With ID {$latestID}");
                        echo "Account Created.";
                    } else {
                        echo "Username Already Used.";
                    }
                } else {
                    echo "Email Already Used.";
                }
            } else {
                echo "Passwords Must Match.";
            }
        } else {
            echo "All Fields Are Required To Sign Up.";
        }
    } else if ($url == "logoutUser") {
        if (isset($_COOKIE['LOGINTOKEN'])) {
            $token = sha1($_COOKIE['LOGINTOKEN']);
            $sql = "SELECT token FROM login_tokens WHERE token = :token";
            $query = $pdo->prepare($sql);
            $query->bindValue(':token', $token, PDO::PARAM_STR);
            $query->execute();
            $result = $query->fetch();
            if ($result) {
                $sql2 = 'DELETE FROM login_tokens WHERE token = :token';
                $query = $pdo->prepare($sql2);
                $query->bindValue(':token', $token, PDO::PARAM_STR);
                $query->execute();
                setcookie("LOGINTOKEN", 0, 1, "/");
                echo '{ "Status": "Success" }';
                http_response_code(200);
            } else {
                echo '{ "Error": "Invalid token" }';
                http_response_code(400);
            }
        } else {
            echo '{ "Error": "Malformed request" }';
            http_response_code(400);
        }
    } else if ($url == "checkLogin") {
        if (isset($_COOKIE['LOGINTOKEN'])) {
            $token = sha1($_COOKIE['LOGINTOKEN']);
            $sql = "SELECT * FROM login_tokens WHERE token = :token";
            $query = $pdo->prepare($sql);
            $query->bindValue(':token', $token, PDO::PARAM_STR);
            $query->execute();
            $result = $query->fetch();
            if ($result) {
                echo true;
            }
        } else {
            Helpers::addAuditLog("AUTHENTICATION_FAILED::{$_SERVER['REMOTE_ADDR']} Triggered An Unauthenticated Response In `CheckLogin`");
            echo false;
        }
    } else if ($url == "addGuide") {
        $li = new User;

        if (Permissions::init()->hasPermission("GUIDE_ADD")) {
            $title = $_POST['title'];
            $body = $_POST['body'];
            $user = $li->info->first_name . " " . $li->info->last_name;
            $sql = "INSERT INTO guides (`title`, `body`, `author`) VALUES (:title, :body, :author)";
            $query = $pdo->prepare($sql);
            $query->bindValue(':title', $title, PDO::PARAM_STR);
            $query->bindValue(':body', $body, PDO::PARAM_STR);
            $query->bindValue(':author', $user, PDO::PARAM_STR);
            $query->execute();
            Helpers::addAuditLog("{$li->info->username} Added Guide {$title}");
            echo "Guide Added Successfully.";
        } else {
            Helpers::addAuditLog("AUTHENTICATION_FAILED::{$_SERVER['REMOTE_ADDR']} Triggered An Unauthenticated Response In `AddGuide`");
            echo "Insufficient Permissions.";
        }
    } else if ($url == "editGuide") {
        $li = new User;

        if (Permissions::init()->hasPermission("GUIDE_EDIT")) {
            $id = $_POST['id'];
            $title = $_POST['title'];
            $body = $_POST['body'];
            $user = $li->info->first_name . " " . $li->info->last_name;
            $sql = "UPDATE guides SET title = :title, body = :body, timestamp = CURRENT_TIMESTAMP() WHERE id=:id";
            $query = $pdo->prepare($sql);
            $query->bindValue(':title', $title, PDO::PARAM_STR);
            $query->bindValue(':body', $body, PDO::PARAM_STR);
            $query->bindValue(':id', $id, PDO::PARAM_STR);
            $query->execute();
            Helpers::addAuditLog("{$li->info->username} Edited Guide `{$title}`");
            echo "Guide Edited Successfully.";
        } else {
            Helpers::addAuditLog("AUTHENTICATION_FAILED::{$_SERVER['REMOTE_ADDR']} Triggered An Unauthenticated Response In `EditGuide`");
            echo "Insufficient Permissions.";
        }
    } else if ($url == "removeStaff") {
        $user = new User;

        $id = (isset($_POST['id'])) ? $_POST['id'] : null;

        if (Permissions::init()->hasPermission("REMOVE_USER")) {
            if ($id !== $user->info->id) {
                $deletedUsername = Helpers::IDToUsername($id);
                $sql = "DELETE FROM users WHERE id=?";
                $exec = $pdo->prepare($sql);
                $exec->execute([$_POST['id']]);
                Helpers::addAuditLog("{$user->info->username} Terminated Staff Member {$deletedUsername} From The Case Logger");
            } else {
                Helpers::addAuditLog("{$user->info->username} Tried to terminate their own account");
            }
        } else {
            Helpers::addAuditLog("AUTHENTICATION_FAILED::{$_SERVER['REMOTE_ADDR']} ~ {$user->info->username} ~ Triggered An Unauthenticated Response In `RemoveStaff`");
        }
    } else if ($url == "getFullGuide") {
        $user = new User;
        if (Permissions::init()->hasPermission("VIEW_GENERAL")) {
            $id = $_POST['id'];
            $sql = "SELECT * FROM guides WHERE id = :id";
            $query = $pdo->prepare($sql);
            $query->bindValue(':id', $id, PDO::PARAM_STR);
            $query->execute();
            $r = $query->fetch();
            $arr = [];
            $arr['title'] = htmlspecialchars($r->title);
            $arr['body'] = $r->body;
            $arr['author'] = $r->author;
            $arr['time'] = $r->timestamp;
            $arr['effective'] = $r->effective;
            echo json_encode($arr);
        } else {
            Helpers::addAuditLog("AUTHENTICATION_FAILED::{$_SERVER['REMOTE_ADDR']} Triggered An Unauthenticated Response In `GetFullGuide`");
        }
    } else if ($url == "getMoreInfo") {
        $user = new User;

        if (Permissions::init()->hasPermission("VIEW_GENERAL")) {
            $id = $_POST['id'];
            $sql = "SELECT * FROM case_logs WHERE id = :id";
            $query = $pdo->prepare($sql);
            $query->bindValue(':id', $id, PDO::PARAM_STR);
            $query->execute();
            $r = $query->fetch();
            $report = [];
            $stmt = $pdo->prepare("SELECT * FROM case_players WHERE case_id = :id");
            $stmt->bindValue(':id', $r->id, PDO::PARAM_INT);
            $stmt->execute();
            $players = $stmt->fetchAll();
            $players = Helpers::parsePlayers($players);
            $stmt = $pdo->prepare("SELECT * FROM punishment_reports WHERE case_id = :id");
            $stmt->bindValue(':id', $r->id, PDO::PARAM_INT);
            $stmt->execute();
            $punishments = $stmt->fetchAll();
            $stmt = $pdo->prepare("SELECT * FROM ban_reports WHERE case_id = :id");
            $stmt->bindValue(':id', $r->id, PDO::PARAM_INT);
            $stmt->execute();
            $bans = $stmt->fetchAll();
            if (count($punishments) > 1) {
                $points = "Yes";
            } else {
                $points = "No";
            }
            if (count($bans) > 1) {
                $ban = "Yes";
            } else {
                $ban = "No";
            }

            foreach ($punishments as $p) {
                $p->html = Helpers::parsePunishment($p);
            }

            foreach ($bans as $b) {
                $b->html = Helpers::parseBan($b);
            }

            $report['report']['id'] = $r->id;
            $report['report']['lead_staff'] = Helpers::ParseOtherStaff($r->lead_staff);
            $report['report']['lead_staff_id'] = Helpers::UsernameToID($r->lead_staff);
            $report['report']['other_staff'] = Helpers::ParseOtherStaff($r->other_staff);
            $report['report']['typeofreport'] = htmlspecialchars($r->type_of_report);
            $report['report']['players'] = $players;
            $report['report']['punishments'] = $punishments;
            $report['report']['bans'] = $bans;
            $report['report']['doe'] = htmlspecialchars($r->description_of_events);
            $report['report']['timestamp'] = htmlspecialchars($r->timestamp);
            echo Helpers::APIResponse("Fetched More Info", $report, 200);
        } else {
            Helpers::addAuditLog("AUTHENTICATION_FAILED::{$_SERVER['REMOTE_ADDR']} Triggered An Unauthenticated Response In `GetMoreInfo`");
            echo Helpers::APIResponse("Failed: Unauthorised", null, 401);
        }
    } else if ($url == "getSearchResults") {
        $user = new User;
        if (Permissions::init()->hasPermission("VIEW_SEARCH")) {
            $searchquery = $_POST['query'];
            $searchType = $_POST['type'];
            switch ($searchType) {
                case 'cases':
                    $sql = "SELECT * FROM `case_logs` WHERE `id` LIKE :query OR `lead_staff` LIKE :query OR `other_staff` LIKE :query OR `description_of_events` LIKE :query ORDER BY id DESC LIMIT 100";
                    $query = $pdo->prepare($sql);
                    $query->bindValue(':query', '%' . $searchquery . '%', PDO::PARAM_STR);
                    $query->execute();
                    $rf = $query->fetchAll();
                    $staffinfo = [];
                    $i = 1;
                    foreach ($rf as $r) {
                        $staffinfo['log'][$i]['id'] = $r->id;
                        $staffinfo['log'][$i]['doe'] = htmlspecialchars($r->description_of_events);
                        $staffinfo['log'][$i]['reporting_player'] = Helpers::getPlayersFromCase($r->id);
                        $i += 1;
                    }
                    $searchcount = count($staffinfo['log']);
                    $query = $pdo->prepare("SELECT count(*) as count FROM `case_logs` WHERE `id` LIKE :query OR `lead_staff` LIKE :query OR `other_staff` LIKE :query OR `description_of_events` LIKE :query");
                    $query->bindValue(':query', '%' . $searchquery . '%', PDO::PARAM_STR);
                    $query->execute();
                    $fetchCount = $query->fetch();
                    $totalcount = $fetchCount->count;
                    $refine = ($totalcount > 100) ? ' Refine Your Search Terms.' : '';
                    echo Helpers::APIResponse("Displaying {$searchcount} Of {$totalcount}{$refine}", $staffinfo, 200);
                    break;
                case 'punishments':
                    $sql = "SELECT * FROM `punishment_reports` WHERE (player LIKE :query OR comments LIKE :query OR rules LIKE :query) AND case_id <> 0 ORDER BY id DESC LIMIT 100";
                    $query = $pdo->prepare($sql);
                    $query->bindValue(':query', '%' . $searchquery . '%', PDO::PARAM_STR);
                    $query->execute();
                    $rf = $query->fetchAll();
                    $staffinfo = [];
                    $i = 1;
                    foreach ($rf as $r) {
                        $bl = htmlspecialchars($r->points);
                        if (is_integer($bl))
                            $bl = $bl . " Points";
                        $staffinfo['log'][$i]['id'] = $r->id;
                        $staffinfo['log'][$i]['case_id'] = $r->case_id;
                        $staffinfo['log'][$i]['doe'] = htmlspecialchars($r->comments);
                        $staffinfo['log'][$i]['points'] = $bl;
                        $staffinfo['log'][$i]['reporting_player'] = Helpers::getPlayersFromCase($r->case_id);
                        $i += 1;
                    }
                    $searchcount = count($staffinfo['log']);
                    $query = $pdo->prepare("SELECT count(*) as count FROM `punishment_reports` WHERE player LIKE :query OR comments LIKE :query OR rules LIKE :query");
                    $query->bindValue(':query', '%' . $searchquery . '%', PDO::PARAM_STR);
                    $query->execute();
                    $fetchCount = $query->fetch();
                    $totalcount = $fetchCount->count;
                    $refine = ($totalcount > 100) ? ' Refine Your Search Terms.' : '';
                    echo Helpers::APIResponse("Displaying {$searchcount} Of {$totalcount}{$refine}", $staffinfo, 200);
                    break;
                case 'bans':
                    $sql = "SELECT * FROM `ban_reports` WHERE (player LIKE :query OR message LIKE :query) AND case_id <> 0 ORDER BY id DESC LIMIT 100";
                    $query = $pdo->prepare($sql);
                    $query->bindValue(':query', '%' . $searchquery . '%', PDO::PARAM_STR);
                    $query->execute();
                    $rf = $query->fetchAll();
                    $staffinfo = [];
                    $i = 1;
                    foreach ($rf as $r) {
                        $bl = htmlspecialchars($r->length);
                        if ($bl == 0)
                            $bl = "Permanent Ban";
                        if ($bl != 0)
                            $bl = $bl . " Days";
                        $staffinfo['log'][$i]['id'] = $r->id;
                        $staffinfo['log'][$i]['case_id'] = $r->case_id;
                        $staffinfo['log'][$i]['player'] = $r->player;
                        $staffinfo['log'][$i]['doe'] = htmlspecialchars($r->message);
                        $staffinfo['log'][$i]['ban_length'] = $bl;
                        $staffinfo['log'][$i]['reporting_player'] = Helpers::getPlayersFromCase($r->case_id);
                        $i += 1;
                    }
                    $searchcount = count($staffinfo['log']);
                    $query = $pdo->prepare("SELECT count(*) as count FROM `ban_reports` WHERE (player LIKE :query OR message LIKE :query) AND case_id <> 0");
                    $query->bindValue(':query', '%' . $searchquery . '%', PDO::PARAM_STR);
                    $query->execute();
                    $fetchCount = $query->fetch();
                    $totalcount = $fetchCount->count;
                    $refine = ($totalcount > 100) ? ' Refine Your Search Terms.' : '';
                    echo Helpers::APIResponse("Displaying {$searchcount} Of {$totalcount}{$refine}", $staffinfo, 200);
                    break;
                case 'unbans':
                    $sql = "SELECT * FROM `case_logs` WHERE (`lead_staff` LIKE :query OR `other_staff` LIKE :query OR `description_of_events` LIKE :query) AND `type_of_report` = 'Unban Log' ORDER BY id DESC LIMIT 100";
                    $query = $pdo->prepare($sql);
                    $query->bindValue(':query', '%' . $searchquery . '%', PDO::PARAM_STR);
                    $query->execute();
                    $rf = $query->fetchAll();
                    $staffinfo = [];
                    $i = 1;
                    foreach ($rf as $r) {
                        $staffinfo['log'][$i]['id'] = $r->id;
                        $staffinfo['log'][$i]['doe'] = htmlspecialchars($r->description_of_events);
                        $staffinfo['log'][$i]['reporting_player'] = Helpers::getPlayersFromCase($r->id);
                        $i += 1;
                    }
                    $searchcount = count($staffinfo['log']);
                    $query = $pdo->prepare("SELECT count(*) as count FROM `case_logs` WHERE (`lead_staff` LIKE :query OR `other_staff` LIKE :query OR `description_of_events` LIKE :query) AND `type_of_report` = 'Unban Log'");
                    $query->bindValue(':query', '%' . $searchquery . '%', PDO::PARAM_STR);
                    $query->execute();
                    $fetchCount = $query->fetch();
                    $totalcount = $fetchCount->count;
                    $refine = ($totalcount > 100) ? ' Refine Your Search Terms.' : '';
                    echo Helpers::APIResponse("Displaying {$searchcount} Of {$totalcount}{$refine}", $staffinfo, 200);
                    break;
                case 'players':
                    $sql = "SELECT ANY_VALUE(`id`) as id, ANY_VALUE(`name`) as name, MAX(`guid`) as guid FROM `case_players` WHERE ANY_VALUE(`name`) LIKE :query OR ANY_VALUE(`guid`) LIKE :query OR ANY_VALUE(`case_id`) LIKE :query GROUP BY `name` LIMIT 100";
                    $query = $pdo->prepare($sql);
                    $query->bindValue(':query', '%' . $searchquery . '%', PDO::PARAM_STR);
                    $query->execute();
                    $rf = $query->fetchAll();
                    $staffinfo = [];
                    $i = 1;
                    foreach ($rf as $r) {
                        $staffinfo['log'][$i] = $r;
                        $staffinfo['log'][$i]->searchType = 'Player';
                        $i += 1;
                    }
                    $searchcount = count($staffinfo['log']);
                    $query = $pdo->prepare("SELECT count(*) as count FROM `case_players` WHERE `name` LIKE :query OR `guid` LIKE :query OR `case_id` LIKE :query");
                    $query->bindValue(':query', '%' . $searchquery . '%', PDO::PARAM_STR);
                    $query->execute();
                    $fetchCount = $query->fetch();
                    $totalcount = $fetchCount->count;
                    $refine = ($totalcount > 100) ? ' Refine Your Search Terms.' : '';
                    echo Helpers::APIResponse("Displaying {$searchcount} Of {$totalcount}{$refine}", $staffinfo, 200);
                    break;
                default:
                    Helpers::addAuditLog("No Search Type Given By {$user->info->username} Type: {$searchType} ~ Query {$searchquery}");
                    echo Helpers::APIResponse("No Search Type Given", null, 400);
                    break;
            }
        } else {
            Helpers::addAuditLog("AUTHENTICATION_FAILED::{$_SERVER['REMOTE_ADDR']} Triggered An Unauthenticated Response In `GetSearchResults`");
            echo Helpers::APIResponse("Search Failed: Unauthorised", null, 401);
        }
    } else if ($url == "getStaffActivity") {
        $user = new User;

        $id = (isset($_POST['id'])) ? $_POST['id'] : false;
        $field = (isset($_POST['field'])) ? $_POST['field'] : false;

        if (Permissions::init()->hasPermission("VIEW_USER_ACTIVITY")) {
            if ($field && $id) {
                $staffinfo = [];
                $staffMember = Helpers::IDToStaff($id);
                $username = $staffMember->info->username;
                $staffinfo['user']['username'] = $username;
                $staffinfo['user']['displayname'] = $staffMember->info->first_name . ' ' . $staffMember->info->last_name;
                $staffinfo['user']['id'] = $staffMember->info->id;
                switch ($field) {
                    case 'cases':
                        $stmt = $pdo->prepare("SELECT * FROM case_logs WHERE lead_staff LIKE :name OR other_staff LIKE :name ORDER BY id DESC");
                        $stmt->bindValue(':name', '%' . $username . '%', PDO::PARAM_STR);
                        $stmt->execute();
                        $rows = $stmt->fetchAll();
                        $i = 1;
                        $staffinfo['user']['username'] = $user->info->username;
                        $staffinfo['user']['id'] = $user->info->id;
                        foreach ($rows as $r) {
                            if (strpos($r->other_staff, $user->info->username) !== false) {
                                $staffinfo['log'][$i]['other_staff'] = true;
                            }
                            $staffinfo['log'][$i]['id'] = $r->id;
                            $staffinfo['log'][$i]['doe'] = htmlspecialchars($r->description_of_events);
                            $staffinfo['log'][$i]['reporting_player'] = Helpers::getPlayersFromCase($r->id);
                            $i += 1;
                        }
                        echo Helpers::APIResponse("Fetched Activity", $staffinfo, 200);
                        break;
                    case 'punishments':
                        $stmt = $pdo->prepare("SELECT * FROM case_logs WHERE lead_staff LIKE :name OR other_staff LIKE :name ORDER BY id DESC");
                        $stmt->bindValue(':name', '%' . $username . '%', PDO::PARAM_STR);
                        $stmt->execute();
                        $rows = $stmt->fetchAll();
                        $i = 1;

                        if (count($rows) < 1)
                            echo Helpers::APIResponse('No Cases Logged', null, 404);

                        foreach ($rows as $row) {
                            $stmt = $pdo->prepare('SELECT * FROM punishment_reports WHERE case_id = :id');
                            $stmt->bindValue(':id', $row->id, PDO::PARAM_INT);
                            $stmt->execute();
                            $punishments = $stmt->fetchAll();
                            foreach ($punishments as $punishment) {
                                $staffinfo['punishment'][$i]['id'] = $punishment->id;
                                $staffinfo['punishment'][$i]['case_id'] = $punishment->case_id;
                                $staffinfo['punishment'][$i]['comments'] = htmlspecialchars($punishment->comments);
                                $staffinfo['punishment'][$i]['player'] = $punishment->player;
                                $i++;
                            }
                        }

                        if (count($rows) < 1)
                            echo Helpers::APIResponse('No Punishments Logged', null, 404);

                        echo Helpers::APIResponse("Fetched", $staffinfo, 200);
                        break;
                    case 'bans':
                        $stmt = $pdo->prepare("SELECT * FROM case_logs WHERE lead_staff LIKE :name OR other_staff LIKE :name ORDER BY id DESC");
                        $stmt->bindValue(':name', '%' . $username . '%', PDO::PARAM_STR);
                        $stmt->execute();
                        $rows = $stmt->fetchAll();
                        $i = 1;

                        if (count($rows) < 1)
                            echo Helpers::APIResponse('No Cases Logged', null, 404);

                        foreach ($rows as $row) {
                            $stmt = $pdo->prepare('SELECT * FROM ban_reports WHERE case_id = :id');
                            $stmt->bindValue(':id', $row->id, PDO::PARAM_INT);
                            $stmt->execute();
                            $punishments = $stmt->fetchAll();
                            foreach ($punishments as $punishment) {
                                $staffinfo['punishment'][$i]['id'] = $punishment->id;
                                $staffinfo['punishment'][$i]['case_id'] = $punishment->case_id;
                                $staffinfo['punishment'][$i]['message'] = htmlspecialchars($punishment->message);
                                $staffinfo['punishment'][$i]['length'] = $punishment->length;
                                $staffinfo['punishment'][$i]['player'] = $punishment->player;
                                $i++;
                            }
                        }

                        if (count($rows) < 1)
                            echo Helpers::APIResponse('No Bans Logged', null, 404);

                        echo Helpers::APIResponse("Fetched", $staffinfo, 200);
                        break;
                    default:
                        echo Helpers::APIResponse("Invalid Field Provided", null, 400);
                        break;
                }
            }
        } else {
            Helpers::addAuditLog("AUTHENTICATION_FAILED::{$_SERVER['REMOTE_ADDR']} Triggered An Unauthenticated Response In `GetStaffActivity`");
            echo Helpers::APIResponse("Unauthorised", null, 401);
        }
    } else if ($url == "getMyActivity") {
        $user = new User;

        $field = (isset($_POST['field'])) ? $_POST['field'] : null;

        if (Permissions::init()->hasPermission("VIEW_GENERAL")) {
            if ($field) {
                switch ($field) {
                    case 'activity':
                        $stmt = $pdo->prepare("SELECT * FROM case_logs WHERE lead_staff LIKE :name OR other_staff LIKE :name ORDER BY id DESC");
                        $stmt->bindValue(':name', '%' . $user->info->username . '%', PDO::PARAM_STR);
                        $stmt->execute();
                        $rows = $stmt->fetchAll();
                        $staffinfo = [];
                        $i = 1;
                        $staffinfo['user']['username'] = $user->info->username;
                        $staffinfo['user']['id'] = $user->info->id;
                        foreach ($rows as $r) {
                            if (strpos($r->other_staff, $user->info->username) !== false) {
                                $staffinfo['log'][$i]['other_staff'] = true;
                            }
                            $staffinfo['log'][$i]['id'] = $r->id;
                            $staffinfo['log'][$i]['doe'] = htmlspecialchars($r->description_of_events);
                            $staffinfo['log'][$i]['reporting_player'] = Helpers::getPlayersFromCase($r->id);
                            $i += 1;
                        }
                        echo Helpers::APIResponse("Fetched Activity", $staffinfo, 200);
                        break;
                    case 'punishments':
                        $stmt = $pdo->prepare("SELECT * FROM case_logs WHERE lead_staff LIKE :name OR other_staff LIKE :name ORDER BY id DESC");
                        $stmt->bindValue(':name', '%' . $user->info->username . '%', PDO::PARAM_STR);
                        $stmt->execute();
                        $rows = $stmt->fetchAll();
                        $staffinfo = [];
                        $i = 1;

                        if (count($rows) < 1)
                            echo Helpers::APIResponse('No Cases Logged', null, 404);

                        foreach ($rows as $row) {
                            $stmt = $pdo->prepare('SELECT * FROM punishment_reports WHERE case_id = :id');
                            $stmt->bindValue(':id', $row->id, PDO::PARAM_INT);
                            $stmt->execute();
                            $punishments = $stmt->fetchAll();
                            foreach ($punishments as $punishment) {
                                $staffinfo['punishment'][$i]['id'] = $punishment->id;
                                $staffinfo['punishment'][$i]['case_id'] = $punishment->case_id;
                                $staffinfo['punishment'][$i]['comments'] = htmlspecialchars($punishment->comments);
                                $staffinfo['punishment'][$i]['player'] = $punishment->player;
                                $i++;
                            }
                        }

                        if (count($rows) < 1)
                            echo Helpers::APIResponse('No Punishments Logged', null, 404);

                        echo Helpers::APIResponse("Fetched", $staffinfo, 200);
                        break;
                    case 'bans':
                        $stmt = $pdo->prepare("SELECT * FROM case_logs WHERE lead_staff LIKE :name OR other_staff LIKE :name ORDER BY id DESC");
                        $stmt->bindValue(':name', '%' . $user->info->username . '%', PDO::PARAM_STR);
                        $stmt->execute();
                        $rows = $stmt->fetchAll();
                        $staffinfo = [];
                        $i = 1;

                        if (count($rows) < 1)
                            echo Helpers::APIResponse('No Cases Logged', null, 404);

                        foreach ($rows as $row) {
                            $stmt = $pdo->prepare('SELECT * FROM ban_reports WHERE case_id = :id');
                            $stmt->bindValue(':id', $row->id, PDO::PARAM_INT);
                            $stmt->execute();
                            $punishments = $stmt->fetchAll();
                            foreach ($punishments as $punishment) {
                                $staffinfo['punishment'][$i]['id'] = $punishment->id;
                                $staffinfo['punishment'][$i]['case_id'] = $punishment->case_id;
                                $staffinfo['punishment'][$i]['message'] = htmlspecialchars($punishment->message);
                                $staffinfo['punishment'][$i]['length'] = $punishment->length;
                                $staffinfo['punishment'][$i]['player'] = $punishment->player;
                                $i++;
                            }
                        }

                        if (count($rows) < 1)
                            echo Helpers::APIResponse('No Bans Logged', null, 404);

                        echo Helpers::APIResponse("Fetched", $staffinfo, 200);
                        break;
                    default:
                        echo Helpers::APIResponse("Invalid Field Provided", null, 400);
                        break;
                }
            } else {
                echo Helpers::APIResponse("Field Required", null, 400);
            }
        } else {
            Helpers::addAuditLog("AUTHENTICATION_FAILED::{$_SERVER['REMOTE_ADDR']} Triggered An Unauthenticated Response In `GetMyActivity`");
            echo Helpers::APIResponse("Unauthorised", null, 401);
        }
    } else if ($url == "getStaffMoreInfo") {
        $user = new User;

        if (Permissions::init()->hasPermission("VIEW_USER_INFO")) {
            $id = $_POST['id'];
            $staffinfo = [];
            $sql = "SELECT * FROM users WHERE id = :name";
            $query = $pdo->prepare($sql);
            $query->bindValue(':name', $_POST['id'], PDO::PARAM_STR);
            $query->execute();
            $r = $query->fetch();
            $staffname = $r->username;
            $stmt = $pdo->prepare("SELECT count(*) as Count FROM case_logs WHERE `lead_staff` LIKE :uname OR `other_staff` LIKE :uname");
            $stmt->bindValue(':uname', '%' . $staffname . '%', PDO::PARAM_STR);
            $stmt->execute();
            $AllTime = $stmt->fetch()->Count;
            $stmt = $pdo->prepare("SELECT count(*) as Count FROM case_logs WHERE `timestamp` > NOW() - INTERVAL 7 DAY AND (`lead_staff` LIKE :uname OR `other_staff` LIKE :uname)");
            $stmt->bindValue(':uname', '%' . $staffname . '%', PDO::PARAM_STR);
            $stmt->execute();
            $Recent = $stmt->fetch()->Count;
            $stmt = $pdo->prepare("SELECT count(*) as Count FROM case_logs WHERE `timestamp` > NOW() - INTERVAL 30 DAY AND (`lead_staff` LIKE :uname OR `other_staff` LIKE :uname)");
            $stmt->bindValue(':uname', '%' . $staffname . '%', PDO::PARAM_STR);
            $stmt->execute();
            $Month = $stmt->fetch()->Count;
            $stmt = $pdo->prepare("SELECT * FROM case_logs WHERE `timestamp` > NOW() - INTERVAL 7 DAY AND (`lead_staff` LIKE :uname OR `other_staff` LIKE :uname)");
            $stmt->bindValue(':uname', '%' . $staffname . '%', PDO::PARAM_STR);
            $stmt->execute();
            $Cases = $stmt->fetchAll();
            $recentCount = $Recent;
            $allTimeCount = $AllTime;
            //Check for activity warnings based on current weekly case count.
            $staffinfo['activity_warning'] = false;
            if ($r->rank_lvl >= 7) {
                if ($Recent < 10 && $Month < 80) {
                    $staffinfo['activity_warning'] = true;
                }
            }

            $staffinfo['onLOA'] = false;

            if ($r->loa !== null) {
                /** @noinspection PhpUnhandledExceptionInspection */
                if (new DateTime() < new DateTime($r->loa)) {
                    $staffinfo['onLOA'] = true;
                    $staffinfo['loaEND'] = $r->loa;
                }
            }

            $activityGraph = [
                'Today' => 0,
                'Yesterday' => 0,
                'Two Days Ago' => 0,
                'Three Days Ago' => 0,
                'Four Days Ago' => 0,
                'Five Days Ago' => 0,
                'A Week Ago' => 0
            ];

            foreach ($Cases as $case) {
                $i1 = time() - strtotime($case->timestamp);
                if ($i1 <= 86400) {
                    $activityGraph['Today']++;
                } else if ($i1 <= 86400 * 2) {
                    $activityGraph['Yesterday']++;
                } else if ($i1 <= 86400 * 3) {
                    $activityGraph['Two Days Ago']++;
                } else if ($i1 <= 86400 * 4) {
                    $activityGraph['Three Days Ago']++;
                } else if ($i1 <= 86400 * 5) {
                    $activityGraph['Four Days Ago']++;
                } else if ($i1 <= 86400 * 6) {
                    $activityGraph['Five Days Ago']++;
                } else if ($i1 <= 86400 * 7) {
                    $activityGraph['A Week Ago']++;
                }
            }

            if ($r->notes == null)
                $r->notes = '';
            if ($r->steamid == null)
                $r->steamid = '';
            if ($r->rank_lvl == null)
                $r->rank_lvl = 100;
            if ($r->lastPromotion == null)
                $r->lastPromotion = 'CHANGE ME';

            $real_highest_rank_position = Permissions::getHighestRank(json_decode($r->rank_groups, true));

            $user_highest_rank_position = Permissions::getHighestRank(json_decode($user->info->rank_groups, true));

            $user_highest_rank_position = ($user_highest_rank_position == 10) ? 0 : $user_highest_rank_position;

            if (Permissions::init()->isOverlord())
                $user_highest_rank_position = 0;

            $stmt = $pdo->prepare("SELECT * FROM rank_groups WHERE position > :p ORDER BY position");
            $stmt->bindValue(':p', $user_highest_rank_position, PDO::PARAM_INT);
            $stmt->execute();
            $ranks_available = $stmt->fetchAll();

            $stmt = $pdo->prepare('SELECT * FROM rank_groups WHERE position = :i');
            $stmt->bindValue(':i', $real_highest_rank_position, PDO::PARAM_INT);
            $stmt->execute();
            $ri = $stmt->fetch();
            $rank = ($ri !== false) ? $ri : 'Unranked';

            $ranks = [];
            foreach (json_decode($r->rank_groups) as $rr) {
                $stmt = $pdo->prepare('SELECT * FROM rank_groups WHERE id = :i');
                $stmt->bindValue(':i', $rr, PDO::PARAM_INT);
                $stmt->execute();
                $ri = $stmt->fetch();
                $ranks[] = ($ri !== false) ? $ri : 'Unknown';
            }


            $staffinfo['id'] = $r->id;
            $staffinfo['name'] = $staffname;
            $staffinfo['display_name'] = $r->first_name . ' ' . $r->last_name;
            $staffinfo['primary_rank'] = $rank;
            $staffinfo['all_ranks'] = $ranks;
            if ($user->info->rank_lvl <= 6) {
                $staffinfo['notes'] = $r->notes;
            } else {
                $staffinfo['notes'] = "SA+ to view staff notes";
            }
            $staffinfo['uid'] = $r->steamid;
            $staffinfo['lastPromotion'] = $r->lastPromotion;
            $staffinfo['rank_lvl'] = $r->rank_lvl;
            $staffinfo['team'] = $r->staff_team;
            $staffinfo['isSuspended'] = ($r->suspended) ? true : false;
            $staffinfo['region'] = $r->region;
            $staffinfo['activityGraph'] = (array) $activityGraph;
            $staffinfo['casecount'] = $allTimeCount;
            $staffinfo['casecount_week'] = $recentCount;
            $staffinfo['casecount_month'] = $Month;
            $staffinfo['ranks_available'] = $ranks_available;
            echo Helpers::APIResponse("Fetched User", $staffinfo, 200);
        } else {
            Helpers::addAuditLog("AUTHENTICATION_FAILED::{$_SERVER['REMOTE_ADDR']} Triggered An Unauthenticated Response In `GetStaffMoreInfo`");
            echo Helpers::APIResponse("Unauthorised", null, 401);
        }
    } else if ($url == "getMyInfo") {
        $user = new User;

        if (Permissions::init()->hasPermission("VIEW_GENERAL")) {
            $id = $_POST['id'];
            $staffinfo = [];
            $sql = "SELECT * FROM users WHERE id = :name";
            $query = $pdo->prepare($sql);
            $query->bindValue(':name', $user->info->id, PDO::PARAM_STR);
            $query->execute();
            $r = $query->fetch();
            $staffname = $r->username;
            $stmt = $pdo->prepare("SELECT count(*) as Count FROM case_logs WHERE `lead_staff` LIKE :uname OR `other_staff` LIKE :uname");
            $stmt->bindValue(':uname', '%' . $staffname . '%', PDO::PARAM_STR);
            $stmt->execute();
            $AllTime = $stmt->fetch()->Count;
            $stmt = $pdo->prepare("SELECT count(*) as Count FROM case_logs WHERE `timestamp` > NOW() - INTERVAL 7 DAY AND (`lead_staff` LIKE :uname OR `other_staff` LIKE :uname)");
            $stmt->bindValue(':uname', '%' . $staffname . '%', PDO::PARAM_STR);
            $stmt->execute();
            $Recent = $stmt->fetch()->Count;
            $stmt = $pdo->prepare("SELECT * FROM case_logs WHERE `timestamp` > NOW() - INTERVAL 7 DAY AND (`lead_staff` LIKE :uname OR `other_staff` LIKE :uname)");
            $stmt->bindValue(':uname', '%' . $staffname . '%', PDO::PARAM_STR);
            $stmt->execute();
            $Cases = $stmt->fetchAll();
            $recentCount = $Recent;
            $allTimeCount = $AllTime;
            //Check for activity warnings based on current weekly case count.
            $staffinfo['activity_warning'] = false;
            if ($r->rank_lvl >= 7) {
                if ($Recent < 10) {
                    $staffinfo['activity_warning'] = true;
                }
            }

            $staffinfo['onLOA'] = false;

            if ($r->loa !== null) {
                /** @noinspection PhpUnhandledExceptionInspection */
                if (new DateTime() < new DateTime($r->loa)) {
                    $staffinfo['onLOA'] = true;
                    $staffinfo['loaEND'] = $r->loa;
                }
            }

            $activityGraph = [
                'Today' => 0,
                'Yesterday' => 0,
                'Two Days Ago' => 0,
                'Three Days Ago' => 0,
                'Four Days Ago' => 0,
                'Five Days Ago' => 0,
                'A Week Ago' => 0
            ];

            foreach ($Cases as $case) {
                $i1 = time() - strtotime($case->timestamp);
                if ($i1 <= 86400) {
                    $activityGraph['Today']++;
                } else if ($i1 <= 86400 * 2) {
                    $activityGraph['Yesterday']++;
                } else if ($i1 <= 86400 * 3) {
                    $activityGraph['Two Days Ago']++;
                } else if ($i1 <= 86400 * 4) {
                    $activityGraph['Three Days Ago']++;
                } else if ($i1 <= 86400 * 5) {
                    $activityGraph['Four Days Ago']++;
                } else if ($i1 <= 86400 * 6) {
                    $activityGraph['Five Days Ago']++;
                } else if ($i1 <= 86400 * 7) {
                    $activityGraph['A Week Ago']++;
                }
            }

            if ($r->notes == null)
                $r->notes = '';
            if ($r->steamid == null)
                $r->steamid = '';
            if ($r->rank_lvl == null)
                $r->rank_lvl = 100;
            if ($r->lastPromotion == null)
                $r->lastPromotion = 'CHANGE ME';

            $staffinfo['id'] = $r->id;
            $staffinfo['name'] = $staffname;
            $staffinfo['rank'] = $r->rank;
            $staffinfo['notes'] = $r->notes;
            $staffinfo['uid'] = $r->steamid;
            $staffinfo['lastPromotion'] = $r->lastPromotion;
            $staffinfo['rank_lvl'] = $r->rank_lvl;
            $staffinfo['team'] = $r->staff_team;
            $staffinfo['activityGraph'] = (array) $activityGraph;
            $staffinfo['casecount'] = $allTimeCount;
            $staffinfo['casecount_week'] = $recentCount;
            echo Helpers::APIResponse("Fetched User", $staffinfo, 200);
        } else {
            Helpers::addAuditLog("AUTHENTICATION_FAILED::{$_SERVER['REMOTE_ADDR']} Triggered An Unauthenticated Response In `GetMyInfo`");
            echo Helpers::APIResponse("Unauthorised", null, 401);
        }
    } else if ($url == "addMeeting") {
        $user = new User;

        $date = (isset($_POST['date'])) ? $_POST['date'] : false;
        $type = (isset($_POST['type'])) ? $_POST['type'] : false;

        if (Permissions::init()->hasPermission("ADD_MEETING")) {
            if ($date && $type) {
                $types = [
                    "slt" => 0,
                    "staff" => 0,
                    "pd" => 0,
                    "ems" => 0
                ];

                $types[$type] = 1;

                $stmt = $pdo->prepare("INSERT INTO meetings (`date`, `slt`, `staff`, `pd`, `ems`) VALUES (:dte, :slt, :staff, :pd, :ems)");
                $stmt->bindValue(':dte', $date, PDO::PARAM_STR);
                $stmt->bindValue(':slt', $types['slt'], PDO::PARAM_STR);
                $stmt->bindValue(':ems', $types['ems'], PDO::PARAM_STR);
                $stmt->bindValue(':pd', $types['pd'], PDO::PARAM_STR);
                $stmt->bindValue(':staff', $types['staff'], PDO::PARAM_STR);
                if ($stmt->execute()) {
                    Helpers::addAuditLog("MEETINGS::{$user->info->username} Scheduled A Meeting On {$date} [Type: {$type}]");
                    echo Helpers::APIResponse("Meeting Added Successfully", null, 200);
                } else {
                    echo Helpers::APIResponse("Database Error", $stmt->errorinfo(), 500);
                }
            } else {
                echo Helpers::APIResponse("Invalid Request", null, 400);
            }
        } else {
            Helpers::addAuditLog("AUTHENTICATION_FAILED::{$_SERVER['REMOTE_ADDR']} Triggered An Unauthenticated Response In `AddMeeting`");
            echo Helpers::APIResponse("Authentication Failed", null, 403);
        }
    } else if ($url == "addCommentNew") {
        $user = new User;

        if (Permissions::init()->hasPermission("ADD_MEETING_COMMENT")) {
            $stmt = $pdo->prepare("INSERT INTO meeting_comments (`content`, `author`, `pointID`) VALUES (:content, :author, :id)");
            $stmt->bindValue(":id", $_POST['pointID'], PDO::PARAM_STR);
            $stmt->bindValue(":content", htmlspecialchars($_POST['content']), PDO::PARAM_STR);
            $stmt->bindValue(":author", $user->info->username, PDO::PARAM_STR);
            if ($stmt->execute()) {
                $data = ['canDelete' => 1, 'content' => htmlspecialchars($_POST['content']), 'author' => $user->getInfoForFrontend(), 'id' => $pdo->lastInsertId(), 'pointID' => htmlspecialchars($_POST['pointID'])];
                if (Helpers::PusherSend($data, 'meetings', 'addComment')) {
                    Helpers::addAuditLog("{$user->info->username} Added Comment To Meeting Point {$_POST['pointID']}");
                    echo Helpers::APIResponse("Success", null, 200);
                } else {
                    echo Helpers::APIResponse("Failed To Publish To Websocket", null, 500);
                }
            } else {
                echo Helpers::APIResponse("Database Error", $stmt->errorInfo(), 500);
            }
        } else {
            Helpers::addAuditLog("AUTHENTICATION_FAILED::{$_SERVER['REMOTE_ADDR']} Triggered An Unauthenticated Response In `AddCommentNew`");
            echo Helpers::APIResponse("Authentication Failed", null, 401);
        }
    } else if ($url == "addPointNew") {
        $user = new User;

        if (Permissions::init()->hasPermission("ADD_MEETING_POINT")) {
            $stmt = $pdo->prepare("INSERT INTO meeting_points (`name`, `description`, `author`, `meetingID`) VALUES (:pointname, :pointdescription, :author, :meetingID)");
            $stmt->bindValue(":pointname", htmlspecialchars($_POST['title']), PDO::PARAM_STR);
            $stmt->bindValue(":pointdescription", htmlspecialchars($_POST['description']), PDO::PARAM_STR);
            $stmt->bindValue(":author", $user->info->username, PDO::PARAM_STR);
            $stmt->bindValue(":meetingID", htmlspecialchars($_POST['mid']), PDO::PARAM_STR);
            if ($stmt->execute()) {
                $data = ['meetingID' => htmlspecialchars($_POST['mid']), 'id' => $pdo->lastInsertId(), 'name' => $_POST['title'], 'author' => $user->getInfoForFrontend()['displayName']];
                if (Helpers::PusherSend($data, "meetings", "addPoint")) {
                    Helpers::addAuditLog("{$user->info->username} Added A New Point `{$_POST['title']}` To Meeting {$_POST['mid']}");
                    echo Helpers::APIResponse("Added Point.", null, 200);
                } else {
                    echo Helpers::APIResponse("Failed To Publish To Pusher", null, 500);
                }
            } else {
                echo Helpers::APIResponse("Failed To Add Point", $stmt->errorinfo(), 500);
            }
        } else {
            Helpers::addAuditLog("AUTHENTICATION_FAILED::{$_SERVER['REMOTE_ADDR']} Triggered An Unauthenticated Response In `AddPointNew`");
            echo Helpers::APIResponse("Authentication Failed", null, 401);
        }
    } else if ($url == "sendMessage") {
        $li = new User();

        $content = $_POST['content'];

        if (Permissions::init()->hasPermission("SEND_MESSAGE")) {
            $stmt = $pdo->prepare('INSERT INTO staffMessages (`content`, `user`) VALUES (:content, :usr)');
            $stmt->bindValue(':content', htmlspecialchars($content), PDO::PARAM_STR);
            $stmt->bindValue(':usr', $li->info->id, PDO::PARAM_STR);
            if (!$stmt->execute()) {
                echo json_encode(["code" => 500, "message" => "database error"]);
                exit;
            }

            $data['username'] = $li->info->username;
            $data['message'] = htmlspecialchars($content);
            Helpers::PusherSend($data, 'staffchat-messages', 'receive');
            Helpers::addAuditLog("{$li->user->username} Sent Message {$content} To staffchat-messages");

            echo json_encode(["code" => 200, "message" => "sent"]);
        } else {
            Helpers::addAuditLog("AUTHENTICATION_FAILED::{$_SERVER['REMOTE_ADDR']} Triggered An Unauthenticated Response In `SendMessage`");
            echo json_encode(["code" => 401, "message" => "You're Not Authorised"]);
        }
    } else if ($url == "getMessages") {
        $li = new User();

        if (Permissions::init()->hasPermission("VIEW_MESSAGES")) {
            $stmt = $pdo->prepare('SELECT staffMessages.id, users.username AS username, staffMessages.content AS message, staffMessages.timestamp FROM staffMessages JOIN users 
                                           ON staffMessages.user = users.id LIMIT 100');
            if (!$stmt->execute()) {
                echo json_encode(["code" => 500, "message" => "database error"]);
                exit;
            }
            $messages = $stmt->fetchAll();
            if (!$messages) {
                echo json_encode(["code" => 404, "message" => "No Messages Found"]);
                exit;
            }
            echo json_encode(["list" => $messages, "code" => 200]);
        } else {
            echo json_encode(["message" => "Unauthorised", "code" => 401]);
            Helpers::addAuditLog("AUTHENTICATION_FAILED::{$_SERVER['REMOTE_ADDR']} Triggered An Unauthenticated Response In `GetMessages`");
        }
    } else if ($url == "saveStaffNotes") {
        $li = new User();

        if (Permissions::init()->hasPermission("EDIT_USER_NOTES")) {
            $stmt = $pdo->prepare('UPDATE users SET notes = :notes WHERE id = :id');
            $stmt->bindValue(':id', htmlspecialchars($_POST['id']), PDO::PARAM_STR);
            $stmt->bindValue(':notes', htmlspecialchars($_POST['notes']), PDO::PARAM_STR);
            if ($stmt->execute()) {
                $updatedUsername = Helpers::IDToUsername($_POST['id']);
                Helpers::addAuditLog("{$li->info->username} Saved Notes On {$updatedUsername}");
                echo "Success";
            } else {
                print_r($stmt->errorinfo());
            }
        } else {
            Helpers::addAuditLog("AUTHENTICATION_FAILED::{$_SERVER['REMOTE_ADDR']} Triggered An Unauthenticated Response In `SaveStaffNotes`");
        }
    } else if ($url == "saveStaffUID") {
        $li = new User();

        if (Permissions::init()->hasPermission("EDIT_USER_UUID")) {
            $stmt = $pdo->prepare('UPDATE users SET steamid = :uid WHERE id = :id');
            $stmt->bindValue(':id', htmlspecialchars($_POST['id']), PDO::PARAM_STR);
            $stmt->bindValue(':uid', htmlspecialchars($_POST['uid']), PDO::PARAM_STR);
            if ($stmt->execute()) {
                $updatedUsername = Helpers::IDToUsername($_POST['id']);
                Helpers::addAuditLog("{$li->info->username} Set UID For {$updatedUsername} To {$_POST['uid']}");
                echo "Success";
            } else {
                print_r($stmt->errorinfo());
            }
        } else {
            Helpers::addAuditLog("AUTHENTICATION_FAILED::{$_SERVER['REMOTE_ADDR']} Triggered An Unauthenticated Response In `SaveStaffUID`");
        }
    } else if ($url == "saveStaffPromotion") {
        $li = new User();

        if (Permissions::init()->hasPermission("EDIT_USER_PROMOTION") || $li->info->id == $_POST['id']) {
            $stmt = $pdo->prepare('UPDATE users SET lastPromotion = :promo WHERE id = :id');
            $stmt->bindValue(':id', htmlspecialchars($_POST['id']), PDO::PARAM_STR);
            $stmt->bindValue(':promo', htmlspecialchars($_POST['promotionTime']), PDO::PARAM_STR);
            $updatedUsername = Helpers::IDToUsername($_POST['id']);
            if ($stmt->execute()) {
                Helpers::addAuditLog("{$li->info->username} Updated {$updatedUsername}'s Last Promotion Date To {$_POST['promotionTime']}");
                echo "Success";
            } else {
                Helpers::addAuditLog("DATABASE_ERROR::{$li->info->username} Failed To Update {$updatedUsername}'s Last Promotion Date");
                print_r($stmt->errorinfo());
            }
        } else {
            Helpers::addAuditLog("AUTHENTICATION_FAILED::{$_SERVER['REMOTE_ADDR']} Triggered An Unauthenticated Response In `SaveStaffPromotion`");
            echo Helpers::APIResponse("Authentication Failed", null, 401);
        }
    } else if ($url == "saveStaffRegion") {
        $li = new User();

        if (Permissions::init()->hasPermission("EDIT_USER_REGION") || $li->info->id == $_POST['id']) {
            $stmt = $pdo->prepare('UPDATE users SET region = :reg WHERE id = :id');
            $stmt->bindValue(':id', htmlspecialchars($_POST['id']), PDO::PARAM_STR);
            $stmt->bindValue(':reg', htmlspecialchars($_POST['region']), PDO::PARAM_STR);
            $updatedUsername = Helpers::IDToUsername($_POST['id']);
            if ($stmt->execute()) {
                Helpers::addAuditLog("{$li->info->username} Updated {$updatedUsername}'s Region To {$_POST['region']}");
                echo "Success";
            } else {
                Helpers::addAuditLog("DATABASE_ERROR::{$li->info->username} Failed To Update {$updatedUsername}'s Region");
                print_r($stmt->errorinfo());
            }
        } else {
            Helpers::addAuditLog("AUTHENTICATION_FAILED::{$_SERVER['REMOTE_ADDR']} Triggered An Unauthenticated Response In `SaveStaffRegion`");
            echo Helpers::APIResponse("Authentication Failed", null, 401);
        }
    } else if ($url == "sendOnLOA") {
        $li = new User();

        if (Permissions::init()->hasPermission("SEND_USER_ON_LOA")) {
            $stmt = $pdo->prepare('UPDATE users SET loa = :tor WHERE id = :id');
            $stmt->bindValue(':id', htmlspecialchars($_POST['id']), PDO::PARAM_STR);
            $stmt->bindValue(':tor', htmlspecialchars($_POST['time']), PDO::PARAM_STR);
            $userOnLoaUsername = Helpers::IDToUsername($_POST['id']);
            if ($stmt->execute()) {
                Helpers::addAuditLog("{$li->info->username} Sent {$userOnLoaUsername} On LOA Until {$_POST['time']}");
                echo "Success";
            } else {
                Helpers::addAuditLog("DATABASE_ERROR::{$li->info->username} Failed To Send {$userOnLoaUsername} On LOA Until {$_POST['time']}");
                print_r($stmt->errorinfo());
            }
        } else {
            Helpers::addAuditLog("AUTHENTICATION_FAILED::{$_SERVER['REMOTE_ADDR']} Triggered An Unauthenticated Response In `SendOnLOA`");
            echo Helpers::APIResponse("Authentication Failed", null, 401);
        }
    } else if ($url == "deletePoint") {
        $user = new User;

        $stmt = $pdo->prepare("SELECT author FROM meeting_points WHERE id = :id");
        $stmt->bindValue(":id", $_POST['pointID'], PDO::PARAM_INT);
        $stmt->execute();
        $pointAuthor = $stmt->fetch();

        if (Permissions::init()->hasPermission("REMOVE_MEETING_POINT") || $pointAuthor->author == $user->info->username) {
            $stmt = $pdo->prepare("DELETE FROM meeting_points WHERE id = :id");
            $stmt->bindValue(':id', $_POST['pointID'], PDO::PARAM_INT);
            if ($stmt->execute()) {
                $stmt = $pdo->prepare("DELETE FROM meeting_comments WHERE pointID = :id");
                $stmt->bindValue(':id', $_POST['pointID'], PDO::PARAM_INT);
                if ($stmt->execute()) {
                    $data = ['deleteID' => htmlspecialchars($_POST['pointID'])];
                    if (Helpers::PusherSend($data, 'meetings', 'deletePoint')) {
                        echo Helpers::APIResponse("Success", null, 200);
                    } else {
                        echo Helpers::APIResponse("Failed To Publish To Pusher", null, 500);
                    }
                } else {
                    echo Helpers::APIResponse("Database Error [Failed To Delete Comments]", $stmt->errorInfo(), 500);
                }
            } else {
                echo Helpers::APIResponse("Database Error [Failed To Delete Point]", $stmt->errorInfo(), 500);
            }
        } else {
            Helpers::addAuditLog("AUTHENTICATION_FAILED::{$_SERVER['REMOTE_ADDR']} Triggered An Unauthenticated Response In `DeleteMeetingPoint`");
            echo Helpers::APIResponse("Authentication Failed", null, 401);
        }
    } else if ($url == "interview") {
        $user = new User;

        if (Permissions::init()->hasPermission("ADD_INTERVIEW")) {
            $stmt = $pdo->prepare("INSERT INTO staff_interviews (previous_experience, ever_banned_reason, how_much_time, time_away_from_server, work_flexibly, applicant_name, applicant_region, interviewer_id) VALUES (:prevexp, :everban, :howmuchtime, :awayfrom, :workflex, :name, :region, :id)");
            $stmt->bindValue(':prevexp', $_POST['previousExperience'], PDO::PARAM_STR);
            $stmt->bindValue(':everban', $_POST['previousBans'], PDO::PARAM_STR);
            $stmt->bindValue(':howmuchtime', $_POST['dedicateTime'], PDO::PARAM_STR);
            $stmt->bindValue(':awayfrom', $_POST['timeAwayFromServer'], PDO::PARAM_STR);
            $stmt->bindValue(':workflex', $_POST['workFlexibly'], PDO::PARAM_STR);
            $stmt->bindValue(':name', $_POST['applicantName'], PDO::PARAM_STR);
            $stmt->bindValue(':region', $_POST['applicantRegion'], PDO::PARAM_STR);
            $stmt->bindValue(':id', $user->info->id, PDO::PARAM_STR);
            if ($stmt->execute()) {
                echo Helpers::APIResponse("Success", null, 200);
            } else {
                echo Helpers::APIResponse("Database Error", $stmt->errorInfo(), 500);
            }
        } else {
            Helpers::addAuditLog("AUTHENTICATION_FAILED::{$_SERVER['REMOTE_ADDR']} Triggered An Unauthenticated Response In `AddInterview`");
            echo Helpers::APIResponse("Authentication Failed", null, 401);
        }
    } else if ($url == "editInterview") {
        $user = new User;

        if (Permissions::init()->hasPermission("EDIT_INTERVIEW")) {
            $stmt = $pdo->prepare("UPDATE staff_interviews SET previous_experience = :prevexp, ever_banned_reason = :everban, how_much_time = :howmuchtime, time_away_from_server = :awayfrom, work_flexibly = :workflex, applicant_name = :name, applicant_region = :region, passed = :passed, processed = :processed WHERE id = :id");
            $stmt->bindValue(':prevexp', $_POST['edit_previousExperience'], PDO::PARAM_STR);
            $stmt->bindValue(':everban', $_POST['edit_previousBans'], PDO::PARAM_STR);
            $stmt->bindValue(':howmuchtime', $_POST['edit_dedicateTime'], PDO::PARAM_STR);
            $stmt->bindValue(':awayfrom', $_POST['edit_timeAwayFromServer'], PDO::PARAM_STR);
            $stmt->bindValue(':workflex', $_POST['edit_workFlexibly'], PDO::PARAM_STR);
            $stmt->bindValue(':name', $_POST['edit_applicantName'], PDO::PARAM_STR);
            $stmt->bindValue(':region', $_POST['edit_applicantRegion'], PDO::PARAM_STR);
            $stmt->bindValue(':passed', intval($_POST['edit_passed']), PDO::PARAM_INT);
            $stmt->bindValue(':processed', intval($_POST['edit_processed']), PDO::PARAM_INT);
            $stmt->bindValue(':id', $_POST['updateID'], PDO::PARAM_STR);
            if ($stmt->execute()) {
                Helpers::addAuditLog("{$user->info->username} Edited Interview {$_POST['updateID']}");
                echo Helpers::APIResponse("Success", null, 200);
            } else {
                Helpers::addAuditLog("DATABASE_ERROR::{$user->info->username} Failed To Edit Interview {$_POST['updateID']}");
                echo Helpers::APIResponse("Database Error", $stmt->errorInfo(), 500);
            }
        } else {
            Helpers::addAuditLog("AUTHENTICATION_FAILED::{$_SERVER['REMOTE_ADDR']} Triggered An Unauthenticated Response In `EditInterview`");
            echo Helpers::APIResponse("Authentication Failed", null, 401);
        }
    } else if ($url == "playerChangeAdminLevel") {
        $user = new User;

        if (Permissions::init()->hasPermission("EDIT_PLAYER_ADMIN")) {
            $uid = (isset($_POST['id'])) ? $_POST['id'] : null;
            $al = (isset($_POST['al'])) ? $_POST['al'] : null;

            $gamepdo = game_pdo();

            if ($uid == null || $al == null) {
                echo Helpers::APIResponse("No ID OR AdminLevel Passed", null, 400);
                exit;
            }

            $stmt = $gamepdo->prepare('UPDATE `players` SET adminlevel = :al WHERE uid = :uid');
            $stmt->bindValue(':uid', $uid, PDO::PARAM_INT);
            $stmt->bindValue(':al', $al, PDO::PARAM_INT);
            if ($stmt->execute()) {
                Helpers::addAuditLog("GAME::{$user->info->username} Changed Game_Player({$uid}) Set AdminLevel = {$al}");
                echo Helpers::APIResponse("Success", null, 200);
            } else {
                Helpers::addAuditLog("DATABASE_ERROR::{$user->info->username} Failed To Change Game_Player({$uid})::" . json_encode($stmt->errorInfo()));
                echo Helpers::APIResponse("Database Error", $stmt->errorInfo(), 500);
            }
        } else {
            Helpers::addAuditLog("GAME_PLAYER_UNAUTHORISED::{$user->info->username} Failed To Change Game_Player Insufficient Rank");
            echo Helpers::APIResponse("Insufficient Rank", null, 401);
        }
    } else if ($url == "playerChangeMedicLevel") {
        $user = new User;

        if (Permissions::init()->hasPermission("EDIT_PLAYER_MEDIC")) {
            $uid = (isset($_POST['id'])) ? $_POST['id'] : null;
            $ml = (isset($_POST['ml'])) ? $_POST['ml'] : null;

            $gamepdo = game_pdo();

            if ($uid == null || $ml == null) {
                echo Helpers::APIResponse("No ID OR MedicLevel Passed", null, 400);
                exit;
            }

            $stmt = $gamepdo->prepare('UPDATE `players` SET mediclevel = :ml WHERE uid = :uid');
            $stmt->bindValue(':uid', $uid, PDO::PARAM_INT);
            $stmt->bindValue(':ml', $ml, PDO::PARAM_INT);
            if ($stmt->execute()) {
                Helpers::addAuditLog("GAME::{$user->info->username} Changed Game_Player({$uid}) Set MedicLevel = {$ml}");
                echo Helpers::APIResponse("Success", null, 200);
            } else {
                Helpers::addAuditLog("DATABASE_ERROR::{$user->info->username} Failed To Change Game_Player({$uid})::" . json_encode($stmt->errorInfo()));
                echo Helpers::APIResponse("Database Error", $stmt->errorInfo(), 500);
            }
        } else {
            Helpers::addAuditLog("GAME_PLAYER_UNAUTHORISED::{$user->info->username} Failed To Change Game_Player Insufficient Rank");
            echo Helpers::APIResponse("Insufficient Rank", null, 401);
        }
    } else if ($url == "playerChangeMedicDepartment") {
        $user = new User;

        if (Permissions::init()->hasPermission("EDIT_PLAYER_MEDIC")) {
            $uid = (isset($_POST['id'])) ? $_POST['id'] : null;
            $ml = (isset($_POST['md'])) ? $_POST['md'] : null;

            $gamepdo = game_pdo();

            if ($uid == null || $ml == null) {
                echo Helpers::APIResponse("No ID OR MedicLevel Passed", null, 400);
                exit;
            }

            $stmt = $gamepdo->prepare('UPDATE `players` SET medicdept = :ml WHERE uid = :uid');
            $stmt->bindValue(':uid', $uid, PDO::PARAM_INT);
            $stmt->bindValue(':ml', $ml, PDO::PARAM_INT);
            if ($stmt->execute()) {
                Helpers::addAuditLog("GAME::{$user->info->username} Changed Game_Player({$uid}) Set MedicDept = {$ml}");
                echo Helpers::APIResponse("Success", null, 200);
            } else {
                Helpers::addAuditLog("DATABASE_ERROR::{$user->info->username} Failed To Change Game_Player({$uid})::" . json_encode($stmt->errorInfo()));
                echo Helpers::APIResponse("Database Error", $stmt->errorInfo(), 500);
            }
        } else {
            Helpers::addAuditLog("GAME_PLAYER_UNAUTHORISED::{$user->info->username} Failed To Change Game_Player Insufficient Rank");
            echo Helpers::APIResponse("Insufficient Rank", null, 401);
        }
    } else if ($url == "playerChangePoliceLevel") {
        $user = new User;

        if (Permissions::init()->hasPermission("EDIT_PLAYER_POLICE")) {
            $uid = (isset($_POST['id'])) ? $_POST['id'] : null;
            $pl = (isset($_POST['pl'])) ? $_POST['pl'] : null;

            $gamepdo = game_pdo();

            if ($uid == null || $pl == null) {
                echo Helpers::APIResponse("No ID OR PoliceLevel Passed", null, 400);
                exit;
            }

            $stmt = $gamepdo->prepare('UPDATE `players` SET coplevel = :pl WHERE uid = :uid');
            $stmt->bindValue(':uid', $uid, PDO::PARAM_INT);
            $stmt->bindValue(':pl', $pl, PDO::PARAM_INT);
            if ($stmt->execute()) {
                Helpers::addAuditLog("GAME::{$user->info->username} Changed Game_Player({$uid}) Set PoliceLevel = {$pl}");
                echo Helpers::APIResponse("Success", null, 200);
            } else {
                Helpers::addAuditLog("DATABASE_ERROR::{$user->info->username} Failed To Change Game_Player({$uid})::" . json_encode($stmt->errorInfo()));
                echo Helpers::APIResponse("Database Error", $stmt->errorInfo(), 500);
            }
        } else {
            Helpers::addAuditLog("GAME_PLAYER_UNAUTHORISED::{$user->info->username} Failed To Change Game_Player Insufficient Rank");
            echo Helpers::APIResponse("Insufficient Rank", null, 401);
        }
    } else if ($url == "playerChangePoliceDepartment") {
        $user = new User;

        if (Permissions::init()->hasPermission("EDIT_PLAYER_POLICE")) {
            $uid = (isset($_POST['id'])) ? $_POST['id'] : null;
            $ml = (isset($_POST['pd'])) ? $_POST['pd'] : null;

            $gamepdo = game_pdo();

            if ($uid == null || $ml == null) {
                echo Helpers::APIResponse("No ID OR MedicLevel Passed", null, 400);
                exit;
            }

            $stmt = $gamepdo->prepare('UPDATE `players` SET copdept = :ml WHERE uid = :uid');
            $stmt->bindValue(':uid', $uid, PDO::PARAM_INT);
            $stmt->bindValue(':ml', $ml, PDO::PARAM_INT);
            if ($stmt->execute()) {
                Helpers::addAuditLog("GAME::{$user->info->username} Changed Game_Player({$uid}) Set PoliceDept = {$ml}");
                echo Helpers::APIResponse("Success", null, 200);
            } else {
                Helpers::addAuditLog("DATABASE_ERROR::{$user->info->username} Failed To Change Game_Player({$uid})::" . json_encode($stmt->errorInfo()));
                echo Helpers::APIResponse("Database Error", $stmt->errorInfo(), 500);
            }
        } else {
            Helpers::addAuditLog("GAME_PLAYER_UNAUTHORISED::{$user->info->username} Failed To Change Game_Player Insufficient Rank");
            echo Helpers::APIResponse("Insufficient Rank", null, 401);
        }
    } else if ($url == "playerChangeBalance") {
        $user = new User;

        if (Permissions::init()->hasPermission("EDIT_PLAYER_BALANCE")) {
            $uid = (isset($_POST['id'])) ? $_POST['id'] : null;
            $pb = (isset($_POST['pb'])) ? $_POST['pb'] : null;

            $gamepdo = game_pdo();

            if ($uid == null || $pb == null) {
                echo Helpers::APIResponse("No ID OR Balance Passed", null, 400);
                exit;
            }

            if ($pb == 'NaN')
                $pb = 0;

            $stmt = $gamepdo->prepare('SELECT * FROM `players` WHERE uid = :uid');
            $stmt->bindValue(':uid', $uid, PDO::PARAM_INT);
            $stmt->execute();
            $current = $stmt->fetch(PDO::FETCH_OBJ);

            $stmt = $gamepdo->prepare('UPDATE `players` SET bankacc = :pb WHERE uid = :uid');
            $stmt->bindValue(':uid', $uid, PDO::PARAM_INT);
            $stmt->bindValue(':pb', $pb, PDO::PARAM_INT);
            if ($stmt->execute()) {
                $comp = (isset($_POST['comp'])) ? ' [COMPENSATION]' : '';
                Helpers::addAuditLog("GAME::{$user->info->username} Changed Game_Player({$uid}) [Currently \${$current->bankacc}] Changed Balance To \${$pb}{$comp}");
                echo Helpers::APIResponse("Success", null, 200);
            } else {
                Helpers::addAuditLog("DATABASE_ERROR::{$user->info->username} Failed To Change Game_Player({$uid})::" . json_encode($stmt->errorInfo()));
                echo Helpers::APIResponse("Database Error", $stmt->errorInfo(), 500);
            }
        } else {
            Helpers::addAuditLog("GAME_PLAYER_UNAUTHORISED::{$user->info->username} Failed To Change Game_Player Insufficient Rank");
            echo Helpers::APIResponse("Insufficient Rank", null, 401);
        }
    } else if ($url == "punishment") {
        $user = new User;

        if (Permissions::init()->hasPermission("ADD_PUNISHMENT")) {
            $points = (isset($_POST['points']) && !empty($_POST['points'])) ? htmlspecialchars($_POST['points']) : false;
            $rules = (isset($_POST['rules']) && !empty($_POST['rules'])) ? htmlspecialchars($_POST['rules']) : false;
            $comments = (isset($_POST['comments']) && !empty($_POST['comments'])) ? htmlspecialchars($_POST['comments']) : false;
            $player = (isset($_POST['player']) && !empty($_POST['player'])) ? htmlspecialchars($_POST['player']) : false;

            if (!$player || $player == "No Reported Players Found" || $player == "Choose A Player") {
                echo Helpers::APIResponse("Invalid Player Selected", null, 400);
                exit;
            }

            if ($points && $rules && $comments) {
                $stmt = $pdo->prepare('INSERT INTO punishment_reports (points, rules, comments, player) VALUES (:p, :r, :c, :pl)');

                $stmt->bindValue(':p', $points, PDO::PARAM_INT);
                $stmt->bindValue(':r', $rules, PDO::PARAM_STR);
                $stmt->bindValue(':c', $comments, PDO::PARAM_STR);
                $stmt->bindValue(':pl', $player, PDO::PARAM_STR);

                if ($stmt->execute()) {
                    echo Helpers::APIResponse("Success", [$pdo->lastInsertId()], 200);
                } else {
                    Helpers::addAuditLog("ERROR::Database Error At (Add Punishment)" . json_encode($stmt->errorInfo()));
                    echo Helpers::APIResponse("Database Error", $stmt->errorInfo(), 500);
                }
            } else {
                echo Helpers::APIResponse("All Fields Are Required", null, 400);
            }
        } else {
            Helpers::addAuditLog("UNAUTHORISED::Authentication Failed At (Add Punishment)");
            echo Helpers::APIResponse("Authentication Failed", null, 401);
        }
    } else if ($url == "ban") {
        $user = new User;

        if (Permissions::init()->hasPermission("ADD_BAN")) {
            $length = (isset($_POST['length'])) ? htmlspecialchars($_POST['length']) : null;
            $message = (isset($_POST['message']) && !empty($_POST['message'])) ? htmlspecialchars($_POST['message']) : false;
            $teamspeak = (isset($_POST['teamspeak'])) ? htmlspecialchars($_POST['teamspeak']) : null;
            $ingame = (isset($_POST['ingame'])) ? htmlspecialchars($_POST['ingame']) : null;
            $website = (isset($_POST['website'])) ? htmlspecialchars($_POST['website']) : null;
            $permanent = (isset($_POST['permanent'])) ? htmlspecialchars($_POST['permanent']) : null;
            $player = (isset($_POST['player']) && !empty($_POST['player'])) ? $_POST['player'] : false;
            $addBattlemetrics = (isset($_POST['battlemetrics']) && !empty($_POST['battlemetrics'])) ? $_POST['battlemetrics'] : false;

            if (!$player || $player['name'] == "No Reported Players Found" || $player['name'] == "Choose A Player") {
                echo Helpers::APIResponse("Invalid Player Selected", null, 400);
                exit;
            }

            if ($addBattlemetrics) {
                if (Permissions::init()->hasPermission("ADD_BAN")) {
                    if ($length == 0 && Permissions::init()->hasPermission("ADD_BAN_PERMANENT")) {
                        echo Helpers::APIResponse("You can't issue permanent bans.", null, 400);
                        exit;
                    }
                    if (!$player['uuid']) {
                        echo Helpers::APIResponse("Invalid Player SteamID", null, 400);
                        exit;
                    }
                    Helpers::BattlemetricsIssueBan($player['uuid'], $message, $user->info->username, $length);
                } else {
                    echo Helpers::APIResponse("You Can't Add Bans To Battlemetrics (Admin+)", null, 400);
                    exit;
                }
            }

            if ($length !== null && $message && $teamspeak !== null && $ingame !== null && $website !== null && $permanent !== null) {
                $stmt = $pdo->prepare('INSERT INTO ban_reports (length, message, teamspeak, ingame, website, permenant, player) VALUES (:l, :m, :t, :i, :w, :p, :pl)');

                $stmt->bindValue(':l', $length, PDO::PARAM_INT);
                $stmt->bindValue(':m', $message, PDO::PARAM_STR);
                $stmt->bindValue(':t', $teamspeak, PDO::PARAM_BOOL);
                $stmt->bindValue(':i', $ingame, PDO::PARAM_BOOL);
                $stmt->bindValue(':w', $website, PDO::PARAM_BOOL);
                $stmt->bindValue(':p', $permanent, PDO::PARAM_BOOL);
                $stmt->bindValue(':pl', htmlspecialchars($player['name']), PDO::PARAM_STR);

                if ($stmt->execute()) {
                    echo Helpers::APIResponse("Success", [$pdo->lastInsertId()], 200);
                } else {
                    Helpers::addAuditLog("ERROR::Database Error At (Add Ban)" . json_encode($stmt->errorInfo()));
                    echo Helpers::APIResponse("Database Error", $stmt->errorInfo(), 500);
                }
            } else {
                echo Helpers::APIResponse("All Fields Are Required", null, 400);
            }
        } else {
            Helpers::addAuditLog("UNAUTHORISED::Authentication Failed At (Add Ban)");
            echo Helpers::APIResponse("Authentication Failed", null, 401);
        }
    } else if ($url == "markEssentialRead") {
        $user = new User;

        if (Permissions::init()->hasPermission("VIEW_GENERAL")) {
            $stmt = $pdo->prepare('UPDATE users SET readEssentialNotification = 1 WHERE id = :id');
            $stmt->bindValue(':id', $user->info->id, PDO::PARAM_INT);
            $stmt->execute();

            echo Helpers::APIResponse('Marked As Read', null, 200);
        } else {
            echo Helpers::APIResponse('Unauthorised', null, 401);
        }
    } else if ($url == "changeStaffName") {
        $user = new User;

        $newName = (isset($_POST['newName'])) ? $_POST['newName'] : false;
        $id = (isset($_POST['id'])) ? $_POST['id'] : false;

        if ($id && $newName && Permissions::init()->hasPermission("EDIT_USER_NAME")) {
            $stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id');
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $edit = $stmt->fetch();

            $username = $edit->username;

            $nameArray = explode(' ', $newName);

            if (count($nameArray) != 2) {
                echo Helpers::APIResponse("First And Last Name Required", null, 400);
                exit;
            }

            $firstName = $nameArray[0];
            $lastName = $nameArray[1];
            $userName = $nameArray[0] . $nameArray[1];

            $stmt = $pdo->prepare('SELECT id, lead_staff FROM case_logs WHERE lead_staff LIKE :username');
            $stmt->bindValue(':username', "%{$username}%", PDO::PARAM_STR);
            $stmt->execute();
            $cases = $stmt->fetchAll();

            foreach ($cases as $case) {
                $stmt = $pdo->prepare('UPDATE case_logs SET lead_staff = :ls WHERE id = :id');
                $stmt->bindValue(':ls', $userName, PDO::PARAM_STR);
                $stmt->bindValue(':id', $case->id, PDO::PARAM_INT);
                $stmt->execute();
            }

            $stmt = $pdo->prepare('UPDATE users SET first_name = :fn, last_name = :ln, username = :un WHERE id = :id');
            $stmt->bindValue(':fn', $firstName, PDO::PARAM_STR);
            $stmt->bindValue(':ln', $lastName, PDO::PARAM_STR);
            $stmt->bindValue(':un', $userName, PDO::PARAM_STR);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            Helpers::addAuditLog("Changed {$username}'s Name To {$newName}");
            echo Helpers::APIResponse("Success", null, 200);
        } else {
            echo Helpers::APIResponse("Error", null, 401);
        }
    } else if ($url == "savePage") {
        $user = new User;

        $id = (isset($_POST['id'])) ? $_POST['id'] : false;
        $content = (isset($_POST['content'])) ? $_POST['content'] : false;

        if (Permissions::init()->hasPermission("EDIT_NOTEBOOK_PAGE") && $content && $id) {
            $stmt = $pdo->prepare('UPDATE pages SET content = :c WHERE id = :id');
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->bindValue(':c', $content, PDO::PARAM_STR);
            if ($stmt->execute()) {
                echo Helpers::APIResponse("Saved Page", null, 200);
            } else {
                echo Helpers::APIResponse("Database Error", $stmt->errorInfo(), 500);
            }
        } else {
            echo Helpers::APIResponse("Unauthorised or Invalid Request", null, 401);
        }
    } else if ($url == "createPage") {
        $user = new User;

        $title = (isset($_POST['title'])) ? $_POST['title'] : false;

        if (Permissions::init()->hasPermission("ADD_NOTEBOOK_PAGE") && $title) {
            $stmt = $pdo->prepare('INSERT INTO `pages` (`content`, `title`, `creator_id`) VALUES (:c, :t, :cid)');
            $stmt->bindValue(':c', '# This is your new page, enjoy', PDO::PARAM_STR);
            $stmt->bindValue(':t', $title, PDO::PARAM_STR);
            $stmt->bindValue(':cid', $user->info->id, PDO::PARAM_INT);
            if ($stmt->execute()) {
                echo Helpers::APIResponse("Created Page", null, 200);
            } else {
                echo Helpers::APIResponse("Database Error", $stmt->errorInfo(), 500);
            }
        } else {
            echo Helpers::APIResponse("Unauthorised or Invalid Request", null, 401);
        }
    } else if ($url == "activatePurchase") {
        $lk = (isset($_POST['license'])) ? htmlspecialchars($_POST['license']) : false;

        if ($lk) {
            try {
                $url_lkInfo = 'https://forums.arma-life.com/api/v1/nexus/lkey/' . $lk . '?key=5233a48be88f86b0cd4ffb7013f0cf33';

                $tok = Config::$battleMetrics['apiKey'];

                $order = Unirest\Request::get($url_lkInfo);

                if ($order->body->errorMessage) {
                    echo Helpers::APIResponse("An Error Occoured: " . $order->body->errorMessage, null, 500);
                    exit;
                }

                $activateBody = Unirest\Request\Body::Form([
                    "key" => $order->body->licenseKey,
                    "identifier" => "",
                    "extra" => [
                        "activator_ip" => $_SERVER['REMOTE_ADDR']
                    ]
                ]);

                $info = Unirest\Request::post('https://forums.arma-life.com/applications/nexus/interface/licenses/?info', null, $activateBody);

                if ($info->body->uses < $info->body->max_uses) {
                    $activate = Unirest\Request::post('https://forums.arma-life.com/applications/nexus/interface/licenses/?activate', null, $activateBody);
                    if ($activate->body->response == "OKAY") {
                        $headers = [
                            "Authorization" => 'Bearer ' . $tok,
                            'Accept' => 'application/json',
                            'Content-Type' => 'application/json',
                        ];

                        $bmReq = json_decode(Helpers::getBMMakeReservedRequest(json_decode(json_encode($order->body), true)['customFields'][1], $order->body->licenseKey, $order->body->id, $order->body->customer->name));

                        $body = Unirest\Request\Body::json($bmReq);

                        $reserved = Unirest\Request::post('https://api.battlemetrics.com/reserved-slots', $headers, $body);

                        echo Helpers::APIResponse("Success", null, 200);
                    } else {
                        echo Helpers::APIResponse("An Error Occoured: Failed To Activate Key", null, 500);
                        exit;
                    }
                } else {
                    echo Helpers::APIResponse("An Error Occoured: Max Uses Reached", null, 500);
                    exit;
                }
            } catch (Exception $e) {
                Helpers::addAuditLog('ERROR::Unirest Error [Get Player SteamID Match] ' . $e->getMessage());
                return false;
            }
        } else {
            echo Helpers::APIResponse("Failed", null, 401);
        }
    } else if ($url == "factionSaveRank") {
        $user = new User;

        $playerID = (isset($_POST['playerID'])) ? $_POST['playerID'] : false;
        $rank = (isset($_POST['rank'])) ? $_POST['rank'] : false;


        if ($user->verified(false) && ($user->isCommand() || $user->isSLT())) {
            $player = new User($playerID);

            $customSQL = ($user->isSLT()) ? "isCommand = 0," : "";

            if ($player->isPD()) {
                $faction = 'police';
                if ($rank > 5 && $user->isSLT()) {
                    $customSQL = "isCommand = 1,";
                }
                if ($rank < 1) {
                    $customSQL .= " isPD = 0,";
                }
                if ($rank > 0) {
                    $customSQL .= " isPD = 1,";
                }
            } else {
                $faction = 'medic';
                if ($rank > 4 && $user->isSLT()) {
                    $customSQL = "isCommand = 1,";
                }
                if ($rank < 1) {
                    $customSQL .= " isEMS = 0,";
                }
                if ($rank > 0) {
                    $customSQL .= " isEMS = 1,";
                }
            }

            $stmt = $pdo->prepare("UPDATE users SET {$customSQL} faction_rank = :r WHERE id = :i");
            $stmt->bindValue(':r', $rank, PDO::PARAM_INT);
            $stmt->bindValue(':i', $playerID, PDO::PARAM_INT);
            $stmt->execute();

            echo Helpers::APIResponse("Success", ['faction_rank' => Config::$faction_ranks[$faction][$rank]], 200);
        } else {
            echo Helpers::APIResponse("Unauthorised", null, 401);
        }
    } else if ($url == "factionInitializePlayer") {
        $user = new User;

        $playerID = (isset($_POST['playerID'])) ? $_POST['playerID'] : false;
        $type = (isset($_POST['type'])) ? $_POST['type'] : false;


        if ($user->verified(false) && ($user->isCommand() || $user->isSLT())) {
            $player = new User($playerID);

            if ($type == "police") {
                $typeQuery = 'isPD = 1,';
            } else if ($type == "medic") {
                $typeQuery = 'isEMS = 1,';
            } else {
                echo Helpers::APIResponse("Invalid Player Type", null, 400);
                exit;
            }

            $stmt = $pdo->prepare("UPDATE users SET {$typeQuery} faction_rank = 1 WHERE id = :i");
            $stmt->bindValue(':i', $playerID, PDO::PARAM_INT);
            $stmt->execute();

            $updatedPlayer = new User($playerID);

            echo Helpers::APIResponse("Success", $updatedPlayer->getInfoForFrontend(), 200);
        } else {
            echo Helpers::APIResponse("Unauthorised", null, 401);
        }
    } else if ($url == "suspend") {
        $user = new User;

        $id = (isset($_POST{
            'id'})) ? $_POST['id'] : false;
        $remove = (isset($_POST{
            'remove'})) ? $_POST['remove'] : false;

        if (Permissions::init()->hasPermission("SEND_USER_ON_SUSPENSION")) {
            if (!$id) {
                echo Helpers::APIResponse("Invalid Request", null, 400);
                exit;
            }
            if ($remove) {
                $stmt = $pdo->prepare('UPDATE users SET suspended = 0 WHERE id = :id');
                $stmt->bindValue(':id', $id, PDO::PARAM_INT);
                if ($stmt->execute()) {
                    echo Helpers::APIResponse("Suspension Removed Successfully", null, 200);
                } else {
                    echo Helpers::APIResponse("Database Error", null, 500);
                }
            } else {
                $stmt = $pdo->prepare('UPDATE users SET suspended = 1 WHERE id = :id');
                $stmt->bindValue(':id', $id, PDO::PARAM_INT);
                if ($stmt->execute()) {
                    echo Helpers::APIResponse("Suspended", null, 200);
                } else {
                    echo Helpers::APIResponse("Database Error", null, 500);
                }
            }
        } else {
            echo Helpers::APIResponse("Unauthorised", null, 401);
        }
    } else if ($url == "newRole") {
        $user = new User;

        $name = (isset($_POST{
            'name'})) ? $_POST['name'] : false;

        if (Permissions::init()->hasPermission("CREATE_ROLE")) {
            if (!$name) {
                echo Helpers::APIResponse("Name Required", null, 400);
                exit;
            }

            $stmt = $pdo->prepare('INSERT INTO rank_groups (`name`, `permissions`, `position`) VALUES (:n, \'["VIEW_GENERAL"]\', 1000000)');
            $stmt->bindValue(':n', $name, PDO::PARAM_STR);
            if ($stmt->execute()) {
                $stmt = $pdo->prepare('
                    SET @ordering_inc = 10;SET @new_ordering = 0;
                    UPDATE rank_groups SET 
                    position = (@new_ordering := @new_ordering + @ordering_inc)
                    ORDER BY position ASC
                ');
                $stmt->execute();
                echo Helpers::APIResponse("Success", null, 200);
            } else {
                echo Helpers::APIResponse("Database Error " . json_encode($stmt->errorInfo()), null, 500);
            }
        } else {
            echo Helpers::APIResponse("Unauthorised", null, 401);
        }
    } else if ($url == "updateRolePermissions") {
        $user = new User;

        $roleID = (isset($_POST['roleID'])) ? $_POST['roleID'] : false;
        $perms = (isset($_POST['perms'])) ? $_POST['perms'] : false;

        if (Permissions::init()->hasPermission("EDIT_ROLE")) {
            if ($roleID === false || $perms === false) {
                echo Helpers::APIResponse("RoleID & One Permission Required", null, 400);
                exit;
            }

            if (in_array('*', $perms))
                $perms = ['*'];

            $stmt = $pdo->prepare('UPDATE rank_groups SET permissions = :p WHERE id = :i');
            $stmt->bindValue(':i', $roleID, PDO::PARAM_INT);
            $stmt->bindValue(':p', json_encode($perms), PDO::PARAM_STR);
            if ($stmt->execute()) {
                echo Helpers::APIResponse("Success", null, 200);
            } else {
                echo Helpers::APIResponse("Database Error " . json_encode($stmt->errorInfo()), null, 500);
            }
        } else {
            echo Helpers::APIResponse("Unauthorised", null, 401);
        }
    } else if ($url == "shuffleUpRole") {
        $user = new User;

        $id = (isset($_POST['id'])) ? $_POST['id'] : false;

        if (Permissions::init()->hasPermission("EDIT_ROLE")) {
            if ($id === false) {
                echo Helpers::APIResponse("RoleID Required", null, 400);
                exit;
            }

            $stmt = $pdo->prepare('UPDATE rank_groups SET position = (position) - 15 WHERE id = :id');
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $stmt = $pdo->prepare('
                SET @ordering_inc = 10;SET @new_ordering = 0;
                UPDATE rank_groups SET 
                position = (@new_ordering := @new_ordering + @ordering_inc)
                ORDER BY position ASC
            ');

            if ($stmt->execute()) {
                echo Helpers::APIResponse("Success", null, 200);
            } else {
                echo Helpers::APIResponse("Database Error " . json_encode($stmt->errorInfo()), null, 500);
            }
        } else {
            echo Helpers::APIResponse("Unauthorised", null, 401);
        }
    } else if ($url == "shuffleDownRole") {
        $user = new User;

        $id = (isset($_POST['id'])) ? $_POST['id'] : false;

        if (Permissions::init()->hasPermission("EDIT_ROLE")) {
            if ($id === false) {
                echo Helpers::APIResponse("RoleID Required", null, 400);
                exit;
            }

            $stmt = $pdo->prepare('UPDATE rank_groups SET position = (position) + 15 WHERE id = :id');
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $stmt = $pdo->prepare('
                SET @ordering_inc = 10;SET @new_ordering = 0;
                UPDATE rank_groups SET 
                position = (@new_ordering := @new_ordering + @ordering_inc)
                ORDER BY position ASC
            ');

            if ($stmt->execute()) {
                echo Helpers::APIResponse("Success", null, 200);
            } else {
                echo Helpers::APIResponse("Database Error " . json_encode($stmt->errorInfo()), null, 500);
            }
        } else {
            echo Helpers::APIResponse("Unauthorised", null, 401);
        }
    } else if ($url == "deleteRole") {
        $user = new User;

        $id = (isset($_POST['id'])) ? $_POST['id'] : false;

        if (Permissions::init()->hasPermission("REMOVE_ROLE")) {
            if ($id === false) {
                echo Helpers::APIResponse("RoleID Required", null, 400);
                exit;
            }

            foreach ($pdo->query('SELECT * FROM users') as $u) {
                foreach (json_decode($u->rank_groups) as $g) {
                    if ($g == $id) {
                        echo Helpers::APIResponse("There are users dependant on this role, it can't be deleted", null, 400);
                        exit;
                    }
                }
            }

            $stmt = $pdo->prepare('DELETE FROM `rank_groups` WHERE id = :id');
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $stmt = $pdo->prepare('
                SET @ordering_inc = 10;SET @new_ordering = 0;
                UPDATE rank_groups SET 
                position = (@new_ordering := @new_ordering + @ordering_inc)
                ORDER BY position ASC
            ');

            if ($stmt->execute()) {
                echo Helpers::APIResponse("Success", null, 200);
            } else {
                echo Helpers::APIResponse("Database Error " . json_encode($stmt->errorInfo()), null, 500);
            }
        } else {
            echo Helpers::APIResponse("Unauthorised", null, 401);
        }
    } else {
        echo Helpers::APIResponse("Route Not Found", null, 404);
    }
} else if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if ($url == "dailyCases") {
        $today = 0;
        $yesterday = 0;
        $twodays = 0;
        $threedays = 0;
        $fourdays = 0;
        foreach ($pdo->query('SELECT * FROM case_logs') as $r) {
            $timeinseconds = strtotime($r->timestamp) - time();
            if ($timeinseconds > -86400) {
                $today++;
            }
            if ($timeinseconds > -172800 && $timeinseconds < -86400) {
                $yesterday++;
            }
            if ($timeinseconds > -259200 && $timeinseconds < -172800) {
                $twodays++;
            }
            if ($timeinseconds > -345600 && $timeinseconds < -259200) {
                $threedays++;
            }
            if ($timeinseconds > -432000 && $timeinseconds < -345600) {
                $fourdays++;
            }
        }
        $arr = [];
        $arr['today'] .= $today;
        $arr['yesterday'] .= $yesterday;
        $arr['twodays'] .= $twodays;
        $arr['threedays'] .= $threedays;
        $arr['fourdays'] .= $fourdays;
        echo json_encode($arr);
    } else if ($url == "weeklyCases") {
        $thisweek = 0;
        $lastweek = 0;
        $twoweeks = 0;
        $threeweeks = 0;
        $onemonth = 0;
        foreach ($pdo->query('SELECT * FROM case_logs') as $r) {
            $timeinseconds = strtotime($r->timestamp) - time();
            if ($timeinseconds > -604800) {
                $thisweek++;
            }
            if ($timeinseconds > -1209600 && $timeinseconds < -604800) {
                $lastweek++;
            }
            if ($timeinseconds > -1814400 && $timeinseconds < -1209600) {
                $twoweeks++;
            }
            if ($timeinseconds > -2419200 && $timeinseconds < -1814400) {
                $threeweeks++;
            }
            if ($timeinseconds > -3024000 && $timeinseconds < -2419200) {
                $onemonth++;
            }
        }
        $arr = [];
        $arr['thisweek'] .= $thisweek;
        $arr['lastweek'] .= $lastweek;
        $arr['twoweeks'] .= $twoweeks;
        $arr['threeweeks'] .= $threeweeks;
        $arr['onemonth'] .= $onemonth;
        echo json_encode($arr);
    } else if ($url == "getUserInfo") {
        if (!isset($_COOKIE['LOGINTOKEN'])) {
            echo Helpers::APIResponse('Invalid API Token', null, 401);
            exit;
        }

        $cookietoken = sha1($_COOKIE['LOGINTOKEN']);
        //Get User ID From Login Tokens
        $sql = "SELECT * FROM login_tokens WHERE token = :token";
        $query = $pdo->prepare($sql);
        $query->bindValue(':token', $cookietoken, PDO::PARAM_STR);
        $query->execute();
        $result = $query->fetch();
        //Get Logged In User's Information
        $sql2 = "SELECT * FROM users WHERE id = :id";
        $query2 = $pdo->prepare($sql2);
        $query2->bindValue(':id', $result->user_id, PDO::PARAM_STR);
        $query2->execute();
        $user = $query2->fetch();
        if ($user) {
            //Assign Values To An Array.
            $arr = [];
            $arr['info']['id'] = $user->id;
            $arr['info']['username'] = $user->username;
            $arr['info']['firstname'] = $user->first_name;
            $arr['info']['lastname'] = $user->last_name;
            $arr['info']['email'] = $user->email;
            $arr['info']['suspended'] = $user->suspended;
            $arr['info']['slt'] = $user->SLT;
            $arr['info']['dev'] = $user->Developer;
            $arr['info']['team'] = $user->staff_team;
            setcookie('userArrayPHP', serialize($arr), time() + 60 * 60 * 24 * 30, '/');
        } else {
            $arr = ['error' => true];
        }
        echo json_encode($arr);
    } else if ($url == "getUserInfoNew") {
        $user = new User;

        if ($user->verified(false)) {
            echo Helpers::APIResponse("Success", $user->getInfoForFrontend(), 200);
        } else {
            echo Helpers::APIResponse("Unauthorized", null, 403);
        }
    } else if ($url == "getGuides") {
        $guides = [];
        $i = 1;
        foreach ($pdo->query('SELECT * FROM guides ORDER BY title') as $r) {
            $title = $r->title;
            $author = $r->author;
            $body = $r->body;
            $guides[$i]['id'] .= $r->id;
            $guides[$i]['title'] .= htmlspecialchars($title);
            $guides[$i]['author'] .= $author;
            $guides[$i]['body'] .= $body;
            $guides[$i]['time'] .= $r->timestamp;
            $guides[$i]['effective'] .= $r->effective;
            $i += 1;
        }
        echo json_encode($guides);
    } else if ($url == "getSuggestions") {
        $user = new User;

        if (!$user->error) {
            $arr = [];
            $i = 1;
            foreach ($pdo->query('SELECT * FROM suggestions ORDER BY id DESC') as $r) {
                $arr[$i]['id'] .= $r->id;
                $arr[$i]['name'] .= $r->name;
                $arr[$i]['suggestion'] .= htmlspecialchars($r->suggestion);
                $i++;
            }
            echo Helpers::APIResponse("Fetched Suggestions", $arr, 200);
        } else {
            echo Helpers::APIResponse("Failed To Fetch Suggestions: Unauthorised", null, 401);
        }
    } else if ($url == "getMeetings") {
        $arr = [];
        $i = 1;
        foreach ($pdo->query("SELECT * FROM meetings ORDER BY date DESC") as $meeting) {
            $stmt = $pdo->prepare('SELECT COUNT(*) as points FROM meeting_points WHERE id = :id');
            $stmt->bindValue(':id', $meeting->id, PDO::PARAM_STR);
            $stmt->execute();
            $points = $stmt->fetch()->points;
            $theDate = DateTime::createFromFormat('Y-m-d', $meeting->date);
            if (!$meeting->slt) {
                $arr[$i]['id'] = $meeting->id;
                $arr[$i]['date'] = $theDate->format('d/m/Y');
                $arr[$i]['wrongDate'] = $theDate->format('m/d/Y');
                $arr[$i]['points'] = $points;
                $i++;
            } else {
                if (unserialize($_COOKIE['userArrayPHP'])['info']['slt'] == 1) {
                    $arr[$i]['id'] = $meeting->id;
                    $arr[$i]['date'] = $theDate->format('d/m/Y');
                    $arr[$i]['wrongDate'] = $theDate->format('m/d/Y');
                    $arr[$i]['slt'] = true;
                    $arr[$i]['points'] = $points;
                    $i++;
                }
            }
        }
        echo json_encode($arr);
    } else if ($url == "getMeeting") {
        $arr = [];
        $stmt = $pdo->prepare("SELECT * FROM meetings WHERE id=:id");
        $stmt->bindValue(":id", $_GET['meetingID'], PDO::PARAM_STR);
        $stmt->execute();
        $meeting = $stmt->fetch();
        $pointCount = count(json_decode($meeting->points));
        $theDate = DateTime::createFromFormat('Y-m-d', $meeting->date);
        $arr['id'] = $meeting->id;
        $arr['date'] = $theDate->format('d/m/Y');
        $arr['wrongDate'] = $theDate->format('m/d/Y');
        $arr['pointCount'] = $pointCount;
        $arr['points'] = $meeting->points;
        $arr['slt'] = true;
        echo json_encode($arr);
    } else if ($url == "getMeetingPoints") {
        $arr = [];
        $stmt = $pdo->prepare("SELECT * FROM meeting_points WHERE meetingID=:id ORDER BY id DESC");
        $stmt->bindValue(":id", $_GET['meetingID'], PDO::PARAM_STR);
        $stmt->execute();
        $points = $stmt->fetchAll();
        $i = 0;
        foreach ($points as $point) {
            $arr[$i]['id'] = $point->id;
            $arr[$i]['name'] = $point->name;
            $arr[$i]['author'] = $point->author;
            $arr[$i]['votes'] = $point->votes;
            $arr[$i]['comments'] = $point->comments;
            $i++;
        }
        echo json_encode($arr);
    } else if ($url == "getMeetingNew") {
        $user = new User;
        if (Permissions::init()->hasPermission("VIEW_MEETING")) {
            $arr = [];
            $stmt = $pdo->prepare("SELECT * FROM meeting_points WHERE meetingID=:id ORDER BY id DESC");
            $stmt->bindValue(":id", $_GET['meetingID'], PDO::PARAM_STR);
            $stmt->execute();
            $points = $stmt->fetchAll();
            foreach ($points as $point) {
                $temp = [];
                $temp['id'] = $point->id;
                $temp['name'] = $point->name;
                $pointAuthor = new User(Helpers::UsernameToID($point->author));
                $temp['author'] = $pointAuthor->getInfoForFrontend()['displayName'];
                $temp['canDelete'] = ($pointAuthor->info->id == $user->info->id || $user->isSLT());
                $arr[] = $temp;
            }
            echo Helpers::APIResponse("Loaded Meeting", $arr, 200);
        } else {
            echo Helpers::APIResponse("Authentication Failed", null, 401);
        }
    } else if ($url == "getPointNew") {
        $user = new User;

        if (Permissions::init()->hasPermission("VIEW_MEETING")) {
            $stmt = $pdo->prepare("SELECT * FROM meeting_points WHERE id=:id");
            $stmt->bindValue(":id", $_GET['pointID'], PDO::PARAM_STR);
            if (!$stmt->execute()) {
                echo Helpers::APIResponse("Database Error", null, 500);
                exit;
            }
            $point = $stmt->fetch(PDO::FETCH_ASSOC);
            $pointAuthor = new User(Helpers::UsernameToID($point['author']));
            $point['author'] = $pointAuthor->getInfoForFrontend()['displayName'];
            if ($point) {
                $stmt = $pdo->prepare("SELECT * FROM meeting_comments WHERE pointID = :id ORDER BY id DESC");
                $stmt->bindValue(":id", $_GET['pointID'], PDO::PARAM_STR);
                if (!$stmt->execute()) {
                    echo Helpers::APIResponse("Database Error", null, 500);
                    exit;
                }
                $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($comments as $k => $c) {
                    $commentAuthor = new User(Helpers::UsernameToID($c['author']));
                    $comments[$k]['author'] = $commentAuthor->getInfoForFrontend();
                }
                $point['comments'] = $comments;
                echo Helpers::APIResponse("Fetched Point", $point, 200);
            } else {
                echo Helpers::APIResponse("No Point Found", null, 400);
            }
        } else {
            echo Helpers::APIResponse("Authentication Failed", null, 401);
        }
    } else if ($url == "interviewDetails") {
        $user = new User;

        if (Permissions::init()->hasPermission("VIEW_INTERVIEW")) {
            echo Helpers::APIResponse("Success", Interviews::fromID($_GET['id']), 200);
        } else {
            echo Helpers::APIResponse("Authentication Failed", null, 401);
        }
    } else if ($url == "teamStats") {
        $user = new User;

        $team = (isset($_GET['team'])) ? $_GET['team'] : false;

        if (Permissions::init()->hasPermission("VIEW_SLT")) {
            if ($team !== false) {
                $team_data = [];
                if ($team == 0) {
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE staff_team IS NULL ORDER BY username, id DESC");
                    $stmt->execute();
                } else {
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE staff_team = :team ORDER BY username, id DESC");
                    $stmt->bindValue(':team', $team, PDO::PARAM_INT);
                    $stmt->execute();
                }
                $team_data['staff'] = Helpers::sanitizeUserDataArray($stmt->fetchAll());
                foreach ($team_data['staff'] as $k => $s) {
                    $team_data['staff'][$k]->rank = Helpers::getRankNameFromPosition(Permissions::getHighestRank(json_decode($s->rank_groups, true)));
                }
                echo Helpers::APIResponse("Success", $team_data, 200);
            } else {
                echo Helpers::APIResponse("No ID Passed", null, 400);
            }
        } else {
            echo Helpers::APIResponse("Authentication Failed", null, 401);
        }
    } else if ($url == "player") {
        $user = new User;

        if (Permissions::init()->hasPermission("VIEW_CASE")) {
            $playerName = '';
            if (isset($_GET['name']) && !empty($_GET['name']))
                $playerName = $_GET['name'];
            if ($playerName !== '') {
                $stmt = $pdo->prepare('SELECT * FROM case_players WHERE name = :name');
                $stmt->bindValue(':name', $playerName, PDO::PARAM_STR);
                $stmt->execute();
                $items = $stmt->fetchAll();
                foreach ($items as $key => $item) {
                    $stmt = $pdo->prepare("SELECT * FROM case_logs WHERE id = :id");
                    $stmt->bindValue(':id', $item->case_id, PDO::PARAM_INT);
                    $stmt->execute();
                    $items[$key]->case = $stmt->fetch();
                    $items[$key]->case->players = Helpers::getPlayersFromCase($item->case_id);
                }
                echo Helpers::APIResponse("Success", $items, 200);
            } else {
                echo Helpers::APIResponse("Player Name Invalid", null, 400);
            }
        } else {
            echo Helpers::APIResponse("Authentication Failed", null, 401);
        }
    } else if ($url == "gamePlayers") {
        $user = new User;

        if (Permissions::init()->hasPermission("VIEW_GAME_PLAYER")) {
            $q = (isset($_GET['q'])) ? $_GET['q'] : '';
            $filters = (isset($_GET['filters'])) ? json_decode($_GET['filters']) : false;

            $sqlFilters = "AND (";
            $filterCount = 0;
            $filterConnections = [
                'onlyPolice' => '`coplevel` <> \'0\'',
                'onlyMedics' => '`mediclevel` <> \'0\'',
                'onlyAdmins' => '`adminlevel` <> \'0\''
            ];

            if ($filters) {
                foreach ($filters as $key => $filter) {
                    if ($filter) {
                        if ($filterCount > 0)
                            $sqlFilters .= "OR ";
                        $sqlFilters .= "{$filterConnections[$key]} ";
                        $filterCount++;
                    }
                }
            }

            if ($filterCount == 0) {
                $sqlFilters = "";
            } else {
                $sqlFilters .= ")";
            }

            $gamepdo = game_pdo();

            $stmt = $gamepdo->prepare("SELECT uid, pid, name FROM `players` WHERE (`uid` LIKE :q OR `pid` LIKE :q OR `name` LIKE :q OR `aliases` LIKE :q) {$sqlFilters} ORDER BY uid ASC LIMIT 100");
            $stmt->bindValue(':q', '%' . $q . '%', PDO::PARAM_INT);
            $stmt->execute();
            $players = $stmt->fetchAll(PDO::FETCH_OBJ);
            $stmt = $gamepdo->prepare("SELECT COUNT(*) as count FROM `players` WHERE (`uid` LIKE :q OR `pid` LIKE :q OR `name` LIKE :q OR `aliases` LIKE :q) {$sqlFilters}");
            $stmt->bindValue(':q', '%' . $q . '%', PDO::PARAM_INT);
            $stmt->execute();
            $playerTotalCount = $stmt->fetch(PDO::FETCH_OBJ);
            $playerTotalCount = intval($playerTotalCount->count);

            $playerCount = count($players);
            $refine = ($playerTotalCount > 100) ? ' Refine Your Search Terms.' : '';

            echo (Helpers::APIResponse("Displaying {$playerCount} Of {$playerTotalCount}{$refine}", $players, 200));
        } else {
            echo Helpers::APIResponse("Not High Enough Rank", null, 403);
        }
    } else if ($url == "gamePlayer") {
        $user = new User;

        if (Permissions::init()->hasPermission("VIEW_GAME_PLAYER")) {
            $uid = (isset($_GET['id'])) ? $_GET['id'] : null;

            $gamepdo = game_pdo();

            if ($uid == null) {
                echo Helpers::APIResponse("No ID Passed", null, 400);
                exit;
            }

            $stmt = $pdo->prepare("SELECT * FROM `audit_log` WHERE LOCATE(:id, log_content)>0 ORDER BY id DESC");
            $stmt->bindValue(':id', "Game_Player(" . $uid . ")", PDO::PARAM_INT);
            $stmt->execute();
            $auditLogs = $stmt->fetchAll();

            foreach ($auditLogs as $log) {
                $log->staff_member_name = ($log->logged_in_user != null) ? Helpers::IDToUsername($log->logged_in_user) : '';
            }

            $stmt = $gamepdo->prepare('SELECT * FROM `players` WHERE uid = :uid');
            $stmt->bindValue(':uid', $uid, PDO::PARAM_INT);
            $stmt->execute();
            $player = $stmt->fetch(PDO::FETCH_OBJ);

            $player->formatbankacc = "$" . number_format($player->bankacc);
            $player->cash = number_format($player->cash);
            $player->exp_total = number_format($player->exp_total);
            $player->edits = $auditLogs;

            echo Helpers::APIResponse("Success", $player, 200);
        } else {
            echo Helpers::APIResponse("Not High Enough Rank", null, 403);
        }
    } else if ($url == "gamePlayerVehicles") {
        $user = new User;

        if (Permissions::init()->hasPermission("VIEW_GAME_VEHICLES")) {
            $pid = (isset($_GET['id'])) ? $_GET['id'] : null;

            $gamepdo = game_pdo();

            if ($pid == null) {
                echo Helpers::APIResponse("No ID Passed", null, 400);
                exit;
            }

            $stmt = $gamepdo->prepare('SELECT * FROM `vehicles` WHERE pid = :pid');
            $stmt->bindValue(':pid', $pid, PDO::PARAM_STR);
            $stmt->execute();
            $playerVehicles = $stmt->fetchAll(PDO::FETCH_OBJ);

            echo Helpers::APIResponse("Success", ['vehicles' => $playerVehicles, 'vehiclesFilled' => count($playerVehicles)], 200);
        } else {
            echo Helpers::APIResponse("Not High Enough Rank", null, 403);
        }
    } else if ($url == "levelSettings") {
        $user = new User;

        if (Permissions::init()->hasPermission("VIEW_GAME_PLAYER")) {
            //            $levelSettings = file_get_contents('https://ws.infishit.de/gameLevels');
            $levelSettings = [
                "police" => [
                    0 => 'Not Whitelisted',
                    1 => 'Cadet',
                    2 => 'Officer',
                    3 => 'Senior Officer',
                    4 => 'Corporal',
                    5 => 'Sergeant',
                    6 => 'Lieutenant/Captain',
                    7 => 'State Command'
                ],
                "police_department" => [
                    0 => 'No Department',
                    1 => 'Department Of Corrections',
                    2 => 'Patrol',
                    3 => 'Highway Patrol',
                    4 => 'Internal Affairs',
                    5 => 'Corrections Response Team',
                    6 => 'Special Weapons And Tactics (SWAT)',
                    7 => 'Command'
                ],
                "admin" => [
                    0 => 'No Admin Rank',
                    1 => 'Senior Administrator+',
                    2 => 'Senior Leadership Team'
                ],
                "medic" => [
                    0 => 'Not Whitelisted',
                    1 => 'EMT',
                    2 => 'Advanced EMT',
                    3 => 'Volunteer / Paramedic',
                    4 => 'Advanced Paramedic',
                    5 => 'Field Commander',
                    6 => 'Captain',
                    7 => 'Assistant Chief',
                    8 => 'Deputy Chief',
                    9 => 'Chief Of EMS',
                ],
                "medic_department" => [
                    0 => 'None',
                    1 => 'EMS Department',
                    2 => 'Fire Department'
                ],
                "vehicle_dictionary" => json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/lib/carDictionary.json'))
            ];
            echo Helpers::APIResponse("Success", $levelSettings, 200);
        }
    } else if ($url == "reservedSlots") {
        $json = json_decode(file_get_contents('../lib/whitelist.json'));
        echo Helpers::APIResponse("Fetched", $json, 200);
    } else if ($url == "stringDiffHTML") {
        $str1 = (isset($_GET['string1'])) ? $_GET['string1'] : false;
        $str2 = (isset($_GET['string2'])) ? $_GET['string2'] : false;

        if ($str1 && $str2) {
            echo Helpers::APIResponse("Success", [Diff::compare($str1, $str2, false, true)], 200);
        } else {
            echo Helpers::APIResponse("Failed", null, 400);
        }
    } else if ($url == "staffAuditLogs") {
        $user = new User;

        $id = (isset($_GET['id'])) ? $_GET['id'] : null;

        if ($id !== null && (Permissions::init()->hasPermission("VIEW_USER_AUDIT") || $user->info->id == $id)) {
            $stmt = $pdo->prepare('SELECT * FROM audit_log WHERE logged_in_user = :id ORDER BY id DESC');
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $audit = $stmt->fetchAll();

            echo Helpers::APIResponse("Success", $audit, 200);
        } else {
            echo Helpers::APIResponse("Unauthorised", null, 401);
        }
    } else if ($url == "serverStats") {
        $user = new User;

        if (Permissions::init()->hasPermission("VIEW_GENERAL") && Config::$enableGamePanel) {
            $gamepdo = game_pdo();

            $stmt = $gamepdo->prepare('SELECT COUNT(*) AS total from `players`');
            $stmt->execute();
            $players = $stmt->fetch(PDO::FETCH_OBJ);
            $stmt = $gamepdo->prepare('SELECT COUNT(*) AS total from `players` WHERE coplevel <> "0"');
            $stmt->execute();
            $cops = $stmt->fetch(PDO::FETCH_OBJ);
            $stmt = $gamepdo->prepare('SELECT COUNT(*) AS total from `players` WHERE mediclevel <> "0"');
            $stmt->execute();
            $medics = $stmt->fetch(PDO::FETCH_OBJ);
            $stmt = $gamepdo->prepare('SELECT SUM(bankacc) AS total from `players`');
            $stmt->execute();
            $serverBalance = $stmt->fetch(PDO::FETCH_OBJ);
            $stmt = $gamepdo->prepare('SELECT `bankacc`, `aliases`, `name`, `uid`, `pid`, `last_seen` from `players` ORDER BY bankacc DESC LIMIT 10');
            $stmt->execute();
            $richList = $stmt->fetchAll(PDO::FETCH_OBJ);
            foreach ($richList as $user) {
                $user->bankacc = "$" . number_format($user->bankacc, 0);
            }

            echo Helpers::APIResponse("Success", ['serverBalance' => ['read' => $serverBalance->total, 'formatted' => '$' . number_format($serverBalance->total)], 'players' => ['total' => $players->total, 'total_cops' => $cops->total, 'total_medics' => $medics->total, 'rich_list' => $richList]], 200);
        } else {
            echo Helpers::APIResponse("Unauthorised", null, 401);
        }
    } else if ($url == "pages") {
        $user = new User;

        if ($user->verified()) {
            $stmt = $pdo->prepare('SELECT id, title, creator_id FROM pages WHERE creator_id = :id');
            $stmt->bindValue(':id', $user->info->id, PDO::PARAM_INT);
            $stmt->execute();
            $pages = $stmt->fetchAll();

            echo Helpers::APIResponse("Fetched", $pages, 200);
        } else {
            echo Helpers::APIResponse("Unauthorised", null, 401);
        }
    } else if ($url == "page") {
        $user = new User;

        $id = (isset($_GET['id'])) ? $_GET['id'] : false;

        if ($user->verified()) {
            $stmt = $pdo->prepare('SELECT * FROM pages WHERE id = :id');
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $page = $stmt->fetch();

            echo Helpers::APIResponse("Fetched", $page, 200);
        } else {
            echo Helpers::APIResponse("Unauthorised", null, 401);
        }
    } else if ($url == "page_formatted") {
        $user = new User;

        $id = (isset($_GET['id'])) ? $_GET['id'] : false;

        if ($user->verified()) {
            $stmt = $pdo->prepare('SELECT * FROM pages WHERE id = :id');
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $page = $stmt->fetch();

            $parsedown = new Parsedown();

            $content = $parsedown->text(nl2br($page->content));

            echo Helpers::APIResponse("Fetched", ['page' => $content, 'title' => $page->title], 200);
        } else {
            echo Helpers::APIResponse("Unauthorised", null, 401);
        }
    } else if ($url == "steamIDToUsername") {
        $sid = (isset($_GET['uid'])) ? $_GET['uid'] : false;
        $key = (isset($_GET['key'])) ? $_GET['key'] : false;

        $apiKeyInfo = Helpers::isValidAPIToken($key);

        if (!Helpers::ValidateAPITokenPerms($apiKeyInfo, 'V')) {
            echo Helpers::APIResponse("Unauthorised", null, 403);
            exit;
        }

        $gamepdo = game_pdo();

        if ($sid) {
            $stmt = $pdo->prepare("SELECT * FROM case_players WHERE guid = :id LIMIT 1");
            $stmt->bindValue(':id', $sid, PDO::PARAM_INT);
            $stmt->execute();
            $player = $stmt->fetch();
            if ($player) {
                echo Helpers::APIResponse("Success", ['player' => $player->name], 200);
                exit;
            }
            $stmt = $gamepdo->prepare("SELECT * FROM players WHERE pid = :id");
            $stmt->bindValue(':id', $sid, PDO::PARAM_INT);
            $stmt->execute();
            $player = $stmt->fetch(PDO::FETCH_OBJ);
            if ($player) {
                echo Helpers::APIResponse("Success", ['player' => $player->name], 200);
                exit;
            }
            echo Helpers::APIResponse("Unknown Player", ['player' => 'Unknown Player'], 200);
        }
    } else if ($url == "factionPlayerList") {
        $user = new User;

        if ($user->verified(false) && $user->isCommand()) {

            if ($user->isSLT()) {
                $stmt = $pdo->prepare('SELECT id, username, faction_rank, first_name, last_name, isEMS, isPD FROM users WHERE isPD = 1 OR isEMS = 1');

                $stmt->execute();

                $players = $stmt->fetchAll();

                foreach ($players as $p) {
                    if ($p->isPD) {
                        $p->rank = Config::$faction_ranks['police'][$p->faction_rank];
                    } else {
                        $p->rank = Config::$faction_ranks['medic'][$p->faction_rank];
                    }
                }

                echo Helpers::APIResponse("Loaded Players", $players, 200);
            } else if ($user->isPD()) {
                $stmt = $pdo->prepare('SELECT id, username, faction_rank, first_name, last_name FROM users WHERE isPD = 1');

                $stmt->execute();

                $players = $stmt->fetchAll();

                foreach ($players as $p) {
                    $p->rank = Config::$faction_ranks['police'][$p->faction_rank];
                }

                echo Helpers::APIResponse("Loaded Players", $players, 200);
            } else if ($user->isEMS()) {
                $stmt = $pdo->prepare('SELECT id, username, faction_rank, first_name, last_name FROM users WHERE isEMS = 1');

                $stmt->execute();

                $players = $stmt->fetchAll();

                foreach ($players as $p) {
                    $p->rank = Config::$faction_ranks['medic'][$p->faction_rank];
                }

                echo Helpers::APIResponse("Loaded Players", $players, 200);
            } else {
                echo Helpers::APIResponse("You Do Not Belong To A Faction", null, 403);
            }
        } else {
            echo Helpers::APIResponse("Unauthorised", null, 400);
        }
    } else if ($url == "factionPlayerFull") {
        $user = new User;

        $id = (isset($_GET['id'])) ? $_GET['id'] : false;

        if ($user->verified(false) && $user->isCommand()) {
            if ($id) {
                $usr = new User($id);
                $inf = $usr->getInfoForFrontend();
                echo Helpers::APIResponse("Loaded Player", $inf, 200);
            } else {
                echo Helpers::APIResponse("Invalid Request", null, 400);
            }
        } else {
            echo Helpers::APIResponse("Unauthorised", null, 401);
        }
    } else if ($url == "factionConfigs") {
        $user = new User;
        if ($user->verified(false)) {
            echo json_encode(Config::$faction_ranks);
        }
    } else if ($url == "notifications") {
        $user = new User;

        if ($user->verified(false)) {
            echo Helpers::APIResponse("Loaded Notifications", $user->fetchNotifications(), 200);
        } else {
            echo Helpers::APIResponse("Login To View Notifications", null, 401);
        }
    } else if ($url == "roles") {
        $user = new User;

        if (Permissions::init()->hasPermission("VIEW_ROLES")) {
            $stmt = $pdo->prepare('SELECT * FROM rank_groups ORDER BY `position` ASC');
            $stmt->execute();
            $roles = $stmt->fetchAll();

            foreach ($roles as $k => $r) {
                $roles[$k]->permissions = json_decode($r->permissions);
            }

            echo Helpers::APIResponse("Loaded Role Information", $roles, 200);
        } else {
            echo Helpers::APIResponse("Unauthorised", null, 401);
        }
    } else {
        echo Helpers::APIResponse("Route Not Found", null, 404);
    }
} else {
    echo Helpers::APIResponse("Method Not Allowed", null, 405);
}
