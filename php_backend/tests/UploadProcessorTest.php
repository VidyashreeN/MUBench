<?php
require_once 'src/ConnectionDB.php';
require_once 'src/UploadProcessor.php';

use PHPUnit\Framework\TestCase;

class UploadProcessorTest extends TestCase
{
    protected $obj;
    protected $proc;
    protected $db;

    public function getConnection()
    {
        $pdo = new PDO('sqlite::memory:');
        return $pdo;
    }

    protected function setUp()
    {
        $this->db = new DBConnection($this->getConnection(), new \Monolog\Logger("test"));
        $statements = [];
        $statements[] =
            "CREATE TABLE stats (id TEXT NOT NULL, result TEXT NOT NULL, runtime TEXT NOT NULL, number_of_findings TEXT NOT NULL, table_id TEXT NOT NULL, exp TEXT NOT NULL, project TEXT NOT NULL, version TEXT NOT NULL);";
        $statements[] =
            "CREATE TABLE metadata (misuse TEXT NOT NULL,description TEXT NOT NULL,fix_description TEXT NOT NULL,violation_types TEXT NOT NULL,file TEXT NOT NULL,method TEXT NOT NULL,diff_url TEXT NOT NULL);";
        $statements[] =
            "CREATE TABLE patterns (misuse TEXT NOT NULL,name TEXT NOT NULL,code TEXT NOT NULL,line TEXT NOT NULL);";
        $statements[] =
           "CREATE TABLE reviews (exp VARCHAR(100) NOT NULL,detector VARCHAR(100) NOT NULL,project VARCHAR(100) NOT NULL,version VARCHAR(100) NOT NULL,misuse VARCHAR(100) NOT NULL,name TEXT NOT NULL,comment TEXT NOT NULL,id int AUTO_INCREMENT,PRIMARY KEY(id));";
        $statements[] =
            "CREATE TABLE detectors (id int AUTO_INCREMENT, name TEXT NOT NULL);";
        $this->db->execStatements($statements);
        $this->obj =
            json_decode('{"findings":[{"target_snippets":[{"first_line_number":0, "code":"c"}],"a":"1", "b":"2", "c":"3", "rank":"4", "misuse":"5"}], "project":"p", "version":"v", "runtime":"0", "result":"success", "number_of_findings":"1", "dataset":"any", "detector":"mudetect"}');
        $this->metaObj =
            json_decode('{"misuse":"m.1","project":"p1", "version":"v1", "fix":{"diff-url":"url", "description":"desc"}, "description":"d", "violation_types":["1","2"], "location":{"file":"f", "method":"m"}, "patterns":[{"id":"1","snippet":{"code":"c", "first_line":0}}]}');
        $this->reviewObj = array("review_name" => "admin", "review_identifier" => "id", "review_comment" => "test",
            "review_hit" => array(0 => array("hit" => "Yes", "types" => ["1", "2"])), "review_exp" => "ex1", "review_detector" => "detector1", "review_project" => "project1", "review_version" => "test-version", "review_misuse" => "test-misuse");
        $this->proc = new UploadProcessor($this->db,
            new Monolog\Logger('test'));
    }

    public function testgetJsonNames()
    {
        $actual = $this->proc->getJsonNames($this->obj->{'findings'});
        $expected = array('project', 'version', 'target_snippets', 'a', 'b', 'c', 'rank', 'misuse');
        $this->assertEquals($expected, $actual);
    }

    public function testarrayToString()
    {
        $actual = $this->proc->arrayToString(json_decode('{"violation_type":["first", "second"]}')->{'violation_type'});
        $expected = "first;second";
        $this->assertEquals($expected, $actual);
    }

    public function testRearrangeCodeSnippets()
    {
        $actual = $this->proc->rearrangeCodeSnippets($this->obj->{'findings'});
        $this->assertEquals(0, $actual[0]->{'line'});
        $this->assertEquals("c", $actual[0]->{'target_snippets'});
    }

    public function testHandleFindings()
    {
        $table = "testTable";
        $project = "p";
        $version = "v";
        $exp = "ex1";
        $findings = $this->proc->rearrangeCodeSnippets($this->obj->{'findings'});
        $this->proc->handleTableColumns($table, $this->proc->getJsonNames($findings),
            array(), $findings);
        $this->proc->handleFindings($table, $exp ,$project, $version, $findings);
        $query = $this->db->getPotentialHits($table, $exp, $project, $version);
        $this->assertTrue(count($query) == 1);
        $this->assertEquals("c", $query[0]['target_snippets']);
    }

    public function testHandleStats()
    {
        $this->proc->handleStats("table", "p", "v", "success", "0", "10", "exp1");
        $query = $this->db->getAllStats("table");
        $this->assertTrue(count($query) == 1);
    }

    public function testHandleMetadata()
    {
        $this->proc->processMetaData($this->metaObj);
        $query = $this->db->getMetadata("p1", "v1", "m.1");
        $pattern = $this->db->getPattern("m.1");
        $this->assertTrue(count($query) == 1);
        $this->assertTrue(count($pattern) == 1);
    }

    public function testProcessReview()
    {
        $this->proc->processReview($this->reviewObj);
        $query = $this->db->getReview($this->reviewObj['review_exp'], $this->reviewObj['review_detector'], $this->reviewObj['review_project'], $this->reviewObj['review_version'], $this->reviewObj['review_misuse'], $this->reviewObj['review_name']);
        $this->assertEquals($this->reviewObj['review_detector'], $query['detector']);
    }

}