import csv
import datetime
import json
import os
# my lib
import vra_helper

def get_race_name(f, rest):
    _, f_name = os.path.split(f)
    return f_name.replace(rest, "").strip()

def read_split_data(f):
    """
    Read the content of a Concept2 VRA split data file.
    """
    result = {}
    with open(f) as csvfile:
        reader = csv.DictReader(csvfile)
        competitor = ""
        for row in reader:
            #{'Split_Stroke_Rate': '22',
            # 'Split_Heart_Rate': '0',
            # 'Interval': '',
            # 'Time': '125.5',
            # 'Boat': '',
            # 'Split_Avg_Pace': '125.5',
            # 'Meters': '1000.0'}
            if row['Boat'] != "":
                competitor = row['Boat']
                result[competitor] = []
            split_time = float(row['Time'])
            split_meters = float(row['Meters'])
            result[competitor].append({'split_time':split_time, 'split_meters':split_meters})

    return result

def read_result_data(f):
    """
    Read the content of a Concept2 VRA race result file.
    """
    sep = ','
    is_headline = True
    result = []

    for line in open(f):
        if line.strip().startswith('Detailed'):
            # here starts the next table in this file, so stop
            break
        if ',' not in line:
            continue
        # work with line
        if is_headline:
            is_headline = False
            headline = line.split(sep)
        else:
            curr_line = line.split(sep)
            line_dict = {}
            for i in range(len(headline)):
                line_dict[headline[i].strip('\n\r')] = curr_line[i].strip('\n\r')
            result.append(line_dict)
    return result

class Race(object):
    def __init__(self, end_timestamp, results, splits):
        self.end = end_timestamp
        self.start = int(self.end - self.calculate_start_offset(results))
        self.classes = self.merge(results, splits)

    def calculate_start_offset(self, data):
        """
        Get the longest time in the result data set (as FLOAT)
        """
        longest = 0
        for x in data:
            time_rowed = x['Time Rowed']
            sum_time = vra_helper.convert_time_in_seconds(time_rowed)
            if sum_time > longest:
                longest = sum_time
        return longest

    def merge(self, r, s):
        result = {}
        for x in r:
            starter = Starter(x)
            clazz = x['Class']
            if clazz not in result:
                result[clazz] = []
            result[clazz].append(starter)
            for n in s:
                if starter.name == n:
                    starter.add_splits(s[n])
        return result

    def toJSON(self):
        return json.dumps(self, default=lambda o: o.__dict__, sort_keys=True, indent=4)

class Starter(object):
    def __init__(self, race_result_dataset):
        self.name = race_result_dataset['Boat/Team Name']
        self.id = int(race_result_dataset['Bib Number'])
        self.place = int(race_result_dataset['Place'])
        self.final_time = race_result_dataset['Time Rowed'].strip()
        self.final_time_sec = vra_helper.convert_time_in_seconds(self.final_time)
        self.avg_pace = race_result_dataset['Avg. Pace'].strip()
        self.avg_pace_sec = vra_helper.convert_time_in_seconds(self.avg_pace)
        self.meters_rowed = int(race_result_dataset['Meters Rowed'])
        self.splits = []

    def add_splits(self, splits):
        # order splits by meters
        xs = {}
        for s in splits:
            xs[s['split_meters']] = s['split_time']
        total = 0.0
        for k in sorted(list(xs.keys())):
            total = total + xs[k]
            split = {
                'meters': k,
                'split_time': xs[k],
                'total_time': total
            }
            self.splits.append(split)

def process_race_result(f_result, f_split, output_path):
    race_name = get_race_name(f_result, 'Results.txt')
    last_mod_date = vra_helper.creation_date(f_result)
    output_fname = datetime.datetime.fromtimestamp(last_mod_date).strftime('%Y%m%d_%H%M_') + race_name + '.rac_result.txt'

    results = read_result_data(f_result)
    splits = read_split_data(f_split)

    r = Race(last_mod_date, results, splits)
    my_json = r.toJSON()

    output_fpath = os.path.join(output_path, output_fname)
    with open(output_fpath, 'w+') as text_file:
        print(my_json, file=text_file)
