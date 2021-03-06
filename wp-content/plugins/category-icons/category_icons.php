<?php
/*
Plugin Name: Category Icons
Plugin URI: http://www.category-icons.com/
Description: Easily assign icons to your categories. (Minimum WP version : 2.8)
Version: 2.2.3
Author: Brahim Machkouri
Author URI: http://www.category-icons.com/
Text Domain: category_icons
Domain Path: /languages/
*/

/*
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

// Initialization and Hooks
global $table_prefix,$recent_posts_version;
$wpdb->ig_caticons = $table_prefix.'ig_caticons';
$caticons_datas = array();

add_action('init', 'ig_caticons_install');
add_action('admin_menu', 'ig_caticons_adminmenu');
add_action('admin_menu', 'bm_caticons_add_js_libs');
add_filter('pre_kses', 'bm_caticons_plugin_description');
add_action('rss2_head','bm_caticons_rss_flag');
add_action('rdf_header','bm_caticons_rss_flag'); // only for Safari
add_action('atom_ns','bm_caticons_rss_flag');
add_filter('the_excerpt_rss','bm_caticons_rss');
add_filter('the_content','bm_caticons_rss'); 
add_filter('the_content_rss','bm_caticons_rss');
add_filter('plugin_action_links', 'bm_caticons_plugin_action_links', 10, 2);

include_once('category_icons_widget.php');

/**
 * Display "Settings" once the plugin is activated
 * @author Brahim Machkouri 
 */
function bm_caticons_plugin_action_links($links, $file) {
	if ($file == plugin_basename(dirname(__FILE__).'/category_icons.php')){
      	$links[]  = "<a href='edit.php?page=category_icons.php'>" . __('Settings') . "</a>";
      }
	return $links;
}

/**
 * WordPress Template Tag to Insert Category Icon
 * @author Brahim Machkouri 
 * @param boolean $align align attribute for the image tag
 * @param int $fit_width Maximum width of the image, or desired width if expand is true. Default : -1
 * @param int $fit_height Maximum height (or desired height if $expanded=true) of the image. Default : -1
 * @param boolean $expand Whether the image should be expanded to fit the rectangle specified by fit_xxx. Default : false
 * @param int $cat Category ID. If not specified, the current category is used or the current post's category. Default : 
 * @param boolean $small Use the small icon. Default : false
 * @param string $prefix String to echo before the image tag. If no image, no output. Default : 
 * @param string $suffix String to echo after the image tag. Ignored if no image found. Default : 
 * @param string $class Class attribute for the image tag. Default : 
 * @param boolean $link If true, an anchor tag wraps the image (a hyperlink to the category is made). Default : true
 * @param boolean $echo If true the html code will be 'echoed'. If no, it'll be returned. Default : true
 * @param boolean $use_priority If true, only the most prioritized icon will be displayed. Default : false
 * @param int $max_icons Maximum number of icons to display. Default : 3
 * @param boolean $border If true, displays the icon with a border. If false, no border is diplayed. (Don't use this if you want valid Strict XHTML)
 * @param boolean $hierarchical If true, displays the icon in hierarchical order.(horizontally)
 * @return boolean True if image found.
 */
function get_cat_icon($params='') {
    $one_category_only = false;
    
    if (0 < (int) get_option('igcaticons_iconcatpage',0) ) {
        $one_category_only = true;
    }
     
	// Compatibility with qTranslate
	if (function_exists('qtrans_useCurrentLanguageIfNotFoundUseDefaultLanguage')) {
		remove_filter('wp_list_categories','qtrans_useCurrentLanguageIfNotFoundUseDefaultLanguage',0);
		remove_filter('list_cats','qtrans_useCurrentLanguageIfNotFoundUseDefaultLanguage',0);
	}
	
	// Compatibility with SEO Friendly Images
	if (function_exists('seo_friendly_images') && 1 == get_option('igcaticons_useseo_plugin'))
		add_filter('category_icons', 'seo_friendly_images', 50);
	parse_str($params, $p);
	if (!isset($p['fit_width'])) $p['fit_width'] = (int) get_option('igcaticons_max_width');
	if (!isset($p['fit_height'])) $p['fit_height'] = (int) get_option('igcaticons_max_height');
	if (!isset($p['expand'])) $p['expand'] = false;
	if (!isset($p['small'])) $p['small'] = (int) get_option('igcaticons_use_small');
	if (!isset($p['prefix'])) $p['prefix'] = '';
	if (!isset($p['suffix'])) $p['suffix'] = '';
	if (!isset($p['class'])) $p['class'] = '';
	if (!isset($p['link'])) $p['link'] = true;
	if (!isset($p['echo'])) $p['echo'] = true; 
	if (!isset($p['use_priority'])) $p['use_priority'] = false;
	if (!isset($p['max_icons'])) $p['max_icons'] = (int) get_option('igcaticons_max_icons'); 
	
	if (!isset($p['align'])) $p['align'] = false; 
	if (!isset($p['hierarchical'])) $p['hierarchical'] = false; 
	if (!$p['hierarchical'] === false) $p['hierarchical'] = bm_caticons_getbool($p['hierarchical']);
	
	if (!isset($p['orderby'])) $p['orderby'] = 'name';
	$p['expand'] = bm_caticons_getbool($p['expand']);
	$p['small'] = bm_caticons_getbool($p['small']);
	$p['echo'] = bm_caticons_getbool($p['echo']);
	if (isset($p['border'])) $p['border'] = bm_caticons_getbool($p['border']);
	$p['use_priority'] = bm_caticons_getbool($p['use_priority']);
	if ($p['max_icons']==1) $p['use_priority'] = true;
	$p['link'] = bm_caticons_getbool($p['link']);
	
	stripslaghes_gpc_arr($p);
	if (is_category() && in_the_loop() && $one_category_only == true ) {
		$cat = (int) get_query_var('cat');
		$category=get_category($cat);
		if (!isset($p['cat']) || !is_array($p['cat'])) {
    		$p['cat']=array();
		}
		
    	$p['cat'][] = $category->cat_ID;
	}
	else {
		if (!isset($p['cat']) && isset($GLOBALS['post'])) {
			$catlist = get_the_category($GLOBALS['post']->ID);
			if (is_array($catlist)) {
				$p['cat'] = array();
				if ($p['orderby']!='name' || $p['hierarchical']==true) {
					$p['cat'] = bm_caticons_process_categories('orderby='.$p['orderby'].'&hierarchical='.$p['hierarchical']);
				} else {
					foreach ($catlist as $categorie) {
						$p['cat'][] = $categorie->cat_ID; // Adds all the categories in the array
						$cat[$categorie->cat_ID] = $categorie->name;
					}
				}
			}
		}
	
		if (!isset($p['cat'][0])) return;
		if (!is_array($p['cat'])) {
			$categorie= (int) $p['cat'];
			$p['cat'] = array();
			$p['cat'][] = $categorie;
		}
	}
	
	$nb_icons = 0;
	$urlbegin = '';
	$urlend = '';
	$cat_icons = '';
	for ($i=0; $i<count($p['cat']); $i++)	{
		if($p['use_priority']) // if you decide to use the priority feature
			list( $p['cat'][$i] , $priority , $icon , $small_icon ) = ow_caticons_get_priority_icon( $p['cat'] );
		else 
			list( $priority , $icon , $small_icon ) = bm_caticons_get_icons( $p['cat'][$i]);
		
		if ($p['small']) {// If we choose to display the small icon
			$file = ig_caticons_path().'/'.$small_icon;
			$url = ig_caticons_url().'/'.$small_icon;
			if (!is_file($file)) {// if the small icon can't be found, the normal one will be loaded
				$file = ig_caticons_path().'/'.$icon;
				$url = ig_caticons_url().'/'.$icon;
			}
		} 
		else {
			$file = ig_caticons_path().'/'.$icon;
			$url = ig_caticons_url().'/'.$icon;
			if ( !is_file($file) ) {// If the normal icon can't be found, the small one will be loaded
				$file = ig_caticons_path().'/'.$small_icon;
				$url = ig_caticons_url().'/'.$small_icon;
			}
		}
		if ( is_file($file) ) {
			if ( $p['link'] ) {
				$urlbegin = '<a href="'.get_category_link($p['cat'][$i]).'" title="';
				if (function_exists('qtrans_useCurrentLanguageIfNotFoundUseDefaultLanguage')) // Compatibility with qTranslate 
					$urlbegin .= qtrans_useCurrentLanguageIfNotFoundUseDefaultLanguage(get_cat_name($p['cat'][$i]));
				else
					$urlbegin .= get_cat_name($p['cat'][$i]);
				$urlbegin .= '">';
				$urlend = '</a>';
			}
			list( $width , $height , $type , $attr) = getimagesize($file);
			$w = (int) $width;
			$h = (int) $height;
			if (!empty($p['fit_width']) || !empty($p['fit_height'])) 
				list($w, $h) = ig_caticons_fit_rect($width, $height, $p['fit_width'], $p['fit_height'], $p['expand']);
			$cat_icons .= $p['prefix'].$urlbegin.'<img ';
			if (!empty($p['class'])) $cat_icons .= 'class="'.$p['class'].'" ';
			if (isset($p['border'])) {
				if ($p['border']) 
					$cat_icons .= 'border="1" ';
				else
					$cat_icons .= 'border="0" ';
			}
			if (!$p['align']===false) $cat_icons .= 'align="'.$p['align'].'" ';
			$fill_with_catname = get_cat_name($p['cat'][$i]);
			$title = 'title="'.$fill_with_catname.'"';	
			$alt =  'alt="'.$fill_with_catname.'"';;//$title;	
			if (function_exists('seo_friendly_images') && 1 == get_option('igcaticons_useseo_plugin'))  $title = '' ; // Compatibility with qTranslate
			$cat_icons .= 'src="'.$url.'" width="'.$w.'" height="'.$h.'" '.$alt.' '.$title.' />'.$urlend.$p['suffix'];
			$nb_icons++;
		}
		if ( $p['use_priority'] ) break;
		if ( $nb_icons == $p['max_icons']) break;
	}
	if (function_exists('qtrans_useCurrentLanguageIfNotFoundUseDefaultLanguage')) {// Add the filter again, as we finished to process the icon(s)
		add_filter('wp_list_categories','qtrans_useCurrentLanguageIfNotFoundUseDefaultLanguage',0);
		add_filter('list_cats','qtrans_useCurrentLanguageIfNotFoundUseDefaultLanguage',0);
	}
	$cat_icons = apply_filters('category_icons', $cat_icons);
	if (function_exists('qtrans_useCurrentLanguageIfNotFoundUseDefaultLanguage') ) // Update the compatibility with qTranslate
		$cat_icons = qtrans_useCurrentLanguageIfNotFoundUseDefaultLanguage($cat_icons);
	if ($p['echo'])	{
		if (empty($cat_icons)) 	
			return false;
		else {
			echo $cat_icons;
			return true; // if echo get_cat_icon() is used, true will be displayed as "1". So the parameter echo=false must be used in this case.
		}
	} 
	else 
		return $cat_icons;
}

