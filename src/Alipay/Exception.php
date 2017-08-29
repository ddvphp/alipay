<?php

namespace DdvPhp\Alipay;

class Exception extends \DdvPhp\DdvException\Error
{
  // 魔术方法
  public function __construct( $message = 'Alipay Error', $errorId = 'ALIPAY_ERROR' , $code = '400', $errorData = array() )
  {
    parent::__construct( $message , $errorId , $code, $errorData );
  }
}
