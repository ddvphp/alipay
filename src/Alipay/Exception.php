<?php
namespace DdvPhp\Alipay;

class Exception extends \DdvPhp\DdvException\Error
{
  // 魔术方法
  public function __construct( $message = 'Alipay Error', $code = '400', $errorId = 'ALIPAY_ERROR' , $errorData = array() )
  {
    parent::__construct( $message , $errorId , $code, $errorData );
  }
}
