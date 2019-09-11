<?php

$root = realpath($_SERVER["DOCUMENT_ROOT"]);

//include $root . "/setting/config.php";

//echo @$_SESSION['user'];exit;
global $text_direction;
global $lng;
//$result = $config->getAboutContent();
//$about = $result->fetch_assoc();
//
//$result = $config->getContent();
//$content = $result->fetch_assoc();
//
//$result = $config->getInformationContent();
//$information = $result->fetch_assoc();
global $crt_lang_code;

$result = $config->getFooterByCode($crt_lang_code);
$footer_new = $result->fetch_assoc();

$banners = $config->loadAdsBanner("header");
$banners_footer = $config->loadAdsBanner("footer");

$enableLanguages1 = $config->getEnableLanguages();
$enableLanguages2 = $config->getEnableLanguages();

?>
<?php
if ($text_direction == 'rtl') {
    ?>
    <link href="/template/css/style-rtl.css" rel="stylesheet">
    <?php
} else {
    ?>
    <link href="/template/css/style.css" rel="stylesheet">
<?php } ?>
<nav class="navbar navbar-toggleable-md mb-4 top-bar navbar-static-top sps sps--abv">

    <div class="container">

        <button class="navbar-toggler navbar-toggler-right" type="button" data-toggle="collapse"
                data-target="#navbarCollapse1" aria-controls="navbarCollapse" aria-expanded="false"
                aria-label="Toggle navigation">
            <!--<span class="navbar-toggler-icon"></span>-->
            <i class="fa fa-bars"></i>
        </button>
        <a class="navbar-brand" href="/"><img class="logo-img" src="/img/logo.png" alt="logo"></a>
        <div class="collapse navbar-collapse" id="navbarCollapse1">
            <ul class="navbar-nav ml-auto" style="position:relative;">
                <li class="nav-item active"><a class="nav-link" href="/"><?php echo $lng['navbar']['home']; ?> <span
                                class="sr-only">(current)</span></a>
                </li>
                <li class="nav-item"><a class="nav-link"
                                        href="/template/price.php"><?php echo $lng['navbar']['price']; ?></a></li>
                <li class="nav-item"><a class="nav-link"
                                        href="/template/info.php"><?php echo $lng['navbar']['information']; ?></a></li>
                <?php
                if (@$_SESSION['user']) {
                    ?>
                    <li class="nav-item"><a class="nav-link"
                                            href="/vehicle/add.php"><?php echo $lng['navbar']['add_vehicle_km']; ?></a>
                    </li>
                    <li class="nav-item"><a class="nav-link"
                                            href="/user/logout.php"><?php echo $lng['navbar']['logout']; ?></a></li>
                    <li class="nav-item"><a class="nav-link"
                                            href="/user/profile.php"><?php echo $lng['navbar']['my_account']; ?></a>
                    </li>
                <?php } else { ?>
                    <li class="nav-item"><a class="nav-link"
                                            href="/user/login.php?query=goVehicle"><?php echo $lng['navbar']['add_vehicle_km']; ?></a>
                    </li>
                    <li class="nav-item"><a class="nav-link"
                                            href="/user/login.php"><?php echo $lng['navbar']['login']; ?></a></li>
                    <li class="nav-item"><a class="nav-link"
                                            href="/user/register.php"><?php echo $lng['navbar']['register']; ?></a></li>
                <?php } ?>
                <li class="nav-item"><a class="nav-link"
                                        href="/template/contact.php"><?php echo $lng['navbar']['contact']; ?></a></li>
            </ul>


        </div>
        <div>
            <ul class="languagepicker">
                <?php
                $langSelector = '';
                while ($row = $enableLanguages1->fetch_assoc()) {
                    ?>
                    <?php
                    if ($crt_lang == $row['id']) {
                        ?>
                        <a href="javascript:;"
                           onclick="javascript::return false;">
                            <li><img src="/images/languages/<?php echo $row['image']; ?>"/><?php echo $row['name']; ?>
                            </li>
                        </a>
                        <?php
                    } ?>
                    <?php
                }
                while ($row = $enableLanguages2->fetch_assoc()) {
                    ?>
                    <?php
                    if ($crt_lang != $row['id']) {
                        ?>
                        <a href="javascript:;"
                           onclick="document.cookie='default_lang=<?php echo $row['id']; ?>; path=/; expires = '+exdate.toUTCString()+'; '; window.location.reload( false );">
                            <li><img src="/images/languages/<?php echo $row['image']; ?>"/><?php echo $row['name']; ?>
                            </li>
                        </a>
                        <?php
                    } ?>
                    <?php
                }

                ?>
            </ul>
        </div>

    </div>
</nav>
