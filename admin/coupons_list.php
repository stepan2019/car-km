<?php

    $result = $config->getCoupon();
    $couponData = $result->fetch_All();
    //var_dump($couponData);exit;
    if(isset($_POST['save_code'])) {
        $code = $_POST['code'];
        $allow_usage = $_POST['allow_usage'];

        
        $result = $config->add_coupon($code, $allow_usage);
        if($result) {
            // header("location:home.php");
            $result = $config->getCoupon();
            $couponData = $result->fetch_All();
        } else {
            echo "<script>alert('Sorry ...');</script>";
        }
    }
?>

<div>
    
    <table class="table table-bordered" id="wang-dataTable">
        <thead>
            <tr>
                <th class="text-center">#</th>
                <th>Code</th>
                <th>Allow Usage</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php 
				$s_sn = 1;
				foreach($couponData as $row){ 
			?>
            <tr>
                <td class="text-center"><?php echo $s_sn; ?></td>
                <td><?php echo $row[1]; ?></td>
                <td><?php echo $row[2]; ?></td>
                <td class="text-center">
                    <a href="home.php?query=couponedit&id=<?php echo $row[0]; ?>">
                    	<i class="far fa-edit" style="float:left;"></i>
                    </a>
                    <a href="home.php?query=coupondelete&id=<?php echo $row[0]; ?>">
                    	<i class="far fa-trash-alt" style="float:right;"></i>
                    </a>
                </td>
            </tr>
            <?php $s_sn++; }?>
        </tbody>
    </table>
</div>
