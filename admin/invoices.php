<?php

$result = $config->getInvoiceHistory();
$invoiceData = $result->fetch_All();
?>

<div>
    <table class="table table-bordered" id="wang-dataTable">
        <thead>
        <tr>
            <th class="text-center">#</th>
            <th>Amount</th>
            <th>User</th>
            <th>Plate number</th>
            <th>Date</th>
            <!--<th>Amount</th>-->
            <th class="text-center">Action</th>
        </tr>
        </thead>
        <tbody>
        <?php
        $s_sn = 1;
        foreach ($invoiceData as $row) {
            $user = $config->getUserById($row[1]);
            $user_info = $user->fetch_assoc();
            ?>
            <tr>
                <td class="text-center"><?php echo $row[0]; ?></td>
                <td><?php echo $row[5]; ?></td>
                <td><?php echo $user_info['name']; ?></td>
                <td><?php echo $row[8]; ?></td>
                <td><?php echo date('Y-m-d', strtotime($row[3])); ?></td>
                <td class="text-center">
                    <a href="javascript:gotoInvoice(<?php echo "'".$row[8]."'".','."'".$row[9]."'".','."'".$user_info['email']."'";?>)">
                        <i class="far fa-edit"></i>
                    </a>
                    <a href="home.php?query=deleteinvoicehistory&id=<?php echo $row[0]; ?>">
                        <i class="far fa-trash-alt pl-3" style=""></i>
                    </a>
                </td>
            </tr>
            <?php $s_sn++;
        } ?>
        </tbody>
    </table>
    <form action="/vehicle/invoice.php" method="post" id="invoice_form" target="_blank" name="invoice_form">
        <input type="hidden" name="vin" id="vin" value=""/>
        <input type="hidden" name="plate" id="plate" value=""/>
        <input type="hidden" name="email" id="email" value=""/>
    </form>
</div>