/**
 * Order the categories
 * @author Brahim Machkouri
 * @param string $string parameters
 */
function bm_caticons_process_categories($string='') {	
    $caticons_processed = array();
	$catlist = get_the_category($GLOBALS['post']->ID);	
	if (count($catlist)>1) {
		parse_str($string);
		$depth = -1; // Flat
		$r = array('orderby' => 'name', 'order' => 'ASC', 'hierarchical' => false, 'echo' => 0, 'hide_empty' => 0);
		if ($hierarchical==1) {
			$r['hierarchical'] = true;
			$depth = 0 ;
		}
		if (in_array(strtolower($orderby), array('id','slug','count','order','term_order')))
			$r['orderby'] = $orderby;			
		$a = 'array('.bm_caticons_walk_tree(  get_categories( $r ), $depth, $r ).');';
		eval("\$caticons_processed = $a;");
		foreach ($catlist as $object) 
			$categories[] = $object->cat_ID;
		$caticons_processed = array_intersect($caticons_processed,$categories);
	} else {
		$caticons_processed = array($catlist[0]->cat_ID);
	}
	return array_values($caticons_processed);
}

/**
 * Get the categories ordered the way we want
 * @author Brahim Machkouri
 */
function bm_caticons_walk_tree() { // Greatly inspired from WordPress Source Code
	include_once('category_icons_walker.php');
	$args = func_get_args();
	// the user's options are the third parameter
	if ( empty($args[2]['walker']) || !is_a($args[2]['walker'], 'Walker') )
		$walker = new Walker_Caticons;
	else
		$walker = $args[2]['walker'];
	$string = call_user_func_array(array( &$walker, 'walk' ), $args );
	return substr($string, 0, strlen($string)-1);
}

/**
 * WordPress Template Tag to Inject the html image source code of each category contained in the list given as parameter
 * @author Brahim Machkouri
 * @param string $list HTML code of the category list 
 * @param string $parameters The parameters of get_cat_icon() function 
 */
function put_cat_icons($list,$parameters='',$type='category') {
	global $wpdb,$post;
	$echo_output = true;
	$original_title = $post->post_title;
	$request = "SELECT term_id
				FROM $wpdb->term_relationships
				right join $wpdb->term_taxonomy on $wpdb->term_relationships.term_taxonomy_id = $wpdb->term_taxonomy.term_taxonomy_id
				left join $wpdb->posts on $wpdb->term_relationships.object_id = $wpdb->posts.ID
				where post_type = 'page' and $wpdb->term_taxonomy.taxonomy = 'category' and ID=";
	$nb_max_icons_page = (int) get_option('igcaticons_max_icons');
	$cats = array();
	if ($type != 'cat' && $type != 'page') {
		$type = 'cat';
		if (version_compare($GLOBALS['wp_version'], '3.1', '>=') ) {
			$type = 'category_name';
		}
	}
	if (empty($parameters)) // If no parameter is given, the ones from the options will be taken
		$parameters = 'fit_width='.get_option('igcaticons_max_width').'&fit_height='.get_option('igcaticons_max_height').'&small='.(bm_caticons_getbool(get_option('igcaticons_use_small')) ?'true':'false');
	else {
		parse_str($parameters, $p);
		if (isset($p['echo']) && bm_caticons_getbool($p['echo'])===false) {
			$echo_output = false;
		}
		if ( !isset($p['icons_only']) ) {
			$p['icons_only']=0;
		}	
		if ( isset($p['icons_only']) && 'true' == strtolower( $p['icons_only'] ) ) {
			$p['icons_only']=1;
		}
	}
	if (!empty($list)) {
		$myarray = bm_caticons_url_extractor($list);
		foreach ($myarray as $child) {
			$cats = array();
			$pos = strpos($child[0],$type.'=');
			if ($pos>0)	{ 
				parse_str(substr($child[0],$pos, strlen($child[0])-$pos)); // $cat or $category_name is created from this function
				if (isset($page) && intval($page) >0) { // get the category from page id
					$cats = array();
					$cats = $wpdb->get_results($wpdb->prepare($request.intval($page)),ARRAY_A);
				}
				else {
					if (version_compare($GLOBALS['wp_version'], '3.1', '>=')) {
						// standard permalinks in wp 3.1+: http://www.sublab.dev/wpdev/?category_name=apple
						$cats[] = bm_caticons_get_cat_ID_by_slug($category_name);
					}
					else { // standard permalinks in wp up to 3.0: http://www.sublab.dev/wpdev/?cat=3
						$cats[] = (int) $cat;
					}
				}
			}
			else {// not standard permalinks 
				$cats = array();
				$temp = explode('/',($child[0]));
				$name = '';
				do 
					$name = array_pop($temp); // get the last non empty/null string from the array
				while (is_null($name) || empty($name));
				if ($type == 'cat' OR $type == 'category_name') {
					$cat  = bm_caticons_get_cat_ID_by_slug(trim($name));
					$cats[] = $cat;
				}
				else {
					$cats = array();
					$page  = $wpdb->get_var($wpdb->prepare("SELECT ID FROM `$wpdb->posts` WHERE post_name = '$name'"),0,0);
					$cats = $wpdb->get_results($wpdb->prepare($request.intval($page)),ARRAY_A);
				}
			}
			if (is_array($cats[0])) { // Pages Icons
				$temp = array();
				for ($i=0; $i<count($cats);$i++) {
					$temp[$i]= $cats[$i]['term_id'];
				}
				$cats = $temp;
				unset($temp);
			}
			$img = '';
			$i=1;
			if (count($cats)>0) {
				foreach ($cats as $categ) {
					if ($categ == 1 && $type == 'page') continue;// Do not display uncategorized icon for a page
					if ($i > $nb_max_icons_page) break;
					$post->post_title = get_cat_name($categ);
					$img .= get_cat_icon($parameters.'&cat='.$categ.'&echo=false&link=false');
					$i++;
				}
				if ( isset($p) && 1 == $p['icons_only'] && !empty($img) ) {
					$list = str_replace($child[1],'>'.$img.'<', $list);
				}
				else {// Inject icon into the list
					$before_name = bm_caticons_getbool(get_option('igcaticons_before_name'));
					if ($before_name) { // put the html code of the icons before the category name
						$cat_name = substr($child[1],1,strlen($child[1]));
						$list = str_replace('>'.$cat_name, '>'.$img.$cat_name,$list);
					} else {// else after the category name
						$new = substr($child[1],0,strlen($child[1])-1);
						$list = str_replace($child[1], $new.$img.'<', $list);
					}
				}
			}
		}
		$list = apply_filters('category_icons_widget', $list);
		if ($echo_output) echo $list;
	}
	else {
		_e("put_cat_icons : your list is empty ! Don't forget to add <strong>echo=0</strong> in the parameters of wp_list_categories().",'category_icons');
	}
	$post->post_title = $original_title;
	if (!$echo_output && !empty($list))  
		return $list;
}

