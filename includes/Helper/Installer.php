<?php
namespace YaySMTP\Helper;

defined('ABSPATH') || exit;

class Installer {
  protected static $instance = null;

  public static function getInstance() {
    if (null == self::$instance) {
      self::$instance = new self;
    }

    return self::$instance;
  }

  private function __construct() {
    $this->setupPages();
    $this->createTables();
  }

  public function setupPages() {

  }

  public function pageExit($postTitle) {
    $foundPost = post_exists($postTitle);
    return $foundPost;
  }

  public function createTables() {
    include_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $this->createYaySMTPEmailLogs();
    // Modify $wpdb->prefix . yaysmtp_email_logs table (Ex: Add colunm, ....)
    $this->modifyYaySMTPEmailLogs();
  }

  public function createYaySMTPEmailLogs() {
    global $wpdb;
    $table = $wpdb->prefix . 'yaysmtp_email_logs';
    $sql   = "CREATE TABLE $table (
      `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
      `subject` varchar(1000) DEFAULT NULL,
      `email_from` varchar(300) DEFAULT NULL,
      `email_to` longtext DEFAULT NULL,
      `mailer` varchar(300) DEFAULT NULL,
      `date_time` datetime NOT NULL,
      `status` int(1) DEFAULT NULL,
      `content_type` varchar(300) DEFAULT NULL,
      `body_content` longtext DEFAULT NULL,
      `reason_error` varchar(300) DEFAULT NULL,
      `flag_delete` int(1) DEFAULT 0,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

    if ( $wpdb->get_var( "show tables like '$table'" ) != $table ) {
      dbDelta( $sql );
    }
  }
  
  public function modifyYaySMTPEmailLogs() {
    global $wpdb;
    $table = $wpdb->prefix . 'yaysmtp_email_logs';

    if ( $wpdb->get_var( "show tables like '$table'" ) == $table ) {
      $columns = array(
        array(
          'name' => 'root_name',
          'type' => 'varchar(1000)'
        ),
        array(
          'name' => 'extra_info',
          'type' => 'longtext'
        )
      );

      foreach ( $columns as $column ) {
        $col_name = $column['name'];
        $col_type = $column['type'];
        $check_col_exist = $wpdb->query( $wpdb->prepare( "SHOW COLUMNS FROM `$table` LIKE %s ", $col_name ) ); 
        if ( empty( $check_col_exist ) ) {
          $alter_query = 'ALTER TABLE ' . $table . '
                          ADD COLUMN ' . $col_name . ' ' . $col_type . ' DEFAULT NULL';
          $ret = $wpdb->query( $alter_query );
        }
      }
    }
  }
}
