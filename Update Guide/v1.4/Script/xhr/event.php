<?php
if (!IS_LOGGED || $music->config->event_system != 1) {
	header('Location: ' . $site_url);
	exit;
}
$data['status'] = 400;
if ($option == 'create' && $music->user->artist != 0) {
	if (!empty($_POST['name']) && !empty($_POST['location']) && !empty($_POST['desc']) && !empty($_POST['location']) && in_array($_POST['location'], array('online','real')) && !empty($_POST['start_date']) && !empty($_POST['start_time']) && !empty($_POST['end_date']) && !empty($_POST['end_time']) && !empty($_POST['timezone']) && in_array($_POST['timezone'], array_keys($music->timezones)) && !empty($_POST['sell_tickets']) && in_array($_POST['sell_tickets'], array('no','yes')) && !empty($_FILES['image'])) {

		if(!empty($_FILES['image']) && file_exists($_FILES['image']['tmp_name']) && $_FILES['image']['size'] > $music->config->max_upload){
			$max   = size_format($music->config->max_upload);
    		$data['message'] = (lang('File is too big, Max upload size is').": $max");
		}
		elseif ($_POST['location'] == 'online' && empty($_POST['online_url'])) {
			$data['message'] = lang('URL can not be empty');
		}
		elseif ($_POST['location'] == 'online' && !pt_is_url($_POST['online_url'])) {
			$data['message'] = lang('URL can not be empty');
		}
		elseif ($_POST['location'] == 'real' && empty($_POST['real_address'])) {
			$data['message'] = lang('Address can not be empty');
		}
		elseif ($_POST['sell_tickets'] == 'yes') {
			if (empty($_POST['available_tickets']) || !is_numeric($_POST['available_tickets']) || $_POST['available_tickets'] < 1 || empty($_POST['ticket_price']) || !is_numeric($_POST['ticket_price']) || $_POST['ticket_price'] < 1) {
				$data['message'] = lang('Tickets available and Ticket Price can not be empty');
			}
		}
		if (empty($data['message'])) {
			$insert_data = array(
				"user_id" => $music->user->id,
				"name" => Secure($_POST['name']),
				"desc" => Secure($_POST['desc']),
				"start_date" => Secure($_POST['start_date']),
				"start_time" => Secure($_POST['start_time']),
				"end_date" => Secure($_POST['end_date']),
				"end_time" => Secure($_POST['end_time']),
				"timezone" => Secure($_POST['timezone']),
				"time" => time(),
			);
			if ($_POST['location'] == 'online' && !empty($_POST['online_url'])) {
				$insert_data['online_url'] = Secure($_POST['online_url']);
			}
			elseif ($_POST['location'] == 'real' && !empty($_POST['real_address'])) {
				$insert_data['real_address'] = Secure($_POST['real_address']);
			}
			if ($_POST['sell_tickets'] == 'yes') {
				if (!empty($_POST['available_tickets']) && is_numeric($_POST['available_tickets']) && $_POST['available_tickets'] > 0 && !empty($_POST['ticket_price']) && is_numeric($_POST['ticket_price']) && $_POST['ticket_price'] > 0) {
					$insert_data['available_tickets'] = Secure($_POST['available_tickets']);
					$insert_data['ticket_price'] = Secure($_POST['ticket_price']);
				}
			}
	    	$file_info = array(
		        'file' => $_FILES['image']['tmp_name'],
		        'size' => $_FILES['image']['size'],
		        'name' => $_FILES['image']['name'],
		        'type' => $_FILES['image']['type']
		    );
		    $file_upload = ShareFile($file_info);
		    if (empty($file_upload) || empty($file_upload['filename'])) {
		    	$data['message'] = lang('Event Cover can not be empty');
		    	header('Content-Type: application/json');
				echo json_encode($data);
				exit();
		    }
		    $insert_data['image'] = $file_upload['filename'];

		    if (!empty($_FILES['video'])) {
		    	$file_info = array(
			        'file' => $_FILES['video']['tmp_name'],
			        'size' => $_FILES['video']['size'],
			        'name' => $_FILES['video']['name'],
			        'type' => $_FILES['video']['type'],
			        'file_type' => 'video'
			    );
			    if ($music->config->ffmpeg_system != 'on') {
			    	$file_info['allowed'] = 'mp4,m4v,webm,flv,mov,mpeg,mkv';
			    }
			    if ($music->config->ffmpeg_system == 'on') {
			    	$amazone_s3 = $music->config->s3_upload;
                    $ftp_upload = $music->config->ftp_upload;
                    $spaces = $music->config->spaces;
                    $music->config->s3_upload = 'off';
                    $music->config->ftp_upload = 'off';
                    $music->config->spaces = 'off';
			    }
			    $video_file_upload = ShareFile($file_info);
			    if ($music->config->ffmpeg_system == 'on') {
                    $music->config->s3_upload = $amazone_s3;
                    $music->config->ftp_upload = $ftp_upload;
                    $music->config->spaces = $spaces;
			    }
			    if (empty($video_file_upload) || empty($video_file_upload['filename'])) {
			    	$data['message'] = lang('Event Video can not be empty');
			    	header('Content-Type: application/json');
					echo json_encode($data);
					exit();
			    }
			    if ($music->config->ffmpeg_system != 'on') {
			    	$insert_data['video'] = $video_file_upload['filename'];
				}
		    }
		    $insert  = $db->insert(T_EVENTS,$insert_data);
	    	if (!empty($insert)) {
	    		$create_activity = createActivity([
                    'user_id' => $music->user->id,
                    'type' => 'created_event',
                    'event_id' => $insert,
                ]);
	    		$db->where('id',$insert)->update(T_EVENTS,array('hash_id' => uniqid($insert)));
	    		$data['status'] = 200;
	    		$data['message'] = lang('Your event has been published successfully');
	    		$event = GetEventById($insert);
	    		$data['url'] = $event->url;
	    		if ($music->config->ffmpeg_system == 'on') {
	    			RunInBackground($data);
	    			FFMPEGUpload(array('filename' => $video_file_upload['filename'],
	    				               'id' => $insert));
				    // $explode_video = explode('_video', $file_upload['filename']);
				    // $video_file_full_path = dirname(dirname(__DIR__)).'/'.$file_upload['filename'];
				    // $dir         = dirname(dirname(__DIR__));
				    // $video_path_240 = $explode_video[0] . "_video_240p_converted.mp4";
				    // $insert_data['video'] = $video_path_240;
				    
	    		}
	    	}
	    	else{
	    	  $data['message'] = lang('Error 500 internal server error!');
	    	}
		}
	}
	else{
		if (empty($_POST['name'])) {
			$data['message'] = lang('Event name can not be empty');
		}
		elseif (empty($_POST['location'])) {
			$data['message'] = lang('Event location can not be empty');
		}
		elseif (empty($_POST['desc'])) {
			$data['message'] = lang('Event description can not be empty');
		}
		elseif (empty($_POST['start_date'])) {
			$data['message'] = lang('Start date can not be empty');
		}
		elseif (empty($_POST['start_time'])) {
			$data['message'] = lang('Start time can not be empty');
		}
		elseif (empty($_POST['end_date'])) {
			$data['message'] = lang('End Date can not be empty');
		}
		elseif (empty($_POST['end_time'])) {
			$data['message'] = lang('End time can not be empty');
		}
		elseif (empty($_POST['timezone'])) {
			$data['message'] = lang('Timezone can not be empty');
		}
		elseif (empty($_FILES['image'])) {
			$data['message'] = lang('Event image can not be empty');
		}
		else{
			$data['message'] = lang('Please check the details');
		}
	}
}
if ($option == 'edit' && $music->user->artist != 0) {
	if (!empty($_POST['name']) && !empty($_POST['location']) && !empty($_POST['desc']) && !empty($_POST['location']) && in_array($_POST['location'], array('online','real')) && !empty($_POST['start_date']) && !empty($_POST['start_time']) && !empty($_POST['end_date']) && !empty($_POST['end_time']) && !empty($_POST['timezone']) && in_array($_POST['timezone'], array_keys($music->timezones)) && !empty($_POST['sell_tickets']) && in_array($_POST['sell_tickets'], array('no','yes')) && !empty($_POST['id']) && is_numeric($_POST['id']) && $_POST['id'] > 0) {
		$id = secure($_POST['id']);

		$event = GetEventById($id);
		if (empty($event)) {
			$data['message'] = lang('Event not found');
		}
		elseif ($user->id != $event->user_data->id && !isAdmin()) {
			$data['message'] = lang('You are not the owner');
		}
		elseif(!empty($_FILES['image']) && file_exists($_FILES['image']['tmp_name']) && $_FILES['image']['size'] > $music->config->max_upload){
			$max   = size_format($music->config->max_upload);
    		$data['message'] = (lang('File is too big, Max upload size is').": $max");
		}
		elseif ($_POST['location'] == 'online' && empty($_POST['online_url'])) {
			$data['message'] = lang('URL can not be empty');
		}
		elseif ($_POST['location'] == 'online' && !pt_is_url($_POST['online_url'])) {
			$data['message'] = lang('URL can not be empty');
		}
		elseif ($_POST['location'] == 'real' && empty($_POST['real_address'])) {
			$data['message'] = lang('Address can not be empty');
		}
		elseif ($_POST['sell_tickets'] == 'yes') {
			if (empty($_POST['available_tickets']) || !is_numeric($_POST['available_tickets']) || $_POST['available_tickets'] < 1 || empty($_POST['ticket_price']) || !is_numeric($_POST['ticket_price']) || $_POST['ticket_price'] < 1) {
				$data['message'] = lang('Tickets available and Ticket Price can not be empty');
			}
		}
		if (empty($data['message'])) {
			$insert_data = array(
				"name" => Secure($_POST['name']),
				"desc" => Secure($_POST['desc']),
				"start_date" => Secure($_POST['start_date']),
				"start_time" => Secure($_POST['start_time']),
				"end_date" => Secure($_POST['end_date']),
				"end_time" => Secure($_POST['end_time']),
				"timezone" => Secure($_POST['timezone']),
			);
			if ($_POST['location'] == 'online' && !empty($_POST['online_url'])) {
				$insert_data['online_url'] = Secure($_POST['online_url']);
			}
			elseif ($_POST['location'] == 'real' && !empty($_POST['real_address'])) {
				$insert_data['real_address'] = Secure($_POST['real_address']);
			}
			if ($_POST['sell_tickets'] == 'yes') {
				if (!empty($_POST['available_tickets']) && is_numeric($_POST['available_tickets']) && $_POST['available_tickets'] > 0 && !empty($_POST['ticket_price']) && is_numeric($_POST['ticket_price']) && $_POST['ticket_price'] > 0) {
					$insert_data['available_tickets'] = Secure($_POST['available_tickets']);
					$insert_data['ticket_price'] = Secure($_POST['ticket_price']);
				}
			}
			if (!empty($_FILES['image'])) {
				$file_info = array(
			        'file' => $_FILES['image']['tmp_name'],
			        'size' => $_FILES['image']['size'],
			        'name' => $_FILES['image']['name'],
			        'type' => $_FILES['image']['type']
			    );
			    $file_upload = ShareFile($file_info);
			    if (empty($file_upload) || empty($file_upload['filename'])) {
			    	$data['message'] = lang('Event Cover can not be empty');
			    	header('Content-Type: application/json');
					echo json_encode($data);
					exit();
			    }
			    $insert_data['image'] = $file_upload['filename'];
			}
		    	

		    if (!empty($_FILES['video'])) {
		    	$file_info = array(
			        'file' => $_FILES['video']['tmp_name'],
			        'size' => $_FILES['video']['size'],
			        'name' => $_FILES['video']['name'],
			        'type' => $_FILES['video']['type'],
			        'file_type' => 'video'
			    );
			    if ($music->config->ffmpeg_system != 'on') {
			    	$file_info['allowed'] = 'mp4,m4v,webm,flv,mov,mpeg,mkv';
			    }
			    if ($music->config->ffmpeg_system == 'on') {
			    	$amazone_s3 = $music->config->s3_upload;
                    $ftp_upload = $music->config->ftp_upload;
                    $spaces = $music->config->spaces;
                    $music->config->s3_upload = 'off';
                    $music->config->ftp_upload = 'off';
                    $music->config->spaces = 'off';
			    }
			    $video_file_upload = ShareFile($file_info);
			    if ($music->config->ffmpeg_system == 'on') {
                    $music->config->s3_upload = $amazone_s3;
                    $music->config->ftp_upload = $ftp_upload;
                    $music->config->spaces = $spaces;
			    }
			    if (empty($video_file_upload) || empty($video_file_upload['filename'])) {
			    	$data['message'] = lang('Event Video can not be empty');
			    	header('Content-Type: application/json');
					echo json_encode($data);
					exit();
			    }
			    if ($music->config->ffmpeg_system == 'on') {
			    	$db->where('id',$event->id)->update(T_EVENTS,array('240p' => 0,
			                                                           '360p' => 0,
			                                                           '480p' => 0,
			                                                           '720p' => 0,
			                                                           '1080p' => 0,
			                                                           '2048p' => 0,
			                                                           '4096p' => 0));
				    FFMPEGUpload(array('filename' => $video_file_upload['filename'],
				                       'id' => $id));
				    // $explode_video = explode('_video', $video_file_upload['filename']);
				    // $video_file_full_path = dirname(dirname(__DIR__)).'/'.$video_file_upload['filename'];
				    // $dir         = dirname(dirname(__DIR__));
				    // $video_path_240 = $explode_video[0] . "_video_240p_converted.mp4";
				    // $insert_data['video'] = $video_path_240;
				}
				else{
					$insert_data['video'] = $video_file_upload['filename'];
				}
		    }
		    $insert  = $db->where('id',$event->id)->update(T_EVENTS,$insert_data);
	    	if (!empty($insert)) {
	    		$data['status'] = 200;
	    		$data['message'] = lang('Your event has been updated successfully');
	    		$data['url'] = $event->url;
	    	}
	    	else{
	    	  $data['message'] = lang('Error 500 internal server error!');
	    	}
		}
	}
	else{
		if (empty($_POST['name'])) {
			$data['message'] = lang('Event name can not be empty');
		}
		elseif (empty($_POST['location'])) {
			$data['message'] = lang('Event location can not be empty');
		}
		elseif (empty($_POST['desc'])) {
			$data['message'] = lang('Event description can not be empty');
		}
		elseif (empty($_POST['start_date'])) {
			$data['message'] = lang('Start date can not be empty');
		}
		elseif (empty($_POST['start_time'])) {
			$data['message'] = lang('Start time can not be empty');
		}
		elseif (empty($_POST['end_date'])) {
			$data['message'] = lang('End Date can not be empty');
		}
		elseif (empty($_POST['end_time'])) {
			$data['message'] = lang('End time can not be empty');
		}
		elseif (empty($_POST['timezone'])) {
			$data['message'] = lang('Timezone can not be empty');
		}
		else{
			$data['message'] = lang('Please check the details');
		}
	}
}
if ($option == 'buy') {
	if (!empty($_POST['id']) && is_numeric($_POST['id']) && $_POST['id'] > 0) {
		$id = Secure($_POST['id']);
		$event = GetEventById($id);
		if (!empty($event)) {
			if ($event->ticket_price > 0) {
				if ($music->user->org_wallet >= $event->ticket_price && $event->available_tickets > 0) {
					
					$commission = 0;
					if (!empty($music->config->event_commission)) {
						$commission = round((($music->config->event_commission * $event->ticket_price) / 100), 2);
					}
					$db->where('id',$music->user->id)->update(T_USERS,array('wallet' => $db->dec($event->ticket_price)));
					$db->where('id',$event->user_id)->update(T_USERS,array('wallet' => $db->inc($event->ticket_price - $commission)));
					$db->where('id',$id)->update(T_EVENTS,array('available_tickets' => $db->dec(1)));
						
					$db->insert(T_PURCHAES,array('user_id' => $music->user->id,
	                                             'event_id' => $id,
	                                             'price' => $event->ticket_price,
	                                             'title' => $event->name,
	                                             'commission' => $commission,
	                                             'final_price' => $event->ticket_price - $commission,
	                                             'time' => time()));
					$create_activity = createActivity([
	                    'user_id' => $music->user->id,
	                    'type' => 'ticket_event',
	                    'event_id' => $id,
	                ]);
					$create_notification = createNotification([
                            'notifier_id' => $music->user->id,
                            'recipient_id' => $event->user_id,
                            'type' => 'bought_ticket',
                            'url' => $event->url
                        ]);
					$data['status'] = 200;
					$data['message'] = lang('payment successfully done');
				}
				else{
					if ($event->available_tickets < 1) {
						$data['message'] = lang('There is no available tickets');
					}
					elseif ($music->user->org_wallet < $event->ticket_price) {
						$data['message'] = lang("You don't have enough wallet")." <a href='".getLink("settings/".$music->user->username."/wallet")."'>".lang("Please top up your wallet")."</a>";
					}
				}
			}
			else{
				$data['message'] = lang('This event is free');
			}
		}
		else{
			$data['message'] = lang('Event not found');
		}
	}
	else{
		$data['message'] = lang('Please check the details');
	}
}
if ($option == 'join') {
	if (!empty($_POST['id']) && is_numeric($_POST['id']) && $_POST['id'] > 0 && !empty($_POST['type']) && in_array($_POST['type'], array('join','unjoin'))) {
		$id = Secure($_POST['id']);
		$event = GetEventById($id);
		$is_joined = $db->where('event_id',$id)->where('user_id',$music->user->id)->getValue(T_EVENTS_JOINED,'COUNT(*)');
		if (!empty($event)) {
			if ($is_joined > 0) {
				$db->where('event_id',$id)->where('user_id',$music->user->id)->delete(T_EVENTS_JOINED);
				deleteActivity([
				    'user_id' => $music->user->id,
					'type' => 'joined_event',
					'event_id' => $id,
				]);
			}
			else{
				$db->insert(T_EVENTS_JOINED,array('user_id' => $music->user->id,
				                                          'event_id' => $id,
				                                          'time' => time()));
				$create_activity = createActivity([
                    'user_id' => $music->user->id,
                    'type' => 'joined_event',
                    'event_id' => $id,
                ]);
				$create_notification = createNotification([
                            'notifier_id' => $music->user->id,
                            'recipient_id' => $event->user_id,
                            'type' => 'event_joined',
                            'url' => $event->url
                        ]);
			}
		}
			
		$data['status'] = 200;

	}
	else{
		$data['message'] = lang('Please check the details');
	}
}
// if ($option == 'join') {
// 	if (!empty($_POST['id']) && is_numeric($_POST['id']) && $_POST['id'] > 0 && !empty($_POST['type']) && in_array($_POST['type'], array('join','unjoin'))) {
// 		$id = Secure($_POST['id']);
// 		$event = GetEventById($id);
// 		$is_joined = $db->where('event_id',$id)->where('user_id',$music->user->id)->getValue(T_EVENTS_JOINED,'COUNT(*)');
// 		if (!empty($event)) {
// 			if ($is_joined > 0) {
// 				$db->where('event_id',$id)->where('user_id',$music->user->id)->delete(T_EVENTS_JOINED);
// 				if ($event->ticket_price > 0) {
// 					$db->where('event_id',$id)->where('user_id',$music->user->id)->delete(T_PURCHAES);
// 				}
// 			}
// 			else{
// 				if ($event->ticket_price > 0) {
// 					if ($music->user->org_wallet >= $event->ticket_price) {
// 						$db->where('id',$music->user->id)->update(T_USERS,array('wallet' => $db->dec($event->ticket_price)));
// 						$db->where('id',$id)->update(T_EVENTS,array('available_tickets' => $db->dec(1)));
// 						$db->insert(T_EVENTS_JOINED,array('user_id' => $music->user->id,
// 				                                          'event_id' => $id,
// 				                                          'time' => time()));
// 						$db->insert(T_PURCHAES,array('user_id' => $music->user->id,
// 		                                             'event_id' => $id,
// 		                                             'price' => $event->ticket_price,
// 		                                             'final_price' => $event->ticket_price,
// 		                                             'time' => time()));
// 					}
// 				}
// 				else{
// 					$db->insert(T_EVENTS_JOINED,array('user_id' => $music->user->id,
// 				                                          'event_id' => $id,
// 				                                          'time' => time()));
// 				}
					
