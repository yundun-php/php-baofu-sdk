<?php

namespace BaofuPay\EncryptDecrypt;


use BaofuPay\Logger\MonologLogger as MLOG;

class BFRSA {

	private $private_key;

	private $public_key;


	/**
	 * BFRSA constructor.
	 *
	 * @param $private_key_path //商户证书路径（pfx）
	 * @param $public_key_path //宝付公钥证书路径（cer）
	 * @param $private_key_password //证书密码
	 */
	public function __construct( $private_key_path, $public_key_path, $private_key_password ) {
		// 初始化商户私钥
		$pkcs12      = file_get_contents( $private_key_path );
		$private_key = array();
		openssl_pkcs12_read( $pkcs12, $private_key, $private_key_password );
		MLOG::getLoggerInstance()->error( empty( $private_key ) == true ? "读取私钥是否可用:不可用" : "读取私钥是否可用:可用" );
		$this->private_key = $private_key["pkey"];

		//宝付公钥

		$keyFile          = file_get_contents( $public_key_path );
		$this->public_key = openssl_get_publickey( $keyFile );
		MLOG::getLoggerInstance()->error( empty( $this->public_key ) == true ? "读取宝付公钥是否可用:不可用" : "读取宝付公钥是否可用:可用" );
	}

	// 私钥加密
	function encryptedByPrivateKey( $data_content ) {
		$data_content = base64_encode( $data_content );
		$encrypted    = "";
		$totalLen     = strlen( $data_content );
		$encryptPos   = 0;
		while ( $encryptPos < $totalLen ) {
			openssl_private_encrypt( substr( $data_content, $encryptPos, 117 ), $encryptData, $this->private_key );
			$encrypted  .= bin2hex( $encryptData );
			$encryptPos += 117;
		}

		return $encrypted;
	}

	// 公钥解密
	function decryptByPublicKey( $encrypted ) {
		$decrypt    = "";
		$totalLen   = strlen( $encrypted );
		$decryptPos = 0;
		while ( $decryptPos < $totalLen ) {
			openssl_public_decrypt( hex2bin( substr( $encrypted, $decryptPos, 256 ) ), $decryptData, $this->public_key );
			$decrypt    .= $decryptData;
			$decryptPos += 256;
		}
		$decrypt = base64_decode( $decrypt );

		return $decrypt;
	}
}
