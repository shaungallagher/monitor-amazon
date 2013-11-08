<?

// Your email address
$email = 'you@example.com';

// The URL of the Customer Reviews page associated with your product.
// Make sure it's sorted by newest reviews.
$url = 'http://www.amazon.com/Experimenting-Babies-Amazing-Science-Projects/product-reviews/0399162461/ref=cm_cr_dp_see_all_btm?ie=UTF8&showViewpoints=1&sortBy=bySubmissionDateDescending';

// Your database
$link = mysqli_connect("localhost", "username", "password", "database_name");




// YOU NEED NOT EDIT BELOW THIS LINE

include_once('phpQuery.php');

$html = fetch_curl($url);

$doc = phpQuery::newDocument($html);

$body = '';

foreach(pq('table#productReviews > tr > td > a + br + div') as $review) {

    $permalink = pq($review)->find('span.tiny + span + span.tiny > a')->eq(0)->attr('href');

    if ($stmt = mysqli_prepare($link, "SELECT permalink FROM amazon_reviews WHERE permalink = ?")) {

        mysqli_stmt_bind_param($stmt, "s", $permalink);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) == 0) {

            $created = date("Y-m-d h:i a", $child[data][created]);
            $int_created = intval($child[data][created]);

            $stmt2 = mysqli_prepare($link, "INSERT INTO amazon_reviews VALUES (?, ?)");
            mysqli_stmt_bind_param($stmt2, "si", $permalink, $int_created);
            mysqli_stmt_execute($stmt2);

            $stars = substr(pq($review)->find('span.swSprite > span:first')->eq(0)->html(), 0, 3);
            $title = pq($review)->find('div + div > span > b:first')->eq(0)->html();
            $date = pq($review)->find('div + div > span > b + nobr:first')->eq(0)->html();
            $author = pq($review)->find('div > div > div + div > a > span:first')->eq(0)->html();
            pq($review)->children()->empty();
            $text = pq($review)->text();

            $item = <<< EOF

<div class="item" style="border: 1px solid #CCC; margin: 10px; padding: 10px; font-family: arial; font-size: 13px">
<h3 class="title" style="font-size: 16px; font-weight: bold">
    <a href="$permalink" style="color: #009; text-decoration: none" target="_new">$title</a> &nbsp; ($stars stars)
</h3>
<p class="author" style="font-size:13px; font-weight: bold">Author: $author</p>
<p class="text" style="font-size: 13px">$text</p>
<p style="font-size: 13px">Posted: $date</p>
</div>

EOF;

            $body .= $item;
            $results_count++;

        }
    }
}

if ($results_count > 0) {
    mail($email, $results_count.' new reviews ('.date('h:i a').')', $body, "Content-type: text/html\nFrom: Amazon Reviews <$email>");
}

// Remove results that are more than 60 days old
$old_time = time()-5184000;
$stmt = mysqli_prepare($link, "DELETE FROM amazon_reviews WHERE created < ?");
mysqli_stmt_bind_param($stmt, "i", $old_time);
mysqli_stmt_execute($stmt);



function fetch_curl($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_REFERER, $url);
    $source = curl_exec($ch);
    curl_close($ch);
    return $source;
}
