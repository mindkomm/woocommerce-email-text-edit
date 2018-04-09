<?php
/**
 * Class WooCommerce_Email_Text_Edit
 */
class WooCommerce_Email_Text_Edit {
	/**
	 * Emails where an additional form field is added before the order details.
	 *
	 * Other available types not included in this list:
	 *
	 * - customer_reset_password
	 *
	 * @var array Array of email IDs.
	 */
	public $editable_emails = array(
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
	 * Emails where the already existing content before the order details will be removed.
	 *
	 * @var array Array of email IDs.
	 */
	public $filtered_emails = array(
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
	 * Email_Text_Edit_For_WooCommerce constructor.
	 */
	public function init() {
		/**
		 * Filters email IDs that should have an edit field in the settings.
		 *
		 * For all emails passed in this list, a textarea will be added to the settings where the
		 * email text can be edited.
		 *
		 * @api
		 * @since 1.0.0
		 *
		 * @param array $editable_emails An array of email IDs that should be editable.
		 */
		$this->editable_emails = apply_filters( 'wc_ete/emails/editable', $this->editable_emails );

		/**
		 * Filters email IDs where default content should be removed.
		 *
		 * For all emails passed in this list the default content that is defined in the email
		 * template before the order details will be removed.
		 *
		 * @api
		 * @since 1.0.0
		 *
		 * @param array $filtered_emails An array of email IDs where the default content should be removed.
		 */
		$this->filtered_emails = apply_filters( 'wc_ete/emails/filtered', $this->filtered_emails );

		// Add form fields for different email types
		foreach ( $this->editable_emails as $type ) {
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
	 * Start catching content with an output buffer.
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
	 * Release content buffer into ether.
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
		return in_array( $email->id, $this->filtered_emails, true );
	}

	/**
	 * Checks whether email text is is empty.
	 *
	 * @param WC_Email $email The email to check.
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

		/**
		 * Filters placeholder contents.
		 *
		 * You can use this to add your own placeholders.
		 *
		 * @api
		 * @since 1.0.0
		 * @example
		 * ```php
		 * add_filter( 'wc_ete/placeholders/content', function( $placeholders ) {
		 *     $placeholders['{admin_email}'] = get_option( 'admin_email' );
		 *
		 *     return $placeholders;
		 * } );
		 * ```
		 *
		 * @param array    $placeholders  A key-value array of placeholders where the key is the
		 *                                placeholder and the value is the content that should be
		 *                                used instead of the placeholder in the email text.
		 * @param WC_Email $email         A WooCommerce email object.
		 * @param WC_Order $order         A WooCommerce order object.
		 * @param bool     $sent_to_admin Whether the email will be sent to an admin.
		 * @param bool     $plain_text    Whether the email is sent in plaintext.
		 */
		$placeholders = apply_filters(
			'wc_ete/placeholders/content',
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
			'description' => __( 'Available Placeholders', 'woocommerce-email-text-edit' )
								. $this->create_placeholder_descriptions(),
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
			'{first_name}' => __(
				'First name from billing address',
				'woocommerce-email-text-edit'
			),
		);

		/**
		 * Filters placeholder descriptions used in admin area.
		 *
		 * @api
		 * @since 1.0.0
		 * @example
		 * ```php
		 * add_filter( 'wc_ete/placeholders/description', function( $descriptions ) {
		 *     $descpriptions['{admin_email}'] = 'The email address of the shop owner.'.
		 *
		 *     return $descriptions;
		 * } );
		 * ```
		 *
		 * @param array $descriptions A key-value array of descriptions where the key is the
		 *                            placeholder and the key is the description of that placeholder.
		 */
		$descriptions = apply_filters( 'wc_ete/placeholders/description', $descriptions );

		$html = '';

		if ( empty( $descriptions ) ) {
			return $html;
		}

		foreach ( $descriptions as $tag => $description ) {
			$html .= '<br><code>' . $tag . '</code>: ' . $description;
		}

		return $html;
	}
}
