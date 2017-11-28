#!/usr/bin/python -tt

import twitter
import datetime
import sys
import os.path
    
def main():
    twitter.initializeDB()
    if(len(sys.argv)==2):
        fname = '../data/zilverenkruis_'+sys.argv[1]+'.txt'
        if(os.path.isfile(fname)):
            tweets = twitter.readTweetsFromFile(fname)
            twitter.loadTweetsToDB(tweets)
        else:
            print fname, "does not exists"
    else:
        print "No file"
                
# boilerplate
if __name__ == '__main__':
    main()