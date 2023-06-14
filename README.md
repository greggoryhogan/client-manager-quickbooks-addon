# Client Manager

Client Manager allows you to create clients and sub-clients, and tracks logged hours for each client on an hourly-billing cycle. The frontend allows the client to log in and view a summary of the hours billed that month.

This functionality is based on [The Events Calendar](https://theeventscalendar.com/) and the plugin is required to function.

In order to use this theme, define the following in wp-config:

* define('ADMIN_LOGIN_PIN','PICKAPIN'); //Allows you to log in and view all your hours for the month
* define('ADMIN_ID','THE ID OF THE ADMINISTRATOR WHO LOGS IN'); //Sets the id of the admin when logging in on the front end

TODO:

* Add options page rather than setting definitions in wp-config
