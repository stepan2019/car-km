<div>
    <?php
    $userList = $config->getUserList();
    $count = $userList->num_rows;
    ?>
    <h2 class="mb-5">Total Users : <?php echo $count; ?></h2>
    <table class="table table-bordered" id="wang-dataTable">
        <thead>
        <tr>
            <th class="text-center">#</th>
            <th>Name</th>
            <th>Address</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Active</th>
            <th>Block</th>
            <th class="text-center">Action</th>
        </tr>
        </thead>
        <tbody>
        <?php
        $s_sn = 1;
        if ($count > 0) {
            while ($user = $userList->fetch_assoc()) {
                ?>
                <tr>
                    <td class="text-center"><?php echo $s_sn; ?></td>
                    <td><?php echo $user['name']; ?></td>
                    <td><?php echo $user['address']; ?></td>
                    <td><?php echo $user['email']; ?></td>
                    <td><?php echo $user['phone']; ?></td>
                    <td><?php echo ($user['active']) ? 'Yes' : 'No'; ?></td>
                    <td>
                        <input type="checkbox" value="<?php echo $user['block']; ?>" name="user_block"
                               data-id="<?php echo $user['id']; ?>"
                            <?php
                            if ($user['block'] == "True") {
                                echo "checked";
                            }
                            ?>
                        >
                    </td>

                    <td class="text-center">
                        <a href="home.php?query=useredit&id=<?php echo $user['id']; ?>">
                            <i class="far fa-edit" style="float:left;"></i>
                        </a>
                        <a href="home.php?query=userdelete&id=<?php echo $user['id']; ?>">
                            <i class="far fa-trash-alt" style="float:right;"></i>
                        </a>
                    </td>
                </tr>
                <?php $s_sn++;
            }
        } else {
            ?>
            <td colspan="12">No any user information found
            </td>
            <?php
        } ?>
        </tbody>
    </table>
</div>