/**
 * Get the slug category
 * @author Brahim Machkouri
 * @param string $slug slug from which you want to get the category 
 * @return int 
 */
function bm_caticons_get_cat_ID_by_slug($slug) {
	$idObj = get_category_by_slug($slug);
	
	$id = false; 
	if (is_object($idObj))
	 $id = $idObj->term_id;
	else { // if no category id is found, this is ?cat=X given
		list($dummy,$id) = explode('?cat=',$slug);
		$id = (int) $id;
	}
	return $id;
}

/**
 * If a RSS feed is created, 'raise the flag'
 * @author Brahim Machkouri
 */
function bm_caticons_rss_flag() {
	global $bm_caticons_rss;
	$bm_caticons_rss = true;
}

/**
 * Inject the icons into the feeds 
 * Working only with RSS2 and Atom. (And RDF, but only in Safari)
 * @author Brahim Machkouri
 * @param string The content to process
 * @return string The feed content
 */
function bm_caticons_rss($content) {
	global $bm_caticons_rss;
	if ( $bm_caticons_rss && 1 == get_option('igcaticons_rssfeeds')  ) // If the rss flag is raised, inject icons
		$content = get_cat_icon('echo=false&link=false').'<br/>'.$content;
	return $content;
}

/**
 * Localization of the plugin description
 * @author Brahim Machkouri
 * @param string The string to process
 * @return string The string to display
 */
function bm_caticons_plugin_description( $string) {
	if (trim($string) == 'Easily assign icons to your categories.') {
		$string = __('Easily assign icons to your categories.','category_icons');
	}
	return $string;
}

/**
 * Displays informations in the footer of WordPress
 * @author Brahim Machkouri
 */
function bm_caticons_credits() {
	$translator_name = (__('translator_name','category_icons')) == 'translator_name' ? '' : __('translator_name','category_icons');
	$translator_url  = (__('translator_url','category_icons')) == 'translator_url' ? '' : __('translator_url','category_icons') ;
	$lang = get_locale();
	$plugins = get_plugins();
	$current_version = trim($plugins['category-icons/category_icons.php']['Version']);
	$msg = 'Category Icons '.$current_version.'&nbsp;&nbsp;<img style="border-style:none;vertical-align:middle;padding:0px;padding-bottom:1px;margin:0px;" src="'.trailingslashit(get_option('siteurl')).PLUGINDIR.'/category-icons/images/w3c-xhtml1.0.png" alt="w3c-xhtml" /> | ';
	$msg .= '<a target="_blank" href="http://www.category-icons.com/about/">'.__('About','category_icons').'</a>';
	$msg .= ' | <a target="_blank" href="http://www.category-icons.com/category/howto/">'.__('How To','category_icons').'</a>';
	$msg .= ' | <a target="_blank" href="http://www.category-icons.com/category/documentation/">'.__('Documentation','category_icons').'</a>';
	$msg .= ' | <a target="_blank" href="http://www.category-icons.com/category/faq/">'.__('FAQ','category_icons').'</a>';
	$msg .= ' | <a target="_blank" href="http://www.category-icons.com/translate-the-plugin/">'.__('Translate the plugin','category_icons').'</a>';
	$msg .= ' | <a target="_blank" href="http://www.category-icons.com/troubleshooting/">'.__('Troubleshooting','category_icons').'</a>';
	$msg .= ' | <a target="_blank" href="http://www.category-icons.com/contact/">'.__('Contact the author','category_icons').'</a>' ;
	if (!empty($lang) && !empty($translator_name)) {
		$msg .= (' | ').__('Translated by','category_icons');
		if (!empty($translator_url))
			$msg .= ' <a target="_blank" href="'.$translator_url.'">'.$translator_name.'</a>';
		else
			$msg .= ' '.$translator_name;
	}
	echo $msg.' <br/>';
}

/**
 * Loads the scripts needed by the plugin
 * @author Brahim Machkouri
 */
function bm_caticons_add_js_libs() {
	wp_enqueue_script('jquery');
	wp_enqueue_style ( 'caticons-css', WP_PLUGIN_URL.'/category-icons/category_icons.css', array(), '1.0', 'all');
	wp_enqueue_script('admin-forms');//WP 2.5+ only
	wp_enqueue_script('caticons-tablesorter', WP_PLUGIN_URL.'/category-icons/js/jquery.tablesorter.min.js');
	wp_enqueue_script('caticons-metadatas', WP_PLUGIN_URL.'/category-icons/js/jquery.metadata.min.js');
	wp_enqueue_script('caticons-js', WP_PLUGIN_URL.'/category-icons/js/category_icons.js');	
	wp_localize_script( 'caticons-js', 'CatIconsSettings', 
		array('plugin_url' => get_option('siteurl').WP_PLUGIN_URL.'/category-icons',
			  'error' => __('Can\'t contact www.category-icons.com. Retry later, please.','category_icons'))
		);
}

/**
 * Return the boolean corresponding to $var
 * @author Brahim Machkouri
 * @param boolean|int|string $var boolean or string or int to eval
 * @return boolean
 */
function bm_caticons_getbool($var) {// i made this function because casting get_option() didn't react as I wanted
	if (is_bool($var)) return $var;
    switch (strtolower($var)) {
        case ('true'): 
			return true;
        case ('false'): 
        default: 
			return false;
    }
}

/**
 * Extract the urls & labels
 * @author Brahim Machkouri
 * @param string $string String containing the urls to extract
 * @return array
 */
function bm_caticons_url_extractor($string) {
	$myarray = array();
	if (preg_match_all('/<a\s+.*?href=[\"\']?([^\"\' >]*)[\"\']?[^>]*(>.*?<)\/a>/i', $string, $correspondances, PREG_SET_ORDER)) 
		foreach ($correspondances as $correspondance) 
			array_push($myarray, array($correspondance[1], $correspondance[2]));
	return $myarray;
}

/**
 * Find where to place the code (get_cat_icons()) in the template files. This function'll work for numerous templates.
 * For the majority of the templates I've seen, the post title is after an anchor tag or after a h2 tag.
 * So the function search for : <a href...>the_title()</a> and <h2...>the_title()</h2>.
 * @author Brahim Machkouri
 * @param string $filename Name of the template folder
 * @return array
 */
