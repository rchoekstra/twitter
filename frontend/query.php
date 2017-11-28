<?php
spl_autoload_register(function ($class_name) {
    include $class_name . '.class.php';
});
session_start();

$config = json_decode(file_get_contents('../config.json'));

$db = new mysqli($config->db->host,$config->db->user,$config->db->password,$config->db->database);
$db->set_charset('utf8mb4');
$qry = new Query();

if(isset($_REQUEST['id'])) {
if ($_REQUEST['id']==0) {
    $qry->setQuery("select concat('Date(',convert(unix_timestamp(date(created_at))*1000, CHAR(15)),')') as date
                                 , count(*)         as num_tweets
                              from twitter 
                              where created_at >= '2016-10-01 00:00:00'
                             group by date(created_at) order by date(created_at)");
    $qry->addColumn("date", "Datum", "date");
    $qry->addColumn("num_tweets","Tweets", "number");
    $qry->setTitle("Dagelijks twitter gebruik");
    $qry->setOption("isStacked", true);
    $qry->setOption("legend", array('position' => none));
    $qry->setSubTitle("<br />[Aantal tweets]");

}

// Tweets per day
if ($_REQUEST['id']==1) {
    $qry->setQuery(sprintf("select date(created_at) as date
                                 , sum(case when quoted_status_id = 0 and coalesce(retweet_id,0) = 0 then 1 else 0 end) as num_tweets 
                                 , sum(case when quoted_status_id > 0 and coalesce(retweet_id,0) = 0 then 1 else 0 end) as num_replies
                                 , sum(coalesce(retweet_id,0) > 1)                                                      as num_retweets
                              from twitter 
                             where %s group by date(created_at) order by date(created_at)", $_SESSION['filter']->getFilter()));
    $qry->addColumn("date", "Datum", "string");
    $qry->addColumn("num_tweets","Tweets", "number");
    $qry->addColumn("num_replies","Replies", "number");
    $qry->addColumn("num_retweets","Retweets", "number");
    $qry->setTitle("Dagelijks twitter gebruik");
    $qry->setOption("isStacked", true);
    $qry->setOption("legend", array('position' => true));
    $qry->setSubTitle("<br />[Aantal tweets]");
    $qry->setEvent("alert(col_id+': '+data.getFormattedValue(row_num,0));");
    #$qry->setEvent("window.location = 'index2.php?page=99&val='+row_num;");
}

// Tweets per user_screen_name
else if ($_REQUEST['id']==2) {
    $qry->setQuery(sprintf("select user_screen_name, count(*) as num_tweets from twitter 
                     where %s group by user_screen_name order by count(*) desc limit 10", $_SESSION['filter']->getFilter()));
    $qry->addColumn("user_screen_name", "User", "string");
    $qry->addColumn("num_tweets", "Aantal tweets", "number");
    $qry->addColumn("num_tweets", "Aantal tweets", "string", "annotation");
    $qry->setTitle("Top 10 gebruikers");
    $qry->setSubTitle("<br />[Aantal tweets]");
    $qry->setChartType("BarChart");
    $qry->setEvent("showUserTweets(data.getValue(row_num,0));");
}

// Number of retweets
else if ($_REQUEST['id']==3) {
    $qry->setQuery(sprintf("select convert(t1.tweet_id, CHAR(18)) as tweet_id, t1.user_screen_name, t1.tweet_text, t2.num_retweets
                             from twitter t1
                                , (select t.retweet_id, count(*) as num_retweets 
                                     from twitter t
                                    where %s and t.retweet_id is not null 
                                    group by t.retweet_id 
                                    order by count(*) desc limit 10) t2
                             where t1.tweet_id = t2.retweet_id
                             ", $_SESSION['filter']->getFilter('t')));
    $qry->addColumn("tweet_id", "Gebruiker", "string",null, "user_screen_name");
    $qry->addColumn("num_retweets", "Aantal retweets", "number");
    $qry->addColumn("num_retweets", "Aantal retweets", "string","annotation");
    $qry->setTitle("Meeste reweets");
    $qry->setSubTitle("<br />[Aantal tweets]");
    $qry->setChartType("BarChart");
    $qry->setEvent("showTweet(data.getValue(row_num,0));");
}

// Conversations
else if ($_REQUEST['id']==4) {
    $qry->setQuery(sprintf("select convert(c.conversation_id, char(18)) as tweet_id
                                 , ct.user_screen_name
                                 , sum(case when t.retweet_id is     null then 1 else 0 end) as count_tweet
                                 , sum(case when t.retweet_id is not null then 1 else 0 end) as count_retweet
                                 , count(*) as num_tweets_in_conv 
                      from twitter_conversations c 
                     inner join twitter t on (c.tweet_id= t.tweet_id)
                     inner join twitter ct on (c.conversation_id = ct.tweet_id)
                     where %s 
                     group by convert(c.conversation_id, char(18)), ct.user_screen_name having count(*) > 1 order by count(*) desc limit 10",$_SESSION['filter']->getFilter("t")));
    $qry->addColumn("tweet_id", "Gebruiker", "string",null,"user_screen_name");
    $qry->addColumn("count_tweet", "Tweet", "number");
    $qry->addColumn("count_retweet", "Retweet", "number");
    $qry->addColumn("num_tweets_in_conv", "Aantal tweets in conversatie", "string","annotation");
    $qry->setTitle("Grootste conversaties");
    $qry->setSubTitle("<br />[Aantal tweets]");
    $qry->setChartType("BarChart");
    $qry->setOption("isStacked", true);
    $qry->setOption("legend", array('position' => true));
    #$qry->setEvent("showTweet(data.getValue(row_num,0));");
    $qry->setEvent("window.open('index2.php?page=3&conversation_id='+data.getValue(row_num,0),'_self');");
}
 
else if ($_REQUEST['id']==5) { 
    //$conversation_id = "813821140506472450";
    $conversation_id = $_REQUEST['conversation_id'];
    $qry->setQuery("SELECT t.tweet_id
                         , concat(t.user_screen_name, case when t.retweet_id is null then ''  else ' (RT)' end) as user_screen_name
                         , t.tweet_text
                         , coalesce(t.in_reply_to_status_id, NULLIF(t.quoted_status_id,0), t.retweet_id) as parent_tweet_id
                      FROM twitter t
                     INNER JOIN twitter_conversations c on (t.tweet_id = c.tweet_id)
                     where c.conversation_id = $conversation_id");
    $qry->addColumn("tweet_id","Tweet ID","string",null,"user_screen_name");
    $qry->addColumn("parent_tweet_id","Tweet ID","string");
    $qry->addColumn("user_screen_name","User screen name ID","string");
    $qry->setTitle("Conversatie boom");
    $qry->setChartType("OrgChart");
    $qry->setOption("allowCollapse", True);
    $qry->setOption("allowHtml", True);
    $qry->setEvent("showTweet(data.getValue(row_num,0));");
}
}  else {
    
   $valid_parameters  = array('tweet_id','user_screen_name');
   
    $parameters = array();
    foreach($_REQUEST as $key => $value) {
        if(in_array($key,$valid_parameters)) {
            if (is_string($value)) {
                $value = "\"$value\"";
            }
            $parameters[] = $key."=".$value;
        }
    }
    
    if(array_key_exists('tweet_id',$_REQUEST)) {
        $qry->setQuery(sprintf("SELECT t.tweet_id
                             , t.user_screen_name
                             , t.created_at
                             , t.tweet_text
                          FROM twitter t
                         where %s", implode(' AND ',$parameters)));
        $qry->addColumn("tweet_id","Tweet ID","string",null,'tweet_id');
        $qry->addColumn("user_screen_name","User screen name","string",null,'user_screen_name');
        $qry->addColumn("created_at","Created at","string");
        $qry->addColumn("tweet_text","Tweet text","string");
    }
    else if(array_key_exists('user_screen_name',$_REQUEST)) {
        $qry->setQuery(sprintf("SELECT t.tweet_id
                             , t.user_screen_name
                             , t.created_at
                             , t.tweet_text
                          FROM twitter t
                         where %s and %s", $_SESSION['filter']->getFilter('t'), implode(' AND ',$parameters)));
        $qry->addColumn("tweet_id","Tweet ID","string",null,'tweet_id');
        $qry->addColumn("user_screen_name","User screen name","string",null,'user_screen_name');
        $qry->addColumn("created_at","Created at","string");
        $qry->addColumn("tweet_text","Tweet text","string");
    }
}

$qry->runQuery($db);
echo $qry->returnJSON();
?>
