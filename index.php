<?php
/**
 * Wechat-Pay
 *
 * 一个php微信支付插件（类）
 * @author kuai
 * @copyright ekuai 2020
 * @version 1.2
 */
include 'WxPay.php';

class WxPay extends WxPayBase {
	protected $mysql;
	public $error = '';
	
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
		$res = $this->NativeGetPayUrl($order, $user, $price, $productId);
		if ($res['return_code'] !== 'SUCCESS') {
			$ret = $this->ret(300001, $res['return_msg']);
		} else if ($res['result_code'] !== 'SUCCESS') {
			$ret = $this->ret(300002, $res['err_code']);
		} else {
			$this->query("INSERT INTO `ekm_order` (`name`,`time_start`, `status`, `product`, `user`, `order`, `price`, `url`, `remark`) VALUES (?, ?, 'NOTPAY', ?, ?, ?, ?, ?, ?);", [$order, time(), $productId, $user, $res['out_trade_no'], $price, $res['code_url'], $remark]);
			$ret = $this->ret(0, $res['out_trade_no']);
		}
		return $ret;
	}
	
	public function checkOrder($order) {
		$res = $this->checkOrder($order);
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
	
}