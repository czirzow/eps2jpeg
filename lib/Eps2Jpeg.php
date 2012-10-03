<?php

/* 
 * base/factory class for converter.
 */
class Eps2Jpeg {
	const MAX_RATIO = 4.0;

	public static $pstill_cmd = '/opt/pstill/pstill';
	public static $convert_cmd = '/usr/bin/convert';
	public static $identify_cmd = '/usr/bin/identify';
	public static $tmp_dir = '/tmp/';

	const BAD_REQUEST = -2;
	const UNKNOWN     = -1;
	const PARAM       =  1;
	const INIT        =  2;
	const CONVERT     =  3;

	/**
	 * keep only one instance of a response() call
	 * @var object
	 */
	private static $response = null;

	/**
	 * keep only one instance of a cleaner() call
	 * @var object
	 */
	private static $cleaner = null;

	/**
	 * Obtain a request object based on type of upload.
	 * 
	 * @param string $upload_type type of upload, file (multiform post), url
	 *
	 * return mixed and Eps2JpegRequest object on succes otherwise an Eps2Jpeg_Error->error()
	 */
	public static function request($upload_type) {
		switch($upload_type) {
			case 'file':
				return new Eps2JpegRequest_File();
			case 'url':
				return new Eps2JpegRequest_File();
			case 'post':
			default:
				echo 'asdf';
				return Eps2Jpeg::response()->error('Upload method not valid', Eps2Jpeg::BAD_REQUEST);
		}

	}

	/**
	 * Obtain the converter object, currently only one exists.. hard coded Eps2Jpeg.
	 */
	public static function converter($request) {
		return new Eps2JpegConverter($request);
	}


	/**
	 * Run a test to see if code is able to be ran at all.
	 *
	 * return array array indexes:
	 *                 'fail' - array of items that failed, 
	 *                 'success' - array of successful tests, 
	 *                 'warn' - an array of warnings.
	 *                 'message' - only shown of 100 percent success
	 */
	public static function test() {

		$out = array();

		/* this font directory works for conversions */
		if(! is_dir("/usr/share/X11/fonts/Type1/")) {
			$out['fail']['pstill'] = 'xorg-x11-fonts-Type1 not installed';
		} else {

			/* is pstill installed? */
			if(file_exists(Eps2Jpeg::$pstill_cmd)) {
				$out['success']['pstill'] = 'installed';
			} else {
				$out['fail']['pstill'] = 'not-installed';
			}
		}

		/* is imagick installed? */
		if(file_exists(Eps2Jpeg::$convert_cmd)) {
			$out['success']['convert'] = 'installed';
		} else {
			$out['fail']['convert'] = 'not-installed';
		}

		/* is imagick installed? */
		if(file_exists(Eps2Jpeg::$identify_cmd)) {
			$out['success']['identify'] = 'installed';
		} else {
			$out['fail']['identify'] = 'not-installed';
		}

		/* check directory permissions */
		if(is_dir(Eps2Jpeg::$tmp_dir) ) {
			if(is_writable(Eps2Jpeg::$tmp_dir)) {
				$out['success']['tmp_dir'] = 'Ok';
				$perms = fileperms(Eps2Jpeg::$tmp_dir);
				if(! ($perms & 0x0200)) { 
					/* sticky bit t should be set.. */
					$out['warn']['tmp_dir'] = "Should have bit t set";
				}
			} else {
				$out['fail']['tmp_dir'] = 'Not writable';
			}
		} else {
			$out['fail']['tmp_dir'] = 'Directory does not exists.';
		}
		if(! $out['fail'] || $out['warn']) {
			/* if this happens, its a go on being able to use this service */
			$out['message'] = 'Have a good time converting things!';
		}

		return $out;
	}

	/**
	 * Return a singleton Eps2Jpeg_Response object
	 */
	public static function response() {
		if(! self::$response) {
			self::$response = new Eps2Jpeg_Response();
		}

		return self::$response;
	}

	/**
	 * Return a the current cleaner
	 */
	public static function clean() {
		return Eps2Jpeg_Cleaner::getInstance();
	}

}


/**
 * Manager to clean up temp data
 */
class Eps2Jpeg_Cleaner {
	private static $instance = null;