// 			}
// 		}
			
// 		$data['status'] = 200;

// 	}
// 	else{
// 		$data['message'] = lang('Please check the details');
// 	}
// }
if ($option == 'delete') {
	if (!empty($_POST['id']) && is_numeric($_POST['id']) && $_POST['id'] > 0) {
		$id = secure($_POST['id']);

		$event = GetEventById($id);
		if (empty($event)) {
			$data['message'] = lang('Event not found');
		}
		elseif ($user->id != $event->user_data->id && !isAdmin()) {
			$data['message'] = lang('You are not the owner');
		}
		if (empty($data['message'])) {
			if (!empty($event->org_image)) {
				@unlink($event->org_image);
				PT_DeleteFromToS3($event->org_image);
			}
			if (!empty($event->org_video)) {
				@unlink($event->org_video);
				PT_DeleteFromToS3($event->org_video);
			}
			$db->where('event_id', $event->id)->delete(T_EVENTS_JOINED);
			$db->where('id', $event->id)->delete(T_EVENTS);
			deleteActivity([
				    'user_id' => $music->user->id,
					'type' => 'created_event',
					'event_id' => $event->id,
				]);
			$data['status'] = 200;
		}
	}
	else{
		$data['message'] = lang('Please check the details');
	}
}
if ($option == 'download') {
	if (!empty($_POST['id']) && is_numeric($_POST['id']) && $_POST['id'] > 0) {
		$id = secure($_POST['id']);
		$music->purchase = $db->where('id',$id)->where('user_id',$music->user->id)->getOne(T_PURCHAES);
		if (!empty($music->purchase)) {
			$music->event = GetEventById($music->purchase->event_id);
			if (!empty($music->event)) {
				$html = loadPage('pdf/ticket');
				$data['status'] = 200;
				$data['html'] = $html;
			}
			else{
				$data['message'] = lang('Event not found');
			}
		}
		else{
			$data['message'] = lang('You are not purchased');
		}
	}
	else{
		$data['message'] = lang('id can not be empty');
	}		
}
if ($option == 'edit_cover') {
	if (!empty($_FILES['image']) && !empty($_POST['event_id']) && is_numeric($_POST['event_id']) && $_POST['event_id'] > 0) {
		$event = GetEventById(secure($_POST['event_id']));
		if (!empty($event) && $event->user_id == $music->user->id) {
			$file_info = array(
		        'file' => $_FILES['image']['tmp_name'],
		        'size' => $_FILES['image']['size'],
		        'name' => $_FILES['image']['name'],
		        'type' => $_FILES['image']['type']
		    );
		    $file_upload = ShareFile($file_info);
		    if (empty($file_upload) || empty($file_upload['filename'])) {
		    	$data['message'] = lang('Event Cover can not be empty');
		    	header('Content-Type: application/json');
				echo json_encode($data);
				exit();
		    }
		    $db->where('id',$event->id)->update(T_EVENTS,array('image' => $file_upload['filename']));
		    $data['status'] = 200;
	        $data['img'] = getMedia($file_upload['filename']);
		}
		else{
			$data['message'] = lang('You are not the owner');
		}
	}
	else{
		$data['message'] = lang('Event Cover can not be empty');
	}
}