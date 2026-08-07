<?php

/**
 * Handles /dev and /dev/ -- the server redirects the extensionless path to
 * this directory index. Same destination as /dev.php.
 */

header('Location: ../internal/dev.php', true, 302);
exit;
