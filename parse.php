<?php
@mkdir("posts/");
@mkdir("cache/");

$link = "https://www.doostihaa.com/";
$total_pages = 2285;

// Functions
function parsePage(int $page_index) : array {
	global $link;

	$url = $link . "page/$page_index/";
	$data = file_get_contents($url);

	$regex = '/<a class=\"moreslinks\" target=\"_blank\" href=\"([^\"]+)\"/i';
	preg_match_all($regex, $data, $matches);

	return $matches[1];
}

function remove_style_scripts(string $content) : string {
	// <script type="text/javascript">
	$content = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', "", $content);
	// <style></style>
	$content = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', "", $content);
	return $content;
}

function parsePost(string $post_link) : ?array {
	if (file_exists("cache/" . md5($post_link))) {
		$data = file_get_contents("cache/". md5($post_link));
	} else {
		$data = file_get_contents($post_link);
		file_put_contents("cache/" . md5($post_link), $data);
	}
	if ($data === null || $data === "") return null;

	// title
	$title = null;
	$regex = '/\">([^\<]+)<\/a>([\n\s]+|)<span class=\"biigli3/is';
	preg_match($regex, $data, $_title);
	if (isset($_title[1])) $title = $_title[1];

	// content
	$content = null;
	$regex = '/<div class="article_txtc"><div class="textkian0">(.*)<\/div><div class="tags_boxes">/si';
	preg_match($regex, $data, $_content);
	if (isset($_content[1])) $content = $_content[1];
	$content = html_entity_decode($content);
	$content = remove_style_scripts($content);

	// tags
	$tag_content = "";
	$tags = [];
	$regex ='/<div class=\"tags_boxes\">(.*?)<\/div><div class=\"clear/si';
	preg_match($regex, $data, $_tags);
	if (isset($_tags[1])) $tag_content = $_tags[1];

	$regex = '/\/tag\/([^\"]+)/i';
	preg_match_all($regex, $tag_content, $_tags);
	if (isset($_tags[1])) $tags = $_tags[1];
	foreach ($tags as $key => $tag) {
		unset($tags[$key]);
		$key = urldecode($tag);
		$tags[$key] = "https://www.doostihaa.com/tag/" . $tag;
	}

	// views
	$views = null;
	$regex = '/<i class="fa fa-lg fa-eye"><\/i><\/span> ([0-9]+) بازدید/i';
	preg_match($regex, $data, $_views);
	if (isset($_views[1])) $views = $_views[1];

	// likes
	$likes = null;
	$regex = '/> ([0-9]+) <i class=\'fa fa-thumbs-up\' title=\'می‌پسندم\'>/i';
	preg_match($regex, $data, $_likes);
	if (isset($_likes[1])) $likes = $_likes[1];

	// dislikes
	$dislikes = null;
	$regex = '/> ([0-9]+) <i class=\'fa fa-thumbs-down\' title=\'نمی‌پسندم\'>/i';
	preg_match($regex, $data, $_dislikes);
	if (isset($_dislikes[1])) $dislikes = $_dislikes[1];

	// date
	$date = null;
	$regex = '/<i class=\"fa fa-lg fa-clock-o\"><\/i><\/span> ([^\<]+)<\/li>/i';
	preg_match($regex, $data, $_date);
	if (isset($_date[1])) $date = $_date[1];

	// image
	$image = null;
	$regex = '/srcset=\"(https:\/\/www.doostihaa.com\/img\/uploads\/([^\" ]+))/i';
	preg_match($regex, $data, $_image);
	if (isset($_image[1])) $image = $_image[1];

	// var_dump($title);
	// var_dump($content);
	// var_dump($tags);
	var_dump($views);
	var_dump($likes);
	var_dump($dislikes);
	var_dump($date);
	var_dump($image);

	$post = [];
	$post["title"] = $title;
	$post["content"] = $content;
	$post["tags"] = $tags;
	$post["views"] = $views;
	$post["likes"] = $likes;
	$post["dislikes"] = $dislikes;
	$post["date"] = $date;
	$post["image"] = $image;

	return $post;
}

function savePost(array $post) : void {
	file_put_contents("posts/" . $post["title"] . ".txt", json_encode($post));
}

// Test
$sample_link = "https://www.doostihaa.com/1401/08/19/%D9%82%D8%B3%D9%85%D8%AA-%D8%B3%DB%8C%D8%B2%D8%AF%D9%87%D9%85-%D8%B3%D8%B1%DB%8C%D8%A7%D9%84-%D8%AE%D9%88%D9%86-%D8%B3%D8%B1%D8%AF.html";
$post = parsePost($sample_link);
// print_r($post);
exit();

// Main
for ($page = 1; $page <= $total_pages; $page++) {
	print "Page $page\n";
	$post_links = parsePage($page);
	print_r($post_links);

	foreach ($post_links as $post_link) {
		print "Parse post $post_link\n";
		$post = parsePost($post_link);
		if ($post !== null) {
			savePost($post);
		}
		break;
	}

	exit();
}
