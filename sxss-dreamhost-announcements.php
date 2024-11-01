<?php
/*
Plugin Name: sxss Dreamhost Announcements
Plugin URI: http://sxss.info
Description: Plugin can send announcements (newsletter) with the Dreamhost (http://sxss.info/dreamhost) Announcement feature
Author: sxss
Version: 1.1
*/

/*
		recommendations
*/

// I18n
load_plugin_textdomain('sxss_da', false, basename( dirname( __FILE__ ) ) . '/languages' );

// Get Announcements Lists
function sxss_da_api($function, $optional = false)
{
	$api_key = get_option('sxss_da_apikey');;

	$result_format = "php";
	
	$api_url = "https://api.dreamhost.com/?key=$api_key&cmd=$function&format=$result_format";
	
	$result = file_get_contents($api_url);
	
	$result = unserialize($result);
		
	return $result;
}

// Send Mail Via API
function sxss_da_api_send_mail($listdata, $subject, $message)
{
	// Standard Return Value
	$return = true;
	
	foreach($listdata as $data)
	{
		$data = unserialize( base64_decode($data) );
		
		$listname = urlencode( $data["listname"] );
		$domain = urlencode( $data["domain"] );
		$subject = urlencode( $subject );
		$message = urlencode( nl2br( stripslashes( $message ) ) );
		$name = urlencode( $data["name"] );
		$charset = urlencode( "UTF-8" );
		$type = urlencode( "html" );
		
		/*
			Also available:
			stamp : the time to send the message, like 2009-05-28 10:40AM (optional)
			duplicate_ok : whether to allow duplicate messages to be sent, like 1 or 0 (optional)
		*/
		
		$api_key = get_option('sxss_da_apikey');

		$function = "announcement_list-post_announcement";
		
		$result_format = "php";
		
		$api_url = "https://api.dreamhost.com/?key=$api_key&cmd=$function&format=$result_format&listname=$listname&domain=$domain&subject=$subject&message=$message&name=$name&charset=$charset&type=$type";

		$result = file_get_contents($api_url);
		
		$result = unserialize($result);

		// Error Handling
		if( "error" == $result["result"] )
		{
			echo '
				<div id="message" class="updated fade">
					<p>
						<strong>' . __('API error while sending one or more announcements!', 'sxss_da') . '</strong><br />
						' . __('Reason', 'sxss_da') . ': ' . $result["data"] . '<br />
						' . __('Info', 'sxss_da') . ': ' . $result["reason"] . '<br />
					</p>
				</div>';
		}
		
		if( $return == true && $result["result"] == "error" ) $return = false;
	}

	return $return;
}

