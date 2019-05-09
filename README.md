# WooCommerce Email Text Edit

This plugin allows you to edit email texts that are used in WooCommerce customer emails without having to edit the default templates that WooCommerce uses for emails.

Unlike other plugins, it will not overwrite your existing email template files.

## Installation

You can install the package with Composer:

```
composer require mindkomm/woocommerce-email-text-edit
```

## Usage

After you’ve installed the plugin, go to **WooCommerce** &rarr; **Settings** &rarr; **Emails** and select a customer email that you want to edit. You’ll find two new edit field where you can edit the content and the footer content of the email.

The following custom emails are supported:

- Completed Order
- Customer Invoice
- Customer Note
- Order on-hold
- Processing order
- Refunded order

Additionally, if you use the WooCommerce Subscriptions plugin, the following email types are supported as well:

- Completed Renewal Order
- Subscription Switch Complete
- Customer Payment Retry
- Processing Renewal Order
- Customer Renewal Invoice

## Technical details

This plugin won’t overwrite any email templates that you’ve already defined. It will instead hook into the `woocommerce_email_header` action with a priority of `999` and start an output buffer. The output buffer will then be released in a function hooked to `woocommerce_email_order_details` with a priority of `1`. All output that was echoed between these two hooks will be suppressed.

It will do the same for the content in the footer. There, it will hook into the `woocommerce_email_customer_details` and `woocommerce_email_footer` actions.

Because of this setup, this plugin might not work if you have already heavily customized your emails with custom templates.

## Filters

See the [Filters documentation](https://github.com/mindkomm/woocommerce-email-text-edit/blob/master/docs/filters.md).

