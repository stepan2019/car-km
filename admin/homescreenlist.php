<?php
    if(isset($_GET['res'])) {
        $res = "Sorry, failed to remove.";
        echo $res;
    }
?>
<div>
    <a href="home.php?query=homescreen" class="btn btn-primary btn-custom submit-fs">Add New Homescreen Image</a>
	<table class="table table-bordered" id="wang-dataTable">
        <thead>
            <tr>
                <th class="text-center">#</th>
                <th class="text-center">Title</th>
                <th class="text-center">Position</th>
                <th class="text-center">Filename</th>
                <th class="text-center">Size</th>
                <th class="text-center">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php 
				$homescreenList = $config->getHomescreen();
				$s_sn = 1;
				if($homescreenList->num_rows > 0) {
					while($homescreen = $homescreenList->fetch_assoc()) { 
			?>
            <tr>
                <td class="text-center"><?php echo $s_sn; ?></td>
                <td class="text-center"><?php echo $homescreen['title']; ?></td>
                <td class="text-center"><?php echo $homescreen['position']; ?></td>
                <td class="text-center"><?php echo $homescreen['file_name']; ?></td>
                <td class="text-center"><?php echo $homescreen['width']; ?> X <?php echo $homescreen['height']; ?></td>

                <td class="text-center">
                    <a href="home.php?query=homescreenedit&id=<?php echo $homescreen['id']; ?>">
                        <i class="far fa-edit"></i>
                    </a>
                    <a href="home.php?query=homescreendelete&id=<?php echo $homescreen['id']; ?>">
                    	<i class="far fa-trash-alt"></i>
                    </a>
                </td>
            </tr>
            <?php $s_sn++; }} else {
			?>
            <td colspan="12">No any Homescreen information found
            </td>
            <?php 
						}  ?>
        </tbody>
    </table>
</div>