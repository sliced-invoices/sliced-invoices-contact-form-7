<?php
/**
 * @wordpress-plugin
 * Plugin Name:       Sliced Invoices & Contact Form 7
 * Plugin URI:        https://wordpress.org/plugins/sliced-invoices-contact-form-7
 * Description:       Create forms that allow users to submit a quote or estimate request. Requirements: The Sliced Invoices Plugin & Contact Form 7 Plugin
 * Version:           1.1.3
 * Author:            Sliced Invoices
 * Author URI:        https://slicedinvoices.com/
 * Text Domain:       sliced-invoices-contact-form-7
 * Domain Path:       /languages
 * Copyright:         © 2022 Sliced Software, LLC. All rights reserved.
 * License:           GPLv2
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.html
 */

// Exit if accessed directly
if ( ! defined('ABSPATH') ) { 
	exit;
}


/**
 * Calls the class.
 */
function sliced_call_cf7_class() {
    new Sliced_CF7();
}
add_action( 'init', 'sliced_call_cf7_class' );


/** 
 * The Class.
 */
class Sliced_CF7 {

    /**
     * Hook into the appropriate actions when the class is constructed.
     */
    public function __construct() {
		
		if ( ! $this->validate_settings() ) {
			return;
		}
		
        add_action( 'wpcf7_before_send_mail', array( $this, 'handle' ) );
		
    }

    /**
     * Process the data coming from the form
     * @since  1.1.0
     */ 
	public function handle( $form ) {
	
		$posted_data = null;
		
		if ( isset( $form->posted_data ) ) {
			$posted_data = $form->posted_data;
		} elseif ( class_exists('WPCF7_Submission')) {
			$submission = WPCF7_Submission::get_instance();
			if ( $submission ) {
				$posted_data = $submission->get_posted_data();
			}
		}
		
		if ( ! $posted_data ) {
            return;
		}
		
		if ( isset( $posted_data['sliced_quote_or_invoice'] ) && strtolower( $posted_data['sliced_quote_or_invoice'] ) === 'invoice' ) {
			$this->create_new_invoice( $posted_data );
		} else {
			$this->create_new_quote( $posted_data );
		}
		
	}
	
	
	/**
     * Create a new invoice
     * @since  1.1.0
     */ 
    public function create_new_invoice( $posted_data ) {
        
        if ( ! $posted_data ) {
            return;
		}

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
                'post_type'      => 'sliced_invoice',
            );
            $id = wp_insert_post( $post_array, $wp_error = false );

            // set status
			$status = null;
			$taxonomy = 'invoice_status';
			if ( isset( $posted_data['sliced_invoice_status'] ) && term_exists( $posted_data['sliced_invoice_status'], $taxonomy ) ) {
				$status = $posted_data['sliced_invoice_status'];
			}
			wp_set_object_terms( $id, array( ( $status ? $status : 'draft' ) ), $taxonomy );
            
			// invoice number			
			$prefix = sliced_get_invoice_prefix();
			$suffix = sliced_get_invoice_suffix();
			$number = sliced_get_next_invoice_number();
		
            // insert the post_meta
            update_post_meta( $id, '_sliced_description', esc_html( $desc ) );
            update_post_meta( $id, '_sliced_invoice_created', time() );
            update_post_meta( $id, '_sliced_invoice_prefix', esc_html( $prefix ) );
            update_post_meta( $id, '_sliced_invoice_number', esc_html( $number ) );
			update_post_meta( $id, '_sliced_invoice_suffix', esc_html( $suffix ) );
			update_post_meta( $id, '_sliced_number', $prefix . $number . $suffix );
            Sliced_Invoice::update_invoice_number( $id ); // update the invoice number
			
			// line items
			$line_items = array();
			foreach ( $posted_data as $key => $value ) {
				
				if ( ! $value > '' ) {
					continue;
				}
				
				preg_match( '/^sliced_line_item_([0-9]+)_qty$/', $key, $line_qty );
				if ( ! empty( $line_qty ) ) {
					$line_items[ $line_qty[1] ]['qty'] = esc_html( $value );
				}
				
				preg_match( '/^sliced_line_item_([0-9]+)_title$/', $key, $line_title );
				if ( ! empty( $line_title ) ) {
					$line_items[ $line_title[1] ]['title'] = esc_html( $value );
				}
				
				preg_match( '/^sliced_line_item_([0-9]+)_desc$/', $key, $line_desc );
				if ( ! empty( $line_desc ) ) {
					$line_items[ $line_desc[1] ]['description'] = wp_kses_post( $value );
				}
				
				preg_match( '/^sliced_line_item_([0-9]+)_amt$/', $key, $line_amt );
				if ( ! empty( $line_amt ) ) {
					$line_items[ $line_amt[1] ]['amount'] = Sliced_Shared::get_formatted_number( $value );
				}
			}
			$line_items = array_values( $line_items );
			foreach ( $line_items as &$line_item ) {
				$line_item['taxable'] = 'on';
				$line_item['second_taxable'] = 'on';
			}
			update_post_meta( $id, '_sliced_items', apply_filters( 'sliced_cf7_line_items', $line_items ) );
			
