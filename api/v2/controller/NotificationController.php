<?php
namespace App\API\V2\Controller;

use \User, \Helpers, \Permissions, \PDO;

class NotificationController
{

	//--- Main Notifications ---//

	public function getNotifications()
	{
		//global $PDO;
		$user = new User;

        if ($user->verified(false)) {
            echo Helpers::APIResponse("Loaded Notifications", $user->fetchNotifications(), 200);
        } else {
            echo Helpers::APIResponse("Login To View Notifications", null, 401);
        }
	}

	public function setNotifications()
	{  
		$user = new User;

		if ($user->verified(false)) {
			//$user->setNotifications(); //Create method in User class (So we can have a method to set notifications from web panel)
			echo Helpers::APIResponse("Notifications Set", null, 200);
		} else {
			echo Helpers::APIResponse("Login To Set Notifications", null, 401);
		}
	}

	//--- Essential Notifications ---//

	public function markEssentialRead()
	{
		global $pdo;

		$user = new User;

        if (Permissions::init()->hasPermission("VIEW_GENERAL")) {
            $stmt = $pdo->prepare('UPDATE users SET readEssentialNotification = 1 WHERE id = :id');
            $stmt->bindValue(':id', $user->info->id, PDO::PARAM_INT);
            $stmt->execute();

            echo Helpers::APIResponse('Marked As Read', null, 200);
        } else {
            echo Helpers::APIResponse('Unauthorised', null, 401);
        }
	}
}