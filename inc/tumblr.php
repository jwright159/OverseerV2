<?php
$numPosts = 10;
$ida = 1;
$feedURL = "http://theoverseerproject.tumblr.com/api/read/?num=$numPosts";
$xml = simplexml_load_file($feedURL);
foreach($xml->posts->post as $post){
echo "<span class='tumpost'>";
$type = $post->attributes();
$url = $type['url'];
$date = $type['date'];

   if ($type['type'] == "regular")
   {
   $title = (string) $post->{'regular-title'};
   $body = (string) $post->{'regular-body'};
   echo "<a href='$url'><p id='datea'>$date</p></a>";
   echo "<h3>Text Post: <a href='$url'>$title</h3></a>";
   echo "<p>$body</p>";
   }

   if ($type['type'] == "answer")
   {
   $title = (string) $post->{'question'};
   $body = (string) $post->{'answer'};
   echo "<a href='$url'><p id='datea'>$date</p></a>";
   echo "<h3>Ask: <a href='$url'>$title</h3></a>";
   echo "<p>$body</p>";
   }

   if ($type['type'] == "photo")
   {
   $title = (string) $post->{'photo-url'};
   $body = (string) $post->{'photo-caption'};
   echo "<a href='$url'><p id='datea'>$date</p></a>";
   echo "<a href='$title'><img src='$title' /></a>";
   echo "<p>$body</p>";
   }

   if ($type['type'] == "link")
   {
   $text = (string) $post->{'link-text'};
   $urla = (string) $post->{'link-url'};
   $body = (string) $post->{'link-description'};
   echo "<a href='$url'><p id='datea'>$date</p></a>";
   echo "<h3>Link: <a href='$urla'>$text</h3></a>";
   echo "<p>$body</p>";
   }

   if ($ida == $numPosts){}
   else {
   echo "<hr color='black'>";
   }
   $ida++;
echo '</span>';
}
?>