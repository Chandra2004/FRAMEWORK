<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 Not Found - Kiwkiw MVC</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🐦</text></svg>">
</head>
<body class="bg-gradient-to-br from-gray-900 to-gray-800 min-h-screen flex items-center justify-center">
    <div class="text-center">
        <!-- Animated 404 Text -->
        <div class="mb-8 animate-pulse">
            <span class="text-9xl font-bold bg-gradient-to-r from-cyan-400 to-blue-500 bg-clip-text text-transparent">
                404
            </span>
        </div>

        <!-- Content Card -->
        <div class="bg-gray-800/50 backdrop-blur-sm rounded-2xl p-8 border border-gray-700/50 mx-4">
            <h2 class="text-3xl font-semibold text-gray-100 mb-4">
                Page Not Found
            </h2>
            <p class="text-gray-400 max-w-md mb-6">
                Oops! The page you're looking for has flown the nest. Maybe it's migrated to a new route?
            </p>

            <!-- Home Button -->
            <a href="{{ url('/') }}" 
               class="inline-block bg-gradient-to-r from-cyan-500 to-blue-600 text-white px-8 py-3 rounded-lg font-medium 
                      hover:from-cyan-600 hover:to-blue-700 transition-all shadow-2xl hover:shadow-cyan-500/20">
                Return to Home
            </a>
        </div>

        <!-- Footer Note -->
        <p class="mt-8 text-gray-500 text-sm">
            Still lost? Check our 
            <a href="https://github.com/Chandra2004/Kiwkiw-Native" target="_blank" class="text-cyan-400 hover:underline">documentation</a>
        </p>
    </div>
</body>
</html>