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
		<p>Index Content</p>
        <textarea id="id_cazary" class="form-control" name="content" style="min-height:500px;"><?php echo $information['content'] ?></textarea>

        <p><?php echo $indexcontent['content']; ?></p>

		<button type="submit" class="btn btn-primary btn-custom" name="add_information">OK</button>
		<a href="/admin" class="btn btn-primary btn-custom">Cancel</a>

	</form>
</div>