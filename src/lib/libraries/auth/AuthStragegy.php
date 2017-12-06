<?php if (!defined('BASEPATH')) {exit('No direct script access allowed');
}

/*
 * Created by xsh on Feb 24, 2014
 *
 */

define('LOGIN_SUCCESS', 'success');
define('LOGIN_ERROR', 'error');
define('LOGIN_NOT_ALLOW', 'not_allow');
define('LOGIN_NOT_ALLOW_MESSAGE', '登陆限制，请输入验证码');
define('LOGIN_CAPTCHA_ERROR', 'captcha_error');
define('LOGIN_CAPTCHA_ERROR_MESSAGE', '验证码错误');
define('LOGIN_FAILED', 'failed');
define('LOGIN_FAILED_MESSAGE', '用户名/邮箱或者密码错误！');

require_once ('Result.php');

class AuthStragegy {
	private $login_interval;
	private $login_max_error_count;
	private $ci;

	public function __construct() {
		$this->AuthStragegy();
	}

	private function AuthStragegy() {
		$this->ci = &get_instance();

		$this->login_interval = 60 * 5;//5分钟
		$this->login_max_error_count = 3;//5分钟内连续错误3次，即出现验证码。
	}

	private function _loginStorage() {
		if (empty($this->ci->loginstorage)) {
			$this->ci->load->library('auth/LoginStorage');
		}
		return $this->ci->loginstorage;
	}

	private function _authService() {
		if (empty($this->ci->authservice)) {
			$this->ci->load->library('auth/AuthService');
		}
		return $this->ci->authservice;
	}

	private function _captcha() {
		if ($this->ci->captchaservice == NULL) {
			$this->ci->load->library('auth/CaptchaService');
		}
		return $this->ci->captchaservice;
	}

	public function login($client_identity, $user_name, $plain_password, $auth_code) {
		$result = $this->_login($client_identity, $user_name, $plain_password, $auth_code);

		return $result;
	}

	private function _login($client_identity, $user_name, $plain_password, $auth_code) {
		if (empty($client_identity) || empty($user_name) || empty($plain_password)) {
			return Result::Error(false/* refresh captcha*/, LOGIN_ERROR);
		}

		$logs = $this->_loginStorage()->get($client_identity);

		if (!$this->allow_login($logs, $client_identity, $user_name, $plain_password)) {
			if (empty($auth_code)) {
				return Result::Error(true/* refresh captcha*/, LOGIN_NOT_ALLOW, LOGIN_NOT_ALLOW_MESSAGE);
			}

			if (!$this->_captcha()->check($auth_code)) {
				return Result::error(false/* refresh captcha*/, LOGIN_CAPTCHA_ERROR, LOGIN_CAPTCHA_ERROR_MESSAGE);
			}
		}

		$login_result = $this->_doLogin($user_name, $plain_password);

		if (!$login_result->success) {
			$log = array('login_time' => time(), 'login_name' => $user_name);
			$this->_loginStorage()->set($client_identity, $logs, $log, $this->login_max_error_count, $this->login_interval);
			$this->_captcha()->clear();
			$login_result->data = true/* refresh captcha*/;
		}

		return $login_result;
	}

	function _doLogin($loginName, $plainPassword) {
		$result = null;
		if (is_email($loginName)) {
			$result = $this->_authService()->loginByEmail($loginName, $plainPassword);
		} else {
			$result = $this->_authService()->login($loginName, $plainPassword);
		}

		if ($result->success) {
			return Result::Success($result->data, LOGIN_SUCCESS);
		}

		return Result::Error(false/* refresh captcha*/, LOGIN_FAILED, LOGIN_FAILED_MESSAGE);
	}

	private function allow_login($logs, $client_identity, $user_name, $plain_password) {
		return $this->total_error_count($logs) < $this->login_max_error_count;
	}

	private function total_error_count($logs) {
		if (empty($logs)) {
			return 0;
		}

		$now = time();

		$count = 0;
		foreach ($logs as $l) {
			if (($now - $l['login_time']) < $this->login_interval) {
				$count++;
			}
		}

		return $count;
	}
}

?>
