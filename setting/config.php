<?php

include 'db_connect.php';

class Car
{

    public $connectdb;

    function __construct()
    {
        $db = new Db();
        $this->connectdb = $db;
    }

    public function getDefaultLanguage()
    {
        $query = "select * from languages where `default` = 1";
        $result = $this->connectdb->query($query);
        return $result;
    }

    public function getLanguage($id)
    {
        $query = "select * from languages where id='$id'";
        $result = $this->connectdb->query($query);
        return $result;

    }

    public function getInvoiceHistory()
    {
        $query = "select * from invoices order by `id` DESC";
        $result = $this->connectdb->query($query);
        return $result;

    }

    public function getCurrentInvoiceId()
    {
        $query = "select MAX(id) as id from invoices";
        $result = $this->connectdb->query($query);
        return $result->fetch_assoc();

    }

    public function setInoviceHistory($user_id, $user_type, $plate, $vin, $price, $tax)
    {
//        $settingResult = $this->getEmailSetting();
//        $setting = $settingResult->fetch_assoc();
//        if ($setting) {
//            $this->deleteEmailSetting();
//        }
        $currentId = $this->getCurrentInvoiceId();
        $date = date('Y-m-d');
        $query = "insert into invoices(user_id,user_type, date, payment_action, currency, amount, tax, plate_number, vin)value('$user_id','$user_type', '$date', 'paypal', '$price', '1','$tax','$plate','$vin')";
        $this->connectdb->query($query);
        return $currentId;
    }

    public
    function deleteInvoiceHistory($id)
    {
        $query = "delete from invoices where id = '$id'";
        $result = $this->connectdb->query($query);
        return $result;
    }

    public
    function getLanguages()
    {
        $query = "select * from languages order by `order_no`";
        $result = $this->connectdb->query($query);
        return $result;

    }

    public
    function getEnableLanguages()
    {
        $query = "select * from languages where `enabled`=1 order by `order_no`";
        $result = $this->connectdb->query($query);
        return $result;

    }

    public
    function getEnableLanguagesWithoutDefault()
    {
        $query = "select * from languages where `enabled`=1 and `default` <> 1 order by `order_no`";
        $result = $this->connectdb->query($query);
        return $result;

    }
    public function userEmailCheck($email){
        $query = "select email from user where email='$email'";
        $result = $this->connectdb->query($query);
        return $result->num_rows;
    }
// register
    public
    function register_user($name, $address, $email, $phone, $password)
    {
        $block = "False";
        $query = "insert into user(name, address, email, phone, password, block) value('$name', '$address', '$email', '$phone', '$password', '$block')";
        $result = $this->connectdb->query($query);
        return $result;
    }

    public
    function compareActivationCode($email, $activationCode, $type)
    {
        if($type == 'user'){
            $query = "select * from user where `email`='$email' and `activation`='$activationCode'";
        }else{
            $query = "select * from dealer where `email`='$email' and `activation`='$activationCode'";
        }
        $result = $this->connectdb->query($query);
        return $result;
    }

    public
    function setActivation($email, $activationCode, $type)
    {
        if($type == 'user'){
            $query = "update user set `active` = 1 where `email`='$email' and `activation`='$activationCode'";
        }else{
            $query = "update dealer set `active` = 1 where `email`='$email' and `activation`='$activationCode'";
        }
        $result = $this->connectdb->query($query);
        return $result;
    }

    public function dealerEmailCheck($email){
        $query = "select email from dealer where email='$email'";
        $result = $this->connectdb->query($query);
        return $result->num_rows;
    }

    public
    function register_dealer($name, $address, $email, $phone, $company, $website, $password)
    {
        $block = "False";
        $query = "insert into dealer(name, address, email, phone, company, website, password, block) value('$name', '$address', '$email', '$phone', '$company', '$website', '$password', '$block')";
        $result = $this->connectdb->query($query);
        return $result;
    }

// register paypal
    public
    function update_paypal($allow_paypal, $api_username, $api_password, $api_signature, $paypal_sandbox, $note)
    {
        $query = "insert into paypal_setting(allow_paypal, api_username, api_password, api_signature, paypal_sandbox, note) value('$allow_paypal', '$api_username', '$api_password', '$api_signature', '$paypal_sandbox', '$note')";
        $result = $this->connectdb->query($query);
        return $result;
    }

