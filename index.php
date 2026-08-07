<?php

/**
 * Convenience redirect for hosts whose document root is the repository root
 * rather than public/. Relative on purpose, so a clone works on any domain.
 */

header('Location: public/');
exit;
