<?php
$version['name']            = 'Neostrada';
$version['api_version']     = '1.0';
$version['date']            = '2016-01-20'; // Last modification date
$version['wefact_version']  = '1.1'; // Version released for WeFact
$version['autorenew']       = true; // AutoRenew is default?  true | false
$version['handle_support']  = true; // Handles are supported? true | false
$version['cancel_direct']   = false; // Possible to terminate domains immediately?  true | false
$version['cancel_expire']   = false; // Possible to stop auto-renew for domains? true | false

// Information for customer (will be showed at registrar-show-page)
$version['dev_logo']		= ''; // URL to your logo
$version['dev_author']		= 'Neostrada'; // Your companyname
$version['dev_website']		= 'https://www.neostrada.nl'; // URL website
$version['dev_email']		= 'support@neostrada.nl'; // Your e-mailaddress for support questions
//$version['dev_phone']		= ''; // Your phone number for support questions

// Does this registrar integration support functions related to domains?
$version['domain_support']  = true;
// Does this registrar integration support functions related to SSL certificates?
$version['ssl_support']   	= false;

// Does this registrar integration support functions related to DNS management?
$version['dns_management_support']   	= false;
// Does this registrar integration support DNS templates?
$version['dns_templates_support']       = false;
// Does this registrar integration support DNS records?
$version['dns_records_support']         = false;
?>