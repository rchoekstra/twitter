#!/usr/bin/python -tt

##@package twitter
# Blablabla

import tweepy
import jsonpickle
import MySQLdb
import time
import datetime
#from utils import unicodeToHTMLEntities
#import os
#import sys

db = None
cur= None

class dict2obj:
    def __init__(self, **response):
        for k,v in response.items():
            if isinstance(v,dict):
                self.__dict__[k] = dict2obj(**v)
            else:
                self.__dict__[k] = v

# Load the config.json file and convert it to an object
config = dict2obj(**jsonpickle.decode(open('../../config.json','r').read()))
                
## Initialize the connection to the MySQL-database
#
# Blablabla
def initializeDB():

    global db
    global config
    db = MySQLdb.connect(host    = config.db.hostname,
                         user    = config.db.user,
                         passwd  = config.db.password,
                         db      = config.db.database,
                         charset = 'utf8')
             
    # init command werkt niet...
    global cur
    cur = db.cursor()
    cur.execute('SET NAMES utf8mb4')
    cur.execute('SET CHARACTER SET utf8mb4')
    cur.execute('SET character_set_connection=utf8mb4')


## Initialize Tweepy to Twitter
#
# Blablabla
def initializeTweepy():
    global auth
    global api
    global config
    consumer_key        = config.twitter.consumer_key
    consumer_secret     = config.twitter.consumer_secret
    access_token        = config.twitter.access_token
    access_token_secret = config.twitter.access_token_secret

    print "Authorizing Tweepy"
    auth = tweepy.AppAuthHandler(consumer_key, consumer_secret)
    api = tweepy.API(auth, wait_on_rate_limit=True,wait_on_rate_limit_notify=True)
                         

## Get the missing Tweet IDs from the database
#
# Tweets are part of a conversation. A tweet can be a retweet, quote or reply. It is not guaranteed that when a tweet is a rewtweet of reply
# that the original tweets also match to the search string. Therefore, tweets can be missing. This functions queries all the relevant tweet IDs
# that are not present in the database, but are referenced by other tweets.                         
def getMissingTweetIDsFromDB():
    cur.execute("SELECT tweet_id FROM vw_missing_tweets_2")
  
    tweet_ids = set()
  
    for row in cur.fetchall():
        tweet_ids.add(row[0])  
        
    return tweet_ids


## Get all the Tweet IDs that are present in de database
#
# @return: A set of Tweet IDs
def getTweetIDsFromDB():
    cur.execute("SELECT tweet_id FROM twitter")
    
    tweet_ids = set()
  
    for row in cur.fetchall():
        tweet_ids.add(row[0])  
        
    return tweet_ids

## Get Tweet IDs from external file
#
# The external file must have a separate Tweet ID on each line    
def getTweetIDsFromFile(filename):
    with open(filename,'r') as f:
        tweet_ids = list(set(f.read().splitlines()));
        tweet_ids = map(int, tweet_ids)
    
    # Make unique
    return set(tweet_ids)


## Download Tweets from list of Tweet IDs
#
# The Tweets IDs that are passed in the tweet_ids paramater will be downloaded in tranches of 100    
#
# @param tweet_ids: set of Tweet IDs
def downloadTweetsFromList(tweet_ids):
    tweet_ids   = list(tweet_ids)
    tweet_count = len(tweet_ids)

    try:
        for i in range((tweet_count / 100) + 1):
            end_loc = min((i+1)*100, tweet_count)
            print "Downloading " + str(i*100) + '-' + str(end_loc) + '/' + str(tweet_count)
            tweets = api._statuses_lookup(id=tweepy.utils.list_to_csv(tweet_ids[i * 100:end_loc]), tweet_mode='extended')
        return list(tweets)
    except tweepy.TweepError:
        print 'Something went wrong, quitting...'


