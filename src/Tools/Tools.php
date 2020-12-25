<?php

namespace BaofuPay\Tools;

use BaofuPay\Exceptions\ToolsException;

class Tools {

	/**
	 * 生成时间戳
	 *
	 */
	public static function getTransid() {
		return strtotime( date( 'Y-m-d H:i:s', time() ) );
	}

	/**
	 * 生成四位随机数
	 *
	 */
	public static function getRand4() {
		return rand( 1000, 9999 );
	}

	/**
	 *
	 *  获取当前时间
	 */
	public static function getTime() {
		return date( 'YmdHis', time() );
	}


	/**
	 * 明文请求参数
	 *
	 * @param $txn_sub_type
	 * @param $Encrypted
	 * @param $data
	 *
	 * @return array
	 * @throws ToolsException
	 */
	public static function getPostParam( $txn_sub_type, $Encrypted, $data ) {

		if ( $txn_sub_type == null || $txn_sub_type == "" ) {
			throw  new ToolsException( "方法：getPostParam，参数：txn_sub_type  异常为空！" );
		}
		if ( $Encrypted == null || $Encrypted == "" ) {
			throw  new ToolsException( "方法：getPostParam，参数：Encrypted  异常为空！" );
		}

		$PostArr                 = [];
		$PostArr["version"]      = $data["version"];
		$PostArr["member_id"]    = $data["member_id"];
		$PostArr["terminal_id"]  = $data["terminal_id"];
		$PostArr["txn_type"]     = $data["txn_type"];
		$PostArr["txn_sub_type"] = $txn_sub_type;
		$PostArr["data_type"]    = $data["data_type"];
		$PostArr["data_content"] = $Encrypted;

		return $PostArr;
	}

}
