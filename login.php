<?php
 require_once("./phpQuery-onefile.php");
 $reportnames = new ArrayObject();
 $nameTable = array();
 function updateCookie($user,$pass)
 {
    $data = "username={$user}&password={$pass}";
    $ch = curl_init("https://moodle.edu.osaka-pct.ac.jp/moodle/login/index.php");
    curl_setopt($ch,CURLOPT_COOKIEJAR,"./cookie.txt");
    curl_setopt($ch,CURLOPT_POSTFIELDS,$data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
    $res = curl_exec($ch);
    curl_close($ch);
 }

 function getHtmlWithCookie($url)
 {
    $cookie = "./cookie.txt";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_COOKIEFILE,$cookie);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
    $output = curl_exec($ch);
    curl_close($ch);
    return($output);
 }

 function getTaskFromSub($url)
 {
    $urlids = new ArrayObject();
    $reportnames = new ArrayObject();
    global $nameTable,$endtime;
    $output = getHtmlWithCookie($url);
    $doc = phpQuery::newDocument($output);
    //$coursenames = $doc[".coursename"];
    //echo $coursenames;
   
   $names = $doc["li"]->find(".activity.assign.modtype_assign");
   $h1 = $doc["h1"]->text();
   $SubNames = explode(" ",$h1);
   $SubName = $SubNames[count($SubNames)-1];
   foreach($names as $h)
   {
       $urlids->append(str_replace("https://moodle.edu.osaka-pct.ac.jp/moodle/mod/assign/view.php?id=","",pq($h)->find("a")->attr("href")));
       $reportnames->append(pq($h)->text().":{$SubName}");
   }
   
   for($i = 0,$c = count($reportnames);$c>$i;++$i)
   {
       $nameTable = $nameTable + array($urlids[$i] =>$reportnames[$i]);
   }
   
   foreach($nameTable as $key=>$val)
   {
       $url = "https://moodle.edu.osaka-pct.ac.jp/moodle/mod/assign/view.php?id=".$key;
       $out = getHtmlWithCookie($url);
       $doc = phpQuery::newDocument($out);
       if($doc["input[type='submit']"]->attr("value") != "課題を追加する")unset($nameTable[$key]);
       else{
           $endtime[$key] = $doc["td:contains('終了日時') + td"]->text();
       }
   }
   
   #var_dump($endtime);
 }

 function getSubsUrl($url)
 {
    $urls = new ArrayObject();
    $out = getHtmlWithCookie($url);
    $doc = phpQuery::newDocument($out);
    $subs = $doc["h3"];
    foreach($subs as $s)
    {
       $t = pq($s)->find("a")->attr("href");
       $urls->append($t);
    }
   return($urls);
 }

 function mainloop($user,$pass,$url){
    global $endtime,$nameTable;
    updateCookie($user,$pass);
    $urls = getSubsUrl("https://moodle.edu.osaka-pct.ac.jp/moodle/course/index.php?categoryid=310");
    foreach($urls as $url)
    {
        getTaskFromSub($url);
    }
    natsort($endtime);
    echo "<table>";
    echo "<tr>";
    echo "<th>"."教科"."</th>";
    echo "<th>"."締め切り"."</th>";
    echo "<tr>";
    foreach($endtime as $id => $end)
    {
        $url = "https://moodle.edu.osaka-pct.ac.jp/moodle/mod/assign/view.php?id=".$id;
        echo "<tr>";
        echo "<td>"."<a href='".$url."'>".$nameTable[$id]."</a>"."</td>";
        echo "<td>".$endtime[$id]."</td>";
        echo "</tr>";
    }
    echo "</table>";
 }

?>