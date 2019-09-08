<?php
    if(isset($_GET['res'])) {
        $res = "Sorry, failed to remove.";
        echo $res;
    }
?>
<div>
	<table class="table table-bordered" id="wang-dataTable">
        <thead>
            <tr>
                <th class="text-center">#</th>
                <th class="text-center">Title</th>
                <th class="text-center">Position</th>
                <th class="text-center">Link</th>
                <th class="text-center">Starting Date</th>
                <th class="text-center">End Date</th>
                <th class="text-center">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php 
				$bannerList = $config->getBanner();
				$s_sn = 1;
				if($bannerList->num_rows > 0) {
					while($banner = $bannerList->fetch_assoc()) { 
			?>
            <tr>
                <td class="text-center"><?php echo $s_sn; ?></td>
                <td class="text-center"><?php echo $banner['bannerText']; ?></td>
                <td class="text-center"><?php echo $banner['position']; ?></td>
                <td class="text-center"><?php echo $banner['link']; ?></td>
                <td class="text-center"><?php echo $banner['start_date']; ?></td>
                <td class="text-center"><?php echo $banner['end_date']; ?></td>

                <td class="text-center">
                    <a href="home.php?query=banneredit&id=<?php echo $banner['id']; ?>">
                        <i class="far fa-edit"></i>
                    </a>
                    <a href="home.php?query=banneractivate&id=<?php echo $banner['id']; ?>" style="padding: 0 5px;">
                        <?php
                            if($banner['activate'] == "yes") {
                        ?>
                                <i class="far fa-hand-point-up"></i>
                        <?php
                            } else {
                        ?>
                                <i class="far fa-hand-point-down"></i>
                        <?php
                            }
                        ?>
                    </a>
                    <a href="home.php?query=bannerdelete&id=<?php echo $banner['id']; ?>">
                    	<i class="far fa-trash-alt"></i>
                    </a>
                </td>
            </tr>
            <?php $s_sn++; }} else {
			?>
            <td colspan="12">No any banner information found
            </td>
            <?php 
						}  ?>
        </tbody>
    </table>
</div>