    public
    function getPaypalSetting()
    {
        $query = "select * from paypal_setting order by id DESC LIMIT 1";
        $result = $this->connectdb->query($query);
        return $result;
    }

// E-mail Option

    public
    function getEmailSetting()
    {
        $query = "select * from email_option order by id DESC LIMIT 1";
        $result = $this->connectdb->query($query);
        return $result;
    }

    public
    function deleteEmailSetting()
    {
        $query = "delete from email_option";
        $result = $this->connectdb->query($query);
        return $result;
    }

    public
    function update_email_option($html_email, $smtp_auth, $smtp_server, $encryption, $port, $username, $password, $bcc_email, $admin_email)
    {
        $settingResult = $this->getEmailSetting();
        $setting = $settingResult->fetch_assoc();
        if ($setting) {
            $this->deleteEmailSetting();
        }
        $query = "insert into email_option(html_email, smtp_auth, smtp_server, encryption, port, username, password,bcc_email, admin_email)value('$html_email', '$smtp_auth', '$smtp_server', '$encryption', '$port','$username','$password','$bcc_email','$admin_email')";
        $result = $this->connectdb->query($query);
        return $result;
    }

// password forgot
    public
    function getUserFromEmail($email, $type)
    {
        if ($type == "user")
            $query = "select * from user where email = '$email'";
        else
            $query = "select * from dealer where email = '$email'";
        $result = $this->connectdb->query($query);
        $number = $result->num_rows;
        return $number;
    }

    public
    function setForgotPassword($email, $pass_key, $expDate)
    {
        $query = "insert into password_reset_temp(email, pass_key, expDate) value('$email', '$pass_key', '$expDate')";
        $result = $this->connectdb->query($query);
        return $result;
    }

    public
    function getForgotPassword($email, $pass_key)
    {
        $query = "select * from password_reset_temp where email = '$email' AND pass_key = '$pass_key'";
        $result = $this->connectdb->query($query);
        return $result;
    }

    public
    function updateForgotPassword($email, $pass1, $type)
    {
        if ($type == "user")
            $query = "update user set password = '$pass1' where email = '$email'";
        else
            $query = "update dealer set password = '$pass1' where email = '$email'";
        $result = $this->connectdb->query($query);
        return $result;
    }

// login
    public
    function login_user($email, $password)
    {
        $query = "select * from user where email = '$email' AND password = '$password'";
        $result = $this->connectdb->query($query);
        $number = $result->num_rows;
        return $number;
    }

    public
    function login_dealer($email, $password)
    {
        $query = "select * from dealer where email = '$email' AND password = '$password'";
        $result = $this->connectdb->query($query);
        $number = $result->num_rows;
        return $number;
    }

