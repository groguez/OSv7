<?php
function run_command_in_background($command)
{
	shell_exec($command.' >/dev/null 2>/dev/null &');
}

function add_command_to_queue_background_queue($command, $username, $url, $task_data)
{
	$CI =& get_instance();	
	
	$site_db = $CI->load->database('site', TRUE);
	$data = [
		'username' => $username,
		'url' => $url,
	    'command' => $command,
	    'status' => 'pending',
		'task_data' => $task_data,
	];

	$site_db->insert('background_jobs', $data);
}