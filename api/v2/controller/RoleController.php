<?php

namespace App\API\V2\Controller;

use  \Helpers, \Permissions, \User;

class RoleController
{
	public function List()
	{
		global $pdo;

		if (Permissions::init()->hasPermission("VIEW_ROLES")) {
			$stmt = $pdo->prepare('SELECT * FROM rank_groups ORDER BY `position` ASC');
			$stmt->execute();
			$roles = $stmt->fetchAll();

			foreach ($roles as $k => $r) {
				$roles[$k]->permissions = json_decode($r->permissions);
			}

			echo Helpers::NewAPIResponse(["success" => true, "roles" => $roles]);
		} else {
			echo Helpers::NewAPIResponse(["success" => false, "message" => "Unauthorised"]);
		}
	}

	public function Update($roleID)
	{
		global $pdo;

		$user = new User;
		$sudo = Permissions::init()->hasSudo();

		$perms = (isset($_POST['perms'])) ? $_POST['perms'] : false;
		$name = (isset($_POST['name'])) ? $_POST['name'] : false;

		if (Permissions::init()->hasPermission("EDIT_ROLE")) {
			if ($roleID === false || $perms === false) {
				echo Helpers::NewAPIResponse(["success" => false, "message" => "RoleID Required"]);
				exit;
			}

			if (in_array('*', $perms)) {
				// User must have sudo to edit a role with sudo
				if (!$sudo) {
					echo Helpers::NewAPIResponse(["success" => false, "message" => "Sudo Required to edit role with sudo"]);
					exit;
				}
				$perms = ['*'];
			}

			// Get rank group
			$stmt = $pdo->prepare('SELECT * FROM rank_groups WHERE id = :id');
			$stmt->execute(['id' => $roleID]);
			$role = $stmt->fetch();
			if (!$sudo) {
				// Check if users rank is higher than the role they are trying to edit
				$highest = $user->highestRank();
				if ($highest->position >= $role->position) {
					echo Helpers::NewAPIResponse(["success" => false, "message" => "Cannot edit role with higher or equal rank"]);
					exit;
				}
			}


			$stmt = $pdo->prepare('UPDATE rank_groups SET permissions = :perms, name = :name WHERE id = :i');
			$stmt->bindValue(':i', $roleID);
			$stmt->bindValue(':perms', json_encode($perms));
			$stmt->bindValue(':name', $name);
			if ($stmt->execute()) {
				Helpers::addAuditLog("Updated Role {$name} ({$roleID})");
				echo Helpers::NewAPIResponse(["success" => true, "message" => "Updated Role {$name}"]);
			} else {
				echo Helpers::NewAPIResponse(["success" => false, "message" => "Database Error " . json_encode($stmt->errorInfo())]);
			}
		} else {
			echo Helpers::NewAPIResponse(["success" => false, "message" => "Unauthorised"]);
		}
	}

