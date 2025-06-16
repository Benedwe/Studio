<?php
$api_key = '32b4627cf1acd32b148c093618c89f90';
$username = 'Benedwe';


$url = "http://ws.audioscrobbler.com/2.0/?method=user.gettoptracks&user=$username&api_key=$api_key&format=json&limit=5";
$response = file_get_contents($url);
$data = json_decode($response, true);

if (isset($data['toptracks']['track'])) {
    echo "<h3>Top Tracks for $username</h3><ul>";
    foreach ($data['toptracks']['track'] as $track) {
        $name = htmlspecialchars($track['name']);
        $artist = htmlspecialchars($track['artist']['name']);
        echo "<li>$name by $artist</li>";
    }
    echo "</ul>";
} else {
    echo "Could not fetch tracks. Check your API key and username.";
}
?>