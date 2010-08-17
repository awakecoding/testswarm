
<h3>
<?php
	echo $job_name;

	if ( $owner && $_SESSION['auth'] == 'yes' ) {
		echo get_jobstatus_controls($job_id);
	}
?>
</h3>

<?php echo get_results_table($browsers, $runs, $job_id); ?>
