# Filter Hooks

## wc\_ete/emails/editable

<p class="summary">Filters email IDs that should have an edit field in the settings.</p>

For all emails passed in this list, a textarea will be added to the settings where the
email text can be edited.

**since** 1.0.0 

| Name | Type | Description |
| --- | --- | --- |
| $editable_emails | `array` | An array of email IDs that should be editable. |

---

## wc\_ete/emails/filtered

<p class="summary">Filters email IDs where default content should be removed.</p>

For all emails passed in this list the default content that is defined in the email
template before the order details will be removed.

**since** 1.0.0 

| Name | Type | Description |
| --- | --- | --- |
| $filtered_emails | `array` | An array of email IDs where the default content should be removed. |

---

## wc\_ete/placeholders/content

<p class="summary">Filters placeholder contents.</p>

You can use this to add your own placeholders.

**since** 1.0.0 

| Name | Type | Description |
| --- | --- | --- |
| $placeholders | `array` | A key-value array of placeholders where the key is the placeholder and the value is the content that should be used instead of the placeholder in the email text. |
| $email | `\WC_Email` | A WooCommerce email object. |
| $order | `\WC_Order` | A WooCommerce order object. |
| $sent_to_admin | `bool` | Whether the email will be sent to an admin. |
| $plain_text | `bool` | Whether the email is sent in plaintext. |

**PHP**

```php
add_filter( 'wc_ete/placeholders/content', function( $placeholders ) {
    $placeholders['{admin_email}'] = get_option( 'admin_email' );

    return $placeholders;
} );
```

---

## wc\_ete/placeholders/description

<p class="summary">Filters placeholder description used in admin area.</p>

**since** 1.0.0 

| Name | Type | Description |
| --- | --- | --- |
| $descriptions | `array` | A key-value array of descriptions where the key is the placeholder and the key is the description of that placeholder. |

**PHP**

```php
add_filter( 'wc_ete/placeholders/description', function( $descriptions ) {
    $descpriptions['{admin_email}'] = 'The email address of the shop owner.'.

    return $descriptions;
} );
```

---

