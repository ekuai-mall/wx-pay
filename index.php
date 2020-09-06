<?php
/**
 * Wechat-Pay
 *
 * 一个php微信支付插件（类）
 * @author kuai
 * @copyright ekuai 2020
 * @version 2.2
 */
include 'WxPay.php';

class WxPay extends WxPayBase {
	protected $mysql;
	public $error = '';
	
	private function isMobile() {
		// 如果有HTTP_X_WAP_PROFILE则一定是移动设备
		if (isset ($_SERVER['HTTP_X_WAP_PROFILE'])) {
			return true;
		}
		// 如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
		if (isset ($_SERVER['HTTP_VIA'])) {
			// 找不到为flase,否则为true
			return stristr($_SERVER['HTTP_VIA'], "wap") ? true : false;
		}
		// 脑残法，判断手机发送的客户端标志,兼容性有待提高
		if (isset ($_SERVER['HTTP_USER_AGENT'])) {
			$clientkeywords = array('nokia',
				'sony',
				'ericsson',
				'mot',
				'samsung',
				'htc',
				'sgh',
				'lg',
				'sharp',
				'sie-',
				'philips',
				'panasonic',
				'alcatel',
				'lenovo',
				'iphone',
				'ipod',
				'blackberry',
				'meizu',
				'android',
				'netfront',
				'symbian',
				'ucweb',
				'windowsce',
				'palm',
				'operamini',
				'operamobi',
				'openwave',
				'nexusone',
				'cldc',
				'midp',
				'wap',
				'mobile',
			);
			// 从HTTP_USER_AGENT中查找手机浏览器的关键字
			if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT']))) {
				return true;
			}
		}
		// 协议法，因为有可能不准确，放到最后判断
		if (isset ($_SERVER['HTTP_ACCEPT'])) {
			// 如果只支持wml并且不支持html那一定是移动设备
			// 如果支持wml和html但是wml在html之前则是移动设备
			if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html')))) {
				return true;
			}
		}
		return false;
	}
	
	public function __construct($dbName, $dbUser, $dbPwd) {
		try {
			$this->mysql = new PDO('mysql:dbname=' . $dbName . ';host=localhost;', $dbUser, $dbPwd, array
			(PDO::MYSQL_ATTR_INIT_COMMAND => "set names utf8"));
			$this->error = '';
		} catch (PDOException $e) {
			$this->error = $e->getMessage();
		}
	}
	
	protected function query($sql, $para, $fetch = PDO::FETCH_ASSOC) {
		if ($this->error !== '') {
			return false;
		} else {
			$a = $this->mysql->prepare($sql);
			if ($a->execute($para)) {
				return $a->fetchAll($fetch);
			} else {
				return false;
			}
		}
	}
	
	private function ret($status, $ret) {
		return ['status' => $status, 'ret' => $ret];
	}
	
	public function newOrder($order, $user, $productId, $price, $remark) {
		if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger')) {
			$tradeNo = $this->newTradeNo($user);
			$this->query("INSERT INTO `ekm_order` (`name`,`time_start`, `status`, `product`, `user`, `order`, `price`, `url`, `remark`) VALUES (?, ?, 'NOTPAY', ?, ?, ?, ?, ?, ?);", [$order, time(), $productId, $user, $tradeNo, $price, 'JSAPI', $remark]);
			$ret = $this->ret(0, $tradeNo);
		} else if ($this->isMobile()) {
			$res = $this->H5GetPayUrl($order, $user, $price);
			if ($res['return_code'] !== 'SUCCESS') {
				$ret = $this->ret(350001, $res['return_msg']);
			} else if ($res['result_code'] !== 'SUCCESS') {
				$ret = $this->ret(350002, $res['err_code']);
			} else {
				$this->query("INSERT INTO `ekm_order` (`name`,`time_start`, `status`, `product`, `user`, `order`, `price`, `url`, `remark`) VALUES (?, ?, 'NOTPAY', ?, ?, ?, ?, ?, ?);", [$order, time(), $productId, $user, $res['out_trade_no'], $price, $res['mweb_url'], $remark]);
				$ret = $this->ret(0, $res['out_trade_no']);
			}
		} else {
			$res = $this->NativeGetPayUrl($order, $user, $price, $productId);
			if ($res['return_code'] !== 'SUCCESS') {
				$ret = $this->ret(300001, $res['return_msg']);
			} else if ($res['result_code'] !== 'SUCCESS') {
				$ret = $this->ret(300002, $res['err_code']);
			} else {
				$this->query("INSERT INTO `ekm_order` (`name`,`time_start`, `status`, `product`, `user`, `order`, `price`, `url`, `remark`) VALUES (?, ?, 'NOTPAY', ?, ?, ?, ?, ?, ?);", [$order, time(), $productId, $user, $res['out_trade_no'], $price, $res['code_url'], $remark]);
				$ret = $this->ret(0, $res['out_trade_no']);
			}
		}
		return $ret;
	}
	
	public function checkOrder($order) {
		$res = $this->orderQuery($order);
		if ($res['return_code'] !== 'SUCCESS') {
			$ret = $this->ret(310001, $res['return_msg']);
		} else if ($res['result_code'] !== 'SUCCESS') {
			$ret = $this->ret(310002, $res['err_code']);
		} else if ($res['trade_state'] === 'SUCCESS') {
			$this->query("UPDATE `ekm_order` SET `time_finish` = ?,`status` = ? WHERE `order` = ?;", [time(),
				$res['trade_state'], $order]);
			$ret = $this->ret(0, $res['trade_state']);
		} else {
			$this->query("UPDATE `ekm_order` SET `status` = ? WHERE `order` = ?;", [$res['trade_state'], $order]);
			$ret = $this->ret(0, $res['trade_state']);
		}
		return $ret;
	}
	
	public function generateOrder($order, $user, $price, $trade_no, $openid) {
		return $this->JsGetPayParams($order, $user, $price, $trade_no, $openid);
	}
	
}