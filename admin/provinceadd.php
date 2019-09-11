<?php 
    $response = "";

    if(isset($_POST['add'])) {
        $english = $_POST['english'];
        $arabic = $_POST['arabic'];
        $kurdish = $_POST['kurdish'];
        $greek = $_POST['greek'];
        $add_provin = $config->add_provin($english, $arabic, $greek, $kurdish);

        if($add_provin) {
            echo "<script>window.location='home.php?query=province';</script>";
        } else {
            $response = "Can not add Information";
        }
    }
?>

<form method="post">
    <div class="text-center">
        <h3 class="dashboard-txt"><u>Province Add</u></h3>
    </div>
    <div class="row col-md-12">
        <div class="col-md-1"></div>
        <div class="col-md-5 text-left mt-4">
            <label class="control-label">English</label>
            <div class="agileits-main">
                <input type="text" id="inputEmail3" name="english" id="english">
            </div>
        </div>
        <div class="col-md-5 text-left mt-4">
            <label class="control-label">Arabic</label>
            <div class="agileits-main">
                <input type="text" required="" name="arabic" id="arabic">
            </div>
        </div>
        <div class="col-md-1"></div>
    </div>
    <div class="row col-md-12">
        <div class="col-md-1"></div>
        <div class="col-md-5 text-left mt-4">
            <label class="control-label">Greek</label>
            <div class="agileits-main">
                <input type="text" id="inputEmail3" name="greek" id="greek">
            </div>
        </div>
        <div class="col-md-5 text-left mt-4">
            <label class="control-label">Kurdish</label>
            <div class="agileits-main">
                <input type="text" required="" name="kurdish" id="kurdish">
            </div>
        </div>
        <div class="col-md-1"></div>
    </div>



    <div class="text-center" style="margin-top:30px;">
        <button type="submit" class="btn btn-primary submit-fs btn-custom" name="add" style="font-size:1.2em;">Add</button>
        <a href="home.php?query=provincelist" class="btn btn-primary submit-fs btn-custom" style="font-size:1.2em;">Cancel</a>
        <?php if($response != "") { ?>
            <p><label class="control-label mt-3"><?php echo $response; ?></label></p>
        <?php } ?>
    </div>
</form>
        
<script src="../js/jquery.min.js" ></script>
<script type="text/javascript">

</script>
