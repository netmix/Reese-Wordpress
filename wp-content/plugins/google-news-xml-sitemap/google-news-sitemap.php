<?
/* 
Plugin Name: Google News XML Sitemap
Plugin URI: http://chicagopressrelease.com/about/google-news-xml-sitemap-plugin-for-wordpress
Version: 1.0.3
Author: <a href="http://chicagopressrelease.com/">Chicago Press Release Services</a>
Description: Automatically generate an XML sitemap for inclusion to Google News 2.0. 

Installation:
==============================================================================
	1. Upload `google-news-sitemap-generator` directory to the `/wp-content/plugins/` directory
	2. Activate the plugin through the 'Plugins' menu in WordPress
	3. Move the file "google-news-sitemap.xml" into your blog root directory and CHMOD to 777 so it is writable
	4. Save/publish/delete a post to generate a Google News XML sitemap
*/

/*  Copyright 2010 Chicago Press Release Services

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
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

function get_category_keywords($newsID)
{
	global $wpdb;
	
	//Check for new >2.3 Wordpress taxonomy	
	if (function_exists("get_taxonomy") && function_exists("get_terms"))
		{
			//Get categoy names
			$categories = $wpdb->get_results("
					SELECT $wpdb->terms.name FROM $wpdb->term_relationships,  $wpdb->term_taxonomy,  $wpdb->terms
					WHERE $wpdb->term_relationships.term_taxonomy_id = $wpdb->term_taxonomy.term_taxonomy_id
					AND $wpdb->term_taxonomy.term_id =  $wpdb->terms.term_id
					AND $wpdb->term_relationships.object_id = $newsID
					AND $wpdb->term_taxonomy.taxonomy = 'category'");
				$i = 0;
				$categoryKeywords = "";
				foreach ($categories as $category)
				{
					if ($i>0){$categoryKeywords.= ", ";} //Comma seperator
					$categoryKeywords.= $category->name; //ammed string
					$i++;
				}
				
			//Get tags				
			$tags = $wpdb->get_results("
					SELECT $wpdb->terms.name FROM $wpdb->term_relationships,  $wpdb->term_taxonomy,  $wpdb->terms
					WHERE $wpdb->term_relationships.term_taxonomy_id = $wpdb->term_taxonomy.term_taxonomy_id
					AND $wpdb->term_taxonomy.term_id =  $wpdb->terms.term_id
					AND $wpdb->term_relationships.object_id = $newsID
					AND $wpdb->term_taxonomy.taxonomy = 'post_tag'");
				$i = 0;
				$tagKeywords = "";
				foreach ($tags as $tag)
				{
					if ($i>0){$tagKeywords.= ", ";} //Comma seperator
					$tagKeywords.= $tag->name; //ammed string
					$i++;
				}	

		}
		
	//Old Wordpress database <2.3
	else
		{
			$categories = $wpdb->get_results("SELECT category_id FROM $wpdb->post2cat WHERE post_id=$newsID");
			$i = 0;
			$categoryKeywords = "";
			foreach ($categories as $category)
			{
				if ($i>0){$categoryKeywords.= ", ";} //Comma seperator
				$categoryKeywords.= get_catname($category->category_id); //ammed string
				$i++;
			}
		}
	
	if (get_option('googlenewssitemap_tagkeywords') == 'on')
	{
		if($tagKeywords!=NULL)
		{
			$categoryKeywords = $categoryKeywords.', '.$tagKeywords; //IF tags are included 
		}
	} 
	
	 return $categoryKeywords; //Return post category names as keywords
}

function write_google_news_sitemap() 
{

	global $wpdb;
	// Fetch options from database
	$permalink_structure = $wpdb->get_var("SELECT option_value FROM $wpdb->options 
					WHERE option_name='permalink_structure'");
	$siteurl = $wpdb->get_var("SELECT option_value FROM $wpdb->options
				WHERE option_name='siteurl'");

	// Output XML header
	
	// Begin urlset			
	$xmlOutput.= "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\" xmlns:n=\"http://www.google.com/schemas/sitemap-news/0.9\">\n";
	
	//Credit
	$xmlOutput.= "<!-- Generated with the Google News XML Sitemap plugin for Wordpress -->\n";
	$xmlOutput.= "<!-- Created by Chicago Press Release Services | http://chicagopressrelease.com -->\n";
	$xmlOutput.= "<!-- http://wordpress.org/extend/plugins/google-news-xml-sitemap/ -->\n";
	
	//Show either Posts or Pages or Both
	if (get_option('googlenewssitemap_includePages') == 'on' && get_option('googlenewssitemap_includePosts') == 'on')
		$includeMe = 'AND (post_type="page" OR post_type = "post")';
	
	elseif (get_option('googlenewssitemap_includePages') == 'on')
		$includeMe = 'AND post_type="page"';
	
	elseif (get_option('googlenewssitemap_includePosts') == 'on')
		$includeMe = 'AND post_type="post"';
	
	//Exclude categories	
	if (get_option('googlenewssitemap_excludeCat')<>NULL)
	{
		$exPosts = get_objects_in_term(get_option('googlenewssitemap_excludeCat'),"category");
		$includeMe.= ' AND ID NOT IN ('.implode(",",$exPosts).')';
	}
	
	//Limit to last 2 days, 50,000 items					
	$rows = $wpdb->get_results("SELECT ID, post_date_gmt, post_title
						FROM $wpdb->posts 
						WHERE post_status='publish' 
						AND (DATEDIFF(CURDATE(), post_date_gmt)<=2)
						$includeMe
						ORDER BY post_date_gmt DESC
						LIMIT 0, 50000");						
	
	// Output sitemap data
	foreach($rows as $row){
		$xmlOutput.= "\t<url>\n";
		$xmlOutput.= "\t\t<loc>";
		$xmlOutput.= get_permalink($row->ID);
		$xmlOutput.= "</loc>\n";
		$xmlOutput.= "\t\t<n:news>\n";
		
		$xmlOutput.= "\t\t\t<n:publication>\n";
		$xmlOutput.= "\t\t\t\t<n:name>";
		$xmlOutput.= htmlspecialchars(get_option('blogname'));
		$xmlOutput.= "</n:name>\n";
		$xmlOutput.= "\t\t\t\t<n:language>";
		$xmlOutput.= get_option('rss_language');
		$xmlOutput.= "</n:language>\n";
		$xmlOutput.= "\t\t\t</n:publication>\n";
		$xmlOutput.= "\t\t\t<n:publication_date>";
		$thedate = substr($row->post_date_gmt, 0, 10);
		$xmlOutput.= $thedate;
		$xmlOutput.= "</n:publication_date>\n";
		$xmlOutput.= "\t\t\t<n:title>";
		$xmlOutput.= htmlspecialchars($row->post_title);
		$xmlOutput.= "</n:title>\n";
		$xmlOutput.= "\t\t\t<n:keywords>";
		
		//Use the categories for keywords
		$xmlOutput.= get_category_keywords($row->ID);
		
		$xmlOutput.= "</n:keywords>\n"; 
		$xmlOutput.= "\t\t</n:news>\n";
		$xmlOutput.= "\t</url>\n";
	}
	
	// End urlset
	$xmlOutput.= "</urlset>\n";
	$xmlOutput.= "<!-- Last build time: ".date("F j, Y, g:i a")."-->";
	
	$xmlFile = ABSPATH."/google-news-sitemap.xml";
	$fp = fopen($xmlFile, "w+"); // open the cache file "google-news-sitemap.xml" for writing
	fwrite($fp, $xmlOutput); // save the contents of output buffer to the file
	fclose($fp); // close the file
	
}

if(function_exists('add_action')) //Stop error when directly accessing the PHP file
{
	add_action('publish_post', 'write_google_news_sitemap');
	add_action('save_post', 'write_google_news_sitemap');
	add_action('delete_post', 'write_google_news_sitemap');
	add_action('transition_post_status', 'write_google_news_sitemap',10, 3); //Future scheduled post action fix
	
	//Any changes to the settings are executed on change
	add_action('update_option_googlenewssitemap_includePosts', 'write_google_news_sitemap', 10, 2);
	add_action('update_option_googlenewssitemap_includePages', 'write_google_news_sitemap', 10, 2);
	add_action('update_option_googlenewssitemap_tagkeywords', 'write_google_news_sitemap', 10, 2);
	add_action('update_option_googlenewssitemap_excludeCat', 'write_google_news_sitemap', 10, 2);
}
else  //Friendly error message :)
{
	?>
	<p style="color:#FF0000"><em>Accessing this file directly will not generate the sitemap.</em></p>
	<p>The sitemap will be generated automatically when you save/pubish/delete a post from the standard Wordpress interface.</p>
	<p><strong>Instructions</strong></p>
	<p>1. Upload `google-news-sitemap-generator` directory to the `/wp-content/plugins/` directory<br />
	2. Activate the plugin through the 'Plugins' menu in WordPress<br />
	3. Move the file "google-news-sitemap.xml" into your blog root directory and CHMOD to 777 so it is writable<br />
	4. Save/publish/delete a post to generate the sitemap</p>
	<?
}
//
// Admin panel options.... //
//

add_action('admin_menu', 'show_googlenewssitemap_options');

function show_googlenewssitemap_options() {
    // Add a new submenu under Options:
    add_options_page('Google News Sitemap Generator Plugin Options', 'Google News Sitemap', 8, 'googlenewssitemap', 'googlenewssitemap_options');
	
	//Add options for plugin
	add_option('googlenewssitemap_includePosts', 'on');
	add_option('googlenewssitemap_includePages', 'off');
	add_option('googlenewssitemap_tagkeywords', 'off');
	add_option('googlenewssitemap_excludeCat', '');
	
}
//
// Admin page HTML //
//
function googlenewssitemap_options() { ?>
<style type="text/css">
div.headerWrap { background-color:#e4f2fds; width:200px}
#options h3 { padding:7px; padding-top:10px; margin:0px; cursor:auto }
#options label { width: 300px; float: left; margin-left: 10px; }
#options input { float: left; margin-left:10px}
#options p { clear: both; padding-bottom:10px; }
#options .postbox { margin:0px 0px 10px 0px; padding:0px; }
</style>
<div class="wrap">
<form method="post" action="options.php" id="options">
<?php wp_nonce_field('update-options') ?>
<h2>Google News XML Sitemap Options</h2>

<div class="postbox">
<h3 class="hndle">Information</h3>
	<div style="text-decoration:none; padding:10px">
	
	<div style="width:180px; text-align:center; float:right; font-size:10px; font-weight:bold">
<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=MHCWGNG5C73ZQ&lc=US&item_name=Chicago%20Press%20Release%20Services&item_number=wordpress&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted" target="_blank">
<img src="https://www.paypal.com/en_GB/i/btn/btn_donateCC_LG.gif" border="0" style="padding-bottom:10px" /></a><br />
Please donate to keep this plugin and future updates free!
</div>

	<a href="http://wordpress.org/extend/plugins/google-news-xml-sitemap/" style="text-decoration:none" target="_blank">Google News Sitemap Generator homepage</a> <small>- Report a bug or suggest a feature</small><br />
	<br />

	<a href="http://www.google.com/webmasters/tools/" style="text-decoration:none" target="_blank">Google Webmaster Tools</a> <small>- Submit Google News sitemap</small> <br /><br />

<a href="http://www.google.com/support/news_pub/bin/answer.py?answer=74288&topic=11666" style="text-decoration:none" target="_blank">Google News Sitemap Guidelines</a> <small>- Detailed outline of sitemaps specification</small><br />
<br />
		
		<a href="http://chicagopressrelease.com/about/google-news-xml-sitemap-plugin-for-wordpress" style="text-decoration:none" target="_blank">Plugin developer page</a> <small>- More information about the developer of this plugin</small><br />
		
</div>
</div>

<div class="postbox">
<h3 class="hndle">Sitemap contents</h3>

		<p>
			<?php
				if (get_option('googlenewssitemap_includePosts') == 'on') {echo '<input type="checkbox" name="googlenewssitemap_includePosts" checked="yes" />';}
				else {echo '<input type="checkbox" name="googlenewssitemap_includePosts" />';}
			?>
			<label>Include posts in Google News sitemap <small>(Default)</small></label>
		</p>
		<p>
			<?php
				if (get_option('googlenewssitemap_includePages') == 'on') {echo '<input type="checkbox" name="googlenewssitemap_includePages" checked="yes" />';}
				else {echo '<input type="checkbox" name="googlenewssitemap_includePages" />';}
			?>
			<label>Include pages in Google News sitemap</label>
		</p>
<br style="clear:both"/>			
</div>
		
<div class="postbox">
<h3 class="hndle">Sitemap keywords</h3>
		<p>
			<?php
				if (get_option('googlenewssitemap_tagkeywords') == 'on') {echo '<input type="checkbox" name="googlenewssitemap_tagkeywords" checked="yes" />';}
				else {echo '<input type="checkbox" name="googlenewssitemap_tagkeywords" />';}
			?>
			<label>Use post tags as sitemap keywords <small><a href="http://www.google.com/support/news_pub/bin/answer.py?answer=74288&topic=11666" style="text-decoration:none" target="_blank">More Info</a></small></label>
		</p>
<br style="clear:both"/>		
</div>

<div class="postbox">
<h3 class="hndle">Exclude categories</h3>

<div style="padding:10px">Select the categories you would like to <em><strong>exclude</strong></em> from the Google News Sitemap:</div>

<div style="padding:10px">
<?php
  //Categories to exclude from sitemap
  $excludedCats = get_option('googlenewssitemap_excludeCat');
  if (!is_array($excludedCats)) 
  $excludedCats= array();
  $categories = get_categories('hide_empty=1');
  foreach ($categories as $cat) {
  	if (in_array($cat->cat_ID,$excludedCats))
	{
  		echo '<label class="selectit"><input type="checkbox" name="googlenewssitemap_excludeCat[\''.$cat->cat_ID.'\']" value="'.$cat->cat_ID.'" checked="yes" /><span style="padding-left:5px">'.$cat->cat_name.'</span></label>';
  	}
	else
	{
		echo '<label class="selectit"><input type="checkbox" name="googlenewssitemap_excludeCat[\''.$cat->cat_ID.'\']" value="'.$cat->cat_ID.'" /><span style="padding-left:5px">'.$cat->cat_name.'</span></label>';
	}
  }
?>
<br style="clear:both"/>
</div>

</div>
		<input type="hidden" name="action" value="update" />
		<input type="hidden" name="page_options" value="googlenewssitemap_includePosts,googlenewssitemap_includePages,googlenewssitemap_tagkeywords,googlenewssitemap_excludeCat" />
		<div style="clear:both;padding-top:0px;"></div>
		<p class="submit"><input type="submit" name="Submit" value="<?php _e('Update Options') ?>" /></p>
		<div style="clear:both;padding-top:20px;"></div>
		</form>
			
</div>

<?php } ?>