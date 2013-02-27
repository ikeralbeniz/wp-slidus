<?php
/*/
Plugin Name: Slid.us
Plugin URI: https://github.com/ikeralbeniz/wp-slidus
Description: Slid.us personal dashboard viewer. This plugin will allow you to add your slides on your WP page. 
Version: 1.0.1
Author: Iker Perez de Albeniz
Author URI: www.ikeralbeniz.com
/*/

define( 'SLIDUSPLUGINNAME_PATH', plugin_dir_path(__FILE__) );


function replace_style($text){

	$text = str_replace("<article", "<article style=\"margin: 1% 2%;z-index: 1;\"",$text);
	$text = str_replace("h1 class=\"text thin", "h1 style=\"font-size: 3.5em;font-weight: 200;padding: 0;border: 0;vertical-align: baseline;-webkit-margin-before: 1.67em;-webkit-margin-after: 1.67em;line-height: 1.25em font-family: \"Helvetica Neue\",Helvetica,Arial,sans-serif;", $text);
	$text = str_replace("h5 class=\"text thin", "h1 style=\"font-size: 1.2em;margin: 0 6%;font-weight: 200;box-sizing: border-box;padding: 0;border: 0;vertical-align: baseline;-webkit-margin-before: 1.67em;-webkit-margin-after: 1.67em;-webkit-margin-start: 0px;-webkit-margin-end: 0px;line-height: 1.25em ", $text);
	return $text;
}

function genarate_slides_list(){

	$result = "<div class=\"padding scroll hide thumbs\" style=\"display: block;\">";
	$json_data = json_decode(get_option('slidus_json_data'));
	$slidus_data = $json_data->{slidus};
	for($i=0; $i < count($slidus_data);$i++){

		$result .= "<a target=\"blanck_\" href=\"http://www.slid.us/".$slidus_data[$i]->{shortcut}."\" class=\"slidus_box\" style=\"".$slidus_data[$i]->{style}."display:block;width:margin:0 24px 24px 0;width:308px;height:212px;font-size:8px;position:relative;\">";
		$result .= replace_style($slidus_data[$i]->{thumb});
		$result .="<div style=\"z-index:1980;position:absolute;height:28px;line-height:28px;color:#fff;font-size:11px;background-color:rgba(0,0,0,0.7);padding-right:8px;width:auto;\">";
		$result .= "<span class=\"icon eye\"></span>";
		$result .= $slidus_data[$i]->{views}."</div>";
		$result .= "</a>";
	}
	$result .= "</div>";

	return $result ;
}

function append_slidus_iframe ($text){
	
	$iframe_text = 
	$text = str_replace("%slidus_frame%",genarate_slides_list(),$text);
	return $text;
}

function slidus_login($mail, $password){
	$url = 'http://www.slid.us/api/login';
	$post_data = "mail=".$mail."&password=".$password;

	// use key 'http' even if you send the request to https://...
	$options = array('http' => array(
		'method'  => 'POST',
		'content' => $post_data,
		'header' => "Accept-language: es\r\n" .
					"Content-Type: application/json\r\n".
					"Content-length: ".strlen($post_data)."\r\n"
		));
	$context  = stream_context_create($options);
	$result = file_get_contents($url, false, $context);

	return json_decode($result)->{'key'};
}

function slidus_data($session,$mail){
	$url = 'http://www.slid.us/api/user?nickname='.$mail;

	// use key 'http' even if you send the request to https://...
	$options = array('http' => array(
		'method'  => 'GET',
		'header' => "Accept-language: es\r\n" .
					"Content-Type: application/json\r\n".
					"Cookie: session=".$session."\r\n"
		));
	$context  = stream_context_create($options);
	$result = file_get_contents($url, false, $context);

	return $result;
}


