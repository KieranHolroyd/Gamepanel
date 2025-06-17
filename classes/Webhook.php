<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';

use Ablaze\PhpDiscordWebhook\Webhook;
use Ablaze\PhpDiscordWebhook\Embed;

class WebhookManager {
	private $webhook = null;
	private $embed = null;
	private $message = null;

	public function discord() {
		$this->webhook = new Webhook(Config::$name, Config::$base_url . "img/favicon.ico");
		return $this;
	}
	public function message(string $message) {
		$this->message = $message;
		return $this;
	}
	public function embed(string $title, string $content, string $user) {
		$this->embed = new Embed($title, $content);
		$this->embed->setColor("#FFFFFF");
		$this->embed->setAuthor($user, Config::$base_url, Config::$base_url . Config::$discord['icon_url']);
		$this->embed->setFooter(Config::$name, Config::$base_url . Config::$discord['icon_url']);
		return $this;
	}

	public function send() {
		if ($this->message != null)
			$this->webhook->setContent($this->message);
		if ($this->embed != null)
			$this->webhook->addEmbed($this->embed);

		if ($this->webhook == null || ($this->message == null && $this->embed == null))
			return false;

		return $this->webhook->send(Config::$discord_webhook);
	}
}
