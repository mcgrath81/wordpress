<?php 
/**
 * CodePeople Post Map 
 * Version: 1.0.1
 * Author: CodePeople
 * Plugin URI: http://wordpress.dwbooster.com
*/

class CPM {
	//---------- VARIABLES ----------
	
	var $lang_array; // List of supported languages
	var $points = array(); // List of points to set on map
	var $points_str = ''; // List of points as javascript code
	var $flush_map = false; // Determine if the map's code was generated to limit only one map for page
	var $map_id; // ID of map
	
	//---------- CONSTRUCTOR ----------
	
	function __construct(){
		$this->map_id = "cpm_".wp_generate_password(6, false);
		$this->lang_array = array(
							"ar"=>__("ARABIC","codepeople-post-map"),
							"eu"=>__("BASQUE","codepeople-post-map"),
							"bg"=>__("BULGARIAN","codepeople-post-map"),
							"bn"=>__("BENGALI","codepeople-post-map"),
							"ca"=>__("CATALAN","codepeople-post-map"),
							"cs"=>__("CZECH","codepeople-post-map"),
							"da"=>__("DANISH","codepeople-post-map"),
							"de"=>__("GERMAN","codepeople-post-map"),
							"el"=>__("GREEK","codepeople-post-map"),
							"en"=>__("ENGLISH","codepeople-post-map"),
							"en-AU"=>__("ENGLISH (AUSTRALIAN)","codepeople-post-map"),
							"en-GB"=>__("ENGLISH (GREAT BRITAIN)","codepeople-post-map"),
							"es"=>__("SPANISH","codepeople-post-map"),
							"eu"=>__("BASQUE","codepeople-post-map"),
							"fa"=>__("FARSI","codepeople-post-map"),
							"fi"=>__("FINNISH","codepeople-post-map"),
							"fil"=>__("FILIPINO","codepeople-post-map"),
							"fr"=>__("FRENCH","codepeople-post-map"),
							"gl"=>__("GALICIAN","codepeople-post-map"),
							"gu"=>__("GUJARATI","codepeople-post-map"),
							"hi"=>__("HINDI","codepeople-post-map"),
							"hr"=>__("CROATIAN","codepeople-post-map"),
							"hu"=>__("HUNGARIAN","codepeople-post-map"),
							"id"=>__("INDONESIAN","codepeople-post-map"),
							"it"=>__("ITALIAN","codepeople-post-map"),
							"iw"=>__("HEBREW","codepeople-post-map"),
							"ja"=>__("JAPANESE","codepeople-post-map"),
							"kn"=>__("KANNADA","codepeople-post-map"),
							"ko"=>__("KOREAN","codepeople-post-map"),
							"lt"=>__("LITHUANIAN","codepeople-post-map"),
							"lv"=>__("LATVIAN","codepeople-post-map"),
							"ml"=>__("MALAYALAM","codepeople-post-map"),
							"mr"=>__("MARATHI","codepeople-post-map"),
							"nl"=>__("DUTCH","codepeople-post-map"),
							"no"=>__("NORWEGIAN","codepeople-post-map"),
							"or"=>__("ORIYA","codepeople-post-map"),
							"pl"=>__("POLISH","codepeople-post-map"),
							"pt"=>__("PORTUGUESE","codepeople-post-map"),
							"pt-BR"=>__("PORTUGUESE (BRAZIL)","codepeople-post-map"),
							"pt-PT"=>__("PORTUGUESE (PORTUGAL)","codepeople-post-map"),
							"ro"=>__("ROMANIAN","codepeople-post-map"),
							"ru"=>__("RUSSIAN","codepeople-post-map"),
							"sk"=>__("SLOVAK","codepeople-post-map"),
							"sl"=>__("SLOVENIAN","codepeople-post-map"),
							"sr"=>__("SERBIAN","codepeople-post-map"),
							"sv"=>__("SWEDISH","codepeople-post-map"),
							"tl"=>__("TAGALOG","codepeople-post-map"),
							"ta"=>__("TAMIL","codepeople-post-map"),
							"te"=>__("TELUGU","codepeople-post-map"),
							"th"=>__("THAI","codepeople-post-map"),
							"tr"=>__("TURKISH","codepeople-post-map"),
							"uk"=>__("UKRAINIAN","codepeople-post-map"),
							"vi"=>__("VIETNAMESE","codepeople-post-map"),
							"zh-CN"=>__("CHINESE (SIMPLIFIED)","codepeople-post-map"),
							"zh-TW"=>__("CHINESE (TRADITIONAL)","codepeople-post-map")
                                                
        ); 
	} // End __construct
	
	//---------- CREATE MAP ----------
	
