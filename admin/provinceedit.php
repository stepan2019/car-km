<?php
$response = "";
$id = $_GET['id'];
$provinceResult = $config->getProvinById($id);
$province = $provinceResult->fetch_assoc();

if(isset($_POST['update'])) {
    $english = $_POST['english'];
    $arabic = $_POST['arabic'];
    $kurdish = $_POST['kurdish'];
    $greek = $_POST['greek'];
    $update_provin = $config->update_provin($id, $english, $arabic, $greek, $kurdish);
    if($update_provin) {
        echo "<script>window.location='home.php?query=province';</script>";
    } else {
        $response = "Can not update Information";
    }
}
?>

<form method="post">
    <div class="text-center">
        <h3 class="dashboard-txt"><u>Province Edit</u></h3>
    </div>
    <div class="row col-md-12">
        <div class="col-md-1"></div>
        <div class="col-md-5 text-left mt-4">
            <label class="control-label">English</label>
            <div class="agileits-main">
                <input type="text" required id="inputEmail3" name="english" value="<?php echo $province['en'];?>" id="english">
            </div>
        </div>
        <div class="col-md-5 text-left mt-4">
            <label class="control-label">Arabic</label>
            <div class="agileits-main">
                <input type="text" name="arabic" value="<?php echo $province['ar'];?>" id="arabic">
            </div>
        </div>
        <div class="col-md-1"></div>
    </div>
    <div class="row col-md-12">
        <div class="col-md-1"></div>
        <div class="col-md-5 text-left mt-4">
            <label class="control-label">Greek</label>
            <div class="agileits-main">
                <input type="text" id="inputEmail3" name="greek" value="<?php echo $province['el'];?>" id="greek">
            </div>
        </div>
        <div class="col-md-5 text-left mt-4">
            <label class="control-label">Kurdish</label>
            <div class="agileits-main">
                <input type="text" name="kurdish" value="<?php echo $province['Ku'];?>" id="kurdish">
            </div>
        </div>
        <div class="col-md-1"></div>
    </div>



    <div class="text-center" style="margin-top:30px;">
        <button type="submit" class="btn btn-primary submit-fs btn-custom" name="update" style="font-size:1.2em;">Update</button>
        <a href="home.php?query=provincelist" class="btn btn-primary submit-fs btn-custom" style="font-size:1.2em;">Cancel</a>
        <?php if($response != "") { ?>
            <p><label class="control-label mt-3"><?php echo $response; ?></label></p>
        <?php } ?>
    </div>
</form>

<script src="../js/jquery.min.js" ></script>
<script type="text/javascript">

</script>
