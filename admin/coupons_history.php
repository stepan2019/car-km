<?php

    $result = $config->getCouponHistory();
    $couponData = $result->fetch_All();
    //var_dump($couponData);exit;
?>

<div>
    
    <table class="table table-bordered" id="wang-dataTable">
        <thead>
            <tr>
                <th class="text-center">#</th>
                <th>Code</th>
                <th>User</th>
                <th>Vin</th>
                <!--<th>Amount</th>-->
                <th class="text-center">Action</th>
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
                <td><?php echo $row[4]; ?></td>
                <td><?php echo $row[3]; ?></td>
                <!--<td><?php echo $row[2]; ?></td>-->
                <td class="text-center">
                    <a href="home.php?query=deletecouponhistory&id=<?php echo $row[0]; ?>">
                    	<i class="far fa-trash-alt" style="float:right;"></i>
                    </a>
                </td>
            </tr>
            <?php $s_sn++; }?>
        </tbody>
    </table>
</div>
