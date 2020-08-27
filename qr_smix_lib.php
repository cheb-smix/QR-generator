<?php
//ini_set('error_reporting',E_ALL & E_DEPRECATED);
//ini_set('display_startup_errors','On');
//ini_set('display_errors','On');

header('Content-Type: text/html; charset=utf-8');
class QR
{
  //работает только до 10 версии QR (т.е. не слишком много текста), есть какой-то баг после 11 версии =( лень искать
  //нормально работает только на utf-8.. а везде koi8-r.. немножко придётся попариться с кодировкой
  //Переменные, которые можно задать вручную, а можно и не задавать, по идее должен сработать автопилот
  public $SETTINGSALLOWEDTOSET = array("CT","cid","sum","VERSION","CL","text","responseType","colorate","color0","color1","moduleSize");
  public $CT; //тип кодирования
  public $VERSION; //версия QR
  public $CL; //уровень коррекции
  public $text = "Привет, мир!"; //текст для преобразования (основная задаваемая переменная)
  public $responseType = "base64"; //base64 чтобы получить ссылку для тега img
  public $b64=null; //сюда запишется ссылка base64
  public $colorate = false; // применение цвета
  public $color0 = 0x00ccff; // цвет qr0
  public $color1 = 0x003355; // цвет qr1
  public $moduleSize = 2;
  public $cid = null;
  public $sum = null;
  
  //переменные по умолчанию
  private $QRsize = 21;
  private $defct = "byte"; //дефолтный тип кодирования
  private $defver = 1; //дефолтная версия QR
  private $defcli = 1; //дефолтный индекс уровня коррекции
  private $defcl = "M"; //дефолтный уровень коррекции
  private $defmi = 4; //дефолтный индекс маски
  public $debug = false; //флаг дебага отображения вычисляемых переменных
  private $deftext = "ST00012|Name=s|PersonalAcc=40702810900010060173|BankName=Ф-Л БАНКА ГПБ (АО) ПРИВОЛЖСКИЙ|BIC=042202764|CorrespAcc=30101810700000000764|PayeeINN=2127313086|PersAcc=";

  //переменные, вычисляемые в процессе генерации
  private $MI; //индекс маски
  private $SIZE; //Размер QR
  private $CLI; //индекс уровня коррекции
  private $textBytesTotal = null; //количество байтов заданного текста
  private $CTC = null; //бинарный код типа кодирования
  private $maxInfo = null; //максимальное количество полезной информации в битах
  private $maxInfoBytes = null; //максимальное количество полезной информации в байтах
  private $totalBytes = null; //примерное общее количество байтов информации
  private $MBCODE = null; //бинарный код маски
  private $VERCODE = null; //бинарный код версии QR
  private $LPPS = null; //расположение выравнивающих узоров
  private $DATALENGTH = null; //длина поля количества данных
  private $ECC = null; //Количество байтов коррекции на один блок (codewords)
  private $GP = null; //Генерирующий многочлен для codewords
  private $BNUM = null; //Количество блоков для отображения
  private $BARR = null; //основной массив байтов информации
  private $MATRIX = null; //основной массив QR
  private $PiP = null; //отступ в пикселях
  private $PiM = 5; //отступ в модулях
  private $BLOCKS = array(); //блоки байтов данных
  private $CORBLOCKS = array(); //блоки байтов коррекции
  public $multicolor = false; // цвета для проверки распределения инфы
  public $bittrace = false; // порядковые номера битов для проверки
  private $totalCanvasFreeModules = 0; //кол-во свободных модулей для проверки перед внесением данных
  private $totalDataModulesWithEC = 0; //общее кол-во бит для размещения
  private $totalBitsShouldBe = 0; //предварительное число бит холста
  private $systemInfo = ''; //тип кодирования и количество информации, системная информация в блоке данных

  //Необходимые массивы
  //Массив уровней коррекции
  public $CLarr = array('L','M','Q','H');
  public $CLcodes = array('01','00','11','10');
  //Массив типов кодирования
  public $CTs = array("numeric"=>"0001","alphanumeric"=>"0010","byte"=>"0100","1"=>"0001","2"=>"0010","3"=>"0100"); 
  //Массив буквенно-цифрового типа кодирования
  public $alphanumeric = array('0','1','2','3','4','5','6','7','8','9','A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z',' ','$','%','*','+','-','.','/',':'); 
  public $SysInfoPos = null;

