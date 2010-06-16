<?php
	session_start();
	$config = parse_ini_file("../config.ini", true);
	$contextpath = $config['web']['contextpath'];
	require "../inc/utilities.php";
	require "../inc/db.php";

        function login()
        {
		if ( $_SESSION['username'] && $_SESSION['auth'] == 'yes' ) {
			$username = $_SESSION['username'];
			/* Already authenticated */
			print(json_encode(array("status"=>"ok","loginError"=>"false","reason"=>"")));
			return true;
		} else {
			$username = preg_replace("/[^a-zA-Z0-9_ -]/", "", $_POST['username']);
			$password = $_POST['password'];

			if ( $username && $password ) {

				$result = mysql_queryf("SELECT id FROM users WHERE name=%s AND password=SHA1(CONCAT(seed, %s)) LIMIT 1;", $username, $password);

				if ( mysql_num_rows( $result ) > 0 ) {
					/* Authentication successful */
					$_SESSION['username'] = $username;
					$_SESSION['auth'] = "yes";
					$_SESSION['tab'] = "testreports";
					print(json_encode(Array("status"=>"ok","loginError"=>"false","reason"=>"")));
					return true;
				} else {
					/* Authentication failure */
					print(json_encode(Array("status"=>"ok","loginError"=>"true","reason"=>"Wrong username or password")));
					return true;
				}
			}
		}
        }

	function logout()
	{
		$_SESSION['username'] = "";
		$_SESSION['auth'] = "";
		$_SESSION['tab'] = "";
		session_write_close();
		$status = Array("status"=>"ok");
		print(json_encode($status));
	}

	function dataset_encode($dataset)
	{
		$i = 0;
		$str = '{"aaData":[';
		foreach ($dataset as $data)
		{
			if ($i++) $str .= ',';
			$str .= '[';

			$j = 0;
			foreach ($data as $element)
			{
				if ($j++) $str .= ',';
				$str .= "\"$element\"";
			}
			$str .= ']';
		}
		$str .= ']}';
		return $str;
	}

	function dataset_encode_assoc($dataset)
	{
		$i = 0;
		$str = '{"aaData":[';

		foreach ($dataset as $key => $val)
		{
			if ($i++) $str .= ',';
			$str .= "[\"$key\",\"$val\"]";
		}

		$str .= ']}';
		return $str;
	}

	function getStatus()
	{
		$authenticated = "false";
		$tab = "testreports";

		if ( $_SESSION['username'] && $_SESSION['auth'] == 'yes' ) {
			$authenticated = "true";
		}

		if ( $_SESSION['tab'] ) {
			$tab = $_SESSION['tab'];
		}

		$status = Array("authenticated"=>$authenticated,"tab"=>$tab);
		print(json_encode($status));
	}

	function getUserAgents()
	{
		$query = "SELECT name,engine,version,active,current FROM useragents";
		$result = mysql_queryf($query);

		$uas = Array();

		$i = 0;
		while ( $row = mysql_fetch_row($result) ) {
			$uas[$i++] = $row;
		}

		echo dataset_encode($uas);
	}

	function getTestReports()
	{
		$query = "SELECT 1,jobs.id,jobs.name,users.name,status,jobs.updated,jobs.created FROM jobs,users WHERE jobs.user_id=users.id";
		$result = mysql_queryf($query);

		$test_reports = array();

		$i = 0;
		while ( $row = mysql_fetch_row($result) ) {
			$row[0] = '<img src=\"../images/details_open.png\">';
			$test_reports[$i++] = $row;
		}

		echo dataset_encode($test_reports);
	}

	function getTestEngines()
	{
		$query = "SELECT DISTINCT engine FROM useragents WHERE active=1 ORDER BY engine";
		$result = mysql_queryf($query);

		$test_engines = array();
		while ( $row = mysql_fetch_row($result) ) {
			array_push($test_engines, $row[0]);
		}

		$i = 0;
		$test_engines_assoc = array();
		for ($i = 0; $i < count($test_engines); $i++) {
			$test_engines_assoc[$test_engines[$i]] = $i;
		}
		$_SESSION['engines'] = $test_engines_assoc;

		$enginesJSON = array("engine" => $test_engines);
		print(json_encode($enginesJSON));
	}

	function getTestResults()
	{
		$query = "SELECT useragents.engine,os,useragents.name,status,fail,error,total,run_id,client_id FROM (SELECT os,clients.useragent_id,status,fail,error,total,run_id,client_id FROM (SELECT run_id,client_id,status,fail,error,total FROM run_client) AS runs JOIN clients ON runs.client_id=clients.id) as results JOIN useragents ON results.useragent_id=useragents.id";
		$result = mysql_queryf($query);

		while ( $row = mysql_fetch_row($result) ) {
			$ei = $_SESSION['engines'][$row[0]];
			if (strlen($run_results["$row[7]"][$ei]) > 0) {
				$run_results["$row[7]"][$ei] .= '|';
			}
			$run_results["$row[7]"][$ei] .=
				"$row[1],$row[2],$row[3],$row[4],$row[5],$row[6],$row[7],$row[8]";
		}

		$i = 0;
		foreach ($run_results as $key => $value) {
			for ($i = 0; $i < count($_SESSION['engines']); $i++) {
				if (strlen($run_results[$key][$i]) < 1) {
					$run_results[$key][$i] = "";
				}
			}
			array_unshift($run_results[$key], $key);
		}

		echo dataset_encode($run_results);
	}

	function getTestDetailsOld($job_id)
	{
		$query = "SELECT run_id,client_id,name,engine,os,status,fail,error,total FROM (SELECT run_id,client_id,useragent_id,os,status,fail,error,total FROM (SELECT run_id,client_id,status,fail,error,total FROM run_client) AS runs JOIN clients ON runs.client_id=clients.id) AS results JOIN useragents ON results.useragent_id=useragents.id";
		$result = mysql_queryf($query);

		$last_run_id = 0;
		$test_results = Array();
		$test_details = Array();

		$i = -1;
		$j = 0;

		while ( $row = mysql_fetch_row($result) )
		{
			if ($last_run_id != $row[0]) {
				$i++;
				$j = 0;
				$test_results = Array();
				$last_run_id = $row[0];
			}

			if ($j > 0) {
				$test_results[1] .= '|';
			}

			$test_results[0] = $last_run_id;
			$test_results[1] .= "$row[0],$row[1],$row[2],$row[3],$row[4],$row[5],$row[6],$row[7],$row[8]";
			$j++;
			
			$test_details[$i] = $test_results;
		}

		echo dataset_encode($test_details);
	}

	function getJobs()
	{
		$query = "SELECT jobs.name,users.name,status,jobs.updated,jobs.created,jobs.id FROM jobs,users WHERE jobs.user_id=users.id";
		$result = mysql_queryf($query);

		$jobs = Array();

		$i = 0;
		while ( $row = mysql_fetch_row($result) ) {
			$jobs[$i++] = $row;
		}

		echo dataset_encode($jobs);
	}

	function deleteJob($job_id)
	{
		$results = mysql_queryf("SELECT runs.id as id FROM users, jobs, runs WHERE users.name=%s AND jobs.user_id=users.id AND jobs.id=%u AND runs.job_id=jobs.id;", $_SESSION['username'], $job_id);

		if ( mysql_num_rows($results) > 0 ) {
			mysql_queryf("DELETE FROM run_client WHERE run_id in (select id from runs where job_id=%u);", $job_id);
			mysql_queryf("DELETE FROM run_useragent WHERE run_id in (select id from runs where job_id=%u);", $job_id);
			mysql_queryf("DELETE FROM runs WHERE job_id=%u;", $job_id);
			mysql_queryf("DELETE FROM jobs WHERE id=%u;", $job_id);
		}

		while ( $row = mysql_fetch_row($results) ) {
			$run_id = $row[0];
			mysql_queryf("DELETE FROM run_client WHERE run_id=%u;", $run_id);
			mysql_queryf("DELETE FROM run_useragent WHERE run_id=%u;", $run_id);
		}
	}

	function getSettings()
	{
		$query = "SELECT name,auth FROM users";
		$result = mysql_queryf($query);
		$settings_assoc = mysql_fetch_assoc($result);
		echo dataset_encode_assoc($settings_assoc);
	}

	/* unauthenticated actions */
	if (isset($_REQUEST["action"]))
	{
		switch ($_REQUEST["action"])
		{
			case("login"):
				login();
				exit();
				break;

			case("logout"):
				logout();
				exit();
				break;

			case("status"):
				getStatus();
				exit();
				break;
		}
	}

	if ( $_SESSION['username'] && $_SESSION['auth'] == 'yes' )
	{
		/* authenticated actions */
		if (isset($_REQUEST["action"]))
		{
			switch ($_REQUEST["action"])
			{
				case("testreports"):
					getTestReports();
					exit();
					break;

				case("testengines"):
					getTestEngines();
					exit();
					break;

				case("testresults"):
					getTestResults();
					exit();
					break;

				case("jobcontrol"):
					switch ($_POST["type"])
					{
						case("delete"):
							if (is_numeric($_POST["job_id"])) {
								deleteJob($_POST["job_id"]);
							}
							exit();
							break;
						
						default:
							getJobs();
							exit();
							break;
					}
					exit();
					break;

				case("useragents"):
					getUserAgents();
					exit();
					break;

				case("settings"):
					getSettings();
					exit();
					break;
			}
		}
	}
?>