    public
    function blockCheck($email, $type)
    {
        if ($type == "user")
            $query = "select * from user where email = '$email'";
        else
            $query = "select * from dealer where email = '$email'";
        $result = $this->connectdb->query($query);

        $obj = $result->fetch_assoc();
        $isBlock = $obj['block'];

        if ($isBlock == "True")
            return true;
        else
            return false;
    }
    function activeCheck($email, $type)
    {
        if ($type == "user")
            $query = "select * from user where email = '$email'";
        else
            $query = "select * from dealer where email = '$email'";
        $result = $this->connectdb->query($query);

        $obj = $result->fetch_assoc();
        $isActive = $obj['active'];
        return $isActive;
    }

// add vehicle
    public
    function add_vehicle($user_id, $type, $plate, $vin, $make, $model, $year, $km, $date, $crash, $front, $back, $lefty, $righty, $total)
    {
        $query = "select id from vehicle where vin = '$vin'";
        $result = $this->connectdb->query($query);
        $existVehicle = $result->num_rows;

        if ($existVehicle > 0) {
            $query = "update vehicle set plate = '$plate',pre_fix = '$pre_fix', after_fix='$after_fix', make = '$make', model = '$model', year = '$year', user_id = '$user_id', type = '$type' where vin = '$vin'";
        } else {
            $query = "insert into vehicle(plate, pre_fix, after_fix, vin, make, model, year, user_id, type) value('$plate','$pre_fix','$after_fix', '$vin', '$make', '$model', '$year', '$user_id', '$type')";
        }

        $result = $this->connectdb->query($query);
        if ($result) {
            if ($existVehicle > 0) {
                $result = $this->get_vehicle_by_vin($vin);
            } else {
                $query = "select id from vehicle ORDER BY id DESC LIMIT 1";
                $result = $this->connectdb->query($query);
            }

            $obj = $result->fetch_assoc();
            $car_id = $obj['id'];

            $logic = "";
            $result = $this->get_vehicle_km_by_car_id($car_id);
            $number = $result->num_rows;
            if ($number == 0) {
                $logic = "no";
            } else {
                while ($kmList = $result->fetch_assoc()) {
                    $dbValue = $kmList['km'];
                    $dbLogicValue = $kmList['logic'];
                    if ($dbValue > $km || $dbLogicValue == "false") {
                        $logic = "false";
                        break;
                    } else {
                        $logic = "true";
                    }
                }
            }

            $query = "insert into vehicle_km(car_id, km, add_date, logic) value('$car_id', '$km', '$date', '$logic')";
            $result = $this->connectdb->query($query);

            if ($result) {
                if ($existVehicle > 0) {
                    $crashedStateResult = $this->getCrashedByCarId($car_id);
                    $crashedState = $crashedStateResult->fetch_assoc();
                    if ($crashedState['crash'] == "yes") {
                        $crash = "yes";
                        $front = ($crashedState['front'] == "") ? $front : $crashedState['front'];
                        $back = ($crashedState['back'] == "") ? $back : $crashedState['back'];
                        $lefty = ($crashedState['lefty'] == "") ? $lefty : $crashedState['lefty'];
                        $righty = ($crashedState['righty'] == "") ? $righty : $crashedState['righty'];
                        $total = ($crashedState['total'] == "") ? $total : $crashedState['total'];
                    }
                    $query = "update vehicle_crash set crash = '$crash', front = '$front', back = '$back', lefty = '$lefty', righty = '$righty', total = '$total' where car_id = '$car_id'";
                } else {
                    $query = "insert into vehicle_crash(car_id, crash, front, back, lefty, righty, total) value('$car_id', '$crash', '$front', '$back', '$lefty', '$righty', '$total')";
                }
                $result = $this->connectdb->query($query);
            }
        }
        return $result;
    }

    public
    function get_vehicle_by_plate($plate)
    {
        $query = " select * from vehicle where plate = '$plate'";
        $result = $this->connectdb->query($query);
        return $result;
    }

    public
    function get_vehicle_by_vin($plate)
    {
        $query = " select * from vehicle where vin = '$plate'";
        $result = $this->connectdb->query($query);
        return $result;
    }

    public
    function get_vehicle_km_by_car_id($car_id)
    {
        $query = " select * from vehicle_km where car_id = '$car_id' order by add_date DESC, id DESC";
        $result = $this->connectdb->query($query);
        return $result;
    }

    public
    function get_last_vehicle_km_by_car_id($car_id)
    {
        $query = " select * from vehicle_km where car_id = '$car_id' order by add_date DESC, id DESC LIMIT 1";
        $result = $this->connectdb->query($query);
        return $result;
    }

// get user address
    public
    function getAddress($email, $type)
    {
        if ($type == "user")
            $query = "select * from user where email = '$email'";
        else
            $query = "select * from dealer where email = '$email'";
        $result = $this->connectdb->query($query);
        return $result;
    }

// add make
    public
    function add_make($name)
    {
        $query = "insert into make(name) values('$name')";
        $result = $this->connectdb->query($query);
        return $result;
    }
    public function getProvinList()
    {
        $query = "select * from province";
        $result = $this->connectdb->query($query);
        return $result;
    }

    public function getProvinById($id)
    {
        $query = "select * from province where id = '$id'";
        $result = $this->connectdb->query($query);
        return $result;
    }
    public function add_provin($english, $arabic, $greek, $kurdish)
    {
        $query = "insert into province(en, ar, el, Ku) values('$english', '$arabic', '$greek', '$kurdish')";
        $result = $this->connectdb->query($query);
        return $result;
    }

    public function update_provin($id, $english, $arabic, $greek, $kurdish)
    {
        $query = "update province set en='$english', ar='$arabic', el='$greek', Ku='$kurdish' where id = '$id'";
        $result = $this->connectdb->query($query);
        return $result;
    }
    
    public function existProvinByCode($new_provin, $lang_id)
    {
        $query = "select * from province where name = '$new_provin' and  lang_id = '$lang_id'";
        $result = $this->connectdb->query($query);
        $exist = $result->num_rows;

        if ($exist > 0) return true;
        return false;
    }
    public function deleteProvince($id)
    {
        $query = "delete from province where id = '$id'";
        $result = $this->connectdb->query($query);
        return $result;
    }

