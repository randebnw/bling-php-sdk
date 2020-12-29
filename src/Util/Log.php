<?php

namespace Bling\Util;

class Log {
	public static function error($object) {
		error_log('[' . date('H:i:s') . '] ' . print_r($object, true) . "\n", 3, DIR_LOGS . 'bling_error.' . date('Y.m.d') . '.log');
	}
	
	public static function debug($object) {
		error_log('[' . date('H:i:s') . '] ' . print_r($object, true) . "\n", 3, DIR_LOGS . 'bling_debug.' . date('Y.m.d') . '.log');
	}
}
?>