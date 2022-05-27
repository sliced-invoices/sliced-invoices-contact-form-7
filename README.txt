=== Sliced Invoices & Contact Form 7 ===
Contributors: SlicedInvoices
Tags: contact form 7, contact form 7 add on, contact form 7 invoice, contact form 7 invoice, contact form 7 estimate, contact form 7 quote, invoice, invoicing, quotes, estimates, invoice clients, quote request, estimate request
Requires at least: 4.0
Tested up to: 6.0
Stable tag: 1.1.3
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Create an online quote or invoice request form using Contact Form 7. Every form entry then automatically creates a quote/invoice in the Sliced Invoices plugin.

== Description ==
Imagine having a form on your website that allows your visitors to basically create their own quotes for you! 

All you need to do once they have submitted the form is read the description of work they require and then set your pricing. All of their client data has already been captured and added to the quote.


= Requirements =
*	[Sliced Invoices Plugin](https://wordpress.org/plugins/sliced-invoices/) (free)
*   [Contact Form 7 Plugin](https://wordpress.org/plugins/contact-form-7/) (free)

= Initial Setup =
Once you have both plugins installed and activated, you simply need to create your Quote or Invoice Request form that contains the following fields:

**Required Fields**

*   sliced_client_name - the Client Name
*   sliced_client_email - the Client Email
*   sliced_title - becomes the Quote/Invoice title
*   sliced_quote_or_invoice - should be "quote" to create a quote, or "invoice" to create an invoice.  For example:

`[hidden sliced_quote_or_invoice "invoice"]`

*If sliced_quote_or_invoice is not included in the form, then "quote" will be assumed by default.*

**Optional Fields**

You can also add the following optional fields that will map to other Sliced Invoices fields for the quote:

*   sliced_client_business - the Client Business Name - recommended
*   sliced_client_address - the Client Address
*   sliced_client_extra - the Client Extra Info field
*   sliced_description - becomes the Quote/Invoice description - recommended

Line Items:

For line items, you can use the following tags.  Just replace {X} with a number.  For example sliced_line_item_1_title, sliced_line_item_2_title, etc.

*   sliced_line_item_{X}_qty - the quantity for line item #{X}
*   sliced_line_item_{X}_title - the title for line item #{X}
*   sliced_line_item_{X}_desc - the description for line item #{X}
*   sliced_line_item_{X}_amt - the amount for line item #{X}

Other Fields:

*   sliced_invoice_status - allows you to set the status of the invoice (unpaid, paid, etc.).  Default is 'draft'.
*   sliced_quote_status - allows you to set the status of the quote (accepted, declined, etc.).  Default is 'draft'.


*NOTE: the names of the fields must match exactly as shown*

**See below for an example form.**

You can also set up confirmations and notifications as per normal in the Contact Form 7 form settings.  However if you want to send the quote or invoice automatically, add the following tag to your form:

`[hidden sliced_quote_send "true"]`
(for quotes)

or

`[hidden sliced_invoice_send "true"]`
(for invoices)

With the form setup and the fields mapped, you simply need to add the form shortcode to one of your pages. When a client fills in your Quote Request form, a new quote will automatically be created with all of their details added to the quote. 

You then need to simply add the line items and pricing to the quote and send to the client.

If the email address that the client fills in is not already linked to a client, the plugin will automatically create a new client with this email.  (Don't worry, if the email address provided matches an existing client/user, it will not be modified for security reasons.)

= An example form: =

`<p>Your Name (required)<br />
    [text* sliced_client_name] </p>

<p>Your Email (required)<br />
    [email* sliced_client_email] </p>

<p>Website (required)<br />
    [url* sliced_client_website] </p>

<p>Business Name (required)<br />
    [text* sliced_client_business] </p>

<p>Address<br />
    [textarea sliced_client_address] </p>

<p>Any extra Business info<br />
    [textarea sliced_client_extra] </p>

<p>Overview of work required (required)<br />
    [text* sliced_title] </p>

<p>Description of work required (required)<br />
    [textarea* sliced_description] </p>

<p>[submit "Send"]</p>`


== Installation ==
1. Upload plugin to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress


== Frequently Asked Questions ==

= Minimum System Requirements =

* WordPress 4.0 or newer
* Contact Form 7 version 5.0 or newer
* Sliced Invoices 3.7 or newer
* PHP version from 5.5 up to 8.0

= Where can I get help? =

For all support issues please [open a Support Ticket on our website](https://slicedinvoices.com/support-ticket/).


== Screenshots ==
1. Creating the Quote Request Form
2. Inserting the Quote Request Form into a page
3. The Quote Request Form on the front end of the site
4. The blank Quote that is created when a user fills in the form. You just need to add your pricing to the quote.


== Changelog ==
= 1.1.3 =
* UPDATE: changes for compatibility with forthcoming Sliced Invoices v3.9.0.
* UPDATE: PHP 8.0 compatibility.

= 1.1.2 =
* FIX: display issue with admin notices.

= 1.1.1 =
* NEW: add requirements check. If either of the 2 required plugins are not found (Contact Form 7 or Sliced Invoices), a notice will be displayed to tell you this.

= 1.1.0 =
* NEW: add support for creating invoices (not just quotes).
* NEW: add new fields for handling of line items, status, etc.
* NEW: automatically populate new quotes/invoices with default Terms and Tax settings (based on your settings).
* NEW: ability to automatically send quote/invoice upon form submission.
* NEW: added new actions 'sliced_cf7_invoice_created' and 'sliced_cf7_quote_created'.
* NEW: added new filter 'sliced_cf7_line_items'.
* FIX: issue with quote numbers not incrementing.

= 1.01 =
* FIX: Add custom validation and stop duplicate entries if invalid fields.

= 1.0 =
* Initial release at WordPress.org.
