<?php

/**
 * Form function for admin section
 * 
 * @package WooCommerce add to cart Text change
 * @author Saiful Islam <codersaiful@gmail.com>
 * @since v1.0
 */
function wactc_text_form() { 

    global $wactc_default_args;
    $message = '';
    if( isset($_POST['reset']) ){
        $message = "<h2 class='message_reset'>" . esc_html__( 'Reset Successfully', 'wactc' ) . "</h2>";
        update_option( 'wactc_default_add_to_cart_text', $wactc_default_args );
    }elseif( isset($_POST['submit']) && isset($_POST['data']) && is_array( $_POST['data'] )){
        $message = "<h2 class='message_successs'>" . esc_html__( 'Data Updated Successfully', 'wactc' ) . "</h2>";
        $data = isset( $_POST['data'] ) ? $_POST['data'] : $wactc_default_args;
        
        $final_data = array();
        $final_data['simple'] = sanitize_text_field( $data['simple'] );
        $final_data['icon'] = sanitize_text_field( $data['icon'] );
        $final_data['variable'] = sanitize_text_field( $data['variable'] );
        $final_data['grouped'] = sanitize_text_field( $data['grouped'] );
        update_option( 'wactc_default_add_to_cart_text', $final_data );
    }
    $saved_data = get_option('wactc_default_add_to_cart_text');

    $saved_data = wp_parse_args( $saved_data, $wactc_default_args );
    ?>
    <div class="wactc_panel">
        <div class="card wactc_config">
            <span class="wactc_section_title"><?php echo esc_html__( 'Write your Text for [Add to cart] Button', 'wactc' ); ?></span>
            <?php echo wp_kses_post( $message ); ?>
            <form action="" method="post" id="wactc_add_to_cart_form">
                <table class="wactc_config_form">
                    <tr>
                        <th><?php echo esc_html__( 'For Single Product Page', 'wactc' ); ?></th>
                        <td>
                            <input name="data[simple]" value="<?php echo esc_attr( $saved_data['simple'] ); ?>"  type="text">
                        </td>
                    </tr>
                    
                    <tr>
                        <th><?php echo esc_html__( 'Icon Setting', 'wactc' ); ?></th>
                        <td>
                            <select  name="data[icon]">
                                <option value="no_icon" <?php echo $saved_data['icon'] == 'no_icon' ? esc_attr( 'selected' ) : false; ?>><?php echo esc_html__( 'No Icon', 'wactc' ); ?></option>
                                <option value="only_icon" <?php echo $saved_data['icon'] == 'only_icon' ? esc_attr( 'selected' ) : false; ?>><?php echo esc_html__( 'Only Icon', 'wactc' ); ?></option>
                                <option value="icon_left" <?php echo $saved_data['icon'] == 'icon_left' ? esc_attr( 'selected' ) : false; ?>><?php echo esc_html__( 'Icon at Left', 'wactc' ); ?></option>
                                <option value="icon_right" <?php echo $saved_data['icon'] == 'icon_right' ? esc_attr( 'selected' ) : false; ?>><?php echo esc_html__( 'Icon at Right', 'wactc' ); ?></option>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><?php echo esc_html__( 'Variable Product [In Loop/ShopPage]', 'wactc' ); ?></th>
                        <td>
                            <input name="data[variable]" value="<?php echo esc_attr( $saved_data['variable'] ); ?>"  type="text">
                        </td>
                    </tr>
                    <tr>
                        <th><?php echo esc_html__( 'Grouped Product  [In Loop/ShopPage]', 'wactc' ); ?></th>
                        <td>
                            <input name="data[grouped]" value="<?php echo esc_attr( $saved_data['grouped'] ); ?>"  type="text">
                        </td>
                    </tr>
                </table>
                <input type="submit" name="submit" value="<?php echo esc_attr__( 'Submit', 'wactc' ); ?>" class="button button-primary"> 
                <input type="submit" name="reset" value="<?php echo esc_attr__( 'Reset', 'wactc' ); ?>" class="button button-primary"> 
            </form>
            <br>
        </div>
        
    </div>


    <?php
}