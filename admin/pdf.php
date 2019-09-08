<?php
	session_start();
    // if(!@$_SESSION['user']) {
    //     header("location:/index.php");
    // }
    
	include "../setting/config.php";
var_dump($_GET);exit;
	if(isset($_GET['query'])) {
		$plate = $_GET['query'];

		$resultByPlate = $config->get_vehicle_by_plate($plate);
        $countByPlate = $resultByPlate->num_rows;

        $resultByVIN = $config->get_vehicle_by_vin($plate);
        $countByVIN = $resultByVIN->num_rows;

        if($countByPlate > 0)
            $vehicle_info = $resultByPlate->fetch_assoc();
        else
            $vehicle_info = $resultByVIN->fetch_assoc();
        $car_id = $vehicle_info['id'];
        $user_id = $vehicle_info['user_id'];
        $user_type = $vehicle_info['type'];

        if($user_type == "user")
            $result = $config->getUserById($user_id);
        else
            $result = $config->getDealerById($user_id);

        $user_info = $result->fetch_assoc();
        
        $result = $config->reportCount();
	}
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Car pass - Report Generate</title>
	<link href="../css/bootstrap.min.css" rel="stylesheet">
	
	<style type="text/css">
	    .table-bordered {
            border: 2px solid #fe7500;
        }
        .table-bordered tbody td, .table-bordered thead th {
            border: 1px solid #fe7500;
        }
    </style>
</head>

