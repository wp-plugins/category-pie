<?php
/**
 * Plugin Name: Category Pie
 * Plugin URI: http://peterherrel.com/wordpress/plugins/category-pie/
 * Description: The Category Pie plugin for WordPress adds a bit of extra flavor to those otherwise boring category administration pages. It's called Category Pie, but it works for tags and custom taxonomies too!
 * Version: 1.0
 * Author: donutz
 * Author URI: http://peterherrel.com/
 * License: GPL3
 * Text Domain: category_pie
 * Domain Path: /lang
 *
 * *************************************************************************************************
 *
 * @author      Peter J. Herrel  (email : peterherrel [at] gmail [dot] com)
 * @copyright   2012-2014  Peter J. Herrel
 * 
 * @link https://wordpress.org/plugins/category-pie/
 * @link https://github.com/diggy/Category-Pie
 * @link https://github.com/diggy/Category-Pie/wiki
 * @link http://peterherrel.com/wordpress/plugins/category-pie/
 *
 * *************************************************************************************************
 *
 * LICENSE
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 3, as 
 * published by the Free Software Foundation.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 * 
 * ************************************************************************************************/

/*
 * Security
 *
 * check if ABSPATH is defined
 */
if( ! defined( 'ABSPATH' ) )
    exit;

if( ! class_exists( 'Category_Pie' ) ) :

/**
 * Category Pie main class
 */
class Category_Pie
{
    public $plugin_dir_url  = '';

