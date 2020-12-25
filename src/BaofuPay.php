<?php

/**
 *
 */

namespace BaofuPay;

use BaofuPay\EncryptDecrypt\BFRSA;
use BaofuPay\Exceptions\BaofuPayException;
use BaofuPay\Tools\Tools;
use BaofuPay\Logger\MonologLogger as MLOG;

class BaofuPay {

	public static $config;
	public static $token;
	public static $certPath;

	public function __construct() {
	}

	public static function setConfig( $config ) {
		self::$config = $config;
	}

	public static function getConfig() {
		return self::$config;
	}

	public static function getConf() {
		$config = self::getConfig()['conf'];

		return $config;
	}

	public static function setCertPath( $path ) {
		self::$certPath = $path;

		return self::$certPath;
	}

	public static function getCertPrivate() {
		$file = self::$certPath . self::getConf()['pfxfilename'];

		return $file;
	}

	public static function getCertPublic() {
		$file = self::$certPath . self::getConf()['cerfilename'];

		return $file;
	}

	public static function pay( $data = [] ) {
		if ( empty( $data ) ) {
			$data = $_POST;
		}
		$Money   = isset( $data["Money"] ) ? $data["Money"] : "0";//接收金额
		$PayID   = isset( $data["PayID"] ) ? $data["PayID"] : "";//宝付收银台传空，直连银行传对应PAYID
		$TransID = isset( $data["trans_id"] ) ? $data["trans_id"] : "";//商户订单号

		$OrderMoney = (int) ( $Money * 100 );//订单金额(以分为单位)  注意：INT类型取值范围，可以使用更大数据类型的

		$data_Array                    = array();
		$data_Array["member_id"]       = self::getConf()["member_id"];//商户号
		$data_Array["terminal_id"]     = self::getConf()["terminal_id"];//终端号
		$data_Array["pay_id"]          = $PayID;//PayID传空跳转宝付收银台，传功能ID跳转对应的银行
		$data_Array["trade_date"]      = Tools::getTime();//下单日期
		$data_Array["trans_id"]        = $TransID?$TransID:"TIDBAOFU" . Tools::getTransid() . Tools::getRand4();//商户订单号（不能重复）
		$data_Array["order_money"]     = $OrderMoney;//订单金额
		$data_Array["product_name"]    = self::getConf()["product_name"];//商品名称
		$data_Array["amount"]          = "1";//商品数量
		$data_Array["user_name"]       = self::getConf()["user_name"];//用户名称
		$data_Array["notice_type"]     = "1";//通知类型 1：页面通知+异步通知；0：异步通知
		$data_Array["page_url"]        = self::getConf()["page_url"];//页面返回地址(商户自定义地址)
		$data_Array["return_url"]      = self::getConf()["return_url"];//交易异步通知地址(商户自定义地址)，订单结果以异步通知为准。
		$data_Array["additional_info"] = self::getConf()["additional_info"];

		if ( self::getConf()["version"] == "4.2" ) {
			$Encrypted_string = str_replace( "\\/", "/", json_encode( $data_Array ) );//转JSON
		} else {
			throw new BaofuPayException( "[version]=" . self::getConf()["version"] . "类型不存在!" );
		}
		MLOG::getLoggerInstance()->error( "序列化结果：" . $Encrypted_string );
		$BFRsa     = new BFRSA( self::getCertPrivate(), self::getCertPublic(), self::getConf()["private_key_password"] ); //实例化加密类。
		$Encrypted = $BFRsa->encryptedByPrivateKey( $Encrypted_string );    //先BASE64进行编码再RSA加密

		$FormString = "<body onload=\"pay.submit()\">" .
		              "正在提交请稍后。。。。。。。。" .
		              "<form method=\"post\" name=\"pay\" id=\"pay\" action=\"" . self::getConf()["request_url"] . "\">" .
		              "<input name=\"member_id\" type=\"hidden\" value=\"" . self::getConf()["member_id"] . "\"/>" .
		              "<input name=\"terminal_id\" type=\"hidden\" value=\"" . self::getConf()["terminal_id"] . "\"/>" .
		              "<input name=\"version\" type=\"hidden\" value= \"" . self::getConf()["version"] . "\"/>" .
		              "<input name=\"data_content\" type=\"hidden\" value= \"" . $Encrypted . "\"/>" .
		              "</form></body>";
		MLOG::getLoggerInstance()->info( "提交表单：" . $FormString );

		$res = [
			'formString' => $FormString,
			'formData'   => [
				"request_url"  => self::getConf()["request_url"],
				"member_id"    => self::getConf()["member_id"],
				"terminal_id"  => self::getConf()["terminal_id"],
				"version"      => self::getConf()["version"],
				"data_content" => $Encrypted,
			],
			"data" => $data_Array
		];

		return $res;
	}


	public static function asyncCallBack( $callback, $data = [] ) {
		if ( empty( $data ) ) {
			$data = $_REQUEST;
		}
		MLOG::getLoggerInstance()->info( "===================接收网银异步通知========================" );
		if ( ! isset( $data["data_content"] ) ) {
			throw new BaofuPayException( "No parameters are received [data_content]" );
		}
		$EndataContent = $data["data_content"];

		MLOG::getLoggerInstance()->info( "异步通知原文：" . $EndataContent );
		$BFRsa        = new BFRSA( self::getCertPrivate(), self::getCertPublic(), self::getConf()["private_key_password"] ); //实例化加密类。
		$ReturnDecode = $BFRsa->decryptByPublicKey( $EndataContent );//解密返回的报文
		MLOG::getLoggerInstance()->info( "异步通知解密原文：" . $ReturnDecode );

		$ArrayContent = [];
		if ( ! empty( $ReturnDecode ) ) {//解析
			if ( self::getConf()["version"] == "4.2" ) {
				$ArrayContent = json_decode( $ReturnDecode, true );
			} else {
				throw new BaofuPayException( "[version]=" . self::getConf()["version"] . "类型不存在!" );
			}
		}

		if ( ! array_key_exists( "result", $ArrayContent ) ) {
			throw new BaofuPayException( "[result]不存在!" );
		}

		if ( ! array_key_exists( "fact_money", $ArrayContent ) ) {
			throw new BaofuPayException( "[fact_money]不存在!" );
		}

		if ( $ArrayContent["result"] == 1 ) {
			MLOG::getLoggerInstance()->info( "状态：" . $ArrayContent["result"] . ", 成功金额：" . $ArrayContent["fact_money"] );

			//注意：
			//  1、需判断成功金额和订单金额是否一致。
			//  2、需要做订单重复性较验。

			return $callback( $ArrayContent );
		} else {
			throw new BaofuPayException( 'pay failed! ' . print_r( $ArrayContent, 1 ) );
		}
	}


}