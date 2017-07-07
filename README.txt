=== Sliced Invoices & Contact Form 7 ===
Contributors: SlicedInvoices
Tags: contact form 7, contact form 7 add on, contact form 7 invoice, contact form 7 invoice, contact form 7 estimate, contact form 7 quote, invoice, invoicing, quotes, estimates, invoice clients, quote request, estimate request
Requires at least: 4.0
Tested up to: 4.4.1
Stable tag: 1.01
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Create an online quote request form using Contact Form 7. Every form entry then automatically creates a quote in the Sliced Invoices plugin.

== Description ==
Imagine having a form on your website that allows your visitors to basically create their own quotes for you! 

All you need to do once they have submitted the form is read the description of work they require and then set your pricing. All of their client data has already been captured and added to the quote.


= Requirements =
*	[Sliced Invoices Plugin](https://wordpress.org/plugins/sliced-invoices/) (free)
*   [Contact Form 7 Plugin](https://wordpress.org/plugins/contact-form-7/) (free)

= Initial Setup =
Once you have both plugins installed and activated, you simply need to create your Quote Request form that contains the following fields:

**Required Fields**

*   sliced_client_name - the Client Name
*   sliced_client_email - the Client Email
*   sliced_title - becomes the Quote title

**Optional Fields**

You can also add the following optional fields that will map to other Sliced Invoices fields for the quote:

*   sliced_client_business - the Client Business Name - recommended
*   sliced_client_address - the Client Address
*   sliced_client_extra - the Client Extra Info field
*   sliced_description - becomes the Quote description - recommended

*NOTE: the names of the fields must match exactly as shown*

**See the [FAQs](https://wordpress.org/plugins/sliced-invoices-contact-form-7/faq) for an example form.**

You can also set up confirmations and notifications as per normal in the Contact Form 7 form settings. This plugin does not send notifications, it relies on the Contact Form 7 notifications.

With the form setup and the fields mapped, you simply need to add the form shortcode to one of your pages. When a client fills in your Quote Request form, a new quote will automatically be created with all of their details added to the quote. 

You then need to simply add the line items and pricing to the quote and send to the client.

If the email address that the client fills in is not already linked to a client, the plugin will automatically create a new client with this email.



== Installation ==
1. Upload plugin to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress


== Frequently Asked Questions ==

An example form:

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


== Screenshots ==
1. Creating the Quote Request Form
2. Inserting the Quote Request Form into a page
3. The Quote Request Form on the front end of the site
4. The blank Quote that is created when a user fills in the form. You just need to add your pricing to the quote.


== Changelog ==
=1.01 =
FIX: Add custom validation and stop duplicate entries if invalid fields

=1.0 =
* Initial release at WordPress.org
