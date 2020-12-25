<?php


namespace Tests;


use BaofuPay\Config\Config;
use BaofuPay\BaofuPay;

class BaofuPayTest extends TestCase {


	public function testLoadConfig() {
		$config = Config::load( __DIR__ . "/../baofu-pay.conf" );

		$this->assertTrue( is_array( $config ) );
	}

	public static function getConfig() {
		$config = Config::load( __DIR__ . "/../baofu-pay.conf" );

		return $config;
	}


	public function testPay() {
		BaofuPay::setConfig( self::getConfig() );
		BaofuPay::setCertPath( dirname( __DIR__ ) . '/CER/' );
		$data = [
			'Money' => 0.01,
			'PayID' => 3001,
//			'trans_id' => 'aaabbbccc'
		];
		$res  = BaofuPay::pay( $data );
		$html = "<!DOCTYPE html>
<html lang=\"en\">
<head>
    <meta charset=\"UTF-8\">
    <title>Title</title>
</head>
{$res['formString']}
</html>";
		$file = __DIR__ . '/pay.html';
		file_put_contents( $file, $html );
		shell_exec( "open tests/pay.html" );
	}


}