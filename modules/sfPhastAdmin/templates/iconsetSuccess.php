<?php
    if($sf_request->hasParameter('src')):

        foreach ($sections as $key => $section):
            foreach ($section as $key => $icon):
                ?>
                .phast-ui .icon-<?php echo $icon['class']; ?>{background-image: url(<?php echo $icon['filename']; ?>);}<br>
            <?php
            endforeach;
        endforeach;

    else:
?>

<ul class="iconset">
    <?php
        foreach ($sections as $key => $section):
    ?>
            <li>
                <h3><?php echo $key; ?></h3>
                <?php
                    foreach ($section as $key => $icon):
                ?>
                    <i style="background-image: url(<?php echo $icon['filename']; ?>);" title="<?php echo $icon['class']; ?>"></i>
                <?php
                    endforeach;
                ?>
            </li>
    <?php
        endforeach;
    ?>
</ul>

<script>
    (function(){
        var i = 0,
            $icon,
            $icons = document.querySelectorAll('.iconset i');

        while($icon = $icons[i++]){
            $icon.onclick = function(){
                var box = $$.Box.create({template: '<input style="text-align: center" type="text" value="' + this.title + '">'});
                box.open();
                box.getNode().css({left: 'auto', padding: 0}).find('input').css({border: 'none'}).select();
            }
        }
    })();

</script>

<?php
    endif;
?>