			// tax
			$tax = get_option( 'sliced_tax' );
			update_post_meta( $id, '_sliced_tax_calc_method', Sliced_Shared::get_tax_calc_method( $id ) );
			update_post_meta( $id, '_sliced_tax', sliced_get_tax_amount_formatted( $id ) );
			update_post_meta( $id, '_sliced_tax_name', sliced_get_tax_name( $id ) );
			update_post_meta( $id, '_sliced_additional_tax_name', isset( $tax['sliced_additional_tax_name'] ) ? $tax['sliced_additional_tax_name'] : '' );
			update_post_meta( $id, '_sliced_additional_tax_rate', isset( $tax['sliced_additional_tax_rate'] ) ? $tax['sliced_additional_tax_rate'] : '' );
			update_post_meta( $id, '_sliced_additional_tax_type', isset( $tax['sliced_additional_tax_type'] ) ? $tax['sliced_additional_tax_type'] : '' );
			
			// terms
			$invoices = get_option( 'sliced_invoices' );
			$terms    = isset( $invoices['terms'] ) ? $invoices['terms'] : '';
			if ( $terms ) {
				update_post_meta( $id, '_sliced_invoice_terms', $terms );
			}
			
			// payment methods
			if ( function_exists( 'sliced_get_accepted_payment_methods' ) ) {
				$payment = sliced_get_accepted_payment_methods();
				update_post_meta( $id, '_sliced_payment_methods', array_keys($payment) );
			}
			
            // client
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
			
