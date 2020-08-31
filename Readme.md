# PHP QR Generator
==================================

![](https://smix-soft.ru/images/qr.png "QR generated example")

Usage examples:
```php
<?php
$QR = new QR();
$imgElement = $QR->set([
    "text"=>"Hello, World! This is my QR Generator on PHP, but for now it works only on 1-9 versions, that means text maximum length is 180 bytes. Yeah, that is very sad, but do we need more???",
    "responseType"=>"img",
    "colorfull"=>true,
    "moduleSize"=>5,
    "color0" => 0x00eeff,
    "color1" => 0x005577,
])->getResponse();
# You've got <img> element in $imgElement 

$base64 = $QR->set([
    "text"=>"Hello, World! This is my QR Generator on PHP, but for now it works only on 1-9 versions, that means text maximum length is 180 bytes. Yeah, that is very sad, but do we need more???",
    "responseType"=>"base64",
    "moduleSize"=>4,
])->getResponse();
# You've got base64 data in $base64, you can use it in your <img> element or save in database as text for example =)

$QR->set([
    "text"=>"Hello, World! This is my QR Generator on PHP, but for now it works only on 1-9 versions, that means text maximum length is 180 bytes. Yeah, that is very sad, but do we need more???",
    "responseType"=>"image",
    "moduleSize"=>4,
])->getResponse();
# This reponse type displays a ready-made image with headers
?>
```
### Allowed settings:

Setting         | Type    | Possible values    | Comment
----------------|---------|--------------------|----------------------
encodingType    | string  | byte               | encoding type (only byte encoding type released for now)
text            | string  | any                | main text
responseType    | string  | base64, image, img | response type
colorfull       | boolean | false, true        | colorate QR with futher colors
color0          | hex     | any hex color      | QR color 1 (background color)
color1          | hex     | any hex color      | QR color 2 (modules color)
moduleSize      | integer | >0                 | size of one module in pixels
multicolor      | boolean | false, true        | colors for tracking (debug stuff)
bittrace        | boolean | false, true        | sequence numbers of bits to check (debug stuff)
debug           | boolean | false, true        | debug flag (printing debug info)