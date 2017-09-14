<?php

namespace psm\Module\Config;

use psm\Util\SparkPostClient;
use SparkPost\SparkPostResponse;

/**
 * Class SparkpostTester
 *
 * @package psm\Module\Config
 * @todo Provide a tester factory by channel (email, sms, pushover and Sparkpost)
 */
class SparkPostTester
{
  /**
   * @var SparkPostClient
   */
  private $sparkpostClient;

  /**
   * @var SparkPostResponse
   */
  private $response;

  /**
   * @param SparkPostClient $sparkpostClient
   */
  public function __construct(SparkPostClient $sparkpostClient) {
    $this->sparkpostClient = $sparkpostClient;
  }

  /**
   * @param object $user
   *
   * @return bool
   */
  public function sendTestingEmail($user)
  {
    $mail = psm_build_mail();
    $message = psm_get_lang('config', 'test_message');
    $mail->Subject	= psm_get_lang('config', 'test_subject');
    $mail->Priority	= 1;
    $mail->Body		= $message;
    $mail->AltBody	= str_replace('<br/>', "\n", $message);

    $this->response = $this->sparkpostClient->sendSync([['name' => $user->name, 'email' => $user->email]], $mail);

    return $this->isStatusCodeOk();
  }

  /**
   * @return string
   */
  public function getErrorMessage()
  {
    if ($this->response !== null && !$this->isStatusCodeOk()) {
      $body = $this->response->getBody();
      $body['reason'] = $this->response->getReasonPhrase();

      return print_r($body, true);
    }

    return '';
  }

  /**
   * @return bool
   */
  private function isStatusCodeOk()
  {
    return $this->response->getStatusCode() >= 200 && $this->response->getStatusCode() < 300;
  }
}