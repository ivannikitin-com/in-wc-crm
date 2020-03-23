<?php
/**
 * API B2CPL
 */
namespace IN_WC_CRM\Extensions\B2CPL;
use \Exception as Exception;

// Не указаны данные
class NoСredentialsException extends Exception {}
//class SendException extends Exception {}
class EmptyResponseException extends Exception {}
class EmptyOrderIDsException extends Exception {}
class NoOrdersException extends Exception {}
