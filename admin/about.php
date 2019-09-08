<?php
    $response = "";

	$result = $config->getAboutContent();
    $about = $result->fetch_assoc();

    if(isset($_POST['add_about'])) {
        $content = $_POST['content'];
        $content2 = $_POST['content2'];

        $result = $config->add_about_content($content, $content2);
        if($result) {
            echo "<script>window.location = 'home.php';</script>";
        } else {
            $response = "Sorry, is failed to add";
        }
    }
?>
<div>
	<form method="post">
		<p>First Content</p>
		<script src="../css/ckeditor/ckeditor.js"></script>
        <textarea id="ckeditor" class="ckeditor" name="content" style="min-height:250px;"><?php echo $about['content'] ?></textarea>
        <p>Second Content</p>
        <textarea id="ckeditor" class="ckeditor" name="content2" style="min-height:250px;"><?php echo $about['content2'] ?></textarea>

        <?php if($response != "") { ?>
            <p><label class="control-label mt-3"><?php echo $response; ?></label></p>
        <?php } ?>

		<button type="submit" class="btn btn-primary btn-custom" name="add_about">OK</button>
		<a href="/admin" class="btn btn-primary btn-custom">Cancel</a>

	</form>
</div>