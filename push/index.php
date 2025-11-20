<?php
require 'config.php';

if (isset($_SESSION['admin_id'])) {
    header("Location: admin.php");
} else {
    header("Location: install.php");
}
exit;
?>