	public function Add()
	{
		global $pdo;

		$name = (isset($_POST['name'])) ? $_POST['name'] : false;

		if (Permissions::init()->hasPermission("ADD_ROLE")) {
			if (!$name) {
				echo Helpers::APIResponse("Name Required", null, 400);
				exit;
			}

			$stmt = $pdo->prepare('INSERT INTO rank_groups (`name`, `permissions`, `position`) VALUES (:n, \'["VIEW_GENERAL"]\', 1000000)');
			$stmt->bindValue(':n', $name);
			if ($stmt->execute()) {
				$stmt = $pdo->prepare('
                    SET @ordering_inc = 10;SET @new_ordering = 0;
                    UPDATE rank_groups SET 
                    position = (@new_ordering := @new_ordering + @ordering_inc)
                    ORDER BY position ASC
                ');
				if ($stmt->execute()) {
					$stmt->closeCursor(); // Close the cursor to prevent errors
					Helpers::addAuditLog("Added Role {$name}");
					echo Helpers::APIResponse("Success", null, 200);
				} else {
					echo Helpers::APIResponse("Database Error " . json_encode($stmt->errorInfo()), null, 500);
				}
			} else {
				echo Helpers::APIResponse("Database Error " . json_encode($stmt->errorInfo()), null, 500);
			}
		} else {
			echo Helpers::APIResponse("Unauthorised", null, 401);
		}
	}

	public function Shuffle($roleID)
	{
		global $pdo;

		$direction = (isset($_POST['direction'])) ? $_POST['direction'] : false;

		if (Permissions::init()->hasPermission("EDIT_ROLE")) {
			if ($roleID === false || $direction === false) {
				echo Helpers::APIResponse("RoleID Required", null, 400);
				exit;
			}

			$operator = ($direction == "UP") ? "-" : "+";

			// Move the role
			$stmt = $pdo->prepare("UPDATE rank_groups SET position = (position) {$operator} 15 WHERE id = :id");
			$stmt->bindValue(':id', $roleID);
			$stmt->execute();
			$stmt->closeCursor();

			// Reorder
			$stmt = $pdo->prepare('
                SET @ordering_inc = 10;SET @new_ordering = 0;
                UPDATE rank_groups SET 
                position = (@new_ordering := @new_ordering + @ordering_inc)
                ORDER BY position ASC
            ');

			if ($stmt->execute()) {
				$stmt->closeCursor(); // Close the cursor to prevent errors
				Helpers::addAuditLog("Shuffled Role {$roleID} {$direction}");
				echo Helpers::APIResponse("Success", null, 200);
			} else {
				echo Helpers::APIResponse("Database Error " . json_encode($stmt->errorInfo()), null, 500);
			}
		} else {
			echo Helpers::APIResponse("Unauthorised", null, 401);
		}
	}

	public function Delete($roleID)
	{
		global $pdo;

		$forcefully = (isset($_POST['forcefully'])) ? json_decode($_POST['forcefully']) : false;
		$dependant = false;

		if (Permissions::init()->hasPermission("REMOVE_ROLE")) {
			if (!$roleID) {
				echo Helpers::NewAPIResponse(["success" => false, "message" => "RoleID Required"]);
				exit;
			}
			if ($forcefully && !Permissions::init()->hasSudo()) {
				echo Helpers::NewAPIResponse(["success" => false, "message" => "Sudo Required"]);
				exit;
			}

			foreach ($pdo->query('SELECT * FROM users') as $u) {
				$pdo->beginTransaction();
				foreach (json_decode($u->rank_groups) as $group) {
					if ($group == $roleID) {
						$dependant = true;

						if ($forcefully) {
							// Remove the role from the user
							$groups = (array) json_decode($u->rank_groups);
							$groups = array_diff($groups, [$roleID]);
							$groups = json_encode($groups);

							// Update the user (Within the transaction)
							$stmt = $pdo->prepare('UPDATE users SET rank_groups = :groups WHERE id = :id');
							$stmt->bindValue(':groups', $groups);
							$stmt->bindValue(':id', $u->id);
							if (!$stmt->execute()) {
								// Rollback the transaction & exit on error
								$pdo->rollBack();
								echo Helpers::NewAPIResponse(["success" => false, "message" => "Database Error " . json_encode($stmt->errorInfo())]);
								exit;
							}
							$stmt->closeCursor();
						}
					}
				}
				$pdo->commit();
			}

			if ($dependant && !$forcefully) {
				Helpers::addAuditLog("Failed to delete role {$roleID} due to dependant users");
				echo Helpers::NewAPIResponse(["success" => false, "message" => "There are users dependant on this role", "action" => "Retry with force?"]);
				exit;
			}
			if ($dependant && $forcefully) {
				Helpers::addAuditLog("Deleting role {$roleID} forcefully");
			}

			$stmt = $pdo->prepare('DELETE FROM `rank_groups` WHERE id = :id');
			$stmt->bindValue(':id', $roleID);
			$stmt->execute();

			$stmt = $pdo->prepare('
			          SET @ordering_inc = 10;SET @new_ordering = 0;
			          UPDATE rank_groups SET 
			          position = (@new_ordering := @new_ordering + @ordering_inc)
			          ORDER BY position ASC
			      ');

			if ($stmt->execute()) {
				$stmt->closeCursor(); // Close the cursor to prevent errors
				Helpers::addAuditLog("Deleted Role {$roleID}");
				echo Helpers::NewAPIResponse(["success" => true, "message" => "Deleted Role {$roleID}"]);
			} else {
				echo Helpers::NewAPIResponse(["success" => false, "message" => "Database Error " . json_encode($stmt->errorInfo())]);
			}
		} else {
			echo Helpers::NewAPIResponse(["success" => false, "message" => "Unauthorised"]);
		}
	}
}