	/**
	 * Save a map object in database
	 * called by the action save_post
	 */
	function save_map($post_id){
		// authentication checks

		// make sure data came from our meta box
		if (!wp_verify_nonce($_POST['cpm_map_noncename'],__FILE__)) return $post_id;

		// check user permissions
		if ($_POST['post_type'] == 'page'){
			if (!current_user_can('edit_page', $post_id)) return $post_id;
		}
		else{
			if (!current_user_can('edit_post', $post_id)) return $post_id;
		}

		// authentication passed, save data
		
		// Check the existence of a point associated to the post
		$cpm_point = get_post_meta($post_id, 'cpm_point', TRUE);
		
		$new_cpm_point = $_POST['cpm_point'];
		$new_cpm_map = $_POST['cpm_map'];
		$new_cpm_point['icon'] = $_POST['default_icon'];
		
		// The address is required, if address is empty the couple: latitude, longitude must be defined
		if(!empty($new_cpm_point['address']) || (!empty($new_cpm_point['latitude']) && !empty($new_cpm_point['longitude']))){
			
			$new_cpm_map['zoompancontrol'] 	= ($new_cpm_map['zoompancontrol'] == true);
			$new_cpm_map['mousewheel'] 		= ($new_cpm_map['mousewheel'] == true);
			$new_cpm_map['typecontrol'] 	= ($new_cpm_map['typecontrol'] == true);
			
			if($cpm_point){
				// Update metadata
				update_post_meta($post_id,'cpm_point',$new_cpm_point);
				update_post_meta($post_id,'cpm_map',$new_cpm_map);
			}else{
				// Create metadata
				add_post_meta($post_id,'cpm_point',$new_cpm_point,TRUE);
				add_post_meta($post_id,'cpm_map',$new_cpm_map,TRUE);
			}
		}else{
			// Remove metadata
			if($cpm_point) {
				delete_post_meta($post_id,'cpm_point');
				delete_post_meta($post_id,'cpm_map');
			}	
		}
		
	} // End save_map
	
	//---------- OPTIONS FOR CODEPEOPLE POST MAP ----------
	/**
	 * Get default windowhtml
	 */
	function _get_default_windowhtml(){
		return "<div class='cpm-infowindow'>
					<div class='cpm-content'>
						<a title='%link%' href='%link%'>%thumbnail%</a>
						<a class='title' href='%link%'>%title%</a>
						<div class='address'>%address%</div>
						<div class='description'>%description%</div>
						<a href='%link%' class='more'>more &raquo;</a>
					</div>
					<div style='clear:both;'></div>
				</div>";
	}
	
	/**
	 * Get default configuration options
	 */
	function _default_configuration(){
		return array(
							'zoom' => '10',
							'width' => '450',
							'height' => '450',
							'margin' => '10',
							'align' => 'center',									
							'language' => 'en',
							'icons' => array(),
							'default_icon' => CPM_PLUGIN_URL.'/images/icons/marker.png',
							'type' => 'ROADMAP',
							'points' => 3,
							'display' => 'map',
							'mousewheel' => true,
							'zoompancontrol' => true,
							'typecontrol' => true,
							'highlight'	=> true,
							'highlight_class' => 'cpm_highlight',
							'windowhtml' => $this->_get_default_windowhtml()
							);
	} // End _default_configuration
	
	/**
	 * Set default system and maps configuration
	 */
	function set_default_configuration($default = false){
		$cpm_default = $this->_default_configuration();
							
    	$options = get_option('cpm_config');
		if ($default || $options === false) {
			update_option('cpm_config', $cpm_default);
			$options = $cpm_default;
		}
		return $options;
	} // End set_default_configuration
	
	/**
	 * Get a part of option variable or the complete array
	 */
	function get_configuration_option($option = null){
	
		$options = get_option('cpm_config');
		$default = $this->_default_configuration();
		
		if(!isset($options)){
			$options = $default;
		}
		
		if(isset($option)){
			return (isset($options[$option])) ? $options[$option] : null;
		}else{
			return $options;
		}	
		
	} // End get_configuration_option
	
	//---------- METADATA FORM METHODS ----------
	
	/**
	 * Private method to deploy the list of languages
	 */
	function _deploy_languages($options){
		print '<select name="cpm_map[language]" id="cpm_map_language">';
		foreach($this->lang_array as $key=>$value)
			print '<option value="'.$key.'" '.((isset($options['language']) && $options['language'] == $key) ? 'selected' : '').'>'.$value.'</option>';
		print '</select>';	
	} // End _deploy_languages
	
