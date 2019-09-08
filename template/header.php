<?php
    include "setting/config.php";

    session_start();
    //echo @$_SESSION['user'];exit;

    $result = $config->getAboutContent();
    $about = $result->fetch_assoc();
    
    $result = $config->getContent();
    $content = $result->fetch_assoc();
    
    $result = $config->getInformationContent();
    $information = $result->fetch_assoc();
    
    $result = $config->getFooter();
    $footer_new = $result->fetch_assoc();
    
    $banners = $config->loadAdsBanner("header");
    $banners_footer = $config->loadAdsBanner("footer");

    $header = $config->loadBanner("header");
    $footer = $config->loadBanner("footer");
    
?>
<nav class="navbar navbar-toggleable-md mb-4 top-bar navbar-static-top sps sps--abv">

    <div class="container">
	
        <button class="navbar-toggler navbar-toggler-right" type="button" data-toggle="collapse" data-target="#navbarCollapse1" aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
            <!--<span class="navbar-toggler-icon"></span>-->
            <i class="fa fa-bars"></i>
        </button>
        <a class="navbar-brand" href="/"><img class="logo-img" src="/img/logo.png" alt="logo"></a>
        <div class="collapse navbar-collapse" id="navbarCollapse1">
            <ul class="navbar-nav ml-auto">
                <li class="nav-item active"> <a class="nav-link" href="/">Home <span class="sr-only">(current)</span></a> </li>
                <li class="nav-item"> <a class="nav-link" href="/template/price.php">Price</a> </li>
                <li class="nav-item"> <a class="nav-link" href="/template/info.php">Information</a> </li>
            <?php
                if(@$_SESSION['user']) {
            ?>
                <li class="nav-item"> <a class="nav-link" href="/vehicle/add.php">Add Vehicle Km</a> </li>
                <li class="nav-item"> <a class="nav-link" href="/user/logout.php">Logout</a> </li>
                <li class="nav-item"> <a class="nav-link" href="/user/profile.php">Profile</a> </li>
            <?php } else { ?>
                <li class="nav-item"> <a class="nav-link" href="/user/login.php?query=goVehicle">Add Vehicle Km</a> </li>
                <li class="nav-item"> <a class="nav-link" href="/user/login.php">Login</a> </li>
                <li class="nav-item"> <a class="nav-link" href="/user/register.php">Registration</a> </li>
            <?php } ?>
                <li class="nav-item"> <a class="nav-link" href="/template/contact.php">Contact</a> </li>
            </ul>
	
        </div>
	
    </div>
</nav>
