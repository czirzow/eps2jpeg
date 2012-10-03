<?php

class Eps2Jpeg {
	const MAX_RATIO = 4.0;

	public static $pstill_cmd = '/opt/pstill/pstill';
	public static $convert_cmd = '/usr/bin/convert';
	public static $identify_cmd = '/usr/bin/identify';
	public static $tmp_dir = '/tmp/';

	public static function request($type) {
		switch($type) {
			case 'file':
				return new Eps2JpegRequest_File();
			case 'url':
				return new Eps2JpegRequest_File();

			case 'post':
			default:
				return Eps2JpegResponse::error('Upload method not valid', Eps2JpegResponse::BAD);
		}

	}

	public static function converter($request) {
		return new Eps2JpegConverter($request);
	}

	public static function test() {

		$out = array();

		if(! is_dir("/usr/share/X11/fonts/Type1/")) {
			$out['fail']['pstill'] = 'xorg-x11-fonts-Type1 not installed';
		} else {

			if(file_exists(Eps2Jpeg::$pstill_cmd)) {
				$out['success']['pstill'] = 'installed';
			} else {
				$out['fail']['pstill'] = 'not-installed';
			}
		}

		if(file_exists(Eps2Jpeg::$convert_cmd)) {
			$out['success']['convert'] = 'installed';
		} else {
			$out['fail']['convert'] = 'not-installed';
		}

		if(file_exists(Eps2Jpeg::$identify_cmd)) {
			$out['success']['identify'] = 'installed';
		} else {
			$out['fail']['identify'] = 'not-installed';
		}

		if(is_dir(Eps2Jpeg::$tmp_dir) ) {
			if(is_writable(Eps2Jpeg::$tmp_dir)) {
				$out['success']['tmp_dir'] = 'Ok';
				$perms = fileperms(Eps2Jpeg::$tmp_dir);
				if(! ($perms & 0x0200)) { 
					$out['warn']['tmp_dir'] = "Should have bit t set";
				}
			} else {
				$out['fail']['tmp_dir'] = 'Not writable';
			}
		} else {
			$out['fail']['tmp_dir'] = 'Directory does not exists.';
		}
		if(! $out['fail'] || $out['warn']) {
			$out['message'] = 'Have a good time converting things!';
		}

		return $out;
	}

	public function response() {}

}


class Eps2JpegResponse {
	const BAD       = -2;
	const UNKNOWN   = -1;
	const PARAM     =  1;
	const INIT      =  2;
	const CONVERT   =  3;

	private static $response_codes = array(
		-2 => array('status' => 400, 'message' => 'Bad Upload'),
		-1 => array('status' => 400, 'message' => 'Unknown error occured'),
		 1 => array('status' => 400, 'message' => 'Invalid Parameter'),
		 2 => array('status' => 400, 'message' => 'Problem with initializing parameters'),
		 3 => array('status' => 400, 'message' => 'Problem Converting Image'),
	);

	private static $errors = array();

	public static function error($message, $code=-1) {

		if(! $response = self::$response_codes[$code]) {
			$response = self::$response_codes[-1];
		}

		$output = array(
			'status' => $response['status'],
			'code' => $code,
			'error' => $response['message'],
			'message' => $message,
		);

		return $output;
	}
}


class Eps2JpegRequest {

	private $type = null;

	public $file = array();
	public $jpg_size = 1000;
	private $cleanup_files = array();

	public function __construct() {
	}

	public function __destruct() {
		foreach($this->cleanup_files as $file) {
			if(file_exists($file) ) {
				unlink($file);
			}
		}
	}

	public function validate() {

		if(! $this->method) {
			return  Eps2JpegResponse::error("Not a valid upload established", Eps2JpegResponse::BAD);
		}

		if($_REQUEST['save_as'] ) {
			$this->eps_save_base = $_REQUEST['save_as'];
		} else {
			if(! $this->file['name']) {
				return  Eps2JpegResponse::error("save_as param is required.", Eps2JpegResponse::PARAM);
			}

			//FIXME: not fully working.
			if($_REQUEST['auto_name']) {
				$this->eps_save_base = $this->file['name'];
			}
		}

		if($_REQUEST['eps_width'] || $_REQUEST['eps_height']) {
			$this->eps_width = (int)$_REQUEST['eps_width'];
			$this->eps_height = (int)$_REQUEST['eps_height'];
			if(! ($this->eps_width && $this->eps_height) ) {
				return Eps2JpegResponse::error("eps_width and eps_height must both be passe", Eps2JpegResponse::PARAM);
			}
		}

		if($_REQUEST['jpg_max_size']) {
			$this->jpg_size = (int)$_REQUEST['jpg_max_size'];
			if($this->jpg_size <= 100) {
				return Eps2JpegResponse::error("jpg_size must be larger than 100", Eps2JpegResponse::PARAM);
			}
		}

		return true;


	}


}

class Eps2JpegRequest_File extends Eps2JpegRequest {

	public function __construct() {
		$this->method = 'file';
	}

