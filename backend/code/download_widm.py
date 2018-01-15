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
twitter.initializeTweepy()

## Some variables
searchQuery = '%40widmnl%20OR%20%40IkBenDeMol%20OR%20%23widm%20OR%20%23moltalk'
timestamp   = datetime.datetime.today().strftime('%Y%m%d-%H%M%S')

with open('../data/widm_tweetids.txt','r') as f:
    existing_tweet_ids = f.readlines()
    
existing_tweet_ids = [long(tweet_id.strip()) for tweet_id in existing_tweet_ids]
    
# Download Tweets
tweets     = twitter.downloadTweetsWithSearchQuery(searchQuery, sinceId=max(existing_tweet_ids))
tweet_ids  = [tweet.id for tweet in tweets]

# Keep only new Tweets    
new_tweets = twitter.removeExistingTweets(tweets, existing_tweet_ids)
print "%s/%s new Tweets" % (len(new_tweets), len(tweets))

#Save (append) tweet ids;
with open('../data/widm_tweetids.txt','a') as f:
    for new_tweet in new_tweets:
        f.write(str(new_tweet.id) + "\n")

# Save Tweets to txt file
filename = '../data/widm_'+timestamp+'.txt'
twitter.saveTweetsToFile(new_tweets,filename)