	/*
	 * an array of files to clean up, assign any files that should be removed once the script is done running, see __destruct()
	 * @var array
	 */
	private $cleanup_files = array();

	/**
	  * singleton.
		*/
	private function __construct() {}

	/** 
	  * singleton logic
		*/
	public static function getInstance() {
		if(! self::$instance) {
			self::$instance = new Eps2Jpeg_Cleaner();
		}
		return self::$instance;
	}

	
	/**
	 * If and when this object is created, these things are done after script execution.
	 */
	public function __destruct() {
		foreach($this->cleanup_files as $file) {
			if(file_exists($file) ) {
				error_log("clean $file");
				unlink($file);
			}
		}
	}

	/**
	 * Add a file to be cleaned up
	 *
	 * @param string the filename to remove when this object is destroyed
	 */
	public function file($file) {
		$this->cleanup_files[] = $file;
	}

}


/**
 * The Response object for Eps2Jpeg, some work needs to be done to be consistent.
 */
class Eps2Jpeg_Response {

	/*
	 * array of responses that can be returned, see also Eps2Jpeg for constants
	 * @var array
	 */
	private static $response_codes = array(
		Eps2Jpeg::BAD_REQUEST => array('status' => 400, 'message' => 'Bad Upload'),
		Eps2Jpeg::UNKNOWN     => array('status' => 400, 'message' => 'Unknown error occured'),
		Eps2Jpeg::PARAM       => array('status' => 400, 'message' => 'Invalid Parameter'),
		Eps2Jpeg::INIT        => array('status' => 400, 'message' => 'Problem with initializing parameters'),
		Eps2Jpeg::CONVERT     => array('status' => 400, 'message' => 'Problem Converting Image'),
	);


	/*
	 * keep track of errors called
	 * @var array
	 */
	private static $errors = array();

	/*
	 * report an error in a the proper format
	 *
	 * @param string $message the message to report
	 * @param int $code  the type of error, see Eps2Jpeg constants
	 *
	 * @return array ensuring a consistent array in results.
	 */
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




/**
 * The Base Eps2Jpeg Request validator 
 */
class Eps2JpegRequest {

	/**
	 * current value for file to work on, child class must define these array values:
	 *   'tmp_name' - the name of the temporary upload
	 *   'name' - friendly name to save things as
	 * @var array
	 */
	public $file = array();

	/**
	 * the max demensions of a jpeg for the result
	 * @var int
	 */
	public $jpg_max_size = 1000;


	/*
	 * A property the child class must set
	 * @var string
	 */
	protected $type = null;


	/** 
	 * Validate input
	 *
	 * @return mixed true value on success, otherwise an array of errors.
	 */
	public function validate() {

		if(! $this->method) {
			return  Eps2Jpeg::response()->error("Not a valid upload method established", Eps2Jpeg::BAD_REQUEST);
		}

		if($_REQUEST['save_as'] ) {
			$this->eps_save_base = $_REQUEST['save_as'];
		} else {
			if(! $this->file['name']) {
				return  Eps2Jpeg::response()->error("save_as param is required.", Eps2Jpeg::PARAM);
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
				return Eps2Jpeg::response()->error("eps_width and eps_height must both be passe", Eps2Jpeg::PARAM);
			}
		}

		if($_REQUEST['jpg_max_size']) {
			$this->jpg_max_size = (int)$_REQUEST['jpg_max_size'];
			if($this->jpg_max_size <= 100) {
				return Eps2Jpeg::response()->error("jpg_max_size must be larger than 100", Eps2Jpeg::PARAM);
			}
		}

		return true;


	}


}



/**
 * The Eps2Jpeg File upload (multi-part) validator, uses Eps2JpegRequest
 */
class Eps2JpegRequest_File extends Eps2JpegRequest {

	/**
	 * A must... set this method as a 'file' type
	 */
	public function __construct() {
		$this->method = 'file';
	}

	/**
	 * Confirms that the file was really uploaded, then validates the rest of the data
	 * @return mixed an array of information regarding the problem or the result of parent::validate()
	 */
	public function validate() {
		$upload = $_FILES['source'];
		if(! is_uploaded_file($upload['tmp_name'])) {
			return Eps2Jpeg::response()->error("Not a valid upload", Eps2Jpeg::BAD_REQUEST);
		}

		$this->file['tmp_name'] = $upload['tmp_name'];
		$this->file['name'] = $upload['name'];

		return parent::validate();
	}

}

class Eps2JpegRequest_Url extends Eps2JpegRequest {

