<?php
/*
Plugin Name: Category Pie
Plugin URI: http://peterherrel.com/wordpress/plugins/category-pie/
Description: The Category Pie plugin for WordPress adds a bit of extra flavor to those otherwise boring category administration pages. It's called Category Pie, but it works for tags and custom taxonomies too!
Version: 0.2
Author: donutz
Author URI: http://peterherrel.com/
License: GPL2
Text Domain: pjh_cat_pie
Domain Path: /lang
*/

/*  Copyright 2012  Peter J. Herrel  (email : peterherrel [at] gmail [dot] com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

new Category_Pie;

class Category_Pie
{	
	var $version = '0.2';
	var $plugin_dir_url = '';

	function Category_Pie()
	{
		$this->__construct();
	}	 
	function __construct()
	{
		$this->plugin_dir_url = trailingslashit( plugins_url( dirname( plugin_basename( __FILE__ ) ) ) );

		new Category_Pie_Options;
	    
		if ( is_admin() )
		{
			global $pagenow;
			if( $pagenow != 'edit-tags.php' ) return;
			if( isset( $_GET['action'] ) ) return;
			add_action( 'admin_head', array( &$this, 'js' ) );
			add_action( 'admin_init', array( &$this, 'add_html' ) );
		}
	}
	function init()
	{    
		load_plugin_textdomain( 'pjh_cat_pie', false, $this->plugin_dir_url . 'lang/' );
	}
	public function add_html()
	{
		global $taxnow;  	
		add_action( $taxnow . '_pre_add_form', array( &$this, 'html' ) );
	}
	public function html( $taxonomy ) 
	{
		$tax = get_taxonomy( $taxonomy );
		if( ! is_taxonomy_hierarchical( $taxonomy ) ) : 
			$title = '<h3>'.$tax->labels->name.' ' . __( 'Top Ten', 'pjh_cat_pie' ) . '</h3>';
		else : 
			$title = '<h3>'.$tax->labels->name.'</h3>';
		endif;
		echo '<div id="cat-pie-admin" class="hide-if-no-js">
		<h3>' . $title . '</h3>
		<div id="cat-pie-admin-inside" class="form-wrap"></div>
		</div>';
	}
	public function js()
	{
		$t = __( 'Term', 'pjh_cat_pie' );
		$c = __( 'Count', 'pjh_cat_pie' );
?>	
<!--Load the AJAX API-->
<script type="text/javascript" src="https://www.google.com/jsapi"></script>
<script type="text/javascript">/* <![CDATA[ */
google.load('visualization', '1.0', {'packages':['corechart']});
google.setOnLoadCallback(drawChart);
function drawChart() {
	var data = new google.visualization.DataTable();
	data.addColumn('string', '<?php echo $t; ?>');
	data.addColumn('number', '<?php echo $c; ?>');
	data.addRows([<?php global $taxonomy;
		$tax = get_taxonomy( $taxonomy );
		$tax_terms = get_terms( $taxonomy, 'orderby=count&order=DESC&hide_empty=1' );
		if( ! is_taxonomy_hierarchical( $taxonomy ) ) $tax_terms = array_slice( get_terms( $taxonomy, 'orderby=count&order=DESC&hide_empty=1' ), 0, 10 );
		foreach( $tax_terms as $array => $data ) {	    					
			$name = addslashes( $data->name );
			$count = $data->count; ?>['<?php echo $name; ?> (<?php echo $count; ?>)', <?php echo $count; ?>],<?php } ?>]);
	var options = {'title':'',
		'width':'100%',
		'height':'auto',
		'chartArea':{left:10,top:0,width:'90%',height:'90%'},
		'tooltip':{showColorCode:true,text:'percentage',trigger:'hover'},
		'is3D':true
	};
	var chart = new google.visualization.PieChart(document.getElementById('cat-pie-admin-inside'));
	chart.draw(data, options);
}
/* ]]> */
</script>
<style type="text/css">
#cat-pie-admin, #cat-pie-admin-inside {width: 100%;display:block;clear:both;background-image:none;background-color:transparent;margin:0px 0px;padding:0px 0px;}
</style>
<?php }
}
class Category_Pie_Options
{
	function Category_Pie_Options()
	{
		$this->__construct();
	}
	function __construct()
	{
		$this->plugin_dir_url = trailingslashit( plugins_url( dirname( plugin_basename( __FILE__ ) ) ) );

		if ( is_admin() )
		{
			add_filter( 'plugin_row_meta', array( &$this, 'links' ), 10, 2 );
		}
	}
	function base()
	{
		return plugin_basename( __FILE__ );
	}
	function links( $links, $file )
	{
		$base = Category_Pie_Options::base();
		if ( $file == $base )
		{
			$links[] = '<a href="http://peterherrel.com/donate/">' . __( 'Donate', 'pjh_cat_pie' ) . '</a>';
		}
		return $links;
	}
}

/**
 * That's all folks!
 */
 
?>