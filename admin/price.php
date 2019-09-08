<?php
    $result = $config->getPrice();
    $currentPrice = $result->fetch_assoc();

    if(isset($_POST['add_price'])) {
        $price = $_POST['price'];
        $download_price = $_POST['download_price'];

        if($price == "no")
            $download_price = 0;

        $result = $config->add_price($download_price);
        if($result) {
            // header("location:home.php");
        } else {
            echo "<script>alert('Sorry ...');</script>";
        }
    }
?>
<div>
    <form method="post">
        <h2>Please select your plan.</h2>
        <input type="radio" name="price" <?php if($currentPrice['price'] > 0) echo "checked" ?> value="yes" id="price_fee" style="width: 16px; height: 16px;"><span class="ml-3" style="font-size: 24px;">Price</span><br>

        <div class="price-visible">
            <p>Please input your price.</p>
            <input type="number" name="download_price" step="any" value="<?php echo $currentPrice['price'] ?>"> $<br>
        </div>

        <input type="radio" name="price" <?php if($currentPrice['price'] == 0) echo "checked" ?> value="no" id="price_free" style="width: 16px; height: 16px;"><span class="ml-3" style="font-size: 24px;">Free</span><br>

        <button type="submit" class="btn btn-primary mt-5 btn-custom" name="add_price">OK</button>
    </form>
</div>