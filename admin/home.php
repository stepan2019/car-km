<?php
    session_start();
    if(!@$_SESSION['admin']) {
        header("location:index.php");
    }
    include "../setting/config.php";
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
    <title>Car Registration - Dashboard</title>

    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/custom.css" rel="stylesheet">

    <link href="../css/loaders.css" rel="stylesheet">
    <link href="../css/swiper.min.css" rel="stylesheet">
    <link href="../css/animate.min.css" rel="stylesheet">
    <link href="../css/nivo-lightbox.css" rel="stylesheet">
    <link href="../css/nivo_themes/default/default.css" rel="stylesheet">

    <link href="../css/dataTables.1.9.4.css" rel="stylesheet">

    <link type="text/css" rel="stylesheet" href="../css/editor/style.css" />

    <link href='../css/select2.min.css' rel='stylesheet' type='text/css'>

    <link rel="stylesheet" href="/css/all.css" crossorigin="anonymous">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/style-sage.css">

    <script type="text/javascript" src="../libs/jQuery/jquery.js"></script>
    <script type="text/javascript" src="../libs/nicEdit/nicEdit.min.js"></script>
    <script type="text/javascript" src="../js/common.min.js"></script>
    <script type="text/javascript" src="js/functions.js"></script>
    <script type="text/javascript" src="../libs/jQuery/plugins/powertip/jquery.powertip.min.js"></script>
    <link rel="stylesheet" href="../libs/jQuery/plugins/powertip/css/jquery.powertip.min.css"/>
    <script>
        function gotoInvoice(plate, vin, email){
            $('#plate').val(plate);
            $('#vin').val(vin);
            $('#email').val(email);
            document.forms[0].submit();
        }
    </script>
