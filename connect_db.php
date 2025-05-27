<?php
    $serveris ="localhost";
    $lietotajs="grobina1_skinkis";
    $parole="yB630ycy@";
    $datubaze="grobina1_skinkis";

    $savienojums =mysqli_connect($serveris, $lietotajs, $parole, $datubaze);

    if(!$savienojums){
        #echo("Piesligties db neizdevas:".mysqli_connect_error());
    }else{
        #echo "Savienojums veikmigi izveidots";
    }