  //массив максимального количества полезной информации в битах для выбранных версии и уровня коррекции
  public $MaxInfoArray = array(
    'L'=>array(152,272,440,640,864,1088,1248,1552,1856,2192,2592,2960,3424,3688,4184,4712,5176,5768,6360,6888,7456,8048,8752,9392,10208,10960,11744,12248,13048,13880,14744,15640,16568,17528,18448,19472,20528,21616,22496,23648),
    'M'=>array(128,224,352,512,688,864,992,1232,1456,1728,2032,2320,2672,2920,3320,3624,4056,4504,5016,5352,5712,6256,6880,7312,8000,8496,9024,9544,10136,10984,11640,12328,13048,13800,14496,15312,15936,16816,17728,18672),
    'Q'=>array(104,176,272,384,496,608,704,880,1056,1232,1440,1648,1952,2088,2360,2600,2936,3176,3560,3880,4096,4544,4912,5312,5744,6032,6464,6968,7288,7880,8264,8920,9368,9848,10288,10832,11408,12016,12656,13328),
    'H'=>array(72,128,208,288,368,480,528,688,800,976,1120,1264,1440,1576,1784,2024,2264,2504,2728,3080,3248,3536,3712,4112,4304,4768,5024,5288,5608,5960,6344,6760,7208,7688,7888,8432,8768,9136,9776,10208)
  );
  //массив вызываемых функций согласно типу кодирования
  public $CTfuncs = array("numeric"=>"ct_num_gen","alphanumeric"=>"ct_an_gen","byte"=>"ct_byte_gen");
  //массив количества блоков для выбранных версии и уровня коррекции
  public $blocksnum = array(
    'L'=>array(1,1,1,1,1,2,2,2,2,4,4,4,4,4,6,6,6,6,7,8,8,9,9,10,12,12,12,13,14,15,16,17,18,19,19,20,21,22,24,25),
    'M'=>array(1,1,1,2,2,4,4,4,5,5,5,8,9,9,10,10,11,13,14,16,17,17,18,20,21,23,25,26,28,29,31,33,35,37,38,40,43,45,47,49),
    'Q'=>array(1,1,2,2,4,4,6,6,8,8,8,10,12,16,12,17,16,18,21,20,23,23,25,27,29,34,34,35,38,40,43,45,48,51,53,56,59,62,65,68),
    'H'=>array(1,1,2,4,4,4,5,6,8,8,11,11,16,16,18,16,19,21,25,25,25,34,30,32,35,37,40,42,45,48,51,54,57,60,63,66,70,74,77,81)
  );
  //массив количества байтов коррекции для выбранных версии и уровня коррекции
  public $ECCodewordsarray = array(
    'L'=>array(7,10,15,20,26,18,20,24,30,18,20,24,26,30,22,24,28,30,28,28,28,28,30,30,26,28,30,30,30,30,30,30,30,30,30,30,30,30,30,30),
    'M'=>array(10,16,26,18,24,16,18,22,22,26,30,22,22,24,24,28,28,26,26,26,26,28,28,28,28,28,28,28,28,28,28,28,28,28,28,28,28,28,28,28),
    'Q'=>array(13,22,18,26,18,24,18,22,20,24,28,26,24,20,30,24,28,28,26,30,28,30,30,30,30,28,30,30,30,30,30,30,30,30,30,30,30,30,30,30),
    'H'=>array(17,28,22,16,22,28,26,26,24,28,24,28,22,24,24,30,28,28,26,28,30,24,30,30,30,30,30,30,30,30,30,30,30,30,30,30,30,30,30,30)
  );
  //массив генерирующих многочленов
  public $GeneratorPolynomials = array(
    "7"=>array(87, 229, 146, 149, 238, 102, 21),
    "10"=>array(251, 67, 46, 61, 118, 70, 64, 94, 32, 45),
    "13"=>array(74, 152, 176, 100, 86, 100, 106, 104, 130, 218, 206, 140, 78),
    "15"=>array(8, 183, 61, 91, 202, 37, 51, 58, 58, 237, 140, 124, 5, 99, 105),
    "16"=>array(120, 104, 107, 109, 102, 161, 76, 3, 91, 191, 147, 169, 182, 194, 225, 120),
    "17"=>array(43, 139, 206, 78, 43, 239, 123, 206, 214, 147, 24, 99, 150, 39, 243, 163, 136),
    "18"=>array(215, 234, 158, 94, 184, 97, 118, 170, 79, 187, 152, 148, 252, 179, 5, 98, 96, 153),
    "20"=>array(17, 60, 79, 50, 61, 163, 26, 187, 202, 180, 221, 225, 83, 239, 156, 164, 212, 212, 188, 190),
    "22"=>array(210, 171, 247, 242, 93, 230, 14, 109, 221, 53, 200, 74, 8, 172, 98, 80, 219, 134, 160, 105, 165, 231),
    "24"=>array(229, 121, 135, 48, 211, 117, 251, 126, 159, 180, 169, 152, 192, 226, 228, 218, 111, 0, 117, 232, 87, 96, 227, 21),
    "26"=>array(173, 125, 158, 2, 103, 182, 118, 17, 145, 201, 111, 28, 165, 53, 161, 21, 245, 142, 13, 102, 48, 227, 153, 145, 218, 70),
    "28"=>array(168, 223, 200, 104, 224, 234, 108, 180, 110, 190, 195, 147, 205, 27, 232, 201, 21, 43, 245, 87, 42, 195, 212, 119, 242, 37, 9, 123),
    "30"=>array(41, 173, 145, 152, 216, 31, 179, 182, 50, 48, 110, 86, 239, 96, 222, 125, 42, 173, 226, 193, 224, 130, 156, 37, 251, 216, 238, 40, 192, 180)
  );
  //массив длины поля количества данных
  public $datalengtharray = array(
    
    "numeric"=>array(
      array("from"=>1,"to"=>9,"bits"=>10),
      array("from"=>10,"to"=>26,"bits"=>12),
      array("from"=>27,"to"=>40,"bits"=>14)
    ),
    "alphanumeric"=>array(
      array("from"=>1,"to"=>9,"bits"=>9),
      array("from"=>10,"to"=>26,"bits"=>11),
      array("from"=>27,"to"=>40,"bits"=>13)
    ),
    "byte"=>array(
      array("from"=>1,"to"=>9,"bits"=>8),
      array("from"=>10,"to"=>40,"bits"=>16)
    )
  );
  //Поле Галуа
  public $GaloisField = array(1,2,4,8,16,32,64,128,29,58,116,232,205,135,19,38,76,152,45,90,180,117,234,201,143,3,6,12,24,48,96,192,157,39,78,156,37,74,148,53,106,212,181,119,238,193,159,35,70,140,5,10,20,40,80,160,93,186,105,210,185,111,222,161,95,190,97,194,153,47,94,188,101,202,137,15,30,60,120,240,253,231,211,187,107,214,177,127,254,225,223,163,91,182,113,226,217,175,67,134,17,34,68,136,13,26,52,104,208,189,103,206,129,31,62,124,248,237,199,147,59,118,236,197,151,51,102,204,133,23,46,92,184,109,218,169,79,158,33,66,132,21,42,84,168,77,154,41,82,164,85,170,73,146,57,114,228,213,183,115,230,209,191,99,198,145,63,126,252,229,215,179,123,246,241,255,227,219,171,75,150,49,98,196,149,55,110,220,165,87,174,65,130,25,50,100,200,141,7,14,28,56,112,224,221,167,83,166,81,162,89,178,121,242,249,239,195,155,43,86,172,69,138,9,18,36,72,144,61,122,244,245,247,243,251,235,203,139,11,22,44,88,176,125,250,233,207,131,27,54,108,216,173,71,142,1);
  //массив расположений выравнивающих узоров согласно версии
  public $LPP = array(
    0,
    0,
    array(18),
    array(22),
    array(26),
    array(30),
    array(34),
    array(6,22,38),
    array(6,24,42),
    array(6,26,46),
    array(6,28,50),
    array(6,30,54),
    array(6,32,58),
    array(6,34,62),
    array(6,26,46,66),
    array(6,26,48,70),
    array(6,26,50,74),
    array(6,30,54,78),
    array(6,30,56,82),
    array(6,30,58,86),
    array(6,34,62,90),
    array(6,28,50,72,94),
    array(6,26,50,74,98),
    array(6,30,54,78,102),
    array(6,28,54,80,106),
    array(6,32,58,84,110),
    array(6,30,58,89,114),
    array(6,34,62,90,118),
    array(6,26,50,74,98,122),
    array(6,30,54,78,102,126),
    array(6,26,52,78,104,130),
    array(6,30,56,82,108,134),
    array(6,34,60,86,112,138),
    array(6,30,58,86,114,142),
    array(6,34,62,90,118,146),
    array(6,30,54,78,102,126,150),
    array(6,24,50,76,102,128,154),
    array(6,28,54,80,106,132,158),
    array(6,32,58,84,110,136,162),
    array(6,26,54,82,110,138,166),
    array(6,30,58,86,114,142,170)
  );
  //массив бинарных кодов версий
  public $verCodes = array(
    "7"=>"000010 011110 100110",
    "8"=>"010001 011100 111000",
    "9"=>"110111 011000 000100",
    "10"=>"101001 111110 000000",
    "11"=>"001111 111010 111100",
    "12"=>"001101 100100 011010",
    "13"=>"101011 100000 100110",
    "14"=>"110101 000110 100010",
    "15"=>"010011 000010 011110",
    "16"=>"011100 010001 011100",
    "17"=>"111010 010101 100000",
    "18"=>"100100 110011 100100",
    "19"=>"000010 110111 011000",
    "20"=>"000000 101001 111110",
    "21"=>"100110 101101 000010",
    "22"=>"111000 001011 000110",
    "23"=>"011110 001111 111010",
    "24"=>"001101 001101 100100",
    "25"=>"101011 001001 011000",
    "26"=>"110101 101111 011100",
    "27"=>"010011 101011 100000",
    "28"=>"010001 110101 000110",
    "29"=>"110111 110001 111010",
    "30"=>"101001 010111 111110",
    "31"=>"001111 010011 000010",
    "32"=>"101000 011000 101101",
    "33"=>"001110 011100 010001",
    "34"=>"010000 111010 010101",
    "35"=>"110110 111110 101001",
    "36"=>"110100 100000 001111",
    "37"=>"010010 100100 110011",
    "38"=>"001100 000010 110111",
    "39"=>"101010 000110 001011",
    "40"=>"111001 000100 010101"
  );
  //массив бинарных кодов масок согласно индексу маски и уровню коррекции (их типа вообще 8 штук, т.е. индексы: 0-7)
  public $masksCodes = array(
    'L'=>array("111011111000100",    "111001011110011",    "111110110101010",    "111100010011101",    "110011000101111",    "110001100011000",    "110110001000001",    "110100101110110"    ),
    'M'=>array("101010000010010",    "101000100100101",    "101111001111100",    "101101101001011",    "100010111111001",    "100000011001110",    "100111110010111",    "100101010100000"    ),
    'Q'=>array("011010101011111",    "011000001101000",    "011111100110001",    "011101000000110",    "010010010110100",    "010000110000011",    "010111011011010",    "010101111101101"    ),
    'H'=>array("001011010001001",    "001001110111110",    "001110011100111",    "001100111010000",    "000011101100010",    "000001001010101",    "000110100001100",    "000100000111011"    )
  );
  //функции масок, применяемые на последнем этапе
  public $masks = array(
    "(X+Y) % 2",
    "Y % 2",
    "X % 3",
    "(X + Y) % 3",
    "(X/3 + Y/2) % 2",
    "(X*Y) % 2 + (X*Y) % 3",
    "((X*Y) % 2 + (X*Y) % 3) % 2",
    "((X*Y) % 3 + (X+Y) % 2) % 2"
  );


