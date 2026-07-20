<?php
class Background extends MY_Controller 
{	
	
		function __construct()
		{
			ini_set('memory_limit','1024M');
			parent::__construct();
			if (!is_cli())//Running from web should have report permissions
			{	
				die('Must run from cli');
			}			
		}
		
		private function validate_command($command)
		{
		    $allowed_commands = [
		        [
					'executable' => 'php',
		            'class' => 'shopify_webhook',
		            'method' => 'save_item_in_background'
				],
		        [
					'executable' => 'php',
		            'class' => 'shopify_webhook',
		            'method' => 'save_order_in_background'
		        ]
		    ];

		    $parts = explode(' ', $command);
		    $executable = $parts[0] ?? '';
		    $class = $parts[2] ?? '';
		    $method = $parts[3] ?? '';

		    // Check against allowed commands
		    foreach ($allowed_commands as $allowed)
		    {
		        if ($allowed['class'] == $class && $allowed['method'] == $method && $allowed['executable'] == $executable)
		        {
		            return true; // Valid command
		        }
		    }

		    return false; // Invalid command
		}
		
		function index()
		{			
		    $site_db = $this->load->database('site', TRUE);	
			$counter = 0; // Initialize the counter	
			$last_cleanup_time = time(); // Track time of last cleanup
		    while (true) 
		    {
		        // Explicitly collect garbage to prevent memory leaks
		        gc_collect_cycles();

                        // Fetch the next pending job in a transaction to avoid race conditions
                        $site_db->trans_start();
                        $job = $site_db->query("SELECT * FROM background_jobs WHERE status = 'pending' ORDER BY created_at ASC LIMIT 1 FOR UPDATE")
                            ->row();
                        if ($job)
                        {
                            $site_db->where('id', $job->id)->update('background_jobs', ['status' => 'running']);
                        }
                        $site_db->trans_complete();

                        if ($job)
                        {
            		
					if (!$this->validate_command($job->command))
					{
						$site_db->where('id', $job->id)->update('background_jobs', ['status' => 'failed', 'result' => 'Unauthorized command']);
						continue;
					}
					
		            $output = null;
		            $return_var = null;
            		$task_data = $job->task_data;
						$url = $job->url;
						$username = $job->username;
						
						 $this->load->helper('text');
						 $url_encoded = json_encode(base64UrlEncode($url));

		            exec($job->command.' '.$url_encoded.' '.$username.' '.$job->id, $output, $return_var);
		            $status = ($return_var === 0) ? 'completed' : 'failed';
					$result = strlen($result = implode("\n", array_slice($output, 0, 100))) > 1000 ? substr($result, 0, 997) . '...' : $result;
					// $result = implode("\n", $output);
		            $site_db->where('id', $job->id)->update('background_jobs', ['status' => $status, 'result' => $result]);
		            usleep(400000);
		        } 
		        else 
		        {
	            usleep(100000);
		        }
				
			    // Every 6 hours remove completed jobs
				if (time() - $last_cleanup_time >= 6 * 3600) // 6 hours = 21600 seconds
				{
				   $site_db->where('status', 'completed')->delete('background_jobs');
				   $last_cleanup_time = time();
				}
				
				// Periodically refresh database connection
		        $counter++;
		        if ($counter >= 100) 
				{ // Reset counter after 100 iterations
		            $site_db->close();
		            $site_db = $this->load->database('site', TRUE);
		            $counter = 0;
		        }		    
			}
		}
	}