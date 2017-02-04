import os
import platform

def creation_date(path_to_file):
    return int(creation_date2(path_to_file))

def creation_date2(path_to_file):
    """
    Try to get the date that a file was created, falling back to when it was
    last modified if that isn't possible.
    From http://stackoverflow.com/questions/237079
    """
    if platform.system() == 'Windows':
        return os.path.getctime(path_to_file)
    else:
        stat = os.stat(path_to_file)
        try:
            return stat.st_birthtime
        except AttributeError:
            # We're probably on Linux. No easy way to get creation dates here,
            # so we'll settle for when its content was last modified.
            return stat.st_mtime

def convert_time_in_seconds(t):
    time_splits = t.split(':')
    multiplier = 1
    sum_time = 0
    for tp in reversed(time_splits):
        sum_time = sum_time + (multiplier * float(tp))
        multiplier = multiplier * 60
    return sum_time