</head>
<body style="background-color: #ece4b7;">
    <nav class="navbar navbar-toggleable-md mb-4 top-bar navbar-static-top sps sps--abv">
        <div class="container-fluid" style="display: flex;">
            <button class="navbar-toggler navbar-toggler-right" type="button" data-toggle="collapse" data-target="#navbarCollapse1" aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <a class="navbar-brand" href="/"><img class="logo-img" src="/img/logo.png" alt="logo"></a>
            <div class="collapse navbar-collapse" id="navbarCollapse1" style="padding-top: 1em;">
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item active"> <a class="nav-link" href="/admin">Home <span class="sr-only">(current)</span></a> </li>
                    <li class="nav-item dropdown"> <a class="nav-link dropdown-toggle" data-toggle="dropdown" href="#">User List<span class="caret"></span></a>
                        <ul class="dropdown-menu">
                            <li><a class="nav-link" href="home.php?query=userlist">User</a></li>
                            <li><a class="nav-link" href="home.php?query=dealerlist">Dealer</a></li>
                        </ul>
                    </li>
                    <li class="nav-item"> <a class="nav-link" href="home.php?query=vehiclelist">Vehicle List</a></li>
					  <li class="nav-item dropdown"> <a class="nav-link dropdown-toggle" data-toggle="dropdown" href="#">Payment<span class="caret"></span></a>
					<ul class="dropdown-menu">
                    <li><a class="nav-link"> <a class="nav-link" href="home.php?query=price">Price</a> </li>
					<li><a class="nav-link" href="home.php?query=paypal">Paypal</a></li>
					<li><a class="nav-link" href="home.php?query=coupons">Add Coupon</a></li>
					<li><a class="nav-link" href="home.php?query=coupons_list">Coupon Lists</a></li>
					<li><a class="nav-link" href="home.php?query=coupons_history">Coupon History</a></li>
					<li><a class="nav-link" href="home.php?query=invoices">Invoice History</a></li>
					</ul>

                    <li class="nav-item dropdown"> <a class="nav-link dropdown-toggle" data-toggle="dropdown" href="#">Language<span class="caret"></span></a>
                        <ul class="dropdown-menu">
                            <li><a class="nav-link" href="home.php?query=add_language">Add Language</a></li>
                            <li><a class="nav-link" href="home.php?query=languages">View Languages</a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown"> <a class="nav-link dropdown-toggle" data-toggle="dropdown" href="#">Pages<span class="caret"></span></a>
                        <ul class="dropdown-menu">
                            <li><a class="nav-link" href="home.php?query=homescreenlist">Home Screen</a></li>
                            <li><a class="nav-link" href="home.php?query=about">Price Content</a></li>
							<li><a class="nav-link" href="home.php?query=content">Content</a></li>
							<li><a class="nav-link" href="home.php?query=footer">Footer</a></li>
							<li><a class="nav-link" href="home.php?query=mails_settings">E-mail option</a></li>
							<li><a class="nav-link" href="home.php?query=send-mail">Send news mail</a></li>
                            <li><a class="nav-link" href="home.php?query=information">Information</a></li>
                            <li><a class="nav-link" href="home.php?query=db_tools">Backup</a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown"> <a class="nav-link dropdown-toggle" data-toggle="dropdown" href="#">Banners<span class="caret"></span></a>
                        <ul class="dropdown-menu">
                            <li><a class="nav-link" href="home.php?query=banner">Add Banner</a></li>
                            <li><a class="nav-link" href="home.php?query=bannerlist">View Banners</a></li>
                        </ul>
                    </li>
                </ul>
                <ul class="navbar-nav ml-auto text-center">
                    <li class="ml-4 mr-4"><a class="tooltips text-white" href="home.php?query=profile"><span>Profile</span><br><i class="fas fa-user-cog"></i></a></li>
                    <li><a class="tooltips text-white" href="logout.php"><span>Log out</span><br><i class="fas fa-sign-out-alt"></i></a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container" style="margin-top: 150px;">
        <?php
            ini_set('display_errors', '1');
            $homepage = "dashboard";
            if(isset($_GET['query'])) {
                $homepage = $_GET['query'];
            }
            include $homepage.".php";
        ?>
    </div>

    <script src="../js/jquery.min.js" ></script>
    <script src="../js/bootstrap.min.js"></script>
    <script src="../js/dataTables.1.9.4.js"></script>
    <script src='../js/select2/select2.min.js' type='text/javascript'></script>

    <script type="text/javascript">
        $(document).ready(function() {
            $("input[name=user_block]").on("change", function(e) {
                var userId = $(e.target).data('id');
                var isChecked = $(e.target).val();

                var blockData = {
                    'blockItem' : userId,
                    'checkItem' : isChecked
                };

                $.ajax({
                    type     : 'POST',
                    url      : 'userblock.php',
                    data     : blockData,
                    dataType : 'json',
                    encode   : true
                }).done(function(result) {
                });
            });
            $("input[name=user_active]").on("change", function(e) {
                var userId = $(e.target).data('id');
                var isChecked = $(e.target).val();
                if(this.checked){
                    isChecked = 1;
                }else{
                    isChecked = 0;
                }
                var activeData = {
                    'activeItem' : userId,
                    'checkItem' : isChecked
                };

                $.ajax({
                    type     : 'POST',
                    url      : 'useractive.php',
                    data     : activeData,
                    dataType : 'json',
                    encode   : true
                }).done(function(result) {
                });
            });

            $("input[name=dealer_block]").on("change", function(e) {
                var userId = $(e.target).data('id');
                var isChecked = $(e.target).val();

                var blockData = {
                    'blockItem' : userId,
                    'checkItem' : isChecked
                };

                $.ajax({
                    type     : 'POST',
                    url      : 'dealerblock.php',
                    data     : blockData,
                    dataType : 'json',
                    encode   : true
                }).done(function(result) {
                });
            });
            $("input[name=dealer_active]").on("change", function(e) {
                var userId = $(e.target).data('id');
                var isChecked = $(e.target).val();
                if(this.checked){
                    isChecked = 1;
                }else{
                    isChecked = 0;
                }
                var activeData = {
                    'activeItem' : userId,
                    'checkItem' : isChecked
                };

                $.ajax({
                    type     : 'POST',
                    url      : 'dealeractive.php',
                    data     : activeData,
                    dataType : 'json',
                    encode   : true
                }).done(function(result) {
                });
            });

            $("input[name=price]").on("change", function(e) {
                var isShow = $(e.target).val();
                if(isShow == "yes") {
                    $(".price-visible").css("display", "block");
                } else {
                    $(".price-visible").css("display", "none");
                }
            });

            var initialShow = $("input[name=price]:checked").val();
            if(initialShow == "yes") {
                $(".price-visible").css("display", "block");
            } else {
                $(".price-visible").css("display", "none");
            }

            $("#select2_make").select2();

            $("select#selected_make").change(function() {
                var selectedMake = this.value;
                var makeData = {
                    'selectedMake': selectedMake
                };

                $.ajax({
                    type     : 'POST',
                    url      : '../vehicle/getModelListFromMake.php',
                    data     : makeData,
                    dataType : 'json',
                    encode   : true
                }).done(function(list) {
                    $('option', $('#modellist')).remove();
                    if(list.length > 0) {
                        for(var i=0; i<list.length; i++) {
                            var makeElement =  list[i]['name'];
                            $('#modellist').append("<option value='" + makeElement + "'>" + makeElement + "</option>");
                        }
                    } else {
                            $('#modellist').append("<option disabled selected>No data</option>");
                    }
                });
            });
        });
    </script>

    <script>
        $(function() {
            $("#wang-dataTable").dataTable();
        });
    </script>

    <script type="text/javascript" src="../js/editor/cazary.min.js"></script>
    <script type="text/javascript">
        (function($, window) {
            $(function($) {
                $("textarea#id_cazary").cazary();
            });
        })(jQuery, window);
    </script>

</body>

</html>