  //основные вычислительные функции
  //определение переменных
  public function set($a=array()){
    foreach($a as $k=>$v){
      if(in_array($k,$this->SETTINGSALLOWEDTOSET)){
        $this->$k = $v;
      }
    }
    if($this->cid != null){
      $this->text = $this->deftext.$this->cid;
      if($this->sum != null){
        $this->text .= "|Sum=".$this->sum;
      }
    }


    $this->initiate();
  }
  //определение длины поля количества данных
  public function getDataLength(){
    foreach($this->datalengtharray[$this->CT] as $i=>$row){
      if($this->VERSION>=$row['from'] && $this->VERSION<=$row['to']){
        return $row['bits'];
      }
    }
  }
  //первая инициализация основных переменных
  public function initiate(){
    if($this->CT==null){
      $this->CT = $this->defct;
    }
    if($this->CL==null){
      if($this->CLI==null){
        $this->CLI = $this->defcli;
      }
    }
    if($this->VERSION==null){
      $this->VERSION = $this->defver;
    }
    if($this->MI==null){
      $this->MI = $this->defmi;
    }
  
    $this->CL = $this->CLarr[$this->CLI];
    $this->textBytesTotal = strlen($this->text);
    $this->maxInfo = $this->MaxInfoArray[$this->CL][$this->VERSION-1];
    $this->maxInfoBytes = $this->maxInfo / 8;
    $this->ECC = $this->ECCodewordsarray[$this->CL][$this->VERSION-1];
    $this->LPPS = $this->LPP[$this->VERSION];

    //$this->totalBytes = ceil(($this->ECC + $this->textBytesTotal + 10 + (pow(count($this->LPPS),2))) );
    $this->totalBytes = $this->textBytesTotal + 3;

    if($this->totalBytes > $this->maxInfoBytes){
      if($this->VERSION < 40){
        $this->VERSION++;
        //повышение версии при нехватке места и повторная инициализация
        $this->initiate();
      }else{
        if($this->CLI < 4){
          //понижение уровня коррекции при нехватке места и максимальной версии и повторная инициализация
          $this->CLI++;
          $this->initiate();
        }else{
          //отключение скрипта при нехватке места
          echo "Too much info: ".$this->textBytesTotal." bytes.";
          exit;
        }
      }
    }else{
      //Всё хорошо - продолжаем
      $this->generateQR();
    }
  }
  //второй этап инициализации вторичных переменных
  public function futherInitiation(){
    $this->CTC = $this->CTs[$this->CT];
    $this->MBCODE = $this->masksCodes[$this->CL][$this->MI - 1];
    if($this->VERSION > 6){
      $this->VERCODE = $this->verCodes[$this->VERSION];
    }
    $this->DATALENGTH = $this->getDataLength();
    $this->GP = $this->GeneratorPolynomials[$this->ECC];
    $this->BNUM = $this->blocksnum[$this->CL][$this->VERSION-1];
    $this->QRsize = 17 + ($this->VERSION * 4);
    
    if($this->bittrace) $this->moduleSize = 28;
    if($this->SIZE==null or $this->SIZE=="auto"){
      $this->SIZE = $this->QRsize * $this->moduleSize;
    }else{
      $this->moduleSize = floor((int)$this->SIZE / $this->QRsize );
      $this->SIZE = $this->QRsize * $this->moduleSize;
    }
    $this->PiP = $this->PiM * $this->moduleSize;
    $this->totalBitsShouldBe = $this->maxInfo + ($this->ECC * $this->BNUM * 8);
  }
  //отображение переменных при $debug = true;
  public function debuggin(){
    ?>
    <table border=1 borderColor="grey" cellpadding=4 cellspacing=0 >
    <tr><td>Variable</td><td>Value</td></tr>
    <?
    foreach($this as $k=>$v){
      if(gettype($v)!="array"){
        $value = $v==NULL?"null":$v;
        ?><tr><td><?=$k?></td><td><?=$value?></td></tr><?
      }
    }
    ?>
    </table>
    <?
  }
  function getInvertedGalua($num){
    return array_search($num,$this->GaloisField);
  }
  function getGalua($num){
    return $this->GaloisField[$num];
  }
  