	/**
	 * Private method to get the list of icons
	 */
	function _deploy_icons($options = null){ 
		$icon_path = CPM_PLUGIN_URL.'/images/icons/';
		$icon_dir = CPM_PLUGIN_DIR.'/images/icons/';	

		$icons_array = array();

		$default_icon = (isset($options) && isset($options['icon'])) ? $options['icon'] : $this->get_configuration_option('default_icon');
		
		if ($handle = opendir($icon_dir)) {
			
			while (false !== ($file = readdir($handle))) {
		
				$file_type = wp_check_filetype($file);
				$file_ext = $file_type['ext'];
				if ($file != "." && $file != ".." && ($file_ext == 'gif' || $file_ext == 'jpg' || $file_ext == 'png') ) {
					array_push($icons_array,$icon_path.$file);
				}
			}
		}
		?>
			<div class="cpm_label">
				<?php _e("Select the marker by clicking on the images", "codepeople-post-map"); ?> 
			</div>    	   
			<div id="cpm_icon_cont">
				<input type="hidden" name="default_icon" value="<?php echo $default_icon ?>" id="default_icon" />			
				<?php foreach ($icons_array as $icon){ ?>
				  <div class="cpm_icon <?php if ($default_icon == $icon) echo "cpm_selected" ?>">
				  <img src="<?php echo $icon ?>" /> 
				  </div>
				<?php } ?>
			</div> 
			<div id="icon_credit">
				<span><?php _e("Powered by","codepeople-post-map"); ?></span>
				<a href="http://mapicons.nicolasmollet.com" target="_blank">
					<img src="<?php echo CPM_PLUGIN_URL ?>/images/miclogo-88x31.gif" />
				</a>
			 </div> 	
		<?php
	} // End _deploy_icons
	
	/**
	 * Private method to insert the map form
	 */
	function _deploy_map_form($options = NULL){
		?>
		<h2><?php _e('Maps Configuration', 'codepeople-post-map'); ?></h2>
		<p  style="border:1px solid #E6DB55;margin-bottom:10px;padding:5px;background-color: #FFFFE0;"><?php _e('For any issues with the map, go to our <a href="http://wordpress.dwbooster.com/contact-us" target="_blank">contact page</a> and leave us a message.'); ?></p>
		<table class="form-table">
			<tr valign="top">
				<th scope="row"><label for="cpm_map_zoom"><?php _e('Map zoom:', 'codepeople-post-map')?></label></th>
				<td>
					<input type="text" size="4" name="cpm_map[zoom]" id="cpm_map_zoom" value="<?php echo ((isset($options['zoom'])) ? $options['zoom'] : '');?>" />
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="cpm_map_width"><?php _e('Map width:', 'codepeople-post-map')?></label></th>
				<td>
					<input type="text" size="4" name="cpm_map[width]" id="cpm_map_width" value="<?php echo ((isset($options['width'])) ? $options['width'] : '');?>" />
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="cpm_map_height"><?php _e('Map height:', 'codepeople-post-map')?></label></th>
				<td>
					<input type="text" size="4" name="cpm_map[height]" id="cpm_map_height" value="<?php echo ((isset($options['height'])) ? $options['height'] : '');?>" />
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="cpm_map_margin"><?php _e('Map margin:', 'codepeople-post-map')?></label></th>
				<td>
					<input type="text" size="4" name="cpm_map[margin]" id="cpm_map_margin" value="<?php echo ((isset($options['height'])) ? $options['margin'] : '');?>" />
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="cpm_map_align"><?php _e('Map align:', 'codepeople-post-map')?></label></th>
				<td>
					<select id="cpm_map_align" name="cpm_map['align']">
						<option value="left" <?php echo((isset($options['align']) && $options['align'] == 'left') ? 'selected': ''); ?>><?php _e('left'); ?></option>
						<option value="center" <?php echo((isset($options['align']) && $options['align'] == 'center') ? 'selected': ''); ?>><?php _e('center'); ?></option>
						<option value="right" <?php echo((isset($options['align']) && $options['align'] == 'right') ? 'selected': ''); ?>><?php _e('right'); ?></option>
					</select>	
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="cpm_map_type"><?php _e('Map type:', 'codepeople-post-map'); ?></label></th>
				<td>
					<select name="cpm_map[type]" id="cpm_map_type" >
						<option value="ROADMAP" <?php echo ((isset($options['type']) && $options['type']=='ROADMAP') ? 'selected' : '');?>><?php _e('ROADMAP - Displays a normal street map', 'codepeople-post-map');?></option>
						<option value="SATELLITE" <?php echo ((isset($options['type']) && $options['type']=='SATELLITE') ? 'selected' : '');?>><?php _e('SATELLITE - Displays satellite images', 'codepeople-post-map');?></option>
						<option value="TERRAIN" <?php echo ((isset($options['type']) && $options['type']=='TERRAIN') ? 'selected' : '');?>><?php _e('TERRAIN - Displays maps with physical features such as terrain and vegetation', 'codepeople-post-map');?></option>
						<option value="HYBRID" <?php echo ((isset($options['type']) && $options['type']=='HYBRID') ? 'selected' : '');?>><?php _e('HYBRID - Displays a transparent layer of major streets on satellite images', 'codepeople-post-map');?></option>
					</select>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="cpm_map_language"><?php _e('Map language:', 'codepeople-post-map');?></th>
				<td><?php $this->_deploy_languages($options); ?></td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="cpm_map_display"><?php _e('Display map in post/page:', 'codepeople-post-map'); ?></label></th>
				<td>
					<select name="cpm_map[display]" id="cpm_map_display" >
						<option value="icon" <?php echo ((isset($options['display']) && $options['display']=='icon') ? 'selected' : '');?>><?php _e('as icon', 'codepeople-post-map'); ?></option>
						<option value="map" <?php echo ((isset($options['display']) && $options['display']=='map') ? 'selected' : '');?>><?php _e('as full map', 'codepeople-post-map'); ?></option>
					</select>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="wpGoogleMaps_description"><?php _e('Options:')?></label></th>
				<td>
					<input type="checkbox" name="cpm_map[mousewheel]" id="cpm_map_mousewheel" value="true" <?php echo ((isset($options['mousewheel']) && $options['mousewheel']) ? 'checked' : '');?> />
					<label for="cpm_map_mousewheel"><?php _e('Enable mouse wheel zoom', 'codepeople-post-map'); ?></label><br />
					<input type="checkbox" name="cpm_map[zoompancontrol]" id="cpm_map_zoompancontrol" value="true" <?php echo ((isset($options['zoompancontrol']) && $options['zoompancontrol']) ? 'checked' : '');?> />
					<label for="cpm_map_zoompancontrol"><?php _e('Enable zoom/pan controls', 'codepeople-post-map'); ?></label><br />
					<input type="checkbox" name="cpm_map[typecontrol]" id="cpm_map_typecontrol" value="true" <?php echo ((isset($options['typecontrol']) && $options['typecontrol']) ? 'checked' : '');?> />
					<label for="cpm_map_typecontrol"> <?php _e('Enable map type controls (Map, Satellite, or Hybrid)', 'codepeople-post-map'); ?> </label><br />
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="cpm_map_points"><?php _e('Enter the number of points on the post/page map:'); ?></th>
				<td><input type="text" name="cpm_map[points]" id="cpm_map_points" value="<?php echo ((isset($options['points'])) ? $options['points'] : '');?>" /></td>
			</tr>
		</table>
	<?php
	} // End _deploy_map_form
	
