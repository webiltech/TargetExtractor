<?php

//CONSTANTS

const BUS_NAME_FIRST_STEP = 'busName: "';
const BUS_NAME_SECOND_STEP = '",';

const MORE_INFO_FIRST_STEP = '<div class="bcard-inner-content description-text">';
const MORE_INFO_SECOND_STEP = '</div>';

const EMAIL_FIRST_STEP = "var email = '";
const EMAIL_SECOND_STEP = "';";

const BUS_PHONE_FIRST_STEP = 'busPhone: "';
const BUS_PHONE_SECOND_STEP = '",';

const ADDRESS_FIRST_STEP = 'busAddress: "';
const ADDRESS_SECOND_STEP = '",';

const WEBSITE_FIRST_STEP = "var _web = '";
const WEBSITE_SECOND_STEP = "'";

const LANDING_FIRST_STEP = "var landingPage = '";
const LANDING_SECOND_STEP = "';";

//*************************************************************
//****** FUNCTIONS ********************************************
//*************************************************************

// function to get email
function get_email($html){
	$first_step = explode( EMAIL_FIRST_STEP , $html );
	$second_step = explode(EMAIL_SECOND_STEP , $first_step[1] );
	
	if(strlen($second_step[0])>0){
		return($second_step[0]);
	}

	//E-mail was not found, trying other method...
	else{
		$res = preg_match_all("/[a-z0-9]+[_a-z0-9.-]*[a-z0-9]+@[a-z0-9-]+(.[a-z0-9-]+)*(.[a-z]{2,4})/i",$html,$matches);
	
		if ($res) {
			foreach(array_unique($matches[0]) as $email) {
				return($email);
			}
		}
		return null;
	}
}
// business name
function get_bus_name($html){
	$first_step = explode( BUS_NAME_FIRST_STEP , $html );
	$second_step = explode(BUS_NAME_SECOND_STEP , $first_step[1] );
	return($second_step[0]);
}
// business more-info
function get_info($html) {
	$first_step = explode( MORE_INFO_FIRST_STEP , $html );
	$second_step = explode(MORE_INFO_SECOND_STEP , $first_step[1] );
	
	return($second_step[0]);
}
// business-phone
function get_phone($html){	
	$first_step = explode( BUS_PHONE_FIRST_STEP , $html );	
	$second_step = explode(BUS_PHONE_SECOND_STEP , $first_step[1] );
	return($second_step[0]);
}
// business-address
function get_addr($html){
	$first_step = explode( ADDRESS_FIRST_STEP , $html );
	$second_step = explode(ADDRESS_SECOND_STEP , $first_step[1] );
	return($second_step[0]);
}

function get_website($html){
	$first_step = explode( WEBSITE_FIRST_STEP , $html );
	$second_step = explode(WEBSITE_SECOND_STEP , $first_step[1] );
	
	return(urlToDomain($second_step[0]));	
}

function get_landing($html){
	$first_step = explode( LANDING_FIRST_STEP , $html );
	$second_step = explode( LANDING_SECOND_STEP , $first_step[1] );
	return($second_step[0]);	
}

function urlToDomain($url) {
	//This function use to trim the urls to "example.com" insted of "http://...."
   return implode(array_slice(explode('/', preg_replace('/https?:\/\/(www\.)?/', '', $url)), 0, 1));
}

