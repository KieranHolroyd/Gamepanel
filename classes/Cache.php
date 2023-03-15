<?php

$cache = new Redis();
$cache->connect(Config::$cache['host'], Config::$cache['port'], 0, "cache_instance");

// Class which stores active connection, returns it if exists
// class Cache {
// 	public static function getRedis() {
// 		global $redis;
// 		try {
// 			if ($redis == null || !$redis->ping()) {
// 				$redis = ;
// 				$redis->connect(Config::$cache['host'], Config::$cache['port'], 0, "cache_instance");
// 			}
// 			return $redis;
// 		} catch (Exception $e) {
// 			print_r($e);
// 		}
// 	}

// 	public static function ping() {
// 		return self::getRedis()->ping();
// 	}

// 	public static function set($key, $value, $ttl = 0) {
// 		return self::getRedis()->set($key, $value, $ttl);
// 	}

// 	public static function get($key) {
// 		return self::getRedis()->get($key);
// 	}

// 	public static function delete($key) {
// 		return self::getRedis()->del($key);
// 	}
// }
