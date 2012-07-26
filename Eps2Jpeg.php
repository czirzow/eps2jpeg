<?php

class EpsToJpeg {
	private static $pstill_cmd = '/usr/bin/pstill/pstill';
	private static $convert_cmd = '/usr/bin/convert';
	private static $identify_cmd = '/usr/bin/identify';

	private $file = null;
	private $width = null;
	private $height = null;

	public function __construct($eps_file, $eps_width=null, $eps_height=null) {
		$this->file = $eps_file;
		$this->width = $eps_width;
		$this->height = $eps_height;
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

	public function convert($jpg_size=null) {

		if(! ($this->width || $this->height) ) {
			//echo 'getting size..';

			if(! $this->setSize() ) {
				$this->error = 'Could not find size of eps';
				return false;
			}
		}
		
		if(! $jpg_size) {
			$ratio = '1.0';
		} else {

			if($this->width > $this->height) {
				$ratio = number_format($jpg_size/$this->width, 4);
				$convert_ratio = '-resize ' . $jpg_size;
			} else {
				$ratio = number_format($jpg_size/$this->height, 4);
				$convert_ratio = '-resize x' . $jpg_size;
			}
		}

		$pdf_out = tempnam('/tmp/', 'eps2pdf');
		$pstill =  self::$pstill_cmd . " -M pagescale=$ratio,$ratio  -M defaultall -s -p -m XPDFA=RGB -o $pdf_out " . escapeshellarg($this->file);
		
		exec("$pstill", $output, $return_var);
		if($return_var) {
			unlink($pdf_out);
			$this->error = "Could not prepare\n";
			return false;
		}
		error_log($pstill);
		//echo "$pstill\n"; var_dump($output);

		$jpg_out = '/tmp/test_out.jpg';
		$convert = self::$convert_cmd . "  $convert_ratio -antialias -quality 100   $pdf_out $jpg_out";
		$convert = self::$convert_cmd . "  -antialias -quality 100   $pdf_out $jpg_out";
		$output = array();
		exec("$convert", $output, $return_var);
		error_log($convert);
		if($return_var) {
			error_log(print_r($outout, 1));
			$this->error = "Could not convert\n";
			return false;
		}
		//echo "$convert\n"; var_dump($output);

		unlink($pdf_out);
			
		return $jpg_out;

	}

}




