import unittest

from os.path import join

import results
import settings
from tests.test_utils.test_env_util import TestEnvironment
from utils.io import safe_write


class ResultsTest(unittest.TestCase):
    def setUp(self):
        self.test_env = TestEnvironment()

        file_result_git = join(self.test_env.RESULTS_PATH, 'git', settings.FILE_DETECTOR_RESULT)
        file_result_svn = join(self.test_env.RESULTS_PATH, 'svn', settings.FILE_DETECTOR_RESULT)
        create_result(file_result_git, '')
        create_result(file_result_svn, 'File: some-class.java')

    def test_correct_total(self):
        actual_results = results.evaluate_results()
        self.assertEquals(3, actual_results[0])

    def test_correct_applied(self):
        actual_results = results.evaluate_results()
        self.assertEquals(2, actual_results[1])

    def test_correct_found(self):
        actual_results = results.evaluate_results()
        self.assertEquals(1, actual_results[2])

    def tearDown(self):
        self.test_env.tearDown()


class PathNormalizationTest(unittest.TestCase):
    def setUp(self):
        settings.TEMP_SUBFOLDER = 'MUBenchmark'

    def test_normalize_git_path_from_data(self):
        non_normalized_path = r'src/main/java/com/alibaba/druid/filter/config/ConfigTools.java'
        expected_normalized_path = r'src\main\java\com\alibaba\druid\filter\config\ConfigTools.java'
        actual_normalized_path = results.normalize_data_misuse_path(non_normalized_path)
        self.assertEquals(expected_normalized_path, actual_normalized_path)

    def test_normalize_svn_path_from_data(self):
        non_normalized_path = r'incubator/jackrabbit/trunk/src/java/org/apache/jackrabbit/core/state/obj/ObjectPersistenceManager.java'
        expected_normalized_path = r'src\java\org\apache\jackrabbit\core\state\obj\ObjectPersistenceManager.java'
        actual_normalized_path = results.normalize_data_misuse_path(non_normalized_path)
        self.assertEquals(expected_normalized_path, actual_normalized_path)

    def test_normalize_synthetic_path_from_data(self):
        non_normalized_path = r'synthetic-androidactivity-1.java'
        expected_normalized_path = r'synthetic-androidactivity-1.java'
        actual_normalized_path = results.normalize_data_misuse_path(non_normalized_path)
        self.assertEquals(expected_normalized_path, actual_normalized_path)

    def test_normalize_git_path_from_result(self):
        non_normalized_path = r'C:\Users\m8is\AppData\Local\Temp\MUBenchmark\alibaba-druid\src\main\java\com\alibaba\druid\filter\config\ConfigTools.java'
        expected_normalized_path = r'src\main\java\com\alibaba\druid\filter\config\ConfigTools.java'
        actual_normalized_path = results.normalize_result_misuse_path(non_normalized_path)
        self.assertEquals(expected_normalized_path, actual_normalized_path)

    def test_normalize_svn_path_from_result(self):
        non_normalized_path = r'C:\Users\m8is\AppData\Local\Temp\MUBenchmark\jackrabbit\trunk\src\java\org\apache\jackrabbit\core\state\obj\ObjectPersistenceManager.java'
        expected_normalized_path = r'src\java\org\apache\jackrabbit\core\state\obj\ObjectPersistenceManager.java'
        actual_normalized_path = results.normalize_result_misuse_path(non_normalized_path)
        self.assertEquals(expected_normalized_path, actual_normalized_path)

    def test_normalize_synthetic_path_from_result(self):
        non_normalized_path = r'C:\Users\m8is\AppData\Local\Temp\MUBenchmark\synthetic-androidactivity-1\synthetic-androidactivity-1.java'
        expected_normalized_path = r'synthetic-androidactivity-1.java'
        actual_normalized_path = results.normalize_result_misuse_path(non_normalized_path)
        self.assertEquals(expected_normalized_path, actual_normalized_path)


def create_result(file_result, content):
    safe_write(content, file_result, append=False)


if __name__ == '__main__':
    unittest.main()
