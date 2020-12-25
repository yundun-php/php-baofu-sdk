<?php

/**
 * @node_name Config
 * Desc:
 * Created by PhpStorm.
 * User: jasong
 */

namespace BaofuPay\Config;


use BaofuPay\Exceptions\ConfigException;

class Config {
	private static $config = [];

	public static function load( $config_file ) {
		if ( ! self::$config ) {
			if ( ! is_file( $config_file ) ) {
				throw new ConfigException( 'Invalid config file path' );
			}
			self::$config = parse_ini_file( $config_file, true );
			if ( ! is_array( self::$config ) || empty( self::$config ) ) {
				throw new ConfigException( 'Invalid configuration format' );
			}
		}

		return self::$config;
	}

}