## Download Tweets from Twitter with a search query
#
# @param searchQuery: Twitter search query
# @param maxTweets: Maximum number of Tweets to download
# @param tweetsPerQry: Tweets per query (max=100)
# @return: Returns a list of tweepy.models.Status instances
def downloadTweetsWithSearchQuery(searchQuery,maxTweets=10000000,tweetsPerQry=100):
    # maxTweets    = Some arbitrary large number
    # tweetsPerQry = this is the max the API permitsimpor
    
    # If results from a specific ID onwards are reqd, set since_id to that ID.
    # else default to no lower limit, go as far back as API allows
    sinceId = None

    # If results only below a specific ID are, set max_id to that ID.
    # else default to no upper limit, start from the most recent tweet matching the search query.
    max_id = -1L
    
    tweets = list()
    tweetCount = 0
    print("Downloading max {0} tweets".format(maxTweets))
    while tweetCount < maxTweets:
        try:
            if (max_id <= 0):
                if (not sinceId):
                    new_tweets = api.search(q=searchQuery, count=tweetsPerQry, tweet_mode='extended')
                else:
                    new_tweets = api.search(q=searchQuery, count=tweetsPerQry, tweet_mode='extended',
                                            since_id=sinceId)
            else:
                if (not sinceId):
                    new_tweets = api.search(q=searchQuery, count=tweetsPerQry, tweet_mode='extended',
                                            max_id=str(max_id - 1))
                else:
                    new_tweets = api.search(q=searchQuery, count=tweetsPerQry, tweet_mode='extended',
                                            max_id=str(max_id - 1),
                                            since_id=sinceId)
            if not new_tweets:
                print("No more tweets found")
                break
                
            tweets.extend(new_tweets)
            tweetCount += len(new_tweets)
            print("Downloaded {0} tweets".format(tweetCount))
            max_id = new_tweets[-1].id            
                
        except tweepy.TweepError as e:
            # Just exit if any error
            print("some error : " + str(e))
            break    

    return tweets

## Save tweets to file
#
# The tweets are saved as JSON with Jsonpickle
def saveTweetsToFile(tweets, filename):
    print 'Writing %s tweets to %s' % (len(tweets), filename)
    with open(filename,'a') as f:
        for tweet in tweets:
            f.write(jsonpickle.encode(tweet._json) + '\n')


## Read tweets from file
#
# Read a file with on each line a JSON-string containing the Tweet-data. The JSON will be decoded with Jsonpickle
def readTweetsFromFile(filename):
    tweets = list()
    print 'Reading tweets from ' + filename
    with open(filename, 'r') as f:
        for line in f:
            tweet = jsonpickle.decode(line)
            tweet['jsonpickle'] = line
            tweets.append(tweet)
            #tweet_object = dict2obj(**tweet_dict)
            #tweets.extend(tweet_object)
    
    return tweets
   
## Get Tweet IDs from Tweet
#
# This function returns a set which contains all the Tweet IDs.
def getTweetIDsFromTweets(tweets):
    tweet_ids = set()
    for tweet in tweets:
        tweet_ids.add(tweet.id)
    return tweet_ids
    
## Remove existing tweets
# 
# This functions checks whether the TweetID is already is in the database. If it is, the tweet is removed
# from the list
# @return: List of tweets that do not exist in the database 
def removeExistingTweets(tweets, existing_tweet_ids=None):
    if existing_tweet_ids==None :
        existing_tweet_ids = getTweetIDsFromDB()
        
    try:
        new_tweets = [tweet for tweet in tweets if tweet.id not in existing_tweet_ids]
    except:
        new_tweets = [tweet for tweet in tweets if tweet['id'] not in existing_tweet_ids]
    return new_tweets
    

