<?php

namespace GitlabChangelog;

class GitlabChangelog
{

    public $url;
    public $repo;
    public $token;
    public $debug = false;
    public $getLabels;

    public function __construct()
    {
        $this->getLabels = function ($issue) {
            return $issue->labels;
        };
    }

    private function get($arg)
    {
        $url = $this->url . 'api/v3/' . $arg;
        if ($this->debug) {
            echo $url . "\n";
        }
        if (strripos($url, '?') !== false) {
            $url .= '&';
        } else {
            $url .= '?';
        }
        $url .= 'private_token=' . $this->token;

        return json_decode(file_get_contents($url));
    }

    private function getRepo()
    {
        return array_pop(array_filter($this->get('projects'), function ($repo) {
            return $repo->path_with_namespace === $this->repo;
        }));
    }

    private function getIssues($repo)
    {
        $page = 1;
        $per_page = 100;
        $issues = [];
        while (true) {
            $next = $this->get('projects/' . $repo->id . '/issues?page=' . $page . '&per_page=' . $per_page);
            $count = count($next);
            $issues = array_merge($issues, $next);
            $page++;
            if ($count < $per_page) {
                break;
            }
        }

        return array_reverse(array_filter($issues, function ($issue) {
            return $issue->state === "closed" && isset($issue->milestone);
        }));
    }

    public function markdown()
    {
        $repo = $this->getRepo();
        $issues = $this->getIssues($repo);

        $markdown = function () use ($issues, $repo) {
            $count = array_map(function ($issue) use ($repo) {

                $labels = call_user_func($this->getLabels, $issue);
                $labels = implode(', ', $labels);
                $str = "- `$labels` [#$issue->id]";
                $str .= "(" . $this->url . $repo->path_with_namespace . "/issues/" . $issue->id . ") ";
                $str .= $issue->title;
                $str .= "\n";

                return [
                    'text' => $str,
                    'date' => date('Y-m-d', strtotime($issue->updated_at)),
                    'title' => $issue->milestone->title
                ];
            }, $issues);

            $dates = [];
            foreach ($count as $date) {
                $dates[$date['date']][] = $date;
            }

            krsort($dates);

            $text = '';

            foreach ($dates as $date => $items) {
                $texts = '';
                foreach ($items as $item) {
                    $texts .= $item['text'];
                }
                $text .= "## " . $item['title'] . " - _" . $date . "_\n" . $texts . "\n\n";
            }

            return $text;
        };
        $result = "# Changelog\n\n" . $markdown();

        return $result;
    }
}

?>
