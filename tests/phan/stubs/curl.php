<?php

/**
 * These constants only serve to silence some of phan's false positives.
 * This should not be used anywhere other than in the static analysis.
 */
define('CURLOPT_URL', -1);
define('CURLOPT_POST', -1);
define('CURLOPT_HTTPHEADER', -1);
define('CURLOPT_RETURNTRANSFER', -1);
define('CURLOPT_SSL_VERIFYPEER', -1);
define('CURLOPT_POSTFIELDS', -1);
define('CURLOPT_HEADER', -1);
