<?php
	

class Eps2Jpeg {
	const MAX_RATIO = 4.0;

	public static $pstill_cmd = '/opt/pstill/pstill';
	public static $convert_cmd = '/usr/bin/convert';
	public static $identify_cmd = '/usr/bin/identify';
	public static $tmp_dir = '/tmp/';

	public static function request($type) {
		return new Eps2JpegRequest($type);
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
	private $type = 'file';

	private $file = array();
	private $cleanup_files = array();

	public function __construct($type) {
		$this->type = $type;
	}

	public function __destruct() {
		foreach($this->cleanup_files as $file) {
			if(file_exists($file) ) {
				unlink($file);
			}
		}
	}

	private function validateUploadFile() {
		$upload = $_FILES['source'];
		if(! is_uploaded_file($upload['tmp_name'])) {
			return Eps2JpegResponse::error("Not a valid upload", Eps2JpegResponse::BAD);
		}

		$this->file['tmp_name'] = $upload['tmp_name'];
		$this->file['name'] = $upload['name'];

		return true;
	}

	public function validate() {

		switch($this->type) {
			case 'file':
				$rc = $this->validateUploadFile();
				break;
			case 'url':
			case 'post':
				//todo: add support.
			default:
				return  Eps2JpegResponse::error("Upload Type Not supported", Eps2JpegResponse::BAD);
				break;
		}

		if($rc !== true) {
			return $rc;
		}

		if($_REQUEST['save_as'] ) {
			$this->eps_save_base = $_REQUEST['save_as'];
		} else {
			if(! $this->file['name']) {
				return  Eps2JpegResponse::error("save_as param is required.", Eps2JpegResponse::PARAM);
			}
			if($_REQUEST['auto_name']) {
				$this->eps_save_base = $this->file['name'];
			}
		}

		if($_REQUEST['eps_width'] || $_REQUEST['eps_height']) {
			$this->eps_width = (int)$_REQUEST['eps_width'];
			$this->eps_height = (int)$_REQUEST['eps_height'];
			if(! ($this->eps_width && $this->eps_height) ) {
				return Eps2JpegResponse::error("eps_width and eps_height must both be passe", Respond::PARAM);
			}
		}

		if($_REQUEST['jpg_max_size']) {
			$this->jpg_size = (int)$_REQUEST['jpg_max_size'];
			if($this->jpg_size <= 100) {
				return Eps2JpegResponse::error("jpg_size must be larger than 100", Respond::PARAM);
			}
		}

		return true;


	}

}

class Eps2JpegConverter {

	private $cleanup_files = array();

	private $ratio = array();
	private $jpg_size = null;
	private $input = null;



	public function construct(Eps2JpegRequest $input) {
		$this->input = $input;
	}

	public function __destruct() {
		foreach($this->cleanup_files as $file) {
			if(file_exists($file) ) unlink($file);
		}
	}


	private function setSize() {

		$cmd = Eps2Jpeg::$identify_cmd . ' ' . escapeshellarg($this->file);
		error_log($cmd);
		exec("$cmd 2>&1", $output, $return_var);
		if($return_var) {
			error_log('return var not 0');
			error_log(print_r($output, 1));
			return false;
		}
		// 'admin_1326964.eps EPT 823x648 823x648+0+0 DirectClass 2mb'
		error_log("check size for" . print_r($output, 1));
		if(! preg_match('/ (\d+)x(\d+) /', $output[0], $matches)) {
			error_log("no size match for" . print_r($output, 1));
			return false;
		}

		$this->width = $matches[1];
		$this->height = $matches[2];

		return true;
		
	}

	private function pstillCommand($input, $output) {
		$ratio = $this->ratio['pstill'];
		return Eps2Jpeg::$pstill_cmd . " -M pagescale=$ratio,$ratio  -M defaultall -s -p -m XPDFA=RGB -o $output " . escapeshellarg($input);
	}

	private function convertCommand($input, $output) {
		$convert_ratio = $this->ratio['convert'];
		$convert = Eps2Jpeg::$convert_cmd . "  $convert_ratio -antialias -quality 100 $input $output";
		$convert = Eps2Jpeg::$convert_cmd . "  -antialias -quality 100 pdf:$input jpg:$output";

		return $convert;
	}

	public function init() {
		$this->jpg_size = $this->input->$jpg_size;

		if(! ($this->width || $this->height) ) {
			//echo 'getting size..';

			if(! $this->setSize() ) {
				$this->error = 'Could not find size of eps';
				return false;
			}
		}

		if(! $jpg_size) {
			$this->ratio['pstill'] = '1.0';
			$this->ratio['convert'] = '';
		} else {

			if($this->width > $this->height) {
				$this->ratio['pstill'] = number_format($jpg_size/$this->width, 4);
				$this->ratio['convert'] = '-resize ' . $jpg_size;
			} else {
				$this->ratio['pstill'] = number_format($jpg_size/$this->height, 4);
				$this->ratio['convert'] = '-resize x' . $jpg_size;
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

		$pstill = $this->pstillCommand($this->file, $pdf_out);
		
		exec("$pstill", $output, $return_var);
		if($return_var) {
			unlink($pdf_out);
			$this->error = "Could not prepare\n";
			return false;
		}
		error_log($pstill);
		//echo "$pstill\n"; var_dump($output);

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