function bm_caticons_template_code($filename='') { //needs to be optimized or rewritten
	$files = array();
	$results = array();
	$position = 0;
	$correspondances = array();
	$filename='';
	$notfound = 0;
	//$path = ABSPATH.WP_CONTENT_DIR.'/themes/'.get_option('template');
	$path = WP_CONTENT_DIR.'/themes/'.get_option('template');
	$getcat_code = "\n<?php if (function_exists('get_cat_icon')) get_cat_icon(); ?>";
	
	if ($handle = opendir($path)) {
		while (false !== ($filename = readdir($handle))) 
			if ( '.php' == trim(substr($filename, strrpos($filename, '.'), strlen($filename)-strrpos($filename, '.'))) ) 
				$files[] = trim($filename);
		closedir($handle);
	}

	foreach ($files as $filename) {// Scan all the php files found for the linked (a href) the_title() function
		$fullfilename = $path.'/'.$filename;
		$string = file_get_contents($fullfilename);
		$found = 0;
		
		if ( (false === strpos(strtolower($string),'put_cat_icons(')) ) {
			if (preg_match_all('/(wp_list_cat?[^\)].*\)[;\s])/i', $string, $correspondances, PREG_SET_ORDER)) {
				$result = array();
				foreach ($correspondances as $correspondance) {
					if ( isset($correspondance[1]) ) 
						$result[] = $correspondance[1];
				}
				
				$correspondances = array_unique($result);
				foreach ($correspondances as $correspondance) {
					if (strpos($correspondance,'(')>0) {// Extract the wp_list_categories() parameters
						$parameters = '';
						if (preg_match_all('/[\(](.*[^\)]\))/i', $correspondance, $trouves, PREG_SET_ORDER)) {
							$parameters = substr($trouves[0][1],0,strlen($trouves[0][0])-2);
							if ( false === !strpos($parameters,'(') ) 
								$parameters = $trouves[0][1];
						}
						// Put the code
						$putcat_code = "\nif (function_exists('put_cat_icons')) \n\tput_cat_icons( wp_list_categories(".$parameters;
						if (!empty($parameters)) 
							$putcat_code .=".'&";
						else
							$putcat_code .= '\'';	
						$putcat_code .= "echo=0'));\nelse\n\twp_list_categories(".$parameters.')';
						if (strrpos($correspondance,';')>0)
							$putcat_code .= ';';
						if ( 1 == get_option('igcaticons_templatecode_patch') && is_writable($path.'/'.$filename)) {// patch the files
							$handle = fopen($path.'/'.$filename, "wb");
							$string = str_replace($correspondance,$putcat_code,$string);
							if (fwrite($handle, $string)) $results = array($filename,'-','<i>Ok</i>');
							fclose($handle);
						}
						else {// display the informations about the line and column
						    $position = 0;
							do {
								$position = strpos($string,$correspondance,$position);	
								if ( !( false === $position )	) {					
									$line_number = substr_count(  substr($string, 0,$position)   ,"\n")+1;								
									$column = $position - strrpos (substr($string, 0,$position),"\n") - 1;
									$results[] = array($putcat_code,$filename,$line_number.':'.$column,htmlentities($correspondance));
									$position += strlen($correspondance);
								}
							}
							while ( !( false === $position ));
						}
					}
				}
				
			}
		}
		
		if ( false === strpos(strtolower($string),'get_cat_icon(') ) {
			if (preg_match_all('/(<a\s+.*?href=[\"\']?[^\"\' >]*[\"\']?[^>].*>+[\s\t\r\n]*<\?php +[\/\s\t\r\n$]*?[^>]+[\s\t\r\n]*.*\?>+[\s\t\r\n]*<\/a>)/i', $string, $correspondances, PREG_SET_ORDER)) {
				$correspondances = array_unique($correspondances);
				foreach ($correspondances as $correspondance) {
					if (strpos(strtolower($correspondance[1]),'the_title()')>0) {
						$found = 1;
						if (get_option('igcaticons_templatecode_patch')==1 && is_writable($path.'/'.$filename)) {// patch the files
							$handle = fopen($path.'/'.$filename, "wb");
							$string = str_replace($correspondance[1],$getcat_code.$correspondance[1],$string);
							if (fwrite($handle, $string)) 
								$results = array($getcat_code,$filename,'-','<i>Ok</i>');
							fclose($handle);
						}
						else {// display the informations about the line and column
							do {
								$position = strpos($string,$correspondance[1],$position);	
								if ( !( false === $position )	) {						
									$line_number = substr_count(  substr($string, 0,$position)   ,"\n")+1;								
									$column = $position - strrpos (substr($string, 0,$position),"\n") - 1;
									$results[] = array($getcat_code,$filename,$line_number.':'.$column,htmlentities($correspondance[1]));
									$position += strlen($correspondance[1]);
								}
							}
							while  (!( false === $position ));
						}
					}
				}
			}
			if ( 0 == $found && preg_match_all('#<h2[^>]*>+[\s\t\r\n]*(<\?php +[\/\s\t\r\n$]*?[^>]+[\s\t\r\n]*.*\?>+[\s\t\r\n]*</h2>)#Ui', $string, $correspondances, PREG_SET_ORDER)) {
				$correspondances = array_unique($correspondances);
				foreach ($correspondances as $correspondance) {
					if (strpos(strtolower($correspondance[1]),'the_title()')>0) {
						if ( 1 == get_option('igcaticons_templatecode_patch') && is_writable($path.'/'.$filename)) {// patch the files
							$handle = fopen($path.'/'.$filename, "wb");
							$string = str_replace($correspondance[1],$getcat_code.$correspondance[1],$string);
							if (fwrite($handle, $string)) $results = array($getcat_code,$filename,'-','<i>Ok</i>');
							fclose($handle);
						}
						else {// display the informations about the line and column
							do {
								$position = strpos($string,$correspondance[1],$position);	
								if ( !( false === $position )	) {							
									$line_number = substr_count(  substr($string, 0,$position)   ,"\n")+1;								
									$column = $position - strrpos (substr($string, 0,$position),"\n") - 1;
									$results[] = array($getcat_code,$filename,$line_number.':'.$column,htmlentities($correspondance[1]));
									$position += strlen($correspondance[1]);
								}
							}
							while (!( false === $position ));
						}
					}
				}
			}	
		}
	}
	return $results;
}

/**
 * Display code template table panel.
 * @author Brahim Machkouri
 * @param string $message String to display before the table
 * @param string $code String representing the code to paste
 * @param array $list Every row to display
 * @param string $filter Which type of table to display
 */
function bm_caticons_codetemplate_display($message,$list,$filter) {
	$table = array();
	foreach ($list as $element) {
		if (strpos($element[0],$filter)) $table[] = $element;
	}
	$list = $table;
	if ( 0 < count($list) ) {
?>
         <?php echo '<p>'.$message.'</p>'; ?>
           <?php if ( 'get_cat_icon' == $filter ) echo '<pre>'.htmlentities($list[0][0]).'</pre>'; ?>
            <table class="widefat">
            	<thead>
                <tr>
                    <th ><?php _e('Filename','category_icons'); ?></th>
                    <th style="text-align:center"><?php _e('Line Number : Column','category_icons'); ?></th>
                    <th style="text-align:center"><?php _e('Code','category_icons'); ?></th>
                </tr>
                </thead>
                <tbody>
                <?php 
                    $class = '';
                    if ( 0 == count($list) ) 
						$list = array(array('-','-','-'));
                    foreach ($list as $element) {	
                        $class = ('alternate' == $class) ? '' : 'alternate';
                        ?>
                    <tr class="<?php echo $class; ?>">
                        <th><?php echo $element[1]; ?></th>
                        <td align="center"><?php echo $element[2]; ?></td>
                        <td align="center"><?php 
							if ( 'get_cat_icon' == $filter ) 
								echo '<img src="'.trailingslashit(get_option('siteurl')).PLUGINDIR.'/category-icons/images/arrowdown.png" alt="arrow" />';
							echo $element[3];
							if ( 'put_cat_icon' == $filter )
								echo '<br/><b>'.__('by','category_icons').'</b><br/>'.htmlentities($element[0]).'<br/>';
							?></td>
                    </tr>
                        <?php
                    }
                ?></tbody>
         </table>
  <?php
     }
}

/**
 * Check if the default upload path is readable.
 * @author Brahim Machkouri
 */
