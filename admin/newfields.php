<?php
	$resAddMake = "";
	$resDelMake = "";
	$resAddModel = "";
	$resDelModel = "";

	if(isset($_POST['add_make'])) {
        $new_make = $_POST['new_make'];
        $new_makes = explode ("\r\n",$new_make);
        foreach($new_makes as $v){
            $exist = $config->existMake($v);
            if($exist) {
                $resAddMake = "The make already exists.";
            } else {
                $result = $config->add_make($v);
                if($result) {
                    $resAddMake = "The new make has been added successfully.";
                } else {
                    $resAddMake = "Sorry, is failed to add";
                }
            }
        }
    }

    if(isset($_POST['del_make'])) {
    	$makelist = array();
    	foreach ($_POST['makelist'] as $selectedOption) {
    		$makelist[] = $selectedOption;
    	}
        
    	$result = $config->deleteMake($makelist);
    	if($result) {
            $resDelMake = "The selected values has been deleted successfully.";
    	} else {
            $resDelMake = "Sorry, is failed to delete";
    	}
    }

    if(isset($_POST['add_model'])) {
        if(isset($_POST['selectedMakeName'])) {
            $new_model = $_POST['new_model'];
            $new_models = explode ("\r\n",$new_model);
            $selectedMakeName = $_POST['selectedMakeName'];
            
            foreach($new_models as $v){
                $exist = $config->existModel($v, $selectedMakeName);
                if($exist) {
                    $resAddModel = "The model already exists.";
                } else {
                    $result = $config->add_model($v, $selectedMakeName);
                    if($result) {
                        $resAddModel = "The new model has been added successfully.";
                    } else {
                        $resAddModel = "Sorry, is failed to add";
                    }    
                }
            }
        } else {
            $resAddModel = "Please select the car make to add new model.";
        }
    }

    if(isset($_POST['del_model'])) {
    	$modellist = array();
    	foreach ($_POST['modellist'] as $selectedOption) {
    		$modellist[] = $selectedOption;
    	}
        
    	$result = $config->deleteModel($modellist);
    	if($result) {
            $resDelModel = "The selected values has been deleted successfully.";
    	} else {
            $resDelModel = "Sorry, is failed to delete";
    	}
    }
?>

<div class="text-center dashboard-txt mb-4">You can add new Make and Model if there doesn't exist.</div>
<hr>
<div class="row col-md-12">
    <div class="col-md-1"></div>
    <form method="post" class="col-md-6">
        <div class="row col-md-12 text-center">
            <!--<div class="agileits-main mb-3">-->
            <!--    <i class="fas fa-plus-circle"></i>-->
            <!--    <input type="text" name="new_make" required="" placeholder="Input new car make.">-->
            <!--</div>-->
            <div class="agileits-main mb-3">
                <textarea type="text" name="new_make" style="width:250px;height:250px"></textarea>
            </div>
            <div class="ml-1">
	            <button type="submit" class="btn btn-primary btn-custom" name="add_make">Add New Make <i class="fas fa-angle-double-right" style="position: relative;"></i></button>
	        </div>
	        <?php if($resAddMake != "") { ?>
                <p><label class="control-label mt-3"><?php echo $resAddMake; ?></label></p>
            <?php } ?>
        </div>
    </form>
    <form method="post" class="col-md-5">
        <div class="row col-md-12 text-center">
            <div class="agileits-main mb-3">
                <select name="makelist[]" required="" id="makelist" multiple="" style="min-width: 270px; height: 260px;">
                <?php 
                    $getMakeList = $config->getMakeList();
                    while($getMakeList_fetch = $getMakeList->fetch_assoc()) {
                ?>
                        <option value="<?php echo $getMakeList_fetch['name']; ?>">
                            <?php echo $getMakeList_fetch['name']; ?>
                        </option>
                <?php
                    }
                ?>
                </select>
            </div>
            <div class="ml-1">
            	<button type="submit" class="btn btn-primary btn-custom" name="del_make">Delete <i class="far fa-trash-alt" style="position: relative;"></i></button>
            </div>
        </div>
        <?php if($resDelMake != "") { ?>
            <p><label class="control-label mt-3"><?php echo $resDelMake; ?></label></p>
        <?php } ?>
    </form>
</div>
<hr>
<div class="row col-md-12">
    <div class="col-md-1"></div>
    <form method="post" class="col-md-6">
        <div class="row col-md-12 text-center">
            <div class="agileits-main mb-3">
                <i class="far fa-building"></i>
                <select name="selectedMakeName" required="" id="selected_make" style="min-width: 260px;">
                    <option disabled selected>Select Car Make</option>
                <?php 
                    $getMakeList = $config->getMakeList();
                    while($getMakeList_fetch = $getMakeList->fetch_assoc()) {
                ?>
                        <option value="<?php echo $getMakeList_fetch['name']; ?>">
                            <?php echo $getMakeList_fetch['name']; ?>
                        </option>
                <?php
                    }
                ?>
                </select>
            </div>
            <div class="row col-md-12">
	            <div class="agileits-main mb-3">
	                <!--<i class="fas fa-plus-circle"></i>-->
	                <!--<input type="text" name="new_model" required="" placeholder="Input new car model.">-->
	                <textarea type="text" name="new_model" style="width:250px;height:200px"></textarea>
	            </div>
            	<div class="ml-1">
		            <button type="submit" class="btn btn-primary btn-custom" name="add_model">Add New Model <i class="fas fa-angle-double-right" style="position: relative;"></i></button>
		        </div>
		        <?php if($resAddModel != "") { ?>
	                <p><label class="control-label mt-3"><?php echo $resAddModel; ?></label></p>
	            <?php } ?>
            </div>
        </div>
    </form>
    <form method="post" class="col-md-5">
        <div class="row col-md-12 text-center">
            <div class="agileits-main mb-3">
                <select name="modellist[]" required="" id="modellist" multiple="" style="width: 270px; height: 260px;">
                <?php 
                    $getModelList = $config->getModelList();
                    while($getModelList_fetch = $getModelList->fetch_assoc()) {
                ?>
                        <option value="<?php echo $getModelList_fetch['name']; ?>">
                            <?php echo $getModelList_fetch['name']; ?>
                        </option>
                <?php
                    }
                ?>
                </select>
            </div>
            <div class="ml-1"><button class="btn btn-primary btn-custom" name="del_model">Delete <i class="far fa-trash-alt" style="position: relative;"></i></button></div>
        </div>
        <?php if($resDelModel != "") { ?>
            <p><label class="control-label mt-3"><?php echo $resDelModel; ?></label></p>
        <?php } ?>
    </form>
    <div class="col-md-1"></div>
</div>