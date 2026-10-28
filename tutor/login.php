<?php
/** URL login tutor lama tetap berfungsi melalui pintu masuk terpadu. */
header('Cache-Control: no-store');
header('Location: ../login.php?role=tutor', true, 303);
exit;
