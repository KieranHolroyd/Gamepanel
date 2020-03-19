<?php

include_once $_SERVER['DOCUMENT_ROOT'] . '/classes/User.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/classes/Config.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/classes/Permissions.php';

class Guard extends User
{
    private static $instance;

    public static function init()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function StaffRequired()
    {
        if (!Permissions::init()->hasPermission("VIEW_GENERAL")) {
            echo '<h1 style="padding-left: 80px;padding-top: 20px;">Unauthorised! Redirecting...</h1><meta http-equiv="refresh" content="0;url=/errors/nostaff">';
            die();
        }
    }

    public function SLTRequired()
    {
        if (!Permissions::init()->hasPermission("VIEW_SLT")) {
            echo '<h1 style="padding-left: 80px;padding-top: 20px;">Unauthorised! Redirecting...</h1><meta http-equiv="refresh" content="0;url=/">';
            die();
        }
    }

    public function RequireGameAccess()
    {
        if (!Permissions::init()->hasPermission("VIEW_GAME_PLAYER")) {
            echo '<h1>Unauthorised! Redirecting...</h1><meta http-equiv="refresh" content="0;url=/">';
            die();
        }
        if (!Config::$enableGamePanel) {
            echo '<div style="padding-left: 70px;padding-top: 10px;">Game Panel Disabled, Contact Your Systems Administrator.</div>';
            die();
        }
    }

    public function LoginRequired()
    {
        if (!$this->verified(false) && ($this->isCommand() || $this->isPD() || $this->isEMS() || $this->isStaff())) {
            echo '<h1>Unauthorised! Redirecting...</h1><meta http-equiv="refresh" content="0;url=/">';
            die();
        }
    }

    public function DevRequired()
    {
        if (!Permissions::init()->hasPermission("SPECIAL_DEVELOPER")) {
            echo '<h1>Unauthorised! Redirecting...</h1><meta http-equiv="refresh" content="0;url=/">';
            die();
        }
    }

    public function CommandRequired()
    {
        if (!Permissions::init()->hasPermission("VIEW_COMMAND")) {
            echo '<h1>Unauthorised! Redirecting...</h1><meta http-equiv="refresh" content="0;url=/">';
            die();
        }
    }
}