    public
    function getMakeList()
    {
        $query = "select * from make order by name ASC";
        $result = $this->connectdb->query($query);
        return $result;
    }
    

    public
    function getMaekIdByName($name)
    {
        $query = "select * from make where name = '$name'";
        $result = $this->connectdb->query($query);
        return $result;
    }

    public
    function existMake($new_make)
    {
        $query = "select * from make where name = '$new_make'";
        $result = $this->connectdb->query($query);
        $exist = $result->num_rows;

        if ($exist > 0) return true;
        return false;
    }

    

    public
    function deleteMake($makelist)
    {
        for ($i = 0; $i < count($makelist); $i++) {
            $name = $makelist[$i];
            $query = "delete from make where name = '$name'";
            $result = $this->connectdb->query($query);
            if (!$result)
                return false;
        }
        return $result;
    }

    

// add model
    public
    function add_model($name, $selectedMakeName)
    {
        $result = $this->getMaekIdByName($selectedMakeName);
        $obj = $result->fetch_assoc();
        $dep = $obj['id'];

        $query = "insert into model(name, dep) values('$name', '$dep')";
        $result = $this->connectdb->query($query);
        return $result;
    }

    public
    function getModelList()
    {
        $query = "select * from model order by name ASC";
        $result = $this->connectdb->query($query);
        return $result;
    }

    public
    function getModelListFromMake($selectedMake)
    {
        $result = $this->getMaekIdByName($selectedMake);
        $obj = $result->fetch_assoc();
        $dep = $obj['id'];

        $query = "select * from model where dep = '$dep' order by name ASC";
        $result = $this->connectdb->query($query);
        return $result;
    }

    public
    function existModel($new_model, $selectedMakeName)
    {
        $result = $this->getMaekIdByName($selectedMakeName);
        $obj = $result->fetch_assoc();
        $dep = $obj['id'];

        $query = "select * from model where name = '$new_model' AND dep = '$dep'";
        $result = $this->connectdb->query($query);
        $exist = $result->num_rows;

        if ($exist > 0) return true;
        return false;
    }

    public
    function deleteModel($modellist)
    {
        for ($i = 0; $i < count($modellist); $i++) {
            $name = $modellist[$i];
            $query = "delete from model where name = '$name'";
            $result = $this->connectdb->query($query);
            if (!$result)
                return false;
        }
        return $result;
    }

// admin
    public
    function admin_check($username, $password)
    {
        $query = "select * from admin where username = '$username' AND password = '$password'";
        $result = $this->connectdb->query($query);
        $exist = $result->num_rows;
        return $exist;
    }

    public
    function getAdminEmail()
    {
        $query = "select email from admin";
        $result = $this->connectdb->query($query);
        return $result;
    }

// userlist
    public
    function getUserList()
    {
        $query = "select * from user order by `id` DESC";
        $result = $this->connectdb->query($query);
        return $result;
    }

    public
    function getDealerList()
    {
        $query = "select * from dealer order by `id` DESC";
        $result = $this->connectdb->query($query);
        return $result;
    }

    public
    function deleteUser($id)
    {
        $query = "delete from user where id = '$id'";
        $result = $this->connectdb->query($query);
        return $result;
    }

    public
    function deleteDealer($id)
    {
        $query = "delete from dealer where id = '$id'";
        $result = $this->connectdb->query($query);
        return $result;
    }

    public
    function getUserById($id)
    {
        $query = "select * from user where id = '$id'";
        $result = $this->connectdb->query($query);
        return $result;
    }

    public
    function updateUser($id, $name, $address, $email, $phone, $password)
    {
        $query = "update user set name = '$name', address = '$address', email = '$email', phone = '$phone', password = '$password' where id = '$id'";
        $result = $this->connectdb->query($query);
        return $result;
    }

    public
    function getDealerById($id)
    {
        $query = "select * from dealer where id = '$id'";
        $result = $this->connectdb->query($query);
        return $result;
    }

    public
    function updateDealer($id, $name, $address, $email, $phone, $company, $website, $password)
    {
        $query = "update dealer set name = '$name', password = '$password', address = '$address', email = '$email', phone = '$phone', company = '$company', website = '$website' where id = '$id'";
        $result = $this->connectdb->query($query);
        return $result;
    }

// user block
    public
    function userBlock($id, $isChecked)
    {
        $query = "update user set block = '$isChecked' where id = '$id'";
        $result = $this->connectdb->query($query);
        return $result;
    }
    function userActive($id, $isChecked)
    {
        $query = "update user set active = '$isChecked' where id = '$id'";
        $result = $this->connectdb->query($query);
        return $result;
    }

