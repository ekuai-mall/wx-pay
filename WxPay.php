<?php
require_once __DIR__ . "/lib/WxPay.Api.php";
require_once "WxPay.Config.php";

class WxPay {
	/**
	 * 生成直接支付url，支付url有效期为2小时,模式二
	 * @param WxPayUnifiedOrder $input
	 */
	protected function GetPayUrl($input) {
		if ($input->GetTrade_type() == "NATIVE") {
			try {
				$config = new WxPayConfig();
				return WxPayApi::unifiedOrder($config, $input);
			} catch (Exception $e) {
				return false;
			}
		}
		return false;
	}
	
	public function NativeGetPayUrl($body, $attach, $price, $productId) {
		$trade_no = "life" . $attach . date("YmdHis") . mt_rand(10000, 65535);
		$input = new WxPayUnifiedOrder();
		$input->SetBody($body);
		$input->SetAttach($attach);
		$input->SetOut_trade_no($trade_no);
		$input->SetTotal_fee($price);
		$input->SetTime_start(date("YmdHis"));
		$input->SetTime_expire(date("YmdHis", time() + 600));
		$input->SetNotify_url("http://paysdk.weixin.qq.com/notify.php");
		$input->SetTrade_type("NATIVE");
		$input->SetProduct_id($productId);
		$ret = $this->GetPayUrl($input);
		$ret['out_trade_no'] = $trade_no;
		return $ret;
	}
	
	public function orderQuery($out_trade_no) {
		try {
			$input = new WxPayOrderQuery();
			$input->SetOut_trade_no($out_trade_no);
			$config = new WxPayConfig();
			return WxPayApi::orderQuery($config, $input);
		} catch (Exception $e) {
			return false;
		}
	}
}