function bm_caticons_check_uploads_path() {
	if (!wp_mkdir_p(ig_caticons_path())) {
	
		$options_file = "options-misc.php";
	
		if (version_compare($GLOBALS['wp_version'], '3.0','>=')) {
			$options_file ="options-media.php";
		}
	
		$message = __('the default upload path is not accessible.','category_icons');
		if (!is_multisite()) {
			$message = "<a href='$options_file'>".$message.'</a>';
		}
		else {
			$message .= ' ( '.ig_caticons_path().' ) <br/>';
		}
	
		if (!is_readable(ig_caticons_path())) {
		?>
	        <div id="message" class="updated fade">
	            <p><strong>
	        <?php 
	            echo __('Error in Category Icons','category_icons').' : '.$message.__('Please create one or change the permissions on the directory.','category_icons'); 
	        ?>
	            </strong></p>
	        </div>
	    <?php
		}
	}
}

/**
 * Echoes the option tag filled with icons paths
 * @author Brahim Machkouri
 * @param string $selected the icon to select in the dropdown menu
 */
function bm_caticons_get_icons_paths($selected='') {
	$files = array();
	$basepath = ig_caticons_path();
	$files = bm_caticons_recursive_readdir($basepath);
	natcasesort($files);
	foreach ($files as $fullpath => $filename) {
		$sel = ($selected==$fullpath) ? ' selected="selected"' : '';
		echo('<option value="'.htmlspecialchars($fullpath).'"'.$sel.'>'. htmlspecialchars($filename).' ('.str_replace($filename,'',$fullpath).')</option>');
	}
}

/**
 * Gets the icons paths
 * @author Brahim Machkouri
 * @param string $folder Name of the folder to browse
 * @param array $files Filenames already found
 * @return array
 */
function bm_caticons_recursive_readdir($folder,$files=array()) {
	$separator = '/';
	$types = explode(' ',get_option('igcaticons_filetypes'));
	if (is_readable($folder) && $handle = opendir($folder)  ) {
		while (false !== ($filename = readdir($handle))) {
			if ($filename !== '.' && $filename !== '..') {
				$path = $folder.$separator.$filename;
				if (is_dir ($path)) 
					$files = array_unique(array_merge($files, bm_caticons_recursive_readdir($path,$files))); // recursive call
				else {// files
					$p = strrpos($filename,'.');
					if (false===$p || !in_array(strtolower(substr($filename, $p+1)), $types)) continue;
					else
						$files[str_replace(ig_caticons_path().$separator,'', trim($path))] = $filename;
				}
			}
		}
		closedir($handle);
	}
	return $files;
}

/**
 * Print category rows for admin panel
 * @author Brahim Machkouri
 * @param int $parent Category ID of the parent
 * @param int $level Level of the category (parent or child)
 * @param array $category Array of categories (Objects)
 * @return false False if no category is found
 */
function bm_caticons_rows( $parent = 0, $level = 0, $categories = 0 ) { // I took the code from template.php of WordPress 2.5 and modified it a little
	if ( !$categories ) {
		$args = array('hide_empty' => 0);
		if ( !empty($_GET['s']) )
			$args['search'] = $_GET['s'];
		$categories = get_categories( $args );
	}
	$children = _get_term_hierarchy('category');
	if ( $categories ) {
		ob_start();
		foreach ( $categories as $category ) {
			if ( $category->parent == $parent) {
				echo "\t" . _bm_caticons_row( $category, $level );
				if ( isset($children[$category->term_id]) )
					bm_caticons_rows( $category->term_id, $level +1, $categories );
			}
		}
		$output = ob_get_contents();
		ob_end_clean();
		$output = apply_filters('bm_caticons_rows', $output);
		echo $output;
	}
	else
		return false;
}

/**
 * Display the rows of the icons panel in Icons tab
 * @author Brahim Machkouri
 * @return string 
 */
function _bm_caticons_row( $category, $level) { // I took the code from template.php of WordPress 2.5 and modified it a little
	global $class;
	$icons_path = ig_caticons_path();
	$category = get_category( $category );
	$warning_flag = 0;
	$pad = str_repeat( '&#8212; ', $level );
	$name =  $pad . ' ' . $category->name ;
	list($priority, $icon, $small_icon) = bm_caticons_get_icons($category->term_id);
	$message_icon = esc_attr(sprintf(__('If you can\'t see the icons, the URL to icons in the Options panel should be set to %s','category_icons'), ig_caticons_url().get_option('igcaticons_path')));
	// Normal icon
	$icon_cell = '';
	if ( !empty($icon) && is_readable( trailingslashit($icons_path).$icon ) ) {
		list($width, $height, $type, $attr) = getimagesize(trailingslashit($icons_path).$icon);
		list($w, $h) = ig_caticons_fit_rect($width, $height, 100, 100);
		$icon_cell = '<img src="'.trailingslashit(ig_caticons_url())."$icon\" width=\"$w\" height=\"$h\" alt=\"icon\" onerror='alert(\"$message_icon\");' /> <br />$icon ($width x $height)";
		$icon_cell = '<a href="'.trailingslashit(ig_caticons_url()).$icon.'">'.$icon_cell.'</a>';
	}
	if ( !empty($icon) && !is_readable( trailingslashit($icons_path).$icon ) ) {
		$icon_cell = '<img src="'.$warning.'">'.trailingslashit($icons_path).$icon._e('is not accessible','category_icons');
		$warning_flag = 1;
	}
	// Small icon
	$small_icon_cell = '';
	if ( !empty($small_icon) && is_readable( trailingslashit($icons_path).$small_icon ) ) {
		list($width, $height, $type, $attr) = getimagesize(trailingslashit($icons_path).$small_icon);
		list($w, $h) = ig_caticons_fit_rect($width, $height, 100, 100);
		$small_icon_cell = '<img src="'.trailingslashit(ig_caticons_url())."$small_icon\" width=\"$w\" height=\"$h\" alt=\"small icon\" onerror='alert(\"$message_icon\");'/> <br />$small_icon ($width x $height)";
		$small_icon_cell = '<a href="'.trailingslashit(ig_caticons_url()).$small_icon.'">'.$small_icon_cell.'</a>';
	}
	if ( !empty($small_icon) && !is_readable( trailingslashit($icons_path).$small_icon ) ) {
		$small_icon_cell = '<img src="'.$warning.'">'.trailingslashit($icons_path).$small_icon._e('is not accessible','category_icons');
		$warning_flag = 1;
	}
	$edit = $name;
	if (is_admin() || current_user_can( 'manage_categories' ))
		$edit = "<a class='row-title' href='".CI_ADMIN_SELF."&amp;ig_module=caticons&amp;ig_tab=icons&amp;action=edit&amp;cat_ID=$category->term_id' title='"
				. esc_attr(sprintf(__('Edit %s','category_icons'), $category->name)) . "'>" .$name . "</a>";
	$class = " class='alternate'" == $class ? '' : " class='alternate'";
	if ($warning_flag > 0) $class = 'style = "background-color:#FFB06C"';
	$category->count = number_format_i18n( $category->count );
	$posts_count = $category->count;
	// Prepare the output string
	$output = '<tr id="cat-'.$category->term_id.'" '.$class.' >'.
			   '<th scope="row" class="check-column" style="text-align:center;vertical-align:middle;padding:7px 0 8px">';
	if (is_admin() || current_user_can( 'manage_categories' ))
		$output .= '<input type="checkbox" name="delete[]" value="'.$category->term_id.'" /></th>';
	else 
		$output .= "&nbsp;";
	$output .=  '<td style="vertical-align:middle;">'.$category->term_id.'</td>'.
				'<td style="vertical-align:middle;">'.$edit.'</td>'.
				'<td style="vertical-align:middle;">'.$category->description.'</td>'.
				'<td class="num" style="vertical-align:middle;text-align:center" align="center" >'.$posts_count.'</td>'.
				'<td class="num" style="vertical-align:middle;text-align:center" align="center">'.$priority.'</td>'.
				'<td style="vertical-align:middle;text-align:center" align="center">'.$icon_cell.'</td>'.
				'<td style="vertical-align:middle;text-align:center" align="center">'.$small_icon_cell.'</td>'.
				"\n\t</tr>\n";
	return apply_filters('bm_caticons_row', $output);
}

/**
 * Display the icons panel in Icons tab
 * @author Brahim MACHKOURI
 */
