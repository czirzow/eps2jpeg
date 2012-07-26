<?php

class Eps2Jpeg {
	const MAX_RATIO = 4.0;

	private static $pstill_cmd = '/usr/bin/pstill/pstill';
	private static $convert_cmd = '/usr/bin/convert';
	private static $identify_cmd = '/usr/bin/identify';

	private $file = null;
	private $width = null;
	private $height = null;

	private $jpg_size = null;
	private $ratio = array();

	private $cleanup_files = array();

	public function __construct($eps_file, $eps_width=null, $eps_height=null) {
		$this->file = $eps_file;
		$this->width = $eps_width;
		$this->height = $eps_height;
	}

	public function __destruct() {
		foreach($this->cleanup_files as $file) {
			if(file_exists($file) ) unlink($file);
		}
	}

	private function setSize() {

		$cmd = self::$identify_cmd . ' ' . escapeshellarg($this->file);
		exec("$cmd 2>&1", $output, $return_var);
		if($return_var) {
			return false;
		}
		// 'admin_1326964.eps EPT 823x648 823x648+0+0 DirectClass 2mb'
		if(! preg_match('/ (\d+)x(\d+) /', $output[0], $matches)) {
			return false;
		}

		$this->width = $matches[1];
		$this->height = $matches[2];

		return true;
		
	}

	public function init($jpg_size=null) {
		$this->jpg_size = $jpg_size;

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

	private function pstillCommand($input, $output) {
		$ratio = $this->ratio['pstill'];
		return self::$pstill_cmd . " -M pagescale=$ratio,$ratio  -M defaultall -s -p -m XPDFA=RGB -o $output " . escapeshellarg($input);
	}

	private function convertCommand($input, $output) {
		$convert_ratio = $this->ratio['convert'];
		$convert = self::$convert_cmd . "  $convert_ratio -antialias -quality 100 $input $output";
		$convert = self::$convert_cmd . "  -antialias -quality 100 pdf:$input jpg:$output";

		return $convert;
	}


	public function convert() {

		$pdf_out = tempnam('/tmp/', 'pdf');
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

		$jpg_out = tempnam('/tmp/', 'jpg');
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




