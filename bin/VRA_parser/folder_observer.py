import getopt
import os
import sys
import time

# my libs
import vra_helper
import vra_parser

def observe(paths, output):
    relevant_file_endings = {'results': 'Results.txt', 'splits': 'Splits Data.txt'}
    last_run = 0
    
    while True:
        try:
            to_process = []
            relevant_files = {}
            for path in paths:
                for fname in os.listdir(path):
                    # collect all potential candidates
                    key = ''
                    data_type = ''
                    for k in relevant_file_endings:
                        v = relevant_file_endings[k]
                        if fname.endswith(v):
                            key = vra_parser.get_race_name(fname, v)
                            data_type = k
                            break
                    if key is not '':
                        if key not in relevant_files:
                            relevant_files[key] = []
                        relevant_files[key].append((data_type, os.path.join(path, fname)))
                    # collect all where processing is needed
                    if fname.endswith(relevant_file_endings['results']):
                        last_mod_date = vra_helper.creation_date(os.path.join(path, fname))
                        if last_mod_date > last_run:
                            to_process.append(key)
            # processing
            for key in to_process:
                print('processing:', key)
                process(relevant_files[key], output)
            # next run
            last_run = time.time()
            time.sleep(5) # delays for 5 seconds
        except KeyboardInterrupt:
            print('stopped!')
            sys.exit()

def process(data, output_path):
    f_result = None
    f_split = None
    for x,y in data:
        if 'results' == x:
            f_result = y
        elif 'splits' == x:
            f_split = y
    if f_result is None or f_split is None:
        print("not enough data in", data)
        return
    vra_parser.process_race_result(f_result, f_split, output_path)

def main(argv):
    def show_help():
        print(argv[0], ' -p <path to observe> [-p <path to observe>,[-p ...]] -o <path to store output>')
    if len(argv) == 1:
        show_help()
    try:
        opts, args = getopt.getopt(argv[1:],"hp:o:",["path=", "output_path="])
    except getopt.GetoptError:
        show_help()
        sys.exit(2)
    # lookup parameters
    paths_to_observe = []
    output_path = ''
    for opt, arg in opts:
        if opt == '-h':
            show_help()
            sys.exit()
        elif opt in ("-p", "--path"):
            paths_to_observe.append(arg.strip())
        elif opt in ("-o", "--output_path"):
            output_path = arg.strip()
    if 0 == len(paths_to_observe) or '' == output_path:
        show_help()
        sys.exit(2)
    for path in paths_to_observe:
        if not os.path.exists(path) or not os.path.isdir(path):
            print("given path is not an existing directory:", path)
            show_help()
            sys.exit(2)
    for path in paths_to_observe:
        print("observing:", path)
    observe(paths_to_observe, output_path)

if __name__ == "__main__":
    main(sys.argv)
