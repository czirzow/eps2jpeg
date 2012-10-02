## Eps2Jpeg

Convert Eps files to jpeg with php

## Required: 
 * rockplatform/php54 (php53 without rockplatform)
 * ImageMagick (yum install ImageMagick)
 * Type1 fonts (yum install xorg-x11-fonts-Type1)
 * pstill 
   * http://www.wizards.de/~frank/pstill.html
   * http://www.wizards.de/~frank/pstill17814_linux_ia32.tar.gz
	 * cd ~/tmp/
	 * wget http://www.wizards.de/~frank/pstill17814_linux_ia32.tar.gz
	 * tar xzf pstill17814_linux_ia32.tar.gz 
	 * cd pstill_dist
	 * patch < [path_to_Eps2Jpeg]/patches/linkAllFonts.sh.patch
	 * Follow INSTALL file for pstill (to through step 3)
	 * sudo cp -R . /opt/pstill


Original source from: https://github.com/czirzow/eps2jpeg


### Input params for convert
* *eps_file* - the multi-part file that is uploaded
* *auto_name* - auto respond with the jpeg name
* *eps_width* - a hint to the system about the width of the eps
* *eps_height* - a hint to the system about the height of the eps
* *jpeg_size* - the max size of the jpeg desired, aspect ratio will be kept

**note:** *eps_width* and $eps_height are helpful so the convert script does not need to figure out the size.


### Response for convert.php

#### HTTP status codes:

* 200 - successfull jpg
* 400 - an error

When the http status code is a 200 (success), a jpeg will follow in the contents. If the form value *auto_name* was specificed the header with the following headers:

    Content-Disposition: attachment; filenme="{$form_upload_basename}.jpg";


if the http status code is a 400 json will be returned, with some information in this format:
     HTTP/1.1 400 [error_code] error_reason
     {
        status: 400
        code: error_code
        error: error_reason
        message: reason for this error
     }

#### Error Codes

     -2 - simply a bad upload (should not happen)
     -1 - uknown error, it is the default error
      1  - parameter passed issue
      2  - there was a problem initializing the paramters
      3  - there was a problem converting the file.


#### Example Response:

     HTTP/1.1 400 [1] Invalid Parameter
     {
        status: 400,
        code: 1,
        error: "Invalid Parameter",
        message: "eps_width and eps_height must both be passed"
     }


## License

Copyright (C) 2012 by Shutterstock Images, LLC

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

