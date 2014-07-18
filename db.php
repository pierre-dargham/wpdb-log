<?php

define('WPDB_LOG', true); 				// WPDB_LOG : bool true|false : activate the log function
define('WPDB_LOG_TABLE', 'wp_db_log');	// WPDB_LOG_TABLE : string : name of the log table
define('WPDB_LOG_NO_SELECT', true);		// WPDB_LOG_NO_SELECT : bool true|false : if true, SELECT queries are not logged

/**
 *	WPDB_LOG
 *
 *	Description : Log in your database every SQL query run by wordpress
 *
 *	Author : Pierre DARGHAM
 *	Mail : pierre.dargham@gmail.com
 *	Date : 06/09/2014
 *
 *	Install :
 *
 *	1. Copy this file into your wp-content folder
 *	2. That's all !
 *
 */

if( !class_exists( 'wpdb_log' ) ) :

	if (!defined('WPDB_LOG') ) {
		if (defined('WP_DEBUG') ) {
			define('WPDB_LOG', WP_DEBUG);
		}
		else {
			define('WPDB_LOG', true);
		}
	}

	if (!defined('WPDB_LOG_TABLE') ) {
		define('WPDB_LOG_TABLE', 'wp_db_log');
	}

	if (!defined('WPDB_LOG_NO_SELECT') ) {
		define('WPDB_LOG_NO_SELECT', true);
	}

	class wpdb_log extends wpdb {
		private $log_table;
		private $no_select;

		/**
		 * Extends wpdb wordpress core class and provides log function
		 *
		 * @param string $dbuser MySQL database user
		 * @param string $dbpassword MySQL database password
		 * @param string $dbname MySQL database name
		 * @param string $dbhost MySQL database host
		 * @param string $log_table name of the log table
		 * @param bool 	 $no_select save select queries
		 */
		function __construct( $dbuser, $dbpassword, $dbname, $dbhost, $log_table = 'wp_db_log', $no_select = true ) {

			parent::__construct($dbuser, $dbpassword, $dbname, $dbhost);

			$this->log_table = $log_table;
			$this->no_select = $no_select;

			$query =
					'CREATE TABLE IF NOT EXISTS '.$log_table.' (
					  id INT(11) NOT NULL AUTO_INCREMENT,
					  date DATETIME NOT NULL,
					  query VARCHAR(9000) NOT NULL,
					  PRIMARY KEY id (id))';

			parent::query($query);		
		}


		/**
		 * Perform a MySQL database query, using current database connection.
		 *
		 *
		 * @param string $query Database query
		 * @return int|false Number of rows affected/selected or false on error
		 */
		function query( $query ) {

				$query_test  = preg_replace("/\s+/", "", $query);
				if(!$this->no_select || strtolower(substr($query_test, 0, 6)) != strtolower('SELECT')) {

					$this->insert_no_log($this->log_table,
						array(
							'date'	=> date("Y-m-d H:i:s"),
							'query' => $query,
							));					
				}

			return parent::query($query);
		}

		function insert_no_log( $table, $data, $format = null ) {
			return $this->_insert_replace_helper_no_log( $table, $data, $format, 'INSERT' );
		}

		function _insert_replace_helper_no_log( $table, $data, $format = null, $type = 'INSERT' ) {
			if ( ! in_array( strtoupper( $type ), array( 'REPLACE', 'INSERT' ) ) )
				return false;
			$this->insert_id = 0;
			$formats = $format = (array) $format;
			$fields = array_keys( $data );
			$formatted_fields = array();
			foreach ( $fields as $field ) {
				if ( !empty( $format ) )
					$form = ( $form = array_shift( $formats ) ) ? $form : $format[0];
				elseif ( isset( $this->field_types[$field] ) )
					$form = $this->field_types[$field];
				else
					$form = '%s';
				$formatted_fields[] = $form;
			}
			$sql = "{$type} INTO `$table` (`" . implode( '`,`', $fields ) . "`) VALUES (" . implode( ",", $formatted_fields ) . ")";
			return parent::query( $this->prepare( $sql, $data ) );
		}

	}

	if(WPDB_LOG) {
		$wpdb = new wpdb_log( DB_USER, DB_PASSWORD, DB_NAME, DB_HOST, WPDB_LOG_TABLE,  WPDB_LOG_NO_SELECT);
	}
	else {
		$wpdb = new wpdb( DB_USER, DB_PASSWORD, DB_NAME, DB_HOST );	
	}

endif;
