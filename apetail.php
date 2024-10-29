<?php
/*
Plugin Name: ApeTail
Plugin URI: https://apetail.chat
Description: Public chats under posts, channels, direct chats. Stream with filters, direct replies for branching and structurized messaging. Talk with ChatGPT. Customizable questions-answers AI Agent.  Advanced communications for a web project.
Version: 2.0.0
Author: Olexiy Ayahov
License: GPLv2 or later
Text Domain: apetail
*/
if(!defined('ABSPATH')){
    die;
}

class ApeTail {
    var $defaulButtonSettingsObject = 
"
{
    
    //main_admin: 'nickname_of_main_admin',
    //chat_name: 'Name of chat',

    trigger: {
        expanded: true,
        location: 'bottom-right',
        css: {

        }
    }

}
";
    var $defaultUnderPostsObject = 
"
{
    //chat_name: 'Article discussion',
    //main_admin: 'nickname_of_main_admin'
}";

    function __construct(){
        add_action('wp_enqueue_scripts',[$this,'enqueue_wp']);
        add_action('admin_menu', [$this,'add_admin_menu']);
        add_filter('plugin_action_links_'.plugin_basename(__FILE__), [$this,'plugin_setting_link']);
        add_action('admin_init',[$this,'settings_init']);
        add_action('admin_head', [$this,'admin_style']);
        add_action('wp_head',[$this,'apetail_js_object']);         
        add_filter('the_content',[$this,'add_apetail_under_post']);
        add_filter('widgets_init', [$this,'unregister_recent_comments_widget']);
        add_filter('widget_block_content', [$this,'remove_recent_comment_from_widget_block'], 10, 3 );                       
    }
    function unregister_recent_comments_widget(){
        unregister_widget('WP_Widget_Recent_Comments');     
    }
    function remove_recent_comment_from_widget_block( $content, $instance, $widget ){
    	return strpos($content,'wp-block-latest-comments')?null:$content;
    }      
    function admin_style(){
        echo 
"<style>
    #button_settings_object{min-width:350px;min-height:300px;}
    #under_posts_object{min-width:350px;min-height:150px;}
    .apetail-notice{background: #fff;border: 1px solid #c3c4c7;border-left-width: 4px;box-shadow: 0 1px 1px rgba(0,0,0,.04);margin:0;padding: 1px 12px;}
    .apetail-notice.warning{border-left-color: #dba617;}
    .apetail-notice.info{border-left-color: #72aee6;}
    a.big{display:block;margin:20px;font-size:170%;margin-left:0;}            
</style>";
    }
    
            
    function apetail_js_object(){
        $options = get_option('apetail_settings_options');
        $checked = 'checked';
        if(!isset($options['show_button'])&&!empty($options))$checked='';
        if(!$checked)return; 
        $hostName = $this->get_host_name($options);
        $hostName = str_replace(["'","\\"],["&quot;","\\\\"],$hostName);
        $buttonSettingsObject = @$options['button_settings_object'];
        if(!$buttonSettingsObject) $buttonSettingsObject = $this->defaulButtonSettingsObject;       
        $pos = strpos($buttonSettingsObject, '{');
        if($pos !== false) {
            $buttonSettingsObject = substr_replace($buttonSettingsObject, "{host_name:'$hostName',", $pos, 1);
        }        
        echo "<script>var ApeTail = $buttonSettingsObject </script>";        
    }
    function enqueue_wp(){
        wp_enqueue_script('ApeTailWpScript', 'https://apetail.chat/init');
    }
    function add_admin_menu(){
        add_menu_page(
            esc_html__('ApeTail Settings Page', 'apetail'),
            esc_html__('ApeTail','apetail'),
            'manage_options',
            'apetail_settings',
            [$this, 'apetail_admin_page'],
            'dashicons-admin-comments',
            100
        );
    }
    function apetail_admin_page(){
        require_once plugin_dir_path(__FILE__).'admin/admin-page.php';
    }
    function plugin_setting_link($links){
        $custom_link = '<a href="admin.php?page=apetail_settings">'.esc_html__('Settings','apetail').'</a>';
        array_push($links, $custom_link);
        return $links;        
    }
    function settings_init(){
        register_setting('apetail_settings','apetail_settings_options');
        add_settings_section('settings_section', __('','apetail'), [$this, 'settings_section_html'], 'apetail_settings');
        add_settings_field('hostname', esc_html__('Host name','apetail'), [$this, 'hostname_html'], 'apetail_settings', 'settings_section');
        add_settings_field('show_button', esc_html__('Show trigger button','apetail'), [$this, 'show_button_html'], 'apetail_settings', 'settings_section');
        add_settings_field('button_settings_object', esc_html__('Trigger button settings JavaScript object','apetail'), [$this, 'button_settings_object_html'], 'apetail_settings', 'settings_section');
        add_settings_field('put_chat_rooms_under_posts', esc_html__('Put chat rooms under posts instead default comments','apetail'), [$this, 'put_chat_rooms_under_posts_html'], 'apetail_settings', 'settings_section');
        add_settings_field('under_posts_object', esc_html__('Chat room under posts JavaScript object','apetail'), [$this, 'button_js_html'], 'apetail_settings', 'settings_section');    
    }
    
    function settings_section_html(){
        if(!get_option('apetail_settings_options')){
            $tip = esc_html__( "After activation, to become the main admin of the host, sign in/sign up on ApeTail (not as guest) with nickname set for main_admin key.", 'apetail' );            
            echo "<div class='apetail-notice warning'>
                <p>$tip</p>
            </div>";
        }
        echo "<div class='apetail-notice info'>
            <p>";
        _e("<font color='grey'><b>General rules for a JavaScript object</b><br> 
            1) It wrapped in curly braces <b>{}</b><br> 
            2) It consists from key:value pairs<br> 
            3) key:value pairs separated by comma<br> 
            4) Value is taken in quotes if it is a string type<br> 
            5) Value can be another JavaScript object<br> 
            6) Single-string key:value pair can be temporary disabled by \"commenting\" with double slashes <b>//</b> on the single-string begining<br> 
            7) Single quote <b>'</b> and separete slash <b>\</b> in string value should be escaped with slash \' and \\\\ respectivelly</font><br>", 'apetail');
        echo "</p>
        </div>";                    
    }
    
    function hostname_html(){
        $options = get_option('apetail_settings_options'); #print_r($options);
        $hostName = @$options['hostname'];
        if(!$hostName) {
            $parts = explode('.',$this->get_domain($_SERVER['SERVER_NAME']));
            $hostName = ucfirst($parts[0]);
        }   
        $value = esc_attr($hostName);
        echo " <input type='text' maxlength=32 name='apetail_settings_options[hostname]' value='$value' />"; 
    }
    
    function show_button_html(){
        $options = get_option('apetail_settings_options');        
        $checked = 'checked';
        if(!isset($options['show_button'])&&!empty($options)) $checked=''; 
        $checked = esc_attr($checked);
        echo "<input type='checkbox' name='apetail_settings_options[show_button]' $checked />";
    }
    
    function button_settings_object_html(){
        $options = get_option('apetail_settings_options'); 
        $button_settings_object = esc_textarea(isset($options['button_settings_object']) ? $options['button_settings_object'] : $this->defaulButtonSettingsObject);            
        echo "<textarea id='button_settings_object' name='apetail_settings_options[button_settings_object]'>
                    $button_settings_object
                </textarea>
                <div class='apetail-notice info'>
                    ";
        _e( "<p><b>main_admin</b> Nickname of the main admin, which can create custom chats, channels, AI Agents, ban users and delete their messages.  Once main admin is set, this parameter can be omitted.</p>
            <p><b>chat_name</b> (optional) Name of the custom chat (new or existed) which will be opened. If not set, Lobby (general) chat will be opened.</p>
            <p><b>channel_name</b> (optional) Name of the custom channel (new or existed) which will be opened.</p>
            <p><b>direct_chat</b> (optional) Nickname of the user with whom direct chat will be opened.</p>
            <p><b>ai_agent</b> Nickname of AI Agent chat with which will be opened. AI Agents let user to have question-answers chats to know more about your project. Check <a href='https://apetail.chat' target='_blank'>ApeTail web site</a> to know more.</p>            
            <p><b>trigger { expanded }</b> (optional, true/false) Form of the trigger button. If expanded:true , details of the chat are displayed on the button.</p>
            <p><b>trigger { location }</b> (optional) Where on the page trigger button is fixed. Available values: <i>'bottom-left'</i> (by default), <i>'bottom-right'</i>, <i>'left'</i>, <i>'right'</i></p>
            <p><b>trigger { prefix }</b> (optional) Left-padded word/phrase to chat/channel caption name.</p>            
            <p><b>trigger { css }</b> (optional) CSS styles applied on the trigger button. If not used, default style is applied. The precise position on the page may be changed with <i>top</i>,<i>right</i>,<i>bottom</i> and <i>left</i> keys with string values like <i>'10px'</i>, where <i>px</i> means pixels</p>", 'apetail' );
        echo "
            </div>";
    }
    
    function put_chat_rooms_under_posts_html(){
        $options = get_option('apetail_settings_options');        
        $checked = 'checked';
        if(!isset($options['put_chat_rooms_under_posts'])&&!empty($options)) $checked='';  
        $checked = esc_attr($checked);
        echo "<input type='checkbox' name='apetail_settings_options[put_chat_rooms_under_posts]' $checked />";
    }
    
    function button_js_html(){
        $options = get_option('apetail_settings_options');
        $options = esc_textarea(isset($options['under_posts_object']) ? $options['under_posts_object'] : $this->defaultUnderPostsObject);
        echo "<textarea id='under_posts_object' name='apetail_settings_options[under_posts_object]'>
                    $options
                </textarea>
                <div class='apetail-notice info'>";
        _e("<p><b>chat_name</b> (optional) Name of the custom chat (new or existed) which will be opened (if not set, web page title will be used as name).</p>
            <p><b>main_admin</b> Nickname of the main admin, which can create custom chats, channels, AI Agents, ban users and delete their messages. Once main admin is set, this parameter can be omitted.</p>
            <p><b>rating</b> Open ability to rate (like) messages. Messages are sorted by most rated on top.</p>", 'apetail');
        echo "
            </div>";
    }
    
    function add_apetail_under_post($content){
        global $post;
        $options = get_option('apetail_settings_options');
        $checked = 'checked';
        if(!isset($options['put_chat_rooms_under_posts'])&&!empty($options))$checked='';
        if(!$checked)return;
        
        add_filter('comments_open', '__return_false', 20, 2);
        add_filter('pings_open', '__return_false', 20, 2);
        add_filter('comments_array', '__return_empty_array', 10, 2);
        $containerId = 'apetail-container'; 
        $content.="<div id='$containerId'></div>";
        
        $hostName = $this->get_host_name($options);
        if(isset($options['under_posts_object']))$underPostsObject = $options['under_posts_object'];
        else $underPostsObject = $this->defaultUnderPostsObject;
        $hostName = str_replace(["'","\\"],["&quot;","\\\\"],$hostName);
        $chatName = str_replace(["'","\\"],["&quot;","\\\\"],$post->post_title);
        $pos = strpos($underPostsObject, '{');
        if ($pos !== false) {
            $underPostsObject = substr_replace($underPostsObject, "{host_name:'$hostName',chat_name:'$chatName',container_id:'$containerId',", $pos, 1);
        } 
        $content.= "<script>var ApeTail1 = $underPostsObject</script>";                   
        return $content;
    }    
    function get_host_name($options){
        $hostName = @$options['hostname'];
        if(!$hostName) {
            $parts = explode('.',$this->get_domain($_SERVER['SERVER_NAME']));
            $hostName = ucfirst($parts[0]);
        }
        return $hostName;        
    }    
    function get_domain($url){
        $domain = strpos($url,'/')? parse_url($url, PHP_URL_HOST):$url;
        $ccTLD = json_decode( file_get_contents(plugin_dir_url( __FILE__).'admin/ccTLD.json'), 1 );
        $parts = explode('.',$domain);
        if(count($parts)>3) $parts = array_slice($parts, -3);
        if(count($parts)==3&&!in_array($parts[2],$ccTLD)) $parts = array_slice($parts, -2);
        return implode('.',$parts); 
    }                        
}
new ApeTail();