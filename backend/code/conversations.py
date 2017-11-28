#!/usr/bin/python -tt
import MySQLdb
import os
import jsonpickle

## Set working directory (necessary for crontab)
abspath = os.path.abspath(__file__)
dname   = os.path.dirname(abspath)
os.chdir(dname)
os.getcwd()

class dict2obj:
    def __init__(self, **response):
        for k,v in response.items():
            if isinstance(v,dict):
                self.__dict__[k] = dict2obj(**v)
            else:
                self.__dict__[k] = v

# Load the config.json file and convert it to an object
config = dict2obj(**jsonpickle.decode(open('../../config.json','r').read()))

  
db = MySQLdb.connect(host    = config.db.hostname,
                     user    = config.db.user,
                     passwd  = config.db.password,
                     db      = config.db.database,
                     charset = 'utf8')
                     
cur = db.cursor()
cur.execute("TRUNCATE TABLE twitter_conversations")
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
            
    cur.execute("INSERT INTO twitter_conversations(tweet_id, conversation_id) VALUES (%s, %s)" % (tweet, parent))
    
    if i % 100 == 0:
        print str(i) + ' / ' + str(rowcount)
        db.commit()
        
db.commit()
        
    
