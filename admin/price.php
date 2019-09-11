<?php
$result = $config->getPrice();
$currentPrice = $result->fetch_assoc();

if (isset($_POST['add_price'])) {
    $price = $_POST['price'];
    $download_price = $_POST['download_price'];
    $download_tax = $_POST['download_tax'];

    if ($price == "no")
        $download_price = 0;

    $is_success = $config->add_price($download_price, $download_tax);
    if ($is_success) {
        $result = $config->getPrice();
        $currentPrice = $result->fetch_assoc();
    } else {
        echo "<script>alert('Sorry ...');</script>";
    }
}
?>
<div>
    <form method="post">
        <h2>Please select your plan.</h2>
        <input type="radio" name="price" <?php if ($currentPrice['price'] > 0) echo "checked" ?> value="yes"
               id="price_fee" class="mt-4" style="width: 16px; height: 16px;">
            <span class="ml-3" style="font-size: 24px;">Price</span>
        <br>
        <div class="price-visible">
            <div class="form-group row mt-3 mb-3">
                <label for="price" class="form-control-label">Please input your price.</label>
                <input type="number" id="price" name="download_price" step="any" class="ml-2"
                       value="<?php echo $currentPrice['price'] ?>"> $<br>
                <label for="tax" class="form-control-label ml-5">Please input your tax.</label>
                <input type="number" id="tax" name="download_tax" step="any" class="ml-2" value="<?php echo $currentPrice['tax'] ?>">
                $
            </div>
        </div>

        <input type="radio" name="price" <?php if ($currentPrice['price'] == 0) echo "checked" ?> value="no"
               id="price_free" style="width: 16px; height: 16px;"><span class="ml-3" style="font-size: 24px;">Free</span><br>

        <button type="submit" class="btn btn-primary mt-5 btn-custom" name="add_price">OK</button>
    </form>
</div>
