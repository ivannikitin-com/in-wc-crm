<?php
/**
 * API CDEK
 */
namespace IN_WC_CRM\Extensions\CDEK;
use \Exception as Exception;

// Не указаны данные
class NoСredentialsException extends Exception {} 
class LoginException extends Exception {}
class SendException extends Exception {}
class EmptyResponseException extends Exception {}
class EmptyOrderIDsException extends Exception {}
class NoOrdersException extends Exception {}
