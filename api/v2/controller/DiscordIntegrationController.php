<?php

namespace App\API\V2\Controller;

use Helpers;
use \Unirest;

class DiscordIntegrationController {
	public function SearchForMemberByTag($tag) {
		$response = Unirest\Request::get("https://discord.com/api/v10/guilds/964059009453785099/members/search", [
			"Authorization" => "Bot " . $_ENV["DISCORD_BOT_TOKEN"],
		], [
			"query" => $tag,
			"limit" => 1,
		]);

		return json_encode($response->body);
	}

	public function FindID() {
		global $cache;
		$tag = $_GET['tag'] ?? null;
		if (empty($tag)) {
			echo Helpers::NewAPIResponse(["success" => false, "message" => "No tag provided"]);
			return;
		}
		$tag = str_replace(":", "#", $tag);
		$cached = $cache->get("discord_user_$tag");
		if ($cached) {
			echo Helpers::NewAPIResponse(["success" => true, "user" => json_decode($cached), "message" => "Searched for user with tag $tag"]);
			return;
		}

		$controller = new \App\API\V2\Controller\DiscordIntegrationController();
		$response = json_decode($controller->SearchForMemberByTag($tag))[0]->user;
		$cache->set("discord_user_$tag", json_encode($response), 86400);


		echo Helpers::NewAPIResponse(["success" => true, "user" => $response, "message" => "Searched for user with tag " . $tag]);
	}
}
