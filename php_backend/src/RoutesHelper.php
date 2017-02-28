<?php

require_once "DBConnection.php";

use Monolog\Logger;
use MuBench\Detector;
use MuBench\DetectorResult;
use MuBench\ExperimentResult;
use MuBench\ReviewState;

class RoutesHelper
{

    protected $logger;
    protected $settings;
    protected $root_url;
    protected $base_url;
    protected $private_url;
    protected $db;
    protected $user;
    protected $route;

    public function __construct(Logger $logger, $settings, DBConnection $db)
    {
        $this->logger = $logger;
        $this->db = $db;
        $this->settings = $settings;
        $this->root_url = $settings['root_url'];
        $this->base_url = $settings['root_url'] . "index.php/";
        $this->private_url = $this->base_url . "private/";
        $this->user = "";
        $this->route = "";
    }

    public function index_route($args, $r, $response, $logged)
    {
        return $this->render($r, $args, $response, 'index.phtml',
            array('experiments' => $this->settings['ex_template'], "logged" => $logged));
    }

    public function experiment_route($args, $r, $response, $logged)
    {
        $exp = $args['exp'];
        $detectors = $this->db->getDetectors($exp);
        $template = $this->settings['ex_template'][$exp];
        return $this->render($r, $args, $response, 'experiment.phtml',
            array('detectors' => $detectors, 'id' => $template['id'], 'title' => $template['title'], 'exp' => $exp,
                'logged' => $logged));
    }

    public function overview_route($args, $r, $response)
    {
        $misuses = $this->db->getAllReviews($this->user);
        return $this->render($r, $args, $response, 'overview.phtml',
            array("name" => $this->user, "misuses" => $misuses, "logged" => true));
    }

    public function todo_route($args, $r, $response)
    {
        $misuses = $this->db->getTodo($this->user);
        return $this->render($r, $args, $response, 'todo.phtml', array("logged" => true, "misuses" => $misuses));
    }

    public function detect_route($args, $r, $response, $logged)
    {
        $exp = $args['exp'];
        $detector = $args['detector'];
        if (!($exp === "ex1" || $exp === "ex2" || $exp === "ex3") || $detector == "") {
            return $response->withStatus(404);
        }
        $name = "";
        if ($logged) {
            $name = $this->user;
        }

        $det = $this->db->getDetector($detector);
        $runs = $this->db->getRuns($det, $exp);

        return $this->render($r, $args, $response, 'detector.phtml',
            array('logged' => $logged, 'name' => $name,
                'exp' => $exp, 'detector' => $detector, 'runs' => $runs));
    }

    public function review_route($args, $r, $response, $logged, $review_flag)
    {
        $exp = $args['exp'];
        $detector = $args['detector'];
        $project = $args['project'];
        $version = $args['version'];
        $misuse = $args['misuse'];

        $det = $this->db->getDetector($detector);
        $misuse = $this->db->getMisuse($exp, $det, $project, $version, $misuse);

        $reviewer = "";
        if ($review_flag && !$logged) {
            $reviewer = $args['reviewer'];
            $logged = (strcmp($this->user, $reviewer) === 0);
        } else if ($review_flag && $logged) {
            $reviewer = $this->user;
        }
        return $this->render($r, $args, $response, 'review.phtml',
            array('logged' => $logged, 'name' => $reviewer, 'exp' => $exp, 'detector' => $detector,
                'misuse' => $misuse, 'review' => $misuse->getReview($reviewer)));
    }

    public function stats_route($handler, $response, $args, $ex2_review_size) {
        $results = array();
        foreach (array("ex1", "ex2", "ex3") as $experiment) {
            $detectors = $this->db->getDetectors($experiment);
            $results[$experiment] = array();
            foreach ($detectors as $detector) {
                $runs = $this->db->getRuns($detector, $experiment);
                // TODO move this functionality to dedicate experiment classes
                if (strcmp($experiment, "ex2") === 0) {
                    foreach ($runs as &$run) {
                        $misuses = array();
                        $number_of_misuses = 0;
                        foreach ($run["misuses"] as $misuse) { /** @var $misuse \MuBench\Misuse */
                            if ($misuse->getReviewState() != ReviewState::UNRESOLVED) {
                                $misuses[] = $misuse;
                                $number_of_misuses++;
                            }

                            if ($number_of_misuses == $ex2_review_size) {
                                break;
                            }
                        }
                        $run["misuses"] = $misuses;
                    }
                }
                $results[$experiment][$detector->id] = new DetectorResult($detector, $runs);
            }
            $results[$experiment]["total"] = new ExperimentResult($results[$experiment]);
        }

        return $this->render($handler, $args, $response, 'stats.phtml', array(
            'results' => $results,
            'ex2_review_size' => $ex2_review_size)
        );
    }

    private function render($r, $args, $response, $template, $params)
    {
        $params["root_url"] = htmlspecialchars($this->root_url);
        $params["base_url"] = htmlspecialchars($this->base_url);
        $params["private_url"] = htmlspecialchars($this->private_url);
        $params["user"] = $this->user;
        // TODO add auth information here as well
        $params["route_url"] = htmlspecialchars($this->route);
        $params["experiment"] = array_key_exists("exp", $args) ? $args["exp"] : null;
        $params["detector"] = array_key_exists("detector", $args) ? $args["detector"] : null;
        return $r->renderer->render($response, $template, $params);
    }

    public function setAuth($user){
        $this->user = $user;
    }

    public function setRoute($route){
        $this->route = $route;
        $this->logger->info("ROUTE: " . $route);
    }
}
