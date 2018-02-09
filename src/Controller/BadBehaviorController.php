<?php

namespace Drupal\badbehavior\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class BadBehaviorController extends ControllerBase {
  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;



  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('date.formatter'),
      $container->get('config.factory')
    );
  }

  /**
   * Constructs a BadBehaviorController object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   A database connection.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   */
  public function __construct(Connection $database, DateFormatterInterface $date_formatter, ConfigFactoryInterface $config_factory) {
    $this->database = $database;
    $this->dateFormatter = $date_formatter;
    $this->configFactory = $config_factory;
  }

  /**
   * Builds the log table seen in the /admin/reports/badbehavior/ page.
   *
   * @return array
   *   A build array in the format expected by drupal_render().
   */
  public function BBLogMessages() {
    if (!badbehavior_load_includes()) {
      return 'Bad Behavior is not installed correctly. See the README.txt for installation details.';
    }
    $config = $this->configFactory->get('badbehavior.settings');
    $header = array(
      array('data' => $this->t('Response')),
      array('data' => $this->t('Reason')),
      array('data' => $this->t('Date'), 'field' => 'b.date', 'sort' => 'desc'),
      array('data' => $this->t('IP'), 'field' => 'b.ip'),
      array('data' => $this->t('Agent'), 'field' => 'b.user_agent', 'colspan' => 2)
    );

    $query = $this->database->select('bad_behavior_log', 'b')
      ->extend('\Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('\Drupal\Core\Database\Query\TableSortExtender');
    $query = $query
      ->fields('b')
      ->limit(50)
      ->orderByHeader($header);
    if ($config->get('verbose') == FALSE) {
      $query->condition('b.key', '00000000', '<>');
    }
    $result = $query->execute();

    $rows = array();
    foreach ($result as $record) {
      $response = bb2_get_response($record->key);
      $record->localdate = $this->convertDate($record->date);
      $link = Link::fromTextAndUrl($this->t('Details'), Url::fromRoute("badbehavior.event", ['event_id' => $record->id]));
      $rows[] = [
        'data' => [
          $response['response'],
          $response['log'],
          $record->localdate,
          $record->ip,
          $record->user_agent,
          $link,
        ],
      ];
    }

    $build['badbehavior_log_table']  = array(
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No log messages available.'),
    );
    $build['badbehavior_log_pager'] = array('#type' => 'pager');

    return $build;
  }

  /**
   * Displays details about a specific database BadBehavior log message.
   *
   * @param int $event_id
   *   Unique ID of the database log message.
   *
   * @return array
   *   If the ID is located in the Database Logging table, a build array in the
   *   format expected by drupal_render();
   */
  public function eventDetails($event_id) {
    if (!badbehavior_load_includes(array('responses'))) {
      return 'Bad Behavior is not installed correctly. See the README.txt for installation details.';
    }
    $build = array();
    $record = $this->database
    ->select('bad_behavior_log', 'b')
    ->fields('b')
    ->condition('id', $event_id, '=')
    ->execute()
    ->fetchObject();
    if ($record) {
      $response = bb2_get_response($record->key);
      $record->localdate = $this->convertDate($record->date);
      if ($record->ip) {
        $ip = $record->ip;
        $link = Link::fromTextAndUrl($this->t('whois'), Url::fromUri("http://www.whois.sc/{$record->ip}"));
        $hostname = [
          ['#markup' => gethostbyaddr($record->ip) . " ({$link->toString()})"],
        ];
      }
      else {
        $ip = $this->t('Possible Proxy Settings Error: No IP address reported');
        $hostname = $this->t('Possible Proxy Settings Error: No Hostname reported');
      }
      $rows = array(
        array(
          array('data' => $this->t('IP Address'), 'header' => TRUE),
          $ip,
        ),
        array(
          array('data' => $this->t('Hostname'), 'header' => TRUE),
          array('data' => $hostname),
        ),
        array(
          array('data' => $this->t('Date'), 'header' => TRUE),
          array('data' => $record->localdate),
        ),
        array(
          array('data' => $this->t('Request type'), 'header' => TRUE),
          $record->request_method,
        ),
        array(
          array('data' => $this->t('URI'), 'header' => TRUE),
          $record->request_uri,
        ),
        array(
          array('data' => $this->t('Protocol'), 'header' => TRUE),
          $record->server_protocol,
        ),
        array(
          array('data' => $this->t('User Agent'), 'header' => TRUE),
          $record->user_agent,
        ),
        array(
          array('data' => $this->t('Headers'), 'header' => TRUE),
          $record->http_headers,
        ),
        array(
          array('data' => $this->t('Denied Reason'), 'header' => TRUE),
          $response['log'],
        ),
        array(
          array('data' => $this->t('Explanation'), 'header' => TRUE),
          $response['explanation'],
        ),
        array(
          array('data' => $this->t('Response'), 'header' => TRUE),
          $response['response'],
        ),
      );
      $build['badbehavior_table'] = array(
        '#type' => 'table',
        '#rows' => $rows,
      );
    }
    return $build;
  }

  /**
   * Converts dates in BB log screen output to server's time zone.
   *
   * @param $bbdate
   *
   * @return string
   */
  private function convertDate($bbdate) {
    $timestamp = strtotime($bbdate . ' UTC');
    $timeformat = $this->configFactory->get('badbehavior.settings')->get('log_timeformat');
    $format = 'Y-m-d g:i:s a';
    if ($timeformat == '24') {
      $format = 'Y-m-d H:i:s';
    }
    return $this->dateFormatter->format($timestamp, 'custom', $format);
  }
}
