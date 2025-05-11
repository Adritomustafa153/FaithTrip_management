Setup Instructions:

1. Import the following SQL into your MySQL server:
----------------------------------------------------
CREATE TABLE passengers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    email VARCHAR(100),
    date_of_birth DATE,
    passport_number VARCHAR(50),
    passport_expiry DATE
);
----------------------------------------------------

2. Update your database credentials in:
- add_passenger.php
- daily_check.php

3. Host this folder on a PHP-supported web server.

4. Set up a cron job to run `daily_check.php` daily:
Example (in cPanel or Linux):
0 7 * * * /usr/bin/php /path/to/daily_check.php

5. Upload a birthday image to your server (optional) and update the image link in `daily_check.php`.

Done!