	/**
	 * A must... set this method as a 'url' type
	 */
	public function __construct() {
		$this->method = 'url';
	}

	/**
	 * Confirms that the url is valid, gets the data from the url, then validates the rest of the data
	 * @return mixed an array of information regarding the problem or the result of parent::validate()
	 */
	public function validate() {

		$url = $_REQUEST['source'];
		$url_parts = parse_url($url);
		switch($url_parts['scheme']) {
			case 'http':
			case 'https':
				break;
			default:
				return  Eps2Jpeg::response()->error("Url transport not supported", Eps2Jpeg::BAD_REQUEST);
				break;
		}
		
		$tmp_file = preg_replace('/[^A-Z0-9_.-]/i', '-', $url_parts['path']);

		$tmp_name = tempnam(Eps2Jpeg::$tmp_dir, 'url-' . $url_parts['host'] . $tmp_file);
		$tmp_filename = basename($url_parts['path']);

		Eps2Jpeg::clean()->file($tmp_name);

		$fp = fopen($url, 'r');
		$rc = file_put_contents($tmp_name, $fp);
		fclose($fp);

		$this->file['tmp_name'] = $tmp_name;
		$this->file['name'] = $tmp_filename;

		return parent::validate();

	}

}


/**
 * Convert to jpeg 
 */
class Eps2JpegConverter {


	/**
	 * keep track of ratios
	 * @var array
	 *      'pstill' - the ratio pstill should doe
	 *      'convert' - the ratio convert should use.
	 */
	private $ratio = array();

	/**
	 * the actual requested vars
	 *
	 * @var object Eps2JpegRequest object
	 */
	private $input = null;

	/**
	 * create a new converter object (eps2jpeg only)
	 */
	public function __construct(Eps2JpegRequest $input) {
		$this->input = $input;
	}

	/**
	 *  Get the size of the image to process
	 *    uses 
	 */
	private function obtainSize() {

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

	/**
	 * get the pstill command to use
	 *
	 * @param $input the file to use from
	 * @param $output the file to use to
	 *
	 * return @string the comman that should be ran.
	 */
	private function pstillCommand($input, $output) {
		$ratio = $this->ratio['pstill'];
		return Eps2Jpeg::$pstill_cmd . " -M pagescale=$ratio,$ratio  -M defaultall -s -p -m XPDFA=RGB -o $output " . escapeshellarg($input);
	}

	/**
	 * get the convert command to use
	 *
	 * @param $input the file to use from
	 * @param $output the file to use to
	 *
	 * return @string the comman that should be ran.
	 */
	private function convertCommand($input, $output) {
		$convert_ratio = $this->ratio['convert'];
		$convert = Eps2Jpeg::$convert_cmd . "  $convert_ratio -antialias -quality 100 pdf:$input jpg:$output";
		error_log($convert);

		return $convert;
	}

	/**
	 * Secondary layer of confirming that things are ok, find the  size of the image and ensure the script wont bring the box to its knees.
	 *
	 * @return boolean true if able to do things otherwise false, the value of ->error is set if false.
	 */
	public function init() {
		$jpg_size = $this->input->jpg_max_size;

		if(! ($this->width || $this->height) ) {

			if(! $this->obtainSize() ) {
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

	/**
	 * convert the darn file...
	 *
	 * @return mixed  the filename of the converted file otherwse false, with ->error set
	 */
	public function convert() {

		$pdf_out = tempnam(Eps2Jpeg::$tmp_dir, 'pdf');
		Eps2Jpeg::clean()->file($pdf_out);

		$pstill = $this->pstillCommand($this->input->file['tmp_name'], $pdf_out);
		
		exec("$pstill", $output, $return_var);
		if($return_var) {
			unlink($pdf_out);
			$this->error = "Could not prepare\n";
			return false;
		}
		error_log($pstill);

		$jpg_out = tempnam(Eps2Jpeg::$tmp_dir, 'jpg');
		Eps2Jpeg::clean()->file($jpg_out);

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