function bm_caticons_adminicons() { // I took some of the code from categories.php of WordPress 2.5 and modified it a little
	global $wpdb;
	$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
	if ( isset($_GET['deleteit']) && isset($_GET['delete']) ) 
		$action = 'bulk-delete';
	switch($action) {
		case 'update-category-icon':
			$cat_ID = (int) $_GET['cat_ID'];
			$priority = $_REQUEST['ig_priority'];
			$icon = $_REQUEST['ig_icon'];
			$small_icon = $_REQUEST['ig_small_icon'];
			if ($wpdb->get_var($wpdb->prepare("SELECT cat_id FROM $wpdb->ig_caticons WHERE cat_id='$cat_ID'"))) {
				$wpdb->query($wpdb->prepare("UPDATE $wpdb->ig_caticons SET priority='$priority', icon='$icon', small_icon='$small_icon' WHERE cat_id='$cat_ID'"));
			} else {
				$wpdb->query($wpdb->prepare("INSERT INTO $wpdb->ig_caticons (cat_id, priority, icon, small_icon) VALUES ('$cat_ID', '$priority', '$icon', '$small_icon')"));
			}
		break;
		case 'delete':
			$cat_ID = (int) $_GET['cat_ID'];
			if (!is_admin() || !current_user_can( 'manage_categories' ))
				wp_die(__('Are you trying to cheat ?','category_icons'));
			$cat_name = get_catname($cat_ID);
			$request = "DELETE FROM $wpdb->ig_caticons WHERE cat_id='$cat_ID'";
			if (false === $wpdb->query($wpdb->prepare($request) ))
				wp_die(__('Error in Category Icons','category_icons').' : '.$request);
		break;
		case 'bulk-delete':
			if (!is_admin() || !current_user_can( 'manage_categories' ))
				wp_die( __('You are not allowed to delete category icons.','category_icons') );
			foreach ( (array) $_GET['delete'] as $cat_ID ) {
				$cat_name = get_catname($cat_ID);
				$wpdb->query($wpdb->prepare("DELETE FROM $wpdb->ig_caticons WHERE cat_id='$cat_ID'"));
			}
		break;
	}
	switch ($action) {
	case 'edit':
		$cat_ID = (int) $_GET['cat_ID'];
		$category = get_category_to_edit($cat_ID);
		list($priority, $icon, $small_icon) = bm_caticons_get_icons($cat_ID);
		?>
		<div class="wrap">
		<h2><?php _e('Select Category Icons','category_icons') ?></h2>
		<form method="post" name="caticons-form1" action="">
		  <?php wp_nonce_field('caticons-nonce'); ?>
			<input type="hidden" name="ig_module" value="caticons" />
			<input type="hidden" name="ig_tab" value="icons" />
			<input type="hidden" name="action" value="update-category-icon" />
            <table  border="0" class="form-table">
                <tr>
                    <th scope="row" style="vertical-align:text-top;"><?php _e('Category ID','category_icons'); ?></th>
                    <td colspan="2" ><?php echo $cat_ID;?></td>
                </tr>
                <tr>
                    <th scope="row" style="vertical-align:text-top;"><?php _e('Name','category_icons'); ?></th>
                    <td colspan="2"><?php echo $category->name;?></td>
                </tr>
                <tr>
                    <th scope="row" class="num" style="vertical-align:text-top;"><?php _e('Priority','category_icons'); ?></th>
                    <td colspan="2">
                        <input type="text" name="ig_priority" size="5" value="<?php echo $priority; ?>" />
                    </td>
                </tr>
                <tr>
                    <th scope="row" style="vertical-align:text-top;"><?php _e('Icon','category_icons'); ?></th>
                    <td valign="top"><label><?php _e('Select a file : ','category_icons');?></label>
                        <select name="ig_icon" onchange="icon_preview.src=('<?php echo ig_caticons_url();?>/'+this.options[this.selectedIndex].value);">
                            <option value="">--- <?php _e('No Icon','category_icons'); ?> ---</option>
                            <?php bm_caticons_get_icons_paths($icon);	?>
                        </select><br/>
                        <label><?php _e('Or enter an URL : ','category_icons');?><input type="text" name="bm_icon_url"/></label>
                    </td>
                    <td valign="top"><img id="icon_preview" src="<?php echo ig_caticons_url()."/$icon";?>" alt="icon" /></td>
                </tr>
                <tr>
                    <th scope="row" style="vertical-align:text-top;"><?php _e('Small Icon','category_icons'); ?></th>
                    <td valign="top"><label><?php _e('Select a file : ','category_icons');?></label>
                        <select name="ig_small_icon" onchange="small_icon_preview.src=('<?php echo ig_caticons_url();?>/'+this.options[this.selectedIndex].value);">
                            <option value="">--- <?php _e('No Icon','category_icons'); ?> ---</option>
                            <?php bm_caticons_get_icons_paths($small_icon); ?>
                        </select><br/>
                        <label><?php _e('Or enter an URL : ','category_icons');?><input type="text" name="bm_smallicon_url"/></label>
                    </td>
                    <td valign="top"><img id="small_icon_preview" src="<?php echo ig_caticons_url()."/$small_icon";?>" alt="small icon" /></td>
                </tr>
            </table>
			<div class="submit"><input type="submit" name="info_update" value="<?php _e('Select Icons','category_icons');?> &raquo;" /></div>
        </form>
		</div>
		<?php
	break;
	default:
	?>
	<div class="wrap">
        <form id="posts-filter" action="" method="get" name="caticons-form2">
        <?php wp_nonce_field('caticons-nonce'); ?>
            <input type="hidden" name="ig_module" value="caticons" />
            <input type="hidden" name="page" value="category_icons.php" />
            <input type="hidden" name="action" value="delete" />
            <input type="hidden" name="ig_tab" value="icons" />
		
		<div class="tablenav">
			<div class="alignleft">
				<input type="submit" value="<?php _e('Delete icons and priority','category_icons'); ?>" name="deleteit" class="button-secondary delete" />
			</div>
			<br class="clear" />
		</div>
		<br class="clear" />
		<table class="widefat" id="caticons_table">
			<thead>
			<tr>
   				<th scope="col" id="cb" class="check-column"><input type="checkbox" /></th>
				<th scope="col"><?php _e('ID','category_icons') ?></th>
                <th scope="col" ><?php _e('Name','category_icons') ?></th>
                <th scope="col" style="text-align:center"><?php _e('Description','category_icons') ?></th>
                <th scope="col" class="num" style="text-align:center"><?php _e('Posts','category_icons') ?></th>
                <th scope="col" style="text-align:center"><?php _e('Priority','category_icons') ?></th>
				<th scope="col" style="text-align:center"><?php _e('Icon','category_icons') ?></th>
				<th scope="col" style="text-align:center"><?php _e('Small Icon','category_icons') ?></th>
			</tr>
			</thead>
			<tbody id="the-list" class="list:cat">
		<?php
		bm_caticons_rows();
		?>
			</tbody>
		</table>
		</form>
        <div class="tablenav">
        <br class="clear" />
        </div>
        <br class="clear" />
        </div>
	<?php
	}// end switch
}

/**
 * Install the plugin
 * @author Ivan Georgiev
 */
function ig_caticons_install() {
	global $wpdb, $table_prefix;
	$wpdb->query($wpdb->prepare("CREATE TABLE IF NOT EXISTS `$wpdb->ig_caticons` (`cat_id` INT NOT NULL ,`priority` INT NOT NULL ,`icon` TEXT NOT NULL ,`small_icon` TEXT NOT NULL , PRIMARY KEY ( `cat_id` ))"));
	add_option('igcaticons_path', '');
	add_option('igcaticons_url', '');
	add_option('igcaticons_filetypes', 'jpg gif jpeg png');
	add_option('igcaticons_max_icons','3');
	add_option('igcaticons_before_name','true');
	add_option('igcaticons_fit_width','-1');
	add_option('igcaticons_fit_height','-1');
	add_option('igcaticons_use_small','true');
	add_option('igcaticons_templatecode_patch','0');
	add_option('igcaticons_templatecode_sidebar','1');
	add_option('igcaticons_rssfeeds','1');
	add_option('igcaticons_useseo_plugin', '0');
	add_option('igcaticons_max_width','-1');
	add_option('igcaticons_max_height','-1');
	add_option('igcaticons_iconcatpage','0');
}