<body class="container">
	<div class="row">
		<div class="col-md-12 text-center btn-field">
			<button onclick="if (!window.__cfRLUnblockHandlers) return false; getPDF()" id="downloadbtn" data-cf-modified-3041e76d3da1bfb24a107310-=""><b>Download as PDF</b></button>
			<button onclick="printThis()" ><b> Print</b></button>
			<button class="button" onclick="closeWindow()" ><b> Close this window</b></button>

		</div>
	</div>
	
	<div class="canvas_div_pdf mt-2" style="display: block;">
	
		<div class="top_map_section clearfix">
			<div class="row col-md-12 text-left mt-4">
				<div class="col-md-6">
					<img src="/img/logo.png" style="width: 150px;">
				</div>
				<div class="col-md-6" style="margin-top:20px;">
                    <h2 style="color: #fe7500;font-weight: bold;">Tellerrapport</h2>
                    <p><b><?php echo date('d-m-Y'); ?></b></p>
                </div>
            <?php
                if($user_type == "user") {
            ?>
                <div style="margin-top:20px; margin-bottom: 10px; padding: 10px; margin-top: 20px; border: 3px solid #fe7500; border-radius: 5px;">
                    <p style="font-size: 24px; margin-bottom: 0px;"><?php echo $user_info['name']; ?></p>
                    <p style="font-size: 24px; margin-bottom: 0px;"><?php echo $user_info['address']; ?></p>
                </div>
            <?php } else { ?>
                <div style="margin-top:20px; margin-bottom: 10px; padding: 10px; margin-top: 20px; border: 3px solid #fe7500; border-radius: 5px;">
                    <p style="font-size: 24px; margin-bottom: 0px;"><?php echo $user_info['company']; ?></p>
                    <p style="font-size: 24px; margin-bottom: 0px;"><?php echo $user_info['address']; ?></p>
                </div>
            <?php } ?>
                
                <?php
                    $result = $config->get_last_vehicle_km_by_car_id($car_id);
                    $km_last_info = $result->fetch_assoc();

                    $result = $config->getCrashedByCarId($car_id);
                    $crash_info = $result->fetch_assoc();
                ?>
                
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th class="text-center">License Plate</th>
                            <th class="text-center">VIN</th>
                            <th class="text-center">Make / Model</th>
                            <th class="text-center">Year Build</th>
                            <th class="text-center">Current KM reading</th>
                            <th class="text-center">Car Crashed ?</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="text-center"><?php echo $vehicle_info['plate']; ?></td>
                            <td class="text-center"><?php echo $vehicle_info['vin']; ?></td>
                            <td class="text-center"><?php echo $vehicle_info['make']; ?> / <?php echo $vehicle_info['model']; ?></td>
                            <td class="text-center"><?php echo $vehicle_info['year']; ?></td>
                            <td class="text-center"><?php echo $km_last_info['km']; ?> km</td>
                            <td class="text-center">
                                <?php
                                    if($crash_info['crash'] == "yes") {
                                        if($crash_info['front'] != "")
                                            echo "*front<br>";
                                        if($crash_info['back'] != "")
                                            echo "*back<br>";
                                        if($crash_info['lefty'] != "")
                                            echo "*left<br>";
                                        if($crash_info['righty'] != "")
                                            echo "*right<br>";
                                        if($crash_info['total'] != "")
                                            echo "*total less";
                                    } else {
                                        echo $crash_info['crash'];   
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
                                <span class="mr-4" style="font-size: 40px; color: #000;font-weight: bold;">Logical</span>
                                <img src="/img/Logical.png" alt="record">
                        <?php
                            } else if($km_last_info['logic'] == "false") {
                        ?>
                                <span class="mr-4" style="font-size: 40px; color: #000;font-weight: bold;">Not Logical</span>
                                <img src="/img/Not-Logical.png" alt="record">
                        <?php
                            } else {
                        ?>
                                <span class="mr-4" style="font-size: 40px; color: #000;font-weight: bold;">No Judgement</span>
                                <img src="/img/No-Jurgment.png" alt="record">
                        <?php
                            }
                        ?>
                    </p>
                </div>

                <p class="col-md-12" style="font-size: 24px;font-weight: bold;">Km Records</p>
            
            <?php
                $result = $config->get_vehicle_km_by_car_id($car_id);
                $totalRows = $result->num_rows;
                $minRows = ceil($totalRows / 3);
                $minRows = $minRows > 10 ? $minRows : 10;
            ?>
                <table class="table table-bordered" style="width: 33%;">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 16%;">km</th>
                            <th class="text-center" style="width: 16%;">add_date</th>
                        </tr>
                    </thead>
                    <tbody>
                <?php
                	$cnt = 0;
                    while($km_info = $result->fetch_assoc()) {
                    	$cnt++;
                ?>
                        <tr>
                            <td class="text-center" style="border-left:none;border-right:none;"><?php echo $km_info['km']; ?></td>
                            <td class="text-center" style="border-left:none;border-right:none;"><?php echo $km_info['add_date']; ?></td>
                        </tr>
                <?php
                		if($cnt == $minRows) break;
                    }
                ?>
                    </tbody>
                </table>
                <table class="table table-bordered" style="width: 33%;">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 16%;">km</th>
                            <th class="text-center" style="width: 16%;">add_date</th>
                        </tr>
                    </thead>
                    <tbody>
                <?php
                	$cnt = 0;
                    while($km_info = $result->fetch_assoc()) {
                    	$cnt++;
                ?>
                        <tr>
                            <td class="text-center" style="border-left:none;border-right:none;"><?php echo $km_info['km']; ?></td>
                            <td class="text-center" style="border-left:none;border-right:none;"><?php echo $km_info['add_date']; ?></td>
                        </tr>
                <?php
                		if($cnt == $minRows) break;
                    }
                ?>
                    </tbody>
                </table>
                <table class="table table-bordered" style="width: 33%;">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 16%;">km</th>
                            <th class="text-center" style="width: 16%;">add_date</th>
                        </tr>
                    </thead>
                    <tbody>
                <?php
                    while($km_info = $result->fetch_assoc()) {
                ?>
                        <tr>
                            <td class="text-center" style="border-left:none;border-right:none;"><?php echo $km_info['km']; ?></td>
                            <td class="text-center" style="border-left:none;border-right:none;"><?php echo $km_info['add_date']; ?></td>
                        </tr>
                <?php
                    }
                ?>
		
                    </tbody>
                </table>
                <div class="col-md-12">
                    <p style="font-size: 24px;"><u>Explanation</u></p>
                    <p>Registration of the car KM in Greece is From 2019, we cannot JUDGEMENT about the Km before this year.<br>
                        You see in the report Logical, Not Logical, No JUDGEMENT that means:<br>
                        1 - Logical that means the KM is Logical as we have Registered by Carpass.<br>
                        2 - Not Logical, That means the km is change after registration by Carpass.<br>
                        3 - No Judgment, that mean we have no any data of this vehicle, that is first Registration by Carpass.
                    </p>
                </div>
            </div>
		</div>
	</div>

	<script src="https://code.jquery.com/jquery-1.11.3.js" type="text/javascript"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/1.3.3/jspdf.min.js" type="text/javascript"></script>
	<script src="https://html2canvas.hertzen.com/dist/html2canvas.js" type="text/javascript"></script>

	<script type="text/javascript">
		function getPDF(){
			$("#downloadbtn").hide();
			$("#genmsg").show();
			var HTML_Width = $(".canvas_div_pdf").width();
			var HTML_Height = $(".canvas_div_pdf").height();
			var top_left_margin = 15;
			var PDF_Width = HTML_Width+(top_left_margin*2);
			var PDF_Height = (PDF_Width*1.2)+(top_left_margin*2);
			var canvas_image_width = HTML_Width;
			var canvas_image_height = HTML_Height;
			
			var totalPDFPages = Math.ceil(HTML_Height/PDF_Height)-1;

			html2canvas($(".canvas_div_pdf")[0],{allowTaint:true}).then(function(canvas) {
				canvas.getContext('2d');
				
				console.log(canvas.height+"  "+canvas.width);
				
				var imgData = canvas.toDataURL("image/jpeg", 1.0);
				var pdf = new jsPDF('p', 'pt',  [PDF_Width, PDF_Height]);
			    pdf.addImage(imgData, 'JPG', top_left_margin, top_left_margin,canvas_image_width,canvas_image_height);
				
				for (var i = 1; i <= totalPDFPages; i++) { 
					pdf.addPage(PDF_Width, PDF_Height);
					pdf.addImage(imgData, 'JPG', top_left_margin, -(PDF_Height*i)+(top_left_margin*4),canvas_image_width,canvas_image_height);
				}
				
			    pdf.save("HTML-Document.pdf");
				
				setTimeout(function(){ 			
					$("#downloadbtn").show();
					$("#genmsg").hide();
				}, 0);

	        });
		};
		
		function closeWindow(){
		    if (confirm("Close Window?")) {
                window.close();
              }
		};
		
		function printThis(){
		    console.log("print this page....");
		    $(".btn-field").hide();
		    window.print();
		};
	</script>
	
	<script src="https://ajax.cloudflare.com/cdn-cgi/scripts/a2bd7673/cloudflare-static/rocket-loader.min.js" data-cf-settings="3041e76d3da1bfb24a107310-|49" defer=""></script>
</body>
</html>