    public
    function dealerBlock($id, $isChecked)
    {
        $query = "update dealer set block = '$isChecked' where id = '$id'";
        $result = $this->connectdb->query($query);
        return $result;
    }
    public
    function dealerActive($id, $isChecked)
    {
        $query = "update dealer set active = '$isChecked' where id = '$id'";
        $result = $this->connectdb->query($query);
        return $result;
    }

    public
    function getBlockList($type)
    {
        if ($type == "user")
            $query = "select * from user where block = 'True'";
        else
            $query = "select * from dealer where block = 'True'";
        $result = $this->connectdb->query($query);
        return $result;
    }

// vehicle list
    public
    function getVehicleList()
    {
        $query = "select * from vehicle order by `id` DESC";
        $result = $this->connectdb->query($query);
        return $result;
    }

    public
    function getCrashedByCarId($car_id)
    {
        $query = " select * from vehicle_crash where car_id = '$car_id'";
        $result = $this->connectdb->query($query);
        return $result;
    }

    public
    function deleteVehicle($id)
    {
        $query = "delete from vehicle where id = '$id'";
        $result = $this->connectdb->query($query);
        return $result;
    }

    public
    function deleteCrashByCarId($car_id)
    {
        $query = "delete from vehicle_crash where car_id = '$car_id'";
        $result = $this->connectdb->query($query);
        return $result;
    }

    public
    function deleteKmByCarId($car_id)
    {
        $query = "delete from vehicle_km where car_id = '$car_id'";
        $result = $this->connectdb->query($query);
        return $result;
    }

    public
    function getVehicleById($id)
    {
        $query = " select * from vehicle where id = '$id'";
        $result = $this->connectdb->query($query);
        return $result;
    }

    public
    function updateVehicle($id, $plate, $make, $model, $year)
    {
        $query = "update vehicle set plate = '$plate', make = '$make', model = '$model', year = '$year' where id = '$id'";
        $result = $this->connectdb->query($query);
        return $result;
    }

    public
    function updateVehicleCrash($car_id, $crash, $front, $back, $lefty, $righty, $total)
    {
        $crashedStateResult = $this->getCrashedByCarId($car_id);
        $crashedState = $crashedStateResult->fetch_assoc();
        if ($crashedState['crash'] == "yes") {
            $crash = "yes";
            $front = ($crashedState['front'] == "") ? $front : $crashedState['front'];
            $back = ($crashedState['back'] == "") ? $back : $crashedState['back'];
            $lefty = ($crashedState['lefty'] == "") ? $lefty : $crashedState['lefty'];
            $righty = ($crashedState['righty'] == "") ? $righty : $crashedState['righty'];
            $total = ($crashedState['total'] == "") ? $total : $crashedState['total'];
        }
        $query = "update vehicle_crash set crash = '$crash', front = '$front', back = '$back', lefty = '$lefty', righty = '$righty', total = '$total' where car_id = '$car_id'";
        $result = $this->connectdb->query($query);
        return $result;
    }

    public
    function updateVehicleKm($car_id, $km, $date)
    {
        $logic = "";
        $result = $this->get_vehicle_km_by_car_id($car_id);
        $number = $result->num_rows;
        if ($number == 0) {
            $logic = "no";
        } else {
            while ($kmList = $result->fetch_assoc()) {
                $dbValue = $kmList['km'];
                if ($dbValue > $km) {
                    $logic = "false";
                    break;
                } else {
                    $logic = "true";
                }
            }
        }

        $query = "insert into vehicle_km(car_id, km, add_date, logic) value('$car_id', '$km', '$date', '$logic')";
        $result = $this->connectdb->query($query);
        return $result;
    }

// price
    public
    function add_price($download_price, $download_tax)
    {
        $getresult = $this->getPrice();
        $query = "insert into price(price, tax) values('$download_price','$download_tax')";
        if ($getresult->num_rows) {
            $query = "update price set `price` = '$download_price',`tax` = '$download_tax'";
        }
        $result = $this->connectdb->query($query);
        return $result;
    }

