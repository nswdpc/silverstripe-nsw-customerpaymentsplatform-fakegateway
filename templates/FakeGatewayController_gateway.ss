<!doctype>
<html>
<head>
    <title>Fake gateway for testing</title>
    <style>

        body {
            font-family: sans-sans-serif;
            color : #000;
            background-color : #fff;
            font-size : 1rem;
            display : block;
            box-sizing: border-box;
            margin : 0;
            padding : 0;
        }

        body > div {
            width  : 50%;
            margin : 0 auto;
        }

        form {
            width  : 100%;
            border: 1px solid #ccc;
            padding : 0.5rem;
        }

        form p.message {
            font-family: monospace;
            background : #ccc;
            color : #000;
        }

        form label {
            font-size : 1.5rem;
            font-weight : bold;
        }

        form > fieldset,
        form > div {
            margin : 0 0 1rem 0;
            border : none;
            padding : 0.5rem;
        }

        form div.message {
            background : #228800;
            color : #fff;
            padding : 0.5rem;
        }

        form input[type=text] {
            display : block;
            width : 100%;
            margin : 0 0 1rem 0;
            padding : 0.5rem;
            text-align : left;
            border : 2px inset #ddd;
            background : #eee;
            font-size : 1rem;
        }

        form input[type=submit] {
            display : block;
            width : 100%;
            margin : 0 0 1rem 0;
            padding : 0.5rem;
            text-align : center;
            border : 2px outset #ddd;
            background : #eee;
            font-size : 1rem;
        }

    </style>
</head>
<body>
    <div>
    <h1>{$Title.XML}</h1>
    <p>This is a test payment gateway, only</p>
    {$Form}
    </div>
</body>
</html>