## Insert Tweets into the database
#
# 
def loadTweetsToDB(tweets):
    #cur = db.cursor()

    ids = getTweetIDsFromDB()
    
    inserted_tweets = 0
    for t in tweets:
        if type(t) is dict:
            tweet = dict2obj(**t)
            tweet.created_at = datetime.datetime.strptime(tweet.created_at, '%a %b %d %H:%M:%S +0000 %Y')
        else:
            tweet = t
            
        if tweet.id in ids:
        #if 1 == 2:
            print tweet.id, "already in database"
        else:
            # datetime.datetime.strptime(tweet.created_at, '%a %b %d %H:%M:%S +0000 %Y').strftime('%Y-%m-%d %H:%M:%S')
            #ts = time.strftime('%Y-%m-%d %H:%M:%S', time.strptime(tweet.created_at,'%a %b %d %H:%M:%S +0000 %Y'))
            ts = tweet.created_at.strftime('%Y-%m-%d %H:%M:%S')
            try:
                quoted_status_id = tweet.quoted_status_id
            except:
                quoted_status_id = 0
                
            tweet_text = tweet.full_text;
            
            try:
                retweet_id = tweet.retweeted_status.id_str
            except:
                retweet_id = None
                
            in_reply_to_status_id = tweet.in_reply_to_status_id
            if tweet.in_reply_to_status_id==None:
                in_reply_to_status_id = None
            else:
                in_reply_to_status_id = tweet.in_reply_to_status_id

            try:
                json = tweet.jsonpickle
            except:
                json = jsonpickle.encode(tweet._json)
            
            cur.execute("INSERT INTO twitter (tweet_id, tweet_id_str, user_name, user_screen_name, created_at, quoted_status_id, tweet_text, tweet_json_blob, retweet_id, in_reply_to_status_id) VALUES (%s,%s,%s,%s,%s,%s,%s, %s,%s,%s)", (tweet.id, tweet.id_str, tweet.user.name, tweet.user.screen_name,ts, quoted_status_id, tweet_text, json, retweet_id, in_reply_to_status_id))
            db.commit()
            inserted_tweets += 1
            print tweet.id, "inserted, retweet_id", retweet_id
            
    print "%s/%s Tweets inserted into database" % (inserted_tweets, len(tweets))

    
## Create converations based on hierarchical informatie in Tweetss
#
# For each Tweet the parent Tweet is determined. The parent Tweet is based on the following fields (in the same order):
#
# - in_reply_to_status_id
# - quoted_status_id
# - retweet_id
#
# When the parent Tweet ID is determined, the Tweet that started the conversation (root Tweet) is searched for by looking at the parent of the parent
# until no parent is found anymore.
def createConversations():
    #cur = db.cursor()
    #cur.execute("SELECT tweet_id, in_reply_to_status_id, quoted_status_id, retweet_id FROM twitter LIMIT 10")
    cur.execute("SELECT tweet_id, coalesce(in_reply_to_status_id, NULLIF(quoted_status_id,0), retweet_id, tweet_id) as parent_tweet_id FROM twitter")

    rowcount = cur.rowcount
    print "Tweets downloaded: " + str(rowcount)

    tweets = dict()
    for row in cur.fetchall():
        tweets[row[0]] = row[1]

    i = 0
    for tweet in tweets:
        i += 1
        parent = tweets[tweet]
        
        while True:
            if parent in tweets:
                parent_of_parent = tweets[parent]
            else:
                parent_of_parent = parent

            if parent_of_parent == parent :
                break
            else:
                parent = parent_of_parent
                
        #cur.execute("INSERT INTO twitter_conversations(tweet_id, conversation_id) VALUES (%s, %s)" % (tweet, parent))
        
        #if i % 100 == 0:
            #print str(i) + ' / ' + str(rowcount)
            #db.commit()
            
    #db.commit()
    
    

## Boilerplate            
def main():
    initializeDB()
    #initializeTweepy()
    #missing_tweets = getMissingTweetIDsFromDB()
    #tweets = downloadTweetsFromList(missing_tweets)
    #getTweetIDsFromTweets(tweets)
    
    #initializeTweepy()
    #tweets = downloadTweetsFromList(tweet_ids)
    #saveTweetsToFile(tweets, 'data/test.restults.txt')

if __name__ == '__main__':
    main()
