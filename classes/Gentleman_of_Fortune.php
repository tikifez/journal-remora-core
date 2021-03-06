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
	function get_abstract($id, $conn){

		if ( !is_object( $abstract ) ) {
			$abstract = new stdClass();
		}
		$abstract->id = (int) $id;

		$abstract->queries = array(
			'title' => "SELECT `setting_value` FROM  `article_settings` WHERE article_id = {$abstract->id} AND setting_name =  \"cleanTitle\" LIMIT 1;",
			'text' => "SELECT `setting_value` FROM  `article_settings` WHERE article_id = {$abstract->id} AND setting_name =  \"abstract\" LIMIT 1;"
			);

		foreach($abstract->queries as $key => $query) {
			if ($result = $conn->query($query) ) {
				$obj = $result->fetch_object();
				$abstract->$key = $obj->setting_value;
				$result->close();
			}
			else $abstract->errors[] = "Could not retrieve abstract {$key} for article {$id}";
		}

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
			if(current_user_can('activate_plugins') ) echo "<div class=\"alert alert-warning\">Connect failed: {$mysqli->connect_error}</div>" ;
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
