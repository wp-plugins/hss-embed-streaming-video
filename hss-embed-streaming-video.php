<?php
/*
Plugin Name: HSS Embed Streaming Video
Plugin URI: https://www.hoststreamsell.com
Description: Provide access to Streaming Video in your WordPress Website
Author: Gavin Byrne
Author URI: https://www.hoststreamsell.com
Contributors:
Version: 0.1

HSS Embed Streaming Video is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.

HSS Embed Streaming Video is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with HSS Embed Streaming Video. If not, see <http://www.gnu.org/licenses/>.
*/


// create shortcode with parameters so that the user can define what's queried - default is to list all blog posts



register_activation_hook(__FILE__, 'hss_embed_add_defaults');
register_uninstall_hook(__FILE__, 'hss_embed_delete_plugin_options');
add_action('admin_init', 'hss_embed_init' );

function hss_embed_add_defaults() {
        $tmp = get_option('hss_embed_options');
    if(($tmp['chk_default_options_db']=='1')||(!is_array($tmp))) {
                delete_option('hss_embed_options'); // so we don't have to reset all the 'off' checkboxes too! (don't think this is needed but leave for now)
                $arr = array(   "api_key" => "",
                );
                update_option('hss_embed_options', $arr);
        }
}

function hss_embed_delete_plugin_options() {
        delete_option('hss_embed_options');
}

function hss_embed_init(){
        register_setting( 'hss_embed_plugin_options', 'hss_embed_options', 'hss_embed_validate_options' );
}

function hss_embed_validate_options($input) {
         // strip html from textboxes
        $input['api_key'] =  wp_filter_nohtml_kses($input['api_key']); // Sanitize textarea input (strip html tags, and escape characters)
        return $input;
}

function hss_embed_options_page () {
?>
        <div class="wrap">

                <!-- Display Plugin Icon, Header, and Description -->
                <div class="icon32" id="icon-options-general"><br></div>
                <h2>HostStreamSell Video Embed Plugin Settings</h2>
                <p>Please enter the settings below...</p>

                <!-- Beginning of the Plugin Options Form -->
                <form method="post" action="options.php">
                        <?php settings_fields('hss_embed_plugin_options'); ?>
                        <?php $options = get_option('hss_embed_options'); ?>

                        <!-- Table Structure Containing Form Controls -->
                        <!-- Each Plugin Option Defined on a New Table Row -->
                        <table class="form-table">

                                <!-- Textbox Control -->
                                <tr>
                                        <th scope="row">HostStreamSell API Key<BR><i>(available from your account on www.hoststreamsell.com)</i></th>
                                        <td>
                                                <input type="text" size="40" name="hss_embed_options[api_key]" value="<?php echo $options['api_key']; ?>" />
                                        </td>
                                </tr>
                                <tr>
                                        <th scope="row">Video Player Size<BR><i>(leave blank to use defaults)</i></th>
                                        <td>
                                                Width <input type="text" size="10" name="hss_embed_options[player_width_default]" value="<?php echo $options['player_width_default']; ?>" /> Height  <input type="text" size="10" name="hss_embed_options[player_height_default]" value="<?php echo $options['player_height_default']; ?>" />
                                        </td>
                                </tr>
                                <tr>
                                        <th scope="row">Mobile Device Video Player Size<BR><i>(leave blank to use defaults)</i></th>
                                        <td>
                                                Width <input type="text" size="10" name="hss_embed_options[player_width_mobile]" value="<?php echo $options['player_width_mobile']; ?>" /> Height  <input type="text" size="10" name="hss_embed_options[player_height_mobile]" value="<?php echo $options['player_height_mobile']; ?>" />
                                        </td>
                                </tr>
                                <tr>
                                        <th scope="row">JW Player License Key<BR><i>(available from www.longtailvideo.com)</i></th>
                                        <td>
                                                <input type="text" size="50" name="hss_embed_options[jwplayer_license]" value="<?php echo $options['jwplayer_license']; ?>" />
                                        </td>
                                </tr>
                        </table>
                        <p class="submit">
                        <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
                        </p>
                </form>
        </div>
<?
}

