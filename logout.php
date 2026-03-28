<?php
require_once '../proyecto/includes/config.php';
session_destroy();
header('Location: index.php');
exit;
