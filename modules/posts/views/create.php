<h1><?= $headline ?></h1>
<?= validation_errors() ?>
<div class="card">
    <div class="card-heading">
        Post Details
    </div>
    <div class="card-body">
        <?php
        echo form_open($form_location);
        echo form_label('Primary Object');
        echo form_input('primary_object', $primary_object, array("placeholder" => "Enter Primary Object"));
        echo form_label('Secondary Object <span>(optional)</span>');
        echo form_input('secondary_object', $secondary_object, array("placeholder" => "Enter Secondary Object"));
        echo form_label('Tutorial <span>(optional)</span>');
        echo form_textarea('tutorial', $tutorial, array("placeholder" => "Enter Tutorial"));
        echo form_label('Pic <span>(optional)</span>');
        echo form_textarea('pic', $pic, array("placeholder" => "Enter Pic"));
        echo form_submit('submit', 'Submit');
        echo anchor($cancel_url, 'Cancel', array('class' => 'button alt'));
        echo form_close();
        ?>
    </div>
</div>