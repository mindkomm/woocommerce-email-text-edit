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
		// Default WooCommerce E-Mails.
		'customer_completed_order',
		'customer_invoice',
		'customer_note',
		'customer_on_hold_order',
		'customer_processing_order',
		'customer_refunded_order',

		// WooCommerce Subscriptions.
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
		// Default WooCommerce E-Mails.
		'customer_completed_order',
		'customer_invoice',
		'customer_note',
		'customer_on_hold_order',
		'customer_processing_order',
		'customer_refunded_order',

		// WooCommerce Subscriptions.
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

		// Add form fields for different email types.
		foreach ( $this->editable_emails as $type ) {
			add_filter( "woocommerce_settings_api_form_fields_{$type}", [
				$this,
				'add_form_fields',
			] );
		}

		// Remove the content that’s already there.
		add_action( 'woocommerce_email_header', array( $this, 'catch_header' ), 999, 2 );
		add_action( 'woocommerce_email_order_details', array( $this, 'catch_order_table' ), 1, 4 );

		// Insert content before email content.
		add_action(
			'woocommerce_email_order_details',
			array( $this, 'insert_content_before_email' ),
			9, 4
		);

		// Remove footer content that’s already there.
		add_action( 'woocommerce_email_customer_details', array( $this, 'catch_footer' ), 999, 4 );
		add_action( 'woocommerce_email_footer', array( $this, 'catch_email_footer' ), 1 );

		// Insert content before footer.
		add_action(
			'woocommerce_email_footer',
			array( $this, 'insert_content_before_footer' ),
			9
		);

		// Compatibility with WooCommerce Subscriptions.
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
	 * Start catching email content with an output buffer.
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
	 * Start catching content before footer with an output buffer.
	 *
	 * @since 1.1.0
	 *
	 * @param WC_Order $order         Order object.
	 * @param bool     $sent_to_admin Whether email is sent to admin.
	 * @param bool     $plain_text    Whether email is plaintext.
	 * @param WC_Email $email         Email object.
	 */
	public function catch_footer( $order, $sent_to_admin, $plain_text, $email = null ) {
		if ( ! isset( $email->id )
			|| ! $this->email_should_be_filtered( $email )
			|| $this->email_footer_is_empty( $email )
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
		if ( ! $this->email_should_be_filtered( $email )
			|| $this->email_content_is_empty( $email )
		) {
			return;
		}

		ob_end_clean();
	}

	/**
	 * Release content buffer into ether.
	 *
	 * @since 1.1.0
	 *
	 * @param WC_Email $email Email object.
	 */
	public function catch_email_footer( $email ) {
		if ( ! $this->email_should_be_filtered( $email )
			|| $this->email_footer_is_empty( $email )
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
		return $email && in_array( $email->id, $this->filtered_emails, true );
	}

	/**
	 * Checks whether email content text is empty.
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
	 * Checks whether email footer text is is empty.
	 *
	 * @since 1.1.0
	 *
	 * @param WC_Email $email The email to check.
	 *
	 * @return bool
	 */
	public function email_footer_is_empty( $email ) {
		$settings = $email->get_option( 'email_text_before_footer' );

		return empty( $settings );
	}

	/**
	 * Inserts custom content before the email’s content.
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
			'{blank}'      => '',
			'{order_id}'   => $order->get_order_number(),
			'{first_name}' => $order->get_billing_first_name(),
			'{last_name}'  => $order->get_billing_last_name(),
		);

		/**
		 * Filters email content placeholders.
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

		// Replace still existing placeholders.
		$content = str_replace( $find, '', $content );

		$content = $this->sanitize_content( $content );

		echo $content;
	}

	/**
	 * Inserts custom content before the email’s footer.
	 *
	 * @since 1.1.0
	 *
	 * @param WC_Email $email A WooCommerce email object.
	 */
	public function insert_content_before_footer( $email ) {
		if ( ! $this->email_should_be_filtered( $email )
			|| $this->email_footer_is_empty( $email ) ) {
			return;
		}

		$placeholders = array(
			'{blank}' => '',
		);

		/**
		 * Filters email footer content placeholders.
		 *
		 * You can use this to add your own placeholders.
		 *
		 * @api
		 * @since 1.1.0
		 * @example
		 * ```php
		 * add_filter( 'wc_ete/placeholders/footer', function( $placeholders ) {
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
		 */
		$placeholders = apply_filters(
			'wc_ete/placeholders/footer',
			$placeholders,
			$email
		);

		$find    = array_keys( $placeholders );
		$replace = array_values( $placeholders );
		$content = str_replace( $find, $replace, $email->settings['email_text_before_footer'] );

		// Replace still existing placeholders.
		$content = str_replace( $find, '', $content );

		$content = $this->sanitize_content( $content );

		echo $content;
	}

	/**
	 * Sanitizes email content.
	 *
	 * @param string $content Content string.
	 *
	 * @return string
	 */
	public function sanitize_content( $content ) {
		// Apply default formatting.
		$content = wptexturize( $content );
		$content = wpautop( $content );

		$content = wp_kses_post( $content );

		return $content;
	}

	/**
	 * Add form field for additional email texts.
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
				. $this->create_placeholder_description_content(),
			'default'     => '',
		);

		$fields['email_text_before_footer'] = array(
			'title'       => __( 'Email text before footer', 'woocommerce-email-text-edit' ),
			'type'        => 'textarea',
			'description' => __( 'Available Placeholders', 'woocommerce-email-text-edit' )
				. $this->create_placeholder_description_footer(),
			'default'     => '',
		);

		return $fields;
	}

	/**
	 * Creats placeholder descriptions for email content.
	 *
	 * @since 1.1.0
	 * @return string
	 */
	public function create_placeholder_description_content() {
		$descriptions = array(
			'{blank}'      => __(
				'Add only this tag to the content to leave email content empty',
				'woocommerce-email-text-edit'
			),
			'{order_id}'   => __(
				'Order number/ID',
				'woocommerce-email-text-edit'
			),
			'{first_name}' => __(
				'First name from billing address',
				'woocommerce-email-text-edit'
			),
			'{last_name}'  => __(
				'Last name from billing address',
				'woocommerce-email-text-edit'
			),
		);

		return $this->create_placeholder_descriptions( $descriptions );
	}

	/**
	 * Creates placeholder descriptions for email footer.
	 *
	 * @since 1.1.0
	 * @return string
	 */
	public function create_placeholder_description_footer() {
		$descriptions = array(
			'{blank}' => __(
				'Add only this tag to the content to leave email content empty',
				'woocommerce-email-text-edit'
			),
		);

		return $this->create_placeholder_descriptions( $descriptions );
	}

	/**
	 * Generates descriptions for placeholders.
	 *
	 * Placeholder descriptions will be displayed below the edit field of the email.
	 *
	 * @param array $descriptions Default descriptions.
	 *
	 * @return string
	 */
	public function create_placeholder_descriptions( $descriptions ) {
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