    public
    function getPrice()
    {
        $query = "select * from price order by id DESC LIMIT 1";
        $result = $this->connectdb->query($query);
        return $result;
    }

//coupon
    public
    function add_coupon($code, $allow_usage)
    {
        $query = "insert into coupon(code, allow_usage) values('$code','$allow_usage')";
        $result = $this->connectdb->query($query);
        return $result;
    }

    public
    function update_coupon($code, $amount)
    {
        if (!$amount) {
            return 0;
        }
        $query = "update coupon set allow_usage = (allow_usage -$amount) where code = '$code'";
        //echo $query;exit;
        $result = $this->connectdb->query($query);
        return $result;
    }

    public
    function getCoupon()
    {
        $query = "select * from coupon order by id DESC";
        $result = $this->connectdb->query($query);
        return $result;
    }

    public
    function edit_coupon($code, $allow_usage)
    {
        $query = "update coupon set allow_usage = '$allow_usage' where code = '$code'";
        $result = $this->connectdb->query($query);
        return $result;
    }

    public
    function getCouponById($id)
    {
        $query = " select * from coupon where id = '$id'";
        $result = $this->connectdb->query($query);
        return $result;
    }

    public
    function deleteCoupon($id)
    {
        $query = "delete from coupon where id = '$id'";
        $result = $this->connectdb->query($query);
        return $result;
    }

//coupon use history
    public
    function add_coupon_history($code, $amount, $vin, $payer_email)
    {
        $query = "insert into coupon_history(code, amount,vin,payer_email) values('$code','$amount','$vin','$payer_email')";
        $result = $this->connectdb->query($query);
        return $result;
    }

    public
    function getCouponHistory()
    {
        $query = "select * from coupon_history order by id DESC";
        $result = $this->connectdb->query($query);
        return $result;
    }

    public
    function deleteCouponHistory($id)
    {
        $query = "delete from coupon_history where id = '$id'";
        $result = $this->connectdb->query($query);
        return $result;
    }

// admin change
    public
    function getAdmin($username)
    {
        $query = "select * from admin where username = '$username'";
        $result = $this->connectdb->query($query);
        return $result;
    }

    public
    function update_profile($username, $email)
    {
        $query = "update admin set email = '$email' where username = '$username'";
        $result = $this->connectdb->query($query);
        return $result;
    }

    public
    function update_password($username, $new_password)
    {
        $query = "update admin set password = '$new_password' where username = '$username'";
        $result = $this->connectdb->query($query);
        return $result;
    }

// user password
    public
    function changePassword($id, $type, $new_password)
    {
        if ($type == "user")
            $query = "update user set password = '$new_password' where id = '$id'";
        else
            $query = "update dealer set password = '$new_password' where id = '$id'";
        $result = $this->connectdb->query($query);
        return $result;
    }

// about content
    public
    function add_about_content($content, $content2, $lang_id)
    {
        $getcontent = $this->getAboutContentByCode($lang_id);

        $query = "insert into about(content,content2, lang_id) values('$content', '$content2', '$lang_id')";
        if ($getcontent->num_rows) {
            $query = "update about set `content` = '$content',`content2` = '$content2', `lang_id` = '$lang_id' where `lang_id`= '$lang_id'";
        }

        $result = $this->connectdb->query($query);
        return $result;
    }
    public
    function add_terms_content($content, $lang_id)
    {
        $getcontent = $this->getTermsContentByCode($lang_id);

        $query = "insert into terms(content, lang_id) values('$content', '$lang_id')";
        if ($getcontent->num_rows) {
            $query = "update terms set `content` = '$content', `lang_id` = '$lang_id' where `lang_id`= '$lang_id'";
        }

        $result = $this->connectdb->query($query);
        return $result;
    }
    public
    function add_poiicy_content($content, $lang_id)
    {
        $getcontent = $this->getPolicyContentByCode($lang_id);

        $query = "insert into policy(content, lang_id) values('$content', '$lang_id')";
        if ($getcontent->num_rows) {
            $query = "update policy set `content` = '$content', `lang_id` = '$lang_id' where `lang_id`= '$lang_id'";
        }

        $result = $this->connectdb->query($query);
        return $result;
    }

    public
    function getAboutContent()
    {
        $query = "select * from about order by id DESC LIMIT 1";
        $result = $this->connectdb->query($query);
        return $result;
    }

