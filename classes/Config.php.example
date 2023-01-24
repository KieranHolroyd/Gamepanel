<?php
/**
 * Created by PhpStorm.
 * User: kiera
 * Date: 28/11/2018
 * Time: 02:50
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';

class Config
{
    // Personalisation/Root Options
    public static $name = 'Arma-Life';
    public static $base_url = "http://localhost:4400/";
    public static $enableGamePanel = true;

    // SQL Settings
    // Panel SQL Storage
    public static $sql = [
        'host' => 'panel_database',
        'port' => '3306',
        'user' => 'gamepanel',
        'pass' => 'gamepanel',
        'name' => 'gamepanel'
    ];


    // Game SQL Settings
    public static $gameSql = [
        'host' => 'game_database',
        'port' => '3307',
        'user' => 'gamedb',
        'pass' => 'gamedb',
        'name' => 'gamedb'
    ];

    // Staff Teams Settings
    public static $teams = [
        1 => '1',
        2 => '2',
        3 => '3',
        4 => '4',
        5 => '5',
        null => 'Unassigned Members',
        6 => 'Support Team',
        100 => 'SMT',
        500 => 'Development Team',
    ];

    // Pusher Config
    public static $pusher = [
        'AUTH_KEY' => '',
        'SECRET' => '',
        'APP_ID' => '',
        'DEFAULT_CONFIG' => [
            'cluster' => 'eu',
            'useTLS' => true
        ]
    ];

    public static $battleMetrics = [
        'apiKey' => ''
    ];

    public static $faction_ranks = [
        'police' => [
            0 => 'Unranked',
            1 => 'Officer',
            2 => 'Senior Officer',
            3 => 'Corporal',
            4 => 'Sergeant',
            5 => 'Lieutenant',
            6 => 'Captain',
            7 => 'Major',
            8 => 'Assistant Chief',
            9 => 'Chief Of Police'
        ],
        'medic' => [
            0 => 'Unranked',
            1 => 'Probationary Paramedic',
            2 => 'Advanced EMT',
            3 => 'Paramedic',
            4 => 'EMT',
            5 => 'Advanced Paramedic',
            6 => 'Lieutenant',
            7 => 'Captain',
            8 => 'Medical Advisor',
            9 => 'Deputy Chief Of EMS',
            10 => 'Chief Of EMS'
        ]
    ];

    // Required! (DO NOT EDIT BELOW THIS LINE)

    // Permissions System
    public static $permissions = [
        "*",
        "VIEW_GENERAL",
        "VIEW_CASE",
        "VIEW_SLT",
        "VIEW_GAME",
        "VIEW_COMMAND",
        "SPECIAL_DEVELOPER",
        "SUBMIT_CASE",
        "REMOVE_USER",
        "GUIDE_EDIT",
        "GUIDE_ADD",
        "VIEW_SEARCH",
        "VIEW_USER_INFO",
        "VIEW_USER_AUDIT",
        "VIEW_USER_ACTIVITY",
        "EDIT_USER_TEAM",
        "EDIT_USER_RANK",
        "EDIT_USER_NOTES",
        "EDIT_USER_UUID",
        "EDIT_USER_NAME",
        "EDIT_USER_PROMOTION",
        "EDIT_USER_REGION",
        "SEND_USER_ON_LOA",
        "SEND_USER_ON_SUSPENSION",
        "VIEW_MEETING",
        "ADD_MEETING",
        "ADD_MEETING_COMMENT",
        "ADD_MEETING_POINT",
        "REMOVE_MEETING_POINT",
        "ADD_PUNISHMENT",
        "ADD_BAN",
        "ADD_BAN_PERMANENT",
        "VIEW_INTERVIEW",
        "ADD_INTERVIEW",
        "EDIT_INTERVIEW",
        "VIEW_GAME_PLAYER",
        "VIEW_GAME_VEHICLES",
        "EDIT_PLAYER_ADMIN",
        "EDIT_PLAYER_POLICE",
        "EDIT_PLAYER_MEDIC",
        "EDIT_PLAYER_BALANCE",
        "VIEW_PLAYER_VEHICLES",
        "NEW_NOTEBOOK_PAGE",
        "EDIT_NOTEBOOK_PAGE",
        "ADD_ROLE",
        "EDIT_ROLE",
        "REMOVE_ROLE"
    ];

    public static $permissions_dictionary = [
        "ADD_PUNISHMENT" => "Allows the staff member to issue a punishment of points.",
        "ADD_BAN" => "Allows the staff member to issue a ban through battlemetrics.",
        "ADD_BAN_PERMANENT" => "Allows the staff member to issue permanent ban.",
    ];
}