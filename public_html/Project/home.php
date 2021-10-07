<?php
require(__DIR__."/../../partials/nav.php");
?>
<h1>Home</h1>
<?php
if(isset($_SESSION["user"]) && isset($_SESSION["user"]["email"])){
 flash("Welcome, " . $_SESSION["user"]["email"]);
}
else{
  flash("You're not logged in");
}
?>