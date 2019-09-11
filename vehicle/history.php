<?php 
    include "../setting/config.php";

    session_start();

    if(!@$_SESSION['user']) {
         header("location:/user/login.php");
    }
    
    $result = $config->getInformationContent();
    $information = $result->fetch_assoc();

    $email = @$_SESSION['user'];
    $type = @$_SESSION['type'];
    $result = $config->getAddress($email, $type);
    $user_info = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="icon" href=" ">
    <title>Car Registration - Vehicle History</title>

    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/custom.css" rel="stylesheet">

    <link href="../css/loaders.css" rel="stylesheet">
    <link href="../css/swiper.min.css" rel="stylesheet">
    <link href="../css/animate.min.css" rel="stylesheet">
    <link href="../css/nivo-lightbox.css" rel="stylesheet">
    <link href="../css/nivo_themes/default/default.css" rel="stylesheet">

    <link rel="stylesheet" href="/css/all.css" crossorigin="anonymous">
</head>
<body>

    <?php
        include "../template/header.php";
    ?>

    <div class="container-fluid main-screen">
        <div class="container main-content">
            <div class="row col-md-12">
                <form method="post">
                    <div class="col-md-4 text-left mt-4" style="display: flex;">
                        <label class="control-label mr-5">Plate Number</label>
                        <div class="agileits-main mr-5">
                            <i class="fas fa-signature"></i>
                            <input type="text" required="" name="plate" id="car_plate">
                        </div>
                        <button type="submit" class="btn btn-primary submit-fs ml-5 btn-custom" name="view_history">View</button>
                    </div>
                </form>
            </div>
        <?php
            if(isset($_POST['view_history'])) {
                $plate = $_POST['plate'];

                $result = $config->get_vehicle_by_plate($plate);
                $count = $result->num_rows;
                if($count > 0) {
                    $vehicle_info = $result->fetch_assoc();
                    $car_id = $vehicle_info['id'];
        ?>
            <div id="html-2-pdfwrapper" class="row col-md-12 text-left mt-4">
                <div class="col-md-3">
                    <p><u>Fullname</u></p>
                    <p class="w-f-18"><?php echo $user_info['name']; ?></p>
                </div>
                <div class="col-md-3">
                    <p><u>Address</u></p>
                    <p class="w-f-18"><?php echo $user_info['address']; ?></p>
                </div>
                <div class="col-md-6">
                    <h2 style="color: #f68;">Tellerrapport</h2>
                    <p><?php echo date('d-m-Y'); ?></p>
                </div>

                <?php
                    $result = $config->get_last_vehicle_km_by_car_id($car_id);
                    $km_last_info = $result->fetch_assoc();

                    $result = $config->getCrashedByCarId($car_id);
                    $crash_info = $result->fetch_assoc();
                ?>
                
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>License Plate</th>
                            <th>Make + Model</th>
                            <th>Year Build</th>
                            <th>Current km reading</th>
                            <th>Car Crashed ?</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?php echo $vehicle_info['plate']; ?></td>
                            <td><?php echo $vehicle_info['make']; ?> + <?php echo $vehicle_info['model']; ?></td>
                            <td><?php echo $vehicle_info['year']; ?></td>
                            <td><?php echo $km_last_info['km']; ?> km</td>
                            <td>
                                <?php echo $crash_info['crash']; ?>
                                <?php
                                    if($crash_info['crash'] == "yes") {
                                        echo '(';
                                        if($crash_info['front'] != "")
                                            echo "front ";
                                        if($crash_info['back'] != "")
                                            echo "back ";
                                        if($crash_info['lefty'] != "")
                                            echo "left ";
                                        if($crash_info['righty'] != "")
                                            echo "right ";
                                        if($crash_info['total'] != "")
                                            echo "total ";
                                        echo ')';
                                    }
                                ?>    
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div class="col-md-12">
                    <p>
                        <?php
                            if($km_last_info['logic'] == "true") {
                        ?>
                                <span class="mr-4">Logical</span>
                                <img src="/img/Logical.png" alt="record">
                        <?php
                            } else if($km_last_info['logic'] == "false") {
                        ?>
                                <span class="mr-4">Not Logical</span>
                                <img src="/img/Not-Logical.png" alt="record">
                        <?php
                            } else {
                        ?>
                                <span class="mr-4">No Judgement</span>
                                <img src="/img/No-Jurgment.png" alt="record">
                        <?php
                            }
                        ?>
                    </p>
                </div>

                <p class="col-md-12">Km Record</p>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>km</th>
                            <th>add_date</th>
                        </tr>
                    </thead>
                    <tbody>
                <?php
                    $result = $config->get_vehicle_km_by_car_id($car_id);
                    while($km_info = $result->fetch_assoc()) {
                ?>
                        <tr>
                            <td><?php echo $km_info['km']; ?></td>
                            <td><?php echo $km_info['add_date']; ?></td>
                        </tr>
                <?php
                    }
                ?>
                    </tbody>
                </table>
                <div class="col-md-12">
                    <p><u>Explanation</u></p>
                    <p>Registration of the car KM in Greece is From 2019, we cannot JUDGEMENT about the Km before this year.<br>
                        You see in the report Logical, Not Logical, No JUDGEMENT that means:<br>
                        1 - Logical that means the KM is Logical as we have Registered by Carpass.<br>
                        2 - Not Logical, That means the km is change after registration by Carpass.<br>
                        3 - No Judgment, that mean we have no any data of this vehicle, that is first Registration by Carpass.
                    </p>
                </div>
            </div>
            <?php
                $result = $config->getPrice();
                $currentPrice = $result->fetch_assoc();
                $val = $currentPrice['price'];

                if($val == 0) {
            ?>
                <button class="btn btn-primary mb-5 btn-custom" onclick="generate()">Generate PDF</button>
            <?php
                } else {
            ?>
                <form class="paypal" action="payments.php" method="post" id="paypal_form">
                    <input type="hidden" name="payer_email" value="<?php echo $user_info['email'] ?>" />
                    <input type="hidden" name="itemAmount" value="<?php echo $val; ?>" />
                    
                    <input type="hidden" name="cmd" value="_xclick" />
                    <input type="hidden" name="lc" value="UK" />
                    <input type="hidden" name="bn" value="PP-BuyNowBF:btn_buynow_LG.gif:NonHostedGuest" />
                    <input type="hidden" name="first_name" value="Customer's First Name" />
                    <input type="hidden" name="last_name" value="Customer's Last Name" />
                    <input type="hidden" name="item_number" value="123456" / >
                    <input type="submit" name="submit" value="Pay Now" class="btn btn-primary mb-5 btn-custom"/>
                </form>
            <?php } ?>
        <?php
                } else {
                    echo 'The data does not exist.';
                }
            }
        ?>
        </div>
    </div>
    
    <?php
        include "../template/information.php";
    ?>

    <?php
        include "../template/footer.php";
    ?>

    <script src="../js/jquery.min.js" ></script> 
    <script src="../js/bootstrap.min.js"></script> 
    <script src="../js/scrollPosStyler.js"></script> 
    <script src='dist/jspdf.min.js'></script>

    <script>
        //Global Variable Declaration
        var base64Img = null;
        margins = {
            top: 70,
            bottom: 40,
            left: 30,
            width: 550
        };

        /* append other function below: */
        generate = function() {
            var pdf = new jsPDF('p', 'pt', 'a4');
            pdf.setFontSize(18);
            pdf.fromHTML(document.getElementById('html-2-pdfwrapper'), 
                margins.left, // x coord
                margins.top,
                {
                    // y coord
                    width: margins.width// max width of content on PDF
                },
                function(dispose) {
                    headerFooterFormatting(pdf)
                },
                margins
            );
        
            var iframe = document.createElement('iframe');
            iframe.setAttribute('style','z-index:9999;position:absolute;right:0; top:0; bottom:0; height:100%; width:650px; padding:20px;');
            document.body.appendChild(iframe);
      
            iframe.src = pdf.output('datauristring');
        };

        function headerFooterFormatting(doc) {
            var totalPages  = doc.internal.getNumberOfPages();

            for(var i = totalPages; i >= 1; i--) { //make this page, the current page we are currently working on.
                doc.setPage(i);
                header(doc);
                // footer(doc, i, totalPages);
            }
        };

        function header(doc) {
            doc.setFontSize(30);
            doc.setTextColor(40);
            doc.setFontStyle('normal');
          
            if (base64Img) {
                doc.addImage(base64Img, 'JPEG', margins.left, 10, 40,40);        
            }
              
            doc.text("Report Header Template", margins.left + 50, 40 );
          
            doc.line(3, 70, margins.width + 43,70); // horizontal line
        };

        imgToBase64('octocat.jpg', function(base64) {
            base64Img = base64; 
        });

        function imgToBase64(url, callback, imgVariable) {
            if (!window.FileReade) {
                callback(null);
                return;
            }
            var xhr = new XMLHttpRequest();
            xhr.responseType = 'blob';
            xhr.onload = function() {
                var reader = new FileReader();
                reader.onloadend = function() {
                    imgVariable = reader.result.replace('text/xml', 'image/jpeg');
                    callback(imgVariable);
                };
                reader.readAsDataURL(xhr.response);
            };
            xhr.open('GET', url);
            xhr.send();
        };
    </script>
    
</body>

</html>