    /**
     * Constructor
     *
     * @return  void
     */
    public function __construct()
    {
        $this->plugin_dir_url = trailingslashit( plugins_url( dirname( plugin_basename( __FILE__ ) ) ) );

        if( ! is_admin() )
            return;

        register_activation_hook( __FILE__, array( $this , 'register_activation_hook' ) );

        add_action( 'init',             array( $this, 'init' ), 10 );
        add_action( 'admin_init',       array( $this, 'check_compat' ) );

        add_filter( 'plugin_row_meta',  array( $this, 'plugin_row_meta' ), 10, 2 );

        if( false === self::is_compatible() )
            return;

        global $pagenow;

        if( $pagenow != 'edit-tags.php' || isset( $_GET['action'] ) )
            return;

        add_action( 'admin_init',   array( $this, 'admin_init' ) );
        add_action( 'admin_head',   array( $this, 'admin_head' ) );
    }
    /**
     * Load texdomain
     *
     * @return  void
     */
    public function init()
    {
        load_plugin_textdomain( 'category_pie', false, $this->plugin_dir_url . 'lang/' );
    }
    /**
     * Category Pie inject HTML
     *
     * @return  void
     */
    public function admin_init()
    {
        global $taxnow;

        $hook = apply_filters( 'category_pie_taxnow_hook', "{$taxnow}_pre_add_form", $taxnow );

        add_action( $hook, array( $this, 'html' ), 10, 1 );
    }
    /**
     * Category Pie JS
     *
     * Documentation: https://developers.google.com/chart/interactive/docs/gallery/piechart
     *
     * @return  void
     */
    public function admin_head()
    {
        global $taxonomy;

        $tax        = get_taxonomy( $taxonomy );
        $tax_terms  = get_terms( $taxonomy, 'orderby=count&order=DESC&hide_empty=1' );

        if( empty( $tax_terms ) || is_wp_error( $tax_terms ) )
            return;

        if( ! is_taxonomy_hierarchical( $taxonomy ) )
            $tax_terms = array_slice( $tax_terms, 0, 10 );
?>
<!--Load the AJAX API-->
<script type="text/javascript" src="https://www.google.com/jsapi"></script>
<script type="text/javascript">/* <![CDATA[ */
google.load("visualization", "1", {packages:["corechart"]});
google.setOnLoadCallback(drawChart);
function drawChart() {
    var data = google.visualization.arrayToDataTable([<?php echo $this->datarows( $tax_terms ); ?>]);
    var options = <?php echo $this->chart_options(); ?>;
    var chart = new google.visualization.PieChart(document.getElementById('cat-pie-admin-inside'));
    chart.draw(data, options);
}
/* ]]> */
</script>
<!--Category Pie CSS-->
<style type="text/css">#cat-pie-admin,#cat-pie-admin-inside{width:100%;max-width:100%;display:block;clear:both;background-image:none;background-color:transparent;margin:0;padding:0;}</style>
<?php
    }
    /**
     * Category Pie HTML
     *
     * @param   string $taxonomy    taxonomy name
     * @return  void
     */
    public function html( $taxonomy ) 
    {
        $tax        = get_taxonomy( $taxonomy );
        $tax_terms  = get_terms( $taxonomy, 'orderby=count&order=DESC&hide_empty=1' );

        if( empty( $tax_terms ) || is_wp_error( $tax_terms ) )
            return;

        $title  = $tax->labels->name;

        if( ! is_taxonomy_hierarchical( $taxonomy ) )
            $title = $title . ' ' . __( 'Top Ten', 'category_pie' );

        echo '<div class="form-wrap hide-if-no-js">
                <div id="cat-pie-admin">
                    <h3>' . $title . '</h3>
                    <div id="cat-pie-admin-inside" class="form-wrap">
                    </div>
                </div>
        </div>';
    }
    /**
     * Category Pie Data Rows
     *
     * @param   array   $tax_terms  array of taxonomy terms
     * @return  string              JS array of pie chart data
     */
    private function datarows( $tax_terms )
    {
        $datarows = array();

        $datarows[] = sprintf( '["%s", "%s"]', __( 'Term', 'category_pie' ), __( 'Count', 'category_pie' ) );

        foreach( $tax_terms as $array => $data )
            $datarows[] = '["' . wp_slash( $data->name ) . ' (' . $data->count . ')", ' . $data->count . ']';

        return apply_filters( 'category_pie_datarows', implode( ',', $datarows ) );
    }
    /**
     * Category Pie Chart Options
     *
     * Documentation: https://developers.google.com/chart/interactive/docs/gallery/piechart#Configuration_Options
     *
     * @return  string  JSON encoded string of pie chart options
     */
    private function chart_options()
    {
        return json_encode( apply_filters( 'category_pie_chart_options', array(
             'backgroundColor'  =>  array(
                 'stroke'           => '#e5e5e5'
                ,'strokeWidth'      => '0'
                ,'fill'             => 'transparent'
            )
            ,'chartArea'        =>  array(
                 'backgroundColor'  => array(
                     'stroke'           => '#e5e5e5'
                    ,'strokeWidth'      => '0'
                )
                ,'left'             => 'auto'
                ,'top'              => 'auto'
                ,'width'            => '99%'
                ,'height'           => '80%'
            )
            //,'colors'         => array()
            ,'enableInteractivity' => true
            //,'fontSize'       => ''
            ,'fontName'         => '"Open Sans",sans-serif'
            ,'forceIFrame'      => false
            //,'height'           => ''
            ,'is3D'             => false
            ,'legend'           => array(
                 'alignment'        => 'start'
                ,'position'         => ( is_rtl() ? 'left' : 'right' )
                ,'maxLines'         => '1'
                ,'textStyle'        => array(
                     'color'            => '#666'
                    ,'fontName'         => '"Open Sans",sans-serif'
                    ,'fontSize'         => '11'
                    ,'bold'             => false
                    ,'italic'           => false
                )
            )
            ,'pieHole'          => '0.4'
            ,'pieSliceBorderColor' => 'white'
            ,'pieSliceText'     => 'percentage'
            //,'pieSliceTextStyle' => array()
            ,'pieStartAngle'    => '0'
            ,'reverseCategories' => false
            ,'pieResidueSliceColor' => '#ccc'
            ,'pieResidueSliceLabel' => 'Other'
            //,'slices'         => array()
            //,'sliceVisibilityThreshold' => ''
            ,'title'            =>  ''
            //,'titleTextStyle' => array()
            ,'tooltip'          =>  array(
                 'showColorCode'    => true
                ,'text'             => 'percentage'
                //,'textStyle'      => array()
                ,'trigger'          => 'focus'
            )
            //,'width'          =>  ''
        ) ) );
    }
    /**
     * Activation hook
     *
     * @return  void
     */
    public function register_activation_hook()
    {
        if( false !== self::is_compatible() )
            return;

        deactivate_plugins( plugin_basename( __FILE__ ) );

        wp_die( __( 'Category Pie requires WordPress 3.6 or higher.', 'category_pie' ) );
    }
    /**
     * Compatibility check
     *
     * @return  void
     */
    public function check_compat()
    {
        if( false !== self::is_compatible() )
            return;

        if( ! is_plugin_active( plugin_basename( __FILE__ ) ) )
            return;

        deactivate_plugins( plugin_basename( __FILE__ ) );

        add_action( 'admin_notices', array( $this, 'admin_notices' ) );

        if( isset( $_GET['activate'] ) )
            unset( $_GET['activate'] );
    }
    /**
     * Admin notice
     *
     * @return  void
     */
    public function admin_notices()
    {
        printf( '<div class="error" id="message"><p><strong>%s</strong></p></div>', __( 'Category Pie requires WordPress 3.6 or higher.', 'category_pie' ) );
    }
    /**
     * Version compare
     *
     * @return  void
     */
    public static function is_compatible()
    {
        if( version_compare( $GLOBALS['wp_version'], '3.6', '<' ) )
            return false;

        return true;
    }
    /**
     * Plugin row meta
     *
     * @param   array   $links
     * @param   string  $file
     * @return  array
     */
    public function plugin_row_meta( $links, $file )
    {
        if( $file == plugin_basename( __FILE__ ) )
            return array_merge( $links, array(
                 sprintf( '<a href="%s" target="_blank">%s</a>', 'https://github.com/diggy/Category-Pie/wiki', __( 'Wiki', 'category_pie' ) )
                ,sprintf( '<a href="%s" target="_blank">%s</a>', 'https://wordpress.org/support/plugin/category-pie', __( 'Support', 'category_pie' ) )
                ,sprintf( '<a href="%s" target="_blank">%s</a>', 'https://github.com/diggy/Category-Pie', __( 'Repository', 'category_pie' ) )
                ,sprintf( '<a href="%s" target="_blank">%s</a>', 'http://peterherrel.com/donate/', __( 'Donate', 'category_pie' ) )
            ) );

        return $links;
    }
}

/*
 * Initialize class
 */
new Category_Pie;

endif; // end class_exists check

/* end of file category-pie.php */
