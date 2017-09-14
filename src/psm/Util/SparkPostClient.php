<?php

namespace psm\Util;

use GuzzleHttp\Client;
use Http\Adapter\Guzzle6\Client as GuzzleAdapter;
use Http\Client\HttpClient;
use SparkPost\SparkPost;
use SparkPost\SparkPostException;
use SparkPost\SparkPostPromise;
use SparkPost\SparkPostResponse;

class SparkPostClient
{
  /**
   * @var HttpClient
   */
  private $httpClient;

  public function __construct()
  {
    $this->httpClient = new GuzzleAdapter(new Client());
  }

  /**
   * @param array $users
   * @param \PHPMailer $mail
   *
   * @return SparkPostResponse
   */
  public function sendSync(array $users, \PHPMailer $mail)
  {
    return $this->post($this->getSyncClient(), $mail, $users);
  }

  /**
   * @param array $users
   * @param \PHPMailer $mail
   * @todo make it work
   * @deprecated
   */
  public function sendAsync(array $users, \PHPMailer $mail)
  {
    $this
      ->post($this->getAsyncClient(), $mail, $users)
      ->then(null, function(SparkPostException $e) {
        error_log($e->getMessage(), E_WARNING);
      });
  }

  /**
   * @param bool $sync
   *
   * @return SparkPost
   */
  private function getClient($sync = false)
  {
    return new SparkPost($this->httpClient, [
      'key' => psm_get_conf('sparkpost_api_key'),
      'async' => !$sync
    ]);
  }

  /**
   * @return SparkPost
   */
  private function getAsyncClient()
  {
    return $this->getClient();
  }

  /**
   * @return SparkPost
   */
  private function getSyncClient()
  {
    return $this->getClient(true);
  }

  /**
   * @param \SparkPost\SparkPost $client
   * @param \PHPMailer $mail
   * @param array $users
   *
   * @return SparkPostPromise|SparkPostResponse
   */
  private function post(SparkPost $client, \PHPMailer $mail, array $users)
  {
    return $client->transmissions->post([
      'content' => [
        'name' => $mail->FromName,
        'from' => $mail->From,
        'subject' => $mail->Subject,
        'html' => $mail->Body,
        'text' => $mail->AltBody
      ],
      'recipients' => $this->getRecipients($users)
    ]);
  }

  /**
   * @param array $users
   *
   * @return array
   */
  private function getRecipients(array $users) {
    return array_map(function(array $userData) {
      return [
        'address' => [
          'name' => $userData['name'],
          'email' => $userData['email'],
        ],
      ];
    }, $users);
  }
}