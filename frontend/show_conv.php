<html>
<body>
<pre>
<?php
$db = new mysqli('localhost','rene','gimmexs86','rene');


#$tweet_id = "813715220724088832";
$tweet_id = "813715198930546688";
#$tweet_id = "814100742336290816";
#$tweet_id = "813747039314071554";
#$tweet_id = "850075949123207168";
$tweet_id = $_REQUEST['tweet_id'];


// Query for top tweet in the conversation
$q = "select t1.tweet_id as tweet_id_1
           , t2.tweet_id as tweet_id_2
           , t3.tweet_id as tweet_id_3
           , t4.tweet_id as tweet_id_4
           , t5.tweet_id as tweet_id_5
           , t6.tweet_id as tweet_id_6
        from twitter t1
        left join twitter t2 on (coalesce(t1.in_reply_to_status_id, NULLIF(t1.quoted_status_id,0), t1.retweet_id) = t2.tweet_id)
        left join twitter t3 on (coalesce(t2.in_reply_to_status_id, NULLIF(t2.quoted_status_id,0), t2.retweet_id) = t3.tweet_id)
        left join twitter t4 on (coalesce(t3.in_reply_to_status_id, NULLIF(t3.quoted_status_id,0), t3.retweet_id) = t4.tweet_id)
        left join twitter t5 on (coalesce(t4.in_reply_to_status_id, NULLIF(t4.quoted_status_id,0), t4.retweet_id) = t5.tweet_id)
        left join twitter t6 on (coalesce(t5.in_reply_to_status_id, NULLIF(t5.quoted_status_id,0), t5.retweet_id) = t6.tweet_id)
        where t1.tweet_id=".$tweet_id;
 /*
       left join twitter t2 on (t1.quoted_status_id = t2.tweet_id)
        left join twitter t3 on (t2.quoted_status_id = t3.tweet_id)
        left join twitter t4 on (t3.quoted_status_id = t4.tweet_id)
        left join twitter t5 on (t4.quoted_status_id = t5.tweet_id)
        left join twitter t6 on (t5.quoted_status_id = t6.tweet_id)
*/
if($rs = $db->query($q)) {
    $r = $rs->fetch_assoc();
    for($i=6; $i>=1; $i--) {
        if($r['tweet_id_'.$i] != NULL) {
            $top_tweet_id = $r['tweet_id_'.$i];
            break;
        }
    }
}
echo $top_tweet_id."\n";

$ids = array($top_tweet_id);
for ($i=0; $i <= 5; $i++) {
    $ids_imploded = implode(',',$ids);
    $q = "select tweet_id from twitter where tweet_id              in ($ids_imploded) 
                                          or quoted_status_id      in ($ids_imploded) 
                                          or retweet_id            in ($ids_imploded)
                                          or in_reply_to_status_id in ($ids_imploded)";
    $rs = $db->query($q);
    while($r = $rs->fetch_assoc()) {
        $ids[] = $r['tweet_id'];
    }
    $ids = array_unique($ids);
    if ($ids === explode(',',$ids_imploded)) break;
}


$q = "select tweet_id, quoted_status_id, retweet_id from twitter where tweet_id = $tweet_id";
if($rs = $db->query($q)) {
    $r = $rs->fetch_assoc();
    
    $in = $r['tweet_id'];
    if($r['quoted_status_id'] >0) $in = $in . "," . $r['quoted_status_id'];

    echo $in;
}
echo "</pre>";

$q  = "SELECT t.created_at, t.user_name, t.user_screen_name, t.tweet_id, t.quoted_status_id, t.tweet_text, t.retweet_id, t.in_reply_to_status_id, c.conversation_id
         FROM twitter t
         left join twitter_conversations c on (t.tweet_id = c.tweet_id)
        WHERE t.tweet_id in (".implode(',',$ids).") 
        ORDER BY t.created_at ASC";

if($rs = $db->query($q)) {
    printf("<table>\n");
    printf("\t<tr><th>&nbsp;</th><th>#</th><th>user_screen_name</th><th>tweet_id</th><th>quoted_status_id</th><th>retweet_id</th><th>in_reply_to_status_id</th><th>conversation_id</th><th>tweet_text</th></tr>");
    $i = 0;
    while($r = $rs->fetch_assoc()) {
        printf("\t<tr>");
        if ( $r['tweet_id'] == $tweet_id) printf("\t\t<td>X</td>\n");
        else                              printf("\t\t<td>&nbsp;</td>\n");
        printf("\t\t<td>%s</td>\n", ++$i);
        printf("\t\t<td>%s</td>\n", $r['user_screen_name']);
        printf("\t\t<td>%s</td>\n", $r['tweet_id']);
        printf("\t\t<td>%s</td>\n", $r['quoted_status_id']);
        printf("\t\t<td>%s</td>\n", $r['retweet_id']);
        printf("\t\t<td>%s</td>\n", $r['in_reply_to_status_id']);
        printf("\t\t<td>%s</td>\n", $r['conversation_id']);
        printf("\t\t<td>%s</td>", $r['tweet_text']);
        printf("\t</tr>\n");
    }
}
?>
</body>
</html>
