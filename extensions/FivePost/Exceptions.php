<?php
/**
 * API B2CPL
 */
namespace IN_WC_CRM\Extensions\FivePost;
use \Exception as Exception;

// Не указаны данные
class GetTokenException extends Exception {}
class NoСredentialsException extends Exception {}
//class SendException extends Exception {}
class EmptyResponseException extends Exception {}
class EmptyOrderIDsException extends Exception {}
class NoOrdersException extends Exception {}
class NoRequiredParameter extends Exception {}