	public function validate() {
		$upload = $_FILES['source'];
		if(! is_uploaded_file($upload['tmp_name'])) {
			return Eps2JpegResponse::error("Not a valid upload", Eps2JpegResponse::BAD);
		}

		$this->file['tmp_name'] = $upload['tmp_name'];
		$this->file['name'] = $upload['name'];

		return parent::validate();
	}

}

class Eps2JpegRequest_Url extends Eps2JpegRequest {

	public function __construct() {
		$this->method = 'url';
	}

	public function validate() {

		$url = $_REQUEST['source'];
		$url_parts = parse_url($url);
		switch($url_parts['scheme']) {
			case 'http':
			case 'https':
				break;
			default:
				return  Eps2JpegResponse::error("Url transport not supported", Eps2JpegResponse::BAD);
				break;
		}
		
		$tmp_file = preg_replace('/[^A-Z0-9_.-]/i', '-', $url_parts['path']);

		$tmp_name = tempnam(Eps2Jpeg::$tmp_dir, 'url-' . $url_parts['host'] . $tmp_file);
		$tmp_filename = basename($url_parts['path']);

		$this->cleanup_files[] = $tmp_name;

		$fp = fopen($url, 'r');
		$rc = file_put_contents($tmp_name, $fp);
		fclose($fp);

		$this->file['tmp_name'] = $tmp_name;
		$this->file['name'] = $tmp_filename;

		return parent::validate();

	}

}


class Eps2JpegConverter {

	private $cleanup_files = array();

	private $ratio = array();
	private $jpg_size = null;
	private $input = null;



	public function __construct(Eps2JpegRequest $input) {
		$this->input = $input;
	}

	public function __destruct() {
		foreach($this->cleanup_files as $file) {
			if(file_exists($file) ) unlink($file);
		}
	}

	private function setSize() {

		$cmd = Eps2Jpeg::$identify_cmd . ' ' . escapeshellarg($this->input->file['tmp_name']);
		error_log($cmd);
		exec("$cmd 2>&1", $output, $return_var);
		if($return_var) {
			error_log('return var not 0');
			error_log(print_r($output, 1));
			return false;
		}

		// looking (EPT|PS) 823x648 823x648+0+0 DirectClass 2mb'
		//error_log("check size for" . print_r($output, 1));
		if(! preg_match('/ (\d+)x(\d+) /', $output[0], $matches)) {
			error_log("no size match for" . print_r($output, 1));
			return false;
		}

		$this->width = $matches[1];
		$this->height = $matches[2];
		error_log("size found {$this->width}x{$this->height}");

		return true;
		
	}

	private function pstillCommand($input, $output) {
		$ratio = $this->ratio['pstill'];
		return Eps2Jpeg::$pstill_cmd . " -M pagescale=$ratio,$ratio  -M defaultall -s -p -m XPDFA=RGB -o $output " . escapeshellarg($input);
	}

	private function convertCommand($input, $output) {
		$convert_ratio = $this->ratio['convert'];
		$convert = Eps2Jpeg::$convert_cmd . "  $convert_ratio -antialias -quality 100 pdf:$input jpg:$output";
		error_log($convert);

		return $convert;
	}

	public function init() {
		$jpg_size = $this->input->jpg_size;

		if(! ($this->width || $this->height) ) {

			if(! $this->setSize() ) {
				$this->error = 'Could not find size of eps';
				return false;
			}
		}

		if(! $jpg_size) {
			error_log('no jpeg size');
			$this->ratio['pstill'] = '1.0';
			$this->ratio['convert'] = '';
		} else {

			if($this->width > $this->height) {
				$this->ratio['pstill'] = number_format($jpg_size/$this->width, 4);
				$this->ratio['convert'] = '-resize ' . $jpg_size;
				error_log("width > height");
			} else {
				$this->ratio['pstill'] = number_format($jpg_size/$this->height, 4);
				$this->ratio['convert'] = '-resize x' . $jpg_size;
				error_log("width < height");
			}
		}

		if(abs($this->ratio['pstill']) > Eps2Jpeg::MAX_RATIO) {
			$this->error = "Convertion ratio of {$this->ratio['pstill']} is too large; current supported ratio is " . Eps2Jpeg::MAX_RATIO;
			return false;
		}

		return true;

	}

	public function convert() {

		$pdf_out = tempnam(Eps2Jpeg::$tmp_dir, 'pdf');
		$this->cleanup_files[] = $pdf_out;

		$pstill = $this->pstillCommand($this->input->file['tmp_name'], $pdf_out);
		
		exec("$pstill", $output, $return_var);
		if($return_var) {
			unlink($pdf_out);
			$this->error = "Could not prepare\n";
			return false;
		}
		error_log($pstill);

		$jpg_out = tempnam(Eps2Jpeg::$tmp_dir, 'jpg');
		$this->cleanup_files[] = $jpg_out;
		$convert = $this->convertCommand($pdf_out, $jpg_out);
		error_log($convert);

		$output = array();
		exec("$convert", $output, $return_var);
		if($return_var) {
			error_log(print_r($outout, 1));
			$this->error = "Could not convert\n";
			return false;
		}
			
		return $jpg_out;

	}

}




