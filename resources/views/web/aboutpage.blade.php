<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>{{ $seo_title ?? 'Default Title' }}</title>
    <meta name="description" content="{{ $seo_description ?? 'Default Description' }}">
    <meta name="keywords" content="{{ $seo_keywords ?? 'keyword1, keyword2' }}">
    <meta property="og:title" content="{{ $seo_og_title ?? '' }}">
    <meta property="og:description" content="{{ $seo_og_description ?? '' }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #ffffff;
            color: #333;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 60px 20px;
        }

        .about-box {
            background-color: #ff5608;
            color: white;
            border-radius: 20px;
            padding: 50px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .about-box h2 {
            font-size: 2.5rem;
            margin-bottom: 20px;
        }

        .about-box p {
            font-size: 1.2rem;
            line-height: 1.8;
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="about-box">
            <h2>About Us</h2>
            <p>
                Welcome to our amazing platform! We are dedicated to delivering creative and user-friendly experiences.
                Our mission is to build beautiful websites with modern design and performance in mind.
            </p>
        </div>
    </div>

</body>

</html>
