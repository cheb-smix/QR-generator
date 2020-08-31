<h1>PHP QR Generator</h1>

Example of usage:
```php
<?php
$QR = new QR();
$image = $QR->set([
    "text"=>"Hello, World! This is my QR Generator on PHP, but for now it works only on 1-9 versions, that means text maximum length is 180 bytes. Yeah, that is very sad, but do we need more???",
    "responseType"=>"image",
    "colorfull"=>true,
    "moduleSize"=>5,
    "color0" => 0x00eeff,
    "color1" => 0x005577,
])->getResponse();
?>
```
Allowed settings:

"encodingType" => "byte",   |  encoding type (only byte encoding type released for now)
"text" => "Hello, World!",  |  main text
"responseType" => "base64", |  response type [base64, image or img html element]
"colorfull" => false,       |  colorate QR (use colors)
"color0" => 0x00ccff,       |  QR color 1 (background color)
"color1" => 0x003355,       |  QR color 2 (modules color)
"moduleSize" => 2,          |  size of one module (size of one point in pixels)
"multicolor" => false,      |  colors for tracking (debug stuff)
"bittrace" => false,        |  sequence numbers of bits to check (debug stuff)
"debug" => false,           |  debug flag (printing debug info)