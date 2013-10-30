<?php
class Gentleman_of_Fortune {

	/**
	 * Gets OJS article abstract from ID
	 *
	 * @id - Numeric OJS ID of article to get the abstract of
	 * @conn - Database connection
	 * 
	 * Returns array of abstract info
	 */
	function get_ojs_abstract($id, $conn){
		$article_id = (int) $id;

		$abstract->title_q = "SELECT setting_value FROM  `article_settings` WHERE article_id = {$article_id} AND setting_name =  \"cleanTitle\") LIMIT 1;";
		$abstract->text_q = "SELECT setting_value FROM  `article_settings` WHERE article_id = {$article_id} AND setting_name =  \"abstract\") LIMIT 1;";

		$abstract->title = ($results = $conn->query($abstract->title_q)) ? $results->fetch_array(MYSQLI_NUM) : null;
		$abstract->text = ($results = $conn->query($abstract->text_q)) ? $results->fetch_array(MYSQLI_NUM) : null;

		return $abstract;
		}

	/** 
	 * Get WP auth
	 *
	 */
	function grapple_ojs($db){
		// Connect to database
		if(strstr(DB_HOST, ':')) {
			$db_host = explode(':', DB_HOST);
			$host = $db_host[0];
			$port = $db_host[1];
		}
		else $host = DB_HOST;

		$mysqli = new mysqli($host, DB_USER, DB_PASSWORD, $db, $port);
		if ($mysqli->connect_errno) {
			printf("Connect failed: %s\n", $mysqli->connect_error);
			return false;
		}

		return $mysqli;
	}

	/**
	 * Get WP install path
	 */
	function get_wp_install_path(){

		$base = dirname(__FILE__);
		$path = false;

		if (@file_exists(dirname(dirname($base))."/wp-config.php"))
		{
			$path = dirname(dirname($base))."/wp-config.php";
		}
		else
			if (@file_exists(dirname(dirname(dirname($base)))."/wp-config.php"))
			{
				$path = dirname(dirname(dirname($base)))."/wp-config.php";
			}
			else
				$path = false;

			if ($path != false)
			{
				$path = str_replace("\\", "/", $path);
			}
			return $path;
		}


	}
