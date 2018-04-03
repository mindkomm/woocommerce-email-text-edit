<?php
/**
 * Class WooCommerce_Email_Text_Edit
 */
class WooCommerce_Email_Text_Edit {
	/**
	 * Emails where an additional form field is added.
	 *
	 * Other available types not included in this list:
	 *
	 * - customer_reset_password
	 *
	 * @var array Array of email IDs.
	 */
	public $customer_email_types = array(
		// Default WooCommerce E-Mails
		'customer_completed_order',
		'customer_invoice',
		'customer_note',
		'customer_on_hold_order',
		'customer_processing_order',
		'customer_refunded_order',

		// WooCommerce Subscriptions
		'customer_completed_renewal_order',
		'customer_completed_switch_order',
		'customer_payment_retry',
		'customer_processing_renewal_order',
		'customer_renewal_invoice',
	);

	/**
	 * Emails where the already existing header content will be removed.
	 *
	 * @var array Array of email IDs.
	 */
	public $customer_email_types_filtered = array(
		'customer_completed_order',
		'customer_invoice',
		'customer_processing_order',

		// WooCommerce Subscriptions
		'customer_completed_renewal_order',
		'customer_completed_switch_order',
		'customer_payment_retry',
		'customer_processing_renewal_order',
		'customer_renewal_invoice',
	);

	/**
	 * WooCommerce_Email_Text_Edit constructor.
	 */
	public function init() {
		// Add form fields for different email types
		foreach ( $this->customer_email_types as $type ) {
			add_filter( "woocommerce_settings_api_form_fields_{$type}", [
				$this,
				'add_form_fields',
			] );
		}

		// Remove the content that’s already there.
		add_action( 'woocommerce_email_header', array( $this, 'catch_header' ), 999, 2 );
		add_action( 'woocommerce_email_order_details', array( $this, 'catch_order_table' ), 1, 4 );

		// Insert content before email
		add_action(
			'woocommerce_email_order_details',
			array( $this, 'insert_content_before_email' ),
			9, 4
		);

		// Compatibility with WooCommerce Subscriptions
		add_action(
			'woocommerce_subscriptions_email_order_details',
			array( $this, 'catch_order_table' ),
			1, 4
		);

		add_action(
			'woocommerce_subscriptions_email_order_details',
			array( $this, 'insert_content_before_email' ),
			9, 4
		);
	}

	/**
	 * Start catching content.
	 *
	 * @param string   $email_heading Email heading.
	 * @param WC_Email $email         Email object.
	 */
	public function catch_header( $email_heading, $email = null ) {
		if ( ! isset( $email->id )
			|| ! $this->email_should_be_filtered( $email )
			|| $this->email_content_is_empty( $email )
		) {
			return;
		}

		ob_start();
	}

	/**
	 * Release content into ether.
	 *
	 * @param WC_Order $order         Order object.
	 * @param bool     $sent_to_admin Whether email is sent to admin.
	 * @param bool     $plain_text    Whether email is plaintext.
	 * @param WC_Email $email         Email object.
	 */
	public function catch_order_table( $order, $sent_to_admin, $plain_text, $email ) {
		if ( ! $this->email_should_be_filtered( $email ) || $this->email_content_is_empty( $email )
		) {
			return;
		}

		ob_end_clean();
	}

	/**
	 * Checks whether an email should be filtered.
	 *
	 * @param WC_Email $email The email to check.
	 *
	 * @return bool
	 */
	public function email_should_be_filtered( $email ) {
		return in_array( $email->id, $this->customer_email_types_filtered, true );
	}

	/**
	 * Checks whether email text is is empty.
	 *
	 * @param WC_Email $email
	 *
	 * @return bool
	 */
	public function email_content_is_empty( $email ) {
		$settings = $email->get_option( 'email_text_before_content' );

		return empty( $settings );
	}

	/**
	 * Insert custom content before an email.
	 *
	 * @param WC_Order $order         A WooCommerce order object.
	 * @param bool     $sent_to_admin Whether the email is sent to an admin.
	 * @param bool     $plain_text    Whether the email is sent in plaintext.
	 * @param WC_Email $email         A WooCommerce email object.
	 */
	public function insert_content_before_email( $order, $sent_to_admin, $plain_text, $email ) {
		if ( ! $this->email_should_be_filtered( $email ) || $this->email_content_is_empty( $email ) ) {
			return;
		}

		$placeholders = array(
			'{blank}'     => '',
			'{firstname}' => $this->get_first_name( $order ),
		);

		$placeholders = apply_filters(
			'wc_ete_placeholders_content',
			$placeholders,
			$email,
			$order,
			$sent_to_admin,
			$plain_text
		);

		$find    = array_keys( $placeholders );
		$replace = array_values( $placeholders );
		$content = str_replace( $find, $replace, $email->settings['email_text_before_content'] );

		// Replace still existing placeholders
		$content = str_replace( $find, '', $content );

		// Apply default formatting
		$content = wptexturize( $content );
		$content = wpautop( $content );

		$content = wp_kses_post( $content );

		echo $content;
	}

	/**
	 * Get firstname from an order.
	 *
	 * @param WC_Order $order Order object.
	 *
	 * @return bool|string
	 */
	public function get_first_name( $order ) {
		$name = get_post_meta( $order->get_id(), '_billing_first_name', true );

		if ( ! empty( $name ) ) {
			return $name;
		}

		return false;
	}

	/**
	 * Add form field for additional email text.
	 *
	 * @param array $fields Existing form fields.
	 *
	 * @return array Form fields.
	 */
	public function add_form_fields( $fields ) {
		$fields['email_text_before_content'] = array(
			'title'       => __( 'Email text before content', 'woocommerce-email-text-edit' ),
			'type'        => 'textarea',
			'description' => __( 'Available Placeholders', 'woocommerce-email-text-edit' ) . $this->create_placeholder_descriptions(),
			'default'     => '',
		);

		return $fields;
	}

	/**
	 * Generate descriptions for placeholders.
	 *
	 * Placeholder descriptions will be displayed below the edit field of the email.
	 *
	 * @return string
	 */
	public function create_placeholder_descriptions() {
		$descriptions = array(
			'{blank}'      => __(
				'Add only this tag to the content to leave email content empty',
				'woocommerce-email-text-edit'
			),
			'{first_name}' => __( 'First name from billing address', 'woocommerce-email-text-edit' ),
		);

		$descriptions = apply_filters( 'wc_ete_placeholders_description', $descriptions );

		$html = '';

		foreach ( $descriptions as $tag => $description ) {
			$html .= '<br><code>' . $tag . '</code>: ' . $description;
		}

		return $html;
	}
}
