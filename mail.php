<?php

include 'PHPMailer/class.phpmailer.php';

$emailBody = '

<!doctype html>
<html>
<head>
<title>Tyresonline.ae PRO</title>  
</head>
<body>
  <!--Email Signature Start -->
Dear Partner,
<br/><br/>
<p style="font-family: Trebuchet MS, Arial, Verdana, sans-serif, Tahoma, Times New Roman, Trebuchet MS;">My name is Marc, and it is my please to introduce to you our company, <a href="https://business.tyresonline.ae">Tyresonline.ae PRO</a>  A game changer solution for professionals in the tyre and automotive maintenance and repair sector.<br/><br/>
To put it simple, we are tyre, lubricants, battery and aftermarket auto parts specialists. Our prices are amongst the most competitive and affordable in the UAE market, our team members are genuine people you can trust as individuals and professional experts.<br/><br/>
As business professionals, we are all aware that buying well means selling well. Now with the Covid19 reality, this is more key than ever, and we understand this. So yes, price matters.<br/><br/>
We come to you in the most honest and transparent way. Call us, meet with us, ask us anything and we will ensure our best to meet your expectations. We are both an online and offline solution.<br/><br/>
For online solution visit our website <a href="https://business.tyresonline.ae">https://business.tyresonline.ae</a> and sign up for an account today. Here you can view stock availability and pricing. You can easily order online and have the goods shipped to you.<br/><br/>
Still, and if you prefer to take the personal offline approach, for tyres, you can contact me personally by phone or simply drop me a WhatsApp message:<br/>
<strong>Marc +971 56 547 6768</strong><br/><br/>
For Spare Parts, Lubricants and Batteries call or WhatsApp:<br/>
<strong>Yassir +971 54 584 2533</strong> <br/><br/>
We believe that business brings people together, so, what do you have to loose in contacting us?<br/><br/></p>

  <table style=" font-family:Trebuchet MS,Arial, Verdana, sans-serif, Tahoma, Times New Roman, Trebuchet MS; font-size:12px; color:#2e2925; line-height:16px;" cellspacing="0" cellpadding="0" border="0">
    <tbody>
      <tr>
        <td valign="middle" nowrap="nowrap" align="center">
			<a href="http://www.tyresonline.ae" target="_blank"><img src="https://dl.dropboxusercontent.com/s/qxw8j14lys83h07/logo.png?dl=0" alt="" width="200" height="82"></a>
          	<div style="width:99%; height:5px;line-height:5px; overflow:hidden; padding:0; margin:0;font-size:0px; display:block">&nbsp;</div>
		  </td>
      </tr>
      <tr>
        <td valign="middle" nowrap="nowrap" align="center" style="border-bottom: solid 2px #d80000">
          <span style="color:#2e2925;"><strong>Marc Housseiny</strong></span> <span>&#8226;</span> 
			<span>Sales Manager</span><br>
			<a href="tel:+971 56 547 6768" style=" color:#2e2925;text-decoration:none;"><span style="text-decoration:none"><strong>+971 56 547 6768</strong></span></a> <span>&#8226;</span> 
			<a href="mailto:marc@tyresonline.ae" style=" color:#2e2925;text-decoration:none;"><span style="text-decoration:none">marc@tyresonline.ae</span></a> <br>		  
		    <div style="width:99%; height:5px;line-height:5px; overflow:hidden; padding:0; margin:0;font-size:0px; display:block">&nbsp;</div>
		  </td>
      </tr>
      <tr>
        <td valign="middle" nowrap="nowrap" align="center">
          	<div style="width:99%; height:5px;line-height:5px; overflow:hidden; padding:0; margin:0;font-size:0px; display:block">&nbsp;</div>
			<strong><span><span style="color: #d80000">800</span> ALL TYRES </span> | 
			<a href="tel:800 255 89737" style=" color:#2e2925;text-decoration:none;"><span style="text-decoration:none"><span style="color: #d80000">800</span> 255 89737</span></a> <br>
			</strong>          
			<a href="http://www.tyresonline.ae" style=" color:#2e2925;text-decoration:none;" target="_blank"><span style="text-decoration:none">www.tyresonline.ae</span></a><br>
          	<div style="width:99%; height:5px;line-height:5px; overflow:hidden; padding:0; margin:0;font-size:0px; display:block">&nbsp;</div>
          	<a href="https://www.facebook.com/tyresonlineuae/" target="_blank"><img style="display:inline; overflow:hidden; border:none;" src="https://dl.dropboxusercontent.com/s/qx7yh1a4cnl5u4a/facebook.png?dl=0" alt="Facebook" width="23" height="23"></a>&nbsp; 
			<a href="https://www.instagram.com/tyresonline.ae/" target="_blank"><img style="display:inline; overflow:hidden; border:none;" src="https://dl.dropboxusercontent.com/s/lkhc7b7zk3ybbd8/instagram.png?dl=0" alt="Instagram" width="23" height="23"></a>&nbsp; </td>
      </tr>
    </tbody>
  </table>
  <div style="width:100px; height:5px;line-height:5px; overflow:hidden; padding:0; margin:0;font-size:0px; display:block">&nbsp;</div>
  <table style="font-family:Trebuchet MS, Arial, Helvetica, sans-serif; font-size:11px; color:#2e2925; " width="100%" cellspacing="0" cellpadding="0" border="0">
    <tbody>
      <tr>
        <td style="padding-top:10px;">This email and any files transmitted with it are confidential and intended solely for the use of the individual or entity to whom they are addressed. If you have received this email in error please notify the system manager. This message contains confidential information and is intended only for the individual named. If you are not the named addressee you should not disseminate, distribute or copy this e-mail. Please notify the sender immediately by e-mail if you have received this e-mail by mistake and delete this e-mail from your system. </td>
      </tr>
    </tbody>
  </table>

<br>
<div style="white-space:nowrap; font:15px courier; line-height:0;">  &nbsp; &nbsp; &nbsp; &nbsp;  &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; </div>
</body>
</html>


';
$row = 1;
if (($handle = fopen("b2b-customer.csv", "r")) !== FALSE) {
	while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
/*		$findArr = array('FIRST_NAME', 'PASS_WORD');
		$replaceArr = array($data[1], $data[2]);
		$updateEmailBody = str_replace($findArr, $replaceArr, $emailBody);*/
		$mailObj             = new PHPMailer();
		$mailObj->IsSMTP();
		$mailObj->SMTPAuth   = true;
		$mailObj->Host       = "hosting51.serverhs.org";
		$mailObj->Port       = 587;                   
		$mailObj->Username   = "marc@tyresonline.ae"; 
		$mailObj->Password   = "Mrc@TYO2020"; 
		$mailObj->addAttachment('TyresonlinePro.pdf');
		$mailObj->SetFrom('marc@tyresonline.ae', 'Tyresonline.ae PRO');
		$mailObj->Subject    = 'Partner Value: Tyres + Auto Spare Parts';
		$mailObj->MsgHTML($emailBody);
		$mailObj->AddAddress($data[0]);
		
		$sendResult = $mailObj->Send();

		if ($sendResult)
		{
		     echo "Message has been sent to " . $data[0] ."<br/><br/>";
		}
		else
		{
		     echo "Mailer Error: " . $mail->ErrorInfo ."<br/><br/>";
		}

		$row++;
		sleep(60);
	}
}