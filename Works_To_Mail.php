<?php

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "CompanyDataBase";
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Ei õnnestunud luua ühendust: " . $conn->connect_error);
}

$sql = "SELECT DISTINCT `onr`, productowners.email AS email 
    FROM `proddb`
    LEFT JOIN productowners  ON proddb.cusref1 LIKE CONCAT('%', productowners.cusref1,'%')
    WHERE `deldate` = CURDATE()+1
    ORDER BY `proddb`.`onrrnr` ASC";

$result3 = $conn->query($sql);
$messages = [];
$recivers = [];

while ($row2 = $result3->fetch_assoc()){
        $onr = $row2['onr'];
        $email = $row2['email'];
        array_push($recivers, $email);

       $message = '
<html><body>
<style type="text/css">
        table { width:250px; }
        table tr { height:1em; overflow:hidden; }
    </style>';

$query = "SELECT proddb.onrrnr,proddb.idnr,proddb.onr,proddb.prod,proddb.qty
          FROM proddb 
          WHERE proddb.onr=" . $onr . " AND proddb.idnr<>'0'";


    $result1 = $conn->query($query);
    $num_results1 = $result1->num_rows;
    if ($num_results1 > 0) {
        $message .= '

<table border="1" class="db-table">
</br>
<thead>
<tr>
<th width="">Tellimus</th>
<th width="6">ID</th>
<th width="8"> <meta charset="UTF-8"> Toode</th>
<th width="">Valmis</th>
</tr>
</thead>
<tbody>';

        for ($j = 0; $j < $num_results1; $j++){
            $row = $result1->fetch_assoc();

$query1 = 'SELECT p1.onrrnr, p1.prLineSh, p1.glLiteNr, p1.seqNr, p1.planQty 
           FROM pf_prodQtySeq p1 INNER JOIN (SELECT onrrnr, glLiteNr, MAX(seqNr) AS seqNr 
           FROM pf_prodQtySeq  where onrrnr=' . $row['onrrnr'] . '  
           GROUP BY  glLiteNr) p2 ON p2.glLiteNr = p1.glLiteNr AND p2.seqNr = p1.seqNr AND p2.onrrnr=p1.onrrnr';
           $conn->query($query1);
            $qtyMultiplier = 1;
            $num_results2 = $result2->num_rows;
            if ($num_results2 > 0) {
                $liteOrdQty = 0;
                $remQty = 0;
                for ($i1 = 0; $i1 < $num_results2; $i1++) {
                    if ($num_results2 > 1) {
                        $qtyMultiplier = $num_results2;
                    }
                    $row1 = $result2->fetch_assoc();
                    $liteOrdQty = $liteOrdQty + $row['qty'];
                    $remQty = $remQty + $row1['planQty'];
                }
                $liteOrdQty = $liteOrdQty / $qtyMultiplier;
                $remQty = $remQty / $qtyMultiplier;
                $doneQty = ($liteOrdQty - $remQty);

                if ($liteOrdQty === $remQty) {
                    $message .= '<tr style="font-weight:bold">
                     <td>' . $row['onr'] . '</td>
                     <td>' . $row['idnr'] . '</td>
                     <td>' . utf8_encode($row['prod']) . '</td>
                     <td style = "color:red">' . $liteOrdQty . "/" . $doneQty . '</td>
                     </tr>';

                }
            }else{
                continue 2;
            }
        }
        $message .= '</tbody></table>';

    }

    $message .= '</body></html><br><br/>';
    $genTime = date("Y-m-d H:i:s");
    $message .= "Kahjuks on siin teie tellimused, mille staatus on mittevalmis!\n
 Aruanne genereeriti: $genTime";
    echo $message ;
    array_push($messages,$message);
}

$headers = 'MIME-Version: 1.0' . "\r\n";
$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";

$subject = "Tere hommikust!";
$from = "tooteomanik@ettevõtte.ee";
$headers .= 'From: ' . $from . "\r\n" .
    'Reply-To: ' . $from . "\r\n" .
    'X-Mailer: PHP/' . phpversion();

foreach ($messages as $index=> $content){
    if (mail($recivers[$index], $subject, $content, $headers)) {
        echo 'Your mail has been sent successfully.';
    } else {
        echo 'Unable to send email. Please try again.';
    }
}
?>