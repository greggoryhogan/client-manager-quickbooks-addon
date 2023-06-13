# Client Manager

A child theme of twentytwenty that manages clients and tracks logged hours. Create clients, categorize hours based on client access, summarize time billed, and give access to your client to review monthly hours.

This functionality is based on [The Events Calendar](https://theeventscalendar.com/) and changing 'events' to 'hours.'

In order to use this theme, define the following in wp-config:

// Admin credentials for logging in from the frontend

* define('ADMIN_LOGIN_PIN','PICKAPIN');
* define('ADMIN_ID','THE ID OF THE ADMINISTRATOR WHO LOGS IN');

// SMTP Credentials so we aren't relying on the server to send emails

* define('SMTP_HOST','host.youremail.com');
* define('SMTP_FROM','YOUR NAME');
* define('SMTP_EMAIL','sample@sample.com');
* define('SMTP_PASSWORD','YOUR PASSWORD');
* define('SMTP_PORT','587');

### TODO:

* Separate functionality from child theme (aka this should be a plugin)
* Add options page rather than setting definitions as
