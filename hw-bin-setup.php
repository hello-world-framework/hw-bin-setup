<?php declare(strict_types=1);


$hwBinSetupMeta = [
    "name" => "hw-bin-setup",
    "version" => "v0.1.0"
];


// utility functions
function isDirectory($dir) {
    return file_exists($dir) && is_dir($dir);
}

function isFile($file) {
    return file_exists($file) && !is_dir($file);
}

function makeDirectory($dir) {
    if(!file_exists($dir) || !is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
}

function copyDirectoryRecursively($src, $dst) {
    $errCnt = 0;
    if(isDirectory($src)) {
        makeDirectory($dst);
        $srcDir = opendir($src);
        while(($obj = readdir($srcDir)) !== false) {
            if($obj != "." && $obj != "..") {
                $file = $src . "/" . $obj;
                if(is_dir($file) && !is_link($file)) {
                    $errCnt += copyDirectoryRecursively($file, $dst . "/" . $obj);
                } else {
                    $trg = $dst . "/" . $obj;
                    echo "copying: \"" . $obj . "\"\n";
                    echo "  - from: \"" . $file . "\"\n";
                    echo "  - to: \"" . $trg. "\"\n";
                    if(copy($file, $trg)) {
                        echo "  - status: success\n";
                    } else {
                        echo "  - status: error\n";
                        echo "    - message: file not copied\n";
                        $errCnt++;
                    }
                }
            }
        }
        closedir($srcDir);
    }
    return $errCnt;
}

function removeDirectoryRecursively($dir) {
    $errCnt = 0;
    if(isDirectory($dir)) {
        $objects = scandir($dir);
        foreach($objects as $object) { 
            if ($object != "." && $object != "..") { 
                if(is_dir($dir . "/" . $object) && !is_link($dir . "/" . $object)) {
                    $errCnt += removeDirectoryRecursively($dir . "/" . $object);
                } else {
                    if(unlink($dir . "/" . $object)) {
                        echo "- successfully deleted file: \"" . $dir . "/" . $object . "\"\n";
                    } else {
                        echo "- couldn't delete file: \"" . $dir . "/" . $object . "\"\n";
                        $errCnt++;
                    }
                }
            }
        }
        if(rmdir($dir)) {
            echo "- successfully deleted directory: \"" . $dir . "\"\n";
        } else {
            echo "- couldn't delete directory \"" . $dir . "\"\n";
            $errCnt++;
        }
    }
}

// provided a uncleaned tagList(which is json_decoded(returned by github api) php array)
// a clean tag only list is returned
// example:
// for input:
// (
//     [0] => Array
//         (
//             [ref] => refs/tags/v0.1.0
//             [node_id] => MDM6UmVmMzEzMDI3NjI3OnJlZnMvdGFncy92MC4xLjA=
//             [url] => https://api.github.com/repos/hello-world-framework/routing-engine/git/refs/tags/v0.1.0
//             [object] => Array
//                 (
//                     [sha] => aad3ca5013245133907ce8b6b431a9817b18e85e
//                     [type] => tag
//                     [url] => https://api.github.com/repos/hello-world-framework/routing-engine/git/tags/aad3ca5013245133907ce8b6b431a9817b18e85e
//                 )

//         )
//     [1] => Array
//         (
//             [ref] => refs/tags/v0.1.1
//             [node_id] => MDM6UmVmMzEzMDI3NjI3OnJlZnMvdGFncy92MC4xLjE=
//             [url] => https://api.github.com/repos/hello-world-framework/routing-engine/git/refs/tags/v0.1.1
//             [object] => Array
//                 (
//                     [sha] => 32cdc75e7c7c4fc5292d443764a9539660e26154
//                     [type] => tag
//                     [url] => https://api.github.com/repos/hello-world-framework/routing-engine/git/tags/32cdc75e7c7c4fc5292d443764a9539660e26154
//                 )
//         )
// )
// output is:
// ["v0.1.0", "v0.1.1"]
function getCleanTags($tagList) {
    $cleanTags = [];
    foreach($tagList as $tagData) {
        $ref = $tagData["ref"];
        $tmp = explode("/", $ref);
        $cleanTag = $tmp[2];
        $cleanTags[] = $cleanTag;
    }
    return $cleanTags;
}

// split a tag (ex: "v1.0.1" as [1, 0, 1])
function splitTag($tag) {
    $tmp = explode(".", $tag);
    $tmp[0] = substr($tmp[0], 1);
    return [
        (int)$tmp[0],
        (int)$tmp[1],
        (int)$tmp[2]
    ];
}

// check in the tags i.e ["v1.0.1", "v0.1.0", ...] if a tag exists
function checkTagExists($tags, $keyTag) {
    foreach($tags as $tag) {
        if($tag === $keyTag) {
            return true;
        }
    }
    return false;
}

// iterate over tags i.e. ["v1.0.1", "v0.1.0", ...] and returns the maximum of them
function findLatestTag($tags) {
    $splitTags = [];
    foreach($tags as $tag) {
        $splitTags[] = splitTag($tag);
    }
    usort($splitTags, function($x, $y) {
        $diff = 0;
        for($i=0; $i<3; $i++) {
            $diff = $y[$i] - $x[$i];
            if($diff !== 0) {
                return $diff;
            }
        }
        return 0;
    });
    return "v" . $splitTags[0][0] . "." . $splitTags[0][1] . "." . $splitTags[0][2];
}

// download hello-world-framework/bin 's version provided by $tag argument
// and returns the downloaded file
function downloadBin($tag) {
    echo "> downloading hello-world-framework/bin, version: " . $tag . "...\n";
    $url = "https://github.com/hello-world-framework/bin/archive/" . $tag . ".tar.gz";
    try {
        $bin = file_get_contents($url);
    } catch(\Exception $e) {
        echo $e->getMessage();
        echo "\n";
    }
    echo "downloaded hello-world-framework/bin, version: " . $tag . " successfully...\n";
    return $bin;
}

// extracts $bin to necessary directory
// also deletes .tar.gz and .tar files those were created
// for short time for extracting purposes
function extractBinTo($bin, $tmpDir) {
    $tarGzFile = $tmpDir . "/" . "bin.tar.gz";
    $tarFile = $tmpDir . "/" . "bin.tar";
    $extractTo = $tmpDir;
    try {
        file_put_contents(
            $tarGzFile,
            $bin
        );

        $tar_gz = new PharData($tarGzFile);
        $tar_gz->decompress();

        $tar = new PharData($tarFile);
        $tar->extractTo($extractTo);

        unlink($tarGzFile);
        unlink($tarFile);
    } catch(\Exception $e) {
        echo $e->getMessage();
        echo "\n";
    }
}

// downloads(with the help of downloadBin($tag) function),
// extracts and
// install hello-world-framework/bin in <__DIR__ . "/hw"> directory
// also clears all files and directories those were created only for short time
// to help in the process of installation
function installBin($tag) {
    $tmpDir = __DIR__ . "/__hw_tmp__";
    makeDirectory($tmpDir);
    extractBinTo(downloadBin($tag), $tmpDir);
    
    echo "> installing hello-world-framework/bin...\n";
    $binDir = $tmpDir . "/bin-" . substr($tag, 1);
    $dst = __DIR__ . "/hw";
    if(copyDirectoryRecursively($binDir, $dst)) {
        echo "error occured in copying files from \"" . $bindir
            . "to \"" . $dst . "\" directory\n";
    } else {
        echo "removing \"" . $tmpDir . "\":\n";
        if(removeDirectoryRecursively($tmpDir) > 0) {
            echo "error occured in deleting files from \"" . $tmpDir . "\n";
        } else {
            echo "hello-world-framework/bin installed successfully...\n";
        }
    }
}

// downloaded tags of hello-world-framework/bin
// output is:
// (
//     [0] => Array
//         (
//             [ref] => refs/tags/v0.1.0
//             [node_id] => MDM6UmVmMzEzMDI3NjI3OnJlZnMvdGFncy92MC4xLjA=
//             [url] => https://api.github.com/repos/hello-world-framework/routing-engine/git/refs/tags/v0.1.0
//             [object] => Array
//                 (
//                     [sha] => aad3ca5013245133907ce8b6b431a9817b18e85e
//                     [type] => tag
//                     [url] => https://api.github.com/repos/hello-world-framework/routing-engine/git/tags/aad3ca5013245133907ce8b6b431a9817b18e85e
//                 )

//         )
//     [1] => Array
//         (
//             [ref] => refs/tags/v0.1.1
//             [node_id] => MDM6UmVmMzEzMDI3NjI3OnJlZnMvdGFncy92MC4xLjE=
//             [url] => https://api.github.com/repos/hello-world-framework/routing-engine/git/refs/tags/v0.1.1
//             [object] => Array
//                 (
//                     [sha] => 32cdc75e7c7c4fc5292d443764a9539660e26154
//                     [type] => tag
//                     [url] => https://api.github.com/repos/hello-world-framework/routing-engine/git/tags/32cdc75e7c7c4fc5292d443764a9539660e26154
//                 )
//         )
// )
function downloadBinTags() {
    echo "> downloading hello-world-framework/bin tags...\n";
    $options = [
        "http" => [
            "method" => "GET",
            "header" => [
                "User-Agent: Anonymous"
            ]
        ]
    ];
    try {
        $context = stream_context_create($options);
        $content = file_get_contents(
            "https://api.github.com/repos/hello-world-framework/bin/git/refs/tags",
            false,
            $context
        );
        // the content is a json string
        // so, we can convert it into php array and print it
        $tags = json_decode($content, true);
    } catch(\Exception $e) {
        echo $e->getMessage();
        echo "\n";
    }
    echo "tags downloaded successfully...\n";
    return $tags;
}

// show help message for -h or --help option
function showHelpMessage() {
    global $hwBinSetupMeta;
    echo $hwBinSetupMeta["name"] . ", version " . $hwBinSetupMeta["version"] . "\n";
    echo "Usage:    php hw-bin-setup.php [option]\n";
    echo "Options:\n";
    echo "          --help or -h(shortcut)\n";
    echo "          --bin=vX.Y.X(here, X,Y,Z represents version number of hello-world-framework/bin)\n";
}

// if option not recogzied, this following message is printed
function showOptionNotRecognized() {
    global $argv;
    echo "\"" . $argv[1] .  "\" option not recognized\n";
}



// script starts here
if(count($argv) === 2) {
    $argv[1] = trim($argv[1]);
    if($argv[1] === "--help" || $argv[1] === "-h") {
        showHelpMessage();
    } else if(strpos($argv[1], "--bin") === 0) {
        $tmp = explode("=", $argv[1]);
        if(count($tmp) !== 2) {
            echo "options are not provided properly\n";
        } else {
            $tag = trim($tmp[1]);
            $tagList = downloadBinTags();
            if(checkTagExists(getCleanTags($tagList), $tag)) {
                installBin($tag);
            } else {
                echo "version " . $tag . " not found\n";
            }
        }
    } else {
        showOptionNotRecognized();
    }
} else {
    if(count($argv) === 1) {
        $tagList = downloadBinTags();
        $tag = findLatestTag(getCleanTags($tagList));
        installBin($tag);
    } else {
        showOptionNotRecognized();
    }
}