    public
    function getAboutContentByCode($lang_id)
    {
        $query = "select * from about where lang_id='" . $lang_id . "'  order by id DESC LIMIT 1";
        $result = $this->connectdb->query($query);
        return $result;
    }
    public
    function getTermsContentByCode($lang_id)
    {
        $query = "select * from terms where lang_id='" . $lang_id . "'  order by id DESC LIMIT 1";
        $result = $this->connectdb->query($query);
        return $result;
    }
    public
    function getPolicyContentByCode($lang_id)
    {
        $query = "select * from policy where lang_id='" . $lang_id . "'  order by id DESC LIMIT 1";
        $result = $this->connectdb->query($query);
        return $result;
    }

// index content
    public
    function add_content_content($content1, $content2)
    {
        $query = "insert into content(content1, content2) values('$content1', '$content2')";
        $result = $this->connectdb->query($query);
        return $result;
    }

    public
    function getContentContent()
    {
        $query = "select * from content order by id DESC LIMIT 1";
        $result = $this->connectdb->query($query);
        return $result;
    }

// get content
    public
    function getContent()
    {
        $query = "select * from content order by id DESC LIMIT 1";
        $result = $this->connectdb->query($query);
        return $result;
    }

    public
    function getContentByCode($lang_id)
    {
        $query = "select * from content where `lang_id`='$lang_id' order by id DESC LIMIT 1";
        $result = $this->connectdb->query($query);
        return $result;
    }

    public
    function add_content($content1, $content2, $lang_id)
    {
        $getcontent = $this->getContentByCode($lang_id);

        $query = "insert into content(content1, content2, lang_id) values('$content1', '$content2', '$lang_id')";
        if ($getcontent->num_rows) {
            $query = "update content set `content1` = '$content1', `content2` = '$content2', `lang_id` = '$lang_id' where `lang_id`= '$lang_id'";
        }

        $result = $this->connectdb->query($query);
        return $result;
    }

// get footer
    public
    function getFooter()
    {
        $query = "select * from footer order by id DESC LIMIT 1";
        $result = $this->connectdb->query($query);
        return $result;
    }

    function getFooterByCode($lang_id)
    {
        $query = "select * from footer where `lang_id`='$lang_id' order by id DESC LIMIT 1";
        $result = $this->connectdb->query($query);
        return $result;
    }

    public
    function add_footer($content1, $content2, $lang_id)
    {
        $getcontent = $this->getFooterByCode($lang_id);

        $query = "insert into footer(content1, content2, lang_id) values('$content1', '$content2', '$lang_id')";
        if ($getcontent->num_rows) {
            $query = "update footer set `content1` = '$content1', `content2` = '$content2', `lang_id` = '$lang_id' where `lang_id`= '$lang_id'";
        }

        $result = $this->connectdb->query($query);
        return $result;
    }

// information content
    public
    function add_information_content($content, $lang_id)
    {
        $getcontent = $this->getInformationContentByCode($lang_id);

        $query = "insert into information(content, lang_id) values('$content', '$lang_id')";
        if ($getcontent->num_rows) {
            $query = "update information set `content` = '$content', `lang_id` = '$lang_id' where `lang_id`= '$lang_id'";
        }
        $result = $this->connectdb->query($query);
        return $result;
    }

    public
    function getInformationContent()
    {
        $query = "select * from information order by id DESC LIMIT 1";
        $result = $this->connectdb->query($query);
        return $result;
    }

    public
    function getInformationContentByCode($lang_id)
    {
        $query = "select * from information where lang_id='" . $lang_id . "' order by id DESC LIMIT 1";
//        exit($query);
        $result = $this->connectdb->query($query);
        return $result;
    }

// image upload
    public
    function imageUpload($fileName, $bannerText, $position, $width, $height, $lang_id)
    {
        $query = "INSERT into images (file_name, uploaded_on, title, position, width, height, lang_id) VALUES ('" . $fileName . "', NOW(), '" . $bannerText . "', '" . $position . "', '" . $width . "', '" . $height . "', '" . $lang_id . "')";
        $result = $this->connectdb->query($query);
        return $result;
    }

// load banner data in dashboard
    public
    function loadBanner($position)
    {
        $query = "select * from images where position = '$position' order by uploaded_on DESC LIMIT 1";
        $result = $this->connectdb->query($query);
        return $result;
    }