function jsonToReadable($json){
    $tc = 0;        //tab count
    $r = '';        //result
    $q = false;     //quotes
    $t = "\t";      //tab
    $nl = "\n";     //new line

    for($i=0;$i<strlen($json);$i++){
        $c = $json[$i];
        if($c=='"' && $json[$i-1]!='\\') $q = !$q;
        if($q){
            $r .= $c;
            continue;
        }
        switch($c){
            case '{':
            case '[':
                $r .= $c . $nl . str_repeat($t, ++$tc);
                break;
            case '}':
            case ']':
                $r .= $nl . str_repeat($t, --$tc) . $c;
                break;
            case ',':
                $r .= $c;
                if($json[$i+1]!='{' && $json[$i+1]!='[') $r .= $nl . str_repeat($t, $tc);
                break;
            case ':':
                $r .= $c . ' ';
                break;
            default:
                $r .= $c;
        }
    }
    return $r;
}

function slidus_admin() {  
	?>
	<div class="wrap">  
	<?php    
	    echo "<h2>" . __( 'Slid.us Plugin Configuration', 'slidus_trdom' ) . "</h2>"; 
	    if($_POST['slidus_hidden'] == 'Y') {  

	        $slmail = $_POST['slidus_mail'];  
	        update_option('slidus_mail', $slmail);  

	        $slpasswrd = $_POST['slidus_passwrd'];  
	        update_option('slidus_passwrd', $slpasswrd); 
	        ?>
	        <div class="updated"><p><strong><?php _e('Options saved.' ); ?></strong></p></div>  
	        <?php  
	    }else if($_POST['slidus_hidden'] == 'J'){

	    	$slmail = get_option('slidus_mail'); 
	    	$slpasswrd = get_option('slidus_passwrd'); 

	    	$sljson_session = slidus_login($slmail,$slpasswrd);
	    	$sljson_data = slidus_data($sljson_session ,$slmail);
	    	
	    	update_option('slidus_json_data', $sljson_data); 

	    }
	    
	    $slmail = get_option('slidus_mail'); 
	    $slpasswrd = get_option('slidus_passwrd'); 
	    $sljson_data = get_option('slidus_json_data'); 
	    
	?>
    <form name="slidus_form" method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">  
        <input type="hidden" name="slidus_hidden" value="Y">  
        <p><?php _e("Registered e-mail address: " ); ?><input type="text" name="slidus_mail" value="<?php echo $slmail; ?>" size="20"><?php _e(" ex: user@mail.com" ); ?></p>
        <p><?php _e("Registered e-mail password: " ); ?><input type="password" name="slidus_passwrd" value="<?php echo $slpasswrd; ?>" size="20"></p>
        <p class="submit">  
        <input type="submit" name="Submit" value="<?php _e('Update Options', 'slidus_trdom' ) ?>" />  
        </p>  
    </form>  
    <hr>
    <?php
    echo "<h2>" . __( 'Slid.us Personal Data (JSON)', 'slidus_trdom' ) . "</h2>"; 
    ?>
    <form name="slidus_form" method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">  
        <input type="hidden" name="slidus_hidden" value="J">  
        <p class="submit">  
        <input type="submit" name="Submit" value="<?php _e('Update Data', 'slidus_trdom' ) ?>" />  
        </p>  
    </form>  
    <textarea style="border:2px dotted #999999;padding:15px; width:100%;height:200px;"><?php echo jsonToReadable($sljson_data); ?></textarea>
	</div>  
	<?php
}  

function slidus_admin_actions() {  
    add_options_page("Slid.us Plugin Configuration", "Slid.us Plugin Configuration", 'manage_options', "slidus-plugin-onfiguration", "slidus_admin");  
    //add_options_page('Slid.us Plugin Configuration', 'Slid.us Plugin Configuration', 'manage_options', 'options-general.php?page=wp-slidus/slidus_admin.php','');
}  

add_filter('the_content','append_slidus_iframe');
add_action('admin_menu', 'slidus_admin_actions',11); 