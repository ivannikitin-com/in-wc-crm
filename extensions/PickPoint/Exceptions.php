<?php
/**
 * API PickPoint
 */
namespace IN_WC_CRM\Extensions\PickPoint;
use \Exception as Exception;

// Не указаны данные
class NoСredentialsException extends Exception {}
class LoginException extends Exception {}
class SendException extends Exception {}
class EmptyResponseException extends Exception {}