  //старт генерации QR
  public function generateQR(){
    $this->futherInitiation();
    $this->defineEmptyMatrix();
    //выполнение функции, соответствующей выбранному типу кодирования
    $CTFUNC = $this->CTfuncs[$this->CT];
    $this->$CTFUNC();

    $this->genImage();
    //дебаг
    if($this->debug){
      $this->debuggin();
    }
  }
  //корректировка
  function nrmltnum($num,$x=8){
    while(strlen($num)<$x){ 
      $num='0'.$num;
    }
    return $num;
  }
  //XOR
  function bitbybit($n1,$n2){
    $b1=$this->nrmltnum(decbin($n1));
    $b2=$this->nrmltnum(decbin($n2));
    $b3=array();
    for($i=0;$i<8;$i++){
      $b3[] = $b1[$i]==1 ^ $b2[$i]==1;
    }
    $b3 = implode('',$b3);

    //echo "$b1 + $b2 = $b3";
    return bindec($b3);
  }
  function _xor($b1,$b2){
    $b3=array();
    for($i=0;$i<count($b1);$i++){
      $b3[] = $b1[$i]==1 ^ $b2[$i]==1;
    }
    return implode('',$b3);
  }
  //функция работы с цифровым типом кодирования
  public function ct_num_gen(){
    echo "В разработке";
  }
  //функция работы с буквенно-цифровым типом кодирования
  public function ct_an_gen(){
    echo "В разработке";
  }
  //основная функция работы с побайтовым типом кодирования
  public function ct_byte_gen(){
    $tlen = strlen($this->text);
    $this->BARR = array();
    $this->systemInfo = $this->CTC . $this->nrmltnum(decbin($tlen),$this->DATALENGTH);
    
    $binary = $this->systemInfo;

    for($i=0;$i<$tlen;$i++){
      $this->BARR[] = ord($this->text[$i]);
      $binary .= $this->nrmltnum(decbin(ord($this->text[$i])));
    }
    
    $textremain8 = strlen($binary) % 8;
    while($textremain8 > 0){
      $binary .= '0';
      $textremain8--;
    }
    //дополнение пустыми байтами вар1
    $damnBeforeFullfilling = $binary;
    $bitsRemain = $this->maxInfo - strlen($binary);
    $index = 1;
    while($bitsRemain > 0){
      $binary .= ($index % 2==1?"11101100":"00010001");
      $index++;
      $bitsRemain -= 8;
    }
    $binlen = strlen($binary);

    $this->BLOCKS = array();
    $bytesPerBlock = floor($this->maxInfoBytes / $this->BNUM);
    $blockSizes = array();
    for($i=0;$i<$this->BNUM;$i++){
      $blockSizes[$i] = $bytesPerBlock;
    }
    $remain = $this->maxInfoBytes % $this->BNUM;
    $index = $this->BNUM - 1;
    while($remain > 0){
      $blockSizes[$index] = $bytesPerBlock  + 1;
      $index--;
      $remain--;
    }
    $index = 0;
    for($i=0;$i<$this->BNUM;$i++){
      $this->BLOCKS[$i] = array();
      for($n=0;$n<$blockSizes[$i];$n++){
        if($binlen>$index*8){
          $this->BLOCKS[$i][$n] = bindec(substr($binary,$index*8,8));
        }
        $index++;
      }
    }
    if($binlen!=$this->maxInfo){
      $this->PRINTME("Длина данных не совпадает с требуемой $binlen, $this->maxInfo",__LINE__);
    }
    $MAXBLOCKLEN = 0;
    //генерация байтов коррекции
    for($b=0;$b<$this->BNUM;$b++){
      $VARARR = $this->BLOCKS[$b];
      $BLOCKLEN = count($this->BLOCKS[$b]);
      $MAXBLOCKLEN = $MAXBLOCKLEN < $BLOCKLEN ? $BLOCKLEN : $MAXBLOCKLEN;
      for($o=0;$o<$this->ECC;$o++){
        $VARARR[]=0;
      }
      for($i=0;$i<$BLOCKLEN;$i++){
        $A = array_shift($VARARR);
        $VARARR[]=0;
        if($A > 0){
          $B = $this->getInvertedGalua($A);
          for($m=0;$m<$this->ECC;$m++){
            $C = $B + $this->GP[$m];
            $C = $this->getGalua($C % 255);
            $VARARR[$m] = $this->bitbybit($C,$VARARR[$m]);
          }
        }else{
          break;
        }
      }
      array_splice($VARARR,$this->ECC);
      $this->CORBLOCKS[$b] = $VARARR;
    }
      
    //$this->PRINTME($this->BLOCKS,__LINE__);   
    //$this->PRINTME($this->CORBLOCKS,__LINE__); 
    $this->totalDataModulesWithEC = 0;
    //////////объединение в поток
    $BYTEFLOW = '';
    for($s=0;$s<$MAXBLOCKLEN;$s++){
      for($b=0;$b<$this->BNUM;$b++){
        if(isset($this->BLOCKS[$b][$s]) and $this->BLOCKS[$b][$s] >= 0){
          $BYTEFLOW .= $this->nrmltnum(decbin($this->BLOCKS[$b][$s]));
          $this->totalDataModulesWithEC += 8;
        }
      }
    }
    if($binlen!=$this->maxInfo){
      $this->PRINTME("Длина данных не совпадает с требуемым числом бит $binlen, $this->maxInfo",__LINE__);
    }
    
    for($s=0;$s<$this->ECC;$s++){
      for($b=0;$b<$this->BNUM;$b++){
        if(isset($this->CORBLOCKS[$b][$s]) and $this->CORBLOCKS[$b][$s] >= 0){
          $this->CORBLOCKS[$b][$s] = $this->nrmltnum(decbin($this->CORBLOCKS[$b][$s]));
          $BYTEFLOW .= $this->CORBLOCKS[$b][$s];
          $this->totalDataModulesWithEC += 8;
        }
      }
    }
    $this->totalCanvasFreeModules = 0;
    $byteflowlength = strlen($BYTEFLOW);
    if($byteflowlength!=$this->totalBitsShouldBe){
      $this->PRINTME("Длина потока не совпадает с числом свободных бит $byteflowlength, $this->totalBitsShouldBe",__LINE__);
    }
    //$this->PRINTME($BYTEFLOW,__LINE__);   
    ///////////////////////////////////////старт размещения на холсте
    $li = $this->QRsize-1;
    $x=0;
    $h=0;
    $cc=0;
    foreach($this->MATRIX as $x =>$row){
      foreach($row as $y =>$cell){
        if($cell==9){
          $this->totalCanvasFreeModules++;
        }
      }
    }

    for($cc=ceil($this->QRsize/2);$cc>=0;$cc--){
      if($cc%2==1){
        $num1=0; $num2=$li;
      }else{
        $num1=0-$li; $num2=0;
      }

      for($y=$num1;$y<=$num2;$y++){
        $yy = abs($y);
        $x = $cc*2;
        if($cc<=3) $x-=1;
        for($xx=$x;$xx>=$x-1;$xx--){
          if($this->MATRIX[$xx][$yy] == 9){

            if($this->multicolor or $this->bittrace){
              $this->MATRIX[$xx][$yy] = $h;
            }else{
              $this->MATRIX[$xx][$yy] = $this->applyMask($xx,$yy)?($BYTEFLOW[$h]==0?1:0):$BYTEFLOW[$h];
              if(!isset($BYTEFLOW[$h])){
                $this->MATRIX[$xx][$yy] = 9;
               // var_dump($xx,$yy,$BYTEFLOW[$h]);
              }
            }
            $h++;
          }
        }  
      }
    }

    if($this->totalCanvasFreeModules!=$this->totalDataModulesWithEC){
      $this->PRINTME("Длина данных не совпадает с числом свободных бит $this->totalCanvasFreeModules, $this->totalDataModulesWithEC",__LINE__);

    }
    if($this->totalCanvasFreeModules!=$this->totalBitsShouldBe){
      $this->PRINTME("Число свободных модулей не совпадает с числом свободных бит $this->totalCanvasFreeModules, $this->totalBitsShouldBe",__LINE__);
    }
  }

