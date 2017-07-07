<?php

/**
 * @wordpress-plugin
 * Plugin Name:       Sliced Invoices & Contact Form 7
 * Plugin URI:        https://wordpress.org/plugins/sliced-invoices-contact-form-7
 * Description:       Create forms that allow users to submit a quote or estimate request. Requirements: The Sliced Invoices Plugin & Contact Form 7 Plugin
 * Version:           1.0
 * Author:            Sliced Invoices
 * Author URI:        https://slicedinvoices.com/
 * Text Domain:       sliced-invoices-contact-form-7
 * Domain Path:       /languages
 */

// Exit if accessed directly
if ( ! defined('ABSPATH') ) { exit;
}


/**
 * Calls the class.
 */
function sliced_call_cf7_class() {
    new Sliced_CF7();
}
add_action( 'sliced_loaded', 'sliced_call_cf7_class' );


/** 
 * The Class.
 */
class Sliced_CF7 {

    /**
     * Hook into the appropriate actions when the class is constructed.
     */
    public function __construct() {
        add_filter( 'wpcf7_posted_data', array( $this, 'create_new_quote' ) );
    }

    /**
     * Process the data coming from the form
     * @since  1.0
     */ 
    public function create_new_quote( $posted_data ) {
        
        if ( ! $posted_data )
            return;

        if ( array_key_exists( 'sliced_client_email', $posted_data ) && array_key_exists( 'sliced_title', $posted_data ) && array_key_exists( 'sliced_client_name', $posted_data ) ) {

            $email       = $posted_data['sliced_client_email'];
            $name        = $posted_data['sliced_client_name'];
            $business    = isset( $posted_data['sliced_client_business'] ) ? $posted_data['sliced_client_business'] : $name;
            $address     = isset( $posted_data['sliced_client_address'] ) ? $posted_data['sliced_client_address'] : '';
            $extra       = isset( $posted_data['sliced_client_extra'] ) ? $posted_data['sliced_client_extra'] : '';
            $website     = isset( $posted_data['sliced_client_website'] ) ? $posted_data['sliced_client_website'] : '';
            $title       = $posted_data['sliced_title'];
            $desc        = isset( $posted_data['sliced_description'] ) ? $posted_data['sliced_description'] : '';

            // insert the post
            $post_array = array(
                'post_content'   => '',
                'post_title'     => $title,
                'post_status'    => 'publish',
                'post_type'      => 'sliced_quote',
            );
            $id = wp_insert_post( $post_array, $wp_error = false );

            // set to a draft
            $taxonomy = 'quote_status';
            wp_set_object_terms( $id, array( 'draft' ), $taxonomy );
            
            // insert the post_meta
            update_post_meta( $id, '_sliced_description', esc_html( $desc ) );
            update_post_meta( $id, '_sliced_quote_created', time() );
            update_post_meta( $id, '_sliced_quote_prefix', esc_html( sliced_get_quote_prefix() ) );
            update_post_meta( $id, '_sliced_quote_number', esc_html( sliced_get_next_quote_number() ) );
            Sliced_Quote::update_quote_number(); // update the invoice number

            // put the client details into array
            $client_array = array(
                'name'          => trim( $name ),
                'email'         => trim( $email ),
                'website'       => trim( $website ),
                'business'      => $business,
                'address'       => wpautop( $address ),
                'extra_info'    => wpautop( $extra ),
                'post_id'       => $id,
              
            );
            $client_id = $this->maybe_add_client( $client_array );

        }

        return $posted_data;

    }


    /**
     * Check for existing client and add new one if does not exist.
     * @since  1.0
     */ 
    public function maybe_add_client( $client_array ) {
        
        // if client does not exist, create one
        $client_id = null;
        if( ! email_exists( $client_array['email'] ) ) {

            // generate random password
            $password = wp_generate_password( 10, true, true );
            
            // a bit of safeguarding
            $name = !empty( $client_array['name'] ) ? $client_array['name'] : 'no_name_' . $password; // just in case
            $business = !empty( $client_array['business'] ) ? $client_array['business'] : $name;

            if( username_exists( $name ) ) {
                $name = $business;
            }
            if( username_exists( $name ) ) {
                $name = 'no-name-' . $password;
            }

            // create the user
            $userdata = array(
                'user_login'  =>  $name,
                'user_url'    =>  $client_array['website'],
                'user_email'  =>  $client_array['email'],
                'user_pass'   =>  $password  // When creating an user, `user_pass` is expected.
            );
            $client_id = wp_insert_user( $userdata );

            // add the user meta
            add_user_meta( $client_id, '_sliced_client_business', esc_html( $business ) );
            add_user_meta( $client_id, '_sliced_client_address', wp_kses_post( $client_array['address'] ) );
            add_user_meta( $client_id, '_sliced_client_extra_info', wp_kses_post( $client_array['extra_info'] ) );

        } else {

            // get the existing user id
            $client = get_user_by( 'email', $client_array['email'] );
            $client_id = $client->ID;

        }   

        // add the user to the post
        update_post_meta( $client_array['post_id'], '_sliced_client', (int) $client_id );

        return $client_id;

    }


}