/**
 * Get the upload & siteurl paths
 * @author Ivan Georgiev
 * @return array (path, url) 
 */
function ig_caticons_defupload() {
	$def_path = str_replace(ABSPATH, '', get_option('upload_path')); // wordpress's option
	$def_url = trailingslashit(get_option('siteurl')) . $def_path; // idem
	return array($def_path, $def_url);
}

/**
 * Integrate into the admin menu.
 * @author Ivan Georgiev, Brahim Machkouri
 */
function ig_caticons_adminmenu() {
	load_plugin_textdomain('category_icons', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	$page = isset($_REQUEST['page']) ? $_REQUEST['page'] : 'category_icons.php';
	define('CI_ADMIN_SELF', '?page='.$page);
	if (function_exists('add_submenu_page'))
		add_submenu_page('edit.php', __('Category Icons','category_icons') , __('Category Icons','category_icons') , 'manage_categories' , basename(__FILE__) , 'ig_caticons_adminpanel' );	
}

/**
 * Handle admin panel
 * @authors Ivan Georgiev, Brahim Machkouri
 */
function ig_caticons_adminpanel() {
	add_action( 'in_admin_footer', 'bm_caticons_credits' );
	if (!(file_exists( ABSPATH.'/'.PLUGINDIR.'/category-icons/category_icons.php'))) {
?>
		<div id="message" class="updated fade">
        	<p><strong>
		<?php 
			echo __('Error in Category Icons','category_icons').' : '.__('you must put the category_icons.php in /wp-content/plugins/category-icons/ directory.<br/> So deactivate the plugin, and move the file into category-icons directory.<br/>Click on Plugins 2 times, to restart the plugin detection.<br/>And then, reactivate it.','category_icons'); 
		?>
        	</strong></p>
        </div>
<?php
	}
	bm_caticons_check_uploads_path();
	$tab = isset($_REQUEST['ig_tab']) ? $_REQUEST['ig_tab'] : '';
	if (isset($_POST['info_update']) && 'options' == $tab && check_admin_referer('caticons-nonce')) {	
		?><div class="updated"><p><strong><?php _e('Settings updated.','category_icons');?></strong></p></div><?php
		update_option('igcaticons_path', $_POST['igcaticons_path']);
		update_option('igcaticons_url', $_POST['igcaticons_url']);
		update_option('igcaticons_filetypes', $_POST['igcaticons_filetypes']);
		update_option('igcaticons_max_icons', $_POST['igcaticons_max_icons']);
		update_option('igcaticons_before_name', $_POST['igcaticons_before_name']);
		update_option('igcaticons_max_width', $_POST['igcaticons_max_width']);
		update_option('igcaticons_max_height', $_POST['igcaticons_max_height']);
		
		bm_caticons_updatemyoption('igcaticons_templatecode_patch');
		bm_caticons_updatemyoption('igcaticons_templatecode_sidebar');
		bm_caticons_updatemyoption('igcaticons_rssfeeds');  
		bm_caticons_updatemyoption('igcaticons_useseo_plugin');
		bm_caticons_updatemyoption('igcaticons_iconcatpage');		
	}
	
	bm_caticons_menu($tab);
	switch ($tab) {
		case ('icons'):
			bm_caticons_adminicons();
		break;
		case ('template'):
			bm_caticons_admintemplate();
		break;
		case ('options'):
		default:
			bm_caticons_adminoptions();
	}
}

// for the checkboxes options
function bm_caticons_updatemyoption($option) {
    if (isset($_POST[$option])) {
	    update_option($option, $_POST[$option]);
	}
	else {
    	update_option($option, 0);
	}  
}
/**
 * Display the menu (Icons, Options, Template Tags)
 * @author Brahim Machkouri
 * @param string $tab The tab that was seleted
 */
function bm_caticons_menu($tab) {
?> <div class="wrap"><?php screen_icon(); ?>
	<h2><a target="_blank" href="http://www.category-icons.com/"><?php _e('Category Icons','category_icons'); ?></a></h2>
	<ul style="display:inline" id="submenu" class="subsubsub">
      <li id="caticons_icons" style="font-size:12px"> <a style="display:inline" href="<?php echo CI_ADMIN_SELF;?>&amp;ig_module=caticons&amp;ig_tab=icons" <?php if ('icons' == $tab ) echo 'class="current"'; ?>><?php _e('Icons','category_icons'); ?></a>&nbsp;</li>
      <li id="caticons_options" style="font-size:12px"> <a style="display:inline" href="<?php echo CI_ADMIN_SELF;?>&amp;ig_module=caticons&amp;ig_tab=options" <?php if ('options' == $tab || empty($tab)) echo 'class="current"'; ?>><?php _e('Options','category_icons'); ?></a>&nbsp;</li>
      <li id="caticons_templatecode" style="font-size:12px"> <a style="display:inline" href="<?php echo CI_ADMIN_SELF;?>&amp;ig_module=caticons&amp;ig_tab=template" <?php if ('template' == $tab ) echo 'class="current"'; ?>><?php _e('Template Tags','category_icons'); ?></a>&nbsp;</li>
	 </ul>
     </div><div class="clear"></div>
<?php    
}

/**
 * Display the Options tab
 * @author Brahim Machkouri
 */
function bm_caticons_adminoptions() {	
	list($def_path, $def_url) = ig_caticons_defupload();
?><div class="form-wrap">
	<form method="post" action="" name="caticons-form3">
	<?php wp_nonce_field('caticons-nonce'); ?>
	<input type="hidden" name="ig_module" value="caticons"/>
	<input type="hidden" name="ig_tab" value="options"/>
    <div class="col-container">
        <div id="col-right">
        	<div class="metabox-holder">	
                <div class="postbox">
                    <h3 class="hndle"><span><?php _e('Template Tags','category_icons'); ?></span></h3>
                    <div class="inside">
                        <label class="selectit"><input type="checkbox" name="igcaticons_templatecode_patch" id="templatecode_patch" value="1" <?php checked('1', get_option('igcaticons_templatecode_patch')); ?> />
                        <?php _e('Patch the files if possible (check for true)','category_icons');  ?></label>
                        <label class="selectit"><input type="checkbox" name="igcaticons_templatecode_sidebar" id="templatecode_sidebar" value="1" <?php checked('1', get_option('igcaticons_templatecode_sidebar')); ?> />
                        <?php _e('Process the list of categories (usually used in sidebar) ?','category_icons');?></label>
                    </div>
                </div>
            </div>
            <div class="metabox-holder">	
                <div class="postbox">
                <h3 class="hndle"><span><?php _e('Position of the category icon in the sidebar','category_icons'); ?></span></h3>
            <div class="inside">
                <label><input type="radio" name="igcaticons_before_name" id="before_name_left" value="true" <?php  checked('true',get_option('igcaticons_before_name')); ?>/>&nbsp;<?php _e('Left','category_icons');?></label>
                <label><input type="radio" name="igcaticons_before_name" id="before_name_right" value="false" <?php checked('false',get_option('igcaticons_before_name')); ?>/>&nbsp;<?php _e('Right','category_icons');?></label>
                <p><?php _e("Select 'Left' if you want to put the category icon in front of the category name in the sidebar, or 'Right' if you want to put it after. <b>Remember that you must use the put_cat_icons() function to display icons in the sidebar, unless you use the <a href='widgets.php'>widget</a></b>.",'category_icons');?></p>
            </div>
            </div>
            </div>
                <label><input type="checkbox" name="igcaticons_rssfeeds" id="rssfeeds" value="1" <?php checked('1', get_option('igcaticons_rssfeeds')); ?> />&nbsp;<?php _e('Display category icons in RSS feeds','category_icons'); ?></label>
                <label><input type="checkbox" name="igcaticons_iconcatpage" id="iconcatpage" value="1" <?php checked('1', get_option('igcaticons_iconcatpage')); ?> />&nbsp;<?php _e('Display only the icon of the selected category in the category page','category_icons'); ?></label>
                
            <?php if (function_exists('seo_friendly_images')) : ?>
                <label><input type="checkbox" name="igcaticons_useseo_plugin" id="useseofriendlyimages" value="1" <?php checked('1', get_option('igcaticons_useseo_plugin')); ?> />
            <?php _e('Use SEO Friendly Images plugin','category_icons'); ?></label>
            <?php  endif; ?>
         
        </div>
        <div id="col-left">
            <div class="form-field">
                <label><?php _e('Local Path to Icons','category_icons');?></label>
                <input type="text" name="igcaticons_path" size="50" value="<?php echo htmlspecialchars(get_option('igcaticons_path')); ?>" />
                <p><?php _e('Leave blank to use default upload path','category_icons');?>.</p>
				 <p><?php echo __('Your local path to icons is set to ','category_icons').trailingslashit(ig_caticons_path()); ?></p>
            </div>
            <div class="form-field">
                <label><?php _e('URL to Icons','category_icons');?></label>
                <input type="text" name="igcaticons_url" size="50" value="<?php echo htmlspecialchars(get_option('igcaticons_url')); ?>" />
                <p><?php _e('Leave blank to use default upload url','category_icons');?>.</p>
				<p><?php echo __('Your URL to icons is set to ','category_icons').trailingslashit(ig_caticons_url()); ?></p>
            </div>
            <div class="form-field">
                <label><?php _e('Image File Types','category_icons');?></label>
                <input type="text" name="igcaticons_filetypes" size="50" value="<?php echo htmlspecialchars(get_option('igcaticons_filetypes')); ?>" />
                <p><?php _e("Separate by space or comma. E.g. 'jpg gif jpeg png'",'category_icons');?></p>
            </div>
            <div class="form-field">
                <label><?php _e('Maximum icon width','category_icons');?></label>
                <input type="text" name="igcaticons_max_width" size="3" value="<?php echo htmlspecialchars(get_option('igcaticons_max_width')); ?>" />
                <p><?php _e('Enter the maximum width of an icon.','category_icons');?></p>
            </div>	
            <div class="form-field">
                <label><?php _e('Maximum icon height','category_icons');?></label>
                <input type="text" name="igcaticons_max_height" size="3" value="<?php echo htmlspecialchars(get_option('igcaticons_max_height')); ?>" />
                <p><?php _e('Enter the maximum height of an icon.','category_icons');?></p>
            </div>
            <div class="form-field">
                <label><?php _e('Maximum number of icons','category_icons');?></label>
                <input type="text" name="igcaticons_max_icons" size="3" value="<?php echo htmlspecialchars(get_option('igcaticons_max_icons')); ?>" />
                <p><?php _e('Enter the maximum number of icons to display in front of the posts titles.','category_icons');?></p>
            </div>
            <div class="submit">
            	<input type="submit" name="info_update" value="<?php _e('Update Options','category_icons');?> &raquo;" />
            </div>	
        </div>
    </div>
    </form>
</div>
<?php
}

/**
 * Display the 'Template Tags' tab
 * @author Brahim Machkouri
 */
function bm_caticons_admintemplate() {
	$list = bm_caticons_template_code();
	
	$message = __('Remember that these locations are just where you COULD paste the tag, not where you MUST paste it. It\'s up to you to find the appropriate location within your template files. The tag is usually within the Loop.','category_icons');
	$message .=' '.__('Paste the following code in the listed files, at the line and column numbers displayed below.','category_icons');
	if ( 0 == count($list) ) 
		$message = __('There\'s nothing to do.','category_icons');
?>
<div class="wrap"><h2><?php 
			$ct = wp_get_theme();//current_theme_info();
			_e('Template Tags for','category_icons');
			echo ' '.$ct->name;
		?></h2>
    <h3><a target="_blank" href="http://www.category-icons.com/2008/03/12/function-get_cat_icon/">get_cat_icon()</a></h3>
<?php 
	if ( 0 < count($list) ) {	
		bm_caticons_codetemplate_display($message,$list,'get_cat_icon'); 
		if (get_option('igcaticons_templatecode_sidebar')==1) {
			echo '<h3><a target="_blank" href="http://www.category-icons.com/2008/03/12/function-put_cat_icon/">put_cat_icons()</a></h3>';
			$message = __('Replace the code','category_icons');
			bm_caticons_codetemplate_display($message,$list,'put_cat_icon'); 
		}
	}
?>
    
</div>
<?php
}

/**
 * Get the icons base path.
 * @author Ivan Georgiev 
 * @return array
 */
function ig_caticons_path() {
	$path = get_option('igcaticons_path');
	$def = ig_caticons_defupload();
	
	if (empty($path))
		return ABSPATH.$def[0];
	else
		return ABSPATH.$path;
}

/**
 * Get the icons base url.
 * @author Ivan Georgiev
 * @return array
 */
function ig_caticons_url() {
	$url = get_option('igcaticons_url');
	$def = ig_caticons_defupload();
	if (empty($url))
		return $def[1];
	else
		return $url;
}

/**
 * Get file types to show when selecting icons.
 * @author Ivan Georgiev
 * @return array
 */
function ig_caticons_filetypes() {
	$types = get_option('igcaticons_filetypes');
	if (empty($types))
		return get_option('fileupload_allowedtypes');
	else
		return $types;
}

/**
 * Get category icons
 * @author Ivan Georgiev
 * @param int $cat Category ID
 * @return array (priority, icon, small_icon)
 */
function bm_caticons_get_icons($cat=0) {
	global $wpdb, $caticons_datas;	
	
	$cat = (int) $cat;//$wpdb->escape($cat);
	$result = false;
	
	if (empty($caticons_datas)) {
		$datas = $wpdb->get_results($wpdb->prepare("SELECT cat_id, priority, icon, small_icon FROM $wpdb->ig_caticons"));
		foreach ($datas as $row) {
			$caticons_datas[$row->cat_id] = array ($row->priority, $row->icon, $row->small_icon);
		}
	}
	if (isset($caticons_datas[$cat])) $result = $caticons_datas[$cat];
	
	return $result;
}

/**
 * Get category icon with the hightest priority.
 * @param array $cats Array of Category IDs
 * @return array (cat_id, priority, icon, small_icon)
 * @author Oliver Weichhold
 */
function ow_caticons_get_priority_icon($cats) {
	global $wpdb;
	$instr = '';
	foreach($cats as $cat)
		$instr .= $wpdb->escape($cat).',';
	$instr = preg_replace('/,$/','', $instr); // Remove trailing comma
	if ($row = $wpdb->get_row($wpdb->prepare("SELECT cat_id, priority, icon, small_icon FROM $wpdb->ig_caticons WHERE cat_id IN($instr) ORDER BY priority DESC LIMIT 1")))
		return array($row->cat_id, $row->priority, $row->icon, $row->small_icon);
	else
		return false;
}

/**
 * Utility function to compute a rectangle to fit a given rectangle by maintaining the aspect ratio.
 * @author Ivan Georgiev
 * @param int $width Width of the rectangle
 * @param int $height Height of the rectangle
 * @param int $max_width Maximum Width of the new rectangle
 * @param int $max_height Maximum Height of the new rectangle
 * @param boolean $expand Expand the rectangle ? Default : false
 * @return array (width, height)
 */
function ig_caticons_fit_rect($width, $height, $max_width=-1, $max_height=-1, $expand=false) {
	$h = (int) $height;
	$w = (int) $width;
	if ($max_width>0 && ($w > $max_width || $expand)) {
		$w = $max_width;
		$h = floor(($w*$height)/$width);
	}
	if ($max_height>0 && $h >$max_height) {
		$h = $max_height;
		$w = floor(($h*$width)/$height);
	}
	return array($w,$h);
}

if (!function_exists('stripslaghes_gpc_arr')) {
	function stripslaghes_gpc_arr(&$arr) {
		if (get_magic_quotes_gpc()) {
			foreach(array_keys($arr) as $k) $arr[$k] = stripslashes($arr[$k]);
		}
	}
}

/**
 * Adds compatibility with Recent posts and recent comments, similar posts from Rob Marsh (http://rmarsh.com/)
 * Just use the tag {caticons} to display the category icon
 * @author Brahim Machkouri
 */
	function otf_caticons($option_key, $result, $ext) {
		$categories = get_the_category($result->ID);
		return get_cat_icon('cat='.$categories[0]->term_id.'&echo=0');
	}
