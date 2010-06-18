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
					$_SESSION['engines'] = "";
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
		$_SESSION['engines'] = "";
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

		$i = 0;
		$test_engines = array();
		while ( $row = mysql_fetch_row($result) ) {
			$test_engines[$i++] = $row[0];
		}

		$i = 0;
		for ($i = 0; $i < count($test_engines); $i++) {
			$_SESSION['engines'][$test_engines[$i]] = $i;
		}

		$enginesJSON = array("engine" => $test_engines);
		return json_encode($enginesJSON);
	}

	function getTestResults()
	{
		if (count($_SESSION['engines']) < 1)
			getTestEngines();

		$query = "SELECT useragents.engine,os,useragents.name,run_client.status,fail,error,total,run_id,client_id,jobs.name,jobs.id,runs.id,runs.name,runs.url FROM run_client,clients,useragents,runs,jobs WHERE (run_client.client_id=clients.id AND clients.useragent_id=useragents.id AND run_client.run_id=runs.id AND runs.job_id=jobs.id)";
		$result = mysql_queryf($query);

		while ( $row = mysql_fetch_row($result) ) {
			$ei = $_SESSION['engines'][$row[0]] + 1;
			if (strlen($run_results[$row[7]][$ei]) > 0) {
				$run_results[$row[7]][$ei] .= '|';
			}
			$run_results[$row[7]][$ei] .=
				"$row[1],$row[2],$row[3],$row[4],$row[5],$row[6],$row[7],$row[8],$row[9],$row[10]";
			$job_details[$row[7]][0] = $row[9];
			$job_details[$row[7]][1] = $row[10];
			$job_details[$row[7]][2] = $row[11];
			$job_details[$row[7]][3] = $row[12];
			$job_details[$row[7]][4] = $row[13];
		}

		foreach ($run_results as $key => $value) {
			for ($i = 1; $i < count($_SESSION['engines']) + 1; $i++) {
				if (strlen($run_results[$key][$i]) < 1)
					$run_results[$key][$i] = "";
			}

			$run_results[$key][0] = $job_details[$key][0] . "," .
						$job_details[$key][3] . "," .
						$job_details[$key][4];

			$run_results[$key][count($_SESSION['engines']) + 1] = $job_details[$key][1];
			$run_results[$key][count($_SESSION['engines']) + 2] = $job_details[$key][2];

			ksort($run_results[$key]);
		}

		echo dataset_encode(array_reverse($run_results));
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
		$query = "SELECT name,auth FROM users WHERE users.name='" . $_SESSION['username'] . "'";
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
					echo getTestEngines();
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
