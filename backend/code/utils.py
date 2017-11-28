#!/usr/bin/python -tt
import cgi

def isIterable(p_object):
    try:
        it = iter(p_object)
    except TypeError: 
        return False
    return True
    
def isDict(p_object):
    try:
        key = p_object.keys()
    except:
        return False
    return True
    
def getKeys(p_object,lvl):
    if isIterable(p_object):
        for key in p_object:
            if isDict(p_object[key]):
                getKeys(p_object[key],lvl+1)
            else:
                print ('-'*lvl) + str(key)

def unicodeToHTMLEntities(text):
    return cgi.escape(text).encode('ascii', 'xmlcharrefreplace')
    
def wordcount(w):
    return w[1]
    
def main():
    print "Do nothing"
    
# boilerplate
if __name__ == '__main__':
    main()