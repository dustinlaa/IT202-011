<?php
session_start();
session_unset();
session_destroy();
require(__DIR__ . "/../../lib/functions.php");
require_once(__DIR__ . "/../../partials/nav.php");

flash("Successfully logged out", "success");

die(header("Location: login.php"));