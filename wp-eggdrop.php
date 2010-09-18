<?php
/*
Plugin Name: WP Eggdrop
Plugin URI: http://www.backie.net
Version: 0.1
Description: Sends data to a eggdrop bot that annouces into IRC.
Author: Iain Cambridge
Author URI: http://codeninja.me.uk/?ref=wp-eggdrop
*/




function wpegg_scriptInstall(){
    
    // Put in empty options
    add_option("wpegg_server", "",NULL,"no");
    add_option("wpegg_port","",NULL,"no");
    add_option("wpegg_password","",NULL,"no");
    add_option("wpegg_message","",NULL,"no");
        
}

register_activation_hook(__FILE__, 'wpegg_scriptInstall');


function wpegg_handlePost($PostID){
    
    /*
     * %A == Author Display Name
     * %a == Author Full Name 
     * %d == Post Date
     * %D == Post Date GMT
     * %C == All catergories
     * %c == 1st catergory.
     * %T == Post's Title
     * %t == Post's slug (url friendly title)
     * %U == Permalink URL
     */
    
    $Post = get_post($PostID);
    $Bot = wpegg_getOptions();
    $Message = $Bot['Message'];        
    $Permalink = get_permalink($PostID);
    // Get author details.
    if ((strstr($Message,"%A")) || (strstr($Message,"%a")) ){
    
        $User = get_userdata($Post->post_author);
            
        $Message = str_replace("%a","{$User->first_name} {$User->last_name}",$Message);
        $Message = str_replace("%A",$User->display_name,$Message);
            
    }
        
        // Get catergory name.
    if (strstr($Message,"%C")){
            
        foreach((get_the_category($PostID)) as $category) { 
            $Cat .= $category->cat_name . ' '; 
        } 
            
        $Message =  str_replace("%C",$Cat,$Message);
            
    }
        
    if (strstr($Message,"%c")){
            
        $Catergories = get_the_category($PostID);
            
        $Message = str_replace("%c",$Catergories[0]->category_nicename,$Message);
            
    }
        
       
    $Message = str_replace("%d",$Post->post_date,$Message);
    $Message = str_replace("%D",$Post->post_date_gmt,$Message);
    $Message = str_replace("%t",$Post->post_name,$Message);
    $Message = str_replace("%T",$Post->post_title,$Message);
    $Message = str_replace("%U",$Permalink,$Message);
        
        
    wpegg_sendMessage($Bot['Server'],$Bot['Port'],$Bot['Password'],$Message);
    
  
    
}


// Send the message

function wpegg_sendMessage($BotServer,$BotPort,$BotPassword,$BotMessage){
    
    $fp = fsockopen($BotServer, $BotPort, $errno, $errstr, 40);
    
    if($fp)
    {
        if ( !fputs($fp, $BotPassword . " " . $BotMessage . "\r\n") ){
        	trigger_error('Unable to sent message, check php settings');
        }
    }
    
    fclose($fp);
}

add_action('publish_post', 'wpegg_handlePost');

//
// Get the options.
// 

function wpegg_getOptions(){

        $Bot['Server']   = get_option("wpegg_server");
        $Bot['Port']     = get_option("wpegg_port");
        $Bot['Password'] = get_option("wpegg_password");
        $Bot['Message']  = get_option("wpegg_message"); 
        return $Bot;
}

//
// Show Options Page!
//

function wpegg_showOptions(){
    
    ?>
    <div class="wrap">
  	    <div id="icon-options-general" class="icon32"><br /></div>    
        <h2>WP Eggdrop</h2>
        
    <?php 
        
    if (isset($_POST['wpegg_submit'])){
        
        
        // Data been submitted.
        $Errors = wpegg_updateOptions(); // send to be validated and updated.
        
        // Set vars
        $Bot['Server']   = $_POST['wpegg_server']; 
        $Bot['Port']     = $_POST['wpegg_port'];
        $Bot['Password'] = $_POST['wpegg_password'];
        $Bot['Message']  = $_POST['wpegg_message']; 
        
        if (!empty($Errors)){ // Display errors.
        ?>
        <p>
            <ol style="list-style-type: decimal;">
                <?php foreach ($Errors as $Error){?>
                <li style="color: red;font-weight: bold;"><?php echo $Error; ?></li>
                <?php } ?>
            </ol>
        </p>
        <?php 
        }
        else { // Display updated notice!
            ?><div id="message" class="updated fade"><p>Options updated!</p></div>
            <?php 
        }
    }
    else{
        // Get bot options from SQL
        $Bot = wpegg_getOptions();
    }
    ?>
        <form method='post' action='?<?php echo $_SERVER['QUERY_STRING']?>'>
        
        <table class="form-table">
        
            <tr valign="top">
                <th scope="row">Bot Server/IP</th>
                <td><input type="text" name="wpegg_server" value="<?php echo $Bot['Server']; ?>" /></td>
            </tr>
            
            <tr valign="top">
                <th scope="row">Bot Port</th>
                <td><input type="text" name="wpegg_port" value="<?php echo $Bot['Port']; ?>" /></td>
            </tr>
            
            <tr valign="top">
                <th scope="row">Bot Password</th>
                <td><input type="text" name="wpegg_password" value="<?php echo $Bot['Password']; ?>" /></td>
            </tr>
            
            <tr valign="top">
                <th scope="row">Bot Message</th>
                <td><input type="text" name="wpegg_message" value="<?php echo $Bot['Message']; ?>" /></td>
            </tr>
        
        </table>

        <p class='submit'>
                <input type="submit" name="wpegg_submit" value="Update Options &raquo;" />
            </p>
            
           </form>
           
           <p>
               <ol>Message Formatting
                   <li>%A - Author's display name</li>
                   <li>%a - Author's full name</li>
                   <li>$d - Post Date</li>
                   <li>%D - Post Date GMT</li>
                   <li>%C - All catergories seperated by a space</li>
                   <li>%c - The first catergory</li>
                   <li>%T - Post Title</li>
                   <li>%t - Post slug (url friendly title)</li>
                   <li>%U - Post permalink (web address)</li>
               </ol>
           </p>
       </div>
    <?php 
}

function wpegg_updateOptions(){
    
    $Errors = array();
    
    // Validate the information.
    if(empty($_POST['wpegg_server'])){
        $Errors[] = "Server/IP cannot be blank.";
    }
    
    if ($_POST['wpegg_port'] == ""){
        $Errors[] = "Port cannot be blank.";
    }
    elseif (!is_numeric($_POST['wpegg_port'])){
        $Errors[] = "Port has to be a number";
    }
    
    if (empty($_POST['wpegg_password'])){
        $Errors[] = "Password cannot be blank";
    }
    
    if (empty($_POST['wpegg_message'])){
        $Errors[] = "Password cannot be blank";
    }
    
    if (empty($Errors)){
        update_option("wpegg_server",$_POST['wpegg_server']);
        update_option("wpegg_port",$_POST['wpegg_port']);
        update_option("wpegg_password",$_POST['wpegg_password']); 
        update_option("wpegg_message",$_POST['wpegg_message']);        
    }
    
    return $Errors;
}

// Add to menu.

function wpegg_addMenu(){
    add_options_page('WP Eggdrop Settings', 'WP Eggdrop Settings', 'manage_options', 'wpegg', 'wpegg_showOptions');
}

add_filter('admin_menu','wpegg_addMenu');

?>
