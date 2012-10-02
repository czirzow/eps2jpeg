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


## Eps2Jpeg Usage

### Start up things:
    rock build
    rock start


### Testing install:
This will test things to ensure everything is setup properly to run things:

    http://hostname:8000/test-install/

### Testing upload form:

A form to test uploads with:

    http://hostname:8000/form/

### Converting an eps

Converting an uploaded file:

    http://hostname:8000/convert/file/

Converting an raw post of content [not implemented]:

    http://hostname:8000/convert/post/

Converting a url [not implemented]:

    http://hostname:8000/convert/url/


### Input params for convert [cleanup]
* *source* - depending on action: file: a multi-part file uploaded, post: a post var containg content,  url: the url to use
* *save_as* - filename returned in the headers, defaults to the basename of the original input. (required for convert/post)
* *max_jpeg_size* - the max size width or height of jpeg desired, aspect ratio will be kept

[optional]
* *target_format* - [default: jpeg] only jpeg supported.
* *eps_width* - [default: calculated] a hint to the system about the width of the eps
* *eps_height* - [default: calculated] a hint to the system about the height of the eps

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

