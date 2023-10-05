# Client Manager Quickbooks Add-On

This plugin connects to Quickbooks to add a summary of year-to-date invoices in the WordPress dashboard and individual summaries in the client editor. It is targeted towards **advanced users**, as it requires a little extra work to get working.

You must [create your own app in Quickbooks](https://developer.intuit.com/app/developer/qbo/docs/develop) to use this plugin. Once your app is published, add the following constants to wp-config.php:

define('QB_CLIENT_ID','*YOUR QB CLIENT ID*');

define('QB_CLIENT_SECRET','*YOUR QB CLIENT SECRET*');

To use the individual client summary, add a definition for each client in wp-config with the post name (slug) and nameId for your client. For example, when the client is 'My Name is Gregg' (https://yoursite.com/clients/my-name-is-gregg/) and the customer nameId is 5 (https://app.qbo.intuit.com/app/customerdetail?nameId=5), add the following constant:

define('CM-QBA-my-name-is-gregg',5);

Repeat this process for each client in your client manager.

TODO:

* Add checks for Quickbooks client id and secret before making a request