	/**
	 * Get all the thumbnails from post
	 */
	function _all_post_thumb($the_parent){
		$attachments_id = array();
		$attachments = get_children( array(
										'post_parent' => $the_parent, 
										'post_type' => 'attachment', 
										'post_mime_type' => 'image',
										'orderby' => 'menu_order', 
										'order' => 'ASC',
										'numberposts' => 10) );									
											
		if($attachments == true) :
			foreach($attachments as $attachment) :
				array_push($attachments_id,$attachment->ID);
			endforeach;		
		endif;

		return $attachments_id; 
	} // End _all_post_thumb
	
	/**
	 * Private method to print Maps form
	 */
	function _print_form($options){
		global $post;
		$default_configuration = $this->_default_configuration();
	?>
		<script>
			var cpm_default_marker = "<?php echo $default_configuration['default_icon']; ?>";
			var cpm_point = {};
			
			<?php 
			if(!empty($options['address']) || (!empty($options['latitude']) && !empty($options['longitude']))){ 
				if(!empty($options['address'])) echo 'cpm_point["address"]="'.$options['address'].'";';
				if(!empty($options['latitude']) && !empty($options['longitude'])){
					echo 'cpm_point["latitude"]="'.$options['latitude'].'";';
					echo 'cpm_point["longitude"]="'.$options['longitude'].'";';
				}
				
			} else {
				echo 'cpm_point["address"]="Statue of Liberty, Statue of Liberty National Monument, Statue Of Liberty, New York, NY 10004, USA";'; 
				echo 'cpm_point["latitude"]="40.689848";';
				echo 'cpm_point["longitude"]="-74.044869";';
			} 
			?>
			
		</script>
		<p  style="font-weight:bold;"><?php _e('For more information go to the <a href="http://wordpress.dwbooster.com/content-tools/codepeople-post-map" target="_blank">CodePeople Post Map</a> plugin page'); ?></p>
		<p  style="border:1px solid #E6DB55;margin-bottom:10px;padding:5px;background-color: #FFFFE0;"><?php _e('For any issues with the map, go to our <a href="http://wordpress.dwbooster.com/contact-us" target="_blank">contact page</a> and leave us a message.'); ?></p>
		<div style="border:1px solid #CCC;margin-bottom:10px;min-height:60px;">
			<h3><?php _e('Map points'); ?></h3>
			<div id="points_container" style="padding:10px;">
			<?php _e('Multiple points in the same Post/Page are available only in the <a href="http://wordpress.dwbooster.com/content-tools/codepeople-post-map" target="_blank">advanced version</a>.'); ?>
			</div>
		</div>
		<div class="point_form" style="border:1px solid #CCC;">
			<h3><?php _e('Map point description'); ?></h3>
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><label for="cpm_name"><?php _e('Location name:', 'codepeople-post-map')?></label></th>
					<td>
						<input type="text" size="40" style="width:95%;" name="cpm_point[name]" id="cpm_point_name" value="<?php echo ((isset($options['name'])) ? $options['name'] : '');?>" />
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><label for="cpm_point_description"><?php _e('Location description:', 'codepeople-post-map')?></label></th>
					<td>
						<input type="text" size="40" style="width:95%;" name="cpm_point[description]" id="cpm_point_description" value="<?php echo ((isset($options['description'])) ? $options['description'] : '');?>" />
					</td>
				</tr>
				<tr valign="top">
					<td valign="top" colspan="2">
					<?php 
					$attch_list = $this->_all_post_thumb($post->ID);
					if (count($attch_list) > 0) { 
					?>
						<div>
							<?php _e("Select the thumbnail by clicking on the images","codepeople-post-map"); ?>
						</div>
						<div id="cpm_thumbnail_container">
							<input type="hidden" name="cpm_point[thumbnail]" value="<?php if($options["thumbnail"]){ echo $options["thumbnail"];} ?>" id="cpm_point_thumbnail" />
							<?php 
							foreach ($attch_list as $attch) { 
							   $thumbnail = wp_get_attachment_image_src($attch, 'thumbnail');
							?>
							<div class="cpm_thumb <?php if($options["thumbnail"] && $options["thumbnail"] == $thumbnail[0]){ echo 'cpm_thumb_selected';}?>">
								<img attch="<?php echo $attch ?>" src="<?php echo $thumbnail[0] ?>" width="40" height="40" />
							</div>
							<?php  } ?>
						</div>
					<?php } else { ?>
						<div>
							<strong><?php _e("Thumbnail: ","codepeople-post-map"); ?></strong><?php _e("If you want to attach an image to the point you need to upload it first to the post gallery","codepeople-post-map"); ?>
						</div> 
					<?php  } ?> 
					</td>	
				</tr>
				<tr valign="top">
					<td><a class="button" href="upload.php" title="Upload Images"><?php _e("Upload Images","codepeople-post-map") ?></a></td>
					<td>
					</td>
				</tr>	
				<tr valign="top">
					<td colspan="2">
						<table>
							<tr valign="top">
								<td>
									<table>
										<tr valign="top">
											<th scope="row"><label for="cpm_point_address"><?php _e('Address:', 'codepeople-post-map')?></label></th>
											<td  width="100%">
												<input type="text" style="width:100%;" name="cpm_point[address]" id="cpm_point_address" value="<?php echo ((isset($options['address'])) ? $options['address'] : '');?>" />
											</td>
										</tr>	
										<tr valign="top">
											<th scope="row"><label for="cpm_point_latitude"><?php _e('Latitude:', 'codepeople-post-map')?></label></th>
											<td>
												<input type="text" style="width:100%;" name="cpm_point[latitude]" id="cpm_point_latitude" value="<?php echo ((isset($options['latitude'])) ? $options['latitude'] : '');?>" />
											</td>
										</tr>
										<tr valign="top">
											<th scope="row"><label for="cpm_point_longitude"><?php _e('Longitude:', 'codepeople-post-map')?></label></th>
											<td>
												<input type="text" style="width:100%;" name="cpm_point[longitude]" id="cpm_point_longitude" value="<?php echo ((isset($options['longitude'])) ? $options['longitude'] : '');?>" />
											</td>
										</tr>
										<tr valign="top">
											<th scope="row" style="text-align:right;"><p class="submit"><input type="button" name="cpm_point_verify" id="cpm_point_verify" value="<?php _e('Verify', 'codepeople-post-map'); ?>" onclick="cpm_checking_point(this);" /></p></th>
											<td>
												<label for="cpm_point_verify"><?php _e('Verify this latitude and longitude using Geocoding. This could overwrite the point address.', 'codepeople-post-map')?></label>
											</td>
										</tr>
									</table>
								</td>
								<td width="50%">
									<div id="cpm_map_container" class="cpm_map_container" style="width:400px; height:250px; border:1px dotted #CCC;">
									</div>
								</td>
							</tr>
						</table>	
					</td>
				</tr>
				<tr valign="top">
					<td colspan="2">
						<?php $this->_deploy_icons($options); ?>
					</td>	
				</tr>
			</table>
		</div>
		<p style="border:1px solid #CCC; padding:10px;">
			<?php
			_e( 'To insert this map in a post/page, press the <strong>"insert the map tag"</strong> button and save the post/page modifications.' );
			?>
		</p>	
		<table class="form-table">
			<tr valign="top">
                <th scope="row">
					<label for="cpm_point_bubble"><?php _e('If you want to display the map in page / post:', 'codepeople-post-map')?></label>
				</th>
                <td> 
					<p style="float:left;"><input type="button" class="button-primary" name="cpm_map_shortcode" id="cpm_map_shortcode" value="<?php _e('insert the map tag', 'codepeople-post-map'); ?>" /></p>
				</td>
            </tr>
		</table>	
		<div id="map_data">
			<?php $this->_deploy_map_form($options); ?>
		</div>	
        <p class="submit">
            <input type="button" onclick="display_map_form();" value="<?php _e("Show / Hide Map's Options &raquo;", 'codepeople-post-map'); ?>" />
        </p>
        <p>&nbsp;</p>
		<?php
		// create a custom nonce for submit verification later
		echo '<input type="hidden" name="cpm_map_noncename" value="' . wp_create_nonce(__FILE__) . '" />';
	} // End _print_form
	