    public
    function loadBannerByCode($position, $lang_id)
    {
        $query = "select * from images where position = '$position' and `lang_id`='$lang_id' order by uploaded_on DESC LIMIT 1";
        $result = $this->connectdb->query($query);
        return $result;
    }

// get all homescreen
    public
    function getHomescreen()
    {
        $query = "select * from images";
        $result = $this->connectdb->query($query);
        return $result;
    }

// delete homescreen
    public
    function deleteHomescreen($id)
    {
        $query = "delete from images where id = '$id'";
        $result = $this->connectdb->query($query);
        return $result;
    }

// get homescreen by id
    public
    function getHomescreenById($id)
    {
        $query = "select * from images where id = '$id'";
        $result = $this->connectdb->query($query);
        return $result;
    }

    function getHomescreenByIdAndCode($id, $lang_id)
    {
        $query = "select * from images where `lang_id`='$lang_id' id = '$id'";
        $result = $this->connectdb->query($query);
        return $result;
    }

// update homescreen
    public
    function updateHomescreen($id, $file_name, $title, $position, $width, $height, $lang_id)
    {
        $query = "update images set file_name = '$file_name', title = '$title', position = '$position', width = '$width', height = '$height',lang_id = '$lang_id', uploaded_on = NOW() where id = '$id'";
        $result = $this->connectdb->query($query);
        return $result;
    }

// add Ads Banner
    public
    function addAdsBanner($fileName, $bannerText, $link, $position, $start_date, $end_date)
    {
        $activate = "yes";
        $query = "INSERT into adsbanner (fileName, bannerText, link, position, start_date, end_date, activate, created_on) VALUES ('" . $fileName . "', '" . $bannerText . "', '" . $link . "', '" . $position . "', '" . $start_date . "', '" . $end_date . "', '" . $activate . "', NOW())";
        $result = $this->connectdb->query($query);
        return $result;
    }

// get all banner info
    public
    function getBanner()
    {
        $query = "select * from adsbanner";
        $result = $this->connectdb->query($query);
        return $result;
    }

// get banner by id
    public
    function getBannerById($id)
    {
        $query = "select * from adsbanner where id = '$id'";
        $result = $this->connectdb->query($query);
        return $result;
    }

// add Ads Banner
    public
    function updateAdsBanner($id, $fileName, $bannerText, $link, $position, $start_date, $end_date)
    {
        $query = "update adsbanner set fileName = '$fileName', bannerText = '$bannerText', link = '$link', position = '$position', start_date = '$start_date', end_date = '$end_date', created_on = NOW() where id = '$id'";
        $result = $this->connectdb->query($query);
        return $result;
    }

// delete banner
    public
    function deleteBanner($id)
    {
        $query = "delete from adsbanner where id = '$id'";
        $result = $this->connectdb->query($query);
        return $result;
    }

// activate banner
    public
    function activateBanner($id)
    {
        $state = $this->getActivateState($id);
        if ($state == "yes")
            $activate = "no";
        else
            $activate = "yes";
        $query = "update adsbanner set activate = '$activate' where id = '$id'";
        $result = $this->connectdb->query($query);
        return $result;
    }

// load specific ads banner
    public
    function loadAdsBanner($position)
    {
        $today = date('Y-m-d');
        $query = "select * from adsbanner where position = '$position' and activate = 'yes' and start_date <= '$today' and end_date >= '$today'";
        $result = $this->connectdb->query($query);
        return $result;
    }

// get activate state
    public
    function getActivateState($id)
    {
        $query = "select * from adsbanner where id = '$id'";
        $result = $this->connectdb->query($query);
        $obj = $result->fetch_assoc();
        return $obj['activate'];
    }

// paypal payment
    public
    function putPaymentResult($email, $itemAmount)
    {
        $query = "insert into payments(email, itemAmount, pay_date) value('$email', '$itemAmount', NOW())";
        $result = $this->connectdb->query($query);
        return $result;
    }

    public
    function getPayments()
    {
        $query = "select * from payments";
        $result = $this->connectdb->query($query);
        return $result;
    }

    public
    function reportCount()
    {
        $query = "select * from report";
        $result = $this->connectdb->query($query);

        $obj = $result->fetch_assoc();
        $count = $obj['count'];
        $count++;

        $query = "update report set count = '$count' where id = 1";
        $result = $this->connectdb->query($query);
        return $result;
    }

    public
    function getReportCount()
    {
        $query = "select * from report";
        $result = $this->connectdb->query($query);

        $obj = $result->fetch_assoc();
        $count = $obj['count'];
        return $count;
    }
}

$config = new Car();
