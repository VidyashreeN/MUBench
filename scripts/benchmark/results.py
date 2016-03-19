from genericpath import isdir, exists
from os import listdir
from os.path import join, splitext
from os.path import normpath, basename

import datareader
import settings


def evaluate_single_result(file_data, data_content):
    def evaluate():
        file_result = join(dir_result, settings.FILE_DETECTOR_RESULT)
        print("Evaluating result {} against data {}".format(file_result, file_data))

        if exists(file_result):
            lines = [line.rstrip('\n') for line in open(file_result)]

            for line in lines:
                if line.startswith("File: "):
                    found_misuse = line.split(' ', 1)[1]

                    if settings.TEMP_SUBFOLDER + '\\' in found_misuse:
                        found_misuse = found_misuse.split(settings.TEMP_SUBFOLDER + '\\', 1)[1]

                    fix_filename = data_content["fix"]["files"][0]["name"]
                    print(fix_filename)
                    if 'trunk/' in fix_filename:
                        fix_filename = fix_filename.split('trunk/', 1)[1]

                    print("Comparing fixed file {} against file with misuse found {}".format(fix_filename, found_misuse))
                    if normpath(found_misuse) == normpath(fix_filename):
                        return 1

        return 0

    dirs_results = [join(settings.RESULTS_PATH, result_dir) for result_dir in listdir(settings.RESULTS_PATH) if
                    isdir(join(settings.RESULTS_PATH, result_dir))]
    for dir_result in dirs_results:
        is_result_for_file = splitext(basename(normpath(file_data)))[0] == basename(normpath(dir_result))
        if is_result_for_file:
            return evaluate()
    return None  # to indicate that no result was found for this data


def evaluate_results():
    results = [result for result in datareader.on_all_data_do(evaluate_single_result) if
               result is not None]
    write_results(results)


def write_results(results):
    count_success = sum(results)
    count_data = len(results)

    benchmark_result = "Found {} of {} misuses!".format(count_success, count_data)
    print(benchmark_result)
    with open(settings.BENCHMARK_RESULT_FILE, 'w+') as result_file:
        print(benchmark_result, file=result_file)

    return benchmark_result
