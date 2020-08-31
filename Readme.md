# h1 PHP QR Generator
==================================
![QR generated example](https://smix-soft.ru/images/qr.png)
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
### h3 Allowed settings:

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