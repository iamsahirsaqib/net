<?php
// ----- Single File IPTV Player -----

// Check if a proxied stream is requested
if (isset($_GET['stream'])) {
    $url = $_GET['stream'];
    if (!$url) exit("No stream URL");

    // Proxy the stream to bypass CORS
    header("Content-Type: application/vnd.apple.mpegurl");
    header("Access-Control-Allow-Origin: *");

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["User-Agent: Mozilla/5.0"]);
    $output = curl_exec($ch);
    curl_close($ch);

    echo $output;
    exit;
}

// ----- If not proxying, show the player -----
$playlistURL = "http://a2zhub.one:80/get.php?username=saqibtrial&password=saqib0000&type=m3u_plus&output=hls";
$playlistContent = file_get_contents($playlistURL);
$channels = [];

// Parse M3U playlist
$lines = explode("\n", $playlistContent);
for ($i = 0; $i < count($lines); $i++) {
    if (strpos($lines[$i], "#EXTINF") !== false) {
        $name = trim(explode(",", $lines[$i])[1]);
        $url = trim($lines[$i+1]);
        $channels[] = ["name" => $name, "url" => $url];
        $i++;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>IPTV Player</title>
<style>
body { margin: 0; background: #111; color: #fff; font-family: sans-serif; display: flex; flex-direction: column; align-items: center; }
video { width: 90%; max-width: 900px; height: auto; margin-top: 20px; background: #000; }
#channels { margin-top: 20px; display: flex; flex-wrap: wrap; gap: 10px; justify-content: center; }
.channel { background: #222; padding: 10px 15px; cursor: pointer; border-radius: 5px; transition: 0.2s; }
.channel:hover { background: #0ea5e9; color: #000; }
</style>
</head>
<body>

<h2>Select a Channel</h2>
<div id="channels">
<?php foreach ($channels as $ch): ?>
<div class="channel" onclick="playChannel('<?php echo urlencode($ch['url']); ?>')">
<?php echo htmlspecialchars($ch['name']); ?>
</div>
<?php endforeach; ?>
</div>

<video id="video" controls autoplay></video>

<script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
<script>
const video = document.getElementById('video');

function playChannel(url) {
    // Use this PHP file as a proxy
    const proxiedURL = "<?php echo $_SERVER['PHP_SELF']; ?>?stream=" + url;

    if (video.canPlayType('application/vnd.apple.mpegurl')) {
        video.src = proxiedURL;
        video.play();
    } else if (Hls.isSupported()) {
        const hls = new Hls();
        hls.loadSource(proxiedURL);
        hls.attachMedia(video);
        hls.on(Hls.Events.MANIFEST_PARSED, function() {
            video.play();
        });
    } else {
        alert('Your browser does not support HLS playback.');
    }
}
</script>

</body>
</html>