			// maybe send it
			if ( isset( $posted_data['sliced_invoice_send'] ) && strtolower( $posted_data['sliced_invoice_send'] ) === 'true' ) {
				if ( class_exists( 'Sliced_Pdf' ) ) {
					// temporary solution to solve conflict between PDF extension and Secure Invoices, when doing ajax form submission
					$_GET['print_pdf'] = 'true';
					if ( ! defined('DOING_AJAX') ) {
						define('DOING_AJAX',true);
					}
				}
				$send = new Sliced_Notifications;
				$send->send_the_invoice( $id );
				if ( $status ) {
					// set the status again, because send_the_quote() changes the status to "sent"
					wp_set_object_terms( $id, array( $status ), $taxonomy );
				}
			}

        }

		// done
        do_action( 'sliced_cf7_invoice_created', $id, $posted_data );

    }
	
	
	/**
     * Create a new quote
     * @since  1.0
     */ 
    public function create_new_quote( $posted_data ) {
        
        if ( ! $posted_data ) {
            return;
		}

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

            // set status
			$status = null;
			$taxonomy = 'quote_status';
			if ( isset( $posted_data['sliced_quote_status'] ) && term_exists( $posted_data['sliced_quote_status'], $taxonomy ) ) {
				$status = $posted_data['sliced_quote_status'];
			}
			wp_set_object_terms( $id, array( ( $status ? $status : 'draft' ) ), $taxonomy );
            
			// quote number			
			$prefix = sliced_get_quote_prefix();
			$suffix = sliced_get_quote_suffix();
			$number = sliced_get_next_quote_number();
			
            // insert the post_meta
            update_post_meta( $id, '_sliced_description', esc_html( $desc ) );
            update_post_meta( $id, '_sliced_quote_created', time() );
            update_post_meta( $id, '_sliced_quote_prefix', esc_html( $prefix ) );
            update_post_meta( $id, '_sliced_quote_number', esc_html( $number ) );
			update_post_meta( $id, '_sliced_quote_suffix', esc_html( $suffix ) );
			update_post_meta( $id, '_sliced_number', $prefix . $number . $suffix );
            Sliced_Quote::update_quote_number( $id ); // update the quote number

			// line items
			$line_items = array();
			foreach ( $posted_data as $key => $value ) {
				
				if ( ! $value > '' ) {
					continue;
				}
				
				preg_match( '/^sliced_line_item_([0-9]+)_qty$/', $key, $line_qty );
				if ( ! empty( $line_qty ) ) {
					$line_items[ $line_qty[1] ]['qty'] = esc_html( $value );
				}
				
				preg_match( '/^sliced_line_item_([0-9]+)_title$/', $key, $line_title );
				if ( ! empty( $line_title ) ) {
					$line_items[ $line_title[1] ]['title'] = esc_html( $value );
				}
				
				preg_match( '/^sliced_line_item_([0-9]+)_desc$/', $key, $line_desc );
				if ( ! empty( $line_desc ) ) {
					$line_items[ $line_desc[1] ]['description'] = wp_kses_post( $value );
				}
				
				preg_match( '/^sliced_line_item_([0-9]+)_amt$/', $key, $line_amt );
				if ( ! empty( $line_amt ) ) {
					$line_items[ $line_amt[1] ]['amount'] = Sliced_Shared::get_formatted_number( $value );
				}
			}
			$line_items = array_values( $line_items );
			foreach ( $line_items as &$line_item ) {
				$line_item['taxable'] = 'on';
				$line_item['second_taxable'] = 'on';
			}
			update_post_meta( $id, '_sliced_items', apply_filters( 'sliced_cf7_line_items', $line_items ) );
			
			// tax
			$tax = get_option( 'sliced_tax' );
			update_post_meta( $id, '_sliced_tax_calc_method', Sliced_Shared::get_tax_calc_method( $id ) );
			update_post_meta( $id, '_sliced_tax', sliced_get_tax_amount_formatted( $id ) );
			update_post_meta( $id, '_sliced_tax_name', sliced_get_tax_name( $id ) );
			update_post_meta( $id, '_sliced_additional_tax_name', isset( $tax['sliced_additional_tax_name'] ) ? $tax['sliced_additional_tax_name'] : '' );
			update_post_meta( $id, '_sliced_additional_tax_rate', isset( $tax['sliced_additional_tax_rate'] ) ? $tax['sliced_additional_tax_rate'] : '' );
			update_post_meta( $id, '_sliced_additional_tax_type', isset( $tax['sliced_additional_tax_type'] ) ? $tax['sliced_additional_tax_type'] : '' );
		
			// terms
			$quotes = get_option( 'sliced_quotes' );
			$terms  = isset( $quotes['terms'] ) ? $quotes['terms'] : '';
			if ( $terms ) {
				update_post_meta( $id, '_sliced_quote_terms', $terms );
			}
			
            // client
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
			
			// maybe send it
			if ( isset( $posted_data['sliced_quote_send'] ) && strtolower( $posted_data['sliced_quote_send'] ) === 'true' ) {
				if ( class_exists( 'Sliced_Pdf' ) ) {
					// temporary solution to solve conflict between PDF extension and Secure Invoices, when doing ajax form submission
					$_GET['print_pdf'] = 'true';
					if ( ! defined('DOING_AJAX') ) {
						define('DOING_AJAX',true);
					}
				}
				$send = new Sliced_Notifications;
				$send->send_the_quote( $id );
				if ( $status ) {
					// set the status again, because send_the_quote() changes the status to "sent"
					wp_set_object_terms( $id, array( $status ), $taxonomy );
				}
			}

        }

		// done
        do_action( 'sliced_cf7_quote_created', $id, $posted_data );

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
	
	
	/**
     * Output requirements not met notice.
     *
     * @since   1.1.1
     */
	public function requirements_not_met_notice_sliced() {
		echo '<div id="message" class="error">';
		echo '<p>' . sprintf( __( 'Sliced Invoices & Contact Form 7 extension cannot find the required <a href="%s">Sliced Invoices plugin</a>. Please make sure the core Sliced Invoices plugin is <a href="%s">installed and activated</a>.', 'sliced-invoices-contact-form-7' ), 'https://wordpress.org/plugins/sliced-invoices/', admin_url( 'plugins.php' ) ) . '</p>';
		echo '</div>';
	}
	
	
	/**
     * Output requirements not met notice.
     *
     * @since   1.1.1
     */
	public function requirements_not_met_notice_wpcf7() {
		echo '<div id="message" class="error">';
		echo '<p>' . sprintf( __( 'Sliced Invoices & Contact Form 7 extension cannot find the required <a href="%s">Contact Form 7 plugin</a>. Please make sure the Contact Form 7 plugin is <a href="%s">installed and activated</a>.', 'sliced-invoices-contact-form-7' ), 'https://wordpress.org/plugins/contact-form-7/', admin_url( 'plugins.php' ) ) . '</p>';
		echo '</div>';
	}
	
	
	/**
	 * Validate settings, make sure all requirements met, etc.
	 *
	 * @version 1.1.2
	 * @since   1.1.1
	 */
	public function validate_settings() {
	
		$validated = true;
	
		if ( ! class_exists( 'Sliced_Invoices' ) ) {
			
			// Add a dashboard notice.
			add_action( 'admin_notices', array( $this, 'requirements_not_met_notice_sliced' ) );

			$validated = false;
		}
		
		if ( ! class_exists( 'WPCF7' ) ) {
			
			// Add a dashboard notice.
			add_action( 'admin_notices', array( $this, 'requirements_not_met_notice_wpcf7' ) );

			$validated = false;
		}
		
		return $validated;
	}


}
