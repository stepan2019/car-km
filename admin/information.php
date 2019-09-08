<?php
    $response = "";

	$result = $config->getInformationContent();
    $information = $result->fetch_assoc();

    if(isset($_POST['add_information'])) {
        $content = $_POST['content'];

        $result = $config->add_information_content($content);
        if($result) {
            echo "<script>window.location = 'home.php';</script>";
        } else {
            $response = "Sorry, is failed to add";
        }
    }
?>
<div>
	<form method="post">
		<p>Content</p>
		<script src="../css/ckeditor/ckeditor.js"></script>
        <textarea id="ckeditor" class="ckeditor" name="content" style="min-height:500px;"><?php echo $information['content'] ?></textarea>

        <?php if($response != "") { ?>
            <p><label class="control-label mt-3"><?php echo $response; ?></label></p>
        <?php } ?>

		<button type="submit" class="btn btn-primary btn-custom" name="add_information">OK</button>
		<a href="/admin" class="btn btn-primary btn-custom">Cancel</a>

	</form>
</div>