	/**
	 * Form for maps insertion and update
	 */
	function insert_form(){
		global $post, $wpdb;
	
		$cpm_point = get_post_meta($post->ID, 'cpm_point', TRUE);
		$cpm_map = get_post_meta($post->ID, 'cpm_map', TRUE);
		$general_options = $this->get_configuration_option();
		$options = array_merge((array)$general_options, (array)$cpm_point, (array)$cpm_map);
		$options['post_id'] = $post->ID;
		$this->_print_form($options);
	} // End insert_form
	
	//---------- LOADING RESOURCES ----------
	
	/*
	 * Load the required scripts and styles for ADMIN section of website
	 */
	function load_admin_resources(){
		wp_enqueue_style(
			'admin_cpm_style',
			CPM_PLUGIN_URL.'/styles/cpm-admin-styles.css'
		);
		
		wp_enqueue_script(
			'admin_cpm_script',
			CPM_PLUGIN_URL.'/js/cpm.admin.js',
			array('jquery')
		);
	} // End load_admin_resources
	
	/**
	 * Load script and style files required for display google maps on public website
	 */
	function load_resources() {
		wp_enqueue_style( 'cpm_style', CPM_PLUGIN_URL.'/styles/cpm-styles.css');
		wp_enqueue_script( 'cpm_script', CPM_PLUGIN_URL.'/js/cpm.js', array('jquery'));
		$language = $this->get_configuration_option('language');
		if(isset($language)) 
			wp_localize_script('cpm_script', 'cpm_language', array('lng' => $language));
	} // End load_resources
	
