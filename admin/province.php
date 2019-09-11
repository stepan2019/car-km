<div>
    <?php
        $provinceList = $config->getProvinList();
        $count = $provinceList->num_rows;
    ?>
    <div class="mb-4">
        <span class="dashboard-txt">Total Provinces : <?php echo $count; ?></span>
        <a href="home.php?query=provinceadd" class="btn btn-primary submit-fs btn-custom ml-5">New Province</a>
    </div>
	<table class="table table-bordered" id="wang-dataTable">
        <thead>
            <tr>
                <th class="text-center">#</th>
                <th>English</th>
                <th>Arabic</th>
                <th>Greek</th>
                <th>Kurdish</th>
                <th class="text-center">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php 
				$s_sn = 1;
				if($count > 0) {
					while($province = $provinceList->fetch_assoc()) {
			?>
            <tr>
                <td class="text-center"><?php echo $s_sn; ?></td>
                <td><?php echo $province['en']; ?></td>
                <td><?php echo $province['ar']; ?></td>
                <td><?php echo $province['el']; ?></td>
                <td><?php echo $province['Ku']; ?></td>
                <td class="text-center">
                    <a href="home.php?query=provinceedit&id=<?php echo $province['id']; ?>">
                    	<i class="far fa-edit" style="float:left;"></i>
                    </a>
                    <a href="home.php?query=provincedelete&id=<?php echo $province['id']; ?>">
                    	<i class="far fa-trash-alt" style="float:right;"></i>
                    </a>
                </td>
            </tr>
            <?php $s_sn++; }} else {
			?>
            <td colspan="12">No any vehicle information found
            </td>
            <?php 
		        }
            ?>
        </tbody>
    </table>
</div>
