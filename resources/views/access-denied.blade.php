<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <title>access denied</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>

    <body>
        <div class="flex items-center justify-center h-screen w-full px-52 gap-5">
            <div class=" text-center px-10 flex flex-col items-center gap-5">
                <div class="text-6xl text-gray-600">
                    Access Denied!
                </div>
                <div class="w- text-gray-600 font-serif text-lg">
                    We're sorry, but you do not have
                    required permission to access this
                    page. Please contact the site
                    administrator.
                </div>
                <div>
                    <span>Go to</span>
                    <a href="{{ route('dashboard') }}">
                        <button class="bg-red-300 border rounded-md px-2 py-1">Dashboard</button>
                    </a>
                </div>
            </div>
            <div>
                <img src="{{ asset('images/access-denied.gif') }}" alt="Image">
            </div>
        </div>
    </body>

</html>
