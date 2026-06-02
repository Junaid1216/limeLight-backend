<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $seo_title ?? 'Default Title' }}</title>
    <meta name="description" content="{{ $seo_description ?? 'Default Description' }}">
    <meta name="keywords" content="{{ $seo_keywords ?? 'keyword1, keyword2' }}">
    <meta property="og:title" content="{{ $seo_og_title ?? '' }}">
    <meta property="og:description" content="{{ $seo_og_description ?? '' }}">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #ffffff;
            color: #333;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .hero {
            background-color: #ff5608;
            color: #ffffff;
            padding: 100px 20px;
            text-align: center;
            border-radius: 0 0 30px 30px;
        }

        .hero h1 {
            font-size: 3rem;
            font-weight: bold;
        }

        .hero p {
            font-size: 1.2rem;
            margin-top: 10px;
        }

        .content {
            padding: 50px 20px;
            text-align: center;
        }

        .btn-custom {
            background-color: #ff5608;
            color: #ffffff;
            border: none;
            padding: 12px 30px;
            font-size: 1rem;
            border-radius: 25px;
            transition: 0.3s;
        }

        .btn-custom:hover {
            background-color: #e64e00;
            color: #ffffff;
        }
    </style>
</head>

<body>

    <section class="hero">
        <h1>Welcome to Our Ranglerz Digital Marketing</h1>
        <p>Lorem ipsum dolor sit amet consectetur, adipisicing elit. Ipsa doloribus veritatis saepe quos nulla unde,
            praesentium voluptatum quam temporibus provident fugit est a autem expedita corporis ipsum numquam rem
            reprehenderit!.</p>
    </section>

    <section class="content">
        <h2>About Ranglerz</h2>
        <p>Lorem, ipsum dolor sit amet consectetur adipisicing elit. Eveniet aliquid deserunt quo recusandae
            reprehenderit nihil neque fugit rerum molestiae libero ad quos quae tempora, expedita maxime aliquam commodi
            aut.</p>
        <button class="btn btn-custom mt-4">Learn More</button>
    </section>

</body>

</html>
