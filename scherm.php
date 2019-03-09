<!-- Schermresolutie _____________________________ -->
 
<script type="text/javascript">
document.write(screen.width+'x'+screen.height+'x'+screen.colorDepth);
</script>
 
<?php
    
    if (isset($_GET['width']) AND isset($_GET['height'])) {
   
        $breedte = $_GET['width'];
        $hoogte = $_GET['height'];
        $kleur = $_GET['colorDepth'] . " Bit's";
    }
 
?>