function extract_targets($file_name,$type,$EmailsOnly,$WebsitesOnly){
	$leads_collected=0;
	$emails_collected=0;
	$addr_collected=0;
	$phones_collected=0;
	$info_collected=0;
	$website_collected=0;
	$landing_collected=0;
	$email_format_failures=0;
	$email_varification_failures=0;
	$url_validation_failures=0;
	$n_of_lines=0;
	$n_of_lines_saved=0;
	
	$url_errors=0;
	
	echo '<div class="p-3 mb-2 bg-warning text-white">';
		
	if (($handle = fopen("urls/" . $file_name, "r")) !== FALSE) {
		
		$fp = fopen("csv/" .$file_name ,"w+");
		if(!$fp){
			echo '<div class="h1 bg-info text-white">';
			echo "<strong>Can't open the file</strong>";
			echo '</div>';
			exit(1);
		}
			
		//adding first row(column headings)
		fputcsv($fp, array('NAME','ACCOUNT NAME','EMAIL TPL NAME','PRIMARY ADDRESS STREET','OFFICE PHONE','EMAIL ADDRESS','WEBSITE','BZQ LANDING','DIRECTORY','DIRECTORY URL','DESCRIPTION','BUSINESS TYPE'));
		
		//E-mail varification
		require_once("EmailVerify.class.php");

				
		while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
			$num = count($data);
			
				
			for ($c=0; $c < $num; $c++) {
				$url = $data[$c];
				$n_of_lines+=1;
				$html = file_get_contents($url);
				
				if(!$html){
					$url_errors+=1;
					continue;
				}
				
				$email = get_email($html);
				
				if($EmailsOnly && strlen($email)<1) continue;  
	
				$verify = new EmailVerify();
				
				$ver_formatting = $verify->verify_formatting($email);
				$ver_domain = $verify->verify_domain($email);
							
				if((!$ver_formatting) || (!$ver_domain)){
					$email_varification_failures+=1;
					if($EmailsOnly) {
						//user selected to retrieve businesses with valid e-mails only 
						//so we skip this target
						continue;
					}
					else{
						/*
						email is bad so it needs to be noted somehow.
						At the moment i just leave it empty so we will not accidentally 
						try and send an e-mail to this address
						*/
						
						$email = '';			
					} 

				}
				
				$lead = trim(get_bus_name($html));		
				$account_name = $lead;
				$email_tpl_name = $lead;
				 
				$more_info = trim(get_info($html));				
				$more_info = trim(str_replace(array('<br>', '</br>','<br/>'), '', $more_info));				
				$phone = trim(get_phone($html));
				$address = trim(get_addr($html));
				
				//retrieving website and Remove all illegal characters from a url
				$website = filter_var(trim(get_website($html)), FILTER_SANITIZE_URL);
				
				//retrieving landing page and Remove all illegal characters from a url
				$landing = filter_var(trim(get_landing($html)), FILTER_SANITIZE_URL);
				
				/*
				if ($WebsitesOnly) {
					//user selected to retrieve only target with websites
					//if no website we skip this target
					if ( (filter_var($website, FILTER_VALIDATE_URL) == FALSE) ) continue;
				}
				*/

				if($WebsitesOnly && strlen($website)<1) continue;
				  				
				$directory = 'בזק עסקים';
				
				//making sure the *email & url* fields will be saved in lower case
				//so we will not get something like this: "WWW.example.com","http://WWW.example.com"
				$email = strtolower($email);
				$website = strtolower($website);
				$landing = strtolower($landing);
				
				fputcsv($fp, array($lead, $account_name, $email_tpl_name, $address, $phone, $email, $website, $landing, $directory, $url, $more_info, $type));
				
				$n_of_lines_saved+=1;
				
				if($lead)$leads_collected+=1;
				if($email)$emails_collected+=1;
				if($phone)$phones_collected+=1;
				if($website)$website_collected+=1;
				if($landing)$landing_collected+=1;
				if($address)$addr_collected+=1;
				if($more_info)$info_collected+=1;
			}
		}

		fclose($fp);
		fclose($handle);

		echo '<div class="h1 bg-info text-white">';
		echo '<i class="fa fa-check"></i> <strong>Extraction Complete</strong>';
		echo '</div>';
		
		echo '<div class="p-3 mb-2 bg-warning text-white">';
		echo "<p><u>Here are the results:</u></p>" ;
		echo "<p>FILE NAME: " . $file_name ."</p>" ;
		echo "<p>" . $n_of_lines . " Lines read from file</p>" ;
		echo "<p>" . $n_of_lines_saved . " Saved to a new CSV file</p>" ;
		echo "<p>Business Names: " . $leads_collected ." Extracted</p>" ;
		echo "<p>Business E-mail Addresses: " . $emails_collected ." Extracted</p>" ;
		echo "<p>BAD E-mails found: " . $email_varification_failures ."</p>" ;
		echo "<p>" . $url_validation_failures ." bad URL's found!</p>";
		echo "<p>Business Websites: " . $website_collected ." Extracted</p>" ;
		echo "<p>Bezeq Landing Page: " . $landing_collected ." Extracted</p>" ;
		echo "<p>Business REAL Addresses: " . $addr_collected ." Extracted</p>" ;
		echo "<p>Business Phone Numbers: " . $phones_collected ." Extracted</p>" ;
		echo "<p>Business Info: " . $info_collected ." Extracted</p>" ;
		echo "<p>URL Errors: " . $url_errors ."</p>" ;
		echo '</div>';

		echo '<form method="post" enctype="multipart/form-data">';	
		echo '<button class="btn btn-info" type="submit" id="next_file" name="next_file" value="next_file"> Next File</button>';
		
        echo '</form>';
        
        //writing info to csv for automation!
        $csv = fopen("urls/last.csv","w+");
		if(!$csv){
			echo '<div class="h1 bg-info text-white">';
			echo "<strong>Can't open the file</strong>";
			echo '</div>';
			exit(1);
		}
			
		//adding 1 row
		fputcsv($csv, array($file_name,$type,$EmailsOnly,$WebsitesOnly));
        fclose($csv);
	}
	else{
		return(0);
	}
	
}


