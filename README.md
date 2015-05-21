# gitlab-changelog

A php script to generate changelog via gitlab api v3

## Usage

composer.json:

```json
{
    "require": {
        "subdee/gitlab-changelog": "0.0.1"
    }
}
```

index.php:

```php
<?php
require "vendor/autoload.php";

use GitlabChangelog\GitlabChangelog;

$changelog = new GitlabChangelog();
$changelog->url = "GITLAB URL";
$changelog->repo = "REPO NAME";
$changelog->token = "YOUR PRIVATE TOKEN";

$changelog->getLabels = function($issue) {
    $label = "Fixed";
    $map = array(
        "bug" => "Fixed",
        "enhancement" => "Improved",
        "feature" => "Added"
    );
    foreach($map as $k => $v) {
        if(strripos(implode(',', $issue->labels), $k) !== FALSE) {
            $label = $v;
            break;
        }
    }
    return array($label);
};

$markdown = $changelog->markdown();

file_put_contents("changelog.md", $markdown);
```

Run:
```shell
composer install
php index.php
```
