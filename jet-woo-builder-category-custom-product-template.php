<?php

/*
Plugin Name: JetWooBuilder - Category Custom Product Template
Plugin URI: https://runthings.dev
Description: Apply a custom JetWooBuilder product template at the WooCommerce category level
Version: 1.2.0
Author: Matthew Harris, runthings.dev
Author URI: https://runthings.dev/
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Copyright 2022 Matthew Harris

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

if ( ! defined( 'WPINC' ) ) {
	die;
}

class runthingsJetWooBuilderCategoryCustomProductTemplate {

    const NO_TEMPLATE_SET = -1;

    public function __construct()
    {
        // override template
        add_filter( 'jet-woo-builder/custom-single-template', [$this, 'runthings_custom_woo_product_template'] );

        // insert fields ui into add / edit taxonomy pages
        add_action( "product_cat_edit_form_fields", [$this, 'taxonomy_product_cat_custom_fields_edit'] );
        add_action( "product_cat_add_form_fields", [$this, 'taxonomy_product_cat_custom_fields_add'] );
        add_action( 'edited_product_cat', [$this, 'taxonomy_product_cat_custom_fields_save'], 10, 2 );
        add_action( 'created_product_cat', [$this, 'taxonomy_product_cat_custom_fields_save'], 10, 2 );

        // insert custom columns into listing
        add_filter( 'manage_edit-product_cat_columns', [$this, 'manage_edit_columns'] );
        add_filter( 'manage_product_cat_custom_column', [$this, 'manage_edit_columns_content_product_template'], 10, 3 );
        add_filter( 'manage_product_cat_custom_column', [$this, 'manage_edit_columns_content_product_template_priority'], 10, 3 );
        add_filter( 'manage_edit-product_cat_sortable_columns', [$this, 'manage_sortable_columns'] );
        add_filter( 'terms_clauses', [$this, 'modify_orderby_query'], 10, 3 );
    }

    /**
     * Apply a custom JetWooBuilder template at the category level
     * 
     * @thanks https://stackoverflow.com/questions/27385920/woocommerce-get-current-product-id/43472722#43472722
     * @thanks https://stackoverflow.com/questions/15303031/woocommerce-get-category-for-product-page/15334415#15334415
     */
    public function runthings_custom_woo_product_template( $template ) {
        if(!is_product()) {
            return $template;
        }

        $id = get_the_ID();

        // check if custom template set on individual product
        if (get_post_meta( $id, '_jet_woo_template', true )) {
            return $template;
        };

        $terms = get_the_terms( $id, 'product_cat' );

        $template_id = self::NO_TEMPLATE_SET;
        $priority = 0;

        foreach ($terms as $term) {
            $meta_template_id = get_term_meta($term->term_id, 'runthings-jetwoobuilder-template-id', true);

            if ($meta_template_id !== '') {
                $meta_priority =  get_term_meta($term->term_id, 'runthings-jetwoobuilder-priority', true);

                if($template_id == self::NO_TEMPLATE_SET) {
                    // first template found, set the value
                    $template_id = $meta_template_id;
                    $priority = $meta_priority;
                } else {
                    // template already found, check if new one has higher priority
                    if(intval($meta_priority) > $priority) {
                        $template_id = intval($meta_template_id);
                        $priority = intval($meta_priority);
                    }
                }
            }
        }

        if($template_id !== self::NO_TEMPLATE_SET) {
            $template = $template_id;
        }

        return $template;
    }

    /**
     * Add in a taxonomy field template edit option
     * 
     * @note only difference between edit and add is that edit uses div and add uses tr
     * @thanks https://stackoverflow.com/questions/33907177/add-custom-taxonomy-metadata-field-to-wordpress/36200866#36200866
     * @thanks https://developer.wordpress.org/reference/hooks/taxonomy_add_form_fields/
     * @thanks https://www.smashingmagazine.com/2015/12/how-to-use-term-meta-data-in-wordpress/
     */
    public function taxonomy_product_cat_custom_fields_edit ( $term ) {
        $selected_template_id = get_term_meta( $term->term_id, 'runthings-jetwoobuilder-template-id', true );
        $priority = get_term_meta( $term->term_id, 'runthings-jetwoobuilder-priority', true );
        ?>
            </tbody>
        </table>
        <?php $this->echo_intro_text(); ?>
        <table class="form-table" role="presentational">
            <tbody>
                <tr class="form-field term-runthings-jetwoobuilder-template-id-wrap">
                    <th scope="row">
                        <label for="runthings-jetwoobuilder-template-id"><?php _e( 'Product Template', 'runthings_jetwoobuilder_category_template' );?></label>
                    </th>
                    <td>
                        <select name="runthings-jetwoobuilder[template-id]" id="runthings-jetwoobuilder-template-id" class="postform">
                            <?php 
                            $templates = $this->get_single_templates();
                            foreach ($templates as $template_id => $template_name) {
                                $selected = ($selected_template_id == $template_id ? "selected" : "");
                                ?><option <?php echo $selected; ?> value="<?php echo $template_id; ?>"><?php esc_html_e($template_name, 'runthings_jetwoobuilder_category_template'); ?></option><?php
                            }
                            ?>
                        </select>
                        <p class="description"><?php esc_html_e('Select the product template to use for products in this taxonomy.', 'runthings_jetwoobuilder_category_template'); ?></p>
                    </td>
                </tr>   
                <tr class="form-field term-runthings-jetwoobuilder-priority-wrap">
                    <th scope="row">
                        <label for="runthings_jetwoobuilder_priority"><?php _e( 'Template Priority', 'runthings_jetwoobuilder_category_template' );?></label>
                    </th>
                    <td>
                        <input name="runthings-jetwoobuilder[priority]" id="runthings_jetwoobuilder_priority" type="number" value="<?php echo $priority; ?>" size="40" />
                        <p class="description"><?php esc_html_e('Default priority is 0.', 'runthings_jetwoobuilder_category_template'); ?></p>
                    </td>
                </tr>
            </tbody>
        </table>
        <table class="form-table" role="presentational">
            <tbody>
        <?php
    }

    /**
     * Add in a taxonomy field template add option
     * 
     * @thanks https://stackoverflow.com/questions/33907177/add-custom-taxonomy-metadata-field-to-wordpress/36200866#36200866
     * @thanks https://developer.wordpress.org/reference/hooks/taxonomy_add_form_fields/
     */
    public function taxonomy_product_cat_custom_fields_add ( $term ) {
        ?>
        <?php $this->echo_intro_text(); ?>
        <div class="form-field term-runthings-jetwoobuilder-template-id-wrap">
            <label for="runthings-jetwoobuilder-template-id"><?php _e( 'Product Template', 'runthings_jetwoobuilder_category_template' );?></label>
            <select name="runthings-jetwoobuilder[template-id]" id="runthings-jetwoobuilder-template-id" class="postform">
                <?php 
                $templates = $this->get_single_templates();
                foreach ($templates as $template_id => $template_name) {
                    ?><option value="<?php echo $template_id; ?>"><?php esc_html_e($template_name, 'runthings_jetwoobuilder_category_template'); ?></option><?php
                }
                ?>
            </select>
            <p class="description"><?php esc_html_e('Select the product template to use for products in this taxonomy.', 'runthings_jetwoobuilder_category_template'); ?></p>
        </div>   
        <div class="form-field term-runthings-jetwoobuilder-priority-wrap">
            <label for="runthings_jetwoobuilder_priority"><?php _e( 'Template Priority', 'runthings_jetwoobuilder_category_template' );?></label>
            <input name="runthings-jetwoobuilder[priority]" id="runthings_jetwoobuilder_priority" type="number" size="40" />
            <p class="description"><?php esc_html_e('Default priority is 0.', 'runthings_jetwoobuilder_category_template'); ?></p>
        </div>
        <?php
    }

    /**
     * Save custom terms meta
     */
    public function taxonomy_product_cat_custom_fields_save( $term_id, $tt_id ) {
        if (!isset($_POST['runthings-jetwoobuilder'])) {
            return;
        }

        if( isset($_POST['runthings-jetwoobuilder']['template-id']) ){
            $template_id = $_POST['runthings-jetwoobuilder']['template-id'];
            if($template_id != '') {
                $template_id = intval($_POST['runthings-jetwoobuilder']['template-id']);
            }
            update_term_meta( $term_id, 'runthings-jetwoobuilder-template-id', $template_id );
        }

        if( isset( $_POST['runthings-jetwoobuilder']['priority'] )){
            $priority = $_POST['runthings-jetwoobuilder']['priority'];
            if ($priority !== '') {
                $priority = intval( $_POST['runthings-jetwoobuilder']['priority'] );
            }
            update_term_meta( $term_id, 'runthings-jetwoobuilder-priority', $priority );
        }
    }

    /**
     * Add the custom column to the taxonomy management page
     * 
     * @since 1.1.0
     */
    public function manage_edit_columns( $columns ){
        $columns['product_template'] = __( 'Product Template', 'runthings_jetwoobuilder_category_template' );
        $columns['product_template_priority'] = __( 'Product Template Priority', 'runthings_jetwoobuilder_category_template' );
        return $columns;
    }

    /**
     * Add the custom column content to the taxonomy management page
     * 
     * @since 1.1.0
     */
    public function manage_edit_columns_content_product_template( $content, $column_name, $term_id ){
        if( $column_name !== 'product_template' ){
            return $content;
        }

        $term_id = absint( $term_id );
        $term_meta = get_term_meta( $term_id, 'runthings-jetwoobuilder-template-id', true );

        if( !empty( $term_meta ) ){
            $content .= esc_attr( get_the_title($term_meta) );
        } else {
            $content .= "<em>(Default)</em>";
        }

        return $content;
    }

    /**
     * Add the custom column content to the taxonomy management page
     * 
     * @since 1.1.0
     */
    public function manage_edit_columns_content_product_template_priority( $content, $column_name, $term_id ){
        if( $column_name !== 'product_template_priority' ){
            return $content;
        }

        $term_id = absint( $term_id );
        $term_meta = get_term_meta( $term_id, 'runthings-jetwoobuilder-priority', true );

        if( !empty( $term_meta ) ){
            $content .= esc_attr( $term_meta );
        }

        return $content;
    }

    /**
     * Enable sortable functionality for the custom columns
     * 
     * @since 1.1.0
     * @source https://code.tutsplus.com/articles/quick-tip-make-your-custom-column-sortable--wp-25095
     */
    public function manage_sortable_columns( $sortable ){
        $sortable[ 'product_template' ] = 'runthings-jetwoobuilder-template-id';
        $sortable[ 'product_template_priority' ] = 'runthings-jetwoobuilder-priority';
        return $sortable;
    }

    /**
     * Modify ordervy query to support the sortable columns
     * 
     * @since 1.1.0
     * @source https://wordpress.org/support/topic/sorting-by-custom-taxonomy-field/#post-14085241
     */
    function modify_orderby_query( $pieces, $taxonomies, $args ) {      
        global $pagenow, $wpdb;

        $tax_name = 'product_cat';

        $orderby = ( isset( $_GET[ 'orderby' ] ) ) ? trim( sanitize_text_field( $_GET[ 'orderby' ] ) ) : '';
        if ( empty( $orderby ) ) { return $pieces; }

        $taxonomy = $taxonomies[ 0 ];

        if ( ! is_admin() || 'edit-tags.php' !== $pagenow || ! in_array( $taxonomy, [ $tax_name ] ) ) {
            return $pieces;
        }

        $field_name = 'runthings-jetwoobuilder-template-id';
        if ( $field_name === $orderby ) {
            $pieces[ 'join' ] .= ' INNER JOIN ' . $wpdb->termmeta . ' AS tm ON t.term_id = tm.term_id ';
            $pieces[ 'orderby' ]  = ' ORDER BY tm.meta_value ';
            $pieces[ 'where' ] .= ' AND tm.meta_key = "'.$field_name.'"';
        }

        $field_name = 'runthings-jetwoobuilder-priority';
        if ( $field_name === $orderby ) {
            $pieces[ 'join' ] .= ' INNER JOIN ' . $wpdb->termmeta . ' AS tm ON t.term_id = tm.term_id ';
            $pieces[ 'orderby' ]  = ' ORDER BY tm.meta_value ';
            $pieces[ 'where' ] .= ' AND tm.meta_key = "'.$field_name.'"';
        }

        return $pieces;
    }

    /** 
     * Get an array of all available Jet Woo Builder templates
     */
    private function get_single_templates() {
        if (function_exists('jet_woo_builder_post_type')) {
            return jet_woo_builder_post_type()->get_templates_list_for_options('single');
        }

        return [];
    }

    /**
     * Echo the intro text
     */
    private function echo_intro_text() {
        ?>
        <h2 class="title"><?php esc_html_e('runthings.dev - JetWooBuilder - Category Custom Template', 'runthings_jetwoobuilder_category_template'); ?></h2>
        <p><?php esc_html_e('Applies to all product templates in this category. Note: Individual template settings per-product will override the category setting.', 'runthings_jetwoobuilder_category_template'); ?></p>
        <?php
    }
}

new runthingsJetWooBuilderCategoryCustomProductTemplate();