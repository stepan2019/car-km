<div>
    <?php
        $dealerList = $config->getDealerList();
        $count = $dealerList->num_rows;
    ?>
    <h2 class="mb-5">Total Dealers : <?php echo $count; ?></h2>
	<table class="table table-bordered" id="wang-dataTable">
        <thead>
            <tr>
                <th class="text-center">#</th>
                <th>Name</th>
                <th>Address</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Company</th>
                <th>Website</th>
                <th>Active</th>
                <th>Block</th>
                <th class="text-center">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php 
				$s_sn = 1;
				if($count > 0) {
					while($dealer = $dealerList->fetch_assoc()) { 
			?>
            <tr>
                <td class="text-center"><?php echo $s_sn; ?></td>
                <td><?php echo $dealer['name']; ?></td>
                <td><?php echo $dealer['address']; ?></td>
                <td><?php echo $dealer['email']; ?></td>
                <td><?php echo $dealer['phone']; ?></td>
                <td><?php echo $dealer['company']; ?></td>
                <td><?php echo $dealer['website']; ?></td>
                <td>
                    <input type="checkbox" value="<?php echo $dealer['active']; ?>" name="dealer_active" data-id="<?php echo $dealer['id']; ?>"
                        <?php
                            if($dealer['active'] == 1) {
                                echo "checked";
                            }
                        ?>
                    >
                </td>
                <td>
                    <input type="checkbox" value="<?php echo $dealer['block']; ?>" name="dealer_block" data-id="<?php echo $dealer['id']; ?>"
                        <?php
                            if($dealer['block'] == "True") {
                                echo "checked";
                            }
                        ?>
                    >
                </td>

                <td class="text-center">
                    <a href="home.php?query=dealeredit&id=<?php echo $dealer['id']; ?>">
                        <i class="far fa-edit" style="float:left;"></i>
                    </a>
                    <a href="home.php?query=dealerdelete&id=<?php echo $dealer['id']; ?>">
                        <i class="far fa-trash-alt" style="float:right;"></i>
                    </a>
                </td>
            </tr>
            <?php $s_sn++; }} else {
			?>
            <td colspan="12">No any dealer information found
            </td>
            <?php 
						}  ?>
        </tbody>
    </table>
</div>
