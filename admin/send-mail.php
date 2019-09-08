<?php

 include "../phpmailer/PHPMailerAutoload.php";
 $result='';

function smtpmailer($to, $from, $from_name, $subject, $body){
    $mail = new PHPMailer();
    $mail->IsSMTP();
    $mail->SMTPAuth = true;
    
    $mail->SMTPSecure = 'ssl';
    $mail->Host = 'www.car-km.com';
    $mail->Port = '465';
    $mail->Username = '_mainaccount@car-km.com';
    $mail->Password = '6c,E(PJw#lK;';
    
    $mail->IsHTML(true);
    $mail->From = '_mainaccount@car-km.com';
    $mail->FromName=$from_name;
    $mail->Sender=$from;
    $mail->AddReplyTo($from, $from_name);
    $mail->Subject = $subject;
    $mail->Body = $body;
    $mail->AddAddress($to);
    if(!$mail->Send()){
        $error = "Mail isn't sent.";
        return $error;
    }else{
        $return_str = "Your email is sent.";
        return $return_str;
    }
}

if(isset($_POST['send_email'])) {
    //echo 123;exit;
    $title = $_POST['mail_tilte'];
    $content = $_POST['content1'];
    //var_dump($_POST); exit;

    $to = 'sndswllow519812@gamaill.com';
    $from = '_mainaccount@car-km.com';
    $name = 'Carpass';
    $subj = $title;
    $msg = $content;
    $result = smtpmailer($to,$from,$name,$subj,$msg);
}
?>
<style>
.dataTables_wrapper{
    width:100%;
}
.check-all-user{
    margin-right:10px;
}
.check-all-dealer{
    margin-right:10px;
}
</style>
<div>
    <h2><?php echo $result; ?></h2>
    
    <form method="post" class="form-horizontal" role="form">
        
        <div class="row col-md-12">
            
            <label class="control-label" style="font-size:30px">Title</labe
            <div class="col-md-1"> </div>
            <div class="col-md-7 text-left mt-1">
                <div class="agileits-main">
                    <!--<i class="fas fa-list-ol"></i>-->
                    <input type="text" required="" name="mail_tilte" id="mail_tiltes" style="width:100%">
                </div>
            </div>
        </div>
        
        <div class="row col-md-12">
            <label class="control-label" style="font-size:30px">User List</label>
        </div>
        
        <div class="row col-md-12">
                <?php
                    $userList = $config->getUserList();
                    $count = $userList->num_rows;
                ?>
            	<table class="table table-bordered" id="fouad-dataTable1">
                    <thead>
                        <tr>
                            <th class="text-center">#</th>
                            <th>Name</th>
                            <th>Address</th>
                            <th>Email</th>
                            <th><input type="checkbox" class="check-all-user">Check</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
            				$s_sn = 1;
            				if($count > 0) {
            					while($user = $userList->fetch_assoc()) { 
            			?>
                        <tr>
                            <td class="text-center"><?php echo $s_sn; ?></td>
                            <td><?php echo $user['name']; ?></td>
                            <td><?php echo $user['address']; ?></td>
                            <td><?php echo $user['email']; ?></td>
                            <td>
                                <input type="checkbox" class="user-checkbox" value="" name="user_checkbox[<?php echo $user['id']; ?>]" data-id="<?php echo $user['id']; ?>" >
                            </td>
                        </tr>
                        <?php $s_sn++; }} else {
            			?>
                        <td colspan="12">No any user information found
                        </td>
                        <?php 
            						}  ?>
                    </tbody>
                </table>
        </div>
        
        <div class="row col-md-12">
            <label class="control-label" style="font-size:30px">Dealer List</label>
        </div>
            
        <div class="row col-md-12">
                <?php
                    $dealerList = $config->getDealerList();
                    $count = $dealerList->num_rows;
                ?>
            	<table class="table table-bordered" id="fouad-dataTable2">
                    <thead>
                        <tr>
                            <th class="text-center">#</th>
                            <th>Name</th>
                            <th>Address</th>
                            <th>Email</th>
                            <th><input type="checkbox" class="check-all-dealer">Check</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
            				$s_sn = 1;
            				if($count > 0) {
            					while($dealer = $dealerList->fetch_assoc()) { 
            			?>
                        <tr>
                            <td class="text-center"><?php echo $s_sn; ?></td>
                            <td><?php echo $dealer['name']; ?></td>
                            <td><?php echo $dealer['address']; ?></td>
                            <td><?php echo $dealer['email']; ?></td>
                            <td>
                                <input type="checkbox" class="dealer-checkbox" value="" name="dealer_checkbox[<?php echo $dealer['id']; ?>]" data-id="<?php echo $dealer['id']; ?>" >
                            </td>
                        </tr>
                        <?php $s_sn++; }} else {
            			?>
                        <td colspan="12">No any dealer information found
                        </td>
                        <?php 
            						}  ?>
                    </tbody>
                </table>
        </div>
        
        <div class="row col-md-12">
            <label class="control-label" style="font-size:30px">Mail Content</label>
        </div>
	    <script src="../css/ckeditor/ckeditor.js"></script>
        <textarea id="ckeditor" class="ckeditor" name="content1" style="min-height:200px;"></textarea>
        <div class="text-center submit mt-5">
            <button type="submit" class="btn btn-primary submit-fs btn-custom" name="send_email" id="send_eamil_btn">Send</button>
        </div>
    </form>
</div>
<script src="../js/jquery.min.js" ></script>
<script src="../js/bootstrap.min.js"></script>
<script src="../js/dataTables.1.9.4.js"></script>
<script src='../js/select2/select2.min.js' type='text/javascript'></script>

<script type="text/javascript">
    $(function() {
        // $("#fouad-dataTable1").dataTable();
        // $("#fouad-dataTable2").dataTable();
    });
    
    //select all checkboxes
    $(".check-all-dealer").change(function(){  //"select all" change 
    	var status = this.checked; // "select all" checked status
    	$('.dealer-checkbox').each(function(){ //iterate all listed checkbox items
    		this.checked = status; //change ".checkbox" checked status
    	});
    });
    
    $(".check-all-user").change(function(){  //"select all" change 
    	var status = this.checked; // "select all" checked status
    	$('.user-checkbox').each(function(){ //iterate all listed checkbox items
    		this.checked = status; //change ".checkbox" checked status
    	});
    });
    
    $('#fouad-dataTable1').dataTable({
        "aLengthMenu": [[5, 10, 25, -1], [5, 10, 25, "All"]],
        "iDisplayLength": 5,
        "bSort": false,
    });
    
    $('#fouad-dataTable2').dataTable({
        "aLengthMenu": [[5, 10, 25, -1], [5, 10, 25, "All"]],
        "iDisplayLength": 5,
        "bSort": false,
    });
    
    // $('#send_eamil_btn').click(function(){
    //     console.log("all data funcions");
    //     $('checked_value_area').val("namamamamamam")
    // });
</script>