  public function applyMask($x,$y){
    switch ($this->MI){
      case 1:
       // if($x>18 and $y>18) var_dump($x,$y,($x+$y)%2==0);
        return ($x+$y)%2==0;
      case 2:
        return $y%2==0;
      case 3:
        return $x%3==0;
      case 4:
        return ($x+$y)%3==0;
      case 5:
        return ($x/3 + $y/2)%2==0;
      case 6:
        return ($x*$y)%2 + ($x*$y)%3 == 0;
      case 7:
        return (($x*$y)%2 + ($x*$y)%3)%2==0;
      case 8:
        return (($x*$y)%3 + ($x*$y)%2)%2==0;
    }
  }
  public function defineEmptyMatrix(){
    $this->MATRIX = array();
    for($x=0;$x<$this->QRsize;$x++){
      $this->MATRIX[$x] = array();
      for($y=0;$y<$this->QRsize;$y++){
        $this->MATRIX[$x][$y] = 9;
      }
    }
    $this->setBasicElementsToMatrix();
  }
  public function setBasicElementsToMatrix(){
    $this->newSearchP(3,3);
    $this->newSearchP(3,$this->QRsize-4);
    $this->newSearchP($this->QRsize-4,3);
    if(is_array($this->LPPS)){
      foreach($this->LPPS as $c=>$x){
        foreach($this->LPPS as $r=>$y){
          $this->newAlignmentP($x,$y);
        }
      }
    }
    for($x=8;$x<$this->QRsize-8;$x++){
      if(($x + 8)%2==1){
        $this->MATRIX[$x][6] = 0;
      }else{
        $this->MATRIX[$x][6] = 1;
      }
    }
    for($y=8;$y<$this->QRsize-8;$y++){
      if(($y+8)%2==1){
        $this->MATRIX[6][$y] = 0;
      }else{
        $this->MATRIX[6][$y] = 1;
      }
    }
    $this->MATRIX[8][$this->QRsize-8] = 1;
    
    $this->SysInfoPos = json_decode('[[[0,8],[8,-1]],[[1,8],[8,-2]],[[2,8],[8,-3]],[[3,8],[8,-4]],[[4,8],[8,-5]],[[5,8],[8,-6]],[[7,8],[8,-7]],[[8,8],[-8,8]],[[8,7],[-7,8]],[[8,5],[-6,8]],[[8,4],[-5,8]],[[8,3],[-4,8]],[[8,2],[-3,8]],[[8,1],[-2,8]],[[8,0],[-1,8]]]');

    for($i=0;$i<strlen($this->MBCODE);$i++){
      $x = $this->SysInfoPos[$i][0][0];
      $y = $this->SysInfoPos[$i][0][1];
      $this->MATRIX[$x][$y] = $this->MBCODE[$i];
      $x = $this->SysInfoPos[$i][1][0];
      $y = $this->SysInfoPos[$i][1][1];
      $x = $x<0?$this->QRsize+$x:$x;
      $y = $y<0?$this->QRsize+$y:$y;
      $this->MATRIX[$x][$y] = $this->MBCODE[$i];
    }

    if($this->VERSION > 6){
      $vc = explode(' ',$this->VERCODE);
      for($y=0;$y<count($vc);$y++){
        $bit = $vc[$y];
        for($x=0;$x<strlen($bit);$x++){
          $yy = $this->QRsize - 11 + $y;
          $this->MATRIX[$x][$yy] = $bit[$x];
          $this->MATRIX[$yy][$x] = $bit[$x];
        }
      }
    }
    if(count($this->MATRIX) != $this->QRsize){
      $this->PRINTME("Количество столбцов не совпадает!".count($this->MATRIX)." != ".$this->QRsize,__LINE__);
    }
    if(count($this->MATRIX[0]) != $this->QRsize){
      $this->PRINTME("Количество строк не совпадает!".count($this->MATRIX[0])." != ".$this->QRsize,__LINE__);
    }
  }
  public function newAlignmentP($x,$y){
    if(!in_array($this->MATRIX[$x][$y],array(0,1))){
      //$this->PRINTME("Распечатка выравнивающего узора на $x-$y",__LINE__);
      $startX = $x-2;
      $startY = $y-2;
      for($xx=0;$xx<5;$xx++){
        for($yy=0;$yy<5;$yy++){
          $rx=$startX+$xx;
          $ry=$startY+$yy;
          if(isset($this->MATRIX[$rx][$ry])){
            if($yy==0 or $xx==0 or $yy==4 or $xx==4){
              $this->MATRIX[$rx][$ry] = 1;
            }else{
              $this->MATRIX[$rx][$ry] = 0;
            }
          }
          $this->MATRIX[$x][$y] = 1;
        }
      }
    }
  }
  public function newSearchP($x,$y){
    //$this->PRINTME("Распечатка поискового узора на $x-$y",__LINE__);
    $startX = $x-4;
    $startY = $y-4;
    for($xx=0;$xx<9;$xx++){
      for($yy=0;$yy<9;$yy++){
        $rx=$startX+$xx;
        $ry=$startY+$yy;
        if(isset($this->MATRIX[$rx][$ry])){
          if($yy==2 && $xx>2 && $xx<6 or $xx==2 && $yy>=2 && $yy<=6 or $yy==6 && $xx>2 && $xx<6 or $xx==6 && $yy>=2 && $yy<=6){
            $this->MATRIX[$rx][$ry] = 0;
          }elseif($yy==0 or $xx==0 or $yy==8 or $xx==8){
            $this->MATRIX[$rx][$ry] = 0;
          }else{
            $this->MATRIX[$rx][$ry] = 1;
          }
        }
      }
    }
  }
  public function genImage(){
    $image = imagecreatetruecolor($this->SIZE+($this->PiP*2),$this->SIZE+($this->PiP*2)) or die('Cannot create image');
    imagefill($image, 0, 0, $this->colorate?$this->color0:0xffffff);

    $matrix = imagecreatetruecolor($this->SIZE,$this->SIZE) or die('Cannot create image');
    imagefill($matrix, 0, 0, 0xccff00);

    foreach($this->MATRIX as $x => $col){
      foreach($col as $y => $cell){
        if($this->bittrace){
          
          if($cell==0){ imagefilledrectangle( $matrix , $x*$this->moduleSize , $y*$this->moduleSize , ($x+1)*$this->moduleSize , ($y+1)*$this->moduleSize , 0xffffff ); }
          if($cell==1){ imagefilledrectangle( $matrix , $x*$this->moduleSize , $y*$this->moduleSize , ($x+1)*$this->moduleSize , ($y+1)*$this->moduleSize , 0x000000 ); }
          imagestring($matrix, 2, $x*$this->moduleSize, $y*$this->moduleSize, $cell, 0x000000);
        }else{
          $cell = $cell % 10;
          if($cell==0){ if($this->colorate){ $color = $this->color0; }else{ $color = 0xffffff; }}
          if($cell==1){ if($this->colorate){ $color = $this->color1; }else{ $color = 0x000000; }}
          if($cell==2) $color = 0xaa0000;
          if($cell==3) $color = 0xff0000;
          if($cell==4) $color = 0x00aa00;
          if($cell==5) $color = 0x00ff00;
          if($cell==6) $color = 0x0000aa;
          if($cell==7) $color = 0x0000ff;
          if($cell==8) $color = 0x0f00af;
          if($cell==9) $color = 0xccff00;
          imagefilledrectangle( $matrix , $x*$this->moduleSize , $y*$this->moduleSize , ($x+1)*$this->moduleSize , ($y+1)*$this->moduleSize , $color );
        }
      }
    }
    imagecolortransparent($matrix,0xccff00);
    imagecopy($image, $matrix, $this->PiP, $this->PiP, 0, 0, $this->SIZE, $this->SIZE);

    ob_start(); 
      imagepng($image);
      $this->b64 = 'data:image/png;base64,'.base64_encode(ob_get_contents()); 
      ob_end_clean(); 

      if($this->responseType!="base64" or $this->debug){
        ?><center><img style="box-shadow: 0 0 10px black;" src="<?=$this->b64?>"><br><br><?
      }
    //header('Content-type: image/png');
    //imagepng($image);
    imagedestroy($image);
  }
  function PRINTME($o,$stroka){
    if($this->debug){
      ?><pre><h5><?php
      $m=gettype($o);
      echo $m;
      if($m=="array"){
        $cnt = count($o);
      }else{
        $cnt = strlen($o);
      }
      echo " ($cnt) строка $stroka ";
      ?></h5><? print_r($o);?></pre><?
    }
  }
}


$QR = new QR;

$QR->set(array("moduleSize"=>4));
$QR->multicolor = false;
$QR->bittrace = true;

echo $QR->b64;