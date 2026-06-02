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
            padding: 60px 20px;
        }

        .contact-form {
            background-color: #ff5608;
            padding: 40px;
            border-radius: 20px;
            color: white;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            margin: auto;
        }

        .form-control {
            border-radius: 10px;
        }

        .btn-submit {
            background-color: white;
            color: #ff5608;
            font-weight: bold;
            border: none;
            border-radius: 30px;
            padding: 10px 30px;
            transition: 0.3s ease-in-out;
        }

        .btn-submit:hover {
            background-color: #ff5608;
            color: white;
            border: 1px solid white;
        }
    </style>
</head>

<body>

    <div class="contact-form">
        <h2 class="text-center mb-4">Contact Us</h2>
        <form>
            <div class="mb-3">
                <input type="text" class="form-control" placeholder="Your Name" required>
            </div>
            <div class="mb-3">
                <input type="email" class="form-control" placeholder="Your Email" required>
            </div>
            <div class="mb-3">
                <textarea class="form-control" rows="4" placeholder="Your Message" required></textarea>
            </div>
            <div class="text-center">
                <button type="submit" class="btn btn-submit">Send Message</button>
            </div>
        </form>
    </div>

</body>

</html>
