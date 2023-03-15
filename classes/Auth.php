<?php

include_once $_SERVER['DOCUMENT_ROOT'] . '/classes/User.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/classes/Config.php';

// TODO: use Guard.php

class Auth extends User {

    public function SLTRequired() {
        if (!$this->isSLT()) {
            echo '<h1>Unauthorised! Redirecting...</h1><meta http-equiv="refresh" content="0;url=/">';
            die();
        }
    }

    public function RequireGameAccess() {
        if (!Permissions::init()->hasPermission("VIEW_GAME")) {
            echo '<h1>Unauthorised! Redirecting...</h1><meta http-equiv="refresh" content="0;url=/">';
            die();
        }
        if (!Config::$enableGamePanel) {
            echo '<div style="padding-left: 70px;padding-top: 10px;">Game Panel Disabled, Contact Your Systems Administrator.</div>';
            die();
        }
    }
}