function hss_embed_menu () {
        add_options_page('HostStreamSell Embed Video Admin','HSS Embed Admin','manage_options','hss_embed_admin', 'hss_embed_options_page');
}

add_action('admin_menu','hss_embed_menu');

add_shortcode( 'hss-embed-video', 'hss_embed_video_shortcode' );
function hss_embed_video_shortcode( $atts ) {
global $is_iphone;
        global $user_ID;
    ob_start();
 
    // define attributes and their defaults
    extract( shortcode_atts( array (
      'videoid' => '-1',
      'version' => 'trailer',
    ), $atts ) );


	if($videoid<0){
		echo "ERROR: you need to set the videoid attribute!";	
	      	$myvariable = ob_get_clean();
        	return $myvariable;
	}


                                $options = get_option('hss_embed_options');
                                $userId = $user_ID;
				if($userId==0){
					$userId = mt_rand(100000,999999);
				}
				#echo $userId;

                                $hss_video_id = $videoid;
				
				if($version=="full")
					$force_allow = "yes";
                                $response = wp_remote_post( "https://www.hoststreamsell.com/api/1/xml/videos?api_key=".$options['api_key']."&video_id=$hss_video_id&private_user_id=$userId&expands=playback_details&force_allow=$force_allow", array(
                                        'method' => 'GET',
                                        'timeout' => 15,
                                        'redirection' => 5,
                                        'httpversion' => '1.0',
                                        'blocking' => true,
                                        'headers' => array(),
                                        'body' => $params,
                                        'cookies' => array()
                                    )
                                );
                                $res = "";
                                if( is_wp_error( $response ) ) {
                                   $return_string .= 'Error occured retieving video information, please try refresh the page';
                                } else {
                                   $res = $response['body'];
                                }

                                $xml = new SimpleXMLElement($res);
                                _log($xml);
                                $title = $xml->result->title;
                                $hss_video_title = $title;
                                $user_has_access = $xml->result->user_has_access;
                                //$video = "".$user_has_access;
                                
				#if($user_has_access=="true")
                                #        $video = "<center>You have access to this video</center>";

                                $description = $xml->result->description;
                                $feature_duration = $xml->result->feature_duration;
                                $trailer_duration = $xml->result->trailer_duration;
                                $video_width = $xml->result->width;
                                $video_height = $xml->result->height;
                                if($video_width>640){
                                        $video_width = "640";
                                        $video_height = "390";
                                }
                                $referrer = site_url();
                                $hss_video_user_token = $xml->result->user_token;

                                $hss_video_mediaserver_ip = $xml->result->wowza_ip;

                                $hss_video_smil_token = "?privatetoken=".$hss_video_user_token;
                                $hss_video_mediaserver_ip = $xml->result->wowza_ip;

                                $hss_video_smil = $xml->result->smil;
                                $hss_video_big_thumb_url = $xml->result->big_thumb_url;
                                $hss_rtsp_url = $xml->result->rtsp_url;
                                $referrer = site_url();

                                $content_width = $video_width;
                                $content_height = $video_height;

                                if($is_iphone){
                                        if($content_width<320){
                                                $content_width=320;
                                        }
                                }

                                if($video_width>$content_width){
                                        $mod = $content_width%40;
                                        $video_width = $content_width-$mod;
                                        $multiple = $video_width/40;
                                        $video_height = $multiple*30;
                                }

                                if($is_iphone){
                                        if($options['player_width_mobile']!="")
                                                $video_width=$options['player_width_mobile'];
                                        if($options['player_height_mobile']!="")
                                                $video_height=$options['player_height_mobile'];
                                }else{
                                        if($options['player_width_default']!="")
                                                $video_width=$options['player_width_default'];
                                        if($options['player_height_default']!="")
                                                $video_height=$options['player_height_default'];
                                }

                                $video = $video."
                                <script type=\"text/javascript\" src=\"https://www.hoststreamsell.com/mod/secure_videos/jwplayer-6/jwplayer.js\"></script>
                                <script type=\"text/javascript\" src=\"https://www.hoststreamsell.com/mod/secure_videos/jwplayer/swfobject.js\"></script>
                                <script type=\"text/javascript\">jwplayer.key=\"".$options['jwplayer_license']."\";</script>
                                <center>
                                <div>
                                <div id='videoframe'>If you are seing this you may not have Flash installed!</div>

                                <SCRIPT type=\"text/javascript\">

                                var viewTrailer = false;
                                var videoFiles = new Array();;
                                var trailerFiles = new Array();;

                                var agent=navigator.userAgent.toLowerCase();
                                var is_iphone = (agent.indexOf('iphone')!=-1);
                                var is_ipad = (agent.indexOf('ipad')!=-1);
                                var is_playstation = (agent.indexOf('playstation')!=-1);
                                var is_safari = (agent.indexOf('safari')!=-1);
                                var is_iemobile = (agent.indexOf('iemobile')!=-1);
                                var is_blackberry = (agent.indexOf('BlackBerry')!=-1);
                                var is_android = (agent.indexOf('android')!=-1);
                                var is_webos = (agent.indexOf('webos')!=-1);

                                if (is_iphone) { html5Player();}
                                else if (is_ipad) { html5Player(); }
                                else if (is_android) { rtspPlayer(); }
                                else if (is_webos) { rtspPlayer(); }
                                else if (is_blackberry) { rtspPlayer(); }
                                else if (is_playstation) { newJWPlayer(); }
                                else { newJWPlayer(); }

                                function newJWPlayer()
                                {
                                        jwplayer('videoframe').setup({
                                            playlist: [{
                                                image: '$hss_video_big_thumb_url',
                                                sources: [{
                                                    file: 'https://www.hoststreamsell.com/mod/secure_videos/private_media_playlist_v2.php?params=".$hss_video_id."!".urlencode($referrer)."!".$hss_video_user_token."!',
                                                    type: 'rtmp'
                                                },{
                                                    file: 'http://".$hss_video_mediaserver_ip.":1935/hss/smil:".$hss_video_smil."/playlist.m3u8".$hss_video_smil_token."&referer=".urlencode($referrer)."'
                                                }]
                                            }],
                                            height: $video_height,
                                            primary: 'flash',
                                            width: $video_width
                                        });
                                }

                                function rtspPlayer()
                                {
                                        var player=document.getElementById(\"videoframe\");
                                        player.innerHTML='<A HREF=\"rtsp://".$hss_video_mediaserver_ip."/hss/mp4:".$hss_rtsp_url."".$hss_video_smil_token."&referer=".urlencode($referrer)."\">'+
                                        '<IMG SRC=\"".$hss_video_big_thumb_url."\" '+
                                        'ALT=\"Start Mobile Video\" '+
                                        'BORDER=\"0\" '+
                                        'HEIGHT=\"$video_height\"'+
                                        'WIDTH=\"$video_width\">'+
                                        '</A>';
                                }

                                function html5Player()
                                {
                                        var player=document.getElementById(\"videoframe\");
                                        player.innerHTML='<video controls '+
                                        'src=\"http://".$hss_video_mediaserver_ip.":1935/hss/smil:".$hss_video_smil."/playlist.m3u8".$hss_video_smil_token."&referer=".urlencode($referrer)."\" '+
                                        'HEIGHT=\"".$video_height."\" '+
                                        'WIDTH=\"".$video_width."\" '+
                                        'poster=\"".$hss_video_big_thumb_url."\" '+
                                        'title=\"".$hss_video_title."\">'+
                                        '</video>';
                                }

                                </script>
                                </div>
                                </center>
                                <BR>";

		echo $video;

      $myvariable = ob_get_clean();
        return $myvariable;

}

if(!function_exists('_log')){
  function _log( $message ) {
    if( WP_DEBUG === true ){
      if( is_array( $message ) || is_object( $message ) ){
        error_log( print_r( $message, true ) );
      } else {
        error_log( $message );
      }
    }
  }
}

?>
