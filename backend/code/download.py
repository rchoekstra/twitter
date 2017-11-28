#!/usr/bin/python -tt

# Initialize 
import os
print os.getcwd()
## Set working directory (necessary for crontab)
abspath = os.path.abspath(__file__)
dname   = os.path.dirname(abspath)
os.chdir(dname)
print os.getcwd()

# Imports
import twitter
import datetime


## database and Tweepy
twitter.initializeDB()
twitter.initializeTweepy()

## Some variables
searchQuery = 'zilverenkruis%20OR%20"zilveren%20OR%20kruis"%20OR%20%40zilverenkruis%20OR%20%23zilverenkruis%20OR%20zilveren%2Bkruis'
timestamp   = datetime.datetime.today().strftime('%Y%m%d-%H%M%S')

# Download Tweets
tweets     = twitter.downloadTweetsWithSearchQuery(searchQuery)

# Keep only new Tweets
existing_tweet_ids = twitter.getTweetIDsFromDB()
new_tweets = twitter.removeExistingTweets(tweets, existing_tweet_ids)
print "%s/%s new Tweets" % (len(new_tweets), len(tweets))

# Missing tweets
new_tweet_ids = twitter.getTweetIDsFromTweets(new_tweets)
referenced_tweet_ids = set()
for tweet in tweets:
    try:
        referenced_tweet_ids.add(tweet.retweeted_status.id)
    except:
        pass

    try:
        referenced_tweet_ids.add(tweet.quoted_status_id)
    except:
        pass

    if tweet.in_reply_to_status_id != None:
        referenced_tweet_ids.add(tweet.in_reply_to_status_id)

all_tweet_ids     = existing_tweet_ids.union(new_tweet_ids)
missing_tweet_ids = [id for id in referenced_tweet_ids if id not in all_tweet_ids]
missing_tweets    = twitter.downloadTweetsFromList(missing_tweet_ids)
lost_tweet_ids    = set(missing_tweet_ids) - twitter.getTweetIDsFromTweets(missing_tweets)
print "%s/%s Missing Tweets downloaded" % (len(missing_tweets), len(missing_tweet_ids))

all_new_tweets    = new_tweets + missing_tweets

# Save Tweets to txt file
filename = '../data/zilverenkruis_'+timestamp+'.txt'
twitter.saveTweetsToFile(all_new_tweets,filename)

# Save Tweets in database
twitter.loadTweetsToDB(all_new_tweets)