	function load_footer_resources(){
		echo '<style>.cpm-map img{ max-width: none;box-shadow:none;}</style>';
	} // End load_footer_resources
	
	/**
	 * Print the settings page for entering the general setting's data of maps
	 */
	function settings_page(){
		// Check if post exists and save the configuraton options
		if (wp_verify_nonce($_POST['cpm_map_noncename'],__FILE__)){
			$options = $_POST['cpm_map'];
			if(!isset($options['windowhtml'])){
				$options['windowhtml'] = $this->get_configuration_option('windowhtml');
			}
			update_option('cpm_config', $options);
			echo '<div class="updated"><p><strong>'.__("Settings Updated").'</strong></div>';
		}else{
			$options = $this->get_configuration_option();
		}
		
	?>
		<div class="wrap">
			<form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
	<?php	
		$this->_deploy_map_form($options);
	?>
		<table class="form-table">
			<tr valign="top">
				<th scope="row"><label for="cpm_map_search"  style="color:#CCCCCC;"><?php _e('Use points information in search results:', 'codepeople-post-map')?></label></th>
				<td>
					<input type="checkbox" name="cpm_map[search]" id="cpm_map_search" value="true" disabled /> The use of points metadata in search result is enabled only in advanced version of plugin. <a href="http://wordpress.dwbooster.com/contact-us/codepeople-post-map">Click Here</a>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="cpm_map_highlight" style="color:#CCCCCC;"><?php _e('Highlight post when mouse move over related point on map:', 'codepeople-post-map')?></label></th>
				<td>
					<input type="checkbox" name="cpm_map[highlight]" id="cpm_map_highlight" value="true" disabled /> The use of post highlight is enabled only in advanced version of plugin. <a href="http://wordpress.dwbooster.com/contact-us/codepeople-post-map">Click Here</a>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="cpm_map_highlight_class" style="color:#CCCCCC;"><?php _e('Highlight class:', 'codepeople-post-map')?></label></th>
				<td>
					<input type="input" name="cpm_map[highlight_class]" id="cpm_map_highlight_class" disabled />The use of post highlight class is possible only in advanced version of plugin. <a href="http://wordpress.dwbooster.com/contact-us/codepeople-post-map">Click Here</a>
				</td>
			</tr>
		</table>
		<p  style="font-weight:bold;"><?php _e('For more information go to the <a href="http://wordpress.dwbooster.com/content-tools/codepeople-post-map" target="_blank">CodePeople Post Map</a> plugin page'); ?></p>
		<div class="submit"><input type="submit" class="button-primary" value="<?php _e('Update Settings', 'codepeople-post-map');?>" /></div>
		<?php 
			// create a custom nonce for submit verification later
			echo '<input type="hidden" name="cpm_map_noncename" value="' . wp_create_nonce(__FILE__) . '" />'; 
		?>
		</form>
		</div>
	<?php
	} // End settings_page
	
