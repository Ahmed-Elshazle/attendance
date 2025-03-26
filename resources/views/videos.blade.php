<!-- resources/views/videos.blade.php -->
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video Gallery</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            text-align: center;
        }
        .video-container {
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 20px 0;
        }
        .video-container video {
            max-width: 100%;
            height: auto;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        .nav-buttons {
            margin-top: 20px;
        }
        .nav-buttons button {
            background-color: #007bff;
            color: #fff;
            border: none;
            padding: 10px 20px;
            margin: 0 10px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .nav-buttons button:hover {
            background-color: #0056b3;
        }
        .download-button {
            margin-top: 20px;
        }
        .download-button a {
            text-decoration: none;
            color: #fff;
            background-color: #28a745;
            padding: 10px 20px;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }
        .download-button a:hover {
            background-color: #218838;
        }
        .video-info {
            margin-top: 10px;
            font-size: 18px;
            color: #333;
        }
    </style>
</head>
<body>
    <h1>Video Gallery</h1>

    <div class="video-container">
        <video id="videoPlayer" controls width="800">
            <source src="{{ asset('videos/' . $currentVideo) }}" type="video/mp4">
            Your browser does not support the video tag.
        </video>
    </div>

    <div class="video-info">
        Current Video: <span id="currentVideoName">{{ $currentVideo }}</span>
    </div>

    <div class="nav-buttons">
        <button id="prevButton">← Previous</button>
        <button id="nextButton">Next →</button>
    </div>

    <div class="download-button">
        <a id="downloadLink" href="{{ asset('videos/' . $currentVideo) }}" download>Download Video</a>
    </div>

    <script>
        const videos = @json($videoNames);
        let currentIndex = @json(array_search($currentVideo, $videoNames));

        const videoPlayer = document.getElementById('videoPlayer');
        const currentVideoName = document.getElementById('currentVideoName');
        const downloadLink = document.getElementById('downloadLink');

        function loadVideo(index) {
            const video = videos[index];
            videoPlayer.src = "{{ asset('videos/') }}/" + video;
            currentVideoName.textContent = video;
            downloadLink.href = "{{ asset('videos/') }}/" + video;
            videoPlayer.play();
        }

        document.getElementById('prevButton').addEventListener('click', () => {
            currentIndex = (currentIndex - 1 + videos.length) % videos.length;
            loadVideo(currentIndex);
        });

        document.getElementById('nextButton').addEventListener('click', () => {
            currentIndex = (currentIndex + 1) % videos.length;
            loadVideo(currentIndex);
        });

        videoPlayer.addEventListener('ended', () => {
            currentIndex = (currentIndex + 1) % videos.length;
            loadVideo(currentIndex);
        });
    </script>
</body>
</html>