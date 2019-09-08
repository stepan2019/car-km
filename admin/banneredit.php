<?php
	$id = $_GET['id'];
    $result = $config->getBannerById($id);
    $banner = $result->fetch_assoc();

	if(isset($_POST["update"])) {
		$statusMsg = '';

		if(!isset($_POST['bannerText']))
			$bannerText = "";
		else
			$bannerText = $_POST['bannerText'];
		$bannerImageName = $_POST['bannerImageName'];
		$link = $_POST['link'];
		$position = $_POST['position'];
		$start_date = $_POST['start_date'];
		$end_date = $_POST['end_date'];

		$targetDir = "../img/";
		$fileName = basename($_FILES["file"]["name"]);
		$targetFilePath = $targetDir . $fileName;
		$fileType = pathinfo($targetFilePath,PATHINFO_EXTENSION);

		if($fileName == "") {
			$isSuccess = $config->updateAdsBanner($id, $bannerImageName, $bannerText, $link, $position, $start_date, $end_date);
            if($isSuccess) {
                $statusMsg = "Updated successfully.";
            } else {
                $statusMsg = "Failed, please try again.";
            } 
		} else if(isset($_POST["update"]) && !empty($_FILES["file"]["name"])) {
		    $allowTypes = array('jpg','png','jpeg','gif','swf');
		    if(in_array($fileType, $allowTypes)) {
		        if(move_uploaded_file($_FILES["file"]["tmp_name"], $targetFilePath)) {
		            $isSuccess = $config->updateAdsBanner($id, $fileName, $bannerText, $link, $position, $start_date, $end_date);
		            if($isSuccess) {
		                $statusMsg = "The file ".$fileName. " has been uploaded successfully and updated ads banner.";
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
		            <input type="text" name="bannerText" value="<?php echo $banner['bannerText']; ?>" class="banner-title">
		        </div>
			</div>	
		</div>
		
		<div class="col-md-12 mt-4 row">
			<div class="col-md-7 pl-5">
				<p>Current Banner Image</p>
				<img width="468px" height="160px" src="../img/<?php echo $banner["fileName"] ?>" alt="current image">
				<input hidden type="text" name="bannerImageName" value="<?php echo $banner['fileName']; ?>">
				<div class="col-md-12 mt-3">You can select another banner image (468 X 60)</div>
				<div class="col-md-12"><span>( Extensions : JPG, JPEG, PNG, GIF, & SWF )</span></div>
				<div class="col-md-12"><input type="file" name="file"></div>	
			</div>
			<div class="col-md-5">
				<div class="col-md-12">
					<div class="col-md-12 mt-4">Link To :</div>
					<div class="col-md-12">
						<div class="agileits-main">
				            <input type="url" name="link" value="<?php echo $banner['link']; ?>" required="" class="banner-txt">
				        </div>
					</div>
				</div>
				<div class="col-md-12">
					<div class="col-md-12 mt-4">Template position :</div>
					<div class="col-md-12">
						<div class="agileits-main">
							<select name="position" required="" class="banner-txt">
						        <option <?php if($banner['position'] == "header") echo "selected"; ?> value="header">Header</option>
						        <option <?php if($banner['position'] == "footer") echo "selected"; ?> value="footer">Footer</option>
						    </select>
						</div>
				    </div>	
				</div>	
			</div>
		</div>

		<div class="col-md-6">
			<div class="col-md-4 mt-4">Starting Date :</div>
			<div class="col-md-8">
				<div class="agileits-main">
					<input type="text" name="start_date" value="<?php echo $banner['start_date']; ?>" required="" class="banner-txt">
				</div>
		    </div>	
		</div>

		<div class="col-md-6">
			<div class="col-md-4 mt-4">End Date :</div>
			<div class="col-md-8">
				<div class="agileits-main">
					<input type="text" name="end_date" value="<?php echo $banner['end_date']; ?>" required="" class="banner-txt">
				</div>
		    </div>	
		</div>
		
		<div class="col-md-12 mt-5 text-center">
			<input type="submit" name="update" value="Update" class="btn btn-primary btn-custom submit-fs">
		</div>
	</div>
</form>