	//---------- SHORTCODE METHODS ----------
	
	/*
	 * Static method for ordering an array of posts
	 */
	static function _ordering_array($postA, $postB){
		if ($postA->post_date == $postB->post_date) {
            return 0;
        }
        return ($postA->post_date > $postB->post_date) ? -1 : +1;
	} // End _ordering_array
	
	/*
	 * Static method for remove duplicate posts
	 */
	static function _unique_element( $obj ) {
		static $posts_list = array();
 
		if ( in_array( $obj->ID, $posts_list ) )
			return false;
 
		$posts_list[] = $obj->ID;
		return true;    
	} // End _unique_element
	
	/*
	 * Populate the attribute points
	 */
	function populate_points($post){
		$point = get_post_meta($post->ID, 'cpm_point', TRUE);
		if(!empty($point)){
			$point['post_id'] = $post->ID;
			$this->points[] = $point;
			$index = count($this->points);
		}	
	} // End populate_points
	
	/**
	 * Replace each [codepeople-post-map] shortcode by the map
	 */
	function replace_shortcode($atts){
		global $post;
		
		// Limit the publication of map to only one
		if($this->flush_map)
			return '';
		
		$this->flush_map = true;
		
		if(is_singular()){ // For maps in a post or page
			$cpm_map = get_post_meta($post->ID, 'cpm_map', TRUE);
			$number = (!empty($cpm_map['points'])) ? 'numberposts='.$cpm_map['points'].'&' : '';
			
			// Set the actual post only to avoid duplicates
			$posts = array($post);
			
			// Get POSTs in the same category
			$categories = get_the_category();
			foreach($categories as $category){
				$posts = array_merge($posts, get_posts($number.'category='. $category->term_id));
			}
			
			// Remove duplicate posts
			$posts = array_filter($posts, array('CPM', '_unique_element'));
			
			// The first post is the actual post, I remove it before sorting the rest of posts in list
			$actual = array_shift($posts);
			usort($posts, array('CPM', '_ordering_array'));
			array_unshift($posts, $actual);
			
			// Obtain only the number of posts configured in the plugin settings
			if(!empty($cpm_map['points']) && $cpm_map['points'] > 0)
				$posts = array_slice($posts, 0, $cpm_map['points']);
			
			foreach($posts as $_post){
				$this->populate_points($_post);
			}	
			
			$output  = $this->_set_map_tag($cpm_map);
			$output .= $this->_set_map_config($cpm_map);
			$output .= "<script>\n";
			$i = 0;
			foreach ($this->points as $point){	  	 
				$output .= $this->_set_map_point($point, $i, (($point['post_id'] == $post->ID) ? "true" : "false"));
				$i ++;
			}	  
			$output .= "</script>\n
			<noscript>
				codepeople-post-map require JavaScript
			</noscript>
			";	
			return $output;
		}else{ 
			return '';
		}	
	} // End replace_shortcode
	
	/*
	 * Generates the DIV tag where the map will be loaded
	 */
	function _set_map_tag($atts){
		extract($atts);				
		
		$output ='<div id="'.$this->map_id.'" class="cpm-map" style="display:none; width:'.$width.'px; height:'.$height.'px; ';
		switch ($align) {
			case "left" :		  
				$output .= 'float:left; margin:'.$margin.'px;"';
			break;
			case "right" :		  
				$output .= 'float:right; margin:'.$margin.'px;"';
			break;
			case "center" :		  
				$output .= 'clear:both; overflow:hidden; margin:'.$margin.'px auto;"';
			break;
			default:
				$output .= 'clear:both; overflow:hidden; margin:'.$margin.'px auto;"';
			break;		  	  
		}
		$output .= "></div>";
		return $output;
	} // End _set_map_tag
	
	/*
	 * Generates the javascript tag with map configuration
	 */
	function _set_map_config($atts){
		extract($atts);
		$output  = "<script type=\"text/javascript\">\n";
		$output .= "var cpm_global = cpm_global || {};\n";
		$output .= "cpm_global['$this->map_id'] = {}; \n";
		$output .= "cpm_global['$this->map_id']['zoom'] = $zoom;\n";
		$output .= "cpm_global['$this->map_id']['markers'] = new Array();\n";
		$output .= "cpm_global['$this->map_id']['display'] = '$display';\n"; 
		$output .= "cpm_global['$this->map_id']['highlight_class'] = '".$this->get_configuration_option('highlight_class')."';\n"; 
		  
		$highlight = $this->get_configuration_option('highlight');
		$output .= "cpm_global['$this->map_id']['highlight'] = ".(($highlight && !is_singular()) ? 'true' : 'false').";\n"; 
		$output .= "cpm_global['$this->map_id']['type'] = '$type';\n";	
		  
		// Define controls
		$output .= "cpm_global['$this->map_id']['mousewheel'] = ".(($mousewheel) ? 'true' : 'false').";\n";	  
		$output .= "cpm_global['$this->map_id']['zoompancontrol'] = ".(($zoompancontrol) ? 'true' : 'false').";\n";	  
		$output .= "cpm_global['$this->map_id']['typecontrol'] = ".(($typecontrol) ? 'true' : 'false').";\n";	  
		$output .= "</script>";
		
		return $output;
	} // End _set_map_config
	
