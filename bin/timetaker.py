#!/usr/bin/python
# -*- coding: utf-8 -*-

# TODO use correct logging

import argparse
from datetime import datetime, timezone
import time
import platform
import threading
import sqlite3
import sys

import urllib.request

from urllib.parse import urljoin, urlencode
from urllib.error import URLError
from urllib.error import HTTPError


class CmdReader(threading.Thread):
    def __init__(self, database, delta_time):
        threading.Thread.__init__(self)
        self.db = database
        self.delta_time = delta_time

    def run(self):
        while True:
            try:
                line = sys.stdin.readline()
                line = line.rstrip('\n')
            except KeyboardInterrupt:
                # e.g. when killing the script with Ctrl+C
                print("STOPPING... Please wait some seconds.")
                break
            if not line:
                # e.g. when killing the script with Ctrl+D
                print("STOPPING... Please wait some seconds.")
                break
            now = datetime.now()
            timestamp = (now - datetime(1970, 1, 1)).total_seconds() - delta_time
            # three levels of milliseconds is precise enough here
            self.db.store(line, '%.3f' % timestamp)

class TimeChecker:
    def __init__(self, server):
        self.server = server

    def get_delta(self, checks=1):
        # FIXME implementation missing
        print("ATTENTION: delta checking still missing!")
        # list of all calculated offsets
        # for each iteration:
            # take current time (1)
            # do a request to the server, asking for server time (2)
            # take current time (3)
            # calculate delta between (1) and (2) -> (4)
            # calculate delta between (1) and (3) -> (5)
            # subtract (5)/2 from (4) => offset
        # get the average of all offsets
        return 0;

class Writer(threading.Thread):
    def __init__(self, dbname='regatta.db', table_name='timings'):
        threading.Thread.__init__(self, daemon=True)
        self.dbname = dbname
        self.table_name = table_name

    def run(self):
        try:
            db = sqlite3.connect(self.dbname)
            cur = db.cursor()
            cur.execute('CREATE TABLE IF NOT EXISTS ' + self.table_name +
                        ' (id INTEGER PRIMARY KEY, token TEXT, time TEXT)')
            db.commit()
        except sqlite3.Error as e:
            print("Error %s:" % e.args[0])
        finally:
            if db:
                db.close()

    def store(self, token, time):
        try:
            db = sqlite3.connect(self.dbname)
            cur = db.cursor()
            cur.execute('INSERT INTO ' + self.table_name + ' (token, time) VALUES(?,?)', (token, time))
            db.commit()
        except sqlite3.Error as e:
            print("Error %s:" % e.args[0])
        finally:
            if db:
                db.close()

class Sender(threading.Thread):
    def __init__(self, db_name, table_name, server, checkpoint_name):
        threading.Thread.__init__(self, daemon=True)
        self.kill_event = threading.Event()

        self.dbname = db_name
        self.table = table_name
        self.checkpoint_name = checkpoint_name

        self.url = server + '/api/timing/checkpoint/'

    def run(self):
        # try to send the data to server in regular intervals
        self.restart_timer()

    def restart_timer(self):
        if not self.kill_event.is_set():
            threading.Timer(3.0, self.read_db).start()

    def read_db(self):
        try:
            db = sqlite3.connect(self.dbname)
            cur = db.cursor()
            cur.execute('SELECT * FROM ' + self.table);
            rows = cur.fetchall();
            for row in rows:
                if self.try_to_send_to_server(row):
                    self.remove_from_db(row, cur)
            db.commit()
        except sqlite3.Error as e:
            print("Error %s:" % e.args[0])
        finally:
            if db:
                db.close()
        self.restart_timer()

    def remove_from_db(self, db_tuple, db_cursor):
        id = db_tuple[0]
        # Unfortunately, this statement does not support named parameters
        # like SELECT, so we have to concatenate it by hand.
        db_cursor.execute('DELETE FROM ' + self.table + ' WHERE id=' + str(id))

    def try_to_send_to_server(self, db_tuple):
        reqBody = {'checkpoint': self.checkpoint_name, 'token': db_tuple[1], 'time': db_tuple[2]}
        data = urlencode(reqBody).encode('ascii')

        headers = {}
        headers['User-Agent'] = "Timetaker.py"
        headers['Content-type'] = "application/x-www-form-urlencoded"

        req = urllib.request.Request(self.url, data, headers)
        try:
            response = urllib.request.urlopen(req)
            # this means, we got OK (status 200), so the token was successfully
            # transmitted and can be deleted from the cache database
            return True
        except HTTPError as httperror:
            # Return values indicating errors (from API documentation):
            # BAD_REQUEST (status 400) if parameters missing
            # FORBIDDEN (status 403) if setting the time was not allowed
            # NOT_FOUND (status 404) if token was wrong
            if 400 == httperror.code:
                print("ATTENTION: parameters missing! Please get in touch with technical support!")
                return False # do not delete data from DB until code is fixed
            elif 403 == httperror.code:
                print("Athelete passed already this checkpoint! Deleting duplicate...")
                return True # delete duplicate
            elif 404 == httperror.code:
                print("ATTENTION: token in DB does not exist on server side!")
                return True # delete wrong data
            elif 500 == httperror.code:
                print("ATTENTION: internal server error")
                return False
            else:
                raise httperror
        except URLError as urlerror:
            # e.g. if the server is not reachable
            print("WARNING: cannot reach server! " + str(urlerror))
            return False # do not delete anything
#        except ValueError as valueerror:
#            # the given URL is not valid
#            raise valueerror

if __name__ == "__main__":
    # command line arguments and help
    parser = argparse.ArgumentParser(description='Time taker for the REGATTA program.')
    parser.add_argument('--server', required=True,
                        help='The base url of the regatta website. E.g.: "http://localhost"')
    parser.add_argument('--name',
                        help='Name of this checkpoint.')
    args = parser.parse_args()

    print("Running... Please use Ctrl+D for stopping the program")

    # get the name of this checkpoint
    checkpoint_name = args.name
    if None == checkpoint_name:
        # If no name was given, take the PC name. This allows to run the script
        # headless and get results even when called wrongly.
        # See <http://stackoverflow.com/questions/4271740/>.
        checkpoint_name = platform.node()

    # Make a time comparison between current system time and server
    # time to get the delta and allow correct timings.
    checker = TimeChecker(args.server)
    delta_time = checker.get_delta(checks=5)

    # create a data base access class
    db = Writer()
    db.start()

    # create a sender from DB to server
    sender = Sender(db.dbname, db.table_name, args.server, checkpoint_name)
    sender.start()

    # start listening to console
    reader = CmdReader(db, delta_time)
    reader.start()
    # read input as long as there is no kill event
    reader.join()
    # if reading is done, try to finish transmission and then stop
    sender.kill_event.set()
