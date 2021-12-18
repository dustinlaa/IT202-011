<div id="point-value">
    User: <?php echo get_username();?> | Points: <?php echo get_account_points(); ?>
</div>
<script>
    let bv = document.getElementById("point-value");
    let placeholders = document.getElementsByClassName("show-points");
    for (let p of placeholders) {
        p.innerHTML = bv.outerHTML;
    }
    bv.remove();
</script>