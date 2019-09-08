<?php
    $response = "";

	$result = $config->getContent();
    $content = $result->fetch_assoc();

    if(isset($_POST['add_content'])) {
        $content1 = $_POST['content1'];
        $content2 = $_POST['content2'];

        $result = $config->add_content($content1, $content2);
        if($result) {
            echo "<script>window.location = 'home.php';</script>";
        } else {
            $response = "Sorry, is failed to add";
        }
    }
?>
<div>
	<form method="post">
		<p>Content1</p>
		<script src="../css/ckeditor/ckeditor.js"></script>
        <textarea id="ckeditor" class="ckeditor" name="content1" style="min-height:200px;"><?php echo $content['content1'] ?></textarea>

        <p class="mt-5">Content2</p>
        <textarea id="ckeditor" class="ckeditor" name="content2" style="min-height:200px;"><?php echo $content['content2'] ?></textarea>

		<button type="submit" class="btn btn-primary submit-fs btn-custom mt-5" name="add_content">OK</button>
		<a href="/admin" class="btn btn-primary submit-fs btn-custom mt-5">Cancel</a>

        <div>
            <?php if($response != "") { ?>
                <p><label class="control-label mt-3"><?php echo $response; ?></label></p>
            <?php } ?>
        </div>

	</form>
</div>