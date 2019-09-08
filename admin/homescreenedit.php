<?php
	$id = $_GET['id'];
    $result = $config->getHomescreenById($id);
    $homescreen = $result->fetch_assoc();

	if(isset($_POST["update"])) {
		$statusMsg = '';

		if(!isset($_POST['title']))
			$title = "";
		else
			$title = $_POST['title'];
		$file_name = $_POST['file_name'];
		$position = $_POST['position'];
		$imgWidth = $_POST['imgWidth'];
		$imgHeight = $_POST['imgHeight'];
		$info = @getimagesize($_FILES["file"]["tmp_name"]);
		$width = $info[0];
		$height = $info[1];

		$targetDir = "../img/";
		$uploadFileName = basename($_FILES["file"]["name"]);
		$targetFilePath = $targetDir . $uploadFileName;
		$fileType = pathinfo($targetFilePath,PATHINFO_EXTENSION);

		if($uploadFileName == "") {
			$isSuccess = $config->updateHomescreen($id, $file_name, $title, $position, $imgWidth, $imgHeight);
            if($isSuccess) {
                $statusMsg = "Updated successfully.";
            } else {
                $statusMsg = "Failed, please try again.";
            } 
		} else if(isset($_POST["update"]) && !empty($_FILES["file"]["name"])) {
		    $allowTypes = array('jpg','png','jpeg','gif','swf');
		    if(in_array($fileType, $allowTypes)) {
		        if(move_uploaded_file($_FILES["file"]["tmp_name"], $targetFilePath)) {
		            $isSuccess = $config->updateHomescreen($id, $uploadFileName, $title, $position, $width, $height);
		            if($isSuccess) {
		                echo "<script>window.location = 'home.php?query=homescreenlist';</script>";
		            } else {
		                $statusMsg = "Update failed, please try again.";
		            } 
		        } else {
		            $statusMsg = "Sorry, there was an error uploading your file.";
		        }
		    } else {
		        $statusMsg = 'Sorry, only JPG, JPEG, PNG, GIF, & SWF files are allowed to upload.';
		    }
		} else {
		    $statusMsg = 'Failed, please try again.';
		}

		echo $statusMsg;
	}
?>

<form method="post" enctype="multipart/form-data">
	<div class="col-md-12 row">
		<div class="col-md-6">
			<div class="col-md-4">Title :</div>
			<div class="col-md-8">
				<div class="agileits-main">
		            <input type="text" name="title" value="<?php echo $homescreen['title']; ?>" class="banner-title">
		        </div>
			</div>	
		</div>
		
		<div class="col-md-12 mt-4 row">
			<div class="col-md-7 pl-5">
				<p>Current Banner Image</p>
				<img width="500px" height="300px" src="../img/<?php echo $homescreen["file_name"] ?>" alt="current image">
				<input hidden type="text" name="file_name" value="<?php echo $homescreen['file_name']; ?>">
				<input hidden type="text" name="imgWidth" value="<?php echo $homescreen['width']; ?>">
				<input hidden type="text" name="imgHeight" value="<?php echo $homescreen['height']; ?>">
				<div class="col-md-12 mt-3">You can select another banner image (468 X 60)</div>
				<div class="col-md-12"><span>( Extensions : JPG, JPEG, PNG, GIF, & SWF )</span></div>
				<div class="col-md-12"><input type="file" name="file"></div>	
			</div>
			<div class="col-md-5">
				<div class="col-md-12">
					<div class="col-md-12 mt-4">Template position :</div>
					<div class="col-md-12">
						<div class="agileits-main">
							<select name="position" required="" class="banner-txt">
						        <option <?php if($homescreen['position'] == "header") echo "selected"; ?> value="header">Header</option>
						        <option <?php if($homescreen['position'] == "footer") echo "selected"; ?> value="footer">Footer</option>
						    </select>
						</div>
				    </div>	
				</div>	
			</div>
		</div>
		
		<div class="col-md-12 mt-5 text-center">
			<input type="submit" name="update" value="Update" class="btn btn-primary btn-custom submit-fs">
		</div>
	</div>
</form>