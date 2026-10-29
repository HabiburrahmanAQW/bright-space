<?php
/** Kompatibilitas tautan login tutor lama menuju satu form login. */
header('Cache-Control: no-store');
header('Location: ../login.php', true, 303);
exit;
