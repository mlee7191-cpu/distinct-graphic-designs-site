<?php
/*
 * Template for the enquiry form's private settings. NOT deployed (it is not in .cpanel.yml) and it contains no real secrets.
 *
 * Setup on the server, once:
 *   1. cPanel > MySQL Databases: create a database and a user, and add the user to the database with ALL PRIVILEGES
 *      (the script creates its own `enquiries` table on the first enquiry).
 *   2. Copy this file to /home/discomau/enquiry-config.php, ONE LEVEL ABOVE public_html so the web can never serve it,
 *      and fill in the real values below. Never commit the real file (it is in .gitignore).
 *   3. cPanel > Email Deliverability: make sure SPF and DKIM are valid for distinctgraphicdesigns.com.au, or the enquiry
 *      emails are likely to be filed as spam.
 *   4. Deploy, send a test enquiry from the home page, then check the inbox and the `enquiries` table in phpMyAdmin.
 */
return [
    'db_host' => 'localhost',
    'db_name' => 'discomau_enquiries',
    'db_user' => 'discomau_enquiries',
    'db_pass' => 'CHANGE_ME',

    // Where enquiries are emailed, and the address they are sent from (use an address on this domain so SPF/DKIM pass).
    'to'      => 'mark@distinctgraphicdesigns.com.au',
    'from'    => 'enquiries@distinctgraphicdesigns.com.au',

    // Any long random string. It is mixed into the hash of each visitor's IP address (used only for the spam rate limit).
    'salt'    => 'CHANGE_ME_TO_A_LONG_RANDOM_STRING',
];