// Page
function sxss_da_page() 
{
	// Send Announcement
	if ( true == isset($_POST["sendannouncement"]) )
	{		
		$send = sxss_da_api_send_mail($_POST["sxss_da_list"], $_POST["sxss_da_subject"], $_POST["sxss_da_message"]);
		
		if( $send == true ) 
		{
			$message = '<div id="message" class="updated fade"><p><strong>' . __('Announcement sent successful!', 'sxss_da') . '</strong></p></div>';
		}
		// Error handling in sxss_da_api_send_mail()
	} 
	
	// Save API Key
	if ( true == isset($_POST["saveapikey"]) )
	{
		$apikeytemp = ereg_replace("[^0-9A-Z]", "", $_POST["sxss_da_apikey"]);
		
		if( ( strlen($_POST["sxss_da_apikey"]) == 16 && $apikeytemp == $_POST["sxss_da_apikey"]) || strlen($_POST["sxss_da_apikey"]) == 0 )
		{
			update_option('sxss_da_apikey', $_POST["sxss_da_apikey"]);
			
			$message = '<div id="message" class="updated fade"><p><strong>' . __('API Key saved!', 'sxss_da') . '</strong></p></div>'; 
		}
		else
		{
			$message = '<div id="message" class="updated fade"><p><strong>' . __('Please check your API Key!', 'sxss_da') . '</strong></p></div>';
		}
		
		
	} 

	// Get API Key From DB
	$apikey = get_option('sxss_da_apikey');
	
	if( $apikey != "" ) $editor = true;
	
	echo '
	
		<style type="text/css" media="screen">
					
			#wp-sxss_da_editor-wrap {  } 
			.wp-editor-area		{  }
			.sxss_da_html_code_button { color: gray; text-decoration: none; font-size: 90%; }
			.sxss_da_html_code { display: none; padding: 10px 0 10px 20px; color: gray; font-family: Courier New; }
			#sxss_da_links { display: none; float: right; margin: 20px; }
					
		</style>
		
		<script>
					
			jQuery(document).ready(function() {
							
				jQuery("a.sxss_da_html_code_button").click(function() {
							
					jQuery(this).parent().children("div .sxss_da_html_code").toggle(1000);

				});
							
			});
					
		</script>

		<div class="wrap">

			'.$message.'

			<div id="icon-options-general" class="icon32"><br /></div>

			<h2>' . __('Dreamhost Announcements', 'sxss_da') . '</h2>
			
			<div class="updated" id="sxss_da_links">
			
				<p>sxss recommends:</p>
				
				<a href="http://sxss.info/dreamhost/">image</a>
			
				<p align="center"><a href="http://sxss.info" target="_blank">www.sxss.info</a></p>
				
			</div>

			<form style="padding: 10px;" method="post" action="">
			
				<p>
					API Key: <input name="sxss_da_apikey" type="text" value="' . $apikey . '" maxlength="16" size="20"> <input name="saveapikey" type="submit" class="button-primary" value="' . __('Save API Key', 'sxss_da') . '" />
				</p>
			
			</form>

			';
			
			if( $apikey == true )
			{
			
				echo '
				
					<form method="post" action="">
					


					<input type="hidden" name="action" value="update" />
					

					
					<h3>' . __('Send a new announcement', 'sxss_da') . ':</h3>
				
				';
				
				$api_data = sxss_da_api('announcement_list-list_lists');
				
				if($api_data["result"] == "success")
				{
				
					$lists = $api_data["data"];

					echo '<table><tr  valign="top"><td width="140"><strong>' . __('Announcement List', 'sxss_da') . '</strong>: &nbsp; </td><td>';
					
					$i = 0;
					
					foreach($lists as $list)
					{
						$data = base64_encode( serialize($list) );
						
						$htmlcode = '
							<form method="post" action="http://scripts.dreamhost.com/add_list.cgi">
							<input type="hidden" name="list" value="' . $list["listname"] . '@' . $list["domain"] . '" /> 
							<input type="hidden" name="domain" value="' . $list["domain"] . '" /> 
							<input type="hidden" name="emailit" value="1" /> 
							<input name="email" value="' . __('your@adress.com', 'sxss_da') . '" /><br />
							<input type="submit" name="submit" value="' . __('Subscribe', 'sxss_da') . '" /> <input type="submit" name="unsub" value="' . __('Unsubscribe', 'sxss_da') . '" /> 
							</form>
						';
						
						echo '	<div style="margin-bottom: 10px;"><input name="sxss_da_list[]" type="checkbox" value="' . $data . '"> ' . $list["name"] . ' (' . $list["num_subscribers"] . ' ' . __('subscribers', 'sxss_da') . ') <a class="sxss_da_html_code_button" href="#">(HTML Code)</a><br />
								<div class="sxss_da_html_code" style="">
								'.htmlentities($htmlcode, ENT_QUOTES).'
								</div>
								</div>';
								
						$i++;
						
					}
					
					echo '</td></tr>';

					
				}
				
				echo '
				
					<tr><td><strong>' . __('Subject', 'sxss_da') . ':</strong> </td><td><input style="min-width: 350px;" type="text" name="sxss_da_subject" value=""></td></tr></table>
				
					
				';
				
			// Pre Defined Content, Field Name
			the_editor('','sxss_da_message');
					
			echo '

					<br style="clear: both;" /><input name="sendannouncement" type="submit" class="button-primary" value="' . __('Send announcement', 'sxss_da') . '" />

					</form>
					
			';
	}
	
	echo '

		</div>
	
		<br style="clear: both;" />

		<p align="right"><a target="_blank" title="sxss Plugins on wordpress.org" href="https://profiles.wordpress.org/sxss/"><img src="' . plugins_url( 'sxss-plugins.png' , __FILE__ ) . '"></a></p>
			
	';
}

// Register Settings Page
function sxss_da_admin_menu()
{  
	add_submenu_page('tools.php', __('sxss Dreamhost Announcements', 'sxss_da'), __('sxss Dreamhost Announcements', 'sxss_da'), 9, 'sxss_da', 'sxss_da_page');  
}  

add_action("admin_menu", "sxss_da_admin_menu"); 


?>