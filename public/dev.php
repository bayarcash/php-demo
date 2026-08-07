<?php

/**
 * Shortcut for the team's muscle memory: the dev form used to live at
 * /v2/dev.php. Relative on purpose, so it works on any host.
 */

header('Location: internal/dev.php', true, 302);
exit;
