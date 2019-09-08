<?php
	if(isset($_POST["submit"])) {
		$statusMsg = '';

		$targetDir = "../img/";
		$fileName = basename($_FILES["file"]["name"]);
		$targetFilePath = $targetDir . $fileName;
		$fileType = pathinfo($targetFilePath,PATHINFO_EXTENSION);

		if(isset($_POST["submit"]) && !empty($_FILES["file"]["name"])) {
			if(!isset($_POST['bannerText']))
				$bannerText = "";
			else
				$bannerText = $_POST['bannerText'];
			$link = $_POST['link'];
			$position = $_POST['position'];
			$start_date = $_POST['start_date'];
			$end_date = $_POST['end_date'];

		    $allowTypes = array('jpg','png','jpeg','gif','swf');
		    if(in_array($fileType, $allowTypes)) {
		        if(move_uploaded_file($_FILES["file"]["tmp_name"], $targetFilePath)) {
		            $insert = $config->addAdsBanner($fileName, $bannerText, $link, $position, $start_date, $end_date);
		            if($insert) {
		                $statusMsg = "The file ".$fileName. " has been uploaded successfully and added ads banner.";
		            } else {
		                $statusMsg = "File upload failed, please try again.";
		            } 
		        } else {
		            $statusMsg = "Sorry, there was an error uploading your file.";
		        }
		    } else {
		        $statusMsg = 'Sorry, only JPG, JPEG, PNG, GIF, & SWF files are allowed to upload.';
		    }
		} else {
		    $statusMsg = 'Please select a file to upload.';
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
		            <input type="text" name="bannerText" value="" class="banner-txt">
		        </div>
			</div>	
		</div>
		
		<div class="col-md-6">
			<div class="col-md-12">Select Image File to Upload (468 X 60)</div>
			<div class="col-md-12"><span>( Extensions : JPG, JPEG, PNG, GIF, & SWF )</span></div>
			<div class="col-md-12"><input type="file" name="file"></div>
		</div>

		<div class="col-md-6">
			<div class="col-md-4 mt-4">Link To :</div>
			<div class="col-md-8">
				<div class="agileits-main">
		            <input type="url" name="link" value="" required="" class="banner-txt">
		        </div>
			</div>
		</div>

		<div class="col-md-6">
			<div class="col-md-4 mt-4">Template position :</div>
			<div class="col-md-8">
				<div class="agileits-main">
					<select name="position" required="" class="banner-txt">
				        <option value="header">Header</option>
				        <option value="footer">Footer</option>
				    </select>
				</div>
		    </div>	
		</div>

		<div class="col-md-6">
			<div class="col-md-4 mt-4">Starting Date :</div>
			<div class="col-md-8">
				<div class="agileits-main">
					<input type="text" name="start_date" value="<?php echo date('Y-m-d'); ?>" required="" class="banner-txt">
				</div>
		    </div>	
		</div>

		<div class="col-md-6">
			<div class="col-md-4 mt-4">End Date :</div>
			<div class="col-md-8">
				<div class="agileits-main">
					<input type="text" name="end_date" value="<?php echo date('Y-m-d'); ?>" required="" class="banner-txt">
				</div>
		    </div>	
		</div>
		
		<div class="col-md-12 mt-5 text-center">
			<input type="submit" name="submit" value="Add New Banner" class="btn btn-primary btn-custom submit-fs">
		</div>
	</div>
</form>