//*************************************************************
//****** END FUNCTIONS ****************************************
//*************************************************************


if( isset( $_POST['next_file'] ) ){    
    if (($handle = fopen("urls/last.csv", "r")) !== FALSE) {
      $data = fgetcsv($handle, 1000, ",");
		$last_file = $data[0];
		$type = $data[1];
		$EmailsOnly = $data[2];
		$WebsitesOnly = $data[3];
		$last_index = strval(preg_replace("/[^0-9]/", '', $last_file));
		$next_index = strval((int)$last_index+1);
		$str = str_replace($last_index,$next_index,$last_file);
    }
    fclose($handle);
    extract_targets($str,$type,$EmailsOnly,$WebsitesOnly);
}
    

if( isset( $_POST['submit'] ) ){		
	if (isset($_FILES['file']) && $_FILES['file']['error'] == UPLOAD_ERR_OK) {
		$file_name = $_FILES['file']['name'];
	
	$type = $_POST['type'];
	$EmailsOnly = $_POST["chkEmailsOnly"]; 
	$WebsitesOnly = $_POST["chkWebsitesOnly"];
	extract_targets($file_name,$type,$EmailsOnly,$WebsitesOnly);
	}
}

?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
<title></title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" >
<!-- Favicons -->
<link href="https://www.webil.tech/img/favicon.png" rel="icon">

<!--<script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>-->
<link href="/img/favicon.png" rel="icon">
<link href="https://fonts.googleapis.com/css?family=Amatic+SC&display=swap" rel="stylesheet">
<!-- BOOTSTRAP -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
  
<script>
$(function(){
	$("#js_verify").click(function(){
		$.getJSON("EmailVerify.class.php", { "address_to_verify" : $("#email").val() }, function(data){
			alert(data.message);
			return false;
		});
		return false;
	});
});
</script>

<script>
if ( window.history.replaceState ) {
	window.history.replaceState( null, null, window.location.href );
}
</script>
    
</head>
<body>
<div id="doc" class="yui-t7">
	<img src="https://webil.tech/img/logo.png" alt="" class="img-fluid">	
	<div id="hd">
		<div id="header">
			<div class="alert alert-info">
				<p class="lead">Extract Targets</p>
			</div>
		</div>
	</div>
	
	<div id="bd">
		<div id="yui-main">
			<form method="post" enctype="multipart/form-data">				
				<div class="alert alert-info">
					<label for="file">Select CSV/TXT file</label>
					<input type="file" name="file" id="file" class="btn btn-info" accept=".txt, .csv">
					<br></br>
					<label for="type">BUSINESS TYPE</label>
			    	<input type="text" name="type" id="type" value=""/><br>
			    	<label for="chkEmailsOnly">Exclude businesses without a verified E-mail</label><br>
  					<input type="checkbox" id="chkEmailsOnly" name="chkEmailsOnly"><br>
					<label for="chkWebsitesOnly">Exclude businesses without a website/landing</label><br>
  					<input type="checkbox" id="chkWebsitesOnly" name="chkWebsitesOnly"><br>
					<button class="btn btn-info" type="submit" id="submit" name="submit" value="Extract Targets"> <span class="glyphicon glyphicon-hdd"></span> Extract Targets</button>
				</div>
			</form>				
		</div>
	</div>
</div>

</body>
</html>