	/*
	 * Generates the javascript code of map points, only called from single post page
	 */
	function _set_map_point($point, $index, $default = "false"){
		return 'cpm_global["'.$this->map_id.'"]["markers"]['.$index.'] = 
							{"address":"'.$point['address'].'",
							 "lat":"'.$point['latitude'].'",
							 "lng":"'.$point['longitude'].'",
							 "info":"'.$this->_get_windowhtml($point).'",
							 "open":"'.$default.'",
							 "icon":"'.((!empty($point['icon'])) ? $point['icon'] : $this->get_configuration_option('default_icon')).'",
							 "post":"'.$point['post_id'].'"};';
	} // End _set_map_point
	
	/**
	 * Get the html info associated to point marker
	 */
 	function _get_windowhtml(&$point) {
    
		$windowhtml = "";
		$windowhtml_frame = $this->get_configuration_option('windowhtml');	
		if(!isset($windowhtml_frame)){
			$windowhtml_frame = $this->_get_default_windowhtml();
		}
		
		$point_title = (!empty($point['name'])) ? $point['name'] : get_the_title($point['post_id']);
		$point_link = (!empty($point['post_id'])) ? get_permalink($point['post_id']) : '';
		
		$point_thumbnail = "";
		if ($point['thumbnail'] != "") {
			if(is_numeric($point['thumbnail'])){
				$thumb = wp_get_attachment_image_src($point['thumbnail'], 'thumbnail');
				$point_thumbnail = $thumb[0];
			}else{
				$point_thumbnail = $point['thumbnail'];
			}
		}
		
		$point_img_url = ($point_thumbnail != "")? $point_thumbnail : $this->_post_img($point['post_id']);
		
		$point_excerpt = $this->_get_excerpt($point['post_id']);

		$point_description = ($point['description'] != "") ? $point['description'] : $point_excerpt;
		$point_address = $point['address'];

		if(isset($point_img_url)) {
			$point_img = "<img src='".$point_img_url."' style='margin:8px 0 0 8px; width:90px; height:90px'/>";
			$html_width = "310px";
		} else {
			$point_img = "";
			$html_width = "auto";
		}				
					
		$find = array("%title%","%link%","%thumbnail%", "%excerpt%","%description%","%address%","%width%","\r\n","\f","\v","\t","\r","\n","\\","\"");
		$replace  = array($point_title,$point_link,$point_img,$point_excerpt,$point_description,$point_address,$html_width,"","","","","","","","'");
		
		$windowhtml = str_replace( $find, $replace, $windowhtml_frame);
					
		return $windowhtml;
		
	} // End _get_windowhtml
	
	/**
	 * Get the thumbnail from post
	 */
	function _post_img($the_parent,$size = 'thumbnail'){
		
		if( function_exists('has_post_thumbnail') && has_post_thumbnail($the_parent)) {
			$thumbnail_id = get_post_thumbnail_id( $the_parent );
			if(!empty($thumbnail_id))
			$img = wp_get_attachment_image_src( $thumbnail_id, $size );	
		} else {
		$attachments = get_children( array(
											'post_parent' => $the_parent, 
											'post_type' => 'attachment', 
											'post_mime_type' => 'image',
											'orderby' => 'menu_order', 
											'order' => 'ASC', 
											'numberposts' => 1) );
		if($attachments == true) :
			foreach($attachments as $id => $attachment) :
				$img = wp_get_attachment_image_src($id, $size);			
			endforeach;		
		endif;
		}
		if (isset($img[0])) return $img[0]; 
	} // End _post_img
	
	/**
	 * Get the excerpt from content
	 */
	function _get_excerpt($post_id) { // Fakes an excerpt if needed

		$content_post = get_post($post_id);
		$content = $content_post->post_content;

		if ( '' != $content ) {
			
			$content = strip_shortcodes( $content ); 
			$content = str_replace(']]>', ']]&gt;', $content);
			$content = strip_tags($content);
			$excerpt_length = 10;
			$words = explode(' ', $content, $excerpt_length + 1);
			if (count($words) > $excerpt_length) {
				array_pop($words);
				array_push($words, '[...]');
				$content = implode(' ', $words);
			}
		}
		return $content;
	} // End _get_excerpt
	
	/*
		Set a link to contact page
	*/
	function customizationLink($links) { 
		$settings_link = '<a href="http://wordpress.dwbooster.com/contact-us" target="_blank">'.__('Request custom changes').'</a>'; 
		array_unshift($links, $settings_link); 
		return $links; 
	} // End customizationLink
} // End CPM class
?>